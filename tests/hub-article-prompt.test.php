<?php
/**
 * Behaviour tests for the "Try this now" prompt settings in inc/hub-article.php:
 * the prompt switch, the paragraph it sits after, the tool choice for each
 * top-level category, the copy of the Nutrition check and Discounts tiles, and the
 * Customizer registrations that the switches and the selects come from.
 *
 * Same shape as hub-article.test.php: the stub block of hub-layout.test.php is
 * lifted and evaluated (one set of WordPress fakes), then the article stubs that
 * suite adds. If that block cannot be found, this suite exits 2.
 *
 * WHAT IS COVERED
 *   1  the switch off: the content is unchanged for every paragraph count, and
 *      the rest of the article layout is still active
 *   2  the position: stored values 1, 2, 10, 0, 11, -3, 'abc', '', 2.7 clamp or
 *      fall back as the sanitiser says; for A in 1, 2, 3 and 10 the prompt sits
 *      after paragraph A when the article has at least A+2 top-level paragraphs,
 *      and at the end otherwise, for 0 to 13 paragraphs; the default is 2
 *   3  the tool choice: vance_hub_article_choose_tool() on its own (no choices,
 *      each of the six choices, two categories, first by name wins, an unknown
 *      value, none); the same through the_content for one category, for two, for
 *      a child category that inherits its top-level category, and for "none"
 *   4  the Nutrition check and Discounts tiles print their own title, text and
 *      button; a tool with no address is not offered
 *   5  the select sanitiser
 *   6  the Customizer registrations: the switches, the position, one select per
 *      top-level category with ids from sanitize_key, an empty slug skipped,
 *      priorities 30 and 32, the description on the first select only, and no
 *      category at all
 *
 * A check that fails on the current code is a finding, not a broken check. The
 * runner lists every failure. Bugs found are reported, not fixed.
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

/* ---- stubs this suite adds (the same set as hub-article.test.php) ---------- */

function a_reset() {
	reset_state();
	$GLOBALS['FILTER_RETURNS']           = array();
	$GLOBALS['vance_hub_article_prompted'] = array();
	$GLOBALS['CATS_REG']                 = array();
	$GLOBALS['A'] = array(
		'singular_post' => true,
		'in_loop'       => true,
		'main_query'    => true,
		'feed'          => false,
		'admin'         => false,
		'ajax'          => false,
		'doing'         => array(),
		'the_id'        => 4905,
		'queried_id'    => 4905,
		'password'      => false,
		'ancestors'     => array(),
		'terms'         => array(),
	);
}
function is_singular( $type = '' ) { return (bool) $GLOBALS['A']['singular_post']; }
function in_the_loop() { return (bool) $GLOBALS['A']['in_loop']; }
function is_main_query() { return (bool) $GLOBALS['A']['main_query']; }
function is_feed() { return (bool) $GLOBALS['A']['feed']; }
function is_admin() { return (bool) $GLOBALS['A']['admin']; }
function wp_doing_ajax() { return (bool) $GLOBALS['A']['ajax']; }
function doing_filter( $tag = null ) { return in_array( $tag, $GLOBALS['A']['doing'], true ); }
function get_the_ID() { return $GLOBALS['A']['the_id']; }
function get_queried_object_id() { return $GLOBALS['A']['queried_id']; }
function post_password_required( $post = null ) { return (bool) $GLOBALS['A']['password']; }
function get_ancestors( $id, $taxonomy = '', $type = '' ) {
	return isset( $GLOBALS['A']['ancestors'][ $id ] ) ? $GLOBALS['A']['ancestors'][ $id ] : array();
}
function get_term( $id, $taxonomy = '' ) {
	return isset( $GLOBALS['A']['terms'][ $id ] ) ? $GLOBALS['A']['terms'][ $id ] : null;
}
function esc_attr__( $text, $domain = '' ) { return esc_attr( $text ); }
function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
/** The category list that the Customizer registration reads. */
function get_categories( $args = array() ) {
	$GLOBALS['CATS_ARGS'][] = $args;
	if ( isset( $args['child_of'] ) ) {
		// The non-empty descendants (ids) of one category, from CATS_KIDS[ id ].
		return isset( $GLOBALS['CATS_KIDS'][ $args['child_of'] ] ) ? $GLOBALS['CATS_KIDS'][ $args['child_of'] ] : array();
	}
	// A top-level query: empty categories come back only when hide_empty is false.
	$out = array();
	foreach ( $GLOBALS['CATS_REG'] as $term ) {
		if ( ! empty( $args['hide_empty'] ) && empty( $term->count ) ) {
			continue;
		}
		$out[] = $term;
	}
	return $out;
}

