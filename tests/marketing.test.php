<?php
/**
 * Tests for the organic social plan code:
 *   inc/attribution.php, inc/marketing-pages.php, inc/waitlist.php and the plan
 *   page templates. Plain PHP, no WordPress. Run: php tests/marketing.test.php
 * Exits 1 when any check fails.
 */

define( 'ABSPATH', true );
define( 'DAY_IN_SECONDS', 86400 );
$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

/* ---- stubs ---------------------------------------------------------------- */
$GLOBALS['META']    = array();
$GLOBALS['OPTIONS'] = array();
$GLOBALS['CRM']     = array();
$GLOBALS['FILTERS'] = array();
class JsonReply extends Exception {
	public $ok; public $data; public $status;
	public function __construct( $ok, $data, $status ) { parent::__construct( 'json' ); $this->ok = $ok; $this->data = $data; $this->status = $status; }
}
function add_action() {}
function add_filter( $tag, $cb ) { $GLOBALS['FILTERS'][ $tag ][] = $cb; }
function apply_filters( $tag, $value ) { return $value; }
function __( $s ) { return $s; }
function wp_unslash( $v ) { return $v; }
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_email( $v ) { return trim( (string) $v ); }
function is_email( $v ) { return (bool) preg_match( '/^[^@\s]+@[^@\s]+\.[^@\s]+$/', (string) $v ); }
function update_user_meta( $id, $k, $v ) { $GLOBALS['META'][ $id ][ $k ] = $v; }
function get_user_meta( $id, $k, $single = true ) { return $GLOBALS['META'][ $id ][ $k ] ?? ''; }
function get_option( $k, $d = false ) { return $GLOBALS['OPTIONS'][ $k ] ?? $d; }
function update_option( $k, $v ) { $GLOBALS['OPTIONS'][ $k ] = $v; return true; }
function wp_verify_nonce( $n, $a ) { return 'good' === $n && 'vance_waitlist' === $a; }
function vance_rate_limit() { return true; }
function home_url( $p = '' ) { return 'https://example.test' . $p; }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function wp_send_json_success( $d = null, $s = 200 ) { throw new JsonReply( true, $d, $s ); }
function wp_send_json_error( $d = null, $s = 400 ) { throw new JsonReply( false, $d, $s ); }
function FluentCrmApi() {
	return new class() {
		public function createOrUpdate( $data ) { $GLOBALS['CRM'][] = $data; return true; }
	};
}

require $THEME . '/inc/attribution.php';
require $THEME . '/inc/marketing-pages.php';
require $THEME . '/inc/waitlist.php';

/* ---- harness ---------------------------------------------------------------- */
$GLOBALS['PASS'] = 0;
$GLOBALS['FAILS'] = array();
function check( $label, $ok ) {
	if ( $ok ) { $GLOBALS['PASS']++; return; }
	$GLOBALS['FAILS'][] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }
function waitlist_call( array $post ) {
	$_POST = $post;
	try {
		vance_waitlist_signup_ajax();
	} catch ( JsonReply $r ) {
		return $r;
	}
	return null;
}
$good = array( 'nonce' => 'good', 'email' => 'a@example.test', 'first_name' => 'Ann', 'consent' => '1' );

/* ---- 1. attribution values ----------------------------------------------------- */
section( '1. Campaign values are short safe labels' );
check( 'clean: lower case, junk stripped', 'instagram' === vance_attribution_clean( ' Instagram!! ' ) );
check( 'clean: markup and quotes removed', 'scriptalert1script' === vance_attribution_clean( '<script>alert(1)</script>' ) );
check( 'clean: capped at 40 characters', 40 === strlen( vance_attribution_clean( str_repeat( 'a', 90 ) ) ) );
check( 'clean: a non-string gives an empty string', '' === vance_attribution_clean( array( 'x' ) ) );
check( 'clean: hyphen, dot and underscore are kept', 'bio-link_2.0' === vance_attribution_clean( 'bio-link_2.0' ) );
$ex = vance_attribution_extract( array( 'utm_source' => 'TikTok', 'utm_medium' => 'bio', 'utm_term' => 'ibs', 'q' => 'x' ) );
check( 'extract: keeps only the four campaign keys', array( 'utm_source' => 'tiktok', 'utm_medium' => 'bio' ) === $ex );
check( 'extract: an empty value is dropped', array() === vance_attribution_extract( array( 'utm_source' => '!!!' ) ) );

