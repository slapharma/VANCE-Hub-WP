<?php
/**
 * Tests for inc/hub-data-manage.php (plain PHP, no WordPress).
 *
 *   1  groups and what counts as data
 *   2  erase: data gone, agreement withdrawn, other users untouched
 *   3  the POST handler: gating, nonce, confirm tick, one group, all
 *   4  the panel: forms only where there is data, escaping of codes
 */
define( 'ABSPATH', __DIR__ . '/' );
error_reporting( E_ALL );
set_error_handler( function ( $no, $str, $file, $line ) { throw new ErrorException( $str, 0, $no, $file, $line ); } );

$PASS = 0;
$FAIL = array();
function check( $name, $ok ) { global $PASS, $FAIL; if ( $ok ) { $PASS++; } else { $FAIL[] = $name; } }
function section( $t ) { echo "\n-- $t\n"; }

$GLOBALS['UMETA']      = array();
$GLOBALS['REDIRECT']   = null;
$GLOBALS['LOCKS']      = array();
$GLOBALS['LOCK_FAILS'] = false;
$GLOBALS['ACTIVE']     = true;
$GLOBALS['UID']        = 7;
$GLOBALS['STUCK']      = '';

class Redirected extends Exception {}

function __( $s ) { return $s; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $s ) { return esc_html( $s ); }
function esc_attr( $s ) { return esc_html( $s ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $s ) ); }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function wp_unslash( $s ) { return $s; }
function wp_create_nonce( $a ) { return 'nonce-' . $a; }
function wp_verify_nonce( $n, $a ) { return $n === 'nonce-' . $a; }
function is_user_logged_in() { return $GLOBALS['UID'] > 0; }
function get_current_user_id() { return $GLOBALS['UID']; }
function current_time( $t ) { return '2026-11-26 10:00:00'; }
function remove_query_arg( $k ) { return 'https://example.test/dashboard/?tab=health-profile&hsub=data'; }
function add_query_arg( $k, $v, $url ) { return $url . '&' . $k . '=' . $v; }
function wp_safe_redirect( $url ) { $GLOBALS['REDIRECT'] = $url; throw new Redirected( $url ); }
function add_action() {}
function vance_hub_dashboard_active() { return $GLOBALS['ACTIVE']; }
function get_user_meta( $u, $k, $single = false ) { return isset( $GLOBALS['UMETA'][ $u ][ $k ] ) ? $GLOBALS['UMETA'][ $u ][ $k ] : ''; }
function update_user_meta( $u, $k, $v ) { $GLOBALS['UMETA'][ $u ][ $k ] = $v; return true; }
function delete_user_meta( $u, $k ) { if ( $GLOBALS['STUCK'] === $k ) { return false; } unset( $GLOBALS['UMETA'][ $u ][ $k ] ); return true; }
function vance_known_tool_slugs() { return array( 'malnutrition-calculator', 'ibd-recipes' ); }
function vance_hub_checkin_meta_key() { return '_sla_weekly_check_in_history'; }
function vance_hub_flare_plan_meta_key() { return '_sla_flare_plan'; }
function vance_hub_flare_log_meta_key() { return '_sla_flare_log'; }
function vance_hub_checkin_consent_keys( $base = 'checkin' ) { return array( '_sla_consent_' . $base, '_sla_consent_' . $base . '_at', '_sla_consent_' . $base . '_text' ); }
function vance_hub_checkin_lock( $uid ) { if ( $GLOBALS['LOCK_FAILS'] ) { return false; } $GLOBALS['LOCKS'][] = 'lock'; return true; }
function vance_hub_checkin_unlock( $uid ) { $GLOBALS['LOCKS'][] = 'unlock'; }

/** A fake $wpdb that finds usermeta keys by prefix in the fake store. */
class Fake_Wpdb {
	public $usermeta = 'wp_usermeta';
	public $last = null;
	public function esc_like( $s ) { return $s; }
	public function prepare( $q, $uid, $like ) { $this->last = array( (int) $uid, $like ); return 'Q'; }
	public function get_col( $q ) {
		list( $uid, $like ) = $this->last;
		$prefix = rtrim( $like, '%' );
		$out = array();
		foreach ( isset( $GLOBALS['UMETA'][ $uid ] ) ? array_keys( $GLOBALS['UMETA'][ $uid ] ) : array() as $k ) {
			if ( 0 === strpos( $k, $prefix ) ) { $out[] = $k; }
		}
		return $out;
	}
}
$GLOBALS['wpdb'] = new Fake_Wpdb();

