<?php
/**
 * Tests for the VanceHubClub site header: inc/hub-header.php,
 * template-parts/header-hub.php, header.php, and the member bar that
 * front-page-hub.php no longer prints twice.
 *
 * Same shape as hub-layout.test.php: WordPress stubs, a bag of globals the
 * stubs read, check() lines, and exit 1 on any failure. Nothing here loads
 * WordPress. hub-layout.php and hub-header.php are loaded for real, and so are
 * header.php, the template part and front-page-hub.php (rendered, not read).
 *
 * The escaping stubs mirror WordPress where a check depends on it: esc_url()
 * drops a scheme outside its allowlist, sanitize_key() lowercases and strips
 * like WordPress (and throws on an array under PHP 8), esc_html() and
 * esc_attr() do not double-encode.
 *
 * WHAT IS COVERED
 *   1  the decision, pure: saved switch, preview value, who may preview
 *   2  the decision read from the request ($_GET and the capability)
 *   3  the hooks: menu location, assets, body class, Customizer switch
 *   4  the fallback navigation: real pages only, never Community, current item
 *   5  an assigned menu wins over the fallback
 *   6  the markup: guest and member buttons, VANCE-Ai, search, phone menu button
 *   7  header.php: the hub header replaces the classic one, and only then
 *   8  escaping
 *   9  the member bar renders once, on the hub home and on other pages
 */

define( 'ABSPATH', true );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );
date_default_timezone_set( 'UTC' );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';
$GLOBALS['THEME_DIR'] = $THEME;
$GLOBALS['HOOKS']     = array();

/* ---- state the stubs read; arrange() resets all of it ---------------------- */

function reset_state() {
	$GLOBALS['MODS']          = array();
	$GLOBALS['CAPS']          = array();
	$GLOBALS['IS_FRONT']      = false;
	$GLOBALS['IS_SEARCH']     = false;
	$GLOBALS['LOGGED_IN']     = false;
	$GLOBALS['TODAY']         = '2026-11-18';
	$GLOBALS['PAGES']         = array();   // slug => permalink; a slug in this list "exists".
	$GLOBALS['MENU_ASSIGNED'] = false;
	$GLOBALS['MENU_HTML']     = '';
	$GLOBALS['MENU_ARGS']     = null;
	$GLOBALS['I18N']          = array();
	$GLOBALS['TRANSIENTS']    = array();
	$GLOBALS['USERS_TOTAL']   = 0;
	$GLOBALS['USERS_WEEK']    = 0;
	$GLOBALS['ENQUEUED']      = array();
	$GLOBALS['REGISTERED_MENUS'] = array();
	$GLOBALS['SEARCH_QUERY']  = '';
	$GLOBALS['DASH_SLUG']     = false;     // is_page( 'dashboard' )
	$GLOBALS['DASH_TEMPLATE'] = false;     // is_page_template( 'page-dashboard.php' )
	$GLOBALS['PAGE_LOOKUPS']  = 0;         // get_page_by_path() calls
	$GLOBALS['PAGE_STATUS']   = array();   // slug => post_status; a page with no entry is published
	$GLOBALS['COMMUNITY_ON']  = false;     // vance_hub_community_active()
	$GLOBALS['PRIMARY_ASSIGNED'] = false;  // a menu is assigned to location primary-menu (the original menu)
	$GLOBALS['PRIMARY_HTML']  = '';        // what wp_nav_menu() returns for primary-menu, before the filter
	$GLOBALS['PRIMARY_ARGS']  = null;      // the arguments of the last wp_nav_menu( primary-menu ) call
	if ( function_exists( 'vance_hub_header_existing_page_url' ) ) {
		vance_hub_header_existing_page_url( '', '', true ); // empty the kept page lookups
	}
	$_GET                     = array();
	$_SERVER['REQUEST_URI']   = '/';
}

function arrange( array $mods = array(), array $caps = array(), array $get = array(), $uri = '/' ) {
	reset_state();
	$GLOBALS['MODS']        = $mods;
	$GLOBALS['CAPS']        = $caps;
	$_GET                   = $get;
	$_SERVER['REQUEST_URI'] = $uri;
}

reset_state();

/* ---- a notice in the code under test is a failure, not a pass ------------- */

set_error_handler( function ( $no, $str, $file, $line ) {
	if ( ! ( error_reporting() & $no ) ) {
		return false;
	}
	throw new ErrorException( $str, 0, $no, $file, $line );
} );

/* ---- WordPress stubs ------------------------------------------------------ */

