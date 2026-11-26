<?php
/**
 * Tests for the VanceHubClub member dashboard home: inc/hub-dashboard.php, and
 * the three edits it needs in page-dashboard.php.
 *
 * Same shape as hub-article.test.php, and on purpose the SAME stubs: the stub
 * block of hub-layout.test.php (everything before its first $FRONT_PAGE line)
 * is lifted and evaluated, so both suites run against one set of WordPress
 * fakes. If that block cannot be found, this suite stops with exit 2 rather
 * than passing on nothing.
 *
 * WHAT IS COVERED
 *   1  the switch: the saved checkbox, the ?vhh_layout= preview, the
 *      capability gate, odd query values, the resolve() table
 *   2  the pure helpers: list, latest_ts, next_step, tool_status,
 *      unread_count, tab_url
 *   3  the data collector: reads only, the next step and its skipped steps,
 *      the tools and the "See my answers" link, the counts, saved articles,
 *      messages, new in the library, malformed meta
 *   4  the HTML: escaping, absent blocks, the unread badge, the one-column
 *      layout, the empty messages line, the check mark, the render entry point
 *   5  the assets: enqueue conditions and the hook
 *   6  page-dashboard.php by static inspection
 *   7  the greeting: side-menu heading or classic greeting, top-row order, member card first name and label
 *
 * The promo block and the Customizer switches are checked in
 * hub-layout.test.php (section 15), because front-page-hub.php is rendered there.
 */

/* ---- load the hub-layout harness's stubs and the code under test ---------- */

$ROOT = dirname( __DIR__ );
$SRC  = file_get_contents( __DIR__ . '/hub-layout.test.php' );
$CUT  = strpos( $SRC, '$FRONT_PAGE = ' );
if ( false === $CUT ) {
	fwrite( STDERR, "could not find the stub block in hub-layout.test.php\n" );
	exit( 2 );
}
$DEFS = substr( $SRC, 0, $CUT );
$DEFS = str_replace( 'dirname( __DIR__ )', var_export( $ROOT, true ), $DEFS );
$DEFS = str_replace( '__DIR__', var_export( __DIR__, true ), $DEFS );
$DEFS = preg_replace( '/^<\?php/', '', $DEFS );
eval( $DEFS );

/* ---- stubs this suite adds ------------------------------------------------- */

class WP_User {
	public $ID              = 0;
	public $display_name    = '';
	public $user_registered = '';
	public function exists() {
		return $this->ID > 0;
	}
}

function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
function esc_attr__( $text, $domain = '' ) { return esc_attr( $text ); }

function is_page_template( $template ) {
	return $GLOBALS['TEMPLATE'] === $template;
}

// is_page( $slug ): true when the page being viewed has that slug, whatever
// template it uses. A page resolved to page-dashboard.php by slug has no
// assigned template, so is_page_template() is false for it and is_page() is true.
function is_page( $page = '' ) {
	return $GLOBALS['SLUG'] === $page;
}

// WordPress returns '' for a meta key that was never written.
function get_user_meta( $user_id, $key = '', $single = false ) {
	return isset( $GLOBALS['USER_META'][ $user_id ][ $key ] ) ? $GLOBALS['USER_META'][ $user_id ][ $key ] : '';
}

// Every write records its name. The collector must leave WRITES empty.
function hd_write( $name ) {
	$GLOBALS['WRITES'][] = $name;
	return true;
}
function update_user_meta( $user_id, $key, $value, $prev = '' ) { return hd_write( 'update_user_meta:' . $key ); }
function add_user_meta( $user_id, $key, $value, $unique = false ) { return hd_write( 'add_user_meta:' . $key ); }
function delete_user_meta( $user_id, $key, $value = '' ) { return hd_write( 'delete_user_meta:' . $key ); }
function update_post_meta( $post_id, $key, $value, $prev = '' ) { return hd_write( 'update_post_meta:' . $key ); }
function add_post_meta( $post_id, $key, $value, $unique = false ) { return hd_write( 'add_post_meta:' . $key ); }
function delete_post_meta( $post_id, $key, $value = '' ) { return hd_write( 'delete_post_meta:' . $key ); }
function update_option( $name, $value, $autoload = null ) { return hd_write( 'update_option:' . $name ); }

// Feature registry. An unknown or unset slug is on, as in the real function.
function vance_dashboard_feature_enabled( $slug ) {
	return array_key_exists( $slug, $GLOBALS['FEATURE_ON'] ) ? (bool) $GLOBALS['FEATURE_ON'][ $slug ] : true;
}
function vance_dashboard_feature_label( $slug ) {
	return isset( $GLOBALS['FEATURE_LABEL'][ $slug ] ) ? $GLOBALS['FEATURE_LABEL'][ $slug ] : '';
}

// Tool history, newest first as the real function returns it.
function vance_get_tool_history( $user_id, $tool_slug, $limit = 0 ) {
	$GLOBALS['HIST_CALLS'][] = array( (int) $user_id, $tool_slug, $limit );
	return isset( $GLOBALS['HISTORY'][ $tool_slug ] ) ? $GLOBALS['HISTORY'][ $tool_slug ] : array();
}

// Team messages. Records the arguments so the include_read flag can be checked.
function vance_admin_messages_for_user( $user_id, $include_read = false ) {
	$GLOBALS['MSG_CALLS'][] = array( (int) $user_id, $include_read );
	return $GLOBALS['MESSAGES'];
}

require_once $THEME . '/inc/hub-dashboard.php';

/* ---- helpers ------------------------------------------------------------------ */

/** Reset every global this suite reads, on top of hub-layout's reset. */
function hd_arrange( array $mods = array(), array $caps = array(), array $get = array(), $logged_in = true ) {
	arrange( $mods, false, $caps, $get );
	$GLOBALS['LOGGED_IN']     = $logged_in;
	$GLOBALS['TEMPLATE']      = '';
	$GLOBALS['SLUG']          = '';
	$GLOBALS['USER_META']     = array();
	$GLOBALS['HISTORY']       = array();
	$GLOBALS['HIST_CALLS']    = array();
	$GLOBALS['FEATURE_ON']    = array();
	$GLOBALS['FEATURE_LABEL'] = array();
	$GLOBALS['MESSAGES']      = array();
	$GLOBALS['MSG_CALLS']     = array();
	$GLOBALS['WRITES']        = array();
	$GLOBALS['ENQUEUED']      = array();
}

function hd_user( $id, $display_name, $registered ) {
	$user                  = new WP_User();
	$user->ID              = $id;
	$user->display_name    = $display_name;
	$user->user_registered = $registered;
	return $user;
}

/** The render entry point, captured. */
function hd_render( $user ) {
	ob_start();
	vance_hub_dashboard_render_home( $user );
	return ob_get_clean();
}

/** A view with every block empty. The shape vance_hub_dashboard_data() returns. */
function hd_view_empty() {
	return array(
		'name'           => 'Ruth',
		'since'          => '',
		'ask'            => false,
		'step'           => null,
		'tools'          => array(),
		'stats'          => array(),
		'saved'          => array(),
		'saved_label'    => 'Saved articles',
		'saved_href'     => '?tab=reading-list',
		'show_messages'  => false,
		'messages'       => array(),
		'unread'         => 0,
		'messages_label' => 'Messages',
		'messages_href'  => '?tab=messages',
		'fresh'          => array(),
	);
}

/** One article row, as vance_hub_post_card() returns it. */
function hd_card( $id, $title, $cat = '', $img = '', $date = '', $iso = '' ) {
	return array(
		'id'    => $id,
		'title' => $title,
		'url'   => 'https://example.test/p/' . $id . '/',
		'cat'   => $cat,
		'img'   => $img,
		'alt'   => '',
		'date'  => $date,
		'iso'   => $iso,
		'read'  => 3,
	);
}

/** One tool tile, as the data collector returns it. */
function hd_tool( $mod, $title, $done, $status ) {
	return array(
		'mod'    => $mod,
		'icon'   => 'clipboard',
		'title'  => $title,
		'cta'    => 'Start',
		'href'   => 'https://example.test/t/',
		'img'    => '',
		'done'   => $done,
		'status' => $status,
	);
}

/** A next-step card. */
function hd_step( $title, $href ) {
	return array( 'title' => $title, 'text' => 'Some text.', 'cta' => 'Go', 'href' => $href );
}

/** A saved-item count tile. */
function hd_stat( $slug, $count, $label ) {
	return array( 'slug' => $slug, 'count' => $count, 'label' => $label, 'href' => '?tab=' . $slug );
}

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/* ===================================================================== */
section( '1. The switch' );

