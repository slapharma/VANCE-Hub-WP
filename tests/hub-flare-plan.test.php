<?php
/**
 * Tests for the flare plan card of the VanceHubClub member dashboard:
 * inc/hub-flare-plan.php, and the three places it shares code with the rest of
 * the dashboard: the $base parameter of the agreement helpers in
 * inc/hub-dashboard-features.php, the default-off switch in inc/hub-layout.php,
 * and the Health Profile tab in page-dashboard.php (read as text) that hosts the card.
 *
 * Same shape as hub-dashboard-features.test.php. The stub block of hub-layout.test.php
 * is evaluated first, so both suites share one set of WordPress fakes. The stubs
 * added here are for the storage and request boundary only: user meta, the nonces,
 * redirects, the database lock and the user lookup by email. The plan is read and
 * written through user meta, so the checks inspect $GLOBALS['UMETA'] and
 * $GLOBALS['WRITES'] directly.
 *
 * WHAT IS COVERED
 *   1  pure helpers: keys, length limit
 *   2  clean(): what a posted answer set becomes
 *   3  stored plan: read back and checked
 *   4  write(): store, read back, backslashes, failures
 *   5  POST handler: gating, expired nonce, unknown action
 *   6  POST handler, save: consent, stale wording, urgent text, validation, order,
 *      rollback of the agreement, edit, the database lock, the agreement read after the lock
 *   7  POST handler, delete: lock, withdrawal, read-back failures
 *   8  check-in untouched, in both directions
 *   9  wiring: default off, not in vance_known_tool_slugs(), data() gating and shape
 *  10  render: escaping, line breaks, states, print button, consent box, flash
 *  11  privacy exporter and eraser
 *  12  the $base parameter of the agreement helpers
 *
 * A check whose label says what should happen and which fails on the current code is a finding,
 * not a broken check. The runner lists every failure and exits 1.
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

// Lift the slug list from the real dashboard-functions.php, so the wiring check
// reads the source rather than a copy of it.
$DASH_FN = file_get_contents( $THEME . '/inc/dashboard-functions.php' );
preg_match( '/function vance_known_tool_slugs\(\) \{.*?\r?\n\}\r?\n/s', $DASH_FN, $LIFT_SLUGS );
if ( empty( $LIFT_SLUGS[0] ) ) {
	fwrite( STDERR, "could not lift vance_known_tool_slugs() from dashboard-functions.php\n" );
	exit( 2 );
}
eval( $LIFT_SLUGS[0] );

/* ---- stubs this suite adds ------------------------------------------------- */

// Thrown by the redirect stub, so the handler's exit() is never reached.
class RedirectStop extends Exception {
	public $url;
	public function __construct( $url ) {
		parent::__construct( 'redirect' );
		$this->url = $url;
	}
}

function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
function sanitize_text_field( $text ) { return trim( strip_tags( (string) $text ) ); }
function current_time( $type ) { return '2026-11-18 12:00:00'; }
function get_current_user_id() { return (int) $GLOBALS['CURRENT_UID']; }
function wp_create_nonce( $action ) { return 'nonce-' . $action; }
function wp_verify_nonce( $nonce, $action ) { return $nonce === 'nonce-' . $action ? 1 : false; }
function remove_query_arg( $key, $url = false ) {
	return preg_replace( '/[?&]vance_flare=[^&]*/', '', $GLOBALS['CURRENT_URL'] );
}
function wp_safe_redirect( $url ) { throw new RedirectStop( $url ); }

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	// Mirrors WordPress for this use: a script or style block goes with its content,
	// other tags are removed, and line breaks are kept.
	function sanitize_textarea_field( $text ) {
		$text = preg_replace( '@<(script|style)\b[^>]*>.*?</\1\s*>@is', '', (string) $text );
		return trim( strip_tags( $text ) );
	}
}
if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
}
if ( ! function_exists( 'get_user_by' ) ) {
	// Only the 'email' lookup is used. Users come from $GLOBALS['USERS_BY_EMAIL'].
	function get_user_by( $field, $value ) {
		return isset( $GLOBALS['USERS_BY_EMAIL'][ $value ] ) ? $GLOBALS['USERS_BY_EMAIL'][ $value ] : false;
	}
}

// As WordPress does: a read of a key with no value gives ''.
function get_user_meta( $user_id, $key = '', $single = false ) {
	$GLOBALS['SEQ'][] = 'read:' . $key;
	// STALE: the copy of the member's meta that WordPress loads at login. Reads
	// come from it until wp_cache_delete() or a write of this request drops it.
	if ( isset( $GLOBALS['STALE'][ $user_id ] ) ) {
		return isset( $GLOBALS['STALE'][ $user_id ][ $key ] ) ? $GLOBALS['STALE'][ $user_id ][ $key ] : '';
	}
	return isset( $GLOBALS['UMETA'][ $user_id ][ $key ] ) ? $GLOBALS['UMETA'][ $user_id ][ $key ] : '';
}
function wp_cache_delete( $id, $group = '' ) {
	$GLOBALS['SEQ'][] = 'cache_delete:' . $group;
	if ( 'user_meta' === $group ) {
		unset( $GLOBALS['STALE'][ $id ] );
	}
	return true;
}
// The database lock. LOCK_RESULT is what GET_LOCK() returns: '1' given, '0' not
// given in time, null error. ON_LOCK runs when the lock is given: it stands for
// what another request of this member wrote while this one waited.
class FbWpdb {
	public $prefix = 'wp_';
	public function prepare( $query, ...$args ) {
		return vsprintf( str_replace( '%s', "'%s'", $query ), $args );
	}
	public function get_var( $sql ) {
		if ( 0 === strpos( $sql, 'SELECT GET_LOCK(' ) ) {
			$GLOBALS['SEQ'][]      = 'lock';
			$GLOBALS['LOCK_SQL'][] = $sql;
			if ( '1' === $GLOBALS['LOCK_RESULT'] && is_callable( $GLOBALS['ON_LOCK'] ) ) {
				call_user_func( $GLOBALS['ON_LOCK'] );
			}
			return $GLOBALS['LOCK_RESULT'];
		}
		if ( 0 === strpos( $sql, 'SELECT RELEASE_LOCK(' ) ) {
			$GLOBALS['SEQ'][]        = 'unlock';
			$GLOBALS['UNLOCK_SQL'][] = $sql;
			return '1';
		}
		return null;
	}
}
$GLOBALS['wpdb'] = new FbWpdb();
// The site secret. Only the lock name uses it.
function wp_salt( $scheme = 'auth' ) { return 'test-salt-one'; }
// UPDATE_FAILS: true fails every write, a key name fails that key only.
// As WordPress does: one level of slashes is removed from a stored string.
function update_user_meta( $user_id, $key, $value, $prev = '' ) {
	$GLOBALS['WRITES'][] = 'update_user_meta:' . $key;
	$GLOBALS['SEQ'][]    = 'write:' . $key;
	unset( $GLOBALS['STALE'][ $user_id ] );
	if ( true === $GLOBALS['UPDATE_FAILS'] || $key === $GLOBALS['UPDATE_FAILS'] ) {
		return false;
	}
	$GLOBALS['UMETA'][ $user_id ][ $key ] = is_string( $value ) ? stripslashes( $value ) : $value;
	return true;
}
if ( ! function_exists( 'wp_slash' ) ) {
	function wp_slash( $value ) { return is_string( $value ) ? addslashes( $value ) : $value; }
}
// DELETE_FAILS: a key name whose delete fails and removes nothing.
function delete_user_meta( $user_id, $key, $value = '' ) {
	$GLOBALS['WRITES'][] = 'delete_user_meta:' . $key;
	$GLOBALS['SEQ'][]    = 'write:' . $key;
	unset( $GLOBALS['STALE'][ $user_id ] );
	if ( isset( $GLOBALS['DELETE_FAILS'] ) && $key === $GLOBALS['DELETE_FAILS'] ) {
		return false;
	}
	unset( $GLOBALS['UMETA'][ $user_id ][ $key ] );
	return true;
}

