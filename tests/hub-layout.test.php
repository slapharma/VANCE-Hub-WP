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
 *   15 the promo block in front-page-hub.php, and the two Customizer switches
 *      that the promo block and the member dashboard read
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
	$GLOBALS['CAT_TERMS']      = array();
	$GLOBALS['WP_QUERY_LOG']   = array();
	$GLOBALS['THUMB_PRIMES']   = 0;
	$GLOBALS['POSTS_BY_ID']    = array();
	$GLOBALS['POST_DATES']     = array();
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
/*
 * WP_Query: posts come from QUERY_POSTS[post_type], or QUERY_POSTS['cat:<id>']
 * when the query names a category (newest first, as listed). post__not_in is
 * honoured as WordPress honours it, so a dedupe that relies on it is tested.
 * Every query's arguments are logged.
 */
class WP_Query {
	public $posts = array();
	public function __construct( $args ) {
		$GLOBALS['WP_QUERY_LOG'][] = $args;
		$key = isset( $args['cat'] ) ? 'cat:' . $args['cat'] : $args['post_type'];
		// vance_hub_popular_recipes() asks for recipes ordered by view count; the
		// "Recently Added" and "Most Popular" groups must not read the same list.
		if ( isset( $args['meta_query']['views'] ) ) { $key .= ':popular'; }
		$all = isset( $GLOBALS['QUERY_POSTS'][ $key ] ) ? $GLOBALS['QUERY_POSTS'][ $key ] : array();
		if ( ! empty( $args['post__not_in'] ) ) {
			$skip = $args['post__not_in'];
			$all  = array_values( array_filter( $all, function ( $p ) use ( $skip ) { return ! in_array( $p->ID, $skip ); } ) );
		}
		$this->posts = array_slice( $all, 0, (int) $args['posts_per_page'] );
	}
}
function post_obj( $id, $title, $content = '' ) {
	$post = (object) array( 'ID' => $id, 'post_title' => $title, 'post_content' => $content, 'post_type' => 'post' );
	$GLOBALS['POSTS_BY_ID'][ $id ] = $post;
	return $post;
}
function get_post_field( $field, $post_id ) {
	return isset( $GLOBALS['POSTS_BY_ID'][ $post_id ] ) ? $GLOBALS['POSTS_BY_ID'][ $post_id ]->$field : '';
}
function strip_shortcodes( $text ) { return $text; }
function get_the_date( $format = '', $post = null ) {
	$ts = isset( $GLOBALS['POST_DATES'][ $post->ID ] ) ? $GLOBALS['POST_DATES'][ $post->ID ] : strtotime( '2026-11-10 09:00:00 UTC' );
	return gmdate( '' === $format ? 'j F Y' : $format, $ts );
}
function get_term_by( $field, $value, $taxonomy ) {
	return ( 'slug' === $field && 'category' === $taxonomy && isset( $GLOBALS['CAT_TERMS'][ $value ] ) ) ? $GLOBALS['CAT_TERMS'][ $value ] : false;
}
function get_term_link( $term ) { return 'https://example.test/category/' . $term->slug . '/'; }
function cat_term( $id, $slug, $name ) {
	return (object) array( 'term_id' => $id, 'slug' => $slug, 'name' => $name );
}
function get_the_title( $post ) { return $post->post_title; }
function get_post_meta( $post_id, $key = '', $single = false ) {
	return isset( $GLOBALS['META'][ $post_id ][ $key ] ) ? $GLOBALS['META'][ $post_id ][ $key ] : '';
}
// The tool card slots (vance_hub_home_tools): the URL of a chosen attachment, or false.
function wp_get_attachment_image_url( $id, $size = 'thumbnail' ) {
	return isset( $GLOBALS['ATTACH_URL'][ $id ] ) ? $GLOBALS['ATTACH_URL'][ $id ] : false;
}
function get_post_thumbnail_id( $post_id ) {
	return isset( $GLOBALS['THUMB_ID'][ $post_id ] ) ? $GLOBALS['THUMB_ID'][ $post_id ] : 0;
}
function update_post_thumbnail_cache( $query ) { $GLOBALS['THUMB_PRIMES']++; }
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
// front-page-hub.php calls vance_dashboard_link(): load the real helper and its one missing stub.
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
require_once $THEME . '/inc/dashboard-url.php';
require_once $THEME . '/inc/condition-priority.php';
require_once $THEME . '/inc/hub-layout.php';
require_once $THEME . '/inc/hub-home-settings.php';
require_once $THEME . '/inc/hub-look-settings.php';

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

// The real reading-time helper, lifted the same way, so the cards are tested
// against the theme's own word-count rule rather than a copy.
preg_match( '/function vance_get_read_time\( \$post_id \) \{.*?\r?\n\}\r?\n/s', $SRC, $LIFT_RT );
if ( empty( $LIFT_RT[0] ) ) {
	fwrite( STDERR, "could not lift vance_get_read_time() from functions.php\n" );
	exit( 2 );
}
eval( $LIFT_RT[0] );

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
	$html = ob_get_clean();
	// When VHH_DUMP_DIR is set, keep every rendered page as home-<n>.html so two
	// runs (before and after a change) can be compared byte for byte.
	$dump_dir = getenv( 'VHH_DUMP_DIR' );
	if ( false !== $dump_dir && '' !== $dump_dir ) {
		static $dump_n = 0;
		$dump_n++;
		file_put_contents( rtrim( $dump_dir, '/\\' ) . '/home-' . $dump_n . '.html', $html );
	}
	return $html;
}

/**
 * Every <img> tag whose alt attribute is missing or empty.
 *
 * Article thumbnails follow the theme's convention (inc/thumbnail-alt.php):
 * the attachment's description, or alt="" because the headline in the same
 * link names it. Pass $article = false to check only the other images (hero,
 * tools, conditions), which must always carry real alt text.
 */
function imgs_without_alt( $html, $article = true ) {
	preg_match_all( '/<img\b(?![^>]*\salt="[^"]+")[^>]*>/', $html, $m );
	if ( $article ) {
		return $m[0];
	}
	return array_values( array_filter( $m[0], function ( $tag ) {
		return ! preg_match( '/class="vhub-(feat|sec-card|card)__img"/', $tag );
	} ) );
}

