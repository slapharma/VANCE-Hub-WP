<?php
/**
 * Behaviour tests for two parts of the VanceHubClub rollout:
 *
 *   A  the staged rollout: the Site layout value "members" (inc/hub-layout.php),
 *      the go-live change that turns "members" into "hub" (inc/hub-golive.php), the
 *      master line of the admin screen (inc/hub-admin.php), and the home page
 *      template choice that each viewer gets
 *   B  the "Reset this section" buttons: the section list (inc/hub-reset.php), the
 *      Customizer controls it registers, the data the script gets (every default,
 *      the health-data sections never), and the inline script's safety
 *
 * Same shape as hub-layout.test.php: the stub block of that suite is lifted and
 * evaluated (one set of WordPress fakes), then the extra fakes listed below. If the
 * block cannot be found, this suite exits 2.
 *
 * WHAT IS COVERED
 *   A1 the saved value and the logged-in flag through vance_hub_layout_for(): every
 *      saved value (classic, members, hub, MEMBERS, junk, null, array) against
 *      logged in true, false, 1 and 'yes'
 *   A2 the truth table through the real functions: saved classic, members, hub and
 *      junk, against a visitor, a member, an administrator with ?vhh_layout=hub,
 *      with =classic, with =members, and a visitor with ?vhh_layout=hub. Columns:
 *      the master switch, the layout, the article, the member dashboard, the header,
 *      the Community, the tool result and the check-in
 *   A3 a visitor never gets a hub part in "members"; the template choice for the
 *      home page; the check-in and the flare plan stay off unless their own switch
 *   A4 the go-live change with saved "members": wait, switch at the exact minute,
 *      the three writes in their order, and "hub" saved gives only a clear; the
 *      Customizer preview writes nothing
 *   A5 the admin master line for the three states and for junk
 *   B1 the section list is the registry minus the three excluded sections
 *   B2 the register: nine reset controls, priority 9999, none in an excluded section
 *   B3 the data: every setting of a reset section with its default and its type; no
 *      setting of an excluded section anywhere in the data; a control with no
 *      setting and a setting with no control; the inline script cannot break out of
 *      its tag
 *
 * A check that fails on the current code is a finding, not a broken check. The
 * runner lists every failure. Bugs found are reported, not fixed.
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

/* ---- stubs this suite adds ------------------------------------------------ */

$GLOBALS['IS_SINGULAR'] = true;
$GLOBALS['PREVIEW']     = false;
$GLOBALS['WRITES']      = array();
$GLOBALS['ACTIONS']     = array();
$GLOBALS['USERMETA']    = array();
$GLOBALS['INLINE']      = array();
$GLOBALS['wp_customize'] = null;

function is_singular( $type = '' ) { return (bool) $GLOBALS['IS_SINGULAR']; }
function is_customize_preview() { return (bool) $GLOBALS['PREVIEW']; }
function set_theme_mod( $key, $value ) {
	$GLOBALS['WRITES'][]     = array( 'set', $key );
	$GLOBALS['MODS'][ $key ] = $value;
}
function remove_theme_mod( $key ) {
	$GLOBALS['WRITES'][] = array( 'remove', $key );
	unset( $GLOBALS['MODS'][ $key ] );
}
function do_action( $tag, ...$args ) { $GLOBALS['ACTIONS'][] = $tag; }
function wp_json_encode( $value, $flags = 0, $depth = 512 ) { return json_encode( $value, $flags, $depth ); }
function wp_add_inline_script( $handle, $data, $position = 'after' ) { $GLOBALS['INLINE'][] = array( $handle, $data, $position ); return true; }
function get_user_meta( $user_id, $key = '', $single = false ) { return isset( $GLOBALS['USERMETA'][ $key ] ) ? $GLOBALS['USERMETA'][ $key ] : ''; }
function wp_salt( $scheme = 'auth' ) { return 'rollout-test-salt'; }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }
function esc_url_raw( $url, $protocols = null ) { return (string) $url; }
function is_search() { return false; }
function get_search_query() { return ''; }
function has_nav_menu( $location ) { return false; }
function wp_nav_menu( $args = array() ) { return ''; }
function is_page( $page = '' ) { return false; }
function is_page_template( $template = '' ) { return false; }
function vance_dashboard_feature_enabled( $slug ) { return true; }
function vance_dashboard_feature_label( $slug ) { return $slug; }
function vance_get_tool_history( $user_id, $tool_slug, $limit = 0 ) { return array(); }
function vance_admin_messages_for_user( $user_id, $include_read = false ) { return array(); }
function wp_get_attachment_image_src( $id, $size = 'thumbnail' ) { return false; }
class WP_User {
	public $ID              = 0;
	public $display_name    = '';
	public $user_registered = '';
	public function exists() { return $this->ID > 0; }
}
/** The base class of the reset button control (the real one is used when this exists). */
class WP_Customize_Control {
	public $id;
	public $section;
	public $settings = array();
	public $type;
	public $priority;
	public function __construct( $manager, $id, $args = array() ) {
		$this->id = $id;
		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}
	}
}

