<?php
/**
 * Tests for the VanceHubClub Community hub, the logic side:
 * inc/hub-community.php (switch, page gate, data model, member writes, polls,
 * privacy), inc/hub-community-admin.php (moderation handler, queue rows, poll
 * editor save) and the switch's wiring in inc/hub-header.php.
 *
 * Same shape as hub-tool-result.test.php: WordPress stubs, a bag of globals the
 * stubs read, check() lines, and exit 1 on any failure. The stubs are shared
 * with hub-community-view.test.php: tests/hub-community-stubs.php.
 *
 * WHAT IS COVERED
 *   1  the switch: resolve, active (administrator preview), who may write
 *   2  the page gate: 404 for visitors when off, no-cache for members, template,
 *      robots, enqueue, body class, the page created on save, the header link
 *   3  types, capability and seeded spaces (no public URL; moderation capability)
 *   4  member writes: hooks; visitor, logged-out, bad nonce and switch-off refusals
 *      for all seven actions
 *   5  ask: pending state, validation, length limits, tags, daily limit
 *   6  reply: comment_approved 0, state waiting, name and no contact data, limits
 *   7  me-too and helpful: toggle, count from stored rows, approved only
 *   8  poll votes: one vote per member, result after vote, closed poll, answers
 *   9  reports: state and hiding, fixed reasons, one per member, daily limit
 *  10  display names and the name settings (never email, never surname)
 *  11  text in: tags, control characters, limits, line breaks, emoji lengths
 *  12  product flag
 *  13  moderation: nonce and capability, approve, reject reasons, queue rows
 *  14  poll editor: answers, one active poll, votes kept, notices
 *  15  privacy exporter and eraser
 *
 * A check whose label says "(DEFECT: ...)" guards a bug that was found and fixed.
 * The note says what used to go wrong. Every check must pass; a red run is a real
 * failure.
 */

require_once __DIR__ . '/hub-community-stubs.php';

set_error_handler( function ( $no, $str, $file, $line ) {
	if ( ! ( error_reporting() & $no ) ) {
		return false;
	}
	throw new ErrorException( $str, 0, $no, $file, $line );
} );

require_once $THEME . '/inc/hub-layout.php';
require_once $THEME . '/inc/hub-header.php';
require_once $THEME . '/inc/hub-community.php';
require_once $THEME . '/inc/hub-community-view.php';
require_once $THEME . '/inc/hub-community-admin.php';

/* ---- helpers: each one runs code and returns what it captured ---------------------- */

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/** Resets the store, then sets the user, their caps and the mods. The spaces are seeded as on a real site. */
function arrange( array $mods = array(), array $caps = array(), $user = 0 ) {
	cs_reset();
	$GLOBALS['WP']['mods'] = $mods;
	$GLOBALS['WP']['caps'] = $caps;
	$GLOBALS['WP']['user'] = (int) $user;
	vance_hub_header_existing_page_url( '', '', true );
	vance_community_register_types();
	vance_community_seed_spaces();
}
/** Changes the signed-in user without resetting the store. */
function act_as( $user, array $caps ) {
	$GLOBALS['WP']['user'] = (int) $user;
	$GLOBALS['WP']['caps'] = $caps;
}
function admin_caps() {
	return array( 'vance_moderate_community' => true, 'edit_theme_options' => true, 'publish_pages' => true, 'manage_options' => true );
}
function on_mods() {
	return array( 'vance_hub_community' => true );
}
function cs_page( $slug, $status = 'publish' ) {
	return wp_insert_post( array( 'post_type' => 'page', 'post_status' => $status, 'post_name' => $slug, 'post_title' => $slug ) );
}
function cs_poll( array $answers, $status = 'publish', $active = true, $title = 'Which symptom is worst this week?' ) {
	$id = wp_insert_post( array( 'post_type' => 'vance_poll', 'post_status' => $status, 'post_title' => $title ) );
	update_post_meta( $id, '_vance_community_poll_answers', $answers );
	if ( $active ) {
		update_post_meta( $id, '_vance_community_poll_active', '1' );
	}
	return $id;
}
/** The JSON reply a handler sent, or null when it sent none. */
function reply_of( $fn ) {
	try { $fn(); } catch ( CsJsonReply $r ) { return $r; }
	return null;
}
/** array(status, code) of a refused AJAX request, or null when it was not refused. */
function refusal( $fn ) {
	$r = reply_of( $fn );
	if ( ! is_object( $r ) ) { return null; }
	return array( $r->status, isset( $r->data['code'] ) ? $r->data['code'] : '' );
}
/** The exception a handler ended with (wp_die), or null. */
function die_of( $fn ) {
	try { $fn(); } catch ( CsDie $d ) { return $d; } catch ( CsRedirect $r ) { return null; }
	return null;
}
/** The address a handler redirected to, or null. */
function redirect_of( $fn ) {
	try { $fn(); } catch ( CsRedirect $r ) { return $r->location; }
	return null;
}
function member_setup( $uid = 5, $email = 'mary@example.test', array $meta = array( 'first_name' => 'Mary' ) ) {
	return cs_user( $uid, $email, $meta );
}
function member_post( array $extra ) {
	$_POST = $extra + array( 'nonce' => 'nonce-vance_community_nonce' );
}
/** ID of the active poll, or 0 when none is active. */
function active_poll_id() {
	$p = vance_community_active_poll();
	return is_object( $p ) ? (int) $p->ID : 0;
}
/** True when $needle is in $hay. */
function has_str( $hay, $needle ) { return false !== strpos( (string) $hay, $needle ); }

/** Customizer stand-in: records what the register function adds. */
class CommunityCustomizeFake {
	public $settings = array();
	public $controls = array();
	public function add_setting( $id, $args = array() ) { $this->settings[ $id ] = $args; }
	public function add_control( $id, $args = array() ) { $this->controls[ $id ] = $args; }
}

/* ===================================================================== */

/* ---- 1. the switch -------------------------------------------------------- */
section( '1. The switch: resolve, active, and who may write' );

check( 'resolve: nothing saved and no preview is off', false === vance_hub_community_resolve( false, '', false ) );
check( 'resolve: saved "1" is on', true === vance_hub_community_resolve( '1', '', false ) );
check( 'resolve: saved true is on', true === vance_hub_community_resolve( true, '', false ) );
check( 'resolve: saved "" (an unticked box) is off', false === vance_hub_community_resolve( '', '', false ) );
check( 'resolve: saved "on" is on', true === vance_hub_community_resolve( 'on', '', false ) );
check( 'resolve: a preview of hub by a previewer turns the saved off on', true === vance_hub_community_resolve( false, 'hub', true ) );
check( 'resolve: a preview of hub by a visitor is ignored', false === vance_hub_community_resolve( false, 'hub', false ) );
check( 'resolve: a preview of classic turns a saved on off for a previewer', false === vance_hub_community_resolve( true, 'classic', true ) );
check( 'resolve: an unknown query value leaves the saved on in charge', true === vance_hub_community_resolve( true, 'evil', true ) );
check( 'resolve: the query is read case-insensitively for a previewer', true === vance_hub_community_resolve( false, 'HUB', true ) );

arrange();
check( 'active: nothing saved, a visitor: off', false === vance_hub_community_active() );
arrange( on_mods() );
check( 'active: saved on, a visitor: on', true === vance_hub_community_active() );
arrange( array(), admin_caps(), 1 );
$_GET = array( 'vhh_layout' => 'hub' );
check( 'active: an administrator previews with ?vhh_layout=hub while the switch is off', true === vance_hub_community_active() );
arrange( array(), array(), 0 );
$_GET = array( 'vhh_layout' => 'hub' );
check( 'active: a visitor with ?vhh_layout=hub is not read: off', false === vance_hub_community_active() );
arrange( on_mods(), admin_caps(), 1 );
$_GET = array( 'vhh_layout' => 'classic' );
check( 'active: an administrator with ?vhh_layout=classic turns a saved on off', false === vance_hub_community_active() );
arrange( array(), admin_caps(), 1 );
$_GET = array( 'vhh_layout' => array( 'hub' ) );
check( 'active: ?vhh_layout[]=hub (an array) does not throw and is ignored', false === vance_hub_community_active() );

arrange();
check( 'writes: a visitor with the switch off is refused', false === vance_community_writes_open() );
arrange( array(), array(), 5 );
check( 'writes: a member with the switch off is refused', false === vance_community_writes_open() );
arrange( on_mods(), array(), 5 );
check( 'writes: a member with the switch on is accepted', true === vance_community_writes_open() );
arrange( array(), admin_caps(), 1 );
check( 'writes: an administrator may write while the switch is off (the preview works end to end)', true === vance_community_writes_open() );

arrange();
$WC = new CommunityCustomizeFake();
vance_hub_community_customize_register( $WC );
check( 'customizer: the setting vance_hub_community defaults to false', isset( $WC->settings['vance_hub_community'] ) && false === $WC->settings['vance_hub_community']['default'] );
check( 'customizer: the setting is sanitised as a checkbox', isset( $WC->settings['vance_hub_community'] ) && 'vance_hub_sanitize_checkbox' === $WC->settings['vance_hub_community']['sanitize_callback'] );
check( 'customizer: the control is a checkbox in section vance_home_layout', isset( $WC->controls['vance_hub_community'] ) && 'checkbox' === $WC->controls['vance_hub_community']['type'] && 'vance_home_layout' === $WC->controls['vance_hub_community']['section'] );
check( 'customizer: the control is labelled Community hub', isset( $WC->controls['vance_hub_community'] ) && 'Community hub' === $WC->controls['vance_hub_community']['label'] );
check( 'customizer: the register function runs after the section is made (priority 11)', in_array( array( 'cb' => 'vance_hub_community_customize_register', 'priority' => 11 ), $GLOBALS['WP']['hooks']['customize_register'], true ) );

/* ---- 2. the page gate, the page, the header --------------------------------- */
section( '2. The page gate: 404, no-cache, template, robots, enqueue, body class, page, header' );

arrange( on_mods() );
$GLOBALS['WP']['page_slug'] = 'community';
check( 'on page: the community page with the switch on is "on"', true === vance_community_on_page() );

arrange();
$GLOBALS['WP']['page_slug'] = 'community';
check( 'on page: the community page with the switch off is not "on"', false === vance_community_on_page() );
check( 'is page: a page with the community slug is recognised even with the switch off', true === vance_community_is_page() );

