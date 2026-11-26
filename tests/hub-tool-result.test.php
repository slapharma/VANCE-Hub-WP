<?php
/**
 * Tests for the VanceHubClub survey result page: inc/hub-tool-result.php, plus
 * the wiring in page-healthcare-quiz.php, functions.php and the stylesheet.
 *
 * Same shape as hub-layout.test.php: WordPress stubs, a bag of globals the
 * stubs read, and assertions on return values and on the rendered HTML. Nothing
 * here loads WordPress. The survey questions are the REAL vance_quiz_steps(),
 * cut out of functions.php, so a question the dashboard cannot name is tested
 * against real data.
 *
 * The JavaScript that builds the tiles is tested in hub-tool-result.test.js.
 *
 * WHAT IS COVERED
 *   1  switch and preview: saved mod, ?vhh_layout=, who may preview, bad input
 *   2  the survey-page check, the hooks, the assets, the Customizer control
 *   3  the field spec built from the real survey questions
 *   4  the sign-up address and the next-step cards (missing pages are left out)
 *   5  the markup, guest and member (the data rule: nothing invented)
 *   6  escaping
 *   7  the data gatherer: real pages only, Google only when set up
 *   8  the wiring: template, functions.php order, IDs kept, stylesheet tokens
 */

define( 'ABSPATH', true );
date_default_timezone_set( 'UTC' );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';
$GLOBALS['HOOKS'] = array();

function reset_state() {
	$GLOBALS['MODS']        = array();
	$GLOBALS['CAPS']        = array();
	$GLOBALS['LOGGED_IN']   = false;
	$GLOBALS['IS_SURVEY']   = false;
	$GLOBALS['PAGES']       = array();
	$GLOBALS['ENQUEUED']    = array();
	$GLOBALS['SHORTCODES']  = array();
	$GLOBALS['SHORTCODE_SAW_REDIRECT'] = null;
	$_GET                   = array();
}

function arrange( array $mods = array(), array $caps = array(), array $get = array() ) {
	reset_state();
	$GLOBALS['MODS'] = $mods;
	$GLOBALS['CAPS'] = $caps;
	$_GET            = $get;
}

reset_state();

set_error_handler( function ( $no, $str, $file, $line ) {
	if ( ! ( error_reporting() & $no ) ) {
		return false;
	}
	throw new ErrorException( $str, 0, $no, $file, $line );
} );

/* ---- WordPress stubs ------------------------------------------------------ */

function __( $text, $domain = '' ) { return $text; }
function _n( $single, $plural, $n, $domain = '' ) { return 1 === (int) $n ? $single : $plural; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
function esc_url( $url ) {
	// A scheme outside the allowlist is dropped and the attribute is left empty.
	$url = (string) $url;
	if ( preg_match( '#^([a-z][a-z0-9+.\-]*):#i', $url, $m ) && ! in_array( strtolower( $m[1] ), array( 'http', 'https', 'mailto', 'tel' ), true ) ) {
		return '';
	}
	return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8', false );
}
function wp_json_encode( $v ) { return json_encode( $v ); }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ); }
function wp_unslash( $v ) { return $v; }
function current_user_can( $cap ) { return ! empty( $GLOBALS['CAPS'][ $cap ] ); }
function vance_get_theme_mod( $key, $default = '' ) {
	return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
}
function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['HOOKS'][ $tag ][ $priority ][] = $callback;
	return true;
}
function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) { return add_action( $tag, $callback, $priority, $accepted_args ); }
function is_user_logged_in() { return $GLOBALS['LOGGED_IN']; }
function is_page_template( $t = '' ) { return $GLOBALS['IS_SURVEY'] && 'page-healthcare-quiz.php' === $t; }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }
function get_template_directory() { return $GLOBALS['THEME_DIR']; }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/vance-health-hub'; }
function add_query_arg( $key, $value = null, $url = '' ) {
	return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value );
}
function get_page_by_path( $slug ) { return isset( $GLOBALS['PAGES'][ $slug ] ) ? (object) array( 'slug' => $slug ) : null; }
function get_permalink( $post = 0 ) { return is_object( $post ) && isset( $GLOBALS['PAGES'][ $post->slug ] ) ? $GLOBALS['PAGES'][ $post->slug ] : false; }
function wp_create_nonce( $action ) { return 'nonce-' . $action; }
function shortcode_exists( $tag ) { return isset( $GLOBALS['SHORTCODES'][ $tag ] ); }
function do_shortcode( $content ) {
	$GLOBALS['SHORTCODE_SAW_REDIRECT'] = isset( $_GET['redirect_to'] ) ? $_GET['redirect_to'] : null;
	return $GLOBALS['SHORTCODES']['google_login'];
}
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false ) { $GLOBALS['ENQUEUED'][] = array( 'style', $handle, $src, $deps ); }
function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) { $GLOBALS['ENQUEUED'][] = array( 'script', $handle, $src, $deps ); }

