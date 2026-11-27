<?php
/**
 * Behaviour tests for inc/hub-community-settings.php as the Community page
 * uses it: the poll switch (page and vote handler), the four texts, the extra
 * line, the rules link, the fixed safety sentences that no setting can remove,
 * and the six Customizer registrations.
 *
 * Same shape as hub-community-view.test.php: the WordPress stubs of
 * hub-community-stubs.php, check() lines, exit 1 on any failure. The vote is
 * run through the real AJAX handler, which ends in wp_send_json_* stubs that
 * throw, so the reply is caught here.
 *
 * WHAT IS COVERED
 *   1  the poll switch: stored false, '0', 0, '', missing and true. Off prints no
 *      poll block, and the page equals the page with no active poll
 *   2  the vote handler: off refuses and writes no user meta; a vote saved before
 *      the switch went off is still there; on, a vote works
 *   3  the visitor intro: empty, spaces, default, custom, hostile (escaped)
 *   4  the member intro: the fixed sentence follows a custom text exactly once;
 *      nothing saved prints the single built-in string
 *   5  the extra line: nothing saved and spaces add no element; a saved line prints
 *      once in the member box and once in the visitor block, before the rules link
 *   6  the rules link: unpublished, published, site path, full https, javascript:,
 *      //evil, a backslash form, a bad value in the mod; the label custom and empty
 *   7  the fixed safety text: with every setting hostile, each fixed sentence is
 *      still on the page, for a visitor and for a member
 *   8  the Customizer registrations: keys, defaults, sanitisers, sections, priorities
 *
 * A check that fails on the current code is a finding, not a broken check. The
 * runner lists every failure. Bugs found are reported, not fixed.
 */

require_once __DIR__ . '/hub-community-stubs.php';

set_error_handler( function ( $no, $str, $file, $line ) {
	if ( ! ( error_reporting() & $no ) ) {
		return false;
	}
	throw new ErrorException( $str, 0, $no, $file, $line );
} );

require_once $THEME . '/inc/condition-priority.php';
require_once $THEME . '/inc/hub-layout.php';
require_once $THEME . '/inc/hub-look-settings.php';
require_once $THEME . '/inc/hub-header.php';
require_once $THEME . '/inc/hub-community.php';
require_once $THEME . '/inc/hub-community-view.php';
require_once $THEME . '/inc/hub-community-settings.php';
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
require_once $THEME . '/inc/hub-community-sample.php';

/* ---- runner and helpers --------------------------------------------------------------- */

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }
function has( $hay, $needle ) { return false !== strpos( (string) $hay, $needle ); }

/** Hostile text, as a raw value. */
$HOSTILE     = '<script>alert("x")</script> & \'q\'';
/** The same text after esc_html(). */
$HOSTILE_ESC = '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; &amp; &#039;q&#039;';

/** The three-sentence built-in member intro, as the template prints it. */
$MEMBER_BUILTIN = 'Ask people who live with a gut condition. Share what works for you. A moderator reads every post.';

