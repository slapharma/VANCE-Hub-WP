<?php
/**
 * Tests for the Community sample data: inc/hub-community-sample.php.
 *
 * Same shape as hub-community.test.php: the shared stubs, check() lines, and
 * exit 1 on any failure. The stub apply_filters() returns its value unchanged,
 * so the two filter callbacks are called directly here, and their registration
 * is checked separately.
 *
 * WHAT IS COVERED
 *   1  the data: 20 questions, the space split, reply counts, text limits,
 *      no product names, plain text only
 *   2  load: posts, replies, marker, names, state, spaces, counts, dates
 *   3  load is refused when sample data exists (idempotent)
 *   4  author name: stored name only for a marked, author-less item
 *   5  feed: staff see samples, members and visitors do not
 *   6  remove: only sample questions and their replies go; real posts stay
 *   7  admin handlers: capability, nonce, redirect, box and notice
 *   8  failure paths: a refused insert or delete (stub switches)
 *   9  stored names and counts match the data rows; load dates; no spaces
 *  10  remove: draft and trash too, and a non-question with the marker stays
 *  11  feed: a space filter for staff and for members
 *  12  handlers: remove needs a good nonce; redirect target; box form fields
 *  13  the filters run through apply_filters: name and feed pipeline
 *
 * Run: cd tests && php hub-community-sample.test.php
 */

require_once __DIR__ . '/hub-community-stubs.php';

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

set_error_handler( function ( $no, $str, $file, $line ) {
	if ( ! ( error_reporting() & $no ) ) {
		return false;
	}
	throw new ErrorException( $str, 0, $no, $file, $line );
} );

require_once $THEME . '/inc/hub-layout.php';
require_once $THEME . '/inc/hub-community.php';
require_once $THEME . '/inc/hub-community-view.php';
require_once $THEME . '/inc/hub-community-admin.php';
require_once $THEME . '/inc/hub-community-sample.php';

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

function arrange( array $caps = array(), $user = 0 ) {
	cs_reset();
	$GLOBALS['WP']['caps'] = $caps;
	$GLOBALS['WP']['user'] = (int) $user;
	vance_community_register_types();
	vance_community_seed_spaces();
}
function act_as( $user, array $caps ) {
	$GLOBALS['WP']['user'] = (int) $user;
	$GLOBALS['WP']['caps'] = $caps;
}
function staff_caps() {
	return array( 'vance_moderate_community' => true, 'manage_options' => true );
}
function sample_questions() {
	return array_values( array_filter( $GLOBALS['WP']['posts'], function ( $p ) {
		return 'vance_question' === $p->post_type && cs_has_meta( 'post', $p->ID, '_vance_community_sample', '1' );
	} ) );
}
function sample_replies() {
	return array_values( array_filter( $GLOBALS['WP']['comments'], function ( $c ) {
		return 'vance_reply' === $c->comment_type && cs_has_meta( 'comment', $c->comment_ID, '_vance_community_sample', '1' );
	} ) );
}
function die_of( $fn ) {
	try { $fn(); } catch ( CsDie $d ) { return $d; } catch ( CsRedirect $r ) { return null; }
	return null;
}
function redirect_of( $fn ) {
	try { $fn(); } catch ( CsRedirect $r ) { return $r->location; } catch ( CsDie $d ) { return null; }
	return null;
}

/* ===================================================================== */
section( '1. The data' );

$DATA   = vance_community_sample_data();
$SPACES = vance_community_spaces();
$BY     = array();
$RCOUNT = array();
$BAD    = array();
$TERMS  = vance_community_product_terms();
foreach ( $DATA as $i => $row ) {
	list( $space, $asker, $days, $metoo, $title, $text, $replies ) = $row;
	$BY[ $space ] = ( $BY[ $space ] ?? 0 ) + 1;
	$RCOUNT[]     = count( $replies );
	$tl = vance_community_strlen( $title );
	if ( ! isset( $SPACES[ $space ] ) ) { $BAD[] = "q$i space"; }
	if ( $tl < vance_community_limit( 'title_min' ) || $tl > vance_community_limit( 'title_max' ) ) { $BAD[] = "q$i title length"; }
	if ( vance_community_strlen( $text ) > vance_community_limit( 'text_max' ) ) { $BAD[] = "q$i text length"; }
	if ( vance_community_plain_text( $title, 1000, false ) !== $title || vance_community_plain_text( $text, 1000 ) !== $text ) { $BAD[] = "q$i not plain"; }
	if ( vance_community_text_names_product( $title . ' ' . $text, $TERMS ) ) { $BAD[] = "q$i product"; }
	if ( (int) $days < 1 || (int) $metoo < 0 || '' === trim( $asker ) ) { $BAD[] = "q$i numbers"; }
	foreach ( $replies as $n => $r ) {
		list( $name, $helpful, $body ) = $r;
		$bl = vance_community_strlen( $body );
		if ( $bl < vance_community_limit( 'reply_min' ) || $bl > vance_community_limit( 'reply_max' ) ) { $BAD[] = "q$i r$n length"; }
		if ( vance_community_plain_text( $body, 1000 ) !== $body ) { $BAD[] = "q$i r$n not plain"; }
		if ( vance_community_text_names_product( $body, $TERMS ) ) { $BAD[] = "q$i r$n product"; }
		if ( '' === trim( $name ) || false !== strpos( $name, '@' ) || (int) $helpful < 0 ) { $BAD[] = "q$i r$n name or count"; }
	}
}
check( 'data: 20 questions', 20 === count( $DATA ) );
check( 'data: the space split is ibd 3, UC 3, Crohn\'s 3, IBS 4, food 3, work 2, carers 2', array( 'ibd' => 3, 'ulcerative-colitis' => 3, 'crohns-disease' => 3, 'ibs' => 4, 'food-and-recipes' => 3, 'work-and-travel' => 2, 'carers' => 2 ) == $BY );
check( 'data: every space has at least one question', count( $BY ) === count( $SPACES ) );
check( 'data: reply counts are 4,7,2,9,1,5,10,3,6,8,2,5,7,1,9,4,3,6,10,2', array( 4, 7, 2, 9, 1, 5, 10, 3, 6, 8, 2, 5, 7, 1, 9, 4, 3, 6, 10, 2 ) === $RCOUNT );
check( 'data: every count is between 1 and 10', min( $RCOUNT ) >= 1 && max( $RCOUNT ) <= 10 );
check( 'data: every text is in the limits, plain, and names no product (' . implode( ', ', $BAD ) . ')', array() === $BAD );
check( 'data: the product check can fail (negative control)', vance_community_text_names_product( 'I tried EPAVANCE', $TERMS ) );
check( 'data: titles are unique', count( array_unique( array_column( $DATA, 4 ) ) ) === count( $DATA ) );

