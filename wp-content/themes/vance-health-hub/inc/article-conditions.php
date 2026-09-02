<?php
/**
 * Tie each article to the conditions it is actually about.
 *
 * The seven condition pages carry proper MedicalCondition markup (see
 * inc/medical-schema.php) and the 149 articles about those conditions
 * referenced none of it: an audit on 2026-09-01 found `about` present on 7 of
 * 156 pages, the condition pages themselves. The site's strongest semantic
 * asset sat on seven URLs and connected to nothing.
 *
 * The same gap showed up in the link graph. Strip out the header and footer and
 * there was almost no editorial linking left — a typical article had three to
 * six inbound links and every one of them was navigation. Nothing pointed
 * readers, or authority, from an article at the pillar page written for its
 * subject.
 *
 * One derived mapping fixes both: it emits `about` into the article's schema
 * and prints a link to the pillar page under the copy.
 *
 * How an article gets assigned a condition
 * ---------------------------------------
 * In precision order, because a wrong `about` is worse than a missing one:
 *
 *   1. The condition is named in the post title. Unambiguous.
 *   2. The post carries a matching tag.
 *   3. Failing both, one condition clearly dominates the body — at least five
 *      mentions and at least twice the runner-up.
 *
 * Rule 3 has one exception. Crohn's, ulcerative colitis and microscopic colitis
 * are *kinds of* inflammatory bowel disease, so they do not count as rivals to
 * it: an article using "IBD" thirteen times and "Crohn's" seven is about IBD,
 * and without that clause the dominance test reads the hierarchy as a tie and
 * declines to answer.
 *
 * Measured over the 149 published posts before this shipped: 143 assigned, 125
 * of them a single condition. The six left unassigned are genuinely general —
 * stoma care, two Guts UK campaign items, a microbiome study, an ultrasound
 * platform — and no condition link is the right answer for those.
 *
 * @package Vance_Health_Hub
 * @since   2026-09-01
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conditions that are kinds of inflammatory bowel disease.
 *
 * @return string[]
 */
function vance_ibd_subtypes() {
	return array( 'crohns-disease', 'ulcerative-colitis', 'microscopic-colitis' );
}

/**
 * How each condition is written about, keyed by its page slug.
 *
 * Abbreviations are matched case-sensitively. Lowercased, "uc" and "ibs" turn
 * up inside ordinary words and inside surnames in reference lists.
 *
 * @return array<string,string[]>
 */
function vance_condition_vocabulary() {
	return array(
		'inflammatory-bowel-disease' => array( '~\binflammatory bowel disease\b~i', '~\bIBD\b~' ),
		'ulcerative-colitis'         => array( '~\bulcerative colitis\b~i', '~\bUC\b~' ),
		'crohns-disease'             => array( '~\bcrohn~i' ),
		'microscopic-colitis'        => array( '~\bmicroscopic colitis\b~i', '~\blymphocytic colitis\b~i', '~\bcollagenous colitis\b~i' ),
		'irritable-bowel-syndrome'   => array( '~\birritable bowel syndrome\b~i', '~\bIBS\b~' ),
		'diverticular-disease'       => array( '~\bdiverticul~i' ),
		'colorectal-cancer'          => array( '~\bcolorectal cancer\b~i', '~\bbowel cancer\b~i', '~\bcolon cancer\b~i' ),
	);
}

/**
 * Tag names that name a condition, lowercased.
 *
 * @return array<string,string>
 */
function vance_condition_tag_map() {
	return array(
		'inflammatory bowel disease' => 'inflammatory-bowel-disease',
		'ibd'                        => 'inflammatory-bowel-disease',
		'ulcerative colitis'         => 'ulcerative-colitis',
		'uc'                         => 'ulcerative-colitis',
		"crohn's disease"            => 'crohns-disease',
		'crohns disease'             => 'crohns-disease',
		'microscopic colitis'        => 'microscopic-colitis',
		'irritable bowel syndrome'   => 'irritable-bowel-syndrome',
		'ibs'                        => 'irritable-bowel-syndrome',
		'diverticular disease'       => 'diverticular-disease',
		'colorectal cancer'          => 'colorectal-cancer',
		'bowel cancer'               => 'colorectal-cancer',
	);
}

/**
 * The conditions an article is about.
 *
 * @param int $post_id Post ID.
 * @return string[] Condition page slugs, possibly empty.
 */
