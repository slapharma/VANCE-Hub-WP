<?php
/**
 * Tests for inc/hub-golive.php: the scheduled go-live.
 *
 * Same shape as hub-layout.test.php: WordPress stubs, a bag of globals the stubs
 * read and write, check() lines, and exit 1 on any failure. Nothing here loads
 * WordPress.
 *
 * WHAT IS COVERED
 *   1  vance_hub_sanitize_datetime: good values, seconds dropped, impossible dates
 *      and times, garbage, non-strings
 *   2  vance_hub_golive_timestamp: the site zone (Europe/London, in summer and in
 *      winter), zero seconds, the daylight saving gaps, invalid values
 *   3  vance_hub_golive_decide: none, wait, switch, clear; the exact minute
 *   4  vance_hub_golive_run: the Customizer preview makes no change; the switch
 *      writes exactly three mods and fires the purge, then the action; a second
 *      run changes nothing; clear, wait and none; the two health data switches
 *      are never written; the cost of "no date" is one theme mod read
 *   5  the hooks and vance_hub_golive_reschedule
 */

define( 'ABSPATH', true );
define( 'HOUR_IN_SECONDS', 3600 );
date_default_timezone_set( 'UTC' );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

set_error_handler( function ( $no, $str, $file, $line ) {
	if ( ! ( error_reporting() & $no ) ) {
		return false;
	}
	throw new ErrorException( $str, 0, $no, $file, $line );
} );

/* ---- state the stubs read and write; reset_state() clears it ---------------- */

$GLOBALS['HOOKS'] = array();

function reset_state() {
	$GLOBALS['MODS']        = array(); // Saved theme mods.
	$GLOBALS['PREVIEW']     = false;   // is_customize_preview().
	$GLOBALS['SITE_TZ']     = 'Europe/London';
	$GLOBALS['WRITES']      = array(); // Every set_theme_mod / remove_theme_mod, in order.
	$GLOBALS['READS']       = 0;       // Every vance_get_theme_mod call.
	$GLOBALS['ACTIONS']     = array(); // Every do_action tag, in order.
	$GLOBALS['CRON']        = array(); // Every cron call, in order.
}
reset_state();

/* ---- WordPress stubs ------------------------------------------------------ */

function __( $text, $domain = '' ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_url( $url ) { return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8', false ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }
function current_user_can( $cap ) { return false; }
function vance_get_theme_mod( $key, $default = '' ) {
	$GLOBALS['READS']++;
	return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
}
function set_theme_mod( $key, $value ) {
	$GLOBALS['WRITES'][]    = array( 'set', $key );
	$GLOBALS['MODS'][ $key ] = $value;
}
function remove_theme_mod( $key ) {
	$GLOBALS['WRITES'][] = array( 'remove', $key );
	unset( $GLOBALS['MODS'][ $key ] );
}
function is_customize_preview() { return (bool) $GLOBALS['PREVIEW']; }
function wp_timezone() { return new DateTimeZone( $GLOBALS['SITE_TZ'] ); }
function wp_timezone_string() { return $GLOBALS['SITE_TZ']; }
function do_action( $tag, ...$args ) { $GLOBALS['ACTIONS'][] = $tag; }
function wp_clear_scheduled_hook( $hook ) { $GLOBALS['CRON'][] = array( 'clear', $hook ); }
function wp_schedule_single_event( $timestamp, $hook ) { $GLOBALS['CRON'][] = array( 'schedule', $hook, $timestamp ); return true; }
function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['HOOKS'][ $tag ][ $priority ][] = $callback;
	return true;
}
function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) { return add_action( $tag, $callback, $priority, $accepted_args ); }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }

/* ---- load the real code under test ---------------------------------------- */

require_once $THEME . '/inc/hub-layout.php';
require_once $THEME . '/inc/hub-golive.php';

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/** The names that a run wrote, as "set:name" or "remove:name", in order. */
function writes() {
	return array_map( function ( $w ) { return $w[0] . ':' . $w[1]; }, $GLOBALS['WRITES'] );
}

$LONDON = new DateTimeZone( 'Europe/London' );
$UTC    = new DateTimeZone( 'UTC' );

/* ===================================================================== */