arrange();
$GLOBALS['WP']['page_slug'] = 'community';
vance_community_template_redirect();
check( 'gate: switch off, a visitor: the community page answers 404', 404 === $GLOBALS['WP']['status'] );
check( 'gate: switch off: the main query is set to 404', true === $GLOBALS['wp_query']->is_404 );

arrange( on_mods() );
$GLOBALS['WP']['page_slug'] = 'community';
vance_community_template_redirect();
check( 'gate: switch on, a visitor: no 404', null === $GLOBALS['WP']['status'] );
check( 'gate: switch on, a visitor: the page is not sent as no-cache', false === $GLOBALS['WP']['nocache'] );

arrange( on_mods(), array(), 5 );
$GLOBALS['WP']['page_slug'] = 'community';
vance_community_template_redirect();
check( 'gate: switch on, a member: the page is sent no-cache (it carries a nonce)', true === $GLOBALS['WP']['nocache'] );

arrange( on_mods() );
$GLOBALS['WP']['page_slug'] = 'about-us';
vance_community_template_redirect();
check( 'gate: another page is left alone by the gate', null === $GLOBALS['WP']['status'] && false === $GLOBALS['WP']['nocache'] );

arrange();
$GLOBALS['WP']['page_slug'] = 'community';
check( 'template: switch off: the template WordPress chose is kept', '/theme/index.php' === vance_community_template_include( '/theme/index.php' ) );

arrange( on_mods() );
$GLOBALS['WP']['page_slug'] = 'community';
check( 'template: switch on, on the community page: page-community.php is used', $THEME . '/page-community.php' === vance_community_template_include( '/theme/index.php' ) );
$GLOBALS['WP']['page_slug'] = 'about-us';
check( 'template: switch on, another page keeps its own template', '/theme/index.php' === vance_community_template_include( '/theme/index.php' ) );

arrange();
$GLOBALS['WP']['page_slug'] = 'community';
$ROBOTS = vance_community_wp_robots( array( 'index' => true, 'follow' => true ) );
check( 'robots: the community page is noindex and follow, and index is removed', true === $ROBOTS['noindex'] && true === $ROBOTS['follow'] && ! array_key_exists( 'index', $ROBOTS ) );
$GLOBALS['WP']['page_slug'] = 'about-us';
$ROBOTS = vance_community_wp_robots( array( 'index' => true ) );
check( 'robots: another page is not changed', true === $ROBOTS['index'] && ! array_key_exists( 'noindex', $ROBOTS ) );
$GLOBALS['WP']['page_slug'] = 'community';
$AIO = vance_community_aioseo_robots( array( 'nofollow' => 'nofollow' ) );
check( 'robots: AIOSEO also gets noindex on the community page', 'noindex' === $AIO['noindex'] );

arrange( on_mods() );
$GLOBALS['WP']['page_slug'] = 'community';
vance_community_enqueue();
$ENQ = $GLOBALS['WP']['enqueued'];
check( 'enqueue: switch on, community page: the stylesheet follows the main style', 2 === count( $ENQ ) && 'style' === $ENQ[0][0] && 'vance-hub-community' === $ENQ[0][1] && array( 'vance-main-style' ) === $ENQ[0][3] );
check( 'enqueue: switch on, community page: hub-community.js is loaded with no dependencies', 'script' === $ENQ[1][0] && 'vance-hub-community' === $ENQ[1][1] && array() === $ENQ[1][3] && has_str( $ENQ[1][2], '/assets/js/hub-community.js' ) );

arrange();
$GLOBALS['WP']['page_slug'] = 'community';
vance_community_enqueue();
check( 'enqueue: switch off: nothing is loaded', array() === $GLOBALS['WP']['enqueued'] );

arrange( on_mods() );
$GLOBALS['WP']['page_slug'] = 'about-us';
vance_community_enqueue();
check( 'enqueue: switch on, another page: nothing is loaded', array() === $GLOBALS['WP']['enqueued'] );

arrange( on_mods() );
$GLOBALS['WP']['page_slug'] = 'community';
check( 'body class: the community page gets vcom-page', in_array( 'vcom-page', vance_community_body_class( array( 'page' ) ), true ) );
arrange();
$GLOBALS['WP']['page_slug'] = 'community';
check( 'body class: switch off: no vcom-page', ! in_array( 'vcom-page', vance_community_body_class( array( 'page' ) ), true ) );

arrange();
cs_page( 'tools-resources' );
cs_page( 'community' );
check( 'header: switch off: no Community item, even with the page there', ! in_array( 'Community', array_column( vance_hub_header_fallback_items(), 'label' ), true ) );

arrange( on_mods() );
cs_page( 'tools-resources' );
cs_page( 'community' );
$ITEMS = vance_hub_header_fallback_items();
check( 'header: switch on with the page: Community sits right after Tools', array( 'Tools', 'Community' ) === array_slice( array_column( $ITEMS, 'label' ), 0, 2 ) );
check( 'header: the Community item links to the community page', isset( $ITEMS[1]['url'] ) && 'https://example.test/community/' === $ITEMS[1]['url'] );

arrange( on_mods() );
cs_page( 'tools-resources' );
check( 'header: switch on but no community page: no Community item', ! in_array( 'Community', array_column( vance_hub_header_fallback_items(), 'label' ), true ) );

arrange( on_mods(), admin_caps(), 1 );
vance_community_maybe_create_page();
$MADE = get_page_by_path( 'community' );
check( 'page: saving the switch on makes the community page when none exists', is_object( $MADE ) && 'publish' === $MADE->post_status );
check( 'page: the new page uses the community template', is_object( $MADE ) && 'page-community.php' === get_post_meta( $MADE->ID, '_wp_page_template', true ) );

arrange( on_mods(), admin_caps(), 1 );
$EXISTING = cs_page( 'community', 'publish' );
vance_community_maybe_create_page();
check( 'page: an existing published community page is not duplicated or changed', 1 === count( get_posts( array( 'post_type' => 'page', 'name' => 'community', 'post_status' => 'any' ) ) ) && '' === get_post_meta( $EXISTING, '_wp_page_template', true ) );

arrange( on_mods(), admin_caps(), 1 );
cs_page( 'community', 'draft' );
vance_community_maybe_create_page();
check( 'page: an existing draft community page is not duplicated', 1 === count( get_posts( array( 'post_type' => 'page', 'name' => 'community', 'post_status' => 'any' ) ) ) );

arrange( on_mods(), admin_caps(), 1 );
cs_page( 'community__trashed', 'trash' );
vance_community_maybe_create_page();
check( 'page: a community page in the bin is not replaced by a new one', null === get_page_by_path( 'community' ) );

arrange( array(), admin_caps(), 1 );
vance_community_maybe_create_page();
check( 'page: switch off: no page is made', null === get_page_by_path( 'community' ) );

arrange( on_mods(), array(), 5 );
vance_community_maybe_create_page();
check( 'page: a member who cannot edit the theme does not make the page', null === get_page_by_path( 'community' ) );

/* ---- 3. types, capability, seeded spaces ------------------------------------ */
section( '3. Types, capability and the seeded spaces' );

arrange();
$QT = $GLOBALS['WP']['post_types']['vance_question'];
check( 'types: vance_question is not public and has no public URL', false === $QT['public'] && false === $QT['publicly_queryable'] && false === $QT['rewrite'] && false === $QT['has_archive'] );
check( 'types: vance_question is kept out of search and the REST API', true === $QT['exclude_from_search'] && false === $QT['show_in_rest'] );
check( 'types: vance_question is in no menu', false === $QT['show_in_menu'] && false === $QT['show_in_nav_menus'] );
check( 'types: every capability of vance_question is vance_moderate_community', array( 'vance_moderate_community' ) === array_values( array_unique( array_values( $QT['capabilities'] ) ) ) );
$PT = $GLOBALS['WP']['post_types']['vance_poll'];
check( 'types: vance_poll is not public and has no public URL', false === $PT['public'] && false === $PT['publicly_queryable'] && false === $PT['rewrite'] );
check( 'types: vance_poll uses the moderation capability for every capability', array( 'vance_moderate_community' ) === array_values( array_unique( array_values( $PT['capabilities'] ) ) ) );
$TX = $GLOBALS['WP']['taxonomies']['vance_space'];
check( 'types: the vance_space taxonomy is private and out of the REST API', false === $TX['public'] && false === $TX['show_in_rest'] && false === $TX['rewrite'] );
check( 'types: managing the spaces needs the moderation capability', 'vance_moderate_community' === $TX['capabilities']['manage_terms'] && 'vance_moderate_community' === $TX['capabilities']['assign_terms'] );

arrange();
vance_community_grant_caps();
check( 'caps: administrators are given vance_moderate_community', true === get_role( 'administrator' )->has_cap( 'vance_moderate_community' ) );
check( 'caps: editors are given vance_moderate_community', true === get_role( 'editor' )->has_cap( 'vance_moderate_community' ) );
check( 'caps: authors are not given vance_moderate_community', false === get_role( 'author' )->has_cap( 'vance_moderate_community' ) );
check( 'caps: subscribers are not given vance_moderate_community', false === get_role( 'subscriber' )->has_cap( 'vance_moderate_community' ) );

arrange();
$SLUGS = array_keys( vance_community_space_terms() );
check( 'spaces: the seven spaces exist after the seed, in the fixed order', array( 'ibd', 'ulcerative-colitis', 'crohns-disease', 'ibs', 'food-and-recipes', 'work-and-travel', 'carers' ) === $SLUGS );
check( 'spaces: the seed sets its flag once every space exists', 1 === get_option( 'vance_community_spaces_seeded' ) );
vance_community_seed_spaces();
check( 'spaces: a second seed adds no spaces', 7 === count( get_terms( array( 'taxonomy' => 'vance_space' ) ) ) );

arrange();
$GLOBALS['WP']['terms'][ get_term_by( 'slug', 'ibd', 'vance_space' )->term_id ]->name = 'IBD (renamed)';
update_option( 'vance_community_spaces_seeded', false );
vance_community_seed_spaces();
check( 'spaces: a moderator rename survives a re-seed', 'IBD (renamed)' === get_term_by( 'slug', 'ibd', 'vance_space' )->name );

arrange();
wp_insert_term( 'Pharmacy', 'vance_space', array( 'slug' => 'pharmacy' ) );
$KEYS = array_keys( vance_community_space_terms() );
check( 'spaces: a space a moderator adds goes after the seven', 8 === count( $KEYS ) && 'pharmacy' === $KEYS[7] );

/* ---- 4. member writes: hooks and refusals ---------------------------------- */
section( '4. Member writes: hooks, and refusals for visitors, nonces and the switch' );