// The assertion helpers. hub-layout.test.php defines the same pair after the stub block.
$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

require_once $THEME . '/inc/hub-dashboard.php';
require_once $THEME . '/inc/hub-dashboard-features.php';
require_once $THEME . '/inc/hub-flare-plan.php';

/* ---- helpers ------------------------------------------------------------------ */

$NOW    = time();
$OLD_TS = strtotime( '2026-11-10 12:00:00 UTC' );
$FP_ON  = array(
	'vance_home_layout'            => 'hub',
	'vance_hub_dash_flare_plan'    => true,
	'vance_hub_flare_plan_urgent'  => 'Call the clinic on 0800 000 000 if you have blood in your stool.',
);

/** Reset every global this suite reads. */
function fb_arrange( array $mods = array(), $logged_in = true, $uid = 7 ) {
	$GLOBALS['MODS']           = $mods;
	$GLOBALS['CAPS']           = array();
	$GLOBALS['LOGGED_IN']      = $logged_in;
	$GLOBALS['CURRENT_UID']    = $uid;
	$GLOBALS['CURRENT_URL']    = 'https://example.test/hub/';
	$GLOBALS['UMETA']          = array();
	$GLOBALS['WRITES']         = array();
	$GLOBALS['UPDATE_FAILS']   = false;
	$GLOBALS['DELETE_FAILS']   = '';
	$GLOBALS['SEQ']            = array();
	$GLOBALS['STALE']          = array();
	$GLOBALS['LOCK_RESULT']    = '1';
	$GLOBALS['ON_LOCK']        = null;
	$GLOBALS['LOCK_SQL']       = array();
	$GLOBALS['UNLOCK_SQL']     = array();
	$GLOBALS['USERS_BY_EMAIL'] = array();
	$_GET                      = array();
	$_POST                     = array();
	$_SERVER['REQUEST_METHOD'] = 'POST';
}

/** A valid save: three answers, and the consent box ticked when $consent is true. */
function fb_post_save( array $answers = array( 'signs' => 'Tight belly', 'actions' => 'Fluids', 'contacts' => 'Nurse' ), $consent = true ) {
	$_POST = array(
		'vance_hub_flare_nonce' => 'nonce-vance_hub_flare_plan',
		'vance_hub_flare_do'    => 'save',
		'vhd_flare'             => $answers,
	);
	if ( $consent ) {
		// As the form sends it: the tick, and the reference of the wording shown.
		$_POST['vhd_consent']     = '1';
		$_POST['vhd_consent_ref'] = hash( 'sha256', vance_hub_flare_plan_consent_text() );
	}
}

/** A delete request with the right nonce. */
function fb_post_delete() {
	$_POST = array(
		'vance_hub_flare_nonce' => 'nonce-vance_hub_flare_plan',
		'vance_hub_flare_do'    => 'delete',
	);
}

/** Give user 7 a flare plan agreement to the wording in use now. Call after fb_arrange(). */
function fb_consented() {
	$GLOBALS['UMETA'][7]['_sla_consent_flare_plan']      = '1';
	$GLOBALS['UMETA'][7]['_sla_consent_flare_plan_at']   = '2026-10-01 09:00:00';
	$GLOBALS['UMETA'][7]['_sla_consent_flare_plan_text'] = vance_hub_flare_plan_consent_text();
}

/** Give user 7 a saved plan from 10 Nov 2026. Call after fb_arrange(). */
function fb_stored_plan() {
	$GLOBALS['UMETA'][7]['_sla_flare_plan'] = array(
		'updated'  => $GLOBALS['OLD_TS'],
		'signs'    => 'Tight belly',
		'actions'  => 'Fluids',
		'contacts' => 'Nurse',
	);
}

/** A stored value of user 7, or null when the key is absent. */
function fb_meta( $key ) {
	return $GLOBALS['UMETA'][7][ $key ] ?? null;
}

/** Run the flare POST handler. Returns the redirect URL, or null when it did not redirect. */
function fb_handle() {
	try {
		vance_hub_flare_plan_handle_post();
	} catch ( RedirectStop $stop ) {
		return $stop->url;
	}
	return null;
}

/** Position in SEQ of the first event, or -1 when there is none. */
function fb_seq_index( $event ) {
	$found = array_search( $event, $GLOBALS['SEQ'], true );
	return false === $found ? -1 : $found;
}

/** True when SEQ holds one lock and one unlock, and the unlock is the last event. */
function fb_seq_locked_once() {
	$counts = array_count_values( $GLOBALS['SEQ'] );
	return isset( $counts['lock'], $counts['unlock'] ) && 1 === $counts['lock'] && 1 === $counts['unlock'] && 'unlock' === end( $GLOBALS['SEQ'] );
}

/** A plan view as vance_hub_flare_plan_html() takes it. */
function fb_flare_view( array $over = array() ) {
	return array_merge( array(
		'plan'         => null,
		'updated'      => '',
		'urgent'       => 'Call the clinic.',
		'need_consent' => false,
		'consent'      => 'I agree.',
		'note'         => 'Not medical advice.',
		'nonce'        => 'nonce-vance_hub_flare_plan',
		'flash'        => '',
	), $over );
}

/** A check-in save with the consent box ticked. */
function fb_checkin_post_save() {
	$_POST = array(
		'vance_hub_checkin_nonce' => 'nonce-vance_hub_checkin',
		'vance_hub_checkin_do'    => 'save',
		'vhd_checkin'             => array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ),
		'vhd_consent'             => '1',
		'vhd_consent_ref'         => hash( 'sha256', vance_hub_checkin_consent_text() ),
	);
}

/** Run the check-in POST handler. Returns the redirect URL, or null. */
function fb_checkin_handle() {
	try {
		vance_hub_checkin_handle_post();
	} catch ( RedirectStop $stop ) {
		return $stop->url;
	}
	return null;
}

/** A member object with an ID, as the features collector reads it. */
function fb_user( $id ) {
	return (object) array( 'ID' => $id );
}

/* ===================================================================== */
section( '1. pure helpers: keys and the length limit' );

check( 'keys: the three answers are signs, actions and contacts, in that order', vance_hub_flare_plan_keys() === array( 'signs', 'actions', 'contacts' ) );
check( 'limit: an answer is at most 1000 characters', vance_hub_flare_plan_max_chars() === 1000 );

/* ===================================================================== */
section( '2. clean(): what a posted answer set becomes' );