/* ---- 2. sign-up record ------------------------------------------------------------ */
section( '2. The source is copied to the member on sign-up' );
$_COOKIE = array( 'vance_utm' => 'utm_source=instagram&utm_medium=bio&utm_campaign=wk1' );
vance_attribution_record_signup( 11 );
check( 'record: source, medium and campaign are stored', 'instagram' === get_user_meta( 11, '_vance_signup_source' ) && 'bio' === get_user_meta( 11, '_vance_signup_medium' ) && 'wk1' === get_user_meta( 11, '_vance_signup_campaign' ) );
$_COOKIE = array();
vance_attribution_record_signup( 12 );
check( 'record: no cookie means no meta is written', empty( $GLOBALS['META'][12] ) );
$_COOKIE = array( 'vance_utm' => 'utm_medium=bio' );
vance_attribution_record_signup( 13 );
check( 'record: no source means nothing is written', empty( $GLOBALS['META'][13] ) );
$_COOKIE = array( 'vance_utm' => 'utm_source=%3Cb%3Eevil%3C%2Fb%3E&condition=ibs' );
vance_attribution_record_signup( 14 );
check( 'record: markup in the cookie is reduced to a token', 'bevilb' === get_user_meta( 14, '_vance_signup_source' ) );
check( 'record: an unknown cookie key (such as a condition) is never stored', ! isset( $GLOBALS['META'][14]['_vance_signup_condition'] ) && 1 === count( $GLOBALS['META'][14] ) );

/* ---- 3. CRM fields -------------------------------------------------------------------- */
section( '3. FluentCRM gets the source tag and value' );
$contact = vance_attribution_crm_contact( array( 'email' => 'a@example.test', 'tags' => array( 'member' ), 'custom_values' => array( 'wp_user_id' => 11 ) ), 11 );
check( 'crm: the utm tag is added next to the existing tags', array( 'member', 'utm-instagram' ) === $contact['tags'] );
check( 'crm: the custom value is added and the old value kept', 'instagram' === $contact['custom_values']['utm_source'] && 11 === $contact['custom_values']['wp_user_id'] );
$same = array( 'email' => 'b@example.test', 'tags' => array( 'member' ) );
check( 'crm: a member with no source gets the contact unchanged', $same === vance_attribution_crm_contact( $same, 12 ) );
check( 'crm: a different source gives a different tag (control)', in_array( 'utm-bevilb', vance_attribution_crm_contact( array( 'tags' => array() ), 14 )['tags'], true ) );

/* ---- 4. tool links on the landing page -------------------------------------------------- */
section( '4. Landing page links keep the tags and use exact slugs' );
$_GET = array( 'utm_source' => 'Instagram', 'utm_medium' => 'bio', 'utm_campaign' => 'wk1', 'evil' => '<x>' );
$tools = vance_marketing_tools();
check( 'tools: exactly three tools', 3 === count( $tools ) );
check( 'tools: the paths are the exact slugs with the final slash', array( '/ask-ai/', '/gastro-meal-planner/', '/gastro-health-survey/' ) === array_column( $tools, 'path' ) );
foreach ( $tools as $t ) {
	$u = vance_marketing_tool_url( $t['path'] );
	check( 'link ' . $t['path'] . ': keeps the three utm tags', false !== strpos( $u, 'utm_source=instagram' ) && false !== strpos( $u, 'utm_medium=bio' ) && false !== strpos( $u, 'utm_campaign=wk1' ) );
	check( 'link ' . $t['path'] . ': passes no other parameter on', false === strpos( $u, 'evil' ) );
	check( 'link ' . $t['path'] . ': the path is followed by the query with the final slash intact', 0 === strpos( $u, 'https://example.test' . $t['path'] . '?' ) );
}
$_GET = array();
check( 'link: with no tags the link is the bare path', 'https://example.test/ask-ai/' === vance_marketing_tool_url( '/ask-ai/' ) );

