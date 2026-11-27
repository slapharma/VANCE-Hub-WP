<?php
/**
 * Tests for the VanceHubClub look on every member dashboard tab: the second
 * switch "vance_hub_dashboard_tabs" in inc/hub-dashboard.php, the stylesheet
 * hub-dashboard-tabs.css, and the additive edits in page-dashboard.php.
 *
 * Same shape as hub-dashboard.test.php, and on purpose the SAME stubs: the stub
 * block of hub-layout.test.php (everything before its first $FRONT_PAGE line) is
 * lifted and evaluated. If that block cannot be found, this suite stops with
 * exit 2 rather than passing on nothing.
 *
 * WHAT IS COVERED
 *   1  the switch: resolve() table, active() through the request and the
 *      capability gate
 *   2  the body class: only on the dashboard page, for a signed-in member, when
 *      the switch AND the VanceHubClub dashboard are on; hook registered
 *   3  the assets: stylesheet on a non-home tab, and every case where it must
 *      not load
 *   4  the preview: ?vhh_layout=hub is kept in tab links for an administrator,
 *      and never added for anyone else
 *   5  the member card: real data only, escaping, the member number only when its
 *      own switch is on, the Ask VANCE-Ai block
 *   6  page-dashboard.php by static inspection: every new line is guarded by the
 *      flag, and the order of the sidebar parts
 *   7  the contract guard: form field names, element ids, AJAX actions and nonce
 *      names in page-dashboard.php are identical to the commit this work started
 *      from. A mutated copy must NOT match (negative control).
 *   8  the stylesheet: every rule is scoped under body.vhd-tabs
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
function is_page_template( $template ) { return $GLOBALS['TEMPLATE'] === $template; }
function is_page( $page = '' ) { return $GLOBALS['SLUG'] === $page; }

function get_user_meta( $user_id, $key = '', $single = false ) {
	return isset( $GLOBALS['USER_META'][ $user_id ][ $key ] ) ? $GLOBALS['USER_META'][ $user_id ][ $key ] : '';
}
function vance_dashboard_feature_enabled( $slug ) {
	return array_key_exists( $slug, $GLOBALS['FEATURE_ON'] ) ? (bool) $GLOBALS['FEATURE_ON'][ $slug ] : true;
}

// The member number lives in hub-dashboard-features.php, with its own suite.
// Here only the wiring is tested: the card asks for the code when, and only
// when, the member-number switch is on.
function vance_hub_dash_feature_on( $name ) {
	return (bool) vance_hub_sanitize_checkbox( vance_get_theme_mod( $name, false ) );
}
function vance_hub_member_code_for( $user_id ) {
	$GLOBALS['CODE_CALLS']++;
	return 'K7QM-4XZP';
}

require_once $THEME . '/inc/hub-dashboard.php';

/* ---- helpers ------------------------------------------------------------------ */

function ht_arrange( array $mods = array(), array $caps = array(), array $get = array(), $logged_in = true ) {
	arrange( $mods, false, $caps, $get );
	$GLOBALS['LOGGED_IN']    = $logged_in;
	$GLOBALS['TEMPLATE']     = '';
	$GLOBALS['SLUG']         = 'dashboard';
	$GLOBALS['USER_META']    = array();
	$GLOBALS['FEATURE_ON']   = array();
	$GLOBALS['CODE_CALLS']   = 0;
	$GLOBALS['ENQUEUED']     = array();
}

function ht_user( $id, $display_name, $registered ) {
	$user                  = new WP_User();
	$user->ID              = $id;
	$user->display_name    = $display_name;
	$user->user_registered = $registered;
	return $user;
}

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

$ON  = array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true, 'vance_hub_dashboard_tabs' => true );

/* ===================================================================== */
section( '1. The switch' );