// resolve(): the saved value and the preview query, pure.
check( 'resolve: saved true, no query, is on', vance_hub_dashboard_resolve( true, '', false ) === true );
check( 'resolve: saved false, no query, is off', vance_hub_dashboard_resolve( false, '', false ) === false );
check( 'resolve: saved 1 (int) is on', vance_hub_dashboard_resolve( 1, '', false ) === true );
check( 'resolve: saved "1" (string) is on', vance_hub_dashboard_resolve( '1', '', false ) === true );
check( 'resolve: saved 0 is off', vance_hub_dashboard_resolve( 0, '', false ) === false );
check( 'resolve: saved "0" is off', vance_hub_dashboard_resolve( '0', '', false ) === false );
check( 'resolve: saved 2 is off (only 1 means on)', vance_hub_dashboard_resolve( 2, '', false ) === false );
check( 'resolve: saved empty string is off', vance_hub_dashboard_resolve( '', '', false ) === false );
check( 'resolve: saved null is off', vance_hub_dashboard_resolve( null, '', false ) === false );
check( 'resolve: saved array is off', vance_hub_dashboard_resolve( array(), '', false ) === false );
check( 'resolve: saved "on" raw is off (the caller sanitises first)', vance_hub_dashboard_resolve( 'on', '', false ) === false );
check( 'resolve: preview hub wins over a saved off', vance_hub_dashboard_resolve( false, 'hub', true ) === true );
check( 'resolve: preview classic wins over a saved on', vance_hub_dashboard_resolve( true, 'classic', true ) === false );
check( 'resolve: unknown preview value falls back to the saved on', vance_hub_dashboard_resolve( true, 'other', true ) === true );
check( 'resolve: unknown preview value falls back to the saved off', vance_hub_dashboard_resolve( false, 'other', true ) === false );
check( 'resolve: visitor query "hub" is ignored (no capability)', vance_hub_dashboard_resolve( false, 'hub', false ) === false );
check( 'resolve: visitor query "classic" is ignored (no capability)', vance_hub_dashboard_resolve( true, 'classic', false ) === true );

// vance_hub_dashboard_active(): the request, read through the capability gate.
hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true ) );
check( 'active: saved on, nothing in the request, is on', vance_hub_dashboard_active() === true );

hd_arrange( array( 'vance_hub_show_dashboard' => false ) );
check( 'active: default (saved off), nothing in the request, is off', vance_hub_dashboard_active() === false );

hd_arrange( array(), array(), array(), true );
check( 'active: nothing saved at all (mod unset) is off', vance_hub_dashboard_active() === false );

hd_arrange( array( 'vance_hub_show_dashboard' => false ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'active: administrator preview ?vhh_layout=hub switches it on', vance_hub_dashboard_active() === true );

hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'classic' ) );
check( 'active: administrator preview ?vhh_layout=classic switches it off', vance_hub_dashboard_active() === false );

hd_arrange( array( 'vance_hub_show_dashboard' => false ), array(), array( 'vhh_layout' => 'hub' ) );
check( 'active: visitor cannot preview with ?vhh_layout=hub', vance_hub_dashboard_active() === false );

hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true ), array(), array( 'vhh_layout' => 'classic' ) );
check( 'active: visitor cannot preview with ?vhh_layout=classic', vance_hub_dashboard_active() === true );

hd_arrange( array( 'vance_hub_show_dashboard' => false ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'HUB' ) );
check( 'active: preview value is sanitised, HUB is hub', vance_hub_dashboard_active() === true );

hd_arrange( array( 'vance_hub_show_dashboard' => false ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub<script>' ) );
check( 'active: preview value with markup is sanitised to an unknown key, so the saved mod decides', vance_hub_dashboard_active() === false );

hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => array( 'hub' ) ) );
check( 'active: an array preview value is ignored, not a fatal, and the saved mod decides', vance_hub_dashboard_active() === true );

hd_arrange( array( 'vance_hub_show_dashboard' => false ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => '' ) );
check( 'active: an empty preview value is ignored, the saved mod decides', vance_hub_dashboard_active() === false );

hd_arrange( array( 'vance_hub_show_dashboard' => '0' ), array(), array() );
check( 'active: a saved "0" string is off after the checkbox sanitiser', vance_hub_dashboard_active() === false );

hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => '1' ), array(), array() );
check( 'active: a saved "1" string is on after the checkbox sanitiser', vance_hub_dashboard_active() === true );

// The master switch (Home layout) over the dashboard part.
hd_arrange( array( 'vance_hub_show_dashboard' => true ) );
check( 'master: classic with the dashboard box ticked is off', vance_hub_dashboard_active() === false );

hd_arrange( array( 'vance_hub_show_dashboard' => true ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'master: classic with an admin ?vhh_layout=hub preview is on', vance_hub_dashboard_active() === true );

hd_arrange( array( 'vance_home_layout' => 'hub' ) );
check( 'master: hub with the dashboard box never saved is on (default on)', vance_hub_dashboard_active() === true );

hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => false ) );
check( 'master: hub with the dashboard box saved off is off', vance_hub_dashboard_active() === false );

/* ===================================================================== */
section( '2. Pure helpers' );

// list()
check( 'list: empty string is an empty list', vance_hub_dashboard_list( '' ) === array() );
check( 'list: a string is an empty list (damaged meta)', vance_hub_dashboard_list( 'oops' ) === array() );
check( 'list: zero is an empty list', vance_hub_dashboard_list( 0 ) === array() );
check( 'list: null is an empty list', vance_hub_dashboard_list( null ) === array() );
check( 'list: an array comes back unchanged', vance_hub_dashboard_list( array( 'a' => 1 ) ) === array( 'a' => 1 ) );

// latest_ts()
check( 'latest_ts: no rows gives 0', vance_hub_dashboard_latest_ts( array() ) === 0 );
check( 'latest_ts: a string instead of rows gives 0', vance_hub_dashboard_latest_ts( 'oops' ) === 0 );
check( 'latest_ts: one row gives its ts', vance_hub_dashboard_latest_ts( array( array( 'ts' => 500 ) ) ) === 500 );
check( 'latest_ts: out of order, the maximum wins, not row 0', vance_hub_dashboard_latest_ts( array( array( 'ts' => 100 ), array( 'ts' => 300 ), array( 'ts' => 200 ) ) ) === 300 );
check( 'latest_ts: newest first (the real order) gives the first row', vance_hub_dashboard_latest_ts( array( array( 'ts' => 900 ), array( 'ts' => 100 ) ) ) === 900 );
check( 'latest_ts: a row with no ts is skipped', vance_hub_dashboard_latest_ts( array( array( 'payload' => 1 ), array( 'ts' => 40 ) ) ) === 40 );
check( 'latest_ts: a non-numeric ts is skipped', vance_hub_dashboard_latest_ts( array( array( 'ts' => 'abc' ), array( 'ts' => 40 ) ) ) === 40 );
check( 'latest_ts: a numeric string ts is read', vance_hub_dashboard_latest_ts( array( array( 'ts' => '900' ) ) ) === 900 );
check( 'latest_ts: a boolean ts is skipped (is_numeric is false)', vance_hub_dashboard_latest_ts( array( array( 'ts' => true ) ) ) === 0 );
check( 'latest_ts: a negative ts never beats 0', vance_hub_dashboard_latest_ts( array( array( 'ts' => -5 ) ) ) === 0 );
check( 'latest_ts: non-array rows are skipped', vance_hub_dashboard_latest_ts( array( 'x', null, 7, array( 'ts' => 12 ) ) ) === 12 );
check( 'latest_ts: a row whose ts is an array is skipped', vance_hub_dashboard_latest_ts( array( array( 'ts' => array( 9 ) ) ) ) === 0 );

// next_step(): survey, profile, plan, check, in that order.
$BOTH_ON = array( 'health-profile' => true, 'my-recipes' => true );
check( 'next_step: nothing done, both tabs on, the survey comes first', vance_hub_dashboard_next_step( array(), $BOTH_ON ) === 'survey' );
check( 'next_step: survey done, the profile is next', vance_hub_dashboard_next_step( array( 'survey_done' => true ), $BOTH_ON ) === 'profile' );
check( 'next_step: survey and profile done, the first plan is next', vance_hub_dashboard_next_step( array( 'survey_done' => true, 'profile_done' => true ), $BOTH_ON ) === 'plan' );
check( 'next_step: plan saved, the first check is next', vance_hub_dashboard_next_step( array( 'survey_done' => true, 'profile_done' => true, 'plans' => 1 ), $BOTH_ON ) === 'check' );
check( 'next_step: everything done gives empty string', vance_hub_dashboard_next_step( array( 'survey_done' => true, 'profile_done' => true, 'plans' => 1, 'checks' => 1 ), $BOTH_ON ) === '' );
check( 'next_step: a count of 0 is not done', vance_hub_dashboard_next_step( array( 'survey_done' => true, 'profile_done' => true, 'plans' => 0 ), $BOTH_ON ) === 'plan' );
check( 'next_step: a count given as the string "0" is not done', vance_hub_dashboard_next_step( array( 'survey_done' => true, 'profile_done' => true, 'plans' => '0' ), $BOTH_ON ) === 'plan' );
check( 'next_step: meta that reads as a string is treated as nothing done', vance_hub_dashboard_next_step( 'oops', $BOTH_ON ) === 'survey' );
check( 'next_step: a missing enabled map is treated as all tabs off', vance_hub_dashboard_next_step( array(), array() ) === '' );
check( 'next_step: health profile off skips the survey and the profile, plan is next', vance_hub_dashboard_next_step( array(), array( 'health-profile' => false, 'my-recipes' => true ) ) === 'plan' );
check( 'next_step: health profile off also skips the check (its result shows there)', vance_hub_dashboard_next_step( array( 'plans' => 1 ), array( 'health-profile' => false, 'my-recipes' => true ) ) === '' );
check( 'next_step: my recipes off skips the plan, check is next', vance_hub_dashboard_next_step( array( 'survey_done' => true, 'profile_done' => true ), array( 'health-profile' => true, 'my-recipes' => false ) ) === 'check' );
check( 'next_step: both tabs off, nothing to suggest', vance_hub_dashboard_next_step( array(), array( 'health-profile' => false, 'my-recipes' => false ) ) === '' );
check( 'next_step: profile done is ignored when the profile tab is off', vance_hub_dashboard_next_step( array( 'survey_done' => true, 'profile_done' => false ), array( 'health-profile' => false, 'my-recipes' => false ) ) === '' );

