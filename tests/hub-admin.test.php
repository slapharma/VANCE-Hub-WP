<?php
/**
 * Tests for inc/hub-admin.php: the read-only VanceHubClub admin screen.
 *
 * Same shape as hub-layout.test.php: WordPress stubs, a bag of globals the stubs
 * read, check() lines, and exit 1 on any failure. Nothing here loads WordPress.
 *
 * The screen only reads. Its switch defaults must equal the Customizer
 * registrations, so this suite runs the three real register functions against a
 * stub customizer and compares. hub-layout.php, hub-article.php,
 * hub-dashboard-features.php and hub-admin.php are all loaded for real.
 *
 * WHAT IS COVERED
 *   1  defaults: each switch default equals the registered default; each switch
 *      mod is a setting in section vance_home_layout
 *   2  status: an unsaved mod gives the default; saved true/false/'1'/''/0 give
 *      on/off; a default-true switch saved false reads Off; no mutation of the mods
 *   3  the menu: parent, capability, slug, callback, hook and priority
 *   4  preview links: home always; article only when a published post exists;
 *      dashboard only when the page exists; vhh_layout=hub on each; a false
 *      permalink leaves its link out; the Customizer address
 *   5  render: no output without manage_options; escaping of labels and URLs;
 *      On/Off matches the status; the layout line matches vance_hub_saved_layout()
 *   6  the Customizer section vance_home_layout is registered with no panel key
 */

define( 'ABSPATH', true );
define( 'HOUR_IN_SECONDS', 3600 );
date_default_timezone_set( 'UTC' );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

/* ---- state the stubs read; every section resets it first ------------------- */

$GLOBALS['HOOKS'] = array();

function reset_admin_state() {
	$GLOBALS['MODS']              = array();
	$GLOBALS['CAPS']              = array();
	$GLOBALS['PUBLISHED']         = array();
	$GLOBALS['PERMALINKS']        = array();
	$GLOBALS['CURRENT_PERMALINK'] = 'https://example.test/current/';
	$GLOBALS['DASH_PAGE']         = null;
	$GLOBALS['GET_POSTS_ARGS']    = null;
	$GLOBALS['SUBMENU_CALLS']     = array();
	$GLOBALS['I18N']              = array();
}

function arrange_admin( array $mods, array $caps ) {
	reset_admin_state();
	$GLOBALS['MODS'] = $mods;
	$GLOBALS['CAPS'] = $caps;
}

reset_admin_state();

/* ---- error handling: a notice in the code under test is a failure, not a pass */

set_error_handler( function ( $no, $str, $file, $line ) {
	if ( ! ( error_reporting() & $no ) ) {
		return false;
	}
	throw new ErrorException( $str, 0, $no, $file, $line );
} );

/* ---- WordPress stubs ------------------------------------------------------ */

function __( $text, $domain = '' ) {
	// A test can swap a label for one with markup in it, to prove the screen escapes it.
	return array_key_exists( $text, $GLOBALS['I18N'] ) ? $GLOBALS['I18N'][ $text ] : $text;
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
function current_user_can( $cap ) { return ! empty( $GLOBALS['CAPS'][ $cap ] ); }
function vance_get_theme_mod( $key, $default = '' ) {
	return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
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
		'callback'   => $callback,
	);
	return 'toplevel_page_' . $menu_slug;
}
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function add_query_arg( $key, $value = null, $url = '' ) {
	// Both WordPress forms: (key, value, url) and (array of pairs, url).
	if ( is_array( $key ) ) {
		$pairs = $key;
		$url   = $value;
	} else {
		$pairs = array( $key => $value );
	}
	$parts = array();
	foreach ( $pairs as $k => $v ) {
		$parts[] = rawurlencode( (string) $k ) . '=' . rawurlencode( (string) $v );
	}
	return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . implode( '&', $parts );
}
function get_posts( $args ) {
	$GLOBALS['GET_POSTS_ARGS'] = $args;
	return array_slice( $GLOBALS['PUBLISHED'], 0, (int) $args['posts_per_page'] );
}
function get_page_by_path( $slug ) {
	return 'dashboard' === $slug ? $GLOBALS['DASH_PAGE'] : null;
}
function get_permalink( $post = 0 ) {
	// WordPress: an empty post argument means the current post, not false.
	if ( is_object( $post ) ) {
		$id = (int) $post->ID;
	} elseif ( is_int( $post ) && $post > 0 ) {
		$id = $post;
	} else {
		return $GLOBALS['CURRENT_PERMALINK'];
	}
	return array_key_exists( $id, $GLOBALS['PERMALINKS'] ) ? $GLOBALS['PERMALINKS'][ $id ] : false;
}

