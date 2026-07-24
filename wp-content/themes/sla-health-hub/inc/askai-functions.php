<?php
/**
 * Ask AI — grounded chat over Vance Medical Hub content.
 *
 * The assistant answers ONLY from articles published on this site. Every request
 * runs a multi-pass keyword retrieval over the hub, and the matched extracts (with
 * their permalinks) are the model's entire world — it is instructed to refuse
 * anything the extracts do not cover and to cite the hub articles it used.
 *
 * Powers three surfaces, all through the same REST route:
 *   - the /ask-ai/ page          (inline mount)
 *   - the site-wide chat modal   (homepage card, Discovery "Ask" tab)
 *   - highlight-to-ask           (reader selects text in an article)
 *
 * For logged-in users every exchange is auto-saved into the `_sla_saved_chats`
 * user meta key, which Dashboard → My AI Chats already reads. That meta key is
 * load-bearing and must never be renamed (see CLAUDE.md).
 *
 * @package sla-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =========================================================================
// Retrieval
// =========================================================================

/**
 * Post types the assistant is allowed to read from.
 *
 * GI Health hub/condition pages and the Turn Evidence page are WP pages, so
 * 'page' is included. Podcast/webinar/course/infographic are excluded: they are
 * media wrappers with little body text to ground an answer in.
 *
 * @return string[]
 */
function vance_ai_source_post_types() {
	return apply_filters(
		'vance_ai_source_post_types',
		array( 'post', 'page', 'news', 'research', 'oped', 'review', 'whitepaper' )
	);
}

/**
 * Words that carry no retrieval signal — ordinary English glue plus the filler
 * that shows up in chat questions ("can you explain what ... means").
 *
 * @return array<string,bool> Lookup keyed by word for O(1) checks.
 */
function vance_ai_stopwords() {
	static $lookup = null;
	if ( null !== $lookup ) {
		return $lookup;
	}

	$words = array(
		// Articles, pronouns, prepositions, conjunctions.
		'the', 'and', 'but', 'for', 'nor', 'yet', 'his', 'her', 'its', 'our', 'their', 'they', 'them',
		'this', 'that', 'these', 'those', 'there', 'here', 'with', 'from', 'into', 'onto', 'upon',
		'about', 'above', 'after', 'again', 'against', 'because', 'been', 'before', 'being', 'below',
		'between', 'both', 'down', 'during', 'each', 'few', 'further', 'have', 'has', 'had', 'having',
		'more', 'most', 'other', 'over', 'same', 'some', 'such', 'than', 'then', 'through', 'under',
		'until', 'very', 'were', 'was', 'will', 'would', 'should', 'could', 'shall', 'must', 'may',
		'might', 'you', 'your', 'yours', 'are', 'not', 'any', 'all', 'can', 'get', 'got', 'out',
		'off', 'own', 'too', 'who', 'whom', 'whose', 'why', 'how', 'when', 'where', 'which', 'while',
		'does', 'did', 'doing', 'done', 'just', 'also', 'ever', 'even', 'much', 'many', 'like',
		// Chat filler.
		'please', 'explain', 'explanation', 'tell', 'ask', 'asking', 'question', 'answer', 'what',
		'mean', 'means', 'meaning', 'help', 'know', 'understand', 'simple', 'simply', 'give', 'want',
		'need', 'thanks', 'thank', 'hello', 'article', 'passage', 'text', 'says', 'said', 'read',
		'reading', 'page', 'post', 'site', 'hub', 'information', 'info', 'something', 'anything',
	);

	$lookup = array_fill_keys( $words, true );
	return $lookup;
}

/**
 * Split free text into usable search tokens.
 *
 * Possessives are trimmed so "Crohn's" becomes "crohn", which still matches the
 * apostrophe form via WordPress's LIKE search.
 *
 * @param string $text Raw text.
 * @return string[] Lower-case tokens, stopwords removed.
 */