check( 'resolve: switch on and dashboard active is on', vance_hub_dashboard_tabs_resolve( true, true ) === true );
check( 'resolve: switch on, dashboard NOT active is off', vance_hub_dashboard_tabs_resolve( true, false ) === false );
check( 'resolve: switch off, dashboard active is off', vance_hub_dashboard_tabs_resolve( false, true ) === false );
check( 'resolve: switch off and dashboard off is off', vance_hub_dashboard_tabs_resolve( false, false ) === false );
check( 'resolve: saved 1 (int) with the dashboard is on', vance_hub_dashboard_tabs_resolve( 1, true ) === true );
check( 'resolve: saved "1" (string) with the dashboard is on', vance_hub_dashboard_tabs_resolve( '1', true ) === true );
check( 'resolve: saved 0 is off', vance_hub_dashboard_tabs_resolve( 0, true ) === false );
check( 'resolve: saved "0" is off', vance_hub_dashboard_tabs_resolve( '0', true ) === false );
check( 'resolve: saved empty string is off', vance_hub_dashboard_tabs_resolve( '', true ) === false );
check( 'resolve: saved null is off', vance_hub_dashboard_tabs_resolve( null, true ) === false );
check( 'resolve: saved "on" raw is off (the caller sanitises first)', vance_hub_dashboard_tabs_resolve( 'on', true ) === false );
check( 'resolve: a truthy non-bool dashboard flag is not enough', vance_hub_dashboard_tabs_resolve( true, 'yes' ) === false );

ht_arrange( $ON );
check( 'active: both switches saved on is on', vance_hub_dashboard_tabs_active() === true );

ht_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true ) );
check( 'active: the tab switch never saved is on (its default is on)', vance_hub_dashboard_tabs_active() === true );

ht_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true, 'vance_hub_dashboard_tabs' => false ) );
check( 'active: the tab switch saved off is off', vance_hub_dashboard_tabs_active() === false );

ht_arrange( array( 'vance_hub_show_dashboard' => false, 'vance_home_layout' => 'hub', 'vance_hub_dashboard_tabs' => true ) );
check( 'active: tab switch on but the VanceHubClub dashboard off is off', vance_hub_dashboard_tabs_active() === false );

ht_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dashboard_tabs' => true ) );
check( 'active: tab switch on, dashboard switch never saved (default on), is on', vance_hub_dashboard_tabs_active() === true );

ht_arrange( array( 'vance_hub_show_dashboard' => false, 'vance_home_layout' => 'hub', 'vance_hub_dashboard_tabs' => true ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'active: an administrator preview of the dashboard turns the tab look on', vance_hub_dashboard_tabs_active() === true );

ht_arrange( $ON, array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'classic' ) );
check( 'active: an administrator classic preview turns the tab look off', vance_hub_dashboard_tabs_active() === false );

ht_arrange( array( 'vance_hub_show_dashboard' => false, 'vance_home_layout' => 'hub', 'vance_hub_dashboard_tabs' => true ), array(), array( 'vhh_layout' => 'hub' ) );
check( 'active: a visitor cannot preview it with ?vhh_layout=hub', vance_hub_dashboard_tabs_active() === false );

ht_arrange( array( 'vance_hub_show_dashboard' => false, 'vance_hub_dashboard_tabs' => false ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'active: a preview does not switch on a tab switch that is off', vance_hub_dashboard_tabs_active() === false );

ht_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true, 'vance_hub_dashboard_tabs' => '1' ) );
check( 'active: a saved "1" string is on after the checkbox sanitiser', vance_hub_dashboard_tabs_active() === true );

ht_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true, 'vance_hub_dashboard_tabs' => '0' ) );
check( 'active: a saved "0" string is off after the checkbox sanitiser', vance_hub_dashboard_tabs_active() === false );

// The master switch (Home layout) over the tab look.
ht_arrange( array( 'vance_hub_show_dashboard' => true, 'vance_hub_dashboard_tabs' => true ) );
check( 'master: classic with both boxes ticked is off for a visitor', vance_hub_dashboard_tabs_active() === false );

// Expected to FAIL on the current theme: under an admin preview the dashboard is on,
// but the tab look reads its saved switch through the master, which is classic here.
ht_arrange( array( 'vance_hub_show_dashboard' => true, 'vance_hub_dashboard_tabs' => true ), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'master: classic with an admin ?vhh_layout=hub preview turns the tab look on', vance_hub_dashboard_tabs_active() === true );

ht_arrange( array( 'vance_home_layout' => 'hub' ) );
check( 'master: hub with the tab switch never saved is on (default on)', vance_hub_dashboard_tabs_active() === true );