$GLOBALS['THEME_DIR'] = $THEME;

/* The REAL survey questions, cut out of functions.php. */
$fn_src = file_get_contents( $THEME . '/functions.php' );
$start  = strpos( $fn_src, 'function vance_quiz_steps()' );
$end    = strpos( $fn_src, '/**', $start );
if ( false === $start || false === $end ) {
	fwrite( STDERR, "Cannot find vance_quiz_steps() in functions.php\n" );
	exit( 2 );
}
eval( substr( $fn_src, $start, $end - $start ) );

require_once $THEME . '/inc/hub-layout.php';
require_once $THEME . '/inc/hub-tool-result.php';

class ToolResultCustomizeFake {
	public $panels   = array();
	public $settings = array();
	public $controls = array();
	public function add_panel( $id, $args = array() ) { $this->panels[ $id ] = $args; }
	public function add_setting( $id, $args = array() ) { $this->settings[ $id ] = $args; }
	public function add_control( $id, $args = array() ) { $this->controls[ $id ] = $args; }
}

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }
function has( $html, $needle ) { return false !== strpos( $html, $needle ); }

/** Full data for the markup tests; each test overrides what it needs. */
function sample( array $over = array() ) {
	return $over + array(
		'member'        => false,
		'fields'        => vance_hub_tool_result_fields( vance_quiz_steps() ),
		'signup_url'    => 'https://example.test/login/?tab=signup&redirect_to=https%3A%2F%2Fexample.test%2Fgastro-health-survey%2F',
		'google_html'   => '',
		'dashboard_url' => 'https://example.test/dashboard/',
		'next'          => vance_hub_tool_result_next_cards( array(
			'planner' => 'https://example.test/gastro-meal-planner/',
			'guides'  => 'https://example.test/gastro-health-explained/',
			'ask'     => 'https://example.test/ask-ai/',
		) ),
		'home_url'      => 'https://example.test/',
		'image'         => '',
		'image_alt'     => '',
		'ajax_url'      => 'https://example.test/wp-admin/admin-ajax.php',
		'nonce'         => 'nonce-vance_quiz_nonce',
	);
}

/* ---- 1. switch and preview ------------------------------------------------- */
section( '1. Switch and preview' );

check( 'resolve: unsaved (false), no query: off', false === vance_hub_tool_result_resolve( false, '', false ) );
check( 'resolve: saved "1", no query: on', true === vance_hub_tool_result_resolve( '1', '', false ) );
check( 'resolve: saved true, no query: on', true === vance_hub_tool_result_resolve( true, '', false ) );
check( 'resolve: saved "" : off', false === vance_hub_tool_result_resolve( '', '', false ) );
check( 'resolve: preview hub by a user who may preview turns it on', true === vance_hub_tool_result_resolve( false, 'hub', true ) );
check( 'resolve: preview hub by a user who may not preview is ignored', false === vance_hub_tool_result_resolve( false, 'hub', false ) );
check( 'resolve: preview classic by a user who may preview turns a saved "on" off', false === vance_hub_tool_result_resolve( true, 'classic', true ) );
check( 'resolve: an unknown query value leaves the saved value in charge (on)', true === vance_hub_tool_result_resolve( true, 'evil', true ) );
check( 'resolve: an unknown query value leaves the saved value in charge (off)', false === vance_hub_tool_result_resolve( false, 'evil', true ) );
check( 'resolve: the query is case-insensitive for a previewer', true === vance_hub_tool_result_resolve( false, 'HUB', true ) );