// tool_status(): plural forms, dates, damaged input.
check( 'tool_status: survey done says the answers are saved', vance_hub_dashboard_tool_status( 'survey', array( 'survey_done' => true ) ) === array( 'done' => true, 'text' => 'Your answers are saved.' ) );
check( 'tool_status: survey not done says not started', vance_hub_dashboard_tool_status( 'survey', array( 'survey_done' => false ) ) === array( 'done' => false, 'text' => 'Not started.' ) );
check( 'tool_status: survey with a string meta is not started', vance_hub_dashboard_tool_status( 'survey', 'oops' ) === array( 'done' => false, 'text' => 'Not started.' ) );
check( 'tool_status: recipes, zero plans is not done', vance_hub_dashboard_tool_status( 'recipes', array( 'plans' => 0 ) ) === array( 'done' => false, 'text' => 'No saved meal plan.' ) );
check( 'tool_status: recipes, negative plans is clamped to zero', vance_hub_dashboard_tool_status( 'recipes', array( 'plans' => -3 ) ) === array( 'done' => false, 'text' => 'No saved meal plan.' ) );
check( 'tool_status: recipes, one plan uses the singular', vance_hub_dashboard_tool_status( 'recipes', array( 'plans' => 1 ) ) === array( 'done' => true, 'text' => '1 saved meal plan.' ) );
check( 'tool_status: recipes, two plans use the plural', vance_hub_dashboard_tool_status( 'recipes', array( 'plans' => 2 ) ) === array( 'done' => true, 'text' => '2 saved meal plans.' ) );
check( 'tool_status: recipes, 1234 plans get a thousands separator', vance_hub_dashboard_tool_status( 'recipes', array( 'plans' => 1234 ) ) === array( 'done' => true, 'text' => '1,234 saved meal plans.' ) );
check( 'tool_status: recipes, a numeric string count is read', vance_hub_dashboard_tool_status( 'recipes', array( 'plans' => '2' ) ) === array( 'done' => true, 'text' => '2 saved meal plans.' ) );
check( 'tool_status: recipes, a date is appended', vance_hub_dashboard_tool_status( 'recipes', array( 'plans' => 1, 'plan_date' => '5 Nov 2026' ) ) === array( 'done' => true, 'text' => '1 saved meal plan. Last saved 5 Nov 2026.' ) );
check( 'tool_status: recipes, an empty date adds nothing', vance_hub_dashboard_tool_status( 'recipes', array( 'plans' => 1, 'plan_date' => '' ) ) === array( 'done' => true, 'text' => '1 saved meal plan.' ) );
check( 'tool_status: recipes, a date given as an array is ignored', vance_hub_dashboard_tool_status( 'recipes', array( 'plans' => 1, 'plan_date' => array( '5 Nov' ) ) ) === array( 'done' => true, 'text' => '1 saved meal plan.' ) );
check( 'tool_status: nutrition, zero checks is not done', vance_hub_dashboard_tool_status( 'nutrition', array( 'checks' => 0 ) ) === array( 'done' => false, 'text' => 'No saved check.' ) );
check( 'tool_status: nutrition, one check uses the singular', vance_hub_dashboard_tool_status( 'nutrition', array( 'checks' => 1 ) ) === array( 'done' => true, 'text' => '1 saved check.' ) );
check( 'tool_status: nutrition, three checks with a date', vance_hub_dashboard_tool_status( 'nutrition', array( 'checks' => 3, 'check_date' => '12 Nov 2026' ) ) === array( 'done' => true, 'text' => '3 saved checks. Last check 12 Nov 2026.' ) );
check( 'tool_status: discounts, zero schemes is not done', vance_hub_dashboard_tool_status( 'discounts', array( 'discounts' => 0 ) ) === array( 'done' => false, 'text' => 'No saved scheme.' ) );
check( 'tool_status: discounts, one scheme uses the singular', vance_hub_dashboard_tool_status( 'discounts', array( 'discounts' => 1 ) ) === array( 'done' => true, 'text' => '1 saved scheme.' ) );
check( 'tool_status: discounts, two schemes use the plural', vance_hub_dashboard_tool_status( 'discounts', array( 'discounts' => 2 ) ) === array( 'done' => true, 'text' => '2 saved schemes.' ) );
check( 'tool_status: an unknown tool gives not done and no text', vance_hub_dashboard_tool_status( 'nope', array() ) === array( 'done' => false, 'text' => '' ) );
check( 'tool_status: a null state is treated as nothing saved', vance_hub_dashboard_tool_status( 'recipes', null ) === array( 'done' => false, 'text' => 'No saved meal plan.' ) );
check( 'tool_status: a non-numeric count is read as zero', vance_hub_dashboard_tool_status( 'nutrition', array( 'checks' => 'many' ) ) === array( 'done' => false, 'text' => 'No saved check.' ) );

// unread_count(): read lists, any scalar ID type, damaged rows.
check( 'unread: no messages, zero unread', vance_hub_dashboard_unread_count( array(), 7 ) === 0 );
check( 'unread: a message nobody read is unread', vance_hub_dashboard_unread_count( array( array() ), 7 ) === 1 );
check( 'unread: a message read by this member is not unread', vance_hub_dashboard_unread_count( array( array( 7 ) ), 7 ) === 0 );
check( 'unread: a message read by someone else is still unread', vance_hub_dashboard_unread_count( array( array( 8, 9 ) ), 7 ) === 1 );
check( 'unread: two of three unread', vance_hub_dashboard_unread_count( array( array( 7 ), array(), array( 3 ) ), 7 ) === 2 );
check( 'unread: string IDs match the integer member ID', vance_hub_dashboard_unread_count( array( array( '7' ) ), 7 ) === 0 );
check( 'unread: a zero-padded string ID matches', vance_hub_dashboard_unread_count( array( array( '07' ) ), 7 ) === 0 );
check( 'unread: a non-numeric string ID does not match', vance_hub_dashboard_unread_count( array( array( 'abc' ) ), 7 ) === 1 );
check( 'unread: a read list given as a string is unread (damaged meta)', vance_hub_dashboard_unread_count( array( '7' ), 7 ) === 1 );
check( 'unread: non-array rows are unread (damaged meta)', vance_hub_dashboard_unread_count( array( null, 'x', 7 ), 7 ) === 3 );
check( 'unread: nested arrays inside a read list do not match', vance_hub_dashboard_unread_count( array( array( array( 7 ) ) ), 7 ) === 1 );
check( 'unread: null and empty entries inside a read list do not match', vance_hub_dashboard_unread_count( array( array( null, '' ) ), 7 ) === 1 );
check( 'unread: the same reader listed twice still reads once', vance_hub_dashboard_unread_count( array( array( 7, 7 ) ), 7 ) === 0 );
check( 'unread: a non-array input gives zero, no error', vance_hub_dashboard_unread_count( null, 7 ) === 0 );

// tab_url()
check( 'tab_url: a plain slug', vance_hub_dashboard_tab_url( 'health-profile' ) === '?tab=health-profile' );
check( 'tab_url: spaces and ampersands are encoded', vance_hub_dashboard_tab_url( 'a b&c' ) === '?tab=a%20b%26c' );
check( 'tab_url: quotes and angle brackets are encoded', vance_hub_dashboard_tab_url( '"><x' ) === '?tab=%22%3E%3Cx' );
check( 'tab_url: an empty slug gives an empty tab, not a fatal', vance_hub_dashboard_tab_url( '' ) === '?tab=' );

/* ===================================================================== */
section( '3. Data: the member view, reads only' );

