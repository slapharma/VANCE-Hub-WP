<?php
/**
 * Keep thin and unwritten URLs out of the index.
 *
 * This file only sets the meta tag. Sitemap removal is a separate job, and the
 * filter here does not do it: AIOSEO's sitemap query reads the stored
 * robots_noindex / robots_default columns in wp_aioseo_posts, and knows nothing
 * about a runtime filter. The eleven pages were taken out of the sitemap by
 * writing that stored value through the plugin's own Post model instead. Both
 * halves are needed — the stored value because AIOSEO may not honour it in the
 * served tag, the filter because it is the half that definitely works.
 *
 * There is no equivalent for terms: AIOSEO free ships no Term model, and its
 * sitemap query filters on the post table only. The undescribed recipe term
 * archives are therefore noindexed but still listed. Google fetches them once
 * and drops them, which is untidy rather than harmful.
 *
 * Do NOT reach for aioseo_sitemap_exclude_posts or _exclude_terms to tidy that
 * up. Those were tried on 2026-09-01 and reverted. AIOSEO's excludedObjectIds()
 * checks has_filter() before its early return, so merely registering a callback
 * pushes it into
 *
 *     aioseo()->options->sitemap->{$type}->advancedSettings->{$option}
 *
 * where the same broken options object described above resolves to null. That
 * throws a TypeError out of array_merge(), and the visible result was that
 * vance_recipe-sitemap.xml — the 19 actual recipes, nothing to do with this
 * change — vanished from the sitemap index and started returning 404.
 *
 * AIOSEO already stores some of these settings — Search Appearance → Taxonomies
 * → Tags, and → Archives → Author are both set to noindex in the database — but
 * on 4.9.9 the plugin never acts on them.
 *
 * The reason is in AIOSEO's own Robots::globalValues():
 *
 *     if ( ! isset( $options->advanced->robotsMeta ) ) {
 *         $robotsMeta = aioseo()->options->searchAppearance->advanced->globalRobotsMeta->all();
 *     }
 *
 * The options object's __isset() returns false for a path that demonstrably
 * exists (and, as a side effect, nulls the sub-object it was asked about), so
 * that branch is always taken. Every per-taxonomy and per-archive robots
 * setting therefore falls back to the global default, which is "index".
 * Verified on this install: the stored value reads back as noindex=true while
 * the served page carries only "max-image-preview:large".
 *
 * So this hooks AIOSEO's own output filter instead. Delete the archive half of
 * this file if a future AIOSEO release fixes the isset() branch — the stored
 * settings will then take over on their own, and it becomes a harmless no-op
 * either way.
 *
 * Why these URLs:
 *
 *   - Tag archives duplicate the titles of the seven condition hub pages and
 *     receive more internal links than the pages themselves, so Google is being
 *     pointed at the archive instead of the page written for the query.
 *   - The author archive lists every post under one generic "Team Vance"
 *     account, duplicating the post listing with nothing added.
 *   - Eleven pages were scaffolded during the site build and never written,
 *     and all eleven sat in the XML sitemap. Measured two ways on 2026-09-01
 *     and agreeing: post_content is empty in the database, and the rendered
 *     page carries nought to twelve words beyond the site chrome — the H1 and
 *     nothing else. See vance_placeholder_pages() for why this is a list and
 *     not a rule.
 *   - The recipe taxonomy archives carry no term description, and with 19
 *     recipes spread over 15 terms they largely repeat each other and the
 *     recipe index: gluten-free and dairy-free each return all 19. These are
 *     working archives rather than empty pages — the thinnest still renders
 *     about 40 words of its own — so this is a judgement about overlap, and it
 *     reverses the moment somebody writes a description.
 *
 * Note for anyone extending this list: measure unique content as the rendered
 * word count minus the site chrome, which is 1,089 words on every page. Naive
 * extraction badly misreads this theme. Splitting rendered HTML on the last
 * </header> reports /category/content-healthcare-news/ as a 38-word empty
 * archive, because each of its 32 cards contains a <header>; it actually
 * carries the most unique content of any category on the site.
 *
 * Everything here is self-healing: write the page, or write the term
 * description, and the URL becomes indexable again with no code change.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recipe taxonomies whose archives are gated on having a term description.
 *
 * @return string[]
 */