check( 'clean: a string instead of an array gives null', vance_hub_flare_plan_clean( 'signs' ) === null );
check( 'clean: null gives null', vance_hub_flare_plan_clean( null ) === null );
check( 'clean: three empty answers give null', vance_hub_flare_plan_clean( array( 'signs' => '', 'actions' => '', 'contacts' => '' ) ) === null );
check( 'clean: whitespace-only answers give null', vance_hub_flare_plan_clean( array( 'signs' => " \t ", 'actions' => "\n\n", 'contacts' => '   ' ) ) === null );
check( 'clean: an answer that is only an empty tag gives null', vance_hub_flare_plan_clean( array( 'signs' => '<b></b>', 'actions' => '', 'contacts' => '' ) ) === null );
check( 'clean: one character is kept, the other two come back as empty strings', vance_hub_flare_plan_clean( array( 'signs' => 'a' ) ) === array( 'signs' => 'a', 'actions' => '', 'contacts' => '' ) );
check( 'clean: a missing answer key comes back as an empty string', vance_hub_flare_plan_clean( array( 'contacts' => 'Nurse' ) ) === array( 'signs' => '', 'actions' => '', 'contacts' => 'Nurse' ) );

check( 'limit: exactly 1000 characters are kept whole', strlen( vance_hub_flare_plan_clean( array( 'signs' => str_repeat( 'a', 1000 ) ) )['signs'] ?? '' ) === 1000 );
check( 'limit: 1001 characters are cut to 1000', strlen( vance_hub_flare_plan_clean( array( 'signs' => str_repeat( 'a', 1001 ) ) )['signs'] ?? '' ) === 1000 );
check( 'limit: 1001 accented characters are cut to 1000 characters, not bytes', preg_match_all( '/./su', vance_hub_flare_plan_clean( array( 'signs' => str_repeat( 'é', 1001 ) ) )['signs'] ?? '' ) === 1000 );
check( 'limit: 1001 emoji are cut to 1000 characters, not bytes', preg_match_all( '/./su', vance_hub_flare_plan_clean( array( 'signs' => str_repeat( '😀', 1001 ) ) )['signs'] ?? '' ) === 1000 );
check( 'limit: a cut through 3-byte characters leaves valid UTF-8', preg_match( '//u', vance_hub_flare_plan_clean( array( 'signs' => str_repeat( '€', 1001 ) ) )['signs'] ?? '' ) === 1 );

check( 'tags: markup tags are removed and the text is kept', ( vance_hub_flare_plan_clean( array( 'signs' => '<b>bold</b> <a href="https://example.test">link</a>' ) )['signs'] ?? null ) === 'bold link' );
check( 'tags: a script block is removed with its content', ( vance_hub_flare_plan_clean( array( 'signs' => 'before<script>alert(1)</script>after' ) )['signs'] ?? null ) === 'beforeafter' );
check( 'line breaks: CRLF and CR become LF', ( vance_hub_flare_plan_clean( array( 'signs' => "one\r\ntwo\rthree" ) )['signs'] ?? null ) === "one\ntwo\nthree" );
check( 'line breaks: a blank line between two lines is kept', ( vance_hub_flare_plan_clean( array( 'signs' => "one\n\ntwo" ) )['signs'] ?? null ) === "one\n\ntwo" );
check( 'slashes: a posted double backslash arrives as one backslash', ( vance_hub_flare_plan_clean( array( 'signs' => 'a\\\\b' ) )['signs'] ?? null ) === 'a\\b' );
check( 'non-string: an array answer is treated as empty, the others are kept', vance_hub_flare_plan_clean( array( 'signs' => array( 'x' ), 'actions' => 5, 'contacts' => 'Nurse' ) ) === array( 'signs' => '', 'actions' => '', 'contacts' => 'Nurse' ) );
check( 'unknown keys are dropped: only the three answer keys come back', array_keys( vance_hub_flare_plan_clean( array( 'signs' => 'a', 'evil' => 'b' ) ) ?? array() ) === array( 'signs', 'actions', 'contacts' ) );

$RAW    = array( 'signs' => 'a\\\\b', 'actions' => "x\r\n" );
$BEFORE = $RAW;
vance_hub_flare_plan_clean( $RAW );
check( 'input: the posted array is not changed by clean()', $RAW === $BEFORE );

/* ===================================================================== */
section( '3. stored plan: read back and checked' );

fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_plan'] = 'junk';
check( 'stored: a value that is not an array reads as no plan', vance_hub_flare_plan_get( 7 ) === null );

$GLOBALS['UMETA'][7]['_sla_flare_plan'] = array( 'updated' => 0, 'signs' => 'a', 'actions' => '', 'contacts' => '' );
check( 'stored: a plan with updated 0 reads as no plan', vance_hub_flare_plan_get( 7 ) === null );

$GLOBALS['UMETA'][7]['_sla_flare_plan'] = array( 'signs' => 'a', 'actions' => '', 'contacts' => '' );
check( 'stored: a plan with no updated time reads as no plan', vance_hub_flare_plan_get( 7 ) === null );

$GLOBALS['UMETA'][7]['_sla_flare_plan'] = array( 'updated' => $OLD_TS, 'signs' => array( 'x' ), 'actions' => 'Fluids', 'contacts' => 'Nurse' );
check( 'stored: a damaged answer reads as empty text, the others are kept', vance_hub_flare_plan_get( 7 ) === array( 'updated' => $OLD_TS, 'signs' => '', 'actions' => 'Fluids', 'contacts' => 'Nurse' ) );

fb_stored_plan();
check( 'stored: a good plan reads back with its time as an int', vance_hub_flare_plan_get( 7 ) === array( 'updated' => $OLD_TS, 'signs' => 'Tight belly', 'actions' => 'Fluids', 'contacts' => 'Nurse' ) );

/* ===================================================================== */
section( '4. write(): store, read back, backslashes and failures' );

fb_arrange( $FP_ON );
check( 'write: a good plan is stored and reports true', vance_hub_flare_plan_write( 7, array( 'signs' => 'Tight belly', 'actions' => 'Fluids', 'contacts' => 'Nurse' ), $OLD_TS ) === true );
check( 'write: the stored plan has the time given and the three answers', vance_hub_flare_plan_get( 7 ) === array( 'updated' => $OLD_TS, 'signs' => 'Tight belly', 'actions' => 'Fluids', 'contacts' => 'Nurse' ) );

fb_arrange( $FP_ON );
vance_hub_flare_plan_write( 7, vance_hub_flare_plan_clean( array( 'signs' => 'C:\\\\Clinic' ) ), $OLD_TS );
check( 'write: a backslash in an answer is stored as typed', ( vance_hub_flare_plan_get( 7 )['signs'] ?? null ) === 'C:\\Clinic' );

fb_arrange( $FP_ON );
$GLOBALS['UPDATE_FAILS'] = '_sla_flare_plan';
check( 'write: a failed store reports false', vance_hub_flare_plan_write( 7, array( 'signs' => 'a', 'actions' => '', 'contacts' => '' ), $OLD_TS ) === false );
check( 'write: a failed store leaves no plan behind', fb_meta( '_sla_flare_plan' ) === null );