// A member with nothing saved.
hd_arrange();
$M = hd_user( 7, 'Ruth Adeyemi', '2024-03-05 10:00:00' );
$D = vance_hub_dashboard_data( $M );
check( 'data: nothing saved, the first step is the survey', is_array( $D['step'] ) && $D['step']['title'] === 'Do the Gastro Self-Assessment' );
check( 'data: nothing saved, the survey step links to the survey page', is_array( $D['step'] ) && $D['step']['href'] === 'https://example.test/gastro-health-survey/' );
check( 'data: nothing saved, four tools show', count( $D['tools'] ) === 4 );
check( 'data: nothing saved, the survey tile says not started', $D['tools'][0]['status'] === 'Not started.' && $D['tools'][0]['done'] === false );
check( 'data: nothing saved, the survey tile keeps its Start button', $D['tools'][0]['cta'] === 'Start the survey' );
check( 'data: nothing saved, the planner tile says no saved plan', $D['tools'][1]['status'] === 'No saved meal plan.' );
check( 'data: nothing saved, the nutrition tile says no saved check', $D['tools'][2]['status'] === 'No saved check.' );
check( 'data: nothing saved, the discounts tile says no saved scheme', $D['tools'][3]['status'] === 'No saved scheme.' );
check( 'data: nothing saved, four stat tiles show with a zero each', count( $D['stats'] ) === 4 && $D['stats'][0]['count'] === 0 && $D['stats'][3]['count'] === 0 );
check( 'data: nothing saved, no saved articles', $D['saved'] === array() );
check( 'data: nothing saved, no new-in-library articles', $D['fresh'] === array() );
check( 'data: nothing saved, the messages block is on, with no messages', $D['show_messages'] === true && $D['messages'] === array() && $D['unread'] === 0 );
check( 'data: nothing saved, the tool history is asked for the planner and nutrition, all rows', $GLOBALS['HIST_CALLS'] === array( array( 7, 'ibd-recipes', 0 ), array( 7, 'malnutrition-calculator', 0 ) ) );
check( 'data: nothing saved, the collector writes nothing', $GLOBALS['WRITES'] === array() );

// Name and join date.
hd_arrange();
$GLOBALS['USER_META'][7]['first_name'] = 'Ruth';
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth Adeyemi', '2024-03-05 10:00:00' ) );
check( 'data: the first name is read from first_name', $D['name'] === 'Ruth' );

hd_arrange();
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth Adeyemi', '2024-03-05 10:00:00' ) );
check( 'data: with no first_name, the display name is used', $D['name'] === 'Ruth Adeyemi' );

hd_arrange();
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth Adeyemi', '2024-03-05 10:00:00' ) );
check( 'data: the join date is the month and year, in words', $D['since'] === 'March 2024' );

hd_arrange();
$D = vance_hub_dashboard_data( hd_user( 8, 'New Member', '' ) );
check( 'data: a missing registration date gives no join line', $D['since'] === '' );

hd_arrange();
$D = vance_hub_dashboard_data( hd_user( 8, 'New Member', '0000-00-00 00:00:00' ) );
check( 'data: the zero registration date gives no join line', $D['since'] === '' );

hd_arrange();
$D = vance_hub_dashboard_data( hd_user( 8, 'New Member', 'not a date' ) );
check( 'data: an unparseable registration date gives no join line', $D['since'] === '' );

// The next step follows the real state.
hd_arrange();
$GLOBALS['USER_META'][7]['_sla_healthcare_quiz_results'] = array( 'q1' => 'a' );
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: survey done, the next step is the profile', is_array( $D['step'] ) && $D['step']['title'] === 'Complete your Health Profile' );
check( 'data: survey done, the next step links to the Health Profile tab', is_array( $D['step'] ) && $D['step']['href'] === '?tab=health-profile' );
check( 'data: survey done, the survey tile says the answers are saved', $D['tools'][0]['done'] === true && $D['tools'][0]['status'] === 'Your answers are saved.' );
check( 'data: survey done with the Health Profile on, the survey tile becomes "See my answers"', $D['tools'][0]['cta'] === 'See my answers' );
check( 'data: survey done with the Health Profile on, "See my answers" links to the Health Profile tab', $D['tools'][0]['href'] === '?tab=health-profile' );

hd_arrange( array(), array(), array(), true );
$GLOBALS['FEATURE_ON']['health-profile'] = false;
$GLOBALS['USER_META'][7]['_sla_healthcare_quiz_results'] = array( 'q1' => 'a' );
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: Health Profile off, the next step skips the profile and goes to the planner', is_array( $D['step'] ) && $D['step']['title'] === 'Build your first meal plan' );
check( 'data: Health Profile off, the survey tile keeps its own Start button', $D['tools'][0]['cta'] === 'Start the survey' );
check( 'data: Health Profile off, "See my answers" does not link to a switched-off tab', $D['tools'][0]['href'] !== '?tab=health-profile' );
check( 'data: Health Profile off, the survey tile still shows the member saved answers', $D['tools'][0]['status'] === 'Your answers are saved.' );

// Everything done: no next step.
hd_arrange();
$GLOBALS['USER_META'][7]['_sla_healthcare_quiz_results'] = array( 'q1' => 'a' );
$GLOBALS['USER_META'][7]['_sla_clinical_profile'] = array( 'condition' => 'ibd' );
$GLOBALS['HISTORY']['ibd-recipes'] = array(
	array( 'ts' => strtotime( '2026-11-05 10:00:00 UTC' ), 'payload' => array( 'week' => 1 ) ),
);
$GLOBALS['HISTORY']['malnutrition-calculator'] = array(
	array( 'ts' => strtotime( '2026-10-01 09:00:00 UTC' ), 'payload' => array( 'score' => 1 ) ),
	array( 'ts' => strtotime( '2026-11-12 09:00:00 UTC' ), 'payload' => array( 'score' => 2 ) ),
	array( 'ts' => strtotime( '2026-11-03 09:00:00 UTC' ), 'payload' => array( 'score' => 3 ) ),
);
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: all four steps done, there is no next step', $D['step'] === null );
check( 'data: planner tile shows the count and the newest plan date', $D['tools'][1]['status'] === '1 saved meal plan. Last saved 5 Nov 2026.' );
check( 'data: nutrition tile shows three checks and the newest date, not the first row', $D['tools'][2]['status'] === '3 saved checks. Last check 12 Nov 2026.' );

// Discounts tile and the discounts tab.
hd_arrange( array(), array(), array(), true );
$GLOBALS['FEATURE_ON']['discounts'] = false;
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: discounts tab off removes its tile, three tiles left', count( $D['tools'] ) === 3 );
check( 'data: discounts tab off leaves the nutrition tile in place as the third', $D['tools'][2]['mod'] === 'nutrition' );

hd_arrange();
$GLOBALS['USER_META'][7]['_sla_saved_discounts'] = array( 'scheme-a', 'scheme-b' );
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: discounts tab on shows the saved scheme count', $D['tools'][3]['status'] === '2 saved schemes.' && $D['tools'][3]['done'] === true );

// Saved-item counts and the tabs that switch them off.
hd_arrange();
$GLOBALS['USER_META'][7]['_sla_user_notes'] = array( 'n1', 'n2' );
$GLOBALS['USER_META'][7]['_sla_saved_chats'] = array( 'c1' );
$GLOBALS['USER_META'][7]['_sla_profile_docs'] = array( 'd1', 'd2', 'd3' );
$GLOBALS['FEATURE_LABEL']['reading-list'] = 'My Reading List';
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: stat tiles come in the order reading list, notes, chats, documents', $D['stats'][0]['slug'] === 'reading-list' && $D['stats'][1]['slug'] === 'notes' && $D['stats'][2]['slug'] === 'ai-chats' && $D['stats'][3]['slug'] === 'documents' );
check( 'data: the notes count is read from _sla_user_notes', $D['stats'][1]['count'] === 2 );
check( 'data: the chats count is read from _sla_saved_chats', $D['stats'][2]['count'] === 1 );
check( 'data: the documents count is read from _sla_profile_docs', $D['stats'][3]['count'] === 3 );
check( 'data: a saved label replaces the default stat label', $D['stats'][0]['label'] === 'My Reading List' );
check( 'data: an unset label falls back to the default stat label', $D['stats'][1]['label'] === 'Notes' );
check( 'data: a stat tile links to its own tab', $D['stats'][1]['href'] === '?tab=notes' );

hd_arrange();
$GLOBALS['FEATURE_ON']['notes'] = false;
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: notes tab off removes its stat tile, three left', count( $D['stats'] ) === 3 );
check( 'data: notes tab off, the next tile is the chats tile', $D['stats'][1]['slug'] === 'ai-chats' );

hd_arrange();
$GLOBALS['FEATURE_ON']['ai-chats'] = false;
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: VANCE-Ai tab off, the Ask button is not offered', $D['ask'] === false );

hd_arrange();
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: VANCE-Ai tab on, the Ask button is offered', $D['ask'] === true );

// Malformed saved-item meta reads as nothing.
hd_arrange();
$GLOBALS['USER_META'][7]['_sla_user_notes'] = 'oops';
$GLOBALS['USER_META'][7]['_sla_saved_chats'] = '';
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: a string in the notes meta counts as zero notes', $D['stats'][1]['count'] === 0 );
check( 'data: an empty string in the chats meta counts as zero chats', $D['stats'][2]['count'] === 0 );

// Saved articles: bookmarks, the newest first, published only.
hd_arrange();
$GLOBALS['USER_META'][7]['_sla_reading_list'] = array( 11, 22, 33 );
$GLOBALS['QUERY_POSTS']['any'] = array( post_obj( 33, 'Newest saved' ) );
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: saved query asks for the bookmarks newest first', $GLOBALS['WP_QUERY_LOG'][0]['post__in'] === array( 33, 22, 11 ) );
check( 'data: saved query asks for published posts only', $GLOBALS['WP_QUERY_LOG'][0]['post_status'] === 'publish' );
check( 'data: saved query asks for four posts', $GLOBALS['WP_QUERY_LOG'][0]['posts_per_page'] === 4 );
check( 'data: saved query runs before the new-in-library query', $GLOBALS['WP_QUERY_LOG'][1]['post_type'] === 'post' );
check( 'data: saved articles show the one post the query returned', count( $D['saved'] ) === 1 && $D['saved'][0]['title'] === 'Newest saved' );

