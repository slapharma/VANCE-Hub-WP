<?php
/**
 * Tests for the check-in privacy exporter and eraser in inc/hub-dashboard-features.php:
 * vance_hub_checkin_all_meta_keys(), the two registration filters, vance_hub_checkin_export()
 * and vance_hub_checkin_erase().
 *
 * Same shape as hub-flare-plan.test.php. The stub block of hub-layout.test.php is evaluated
 * first, so both suites share one set of WordPress fakes. The stubs added here cover the
 * storage boundary only: user lookup by email, and user meta reads and deletes. Member data
 * is read and removed through $GLOBALS['UMETA'], which the checks inspect directly.
 *
 * WHAT IS COVERED
 *   1  registration: both filters, callbacks, friendly names, other registrants kept
 *   2  vance_hub_checkin_all_meta_keys(): exactly the five check-in keys
 *   3  export: unknown email, nothing stored, history rows, agreement item, damaged rows
 *   4  erase: unknown email, nothing stored, full removal of all five keys
 *   5  erase: a key whose delete fails read-back is reported retained, the rest still removed
 *   6  erase: other members and the flare plan keys are left alone
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

/* ---- stubs this suite adds ------------------------------------------------- */

// Only the 'email' lookup is used. Users come from $GLOBALS['USERS_BY_EMAIL'].
function get_user_by( $field, $value ) {
	return isset( $GLOBALS['USERS_BY_EMAIL'][ $value ] ) ? $GLOBALS['USERS_BY_EMAIL'][ $value ] : false;
}

// As WordPress does: a read of a key with no value gives ''.
function get_user_meta( $user_id, $key = '', $single = false ) {
	return isset( $GLOBALS['UMETA'][ $user_id ][ $key ] ) ? $GLOBALS['UMETA'][ $user_id ][ $key ] : '';
}

// DELETE_FAILS: a key name whose delete fails and removes nothing.
function delete_user_meta( $user_id, $key, $value = '' ) {
	$GLOBALS['WRITES'][] = 'delete_user_meta:' . $key;
	if ( isset( $GLOBALS['DELETE_FAILS'] ) && $key === $GLOBALS['DELETE_FAILS'] ) {
		return false;
	}
	unset( $GLOBALS['UMETA'][ $user_id ][ $key ] );
	return true;
}

// The assertion helpers. Same pair as hub-flare-plan.test.php.
$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

require_once $THEME . '/inc/hub-dashboard.php';
require_once $THEME . '/inc/hub-dashboard-features.php';

/* ---- fixed data -------------------------------------------------------------- */

$TS_1      = strtotime( '2026-11-10 12:00:00 UTC' ); // shown as 10 Nov 2026 12:00
$TS_2      = strtotime( '2026-11-11 08:30:00 UTC' ); // shown as 11 Nov 2026 08:30
$AGREED_AT = '2026-10-01 09:00:00';
$WITHDR_AT = '2026-11-01 08:00:00';

// Answers: pain 2 of 4, bowel 4 of 4, energy 1 of 4.
$ROW_1 = array( 'id' => 'ci_1', 'ts' => $TS_1, 'payload' => array( 'pain' => 2, 'bowel' => 4, 'tired' => 1, 'kind' => 'check-in' ) );
// Answers: pain 0 (a zero answer must still be exported), bowel 3, energy 4.
$ROW_2 = array( 'id' => 'ci_2', 'ts' => $TS_2, 'payload' => array( 'pain' => 0, 'bowel' => 3, 'tired' => 4, 'kind' => 'check-in' ) );

/** Reset every global this suite reads. */
function ci_arrange() {
	$GLOBALS['MODS']           = array( 'vance_home_layout' => 'hub', 'vance_hub_dash_checkin' => true );
	$GLOBALS['CAPS']           = array();
	$GLOBALS['LOGGED_IN']      = false;
	$GLOBALS['UMETA']          = array();
	$GLOBALS['WRITES']         = array();
	$GLOBALS['DELETE_FAILS']   = '';
	$GLOBALS['USERS_BY_EMAIL'] = array();
}

