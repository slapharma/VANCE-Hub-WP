<?php
/**
 * Tests for inc/admin-menu-content.php: the Recipes, Gastro Discounts and
 * VANCE-Ai KB screens in the VanceHealthHub admin menu.
 *
 * Same shape as hub-admin.test.php: WordPress stubs, a bag of globals the stubs
 * read, check() lines, and exit 1 on any failure. Nothing here loads WordPress.
 *
 * WHAT IS COVERED
 *   1  the menu: eight submenu items in order, parent, capability, slugs, labels,
 *      and the admin_menu hook at priority 1002
 *   2  the target slug for a screen: each post type, each taxonomy, taxonomy wins,
 *      unknown and empty inputs give ''
 *   3  the parent_file filter: our screens set $submenu_file to a registered slug;
 *      foreign screens leave the parent and $submenu_file unchanged
 *   4  source guard: the keys of the map are registered in the three CPT files,
 *      and each post type's register call sets 'show_in_menu' to false
 */

define( 'ABSPATH', true );
date_default_timezone_set( 'UTC' );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

/* ---- state the stubs read; every section resets it first ------------------- */

$GLOBALS['HOOKS'] = array();

function reset_menu_state() {
	$GLOBALS['SUBMENU_CALLS'] = array();
	$GLOBALS['submenu_file']  = 'sentinel-submenu-file';
	$GLOBALS['typenow']       = '';
	$GLOBALS['taxnow']        = '';
}

reset_menu_state();

/* ---- error handling: a notice in the code under test is a failure, not a pass */

set_error_handler( function ( $no, $str, $file, $line ) {
	if ( ! ( error_reporting() & $no ) ) {
		return false;
	}
	throw new ErrorException( $str, 0, $no, $file, $line );
} );

/* ---- WordPress stubs ------------------------------------------------------ */

function __( $text, $domain = '' ) {
	return $text;
}
function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['HOOKS'][ $tag ][ $priority ][] = $callback;
	return true;
}
function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	return add_action( $tag, $callback, $priority, $accepted_args );
}
function add_submenu_page( $parent, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	$GLOBALS['SUBMENU_CALLS'][] = array(
		'parent'     => $parent,
		'page_title' => $page_title,
		'menu_title' => $menu_title,
		'capability' => $capability,
		'menu_slug'  => $menu_slug,
	);
	return 'toplevel_page_' . $menu_slug;
}

/* ---- load the code under test; its add_action and add_filter calls run here ---- */

require_once $THEME . '/inc/admin-menu-content.php';

/* ---- helpers for the source guard (section 4) ----------------------------- */

/** The text of the register_post_type( call: from its opening to the first register_taxonomy( after it. */
function post_type_block( $src ) {
	$start = strpos( $src, 'register_post_type(' );
	if ( false === $start ) {
		return '';
	}
	$end = strpos( $src, 'register_taxonomy(', $start );
	return false === $end ? substr( $src, $start ) : substr( $src, $start, $end - $start );
}

/** True when the block sets 'show_in_menu' to the literal false. */
function sets_show_in_menu_false( $block ) {
	return preg_match( '/\'show_in_menu\'\s*=>\s*false\b/', $block ) === 1;
}

/** True when the source calls $call( with the quoted slug as its first argument. */
function registers( $src, $call, $slug ) {
	return preg_match( '/' . $call . '\(\s*\'' . preg_quote( $slug, '/' ) . '\'/', $src ) === 1;
}

/* ===================================================================== */

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/* ---- 1. the menu items ---------------------------------------------------- */
section( '1. The menu items' );

reset_menu_state();
vance_admin_content_menu();
$CALLS = $GLOBALS['SUBMENU_CALLS'];
$CAPS  = array_column( $CALLS, 'capability' );