arrange( on_mods(), array(), 5 );
check( 'hooks: vance_community_ask is handled for members', cs_hooked( 'wp_ajax_vance_community_ask', 'vance_community_ajax_ask' ) );
check( 'hooks: vance_community_reply is handled for members', cs_hooked( 'wp_ajax_vance_community_reply', 'vance_community_ajax_reply' ) );
check( 'hooks: vance_community_metoo is handled for members', cs_hooked( 'wp_ajax_vance_community_metoo', 'vance_community_ajax_metoo' ) );
check( 'hooks: vance_community_helpful is handled for members', cs_hooked( 'wp_ajax_vance_community_helpful', 'vance_community_ajax_helpful' ) );
check( 'hooks: vance_community_vote is handled for members', cs_hooked( 'wp_ajax_vance_community_vote', 'vance_community_ajax_vote' ) );
check( 'hooks: vance_community_report is handled for members', cs_hooked( 'wp_ajax_vance_community_report', 'vance_community_ajax_report' ) );
check( 'hooks: vance_community_profile is handled for members', cs_hooked( 'wp_ajax_vance_community_profile', 'vance_community_ajax_profile' ) );
check( 'hooks: the logged-out ask answers "join first"', cs_hooked( 'wp_ajax_nopriv_vance_community_ask', 'vance_community_ajax_visitor' ) );
check( 'hooks: the logged-out reply answers "join first"', cs_hooked( 'wp_ajax_nopriv_vance_community_reply', 'vance_community_ajax_visitor' ) );
check( 'hooks: the logged-out metoo answers "join first"', cs_hooked( 'wp_ajax_nopriv_vance_community_metoo', 'vance_community_ajax_visitor' ) );
check( 'hooks: the logged-out helpful answers "join first"', cs_hooked( 'wp_ajax_nopriv_vance_community_helpful', 'vance_community_ajax_visitor' ) );
check( 'hooks: the logged-out vote answers "join first"', cs_hooked( 'wp_ajax_nopriv_vance_community_vote', 'vance_community_ajax_visitor' ) );
check( 'hooks: the logged-out report answers "join first"', cs_hooked( 'wp_ajax_nopriv_vance_community_report', 'vance_community_ajax_visitor' ) );
check( 'hooks: the logged-out profile answers "join first"', cs_hooked( 'wp_ajax_nopriv_vance_community_profile', 'vance_community_ajax_visitor' ) );

arrange( on_mods() );
$_POST = array();
$VIS = reply_of( 'vance_community_ajax_visitor' );
check( 'visitor: the join answer is 401 with code login and the join address', is_object( $VIS ) && 401 === $VIS->status && 'login' === $VIS->data['code'] && has_str( $VIS->data['join'], '/login/?tab=signup' ) );

arrange( on_mods() );
check( 'logged out: ask refuses with 401 login', array( 401, 'login' ) === refusal( 'vance_community_ajax_ask' ) );
check( 'logged out: reply refuses with 401 login', array( 401, 'login' ) === refusal( 'vance_community_ajax_reply' ) );
check( 'logged out: metoo refuses with 401 login', array( 401, 'login' ) === refusal( 'vance_community_ajax_metoo' ) );
check( 'logged out: helpful refuses with 401 login', array( 401, 'login' ) === refusal( 'vance_community_ajax_helpful' ) );
check( 'logged out: vote refuses with 401 login', array( 401, 'login' ) === refusal( 'vance_community_ajax_vote' ) );
check( 'logged out: report refuses with 401 login', array( 401, 'login' ) === refusal( 'vance_community_ajax_report' ) );
check( 'logged out: profile refuses with 401 login', array( 401, 'login' ) === refusal( 'vance_community_ajax_profile' ) );

arrange( on_mods(), array(), 5 );
$_POST = array( 'nonce' => 'not-the-nonce' );
check( 'bad nonce: ask refuses with 403 nonce', array( 403, 'nonce' ) === refusal( 'vance_community_ajax_ask' ) );
check( 'bad nonce: reply refuses with 403 nonce', array( 403, 'nonce' ) === refusal( 'vance_community_ajax_reply' ) );
check( 'bad nonce: metoo refuses with 403 nonce', array( 403, 'nonce' ) === refusal( 'vance_community_ajax_metoo' ) );
check( 'bad nonce: helpful refuses with 403 nonce', array( 403, 'nonce' ) === refusal( 'vance_community_ajax_helpful' ) );
check( 'bad nonce: vote refuses with 403 nonce', array( 403, 'nonce' ) === refusal( 'vance_community_ajax_vote' ) );
check( 'bad nonce: report refuses with 403 nonce', array( 403, 'nonce' ) === refusal( 'vance_community_ajax_report' ) );
check( 'bad nonce: profile refuses with 403 nonce', array( 403, 'nonce' ) === refusal( 'vance_community_ajax_profile' ) );

arrange( on_mods(), array(), 5 );
$_POST = array();
check( 'missing nonce: ask refuses with 403 nonce', array( 403, 'nonce' ) === refusal( 'vance_community_ajax_ask' ) );

arrange( array(), array(), 5 );
$_POST = array( 'nonce' => 'nonce-vance_community_nonce' );
check( 'switch off, member: ask refuses with 403 closed', array( 403, 'closed' ) === refusal( 'vance_community_ajax_ask' ) );
check( 'switch off, member: reply refuses with 403 closed', array( 403, 'closed' ) === refusal( 'vance_community_ajax_reply' ) );
check( 'switch off, member: metoo refuses with 403 closed', array( 403, 'closed' ) === refusal( 'vance_community_ajax_metoo' ) );
check( 'switch off, member: helpful refuses with 403 closed', array( 403, 'closed' ) === refusal( 'vance_community_ajax_helpful' ) );
check( 'switch off, member: vote refuses with 403 closed', array( 403, 'closed' ) === refusal( 'vance_community_ajax_vote' ) );
check( 'switch off, member: report refuses with 403 closed', array( 403, 'closed' ) === refusal( 'vance_community_ajax_report' ) );
check( 'switch off, member: profile refuses with 403 closed', array( 403, 'closed' ) === refusal( 'vance_community_ajax_profile' ) );

arrange( array(), admin_caps(), 1 );
$_POST = array( 'nonce' => 'nonce-vance_community_nonce', 'title' => 'Preview question here', 'text' => '', 'space' => 'ibd' );
$PV = reply_of( 'vance_community_ajax_ask' );
check( 'switch off, administrator preview: ask is accepted (not closed)', is_object( $PV ) && true === $PV->success );

/* ---- 5. ask ------------------------------------------------------------------- */
section( '5. Ask: pending state, validation, limits' );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'title' => 'Is bloating with IBS normal?', 'text' => 'Looking for experiences.', 'space' => 'ibs' ) );
$R = reply_of( 'vance_community_ajax_ask' );
$QID = array_key_last( $GLOBALS['WP']['posts'] );
check( 'ask: a valid question is accepted with a success reply', is_object( $R ) && true === $R->success );
check( 'ask: the new question is saved as pending, so it waits for a moderator', 'pending' === $GLOBALS['WP']['posts'][ $QID ]->post_status );
check( 'ask: the new question is a vance_question by the member', 'vance_question' === $GLOBALS['WP']['posts'][ $QID ]->post_type && 5 === (int) $GLOBALS['WP']['posts'][ $QID ]->post_author );
check( 'ask: the new question has the state waiting', 'waiting' === get_post_meta( $QID, '_vance_community_state', true ) );
check( 'ask: the new question sits in the chosen space', 'ibs' === cs_space_of( $QID ) );
check( 'ask: the title and the text are stored as typed', 'Is bloating with IBS normal?' === $GLOBALS['WP']['posts'][ $QID ]->post_title && 'Looking for experiences.' === $GLOBALS['WP']['posts'][ $QID ]->post_content );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'title' => 'Help', 'text' => '', 'space' => 'ibd' ) );
$N = count( $GLOBALS['WP']['posts'] );
$R = reply_of( 'vance_community_ajax_ask' );
check( 'ask: a four-character title is refused with 400 (the minimum is five)', is_object( $R ) && false === $R->success && 400 === $R->status );
check( 'ask: a refused question is not saved', $N === count( $GLOBALS['WP']['posts'] ) );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'title' => 'Hello', 'text' => '', 'space' => 'ibd' ) );
$R = reply_of( 'vance_community_ajax_ask' );
check( 'ask: a five-character title is the smallest accepted', is_object( $R ) && true === $R->success );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'title' => '<b></b>', 'text' => '', 'space' => 'ibd' ) );
$R = reply_of( 'vance_community_ajax_ask' );
check( 'ask: a title made only of tags is refused with 400', is_object( $R ) && 400 === $R->status );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'title' => 'Hello there', 'text' => '', 'space' => '' ) );
$R = reply_of( 'vance_community_ajax_ask' );
check( 'ask: no space is refused with 400', is_object( $R ) && 400 === $R->status );
member_post( array( 'title' => 'Hello there', 'text' => '', 'space' => 'nonsense' ) );
$R = reply_of( 'vance_community_ajax_ask' );
check( 'ask: an unknown space is refused with 400', is_object( $R ) && 400 === $R->status );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'title' => str_repeat( 'a', 200 ), 'text' => '', 'space' => 'ibd' ) );
reply_of( 'vance_community_ajax_ask' );
$QID = array_key_last( $GLOBALS['WP']['posts'] );
check( 'ask: a title over 150 characters is cut to 150', 150 === mb_strlen( $GLOBALS['WP']['posts'][ $QID ]->post_title ) );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'title' => 'Long text', 'text' => str_repeat( 'b', 1200 ), 'space' => 'ibd' ) );
reply_of( 'vance_community_ajax_ask' );
$QID = array_key_last( $GLOBALS['WP']['posts'] );
check( 'ask: a body over 1000 characters is cut to 1000', 1000 === mb_strlen( $GLOBALS['WP']['posts'][ $QID ]->post_content ) );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'title' => str_repeat( "\xF0\x9F\x98\x80", 4 ), 'text' => '', 'space' => 'ibd' ) );
$R = reply_of( 'vance_community_ajax_ask' );
check( 'ask: four emoji are four characters, so a title of them is refused', is_object( $R ) && 400 === $R->status );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'title' => str_repeat( "\xF0\x9F\x98\x80", 150 ), 'text' => '', 'space' => 'ibd' ) );
reply_of( 'vance_community_ajax_ask' );
$QID = array_key_last( $GLOBALS['WP']['posts'] );
check( 'ask: a title of 150 emoji is kept whole (counted in characters, not bytes)', 150 === mb_strlen( $GLOBALS['WP']['posts'][ $QID ]->post_title ) );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'title' => '<script>alert(1)</script>Hello there', 'text' => '', 'space' => 'ibd' ) );
reply_of( 'vance_community_ajax_ask' );
$QID = array_key_last( $GLOBALS['WP']['posts'] );
check( 'ask: a script block in the title is removed with its content', 'Hello there' === $GLOBALS['WP']['posts'][ $QID ]->post_title );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'title' => "Hello\x07 there", 'text' => '', 'space' => 'ibd' ) );
reply_of( 'vance_community_ajax_ask' );
$QID = array_key_last( $GLOBALS['WP']['posts'] );
check( 'ask: a control character in the title is removed', 'Hello there' === $GLOBALS['WP']['posts'][ $QID ]->post_title );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'title' => "Hello\tthere", 'text' => '', 'space' => 'ibd' ) );
reply_of( 'vance_community_ajax_ask' );
$QID = array_key_last( $GLOBALS['WP']['posts'] );
check( 'ask: a tab between two words becomes a space (DEFECT: the tab is deleted, so the words join)', 'Hello there' === $GLOBALS['WP']['posts'][ $QID ]->post_title );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'title' => 'Line test', 'text' => "One\n\n\n\nTwo", 'space' => 'ibd' ) );
reply_of( 'vance_community_ajax_ask' );
$QID = array_key_last( $GLOBALS['WP']['posts'] );
check( 'ask: runs of blank lines in the body are cut to one blank line', "One\n\nTwo" === $GLOBALS['WP']['posts'][ $QID ]->post_content );