hd_arrange();
$GLOBALS['USER_META'][7]['_sla_reading_list'] = range( 1, 25 );
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: only the newest 20 bookmarks are queried', count( $GLOBALS['WP_QUERY_LOG'][0]['post__in'] ) === 20 );
check( 'data: the newest bookmark is first in the 20', $GLOBALS['WP_QUERY_LOG'][0]['post__in'][0] === 25 );
check( 'data: the oldest of the 20 is bookmark 6', $GLOBALS['WP_QUERY_LOG'][0]['post__in'][19] === 6 );

hd_arrange();
$GLOBALS['USER_META'][7]['_sla_reading_list'] = array( 0, 'x', null, 9 );
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: damaged bookmark rows (0, text, null) are dropped', $GLOBALS['WP_QUERY_LOG'][0]['post__in'] === array( 9 ) );
check( 'data: the saved count skips damaged rows', $D['stats'][0]['count'] === 1 );

hd_arrange();
$GLOBALS['USER_META'][7]['_sla_reading_list'] = 'oops';
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: a string bookmark meta gives no saved query', count( $GLOBALS['WP_QUERY_LOG'] ) === 1 );
check( 'data: a string bookmark meta gives zero saved articles', $D['saved'] === array() && $D['stats'][0]['count'] === 0 );

hd_arrange();
$GLOBALS['USER_META'][7]['_sla_reading_list'] = array( 11 );
$GLOBALS['FEATURE_ON']['reading-list'] = false;
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: reading-list tab off runs no saved query', count( $GLOBALS['WP_QUERY_LOG'] ) === 1 && $GLOBALS['WP_QUERY_LOG'][0]['post_type'] === 'post' );
check( 'data: reading-list tab off gives no saved articles and no saved stat', $D['saved'] === array() && count( $D['stats'] ) === 3 );

hd_arrange();
$GLOBALS['QUERY_POSTS']['any'] = array( post_obj( 1, 'A' ), post_obj( 2, 'B' ), post_obj( 3, 'C' ), post_obj( 4, 'D' ), post_obj( 5, 'E' ) );
$GLOBALS['USER_META'][7]['_sla_reading_list'] = array( 1 );
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: saved articles show at most four even when the query returns more', count( $D['saved'] ) === 4 );

// Messages.
hd_arrange();
$GLOBALS['MESSAGES']    = array( post_obj( 301, 'Clinic update' ), post_obj( 302, 'Welcome' ) );
$GLOBALS['POST_DATES'][301] = strtotime( '2026-11-02 09:00:00 UTC' );
$GLOBALS['META'][301]['_sla_msg_read_by'] = array( 7 );
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: messages are asked for with read ones included, for this member', $GLOBALS['MSG_CALLS'] === array( array( 7, true ) ) );
check( 'data: two messages, one unread, is the unread count', $D['unread'] === 1 && count( $D['messages'] ) === 2 );
check( 'data: a message this member has read is not marked unread', $D['messages'][0]['unread'] === false );
check( 'data: a message with no read list is marked unread', $D['messages'][1]['unread'] === true );
check( 'data: a message row carries its title and date', $D['messages'][0]['title'] === 'Clinic update' && $D['messages'][0]['date'] === '2 Nov 2026' );

hd_arrange();
$GLOBALS['MESSAGES'] = array( post_obj( 401, 'One' ), post_obj( 402, 'Two' ), post_obj( 403, 'Three' ), post_obj( 404, 'Four' ) );
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: the messages list shows the newest three', count( $D['messages'] ) === 3 );
check( 'data: the unread count covers every message, not only the three shown', $D['unread'] === 4 );

hd_arrange();
$GLOBALS['MESSAGES'] = array( post_obj( 501, 'Hidden' ) );
$GLOBALS['FEATURE_ON']['messages'] = false;
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: messages tab off, the messages block is off', $D['show_messages'] === false && $D['messages'] === array() );
check( 'data: messages tab off, the unread count is zero', $D['unread'] === 0 );
check( 'data: messages tab off, the message query is not run', $GLOBALS['MSG_CALLS'] === array() );

// New in the library.
hd_arrange();
$GLOBALS['QUERY_POSTS']['post'] = array( post_obj( 401, 'A' ), post_obj( 402, 'B' ), post_obj( 403, 'C' ), post_obj( 404, 'D' ) );
$GLOBALS['USER_META'][7]['_sla_reading_list'] = array( 401 );
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: new-in-library query excludes the bookmarked post', $GLOBALS['WP_QUERY_LOG'][1]['post__not_in'] === array( 401 ) );
check( 'data: new-in-library query asks for published posts only', $GLOBALS['WP_QUERY_LOG'][1]['post_status'] === 'publish' );
check( 'data: new-in-library shows three posts', count( $D['fresh'] ) === 3 );
check( 'data: new-in-library does not show the bookmarked post', $D['fresh'][0]['id'] === 402 );

hd_arrange();
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'data: with no bookmarks the new-in-library query excludes nothing', $GLOBALS['WP_QUERY_LOG'][0]['post__not_in'] === array() );

// Reads only.
hd_arrange( array(), array(), array(), true );
$GLOBALS['USER_META'][7]['_sla_healthcare_quiz_results'] = array( 'q1' => 'a' );
$GLOBALS['USER_META'][7]['_sla_reading_list'] = array( 11 );
$GLOBALS['USER_META'][7]['_sla_user_notes'] = array( 'n' );
$GLOBALS['QUERY_POSTS']['any'] = array( post_obj( 11, 'Saved' ) );
$GLOBALS['QUERY_POSTS']['post'] = array( post_obj( 12, 'Fresh' ) );
$GLOBALS['MESSAGES'] = array( post_obj( 601, 'Msg' ) );
$D = vance_hub_dashboard_data( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'reads only: a full run with every block writes nothing', $GLOBALS['WRITES'] === array() );

/* ===================================================================== */
section( '4. The HTML' );

// Greeting.
$V = hd_view_empty();
$HTML = vance_hub_dashboard_home_html( $V );
check( 'html: the greeting shows the first name', strpos( $HTML, 'Hello, Ruth' ) !== false );
check( 'html: exactly one <h1> in the empty view', substr_count( $HTML, '<h1' ) === 1 );
check( 'html: no join line when since is empty', strpos( $HTML, 'vhd-hello__since' ) === false );
check( 'html: no Ask button when ask is off', strpos( $HTML, 'data-vance-askai-open' ) === false );

$V['since'] = 'March 2024';
$V['ask']   = true;
$HTML = vance_hub_dashboard_home_html( $V );
check( 'html: the join line reads "Member since" and the month', strpos( $HTML, '<p class="vhd-hello__since">Member since March 2024</p>' ) !== false );
check( 'html: the Ask button opens a new VANCE-Ai conversation', strpos( $HTML, 'data-vance-askai-open="new"' ) !== false );

// Blocks with no data are absent.
$HTML = vance_hub_dashboard_home_html( hd_view_empty() );
check( 'absent: no next-step block when step is null', strpos( $HTML, 'id="vhd-step-title"' ) === false );
check( 'absent: no tools block when there are no tools', strpos( $HTML, 'id="vhd-tools-title"' ) === false );
check( 'absent: no saved-items block when there are no stats', strpos( $HTML, 'id="vhd-stats-title"' ) === false );
check( 'absent: no saved-articles card when there are none', strpos( $HTML, 'id="vhd-saved-title"' ) === false );
check( 'absent: no messages card when messages are off', strpos( $HTML, 'id="vhd-msgs-title"' ) === false );
check( 'absent: no new-in-library card when there are no posts', strpos( $HTML, 'id="vhd-fresh-title"' ) === false );
check( 'absent: no column wrapper when neither card shows', strpos( $HTML, 'vhd-cols' ) === false );
check( 'absent: no unread badge when messages are off', strpos( $HTML, 'vhd-badge' ) === false );
check( 'absent: no empty-messages line when messages are off', strpos( $HTML, 'No messages from the team.' ) === false );

// Next step.
$V = hd_view_empty();
$V['step'] = hd_step( 'Do the Gastro Self-Assessment', 'https://example.test/gastro-health-survey/' );
$HTML = vance_hub_dashboard_home_html( $V );
check( 'html: a next step shows its title', strpos( $HTML, '<h2 id="vhd-step-title" class="vhd-step__title">Do the Gastro Self-Assessment</h2>' ) !== false );
check( 'html: a next step links its button to the step', strpos( $HTML, '<a class="vhd-btn vhd-btn--white" href="https://example.test/gastro-health-survey/">Go' ) !== false );

$V['step'] = hd_step( 'Title', 'javascript:alert(1)' );
$HTML = vance_hub_dashboard_home_html( $V );
check( 'html: a javascript: step link is dropped to an empty href', strpos( $HTML, 'href=""' ) !== false && strpos( $HTML, 'javascript:' ) === false );