/* ---- 5. waitlist handler ------------------------------------------------------------------ */
section( '5. The waitlist asks for consent and for no health information' );
$r = waitlist_call( $good );
check( 'waitlist: a good request succeeds', $r && $r->ok );
check( 'waitlist: the consent is recorded with the wording, the version and a time', 1 === count( $GLOBALS['OPTIONS']['vance_waitlist_consents'] ) && $GLOBALS['OPTIONS']['vance_waitlist_consents'][0]['text'] === vance_waitlist_consent_text() && 'draft-1' === $GLOBALS['OPTIONS']['vance_waitlist_consents'][0]['version'] && ! empty( $GLOBALS['OPTIONS']['vance_waitlist_consents'][0]['at'] ) );
check( 'waitlist: the CRM contact is pending and tagged for the waitlist', 1 === count( $GLOBALS['CRM'] ) && 'pending' === $GLOBALS['CRM'][0]['status'] && in_array( 'waitlist-b-vance', $GLOBALS['CRM'][0]['tags'], true ) );
$crm_text = json_encode( $GLOBALS['CRM'][0] );
check( 'waitlist: the contact is NOT added to a newsletter list or tag', false === stripos( $crm_text, 'newsletter' ) && false === stripos( $crm_text, 'Members' ) && false === stripos( $crm_text, 'marketing-opt-in' ) );
check( 'waitlist: the contact holds no health field', array( 'email', 'first_name', 'status', 'tags', 'lists', 'source' ) === array_keys( $GLOBALS['CRM'][0] ) );

$before = count( $GLOBALS['OPTIONS']['vance_waitlist_consents'] );
$bad = $good; unset( $bad['consent'] );
$r = waitlist_call( $bad );
check( 'waitlist: no consent is refused', $r && ! $r->ok && 422 === $r->status && $before === count( $GLOBALS['OPTIONS']['vance_waitlist_consents'] ) );
$bad = $good; $bad['consent'] = '0';
$r = waitlist_call( $bad );
check( 'waitlist: consent "0" is refused', $r && ! $r->ok );
$bad = $good; $bad['email'] = 'not-an-email';
$r = waitlist_call( $bad );
check( 'waitlist: a bad address is refused', $r && ! $r->ok && 422 === $r->status );
$bad = $good; $bad['nonce'] = 'bad';
$r = waitlist_call( $bad );
check( 'waitlist: a bad nonce is refused', $r && ! $r->ok && 403 === $r->status );
$n = count( $GLOBALS['CRM'] );
$bad = $good; $bad['website'] = 'http://spam.test';
$r = waitlist_call( $bad );
check( 'waitlist: the honeypot is dropped silently, with no record and no CRM call', $r && $r->ok && $n === count( $GLOBALS['CRM'] ) && $before === count( $GLOBALS['OPTIONS']['vance_waitlist_consents'] ) );
$ext = vance_waitlist_export( 'a@example.test' );
check( 'privacy: the export returns the record', 1 === count( $ext['data'] ) );
vance_waitlist_erase( 'a@example.test' );
check( 'privacy: the eraser removes the record', 0 === count( vance_waitlist_export( 'a@example.test' )['data'] ) );