/** Stand-in for WP_Customize_Manager: records what each register function adds. */
class HubAdminCustomizeFake {
	public $sections = array();
	public $settings = array();
	public $controls = array();
	public function add_section( $id, $args = array() ) { $this->sections[ $id ] = $args; }
	public function add_setting( $id, $args = array() ) { $this->settings[ $id ] = $args; }
	public function add_control( $id, $args = array() ) { $this->controls[ $id ] = $args; }
}

/* ---- load the code under test and the register functions it mirrors ------- */

require_once $THEME . '/inc/hub-layout.php';
require_once $THEME . '/inc/hub-article.php';
require_once $THEME . '/inc/hub-dashboard-features.php';
require_once $THEME . '/inc/hub-tool-result.php';
require_once $THEME . '/inc/hub-admin.php';

/* Row order in vance_hub_admin_status(), as the tests index it. */
// 0 promo, 1 sections, 2 more, 3 articles, 4 dashboard, 5 member no, 6 chart, 7 check-in, 8 tab look.

/** The On/Off text the screen prints for one switch label, or 'missing'. */
function switch_cell( $html, $escaped_label ) {
	$pattern = '/<td>' . preg_quote( $escaped_label, '/' ) . '<\/td>\s*<td><strong>(On|Off)<\/strong><\/td>/';
	return preg_match( $pattern, $html, $m ) ? $m[1] : 'missing';
}

/** The output of vance_hub_admin_render(). */
function render_screen() {
	ob_start();
	vance_hub_admin_render();
	return ob_get_clean();
}

/* ===================================================================== */

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/* The registered defaults, from the real register functions. */
$WC = new HubAdminCustomizeFake();
vance_hub_customize_register( $WC );
vance_hub_article_customize_register( $WC );
vance_hub_dash_features_customize( $WC );
vance_hub_tool_result_customize_register( $WC );

/* ---- 1. defaults equal the Customizer registrations ----------------------- */
section( '1. Switch defaults against the Customizer registrations' );

$ROWS = array_column( vance_hub_admin_switches(), 'default', 'mod' );

