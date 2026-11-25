<?php
/**
 * Tests for the three optional blocks of the VanceHubClub member dashboard:
 * inc/hub-dashboard-features.php, and the switch wiring in inc/hub-dashboard.php
 * and inc/dashboard-functions.php that these blocks need.
 *
 * Same shape as hub-dashboard.test.php. The stub block of hub-layout.test.php is
 * evaluated first, so both suites share one set of WordPress fakes. The stubs
 * added here are for the storage and request boundary only: user meta, the
 * nonces, redirects and the user query. The check-in history is read and written
 * through user meta, so its checks inspect $GLOBALS['UMETA'] directly.
 *
 * WHAT IS COVERED
 *   1  pure helpers: member code (make, valid), check-in answers (clean, due),
 *      chart series (series, series_max, chart_svg), number display (num)
 *   2  member code storage: stored, absent, damaged, taken, lost race, user 0
 *   3  check-in POST handler: gating, validation, consent, the 6-day rule,
 *      the save row, the consent record, delete, failure paths
 *   4  render: check-in form and its states, flash messages, escaping, delete
 *      control, trends; results block; member number line on the home
 *   5  switch gating in vance_hub_dashboard_features_data(), and the data shape
 *      when the switches are on
 *   6  the POST handler's expired path: a page left open, a bad or foreign nonce
 *   7  check-in storage: history (damaged rows), append (validation, order, cap,
 *      failed write)
 *   8  the wiring: vance_known_tool_slugs() keeps weekly-check-in out
 *
 * A check whose label says what should happen and which fails on the current
 * code is a finding, not a broken check. The runner lists every failure.
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
	return preg_replace( '/[?&]vance_checkin=[^&]*/', '', $GLOBALS['CURRENT_URL'] );
}
function wp_safe_redirect( $url ) { throw new RedirectStop( $url ); }

// Every write records its name. The collector and the GET path must leave WRITES empty.
function fb_write( $name ) {
	$GLOBALS['WRITES'][] = $name;
	return true;
}
// Every read is logged, so a check can prove a path reads no check-in history.
function get_user_meta( $user_id, $key = '', $single = false ) {
	$GLOBALS['META_READS'][] = $key;
	$GLOBALS['SEQ'][]        = 'read:' . $key;
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
// The site secret. SALT lets a check change it.
function wp_salt( $scheme = 'auth' ) {
	$GLOBALS['SALT_SCHEMES'][] = $scheme;
	return isset( $GLOBALS['SALT'] ) ? $GLOBALS['SALT'] : 'test-salt-one';
}
// A failed write stores nothing and returns false, as WordPress does.
function update_user_meta( $user_id, $key, $value, $prev = '' ) {
	$GLOBALS['WRITES'][] = 'update_user_meta:' . $key;
	$GLOBALS['SEQ'][]    = 'write:' . $key;
	unset( $GLOBALS['STALE'][ $user_id ] );
	// UPDATE_FAILS: true fails every write, a key name fails that key only.
	if ( true === $GLOBALS['UPDATE_FAILS'] || $key === $GLOBALS['UPDATE_FAILS'] ) {
		// HISTORY_ON_FAILED: what a second request stored at the same moment.
		if ( '_sla_weekly_check_in_history' === $key && ! empty( $GLOBALS['HISTORY_ON_FAILED'] ) ) {
			$GLOBALS['UMETA'][ $user_id ][ $key ] = $GLOBALS['HISTORY_ON_FAILED'];
		}
		return false;
	}
	// As WordPress does: one level of slashes is removed from a stored string.
	$GLOBALS['UMETA'][ $user_id ][ $key ] = is_string( $value ) ? stripslashes( $value ) : $value;
	return true;
}
if ( ! function_exists( 'wp_slash' ) ) {
	function wp_slash( $value ) { return is_string( $value ) ? addslashes( $value ) : $value; }
}
function add_user_meta( $user_id, $key, $value, $unique = false ) {
	$GLOBALS['ADD_META_CALLS'][] = array( (int) $user_id, $key, $value, $unique );
	// A second request may store the key between our read and our add.
	if ( '' !== $GLOBALS['CONCURRENT_CODE'] ) {
		$GLOBALS['UMETA'][ $user_id ][ $key ] = $GLOBALS['CONCURRENT_CODE'];
		$GLOBALS['CONCURRENT_CODE']           = '';
	}
	// As WordPress does: a unique add fails when the key already exists.
	if ( $unique && isset( $GLOBALS['UMETA'][ $user_id ][ $key ] ) ) {
		return false;
	}
	$GLOBALS['UMETA'][ $user_id ][ $key ] = $value;
	return fb_write( 'add_user_meta:' . $key );
}
function delete_user_meta( $user_id, $key, $value = '' ) {
	$GLOBALS['WRITES'][] = 'delete_user_meta:' . $key;
	$GLOBALS['SEQ'][]    = 'write:' . $key;
	unset( $GLOBALS['STALE'][ $user_id ] );
	// DELETE_FAILS: a key name whose delete fails and removes nothing.
	if ( isset( $GLOBALS['DELETE_FAILS'] ) && $key === $GLOBALS['DELETE_FAILS'] ) {
		return false;
	}
	unset( $GLOBALS['UMETA'][ $user_id ][ $key ] );
	return true;
}

// The taken-code lookup. The first TAKEN_FIRST calls report the code as taken.
function get_users( $args ) {
	$GLOBALS['GET_USERS_CALLS'][] = $args;
	return count( $GLOBALS['GET_USERS_CALLS'] ) <= (int) $GLOBALS['TAKEN_FIRST'] ? array( 99 ) : array();
}

require_once $THEME . '/inc/hub-dashboard.php';
require_once $THEME . '/inc/hub-dashboard-features.php';

/* ---- helpers ------------------------------------------------------------------ */

$NOW = time();
$DAY = 86400;

/** Reset every global this suite reads. */
function fb_arrange( array $mods = array(), $logged_in = true, $uid = 7 ) {
	$GLOBALS['MODS']            = $mods;
	$GLOBALS['CAPS']            = array();
	$GLOBALS['LOGGED_IN']       = $logged_in;
	$GLOBALS['CURRENT_UID']     = $uid;
	$GLOBALS['CURRENT_URL']     = 'https://example.test/hub/';
	$GLOBALS['UMETA']           = array();
	$GLOBALS['WRITES']          = array();
	$GLOBALS['META_READS']      = array();
	$GLOBALS['UPDATE_FAILS']    = false;
	$GLOBALS['DELETE_FAILS']    = '';
	$GLOBALS['GET_USERS_CALLS'] = array();
	$GLOBALS['TAKEN_FIRST']     = 0;
	$GLOBALS['ADD_META_CALLS']  = array();
	$GLOBALS['CONCURRENT_CODE'] = '';
	$GLOBALS['SEQ']             = array();
	$GLOBALS['STALE']           = array();
	$GLOBALS['LOCK_RESULT']     = '1';
	$GLOBALS['ON_LOCK']         = null;
	$GLOBALS['LOCK_SQL']        = array();
	$GLOBALS['UNLOCK_SQL']      = array();
	$_GET                       = array();
	$_POST                      = array();
	$_SERVER['REQUEST_METHOD']  = 'POST';
}

/** A valid save: three answers, and the consent box ticked when $consent is true. */
function fb_post_save( array $answers = array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), $consent = true ) {
	$_POST = array(
		'vance_hub_checkin_nonce' => 'nonce-vance_hub_checkin',
		'vance_hub_checkin_do'    => 'save',
		'vhd_checkin'             => $answers,
	);
	if ( $consent ) {
		// As the form sends it: the tick, and the reference of the wording shown.
		$_POST['vhd_consent']     = '1';
		$_POST['vhd_consent_ref'] = hash( 'sha256', vance_hub_checkin_consent_text() );
	}
}

/** A delete request with the right nonce. */
function fb_post_delete() {
	$_POST = array(
		'vance_hub_checkin_nonce' => 'nonce-vance_hub_checkin',
		'vance_hub_checkin_do'    => 'delete',
	);
}

/** Give user 7 a check-in agreement to the wording in use now. Call after fb_arrange(). */
function fb_consented() {
	$GLOBALS['UMETA'][7]['_sla_consent_checkin']      = '1';
	$GLOBALS['UMETA'][7]['_sla_consent_checkin_at']   = '2026-10-01 09:00:00';
	$GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] = vance_hub_checkin_consent_text();
}

/** Run the POST handler. Returns the redirect URL, or null when it did not redirect. */
function fb_handle() {
	try {
		vance_hub_checkin_handle_post();
	} catch ( RedirectStop $stop ) {
		return $stop->url;
	}
	return null;
}

/** Position in SEQ of the first write, or the first read of check-in data. -1 when there is none. */
function fb_seq_first_data() {
	foreach ( $GLOBALS['SEQ'] as $i => $event ) {
		if ( 0 === strpos( $event, 'write:' ) || 0 === strpos( $event, 'read:_sla_consent_checkin' ) || 'read:_sla_weekly_check_in_history' === $event ) {
			return $i;
		}
	}
	return -1;
}

/** True when SEQ holds one lock and one unlock, and the unlock is the last event. */
function fb_seq_locked_once() {
	$counts = array_count_values( $GLOBALS['SEQ'] );
	return isset( $counts['lock'], $counts['unlock'] ) && 1 === $counts['lock'] && 1 === $counts['unlock'] && 'unlock' === end( $GLOBALS['SEQ'] );
}

/** One check-in history row. */
function fb_row( $ts, array $payload = array() ) {
	return array( 'id' => 'th_' . $ts, 'ts' => $ts, 'payload' => $payload );
}

/** One chart-series row as vance_hub_dashboard_series() returns it. */
function fb_point( $ts, $value, $max, $date = '' ) {
	return array( 'ts' => $ts, 'value' => $value, 'max' => $max, 'date' => $date );
}

/** The check-in view as vance_hub_dashboard_features_data() returns it. */
function fb_checkin_view( array $over = array() ) {
	return array_merge( array(
		'due'          => true,
		'last'         => '',
		'next'         => '',
		'count'        => 0,
		'questions'    => array( 'pain' => 'Pain question', 'bowel' => 'Bowel question', 'tired' => 'Tiredness question' ),
		'series'       => array( 'pain' => array(), 'bowel' => array(), 'tired' => array() ),
		'max'          => 4,
		'need_consent' => false,
		'consent'      => 'I agree.',
		'note'         => 'Not medical advice.',
		'nonce'        => 'nonce-vance_hub_checkin',
		'flash'        => '',
	), $over );
}

/** The home view with every block empty. The shape vance_hub_dashboard_data() returns. */
function fb_home_view( array $over = array() ) {
	return array_merge( array(
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
		'member_code'    => '',
		'results'        => null,
		'checkin'        => null,
	), $over );
}

/** A member object with an ID, as the features collector reads it. */
function fb_user( $id ) {
	return (object) array( 'ID' => $id );
}

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/* ===================================================================== */
section( '1. Constants and the storage key' );

check( 'constants: the check-in answer keys are pain, bowel, tired in that order', vance_hub_checkin_keys() === array( 'pain', 'bowel', 'tired' ) );
check( 'constants: the highest answer on the scale is 4', vance_hub_checkin_max() === 4 );
check( 'constants: the check-in storage key is _sla_weekly_check_in_history', vance_hub_checkin_meta_key() === '_sla_weekly_check_in_history' );
check( 'constants: the alphabet has 32 characters', strlen( vance_hub_member_code_alphabet() ) === 32 );
check( 'constants: the alphabet holds no 0, 1, I or O', strpbrk( vance_hub_member_code_alphabet(), '01IO' ) === false );