section( '1. vance_hub_sanitize_datetime' );

check( 'sanitise: a good value stays as it is', vance_hub_sanitize_datetime( '2026-07-01T09:30' ) === '2026-07-01T09:30' );
check( 'sanitise: seconds are dropped', vance_hub_sanitize_datetime( '2026-07-01T09:30:45' ) === '2026-07-01T09:30' );
check( 'sanitise: zero seconds are dropped too', vance_hub_sanitize_datetime( '2026-07-01T09:30:00' ) === '2026-07-01T09:30' );
check( 'sanitise: surrounding spaces are trimmed', vance_hub_sanitize_datetime( "  2026-07-01T09:30\n" ) === '2026-07-01T09:30' );
check( 'sanitise: midnight and 23:59 are valid', vance_hub_sanitize_datetime( '2026-07-01T00:00' ) === '2026-07-01T00:00' && vance_hub_sanitize_datetime( '2026-07-01T23:59' ) === '2026-07-01T23:59' );
check( 'sanitise: 29 February in a leap year is valid', vance_hub_sanitize_datetime( '2028-02-29T10:00' ) === '2028-02-29T10:00' );
check( 'sanitise: 29 February in a common year is not', vance_hub_sanitize_datetime( '2026-02-29T10:00' ) === '' );
check( 'sanitise: 31 February is not a date', vance_hub_sanitize_datetime( '2026-02-31T10:00' ) === '' );
check( 'sanitise: month 13 and day 00 are not dates', vance_hub_sanitize_datetime( '2026-13-01T10:00' ) === '' && vance_hub_sanitize_datetime( '2026-01-00T10:00' ) === '' );
check( 'sanitise: hour 24 is not a time', vance_hub_sanitize_datetime( '2026-07-01T24:00' ) === '' );
check( 'sanitise: minute 60 is not a time', vance_hub_sanitize_datetime( '2026-07-01T09:60' ) === '' );
check( 'sanitise: second 60 is not a time', vance_hub_sanitize_datetime( '2026-07-01T09:30:60' ) === '' );
check( 'sanitise: a date with no time is refused', vance_hub_sanitize_datetime( '2026-07-01' ) === '' );
check( 'sanitise: a space instead of the T is refused', vance_hub_sanitize_datetime( '2026-07-01 09:30' ) === '' );
check( 'sanitise: a one-digit hour is refused', vance_hub_sanitize_datetime( '2026-07-01T9:30' ) === '' );
check( 'sanitise: a time zone suffix is refused', vance_hub_sanitize_datetime( '2026-07-01T09:30Z' ) === '' && vance_hub_sanitize_datetime( '2026-07-01T09:30+01:00' ) === '' );
check( 'sanitise: text around a good value is refused', vance_hub_sanitize_datetime( 'x2026-07-01T09:30' ) === '' && vance_hub_sanitize_datetime( '2026-07-01T09:30x' ) === '' );
check( 'sanitise: garbage and the empty string give the empty string', vance_hub_sanitize_datetime( 'tomorrow' ) === '' && vance_hub_sanitize_datetime( '' ) === '' );
check( 'sanitise: a non-string gives the empty string', vance_hub_sanitize_datetime( null ) === '' && vance_hub_sanitize_datetime( 20260701 ) === '' && vance_hub_sanitize_datetime( array( '2026-07-01T09:30' ) ) === '' );
check( 'sanitise: a value with a newline inside is refused', vance_hub_sanitize_datetime( "2026-07-01T09:30\nx" ) === '' );

section( '2. vance_hub_golive_timestamp' );