arrange( on_mods(), array(), 5 );
member_setup();
cs_repeat( 5, function ( $i ) {
	cs_seed_question( 5, 'waiting', 'Earlier question ' . $i, '', 3600 );
} );
member_post( array( 'title' => 'The sixth question', 'text' => '', 'space' => 'ibd' ) );
$N = count( $GLOBALS['WP']['posts'] );
$R = reply_of( 'vance_community_ajax_ask' );
check( 'limit: the sixth question in 24 hours is refused with 429', is_object( $R ) && false === $R->success && 429 === $R->status );
check( 'limit: a refused question is not saved', $N === count( $GLOBALS['WP']['posts'] ) );

arrange( on_mods(), array(), 5 );
member_setup();
cs_seed_question( 5, 'waiting', 'One', '', 3600 );
cs_seed_question( 5, 'approved', 'Two', '', 3600 );
cs_seed_question( 5, 'reported', 'Three', '', 3600 );
cs_seed_question( 5, 'rejected', 'Four', '', 3600 );
member_post( array( 'title' => 'The fifth question', 'text' => '', 'space' => 'ibd' ) );
$R = reply_of( 'vance_community_ajax_ask' );
check( 'limit: the fifth question is accepted, and a rejected one counts toward the limit', is_object( $R ) && true === $R->success );

arrange( on_mods(), array(), 5 );
member_setup();
cs_repeat( 5, function ( $i ) {
	cs_seed_question( 5, 'approved', 'Old question ' . $i, '', 90000 );
} );
member_post( array( 'title' => 'A fresh question', 'text' => '', 'space' => 'ibd' ) );
$R = reply_of( 'vance_community_ajax_ask' );
check( 'limit: questions older than 24 hours do not count', is_object( $R ) && true === $R->success );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
cs_repeat( 5, function ( $i ) {
	cs_seed_question( 6, 'waiting', 'Tom question ' . $i, '', 3600 );
} );
member_post( array( 'title' => 'Mary question here', 'text' => '', 'space' => 'ibd' ) );
$R = reply_of( 'vance_community_ajax_ask' );
check( 'limit: another member\'s questions do not use up this member\'s limit', is_object( $R ) && true === $R->success );

/* ---- 6. reply ----------------------------------------------------------------- */
section( '6. Reply: unapproved until read, name, no contact data, limits' );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$Q = cs_seed_question( 6, 'approved', 'A question to reply to', '', 0, 'ibd' );
member_post( array( 'question' => (string) $Q, 'text' => 'I have had this for years.' ) );
$R = reply_of( 'vance_community_ajax_reply' );
$CID = array_key_last( $GLOBALS['WP']['comments'] );
check( 'reply: a valid reply is accepted with a success reply', is_object( $R ) && true === $R->success );
check( 'reply: it is stored as a vance_reply on the question', 'vance_reply' === $GLOBALS['WP']['comments'][ $CID ]->comment_type && $Q === (int) $GLOBALS['WP']['comments'][ $CID ]->comment_post_ID );
check( 'reply: comment_approved is 0 until a moderator reads it', '0' === (string) $GLOBALS['WP']['comments'][ $CID ]->comment_approved );
check( 'reply: its state is waiting', 'waiting' === get_comment_meta( $CID, '_vance_community_state', true ) );
check( 'reply: the author name is the first name, never the email', 'Mary' === $GLOBALS['WP']['comments'][ $CID ]->comment_author );
check( 'reply: no email address or IP address is stored with the reply', '' === $GLOBALS['WP']['comments'][ $CID ]->comment_author_email && '' === $GLOBALS['WP']['comments'][ $CID ]->comment_author_IP );
check( 'reply: the reply belongs to the member', 5 === (int) $GLOBALS['WP']['comments'][ $CID ]->user_id );

arrange( on_mods(), array(), 5 );
member_setup();
$WQ = cs_seed_question( 6, 'waiting', 'Not yet public', '', 0, 'ibd' );
member_post( array( 'question' => (string) $WQ, 'text' => 'Reply to a waiting question' ) );
$R = reply_of( 'vance_community_ajax_reply' );
check( 'reply: a question that is not published cannot be replied to (404)', is_object( $R ) && 404 === $R->status );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'question' => '0', 'text' => 'Reply to nothing' ) );
$R = reply_of( 'vance_community_ajax_reply' );
check( 'reply: a missing question is refused with 404', is_object( $R ) && 404 === $R->status );

arrange( on_mods(), array(), 5 );
member_setup();
$Q = cs_seed_question( 6, 'approved', 'Short reply test', '', 0, 'ibd' );
member_post( array( 'question' => (string) $Q, 'text' => 'a' ) );
$R = reply_of( 'vance_community_ajax_reply' );
check( 'reply: a one-character reply is refused with 400 (the minimum is two)', is_object( $R ) && 400 === $R->status );

arrange( on_mods(), array(), 5 );
member_setup();
$Q = cs_seed_question( 6, 'approved', 'Long reply test', '', 0, 'ibd' );
member_post( array( 'question' => (string) $Q, 'text' => str_repeat( 'c', 1200 ) ) );
reply_of( 'vance_community_ajax_reply' );
$CID = array_key_last( $GLOBALS['WP']['comments'] );
check( 'reply: a reply over 1000 characters is cut to 1000', 1000 === mb_strlen( $GLOBALS['WP']['comments'][ $CID ]->comment_content ) );

arrange( on_mods(), array(), 5 );
member_setup();
$Q = cs_seed_question( 6, 'approved', 'Tag test', '', 0, 'ibd' );
member_post( array( 'question' => (string) $Q, 'text' => '<img src=x onerror=alert(1)>Thanks for asking' ) );
reply_of( 'vance_community_ajax_reply' );
$CID = array_key_last( $GLOBALS['WP']['comments'] );
check( 'reply: tags are stripped from the reply text', 'Thanks for asking' === $GLOBALS['WP']['comments'][ $CID ]->comment_content );

arrange( on_mods(), array(), 5 );
member_setup();
$Q = cs_seed_question( 6, 'approved', 'Daily reply limit', '', 0, 'ibd' );
cs_repeat( 20, function ( $i ) use ( $Q ) {
	cs_seed_reply( $Q, 5, 'Earlier reply ' . $i, 'waiting', 3600 );
} );
member_post( array( 'question' => (string) $Q, 'text' => 'The twenty-first reply' ) );
$N = count( $GLOBALS['WP']['comments'] );
$R = reply_of( 'vance_community_ajax_reply' );
check( 'limit: the twenty-first reply in 24 hours is refused with 429', is_object( $R ) && 429 === $R->status );
check( 'limit: a refused reply is not saved', $N === count( $GLOBALS['WP']['comments'] ) );

arrange( on_mods(), array(), 5 );
member_setup();
$Q = cs_seed_question( 6, 'approved', 'Limit nineteen', '', 0, 'ibd' );
cs_repeat( 19, function ( $i ) use ( $Q ) {
	cs_seed_reply( $Q, 5, 'Earlier reply ' . $i, 'waiting', 3600 );
} );
member_post( array( 'question' => (string) $Q, 'text' => 'The twentieth reply' ) );
$R = reply_of( 'vance_community_ajax_reply' );
check( 'limit: the twentieth reply is accepted', is_object( $R ) && true === $R->success );

arrange( on_mods(), array(), 5 );
member_setup();
$Q = cs_seed_question( 6, 'approved', 'Old replies', '', 0, 'ibd' );
cs_repeat( 20, function ( $i ) use ( $Q ) {
	cs_seed_reply( $Q, 5, 'Old reply ' . $i, 'approved', 90000 );
} );
member_post( array( 'question' => (string) $Q, 'text' => 'A new reply' ) );
$R = reply_of( 'vance_community_ajax_reply' );
check( 'limit: replies older than 24 hours do not count', is_object( $R ) && true === $R->success );

/* ---- 7. me-too and helpful ------------------------------------------------------ */
section( '7. Me too and Helpful: toggle, count from stored rows' );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$Q = cs_seed_question( 6, 'approved', 'Me too question', '', 0, 'ibd' );
member_post( array( 'id' => (string) $Q ) );
$T1 = reply_of( 'vance_community_ajax_metoo' );
check( 'metoo: the first tap turns it on', is_object( $T1 ) && true === $T1->data['active'] );
check( 'metoo: the count is 1 after the first tap', is_object( $T1 ) && 1 === $T1->data['count'] );
$T2 = reply_of( 'vance_community_ajax_metoo' );
check( 'metoo: the second tap turns it off (the row is removed)', is_object( $T2 ) && false === $T2->data['active'] );
check( 'metoo: the count is 0 after the second tap', is_object( $T2 ) && 0 === $T2->data['count'] );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$Q = cs_seed_question( 6, 'approved', 'Counted from rows', '', 0, 'ibd' );
add_post_meta( $Q, '_vance_community_metoo', '6' );
add_post_meta( $Q, '_vance_community_metoo', '7' );
member_post( array( 'id' => (string) $Q ) );
$T = reply_of( 'vance_community_ajax_metoo' );
check( 'metoo: the count is the stored rows: two others and this member make 3', is_object( $T ) && 3 === $T->data['count'] );