function sanitize_key( $key ) {
	// Mirrors WordPress; on an array this throws a TypeError under PHP 8, as it does there.
	$key = strtolower( $key );
	return preg_replace( '/[^a-z0-9_\-]/', '', $key );
}
function wp_unslash( $value ) { return is_string( $value ) ? stripslashes( $value ) : $value; }
function current_user_can( $cap ) { return ! empty( $GLOBALS['CAPS'][ $cap ] ); }
function is_user_logged_in() { return (bool) $GLOBALS['LOGGED_IN']; }
function is_front_page() { return (bool) $GLOBALS['IS_FRONT']; }
function is_search() { return (bool) $GLOBALS['IS_SEARCH']; }
function get_search_query() { return $GLOBALS['SEARCH_QUERY']; }
function vance_get_theme_mod( $key, $default = '' ) {
	return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
}
function get_template_directory() { return $GLOBALS['THEME_DIR']; }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/vance-health-hub'; }
function add_filter( $tag, $callback, $priority = 10, $accepted = 1 ) {
	$GLOBALS['HOOKS'][ $tag ][ $priority ][] = $callback;
	return true;
}
function add_action( $tag, $callback, $priority = 10, $accepted = 1 ) { return add_filter( $tag, $callback, $priority, $accepted ); }
function apply_filters( $tag, $value ) { return $value; }
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false ) {
	$GLOBALS['ENQUEUED'][ 'style:' . $handle ] = array( 'src' => $src, 'deps' => $deps );
}
function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
	$GLOBALS['ENQUEUED'][ 'script:' . $handle ] = array( 'src' => $src, 'deps' => $deps, 'in_footer' => $in_footer );
}
function register_nav_menu( $location, $description ) { $GLOBALS['REGISTERED_MENUS'][ $location ] = $description; }
function has_nav_menu( $location ) {
	if ( 'primary-menu' === $location ) { return (bool) $GLOBALS['PRIMARY_ASSIGNED']; }
	return 'vance-hub-primary' === $location && $GLOBALS['MENU_ASSIGNED'];
}
function wp_nav_menu( $args = array() ) {
	if ( 'primary-menu' === ( $args['theme_location'] ?? '' ) ) {
		$GLOBALS['PRIMARY_ARGS'] = $args;
		// WordPress passes the item markup through wp_nav_menu_items before it prints it.
		return vance_hub_header_menu_community( $GLOBALS['PRIMARY_HTML'], (object) $args );
	}
	$GLOBALS['MENU_ARGS'] = $args;
	return $GLOBALS['MENU_HTML'];
}
function vance_hub_community_active() { return (bool) $GLOBALS['COMMUNITY_ON']; }
function get_transient( $key ) { return array_key_exists( $key, $GLOBALS['TRANSIENTS'] ) ? $GLOBALS['TRANSIENTS'][ $key ] : false; }
function set_transient( $key, $value, $expiration = 0 ) { $GLOBALS['TRANSIENTS'][ $key ] = $value; return true; }
class WP_User_Query {
	private $args;
	public function __construct( $args ) { $this->args = $args; }
	public function get_total() { return isset( $this->args['date_query'] ) ? $GLOBALS['USERS_WEEK'] : $GLOBALS['USERS_TOTAL']; }
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
function is_page( $page = '' ) { return 'dashboard' === $page && $GLOBALS['DASH_SLUG']; }
function is_page_template( $template = '' ) { return 'page-dashboard.php' === $template && $GLOBALS['DASH_TEMPLATE']; }
function get_page_by_path( $slug ) {
	$GLOBALS['PAGE_LOOKUPS']++;
	if ( ! isset( $GLOBALS['PAGES'][ $slug ] ) ) { return null; }
	$status = $GLOBALS['PAGE_STATUS'][ $slug ] ?? 'publish';
	return (object) array( 'ID' => 1, 'slug' => $slug, 'post_status' => $status );
}
function get_permalink( $post ) { return $GLOBALS['PAGES'][ $post->slug ]; }
function add_query_arg( $key, $value, $url ) {
	return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . $key . '=' . rawurlencode( (string) $value );
}
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_url( $url ) {
	// A scheme outside the allowlist is dropped and the attribute is left empty.
	$url = (string) $url;
	if ( preg_match( '#^([a-z][a-z0-9+.\-]*):#i', $url, $m ) && ! in_array( strtolower( $m[1] ), array( 'http', 'https', 'mailto', 'tel' ), true ) ) {
		return '';
	}
	return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8', false );
}
function esc_html_e( $text, $domain = '' ) { echo esc_html( __( $text, $domain ) ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( __( $text, $domain ) ); }
function esc_html__( $text, $domain = '' ) { return esc_html( __( $text, $domain ) ); }
function wp_specialchars_decode( $text ) { return htmlspecialchars_decode( (string) $text, ENT_QUOTES ); }
function wp_strip_all_tags( $text ) { return strip_tags( (string) $text ); }
function number_format_i18n( $n ) { return number_format( (float) $n ); }
function __( $text, $domain = '' ) { return array_key_exists( $text, $GLOBALS['I18N'] ) ? $GLOBALS['I18N'][ $text ] : $text; }
function _n( $single, $plural, $number, $domain = '' ) { return 1 == $number ? $single : $plural; }

/* header.php calls these; they print nothing that a check reads. */
function language_attributes() {}
function bloginfo( $what ) {}
function wp_head() {}
function wp_body_open() {}
function body_class() { echo 'class="x"'; }
function get_header() { include $GLOBALS['THEME_DIR'] . '/header.php'; }
function get_footer() {}
function get_template_part( $slug ) { include $GLOBALS['THEME_DIR'] . '/' . $slug . '.php'; }

/* The home page reads these from elsewhere in the theme; no data, so nothing renders. */
function vance_quiz_steps() { return array(); }
function vance_page_hero_spotlight_recipe_stats() { return array(); }
function vance_gi_page_url( $slug ) { return 'https://example.test/gi/' . $slug . '/'; }
function vance_gi_hub_url() { return 'https://example.test/gastro-health-explained/'; }
class WP_Query {
	public $posts = array();
	public function __construct( $args ) {}
}
function get_term_by( $field, $value, $taxonomy ) { return false; }
function get_the_title( $post ) { return ''; }
function get_post_meta( $post_id, $key = '', $single = false ) { return ''; }
function update_post_thumbnail_cache( $query ) {}

/* ---- load the code under test --------------------------------------------- */

// No mbstring on this machine. This polyfill upper-cases ASCII and the one accented letter the label check uses,
// so the expected value is the same here and on a server with mbstring. Where mbstring exists, the real function runs.
if ( ! function_exists( 'mb_strtoupper' ) ) {
	function mb_strtoupper( $s, $enc = 'UTF-8' ) {
		return strtr( strtoupper( (string) $s ), array( chr( 0xC3 ) . chr( 0xA9 ) => chr( 0xC3 ) . chr( 0x89 ) ) );
	}
}

// front-page-hub.php calls vance_dashboard_link(): load the real helper and its one missing stub.
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
require_once $THEME . '/inc/dashboard-url.php';
require_once $THEME . '/inc/condition-priority.php';
require_once $THEME . '/inc/hub-layout.php';
require_once $THEME . '/inc/hub-home-settings.php';
require_once $THEME . '/inc/hub-header.php';

// The condition registry lives in functions.php, which cannot be loaded here.
// Lift the one function the home template reads, as hub-layout.test.php does.
$SRC = file_get_contents( $THEME . '/functions.php' );
preg_match( '/function vance_gi_condition_cards\(\) \{.*?\r?\n\}\r?\n/s', $SRC, $LIFT );
if ( empty( $LIFT[0] ) ) {
	fwrite( STDERR, "could not lift vance_gi_condition_cards() from functions.php\n" );
	exit( 2 );
}
eval( $LIFT[0] );

/* ---- rendering helpers ------------------------------------------------------ */

/** Output of the template part. */
function render_part() {
	ob_start();
	include $GLOBALS['THEME_DIR'] . '/template-parts/header-hub.php';
	return ob_get_clean();
}

/** Output of header.php, as a page would print it. */
function render_header_php() {
	ob_start();
	include $GLOBALS['THEME_DIR'] . '/header.php';
	return ob_get_clean();
}

/** Output of the hub home template, which calls get_header() first. */
function render_home() {
	ob_start();
	include $GLOBALS['THEME_DIR'] . '/front-page-hub.php';
	return ob_get_clean();
}

/** How many times a plain substring appears. */
function count_of( $html, $needle ) { return substr_count( $html, $needle ); }

/** The pages the fallback navigation can link to, all present. */
function all_pages() {
	return array(
		'tools-resources'         => 'https://example.test/tools-resources/',
		'gastro-health-explained' => 'https://example.test/gastro-health-explained/',
		'knowledgebase'           => 'https://example.test/knowledgebase/',
	);
}

/** Labels of the fallback items, in order. */
function item_labels() {
	return array_column( vance_hub_header_fallback_items(), 'label' );
}

/* ===================================================================== */

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/* ---- 1. the decision, pure ------------------------------------------------- */
section( '1. The decision (pure)' );

check( 'resolve: switch off, no query, no preview is classic', vance_hub_header_resolve( false, '', false ) === false );
check( 'resolve: a null mod is classic', vance_hub_header_resolve( null, '', false ) === false );
check( 'resolve: switch on is the hub header', vance_hub_header_resolve( true, '', false ) === true );
check( 'resolve: the saved string "1" is on', vance_hub_header_resolve( '1', '', false ) === true );
check( 'resolve: the saved empty string is off', vance_hub_header_resolve( '', '', false ) === false );
check( 'resolve: admin preview hub overrides an off switch', vance_hub_header_resolve( false, 'hub', true ) === true );
check( 'resolve: admin preview classic overrides an on switch', vance_hub_header_resolve( true, 'classic', true ) === false );
check( 'resolve: a visitor hub query cannot turn the switch on', vance_hub_header_resolve( false, 'hub', false ) === false );
check( 'resolve: a visitor classic query cannot turn the switch off', vance_hub_header_resolve( true, 'classic', false ) === true );
check( 'resolve: the admin query is case-insensitive', vance_hub_header_resolve( false, 'HUB', true ) === true );
check( 'resolve: the admin query is trimmed', vance_hub_header_resolve( false, " hub \t", true ) === true );
check( 'resolve: an unknown query value falls through to the switch (on)', vance_hub_header_resolve( true, 'banana', true ) === true );
check( 'resolve: an unknown query value falls through to the switch (off)', vance_hub_header_resolve( false, 'banana', true ) === false );
check( 'resolve: an array query is ignored', vance_hub_header_resolve( true, array( 'classic' ), true ) === true );
check( 'resolve: an array query cannot turn the header on', vance_hub_header_resolve( false, array( 'hub' ), true ) === false );

/* ---- 2. the decision read from the request --------------------------------- */
section( '2. The decision from the request' );

arrange();
check( 'active: nothing saved, no query is classic', vance_hub_header_active() === false );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
check( 'active: switch on is the hub header for a visitor', vance_hub_header_active() === true );
arrange( array( 'vance_hub_header' => false ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'active: an administrator previews the hub header with the switch off', vance_hub_header_active() === true );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'classic' ) );
check( 'active: ?vhh_layout=classic forces the classic header for an administrator', vance_hub_header_active() === false );
arrange( array( 'vance_hub_header' => false ), array(), array( 'vhh_layout' => 'hub' ) );
check( 'active: a visitor with ?vhh_layout=hub still gets the classic header', vance_hub_header_active() === false );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ), array(), array( 'vhh_layout' => 'classic' ) );
check( 'active: a visitor with ?vhh_layout=classic still gets the hub header', vance_hub_header_active() === true );
arrange( array( 'vance_hub_header' => false ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'HUB' ) );
check( 'active: the preview value is read through sanitize_key (upper case works)', vance_hub_header_active() === true );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => array( 'classic' ) ) );
$err = '';
try { $res = vance_hub_header_active(); } catch ( Throwable $e ) { $res = null; $err = $e->getMessage(); }
check( 'active: ?vhh_layout[]= does not throw (' . $err . ') and the switch decides', $res === true );
arrange( array( 'vance_hub_header' => false ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'banana' ) );
check( 'active: an unknown preview value falls through to the switch', vance_hub_header_active() === false );
arrange( array( 'vance_hub_header' => false ), array( 'edit_theme_options' => true ), array() );
check( 'active: an administrator with no query gets the saved switch', vance_hub_header_active() === false );

// The master switch (Home layout) over the header part.
arrange( array( 'vance_hub_header' => true ) );
check( 'master: classic with the header box ticked is off for a visitor', vance_hub_header_active() === false );
arrange( array( 'vance_hub_header' => true ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'master: classic with an admin ?vhh_layout=hub preview is on', vance_hub_header_active() === true );
arrange( array( 'vance_home_layout' => 'hub' ) );
check( 'master: hub with the header box never saved is on (default on)', vance_hub_header_active() === true );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => false ) );
check( 'master: hub with the header box saved off is off', vance_hub_header_active() === false );

/* ---- 3. the hooks ------------------------------------------------------------ */
section( '3. Hooks: menu location, assets, body class, Customizer' );

