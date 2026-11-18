<?php
/**
 * Tests for the switchable Hub home page: inc/hub-layout.php and
 * front-page-hub.php.
 *
 * Same shape as category-hero.test.php and gi-hero.test.php: WordPress stubs, a
 * bag of globals the stubs read, and assertions on return values and on the
 * rendered HTML. Nothing here loads WordPress.
 *
 * The stubs for escaping mirror WordPress where a check depends on it:
 * esc_url() drops any scheme outside its allowlist (so a javascript: link
 * renders as an empty href), sanitize_key() lowercases and strips characters
 * exactly as WordPress does, and esc_html() does not double-encode.
 *
 * WHAT IS COVERED
 *   1  layout resolution: the saved mod, the ?vhh_layout= preview, who may preview
 *   2  the same decision read from the request ($_GET and the capability)
 *   3  the template_include swap, and nothing else being swapped
 *   4  the hooks the feature depends on
 *   5  the assets, and the body class
 *   6  the date helpers
 *   7  member bar mode and the Founding Member deadline
 *   8  member counts: cache, week query, failure path
 *   9  tile filtering, the condition split, the icons
 *   10 the rendered template with nothing saved (the data rule)
 *   11 the rendered template with real data, guest and member
 *   12 the rendered template: boundaries, unsafe and failing inputs
 *
 * A check whose label says what should happen and which fails on the current
 * code is a finding, not a broken check. The runner lists every failure.
 */

define( 'ABSPATH', true );
define( 'HOUR_IN_SECONDS', 3600 );
date_default_timezone_set( 'UTC' );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';
$GLOBALS['THEME_DIR'] = $THEME;
$GLOBALS['HOOKS']     = array();
$RENDER_ERROR         = '';

/* ---- state the stubs read; every test sets its own through arrange() ----- */

function reset_state() {
	$GLOBALS['MODS']           = array();
	$GLOBALS['CAPS']           = array();
	$GLOBALS['IS_FRONT']       = false;
	$GLOBALS['LOGGED_IN']      = false;
	$GLOBALS['TODAY']          = '2026-11-18';
	$GLOBALS['LOCATE_FAILS']   = false;
	$GLOBALS['TRANSIENTS']     = array();
	$GLOBALS['SET_TRANSIENTS'] = array();
	$GLOBALS['ENQUEUED']       = array();
	$GLOBALS['USERS_TOTAL']    = 0;
	$GLOBALS['USERS_WEEK']     = 0;
	$GLOBALS['USER_QUERY_ARGS'] = null;
	$GLOBALS['USER_QUERY_LOG']  = array();
	$GLOBALS['QUERY_POSTS']    = array();
	$GLOBALS['META']           = array();
	$GLOBALS['TERMS_BY_POST']  = array();
	$GLOBALS['CATS_BY_POST']   = array();
	$GLOBALS['THUMB_URL']      = array();
	$GLOBALS['THUMB_ID']       = array();
	$GLOBALS['RECIPE_CELLS']   = array();
	$GLOBALS['SURVEY_STEPS']   = 0;
	$_GET                      = array();
}

function arrange( array $mods = array(), $is_front = false, array $caps = array(), array $get = array() ) {
	reset_state();
	$GLOBALS['MODS']     = $mods;
	$GLOBALS['IS_FRONT'] = $is_front;
	$GLOBALS['CAPS']     = $caps;
	$_GET                = $get;
}

/* ---- error handling: a notice in the template is a failure, not a pass ---- */

set_error_handler( function ( $no, $str, $file, $line ) {
	if ( ! ( error_reporting() & $no ) ) {
		return false;
	}
	throw new ErrorException( $str, 0, $no, $file, $line );
} );

/* ---- WordPress stubs ------------------------------------------------------ */

function sanitize_key( $key ) {
	// Mirrors WordPress: strtolower() then strip everything but [a-z0-9_-].
	// On an array this throws a TypeError under PHP 8, as it does in WordPress.
	$key = strtolower( $key );
	return preg_replace( '/[^a-z0-9_\-]/', '', $key );
}
function wp_unslash( $value ) { return is_string( $value ) ? stripslashes( $value ) : $value; }
function current_user_can( $cap ) { return ! empty( $GLOBALS['CAPS'][ $cap ] ); }
function is_user_logged_in() { return (bool) $GLOBALS['LOGGED_IN']; }
function is_front_page() { return (bool) $GLOBALS['IS_FRONT']; }
function vance_get_theme_mod( $key, $default = '' ) {
	return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
}
function get_template_directory() { return $GLOBALS['THEME_DIR']; }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/vance-health-hub'; }
function locate_template( $name ) {
	if ( $GLOBALS['LOCATE_FAILS'] ) {
		return '';
	}
	$path = $GLOBALS['THEME_DIR'] . '/' . $name;
	return file_exists( $path ) ? $path : '';
}
function add_filter( $tag, $callback, $priority = 10, $accepted = 1 ) {
	$GLOBALS['HOOKS'][ $tag ][ $priority ][] = $callback;
	return true;
}
function apply_filters( $tag, $value ) {
	return array_key_exists( $tag, $GLOBALS['FILTER_RETURNS'] ?? array() ) ? $GLOBALS['FILTER_RETURNS'][ $tag ] : $value;
}
function add_action( $tag, $callback, $priority = 10, $accepted = 1 ) {
	return add_filter( $tag, $callback, $priority, $accepted );
}
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false ) {
	$GLOBALS['ENQUEUED'][ 'style:' . $handle ] = array( 'src' => $src, 'deps' => $deps, 'ver' => $ver );
}
function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
	$GLOBALS['ENQUEUED'][ 'script:' . $handle ] = array( 'src' => $src, 'deps' => $deps, 'in_footer' => $in_footer );
}
function get_transient( $key ) {
	return array_key_exists( $key, $GLOBALS['TRANSIENTS'] ) ? $GLOBALS['TRANSIENTS'][ $key ] : false;
}
function set_transient( $key, $value, $expiration = 0 ) {
	$GLOBALS['TRANSIENTS'][ $key ]     = $value;
	$GLOBALS['SET_TRANSIENTS'][ $key ] = $expiration;
	return true;
}
function delete_transient( $key ) {
	unset( $GLOBALS['TRANSIENTS'][ $key ] );
	return true;
}
function count_users() {
	if ( null === $GLOBALS['USERS_TOTAL'] ) {
		return array();
	}
	return array( 'total_users' => $GLOBALS['USERS_TOTAL'] );
}
class WP_User_Query {
	private $args;
	public function __construct( $args ) {
		$this->args                   = $args;
		$GLOBALS['USER_QUERY_ARGS']   = $args;
		$GLOBALS['USER_QUERY_LOG'][]  = $args;
	}
	// A query with a date_query is the weekly figure; one without is the total.
	public function get_total() {
		return isset( $this->args['date_query'] ) ? $GLOBALS['USERS_WEEK'] : $GLOBALS['USERS_TOTAL'];
	}
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
function wp_date( $format, $timestamp = null ) {
	if ( null === $timestamp ) {
		$timestamp = strtotime( $GLOBALS['TODAY'] . ' 12:00:00 UTC' );
	}
	return gmdate( $format, $timestamp );
}
function wp_timezone() { return new DateTimeZone( 'Europe/London' ); }
function get_option( $key, $default = false ) { return 'date_format' === $key ? 'j F Y' : $default; }
function absint( $n ) { return abs( (int) $n ); }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function get_page_by_path( $slug ) { return null; }
function get_permalink( $post ) { return 'https://example.test/p/' . $post->ID . '/'; }
function add_query_arg( $key, $value, $url ) {
	return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . $key . '=' . rawurlencode( (string) $value );
}
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_url( $url ) {
	// Mirrors the part of WordPress esc_url() that matters here: a scheme outside
	// the allowlist is dropped and the attribute is left empty.
	$url = (string) $url;
	if ( preg_match( '#^([a-z][a-z0-9+.\-]*):#i', $url, $m ) && ! in_array( strtolower( $m[1] ), array( 'http', 'https', 'mailto', 'tel' ), true ) ) {
		return '';
	}
	return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8', false );
}
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }
function wp_specialchars_decode( $text ) { return htmlspecialchars_decode( (string) $text, ENT_QUOTES ); }
function wp_strip_all_tags( $text ) { return strip_tags( (string) $text ); }
function number_format_i18n( $n ) { return number_format( (float) $n ); }
function __( $text, $domain = '' ) { return $text; }
function _n( $single, $plural, $number, $domain = '' ) { return 1 == $number ? $single : $plural; }
function get_header() {}
function get_footer() {}