arrange( on_mods(), array(), 5 );
member_setup();
$Q = cs_seed_question( 6, 'waiting', 'Not public yet', '', 0, 'ibd' );
member_post( array( 'id' => (string) $Q ) );
$T = reply_of( 'vance_community_ajax_metoo' );
check( 'metoo: a question that is not published is refused with 404', is_object( $T ) && 404 === $T->status );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$Q  = cs_seed_question( 6, 'approved', 'Helpful test question', '', 0, 'ibd' );
$RC = cs_seed_reply( $Q, 6, 'A reply that helped', 'approved' );
member_post( array( 'id' => (string) $RC ) );
$H1 = reply_of( 'vance_community_ajax_helpful' );
check( 'helpful: the first tap turns it on with a count of 1', is_object( $H1 ) && true === $H1->data['active'] && 1 === $H1->data['count'] );
$H2 = reply_of( 'vance_community_ajax_helpful' );
check( 'helpful: the second tap turns it off with a count of 0', is_object( $H2 ) && false === $H2->data['active'] && 0 === $H2->data['count'] );

arrange( on_mods(), array(), 5 );
member_setup();
$Q  = cs_seed_question( 6, 'approved', 'Helpful waiting test', '', 0, 'ibd' );
$RC = cs_seed_reply( $Q, 6, 'Reply still waiting', 'waiting' );
member_post( array( 'id' => (string) $RC ) );
$H = reply_of( 'vance_community_ajax_helpful' );
check( 'helpful: a reply that is not approved is refused with 404', is_object( $H ) && 404 === $H->status );

/* ---- 8. poll votes --------------------------------------------------------------- */
section( '8. Poll votes: one per member, the result after the vote, a closed poll' );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$P = cs_poll( array( 'Bloating', 'Pain', 'Tiredness' ) );
member_post( array( 'poll' => (string) $P, 'answer' => '1' ) );
$V = reply_of( 'vance_community_ajax_vote' );
check( 'vote: the first vote is accepted', is_object( $V ) && true === $V->success );
check( 'vote: the answer is stored for this member and this poll', '1' === (string) get_user_meta( 5, '_vance_community_vote_' . $P, true ) );
check( 'vote: the reply is the result block, with "(your vote)" on the chosen answer', is_object( $V ) && has_str( $V->data['html'], 'vcom-poll__results' ) && has_str( $V->data['html'], '<em>(your vote)</em>' ) );
check( 'vote: the reply holds no vote buttons any more', is_object( $V ) && ! has_str( $V->data['html'], 'data-vcom-vote' ) );
member_post( array( 'poll' => (string) $P, 'answer' => '2' ) );
$V2 = reply_of( 'vance_community_ajax_vote' );
check( 'vote: a second vote is not stored, so the first answer stands', '1' === (string) get_user_meta( 5, '_vance_community_vote_' . $P, true ) );
check( 'vote: a second vote is answered with the same result, not an error', is_object( $V2 ) && true === $V2->success );

arrange( on_mods(), array(), 6 );
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$P = cs_poll( array( 'Bloating', 'Pain', 'Tiredness' ) );
member_post( array( 'poll' => (string) $P, 'answer' => '3' ) );
$V = reply_of( 'vance_community_ajax_vote' );
check( 'vote: an answer past the last one is refused with 400, and nothing is stored', is_object( $V ) && 400 === $V->status && '' === (string) get_user_meta( 6, '_vance_community_vote_' . $P, true ) );
member_post( array( 'poll' => (string) $P, 'answer' => 'x' ) );
$V = reply_of( 'vance_community_ajax_vote' );
check( 'vote: a non-number answer is refused with 400', is_object( $V ) && 400 === $V->status );
member_post( array( 'poll' => (string) $P, 'answer' => '-1' ) );
$V = reply_of( 'vance_community_ajax_vote' );
check( 'vote: a negative answer is refused with 400', is_object( $V ) && 400 === $V->status );
member_post( array( 'poll' => (string) $P, 'answer' => '' ) );
$V = reply_of( 'vance_community_ajax_vote' );
check( 'vote: an empty answer is refused with 400', is_object( $V ) && 400 === $V->status );

arrange( on_mods(), array(), 5 );
member_setup();
$P = cs_poll( array( 'Bloating', 'Pain', 'Tiredness' ), 'publish', false );
member_post( array( 'poll' => (string) $P, 'answer' => '0' ) );
$V = reply_of( 'vance_community_ajax_vote' );
check( 'vote: a poll that is not active is closed (404)', is_object( $V ) && 404 === $V->status );

arrange( on_mods(), array(), 5 );
member_setup();
$OLD = cs_poll( array( 'Bloating', 'Pain', 'Tiredness' ), 'publish', false );
$NEW = cs_poll( array( 'Bloating', 'Pain', 'Tiredness' ) );
member_post( array( 'poll' => (string) $OLD, 'answer' => '0' ) );
$V = reply_of( 'vance_community_ajax_vote' );
check( 'vote: a vote for a poll that is not the active one is refused (404)', is_object( $V ) && 404 === $V->status );

arrange( on_mods(), array(), 5 );
member_setup();
$P2 = cs_poll( array( 'Only', 'Two' ) );
member_post( array( 'poll' => (string) $P2, 'answer' => '0' ) );
$V = reply_of( 'vance_community_ajax_vote' );
check( 'vote: a poll with fewer than three answers takes no vote', is_object( $V ) && 400 === $V->status );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$P = cs_poll( array( 'Bloating', 'Pain', 'Tiredness' ) );
add_user_meta( 6, '_vance_community_vote_' . $P, '0' );
member_post( array( 'poll' => (string) $P, 'answer' => '1' ) );
reply_of( 'vance_community_ajax_vote' );
$C = vance_community_poll_counts( $P );
check( 'vote: one vote per member, counted from the stored rows (one for each of two members)', 1 === $C[1] && 1 === $C[0] );

/* ---- 9. reports ------------------------------------------------------------------ */
section( '9. Reports: hidden, back in the queue, fixed reasons, one per member, daily limit' );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$Q = cs_seed_question( 6, 'approved', 'A question to report', '', 0, 'ibd' );
member_post( array( 'kind' => 'question', 'id' => (string) $Q, 'reason' => 'spam' ) );
$R = reply_of( 'vance_community_ajax_report' );
check( 'report: a valid report is accepted', is_object( $R ) && true === $R->success );
check( 'report: the question is hidden at once (its post status is pending)', 'pending' === $GLOBALS['WP']['posts'][ $Q ]->post_status );
check( 'report: its state is reported', 'reported' === get_post_meta( $Q, '_vance_community_state', true ) );
check( 'report: the report row holds the reporter, the reason and a time', 0 === strpos( (string) get_post_meta( $Q, '_vance_community_report', true ), '5|spam|' ) );
check( 'report: the reporter row holds the reporter ID', '5' === (string) get_post_meta( $Q, '_vance_community_reporter', true ) );
check( 'report: the item is in the Reported tab of the queue', in_array( $Q, array_column( vance_community_queue_rows( 'reported' ), 'id' ), true ) );
check( 'report: the item is no longer in the Approved tab', ! in_array( $Q, array_column( vance_community_queue_rows( 'approved' ), 'id' ), true ) );
$ROW = array_values( array_filter( vance_community_queue_rows( 'reported' ), function ( $row ) use ( $Q ) {
	return $row['id'] === $Q;
} ) );
check( 'report: the queue row names the reason and the count', isset( $ROW[0] ) && 'Spam or selling (1)' === $ROW[0]['reports'] );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$Q  = cs_seed_question( 6, 'approved', 'A question with a reply', '', 0, 'ibd' );
$RC = cs_seed_reply( $Q, 6, 'A reply to report', 'approved' );
member_post( array( 'kind' => 'reply', 'id' => (string) $RC, 'reason' => 'unkind' ) );
$R = reply_of( 'vance_community_ajax_report' );
check( 'report: a reply can be reported', is_object( $R ) && true === $R->success );
check( 'report: a reported reply is unapproved (comment_approved 0)', '0' === (string) $GLOBALS['WP']['comments'][ $RC ]->comment_approved );
check( 'report: a reported reply is in the Reported tab', in_array( $RC, array_column( vance_community_queue_rows( 'reported' ), 'id' ), true ) );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$Q = cs_seed_question( 6, 'approved', 'Bad reason test', '', 0, 'ibd' );
member_post( array( 'kind' => 'question', 'id' => (string) $Q, 'reason' => 'bogus' ) );
$R = reply_of( 'vance_community_ajax_report' );
check( 'report: a reason outside the fixed list is refused with 400', is_object( $R ) && 400 === $R->status );
member_post( array( 'kind' => 'question', 'id' => (string) $Q, 'reason' => 'offtopic' ) );
$R = reply_of( 'vance_community_ajax_report' );
check( 'report: a moderator-only reject reason is not a report reason (400)', is_object( $R ) && 400 === $R->status );
check( 'report: a refused report stores no row and leaves the post public', '' === (string) get_post_meta( $Q, '_vance_community_report', true ) && 'publish' === $GLOBALS['WP']['posts'][ $Q ]->post_status );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$Q = cs_seed_question( 6, 'approved', 'Reported once already', '', 0, 'ibd' );
add_post_meta( $Q, '_vance_community_reporter', '5' );
member_post( array( 'kind' => 'question', 'id' => (string) $Q, 'reason' => 'spam' ) );
$R = reply_of( 'vance_community_ajax_report' );
check( 'report: the same member cannot report the same post twice (409)', is_object( $R ) && 409 === $R->status );
check( 'report: the repeat report stores no second row and leaves the post public', 1 === count( cs_values( 'post', $Q, '_vance_community_reporter' ) ) && 'publish' === $GLOBALS['WP']['posts'][ $Q ]->post_status );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 7, 'ann@example.test', array( 'first_name' => 'Ann' ) );
$Q = cs_seed_question( 6, 'approved', 'Another member can still report', '', 0, 'ibd' );
add_post_meta( $Q, '_vance_community_reporter', '6' );
member_post( array( 'kind' => 'question', 'id' => (string) $Q, 'reason' => 'medical' ) );
$R = reply_of( 'vance_community_ajax_report' );
check( 'report: a different member can report the same post', is_object( $R ) && true === $R->success );