/* ===================================================================== */
section( '2. Pure helpers: member code' );

// make(): the random source is injected, so every character is known.
$CALLS = array();
$RAND_ZERO = function ( $lo, $hi ) use ( &$CALLS ) {
	$CALLS[] = array( $lo, $hi );
	return 0;
};
check( 'make: the random source is asked for 0..31, six times', vance_hub_member_code_make( $RAND_ZERO ) === 'VHC-222222' && $CALLS === array_fill( 0, 6, array( 0, 31 ) ) );

$SEQ = array( 0, 1, 2, 3, 4, 5 );
$I   = 0;
$RAND_SEQ = function ( $lo, $hi ) use ( &$SEQ, &$I ) {
	return $SEQ[ $I++ ];
};
check( 'make: successive random values give successive characters', vance_hub_member_code_make( $RAND_SEQ ) === 'VHC-234567' );

$RAND_TOP = function ( $lo, $hi ) { return 31; };
check( 'make: the top random value gives the last character, Z', vance_hub_member_code_make( $RAND_TOP ) === 'VHC-ZZZZZZ' );

$RAND_NEG = function ( $lo, $hi ) { return -5; };
check( 'make: a negative random value clamps to the first character', vance_hub_member_code_make( $RAND_NEG ) === 'VHC-222222' );

$RAND_HIGH = function ( $lo, $hi ) { return 40; };
check( 'make: a random value past the range clamps to the last character', vance_hub_member_code_make( $RAND_HIGH ) === 'VHC-ZZZZZZ' );

$RAND_STR = function ( $lo, $hi ) { return '3'; };
check( 'make: a numeric string from the random source is read as a number', vance_hub_member_code_make( $RAND_STR ) === 'VHC-555555' );

$RAND_FLOAT = function ( $lo, $hi ) { return 2.9; };
check( 'make: a float from the random source is truncated, 2.9 gives index 2', vance_hub_member_code_make( $RAND_FLOAT ) === 'VHC-444444' );

$RAND_NULL = function ( $lo, $hi ) { return null; };
check( 'make: a null from the random source gives the first character', vance_hub_member_code_make( $RAND_NULL ) === 'VHC-222222' );

// valid(): the format, character by character.
check( 'valid: a well-formed code is accepted', vance_hub_member_code_valid( 'VHC-234567' ) === true );
check( 'valid: the last character of the alphabet is accepted', vance_hub_member_code_valid( 'VHC-ZZZZZZ' ) === true );
check( 'valid: a 0 in the code is rejected', vance_hub_member_code_valid( 'VHC-0AAAAA' ) === false );
check( 'valid: a 1 in the code is rejected', vance_hub_member_code_valid( 'VHC-1AAAAA' ) === false );
check( 'valid: an I in the code is rejected', vance_hub_member_code_valid( 'VHC-IAAAAA' ) === false );
check( 'valid: an O in the code is rejected', vance_hub_member_code_valid( 'VHC-OAAAAA' ) === false );
check( 'valid: lowercase letters are rejected', vance_hub_member_code_valid( 'VHC-abcdef' ) === false );
check( 'valid: a five-character body is rejected', vance_hub_member_code_valid( 'VHC-23456' ) === false );
check( 'valid: a seven-character body is rejected', vance_hub_member_code_valid( 'VHC-2345678' ) === false );
check( 'valid: a code without the VHC- prefix is rejected', vance_hub_member_code_valid( '234567' ) === false );
check( 'valid: a wrong prefix is rejected', vance_hub_member_code_valid( 'ABC-234567' ) === false );
check( 'valid: a leading space is rejected', vance_hub_member_code_valid( ' VHC-234567' ) === false );
check( 'valid: an empty string is rejected', vance_hub_member_code_valid( '' ) === false );
check( 'valid: null is rejected', vance_hub_member_code_valid( null ) === false );
check( 'valid: an integer is rejected', vance_hub_member_code_valid( 234567 ) === false );
check( 'valid: an array is rejected', vance_hub_member_code_valid( array( 'VHC-234567' ) ) === false );
check( 'valid: a trailing newline after a good code is rejected', vance_hub_member_code_valid( "VHC-234567\n" ) === false );

/* ===================================================================== */
section( '3. Pure helpers: check-in answers' );

check( 'clean: three valid answers come back as ints', vance_hub_checkin_clean( array( 'pain' => '0', 'bowel' => '4', 'tired' => '2' ) ) === array( 'pain' => 0, 'bowel' => 4, 'tired' => 2 ) );
check( 'clean: a missing key gives null', vance_hub_checkin_clean( array( 'pain' => '1', 'bowel' => '2' ) ) === null );
check( 'clean: a key set to null gives null', vance_hub_checkin_clean( array( 'pain' => null, 'bowel' => '2', 'tired' => '1' ) ) === null );
check( 'clean: an answer of 5 is out of range and gives null', vance_hub_checkin_clean( array( 'pain' => '5', 'bowel' => '2', 'tired' => '1' ) ) === null );
check( 'clean: a negative answer gives null', vance_hub_checkin_clean( array( 'pain' => '-1', 'bowel' => '2', 'tired' => '1' ) ) === null );
check( 'clean: a decimal answer gives null', vance_hub_checkin_clean( array( 'pain' => '1.5', 'bowel' => '2', 'tired' => '1' ) ) === null );
check( 'clean: a zero-padded answer gives null', vance_hub_checkin_clean( array( 'pain' => '01', 'bowel' => '2', 'tired' => '1' ) ) === null );
check( 'clean: an empty answer gives null', vance_hub_checkin_clean( array( 'pain' => '', 'bowel' => '2', 'tired' => '1' ) ) === null );
check( 'clean: a space-padded answer gives null', vance_hub_checkin_clean( array( 'pain' => ' 1', 'bowel' => '2', 'tired' => '1' ) ) === null );
check( 'clean: an array as an answer gives null', vance_hub_checkin_clean( array( 'pain' => array( '1' ), 'bowel' => '2', 'tired' => '1' ) ) === null );
check( 'clean: an int input 3 is accepted', vance_hub_checkin_clean( array( 'pain' => 3, 'bowel' => 4, 'tired' => 0 ) ) === array( 'pain' => 3, 'bowel' => 4, 'tired' => 0 ) );
check( 'clean: an int input 5 gives null', vance_hub_checkin_clean( array( 'pain' => 5, 'bowel' => 4, 'tired' => 0 ) ) === null );
check( 'clean: an int input -1 gives null', vance_hub_checkin_clean( array( 'pain' => -1, 'bowel' => 4, 'tired' => 0 ) ) === null );
check( 'clean: an extra key is dropped from the result', vance_hub_checkin_clean( array( 'pain' => '1', 'bowel' => '2', 'tired' => '3', 'evil' => '4' ) ) === array( 'pain' => 1, 'bowel' => 2, 'tired' => 3 ) );
check( 'clean: a null input gives null', vance_hub_checkin_clean( null ) === null );
check( 'clean: a string input gives null', vance_hub_checkin_clean( '2' ) === null );
check( 'clean: an empty array gives null', vance_hub_checkin_clean( array() ) === null );

// due(): six days, not seven.
$LAST = 1000000;
check( 'due: no check-in yet (0) is due', vance_hub_checkin_due( 0, $LAST ) === true );
check( 'due: a negative last time is due', vance_hub_checkin_due( -5, $LAST ) === true );
check( 'due: one hour after the last check-in is not due', vance_hub_checkin_due( $LAST, $LAST + 3600 ) === false );
check( 'due: five days after the last check-in is not due', vance_hub_checkin_due( $LAST, $LAST + 5 * 86400 ) === false );
check( 'due: one second short of six days is not due', vance_hub_checkin_due( $LAST, $LAST + 6 * 86400 - 1 ) === false );
check( 'due: exactly six days after the last check-in is due', vance_hub_checkin_due( $LAST, $LAST + 6 * 86400 ) === true );
check( 'due: seven days after the last check-in is due', vance_hub_checkin_due( $LAST, $LAST + 7 * 86400 ) === true );
check( 'due: a numeric string last time is read', vance_hub_checkin_due( '1000000', $LAST + 6 * 86400 ) === true );

/* ===================================================================== */
section( '4. Pure helpers: chart series' );

check( 'series: one good row gives one point with value, max and ts', vance_hub_dashboard_series( array( fb_row( 100, array( 'score' => 3, 'maxScore' => 8 ) ) ), 'score', 4 ) === array( array( 'ts' => 100, 'value' => 3.0, 'max' => 8.0 ) ) );
check( 'series: a row with a non-numeric score is skipped', vance_hub_dashboard_series( array( fb_row( 100, array( 'score' => 'high' ) ) ), 'score', 4 ) === array() );
check( 'series: a row with no score key is skipped', vance_hub_dashboard_series( array( fb_row( 100, array( 'note' => 'x' ) ) ), 'score', 4 ) === array() );
check( 'series: a row with a null score is skipped', vance_hub_dashboard_series( array( fb_row( 100, array( 'score' => null ) ) ), 'score', 4 ) === array() );
check( 'series: a text-only snapshot row is skipped, a scored row is kept', vance_hub_dashboard_series( array( fb_row( 50, array( 'text' => 'old' ) ), fb_row( 60, array( 'score' => 2 ) ) ), 'score', 4 ) === array( array( 'ts' => 60, 'value' => 2.0, 'max' => 4.0 ) ) );
check( 'series: a row with ts 0 is skipped', vance_hub_dashboard_series( array( fb_row( 0, array( 'score' => 2 ) ) ), 'score', 4 ) === array() );
check( 'series: a row with a negative ts is skipped', vance_hub_dashboard_series( array( fb_row( -5, array( 'score' => 2 ) ) ), 'score', 4 ) === array() );
check( 'series: a row with a non-numeric ts is skipped', vance_hub_dashboard_series( array( array( 'ts' => 'abc', 'payload' => array( 'score' => 2 ) ) ), 'score', 4 ) === array() );
check( 'series: a row whose payload is not an array is skipped', vance_hub_dashboard_series( array( array( 'ts' => 5, 'payload' => '3' ) ), 'score', 4 ) === array() );
check( 'series: a row with no payload is skipped', vance_hub_dashboard_series( array( array( 'ts' => 5 ) ), 'score', 4 ) === array() );
check( 'series: non-array rows are skipped', vance_hub_dashboard_series( array( 'junk', null, 7 ), 'score', 4 ) === array() );
check( 'series: a null input gives no points', vance_hub_dashboard_series( null, 'score', 4 ) === array() );
check( 'series: a value above the maximum is clamped down to it', vance_hub_dashboard_series( array( fb_row( 1, array( 'score' => 9, 'maxScore' => 8 ) ) ), 'score', 4 )[0]['value'] === 8.0 );
check( 'series: a negative value is clamped up to zero', vance_hub_dashboard_series( array( fb_row( 1, array( 'score' => -2 ) ) ), 'score', 4 )[0]['value'] === 0.0 );
check( 'series: with no maxScore, the default maximum is used and the value clamps to it', vance_hub_dashboard_series( array( fb_row( 1, array( 'score' => 9 ) ) ), 'score', 4 ) === array( array( 'ts' => 1, 'value' => 4.0, 'max' => 4.0 ) ) );
check( 'series: a maxScore of 0 falls back to the default maximum', vance_hub_dashboard_series( array( fb_row( 1, array( 'score' => 2, 'maxScore' => 0 ) ) ), 'score', 8 )[0]['max'] === 8.0 );
check( 'series: a negative maxScore falls back to the default maximum', vance_hub_dashboard_series( array( fb_row( 1, array( 'score' => 2, 'maxScore' => -3 ) ) ), 'score', 8 )[0]['max'] === 8.0 );
check( 'series: a non-numeric maxScore falls back to the default maximum', vance_hub_dashboard_series( array( fb_row( 1, array( 'score' => 2, 'maxScore' => 'many' ) ) ), 'score', 8 )[0]['max'] === 8.0 );
check( 'series: a row maxScore of 10 wins over a default of 8', vance_hub_dashboard_series( array( fb_row( 1, array( 'score' => 2, 'maxScore' => 10 ) ) ), 'score', 8 )[0]['max'] === 10.0 );
check( 'series: a clamped value uses its own row maximum', vance_hub_dashboard_series( array( fb_row( 1, array( 'score' => 12, 'maxScore' => 10 ) ) ), 'score', 8 )[0]['value'] === 10.0 );
check( 'series: rows given newest first come back oldest first', array_column( vance_hub_dashboard_series( array( fb_row( 300, array( 'score' => 1 ) ), fb_row( 100, array( 'score' => 1 ) ), fb_row( 200, array( 'score' => 1 ) ) ), 'score', 4 ), 'ts' ) === array( 100, 200, 300 ) );
check( 'series: a limit keeps the newest rows, in date order', array_column( vance_hub_dashboard_series( array( fb_row( 10, array( 'score' => 1 ) ), fb_row( 40, array( 'score' => 1 ) ), fb_row( 20, array( 'score' => 1 ) ), fb_row( 30, array( 'score' => 1 ) ) ), 'score', 4, 2 ), 'ts' ) === array( 30, 40 ) );
check( 'series: the default limit keeps the newest 12 of 13 rows', array_column( vance_hub_dashboard_series( array_map( function ( $t ) { return fb_row( $t, array( 'score' => 1 ) ); }, range( 1, 13 ) ), 'score', 4 ), 'ts' ) === range( 2, 13 ) );
check( 'series: a limit of 0 is raised to 1 and keeps only the newest row', array_column( vance_hub_dashboard_series( array( fb_row( 10, array( 'score' => 1 ) ), fb_row( 20, array( 'score' => 1 ) ) ), 'score', 4, 0 ), 'ts' ) === array( 20 ) );
check( 'series: a numeric string ts is read as an int', vance_hub_dashboard_series( array( array( 'ts' => '50', 'payload' => array( 'score' => 1 ) ) ), 'score', 4 )[0]['ts'] === 50 );

