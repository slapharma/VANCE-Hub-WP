<?php
/**
 * Which gastro conditions the site puts first.
 *
 * The client's priority conditions are Crohn's disease, ulcerative colitis and
 * diverticular disease, in that order. Every list or row of conditions that a
 * visitor sees shows those three first and everything else after them, in the
 * order it already had.
 *
 * The order lives HERE. These pass their output through vance_sort_conditions():
 * vance_gi_conditions(), vance_gi_condition_cards(), vance_gi_hero_meta(),
 * vance_gi_hero_related_slugs(), vance_community_spaces(),
 * vance_ai_gi_condition_slugs(), vance_article_conditions() and its confident
 * tier, the Discovery results pills, and the mega-menu tiles
 * (vance_nav_tiles_in_priority()). Their source order is only the tie-break
 * for conditions outside the priority list.
 *
 * One copy exists by hand: the vance_gi_cond_* section order in
 * inc/customizer-structure.php (admin only). tests/condition-priority.test.php
 * fails if it drifts. Lists used only for membership checks
 * (vance_ibd_subtypes(), $redesigned_conditions) are not sorted, because
 * their order is never shown.
 *
 * Sorting never adds, drops or renames anything. Slugs, keys and the data
 * under them are returned unchanged; only the position moves.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The priority conditions, highest first, as condition page slugs.
 *
 * @return string[]
 */
function vance_condition_priority() {
	return array( 'crohns-disease', 'ulcerative-colitis', 'diverticular-disease' );
}

/**
 * The condition slug one entry of a list stands for.
 *
 * Three shapes are in use across the theme, and this reads all of them:
 *   - a row with a 'slug' field (vance_gi_condition_cards());
 *   - a map keyed by slug (vance_gi_conditions(), vance_gi_hero_meta(),
 *     vance_community_spaces());
 *   - a plain list of slug strings (the `related` lists, the AI slug list).
 *
 * @param int|string $key   The entry's array key.
 * @param mixed      $value The entry's value.
 * @return string The slug, or '' when the entry names no condition.
 */
function vance_condition_slug_of( $key, $value ) {
	if ( is_array( $value ) && isset( $value['slug'] ) && is_string( $value['slug'] ) ) {
		return $value['slug'];
	}
	if ( is_string( $key ) ) {
		return $key;
	}
	return is_string( $value ) ? $value : '';
}

/**
 * Put the priority conditions first. Keep every other entry in its order.
 *
 * Stable: entries outside the priority list keep their relative order, and so
 * do two entries for the same priority slug. A map keeps its keys; a list
 * comes back as a list.
 *
 * @param array $items A list or map of conditions, in any shape that
 *                     vance_condition_slug_of() reads.
 * @return array The same entries, reordered.
 */
function vance_sort_conditions( array $items ) {
	$rank  = array_flip( vance_condition_priority() );
	$after = count( $rank );
	$order = array();
	$pos   = 0;

	foreach ( $items as $key => $value ) {
		$slug    = vance_condition_slug_of( $key, $value );
		$order[] = array( isset( $rank[ $slug ] ) ? $rank[ $slug ] : $after, $pos++, $key );
	}

	usort(
		$order,
		static function ( $a, $b ) {
			return ( $a[0] <=> $b[0] ) ?: ( $a[1] <=> $b[1] );
		}
	);

	$is_list = array_keys( $items ) === range( 0, count( $items ) - 1 );
	$out     = array();

	foreach ( $order as $o ) {
		if ( $is_list ) {
			$out[] = $items[ $o[2] ];
		} else {
			$out[ $o[2] ] = $items[ $o[2] ];
		}
	}

	return $out;
}