/** A category term as get_the_category() returns it. */
function a_cat( $id, $slug, $name = '', $parent = 0 ) {
	return (object) array( 'term_id' => $id, 'slug' => $slug, 'name' => '' === $name ? ucfirst( $slug ) : $name, 'parent' => $parent );
}

require_once $THEME . '/inc/hub-article.php';

/* ---- runner and helpers ----------------------------------------------------- */

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }
function has( $hay, $needle ) { return false !== strpos( (string) $hay, $needle ); }

/** Content with $n top-level paragraphs, each numbered. */
function paras( $n ) {
	$out = '';
	for ( $i = 1; $i <= $n; $i++ ) {
		$out .= "<p>Para $i.</p>\n";
	}
	return $out;
}

/** The article layout is on: the master switch is on and the article switch too. */
function art_mods( array $extra = array() ) {
	return array_merge( array( 'vance_home_layout' => 'hub', 'vance_hub_articles' => true ), $extra );
}

/** Run the_content for the post in $cats with these mods; the filter's output. */
function prompt_run( array $mods, $content, array $cats = array(), $post = 4905, array $ancestors = array(), array $terms = array() ) {
	a_reset();
	$GLOBALS['MODS']                 = $mods;
	$GLOBALS['CATS_BY_POST'][ $post ] = $cats;
	$GLOBALS['A']['ancestors']       = $ancestors;
	$GLOBALS['A']['terms']           = $terms;
	return vance_hub_article_content_filter( $content );
}

/** The number of the paragraph that the prompt follows, or '' when it has no anchor. */
function anchor_of( $out ) {
	return preg_match( '/<p>Para (\d+)\.<\/p>\n<aside/', (string) $out, $m ) ? $m[1] : '';
}

/** The "Try this now" title of the prompt in $out, '' when there is none. */
function prompt_title( $out ) {
	return preg_match( '/vhub-aprompt__title">(.*?)<\/div>/', (string) $out, $m ) ? $m[1] : '';
}

/* ===================================================================== */

section( '1. The switch off: the content is unchanged, the layout stays on' );

$OFF = art_mods( array( 'vance_hub_article_prompt' => false ) );
check( 'off: 0 paragraphs, content unchanged', paras( 0 ) === prompt_run( $OFF, paras( 0 ) ) );
check( 'off: 1 paragraph, content unchanged', paras( 1 ) === prompt_run( $OFF, paras( 1 ) ) );
check( 'off: 2 paragraphs, content unchanged', paras( 2 ) === prompt_run( $OFF, paras( 2 ) ) );
check( 'off: 3 paragraphs, content unchanged', paras( 3 ) === prompt_run( $OFF, paras( 3 ) ) );
check( 'off: 4 paragraphs, content unchanged', paras( 4 ) === prompt_run( $OFF, paras( 4 ) ) );
check( 'off: 5 paragraphs, content unchanged', paras( 5 ) === prompt_run( $OFF, paras( 5 ) ) );
check( 'off: 10 paragraphs, content unchanged', paras( 10 ) === prompt_run( $OFF, paras( 10 ) ) );
check( 'off: 13 paragraphs, content unchanged', paras( 13 ) === prompt_run( $OFF, paras( 13 ) ) );
check( 'off: a string with no paragraph tags is unchanged too', 'plain text' === prompt_run( $OFF, 'plain text' ) );
check( 'off: the article layout is still active', true === (function () use ( $OFF ) { a_reset(); $GLOBALS['MODS'] = $OFF; return vance_hub_article_active(); })() );
check( 'off: the sidebar cards still print', '' !== (function () use ( $OFF ) { a_reset(); $GLOBALS['MODS'] = $OFF; ob_start(); vance_hub_article_sidebar_top(); return ob_get_clean(); })() );
check( 'off: the prompt switch defaults on (a missing value gives a prompt)', has( prompt_run( art_mods(), paras( 4 ) ), 'data-vhub-aprompt' ) );
check( 'off: the prompt switch stored as the string "0" is off', paras( 4 ) === prompt_run( art_mods( array( 'vance_hub_article_prompt' => '0' ) ), paras( 4 ) ) );
check( 'off: the prompt switch stored as the empty string is off', paras( 4 ) === prompt_run( art_mods( array( 'vance_hub_article_prompt' => '' ) ), paras( 4 ) ) );

/* ===================================================================== */

section( '2. The position' );

