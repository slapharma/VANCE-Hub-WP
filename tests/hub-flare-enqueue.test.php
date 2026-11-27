<?php
/**
 * When the flare plan script loads on the dashboard.
 *
 * The print buttons of the flare plan card are hidden until
 * assets/js/hub-flare-plan.js shows them. The script must load when the card is
 * available, and also for the read-only card (switch off, plan stored). It must
 * not load when the member has no card.
 *
 * Plain PHP, no WordPress. The real vance_hub_dashboard_enqueue_assets() is
 * lifted from inc/hub-dashboard.php and run with stubs.
 *
 * Run: cd tests; php hub-flare-enqueue.test.php   (exit 1 when a check fails)
 */

$src = file_get_contents( dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub/inc/hub-dashboard.php' );
if ( 1 !== preg_match( '/function vance_hub_dashboard_enqueue_assets\(\) \{.*?\r?\n\}\r?\n/s', (string) $src, $m ) ) {
	fwrite( STDERR, "could not lift vance_hub_dashboard_enqueue_assets() from hub-dashboard.php\n" );
	exit( 2 );
}

$S = array( 'available' => false, 'plan' => null, 'active' => true, 'scripts' => array() );
function is_page( $p = '' ) { return true; }
function is_page_template( $t = '' ) { return true; }
function is_user_logged_in() { return true; }
function vance_hub_dashboard_active() { return $GLOBALS['S']['active']; }
function get_template_directory_uri() { return 'https://example.test/t'; }
function get_template_directory() { return __DIR__; }
function wp_enqueue_style() {}
function wp_enqueue_script( $handle ) { $GLOBALS['S']['scripts'][] = $handle; }
function get_current_user_id() { return 7; }
function vance_hub_flare_plan_available() { return $GLOBALS['S']['available']; }
function vance_hub_flare_plan_get( $uid ) { return 7 === $uid ? $GLOBALS['S']['plan'] : null; }
eval( $m[0] );

$PASS = 0;
$FAIL = array();
function run_case( $label, $available, $plan, $active, $expect ) {
	global $S, $PASS, $FAIL;
	$S = array( 'available' => $available, 'plan' => $plan, 'active' => $active, 'scripts' => array() );
	vance_hub_dashboard_enqueue_assets();
	$got = in_array( 'vance-hub-flare-plan', $S['scripts'], true );
	if ( $got === $expect ) {
		$PASS++;
		return;
	}
	$FAIL[] = $label . ' (script loaded: ' . ( $got ? 'yes' : 'no' ) . ')';
}

run_case( 'card available, no plan: the script loads', true, null, true, true );
run_case( 'card available, stored plan: the script loads', true, array( 'updated' => 1 ), true, true );
run_case( 'switch off, stored plan (read-only card): the script loads', false, array( 'updated' => 1 ), true, true );
run_case( 'switch off, no plan: the script does not load', false, null, true, false );
run_case( 'hub dashboard off, stored plan: the script does not load', false, array( 'updated' => 1 ), false, false );

if ( $FAIL ) {
	echo count( $FAIL ) . ' FAILED of ' . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK - $PASS checks passed.\n";