// Tools.
$V = hd_view_empty();
$V['tools'] = array( hd_tool( 'survey', 'Find what fits me', true, 'Your answers are saved.' ) );
$HTML = vance_hub_dashboard_home_html( $V );
check( 'html: a done tool shows the check mark', strpos( $HTML, 'M5 12l5 5L20 7' ) !== false );
check( 'html: a done tool carries the is-done class', strpos( $HTML, 'vhd-tool__status is-done' ) !== false );

$V['tools'] = array( hd_tool( 'recipes', 'Plan what to eat', false, 'No saved meal plan.' ) );
$HTML = vance_hub_dashboard_home_html( $V );
check( 'html: a tool not done shows no check mark', strpos( $HTML, 'M5 12l5 5L20 7' ) === false );
check( 'html: a tool not done has no is-done class', strpos( $HTML, 'is-done' ) === false );
check( 'html: a tool shows its status text', strpos( $HTML, 'No saved meal plan.' ) !== false );

$V['tools'] = array( hd_tool( 'nutrition', 'Check', false, '' ) );
$HTML = vance_hub_dashboard_home_html( $V );
check( 'html: a tool with an empty status shows no status span', strpos( $HTML, 'vhd-tool__status' ) === false );

// Stats.
$V = hd_view_empty();
$V['stats'] = array( hd_stat( 'notes', 1234, 'Notes' ) );
$HTML = vance_hub_dashboard_home_html( $V );
check( 'html: a stat count gets a thousands separator', strpos( $HTML, '<span class="vhd-stat__n">1,234</span>' ) !== false );
check( 'html: a stat label is shown', strpos( $HTML, '<span class="vhd-stat__label">Notes</span>' ) !== false );
check( 'html: a stat links to its tab', strpos( $HTML, '<a class="vhd-stat" href="?tab=notes">' ) !== false );

// Saved articles and messages: the columns.
$card = hd_card( 11, 'A saved article' );
$V = hd_view_empty();
$V['saved'] = array( $card );
$HTML = vance_hub_dashboard_home_html( $V );
check( 'cols: saved articles only gives the one-column wrapper', strpos( $HTML, '<div class="vhd-cols vhd-cols--one">' ) !== false );
check( 'cols: saved articles only shows the saved card', strpos( $HTML, 'id="vhd-saved-title"' ) !== false && strpos( $HTML, 'id="vhd-msgs-title"' ) === false );

$V = hd_view_empty();
$V['show_messages'] = true;
$HTML = vance_hub_dashboard_home_html( $V );
check( 'cols: messages on, the home shows no Messages card, no badge and no list', strpos( $HTML, 'id="vhd-msgs-title"' ) === false && strpos( $HTML, 'vhd-badge' ) === false && strpos( $HTML, 'vhd-msgs' ) === false );

$V = hd_view_empty();
$V['saved'] = array( $card );
$V['show_messages'] = true;
$HTML = vance_hub_dashboard_home_html( $V );

// Empty messages line, and the unread badge.
$V = hd_view_empty();
$V['show_messages'] = true;
$HTML = vance_hub_dashboard_home_html( $V );
check( 'messages: no messages shows no message list', strpos( $HTML, '<ul class="vhd-msgs">' ) === false );
check( 'messages: no unread gives no badge', strpos( $HTML, 'vhd-badge' ) === false );

$V['unread'] = 1;
$HTML = vance_hub_dashboard_home_html( $V );

$V['unread'] = 1234;
$HTML = vance_hub_dashboard_home_html( $V );

$V = hd_view_empty();
$V['show_messages'] = true;
$V['messages'] = array(
	array( 'title' => 'Clinic update', 'date' => '2 Nov 2026', 'unread' => true ),
	array( 'title' => 'Welcome', 'date' => '1 Nov 2026', 'unread' => false ),
);
$V['unread'] = 1;
$HTML = vance_hub_dashboard_home_html( $V );

// Escaping of member-supplied text.
$V = hd_view_empty();
$V['name'] = '<script>alert(1)</script>';
$HTML = vance_hub_dashboard_home_html( $V );
check( 'escape: a name with a script tag is escaped', strpos( $HTML, '&lt;script&gt;alert(1)&lt;/script&gt;' ) !== false );
check( 'escape: no raw script tag from the name', strpos( $HTML, '<script>' ) === false );

$V = hd_view_empty();
$V['name'] = 'Ruth "Rue" O\'Brien';
$HTML = vance_hub_dashboard_home_html( $V );
check( 'escape: a name with quotes is escaped to entities', strpos( $HTML, 'Ruth &quot;Rue&quot; O&#039;Brien' ) !== false );

$V = hd_view_empty();
$V['tools'] = array( hd_tool( 'survey', '"><img src=x onerror=alert(1)>', false, '<b>bold</b>' ) );
$HTML = vance_hub_dashboard_home_html( $V );
check( 'escape: a tool title with an injected tag is escaped', strpos( $HTML, '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;' ) !== false && strpos( $HTML, '<img src=x' ) === false );
check( 'escape: a tool status with a tag is escaped', strpos( $HTML, '&lt;b&gt;bold&lt;/b&gt;' ) !== false && strpos( $HTML, '<b>bold' ) === false );

$V = hd_view_empty();
$V['stats'] = array( hd_stat( 'notes', 1, '<i>Notes</i>' ) );
$HTML = vance_hub_dashboard_home_html( $V );
check( 'escape: a stat label with a tag is escaped', strpos( $HTML, '&lt;i&gt;Notes&lt;/i&gt;' ) !== false && strpos( $HTML, '<i>Notes' ) === false );

$V = hd_view_empty();
$V['saved'] = array( hd_card( 11, '<script>t</script>', '<i>cat</i>' ) );
$HTML = vance_hub_dashboard_home_html( $V );
check( 'escape: a saved article title is escaped', strpos( $HTML, '&lt;script&gt;t&lt;/script&gt;' ) !== false && strpos( $HTML, '<script>t' ) === false );
check( 'escape: a saved article category is escaped', strpos( $HTML, '&lt;i&gt;cat&lt;/i&gt;' ) !== false && strpos( $HTML, '<i>cat' ) === false );

$V = hd_view_empty();
$V['show_messages'] = true;
$V['messages'] = array( array( 'title' => '<script>m</script>', 'date' => '', 'unread' => false ) );
$HTML = vance_hub_dashboard_home_html( $V );

$V = hd_view_empty();
$V['saved_label'] = '<u>Saved</u>';
$V['saved'] = array( hd_card( 11, 'x' ) );
$HTML = vance_hub_dashboard_home_html( $V );
check( 'escape: a renamed saved-articles label is escaped', strpos( $HTML, '&lt;u&gt;Saved&lt;/u&gt;' ) !== false && strpos( $HTML, '<u>Saved' ) === false );

$V = hd_view_empty();
$V['saved'] = array( hd_card( 11, 'x' ) );
$V['saved'][0]['url'] = 'https://example.test/p/11/" onmouseover="alert(1)';
$HTML = vance_hub_dashboard_home_html( $V );
check( 'escape: a quote in a card link cannot break out of the href', strpos( $HTML, '" onmouseover="' ) === false );

// Article rows: thumbnail, empty category, date.
$V = hd_view_empty();
$V['fresh'] = array( hd_card( 12, 'No image', '', '', '', '' ) );
$HTML = vance_hub_dashboard_home_html( $V );
check( 'row: no image gives the decorative placeholder span', strpos( $HTML, 'vhd-post__img--none' ) !== false );
check( 'row: no image gives no <img>', strpos( $HTML, '<img class="vhd-post__img"' ) === false );
check( 'row: no category gives no category span', strpos( $HTML, 'vhd-post__cat' ) === false );
check( 'row: no date gives no <time>', strpos( $HTML, '<time' ) === false );

$V['fresh'] = array( hd_card( 12, 'With image', 'Gastro Living', 'https://example.test/img/a.jpg', '2 Nov 2026', '2026-11-02T09:00:00+00:00' ) );
$HTML = vance_hub_dashboard_home_html( $V );
check( 'row: an image gets alt="" because the headline names the article', strpos( $HTML, '<img class="vhd-post__img" src="https://example.test/img/a.jpg" alt=""' ) !== false );
check( 'row: a category is shown', strpos( $HTML, '<span class="vhd-post__cat">Gastro Living</span>' ) !== false );
check( 'row: a date gets a machine-readable datetime', strpos( $HTML, '<time class="vhd-post__date" datetime="2026-11-02T09:00:00+00:00">2 Nov 2026</time>' ) !== false );

// The row list class: the library uses the row modifier, the saved list does not.
check( 'row: the new-in-library list uses the row modifier', strpos( $HTML, '<ul class="vhd-posts vhd-posts--row">' ) !== false );

// Render entry point.
hd_arrange();
check( 'render: no user prints nothing', hd_render( null ) === '' );
check( 'render: a user with no ID (not a real user) prints nothing', hd_render( hd_user( 0, 'Nobody', '' ) ) === '' );
$GLOBALS['USER_META'][7]['_sla_user_notes'] = array( 'n' );
$OUT = hd_render( hd_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'render: a real member prints the greeting', strpos( $OUT, 'Hello, Ruth' ) !== false );
check( 'render: a real member prints the saved-items block', strpos( $OUT, 'id="vhd-stats-title"' ) !== false );
check( 'render: the collector ran as a read only', $GLOBALS['WRITES'] === array() );