function arrange( array $mods = array(), $user = 0 ) {
	cs_reset();
	$GLOBALS['WP']['mods'] = $mods;
	$GLOBALS['WP']['caps'] = array();
	$GLOBALS['WP']['user'] = (int) $user;
	vance_hub_header_existing_page_url( '', '', true );
	vance_community_register_types();
	vance_community_seed_spaces();
}
function on_mods() {
	return array( 'vance_home_layout' => 'hub', 'vance_hub_community' => true );
}
function cs_page( $slug, $status = 'publish' ) {
	return wp_insert_post( array( 'post_type' => 'page', 'post_status' => $status, 'post_name' => $slug, 'post_title' => $slug ) );
}
function cs_poll( array $answers = array( 'Bloating', 'Pain', 'Tiredness' ), $title = 'ZZPOLL weekly poll title' ) {
	$id = wp_insert_post( array( 'post_type' => 'vance_poll', 'post_status' => 'publish', 'post_title' => $title ) );
	update_post_meta( $id, '_vance_community_poll_answers', $answers );
	update_post_meta( $id, '_vance_community_poll_active', '1' );
	return $id;
}
/** Which rules page exists in the next render: 'none', 'publish' or 'draft'. arrange() wipes the store, so the page is made after it. */
$RULES = 'none';
function rules_setup() {
	global $RULES;
	if ( 'none' !== $RULES ) {
		cs_page( 'community-rules', $RULES );
	}
}
/** The community page for a visitor with these mods. */
function visitor_page( array $mods ) {
	arrange( $mods, 0 );
	rules_setup();
	return page_html();
}
/** The community page for member 5 with these mods, and no poll. */
function member_page( array $mods ) {
	arrange( $mods, 5 );
	cs_user( 5, 'mary@example.test', array( 'first_name' => 'Mary' ) );
	rules_setup();
	return page_html();
}
/** The community page for member 5 with these mods and an active poll. */
function member_page_with_poll( array $mods ) {
	arrange( $mods, 5 );
	cs_user( 5, 'mary@example.test', array( 'first_name' => 'Mary' ) );
	cs_poll();
	rules_setup();
	return page_html();
}
function page_html() {
	return vance_community_render_page();
}
/** The member intro paragraph only (the text the poll and texts change). */
function lead_of( $html ) {
	$start = strpos( (string) $html, '<p class="vcom-hero__lead">' );
	if ( false === $start ) {
		return '';
	}
	$end = strpos( $html, '</p>', $start );
	return false === $end ? '' : substr( $html, $start, $end - $start + 4 );
}
/** A vote through the real handler: the reply object, or null when it did not reply. */
function vote_reply( $fn ) {
	try {
		$fn();
	} catch ( CsJsonReply $r ) {
		return $r;
	}
	return null;
}
function member_post( array $extra ) {
	$_POST = $extra + array( 'nonce' => 'nonce-vance_community_nonce' );
}
/** Stored vote of member 5 on poll $p ('' when none). */
function stored_vote( $p ) {
	return (string) get_user_meta( 5, '_vance_community_vote_' . $p, true );
}

/** Customizer stand-in: records what the register function adds. */
class CommunitySettingsFake {
	public $settings = array();
	public $controls = array();
	public function add_setting( $id, $args = array() ) { $this->settings[ $id ] = $args; }
	public function add_control( $id, $args = array() ) { $this->controls[ $id ] = $args; }
}

/* ===================================================================== */

section( '1. The poll switch: the page' );

$NOPOLL = member_page( on_mods() );
check( 'baseline: the member page with no poll and nothing saved has no poll block', ! has( $NOPOLL, '<section class="vcom-poll"' ) );

$WITH_POLL = member_page_with_poll( on_mods() );
check( 'missing value: the poll shows (the default is on)', has( $WITH_POLL, '<section class="vcom-poll"' ) );

check( 'true: the poll shows', has( member_page_with_poll( on_mods() + array( 'vance_hub_community_poll' => true ) ), '<section class="vcom-poll"' ) );
check( '"1": the poll shows', has( member_page_with_poll( on_mods() + array( 'vance_hub_community_poll' => '1' ) ), '<section class="vcom-poll"' ) );

$OFF_FALSE = member_page_with_poll( on_mods() + array( 'vance_hub_community_poll' => false ) );
check( 'stored false: no poll block', ! has( $OFF_FALSE, 'vcom-poll' ) );
check( 'stored false: the page equals the page with no active poll', $OFF_FALSE === $NOPOLL );

$OFF_STR0 = member_page_with_poll( on_mods() + array( 'vance_hub_community_poll' => '0' ) );
check( 'stored "0": no poll block', ! has( $OFF_STR0, 'vcom-poll' ) );
check( 'stored "0": the page equals the page with no active poll', $OFF_STR0 === $NOPOLL );

$OFF_INT0 = member_page_with_poll( on_mods() + array( 'vance_hub_community_poll' => 0 ) );
check( 'stored 0 (int): no poll block', ! has( $OFF_INT0, 'vcom-poll' ) );
check( 'stored 0 (int): the page equals the page with no active poll', $OFF_INT0 === $NOPOLL );

