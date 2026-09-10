<?php
/**
 * Search relevance — widen the match, then rank by where the words landed.
 *
 * WordPress core searches with AND across every word and orders by date. On a
 * site whose titles are written as plain English that combination is close to
 * useless: "travelling with IBD" required the literal strings "travelling",
 * "with" and "IBD" to all appear, which returned three long articles and
 * missed the handout actually called "Your IBD Travel Checklist" — "travel"
 * is not a substring of "travelling", and the top hit was a piece on perianal
 * Crohn's that mentions IBD once in passing.
 *
 * Two changes, both display-time and both removable by deleting the require
 * line in functions.php:
 *
 * 1. MATCH WIDER. Stopwords are dropped, every remaining word is truncated to
 *    a crude stem, and the words are OR'd rather than AND'd. "travelling"
 *    becomes "travel", which matches Travel, travels and travelling alike.
 * 2. RANK TIGHTER. Widening alone would bury the good result, so each row
 *    carries a computed score: the whole phrase in the title outscores every
 *    other signal, then words in the title, then the phrase or the words in
 *    the opening ~100 words, then a bare mention anywhere in the body.
 *
 * Recipes (vance_recipe) and the patient-download handouts (ordinary posts
 * carrying _vpd_pdf_file) are searchable already — neither sets
 * exclude_from_search — so nothing here has to add them. What kept them out
 * of results was the AND rule above, not their post type.
 *
 * A quoted query is left alone: WP_Query sets `sentence` for it and core's
 * literal phrase match is exactly what someone typing quotes asked for.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Words that would match most of the site and so carry no signal.
 *
 * Deliberately short. Every word dropped here is a word that can no longer
 * rank a result, so anything with clinical meaning stays in — "with" goes,
 * "can" goes, but nothing resembling a symptom, food or condition does.
 *
 * @return string[]
 */
function vance_search_stopwords() {
	return array(
		'a', 'about', 'am', 'an', 'and', 'any', 'are', 'as', 'at', 'be', 'been',
		'but', 'by', 'can', 'do', 'does', 'for', 'from', 'had', 'has', 'have',
		'how', 'i', 'if', 'in', 'is', 'it', 'its', 'me', 'my', 'of', 'on', 'or',
		'our', 'so', 'than', 'that', 'the', 'their', 'them', 'then', 'there',
		'these', 'they', 'this', 'to', 'too', 'was', 'we', 'were', 'what',
		'when', 'which', 'who', 'why', 'will', 'with', 'you', 'your',
	);
}

/**
 * Crude stem: the first 60% of a long word, floor four characters.
 *
 * Not a real stemmer, and deliberately not one. A Porter implementation would
 * turn "travelling" into "travell", which still fails to match "Travel" under
 * a LIKE — the doubled consonant is the whole problem. Truncating to a prefix
 * sidesteps the English spelling rules entirely:
 *
 *   travelling (10) -> travel     matches Travel, travels, travelling
 *   digestive   (9) -> digest     matches digestion, digestive
 *   conditions (10) -> condit     matches condition, conditions
 *   colitis     (7) -> colit      matches colitis
 *
 * Words of five characters or fewer are left whole: shortening "flare" or
 * "Crohn" buys nothing and starts matching unrelated words.
 *
 * The looseness is affordable because a stem-only hit is scored on its own,
 * lighter tier in vance_search_score_sql() — it can rank a result, but never
 * above one that matched the word the visitor actually typed.
 *
 * CHARACTERS, not bytes. strlen()/substr() would cut a Hebrew or Cyrillic word
 * mid-sequence — the tokeniser accepts any Unicode letter — and hand MySQL a
 * LIKE pattern containing a broken UTF-8 sequence, which matches nothing at
 * best and is refused by wpdb at worst.
 *
 * @param string $word Lowercased search word.
 * @return string
 */
function vance_search_stem( $word ) {
	$length = vance_search_strlen( $word );
	if ( $length <= 5 ) {
		return $word;
	}
	$cut = max( 4, (int) ceil( $length * 0.6 ) );

	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $word, 0, $cut, 'UTF-8' );
	}

	// No mbstring: substr() is safe on ASCII and only on ASCII. A non-ASCII
	// word goes through whole rather than being cut into a broken sequence —
	// a narrower match, which is the right direction to fail in.
	return preg_match( '/^[\x20-\x7E]*$/', $word ) ? substr( $word, 0, $cut ) : $word;
}

/**
 * Character length, falling back to bytes only where mbstring is absent.
 *
 * @param string $word
 * @return int
 */