require_once $THEME . '/inc/hub-dashboard.php';
require_once $THEME . '/inc/hub-header.php';
require_once $THEME . '/inc/hub-community.php';
require_once $THEME . '/inc/hub-tool-result.php';
require_once $THEME . '/inc/hub-dashboard-features.php';
require_once $THEME . '/inc/hub-article.php';
require_once $THEME . '/inc/hub-golive.php';
require_once $THEME . '/inc/hub-admin.php';
require_once $THEME . '/inc/hub-reset.php';

/* ---- runner and helpers ----------------------------------------------------- */

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }
function has( $hay, $needle ) { return false !== strpos( (string) $hay, $needle ); }

/** Clean state for one case: no mods, no viewer, no query. */
function rollout_reset() {
	reset_state();
	$GLOBALS['IS_FRONT']    = false;
	$GLOBALS['IS_SINGULAR'] = true;
	$GLOBALS['PREVIEW']     = false;
	$GLOBALS['WRITES']      = array();
	$GLOBALS['ACTIONS']     = array();
	$GLOBALS['USERMETA']    = array();
	$GLOBALS['INLINE']      = array();
	$_GET                   = array();
	$GLOBALS['LOCATE_FAILS'] = false;
}

/** The saved layout, as the Customizer stores it. */
function saved_layout( $value ) {
	$GLOBALS['MODS']['vance_home_layout'] = $value;
}

/** A viewer: 'visitor', 'member', 'admin_hub', 'admin_classic', 'admin_members' or 'visitor_hub'. */
function become( $viewer ) {
	if ( 'visitor' === $viewer ) {
		$GLOBALS['LOGGED_IN'] = false;
	} elseif ( 'member' === $viewer ) {
		$GLOBALS['LOGGED_IN'] = true;
	} elseif ( 'visitor_hub' === $viewer ) {
		$GLOBALS['LOGGED_IN'] = false;
		$_GET = array( 'vhh_layout' => 'hub' );
	} else {
		$GLOBALS['LOGGED_IN'] = true;
		$GLOBALS['CAPS']['edit_theme_options'] = true;
		$GLOBALS['CAPS']['manage_options']     = true;
		if ( 'admin_hub' === $viewer ) {
			$_GET = array( 'vhh_layout' => 'hub' );
		} elseif ( 'admin_classic' === $viewer ) {
			$_GET = array( 'vhh_layout' => 'classic' );
		} else {
			$_GET = array( 'vhh_layout' => 'members' );
		}
	}
}

/** 'T' or 'F' for a boolean. */
function tf( $value ) { return $value ? 'T' : 'F'; }

/**
 * The eight answers for the current viewer and saved layout, in the order
 * master, layout, article, dashboard, header, community, tool result, check-in.
 */
function row_now() {
	return tf( vance_hub_master_on() )
		. tf( vance_hub_layout_active() )
		. tf( vance_hub_article_active() )
		. tf( vance_hub_dashboard_active() )
		. tf( vance_hub_header_active() )
		. tf( vance_hub_community_active() )
		. tf( vance_hub_tool_result_active() )
		. tf( vance_hub_dash_feature_on( 'vance_hub_dash_checkin' ) );
}

/** Customizer stand-in that records what the reset register adds. */
class ResetManagerFake {
	public $controls = array();
	public $settings = array();
	public $sections = array();
	public function add_control( $control, $args = null ) {
		if ( is_object( $control ) ) {
			$this->controls[ $control->id ] = $control;
		} else {
			$this->controls[ $control ] = (object) $args;
		}
	}
	public function get_section( $id ) { return null; }
	public function controls() { return array_values( $this->controls ); }
}

/* ===================================================================== */

section( 'A1. vance_hub_layout_for(): saved value against logged in' );

check( 'classic, logged in true: classic', 'classic' === vance_hub_layout_for( 'classic', true ) );
check( 'classic, logged in false: classic', 'classic' === vance_hub_layout_for( 'classic', false ) );
check( 'classic, logged in 1: classic', 'classic' === vance_hub_layout_for( 'classic', 1 ) );
check( 'classic, logged in yes: classic', 'classic' === vance_hub_layout_for( 'classic', 'yes' ) );
check( 'members, logged in true: hub', 'hub' === vance_hub_layout_for( 'members', true ) );
check( 'members, logged in false: classic (a visitor gets the classic layout)', 'classic' === vance_hub_layout_for( 'members', false ) );
check( 'members, logged in 1: classic (only the boolean true counts)', 'classic' === vance_hub_layout_for( 'members', 1 ) );
check( 'members, logged in yes: classic (only the boolean true counts)', 'classic' === vance_hub_layout_for( 'members', 'yes' ) );
check( 'hub, logged in true: hub', 'hub' === vance_hub_layout_for( 'hub', true ) );
check( 'hub, logged in false: hub', 'hub' === vance_hub_layout_for( 'hub', false ) );
check( 'hub, logged in 1: hub', 'hub' === vance_hub_layout_for( 'hub', 1 ) );
check( 'hub, logged in yes: hub', 'hub' === vance_hub_layout_for( 'hub', 'yes' ) );
check( 'MEMBERS (upper case) reaches layout_for unchanged: classic for a member, so the sanitiser must lower it first', 'classic' === vance_hub_layout_for( 'MEMBERS', true ) );
check( 'junk, logged in true: classic', 'classic' === vance_hub_layout_for( 'junk', true ) );
check( 'junk, logged in false: classic', 'classic' === vance_hub_layout_for( 'junk', false ) );
check( 'null, logged in true: classic', 'classic' === vance_hub_layout_for( null, true ) );
check( 'null, logged in false: classic', 'classic' === vance_hub_layout_for( null, false ) );
check( 'array, logged in true: classic', 'classic' === vance_hub_layout_for( array( 'members' ), true ) );
check( 'array, logged in false: classic', 'classic' === vance_hub_layout_for( array( 'hub' ), false ) );