/* ===================================================================== */
section( '2. Load' );

arrange( staff_caps(), 1 );
$NOW = time();
$MADE = vance_community_sample_load( $NOW );
$QS   = sample_questions();
$RS   = sample_replies();
check( 'load: returns 20', 20 === $MADE );
check( 'load: 20 sample questions exist', 20 === count( $QS ) && 20 === vance_community_sample_count() );
check( 'load: 104 sample replies exist', array_sum( $RCOUNT ) === count( $RS ) && 104 === count( $RS ) );
check( 'load: questions are published, with no author and the approved state', array() === array_filter( $QS, function ( $q ) {
	return 'publish' !== $q->post_status || 0 !== (int) $q->post_author || 'approved' !== get_post_meta( $q->ID, '_vance_community_state', true );
} ) );
check( 'load: replies are approved vance_reply comments with no user and no email', array() === array_filter( $RS, function ( $r ) {
	return '1' !== (string) $r->comment_approved || 0 !== (int) $r->user_id || '' !== (string) $r->comment_author_email || 'approved' !== get_comment_meta( $r->comment_ID, '_vance_community_state', true );
} ) );
$Q0 = null;
foreach ( $QS as $q ) { if ( $q->post_title === $DATA[0][4] ) { $Q0 = $q; } }
check( 'load: the first question has its stored name and its space', $Q0 && 'Sarah' === get_post_meta( $Q0->ID, '_vance_community_sample_name', true ) && 'ibd' === cs_space_of( $Q0->ID ) );
check( 'load: the first question has 6 me-too rows, each with the value 0', $Q0 && 6 === vance_community_meta_rows( 'post', $Q0->ID, '_vance_community_metoo' ) && array( '0' ) === array_values( array_unique( array_map( 'strval', get_post_meta( $Q0->ID, '_vance_community_metoo' ) ) ) ) );
$R0 = $Q0 ? vance_community_replies( $Q0->ID ) : array();
check( 'load: the first question has its 4 replies in order, with names and helpful rows', 4 === count( $R0 ) && 'Jas' === $R0[0]->comment_author && 'Sarah' === $R0[3]->comment_author && 6 === vance_community_meta_rows( 'comment', $R0[0]->comment_ID, '_vance_community_helpful' ) );
check( 'load: a sample row is never a real member\'s row', $Q0 && ! vance_community_has_row( 'post', $Q0->ID, '_vance_community_metoo', 1 ) );
$SPACE_OK = array_filter( $QS, function ( $q ) { return '' === cs_space_of( $q->ID ); } );
check( 'load: every question is in a space', array() === $SPACE_OK );
$DATE_BAD = array();
foreach ( $QS as $q ) {
	$qt = strtotime( $q->post_date_gmt . ' UTC' );
	if ( $qt >= $NOW ) { $DATE_BAD[] = 'q' . $q->ID . ' future'; }
	$prev = $qt;
	foreach ( vance_community_replies( $q->ID ) as $r ) {
		$rt = strtotime( $r->comment_date_gmt . ' UTC' );
		if ( $rt <= $prev || $rt >= $NOW ) { $DATE_BAD[] = 'r' . $r->comment_ID; }
		$prev = $rt;
	}
}
check( 'load: questions are in the past and replies follow them in order, before now (' . implode( ',', array_slice( $DATE_BAD, 0, 5 ) ) . ')', array() === $DATE_BAD );
$OLDEST = min( array_map( function ( $q ) { return strtotime( $q->post_date_gmt . ' UTC' ); }, $QS ) );
check( 'load: the oldest question is about 24 days old', $NOW - $OLDEST >= 24 * DAY_IN_SECONDS && $NOW - $OLDEST < 25 * DAY_IN_SECONDS );