arrange( on_mods(), array(), 5 );
member_setup();
$Q = cs_seed_question( 6, 'approved', 'Limit test target', '', 0, 'ibd' );
cs_repeat( 20, function ( $i ) use ( $Q ) {
	add_post_meta( $Q, '_vance_community_report', '5|spam|' . time() );
} );
member_post( array( 'kind' => 'question', 'id' => (string) $Q, 'reason' => 'spam' ) );
$R = reply_of( 'vance_community_ajax_report' );
check( 'report: the twenty-first report in 24 hours is refused with 429', is_object( $R ) && 429 === $R->status );
check( 'report: a refused report leaves the member off the reporter list', ! cs_has_meta( 'post', $Q, '_vance_community_reporter', '5' ) );

arrange( on_mods(), array(), 5 );
member_setup();
$Q = cs_seed_question( 6, 'approved', 'Old limit target', '', 0, 'ibd' );
cs_repeat( 20, function ( $i ) use ( $Q ) {
	add_post_meta( $Q, '_vance_community_report', '5|spam|' . ( time() - 90000 ) );
} );
member_post( array( 'kind' => 'question', 'id' => (string) $Q, 'reason' => 'spam' ) );
$R = reply_of( 'vance_community_ajax_report' );
check( 'report: reports older than 24 hours do not count toward the limit', is_object( $R ) && true === $R->success );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$W = cs_seed_question( 6, 'waiting', 'Waiting, cannot be reported', '', 0, 'ibd' );
member_post( array( 'kind' => 'question', 'id' => (string) $W, 'reason' => 'spam' ) );
$R = reply_of( 'vance_community_ajax_report' );
check( 'report: a post that is not published cannot be reported (404)', is_object( $R ) && 404 === $R->status );

/* ---- 10. display names and the name settings ---------------------------------- */
section( '10. Display names: first name or nickname, never email or surname' );

check( 'name: first mode shows the first word of the first name, not the surname', 'Mary' === vance_community_pick_name( 'first', 'Mary Jane Smith', 'Mo' ) );
check( 'name: nickname mode shows the nickname', 'Mo' === vance_community_pick_name( 'nickname', 'Mary Jane Smith', 'Mo' ) );
check( 'name: nickname mode with no nickname shows the first name', 'Mary' === vance_community_pick_name( 'nickname', 'Mary', '' ) );
check( 'name: nickname mode with an email as nickname shows the first name', 'Mary' === vance_community_pick_name( 'nickname', 'Mary', 'mary@example.test' ) );
check( 'name: no first name shows "Member", even with a nickname', 'Member' === vance_community_pick_name( 'first', '', 'Mo' ) );
check( 'name: an email typed as the first name shows "Member"', 'Member' === vance_community_pick_name( 'first', 'mary@example.test', '' ) );
check( 'name: stray spaces around the first name are removed', 'Anne' === vance_community_pick_name( 'first', '  Anne  ', '' ) );
check( 'name: user 0 shows "Member"', 'Member' === vance_community_display_name( 0 ) );

arrange( on_mods(), array(), 5 );
member_setup( 5, 'mary@example.test', array( 'first_name' => 'Mary Jane Smith', 'last_name' => 'Smith' ) );
member_post( array( 'mode' => 'nickname', 'nickname' => 'Mo' ) );
$PR = reply_of( 'vance_community_ajax_profile' );
check( 'profile: a nickname is saved and returned as the name', is_object( $PR ) && true === $PR->success && 'Mo' === $PR->data['name'] );
check( 'profile: the nickname mode is saved', 'nickname' === get_user_meta( 5, '_vance_community_name_mode', true ) );
check( 'profile: a reply written after this shows the nickname', 'Mo' === vance_community_display_name( 5 ) );

arrange( on_mods(), array(), 5 );
member_setup( 5, 'mary@example.test', array( 'first_name' => 'Mary' ) );
member_post( array( 'mode' => 'nickname', 'nickname' => 'mary@example.test' ) );
$PR = reply_of( 'vance_community_ajax_profile' );
check( 'profile: an email address is refused as a nickname (400)', is_object( $PR ) && 400 === $PR->status );
check( 'profile: the refused nickname is not saved', '' === (string) get_user_meta( 5, '_vance_community_nickname', true ) );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'mode' => 'nickname', 'nickname' => 'M' ) );
$PR = reply_of( 'vance_community_ajax_profile' );
check( 'profile: a one-letter nickname in nickname mode is refused (400)', is_object( $PR ) && 400 === $PR->status );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'mode' => 'first', 'nickname' => str_repeat( 'x', 40 ) ) );
reply_of( 'vance_community_ajax_profile' );
check( 'profile: a nickname over 30 characters is cut to 30', 30 === mb_strlen( (string) get_user_meta( 5, '_vance_community_nickname', true ) ) );

arrange( on_mods(), array(), 5 );
member_setup();
member_post( array( 'mode' => 'surname', 'nickname' => '' ) );
reply_of( 'vance_community_ajax_profile' );
check( 'profile: an unknown mode is saved as first name', 'first' === get_user_meta( 5, '_vance_community_name_mode', true ) );

/* ---- 11. text in: tags, control characters, limits, line breaks ---------------- */
section( '11. Text in: tags, control characters, length limits, line breaks' );

check( 'text: tags are removed and the words kept', 'Hi there' === vance_community_plain_text( '<p>Hi <b>there</b></p>', 100, false ) );
check( 'text: a script block is removed with its content', 'Safe' === vance_community_plain_text( '<script>evil()</script>Safe', 100, true ) );
check( 'text: a control character is removed', 'ab' === vance_community_plain_text( "a\x07b", 10, false ) );
check( 'text: a tab between words becomes a space (DEFECT: the tab is deleted, so the words join)', 'a b' === vance_community_plain_text( "a\tb", 10, false ) );
check( 'text: a Windows line break is kept as one line break', "x\ny" === vance_community_plain_text( "x\r\ny", 10, true ) );
check( 'text: a single-line field turns line breaks into spaces', 'x y' === vance_community_plain_text( "x\ny", 10, false ) );
check( 'text: a value that is not a string gives ""', '' === vance_community_plain_text( array( 'x' ), 10, true ) && '' === vance_community_plain_text( null, 10, true ) );
check( 'text: the length cut keeps the first characters', 'abc' === vance_community_plain_text( 'abcdef', 3, false ) );
check( 'text: a zero limit gives ""', '' === vance_community_plain_text( 'abc', 0, false ) );
check( 'text: a long run of blank lines is cut to one blank line', "a\n\nb" === vance_community_plain_text( "a\n\n\n\nb", 100, true ) );
check( 'text: a length is counted in characters, so a multibyte word is cut whole', 'é' === vance_community_plain_text( 'éé', 1, false ) );
check( 'text: a length is counted in characters for emoji', 2 === mb_strlen( vance_community_plain_text( str_repeat( "\xF0\x9F\x98\x80", 5 ), 2, false ) ) );
check( 'text: strlen counts characters for a multibyte string', 3 === vance_community_strlen( "\xC3\xA9\xC3\xA9\xC3\xA9" ) );

/* ---- 12. product flag ------------------------------------------------------------ */
section( '12. The "names a product" flag' );

check( 'product: a Vance product name in a sentence is flagged', vance_community_text_names_product( 'I use Vance every day', array( 'Vance' ) ) );
check( 'product: the match ignores case', vance_community_text_names_product( 'try vance tablets', array( 'Vance' ) ) );
check( 'product: a whole word only, so "advance" is not flagged', ! vance_community_text_names_product( 'advance planning', array( 'Vance' ) ) );
check( 'product: EPAVANCE is flagged', vance_community_text_names_product( 'EPAVANCE helped', array( 'EPAVANCE' ) ) );
check( 'product: B-VANCE is flagged', vance_community_text_names_product( 'Try B-VANCE', array( 'B-VANCE' ) ) );
check( 'product: a text with no product name is not flagged', ! vance_community_names_product( 'No brand in this text' ) );
check( 'product: an empty text is not flagged', ! vance_community_names_product( '' ) );

arrange( on_mods(), array(), 5 );
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$QP = cs_seed_question( 6, 'waiting', 'Does Vance help bloating?', '', 0, 'ibd' );
$RP = cs_seed_reply( $QP, 6, 'Vance worked for me', 'waiting' );
$QPRows = vance_community_queue_rows( 'waiting' );
$QPRow  = array_values( array_filter( $QPRows, function ( $row ) use ( $QP ) {
	return 'question' === $row['kind'] && $row['id'] === $QP;
} ) );
$RPRow  = array_values( array_filter( $QPRows, function ( $row ) use ( $RP ) {
	return 'reply' === $row['kind'] && $row['id'] === $RP;
} ) );
check( 'product: a question that names a product is flagged in the queue', isset( $QPRow[0] ) && true === $QPRow[0]['product'] );
check( 'product: a reply that names a product is flagged in the queue', isset( $RPRow[0] ) && true === $RPRow[0]['product'] );

/* ---- 13. moderation ------------------------------------------------------------- */
section( '13. Moderation: nonce and capability, approve, reject reasons, queue' );

arrange( on_mods(), admin_caps(), 1 );
cs_user( 1, 'admin@example.test' );
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$WQ = cs_seed_question( 6, 'waiting', 'Waiting question for the queue', '', 0, 'ibd' );
check( 'queue: a waiting question is listed in the Waiting tab', in_array( $WQ, array_column( vance_community_queue_rows( 'waiting' ), 'id' ), true ) );
check( 'queue: the badge counts the waiting question', 1 === vance_community_queue_badge_count() );

$_POST = array( 'kind' => 'question', 'id' => (string) $WQ, 'do' => 'approve', 'tab' => 'waiting' );
$D1 = die_of( 'vance_community_moderate_handler' );
check( 'moderate: with no nonce the request dies (403) and nothing changes', is_object( $D1 ) && 403 === $D1->status && 'waiting' === vance_community_state( 'question', $WQ ) );

$_POST = array( '_wpnonce' => 'nonce-some-other-action', 'kind' => 'question', 'id' => (string) $WQ, 'do' => 'approve', 'tab' => 'waiting' );
$D2 = die_of( 'vance_community_moderate_handler' );
check( 'moderate: a nonce for another action dies (403) and nothing changes', is_object( $D2 ) && 403 === $D2->status && 'waiting' === vance_community_state( 'question', $WQ ) );

