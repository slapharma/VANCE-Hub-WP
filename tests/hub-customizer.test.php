<?php
/**
 * Tests for the VanceHubClub Customizer panel: the eight sections, the forty
 * settings, and where each control sits on screen.
 *
 * Same shape as hub-admin.test.php: WordPress stubs, check() lines, and exit 1
 * on any failure. Nothing here loads WordPress.
 *
 * Why this suite exists: hub-layout.test.php, hub-header.test.php and a few
 * other suites die with a pre-existing fatal before their Customizer checks
 * run. This suite does not depend on them. It loads the real hub files and runs
 * all seven register functions against a fake manager that records panels,
 * sections, settings and controls.
 *
 * WHAT IS COVERED
 *   1  lost-setting guard: exactly 40 setting ids, each with its default and
 *      sanitize_callback (the table is hard-coded here)
 *   2  panel and sections: one panel, eight sections in screen order, panel and
 *      priority set, titles numbered 1. to 8.
 *   3  controls: each in a known section, no empty section, an integer priority,
 *      no shared priority in one section, the exact section of each control and
 *      the on-screen order inside each section
 *   4  switches: labels equal vance_hub_customizer_switches(); the admin screen
 *      reads the same thirteen mods, labels and order
 *   5  vance_hub_customizer_dashboard_home_on() and its active_callback
 *   6  texts: no "preview mode" wording in a control; the preview address lives
 *      in the panel description and in section 1 only
 *   7  admin helpers: the Customizer addresses and the seven status groups
 *
 * The one loop in this file runs over the forty-row settings table, one check()
 * per row, so a failure names the setting that broke.
 */

define( 'ABSPATH', true );
define( 'HOUR_IN_SECONDS', 3600 );
date_default_timezone_set( 'UTC' );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

/* ---- error handling: a notice in the code under test is a failure, not a pass */

set_error_handler( function ( $no, $str, $file, $line ) {
	if ( ! ( error_reporting() & $no ) ) {
		return false;
	}
	throw new ErrorException( $str, 0, $no, $file, $line );
} );

/* ---- WordPress stubs ------------------------------------------------------ */

$GLOBALS['MODS'] = array();