/** Register an email address as belonging to a member id. */
function ci_member( $email, $uid ) {
	$GLOBALS['USERS_BY_EMAIL'][ $email ] = (object) array( 'ID' => $uid );
}

/* ===================================================================== */
section( '1. registration: both filters, callbacks and friendly names' );

ci_arrange();
check( 'registration: the exporter is hooked to the personal data exporters filter', in_array( 'vance_hub_checkin_register_exporter', $GLOBALS['HOOKS']['wp_privacy_personal_data_exporters'][10] ?? array(), true ) );
check( 'registration: the eraser is hooked to the personal data erasers filter', in_array( 'vance_hub_checkin_register_eraser', $GLOBALS['HOOKS']['wp_privacy_personal_data_erasers'][10] ?? array(), true ) );
check( 'registration: the exporter is registered under vance-checkin with the export callback', vance_hub_checkin_register_exporter( array() )['vance-checkin']['callback'] === 'vance_hub_checkin_export' );
check( 'registration: the eraser is registered under vance-checkin with the erase callback', vance_hub_checkin_register_eraser( array() )['vance-checkin']['callback'] === 'vance_hub_checkin_erase' );
check( 'registration: the exporter has its friendly name', vance_hub_checkin_register_exporter( array() )['vance-checkin']['exporter_friendly_name'] === 'Daily check-ins' );
check( 'registration: the eraser has its friendly name', vance_hub_checkin_register_eraser( array() )['vance-checkin']['eraser_friendly_name'] === 'Daily check-ins' );
check( 'registration: the exporter keeps the exporters another plugin registered', array_keys( vance_hub_checkin_register_exporter( array( 'other' => array( 'callback' => 'x' ) ) ) ) === array( 'other', 'vance-checkin' ) );
check( 'registration: the eraser keeps the erasers another plugin registered', array_keys( vance_hub_checkin_register_eraser( array( 'other' => array( 'callback' => 'x' ) ) ) ) === array( 'other', 'vance-checkin' ) );

/* ===================================================================== */
section( '2. vance_hub_checkin_all_meta_keys(): exactly the five check-in keys' );

$KEYS = vance_hub_checkin_all_meta_keys();
sort( $KEYS );
$EXPECTED_KEYS = array(
	'_sla_consent_checkin',
	'_sla_consent_checkin_at',
	'_sla_consent_checkin_text',
	'_sla_consent_checkin_withdrawn_at',
	'_sla_weekly_check_in_history',
);
sort( $EXPECTED_KEYS );
check( 'keys: the list holds exactly the five check-in keys, no more and no less', $KEYS === $EXPECTED_KEYS );
check( 'keys: the list holds five entries', count( vance_hub_checkin_all_meta_keys() ) === 5 );

/* ===================================================================== */
section( '3. export: unknown email, nothing stored, history rows, agreement, damaged rows' );