/* ---- 6. templates and scripts, read as text ---------------------------------------------------- */
section( '6. Templates: no health question, no link from the tools, noindex rules' );
$wl  = file_get_contents( $THEME . '/page-waitlist.php' );
$wl  = substr( $wl, strpos( $wl, 'get_header' ) ); // the rendered part, not the file comment
$wljs = file_get_contents( $THEME . '/assets/js/waitlist.js' );
check( 'waitlist page: no textarea, no select, no radio, no condition or symptom word', ! preg_match( '/<textarea|<select|type="radio"|symptom|condition|diagnos|ibs|crohn|colitis/i', $wl ) );
check( 'waitlist page: the form has exactly email, first name, honeypot and consent', 4 === preg_match_all( '/<input /', $wl ) && false !== strpos( $wl, 'name="email"' ) && false !== strpos( $wl, 'name="consent"' ) );
check( 'waitlist page: the consent box is not ticked by default', ! preg_match( '/name="consent"[^>]*checked/', $wl ) );
check( 'waitlist page: the only checkbox is the product consent (no newsletter box)', 1 === preg_match_all( '/type="checkbox"/', $wl ) );
preg_match_all( "/body\\.set\\('([a-z_]+)'/", $wljs, $m );
check( 'waitlist script: posts only action, nonce, email, first_name, website and consent', array( 'action', 'nonce', 'email', 'first_name', 'website', 'consent' ) === $m[1] );
$linked = false;
foreach ( glob( $THEME . '/*.php' ) as $f ) {
	if ( basename( $f ) === 'page-waitlist.php' ) { continue; }
	if ( false !== strpos( file_get_contents( $f ), '/waitlist/' ) ) { $linked = true; }
}
foreach ( glob( $THEME . '/inc/*.php' ) as $f ) {
	if ( in_array( basename( $f ), array( 'marketing-pages.php', 'waitlist.php' ), true ) ) { continue; }
	if ( false !== strpos( file_get_contents( $f ), '/waitlist/' ) ) { $linked = true; }
}
check( 'waitlist: no other theme file links to /waitlist/', ! $linked );
$tpl = file_get_contents( $THEME . '/page-ibs-tools.php' );
check( 'ibs-tools page: no sign-up form and no redirect', ! preg_match( '/<form|wp_redirect|header\( *.Location/i', $tpl ) );
$ev = file_get_contents( $THEME . '/assets/js/vance-events.js' );
check( 'events script: the tool list matches vance_event_tools()', 1 === preg_match( '/ask_ai: 1, meal_planner: 1, self_assessment: 1/', $ev ) && array( 'ask_ai', 'meal_planner', 'self_assessment' ) === vance_event_tools() );
check( 'events script: the pushed object has only an event name and a tool name', 1 === preg_match( '/push\(\{ event: .vance_tool_. \+ stage, vance_tool: tool \}\)/', $ev ) );

/* ---- 7. pages the code keeps ----------------------------------------------------------------------- */
section( '7. The page registry' );
$pages = vance_marketing_pages();
check( 'registry: the four pages', array( 'community', 'community-rules', 'ibs-tools', 'waitlist' ) === array_keys( $pages ) );
check( 'registry: the waitlist and the rules are made as drafts, the others published', 'draft' === $pages['waitlist']['status'] && 'draft' === $pages['community-rules']['status'] && 'publish' === $pages['ibs-tools']['status'] && 'publish' === $pages['community']['status'] );
foreach ( array( 'page-community-rules.php', 'page-ibs-tools.php', 'page-waitlist.php' ) as $f ) {
	check( 'template file exists: ' . $f, is_file( $THEME . '/' . $f ) );
}

/* ---- result -------------------------------------------------------------------------------------------- */
if ( $GLOBALS['FAILS'] ) {
	echo count( $GLOBALS['FAILS'] ) . " FAILED of " . ( $GLOBALS['PASS'] + count( $GLOBALS['FAILS'] ) ) . ":\n";
	foreach ( $GLOBALS['FAILS'] as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "\nOK — {$GLOBALS['PASS']} checks passed.\n";