check( 'switches: the screen lists exactly ten switches', count( vance_hub_admin_switches() ) === 10 );
check( 'defaults: vance_hub_dashboard_tabs equals its registered default', isset( $ROWS['vance_hub_dashboard_tabs'], $WC->settings['vance_hub_dashboard_tabs']['default'] ) && $ROWS['vance_hub_dashboard_tabs'] === $WC->settings['vance_hub_dashboard_tabs']['default'] );
check( 'defaults: vance_hub_dashboard_tabs is off by default', isset( $ROWS['vance_hub_dashboard_tabs'] ) && false === $ROWS['vance_hub_dashboard_tabs'] );
check( 'registered: vance_hub_dashboard_tabs is a checkbox in section vance_home_layout with the checkbox sanitiser', isset( $WC->settings['vance_hub_dashboard_tabs'], $WC->controls['vance_hub_dashboard_tabs'] ) && $WC->controls['vance_hub_dashboard_tabs']['section'] === 'vance_home_layout' && $WC->controls['vance_hub_dashboard_tabs']['type'] === 'checkbox' && $WC->settings['vance_hub_dashboard_tabs']['sanitize_callback'] === 'vance_hub_sanitize_checkbox' );
check( 'registered: vance_hub_dashboard_tabs has the agreed label', isset( $WC->controls['vance_hub_dashboard_tabs'] ) && $WC->controls['vance_hub_dashboard_tabs']['label'] === 'Member dashboard: VanceHubClub look on every tab' );
check( 'defaults: vance_hub_tool_result equals its registered default', isset( $ROWS['vance_hub_tool_result'], $WC->settings['vance_hub_tool_result']['default'] ) && $ROWS['vance_hub_tool_result'] === $WC->settings['vance_hub_tool_result']['default'] );
check( 'registered: vance_hub_tool_result is a setting in section vance_home_layout', isset( $WC->settings['vance_hub_tool_result'], $WC->controls['vance_hub_tool_result'] ) && $WC->controls['vance_hub_tool_result']['section'] === 'vance_home_layout' );
check( 'defaults: vance_hub_show_promo equals its registered default', isset( $ROWS['vance_hub_show_promo'], $WC->settings['vance_hub_show_promo']['default'] ) && $ROWS['vance_hub_show_promo'] === $WC->settings['vance_hub_show_promo']['default'] );
check( 'defaults: vance_hub_show_sections equals its registered default', isset( $ROWS['vance_hub_show_sections'], $WC->settings['vance_hub_show_sections']['default'] ) && $ROWS['vance_hub_show_sections'] === $WC->settings['vance_hub_show_sections']['default'] );
check( 'defaults: vance_hub_show_more equals its registered default', isset( $ROWS['vance_hub_show_more'], $WC->settings['vance_hub_show_more']['default'] ) && $ROWS['vance_hub_show_more'] === $WC->settings['vance_hub_show_more']['default'] );
check( 'defaults: vance_hub_articles equals its registered default', isset( $ROWS['vance_hub_articles'], $WC->settings['vance_hub_articles']['default'] ) && $ROWS['vance_hub_articles'] === $WC->settings['vance_hub_articles']['default'] );
check( 'defaults: vance_hub_show_dashboard equals its registered default', isset( $ROWS['vance_hub_show_dashboard'], $WC->settings['vance_hub_show_dashboard']['default'] ) && $ROWS['vance_hub_show_dashboard'] === $WC->settings['vance_hub_show_dashboard']['default'] );
check( 'defaults: vance_hub_dash_member_no equals its registered default', isset( $ROWS['vance_hub_dash_member_no'], $WC->settings['vance_hub_dash_member_no']['default'] ) && $ROWS['vance_hub_dash_member_no'] === $WC->settings['vance_hub_dash_member_no']['default'] );
check( 'defaults: vance_hub_dash_chart equals its registered default', isset( $ROWS['vance_hub_dash_chart'], $WC->settings['vance_hub_dash_chart']['default'] ) && $ROWS['vance_hub_dash_chart'] === $WC->settings['vance_hub_dash_chart']['default'] );
check( 'defaults: vance_hub_dash_checkin equals its registered default', isset( $ROWS['vance_hub_dash_checkin'], $WC->settings['vance_hub_dash_checkin']['default'] ) && $ROWS['vance_hub_dash_checkin'] === $WC->settings['vance_hub_dash_checkin']['default'] );

check( 'registered: vance_hub_show_promo is a setting in section vance_home_layout', isset( $WC->settings['vance_hub_show_promo'], $WC->controls['vance_hub_show_promo'] ) && $WC->controls['vance_hub_show_promo']['section'] === 'vance_home_layout' );
check( 'registered: vance_hub_show_sections is a setting in section vance_home_layout', isset( $WC->settings['vance_hub_show_sections'], $WC->controls['vance_hub_show_sections'] ) && $WC->controls['vance_hub_show_sections']['section'] === 'vance_home_layout' );
check( 'registered: vance_hub_show_more is a setting in section vance_home_layout', isset( $WC->settings['vance_hub_show_more'], $WC->controls['vance_hub_show_more'] ) && $WC->controls['vance_hub_show_more']['section'] === 'vance_home_layout' );
check( 'registered: vance_hub_articles is a setting in section vance_home_layout', isset( $WC->settings['vance_hub_articles'], $WC->controls['vance_hub_articles'] ) && $WC->controls['vance_hub_articles']['section'] === 'vance_home_layout' );
check( 'registered: vance_hub_show_dashboard is a setting in section vance_home_layout', isset( $WC->settings['vance_hub_show_dashboard'], $WC->controls['vance_hub_show_dashboard'] ) && $WC->controls['vance_hub_show_dashboard']['section'] === 'vance_home_layout' );
check( 'registered: vance_hub_dash_member_no is a setting in section vance_home_layout', isset( $WC->settings['vance_hub_dash_member_no'], $WC->controls['vance_hub_dash_member_no'] ) && $WC->controls['vance_hub_dash_member_no']['section'] === 'vance_home_layout' );
check( 'registered: vance_hub_dash_chart is a setting in section vance_home_layout', isset( $WC->settings['vance_hub_dash_chart'], $WC->controls['vance_hub_dash_chart'] ) && $WC->controls['vance_hub_dash_chart']['section'] === 'vance_home_layout' );
check( 'registered: vance_hub_dash_checkin is a setting in section vance_home_layout', isset( $WC->settings['vance_hub_dash_checkin'], $WC->controls['vance_hub_dash_checkin'] ) && $WC->controls['vance_hub_dash_checkin']['section'] === 'vance_home_layout' );