check( 'position: stored 1 is read as 1', 1 === vance_hub_article_sanitize_after( 1 ) );
check( 'position: stored 2 is read as 2', 2 === vance_hub_article_sanitize_after( 2 ) );
check( 'position: stored 10 is read as 10', 10 === vance_hub_article_sanitize_after( 10 ) );
check( 'position: stored 0 is raised to 1', 1 === vance_hub_article_sanitize_after( 0 ) );
check( 'position: stored 11 is lowered to 10', 10 === vance_hub_article_sanitize_after( 11 ) );
check( 'position: stored -3 is raised to 1', 1 === vance_hub_article_sanitize_after( -3 ) );
check( 'position: stored "abc" falls back to 2', 2 === vance_hub_article_sanitize_after( 'abc' ) );
check( 'position: stored "" falls back to 2', 2 === vance_hub_article_sanitize_after( '' ) );
check( 'position: stored 2.7 is truncated to 2 (a float is numeric)', 2 === vance_hub_article_sanitize_after( 2.7 ) );
check( 'position: stored "5" (a numeric string) is read as 5', 5 === vance_hub_article_sanitize_after( '5' ) );
check( 'position: a missing mod is 2 (the default)', 2 === vance_hub_article_prompt_after() );

// The stored value reaches the content through the mod, sanitised.
check( 'position: stored 0 puts the prompt after paragraph 1', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 0 ) ), paras( 13 ) ) ) );
check( 'position: stored 11 puts the prompt after paragraph 10', '10' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 11 ) ), paras( 13 ) ) ) );
check( 'position: stored -3 puts the prompt after paragraph 1', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => -3 ) ), paras( 13 ) ) ) );
check( 'position: stored "abc" puts the prompt after paragraph 2', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 'abc' ) ), paras( 13 ) ) ) );
check( 'position: stored "" puts the prompt after paragraph 2', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => '' ) ), paras( 13 ) ) ) );
check( 'position: stored 2.7 puts the prompt after paragraph 2', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2.7 ) ), paras( 13 ) ) ) );
check( 'position: stored 10 puts the prompt after paragraph 10 in a 13-paragraph article', '10' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 10 ) ), paras( 13 ) ) ) );
check( 'position: stored 1 puts the prompt after paragraph 1 in a 13-paragraph article', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 13 ) ) ) );

// A = 1. The prompt is after paragraph 1 from 3 paragraphs; 1 and 2 paragraphs get it at the end.
check( 'A=1, 0 paragraphs: nothing to attach to, no prompt', '' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 0 ) ) ) );
check( 'A=1, 1 paragraph: at the end (after paragraph 1)', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 1 ) ) ) );
check( 'A=1, 2 paragraphs: at the end (after paragraph 2)', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 2 ) ) ) );
check( 'A=1, 3 paragraphs: after paragraph 1', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 3 ) ) ) );
check( 'A=1, 4 paragraphs: after paragraph 1', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 4 ) ) ) );
check( 'A=1, 5 paragraphs: after paragraph 1', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 5 ) ) ) );
check( 'A=1, 6 paragraphs: after paragraph 1', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 6 ) ) ) );
check( 'A=1, 7 paragraphs: after paragraph 1', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 7 ) ) ) );
check( 'A=1, 8 paragraphs: after paragraph 1', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 8 ) ) ) );
check( 'A=1, 9 paragraphs: after paragraph 1', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 9 ) ) ) );
check( 'A=1, 10 paragraphs: after paragraph 1', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 10 ) ) ) );
check( 'A=1, 11 paragraphs: after paragraph 1', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 11 ) ) ) );
check( 'A=1, 12 paragraphs: after paragraph 1', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 12 ) ) ) );
check( 'A=1, 13 paragraphs: after paragraph 1', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 1 ) ), paras( 13 ) ) ) );

// A = 2 (the default). Three paragraphs is one short of A+2 = 4.
check( 'A=2, 0 paragraphs: no prompt', '' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 0 ) ) ) );
check( 'A=2, 1 paragraph: at the end', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 1 ) ) ) );
check( 'A=2, 2 paragraphs: at the end', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 2 ) ) ) );
check( 'A=2, 3 paragraphs: at the end (one short of A+2)', '3' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 3 ) ) ) );
check( 'A=2, 4 paragraphs: after paragraph 2', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 4 ) ) ) );
check( 'A=2, 5 paragraphs: after paragraph 2', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 5 ) ) ) );
check( 'A=2, 6 paragraphs: after paragraph 2', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 6 ) ) ) );
check( 'A=2, 7 paragraphs: after paragraph 2', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 7 ) ) ) );
check( 'A=2, 8 paragraphs: after paragraph 2', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 8 ) ) ) );
check( 'A=2, 9 paragraphs: after paragraph 2', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 9 ) ) ) );
check( 'A=2, 10 paragraphs: after paragraph 2', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 10 ) ) ) );
check( 'A=2, 11 paragraphs: after paragraph 2', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 11 ) ) ) );
check( 'A=2, 12 paragraphs: after paragraph 2', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 12 ) ) ) );
check( 'A=2, 13 paragraphs: after paragraph 2', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 13 ) ) ) );