$OFF_EMPTY = member_page_with_poll( on_mods() + array( 'vance_hub_community_poll' => '' ) );
check( 'stored empty string: no poll block', ! has( $OFF_EMPTY, 'vcom-poll' ) );
check( 'stored empty string: the page equals the page with no active poll', $OFF_EMPTY === $NOPOLL );

check( 'off: a visitor never sees a poll either way', ! has( visitor_page( on_mods() + array( 'vance_hub_community_poll' => true ) ), 'vcom-poll' ) );

/* ===================================================================== */

section( '2. The vote handler with the poll switch' );

arrange( on_mods(), 5 );
cs_user( 5, 'mary@example.test', array( 'first_name' => 'Mary' ) );
$P = cs_poll();
member_post( array( 'poll' => (string) $P, 'answer' => '1' ) );
$V = vote_reply( 'vance_community_ajax_vote' );
check( 'on (missing value): a vote is accepted', is_object( $V ) && true === $V->success );
check( 'on: the vote is stored', '1' === stored_vote( $P ) );

// The switch goes off after the vote. Same store, so the old vote must still be there.
$GLOBALS['WP']['mods'] = on_mods() + array( 'vance_hub_community_poll' => false );
member_post( array( 'poll' => (string) $P, 'answer' => '2' ) );
$V = vote_reply( 'vance_community_ajax_vote' );
check( 'off after a vote: the handler sends an error reply', is_object( $V ) && false === $V->success );
check( 'off after a vote: the error is "This poll is closed." with status 404', is_object( $V ) && 404 === (int) $V->status && 'This poll is closed.' === $V->data['message'] );
check( 'off after a vote: the earlier vote is still saved', '1' === stored_vote( $P ) );
check( 'off after a vote: the new answer is NOT written', '2' !== stored_vote( $P ) );

// Fresh store, switch off, nobody has voted: nothing may be written.
arrange( on_mods() + array( 'vance_hub_community_poll' => false ), 5 );
cs_user( 5, 'mary@example.test', array( 'first_name' => 'Mary' ) );
$P2 = cs_poll();
member_post( array( 'poll' => (string) $P2, 'answer' => '0' ) );
$V = vote_reply( 'vance_community_ajax_vote' );
check( 'off, stored false, no earlier vote: the handler refuses', is_object( $V ) && false === $V->success );
check( 'off, stored false, no earlier vote: NO user meta is written', '' === stored_vote( $P2 ) );

arrange( on_mods() + array( 'vance_hub_community_poll' => '0' ), 5 );
cs_user( 5, 'mary@example.test', array( 'first_name' => 'Mary' ) );
$P4 = cs_poll();
member_post( array( 'poll' => (string) $P4, 'answer' => '0' ) );
$V = vote_reply( 'vance_community_ajax_vote' );
check( 'off, stored "0", no earlier vote: the handler refuses', is_object( $V ) && false === $V->success );
check( 'off, stored "0", no earlier vote: NO user meta is written', '' === stored_vote( $P4 ) );

// Switch on again: a vote works.
arrange( on_mods() + array( 'vance_hub_community_poll' => true ), 5 );
cs_user( 5, 'mary@example.test', array( 'first_name' => 'Mary' ) );
$P3 = cs_poll();
member_post( array( 'poll' => (string) $P3, 'answer' => '2' ) );
$V = vote_reply( 'vance_community_ajax_vote' );
check( 'on again (stored true): a vote is accepted and stored', is_object( $V ) && true === $V->success && '2' === stored_vote( $P3 ) );

/* ===================================================================== */

section( '3. The visitor intro' );

