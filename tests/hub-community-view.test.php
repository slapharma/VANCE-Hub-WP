<?php
/**
 * Tests for the VanceHubClub Community hub, the markup side:
 * inc/hub-community-view.php (the page, the cards, the poll block, "Your posts",
 * the ask form and the join bar), the queue screen in inc/hub-community-admin.php,
 * the header link in inc/hub-header.php, and the wiring to assets/js/hub-community.js
 * and page-community.php.
 *
 * Same shape as hub-tool-result.test.php: WordPress stubs (shared with
 * hub-community.test.php: tests/hub-community-stubs.php), check() lines, and
 * exit 1 on any failure. Nothing here runs the JavaScript.
 *
 * WHAT IS COVERED
 *   1  switch off: the page renders nothing
 *   2  the visitor rule: only the first approved reply is in the HTML; the other
 *      replies' text never is (distinct markers seeded for each); grey bars and
 *      the "more replies for members" line instead; visitor actions are join links
 *   3  the member view: every approved reply; the member controls and the config
 *   4  "Your posts": own items only, with their state and reject reason
 *   5  the weekly poll: no block without a poll, buttons before the vote, the
 *      result after it, the visitor never sees the result
 *   6  escaping: seeded markup in title, body, reply, nickname and poll title
 *   7  the data rule: rules and VANCE-Ai links only when their page is published
 *   8  the feed: space filter, unknown space, empty space, paging
 *   9  the header: the Community link and its current-page mark
 *  10  the queue screen: escaping, flags, reasons, nonce fields, notices, capability
 *  11  wiring: the script's actions and fields match the server; the script writes
 *      server HTML only where the server escaped it; the template calls the renderer
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
require_once $THEME . '/inc/hub-header.php';
require_once $THEME . '/inc/hub-community.php';
require_once $THEME . '/inc/hub-community-view.php';
require_once $THEME . '/inc/hub-community-admin.php';
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
require_once $THEME . '/inc/hub-community-sample.php';

/* ---- helpers ------------------------------------------------------------------------ */

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }
function has( $hay, $needle ) { return false !== strpos( (string) $hay, $needle ); }