arrange();
check( 'active: nothing saved, no query: off', false === vance_hub_tool_result_active() );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_tool_result' => true ) );
check( 'active: saved on: on for a visitor', true === vance_hub_tool_result_active() );
arrange( array(), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'active: ?vhh_layout=hub by an administrator: on', true === vance_hub_tool_result_active() );
arrange( array(), array(), array( 'vhh_layout' => 'hub' ) );
check( 'active: ?vhh_layout=hub by a visitor is not read: off', false === vance_hub_tool_result_active() );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_tool_result' => true ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'classic' ) );
check( 'active: ?vhh_layout=classic by an administrator forces it off', false === vance_hub_tool_result_active() );
arrange( array(), array( 'edit_theme_options' => true ), array( 'vhh_layout' => array( 'hub' ) ) );
check( 'active: ?vhh_layout[]=hub (an array) does not throw and is ignored', false === vance_hub_tool_result_active() );
arrange( array(), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub"><script>' ) );
check( 'active: a hostile query value is ignored', false === vance_hub_tool_result_active() );

// The master switch (Home layout) over the survey result page.
arrange( array( 'vance_hub_tool_result' => true ) );
check( 'master: classic with the result box ticked is off for a visitor', false === vance_hub_tool_result_active() );
arrange( array( 'vance_hub_tool_result' => true ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'master: classic with an admin ?vhh_layout=hub preview is on', true === vance_hub_tool_result_active() );
arrange( array( 'vance_home_layout' => 'hub' ) );
check( 'master: hub with the result box never saved is on (default on)', true === vance_hub_tool_result_active() );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_tool_result' => false ) );
check( 'master: hub with the result box saved off is off', false === vance_hub_tool_result_active() );

/* ---- 2. survey page, hooks, assets, Customizer ----------------------------- */
section( '2. Survey page, hooks, assets, Customizer' );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_tool_result' => true ) );
$GLOBALS['IS_SURVEY'] = false;
vance_hub_tool_result_enqueue();
check( 'enqueue: on but not the survey page: nothing enqueued', array() === $GLOBALS['ENQUEUED'] );

arrange();
$GLOBALS['IS_SURVEY'] = true;
vance_hub_tool_result_enqueue();
check( 'enqueue: the survey page with the switch off: nothing enqueued', array() === $GLOBALS['ENQUEUED'] );

arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_tool_result' => true ) );
$GLOBALS['IS_SURVEY'] = true;
vance_hub_tool_result_enqueue();
$handles = array_column( $GLOBALS['ENQUEUED'], 1 );
check( 'enqueue: the survey page with the switch on: one style and one script', 2 === count( $GLOBALS['ENQUEUED'] ) && 'style' === $GLOBALS['ENQUEUED'][0][0] && 'script' === $GLOBALS['ENQUEUED'][1][0] );
check( 'enqueue: the style is hub-tool-result.css, after the main style', has( $GLOBALS['ENQUEUED'][0][2], '/assets/css/hub-tool-result.css' ) && array( 'vance-main-style' ) === $GLOBALS['ENQUEUED'][0][3] );
check( 'enqueue: the script is hub-tool-result.js', has( $GLOBALS['ENQUEUED'][1][2], '/assets/js/hub-tool-result.js' ) );

check( 'hooks: wp_enqueue_scripts is hooked to the enqueue function', isset( $GLOBALS['HOOKS']['wp_enqueue_scripts'][20] ) && in_array( 'vance_hub_tool_result_enqueue', $GLOBALS['HOOKS']['wp_enqueue_scripts'][20], true ) );
check( 'hooks: customize_register is hooked after the section is made (priority 11)', isset( $GLOBALS['HOOKS']['customize_register'][11] ) && in_array( 'vance_hub_tool_result_customize_register', $GLOBALS['HOOKS']['customize_register'][11], true ) );

$WC = new ToolResultCustomizeFake();
vance_hub_tool_result_customize_register( $WC );
check( 'customizer: the setting vance_hub_tool_result exists', isset( $WC->settings['vance_hub_tool_result'] ) );
check( 'customizer: default is true (the master switch decides whether it shows)', isset( $WC->settings['vance_hub_tool_result'] ) && true === $WC->settings['vance_hub_tool_result']['default'] );
check( 'customizer: sanitized by vance_hub_sanitize_checkbox', isset( $WC->settings['vance_hub_tool_result'] ) && 'vance_hub_sanitize_checkbox' === $WC->settings['vance_hub_tool_result']['sanitize_callback'] );
check( 'customizer: a checkbox in section vance_hub_sec_pages', isset( $WC->controls['vance_hub_tool_result'] ) && 'checkbox' === $WC->controls['vance_hub_tool_result']['type'] && 'vance_hub_sec_pages' === $WC->controls['vance_hub_tool_result']['section'] );
check( 'customizer: the label is "Show the Self-Assessment result page"', isset( $WC->controls['vance_hub_tool_result'] ) && 'Show the Self-Assessment result page' === $WC->controls['vance_hub_tool_result']['label'] );

/* ---- 3. field spec from the real questions ----------------------------------- */
section( '3. Field spec from the real survey questions' );

$steps  = vance_quiz_steps();
$fields = vance_hub_tool_result_fields( $steps );
$by     = array_column( $fields, null, 'field' );
check( 'fields: one row per real survey question (9)', 9 === count( $steps ) && 9 === count( $fields ) );
check( 'fields: in survey order, first is age and last is use_supplements', 'age' === $fields[0]['field'] && 'use_supplements' === $fields[8]['field'] );
check( 'fields: labels come from the survey (Age range, Primary concern)', 'Age range' === $by['age']['label'] && 'Primary concern' === $by['condition_type']['label'] );
check( 'fields: duration maps "Recently" to the longer display text', isset( $by['duration']['map']['Recently'] ) && 'Recently (less than 6 months)' === $by['duration']['map']['Recently'] );
check( 'fields: an option with the same display text as its value adds no map entry', ! isset( $by['duration']['map']['1-3 Years'] ) );
check( 'fields: "Yes" on the specialist question reveals specialist_type', isset( $by['seeing_specialist']['text']['Yes'] ) && 'specialist_type' === $by['seeing_specialist']['text']['Yes'] );
check( 'fields: "Other" on condition_type reveals condition_type_other', isset( $by['condition_type']['text']['Other'] ) && 'condition_type_other' === $by['condition_type']['text']['Other'] );
check( 'fields: "Yes" on supplements reveals supplement_types, with Other text supplement_other', isset( $by['use_supplements']['dep']['Yes'] ) && 'supplement_types' === $by['use_supplements']['dep']['Yes']['field'] && 'supplement_other' === $by['use_supplements']['dep']['Yes']['text']['Other'] );
check( 'fields: a plain question (gender) has no map, text or dep keys', ! isset( $by['gender']['map'] ) && ! isset( $by['gender']['text'] ) && ! isset( $by['gender']['dep'] ) );

$odd = vance_hub_tool_result_fields( array(
	array( 'field' => 'a', 'label' => 'A', 'opts' => array( 'x' ) ),
	array( 'field' => 'b' ),
	array( 'label' => 'C' ),
	array( 'field' => 'd', 'label' => '' ),
	'junk',
	array( 'field' => array( 'e' ), 'label' => 'E' ),
) );
check( 'fields: a step with no label, no field, a bad type or junk is dropped (only "a" stays)', 1 === count( $odd ) && 'a' === $odd[0]['field'] );
check( 'fields: no steps, no fields', array() === vance_hub_tool_result_fields( array() ) && array() === vance_hub_tool_result_fields( null ) );

/* ---- 4. sign-up address and next-step cards ----------------------------------- */
section( '4. Sign-up address and next-step cards' );

$su = vance_hub_tool_result_signup_url( 'https://example.test/login/', 'https://example.test/gastro-health-survey/' );
check( 'signup: points at /login/ with tab=signup', 0 === strpos( $su, 'https://example.test/login/?tab=signup' ) );
check( 'signup: carries redirect_to back to the survey, encoded', has( $su, '&redirect_to=' . rawurlencode( 'https://example.test/gastro-health-survey/' ) ) );
check( 'signup: with no survey address there is no redirect_to', ! has( vance_hub_tool_result_signup_url( 'https://example.test/login/', '' ), 'redirect_to' ) );
check( 'signup: no login address gives ""', '' === vance_hub_tool_result_signup_url( '', 'https://example.test/x/' ) );
check( 'signup: a login address that already has a query keeps it and adds with &', has( vance_hub_tool_result_signup_url( 'https://example.test/?p=1', '' ), '?p=1&tab=signup' ) );

$all = vance_hub_tool_result_next_cards( array( 'planner' => 'https://e.test/p/', 'guides' => 'https://e.test/g/', 'ask' => 'https://e.test/a/' ) );
check( 'next: three pages give three cards in order planner, guides, ask', array( 'planner', 'guides', 'ask' ) === array_column( $all, 'key' ) );
check( 'next: only the ask card opens the VANCE-Ai modal', array( false, false, true ) === array_column( $all, 'ask' ) );
check( 'next: no Community card', ! has( json_encode( $all ), 'ommunity' ) );
check( 'next: a missing planner page leaves that card out', array( 'guides', 'ask' ) === array_column( vance_hub_tool_result_next_cards( array( 'planner' => '', 'guides' => 'https://e.test/g/', 'ask' => 'https://e.test/a/' ) ), 'key' ) );
check( 'next: a missing guides key leaves that card out', array( 'planner', 'ask' ) === array_column( vance_hub_tool_result_next_cards( array( 'planner' => 'https://e.test/p/', 'ask' => 'https://e.test/a/' ) ), 'key' ) );
check( 'next: only ask present gives one card', array( 'ask' ) === array_column( vance_hub_tool_result_next_cards( array( 'ask' => 'https://e.test/a/' ) ), 'key' ) );
check( 'next: no pages give no cards', array() === vance_hub_tool_result_next_cards( array() ) && array() === vance_hub_tool_result_next_cards( null ) );
check( 'next: a non-string address is treated as missing', array() === vance_hub_tool_result_next_cards( array( 'planner' => array( 'x' ), 'guides' => 5, 'ask' => false ) ) );

/* ---- 5. the markup: guest and member --------------------------------------------- */
section( '5. The markup, guest and member' );

$g = vance_hub_tool_result_html( sample() );
check( 'guest: the block starts hidden and has the id the script looks for', has( $g, 'id="vhub-tool-result" hidden' ) );
check( 'guest: the heading reads "Your summary is ready"', has( $g, 'Your summary is <span>ready</span>' ) );
check( 'guest: the lead says no account is needed and that it is not a diagnosis', has( $g, 'You do not need an account. This summary is not a diagnosis.' ) );
check( 'guest: the pill reads GASTRO SELF-ASSESSMENT · COMPLETE', has( $g, 'GASTRO SELF-ASSESSMENT</p>' ) === false && has( $g, 'GASTRO SELF-ASSESSMENT &middot; COMPLETE' ) );
check( 'guest: the breadcrumb reads Tools / Gastro Self-Assessment / Your summary', has( $g, 'Tools</a> / Gastro Self-Assessment / Your summary' ) );
check( 'guest: the join card is there with its heading', has( $g, 'Join free and save your next survey' ) );
check( 'guest: the card says this summary is not kept (no false save promise)', has( $g, 'We do not keep this summary.' ) && ! has( $g, 'Save my summary' ) && ! has( $g, 'keeps this summary' ) );
check( 'guest: "Join free" is a link to the sign-up address', 1 === preg_match( '#<a class="vhub-tr__btn vhub-tr__btn--purple" href="https://example\.test/login/\?tab=signup&amp;redirect_to=[^"]+">Join free#', $g ) );
check( 'guest: no "Saved to My Hub" card', ! has( $g, 'Saved to My Hub' ) && ! has( $g, 'vhub-tr-saved' ) );
check( 'guest: no email field (no new capture form)', ! has( $g, '<input' ) && ! has( $g, '<form' ) && ! has( $g, 'type="email"' ) );
check( 'guest: no member figure, no "[N]", no "members are", no founding line', ! has( $g, '[N]' ) && ! has( $g, 'members are' ) && ! has( $g, 'ounding' ) );
check( 'guest: no blurred sample chart (no svg)', ! has( $g, '<svg' ) && ! has( $g, 'blur' ) );
check( 'guest: no privacy line (no delete path exists)', ! has( $g, 'delete them' ) && ! has( $g, 'We keep your answers private' ) );
check( 'guest: no Download as PDF', ! has( $g, 'PDF' ) );
check( 'guest: no "Questions to ask your clinician" (the survey has no such logic)', ! has( $g, 'Questions to ask' ) && ! has( $g, 'clinician' ) );
check( 'guest: no Google button when none is passed', ! has( $g, 'vhub-tr__google' ) && ! has( $g, 'Google' ) );
check( 'guest: the summary card, tiles box and print button are present', has( $g, 'id="vhub-tr-summary" hidden' ) && has( $g, 'id="vhub-tr-tiles"' ) && has( $g, 'id="vhub-tr-print"' ) );
check( 'guest: the summary card is hidden until the script has tiles', has( $g, '<section class="vhub-tr__summary" id="vhub-tr-summary" hidden' ) );
check( 'guest: "What you told us" is the summary heading', has( $g, '>What you told us<' ) );
check( 'guest: the config has fields and member false, and no nonce or ajax address', has( $g, '&quot;member&quot;:false' ) && ! has( $g, 'nonce' ) && ! has( $g, 'admin-ajax' ) );
check( 'guest: "What to do next" has three cards', has( $g, '>What to do next<' ) && 3 === substr_count( $g, 'class="vhub-tr__card ' ) );
check( 'guest: exactly one card opens VANCE-Ai', 1 === substr_count( $g, 'data-vance-askai-open="new"' ) );
check( 'guest: no Community card', ! has( $g, 'ommunity' ) );

$gg = vance_hub_tool_result_html( sample( array( 'google_html' => '<div class="g_id_signin">G</div>' ) ) );
check( 'guest: a Google button passed in is printed inside the save card, after the link', has( $gg, '<div class="vhub-tr__google"><div class="g_id_signin">G</div></div></aside>' ) && strpos( $gg, 'Join free' ) < strpos( $gg, 'g_id_signin' ) );

$gn = vance_hub_tool_result_html( sample( array( 'signup_url' => '' ) ) );
check( 'guest: with no sign-up address there is no join card at all', ! has( $gn, 'Join free and save' ) && ! has( $gn, 'vhub-tr-save-title' ) );

$m = vance_hub_tool_result_html( sample( array( 'member' => true ) ) );
check( 'member: no guest join card', ! has( $m, 'Join free and save' ) && ! has( $m, 'vhub-tr-save-title' ) && ! has( $m, 'SEE HOW YOU CHANGE' ) );
check( 'member: the "Saved to My Hub" card is there, hidden until the save succeeded', has( $m, '<aside class="vhub-tr__save vhub-tr__save--done" id="vhub-tr-saved" hidden' ) && has( $m, 'Saved to My Hub' ) );
check( 'member: the card links to the dashboard', has( $m, 'href="https://example.test/dashboard/">Open My Hub' ) );
check( 'member: the config carries the ajax address and the nonce for the script', has( $m, '&quot;member&quot;:true' ) && has( $m, 'admin-ajax.php' ) && has( $m, 'nonce-vance_quiz_nonce' ) );
check( 'member: no Google button even if one is passed', ! has( vance_hub_tool_result_html( sample( array( 'member' => true, 'google_html' => '<div class="g_id_signin">G</div>' ) ) ), 'g_id_signin' ) );

$mn = vance_hub_tool_result_html( sample( array( 'member' => true, 'dashboard_url' => '' ) ) );
check( 'member: with no dashboard page there is no "Saved to My Hub" card', ! has( $mn, 'Saved to My Hub' ) && ! has( $mn, 'vhub-tr-saved' ) );

$nf = vance_hub_tool_result_html( sample( array( 'fields' => array() ) ) );
check( 'no questions: no summary card and no print button are printed', ! has( $nf, 'vhub-tr-summary' ) && ! has( $nf, 'vhub-tr-print' ) && ! has( $nf, 'What you told us' ) );
check( 'no questions: the rest of the page (hero, save card, next steps) still renders', has( $nf, 'Your summary is' ) && has( $nf, 'Join free and save' ) && has( $nf, 'What to do next' ) );

$nx = vance_hub_tool_result_html( sample( array( 'next' => array() ) ) );
check( 'no next-step cards: the whole "What to do next" section is left out', ! has( $nx, 'What to do next' ) && ! has( $nx, 'vhub-tr__cards' ) );

$np = vance_hub_tool_result_html( sample( array( 'next' => vance_hub_tool_result_next_cards( array( 'ask' => 'https://example.test/ask-ai/' ) ) ) ) );
check( 'next-step cards: with only the Ask page, no planner and no guides card', 1 === substr_count( $np, 'class="vhub-tr__card ' ) && ! has( $np, 'Meal Planner' ) && ! has( $np, 'gut health guides' ) );

check( 'markup: no image tag when there is no image', ! has( $g, '<img' ) );
check( 'markup: an image is printed with its alt text', has( vance_hub_tool_result_html( sample( array( 'image' => 'https://example.test/i.webp', 'image_alt' => 'Two walkers' ) ) ), '<img class="vhub-tr__hero-img" src="https://example.test/i.webp" alt="Two walkers"' ) );
check( 'markup: empty input does not throw and prints an empty-ish block', has( vance_hub_tool_result_html( array() ), 'id="vhub-tool-result"' ) && has( vance_hub_tool_result_html( null ), 'Your summary is' ) );

/* ---- 6. escaping --------------------------------------------------------------------- */
section( '6. Escaping' );

$evil = '"><script>alert(1)</script>';
$e = vance_hub_tool_result_html( sample( array(
	'signup_url'    => 'https://example.test/?a=' . $evil,
	'home_url'      => 'javascript:alert(1)',
	'image'         => 'https://example.test/i.webp',
	'image_alt'     => $evil,
	'fields'        => array( array( 'field' => 'x', 'label' => $evil ) ),
	'next'          => array( array( 'key' => 'k', 'tone' => $evil, 'eyebrow' => $evil, 'title' => $evil, 'text' => $evil, 'cta' => $evil, 'url' => 'javascript:alert(2)', 'ask' => false ) ),
) ) );
check( 'escape: no raw <script> anywhere in the output', ! has( $e, '<script' ) );
check( 'escape: the sign-up address is attribute-escaped', has( $e, '?a=&quot;&gt;&lt;script&gt;' ) );
check( 'escape: a javascript: breadcrumb address is dropped (empty href)', has( $e, '<a href="">Tools</a>' ) );
check( 'escape: a javascript: card address is dropped (empty href)', has( $e, 'class="vhub-tr__card vhub-tr__card--navy" href=""' ) );
check( 'escape: an unknown card tone falls back to navy and is not printed raw', ! has( $e, 'vhub-tr__card--"' ) && has( $e, 'vhub-tr__card--navy' ) );
check( 'escape: card text is escaped', has( $e, '&lt;script&gt;alert(1)&lt;/script&gt;' ) );
check( 'escape: the image alt is attribute-escaped', has( $e, 'alt="&quot;&gt;&lt;script&gt;' ) );
check( 'escape: the config attribute holds no raw quote (a label with a quote cannot end the attribute)', 1 === preg_match( '#data-config="[^"]*"#', $e ) && has( $e, 'data-config="{&quot;fields&quot;' ) );
$cfg = preg_match( '#data-config="([^"]*)"#', $e, $mm ) ? json_decode( html_entity_decode( $mm[1], ENT_QUOTES ), true ) : null;
check( 'escape: the config attribute decodes back to the exact label', is_array( $cfg ) && $evil === $cfg['fields'][0]['label'] );

/* ---- 7. data gatherer ------------------------------------------------------------------ */
section( '7. Data gatherer: real pages only, Google only when set up' );

arrange();
$d = vance_hub_tool_result_data();
check( 'data: a guest with no pages gets no next cards, no dashboard address', array() === $d['next'] && '' === $d['dashboard_url'] );
check( 'data: a guest gets the sign-up address with the return path, built from the home address', has( $d['signup_url'], 'https://example.test/login/?tab=signup&redirect_to=' . rawurlencode( 'https://example.test/gastro-health-survey/' ) ) );
check( 'data: a guest gets no ajax address and no nonce', ! isset( $d['ajax_url'] ) && ! isset( $d['nonce'] ) );
check( 'data: the fields are the real survey questions (9)', 9 === count( $d['fields'] ) );
check( 'data: the hero image is the real theme file', has( $d['image'], '/assets/img/gi-health/ulcerative-colitis.webp' ) );
check( 'data: with no GOOGLE_CLIENT_ID there is no Google button', '' === $d['google_html'] );

arrange();
$GLOBALS['PAGES'] = array( 'gastro-meal-planner' => 'https://example.test/gastro-meal-planner/', 'gastro-health-survey' => 'https://example.test/survey-real/' );
$d = vance_hub_tool_result_data();
check( 'data: only the planner page exists: one card, the planner', array( 'planner' ) === array_column( $d['next'], 'key' ) );
check( 'data: the survey address is the real page address', has( $d['signup_url'], rawurlencode( 'https://example.test/survey-real/' ) ) );

arrange();
$GLOBALS['PAGES'] = array( 'gi-health' => 'https://example.test/gi-health/' );
$d = vance_hub_tool_result_data();
check( 'data: with no gastro-health-explained page, the gi-health page serves as the guides link', array( 'guides' ) === array_column( $d['next'], 'key' ) && 'https://example.test/gi-health/' === $d['next'][0]['url'] );

arrange();
$GLOBALS['PAGES'] = array( 'gastro-meal-planner' => 'https://example.test/m/', 'gastro-health-explained' => 'https://example.test/g/', 'ask-ai' => 'https://example.test/a/', 'dashboard' => 'https://example.test/dashboard/' );
$d = vance_hub_tool_result_data();
check( 'data: all pages exist: three cards, in order', array( 'planner', 'guides', 'ask' ) === array_column( $d['next'], 'key' ) );
check( 'data: the dashboard address is the real page', 'https://example.test/dashboard/' === $d['dashboard_url'] );

arrange();
$GLOBALS['LOGGED_IN'] = true;
$d = vance_hub_tool_result_data();
check( 'data: a member gets the ajax address and the survey save nonce', 'https://example.test/wp-admin/admin-ajax.php' === $d['ajax_url'] && 'nonce-vance_quiz_nonce' === $d['nonce'] );
check( 'data: a member gets no sign-up address and no Google button', ! isset( $d['signup_url'] ) && ! isset( $d['google_html'] ) );
check( 'data: a member with no dashboard page has an empty dashboard address (no card)', '' === $d['dashboard_url'] );

arrange();
$GLOBALS['SHORTCODES'] = array( 'google_login' => '<div class="g_id_signin">G</div>' );
$d = vance_hub_tool_result_data();
check( 'data: the shortcode exists but GOOGLE_CLIENT_ID is not defined: no Google button', '' === $d['google_html'] && null === $GLOBALS['SHORTCODE_SAW_REDIRECT'] );

define( 'GOOGLE_CLIENT_ID', 'client-id.apps.googleusercontent.com' );
arrange();
$d = vance_hub_tool_result_data();
check( 'data: GOOGLE_CLIENT_ID set but the shortcode missing: no Google button', '' === $d['google_html'] );

arrange( array(), array(), array( 'redirect_to' => 'https://example.test/original/' ) );
$GLOBALS['SHORTCODES'] = array( 'google_login' => '<div class="g_id_signin">G</div>' );
$d = vance_hub_tool_result_data();
check( 'data: with Google set up, the button markup is returned', '<div class="g_id_signin">G</div>' === $d['google_html'] );
check( 'data: the shortcode saw redirect_to = the survey address', 'https://example.test/gastro-health-survey/' === $GLOBALS['SHORTCODE_SAW_REDIRECT'] );
check( 'data: the visitor\'s own redirect_to is put back afterwards', 'https://example.test/original/' === $_GET['redirect_to'] );

arrange();
$GLOBALS['SHORTCODES'] = array( 'google_login' => '<div class="g_id_signin">G</div>' );
vance_hub_tool_result_data();
check( 'data: with no redirect_to to begin with, none is left behind', ! array_key_exists( 'redirect_to', $_GET ) );

arrange();
$GLOBALS['LOGGED_IN'] = true;
$GLOBALS['SHORTCODES'] = array( 'google_login' => '<div class="g_id_signin">G</div>' );
$GLOBALS['SHORTCODE_SAW_REDIRECT'] = 'untouched';
vance_hub_tool_result_data();
check( 'data: a member never runs the Google shortcode', 'untouched' === $GLOBALS['SHORTCODE_SAW_REDIRECT'] );

arrange();
check( 'render: with the switch off it returns an empty string', '' === vance_hub_tool_result_render() );
arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_tool_result' => true ) );
check( 'render: with the switch on it returns the block', has( vance_hub_tool_result_render(), 'id="vhub-tool-result"' ) );
arrange( array(), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'render: an administrator\'s preview returns the block', has( vance_hub_tool_result_render(), 'id="vhub-tool-result"' ) );

/* ---- 8. wiring ----------------------------------------------------------------------------- */
section( '8. Wiring: template, load order, IDs kept, stylesheet' );

$tpl = file_get_contents( $THEME . '/page-healthcare-quiz.php' );
$fn  = file_get_contents( $THEME . '/functions.php' );
$css = file_get_contents( $THEME . '/assets/css/hub-tool-result.css' );
$js  = file_get_contents( $THEME . '/assets/js/hub-tool-result.js' );
check( 'wiring: the template calls vance_hub_tool_result_render() behind function_exists', has( $tpl, "function_exists( 'vance_hub_tool_result_render' )" ) && has( $tpl, 'echo vance_hub_tool_result_render()' ) );
check( 'wiring: the call sits inside the survey wrapper (after the quiz container, before the wrapper closes)', 1 === preg_match( '#</form>\s*</div>\s*<\?php[^?]*vance_hub_tool_result_render[^?]*\?>\s*</div>\s*<script>#s', $tpl ) );
foreach ( array( 'id="results-screen"', 'id="health-quiz-form"', 'id="btn-next"', 'id="quiz-footer"', "action: 'vance_save_quiz_results'", 'wp_create_nonce("vance_quiz_nonce")', 'name="condition_type[]"', 'name="supplement_types[]"' ) as $kept ) {
	check( 'wiring: the survey keeps ' . $kept, has( $tpl, $kept ) );
}
check( 'wiring: the survey save action is still registered in functions.php', has( $fn, "add_action( 'wp_ajax_vance_save_quiz_results'" ) );
check( 'wiring: the meta key is still _sla_healthcare_quiz_results', has( $fn, "'_sla_healthcare_quiz_results'" ) );
$p_layout = strpos( $fn, "inc/hub-layout.php'" );
$p_result = strpos( $fn, "inc/hub-tool-result.php'" );
$p_admin  = strpos( $fn, "inc/hub-admin.php'" );
check( 'wiring: functions.php requires hub-tool-result.php once', 1 === substr_count( $fn, "inc/hub-tool-result.php'" ) );
check( 'wiring: it loads after hub-layout.php and after the other hub includes (hub-admin.php)', false !== $p_layout && false !== $p_result && $p_result > $p_layout && $p_result > $p_admin );
check( 'wiring: the PHP file has no stray whitespace after the closing of the last function (no ?> tag)', false === strpos( file_get_contents( $THEME . '/inc/hub-tool-result.php' ), '?>' . "\n" . '<?php' ) );

preg_match_all( '/border-radius:\s*([^;]+);/', $css, $rads );
$bad_radius = array_filter( $rads[1], function ( $v ) { return false === strpos( $v, 'var(--radius-' ); } );
check( 'css: every border-radius uses a radius token (no hard-coded radius)', count( $rads[1] ) > 5 && 0 === count( $bad_radius ) );
check( 'css: no horizontal-scroll risk: tiles and cards use min-width:0 and flex-wrap', has( $css, 'min-width: 0' ) && has( $css, 'flex-wrap: wrap' ) );
check( 'css: buttons are at least 44px tall', 1 === preg_match( '/\.vhub-tr__btn\s*\{[^}]*min-height:\s*(4[4-9]|[5-9]\d)px/s', $css ) );
check( 'css: a print rule shows only the summary card', has( $css, '@media print' ) && has( $css, '.vhub-tr__summary *' ) );
check( 'css: the old hero and form are hidden at the result state', has( $css, '.vhub-result-on > :not(.vhub-tr)' ) );
check( 'js: tiles are built with textContent (no innerHTML)', has( $js, 'textContent' ) && ! has( $js, 'innerHTML' ) );
check( 'js: it posts nothing itself: no AJAX action and no request', ! preg_match( '/action:\s*[\'"]/', $js ) && ! has( $js, '.post(' ) && ! has( $js, 'fetch(' ) );
check( 'js: it stores no answers in the browser (no sessionStorage or localStorage)', ! has( $js, 'sessionStorage' ) && ! has( $js, 'localStorage' ) && ! has( $js, 'document.cookie' ) );
check( 'js: it only listens for the survey\'s own save request', has( $js, "'action=vance_save_quiz_results'" ) );
$tr_php = (string) file_get_contents( $THEME . '/inc/hub-tool-result.php' );
check( 'enqueue: the script depends on jquery (its ajaxSuccess listener needs it)', 1 === preg_match( "#'/assets/js/hub-tool-result\.js',\s*array\(\s*'jquery'\s*\)#", $tr_php ) );
check( 'css: print takes the hero, the join card and the next steps out of the layout', 1 === preg_match( '/@media print\s*\{.*\.vhub-tr__hero,.*display:\s*none !important/s', $css ) && 1 === preg_match( '/@media print.*\.vhub-tr__summary\s*\{[^}]*position:\s*static/s', $css ) );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