check( 'series_max: no points gives 0', vance_hub_dashboard_series_max( array() ) === 0.0 );
check( 'series_max: the largest point maximum wins', vance_hub_dashboard_series_max( array( array( 'max' => 4.0 ), array( 'max' => 10.0 ), array( 'max' => 8.0 ) ) ) === 10.0 );
check( 'series_max: a point with no max is ignored', vance_hub_dashboard_series_max( array( array( 'value' => 5 ), array( 'max' => 3.0 ) ) ) === 3.0 );
check( 'series_max: a null input gives 0', vance_hub_dashboard_series_max( null ) === 0.0 );

check( 'num: a whole number has no decimal point', vance_hub_dashboard_num( 3.0 ) === '3' );
check( 'num: a half keeps one decimal', vance_hub_dashboard_num( 2.5 ) === '2.5' );
check( 'num: zero is "0"', vance_hub_dashboard_num( 0.0 ) === '0' );
check( 'num: 2.44 rounds to one decimal, 2.4', vance_hub_dashboard_num( 2.44 ) === '2.4' );
check( 'num: 0.04 rounds to zero, "0"', vance_hub_dashboard_num( 0.04 ) === '0' );
check( 'num: a large whole number has no separator', vance_hub_dashboard_num( 1234.0 ) === '1234' );

check( 'chart_svg: no points gives empty string', vance_hub_dashboard_chart_svg( array() ) === '' );
check( 'chart_svg: one point gives empty string (one point is not a trend)', vance_hub_dashboard_chart_svg( array( array( 'value' => 2.0, 'max' => 4.0 ) ) ) === '' );
check( 'chart_svg: a zero scale gives empty string', vance_hub_dashboard_chart_svg( array( array( 'value' => 0, 'max' => 0 ), array( 'value' => 0, 'max' => 0 ) ) ) === '' );

$SVG2 = vance_hub_dashboard_chart_svg( array( array( 'value' => 0.0, 'max' => 4.0 ), array( 'value' => 4.0, 'max' => 4.0 ) ) );
check( 'chart_svg: two points, the wrapper has the 300 by 90 viewBox', strpos( $SVG2, '<svg class="vhd-chart__svg" viewBox="0 0 300 90"' ) === 0 );
check( 'chart_svg: two points, the line runs from the bottom-left to the top-right', strpos( $SVG2, 'points="10,80 290,10"' ) !== false );
check( 'chart_svg: two points, the dots are one zero-length segment each', strpos( $SVG2, 'd="M10,80h0M290,10h0"' ) !== false );

$SVG3 = vance_hub_dashboard_chart_svg( array( array( 'value' => 0.0, 'max' => 4.0 ), array( 'value' => 2.0, 'max' => 4.0 ), array( 'value' => 4.0, 'max' => 4.0 ) ) );
check( 'chart_svg: three points, the middle point is at the midline', strpos( $SVG3, 'points="10,80 150,45 290,10"' ) !== false );
check( 'chart_svg: three points, the dots follow the same coordinates', strpos( $SVG3, 'd="M10,80h0M150,45h0M290,10h0"' ) !== false );

$SVG_SCALE = vance_hub_dashboard_chart_svg( array( array( 'value' => 2.0, 'max' => 4.0 ), array( 'value' => 5.0, 'max' => 10.0 ) ) );
check( 'chart_svg: the scale is the largest maximum, so both points share it', strpos( $SVG_SCALE, 'points="10,66 290,45"' ) !== false );

$SVG7 = vance_hub_dashboard_chart_svg( array_map( function ( $v ) { return array( 'value' => $v, 'max' => 6.0 ); }, range( 0, 6 ) ) );
check( 'chart_svg: uneven spacing is rounded to one decimal, the second x is 56.7', strpos( $SVG7, 'points="10,80 56.7,' ) !== false );
check( 'chart_svg: no NAN or INF in the output', strpos( $SVG7, 'NAN' ) === false && strpos( $SVG7, 'INF' ) === false );

/* ===================================================================== */
section( '5. Member code storage: vance_hub_member_code_for()' );

fb_arrange();
$GLOBALS['UMETA'][7]['_sla_member_code'] = 'VHC-234567';
check( 'member code: a stored valid code is returned as it is', vance_hub_member_code_for( 7 ) === 'VHC-234567' );
check( 'member code: a stored valid code writes nothing', $GLOBALS['WRITES'] === array() );
check( 'member code: a stored valid code does not look for taken codes', $GLOBALS['GET_USERS_CALLS'] === array() );

fb_arrange();
$MADE = vance_hub_member_code_for( 7 );
check( 'member code: with none stored, a new VHC- code of six characters is made', preg_match( '/^VHC-[2-9A-HJ-NP-Z]{6}\z/', $MADE ) === 1 );
check( 'member code: the new code is stored under _sla_member_code', $GLOBALS['UMETA'][7]['_sla_member_code'] === $MADE );
check( 'member code: with none stored, one unique add is the only write', $GLOBALS['WRITES'] === array( 'add_user_meta:_sla_member_code' ) );
check( 'member code: the add asks for a unique value', $GLOBALS['ADD_META_CALLS'][0][3] === true );
check( 'member code: the taken check uses the meta key and the new candidate', $GLOBALS['GET_USERS_CALLS'][0]['meta_key'] === '_sla_member_code' && $GLOBALS['GET_USERS_CALLS'][0]['meta_value'] === $MADE );

fb_arrange();
$GLOBALS['UMETA'][7]['_sla_member_code'] = 'VHC-0OOOOO';
$REPLACED = vance_hub_member_code_for( 7 );
check( 'member code: a damaged stored value is replaced by a valid code', preg_match( '/^VHC-[2-9A-HJ-NP-Z]{6}\z/', $REPLACED ) === 1 );
check( 'member code: the damaged value is overwritten with the new code', $GLOBALS['UMETA'][7]['_sla_member_code'] === $REPLACED && $REPLACED !== 'VHC-0OOOOO' );
check( 'member code: a damaged value is replaced by an update, not a second add', $GLOBALS['WRITES'] === array( 'update_user_meta:_sla_member_code' ) );

fb_arrange();
$GLOBALS['UMETA'][7]['_sla_member_code'] = 'vhc-lower';
check( 'member code: a lowercase stored value is also replaced', vance_hub_member_code_for( 7 ) !== 'vhc-lower' && $GLOBALS['UMETA'][7]['_sla_member_code'] !== 'vhc-lower' );

fb_arrange();
$GLOBALS['TAKEN_FIRST'] = 1;
$TRIED = vance_hub_member_code_for( 7 );
check( 'member code: a code reported as taken is skipped and one more check is made', count( $GLOBALS['GET_USERS_CALLS'] ) === 2 );
check( 'member code: the code that was stored is the second candidate', $GLOBALS['UMETA'][7]['_sla_member_code'] === $GLOBALS['GET_USERS_CALLS'][1]['meta_value'] && $TRIED === $GLOBALS['GET_USERS_CALLS'][1]['meta_value'] );
check( 'member code: only one add happens, and it is never the taken candidate', count( $GLOBALS['ADD_META_CALLS'] ) === 1 && $GLOBALS['ADD_META_CALLS'][0][2] !== $GLOBALS['GET_USERS_CALLS'][0]['meta_value'] );

fb_arrange();
$GLOBALS['TAKEN_FIRST'] = 99;
$NONE = vance_hub_member_code_for( 7 );
check( 'member code: five taken candidates in a row give an empty string', $NONE === '' );
check( 'member code: five taken candidates ask the taken check five times', count( $GLOBALS['GET_USERS_CALLS'] ) === 5 );
check( 'member code: five taken candidates store nothing', $GLOBALS['WRITES'] === array() && $GLOBALS['ADD_META_CALLS'] === array() );

fb_arrange();
$GLOBALS['CONCURRENT_CODE'] = 'VHC-ABCDEF';
check( 'member code: a code another request stored first is the one returned', vance_hub_member_code_for( 7 ) === 'VHC-ABCDEF' );
check( 'member code: a lost race writes nothing more of its own', $GLOBALS['WRITES'] === array() );

fb_arrange();
check( 'member code: user id 0 gives an empty string', vance_hub_member_code_for( 0 ) === '' );
check( 'member code: user id 0 reads, writes and queries nothing', $GLOBALS['WRITES'] === array() && $GLOBALS['GET_USERS_CALLS'] === array() && $GLOBALS['ADD_META_CALLS'] === array() );
check( 'member code: a negative user id gives an empty string', vance_hub_member_code_for( -4 ) === '' );