check( 'timestamp: London in summer is one hour behind UTC (09:30 BST is 08:30 UTC)', vance_hub_golive_timestamp( '2026-07-01T09:30', $LONDON ) === gmmktime( 8, 30, 0, 7, 1, 2026 ) );
check( 'timestamp: London in winter is UTC (09:30 GMT is 09:30 UTC)', vance_hub_golive_timestamp( '2026-01-15T09:30', $LONDON ) === gmmktime( 9, 30, 0, 1, 15, 2026 ) );
check( 'timestamp: the same value in UTC is a different instant from London in summer', vance_hub_golive_timestamp( '2026-07-01T09:30', $UTC ) === gmmktime( 9, 30, 0, 7, 1, 2026 ) );
check( 'timestamp: a zone ahead of UTC (Asia/Tokyo) is nine hours ahead', vance_hub_golive_timestamp( '2026-07-01T09:30', new DateTimeZone( 'Asia/Tokyo' ) ) === gmmktime( 0, 30, 0, 7, 1, 2026 ) );
check( 'timestamp: the seconds are zero, whatever the clock says now', vance_hub_golive_timestamp( '2026-07-01T09:30', $LONDON ) % 60 === 0 );
check( 'timestamp: seconds in the value are ignored', vance_hub_golive_timestamp( '2026-07-01T09:30:45', $LONDON ) === gmmktime( 8, 30, 0, 7, 1, 2026 ) );
check( 'timestamp: a time that the spring clock change skips moves forward (01:30 on 29 March 2026 is 02:30 BST, 01:30 UTC)', vance_hub_golive_timestamp( '2026-03-29T01:30', $LONDON ) === gmmktime( 1, 30, 0, 3, 29, 2026 ) );
$AMBIGUOUS = vance_hub_golive_timestamp( '2026-10-25T01:30', $LONDON );
check( 'timestamp: a time that happens twice in the autumn gives one of the two instants', in_array( $AMBIGUOUS, array( gmmktime( 0, 30, 0, 10, 25, 2026 ), gmmktime( 1, 30, 0, 10, 25, 2026 ) ), true ) );
check( 'timestamp: an invalid value gives 0', vance_hub_golive_timestamp( 'garbage', $LONDON ) === 0 && vance_hub_golive_timestamp( '', $LONDON ) === 0 && vance_hub_golive_timestamp( '2026-02-31T10:00', $LONDON ) === 0 && vance_hub_golive_timestamp( null, $LONDON ) === 0 );
check( 'timestamp: the result is an integer', is_int( vance_hub_golive_timestamp( '2026-07-01T09:30', $LONDON ) ) );

section( '3. vance_hub_golive_decide' );

$SUMMER = '2026-07-01T09:30';
$AT     = gmmktime( 8, 30, 0, 7, 1, 2026 ); // 09:30 BST.
check( 'decide: no date gives none', vance_hub_golive_decide( '', $AT, $LONDON, 'classic' ) === 'none' );
check( 'decide: an invalid date gives none', vance_hub_golive_decide( 'garbage', $AT, $LONDON, 'classic' ) === 'none' && vance_hub_golive_decide( '2026-02-31T10:00', $AT, $LONDON, 'classic' ) === 'none' );
check( 'decide: an invalid date with the layout hub gives none (nothing to clear)', vance_hub_golive_decide( '', $AT, $LONDON, 'hub' ) === 'none' );
check( 'decide: one second before the minute waits', vance_hub_golive_decide( $SUMMER, $AT - 1, $LONDON, 'classic' ) === 'wait' );
check( 'decide: the exact minute switches', vance_hub_golive_decide( $SUMMER, $AT, $LONDON, 'classic' ) === 'switch' );
check( 'decide: one second after the minute switches', vance_hub_golive_decide( $SUMMER, $AT + 1, $LONDON, 'classic' ) === 'switch' );
check( 'decide: a long time after the minute switches', vance_hub_golive_decide( $SUMMER, $AT + 86400 * 400, $LONDON, 'classic' ) === 'switch' );
check( 'decide: a time far in the past waits for nothing', vance_hub_golive_decide( $SUMMER, 0, $LONDON, 'classic' ) === 'wait' );
check( 'decide: London summer: 08:30 UTC is the minute. 09:30 UTC is an hour too late for a UTC reading', vance_hub_golive_decide( $SUMMER, $AT - 1800, $LONDON, 'classic' ) === 'wait' && vance_hub_golive_decide( $SUMMER, gmmktime( 9, 30, 0, 7, 1, 2026 ), $UTC, 'classic' ) === 'switch' );
check( 'decide: the same value and "now" in UTC and in London give different answers in summer', vance_hub_golive_decide( $SUMMER, gmmktime( 9, 0, 0, 7, 1, 2026 ), $UTC, 'classic' ) === 'wait' && vance_hub_golive_decide( $SUMMER, gmmktime( 9, 0, 0, 7, 1, 2026 ), $LONDON, 'classic' ) === 'switch' );
check( 'decide: London winter: the minute is 09:30 UTC', vance_hub_golive_decide( '2026-01-15T09:30', gmmktime( 9, 29, 59, 1, 15, 2026 ), $LONDON, 'classic' ) === 'wait' && vance_hub_golive_decide( '2026-01-15T09:30', gmmktime( 9, 30, 0, 1, 15, 2026 ), $LONDON, 'classic' ) === 'switch' );
check( 'decide: the layout hub gives clear, whether the time is reached', vance_hub_golive_decide( $SUMMER, $AT + 60, $LONDON, 'hub' ) === 'clear' );
check( 'decide: the layout hub gives clear, whether the time is not reached', vance_hub_golive_decide( $SUMMER, $AT - 3600, $LONDON, 'hub' ) === 'clear' );
check( 'decide: an unknown layout value is not hub, so the time decides', vance_hub_golive_decide( $SUMMER, $AT, $LONDON, '' ) === 'switch' && vance_hub_golive_decide( $SUMMER, $AT - 1, $LONDON, 'bogus' ) === 'wait' );