check( 'visitor: nothing saved prints the built-in visitor intro', has( visitor_page( on_mods() ), 'A private, moderated space for people living with digestive conditions to share experiences and connect. Free membership. Join at your own pace.' ) );
check( 'visitor: spaces only prints the built-in intro', has( visitor_page( on_mods() + array( 'vance_hub_text_com_visitor_intro' => "   \t  " ) ), 'A private, moderated space for people living with digestive conditions' ) );
check( 'visitor: empty string prints the built-in intro', has( visitor_page( on_mods() + array( 'vance_hub_text_com_visitor_intro' => '' ) ), 'Free membership. Join at your own pace.' ) );
check( 'visitor: the default text saved prints the built-in intro once', 1 === substr_count( visitor_page( on_mods() + array( 'vance_hub_text_com_visitor_intro' => 'A private, moderated space for people living with digestive conditions to share experiences and connect. Free membership. Join at your own pace.' ) ), 'A private, moderated space' ) );
$VIS_CUSTOM = visitor_page( on_mods() + array( 'vance_hub_text_com_visitor_intro' => 'Our own visitor words.' ) );
check( 'visitor: a custom text replaces the built-in intro', has( $VIS_CUSTOM, '<p class="vcom-hero__lead">Our own visitor words.</p>' ) );
check( 'visitor: a custom text removes the built-in intro', ! has( $VIS_CUSTOM, 'A private, moderated space for people' ) );
$VIS_HOSTILE = visitor_page( on_mods() + array( 'vance_hub_text_com_visitor_intro' => $HOSTILE ) );
check( 'visitor: a hostile intro is escaped (script, quotes and ampersand)', has( $VIS_HOSTILE, $HOSTILE_ESC ) );
check( 'visitor: a hostile intro adds no raw script tag', ! has( $VIS_HOSTILE, '<script>alert' ) );

/* ===================================================================== */

section( '4. The member intro: the fixed sentence follows a custom text' );

$MEM_NONE = member_page( on_mods() );
check( 'member: nothing saved prints the single built-in string', 1 === substr_count( lead_of( $MEM_NONE ), $MEMBER_BUILTIN ) );
check( 'member: spaces only prints the single built-in string', 1 === substr_count( lead_of( member_page( on_mods() + array( 'vance_hub_text_com_member_intro' => " \t " ) ) ), $MEMBER_BUILTIN ) );
check( 'member: the default two sentences saved print the built-in string once', 1 === substr_count( lead_of( member_page( on_mods() + array( 'vance_hub_text_com_member_intro' => 'Ask people who live with a gut condition. Share what works for you.' ) ) ), $MEMBER_BUILTIN ) );

$MEM_CUSTOM = lead_of( member_page( on_mods() + array( 'vance_hub_text_com_member_intro' => 'Our own member words.' ) ) );
check( 'member: a custom text is followed by the fixed sentence', has( $MEM_CUSTOM, 'Our own member words. A moderator reads every post.' ) );
check( 'member: the fixed sentence appears exactly once in the intro after a custom text', 1 === substr_count( $MEM_CUSTOM, 'A moderator reads every post.' ) );
check( 'member: a custom text removes the built-in wording', ! has( $MEM_CUSTOM, 'Ask people who live' ) );

$MEM_HOSTILE = lead_of( member_page( on_mods() + array( 'vance_hub_text_com_member_intro' => $HOSTILE ) ) );
check( 'member: a hostile intro is escaped', has( $MEM_HOSTILE, $HOSTILE_ESC . ' A moderator reads every post.' ) );
check( 'member: a hostile intro adds no raw script tag', ! has( $MEM_HOSTILE, '<script>alert' ) );

/* ===================================================================== */

section( '5. The extra line' );

$VIS_NOTE_NONE = visitor_page( on_mods() );
$MEM_NOTE_NONE = member_page( on_mods() );
check( 'note: nothing saved, visitor page: no extra paragraph is added', $VIS_NOTE_NONE === visitor_page( on_mods() + array( 'vance_hub_text_com_note' => '' ) ) );
check( 'note: spaces only adds no element either (visitor)', $VIS_NOTE_NONE === visitor_page( on_mods() + array( 'vance_hub_text_com_note' => "  \t " ) ) );
check( 'note: spaces only adds no element either (member)', $MEM_NOTE_NONE === member_page( on_mods() + array( 'vance_hub_text_com_note' => '   ' ) ) );

$VIS_NOTE = visitor_page( on_mods() + array( 'vance_hub_text_com_note' => 'Our extra line here.' ) );
$MEM_NOTE = member_page( on_mods() + array( 'vance_hub_text_com_note' => 'Our extra line here.' ) );
check( 'note: a saved line prints once on the visitor page', 1 === substr_count( $VIS_NOTE, 'Our extra line here.' ) );
check( 'note: a saved line prints once in the member box', 1 === substr_count( $MEM_NOTE, 'Our extra line here.' ) );
check( 'note: a saved line is a paragraph of its own (visitor)', has( $VIS_NOTE, '<p>Our extra line here.</p>' ) );

