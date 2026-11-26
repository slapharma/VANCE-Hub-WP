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
 *  13  flare log: dates, stored rows, cap, open flare, days, start, end, write
 *  14  flare log action: result codes, the agreement, the plan, failed stores
 *  15  POST handler, flare start and end: lock, gating, nonce, delete with the log,
 *      check-in untouched
 *  16  plan progress: answers and the six-month review
 *  17  flare log view: open flare, five newest rows, count
 *  18  quick check data: the last saved check-in
 *  19  appointment sheet data: empty groups, caps, screening
 *  20  render: progress bar, quick check, flare log, builder, print buttons, sheet
 *  21  privacy: the flare log in the exporter and the eraser
 *  22  static: print script, print CSS, hidden rule
 *  23  dates: a stored day reads as the same day in any site time zone (own wp_date stub)
 *  24  render: consent needed or given, the log forms and the Edit my plan box
 *  25  delete order: the flare log goes before the plan; a failed log delete keeps the plan
 *  26  static: the step builder moves focus after Back and Next
 *  27  no-cache: the negative cases and the hook (static)
 *  28  no-cache: the positive case. It defines DONOTCACHEPAGE (section 27 also does now, for the read-only card)
 *  29  one delete function (delete_all), handler codes deleted/partial/kept, Manage my data
 *  30  result message: #vhd-flare fragment, card id, alert and status roles
 *  31  gates: can(), delete with the switch off, read-only card, eraser lock, exporter
 *  32  text cleaner: <, >, &, %41, backslash survive
 *  33  over-length text refused (toolong)
 *  34  the draft: kept after a refused request, shown once
 *  35  a failed write changes nothing; the log keeps 50 entries
 *  36  sheet and quick check read each store through its own gate
 *  37  remove one log entry
 *  38  telephone links
 *  39  edge cases: multibyte at the limit through the handler, control-only text, draft per member,
 *      row ids, telephone positives and negatives, pattern gate, escaped output
 *
 * Checks that fail on the current code are findings. They are listed in the report,
 * not hidden: a failure names the behaviour the code should have.
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
// This suite needs a wp_date() that applies the zone it is given. The shared stub
// always formats in UTC, so a day that moves by one could never show. Its wp_date()
// and wp_timezone() are taken out of the eval'd text here, and defined again after
// the stub block. hub-layout.test.php itself is not changed.
$DEFS = preg_replace( '/function wp_date\( \$format, \$timestamp = null \) \{.*?\r?\n\}\r?\n/s', '', $DEFS, 1, $N_DATE );
$DEFS = preg_replace( '/function wp_timezone\(\) \{[^\r\n]*\r?\n/', '', $DEFS, 1, $N_ZONE );
if ( 1 !== $N_DATE || 1 !== $N_ZONE ) {
	fwrite( STDERR, "could not take the wp_date stub out of hub-layout.test.php\n" );
	exit( 2 );
}
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

// The appointment sheet reads the nutrition screening through vance_get_tool_history().
// Lift the real one, as above. It needs vance_known_tool_slugs(), loaded just before.
preg_match( '/function vance_get_tool_history\(.*?\r?\n\}\r?\n/s', $DASH_FN, $LIFT_HIST );
if ( empty( $LIFT_HIST[0] ) ) {
	fwrite( STDERR, "could not lift vance_get_tool_history() from dashboard-functions.php\n" );
	exit( 2 );
}
if ( ! function_exists( 'vance_get_tool_history' ) ) {
	eval( $LIFT_HIST[0] );
}

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
if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = '' ) { return esc_attr( $text ); }
}
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
	// UPDATE_FAILS_ONCE: this key fails once, then writes work again.
	if ( ! empty( $GLOBALS['UPDATE_FAILS_ONCE'] ) && $key === $GLOBALS['UPDATE_FAILS_ONCE'] ) {
		$GLOBALS['UPDATE_FAILS_ONCE'] = '';
		return false;
	}
	// CORRUPT_ONCE: the next write of this key stores damaged data (and reports true), as a
	// store that lies would. The read-back then differs from what was sent.
	if ( ! empty( $GLOBALS['CORRUPT_ONCE'] ) && $key === $GLOBALS['CORRUPT_ONCE'] ) {
		$GLOBALS['CORRUPT_ONCE']              = '';
		$GLOBALS['UMETA'][ $user_id ][ $key ] = array( 'corrupt' => true );
		return true;
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

// Transients (for the draft): the shared stubs of hub-layout.test.php already define
// set_transient(), get_transient() and delete_transient(). They keep the values in
// $GLOBALS['TRANSIENTS'] and the lifetimes in $GLOBALS['SET_TRANSIENTS'].
// As WordPress does for UTF-8 text: invalid UTF-8 gives an empty string.
// home_url() comes from the shared stub block: 'https://example.test' . $path.
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( (string) $url, $component );
}
function wp_check_invalid_utf8( $text, $strip = false ) {
	return 1 === preg_match( '//u', (string) $text ) ? (string) $text : '';
}

// The assertion helpers. hub-layout.test.php defines the same pair after the stub block.
$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

// The zone-aware wp_date() and wp_timezone() of this suite. The site zone is UTC until
// a check sets another, so every check above section 23 sees the output it always saw.
$GLOBALS['SITE_TZ'] = 'UTC';
function wp_timezone() { return new DateTimeZone( $GLOBALS['SITE_TZ'] ); }
function wp_date( $format, $timestamp = null, $timezone = null ) {
	if ( null === $timestamp ) {
		$timestamp = strtotime( $GLOBALS['TODAY'] . ' 12:00:00 UTC' );
	}
	$zone = $timezone instanceof DateTimeZone ? $timezone : wp_timezone();
	$when = new DateTime( '@' . (int) $timestamp );
	$when->setTimezone( $zone );
	return $when->format( $format );
}

require_once $THEME . '/inc/hub-dashboard.php';
require_once $THEME . '/inc/hub-dashboard-features.php';
require_once $THEME . '/inc/hub-flare-plan.php';
// The "Manage my data" delete: section 29 checks that it uses the flare plan's own delete.
require_once $THEME . '/inc/hub-data-manage.php';

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
	$GLOBALS['TRANSIENTS']     = array();
	$GLOBALS['SET_TRANSIENTS'] = array();
	$GLOBALS['WRITES']         = array();
	$GLOBALS['UPDATE_FAILS']   = false;
	$GLOBALS['UPDATE_FAILS_ONCE'] = '';
	$GLOBALS['CORRUPT_ONCE']   = '';
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
	unset( $_SERVER['HTTP_SEC_FETCH_SITE'], $_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_REFERER'] );
	if ( ! empty( $GLOBALS['OPEN_GATES'] ) ) {
		fb_open_gates( $uid );
	}
}

/**
 * Open the gates the quick check and the appointment sheet read through: the check-in
 * switch and the check-in agreement for the wording in use, and the general health
 * agreement. fb_arrange() calls it while $GLOBALS['OPEN_GATES'] is true (sections 18 to 23,
 * which test the data and not the gates). Section 31 tests each gate closed.
 */