// A = 3. Four paragraphs is one short of A+2 = 5.
check( 'A=3, 0 paragraphs: no prompt', '' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 0 ) ) ) );
check( 'A=3, 1 paragraph: at the end', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 1 ) ) ) );
check( 'A=3, 2 paragraphs: at the end', '2' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 2 ) ) ) );
check( 'A=3, 3 paragraphs: at the end', '3' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 3 ) ) ) );
check( 'A=3, 4 paragraphs: at the end (one short of A+2)', '4' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 4 ) ) ) );
check( 'A=3, 5 paragraphs: after paragraph 3', '3' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 5 ) ) ) );
check( 'A=3, 6 paragraphs: after paragraph 3', '3' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 6 ) ) ) );
check( 'A=3, 7 paragraphs: after paragraph 3', '3' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 7 ) ) ) );
check( 'A=3, 8 paragraphs: after paragraph 3', '3' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 8 ) ) ) );
check( 'A=3, 9 paragraphs: after paragraph 3', '3' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 9 ) ) ) );
check( 'A=3, 10 paragraphs: after paragraph 3', '3' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 10 ) ) ) );
check( 'A=3, 11 paragraphs: after paragraph 3', '3' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 11 ) ) ) );
check( 'A=3, 12 paragraphs: after paragraph 3', '3' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 12 ) ) ) );
check( 'A=3, 13 paragraphs: after paragraph 3', '3' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 3 ) ), paras( 13 ) ) ) );

// A = 10. Eleven paragraphs is one short of A+2 = 12.
check( 'A=10, 0 paragraphs: no prompt', '' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 10 ) ), paras( 0 ) ) ) );
check( 'A=10, 1 paragraph: at the end', '1' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 10 ) ), paras( 1 ) ) ) );
check( 'A=10, 5 paragraphs: at the end', '5' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 10 ) ), paras( 5 ) ) ) );
check( 'A=10, 9 paragraphs: at the end', '9' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 10 ) ), paras( 9 ) ) ) );
check( 'A=10, 10 paragraphs: at the end (not after 10, which is one short)', '10' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 10 ) ), paras( 10 ) ) ) );
check( 'A=10, 11 paragraphs: at the end (one short of A+2)', '11' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 10 ) ), paras( 11 ) ) ) );
check( 'A=10, 12 paragraphs: after paragraph 10', '10' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 10 ) ), paras( 12 ) ) ) );
check( 'A=10, 13 paragraphs: after paragraph 10', '10' === anchor_of( prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 10 ) ), paras( 13 ) ) ) );

// The default is the same as stored 2.
check( 'default: nothing stored gives the same output as stored 2 (6 paragraphs)', prompt_run( art_mods(), paras( 6 ) ) === prompt_run( art_mods( array( 'vance_hub_article_prompt_after' => 2 ) ), paras( 6 ) ) );
check( 'default: nothing stored puts the prompt after paragraph 2 in a 6-paragraph article', '2' === anchor_of( prompt_run( art_mods(), paras( 6 ) ) ) );

/* ===================================================================== */

section( '3. The tool choice: vance_hub_article_choose_tool() on its own' );