function arrange( array $mods = array(), array $caps = array(), $user = 0 ) {
	cs_reset();
	$GLOBALS['WP']['mods'] = $mods;
	$GLOBALS['WP']['caps'] = $caps;
	$GLOBALS['WP']['user'] = (int) $user;
	vance_hub_header_existing_page_url( '', '', true );
	vance_community_register_types();
	vance_community_seed_spaces();
}
function admin_caps() {
	return array( 'vance_moderate_community' => true, 'edit_theme_options' => true, 'publish_pages' => true, 'manage_options' => true );
}
function on_mods() {
	return array( 'vance_home_layout' => 'hub', 'vance_hub_community' => true );
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
function member_setup( $uid = 5, $email = 'mary@example.test', array $meta = array( 'first_name' => 'Mary' ) ) {
	return cs_user( $uid, $email, $meta );
}
/** The community page as a visitor or member sees it. */
function page_html() {
	return vance_community_render_page();
}
/** The queue screen as an administrator sees it. */
function queue_html() {
	ob_start();
	vance_community_queue_render();
	return ob_get_clean();
}
/** Number of cards on the page. */
function card_count( $html ) {
	return substr_count( (string) $html, '<article class="vcom-card ' );
}

/* ===================================================================== */

/* ---- 1. switch off ------------------------------------------------------------ */
section( '1. Switch off: the page renders nothing' );

arrange();
cs_seed_question( 6, 'approved', 'Should not render', '', 0, 'ibd' );
check( 'off: the page renders no HTML for a visitor', '' === page_html() );
arrange( array(), admin_caps(), 1 );
cs_seed_question( 6, 'approved', 'Should not render either', '', 0, 'ibd' );
check( 'off: the page renders no HTML even for an administrator who is not previewing', '' === page_html() );

/* ---- 2. the visitor rule ------------------------------------------------------------ */
section( '2. The visitor rule: first approved reply only, grey bars, join links' );

arrange( on_mods() );
$Q = cs_seed_question( 6, 'approved', 'Visitor sees this title', 'Question body text', 0, 'ibd' );
cs_seed_reply( $Q, 6, 'ZZONE first reply text', 'approved', 300 );
cs_seed_reply( $Q, 6, 'ZZTWO second reply text', 'approved', 200 );
cs_seed_reply( $Q, 6, 'ZZTHREE third reply text', 'approved', 100 );
cs_seed_reply( $Q, 6, 'ZZFOUR fourth reply text', 'approved', 50 );
cs_seed_reply( $Q, 6, 'ZZWAITING waiting reply text', 'waiting', 10 );
cs_seed_reply( $Q, 6, 'ZZREJECTED rejected reply text', 'rejected', 10 );
cs_seed_reply( $Q, 6, 'ZZREPORTED reported reply text', 'reported', 10 );
$GLOBALS['WP']['comment_queries'] = array();
$HV = page_html();
$CARD_QUERIES = array_filter( $GLOBALS['WP']['comment_queries'], function ( $a ) {
	return isset( $a['type'] ) && 'vance_reply' === $a['type'] && ! isset( $a['count'] );
} );
check( 'visitor: the page asks the store for the replies of each card (the query is recorded)', count( $CARD_QUERIES ) > 0 );
check( 'visitor: every reply query asks for one reply only, so the other replies are never fetched', 0 === count( array_filter( $CARD_QUERIES, function ( $a ) {
	return ! isset( $a['number'] ) || 1 !== (int) $a['number'];
} ) ) );
check( 'visitor: the first approved reply (the oldest) is in the HTML', has( $HV, 'ZZONE first reply text' ) );
check( 'visitor: the second approved reply is NOT in the HTML', ! has( $HV, 'ZZTWO' ) );
check( 'visitor: the third approved reply is NOT in the HTML', ! has( $HV, 'ZZTHREE' ) );
check( 'visitor: the fourth approved reply is NOT in the HTML', ! has( $HV, 'ZZFOUR' ) );
check( 'visitor: a waiting reply is NOT in the HTML', ! has( $HV, 'ZZWAITING' ) );
check( 'visitor: a rejected reply is NOT in the HTML', ! has( $HV, 'ZZREJECTED' ) );
check( 'visitor: a reported reply is NOT in the HTML', ! has( $HV, 'ZZREPORTED' ) );
check( 'visitor: the line says three more replies for members', has( $HV, '3 more replies for members' ) );
check( 'visitor: three grey bars stand in for the held-back replies', 3 === substr_count( $HV, '<span class="vcom-locked__bar"></span>' ) );
check( 'visitor: the reply link reads 4 replies (the approved count)', has( $HV, '>4 replies</a>' ) );
check( 'visitor: the question title and body are shown', has( $HV, 'Visitor sees this title' ) && has( $HV, 'Question body text' ) );
check( 'visitor: "Join free to read" is offered for the held-back replies', has( $HV, 'Join free to read' ) );
check( 'visitor: the config holds member false and no nonce or ajax address', has( $HV, '&quot;member&quot;:false' ) && ! has( $HV, 'nonce' ) && ! has( $HV, 'admin-ajax' ) );
check( 'visitor: no member control is rendered (no toggle, no form, no vote button, no textarea)', ! has( $HV, 'data-vcom-toggle' ) && ! has( $HV, 'data-vcom-form' ) && ! has( $HV, 'data-vcom-vote' ) && ! has( $HV, '<textarea' ) );
check( 'visitor: Me too is a join link, with no pressed state', has( $HV, 'href="https://example.test/login/?tab=signup' ) && ! has( $HV, 'aria-pressed' ) );
check( 'visitor: the ask form is replaced by the join bar', has( $HV, 'Join free to ask' ) && ! has( $HV, 'data-vcom-form="ask"' ) );
check( 'visitor: no report control is rendered', ! has( $HV, 'vcom-report__form' ) && has( $HV, 'vcom-report__link' ) );

arrange( on_mods() );
cs_seed_question( 6, 'waiting', 'ZZPENDING waiting question', '', 0, 'ibd' );
cs_seed_question( 6, 'rejected', 'ZZDRAFTED rejected question', '', 0, 'ibd' );
cs_seed_question( 6, 'reported', 'ZZHIDDEN reported question', '', 0, 'ibd' );
$HQ = page_html();
check( 'visitor: a waiting question is not on the page', ! has( $HQ, 'ZZPENDING' ) );
check( 'visitor: a rejected question is not on the page', ! has( $HQ, 'ZZDRAFTED' ) );
check( 'visitor: a reported question is not on the page', ! has( $HQ, 'ZZHIDDEN' ) );
check( 'visitor: with no published question the empty state invites a join', has( $HQ, 'No questions yet' ) && has( $HQ, 'Join free and ask the first one.' ) );

/* ---- 3. the member view ---------------------------------------------------------------- */
section( '3. The member view: every approved reply, the controls, the config' );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$Q = cs_seed_question( 6, 'approved', 'Member sees this title', 'Member body', 0, 'ibd' );
$R1 = cs_seed_reply( $Q, 6, 'ZZONE first reply text', 'approved', 300 );
cs_seed_reply( $Q, 6, 'ZZTWO second reply text', 'approved', 200 );
cs_seed_reply( $Q, 6, 'ZZTHREE third reply text', 'approved', 100 );
cs_seed_reply( $Q, 6, 'ZZFOUR fourth reply text', 'approved', 50 );
cs_seed_reply( $Q, 6, 'ZZWAITING waiting reply text', 'waiting', 10 );
cs_seed_reply( $Q, 6, 'ZZREJECTED rejected reply text', 'rejected', 10 );
add_post_meta( $Q, '_vance_community_metoo', '6' );
add_post_meta( $Q, '_vance_community_metoo', '7' );
add_comment_meta( $R1, '_vance_community_helpful', '5' );
$HM = page_html();
check( 'member: the replies are printed, oldest first', has( $HM, 'ZZONE first reply text' ) && has( $HM, 'ZZFOUR fourth reply text' ) && strpos( $HM, 'ZZONE' ) < strpos( $HM, 'ZZFOUR' ) );
check( 'member: the held-back replies sit behind a "Show 3 more replies" disclosure', has( $HM, 'Show 3 more replies' ) );
check( 'member: a waiting or rejected reply is still not printed', ! has( $HM, 'ZZWAITING' ) && ! has( $HM, 'ZZREJECTED' ) );
check( 'member: the config carries member true, the nonce and the ajax address', has( $HM, '&quot;member&quot;:true' ) && has( $HM, 'nonce-vance_community_nonce' ) && has( $HM, 'admin-ajax.php' ) );
check( 'member: Me too shows the stored count (2) and is off, as this member has not tapped it', has( $HM, 'data-vcom-toggle="metoo" data-id="' . $Q . '" aria-pressed="false"' ) && has( $HM, '<span data-vcom-count>2</span>' ) );
check( 'member: Helpful on the first reply is on, as this member marked it', has( $HM, 'data-vcom-toggle="helpful" data-id="' . $R1 . '" aria-pressed="true"' ) );
check( 'member: each question has a report form', has( $HM, 'data-vcom-form="report"' ) );
check( 'member: the reply form has the minimum and maximum length from the limits', has( $HM, 'minlength="2" maxlength="1000"' ) );
check( 'member: the ask form has the title limits (5 to 150) and the body limit (1000)', has( $HM, 'minlength="5" maxlength="150"' ) && has( $HM, 'maxlength="1000"' ) );
check( 'member: the ask form is shown in place of the join bar', has( $HM, 'data-vcom-form="ask"' ) && ! has( $HM, 'Join free to ask' ) );
check( 'member: "You post as" shows the first name in the name slot', has( $HM, '<strong data-vcom-name>Mary</strong>' ) );
check( 'member: the name settings form is present', has( $HM, 'data-vcom-form="profile"' ) );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$Q = cs_seed_question( 6, 'approved', 'Me too tapped', '', 0, 'ibd' );
add_post_meta( $Q, '_vance_community_metoo', '5' );
add_post_meta( $Q, '_vance_community_metoo', '6' );
$HT = page_html();
check( 'member: Me too is on when this member has tapped it, with the count of 2', has( $HT, 'data-vcom-toggle="metoo" data-id="' . $Q . '" aria-pressed="true"' ) && has( $HT, '<span data-vcom-count>2</span>' ) );

/* ---- 4. Your posts ----------------------------------------------------------------------- */
section( '4. "Your posts": own items with their state and reason' );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
cs_seed_question( 5, 'waiting', 'Mine waiting question', '', 0, 'ibd' );
$MR = cs_seed_question( 5, 'rejected', 'Mine rejected question', '', 0, 'ibd' );
update_post_meta( $MR, '_vance_community_reject_reason', 'offtopic' );
cs_seed_question( 6, 'waiting', 'Not mine question', '', 0, 'ibd' );
$HP = page_html();
check( 'your posts: the block is shown to a member who has posts', has( $HP, 'YOUR POSTS' ) );
check( 'your posts: a waiting question shows its state', has( $HP, 'Mine waiting question' ) && has( $HP, 'Waiting for a moderator' ) );
check( 'your posts: a rejected question shows "Not published" with its reason', has( $HP, 'Mine rejected question' ) && has( $HP, 'Not published: Off topic' ) );
check( 'your posts: another member\'s waiting question is not listed', ! has( $HP, 'Not mine question' ) );

arrange( on_mods(), array(), 5 );
member_setup();
$HN = page_html();
check( 'your posts: a member with no posts gets no block', ! has( $HN, 'YOUR POSTS' ) );

arrange( on_mods() );
cs_seed_question( 6, 'waiting', 'Visitor cannot see posts', '', 0, 'ibd' );
check( 'your posts: a visitor never gets the block', ! has( page_html(), 'YOUR POSTS' ) );

/* ---- 5. the weekly poll ------------------------------------------------------------------- */
section( '5. The weekly poll: no block without a poll, buttons before the vote, result after it' );

arrange( on_mods(), array(), 5 );
member_setup();
$HNO = page_html();
check( 'poll: no active poll means no poll block', ! has( $HNO, 'class="vcom-poll"' ) );

arrange( on_mods(), array(), 5 );
member_setup();
cs_poll( array( 'Only', 'Two' ) );
check( 'poll: an active poll with fewer than three answers is not shown', ! has( page_html(), 'class="vcom-poll"' ) );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
member_setup( 7, 'ann@example.test', array( 'first_name' => 'Ann' ) );
$P = cs_poll( array( 'Bloating', 'Pain', 'Tiredness' ) );
$HB = page_html();
check( 'poll: before the member votes, each answer is a vote button', has( $HB, 'data-vcom-vote="0"' ) && has( $HB, 'data-vcom-vote="2"' ) );
check( 'poll: before the vote, no result is shown', ! has( $HB, 'vcom-poll__results' ) && ! has( $HB, '%</span>' ) );
check( 'poll: before any vote, the line says to vote to see the result', has( $HB, 'Vote to see the result.' ) );

add_user_meta( 6, '_vance_community_vote_' . $P, '0' );
add_user_meta( 7, '_vance_community_vote_' . $P, '0' );
add_user_meta( 5, '_vance_community_vote_' . $P, '1' );
$HR = page_html();
check( 'poll: after the member votes, the result block is shown', has( $HR, 'vcom-poll__results' ) && ! has( $HR, 'data-vcom-vote' ) );
check( 'poll: the shares are whole percent of the votes: 67, 33 and 0', has( $HR, '>67%</span>' ) && has( $HR, '>33%</span>' ) && has( $HR, '>0%</span>' ) );
check( 'poll: the member\'s own answer is marked "(your vote)"', has( $HR, 'Pain <em>(your vote)</em>' ) );
check( 'poll: the total line says 3 members voted', has( $HR, '3 members voted.' ) );

arrange( on_mods(), array(), 0 );
cs_poll( array( 'Bloating', 'Pain', 'Tiredness' ) );
$PV = page_html();
check( 'poll, visitor: the answers are join links, not vote buttons', has( $PV, 'class="vcom-poll__opt" href="https://example.test/login/' ) && ! has( $PV, 'data-vcom-vote' ) );
check( 'poll, visitor: a poll with no votes says to join and vote', has( $PV, 'Vote to see the result. Join free to vote.' ) );

arrange( on_mods(), array(), 0 );
$PVV = cs_poll( array( 'Bloating', 'Pain', 'Tiredness' ) );
add_user_meta( 6, '_vance_community_vote_' . $PVV, '0' );
add_user_meta( 7, '_vance_community_vote_' . $PVV, '0' );
$PVR = page_html();
check( 'poll, visitor: with votes cast, the visitor still sees no result', ! has( $PVR, 'vcom-poll__results' ) && ! has( $PVR, '>100%</span>' ) && ! has( $PVR, '%</span>' ) );
check( 'poll, visitor: the visitor sees the count and that a result waits for a vote', has( $PVR, '2 members voted. Vote to see the result.' ) );

/* ---- 6. escaping ------------------------------------------------------------------------- */
section( '6. Escaping: seeded markup in title, body, reply, nickname and poll title' );

arrange( on_mods(), array(), 5 );
member_setup();
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$E = cs_seed_question( 6, 'approved', '<script>alert(1)</script>Escaped title', '<script>evil()</script>Body kept', 0, 'ibd' );
cs_seed_reply( $E, 6, '<img src=x onerror=alert(2)>Reply kept', 'approved', 10 );
update_user_meta( 6, '_vance_community_name_mode', 'nickname' );
update_user_meta( 6, '_vance_community_nickname', '<i>Nick</i>' );
cs_poll( array( 'A', 'B', 'C' ), 'publish', true, '<script>poll()</script>Poll title' );
$HE = page_html();
check( 'escape: no <script element anywhere on the page', ! has( $HE, '<script' ) );
check( 'escape: a script in the title is shown as text', has( $HE, '&lt;script&gt;alert(1)&lt;/script&gt;Escaped title' ) );
check( 'escape: a script in the body is removed with its content', has( $HE, 'Body kept' ) && ! has( $HE, 'evil()' ) );
check( 'escape: an image tag with an event handler in a reply is removed', has( $HE, 'Reply kept' ) && ! has( $HE, 'onerror' ) );
check( 'escape: a nickname with markup is shown as text', has( $HE, '&lt;i&gt;Nick&lt;/i&gt;' ) && ! has( $HE, '<i>Nick' ) );
check( 'escape: a poll title with a script is shown as text', has( $HE, '&lt;script&gt;poll()&lt;/script&gt;Poll title' ) );
check( 'escape: the config attribute holds no raw quote that could end it early', 1 === preg_match( '#data-config="[^"]*"#', $HE ) );

/* ---- 7. the data rule: links only when their page is published ----------------------------- */
section( '7. The data rule: the rules and VANCE-Ai links appear only for a published page' );

arrange( on_mods(), array(), 5 );
member_setup();
cs_page( 'community-rules', 'publish' );
cs_page( 'ask-ai', 'publish' );
$HD = page_html();
check( 'data rule: a published rules page gives a "Read the community rules" link', has( $HD, 'Read the community rules' ) );
check( 'data rule: a published ask page gives a VANCE-Ai link that opens the modal', has( $HD, 'href="https://example.test/ask-ai/" data-vance-askai-open="new"' ) );

arrange( on_mods(), array(), 5 );
member_setup();
cs_page( 'community-rules', 'draft' );
$HD2 = page_html();
check( 'data rule: a draft rules page gives no rules link', ! has( $HD2, 'Read the community rules' ) );
check( 'data rule: with no ask page, the VANCE-Ai control is a button that still opens the modal', has( $HD2, '<button type="button" class="vcom-btn vcom-btn--white" data-vance-askai-open="new"' ) );

/* ---- 8. the feed ----------------------------------------------------------------------------- */
section( '8. The feed: space filter, empty space, paging' );

arrange( on_mods() );
cs_seed_question( 6, 'approved', 'Carers question here', '', 0, 'carers' );
cs_seed_question( 6, 'approved', 'IBD question here', '', 0, 'ibd' );
$_GET = array( 'space' => 'carers' );
$HS = page_html();
check( 'feed: a space filter shows only that space', has( $HS, 'Carers question here' ) && ! has( $HS, 'IBD question here' ) );
check( 'feed: the chosen space chip is marked as the current one', has( $HS, 'class="vcom-space is-current" href="https://example.test/community/?space=carers" aria-current="page"' ) );

$_GET = array( 'space' => 'nonsense' );
$HU = page_html();
check( 'feed: an unknown space is ignored and every question shows', has( $HU, 'Carers question here' ) && has( $HU, 'IBD question here' ) );

$_GET = array( 'space' => 'work-and-travel' );
$HE2 = page_html();
check( 'feed: a space with no questions says so', has( $HE2, 'No questions in this space yet' ) );

arrange( on_mods() );
cs_repeat( 11, function ( $i ) {
	cs_seed_question( 6, 'approved', 'Feed question ' . $i, '', 60 * ( $i + 1 ), 'ibd' );
} );
$_GET = array();
$HP1 = page_html();
check( 'feed: page 1 shows ten questions', 10 === card_count( $HP1 ) );
check( 'feed: page 1 offers "Show more posts" to page 2', has( $HP1, 'vcom-more-posts' ) && has( $HP1, 'vcpage=2' ) );
$_GET = array( 'vcpage' => '2' );
$HP2 = page_html();
check( 'feed: page 2 shows the eleventh question only', 1 === card_count( $HP2 ) && has( $HP2, 'Feed question 10' ) );
check( 'feed: page 2 offers "Newer posts" back to page 1 and no "Show more posts"', has( $HP2, 'Newer posts' ) && ! has( $HP2, 'vcom-more-posts' ) );

/* ---- 9. the header link ---------------------------------------------------------------------- */
section( '9. The header: the Community link and its current-page mark' );

arrange( on_mods() );
cs_page( 'tools-resources' );
cs_page( 'community' );
$_SERVER['REQUEST_URI'] = '/community/';
$NAV = vance_hub_header_nav_html();
check( 'header nav: the Community link points at the community page', has( $NAV, 'href="https://example.test/community/"' ) && has( $NAV, '>Community</a>' ) );
check( 'header nav: on the community page the Community link is marked aria-current', 1 === preg_match( '#href="https://example\.test/community/" aria-current="page">Community</a>#', $NAV ) );
$_SERVER['REQUEST_URI'] = '/tools-resources/';
$NAV2 = vance_hub_header_nav_html();
check( 'header nav: on another page the Community link is still there but not marked', has( $NAV2, 'href="https://example.test/community/">Community</a>' ) );
foreach ( $GLOBALS['WP']['posts'] as $DRAFTP ) {
	if ( 'page' === $DRAFTP->post_type && 'community' === $DRAFTP->post_name ) {
		wp_update_post( array( 'ID' => $DRAFTP->ID, 'post_status' => 'draft' ) );
	}
}
check( 'header nav: a draft /community/ page gives no Community link', ! has( vance_hub_header_nav_html(), 'Community</a>' ) );

arrange();
cs_page( 'tools-resources' );
cs_page( 'community' );
$NAVOFF = vance_hub_header_nav_html();
check( 'header nav: switch off: no Community link, though the page exists', ! has( $NAVOFF, 'Community</a>' ) && ! has( $NAVOFF, '/community/' ) );

arrange( on_mods() );
$GLOBALS['WP']['page_slug'] = 'community';
$_SERVER['REQUEST_URI'] = '/community/';
check( 'header: the club header is used on the community page with the switch on', true === vance_hub_header_active() );
arrange();
$GLOBALS['WP']['page_slug'] = 'community';
check( 'header: switch off: the community page does not force the club header', false === vance_hub_header_active() );

/* ---- 10. the queue screen ---------------------------------------------------------------------- */
section( '10. The queue screen: escaping, flags, reasons, nonces, notices, capability' );

arrange( on_mods(), admin_caps(), 1 );
cs_user( 1, 'admin@example.test' );
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$QW = cs_seed_question( 6, 'waiting', '<script>queue()</script>Queue title Vance', 'Queue body', 0, 'ibd' );
$QRP = cs_seed_question( 6, 'approved', 'Reported question here', '', 0, 'ibd' );
add_post_meta( $QRP, '_vance_community_report', '5|spam|' . time() );
update_post_meta( $QRP, '_vance_community_state', 'reported' );
$QH = queue_html();
check( 'queue: the queue screen is printed for a moderator', has( $QH, 'Community queue' ) && has( $QH, 'vcom-queue' ) );
check( 'queue: a title with a script is escaped and the script content is not printed', has( $QH, '&lt;script&gt;queue()&lt;/script&gt;Queue title Vance' ) === true && ! has( $QH, '<script' ) );
check( 'queue: a post that names Vance carries the "Names a product" flag', has( $QH, 'Names a product' ) );
check( 'queue: the Waiting tab shows its count of one', has( $QH, 'Waiting <span class="count">(1)</span>' ) );
check( 'queue: every approve and reject form carries the moderation nonce field', has( $QH, 'name="_wpnonce" value="nonce-vance_community_moderate"' ) && has( $QH, 'name="action" value="vance_community_moderate"' ) );
check( 'queue: the forms post to admin-post.php', has( $QH, 'action="https://example.test/wp-admin/admin-post.php"' ) );
check( 'queue: the reject control lists the five reject reasons', has( $QH, '>Gives medical advice<' ) && has( $QH, '>Product or health claim<' ) && has( $QH, '>Unkind or abusive<' ) && has( $QH, '>Shares personal details<' ) && has( $QH, '>Off topic<' ) );

arrange( on_mods(), admin_caps(), 1 );
cs_user( 1, 'admin@example.test' );
member_setup( 6, 'tom@example.test', array( 'first_name' => 'Tom' ) );
$QRP2 = cs_seed_question( 6, 'approved', 'Reported question here', '', 0, 'ibd' );
add_post_meta( $QRP2, '_vance_community_report', '5|spam|' . time() );
add_post_meta( $QRP2, '_vance_community_report', '7|spam|' . time() );
update_post_meta( $QRP2, '_vance_community_state', 'reported' );
$_GET = array( 'tab' => 'reported' );
$QH2 = queue_html();
check( 'queue: the Reported tab names each report reason with its count', has( $QH2, 'Reported: Spam or selling (2)' ) );

$_GET = array( 'vcom_done' => 'approved' );
check( 'queue: an approved notice is shown after an approve', has( queue_html(), 'Approved. The post shows on the community page.' ) );
$_GET = array( 'vcom_done' => 'error' );
check( 'queue: an error notice is shown after a refused change', has( queue_html(), 'The change was not saved. Choose a reason to reject, then try again.' ) );
$_GET = array();

arrange( array(), admin_caps(), 1 );
cs_user( 1, 'admin@example.test' );
check( 'queue: with the master switch classic, the queue warns that visitors cannot see the page', has( queue_html(), 'The Community hub is off (its own switch, or the Home layout master switch).' ) );

// The master switch (Home layout) over the queue notice.
arrange( array( 'vance_hub_community' => true ), admin_caps(), 1 );
cs_user( 1, 'admin@example.test' );
check( 'queue: master classic with the community box ticked still warns that the hub is off', has( queue_html(), 'The Community hub is off (its own switch, or the Home layout master switch).' ) );

arrange( array( 'vance_home_layout' => 'hub' ), admin_caps(), 1 );
cs_user( 1, 'admin@example.test' );
check( 'queue: master hub with the community box never saved shows no off warning', false === strpos( queue_html(), 'The Community hub is off' ) );

arrange( on_mods(), array(), 5 );
member_setup();
check( 'queue: a member without the moderation capability gets no queue screen', '' === queue_html() );

/* ---- 11. wiring: the script, the template, the server contract ------------------------------------- */
section( '11. Wiring: the script and the server agree; the template calls the renderer' );

$JS  = file_get_contents( $THEME . '/assets/js/hub-community.js' );
$TPL = file_get_contents( $THEME . '/page-community.php' );
$PHP = file_get_contents( $THEME . '/inc/hub-community.php' );

preg_match_all( "/'(vance_community_[a-z]+)'/", $JS, $JS_ACTIONS );
$JS_NAMES = array_values( array_unique( $JS_ACTIONS[1] ) );
sort( $JS_NAMES );
$PHP_NAMES = vance_community_ajax_actions();
sort( $PHP_NAMES );
check( 'wiring: the script names the same seven AJAX actions the server registers', $PHP_NAMES === $JS_NAMES && 7 === count( $JS_NAMES ) );
check( 'wiring: the script sends the nonce as the field "nonce", the field the server checks', has( $JS, "body.append('nonce', config.nonce)" ) && has( $PHP, "check_ajax_referer( 'vance_community_nonce', 'nonce', false )" ) );

preg_match_all( '/innerHTML\s*=\s*([^;]+);/', $JS, $INNER );
check( 'wiring: the script assigns innerHTML only with the empty string (server text goes in with textContent)', array( "''" ) === $INNER[1] );
preg_match_all( '/outerHTML\s*=\s*([^;]+);/', $JS, $OUTER );
check( 'wiring: the one outerHTML assignment takes the server-built poll block only', array( 'res.data.html' ) === $OUTER[1] );

check( 'wiring: the template calls the renderer, inside the theme header and footer', has( $TPL, 'vance_community_render_page()' ) && has( $TPL, 'get_header()' ) && has( $TPL, 'get_footer()' ) );

/* ---- 12. sample data: the feed, the names and the queue box, with the filters run ----------- */
section( '12. Sample data: the feed, the names and the queue box (filters run)' );

arrange( on_mods(), admin_caps(), 1 );
vance_community_sample_load( time() );
cs_seed_question( 0, 'approved', 'A real question in the feed', '', 0, 'ibd' );
$GLOBALS['WP']['run_filters'] = true;
$_GET = array( 'space' => 'carers' );
$SAMPLE_STAFF = page_html();
check( 'sample feed: staff see the carers sample questions', has( $SAMPLE_STAFF, 'How do I help my wife through a bad flare without fussing?' ) );
check( 'sample feed: staff see the stored asker name on the card', has( $SAMPLE_STAFF, '<span class="vcom-card__name">Gaz</span>' ) );
check( 'sample feed: staff see the stored reply name on the first reply', has( $SAMPLE_STAFF, '<strong>Beth</strong>' ) );

arrange( on_mods(), array(), 5 );
member_setup();
vance_community_sample_load( time() );
cs_seed_question( 0, 'approved', 'A real question in the feed', '', 0, 'ibd' );
$GLOBALS['WP']['run_filters'] = true;
$_GET = array( 'space' => 'carers' );
$SAMPLE_MEM = page_html();
check( 'sample feed: a member with the carers filter sees no sample question and no sample name', ! has( $SAMPLE_MEM, 'How do I help my wife' ) && ! has( $SAMPLE_MEM, 'Gaz' ) && has( $SAMPLE_MEM, 'No questions in this space yet' ) );

arrange( on_mods(), array(), 0 );
vance_community_sample_load( time() );
cs_seed_question( 0, 'approved', 'A real question in the feed', '', 0, 'ibd' );
$GLOBALS['WP']['run_filters'] = true;
$_GET = array();
$ALL_VIS = page_html();
check( 'sample feed: a visitor sees the one real question and no sample question', 1 === card_count( $ALL_VIS ) && has( $ALL_VIS, 'A real question in the feed' ) && ! has( $ALL_VIS, 'How do I help my wife' ) );

arrange( on_mods(), admin_caps(), 1 );
$_GET = array();
$QUEUE_EMPTY = queue_html();
check( 'queue box: with no samples the queue screen offers Load sample data', has( $QUEUE_EMPTY, 'Load sample data' ) && ! has( $QUEUE_EMPTY, 'Remove sample data' ) );
vance_community_sample_load( time() );
$QUEUE_FULL = queue_html();
check( 'queue box: with samples the queue screen says 22 and offers Remove', has( $QUEUE_FULL, 'Sample questions now: 22' ) && has( $QUEUE_FULL, 'Remove sample data' ) );

arrange( on_mods(), array( 'vance_moderate_community' => true ), 2 );
$QUEUE_ED = queue_html();
check( 'queue box: an editor without manage_options sees no sample box', ! has( $QUEUE_ED, 'Sample data for team review' ) );
unset( $GLOBALS['WP']['run_filters'] );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