function fb_open_gates( $uid = 7 ) {
	$GLOBALS['MODS']['vance_hub_dash_checkin']              = true;
	$GLOBALS['UMETA'][ $uid ]['_sla_consent_checkin']      = '1';
	$GLOBALS['UMETA'][ $uid ]['_sla_consent_checkin_at']   = '2026-10-01 09:00:00';
	$GLOBALS['UMETA'][ $uid ]['_sla_consent_checkin_text'] = vance_hub_checkin_consent_text();
	$GLOBALS['UMETA'][ $uid ]['_sla_consent_health']       = '1';
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

/**
 * Run the flare POST handler. Returns the redirect URL, or null when it did not redirect.
 * Every redirect now ends in #vhd-flare. The fragment is cut off here, so the many checks
 * that compare the URL read as before. The raw URL is in $GLOBALS['REDIRECT_RAW']; section 30
 * checks the fragment on it.
 */
function fb_handle() {
	$GLOBALS['REDIRECT_RAW'] = null;
	try {
		vance_hub_flare_plan_handle_post();
	} catch ( RedirectStop $stop ) {
		$GLOBALS['REDIRECT_RAW'] = $stop->url;
		return preg_replace( '/#vhd-flare$/', '', $stop->url );
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

/** A flare action POST with the right nonce. $helped is the "what helped" text, when given. */
function fb_post_flare( $do, $helped = null ) {
	$_POST = array(
		'vance_hub_flare_nonce' => 'nonce-vance_hub_flare_plan',
		'vance_hub_flare_do'    => $do,
	);
	if ( null !== $helped ) {
		$_POST['vhd_flare_helped'] = $helped;
	}
}

/** The data-config attribute of the quick check, decoded. Null when it is not valid JSON. */
function fb_config( $html ) {
	preg_match( '/data-config="([^"]*)"/', $html, $m );
	return json_decode( html_entity_decode( $m[1] ?? '', ENT_QUOTES, 'UTF-8' ), true );
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
// CHANGED (spec A): tags are no longer stripped, so an empty tag is text the member typed.
check( 'clean: an answer that is only an empty tag is kept as typed (no tag stripping)', ( vance_hub_flare_plan_clean( array( 'signs' => '<b></b>', 'actions' => '', 'contacts' => '' ) )['signs'] ?? null ) === '<b></b>' );
check( 'clean: one character is kept, the other two come back as empty strings', vance_hub_flare_plan_clean( array( 'signs' => 'a' ) ) === array( 'signs' => 'a', 'actions' => '', 'contacts' => '' ) );
check( 'clean: a missing answer key comes back as an empty string', vance_hub_flare_plan_clean( array( 'contacts' => 'Nurse' ) ) === array( 'signs' => '', 'actions' => '', 'contacts' => 'Nurse' ) );

check( 'limit: exactly 1000 characters are kept whole', strlen( vance_hub_flare_plan_clean( array( 'signs' => str_repeat( 'a', 1000 ) ) )['signs'] ?? '' ) === 1000 );
// CHANGED (spec B): clean() no longer cuts. An over-length answer is kept whole, and
// vance_hub_flare_plan_too_long() reports it, so the handler can refuse it.
check( 'limit: 1001 characters are not cut by clean(), and too_long() says so', strlen( vance_hub_flare_plan_clean( array( 'signs' => str_repeat( 'a', 1001 ) ) )['signs'] ?? '' ) === 1001 && vance_hub_flare_plan_too_long( vance_hub_flare_plan_clean( array( 'signs' => str_repeat( 'a', 1001 ) ) ) ) === true );
check( 'limit: 1000 characters are not too long', vance_hub_flare_plan_too_long( vance_hub_flare_plan_clean( array( 'signs' => str_repeat( 'a', 1000 ) ) ) ) === false );
check( 'limit: 1001 accented characters are too long, counted in characters not bytes', vance_hub_flare_plan_too_long( array( 'signs' => str_repeat( 'é', 1001 ) ) ) === true && vance_hub_flare_plan_too_long( array( 'signs' => str_repeat( 'é', 1000 ) ) ) === false );
check( 'limit: 1001 emoji are too long, 1000 emoji are not', vance_hub_flare_plan_too_long( array( 'signs' => str_repeat( '😀', 1001 ) ) ) === true && vance_hub_flare_plan_too_long( array( 'signs' => str_repeat( '😀', 1000 ) ) ) === false );
check( 'limit: too_long() looks at every answer, and ignores a value that is not an array', vance_hub_flare_plan_too_long( array( 'signs' => 'a', 'actions' => 'b', 'contacts' => str_repeat( 'c', 1001 ) ) ) === true && vance_hub_flare_plan_too_long( 'x' ) === false );
check( 'limit: a cut through 3-byte characters leaves valid UTF-8', preg_match( '//u', vance_hub_flare_plan_clean( array( 'signs' => str_repeat( '€', 1001 ) ) )['signs'] ?? '' ) === 1 );

// CHANGED (spec A): the cleaner strips no tags. Every output is escaped, so the text is stored as typed.
check( 'tags: markup typed by the member is stored as typed, not stripped', ( vance_hub_flare_plan_clean( array( 'signs' => '<b>bold</b> <a href="https://example.test">link</a>' ) )['signs'] ?? null ) === '<b>bold</b> <a href="https://example.test">link</a>' );
check( 'tags: a script block typed by the member is stored as typed (it is escaped on output)', ( vance_hub_flare_plan_clean( array( 'signs' => 'before<script>alert(1)</script>after' ) )['signs'] ?? null ) === 'before<script>alert(1)</script>after' );
check( 'clean text: "<", ">", "&" and "%41" survive', vance_hub_flare_plan_clean_text( 'a < b > c & d %41' ) === 'a < b > c & d %41' );
check( 'clean text: control characters go, LF and TAB stay, CRLF becomes LF, the ends are trimmed', vance_hub_flare_plan_clean_text( "  a\x00b\x07c\td\r\ne\rf\x7f  " ) === "abc\td\ne\nf" );
check( 'clean text: invalid UTF-8 gives an empty string', vance_hub_flare_plan_clean_text( "ab\xff\xfe" ) === '' );
check( 'clean text: a non-string gives an empty string, a number is cast', vance_hub_flare_plan_clean_text( array( 'x' ) ) === '' && vance_hub_flare_plan_clean_text( 12 ) === '12' );
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
// CHANGED (spec F): the switch off no longer makes the handler return silently. A save redirects with failed.
check( 'gating: the switch unset (default off) refuses a good save with failed, and writes nothing', fb_handle() === 'https://example.test/hub/?vance_flare=failed' && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'classic', 'vance_hub_dash_flare_plan' => true, 'vance_hub_flare_plan_urgent' => 'Call the clinic.' ) );
fb_post_save();
check( 'gating: the Home layout master switch off blocks a good save (failed, nothing written)', fb_handle() === 'https://example.test/hub/?vance_flare=failed' && $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
fb_post_delete();
$GLOBALS['MODS']['vance_hub_dash_flare_plan'] = false;
// CHANGED (spec F): a member can always delete. The switch off no longer blocks delete.
check( 'gating: the switch off still deletes the plan (deleted), and withdraws the agreement', fb_handle() === 'https://example.test/hub/?vance_flare=deleted' && fb_meta( '_sla_flare_plan' ) === null && fb_meta( '_sla_consent_flare_plan' ) === null );

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
// CHANGED (spec B): an over-length answer is refused with toolong, never cut.
check( 'limit: a save of 1200 characters is refused with toolong', fb_handle() === 'https://example.test/hub/?vance_flare=toolong' );
check( 'limit: a refused save stores no plan and no agreement', fb_meta( '_sla_flare_plan' ) === null && fb_meta( '_sla_consent_flare_plan' ) === null && $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => str_repeat( 'a', 1000 ), 'actions' => '', 'contacts' => '' ) );
check( 'limit: a save of exactly 1000 characters is stored whole', fb_handle() === 'https://example.test/hub/?vance_flare=saved' && strlen( fb_meta( '_sla_flare_plan' )['signs'] ?? '' ) === 1000 );

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
// CHANGED (spec D): the plan was deleted and the agreement stayed, so part of the data is gone: partial, not kept.
check( 'delete read-back: an agreement that is still active, with the plan deleted, gives partial', fb_handle() === 'https://example.test/hub/?vance_flare=partial' );
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
check( 'data: the switch on gives the view with its keys in order', is_array( $D ) && array_keys( $D ) === array( 'plan', 'progress', 'log', 'quick', 'sheet', 'updated', 'urgent', 'need_consent', 'consent', 'note', 'nonce', 'flash', 'draft', 'readonly' ) );
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
check( 'print: the script does not mark a card that sits in a hidden panel', false !== strpos( $JS, "part.closest( '[hidden]' )" ) );
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
check( 'render: the delete control sends the delete action with the nonce', strpos( $H_PLAN, 'name="vance_hub_flare_do" value="delete"' ) !== false && strpos( $H_PLAN, 'Delete my plan and flare log, and withdraw my agreement' ) !== false );
check( 'render: the print button carries the hidden attribute, so it shows only with the script', strpos( $H_PLAN, 'data-vhd-flare-print="plan" hidden>' ) !== false );

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
section( '13. flare log: dates, stored rows, cap, open flare, days, start, end, write' );

$LOG_NOW  = strtotime( '2026-11-18 12:00:00 UTC' );
$LOG_OK   = array( 'id' => 'a1', 'start' => '2026-11-01', 'end' => '2026-11-03', 'helped' => 'Rest' );
$LOG_OPEN = array( array( 'id' => 'o', 'start' => '2026-11-16', 'end' => '', 'helped' => '' ) );
$SIXTY    = array_map( function ( $i ) {
	return array( 'id' => 'r' . $i, 'start' => '2026-11-01', 'end' => '2026-11-01', 'helped' => '' );
}, range( 0, 59 ) );

check( 'date: a real Y-m-d date is accepted', vance_hub_flare_log_date_ok( '2026-11-18' ) === true );
check( 'date: 29 February of a leap year is accepted', vance_hub_flare_log_date_ok( '2028-02-29' ) === true );
check( 'date: 29 February of a common year is refused', vance_hub_flare_log_date_ok( '2026-02-29' ) === false );
check( 'date: 30 February is refused', vance_hub_flare_log_date_ok( '2026-02-30' ) === false );
check( 'date: month 13 is refused', vance_hub_flare_log_date_ok( '2026-13-01' ) === false );
check( 'date: plain text is refused', vance_hub_flare_log_date_ok( 'junk' ) === false );
check( 'date: a day without a zero is refused', vance_hub_flare_log_date_ok( '2026-11-1' ) === false );
check( 'date: a trailing newline is refused', vance_hub_flare_log_date_ok( "2026-11-18\n" ) === false );
check( 'date: a leading space is refused', vance_hub_flare_log_date_ok( ' 2026-11-18' ) === false );
check( 'date: a number, null and an array are refused', vance_hub_flare_log_date_ok( 20261118 ) === false && vance_hub_flare_log_date_ok( null ) === false && vance_hub_flare_log_date_ok( array( '2026-11-18' ) ) === false );

check( 'normalise: a value that is not an array gives no rows', vance_hub_flare_log_normalise( 'junk' ) === array() );
check( 'normalise: a good closed row is kept as it is', vance_hub_flare_log_normalise( array( $LOG_OK ) ) === array( $LOG_OK ) );
check( 'normalise: a row that starts and ends on the same day is kept', count( vance_hub_flare_log_normalise( array( array( 'id' => 'a', 'start' => '2026-11-01', 'end' => '2026-11-01', 'helped' => '' ) ) ) ) === 1 );
check( 'normalise: an open row with an empty end is kept as open', vance_hub_flare_log_normalise( $LOG_OPEN ) === $LOG_OPEN );
check( 'normalise: a row with no start is dropped', vance_hub_flare_log_normalise( array( array( 'id' => 'a', 'end' => '' ) ) ) === array() );
check( 'normalise: a row with a bad start date is dropped', vance_hub_flare_log_normalise( array( array( 'id' => 'a', 'start' => '2026-02-30', 'end' => '' ) ) ) === array() );
check( 'normalise: a row that ends before it starts is dropped', vance_hub_flare_log_normalise( array( array( 'id' => 'a', 'start' => '2026-11-05', 'end' => '2026-11-04', 'helped' => '' ) ) ) === array() );
check( 'normalise: a row with a bad end date is dropped', vance_hub_flare_log_normalise( array( array( 'id' => 'a', 'start' => '2026-11-01', 'end' => '2026-02-30', 'helped' => '' ) ) ) === array() );
check( 'normalise: a damaged row is dropped and the good rows around it are kept', vance_hub_flare_log_normalise( array( $LOG_OK, 'junk', array( 'start' => 'x' ), array( 'id' => 'b', 'start' => '2026-10-01', 'end' => '2026-10-02', 'helped' => '' ) ) ) === array( $LOG_OK, array( 'id' => 'b', 'start' => '2026-10-01', 'end' => '2026-10-02', 'helped' => '' ) ) );
check( 'normalise: a missing id and a missing helped text come back as empty strings', vance_hub_flare_log_normalise( array( array( 'start' => '2026-11-01', 'end' => '' ) ) ) === array( array( 'id' => '', 'start' => '2026-11-01', 'end' => '', 'helped' => '' ) ) );
check( 'normalise: a helped text that is not a string comes back empty', ( vance_hub_flare_log_normalise( array( array( 'id' => 'a', 'start' => '2026-11-01', 'end' => '', 'helped' => array( 'x' ) ) ) )[0]['helped'] ?? null ) === '' );
check( 'normalise: a helped text longer than 500 characters is cut to 500', strlen( vance_hub_flare_log_normalise( array( array( 'id' => 'a', 'start' => '2026-11-01', 'end' => '', 'helped' => str_repeat( 'h', 600 ) ) ) )[0]['helped'] ?? '' ) === 500 );
check( 'normalise: an end that is not a date text is dropped, not read as an open flare', vance_hub_flare_log_normalise( array( array( 'id' => 'a', 'start' => '2026-11-01', 'end' => 12, 'helped' => '' ) ) ) === array() );

check( 'cap: the log keeps at most 50 rows', vance_hub_flare_log_max_rows() === 50 );
check( 'cap: 60 stored rows are cut to 50', count( vance_hub_flare_log_normalise( $SIXTY ) ) === 50 );
check( 'cap: the 50 kept are the first 50 in stored order', vance_hub_flare_log_normalise( $SIXTY )[49]['id'] === 'r49' );

check( 'open index: no open flare gives null', vance_hub_flare_log_open_index( array( $LOG_OK ) ) === null );
check( 'open index: an empty log gives null', vance_hub_flare_log_open_index( array() ) === null );
check( 'open index: the open row is found at its position', vance_hub_flare_log_open_index( array( $LOG_OK, $LOG_OPEN[0] ) ) === 1 );

check( 'days: a flare on one day counts as 1 day', vance_hub_flare_log_days( '2026-11-01', '2026-11-01' ) === 1 );
check( 'days: 1 to 3 November counts 3 days, both ends included', vance_hub_flare_log_days( '2026-11-01', '2026-11-03' ) === 3 );
check( 'days: 28 February to 1 March in a leap year counts 3 days', vance_hub_flare_log_days( '2028-02-28', '2028-03-01' ) === 3 );
check( 'days: an end before the start counts 1 day, never less', vance_hub_flare_log_days( '2026-11-05', '2026-11-04' ) === 1 );

check( 'start: a new open flare is added first, dated on the day given', vance_hub_flare_log_start( array( $LOG_OK ), '2026-11-18', 'new' ) === array( array( 'id' => 'new', 'start' => '2026-11-18', 'end' => '', 'helped' => '' ), $LOG_OK ) );
check( 'start: a flare cannot start while one is open', vance_hub_flare_log_start( $LOG_OPEN, '2026-11-18', 'new' ) === null );
check( 'start: a bad date gives null', vance_hub_flare_log_start( array(), '2026-02-30', 'new' ) === null );
$FULL_LOG = array_slice( $SIXTY, 0, 50 );
$FULL_NEW = vance_hub_flare_log_start( $FULL_LOG, '2026-11-18', 'new' );
check( 'start: a full log keeps 50 rows and drops the oldest', count( (array) $FULL_NEW ) === 50 && ( $FULL_NEW[0]['id'] ?? null ) === 'new' && ( $FULL_NEW[49]['id'] ?? null ) === 'r48' );

check( 'end: closes the open flare with today as its end', vance_hub_flare_log_end( $LOG_OPEN, '2026-11-18', 'Rest' ) === array( array( 'id' => 'o', 'start' => '2026-11-16', 'end' => '2026-11-18', 'helped' => 'Rest' ) ) );
check( 'end: with no open flare gives null', vance_hub_flare_log_end( array( $LOG_OK ), '2026-11-18', '' ) === null );
check( 'end: a bad date gives null', vance_hub_flare_log_end( $LOG_OPEN, '2026-02-30', '' ) === null );
check( 'end: an end before the start is clamped to the start', ( vance_hub_flare_log_end( array( array( 'id' => 'o', 'start' => '2026-11-18', 'end' => '', 'helped' => '' ) ), '2026-11-10', '' )[0]['end'] ?? null ) === '2026-11-18' );
check( 'end: only the open row changes, the closed rows stay as they were', ( vance_hub_flare_log_end( array( $LOG_OK, $LOG_OPEN[0] ), '2026-11-18', 'x' )[0] ?? null ) === $LOG_OK );
$END_IN = $LOG_OPEN;
vance_hub_flare_log_end( $END_IN, '2026-11-18', 'Rest' );
check( 'end: the rows passed in are not changed', $END_IN === $LOG_OPEN );

fb_arrange( $FP_ON );
$ROWS_RT = array( array( 'id' => 'o', 'start' => '2026-11-16', 'end' => '2026-11-18', 'helped' => "Rest\nfluids" ) );
check( 'write: a log is stored and reads back equal', vance_hub_flare_log_write( 7, $ROWS_RT ) === true && vance_hub_flare_log_get( 7 ) === $ROWS_RT );

fb_arrange( $FP_ON );
check( 'write: a backslash in what helped is stored as typed', vance_hub_flare_log_write( 7, array( array( 'id' => 'o', 'start' => '2026-11-16', 'end' => '', 'helped' => 'C:\\Clinic' ) ) ) === true && ( vance_hub_flare_log_get( 7 )[0]['helped'] ?? null ) === 'C:\\Clinic' );

fb_arrange( $FP_ON );
check( 'write: two backslashes in what helped survive the round trip', vance_hub_flare_log_write( 7, array( array( 'id' => 'o', 'start' => '2026-11-16', 'end' => '', 'helped' => 'a\\\\b' ) ) ) === true && ( vance_hub_flare_log_get( 7 )[0]['helped'] ?? null ) === 'a\\\\b' );

fb_arrange( $FP_ON );
$GLOBALS['UPDATE_FAILS'] = '_sla_flare_log';
check( 'write: a failed store reports false', vance_hub_flare_log_write( 7, $LOG_OPEN ) === false );
check( 'write: a failed store leaves no log behind', fb_meta( '_sla_flare_log' ) === null );

fb_arrange( $FP_ON );
check( 'write: an empty log is refused and writes nothing', vance_hub_flare_log_write( 7, array() ) === false && $GLOBALS['WRITES'] === array() );
check( 'write: user id 0 is refused and writes nothing', vance_hub_flare_log_write( 0, $LOG_OPEN ) === false && $GLOBALS['WRITES'] === array() );

/* ===================================================================== */
section( '14. flare log action: result codes, the agreement, the plan, failed stores' );

fb_arrange( $FP_ON );
check( 'action: no saved plan gives noplan and writes nothing', vance_hub_flare_log_action( 7, 'flare_start', $LOG_NOW, '' ) === 'noplan' && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_flare_plan_urgent' => 'Call the clinic.' ) );
fb_stored_plan();
fb_consented();
check( 'action: the switch off gives noplan, even with a plan and an agreement', vance_hub_flare_log_action( 7, 'flare_start', $LOG_NOW, '' ) === 'noplan' && $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_consent_flare_plan_text'] = 'Old wording.';
check( 'action: an agreement to other wording gives changed and writes nothing', vance_hub_flare_log_action( 7, 'flare_start', $LOG_NOW, '' ) === 'changed' && $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_stored_plan();
check( 'action: no agreement on record gives changed and writes nothing', vance_hub_flare_log_action( 7, 'flare_start', $LOG_NOW, '' ) === 'changed' && $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
check( 'action: a start with a plan and an agreement gives flare_started', vance_hub_flare_log_action( 7, 'flare_start', $LOG_NOW, 'Rest' ) === 'flare_started' );
$LOG_AFTER_START = fb_meta( '_sla_flare_log' );
check( 'action: a start stores one open row dated on the day given', count( (array) $LOG_AFTER_START ) === 1 && ( $LOG_AFTER_START[0]['start'] ?? null ) === '2026-11-18' && ( $LOG_AFTER_START[0]['end'] ?? null ) === '' );
check( 'action: a start stores no helped text, even when some is sent', ( $LOG_AFTER_START[0]['helped'] ?? null ) === '' );

$WRITES_AFTER_START = $GLOBALS['WRITES'];
check( 'action: a second start while a flare is open gives flare_open', vance_hub_flare_log_action( 7, 'flare_start', $LOG_NOW, '' ) === 'flare_open' );
check( 'action: a refused second start leaves the log and the writes as they were', fb_meta( '_sla_flare_log' ) === $LOG_AFTER_START && $GLOBALS['WRITES'] === $WRITES_AFTER_START );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
check( 'action: an end gives flare_ended', vance_hub_flare_log_action( 7, 'flare_end', $LOG_NOW, "  Rest\r\nfluids <b>x</b>  " ) === 'flare_ended' );
// CHANGED (spec A): the text is cleaned (CRLF, control characters, trim) but no tag is stripped.
check( 'action: an end closes the open row with today, and what helped is cleaned, with its tags kept as typed', fb_meta( '_sla_flare_log' ) === array( array( 'id' => 'o', 'start' => '2026-11-16', 'end' => '2026-11-18', 'helped' => "Rest\nfluids <b>x</b>" ) ) );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
// CHANGED (spec B): over-length text is refused with toolong, never cut.
check( 'action: what helped of 600 characters is refused with toolong and writes nothing', vance_hub_flare_log_action( 7, 'flare_end', $LOG_NOW, str_repeat( 'h', 600 ) ) === 'toolong' && $GLOBALS['WRITES'] === array() && ( fb_meta( '_sla_flare_log' )[0]['end'] ?? null ) === '' );
check( 'action: what helped of exactly 500 characters is kept whole', vance_hub_flare_log_action( 7, 'flare_end', $LOG_NOW, str_repeat( 'h', 500 ) ) === 'flare_ended' && strlen( fb_meta( '_sla_flare_log' )[0]['helped'] ?? '' ) === 500 );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
vance_hub_flare_log_action( 7, 'flare_end', $LOG_NOW, 'C:\\\\Clinic' );
check( 'action: a posted double backslash in what helped arrives as one', ( fb_meta( '_sla_flare_log' )[0]['helped'] ?? null ) === 'C:\\Clinic' );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = array( $LOG_OK );
check( 'action: an end with no open flare gives flare_none and writes nothing', vance_hub_flare_log_action( 7, 'flare_end', $LOG_NOW, 'Rest' ) === 'flare_none' && $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UPDATE_FAILS'] = '_sla_flare_log';
check( 'action: a start whose store fails gives failed and leaves no log', vance_hub_flare_log_action( 7, 'flare_start', $LOG_NOW, '' ) === 'failed' && fb_meta( '_sla_flare_log' ) === null );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
$GLOBALS['UPDATE_FAILS'] = '_sla_flare_log';
check( 'action: an end whose store fails gives failed and the flare stays open', vance_hub_flare_log_action( 7, 'flare_end', $LOG_NOW, 'Rest' ) === 'failed' && ( fb_meta( '_sla_flare_log' )[0]['end'] ?? null ) === '' );

/* ===================================================================== */
section( '15. POST handler, flare start and end: lock, gating, delete with the log, check-in untouched' );

$REAL_TODAY = gmdate( 'Y-m-d' );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
fb_post_flare( 'flare_start' );
check( 'handler start: redirects with vance_flare=flare_started', fb_handle() === 'https://example.test/hub/?vance_flare=flare_started' );
check( 'handler start: the open flare is stored dated today', ( fb_meta( '_sla_flare_log' )[0]['start'] ?? null ) === $REAL_TODAY && ( fb_meta( '_sla_flare_log' )[0]['end'] ?? null ) === '' );
check( 'handler start: the lock is taken first and released last, once', fb_seq_locked_once() && fb_seq_index( 'lock' ) === 0 );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
fb_post_flare( 'flare_end', "  Rest\r\n<i>warm</i>  " );
check( 'handler end: redirects with vance_flare=flare_ended', fb_handle() === 'https://example.test/hub/?vance_flare=flare_ended' );
check( 'handler end: the open flare is closed, with what helped cleaned (tags kept as typed)', ( fb_meta( '_sla_flare_log' )[0]['helped'] ?? null ) === "Rest\n<i>warm</i>" &&( fb_meta( '_sla_flare_log' )[0]['start'] ?? null ) === '2026-11-16' );
check( 'handler end: the end is not before the start', ( fb_meta( '_sla_flare_log' )[0]['end'] ?? '' ) >= '2026-11-16' );
check( 'handler end: the lock is released last, once', fb_seq_locked_once() );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['LOCK_RESULT'] = '0';
fb_post_flare( 'flare_start' );
check( 'handler start lock: without the lock the result is failed', fb_handle() === 'https://example.test/hub/?vance_flare=failed' );
check( 'handler start lock: without the lock nothing is written and nothing is unlocked', $GLOBALS['WRITES'] === array() && $GLOBALS['UNLOCK_SQL'] === array() && fb_meta( '_sla_flare_log' ) === null );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
$GLOBALS['LOCK_RESULT'] = '0';
fb_post_flare( 'flare_end', 'Rest' );
check( 'handler end lock: without the lock the result is failed and the flare stays open', fb_handle() === 'https://example.test/hub/?vance_flare=failed' && ( fb_meta( '_sla_flare_log' )[0]['end'] ?? null ) === '' );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
fb_post_flare( 'flare_start' );
$_POST['vance_hub_flare_nonce'] = 'forged';
check( 'handler flare: a bad nonce redirects with vance_flare=expired', fb_handle() === 'https://example.test/hub/?vance_flare=expired' );
check( 'handler flare: a bad nonce takes no lock and writes nothing', $GLOBALS['LOCK_SQL'] === array() && $GLOBALS['WRITES'] === array() );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_flare_plan_urgent' => 'Call the clinic.' ) );
fb_stored_plan();
fb_consented();
fb_post_flare( 'flare_start' );
// CHANGED (spec F): the switch off redirects with failed instead of returning silently.
check( 'handler flare: the switch off blocks a start (failed), and nothing is written or locked', fb_handle() === 'https://example.test/hub/?vance_flare=failed' && $GLOBALS['WRITES'] === array() && $GLOBALS['LOCK_SQL'] === array() );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
fb_post_flare( 'flare_stop' );
check( 'handler flare: an unknown action similar to a flare action gives invalid, writes nothing, takes no lock', fb_handle() === 'https://example.test/hub/?vance_flare=invalid' && $GLOBALS['WRITES'] === array() && $GLOBALS['LOCK_SQL'] === array() );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
fb_post_delete();
check( 'delete log: redirects with vance_flare=deleted', fb_handle() === 'https://example.test/hub/?vance_flare=deleted' );
check( 'delete log: the plan and the flare log are both removed', fb_meta( '_sla_flare_plan' ) === null && fb_meta( '_sla_flare_log' ) === null );
check( 'delete log: the agreement is withdrawn, and its record is kept', fb_meta( '_sla_consent_flare_plan' ) === null && fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === '2026-11-18 12:00:00' && fb_meta( '_sla_consent_flare_plan_at' ) === '2026-10-01 09:00:00' );
check( 'delete log: the lock is released last, once', fb_seq_locked_once() );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
$GLOBALS['DELETE_FAILS'] = '_sla_flare_log';
fb_post_delete();
check( 'delete log read-back: a log that is still there gives kept', fb_handle() === 'https://example.test/hub/?vance_flare=kept' );
check( 'delete log read-back: the log stays and the agreement is not withdrawn', fb_meta( '_sla_flare_log' ) !== null && fb_meta( '_sla_consent_flare_plan' ) === '1' && fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === null );

// The check-in record of the same member: no flare action may touch it.
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true, 'vance_hub_flare_plan_urgent' => 'Call the clinic.', 'vance_hub_dash_flare_plan' => true ) );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_consent_checkin']         = '1';
$GLOBALS['UMETA'][7]['_sla_consent_checkin_at']      = '2026-10-01 09:00:00';
$GLOBALS['UMETA'][7]['_sla_consent_checkin_text']    = vance_hub_checkin_consent_text();
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( array( 'id' => 'c1', 'ts' => $LOG_NOW - 86400, 'payload' => array( 'pain' => 2, 'bowel' => 1, 'tired' => 0, 'kind' => 'check-in' ) ) );
$HIST_BEFORE = $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'];
fb_post_flare( 'flare_start' );
fb_handle();
check( 'check-in untouched: a flare start writes no check-in key and no check-in history', preg_grep( '/_sla_consent_checkin|_sla_weekly_check_in_history/', $GLOBALS['WRITES'] ) === array() );
check( 'check-in untouched: after a flare start the check-in agreement and history are the same', fb_meta( '_sla_consent_checkin' ) === '1' && fb_meta( '_sla_weekly_check_in_history' ) === $HIST_BEFORE );
fb_post_flare( 'flare_end', 'Rest' );
fb_handle();
check( 'check-in untouched: a flare end writes no check-in key and no check-in history', preg_grep( '/_sla_consent_checkin|_sla_weekly_check_in_history/', $GLOBALS['WRITES'] ) === array() );
fb_post_delete();
fb_handle();
check( 'check-in untouched: a delete leaves the check-in agreement and history as they were', fb_meta( '_sla_consent_checkin' ) === '1' && fb_meta( '_sla_weekly_check_in_history' ) === $HIST_BEFORE && preg_grep( '/_sla_consent_checkin/', $GLOBALS['WRITES'] ) === array() );

/* ===================================================================== */
section( '16. plan progress: answers and the six-month review' );

$PLAN_X = array( 'updated' => $OLD_TS, 'signs' => 'Tight belly', 'actions' => 'Fluids', 'contacts' => 'Nurse' );
$DAY    = 86400;

$P4 = vance_hub_flare_plan_progress( array( 'updated' => $LOG_NOW, 'signs' => 'a', 'actions' => 'b', 'contacts' => 'c' ), $LOG_NOW );
check( 'progress: three answers and a review in the last 183 days give 4 of 4', $P4['done'] === 4 && $P4['pct'] === 100 && $P4['missing'] === array() && $P4['stale'] === false && $P4['total'] === 4 );

$P_182 = vance_hub_flare_plan_progress( array( 'updated' => $LOG_NOW - 182 * $DAY, 'signs' => 'a', 'actions' => 'b', 'contacts' => 'c' ), $LOG_NOW );
check( 'progress: a review 182 days old is still in date, 4 of 4', $P_182['done'] === 4 && $P_182['stale'] === false );

$P_183 = vance_hub_flare_plan_progress( array( 'updated' => $LOG_NOW - 183 * $DAY, 'signs' => 'a', 'actions' => 'b', 'contacts' => 'c' ), $LOG_NOW );
check( 'progress: a review exactly 183 days old is stale, so 3 of 4 and 75 percent', $P_183['done'] === 3 && $P_183['stale'] === true && $P_183['pct'] === 75 );
check( 'progress: a review 183 days old counts 6 months', $P_183['months'] === 6 );
check( 'progress: a review 59 days old counts 1 month', vance_hub_flare_plan_progress( array( 'updated' => $LOG_NOW - 59 * $DAY, 'signs' => 'a', 'actions' => 'b', 'contacts' => 'c' ), $LOG_NOW )['months'] === 1 );

$P_NONE = vance_hub_flare_plan_progress( array( 'updated' => $LOG_NOW - 200 * $DAY, 'signs' => '', 'actions' => '', 'contacts' => '' ), $LOG_NOW );
check( 'progress: no answers and a stale review give 0 of 4, 0 percent', $P_NONE['done'] === 0 && $P_NONE['pct'] === 0 && $P_NONE['stale'] === true );
check( 'progress: with no answers all three sections are listed as missing, in order', $P_NONE['missing'] === array( 'signs', 'actions', 'contacts' ) );

$P_ONE = vance_hub_flare_plan_progress( array( 'updated' => $LOG_NOW, 'signs' => 'a', 'actions' => '', 'contacts' => '  ' ), $LOG_NOW );
check( 'progress: one answer and a fresh review give 2 of 4, 50 percent', $P_ONE['done'] === 2 && $P_ONE['pct'] === 50 );
check( 'progress: a blank answer, even spaces only, is listed as missing', $P_ONE['missing'] === array( 'actions', 'contacts' ) );

/* ===================================================================== */
section( '17. flare log view: open flare, five newest rows, count' );

$V_EMPTY = vance_hub_flare_log_view( array(), $LOG_NOW );
check( 'log view: no rows gives no open flare, no rows and a count of 0', $V_EMPTY === array( 'open' => null, 'rows' => array(), 'count' => 0 ) );

$V_OPEN = vance_hub_flare_log_view( $LOG_OPEN, $LOG_NOW );
check( 'log view: an open flare gives its start label and its days so far, 16 to 18 Nov is 3', ( $V_OPEN['open']['start'] ?? null ) === '16 Nov 2026' && ( $V_OPEN['open']['days'] ?? null ) === 3 && ( $V_OPEN['open']['end'] ?? null ) === '' );

$V_CLOSED = vance_hub_flare_log_view( array( $LOG_OK ), $LOG_NOW );
// CHANGED (spec H): each row of the view also carries its id, for the "Remove this entry" form.
check( 'log view: a closed row gives its id, start and end labels, its days and what helped', $V_CLOSED['rows'] === array( array( 'id' => 'a1', 'start' => '1 Nov 2026', 'end' => '3 Nov 2026', 'days' => 3, 'helped' => 'Rest' ) ) && $V_CLOSED['open'] === null );

$SEVEN = array_map( function ( $i ) {
	return array( 'id' => 'r' . $i, 'start' => '2026-10-01', 'end' => '2026-10-02', 'helped' => 'h' . $i );
}, range( 0, 6 ) );
$V_SEVEN = vance_hub_flare_log_view( $SEVEN, $LOG_NOW );
check( 'log view: seven rows show five, and the count still says seven', count( $V_SEVEN['rows'] ) === 5 && $V_SEVEN['count'] === 7 );
check( 'log view: the five shown are the five newest, in stored order', ( $V_SEVEN['rows'][0]['helped'] ?? null ) === 'h0' && ( $V_SEVEN['rows'][4]['helped'] ?? null ) === 'h4' );

/* ===================================================================== */
section( '18. quick check data: the last saved check-in' );

$GLOBALS['OPEN_GATES'] = true;

fb_arrange( $FP_ON );
check( 'quick: no check-in history gives null', vance_hub_flare_plan_quick_data( 7 ) === null );

$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( array( 'id' => 'c1', 'ts' => strtotime( '2026-11-18 09:00:00 UTC' ), 'payload' => array( 'pain' => 1, 'bowel' => 3, 'tired' => 2, 'kind' => 'check-in' ) ) );
check( 'quick: the newest check-in gives its date and its three answers', vance_hub_flare_plan_quick_data( 7 ) === array( 'date' => '18 Nov 2026', 'pain' => 1, 'bowel' => 3, 'tired' => 2 ) );

$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array(
	array( 'id' => 'c2', 'ts' => strtotime( '2026-11-18 09:00:00 UTC' ), 'payload' => array( 'pain' => 1, 'bowel' => 0, 'tired' => 0, 'kind' => 'check-in' ) ),
	array( 'id' => 'c1', 'ts' => strtotime( '2026-11-10 09:00:00 UTC' ), 'payload' => array( 'pain' => 4, 'bowel' => 0, 'tired' => 0, 'kind' => 'check-in' ) ),
);
check( 'quick: an older check-in is never used, the first row is the newest', ( vance_hub_flare_plan_quick_data( 7 )['pain'] ?? null ) === 1 );

$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( array( 'id' => 'c1', 'ts' => strtotime( '2026-11-18 09:00:00 UTC' ), 'payload' => array( 'pain' => 9, 'bowel' => -2, 'tired' => 4, 'kind' => 'check-in' ) ) );
check( 'quick: answers above 4 are held at 4, and below 0 at 0', vance_hub_flare_plan_quick_data( 7 ) === array( 'date' => '18 Nov 2026', 'pain' => 4, 'bowel' => 0, 'tired' => 4 ) );

$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( array( 'id' => 'c1', 'ts' => strtotime( '2026-11-18 09:00:00 UTC' ), 'payload' => array( 'pain' => 'x', 'bowel' => 1, 'tired' => 1, 'kind' => 'check-in' ) ) );
check( 'quick: a damaged answer gives null', vance_hub_flare_plan_quick_data( 7 ) === null );

$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( array( 'id' => 'c1', 'ts' => strtotime( '2026-11-18 09:00:00 UTC' ), 'payload' => array( 'pain' => 1, 'bowel' => 1, 'kind' => 'check-in' ) ) );
check( 'quick: a check-in missing one answer gives null', vance_hub_flare_plan_quick_data( 7 ) === null );

$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( array( 'id' => 'c1', 'ts' => 'junk', 'payload' => array( 'pain' => 1, 'bowel' => 1, 'tired' => 1, 'kind' => 'check-in' ) ) );
check( 'quick: a check-in with a damaged time gives null, not a date of 1970', vance_hub_flare_plan_quick_data( 7 ) === null );

/* ===================================================================== */
section( '19. appointment sheet data: nothing new stored, empty groups, caps, screening' );

fb_arrange( $FP_ON );
$S_EMPTY = vance_hub_flare_sheet_data( 7, $PLAN_X, array(), $LOG_NOW );
check( 'sheet empty: no profile, history, screening or flares gives empty groups only', $S_EMPTY === array( 'prepared' => '18 Nov 2026', 'appointment' => '', 'questions' => '', 'medication' => '', 'checkins' => array(), 'screening' => null, 'flares' => array(), 'flare_count' => 0 ) );

fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_clinical_profile'] = array( 'next_appointment' => '2026-12-03', 'appointment_questions' => "  Can I eat oats?\nIs iron safe? ", 'medication' => '  Iron tablets  ' );
$S_PROFILE = vance_hub_flare_sheet_data( 7, $PLAN_X, array(), $LOG_NOW );
check( 'sheet: the next appointment date reads as 3 Dec 2026', $S_PROFILE['appointment'] === '3 Dec 2026' );
check( 'sheet: the questions keep their line break and lose the outer spaces', $S_PROFILE['questions'] === "Can I eat oats?\nIs iron safe?" );
check( 'sheet: the medication is trimmed', $S_PROFILE['medication'] === 'Iron tablets' );

fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_clinical_profile'] = array( 'next_appointment' => 'soon' );
check( 'sheet: an appointment date that does not parse gives an empty date', vance_hub_flare_sheet_data( 7, $PLAN_X, array(), $LOG_NOW )['appointment'] === '' );

fb_arrange( $FP_ON );
$HIST20 = array_map( function ( $i ) use ( $LOG_NOW ) {
	return array( 'id' => 'c' . $i, 'ts' => $LOG_NOW - $i * 86400, 'payload' => array( 'pain' => $i % 5, 'bowel' => 0, 'tired' => 0, 'kind' => 'check-in' ) );
}, range( 0, 19 ) );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = $HIST20;
$S_CI = vance_hub_flare_sheet_data( 7, $PLAN_X, array(), $LOG_NOW );
check( 'sheet: the check-in table holds the 14 newest rows, no more', count( $S_CI['checkins'] ) === 14 );
check( 'sheet: the first check-in row is the newest, with its date', ( $S_CI['checkins'][0] ?? null ) === array( 'date' => '18 Nov 2026', 'pain' => 0, 'bowel' => 0, 'tired' => 0 ) );

fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array(
	array( 'id' => 'bad', 'ts' => $LOG_NOW, 'payload' => array( 'pain' => 1, 'kind' => 'check-in' ) ),
	array( 'id' => 'ok', 'ts' => $LOG_NOW - 86400, 'payload' => array( 'pain' => 2, 'bowel' => 2, 'tired' => 2, 'kind' => 'check-in' ) ),
);
$S_BAD = vance_hub_flare_sheet_data( 7, $PLAN_X, array(), $LOG_NOW );
check( 'sheet: a check-in with a missing answer is left out of the table', count( $S_BAD['checkins'] ) === 1 && ( $S_BAD['checkins'][0]['pain'] ?? null ) === 2 );

fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_malnutrition_calculator_history'] = array(
	array( 'id' => 'm1', 'ts' => strtotime( '2026-11-02 10:00:00 UTC' ), 'payload' => array( 'score' => 5, 'maxScore' => 8, 'riskLabel' => 'Low risk' ) ),
	array( 'id' => 'm2', 'ts' => strtotime( '2026-11-14 10:00:00 UTC' ), 'payload' => array( 'score' => 3, 'maxScore' => 8, 'riskLabel' => 'Medium risk' ) ),
);
$S_SCREEN = vance_hub_flare_sheet_data( 7, $PLAN_X, array(), $LOG_NOW );
// CHANGED (spec G): the risk label is no longer on the sheet. Score, maximum and date stay.
check( 'sheet: the newest nutrition screening gives its date, score and maximum, and no risk label', $S_SCREEN['screening'] === array( 'date' => '14 Nov 2026', 'score' => '3', 'max' => '8' ) );

fb_arrange( $FP_ON );
$S_FL = vance_hub_flare_sheet_data( 7, $PLAN_X, $LOG_OPEN, $LOG_NOW );
check( 'sheet: the flares are the log view rows, with the count of the whole log', count( $S_FL['flares'] ) === 1 && $S_FL['flare_count'] === 1 && ( $S_FL['flares'][0]['days'] ?? null ) === 3 );

/* ===================================================================== */
section( '20. render: progress bar, quick check, flare log, builder, print buttons, sheet' );

$PROG = array( 'done' => 2, 'total' => 4, 'pct' => 50, 'missing' => array( 'contacts' ), 'stale' => false, 'months' => 0 );
$H_PROG = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'progress' => $PROG ) ) );
check( 'progress: the bar is a progressbar with 2 of 3 sections written', strpos( $H_PROG, 'role="progressbar" aria-valuemin="0" aria-valuemax="3" aria-valuenow="2"' ) !== false );
check( 'progress: the bar fill width is 2 of 3 as a percentage (67)', strpos( $H_PROG, '<span style="width:67%"></span>' ) !== false );
check( 'progress: the text reads "2 of 3 sections written"', strpos( $H_PROG, '>2 of 3 sections written</p>' ) !== false );
check( 'progress: a missing section is named in a hint', strpos( $H_PROG, '<li>Add: Who I contact</li>' ) !== false );
check( 'progress: a fresh review gives no review hint', strpos( $H_PROG, 'You last changed your plan' ) === false );

$H_STALE = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'progress' => array( 'done' => 3, 'total' => 4, 'pct' => 75, 'missing' => array(), 'stale' => true, 'months' => 7 ) ) ) );
check( 'progress: an old review gives its own paragraph, not a list item', strpos( $H_STALE, '<p class="vhd-flare__review">You last changed this plan more than 6 months ago. Check that it is still correct.</p>' ) !== false && strpos( $H_STALE, '<li>You last changed' ) === false );
check( 'progress: no progress bar is drawn without progress data', strpos( vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026' ) ) ), 'role="progressbar"' ) === false );

$H_Q = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'quick' => array( 'date' => '18 Nov 2026', 'pain' => 1, 'bowel' => 3, 'tired' => 2 ) ) ) );
check( 'quick: the block is hidden in the markup, so it shows only with the script', strpos( $H_Q, '<section class="vhd-flare__quick" data-vhd-flare-quick data-config="' ) !== false && strpos( $H_Q, '" hidden aria-labelledby="vhd-flare-quick-title">' ) !== false );
check( 'quick: the config holds the last check-in values and date', ( fb_config( $H_Q )['last_v'] ?? null ) === array( 'pain' => 1, 'bowel' => 3, 'tired' => 2 ) && ( fb_config( $H_Q )['last_at'] ?? null ) === '18 Nov 2026' );
check( 'quick: the config holds the three labels, in order', array_keys( fb_config( $H_Q )['labels'] ?? array() ) === array( 'pain', 'bowel', 'tired' ) );
check( 'quick: the compare button and the result box are in the block', strpos( $H_Q, 'data-vhd-quick-show' ) !== false && strpos( $H_Q, 'data-vhd-quick-result' ) !== false );