/* ---- 2. status: saved values and defaults --------------------------------- */
section( '2. Status: saved values and defaults' );

arrange_admin( array(), array() );
$ST = vance_hub_admin_status();
check( 'status: one row per switch', count( $ST ) === 10 );
check( 'status: the tab-look switch is row 8 and reads Off by default', isset( $ST[8] ) && $ST[8]['label'] === 'Member dashboard: VanceHubClub look on every tab' && $ST[8]['on'] === false );
check( 'status: the survey result switch is the last row and reads Off by default', isset( $ST[9] ) && $ST[9]['label'] === 'Health Survey: VanceHubClub result page' && $ST[9]['on'] === false );
check( 'status: row 0 is the promo switch, in display order', isset( $ST[0] ) && $ST[0]['label'] === 'Home page: promo block' );
check( 'status: an unsaved default-true switch reads On', isset( $ST[0] ) && $ST[0]['on'] === true );
check( 'status: an unsaved default-true section switch reads On', isset( $ST[1] ) && $ST[1]['on'] === true );
check( 'status: an unsaved default-false article switch reads Off', isset( $ST[3] ) && $ST[3]['on'] === false );
check( 'status: an unsaved default-false dashboard switch reads Off', isset( $ST[4] ) && $ST[4]['on'] === false );

arrange_admin( array( 'vance_hub_show_promo' => true ), array() );
$ST = vance_hub_admin_status();
check( 'status: promo saved true reads On', isset( $ST[0] ) && $ST[0]['on'] === true );

arrange_admin( array( 'vance_hub_show_promo' => false ), array() );
$ST = vance_hub_admin_status();
check( 'status: promo saved false reads Off (a default-true switch turned off)', isset( $ST[0] ) && $ST[0]['on'] === false );

arrange_admin( array( 'vance_hub_show_sections' => false ), array() );
$ST = vance_hub_admin_status();
check( 'status: sections saved false reads Off (a default-true switch turned off)', isset( $ST[1] ) && $ST[1]['on'] === false );

arrange_admin( array( 'vance_hub_show_more' => false ), array() );
$ST = vance_hub_admin_status();
check( 'status: more saved false reads Off (a default-true switch turned off)', isset( $ST[2] ) && $ST[2]['on'] === false );

arrange_admin( array( 'vance_hub_show_promo' => '1' ), array() );
$ST = vance_hub_admin_status();
check( 'status: promo saved the string "1" reads On', isset( $ST[0] ) && $ST[0]['on'] === true );

arrange_admin( array( 'vance_hub_show_promo' => '' ), array() );
$ST = vance_hub_admin_status();
check( 'status: promo saved the empty string (an unticked box) reads Off', isset( $ST[0] ) && $ST[0]['on'] === false );

arrange_admin( array( 'vance_hub_show_promo' => '0' ), array() );
$ST = vance_hub_admin_status();
check( 'status: promo saved the string "0" reads Off', isset( $ST[0] ) && $ST[0]['on'] === false );

arrange_admin( array( 'vance_hub_show_promo' => 0 ), array() );
$ST = vance_hub_admin_status();
check( 'status: promo saved the integer 0 reads Off', isset( $ST[0] ) && $ST[0]['on'] === false );

arrange_admin( array( 'vance_hub_articles' => '1' ), array() );
$ST = vance_hub_admin_status();
check( 'status: articles saved "1" reads On (a default-false switch turned on)', isset( $ST[3] ) && $ST[3]['on'] === true );

arrange_admin( array( 'vance_hub_show_dashboard' => true ), array() );
$ST = vance_hub_admin_status();
check( 'status: dashboard saved true reads On', isset( $ST[4] ) && $ST[4]['on'] === true );

arrange_admin( array( 'vance_hub_show_promo' => array( '1' ) ), array() );
$ST = vance_hub_admin_status();
check( 'status: an array saved in a switch does not throw and reads Off', isset( $ST[0] ) && $ST[0]['on'] === false );