/* ===================================================================== */
section( '6. Check-in POST handler: gating' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save();
check( 'control: a good save with the switch on redirects with vance_checkin=saved', fb_handle() === 'https://example.test/hub/?vance_checkin=saved' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save();
unset( $_POST['vance_hub_checkin_nonce'] );
check( 'gating: no nonce field does nothing (no redirect)', fb_handle() === null );
check( 'gating: no nonce field writes nothing', $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save();
$_POST['vance_hub_checkin_nonce'] = array( 'nonce-vance_hub_checkin' );
check( 'gating: an array nonce does nothing and does not fail', fb_handle() === null && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ), false );
fb_post_save();
check( 'gating: a visitor who is not logged in does nothing', fb_handle() === null && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save();
$_SERVER['REQUEST_METHOD'] = 'GET';
check( 'gating: a GET request does nothing', fb_handle() === null && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_hub_dash_checkin' => false ) );
fb_post_save();
check( 'gating: the switch off does nothing, even for a good save', fb_handle() === null && $GLOBALS['WRITES'] === array() );

fb_arrange();
fb_post_save();
check( 'gating: the switch unset (default off) does nothing', fb_handle() === null && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_hub_dash_checkin' => false ) );
fb_post_delete();
check( 'gating: the switch off also blocks delete', fb_handle() === null && $GLOBALS['WRITES'] === array() );

/* ===================================================================== */
section( '6b. Check-in POST handler: a page left open (expired nonce)' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save();
$_POST['vance_hub_checkin_nonce'] = 'forged';
check( 'expired: a bad nonce redirects with vance_checkin=expired', fb_handle() === 'https://example.test/hub/?vance_checkin=expired' );
check( 'expired: a bad nonce writes nothing', $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save();
$_POST['vance_hub_checkin_nonce'] = 'nonce-vance_hub_other';
check( 'expired: a nonce made for another action redirects with vance_checkin=expired', fb_handle() === 'https://example.test/hub/?vance_checkin=expired' );
check( 'expired: a nonce made for another action writes nothing', $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save();
$_POST['vance_hub_checkin_nonce'] = '';
check( 'expired: an empty nonce redirects with vance_checkin=expired', fb_handle() === 'https://example.test/hub/?vance_checkin=expired' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save( array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), true );
$_POST['vance_hub_checkin_nonce'] = 'forged';
fb_handle();
check( 'expired: a bad nonce writes no consent record', ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin'] ) && ! isset( $GLOBALS['UMETA'][7]['_sla_consent_health'] ) );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $NOW - 2 * $DAY, array( 'pain' => 1, 'bowel' => 1, 'tired' => 1, 'kind' => 'check-in' ) ) );
fb_post_delete();
$_POST['vance_hub_checkin_nonce'] = 'forged';
fb_handle();
check( 'expired: a bad nonce on delete keeps the stored history', $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] === array( fb_row( $NOW - 2 * $DAY, array( 'pain' => 1, 'bowel' => 1, 'tired' => 1, 'kind' => 'check-in' ) ) ) );

fb_arrange( array( 'vance_hub_dash_checkin' => false ) );
fb_post_save();
$_POST['vance_hub_checkin_nonce'] = 'forged';
check( 'expired: with the switch off a bad nonce does nothing (no redirect)', fb_handle() === null && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ), false );
fb_post_save();
$_POST['vance_hub_checkin_nonce'] = 'forged';
check( 'expired: a visitor who is not logged in with a bad nonce does nothing (no redirect)', fb_handle() === null && $GLOBALS['WRITES'] === array() );

/* ===================================================================== */
section( '7. Check-in POST handler: validation and the 6-day rule' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save( array( 'pain' => '2', 'bowel' => '3' ) );
check( 'invalid: a missing answer redirects with vance_checkin=invalid', fb_handle() === 'https://example.test/hub/?vance_checkin=invalid' );
check( 'invalid: a missing answer writes nothing', $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save( array( 'pain' => '5', 'bowel' => '3', 'tired' => '1' ) );
check( 'invalid: an answer of 5 gives invalid and writes nothing', fb_handle() === 'https://example.test/hub/?vance_checkin=invalid' && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save( array( 'pain' => '-1', 'bowel' => '3', 'tired' => '1' ) );
check( 'invalid: a negative answer gives invalid and writes nothing', fb_handle() === 'https://example.test/hub/?vance_checkin=invalid' && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save( array( 'pain' => '1', 'bowel' => '3', 'tired' => '1' ), false );
$_POST['vance_hub_checkin_do'] = 'drop';
check( 'invalid: an unknown action gives invalid and writes nothing', fb_handle() === 'https://example.test/hub/?vance_checkin=invalid' && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save( array( 'pain' => '2', 'bowel' => '3' ), false );
check( 'invalid is checked before consent: bad answers and no consent give invalid', fb_handle() === 'https://example.test/hub/?vance_checkin=invalid' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save( array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), false );
check( 'consent: good answers with no consent box redirect with vance_checkin=consent', fb_handle() === 'https://example.test/hub/?vance_checkin=consent' );
check( 'consent: no consent writes nothing, not even the consent record', $GLOBALS['WRITES'] === array() );

// The general health flag is set by a save in any tool, with other wording.
// It must not stand in for the check-in agreement.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
$GLOBALS['UMETA'][7]['_sla_consent_health'] = '1';
fb_post_save( array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), false );
check( 'consent: the general health flag alone does not count, the result is consent', fb_handle() === 'https://example.test/hub/?vance_checkin=consent' && $GLOBALS['WRITES'] === array() );

// An agreement to old wording does not cover new wording.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_consented();
$GLOBALS['MODS']['vance_hub_checkin_consent'] = 'New wording.';
fb_post_save( array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), false );
check( 'consent: changed wording asks again, the result is consent', fb_handle() === 'https://example.test/hub/?vance_checkin=consent' && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_consented();
$GLOBALS['MODS']['vance_hub_checkin_consent'] = 'New wording.';
fb_post_save( array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), true );
fb_handle();
check( 'consent: agreeing to new wording stores the new wording', $GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] === 'New wording.' && $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'] === '2026-11-18 12:00:00' );

check( 'consent_ok: flag and the same wording is true', vance_hub_checkin_consent_ok( '1', 'A', 'A' ) === true );
check( 'consent_ok: no flag is false', vance_hub_checkin_consent_ok( '', 'A', 'A' ) === false );
check( 'consent_ok: other wording is false', vance_hub_checkin_consent_ok( '1', 'A', 'B' ) === false );
check( 'consent_ok: empty stored wording is false, also against empty current wording', vance_hub_checkin_consent_ok( '1', '', '' ) === false );
check( 'consent_ok: non-string stored wording is false', vance_hub_checkin_consent_ok( '1', array( 'A' ), 'A' ) === false );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $NOW - 2 * $DAY, array( 'pain' => 1, 'bowel' => 1, 'tired' => 1, 'kind' => 'check-in' ) ) );
check( 'early: a check-in 2 days ago redirects with vance_checkin=early', fb_handle() === 'https://example.test/hub/?vance_checkin=early' );
check( 'early: a check-in 2 days ago writes nothing', $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $NOW - 5 * $DAY, array( 'pain' => 1, 'bowel' => 1, 'tired' => 1, 'kind' => 'check-in' ) ) );
check( 'early: a check-in 5 days ago is still early', fb_handle() === 'https://example.test/hub/?vance_checkin=early' && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $NOW - 7 * $DAY, array( 'pain' => 1, 'bowel' => 1, 'tired' => 1, 'kind' => 'check-in' ) ) );
fb_consented();
check( 'due: a check-in 7 days ago lets the next one save', fb_handle() === 'https://example.test/hub/?vance_checkin=saved' );

/* ===================================================================== */
section( '8. Check-in POST handler: the save, the consent record, delete' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save( array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), true );
fb_handle();
check( 'save: exactly one check-in row is stored for user 7', count( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) === 1 );
check( 'save: the row holds the three answers as ints and kind check-in', $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'][0]['payload'] === array( 'pain' => 2, 'bowel' => 3, 'tired' => 1, 'kind' => 'check-in' ) );
check( 'save: the row is stamped with the time of the save', $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'][0]['ts'] >= $NOW && $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'][0]['ts'] <= time() );
check( 'save: a new member gets the consent record once (flag, time, wording), then the history write', $GLOBALS['WRITES'] === array( 'update_user_meta:_sla_consent_checkin', 'update_user_meta:_sla_consent_checkin_at', 'update_user_meta:_sla_consent_checkin_text', 'update_user_meta:_sla_weekly_check_in_history' ) );
check( 'save: the consent flag is 1, the time is the stamped time, the wording is the wording shown', $GLOBALS['UMETA'][7]['_sla_consent_checkin'] === '1' && $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'] === '2026-11-18 12:00:00' && $GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] === vance_hub_checkin_consent_text() && '' !== vance_hub_checkin_consent_text() );
check( 'save: the general health flag is not written', ! isset( $GLOBALS['UMETA'][7]['_sla_consent_health'] ) );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_consented();
fb_post_save( array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), false );
fb_handle();
check( 'save: a member who already consented gets no second consent record', $GLOBALS['WRITES'] === array( 'update_user_meta:_sla_weekly_check_in_history' ) && $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'] === '2026-10-01 09:00:00' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
// Only the history write fails. The consent writes succeed first, so this goes
// red if the consent record is not taken back after the failed save.
$GLOBALS['UPDATE_FAILS'] = '_sla_weekly_check_in_history';
fb_post_save();
check( 'save: a failed history write redirects with vance_checkin=failed, not invalid', fb_handle() === 'https://example.test/hub/?vance_checkin=failed' );
check( 'save: a failed history write leaves no consent record', ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin'] ) && ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'] ) && ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] ) );
check( 'save: a failed history write stores no answers', ! isset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) );

// A member who withdrew has an old time and wording on record. A failed save
// must put that record back as it was, not leave a new agreement.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
$GLOBALS['UMETA'][7]['_sla_consent_checkin_at']   = '2026-09-01 08:00:00';
$GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] = 'Old "wording" C:\\x.';
$GLOBALS['UPDATE_FAILS'] = '_sla_weekly_check_in_history';
fb_post_save();
fb_handle();
check( 'save: a failed history write puts the old record back: no flag, old time, old wording', ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin'] ) && isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'], $GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] ) && $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'] === '2026-09-01 08:00:00' && $GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] === 'Old "wording" C:\\x.' );

// The consent record cannot be stored: no answers may be stored.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
$GLOBALS['UPDATE_FAILS'] = '_sla_consent_checkin_text';
fb_post_save();
check( 'save: a failed consent write gives failed', fb_handle() === 'https://example.test/hub/?vance_checkin=failed' );
check( 'save: a failed consent write stores no answers and attempts no history write', ! isset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) && ! in_array( 'update_user_meta:_sla_weekly_check_in_history', $GLOBALS['WRITES'], true ) );
check( 'save: a failed consent write leaves no flag', ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin'] ) );

// The form showed wording A. The wording in use at the save is B.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save();
$GLOBALS['MODS']['vance_hub_checkin_consent'] = 'Wording B.';
check( 'changed: a tick for other wording gives changed', fb_handle() === 'https://example.test/hub/?vance_checkin=changed' );
check( 'changed: a tick for other wording writes nothing', $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save();
unset( $_POST['vhd_consent_ref'] );
check( 'changed: a tick with no reference gives changed and writes nothing', fb_handle() === 'https://example.test/hub/?vance_checkin=changed' && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_post_save();
$_POST['vhd_consent_ref'] = array( hash( 'sha256', vance_hub_checkin_consent_text() ) );
check( 'changed: a reference sent as an array gives changed and writes nothing', fb_handle() === 'https://example.test/hub/?vance_checkin=changed' && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_consented();
fb_post_save( array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), false );
check( 'changed: a member with a valid agreement needs no reference', fb_handle() === 'https://example.test/hub/?vance_checkin=saved' );

check( 'consent_ref: the reference is the sha256 of the wording', vance_hub_checkin_consent_ref( 'A' ) === hash( 'sha256', 'A' ) );
check( 'consent_ref_ok: the right reference is true, another wording and non-strings are false', vance_hub_checkin_consent_ref_ok( 'A', hash( 'sha256', 'A' ) ) === true && vance_hub_checkin_consent_ref_ok( 'A', hash( 'sha256', 'B' ) ) === false && vance_hub_checkin_consent_ref_ok( 'A', null ) === false && vance_hub_checkin_consent_ref_ok( 'A', '' ) === false );

// Delete that removes nothing: the member must not be told the answers are gone.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_consented();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $NOW - 2 * $DAY, array( 'pain' => 1 ) ) );
$GLOBALS['DELETE_FAILS'] = '_sla_weekly_check_in_history';
fb_post_delete();
check( 'delete: a delete that removes nothing gives kept, not deleted', fb_handle() === 'https://example.test/hub/?vance_checkin=kept' );
check( 'delete: a delete that removes nothing keeps the agreement and writes no withdrawal time', isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin'] ) && $GLOBALS['UMETA'][7]['_sla_consent_checkin'] === '1' &&! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_withdrawn_at'] ) );
check( 'checkin html: the kept and changed flashes are warning lines', strpos( vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'flash' => 'kept' ) ) ), 'vhd-flash--warn" role="status">Not completed. The Hub could not delete your answers or withdraw your agreement.' ) !== false && strpos( vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'flash' => 'changed' ) ) ), 'vhd-flash--warn" role="status">Not saved. The agreement text changed.' ) !== false );