/* ===================================================================== */
section( '3. Load is refused when sample data exists' );

$AGAIN = vance_community_sample_load( $NOW );
check( 'refuse: a second load returns 0', 0 === $AGAIN );
check( 'refuse: a second load adds nothing', 20 === count( sample_questions() ) && 104 === count( sample_replies() ) );

arrange( staff_caps(), 1 );
vance_community_sample_load( $NOW );
$ONE = sample_questions()[0];
wp_update_post( array( 'ID' => $ONE->ID, 'post_status' => 'draft' ) );
check( 'refuse: one rejected (draft) sample question still blocks a new load', 0 === vance_community_sample_load( $NOW ) && 20 === count( sample_questions() ) );

/* ===================================================================== */
section( '4. Author name' );

arrange( staff_caps(), 1 );
vance_community_sample_load( $NOW );
$Q   = sample_questions()[0];
$QN  = get_post_meta( $Q->ID, '_vance_community_sample_name', true );
$R   = vance_community_replies( $Q->ID )[0];
$RN  = get_comment_meta( $R->comment_ID, '_vance_community_sample_name', true );
check( 'name: a sample question shows its stored name', $QN === vance_community_sample_author_name( 'Member', 'question', $Q->ID, 0 ) );
check( 'name: a sample reply shows its stored name', $RN === vance_community_sample_author_name( 'Member', 'reply', $R->comment_ID, 0 ) );
check( 'name: kind decides the meta type (a reply ID read as a question has no marker)', 'Member' === vance_community_sample_author_name( 'Member', 'question', $R->comment_ID, 0 ) );
$REAL = cs_seed_question( 0, 'approved', 'A real question with no author' );
check( 'name: an author-less real question (an erased member) keeps "Member"', 'Member' === vance_community_sample_author_name( 'Member', 'question', $REAL, 0 ) );
update_post_meta( $Q->ID, '_vance_community_sample_name', 'Someone' );
check( 'name: a real author always wins over a stored name', 'Ann' === vance_community_sample_author_name( 'Ann', 'question', $Q->ID, 7 ) );
update_post_meta( $Q->ID, '_vance_community_sample_name', '   ' );
check( 'name: an empty stored name falls back to the name given', 'Member' === vance_community_sample_author_name( 'Member', 'question', $Q->ID, 0 ) );
check( 'name: author_name() returns the display name when no filter runs', 'Member' === vance_community_author_name( 'question', $REAL, 0 ) );
check( 'name: the filter is registered with four arguments', cs_hooked( 'vance_community_author_name', 'vance_community_sample_author_name' ) );

/* ===================================================================== */
section( '5. Feed' );

arrange( staff_caps(), 1 );
vance_community_sample_load( $NOW );
$REALQ = cs_seed_question( 5, 'approved', 'A real member question' );
$BASE  = array( 'post_type' => 'vance_question', 'post_status' => 'publish', 'posts_per_page' => -1 );

act_as( 1, staff_caps() );
check( 'feed: staff args are unchanged', $BASE === vance_community_sample_feed_args( $BASE ) );
check( 'feed: staff see 20 samples and the real question', 21 === count( get_posts( vance_community_sample_feed_args( $BASE ) ) ) );

act_as( 1, array( 'vance_moderate_community' => true ) );
check( 'feed: an editor (moderation capability only) sees the samples', 21 === count( get_posts( vance_community_sample_feed_args( $BASE ) ) ) );

act_as( 5, array() );
$MEM = get_posts( vance_community_sample_feed_args( $BASE ) );
check( 'feed: a member sees only the real question', 1 === count( $MEM ) && $REALQ === $MEM[0]->ID );

act_as( 0, array() );
$VIS = get_posts( vance_community_sample_feed_args( $BASE ) );
check( 'feed: a visitor sees only the real question', 1 === count( $VIS ) && $REALQ === $VIS[0]->ID );