check( 'menu: exactly eight submenu pages are added', count( $CALLS ) === 8 );
check( 'menu: every item hangs under vance-content-hub', array_values( array_unique( array_column( $CALLS, 'parent' ) ) ) === array( 'vance-content-hub' ) );
check( 'menu: the slugs run in order: Recipes, its Categories, its Tags, Gastro Discounts, its Categories, its Regions, VANCE-Ai KB, its Categories', array_column( $CALLS, 'menu_slug' ) === array(
	'edit.php?post_type=vance_recipe',
	'edit-tags.php?taxonomy=vance_recipe_cat&amp;post_type=vance_recipe',
	'edit-tags.php?taxonomy=vance_recipe_tag&amp;post_type=vance_recipe',
	'edit.php?post_type=vance_discount',
	'edit-tags.php?taxonomy=vance_discount_cat&amp;post_type=vance_discount',
	'edit-tags.php?taxonomy=vance_discount_region&amp;post_type=vance_discount',
	'edit.php?post_type=vance_kb',
	'edit-tags.php?taxonomy=vance_kb_type&amp;post_type=vance_kb',
) );
check( 'menu: the taxonomy slugs keep the &amp; entity that core writes into $submenu_file', isset( $CALLS[1]['menu_slug'] ) && strpos( $CALLS[1]['menu_slug'], '&amp;post_type=' ) !== false && strpos( $CALLS[1]['menu_slug'], '&post_type=' ) === false );
check( 'menu: the list items use capability edit_posts', isset( $CAPS[0], $CAPS[3], $CAPS[6] ) && $CAPS[0] === 'edit_posts' && $CAPS[3] === 'edit_posts' && $CAPS[6] === 'edit_posts' );
check( 'menu: the taxonomy items use capability manage_categories', isset( $CAPS[1], $CAPS[2], $CAPS[4], $CAPS[5], $CAPS[7] ) && $CAPS[1] === 'manage_categories' && $CAPS[2] === 'manage_categories' && $CAPS[4] === 'manage_categories' && $CAPS[5] === 'manage_categories' && $CAPS[7] === 'manage_categories' );
check( 'menu: the menu titles run in order, taxonomy items indented with the dash', array_column( $CALLS, 'menu_title' ) === array(
	'Recipes',
	'&mdash; Categories',
	'&mdash; Tags',
	'Gastro Discounts',
	'&mdash; Categories',
	'&mdash; Regions',
	'VANCE-Ai KB',
	'&mdash; Categories',
) );
check( 'menu: the page titles name the list they belong to', array_column( $CALLS, 'page_title' ) === array(
	'Recipes',
	'Recipes: Categories',
	'Recipes: Tags',
	'Gastro Discounts',
	'Gastro Discounts: Categories',
	'Gastro Discounts: Regions',
	'VANCE-Ai KB',
	'VANCE-Ai KB: Categories',
) );
check( 'menu: the label is Gastro Discounts, not the retired IBD Discounts', in_array( 'Gastro Discounts', array_column( $CALLS, 'menu_title' ), true ) && strpos( implode( '|', array_merge( array_column( $CALLS, 'menu_title' ), array_column( $CALLS, 'page_title' ) ) ), 'IBD' ) === false );
check( 'hook: vance_admin_content_menu is on admin_menu at priority 1002', isset( $GLOBALS['HOOKS']['admin_menu'][1002] ) && in_array( 'vance_admin_content_menu', $GLOBALS['HOOKS']['admin_menu'][1002], true ) );
check( 'hook: nothing of ours is on admin_menu at 999, 1000 or 1001', ! isset( $GLOBALS['HOOKS']['admin_menu'][999], $GLOBALS['HOOKS']['admin_menu'][1000], $GLOBALS['HOOKS']['admin_menu'][1001] ) );

/* ---- 2. the target slug for a screen -------------------------------------- */
section( '2. The target slug for a screen' );