check( 'hooks: after_setup_theme registers the menu location', isset( $GLOBALS['HOOKS']['after_setup_theme'][20] ) && in_array( 'vance_hub_header_register_menu', $GLOBALS['HOOKS']['after_setup_theme'][20], true ) );
arrange();
vance_hub_header_register_menu();
check( 'menu: the location is vance-hub-primary', isset( $GLOBALS['REGISTERED_MENUS']['vance-hub-primary'] ) );
check( 'menu: the location is named "VanceHubClub header menu"', ( $GLOBALS['REGISTERED_MENUS']['vance-hub-primary'] ?? '' ) === 'VanceHubClub header menu' );
check( 'menu: the classic primary-menu location is not touched', ! isset( $GLOBALS['REGISTERED_MENUS']['primary-menu'] ) );

check( 'hooks: assets are enqueued on wp_enqueue_scripts', isset( $GLOBALS['HOOKS']['wp_enqueue_scripts'][20] ) && in_array( 'vance_hub_header_enqueue_assets', $GLOBALS['HOOKS']['wp_enqueue_scripts'][20], true ) );
arrange();
vance_hub_header_enqueue_assets();
check( 'assets: nothing is enqueued while the header is off', empty( $GLOBALS['ENQUEUED'] ) );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
vance_hub_header_enqueue_assets();
check( 'assets: the stylesheet is enqueued when the header is on', isset( $GLOBALS['ENQUEUED']['style:vance-hub-header'] ) );
check( 'assets: the stylesheet depends on vance-main-style', ( $GLOBALS['ENQUEUED']['style:vance-hub-header']['deps'] ?? array() ) === array( 'vance-main-style' ) );
check( 'assets: the stylesheet file is assets/css/hub-header.css and it exists', strpos( $GLOBALS['ENQUEUED']['style:vance-hub-header']['src'] ?? '', '/assets/css/hub-header.css' ) !== false && file_exists( $THEME . '/assets/css/hub-header.css' ) );
check( 'assets: the script is enqueued in the footer and its file exists', ( $GLOBALS['ENQUEUED']['script:vance-hub-header']['in_footer'] ?? false ) === true && file_exists( $THEME . '/assets/js/hub-header.js' ) );
arrange( array( 'vance_hub_header' => false ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
vance_hub_header_enqueue_assets();
check( 'assets: an administrator preview enqueues them too', isset( $GLOBALS['ENQUEUED']['style:vance-hub-header'] ) );

check( 'hooks: the body class filter is registered', isset( $GLOBALS['HOOKS']['body_class'][10] ) && in_array( 'vance_hub_header_body_class', $GLOBALS['HOOKS']['body_class'][10], true ) );
arrange();
check( 'body class: no vhub-header while off', vance_hub_header_body_class( array( 'home' ) ) === array( 'home' ) );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
check( 'body class: vhub-header is added when on, and the other classes stay', vance_hub_header_body_class( array( 'home' ) ) === array( 'home', 'vhub-header' ) );

class HeaderCustomizeFake {
	public $panels   = array();
	public $sections = array();
	public $settings = array();
	public $controls = array();
	public function add_panel( $id, $args = array() ) { $this->panels[ $id ] = $args; }
	public function add_section( $id, $args = array() ) { $this->sections[ $id ] = $args; }
	public function add_setting( $id, $args = array() ) { $this->settings[ $id ] = $args; }
	public function add_control( $id, $args = array() ) { $this->controls[ $id ] = $args; }
}
$WC = new HeaderCustomizeFake();
vance_hub_header_customize_register( $WC );
check( 'customizer: the setting vance_hub_header is registered', isset( $WC->settings['vance_hub_header'] ) );
check( 'customizer: the default is true (the master switch decides whether it shows)', ( $WC->settings['vance_hub_header']['default'] ?? null ) === true );
check( 'customizer: the sanitize callback is vance_hub_sanitize_checkbox', ( $WC->settings['vance_hub_header']['sanitize_callback'] ?? '' ) === 'vance_hub_sanitize_checkbox' );
check( 'customizer: the control is a checkbox in section vance_hub_sec_header', ( $WC->controls['vance_hub_header']['type'] ?? '' ) === 'checkbox' && ( $WC->controls['vance_hub_header']['section'] ?? '' ) === 'vance_hub_sec_header' );
check( 'customizer: the label is "Use the VanceHubClub header"', ( $WC->controls['vance_hub_header']['label'] ?? '' ) === 'Use the VanceHubClub header' );
check( 'hooks: the Customizer registration is hooked', isset( $GLOBALS['HOOKS']['customize_register'][10] ) && in_array( 'vance_hub_header_customize_register', $GLOBALS['HOOKS']['customize_register'][10], true ) );

/* ---- 4. the fallback navigation ---------------------------------------------- */
section( '4. Fallback navigation: real pages only' );

check( 'build: all three URLs give three items in order', array_column( vance_hub_header_build_items( 'a', 'b', 'c' ), 'label' ) === array( 'Tools', 'Conditions', 'Library' ) );
check( 'build: an empty Tools URL leaves Tools out', array_column( vance_hub_header_build_items( '', 'b', 'c' ), 'label' ) === array( 'Conditions', 'Library' ) );
check( 'build: an empty Conditions URL leaves Conditions out',array_column( vance_hub_header_build_items( 'a', '', 'c' ), 'label' ) === array( 'Tools', 'Library' ) );
check( 'build: an empty Library URL leaves Library out', array_column( vance_hub_header_build_items( 'a', 'b', '' ), 'label' ) === array( 'Tools', 'Conditions' ) );
check( 'build: there is never a Community item', ! in_array( 'Community', array_column( vance_hub_header_build_items( 'a', 'b', 'c' ), 'label' ), true ) );

arrange();
$GLOBALS['PAGES'] = all_pages();
$ITEMS = vance_hub_header_fallback_items();
check( 'fallback: with every page present the items are Tools, Conditions, Library', array_column( $ITEMS, 'label' ) === array( 'Tools', 'Conditions', 'Library' ) );
check( 'fallback: Tools points at the tools-resources page', ( $ITEMS[0]['url'] ?? '' ) === 'https://example.test/tools-resources/' );
check( 'fallback: Conditions points at gastro-health-explained', ( $ITEMS[1]['url'] ?? '' ) === 'https://example.test/gastro-health-explained/' );
check( 'fallback: Library points at knowledgebase', ( $ITEMS[2]['url'] ?? '' ) === 'https://example.test/knowledgebase/' );

arrange();
$GLOBALS['PAGES'] = array( 'gastro-health-explained' => 'https://example.test/gastro-health-explained/', 'knowledgebase' => 'https://example.test/knowledgebase/' );
check( 'fallback: with no tools page, Tools is left out (it is not linked to the home page)', item_labels() === array( 'Conditions', 'Library' ) && strpos( vance_hub_header_nav_html(), 'Tools' ) === false && strpos( vance_hub_header_nav_html(), 'href="https://example.test/"' ) === false );

arrange();
$GLOBALS['PAGES'] = array( 'tools-resources' => 'https://example.test/tools-resources/', 'knowledgebase' => 'https://example.test/knowledgebase/' );
check( 'fallback: a missing Conditions page is left out', item_labels() === array( 'Tools', 'Library' ) );

arrange();
$GLOBALS['PAGES'] = array( 'tools-resources' => 'https://example.test/tools-resources/', 'gastro-health-explained' => 'https://example.test/gastro-health-explained/' );
check( 'fallback: a missing Library page is left out', item_labels() === array( 'Tools', 'Conditions' ) );

arrange();
check( 'fallback: with no pages at all there are no items and no list', item_labels() === array() && vance_hub_header_nav_html() === '' );

// The page lookups are kept for the request: three slugs, one lookup each.
arrange();
$GLOBALS['PAGES'] = all_pages();
vance_hub_header_fallback_items();
$FIRST = $GLOBALS['PAGE_LOOKUPS'];
vance_hub_header_fallback_items();
vance_hub_header_nav_html();
check( 'cache: the first build looks pages up (' . $FIRST . ' lookups)', $FIRST > 0 );
check( 'cache: two more builds cause no further page lookups', $GLOBALS['PAGE_LOOKUPS'] === $FIRST );

arrange();
$GLOBALS['PAGES'] = all_pages() + array( 'community' => 'https://example.test/community/' );
check( 'fallback: even when a page called community exists, there is no Community item', ! in_array( 'Community', item_labels(), true ) && stripos( vance_hub_header_nav_html(), 'community' ) === false );

// The current item.
arrange( array(), array(), array(), '/knowledgebase/' );
$GLOBALS['PAGES'] = all_pages();
$NAV = vance_hub_header_nav_html();
check( 'current: /knowledgebase/ marks Library as aria-current', preg_match( '/<a class="vhh-nav__link" href="https:\/\/example\.test\/knowledgebase\/" aria-current="page">Library<\/a>/', $NAV ) === 1 );
check( 'current: exactly one item is marked', substr_count( $NAV, 'aria-current="page"' ) === 1 );
arrange( array(), array(), array(), '/gastro-health-explained/crohns-disease/' );
$GLOBALS['PAGES'] = all_pages();
check( 'current: a page below Conditions marks Conditions', preg_match( '/href="https:\/\/example\.test\/gastro-health-explained\/" aria-current="page">Conditions</', vance_hub_header_nav_html() ) === 1 );
arrange( array(), array(), array(), '/knowledgebase/?s=x&page=2' );
$GLOBALS['PAGES'] = all_pages();
check( 'current: a query string does not stop the match', preg_match( '/aria-current="page">Library</', vance_hub_header_nav_html() ) === 1 );
arrange( array(), array(), array(), '/knowledgebase-archive/' );
$GLOBALS['PAGES'] = all_pages();
check( 'current: a path that only starts with the same letters is not current', strpos( vance_hub_header_nav_html(), 'aria-current' ) === false );
arrange( array(), array(), array(), '/about-us/' );
$GLOBALS['PAGES'] = all_pages();
check( 'current: an unrelated page marks nothing', strpos( vance_hub_header_nav_html(), 'aria-current' ) === false );
arrange( array(), array(), array(), '/' );
$GLOBALS['PAGES'] = array( 'knowledgebase' => 'https://example.test/knowledgebase/' );
check( 'current: on the home page, with no tools page, nothing is marked current', strpos( vance_hub_header_nav_html(), 'aria-current' ) === false );
arrange( array(), array(), array(), '/' );
$GLOBALS['PAGES'] = all_pages();
check( 'current: on the home page, with a tools page, Tools is not marked', strpos( vance_hub_header_nav_html(), 'aria-current' ) === false );
arrange( array(), array(), array(), '/tools-resources/' );
$GLOBALS['PAGES'] = all_pages();
check( 'current: on the tools page, Tools is marked', preg_match( '/href="https:\/\/example\.test\/tools-resources\/" aria-current="page">Tools</', vance_hub_header_nav_html() ) === 1 );

/* ---- 5. an assigned menu wins ------------------------------------------------- */
section( '5. An assigned menu wins' );

arrange();
$GLOBALS['PAGES']         = all_pages();
$GLOBALS['MENU_ASSIGNED'] = true;
$GLOBALS['MENU_HTML']     = '<ul class="vhh-nav__list"><li><a href="https://example.test/custom/">Custom link</a></li></ul>';
$NAV = vance_hub_header_nav_html();
check( 'menu: an assigned menu is what the header prints', $NAV === $GLOBALS['MENU_HTML'] );
check( 'menu: the fallback list is not printed beside it', strpos( $NAV, 'Conditions' ) === false && strpos( $NAV, 'knowledgebase' ) === false );
check( 'menu: it is asked for from the vance-hub-primary location', ( $GLOBALS['MENU_ARGS']['theme_location'] ?? '' ) === 'vance-hub-primary' );
check( 'menu: it returns the markup instead of echoing it', ( $GLOBALS['MENU_ARGS']['echo'] ?? null ) === false );
check( 'menu: it is one level deep (the header has no dropdowns)', ( $GLOBALS['MENU_ARGS']['depth'] ?? null ) === 1 );
check( 'menu: it never asks for the classic primary-menu location', ( $GLOBALS['MENU_ARGS']['theme_location'] ?? '' ) !== 'primary-menu' );

arrange();
$GLOBALS['PAGES']         = all_pages();
$GLOBALS['MENU_ASSIGNED'] = true;
$GLOBALS['MENU_HTML']     = '';
check( 'menu: an assigned menu that prints nothing falls back to the real pages', strpos( vance_hub_header_nav_html(), 'Conditions' ) !== false );

arrange();
$GLOBALS['PAGES']         = all_pages();
$GLOBALS['MENU_ASSIGNED'] = false;
$GLOBALS['MENU_HTML']     = '<ul><li>should not be used</li></ul>';
check( 'menu: with no menu assigned, wp_nav_menu() is not called at all', strpos( vance_hub_header_nav_html(), 'should not be used' ) === false && null === $GLOBALS['MENU_ARGS'] );

/* ---- 6. the markup ------------------------------------------------------------ */
section( '6. The markup: buttons, VANCE-Ai, search, phone menu' );

arrange();
$GLOBALS['PAGES'] = all_pages();
$HTML = render_part();
check( 'guest: Sign in links to /login/', strpos( $HTML, 'href="https://example.test/login/">Sign in<' ) !== false );
check( 'guest: Join free links to /login/?tab=signup', strpos( $HTML, 'href="https://example.test/login/?tab=signup">Join free<' ) !== false );
check( 'guest: there is no My Hub button', strpos( $HTML, 'My Hub' ) === false );
check( 'guest: Join free is the purple button', preg_match( '/class="vhh-btn vhh-btn--purple" href="[^"]*tab=signup"/', $HTML ) === 1 );
check( 'guest: Sign in is the outlined button', preg_match( '/class="vhh-btn vhh-btn--outline[^"]*" href="https:\/\/example\.test\/login\/"/', $HTML ) === 1 );

$GLOBALS['LOGGED_IN'] = true;
$HTML = render_part();
check( 'member: My Hub links to /dashboard/ in purple', preg_match( '/class="vhh-btn vhh-btn--purple" href="https:\/\/example\.test\/dashboard\/">My Hub</', $HTML ) === 1 );
check( 'member: there is no Join free button', strpos( $HTML, 'Join free' ) === false );
check( 'member: there is no Sign in button', strpos( $HTML, 'Sign in' ) === false );

$GLOBALS['LOGGED_IN'] = false;
$HTML = render_part();
preg_match_all( '/<button\b[^>]*data-vance-askai-open[^>]*>.*?<\/button>/s', $HTML, $ASK );
check( 'VANCE-Ai: the button carries data-vance-askai-open', count( $ASK[0] ) >= 1 );
check( 'VANCE-Ai: every button labelled VANCE-Ai carries it', substr_count( $HTML, '<span>VANCE-Ai</span>' ) === count( $ASK[0] ) );
check( 'VANCE-Ai: it is a real button, teal, that opens a dialog', preg_match( '/<button type="button" class="vhh-btn vhh-btn--teal[^"]*" data-vance-askai-open aria-haspopup="dialog">/', $HTML ) === 1 );
check( 'VANCE-Ai: it is not a link to a page', strpos( $HTML, 'href="https://example.test/ask-ai/"' ) === false );

check( 'search: the data-vance-search block is kept', strpos( $HTML, 'data-vance-search' ) !== false );
check( 'search: the field is named s and has an accessible name', strpos( $HTML, 'name="s"' ) !== false && strpos( $HTML, 'aria-label="Search the Hub"' ) !== false );
check( 'search: the toggle keeps the classic class and aria-controls', strpos( $HTML, 'class="vance-header-search__toggle"' ) !== false && strpos( $HTML, 'aria-controls="vance-header-search-field"' ) !== false );
check( 'search: closed by default (aria-expanded false)', preg_match( '/vance-header-search__toggle" aria-expanded="false"/', $HTML ) === 1 );
$GLOBALS['IS_SEARCH'] = true;
check( 'search: open on a search results page', preg_match( '/vance-header-search__toggle" aria-expanded="true"/', render_part() ) === 1 );
$GLOBALS['IS_SEARCH'] = false;

check( 'phone menu: the toggle is a real <button type="button">', preg_match( '/<button type="button" class="vhh-menu-btn" aria-expanded="false" aria-controls="vhh-nav"/', $HTML ) === 1 );
check( 'phone menu: the button has an accessible name', preg_match( '/class="vhh-menu-btn"[^>]*aria-label="Menu"/', $HTML ) === 1 );
check( 'phone menu: the element it controls exists with that id', strpos( $HTML, '<nav class="vhh-nav" id="vhh-nav"' ) !== false );
check( 'phone menu: the nav has an accessible name', preg_match( '/<nav class="vhh-nav" id="vhh-nav" aria-label="Main menu">/', $HTML ) === 1 );
check( 'phone menu: the bars are hidden from screen readers', strpos( $HTML, '<span class="vhh-menu-btn__bars" aria-hidden="true">' ) !== false );
check( 'header: the logo is the existing logo-header.png, linked to the home page', strpos( $HTML, 'src="https://example.test/wp-content/themes/vance-health-hub/assets/img/logo-header.png"' ) !== false && strpos( $HTML, '<a class="vhh-logo" href="https://example.test/">' ) !== false );
check( 'header: the logo has an alt text', strpos( $HTML, 'alt="Vance Medical"' ) !== false );
check( 'header: exactly one <header> element', substr_count( $HTML, '<header ' ) === 1 && substr_count( $HTML, '</header>' ) === 1 );
check( 'header: no Community link anywhere in the markup', stripos( $HTML, 'community' ) === false );
check( 'header: the wp-login style classic hooks are not reused (no .main-nav, no .mobile-menu-toggle)', strpos( $HTML, 'main-nav' ) === false && strpos( $HTML, 'mobile-menu-toggle' ) === false );

// Assets the markup relies on.
$JS  = file_get_contents( $THEME . '/assets/js/hub-header.js' );
// Normalise line endings: a Windows checkout (core.autocrlf) gives CRLF.
$CSS = str_replace( "\r\n", "\n", file_get_contents( $THEME . '/assets/css/hub-header.css' ) );
check( 'js: Escape closes the panel', strpos( $JS, "'Escape'" ) !== false );
check( 'js: aria-expanded is kept in step', strpos( $JS, "setAttribute('aria-expanded'" ) !== false );
check( 'css: no hard-coded border radius (the token scale is used)', preg_match( '/border-radius:\s*+(?!var\(|0\b|2px)/', $CSS ) === 0 );
check( 'css: the 44px touch target is set on the button and the menu button', substr_count( $CSS, 'min-height: 44px' ) >= 2 && strpos( $CSS, 'width: 44px;' ) !== false );

/* ---- 7. header.php ------------------------------------------------------------- */
section( '7. header.php: hub header replaces the classic one, and only then' );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['PAGES'] = all_pages();
$PAGE = render_header_php();
check( 'header.php (on): the hub header is printed', strpos( $PAGE, 'class="vhh-header"' ) !== false );
check( 'header.php (on): the classic <header class="site-header"> is not printed', strpos( $PAGE, 'class="site-header"' ) === false );
check( 'header.php (on): no classic logo-area, main-nav or Mega Menu location', strpos( $PAGE, 'logo-area' ) === false && strpos( $PAGE, 'main-nav' ) === false && strpos( $PAGE, 'header-actions' ) === false );
check( 'header.php (on): the classic hamburger script is not printed', strpos( $PAGE, 'mobile-menu-toggle' ) === false );
check( 'header.php (on): <head> and <body> are still printed', strpos( $PAGE, '<head>' ) !== false && strpos( $PAGE, '<body ' ) !== false );
check( 'header.php (on): the favicon link in <head> is still printed', strpos( $PAGE, 'favicon.png' ) !== false );

arrange();
$PAGE = render_header_php();
check( 'header.php (off): the classic header is printed', strpos( $PAGE, 'class="site-header"' ) !== false );
check( 'header.php (off): the hub header is not printed', strpos( $PAGE, 'vhh-header' ) === false );
check( 'header.php (off): the classic hamburger script is still printed', strpos( $PAGE, 'mobile-menu-toggle' ) !== false );
check( 'header.php (off): no purple member bar is printed by the header', strpos( $PAGE, 'vhub-bar' ) === false );

arrange( array( 'vance_hub_header' => false ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
$PAGE = render_header_php();
check( 'header.php (preview): an administrator with ?vhh_layout=hub sees the hub header', strpos( $PAGE, 'class="vhh-header"' ) !== false && strpos( $PAGE, 'class="site-header"' ) === false );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ), array(), array( 'vhh_layout' => 'classic' ) );
$PAGE = render_header_php();
check( 'header.php: a visitor cannot force the classic header off the hub header', strpos( $PAGE, 'class="vhh-header"' ) !== false );

/* ---- 8. escaping --------------------------------------------------------------- */
section( '8. Escaping' );

arrange();
$GLOBALS['PAGES']             = all_pages();
$GLOBALS['PAGES']['knowledgebase'] = 'javascript:alert(1)';
$GLOBALS['I18N']              = array( 'Tools' => '<script>alert(1)</script>', 'Join free' => '"><img src=x onerror=alert(1)>' );
$GLOBALS['SEARCH_QUERY']      = '"><script>alert(2)</script>';
$HTML = render_part();
check( 'escape: a label with markup is escaped in the navigation', strpos( $HTML, '<script>alert(1)</script>' ) === false && strpos( $HTML, '&lt;script&gt;alert(1)&lt;/script&gt;' ) !== false );
check( 'escape: a button label with markup is escaped', strpos( $HTML, '<img src=x' ) === false && strpos( $HTML, '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;' ) !== false );
check( 'escape: a javascript: page address becomes an empty href', strpos( $HTML, 'javascript:' ) === false && strpos( $HTML, 'href="" >' ) === false && preg_match( '/<a class="vhh-nav__link" href="">Library<\/a>/', $HTML ) === 1 );
check( 'escape: the search query is escaped inside the value attribute', strpos( $HTML, '<script>alert(2)</script>' ) === false && strpos( $HTML, 'value="&quot;&gt;&lt;script&gt;alert(2)&lt;/script&gt;"' ) !== false );
check( 'escape: the only <script> in the header is the fixed vhh-js marker', substr_count( $HTML, '<script' ) === 1 && strpos( $HTML, "<script>document.documentElement.classList.add('vhh-js');</script>" ) !== false );

/* ---- 9. the member bar renders once ---------------------------------------------- */
section( '9. The member bar renders once' );

$BAR = 'class="vhub-bar"';

// Counts, hub header ON, on the hub home page.
arrange( array( 'vance_hub_header' => true, 'vance_home_layout' => 'hub' ) );
$GLOBALS['IS_FRONT']    = true;
$GLOBALS['USERS_TOTAL'] = 1234;
$GLOBALS['USERS_WEEK']  = 17;
$GLOBALS['PAGES']       = all_pages();
$HOME = render_home();
check( 'once: hub header + hub home prints the member bar exactly once', count_of( $HOME, $BAR ) === 1 );
check( 'once: that bar is printed after the header and before <main>', ( $p_h = strpos( $HOME, '</header>' ) ) !== false && ( $p_b = strpos( $HOME, $BAR ) ) !== false && ( $p_m = strpos( $HOME, '<main' ) ) !== false && $p_h < $p_b && $p_b < $p_m );
check( 'once: the bar shows the real total and the week figure', strpos( $HOME, '<strong>1,234 members</strong>' ) !== false && strpos( $HOME, '17 joined this week' ) !== false );
check( 'once: on the hub home the guest link keeps the #vhub-join anchor', strpos( $HOME, 'class="vhub-bar__link" href="#vhub-join"' ) !== false );
check( 'once: the page has one <header> and one <main>', count_of( $HOME, '<header ' ) === 1 && count_of( $HOME, '<main ' ) === 1 );
check( 'once: the home content itself still renders after the bar', strpos( $HOME, 'id="vhub-hero-title"' ) !== false );

// Counts, hub header OFF (classic header), hub home: the home prints it, inside <main>.
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => false ) );
$GLOBALS['IS_FRONT']    = true;
$GLOBALS['USERS_TOTAL'] = 1234;
$GLOBALS['USERS_WEEK']  = 17;
$HOME = render_home();
check( 'once: classic header + hub home still prints the bar once', count_of( $HOME, $BAR ) === 1 );
check( 'once: and it is inside <main>, as before', ( $q_m = strpos( $HOME, '<main' ) ) !== false && ( $q_b = strpos( $HOME, $BAR ) ) !== false && $q_m < $q_b );
check( 'once: the classic header is the one printed', strpos( $HOME, 'class="site-header"' ) !== false && strpos( $HOME, 'vhh-header' ) === false );