check( 'sanitiser: members is accepted', 'members' === vance_hub_sanitize_layout( 'members' ) );
check( 'sanitiser: MEMBERS (upper case) is lowered to members', 'members' === vance_hub_sanitize_layout( 'MEMBERS' ) );
check( 'sanitiser: " Members " (spaces) is trimmed to members', 'members' === vance_hub_sanitize_layout( ' Members ' ) );
check( 'sanitiser: junk becomes classic', 'classic' === vance_hub_sanitize_layout( 'junk' ) );
check( 'sanitiser: null becomes classic', 'classic' === vance_hub_sanitize_layout( null ) );
check( 'sanitiser: an array becomes classic', 'classic' === vance_hub_sanitize_layout( array( 'hub' ) ) );
check( 'choices: classic, members and hub, in that order', array( 'classic', 'members', 'hub' ) === vance_hub_layout_choices() );
check( 'query layouts: only classic and hub (members is never forced by ?vhh_layout=)', array( 'classic', 'hub' ) === vance_hub_query_layouts() );

/* ===================================================================== */

section( 'A2. Truth table through the real functions' );

// Saved: classic.
rollout_reset(); saved_layout( 'classic' ); become( 'visitor' );
check( 'classic / visitor: master F, layout F, article F, dashboard F, header F, community F, tool F, check-in F: got ' . row_now(), 'FFFFFFFF' === row_now() );
rollout_reset(); saved_layout( 'classic' ); become( 'member' );
check( 'classic / member: all F: got ' . row_now(), 'FFFFFFFF' === row_now() );
rollout_reset(); saved_layout( 'classic' ); become( 'admin_hub' );
check( 'classic / admin with ?vhh_layout=hub: master F, every part T except check-in F: got ' . row_now(), 'FTTTTTTF' === row_now() );
rollout_reset(); saved_layout( 'classic' ); become( 'admin_classic' );
check( 'classic / admin with ?vhh_layout=classic: all F: got ' . row_now(), 'FFFFFFFF' === row_now() );
rollout_reset(); saved_layout( 'classic' ); become( 'admin_members' );
check( 'classic / admin with ?vhh_layout=members: all F, the query forces nothing: got ' . row_now(), 'FFFFFFFF' === row_now() );
rollout_reset(); saved_layout( 'classic' ); become( 'visitor_hub' );
check( 'classic / visitor with ?vhh_layout=hub: all F, a visitor query is ignored: got ' . row_now(), 'FFFFFFFF' === row_now() );

// Saved: members.
rollout_reset(); saved_layout( 'members' ); become( 'visitor' );
check( 'members / visitor: all F, a visitor NEVER gets a hub part: got ' . row_now(), 'FFFFFFFF' === row_now() );
rollout_reset(); saved_layout( 'members' ); become( 'member' );
check( 'members / member: every part T, check-in F: got ' . row_now(), 'TTTTTTTF' === row_now() );
rollout_reset(); saved_layout( 'members' ); become( 'admin_hub' );
check( 'members / admin with ?vhh_layout=hub: every part T, check-in F: got ' . row_now(), 'TTTTTTTF' === row_now() );
rollout_reset(); saved_layout( 'members' ); become( 'admin_classic' );
check( 'members / admin with ?vhh_layout=classic: master T (the admin is logged in), layout and parts F: got ' . row_now(), 'TFFFFFFF' === row_now() );
rollout_reset(); saved_layout( 'members' ); become( 'admin_members' );
check( 'members / admin with ?vhh_layout=members: the query is ignored, the admin gets hub: got ' . row_now(), 'TTTTTTTF' === row_now() );
rollout_reset(); saved_layout( 'members' ); become( 'visitor_hub' );
check( 'members / visitor with ?vhh_layout=hub: all F, the visitor query is ignored: got ' . row_now(), 'FFFFFFFF' === row_now() );

// Saved: hub.
rollout_reset(); saved_layout( 'hub' ); become( 'visitor' );
check( 'hub / visitor: every part T, check-in F: got ' . row_now(), 'TTTTTTTF' === row_now() );
rollout_reset(); saved_layout( 'hub' ); become( 'member' );
check( 'hub / member: every part T, check-in F: got ' . row_now(), 'TTTTTTTF' === row_now() );
rollout_reset(); saved_layout( 'hub' ); become( 'admin_hub' );
check( 'hub / admin with ?vhh_layout=hub: every part T, check-in F: got ' . row_now(), 'TTTTTTTF' === row_now() );
rollout_reset(); saved_layout( 'hub' ); become( 'admin_classic' );
check( 'hub / admin with ?vhh_layout=classic: master T, layout and parts F: got ' . row_now(), 'TFFFFFFF' === row_now() );
rollout_reset(); saved_layout( 'hub' ); become( 'admin_members' );
check( 'hub / admin with ?vhh_layout=members: every part T, check-in F: got ' . row_now(), 'TTTTTTTF' === row_now() );
rollout_reset(); saved_layout( 'hub' ); become( 'visitor_hub' );
check( 'hub / visitor with ?vhh_layout=hub: every part T, check-in F: got ' . row_now(), 'TTTTTTTF' === row_now() );