$H_Q_NONE = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'quick' => null ) ) );
// array_key_exists, not ??: ?? cannot tell a stored null from a missing key.
check( 'quick: with no check-in the config has last_v null, and last_at empty', array_key_exists( 'last_v', fb_config( $H_Q_NONE ) ?? array() ) && fb_config( $H_Q_NONE )['last_v'] === null && ( fb_config( $H_Q_NONE )['last_at'] ?? null ) === '' );

$H_Q_EVIL = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'quick' => array( 'date' => '</script>"x\'', 'pain' => 0, 'bowel' => 0, 'tired' => 0 ) ) ) );
check( 'quick: a value with quotes and a closing script tag keeps the config valid JSON', ( fb_config( $H_Q_EVIL )['last_at'] ?? null ) === '</script>"x\'' );
check( 'quick: no raw closing script tag appears in the card', strpos( $H_Q_EVIL, '</script>' ) === false );

$LOG_OPEN_VIEW = array( 'open' => array( 'start' => '16 Nov 2026', 'end' => '', 'days' => 3, 'helped' => '' ), 'rows' => array( array( 'start' => '16 Nov 2026', 'end' => '', 'days' => 3, 'helped' => '' ) ), 'count' => 1 );
$H_LOG_OPEN = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'log' => $LOG_OPEN_VIEW ) ) );
check( 'log: an open flare shows its start and the day number', strpos( $H_LOG_OPEN, 'I am in a flare. It started on 16 Nov 2026. Today is day 3.' ) !== false );
check( 'log: an open flare shows the end form and not the start button', strpos( $H_LOG_OPEN, 'name="vance_hub_flare_do" value="flare_end"' ) !== false && strpos( $H_LOG_OPEN, 'value="flare_start"' ) === false );
check( 'log: the end form has the what-helped box with its limit of 500', strpos( $H_LOG_OPEN, 'name="vhd_flare_helped" rows="3" maxlength="500"' ) !== false );
check( 'log: the history line of an open flare says the days so far', strpos( $H_LOG_OPEN, '16 Nov 2026 to now (3 days so far)' ) !== false );
check( 'log: one flare logged is counted in the singular', strpos( $H_LOG_OPEN, '1 flare logged.' ) !== false );

$LOG_CLOSED_VIEW = array( 'open' => null, 'rows' => array( array( 'start' => '1 Nov 2026', 'end' => '3 Nov 2026', 'days' => 3, 'helped' => "Rest\nwarm <b>tea</b>" ) ), 'count' => 1 );
$H_LOG_CLOSED = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'log' => $LOG_CLOSED_VIEW ) ) );
check( 'log: no open flare shows the start button and not the end form', strpos( $H_LOG_CLOSED, 'name="vance_hub_flare_do" value="flare_start"' ) !== false && strpos( $H_LOG_CLOSED, 'value="flare_end"' ) === false );
check( 'log: a closed flare shows its dates and its days', strpos( $H_LOG_CLOSED, '1 Nov 2026 to 3 Nov 2026 (3 days)' ) !== false );
check( 'log: what helped is escaped and keeps its line break', strpos( $H_LOG_CLOSED, "Rest<br />\nwarm &lt;b&gt;tea&lt;/b&gt;" ) !== false && strpos( $H_LOG_CLOSED, '<b>tea</b>' ) === false );

$H_ENDED = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'flash' => 'flare_ended' ) ) );
check( 'flash: flare_ended opens the Edit my plan box', strpos( $H_ENDED, '<details class="vhd-flare__edit" open>' ) !== false );
check( 'flash: flare_ended shows a success message, not a warning', strpos( $H_ENDED, 'Your flare is logged as ended.' ) !== false && strpos( $H_ENDED, 'vhd-flash--warn' ) === false );
check( 'flash: with no flash message the Edit box stays closed', strpos( $H_PLAN, '<details class="vhd-flare__edit">' ) !== false );
$H_FSTART = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'flash' => 'flare_started' ) ) );
check( 'flash: flare_started shows a success message', strpos( $H_FSTART, 'Your flare is logged.' ) !== false && strpos( $H_FSTART, 'vhd-flash--warn' ) === false );
$H_FOPEN = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'flash' => 'flare_open' ) ) );
check( 'flash: flare_open shows a warning', strpos( $H_FOPEN, 'A flare is already open. End it first.' ) !== false && strpos( $H_FOPEN, 'vhd-flash--warn' ) !== false );
$H_FNONE = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'flash' => 'flare_none' ) ) );
check( 'flash: flare_none shows a warning', strpos( $H_FNONE, 'There is no open flare to end.' ) !== false && strpos( $H_FNONE, 'vhd-flash--warn' ) !== false );

$H_NEW = vance_hub_flare_plan_html( fb_flare_view() );
check( 'builder: a new plan form has data-vhd-wizard and the final step', strpos( $H_NEW, 'action="" data-vhd-wizard ' ) !== false && strpos( $H_NEW, 'vhd-flare__final' ) !== false );
check( 'builder: a saved plan edit form has no data-vhd-wizard', strpos( $H_PLAN, 'data-vhd-wizard' ) === false );
check( 'builder: a saved plan edit form has no data-vhd-wizard (the final wrapper is only layout)', strpos( $H_PLAN, 'data-vhd-wizard' ) === false );

$H_PRINT_SHEET = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'sheet' => array( 'prepared' => '18 Nov 2026', 'appointment' => '', 'questions' => 'Q', 'medication' => '', 'checkins' => array(), 'screening' => null, 'flares' => array(), 'flare_count' => 0 ) ) ) );
check( 'print: the plan print button is hidden and names the plan target', strpos( $H_PLAN, 'data-vhd-flare-print="plan" hidden>' ) !== false );
check( 'print: the sheet print button is hidden and names the sheet target', strpos( $H_PRINT_SHEET, 'data-vhd-flare-print="sheet" hidden>' ) !== false );
check( 'print: no sheet print button without a sheet', strpos( $H_PLAN, 'data-vhd-flare-print="sheet"' ) === false );

$SHEET_FULL = array(
	'prepared'    => '18 Nov 2026',
	'appointment' => '3 Dec 2026',
	'questions'   => 'Can I eat oats?',
	'medication'  => 'Iron tablets',
	'checkins'    => array( array( 'date' => '18 Nov 2026', 'pain' => 1, 'bowel' => 3, 'tired' => 2 ) ),
	'screening'   => array( 'date' => '14 Nov 2026', 'score' => '3', 'max' => '8', 'risk' => 'Medium risk' ),
	'flares'      => array( array( 'start' => '1 Nov 2026', 'end' => '3 Nov 2026', 'days' => 3, 'helped' => 'Rest' ) ),
	'flare_count' => 1,
);
$H_SHEET = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'sheet' => $SHEET_FULL ) ) );
check( 'sheet: the heading, the prepared line and the next appointment show', strpos( $H_SHEET, '<h3 class="vhd-flare__h">Appointment sheet</h3>' ) !== false && strpos( $H_SHEET, 'Prepared on 18 Nov 2026 from what I saved in the Hub.' ) !== false && strpos( $H_SHEET, '<p>3 Dec 2026</p>' ) !== false );
check( 'sheet: the questions, the medication and the plan answers show', strpos( $H_SHEET, '<p>Can I eat oats?</p>' ) !== false && strpos( $H_SHEET, '<p>Iron tablets</p>' ) !== false );
check( 'sheet: a flare line gives dates, days and what helped', strpos( $H_SHEET, '1 Nov 2026 to 3 Nov 2026 (3 days) - what helped: Rest' ) !== false );
check( 'sheet: a check-in row gives the date and the three numbers', strpos( $H_SHEET, '<td>18 Nov 2026</td><td>1</td><td>3</td><td>2</td>' ) !== false );
// CHANGED (spec G): the fixture still holds a 'risk' key. The sheet must not print it.
check( 'sheet: the screening line gives score, maximum and date, and never the risk label', strpos( $H_SHEET, 'Score 3 of 8, on 14 Nov 2026.' ) !== false && strpos( $H_SHEET, 'Medium risk' ) === false );

$SHEET_XSS = array(
	'prepared'    => '18 Nov 2026',
	'appointment' => '',
	'questions'   => '<script>alert(1)</script>',
	'medication'  => '<img src=x onerror=alert(1)>',
	'checkins'    => array(),
	'screening'   => null,
	'flares'      => array( array( 'start' => '1 Nov 2026', 'end' => '', 'days' => 2, 'helped' => '<b>heat</b>' ) ),
	'flare_count' => 1,
);
$PLAN_EVIL = array( 'updated' => $OLD_TS, 'signs' => '<script>x</script>', 'actions' => 'Fluids', 'contacts' => 'Nurse' );
$H_XSS = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_EVIL, 'updated' => '10 Nov 2026', 'sheet' => $SHEET_XSS ) ) );
check( 'sheet escape: a script in the questions is shown as text', strpos( $H_XSS, '<script>alert(1)' ) === false && strpos( $H_XSS, '&lt;script&gt;alert(1)&lt;/script&gt;' ) !== false );
check( 'sheet escape: markup in the medication is shown as text', strpos( $H_XSS, '<img src=x' ) === false && strpos( $H_XSS, '&lt;img src=x onerror=alert(1)&gt;' ) !== false );
check( 'sheet escape: markup in what helped on the sheet is shown as text', strpos( $H_XSS, '<b>heat</b>' ) === false && strpos( $H_XSS, '&lt;b&gt;heat&lt;/b&gt;' ) !== false );
check( 'sheet escape: a script in a plan answer is not written raw anywhere in the card', substr_count( $H_XSS, '<script>' ) === 0 );