// The answers are deleted and the withdrawal fails: the member must not be told
// that the agreement is withdrawn.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_consented();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $NOW - 2 * $DAY, array( 'pain' => 1 ) ) );
$GLOBALS['DELETE_FAILS'] = '_sla_consent_checkin';
fb_post_delete();
check( 'delete: a withdrawal that does not happen gives kept, not deleted', fb_handle() === 'https://example.test/hub/?vance_checkin=kept' );
check( 'delete: a withdrawal that does not happen writes no withdrawal time', ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_withdrawn_at'] ) );

// The time of the agreement cannot be stored: the record is not valid, so no
// answers are stored and nothing of the new agreement stays.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
$GLOBALS['UPDATE_FAILS'] = '_sla_consent_checkin_at';
fb_post_save();
check( 'save: a failed agreement-time write gives failed', fb_handle() === 'https://example.test/hub/?vance_checkin=failed' );
check( 'save: a failed agreement-time write stores no answers, no flag and no wording', ! isset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) && ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin'] ) && ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] ) );

// An older agreement time must not stand in for the new one.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
$GLOBALS['UMETA'][7]['_sla_consent_checkin_at'] = '2026-09-01 08:00:00';
$GLOBALS['UPDATE_FAILS'] = '_sla_consent_checkin_at';
fb_post_save();
check( 'save: an older agreement time does not pass as the new one', fb_handle() === 'https://example.test/hub/?vance_checkin=failed' && ! isset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) );

// Wording changed: flag 1 with old wording. A failed save puts all three back.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_consented();
$GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] = 'Old wording.';
$GLOBALS['UPDATE_FAILS'] = '_sla_weekly_check_in_history';
fb_post_save();
fb_handle();
check( 'save: after a wording change, a failed save puts the old flag, time and wording back', isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin'], $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'], $GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] ) && $GLOBALS['UMETA'][7]['_sla_consent_checkin'] === '1' && $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'] === '2026-10-01 09:00:00' && $GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] === 'Old wording.' );

// A member with a valid agreement: a failed save touches no consent key.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_consented();
$GLOBALS['UPDATE_FAILS'] = '_sla_weekly_check_in_history';
fb_post_save( array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), false );
check( 'save: with a valid agreement, a failed save gives failed and writes only the history attempt', fb_handle() === 'https://example.test/hub/?vance_checkin=failed' && $GLOBALS['WRITES'] === array( 'update_user_meta:_sla_weekly_check_in_history' ) );

// Two requests at one time: the other one saved answers after this one
// started. This one fails, and must not take back the agreement they rest on.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
$GLOBALS['UPDATE_FAILS']      = '_sla_weekly_check_in_history';
$GLOBALS['HISTORY_ON_FAILED'] = array( fb_row( time() + 5, array( 'pain' => 1, 'bowel' => 1, 'tired' => 1, 'kind' => 'check-in' ) ) );
fb_post_save();
fb_handle();
check( 'save: a failed save keeps the agreement when newer answers from a second request exist', isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin'] ) && $GLOBALS['UMETA'][7]['_sla_consent_checkin'] === '1' && isset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) );
$GLOBALS['HISTORY_ON_FAILED'] = null;

$C_REF = vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'need_consent' => true, 'consent' => 'I agree to storage.' ) ) );
check( 'checkin html: the consent box comes with the reference of the wording shown', strpos( $C_REF, 'name="vhd_consent_ref" value="' . hash( 'sha256', 'I agree to storage.' ) . '"' ) !== false );
check( 'checkin html: no consent box, no reference', strpos( vance_hub_dashboard_checkin_html( fb_checkin_view() ), 'vhd_consent_ref' ) === false );
check( 'checkin html: the failed flash is a warning line with its text', strpos( vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'flash' => 'failed' ) ) ), 'vhd-flash--warn" role="status">Not saved. The Hub could not store your answers.' ) !== false );

// Consent wording with a backslash and quotes: stored as shown, so the member
// is not asked again on the next view.
$SLASHY = 'I agree (see C:\\hub\\terms and "the policy").';
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true, 'vance_hub_checkin_consent' => $SLASHY ) );
fb_post_save( array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), true );
fb_handle();
check( 'consent: wording with a backslash is stored exactly as shown', $GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] === $SLASHY );
check( 'consent: after that save the member is not asked again', vance_hub_dashboard_features_data( fb_user( 7 ), array(), '' )['checkin']['need_consent'] === false );

// Delete by a member who agreed: the answers go and the agreement is withdrawn.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_consented();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $NOW - 2 * $DAY, array( 'pain' => 1 ) ) );
fb_post_delete();
fb_handle();
check( 'delete: a consented member loses the history and the flag, and gets a withdrawal time', $GLOBALS['WRITES'] === array( 'delete_user_meta:_sla_weekly_check_in_history', 'delete_user_meta:_sla_consent_checkin', 'update_user_meta:_sla_consent_checkin_withdrawn_at' ) );
check( 'delete: the withdrawal time is stamped, and the old time and wording stay as a record', $GLOBALS['UMETA'][7]['_sla_consent_checkin_withdrawn_at'] === '2026-11-18 12:00:00' && $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'] === '2026-10-01 09:00:00' && $GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] !== '' );
check( 'delete: after the withdrawal the member is asked to agree again', vance_hub_dashboard_features_data( fb_user( 7 ), array(), '' )['checkin']['need_consent'] === true );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $NOW - 2 * $DAY, array( 'pain' => 1 ) ) );
fb_post_delete();
check( 'delete: the redirect says vance_checkin=deleted', fb_handle() === 'https://example.test/hub/?vance_checkin=deleted' );
check( 'delete: the one write is the removal of the history meta', $GLOBALS['WRITES'] === array( 'delete_user_meta:_sla_weekly_check_in_history' ) );
check( 'delete: the stored history is gone', ! isset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) );

/* ===================================================================== */
section( '9. Data: vance_hub_dashboard_features_data() switch gating' );

fb_arrange( array(
	'vance_hub_dash_member_no' => false,
	'vance_hub_dash_chart'     => false,
	'vance_hub_dash_checkin'   => false,
) );
$CHECKS_ALL = array( fb_row( $NOW - $DAY, array( 'score' => 3, 'maxScore' => 8, 'riskLabel' => 'High risk' ) ) );
$OFF = vance_hub_dashboard_features_data( fb_user( 7 ), $CHECKS_ALL, 'https://example.test/calc/' );
check( 'gating: all three off gives member_code empty', $OFF['member_code'] === '' );
check( 'gating: all three off gives results null', $OFF['results'] === null );
check( 'gating: all three off gives checkin null', $OFF['checkin'] === null );
check( 'gating: all three off writes no meta', $GLOBALS['WRITES'] === array() );
check( 'gating: all three off makes no member code lookup and reads no user meta', $GLOBALS['GET_USERS_CALLS'] === array() && $GLOBALS['META_READS'] === array() );

fb_arrange();
$OFF_DEFAULT = vance_hub_dashboard_features_data( fb_user( 7 ), $CHECKS_ALL, 'https://example.test/calc/' );
check( 'gating: with no switch saved at all, the result is the same empty shape', $OFF_DEFAULT === array( 'member_code' => '', 'results' => null, 'checkin' => null, 'flare_plan' => null ) );
check( 'gating: with no switch saved at all, nothing is written', $GLOBALS['WRITES'] === array() );

// The master switch (Home layout) over the three feature switches.
fb_arrange( array( 'vance_hub_dash_member_no' => true, 'vance_hub_dash_chart' => true, 'vance_hub_dash_checkin' => true ) );
check( 'master: classic with all three boxes ticked, member number is off', vance_hub_dash_feature_on( 'vance_hub_dash_member_no' ) === false );
check( 'master: classic with all three boxes ticked, chart is off', vance_hub_dash_feature_on( 'vance_hub_dash_chart' ) === false );
check( 'master: classic with all three boxes ticked, check-in is off', vance_hub_dash_feature_on( 'vance_hub_dash_checkin' ) === false );

fb_arrange( array( 'vance_home_layout' => 'hub' ) );
check( 'master: hub with member number never saved is on (default on)', vance_hub_dash_feature_on( 'vance_hub_dash_member_no' ) === true );
check( 'master: hub with chart never saved is on (default on)', vance_hub_dash_feature_on( 'vance_hub_dash_chart' ) === true );
check( 'master: hub with check-in never saved is off (health data, default off)', vance_hub_dash_feature_on( 'vance_hub_dash_checkin' ) === false );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_member_no' => false, 'vance_hub_dash_checkin' => true ) );
check( 'master: hub with member number saved off is off', vance_hub_dash_feature_on( 'vance_hub_dash_member_no' ) === false );
check( 'master: hub with check-in saved on is on', vance_hub_dash_feature_on( 'vance_hub_dash_checkin' ) === true );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_member_no' => true, 'vance_hub_dash_chart' => false, 'vance_hub_dash_checkin' => false ) );
$ONLY_NO = vance_hub_dashboard_features_data( fb_user( 7 ), $CHECKS_ALL, 'https://example.test/calc/' );
check( 'gating: member number on alone gives a member code and nothing else', preg_match( '/^VHC-/', $ONLY_NO['member_code'] ) === 1 && $ONLY_NO['results'] === null && $ONLY_NO['checkin'] === null );
check( 'gating: member number on alone reads no check-in history', ! in_array( '_sla_weekly_check_in_history', $GLOBALS['META_READS'], true ) );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_member_no' => false, 'vance_hub_dash_chart' => true, 'vance_hub_dash_checkin' => false ) );
$ONLY_CHART =vance_hub_dashboard_features_data( fb_user( 7 ), $CHECKS_ALL, 'https://example.test/calc/' );
check( 'gating: chart on alone gives results and no member code or check-in', $ONLY_CHART['member_code'] === '' && is_array( $ONLY_CHART['results'] ) && $ONLY_CHART['checkin'] === null );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_member_no' => false, 'vance_hub_dash_chart' => false, 'vance_hub_dash_checkin' => true ) );
$ONLY_CHECKIN =vance_hub_dashboard_features_data( fb_user( 7 ), $CHECKS_ALL, 'https://example.test/calc/' );
check( 'gating: check-in on alone gives checkin and no member code or results', $ONLY_CHECKIN['member_code'] === '' && $ONLY_CHECKIN['results'] === null && is_array( $ONLY_CHECKIN['checkin'] ) );

