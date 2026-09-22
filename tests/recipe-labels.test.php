<?php
/**
 * inc/recipe-frontend.php — the recipe label model: vance_recipe_label_model(),
 * vance_recipe_labels_for(), and the tags/chips shape vance_recipe_planner_data()
 * builds from them. Also checks tools/recipe-labels.json (the spreadsheet export)
 * and tools/apply-recipe-labels.php (the WP-CLI applier) stay in step with the
 * model, since both are hand-maintained copies of the same 14 slugs.
 *
 * Same shape as the other suites in this directory: hand-written WP stubs, no
 * WordPress, no database. tools/apply-recipe-labels.php is never require()'d —
 * it exits immediately without WP_CLI (see its own header comment) and its
 * $vance_labels list is instead recovered by parsing the file as text.
 */

define( 'ABSPATH', true );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';
$ROOT  = dirname( __DIR__ );

/* ---- the bags the stubs read ------------------------------------------ */
$GLOBALS['TERMS_FOR_POST'] = array(); // post_id => array of WP_Term|'WP_ERROR'|false
$GLOBALS['CATALOGUE']      = array();
$GLOBALS['RECIPE_DATA']    = array();
$GLOBALS['CAN_MANAGE']     = false;
$GLOBALS['POST_DATES']     = array();
$GLOBALS['POST_TIMES']     = array();

function set_terms_for_post( $post_id, $value ) { $GLOBALS['TERMS_FOR_POST'][ $post_id ] = $value; }
function set_catalogue( array $c ) { $GLOBALS['CATALOGUE'] = $c; }
function set_recipe_data( array $d ) { $GLOBALS['RECIPE_DATA'] = $d; }
function set_can_manage( $b ) { $GLOBALS['CAN_MANAGE'] = $b; }

/* ---- WordPress stubs --------------------------------------------------- */

class WP_Term {
	public $term_id, $name, $slug;
	public function __construct( $id, $name, $slug ) {
		$this->term_id = $id;
		$this->name    = $name;
		$this->slug    = $slug;
	}
}

class WP_Error {
	public $message;
	public function __construct( $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}

function is_wp_error( $thing ) { return $thing instanceof WP_Error; }

function get_the_terms( $post_id, $taxonomy ) {
	return array_key_exists( $post_id, $GLOBALS['TERMS_FOR_POST'] )
		? $GLOBALS['TERMS_FOR_POST'][ $post_id ]
		: array();
}

function wp_list_pluck( $list, $field ) {
	$out = array();
	foreach ( (array) $list as $item ) {
		$out[] = is_object( $item ) ? $item->$field : $item[ $field ];
	}
	return $out;
}

function __( $t, $d = '' ) { return $t; }
function add_action( $tag, $cb, $prio = 10, $args = 1 ) {}

/* ---- stubs for vance_recipe_planner_data()'s other dependencies ------- */

function vance_recipe_catalogue() { return $GLOBALS['CATALOGUE']; }
function vance_recipe_data() { return $GLOBALS['RECIPE_DATA']; }
function vance_recipe_image_url( $slug ) { return "https://example.test/img/$slug.jpg"; }
function vance_recipe_url( $slug ) { return "https://example.test/recipes/$slug/"; }
function current_user_can( $cap ) { return $GLOBALS['CAN_MANAGE']; }
function get_the_date( $fmt, $post_id ) { return isset( $GLOBALS['POST_DATES'][ $post_id ] ) ? $GLOBALS['POST_DATES'][ $post_id ] : ''; }
function get_post_time( $fmt, $gmt = false, $post_id = 0 ) { return isset( $GLOBALS['POST_TIMES'][ $post_id ] ) ? $GLOBALS['POST_TIMES'][ $post_id ] : 0; }

require_once $THEME . '/inc/recipe-frontend.php';

/* ---- the runner --------------------------------------------------------- */
$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }


/* ===================================================================== */
section( '1. vance_recipe_label_model()' );
/* ===================================================================== */