ci_arrange();
check( 'export: an unknown email gives no data and is done', vance_hub_checkin_export( 'nobody@example.test' ) === array( 'data' => array(), 'done' => true ) );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
check( 'export: a member with nothing stored gets no data and is done', vance_hub_checkin_export( 'ruth@example.test' ) === array( 'data' => array(), 'done' => true ) );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( $ROW_1, $ROW_2 );
$EXP = vance_hub_checkin_export( 'ruth@example.test' );
check( 'export: two history rows give two items, one per row', is_array( $EXP['data'] ) && count( $EXP['data'] ) === 2 );
check( 'export: the first item is in the check-in group and has the row item id', ( $EXP['data'][0]['group_id'] ?? null ) === 'vance-checkin' && ( $EXP['data'][0]['item_id'] ?? null ) === 'vance-checkin-7-0' );
check( 'export: the first row holds its date and Pain, Bowel and Energy as n / 4', ( $EXP['data'][0]['data'] ?? null ) === array(
	array( 'name' => 'Date', 'value' => '10 Nov 2026 12:00' ),
	array( 'name' => 'Pain', 'value' => '2 / 4' ),
	array( 'name' => 'Bowel', 'value' => '4 / 4' ),
	array( 'name' => 'Energy', 'value' => '1 / 4' ),
) );
check( 'export: the second item has the row item id and holds its own answers', ( $EXP['data'][1]['item_id'] ?? null ) === 'vance-checkin-7-1' && ( $EXP['data'][1]['data'] ?? null ) === array(
	array( 'name' => 'Date', 'value' => '11 Nov 2026 08:30' ),
	array( 'name' => 'Pain', 'value' => '0 / 4' ),
	array( 'name' => 'Bowel', 'value' => '3 / 4' ),
	array( 'name' => 'Energy', 'value' => '4 / 4' ),
) );
check( 'export: the export is done in one page', $EXP['done'] === true );
check( 'export: a history of only check-in rows has no agreement item', count( $EXP['data'] ) === 2 && ( $EXP['data'][1]['group_id'] ?? null ) === 'vance-checkin' );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array_fill( 0, 120, $ROW_1 );
$EXP = vance_hub_checkin_export( 'ruth@example.test' );
check( 'export: a full history of 120 rows is exported whole, in one page', count( $EXP['data'] ) === 120 && $EXP['done'] === true );
check( 'export: the last of 120 rows keeps its row item id', ( $EXP['data'][119]['item_id'] ?? null ) === 'vance-checkin-7-119' );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_consent_checkin_at']   = $AGREED_AT;
$GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] = vance_hub_checkin_consent_text();
$GLOBALS['UMETA'][7]['_sla_consent_checkin_withdrawn_at'] = $WITHDR_AT;
$EXP = vance_hub_checkin_export( 'ruth@example.test' );
check( 'agreement: a member with an agreement gets one agreement item', is_array( $EXP['data'] ) && count( $EXP['data'] ) === 1 );
check( 'agreement: the item is in the agreement group with the member id', ( $EXP['data'][0]['group_id'] ?? null ) === 'vance-checkin-agreement' && ( $EXP['data'][0]['item_id'] ?? null ) === 'vance-checkin-agreement-7' );
check( 'agreement: the item holds the agreed time, the wording and the withdrawn time', ( $EXP['data'][0]['data'] ?? null ) === array(
	array( 'name' => 'Agreed on', 'value' => $AGREED_AT ),
	array( 'name' => 'Agreement wording', 'value' => vance_hub_checkin_consent_text() ),
	array( 'name' => 'Withdrawn on', 'value' => $WITHDR_AT ),
) );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( $ROW_1, $ROW_2 );
$GLOBALS['UMETA'][7]['_sla_consent_checkin_at']   = $AGREED_AT;
$GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] = vance_hub_checkin_consent_text();
$EXP = vance_hub_checkin_export( 'ruth@example.test' );
check( 'agreement: the agreement item comes after the history rows', count( $EXP['data'] ) === 3 && ( $EXP['data'][2]['group_id'] ?? null ) === 'vance-checkin-agreement' );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_consent_checkin_withdrawn_at'] = $WITHDR_AT;
$EXP = vance_hub_checkin_export( 'ruth@example.test' );
check( 'agreement: a withdrawal with no agreement time still gives an item, holding only the withdrawn time', ( $EXP['data'][0]['data'] ?? null ) === array( array( 'name' => 'Withdrawn on', 'value' => $WITHDR_AT ) ) );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_consent_checkin_at']   = '';
$GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] = '';
$GLOBALS['UMETA'][7]['_sla_consent_checkin_withdrawn_at'] = '';
check( 'no empty agreement item: blank agreement keys and no history give no data', vance_hub_checkin_export( 'ruth@example.test' ) === array( 'data' => array(), 'done' => true ) );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( $ROW_1, $ROW_2 );
$GLOBALS['UMETA'][7]['_sla_consent_checkin_at']   = '';
$GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] = '';
check( 'no empty agreement item: blank agreement keys with history give two items and no agreement item', count( vance_hub_checkin_export( 'ruth@example.test' )['data'] ) === 2 );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array(
	$ROW_1,
	'junk',
	array( 'ts' => $TS_1, 'payload' => 'not a list' ),
	array( 'payload' => array( 'pain' => 1 ) ),
	$ROW_2,
);
$EXP = vance_hub_checkin_export( 'ruth@example.test' );
check( 'damaged rows: the damaged rows are skipped and the two good rows are exported', is_array( $EXP['data'] ) && count( $EXP['data'] ) === 2 );
check( 'damaged rows: the good rows keep their own dates after the damaged ones are skipped', ( $EXP['data'][0]['data'][0] ?? null ) === array( 'name' => 'Date', 'value' => '10 Nov 2026 12:00' ) && ( $EXP['data'][1]['data'][0] ?? null ) === array( 'name' => 'Date', 'value' => '11 Nov 2026 08:30' ) );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( array( 'id' => 'ci_3', 'ts' => $TS_1, 'payload' => array( 'pain' => 'x', 'bowel' => 4, 'tired' => 1 ) ) );
$EXP = vance_hub_checkin_export( 'ruth@example.test' );
check( 'damaged answers: a text answer is left out of the row and the other answers are kept', ( $EXP['data'][0]['data'] ?? null ) === array(
	array( 'name' => 'Date', 'value' => '10 Nov 2026 12:00' ),
	array( 'name' => 'Bowel', 'value' => '4 / 4' ),
	array( 'name' => 'Energy', 'value' => '1 / 4' ),
) );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( array( 'id' => 'ci_4', 'ts' => $TS_1, 'payload' => array( 'bowel' => 4, 'tired' => 1 ) ) );
$EXP = vance_hub_checkin_export( 'ruth@example.test' );
check( 'damaged answers: a missing answer is left out of the row', ( $EXP['data'][0]['data'] ?? null ) === array(
	array( 'name' => 'Date', 'value' => '10 Nov 2026 12:00' ),
	array( 'name' => 'Bowel', 'value' => '4 / 4' ),
	array( 'name' => 'Energy', 'value' => '1 / 4' ),
) );