/* Survey and recipe helpers the template reads from elsewhere in the theme. */
function vance_quiz_steps() { return array_fill( 0, $GLOBALS['SURVEY_STEPS'], 'step' ); }
function vance_page_hero_spotlight_recipe_stats() { return $GLOBALS['RECIPE_CELLS']; }
function vance_gi_page_url( $slug ) { return 'https://example.test/gi/' . $slug . '/'; }
function vance_gi_hub_url() { return 'https://example.test/gastro-health-explained/'; }

/* Post and term accessors. */
class WP_Query {
	public $posts = array();
	public function __construct( $args ) {
		$type      = $args['post_type'];
		$all       = isset( $GLOBALS['QUERY_POSTS'][ $type ] ) ? $GLOBALS['QUERY_POSTS'][ $type ] : array();
		$this->posts = array_slice( $all, 0, (int) $args['posts_per_page'] );
	}
}
function post_obj( $id, $title, $content = '' ) {
	return (object) array( 'ID' => $id, 'post_title' => $title, 'post_content' => $content, 'post_type' => 'post' );
}
function get_the_title( $post ) { return $post->post_title; }
function get_post_meta( $post_id, $key = '', $single = false ) {
	return isset( $GLOBALS['META'][ $post_id ][ $key ] ) ? $GLOBALS['META'][ $post_id ][ $key ] : '';
}
function get_post_thumbnail_id( $post_id ) {
	return isset( $GLOBALS['THUMB_ID'][ $post_id ] ) ? $GLOBALS['THUMB_ID'][ $post_id ] : 0;
}
function get_the_post_thumbnail_url( $post, $size = 'post-thumbnail' ) {
	return isset( $GLOBALS['THUMB_URL'][ $post->ID ] ) ? $GLOBALS['THUMB_URL'][ $post->ID ] : false;
}
function get_the_terms( $post, $taxonomy ) {
	return isset( $GLOBALS['TERMS_BY_POST'][ $post->ID ] ) ? $GLOBALS['TERMS_BY_POST'][ $post->ID ] : false;
}
function get_the_category( $post_id ) {
	return isset( $GLOBALS['CATS_BY_POST'][ $post_id ] ) ? $GLOBALS['CATS_BY_POST'][ $post_id ] : array();
}

/* ---- load the code under test --------------------------------------------- */

require_once $THEME . '/inc/thumbnail-alt.php';   // the real helper, not a copy
require_once $THEME . '/inc/hub-layout.php';

/*
 * The condition registry lives in functions.php, which cannot be loaded here
 * (it needs all of WordPress). Lift the one function the template reads out of
 * the file and evaluate it, as gi-hero.test.php does, so the suite tests the
 * real registry rather than a copy that could rot.
 */
$SRC = file_get_contents( $THEME . '/functions.php' );
preg_match( '/function vance_gi_condition_cards\(\) \{.*?\r?\n\}\r?\n/s', $SRC, $LIFT );
if ( empty( $LIFT[0] ) ) {
	fwrite( STDERR, "could not lift vance_gi_condition_cards() from functions.php\n" );
	exit( 2 );
}
eval( $LIFT[0] );

/* ---- rendering -------------------------------------------------------------- */

/** Render front-page-hub.php and return its output. Errors are recorded, not thrown past the runner. */
function render_home( $logged_in ) {
	global $RENDER_ERROR;
	$RENDER_ERROR              = '';
	$GLOBALS['LOGGED_IN']      = $logged_in;
	ob_start();
	try {
		include $GLOBALS['THEME_DIR'] . '/front-page-hub.php';
	} catch ( Throwable $e ) {
		$RENDER_ERROR = $e->getMessage();
	}
	return ob_get_clean();
}

/** Every <img> tag whose alt attribute is missing or empty. */
function imgs_without_alt( $html ) {
	preg_match_all( '/<img\b(?![^>]*\salt="[^"]+")[^>]*>/', $html, $m );
	return $m[0];
}