$SHEET_BARE = array( 'prepared' => '18 Nov 2026', 'appointment' => '', 'questions' => '', 'medication' => '', 'checkins' => array(), 'screening' => null, 'flares' => array(), 'flare_count' => 0 );
$H_BARE = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'sheet' => $SHEET_BARE ) ) );
check( 'sheet empty: the heading shows and the empty groups are left out', strpos( $H_BARE, '<h3 class="vhd-flare__h">Appointment sheet</h3>' ) !== false && strpos( $H_BARE, 'Next appointment' ) === false && strpos( $H_BARE, 'Questions to ask' ) === false && strpos( $H_BARE, 'Medication' ) === false && strpos( $H_BARE, 'My flares' ) === false && strpos( $H_BARE, 'Recent check-ins' ) === false && strpos( $H_BARE, 'Latest nutrition risk check' ) === false );

/* ===================================================================== */
section( '21. privacy: the flare log in the exporter and the eraser' );

check( 'erase keys: the flare log key is one of the keys the eraser clears', in_array( '_sla_flare_log', vance_hub_flare_plan_all_meta_keys(), true ) );

fb_arrange( $FP_ON );
$GLOBALS['USERS_BY_EMAIL']['ruth@example.test'] = (object) array( 'ID' => 7 );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = array(
	array( 'id' => 'b', 'start' => '2026-11-01', 'end' => '2026-11-03', 'helped' => 'Rest' ),
	array( 'id' => 'a', 'start' => '2026-10-02', 'end' => '', 'helped' => '' ),
);
$EXP_L = vance_hub_flare_plan_export( 'ruth@example.test' );
check( 'export log: a closed flare is one row with its dates and what helped', ( $EXP_L['data'][0]['data'][4] ?? null ) === array( 'name' => 'Flare (start / end)', 'value' => '2026-11-01 / 2026-11-03 / What helped: Rest' ) );
check( 'export log: a flare not yet ended says so', ( $EXP_L['data'][0]['data'][5] ?? null ) === array( 'name' => 'Flare (start / end)', 'value' => '2026-10-02 / not ended' ) );
check( 'export log: the agreement record follows the flares', ( $EXP_L['data'][0]['data'][6] ?? null ) === array( 'name' => 'Agreed on', 'value' => '2026-10-01 09:00:00' ) );

fb_arrange( $FP_ON );
$GLOBALS['USERS_BY_EMAIL']['ruth@example.test'] = (object) array( 'ID' => 7 );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = array( array( 'id' => 'b', 'start' => '2026-11-01', 'end' => '2026-11-03', 'helped' => '' ) );
$EXP_LO = vance_hub_flare_plan_export( 'ruth@example.test' );
check( 'export log only: a member with a flare log and no plan gets the flares', count( $EXP_LO['data'] ) === 1 && ( $EXP_LO['data'][0]['data'][0] ?? null ) === array( 'name' => 'Flare (start / end)', 'value' => '2026-11-01 / 2026-11-03' ) );

fb_arrange( $FP_ON );
$GLOBALS['USERS_BY_EMAIL']['ruth@example.test'] = (object) array( 'ID' => 7 );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
$ERA_L = vance_hub_flare_plan_erase( 'ruth@example.test' );
check( 'erase log: the flare log is removed with the plan, and nothing is retained', fb_meta( '_sla_flare_log' ) === null && $ERA_L['items_removed'] === true && $ERA_L['items_retained'] === false );

fb_arrange( $FP_ON );
$GLOBALS['USERS_BY_EMAIL']['ruth@example.test'] = (object) array( 'ID' => 7 );
fb_stored_plan();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
$GLOBALS['DELETE_FAILS'] = '_sla_flare_log';
$ERA_R = vance_hub_flare_plan_erase( 'ruth@example.test' );
check( 'erase log: a flare log that cannot be deleted is reported as retained', $ERA_R['items_retained'] === true && count( $ERA_R['messages'] ) === 1 );
check( 'erase log: the retained message names the log key, and the log is still there', strpos( $ERA_R['messages'][0] ?? '', '_sla_flare_log' ) !== false && fb_meta( '_sla_flare_log' ) !== null );

/* ===================================================================== */
section( '22. static: print script, print CSS, hidden rule' );

check( 'script: marks the part to print with vhd-print-target', strpos( $JS, 'vhd-print-target' ) !== false );
check( 'script: the sheet is printed only from an open sheet box', strpos( $JS, "'.vhd-flare__sheetbox[open]'" ) !== false );
check( 'script: member text is written with textContent', strpos( $JS, 'textContent' ) !== false );
check( 'script: no innerHTML anywhere in the file', strpos( $JS, 'innerHTML' ) === false );
check( 'script: no other HTML-writing sink (outerHTML, insertAdjacentHTML, document.write)', preg_match( '/outerHTML|insertAdjacentHTML|document\.write/', $JS ) === 0 );
check( 'script: sends nothing (no fetch, XMLHttpRequest or sendBeacon)', preg_match( '/fetch\s*\(|XMLHttpRequest|sendBeacon/', $JS ) === 0 );
check( 'script: the quick check and the print buttons are shown only by the script', strpos( $JS, 'box.hidden = false' ) !== false && strpos( $JS, 'button.hidden = false' ) !== false );
check( 'css: the hidden rule keeps hidden flare parts hidden, with !important', strpos( $CSS, '.vhd .vhd-flare [hidden] { display: none !important; }' ) !== false );

// The flare print block is the one after its comment. The sub-tab print rule
// elsewhere in the file is not part of this change and is not checked here.
preg_match( '/Print: only the part the member prints.*?@media print \{(.*?)\r?\n\}\r?\n/s', $CSS, $PM );
preg_match_all( '/([^{}]+)\{/', $PM[1] ?? '', $SEL );
check( 'css print: the flare print block is found and holds its rules', count( $SEL[1] ?? array() ) >= 8 );
// The whitespace is inside the lookahead: a leading \s* outside it can give back its
// whitespace and let the lookahead see a newline, which passes every selector.
check( 'css print: every rule in it is under html.vhd-printing or a print marker', preg_grep( '/^(?!\s*(?:html\.vhd-printing|\.vhd-print-path|\.vhd-print-target))/', $SEL[1] ?? array() ) === array() );
check( 'css print: negative control, a bare body rule is caught by the same pattern', count( preg_grep( '/^(?!\s*(?:html\.vhd-printing|\.vhd-print-path|\.vhd-print-target))/', array( "\tbody * " ) ) ) === 1 );
check( 'css print: negative control, a marked rule is not caught by the same pattern', count( preg_grep( '/^(?!\s*(?:html\.vhd-printing|\.vhd-print-path|\.vhd-print-target))/', array( "\n\thtml.vhd-printing .vhd-flare { position: static; }" ) ) ) === 0 );

/* ===================================================================== */
section( '23. dates: a stored day reads as the same day in any site time zone' );

$D23_NOW  = strtotime( '2026-11-18 12:00:00 UTC' );
$D23_PLAN = array( 'updated' => $OLD_TS, 'signs' => 'a', 'actions' => 'b', 'contacts' => 'c' );
$FP_SRC   = file_get_contents( $THEME . '/inc/hub-flare-plan.php' );

// The stub itself. It must apply the zone it is given, or the checks below prove nothing.
check( 'stub: wp_date applies America/Los_Angeles (12:00 UTC on 12 Nov is 04:00 that day)', wp_date( 'Y-m-d H:i', strtotime( '2026-11-12 12:00:00 UTC' ), new DateTimeZone( 'America/Los_Angeles' ) ) === '2026-11-12 04:00' );
check( 'stub: wp_date applies Pacific/Auckland (12:00 UTC on 12 Nov is 01:00 on 13 Nov)', wp_date( 'Y-m-d H:i', strtotime( '2026-11-12 12:00:00 UTC' ), new DateTimeZone( 'Pacific/Auckland' ) ) === '2026-11-13 01:00' );
check( 'stub: wp_date with no zone uses the site zone', wp_date( 'Y-m-d H:i', strtotime( '2026-11-12 12:00:00 UTC' ) ) === '2026-11-12 12:00' );

// Site zone east of UTC. The PHP default zone follows it, as a site could set it.
fb_arrange( $FP_ON );
$GLOBALS['SITE_TZ'] = 'Pacific/Auckland';
date_default_timezone_set( 'Pacific/Auckland' );
check( 'label: 12 Nov 2026 reads 12 Nov 2026 with the site zone Pacific/Auckland', vance_hub_flare_log_label( '2026-11-12' ) === '12 Nov 2026' );
check( 'label: 1 Jan 2027 reads 1 Jan 2027 with the site zone Pacific/Auckland', vance_hub_flare_log_label( '2027-01-01' ) === '1 Jan 2027' );
$GLOBALS['UMETA'][7]['_sla_clinical_profile'] = array( 'next_appointment' => '2026-12-03' );
$D23_NZ = vance_hub_flare_sheet_data( 7, $D23_PLAN, array(), $D23_NOW );
check( 'appointment: 3 Dec 2026 reads 3 Dec 2026 with the PHP default and site zone Pacific/Auckland', $D23_NZ['appointment'] === '3 Dec 2026' );

// Site zone west of UTC.
fb_arrange( $FP_ON );
$GLOBALS['SITE_TZ'] = 'America/Los_Angeles';
date_default_timezone_set( 'America/Los_Angeles' );
check( 'label: 12 Nov 2026 reads 12 Nov 2026 with the site zone America/Los_Angeles', vance_hub_flare_log_label( '2026-11-12' ) === '12 Nov 2026' );

// PHP default UTC with a west site zone. The appointment's own UTC argument is the only
// thing that keeps the day: without it, 00:00 UTC reads as the day before in Los Angeles.
fb_arrange( $FP_ON );
$GLOBALS['SITE_TZ'] = 'America/Los_Angeles';
date_default_timezone_set( 'UTC' );
$GLOBALS['UMETA'][7]['_sla_clinical_profile'] = array( 'next_appointment' => '2026-12-03' );
$D23_LA = vance_hub_flare_sheet_data( 7, $D23_PLAN, array(), $D23_NOW );
check( 'appointment: 3 Dec 2026 reads 3 Dec 2026 with PHP default UTC and site zone America/Los_Angeles', $D23_LA['appointment'] === '3 Dec 2026' );

$GLOBALS['SITE_TZ'] = 'UTC';
date_default_timezone_set( 'UTC' );

// Static: both calls pass UTC explicitly.
preg_match( '/function vance_hub_flare_log_label\(.*?\r?\n\}/s', $FP_SRC, $LABEL_FN );
preg_match( '/function vance_hub_flare_sheet_data\(.*?\r?\n\}/s', $FP_SRC, $SHEET_FN );
check( 'static: the flare log label passes new DateTimeZone( UTC ) to wp_date', strpos( $LABEL_FN[0] ?? '', "wp_date( 'j M Y', \$ts, new DateTimeZone( 'UTC' ) )" ) !== false );
check( 'static: the appointment date passes new DateTimeZone( UTC ) to wp_date', strpos( $SHEET_FN[0] ?? '', "wp_date( 'j M Y', \$ts, new DateTimeZone( 'UTC' ) )" ) !== false );
check( 'static: negative control, a wp_date call without the UTC zone is not matched', strpos( "return wp_date( 'j M Y', \$ts );", "wp_date( 'j M Y', \$ts, new DateTimeZone( 'UTC' ) )" ) === false );

/* ===================================================================== */
section( '24. render: consent needed or given, the log forms and the Edit my plan box' );

$GLOBALS['OPEN_GATES'] = false;

fb_arrange( $FP_ON );
$P24 = array( 'updated' => $OLD_TS, 'signs' => 'Tight belly', 'actions' => 'Fluids', 'contacts' => 'Nurse' );

// An open flare, so that the end form would render if the consent guard failed.
$D24_LOG_OPEN = array( 'open' => array( 'start' => '16 Nov 2026', 'end' => '', 'days' => 3, 'helped' => '' ), 'rows' => array( array( 'start' => '16 Nov 2026', 'end' => '', 'days' => 3, 'helped' => '' ) ), 'count' => 1 );
$H24_NEED = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $P24, 'updated' => '10 Nov 2026', 'need_consent' => true, 'consent' => 'I agree.', 'log' => $D24_LOG_OPEN ) ) );
check( 'consent needed: no flare start form is rendered', strpos( $H24_NEED, 'value="flare_start"' ) === false );
check( 'consent needed: no flare end form is rendered', strpos( $H24_NEED, 'value="flare_end"' ) === false );
check( 'consent needed: the card says to read the agreement again and open Edit my plan', strpos( $H24_NEED, 'read the agreement again and save your plan' ) !== false && strpos( $H24_NEED, 'Edit my plan' ) !== false );
check( 'consent needed: the Edit my plan box is rendered open', strpos( $H24_NEED, '<details class="vhd-flare__edit" open>' ) !== false );

// No flare open: a flare start form would render if the consent guard failed.
$H24_NEED_NOFLARE = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $P24, 'updated' => '10 Nov 2026', 'need_consent' => true, 'consent' => 'I agree.' ) ) );
check( 'consent needed, no flare open: no flare start form is rendered', strpos( $H24_NEED_NOFLARE, 'value="flare_start"' ) === false );

$H24_OK = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $P24, 'updated' => '10 Nov 2026', 'need_consent' => false ) ) );
check( 'consent given: the flare start form is rendered', strpos( $H24_OK, 'value="flare_start"' ) !== false );
$H24_OPEN = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $P24, 'updated' => '10 Nov 2026', 'need_consent' => false, 'log' => $D24_LOG_OPEN ) ) );
check( 'consent given with an open flare: the flare end form is rendered (positive control for the end-form check)', strpos( $H24_OPEN, 'value="flare_end"' ) !== false );
check( 'consent given: the Edit my plan box is rendered closed', strpos( $H24_OK, '<details class="vhd-flare__edit">' ) !== false );
check( 'consent given: the card does not tell the member to read the agreement again', strpos( $H24_OK, 'read the agreement again' ) === false );

$H24_END = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $P24, 'updated' => '10 Nov 2026', 'need_consent' => false, 'flash' => 'flare_ended' ) ) );
check( 'consent given, flare just ended: the Edit my plan box is rendered open', strpos( $H24_END, '<details class="vhd-flare__edit" open>' ) !== false );

/* ===================================================================== */
section( '25. delete order: the flare log goes before the plan, and a failed log delete keeps the plan' );

$D25_LOG = array( array( 'id' => 'd1', 'start' => '2026-11-16', 'end' => '', 'helped' => '' ) );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $D25_LOG;
fb_post_delete();
fb_handle();
check( 'delete order: the flare log is deleted before the plan', fb_seq_index( 'write:_sla_flare_log' ) >= 0 && fb_seq_index( 'write:_sla_flare_log' ) < fb_seq_index( 'write:_sla_flare_plan' ) );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $D25_LOG;
$GLOBALS['DELETE_FAILS'] = '_sla_flare_log';
fb_post_delete();
check( 'delete log fails: the result is kept', fb_handle() === 'https://example.test/hub/?vance_flare=kept' );
check( 'delete log fails: the plan is still stored with its answers', ( fb_meta( '_sla_flare_plan' )['signs'] ?? '' ) === 'Tight belly' );
check( 'delete log fails: the flare log is still stored', fb_meta( '_sla_flare_log' ) === $D25_LOG );
check( 'delete log fails: the agreement is not withdrawn', fb_meta( '_sla_consent_flare_plan' ) === '1' && fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === null );

// The member can only retry from the card, so the card must still offer the delete form.
$_GET['vance_flare'] = 'kept';
$H25_CARD = vance_hub_flare_plan_html( vance_hub_flare_plan_data( 7 ) );
check( 'delete log fails: the card still offers the delete form, so the member can try again', strpos( $H25_CARD, 'name="vance_hub_flare_do" value="delete"' ) !== false );

$GLOBALS['DELETE_FAILS'] = '';
fb_post_delete();
check( 'delete log retry: the retry gives deleted', fb_handle() === 'https://example.test/hub/?vance_flare=deleted' );
check( 'delete log retry: the plan, the flare log and the agreement flag are gone', fb_meta( '_sla_flare_plan' ) === null && fb_meta( '_sla_flare_log' ) === null && fb_meta( '_sla_consent_flare_plan' ) === null );
check( 'delete log retry: the withdrawal time is written', fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === '2026-11-18 12:00:00' );

/* ===================================================================== */
section( '26. static: the step builder moves focus after Back and Next' );

check( 'focus: a step change moves focus to the step label', preg_match( '/if \( moved \) \{[^}]*label\.focus\(\);/', $JS ) === 1 );
check( 'focus: the step label can take focus with tabIndex -1', strpos( $JS, 'label.tabIndex = -1;' ) !== false );
check( 'focus: negative control, a label focus call after the moved branch is not matched', preg_match( '/if \( moved \) \{[^}]*label\.focus\(\);/', "if ( moved ) {\n\tvar x = 1;\n}\nlabel.focus();" ) === 0 );

/* ===================================================================== */
section( '27. no-cache: the negative cases and the hook (the positive case is section 28)' );

// Not in the shared stubs. Without is_page() the function returns early, and the
// "another page" case would pass for the wrong reason. The counter shows whether
// the headers were sent.
function is_page( $page = '' ) { return '' !== $page && $page === $GLOBALS['PAGE_SLUG']; }
function nocache_headers() { $GLOBALS['NOCACHE_CALLS']++; }

fb_arrange( $FP_ON, false );
$GLOBALS['PAGE_SLUG']     = 'dashboard';
$GLOBALS['NOCACHE_CALLS'] = 0;
vance_hub_flare_plan_no_cache();
check( 'no-cache, logged out: DONOTCACHEPAGE is not defined', ! defined( 'DONOTCACHEPAGE' ) );
check( 'no-cache, logged out: no no-cache headers are sent', 0 === $GLOBALS['NOCACHE_CALLS'] );

fb_arrange( $FP_ON, true );
$GLOBALS['PAGE_SLUG']     = 'hub';
$GLOBALS['NOCACHE_CALLS'] = 0;
vance_hub_flare_plan_no_cache();
check( 'no-cache, another page: DONOTCACHEPAGE is not defined', ! defined( 'DONOTCACHEPAGE' ) );
check( 'no-cache, another page: no no-cache headers are sent', 0 === $GLOBALS['NOCACHE_CALLS'] );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_flare_plan' => true, 'vance_hub_flare_plan_urgent' => '' ), true );
$GLOBALS['PAGE_SLUG']     = 'dashboard';
$GLOBALS['NOCACHE_CALLS'] = 0;
vance_hub_flare_plan_no_cache();
// CHANGED (spec F): the read-only card shows the plan when the card is not available, so the
// no-cache flag no longer depends on available(). This is the first positive case: it defines
// DONOTCACHEPAGE, so no negative check may come after it.
check( 'no-cache, plan not available (no urgent text), logged in, dashboard: the headers are sent once', 1 === $GLOBALS['NOCACHE_CALLS'] );
check( 'no-cache, plan not available (no urgent text): DONOTCACHEPAGE is defined as true', defined( 'DONOTCACHEPAGE' ) && true === constant( 'DONOTCACHEPAGE' ) );

fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_flare_plan' => false, 'vance_hub_flare_plan_urgent' => 'Call the clinic.' ), true );
$GLOBALS['PAGE_SLUG']     = 'dashboard';
$GLOBALS['NOCACHE_CALLS'] = 0;
vance_hub_flare_plan_no_cache();
check( 'no-cache, plan not available (switch off), logged in, dashboard: the headers are sent once', 1 === $GLOBALS['NOCACHE_CALLS'] );

// Static: the hook. Another priority would run it after other template_redirect callbacks.
check( 'hook: the no-cache check is added on template_redirect at priority 1', strpos( $FP_SRC, "add_action( 'template_redirect', 'vance_hub_flare_plan_no_cache', 1 );" ) !== false );
check( 'hook: negative control, priority 11 is not matched by the same text', strpos( "add_action( 'template_redirect', 'vance_hub_flare_plan_no_cache', 11 );", "add_action( 'template_redirect', 'vance_hub_flare_plan_no_cache', 1 );" ) === false );