$model = vance_recipe_label_model();

check( '1a  exactly two layers: core and descriptors',
	array_keys( $model ) === array( 'core', 'descriptors' ) );

check( '1b  exactly 8 core slugs, in order',
	array_keys( $model['core'] ) === array(
		'gluten-free', 'dairy-free', 'vegetarian', 'vegan-friendly',
		'low-fibre', 'high-fibre', 'high-protein', 'low-fodmap',
	) );

check( '1c  exactly 6 descriptor slugs, in order',
	array_keys( $model['descriptors'] ) === array(
		'no-onion', 'no-garlic', 'easy-to-digest', 'nutrient-dense',
		'refined-sugar-free', 'omega-3-rich',
	) );

check( '1d  the two layers never share a slug',
	array_intersect_key( $model['core'], $model['descriptors'] ) === array() );

check( '1e  every label is a non-empty display string, not just the slug echoed back',
	( function () use ( $model ) {
		foreach ( array_merge( $model['core'], $model['descriptors'] ) as $slug => $label ) {
			if ( '' === $label || $label === $slug ) { return false; }
		}
		return true;
	} )() );

check( '1f  a couple of exact display labels',
	$model['core']['low-fodmap'] === 'Low FODMAP'
	&& $model['descriptors']['omega-3-rich'] === 'Omega-3 Rich' );


/* ===================================================================== */
section( '2. vance_recipe_labels_for()' );
/* ===================================================================== */

// 2a — terms in a different order than the model still come back in MODEL order.
set_terms_for_post( 101, array(
	new WP_Term( 1, 'High Protein', 'high-protein' ),
	new WP_Term( 2, 'Gluten Free', 'gluten-free' ),
	new WP_Term( 3, 'Vegan-Friendly', 'vegan-friendly' ),
) );
$out = vance_recipe_labels_for( 101 );
check( '2a  core labels come back in MODEL order, not term order',
	array_keys( $out['core'] ) === array( 'gluten-free', 'vegan-friendly', 'high-protein' ) );
check( '2a  descriptors empty when none assigned',
	$out['descriptors'] === array() );

// 2b — a term outside the model (retired / condition vocabulary) is dropped.
set_terms_for_post( 102, array(
	new WP_Term( 4, 'IBD', 'ibd' ),
	new WP_Term( 5, 'Garlic Free', 'garlic-free' ), // retired slug, NOT the current 'no-garlic'
	new WP_Term( 6, 'No onion', 'no-onion' ),
) );
$out = vance_recipe_labels_for( 102 );
check( '2b  unknown slugs (ibd, garlic-free) are dropped from both layers',
	$out['core'] === array() && array_keys( $out['descriptors'] ) === array( 'no-onion' ) );

// 2c — both layers populated at once, still each in its own model order.
set_terms_for_post( 103, array(
	new WP_Term( 7, 'Omega-3 Rich', 'omega-3-rich' ),
	new WP_Term( 8, 'No onion', 'no-onion' ),
	new WP_Term( 9, 'Dairy Free', 'dairy-free' ),
	new WP_Term( 10, 'Gluten Free', 'gluten-free' ),
) );
$out = vance_recipe_labels_for( 103 );
check( '2c  core: gluten-free before dairy-free (model order, not assignment order)',
	array_keys( $out['core'] ) === array( 'gluten-free', 'dairy-free' ) );
check( '2c  descriptors: no-onion before omega-3-rich (model order)',
	array_keys( $out['descriptors'] ) === array( 'no-onion', 'omega-3-rich' ) );
check( '2c  values are the display labels, not the slugs',
	$out['core']['gluten-free'] === 'Gluten Free' );

// 2d — get_the_terms() returning false (no terms / bad taxonomy) does not fatal.
set_terms_for_post( 104, false );
$out = vance_recipe_labels_for( 104 );
check( '2d  get_the_terms() === false yields both layers empty, not a fatal',
	$out['core'] === array() && $out['descriptors'] === array() );