check( 'choose: no categories gives the automatic tool', 'survey' === vance_hub_article_choose_tool( array(), array(), 'survey' ) );
check( 'choose: one category set to auto gives the automatic tool', 'recipes' === vance_hub_article_choose_tool( array( 'food' ), array( 'food' => 'auto' ), 'recipes' ) );
check( 'choose: one category set to survey', 'survey' === vance_hub_article_choose_tool( array( 'food' ), array( 'food' => 'survey' ), 'recipes' ) );
check( 'choose: one category set to recipes', 'recipes' === vance_hub_article_choose_tool( array( 'food' ), array( 'food' => 'recipes' ), 'survey' ) );
check( 'choose: one category set to nutrition', 'nutrition' === vance_hub_article_choose_tool( array( 'food' ), array( 'food' => 'nutrition' ), 'survey' ) );
check( 'choose: one category set to discounts', 'discounts' === vance_hub_article_choose_tool( array( 'food' ), array( 'food' => 'discounts' ), 'survey' ) );
check( 'choose: one category set to none', 'none' === vance_hub_article_choose_tool( array( 'food' ), array( 'food' => 'none' ), 'survey' ) );
check( 'choose: first by name is auto, second has a choice: the second wins', 'discounts' === vance_hub_article_choose_tool( array( 'alpha', 'beta' ), array( 'alpha' => 'auto', 'beta' => 'discounts' ), 'survey' ) );
check( 'choose: both have a choice: the first by name wins', 'recipes' === vance_hub_article_choose_tool( array( 'alpha', 'beta' ), array( 'alpha' => 'recipes', 'beta' => 'nutrition' ), 'survey' ) );
check( 'choose: a first category set to none still beats a second one', 'none' === vance_hub_article_choose_tool( array( 'alpha', 'beta' ), array( 'alpha' => 'none', 'beta' => 'nutrition' ), 'survey' ) );
check( 'choose: an unknown stored value counts as automatic', 'survey' === vance_hub_article_choose_tool( array( 'food' ), array( 'food' => 'bogus' ), 'survey' ) );
check( 'choose: an unknown value in the first category lets the second one choose', 'nutrition' === vance_hub_article_choose_tool( array( 'alpha', 'beta' ), array( 'alpha' => 'bogus', 'beta' => 'nutrition' ), 'survey' ) );
check( 'choose: a category missing from the choices counts as automatic', 'survey' === vance_hub_article_choose_tool( array( 'food' ), array(), 'survey' ) );

/* ===================================================================== */

section( '4. The tool choice through the_content' );

// The child (id 11, slug bowel) is under the top-level category gut-care (id 10).
$CHILD_ANC   = array( 11 => array( 10 ) );
$CHILD_TERMS = array( 10 => a_cat( 10, 'gut-care', 'Gut care' ) );
$CHILD_CATS  = array( a_cat( 11, 'bowel', 'Bowel', 10 ) );
check( 'content: a child category inherits the parent choice (discounts)', has( prompt_run( art_mods( array( 'vance_hub_aprompt_tool_gut-care' => 'discounts' ) ), paras( 6 ), $CHILD_CATS, 4905, $CHILD_ANC, $CHILD_TERMS ), 'Discounts &amp; benefits' ) );
check( 'content: a child category with the parent set to automatic gets the automatic tool', 'Not sure where to start?' === prompt_title( prompt_run( art_mods( array( 'vance_hub_aprompt_tool_gut-care' => 'auto' ) ), paras( 6 ), $CHILD_CATS, 4905, $CHILD_ANC, $CHILD_TERMS ) ) );
check( 'content: a child category ignores its own choice (the parent decides)', has( prompt_run( art_mods( array( 'vance_hub_aprompt_tool_gut-care' => 'discounts', 'vance_hub_aprompt_tool_bowel' => 'nutrition' ) ), paras( 6 ), $CHILD_CATS, 4905, $CHILD_ANC, $CHILD_TERMS ), 'Discounts &amp; benefits' ) );
check( 'content: with no parent known, the child is its own top category and its own choice applies', has( prompt_run( art_mods( array( 'vance_hub_aprompt_tool_bowel' => 'nutrition' ) ), paras( 6 ), $CHILD_CATS ), 'Are you eating right?' ) );

check( 'content: a category set to survey prints the Self-Assessment copy', 'Not sure where to start?' === prompt_title( prompt_run( art_mods( array( 'vance_hub_aprompt_tool_food' => 'survey' ) ), paras( 6 ), array( a_cat( 1, 'food' ) ) ) ) );
check( 'content: a category set to recipes prints the meal planner copy', 'Gut-friendly recipes' === prompt_title( prompt_run( art_mods( array( 'vance_hub_aprompt_tool_food' => 'recipes' ) ), paras( 6 ), array( a_cat( 1, 'food' ) ) ) ) );
check( 'content: a category set to nutrition prints the nutrition tile', 'Are you eating right?' === prompt_title( prompt_run( art_mods( array( 'vance_hub_aprompt_tool_food' => 'nutrition' ) ), paras( 6 ), array( a_cat( 1, 'food' ) ) ) ) );
check( 'content: a category set to discounts prints the discounts tile', 'Discounts &amp; benefits' === prompt_title( prompt_run( art_mods( array( 'vance_hub_aprompt_tool_food' => 'discounts' ) ), paras( 6 ), array( a_cat( 1, 'food' ) ) ) ) );
check( 'content: a category set to none prints no prompt', ! has( prompt_run( art_mods( array( 'vance_hub_aprompt_tool_food' => 'none' ) ), paras( 6 ), array( a_cat( 1, 'food' ) ) ), 'data-vhub-aprompt' ) );
check( 'content: a category set to none leaves the content unchanged', paras( 6 ) === prompt_run( art_mods( array( 'vance_hub_aprompt_tool_food' => 'none' ) ), paras( 6 ), array( a_cat( 1, 'food' ) ) ) );
check( 'content: automatic keeps the built-in rule (food-nutrition gives recipes)', 'Gut-friendly recipes' === prompt_title( prompt_run( art_mods( array( 'vance_hub_aprompt_tool_food-nutrition' => 'auto' ) ), paras( 6 ), array( a_cat( 2, 'food-nutrition' ) ) ) ) );
check( 'content: a category with no choice and no map entry gets the Self-Assessment', 'Not sure where to start?' === prompt_title( prompt_run( art_mods(), paras( 6 ), array( a_cat( 3, 'lifestyle' ) ) ) ) );

