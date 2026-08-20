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
 *   2. Recipes. Native `vance_recipe` CPT posts (inc/recipe-cpt.php) — real
 *      WordPress content with real permalinks, but the bulk of what a recipe
 *      *is* (ingredients, method, nutrition) lives in post meta rather than
 *      post_content, so the normal WP_Query retrieval in askai-functions.php
 *      would only ever see the intro/"why this works" prose.
 *
 * This file turns both into a cached corpus of plain-text documents that the
 * retrieval step can score and cite, so the assistant can answer from them and
 * link the reader to the right page.
 *
 * @package vance-health-hub
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
 * Resolve the WP page for a condition.
 *
 * The template assumes the conditions are children of a `gi-health` parent, but
 * on the live site they sit at the top level, so /gi-health/<slug>/ answers with
 * a 301 to /<slug>/. Trying the child path first keeps working if the hierarchy
 * is ever restored; falling back to the bare slug gives the canonical permalink
 * and the real page title, apostrophes and all, today.
 *
 * @param string $slug Condition slug.
 * @return WP_Post|null
 */
function vance_ai_find_gi_page( $slug ) {
	$page = get_page_by_path( 'gi-health/' . $slug );
	if ( ! $page ) {
		$page = get_page_by_path( $slug );
	}
	return ( $page instanceof WP_Post ) ? $page : null;
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

		$page  = vance_ai_find_gi_page( $slug );
		$title = $page ? vance_ai_clean_title( get_the_title( $page ) ) : ucwords( str_replace( '-', ' ', $slug ) );
		$url   = $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
		$url   = set_url_scheme( $url, 'https' );

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
// Recipes
// =========================================================================

/**
 * Build a recipe document from the `vance_recipe` CPT — title, category,
 * intro/"why this works" prose, ingredients, method and a nutrition summary,
 * assembled from post_content plus post meta (inc/recipe-cpt.php,
 * inc/recipe-admin.php) since the recipe itself doesn't live in post_content
 * alone.
 *
 * @return array[] Documents: {id, title, url, text, kind}.
 */
function vance_ai_recipe_documents() {
	$documents = array();

	foreach ( vance_recipe_all_posts() as $post ) {
		$cat_terms = get_the_terms( $post->ID, 'vance_recipe_cat' );
		$category  = ( $cat_terms && ! is_wp_error( $cat_terms ) && isset( $cat_terms[0] ) ) ? $cat_terms[0]->name : '';

		$intro = vance_ai_html_to_text( $post->post_content );

		$ingredients      = get_post_meta( $post->ID, '_vance_recipe_ingredients', true );
		$ingredient_lines = array();
		foreach ( (array) $ingredients as $section ) {
			foreach ( (array) ( isset( $section['items'] ) ? $section['items'] : array() ) as $item ) {
				$ingredient_lines[] = (string) $item;
			}
		}

		$method_lines = array_values( (array) get_post_meta( $post->ID, '_vance_recipe_method', true ) );

		$kcal = get_post_meta( $post->ID, '_vance_recipe_kcal', true );
		$nutrition_summary = '';
		if ( '' !== $kcal ) {
			$nutrition_summary = sprintf(
				'Nutrition per serving: %d kcal, %dg protein, %dg carbs, %dg fat, %dg fibre.',
				(int) $kcal,
				(int) get_post_meta( $post->ID, '_vance_recipe_protein_g', true ),
				(int) get_post_meta( $post->ID, '_vance_recipe_carbs_g', true ),
				(int) get_post_meta( $post->ID, '_vance_recipe_fat_g', true ),
				(int) get_post_meta( $post->ID, '_vance_recipe_fibre_g', true )
			);
		}

		$text = implode(
			' ',
			array_filter(
				array(
					$category ? "Category: {$category}." : '',
					$intro,
					$nutrition_summary,
					$ingredient_lines ? 'Ingredients: ' . implode( ', ', $ingredient_lines ) . '.' : '',
					$method_lines ? 'Method: ' . implode( ' ', $method_lines ) : '',
				)
			)
		);

		if ( str_word_count( $text ) < 40 ) {
			continue;
		}

		// Raw post_title, not get_the_title(): the latter HTML-entity-encodes
		// ampersands, which would read as literal "&#038;" in the AI's context
		// (see inc/recipe-catalogue.php for the same fix, there for a different
		// reason — name-matching saved plans rather than prompt readability).
		$title = $post->post_title;

		$documents[] = array(
			'id'    => 'recipe:' . $post->post_name,
			'title' => $title,
			'url'   => set_url_scheme( get_permalink( $post ), 'https' ),
			'text'  => $title . '. Gut-friendly recipe from the Vance Medical Hub recipe collection. ' . $text,
			'kind'  => 'recipe',
		);
	}

	return $documents;
}

/**
 * Cache-busting fingerprint for the recipe half of the corpus: count plus
 * the newest post_modified, so adding, editing or unpublishing any recipe
 * invalidates the cached corpus without anyone having to remember to purge
 * it — the CPT equivalent of the old recipe-directory filemtime check.
 *
 * @return string
 */
function vance_ai_recipe_corpus_fingerprint() {
	$posts = vance_recipe_all_posts();
	$newest = 0;
	foreach ( $posts as $post ) {
		$newest = max( $newest, strtotime( $post->post_modified_gmt ) );
	}
	return count( $posts ) . '|' . $newest;
}

// =========================================================================
// Corpus
// =========================================================================

/**
 * The full virtual corpus, cached.
 *
 * The cache key folds in the GI template's modification time and the recipe
 * corpus fingerprint (count + newest post_modified), so editing either
 * invalidates it without anyone having to remember to purge.
 *
 * @return array[]
 */
function vance_ai_virtual_documents() {
	static $memo = null;
	if ( null !== $memo ) {
		return $memo;
	}

	$template = get_template_directory() . '/page-gi-condition.php';

	$fingerprint = md5(
		( is_readable( $template ) ? (string) filemtime( $template ) : '0' ) . '|' .
		vance_ai_recipe_corpus_fingerprint()
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
