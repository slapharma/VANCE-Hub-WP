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
function is_user_logged_in() { return ! empty( $GLOBALS['LOGGED_IN'] ); }
function vance_get_theme_mod( $key, $default = '' ) {
	return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
}
function wp_timezone_string() { return 'Europe/London'; } // The go-live control names the site zone.
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
require_once $THEME . '/inc/hub-home-settings.php';
require_once $THEME . '/inc/hub-golive.php';
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

/* ---- run all eight register functions once ------------------------------- */

$M = new SuiteCustomizeFake();
vance_hub_customize_register( $M );
vance_hub_home_customize_register( $M );
vance_hub_golive_customize_register( $M );
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
	// The three retired switches keep their setting (section null: no control any more).
	'vance_hub_show_promo'              => array( true, 'vance_hub_sanitize_checkbox', null ),
	'vance_hub_show_sections'           => array( true, 'vance_hub_sanitize_checkbox', null ),
	'vance_hub_show_more'               => array( true, 'vance_hub_sanitize_checkbox', null ),
	'vance_hub_tile_1_badge'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_1_title'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_1_text'             => array( '', 'sanitize_textarea_field', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_1_link'             => array( '', 'esc_url_raw', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_2_badge'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_2_title'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_2_text'             => array( '', 'sanitize_textarea_field', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_2_link'             => array( '', 'esc_url_raw', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_3_badge'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_3_title'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_3_text'             => array( '', 'sanitize_textarea_field', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_3_link'             => array( '', 'esc_url_raw', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_4_badge'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_4_title'            => array( '', 'sanitize_text_field', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_4_text'             => array( '', 'sanitize_textarea_field', 'vance_hub_sec_home_tiles' ),
	'vance_hub_tile_4_link'             => array( '', 'esc_url_raw', 'vance_hub_sec_home_tiles' ),
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
);

// The 28 new settings (block order, five counts, 22 texts): id => array( default, sanitize_callback, section ).
// Hard-coded here on purpose: a text or a number changed in the code must fail this table.
$NEW_SETTINGS = array(
	'vance_hub_block_order'        => array( 'tools,inside,conditions,community,promo,library,sections,more,join', 'vance_hub_sanitize_block_order', 'vance_hub_sec_home' ),
	'vance_hub_count_sections'     => array( 3, 'vance_hub_sanitize_count', 'vance_hub_sec_home' ),
	'vance_hub_count_more'         => array( 6, 'vance_hub_sanitize_count', 'vance_hub_sec_home' ),
	'vance_hub_count_library'      => array( 4, 'vance_hub_sanitize_count', 'vance_hub_sec_home' ),
	'vance_hub_count_recipes'      => array( 2, 'vance_hub_sanitize_count', 'vance_hub_sec_home' ),
	'vance_hub_count_quotes'       => array( 4, 'vance_hub_sanitize_count', 'vance_hub_sec_home' ),
	'vance_hub_text_hero_pill'     => array( 'Your Gastro Health Community', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_hero_title'    => array( 'What do you need help with', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_hero_accent'   => array( 'today?', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_hero_sub'      => array( 'Take the Gastro Self-Assessment, plan meals, read the science and get plain answers from our VANCE-Ai.', 'sanitize_textarea_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_survey_label'  => array( 'Gastro Self-Assessment', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_survey_title'  => array( 'Not sure where to start?', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_survey_text'   => array( 'Answer a few short questions about you and your gut. The Hub then points you to the guides, tools and recipes that fit.', 'sanitize_textarea_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_survey_button' => array( 'Start the survey', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_fact_1_title'  => array( 'Free Forever', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_fact_1_text'   => array( 'Every tool available 24/7', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_fact_2_title'  => array( 'Activity driven', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_fact_2_text'   => array( 'Save searches, answers and results.', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_fact_3_title'  => array( 'Verified Science', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_fact_3_text'   => array( 'Every clinical source referenced.', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_join_pill'     => array( 'Membership is free', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_join_title'    => array( 'Your place is ready.', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_join_text'     => array( 'Members keep their survey answers, tool results and meal plans. They can save articles and notes, and their VANCE-Ai chats stay in their dashboard.', 'sanitize_textarea_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_join_card'     => array( 'Take your place', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_join_button'   => array( 'Create my free account', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_member_title'  => array( 'Pick up where you left off.', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_member_text'   => array( 'Your saved articles, notes, results and chats are in your dashboard.', 'sanitize_textarea_field', 'vance_hub_sec_home_text' ),
	'vance_hub_text_member_button' => array( 'Go to My Hub', 'sanitize_text_field', 'vance_hub_sec_home_text' ),
);

// The 25 later settings: the 24 tool card settings (4 slots x 6) and the go-live date.
// Same shape as the tables above. Written out by slot, on purpose: a default or a
// sanitiser changed in the code must fail this table.
$TOOL_SETTINGS = array();
foreach ( array( 1 => 'survey', 2 => 'nutrition', 3 => 'recipes', 4 => 'discounts' ) as $slot => $pick ) {
	$TOOL_SETTINGS[ "vance_hub_tool_{$slot}_pick" ]   = array( $pick, 'vance_hub_sanitize_tool_pick', 'vance_hub_sec_home_tools' );
	$TOOL_SETTINGS[ "vance_hub_tool_{$slot}_title" ]  = array( '', 'sanitize_text_field', 'vance_hub_sec_home_tools' );
	$TOOL_SETTINGS[ "vance_hub_tool_{$slot}_text" ]   = array( '', 'sanitize_textarea_field', 'vance_hub_sec_home_tools' );
	$TOOL_SETTINGS[ "vance_hub_tool_{$slot}_button" ] = array( '', 'sanitize_text_field', 'vance_hub_sec_home_tools' );
	$TOOL_SETTINGS[ "vance_hub_tool_{$slot}_link" ]   = array( '', 'esc_url_raw', 'vance_hub_sec_home_tools' );
	$TOOL_SETTINGS[ "vance_hub_tool_{$slot}_image" ]  = array( 0, 'absint', 'vance_hub_sec_home_tools' );
}
$TOOL_SETTINGS['vance_hub_golive_at'] = array( '', 'vance_hub_sanitize_datetime', 'vance_home_layout' );

$SECTION_IDS = array(
	'vance_home_layout',
	'vance_hub_sec_home',
	'vance_hub_sec_home_text',
	'vance_hub_sec_home_tools',
	'vance_hub_sec_home_tiles',
	'vance_hub_sec_header',
	'vance_hub_sec_pages',
	'vance_hub_sec_dashboard',
	'vance_hub_sec_community',
	'vance_hub_sec_checkin',
	'vance_hub_sec_flare',
);

// Section id => control ids in on-screen order.
$ORDER = array(
	'vance_home_layout'       => array( 'vance_home_layout', 'vance_hub_golive_at' ),
	'vance_hub_sec_home'      => array(
		'vance_hub_block_order', 'vance_hub_count_sections', 'vance_hub_count_more', 'vance_hub_count_library', 'vance_hub_count_recipes', 'vance_hub_count_quotes',
	),
	'vance_hub_sec_home_text' => array(
		'vance_hub_text_hero_pill', 'vance_hub_text_hero_title', 'vance_hub_text_hero_accent', 'vance_hub_text_hero_sub',
		'vance_hub_text_survey_label', 'vance_hub_text_survey_title', 'vance_hub_text_survey_text', 'vance_hub_text_survey_button',
		'vance_hub_text_fact_1_title', 'vance_hub_text_fact_1_text', 'vance_hub_text_fact_2_title', 'vance_hub_text_fact_2_text', 'vance_hub_text_fact_3_title', 'vance_hub_text_fact_3_text',
		'vance_hub_text_join_pill', 'vance_hub_text_join_title', 'vance_hub_text_join_text', 'vance_hub_text_join_card', 'vance_hub_text_join_button',
		'vance_hub_text_member_title', 'vance_hub_text_member_text', 'vance_hub_text_member_button',
	),
	// Four cards, six controls each: tool, title, text, button, link, image.
	'vance_hub_sec_home_tools' => array(
		'vance_hub_tool_1_pick', 'vance_hub_tool_1_title', 'vance_hub_tool_1_text', 'vance_hub_tool_1_button', 'vance_hub_tool_1_link', 'vance_hub_tool_1_image',
		'vance_hub_tool_2_pick', 'vance_hub_tool_2_title', 'vance_hub_tool_2_text', 'vance_hub_tool_2_button', 'vance_hub_tool_2_link', 'vance_hub_tool_2_image',
		'vance_hub_tool_3_pick', 'vance_hub_tool_3_title', 'vance_hub_tool_3_text', 'vance_hub_tool_3_button', 'vance_hub_tool_3_link', 'vance_hub_tool_3_image',
		'vance_hub_tool_4_pick', 'vance_hub_tool_4_title', 'vance_hub_tool_4_text', 'vance_hub_tool_4_button', 'vance_hub_tool_4_link', 'vance_hub_tool_4_image',
	),
	'vance_hub_sec_home_tiles' => array(
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
	'vance_hub_sec_flare'     => array( 'vance_hub_dash_flare_plan', 'vance_hub_flare_plan_urgent', 'vance_hub_flare_plan_consent', 'vance_hub_flare_plan_note' ),
);

// Section id => the titles the screen shows, as the register code builds them.
$TITLES = array(
	'vance_home_layout'        => '1. On or off',
	'vance_hub_sec_home'       => '2. Home page: blocks and counts',
	'vance_hub_sec_home_text'  => '3. Home page: text',
	'vance_hub_sec_home_tools' => '4. Home page: tool cards',
	'vance_hub_sec_home_tiles' => '5. Home page: member tiles',
	'vance_hub_sec_header'     => '6. Header and member bar',
	'vance_hub_sec_pages'      => '7. Articles and Self-Assessment',
	'vance_hub_sec_dashboard'  => '8. Member dashboard',
	'vance_hub_sec_community'  => '9. Community',
	'vance_hub_sec_checkin'    => '10. Daily check-in (health data)',
	'vance_hub_sec_flare'      => '11. Flare plan (health data)',
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
check( 'settings: the agreed table holds exactly forty original ids', count( $SETTINGS ) === 40 );
check( 'settings: the registered ids are exactly the original forty plus the 28 new ones plus the 25 tool card and go-live ones (93)', count( $M->settings ) === 93 );
check( 'settings: the original forty are all still registered', array_diff( $EXP, $IDS ) === array() );
$EXP_ALL = array_merge( $EXP, array_keys( $NEW_SETTINGS ), array_keys( $TOOL_SETTINGS ) );
sort( $EXP_ALL );
check( 'settings: the registered ids are exactly the original forty plus the agreed 28 plus the agreed 25', $IDS === $EXP_ALL && count( $NEW_SETTINGS ) === 28 && count( $TOOL_SETTINGS ) === 25 );

foreach ( $SETTINGS as $id => $row ) {
	check( "setting $id: default equals the agreed default", isset( $M->settings[ $id ] ) && $M->settings[ $id ]['default'] === $row[0] );
	check( "setting $id: sanitize_callback equals the agreed sanitiser", isset( $M->settings[ $id ] ) && $M->settings[ $id ]['sanitize_callback'] === $row[1] );
}

foreach ( $NEW_SETTINGS as $id => $row ) {
	check( "new setting $id: default equals the agreed default", isset( $M->settings[ $id ] ) && $M->settings[ $id ]['default'] === $row[0] );
	check( "new setting $id: sanitize_callback equals the agreed sanitiser", isset( $M->settings[ $id ] ) && $M->settings[ $id ]['sanitize_callback'] === $row[1] );
}

foreach ( $TOOL_SETTINGS as $id => $row ) {
	check( "tool/go-live setting $id: default equals the agreed default", isset( $M->settings[ $id ] ) && $M->settings[ $id ]['default'] === $row[0] );
	check( "tool/go-live setting $id: sanitize_callback equals the agreed sanitiser", isset( $M->settings[ $id ] ) && $M->settings[ $id ]['sanitize_callback'] === $row[1] );
}

check( 'settings: the check-in texts equal vance_hub_checkin_text_defaults()', vance_hub_checkin_text_defaults()['vance_hub_checkin_note'] === $M->settings['vance_hub_checkin_note']['default'] && vance_hub_checkin_text_defaults()['vance_hub_checkin_q_pain'] === $M->settings['vance_hub_checkin_q_pain']['default'] );
check( 'settings: the flare plan texts equal vance_hub_flare_plan_text_defaults()', vance_hub_flare_plan_text_defaults()['vance_hub_flare_plan_consent'] === $M->settings['vance_hub_flare_plan_consent']['default'] && vance_hub_flare_plan_text_defaults()['vance_hub_flare_plan_note'] === $M->settings['vance_hub_flare_plan_note']['default'] );

section( '2. The panel and the eleven sections' );

check( 'panel: exactly one panel is registered', count( $M->panels ) === 1 );
check( 'panel: it is vance_hub_panel', isset( $M->panels['vance_hub_panel'] ) );
check( 'panel: its priority is 1', isset( $M->panels['vance_hub_panel']['priority'] ) && $M->panels['vance_hub_panel']['priority'] === 1 );
check( 'panel: its title is VanceHubClub', isset( $M->panels['vance_hub_panel']['title'] ) && $M->panels['vance_hub_panel']['title'] === 'VanceHubClub' );

check( 'sections: exactly eleven sections are registered', count( $M->sections ) === 11 );
check( 'sections: the sections are in the order of vance_hub_customizer_sections()', array_keys( $M->sections ) === array_keys( vance_hub_customizer_sections() ) );
check( 'sections: the order is the agreed eleven, from section 1 to section 11', array_keys( $M->sections ) === $SECTION_IDS );

$PANEL_OF = array_column( array_values( $M->sections ), 'panel' );
check( 'sections: every section sits in vance_hub_panel', $PANEL_OF === array_fill( 0, 11, 'vance_hub_panel' ) );

$PRIORITIES = array_column( array_values( $M->sections ), 'priority' );
$SORTED     = $PRIORITIES;
sort( $SORTED );
check( 'sections: priorities strictly rise in screen order', $PRIORITIES === $SORTED && count( array_unique( $PRIORITIES ) ) === 11 );

$TITLE_NUMBERS = array_map( function ( $t ) { return preg_match( '/^(\d+)\. /', $t, $m ) ? (int) $m[1] : 0; }, array_column( array_values( $M->sections ), 'title' ) );
check( 'sections: titles start 1. to 11. in order', $TITLE_NUMBERS === array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 ) );
check( 'sections: each title is the agreed title', array_column( array_values( $M->sections ), 'title' ) === array_values( $TITLES ) );
check( 'sections: the number comes from the position: vance_hub_customizer_section_number() agrees with each title', array_map( 'vance_hub_customizer_section_number', array_keys( $M->sections ) ) === $TITLE_NUMBERS );
check( 'sections: an unknown id has section number 0', vance_hub_customizer_section_number( 'vance_hub_sec_nothing' ) === 0 );
check( 'sections: every section has a one-sentence description', count( array_filter( array_column( array_values( $M->sections ), 'description' ), function ( $d ) { return is_string( $d ) && '' !== $d; } ) ) === 11 );

section( '3. Controls: sections, priorities and on-screen order' );

$CONTROL_SECTIONS = array_column( $M->controls, 'section' );
check( 'controls: 90 controls are registered (the original 40, minus the three retired switches, plus 28 new, plus 25 tool card and go-live)', count( $M->controls ) === 90 );
check( 'controls: every control sits in one of the eleven sections', array_diff( $CONTROL_SECTIONS, $SECTION_IDS ) === array() );
check( 'controls: no section is empty (all eleven sections hold controls)', count( array_count_values( $CONTROL_SECTIONS ) ) === 11 && array_diff( $SECTION_IDS, $CONTROL_SECTIONS ) === array() );
check( 'controls: every control has an integer priority', count( array_filter( array_column( $M->controls, 'priority' ), 'is_int' ) ) === 90 );

$DISTINCT_OK = true;
foreach ( $SECTION_IDS as $sid ) {
	// suite_section_order() keys by priority, so a shared priority would shrink the list.
	$in_section = count( array_filter( $CONTROL_SECTIONS, function ( $s ) use ( $sid ) { return $s === $sid; } ) );
	if ( count( suite_section_order( $M, $sid ) ) !== $in_section ) {
		$DISTINCT_OK = false;
	}
}
check( 'controls: no two controls in one section share a priority', $DISTINCT_OK );

foreach ( $SETTINGS as $id => $row ) {
	if ( null === $row[2] ) {
		check( "section: retired switch $id has no control any more", ! isset( $M->controls[ $id ] ) );
		continue;
	}
	check( "section: control $id is in {$row[2]}", isset( $M->controls[ $id ] ) && $M->controls[ $id ]['section'] === $row[2] );
}
foreach ( $NEW_SETTINGS as $id => $row ) {
	check( "section: new control $id is in {$row[2]}", isset( $M->controls[ $id ] ) && $M->controls[ $id ]['section'] === $row[2] );
}
foreach ( $TOOL_SETTINGS as $id => $row ) {
	check( "section: tool/go-live control $id is in {$row[2]}", isset( $M->controls[ $id ] ) && $M->controls[ $id ]['section'] === $row[2] );
}

check( 'order: section 1 holds the master switch, then the go-live date (priorities 10 and 20)', suite_section_order( $M, 'vance_home_layout' ) === $ORDER['vance_home_layout'] && $M->controls['vance_home_layout']['priority'] === 10 && $M->controls['vance_hub_golive_at']['priority'] === 20 );
check( 'order: section 2 runs the block order, then the five counts', suite_section_order( $M, 'vance_hub_sec_home' ) === $ORDER['vance_hub_sec_home'] );
check( 'order: section 3 runs hero, survey card, facts, visitor join, member join texts', suite_section_order( $M, 'vance_hub_sec_home_text' ) === $ORDER['vance_hub_sec_home_text'] );
check( 'order: section 4 (tool cards) runs cards 1 to 4 (tool, title, text, button, link, image)', suite_section_order( $M, 'vance_hub_sec_home_tools' ) === $ORDER['vance_hub_sec_home_tools'] );
$TOOL_PRIORITIES = array();
foreach ( $ORDER['vance_hub_sec_home_tools'] as $id ) { $TOOL_PRIORITIES[] = $M->controls[ $id ]['priority']; }
check( 'order: the card priorities are 10 to 15, 20 to 25, 30 to 35 and 40 to 45', $TOOL_PRIORITIES === array( 10, 11, 12, 13, 14, 15, 20, 21, 22, 23, 24, 25, 30, 31, 32, 33, 34, 35, 40, 41, 42, 43, 44, 45 ) );
check( 'order: section 5 runs tiles 1 to 4 (badge, title, text, link)', suite_section_order( $M, 'vance_hub_sec_home_tiles' ) === $ORDER['vance_hub_sec_home_tiles'] );
check( 'order: section 6 runs header, member total, founding deadline', suite_section_order( $M, 'vance_hub_sec_header' ) === $ORDER['vance_hub_sec_header'] );
check( 'order: section 7 runs articles, then the survey result page', suite_section_order( $M, 'vance_hub_sec_pages' ) === $ORDER['vance_hub_sec_pages'] );
check( 'order: section 8 runs dashboard home, tab look, member number, chart', suite_section_order( $M, 'vance_hub_sec_dashboard' ) === $ORDER['vance_hub_sec_dashboard'] );
check( 'order: section 9 is the community switch alone', suite_section_order( $M, 'vance_hub_sec_community' ) === $ORDER['vance_hub_sec_community'] );
check( 'order: section 10 runs the check-in switch, then its five texts', suite_section_order( $M, 'vance_hub_sec_checkin' ) === $ORDER['vance_hub_sec_checkin'] );
check( 'order: section 11 runs the flare switch, urgent text, consent, note', suite_section_order( $M, 'vance_hub_sec_flare' ) === $ORDER['vance_hub_sec_flare'] );

section( '4. Switch labels and the admin screen rows' );

$SWITCHES = vance_hub_customizer_switches();
check( 'switches: ten switches are listed (promo, sections and more are retired)', count( $SWITCHES ) === 10 );
check( 'switches: the three retired switches are not listed', ! isset( $SWITCHES['vance_hub_show_promo'] ) && ! isset( $SWITCHES['vance_hub_show_sections'] ) && ! isset( $SWITCHES['vance_hub_show_more'] ) );

foreach ( $SWITCHES as $mod => $switch ) {
	check( "switch $mod: control label is the one from vance_hub_customizer_switches()", isset( $M->controls[ $mod ] ) && $M->controls[ $mod ]['label'] === $switch['label'] );
	check( "switch $mod: control is in the section the switch names", isset( $M->controls[ $mod ] ) && $M->controls[ $mod ]['section'] === $switch['section'] );
	check( "switch $mod: control is a checkbox", isset( $M->controls[ $mod ] ) && $M->controls[ $mod ]['type'] === 'checkbox' );
}

$ADMIN_ROWS = vance_hub_admin_switches();
check( 'admin: the screen reads the same ten mods in the same order', array_column( $ADMIN_ROWS, 'mod' ) === array_keys( $SWITCHES ) );
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
check( 'groups: six groups, one per section that has a switch (the home sections have none)', array_keys( $GROUPS ) === array( 'vance_hub_sec_header', 'vance_hub_sec_pages', 'vance_hub_sec_dashboard', 'vance_hub_sec_community', 'vance_hub_sec_checkin', 'vance_hub_sec_flare' ) );
check( 'groups: section 1 is not a group', ! isset( $GROUPS['vance_home_layout'] ) );
check( 'groups: no home section is a group', ! isset( $GROUPS['vance_hub_sec_home'] ) && ! isset( $GROUPS['vance_hub_sec_home_text'] ) && ! isset( $GROUPS['vance_hub_sec_home_tools'] ) && ! isset( $GROUPS['vance_hub_sec_home_tiles'] ) );
check( 'groups: header holds the header switch', array_column( $GROUPS['vance_hub_sec_header']['rows'], 'label' ) === array( vance_hub_customizer_switch( 'vance_hub_header' )['label'] ) );
check( 'groups: pages hold articles and the survey result page', array_column( $GROUPS['vance_hub_sec_pages']['rows'], 'label' ) === array( vance_hub_customizer_switch( 'vance_hub_articles' )['label'], vance_hub_customizer_switch( 'vance_hub_tool_result' )['label'] ) );
check( 'groups: dashboard holds the four dashboard switches', array_column( $GROUPS['vance_hub_sec_dashboard']['rows'], 'label' ) === array( vance_hub_customizer_switch( 'vance_hub_show_dashboard' )['label'], vance_hub_customizer_switch( 'vance_hub_dashboard_tabs' )['label'], vance_hub_customizer_switch( 'vance_hub_dash_member_no' )['label'], vance_hub_customizer_switch( 'vance_hub_dash_chart' )['label'] ) );
check( 'groups: community holds the community switch', array_column( $GROUPS['vance_hub_sec_community']['rows'], 'label' ) === array( vance_hub_customizer_switch( 'vance_hub_community' )['label'] ) );
check( 'groups: check-in holds the check-in switch', array_column( $GROUPS['vance_hub_sec_checkin']['rows'], 'label' ) === array( vance_hub_customizer_switch( 'vance_hub_dash_checkin' )['label'] ) );
check( 'groups: flare plan holds the flare switch', array_column( $GROUPS['vance_hub_sec_flare']['rows'], 'label' ) === array( vance_hub_customizer_switch( 'vance_hub_dash_flare_plan' )['label'] ) );

check( 'groups: each group title is the section title', $GROUPS['vance_hub_sec_header']['title'] === '6. Header and member bar' && $GROUPS['vance_hub_sec_pages']['title'] === '7. Articles and Self-Assessment' && $GROUPS['vance_hub_sec_dashboard']['title'] === '8. Member dashboard' && $GROUPS['vance_hub_sec_community']['title'] === '9. Community' && $GROUPS['vance_hub_sec_checkin']['title'] === '10. Daily check-in (health data)' && $GROUPS['vance_hub_sec_flare']['title'] === '11. Flare plan (health data)' );

section( '8. Section numbers in descriptions, and the home page settings' );

// A description that names another section must use the section's real number.
check( 'numbers: the panel description names section 1 and sections 2 to 11', strpos( $M->panels['vance_hub_panel']['description'], 'Do section 1 first. Then set each part in sections 2 to 11.' ) !== false );
check( 'numbers: the master switch description names sections 2 to 11 and sections 10 and 11', strpos( $M->controls['vance_home_layout']['description'], 'in sections 2 to 11.' ) !== false && strpos( $M->controls['vance_home_layout']['description'], 'in sections 10 and 11.' ) !== false );
check( 'numbers: the check-in description names the dashboard section by its number (8)', strpos( $M->controls['vance_hub_dash_checkin']['description'], '(section 8)' ) !== false );
check( 'numbers: the flare plan description names the dashboard section by its number (8)', strpos( $M->controls['vance_hub_dash_flare_plan']['description'], '(section 8)' ) !== false );

$HOME_SRC = file_get_contents( $THEME . '/inc/hub-layout.php' ) . file_get_contents( $THEME . '/inc/hub-dashboard-features.php' ) . file_get_contents( $THEME . '/inc/hub-flare-plan.php' ) . file_get_contents( $THEME . '/inc/hub-golive.php' ) . file_get_contents( $THEME . '/inc/hub-home-settings.php' );
check( 'numbers: no hub description holds a literal "section N" (all use %d / %1$d)', ! preg_match( '/__\(\s*\'[^\']*\b[Ss]ections? \d/', $HOME_SRC ) );

// Block registry.
check( 'blocks: nine blocks in the default order', array_keys( vance_hub_home_blocks() ) === array( 'tools', 'inside', 'conditions', 'community', 'promo', 'library', 'sections', 'more', 'join' ) );
check( 'blocks: the labels are the agreed ones', array_values( vance_hub_home_blocks() ) === array( 'Tools', 'Inside the Hub', 'Conditions', 'Community and recipes', 'Promo block', 'From the library', 'Latest by section', 'More to read', 'Join block' ) );
check( 'blocks: without the sortable control class the block order falls back to a plain text control', $M->controls['vance_hub_block_order']['type'] === 'text' && ! class_exists( 'Vance_Customize_Sortable_Sections_Control' ) );
check( 'blocks: the block order control is first in its section (priority 10)', $M->controls['vance_hub_block_order']['priority'] === 10 );

// Sanitiser.
check( 'sanitise order: known ids stay, in the given order', vance_hub_sanitize_block_order( 'join,tools,library' ) === 'join,tools,library' );
check( 'sanitise order: unknown ids and duplicates go', vance_hub_sanitize_block_order( 'tools,nope,tools,more,,join' ) === 'tools,more,join' );
check( 'sanitise order: spaces around an id are trimmed', vance_hub_sanitize_block_order( ' tools , more ' ) === 'tools,more' );
check( 'sanitise order: the empty string stays the empty string', vance_hub_sanitize_block_order( '' ) === '' );
check( 'sanitise order: a non-string becomes the empty string', vance_hub_sanitize_block_order( array( 'tools' ) ) === '' && vance_hub_sanitize_block_order( null ) === '' );

// Reader: never saved, saved empty, saved list, legacy switches.
$GLOBALS['MODS'] = array();
check( 'order: never saved gives the default order', vance_hub_block_order() === array( 'tools', 'inside', 'conditions', 'community', 'promo', 'library', 'sections', 'more', 'join' ) );
$GLOBALS['MODS'] = array( 'vance_hub_show_promo' => false, 'vance_hub_show_more' => '0' );
check( 'order: never saved, two legacy switches off, removes just those blocks', vance_hub_block_order() === array( 'tools', 'inside', 'conditions', 'community', 'library', 'sections', 'join' ) );
$GLOBALS['MODS'] = array( 'vance_hub_show_sections' => 'off' );
check( 'order: a legacy "off" string is off through the checkbox sanitiser', ! in_array( 'sections', vance_hub_block_order(), true ) );
$GLOBALS['MODS'] = array( 'vance_hub_block_order' => '', 'vance_hub_show_promo' => true );
check( 'order: saved empty means every block is hidden (not "never saved")', vance_hub_block_order() === array() );
$GLOBALS['MODS'] = array( 'vance_hub_block_order' => 'join,tools', 'vance_hub_show_promo' => false );
check( 'order: a saved list wins, in its own order', vance_hub_block_order() === array( 'join', 'tools' ) );
$GLOBALS['MODS'] = array( 'vance_hub_block_order' => 'promo,library', 'vance_hub_show_promo' => false, 'vance_hub_show_sections' => false );
check( 'order: once saved, the legacy switches are ignored (promo prints although its switch is off)', vance_hub_block_order() === array( 'promo', 'library' ) );
$GLOBALS['MODS'] = array( 'vance_hub_block_order' => 'tools,bogus,tools' );
check( 'order: a saved list is cleaned on read', vance_hub_block_order() === array( 'tools' ) );
check( 'order: vance_hub_block_on() follows the list', vance_hub_block_on( 'tools' ) === true && vance_hub_block_on( 'join' ) === false );
$GLOBALS['MODS'] = array( 'vance_hub_block_order' => 'tools,more' );
check( 'join link: with the Join block off it is the sign-up page', vance_hub_join_href() === 'https://example.test/login/?tab=signup' );
$GLOBALS['MODS'] = array();
check( 'join link: with the Join block on it is the anchor for a visitor', vance_hub_join_href() === '#vhub-join' );
$GLOBALS['LOGGED_IN'] = true;
check( 'join link: with the Join block on, a signed-in member gets the sign-up page (the member variant has no id)', vance_hub_join_href() === 'https://example.test/login/?tab=signup' );
$GLOBALS['LOGGED_IN'] = false;

// The registered default of the block order is the "never saved" order, so the
// control and the Customizer preview agree with the live page.
function suite_registered_order_default( $mods ) {
	$GLOBALS['MODS'] = $mods;
	$fake            = new SuiteCustomizeFake();
	vance_hub_home_customize_register( $fake );
	return $fake->settings['vance_hub_block_order']['default'];
}
$FULL_ORDER = 'tools,inside,conditions,community,promo,library,sections,more,join';
check( 'default: with nothing saved the registered default is the full order string', suite_registered_order_default( array() ) === $FULL_ORDER );
$D = suite_registered_order_default( array( 'vance_hub_show_sections' => false ) );
check( 'default: with "sections" saved off and no order saved, the registered default omits it', $D === 'tools,inside,conditions,community,promo,library,more,join' );
$GLOBALS['MODS'] = array( 'vance_hub_show_sections' => false );
check( 'default: that registered default equals what the reader returns', $D === implode( ',', vance_hub_block_order() ) && $D === implode( ',', vance_hub_block_order_default() ) );
// With the order saved, a saved value always wins: the reader ignores the retired
// switches. The registered default may still omit a block then. That is harmless,
// because the control and the page read the saved value, not the default.
$GLOBALS['MODS'] = array( 'vance_hub_block_order' => 'sections,tools', 'vance_hub_show_sections' => false );
check( 'default: with the order saved, the reader ignores the retired switch (saved value wins)', vance_hub_block_order() === array( 'sections', 'tools' ) );

// Counts.
check( 'counts: five counts with the agreed defaults and ranges', vance_hub_home_counts() === array(
	'vance_hub_count_sections' => array( 3, 1, 6, 'Articles in each "Latest by section" column' ),
	'vance_hub_count_more'     => array( 6, 3, 12, '"More to read" articles' ),
	'vance_hub_count_library'  => array( 4, 2, 7, 'Library articles (one large and a list)' ),
	'vance_hub_count_recipes'  => array( 2, 1, 4, 'Recipes in each recipe list' ),
	'vance_hub_count_quotes'   => array( 4, 1, 4, 'Community quotes' ),
) );
$GLOBALS['MODS'] = array();
check( 'count: nothing saved gives the default', vance_hub_count( 'vance_hub_count_more' ) === 6 );
$GLOBALS['MODS'] = array( 'vance_hub_count_more' => 99, 'vance_hub_count_sections' => 0, 'vance_hub_count_library' => '5', 'vance_hub_count_recipes' => 'abc', 'vance_hub_count_quotes' => '' );
check( 'count: above the maximum is clamped down', vance_hub_count( 'vance_hub_count_more' ) === 12 );
check( 'count: below the minimum is clamped up', vance_hub_count( 'vance_hub_count_sections' ) === 1 );
check( 'count: a numeric string is read as a number', vance_hub_count( 'vance_hub_count_library' ) === 5 );
check( 'count: a non-numeric value gives the default', vance_hub_count( 'vance_hub_count_recipes' ) === 2 );
check( 'count: an empty string gives the default', vance_hub_count( 'vance_hub_count_quotes' ) === 4 );
check( 'count: an unknown name gives 0', vance_hub_count( 'vance_hub_count_nothing' ) === 0 );
$GLOBALS['MODS'] = array();
check( 'sanitise count: reads the range from a setting object', vance_hub_sanitize_count( '50', (object) array( 'id' => 'vance_hub_count_sections' ) ) === 6 && vance_hub_sanitize_count( -3, (object) array( 'id' => 'vance_hub_count_library' ) ) === 2 );
check( 'sanitise count: reads the range from a plain id string', vance_hub_sanitize_count( 9, 'vance_hub_count_quotes' ) === 4 && vance_hub_sanitize_count( 1, 'vance_hub_count_more' ) === 3 );
check( 'sanitise count: a non-number gives the default of that setting', vance_hub_sanitize_count( 'x', 'vance_hub_count_more' ) === 6 );
check( 'sanitise count: every registered count control is a number input with min, max and step 1', count( array_filter( array_keys( vance_hub_home_counts() ), function ( $mod ) use ( $M ) {
	$c = $M->controls[ $mod ];
	$r = vance_hub_home_counts()[ $mod ];
	return 'number' === $c['type'] && $c['input_attrs'] === array( 'min' => $r[1], 'max' => $r[2], 'step' => 1 );
} ) ) === 5 );
check( 'sanitise count: the count controls use priorities 20 to 60', array_map( function ( $mod ) use ( $M ) { return $M->controls[ $mod ]['priority']; }, array_keys( vance_hub_home_counts() ) ) === array( 20, 30, 40, 50, 60 ) );

// Texts.
$TEXTS = vance_hub_home_text_defaults();
check( 'texts: exactly 22 texts', count( $TEXTS ) === 22 );
check( 'texts: the groups run hero 4, survey 4, facts 6, visitor join 5, member join 3', array_count_values( array_column( $TEXTS, 'group' ) ) === array( 'hero' => 4, 'survey' => 4, 'facts' => 6, 'join_visitor' => 5, 'join_member' => 3 ) );
check( 'texts: the survey note is not editable (it is a safety statement)', ! isset( $TEXTS['vance_hub_text_survey_note'] ) );
$GLOBALS['MODS'] = array();
check( 'text: nothing saved gives the default', vance_hub_home_text( 'vance_hub_text_hero_title' ) === 'What do you need help with' );
$GLOBALS['MODS'] = array( 'vance_hub_text_hero_title' => '', 'vance_hub_text_hero_accent' => "  \t ", 'vance_hub_text_hero_pill' => '  Welcome  ' );
check( 'text: a saved empty string gives the default', vance_hub_home_text( 'vance_hub_text_hero_title' ) === 'What do you need help with' );
check( 'text: a saved whitespace-only string gives the default', vance_hub_home_text( 'vance_hub_text_hero_accent' ) === 'today?' );
check( 'text: a saved text is trimmed', vance_hub_home_text( 'vance_hub_text_hero_pill' ) === 'Welcome' );
check( 'text: an unknown name gives the empty string', vance_hub_home_text( 'vance_hub_text_nothing' ) === '' );
$GLOBALS['MODS'] = array();
foreach ( $TEXTS as $mod => $row ) {
	check( "text control $mod: type, label and default match the registry", $M->controls[ $mod ]['type'] === $row['type'] && $M->controls[ $mod ]['label'] === $row['label'] && $M->settings[ $mod ]['default'] === $row['default'] && ( 'textarea' === $row['type'] ? 'sanitize_textarea_field' : 'sanitize_text_field' ) === $M->settings[ $mod ]['sanitize_callback'] );
	check( "text control $mod: the description starts with the empty-means-default sentence", 0 === strpos( $M->controls[ $mod ]['description'], 'Leave empty to use the default.' ) );
}
check( 'texts: the first control of each of the five groups says where the text shows (a second sentence)', count( array_filter( array_keys( $TEXTS ), function ( $mod ) use ( $M ) { return $M->controls[ $mod ]['description'] !== 'Leave empty to use the default.'; } ) ) === 5 );
check( 'texts: text control priorities are unique and rise with the registry', array_map( function ( $mod ) use ( $M ) { return $M->controls[ $mod ]['priority']; }, array_keys( $TEXTS ) ) === range( 10, 220, 10 ) );

// Added checks: the edges of the block-order and count sanitisers.
check( 'sanitise order: a capital letter is not a block id, so that id is dropped', vance_hub_sanitize_block_order( 'Tools,join' ) === 'join' );
check( 'sanitise order: empty entries between commas are dropped', vance_hub_sanitize_block_order( 'tools, ,join,' ) === 'tools,join' );
check( 'sanitise order: only unknown ids gives the empty string (every block hidden)', vance_hub_sanitize_block_order( 'nope,promo2' ) === '' );
check( 'count: a decimal number is read as its whole part (5.9 is 5)', vance_hub_sanitize_count( '5.9', 'vance_hub_count_library' ) === 5 );
check( 'count: a number in exponent form is clamped to the maximum (1e3 is 6 for sections)', vance_hub_sanitize_count( '1e3', 'vance_hub_count_sections' ) === 6 );
check( 'count: text that starts with a number is not a number, so the default (5abc is 6 for more)', vance_hub_sanitize_count( '5abc', 'vance_hub_count_more' ) === 6 );
check( 'count: the boolean true is not a number, so the default (2 for recipes)', vance_hub_sanitize_count( true, 'vance_hub_count_recipes' ) === 2 );

section( '9. Tool cards and the go-live date: controls and sanitisers' );

check( 'tools: the pick choices are the four tools and "Hide this card"', vance_hub_tool_picks() === array( 'survey' => 'Self-Assessment', 'nutrition' => 'Nutrition check', 'recipes' => 'Meal planner', 'discounts' => 'Discounts and benefits', 'none' => 'Hide this card' ) );
foreach ( array( 1, 2, 3, 4 ) as $slot ) {
	$pick = $M->controls[ "vance_hub_tool_{$slot}_pick" ];
	check( "tools: card $slot pick is a select with the five choices", 'select' === $pick['type'] && $pick['choices'] === vance_hub_tool_picks() );
	check( "tools: card $slot pick is labelled \"Card $slot: tool\"", $pick['label'] === "Card $slot: tool" );
	check( "tools: card $slot pick has the standard-wording description with the live-numbers sentence", false !== strpos( $pick['description'], 'An empty field below uses the standard wording of the selected tool.' ) && false !== strpos( $pick['description'], 'live numbers' ) );
	foreach ( array( 'title' => 'text', 'text' => 'textarea', 'button' => 'text', 'link' => 'url' ) as $key => $type ) {
		$c = $M->controls[ "vance_hub_tool_{$slot}_{$key}" ];
		check( "tools: card $slot $key is a $type control labelled \"Card $slot: $key\" with no description", $type === $c['type'] && "Card $slot: $key" === $c['label'] && ! isset( $c['description'] ) );
	}
	$image = $M->controls[ "vance_hub_tool_{$slot}_image" ];
	check( "tools: card $slot image is labelled \"Card $slot: image\" and, with no core media control, a number control", "Card $slot: image" === $image['label'] && 'number' === $image['type'] && ! class_exists( 'WP_Customize_Media_Control' ) );
}

check( 'pick sanitiser: a known pick stays', vance_hub_sanitize_tool_pick( 'recipes', 'vance_hub_tool_1_pick' ) === 'recipes' && vance_hub_sanitize_tool_pick( 'none', 'vance_hub_tool_2_pick' ) === 'none' );
check( 'pick sanitiser: an unknown value gives the default of the slot, read from a setting object', vance_hub_sanitize_tool_pick( 'bogus', (object) array( 'id' => 'vance_hub_tool_3_pick' ) ) === 'recipes' && vance_hub_sanitize_tool_pick( '', (object) array( 'id' => 'vance_hub_tool_4_pick' ) ) === 'discounts' );
check( 'pick sanitiser: an unknown value gives the default of the slot, read from a plain id', vance_hub_sanitize_tool_pick( 'bogus', 'vance_hub_tool_1_pick' ) === 'survey' && vance_hub_sanitize_tool_pick( array( 'survey' ), 'vance_hub_tool_2_pick' ) === 'nutrition' );
check( 'pick sanitiser: an id that is not a card slot gives "none" for an unknown value', vance_hub_sanitize_tool_pick( 'bogus', 'vance_hub_tool_5_pick' ) === 'none' && vance_hub_sanitize_tool_pick( 'bogus' ) === 'none' );
check( 'pick sanitiser: surrounding spaces are trimmed, but the case is not changed', vance_hub_sanitize_tool_pick( ' survey ', 'vance_hub_tool_4_pick' ) === 'survey' && vance_hub_sanitize_tool_pick( 'Survey', 'vance_hub_tool_4_pick' ) === 'discounts' );

$GO = $M->controls['vance_hub_golive_at'];
check( 'go-live: the control is a datetime-local input labelled "Go-live date and time" in section vance_home_layout', 'datetime-local' === $GO['type'] && 'Go-live date and time' === $GO['label'] && 'vance_home_layout' === $GO['section'] );
check( 'go-live: the description names the site time zone', false !== strpos( $GO['description'], '(Europe/London)' ) );
check( 'go-live: the description names the check-in and flare plan sections by number (10 and 11)', false !== strpos( $GO['description'], 'sections 10 and 11' ) );
check( 'go-live: the description says the field becomes empty, and that empty means no change', false !== strpos( $GO['description'], 'this field then becomes empty' ) && false !== strpos( $GO['description'], 'Leave this field empty for no planned change.' ) );
check( 'go-live: the description names the LiteSpeed purge and the separate hosting cache, purged by hand', false !== strpos( $GO['description'], 'purges the LiteSpeed cache' ) && false !== strpos( $GO['description'], 'The hosting cache is separate.' ) && false !== strpos( $GO['description'], 'hosting panel' ) );
check( 'go-live: the description no longer promises "a few more minutes"', false === strpos( $GO['description'], 'few more minutes' ) );
check( 'go-live: the description says a past time changes the site on the next request', false !== strpos( $GO['description'], 'A time that is already past changes the site on the next request, so check the year and the time before you publish.' ) );
check( 'go-live: the layout control points to the go-live field', false !== strpos( $M->controls['vance_home_layout']['description'], 'use the go-live field below' ) );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