$RULES = 'publish';
$VIS_NOTE_RULES = visitor_page( on_mods() + array( 'vance_hub_text_com_note' => 'Our extra line here.' ) );
check( 'note: on the visitor page the line comes before the rules link', strpos( $VIS_NOTE_RULES, 'Our extra line here.' ) < strpos( $VIS_NOTE_RULES, 'class="vcom-link"' ) );
$MEM_NOTE_RULES = member_page( on_mods() + array( 'vance_hub_text_com_note' => 'Our extra line here.' ) );
check( 'note: on the member page the line comes before the rules link', strpos( $MEM_NOTE_RULES, 'Our extra line here.' ) < strpos( $MEM_NOTE_RULES, 'class="vcom-link"' ) );
$VIS_HOSTILE_NOTE = visitor_page( on_mods() + array( 'vance_hub_text_com_note' => $HOSTILE ) );
check( 'note: a hostile line is escaped (visitor)', has( $VIS_HOSTILE_NOTE, '<p>' . $HOSTILE_ESC . '</p>' ) );

/* ===================================================================== */

section( '6. The rules link' );

$RULES = 'none';

// Nothing saved: the link shows only when the rules page is published.
check( 'rules: nothing saved and no rules page: no rules link for a visitor', ! has( visitor_page( on_mods() ), 'class="vcom-link"' ) );
check( 'rules: nothing saved and no rules page: no rules link for a member', ! has( member_page( on_mods() ), 'class="vcom-link"' ) );

$RULES = 'draft';
check( 'rules: a DRAFT rules page is not linked (visitor)', ! has( visitor_page( on_mods() ), 'class="vcom-link"' ) );
check( 'rules: a DRAFT rules page is not linked (member)', ! has( member_page( on_mods() ), 'class="vcom-link"' ) );
$RULES = 'none';

$RULES = 'publish';
check( 'rules: a published rules page is linked by default (visitor)', has( visitor_page( on_mods() ), 'href="https://example.test/community-rules/"' ) );
check( 'rules: a published rules page is linked by default (member)', has( member_page( on_mods() ), 'href="https://example.test/community-rules/"' ) );
check( 'rules: the default label is printed', has( visitor_page( on_mods() ), 'Read the community rules' ) );

$PATH_LINK = visitor_page( on_mods() + array( 'vance_hub_link_com_rules' => '/community-rules-custom/' ) );
check( 'rules: a site path goes through home_url (visitor)', has( $PATH_LINK, 'href="https://example.test/community-rules-custom/"' ) );
check( 'rules: a site path goes through home_url (member)', has( member_page( on_mods() + array( 'vance_hub_link_com_rules' => '/community-rules-custom/' ) ), 'href="https://example.test/community-rules-custom/"' ) );

check( 'rules: a full https address is used as it is (visitor)', has( visitor_page( on_mods() + array( 'vance_hub_link_com_rules' => 'https://rules.example.org/terms' ) ), 'href="https://rules.example.org/terms"' ) );
check( 'rules: a full https address is used as it is (member)', has( member_page( on_mods() + array( 'vance_hub_link_com_rules' => 'https://rules.example.org/terms' ) ), 'href="https://rules.example.org/terms"' ) );

$JS_VISITOR = visitor_page( on_mods() + array( 'vance_hub_link_com_rules' => 'javascript:alert(1)' ) );
check( 'rules: javascript: is refused (visitor): no javascript text in the page', ! has( $JS_VISITOR, 'javascript' ) );
$JS_MEMBER = member_page( on_mods() + array( 'vance_hub_link_com_rules' => 'javascript:alert(1)' ) );
check( 'rules: javascript: is refused (member): no javascript text in the page', ! has( $JS_MEMBER, 'javascript' ) );