require __DIR__ . '/../wp-content/themes/vance-health-hub/inc/hub-data-manage.php';

function seed( $uid ) {
	$GLOBALS['UMETA'][ $uid ] = array(
		'_sla_healthcare_quiz_results'         => array( 'age' => '40' ),
		'_sla_clinical_profile'                => array( 'weight' => '70' ),
		'_sla_malnutrition_calculator_history' => array( array( 'ts' => 1, 'payload' => array( 'score' => 2 ) ) ),
		'_sla_ibd_recipes_history'             => array( array( 'ts' => 1, 'payload' => array( 'x' => 1 ) ) ),
		'_sla_weekly_check_in_history'         => array( array( 'ts' => 1, 'payload' => array( 'pain' => 1 ) ) ),
		'_sla_consent_checkin'                 => '1',
		'_sla_consent_checkin_at'              => '2026-11-01 09:00:00',
		'_sla_consent_checkin_text'            => 'I agree.',
		'_sla_flare_plan'                      => array( 'signs' => 'x' ),
		'_sla_consent_flare_plan'              => '1',
		'_sla_notes'                           => 'a note',
	);
}
function post( $do, $confirm = '1', $nonce = 'nonce-vance_hub_manage_data' ) {
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_POST = array( 'vance_hub_data_nonce' => $nonce, 'vance_hub_data_do' => $do );
	if ( null !== $confirm ) {
		$_POST['vhd_confirm'] = $confirm;
	}
	$GLOBALS['REDIRECT'] = null;
	try {
		vance_hub_data_handle_post();
	} catch ( Redirected $e ) {
	}
	return $GLOBALS['REDIRECT'];
}
function code() { return $GLOBALS['REDIRECT'] ? substr( strrchr( $GLOBALS['REDIRECT'], '=' ), 1 ) : null; }
function count_meta( $uid ) { return count( $GLOBALS['UMETA'][ $uid ] ); }

section( '1. Groups and what counts as data' );
$G = vance_hub_data_groups();
check( 'groups: the six groups, in order', array_keys( $G ) === array( 'assessment', 'details', 'malnutrition', 'tools', 'checkins', 'flare' ) );
check( 'groups: the flare group holds the plan and the flare log', in_array( '_sla_flare_plan', $G['flare']['keys'], true ) && in_array( '_sla_flare_log', $G['flare']['keys'], true ) );
check( 'groups: malnutrition uses the tool history key', $G['malnutrition']['keys'] === array( '_sla_malnutrition_calculator_history' ) );
check( 'groups: other tools hold only the non-malnutrition slugs', $G['tools']['keys'] === array( '_sla_ibd_recipes_history' ) );
seed( 7 );
$with_data = 0;
foreach ( array_keys( $G ) as $s ) {
	$with_data += vance_hub_data_has( 7, $s ) ? 1 : 0;
}
check( 'has: every group has data after the seed', 6 === $with_data );
check( 'has: a member with no meta has no data', ! vance_hub_data_has( 8, 'assessment' ) );
check( 'has: an unknown group has no data', ! vance_hub_data_has( 7, 'bogus' ) );
$GLOBALS['UMETA'][9]['_sla_clinical_profile'] = array();
check( 'has: an empty array is not data', ! vance_hub_data_has( 9, 'details' ) );

$GLOBALS['UMETA'][10] = array( '_sla_consent_checkin' => '1' );
check( 'has: an agreement still in force counts, even with no answers left', vance_hub_data_has( 10, 'checkins' ) && ! vance_hub_data_has( 10, 'flare' ) );
vance_hub_data_erase( 10, 'checkins' );
check( 'erase: that stale agreement is withdrawn', ! isset( $GLOBALS['UMETA'][10]['_sla_consent_checkin'] ) && isset( $GLOBALS['UMETA'][10]['_sla_consent_checkin_withdrawn_at'] ) && ! vance_hub_data_has( 10, 'checkins' ) );