ht_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true, 'vance_hub_dashboard_tabs' => false ) );
check( 'master: hub with the tab switch saved off is off', vance_hub_dashboard_tabs_active() === false );

/* ===================================================================== */
section( '2. The body class' );

check( 'hook: body_class runs vance_hub_dashboard_tabs_body_class', isset( $GLOBALS['HOOKS']['body_class'][10] ) && in_array( 'vance_hub_dashboard_tabs_body_class', $GLOBALS['HOOKS']['body_class'][10], true ) );

ht_arrange( $ON );
$C = vance_hub_dashboard_tabs_body_class( array( 'page', 'logged-in' ) );
check( 'class: added on the dashboard page when both switches are on', in_array( 'vhd-tabs', $C, true ) );
check( 'class: the existing body classes are kept', in_array( 'page', $C, true ) && in_array( 'logged-in', $C, true ) );

ht_arrange( $ON, array(), array( 'tab' => 'notes' ) );
check( 'class: added on a non-home tab', in_array( 'vhd-tabs', vance_hub_dashboard_tabs_body_class( array() ), true ) );

ht_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true, 'vance_hub_dashboard_tabs' => false ) );
check( 'class: absent when the tab switch is off',! in_array( 'vhd-tabs', vance_hub_dashboard_tabs_body_class( array( 'page' ) ), true ) );

ht_arrange( array( 'vance_hub_show_dashboard' => false, 'vance_home_layout' => 'hub', 'vance_hub_dashboard_tabs' => true ) );
check( 'class: absent when the tab switch is on but the dashboard layout is not', ! in_array( 'vhd-tabs', vance_hub_dashboard_tabs_body_class( array( 'page' ) ), true ) );

ht_arrange( $ON );
$GLOBALS['SLUG'] = 'my-notes';
check( 'class: absent on another page', ! in_array( 'vhd-tabs', vance_hub_dashboard_tabs_body_class( array() ), true ) );

ht_arrange( $ON );
$GLOBALS['SLUG']     = '';
$GLOBALS['TEMPLATE'] = 'page-dashboard.php';
check( 'class: present on a page that uses the dashboard template', in_array( 'vhd-tabs', vance_hub_dashboard_tabs_body_class( array() ), true ) );

ht_arrange( $ON, array(), array(), false );
check( 'class: absent for a visitor who is logged out', ! in_array( 'vhd-tabs', vance_hub_dashboard_tabs_body_class( array() ), true ) );

/* ===================================================================== */
section( '3. Assets' );

check( 'hook: wp_enqueue_scripts runs the tab stylesheet at priority 21, after the home stylesheet', isset( $GLOBALS['HOOKS']['wp_enqueue_scripts'][21] ) && in_array( 'vance_hub_dashboard_tabs_enqueue_assets', $GLOBALS['HOOKS']['wp_enqueue_scripts'][21], true ) );

ht_arrange( $ON, array(), array( 'tab' => 'notes' ) );
vance_hub_dashboard_enqueue_assets();
vance_hub_dashboard_tabs_enqueue_assets();
check( 'assets: the tab stylesheet loads on a non-home tab', array_key_exists( 'style:vance-hub-dashboard-tabs', $GLOBALS['ENQUEUED'] ) );
check( 'assets: its URL points at hub-dashboard-tabs.css', isset( $GLOBALS['ENQUEUED']['style:vance-hub-dashboard-tabs'] ) && $GLOBALS['ENQUEUED']['style:vance-hub-dashboard-tabs']['src'] === 'https://example.test/wp-content/themes/vance-health-hub/assets/css/hub-dashboard-tabs.css' );
check( 'assets: it depends on the main stylesheet', isset( $GLOBALS['ENQUEUED']['style:vance-hub-dashboard-tabs'] ) && $GLOBALS['ENQUEUED']['style:vance-hub-dashboard-tabs']['deps'] === array( 'vance-main-style' ) );
check( 'assets: the home stylesheet still loads next to it on that tab', array_key_exists( 'style:vance-hub-dashboard', $GLOBALS['ENQUEUED'] ) );
check( 'assets: the stylesheet file exists in the theme', is_file( $THEME . '/assets/css/hub-dashboard-tabs.css' ) );