check( 'rules: //evil.example is refused (visitor)', ! has( visitor_page( on_mods() + array( 'vance_hub_link_com_rules' => '//evil.example' ) ), 'evil.example' ) );
check( 'rules: //evil.example is refused (member)', ! has( member_page( on_mods() + array( 'vance_hub_link_com_rules' => '//evil.example' ) ), 'evil.example' ) );
check( 'rules: a backslash form is refused (visitor)', ! has( visitor_page( on_mods() + array( 'vance_hub_link_com_rules' => '/\\evil.example' ) ), 'evil.example' ) );
check( 'rules: a backslash form is refused (member)', ! has( member_page( on_mods() + array( 'vance_hub_link_com_rules' => '/\\evil.example' ) ), 'evil.example' ) );

// A bad saved value falls back to the published page, not to nothing.
check( 'rules: a bad value with a published page falls back to the page (visitor)', has( visitor_page( on_mods() + array( 'vance_hub_link_com_rules' => 'javascript:alert(1)' ) ), 'href="https://example.test/community-rules/"' ) );
check( 'rules: a bad value with a published page falls back to the page (member)', has( member_page( on_mods() + array( 'vance_hub_link_com_rules' => '//evil.example' ) ), 'href="https://example.test/community-rules/"' ) );

$RULES = 'none';
check( 'rules: a custom address wins even with no rules page (visitor)', has( visitor_page( on_mods() + array( 'vance_hub_link_com_rules' => 'https://rules.example.org/terms' ) ), 'href="https://rules.example.org/terms"' ) );
check( 'rules: a bad value with no rules page prints no link (visitor)', ! has( visitor_page( on_mods() + array( 'vance_hub_link_com_rules' => 'javascript:alert(1)' ) ), 'class="vcom-link"' ) );
check( 'rules: a bad value with no rules page prints no link (member)', ! has( member_page( on_mods() + array( 'vance_hub_link_com_rules' => 'javascript:alert(1)' ) ), 'class="vcom-link"' ) );

check( 'rules label: a custom label is printed (visitor)', has( visitor_page( on_mods() + array( 'vance_hub_link_com_rules' => 'https://rules.example.org/terms', 'vance_hub_text_com_rules_label' => 'Our house rules' ) ), '>Our house rules' ) );
check( 'rules label: a custom label is printed (member)', has( member_page( on_mods() + array( 'vance_hub_link_com_rules' => 'https://rules.example.org/terms', 'vance_hub_text_com_rules_label' => 'Our house rules' ) ), '>Our house rules' ) );
check( 'rules label: an empty label falls back to the default words', has( visitor_page( on_mods() + array( 'vance_hub_link_com_rules' => 'https://rules.example.org/terms', 'vance_hub_text_com_rules_label' => '' ) ), '>Read the community rules' ) );
check( 'rules label: spaces only fall back to the default words', has( visitor_page( on_mods() + array( 'vance_hub_link_com_rules' => 'https://rules.example.org/terms', 'vance_hub_text_com_rules_label' => '   ' ) ), '>Read the community rules' ) );
$HOSTILE_LABEL = visitor_page( on_mods() + array( 'vance_hub_link_com_rules' => 'https://rules.example.org/terms', 'vance_hub_text_com_rules_label' => $HOSTILE ) );
check( 'rules label: a hostile label is escaped', has( $HOSTILE_LABEL, $HOSTILE_ESC ) && ! has( $HOSTILE_LABEL, '<script>alert' ) );

/* ===================================================================== */

section( '7. The fixed safety text survives every hostile setting' );

$HOSTILE_MODS = on_mods() + array(
	'vance_hub_community_poll'         => true,
	'vance_hub_text_com_visitor_intro' => $HOSTILE,
	'vance_hub_text_com_member_intro'  => $HOSTILE,
	'vance_hub_text_com_note'          => $HOSTILE,
	'vance_hub_text_com_rules_label'   => $HOSTILE,
	'vance_hub_link_com_rules'         => 'javascript:alert(1)',
);
$HV = visitor_page( $HOSTILE_MODS );
check( 'safety, visitor: "They do not give medical advice" stays', has( $HV, 'They do not give medical advice.' ) );
check( 'safety, visitor: "A moderator reads every post before it shows." stays', has( $HV, 'A moderator reads every post before it shows.' ) );
check( 'safety, visitor: the NHS 111 and 999 sentence stays', has( $HV, 'The Community is not an emergency service. For urgent medical advice, call NHS 111. In an emergency, call 999.' ) );
check( 'safety, visitor: the nickname warning stays', has( $HV, 'A nickname is not always anonymity: other members may still recognise you from what you write.' ) );