check( 'target: a recipe list gives the recipe list slug', vance_admin_content_menu_target( 'vance_recipe', '' ) === 'edit.php?post_type=vance_recipe' );
check( 'target: a discount list gives the discount list slug', vance_admin_content_menu_target( 'vance_discount', '' ) === 'edit.php?post_type=vance_discount' );
check( 'target: a KB list gives the KB list slug', vance_admin_content_menu_target( 'vance_kb', '' ) === 'edit.php?post_type=vance_kb' );
check( 'target: the recipe category screen gives its taxonomy slug', vance_admin_content_menu_target( 'vance_recipe', 'vance_recipe_cat' ) === 'edit-tags.php?taxonomy=vance_recipe_cat&amp;post_type=vance_recipe' );
check( 'target: the recipe tag screen gives its taxonomy slug', vance_admin_content_menu_target( 'vance_recipe', 'vance_recipe_tag' ) === 'edit-tags.php?taxonomy=vance_recipe_tag&amp;post_type=vance_recipe' );
check( 'target: the discount category screen gives its taxonomy slug', vance_admin_content_menu_target( 'vance_discount', 'vance_discount_cat' ) === 'edit-tags.php?taxonomy=vance_discount_cat&amp;post_type=vance_discount' );
check( 'target: the discount region screen gives its taxonomy slug', vance_admin_content_menu_target( 'vance_discount', 'vance_discount_region' ) === 'edit-tags.php?taxonomy=vance_discount_region&amp;post_type=vance_discount' );
check( 'target: the KB category screen gives its taxonomy slug', vance_admin_content_menu_target( 'vance_kb', 'vance_kb_type' ) === 'edit-tags.php?taxonomy=vance_kb_type&amp;post_type=vance_kb' );
check( 'target: a taxonomy alone, with no post type, still gives its own taxonomy slug', vance_admin_content_menu_target( '', 'vance_recipe_cat' ) === 'edit-tags.php?taxonomy=vance_recipe_cat&amp;post_type=vance_recipe' );
check( 'target: when both are given the taxonomy wins, with the post type the map gives it', vance_admin_content_menu_target( 'vance_discount', 'vance_recipe_tag' ) === 'edit-tags.php?taxonomy=vance_recipe_tag&amp;post_type=vance_recipe' );
check( 'target: an unknown post type gives empty', vance_admin_content_menu_target( 'vance_poll', '' ) === '' );
check( 'target: the core post type gives empty', vance_admin_content_menu_target( 'post', '' ) === '' );
check( 'target: an unknown taxonomy with a known post type falls back to the list slug', vance_admin_content_menu_target( 'vance_kb', 'post_tag' ) === 'edit.php?post_type=vance_kb' );
check( 'target: an unknown taxonomy with an unknown post type gives empty', vance_admin_content_menu_target( 'vance_poll', 'post_tag' ) === '' );
check( 'target: empty post type and empty taxonomy give empty', vance_admin_content_menu_target( '', '' ) === '' );
check( 'target: null post type and null taxonomy give empty', vance_admin_content_menu_target( null, null ) === '' );
check( 'target: null taxonomy with a known post type gives the list slug', vance_admin_content_menu_target( 'vance_recipe', null ) === 'edit.php?post_type=vance_recipe' );
check( 'target: a null post type with a known taxonomy gives that taxonomy slug', vance_admin_content_menu_target( null, 'vance_kb_type' ) === 'edit-tags.php?taxonomy=vance_kb_type&amp;post_type=vance_kb' );

/* ---- 3. the parent_file filter -------------------------------------------- */
section( '3. The parent_file filter' );

check( 'parent_file: the filter is hooked to parent_file', isset( $GLOBALS['HOOKS']['parent_file'][10] ) && in_array( 'vance_admin_content_menu_parent_file', $GLOBALS['HOOKS']['parent_file'][10], true ) );

// The registered slugs, captured from the same stub the menu uses.
reset_menu_state();
vance_admin_content_menu();
$SLUGS = array_column( $GLOBALS['SUBMENU_CALLS'], 'menu_slug' );

reset_menu_state();
$GLOBALS['typenow'] = 'vance_recipe';
$GLOBALS['taxnow']  = '';
$RET = vance_admin_content_menu_parent_file( 'index.php' );
check( 'parent_file: a recipe list returns vance-content-hub', $RET === 'vance-content-hub' );
check( 'parent_file: a recipe list sets submenu_file to the recipe list slug', $GLOBALS['submenu_file'] === 'edit.php?post_type=vance_recipe' );
check( 'parent_file: the submenu_file a recipe list sets is a registered menu slug', in_array( $GLOBALS['submenu_file'], $SLUGS, true ) );

reset_menu_state();
$GLOBALS['typenow'] = 'vance_discount';
$GLOBALS['taxnow']  = 'vance_discount_region';
$RET = vance_admin_content_menu_parent_file( 'index.php' );
check( 'parent_file: a discount region screen returns vance-content-hub', $RET === 'vance-content-hub' );
check( 'parent_file: a discount region screen sets submenu_file to its taxonomy slug', $GLOBALS['submenu_file'] === 'edit-tags.php?taxonomy=vance_discount_region&amp;post_type=vance_discount' );
check( 'parent_file: the submenu_file a discount region screen sets is a registered menu slug', in_array( $GLOBALS['submenu_file'], $SLUGS, true ) );

reset_menu_state();
$GLOBALS['typenow'] = 'vance_kb';
$GLOBALS['taxnow']  = 'vance_kb_type';
$RET = vance_admin_content_menu_parent_file( 'index.php' );
check( 'parent_file: a KB category screen returns vance-content-hub', $RET === 'vance-content-hub' );
check( 'parent_file: the submenu_file a KB category screen sets is a registered menu slug', in_array( $GLOBALS['submenu_file'], $SLUGS, true ) && $GLOBALS['submenu_file'] === 'edit-tags.php?taxonomy=vance_kb_type&amp;post_type=vance_kb' );