/** The full data set: a live member total, a deadline, tiles, quotes, recipes, library posts. */
function seed_full_home() {
	$GLOBALS['MODS'] = array(
		'vance_hub_founding_deadline' => '2026-12-01',
		'vance_hub_tile_1_badge'      => 'Members',
		'vance_hub_tile_1_title'      => 'Live Q&A',
		'vance_hub_tile_1_text'       => 'Ask the panel',
		'vance_hub_tile_1_link'       => 'https://example.test/live/',
		'vance_hub_tile_2_title'      => 'Bad link tile',
		'vance_hub_tile_2_link'       => 'javascript:alert(1)',
		'vance_hub_tile_3_title'      => '<b>Bold</b> tile',
		'vance_hub_tile_4_text'       => 'Orphan text',
		'vance_hub_tile_4_link'       => 'https://example.test/orphan/',
	);
	$GLOBALS['USERS_TOTAL']  = 1234;
	$GLOBALS['USERS_WEEK']   = 17;
	$GLOBALS['SURVEY_STEPS'] = 9;
	$GLOBALS['RECIPE_CELLS'] = array( array( 'key' => 'grid', 'value' => '1,091', 'label' => 'Recipes', 'href' => '' ) );

	$GLOBALS['QUERY_POSTS']['testimonial'] = array(
		post_obj( 501, 'Jane Doe', '<p>I finally slept through the night.</p>' ),
		post_obj( 502, 'Blank', '   ' ),
	);
	$GLOBALS['META'][501]['_testimonial_role'] = 'Patient';

	$GLOBALS['QUERY_POSTS']['vance_recipe'] = array( post_obj( 601, 'Porridge Bowl' ), post_obj( 602, 'Plain Rice' ) );
	$GLOBALS['TERMS_BY_POST'][601]          = array( (object) array( 'name' => 'Breakfast' ) );

	$GLOBALS['QUERY_POSTS']['post'] = array(
		post_obj( 701, 'Feature article' ),
		post_obj( 702, 'Second article' ),
		post_obj( 703, 'Third article' ),
		post_obj( 704, 'Fourth article' ),
		post_obj( 705, 'Fifth article' ),
	);
	$GLOBALS['THUMB_URL'][701]                                  = 'https://example.test/wp-content/uploads/feature.jpg';
	$GLOBALS['THUMB_ID'][701]                                   = 9001;
	$GLOBALS['META'][9001]['_wp_attachment_image_alt']          = 'A featured photo for the library';
	$GLOBALS['CATS_BY_POST'][701] = array(
		(object) array( 'slug' => 'uncategorized', 'name' => 'Uncategorized' ),
		(object) array( 'slug' => 'gastro-living', 'name' => 'Gastro Living &amp; More' ),
	);
}

$FRONT_PAGE = $THEME . '/front-page.php';

/* ===================================================================== */

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/* ---- 1. layout resolution, pure ------------------------------------------- */
section( '1. Layout resolution (pure)' );

check( 'sanitize: a null mod is classic, the default', vance_hub_sanitize_layout( null ) === 'classic' );
check( 'sanitize: an empty mod is classic', vance_hub_sanitize_layout( '' ) === 'classic' );
check( 'sanitize: a garbage mod is classic', vance_hub_sanitize_layout( 'hubby' ) === 'classic' );
check( 'sanitize: an array mod is classic and does not throw', vance_hub_sanitize_layout( array( 'hub' ) ) === 'classic' );
check( 'sanitize: boolean true is classic', vance_hub_sanitize_layout( true ) === 'classic' );
check( 'sanitize: an integer is classic', vance_hub_sanitize_layout( 1 ) === 'classic' );
check( 'sanitize: "hub" stays hub', vance_hub_sanitize_layout( 'hub' ) === 'hub' );
check( 'sanitize: upper case and padding are normalised to hub', vance_hub_sanitize_layout( "  HUB\n" ) === 'hub' );

check( 'resolve: no mod, no query, no preview is classic', vance_hub_resolve_layout( null, '', false ) === 'classic' );
check( 'resolve: saved hub with no query is hub', vance_hub_resolve_layout( 'hub', '', false ) === 'hub' );
check( 'resolve: admin preview hub overrides a classic mod', vance_hub_resolve_layout( 'classic', 'hub', true ) === 'hub' );
check( 'resolve: admin preview classic overrides a hub mod', vance_hub_resolve_layout( 'hub', 'classic', true ) === 'classic' );
check( 'resolve: non-admin hub query is ignored when the mod is classic', vance_hub_resolve_layout( 'classic', 'hub', false ) === 'classic' );
check( 'resolve: non-admin classic query is ignored when the mod is hub', vance_hub_resolve_layout( 'hub', 'classic', false ) === 'hub' );
check( 'resolve: admin query is case-insensitive', vance_hub_resolve_layout( 'classic', 'HUB', true ) === 'hub' );
check( 'resolve: admin query is trimmed of whitespace', vance_hub_resolve_layout( 'classic', " hub \t", true ) === 'hub' );
check( 'resolve: an array query is ignored and the mod decides (hub)', vance_hub_resolve_layout( 'hub', array( 'classic' ), true ) === 'hub' );
check( 'resolve: an array query cannot force hub over a classic mod', vance_hub_resolve_layout( 'classic', array( 'hub' ), true ) === 'classic' );
check( 'resolve: an integer query is ignored', vance_hub_resolve_layout( 'classic', 1, true ) === 'classic' );
check( 'resolve: an unknown query value falls through to the mod', vance_hub_resolve_layout( 'hub', 'banana', true ) === 'hub' );
check( 'resolve: an empty query falls through to the mod', vance_hub_resolve_layout( 'hub', '', true ) === 'hub' );
check( 'resolve: a garbage mod with an admin hub preview is hub', vance_hub_resolve_layout( 'junk', 'hub', true ) === 'hub' );
check( 'resolve: a garbage mod with no preview is classic', vance_hub_resolve_layout( 'junk', '', false ) === 'classic' );

/* ---- 2. the same decision read from the request ---------------------------- */
section( '2. Layout active (request, capability)' );

arrange();
check( 'active: no mod and no query is not active (classic default)', vance_hub_layout_active() === false );

arrange( array( 'vance_home_layout' => 'hub' ) );
check( 'active: saved hub, no query, is active', vance_hub_layout_active() === true );

arrange( array( 'vance_home_layout' => 'classic' ), false, array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'active: admin ?vhh_layout=hub previews hub over a classic mod', vance_hub_layout_active() === true );

arrange( array( 'vance_home_layout' => 'hub' ), false, array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'classic' ) );
check( 'active: admin ?vhh_layout=classic forces classic over a hub mod', vance_hub_layout_active() === false );

arrange( array( 'vance_home_layout' => 'classic' ), false, array(), array( 'vhh_layout' => 'hub' ) );
check( 'active: visitor ?vhh_layout=hub is ignored, classic stays', vance_hub_layout_active() === false );

arrange( array( 'vance_home_layout' => 'hub' ), false, array(), array( 'vhh_layout' => 'classic' ) );
check( 'active: visitor ?vhh_layout=classic is ignored, hub stays', vance_hub_layout_active() === true );