// Two categories, the first by name is automatic, the second has a choice.
check( 'content: two categories, first by name automatic, second nutrition: nutrition', 'Are you eating right?' === prompt_title( prompt_run( art_mods( array( 'vance_hub_aprompt_tool_alpha' => 'auto', 'vance_hub_aprompt_tool_beta' => 'nutrition' ) ), paras( 6 ), array( a_cat( 1, 'beta' ), a_cat( 2, 'alpha' ) ) ) ) );
check( 'content: two categories both with a choice: the first by name wins (recipes)', 'Gut-friendly recipes' === prompt_title( prompt_run( art_mods( array( 'vance_hub_aprompt_tool_alpha' => 'recipes', 'vance_hub_aprompt_tool_beta' => 'nutrition' ) ), paras( 6 ), array( a_cat( 1, 'beta' ), a_cat( 2, 'alpha' ) ) ) ) );

check( 'content: an unknown stored choice counts as automatic (Self-Assessment here)', 'Not sure where to start?' === prompt_title( prompt_run( art_mods( array( 'vance_hub_aprompt_tool_food' => 'bogus' ) ), paras( 6 ), array( a_cat( 1, 'food' ) ) ) ) );

/* ===================================================================== */

section( '5. The Nutrition check and Discounts tiles print their own copy' );

$NUT = prompt_run( art_mods( array( 'vance_hub_aprompt_tool_food' => 'nutrition' ) ), paras( 6 ), array( a_cat( 1, 'food' ) ) );
check( 'nutrition: the tile title, text and button are printed', has( $NUT, 'Are you eating right?' ) && has( $NUT, 'A short check that shows your malnutrition risk' ) && has( $NUT, 'Start the check' ) );
check( 'nutrition: the button goes to the malnutrition calculator', has( $NUT, 'href="' ) && has( $NUT, 'malnutrition-calculator' ) );
check( 'nutrition: the image is a background, not an img', has( $NUT, 'vhub-aprompt__img' ) && ! has( $NUT, '<img' ) );
$DIS = prompt_run( art_mods( array( 'vance_hub_aprompt_tool_food' => 'discounts' ) ), paras( 6 ), array( a_cat( 1, 'food' ) ) );
check( 'discounts: the tile title (escaped), text and button are printed', has( $DIS, 'Discounts &amp; benefits' ) && has( $DIS, 'See the UK schemes for toilet access' ) && has( $DIS, 'See the schemes' ) );
check( 'discounts: the button goes to the discounts page', has( $DIS, 'ibd-discounts' ) );

// A tool with no address is never offered: the fallback is the Self-Assessment.
check( 'no address: find_tool skips a tool with an empty href', array() === vance_hub_article_find_tool( array( array( 'mod' => 'nutrition', 'href' => '', 'cta' => 'Go', 'title' => 'T', 'desc' => 'D' ) ), 'nutrition' ) );
check( 'no address: a nutrition tile with an empty href prints nothing', '' === vance_hub_article_prompt_html( array( 'mod' => 'nutrition', 'href' => '', 'cta' => 'Go' ), array( 'title' => 'T', 'text' => 'D' ) ) );
check( 'no address: a discounts tile with no title or description has no copy', array() === vance_hub_article_prompt_copy_for( 'discounts', array( 'mod' => 'discounts', 'href' => '/x/', 'cta' => 'Go', 'title' => '', 'desc' => 'D' ) ) );
check( 'no address: a tile with copy but no button prints nothing', '' === vance_hub_article_prompt_html( array( 'mod' => 'discounts', 'href' => '/x/', 'cta' => '' ), array( 'title' => 'T', 'text' => 'D' ) ) );

/* ===================================================================== */

section( '6. The select sanitiser' );