function vance_recipe_taxonomies() {
	return array( 'vance_recipe_cat', 'vance_recipe_tag' );
}

/**
 * Slugs of pages that exist, are published, and have never been written.
 *
 * This is a hand-maintained list rather than a "page with no content" rule, and
 * deliberately so. Forty of this site's published pages have empty
 * post_content, because most of the site renders from bespoke templates,
 * Customizer values and ACF fields rather than the editor. /terms-of-use/ and
 * /knowledgebase/ both store nothing in post_content and both render over 700
 * words. A content-shape heuristic noindexes them, which is why there isn't
 * one.
 *
 * The blank-content check in vance_page_is_placeholder() is therefore a guard
 * on top of this list, not a substitute for it: it lets a page drop off the
 * list by being written, but it can never add one.
 *
 * Two of these pairs are the same page twice — /contribute/ against
 * /contribute-to-the-hub/, and /podcast-guest/ against
 * /become-a-podcast-guest-on-the-hub/. Whichever of each pair gets written,
 * the other should be retired through vance_retired_redirects() rather than
 * left here.
 *
 * @return string[]
 */
function vance_placeholder_pages() {
	return array(
		'advertise',
		'become-a-podcast-guest-on-the-hub',
		'collaborate-with-us',
		'contribute',
		'contribute-to-the-hub',
		'hcp-learn-more',
		'our-mission',
		'podcast-guest',
		'products',
		'register-as-a-patient',
		'register-as-a-practitioner',
	);
}

/**
 * Is this page a listed placeholder that is still unwritten?
 *
 * @param WP_Post|null $post Page to test.
 * @return bool
 */
function vance_page_is_placeholder( $post ) {
	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
		return false;
	}

	if ( ! in_array( $post->post_name, vance_placeholder_pages(), true ) ) {
		return false;
	}

	// The self-healing half: once there is copy in the editor, this stops
	// matching and the page indexes normally on the next request.
	return '' === trim( (string) $post->post_content );
}

/**
 * Does this term archive have nothing of its own to say?
 *
 * @param WP_Term|null $term Term to test.
 * @return bool
 */
function vance_term_is_undescribed( $term ) {
	if ( ! $term instanceof WP_Term ) {
		return false;
	}

	if ( ! in_array( $term->taxonomy, vance_recipe_taxonomies(), true ) ) {
		return false;
	}

	return '' === trim( wp_strip_all_tags( (string) $term->description ) );
}

/**
 * Add noindex to the AIOSEO robots meta on tag and author archives.
 *
 * @param array $attributes AIOSEO's robots attributes, keyed by directive.
 * @return array
 */
function vance_noindex_thin_archives( $attributes ) {
	if ( ! is_array( $attributes ) ) {
		return $attributes;
	}

	if ( is_tag() || is_author() ) {
		$attributes['noindex'] = 'noindex';
	}

	return $attributes;
}
add_filter( 'aioseo_robots_meta', 'vance_noindex_thin_archives' );

/**
 * Add noindex to unwritten pages and undescribed recipe term archives.
 *
 * @param array $attributes AIOSEO's robots attributes, keyed by directive.
 * @return array
 */
function vance_noindex_unwritten_urls( $attributes ) {
	if ( ! is_array( $attributes ) ) {
		return $attributes;
	}

	if ( is_page() && vance_page_is_placeholder( get_queried_object() ) ) {
		$attributes['noindex'] = 'noindex';
	}

	if ( is_tax( vance_recipe_taxonomies() ) && vance_term_is_undescribed( get_queried_object() ) ) {
		$attributes['noindex'] = 'noindex';
	}

	return $attributes;
}
add_filter( 'aioseo_robots_meta', 'vance_noindex_unwritten_urls' );