// Saved: junk and a value the sanitiser does not know. Same as classic.
rollout_reset(); saved_layout( 'junk' ); become( 'visitor' );
check( 'junk / visitor: all F: got ' . row_now(), 'FFFFFFFF' === row_now() );
rollout_reset(); saved_layout( 'junk' ); become( 'member' );
check( 'junk / member: all F: got ' . row_now(), 'FFFFFFFF' === row_now() );
rollout_reset(); saved_layout( 'junk' ); become( 'admin_hub' );
check( 'junk / admin with ?vhh_layout=hub: master F, every part T, check-in F: got ' . row_now(), 'FTTTTTTF' === row_now() );
rollout_reset(); saved_layout( 'MEMBERS' ); become( 'member' );
check( 'MEMBERS saved / member: sanitised to members, every part T: got ' . row_now(), 'TTTTTTTF' === row_now() );
rollout_reset(); saved_layout( 'MEMBERS' ); become( 'visitor' );
check( 'MEMBERS saved / visitor: all F: got ' . row_now(), 'FFFFFFFF' === row_now() );

/* ===================================================================== */

section( 'A3. Members: the check-in and the flare plan, and the home page template' );

rollout_reset(); saved_layout( 'members' ); $GLOBALS['MODS']['vance_hub_dash_checkin'] = true; become( 'member' );
check( 'members / member with the check-in switched on: the check-in is on', true === vance_hub_dash_feature_on( 'vance_hub_dash_checkin' ) );
rollout_reset(); saved_layout( 'members' ); $GLOBALS['MODS']['vance_hub_dash_checkin'] = true; become( 'visitor' );
check( 'members / visitor with the check-in switched on: the check-in stays off for a visitor', false === vance_hub_dash_feature_on( 'vance_hub_dash_checkin' ) );
rollout_reset(); saved_layout( 'members' ); become( 'member' );
check( 'members / member with nothing saved: the check-in is off (its own default is off)', false === vance_hub_dash_feature_on( 'vance_hub_dash_checkin' ) );
rollout_reset(); saved_layout( 'members' ); $GLOBALS['MODS']['vance_hub_dash_flare_plan'] = true; become( 'member' );
check( 'members / member with the flare plan switched on: the flare plan switch is on', true === vance_hub_dash_feature_on( 'vance_hub_dash_flare_plan' ) );
rollout_reset(); saved_layout( 'members' ); become( 'member' );
check( 'members / member with nothing saved: the flare plan switch is off', false === vance_hub_dash_feature_on( 'vance_hub_dash_flare_plan' ) );
rollout_reset(); saved_layout( 'members' ); $GLOBALS['MODS']['vance_hub_dash_flare_plan'] = true; become( 'visitor' );
check( 'members / visitor with the flare plan switched on: the flare plan stays off for a visitor', false === vance_hub_dash_feature_on( 'vance_hub_dash_flare_plan' ) );

$TPL = $THEME . '/front-page.php';
rollout_reset(); saved_layout( 'members' ); $GLOBALS['IS_FRONT'] = true; become( 'visitor' );
check( 'home / members / visitor: the classic template is kept (not the hub one)', $TPL === vance_hub_template_include( $TPL ) );
rollout_reset(); saved_layout( 'members' ); $GLOBALS['IS_FRONT'] = true; become( 'member' );
check( 'home / members / member: the hub template is chosen', $THEME . '/front-page-hub.php' === vance_hub_template_include( $TPL ) );
rollout_reset(); saved_layout( 'hub' ); $GLOBALS['IS_FRONT'] = true; become( 'member' );
check( 'home / hub / member: the hub template is chosen, the same as members / member', $THEME . '/front-page-hub.php' === vance_hub_template_include( $TPL ) );
rollout_reset(); saved_layout( 'hub' ); $GLOBALS['IS_FRONT'] = true; become( 'visitor' );
check( 'home / hub / visitor: the hub template is chosen', $THEME . '/front-page-hub.php' === vance_hub_template_include( $TPL ) );
rollout_reset(); saved_layout( 'members' ); $GLOBALS['IS_FRONT'] = false; become( 'member' );
check( 'home / members / member on a page that is not the front page: the template is unchanged', $TPL === vance_hub_template_include( $TPL ) );

/* ===================================================================== */

section( 'A4. The go-live change with saved "members"' );