ht_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_show_dashboard' => true, 'vance_hub_dashboard_tabs' => false ) );
vance_hub_dashboard_enqueue_assets();
vance_hub_dashboard_tabs_enqueue_assets();
check( 'assets: switch off loads no tab stylesheet',! array_key_exists( 'style:vance-hub-dashboard-tabs', $GLOBALS['ENQUEUED'] ) );
check( 'assets: switch off still loads the home stylesheet, as before', array_key_exists( 'style:vance-hub-dashboard', $GLOBALS['ENQUEUED'] ) );

ht_arrange( array( 'vance_hub_show_dashboard' => false, 'vance_home_layout' => 'hub', 'vance_hub_dashboard_tabs' => true ) );
vance_hub_dashboard_tabs_enqueue_assets();
check( 'assets: tab switch on but dashboard layout off loads nothing', $GLOBALS['ENQUEUED'] === array() );

ht_arrange( $ON, array(), array(), false );
vance_hub_dashboard_tabs_enqueue_assets();
check( 'assets: a logged-out visitor loads nothing', $GLOBALS['ENQUEUED'] === array() );

ht_arrange( $ON );
$GLOBALS['SLUG'] = 'contact-us';
vance_hub_dashboard_tabs_enqueue_assets();
check( 'assets: another page loads nothing', $GLOBALS['ENQUEUED'] === array() );

ht_arrange( $ON );
$GLOBALS['SLUG']     = '';
$GLOBALS['TEMPLATE'] = 'page-dashboard.php';
vance_hub_dashboard_tabs_enqueue_assets();
check( 'assets: the dashboard template without the slug loads it', array_key_exists( 'style:vance-hub-dashboard-tabs', $GLOBALS['ENQUEUED'] ) );

/* ===================================================================== */
section( '4. The preview survives a click' );

ht_arrange( array(), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'preview: an administrator on ?vhh_layout=hub keeps it in a tab link', vance_hub_dashboard_tab_url( 'notes' ) === '?tab=notes&vhh_layout=hub' );
check( 'preview: the preview argument is hub', vance_hub_dashboard_preview_arg() === 'hub' );

ht_arrange( array(), array( 'edit_theme_options' => true ), array() );
check( 'preview: an administrator with no preview gets the plain link', vance_hub_dashboard_tab_url( 'notes' ) === '?tab=notes' );

ht_arrange( array(), array(), array( 'vhh_layout' => 'hub' ) );
check( 'preview: a visitor with the query gets the plain link', vance_hub_dashboard_tab_url( 'notes' ) === '?tab=notes' );

ht_arrange( array(), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'classic' ) );
check( 'preview: a classic preview is not carried', vance_hub_dashboard_tab_url( 'notes' ) === '?tab=notes' );

ht_arrange( array(), array( 'edit_theme_options' => true ), array( 'vhh_layout' => array( 'hub' ) ) );
check( 'preview: an array value is ignored, not a fatal', vance_hub_dashboard_tab_url( 'notes' ) === '?tab=notes' );