fb_arrange( $FP_ON );
check( 'write: empty answers report false and write nothing', vance_hub_flare_plan_write( 7, array( 'signs' => '', 'actions' => '', 'contacts' => '' ), $OLD_TS ) === false && $GLOBALS['WRITES'] === array() );
check( 'write: user id 0 reports false and writes nothing', vance_hub_flare_plan_write( 0, array( 'signs' => 'a' ), $OLD_TS ) === false && $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_stored_plan();
vance_hub_flare_plan_write( 7, array( 'actions' => 'Rest' ), $OLD_TS + 60 );
check( 'write: a new plan replaces the old one, blank answers are emptied', vance_hub_flare_plan_get( 7 ) === array( 'updated' => $OLD_TS + 60, 'signs' => '', 'actions' => 'Rest', 'contacts' => '' ) );

/* ===================================================================== */
section( '5. POST handler: gating, expired nonce, unknown action' );

fb_arrange( $FP_ON );
fb_post_save();
check( 'control: a good save with the switch on redirects with vance_flare=saved', fb_handle() === 'https://example.test/hub/?vance_flare=saved' );

fb_arrange( $FP_ON );
fb_post_save();
$_SERVER['REQUEST_METHOD'] = 'GET';
check( 'gating: a GET request does nothing (no redirect)', fb_handle() === null );
check( 'gating: a GET request writes nothing', $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_post_save();
unset( $_POST['vance_hub_flare_nonce'] );
check( 'gating: no nonce field does nothing (no redirect)', fb_handle() === null );
check( 'gating: no nonce field writes nothing', $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_post_save();
$_POST['vance_hub_flare_nonce'] = array( 'nonce-vance_hub_flare_plan' );
check( 'gating: an array nonce does nothing and does not fail', fb_handle() === null && $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON, false );
fb_post_save();
check( 'gating: a visitor who is not logged in does nothing', fb_handle() === null && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_flare_plan_urgent' => 'Call the clinic.' ) );
fb_post_save();
check( 'gating: the switch unset (default off) does nothing, even for a good save', fb_handle() === null && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'classic', 'vance_hub_dash_flare_plan' => true, 'vance_hub_flare_plan_urgent' => 'Call the clinic.' ) );
fb_post_save();
check( 'gating: the Home layout master switch off blocks a good save', fb_handle() === null && $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
fb_post_delete();
$GLOBALS['MODS']['vance_hub_dash_flare_plan'] = false;
check( 'gating: the switch off also blocks delete, and the plan stays', fb_handle() === null && $GLOBALS['WRITES'] === array() && fb_meta( '_sla_flare_plan' ) !== null );

fb_arrange( $FP_ON );
fb_post_save();
$_POST['vance_hub_flare_nonce'] = 'forged';
check( 'expired: a bad nonce redirects with vance_flare=expired', fb_handle() === 'https://example.test/hub/?vance_flare=expired' );
check( 'expired: a bad nonce writes nothing', $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_post_save();
$_POST['vance_hub_flare_nonce'] = 'nonce-vance_hub_checkin';
check( 'expired: a nonce made for the check-in redirects with vance_flare=expired', fb_handle() === 'https://example.test/hub/?vance_flare=expired' );
check( 'expired: a nonce made for the check-in writes nothing', $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
fb_post_delete();
$_POST['vance_hub_flare_nonce'] = 'forged';
check( 'expired: a bad nonce on delete keeps the plan and the agreement', fb_handle() === 'https://example.test/hub/?vance_flare=expired' && fb_meta( '_sla_flare_plan' ) !== null && fb_meta( '_sla_consent_flare_plan' ) === '1' );

fb_arrange( $FP_ON );
fb_post_save();
$_POST['vance_hub_flare_do'] = 'rename';
check( 'unknown action: redirects with vance_flare=invalid', fb_handle() === 'https://example.test/hub/?vance_flare=invalid' );
check( 'unknown action: writes nothing and takes no lock', $GLOBALS['WRITES'] === array() && $GLOBALS['LOCK_SQL'] === array() );

/* ===================================================================== */
section( '6. POST handler, save: consent, validation, order, rollback, lock' );

fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => 'Tight belly', 'actions' => 'Fluids', 'contacts' => 'Nurse' ), false );
check( 'consent: good answers with no consent box redirect with vance_flare=consent', fb_handle() === 'https://example.test/hub/?vance_flare=consent' );
check( 'consent: no consent writes nothing, not even the agreement', $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_post_save();
$_POST['vhd_consent_ref'] = hash( 'sha256', 'Old wording.' );
check( 'changed: a consent box for other wording redirects with vance_flare=changed', fb_handle() === 'https://example.test/hub/?vance_flare=changed' );
check( 'changed: writes nothing', $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_flare_plan' => true ) );
fb_post_save();
check( 'urgent: an empty urgent text redirects with vance_flare=failed', fb_handle() === 'https://example.test/hub/?vance_flare=failed' );
check( 'urgent: an empty urgent text writes nothing, not even the agreement', $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_flare_plan' => true, 'vance_hub_flare_plan_urgent' => "  \n " ) );
fb_post_save();
check( 'urgent: a whitespace-only urgent text counts as empty and writes nothing', fb_handle() === 'https://example.test/hub/?vance_flare=failed' && $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => '   ', 'actions' => '', 'contacts' => "\n" ) );
check( 'invalid: blank answers redirect with vance_flare=invalid', fb_handle() === 'https://example.test/hub/?vance_flare=invalid' );
check( 'invalid: blank answers write nothing', $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => '   ', 'actions' => '', 'contacts' => '' ), false );
check( 'invalid is checked before consent: blank answers and no consent give invalid', fb_handle() === 'https://example.test/hub/?vance_flare=invalid' );

fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => str_repeat( 'a', 1200 ), 'actions' => '', 'contacts' => '' ) );
fb_handle();
check( 'limit: a save keeps at most 1000 characters of an answer', strlen( fb_meta( '_sla_flare_plan' )['signs'] ?? '' ) === 1000 );

fb_arrange( $FP_ON );
fb_post_save();
$RESULT_FIRST = fb_handle();
check( 'save: a first save redirects with vance_flare=saved', $RESULT_FIRST === 'https://example.test/hub/?vance_flare=saved' );
check( 'save: the agreement is stored with the flag, the time and the wording shown', fb_meta( '_sla_consent_flare_plan' ) === '1' && fb_meta( '_sla_consent_flare_plan_at' ) === '2026-11-18 12:00:00' && fb_meta( '_sla_consent_flare_plan_text' ) === vance_hub_flare_plan_consent_text() );
check( 'save: the plan is stored with the three answers', ( fb_meta( '_sla_flare_plan' )['signs'] ?? null ) === 'Tight belly' && ( fb_meta( '_sla_flare_plan' )['actions'] ?? null ) === 'Fluids' && ( fb_meta( '_sla_flare_plan' )['contacts'] ?? null ) === 'Nurse' );
check( 'save: the plan is dated with the time of this save', ( fb_meta( '_sla_flare_plan' )['updated'] ?? 0 ) >= $NOW );
check( 'order: the agreement is written before the plan', fb_seq_index( 'write:_sla_consent_flare_plan' ) >= 0 && fb_seq_index( 'write:_sla_consent_flare_plan' ) < fb_seq_index( 'write:_sla_flare_plan' ) );

fb_arrange( $FP_ON );
fb_post_save();
$GLOBALS['UPDATE_FAILS'] = '_sla_flare_plan';
check( 'rollback: a failed plan save redirects with vance_flare=failed', fb_handle() === 'https://example.test/hub/?vance_flare=failed' );
check( 'rollback: the agreement was written first, so there was something to take back', in_array( 'update_user_meta:_sla_consent_flare_plan', $GLOBALS['WRITES'], true ) );
check( 'rollback: no agreement flag is left behind', fb_meta( '_sla_consent_flare_plan' ) === null );
check( 'rollback: no agreement time or wording is left behind', fb_meta( '_sla_consent_flare_plan_at' ) === null && fb_meta( '_sla_consent_flare_plan_text' ) === null );
check( 'rollback: no plan is stored', fb_meta( '_sla_flare_plan' ) === null );

fb_arrange( $FP_ON );
fb_consented();
fb_stored_plan();
fb_post_save( array( 'signs' => 'New sign', 'actions' => '', 'contacts' => '' ), false );
$GLOBALS['UPDATE_FAILS'] = '_sla_flare_plan';
fb_handle();
check( 'rollback: a failed edit leaves the agreement exactly as it was', fb_meta( '_sla_consent_flare_plan_at' ) === '2026-10-01 09:00:00' && fb_meta( '_sla_consent_flare_plan' ) === '1' );

fb_arrange( $FP_ON );
fb_consented();
fb_stored_plan();
fb_post_save( array( 'signs' => 'New sign', 'actions' => '', 'contacts' => '' ), false );
check( 'edit: an edit with the agreement already in place needs no box and redirects with saved', fb_handle() === 'https://example.test/hub/?vance_flare=saved' );
check( 'edit: the agreement time is not rewritten', fb_meta( '_sla_consent_flare_plan_at' ) === '2026-10-01 09:00:00' );
check( 'edit: no agreement key is written', preg_grep( '/_sla_consent_flare_plan/', $GLOBALS['WRITES'] ) === array() );
check( 'edit: the plan holds the new answer', ( fb_meta( '_sla_flare_plan' )['signs'] ?? null ) === 'New sign' );

fb_arrange( $FP_ON );
fb_consented();
$GLOBALS['UMETA'][7]['_sla_consent_flare_plan_text'] = 'Old wording.';
fb_post_save( array( 'signs' => 'New sign', 'actions' => '', 'contacts' => '' ), false );
check( 'edit: an agreement to other wording asks the member again', fb_handle() === 'https://example.test/hub/?vance_flare=consent' );

fb_arrange( $FP_ON );
$GLOBALS['LOCK_RESULT'] = '0';
fb_post_save();
check( 'lock: a save without the lock redirects with vance_flare=failed', fb_handle() === 'https://example.test/hub/?vance_flare=failed' );
check( 'lock: a save without the lock writes nothing', $GLOBALS['WRITES'] === array() );
check( 'lock: a save without the lock does not release a lock it never got', $GLOBALS['UNLOCK_SQL'] === array() );

fb_arrange( $FP_ON );
fb_post_save();
fb_handle();
check( 'lock: a save takes exactly one lock', count( $GLOBALS['LOCK_SQL'] ) === 1 );
check( 'lock: the lock is released last, once', fb_seq_locked_once() );

fb_arrange( $FP_ON );
fb_post_save();
$GLOBALS['UPDATE_FAILS'] = '_sla_flare_plan';
fb_handle();
check( 'lock: a failed save still releases the lock', fb_seq_locked_once() );

fb_arrange( $FP_ON );
fb_post_save();
fb_handle();
check( 'lock: the lock is taken before the agreement is read', fb_seq_index( 'lock' ) === 0 && fb_seq_index( 'lock' ) < fb_seq_index( 'read:_sla_consent_flare_plan' ) );

fb_arrange( $FP_ON );
$GLOBALS['STALE'][7] = array();
$GLOBALS['ON_LOCK']  = function () {
	fb_consented();
};
fb_post_save( array( 'signs' => 'Tight belly', 'actions' => '', 'contacts' => '' ), false );
check( 'lock: a save reads the agreement another request wrote while it waited', fb_handle() === 'https://example.test/hub/?vance_flare=saved' );

/* ===================================================================== */
section( '7. POST handler, delete: lock, withdrawal and read-back failures' );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['LOCK_RESULT'] = '0';
fb_post_delete();
check( 'delete lock: without the lock the result is kept', fb_handle() === 'https://example.test/hub/?vance_flare=kept' );
check( 'delete lock: without the lock nothing is deleted or withdrawn', $GLOBALS['WRITES'] === array() && fb_meta( '_sla_flare_plan' ) !== null && fb_meta( '_sla_consent_flare_plan' ) === '1' );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
fb_post_delete();
check( 'delete: a delete with the agreement on redirects with vance_flare=deleted', fb_handle() === 'https://example.test/hub/?vance_flare=deleted' );
check( 'delete: the plan is removed', fb_meta( '_sla_flare_plan' ) === null );
check( 'delete: the agreement flag is removed and the withdrawal time is set', fb_meta( '_sla_consent_flare_plan' ) === null && fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === '2026-11-18 12:00:00' );
check( 'delete: the agreement time and wording stay as a record', fb_meta( '_sla_consent_flare_plan_at' ) === '2026-10-01 09:00:00' && fb_meta( '_sla_consent_flare_plan_text' ) === vance_hub_flare_plan_consent_text() );
check( 'delete: the lock is released last, once', fb_seq_locked_once() );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_post_delete();
check( 'delete without an agreement on record redirects with vance_flare=deleted', fb_handle() === 'https://example.test/hub/?vance_flare=deleted' );
check( 'delete without an agreement on record writes no withdrawal time', fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === null );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['DELETE_FAILS'] = '_sla_flare_plan';
fb_post_delete();
check( 'delete read-back: a plan that is still there gives kept', fb_handle() === 'https://example.test/hub/?vance_flare=kept' );
check( 'delete read-back: the plan is still there and the agreement is not withdrawn', fb_meta( '_sla_flare_plan' ) !== null && fb_meta( '_sla_consent_flare_plan' ) === '1' && fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === null );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['DELETE_FAILS'] = '_sla_consent_flare_plan';
fb_post_delete();
check( 'delete read-back: an agreement that is still active gives kept, not deleted', fb_handle() === 'https://example.test/hub/?vance_flare=kept' );
check( 'delete read-back: the agreement is still active and no withdrawal time is written', fb_meta( '_sla_consent_flare_plan' ) === '1' && fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === null );

/* ===================================================================== */
section( '8. check-in untouched, in both directions' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true, 'vance_hub_dash_flare_plan' => true, 'vance_hub_flare_plan_urgent' => 'Call the clinic.' ) );
fb_checkin_post_save();
check( 'control: a check-in save redirects with vance_checkin=saved', fb_checkin_handle() === 'https://example.test/hub/?vance_checkin=saved' );
check( 'control: the check-in save writes its own agreement key, so the grep below can see writes', fb_meta( '_sla_consent_checkin' ) === '1' );
check( 'check-in save writes no flare plan agreement key', preg_grep( '/_sla_consent_flare_plan/', $GLOBALS['WRITES'] ) === array() );
check( 'check-in save writes no flare plan key', preg_grep( '/_sla_flare_plan/', $GLOBALS['WRITES'] ) === array() );

fb_arrange( $FP_ON );
fb_post_save();
fb_handle();
check( 'flare save writes no check-in agreement key', preg_grep( '/_sla_consent_checkin/', $GLOBALS['WRITES'] ) === array() );
check( 'flare save writes no check-in history', preg_grep( '/_sla_weekly_check_in_history/', $GLOBALS['WRITES'] ) === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_consented();
fb_checkin_post_save();
unset( $_POST['vhd_consent'], $_POST['vhd_consent_ref'] );
check( 'check-in: a flare plan agreement does not count as a check-in agreement', fb_checkin_handle() === 'https://example.test/hub/?vance_checkin=consent' );

fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_consent_checkin']      = '1';
$GLOBALS['UMETA'][7]['_sla_consent_checkin_at']   = '2026-10-01 09:00:00';
$GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] = vance_hub_checkin_consent_text();
fb_post_save( array( 'signs' => 'Tight belly', 'actions' => '', 'contacts' => '' ), false );
check( 'flare plan: a check-in agreement does not count as a flare plan agreement', fb_handle() === 'https://example.test/hub/?vance_flare=consent' );

/* ===================================================================== */
section( '9. wiring: default off, slug list, data() gating and shape' );

check( 'default: the flare plan switch is off when never saved', vance_hub_switch_default( 'vance_hub_dash_flare_plan' ) === false );
check( 'default: the member dashboard chart keeps its default of on', vance_hub_switch_default( 'vance_hub_dash_chart' ) === true );
check( 'default: the weekly check-in keeps its default of off', vance_hub_switch_default( 'vance_hub_dash_checkin' ) === false );
check( 'known slugs: the flare plan is not listed, so the generic handlers stay closed to it', ! in_array( 'flare-plan', vance_known_tool_slugs(), true ) && ! in_array( 'flare_plan', vance_known_tool_slugs(), true ) );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_flare_plan_urgent' => 'Call the clinic.' ) );
check( 'available: the switch unset gives not available, even with urgent text', vance_hub_flare_plan_available() === false );
check( 'data: the switch off gives null', vance_hub_flare_plan_data( 7 ) === null );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_flare_plan' => true, 'vance_hub_flare_plan_urgent' => '' ) );
check( 'data: an empty urgent text gives null', vance_hub_flare_plan_data( 7 ) === null );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_flare_plan' => true, 'vance_hub_flare_plan_urgent' => "   \n  " ) );
check( 'data: a whitespace-only urgent text gives null', vance_hub_flare_plan_data( 7 ) === null );

fb_arrange( $FP_ON );
$D = vance_hub_flare_plan_data( 7 );
check( 'data: the switch on gives the view with its eight keys in order', is_array( $D ) && array_keys( $D ) === array( 'plan', 'updated', 'urgent', 'need_consent', 'consent', 'note', 'nonce', 'flash' ) );
check( 'data: no saved plan gives plan null and an empty date', is_array( $D ) && $D['plan'] === null && $D['updated'] === '' );
check( 'data: no agreement on record asks for consent', is_array( $D ) && $D['need_consent'] === true );
check( 'data: the urgent text is the one the administrator set', is_array( $D ) && $D['urgent'] === 'Call the clinic on 0800 000 000 if you have blood in your stool.' );
check( 'data: the note falls back to its default text', is_array( $D ) && $D['note'] === 'This plan is your own record. It is not medical advice. Share it with your health team.' );
check( 'data: the nonce is the flare plan nonce', is_array( $D ) && $D['nonce'] === 'nonce-vance_hub_flare_plan' );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$D = vance_hub_flare_plan_data( 7 );
check( 'data: a saved plan is returned with its three answers', is_array( $D ) && ( $D['plan']['signs'] ?? null ) === 'Tight belly' && ( $D['plan']['contacts'] ?? null ) === 'Nurse' );
check( 'data: the date is the date of the last change, in site format', is_array( $D ) && $D['updated'] === '10 Nov 2026' );
check( 'data: an agreement to the wording in use needs no consent', is_array( $D ) && $D['need_consent'] === false );

fb_arrange( $FP_ON );
fb_consented();
$GLOBALS['MODS']['vance_hub_flare_plan_consent'] = 'New wording for the agreement.';
$D = vance_hub_flare_plan_data( 7 );
check( 'data: a change of the consent wording asks for consent again', is_array( $D ) && $D['need_consent'] === true );

fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_plan'] = 'junk';
$D = vance_hub_flare_plan_data( 7 );
check( 'data: a damaged stored plan shows the open form, not a broken plan', is_array( $D ) && $D['plan'] === null );

fb_arrange( $FP_ON );
$_GET['vance_flare'] = 'saved';
$D = vance_hub_flare_plan_data( 7 );
check( 'data: the flash message comes from vance_flare in the URL', is_array( $D ) && $D['flash'] === 'saved' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_member_no' => false, 'vance_hub_dash_chart' => false, 'vance_hub_dash_checkin' => false, 'vance_hub_dash_flare_plan' => true, 'vance_hub_flare_plan_urgent' => 'Call the clinic.' ) );
$F = vance_hub_dashboard_features_data( fb_user( 7 ), array(), '' );
check( 'wiring: the home features data does not carry the flare plan (it is in the Health Profile tab)', is_array( $F ) && ! array_key_exists( 'flare_plan', $F ) );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_member_no' => false, 'vance_hub_dash_chart' => false, 'vance_hub_dash_checkin' => false ) );
$F = vance_hub_dashboard_features_data( fb_user( 7 ), array(), '' );
check( 'wiring: the home features data has no flare plan key when the switch is off either', is_array( $F ) && ! array_key_exists( 'flare_plan', $F ) );

// The Health Profile tab hosts the card. Static inspection of page-dashboard.php:
// the tab is a template, so these check the source, not a render.
$PD = file_get_contents( $THEME . '/page-dashboard.php' );
$HP = preg_match( '/case \'health-profile\':\r?\n(.*?)\r?\n\s+case \'my-recipes\':\r?\n/s', $PD, $HPM ) ? $HPM[1] : '';
check( 'tab: the Health Profile case holds the flare plan card', '' !== $HP && false !== strpos( $HP, 'vance_hub_flare_plan_html( $hp_flare )' ) );
check( 'tab: the flare data comes from vance_hub_flare_plan_data() for the current user', false !== strpos( $HP, 'vance_hub_flare_plan_data( $current_user->ID )' ) );
check( 'tab: the sub-tab links use ?hsub= and the sub-tab is read with sanitize_key', false !== strpos( $HP, "'hsub' => \$hp_slug" ) && false !== strpos( $HP, "sanitize_key( wp_unslash( \$_GET['hsub'] ) )" ) );
check( 'tab: three panels, one each for profile, screening and flare plan', 1 === substr_count( $HP, "\$hp_hide( 'profile' )" ) && 1 === substr_count( $HP, "\$hp_hide( 'screening' )" ) && 1 === substr_count( $HP, "\$hp_hide( 'flare' )" ) );
check( 'tab: the flare sub-tab shows only when the card can show', false !== strpos( $HP, "\$hp_subs['flare']" ) && false !== strpos( $HP, '! empty( $hp_flare )' ) );
check( 'tab: sub-tabs and the hidden panels need the hub dashboard (the stylesheet)', false !== strpos( $HP, 'vance_hub_dashboard_active()' ) );
$HOME = file_get_contents( $THEME . '/inc/hub-dashboard.php' );
$JS  = file_get_contents( $THEME . '/assets/js/hub-flare-plan.js' );
$CSS = file_get_contents( $THEME . '/assets/css/hub-dashboard.css' );
check( 'print: the script does not mark a card that sits in a hidden panel', false !== strpos( $JS, "card.closest( '[hidden]' )" ) );
check( 'print: the card-only print rules apply only under html.vhd-printing', false !== strpos( $CSS, 'html.vhd-printing body * { visibility: hidden; }' ) && 0 === preg_match( '/^\tbody \* \{ visibility: hidden/m', $CSS ) );
check( 'home: the hub dashboard home no longer prints the flare plan card', false === strpos( $HOME, 'vance_hub_flare_plan_html' ) && false === strpos( $HOME, "'flare_plan'" ) );

/* ===================================================================== */
section( '10. render: escaping, states, controls, flash messages' );

check( 'render: no view gives an empty string', vance_hub_flare_plan_html( null ) === '' );
check( 'render: an empty urgent text gives an empty string, no card', vance_hub_flare_plan_html( fb_flare_view( array( 'urgent' => '' ) ) ) === '' );
check( 'render: an urgent text of "0" still shows the card', strpos( vance_hub_flare_plan_html( fb_flare_view( array( 'urgent' => '0' ) ) ), 'My flare plan' ) !== false );

$H_OPEN = vance_hub_flare_plan_html( fb_flare_view() );
check( 'render: no plan shows the card title', strpos( $H_OPEN, '<h2 id="vhd-flare-title" class="vhd-h3">My flare plan</h2>' ) !== false );
check( 'render: no plan shows the three answer boxes with their names', strpos( $H_OPEN, 'name="vhd_flare[signs]"' ) !== false && strpos( $H_OPEN, 'name="vhd_flare[actions]"' ) !== false && strpos( $H_OPEN, 'name="vhd_flare[contacts]"' ) !== false );
check( 'render: no plan shows the save button', strpos( $H_OPEN, 'Save my plan' ) !== false );
check( 'render: no plan shows no edit, manage or print control', strpos( $H_OPEN, 'Edit my plan' ) === false && strpos( $H_OPEN, 'Manage my flare plan data' ) === false && strpos( $H_OPEN, 'data-vhd-flare-print' ) === false );
check( 'render: the fixed urgent help text is shown to a member with no plan', strpos( $H_OPEN, 'Call the clinic.' ) !== false && strpos( $H_OPEN, 'When to get urgent help' ) !== false );

$H_PLAN = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => array( 'updated' => $OLD_TS, 'signs' => 'Tight belly', 'actions' => 'Fluids', 'contacts' => 'Nurse' ), 'updated' => '10 Nov 2026' ) ) );
check( 'render: a plan shows the three section titles', strpos( $H_PLAN, '<h3 class="vhd-flare__h">My early signs</h3>' ) !== false && strpos( $H_PLAN, '<h3 class="vhd-flare__h">What I do first</h3>' ) !== false && strpos( $H_PLAN, '<h3 class="vhd-flare__h">Who I contact</h3>' ) !== false );
check( 'render: a plan shows its answers', strpos( $H_PLAN, '<p>Tight belly</p>' ) !== false && strpos( $H_PLAN, '<p>Fluids</p>' ) !== false && strpos( $H_PLAN, '<p>Nurse</p>' ) !== false );
check( 'render: a plan shows the date of the last change', strpos( $H_PLAN, 'Last changed on 10 Nov 2026.' ) !== false );
check( 'render: a plan shows Edit, Manage and Print controls', strpos( $H_PLAN, '<summary>Edit my plan</summary>' ) !== false && strpos( $H_PLAN, '<summary>Manage my flare plan data</summary>' ) !== false && strpos( $H_PLAN, 'Print my plan' ) !== false );
check( 'render: a plan shows the open form only inside Edit, prefilled', strpos( $H_PLAN, 'id="vhd-flare-edit-signs"' ) !== false && strpos( $H_PLAN, '>Tight belly</textarea>' ) !== false );
check( 'render: the delete control sends the delete action with the nonce', strpos( $H_PLAN, 'name="vance_hub_flare_do" value="delete"' ) !== false && strpos( $H_PLAN, 'Delete my plan and withdraw my agreement' ) !== false );
check( 'render: the print button carries the hidden attribute, so it shows only with the script', strpos( $H_PLAN, 'data-vhd-flare-print hidden>' ) !== false );