reset_menu_state();
$GLOBALS['typenow'] = 'vance_kb';
$GLOBALS['taxnow']  = '';
$RET = vance_admin_content_menu_parent_file( 'index.php' );
check( 'parent_file: a KB edit screen returns vance-content-hub and highlights the KB list', $RET === 'vance-content-hub' && $GLOBALS['submenu_file'] === 'edit.php?post_type=vance_kb' );

reset_menu_state();
$GLOBALS['typenow'] = 'post';
$GLOBALS['taxnow']  = '';
$RET = vance_admin_content_menu_parent_file( 'index.php' );
check( 'parent_file: a post screen returns the parent unchanged', $RET === 'index.php' );
check( 'parent_file: a post screen leaves submenu_file unchanged', $GLOBALS['submenu_file'] === 'sentinel-submenu-file' );

reset_menu_state();
$GLOBALS['typenow'] = 'page';
$GLOBALS['taxnow']  = '';
$RET = vance_admin_content_menu_parent_file( 'index.php' );
check( 'parent_file: a page screen returns the parent unchanged', $RET === 'index.php' );
check( 'parent_file: a page screen leaves submenu_file unchanged', $GLOBALS['submenu_file'] === 'sentinel-submenu-file' );

reset_menu_state();
$GLOBALS['typenow'] = 'vance_poll';
$GLOBALS['taxnow']  = '';
$RET = vance_admin_content_menu_parent_file( 'index.php' );
check( 'parent_file: a vance_poll screen returns the parent unchanged', $RET === 'index.php' );
check( 'parent_file: a vance_poll screen leaves submenu_file unchanged', $GLOBALS['submenu_file'] === 'sentinel-submenu-file' );

reset_menu_state();
$GLOBALS['typenow'] = '';
$GLOBALS['taxnow']  = '';
$RET = vance_admin_content_menu_parent_file( 'index.php' );
check( 'parent_file: an empty screen returns the parent unchanged', $RET === 'index.php' );
check( 'parent_file: an empty screen leaves submenu_file unchanged', $GLOBALS['submenu_file'] === 'sentinel-submenu-file' );

reset_menu_state();
$GLOBALS['typenow'] = 'post';
$GLOBALS['taxnow']  = 'category';
$RET = vance_admin_content_menu_parent_file( 'index.php' );
check( 'parent_file: a core category screen returns the parent unchanged', $RET === 'index.php' );
check( 'parent_file: a core category screen leaves submenu_file unchanged', $GLOBALS['submenu_file'] === 'sentinel-submenu-file' );

// Core renames the top-level menu to its first visible submenu item and records
// that in $_wp_real_parent_file. The filter must return the recorded slug.
reset_menu_state();
$GLOBALS['typenow']              = 'vance_recipe';
$GLOBALS['taxnow']               = '';
$GLOBALS['_wp_real_parent_file'] = array( 'vance-content-hub' => 'edit.php?post_type=vhh_todo' );
$RET = vance_admin_content_menu_parent_file( 'index.php' );
check( 'parent_file: a re-parented menu returns the real parent slug', $RET === 'edit.php?post_type=vhh_todo' );
check( 'parent_file: a re-parented menu still highlights the recipe list', $GLOBALS['submenu_file'] === 'edit.php?post_type=vance_recipe' );

$GLOBALS['typenow'] = 'post';
$RET = vance_admin_content_menu_parent_file( 'index.php' );
check( 'parent_file: a re-parented menu does not change a foreign screen', $RET === 'index.php' );

$GLOBALS['typenow']              = 'vance_recipe';
$GLOBALS['_wp_real_parent_file'] = array( 'some-other-menu' => 'x.php' );
check( 'parent_file: a record for a different menu is not used', vance_admin_content_menu_parent_file( 'index.php' ) === 'vance-content-hub' );
$GLOBALS['_wp_real_parent_file'] = null;
check( 'parent_file: no record gives vance-content-hub', vance_admin_content_menu_parent_file( 'index.php' ) === 'vance-content-hub' );
unset( $GLOBALS['_wp_real_parent_file'] );

/* ---- 4. source guard: the map against the three CPT files ----------------- */
section( '4. Source guard: the map against the CPT files' );