arrange_admin( array( 'vance_hub_show_promo' => '0' ), array() );
vance_hub_admin_status();
check( 'status: reading the switches does not change the saved mods', $GLOBALS['MODS'] === array( 'vance_hub_show_promo' => '0' ) );

/* ---- 3. the menu item ----------------------------------------------------- */
section( '3. The admin menu item' );

arrange_admin( array(), array() );
vance_hub_admin_menu();
check( 'menu: exactly one submenu page is added', count( $GLOBALS['SUBMENU_CALLS'] ) === 1 );
$MENU = isset( $GLOBALS['SUBMENU_CALLS'][0] ) ? $GLOBALS['SUBMENU_CALLS'][0] : array();
check( 'menu: the parent is vance-content-hub', isset( $MENU['parent'] ) && $MENU['parent'] === 'vance-content-hub' );
check( 'menu: the capability is manage_options', isset( $MENU['capability'] ) && $MENU['capability'] === 'manage_options' );
check( 'menu: the slug is vance-hub-club', isset( $MENU['menu_slug'] ) && $MENU['menu_slug'] === 'vance-hub-club' );
check( 'menu: the callback is vance_hub_admin_render', isset( $MENU['callback'] ) && $MENU['callback'] === 'vance_hub_admin_render' );
check( 'menu: the callback is a function that exists', isset( $MENU['callback'] ) && function_exists( $MENU['callback'] ) );
check( 'menu: the menu title is VanceHubClub', isset( $MENU['menu_title'] ) && $MENU['menu_title'] === 'VanceHubClub' );
check( 'menu: the page title is VanceHubClub', isset( $MENU['page_title'] ) && $MENU['page_title'] === 'VanceHubClub' );
check( 'menu: vance_hub_admin_menu is hooked to admin_menu at priority 1000', isset( $GLOBALS['HOOKS']['admin_menu'][1000] ) && in_array( 'vance_hub_admin_menu', $GLOBALS['HOOKS']['admin_menu'][1000], true ) );
check( 'menu: the hook is not at priority 999, where the parent menu is added', ! isset( $GLOBALS['HOOKS']['admin_menu'][999] ) );

/* ---- 4. preview links ----------------------------------------------------- */
section( '4. Preview links' );

arrange_admin( array(), array() );
$LINKS = vance_hub_admin_preview_links();
check( 'preview: with no published post and no dashboard, only the home link is listed', count( $LINKS ) === 1 );
check( 'preview: the home link label is Home page', isset( $LINKS[0] ) && $LINKS[0]['label'] === 'Home page' );
check( 'preview: the home link is the home address with vhh_layout=hub', isset( $LINKS[0] ) && $LINKS[0]['url'] === 'https://example.test/?vhh_layout=hub' );
check( 'preview: the article query asks for published posts only', isset( $GLOBALS['GET_POSTS_ARGS']['post_status'] ) && $GLOBALS['GET_POSTS_ARGS']['post_status'] === 'publish' );
check( 'preview: the article query asks for posts, not pages', isset( $GLOBALS['GET_POSTS_ARGS']['post_type'] ) && $GLOBALS['GET_POSTS_ARGS']['post_type'] === 'post' );

arrange_admin( array(), array() );
$GLOBALS['PUBLISHED']  = array( 42 );
$GLOBALS['PERMALINKS'] = array( 42 => 'https://example.test/p/42/' );
$LINKS = vance_hub_admin_preview_links();
check( 'preview: a published post adds the article link', count( $LINKS ) === 2 );
check( 'preview: the article link label is Newest article', isset( $LINKS[1] ) && $LINKS[1]['label'] === 'Newest article' );
check( 'preview: the article link carries vhh_layout=hub', isset( $LINKS[1] ) && $LINKS[1]['url'] === 'https://example.test/p/42/?vhh_layout=hub' );

arrange_admin( array(), array() );
$GLOBALS['DASH_PAGE']  = (object) array( 'ID' => 77 );
$GLOBALS['PERMALINKS'] = array( 77 => 'https://example.test/dashboard/' );
$LINKS = vance_hub_admin_preview_links();
check( 'preview: an existing dashboard page adds the dashboard link', count( $LINKS ) === 2 );
check( 'preview: the dashboard link label is Member dashboard', isset( $LINKS[1] ) && $LINKS[1]['label'] === 'Member dashboard' );
check( 'preview: the dashboard link carries vhh_layout=hub', isset( $LINKS[1] ) && $LINKS[1]['url'] === 'https://example.test/dashboard/?vhh_layout=hub' );