$H_EMPTY = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => array( 'updated' => $OLD_TS, 'signs' => 'Tight belly', 'actions' => '', 'contacts' => '' ), 'updated' => '10 Nov 2026' ) ) );
check( 'render: a blank answer in a plan shows "Nothing written."', strpos( $H_EMPTY, '<em>Nothing written.</em>' ) !== false );

$H_XSS = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => array( 'updated' => $OLD_TS, 'signs' => '<script>alert(1)</script>', 'actions' => '', 'contacts' => '' ), 'updated' => '10 Nov 2026' ) ) );
check( 'escape: a script in a member answer is shown as text, not run', strpos( $H_XSS, '<script>' ) === false && strpos( $H_XSS, '&lt;script&gt;alert(1)&lt;/script&gt;' ) !== false );

$H_URGENT = vance_hub_flare_plan_html( fb_flare_view( array( 'urgent' => '<img src=x onerror=alert(1)>' ) ) );
check( 'escape: markup in the urgent help text is shown as text', strpos( $H_URGENT, '<img' ) === false && strpos( $H_URGENT, '&lt;img src=x onerror=alert(1)&gt;' ) !== false );

$H_LINES = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => array( 'updated' => $OLD_TS, 'signs' => "Line one\nLine two", 'actions' => '', 'contacts' => '' ), 'updated' => '10 Nov 2026' ) ) );
check( 'line breaks: a line break in an answer is kept as a break', strpos( $H_LINES, "Line one<br />\nLine two" ) !== false );