function __( $text, $domain = '' ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_url( $url ) { return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8', false ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }
function current_user_can( $cap ) { return false; }
function vance_get_theme_mod( $key, $default = '' ) {
	return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
}
function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) { return true; }
function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) { return true; }
function add_submenu_page( $parent, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
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

/** A registered setting as the manager returns it: only value() is used here. */
class SuiteSetting {
	private $v;
	public function __construct( $v ) { $this->v = $v; }
	public function value() { return $this->v; }
}

/** Stand-in for WP_Customize_Manager: records what the register functions add. */
class SuiteCustomizeFake {
	public $values   = array(); // Saved values, for get_setting().
	public $panels   = array();
	public $sections = array();
	public $settings = array();
	public $controls = array();
	public function add_panel( $id, $args = array() ) { $this->panels[ $id ] = $args; }
	public function add_section( $id, $args = array() ) { $this->sections[ $id ] = $args; }
	public function add_setting( $id, $args = array() ) { $this->settings[ $id ] = $args; }
	public function add_control( $id, $args = array() ) { $this->controls[ $id ] = $args; }
	public function get_setting( $id ) {
		return array_key_exists( $id, $this->values ) ? new SuiteSetting( $this->values[ $id ] ) : null;
	}
}

/* ---- load the real code under test ---------------------------------------- */

require_once $THEME . '/inc/hub-layout.php';
require_once $THEME . '/inc/hub-article.php';
require_once $THEME . '/inc/hub-dashboard-features.php';
require_once $THEME . '/inc/hub-tool-result.php';
require_once $THEME . '/inc/hub-header.php';
require_once $THEME . '/inc/hub-community.php';
require_once $THEME . '/inc/hub-flare-plan.php';
require_once $THEME . '/inc/hub-admin.php';

/** Control ids of one section, in on-screen order (sorted by priority). */
function suite_section_order( $manager, $section ) {
	$by_priority = array();
	foreach ( $manager->controls as $id => $control ) {
		if ( $control['section'] === $section ) {
			$by_priority[ $control['priority'] ] = $id;
		}
	}
	ksort( $by_priority );
	return array_values( $by_priority );
}

/* ---- run all seven register functions once ------------------------------- */

$M = new SuiteCustomizeFake();
vance_hub_customize_register( $M );
vance_hub_article_customize_register( $M );
vance_hub_dash_features_customize( $M );
vance_hub_tool_result_customize_register( $M );
vance_hub_header_customize_register( $M );
vance_hub_community_customize_register( $M );
vance_hub_flare_plan_customize( $M );

/* ---- hard-coded expectations ---------------------------------------------- */

// Setting id => array( default, sanitize_callback, section ). From the agreed table.
$SETTINGS = array(
	'vance_home_layout'                 => array( 'classic', 'vance_hub_sanitize_layout', 'vance_home_layout' ),
	'vance_hub_show_promo'              => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_home' ),
	'vance_hub_show_sections'           => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_home' ),
	'vance_hub_show_more'               => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_home' ),
	'vance_hub_tile_1_badge'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home' ),
	'vance_hub_tile_1_title'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home' ),
	'vance_hub_tile_1_text'             => array( '', 'sanitize_textarea_field', 'vance_hub_sec_home' ),
	'vance_hub_tile_1_link'             => array( '', 'esc_url_raw', 'vance_hub_sec_home' ),
	'vance_hub_tile_2_badge'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home' ),
	'vance_hub_tile_2_title'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home' ),
	'vance_hub_tile_2_text'             => array( '', 'sanitize_textarea_field', 'vance_hub_sec_home' ),
	'vance_hub_tile_2_link'             => array( '', 'esc_url_raw', 'vance_hub_sec_home' ),
	'vance_hub_tile_3_badge'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home' ),
	'vance_hub_tile_3_title'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home' ),
	'vance_hub_tile_3_text'             => array( '', 'sanitize_textarea_field', 'vance_hub_sec_home' ),
	'vance_hub_tile_3_link'             => array( '', 'esc_url_raw', 'vance_hub_sec_home' ),
	'vance_hub_tile_4_badge'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home' ),
	'vance_hub_tile_4_title'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home' ),
	'vance_hub_tile_4_text'             => array( '', 'sanitize_textarea_field', 'vance_hub_sec_home' ),
	'vance_hub_tile_4_link'             => array( '', 'esc_url_raw', 'vance_hub_sec_home' ),
	'vance_hub_header'                  => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_header' ),
	'vance_hub_member_min'              => array( 100, 'absint', 'vance_hub_sec_header' ),
	'vance_hub_founding_deadline'       => array( '', 'vance_hub_sanitize_date', 'vance_hub_sec_header' ),
	'vance_hub_articles'                => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_pages' ),
	'vance_hub_tool_result'             => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_pages' ),
	'vance_hub_show_dashboard'          => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_dashboard' ),
	'vance_hub_dashboard_tabs'          => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_dashboard' ),
	'vance_hub_dash_member_no'          => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_dashboard' ),
	'vance_hub_dash_chart'              => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_dashboard' ),
	'vance_hub_community'               => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_community' ),
	'vance_hub_dash_checkin'            => array( false, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_checkin' ),
	'vance_hub_checkin_q_pain'          => array( 'How is your tummy pain today?', 'sanitize_text_field', 'vance_hub_sec_checkin' ),
	'vance_hub_checkin_q_bowel'         => array( 'How are your bowel symptoms today?', 'sanitize_text_field', 'vance_hub_sec_checkin' ),
	'vance_hub_checkin_q_tired'         => array( 'How tired are you today?', 'sanitize_text_field', 'vance_hub_sec_checkin' ),
	'vance_hub_checkin_consent'         => array( 'I agree that the Hub stores my answers in my account. I can delete them at any time.', 'sanitize_text_field', 'vance_hub_sec_checkin' ),
	'vance_hub_checkin_note'            => array( 'This is a record for you. It is not medical advice. Speak to your health team if your symptoms get worse.', 'sanitize_text_field', 'vance_hub_sec_checkin' ),
	'vance_hub_dash_flare_plan'         => array( false, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_flare' ),
	'vance_hub_flare_plan_urgent'       => array( '', 'sanitize_textarea_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_plan_consent'      => array( 'I agree that the Hub stores my flare plan and my flare dates in my account. I can delete them at any time.', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_plan_note'         => array( 'This plan is your own record. It is not medical advice. Share it with your health team.', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	// Phase 2: a name and a help text for each flare plan section; an on/off box for each optional section.
	'vance_hub_flare_sec_signs_label'       => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_signs_hint'        => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_actions_label'     => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_actions_hint'      => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_contacts_label'    => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_contacts_hint'     => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_medicines_label'   => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_medicines_hint'    => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_medicines_on'      => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_told_label'        => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_told_hint'         => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_told_on'           => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_triggers_label'    => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_triggers_hint'     => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_triggers_on'       => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_helps_label'       => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_helps_hint'        => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_helps_on'          => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_questions_label'   => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_questions_hint'    => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_questions_on'      => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_review_on_label'   => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_review_on_hint'    => array( '', 'sanitize_text_field', 'vance_hub_sec_flare' ),
	'vance_hub_flare_sec_review_on_on'      => array( true, 'vance_hub_sanitize_checkbox', 'vance_hub_sec_flare' ),
);

$SECTION_IDS = array(
	'vance_home_layout',
	'vance_hub_sec_home',
	'vance_hub_sec_header',
	'vance_hub_sec_pages',
	'vance_hub_sec_dashboard',
	'vance_hub_sec_community',
	'vance_hub_sec_checkin',
	'vance_hub_sec_flare',
);

// Section id => control ids in on-screen order.
$ORDER = array(
	'vance_home_layout'       => array( 'vance_home_layout' ),
	'vance_hub_sec_home'      => array(
		'vance_hub_show_promo', 'vance_hub_show_sections', 'vance_hub_show_more',
		'vance_hub_tile_1_badge', 'vance_hub_tile_1_title', 'vance_hub_tile_1_text', 'vance_hub_tile_1_link',
		'vance_hub_tile_2_badge', 'vance_hub_tile_2_title', 'vance_hub_tile_2_text', 'vance_hub_tile_2_link',
		'vance_hub_tile_3_badge', 'vance_hub_tile_3_title', 'vance_hub_tile_3_text', 'vance_hub_tile_3_link',
		'vance_hub_tile_4_badge', 'vance_hub_tile_4_title', 'vance_hub_tile_4_text', 'vance_hub_tile_4_link',
	),
	'vance_hub_sec_header'    => array( 'vance_hub_header', 'vance_hub_member_min', 'vance_hub_founding_deadline' ),
	'vance_hub_sec_pages'     => array( 'vance_hub_articles', 'vance_hub_tool_result' ),
	'vance_hub_sec_dashboard' => array( 'vance_hub_show_dashboard', 'vance_hub_dashboard_tabs', 'vance_hub_dash_member_no', 'vance_hub_dash_chart' ),
	'vance_hub_sec_community' => array( 'vance_hub_community' ),
	'vance_hub_sec_checkin'   => array( 'vance_hub_dash_checkin', 'vance_hub_checkin_q_pain', 'vance_hub_checkin_q_bowel', 'vance_hub_checkin_q_tired', 'vance_hub_checkin_consent', 'vance_hub_checkin_note' ),
	'vance_hub_sec_flare'     => array(
		'vance_hub_dash_flare_plan', 'vance_hub_flare_plan_urgent', 'vance_hub_flare_plan_consent', 'vance_hub_flare_plan_note',
		'vance_hub_flare_sec_signs_label', 'vance_hub_flare_sec_signs_hint',
		'vance_hub_flare_sec_actions_label', 'vance_hub_flare_sec_actions_hint',
		'vance_hub_flare_sec_contacts_label', 'vance_hub_flare_sec_contacts_hint',
		'vance_hub_flare_sec_medicines_label', 'vance_hub_flare_sec_medicines_hint', 'vance_hub_flare_sec_medicines_on',
		'vance_hub_flare_sec_told_label', 'vance_hub_flare_sec_told_hint', 'vance_hub_flare_sec_told_on',
		'vance_hub_flare_sec_triggers_label', 'vance_hub_flare_sec_triggers_hint', 'vance_hub_flare_sec_triggers_on',
		'vance_hub_flare_sec_helps_label', 'vance_hub_flare_sec_helps_hint', 'vance_hub_flare_sec_helps_on',
		'vance_hub_flare_sec_questions_label', 'vance_hub_flare_sec_questions_hint', 'vance_hub_flare_sec_questions_on',
		'vance_hub_flare_sec_review_on_label', 'vance_hub_flare_sec_review_on_hint', 'vance_hub_flare_sec_review_on_on',
	),
);

// Section id => the titles the screen shows, as the register code builds them.
$TITLES = array(
	'vance_home_layout'       => '1. On or off',
	'vance_hub_sec_home'      => '2. Home page',
	'vance_hub_sec_header'    => '3. Header and member bar',
	'vance_hub_sec_pages'     => '4. Articles and Self-Assessment',
	'vance_hub_sec_dashboard' => '5. Member dashboard',
	'vance_hub_sec_community' => '6. Community',
	'vance_hub_sec_checkin'   => '7. Daily check-in (health data)',
	'vance_hub_sec_flare'     => '8. Flare plan (health data)',
);

/* ---- check() and section() ------------------------------------------------ */

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/* ===================================================================== */

section( '1. Lost-setting guard: forty settings, with their defaults and sanitisers' );

$IDS = array_keys( $M->settings );
$EXP = array_keys( $SETTINGS );
sort( $IDS );
sort( $EXP );
check( 'settings: exactly sixty-four setting ids are registered', count( $M->settings ) === 64 );
check( 'settings: the registered ids are exactly the agreed sixty-four', $IDS === $EXP );

foreach ( $SETTINGS as $id => $row ) {
	check( "setting $id: default equals the agreed default", isset( $M->settings[ $id ] ) && $M->settings[ $id ]['default'] === $row[0] );
	check( "setting $id: sanitize_callback equals the agreed sanitiser", isset( $M->settings[ $id ] ) && $M->settings[ $id ]['sanitize_callback'] === $row[1] );
}

check( 'settings: the check-in texts equal vance_hub_checkin_text_defaults()', vance_hub_checkin_text_defaults()['vance_hub_checkin_note'] === $M->settings['vance_hub_checkin_note']['default'] && vance_hub_checkin_text_defaults()['vance_hub_checkin_q_pain'] === $M->settings['vance_hub_checkin_q_pain']['default'] );
check( 'settings: the flare plan texts equal vance_hub_flare_plan_text_defaults()', vance_hub_flare_plan_text_defaults()['vance_hub_flare_plan_consent'] === $M->settings['vance_hub_flare_plan_consent']['default'] && vance_hub_flare_plan_text_defaults()['vance_hub_flare_plan_note'] === $M->settings['vance_hub_flare_plan_note']['default'] );

section( '2. The panel and the eight sections' );

check( 'panel: exactly one panel is registered', count( $M->panels ) === 1 );
check( 'panel: it is vance_hub_panel', isset( $M->panels['vance_hub_panel'] ) );
check( 'panel: its priority is 1', isset( $M->panels['vance_hub_panel']['priority'] ) && $M->panels['vance_hub_panel']['priority'] === 1 );
check( 'panel: its title is VanceHubClub', isset( $M->panels['vance_hub_panel']['title'] ) && $M->panels['vance_hub_panel']['title'] === 'VanceHubClub' );

check( 'sections: exactly eight sections are registered', count( $M->sections ) === 8 );
check( 'sections: the sections are in the order of vance_hub_customizer_sections()', array_keys( $M->sections ) === array_keys( vance_hub_customizer_sections() ) );
check( 'sections: the order is the agreed eight, from section 1 to section 8', array_keys( $M->sections ) === $SECTION_IDS );

$PANEL_OF = array_column( array_values( $M->sections ), 'panel' );
check( 'sections: every section sits in vance_hub_panel', $PANEL_OF === array_fill( 0, 8, 'vance_hub_panel' ) );

$PRIORITIES = array_column( array_values( $M->sections ), 'priority' );
$SORTED     = $PRIORITIES;
sort( $SORTED );
check( 'sections: priorities strictly rise in screen order', $PRIORITIES === $SORTED && count( array_unique( $PRIORITIES ) ) === 8 );

$TITLE_NUMBERS = array_map( function ( $t ) { return preg_match( '/^(\d)\. /', $t, $m ) ? (int) $m[1] : 0; }, array_column( array_values( $M->sections ), 'title' ) );
check( 'sections: titles start 1. to 8. in order', $TITLE_NUMBERS === array( 1, 2, 3, 4, 5, 6, 7, 8 ) );
check( 'sections: each title is the agreed title', array_column( array_values( $M->sections ), 'title' ) === array_values( $TITLES ) );

section( '3. Controls: sections, priorities and on-screen order' );

$CONTROL_SECTIONS = array_column( $M->controls, 'section' );
check( 'controls: sixty-four controls are registered', count( $M->controls ) === 64 );
check( 'controls: every control sits in one of the eight sections', array_diff( $CONTROL_SECTIONS, $SECTION_IDS ) === array() );
check( 'controls: no section is empty (eight distinct sections are used)', count( array_count_values( $CONTROL_SECTIONS ) ) === 8 );
check( 'controls: every control has an integer priority', count( array_filter( array_column( $M->controls, 'priority' ), 'is_int' ) ) === 64 );

check( 'controls: no two controls in one section share a priority', count( suite_section_order( $M, 'vance_home_layout' ) ) + count( suite_section_order( $M, 'vance_hub_sec_home' ) ) + count( suite_section_order( $M, 'vance_hub_sec_header' ) ) + count( suite_section_order( $M, 'vance_hub_sec_pages' ) ) + count( suite_section_order( $M, 'vance_hub_sec_dashboard' ) ) + count( suite_section_order( $M, 'vance_hub_sec_community' ) ) + count( suite_section_order( $M, 'vance_hub_sec_checkin' ) ) + count( suite_section_order( $M, 'vance_hub_sec_flare' ) ) === 64 );

foreach ( $SETTINGS as $id => $row ) {
	check( "section: control $id is in {$row[2]}", isset( $M->controls[ $id ] ) && $M->controls[ $id ]['section'] === $row[2] );
}

check( 'order: section 1 holds the master switch only', suite_section_order( $M, 'vance_home_layout' ) === $ORDER['vance_home_layout'] );
check( 'order: section 2 runs promo, sections, more, then tiles 1 to 4 (badge, title, text, link)', suite_section_order( $M, 'vance_hub_sec_home' ) === $ORDER['vance_hub_sec_home'] );
check( 'order: section 3 runs header, member total, founding deadline', suite_section_order( $M, 'vance_hub_sec_header' ) === $ORDER['vance_hub_sec_header'] );
check( 'order: section 4 runs articles, then the survey result page', suite_section_order( $M, 'vance_hub_sec_pages' ) === $ORDER['vance_hub_sec_pages'] );
check( 'order: section 5 runs dashboard home, tab look, member number, chart', suite_section_order( $M, 'vance_hub_sec_dashboard' ) === $ORDER['vance_hub_sec_dashboard'] );
check( 'order: section 6 is the community switch alone', suite_section_order( $M, 'vance_hub_sec_community' ) === $ORDER['vance_hub_sec_community'] );
check( 'order: section 7 runs the check-in switch, then its five texts', suite_section_order( $M, 'vance_hub_sec_checkin' ) === $ORDER['vance_hub_sec_checkin'] );
check( 'order: section 8 runs the flare switch, urgent text, consent, note, then each section name, help text and optional switch',suite_section_order( $M, 'vance_hub_sec_flare' ) === $ORDER['vance_hub_sec_flare'] );

section( '4. Switch labels and the admin screen rows' );

$SWITCHES = vance_hub_customizer_switches();
check( 'switches: thirteen switches are listed', count( $SWITCHES ) === 13 );

foreach ( $SWITCHES as $mod => $switch ) {
	check( "switch $mod: control label is the one from vance_hub_customizer_switches()", isset( $M->controls[ $mod ] ) && $M->controls[ $mod ]['label'] === $switch['label'] );
	check( "switch $mod: control is in the section the switch names", isset( $M->controls[ $mod ] ) && $M->controls[ $mod ]['section'] === $switch['section'] );
	check( "switch $mod: control is a checkbox", isset( $M->controls[ $mod ] ) && $M->controls[ $mod ]['type'] === 'checkbox' );
}

$ADMIN_ROWS = vance_hub_admin_switches();
check( 'admin: the screen reads the same thirteen mods in the same order', array_column( $ADMIN_ROWS, 'mod' ) === array_keys( $SWITCHES ) );
check( 'admin: the screen reads the same labels in the same order', array_column( $ADMIN_ROWS, 'label' ) === array_column( $SWITCHES, 'label' ) );
check( 'admin: the screen reads the same sections in the same order', array_column( $ADMIN_ROWS, 'section' ) === array_column( $SWITCHES, 'section' ) );

section( '5. The dashboard-home gate' );

check( 'gate: vance_hub_dashboard_tabs is the only control with an active_callback', array_keys( array_filter( $M->controls, function ( $c ) { return isset( $c['active_callback'] ); } ) ) === array( 'vance_hub_dashboard_tabs' ) );
check( 'gate: the callback is vance_hub_customizer_dashboard_home_on', $M->controls['vance_hub_dashboard_tabs']['active_callback'] === 'vance_hub_customizer_dashboard_home_on' );

$GATE = new SuiteCustomizeFake();
$GATE->values['vance_hub_show_dashboard'] = true;
$GATE_CONTROL = (object) array( 'manager' => $GATE );
check( 'gate: true when the dashboard home is saved true', vance_hub_customizer_dashboard_home_on( $GATE_CONTROL ) === true );

$GATE->values['vance_hub_show_dashboard'] = '1';
check( 'gate: true when the dashboard home is saved "1"', vance_hub_customizer_dashboard_home_on( $GATE_CONTROL ) === true );

$GATE->values['vance_hub_show_dashboard'] = 1;
check( 'gate: true when the dashboard home is saved 1', vance_hub_customizer_dashboard_home_on( $GATE_CONTROL ) === true );

$GATE->values['vance_hub_show_dashboard'] = false;
check( 'gate: false when the dashboard home is saved false', vance_hub_customizer_dashboard_home_on( $GATE_CONTROL ) === false );

$GATE->values['vance_hub_show_dashboard'] = '';
check( 'gate: false when the dashboard home is saved the empty string', vance_hub_customizer_dashboard_home_on( $GATE_CONTROL ) === false );

$GATE->values['vance_hub_show_dashboard'] = 0;
check( 'gate: false when the dashboard home is saved 0', vance_hub_customizer_dashboard_home_on( $GATE_CONTROL ) === false );

$GATE->values['vance_hub_show_dashboard'] = '0';
check( 'gate: false when the dashboard home is saved "0"', vance_hub_customizer_dashboard_home_on( $GATE_CONTROL ) === false );

$NO_SETTING = new SuiteCustomizeFake();
check( 'gate: true when the manager has no dashboard-home setting', vance_hub_customizer_dashboard_home_on( (object) array( 'manager' => $NO_SETTING ) ) === true );

section( '6. Wording: the preview instruction lives in the panel and section 1' );

$ALL_DESCRIPTIONS = implode( "\n", array_column( $M->controls, 'description' ) );
check( 'wording: no control description says "preview mode"', strpos( $ALL_DESCRIPTIONS, 'preview mode' ) === false );
check( 'wording: no control description says "Administrators can preview"', strpos( $ALL_DESCRIPTIONS, 'Administrators can preview' ) === false );

$OTHER_DESCRIPTIONS = array_map( function ( $c ) { return $c['description'] ?? ''; }, $M->controls );
unset( $OTHER_DESCRIPTIONS['vance_home_layout'] );
check( 'wording: only the section 1 control holds the ?vhh_layout address', strpos( implode( "\n", $OTHER_DESCRIPTIONS ), 'vhh_layout' ) === false );
check( 'wording: the section 1 control holds ?vhh_layout=hub', strpos( $M->controls['vance_home_layout']['description'], '?vhh_layout=hub' ) !== false );
check( 'wording: the panel description contains ?vhh_layout=hub', isset( $M->panels['vance_hub_panel']['description'] ) && strpos( $M->panels['vance_hub_panel']['description'], '?vhh_layout=hub' ) !== false );

section( '7. Admin helpers: addresses and status groups' );

check( 'address: the Customizer address opens the panel', vance_hub_admin_customizer_url() === 'https://example.test/wp-admin/customize.php?autofocus%5Bpanel%5D=vance_hub_panel' );
check( 'address: the section address opens the given section', vance_hub_admin_section_url( 'vance_hub_sec_checkin' ) === 'https://example.test/wp-admin/customize.php?autofocus%5Bsection%5D=vance_hub_sec_checkin' );

$GROUPS = vance_hub_admin_status_groups();
check( 'groups: seven groups, one per section that has a switch', array_keys( $GROUPS ) === array( 'vance_hub_sec_home', 'vance_hub_sec_header', 'vance_hub_sec_pages', 'vance_hub_sec_dashboard', 'vance_hub_sec_community', 'vance_hub_sec_checkin', 'vance_hub_sec_flare' ) );
check( 'groups: section 1 is not a group', ! isset( $GROUPS['vance_home_layout'] ) );

check( 'groups: home holds promo, sections and more', array_column( $GROUPS['vance_hub_sec_home']['rows'], 'label' ) === array( vance_hub_customizer_switch( 'vance_hub_show_promo' )['label'], vance_hub_customizer_switch( 'vance_hub_show_sections' )['label'], vance_hub_customizer_switch( 'vance_hub_show_more' )['label'] ) );
check( 'groups: header holds the header switch', array_column( $GROUPS['vance_hub_sec_header']['rows'], 'label' ) === array( vance_hub_customizer_switch( 'vance_hub_header' )['label'] ) );
check( 'groups: pages hold articles and the survey result page', array_column( $GROUPS['vance_hub_sec_pages']['rows'], 'label' ) === array( vance_hub_customizer_switch( 'vance_hub_articles' )['label'], vance_hub_customizer_switch( 'vance_hub_tool_result' )['label'] ) );
check( 'groups: dashboard holds the four dashboard switches', array_column( $GROUPS['vance_hub_sec_dashboard']['rows'], 'label' ) === array( vance_hub_customizer_switch( 'vance_hub_show_dashboard' )['label'], vance_hub_customizer_switch( 'vance_hub_dashboard_tabs' )['label'], vance_hub_customizer_switch( 'vance_hub_dash_member_no' )['label'], vance_hub_customizer_switch( 'vance_hub_dash_chart' )['label'] ) );
check( 'groups: community holds the community switch', array_column( $GROUPS['vance_hub_sec_community']['rows'], 'label' ) === array( vance_hub_customizer_switch( 'vance_hub_community' )['label'] ) );
check( 'groups: check-in holds the check-in switch', array_column( $GROUPS['vance_hub_sec_checkin']['rows'], 'label' ) === array( vance_hub_customizer_switch( 'vance_hub_dash_checkin' )['label'] ) );
check( 'groups: flare plan holds the flare switch', array_column( $GROUPS['vance_hub_sec_flare']['rows'], 'label' ) === array( vance_hub_customizer_switch( 'vance_hub_dash_flare_plan' )['label'] ) );

check( 'groups: each group title is the section title', $GROUPS['vance_hub_sec_home']['title'] === '2. Home page' && $GROUPS['vance_hub_sec_header']['title'] === '3. Header and member bar' && $GROUPS['vance_hub_sec_pages']['title'] === '4. Articles and Self-Assessment' && $GROUPS['vance_hub_sec_dashboard']['title'] === '5. Member dashboard' && $GROUPS['vance_hub_sec_community']['title'] === '6. Community' && $GROUPS['vance_hub_sec_checkin']['title'] === '7. Daily check-in (health data)' && $GROUPS['vance_hub_sec_flare']['title'] === '8. Flare plan (health data)' );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