// The data shape when the switches are on.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_chart' => true ) );
$T1 = strtotime( '2026-11-01 09:00:00 UTC' );
$T2 = strtotime( '2026-11-05 09:00:00 UTC' );
$TWO = array(
	fb_row( $T2, array( 'score' => 3, 'maxScore' => 8, 'riskLabel' => 'Moderate risk' ) ),
	fb_row( $T1, array( 'score' => 5, 'maxScore' => 8, 'riskLabel' => 'High risk' ) ),
);
$RES = vance_hub_dashboard_features_data( fb_user( 7 ), $TWO, 'https://example.test/calc/' )['results'];
check( 'results: two scored checks give two points, oldest first, with dates', count( $RES['points'] ) === 2 && $RES['points'][0]['date'] === '1 Nov 2026' && $RES['points'][1]['date'] === '5 Nov 2026' );
check( 'results: the risk label is the one of the newest check', $RES['risk'] === 'Moderate risk' );
check( 'results: the check-again link is kept', $RES['href'] === 'https://example.test/calc/' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_chart' => true ) );
$NO_LABEL = vance_hub_dashboard_features_data( fb_user( 7 ), array(
	fb_row( $T1, array( 'score' => 5, 'maxScore' => 8, 'riskLabel' => 'High risk' ) ),
	fb_row( $T2, array( 'score' => 3, 'maxScore' => 8 ) ),
), 'https://example.test/calc/' )['results'];
check( 'results: a newest check with no risk label gives an empty risk, not the older one', $NO_LABEL['risk'] === '' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_chart' => true ) );
$ONE_SCORED = vance_hub_dashboard_features_data( fb_user( 7 ), array(
	fb_row( $T1, array( 'score' => 5, 'maxScore' => 8 ) ),
	fb_row( $T2, array( 'text' => 'old snapshot' ) ),
), 'https://example.test/calc/' )['results'];
check( 'results: a text-only snapshot is not plotted, one scored check gives one point', is_array( $ONE_SCORED ) && count( $ONE_SCORED['points'] ) === 1 );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_chart' => true ) );
check( 'results: no scored checks at all gives results null', vance_hub_dashboard_features_data( fb_user( 7 ), array( fb_row( $T1, array( 'text' => 'x' ) ) ), '' )['results'] === null );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_member_no' => false, 'vance_hub_dash_chart' => false, 'vance_hub_dash_checkin' => true ) );
$LAST_TS = $NOW - 2 * $DAY;
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $LAST_TS, array( 'pain' => 2, 'bowel' => 1, 'tired' => 0, 'kind' => 'check-in' ) ) );
fb_consented();
$CI = vance_hub_dashboard_features_data( fb_user( 7 ), array(), '' )['checkin'];
check( 'checkin data: a check-in 2 days ago is not due, and shows its date and the next date', $CI['due'] === false && $CI['last'] === gmdate( 'j M Y', $LAST_TS ) && $CI['next'] === gmdate( 'j M Y', $LAST_TS + 6 * $DAY ) );
check( 'checkin data: one saved row is counted', $CI['count'] === 1 );
check( 'checkin data: a consented member is not asked for consent again', $CI['need_consent'] === false );
check( 'checkin data: the pain series has one point with the stored answer', count( $CI['series']['pain'] ) === 1 && $CI['series']['pain'][0]['value'] === 2.0 && $CI['series']['pain'][0]['max'] === 4.0 );
check( 'checkin data: the check-in writes nothing', $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
$CI_NEW = vance_hub_dashboard_features_data( fb_user( 7 ), array(), '' )['checkin'];
check( 'checkin data: a member with no history is due, with no last or next date and need_consent', $CI_NEW['due'] === true && $CI_NEW['last'] === '' && $CI_NEW['next'] === '' && $CI_NEW['need_consent'] === true && $CI_NEW['count'] === 0 );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
$GLOBALS['UMETA'][7]['_sla_consent_health'] = '1';
check( 'checkin data: the general health flag alone still needs the check-in consent', vance_hub_dashboard_features_data( fb_user( 7 ), array(), '' )['checkin']['need_consent'] === true );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
fb_consented();
$GLOBALS['MODS']['vance_hub_checkin_consent'] = 'New wording.';
check( 'checkin data: changed wording needs consent again', vance_hub_dashboard_features_data( fb_user( 7 ), array(), '' )['checkin']['need_consent'] === true );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true, 'vance_hub_checkin_q_pain' => '  Custom pain?  ', 'vance_hub_checkin_q_bowel' => '   ' ) );
$CI_TEXT = vance_hub_dashboard_features_data( fb_user( 7 ), array(), '' )['checkin'];
check( 'checkin data: a saved question wording is used, trimmed', $CI_TEXT['questions']['pain'] === 'Custom pain?' );
check( 'checkin data: a blank saved wording falls back to the default', $CI_TEXT['questions']['bowel'] === 'How much did bowel symptoms trouble you this week?' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
$_GET['vance_checkin'] = 'saved';
$CI_FLASH = vance_hub_dashboard_features_data( fb_user( 7 ), array(), '' )['checkin'];
check( 'checkin data: the flash is read from the query string', $CI_FLASH['flash'] === 'saved' );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true ) );
$_GET['vance_checkin'] = array( 'saved' );
$CI_FLASH_ARR = vance_hub_dashboard_features_data( fb_user( 7 ), array(), '' )['checkin'];
check( 'checkin data: an array flash in the query string is ignored', $CI_FLASH_ARR['flash'] === '' );

/* ===================================================================== */
section( '10. Render: the check-in block' );

$C_DUE = vance_hub_dashboard_checkin_html( fb_checkin_view() );
check( 'checkin html: a due member sees the form, posting to the page', strpos( $C_DUE, '<form class="vhd-checkin__form" method="post" action="">' ) !== false );
check( 'checkin html: the form carries the nonce', strpos( $C_DUE, '<input type="hidden" name="vance_hub_checkin_nonce" value="nonce-vance_hub_checkin">' ) !== false );
check( 'checkin html: the form says save', strpos( $C_DUE, 'name="vance_hub_checkin_do" value="save"' ) !== false );
check( 'checkin html: three questions give 15 radio buttons, five each', substr_count( $C_DUE, 'type="radio"' ) === 15 && substr_count( $C_DUE, 'name="vhd_checkin[pain]"' ) === 5 );
check( 'checkin html: the scale runs 0 to 4, with no 5', strpos( $C_DUE, 'value="4"' ) !== false && strpos( $C_DUE, 'value="5"' ) === false );
check( 'checkin html: every radio is required', substr_count( $C_DUE, ' required>' ) === 15 );
check( 'checkin html: a due member with consent already given sees no consent box', strpos( $C_DUE, 'name="vhd_consent"' ) === false );

$C_CONSENT = vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'need_consent' => true, 'consent' => 'I agree to storage.' ) ) );
check( 'checkin html: a member who has not consented sees the consent box, required', strpos( $C_CONSENT, 'name="vhd_consent" value="1" required' ) !== false );
check( 'checkin html: the consent box shows the consent text', strpos( $C_CONSENT, 'I agree to storage.' ) !== false );

$C_NOT_DUE = vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'due' => false, 'last' => '10 Nov 2026', 'next' => '16 Nov 2026', 'count' => 1 ) ) );
check( 'checkin html: a member not due sees no form', strpos( $C_NOT_DUE, '<form class="vhd-checkin__form"' ) === false );
check( 'checkin html: a member not due sees no radio buttons', strpos( $C_NOT_DUE, 'type="radio"' ) === false );
check( 'checkin html: a member not due sees the last and next dates', strpos( $C_NOT_DUE, '<p class="vhd-checkin__done">You did your check-in on 10 Nov 2026. The next one opens on 16 Nov 2026.</p>' ) !== false );

check( 'checkin html: the saved flash is a status line with no warning class', strpos( vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'flash' => 'saved' ) ) ), '<p class="vhd-flash" role="status">Your check-in is saved.</p>' ) !== false );
check( 'checkin html: the deleted flash shows its text', strpos( vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'flash' => 'deleted' ) ) ), 'Your check-in answers are deleted, and your agreement is withdrawn.' ) !== false );
check( 'checkin html: the early flash is a warning line', strpos( vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'flash' => 'early' ) ) ), '<p class="vhd-flash vhd-flash--warn" role="status">Not saved. You did your check-in for this week.</p>' ) !== false );
check( 'checkin html: the expired flash is a warning line with the page-left-open text', strpos( vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'flash' => 'expired' ) ) ), '<p class="vhd-flash vhd-flash--warn" role="status">Not saved. This page was open for too long. Answer the questions again, then send the form.</p>' ) !== false );
check( 'checkin html: the consent flash is a warning line',strpos( vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'flash' => 'consent' ) ) ), 'vhd-flash--warn" role="status">Not saved. Tick the box to agree' ) !== false );
check( 'checkin html: the invalid flash is a warning line', strpos( vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'flash' => 'invalid' ) ) ), 'vhd-flash--warn" role="status">Not saved. Answer all the questions' ) !== false );
check( 'checkin html: an unknown flash key prints no message', strpos( vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'flash' => 'bogus' ) ) ), 'role="status"' ) === false );
check( 'checkin html: an empty flash prints no message', strpos( vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'flash' => '' ) ) ), 'role="status"' ) === false );
check( 'checkin html: a flash holding markup prints no message and no markup', strpos( vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'flash' => '<script>x</script>' ) ) ), '<script' ) === false );

$C_ESC = vance_hub_dashboard_checkin_html( fb_checkin_view( array(
	'need_consent' => true,
	'consent'      => '<b>agree</b>',
	'note'         => '<i>note</i>',
	'questions'    => array( 'pain' => '<img src=x onerror=1>', 'bowel' => 'B', 'tired' => 'T' ),
) ) );
check( 'escape: a question with markup is shown as text', strpos( $C_ESC, '&lt;img src=x onerror=1&gt;' ) !== false && strpos( $C_ESC, '<img src=x' ) === false );
check( 'escape: the consent text is escaped', strpos( $C_ESC, '&lt;b&gt;agree&lt;/b&gt;' ) !== false && strpos( $C_ESC, '<b>agree' ) === false );
check( 'escape: the note is escaped', strpos( $C_ESC, '&lt;i&gt;note&lt;/i&gt;' ) !== false && strpos( $C_ESC, '<i>note' ) === false );

$C_MANAGE_0 = vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'count' => 0 ) ) );
check( 'delete control: no saved rows, no delete control', strpos( $C_MANAGE_0, 'value="delete"' ) === false && strpos( $C_MANAGE_0, 'vhd-checkin__manage' ) === false );

$C_MANAGE_1 = vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'count' => 1 ) ) );
check( 'delete control: one saved row says "1 saved check-in" and shows the delete control', strpos( $C_MANAGE_1, 'You have 1 saved check-in.' ) !== false && strpos( $C_MANAGE_1, 'value="delete"' ) !== false );

$C_MANAGE_3 = vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'count' => 3 ) ) );
check( 'delete control: three saved rows say "3 saved check-ins"', strpos( $C_MANAGE_3, 'You have 3 saved check-ins.' ) !== false );

$TREND_POINTS = array(
	fb_point( 1, 1.0, 4.0, '10 Nov 2026' ),
	fb_point( 2, 3.0, 4.0, '17 Nov 2026' ),
);
$C_TREND = vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'series' => array( 'pain' => $TREND_POINTS, 'bowel' => array(), 'tired' => array() ) ) ) );
check( 'trends: two answers for a question give its trend chart under its own name', strpos( $C_TREND, '<p class="vhd-checkin__trend-q">Pain question</p>' ) !== false && strpos( $C_TREND, '<svg' ) !== false );
check( 'trends: a question with no answers has no trend', strpos( $C_TREND, 'Bowel question</p>' ) === false );

$C_TREND_ONE = vance_hub_dashboard_checkin_html( fb_checkin_view( array( 'series' => array( 'pain' => array( fb_point( 1, 1.0, 4.0, '10 Nov 2026' ) ), 'bowel' => array(), 'tired' => array() ) ) ) );
check( 'trends: one answer is not a trend, so no trends block', strpos( $C_TREND_ONE, 'vhd-checkin__trends' ) === false );