function vance_article_conditions( $post_id ) {
	static $memo = array();

	$post_id = (int) $post_id;

	if ( isset( $memo[ $post_id ] ) ) {
		return $memo[ $post_id ];
	}

	$post = get_post( $post_id );

	if ( ! $post || 'post' !== $post->post_type ) {
		$memo[ $post_id ] = array();
		return $memo[ $post_id ];
	}

	$body  = wp_strip_all_tags( $post->post_content );
	$picks = array();
	$body_hits = array();

	foreach ( vance_condition_vocabulary() as $slug => $patterns ) {
		$in_title = 0;
		$in_body  = 0;

		foreach ( $patterns as $pattern ) {
			$in_title += (int) preg_match_all( $pattern, $post->post_title );
			$in_body  += (int) preg_match_all( $pattern, $body );
		}

		if ( $in_title ) {
			$picks[ $slug ] = true;
		}
		if ( $in_body ) {
			$body_hits[ $slug ] = $in_body;
		}
	}

	$tag_map = vance_condition_tag_map();

	foreach ( (array) wp_get_post_tags( $post_id, array( 'fields' => 'names' ) ) as $tag ) {
		$key = strtolower( trim( html_entity_decode( $tag, ENT_QUOTES, 'UTF-8' ) ) );

		if ( isset( $tag_map[ $key ] ) ) {
			$picks[ $tag_map[ $key ] ] = true;
		}
	}

	// Title or tag named it outright — the confident tier, before the fuzzy
	// body-dominance fallback below runs. Cached for vance_article_conditions_confident().
	vance_article_conditions_confident_cache( $post_id, array_keys( $picks ) );

	// Nothing named it outright — fall back to a clearly dominant subject.
	if ( ! $picks && $body_hits ) {
		arsort( $body_hits );
		$top   = key( $body_hits );
		$score = current( $body_hits );

		$rivals = array();
		foreach ( $body_hits as $slug => $count ) {
			if ( $slug === $top ) {
				continue;
			}
			// A parent condition does not compete with its own subtypes.
			if ( 'inflammatory-bowel-disease' === $top && in_array( $slug, vance_ibd_subtypes(), true ) ) {
				continue;
			}
			$rivals[] = $count;
		}

		$runner_up = $rivals ? max( $rivals ) : 0;

		if ( $score >= 5 && $score >= 2 * max( $runner_up, 1 ) ) {
			$picks[ $top ] = true;
		}
	}

	$memo[ $post_id ] = array_keys( $picks );

	return $memo[ $post_id ];
}

/**
 * Shared cache for the "confident" (title/tag-named) tier of condition
 * matches, set by vance_article_conditions() before it runs its fuzzy
 * body-dominance fallback. A plain function-static can't be shared between
 * two functions, hence this small accessor.
 *
 * @param int        $post_id Post ID.
 * @param array|null $set     Pass an array to store it; omit to read.
 * @return array|null
 */
function vance_article_conditions_confident_cache( $post_id, $set = null ) {
	static $cache = array();

	if ( null !== $set ) {
		$cache[ $post_id ] = $set;
	}

	return isset( $cache[ $post_id ] ) ? $cache[ $post_id ] : null;
}

/**
 * The conditions an article is confidently about — named in the title or by
 * a tag, not merely inferred from which condition is mentioned most in the
 * body.
 *
 * Added 2026-09-02 after QA found the fuzzy body-dominance fallback in
 * vance_article_conditions() misfiring on general wellness posts that
 * reference a condition several times as background context without being
 * about it — e.g. a "morning routines for gut health" piece that mentions
 * IBD 8 times in passing cleared the fallback's "5+ mentions, 2x the
 * runner-up" bar and got tagged as an IBD article. That single small pill
 * link was a low-key existing risk; vance_render_related_articles() below
 * turns the same signal into a whole "Related articles" box recommending 3
 * other posts, which makes a wrong assignment far more visible and worth
 * being conservative about. The pill link and the `about` schema property
 * still use the looser vance_article_conditions() unchanged.
 *
 * @param int $post_id Post ID.
 * @return string[] Condition page slugs, possibly empty.
 */
function vance_article_conditions_confident( $post_id ) {
	vance_article_conditions( $post_id ); // Populates the confident cache as a side effect.
	$confident = vance_article_conditions_confident_cache( (int) $post_id );
	return $confident ?? array();
}