ht_arrange( array(), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub<script>' ) );
check( 'preview: a value with markup is not carried', vance_hub_dashboard_tab_url( 'notes' ) === '?tab=notes' );

ht_arrange( array(), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'preview: the slug is still URL-encoded', vance_hub_dashboard_tab_url( 'a b&c' ) === '?tab=a%20b%26c&vhh_layout=hub' );

/* ===================================================================== */
section( '5. The member card and the Ask block' );

ht_arrange( array( 'vance_home_layout' => 'hub', 'vance_hub_dash_member_no' => true ) );
$GLOBALS['USER_META'][5] = array( 'first_name' => 'Ruth', 'last_name' => 'Adams' );
$U = ht_user( 5, 'ruth.a', '2026-03-09 10:00:00' );
$D = vance_hub_dashboard_member_card_data( $U );
check( 'card data: the full name from the account', $D['name'] === 'Ruth Adams' );
check( 'card data: the join month and year', $D['since'] === 'March 2026' );
check( 'card data: the member number when its switch is on', $D['code'] === 'K7QM-4XZP' );
$H = vance_hub_dashboard_member_card_html( $D );
check( 'card html: shows the name', strpos( $H, 'Ruth Adams' ) !== false );
check( 'card html: shows the member number line', strpos( $H, 'Member number K7QM-4XZP' ) !== false );
check( 'card html: shows the since line', strpos( $H, 'Member since March 2026' ) !== false );

ht_arrange( array() );
$GLOBALS['USER_META'][5] = array( 'first_name' => 'Ruth', 'last_name' => 'Adams' );
$D = vance_hub_dashboard_member_card_data( $U );
check( 'card data: no member number when its switch is off', $D['code'] === '' );
check( 'card data: the code is not even requested when its switch is off', $GLOBALS['CODE_CALLS'] === 0 );
$H = vance_hub_dashboard_member_card_html( $D );
check( 'card html: no member number line when the switch is off', strpos( $H, 'Member number' ) === false );
check( 'card html: the since line still shows', strpos( $H, 'Member since March 2026' ) !== false );

ht_arrange( array() );
$GLOBALS['USER_META'][6] = array( 'first_name' => 'Ola' );
$D = vance_hub_dashboard_member_card_data( ht_user( 6, 'ola.display', '' ) );
check( 'card data: first name alone is used when there is no last name', $D['name'] === 'Ola' );
check( 'card data: a blank registration date gives no since line', $D['since'] === '' );
check( 'card html: no since line for a blank date', strpos( vance_hub_dashboard_member_card_html( $D ), 'Member since' ) === false );

ht_arrange( array() );
$D = vance_hub_dashboard_member_card_data( ht_user( 7, 'display.only', '0000-00-00 00:00:00' ) );
check( 'card data: the display name is the fallback', $D['name'] === 'display.only' );
check( 'card data: the zero date gives no since line', $D['since'] === '' );

check( 'card html: nothing at all when there is no name', vance_hub_dashboard_member_card_html( array( 'name' => '', 'since' => 'May 2026', 'code' => '' ) ) === '' );

$H = vance_hub_dashboard_member_card_html( array( 'name' => '<img src=x onerror=alert(1)>', 'since' => '', 'code' => '"><b>x' ) );
check( 'card html: a name with markup is escaped', strpos( $H, '<img' ) === false && strpos( $H, '&lt;img' ) !== false );
check( 'card html: a code with markup is escaped', strpos( $H, '<b>' ) === false );

// Real data only: the design's sample badge, status and streak lines are not printed.
$H = vance_hub_dashboard_member_card_html( array( 'name' => 'Ruth Adams', 'since' => 'March 2026', 'code' => 'K7QM-4XZP' ) );
check( 'card html: no invented status words', stripos( $H, 'founding' ) === false && stripos( $H, 'weeks in a row' ) === false && stripos( $H, 'streak' ) === false && stripos( $H, 'premium' ) === false );

ht_arrange( array() );
$out = ( function () { ob_start(); vance_hub_dashboard_tabs_render_member_card( new WP_User() ); return ob_get_clean(); } )();
check( 'render: a user who does not exist prints no card', $out === '' );

$A = vance_hub_dashboard_ask_block_html( true );
check( 'ask: a button, not a link', strpos( $A, '<button type="button"' ) === 0 );
check( 'ask: opens the shared chat modal on a new conversation', strpos( $A, 'data-vance-askai-open="new"' ) !== false );
check( 'ask: reads Ask VANCE-Ai', strpos( $A, 'Ask VANCE-Ai' ) !== false );
check( 'ask: nothing when the VANCE-Ai tab is off', vance_hub_dashboard_ask_block_html( false ) === '' );

ht_arrange( array() );
$out = ( function () { ob_start(); vance_hub_dashboard_tabs_render_ask_block(); return ob_get_clean(); } )();
check( 'render ask: printed when the ai-chats feature is on', strpos( $out, 'data-vance-askai-open="new"' ) !== false );
ht_arrange( array() );
$GLOBALS['FEATURE_ON'] = array( 'ai-chats' => false );
$out = ( function () { ob_start(); vance_hub_dashboard_tabs_render_ask_block(); return ob_get_clean(); } )();
check( 'render ask: nothing when the ai-chats feature is off', $out === '' );

check( 'eyebrow: one small line with the My Hub label', vance_hub_dashboard_tab_eyebrow_html() === '<p class="vhdt-eyebrow">My Hub</p>' );

/* ===================================================================== */
section( '6. page-dashboard.php, by static inspection' );

$PD = str_replace( "\r\n", "\n", file_get_contents( $THEME . '/page-dashboard.php' ) );

$OLD_FLAG = '$vance_hub_dash = ( \'home\' === $current_tab && function_exists( \'vance_hub_dashboard_active\' ) && vance_hub_dashboard_active() );';
$NEW_FLAG = '$vance_hub_tabs = ( function_exists( \'vance_hub_dashboard_tabs_active\' ) && vance_hub_dashboard_tabs_active() );';
$P_OLD    = strpos( $PD, $OLD_FLAG );
$P_NEW    = strpos( $PD, $NEW_FLAG );
check( 'page: the existing home flag is untouched', $P_OLD !== false && substr_count( $PD, $OLD_FLAG ) === 1 );
check( 'page: the tab flag is set once, after the home flag', $P_NEW !== false && substr_count( $PD, $NEW_FLAG ) === 1 && $P_NEW > $P_OLD );
check( 'page: the tab flag does not depend on the tab, so it covers every tab', strpos( $NEW_FLAG, '$current_tab' ) === false );

// Every call into the new markup is on a line that tests the flag.
$GUARDED = true;
$CALLS   = 0;
foreach ( explode( "\n", $PD ) as $line ) {
	if ( preg_match( '/vance_hub_dashboard_(tabs_render_member_card|tabs_render_ask_block|tab_eyebrow_html)\(/', $line ) ) {
		$CALLS++;
		if ( strpos( $line, '$vance_hub_tabs' ) === false ) {
			$GUARDED = false;
		}
	}
}
check( 'page: three calls into the new markup (card, Ask block, eyebrow)', 3 === $CALLS );
check( 'page: each of them is guarded by $vance_hub_tabs on the same line', $GUARDED );

$P_HEAD  = strpos( $PD, '<div class="sidebar-header">' );
$P_CARD  = strpos( $PD, 'vance_hub_dashboard_tabs_render_member_card( $current_user )' );
$P_NAV   = strpos( $PD, '<nav class="dash-nav">' );
$P_NAVX  = strpos( $PD, '</nav>', $P_NAV );
$P_ASK   = strpos( $PD, 'vance_hub_dashboard_tabs_render_ask_block()' );
$P_ASIDE = strpos( $PD, '</aside>' );
check( 'page: the member card sits after the logo band and before the menu', $P_HEAD !== false && $P_CARD > $P_HEAD && $P_CARD < $P_NAV );
check( 'page: the Ask block sits after the menu and before the close of the sidebar', $P_ASK > $P_NAVX && $P_ASK < $P_ASIDE );

$P_SW  = strpos( $PD, '<?php switch($current_tab) :' );
$P_GATE = strpos( $PD, '<?php if ( ! $vance_hub_dash ) : ?>' );
$P_WRAP = strpos( $PD, '<div<?php echo $vance_hub_tabs ? \' class="vhdt-head"\' : \'\'; ?> style="margin-bottom: 32px;' );
$P_EYE  = strpos( $PD, 'vance_hub_dashboard_tab_eyebrow_html()' );
$P_H1   = strpos( $PD, '<h1 style="font-family:' );
check( 'page: the heading wrapper gets its class only through the flag', $P_WRAP !== false && $P_WRAP > $P_GATE && $P_WRAP < $P_SW );
check( 'page: the eyebrow comes right before the heading, inside the gate', $P_EYE > $P_WRAP && $P_EYE < $P_H1 && $P_H1 < $P_SW );

// The tab-link helper, run from its own source in both modes.
$GOT = preg_match( '/\$vance_tab_href = function \( \$slug \) use \( \$vance_hub_tabs \) \{.*?\n    \};/s', $PD, $LIFT );
check( 'page: the tab-link helper can be lifted from the source', 1 === $GOT );
$run = function ( $flag, $slug ) use ( $LIFT ) {
	$vance_hub_tabs = $flag;
	eval( $LIFT[0] );
	return $vance_tab_href( $slug );
};
ht_arrange( array(), array( 'edit_theme_options' => true ), array( 'vhh_layout' => 'hub' ) );
check( 'page: flag off gives the classic link, even in an administrator preview', $run( false, 'notes' ) === '?tab=notes' );
check( 'page: flag on gives the link that keeps the preview', $run( true, 'notes' ) === '?tab=notes&vhh_layout=hub' );
check( 'page: the sidebar link and the breadcrumb root both use the helper', strpos( $PD, 'echo esc_attr( $vance_tab_href( $slug ) )' ) !== false && strpos( $PD, 'echo esc_attr( $vance_tab_href( \'home\' ) )' ) !== false );

/* ===================================================================== */
section( '7. The contract guard: forms, ids, AJAX actions, nonces' );

// The commit this work started from. A fixed commit, not origin/main: main moves
// as other work lands, and this guard must stay a comparison with one known page.
$BASE_REF  = 'f69c8f5';
$BASE_PATH = 'wp-content/themes/vance-health-hub/page-dashboard.php';
$BASE      = shell_exec( 'git -C ' . escapeshellarg( $ROOT ) . ' show ' . escapeshellarg( $BASE_REF . ':' . $BASE_PATH ) . ' 2>&1' );
if ( ! is_string( $BASE ) || strpos( $BASE, 'Template Name: User Dashboard' ) === false ) {
	// A shallow clone or a tarball does not hold the base commit. Skip this
	// section loudly instead of aborting the whole suite.
	echo "  SKIPPED: page-dashboard.php at $BASE_REF is not in this git history (shallow clone or tarball?). The contract guard did not run.\n";
	fwrite( STDERR, "contract guard skipped: $BASE_REF not readable with git\n" );
	goto vance_contract_guard_end;
}
$BASE = str_replace( "\r\n", "\n", $BASE );

/** Every contract string of the page, counted. */
function pd_contracts( $src ) {
	$found = array();
	$add   = function ( $kind, $pattern ) use ( &$found, $src ) {
		preg_match_all( $pattern, $src, $m );
		foreach ( $m[1] as $value ) {
			$key           = $kind . ':' . $value;
			$found[ $key ] = ( isset( $found[ $key ] ) ? $found[ $key ] : 0 ) + 1;
		}
	};
	$add( 'name', '/\bname="([^"]*)"/' );
	$add( 'name', "/\\bname='([^']*)'/" );
	$add( 'id', '/\bid="([^"]*)"/' );
	$add( 'action', "/\\baction:\\s*'([^']*)'/" );
	$add( 'action', '/\baction:\s*"([^"]*)"/' );
	$add( 'action', "/'action'\\s*=>\\s*'([^']*)'/" );
	$add( 'action', "/append\\(\\s*'action'\\s*,\\s*'([^']*)'/" );
	$add( 'nonce-make', "/wp_create_nonce\\(\\s*['\"]([^'\"]+)['\"]/" );
	$add( 'nonce-check', "/check_ajax_referer\\(\\s*['\"]([^'\"]+)['\"]/" );
	$add( 'nonce-verify', "/wp_verify_nonce\\([^,]+,\\s*['\"]([^'\"]+)['\"]/" );
	ksort( $found );
	return $found;
}

$BASE_C = pd_contracts( $BASE );
$NOW_C  = pd_contracts( $PD );

$KINDS = array();
foreach ( array_keys( $BASE_C ) as $k ) {
	$KINDS[ substr( $k, 0, strpos( $k, ':' ) ) ] = true;
}
check( 'contract: the baseline holds form field names', isset( $KINDS['name'] ) && count( $BASE_C ) > 40 );
check( 'contract: the baseline holds element ids, AJAX actions and nonce names', isset( $KINDS['id'], $KINDS['action'], $KINDS['nonce-make'] ) );
check( 'contract: the baseline holds a vance_ AJAX action and the dashboard nonce', isset( $BASE_C['action:vance_toggle_bookmark'], $BASE_C['nonce-make:vance_dashboard_nonce'] ) );

$ADDED   = array_diff_key( $NOW_C, $BASE_C );
$REMOVED = array_diff_key( $BASE_C, $NOW_C );
$CHANGED = array();
foreach ( $BASE_C as $k => $n ) {
	if ( isset( $NOW_C[ $k ] ) && $NOW_C[ $k ] !== $n ) {
		$CHANGED[] = $k;
	}
}
check( 'contract: no field name, id, action or nonce was removed' . ( $REMOVED ? ' (' . implode( ', ', array_keys( $REMOVED ) ) . ')' : '' ), $REMOVED === array() );
check( 'contract: none was added' . ( $ADDED ? ' (' . implode( ', ', array_keys( $ADDED ) ) . ')' : '' ), $ADDED === array() );
check( 'contract: none appears a different number of times' . ( $CHANGED ? ' (' . implode( ', ', $CHANGED ) . ')' : '' ), $CHANGED === array() );
check( 'contract: the two fingerprints are identical', $BASE_C === $NOW_C );

// Negative control: the guard must be able to fail. Each mutated copy of the
// current page has to differ from the baseline.
$MUT = array(
	'a renamed field'     => str_replace( 'name="first_name"', 'name="vance_first_name"', $PD ),
	'a renamed action'    => str_replace( "action: 'vance_toggle_bookmark'", "action: 'vance_toggle_bookmark2'", $PD ),
	'a renamed nonce'     => str_replace( 'wp_create_nonce("vance_dashboard_nonce")', 'wp_create_nonce("vance_dashboard_nonce2")', $PD ),
	'a renamed element id' => preg_replace( '/\bid="profile-form-main"/', 'id="profile-form-main2"', $PD, 1 ),
);
foreach ( $MUT as $what => $mutated ) {
	check( 'control: ' . $what . ' makes the guard fail (the mutation did change the page)', $mutated !== $PD && pd_contracts( $mutated ) !== $BASE_C );
}
vance_contract_guard_end:

/* ===================================================================== */
section( '8. The stylesheet' );

$CSS = file_get_contents( $THEME . '/assets/css/hub-dashboard-tabs.css' );
$CSS = preg_replace( '#/\*.*?\*/#s', '', $CSS );
// Selector lists: the text before each "{", ignoring @media lines and keyframes.
preg_match_all( '/([^{}]+)\{/', $CSS, $SEL );
$UNSCOPED = array();
foreach ( $SEL[1] as $list ) {
	$list = trim( $list );
	if ( '' === $list || '@' === $list[0] ) {
		continue;
	}
	foreach ( explode( ',', $list ) as $one ) {
		$one = trim( $one );
		if ( '' !== $one && 0 !== strpos( $one, 'body.vhd-tabs' ) ) {
			$UNSCOPED[] = $one;
		}
	}
}
check( 'css: the file has rules', count( $SEL[1] ) > 20 );
check( 'css: every selector starts with body.vhd-tabs' . ( $UNSCOPED ? ' (loose: ' . implode( ' | ', array_slice( $UNSCOPED, 0, 3 ) ) . ')' : '' ), $UNSCOPED === array() );
check( 'css: no hard-coded radius, only the token scale or a round shape', preg_match_all( '/border-radius:\s*([^;]+);/', $CSS, $RAD ) > 0 && count( array_filter( $RAD[1], function ( $v ) { return strpos( $v, 'var(--radius-' ) !== 0; } ) ) === 0 );
check( 'css: the navy side menu and the teal active item are set', strpos( $CSS, '--vhdt-navy: #0A1929' ) !== false && strpos( $CSS, '--vhdt-teal: #008080' ) !== false && preg_match( '/\.nav-item\.active[^{]*\{[^}]*background:\s*var\(--vhdt-teal\)/', $CSS ) === 1 );
check( 'css: the card has the 6px teal top border and the soft shadow', strpos( $CSS, 'border-top: 6px solid var(--vhdt-teal)' ) !== false && strpos( $CSS, '--vhdt-shadow: 0 8px 22px rgba(var(--vhdt-navy-rgb), 0.08);' ) !== false && strpos( $CSS, '--vhdt-navy-rgb: 10, 25, 41;' ) !== false );
check( 'css: the purple Ask block uses the purple token', preg_match( '/\.vhdt-ask\s*\{[^}]*background:\s*var\(--vhdt-purple\)/', $CSS ) === 1 );
check( 'css: nav items and buttons have a 44px target', substr_count( $CSS, 'min-height: 44px' ) >= 2 );
check( 'css: no Dashicons dependency (not loaded for visitors)', stripos( $CSS, 'dashicons' ) === false );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . ' FAILED of ' . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK - $PASS checks passed.\n";