/* ===================================================================== */
section( '28. no-cache: the positive case, LAST in the file because it defines DONOTCACHEPAGE' );

// A constant cannot be undefined in this process. Nothing after this section may
// depend on DONOTCACHEPAGE being absent: sections 27 and earlier have run.
fb_arrange( $FP_ON, true );
$GLOBALS['PAGE_SLUG']     = 'dashboard';
$GLOBALS['NOCACHE_CALLS'] = 0;
vance_hub_flare_plan_no_cache();
check( 'no-cache, dashboard, logged in, card on: DONOTCACHEPAGE is defined as true', defined( 'DONOTCACHEPAGE' ) && true === constant( 'DONOTCACHEPAGE' ) );
check( 'no-cache, dashboard, logged in, card on: the no-cache headers are sent once', 1 === $GLOBALS['NOCACHE_CALLS'] );

/* ===================================================================== */
// Sections 29 to 38 cover the Phase 1 hardening. They do not depend on DONOTCACHEPAGE.

/** User 7 with a plan, the agreement, an open flare and a kept draft. Call after fb_arrange(). */
function fb_seed_all() {
	fb_stored_plan();
	fb_consented();
	$GLOBALS['UMETA'][7]['_sla_flare_log']        = array( array( 'id' => 'o', 'start' => '2026-11-16', 'end' => '', 'helped' => '' ) );
	$GLOBALS['TRANSIENTS']['vance_flare_draft_7'] = array( 'do' => 'save', 'answers' => array( 'signs' => 'typed', 'actions' => '', 'contacts' => '' ), 'helped' => '' );
}

/** A remove-one-entry request with the right nonce. */
function fb_post_remove( $row ) {
	$_POST = array(
		'vance_hub_flare_nonce' => 'nonce-vance_hub_flare_plan',
		'vance_hub_flare_do'    => 'flare_remove',
		'vhd_flare_row'         => $row,
	);
}

/** How many times a write event is in WRITES. */
function fb_write_count( $event ) {
	return count( array_keys( $GLOBALS['WRITES'], $event, true ) );
}

$LOG_TWO = array(
	array( 'id' => 'o', 'start' => '2026-11-16', 'end' => '', 'helped' => '' ),
	array( 'id' => 'a1', 'start' => '2026-11-01', 'end' => '2026-11-03', 'helped' => 'Rest' ),
);

/* ===================================================================== */
section( '29. one delete function, one order: delete_all(), the handler codes, Manage my data' );

fb_arrange( $FP_ON );
fb_seed_all();
$R29 = vance_hub_flare_plan_delete_all( 7 );
check( 'delete_all: with all three parts stored, every flag and ok are true', $R29 === array( 'log' => true, 'plan' => true, 'consent' => true, 'ok' => true ) );
check( 'delete_all: the log, the plan, the agreement flag are gone, the withdrawal time is written', fb_meta( '_sla_flare_log' ) === null && fb_meta( '_sla_flare_plan' ) === null && fb_meta( '_sla_consent_flare_plan' ) === null && fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === '2026-11-18 12:00:00' );
check( 'delete_all: the old agreement time and wording stay as a record', fb_meta( '_sla_consent_flare_plan_at' ) === '2026-10-01 09:00:00' && fb_meta( '_sla_consent_flare_plan_text' ) === vance_hub_flare_plan_consent_text() );
check( 'delete_all: the draft is deleted', ! isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) );
$I29 = array( fb_seq_index( 'write:_sla_flare_log' ), fb_seq_index( 'write:_sla_flare_plan' ), fb_seq_index( 'write:_sla_consent_flare_plan' ), fb_seq_index( 'write:_sla_consent_flare_plan_withdrawn_at' ) );
check( 'delete_all: the order is log, plan, agreement flag, withdrawal time', $I29[0] >= 0 && $I29[0] < $I29[1] && $I29[1] < $I29[2] && $I29[2] < $I29[3] );

fb_arrange( $FP_ON );
fb_seed_all();
$GLOBALS['DELETE_FAILS'] = '_sla_flare_log';
$R29 = vance_hub_flare_plan_delete_all( 7 );
check( 'delete_all: a log that stays gives false for log, plan, consent and ok', $R29 === array( 'log' => false, 'plan' => false, 'consent' => false, 'ok' => false ) );
check( 'delete_all: it stops at the log: the plan and the agreement are not touched', fb_meta( '_sla_flare_plan' ) !== null && fb_meta( '_sla_consent_flare_plan' ) === '1' && ! in_array( 'delete_user_meta:_sla_flare_plan', $GLOBALS['WRITES'], true ) && ! in_array( 'delete_user_meta:_sla_consent_flare_plan', $GLOBALS['WRITES'], true ) );
check( 'delete_all: the draft is deleted even when a part stays', ! isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) );

fb_arrange( $FP_ON );
fb_seed_all();
$GLOBALS['DELETE_FAILS'] = '_sla_flare_plan';
$R29 = vance_hub_flare_plan_delete_all( 7 );
check( 'delete_all: a plan that stays: the log is gone, the plan and the agreement are not', $R29 === array( 'log' => true, 'plan' => false, 'consent' => false, 'ok' => false ) && fb_meta( '_sla_flare_log' ) === null && fb_meta( '_sla_consent_flare_plan' ) === '1' );

fb_arrange( $FP_ON );
fb_seed_all();
$GLOBALS['DELETE_FAILS'] = '_sla_consent_flare_plan';
$R29 = vance_hub_flare_plan_delete_all( 7 );
check( 'delete_all: an agreement flag that stays: log and plan gone, consent false, ok false', $R29 === array( 'log' => true, 'plan' => true, 'consent' => false, 'ok' => false ) && fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === null );

fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = array( array( 'id' => 'o', 'start' => '2026-11-16', 'end' => '', 'helped' => '' ) );
$GLOBALS['DELETE_FAILS'] = '_sla_flare_log';
$R29 = vance_hub_flare_plan_delete_all( 7 );
check( 'delete_all: a part the run did not reach, and that was never stored, reads as absent', $R29 === array( 'log' => false, 'plan' => true, 'consent' => true, 'ok' => false ) );

fb_arrange( $FP_ON );
check( 'delete_all: user 0 gives all false and writes nothing', vance_hub_flare_plan_delete_all( 0 ) === array( 'log' => false, 'plan' => false, 'consent' => false, 'ok' => false ) && $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
check( 'delete_all: with nothing stored, ok is true and no withdrawal time is written', vance_hub_flare_plan_delete_all( 7 )['ok'] === true && fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === null );

fb_arrange( $FP_ON );
fb_seed_all();
$GLOBALS['UPDATE_FAILS_ONCE'] = '_sla_consent_flare_plan_withdrawn_at';
vance_hub_flare_plan_delete_all( 7 );
check( 'withdrawal time: one failed write is retried once, and the time is stored', fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === '2026-11-18 12:00:00' && 2 === fb_write_count( 'update_user_meta:_sla_consent_flare_plan_withdrawn_at' ) );
fb_arrange( $FP_ON );
fb_seed_all();
$GLOBALS['UPDATE_FAILS'] = '_sla_consent_flare_plan_withdrawn_at';
$R29 = vance_hub_flare_plan_delete_all( 7 );
check( 'withdrawal time: two failed writes stop at two tries, and the data is still reported deleted', 2 === fb_write_count( 'update_user_meta:_sla_consent_flare_plan_withdrawn_at' ) && $R29['ok'] === true );

fb_arrange( $FP_ON );
fb_seed_all();
fb_post_delete();
check( 'handler delete: all parts gone gives deleted', fb_handle() === 'https://example.test/hub/?vance_flare=deleted' && fb_meta( '_sla_flare_log' ) === null && fb_meta( '_sla_flare_plan' ) === null );
fb_arrange( $FP_ON );
fb_seed_all();
$GLOBALS['DELETE_FAILS'] = '_sla_flare_plan';
fb_post_delete();
check( 'handler delete: the log goes and the plan stays gives partial', fb_handle() === 'https://example.test/hub/?vance_flare=partial' && fb_meta( '_sla_flare_log' ) === null && fb_meta( '_sla_flare_plan' ) !== null );
fb_arrange( $FP_ON );
fb_seed_all();
$GLOBALS['DELETE_FAILS'] = '_sla_flare_log';
fb_post_delete();
check( 'handler delete: nothing deleted (the log stays first) gives kept', fb_handle() === 'https://example.test/hub/?vance_flare=kept' && fb_meta( '_sla_flare_plan' ) !== null );
fb_arrange( $FP_ON );
fb_stored_plan();
$GLOBALS['DELETE_FAILS'] = '_sla_flare_plan';
fb_post_delete();
check( 'handler delete: only a plan stored and it stays gives kept, not partial', fb_handle() === 'https://example.test/hub/?vance_flare=kept' );
fb_arrange( $FP_ON );
fb_seed_all();
$GLOBALS['LOCK_RESULT'] = '0';
fb_post_delete();
check( 'handler delete: no lock gives kept, deletes nothing, and still deletes the draft', fb_handle() === 'https://example.test/hub/?vance_flare=kept' && fb_meta( '_sla_flare_plan' ) !== null && fb_meta( '_sla_flare_log' ) !== null && ! isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) );
foreach ( array( 'partial' => 'Part of your data is deleted', 'kept' => 'Not completed' ) as $code29 => $text29 ) {
	$h29 = vance_hub_flare_plan_html( fb_flare_view( array( 'flash' => $code29 ) ) );
	check( "message: $code29 is a warning with its own text", false !== strpos( $h29, $text29 ) && false !== strpos( $h29, 'vhd-flash--warn' ) );
}
check( 'message: partial says that part of the data was deleted and asks to try again', false !== strpos( vance_hub_flare_plan_html( fb_flare_view( array( 'flash' => 'partial' ) ) ), 'Part of your data is deleted. Part of it is still stored. Try again to delete the rest.' ) );

// Manage my data: the flare group uses the same function.
fb_arrange( $FP_ON );
fb_seed_all();
check( 'manage data: the flare group delete reports true', vance_hub_data_erase( 7, 'flare' ) === true );
check( 'manage data: it deleted the log, the plan and the agreement flag, and withdrew the agreement', fb_meta( '_sla_flare_log' ) === null && fb_meta( '_sla_flare_plan' ) === null && fb_meta( '_sla_consent_flare_plan' ) === null && fb_meta( '_sla_consent_flare_plan_withdrawn_at' ) === '2026-11-18 12:00:00' );
check( 'manage data: it used the plan order (log, plan, agreement) and deleted the draft', fb_seq_index( 'write:_sla_flare_log' ) < fb_seq_index( 'write:_sla_flare_plan' ) && fb_seq_index( 'write:_sla_flare_plan' ) < fb_seq_index( 'write:_sla_consent_flare_plan' ) && ! isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) );
fb_arrange( $FP_ON );
fb_seed_all();
$GLOBALS['DELETE_FAILS'] = '_sla_flare_plan';
check( 'manage data: a plan that stays makes the group delete report false', vance_hub_data_erase( 7, 'flare' ) === false && fb_meta( '_sla_flare_plan' ) !== null && fb_meta( '_sla_consent_flare_plan' ) === '1' );
$DM_SRC = file_get_contents( $THEME . '/inc/hub-data-manage.php' );
preg_match( '/function vance_hub_data_handle_post\(\) \{.*?\r?\n\}\r?\n/s', $DM_SRC, $DM_HANDLER );
$DM_H = $DM_HANDLER[0] ?? '';
check( 'manage data: the member lock is taken before the group delete runs and freed after it', '' !== $DM_H && strpos( $DM_H, 'vance_hub_checkin_lock' ) < strpos( $DM_H, 'vance_hub_data_erase' ) && strpos( $DM_H, 'vance_hub_data_erase' ) < strpos( $DM_H, 'vance_hub_checkin_unlock' ) );
check( 'manage data: negative control, an unlock that comes first is not matched by the same order test', ! ( strpos( "unlock lock erase", 'lock' ) < strpos( "unlock lock erase", 'erase' ) && strpos( "unlock lock erase", 'unlock' ) > strpos( "unlock lock erase", 'erase' ) ) );

/* ===================================================================== */
section( '30. result message: the fragment, the card id, the alert and status roles' );

fb_arrange( $FP_ON );
fb_post_save();
fb_handle();
check( 'fragment: a saved redirect ends in #vhd-flare', $GLOBALS['REDIRECT_RAW'] === 'https://example.test/hub/?vance_flare=saved#vhd-flare' );
fb_arrange( $FP_ON );
fb_post_save();
$_POST['vance_hub_flare_nonce'] = 'forged';
fb_handle();
check( 'fragment: an expired redirect ends in #vhd-flare', $GLOBALS['REDIRECT_RAW'] === 'https://example.test/hub/?vance_flare=expired#vhd-flare' );
fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => '', 'actions' => '', 'contacts' => '' ) );
fb_handle();
check( 'fragment: an invalid redirect ends in #vhd-flare', $GLOBALS['REDIRECT_RAW'] === 'https://example.test/hub/?vance_flare=invalid#vhd-flare' );
fb_arrange( $FP_ON );
fb_post_flare( 'flare_stop' );
fb_handle();
check( 'fragment: an unknown action redirect ends in #vhd-flare', $GLOBALS['REDIRECT_RAW'] === 'https://example.test/hub/?vance_flare=invalid#vhd-flare' );
fb_arrange( $FP_ON );
fb_seed_all();
$GLOBALS['LOCK_RESULT'] = '0';
fb_post_delete();
fb_handle();
check( 'fragment: a kept redirect ends in #vhd-flare', $GLOBALS['REDIRECT_RAW'] === 'https://example.test/hub/?vance_flare=kept#vhd-flare' );

$H30 = vance_hub_flare_plan_html( fb_flare_view( array( 'flash' => 'saved' ) ) );
check( 'card: the card root is the section with id vhd-flare', 0 === strpos( $H30, '<section id="vhd-flare" class="vhd-card vhd-flare"' ) );
check( 'card: id vhd-flare appears once', 1 === substr_count( $H30, 'id="vhd-flare"' ) );
$ROLE_OK = true;
foreach ( vance_hub_flare_plan_success_codes() as $code30 ) {
	$h30 = vance_hub_flare_plan_html( fb_flare_view( array( 'flash' => $code30 ) ) );
	if ( false === strpos( $h30, 'id="vhd-flare-flash" tabindex="-1" role="status">' ) || false !== strpos( $h30, 'vhd-flash--warn' ) || false !== strpos( $h30, 'role="alert"' ) ) {
		$ROLE_OK = false;
	}
}
check( 'roles: every success code has a message with role status, tabindex -1 and id vhd-flare-flash, and no warning class', $ROLE_OK && count( vance_hub_flare_plan_success_codes() ) === 6 );
$ROLE_OK = true;
foreach ( vance_hub_flare_plan_error_codes() as $code30 ) {
	$h30 = vance_hub_flare_plan_html( fb_flare_view( array( 'flash' => $code30 ) ) );
	if ( false === strpos( $h30, 'id="vhd-flare-flash" tabindex="-1" role="alert">' ) || false === strpos( $h30, 'vhd-flash--warn' ) || false !== strpos( $h30, 'role="status"' ) ) {
		$ROLE_OK = false;
	}
}
check( 'roles: every error code has a message with role alert, tabindex -1 and id vhd-flare-flash, and the warning class', $ROLE_OK && count( vance_hub_flare_plan_error_codes() ) === 11 );
check( 'roles: negative control, an unknown code has no flash element', false === strpos( vance_hub_flare_plan_html( fb_flare_view( array( 'flash' => 'nonsense' ) ) ), 'vhd-flare-flash' ) );
check( 'message: the toolong text', false !== strpos( vance_hub_flare_plan_html( fb_flare_view( array( 'flash' => 'toolong' ) ) ), 'Not saved. A text is too long. Make it shorter, then save again.' ) );
check( 'message: the flare_removed text', false !== strpos( vance_hub_flare_plan_html( fb_flare_view( array( 'flash' => 'flare_removed' ) ) ), 'The entry is removed.' ) );
check( 'message: the trimmed text names the number from the function', false !== strpos( vance_hub_flare_plan_html( fb_flare_view( array( 'flash' => 'flare_started_trimmed' ) ) ), 'Your flare is recorded. The log keeps 50 entries, so the oldest entry was removed.' ) );

/* ===================================================================== */
section( '31. gates: can(), delete with the switch off, the read-only card, the eraser lock, the exporter' );

fb_arrange( $FP_ON );
check( 'can: view, save and log follow available() when it is true', vance_hub_flare_plan_can( 'view' ) && vance_hub_flare_plan_can( 'save' ) && vance_hub_flare_plan_can( 'log' ) );
check( 'can: delete is true for a logged-in member', vance_hub_flare_plan_can( 'delete' ) === true );
check( 'can: an unknown value is false', vance_hub_flare_plan_can( 'rename' ) === false && vance_hub_flare_plan_can( '' ) === false );
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_flare_plan_urgent' => 'Call the clinic.' ) );
check( 'can: the switch off closes view, save and log, and leaves delete open', ! vance_hub_flare_plan_can( 'view' ) && ! vance_hub_flare_plan_can( 'save' ) && ! vance_hub_flare_plan_can( 'log' ) && vance_hub_flare_plan_can( 'delete' ) );
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_flare_plan' => true, 'vance_hub_flare_plan_urgent' => '' ) );
check( 'can: an empty urgent text closes view, save and log, and leaves delete open', ! vance_hub_flare_plan_can( 'view' ) && ! vance_hub_flare_plan_can( 'save' ) && ! vance_hub_flare_plan_can( 'log' ) && vance_hub_flare_plan_can( 'delete' ) );
fb_arrange( $FP_ON, false );
check( 'can: delete needs a logged-in member', vance_hub_flare_plan_can( 'delete' ) === false );

// With the switch off: delete and flare_remove work, everything else is refused with failed.
$OFF = array( 'vance_home_layout' => 'hub', 'vance_hub_dash_flare_plan' => false, 'vance_hub_flare_plan_urgent' => 'Call the clinic.' );
fb_arrange( $OFF );
fb_seed_all();
fb_post_remove( 'o' );
check( 'switch off: flare_remove still works', fb_handle() === 'https://example.test/hub/?vance_flare=flare_removed' && fb_meta( '_sla_flare_log' ) === null );
fb_arrange( $OFF );
fb_seed_all();
fb_post_delete();
check( 'switch off: delete still works', fb_handle() === 'https://example.test/hub/?vance_flare=deleted' && fb_meta( '_sla_flare_plan' ) === null );
foreach ( array( 'save', 'flare_start', 'flare_end' ) as $do31 ) {
	fb_arrange( $OFF );
	fb_seed_all();
	unset( $GLOBALS['UMETA'][7]['_sla_flare_log'] );
	if ( 'save' === $do31 ) {
		fb_post_save();
	} else {
		fb_post_flare( $do31 );
	}
	check( "switch off: $do31 redirects with failed, writes nothing, takes no lock", fb_handle() === 'https://example.test/hub/?vance_flare=failed' && $GLOBALS['WRITES'] === array() && $GLOBALS['LOCK_SQL'] === array() );
}
fb_arrange( $OFF );
fb_post_flare( 'flare_stop' );
check( 'switch off: an unknown action still gives invalid', fb_handle() === 'https://example.test/hub/?vance_flare=invalid' );
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_flare_plan' => true, 'vance_hub_flare_plan_urgent' => '' ) );
fb_seed_all();
fb_post_delete();
check( 'urgent text empty: delete still works', fb_handle() === 'https://example.test/hub/?vance_flare=deleted' && fb_meta( '_sla_flare_plan' ) === null );