$MAP = vance_admin_content_menu_map();
check( 'map: the post types are recipe, discount and KB, in that order', array_keys( $MAP ) === array( 'vance_recipe', 'vance_discount', 'vance_kb' ) );
check( 'map: the recipe taxonomies are category and tag', array_keys( $MAP['vance_recipe']['taxonomies'] ) === array( 'vance_recipe_cat', 'vance_recipe_tag' ) );
check( 'map: the discount taxonomies are category and region', array_keys( $MAP['vance_discount']['taxonomies'] ) === array( 'vance_discount_cat', 'vance_discount_region' ) );
check( 'map: the KB taxonomy is the KB type', array_keys( $MAP['vance_kb']['taxonomies'] ) === array( 'vance_kb_type' ) );

$RECIPE_SRC   = file_get_contents( $THEME . '/inc/recipe-cpt.php' );
$DISCOUNT_SRC = file_get_contents( $THEME . '/inc/discount-cpt.php' );
$KB_SRC       = file_get_contents( $THEME . '/inc/askai-kb.php' );

check( 'source: recipe-cpt.php registers the vance_recipe post type', registers( $RECIPE_SRC, 'register_post_type', 'vance_recipe' ) );
check( 'source: recipe-cpt.php registers the vance_recipe_cat taxonomy', registers( $RECIPE_SRC, 'register_taxonomy', 'vance_recipe_cat' ) );
check( 'source: recipe-cpt.php registers the vance_recipe_tag taxonomy', registers( $RECIPE_SRC, 'register_taxonomy', 'vance_recipe_tag' ) );
check( 'source: discount-cpt.php registers the vance_discount post type', registers( $DISCOUNT_SRC, 'register_post_type', 'vance_discount' ) );
check( 'source: discount-cpt.php registers the vance_discount_cat taxonomy', registers( $DISCOUNT_SRC, 'register_taxonomy', 'vance_discount_cat' ) );
check( 'source: discount-cpt.php registers the vance_discount_region taxonomy', registers( $DISCOUNT_SRC, 'register_taxonomy', 'vance_discount_region' ) );
check( 'source: askai-kb.php defines the vance_kb post type constant', preg_match( '/const\s+VANCE_KB_POST_TYPE\s*=\s*\'vance_kb\'\s*;/', $KB_SRC ) === 1 );
check( 'source: askai-kb.php registers the post type through that constant', preg_match( '/register_post_type\(\s*VANCE_KB_POST_TYPE\b/', $KB_SRC ) === 1 );
check( 'source: askai-kb.php defines the vance_kb_type taxonomy constant', preg_match( '/const\s+VANCE_KB_TAXONOMY\s*=\s*\'vance_kb_type\'\s*;/', $KB_SRC ) === 1 );
check( 'source: askai-kb.php registers the taxonomy through that constant', preg_match( '/register_taxonomy\(\s*VANCE_KB_TAXONOMY\b/', $KB_SRC ) === 1 );

check( 'show_in_menu: the vance_recipe registration sets it to false', sets_show_in_menu_false( post_type_block( $RECIPE_SRC ) ) );
check( 'show_in_menu: the vance_discount registration sets it to false', sets_show_in_menu_false( post_type_block( $DISCOUNT_SRC ) ) );
check( 'show_in_menu: the vance_kb registration sets it to false', sets_show_in_menu_false( post_type_block( $KB_SRC ) ) );

// Negative controls: the guard must be able to fail.
check( 'control: a registration with show_in_menu true is not reported as false', ! sets_show_in_menu_false( "register_post_type( 'x', array( 'show_in_menu' => true ) );" ) );
check( 'control: a registration with no show_in_menu is not reported as false', ! sets_show_in_menu_false( "register_post_type( 'x', array( 'show_ui' => true ) );" ) );
check( 'control: a show_in_menu false that sits in the taxonomy call is not the post type block', ! sets_show_in_menu_false( post_type_block( "register_post_type( 'x', array( 'show_ui' => true ) );\nregister_taxonomy( 'y', 'x', array( 'show_in_menu' => false ) );" ) ) );
check( 'control: a slug that is not registered is not found', ! registers( $RECIPE_SRC, 'register_taxonomy', 'vance_recipe_nope' ) );
check( 'control: a post type slug is not found as a taxonomy registration', ! registers( $RECIPE_SRC, 'register_taxonomy', 'vance_recipe' ) );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
