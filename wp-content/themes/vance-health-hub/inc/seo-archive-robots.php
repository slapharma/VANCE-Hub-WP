<?php
/**
 * Force noindex on tag and author archives.
 *
 * AIOSEO already stores these settings — Search Appearance → Taxonomies → Tags,
 * and → Archives → Author are both set to noindex in the database — but on
 * 4.9.9 the plugin never acts on them.
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
 * So this hooks AIOSEO's own output filter instead. Delete this file if a
 * future AIOSEO release fixes the isset() branch — the stored settings will
 * then take over on their own, and this becomes a harmless no-op either way.
 *
 * Why these two archives:
 *   - Tag archives duplicate the titles of the seven condition hub pages and
 *     receive more internal links than the pages themselves, so Google is being
 *     pointed at the archive instead of the page written for the query.
 *   - The author archive lists every post under one generic "Team Vance"
 *     account, duplicating the post listing with nothing added.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