$HM = member_page( $HOSTILE_MODS );
check( 'safety, member: "They do not give medical advice" stays', has( $HM, 'They do not give medical advice.' ) );
check( 'safety, member: "A moderator reads every post before it shows." stays', has( $HM, 'A moderator reads every post before it shows.' ) );
check( 'safety, member: "If you feel very unwell, call 111 or 999." stays', has( $HM, 'If you feel very unwell, call 111 or 999.' ) );
check( 'safety, member: the nickname line stays', has( $HM, 'You can post with your first name or a nickname.' ) );
check( 'safety, member: the moderator sentence follows the hostile intro', has( lead_of( $HM ), 'A moderator reads every post.' ) );

/* ===================================================================== */

section( '8. The Customizer registrations' );

$M = new CommunitySettingsFake();
vance_hub_community_settings_customize_register( $M );

check( 'register: six settings are added (the switch, four texts, the link)', 6 === count( $M->settings ) );
check( 'register: six controls are added', 6 === count( $M->controls ) );
check( 'register: the poll switch defaults to on', true === $M->settings['vance_hub_community_poll']['default'] );
check( 'register: the poll switch is sanitised as a checkbox', 'vance_hub_sanitize_checkbox' === $M->settings['vance_hub_community_poll']['sanitize_callback'] );
check( 'register: the visitor intro default is the old wording', 'A private, moderated space for people living with digestive conditions to share experiences and connect. Free membership. Join at your own pace.' === $M->settings['vance_hub_text_com_visitor_intro']['default'] );
check( 'register: the member intro default is the old two sentences', 'Ask people who live with a gut condition. Share what works for you.' === $M->settings['vance_hub_text_com_member_intro']['default'] );
check( 'register: the extra line defaults to nothing', '' === $M->settings['vance_hub_text_com_note']['default'] );
check( 'register: the rules label defaults to the old words', 'Read the community rules' === $M->settings['vance_hub_text_com_rules_label']['default'] );
check( 'register: the rules link defaults to nothing', '' === $M->settings['vance_hub_link_com_rules']['default'] );
check( 'register: the intros use the textarea sanitiser', 'sanitize_textarea_field' === $M->settings['vance_hub_text_com_visitor_intro']['sanitize_callback'] && 'sanitize_textarea_field' === $M->settings['vance_hub_text_com_member_intro']['sanitize_callback'] );
check( 'register: the rules link uses the link sanitiser', 'vance_hub_sanitize_link' === $M->settings['vance_hub_link_com_rules']['sanitize_callback'] );
check( 'register: every text control is in Community', 'vance_hub_sec_community' === $M->controls['vance_hub_text_com_visitor_intro']['section'] && 'vance_hub_sec_community' === $M->controls['vance_hub_link_com_rules']['section'] );
check( 'register: the poll switch sits in Community at priority 20', 20 === $M->controls['vance_hub_community_poll']['priority'] );
check( 'register: priorities run 30, 40, 50, 60, 70 for the texts and the link', 30 === $M->controls['vance_hub_text_com_visitor_intro']['priority'] && 40 === $M->controls['vance_hub_text_com_member_intro']['priority'] && 50 === $M->controls['vance_hub_text_com_note']['priority'] && 60 === $M->controls['vance_hub_link_com_rules']['priority'] && 70 === $M->controls['vance_hub_text_com_rules_label']['priority'] );
check( 'register: the member intro control says the fixed sentence follows', has( $M->controls['vance_hub_text_com_member_intro']['description'], 'A moderator reads every post.' ) );

echo "\n$PASS passed, " . count( $FAIL ) . " failed\n";
foreach ( $FAIL as $f ) {
	echo "FAIL: $f\n";
}
exit( $FAIL ? 1 : 0 );