function vance_ai_tokenise( $text ) {
	$text = strtolower( wp_strip_all_tags( (string) $text ) );
	$text = str_replace( array( '’', '‘' ), "'", $text );
	$text = preg_replace( "/'s\b/", '', $text );
	$text = preg_replace( '/[^a-z0-9\-\s]+/', ' ', $text );

	$parts     = preg_split( '/\s+/', (string) $text, -1, PREG_SPLIT_NO_EMPTY );
	$stopwords = vance_ai_stopwords();
	$tokens    = array();

	foreach ( (array) $parts as $part ) {
		$part = trim( $part, '-' );
		if ( strlen( $part ) < 3 || isset( $stopwords[ $part ] ) || is_numeric( $part ) ) {
			continue;
		}
		$tokens[] = $part;
	}

	return $tokens;
}

/**
 * Pull the highest-signal search terms out of a conversation.
 *
 * The most recent user turn is weighted heaviest, but the two before it still
 * contribute so follow-ups like "and what about children?" keep the earlier
 * subject in play.
 *
 * @param array $messages Conversation, oldest first.
 * @param int   $limit    Maximum terms to return.
 * @return string[]
 */
function vance_ai_extract_terms( $messages, $limit = 8 ) {
	$user_turns = array();
	foreach ( array_reverse( (array) $messages ) as $message ) {
		if ( isset( $message['role'] ) && 'user' === $message['role'] ) {
			$user_turns[] = (string) $message['content'];
		}
		if ( count( $user_turns ) >= 3 ) {
			break;
		}
	}

	$scores  = array();
	$weights = array( 3, 2, 1 );
	foreach ( $user_turns as $index => $turn ) {
		$weight = isset( $weights[ $index ] ) ? $weights[ $index ] : 1;
		foreach ( vance_ai_tokenise( $turn ) as $token ) {
			$scores[ $token ] = ( isset( $scores[ $token ] ) ? $scores[ $token ] : 0 ) + $weight;
		}
	}

	arsort( $scores );
	return array_slice( array_keys( $scores ), 0, $limit );
}

/**
 * Run one search pass and return matching post IDs.
 *
 * @param string   $search  Search string.
 * @param string[] $types   Post types.
 * @param int      $limit   Max results.
 * @param int[]    $exclude Post IDs already collected.
 * @return int[]
 */