// The read-only card.
fb_arrange( $OFF );
fb_stored_plan();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_TWO;
$RO = vance_hub_flare_plan_data( 7 );
check( 'read-only data: the switch off with a stored plan gives the data with readonly true', is_array( $RO ) && $RO['readonly'] === true && ( $RO['plan']['signs'] ?? null ) === 'Tight belly' );
check( 'read-only data: progress, quick, sheet and draft are null, need_consent is false', is_array( $RO ) && $RO['progress'] === null && $RO['quick'] === null && $RO['sheet'] === null && $RO['draft'] === null && $RO['need_consent'] === false );
check( 'read-only data: it carries the date, the urgent text, the log view, the nonce and the flash', is_array( $RO ) && $RO['updated'] === '10 Nov 2026' && $RO['urgent'] === 'Call the clinic.' && ( $RO['log']['count'] ?? null ) === 2 && $RO['nonce'] === 'nonce-vance_hub_flare_plan' && $RO['flash'] === '' );
check( 'read-only data: it has the same keys as the full view', is_array( $RO ) && array_keys( $RO ) === array( 'plan', 'progress', 'log', 'quick', 'sheet', 'updated', 'urgent', 'need_consent', 'consent', 'note', 'nonce', 'flash', 'draft', 'readonly' ) );
fb_arrange( $OFF );
check( 'read-only data: the switch off with no stored plan gives null', vance_hub_flare_plan_data( 7 ) === null );
fb_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_flare_plan' => true, 'vance_hub_flare_plan_urgent' => '' ) );
fb_stored_plan();
$RO2 = vance_hub_flare_plan_data( 7 );
check( 'read-only data: an empty urgent text with a stored plan gives readonly true and urgent empty', is_array( $RO2 ) && $RO2['readonly'] === true && $RO2['urgent'] === '' );
fb_arrange( $FP_ON );
fb_stored_plan();
check( 'read-only data: with the card available readonly is false', ( vance_hub_flare_plan_data( 7 )['readonly'] ?? null ) === false );

$LOG_VIEW_RO = vance_hub_flare_log_view( $LOG_TWO, $LOG_NOW );
$H_RO = vance_hub_flare_plan_html( fb_flare_view( array( 'readonly' => true, 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'urgent' => '', 'log' => $LOG_VIEW_RO ) ) );
check( 'read-only html: it shows the line that editing is turned off', false !== strpos( $H_RO, 'Editing is turned off. You can still print or delete your plan.' ) );
check( 'read-only html: it shows the plan answers, the print button and the delete control', false !== strpos( $H_RO, '<p>Tight belly</p>' ) && false !== strpos( $H_RO, 'data-vhd-flare-print="plan" hidden>' ) && false !== strpos( $H_RO, 'name="vance_hub_flare_do" value="delete"' ) );
check( 'read-only html: no form, no builder, no wizard, no save button, no edit box', false === strpos( $H_RO, 'name="vhd_flare[' ) && false === strpos( $H_RO, 'data-vhd-wizard' ) && false === strpos( $H_RO, 'Save my plan' ) && false === strpos( $H_RO, 'vhd-flare__edit' ) );
check( 'read-only html: no quick check, no progress bar, no appointment sheet', false === strpos( $H_RO, 'data-vhd-flare-quick' ) && false === strpos( $H_RO, 'role="progressbar"' ) && false === strpos( $H_RO, 'vhd-flare__sheetbox' ) && false === strpos( $H_RO, 'Appointment sheet' ) );
check( 'read-only html: the log history shows with no start or end control', false !== strpos( $H_RO, 'vhd-flare__history' ) && false === strpos( $H_RO, 'value="flare_start"' ) && false === strpos( $H_RO, 'value="flare_end"' ) && false === strpos( $H_RO, 'vhd_flare_helped' ) );
check( 'read-only html: an empty urgent text shows no urgent box', false === strpos( $H_RO, 'vhd-flare__urgent' ) && false === strpos( $H_RO, 'When to get urgent help' ) );
$H_RO2 = vance_hub_flare_plan_html( fb_flare_view( array( 'readonly' => true, 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'urgent' => 'Call the clinic.' ) ) );
check( 'read-only html: a set urgent text shows the urgent box', false !== strpos( $H_RO2, 'vhd-flare__urgent' ) && false !== strpos( $H_RO2, 'Call the clinic.' ) );
check( 'read-only html: negative control, the normal card has the forms the read-only card lacks', false !== strpos( vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026' ) ) ), 'name="vhd_flare[' ) );

// The eraser: lock, draft.
fb_arrange( $FP_ON );
$GLOBALS['USERS_BY_EMAIL']['ruth@example.test'] = (object) array( 'ID' => 7 );
fb_seed_all();
$GLOBALS['LOCK_RESULT'] = '0';
$ERA31 = vance_hub_flare_plan_erase( 'ruth@example.test' );
check( 'eraser lock: no lock gives items_retained, one message and nothing removed', $ERA31['items_retained'] === true && $ERA31['items_removed'] === false && count( $ERA31['messages'] ) === 1 && $ERA31['done'] === true );
check( 'eraser lock: no lock deletes nothing and releases no lock', $GLOBALS['WRITES'] === array() && fb_meta( '_sla_flare_plan' ) !== null && $GLOBALS['UNLOCK_SQL'] === array() );
fb_arrange( $FP_ON );
$GLOBALS['USERS_BY_EMAIL']['ruth@example.test'] = (object) array( 'ID' => 7 );
fb_seed_all();
$ERA31 = vance_hub_flare_plan_erase( 'ruth@example.test' );
check( 'eraser lock: with the lock it takes one lock, deletes, and releases it last', $ERA31['items_removed'] === true && $ERA31['items_retained'] === false && fb_seq_locked_once() && fb_seq_index( 'lock' ) === 0 );
check( 'eraser lock: the lock is taken before the first read of the member data', fb_seq_index( 'lock' ) < fb_seq_index( 'read:_sla_consent_flare_plan' ) );
check( 'eraser: the draft is deleted with the data', ! isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) && fb_meta( '_sla_flare_log' ) === null );

// The exporter: no "Withdrawn on" while the agreement is in force.
fb_arrange( $FP_ON );
$GLOBALS['USERS_BY_EMAIL']['ruth@example.test'] = (object) array( 'ID' => 7 );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_consent_flare_plan_withdrawn_at'] = '2026-10-15 08:00:00';
$EXP31 = vance_hub_flare_plan_export( 'ruth@example.test' );
$NAMES31 = array_column( $EXP31['data'][0]['data'] ?? array(), 'name' );
check( 'export: an old withdrawal time is not shown while the agreement flag is set', ! in_array( 'Withdrawn on', $NAMES31, true ) && in_array( 'Agreed on', $NAMES31, true ) );
fb_arrange( $FP_ON );
$GLOBALS['USERS_BY_EMAIL']['ruth@example.test'] = (object) array( 'ID' => 7 );
fb_stored_plan();
$GLOBALS['UMETA'][7]['_sla_consent_flare_plan_at']           = '2026-10-01 09:00:00';
$GLOBALS['UMETA'][7]['_sla_consent_flare_plan_withdrawn_at'] = '2026-10-15 08:00:00';
$NAMES31 = array_column( vance_hub_flare_plan_export( 'ruth@example.test' )['data'][0]['data'] ?? array(), 'name' );
check( 'export: the withdrawal time is shown when the flag is not set (positive control)', in_array( 'Withdrawn on', $NAMES31, true ) );
check( 'customizer text: the urgent text description has the new sentence and not the old claim', false !== strpos( $FP_SRC, 'Saved plans are not erased: a member who has a saved plan can still see, print and delete it.' ) && false === stripos( $FP_SRC, 'members cannot delete a plan while it is hidden' ) );

/* ===================================================================== */
section( '32. the text cleaner: what the member types is stored as typed' );

$TYPED = "a < b > c & d %41 C:\\\\Clinic <b>x</b>";
fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => $TYPED, 'actions' => 'x', 'contacts' => 'y' ) );
check( 'round trip: a save of <, >, &, %41, a backslash and a tag redirects with saved', fb_handle() === 'https://example.test/hub/?vance_flare=saved' );
check( 'round trip: the stored answer equals what was typed (one backslash)', ( fb_meta( '_sla_flare_plan' )['signs'] ?? null ) === 'a < b > c & d %41 C:\\Clinic <b>x</b>' );
$H32 = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => vance_hub_flare_plan_get( 7 ), 'updated' => '18 Nov 2026' ) ) );
check( 'round trip: shown on screen the text is escaped and the percent code is unchanged', false !== strpos( $H32, 'a &lt; b &gt; c &amp; d %41 C:\\Clinic &lt;b&gt;x&lt;/b&gt;' ) && false === strpos( $H32, '<b>x</b>' ) );
check( 'round trip: in the edit box the text is escaped for a textarea', false !== strpos( $H32, '>a &lt; b &gt; c &amp; d %41 C:\\Clinic &lt;b&gt;x&lt;/b&gt;</textarea>' ) );
fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
fb_post_flare( 'flare_end', 'x < y & z %41 C:\\\\Clinic' );
fb_handle();
check( 'round trip: what helped keeps <, &, %41 and a backslash', ( fb_meta( '_sla_flare_log' )[0]['helped'] ?? null ) === 'x < y & z %41 C:\\Clinic' );
fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => "a\x00b\x07c", 'actions' => '', 'contacts' => '' ) );
fb_handle();
check( 'round trip: control characters in a save are removed', ( fb_meta( '_sla_flare_plan' )['signs'] ?? null ) === 'abc' );
check( 'sanitise: running it twice changes nothing (it removes no backslash)', vance_hub_flare_plan_sanitise( vance_hub_flare_plan_sanitise( array( 'signs' => 'a\\b <x> &' ) ) ) === array( 'signs' => 'a\\b <x> &', 'actions' => '', 'contacts' => '' ) );
$FP_SRC = file_get_contents( $THEME . '/inc/hub-flare-plan.php' );
check( 'static: the plan sanitiser and the log text no longer call sanitize_textarea_field', false === strpos( preg_replace( '/\/\*.*?\*\//s', '', $FP_SRC ), 'sanitize_textarea_field( $' ) );

/* ===================================================================== */
section( '33. over-length text is refused, not cut' );

fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => str_repeat( 'a', 1001 ), 'actions' => 'x', 'contacts' => 'y' ) );
check( 'toolong: 1001 characters redirect with toolong', fb_handle() === 'https://example.test/hub/?vance_flare=toolong' );
check( 'toolong: no plan, no agreement key and no write at all', fb_meta( '_sla_flare_plan' ) === null && fb_meta( '_sla_consent_flare_plan' ) === null && $GLOBALS['WRITES'] === array() );
fb_arrange( $FP_ON );
fb_consented();
fb_stored_plan();
fb_post_save( array( 'signs' => str_repeat( 'é', 1001 ), 'actions' => '', 'contacts' => '' ), false );
$BEFORE33 = fb_meta( '_sla_flare_plan' );
check( 'toolong: an edit of 1001 accented characters is refused and the old plan stays', fb_handle() === 'https://example.test/hub/?vance_flare=toolong' && fb_meta( '_sla_flare_plan' ) === $BEFORE33 );
fb_arrange( $FP_ON );
check( 'toolong: write() refuses a 1001-character answer and writes nothing', vance_hub_flare_plan_write( 7, array( 'signs' => str_repeat( 'a', 1001 ) ), $OLD_TS ) === false && $GLOBALS['WRITES'] === array() );
fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
fb_post_flare( 'flare_end', str_repeat( 'h', 501 ) );
check( 'toolong: an end with 501 characters redirects with toolong', fb_handle() === 'https://example.test/hub/?vance_flare=toolong' );
check( 'toolong: the flare stays open and nothing is written', ( fb_meta( '_sla_flare_log' )[0]['end'] ?? null ) === '' && $GLOBALS['WRITES'] === array() );
check( 'toolong: the log read side keeps its cut (defensive)', strlen( vance_hub_flare_log_normalise( array( array( 'id' => 'a', 'start' => '2026-11-01', 'end' => '', 'helped' => str_repeat( 'h', 600 ) ) ) )[0]['helped'] ?? '' ) === 500 );

/* ===================================================================== */
section( '34. the draft: kept after a refused request, shown once' );

fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => 'Tight belly', 'actions' => '', 'contacts' => '' ), false );
fb_handle();
$DRAFT34 = $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ?? null;
check( 'draft: a save refused for consent keeps the typed text', ( $DRAFT34['do'] ?? null ) === 'save' && ( $DRAFT34['answers']['signs'] ?? null ) === 'Tight belly' && ( $DRAFT34['helped'] ?? null ) === '' );
check( 'draft: it lives 10 minutes (600 seconds)', ( $GLOBALS['SET_TRANSIENTS']['vance_flare_draft_7'] ?? null ) === 600 );
check( 'draft: the key is vance_flare_draft_<uid>', vance_hub_flare_plan_draft_key( 7 ) === 'vance_flare_draft_7' );
$_GET['vance_flare'] = 'consent';
$D34 = vance_hub_flare_plan_data( 7 );
check( 'draft: data() with an error flash gives the draft', ( $D34['draft']['answers']['signs'] ?? null ) === 'Tight belly' );
check( 'draft: it is gone after it was taken once', ! isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) && vance_hub_flare_plan_data( 7 )['draft'] === null );

fb_arrange( $FP_ON );
$GLOBALS['TRANSIENTS']['vance_flare_draft_7'] = array( 'do' => 'save', 'answers' => array( 'signs' => 'kept', 'actions' => '', 'contacts' => '' ), 'helped' => '' );
check( 'draft: with no flash data() gives null and does not take the draft', vance_hub_flare_plan_data( 7 )['draft'] === null && isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) );
$_GET['vance_flare'] = 'saved';
check( 'draft: with a success flash data() gives null and does not take the draft', vance_hub_flare_plan_data( 7 )['draft'] === null && isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) );
$_GET['vance_flare'] = 'bogus';
check( 'draft: with an unknown flash data() does not take the draft', vance_hub_flare_plan_data( 7 )['draft'] === null && isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) );
$_GET['vance_flare'] = 'failed';
check( 'draft: with a failed flash data() takes it', ( vance_hub_flare_plan_data( 7 )['draft']['answers']['signs'] ?? null ) === 'kept' );

// Review fix: a "what helped" draft is kept when the end form is not on the page
// (here: no plan and no open flare), so the text is not lost with nothing shown.
fb_arrange( $FP_ON );
$GLOBALS['TRANSIENTS']['vance_flare_draft_7'] = array( 'do' => 'flare_end', 'answers' => array( 'signs' => '', 'actions' => '', 'contacts' => '' ), 'helped' => 'Rest' );
$_GET['vance_flare'] = 'flare_none';
check( 'draft: control, flare_none is an error code', in_array( 'flare_none', vance_hub_flare_plan_error_codes(), true ) );
check( 'draft: an end draft with no end form on the page is not given and not deleted', vance_hub_flare_plan_data( 7 )['draft'] === null && ( $GLOBALS['TRANSIENTS']['vance_flare_draft_7']['helped'] ?? null ) === 'Rest' );
unset( $_GET['vance_flare'] );

fb_arrange( $FP_ON );
$GLOBALS['TRANSIENTS']['vance_flare_draft_7'] = array( 'do' => 'save', 'answers' => array( 'signs' => 'old', 'actions' => '', 'contacts' => '' ), 'helped' => '' );
fb_post_save();
fb_handle();
check( 'draft: a successful save deletes the draft', ! isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) );
fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
$GLOBALS['TRANSIENTS']['vance_flare_draft_7'] = array( 'do' => 'flare_end', 'answers' => array( 'signs' => '', 'actions' => '', 'contacts' => '' ), 'helped' => 'old' );
fb_post_flare( 'flare_end', 'Rest' );
fb_handle();
check( 'draft: a successful end deletes the draft', ! isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) );

fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => 'typed text', 'actions' => '', 'contacts' => '' ) );
$_POST['vance_hub_flare_nonce'] = 'forged';
// CHANGED after the security audit: the typed text is kept only for a request from this site.
$_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';
fb_handle();
check( 'draft: a bad nonce keeps the typed text for the logged-in member', ( $GLOBALS['TRANSIENTS']['vance_flare_draft_7']['answers']['signs'] ?? null ) === 'typed text' );

// A form on another site must not put text into the member's form.
$BAD_NONCE_DRAFT = function ( array $server ) use ( $FP_ON ) {
	fb_arrange( $FP_ON );
	fb_post_save( array( 'signs' => 'planted text', 'actions' => '', 'contacts' => 'Call 0906 000 0000' ) );
	$_POST['vance_hub_flare_nonce'] = 'forged';
	foreach ( $server as $name => $value ) {
		$_SERVER[ $name ] = $value;
	}
	$url = fb_handle();
	return array( isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ), $url );
};
$R = $BAD_NONCE_DRAFT( array( 'HTTP_SEC_FETCH_SITE' => 'cross-site' ) );
check( 'draft csrf: a cross-site post with a bad nonce stores no draft', false === $R[0] );
check( 'draft csrf: it still redirects with expired', false !== strpos( (string) $R[1], 'vance_flare=expired' ) );
check( 'draft csrf: same-site (another host of the domain) stores no draft', false === $BAD_NONCE_DRAFT( array( 'HTTP_SEC_FETCH_SITE' => 'same-site' ) )[0] );
check( 'draft csrf: Sec-Fetch-Site "none" stores no draft', false === $BAD_NONCE_DRAFT( array( 'HTTP_SEC_FETCH_SITE' => 'none' ) )[0] );
check( 'draft csrf: no header at all stores no draft', false === $BAD_NONCE_DRAFT( array() )[0] );
check( 'draft csrf: cross-site wins over a same-host Origin and Referer', false === $BAD_NONCE_DRAFT( array( 'HTTP_SEC_FETCH_SITE' => 'cross-site', 'HTTP_ORIGIN' => 'https://example.test', 'HTTP_REFERER' => 'https://example.test/hub/' ) )[0] );
check( 'draft csrf: with no Sec-Fetch-Site, an Origin of this site stores the draft', true === $BAD_NONCE_DRAFT( array( 'HTTP_ORIGIN' => 'https://example.test' ) )[0] );
check( 'draft csrf: an Origin of another host stores no draft', false === $BAD_NONCE_DRAFT( array( 'HTTP_ORIGIN' => 'https://evil.test' ) )[0] );
check( 'draft csrf: an Origin of another host wins over a Referer of this site', false === $BAD_NONCE_DRAFT( array( 'HTTP_ORIGIN' => 'https://evil.test', 'HTTP_REFERER' => 'https://example.test/hub/' ) )[0] );
check( 'draft csrf: Origin "null" stores no draft', false === $BAD_NONCE_DRAFT( array( 'HTTP_ORIGIN' => 'null' ) )[0] );
check( 'draft csrf: a host that only starts with this host stores no draft', false === $BAD_NONCE_DRAFT( array( 'HTTP_ORIGIN' => 'https://example.test.evil.test' ) )[0] );
check( 'draft csrf: this host over http, not https, stores no draft', false === $BAD_NONCE_DRAFT( array( 'HTTP_ORIGIN' => 'http://example.test' ) )[0] );
check( 'draft csrf: this host on another port stores no draft', false === $BAD_NONCE_DRAFT( array( 'HTTP_ORIGIN' => 'https://example.test:8443' ) )[0] );
check( 'draft csrf: with no Origin, a Referer of this site stores the draft', true === $BAD_NONCE_DRAFT( array( 'HTTP_REFERER' => 'https://example.test/hub/?tab=health-profile' ) )[0] );
check( 'draft csrf: a Referer of another site stores no draft', false === $BAD_NONCE_DRAFT( array( 'HTTP_REFERER' => 'https://evil.test/?https://example.test/' ) )[0] );
fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => 'Tight belly', 'actions' => '', 'contacts' => '' ), false );
$_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';
fb_handle();
check( 'draft csrf: control, a valid nonce keeps the draft whatever the fetch header says', isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) );
fb_arrange( $FP_ON, false );
fb_post_save( array( 'signs' => 'typed text', 'actions' => '', 'contacts' => '' ) );
$_POST['vance_hub_flare_nonce'] = 'forged';
fb_handle();
check( 'draft: a visitor who is not logged in gets no draft', $GLOBALS['TRANSIENTS'] === array() );
fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => '', 'actions' => '', 'contacts' => '' ) );
fb_handle();
check( 'draft: when every text is empty no draft is stored', $GLOBALS['TRANSIENTS'] === array() );
fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => str_repeat( 'a', 5000 ), 'actions' => "x\x00y", 'contacts' => '' ) );
fb_handle();
$DRAFT34 = $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ?? array();
check( 'draft: a toolong save keeps the text cut to 4000 characters per field, cleaned', strlen( $DRAFT34['answers']['signs'] ?? '' ) === 4000 && ( $DRAFT34['answers']['actions'] ?? null ) === 'xy' );
fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
fb_post_flare( 'flare_end', str_repeat( 'h', 501 ) );
fb_handle();
$DRAFT34 = $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ?? array();
check( 'draft: a toolong end keeps the helped text, with do flare_end', ( $DRAFT34['do'] ?? null ) === 'flare_end' && strlen( $DRAFT34['helped'] ?? '' ) === 501 );
fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = array( $LOG_OK );
fb_post_flare( 'flare_end', 'Rest' );
fb_handle();
check( 'draft: an end with no open flare (flare_none) keeps the helped text', ( $GLOBALS['TRANSIENTS']['vance_flare_draft_7']['helped'] ?? null ) === 'Rest' );
foreach ( array( 'delete', 'delete (no lock)' ) as $case34 ) {
	fb_arrange( $FP_ON );
	fb_seed_all();
	if ( 'delete (no lock)' === $case34 ) {
		$GLOBALS['LOCK_RESULT'] = '0';
	}
	fb_post_delete();
	fb_handle();
	check( "draft: $case34 clears the draft", ! isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) );
}
fb_arrange( $FP_ON );
fb_post_flare( 'flare_start' );
fb_handle();
check( 'draft: a flare start stores no draft', $GLOBALS['TRANSIENTS'] === array() );
check( 'draft: a draft of the wrong shape is dropped when taken', ( function () {
	$GLOBALS['TRANSIENTS']['vance_flare_draft_7'] = array( 'do' => 'delete', 'answers' => array( 'signs' => 'x' ), 'helped' => '' );
	return vance_hub_flare_plan_draft_take( 7 ) === null && ! isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] );
} )() );