$WITH = vance_community_sample_feed_args( $BASE + array( 'meta_query' => array( array( 'key' => '_vance_community_state', 'value' => 'approved' ) ) ) );
check( 'feed: an existing meta_query is kept as a nested group under AND with the sample clause', 'AND' === $WITH['meta_query']['relation'] && 'NOT EXISTS' === $WITH['meta_query'][0]['compare'] && '_vance_community_state' === $WITH['meta_query'][1][0]['key'] );
check( 'feed: a member still sees only the real question when the existing query is kept', 1 === count( get_posts( $WITH ) ) );
$OR = vance_community_sample_feed_args( $BASE + array( 'meta_query' => array( 'relation' => 'OR', array( 'key' => '_vance_community_state', 'value' => 'approved' ), array( 'key' => '_vance_community_state', 'value' => 'waiting' ) ) ) );
check( 'feed: an existing OR query cannot let samples through (DEFECT: the clause used to join the OR)', 1 === count( get_posts( $OR ) ) && $REALQ === get_posts( $OR )[0]->ID );
check( 'feed: the space filter still works with the sample clause', 0 === count( get_posts( vance_community_sample_feed_args( $BASE + array( 'tax_query' => array( array( 'taxonomy' => 'vance_space', 'field' => 'slug', 'terms' => 'carers' ) ) ) ) ) ) );
check( 'feed: the hiding is not a removable filter: the view calls it directly (DEFECT: it used to hang on a filter in the optional sample file)', ! cs_hooked( 'vance_community_feed_args', 'vance_community_sample_feed_args' ) && false !== strpos( (string) file_get_contents( $THEME . '/inc/hub-community-view.php' ), 'new WP_Query( vance_community_sample_feed_args( (array) apply_filters( \'vance_community_feed_args\', $args ) ) )' ) );
check( 'feed: the hiding lives in hub-community.php, not in the sample file', false !== strpos( (string) file_get_contents( $THEME . '/inc/hub-community.php' ), 'function vance_community_sample_feed_args(' ) && false === strpos( (string) file_get_contents( $THEME . '/inc/hub-community-sample.php' ), 'function vance_community_sample_feed_args(' ) );

/* ===================================================================== */
section( '6. Remove' );

arrange( staff_caps(), 1 );
vance_community_sample_load( $NOW );
$KEEP_Q = cs_seed_question( 5, 'approved', 'A real member question' );
$KEEP_R = cs_seed_reply( $KEEP_Q, 6, 'A real reply' );
$SQ     = sample_questions()[3];
$STAFF  = cs_seed_reply( $SQ->ID, 1, 'A staff reply on a sample question' );
$GONE   = vance_community_sample_remove();
check( 'remove: returns 20', 20 === $GONE );
check( 'remove: no sample question or reply is left', 0 === count( sample_questions() ) && 0 === count( sample_replies() ) && 0 === vance_community_sample_count() );
check( 'remove: a real question and its real reply stay', isset( $GLOBALS['WP']['posts'][ $KEEP_Q ] ) && isset( $GLOBALS['WP']['comments'][ $KEEP_R ] ) );
check( 'remove: a staff reply on a sample question goes with it (as the box says)', ! isset( $GLOBALS['WP']['comments'][ $STAFF ] ) );
check( 'remove: no sample meta rows are left', array() === array_filter( array_merge( $GLOBALS['WP']['meta']['post'], $GLOBALS['WP']['meta']['comment'] ), function ( $m ) { return '_vance_community_sample' === $m['key']; } ) );
check( 'remove: a second remove deletes nothing', 0 === vance_community_sample_remove() );
check( 'remove: load works again after remove', 20 === vance_community_sample_load( $NOW ) );

/* ===================================================================== */
section( '7. Admin handlers, box and notice' );

arrange( array( 'vance_moderate_community' => true ), 2 );
$_POST = array( '_wpnonce' => 'nonce-vance_community_sample_load' );
$D = die_of( 'vance_community_sample_load_handler' );
check( 'handler: an editor without manage_options is refused with 403 on load', $D && 403 === $D->status && 0 === vance_community_sample_count() );
$_POST = array( '_wpnonce' => 'nonce-vance_community_sample_remove' );
$D = die_of( 'vance_community_sample_remove_handler' );
check( 'handler: an editor without manage_options is refused with 403 on remove', $D && 403 === $D->status );

arrange( staff_caps(), 1 );
$_POST = array( '_wpnonce' => 'nonce-wrong' );
$D = die_of( 'vance_community_sample_load_handler' );
check( 'handler: a bad nonce is refused and loads nothing', $D && 0 === vance_community_sample_count() );
$_POST = array();
check( 'handler: a missing nonce is refused', null !== die_of( 'vance_community_sample_load_handler' ) && 0 === vance_community_sample_count() );

$_POST = array( '_wpnonce' => 'nonce-vance_community_sample_remove' );
check( 'handler: a Remove nonce cannot run Load', null !== die_of( 'vance_community_sample_load_handler' ) && 0 === vance_community_sample_count() );
$_POST = array( '_wpnonce' => 'nonce-vance_community_sample_load' );
$TO = redirect_of( 'vance_community_sample_load_handler' );
check( 'handler: load with the nonce loads 20 and says loaded', 20 === vance_community_sample_count() && false !== strpos( (string) $TO, 'vcom_sample=loaded' ) && false !== strpos( (string) $TO, 'page=vance-community-queue' ) );
$TO = redirect_of( 'vance_community_sample_load_handler' );
check( 'handler: a second load says exists and adds nothing', 20 === vance_community_sample_count() && false !== strpos( (string) $TO, 'vcom_sample=exists' ) );
vance_community_sample_remove();
$GLOBALS['WP']['fail_insert_post'] = true;
$TO = redirect_of( 'vance_community_sample_load_handler' );
$GLOBALS['WP']['fail_insert_post'] = false;
check( 'handler: a load that makes nothing says failed, not exists (DEFECT: it used to say "already loaded")', 0 === vance_community_sample_count() && false !== strpos( (string) $TO, 'vcom_sample=failed' ) );
$_GET = array( 'vcom_sample' => 'failed' );
check( 'notice: failed shows an error notice', false !== strpos( vance_community_sample_notice_html(), 'notice-error' ) );
$_GET = array();
vance_community_sample_load( $NOW );
check( 'handler: a Load nonce cannot run Remove (DEFECT: one nonce served both)', null !== die_of( 'vance_community_sample_remove_handler' ) && 20 === vance_community_sample_count() );
$_POST = array( '_wpnonce' => 'nonce-vance_community_sample_remove' );
$TO = redirect_of( 'vance_community_sample_remove_handler' );
check( 'handler: remove with the nonce removes all and says removed', 0 === vance_community_sample_count() && false !== strpos( (string) $TO, 'vcom_sample=removed' ) );
check( 'handler: both admin-post actions are registered', cs_hooked( 'admin_post_vance_community_sample_load', 'vance_community_sample_load_handler' ) && cs_hooked( 'admin_post_vance_community_sample_remove', 'vance_community_sample_remove_handler' ) );
check( 'handler: no nopriv action exists', ! cs_hooked( 'admin_post_nopriv_vance_community_sample_load', 'vance_community_sample_load_handler' ) );