section( '4. vance_hub_golive_run' );

$PAST   = '2020-01-01T00:00';
$FUTURE = '2099-01-01T00:00';

// The Customizer preview: an unsaved, previewed date must never make a saved change.
reset_state();
$GLOBALS['PREVIEW'] = true;
$GLOBALS['MODS']    = array( 'vance_hub_golive_at' => $PAST, 'vance_home_layout' => 'classic' );
$BEFORE_MODS        = $GLOBALS['MODS'];
$R                  = vance_hub_golive_run();
check( 'run, preview: a past date in the Customizer preview changes nothing saved', $GLOBALS['WRITES'] === array() && $GLOBALS['MODS'] === $BEFORE_MODS );
check( 'run, preview: no purge and no went-live action fire', $GLOBALS['ACTIONS'] === array() );
check( 'run, preview: the run returns before it reads any theme mod', 0 === $GLOBALS['READS'] );
check( 'run, preview: it returns none', 'none' === $R );

// No date: one theme mod read, no write.
reset_state();
$R = vance_hub_golive_run();
check( 'run, no date: returns none', 'none' === $R );
check( 'run, no date: exactly one theme mod read', 1 === $GLOBALS['READS'] );
check( 'run, no date: nothing written, no action', $GLOBALS['WRITES'] === array() && $GLOBALS['ACTIONS'] === array() );

// A saved value that is not a date.
reset_state();
$GLOBALS['MODS'] = array( 'vance_hub_golive_at' => 'garbage' );
$R               = vance_hub_golive_run();
check( 'run, bad value: a saved value that is not a date gives none and writes nothing', 'none' === $R && $GLOBALS['WRITES'] === array() && $GLOBALS['ACTIONS'] === array() );

// A date in the future: wait.
reset_state();
$GLOBALS['MODS'] = array( 'vance_hub_golive_at' => $FUTURE, 'vance_home_layout' => 'classic' );
$R               = vance_hub_golive_run();
check( 'run, future: returns wait, writes nothing, the date stays', 'wait' === $R && $GLOBALS['WRITES'] === array() && $GLOBALS['MODS']['vance_hub_golive_at'] === $FUTURE && 'classic' === $GLOBALS['MODS']['vance_home_layout'] );