$TZ    = new DateTimeZone( 'Europe/London' );
$GO    = new DateTimeImmutable( '2026-12-01 09:00:00', $TZ );
$GO_TS = $GO->getTimestamp();
check( 'go-live: a minute before the time gives wait', 'wait' === vance_hub_golive_decide( '2026-12-01T09:00', $GO_TS - 60, $TZ, 'members' ) );
check( 'go-live: one second before the time gives wait', 'wait' === vance_hub_golive_decide( '2026-12-01T09:00', $GO_TS - 1, $TZ, 'members' ) );
check( 'go-live: at the exact minute gives switch', 'switch' === vance_hub_golive_decide( '2026-12-01T09:00', $GO_TS, $TZ, 'members' ) );
check( 'go-live: after the time gives switch', 'switch' === vance_hub_golive_decide( '2026-12-01T09:00', $GO_TS + 3600, $TZ, 'members' ) );
check( 'go-live: with hub saved, the time gives clear (nothing to switch)', 'clear' === vance_hub_golive_decide( '2026-12-01T09:00', $GO_TS, $TZ, 'hub' ) );
check( 'go-live: with hub saved, before the time also gives clear', 'clear' === vance_hub_golive_decide( '2026-12-01T09:00', $GO_TS - 60, $TZ, 'hub' ) );
check( 'go-live: no date gives none', 'none' === vance_hub_golive_decide( '', $GO_TS, $TZ, 'members' ) );

rollout_reset(); saved_layout( 'members' ); $GLOBALS['MODS']['vance_hub_golive_at'] = '2000-01-01T00:00';
$RUN = vance_hub_golive_run();
check( 'run: a past time with members saved returns switch', 'switch' === $RUN );
check( 'run: the first write sets the layout to hub', array( 'set', 'vance_home_layout' ) === $GLOBALS['WRITES'][0] );
check( 'run: the second write removes the go-live date', array( 'remove', 'vance_hub_golive_at' ) === $GLOBALS['WRITES'][1] );
check( 'run: the third write sets the done time', array( 'set', 'vance_hub_golive_done_at' ) === $GLOBALS['WRITES'][2] );
check( 'run: exactly three writes', 3 === count( $GLOBALS['WRITES'] ) );
check( 'run: the saved layout is hub afterwards', 'hub' === vance_hub_saved_layout() );
check( 'run: the date is gone afterwards', '' === vance_get_theme_mod( 'vance_hub_golive_at', '' ) );
check( 'run: the LiteSpeed purge runs before the went-live action', array( 'litespeed_purge_all', 'vance_hub_went_live' ) === $GLOBALS['ACTIONS'] );

rollout_reset(); saved_layout( 'members' ); $GLOBALS['MODS']['vance_hub_golive_at'] = '2099-01-01T00:00';
check( 'run: a future time with members saved returns wait and writes nothing', 'wait' === vance_hub_golive_run() && array() === $GLOBALS['WRITES'] );
check( 'run: a future time keeps members saved', 'members' === vance_hub_saved_layout() );

rollout_reset(); saved_layout( 'hub' ); $GLOBALS['MODS']['vance_hub_golive_at'] = '2000-01-01T00:00';
check( 'run: a past time with hub saved returns clear and only removes the date', 'clear' === vance_hub_golive_run() && array( array( 'remove', 'vance_hub_golive_at' ) ) === $GLOBALS['WRITES'] );
check( 'run: with hub saved no purge and no action run', array() === $GLOBALS['ACTIONS'] );

rollout_reset(); saved_layout( 'members' ); $GLOBALS['MODS']['vance_hub_golive_at'] = '2000-01-01T00:00'; $GLOBALS['PREVIEW'] = true;
check( 'run: in the Customizer preview a past time writes nothing', 'none' === vance_hub_golive_run() && array() === $GLOBALS['WRITES'] );
check( 'run: in the Customizer preview members stays saved', 'members' === vance_hub_saved_layout() );

rollout_reset(); saved_layout( 'members' );
check( 'run: no date saved returns none and writes nothing', 'none' === vance_hub_golive_run() && array() === $GLOBALS['WRITES'] );

/* ===================================================================== */

section( 'A5. The admin master line' );

check( 'master line: hub reads VanceHubClub: on', 'VanceHubClub: on' === vance_hub_admin_master_text( 'hub' ) );
check( 'master line: members reads the members-only text', 'VanceHubClub: on for logged-in members only' === vance_hub_admin_master_text( 'members' ) );
check( 'master line: classic reads the classic text', 'VanceHealthHub (classic): every VanceHubClub part is off' === vance_hub_admin_master_text( 'classic' ) );
check( 'master line: junk reads the classic text', 'VanceHealthHub (classic): every VanceHubClub part is off' === vance_hub_admin_master_text( 'junk' ) );
check( 'master note: only members has a note', '' !== vance_hub_admin_master_note( 'members' ) && '' === vance_hub_admin_master_note( 'hub' ) && '' === vance_hub_admin_master_note( 'classic' ) );
check( 'master note: the note says visitors see the classic layout', has( vance_hub_admin_master_note( 'members' ), 'Visitors see the classic layout.' ) );
rollout_reset(); saved_layout( 'MEMBERS' );
check( 'master line: the screen reads the saved layout through the sanitiser (MEMBERS gives the members text)', 'VanceHubClub: on for logged-in members only' === vance_hub_admin_master_text( vance_hub_saved_layout() ) );

/* ===================================================================== */

section( 'B1. The section list' );

