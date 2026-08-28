<?php
/**
 * Audit the live primary menu. READ-ONLY — reports, never writes.
 *
 *   wp eval-file tools/audit-mega-menu.php
 *
 * WHAT CHANGED, AND WHY THIS NO LONGER COMPARES AGAINST A SPEC
 *
 * The first version of this file checked the menu against a hard-coded list of
 * columns and child counts copied from build-mega-menu.php, and told you to run
 * that script to fix any difference.
 *
 * Both halves of that became wrong once the menu started being edited by hand
 * (2026-08-28). A fixed expected-structure reports honest edits as faults, and
 * "fix it by running the rebuild" is advice that would hard-delete those edits:
 * build-mega-menu.php is a wipe-and-recreate, not a merge.
 *
 * So this now checks only things that are wrong no matter how the menu is
 * arranged — dead links, mis-typed panels, sort values the plugin ignores,
 * broken placeholders, stray widgets — and prints the live structure so drift
 * is visible without being judged.
 *
 * @package vance-health-hub
 * @since   2026-08-28
 */

if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$LOCATION = 'primary-menu';

$locations = get_nav_menu_locations();
$menu_id   = isset( $locations[ $LOCATION ] ) ? (int) $locations[ $LOCATION ] : 0;

if ( ! $menu_id ) {
	echo "FAIL: no menu is assigned to the '{$LOCATION}' location.\n";
	return;
}

$menu     = wp_get_nav_menu_object( $menu_id );
$items    = (array) wp_get_nav_menu_items( $menu_id );
$children = array();
foreach ( $items as $i ) { $children[ (int) $i->menu_item_parent ][] = $i; }

$problems = array();
$notes    = array();

/* ------------------------------------------------------ the live structure */

echo "LIVE: {$menu->name} (#{$menu_id}) — " . count( $items ) . " items\n\n";

foreach ( ( $children[0] ?? array() ) as $top ) {
	$mm = get_post_meta( $top->ID, '_megamenu', true );
	printf( "%s  [%s]\n", $top->title, is_array( $mm ) ? ( $mm['type'] ?? 'no settings' ) : 'no settings' );

	foreach ( ( $children[ $top->ID ] ?? array() ) as $col ) {
		$cm  = get_post_meta( $col->ID, '_megamenu', true );
		$ord = ( is_array( $cm ) && is_array( $cm['mega_menu_order'] ?? null ) ) ? implode( '', $cm['mega_menu_order'] ) : '-';
		printf( "   [%s] %-26s span %-3s %d links\n", $ord, $col->title, is_array( $cm ) ? ( $cm['mega_menu_columns'] ?? '?' ) : '?', count( $children[ $col->ID ] ?? array() ) );
	}
}

/* ------------------------------------------------------------ objective checks */

// 1. Every top-level item should be a mega panel; a flyout here is almost
//    always an item added in the admin without its display mode being set.
foreach ( ( $children[0] ?? array() ) as $top ) {
	$mm = get_post_meta( $top->ID, '_megamenu', true );
	$type = is_array( $mm ) ? ( $mm['type'] ?? '' ) : '';
	if ( 'megamenu' !== $type ) {
		$problems[] = "NOT A MEGA PANEL: '{$top->title}' is '" . ( $type ?: 'unset' ) . "' — set Sub Menu Display Mode to Mega Menu";
	}
}

// 2. megamenu.php:1100 skips its sort rewrite when the order is 0, so a column
//    with order 0 silently drops to the end of its panel.
foreach ( $items as $i ) {
	$mm = get_post_meta( $i->ID, '_megamenu', true );
	if ( is_array( $mm ) && is_array( $mm['mega_menu_order'] ?? null ) ) {
		foreach ( $mm['mega_menu_order'] as $ord ) {
			if ( 0 === (int) $ord ) { $problems[] = "ORDER 0 (sorts to the end): '{$i->title}'"; }
		}
	}
}

// 3. Nothing may point at a page or term that has gone. Placeholders are
//    exempt by definition — they are meant to have no destination.
foreach ( $items as $i ) {
	$mm = get_post_meta( $i->ID, '_megamenu', true );
	if ( is_array( $mm ) && 'true' === ( $mm['disable_link'] ?? '' ) ) { continue; }

	if ( 'post_type' === $i->type ) {
		$p = get_post( $i->object_id );
		if ( ! $p || 'publish' !== $p->post_status ) {
			$problems[] = "DEAD LINK: '{$i->title}' -> post {$i->object_id} (" . ( $p ? $p->post_status : 'deleted' ) . ')';
		}
	} elseif ( 'taxonomy' === $i->type ) {
		if ( ! get_term( $i->object_id ) ) { $problems[] = "DEAD LINK: '{$i->title}' -> term {$i->object_id} (deleted)"; }
	} elseif ( 'custom' === $i->type && ( '' === $i->url || '#' === $i->url ) ) {
		$problems[] = "EMPTY LINK: '{$i->title}' has no URL and is not marked as a placeholder";
	}
}

// 4. A placeholder must actually be inert: disable_link set AND no real URL.
foreach ( $items as $i ) {
	$mm = get_post_meta( $i->ID, '_megamenu', true );
	if ( ! is_array( $mm ) || 'true' !== ( $mm['disable_link'] ?? '' ) ) { continue; }
	if ( $i->url && '#' !== $i->url ) {
		$notes[] = "placeholder '{$i->title}' still carries a URL ({$i->url}) — harmless, the plugin drops the href, but tidy it if the row is meant to stay inert";
	}
}

// 5. Panel widgets must be bound to a menu item that still exists.
foreach ( array( 'vhh_nav_tiles', 'vhh_nav_cta', 'vhh_nav_featured' ) as $base ) {
	foreach ( (array) get_option( 'widget_' . $base ) as $k => $v ) {
		if ( ! is_array( $v ) || ! isset( $v['mega_menu_parent_menu_id'] ) ) { continue; }
		$parent = (int) $v['mega_menu_parent_menu_id'];
		if ( ! get_post( $parent ) ) {
			$problems[] = "ORPHAN WIDGET: {$base}-{$k} is bound to menu item {$parent}, which no longer exists";
		}
	}
}

/* ---------------------------------------------------------------- verdict */

echo "\n";
if ( $problems ) {
	echo 'PROBLEMS (' . count( $problems ) . "):\n";
	foreach ( $problems as $p ) { echo "  {$p}\n"; }
	echo "\nFix these in Appearance → Menus, or in the Mega Menu settings for the\n";
	echo "item concerned. Do NOT run build-mega-menu.php to 'repair' a live menu:\n";
	echo "it wipes and rebuilds from a 2026-08-28 snapshot and would discard any\n";
	echo "hand edits made since.\n";
} else {
	echo "OK — no dead links, no mis-typed panels, no order-0 columns, no orphan widgets.\n";
}

if ( $notes ) {
	echo "\nNotes (" . count( $notes ) . "):\n";
	foreach ( $notes as $n ) { echo "  {$n}\n"; }
}