// The switch.
reset_state();
$GLOBALS['MODS'] = array( 'vance_hub_golive_at' => $PAST, 'vance_home_layout' => 'classic', 'vance_hub_header' => false );
$T0              = time();
$R               = vance_hub_golive_run();
check( 'run, switch: returns switch', 'switch' === $R );
check( 'run, switch: the layout is saved as hub', 'hub' === $GLOBALS['MODS']['vance_home_layout'] );
check( 'run, switch: the date is removed', ! array_key_exists( 'vance_hub_golive_at', $GLOBALS['MODS'] ) );
check( 'run, switch: the done time is saved, as a Unix time of now', isset( $GLOBALS['MODS']['vance_hub_golive_done_at'] ) && is_int( $GLOBALS['MODS']['vance_hub_golive_done_at'] ) && $GLOBALS['MODS']['vance_hub_golive_done_at'] >= $T0 && $GLOBALS['MODS']['vance_hub_golive_done_at'] <= time() );
check( 'run, switch: exactly three writes, in order: layout, remove the date, done time', writes() === array( 'set:vance_home_layout', 'remove:vance_hub_golive_at', 'set:vance_hub_golive_done_at' ) );
check( 'run, switch: the cache purge fires, then the went-live action', $GLOBALS['ACTIONS'] === array( 'litespeed_purge_all', 'vance_hub_went_live' ) );
check( 'run, switch: no other setting changed (the header switch keeps its saved false)', false === $GLOBALS['MODS']['vance_hub_header'] && count( $GLOBALS['MODS'] ) === 3 );

// A second run: nothing more.
$WRITES_AFTER_FIRST  = $GLOBALS['WRITES'];
$ACTIONS_AFTER_FIRST = $GLOBALS['ACTIONS'];
$MODS_AFTER_FIRST    = $GLOBALS['MODS'];
$R2                  = vance_hub_golive_run();
check( 'run, second: decides none (the date is gone)', 'none' === $R2 );
check( 'run, second: no write, no action, mods the same', $GLOBALS['WRITES'] === $WRITES_AFTER_FIRST && $GLOBALS['ACTIONS'] === $ACTIONS_AFTER_FIRST && $GLOBALS['MODS'] === $MODS_AFTER_FIRST );

// An overlapping run: a request that read the date before the first removed it.
// It decides clear, because the layout is hub now. The date has no more use.
$GLOBALS['MODS']['vance_hub_golive_at'] = $PAST;
$R3                                     = vance_hub_golive_run();
check( 'run, overlap: a stale date with the layout already hub decides clear', 'clear' === $R3 );
check( 'run, overlap: it removes the date and does not purge, switch again or touch the done time', array_slice( writes(), count( $WRITES_AFTER_FIRST ) ) === array( 'remove:vance_hub_golive_at' ) && $GLOBALS['ACTIONS'] === $ACTIONS_AFTER_FIRST && $GLOBALS['MODS']['vance_hub_golive_done_at'] === $MODS_AFTER_FIRST['vance_hub_golive_done_at'] );

// Clear, with a future date.
reset_state();
$GLOBALS['MODS'] = array( 'vance_hub_golive_at' => $FUTURE, 'vance_home_layout' => 'hub' );
$R               = vance_hub_golive_run();
check( 'run, clear: the layout already hub with a future date returns clear and removes only the date', 'clear' === $R && writes() === array( 'remove:vance_hub_golive_at' ) && 'hub' === $GLOBALS['MODS']['vance_home_layout'] && $GLOBALS['ACTIONS'] === array() );

// Health data: never written, in any state.
foreach ( array( 'classic', 'hub' ) as $layout ) {
	foreach ( array( false, true ) as $state ) {
		reset_state();
		$GLOBALS['MODS'] = array( 'vance_hub_golive_at' => $PAST, 'vance_home_layout' => $layout, 'vance_hub_dash_checkin' => $state, 'vance_hub_dash_flare_plan' => $state );
		vance_hub_golive_run();
		$names = array_map( function ( $w ) { return $w[1]; }, $GLOBALS['WRITES'] );
		check( "health data: layout $layout, both saved " . ( $state ? 'on' : 'off' ) . ': neither switch is written', ! in_array( 'vance_hub_dash_checkin', $names, true ) && ! in_array( 'vance_hub_dash_flare_plan', $names, true ) );
		check( "health data: layout $layout, both saved " . ( $state ? 'on' : 'off' ) . ': both keep their saved value', $GLOBALS['MODS']['vance_hub_dash_checkin'] === $state && $GLOBALS['MODS']['vance_hub_dash_flare_plan'] === $state );
	}
}
reset_state();
$GLOBALS['MODS'] = array( 'vance_hub_golive_at' => $PAST, 'vance_home_layout' => 'classic' );
vance_hub_golive_run();
check( 'health data: with neither switch saved, a switch does not save them', ! array_key_exists( 'vance_hub_dash_checkin', $GLOBALS['MODS'] ) && ! array_key_exists( 'vance_hub_dash_flare_plan', $GLOBALS['MODS'] ) );
check( 'health data: after a switch, the check-in and the flare plan still read as off (their own default)', vance_hub_switch_default( 'vance_hub_dash_checkin' ) === false && vance_hub_switch_default( 'vance_hub_dash_flare_plan' ) === false );