arrange_admin( array(), array() );
$GLOBALS['PUBLISHED']  = array( 42 );
$GLOBALS['DASH_PAGE']  = (object) array( 'ID' => 77 );
$GLOBALS['PERMALINKS'] = array( 42 => 'https://example.test/p/42/', 77 => 'https://example.test/dashboard/' );
$LINKS = vance_hub_admin_preview_links();
check( 'preview: with a post and a dashboard, all three links are listed', count( $LINKS ) === 3 );
check( 'preview: the home link is first', isset( $LINKS[0] ) && $LINKS[0]['label'] === 'Home page' );
check( 'preview: the article link is second', isset( $LINKS[1] ) && $LINKS[1]['label'] === 'Newest article' );
check( 'preview: the dashboard link is third', isset( $LINKS[2] ) && $LINKS[2]['label'] === 'Member dashboard' );
check( 'preview: the article link holds vhh_layout=hub', isset( $LINKS[1] ) && strpos( $LINKS[1]['url'], 'vhh_layout=hub' ) !== false );
check( 'preview: the dashboard link holds vhh_layout=hub', isset( $LINKS[2] ) && strpos( $LINKS[2]['url'], 'vhh_layout=hub' ) !== false );

arrange_admin( array(), array() );
$GLOBALS['PUBLISHED']  = array( 42 );
$GLOBALS['DASH_PAGE']  = null;
$GLOBALS['PERMALINKS'] = array( 42 => 'https://example.test/p/42/' );
$LINKS = vance_hub_admin_preview_links();
check( 'preview: a missing dashboard page adds no dashboard link (two links, not three)', count( $LINKS ) === 2 );
check( 'preview: a missing dashboard page leaves no Member dashboard label', isset( $LINKS[1] ) && $LINKS[1]['label'] === 'Newest article' );

arrange_admin( array(), array() );
$GLOBALS['PUBLISHED']  = array( 42 );
$GLOBALS['PERMALINKS'] = array( 42 => false );
$LINKS = vance_hub_admin_preview_links();
check( 'preview: a false article permalink leaves the article link out', count( $LINKS ) === 1 );

arrange_admin( array(), array() );
$GLOBALS['DASH_PAGE']  = (object) array( 'ID' => 77 );
$GLOBALS['PERMALINKS'] = array( 77 => false );
$LINKS = vance_hub_admin_preview_links();
check( 'preview: a false dashboard permalink leaves the dashboard link out', count( $LINKS ) === 1 );

arrange_admin( array(), array() );
check( 'preview: the Customizer address opens the vance_home_layout section', vance_hub_admin_customizer_url() === 'https://example.test/wp-admin/customize.php?autofocus%5Bsection%5D=vance_home_layout' );

/* ---- 5. render ------------------------------------------------------------ */
section( '5. Render: capability, escaping, On/Off, layout line' );

arrange_admin( array(), array() );
check( 'render: a user with no capability gets no output', render_screen() === '' );

arrange_admin( array(), array( 'edit_theme_options' => true ) );
check( 'render: a user who can edit the theme but not manage_options gets no output', render_screen() === '' );

arrange_admin( array(), array( 'manage_options' => true ) );
$OUT = render_screen();
check( 'render: a user with manage_options gets the screen heading', strpos( $OUT, '<h1>VanceHubClub</h1>' ) !== false );

arrange_admin( array( 'vance_hub_show_promo' => false, 'vance_hub_articles' => '1' ), array( 'manage_options' => true ) );
$OUT = render_screen();
$ST  = vance_hub_admin_status();
check( 'render: the promo row reads Off, as the status does', switch_cell( $OUT, 'Home page: promo block' ) === 'Off' && $ST[0]['on'] === false );
check( 'render: the article row reads On, as the status does', switch_cell( $OUT, 'Article pages: VanceHubClub layout' ) === 'On' && $ST[3]['on'] === true );
check( 'render: the sections row reads On by default, as the status does', switch_cell( $OUT, 'Home page: &quot;Latest by section&quot;' ) === 'On' && $ST[1]['on'] === true );
check( 'render: the dashboard row reads Off by default, as the status does', switch_cell( $OUT, 'Member dashboard: VanceHubClub home' ) === 'Off' && $ST[4]['on'] === false );
check( 'render: the member number row reads Off by default, as the status does', switch_cell( $OUT, 'Member dashboard: member number' ) === 'Off' && $ST[5]['on'] === false );

