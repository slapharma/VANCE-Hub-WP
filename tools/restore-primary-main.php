<?php
/**
 * Recreate the "Primary - main" menu — the flyout menu that was live until
 * 2026-08-28, when the mega menu replaced it.
 *
 *   wp eval-file tools/restore-primary-main.php
 *
 * The menu itself was deleted on request. This script exists so that deletion
 * stays reversible: it rebuilds the menu exactly as it was, from a capture
 * taken immediately before the delete, and then tells you the one command that
 * puts it back on the primary location.
 *
 * It does NOT assign a location. Running it changes nothing user-visible until
 * you deliberately switch over — same safety property the mega-menu build has.
 *
 * Pages and categories are re-linked by SLUG rather than by the original post
 * ID, because an ID is only meaningful in the database it came from and these
 * are the IDs from the live site as it stood that day. Anything that has since
 * been deleted is reported and skipped rather than silently linking nowhere.
 *
 * @package vance-health-hub
 * @since   2026-08-28
 */

if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$MENU_NAME = 'Primary - main (restored)';

/**
 * The captured structure. `path` is a page slug, `cat` a category slug, and a
 * bare `url` a custom link. Children follow their parent.
 */
$TREE = array(
	array( 'title' => 'HOME',          'url'  => home_url( '/' ) ),
	array( 'title' => 'ABOUT US',      'url'  => home_url( '/' ), 'children' => array(
		array( 'title' => 'Who We Are',              'path' => 'about-us' ),
		array( 'title' => 'Contact Us',              'path' => 'contact-us' ),
	) ),
	array( 'title' => 'THE HUB',       'url'  => home_url( '/' ), 'children' => array(
		array( 'title' => 'Get Started',             'path' => 'get-started-today' ),
		array( 'title' => 'User Guide',              'path' => 'user-guide' ),
		array( 'title' => 'Free Health Tools',       'path' => 'free-health-tools' ),
	) ),
	// Custom link, not a page item — that is what the original was, and a
	// first pass of this script got it wrong. Caught by diffing the restored
	// menu against the real one before the real one was deleted.
	array( 'title' => 'KNOWLEDGEBASE', 'url' => home_url( '/knowledgebase/' ), 'children' => array(
		array( 'title' => 'Gastro Health Explained', 'path' => 'gastro-health-explained' ),
		array( 'title' => 'Gastro Living Insights',  'cat'  => 'content-gastro-living' ),
		array( 'title' => 'Clinical Data Reviews',   'cat'  => 'content-clinical-reviews' ),
		array( 'title' => 'Gastro Health News',      'cat'  => 'content-healthcare-news' ),
		array( 'title' => 'Webinars and Courses',    'path' => 'webinars-and-courses' ),
	) ),
);

/* -------------------------------------------------------------------------- */

$pos      = 0;
$problems = array();

/** Create one item; returns its ID, or 0 when the destination has gone. */
function vhr_add( $menu_id, $spec, $parent, &$pos, &$problems ) {
	$args = array(
		'menu-item-title'     => $spec['title'],
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => (int) $parent,
		'menu-item-position'  => ++$pos,
	);

	if ( ! empty( $spec['path'] ) ) {
		$p = get_page_by_path( $spec['path'] );
		if ( ! $p ) { $problems[] = "page gone: {$spec['path']} ({$spec['title']})"; return 0; }
		$args['menu-item-type'] = 'post_type';
		$args['menu-item-object'] = 'page';
		$args['menu-item-object-id'] = $p->ID;
	} elseif ( ! empty( $spec['cat'] ) ) {
		$t = get_category_by_slug( $spec['cat'] );
		if ( ! $t ) { $problems[] = "category gone: {$spec['cat']} ({$spec['title']})"; return 0; }
		$args['menu-item-type'] = 'taxonomy';
		$args['menu-item-object'] = 'category';
		$args['menu-item-object-id'] = $t->term_id;
	} else {
		$args['menu-item-type'] = 'custom';
		$args['menu-item-url']  = $spec['url'];
	}

	$id = wp_update_nav_menu_item( $menu_id, 0, $args );
	if ( is_wp_error( $id ) ) { $problems[] = $id->get_error_message(); return 0; }
	return (int) $id;
}

$existing = wp_get_nav_menu_object( $MENU_NAME );
if ( $existing ) {
	foreach ( (array) wp_get_nav_menu_items( $existing->term_id ) as $it ) { wp_delete_post( $it->ID, true ); }
	$menu_id = (int) $existing->term_id;
	echo "Reused '{$MENU_NAME}' (#{$menu_id}), cleared its items.\n";
} else {
	$new = wp_create_nav_menu( $MENU_NAME );
	if ( is_wp_error( $new ) ) { echo 'FATAL: ' . $new->get_error_message() . "\n"; return; }
	$menu_id = (int) $new;
	echo "Created '{$MENU_NAME}' (#{$menu_id}).\n";
}

$made = 0;
foreach ( $TREE as $top ) {
	$tid = vhr_add( $menu_id, $top, 0, $pos, $problems );
	if ( ! $tid ) { continue; }
	$made++;
	foreach ( ( $top['children'] ?? array() ) as $child ) {
		if ( vhr_add( $menu_id, $child, $tid, $pos, $problems ) ) { $made++; }
	}
}

echo "\n--- VERIFY ---\n";
$actual = count( (array) wp_get_nav_menu_items( $menu_id ) );
echo "created {$made} items; menu now holds {$actual} (the original had 14)\n";
if ( $problems ) {
	echo "PROBLEMS:\n";
	foreach ( $problems as $p ) { echo "  {$p}\n"; }
} else {
	echo "every destination still exists\n";
}

echo "\nNot assigned to a location — the site is unchanged.\n";
echo "To put it back on the primary location:\n";
echo "  wp menu location assign {$menu_id} primary-menu\n";