/* ===================================================================== */
section( '5. Assets and hook' );

check( 'hook: wp_enqueue_scripts runs the stylesheet at priority 20', isset( $GLOBALS['HOOKS']['wp_enqueue_scripts'][20] ) && in_array( 'vance_hub_dashboard_enqueue_assets', $GLOBALS['HOOKS']['wp_enqueue_scripts'][20], true ) );

hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true ), array(), array(), true );
$GLOBALS['TEMPLATE'] = 'page-dashboard.php';
vance_hub_dashboard_enqueue_assets();
check( 'assets: enqueued on the dashboard page, logged in, active', array_key_exists( 'style:vance-hub-dashboard', $GLOBALS['ENQUEUED'] ) );
check( 'assets: the stylesheet URL points at hub-dashboard.css', $GLOBALS['ENQUEUED']['style:vance-hub-dashboard']['src'] === 'https://example.test/wp-content/themes/vance-health-hub/assets/css/hub-dashboard.css' );
check( 'assets: the stylesheet depends on the main stylesheet', $GLOBALS['ENQUEUED']['style:vance-hub-dashboard']['deps'] === array( 'vance-main-style' ) );

// The live page: slug 'dashboard', NO assigned template (is_page_template() false).
hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true ), array(), array(), true );
$GLOBALS['SLUG'] = 'dashboard';
vance_hub_dashboard_enqueue_assets();
check( 'assets: enqueued on the dashboard page matched by slug with no assigned template', array_key_exists( 'style:vance-hub-dashboard', $GLOBALS['ENQUEUED'] ) );

hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true ), array(), array(), true );
$GLOBALS['SLUG'] = 'my-notes';
vance_hub_dashboard_enqueue_assets();
check( 'assets: not enqueued on another page matched by slug', array_key_exists( 'style:vance-hub-dashboard', $GLOBALS['ENQUEUED'] ) === false );

hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true ), array(), array(), false );
$GLOBALS['TEMPLATE'] = 'page-dashboard.php';
vance_hub_dashboard_enqueue_assets();
check( 'assets: not enqueued for a visitor who is logged out', array_key_exists( 'style:vance-hub-dashboard', $GLOBALS['ENQUEUED'] ) === false );

hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true ), array(), array(), true );
$GLOBALS['TEMPLATE'] = 'page-my-notes.php';
vance_hub_dashboard_enqueue_assets();
check( 'assets: not enqueued on another page template', array_key_exists( 'style:vance-hub-dashboard', $GLOBALS['ENQUEUED'] ) === false );

hd_arrange( array( 'vance_hub_show_dashboard' => false ), array(), array(), true );
$GLOBALS['TEMPLATE'] = 'page-dashboard.php';
vance_hub_dashboard_enqueue_assets();
check( 'assets: not enqueued when the layout is off', array_key_exists( 'style:vance-hub-dashboard', $GLOBALS['ENQUEUED'] ) === false );

/* ===================================================================== */
section( '6. page-dashboard.php, by static inspection' );

// Line endings normalised so the needles match whichever way git wrote the file.
$PD = str_replace( "\r\n", "\n", file_get_contents( $THEME . '/page-dashboard.php' ) );

$FLAG_NEEDLE  = '$vance_hub_dash = ( \'home\' === $current_tab && function_exists( \'vance_hub_dashboard_active\' ) && vance_hub_dashboard_active() );';
$LABEL_NEEDLE = '$dash_label = \'vance_dashboard_feature_label\';';
$FLAG         = strpos( $PD, $FLAG_NEEDLE );
$LABEL        = strpos( $PD, $LABEL_NEEDLE );
check( 'page: the flag is set with the home-tab and active test', $FLAG !== false );
check( 'page: the flag is set after $dash_label', $FLAG !== false && $LABEL !== false && $FLAG > $LABEL );

$GATE_NEEDLE = '<?php if ( ! $vance_hub_dash ) : ?>';
$GATE        = strpos( $PD, $GATE_NEEDLE );
$SWITCH      = strpos( $PD, '<?php switch($current_tab) :' );
$H1          = $GATE === false ? false : strpos( $PD, '<h1 style="font-family:', $GATE );
check( 'page: the heading gate appears once', substr_count( $PD, $GATE_NEEDLE ) === 1 );
check( 'page: the heading gate comes before the heading and before the switch', $GATE !== false && $H1 !== false && $SWITCH !== false && $GATE < $H1 && $H1 < $SWITCH );

$CLOSE      = '<?php endif; ?>';
$LAST_CLOSE = $SWITCH === false ? false : strrpos( substr( $PD, 0, $SWITCH ), $CLOSE );
check( 'page: the heading gate closes right before the switch', $LAST_CLOSE !== false && $LAST_CLOSE > $H1 && trim( substr( $PD, $LAST_CLOSE + strlen( $CLOSE ), $SWITCH - $LAST_CLOSE - strlen( $CLOSE ) ) ) === '' );

// Two 'home' cases exist: one in the heading's title switch, above the gate's
// close, and the dashboard switch. Search from the dashboard switch onward.
$CASE_HOME = $SWITCH === false ? false : strpos( $PD, "case 'home':", $SWITCH );
$IF_NEEDLE = 'if ( $vance_hub_dash ) {';
$IF        = strpos( $PD, $IF_NEEDLE );
$CALL      = strpos( $PD, 'vance_hub_dashboard_render_home( $current_user );' );
check( 'page: the home case checks the flag as its first statement', $CASE_HOME !== false && $IF !== false && $CASE_HOME < $IF && trim( substr( $PD, $CASE_HOME + strlen( "case 'home':" ), $IF - $CASE_HOME - strlen( "case 'home':" ) ) ) === '' );
check( 'page: the flagged branch renders the hub home directly after its check', $IF !== false && $CALL !== false && trim( substr( $PD, $IF + strlen( $IF_NEEDLE ), $CALL - $IF - strlen( $IF_NEEDLE ) ) ) === '' );
check( 'page: the flagged branch breaks out of the switch', $CALL !== false && strpos( $PD, 'break;', $CALL ) !== false && strpos( $PD, 'break;', $CALL ) - $CALL < 100 );
check( 'page: the classic home stays after the early break, unchanged', strpos( $PD, '// Admin-broadcast messages: compute unread count' ) > $CALL );

/* ===================================================================== */
section( '7. The greeting, the top row and the side-menu card' );

// The check-in block is stubbed here. The real one lives in hub-dashboard-features.php
// and has its own suite. The stub gives the top-row order something to find.
define( 'HD_CHECKIN_STUB', '<section class="vhd-checkin-stub">Check-in</section>' );
function vance_hub_dashboard_checkin_html( $c ) {
	return HD_CHECKIN_STUB;
}

// Side-menu layout: the page keeps only a visually hidden h1.
$MC = hd_view_empty();
$MC['name']        = 'Ruth';
$MC['since']       = 'March 2026';
$MC['member_code'] = 'K7QM-4XZP';
$MC['menu_card']   = true;
$MH = vance_hub_dashboard_home_html( $MC );
check( 'menu card: no greeting section, the side menu carries the member', strpos( $MH, 'vhd-hello' ) === false );
check( 'menu card: the member number text is not printed on the page', strpos( $MH, 'K7QM-4XZP' ) === false );
check( 'menu card: no member number line on the page', strpos( $MH, 'Member number' ) === false );
check( 'menu card: no join line on the page', strpos( $MH, 'Member since' ) === false );
check( 'menu card: the heading is a visually hidden h1 with the name', strpos( $MH, '<h1 class="vhd-sr">Hello, Ruth</h1>' ) !== false );
check( 'menu card: exactly one h1 on the page', substr_count( $MH, '<h1' ) === 1 );
check( 'menu card: no My Hub eyebrow on the page', strpos( $MH, 'My Hub' ) === false );

// Classic layout: the greeting keeps its own eyebrow, date and member number.
$CL = hd_view_empty();
$CL['name']        = 'Ruth';
$CL['since']       = 'March 2026';
$CL['member_code'] = 'K7QM-4XZP';
$CL['ask']         = true;
$CL['menu_card']   = false;
$CH = vance_hub_dashboard_home_html( $CL );
check( 'classic: the greeting section is kept', strpos( $CH, '<section class="vhd-hello" aria-labelledby="vhd-hello-title">' ) !== false );
check( 'classic: the visible h1 keeps its id and class', strpos( $CH, '<h1 id="vhd-hello-title" class="vhd-hello__title">Hello, Ruth</h1>' ) !== false );
check( 'classic: the eyebrow shows', strpos( $CH, '<p class="vhd-eyebrow">My Hub</p>' ) !== false );
check( 'classic: the join line shows', strpos( $CH, '<p class="vhd-hello__since">Member since March 2026</p>' ) !== false );
check( 'classic: the member number line shows', strpos( $CH, '<p class="vhd-hello__code">Member number K7QM-4XZP</p>' ) !== false );
check( 'classic: no screen-reader heading', strpos( $CH, 'vhd-sr' ) === false );
check( 'classic: exactly one h1 on the page', substr_count( $CH, '<h1' ) === 1 );
$CH_CLOSE = strpos( $CH, '</section>' );
$CH_ASK   = strpos( $CH, 'data-vance-askai-open' );
check( 'classic: the Ask button sits after the greeting section closes, not inside it', $CH_CLOSE !== false && $CH_ASK !== false && $CH_ASK > $CH_CLOSE );
check( 'classic: exactly one Ask button', substr_count( $CH, 'data-vance-askai-open' ) === 1 );

