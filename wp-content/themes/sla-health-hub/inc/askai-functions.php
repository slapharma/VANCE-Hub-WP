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
	$content = preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $content );
	$content = wp_strip_all_tags( $content );
	$content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );
	$content = trim( preg_replace( '/\s+/u', ' ', (string) $content ) );

	if ( '' === $content ) {
		$content = trim( wp_strip_all_tags( (string) $post->post_excerpt ) );
	}
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
		foreach ( $terms as $term ) {
			if ( false !== strpos( $lower, $term ) ) {
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

	// The article the reader is looking at is always the primary source, and gets
	// a much larger budget — highlight-to-ask depends on it.
	$context_post_id = absint( $context_post_id );
	if ( $context_post_id ) {
		$post = get_post( $context_post_id );
		if ( $post instanceof WP_Post && 'publish' === $post->post_status && in_array( $post->post_type, $types, true ) ) {
			$sources[] = array(
				'id'      => $post->ID,
				'title'   => get_the_title( $post ),
				'url'     => get_permalink( $post ),
				'excerpt' => vance_ai_build_excerpt( $post, $terms, 1200 ),
				'primary' => true,
			);
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
			'title'   => get_the_title( $post ),
			'url'     => get_permalink( $post ),
			'excerpt' => $excerpt,
			'primary' => false,
		);
	}

	return array_slice( $sources, 0, $max );
}

// =========================================================================
// Prompt
// =========================================================================

/**
 * Assemble the system instruction: the rules, then the retrieved sources.
 *
 * @param array[] $sources Output of vance_ai_retrieve_sources().
 * @return string
 */
function vance_ai_system_prompt( $sources ) {
	$rules = <<<'PROMPT'
You are the Vance Medical Hub assistant on vancehealthhub.co.uk — a library of articles about inflammatory bowel disease (IBD), gastrointestinal health and clinical nutrition.

You answer ONLY from the SOURCES below. The SOURCES are extracts from articles published on this hub. They are your entire world: you have no other knowledge and no access to the internet.

RULES
1. Ground every statement in the SOURCES. Never introduce facts, figures, drug names, guidelines or study results that do not appear in them.
2. If the SOURCES do not answer the question, say so plainly — for example: "I could not find anything in the Vance Medical Hub library that covers that." Then either point to a related topic the SOURCES do cover, or invite the reader to rephrase. Never fall back on general knowledge.
3. Never mention, cite, recommend or link to any website, journal, organisation, guideline body or study outside this hub. Do not write "according to the NHS", "research shows" or similar unless those exact claims appear in a SOURCE.
4. Cite what you used. End your answer with the articles you drew on, one per line, in exactly this form:
Read more: <article title> — <URL>
Copy each URL character-for-character from its SOURCE header. Never invent, shorten or guess a URL, and never cite a source you did not actually use.
5. This is general information, not personal medical advice. Do not diagnose, do not recommend or adjust treatment or dosing, and do not interpret a reader's own test results. Point anything urgent to their clinical team, NHS 111, or 999 in an emergency.
6. Tone: professional, clinical, warm and plain-spoken. Translate jargon into everyday language.
7. FORMATTING: clean, readable prose. Do NOT use Markdown headings or any "#" characters. You may use **bold** for key terms and simple hyphen (-) bullet points for short lists. No tables, no code blocks.
PROMPT;

	if ( empty( $sources ) ) {
		return $rules . "\n\nSOURCES\nNo articles in the Vance Medical Hub library matched this question. Tell the reader you could not find hub content covering it and invite them to rephrase or ask about a different topic. Do NOT answer from general knowledge, and do not cite anything.\n";
	}

	$block = "\n\nSOURCES\n\n";
	foreach ( $sources as $source ) {
		$label = ! empty( $source['primary'] )
			? 'PRIMARY SOURCE (the article the reader is currently reading)'
			: 'SOURCE';

		$block .= '--- ' . $label . ': ' . $source['title'] . ' | URL: ' . $source['url'] . " ---\n";
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
	}
);

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

	// --- Credentials -------------------------------------------------------
	// Read from the Customizer (Appearance → Customize → Ask AI Configuration).
	// Do NOT hardcode keys here; they end up in public git history and on the
	// deployed web server.
	$api_key = vance_get_theme_mod( 'vance_askai_api_key', '' );
	if ( empty( $api_key ) ) {
		return new WP_Error(
			'ai_api_key_missing',
			__( 'AI API key is not configured. Site admin: set it in Appearance → Customize → Ask AI Configuration.', 'sla-health-hub' ),
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
			'content' => vance_ai_system_prompt( $sources ),
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
					'max_tokens'  => 1000,
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