// A date with seconds in an old save is still read.
reset_state();
$GLOBALS['MODS'] = array( 'vance_hub_golive_at' => '2020-01-01T00:00:30', 'vance_home_layout' => 'classic' );
check( 'run: a saved value with seconds is read as its minute', 'switch' === vance_hub_golive_run() );

// The site time zone: a date that is past in Tokyo and not yet past in New York.
$NOW      = time();
$LOCAL_NY = ( new DateTimeImmutable( '@' . ( $NOW + 7200 ), new DateTimeZone( 'UTC' ) ) )->setTimezone( new DateTimeZone( 'America/New_York' ) )->format( 'Y-m-d\TH:i' );
reset_state();
$GLOBALS['SITE_TZ'] = 'America/New_York';
$GLOBALS['MODS']    = array( 'vance_hub_golive_at' => $LOCAL_NY, 'vance_home_layout' => 'classic' );
check( 'run: a local time two hours ahead in the site zone waits', 'wait' === vance_hub_golive_run() );

section( '5. Hooks and the cron event' );

check( 'hooks: the run is on init at priority 20', in_array( 'vance_hub_golive_run', $GLOBALS['HOOKS']['init'][20] ?? array(), true ) );
check( 'hooks: the run is also the action of the cron event vance_hub_golive_event', in_array( 'vance_hub_golive_run', $GLOBALS['HOOKS']['vance_hub_golive_event'][10] ?? array(), true ) );
check( 'hooks: the event is planned again after the Customizer saves', in_array( 'vance_hub_golive_reschedule', $GLOBALS['HOOKS']['customize_save_after'][10] ?? array(), true ) );
check( 'hooks: the Customizer registration is on customize_register', in_array( 'vance_hub_golive_customize_register', $GLOBALS['HOOKS']['customize_register'][10] ?? array(), true ) );

reset_state();
$GLOBALS['MODS'] = array( 'vance_hub_golive_at' => $FUTURE );
vance_hub_golive_reschedule();
$EXPECTED = vance_hub_golive_timestamp( $FUTURE, new DateTimeZone( 'Europe/London' ) );
check( 'reschedule: a future date clears the old event, then plans one at the local time', $GLOBALS['CRON'] === array( array( 'clear', 'vance_hub_golive_event' ), array( 'schedule', 'vance_hub_golive_event', $EXPECTED ) ) );

reset_state();
$GLOBALS['MODS'] = array( 'vance_hub_golive_at' => '2099-07-01T09:30' );
vance_hub_golive_reschedule();
check( 'reschedule: the event time is the site-zone time (London summer: 08:30 UTC)', $GLOBALS['CRON'][1][2] === gmmktime( 8, 30, 0, 7, 1, 2099 ) );

reset_state();
$GLOBALS['MODS'] = array( 'vance_hub_golive_at' => $PAST );
vance_hub_golive_reschedule();
check( 'reschedule: a past date clears the old event and plans none', $GLOBALS['CRON'] === array( array( 'clear', 'vance_hub_golive_event' ) ) );

reset_state();
vance_hub_golive_reschedule();
check( 'reschedule: an empty date clears the old event and plans none', $GLOBALS['CRON'] === array( array( 'clear', 'vance_hub_golive_event' ) ) );

reset_state();
$GLOBALS['MODS'] = array( 'vance_hub_golive_at' => 'garbage' );
vance_hub_golive_reschedule();
check( 'reschedule: a value that is not a date clears the old event and plans none', $GLOBALS['CRON'] === array( array( 'clear', 'vance_hub_golive_event' ) ) );
check( 'reschedule: it writes no theme mod and fires no action', $GLOBALS['WRITES'] === array() && $GLOBALS['ACTIONS'] === array() );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