// Top row: next step, then Ask, then check-in, all in one wrapper.
$TP = hd_view_empty();
$TP['step']    = hd_step( 'Do the survey', 'https://example.test/survey/' );
$TP['ask']     = true;
$TP['checkin'] = array( 'questions' => array() );
$TH = vance_hub_dashboard_home_html( $TP );
$P_TOP  = strpos( $TH, '<div class="vhd-top">' );
$P_STEP = strpos( $TH, 'class="vhd-step"' );
$P_ASK  = strpos( $TH, 'class="vhd-ask"' );
$P_CHK  = strpos( $TH, HD_CHECKIN_STUB );
check( 'top: the wrapper opens before the next step', $P_TOP !== false && $P_STEP !== false && $P_TOP < $P_STEP );
check( 'top: the next step comes before the Ask block', $P_STEP !== false && $P_ASK !== false && $P_STEP < $P_ASK );
check( 'top: the Ask block comes before the check-in', $P_ASK !== false && $P_CHK !== false && $P_ASK < $P_CHK );
check( 'top: the check-in is the last thing in the wrapper', $P_CHK !== false && substr( $TH, $P_CHK + strlen( HD_CHECKIN_STUB ), 6 ) === '</div>' );

// Top row absent: no wrapper, no Ask block.
$NO = vance_hub_dashboard_home_html( hd_view_empty() );
check( 'top: no wrapper when step, Ask and check-in are all absent', strpos( $NO, 'vhd-top' ) === false );
check( 'top: no Ask block when Ask is off', strpos( $NO, 'class="vhd-ask"' ) === false && strpos( $NO, 'data-vance-askai-open' ) === false );

// Ask on its own gets the wrapper, and shows on the side-menu layout too.
$AO = hd_view_empty();
$AO['ask'] = true;
$AH = vance_hub_dashboard_home_html( $AO );
check( 'ask: the Ask block shows when Ask is on', strpos( $AH, '<section class="vhd-ask" aria-labelledby="vhd-ask-title">' ) !== false && strpos( $AH, 'data-vance-askai-open="new"' ) !== false );
check( 'ask: the Ask block is wrapped in vhd-top on its own', strpos( $AH, '<div class="vhd-top"><section class="vhd-ask"' ) !== false );
$AM = $AO;
$AM['menu_card'] = true;
check( 'ask: still shown on the side-menu layout', strpos( vance_hub_dashboard_home_html( $AM ), 'data-vance-askai-open="new"' ) !== false );

// Check-in on its own gets the wrapper.
$CO = hd_view_empty();
$CO['checkin'] = array( 'questions' => array() );
check( 'top: a check-in alone gets the wrapper', strpos( vance_hub_dashboard_home_html( $CO ), '<div class="vhd-top">' . HD_CHECKIN_STUB ) !== false );

// Member card: the first word of the first name.
hd_arrange();
$GLOBALS['USER_META'][7] = array( 'first_name' => 'Mary Ann', 'last_name' => 'Smith' );
$CD = vance_hub_dashboard_member_card_data( hd_user( 7, 'mary.a', '2024-03-05 10:00:00' ) );
check( 'first: the first word of a two-word first name', $CD['first'] === 'Mary' );
check( 'first: the full name is still both words and the surname', $CD['name'] === 'Mary Ann Smith' );

hd_arrange();
$GLOBALS['USER_META'][7] = array( 'first_name' => 'Ruth', 'last_name' => 'Adams' );
$CD = vance_hub_dashboard_member_card_data( hd_user( 7, 'ruth.a', '2024-03-05 10:00:00' ) );
check( 'first: a one-word first name is itself', $CD['first'] === 'Ruth' );

hd_arrange();
$GLOBALS['USER_META'][7] = array( 'first_name' => '   Ruth', 'last_name' => 'Adams' );
$CD = vance_hub_dashboard_member_card_data( hd_user( 7, 'ruth.a', '2024-03-05 10:00:00' ) );
check( 'first: leading spaces in the first name are ignored', $CD['first'] === 'Ruth' );

hd_arrange();
$GLOBALS['USER_META'][7] = array( 'first_name' => 'Zoë' );
$CD = vance_hub_dashboard_member_card_data( hd_user( 7, 'zoe.b', '2024-03-05 10:00:00' ) );
check( 'first: a non-ASCII first name keeps its letters', $CD['first'] === 'Zoë' );

hd_arrange();
$CD = vance_hub_dashboard_member_card_data( hd_user( 7, 'Dana Lee', '2024-03-05 10:00:00' ) );
check( 'first: no first name falls back to the first word of the display name', $CD['first'] === 'Dana' );
check( 'first: the display name is the full name in that case', $CD['name'] === 'Dana Lee' );

hd_arrange();
$GLOBALS['USER_META'][7] = array( 'first_name' => '   ' );
$CD = vance_hub_dashboard_member_card_data( hd_user( 7, 'Dana Lee', '2024-03-05 10:00:00' ) );
check( 'first: a first name of only spaces falls back to the display name', $CD['first'] === 'Dana' );

hd_arrange();
$CD = vance_hub_dashboard_member_card_data( hd_user( 7, '', '2024-03-05 10:00:00' ) );
check( 'first: no name at all gives an empty first word', $CD['first'] === '' );
check( 'first: no name at all gives an empty name', $CD['name'] === '' );

// Member card: the label.
$LB = vance_hub_dashboard_member_card_html( array( 'name' => 'Mary Ann Smith', 'first' => 'Mary', 'since' => '', 'code' => '' ) );
check( 'label: reads Welcome back with the first name', strpos( $LB, '<span class="vhdt-member__label">Welcome back Mary</span>' ) !== false );

$LE = vance_hub_dashboard_member_card_html( array( 'name' => 'Mary Ann Smith', 'first' => '', 'since' => '', 'code' => '' ) );
check( 'label: reads Welcome back alone when there is no first name', strpos( $LE, '<span class="vhdt-member__label">Welcome back</span>' ) !== false );
check( 'label: the old My Hub label is gone from the card', strpos( $LB, 'My Hub' ) === false && strpos( $LE, 'My Hub' ) === false );

$LX = vance_hub_dashboard_member_card_html( array( 'name' => 'Mary', 'first' => '<i>Mary</i>', 'since' => '', 'code' => '' ) );
check( 'label: a first name with markup is escaped', strpos( $LX, '<i>' ) === false && strpos( $LX, 'Welcome back &lt;i&gt;Mary&lt;/i&gt;' ) !== false );

check( 'card: no card at all when there is no name, even with a first name', vance_hub_dashboard_member_card_html( array( 'name' => '', 'first' => 'Mary', 'since' => '', 'code' => '' ) ) === '' );

// Render entry: the side-menu layout follows the tab look AND a name.
hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true, 'vance_hub_dashboard_tabs' => true ) );
$GLOBALS['USER_META'][7] = array( 'first_name' => 'Ruth', 'last_name' => 'Adams' );
$RH = hd_render( hd_user( 7, 'ruth.a', '2024-03-05 10:00:00' ) );
check( 'render: tab look on and a named member gives the side-menu heading', strpos( $RH, '<h1 class="vhd-sr">Hello, Ruth</h1>' ) !== false );
check( 'render: tab look on and a named member drops the greeting section', strpos( $RH, 'vhd-hello' ) === false );

hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true, 'vance_hub_dashboard_tabs' => false ) );
$GLOBALS['USER_META'][7] = array( 'first_name' => 'Ruth', 'last_name' => 'Adams' );
$RH2 = hd_render( hd_user( 7, 'ruth.a', '2024-03-05 10:00:00' ) );
check( 'render: tab look off keeps the classic greeting for a named member', strpos( $RH2, '<section class="vhd-hello"' ) !== false && strpos( $RH2, 'vhd-sr' ) === false );

hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true, 'vance_hub_dashboard_tabs' => true ) );
$RH3 = hd_render( hd_user( 8, '', '2024-03-05 10:00:00' ) );
check( 'render: tab look on but a member with no name keeps the classic greeting', strpos( $RH3, '<section class="vhd-hello"' ) !== false && strpos( $RH3, 'vhd-sr' ) === false );

hd_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true, 'vance_hub_dashboard_tabs' => true ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'classic' ) );
$GLOBALS['USER_META'][7] = array( 'first_name' => 'Ruth', 'last_name' => 'Adams' );
$RH4 = hd_render( hd_user( 7, 'ruth.a', '2024-03-05 10:00:00' ) );
check( 'render: an administrator classic preview keeps the classic greeting', strpos( $RH4, '<section class="vhd-hello"' ) !== false && strpos( $RH4, 'vhd-sr' ) === false );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . ' FAILED of ' . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK - $PASS checks passed.\n";