/* ===================================================================== */
section( '4. erase: unknown email, nothing stored, full removal' );

ci_arrange();
check( 'erase: an unknown email removes nothing and retains nothing', vance_hub_checkin_erase( 'nobody@example.test' ) === array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true ) );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$ERA = vance_hub_checkin_erase( 'ruth@example.test' );
check( 'erase: a member with nothing stored removes nothing and retains nothing', $ERA['items_removed'] === false && $ERA['items_retained'] === false && $ERA['messages'] === array() );
check( 'erase: a member with nothing stored sends no delete at all', $GLOBALS['WRITES'] === array() );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history']              = array( $ROW_1, $ROW_2 );
$GLOBALS['UMETA'][7]['_sla_consent_checkin']                      = '1';
$GLOBALS['UMETA'][7]['_sla_consent_checkin_at']                   = $AGREED_AT;
$GLOBALS['UMETA'][7]['_sla_consent_checkin_text']                 = vance_hub_checkin_consent_text();
$GLOBALS['UMETA'][7]['_sla_consent_checkin_withdrawn_at']         = $WITHDR_AT;
$ERA = vance_hub_checkin_erase( 'ruth@example.test' );
check( 'erase: the check-in history is removed', ! isset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) );
check( 'erase: the agreement flag is removed', ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin'] ) );
check( 'erase: the agreement time is removed', ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'] ) );
check( 'erase: the agreement wording is removed', ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] ) );
check( 'erase: the withdrawal time is removed', ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_withdrawn_at'] ) );
check( 'erase: a full removal reports items removed and none retained, with no messages', $ERA === array( 'items_removed' => true, 'items_retained' => false, 'messages' => array(), 'done' => true ) );

/* ===================================================================== */
section( '5. erase: a key whose delete fails read-back is reported retained' );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history']      = array( $ROW_1 );
$GLOBALS['UMETA'][7]['_sla_consent_checkin']              = '1';
$GLOBALS['UMETA'][7]['_sla_consent_checkin_at']           = $AGREED_AT;
$GLOBALS['UMETA'][7]['_sla_consent_checkin_text']         = vance_hub_checkin_consent_text();
$GLOBALS['UMETA'][7]['_sla_consent_checkin_withdrawn_at'] = $WITHDR_AT;
$GLOBALS['DELETE_FAILS'] = '_sla_weekly_check_in_history';
$ERA = vance_hub_checkin_erase( 'ruth@example.test' );
check( 'retained: a history that cannot be deleted is reported as retained', $ERA['items_retained'] === true );
check( 'retained: the message names the history key', $ERA['messages'] === array( 'The check-in record _sla_weekly_check_in_history could not be deleted.' ) );
check( 'retained: the history is still there after the failed delete', isset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) );
check( 'retained: the other four keys are still removed, and the run reports items removed', ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin'] ) && ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'] ) && ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_text'] ) && ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_withdrawn_at'] ) && $ERA['items_removed'] === true );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history']      = array( $ROW_1 );
$GLOBALS['UMETA'][7]['_sla_consent_checkin']              = '1';
$GLOBALS['UMETA'][7]['_sla_consent_checkin_at']           = $AGREED_AT;
$GLOBALS['DELETE_FAILS'] = '_sla_consent_checkin';
$ERA = vance_hub_checkin_erase( 'ruth@example.test' );
check( 'retained: an agreement flag that cannot be deleted is reported as retained, with its key named', $ERA['items_retained'] === true && $ERA['messages'] === array( 'The check-in record _sla_consent_checkin could not be deleted.' ) );
check( 'retained: the agreement flag is still set, and the history and time are still removed', ( $GLOBALS['UMETA'][7]['_sla_consent_checkin'] ?? null ) === '1' && ! isset( $GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] ) && ! isset( $GLOBALS['UMETA'][7]['_sla_consent_checkin_at'] ) );