arrange( staff_caps(), 1 );
$BOX = vance_community_sample_box_html();
check( 'box: with no samples it offers Load, with the nonce and the load action', false !== strpos( $BOX, 'Load sample data' ) && false !== strpos( $BOX, 'value="vance_community_sample_load"' ) && false !== strpos( $BOX, 'value="nonce-vance_community_sample_load"' ) && false === strpos( $BOX, 'Remove sample data' ) );
vance_community_sample_load( $NOW );
$BOX = vance_community_sample_box_html();
check( 'box: with samples it offers Remove, says 20, and warns about staff replies', false !== strpos( $BOX, 'Remove sample data' ) && false !== strpos( $BOX, 'Sample questions now: 20' ) && false !== strpos( $BOX, 'replies that staff wrote' ) && false === strpos( $BOX, 'value="vance_community_sample_load"' ) );
act_as( 2, array( 'vance_moderate_community' => true ) );
check( 'box: an editor without manage_options sees no box', '' === vance_community_sample_box_html() );

$_GET = array( 'vcom_sample' => 'loaded' );
check( 'notice: loaded shows a success notice', false !== strpos( vance_community_sample_notice_html(), 'notice-success' ) );
$_GET = array( 'vcom_sample' => '<script>' );
check( 'notice: an unknown value shows nothing', '' === vance_community_sample_notice_html() );
$_GET = array( 'vcom_sample' => array( 'loaded' ) );
check( 'notice: an array value shows nothing', '' === vance_community_sample_notice_html() );
$_GET = array( 'vcom_sample' => 'removed' );
check( 'notice: removed shows a success notice with its text', false !== strpos( vance_community_sample_notice_html(), 'notice-success' ) && false !== strpos( vance_community_sample_notice_html(), 'Sample data removed.' ) );
$_GET = array( 'vcom_sample' => 'exists' );
check( 'notice: exists shows a warning notice', false !== strpos( vance_community_sample_notice_html(), 'notice-warning' ) );
$_GET = array();
check( 'notice: no parameter shows nothing', '' === vance_community_sample_notice_html() );

/* ===================================================================== */
section( '8. Failure paths: the store refuses a write' );

arrange( staff_caps(), 1 );
$GLOBALS['WP']['fail_insert_post'] = true;
$NONE = vance_community_sample_load( $NOW );
check( 'fail: every question insert refused: load returns 0', 0 === $NONE );
check( 'fail: every question insert refused: no sample question or reply is made', 0 === vance_community_sample_count() && 0 === count( sample_replies() ) );

arrange( staff_caps(), 1 );
$GLOBALS['WP']['fail_insert_comment'] = true;
$NOREP = vance_community_sample_load( $NOW );
check( 'fail: every reply insert refused: the 20 questions still load', 20 === $NOREP && 20 === vance_community_sample_count() );
check( 'fail: every reply insert refused: no reply is made', 0 === count( sample_replies() ) );
check( 'fail: after a partial load a second load is refused (remove first, no silent repair)', 0 === vance_community_sample_load( $NOW ) );

arrange( staff_caps(), 1 );
vance_community_sample_load( $NOW );
$GLOBALS['WP']['fail_delete_post'] = true;
$NOGO = vance_community_sample_remove();
check( 'fail: every delete refused: remove returns 0', 0 === $NOGO );
check( 'fail: every delete refused: all 20 questions and 104 replies stay', 20 === vance_community_sample_count() && 104 === count( sample_replies() ) );

/* ===================================================================== */
section( '9. Stored data matches the sample rows' );