check( 'select: auto is kept', 'auto' === vance_hub_article_sanitize_tool_choice( 'auto' ) );
check( 'select: survey is kept', 'survey' === vance_hub_article_sanitize_tool_choice( 'survey' ) );
check( 'select: recipes is kept', 'recipes' === vance_hub_article_sanitize_tool_choice( 'recipes' ) );
check( 'select: nutrition is kept', 'nutrition' === vance_hub_article_sanitize_tool_choice( 'nutrition' ) );
check( 'select: discounts is kept', 'discounts' === vance_hub_article_sanitize_tool_choice( 'discounts' ) );
check( 'select: none is kept', 'none' === vance_hub_article_sanitize_tool_choice( 'none' ) );
check( 'select: an unknown string becomes auto', 'auto' === vance_hub_article_sanitize_tool_choice( 'bogus' ) );
check( 'select: the empty string becomes auto', 'auto' === vance_hub_article_sanitize_tool_choice( '' ) );
check( 'select: null becomes auto', 'auto' === vance_hub_article_sanitize_tool_choice( null ) );
check( 'select: an array becomes auto', 'auto' === vance_hub_article_sanitize_tool_choice( array( 'recipes' ) ) );
check( 'select: a different case becomes auto (the values are lower case)', 'auto' === vance_hub_article_sanitize_tool_choice( 'RECIPES' ) );
check( 'select: the choices list has the six values in order', array( 'auto', 'survey', 'recipes', 'nutrition', 'discounts', 'none' ) === array_keys( vance_hub_article_prompt_choices() ) );

/* ===================================================================== */

section( '7. The Customizer registrations' );

class ArticlePromptFake {
	public $settings = array();
	public $controls = array();
	public function add_setting( $id, $args = array() ) { $this->settings[ $id ] = $args; }
	public function add_control( $id, $args = array() ) { $this->controls[ $id ] = $args; }
}

$GLOBALS['CATS_REG'] = array(
	(object) array( 'term_id' => 2, 'slug' => 'beta', 'name' => 'Beta', 'count' => 4 ),
	(object) array( 'term_id' => 1, 'slug' => 'alpha', 'name' => 'Alpha', 'count' => 3 ),
	(object) array( 'term_id' => 3, 'slug' => '', 'name' => 'No slug', 'count' => 2 ),
	(object) array( 'term_id' => 4, 'slug' => '!!!', 'name' => 'Only symbols', 'count' => 2 ),
);
$REG = new ArticlePromptFake();
vance_hub_article_customize_register( $REG );

check( 'register: the article switch, the prompt switch, the position and two selects are added (5 settings)', 5 === count( $REG->settings ) );
check( 'register: the prompt switch defaults on', true === $REG->settings['vance_hub_article_prompt']['default'] );
check( 'register: the prompt switch priority is 12', 12 === $REG->controls['vance_hub_article_prompt']['priority'] );
check( 'register: the position defaults to 2', 2 === $REG->settings['vance_hub_article_prompt_after']['default'] );
check( 'register: the position is sanitised by the position sanitiser', 'vance_hub_article_sanitize_after' === $REG->settings['vance_hub_article_prompt_after']['sanitize_callback'] );
check( 'register: the position control is a number from 1 to 10', 'number' === $REG->controls['vance_hub_article_prompt_after']['type'] && 1 === $REG->controls['vance_hub_article_prompt_after']['input_attrs']['min'] && 10 === $REG->controls['vance_hub_article_prompt_after']['input_attrs']['max'] );
check( 'register: the position control sits at priority 14', 14 === $REG->controls['vance_hub_article_prompt_after']['priority'] );
check( 'register: the position control is in Articles and Self-Assessment', 'vance_hub_sec_pages' === $REG->controls['vance_hub_article_prompt_after']['section'] );

check( 'register: the category with the name Alpha gets the id from its slug', isset( $REG->settings['vance_hub_aprompt_tool_alpha'] ) );
check( 'register: the category with the name Beta gets its own id', isset( $REG->settings['vance_hub_aprompt_tool_beta'] ) );
check( 'register: an empty slug adds no setting', ! isset( $REG->settings['vance_hub_aprompt_tool_'] ) );
check( 'register: a slug that is only symbols adds no setting', ! isset( $REG->settings['vance_hub_aprompt_tool_'] ) );
check( 'register: the tool setting defaults to automatic', 'auto' === $REG->settings['vance_hub_aprompt_tool_alpha']['default'] );
check( 'register: the tool setting is sanitised by the choice sanitiser', 'vance_hub_article_sanitize_tool_choice' === $REG->settings['vance_hub_aprompt_tool_alpha']['sanitize_callback'] );
check( 'register: Alpha is the first select at priority 30 (name order)', 30 === $REG->controls['vance_hub_aprompt_tool_alpha']['priority'] );
check( 'register: Beta is the second select at priority 32', 32 === $REG->controls['vance_hub_aprompt_tool_beta']['priority'] );
check( 'register: only the first select carries the description', '' !== $REG->controls['vance_hub_aprompt_tool_alpha']['description'] && '' === $REG->controls['vance_hub_aprompt_tool_beta']['description'] );
check( 'register: the select offers the six choices', array( 'auto', 'survey', 'recipes', 'nutrition', 'discounts', 'none' ) === array_keys( $REG->controls['vance_hub_aprompt_tool_alpha']['choices'] ) );
check( 'register: the select label names the category', 'Prompt tool: Alpha' === $REG->controls['vance_hub_aprompt_tool_alpha']['label'] );