$H_CONSENT = vance_hub_flare_plan_html( fb_flare_view( array( 'need_consent' => true, 'consent' => 'I agree.' ) ) );
check( 'consent box: shown when consent is needed, with its reference', strpos( $H_CONSENT, 'name="vhd_consent" value="1" required' ) !== false && strpos( $H_CONSENT, 'value="' . hash( 'sha256', 'I agree.' ) . '"' ) !== false );
check( 'consent box: not shown when consent is already given', strpos( $H_OPEN, 'name="vhd_consent"' ) === false );

$H_SAVED = vance_hub_flare_plan_html( fb_flare_view( array( 'flash' => 'saved' ) ) );
check( 'flash: saved shows the saved message, not a warning', strpos( $H_SAVED, 'Your flare plan is saved.' ) !== false && strpos( $H_SAVED, 'vhd-flash--warn' ) === false );

$H_CONSENT_MSG = vance_hub_flare_plan_html( fb_flare_view( array( 'flash' => 'consent' ) ) );
check( 'flash: consent shows a warning about the box', strpos( $H_CONSENT_MSG, 'Tick the box to agree' ) !== false && strpos( $H_CONSENT_MSG, 'vhd-flash--warn' ) !== false );

$H_BOGUS = vance_hub_flare_plan_html( fb_flare_view( array( 'flash' => '<b>' ) ) );
check( 'flash: an unknown flash value shows no message', strpos( $H_BOGUS, 'role="status"' ) === false );