arrange( array( 'vance_home_layout' => 'classic' ), false, array( 'manage_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'active: manage_options alone does not allow a preview (edit_theme_options is the gate)', vance_hub_layout_active() === false );

arrange( array( 'vance_home_layout' => 'classic' ), false, array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'HUB' ) );
check( 'active: admin ?vhh_layout=HUB is read case-insensitively', vance_hub_layout_active() === true );

arrange( array( 'vance_home_layout' => 'classic' ), false, array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub ' ) );
check( 'active: admin ?vhh_layout=hub with a trailing space is read as hub', vance_hub_layout_active() === true );

arrange( array( 'vance_home_layout' => 'classic' ), false, array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub;drop' ) );
check( 'active: an injected query value is not read as hub', vance_hub_layout_active() === false );

// ?vhh_layout[]=x arrives as an array, which sanitize_key() cannot take. Two
// guards stop it: the capability check (visitor) and is_string (admin). The
// admin case is the one that fails if the is_string guard is removed.
foreach ( array( 'a visitor' => array(), 'an admin' => array( 'edit_theme_options' => true ) ) as $WHO => $CAPS ) {
	arrange( array( 'vance_home_layout' => 'classic' ), false, $CAPS, array( 'vhh_layout' => array( 'hub' ) ) );
	$ACTIVE_RESULT = null;
	try {
		$ACTIVE_RESULT = vance_hub_layout_active();
	} catch ( Throwable $e ) {
		$ACTIVE_RESULT = 'threw: ' . $e->getMessage();
	}
	check( 'active: ' . $WHO . ' sending ?vhh_layout[]= gets classic and no exception', $ACTIVE_RESULT === false );
}

/* ---- 3. template_include: swap only the classic front page ------------------ */
section( '3. The template swap' );

arrange( array( 'vance_home_layout' => 'hub' ), true );
check( 'swap: front page, hub active: front-page.php becomes front-page-hub.php',
	vance_hub_template_include( $FRONT_PAGE ) === $THEME . '/front-page-hub.php' );

arrange( array( 'vance_home_layout' => 'classic' ), true );
check( 'swap: front page, classic active: front-page.php is left alone',
	vance_hub_template_include( $FRONT_PAGE ) === $FRONT_PAGE );

arrange( array( 'vance_home_layout' => 'hub' ), true, array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'classic' ) );
check( 'swap: admin previewing classic over a hub mod: front-page.php is left alone',
	vance_hub_template_include( $FRONT_PAGE ) === $FRONT_PAGE );

arrange( array( 'vance_home_layout' => 'hub' ), false );
check( 'swap: hub active but not the front page: front-page.php is left alone',
	vance_hub_template_include( $FRONT_PAGE ) === $FRONT_PAGE );

arrange( array( 'vance_home_layout' => 'hub' ), true );
check( 'swap: front page with index.php (the posts-page fallback) is left alone',
	vance_hub_template_include( $THEME . '/index.php' ) === $THEME . '/index.php' );

arrange( array( 'vance_home_layout' => 'hub' ), true );
check( 'swap: front page with single.php is left alone',
	vance_hub_template_include( $THEME . '/single.php' ) === $THEME . '/single.php' );

arrange( array( 'vance_home_layout' => 'hub' ), true );
check( 'swap: an empty template name is returned unchanged',
	vance_hub_template_include( '' ) === '' );

arrange( array( 'vance_home_layout' => 'hub' ), true );
$GLOBALS['LOCATE_FAILS'] = true;
check( 'swap: when the hub template cannot be located, the original template is returned',
	vance_hub_template_include( $FRONT_PAGE ) === $FRONT_PAGE );

/* ---- 4. the hooks the feature depends on ---------------------------------- */
section( '4. Hook registration' );

check( 'hook: template_include runs vance_hub_template_include at priority 20',
	isset( $GLOBALS['HOOKS']['template_include'][20] )
	&& in_array( 'vance_hub_template_include', $GLOBALS['HOOKS']['template_include'][20], true ) );
check( 'hook: wp_enqueue_scripts runs vance_hub_enqueue_assets at priority 20',
	isset( $GLOBALS['HOOKS']['wp_enqueue_scripts'][20] )
	&& in_array( 'vance_hub_enqueue_assets', $GLOBALS['HOOKS']['wp_enqueue_scripts'][20], true ) );
check( 'hook: body_class runs vance_hub_body_class',
	isset( $GLOBALS['HOOKS']['body_class'][10] )
	&& in_array( 'vance_hub_body_class', $GLOBALS['HOOKS']['body_class'][10], true ) );
check( 'hook: customize_register runs vance_hub_customize_register',
	isset( $GLOBALS['HOOKS']['customize_register'][10] )
	&& in_array( 'vance_hub_customize_register', $GLOBALS['HOOKS']['customize_register'][10], true ) );
// A sign-up must not throw the cache away: a burst of registrations would then
// rebuild the counts on every page view.
check( 'hook: user_register does not clear the member count cache', ! isset( $GLOBALS['HOOKS']['user_register'] ) );
check( 'hook: deleted_user does not clear the member count cache', ! isset( $GLOBALS['HOOKS']['deleted_user'] ) );

/* ---- 5. assets and body class ---------------------------------------------- */
section( '5. Assets and body class' );

arrange( array( 'vance_home_layout' => 'hub' ), true );
vance_hub_enqueue_assets();
check( 'assets: hub front page enqueues the stylesheet from the theme',
	isset( $GLOBALS['ENQUEUED']['style:vance-hub-home'] )
	&& $GLOBALS['ENQUEUED']['style:vance-hub-home']['src'] === 'https://example.test/wp-content/themes/vance-health-hub/assets/css/hub-home.css' );
check( 'assets: the stylesheet depends on the main theme stylesheet',
	isset( $GLOBALS['ENQUEUED']['style:vance-hub-home'] )
	&& $GLOBALS['ENQUEUED']['style:vance-hub-home']['deps'] === array( 'vance-main-style' ) );
check( 'assets: hub front page enqueues the script from the theme, in the footer',
	isset( $GLOBALS['ENQUEUED']['script:vance-hub-home'] )
	&& $GLOBALS['ENQUEUED']['script:vance-hub-home']['src'] === 'https://example.test/wp-content/themes/vance-health-hub/assets/js/hub-home.js'
	&& $GLOBALS['ENQUEUED']['script:vance-hub-home']['in_footer'] === true );

arrange( array( 'vance_home_layout' => 'classic' ), true );
vance_hub_enqueue_assets();
check( 'assets: classic front page enqueues nothing from the hub', $GLOBALS['ENQUEUED'] === array() );

arrange( array( 'vance_home_layout' => 'hub' ), false );
vance_hub_enqueue_assets();
check( 'assets: hub active on a non-front page enqueues nothing', $GLOBALS['ENQUEUED'] === array() );

arrange( array( 'vance_home_layout' => 'hub' ), true );
check( 'body class: hub front page adds vhub-home and keeps existing classes',
	vance_hub_body_class( array( 'page-id-2' ) ) === array( 'page-id-2', 'vhub-home' ) );

arrange( array( 'vance_home_layout' => 'classic' ), true );
check( 'body class: classic front page adds nothing',
	vance_hub_body_class( array( 'page-id-2' ) ) === array( 'page-id-2' ) );

/* ---- 6. date helpers ------------------------------------------------------- */
section( '6. Date helpers' );

check( 'date: a valid Y-m-d is returned unchanged', vance_hub_sanitize_date( '2026-12-01' ) === '2026-12-01' );
check( 'date: surrounding whitespace is trimmed', vance_hub_sanitize_date( ' 2026-12-01 ' ) === '2026-12-01' );
check( 'date: empty string is rejected', vance_hub_sanitize_date( '' ) === '' );
check( 'date: null is rejected', vance_hub_sanitize_date( null ) === '' );
check( 'date: an integer is rejected', vance_hub_sanitize_date( 20261201 ) === '' );
check( 'date: 29 February in a non-leap year is rejected', vance_hub_sanitize_date( '2026-02-29' ) === '' );
check( 'date: 29 February in a leap year is accepted', vance_hub_sanitize_date( '2028-02-29' ) === '2028-02-29' );
check( 'date: month 13 is rejected', vance_hub_sanitize_date( '2026-13-01' ) === '' );
check( 'date: month 00 is rejected', vance_hub_sanitize_date( '2026-00-10' ) === '' );
check( 'date: day 32 is rejected', vance_hub_sanitize_date( '2026-12-32' ) === '' );
check( 'date: year 0000 is rejected', vance_hub_sanitize_date( '0000-01-01' ) === '' );
check( 'date: unpadded month and day are rejected', vance_hub_sanitize_date( '2026-1-5' ) === '' );
check( 'date: a date with a time is rejected', vance_hub_sanitize_date( '2026-12-01T10:00' ) === '' );
check( 'date: a UK-style date is rejected', vance_hub_sanitize_date( '01/12/2026' ) === '' );
check( 'date: a word is rejected', vance_hub_sanitize_date( 'soon' ) === '' );
check( 'date: an injected suffix is rejected', vance_hub_sanitize_date( '2026-12-01; DROP' ) === '' );
check( 'date: an array is rejected without a warning', vance_hub_sanitize_date( array( '2026-12-01' ) ) === '' );

check( 'open: the deadline day itself is open', vance_hub_date_open( '2026-11-18', '2026-11-18' ) === true );
check( 'open: a deadline tomorrow is open', vance_hub_date_open( '2026-11-19', '2026-11-18' ) === true );
check( 'open: the day after the deadline is closed', vance_hub_date_open( '2026-11-17', '2026-11-18' ) === false );
check( 'open: a year boundary: the 1 January deadline is open on 31 December', vance_hub_date_open( '2027-01-01', '2026-12-31' ) === true );
check( 'open: a year boundary: the 31 December deadline is closed on 1 January', vance_hub_date_open( '2026-12-31', '2027-01-01' ) === false );
check( 'open: an empty deadline is closed', vance_hub_date_open( '', '2026-11-18' ) === false );
check( 'open: an invalid deadline is closed', vance_hub_date_open( '2026-02-30', '2026-11-18' ) === false );
check( 'open: an empty today is closed', vance_hub_date_open( '2026-11-18', '' ) === false );
check( 'open: a today in the wrong format is closed, not compared', vance_hub_date_open( '2026-11-18', '18/11/2026' ) === false );

/* ---- 7. member bar mode and the deadline ----------------------------------- */
section( '7. Member bar mode (pure)' );

check( 'bar: zero members, deadline open, logged out shows the Founding line', vance_hub_member_bar_mode( 0, 100, true, false ) === 'founding' );
check( 'bar: below threshold, deadline open, logged out shows the Founding line', vance_hub_member_bar_mode( 99, 100, true, false ) === 'founding' );
check( 'bar: below threshold, deadline open, logged in shows nothing', vance_hub_member_bar_mode( 99, 100, true, true ) === '' );
check( 'bar: below threshold, no deadline, logged out shows nothing', vance_hub_member_bar_mode( 99, 100, false, false ) === '' );
check( 'bar: exactly at the threshold shows counts', vance_hub_member_bar_mode( 100, 100, false, false ) === 'counts' );
check( 'bar: one above the threshold shows counts', vance_hub_member_bar_mode( 101, 100, false, true ) === 'counts' );
check( 'bar: at threshold, logged out, deadline open: counts win over the Founding line', vance_hub_member_bar_mode( 100, 100, true, false ) === 'counts' );
check( 'bar: no deadline and a large total still shows counts', vance_hub_member_bar_mode( 500, 100, false, false ) === 'counts' );
check( 'bar: zero members never shows counts, even with a zero threshold', vance_hub_member_bar_mode( 0, 0, false, false ) === '' );
check( 'bar: zero members with a zero threshold and an open deadline is the Founding line', vance_hub_member_bar_mode( 0, 0, true, false ) === 'founding' );
check( 'bar: a negative total is clamped to zero and shows nothing', vance_hub_member_bar_mode( -5, 0, false, false ) === '' );
check( 'bar: a negative threshold is clamped to zero, so 3 members show counts', vance_hub_member_bar_mode( 3, -10, false, false ) === 'counts' );
check( 'bar: numeric strings are read as numbers', vance_hub_member_bar_mode( '150', '100', false, false ) === 'counts' );

section( '7b. Founding Member deadline (WordPress-backed)' );

arrange( array( 'vance_hub_founding_deadline' => '2026-12-01' ) );
check( 'deadline: a future date is returned', vance_hub_founding_deadline() === '2026-12-01' );

arrange( array( 'vance_hub_founding_deadline' => '2026-11-18' ) );
check( 'deadline: today is still returned (the day counts)', vance_hub_founding_deadline() === '2026-11-18' );

arrange( array( 'vance_hub_founding_deadline' => '2026-11-01' ) );
check( 'deadline: a past date is hidden', vance_hub_founding_deadline() === '' );

arrange( array( 'vance_hub_founding_deadline' => 'soon' ) );
check( 'deadline: a non-date is hidden', vance_hub_founding_deadline() === '' );

arrange( array( 'vance_hub_founding_deadline' => '2026-02-30' ) );
check( 'deadline: an impossible date is hidden', vance_hub_founding_deadline() === '' );

arrange();
check( 'deadline: an unset mod is hidden', vance_hub_founding_deadline() === '' );

arrange( array( 'vance_hub_founding_deadline' => '2026-12-01' ) );
check( 'format: a Y-m-d date is shown in the site date format', vance_hub_format_date( '2026-12-01' ) === '1 December 2026' );
check( 'format: a malformed date formats to an empty string', vance_hub_format_date( 'garbage' ) === '' );

/* ---- 8. member counts: cache, week query, failure path --------------------- */
section( '8. Member counts' );

arrange();
$GLOBALS['USERS_TOTAL'] = 1234;
$GLOBALS['USERS_WEEK']  = 17;
$COUNTS = vance_hub_member_counts();
check( 'counts: total comes from a counted user query', $COUNTS['total'] === 1234 );
check( 'counts: week comes from the registration query', $COUNTS['week'] === 17 );
check( 'counts: the result is cached for one hour', $GLOBALS['SET_TRANSIENTS']['vance_hub_member_counts'] === 3600 );
check( 'counts: the week query looks back one week', $GLOBALS['USER_QUERY_ARGS']['date_query'][0]['after'] === '1 week ago' );
$STAFF = array( 'administrator', 'editor', 'author', 'contributor' );
check( 'counts: two queries run, the total and the week', count( $GLOBALS['USER_QUERY_LOG'] ) === 2 );
check( 'counts: the total query has no date limit', ! isset( $GLOBALS['USER_QUERY_LOG'][0]['date_query'] ) );
check( 'counts: the total leaves staff roles out', $GLOBALS['USER_QUERY_LOG'][0]['role__not_in'] === $STAFF );
check( 'counts: the week figure leaves the same staff roles out', $GLOBALS['USER_QUERY_LOG'][1]['role__not_in'] === $STAFF );
check( 'counts: each query fetches one row and a total, not every user',
	$GLOBALS['USER_QUERY_LOG'][0]['number'] === 1 && $GLOBALS['USER_QUERY_LOG'][0]['count_total'] === true );

arrange();
$GLOBALS['USERS_TOTAL'] = 3;
$GLOBALS['USERS_WEEK']  = 9;
$COUNTS = vance_hub_member_counts();
check( 'counts: the week figure is never larger than the total', $COUNTS['week'] === 3 );

$GLOBALS['FILTER_RETURNS'] = array( 'vance_hub_staff_roles' => 'not-an-array' );
check( 'staff roles: a filter that returns a non-array gives an empty list, not an error', vance_hub_staff_roles() === array() );
$GLOBALS['FILTER_RETURNS'] = array( 'vance_hub_staff_roles' => array( 'administrator', 7, 'shop_manager' ) );
check( 'staff roles: non-string entries from a filter are dropped', vance_hub_staff_roles() === array( 'administrator', 'shop_manager' ) );
$GLOBALS['FILTER_RETURNS'] = array();

arrange();
$GLOBALS['TRANSIENTS']['vance_hub_member_counts'] = array( 'total' => 5, 'week' => 2 );
$GLOBALS['USERS_TOTAL'] = 999;
$COUNTS = vance_hub_member_counts();
check( 'counts: a cached result is returned, not a fresh count', $COUNTS['total'] === 5 && $COUNTS['week'] === 2 );
check( 'counts: a cache hit runs no user query', $GLOBALS['USER_QUERY_ARGS'] === null );

arrange();
$GLOBALS['TRANSIENTS']['vance_hub_member_counts'] = 'junk';
$GLOBALS['USERS_TOTAL'] = 1234;
$COUNTS = vance_hub_member_counts();
check( 'counts: a corrupt cache value is replaced by a fresh count', $COUNTS['total'] === 1234 );

arrange();
$GLOBALS['TRANSIENTS']['vance_hub_member_counts'] = array( 'total' => 5 );
$GLOBALS['USERS_TOTAL'] = 1234;
$COUNTS = vance_hub_member_counts();
check( 'counts: a cache value missing the week key is replaced by a fresh count', $COUNTS['total'] === 1234 );

arrange();
$GLOBALS['USERS_TOTAL'] = null;
$GLOBALS['USERS_WEEK']  = 4;
$COUNTS = vance_hub_member_counts();
check( 'counts: when the total query returns nothing, the total is zero and nothing is shown as a figure', $COUNTS['total'] === 0 );
check( 'counts: a zero total does not make a week figure', $COUNTS['week'] === 0 );
check( 'counts: a zero total is cached for five minutes, not one hour', $GLOBALS['SET_TRANSIENTS']['vance_hub_member_counts'] === 300 );

arrange();
$GLOBALS['TRANSIENTS']['vance_hub_member_counts'] = array( 'total' => 5, 'week' => 2 );
vance_hub_flush_member_counts();
check( 'counts: flushing removes the cached counts', get_transient( 'vance_hub_member_counts' ) === false );

/* ---- 9. tile filtering, condition split, icons ----------------------------- */
section( '9. Tile filtering (pure)' );

function tile( $title, $extra = array() ) {
	return array_merge( array( 'badge' => '', 'title' => $title, 'text' => '', 'link' => '' ), $extra );
}

check( 'tiles: a tile with no title is dropped, the rest keep their order',
	array_column( vance_hub_filter_tiles( array( tile( 'A' ), tile( '' ), tile( 'B' ) ) ), 'title' ) === array( 'A', 'B' ) );
check( 'tiles: a whitespace-only title is dropped',
	array_column( vance_hub_filter_tiles( array( tile( '   ' ), tile( 'B' ) ) ), 'title' ) === array( 'B' ) );
check( 'tiles: all tiles empty gives an empty list',
	vance_hub_filter_tiles( array( tile( '' ), tile( ' ' ) ) ) === array() );
check( 'tiles: an empty input gives an empty list', vance_hub_filter_tiles( array() ) === array() );
check( 'tiles: null input gives an empty list', vance_hub_filter_tiles( null ) === array() );
check( 'tiles: a string input gives an empty list', vance_hub_filter_tiles( 'tiles' ) === array() );
check( 'tiles: a non-array entry is skipped', array_column( vance_hub_filter_tiles( array( 'junk', tile( 'A' ) ) ), 'title' ) === array( 'A' ) );
check( 'tiles: a title of "0" is a title and is kept', array_column( vance_hub_filter_tiles( array( tile( '0' ) ) ), 'title' ) === array( '0' ) );
check( 'tiles: missing keys default to empty strings',
	vance_hub_filter_tiles( array( array( 'title' => 'Only' ) ) ) === array( array( 'badge' => '', 'title' => 'Only', 'text' => '', 'link' => '' ) ) );
check( 'tiles: five valid tiles are capped at four', count( vance_hub_filter_tiles( array( tile( '1' ), tile( '2' ), tile( '3' ), tile( '4' ), tile( '5' ) ) ) ) === 4 );
check( 'tiles: the cap keeps the first four in order',
	array_column( vance_hub_filter_tiles( array( tile( '1' ), tile( '2' ), tile( '3' ), tile( '4' ), tile( '5' ) ) ), 'title' ) === array( '1', '2', '3', '4' ) );
check( 'tiles: exactly three valid tiles are all kept', count( vance_hub_filter_tiles( array( tile( '1' ), tile( '2' ), tile( '3' ) ) ) ) === 3 );

$CARDS = array(
	array( 'slug' => 'a' ), array( 'slug' => 'b' ), array( 'slug' => 'c' ), array( 'slug' => 'd' ),
);
list( $SPLIT_TILES, $SPLIT_LIST ) = vance_hub_split_conditions( $CARDS, array( 'c', 'a' ) );
check( 'split: featured slugs become tiles in featured order', array_column( $SPLIT_TILES, 'slug' ) === array( 'c', 'a' ) );
check( 'split: the rest become the list in registry order', array_column( $SPLIT_LIST, 'slug' ) === array( 'b', 'd' ) );

list( $SPLIT_TILES, $SPLIT_LIST ) = vance_hub_split_conditions( $CARDS, array( 'zzz', 'b' ) );
check( 'split: a featured slug missing from the registry is skipped', array_column( $SPLIT_TILES, 'slug' ) === array( 'b' ) );

list( $SPLIT_TILES, $SPLIT_LIST ) = vance_hub_split_conditions( $CARDS, array( 'b', 'b' ) );
check( 'split: a slug featured twice is shown once', array_column( $SPLIT_TILES, 'slug' ) === array( 'b' ) );
check( 'split: a slug featured twice is not repeated in the list', array_column( $SPLIT_LIST, 'slug' ) === array( 'a', 'c', 'd' ) );

list( $SPLIT_TILES, $SPLIT_LIST ) = vance_hub_split_conditions( $CARDS, array() );
check( 'split: no featured slugs leaves every card in the list', array_column( $SPLIT_LIST, 'slug' ) === array( 'a', 'b', 'c', 'd' ) && $SPLIT_TILES === array() );

list( $SPLIT_TILES, $SPLIT_LIST ) = vance_hub_split_conditions( null, array( 'a' ) );
check( 'split: a null registry gives two empty lists', $SPLIT_TILES === array() && $SPLIT_LIST === array() );

check( 'icon: a known icon renders an aria-hidden svg', strpos( vance_hub_icon( 'check' ), '<svg class="vhub-icon" aria-hidden="true"' ) === 0 );
check( 'icon: an unknown icon renders nothing', vance_hub_icon( 'nope' ) === '' );
check( 'icon: an injected icon name renders nothing', vance_hub_icon( '<script>alert(1)</script>' ) === '' );

/* ---- 10. the rendered template with nothing saved (the data rule) ---------- */
section( '10. Rendered template, nothing saved, guest' );

arrange();
$HTML = render_home( false );
check( 'empty guest: renders without a PHP error', $RENDER_ERROR === '' );
check( 'empty guest: exactly one <h1>', substr_count( $HTML, '<h1' ) === 1 );
check( 'empty guest: no member bar', strpos( $HTML, 'class="vhub-bar"' ) === false );
check( 'empty guest: no "Inside the Hub" section', strpos( $HTML, 'vhub-inside' ) === false );
check( 'empty guest: no community block', strpos( $HTML, 'vhub-community' ) === false );
check( 'empty guest: no library block', strpos( $HTML, 'vhub-library' ) === false );
check( 'empty guest: no literal "[" placeholder text', strpos( $HTML, '[' ) === false );
check( 'empty guest: no unfilled %s placeholder', strpos( $HTML, '%s' ) === false );
check( 'empty guest: no zero recipe count, the generic copy is used', strpos( $HTML, 'Gut-friendly recipes. Build a weekly meal plan.' ) !== false && strpos( $HTML, '0 gut-friendly' ) === false );
check( 'empty guest: no survey question count when there are no steps', strpos( $HTML, 'vhub-survey__count' ) === false );
check( 'empty guest: the four tool tiles still render', substr_count( $HTML, '<li class="vhub-tool vhub-tool--' ) === 4 );
check( 'empty guest: the conditions block renders from the registry', strpos( $HTML, 'Start with your condition' ) !== false );
check( 'empty guest: the join CTA is shown', strpos( $HTML, 'id="vhub-join"' ) !== false && strpos( $HTML, 'https://example.test/login/?tab=signup' ) !== false );
check( 'empty guest: the member join card is not shown', strpos( $HTML, 'vhub-join--member' ) === false );
check( 'empty guest: every <img> has a non-empty alt', imgs_without_alt( $HTML ) === array() );
check( 'empty guest: at least one <img> is really rendered (the check is not vacuous)', substr_count( $HTML, '<img' ) >= 1 );

section( '10b. Rendered template, nothing saved, member' );

arrange( array(), false, array(), array() );
$GLOBALS['LOGGED_IN'] = true;
$HTML = render_home( true );
check( 'empty member: renders without a PHP error', $RENDER_ERROR === '' );
check( 'empty member: exactly one <h1>', substr_count( $HTML, '<h1' ) === 1 );
check( 'empty member: no member bar', strpos( $HTML, 'class="vhub-bar"' ) === false );
check( 'empty member: no "Inside the Hub" section', strpos( $HTML, 'vhub-inside' ) === false );
check( 'empty member: no community block', strpos( $HTML, 'vhub-community' ) === false );
check( 'empty member: no library block', strpos( $HTML, 'vhub-library' ) === false );
check( 'empty member: no literal "[" placeholder text', strpos( $HTML, '[' ) === false );
check( 'empty member: the member join card is shown with the My Hub link', strpos( $HTML, 'vhub-join--member' ) !== false && strpos( $HTML, 'https://example.test/dashboard/' ) !== false );
check( 'empty member: the guest sign-up link is not shown', strpos( $HTML, 'login/?tab=signup' ) === false && strpos( $HTML, 'id="vhub-join"' ) === false );
check( 'empty member: every <img> has a non-empty alt', imgs_without_alt( $HTML ) === array() );

/* ---- 11. the rendered template with real data ------------------------------- */
section( '11. Rendered template, real data, guest' );

arrange();
seed_full_home();
$HTML = render_home( false );
check( 'full guest: renders without a PHP error', $RENDER_ERROR === '' );
check( 'full guest: exactly one <h1>', substr_count( $HTML, '<h1' ) === 1 );
check( 'full guest: no literal "[" placeholder text', strpos( $HTML, '[' ) === false );
check( 'full guest: no unfilled %s placeholder', strpos( $HTML, '%s' ) === false );
check( 'full guest: the member bar shows the real total', strpos( $HTML, '<strong>1,234 members</strong>' ) !== false );
check( 'full guest: the member bar shows the real weekly joins', strpos( $HTML, '17 joined this week' ) !== false );
check( 'full guest: the bar links to the join section, not the dashboard', strpos( $HTML, 'href="#vhub-join"' ) !== false && strpos( $HTML, 'https://example.test/dashboard/' ) === false );
check( 'full guest: the survey tool shows the real step count', strpos( $HTML, 'Gastro Health Survey: 9 short questions.' ) !== false );
check( 'full guest: the survey starter shows the real question count', strpos( $HTML, '9 questions' ) !== false );
check( 'full guest: the recipe tool shows the live recipe count', strpos( $HTML, '1,091 gut-friendly recipes.' ) !== false );
check( 'full guest: the nutrition tool uses the calculator name', strpos( $HTML, 'IBD Malnutrition Calculator. ' ) !== false );
check( 'full guest: the member tiles render the titled tiles only, three of four', substr_count( $HTML, 'class="vhub-member-tile__title"' ) === 3 );
check( 'full guest: the untitled tile text is not rendered', strpos( $HTML, 'Orphan text' ) === false );
check( 'full guest: a tile title is escaped, so markup is text', strpos( $HTML, '&lt;b&gt;Bold&lt;/b&gt; tile' ) !== false && strpos( $HTML, '<b>Bold' ) === false );
check( 'full guest: a tile title with an ampersand is escaped once', strpos( $HTML, 'Live Q&amp;A' ) !== false );
check( 'full guest: a javascript: tile link is not rendered as a link', strpos( $HTML, 'javascript:' ) === false && strpos( $HTML, 'Bad link tile' ) !== false );
check( 'full guest: the community block shows the real quote with its role', strpos( $HTML, 'I finally slept through the night.' ) !== false && strpos( $HTML, '>Patient<' ) !== false );
check( 'full guest: a testimonial with blank content makes no quote card', substr_count( $HTML, 'vhub-community__card--quote' ) === 1 && strpos( $HTML, '>Blank<' ) === false );
check( 'full guest: recent recipes render with their meal category', strpos( $HTML, 'Porridge Bowl' ) !== false && strpos( $HTML, 'Breakfast' ) !== false );
check( 'full guest: a recipe with no category shows no meal label', substr_count( $HTML, 'vhub-recipe__meal' ) === 1 );
check( 'full guest: the library feature article renders', strpos( $HTML, 'Feature article' ) !== false );
check( 'full guest: the library shows at most four posts: one feature and three in the list', substr_count( $HTML, 'class="vhub-lib-item"' ) === 3 );
check( 'full guest: the fifth library post is dropped', strpos( $HTML, 'Fifth article' ) === false );
check( 'full guest: a library category name is decoded once and escaped once', strpos( $HTML, 'Gastro Living &amp; More' ) !== false );
check( 'full guest: the conditions copy counts the seven registry entries', strpos( $HTML, 'Plain-English guides to 7 gut conditions.' ) !== false );
check( 'full guest: three featured conditions are photo tiles', substr_count( $HTML, 'class="vhub-cond-tile"' ) === 3 );
check( 'full guest: the other four conditions are in the plain list', substr_count( $HTML, 'class="vhub-cond-list__link"' ) === 4 );
check( 'full guest: the join copy shows the Founding Member deadline', strpos( $HTML, 'Join by 1 December 2026 and keep the Founding Member mark.' ) !== false );
check( 'full guest: the join CTA is shown', strpos( $HTML, 'id="vhub-join"' ) !== false && strpos( $HTML, 'https://example.test/login/?tab=signup' ) !== false );
check( 'full guest: every <img> has a non-empty alt', imgs_without_alt( $HTML ) === array() );
check( 'full guest: at least one <img> is really rendered (the check is not vacuous)', substr_count( $HTML, '<img' ) >= 1 );

section( '11b. Rendered template, real data, member' );

arrange();
seed_full_home();
$GLOBALS['LOGGED_IN'] = true;
$HTML = render_home( true );
check( 'full member: renders without a PHP error', $RENDER_ERROR === '' );
check( 'full member: exactly one <h1>', substr_count( $HTML, '<h1' ) === 1 );
check( 'full member: the member bar shows the real total', strpos( $HTML, '1,234 members' ) !== false );
check( 'full member: the bar links to the dashboard', strpos( $HTML, 'href="https://example.test/dashboard/"' ) !== false );
check( 'full member: the member join card is shown, not the guest one', strpos( $HTML, 'vhub-join--member' ) !== false && strpos( $HTML, 'id="vhub-join"' ) === false );
check( 'full member: no sign-up link anywhere', strpos( $HTML, 'login/?tab=signup' ) === false );
check( 'full member: no Founding Member line once the counts show', strpos( $HTML, 'Join by' ) === false );
check( 'full member: every <img> has a non-empty alt', imgs_without_alt( $HTML ) === array() );

/* ---- 12. boundaries, unsafe and failing inputs ---------------------------- */
section( '12. Rendered template, boundaries and failure paths' );

arrange();
$GLOBALS['USERS_TOTAL'] = 3;
$GLOBALS['MODS']['vance_hub_founding_deadline'] = '2026-12-01';
$HTML = render_home( false );
check( 'below threshold, guest, deadline open: the bar is the Founding line', strpos( $HTML, '<p class="vhub-bar__text">Join by 1 December 2026 and keep the Founding Member mark.' ) !== false );
check( 'below threshold: the real total is not shown as a figure', strpos( $HTML, '3 members' ) === false );

arrange();
$GLOBALS['USERS_TOTAL'] = 3;
$GLOBALS['MODS']['vance_hub_founding_deadline'] = '2026-12-01';
$HTML = render_home( true );
check( 'below threshold, member, deadline open: no bar and no Founding line', strpos( $HTML, 'class="vhub-bar"' ) === false && strpos( $HTML, 'Join by' ) === false );

arrange();
$GLOBALS['USERS_TOTAL'] = 3;
$HTML = render_home( false );
check( 'below threshold, no deadline: no bar at all', strpos( $HTML, 'class="vhub-bar"' ) === false );

arrange();
$GLOBALS['USERS_TOTAL'] = 99;
$HTML = render_home( false );
check( 'member total one below the default threshold of 100: no counts bar', strpos( $HTML, 'class="vhub-bar"' ) === false );

arrange();
$GLOBALS['USERS_TOTAL'] = 100;
$HTML = render_home( false );
check( 'member total at the default threshold of 100: counts bar shows', strpos( $HTML, '<strong>100 members</strong>' ) !== false );

arrange();
$GLOBALS['USERS_TOTAL'] = 1234;
$GLOBALS['USERS_WEEK']  = 0;
$HTML = render_home( false );
check( 'zero joined this week: the total shows and the weekly span does not', strpos( $HTML, '1,234 members' ) !== false && strpos( $HTML, 'joined this week' ) === false );

arrange( array( 'vance_testimonial_inline_1_quote' => 'Inline quote text', 'vance_testimonial_inline_1_name' => 'Sam', 'vance_testimonial_inline_1_role' => 'Carer' ) );
$HTML = render_home( false );
check( 'community: with no testimonial posts, the Customizer quote is used with its name and role',
	strpos( $HTML, 'Inline quote text' ) !== false && strpos( $HTML, '>Sam<' ) !== false && strpos( $HTML, '>Carer<' ) !== false );

arrange( array( 'vance_show_testimonials' => false ) );
seed_full_home();
$GLOBALS['MODS']['vance_show_testimonials'] = false;
$HTML = render_home( false );
check( 'community: the classic switch off hides the quotes, even with posts', strpos( $HTML, 'I finally slept' ) === false && strpos( $HTML, 'vhub-community' ) === false );

arrange();
$GLOBALS['QUERY_POSTS']['testimonial'] = array( post_obj( 502, 'Blank', '   ' ) );
$HTML = render_home( false );
check( 'community: a block with only blank testimonials is omitted entirely', strpos( $HTML, 'vhub-community' ) === false );

arrange();
seed_full_home();
unset( $GLOBALS['META'][9001] );   // the thumbnail stays; its alt text is removed
$HTML = render_home( false );
// The featured library image has a thumbnail but no alt text. The data rule
// says every <img> carries a real, non-empty alt, so the template leaves the
// photo out and the headline alone names the link.
check( 'library: a featured image with no alt text is not rendered with alt=""', imgs_without_alt( $HTML ) === array() );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