// Founding line.
arrange( array( 'vance_hub_header' => true, 'vance_home_layout' => 'hub', 'vance_hub_founding_deadline' => '2026-12-01' ) );
$GLOBALS['IS_FRONT']    = true;
$GLOBALS['USERS_TOTAL'] = 5;
$HOME = render_home();
check( 'once: the Founding Member line also prints once with the hub header', count_of( $HOME, $BAR ) === 1 && count_of( $HOME, 'Join by 1 December 2026 and keep the Founding Member mark.' ) >= 1 );
check( 'once: the bar copy is not repeated by the template (one bar text paragraph)', count_of( $HOME, '<p class="vhub-bar__text">' ) === 1 );

// Data rule: nothing real, no bar at all.
arrange( array( 'vance_hub_header' => true, 'vance_home_layout' => 'hub' ) );
$GLOBALS['IS_FRONT']    = true;
$GLOBALS['USERS_TOTAL'] = 0;
$HOME = render_home();
check( 'data rule: no members and no deadline prints no bar', count_of( $HOME, $BAR ) === 0 && strpos( $HOME, 'vhub-bar' ) === false );

// Not the home page: the header prints the bar on every page.
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ), array(), array(), '/knowledgebase/' );
$GLOBALS['USERS_TOTAL'] = 250;
$GLOBALS['USERS_WEEK']  = 0;
$GLOBALS['PAGES']       = all_pages();
$PAGE = render_header_php();
check( 'every page: another page gets the bar from the header', count_of( $PAGE, $BAR ) === 1 );
check( 'every page: the guest link goes to the sign-up page there, not to an anchor that is not on the page', strpos( $PAGE, 'class="vhub-bar__link" href="https://example.test/login/?tab=signup"' ) !== false && strpos( $PAGE, '#vhub-join' ) === false );
check( 'every page: no "joined this week" text when the week figure is zero', strpos( $PAGE, 'joined this week' ) === false );
$GLOBALS['LOGGED_IN'] = true;
$PAGE = render_header_php();
check( 'every page: a member sees Go to My Hub in the bar and My Hub in the header', strpos( $PAGE, 'Go to My Hub' ) !== false && strpos( $PAGE, '>My Hub<' ) !== false );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ), array(), array(), '/knowledgebase/' );
$GLOBALS['USERS_TOTAL'] = 0;
check( 'every page: with nothing real to show the header prints no bar', count_of( render_header_php(), 'vhub-bar' ) === 0 );
arrange( array(), array(), array(), '/knowledgebase/' );
$GLOBALS['USERS_TOTAL'] = 250;
check( 'every page: with the switch off the header prints no bar', count_of( render_header_php(), 'vhub-bar' ) === 0 );