// Counts: a top-level category whose articles sit only in sub-categories has its own count at 0.
$GLOBALS['CATS_ARGS'] = array();
$GLOBALS['CATS_KIDS'] = array( 12 => array( 121 ), 13 => array(), 15 => array( 151, 152 ) );
$GLOBALS['CATS_REG']  = array(
	(object) array( 'term_id' => 11, 'slug' => 'normal', 'name' => 'Normal', 'count' => 5 ),
	(object) array( 'term_id' => 12, 'slug' => 'kids-only', 'name' => 'Kids only', 'count' => 0 ),
	(object) array( 'term_id' => 13, 'slug' => 'empty', 'name' => 'Empty', 'count' => 0 ),
	(object) array( 'term_id' => 14, 'slug' => 'no-count-field', 'name' => 'No count field' ),
	(object) array( 'term_id' => 15, 'slug' => 'a-first', 'name' => 'A first', 'count' => 0 ),
);
$PAD = new ArticlePromptFake();
vance_hub_article_customize_register( $PAD );

check( 'counts: a normal top-level category (own count above 0) gets a select', isset( $PAD->controls['vance_hub_aprompt_tool_normal'] ) );
check( 'counts: a top-level category with count 0 and a non-empty child gets a select', isset( $PAD->controls['vance_hub_aprompt_tool_kids-only'] ) );
check( 'counts: a top-level category with count 0 and no non-empty descendant gets no select', ! isset( $PAD->controls['vance_hub_aprompt_tool_empty'], $PAD->settings['vance_hub_aprompt_tool_empty'] ) );
check( 'counts: a category with no count field and no descendant gets no select', ! isset( $PAD->controls['vance_hub_aprompt_tool_no-count-field'] ) );
$PAD_PRIO = array();
foreach ( array( 'a-first', 'kids-only', 'normal' ) as $PAD_SLUG ) {
	$PAD_PRIO[ $PAD_SLUG ] = isset( $PAD->controls[ 'vance_hub_aprompt_tool_' . $PAD_SLUG ] ) ? $PAD->controls[ 'vance_hub_aprompt_tool_' . $PAD_SLUG ]['priority'] : -1;
}
check( 'counts: the three kept categories are in name order at priorities 30, 32 and 34', array( 'a-first' => 30, 'kids-only' => 32, 'normal' => 34 ) === $PAD_PRIO );
check( 'counts: the top-level query asks for empty categories too (hide_empty false, parent 0)', array( 'parent' => 0, 'hide_empty' => false ) === $GLOBALS['CATS_ARGS'][0] );
$ASKED = array_column( array_filter( $GLOBALS['CATS_ARGS'], function ( $a ) { return isset( $a['child_of'] ); } ), 'child_of' );
sort( $ASKED );
check( 'counts: the descendant query is asked only for the four categories whose own count is 0, never for the normal one', array( 12, 13, 14, 15 ) === $ASKED && ! in_array( 11, $ASKED, true ) );
check( 'keep: pure function keeps an own count or a busy descendant, skips neither', true === vance_hub_article_keep_top_category( 2, 0 ) && true === vance_hub_article_keep_top_category( 0, 1 ) && false === vance_hub_article_keep_top_category( 0, 0 ) && false === vance_hub_article_keep_top_category( null, 'x' ) );

$GLOBALS['CATS_REG'] = array();
$NONE = new ArticlePromptFake();
vance_hub_article_customize_register( $NONE );
check( 'register, no categories: only the three article settings are added', 3 === count( $NONE->settings ) );
check( 'register, no categories: no select is added', 3 === count( $NONE->controls ) );

echo "\n$PASS passed, " . count( $FAIL ) . " failed\n";
foreach ( $FAIL as $f ) {
	echo "FAIL: $f\n";
}
exit( $FAIL ? 1 : 0 );