// 2e — get_the_terms() returning a WP_Error does not fatal.
set_terms_for_post( 105, new WP_Error( 'invalid_taxonomy' ) );
$out = vance_recipe_labels_for( 105 );
check( '2e  get_the_terms() === WP_Error yields both layers empty, not a fatal',
	$out['core'] === array() && $out['descriptors'] === array() );

// 2f — no terms at all assigned to the post.
set_terms_for_post( 106, array() );
$out = vance_recipe_labels_for( 106 );
check( '2f  a post with zero terms returns both layers empty',
	$out['core'] === array() && $out['descriptors'] === array() );


/* ===================================================================== */
section( '3. vance_recipe_planner_data()' );
/* ===================================================================== */

set_terms_for_post( 201, array(
	new WP_Term( 20, 'High Fibre', 'high-fibre' ),
	new WP_Term( 21, 'Gluten Free', 'gluten-free' ),
	new WP_Term( 22, 'No garlic', 'no-garlic' ),
	new WP_Term( 23, 'Nutrient dense', 'nutrient-dense' ),
) );
set_terms_for_post( 202, array() ); // no labels at all

set_catalogue( array(
	'porridge-bowl' => array( 'id' => 201, 'name' => 'Porridge Bowl', 'category' => 'breakfast' ),
	'plain-rice'    => array( 'id' => 202, 'name' => 'Plain Rice', 'category' => 'dinner' ),
) );
set_recipe_data( array(
	'porridge-bowl' => array( 'nutrition' => array( 'calories' => 320 ), 'prep' => 5, 'cook' => 10, 'servings' => 2 ),
	'plain-rice'    => array( 'nutrition' => array( 'calories' => 200 ), 'prep' => 2, 'cook' => 15, 'servings' => 4 ),
) );
set_can_manage( false );

$rows = vance_recipe_planner_data();
check( '3a  one row per catalogue entry',
	count( $rows ) === 2 );

$porridge = $rows[0];
check( '3b  tags = core slugs then descriptor slugs, each in model order',
	$porridge['tags'] === array( 'gluten-free', 'high-fibre', 'no-garlic', 'nutrient-dense' ) );

check( '3c  chips = descriptor DISPLAY LABELS only, core labels excluded entirely',
	$porridge['chips'] === array( 'No garlic', 'Nutrient dense' ) );

check( '3d  chips never contains a core label or a slug',
	! in_array( 'Gluten Free', $porridge['chips'], true )
	&& ! in_array( 'gluten-free', $porridge['chips'], true ) );

$rice = $rows[1];
check( '3e  a recipe with no labels gets empty tags and empty chips, not a fatal',
	$rice['tags'] === array() && $rice['chips'] === array() );

check( '3f  dateUploaded/dateTimestamp are absent for a non-admin viewer',
	! array_key_exists( 'dateUploaded', $porridge ) && ! array_key_exists( 'dateTimestamp', $porridge ) );

set_can_manage( true );
$GLOBALS['POST_DATES'][201] = '18 Sep 2026';
$GLOBALS['POST_TIMES'][201] = 1758153600;
$rows2 = vance_recipe_planner_data();
check( '3g  dateUploaded/dateTimestamp ARE present for an administrator',
	$rows2[0]['dateUploaded'] === '18 Sep 2026' && $rows2[0]['dateTimestamp'] === 1758153600 );
set_can_manage( false );


/* ===================================================================== */
section( '4. tools/recipe-labels.json is consistent with the model' );
/* ===================================================================== */

$json_path = $ROOT . '/tools/recipe-labels.json';
$json_raw  = file_get_contents( $json_path );
check( '4-precondition  recipe-labels.json exists and is readable',
	$json_raw !== false );

$json = json_decode( (string) $json_raw, true );
check( '4-precondition  recipe-labels.json parses as valid JSON with a recipes array',
	is_array( $json ) && isset( $json['recipes'] ) && is_array( $json['recipes'] ) );