arrange( on_mods(), array(), 5 );
member_setup( 5 );
$WQ = cs_seed_question( 6, 'waiting', 'Member cannot moderate', '', 0, 'ibd' );
$_POST = array( '_wpnonce' => 'nonce-vance_community_moderate', 'kind' => 'question', 'id' => (string) $WQ, 'do' => 'approve', 'tab' => 'waiting' );
$D3 = die_of( 'vance_community_moderate_handler' );
check( 'moderate: a member without vance_moderate_community dies (403) even with the nonce', is_object( $D3 ) && 403 === $D3->status && 'waiting' === vance_community_state( 'question', $WQ ) );

arrange( on_mods(), admin_caps(), 1 );
cs_user( 1, 'admin@example.test' );
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$WQ = cs_seed_question( 6, 'waiting', 'Approve me properly', '', 0, 'ibd' );
$_POST = array( '_wpnonce' => 'nonce-vance_community_moderate', 'kind' => 'question', 'id' => (string) $WQ, 'do' => 'approve', 'tab' => 'waiting' );
$LOC = redirect_of( 'vance_community_moderate_handler' );
check( 'moderate: approve with the nonce and the capability publishes the question', 'publish' === $GLOBALS['WP']['posts'][ $WQ ]->post_status && 'approved' === vance_community_state( 'question', $WQ ) );
check( 'moderate: it redirects to the queue with vcom_done=approved', has_str( $LOC, 'page=vance-community-queue' ) && has_str( $LOC, 'vcom_done=approved' ) && has_str( $LOC, 'tab=waiting' ) );

arrange( on_mods(), admin_caps(), 1 );
cs_user( 1, 'admin@example.test' );
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$WQ = cs_seed_question( 6, 'waiting', 'Reject me with a reason', '', 0, 'ibd' );
$_POST = array( '_wpnonce' => 'nonce-vance_community_moderate', 'kind' => 'question', 'id' => (string) $WQ, 'do' => 'reject', 'reason' => 'medical', 'tab' => 'waiting' );
$LOC = redirect_of( 'vance_community_moderate_handler' );
check( 'moderate: reject with a listed reason makes the question a draft (not public)', 'draft' === $GLOBALS['WP']['posts'][ $WQ ]->post_status && 'rejected' === vance_community_state( 'question', $WQ ) );
check( 'moderate: the reject reason is stored', 'medical' === get_post_meta( $WQ, '_vance_community_reject_reason', true ) );
check( 'moderate: it redirects with vcom_done=rejected', has_str( $LOC, 'vcom_done=rejected' ) );

$_POST = array( '_wpnonce' => 'nonce-vance_community_moderate', 'kind' => 'question', 'id' => (string) $WQ, 'do' => 'approve', 'tab' => 'rejected' );
redirect_of( 'vance_community_moderate_handler' );
check( 'moderate: approving a rejected question clears its reject reason', '' === (string) get_post_meta( $WQ, '_vance_community_reject_reason', true ) );

$WQ2 = cs_seed_question( 6, 'waiting', 'Reject with a made-up reason', '', 0, 'ibd' );
$_POST = array( '_wpnonce' => 'nonce-vance_community_moderate', 'kind' => 'question', 'id' => (string) $WQ2, 'do' => 'reject', 'reason' => 'hacked', 'tab' => 'waiting' );
$LOC = redirect_of( 'vance_community_moderate_handler' );
check( 'moderate: a reject reason outside the list is refused: vcom_done=error', has_str( $LOC, 'vcom_done=error' ) );
check( 'moderate: a refused reject leaves the question waiting, with no reason stored', 'waiting' === vance_community_state( 'question', $WQ2 ) && '' === (string) get_post_meta( $WQ2, '_vance_community_reject_reason', true ) );

$_POST = array( '_wpnonce' => 'nonce-vance_community_moderate', 'kind' => 'question', 'id' => (string) $WQ2, 'do' => 'reject', 'reason' => '', 'tab' => 'waiting' );
$LOC = redirect_of( 'vance_community_moderate_handler' );
check( 'moderate: a reject with no reason is refused: vcom_done=error', has_str( $LOC, 'vcom_done=error' ) );

check( 'reject: the store keeps only a listed reason (a made-up reason is dropped)', vance_community_set_state( 'question', $WQ2, 'rejected', 'bogus' ) && '' === (string) get_post_meta( $WQ2, '_vance_community_reject_reason', true ) && 'rejected' === vance_community_state( 'question', $WQ2 ) );

arrange( on_mods(), admin_caps(), 1 );
cs_user( 1, 'admin@example.test' );
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$RW = cs_seed_reply( cs_seed_question( 6, 'approved', 'Reply moderation', '', 0, 'ibd' ), 6, 'Reply waiting for a moderator', 'waiting' );
$_POST = array( '_wpnonce' => 'nonce-vance_community_moderate', 'kind' => 'reply', 'id' => (string) $RW, 'do' => 'approve', 'tab' => 'waiting' );
redirect_of( 'vance_community_moderate_handler' );
check( 'moderate: approving a reply makes it visible (comment_approved 1)', '1' === (string) $GLOBALS['WP']['comments'][ $RW ]->comment_approved && 'approved' === vance_community_state( 'reply', $RW ) );
$_POST = array( '_wpnonce' => 'nonce-vance_community_moderate', 'kind' => 'reply', 'id' => (string) $RW, 'do' => 'reject', 'reason' => 'claim', 'tab' => 'approved' );
redirect_of( 'vance_community_moderate_handler' );
check( 'moderate: rejecting a reply hides it (comment_approved 0) with its reason', '0' === (string) $GLOBALS['WP']['comments'][ $RW ]->comment_approved && 'claim' === get_comment_meta( $RW, '_vance_community_reject_reason', true ) );

arrange( on_mods(), admin_caps(), 1 );
cs_user( 1, 'admin@example.test' );
$SP = cs_seed_question( 6, 'waiting', 'Published in the editor', '', 0, 'ibd' );
$GLOBALS['WP']['posts'][ $SP ]->post_status = 'publish';
vance_community_sync_post_state( 'publish', 'pending', get_post( $SP ) );
check( 'sync: publishing a question in the WordPress editor sets its state to approved', 'approved' === vance_community_state( 'question', $SP ) );

arrange( on_mods(), admin_caps(), 1 );
cs_user( 1, 'admin@example.test' );
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$EDIT = wp_insert_post( array( 'post_type' => 'vance_question', 'post_status' => 'pending', 'post_author' => 6, 'post_title' => 'Made in the editor, no state' ) );
check( 'queue: a pending question with no state saved is listed under Waiting, as its state reads waiting (DEFECT: the queue matches the state key exactly)', in_array( $EDIT, array_column( vance_community_queue_rows( 'waiting' ), 'id' ), true ) );

/* ---- 14. poll editor -------------------------------------------------------------- */
section( '14. Poll editor: answers, one active poll, votes kept, notices' );

arrange( on_mods(), admin_caps(), 1 );
cs_user( 1, 'admin@example.test' );
$PA = wp_insert_post( array( 'post_type' => 'vance_poll', 'post_status' => 'publish', 'post_title' => 'First poll' ) );
$_POST = array( 'vance_poll_meta_nonce' => 'nonce-vance_poll_meta', 'vance_poll_answers' => array( 'Bloating', 'Pain', 'Tiredness' ), 'vance_poll_active' => '1' );
vance_community_poll_save( $PA, get_post( $PA ) );
check( 'poll: the three answers are saved', array( 'Bloating', 'Pain', 'Tiredness' ) === get_post_meta( $PA, '_vance_community_poll_answers', true ) );
check( 'poll: a published poll with three answers, made active, is the active poll', $PA === active_poll_id() );

act_as( 5, array() );
member_setup( 5 );
member_post( array( 'poll' => (string) $PA, 'answer' => '1' ) );
reply_of( 'vance_community_ajax_vote' );
act_as( 1, admin_caps() );

$PB = wp_insert_post( array( 'post_type' => 'vance_poll', 'post_status' => 'publish', 'post_title' => 'Second poll' ) );
$_POST = array( 'vance_poll_meta_nonce' => 'nonce-vance_poll_meta', 'vance_poll_answers' => array( 'Sleep', 'Gas', 'Stress' ), 'vance_poll_active' => '1' );
vance_community_poll_save( $PB, get_post( $PB ) );
check( 'poll: activating a second poll makes it the active one', $PB === active_poll_id() );
check( 'poll: the first poll is closed (its active flag is removed and a close time set)', '' === (string) get_post_meta( $PA, '_vance_community_poll_active', true ) && (int) get_post_meta( $PA, '_vance_community_poll_closed', true ) > 0 );
check( 'poll: the first poll keeps the member\'s vote', '1' === (string) get_user_meta( 5, '_vance_community_vote_' . $PA, true ) );
check( 'poll: the first poll keeps its count after it closes', 1 === vance_community_poll_counts( $PA )[1] );

$PC = wp_insert_post( array( 'post_type' => 'vance_poll', 'post_status' => 'publish', 'post_title' => 'Two answers only' ) );
$_POST = array( 'vance_poll_meta_nonce' => 'nonce-vance_poll_meta', 'vance_poll_answers' => array( 'Yes', 'No' ), 'vance_poll_active' => '1' );
vance_community_poll_save( $PC, get_post( $PC ) );
check( 'poll: a poll with fewer than three answers is not made active', $PB === active_poll_id() );
check( 'poll: the editor is told the poll needs three answers', 'answers' === get_transient( 'vance_poll_notice_1' ) );

$PD = wp_insert_post( array( 'post_type' => 'vance_poll', 'post_status' => 'draft', 'post_title' => 'Draft poll' ) );
$_POST = array( 'vance_poll_meta_nonce' => 'nonce-vance_poll_meta', 'vance_poll_answers' => array( 'A', 'B', 'C' ), 'vance_poll_active' => '1' );
vance_community_poll_save( $PD, get_post( $PD ) );
check( 'poll: a draft poll is not made active', $PB === active_poll_id() );
check( 'poll: the editor is told the poll must be published first', 'publish' === get_transient( 'vance_poll_notice_1' ) );

$_POST = array( 'vance_poll_meta_nonce' => 'nonce-vance_poll_meta', 'vance_poll_answers' => array( 'Changed', 'Changed too', 'Changed three' ) );
vance_community_poll_save( $PA, get_post( $PA ) );
check( 'poll: the answers of a poll that has votes cannot change', array( 'Bloating', 'Pain', 'Tiredness' ) === get_post_meta( $PA, '_vance_community_poll_answers', true ) );