arrange( staff_caps(), 1 );
vance_community_sample_load( $NOW );
$ASKED      = array_map( function ( $q ) { return (string) get_post_meta( $q->ID, '_vance_community_sample_name', true ); }, sample_questions() );
$ASKED_WANT = array_column( $DATA, 1 );
sort( $ASKED );
sort( $ASKED_WANT );
check( 'data stored: every sample question carries its asker name from the data', $ASKED_WANT === $ASKED );
$SAID      = array_map( function ( $r ) { return (string) get_comment_meta( $r->comment_ID, '_vance_community_sample_name', true ); }, sample_replies() );
$SAID_WANT = array_merge( ...array_map( function ( $row ) { return array_column( $row[6], 0 ); }, $DATA ) );
sort( $SAID );
sort( $SAID_WANT );
check( 'data stored: every sample reply carries its name from the data', $SAID_WANT === $SAID );
$METOO_WANT = array_sum( array_column( $DATA, 3 ) );
$METOO_GOT  = array_sum( array_map( function ( $q ) { return vance_community_meta_rows( 'post', $q->ID, '_vance_community_metoo' ); }, sample_questions() ) );
check( 'data stored: the me-too rows add up to the data total', $METOO_WANT === $METOO_GOT && $METOO_GOT > 0 );
$HELP_WANT = array_sum( array_map( function ( $row ) { return array_sum( array_column( $row[6], 1 ) ); }, $DATA ) );
$HELP_GOT  = array_sum( array_map( function ( $r ) { return vance_community_meta_rows( 'comment', $r->comment_ID, '_vance_community_helpful' ); }, sample_replies() ) );
check( 'data stored: the helpful rows add up to the data total', $HELP_WANT === $HELP_GOT && $HELP_GOT > 0 );
$SPACE_GOT  = array_count_values( array_map( function ( $q ) { return cs_space_of( $q->ID ); }, sample_questions() ) );
$SPACE_WANT = $BY;
ksort( $SPACE_GOT );
ksort( $SPACE_WANT );
check( 'data stored: each question sits in the space the data gives it', $SPACE_WANT === $SPACE_GOT );

arrange( staff_caps(), 1 );
vance_community_sample_load();
$NEWEST_AGE = time() - max( array_map( function ( $q ) { return strtotime( $q->post_date_gmt . ' UTC' ); }, sample_questions() ) );
check( 'load without a time argument dates the newest question one to two days back', $NEWEST_AGE >= DAY_IN_SECONDS && $NEWEST_AGE < 2 * DAY_IN_SECONDS );

cs_reset();
$GLOBALS['WP']['caps'] = staff_caps();
$GLOBALS['WP']['user'] = 1;
vance_community_register_types();
$NOSP = vance_community_sample_load( $NOW );
check( 'load with no spaces seeded: the 20 questions still load', 20 === $NOSP );
check( 'load with no spaces seeded: no question is in a space', array() === array_filter( sample_questions(), function ( $q ) { return '' !== cs_space_of( $q->ID ); } ) );

/* ===================================================================== */
section( '10. Remove: every status, and nothing else that carries the marker' );

arrange( staff_caps(), 1 );
vance_community_sample_load( $NOW );
$DRAFT = sample_questions()[0]->ID;
$TRASH = sample_questions()[1]->ID;
wp_update_post( array( 'ID' => $DRAFT, 'post_status' => 'draft' ) );
wp_update_post( array( 'ID' => $TRASH, 'post_status' => 'trash' ) );
$BLOG = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'A blog post' ) );
update_post_meta( $BLOG, '_vance_community_sample', '1' );
$GONE = vance_community_sample_remove();
check( 'remove: counts draft and trashed sample questions too (20)', 20 === $GONE );
check( 'remove: a draft sample question goes', ! isset( $GLOBALS['WP']['posts'][ $DRAFT ] ) );
check( 'remove: a trashed sample question goes', ! isset( $GLOBALS['WP']['posts'][ $TRASH ] ) );
check( 'remove: a blog post carrying the same marker stays', isset( $GLOBALS['WP']['posts'][ $BLOG ] ) );

/* ===================================================================== */
section( '11. Feed: a space filter for staff and for members' );

arrange( staff_caps(), 1 );
vance_community_sample_load( $NOW );
$CARER_ARGS = array( 'post_type' => 'vance_question', 'post_status' => 'publish', 'posts_per_page' => -1, 'tax_query' => array( array( 'taxonomy' => 'vance_space', 'field' => 'slug', 'terms' => 'carers' ) ) );
act_as( 1, staff_caps() );
check( 'feed: staff with a carers filter see the 2 carers samples', 2 === count( get_posts( vance_community_sample_feed_args( $CARER_ARGS ) ) ) );
act_as( 5, array() );
check( 'feed: a member with a carers filter sees none of them', 0 === count( get_posts( vance_community_sample_feed_args( $CARER_ARGS ) ) ) );

/* ===================================================================== */
section( '12. Handlers: remove needs a good nonce; redirect target' );

arrange( staff_caps(), 1 );
vance_community_sample_load( $NOW );
$_POST = array( '_wpnonce' => 'nonce-wrong' );
$D = die_of( 'vance_community_sample_remove_handler' );
check( 'handler: a bad nonce on remove is refused with 403 and removes nothing', $D && 403 === $D->status && 20 === vance_community_sample_count() );
$_POST = array();
$D = die_of( 'vance_community_sample_remove_handler' );
check( 'handler: a missing nonce on remove is refused and removes nothing', $D && 20 === vance_community_sample_count() );
$_POST = array( '_wpnonce' => 'nonce-vance_community_sample_remove' );
$TO = redirect_of( 'vance_community_sample_remove_handler' );
check( 'handler: remove redirects to admin.php on the queue page, approved tab, with removed', 0 === strpos( (string) $TO, 'https://example.test/wp-admin/admin.php?' ) && false !== strpos( (string) $TO, 'tab=approved' ) && false !== strpos( (string) $TO, 'vcom_sample=removed' ) );