/** The HTML of the first <section> whose class list contains $class, or ''. */
function section_html( $html, $class ) {
	if ( ! preg_match( '/<section class="[^"]*\b' . preg_quote( $class, '/' ) . '\b[^"]*".*?<\/section>/s', $html, $m ) ) {
		return '';
	}
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
	$GLOBALS['QUERY_POSTS']['vance_recipe:popular'] = array( post_obj( 603, 'Oat Pot' ) );
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
check( 'empty guest: no zero recipe count, the generic copy is used', strpos( $HTML, 'Choose from gut-friendly recipes and leave with a weekly meal plan.' ) !== false && strpos( $HTML, '0 gut-friendly' ) === false );
check( 'empty guest: no survey question count when there are no steps', strpos( $HTML, 'vhub-survey__count' ) === false );
check( 'empty guest: the four tool tiles still render', substr_count( $HTML, '<li class="vhub-tool vhub-tool--' ) === 4 );
check( 'empty guest: the conditions block renders from the registry', strpos( $HTML, 'Recently diagnosed? Learn about your condition' ) !== false );
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
check( 'full guest: the survey tool shows the real step count', strpos( $HTML, 'Answer 9 short questions and get guides, tools and recipes picked for you.' ) !== false );
check( 'full guest: the survey starter shows the real question count', strpos( $HTML, '9 questions' ) !== false );
check( 'full guest: the recipe tool shows the live recipe count', strpos( $HTML, 'Choose from 1,091 gut-friendly recipes and leave with a weekly meal plan.' ) !== false );
check( 'full guest: the nutrition tool describes the malnutrition risk check', strpos( $HTML, 'A short check that shows your malnutrition risk, so you know what to raise with your care team.' ) !== false );
check( 'full guest: the Most Popular group lists the popular recipe, not the recent ones again', strpos( $HTML, 'Most Popular Recipes' ) !== false && strpos( $HTML, 'Oat Pot' ) !== false );
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
check( 'full guest: the fifth post is not in the library block', section_html( $HTML, 'vhub-library' ) !== '' && strpos( section_html( $HTML, 'vhub-library' ), 'Fifth article' ) === false );
check( 'full guest: the fifth post goes to "More to read" instead', strpos( section_html( $HTML, 'vhub-more' ), 'Fifth article' ) !== false );
check( 'full guest: the featured image keeps its own description as alt', strpos( $HTML, 'alt="A featured photo for the library"' ) !== false );
check( 'full guest: a library category name is decoded once and escaped once', strpos( $HTML, 'Gastro Living &amp; More' ) !== false );
check( 'full guest: the conditions copy counts the seven registry entries', strpos( $HTML, 'Easy-to-follow guides to 7 common gut conditions.' ) !== false );
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
// The featured library image has a thumbnail but no alt text. The theme's
// convention (inc/thumbnail-alt.php) applies: the photo still renders, with
// alt="", because the headline inside the same link names it.
preg_match( '/<a class="vhub-feat"[^>]*>.*?<\/a>/s', $HTML, $FEAT );
$FEAT = isset( $FEAT[0] ) ? $FEAT[0] : '';
check( 'library: a featured image with no alt text is still rendered', strpos( $FEAT, '<img class="vhub-feat__img" src="https://example.test/wp-content/uploads/feature.jpg"' ) !== false );
check( 'library: that image carries an alt attribute, and it is empty', preg_match( '/<img class="vhub-feat__img"[^>]*\salt=""/', $FEAT ) === 1 );
check( 'library: the featured link still has an accessible name from its heading', preg_match( '/<h3 class="vhub-feat__title">Feature article<\/h3>/', $FEAT ) === 1 );
check( 'library: every image that is not an article thumbnail still has a non-empty alt', imgs_without_alt( $HTML, false ) === array() );
check( 'library: the non-article alt check is not vacuous (hero, tools and conditions images render)', preg_match_all( '/<img class="vhub-(hero|tool|cond-tile)__img"/', $HTML ) >= 5 );
// Negative control: the non-article filter must still catch a missing alt.
check( 'library: control: a tool image with an empty alt would be reported', imgs_without_alt( '<img class="vhub-tool__img" src="x" alt="">', false ) !== array() );

arrange();
seed_full_home();
$GLOBALS['THUMB_URL'][701] = false;   // no thumbnail at all
$HTML = render_home( false );
check( 'library: a featured post with no thumbnail renders no <img> in the card', strpos( $HTML, 'vhub-feat__img' ) === false && strpos( $HTML, 'Feature article' ) !== false );

/* ---- 13. checkbox, hero colours, body class cleanup, section slugs -------- */
section( '13. Small helpers' );

check( 'checkbox: true stays true', vance_hub_sanitize_checkbox( true ) === true );
check( 'checkbox: false stays false', vance_hub_sanitize_checkbox( false ) === false );
check( 'checkbox: "1" (what the Customizer posts) is true', vance_hub_sanitize_checkbox( '1' ) === true );
check( 'checkbox: "" (an unticked box) is false', vance_hub_sanitize_checkbox( '' ) === false );
check( 'checkbox: integer 1 is true, 0 is false', vance_hub_sanitize_checkbox( 1 ) === true && vance_hub_sanitize_checkbox( 0 ) === false );
check( 'checkbox: "on" and " TRUE " are true', vance_hub_sanitize_checkbox( 'on' ) === true && vance_hub_sanitize_checkbox( ' TRUE ' ) === true );
check( 'checkbox: "0", "no" and junk are false', vance_hub_sanitize_checkbox( '0' ) === false && vance_hub_sanitize_checkbox( 'no' ) === false && vance_hub_sanitize_checkbox( 'yes please' ) === false );
check( 'checkbox: null and an array are false', vance_hub_sanitize_checkbox( null ) === false && vance_hub_sanitize_checkbox( array( '1' ) ) === false );

check( 'hero style: the classic defaults', vance_hub_hero_style( '#ECF5F5', '#F6F9FA' ) === '--vhh-hs-from: #ECF5F5; --vhh-hs-to: #F6F9FA;' );
check( 'hero style: an owner colour is passed through', vance_hub_hero_style( '#abc', '#112233' ) === '--vhh-hs-from: #abc; --vhh-hs-to: #112233;' );
check( 'hero style: a CSS injection falls back to the default', vance_hub_hero_style( 'red;}body{display:none', '#F6F9FA' ) === '--vhh-hs-from: #ECF5F5; --vhh-hs-to: #F6F9FA;' );
check( 'hero style: a non-string falls back to the default', vance_hub_hero_style( array(), null ) === '--vhh-hs-from: #ECF5F5; --vhh-hs-to: #F6F9FA;' );
check( 'hero style: a hex with no # is not accepted', vance_hub_hero_style( 'ECF5F5', '#F6F9FA' ) === '--vhh-hs-from: #ECF5F5; --vhh-hs-to: #F6F9FA;' );

arrange( array( 'vance_hero_spotlight_bg_from' => '#FFEEDD', 'vance_hero_spotlight_bg_to' => '"><script>' ) );
$HTML = render_home( false );
check( 'hero: the hub hero reads the classic hero colour mods', strpos( $HTML, '<section class="vhub-hero" style="--vhh-hs-from: #FFEEDD; --vhh-hs-to: #F6F9FA;"' ) !== false );
check( 'hero: a bad colour mod does not reach the markup', strpos( $HTML, '<script>' ) === false );

check( 'hook: body_class runs vance_hub_body_class_cleanup at priority 20, after the theme',
	isset( $GLOBALS['HOOKS']['body_class'][20] ) && in_array( 'vance_hub_body_class_cleanup', $GLOBALS['HOOKS']['body_class'][20], true ) );
arrange( array( 'vance_home_layout' => 'hub' ), true );
check( 'body class: the hub home drops has-vance-swipecards and keeps the rest',
	vance_hub_body_class_cleanup( array( 'home', 'has-vance-bottom-nav', 'has-vance-swipecards' ) ) === array( 'home', 'has-vance-bottom-nav' ) );
arrange( array( 'vance_home_layout' => 'classic' ), true );
check( 'body class: the classic home keeps has-vance-swipecards',
	vance_hub_body_class_cleanup( array( 'home', 'has-vance-swipecards' ) ) === array( 'home', 'has-vance-swipecards' ) );

check( 'slugs: the three content sections, in order', vance_hub_section_slugs() === array( 'content-gastro-living', 'content-health-news', 'content-clinical-reviews' ) );
$GLOBALS['FILTER_RETURNS'] = array( 'vance_hub_section_slugs' => 'nope' );
check( 'slugs: a filter returning a non-array gives no sections', vance_hub_section_slugs() === array() );
$GLOBALS['FILTER_RETURNS'] = array( 'vance_hub_section_slugs' => array( 'a', 7, 'a', 'b' ) );
check( 'slugs: non-strings and repeats from a filter are dropped', vance_hub_section_slugs() === array( 'a', 'b' ) );
$GLOBALS['FILTER_RETURNS'] = array();

/* ---- 14. Part B: Latest by section and More to read ----------------------- */
section( '14. Latest by section, More to read' );

/**
 * Library 701-704. Gastro Living (term 11) re-lists 701 and has four more;
 * Health News (term 12) re-lists 721, which Gastro Living already shows.
 * Clinical Reviews has no term on this site. The recent list holds twelve.
 */
function seed_article_blocks() {
	seed_full_home();
	$GLOBALS['CAT_TERMS'] = array(
		'content-gastro-living' => cat_term( 11, 'content-gastro-living', 'Gastro Living' ),
		'content-health-news'   => cat_term( 12, 'content-health-news', 'Health News &amp; Notes' ),
	);
	$words = trim( str_repeat( 'word ', 401 ) );   // 401 words: 3 minutes at 200 wpm
	$GLOBALS['QUERY_POSTS']['cat:11'] = array(
		$GLOBALS['QUERY_POSTS']['post'][0],
		post_obj( 721, 'Gut walk <script>alert(1)</script> & more', $words ),
		post_obj( 722, 'Gastro two' ),
		post_obj( 723, 'Gastro three' ),
		post_obj( 724, 'Gastro four' ),
	);
	$GLOBALS['QUERY_POSTS']['cat:12'] = array(
		$GLOBALS['POSTS_BY_ID'][721],
		post_obj( 731, 'News one' ),
		post_obj( 732, 'News two' ),
	);
	$GLOBALS['META'][731]['_oped_read_time'] = 7;
	$GLOBALS['POST_DATES'][731] = strtotime( '2026-11-03 08:00:00 UTC' );
	$GLOBALS['THUMB_URL'][722] = 'https://example.test/wp-content/uploads/g2.jpg';   // no alt text on purpose
	$GLOBALS['THUMB_URL'][706] = 'https://example.test/wp-content/uploads/m6.jpg';
	$GLOBALS['THUMB_ID'][706]  = 9006;
	$GLOBALS['META'][9006]['_wp_attachment_image_alt'] = 'A bowl of soup';
	$GLOBALS['CATS_BY_POST'][706] = array( (object) array( 'slug' => 'food', 'name' => 'Food &amp; Drink' ) );
	$recent = array_slice( $GLOBALS['QUERY_POSTS']['post'], 0, 4 );
	$recent[] = $GLOBALS['POSTS_BY_ID'][721];
	$recent[] = $GLOBALS['POSTS_BY_ID'][731];
	foreach ( array( 705, 706, 707, 708, 709, 710, 711 ) as $id ) {
		$recent[] = isset( $GLOBALS['POSTS_BY_ID'][ $id ] ) ? $GLOBALS['POSTS_BY_ID'][ $id ] : post_obj( $id, 'Recent ' . $id );
	}
	$GLOBALS['QUERY_POSTS']['post'] = $recent;
}

arrange();
seed_article_blocks();
$HTML = render_home( false );
$SECS = section_html( $HTML, 'vhub-secs' );
$MORE = section_html( $HTML, 'vhub-more' );
check( 'blocks: render without a PHP error', $RENDER_ERROR === '' );
check( 'blocks: "Latest by section" renders', $SECS !== '' && strpos( $SECS, 'Latest by section' ) !== false );
check( 'blocks: "More to read" renders', $MORE !== '' && strpos( $MORE, 'More to read' ) !== false );
check( 'blocks: both sit after the library and before the join band',
	strpos( $HTML, 'vhub-library' ) < strpos( $HTML, 'vhub-secs' ) && strpos( $HTML, 'vhub-secs' ) < strpos( $HTML, 'vhub-more' ) && strpos( $HTML, 'vhub-more' ) < strpos( $HTML, 'id="vhub-join"' ) );
check( 'sections: two columns, for the two sections that exist', substr_count( $SECS, '<div class="vhub-sec">' ) === 2 );
check( 'sections: a section whose term is missing is skipped (no Clinical Reviews)', strpos( $SECS, 'clinical' ) === false && strpos( $SECS, 'Clinical' ) === false );
check( 'sections: the column heading links to the section archive', strpos( $SECS, 'href="https://example.test/category/content-gastro-living/"' ) !== false );
check( 'sections: a term name is decoded once and escaped once', strpos( $SECS, 'Health News &amp; Notes' ) !== false && strpos( $SECS, '&amp;amp;' ) === false );
preg_match_all( '/<div class="vhub-sec">.*?<\/ul>/s', $SECS, $COLS );
check( 'sections: Gastro Living is capped at three posts', isset( $COLS[0][0] ) && substr_count( $COLS[0][0], 'class="vhub-sec-card"' ) === 3 );
check( 'sections: Gastro Living skips the library post and keeps newest first',
	isset( $COLS[0][0] ) && strpos( $COLS[0][0], '/p/701/' ) === false && strpos( $COLS[0][0], '/p/721/' ) < strpos( $COLS[0][0], '/p/723/' ) && strpos( $COLS[0][0], '/p/724/' ) === false );
check( 'sections: Health News skips the post Gastro Living already shows', isset( $COLS[0][1] ) && strpos( $COLS[0][1], '/p/721/' ) === false && substr_count( $COLS[0][1], 'class="vhub-sec-card"' ) === 2 );
check( 'sections: a title is escaped, so markup is text', strpos( $HTML, 'Gut walk &lt;script&gt;alert(1)&lt;/script&gt; &amp; more' ) !== false && strpos( $HTML, '<script>alert(1)' ) === false );
check( 'sections: reading time comes from the word count (401 words is 3 min)', isset( $COLS[0][0] ) && strpos( $COLS[0][0], '3 min read' ) !== false );
check( 'sections: a set _oped_read_time wins over the word count', isset( $COLS[0][1] ) && strpos( $COLS[0][1], '7 min read' ) !== false );
check( 'sections: the date is real and machine-readable', isset( $COLS[0][1] ) && strpos( $COLS[0][1], '<time datetime="2026-11-03T08:00:00+00:00">3 November 2026</time>' ) !== false );
check( 'sections: no view count anywhere (that helper returns a random number)', stripos( $HTML, 'views' ) === false );
check( 'sections: a thumbnail with no description renders with alt=""', preg_match( '/<img class="vhub-sec-card__img" src="https:\/\/example.test\/wp-content\/uploads\/g2.jpg" alt=""/', $SECS ) === 1 );
check( 'sections: a post with no thumbnail renders no <img>', substr_count( $SECS, '<img' ) === 1 );
check( 'more: capped at six', substr_count( $MORE, 'class="vhub-card"' ) === 6 );
check( 'more: the seventh candidate is dropped', strpos( $MORE, '/p/711/' ) === false && strpos( $MORE, '/p/710/' ) !== false );
check( 'more: a real category label is shown, decoded once', strpos( $MORE, '<span class="vhub-tag vhub-tag--purple">Food &amp; Drink</span>' ) !== false );
check( 'more: a post with no category shows no label', substr_count( $MORE, '<span class="vhub-tag ' ) === 1 );
check( 'more: the thumbnail keeps its own description', strpos( $MORE, 'alt="A bowl of soup"' ) !== false );
// Dedupe across the featured card, the library list and both new blocks.
preg_match_all( '#href="https://example\.test/p/(\d+)/"#', $HTML, $LINKS );
$COUNTS_BY_ID = array_count_values( $LINKS[1] );
check( 'dedupe: no article is linked twice on the page', ! empty( $COUNTS_BY_ID ) && max( $COUNTS_BY_ID ) === 1 );
// Recipes (601, 602) share the /p/<id>/ stub permalink; articles are 700 and up.
$ARTICLE_IDS = array_filter( array_keys( $COUNTS_BY_ID ), function ( $id ) { return (int) $id >= 700; } );
check( 'dedupe: the check is not vacuous (4 + 3 + 2 + 6 = 15 distinct articles are linked)', count( $ARTICLE_IDS ) === 15 );
check( 'dedupe: control: a page with a repeated link would fail the check', max( array_count_values( array( '1', '2', '1' ) ) ) === 2 );
// The queries stay light: this is the home page.
$POST_QUERIES = array_values( array_filter( $GLOBALS['WP_QUERY_LOG'], function ( $a ) { return 'post' === $a['post_type']; } ) );
check( 'queries: four article queries run (library, two sections, more)', count( $POST_QUERIES ) === 4 );
check( 'queries: every article query skips the row count and sticky posts', count( array_filter( $POST_QUERIES, function ( $a ) {
	return ! empty( $a['no_found_rows'] ) && ! empty( $a['ignore_sticky_posts'] ) && $a['posts_per_page'] <= 6;
} ) ) === 4 );
check( 'queries: each article query primes its featured images in one go', $GLOBALS['THUMB_PRIMES'] === 4 );
check( 'queries: the second section query excludes what the first one used', isset( $POST_QUERIES[2]['post__not_in'] ) && in_array( 721, $POST_QUERIES[2]['post__not_in'], true ) );
check( 'blocks: every non-article image still has a non-empty alt', imgs_without_alt( $HTML, false ) === array() );

arrange();
seed_article_blocks();
$GLOBALS['MODS']['vance_hub_show_sections'] = '';
$HTML = render_home( false );
check( 'toggle: "Latest by section" off hides the block', strpos( $HTML, 'vhub-secs' ) === false );
check( 'toggle: with it off, no section query runs', count( array_filter( $GLOBALS['WP_QUERY_LOG'], function ( $a ) { return isset( $a['cat'] ); } ) ) === 0 );
check( 'toggle: with it off, "More to read" picks up the next posts instead', strpos( section_html( $HTML, 'vhub-more' ), '/p/721/' ) !== false );

arrange();
seed_article_blocks();
$GLOBALS['MODS']['vance_hub_show_more'] = false;
$HTML = render_home( false );
check( 'toggle: "More to read" off hides the block', strpos( $HTML, 'vhub-more' ) === false && strpos( $HTML, 'vhub-secs' ) !== false );

arrange();
seed_full_home();
$GLOBALS['CAT_TERMS'] = array( 'content-gastro-living' => cat_term( 11, 'content-gastro-living', 'Gastro Living' ) );
$GLOBALS['QUERY_POSTS']['post'] = array_slice( $GLOBALS['QUERY_POSTS']['post'], 0, 4 );
$HTML = render_home( false );
check( 'empty: a section term with no posts is skipped, and with no column the block is omitted', strpos( $HTML, 'vhub-secs' ) === false );
check( 'empty: with no posts beyond the library, "More to read" is omitted', strpos( $HTML, 'vhub-more' ) === false );

arrange();
$HTML = render_home( false );
check( 'empty: nothing saved, neither block renders', strpos( $HTML, 'vhub-secs' ) === false && strpos( $HTML, 'vhub-more' ) === false );

/* ---- 15. the promo block and the Customizer switches ----------------------- */

// Stand-in for inc/promo-block.php: counts each call and prints PROMO_HTML.
// Both globals default to nothing, so sections 10-12 render no promo at all.
function vance_render_promo_home() {
	$GLOBALS['PROMO_CALLS'] = ( $GLOBALS['PROMO_CALLS'] ?? 0 ) + 1;
	echo $GLOBALS['PROMO_HTML'] ?? '';
}

// Stand-in for the Customizer manager: records what the register function asks for.
class HubCustomizeFake {
	public $panels   = array();
	public $sections = array();
	public $settings = array();
	public $controls = array();
	public function add_panel( $id, $args = array() ) { $this->panels[ $id ] = $args; }
	public function add_section( $id, $args = array() ) { $this->sections[ $id ] = $args; }
	public function add_setting( $id, $args = array() ) { $this->settings[ $id ] = $args; }
	public function add_control( $id, $args = array() ) { $this->controls[ $id ] = $args; }
}

section( '15. The promo block on the Hub home' );

$PROMO = '<p class="promo-body">PROMO-BODY</p>';

arrange();
$GLOBALS['PROMO_HTML']  = $PROMO;
$GLOBALS['PROMO_CALLS'] = 0;
$HTML = render_home( false );
check( 'promo: shown by default, with the switch unset (guest)', strpos( $HTML, 'class="vhub-promo"' ) !== false && strpos( $HTML, 'PROMO-BODY' ) !== false );
check( 'promo: the block is rendered exactly once per page view', $GLOBALS['PROMO_CALLS'] === 1 );

arrange();
$GLOBALS['PROMO_HTML']  = $PROMO;
$GLOBALS['PROMO_CALLS'] = 0;
$HTML = render_home( true );
check( 'promo: shown to a member too', strpos( $HTML, 'class="vhub-promo"' ) !== false && strpos( $HTML, 'PROMO-BODY' ) !== false );

arrange( array( 'vance_hub_show_promo' => false ) );
$GLOBALS['PROMO_HTML']  = $PROMO;
$GLOBALS['PROMO_CALLS'] = 0;
$HTML = render_home( false );
check( 'promo: switched off, the wrapper is absent', strpos( $HTML, 'vhub-promo' ) === false );
check( 'promo: switched off, the block is not called at all', $GLOBALS['PROMO_CALLS'] === 0 && strpos( $HTML, 'PROMO-BODY' ) === false );

arrange( array( 'vance_hub_show_promo' => '0' ) );
$GLOBALS['PROMO_HTML'] = $PROMO;
$HTML = render_home( false );
check( 'promo: a saved "0" switches it off after the checkbox sanitiser', strpos( $HTML, 'vhub-promo' ) === false );

// "0" is falsy in PHP, so it would pass even with the sanitiser removed. "off" is
// truthy as a string and only the sanitiser turns it off.
arrange( array( 'vance_hub_show_promo' => 'off' ) );
$GLOBALS['PROMO_HTML'] = $PROMO;
$HTML = render_home( false );
check( 'promo: a saved "off" switches it off, which needs the checkbox sanitiser', strpos( $HTML, 'vhub-promo' ) === false );

arrange( array( 'vance_hub_show_promo' => '1' ) );
$GLOBALS['PROMO_HTML'] = $PROMO;
$HTML = render_home( false );
check( 'promo: a saved "1" switches it on', strpos( $HTML, 'PROMO-BODY' ) !== false );

arrange();
$GLOBALS['PROMO_HTML'] = '';
$HTML = render_home( false );
check( 'promo: an empty block prints no wrapper', strpos( $HTML, 'vhub-promo' ) === false );

arrange();
$GLOBALS['PROMO_HTML'] = " \n\t ";
$HTML = render_home( false );
check( 'promo: a block that is only whitespace prints no wrapper', strpos( $HTML, 'vhub-promo' ) === false );

section( '15a. Block order, texts and counts on the Hub home' );

arrange( array( 'vance_hub_block_order' => 'join,tools' ) );
$HTML = render_home( false );
check( 'order: a saved order prints the Join block before the tools', strpos( $HTML, 'id="vhub-join"' ) !== false && strpos( $HTML, 'id="vhub-join"' ) < strpos( $HTML, 'vhub-tools-section' ) );
check( 'order: a block that is not in the saved order does not print', strpos( $HTML, 'vhub-library' ) === false && strpos( $HTML, 'vhub-conditions' ) === false );
check( 'order: the hero and the facts print first, whatever the order', strpos( $HTML, 'vhub-hero' ) < strpos( $HTML, 'vhub-facts' ) && strpos( $HTML, 'vhub-facts' ) < strpos( $HTML, 'id="vhub-join"' ) );
check( 'order: with the Join block on, the member bar link is the #vhub-join anchor', strpos( $HTML, 'href="#vhub-join"' ) !== false || strpos( $HTML, 'vhub-bar' ) === false );

arrange( array( 'vance_hub_block_order' => '' ) );
$HTML = render_home( false );
check( 'order: a saved empty order prints no movable block, only the hero and the facts', strpos( $HTML, 'vhub-hero' ) !== false && strpos( $HTML, 'vhub-facts' ) !== false && strpos( $HTML, 'vhub-tools' ) === false && strpos( $HTML, 'vhub-join' ) === false );

arrange( array( 'vance_hub_text_hero_title' => 'Ask <b>us</b>', 'vance_hub_text_hero_accent' => '  ', 'vance_hub_text_fact_2_title' => 'Saved', 'vance_hub_text_member_title' => 'Welcome back' ) );
$HTML = render_home( false );
check( 'text: a saved hero title is printed, escaped', strpos( $HTML, 'Ask &lt;b&gt;us&lt;/b&gt; <span class="vhub-hero__accent">' ) !== false && strpos( $HTML, '<b>us</b>' ) === false );
check( 'text: an empty accent prints the default inside the accent span', strpos( $HTML, '<span class="vhub-hero__accent">today?</span>' ) !== false );
check( 'text: a saved fact title keeps the strong and span markup', strpos( $HTML, '<strong>Saved</strong><span>Save searches, answers and results.</span>' ) !== false );
check( 'text: the survey note is fixed text', strpos( $HTML, 'You can change any answer later. This is not a diagnosis.' ) !== false );
$HTML = render_home( true );
check( 'text: a member sees the saved member title', strpos( $HTML, '>Welcome back</h2>' ) !== false );

section( '15b. The promo and dashboard switches in the Customizer' );

$WC = new HubCustomizeFake();
vance_hub_customize_register( $WC );
check( 'customize: vance_hub_show_promo is registered as a setting', isset( $WC->settings['vance_hub_show_promo'] ) );
check( 'customize: vance_hub_show_promo defaults to on', $WC->settings['vance_hub_show_promo']['default'] === true );
check( 'customize: vance_hub_show_promo is sanitised as a checkbox', $WC->settings['vance_hub_show_promo']['sanitize_callback'] === 'vance_hub_sanitize_checkbox' );
check( 'customize: the promo switch has no control any more (the block order replaced it); the setting stays', ! isset( $WC->controls['vance_hub_show_promo'] ) && isset( $WC->settings['vance_hub_show_promo'] ) );
check( 'customize: vance_hub_show_dashboard is registered as a setting', isset( $WC->settings['vance_hub_show_dashboard'] ) );
check( 'customize: vance_hub_show_dashboard defaults to on (its switch default)', $WC->settings['vance_hub_show_dashboard']['default'] === true );
check( 'customize: vance_hub_show_dashboard is sanitised as a checkbox', $WC->settings['vance_hub_show_dashboard']['sanitize_callback'] === 'vance_hub_sanitize_checkbox' );
check( 'customize: the dashboard switch is a checkbox in section 5 (vance_hub_sec_dashboard)', $WC->controls['vance_hub_show_dashboard']['type'] === 'checkbox' && $WC->controls['vance_hub_show_dashboard']['section'] === 'vance_hub_sec_dashboard' );
check( 'customize: the panel description gives the preview address the code reads', isset( $WC->panels['vance_hub_panel']['description'] ) && strpos( $WC->panels['vance_hub_panel']['description'], '?vhh_layout=hub' ) !== false );

$DASH_SRC  = file_get_contents( $THEME . '/inc/hub-dashboard.php' );
$FRONT_SRC = file_get_contents( $THEME . '/front-page-hub.php' );
check( 'customize: the dashboard gate reads the same key through the master switch', strpos( $DASH_SRC, "vance_hub_switch_on( 'vance_hub_show_dashboard' )" ) !== false );
check( 'customize: the registered dashboard default is the one switch default', vance_hub_switch_default( 'vance_hub_show_dashboard' ) === $WC->settings['vance_hub_show_dashboard']['default'] );
check( 'customize: the template decides what prints through vance_hub_block_order()', strpos( $FRONT_SRC, 'vance_hub_block_order()' ) !== false );
check( 'customize: the template no longer reads the three retired switches itself', strpos( $FRONT_SRC, 'vance_hub_show_promo' ) === false && strpos( $FRONT_SRC, 'vance_hub_show_sections' ) === false && strpos( $FRONT_SRC, 'vance_hub_show_more' ) === false );
check( 'customize: the retired switches are read in one place, vance_hub_block_order() (legacy mods)', vance_hub_home_legacy_switches() === array( 'promo' => 'vance_hub_show_promo', 'sections' => 'vance_hub_show_sections', 'more' => 'vance_hub_show_more' ) );

check( 'sanitize: "0" is off', vance_hub_sanitize_checkbox( '0' ) === false );
check( 'sanitize: an empty string is off', vance_hub_sanitize_checkbox( '' ) === false );
check( 'sanitize: "off" is off', vance_hub_sanitize_checkbox( 'off' ) === false );
check( 'sanitize: "1" is on', vance_hub_sanitize_checkbox( '1' ) === true );
check( 'sanitize: true is on', vance_hub_sanitize_checkbox( true ) === true );
check( 'sanitize: false is off', vance_hub_sanitize_checkbox( false ) === false );
check( 'sanitize: the integer 2 is off (only 1 means on)', vance_hub_sanitize_checkbox( 2 ) === false );

/* ---- 15c. tool cards: slots, overrides, hidden ---------------------------- */
section( '15c. Tool cards on the Hub home' );

/** The mod keys of the tool tiles in a rendered page, in print order. */
function tool_order( $html ) {
	preg_match_all( '/<li class="vhub-tool vhub-tool--([a-z]+)">/', $html, $m );
	return $m[1];
}

$STANDARD = array(
	array( 'mod' => 'survey', 'icon' => 'clipboard', 'title' => 'S title', 'desc' => 'S desc', 'cta' => 'S cta', 'href' => 'https://example.test/s/', 'img' => 'https://example.test/s.jpg', 'alt' => 'S alt' ),
	array( 'mod' => 'nutrition', 'icon' => 'gauge', 'title' => 'N title', 'desc' => 'N desc', 'cta' => 'N cta', 'href' => 'https://example.test/n/', 'img' => 'https://example.test/n.jpg', 'alt' => 'N alt' ),
	array( 'mod' => 'recipes', 'icon' => 'fork', 'title' => 'R title', 'desc' => 'R desc', 'cta' => 'R cta', 'href' => 'https://example.test/r/', 'img' => '', 'alt' => 'R alt' ),
	array( 'mod' => 'discounts', 'icon' => 'tag', 'title' => 'D title', 'desc' => 'D desc', 'cta' => 'D cta', 'href' => 'https://example.test/d/', 'img' => 'https://example.test/d.jpg', 'alt' => 'D alt' ),
);
$EMPTY_SLOT = array( 'title' => '', 'text' => '', 'button' => '', 'link' => '', 'image' => 0 );
$DEFAULT_SLOTS = array(
	array_merge( array( 'pick' => 'survey' ), $EMPTY_SLOT ),
	array_merge( array( 'pick' => 'nutrition' ), $EMPTY_SLOT ),
	array_merge( array( 'pick' => 'recipes' ), $EMPTY_SLOT ),
	array_merge( array( 'pick' => 'discounts' ), $EMPTY_SLOT ),
);
$GLOBALS['ATTACH_URL'] = array( 41 => 'https://example.test/uploads/custom-large.jpg', 42 => 'https://example.test/uploads/no-alt-large.jpg' );
$GLOBALS['META']       = array( 41 => array( '_wp_attachment_image_alt' => "  A custom picture \n" ), 42 => array( '_wp_attachment_image_alt' => '' ) );

check( 'tools pure: nothing set gives the standard cards, same order and values', vance_hub_home_tools_apply( $STANDARD, $DEFAULT_SLOTS ) === $STANDARD );
$SLOTS = $DEFAULT_SLOTS;
$SLOTS[1]['pick'] = 'none';
check( 'tools pure: a "none" slot gives no card, and the others keep their order', array_column( vance_hub_home_tools_apply( $STANDARD, $SLOTS ), 'mod' ) === array( 'survey', 'recipes', 'discounts' ) );
$SLOTS = $DEFAULT_SLOTS;
foreach ( $SLOTS as $i => $s ) { $SLOTS[ $i ]['pick'] = 'none'; }
check( 'tools pure: four "none" slots give an empty list', vance_hub_home_tools_apply( $STANDARD, $SLOTS ) === array() );
$SLOTS = $DEFAULT_SLOTS;
$SLOTS[0]['pick'] = 'discounts';
$SLOTS[3]['pick'] = 'discounts';
check( 'tools pure: the same tool in two slots gives two cards (no de-duplication)', array_column( vance_hub_home_tools_apply( $STANDARD, $SLOTS ), 'mod' ) === array( 'discounts', 'nutrition', 'recipes', 'discounts' ) );
$SLOTS = $DEFAULT_SLOTS;
$SLOTS[0]['pick'] = 'bogus';
$SLOTS[1]['pick'] = null;
check( 'tools pure: an unknown or non-string pick gives no card', array_column( vance_hub_home_tools_apply( $STANDARD, $SLOTS ), 'mod' ) === array( 'recipes', 'discounts' ) );

$SLOTS = $DEFAULT_SLOTS;
$SLOTS[0]['title']  = '  My title ';
$SLOTS[0]['text']   = 'My text';
$SLOTS[0]['button'] = 'My button';
$SLOTS[0]['link']   = 'https://example.test/mine/';
$CARDS = vance_hub_home_tools_apply( $STANDARD, $SLOTS );
check( 'tools pure: a title, text, button and link replace title, desc, cta and href (title trimmed)', $CARDS[0]['title'] === 'My title' && $CARDS[0]['desc'] === 'My text' && $CARDS[0]['cta'] === 'My button' && $CARDS[0]['href'] === 'https://example.test/mine/' );
check( 'tools pure: the image, alt, icon and mod of that card are the standard ones', $CARDS[0]['img'] === 'https://example.test/s.jpg' && $CARDS[0]['alt'] === 'S alt' && $CARDS[0]['icon'] === 'clipboard' && $CARDS[0]['mod'] === 'survey' );
check( 'tools pure: the other cards are untouched', array_slice( $CARDS, 1 ) === array_slice( $STANDARD, 1 ) );
$SLOTS = $DEFAULT_SLOTS;
$SLOTS[1]['title'] = '   ';
$SLOTS[1]['text']  = "\n";
check( 'tools pure: a title or text of only spaces keeps the standard wording', vance_hub_home_tools_apply( $STANDARD, $SLOTS ) === $STANDARD );

$SLOTS = $DEFAULT_SLOTS;
$SLOTS[0]['image'] = 41;
$CARDS = vance_hub_home_tools_apply( $STANDARD, $SLOTS );
check( 'tools pure: a custom image replaces img and takes the attachment URL', $CARDS[0]['img'] === 'https://example.test/uploads/custom-large.jpg' );
check( 'tools pure: a custom image takes the alt text of the attachment, trimmed, never the standard alt', $CARDS[0]['alt'] === 'A custom picture' && $CARDS[0]['alt'] !== 'S alt' );
$SLOTS = $DEFAULT_SLOTS;
$SLOTS[0]['image'] = 42;
$CARDS = vance_hub_home_tools_apply( $STANDARD, $SLOTS );
check( 'tools pure: a custom image with no alt in the media library gets an empty alt, not the standard alt', $CARDS[0]['img'] === 'https://example.test/uploads/no-alt-large.jpg' && $CARDS[0]['alt'] === '' );
$SLOTS = $DEFAULT_SLOTS;
$SLOTS[0]['image'] = 99;
check( 'tools pure: an attachment that gives no URL keeps the standard image and alt', vance_hub_home_tools_apply( $STANDARD, $SLOTS ) === $STANDARD );
$SLOTS = $DEFAULT_SLOTS;
$SLOTS[2]['image'] = 41;
check( 'tools pure: a custom image on a card with no standard image sets both img and alt', vance_hub_home_tools_apply( $STANDARD, $SLOTS )[2]['img'] === 'https://example.test/uploads/custom-large.jpg' );
$SLOTS = $DEFAULT_SLOTS;
$SLOTS[0]['image'] = -41;
check( 'tools pure: a negative image id is treated as no image', vance_hub_home_tools_apply( $STANDARD, $SLOTS ) === $STANDARD );
$SLOTS = $DEFAULT_SLOTS;
$SLOTS[0]['pick'] = 'recipes';
$SLOTS[2]['pick'] = 'recipes';
$SLOTS[2]['title'] = 'Second recipes';
$CARDS = vance_hub_home_tools_apply( $STANDARD, $SLOTS );
check( 'tools pure: two slots with the same tool keep their own overrides', $CARDS[0]['title'] === 'R title' && $CARDS[2]['title'] === 'Second recipes' );

// The reader and the template.
arrange();
check( 'tools: with nothing saved, vance_hub_home_tools() equals vance_hub_tools() exactly', vance_hub_home_tools() === vance_hub_tools() && 4 === count( vance_hub_tools() ) );
$HTML = render_home( false );
check( 'tools: with nothing saved the page prints the four tiles in today\'s order', tool_order( $HTML ) === array( 'survey', 'nutrition', 'recipes', 'discounts' ) );

arrange( array( 'vance_hub_tool_1_pick' => 'none', 'vance_hub_tool_3_pick' => 'none' ) );
$HTML = render_home( false );
check( 'tools: two slots hidden print two tiles, in slot order', tool_order( $HTML ) === array( 'nutrition', 'discounts' ) );

arrange( array( 'vance_hub_tool_1_pick' => 'none', 'vance_hub_tool_2_pick' => 'none', 'vance_hub_tool_3_pick' => 'none', 'vance_hub_tool_4_pick' => 'none' ) );
$HTML = render_home( false );
check( 'tools: four hidden slots print no tools section, no heading and no list', strpos( $HTML, 'vhub-tools-section' ) === false && strpos( $HTML, 'vhub-tools-title' ) === false && strpos( $HTML, 'class="vhub-wrap vhub-tools"' ) === false && strpos( $HTML, 'Free tools' ) === false );
check( 'tools: with the tools block empty, the other blocks still print', strpos( $HTML, 'vhub-hero' ) !== false && strpos( $HTML, 'id="vhub-join"' ) !== false && '' === $RENDER_ERROR );

arrange( array( 'vance_hub_tool_2_pick' => 'survey', 'vance_hub_tool_2_title' => 'A <b>new</b> title', 'vance_hub_tool_1_link' => 'https://example.test/own/', 'vance_hub_tool_4_image' => 41 ) );
$GLOBALS['META'] = array( 41 => array( '_wp_attachment_image_alt' => 'A custom picture' ) ); // arrange() clears the meta.
$HTML = render_home( false );
check( 'tools: the same tool in two slots prints two tiles', tool_order( $HTML ) === array( 'survey', 'survey', 'recipes', 'discounts' ) );
check( 'tools: a saved title is printed, escaped', strpos( $HTML, 'A &lt;b&gt;new&lt;/b&gt; title' ) !== false && strpos( $HTML, '<b>new</b>' ) === false );
check( 'tools: a saved link is the tile address', strpos( $HTML, '<a class="vhub-tool__link" href="https://example.test/own/">' ) !== false );
check( 'tools: a custom image prints with the attachment alt, not the standard alt of the discounts tile', strpos( $HTML, 'src="https://example.test/uploads/custom-large.jpg" alt="A custom picture"' ) !== false && strpos( $HTML, 'Two people walking side by side' ) === false );
check( 'tools: a tile with no custom image keeps its standard image and alt', strpos( $HTML, 'Bowls of oats' ) === false && strpos( $HTML, 'A woman in an apron' ) !== false );

arrange( array( 'vance_hub_tool_1_pick' => 'bogus', 'vance_hub_tool_2_pick' => 12, 'vance_hub_tool_3_image' => 'abc', 'vance_hub_tool_4_title' => array( 'x' ) ) );
check( 'tools: unreadable saved values fall back to the slot default (pick), to no image and to the standard title', vance_hub_home_tools() === vance_hub_tools() );

arrange( array( 'vance_hub_tool_1_title' => 'Changed', 'vance_hub_tool_2_pick' => 'none' ) );
$STD = vance_hub_tools();
check( 'tools: vance_hub_tools() (used by the article sidebar and the dashboard) ignores the card settings', array_column( $STD, 'mod' ) === array( 'survey', 'nutrition', 'recipes', 'discounts' ) && 'Changed' !== $STD[0]['title'] );
check( 'tools: only the home template reads vance_hub_home_tools()', 1 === preg_match( '/vance_hub_home_tools\(\)/', file_get_contents( $THEME . '/front-page-hub.php' ) ) && false === strpos( file_get_contents( $THEME . '/inc/hub-article.php' ) . file_get_contents( $THEME . '/inc/hub-dashboard.php' ), 'vance_hub_home_tools' ) );

unset( $GLOBALS['ATTACH_URL'] );
$GLOBALS['META'] = array();

/* ---- 16. home condition tiles: symptoms pictures, names, fallbacks --------- */
section( '16. Home condition tiles (pure)' );

$TILE_REG        = array_column( vance_gi_condition_cards(), null, 'slug' );
$TILE_CDN        = 'https://example.test/wp-content/themes/vance-health-hub/assets/img/';
$TILE_CROHN_NAME = "Crohn\u{2019}s Disease";

arrange();
$TILE_CD  = vance_hub_condition_tile( $TILE_REG['crohns-disease'] );
$TILE_UC  = vance_hub_condition_tile( $TILE_REG['ulcerative-colitis'] );
$TILE_DD  = vance_hub_condition_tile( $TILE_REG['diverticular-disease'] );
$TILE_IBS = vance_hub_condition_tile( $TILE_REG['irritable-bowel-syndrome'] );

check( "tile crohns: picture is the symptoms image from the conditions folder", strpos( $TILE_CD['img'], $TILE_CDN . 'gi-health/conditions/symptoms-crohns.webp?v=' ) === 0 );
check( "tile crohns: alt is the Crohn's symptoms text", $TILE_CD['alt'] === "Body silhouette showing common Crohn's symptoms" );
check( "tile crohns: name equals the decoded registry title", $TILE_CD['name'] === wp_specialchars_decode( $TILE_REG['crohns-disease']['title'] ) );
check( "tile crohns: name is the typographic apostrophe form", $TILE_CD['name'] === $TILE_CROHN_NAME );

check( "tile uc: picture is the symptoms image from the conditions folder", strpos( $TILE_UC['img'], $TILE_CDN . 'gi-health/conditions/symptoms-uc.webp?v=' ) === 0 );
check( "tile uc: alt is the UC symptoms text", $TILE_UC['alt'] === 'Body silhouette showing common UC symptoms' );
check( "tile uc: name equals the decoded registry title", $TILE_UC['name'] === wp_specialchars_decode( $TILE_REG['ulcerative-colitis']['title'] ) );
check( "tile uc: name is the registry title with its abbreviation", $TILE_UC['name'] === 'Ulcerative Colitis (UC)' );

check( "tile dd: picture is the symptoms image from the conditions folder", strpos( $TILE_DD['img'], $TILE_CDN . 'gi-health/conditions/symptoms-dd.webp?v=' ) === 0 );
check( "tile dd: alt is the Diverticular Disease symptoms text", $TILE_DD['alt'] === 'Body silhouette showing common Diverticular Disease symptoms' );
check( "tile dd: name is the short form", $TILE_DD['name'] === 'Diverticular Disease' );
check( "tile dd: name has no Diverticulitis", strpos( $TILE_DD['name'], 'Diverticulitis' ) === false );
check( "tile dd: name has no ampersand, encoded or decoded", strpos( $TILE_DD['name'], '&' ) === false );

check( "tile ibs: name keeps the decoded registry title", $TILE_IBS['name'] === 'Irritable Bowel Syndrome (IBS)' );
check( "tile ibs: picture is the registry photo when no override exists", strpos( $TILE_IBS['img'], $TILE_CDN . 'gi-health/ibs.webp?v=' ) === 0 );
check( "tile ibs: alt is the registry alt", $TILE_IBS['alt'] === 'A man and his dog looking out across the water from a waterfront pier' );

$TILE_AMP = vance_hub_condition_tile( array( 'slug' => 'made-up-condition', 'image' => '', 'alt' => '', 'title' => 'Diet &amp; Gut' ) );
check( "tile: an unknown slug gets its title decoded once", $TILE_AMP['name'] === 'Diet & Gut' );

check( "registry: the diverticular-disease title keeps its full name for the other pages", $TILE_REG['diverticular-disease']['title'] === 'Diverticular Disease &amp; Diverticulitis' );

$TILE_JUNK = null;
try { $TILE_JUNK = vance_hub_condition_tile( 'junk' ); } catch ( Throwable $e ) { $TILE_JUNK = 'threw: ' . $e->getMessage(); }
check( "tile: a non-array card gives an empty tile and does not throw", $TILE_JUNK === array( 'img' => '', 'alt' => '', 'name' => '' ) );

$TILE_NULL = null;
try { $TILE_NULL = vance_hub_condition_tile( null ); } catch ( Throwable $e ) { $TILE_NULL = 'threw: ' . $e->getMessage(); }
check( "tile: a null card gives an empty tile and does not throw", $TILE_NULL === array( 'img' => '', 'alt' => '', 'name' => '' ) );

// Fallback: a theme root with only the registry photos for UC and DD. The
// symptoms files and the Crohn's photo are absent, so the override is missing.
$TILE_ROOT = sys_get_temp_dir() . '/vhh-hub-tiles-' . getmypid();
@mkdir( $TILE_ROOT . '/assets/img/gi-health', 0777, true );
file_put_contents( $TILE_ROOT . '/assets/img/gi-health/ulcerative-colitis.webp', '' );
file_put_contents( $TILE_ROOT . '/assets/img/gi-health/diverticular-disease.webp', '' );
register_shutdown_function( function () use ( $TILE_ROOT ) {
	@unlink( $TILE_ROOT . '/assets/img/gi-health/ulcerative-colitis.webp' );
	@unlink( $TILE_ROOT . '/assets/img/gi-health/diverticular-disease.webp' );
	@rmdir( $TILE_ROOT . '/assets/img/gi-health' );
	@rmdir( $TILE_ROOT . '/assets/img' );
	@rmdir( $TILE_ROOT . '/assets' );
	@rmdir( $TILE_ROOT );
} );

$GLOBALS['THEME_DIR'] = $TILE_ROOT;
$TILE_FB_UC = vance_hub_condition_tile( $TILE_REG['ulcerative-colitis'] );
$TILE_FB_DD = vance_hub_condition_tile( $TILE_REG['diverticular-disease'] );
$TILE_FB_CD = vance_hub_condition_tile( $TILE_REG['crohns-disease'] );
$GLOBALS['THEME_DIR'] = $THEME;

check( "fallback uc: override missing, picture is the registry photo", strpos( $TILE_FB_UC['img'], $TILE_CDN . 'gi-health/ulcerative-colitis.webp?v=' ) === 0 );
check( "fallback uc: the registry photo is not the symptoms picture", strpos( $TILE_FB_UC['img'], 'symptoms' ) === false );
check( "fallback uc: alt is the registry alt for the card", $TILE_FB_UC['alt'] === 'Two women walking and talking together on a sunlit city street' );
check( "fallback dd: picture is the registry photo for diverticular disease", strpos( $TILE_FB_DD['img'], $TILE_CDN . 'gi-health/diverticular-disease.webp?v=' ) === 0 );
check( "fallback dd: alt is the registry alt for the card", $TILE_FB_DD['alt'] === 'A carer handing a glass of water to an older woman sitting on a sofa' );
check( "fallback dd: name stays the short form when the photo falls back", $TILE_FB_DD['name'] === 'Diverticular Disease' );
check( "fallback both missing: picture is empty, so the template prints no img", $TILE_FB_CD['img'] === '' );
check( "fallback both missing: alt is empty", $TILE_FB_CD['alt'] === '' );
check( "fallback both missing: name is still the decoded registry title", $TILE_FB_CD['name'] === $TILE_CROHN_NAME );

section( '16b. Home condition tiles, rendered' );

arrange();
seed_full_home();
$HTML  = render_home( false );
$TILES = section_html( $HTML, 'vhub-conditions' );
check( 'rendered: the conditions block renders without a PHP error', $RENDER_ERROR === '' && $TILES !== '' );
check( 'rendered: the Crohn\'s tile shows the symptoms picture with its alt', preg_match( '/<img class="vhub-cond-tile__img" src="[^"]*gi-health\/conditions\/symptoms-crohns\.webp\?v=\d+" alt="Body silhouette showing common Crohn&#039;s symptoms"/', $TILES ) === 1 );
check( 'rendered: the UC tile shows the symptoms picture with its alt', preg_match( '/<img class="vhub-cond-tile__img" src="[^"]*gi-health\/conditions\/symptoms-uc\.webp\?v=\d+" alt="Body silhouette showing common UC symptoms"/', $TILES ) === 1 );
check( 'rendered: the diverticular tile shows the symptoms picture with its alt', preg_match( '/<img class="vhub-cond-tile__img" src="[^"]*gi-health\/conditions\/symptoms-dd\.webp\?v=\d+" alt="Body silhouette showing common Diverticular Disease symptoms"/', $TILES ) === 1 );
check( 'rendered: exactly three tiles carry a symptoms picture', substr_count( $TILES, 'gi-health/conditions/symptoms-' ) === 3 );
check( 'rendered: the diverticular tile name is the short form', strpos( $TILES, '<span class="vhub-cond-tile__name">Diverticular Disease</span>' ) !== false );
check( 'rendered: no "Diverticulitis" in the conditions block', strpos( $TILES, 'Diverticulitis' ) === false );
check( 'rendered: no "&amp;" in the conditions block', strpos( $TILES, '&amp;' ) === false );
check( 'rendered: the Crohn\'s tile name is the typographic apostrophe form', strpos( $TILES, '<span class="vhub-cond-tile__name">' . $TILE_CROHN_NAME . '</span>' ) !== false );
check( 'rendered: the teal list shows the other four conditions', substr_count( $TILES, 'class="vhub-cond-list__link"' ) === 4 );
check( 'rendered: the list shows the full title for Inflammatory Bowel Disease', strpos( $TILES, '>Inflammatory Bowel Disease (IBD)</a>' ) !== false );
check( 'rendered: the list shows the full title for Irritable Bowel Syndrome', strpos( $TILES, '>Irritable Bowel Syndrome (IBS)</a>' ) !== false );
check( 'rendered: the list shows the full title for Microscopic Colitis', strpos( $TILES, '>Microscopic Colitis</a>' ) !== false );
check( 'rendered: the list shows the full title for Colorectal Cancer', strpos( $TILES, '>Colorectal Cancer</a>' ) !== false );
check( 'rendered: the list does not repeat Ulcerative Colitis from the tiles', strpos( $TILES, '>Ulcerative Colitis (UC)</a>' ) === false );

/* ===================================================================== */
section( '17. Master switch: Home layout turns every VanceHubClub part on or off' );

// The defaults the gates fall back to. Every part is on unless it is the check-in.
check( 'master: the default of the article part is on', vance_hub_switch_default( 'vance_hub_articles' ) === true );
check( 'master: the default of the dashboard part is on', vance_hub_switch_default( 'vance_hub_show_dashboard' ) === true );
check( 'master: the default of the check-in part is off (health data)', vance_hub_switch_default( 'vance_hub_dash_checkin' ) === false );

// Nothing saved: the master is classic.
arrange();
check( 'master: nothing saved, the master is off', vance_hub_master_on() === false );

// Master classic: every part is off, even with its own box ticked.
arrange( array(
	'vance_hub_articles'       => true,
	'vance_hub_show_dashboard' => true,
	'vance_hub_dashboard_tabs' => true,
	'vance_hub_dash_member_no' => true,
	'vance_hub_dash_chart'     => true,
	'vance_hub_dash_checkin'   => true,
	'vance_hub_tool_result'    => true,
	'vance_hub_header'         => true,
	'vance_hub_community'      => true,
) );
check( 'master classic: the article part is off', vance_hub_switch_on( 'vance_hub_articles' ) === false );
check( 'master classic: the dashboard part is off', vance_hub_switch_on( 'vance_hub_show_dashboard' ) === false );
check( 'master classic: the tab look is off', vance_hub_switch_on( 'vance_hub_dashboard_tabs' ) === false );
check( 'master classic: the member number is off', vance_hub_switch_on( 'vance_hub_dash_member_no' ) === false );
check( 'master classic: the results chart is off', vance_hub_switch_on( 'vance_hub_dash_chart' ) === false );
check( 'master classic: the check-in is off', vance_hub_switch_on( 'vance_hub_dash_checkin' ) === false );
check( 'master classic: the survey result page is off', vance_hub_switch_on( 'vance_hub_tool_result' ) === false );
check( 'master classic: the header is off', vance_hub_switch_on( 'vance_hub_header' ) === false );
check( 'master classic: the community hub is off', vance_hub_switch_on( 'vance_hub_community' ) === false );

// Master classic: the switch does not read the admin preview. Each part's own resolver
// applies the preview, so the switch stays off here.
arrange( array( 'vance_hub_articles' => true ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'master classic: the switch ignores an admin ?vhh_layout=hub (the resolver applies it)', vance_hub_switch_on( 'vance_hub_articles' ) === false );

// Master hub, nothing saved: every part is on, except the check-in.
arrange( array( 'vance_home_layout' => 'hub' ) );
check( 'master hub, unsaved: the master is on', vance_hub_master_on() === true );
check( 'master hub, unsaved: the article part is on', vance_hub_switch_on( 'vance_hub_articles' ) === true );
check( 'master hub, unsaved: the dashboard part is on', vance_hub_switch_on( 'vance_hub_show_dashboard' ) === true );
check( 'master hub, unsaved: the tab look is on', vance_hub_switch_on( 'vance_hub_dashboard_tabs' ) === true );
check( 'master hub, unsaved: the member number is on', vance_hub_switch_on( 'vance_hub_dash_member_no' ) === true );
check( 'master hub, unsaved: the results chart is on', vance_hub_switch_on( 'vance_hub_dash_chart' ) === true );
check( 'master hub, unsaved: the check-in is off (its default)', vance_hub_switch_on( 'vance_hub_dash_checkin' ) === false );
check( 'master hub, unsaved: the survey result page is on', vance_hub_switch_on( 'vance_hub_tool_result' ) === true );
check( 'master hub, unsaved: the header is on', vance_hub_switch_on( 'vance_hub_header' ) === true );
check( 'master hub, unsaved: the community hub is on', vance_hub_switch_on( 'vance_hub_community' ) === true );

// Master hub, every part saved off: every part is off.
arrange( array(
	'vance_home_layout'        => 'hub',
	'vance_hub_articles'       => false,
	'vance_hub_show_dashboard' => false,
	'vance_hub_dashboard_tabs' => false,
	'vance_hub_dash_member_no' => false,
	'vance_hub_dash_chart'     => false,
	'vance_hub_dash_checkin'   => false,
	'vance_hub_tool_result'    => false,
	'vance_hub_header'         => false,
	'vance_hub_community'      => false,
) );
check( 'master hub, saved off: the article part is off', vance_hub_switch_on( 'vance_hub_articles' ) === false );
check( 'master hub, saved off: the dashboard part is off', vance_hub_switch_on( 'vance_hub_show_dashboard' ) === false );
check( 'master hub, saved off: the tab look is off', vance_hub_switch_on( 'vance_hub_dashboard_tabs' ) === false );
check( 'master hub, saved off: the member number is off', vance_hub_switch_on( 'vance_hub_dash_member_no' ) === false );
check( 'master hub, saved off: the results chart is off', vance_hub_switch_on( 'vance_hub_dash_chart' ) === false );
check( 'master hub, saved off: the check-in is off', vance_hub_switch_on( 'vance_hub_dash_checkin' ) === false );
check( 'master hub, saved off: the survey result page is off', vance_hub_switch_on( 'vance_hub_tool_result' ) === false );
check( 'master hub, saved off: the header is off', vance_hub_switch_on( 'vance_hub_header' ) === false );
check( 'master hub, saved off: the community hub is off', vance_hub_switch_on( 'vance_hub_community' ) === false );

// Master hub, check-in saved on: the check-in follows its own saved switch.
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
check( 'master hub, check-in saved on: the check-in is on', vance_hub_switch_on( 'vance_hub_dash_checkin' ) === true );

/* ===================================================================== */
/* The checks below were added to find what the builders' own tests did not
 * prove: block order rendered, hidden blocks and their queries, the join link,
 * every text mod, the counts at their limits, and more tool-card cases.
 * ===================================================================== */

/** Posts 701 to 720 are article posts; the ids are in order. */
function posts_range( $first, $last ) {
	return array_map( function ( $id ) { return post_obj( $id, 'Article ' . $id ); }, range( $first, $last ) );
}

/**
 * Every block has real data: tiles, five quotes, recipes in two lists, three
 * articles in two categories and in the library, and the promo block.
 *
 * Article ids 701 to 720 are the "post" list. Category 11 (Gastro Living) holds
 * 705 to 708 and category 12 (Health News) holds 709 to 712. Ids 701 to 704 are
 * the library. With the three "Latest by section" slugs (the third has no term),
 * the sections take 705, 706, 707 and 709, 710, 711. "More to read" takes what
 * is left in "post" order.
 */
function seed_blocks_home() {
	$GLOBALS['MODS'] = array(
		'vance_hub_founding_deadline' => '2026-12-01',
		'vance_hub_tile_1_title'      => 'Live Q&A',
		'vance_hub_tile_1_text'       => 'Ask the panel',
		'vance_hub_tile_2_title'      => 'Second tile',
		'vance_hub_tile_2_link'       => 'https://example.test/second/',
	);
	$GLOBALS['USERS_TOTAL']  = 1234;
	$GLOBALS['USERS_WEEK']   = 17;
	$GLOBALS['SURVEY_STEPS'] = 9;
	$GLOBALS['RECIPE_CELLS'] = array( array( 'key' => 'grid', 'value' => '1,091', 'label' => 'Recipes', 'href' => '' ) );
	$GLOBALS['PROMO_HTML']   = '<p class="promo-body">PROMO-BODY</p>';
	$GLOBALS['PROMO_CALLS']  = 0;

	$ARTICLES = posts_range( 701, 720 );
	$GLOBALS['QUERY_POSTS']['post']   = $ARTICLES;
	$GLOBALS['QUERY_POSTS']['cat:11'] = array_slice( $ARTICLES, 4, 4 ); // 705 to 708
	$GLOBALS['QUERY_POSTS']['cat:12'] = array_slice( $ARTICLES, 8, 4 ); // 709 to 712
	$GLOBALS['CAT_TERMS']['content-gastro-living'] = cat_term( 11, 'content-gastro-living', 'Gastro Living' );
	$GLOBALS['CAT_TERMS']['content-health-news']   = cat_term( 12, 'content-health-news', 'Health News' );

	$GLOBALS['QUERY_POSTS']['testimonial'] = array(
		post_obj( 501, 'Quote A', '<p>Quote one.</p>' ),
		post_obj( 502, 'Quote B', '<p>Quote two.</p>' ),
		post_obj( 503, 'Quote C', '<p>Quote three.</p>' ),
		post_obj( 504, 'Quote D', '<p>Quote four.</p>' ),
		post_obj( 505, 'Quote E', '<p>Quote five.</p>' ),
	);
	$GLOBALS['QUERY_POSTS']['vance_recipe']         = array( post_obj( 601, 'Recipe 1' ), post_obj( 602, 'Recipe 2' ), post_obj( 603, 'Recipe 3' ), post_obj( 604, 'Recipe 4' ) );
	$GLOBALS['QUERY_POSTS']['vance_recipe:popular'] = array( post_obj( 605, 'Popular 1' ), post_obj( 606, 'Popular 2' ), post_obj( 607, 'Popular 3' ), post_obj( 608, 'Popular 4' ) );
}

/**
 * The same data as seed_blocks_home(), with enough articles for the largest
 * count: category 11 holds 701 to 712 and category 12 holds 713 to 720.
 */
function seed_counts_home() {
	seed_blocks_home();
	$ARTICLES = posts_range( 701, 720 );
	$GLOBALS['QUERY_POSTS']['post']   = $ARTICLES;
	$GLOBALS['QUERY_POSTS']['cat:11'] = array_slice( $ARTICLES, 0, 12 );
	$GLOBALS['QUERY_POSTS']['cat:12'] = array_slice( $ARTICLES, 12, 8 );
}

/** The blocks that print, in the order they appear in the HTML. */
function block_order_in( $html, $marks ) {
	$at = array();
	foreach ( $marks as $block => $mark ) {
		$pos = strpos( $html, $mark );
		if ( false !== $pos ) {
			$at[ $block ] = $pos;
		}
	}
	asort( $at );
	return array_keys( $at );
}

/** The article ids linked from one block of the page, in order. */
function block_article_ids( $html, $class ) {
	preg_match_all( '/example\.test\/p\/(\d+)\//', section_html( $html, $class ), $m );
	return array_map( 'intval', $m[1] );
}

/** How many logged WP_Query calls match the predicate. */
function count_queries( $match ) {
	$n = 0;
	foreach ( $GLOBALS['WP_QUERY_LOG'] as $args ) {
		if ( $match( $args ) ) {
			$n++;
		}
	}
	return $n;
}

/** posts_per_page of every logged query, in the order the page ran them. */
function query_limits() {
	return array_column( $GLOBALS['WP_QUERY_LOG'], 'posts_per_page' );
}

/** The first markup of each movable block. A block is on the page when its mark is. */
$BLOCK_MARK = array(
	'tools'      => 'class="vhub-tools-section"',
	'inside'     => 'class="vhub-wrap vhub-section vhub-inside"',
	'conditions' => 'class="vhub-wrap vhub-section vhub-conditions"',
	'community'  => '<section class="vhub-community"',
	'promo'      => '<div class="vhub-promo">',
	'library'    => 'class="vhub-wrap vhub-section vhub-library"',
	'sections'   => 'class="vhub-wrap vhub-section vhub-secs"',
	'more'       => 'class="vhub-wrap vhub-section vhub-more"',
	'join'       => 'id="vhub-join"',
);
/** Markup that only one block prints. None of it may appear when that block is hidden. */
$BLOCK_EXTRA = array(
	'tools'      => 'vhub-tool--',
	'inside'     => 'vhub-member-tile',
	'conditions' => 'vhub-cond-tile',
	'community'  => 'vhub-quote',
	'promo'      => 'PROMO-BODY',
	'library'    => 'vhub-feat',
	'sections'   => 'vhub-sec-card',
	'more'       => 'vhub-card__',
	'join'       => 'vhub-join',
);

/* ---- 18. block order, rendered -------------------------------------------- */
section( '18. Block order, rendered' );

/*
 * Each row: the saved order, the blocks that must print in that order, and the
 * article ids each article block must hold. The ids follow from seed_blocks_home().
 */
$LIB_IDS = array( 701, 702, 703, 704 );
$SEC_IDS = array( 705, 706, 707, 709, 710, 711 );
$MORE_IDS = array( 708, 712, 713, 714, 715, 716 );
$ORDER_ROWS = array(
	'reversed'          => array( 'order' => 'join,more,sections,library,promo,community,conditions,inside,tools', 'expect' => array( 'join', 'more', 'sections', 'library', 'promo', 'community', 'conditions', 'inside', 'tools' ), 'lib' => $LIB_IDS, 'sec' => $SEC_IDS, 'more' => $MORE_IDS ),
	'more before library' => array( 'order' => 'more,library,tools,inside,conditions,sections,community,promo,join', 'expect' => array( 'more', 'library', 'tools', 'inside', 'conditions', 'sections', 'community', 'promo', 'join' ), 'lib' => $LIB_IDS, 'sec' => $SEC_IDS, 'more' => $MORE_IDS ),
	'sections first'    => array( 'order' => 'sections,tools,join,library,more,conditions,inside,community,promo', 'expect' => array( 'sections', 'tools', 'join', 'library', 'more', 'conditions', 'inside', 'community', 'promo' ), 'lib' => $LIB_IDS, 'sec' => $SEC_IDS, 'more' => $MORE_IDS ),
	'library hidden'    => array( 'order' => 'tools,inside,sections,more,join', 'expect' => array( 'tools', 'inside', 'sections', 'more', 'join' ), 'lib' => array(), 'sec' => $SEC_IDS, 'more' => array( 701, 702, 703, 704, 708, 712 ) ),
	'sections hidden'   => array( 'order' => 'library,more,join', 'expect' => array( 'library', 'more', 'join' ), 'lib' => $LIB_IDS, 'sec' => array(), 'more' => array( 705, 706, 707, 708, 709, 710 ) ),
	'unknown and repeated ids' => array( 'order' => 'bogus,join,join,tools,nope', 'expect' => array( 'join', 'tools' ), 'lib' => array(), 'sec' => array(), 'more' => array() ),
	'the default order' => array( 'order' => 'tools,inside,conditions,community,promo,library,sections,more,join', 'expect' => array( 'tools', 'inside', 'conditions', 'community', 'promo', 'library', 'sections', 'more', 'join' ), 'lib' => $LIB_IDS, 'sec' => $SEC_IDS, 'more' => $MORE_IDS ),
);

foreach ( $ORDER_ROWS as $ROW_NAME => $ROW ) {
	arrange();
	seed_blocks_home();
	$GLOBALS['MODS']['vance_hub_block_order'] = $ROW['order'];
	$H = render_home( false );
	check( "order $ROW_NAME: renders without a PHP error", '' === $RENDER_ERROR );
	check( "order $ROW_NAME: the blocks print in exactly the saved order", block_order_in( $H, $BLOCK_MARK ) === $ROW['expect'] );
	check( "order $ROW_NAME: exactly one h1", substr_count( $H, '<h1' ) === 1 );
	check( "order $ROW_NAME: the hero comes first, then the facts, then the first block", strpos( $H, '<section class="vhub-hero"' ) < strpos( $H, '<ul class="vhub-facts">' ) && strpos( $H, '<ul class="vhub-facts">' ) < strpos( $H, $BLOCK_MARK[ $ROW['expect'][0] ] ) );
	check( "order $ROW_NAME: the library holds the expected articles", block_article_ids( $H, 'vhub-library' ) === $ROW['lib'] );
	check( "order $ROW_NAME: the latest-by-section columns hold the expected articles", block_article_ids( $H, 'vhub-secs' ) === $ROW['sec'] );
	check( "order $ROW_NAME: more to read holds the expected articles", block_article_ids( $H, 'vhub-more' ) === $ROW['more'] );
	check( "order $ROW_NAME: no article is in two of library, sections and more", array_intersect( $ROW['lib'], $ROW['sec'] ) === array() && array_intersect( $ROW['lib'], $ROW['more'] ) === array() && array_intersect( $ROW['sec'], $ROW['more'] ) === array() );
	foreach ( array_diff( array_keys( $BLOCK_MARK ), $ROW['expect'] ) as $HIDDEN ) {
		check( "order $ROW_NAME: hidden $HIDDEN prints none of its markup", strpos( $H, $BLOCK_MARK[ $HIDDEN ] ) === false && strpos( $H, $BLOCK_EXTRA[ $HIDDEN ] ) === false );
	}
}

// An order saved as the empty string: the hero and the facts, and no movable block.
arrange();
seed_blocks_home();
$GLOBALS['MODS']['vance_hub_block_order'] = '';
$H = render_home( false );
check( 'order empty: renders without a PHP error', '' === $RENDER_ERROR );
check( 'order empty: the hero and the facts still print', strpos( $H, '<section class="vhub-hero"' ) !== false && strpos( $H, '<ul class="vhub-facts">' ) !== false );
check( 'order empty: exactly one h1', substr_count( $H, '<h1' ) === 1 );
foreach ( $BLOCK_MARK as $EMPTY_BLOCK => $EMPTY_MARK ) {
	check( "order empty: $EMPTY_BLOCK prints none of its markup", strpos( $H, $EMPTY_MARK ) === false && strpos( $H, $BLOCK_EXTRA[ $EMPTY_BLOCK ] ) === false );
}

/* ---- 19. hidden blocks run no query --------------------------------------- */
section( '19. Hidden blocks run no query' );

arrange();
seed_blocks_home();
$GLOBALS['MODS']['vance_hub_block_order'] = 'tools,inside,conditions,promo,join';
$H = render_home( false );
check( 'no queries: with only non-article blocks on, no article query runs', count_queries( function ( $a ) { return 'post' === $a['post_type']; } ) === 0 );
check( 'no queries: with community off, no testimonial query runs', count_queries( function ( $a ) { return 'testimonial' === $a['post_type']; } ) === 0 );
check( 'no queries: with community off, no recipe query runs', count_queries( function ( $a ) { return 'vance_recipe' === $a['post_type']; } ) === 0 );

arrange();
seed_blocks_home();
$GLOBALS['MODS']['vance_hub_block_order'] = 'library,more,join';
$H = render_home( false );
check( 'no queries: sections hidden runs no category query', count_queries( function ( $a ) { return isset( $a['cat'] ); } ) === 0 );
check( 'no queries: library and more each run exactly one query', count_queries( function ( $a ) { return 'post' === $a['post_type'] && ! isset( $a['cat'] ) && ! isset( $a['post__not_in'] ); } ) === 1 && count_queries( function ( $a ) { return isset( $a['post__not_in'] ) && ! isset( $a['cat'] ); } ) === 1 );
check( 'sections hidden: "More to read" shows the posts the sections would have taken (705, 706, 707, 709, 710)', block_article_ids( $H, 'vhub-more' ) === array( 705, 706, 707, 708, 709, 710 ) && array_intersect( array( 705, 706, 707, 709, 710 ), block_article_ids( $H, 'vhub-more' ) ) === array( 705, 706, 707, 709, 710 ) );

arrange();
seed_blocks_home();
$GLOBALS['MODS']['vance_hub_block_order'] = 'tools,sections,more,join';
$H = render_home( false );
check( 'library hidden: no library query runs, and the sections still run theirs', count_queries( function ( $a ) { return 'post' === $a['post_type'] && ! isset( $a['cat'] ) && ! isset( $a['post__not_in'] ); } ) === 0 && count_queries( function ( $a ) { return isset( $a['cat'] ); } ) === 2 );
check( 'library hidden: the articles the library would have shown go to the sections and more', block_article_ids( $H, 'vhub-more' ) === array( 701, 702, 703, 704, 708, 712 ) );

/* ---- 20. the join link, rendered ------------------------------------------ */
section( '20. The join link, rendered' );

arrange();
seed_blocks_home();
$GLOBALS['MODS']['vance_hub_block_order'] = 'join,tools';
$H = render_home( false );
check( 'join on, guest: the member bar links to the #vhub-join anchor (one link)', substr_count( $H, 'href="#vhub-join"' ) === 1 );
check( 'join on, guest: the id vhub-join exists exactly once', substr_count( $H, 'id="vhub-join"' ) === 1 );
check( 'join on, guest: the sign-up button of the Join block goes to the sign-up page', strpos( $H, 'href="https://example.test/login/?tab=signup" data-vhub-join' ) !== false );

arrange();
seed_blocks_home();
$GLOBALS['MODS']['vance_hub_block_order'] = 'tools,more';
$H = render_home( false );
check( 'join off, guest: no element links to #vhub-join, the member bar included', substr_count( $H, '#vhub-join' ) === 0 );
check( 'join off, guest: the member bar link goes to the sign-up page', substr_count( $H, 'href="https://example.test/login/?tab=signup"' ) === 1 );
check( 'join off, guest: no Join block markup prints', strpos( $H, 'vhub-join' ) === false );

arrange();
seed_blocks_home();
$GLOBALS['MODS']['vance_hub_block_order'] = 'tools,more';
$H = render_home( true );
check( 'join off, member: no element links to #vhub-join', substr_count( $H, '#vhub-join' ) === 0 );
check( 'join off, member: the member bar links to the dashboard', strpos( $H, 'href="https://example.test/dashboard/"' ) !== false );

arrange();
seed_blocks_home();
$GLOBALS['MODS']['vance_hub_block_order'] = 'join,tools';
$H = render_home( true );
check( 'join on, member: no element links to #vhub-join (the member bar links to the dashboard)', substr_count( $H, '#vhub-join' ) === 0 );
// The member variant of the Join block has no id, so for a signed-in member the
// function returns the sign-up address, and no link on the page points at #vhub-join.
check( 'join on, member: vance_hub_join_href() is not the #vhub-join anchor, and no link on the page points at it', vance_hub_join_href() !== '#vhub-join' && strpos( $H, 'href="#vhub-join"' ) === false );

/* ---- 21. texts, rendered: each of the 22 mods ----------------------------- */
section( '21. Texts, rendered: each of the 22 mods' );

$NOTE_TEXT = 'This is not a diagnosis.';
$MARKUP    = '<script>alert(1)</script> "quoted" & <b>x</b>';
foreach ( vance_hub_home_text_defaults() as $TMOD => $TROW ) {
	// Member texts print for a signed-in member. Every other text prints for a visitor.
	$AS_MEMBER = 'join_member' === $TROW['group'];
	$TOKEN     = 'Saved wording for ' . $TMOD;

	arrange( array( 'vance_hub_block_order' => 'join', $TMOD => $TOKEN ) );
	$H = render_home( $AS_MEMBER );
	check( "text $TMOD: a saved value is printed", strpos( $H, $TOKEN ) !== false );
	// The needle starts with ">" so it matches the start of an element's text. A bare
	// substring would match the hero sub-text, which also contains "Gastro Self-Assessment".
	check( "text $TMOD: the default is not printed once a value is saved", strpos( $H, '>' . esc_html( $TROW['default'] ) ) === false );
	check( "text $TMOD: the fixed note still prints", strpos( $H, $NOTE_TEXT ) !== false );

	arrange( array( 'vance_hub_block_order' => 'join', $TMOD => " \t  " ) );
	$H = render_home( $AS_MEMBER );
	check( "text $TMOD: spaces only give the default", strpos( $H, '>' . esc_html( $TROW['default'] ) ) !== false );

	arrange( array( 'vance_hub_block_order' => 'join', $TMOD => $MARKUP ) );
	$H = render_home( $AS_MEMBER );
	check( "text $TMOD: markup is escaped in the page", strpos( $H, esc_html( $MARKUP ) ) !== false );
	check( "text $TMOD: markup never reaches the page raw", strpos( $H, '<script>alert(1)</script>' ) === false && strpos( $H, '<b>x</b>' ) === false );
}

/* ---- 22. counts, rendered: the limits and the values around them ---------- */
section( '22. Counts, rendered: minimum, maximum, below, above, not a number' );

/** One page with one block on and one count set. */
function render_with_count( $mod, $value, $order ) {
	arrange();
	seed_counts_home();
	$GLOBALS['MODS']['vance_hub_block_order'] = $order;
	$GLOBALS['MODS'][ $mod ]                  = $value;
	return render_home( false );
}

// Latest by section: two columns. The default is 3, the range 1 to 6.
$H = render_with_count( 'vance_hub_count_sections', 1, 'sections' );
check( 'count sections, minimum 1: one card in each of two columns', substr_count( $H, 'class="vhub-sec-card"' ) === 2 && query_limits() === array( 1, 1 ) );
$H = render_with_count( 'vance_hub_count_sections', 6, 'sections' );
check( 'count sections, maximum 6: six cards in each of two columns (12), each column asks for 6', substr_count( $H, 'class="vhub-sec-card"' ) === 12 && query_limits() === array( 6, 6 ) );
$H = render_with_count( 'vance_hub_count_sections', 0, 'sections' );
check( 'count sections, below the minimum (0): the minimum, 1 each (2 cards)', substr_count( $H, 'class="vhub-sec-card"' ) === 2 && query_limits() === array( 1, 1 ) );
$H = render_with_count( 'vance_hub_count_sections', 7, 'sections' );
check( 'count sections, above the maximum (7): the maximum, 6 each (12 cards)', substr_count( $H, 'class="vhub-sec-card"' ) === 12 && query_limits() === array( 6, 6 ) );
$H = render_with_count( 'vance_hub_count_sections', 'abc', 'sections' );
check( 'count sections, not a number: the default, 3 each (6 cards)', substr_count( $H, 'class="vhub-sec-card"' ) === 6 && query_limits() === array( 3, 3 ) );

// More to read: one list. The default is 6, the range 3 to 12.
$H = render_with_count( 'vance_hub_count_more', 3, 'more' );
check( 'count more, minimum 3: three cards, the query asks for 3', substr_count( $H, 'class="vhub-card"' ) === 3 && query_limits() === array( 3 ) );
$H = render_with_count( 'vance_hub_count_more', 12, 'more' );
check( 'count more, maximum 12: twelve cards, the query asks for 12', substr_count( $H, 'class="vhub-card"' ) === 12 && query_limits() === array( 12 ) );
$H = render_with_count( 'vance_hub_count_more', 2, 'more' );
check( 'count more, below the minimum (2): three cards', substr_count( $H, 'class="vhub-card"' ) === 3 && query_limits() === array( 3 ) );
$H = render_with_count( 'vance_hub_count_more', 13, 'more' );
check( 'count more, above the maximum (13): twelve cards', substr_count( $H, 'class="vhub-card"' ) === 12 && query_limits() === array( 12 ) );
$H = render_with_count( 'vance_hub_count_more', 'abc', 'more' );
check( 'count more, not a number: the default, six cards', substr_count( $H, 'class="vhub-card"' ) === 6 && query_limits() === array( 6 ) );

// From the library: one feature and a list, so the total is the count. Range 2 to 7.
$H = render_with_count( 'vance_hub_count_library', 2, 'library' );
check( 'count library, minimum 2: one feature and one list item (two articles), the query asks for 2', substr_count( $H, 'class="vhub-feat"' ) + substr_count( $H, 'class="vhub-lib-item"' ) === 2 && query_limits() === array( 2 ) );
$H = render_with_count( 'vance_hub_count_library', 7, 'library' );
check( 'count library, maximum 7: seven articles, the query asks for 7', substr_count( $H, 'class="vhub-feat"' ) + substr_count( $H, 'class="vhub-lib-item"' ) === 7 && query_limits() === array( 7 ) );
$H = render_with_count( 'vance_hub_count_library', 1, 'library' );
check( 'count library, below the minimum (1): two articles', substr_count( $H, 'class="vhub-feat"' ) + substr_count( $H, 'class="vhub-lib-item"' ) === 2 && query_limits() === array( 2 ) );
$H = render_with_count( 'vance_hub_count_library', 8, 'library' );
check( 'count library, above the maximum (8): seven articles', substr_count( $H, 'class="vhub-feat"' ) + substr_count( $H, 'class="vhub-lib-item"' ) === 7 && query_limits() === array( 7 ) );
$H = render_with_count( 'vance_hub_count_library', 'abc', 'library' );
check( 'count library, not a number: the default, four articles', substr_count( $H, 'class="vhub-feat"' ) + substr_count( $H, 'class="vhub-lib-item"' ) === 4 && query_limits() === array( 4 ) );

// Recipes: two lists (recently added, most popular). Range 1 to 4. The quotes query runs first.
$H = render_with_count( 'vance_hub_count_recipes', 1, 'community' );
check( 'count recipes, minimum 1: one recipe in each list (two), each query asks for 1', substr_count( $H, 'class="vhub-recipe"' ) === 2 && query_limits() === array( 4, 1, 1 ) );
$H = render_with_count( 'vance_hub_count_recipes', 4, 'community' );
check( 'count recipes, maximum 4: four in each list (eight), each query asks for 4', substr_count( $H, 'class="vhub-recipe"' ) === 8 && query_limits() === array( 4, 4, 4 ) );
$H = render_with_count( 'vance_hub_count_recipes', 0, 'community' );
check( 'count recipes, below the minimum (0): one in each list (two)', substr_count( $H, 'class="vhub-recipe"' ) === 2 && query_limits() === array( 4, 1, 1 ) );
$H = render_with_count( 'vance_hub_count_recipes', 5, 'community' );
check( 'count recipes, above the maximum (5): four in each list (eight)', substr_count( $H, 'class="vhub-recipe"' ) === 8 && query_limits() === array( 4, 4, 4 ) );
$H = render_with_count( 'vance_hub_count_recipes', 'abc', 'community' );
check( 'count recipes, not a number: the default, two in each list (four)', substr_count( $H, 'class="vhub-recipe"' ) === 4 && query_limits() === array( 4, 2, 2 ) );

// Community quotes: range 1 to 4, default 4. The quotes query runs first.
$H = render_with_count( 'vance_hub_count_quotes', 1, 'community' );
check( 'count quotes, minimum 1: one quote, the query asks for 1', substr_count( $H, 'vhub-community__card--quote' ) === 1 && query_limits() === array( 1, 2, 2 ) );
$H = render_with_count( 'vance_hub_count_quotes', 4, 'community' );
check( 'count quotes, maximum 4: four quotes, the query asks for 4', substr_count( $H, 'vhub-community__card--quote' ) === 4 && query_limits() === array( 4, 2, 2 ) );
$H = render_with_count( 'vance_hub_count_quotes', 0, 'community' );
check( 'count quotes, below the minimum (0): one quote', substr_count( $H, 'vhub-community__card--quote' ) === 1 && query_limits() === array( 1, 2, 2 ) );
$H = render_with_count( 'vance_hub_count_quotes', 9, 'community' );
check( 'count quotes, above the maximum (9): four quotes', substr_count( $H, 'vhub-community__card--quote' ) === 4 && query_limits() === array( 4, 2, 2 ) );
$H = render_with_count( 'vance_hub_count_quotes', 'abc', 'community' );
check( 'count quotes, not a number: the default, four quotes', substr_count( $H, 'vhub-community__card--quote' ) === 4 && query_limits() === array( 4, 2, 2 ) );

/* ---- 23. tool cards, rendered: the cases the earlier checks did not cover -- */
section( '23. Tool cards, rendered: more cases' );

arrange( array( 'vance_hub_tool_2_pick' => 'none' ) );
$GLOBALS['ATTACH_URL'] = array( 41 => 'https://example.test/uploads/custom-large.jpg', 42 => 'https://example.test/uploads/no-alt-large.jpg' );
$GLOBALS['META']       = array( 41 => array( '_wp_attachment_image_alt' => 'A custom picture' ), 42 => array( '_wp_attachment_image_alt' => '' ) );
$H = render_home( false );
check( 'tools: one hidden slot (card 2) prints three tiles, in slot order', tool_order( $H ) === array( 'survey', 'recipes', 'discounts' ) );

arrange( array( 'vance_hub_tool_1_link' => 'https://example.test/own/?a=1&b="2"' ) );
$H = render_home( false );
check( 'tools: a link with an ampersand and quotes is escaped in the href', strpos( $H, 'href="https://example.test/own/?a=1&amp;b=&quot;2&quot;"' ) !== false );

arrange( array( 'vance_hub_tool_3_text' => '<i>Bold</i> text' ) );
$H = render_home( false );
check( 'tools: a card text with markup is escaped, not raw', strpos( $H, '&lt;i&gt;Bold&lt;/i&gt; text' ) !== false && strpos( $H, '<i>Bold' ) === false );

arrange( array( 'vance_hub_tool_4_button' => 'Go "now" & <b>' ) );
$H = render_home( false );
check( 'tools: a card button with markup is escaped, not raw', strpos( $H, 'Go &quot;now&quot; &amp; &lt;b&gt;' ) !== false && strpos( $H, '<b>' ) === false );

arrange( array( 'vance_hub_tool_2_image' => 42 ) );
$GLOBALS['ATTACH_URL'] = array( 42 => 'https://example.test/uploads/no-alt-large.jpg' );
$GLOBALS['META']       = array( 42 => array( '_wp_attachment_image_alt' => '' ) );
$H = render_home( false );
check( 'tools: a custom image with no media alt prints src with alt="" (the attribute is present)', strpos( $H, 'src="https://example.test/uploads/no-alt-large.jpg" alt=""' ) !== false );
check( 'tools: that image does not carry the standard alt of the card', strpos( $H, 'Bowls of oats' ) === false );

arrange( array( 'vance_hub_tool_1_image' => 99 ) );
$GLOBALS['ATTACH_URL'] = array();
$GLOBALS['META']       = array();
$H = render_home( false );
check( 'tools: an image id that does not resolve keeps the standard alt and image', strpos( $H, 'alt="Two women walking and talking together on a sunlit city street"' ) !== false && strpos( $H, 'src="https://example.test/wp-content/themes/vance-health-hub/assets/img/gi-health/ulcerative-colitis.webp' ) !== false );

arrange();
$STANDARD_CARDS = vance_hub_tools();
arrange( array(
	'vance_hub_tool_1_pick'   => 'none',
	'vance_hub_tool_2_title'  => 'Changed title',
	'vance_hub_tool_2_text'   => 'Changed text',
	'vance_hub_tool_2_button' => 'Changed button',
	'vance_hub_tool_2_link'   => 'https://example.test/changed/',
	'vance_hub_tool_3_image'  => 41,
	'vance_hub_tool_4_pick'   => 'none',
) );
$GLOBALS['ATTACH_URL'] = array( 41 => 'https://example.test/uploads/custom-large.jpg' );
check( 'tools: vance_hub_tools() returns the same four standard cards whatever the tool settings say', vance_hub_tools() === $STANDARD_CARDS && 4 === count( vance_hub_tools() ) );
unset( $GLOBALS['ATTACH_URL'] );
$GLOBALS['META'] = array();

/* ---- 24. the rendered page never prints a bare placeholder ----------------- */
section( '24. The rendered page: no unfilled placeholder with each count at its limit' );

$H = render_with_count( 'vance_hub_count_more', 3, 'more' );
check( 'placeholders: a count at its minimum leaves no %d or %s in the page', strpos( $H, '%d' ) === false && strpos( $H, '%s' ) === false );
$H = render_with_count( 'vance_hub_count_recipes', 4, 'community' );
check( 'placeholders: a count at its maximum leaves no %d or %s in the page', strpos( $H, '%d' ) === false && strpos( $H, '%s' ) === false );



/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