/**
 * The published condition page for a slug, or null.
 *
 * @param string $slug Condition slug.
 * @return WP_Post|null
 */
function vance_condition_page( $slug ) {
	static $pages = array();

	if ( ! array_key_exists( $slug, $pages ) ) {
		$page = get_page_by_path( $slug );
		$pages[ $slug ] = ( $page && 'publish' === $page->post_status ) ? $page : null;
	}

	return $pages[ $slug ];
}

/**
 * The MedicalCondition nodes for a post, ready to drop into `about`.
 *
 * Each is written out in full rather than as a bare @id reference. The
 * canonical node lives on the condition page, and sharing its @id is what ties
 * the two together — but a reference whose target is defined on a different URL
 * leaves a dangling node in this page's graph, so name and url travel with it.
 *
 * @param int $post_id Post ID.
 * @return array<int,array<string,string>>
 */
function vance_article_about_nodes( $post_id ) {
	$data  = vance_gi_condition_medical();
	$about = array();

	foreach ( vance_article_conditions( $post_id ) as $slug ) {
		$page = vance_condition_page( $slug );

		if ( ! $page ) {
			continue;
		}

		$url = get_permalink( $page );

		$about[] = array(
			'@type' => 'MedicalCondition',
			'@id'   => $url . '#medicalcondition',
			'name'  => isset( $data[ $slug ]['name'] ) ? $data[ $slug ]['name'] : get_the_title( $page ),
			'url'   => $url,
		);
	}

	return $about;
}

/**
 * Add `about` to the article node in AIOSEO's graph.
 *
 * aioseo_schema_output passes the whole @graph — a list of nodes — not one node
 * at a time. Treating the argument as a single node is silently a no-op: the
 * list has no '@type' key, the guard returns early, and the page ships without
 * the property while every unit test that hands the function one node passes.
 *
 * @param array $graph The assembled @graph array.
 * @return array
 */
function vance_article_about_schema( $graph ) {
	if ( ! is_array( $graph ) || ! is_singular( 'post' ) ) {
		return $graph;
	}

	$about = vance_article_about_nodes( get_the_ID() );

	if ( ! $about ) {
		return $graph;
	}

	foreach ( $graph as $i => $node ) {
		if ( ! is_array( $node ) || ! isset( $node['@type'] ) ) {
			continue;
		}

		if ( array_intersect( (array) $node['@type'], array( 'Article', 'NewsArticle', 'BlogPosting' ) ) ) {
			$graph[ $i ]['about'] = $about;
		}
	}

	return $graph;
}
add_filter( 'aioseo_schema_output', 'vance_article_about_schema' );

/**
 * Print the condition links under an article.
 *
 * Prints nothing when no condition was confidently identified, which is the
 * right answer for a general piece and keeps this from becoming a row of
 * links that mean nothing.
 *
 * @return void
 */
function vance_render_article_conditions() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$slugs = vance_article_conditions( get_the_ID() );

	if ( ! $slugs ) {
		return;
	}

	$data  = vance_gi_condition_medical();
	$links = array();

	foreach ( $slugs as $slug ) {
		$page = vance_condition_page( $slug );

		if ( ! $page ) {
			continue;
		}

		$links[] = sprintf(
			'<a class="va-condition-link" href="%s">%s</a>',
			esc_url( get_permalink( $page ) ),
			esc_html( isset( $data[ $slug ]['name'] ) ? $data[ $slug ]['name'] : get_the_title( $page ) )
		);
	}

	if ( ! $links ) {
		return;
	}

	printf(
		'<aside class="va-article-conditions" aria-label="%s"><span class="va-conditions-label">%s</span><span class="va-conditions-list">%s</span></aside>',
		esc_attr__( 'Conditions covered in this article', 'vance-health-hub' ),
		esc_html( _n( 'This article covers', 'This article covers', count( $links ), 'vance-health-hub' ) ),
		implode( '', $links ) // Each link escaped above.
	);
}