// Render: the form holds the draft.
$P34 = array( 'updated' => $OLD_TS, 'signs' => 'saved signs', 'actions' => 'saved actions', 'contacts' => 'saved contacts' );
$DR_SAVE = array( 'do' => 'save', 'answers' => array( 'signs' => 'typed signs <b>', 'actions' => '', 'contacts' => 'typed contacts' ), 'helped' => '' );
$H34 = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $P34, 'updated' => '10 Nov 2026', 'flash' => 'failed', 'draft' => $DR_SAVE ) ) );
check( 'draft render: the edit form holds the typed text, not the saved plan', false !== strpos( $H34, '>typed signs &lt;b&gt;</textarea>' ) && false !== strpos( $H34, '>typed contacts</textarea>' ) && false === strpos( $H34, '>saved signs</textarea>' ) );
check( 'draft render: a blank typed answer stays blank, it is not refilled from the saved plan', false !== strpos( $H34, 'id="vhd-flare-edit-actions" name="vhd_flare[actions]" rows="4" maxlength="1000" aria-describedby="vhd-flare-edit-actions-hint"></textarea>' ) );
check( 'draft render: the Edit box is open and the line is shown once', false !== strpos( $H34, '<details class="vhd-flare__edit" open>' ) && 1 === substr_count( $H34, 'We kept what you typed. Check it, then save again.' ) );
$H34B = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $P34, 'updated' => '10 Nov 2026', 'flash' => 'failed' ) ) );
check( 'draft render: with no draft the saved plan fills the form, the box is closed, there is no line', false !== strpos( $H34B, '>saved signs</textarea>' ) && false !== strpos( $H34B, '<details class="vhd-flare__edit">' ) && false === strpos( $H34B, 'We kept what you typed' ) );
$H34C = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => null, 'flash' => 'invalid', 'draft' => $DR_SAVE ) ) );
check( 'draft render: the builder with no saved plan holds the typed text and the line', false !== strpos( $H34C, '>typed signs &lt;b&gt;</textarea>' ) && false !== strpos( $H34C, 'We kept what you typed. Check it, then save again.' ) && false !== strpos( $H34C, 'data-vhd-wizard' ) );
$DR_END = array( 'do' => 'flare_end', 'answers' => array( 'signs' => '', 'actions' => '', 'contacts' => '' ), 'helped' => 'typed <helped>' );
$H34D = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $P34, 'updated' => '10 Nov 2026', 'flash' => 'toolong', 'draft' => $DR_END, 'log' => $LOG_OPEN_VIEW ) ) );
check( 'draft render: an end draft fills the what-helped box, with the line above it', false !== strpos( $H34D, 'maxlength="500">typed &lt;helped&gt;</textarea>' ) && 1 === substr_count( $H34D, 'We kept what you typed. Check it, then save again.' ) );
check( 'draft render: an end draft does not fill the plan form or open the Edit box', false !== strpos( $H34D, '>saved signs</textarea>' ) && false !== strpos( $H34D, '<details class="vhd-flare__edit">' ) );
check( 'draft render: a plan draft does not fill the what-helped box', false !== strpos( vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $P34, 'updated' => '10 Nov 2026', 'draft' => $DR_SAVE, 'log' => $LOG_OPEN_VIEW ) ) ), 'maxlength="500"></textarea>' ) );

/* ===================================================================== */
section( '35. a failed write changes nothing; the log keeps 50 entries' );

// No earlier plan, a new agreement, and a store that writes damaged data.
fb_arrange( $FP_ON );
fb_post_save();
$GLOBALS['CORRUPT_ONCE'] = '_sla_flare_plan';
check( 'failed write: a plan the read-back rejects gives failed', fb_handle() === 'https://example.test/hub/?vance_flare=failed' );
check( 'failed write: with no earlier plan no plan is left stored (the damaged value is removed)', fb_meta( '_sla_flare_plan' ) === null );
check( 'failed write: the agreement of the same request is rolled back to what it was (none)', fb_meta( '_sla_consent_flare_plan' ) === null && fb_meta( '_sla_consent_flare_plan_at' ) === null && fb_meta( '_sla_consent_flare_plan_text' ) === null );
// An earlier plan and agreement: the old plan comes back exactly.
fb_arrange( $FP_ON );
fb_consented();
fb_stored_plan();
$OLD_PLAN35 = fb_meta( '_sla_flare_plan' );
fb_post_save( array( 'signs' => 'New', 'actions' => '', 'contacts' => '' ), false );
$GLOBALS['CORRUPT_ONCE'] = '_sla_flare_plan';
check( 'failed write: a failed edit gives failed', fb_handle() === 'https://example.test/hub/?vance_flare=failed' );
check( 'failed write: the earlier plan is put back exactly', fb_meta( '_sla_flare_plan' ) === $OLD_PLAN35 );
check( 'failed write: the earlier agreement is unchanged', fb_meta( '_sla_consent_flare_plan' ) === '1' && fb_meta( '_sla_consent_flare_plan_at' ) === '2026-10-01 09:00:00' );
// A backslash in the earlier plan survives the put-back.
fb_arrange( $FP_ON );
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_plan'] = array( 'updated' => $OLD_TS, 'signs' => 'C:\\Clinic', 'actions' => '', 'contacts' => '' );
fb_post_save( array( 'signs' => 'New', 'actions' => '', 'contacts' => '' ), false );
$GLOBALS['CORRUPT_ONCE'] = '_sla_flare_plan';
fb_handle();
check( 'failed write: a backslash in the earlier plan is still one backslash after the put-back', ( fb_meta( '_sla_flare_plan' )['signs'] ?? null ) === 'C:\\Clinic' );
// An earlier agreement for other wording and a failed plan write: the agreement is restored.
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_consent_flare_plan']      = '1';
$GLOBALS['UMETA'][7]['_sla_consent_flare_plan_at']   = '2026-09-01 09:00:00';
$GLOBALS['UMETA'][7]['_sla_consent_flare_plan_text'] = 'Old wording.';
fb_post_save();
$GLOBALS['CORRUPT_ONCE'] = '_sla_flare_plan';
fb_handle();
check( 'failed write: an agreement to old wording is restored as it was after a failed save', fb_meta( '_sla_consent_flare_plan_at' ) === '2026-09-01 09:00:00' && fb_meta( '_sla_consent_flare_plan_text' ) === 'Old wording.' && fb_meta( '_sla_flare_plan' ) === null );

// The log.
fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = array( $LOG_OK );
$GLOBALS['CORRUPT_ONCE'] = '_sla_flare_log';
check( 'failed log write: a start whose read-back is rejected gives failed', vance_hub_flare_log_action( 7, 'flare_start', $LOG_NOW, '' ) === 'failed' );
check( 'failed log write: the earlier log is put back exactly', fb_meta( '_sla_flare_log' ) === array( $LOG_OK ) );
fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['CORRUPT_ONCE'] = '_sla_flare_log';
check( 'failed log write: with no earlier log the damaged value is removed', vance_hub_flare_log_action( 7, 'flare_start', $LOG_NOW, '' ) === 'failed' && fb_meta( '_sla_flare_log' ) === null );
fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_OPEN;
$GLOBALS['CORRUPT_ONCE'] = '_sla_flare_log';
check( 'failed log write: an end whose read-back is rejected gives failed and the flare stays open', vance_hub_flare_log_action( 7, 'flare_end', $LOG_NOW, 'Rest' ) === 'failed' && fb_meta( '_sla_flare_log' ) === $LOG_OPEN );

// Trimmed.
fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = array_slice( $SIXTY, 0, 50 );
check( 'trimmed: a start with 50 rows gives flare_started_trimmed', vance_hub_flare_log_action( 7, 'flare_start', $LOG_NOW, '' ) === 'flare_started_trimmed' );
check( 'trimmed: the log still holds 50 rows, the new one first, the oldest gone', count( fb_meta( '_sla_flare_log' ) ) === 50 && fb_meta( '_sla_flare_log' )[0]['end'] === '' && fb_meta( '_sla_flare_log' )[49]['id'] === 'r48' );
fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = array_slice( $SIXTY, 0, 49 );
check( 'trimmed: a start with 49 rows gives flare_started and keeps all 49', vance_hub_flare_log_action( 7, 'flare_start', $LOG_NOW, '' ) === 'flare_started' && count( fb_meta( '_sla_flare_log' ) ) === 50 );
fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = array_slice( $SIXTY, 0, 50 );
fb_post_flare( 'flare_start' );
check( 'trimmed: the handler redirects with flare_started_trimmed', fb_handle() === 'https://example.test/hub/?vance_flare=flare_started_trimmed' );

/* ===================================================================== */
section( '36. the appointment sheet and the quick check read each store only through its own gate' );

$GLOBALS['OPEN_GATES'] = false;
$CI36   = array( array( 'id' => 'c1', 'ts' => strtotime( '2026-11-18 09:00:00 UTC' ), 'payload' => array( 'pain' => 9, 'bowel' => -2, 'tired' => 3, 'kind' => 'check-in' ) ) );
$PROF36 = array( 'next_appointment' => '2026-12-03', 'appointment_questions' => 'Can I eat oats?', 'medication' => 'Iron tablets' );
$MN36   = array( array( 'id' => 'm1', 'ts' => strtotime( '2026-11-14 10:00:00 UTC' ), 'payload' => array( 'score' => 3, 'maxScore' => 8, 'riskLabel' => 'Medium risk' ) ) );
function fb_stores36( $ci, $prof, $mn ) {
	$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history']         = $ci;
	$GLOBALS['UMETA'][7]['_sla_clinical_profile']                = $prof;
	$GLOBALS['UMETA'][7]['_sla_malnutrition_calculator_history'] = $mn;
}
function fb_checkin_agreed36() {
	$GLOBALS['UMETA'][7]['_sla_consent_checkin']      = '1';
	$GLOBALS['UMETA'][7]['_sla_consent_checkin_at']   = '2026-10-01 09:00:00';
	$GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] = vance_hub_checkin_consent_text();
}

// Everything closed.
fb_arrange( $FP_ON );
fb_stores36( $CI36, $PROF36, $MN36 );
$S36 = vance_hub_flare_sheet_data( 7, $PLAN_X, array(), $LOG_NOW );
check( 'sheet gates: every gate closed gives empty profile parts, no check-ins and no screening', $S36['appointment'] === '' && $S36['questions'] === '' && $S36['medication'] === '' && $S36['checkins'] === array() && $S36['screening'] === null );
check( 'sheet gates: every gate closed gives no quick check data', vance_hub_flare_plan_quick_data( 7 ) === null );
// Only the health agreement.
fb_arrange( $FP_ON );
fb_stores36( $CI36, $PROF36, $MN36 );
$GLOBALS['UMETA'][7]['_sla_consent_health'] = '1';
$S36 = vance_hub_flare_sheet_data( 7, $PLAN_X, array(), $LOG_NOW );
check( 'sheet gates: the health agreement opens the profile and the screening, and not the check-ins', $S36['appointment'] === '3 Dec 2026' && $S36['questions'] === 'Can I eat oats?' && $S36['medication'] === 'Iron tablets' && is_array( $S36['screening'] ) && $S36['checkins'] === array() && vance_hub_flare_plan_quick_data( 7 ) === null );
// Only the check-in gate.
fb_arrange( $FP_ON );
$GLOBALS['MODS']['vance_hub_dash_checkin'] = true;
fb_checkin_agreed36();
fb_stores36( $CI36, $PROF36, $MN36 );
$S36 = vance_hub_flare_sheet_data( 7, $PLAN_X, array(), $LOG_NOW );
check( 'sheet gates: the check-in gate opens the check-ins and the quick check, and not the profile or the screening', count( $S36['checkins'] ) === 1 && $S36['appointment'] === '' && $S36['questions'] === '' && $S36['medication'] === '' && $S36['screening'] === null && is_array( vance_hub_flare_plan_quick_data( 7 ) ) );
check( 'sheet clamp: check-in values are held between 0 and 4 in the sheet', ( $S36['checkins'][0] ?? null ) === array( 'date' => '18 Nov 2026', 'pain' => 4, 'bowel' => 0, 'tired' => 3 ) );
check( 'quick clamp: the same values in the quick check', vance_hub_flare_plan_quick_data( 7 ) === array( 'date' => '18 Nov 2026', 'pain' => 4, 'bowel' => 0, 'tired' => 3 ) );
// The check-in switch off with a valid agreement.
fb_arrange( $FP_ON );
fb_checkin_agreed36();
fb_stores36( $CI36, $PROF36, $MN36 );
check( 'sheet gates: a valid check-in agreement with the check-in switch off is closed', vance_hub_flare_sheet_data( 7, $PLAN_X, array(), $LOG_NOW )['checkins'] === array() && vance_hub_flare_plan_quick_data( 7 ) === null );
// The check-in switch on, the agreement for other wording.
fb_arrange( $FP_ON );
$GLOBALS['MODS']['vance_hub_dash_checkin'] = true;
fb_checkin_agreed36();
$GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] = 'Old wording.';
fb_stores36( $CI36, $PROF36, $MN36 );
check( 'sheet gates: a check-in agreement to old wording is closed', vance_hub_flare_sheet_data( 7, $PLAN_X, array(), $LOG_NOW )['checkins'] === array() && vance_hub_flare_plan_quick_data( 7 ) === null );
// A flare plan agreement does not open the check-ins.
fb_arrange( $FP_ON );
$GLOBALS['MODS']['vance_hub_dash_checkin'] = true;
fb_consented();
fb_stores36( $CI36, $PROF36, $MN36 );
check( 'sheet gates: the flare plan agreement does not open the check-ins', vance_hub_flare_sheet_data( 7, $PLAN_X, array(), $LOG_NOW )['checkins'] === array() );

// data(): the sheet is null while consent is needed.
fb_arrange( $FP_ON );
fb_stored_plan();
$GLOBALS['UMETA'][7]['_sla_consent_health'] = '1';
fb_stores36( $CI36, $PROF36, $MN36 );
$D36 = vance_hub_flare_plan_data( 7 );
check( 'sheet data: the sheet is null while the flare agreement is missing', $D36['need_consent'] === true && $D36['sheet'] === null );
fb_consented();
$D36 = vance_hub_flare_plan_data( 7 );
check( 'sheet data: the sheet is an array once the agreement is valid', $D36['need_consent'] === false && is_array( $D36['sheet'] ) && $D36['sheet']['medication'] === 'Iron tablets' );
$H36 = vance_hub_flare_plan_html( vance_hub_flare_plan_data( 7 ) );
check( 'sheet render: with a sheet the block is rendered', false !== strpos( $H36, 'vhd-flare__sheetbox' ) && false !== strpos( $H36, 'Iron tablets' ) );
check( 'sheet render: the screening line has no risk label', false !== strpos( $H36, 'Score 3 of 8, on 14 Nov 2026.' ) && false === strpos( $H36, 'Medium risk' ) );
fb_arrange( $FP_ON );
fb_stored_plan();
$H36 = vance_hub_flare_plan_html( vance_hub_flare_plan_data( 7 ) );
check( 'sheet render: with the sheet null the sheet block is not rendered', false === strpos( $H36, 'vhd-flare__sheetbox' ) && false === strpos( $H36, 'Print appointment sheet' ) );
check( 'sheet render: the plan form still shows when the sheet is hidden', false !== strpos( $H36, '<details class="vhd-flare__edit" open>' ) );

/* ===================================================================== */
section( '37. remove one log entry' );

fb_arrange( $FP_ON );
fb_stored_plan();
fb_consented();
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_TWO;
fb_post_remove( 'o' );
check( 'remove: an open row is removed, with flare_removed', fb_handle() === 'https://example.test/hub/?vance_flare=flare_removed' && fb_meta( '_sla_flare_log' ) === array( $LOG_TWO[1] ) );
check( 'remove: the lock is taken first and released last, once', fb_seq_locked_once() && fb_seq_index( 'lock' ) === 0 );
check( 'remove: the plan and the agreement are not touched', fb_meta( '_sla_flare_plan' ) !== null && fb_meta( '_sla_consent_flare_plan' ) === '1' && ! in_array( 'delete_user_meta:_sla_flare_plan', $GLOBALS['WRITES'], true ) );
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_TWO;
fb_post_remove( 'a1' );
check( 'remove: a closed row is removed, the open row stays', fb_handle() === 'https://example.test/hub/?vance_flare=flare_removed' && fb_meta( '_sla_flare_log' ) === array( $LOG_TWO[0] ) );
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_TWO;
fb_post_remove( 'nope' );
check( 'remove: an unknown id gives flare_none and writes nothing', fb_handle() === 'https://example.test/hub/?vance_flare=flare_none' && $GLOBALS['WRITES'] === array() );
foreach ( array( 'a-1', 'a 1', '', 'a1;', '../a1' ) as $bad37 ) {
	fb_arrange( $FP_ON );
	$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_TWO;
	fb_post_remove( $bad37 );
	check( 'remove: the id ' . var_export( $bad37, true ) . ' (not letters, digits, underscore) gives flare_none and writes nothing', fb_handle() === 'https://example.test/hub/?vance_flare=flare_none' && $GLOBALS['WRITES'] === array() );
}
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_TWO;
fb_post_remove( array( 'o' ) );
check( 'remove: an array id gives flare_none', fb_handle() === 'https://example.test/hub/?vance_flare=flare_none' && $GLOBALS['WRITES'] === array() );
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = array( $LOG_OK );
fb_post_remove( 'a1' );
check( 'remove: the last row removed deletes the meta key', fb_handle() === 'https://example.test/hub/?vance_flare=flare_removed' && fb_meta( '_sla_flare_log' ) === null && in_array( 'delete_user_meta:_sla_flare_log', $GLOBALS['WRITES'], true ) );
fb_arrange( $FP_ON );
fb_post_remove( 'a1' );
check( 'remove: with no log at all gives flare_none', fb_handle() === 'https://example.test/hub/?vance_flare=flare_none' );
// An old row with no id.
$NOID37 = array( array( 'id' => '', 'start' => '2026-10-01', 'end' => '2026-10-02', 'helped' => '' ), array( 'id' => 'x1', 'start' => '2026-11-01', 'end' => '', 'helped' => '' ) );
$VIEW37 = vance_hub_flare_log_view( vance_hub_flare_log_normalise( $NOID37 ), $LOG_NOW );
check( 'row id: a row with no id gets a stable id made from its dates', ( $VIEW37['rows'][0]['id'] ?? null ) === 'r' . md5( '2026-10-012026-10-02' ) && ( $VIEW37['rows'][1]['id'] ?? null ) === 'x1' );
check( 'row id: the view exposes the id of the open flare too', ( $VIEW37['open']['id'] ?? null ) === 'x1' );
check( 'row id: an id with a dash is replaced by the fallback id', vance_hub_flare_log_row_id( array( 'id' => 'a-b', 'start' => '2026-10-01', 'end' => '' ) ) === 'r' . md5( '2026-10-01' ) );
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $NOID37;
fb_post_remove( 'r' . md5( '2026-10-012026-10-02' ) );
check( 'row id: the fallback id removes the row that has no id', fb_handle() === 'https://example.test/hub/?vance_flare=flare_removed' && count( fb_meta( '_sla_flare_log' ) ) === 1 && fb_meta( '_sla_flare_log' )[0]['id'] === 'x1' );
// Two identical rows with no id: the first goes.
fb_arrange( $FP_ON );
$TWIN37 = array( array( 'id' => '', 'start' => '2026-10-01', 'end' => '2026-10-02', 'helped' => 'first' ), array( 'id' => '', 'start' => '2026-10-01', 'end' => '2026-10-02', 'helped' => 'second' ) );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $TWIN37;
fb_post_remove( 'r' . md5( '2026-10-012026-10-02' ) );
fb_handle();
check( 'row id: two rows with the same fallback id lose one row, the first', count( fb_meta( '_sla_flare_log' ) ) === 1 && ( fb_meta( '_sla_flare_log' )[0]['helped'] ?? null ) === 'second' );
// Lock, nonce, gate, failed write.
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_TWO;
$GLOBALS['LOCK_RESULT'] = '0';
fb_post_remove( 'o' );
check( 'remove: without the lock the result is failed and the log is unchanged', fb_handle() === 'https://example.test/hub/?vance_flare=failed' && fb_meta( '_sla_flare_log' ) === $LOG_TWO && $GLOBALS['WRITES'] === array() );
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_TWO;
fb_post_remove( 'o' );
$_POST['vance_hub_flare_nonce'] = 'forged';
check( 'remove: a bad nonce gives expired and removes nothing', fb_handle() === 'https://example.test/hub/?vance_flare=expired' && fb_meta( '_sla_flare_log' ) === $LOG_TWO );
fb_arrange( $FP_ON, false );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_TWO;
fb_post_remove( 'o' );
check( 'remove: a visitor who is not logged in does nothing', fb_handle() === null && fb_meta( '_sla_flare_log' ) === $LOG_TWO );
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_TWO;
fb_post_remove( 'a1' );
$GLOBALS['CORRUPT_ONCE'] = '_sla_flare_log';
check( 'remove: a write the read-back rejects gives failed and the old log is put back', fb_handle() === 'https://example.test/hub/?vance_flare=failed' && fb_meta( '_sla_flare_log' ) === $LOG_TWO );
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = array( $LOG_OK );
$GLOBALS['DELETE_FAILS'] = '_sla_flare_log';
fb_post_remove( 'a1' );
check( 'remove: a last-row delete that does not stick gives failed', fb_handle() === 'https://example.test/hub/?vance_flare=failed' );
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $LOG_TWO;
fb_post_remove( 'o' );
fb_handle();
check( 'remove: it writes no check-in key and no flare plan key', preg_grep( '/_sla_consent_checkin|_sla_weekly_check_in_history|_sla_flare_plan/', $GLOBALS['WRITES'] ) === array() );