/* ---- 10. the dashboard keeps the classic header ------------------------------------- */
section( '10. The member dashboard keeps the classic header' );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['DASH_SLUG'] = true;
check( 'dashboard: matched by slug, the hub header is off even with the switch on', vance_hub_header_on_dashboard() === true && vance_hub_header_active() === false );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['DASH_TEMPLATE'] = true;
check( 'dashboard: matched by page template, the hub header is off', vance_hub_header_on_dashboard() === true && vance_hub_header_active() === false );
arrange( array( 'vance_hub_header' => false ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
$GLOBALS['DASH_SLUG'] = true;
check( 'dashboard: an administrator preview does not turn it on either', vance_hub_header_active() === false );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
check( 'dashboard: on any other page the switch still decides', vance_hub_header_on_dashboard() === false && vance_hub_header_active() === true );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['DASH_SLUG'] = true;
$PAGE = render_header_php();
check( 'dashboard: header.php prints the classic header (which page-dashboard.php hides)', strpos( $PAGE, 'class="site-header"' ) !== false );
check( 'dashboard: header.php prints no hub header and no member bar', strpos( $PAGE, 'vhh-header' ) === false && strpos( $PAGE, 'vhub-bar' ) === false );
check( 'dashboard: no vhub-header body class and no assets', vance_hub_header_body_class( array( 'x' ) ) === array( 'x' ) && ( vance_hub_header_enqueue_assets() || empty( $GLOBALS['ENQUEUED'] ) ) );

/* ---- 11. markup and stylesheet details ------------------------------------------------ */
section( '11. Markup and stylesheet details' );

arrange();
$GLOBALS['PAGES'] = all_pages();
$HTML = render_part();
check( 'js marker: the inline script that adds vhh-js to <html> comes before the header', ( $s = strpos( $HTML, "classList.add('vhh-js')" ) ) !== false && $s < strpos( $HTML, '<header ' ) );
check( 'tablet: the in-bar VANCE-Ai button has an aria-label, so it keeps a name when its text is hidden', preg_match( '/vhh-actions__ask" data-vance-askai-open aria-haspopup="dialog" aria-label="VANCE-Ai">/', $HTML ) === 1 );

check( 'css: .vhh-header is sticky at top 0 with the classic header z-index 1000', preg_match( '/\.vhh-header \{\s*position: sticky;\s*top: 0;\s*z-index: 1000;/', $CSS ) === 1 );
check( 'css: the classic header z-index is 1000 too (they must match)', preg_match( '/\.site-header \{[^}]*z-index: 1000;/s', file_get_contents( $THEME . '/assets/css/main.css' ) ) === 1 );
check( 'css: --header-height is 77px under body.vhub-header (desktop)', preg_match( '/body\.vhub-header \{[^}]*--header-height: 77px;/s', $CSS ) === 1 );
check( 'css: --header-height is 61px on a phone', preg_match( '/max-width: 767\.98px\) \{.*body\.vhub-header \{ --header-height: 61px; \}/s', $CSS ) === 1 );
check( 'css: the 77px matches the parts: 64px logo, 6px padding twice, 1px rule', strpos( $CSS, "width: 114px;\n\theight: 64px;" ) !== false && strpos( $CSS, 'padding: 6px 24px;' ) !== false && strpos( $CSS, 'border-bottom: 1px solid var(--vhh-line' ) !== false );
check( 'css: the member bar is a sibling after the header, not inside it (so only the header sticks)', strpos( $HTML, '</header>' ) !== false && substr_count( $HTML, '<header ' ) === 1 );
check( 'css: the phone panel is hidden only under html.vhh-js', strpos( $CSS, 'html.vhh-js .vhh-header .vhh-nav {' ) !== false && strpos( $CSS, '.vhh-header.vhh-js' ) === false );
check( 'css: the menu button is display:none by default and shown only under html.vhh-js', strpos( $CSS, '.vhh-menu-btn { display: none; }' ) !== false && strpos( $CSS, 'html.vhh-js .vhh-menu-btn { display: inline-flex; }' ) !== false );
check( 'css: the actions are pushed right with margin-left:auto', preg_match( '/\.vhh-actions \{[^}]*margin-left: auto;/s', $CSS ) === 1 );
check( 'css: a tablet block (768px to 999px) keeps one row and hides the VANCE-Ai text, not the icon', preg_match( '/min-width: 768px\) and \(max-width: 999\.98px\) \{.*\.vhh-actions__ask span \{[^}]*clip: rect/s', $CSS ) === 1 );
check( 'css: the member bar wrap is border-box', preg_match( '/body\.vhub-header \.vhub-bar \.vhub-wrap \{\s*box-sizing: border-box;/', $CSS ) === 1 );

$FN = file_get_contents( $THEME . '/functions.php' );
check( 'login: the auth-overlay rule that hides the site chrome also hides .vhh-header', strpos( $FN, 'body:has(.vance-auth-overlay) .vhh-header,' ) !== false );
check( 'login: and the member bar', strpos( $FN, 'body:has(.vance-auth-overlay) .vhub-bar,' ) !== false );
check( 'login: both sit inside the same selector list as .site-header (before .site-footer)', preg_match( '/\.site-header,\s*body:has\(\.vance-auth-overlay\) \.vhh-header,\s*body:has\(\.vance-auth-overlay\) \.vhub-bar,\s*body:has\(\.vance-auth-overlay\) \.site-footer,/', $FN ) === 1 );

$WC2 = new HeaderCustomizeFake();
vance_hub_header_customize_register( $WC2 );
check( 'customizer: the description says the original primary menu is used', strpos( $WC2->controls['vance_hub_header']['description'] ?? '', 'original primary menu' ) !== false );
check( 'customizer: and that a short club menu shows only its top level', strpos( $WC2->controls['vance_hub_header']['description'] ?? '', 'only its top level appears' ) !== false );

/* ---- 12. the original primary menu: precedence, Max Mega Menu, Community ----------- */
section( '12. The original primary menu: precedence, Mega Menu, Community' );

$CLUB_HTML  = '<ul class="vhh-nav__list"><li><a href="https://example.test/custom/">Custom link</a></li></ul>';
$PLAIN_HTML = '<ul class="vhh-nav__list"><li class="menu-item"><a href="https://example.test/about-us/">About</a></li></ul>';
$MEGA_HTML  = '<div id="mega-menu-wrap-primary-menu" class="mega-menu-wrap"><ul id="mega-menu-primary-menu" class="mega-menu"><li class="mega-menu-item mega-menu-item-type-post_type mega-menu-item-object-page mega-menu-flyout"><a class="mega-menu-link" href="https://example.test/about-us/">About</a></li></ul></div>';
$COMM_PLAIN = '<li class="menu-item menu-item-type-post_type menu-item-object-page vhh-nav__community"><a href="https://example.test/community/">Community</a></li>';
// The Mega Menu labels are typed in capitals (THE HUB, CONDITIONS), so Community is printed as COMMUNITY there.
$COMM_MEGA  = '<li class="mega-menu-item mega-menu-item-type-post_type mega-menu-item-object-page mega-align-bottom-left mega-menu-flyout vhh-nav__community"><a class="mega-menu-link" href="https://example.test/community/">COMMUNITY</a></li>';

// Precedence: club menu, then the original primary menu, then the real pages.
arrange();
$GLOBALS['PAGES']            = all_pages();
$GLOBALS['MENU_ASSIGNED']    = true;
$GLOBALS['MENU_HTML']        = $CLUB_HTML;
$GLOBALS['PRIMARY_ASSIGNED'] = true;
$GLOBALS['PRIMARY_HTML']     = $PLAIN_HTML;
check( 'precedence: the VanceHubClub menu beats the original primary menu', vance_hub_header_nav_html() === $CLUB_HTML );
check( 'precedence: the original primary menu is not asked for when the club menu is assigned', $GLOBALS['PRIMARY_ARGS'] === null );

arrange();
$GLOBALS['PAGES']            = all_pages();
$GLOBALS['PRIMARY_ASSIGNED'] = true;
$GLOBALS['PRIMARY_HTML']     = $PLAIN_HTML;
check( 'precedence: with no club menu, the original primary menu is printed', vance_hub_header_nav_html() === $PLAIN_HTML );
check( 'precedence: the original menu is asked for at location primary-menu', ( $GLOBALS['PRIMARY_ARGS']['theme_location'] ?? '' ) === 'primary-menu' );
check( 'precedence: the original menu has no depth limit (depth 0 is the whole tree)', ( $GLOBALS['PRIMARY_ARGS']['depth'] ?? 0 ) === 0 );
check( 'precedence: the original menu is returned, not echoed, with the header list class', ( $GLOBALS['PRIMARY_ARGS']['echo'] ?? null ) === false && ( $GLOBALS['PRIMARY_ARGS']['menu_class'] ?? '' ) === 'vhh-nav__list' );
check( 'precedence: the original menu has no container and no fallback markup', ( $GLOBALS['PRIMARY_ARGS']['container'] ?? null ) === false && ( $GLOBALS['PRIMARY_ARGS']['fallback_cb'] ?? null ) === false );
check( 'precedence: the fallback list is not printed beside the original menu', strpos( vance_hub_header_nav_html(), 'Conditions' ) === false );

arrange();
$GLOBALS['PAGES']            = all_pages();
$GLOBALS['PRIMARY_ASSIGNED'] = true;
$GLOBALS['PRIMARY_HTML']     = '';
check( 'precedence: an original menu that prints nothing falls back to the real pages', strpos( vance_hub_header_nav_html(), '>Conditions</a>' ) !== false );

arrange();
$GLOBALS['PAGES']            = all_pages();
$GLOBALS['PRIMARY_ASSIGNED'] = true;
$GLOBALS['PRIMARY_HTML']     = " \n\t ";
check( 'precedence: an original menu that prints only whitespace falls back to the real pages', strpos( vance_hub_header_nav_html(), '>Conditions</a>' ) !== false );

arrange();
$GLOBALS['PAGES']            = all_pages();
$GLOBALS['PRIMARY_ASSIGNED'] = true;
$GLOBALS['PRIMARY_HTML']     = null;
check( 'precedence: an original menu that returns null falls back to the real pages', strpos( vance_hub_header_nav_html(), '>Conditions</a>' ) !== false );

arrange();
$GLOBALS['PAGES']            = all_pages();
$GLOBALS['MENU_ASSIGNED']    = true;
$GLOBALS['MENU_HTML']        = '';
$GLOBALS['PRIMARY_ASSIGNED'] = true;
$GLOBALS['PRIMARY_HTML']     = $PLAIN_HTML;
check( 'precedence: an empty club menu does not hide the original menu', vance_hub_header_nav_html() === $PLAIN_HTML );

// Max Mega Menu detection.
check( 'mega: Max Mega Menu markup is detected', vance_hub_header_nav_is_mega( $MEGA_HTML ) === true );
check( 'mega: a plain nested list is not Mega Menu', vance_hub_header_nav_is_mega( $PLAIN_HTML ) === false );
check( 'mega: the club menu markup is not Mega Menu', vance_hub_header_nav_is_mega( $CLUB_HTML ) === false );
check( 'mega: the fallback list is not Mega Menu', vance_hub_header_nav_is_mega( '<ul class="vhh-nav__list"><li class="vhh-nav__item"><a class="vhh-nav__link" href="https://example.test/tools-resources/">Tools</a></li></ul>' ) === false );
check( 'mega: an empty string is not Mega Menu', vance_hub_header_nav_is_mega( '' ) === false );
check( 'mega: null is not Mega Menu', vance_hub_header_nav_is_mega( null ) === false );
check( 'mega: the item class without the wrap is not enough', vance_hub_header_nav_is_mega( '<li class="mega-menu-item">x</li>' ) === false );

// The Community item: its gates.
arrange();
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON'] = true;
check( 'community li: appears when the hub is on and the page is published', vance_hub_header_community_li( false ) === $COMM_PLAIN );

arrange();
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON'] = false;
check( 'community li: none when the Community hub is off', vance_hub_header_community_li( false ) === '' );
check( 'community li: none in Mega Menu form when the Community hub is off', vance_hub_header_community_li( true ) === '' );

arrange();
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['PAGE_STATUS']  = array( 'community' => 'draft' );
$GLOBALS['COMMUNITY_ON'] = true;
check( 'community li: none when the community page is a draft', vance_hub_header_community_li( false ) === '' );

arrange();
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['PAGE_STATUS']  = array( 'community' => 'private' );
$GLOBALS['COMMUNITY_ON'] = true;
check( 'community li: none when the community page is private', vance_hub_header_community_li( false ) === '' );

arrange();
$GLOBALS['PAGES']        = all_pages();
$GLOBALS['COMMUNITY_ON'] = true;
check( 'community li: none when there is no community page at all', vance_hub_header_community_li( false ) === '' );

// Plain and Mega Menu classes.
arrange();
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON'] = true;
check( 'classes: the plain item uses the menu-item classes', vance_hub_header_community_li( false ) === $COMM_PLAIN );
check( 'classes: the Mega Menu item uses the plugin top-level classes and mega-menu-link', vance_hub_header_community_li( true ) === $COMM_MEGA );

// Current marking.
arrange( array(), array(), array(), '/community/' );
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON'] = true;
check( 'current: on the community page the plain item is marked current', vance_hub_header_community_li( false ) === '<li class="menu-item menu-item-type-post_type menu-item-object-page vhh-nav__community current-menu-item"><a href="https://example.test/community/" aria-current="page">Community</a></li>' );
check( 'current: on the community page the Mega Menu item is marked current', vance_hub_header_community_li( true ) === '<li class="mega-menu-item mega-menu-item-type-post_type mega-menu-item-object-page mega-align-bottom-left mega-menu-flyout vhh-nav__community mega-current-menu-item"><a class="mega-menu-link" href="https://example.test/community/" aria-current="page">COMMUNITY</a></li>' );

arrange( array(), array(), array(), '/community/thread-1/' );
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON'] = true;
check( 'current: a page below Community marks it, as a page below Conditions does', strpos( vance_hub_header_community_li( false ), 'aria-current="page"' ) !== false );

arrange( array(), array(), array(), '/community-guidelines/' );
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON'] = true;
check( 'current: a path that only starts with the same letters is not current', strpos( vance_hub_header_community_li( false ), 'current' ) === false );

arrange( array(), array(), array(), '/about-us/' );
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON'] = true;
check( 'current: an unrelated page marks nothing', strpos( vance_hub_header_community_li( false ), 'current' ) === false );

// Escaping.
arrange();
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON'] = true;
$GLOBALS['I18N']         = array( 'Community' => '<b>Forum</b>' );
$LI = vance_hub_header_community_li( false );
check( 'escape: a label with markup is escaped, not printed', strpos( $LI, '<b>' ) === false && strpos( $LI, '>&lt;b&gt;Forum&lt;/b&gt;</a>' ) !== false );
$LI = vance_hub_header_community_li( true );
check( 'escape: the upper-cased Mega Menu label is still escaped', stripos( $LI, '<b>' ) === false && strpos( $LI, '>&lt;B&gt;FORUM&lt;/B&gt;</a>' ) !== false );
$GLOBALS['I18N']         = array( 'Community' => 'Communauté' );
check( 'label: the Mega Menu label upper-cases accented letters too', strpos( vance_hub_header_community_li( true ), '>COMMUNAUTÉ</a>' ) !== false );
$GLOBALS['I18N']         = array( 'Community' => 'Community forum' );
check( 'label: the Mega Menu label upper-cases a multi-word label as a whole', strpos( vance_hub_header_community_li( true ), '>COMMUNITY FORUM</a>' ) !== false );
$GLOBALS['I18N']         = array( 'Community' => 'Communauté' );
check( 'label: the plain label keeps its case', strpos( vance_hub_header_community_li( false ), '>Communauté</a>' ) !== false );

arrange();
$GLOBALS['PAGES']        = all_pages() + array( 'community' => '"><script>alert(1)</script>' );
$GLOBALS['COMMUNITY_ON'] = true;
$LI = vance_hub_header_community_li( false );
check( 'escape: a page address with markup cannot break out of its href', strpos( $LI, '<script>' ) === false && strpos( $LI, 'href="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"' ) !== false );

arrange();
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'javascript:alert(1)' );
$GLOBALS['COMMUNITY_ON'] = true;
check( 'escape: a javascript: page address leaves an empty href and no scheme', vance_hub_header_community_li( false ) === '<li class="menu-item menu-item-type-post_type menu-item-object-page vhh-nav__community"><a href="">Community</a></li>' );

// The filter: gates and placement.
check( 'filter: registered on wp_nav_menu_items at priority 10', isset( $GLOBALS['HOOKS']['wp_nav_menu_items'][10] ) && in_array( 'vance_hub_header_menu_community', $GLOBALS['HOOKS']['wp_nav_menu_items'][10], true ) );
check( 'filter: declared with two accepted arguments (items and args)', strpos( file_get_contents( $THEME . '/inc/hub-header.php' ), "add_filter( 'wp_nav_menu_items', 'vance_hub_header_menu_community', 10, 2 );" ) !== false );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['PAGES']            = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON']     = true;
$GLOBALS['PRIMARY_ASSIGNED'] = true;
$GLOBALS['PRIMARY_HTML']     = $PLAIN_HTML;
check( 'filter: with the header on, Community is added last to the original menu', vance_hub_header_nav_html() === $PLAIN_HTML . $COMM_PLAIN );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['PAGES']            = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON']     = true;
$GLOBALS['PRIMARY_ASSIGNED'] = true;
$GLOBALS['PRIMARY_HTML']     = $MEGA_HTML;
$SIGNIN_MEGA = '<li class="mega-menu-item mega-menu-item-type-custom mega-menu-item-object-custom mega-align-bottom-left mega-menu-flyout vhh-nav__phone-only"><a class="mega-menu-link" href="' . home_url( '/login/' ) . '">Sign in</a></li>';
$GLOBALS['LOGGED_IN'] = false;
check( 'filter: with Max Mega Menu, a visitor gets Community then the phone-only Sign in item', vance_hub_header_nav_html() === $MEGA_HTML . $COMM_MEGA . $SIGNIN_MEGA );
$GLOBALS['LOGGED_IN'] = true;
check( 'filter: with Max Mega Menu, a member gets Community and no Sign in item', vance_hub_header_nav_html() === $MEGA_HTML . $COMM_MEGA );
$GLOBALS['LOGGED_IN'] = false;
$GLOBALS['PRIMARY_HTML'] = $PLAIN_HTML;
check( 'filter: a plain (non-mega) menu never gets the Sign in item', strpos( vance_hub_header_nav_html(), 'vhh-nav__phone-only' ) === false );

// An editor already put Community in the primary menu: no second one.
$GLOBALS['PRIMARY_HTML'] = $MEGA_HTML . '<li class="mega-menu-item"><a class="mega-menu-link" href="https://example.test/community/">Community</a></li>';
$NAV_DUP = vance_hub_header_nav_html();
check( 'filter: a menu that already links to /community/ gets no second Community item', substr_count( $NAV_DUP, 'href="https://example.test/community/"' ) === 1 && strpos( $NAV_DUP, 'vhh-nav__community' ) === false );
check( 'filter: and still gets the Sign in item', strpos( $NAV_DUP, 'vhh-nav__phone-only' ) !== false );

// A location with an empty menu: nothing is added, so the fallback list is used.
check( 'filter: an empty item list is returned unchanged', vance_hub_header_menu_community( '', (object) array( 'theme_location' => 'primary-menu' ) ) === '' && vance_hub_header_menu_community( "  \n", (object) array( 'theme_location' => 'primary-menu' ) ) === "  \n" );
$GLOBALS['LOGGED_IN'] = false;

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['PAGES']            = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON']     = true;
$GLOBALS['MENU_ASSIGNED']    = true;
$GLOBALS['MENU_HTML']        = $CLUB_HTML;
check( 'filter: the VanceHubClub menu never gets Community added', vance_hub_header_nav_html() === $CLUB_HTML );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON'] = true;
check( 'gate: the footer menu location is unchanged', vance_hub_header_menu_community( $PLAIN_HTML, (object) array( 'theme_location' => 'footer-menu' ) ) === $PLAIN_HTML );
check( 'gate: the VanceHubClub menu location is unchanged', vance_hub_header_menu_community( $PLAIN_HTML, (object) array( 'theme_location' => 'vance-hub-primary' ) ) === $PLAIN_HTML );
check( 'gate: an args object with no theme location is unchanged', vance_hub_header_menu_community( $PLAIN_HTML, (object) array() ) === $PLAIN_HTML );
check( 'gate: arguments given as an array are returned unchanged', vance_hub_header_menu_community( $PLAIN_HTML, array( 'theme_location' => 'primary-menu' ) ) === $PLAIN_HTML );
check( 'gate: null arguments are returned unchanged', vance_hub_header_menu_community( $PLAIN_HTML, null ) === $PLAIN_HTML );
check( 'gate: the original menu gets Community when the header is on', vance_hub_header_menu_community( $PLAIN_HTML, (object) array( 'theme_location' => 'primary-menu' ) ) === $PLAIN_HTML . $COMM_PLAIN );

arrange( array( 'vance_hub_header' => false ) );
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON'] = true;
check( 'gate: the classic header (hub header off) leaves the original menu unchanged', vance_hub_header_menu_community( $PLAIN_HTML, (object) array( 'theme_location' => 'primary-menu' ) ) === $PLAIN_HTML );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON'] = false;
check( 'gate: with the Community hub off the original menu is unchanged', vance_hub_header_menu_community( $PLAIN_HTML, (object) array( 'theme_location' => 'primary-menu' ) ) === $PLAIN_HTML );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['PAGE_STATUS']  = array( 'community' => 'draft' );
$GLOBALS['COMMUNITY_ON'] = true;
check( 'gate: with the community page a draft the original menu is unchanged', vance_hub_header_menu_community( $PLAIN_HTML, (object) array( 'theme_location' => 'primary-menu' ) ) === $PLAIN_HTML );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ), array(), array(), '/' );
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON'] = true;
$GLOBALS['DASH_SLUG']    = true;
check( 'gate: on the member dashboard the original menu is unchanged (the classic header shows there)', vance_hub_header_menu_community( $PLAIN_HTML, (object) array( 'theme_location' => 'primary-menu' ) ) === $PLAIN_HTML );