function vance_search_strlen( $word ) {
	return function_exists( 'mb_strlen' ) ? mb_strlen( $word, 'UTF-8' ) : strlen( $word );
}

/**
 * The search words worth using, or an empty array when there are none.
 *
 * Returns at most five. Each word adds up to six CASE expressions to the score
 * — which MySQL evaluates twice per candidate row, once for the SELECT and
 * once for the ORDER BY — plus three leading-wildcard LIKEs in the WHERE. Five
 * is past any real query and keeps a deliberately expensive one, typed by
 * anyone with a browser and no cookie, from being expensive enough to matter.
 *
 * @param string $query Raw search string.
 * @return string[] Lowercased words, punctuation stripped, stopwords removed.
 */
function vance_search_terms( $query ) {
	$query = strtolower( wp_strip_all_tags( (string) $query ) );
	$words = preg_split( '/[^\p{L}\p{N}]+/u', $query, -1, PREG_SPLIT_NO_EMPTY );

	if ( empty( $words ) ) {
		return array();
	}

	$stopwords = vance_search_stopwords();
	$kept      = array();

	foreach ( $words as $word ) {
		if ( vance_search_strlen( $word ) < 2 || in_array( $word, $stopwords, true ) ) {
			continue;
		}
		$kept[] = $word;
	}

	// A query made entirely of stopwords ("how do I") still has to search for
	// something, so fall back to the words as typed. Re-apply a length floor
	// while doing it: without one, "?s=a" came back through here as LIKE '%a%'
	// against the title, excerpt and content of every post on the site, which
	// is both a useless result page and the cheapest way to make the database
	// work hard from an anonymous GET. Nothing usable left means nothing to
	// rank, so hand the query back to core rather than inventing terms.
	if ( empty( $kept ) ) {
		foreach ( $words as $word ) {
			if ( vance_search_strlen( $word ) >= 3 ) {
				$kept[] = $word;
			}
		}
	}

	return array_slice( array_values( array_unique( $kept ) ), 0, 5 );
}

/**
 * Whether this query is the one to rewrite.
 *
 * @param WP_Query $query
 * @return bool
 */
function vance_search_should_rank( $query ) {
	if ( is_admin() || ! $query instanceof WP_Query ) {
		return false;
	}
	if ( ! $query->is_search() || ! $query->is_main_query() ) {
		return false;
	}
	/*
	 * Quoted query: core's literal phrase match is what was asked for, so get
	 * out of its way.
	 *
	 * The quotes have to be detected in `s` itself. WP_Query does NOT set
	 * `sentence` for a quoted query — it handles quotes inside parse_search()'s
	 * own term regex, and `sentence` is only ever set from the URL or by a
	 * pre_get_posts callback. Testing `sentence` alone therefore never fired,
	 * and a visitor searching "low FODMAP" got the loose OR match anyway. It
	 * is still honoured, for the callback that sets it deliberately.
	 */
	$raw = (string) $query->get( 's' );
	if ( ! empty( $query->query_vars['sentence'] ) || preg_match( '/"[^"]{2,}"/', $raw ) ) {
		return false;
	}

	return array() !== vance_search_terms( $query->get( 's' ) );
}

/**
 * Replace core's AND-across-every-word WHERE with an OR across stems.
 *
 * @param string   $search Core's search clause.
 * @param WP_Query $query
 * @return string
 */
function vance_search_where( $search, $query ) {
	global $wpdb;

	if ( ! vance_search_should_rank( $query ) ) {
		return $search;
	}

	$clauses = array();

	foreach ( vance_search_terms( $query->get( 's' ) ) as $term ) {
		$like      = '%' . $wpdb->esc_like( vance_search_stem( $term ) ) . '%';
		$clauses[] = $wpdb->prepare(
			"({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s OR {$wpdb->posts}.post_content LIKE %s)",
			$like,
			$like,
			$like
		);
	}

	$search = ' AND (' . implode( ' OR ', $clauses ) . ')';

	// Core adds this itself and we have just discarded core's clause, so it
	// has to be re-added or search would expose password-protected posts.
	if ( ! is_user_logged_in() ) {
		$search .= " AND ({$wpdb->posts}.post_password = '')";
	}

	return $search;
}
add_filter( 'posts_search', 'vance_search_where', 10, 2 );