arrange( staff_caps(), 1 );
vance_community_sample_load( $NOW );
$BOX_FULL = vance_community_sample_box_html();
check( 'box: with samples the form posts the remove action with the nonce', false !== strpos( $BOX_FULL, 'value="vance_community_sample_remove"' ) && false !== strpos( $BOX_FULL, 'value="nonce-vance_community_sample_remove"' ) );

/* ===================================================================== */
section( '13. Filters run: the name and the feed go through apply_filters' );

arrange( staff_caps(), 1 );
vance_community_sample_load( $NOW );
$REALP = cs_seed_question( 0, 'approved', 'A real question with no author for the pipeline' );
$Q1    = sample_questions()[0];
$GLOBALS['WP']['run_filters'] = true;
check( 'pipeline: vance_community_author_name returns the stored name through the filter', get_post_meta( $Q1->ID, '_vance_community_sample_name', true ) === vance_community_author_name( 'question', $Q1->ID, 0 ) );
check( 'pipeline: a real question with no author is still "Member" through the filter', 'Member' === vance_community_author_name( 'question', $REALP, 0 ) );
$PIPE_ARGS = array( 'post_type' => 'vance_question', 'post_status' => 'publish', 'posts_per_page' => -1 );
act_as( 5, array() );
// As the view does it: the filter first, then the sample clause, which no filter can remove.
$PIPE_M = get_posts( vance_community_sample_feed_args( apply_filters( 'vance_community_feed_args', $PIPE_ARGS ) ) );
check( 'pipeline: the feed hides the samples from a member', 1 === count( $PIPE_M ) && $REALP === $PIPE_M[0]->ID );
act_as( 1, staff_caps() );
check( 'pipeline: the feed keeps the samples for staff', 21 === count( get_posts( vance_community_sample_feed_args( apply_filters( 'vance_community_feed_args', $PIPE_ARGS ) ) ) ) );
unset( $GLOBALS['WP']['run_filters'] );

/* ===================================================================== */
section( '16. Load lock: one load at a time' );

arrange( staff_caps(), 1 );
update_option( 'vance_community_sample_lock', (string) time() );
check( 'lock: a fresh lock (another load running) refuses a load', 0 === vance_community_sample_load( $NOW ) && 0 === vance_community_sample_count() );
check( 'lock: a refused load leaves the other load\'s lock in place', array_key_exists( 'vance_community_sample_lock', $GLOBALS['WP']['options'] ) );
update_option( 'vance_community_sample_lock', (string) ( time() - 11 * MINUTE_IN_SECONDS ) );
check( 'lock: a lock older than ten minutes (a dead load) is taken over', 20 === vance_community_sample_load( $NOW ) );
check( 'lock: the lock is gone after a load', ! array_key_exists( 'vance_community_sample_lock', $GLOBALS['WP']['options'] ) );
check( 'lock: the lock is gone after a refused (exists) load', 0 === vance_community_sample_load( $NOW ) && ! array_key_exists( 'vance_community_sample_lock', $GLOBALS['WP']['options'] ) );
vance_community_sample_remove();
$GLOBALS['WP']['fail_insert_post'] = true;
vance_community_sample_load( $NOW );
$GLOBALS['WP']['fail_insert_post'] = false;
check( 'lock: the lock is gone after a load that made nothing', ! array_key_exists( 'vance_community_sample_lock', $GLOBALS['WP']['options'] ) );

/* ===================================================================== */
section( '17. Member actions cannot reach a hidden sample question' );

function ajax_status( $fn ) {
	try { $fn(); } catch ( CsJsonReply $r ) { return array( $r->success, $r->status ); }
	return null;
}
/** Real POST values are strings; the handlers read only strings. */
function as_member_post( array $fields ) {
	$_POST = array( 'nonce' => 'nonce-vance_community_nonce' ) + array_map( 'strval', $fields );
}
arrange( staff_caps(), 1 );
$GLOBALS['WP']['mods'] = array( 'vance_hub_community' => true );
vance_community_sample_load( $NOW );
$SQ  = sample_questions()[0];
$SR  = vance_community_replies( $SQ->ID )[0];
$RQ  = cs_seed_question( 9, 'approved', 'A real question for the action checks' );
$RR  = cs_seed_reply( $RQ, 9, 'A real reply for the action checks' );
cs_user( 5, 'member@example.test', array( 'first_name' => 'Ann' ) );
act_as( 5, array() );