check( 'checkin html: a null block renders nothing', vance_hub_dashboard_checkin_html( null ) === '' );
check( 'checkin html: an empty block renders nothing', vance_hub_dashboard_checkin_html( array() ) === '' );
check( 'checkin html: a block with no questions renders nothing', vance_hub_dashboard_checkin_html( array( 'questions' => array() ) ) === '' );

/* ===================================================================== */
section( '11. Render: the results block' );

check( 'results html: null renders nothing', vance_hub_dashboard_results_html( null ) === '' );
check( 'results html: an empty array renders nothing', vance_hub_dashboard_results_html( array() ) === '' );
check( 'results html: no points renders nothing', vance_hub_dashboard_results_html( array( 'points' => array(), 'risk' => '', 'href' => '' ) ) === '' );

$R_ONE = vance_hub_dashboard_results_html( array( 'points' => array( fb_point( 1, 3.0, 8.0, '5 Nov 2026' ) ), 'risk' => '', 'href' => '' ) );
check( 'results html: one point shows the latest line', strpos( $R_ONE, '<p class="vhd-results__latest">Latest score: 3 of 8, on 5 Nov 2026.</p>' ) !== false );
check( 'results html: one point shows no chart', strpos( $R_ONE, '<svg' ) === false );

$R_TWO = vance_hub_dashboard_results_html( array(
	'points' => array( fb_point( 1, 1.0, 8.0, '1 Nov 2026' ), fb_point( 2, 6.0, 8.0, '5 Nov 2026' ) ),
	'risk'   => '',
	'href'   => '',
) );
check( 'results html: two points show the chart', strpos( $R_TWO, '<svg class="vhd-chart__svg"' ) !== false && strpos( $R_TWO, 'vhd-chart__line' ) !== false );
check( 'results html: the latest line uses the newest point, the last one given', strpos( $R_TWO, 'Latest score: 6 of 8, on 5 Nov 2026.' ) !== false );
check( 'results html: the chart range shows the first and last dates', strpos( $R_TWO, '<figcaption class="vhd-chart__range"><span>1 Nov 2026</span><span>5 Nov 2026</span></figcaption>' ) !== false );
check( 'results html: the text alternative lists each value by date', strpos( $R_TWO, '<li>1 Nov 2026: 1 of 8</li>' ) !== false && strpos( $R_TWO, '<li>5 Nov 2026: 6 of 8</li>' ) !== false );

$R_RISK = vance_hub_dashboard_results_html( array( 'points' => array( fb_point( 1, 3.0, 8.0, '5 Nov 2026' ) ), 'risk' => 'High risk', 'href' => '' ) );
check( 'results html: the risk label follows the latest line', strpos( $R_RISK, 'Latest score: 3 of 8, on 5 Nov 2026. High risk.' ) !== false );

$R_ESC = vance_hub_dashboard_results_html( array( 'points' => array( fb_point( 1, 3.0, 8.0, '5 Nov 2026' ) ), 'risk' => '<i>High</i>', 'href' => '' ) );
check( 'results html: a risk label with markup is escaped', strpos( $R_ESC, 'on 5 Nov 2026. &lt;i&gt;High&lt;/i&gt;.' ) !== false && strpos( $R_ESC, '<i>High' ) === false );

$R_LINK = vance_hub_dashboard_results_html( array( 'points' => array( fb_point( 1, 3.0, 8.0, '5 Nov 2026' ) ), 'risk' => '', 'href' => 'https://example.test/calc/' ) );
check( 'results html: the check-again link goes to the calculator', strpos( $R_LINK, '<a class="vhd-link" href="https://example.test/calc/">Check again' ) !== false );

$R_NOLINK = vance_hub_dashboard_results_html( array( 'points' => array( fb_point( 1, 3.0, 8.0, '5 Nov 2026' ) ), 'risk' => '', 'href' => '' ) );
check( 'results html: no calculator address, no link', strpos( $R_NOLINK, 'vhd-link' ) === false );

$R_JS = vance_hub_dashboard_results_html( array( 'points' => array( fb_point( 1, 3.0, 8.0, '5 Nov 2026' ) ), 'risk' => '', 'href' => 'javascript:alert(1)' ) );
check( 'results html: a javascript: address is dropped to an empty href', strpos( $R_JS, 'href=""' ) !== false && strpos( $R_JS, 'javascript:' ) === false );

/* ===================================================================== */
section( '12. Render: the member number and the wiring into the home' );

$H_NONE = vance_hub_dashboard_home_html( fb_home_view() );
check( 'member number: an empty code shows no member line', strpos( $H_NONE, 'vhd-hello__code' ) === false );

$H_CODE = vance_hub_dashboard_home_html( fb_home_view( array( 'member_code' => 'VHC-234567' ) ) );
check( 'member number: a code shows as "Member number VHC-234567"', strpos( $H_CODE, '<p class="vhd-hello__code">Member number VHC-234567</p>' ) !== false );

$H_ESC = vance_hub_dashboard_home_html( fb_home_view( array( 'member_code' => '<b>x</b>' ) ) );
check( 'member number: a code with markup is escaped', strpos( $H_ESC, 'Member number &lt;b&gt;x&lt;/b&gt;' ) !== false && strpos( $H_ESC, '<b>x' ) === false );

$H_NO_CHECKIN = vance_hub_dashboard_home_html( fb_home_view() );
check( 'home: no check-in block when checkin is null', strpos( $H_NO_CHECKIN, 'id="vhd-checkin-title"' ) === false );

$H_CHECKIN = vance_hub_dashboard_home_html( fb_home_view( array( 'checkin' => fb_checkin_view() ) ) );
check( 'home: a check-in block shows on the home when present', strpos( $H_CHECKIN, 'id="vhd-checkin-title"' ) !== false );

$H_NO_RESULTS = vance_hub_dashboard_home_html( fb_home_view() );
check( 'home: no results block when results is null', strpos( $H_NO_RESULTS, 'id="vhd-results-title"' ) === false );

$H_RESULTS = vance_hub_dashboard_home_html( fb_home_view( array( 'results' => array( 'points' => array( fb_point( 1, 3.0, 8.0, '5 Nov 2026' ) ), 'risk' => '', 'href' => '' ) ) ) );
check( 'home: a results block shows on the home when present', strpos( $H_RESULTS, 'id="vhd-results-title"' ) !== false );

/* ===================================================================== */
section( '13. Wiring: vance_known_tool_slugs() keeps check-in rows out' );

check( 'known slugs: weekly-check-in is not listed, so the generic save, update, rename and quick-register handlers stay closed to check-in rows', ! in_array( 'weekly-check-in', vance_known_tool_slugs(), true ) );
check( 'known slugs: the list is exactly malnutrition-calculator and ibd-recipes, in that order', vance_known_tool_slugs() === array( 'malnutrition-calculator', 'ibd-recipes' ) );

/* ===================================================================== */
section( '14. Check-in storage: history and append' );

fb_arrange();
check( 'history: missing meta gives an empty list', vance_hub_checkin_history( 7 ) === array() );

fb_arrange();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = 'junk';
check( 'history: non-array meta gives an empty list', vance_hub_checkin_history( 7 ) === array() );

fb_arrange();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( 300, array( 'pain' => 1 ) ), 'junk', array( 'ts' => 200 ), array( 'ts' => 150, 'payload' => 'x' ), fb_row( 100, array( 'pain' => 2 ) ) );
check( 'history: damaged rows are dropped and the good rows keep their order', array_column( vance_hub_checkin_history( 7 ), 'ts' ) === array( 300, 100 ) );

fb_arrange();
check( 'append: an answer missing its key is refused, false', vance_hub_checkin_append( 7, array( 'pain' => '2', 'bowel' => '3' ), $NOW ) === false );
check( 'append: a missing answer writes nothing', $GLOBALS['WRITES'] === array() && ! isset( $GLOBALS['UMETA'][7] ) );

fb_arrange();
check( 'append: an answer of 5 is refused, false', vance_hub_checkin_append( 7, array( 'pain' => '5', 'bowel' => '3', 'tired' => '1' ), $NOW ) === false );
check( 'append: an out-of-range answer writes nothing', $GLOBALS['WRITES'] === array() && ! isset( $GLOBALS['UMETA'][7] ) );

fb_arrange();
check( 'append: an int answer of 5 is refused, false', vance_hub_checkin_append( 7, array( 'pain' => 5, 'bowel' => 3, 'tired' => 1 ), $NOW ) === false );
check( 'append: user id 0 is refused, false', vance_hub_checkin_append( 0, array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), $NOW ) === false );
check( 'append: user id 0 writes nothing', $GLOBALS['WRITES'] === array() );

fb_arrange();
check( 'append: a good save returns true', vance_hub_checkin_append( 7, array( 'pain' => '2', 'bowel' => 3, 'tired' => '1' ), 1000 ) === true );
check( 'append: the row holds ints plus kind check-in, with the given ts', $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'][0]['payload'] === array( 'pain' => 2, 'bowel' => 3, 'tired' => 1, 'kind' => 'check-in' ) && $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'][0]['ts'] === 1000 );