/* ===================================================================== */
section( '6. erase: other members and the flare plan are left alone' );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history'] = array( $ROW_1 );
$GLOBALS['UMETA'][7]['_sla_consent_checkin']         = '1';
$GLOBALS['UMETA'][8] = array(
	'_sla_weekly_check_in_history' => array( $ROW_2 ),
	'_sla_consent_checkin'         => '1',
	'_sla_consent_checkin_at'      => $AGREED_AT,
	'_sla_consent_checkin_text'    => vance_hub_checkin_consent_text(),
);
$OTHER_BEFORE = $GLOBALS['UMETA'][8];
vance_hub_checkin_erase( 'ruth@example.test' );
check( 'other members: another member\'s check-in data is not changed by the erase', $GLOBALS['UMETA'][8] === $OTHER_BEFORE );

ci_arrange();
ci_member( 'ruth@example.test', 7 );
$FLARE = array( 'updated' => $TS_1, 'signs' => 'Tight belly', 'actions' => 'Fluids', 'contacts' => 'Nurse' );
$GLOBALS['UMETA'][7]['_sla_weekly_check_in_history']      = array( $ROW_1 );
$GLOBALS['UMETA'][7]['_sla_consent_checkin']              = '1';
$GLOBALS['UMETA'][7]['_sla_flare_plan']                   = $FLARE;
$GLOBALS['UMETA'][7]['_sla_consent_flare_plan']           = '1';
$GLOBALS['UMETA'][7]['_sla_consent_flare_plan_at']        = $AGREED_AT;
$GLOBALS['UMETA'][7]['_sla_consent_flare_plan_text']      = 'Flare wording.';
vance_hub_checkin_erase( 'ruth@example.test' );
check( 'flare plan: the flare plan is still stored after a check-in erase', ( $GLOBALS['UMETA'][7]['_sla_flare_plan'] ?? null ) === $FLARE );
check( 'flare plan: the flare plan agreement is still stored after a check-in erase', ( $GLOBALS['UMETA'][7]['_sla_consent_flare_plan'] ?? null ) === '1' && ( $GLOBALS['UMETA'][7]['_sla_consent_flare_plan_at'] ?? null ) === $AGREED_AT && ( $GLOBALS['UMETA'][7]['_sla_consent_flare_plan_text'] ?? null ) === 'Flare wording.' );
check( 'flare plan: no flare plan key is deleted by the check-in erase', preg_grep( '/_sla_flare_plan|_sla_consent_flare_plan/', $GLOBALS['WRITES'] ) === array() );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . ' FAILED of ' . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK - $PASS checks passed.\n";