as_member_post( array( 'question' => $SQ->ID, 'text' => 'A reply to a hidden sample' ) );
check( 'member: reply on a sample question is 404 and stores nothing (DEFECT: it used to save a waiting reply)', array( false, 404 ) === ajax_status( 'vance_community_ajax_reply' ) && 0 === count( cs_match_comments( array( 'post_id' => $SQ->ID, 'user_id' => 5, 'status' => 'all' ) ) ) );
as_member_post( array( 'id' => $SQ->ID ) );
check( 'member: me-too on a sample question is 404 and adds no row', array( false, 404 ) === ajax_status( 'vance_community_ajax_metoo' ) && ! vance_community_has_row( 'post', $SQ->ID, '_vance_community_metoo', 5 ) );
as_member_post( array( 'id' => $SR->comment_ID ) );
check( 'member: helpful on a sample reply is 404 and adds no row', array( false, 404 ) === ajax_status( 'vance_community_ajax_helpful' ) && ! vance_community_has_row( 'comment', $SR->comment_ID, '_vance_community_helpful', 5 ) );
as_member_post( array( 'kind' => 'question', 'id' => $SQ->ID, 'reason' => array_keys( vance_community_report_reasons() )[0] ) );
check( 'member: report on a sample question is 404 and leaves it approved', array( false, 404 ) === ajax_status( 'vance_community_ajax_report' ) && 'approved' === vance_community_state( 'question', $SQ->ID ) );
as_member_post( array( 'kind' => 'reply', 'id' => $SR->comment_ID, 'reason' => array_keys( vance_community_report_reasons() )[0] ) );
check( 'member: report on a sample reply is 404 and leaves it approved', array( false, 404 ) === ajax_status( 'vance_community_ajax_report' ) && 'approved' === vance_community_state( 'reply', $SR->comment_ID ) );

// Negative controls: the same actions on real content still work for the member.
as_member_post( array( 'id' => $RQ ) );
check( 'member: me-too on a real question still works', array( true, 200 ) === ajax_status( 'vance_community_ajax_metoo' ) );
as_member_post( array( 'id' => $RR ) );
check( 'member: helpful on a real reply still works', array( true, 200 ) === ajax_status( 'vance_community_ajax_helpful' ) );
as_member_post( array( 'question' => $RQ, 'text' => 'A real reply from Ann' ) );
check( 'member: reply on a real question still works', array( true, 200 ) === ajax_status( 'vance_community_ajax_reply' ) );

// Staff may use the actions on samples (to demo them).
act_as( 1, staff_caps() );
as_member_post( array( 'id' => $SQ->ID ) );
check( 'staff: me-too on a sample question works', array( true, 200 ) === ajax_status( 'vance_community_ajax_metoo' ) );
check( 'hidden: a real question is never hidden', false === vance_community_sample_hidden( $RQ ) );
act_as( 5, array() );
check( 'hidden: a sample question is hidden from a member', true === vance_community_sample_hidden( $SQ->ID ) );
act_as( 0, array() );
check( 'hidden: a sample question is hidden from a visitor', true === vance_community_sample_hidden( $SQ->ID ) );

/* ===================================================================== */
section( '14. Names: the marker is required, a real reply has no name, the stored name is trimmed' );

arrange( staff_caps(), 1 );
vance_community_sample_load( $NOW );
$IMP = cs_seed_question( 0, 'approved', 'A real question that has a stored name but no marker' );
update_post_meta( $IMP, '_vance_community_sample_name', 'Impostor' );
check( 'name: a stored name on a real question without the marker is ignored', 'Member' === vance_community_sample_author_name( 'Member', 'question', $IMP, 0 ) );
$IMPR = cs_seed_reply( $IMP, 0, 'A real reply that has a stored name but no marker' );
update_comment_meta( $IMPR, '_vance_community_sample_name', 'Impostor' );
check( 'name: a stored name on a real reply without the marker is ignored', 'Member' === vance_community_sample_author_name( 'Member', 'reply', $IMPR, 0 ) );
$PLAIN_R = cs_seed_reply( $IMP, 0, 'A plain real reply with no author and no name' );
check( 'name: an author-less real reply with no stored name keeps "Member"', 'Member' === vance_community_sample_author_name( 'Member', 'reply', $PLAIN_R, 0 ) );
$Q3 = sample_questions()[2];
update_post_meta( $Q3->ID, '_vance_community_sample_name', '  Padded  ' );
check( 'name: a stored name is trimmed before it is shown', 'Padded' === vance_community_sample_author_name( 'Member', 'question', $Q3->ID, 0 ) );

/* ===================================================================== */
section( '15. Boundaries of the load refusal: one sample question refuses; a real question does not' );

arrange( staff_caps(), 1 );
$ONLY = cs_seed_question( 0, 'approved', 'The only sample question' );
update_post_meta( $ONLY, '_vance_community_sample', '1' );
check( 'boundary: exactly one sample question refuses a load and adds nothing', 0 === vance_community_sample_load( $NOW ) && 1 === vance_community_sample_count() && 0 === count( sample_replies() ) );

arrange( staff_caps(), 1 );
cs_seed_question( 0, 'approved', 'A real question, no marker' );
check( 'boundary: a real question without the marker does not block the load', 20 === vance_community_sample_load( $NOW ) && 20 === vance_community_sample_count() );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
