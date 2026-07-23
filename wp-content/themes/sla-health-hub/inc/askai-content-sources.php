<?php
/**
 * VANCE-Ai virtual content sources.
 *
 * Two bodies of hub content are invisible to the normal WP_Query retrieval in
 * askai-functions.php, because neither lives in post_content:
 *
 *   1. GI Health conditions. page-gi-condition.php renders each condition from a
 *      hard-coded `case` block of literal HTML, so the pages have real
 *      permalinks but effectively empty post_content.
 *   2. IBD Recipes. A static Next.js export under assets/tools/ibd-recipes/,
 *      iframed by the recipes page. Not WordPress content at all.
 *
 * This file turns both into a cached corpus of plain-text documents that the
 * retrieval step can score and cite, so the assistant can answer from them and
 * link the reader to the right page.
 *
 * The recipes are read from their 19 pre-rendered HTML pages rather than the
 * minified JS array that also holds them. The array is a JS object literal with
 * unquoted keys, and the regex needed to make it JSON would corrupt any value
 * containing a comma followed by a word and a colon, which recipe instructions
 * genuinely contain. The rendered pages carry the same text with no such risk.
 *
 * @package sla-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =========================================================================
// GI Health conditions
// =========================================================================

/**
 * Condition slugs, in the order the hub presents them.
 *
 * @return string[]
 */
function vance_ai_gi_condition_slugs() {
	return array(
		'inflammatory-bowel-disease',
		'crohns-disease',
		'ulcerative-colitis',
		'microscopic-colitis',
		'irritable-bowel-syndrome',
		'colorectal-cancer',
		'diverticular-disease',
	);
}

/**
 * Pull each condition's prose out of the page template.
 *
 * The template selects a condition with `switch ( $slug )`, and each case body
 * is literal HTML apart from image src attributes, so slicing between
 * `case '<slug>':` and the following `break;` is deterministic. PHP fragments
 * are stripped before the HTML is flattened.
 *
 * @return array[] Documents keyed by index: {id, title, url, text, kind}.
 */
function vance_ai_gi_documents() {
	$template = get_template_directory() . '/page-gi-condition.php';
	if ( ! is_readable( $template ) ) {
		return array();
	}

	$source = file_get_contents( $template ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( false === $source ) {
		return array();
	}

	$documents = array();

	foreach ( vance_ai_gi_condition_slugs() as $slug ) {
		$start = strpos( $source, "case '" . $slug . "':" );
		if ( false === $start ) {
			continue;
		}

		$end = strpos( $source, 'break;', $start );
		if ( false === $end ) {
			continue;
		}

		$block = substr( $source, $start, $end - $start );

		// Drop the PHP fragments (image src echoes, the case statement itself).
		// The case body opens by closing PHP mode, so an orphaned closing tag is
		// left behind once the paired blocks are gone; clear stray tokens too.
		// (Do not write a literal closing tag in a line comment: it ends PHP.)
		$block = preg_replace( '/<\?php.*?\?>/s', ' ', $block );
		$block = preg_replace( '/<\?=.*?\?>/s', ' ', $block );
		$block = str_replace( "case '" . $slug . "':", ' ', $block );
		$block = str_replace( array( '<?php', '<?=', '?>' ), ' ', $block );

		$text = vance_ai_html_to_text( $block );
		if ( str_word_count( $text ) < 40 ) {
			continue;
		}

		$page  = get_page_by_path( 'gi-health/' . $slug );
		$title = $page ? get_the_title( $page ) : ucwords( str_replace( '-', ' ', $slug ) );
		$url   = $page ? get_permalink( $page ) : home_url( '/gi-health/' . $slug . '/' );

		$documents[] = array(
			'id'    => 'gi:' . $slug,
			'title' => $title,
			'url'   => $url,
			'text'  => $text,
			'kind'  => 'gi',
		);
	}

	return $documents;
}

// =========================================================================
// IBD Recipes
// =========================================================================

/**
 * Read the pre-rendered recipe pages from the static export.
 *
 * Each recipe has its own directory, and the directory name is the recipe id
 * used in its public URL, so the citation link is exact.
 *
 * @return array[] Documents: {id, title, url, text, kind}.
 */
function vance_ai_recipe_documents() {
	$root = get_template_directory() . '/assets/tools/ibd-recipes/recipes';
	if ( ! is_dir( $root ) ) {
		return array();
	}

	$base      = get_template_directory_uri() . '/assets/tools/ibd-recipes/recipes/';
	$documents = array();

	foreach ( (array) glob( $root . '/*', GLOB_ONLYDIR ) as $dir ) {
		$file = $dir . '/index.html';
		if ( ! is_readable( $file ) ) {
			continue;
		}

		$html = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $html ) {
			continue;
		}

		$id = basename( $dir );

		// The page title carries a site suffix; keep only the recipe name.
		$title = ucwords( str_replace( '-', ' ', $id ) );
		if ( preg_match( '#<title>(.*?)</title>#is', $html, $m ) ) {
			$raw   = html_entity_decode( trim( $m[1] ), ENT_QUOTES, 'UTF-8' );
			$parts = explode( '|', $raw );
			$title = trim( $parts[0] );
		}

		// Each page has a single <main>; taking it drops the site nav, header and
		// footer, which would otherwise lead every excerpt with the same chrome.
		if ( preg_match( '#<main\b[^>]*>(.*?)</main>#is', $html, $main ) ) {
			$html = $main[1];
		}

		$text = vance_ai_html_to_text( $html );
		if ( str_word_count( $text ) < 40 ) {
			continue;
		}

		// Lead with the name and a line of context, so a snippet lifted from the
		// middle of the page still reads as a recipe from this hub.
		$documents[] = array(
			'id'    => 'recipe:' . $id,
			'title' => $title,
			'url'   => $base . $id . '/',
			'text'  => $title . '. Gut-friendly recipe from the Vance Medical Hub IBD recipe collection. ' . $text,
			'kind'  => 'recipe',
		);
	}

	return $documents;
}

