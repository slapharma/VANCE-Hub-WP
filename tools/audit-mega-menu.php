<?php
/**
 * Audit the live primary menu against the structure it is supposed to have.
 *
 *   wp eval-file tools/audit-mega-menu.php
 *
 * Read-only: reports, never repairs. Run tools/build-mega-menu.php to fix
 * anything this finds — it is idempotent and rebuilds from the same spec.
 *
 * WHY THIS EXISTS
 *
 * build-mega-menu.php verifies its own work, but only at the moment it runs.
 * Twice now, menu items have been hard-deleted AFTER a clean build:
 *
 *   2026-08-28  "Webinars & Courses" vanished from Browse the library.
 *   2026-08-28  "How to Use the Hub", "My Tools" and "View all gastro
 *               conditions" vanished while unrelated menus were being deleted.
 *
 * Both times the build's own check had passed, because the loss happened
 * afterwards — a self-check that runs once at build time cannot catch it. The
 * items were hard-deleted, not trashed and not merely detached from the term,
 * which points at WordPress's own menu-item cleanup cascading further than
 * expected during wp_delete_nav_menu(). Root cause is not yet pinned down.
 *
 * So: run this after ANY menu surgery, and after deleting any menu at all.
 *
 * @package vance-health-hub
 * @since   2026-08-28
 */

if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$LOCATION = 'primary-menu';

/** Expected columns and their child counts. Keep in sync with build-mega-menu.php. */
$EXPECTED = array(
	'THE HUB' => array(
		'Start here'         => 3,
		'Free Health Tools'  => 3,
		'Patient Downloads'  => 3,
		'Your Account'       => 6,
		'Vance Medical'      => 2,
	),
	'KNOWLEDGEBASE' => array(
		'Browse the library' => 5,
		'By content type'    => 3,
	),
	'CONDITIONS' => array(),
);
$EXPECTED_TOTAL = 35;

/* -------------------------------------------------------------------------- */

$locations = get_nav_menu_locations();
$menu_id   = isset( $locations[ $LOCATION ] ) ? (int) $locations[ $LOCATION ] : 0;

if ( ! $menu_id ) {
	echo "FAIL: no menu assigned to the '{$LOCATION}' location\n";
	return;
}

$menu  = wp_get_nav_menu_object( $menu_id );
$items = (array) wp_get_nav_menu_items( $menu_id );
echo "menu: {$menu->name} (#{$menu_id})   items: " . count( $items ) . " (expected {$EXPECTED_TOTAL})\n\n";

$children = array();
foreach ( $items as $i ) { $children[ (int) $i->menu_item_parent ][] = $i; }

$problems = array();

foreach ( $EXPECTED as $top_title => $cols ) {
	$top = null;
	foreach ( ( $children[0] ?? array() ) as $t ) { if ( $t->title === $top_title ) { $top = $t; } }
	if ( ! $top ) { $problems[] = "MISSING panel: {$top_title}"; continue; }

	$found = array();
	foreach ( ( $children[ $top->ID ] ?? array() ) as $col ) { $found[ $col->title ] = count( $children[ $col->ID ] ?? array() ); }

	foreach ( $cols as $col_title => $want ) {
		if ( ! isset( $found[ $col_title ] ) ) { $problems[] = "MISSING column: {$top_title} > {$col_title}"; continue; }
		if ( $found[ $col_title ] !== $want ) {
			$problems[] = "LOST ITEMS: {$top_title} > {$col_title} has {$found[$col_title]}, expected {$want}";
		}
	}
	foreach ( $found as $col_title => $n ) {
		if ( ! isset( $cols[ $col_title ] ) ) { $problems[] = "UNEXPECTED column: {$top_title} > {$col_title}"; }
	}
}

// Every panel must be a mega menu, and no column may sort with order 0
// (megamenu.php:1100 treats 0 as unset and drops the column to the end).
foreach ( ( $children[0] ?? array() ) as $top ) {
	$mm = get_post_meta( $top->ID, '_megamenu', true );
	if ( ! is_array( $mm ) || ( $mm['type'] ?? '' ) !== 'megamenu' ) {
		$problems[] = "NOT A MEGA PANEL: {$top->title} (type=" . ( $mm['type'] ?? 'unset' ) . ')';
	}
}
foreach ( $items as $i ) {
	$mm = get_post_meta( $i->ID, '_megamenu', true );
	if ( is_array( $mm ) && is_array( $mm['mega_menu_order'] ?? null ) ) {
		foreach ( $mm['mega_menu_order'] as $ord ) {
			if ( 0 === (int) $ord ) { $problems[] = "ORDER 0 (sorts last): {$i->title}"; }
		}
	}
}

// Every non-placeholder item must resolve to somewhere real.
foreach ( $items as $i ) {
	$mm = get_post_meta( $i->ID, '_megamenu', true );
	if ( is_array( $mm ) && ( $mm['disable_link'] ?? '' ) === 'true' ) { continue; }
	if ( 'post_type' === $i->type ) {
		$p = get_post( $i->object_id );
		if ( ! $p || 'publish' !== $p->post_status ) { $problems[] = "DEAD LINK: {$i->title} -> post {$i->object_id}"; }
	} elseif ( 'taxonomy' === $i->type ) {
		if ( ! get_term( $i->object_id ) ) { $problems[] = "DEAD LINK: {$i->title} -> term {$i->object_id}"; }
	}
}

// The widgets that fill the panel cells.
$widgets = array( 'vhh_nav_tiles' => 1, 'vhh_nav_cta' => 1, 'vhh_nav_featured' => 1 );
foreach ( $widgets as $base => $want ) {
	$opt = get_option( 'widget_' . $base );
	$n   = 0;
	foreach ( (array) $opt as $k => $v ) { if ( is_array( $v ) && isset( $v['mega_menu_parent_menu_id'] ) ) { $n++; } }
	if ( $n !== $want ) { $problems[] = "WIDGETS: {$base} has {$n} instance(s), expected {$want}"; }
}

if ( count( $items ) !== $EXPECTED_TOTAL ) {
	$problems[] = 'TOTAL: ' . count( $items ) . " items, expected {$EXPECTED_TOTAL}";
}

echo $problems
	? "PROBLEMS (" . count( $problems ) . "):\n  " . implode( "\n  ", $problems ) . "\n\nFix: wp eval-file tools/build-mega-menu.php\n"
	: "OK — structure, ordering, widgets and every destination check out.\n";