$recipes    = $json['recipes'];
$model_full = array_merge( vance_recipe_label_model()['core'], vance_recipe_label_model()['descriptors'] );

check( '4a  exactly 90 recipes',
	count( $recipes ) === 90 );

$ids = array_map( function ( $r ) { return $r['id']; }, $recipes );
check( '4b  every recipe id is unique',
	count( $ids ) === count( array_unique( $ids ) ) );

$unknown_labels = array();
foreach ( $recipes as $r ) {
	foreach ( $r['labels'] as $slug ) {
		if ( ! isset( $model_full[ $slug ] ) ) { $unknown_labels[] = $slug; }
	}
}
check( '4c  every label slug used in the file is one of the 14 in the model',
	$unknown_labels === array() );

$counts = array_fill_keys( array_keys( $model_full ), 0 );
foreach ( $recipes as $r ) {
	foreach ( $r['labels'] as $slug ) {
		if ( isset( $counts[ $slug ] ) ) { $counts[ $slug ]++; }
	}
}
$expected_counts = array(
	'gluten-free'        => 70,
	'dairy-free'         => 57,
	'vegetarian'         => 53,
	'vegan-friendly'     => 26,
	'low-fibre'          => 8,
	'high-fibre'         => 59,
	'high-protein'       => 52,
	'low-fodmap'         => 14,
	'no-onion'           => 67,
	'no-garlic'          => 62,
	'easy-to-digest'     => 17,
	'nutrient-dense'     => 63,
	'refined-sugar-free' => 89,
	'omega-3-rich'       => 23,
);
foreach ( $expected_counts as $slug => $n ) {
	check( "4d  $slug: file carries $n recipes",
		isset( $counts[ $slug ] ) && $counts[ $slug ] === $n );
}


/* ===================================================================== */
section( '5. tools/apply-recipe-labels.php: $vance_labels matches the model' );
/* ===================================================================== */

// Parsed as TEXT — never require()'d/eval()'d. The file exits immediately
// without WP_CLI defined (see its own header comment), so including it would
// silently test nothing rather than the real $vance_labels list.
$apply_path = $ROOT . '/tools/apply-recipe-labels.php';
$apply_src  = file_get_contents( $apply_path );
check( '5-precondition  apply-recipe-labels.php exists and is readable',
	$apply_src !== false );

check( '5-precondition  the file still guards on WP_CLI (never safe to include)',
	strpos( $apply_src, "if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {" ) !== false );

preg_match( '/\$vance_labels\s*=\s*array\((.*?)\n\);/s', (string) $apply_src, $m );
check( '5-precondition  $vance_labels array literal found in the file',
	isset( $m[1] ) );

$file_pairs = array();
preg_match_all( "/'([a-z0-9\\-]+)'\\s*=>\\s*'([^']*)'/", isset( $m[1] ) ? $m[1] : '', $pairs, PREG_SET_ORDER );
foreach ( $pairs as $p ) {
	$file_pairs[ $p[1] ] = $p[2];
}

check( '5a  the file lists exactly 14 slugs, same count as the model',
	count( $file_pairs ) === count( $model_full ) );

$model_slugs = array_keys( $model_full );
$file_slugs  = array_keys( $file_pairs );
sort( $model_slugs );
sort( $file_slugs );
check( '5b  the slug SET is identical to the model (no extra, none missing)',
	$model_slugs === $file_slugs );

check( '5c  the slugs are listed in the same order as the model (core, then descriptors)',
	array_keys( $file_pairs ) === array_keys( $model_full ) );

$label_mismatches = array();
foreach ( $model_full as $slug => $label ) {
	if ( isset( $file_pairs[ $slug ] ) && $file_pairs[ $slug ] !== $label ) {
		$label_mismatches[] = "$slug: model says '$label', file says '{$file_pairs[ $slug ]}'";
	}
}
check( '5d  every display label text matches the model exactly',
	$label_mismatches === array() );


/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