// =========================================================================
// Corpus
// =========================================================================

/**
 * The full virtual corpus, cached.
 *
 * The cache key folds in the template's modification time and the recipe
 * directory listing, so editing either invalidates it without anyone having to
 * remember to purge.
 *
 * @return array[]
 */
function vance_ai_virtual_documents() {
	static $memo = null;
	if ( null !== $memo ) {
		return $memo;
	}

	$template   = get_template_directory() . '/page-gi-condition.php';
	$recipe_dir = get_template_directory() . '/assets/tools/ibd-recipes/recipes';

	$fingerprint = md5(
		( is_readable( $template ) ? (string) filemtime( $template ) : '0' ) . '|' .
		( is_dir( $recipe_dir ) ? (string) filemtime( $recipe_dir ) : '0' )
	);

	$key    = 'vance_ai_virtual_docs_' . $fingerprint;
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		$memo = $cached;
		return $memo;
	}

	$documents = array_merge( vance_ai_gi_documents(), vance_ai_recipe_documents() );

	set_transient( $key, $documents, 12 * HOUR_IN_SECONDS );
	$memo = $documents;
	return $memo;
}

/**
 * Score the corpus against the conversation and return the best matches.
 *
 * Plain term counting: a hit in the title counts for more than a hit in the
 * body, because these documents are long and a title match is a much stronger
 * signal of what the reader means.
 *
 * @param array    $messages Conversation.
 * @param string[] $terms    Extracted search terms.
 * @return array[] Source blocks in the shape vance_ai_system_prompt() expects.
 */
function vance_ai_virtual_sources( $messages, $terms ) {
	$limit = (int) apply_filters( 'vance_ai_virtual_source_limit', 3 );
	if ( $limit < 1 || empty( $terms ) ) {
		return array();
	}

	$documents = vance_ai_virtual_documents();
	if ( empty( $documents ) ) {
		return array();
	}

	$scored = array();
	foreach ( $documents as $index => $doc ) {
		$title_l = ' ' . strtolower( $doc['title'] ) . ' ';
		$text_l  = ' ' . strtolower( $doc['text'] ) . ' ';
		$score   = 0;

		foreach ( $terms as $term ) {
			if ( strlen( $term ) < 3 ) {
				continue;
			}
			$score += 8 * substr_count( $title_l, $term );
			$score += min( 6, substr_count( $text_l, $term ) );
		}

		if ( $score > 0 ) {
			$scored[] = array(
				'score' => $score,
				'order' => $index,
				'doc'   => $doc,
			);
		}
	}

	if ( empty( $scored ) ) {
		return array();
	}

	usort(
		$scored,
		function ( $a, $b ) {
			if ( $a['score'] === $b['score'] ) {
				return $a['order'] - $b['order'];
			}
			return $b['score'] - $a['score'];
		}
	);

	$sources = array();
	foreach ( array_slice( $scored, 0, $limit ) as $hit ) {
		$doc     = $hit['doc'];
		$excerpt = vance_ai_excerpt_from_text( $doc['text'], $terms, 'recipe' === $doc['kind'] ? 320 : 420 );
		if ( '' === $excerpt ) {
			continue;
		}

		$sources[] = array(
			'id'      => $doc['id'],
			'title'   => $doc['title'],
			'url'     => $doc['url'],
			'excerpt' => $excerpt,
			'primary' => false,
		);
	}

	return $sources;
}
