<?php
/**
 * inc/dashboard-url.php — vance_dashboard_link(): the signed-out /dashboard/
 * link goes straight to /login/ with redirect_to, and everything else passes
 * through unchanged.
 *
 * Only this site's bare /dashboard/ may be rewritten. A /dashboard/ on another
 * host must pass through.
 *
 * Same shape as the other suites in this directory: hand-written WP stubs, no
 * WordPress, no database.
 */

define( 'ABSPATH', true );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

/* ---- WordPress stubs --------------------------------------------------- */

// Switch the signed-in state per test: $LOGGED_IN = true / false.
$LOGGED_IN = false;
function is_user_logged_in() { global $LOGGED_IN; return $LOGGED_IN; }

function home_url( $path = '' ) { return 'https://vancehealthhub.co.uk' . $path; }

function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }

// Matches WordPress: add_query_arg() does not encode the value. wp_login_url()
// passes urlencode( $redirect ) in itself, so the caller encodes.
function add_query_arg( $k, $v, $url ) {
	return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . $k . '=' . $v;
}

require_once $THEME . '/inc/dashboard-url.php';

/* ---- the runner --------------------------------------------------------- */
$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }


/* ===================================================================== */
section( '1. signed-out visitor, bare /dashboard/' );
/* ===================================================================== */

$LOGGED_IN = false;
$out = vance_dashboard_link( '/dashboard/' );
check( '1a  bare /dashboard/ becomes the login page with redirect_to the dashboard',
	$out === 'https://vancehealthhub.co.uk/login/?redirect_to=https%3A%2F%2Fvancehealthhub.co.uk%2Fdashboard%2F' );

$LOGGED_IN = false;
$out = vance_dashboard_link( 'https://vancehealthhub.co.uk/dashboard/' );
check( '1b  /dashboard/ with this site host becomes the same login URL',
	$out === 'https://vancehealthhub.co.uk/login/?redirect_to=https%3A%2F%2Fvancehealthhub.co.uk%2Fdashboard%2F' );

$LOGGED_IN = false;
$out = vance_dashboard_link();
check( '1c  no argument uses the default /dashboard/ and becomes the login URL',
	$out === 'https://vancehealthhub.co.uk/login/?redirect_to=https%3A%2F%2Fvancehealthhub.co.uk%2Fdashboard%2F' );


/* ===================================================================== */
section( '2. signed-in visitor' );
/* ===================================================================== */

$LOGGED_IN = true;
$out = vance_dashboard_link( '/dashboard/' );
check( '2a  signed-in bare /dashboard/ is returned unchanged',
	$out === '/dashboard/' );

$LOGGED_IN = true;
$out = vance_dashboard_link( 'https://vancehealthhub.co.uk/dashboard/' );
check( '2b  signed-in full URL to this site dashboard is returned unchanged',
	$out === 'https://vancehealthhub.co.uk/dashboard/' );


/* ===================================================================== */
section( '3. signed-out visitor, other URLs pass through unchanged' );
/* ===================================================================== */

$LOGGED_IN = false;
$out = vance_dashboard_link( '/dashboard' );
check( '3a  /dashboard with no trailing slash is returned unchanged',
	$out === '/dashboard' );

$LOGGED_IN = false;
$out = vance_dashboard_link( '/dashboard/?tab=ai-chats' );
check( '3b  /dashboard/ with a query string is returned unchanged',
	$out === '/dashboard/?tab=ai-chats' );

$LOGGED_IN = false;
$out = vance_dashboard_link( 'https://vancehealthhub.co.uk/dashboard/?tab=ai-chats' );
check( '3c  full /dashboard/ URL with a query string is returned unchanged',
	$out === 'https://vancehealthhub.co.uk/dashboard/?tab=ai-chats' );

$LOGGED_IN = false;
$out = vance_dashboard_link( '/dashboard/settings/' );
check( '3d  a deeper path under /dashboard/ is returned unchanged',
	$out === '/dashboard/settings/' );

$LOGGED_IN = false;
$out = vance_dashboard_link( '/about-us/' );
check( '3e  an unrelated path is returned unchanged',
	$out === '/about-us/' );

$LOGGED_IN = false;
$out = vance_dashboard_link( '' );
check( '3f  an empty string is returned unchanged',
	$out === '' );


/* ===================================================================== */
section( '4. /dashboard/ on another host must not be rewritten' );
/* ===================================================================== */

// Only this site's dashboard should change. The source reads only the path
// of the URL, so these two currently fail.
$LOGGED_IN = false;
$out = vance_dashboard_link( 'https://example.com/dashboard/' );
check( '4a  signed-out https://example.com/dashboard/ is returned unchanged (BUG if this fails)',
	$out === 'https://example.com/dashboard/' );

$LOGGED_IN = false;
$out = vance_dashboard_link( '//example.com/dashboard/' );
check( '4b  signed-out protocol-relative //example.com/dashboard/ is returned unchanged (BUG if this fails)',
	$out === '//example.com/dashboard/' );


/* ===================================================================== */
section( '5. non-string input is returned as given' );
/* ===================================================================== */

$LOGGED_IN = false;
$out = vance_dashboard_link( null );
check( '5a  null is returned unchanged',
	$out === null );

$LOGGED_IN = false;
$out = vance_dashboard_link( array( '/dashboard/' ) );
check( '5b  an array is returned unchanged, not parsed',
	$out === array( '/dashboard/' ) );


/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