/**
 * Print a single contextual link back to this article's category archive.
 *
 * Audit finding cluster-F1: four whole clusters (79 articles — Diet &
 * Nutrition, Living with IBD, Clinical Research & News, Symptoms & Tests) had
 * a 0% spoke-to-hub link rate — not one article in any of them linked back to
 * its own category archive in-body. Rather than hand-edit 79 posts (and drift
 * again the next time one is added), this prints the link from the template
 * for every article, driven by whichever category WordPress already
 * considers primary (get_the_category()'s own ordering — the same one
 * single.php's $type_label uses at the top of the template).
 *
 * @return void
 */
function vance_render_article_category_link() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$categories = get_the_category();
	if ( empty( $categories ) ) {
		return;
	}

	$category = $categories[0];
	$url      = get_category_link( $category );
	if ( is_wp_error( $url ) ) {
		return;
	}

	printf(
		'<p class="va-category-backlink"><a href="%1$s">%2$s</a></p>',
		esc_url( $url ),
		esc_html(
			sprintf(
				/* translators: %s: category name */
				__( 'More from %s', 'vance-health-hub' ),
				$category->name
			)
		)
	);
}

/**
 * Map of condition slug => published post IDs about it, cached in a
 * transient rather than recomputed on every article view.
 *
 * vance_article_conditions() scans each post's title/tags/body with regex; do
 * that for the whole 149-post catalogue on every single pageview and it's 149
 * extra get_post() calls plus regex scans per request. Building the map once
 * and invalidating it on save keeps the per-pageview cost to one lookup.
 *
 * @return array<string,int[]>
 */
function vance_condition_article_map() {
	$map = get_transient( 'vance_condition_article_map' );
	if ( is_array( $map ) ) {
		return $map;
	}

	$map = array();
	$ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ( $ids as $id ) {
		// Confident tier only (title/tag-named) — see
		// vance_article_conditions_confident()'s docblock for why: a sibling
		// suggested here should genuinely be about the shared condition, not
		// merely mention it in passing.
		foreach ( vance_article_conditions_confident( $id ) as $slug ) {
			$map[ $slug ][] = $id;
		}
	}

	set_transient( 'vance_condition_article_map', $map, DAY_IN_SECONDS );

	return $map;
}

/**
 * Clear the cached condition map whenever a post is written, so a newly
 * published or re-tagged article shows up in siblings' related blocks
 * without waiting out the day-long cache.
 *
 * @param int $post_id Post ID.
 */
function vance_clear_condition_article_map( $post_id ) {
	if ( 'post' === get_post_type( $post_id ) ) {
		delete_transient( 'vance_condition_article_map' );
	}
}
add_action( 'save_post', 'vance_clear_condition_article_map' );

/**
 * Print up to 3 other articles that share this one's primary condition.
 *
 * Audit finding cluster-F4: sibling linking between articles on the same
 * condition ran as low as 0.14 links per spoke on Crohn's Disease. Silent
 * when the article has no confidently-identified condition (general pieces)
 * or no siblings yet.
 *
 * @return void
 */
function vance_render_related_articles() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$slugs = vance_article_conditions_confident( get_the_ID() );
	if ( ! $slugs ) {
		return;
	}

	$map        = vance_condition_article_map();
	$candidates = isset( $map[ $slugs[0] ] ) ? $map[ $slugs[0] ] : array();
	$candidates = array_values( array_diff( $candidates, array( get_the_ID() ) ) );

	if ( ! $candidates ) {
		return;
	}

	$related = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'post__in'            => $candidates,
			'posts_per_page'      => 3,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	if ( ! $related->have_posts() ) {
		wp_reset_postdata();
		return;
	}

	$data = vance_gi_condition_medical();
	$name = isset( $data[ $slugs[0] ]['name'] ) ? $data[ $slugs[0] ]['name'] : '';

	echo '<aside class="va-related-articles" aria-label="' . esc_attr__( 'Related articles', 'vance-health-hub' ) . '">';
	echo '<h2 class="va-related-articles__title">' . esc_html( $name ? sprintf( __( 'More on %s', 'vance-health-hub' ), $name ) : __( 'Related articles', 'vance-health-hub' ) ) . '</h2>';
	echo '<ul class="va-related-articles__list">';

	while ( $related->have_posts() ) {
		$related->the_post();
		printf(
			'<li class="va-related-articles__item"><a href="%1$s">%2$s</a></li>',
			esc_url( get_permalink() ),
			esc_html( get_the_title() )
		);
	}

	echo '</ul></aside>';

	wp_reset_postdata();
}