fb_arrange();
vance_hub_checkin_append( 7, array( 'pain' => '2', 'bowel' => '3', 'tired' => '1', 'evil' => '<b>' ), 1000 );
check( 'append: an extra posted key is not stored', $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'][0]['payload'] === array( 'pain' => 2, 'bowel' => 3, 'tired' => 1, 'kind' => 'check-in' ) );

fb_arrange();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( 500, array( 'pain' => 1, 'bowel' => 1, 'tired' => 1, 'kind' => 'check-in' ) ) );
vance_hub_checkin_append( 7, array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), 1000 );
check( 'append: the new row goes first and the older row keeps second place', array_column( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'], 'ts' ) === array( 1000, 500 ) );

fb_arrange();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array_map( function ( $t ) { return fb_row( $t, array( 'pain' => 1, 'bowel' => 1, 'tired' => 1, 'kind' => 'check-in' ) ); }, range( 50, 1 ) );
vance_hub_checkin_append( 7, array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), 1000 );
$CAPPED = $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'];
check( 'cap: 51 rows keep the newest 50', count( $CAPPED ) === 50 );
check( 'cap: the newest row is first and the oldest kept row, ts 2, is last', $CAPPED[0]['ts'] === 1000 && $CAPPED[49]['ts'] === 2 );
check( 'cap: the oldest row, ts 1, is dropped', ! in_array( 1, array_column( $CAPPED, 'ts' ), true ) );

fb_arrange();
$GLOBALS['UPDATE_FAILS'] = true;
check( 'append: a failed update_user_meta gives false', vance_hub_checkin_append( 7, array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' ), 1000 ) === false );
check( 'append: a failed write stores nothing', ! isset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) );

/* ===================================================================== */
section( '15. Check-in lock: one request per member at a time' );

$ON   = array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true );
$ROW  = array( 'pain' => 1, 'bowel' => 1, 'tired' => 1, 'kind' => 'check-in' );
$SAVE = array( 'pain' => '2', 'bowel' => '3', 'tired' => '1' );

fb_arrange();
$LOCK7 = vance_hub_checkin_lock_name( 7 );
check( 'lock name: starts with vance_checkin_ and has 46 characters, below the limit of 64', 0 === strpos( $LOCK7, 'vance_checkin_' ) && strlen( $LOCK7 ) === 46 );
check( 'lock name: the same member gives the same name', vance_hub_checkin_lock_name( '7' ) === $LOCK7 );
check( 'lock name: another member gives another name', vance_hub_checkin_lock_name( 8 ) !== $LOCK7 );
$GLOBALS['wpdb']->prefix = 'other_';
check( 'lock name: another table prefix gives another name', vance_hub_checkin_lock_name( 7 ) !== $LOCK7 );
$GLOBALS['wpdb']->prefix = 'wp_';
// The name must need the site secret. A name made from the database name, the
// prefix and the user id only can be made by another customer on the server.
$GLOBALS['SALT'] = 'test-salt-two';
check( 'lock name: another site secret gives another name', vance_hub_checkin_lock_name( 7 ) !== $LOCK7 );
unset( $GLOBALS['SALT'] );
$PUBLIC_INPUT = ( defined( 'DB_NAME' ) ? DB_NAME : '' ) . '|wp_|7';
check( 'lock name: the name is not a plain hash of the public inputs', ! in_array( substr( $LOCK7, 14 ), array( md5( $PUBLIC_INPUT ), substr( hash( 'sha256', $PUBLIC_INPUT ), 0, 32 ), substr( sha1( $PUBLIC_INPUT ), 0, 32 ) ), true ) );
check( 'lock name: the secret is the auth salt', isset( $GLOBALS['SALT_SCHEMES'] ) && in_array( 'auth', $GLOBALS['SALT_SCHEMES'], true ) );
check( 'lock name: the part after the prefix is 32 hex characters', 1 === preg_match( '/^[0-9a-f]{32}$/D', substr( $LOCK7, 14 ) ) );

fb_arrange();
check( 'lock: user 0 gets no lock and sends no query', vance_hub_checkin_lock( 0 ) === false && $GLOBALS['LOCK_SQL'] === array() );

// A save: lock, drop the login copy, work, unlock.
fb_arrange( $ON );
fb_post_save();
check( 'lock: the save still saves', fb_handle() === 'https://example.test/hub/?vance_checkin=saved' );
$LOCK_AT = array_search( 'lock', $GLOBALS['SEQ'], true );
check( 'lock: a save takes the lock before its first read or write of check-in data', false !== $LOCK_AT && fb_seq_first_data() > $LOCK_AT );
check( 'lock: the login copy of the meta is dropped directly after the lock', false !== $LOCK_AT && isset( $GLOBALS['SEQ'][ $LOCK_AT + 1 ] ) && $GLOBALS['SEQ'][ $LOCK_AT + 1 ] === 'cache_delete:user_meta' );
check( 'lock: a save takes one lock and frees it as its last step', fb_seq_locked_once() );
check( 'lock: the save asks for the lock of this member and waits 5 seconds at most', $GLOBALS['LOCK_SQL'] === array( "SELECT GET_LOCK('" . $LOCK7 . "', 5)" ) );
check( 'lock: the save frees the lock that it took', $GLOBALS['UNLOCK_SQL'] === array( "SELECT RELEASE_LOCK('" . $LOCK7 . "')" ) );

// A delete: the same order.
fb_arrange( $ON );
fb_consented();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $NOW - 2 * $DAY, $ROW ) );
fb_post_delete();
check( 'lock: the delete still deletes', fb_handle() === 'https://example.test/hub/?vance_checkin=deleted' );
$LOCK_AT = array_search( 'lock', $GLOBALS['SEQ'], true );
check( 'lock: a delete takes the lock before its first read or write of check-in data', false !== $LOCK_AT && fb_seq_first_data() > $LOCK_AT );
check( 'lock: a delete takes one lock and frees it as its last step', fb_seq_locked_once() );

// Each result that comes after the lock frees it.
fb_arrange( $ON );
fb_post_save( array( 'pain' => '9', 'bowel' => '3', 'tired' => '1' ) );
check( 'lock: an invalid save frees the lock', fb_handle() === 'https://example.test/hub/?vance_checkin=invalid' && fb_seq_locked_once() );

fb_arrange( $ON );
fb_post_save( $SAVE, false );
check( 'lock: a save with no agreement frees the lock', fb_handle() === 'https://example.test/hub/?vance_checkin=consent' && fb_seq_locked_once() );

fb_arrange( $ON );
fb_consented();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $NOW - 2 * $DAY, $ROW ) );
fb_post_save( $SAVE, false );
check( 'lock: an early save frees the lock', fb_handle() === 'https://example.test/hub/?vance_checkin=early' && fb_seq_locked_once() );

fb_arrange( $ON );
$GLOBALS['UPDATE_FAILS'] = '_sla_weekly_check_in_history';
fb_post_save();
check( 'lock: a failed save frees the lock', fb_handle() === 'https://example.test/hub/?vance_checkin=failed' && fb_seq_locked_once() );

// The lock is not given: another request of this member holds it.
fb_arrange( $ON );
fb_consented();
$GLOBALS['LOCK_RESULT'] = '0';
fb_post_save( $SAVE, false );
check( 'lock: a save with no lock gives failed', fb_handle() === 'https://example.test/hub/?vance_checkin=failed' );
check( 'lock: a save with no lock writes nothing and reads no check-in data', $GLOBALS['WRITES'] === array() && fb_seq_first_data() === -1 );
check( 'lock: a save with no lock does not free a lock that it does not hold', $GLOBALS['UNLOCK_SQL'] === array() );

fb_arrange( $ON );
fb_consented();
$GLOBALS['LOCK_RESULT'] = null;
fb_post_save( $SAVE, false );
check( 'lock: a lock error gives failed and writes nothing', fb_handle() === 'https://example.test/hub/?vance_checkin=failed' && $GLOBALS['WRITES'] === array() && $GLOBALS['UNLOCK_SQL'] === array() );

fb_arrange( $ON );
fb_consented();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $NOW - 2 * $DAY, $ROW ) );
$GLOBALS['LOCK_RESULT'] = '0';
fb_post_delete();
check( 'lock: a delete with no lock gives kept, not deleted', fb_handle() === 'https://example.test/hub/?vance_checkin=kept' );
check( 'lock: a delete with no lock keeps the answers and the agreement', $GLOBALS['WRITES'] === array() && count( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) === 1 && $GLOBALS['UMETA'][7]['_sla_consent_checkin'] === '1' );

// Requests that stop before the work take no lock.
fb_arrange( $ON );
fb_post_save();
$_POST['vance_hub_checkin_do'] = 'other';
check( 'lock: an unknown action takes no lock', fb_handle() === 'https://example.test/hub/?vance_checkin=invalid' && $GLOBALS['LOCK_SQL'] === array() );

fb_arrange( $ON );
fb_post_save();
$_POST['vance_hub_checkin_nonce'] = 'bad';
check( 'lock: a bad nonce takes no lock', fb_handle() === 'https://example.test/hub/?vance_checkin=expired' && $GLOBALS['LOCK_SQL'] === array() );

fb_arrange();
fb_post_save();
check( 'lock: with the switch off, no lock', fb_handle() === null && $GLOBALS['LOCK_SQL'] === array() );

// Two tabs, two saves. The other tab saved while this request waited. STALE is
// what this request loaded at login: an agreement and no answers.
fb_arrange( $ON );
fb_consented();
$GLOBALS['STALE'][7] = $GLOBALS['UMETA'][7];
$GLOBALS['ON_LOCK']  = function () use ( $ROW ) {
	$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( time(), $ROW ) );
};
fb_post_save( $SAVE, false );
check( 'two saves: the second save sees the first and gives early', fb_handle() === 'https://example.test/hub/?vance_checkin=early' );
check( 'two saves: the second save writes nothing, one row stays', $GLOBALS['WRITES'] === array() && count( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) === 1 );

// A delete in the other tab finished while this save waited. The form of this
// save had no agreement box, because the agreement was there when it was shown.
fb_arrange( $ON );
fb_consented();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $NOW - 8 * $DAY, $ROW ) );
$GLOBALS['STALE'][7] = $GLOBALS['UMETA'][7];
$GLOBALS['ON_LOCK']  = function () {
	unset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'], $GLOBALS['UMETA'][7]['_sla_consent_checkin'] );
};
fb_post_save( $SAVE, false );
check( 'delete then save: a save with no tick sees the withdrawal and gives consent', fb_handle() === 'https://example.test/hub/?vance_checkin=consent' );
check( 'delete then save: no answers are stored with no agreement', $GLOBALS['WRITES'] === array() && ! isset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) && ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin'] ) );

// The same, and the form had the tick: the answers get a new agreement record.
fb_arrange( $ON );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( $NOW - 8 * $DAY, $ROW ) );
$GLOBALS['STALE'][7] = $GLOBALS['UMETA'][7];
$GLOBALS['ON_LOCK']  = function () {
	unset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] );
};
fb_post_save( $SAVE, true );
check( 'delete then save: a save with the tick saves', fb_handle() === 'https://example.test/hub/?vance_checkin=saved' );
check( 'delete then save: only the new row is stored, with a new agreement record', count( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) === 1 && $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'][0]['payload']['pain'] === 2 && $GLOBALS['UMETA'][7]['_sla_consent_checkin'] === '1' && $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'] === '2026-11-18 12:00:00' );

// A save in the other tab finished while this delete waited. At login this
// request saw no answers and no agreement.
fb_arrange( $ON );
$GLOBALS['STALE'][7] = array();
$GLOBALS['ON_LOCK']  = function () use ( $ROW ) {
	fb_consented();
	$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( fb_row( time(), $ROW ) );
};
fb_post_delete();
check( 'save then delete: the delete gives deleted', fb_handle() === 'https://example.test/hub/?vance_checkin=deleted' );
check( 'save then delete: the answers are gone and the agreement is withdrawn, with its time', ! isset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) && ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin'] ) && isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_withdrawn_at'] ) && $GLOBALS['UMETA'][7]['_sla_consent_checkin_withdrawn_at'] === '2026-11-18 12:00:00' );

/* ---- master switch and an administrator's preview ----------------------------- */
section( 'master switch: an administrator preview of the feature switches' );
// Master classic, both switches saved on, an administrator previews with ?vhh_layout=hub.
fb_arrange( array( 'vance_home_layout' => 'classic', 'vance_hub_dash_member_no' => true, 'vance_hub_dash_checkin' => true ) );
$GLOBALS['CAPS'] = array( 'edit_theme_options' => true );
$_GET            = array( 'vhh_layout' => 'hub' );
check( 'preview: master classic, ?vhh_layout=hub shows a feature saved on', true === vance_hub_dash_feature_on( 'vance_hub_dash_member_no' ) );
check( 'preview: master classic, ?vhh_layout=hub shows the check-in saved on', true === vance_hub_dash_feature_on( 'vance_hub_dash_checkin' ) );
$GLOBALS['MODS']['vance_hub_dash_member_no'] = false;
check( 'preview: a feature saved off stays off in the preview', false === vance_hub_dash_feature_on( 'vance_hub_dash_member_no' ) );
unset( $GLOBALS['MODS']['vance_hub_dash_checkin'] );
check( 'preview: the check-in keeps its default of off in the preview', false === vance_hub_dash_feature_on( 'vance_hub_dash_checkin' ) );
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_chart' => true ) );
$GLOBALS['CAPS'] = array( 'edit_theme_options' => true );
$_GET            = array( 'vhh_layout' => 'classic' );
check( 'preview: master hub, ?vhh_layout=classic turns a feature off', false === vance_hub_dash_feature_on( 'vance_hub_dash_chart' ) );
fb_arrange( array( 'vance_home_layout' => 'classic', 'vance_hub_dash_chart' => true ) );
$_GET = array( 'vhh_layout' => 'hub' );
check( 'preview: a visitor without edit_theme_options cannot preview a feature on', false === vance_hub_dash_feature_on( 'vance_hub_dash_chart' ) );
$_GET = array();

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . ' FAILED of ' . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK - $PASS checks passed.\n";