/**
 * The scoring expression, shared by the SELECT and the ORDER BY.
 *
 * Weights are ordered by how strongly each signal predicts that the visitor
 * wanted this post, not by anything measured — there is no click data on this
 * site to fit them against. What matters is the ordering between tiers:
 *
 *   120  whole phrase in the title        "IBD travel" in "Your IBD Travel Checklist"
 *    30  each typed word in the title     per word, so two beat one
 *    45  whole phrase in the opening      the article is *about* this
 *    12  each typed word in the opening
 *     3  each typed word anywhere         a passing mention, and ranked like one
 *
 * A title carrying two of the words (60) therefore outranks a long article
 * that happens to contain all of them in its body (3 each), which is exactly
 * the perianal-Crohn's-for-"travelling with IBD" case this exists to fix.
 *
 * The "opening" is the first 1200 characters of post_content. The brief was
 * the first 100 words; 100 words of prose is roughly 600 characters, and the
 * extra allowance covers the block-editor comments and shortcode wrappers
 * that sit in front of the first sentence in the stored content.
 *
 * Every tier is scored TWICE where the stem differs from the word typed: once
 * for the exact word, and again, at roughly 60%, for the stem. Scoring the
 * exact word alone does not work, and the query this was built for is the
 * proof — no post on the site contains the literal string "travelling", so
 * "travelling with IBD" scored the word at zero everywhere and left every
 * IBD-titled article tied on the "ibd" half alone, the handout among them.
 * Crediting the stem separates them (title "Travel" earns 18 where an exact
 * title hit would earn 30) while still ranking a post that used the visitor's
 * actual word above one that only matched a prefix of it.
 *
 * @param string[] $terms
 * @return string SQL expression.
 */
function vance_search_score_sql( $terms ) {
	global $wpdb;

	$phrase = implode( ' ', $terms );
	$lede   = "LEFT({$wpdb->posts}.post_content, 1200)";
	$body   = "CONCAT({$wpdb->posts}.post_excerpt, ' ', {$lede})";
	$parts  = array();

	if ( count( $terms ) > 1 ) {
		$phrase_like = '%' . $wpdb->esc_like( $phrase ) . '%';
		$parts[]     = $wpdb->prepare( "(CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN 120 ELSE 0 END)", $phrase_like );
		$parts[]     = $wpdb->prepare( "(CASE WHEN {$lede} LIKE %s THEN 45 ELSE 0 END)", $phrase_like );
	}

	foreach ( $terms as $term ) {
		$stem    = vance_search_stem( $term );
		$weights = array( $term => array( 30, 12, 3 ) );
		if ( $stem !== $term ) {
			$weights[ $stem ] = array( 18, 7, 2 );
		}

		foreach ( $weights as $needle => $tier ) {
			$like    = '%' . $wpdb->esc_like( $needle ) . '%';
			$parts[] = $wpdb->prepare( "(CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN {$tier[0]} ELSE 0 END)", $like );
			$parts[] = $wpdb->prepare( "(CASE WHEN {$body} LIKE %s THEN {$tier[1]} ELSE 0 END)", $like );
			$parts[] = $wpdb->prepare( "(CASE WHEN {$wpdb->posts}.post_content LIKE %s THEN {$tier[2]} ELSE 0 END)", $like );
		}
	}

	return implode( ' + ', $parts );
}

/**
 * Carry the score on every row so ORDER BY can sort on it.
 *
 * @param string   $fields
 * @param WP_Query $query
 * @return string
 */
function vance_search_fields( $fields, $query ) {
	if ( ! vance_search_should_rank( $query ) ) {
		return $fields;
	}

	return $fields . ', (' . vance_search_score_sql( vance_search_terms( $query->get( 's' ) ) ) . ') AS vance_search_score';
}
add_filter( 'posts_fields', 'vance_search_fields', 10, 2 );

/**
 * Score first, newest first inside a tie.
 *
 * The expression is repeated rather than referenced by its alias. MySQL would
 * accept the alias here — core counts found rows with SQL_CALC_FOUND_ROWS and
 * a follow-up SELECT FOUND_ROWS(), not a second query that re-runs this filter
 * without a field list. Repeating it keeps posts_orderby self-contained, which
 * is what the filter's contract asks for and what survives another plugin
 * rewriting the SELECT list; the cost is that MySQL evaluates the CASE
 * expressions twice per candidate row, which is why vance_search_terms() caps
 * the word count.
 *
 * @param string   $orderby
 * @param WP_Query $query
 * @return string
 */
function vance_search_orderby( $orderby, $query ) {
	global $wpdb;

	if ( ! vance_search_should_rank( $query ) ) {
		return $orderby;
	}

	return '(' . vance_search_score_sql( vance_search_terms( $query->get( 's' ) ) ) . ") DESC, {$wpdb->posts}.post_date DESC";
}
add_filter( 'posts_orderby', 'vance_search_orderby', 10, 2 );