arrange_admin( array( 'vance_home_layout' => 'hub' ), array( 'manage_options' => true ) );
$OUT = render_screen();
check( 'render: the layout line says VanceHubClub when the saved layout is hub', strpos( $OUT, '<strong>VanceHubClub</strong>' ) !== false && vance_hub_saved_layout() === 'hub' );

arrange_admin( array(), array( 'manage_options' => true ) );
$OUT = render_screen();
check( 'render: the layout line says classic when nothing is saved', strpos( $OUT, '<strong>VanceHealthHub (classic)</strong>' ) !== false && vance_hub_saved_layout() === 'classic' );

arrange_admin( array( 'vance_home_layout' => 'junk' ), array( 'manage_options' => true ) );
$OUT = render_screen();
check( 'render: a garbage saved layout shows classic, matching vance_hub_saved_layout()', strpos( $OUT, '<strong>VanceHealthHub (classic)</strong>' ) !== false && vance_hub_saved_layout() === 'classic' );

arrange_admin( array( 'vance_home_layout' => 'HUB' ), array( 'manage_options' => true ) );
$OUT = render_screen();
check( 'render: a saved layout in upper case shows VanceHubClub, matching vance_hub_saved_layout()', strpos( $OUT, '<strong>VanceHubClub</strong>' ) !== false && vance_hub_saved_layout() === 'hub' );

arrange_admin( array(), array( 'manage_options' => true ) );
$GLOBALS['I18N']       = array( 'Newest article' => '<b>bold</b>' );
$GLOBALS['PUBLISHED']  = array( 42 );
$GLOBALS['PERMALINKS'] = array( 42 => 'https://example.test/p/42/' );
$OUT = render_screen();
check( 'escape: a preview label with markup is escaped, not raw', strpos( $OUT, '&lt;b&gt;bold&lt;/b&gt;' ) !== false );
check( 'escape: a preview label with markup does not reach the HTML raw', strpos( $OUT, '<b>bold</b>' ) === false );

arrange_admin( array(), array( 'manage_options' => true ) );
$GLOBALS['PUBLISHED']  = array( 42 );
$GLOBALS['PERMALINKS'] = array( 42 => 'https://example.test/p/"><img src=x onerror=alert(1)>/' );
$OUT = render_screen();
check( 'escape: an article URL with markup does not break out of its href', strpos( $OUT, '<img src=x' ) === false );
check( 'escape: an article URL with markup keeps its quote encoded', strpos( $OUT, '&quot;&gt;&lt;img' ) !== false );

arrange_admin( array(), array( 'manage_options' => true ) );
$GLOBALS['DASH_PAGE']  = (object) array( 'ID' => 77 );
$GLOBALS['PERMALINKS'] = array( 77 => 'javascript:alert(1)' );
$OUT = render_screen();
check( 'escape: a javascript: dashboard URL does not reach the HTML', strpos( $OUT, 'javascript:' ) === false );

arrange_admin( array(), array( 'manage_options' => true ) );
$GLOBALS['I18N'] = array( 'Member dashboard: weekly check-in' => '<script>x</script>' );
$OUT = render_screen();
check( 'escape: a switch label with markup is escaped, not raw', strpos( $OUT, '<script>' ) === false );
check( 'escape: a switch label with markup is shown as text', strpos( $OUT, '&lt;script&gt;x&lt;/script&gt;' ) !== false );

/* ---- 6. the Customizer section has no panel ------------------------------- */
section( '6. The Customizer section vance_home_layout' );

check( 'section: vance_home_layout is registered', isset( $WC->sections['vance_home_layout'] ) );
check( 'section: vance_home_layout has no panel key', isset( $WC->sections['vance_home_layout'] ) && ! array_key_exists( 'panel', $WC->sections['vance_home_layout'] ) );
check( 'section: vance_home_layout is titled VanceHubClub', isset( $WC->sections['vance_home_layout']['title'] ) && $WC->sections['vance_home_layout']['title'] === 'VanceHubClub' );
check( 'section: the home layout radio is still in vance_home_layout', isset( $WC->controls['vance_home_layout']['section'] ) && $WC->controls['vance_home_layout']['section'] === 'vance_home_layout' );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