$EXCLUDED = array( 'vance_home_layout', 'vance_hub_sec_checkin', 'vance_hub_sec_flare' );
check( 'excluded: exactly the On or off section and the two health-data sections', $EXCLUDED === vance_hub_reset_excluded() );
$SECTIONS = array(
	'vance_hub_sec_home',
	'vance_hub_sec_home_text',
	'vance_hub_sec_home_tools',
	'vance_hub_sec_home_tiles',
	'vance_hub_sec_header',
	'vance_hub_sec_pages',
	'vance_hub_sec_dashboard',
	'vance_hub_sec_dashboard_text',
	'vance_hub_sec_community',
);
check( 'sections: the reset list is the nine other sections, in registry order', $SECTIONS === vance_hub_reset_sections() );
check( 'sections: nine buttons, not twelve', 9 === count( vance_hub_reset_sections() ) );
check( 'sections: the On or off section has no button', ! in_array( 'vance_home_layout', vance_hub_reset_sections(), true ) );
check( 'sections: the check-in section has no button', ! in_array( 'vance_hub_sec_checkin', vance_hub_reset_sections(), true ) );
check( 'sections: the flare plan section has no button', ! in_array( 'vance_hub_sec_flare', vance_hub_reset_sections(), true ) );
check( 'sections: the registry has twelve sections', 12 === count( vance_hub_customizer_sections() ) );
check( 'control id: the reset button of a section is named after it', 'vance_hub_reset_vance_hub_sec_home' === vance_hub_reset_control_id( 'vance_hub_sec_home' ) );

/* ===================================================================== */

section( 'B2. The register: nine reset controls' );

$RM = new ResetManagerFake();
vance_hub_reset_customize_register( $RM );
check( 'register: nine reset controls are added', 9 === count( $RM->controls ) );
check( 'register: the reset control is the reset control class', isset( $RM->controls['vance_hub_reset_vance_hub_sec_home'] ) && $RM->controls['vance_hub_reset_vance_hub_sec_home'] instanceof Vance_Customize_Hub_Reset_Control );
check( 'register: each button is in its own section (home)', 'vance_hub_sec_home' === $RM->controls['vance_hub_reset_vance_hub_sec_home']->section );
check( 'register: each button is in its own section (community)', 'vance_hub_sec_community' === $RM->controls['vance_hub_reset_vance_hub_sec_community']->section );
check( 'register: each button sits last (priority 9999)', 9999 === $RM->controls['vance_hub_reset_vance_hub_sec_home']->priority && 9999 === $RM->controls['vance_hub_reset_vance_hub_sec_dashboard_text']->priority );
check( 'register: no button in the On or off section', ! isset( $RM->controls['vance_hub_reset_vance_home_layout'] ) );
check( 'register: no button in the check-in section', ! isset( $RM->controls['vance_hub_reset_vance_hub_sec_checkin'] ) );
check( 'register: no button in the flare plan section', ! isset( $RM->controls['vance_hub_reset_vance_hub_sec_flare'] ) );
check( 'register: each button has no setting of its own', array() === $RM->controls['vance_hub_reset_vance_hub_sec_home']->settings );

/* ===================================================================== */

section( 'B3. The data the script gets' );

/** A setting as the Customizer holds it: an id and a registered default. */
function fake_setting( $id, $default ) {
	return (object) array( 'id' => $id, 'default' => $default );
}
/** A control of a section with the settings it owns, keyed as the Customizer keys them. */
function fake_control( $id, $section, array $settings ) {
	$map = array();
	foreach ( $settings as $setting ) {
		$map[ $setting->id ] = $setting;
	}
	return (object) array( 'id' => $id, 'section' => $section, 'settings' => $map );
}

$HOSTILE = '</script><script>alert("x")</script> & \'q\' \\ end';
$CONTROLS = array(
	fake_control( 'c_home_order', 'vance_hub_sec_home', array( fake_setting( 'vance_hub_block_order', 'hero,recipes,articles' ) ) ),
	fake_control( 'c_home_bool', 'vance_hub_sec_home', array( fake_setting( 'vance_hub_show_recipes', true ), fake_setting( 'vance_hub_count_articles', 3 ) ) ),
	fake_control( 'c_comm', 'vance_hub_sec_community', array( fake_setting( 'vance_hub_community_poll', true ), fake_setting( 'vance_hub_text_com_visitor_intro', $HOSTILE ) ) ),
	fake_control( 'c_header', 'vance_hub_sec_header', array( fake_setting( 'vance_hub_brand_primary', '#008080' ) ) ),
	// A control with no setting: it must add nothing.
	fake_control( 'c_empty', 'vance_hub_sec_home_tiles', array() ),
	// The excluded sections, with the health-data settings and the golive date.
	fake_control( 'c_layout', 'vance_home_layout', array( fake_setting( 'vance_home_layout', 'classic' ), fake_setting( 'vance_hub_golive_at', '' ), fake_setting( 'vance_hub_flare_plan_urgent', '' ) ) ),
	fake_control( 'c_checkin', 'vance_hub_sec_checkin', array( fake_setting( 'vance_hub_dash_checkin', false ), fake_setting( 'vance_hub_checkin_consent', '1' ) ) ),
	fake_control( 'c_flare', 'vance_hub_sec_flare', array( fake_setting( 'vance_hub_dash_flare_plan', false ), fake_setting( 'vance_hub_flare_plan_consent', '1' ) ) ),
);
$SETTING_NOCONTROL = fake_setting( 'vance_hub_orphan_setting', 'orphan' );