$PE = wp_insert_post( array( 'post_type' => 'vance_poll', 'post_status' => 'draft', 'post_title' => 'Trimmed answers' ) );
$_POST = array( 'vance_poll_meta_nonce' => 'nonce-vance_poll_meta', 'vance_poll_answers' => array( '  Sleep ', '', 'Tiredness', 'Gas', 'Fatigue', 'Pain', 'Other' ) );
vance_community_poll_save( $PE, get_post( $PE ) );
check( 'poll: answers are trimmed, blanks are dropped, and only the first five are read', array( 'Sleep', 'Tiredness', 'Gas', 'Fatigue' ) === get_post_meta( $PE, '_vance_community_poll_answers', true ) );

$PF = wp_insert_post( array( 'post_type' => 'vance_poll', 'post_status' => 'draft', 'post_title' => 'No nonce' ) );
$_POST = array( 'vance_poll_answers' => array( 'A', 'B', 'C' ), 'vance_poll_active' => '1' );
vance_community_poll_save( $PF, get_post( $PF ) );
check( 'poll: a save with no nonce changes nothing', '' === get_post_meta( $PF, '_vance_community_poll_answers', true ) && '' === (string) get_post_meta( $PF, '_vance_community_poll_active', true ) );

act_as( 5, array() );
$PG = wp_insert_post( array( 'post_type' => 'vance_poll', 'post_status' => 'draft', 'post_title' => 'Member save' ) );
$_POST = array( 'vance_poll_meta_nonce' => 'nonce-vance_poll_meta', 'vance_poll_answers' => array( 'A', 'B', 'C' ) );
vance_community_poll_save( $PG, get_post( $PG ) );
check( 'poll: a member without the moderation capability cannot save answers', '' === get_post_meta( $PG, '_vance_community_poll_answers', true ) );
act_as( 1, admin_caps() );

$PT2 = cs_poll( array( 'A', 'B', 'C' ) );
vance_community_poll_status_change( 'trash', 'publish', get_post( $PT2 ) );
check( 'poll: a poll moved to the bin stops being active and records when it closed', '' === (string) get_post_meta( $PT2, '_vance_community_poll_active', true ) && (int) get_post_meta( $PT2, '_vance_community_poll_closed', true ) > 0 );

/* ---- 15. privacy exporter and eraser ---------------------------------------------- */
section( '15. Privacy: the exporter and the eraser' );

arrange( on_mods(), admin_caps(), 1 );
$EXR = vance_community_register_exporter( array() );
$ERR = vance_community_register_eraser( array() );
check( 'privacy: the exporter is registered as vance-community', 'vance_community_export' === $EXR['vance-community']['callback'] );
check( 'privacy: the eraser is registered as vance-community', 'vance_community_erase' === $ERR['vance-community']['callback'] );

arrange( on_mods(), array(), 0 );
member_setup( 5, 'mary@example.test', array( 'first_name' => 'Mary' ) );
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$POLL = cs_poll( array( 'Bloating', 'Pain', 'Tiredness' ) );
$EQ   = cs_seed_question( 5, 'waiting', 'Export title', 'Export body', 0, 'carers' );
$OQ   = cs_seed_question( 6, 'approved', 'Tom question', '', 0, 'ibd' );
$ERC  = cs_seed_reply( $OQ, 5, 'My reply text', 'approved' );
$OTH  = cs_seed_reply( $OQ, 6, 'Tom reply text', 'approved' );
add_post_meta( $OQ, '_vance_community_metoo', '5' );
add_comment_meta( $OTH, '_vance_community_helpful', '5' );
add_post_meta( $OQ, '_vance_community_reporter', '5' );
add_post_meta( $OQ, '_vance_community_report', '5|spam|' . time() );
add_user_meta( 5, '_vance_community_vote_' . $POLL, '2' );
update_user_meta( 5, '_vance_community_nickname', 'Mo' );
$EXP    = vance_community_export( 'mary@example.test' );
$DATA   = $EXP['data'];
$GROUPS = array_column( $DATA, 'group_id' );
$QROW   = array_column( $DATA, 'data', 'item_id' );
$QFIELD = isset( $QROW[ 'vance-question-' . $EQ ] ) ? array_column( $QROW[ 'vance-question-' . $EQ ], 'value', 'name' ) : array();
$RFIELD = isset( $QROW[ 'vance-reply-' . $ERC ] ) ? array_column( $QROW[ 'vance-reply-' . $ERC ], 'value', 'name' ) : array();
$VFIELD = isset( $QROW[ 'vance-vote-' . $POLL ] ) ? array_column( $QROW[ 'vance-vote-' . $POLL ], 'value', 'name' ) : array();
$NFIELD = isset( $QROW['vance-community-profile'] ) ? array_column( $QROW['vance-community-profile'], 'value', 'name' ) : array();
check( 'export: the member questions group is present', in_array( 'vance-community-questions', $GROUPS, true ) );
check( 'export: the member replies group is present', in_array( 'vance-community-replies', $GROUPS, true ) );
check( 'export: the me-too, helpful, report, vote and name groups are present', in_array( 'vance-community-metoo', $GROUPS, true ) && in_array( 'vance-community-helpful', $GROUPS, true ) && in_array( 'vance-community-reports', $GROUPS, true ) && in_array( 'vance-community-votes', $GROUPS, true ) && in_array( 'vance-community-profile', $GROUPS, true ) );
check( 'export: the question shows its title', 'Export title' === ( $QFIELD['Question'] ?? '' ) );
check( 'export: the question shows its space name', 'Carers' === ( $QFIELD['Space'] ?? '' ) );
check( 'export: the question shows its state', 'waiting' === ( $QFIELD['State'] ?? '' ) );
check( 'export: the reply shows the member\'s own text', 'My reply text' === ( $RFIELD['Reply'] ?? '' ) );
check( 'export: the poll vote shows the answer text, not the index', 'Tiredness' === ( $VFIELD['Answer'] ?? '' ) );
check( 'export: the nickname is in the export', 'Mo' === ( $NFIELD['Nickname'] ?? '' ) );
check( 'export: another member\'s reply is not in this member\'s export', ! isset( $QROW[ 'vance-reply-' . $OTH ] ) );
check( 'export: exactly one question is exported for this member', 1 === count( array_keys( $GROUPS, 'vance-community-questions', true ) ) );
check( 'export: an unknown address gives no data and is done', array() === vance_community_export( 'nobody@example.test' )['data'] && true === vance_community_export( 'nobody@example.test' )['done'] );
check( 'export: page 2 of the export is empty', array() === vance_community_export( 'mary@example.test', 2 )['data'] );

$TRASHED = cs_seed_question( 5, 'approved', 'Trashed question', '', 0, 'ibd' );
wp_update_post( array( 'ID' => $TRASHED, 'post_status' => 'trash' ) );
$EXP2 = vance_community_export( 'mary@example.test' );
check( 'export: a question in the bin is still the member\'s data and is exported (DEFECT: the bin is left out)', isset( array_column( $EXP2['data'], 'data', 'item_id' )[ 'vance-question-' . $TRASHED ] ) );

$ERES = vance_community_erase( 'mary@example.test' );
check( 'erase: the result says that items were removed and the run is done', true === $ERES['items_removed'] && true === $ERES['done'] );
check( 'erase: the member question is removed', ! isset( $GLOBALS['WP']['posts'][ $EQ ] ) );
check( 'erase: the member reply is removed', ! isset( $GLOBALS['WP']['comments'][ $ERC ] ) );
check( 'erase: the member me-too row is removed', ! cs_has_meta( 'post', $OQ, '_vance_community_metoo', '5' ) );
check( 'erase: the member helpful row is removed', ! cs_has_meta( 'comment', $OTH, '_vance_community_helpful', '5' ) );
check( 'erase: the member report and reporter rows are removed', ! cs_has_meta( 'post', $OQ, '_vance_community_reporter', '5' ) && '' === (string) get_post_meta( $OQ, '_vance_community_report', true ) );
check( 'erase: the member poll vote and name settings are removed', '' === (string) get_user_meta( 5, '_vance_community_vote_' . $POLL, true ) && '' === (string) get_user_meta( 5, '_vance_community_nickname', true ) );
check( 'erase: a question in the bin is removed too (DEFECT: erase only looks at published, pending and draft)', ! isset( $GLOBALS['WP']['posts'][ $TRASHED ] ) );
check( 'erase: another member\'s reply is kept', isset( $GLOBALS['WP']['comments'][ $OTH ] ) );
check( 'erase: another member\'s question is kept', isset( $GLOBALS['WP']['posts'][ $OQ ] ) );
check( 'erase: an unknown address removes nothing and says so', false === vance_community_erase( 'nobody@example.test' )['items_removed'] );

// A question that another member replied to is kept as an empty, hidden shell,
// so the other member's reply is not destroyed with it.
member_setup( 7, 'ann@example.test', array( 'first_name' => 'Ann' ) );
$AQ  = cs_seed_question( 7, 'approved', 'Ann private title', 'Ann private body', 0, 'ibd' );
$AQ2 = cs_seed_question( 7, 'approved', 'Ann lonely question', '', 0, 'ibd' );
$AOWN = cs_seed_reply( $AQ, 7, 'Ann own reply', 'approved' );
$TOMR = cs_seed_reply( $AQ, 6, 'Tom reply to Ann', 'approved' );
$ARES = vance_community_erase( 'ann@example.test' );
$ASH  = $GLOBALS['WP']['posts'][ $AQ ] ?? null;
check( 'erase: a question with another member\'s reply is kept, and the other reply survives', null !== $ASH && isset( $GLOBALS['WP']['comments'][ $TOMR ] ) );
check( 'erase: the kept question has no author, title text or body, and is hidden', null !== $ASH && 0 === (int) $ASH->post_author && false === strpos( (string) $ASH->post_title, 'Ann' ) && '' === (string) $ASH->post_content && 'draft' === $ASH->post_status && 'question-removed-' . $AQ === $ASH->post_name );
check( 'erase: the member\'s own reply under the kept question is removed', ! isset( $GLOBALS['WP']['comments'][ $AOWN ] ) );
check( 'erase: a question nobody else replied to is deleted outright', ! isset( $GLOBALS['WP']['posts'][ $AQ2 ] ) );
check( 'erase: the result says an item was retained, with a message', true === $ARES['items_retained'] && 1 === count( $ARES['messages'] ) && true === $ARES['items_removed'] );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