arrange( array( 'vance_hub_header' => false ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
$GLOBALS['PAGES']        = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON'] = true;
check( 'gate: an administrator preview turns the Community item on in the original menu', vance_hub_header_menu_community( $PLAIN_HTML, (object) array( 'theme_location' => 'primary-menu' ) ) === $PLAIN_HTML . $COMM_PLAIN );

// The template: the header class and the phone menu button.
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['PAGES']            = all_pages() + array( 'community' => 'https://example.test/community/' );
$GLOBALS['COMMUNITY_ON']     = true;
$GLOBALS['PRIMARY_ASSIGNED'] = true;
$GLOBALS['PRIMARY_HTML']     = $MEGA_HTML;
$HTML = render_part();
check( 'template: with Max Mega Menu the header carries vhh-header--mega', strpos( $HTML, '<header class="vhh-header vhh-header--mega" id="vhh-header" data-vhh-header>' ) !== false );
check( 'template: with Max Mega Menu no menu button is printed', strpos( $HTML, 'class="vhh-menu-btn"' ) === false );
check( 'template: with Max Mega Menu the menu button bars are not printed either', strpos( $HTML, 'vhh-menu-btn__bars' ) === false );
check( 'template: with Max Mega Menu nothing points at a menu button (no aria-controls)', strpos( $HTML, 'aria-controls="vhh-nav"' ) === false );
check( 'template: with Max Mega Menu the nav keeps its id', strpos( $HTML, '<nav class="vhh-nav" id="vhh-nav"' ) !== false );
check( 'template: with Max Mega Menu the Mega Menu items and Community sit inside the nav', ( $n = strpos( $HTML, '<nav class="vhh-nav" id="vhh-nav"' ) ) !== false && ( $m = strpos( $HTML, $MEGA_HTML . $COMM_MEGA ) ) !== false && $n < $m );
check( 'header.php: with Max Mega Menu the page header carries the mega class too', strpos( render_header_php(), 'class="vhh-header vhh-header--mega"' ) !== false );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['PAGES']         = all_pages();
$GLOBALS['MENU_ASSIGNED'] = true;
$GLOBALS['MENU_HTML']     = $CLUB_HTML;
$HTML = render_part();
check( 'template: a VanceHubClub menu leaves the header plain', strpos( $HTML, '<header class="vhh-header" id="vhh-header" data-vhh-header>' ) !== false );
check( 'template: a VanceHubClub menu keeps the phone menu button', strpos( $HTML, '<button type="button" class="vhh-menu-btn" aria-expanded="false" aria-controls="vhh-nav" aria-label="Menu">' ) !== false );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['PAGES']            = all_pages();
$GLOBALS['PRIMARY_ASSIGNED'] = true;
$GLOBALS['PRIMARY_HTML']     = $PLAIN_HTML;
$HTML = render_part();
check( 'template: an original menu without Max Mega Menu keeps the phone menu button', strpos( $HTML, '<header class="vhh-header" id="vhh-header" data-vhh-header>' ) !== false && strpos( $HTML, 'class="vhh-menu-btn"' ) !== false );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_header' => true ) );
$GLOBALS['PAGES'] = all_pages();
$HTML = render_part();
check( 'template: the fallback list keeps the phone menu button', strpos( $HTML, '<header class="vhh-header" id="vhh-header" data-vhh-header>' ) !== false && strpos( $HTML, 'class="vhh-menu-btn"' ) !== false );

/* ---- result ------------------------------------------------------------------------ */

echo "\n";
if ( empty( $FAIL ) ) {
	echo "OK — {$PASS} checks passed.\n";
	exit( 0 );
}
echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
foreach ( $FAIL as $label ) {
	echo "  x  {$label}\n";
}
exit( 1 );