$FOUND = vance_hub_reset_collect( $CONTROLS );
$MAP   = vance_hub_reset_map( vance_hub_reset_sections(), $FOUND );

check( 'map: every setting of a reset section is in the map with its default', 'hero,recipes,articles' === $MAP['vance_hub_sec_home']['vance_hub_block_order'] );
check( 'map: a boolean default stays a boolean', true === $MAP['vance_hub_sec_home']['vance_hub_show_recipes'] );
check( 'map: an integer default stays an integer', 3 === $MAP['vance_hub_sec_home']['vance_hub_count_articles'] );
check( 'map: a community default that is a hostile string is kept as it is', $HOSTILE === $MAP['vance_hub_sec_community']['vance_hub_text_com_visitor_intro'] );
check( 'map: a colour default is kept', '#008080' === $MAP['vance_hub_sec_header']['vance_hub_brand_primary'] );
check( 'map: a control with no setting adds no section', ! isset( $MAP['vance_hub_sec_home_tiles'] ) );
check( 'map: a section that is not in the reset list is not in the map', ! isset( $MAP['vance_home_layout'] ) );

$DATA_TEXT = wp_json_encode( vance_hub_reset_script_data( $MAP, array( 'vance_hub_sec_home' => 'Home page: blocks and counts' ) ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
$DATA      = json_decode( $DATA_TEXT );
check( 'data: the encoded map decodes back to the same boolean default', true === $DATA->sections->vance_hub_sec_home->defaults->vance_hub_show_recipes );
check( 'data: the encoded map keeps the integer default as an integer', is_int( $DATA->sections->vance_hub_sec_home->defaults->vance_hub_count_articles ) && 3 === $DATA->sections->vance_hub_sec_home->defaults->vance_hub_count_articles );
check( 'data: the encoded map keeps the string default as a string', is_string( $DATA->sections->vance_hub_sec_home->defaults->vance_hub_block_order ) );
check( 'data: with no title given, a section is titled by its id (the enqueue path passes the registry titles)', 'vance_hub_sec_community' === $DATA->sections->vance_hub_sec_community->title );
check( 'data: the given title is used for its section', 'Home page: blocks and counts' === $DATA->sections->vance_hub_sec_home->title );
check( 'data: the hostile default round-trips exactly through the encoding', $HOSTILE === $DATA->sections->vance_hub_sec_community->defaults->vance_hub_text_com_visitor_intro );
check( 'data: the encoding has no raw closing script tag', ! has( $DATA_TEXT, '</script' ) );
check( 'data: the encoding has no raw angle bracket at all', ! has( $DATA_TEXT, '<' ) && ! has( $DATA_TEXT, '>' ) );
check( 'data: the encoding has no raw ampersand or apostrophe', ! has( $DATA_TEXT, '&' ) && ! has( $DATA_TEXT, "'" ) );
check( 'data: a setting with no control is not in the data', ! has( $DATA_TEXT, 'vance_hub_orphan_setting' ) && ! has( $DATA_TEXT, $SETTING_NOCONTROL->id ) );
check( 'data: the confirm text has the %s placeholder for the title', has( $DATA->confirm, '%s' ) );

// The excluded sections never appear: their ids are not anywhere in the data.
check( 'excluded data: vance_home_layout is not in the data', ! has( $DATA_TEXT, 'vance_home_layout' ) );
check( 'excluded data: vance_hub_golive_at is not in the data', ! has( $DATA_TEXT, 'vance_hub_golive_at' ) );
check( 'excluded data: vance_hub_dash_checkin is not in the data', ! has( $DATA_TEXT, 'vance_hub_dash_checkin' ) );
check( 'excluded data: vance_hub_checkin_consent is not in the data', ! has( $DATA_TEXT, 'vance_hub_checkin_consent' ) );
check( 'excluded data: vance_hub_dash_flare_plan is not in the data', ! has( $DATA_TEXT, 'vance_hub_dash_flare_plan' ) );
check( 'excluded data: vance_hub_flare_plan_consent is not in the data', ! has( $DATA_TEXT, 'vance_hub_flare_plan_consent' ) );
check( 'excluded data: vance_hub_flare_plan_urgent is not in the data', ! has( $DATA_TEXT, 'vance_hub_flare_plan_urgent' ) );

// The enqueue path, with the fake manager: the inline script it prints.
$RM2 = new ResetManagerFake();
foreach ( $CONTROLS as $c ) {
	$RM2->controls[ $c->id ] = $c;
}
$GLOBALS['wp_customize'] = $RM2;
rollout_reset();
$GLOBALS['wp_customize'] = $RM2;
vance_hub_reset_enqueue();
check( 'enqueue: the inline script is added once, before the script', 1 === count( $GLOBALS['INLINE'] ) && 'before' === $GLOBALS['INLINE'][0][2] );
$INLINE = isset( $GLOBALS['INLINE'][0][1] ) ? $GLOBALS['INLINE'][0][1] : '';
check( 'enqueue: the inline script assigns window.vanceHubReset', has( $INLINE, 'window.vanceHubReset = ' ) && has( $INLINE, ';' ) );
check( 'enqueue: the inline script cannot break out of its tag', ! has( $INLINE, '</script' ) && ! has( $INLINE, '<' ) );
check( 'enqueue: no excluded id is in the inline script (golive date)', ! has( $INLINE, 'vance_hub_golive_at' ) );
check( 'enqueue: no excluded id is in the inline script (check-in switch)', ! has( $INLINE, 'vance_hub_dash_checkin' ) );
check( 'enqueue: no excluded id is in the inline script (flare plan urgent text)', ! has( $INLINE, 'vance_hub_flare_plan_urgent' ) );
check( 'enqueue: no excluded id is in the inline script (On or off setting)', ! has( $INLINE, '"vance_home_layout"' ) );
check( 'enqueue: the reset section defaults are in the inline script', has( $INLINE, 'vance_hub_block_order' ) && has( $INLINE, 'vance_hub_text_com_visitor_intro' ) );
$JSON = substr( $INLINE, strlen( 'window.vanceHubReset = ' ), -1 );
$PARSED = json_decode( $JSON );
check( 'enqueue: the inline script payload is valid JSON', is_object( $PARSED ) && isset( $PARSED->sections ) );
check( 'enqueue: the payload default of the hostile setting is the exact string', is_object( $PARSED ) && $HOSTILE === $PARSED->sections->vance_hub_sec_community->defaults->vance_hub_text_com_visitor_intro );

/* ===================================================================== */

section( 'B4. The inline script when the encoding fails' );

// Valid data: one assignment of a JSON object.
$OK_SCRIPT = vance_hub_reset_inline_script( vance_hub_reset_script_data( $MAP, array() ) );
$OK_JSON   = substr( $OK_SCRIPT, strlen( 'window.vanceHubReset = ' ), -1 );
check( 'inline: valid data gives one assignment that ends with a semicolon', 0 === strpos( $OK_SCRIPT, 'window.vanceHubReset = {' ) && ';' === substr( $OK_SCRIPT, -1 ) );
check( 'inline: valid data assigns a JSON object with the sections', is_object( json_decode( $OK_JSON ) ) && isset( json_decode( $OK_JSON )->sections->vance_hub_sec_home ) );

// A default that holds an invalid UTF-8 byte: json_encode returns false for it.
$BAD_BYTES = 'caf' . chr( 0xC3 ) . chr( 0x28 );
check( 'inline: the bad string really makes the encoder fail (the check is not vacuous)', false === wp_json_encode( array( 'x' => $BAD_BYTES ), JSON_HEX_TAG ) );
$BAD_CONTROLS = $CONTROLS;
$BAD_CONTROLS[] = fake_control( 'c_bad', 'vance_hub_sec_home', array( fake_setting( 'vance_hub_bad_text', $BAD_BYTES ) ) );
$BAD_MAP    = vance_hub_reset_map( vance_hub_reset_sections(), vance_hub_reset_collect( $BAD_CONTROLS ) );
$BAD_DATA   = vance_hub_reset_script_data( $BAD_MAP, array() );
check( 'inline: the bad data does not encode (so the guard is what is tested)', false === wp_json_encode( $BAD_DATA, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) );
$BAD_SCRIPT = vance_hub_reset_inline_script( $BAD_DATA );
$BAD_JSON   = substr( $BAD_SCRIPT, strlen( 'window.vanceHubReset = ' ), -1 );
check( 'inline: bad data still gives a complete assignment', 0 === strpos( $BAD_SCRIPT, 'window.vanceHubReset = {' ) && ';' === substr( $BAD_SCRIPT, -1 ) );
check( 'inline: bad data never prints an empty assignment', ! has( $BAD_SCRIPT, '= ;' ) && ! has( $BAD_SCRIPT, '=;' ) );
check( 'inline: bad data prints JSON that decodes to an object', is_object( json_decode( $BAD_JSON ) ) );
$BAD_OBJ = json_decode( $BAD_JSON );
check( 'inline: bad data prints no sections, an empty object', is_object( $BAD_OBJ ) && isset( $BAD_OBJ->sections ) && is_object( $BAD_OBJ->sections ) && 0 === count( (array) $BAD_OBJ->sections ) && has( $BAD_JSON, '"sections":{}' ) );
check( 'inline: bad data keeps the notice text for the script', has( $BAD_JSON, 'cannot be reset here' ) );
check( 'inline: bad data holds no setting id of the bad section', ! has( $BAD_SCRIPT, 'vance_hub_bad_text' ) && ! has( $BAD_SCRIPT, 'vance_hub_block_order' ) );
foreach ( array( 'vance_home_layout', 'vance_hub_golive_at', 'vance_hub_dash_checkin', 'vance_hub_checkin_consent', 'vance_hub_dash_flare_plan', 'vance_hub_flare_plan_consent', 'vance_hub_flare_plan_urgent' ) as $EXCLUDED_ID ) {
	check( "inline: bad data holds no excluded setting id ($EXCLUDED_ID)", ! has( $BAD_SCRIPT, $EXCLUDED_ID ) );
}
check( 'inline: the unavailable text is in the normal data too', has( $OK_JSON, 'cannot be reset here' ) );

echo "\n$PASS passed, " . count( $FAIL ) . " failed\n";
foreach ( $FAIL as $f ) {
	echo "FAIL: $f\n";
}
exit( $FAIL ? 1 : 0 );