function vance_ai_query_ids( $search, $types, $limit, $exclude = array() ) {
	$search = trim( (string) $search );
	if ( '' === $search || $limit < 1 ) {
		return array();
	}

	$query = new WP_Query(
		array(
			's'                   => $search,
			'post_type'           => $types,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'post__not_in'        => array_map( 'absint', $exclude ),
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	return array_map( 'absint', (array) $query->posts );
}

/**
 * Build a readable extract from a post, centred on the first search-term hit.
 *
 * Templates that render hard-coded copy (the GI Health condition pages, for
 * example) have thin post_content — those fall back to the excerpt and, failing
 * that, are dropped by the caller rather than offered as an empty source.
 *
 * @param WP_Post  $post   Post object.
 * @param string[] $terms  Search terms to centre on.
 * @param int      $budget Approximate word budget.
 * @return string Extract, or '' when the post has no usable body text.
 */
function vance_ai_build_excerpt( $post, $terms, $budget = 300 ) {
	$content = strip_shortcodes( (string) $post->post_content );
	$content = vance_ai_html_to_text( $content );

	if ( '' === $content ) {
		$content = trim( wp_strip_all_tags( (string) $post->post_excerpt ) );
	}

	return vance_ai_excerpt_from_text( $content, $terms, $budget );
}

/**
 * Decode a post title for use in a source header and a citation line.
 *
 * WordPress texturises titles, so a possessive arrives as "Crohn&#8217;s". The
 * client escapes ampersands before rendering, which would print the entity
 * verbatim in the "Read more" link, so titles are decoded here at the source.
 *
 * @param string $title Raw title.
 * @return string
 */
function vance_ai_clean_title( $title ) {
	$title = html_entity_decode( (string) $title, ENT_QUOTES, 'UTF-8' );
	return trim( preg_replace( '/\s+/u', ' ', $title ) );
}

/**
 * Flatten a chunk of HTML into readable plain text.
 *
 * @param string $html Raw HTML.
 * @return string
 */
function vance_ai_html_to_text( $html ) {
	$text = preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', ' ', (string) $html );
	$text = wp_strip_all_tags( $text );
	$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
	return trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
}

/**
 * Take a readable window out of plain text, centred on the first term hit.
 *
 * Shared by post excerpts and by the virtual sources in askai-content-sources.php,
 * so every source block is trimmed the same way.
 *
 * @param string   $content Plain text.
 * @param string[] $terms   Search terms to centre on.
 * @param int      $budget  Approximate word budget.
 * @return string Extract, or '' when there is no usable text.
 */
function vance_ai_excerpt_from_text( $content, $terms, $budget = 300 ) {
	$content = trim( (string) $content );
	if ( '' === $content ) {
		return '';
	}

	$words = preg_split( '/\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY );
	$total = count( $words );
	if ( $total <= $budget ) {
		return implode( ' ', $words );
	}

	// Centre the window on the first term hit so the relevant passage survives.
	$hit = -1;
	foreach ( $words as $index => $word ) {
		$lower = strtolower( $word );
		foreach ( (array) $terms as $term ) {
			if ( '' !== $term && false !== strpos( $lower, $term ) ) {
				$hit = $index;
				break 2;
			}
		}
	}

	$start = ( $hit < 0 ) ? 0 : max( 0, $hit - (int) floor( $budget / 3 ) );
	$slice = array_slice( $words, $start, $budget );

	$excerpt = implode( ' ', $slice );
	if ( $start > 0 ) {
		$excerpt = '… ' . $excerpt;
	}
	if ( ( $start + $budget ) < $total ) {
		$excerpt .= ' …';
	}

	return $excerpt;
}

/**
 * Find the hub articles that should ground this answer.
 *
 * Three passes, because WordPress's `s` parameter is AND-matching: a full
 * conversational sentence frequently matches nothing on its own.
 *   1. Phrase   — first ~12 words of the question.
 *   2. AND      — the top few extracted terms together.
 *   3. Per-term — each term on its own, until the quota is filled.
 *
 * @param array $messages        Conversation, oldest first.
 * @param int   $context_post_id Article the reader is currently on, if any.
 * @return array[] List of {id, title, url, excerpt, primary}.
 */
function vance_ai_retrieve_sources( $messages, $context_post_id = 0 ) {
	$max = (int) vance_get_theme_mod( 'vance_askai_max_sources', 5 );
	$max = max( 3, min( 8, $max ) );

	$types   = vance_ai_source_post_types();
	$terms   = vance_ai_extract_terms( $messages );
	$sources = array();
	$exclude = array();

	// The curated knowledge base comes first: glossary definitions and the facts
	// about Vance Medical Hub, Vance Medical Foods and SLA Pharma that no article
	// covers. It has its own budget so it never crowds out article sources.
	$kb_sources = function_exists( 'vance_kb_retrieve_sources' )
		? vance_kb_retrieve_sources( $messages, $terms )
		: array();

	// GI Health conditions and IBD recipes, neither of which lives in
	// post_content and so cannot be found by the searches below. Also on its own
	// budget. See inc/askai-content-sources.php.
	$virtual_sources = function_exists( 'vance_ai_virtual_sources' )
		? vance_ai_virtual_sources( $messages, $terms )
		: array();

	// The article the reader is looking at is always the primary source, and gets
	// a much larger budget — highlight-to-ask depends on it.
	$context_post_id = absint( $context_post_id );
	if ( $context_post_id ) {
		$post = get_post( $context_post_id );
		if ( $post instanceof WP_Post && 'publish' === $post->post_status && in_array( $post->post_type, $types, true ) ) {
			$primary_excerpt = vance_ai_build_excerpt( $post, $terms, 1200 );

			// Template-driven pages (the Ask AI page itself, the GI Health
			// condition pages) carry little or no post_content. Adding one as an
			// empty PRIMARY SOURCE is worse than adding nothing: it presents the
			// model with a titled, authoritative-looking block and no facts.
			if ( str_word_count( $primary_excerpt ) >= 20 ) {
				$sources[] = array(
					'id'      => $post->ID,
					'title'   => vance_ai_clean_title( get_the_title( $post ) ),
					'url'     => get_permalink( $post ),
					'excerpt' => $primary_excerpt,
					'primary' => true,
				);
			}
			$exclude[] = $post->ID;
		}
	}

	$last_user_message = '';
	foreach ( array_reverse( (array) $messages ) as $message ) {
		if ( isset( $message['role'] ) && 'user' === $message['role'] ) {
			$last_user_message = (string) $message['content'];
			break;
		}
	}

	$candidates = array();
	$needed     = $max - count( $sources );

	if ( $needed > 0 && '' !== $last_user_message ) {
		// Pass 1 — phrase.
		$candidates = vance_ai_query_ids( wp_trim_words( $last_user_message, 12, '' ), $types, $needed, $exclude );
		$exclude    = array_merge( $exclude, $candidates );

		// Pass 2 — top terms ANDed together.
		if ( count( $candidates ) < $needed && count( $terms ) > 1 ) {
			$and_search = implode( ' ', array_slice( $terms, 0, 4 ) );
			$more       = vance_ai_query_ids( $and_search, $types, $needed - count( $candidates ), $exclude );
			$candidates = array_merge( $candidates, $more );
			$exclude    = array_merge( $exclude, $more );
		}

		// Pass 3 — each term on its own.
		if ( count( $candidates ) < $needed ) {
			foreach ( $terms as $term ) {
				if ( count( $candidates ) >= $needed ) {
					break;
				}
				$more       = vance_ai_query_ids( $term, $types, 2, $exclude );
				$candidates = array_merge( $candidates, $more );
				$exclude    = array_merge( $exclude, $more );
			}
		}
	}

	foreach ( $candidates as $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		$excerpt = vance_ai_build_excerpt( $post, $terms, 300 );

		// A source with no body text can't ground anything — offering it as an
		// empty block invites the model to fill the gap from memory.
		if ( str_word_count( $excerpt ) < 20 ) {
			continue;
		}

		$sources[] = array(
			'id'      => $post->ID,
			'title'   => vance_ai_clean_title( get_the_title( $post ) ),
			'url'     => get_permalink( $post ),
			'excerpt' => $excerpt,
			'primary' => false,
		);
	}

	return array_merge( $kb_sources, $virtual_sources, array_slice( $sources, 0, $max ) );
}

// =========================================================================
// Prompt
// =========================================================================

/**
 * The reading levels a reader can pick from with the on-screen slider.
 *
 * Keys are stored/sent by the client; each entry carries the label shown in the
 * UI and the instruction handed to the model.
 *
 * @return array<string,array{label:string,instruction:string}>
 */
function vance_ai_reading_levels() {
	return array(
		'simple'        => array(
			'label'       => __( 'Basic', 'sla-health-hub' ),
			'instruction' => 'READING LEVEL: SIMPLE. Write for someone with no medical background and no prior knowledge of the condition. Use short sentences and everyday words. Never use a clinical or Latin term without immediately explaining it in plain English in the same sentence. Prefer "gut" over "gastrointestinal tract", "swelling" over "inflammation" (you may give the medical word in brackets once). Avoid statistics and study detail unless they are essential; if you give a number, say plainly what it means. Keep the whole answer short.',
		),
		'knowledgeable' => array(
			'label'       => __( 'Advanced', 'sla-health-hub' ),
			'instruction' => 'READING LEVEL: KNOWLEDGEABLE. Write for an informed reader who already lives with or works around this condition. You may use common clinical terms (inflammation, remission, flare, biologics, mucosa) without defining them, but briefly gloss anything more specialist the first time it appears. Include relevant detail and nuance, and mention figures or study findings where the sources give them.',
		),
		'expert'        => array(
			'label'       => __( 'Expert', 'sla-health-hub' ),
			'instruction' => 'READING LEVEL: EXPERT. Write for a healthcare professional. Use precise clinical terminology without simplification or glossing. Reference mechanisms, drug classes and named agents, disease phenotypes, scoring systems and study findings exactly as the sources present them. Be concise and information-dense; do not pad with lay explanations or reassurance.',
		),
	);
}

/**
 * Normalise a client-supplied reading level to a known key.
 *
 * @param mixed $level Raw value.
 * @return string
 */
function vance_ai_normalise_reading_level( $level ) {
	$level  = is_string( $level ) ? strtolower( trim( $level ) ) : '';
	$levels = vance_ai_reading_levels();
	return isset( $levels[ $level ] ) ? $level : 'knowledgeable';
}

/**
 * Assemble the system instruction: the rules, the reading level, then the sources.
 *
 * @param array[] $sources       Output of vance_ai_retrieve_sources().
 * @param string  $reading_level One of the vance_ai_reading_levels() keys.
 * @return string
 */
function vance_ai_system_prompt( $sources, $reading_level = 'knowledgeable' ) {
	$rules = <<<'PROMPT'
You are VANCE-Ai, the assistant on the Vance Medical Hub (vancehealthhub.co.uk), a library of articles about inflammatory bowel disease (IBD), gastrointestinal health and clinical nutrition.

The SOURCES below are extracts from this hub's own library and reference material. They are your primary and strongly preferred basis for answering.

RULES
1. Ground every substantive statement in the SOURCES. Never introduce clinical facts, figures, drug names, guidelines or study results that do not appear in them.
2. If the SOURCES do not cover the question:
   a. For a basic, uncontroversial factual or definitional question, such as what an abbreviation stands for, what a common word or term means, a general-knowledge fact: answer it briefly and correctly from your own general knowledge. When you do, add this on its own line, worded exactly like this:
      Note: that last part is general knowledge, not taken from the Vance Medical Hub library.
   b. For anything clinical or health-related, such as symptoms, causes, diagnosis, treatment, medicines, dosing, prognosis, diet or lifestyle advice, interpreting results: do NOT answer from general knowledge. Say plainly that you could not find it in the library, then point to a related topic the SOURCES do cover or invite the reader to rephrase.
3. Never cite, recommend or link to any website, journal, organisation, guideline body or study outside this hub. Do not write "according to the NHS" or "research shows" unless that exact claim appears in a SOURCE.
4. Cite what you used. End your answer with the hub articles you drew on, one per line, in exactly this form:
Read more: <article title> | <URL>
Copy each URL character-for-character from its SOURCE header. Never invent, shorten or guess a URL, and never cite a source you did not actually use. Reference entries that carry no URL are not cited this way.
5. This is general information, not personal medical advice. Do not diagnose, do not recommend or adjust treatment or dosing, and do not interpret a reader's own test results. Point anything urgent to their clinical team, NHS 111, or 999 in an emergency.
6. Tone: professional, clinical, warm and plain-spoken.
7. FORMATTING: clean, readable prose. Do NOT use Markdown headings or any "#" characters. You may use **bold** for key terms and simple hyphen (-) bullet points for short lists. No tables, no code blocks.
8. PUNCTUATION: never use an em dash or an en dash anywhere in your reply. Use a comma, a colon, a full stop or brackets instead. This applies to every line, including the citation lines.
9. LENGTH: keep answers focused, around 300 words, unless the reader explicitly asks for more depth. Never let the answer run so long that the citation lines get cut off: the "Read more" lines matter more than the last paragraph, so finish the prose early enough to write them in full.
PROMPT;

	$levels = vance_ai_reading_levels();
	$key    = vance_ai_normalise_reading_level( $reading_level );
	$rules .= "\n\n" . $levels[ $key ]['instruction'];

	if ( empty( $sources ) ) {
		return $rules . "\n\nSOURCES\nNothing in the Vance Medical Hub library matched this question. Follow rule 2: answer only if it is a basic definitional or general-knowledge question, with the required note; otherwise say you could not find hub content covering it. Do not cite anything.\n";
	}

	$block = "\n\nSOURCES\n\n";
	foreach ( $sources as $source ) {
		if ( ! empty( $source['reference'] ) ) {
			$label = 'REFERENCE (Vance Medical Hub knowledge base: authoritative, but not a public article)';
		} elseif ( ! empty( $source['primary'] ) ) {
			$label = 'PRIMARY SOURCE (the article the reader is currently reading)';
		} else {
			$label = 'SOURCE';
		}

		$block .= '--- ' . $label . ': ' . $source['title'];
		$block .= ! empty( $source['url'] ) ? ' | URL: ' . $source['url'] : ' | (no public URL, do not cite a link for this entry)';
		$block .= " ---\n";
		$block .= $source['excerpt'] . "\n";
		$block .= "--- END SOURCE ---\n\n";
	}

	return $rules . $block;
}

// =========================================================================
// Conversation storage
// =========================================================================

/**
 * Upsert a conversation into the user's saved chats.
 *
 * Keyed by the client's conversation id so repeated exchanges update one entry
 * instead of piling up. The stored shape matches what Dashboard → My AI Chats
 * already renders (id / title / transcript / date), plus an `updated` stamp.
 *
 * A title set by the user via the rename action is preserved.
 *
 * @param int    $user_id         User.
 * @param string $conversation_id Client-generated conversation id.
 * @param array  $transcript      Full conversation, oldest first.
 * @return bool True when stored.
 */
function vance_ai_autosave_conversation( $user_id, $conversation_id, $transcript ) {
	$user_id = absint( $user_id );
	if ( ! $user_id || '' === $conversation_id || empty( $transcript ) ) {
		return false;
	}

	$key   = 'chat_' . $conversation_id;
	$now   = current_time( 'mysql' );
	$chats = get_user_meta( $user_id, '_sla_saved_chats', true );
	if ( ! is_array( $chats ) ) {
		$chats = array();
	}

	$transcript = array_slice( $transcript, -40 );

	$auto_title = '';
	foreach ( $transcript as $message ) {
		if ( 'user' === $message['role'] ) {
			$auto_title = wp_trim_words( $message['content'], 8, '…' );
			break;
		}
	}
	if ( '' === $auto_title ) {
		$auto_title = __( 'AI conversation', 'sla-health-hub' );
	}

	$updated = false;
	foreach ( $chats as $index => $chat ) {
		if ( ! is_array( $chat ) || ! isset( $chat['id'] ) || $chat['id'] !== $key ) {
			continue;
		}
		$chats[ $index ]['transcript'] = $transcript;
		$chats[ $index ]['updated']    = $now;
		if ( empty( $chats[ $index ]['title'] ) ) {
			$chats[ $index ]['title'] = $auto_title;
		}
		if ( empty( $chats[ $index ]['date'] ) ) {
			$chats[ $index ]['date'] = $now;
		}
		$updated = true;
		break;
	}

	if ( ! $updated ) {
		$chats[] = array(
			'id'         => $key,
			'title'      => $auto_title,
			'transcript' => $transcript,
			'date'       => $now,
			'updated'    => $now,
		);
	}

	if ( count( $chats ) > 50 ) {
		$chats = array_slice( $chats, -50 );
	}

	update_user_meta( $user_id, '_sla_saved_chats', $chats );

	// This write happens in a REST request, so nothing else invalidates the
	// caller's cached dashboard. Drop their private cache copy so the new
	// conversation is visible the moment they open it. No-op without LiteSpeed.
	do_action( 'litespeed_purge_private_all' );

	return true;
}

// =========================================================================
// Abuse guard
// =========================================================================

/**
 * Light per-IP throttle for logged-out visitors.
 *
 * The chat endpoint is public and every call spends OpenRouter credit, so cap
 * how fast one address can burn through it. Logged-in users are exempt.
 *
 * @return bool True when the request should be blocked.
 */
function vance_ai_is_rate_limited() {
	if ( is_user_logged_in() ) {
		return false;
	}

	$limit  = (int) apply_filters( 'vance_ai_guest_rate_limit', 20 );
	$window = (int) apply_filters( 'vance_ai_guest_rate_window', 10 * MINUTE_IN_SECONDS );
	if ( $limit < 1 ) {
		return false;
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( '' === $ip ) {
		return false;
	}

	$transient = 'vance_ai_rl_' . md5( $ip );
	$count     = (int) get_transient( $transient );
	if ( $count >= $limit ) {
		return true;
	}

	set_transient( $transient, $count + 1, $window );
	return false;
}

// =========================================================================
// REST route
// =========================================================================

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'vance-health/v1',
			'/ai-chat',
			array(
				'methods'             => 'POST',
				'callback'            => 'vance_rest_ai_chat',
				'permission_callback' => '__return_true', // Guests may chat; only logged-in chats are stored.
			)
		);

		// Used by the chat's "Clear" button to drop a stored conversation.
		register_rest_route(
			'vance-health/v1',
			'/ai-chat/clear',
			array(
				'methods'             => 'POST',
				'callback'            => 'vance_rest_ai_chat_clear',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
	}
);

/**
 * Delete one stored conversation.
 *
 * The client clears its own view regardless; this removes the saved copy so a
 * cleared conversation does not linger in Dashboard → My AI Chats.
 *
 * @param WP_REST_Request $request Request.
 * @return array|WP_Error
 */
function vance_rest_ai_chat_clear( $request ) {
	$params          = (array) $request->get_json_params();
	$conversation_id = isset( $params['conversation_id'] ) ? (string) $params['conversation_id'] : '';

	if ( ! preg_match( '/^[a-z0-9-]{8,40}$/', $conversation_id ) ) {
		return new WP_Error( 'bad_conversation', __( 'Invalid conversation id.', 'sla-health-hub' ), array( 'status' => 400 ) );
	}

	$user_id = get_current_user_id();
	$chats   = get_user_meta( $user_id, '_sla_saved_chats', true );
	if ( ! is_array( $chats ) ) {
		return array(
			'success' => true,
			'removed' => 0,
		);
	}

	$key  = 'chat_' . $conversation_id;
	$kept = array();
	foreach ( $chats as $chat ) {
		if ( is_array( $chat ) && isset( $chat['id'] ) && $chat['id'] === $key ) {
			continue;
		}
		$kept[] = $chat;
	}

	$removed = count( $chats ) - count( $kept );
	if ( $removed > 0 ) {
		update_user_meta( $user_id, '_sla_saved_chats', $kept );
	}

	return array(
		'success' => true,
		'removed' => $removed,
	);
}

/**
 * Drop a citation line that the model ran out of tokens part-way through.
 *
 * A reply cut off mid-URL would otherwise be rendered as a link to a truncated
 * address, which looks authoritative and goes nowhere. Only the trailing line is
 * considered, and only when the model actually hit the length limit.
 *
 * @param string $reply  Raw reply.
 * @param string $finish finish_reason reported by the API.
 * @return string
 */
function vance_ai_drop_truncated_citation( $reply, $finish ) {
	if ( 'length' !== $finish ) {
		return $reply;
	}

	$lines = explode( "\n", rtrim( $reply ) );
	$last  = trim( (string) end( $lines ) );

	if ( 0 !== stripos( $last, 'Read more:' ) ) {
		return $reply;
	}

	// A complete citation ends in a URL with a path or a trailing slash. Anything
	// shorter than that was cut off mid-address.
	if ( preg_match( '#https?://[^\s]+\.[a-z]{2,}(?:\.[a-z]{2,})?/\S*$#i', $last ) ) {
		return $reply;
	}

	array_pop( $lines );
	return rtrim( implode( "\n", $lines ) );
}

/**
 * Handle a chat turn: retrieve hub sources, ask the model, store the result.
 *
 * @param WP_REST_Request $request Request.
 * @return array|WP_Error
 */
function vance_rest_ai_chat( $request ) {
	$params = (array) $request->get_json_params();

	// --- Normalise the conversation ---------------------------------------
	$raw      = isset( $params['messages'] ) && is_array( $params['messages'] ) ? $params['messages'] : array();
	$messages = array();
	foreach ( $raw as $message ) {
		if ( ! is_array( $message ) || ! isset( $message['content'] ) ) {
			continue;
		}
		$content = trim( wp_strip_all_tags( (string) $message['content'] ) );
		if ( '' === $content ) {
			continue;
		}
		if ( strlen( $content ) > 8000 ) {
			$content = substr( $content, 0, 8000 );
		}
		$role       = ( isset( $message['role'] ) && 'assistant' === $message['role'] ) ? 'assistant' : 'user';
		$messages[] = array(
			'role'    => $role,
			'content' => $content,
		);
	}

	if ( empty( $messages ) ) {
		return new WP_Error( 'no_messages', __( 'No messages provided.', 'sla-health-hub' ), array( 'status' => 400 ) );
	}

	if ( vance_ai_is_rate_limited() ) {
		return new WP_Error(
			'rate_limited',
			__( 'You have sent a lot of questions in a short time. Please wait a few minutes and try again.', 'sla-health-hub' ),
			array( 'status' => 429 )
		);
	}

	$conversation_id = isset( $params['conversation_id'] ) ? (string) $params['conversation_id'] : '';
	if ( ! preg_match( '/^[a-z0-9-]{8,40}$/', $conversation_id ) ) {
		$conversation_id = '';
	}
	$context_post_id = isset( $params['context_post_id'] ) ? absint( $params['context_post_id'] ) : 0;
	$reading_level   = vance_ai_normalise_reading_level( isset( $params['reading_level'] ) ? $params['reading_level'] : '' );

	// --- Credentials -------------------------------------------------------
	// Read from the Customizer (Appearance → Customize → VANCE-Ai Configuration).
	// Do NOT hardcode keys here; they end up in public git history and on the
	// deployed web server.
	$api_key = vance_get_theme_mod( 'vance_askai_api_key', '' );
	if ( empty( $api_key ) ) {
		return new WP_Error(
			'ai_api_key_missing',
			__( 'AI API key is not configured. Site admin: set it in Appearance → Customize → VANCE-Ai Configuration.', 'sla-health-hub' ),
			array( 'status' => 503 )
		);
	}

	$model = trim( (string) vance_get_theme_mod( 'vance_askai_model', '' ) );
	if ( '' === $model ) {
		$model = 'anthropic/claude-opus-4.8'; // Sensible fallback if the setting is unset.
	}

	// --- Ground the answer in hub content ----------------------------------
	$sources = vance_ai_retrieve_sources( $messages, $context_post_id );

	$payload_messages = array(
		array(
			'role'    => 'system',
			'content' => vance_ai_system_prompt( $sources, $reading_level ),
		),
	);
	// Only the recent turns go to the model — the full transcript is still stored.
	foreach ( array_slice( $messages, -12 ) as $message ) {
		$payload_messages[] = $message;
	}

	$response = wp_remote_post(
		'https://openrouter.ai/api/v1/chat/completions',
		array(
			'body'    => wp_json_encode(
				array(
					'model'       => $model,
					'messages'    => $payload_messages,
					'temperature' => 0.2, // Low, to keep the model on the supplied sources.
					// Headroom for the answer plus its citation lines. At 1000 a
					// long answer ran out mid-URL and shipped a broken link; at
					// 1600 a verbose "explain in detail" answer still lost its
					// citations to the truncation guard.
					'max_tokens'  => (int) apply_filters( 'vance_ai_max_tokens', 2000 ),
				)
			),
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
				'HTTP-Referer'  => home_url(),
				'X-Title'       => 'Vance Medical Hub',
			),
			'timeout' => 60,
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error(
			'api_error',
			__( 'Failed to connect to the AI service: ', 'sla-health-hub' ) . $response->get_error_message(),
			array( 'status' => 500 )
		);
	}

	$code    = wp_remote_retrieve_response_code( $response );
	$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== (int) $code ) {
		$detail = isset( $decoded['error']['message'] ) ? $decoded['error']['message'] : __( 'Unknown error', 'sla-health-hub' );
		return new WP_Error(
			'api_error',
			__( 'The AI service returned an error: ', 'sla-health-hub' ) . $detail,
			array( 'status' => $code )
		);
	}

	$reply = isset( $decoded['choices'][0]['message']['content'] ) ? (string) $decoded['choices'][0]['message']['content'] : '';
	if ( '' === trim( $reply ) ) {
		return new WP_Error( 'api_error', __( 'The AI service returned an empty reply.', 'sla-health-hub' ), array( 'status' => 502 ) );
	}

	$finish = isset( $decoded['choices'][0]['finish_reason'] ) ? $decoded['choices'][0]['finish_reason'] : '';
	$reply  = vance_ai_drop_truncated_citation( $reply, $finish );

	// --- Store the conversation -------------------------------------------
	$saved = false;
	if ( is_user_logged_in() && $conversation_id ) {
		$transcript = $messages;
		$transcript[] = array(
			'role'    => 'assistant',
			'content' => $reply,
		);
		$saved = vance_ai_autosave_conversation( get_current_user_id(), $conversation_id, $transcript );
	}

	return array(
		'success' => true,
		'reply'   => $reply,
		'saved'   => $saved,
		'sources' => count( $sources ),
	);
}