$GLOBALS['UMETA'][11] = array( '_sla_calc_results_malnutrition' => array( 'score' => 3 ), '_sla_calc_results_other' => array( 'x' => 1 ), '_sla_notes' => 'n' );
$GLOBALS['UMETA'][12] = array( '_sla_calc_results_malnutrition' => array( 'score' => 9 ) );
check( 'calc results: the older save path counts as malnutrition data', vance_hub_data_has( 11, 'malnutrition' ) );
check( 'calc results: the keys are read for this member only', array( '_sla_malnutrition_calculator_history', '_sla_calc_results_malnutrition', '_sla_calc_results_other' ) === vance_hub_data_keys( 11, 'malnutrition' ) );
vance_hub_data_erase( 11, 'malnutrition' );
check( 'calc results: erase removes every _sla_calc_results_ key of the member and nothing else', ! isset( $GLOBALS['UMETA'][11]['_sla_calc_results_malnutrition'] ) && ! isset( $GLOBALS['UMETA'][11]['_sla_calc_results_other'] ) && 'n' === $GLOBALS['UMETA'][11]['_sla_notes'] );
check( 'calc results: another member keeps theirs', isset( $GLOBALS['UMETA'][12]['_sla_calc_results_malnutrition'] ) );

section( '2. Erase' );
seed( 7 );
seed( 8 );
check( 'erase: assessment returns true', true === vance_hub_data_erase( 7, 'assessment' ) );
check( 'erase: the assessment answers are gone', ! isset( $GLOBALS['UMETA'][7]['_sla_healthcare_quiz_results'] ) );
check( 'erase: the other groups stay', isset( $GLOBALS['UMETA'][7]['_sla_clinical_profile'] ) && isset( $GLOBALS['UMETA'][7]['_sla_malnutrition_calculator_history'] ) );
check( 'erase: another member keeps everything', isset( $GLOBALS['UMETA'][8]['_sla_healthcare_quiz_results'] ) && 11 === count_meta( 8 ) );
vance_hub_data_erase( 7, 'checkins' );
check( 'erase: check-in history gone, agreement flag gone', ! isset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) && ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin'] ) );
check( 'erase: the withdrawal time is stored and the old agreement record stays', '2026-11-26 10:00:00' === $GLOBALS['UMETA'][7]['_sla_consent_checkin_withdrawn_at'] && '2026-11-01 09:00:00' === $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'] );
vance_hub_data_erase( 7, 'flare' );
check( 'erase: the flare plan and its flag are gone', ! isset( $GLOBALS['UMETA'][7]['_sla_flare_plan'] ) && ! isset( $GLOBALS['UMETA'][7]['_sla_consent_flare_plan'] ) );
check( 'erase: an unknown group returns false and deletes nothing', false === vance_hub_data_erase( 7, 'bogus' ) && isset( $GLOBALS['UMETA'][7]['_sla_notes'] ) );
check( 'erase: user 0 returns false', false === vance_hub_data_erase( 0, 'details' ) );
seed( 7 );
$GLOBALS['STUCK'] = '_sla_clinical_profile';
check( 'erase: a delete that does not stick returns false (read back from the store)', false === vance_hub_data_erase( 7, 'details' ) );
$GLOBALS['STUCK'] = '';
seed( 7 );
foreach ( array_keys( vance_hub_data_groups() ) as $s ) {
	vance_hub_data_erase( 7, $s );
}
check( 'erase: all six groups together leave the notes alone', 'a note' === $GLOBALS['UMETA'][7]['_sla_notes'] );

section( '3. The POST handler' );
seed( 7 );
seed( 8 );
$_SERVER['REQUEST_METHOD'] = 'GET';
$GLOBALS['REDIRECT']       = null;
vance_hub_data_handle_post();
check( 'gate: a GET does nothing', null === $GLOBALS['REDIRECT'] );
$GLOBALS['UID'] = 0;
post( 'all' );
check( 'gate: a logged-out visitor deletes nothing and is not redirected', null === $GLOBALS['REDIRECT'] && 11 === count_meta( 7 ) );
$GLOBALS['UID']    = 7;
$GLOBALS['ACTIVE'] = false;
post( 'all' );
check( 'gate: with the hub dashboard off nothing happens', null === $GLOBALS['REDIRECT'] && 11 === count_meta( 7 ) );
$GLOBALS['ACTIVE'] = true;
post( 'all', '1', 'bad' );
check( 'nonce: a bad nonce redirects with expired and deletes nothing', 'expired' === code() && 11 === count_meta( 7 ) );
post( 'all', null );
check( 'confirm: no tick gives "confirm" and deletes nothing', 'confirm' === code() && 11 === count_meta( 7 ) );
post( 'all', '0' );
check( 'confirm: a tick of 0 does not count', 'confirm' === code() && 11 === count_meta( 7 ) );
post( 'bogus' );
check( 'invalid: an unknown group gives "invalid" and deletes nothing', 'invalid' === code() && 11 === count_meta( 7 ) );
post( 'details' );
check( 'one group: details deleted, result "deleted"', 'deleted' === code() && ! isset( $GLOBALS['UMETA'][7]['_sla_clinical_profile'] ) && isset( $GLOBALS['UMETA'][7]['_sla_healthcare_quiz_results'] ) );
check( 'one group: the redirect stays on the data tab', false !== strpos( $GLOBALS['REDIRECT'], 'hsub=data' ) );
check( 'lock: taken once and freed once', array( 'lock', 'unlock' ) === $GLOBALS['LOCKS'] );
post( 'details' );
check( 'nothing: deleting an empty group says "nothing"', 'nothing' === code() );
$GLOBALS['LOCKS'] = array();
post( 'all' );
check( 'all: every group of this member is gone', ! vance_hub_data_has( 7, 'assessment' ) && ! vance_hub_data_has( 7, 'malnutrition' ) && ! vance_hub_data_has( 7, 'tools' ) && ! vance_hub_data_has( 7, 'checkins' ) && ! vance_hub_data_has( 7, 'flare' ) && 'deleted' === code() );
check( 'all: another member is untouched', 11 === count_meta( 8 ) );
check( 'all: the member notes are untouched', 'a note' === $GLOBALS['UMETA'][7]['_sla_notes'] );
seed( 7 );
$GLOBALS['LOCK_FAILS'] = true;
post( 'all' );
check( 'lock: when the lock cannot be taken nothing is deleted and the result is "kept"', 'kept' === code() && 11 === count_meta( 7 ) );
$GLOBALS['LOCK_FAILS'] = false;
$GLOBALS['STUCK']      = '_sla_flare_plan';
$GLOBALS['LOCKS']      = array();
post( 'all' );
check( 'kept: one delete that does not stick gives "kept", and the lock is still freed', 'kept' === code() && 'unlock' === end( $GLOBALS['LOCKS'] ) );
$GLOBALS['STUCK'] = '';

section( '4. The panel' );
seed( 7 );
$H = vance_hub_data_panel_html( 7, '' );
check( 'panel: one delete form for each of the six groups plus the all form', 7 === substr_count( $H, 'name="vance_hub_data_do"' ) );
check( 'panel: every form has a required confirm box and a nonce', 7 === substr_count( $H, 'name="vhd_confirm" value="1" required' ) && 7 === substr_count( $H, 'name="vance_hub_data_nonce" value="nonce-vance_hub_manage_data"' ) );
check( 'panel: no script output', false === strpos( $H, '<script' ) );
$GLOBALS['UMETA'][7] = array( '_sla_clinical_profile' => array( 'a' => 1 ) );
$H = vance_hub_data_panel_html( 7, '' );
check( 'panel: only the group with data has a form, plus the all form', 2 === substr_count( $H, 'name="vance_hub_data_do"' ) && false !== strpos( $H, 'value="details"' ) && false === strpos( $H, 'value="flare"' ) );
check( 'panel: groups with no data read "Nothing saved"', 5 === substr_count( $H, 'Nothing saved' ) );
$GLOBALS['UMETA'][7] = array();
$H = vance_hub_data_panel_html( 7, '' );
check( 'panel: with no data there are no forms at all', false === strpos( $H, '<form' ) );
check( 'flash: deleted is a status line with no warning class', false !== strpos( vance_hub_data_panel_html( 7, 'deleted' ), '<p class="vhd-flash" role="status">Your data is deleted.</p>' ) );
check( 'flash: confirm is a warning line', false !== strpos( vance_hub_data_panel_html( 7, 'confirm' ), 'vhd-flash--warn' ) );
check( 'flash: an unknown code prints no message', false === strpos( vance_hub_data_panel_html( 7, '<script>' ), 'role="status"' ) );
$_GET = array( 'vance_data' => 'deleted' );
check( 'request: a known code is read', 'deleted' === vance_hub_data_flash_from_request() );
$_GET = array( 'vance_data' => '<b>x</b>' );
check( 'request: an unknown code is dropped', '' === vance_hub_data_flash_from_request() );
$_GET = array( 'vance_data' => array( 'a' ) );
check( 'request: an array is dropped', '' === vance_hub_data_flash_from_request() );

echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . ' FAILED of ' . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) {
		echo "  x  $f\n";
	}
	exit( 1 );
}
echo "OK - $PASS checks passed.\n";