/* ===================================================================== */
section( '11. privacy: exporter and eraser' );

check( 'registration: the exporter is registered under vance-flare-plan', vance_hub_flare_plan_register_exporter( array() )['vance-flare-plan']['callback'] === 'vance_hub_flare_plan_export' );
check( 'registration: the eraser is registered under vance-flare-plan', vance_hub_flare_plan_register_eraser( array() )['vance-flare-plan']['callback'] === 'vance_hub_flare_plan_erase' );

fb_arrange( $FP_ON );
check( 'export: an unknown email gives no data and is done', vance_hub_flare_plan_export( 'nobody@example.test' ) === array( 'data' => array(), 'done' => true ) );

fb_arrange( $FP_ON );
$GLOBALS['USERS_BY_EMAIL']['ruth@example.test'] = (object) array( 'ID' => 7 );
fb_stored_plan();
fb_consented();
$EXP = vance_hub_flare_plan_export( 'ruth@example.test' );
check( 'export: a member with a plan gets one group for the flare plan', ( $EXP['data'][0]['group_id'] ?? null ) === 'vance-flare-plan' && ( $EXP['data'][0]['item_id'] ?? null ) === 'vance-flare-plan-7' && count( $EXP['data'] ) === 1 );
check( 'export: the plan rows hold the date and the three answers', ( $EXP['data'][0]['data'][0] ?? null ) === array( 'name' => 'Last changed', 'value' => '10 Nov 2026 12:00' ) && ( $EXP['data'][0]['data'][1] ?? null ) === array( 'name' => 'My early signs', 'value' => 'Tight belly' ) );
check( 'export: the agreement time is exported with the plan', ( $EXP['data'][0]['data'][4] ?? null ) === array( 'name' => 'Agreed on', 'value' => '2026-10-01 09:00:00' ) );
check( 'export: the export is done in one page', $EXP['done'] === true );