// Render.
$LOG_ID_VIEW = vance_hub_flare_log_view( $LOG_TWO, $LOG_NOW );
$H37 = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'log' => $LOG_ID_VIEW ) ) );
check( 'remove render: one remove form on the open flare and one on each history row (3 for 2 rows)', 3 === substr_count( $H37, 'name="vance_hub_flare_do" value="flare_remove"' ) );
check( 'remove render: each form carries the row id and the nonce', 2 === substr_count( $H37, 'name="vhd_flare_row" value="o"' ) && 1 === substr_count( $H37, 'name="vhd_flare_row" value="a1"' ) && false !== strpos( $H37, 'name="vance_hub_flare_nonce" value="nonce-vance_hub_flare_plan"' ) );
check( 'remove render: each form has a required tick box "Yes, remove it" and a "Remove this entry" button', 3 === substr_count( $H37, 'name="vhd_flare_remove_ok" value="1" required' ) && 3 === substr_count( $H37, 'Yes, remove it' ) && 3 === substr_count( $H37, 'Remove this entry' ) );
$H37B = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'log' => vance_hub_flare_log_view( array( $LOG_OK ), $LOG_NOW ) ) ) );
check( 'remove render: a closed row has a remove form and the start button shows', 1 === substr_count( $H37B, 'value="flare_remove"' ) && false !== strpos( $H37B, 'value="flare_start"' ) );
check( 'remove render: a history row with no id in the view has no remove form (old callers)', false === strpos( vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'log' => $LOG_CLOSED_VIEW ) ) ), 'flare_remove' ) );
check( 'remove render: the read-only card keeps the remove forms and has no start or end control', 3 === substr_count( vance_hub_flare_plan_html( fb_flare_view( array( 'readonly' => true, 'plan' => $PLAN_X, 'updated' => '10 Nov 2026', 'log' => $LOG_ID_VIEW ) ) ), 'value="flare_remove"' ) );

/* ===================================================================== */
section( '38. telephone links' );

function fb_tel( $text ) { return vance_hub_flare_plan_text_html( $text, true ); }
check( 'tel: a UK number with spaces becomes a link with digits only', fb_tel( 'Call 0800 000 000.' ) === 'Call <a href="tel:0800000000">0800 000 000</a>.' );
check( 'tel: a number with + keeps the plus, and the spaces go from the href', fb_tel( '+44 20 7946 0958' ) === '<a href="tel:+442079460958">+44 20 7946 0958</a>' );
check( 'tel: hyphens are allowed', fb_tel( 'Nurse 0800-000-000' ) === 'Nurse <a href="tel:0800000000">0800-000-000</a>' );
check( 'tel: a bracketed trunk zero is allowed, and its digit is counted', fb_tel( '+44 (0) 20 7946 0958' ) === '<a href="tel:+4402079460958">+44 (0) 20 7946 0958</a>' );
check( 'tel: 9 digits in total is the shortest number that links', false !== strpos( fb_tel( '0123 456 78' ), '<a href="tel:012345678">' ) );
check( 'tel: 15 digits in total is the longest number that links', false !== strpos( fb_tel( '+123456789012345' ), '<a href="tel:+123456789012345">' ) );
check( 'tel: 8 digits do not link', false === strpos( fb_tel( '0123 4567' ), '<a' ) );
check( 'tel: 16 digits do not link', false === strpos( fb_tel( '+1234567890123456' ), '<a' ) );
foreach ( array( '999', '111', '112' ) as $code38 ) {
	check( "tel: the short code $code38 alone links", fb_tel( "Ring $code38 now" ) === "Ring <a href=\"tel:$code38\">$code38</a> now" );
}
check( 'tel: a short code at the end of a sentence links, the full stop stays outside', fb_tel( 'Ring 111.' ) === 'Ring <a href="tel:111">111</a>.' );
check( 'tel: two numbers in one text both link', 2 === substr_count( fb_tel( 'Day 0800 000 000, night 0800 111 222' ), '<a href="tel:' ) );
check( 'tel: a long number holding 999 or 111 is one link, not three', 1 === substr_count( fb_tel( '0800 999 111' ), '<a href="tel:' ) );
foreach ( array( '2026-11-26', '26/11/2026', '2026', '26 November 2026', '1 Jan 2026', '01-12-2026', '01-12-2026 10:00', '2026-11-26 10:00', '12/11/26', '0123 4567 8901 2345', '1999', '11112', '£1,999', '12-111-5', '1 2 3 4 5 6 7 8 9 0' ) as $neg38 ) {
	check( 'tel: "' . $neg38 . '" does not become a link', false === strpos( fb_tel( $neg38 ), '<a' ) );
}
check( 'tel: a date next to a number leaves the date alone and links the number', fb_tel( 'Seen 2026-11-26, call 0800 000 000' ) === 'Seen 2026-11-26, call <a href="tel:0800000000">0800 000 000</a>' );
check( 'tel: markup in the text is escaped, and the number inside it still links', fb_tel( '<b>0800 000 000</b>' ) === '&lt;b&gt;<a href="tel:0800000000">0800 000 000</a>&lt;/b&gt;' );
check( 'tel: an ampersand is escaped once', fb_tel( 'A & B 0800 000 000' ) === 'A &amp; B <a href="tel:0800000000">0800 000 000</a>' );
check( 'tel: line breaks are kept', fb_tel( "Ann 0800 000 000\nBob" ) === "Ann <a href=\"tel:0800000000\">0800 000 000</a><br />\nBob" );
check( 'tel: with $tel false (the default) nothing links and the output is as before', vance_hub_flare_plan_text_html( 'Call 0800 000 000' ) === 'Call 0800 000 000' && vance_hub_flare_plan_text_html( 'a < b', false ) === 'a &lt; b' );
check( 'tel: negative control, a number the pattern should match is matched', 1 === preg_match( vance_hub_flare_plan_tel_pattern(), 'x 0800 000 000' ) );
check( 'tel: negative control, the href function refuses a date-shaped match', vance_hub_flare_plan_tel_href( '01-12-2026 10' ) === '' && vance_hub_flare_plan_tel_href( '0800 000 000' ) === '0800000000' );

// Where it is used.
$PLAN38 = array( 'updated' => $OLD_TS, 'signs' => 'Ring 0800 111 222 first', 'actions' => 'Ring 0800 333 444', 'contacts' => 'Nurse 0800 000 000' );
$H38 = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN38, 'updated' => '10 Nov 2026', 'urgent' => 'Ring 111 or 999.' ) ) );
check( 'tel render: the "Who I contact" answer links', false !== strpos( $H38, '<p>Nurse <a href="tel:0800000000">0800 000 000</a></p>' ) );
check( 'tel render: the other two answers do not link', false !== strpos( $H38, '<p>Ring 0800 111 222 first</p>' ) && false !== strpos( $H38, '<p>Ring 0800 333 444</p>' ) );
check( 'tel render: the urgent help text links its short codes', false !== strpos( $H38, 'Ring <a href="tel:111">111</a> or <a href="tel:999">999</a>.' ) );
$H38B = vance_hub_flare_plan_html( fb_flare_view( array( 'plan' => $PLAN38, 'updated' => '10 Nov 2026', 'sheet' => array( 'prepared' => '18 Nov 2026', 'appointment' => '', 'questions' => 'Ring 0800 555 666?', 'medication' => '0800 777 888', 'checkins' => array(), 'screening' => null, 'flares' => array(), 'flare_count' => 0 ) ) ) );
$SHEET38 = substr( $H38B, (int) strpos( $H38B, 'vhd-flare-sheet' ) );
check( 'tel render: the contacts on the sheet link', false !== strpos( $SHEET38, '<p>Nurse <a href="tel:0800000000">0800 000 000</a></p>' ) );
check( 'tel render: the questions, the medication and the other plan answers on the sheet do not link', 1 === substr_count( $SHEET38, '<a href="tel:' ) );
check( 'tel render: the edit box text is not linked (a textarea holds the plain text)', false !== strpos( $H38, '>Nurse 0800 000 000</textarea>' ) );

/* ===================================================================== */
section( '39. edge cases: multibyte at the limit, control-only text, draft per member, row ids, telephone, escaping' );

// Multibyte text at the limit, through the handler. The limit counts characters.
$EMOJI_1000  = str_repeat( "\xF0\x9F\x8C\xBF", 1000 );
$EMOJI_1001  = str_repeat( "\xF0\x9F\x8C\xBF", 1001 );
$ACCENT_1000 = str_repeat( "\xC3\xA9", 1000 );
$ACCENT_1001 = str_repeat( "\xC3\xA9", 1001 );

fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => $EMOJI_1000, 'actions' => 'x', 'contacts' => 'y' ) );
check( 'limit handler: 1000 emoji (4000 bytes) are saved, not refused', fb_handle() === 'https://example.test/hub/?vance_flare=saved' && ( fb_meta( '_sla_flare_plan' )['signs'] ?? null ) === $EMOJI_1000 );

fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => $EMOJI_1001, 'actions' => 'x', 'contacts' => 'y' ) );
check( 'limit handler: 1001 emoji are refused with toolong and nothing is written', fb_handle() === 'https://example.test/hub/?vance_flare=toolong' && fb_meta( '_sla_flare_plan' ) === null && $GLOBALS['WRITES'] === array() );

fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => $ACCENT_1000, 'actions' => 'x', 'contacts' => 'y' ) );
check( 'limit handler: 1000 accented characters (2000 bytes) are saved, not refused', fb_handle() === 'https://example.test/hub/?vance_flare=saved' && ( fb_meta( '_sla_flare_plan' )['signs'] ?? null ) === $ACCENT_1000 );

fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => $ACCENT_1001, 'actions' => 'x', 'contacts' => 'y' ) );
check( 'limit handler: 1001 accented characters are refused with toolong and nothing is written', fb_handle() === 'https://example.test/hub/?vance_flare=toolong' && fb_meta( '_sla_flare_plan' ) === null && $GLOBALS['WRITES'] === array() );

// Text made only of control characters is no answer.
check( 'control only: a text of control characters cleans to an empty string', vance_hub_flare_plan_clean_text( "\x00\x07\x1B\x7F" ) === '' );
check( 'control only: three answers of control characters give no answer at all (null)', vance_hub_flare_plan_sanitise( array( 'signs' => "\x00\x07", 'actions' => "\x1B", 'contacts' => "\x7F" ) ) === null );
fb_arrange( $FP_ON );
fb_post_save( array( 'signs' => "\x00\x07\x1B", 'actions' => "\x7F", 'contacts' => '' ) );
check( 'control only: a save of control characters alone is refused as invalid, with no plan and no agreement', fb_handle() === 'https://example.test/hub/?vance_flare=invalid' && fb_meta( '_sla_flare_plan' ) === null && fb_meta( '_sla_consent_flare_plan' ) === null && $GLOBALS['WRITES'] === array() );

// A draft belongs to the member who typed it.
fb_arrange( $FP_ON, true, 7 );
fb_post_save( array( 'signs' => 'Member seven text', 'actions' => '', 'contacts' => '' ), false );
$_POST['vance_hub_flare_nonce'] = 'nonce-wrong';
$_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';
fb_handle();
check( 'draft scope: an expired save of member 7 stores the draft under the member 7 key only', array_keys( $GLOBALS['TRANSIENTS'] ) === array( 'vance_flare_draft_7' ) );
check( 'draft scope: taking the draft for member 8 gives nothing', vance_hub_flare_plan_draft_take( 8 ) === null );
check( 'draft scope: taking for member 8 leaves the draft of member 7 in place', isset( $GLOBALS['TRANSIENTS']['vance_flare_draft_7'] ) );
check( 'draft scope: member 7 still gets its own draft', ( vance_hub_flare_plan_draft_take( 7 )['answers']['signs'] ?? null ) === 'Member seven text' );

// Row ids: only letters, digits and underscore are accepted.
$ROW_R1 = array( array( 'id' => 'r1', 'start' => '2026-11-01', 'end' => '2026-11-03', 'helped' => '' ) );
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $ROW_R1;
fb_post_remove( 'r1' );
check( 'row id: a plain id of letters and digits removes its row (positive control)', fb_handle() === 'https://example.test/hub/?vance_flare=flare_removed' && fb_meta( '_sla_flare_log' ) === null );
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $ROW_R1;
fb_post_remove( 'r1-x' );
check( 'row id: a hyphen in the id gives flare_none and the log is unchanged', fb_handle() === 'https://example.test/hub/?vance_flare=flare_none' && fb_meta( '_sla_flare_log' ) === $ROW_R1 && $GLOBALS['WRITES'] === array() );
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $ROW_R1;
fb_post_remove( "r1'; --" );
check( 'row id: a quote and a space in the id give flare_none and the log is unchanged', fb_handle() === 'https://example.test/hub/?vance_flare=flare_none' && fb_meta( '_sla_flare_log' ) === $ROW_R1 && $GLOBALS['WRITES'] === array() );
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $ROW_R1;
fb_post_remove( "r1\n" );
check( 'row id: a trailing line break in the id gives flare_none and the log is unchanged', fb_handle() === 'https://example.test/hub/?vance_flare=flare_none' && fb_meta( '_sla_flare_log' ) === $ROW_R1 && $GLOBALS['WRITES'] === array() );
fb_arrange( $FP_ON );
$GLOBALS['UMETA'][7]['_sla_flare_log'] = $ROW_R1;
fb_post_remove( "r1\xC3\xA9" );
check( 'row id: a non-ASCII letter in the id gives flare_none and the log is unchanged', fb_handle() === 'https://example.test/hub/?vance_flare=flare_none' && fb_meta( '_sla_flare_log' ) === $ROW_R1 && $GLOBALS['WRITES'] === array() );

// Telephone positives.
check( 'tel: a 020 number with spaces links, digits only in the href', fb_tel( '020 7946 0958' ) === '<a href="tel:02079460958">020 7946 0958</a>' );
check( 'tel: a bracketed 020 number links as one number', fb_tel( '(020) 7946 0958' ) === '<a href="tel:02079460958">(020) 7946 0958</a>' );
check( 'tel: a mobile number with a leading 0 links', fb_tel( '07700 900123' ) === '<a href="tel:07700900123">07700 900123</a>' );
check( 'tel: a plus number with spaces links, the plus kept in the href', fb_tel( '+44 20 7946 0958' ) === '<a href="tel:+442079460958">+44 20 7946 0958</a>' );
// Telephone negatives.
check( 'tel: 1000 does not become a link', fb_tel( '1000' ) === '1000' );
check( 'tel: 0.5 does not become a link', fb_tel( '0.5' ) === '0.5' );
check( 'tel: 111.5 does not become a link', fb_tel( '111.5' ) === '111.5' );
check( 'tel: a 6-digit number starting with 0 does not become a link', fb_tel( '012345' ) === '012345' );
check( 'tel: a 6-digit number starting with 1 does not become a link', fb_tel( '123456' ) === '123456' );
// The pattern itself does not match a date or a plain year (the first gate; the href check is the second).
check( 'tel pattern: 2026-11-26 is not matched at all', 0 === preg_match( vance_hub_flare_plan_tel_pattern(), '2026-11-26' ) );
check( 'tel pattern: 26/11/2026 is not matched at all', 0 === preg_match( vance_hub_flare_plan_tel_pattern(), '26/11/2026' ) );
check( 'tel pattern: 1000 is not matched at all', 0 === preg_match( vance_hub_flare_plan_tel_pattern(), '1000' ) );

// Review fixes: a closing bracket stays outside the link, a list of single
// digits is not a number, and a short code needs a call word or its own line.
check( 'tel: a closing bracket after the number stays outside the link', fb_tel( '(call 0800 123 4567)' ) === '(call <a href="tel:08001234567">0800 123 4567</a>)' );
check( 'tel: a list of single digits does not link', fb_tel( '0 3 4 2 1 0 4 3 2' ) === '0 3 4 2 1 0 4 3 2' );
check( 'tel: "pulse 112" does not link', fb_tel( 'pulse 112' ) === 'pulse 112' );
check( 'tel: "112 mg" does not link', fb_tel( '112 mg' ) === '112 mg' );
check( 'tel: "Call 999" links, whatever the letter case', fb_tel( 'Call 999' ) === 'Call <a href="tel:999">999</a>' );
check( 'tel: "NHS 111" links', fb_tel( 'NHS 111' ) === 'NHS <a href="tel:111">111</a>' );
check( 'tel: a short code alone on its line links', fb_tel( "Emergency:\n999" ) === "Emergency:<br />\n" . '<a href="tel:999">999</a>' );
check( 'tel: +44 (0)20 form links as one number', fb_tel( '+44 (0)20 7946 0958' ) === '<a href="tel:+4402079460958">+44 (0)20 7946 0958</a>' );

// Escaping with tel linking on: markup from the member is escaped, and only digits go in an href.
check( 'escape: markup typed next to a number is escaped, and the number still links', fb_tel( '<img src=x onerror=0800 000 000>' ) === '&lt;img src=x onerror=<a href="tel:0800000000">0800 000 000</a>&gt;' );
// CHANGED after review: a short code links only after a call word, so the input says "call 999".
$NASTY39 = '<a href="x">0800 000 000</a><script>alert(1)</script> +44 20 7946 0958 "q" call 999';
$OUT39   = fb_tel( $NASTY39 );
check( 'escape: the only "<" in the output are the markup of the three links', 6 === substr_count( $OUT39, '<' ) );
check( 'escape: each tel: href holds digits with an optional leading plus, and there are three', 3 === preg_match_all( '/href="tel:\+?\d+"/', $OUT39 ) && 3 === substr_count( $OUT39, 'href="tel:' ) );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . ' FAILED of ' . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK - $PASS checks passed.\n";