fb_arrange( $FP_ON );
$GLOBALS['USERS_BY_EMAIL']['ruth@example.test'] = (object) array( 'ID' => 7 );
$EXP = vance_hub_flare_plan_export( 'ruth@example.test' );
check( 'export: a member with nothing stored gets no data', $EXP === array( 'data' => array(), 'done' => true ) );

fb_arrange( $FP_ON );
$ERA = vance_hub_flare_plan_erase( 'nobody@example.test' );
check( 'erase: an unknown email removes nothing and retains nothing', $ERA === array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true ) );

fb_arrange( $FP_ON );
$GLOBALS['USERS_BY_EMAIL']['ruth@example.test'] = (object) array( 'ID' => 7 );
$ERA = vance_hub_flare_plan_erase( 'ruth@example.test' );
check( 'erase: a member with nothing stored removes nothing', $ERA['items_removed'] === false && $ERA['items_retained'] === false );

fb_arrange( $FP_ON );
$GLOBALS['USERS_BY_EMAIL']['ruth@example.test'] = (object) array( 'ID' => 7 );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_consent_flare_plan_withdrawn_at'] = '2026-11-01 08:00:00';
$ERA = vance_hub_flare_plan_erase( 'ruth@example.test' );
check( 'erase: the plan, the agreement and its record are all removed', fb_meta( '_sla_flare_plan' ) === null && fb_meta( '_sla_consent_flare_plan' ) === null && fb_meta( '_sla_consent_flare_plan_at' ) === null && fb_meta( '_sla_consent_flare_plan_text' ) === null && fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === null );
check( 'erase: a full removal reports items removed and none retained', $ERA['items_removed'] === true && $ERA['items_retained'] === false && $ERA['messages'] === array() );

fb_arrange( $FP_ON );
$GLOBALS['USERS_BY_EMAIL']['ruth@example.test'] = (object) array( 'ID' => 7 );
fb_stored_plan();
fb_consented();
$GLOBALS['DELETE_FAILS'] = '_sla_flare_plan';
$ERA = vance_hub_flare_plan_erase( 'ruth@example.test' );
check( 'erase: a plan that cannot be deleted is reported as retained', $ERA['items_retained'] === true );
check( 'erase: the retained plan is still there and its message names the key', fb_meta( '_sla_flare_plan' ) !== null && count( $ERA['messages'] ) === 1 && strpos( $ERA['messages'][0] ?? '', '_sla_flare_plan' ) !== false );
check( 'erase: the agreement keys are still removed when the plan is not', fb_meta( '_sla_consent_flare_plan' ) === null && $ERA['items_removed'] === true );

/* ===================================================================== */
section( '12. $base parameter of the agreement helpers' );

fb_arrange( $FP_ON );
check( 'base: the default keys are the check-in keys, unchanged', vance_hub_checkin_consent_keys() === array( '_sla_consent_checkin', '_sla_consent_checkin_at', '_sla_consent_checkin_text' ) );
check( 'base: flare_plan gives the flare plan keys', vance_hub_checkin_consent_keys( 'flare_plan' ) === array( '_sla_consent_flare_plan', '_sla_consent_flare_plan_at', '_sla_consent_flare_plan_text' ) );

fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_consent_checkin'] = '1';
$SNAP = vance_hub_checkin_consent_snapshot( 7 );
check( 'base: a snapshot with no base reads the check-in record only', array_keys( $SNAP ) === array( '_sla_consent_checkin', '_sla_consent_checkin_at', '_sla_consent_checkin_text' ) && ( $SNAP['_sla_consent_checkin'] ?? null ) === '1' );

fb_arrange( $FP_ON );
fb_consented();
$SNAP_F = vance_hub_checkin_consent_snapshot( 7, 'flare_plan' );
check( 'base: a snapshot with flare_plan reads the flare record only', array_keys( $SNAP_F ) === array( '_sla_consent_flare_plan', '_sla_consent_flare_plan_at', '_sla_consent_flare_plan_text' ) && ( $SNAP_F['_sla_consent_flare_plan'] ?? null ) === '1' );

fb_arrange( $FP_ON );
vance_hub_checkin_consent_restore( 7, array( '_sla_consent_checkin' => '1', '_sla_consent_checkin_at' => '2026-10-01 09:00:00', '_sla_consent_checkin_text' => 'Check-in wording.' ) );
check( 'base: a restore with no base writes the check-in keys and no flare key', fb_meta( '_sla_consent_checkin' ) === '1' && preg_grep( '/_sla_consent_flare_plan/', $GLOBALS['WRITES'] ) === array() );

fb_arrange( $FP_ON );
vance_hub_checkin_consent_restore( 7, array( '_sla_consent_flare_plan' => '1', '_sla_consent_flare_plan_at' => '2026-10-01 09:00:00', '_sla_consent_flare_plan_text' => 'Flare wording.' ), 'flare_plan' );
check( 'base: a restore with flare_plan writes the flare keys and no check-in key', fb_meta( '_sla_consent_flare_plan' ) === '1' && preg_grep( '/_sla_consent_checkin/', $GLOBALS['WRITES'] ) === array() );

fb_arrange( $FP_ON );
$REC = vance_hub_checkin_consent_record( 7, 'Check-in wording.' );
check( 'base: a record with no base stores the check-in agreement and reports true', $REC === true && fb_meta( '_sla_consent_checkin' ) === '1' && fb_meta( '_sla_consent_checkin_text' ) === 'Check-in wording.' );
check( 'base: a record with no base writes no flare key', preg_grep( '/_sla_consent_flare_plan/', $GLOBALS['WRITES'] ) === array() );

fb_arrange( $FP_ON );
$REC_F = vance_hub_checkin_consent_record( 7, 'Flare wording.', 'flare_plan' );
check( 'base: a record with flare_plan stores the flare agreement and reports true', $REC_F === true && fb_meta( '_sla_consent_flare_plan' ) === '1' && fb_meta( '_sla_consent_flare_plan_text' ) === 'Flare wording.' );
check( 'base: a record with flare_plan writes no check-in key', fb_meta( '_sla_consent_checkin' ) === null && preg_grep( '/_sla_consent_checkin/', $GLOBALS['WRITES'] ) === array() );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . ' FAILED of ' . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK - $PASS checks passed.\n";
