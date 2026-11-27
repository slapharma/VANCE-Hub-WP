<?php
/**
 * Tests for the VanceHubClub article layout: inc/hub-article.php, and the one
 * line it needs in single.php.
 *
 * Same shape as hub-layout.test.php, and on purpose the SAME stubs: the
 * stub block of that suite (everything before its first check) is lifted and
 * evaluated here, as render.php-style tools do, so the two suites cannot drift
 * into testing against different fakes of WordPress. If that suite's layout
 * changes so the block cannot be found, this one stops with exit 2 rather
 * than passing on nothing.
 *
 * WHAT IS COVERED
 *   1  the switch: inert with the mod off, the saved checkbox, the
 *      administrator preview both ways, a visitor's query ignored, an array
 *      query safe, non-article pages never active
 *   2  hooks, assets, body class, Customizer registration
 *   3  category -> tool mapping, ancestors included, unknown categories,
 *      a filtered map, a broken map
 *   4  paragraph counting and insertion: 0/1/3/4/many paragraphs, blocks,
 *      shortcodes, lists, blockquotes, headings, nested <p>, comments,
 *      raw-text elements, quoted '>', unclosed inline tags, implied closes,
 *      idempotence
 *   5  the the_content filter's guards: main query, in the loop, single
 *      article, the main post only, feeds, admin, AJAX, excerpts, password
 *      and handout posts, and REST (last, because a constant cannot be unset)
 *   6  the prompt markup: copy, tool, annotations opt-out, escaping
 *   7  the sidebar cards: what renders, what does not, escaping
 *   8  single.php: the sidebar hook is a live call at the top of the aside
 *   9  the content readers that must never see the prompt read post_content
 *      directly, not the filtered content
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

function a_reset() {
	reset_state();
	$GLOBALS['FILTER_RETURNS'] = array();
	$GLOBALS['A']              = array(
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
function is_singular( $type = '' ) {
	return 'post' === $type ? (bool) $GLOBALS['A']['singular_post'] : (bool) $GLOBALS['A']['singular_post'];
}
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

/** A category term as get_the_category() returns it. */
function a_cat( $id, $slug, $parent = 0 ) {
	return (object) array( 'term_id' => $id, 'slug' => $slug, 'name' => ucfirst( $slug ), 'parent' => $parent );
}

require_once $THEME . '/inc/hub-article.php';

/* ---- runner ----------------------------------------------------------------- */

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/** Run the_content as WordPress would, through this file's filter only. */
function run_filter( $content ) {
	return vance_hub_article_content_filter( $content );
}

/** Capture the sidebar action's output. */
function sidebar_html() {
	ob_start();
	vance_hub_article_sidebar_top();
	return ob_get_clean();
}

/** Byte position of $needle in $hay, or -1. */
function at( $hay, $needle ) {
	$p = strpos( $hay, $needle );
	return false === $p ? -1 : $p;
}

/** Content with $n top-level paragraphs, each numbered. */
function paras( $n ) {
	$out = '';
	for ( $i = 1; $i <= $n; $i++ ) {
		$out .= "<p>Para $i.</p>\n";
	}
	return $out;
}

$BLOCK = '<aside data-vhub-aprompt>X</aside>';

/* ===================================================================== */
section( '1. The switch' );

check( 'resolve: nothing saved is off', vance_hub_article_resolve( null, '', false ) === false );
check( 'resolve: default false is off', vance_hub_article_resolve( false, '', false ) === false );
check( 'resolve: a ticked box ("1") is on', vance_hub_article_resolve( '1', '', false ) === true );
check( 'resolve: a stored true is on', vance_hub_article_resolve( true, '', false ) === true );
check( 'resolve: garbage mod is off', vance_hub_article_resolve( 'hub', '', false ) === false );
check( 'resolve: admin ?vhh_layout=hub turns it on over an unticked box', vance_hub_article_resolve( false, 'hub', true ) === true );
check( 'resolve: admin ?vhh_layout=classic turns it off over a ticked box', vance_hub_article_resolve( true, 'classic', true ) === false );
check( 'resolve: a visitor\'s hub value is ignored', vance_hub_article_resolve( false, 'hub', false ) === false );
check( 'resolve: a visitor\'s classic value is ignored', vance_hub_article_resolve( true, 'classic', false ) === true );
check( 'resolve: an array query is ignored', vance_hub_article_resolve( false, array( 'hub' ), true ) === false );
check( 'resolve: an unknown query falls to the box', vance_hub_article_resolve( true, 'banana', true ) === true );

a_reset();
check( 'active: nothing saved, an article, a visitor is off', vance_hub_article_active() === false );

a_reset();
$_GET = array( 'vhh_layout' => 'hub' );
check( 'active: a visitor adding ?vhh_layout=hub stays off', vance_hub_article_active() === false );

a_reset();
$_GET = array( 'vhh_layout' => array( 'hub' ) );
$CAPS_ERR = '';
$GLOBALS['CAPS'] = array( 'edit_theme_options' => true );
try { $r = vance_hub_article_active(); } catch ( Throwable $e ) { $CAPS_ERR = $e->getMessage(); $r = null; }
check( 'active: an admin\'s array query does not throw', '' === $CAPS_ERR );
check( 'active: an admin\'s array query does not turn it on', false === $r );

a_reset();
$GLOBALS['CAPS'] = array( 'edit_theme_options' => true );
$_GET = array( 'vhh_layout' => 'hub' );
check( 'active: admin ?vhh_layout=hub previews it with nothing saved', vance_hub_article_active() === true );
$_GET = array( 'vhh_layout' => 'HUB' );
check( 'active: admin preview value is case-insensitive', vance_hub_article_active() === true );

a_reset();
$GLOBALS['MODS'] = array( 'vance_home_layout' => 'hub', 'vance_hub_articles' => true );
$GLOBALS['CAPS'] = array( 'edit_theme_options' => true );
$_GET = array( 'vhh_layout' => 'classic' );
check( 'active: admin ?vhh_layout=classic forces it off with the box ticked', vance_hub_article_active() === false );

a_reset();
$GLOBALS['MODS'] = array( 'vance_home_layout' => 'hub', 'vance_hub_articles' => true );
$_GET = array( 'vhh_layout' => 'classic' );
check( 'active: a visitor\'s ?vhh_layout=classic cannot switch it off', vance_hub_article_active() === true );

a_reset();
$GLOBALS['MODS'] = array( 'vance_home_layout' => 'hub', 'vance_hub_articles' => '1' );
check( 'active: the ticked box turns it on for a visitor', vance_hub_article_active() === true );
$GLOBALS['A']['singular_post'] = false;
check( 'active: never on a page that is not a single article', vance_hub_article_active() === false );

a_reset();
$GLOBALS['A']['singular_post'] = false;
$GLOBALS['CAPS'] = array( 'edit_theme_options' => true );
$_GET = array( 'vhh_layout' => 'hub' );
check( 'active: the admin preview does nothing off an article', vance_hub_article_active() === false );

// Inert with the mod off: every output path returns what it was given.
a_reset();
$C = paras( 6 );
check( 'inert: the content filter returns the content byte-for-byte', run_filter( $C ) === $C );
check( 'inert: the sidebar action prints nothing', sidebar_html() === '' );
$classes = array( 'single', 'single-post', 'has-vance-bottom-nav' );
check( 'inert: the body classes are unchanged', vance_hub_article_body_class( $classes ) === $classes );
vance_hub_article_enqueue_assets();
check( 'inert: no stylesheet or script is enqueued', $GLOBALS['ENQUEUED'] === array() );

/* ===================================================================== */
section( '1b. Master switch (Home layout) over the article part' );

// Master classic: the part is off even when its own box is ticked.
a_reset();
$GLOBALS['MODS'] = array( 'vance_hub_articles' => true );
check( 'master: classic with the article box ticked is off for a visitor', vance_hub_article_active() === false );

// Master classic: the admin preview still turns the part on.
a_reset();
$GLOBALS['MODS'] = array( 'vance_home_layout' => 'classic', 'vance_hub_articles' => true );
$GLOBALS['CAPS'] = array( 'edit_theme_options' => true );
$_GET = array( 'vhh_layout' => 'hub' );
check( 'master: classic with an admin ?vhh_layout=hub preview is on', vance_hub_article_active() === true );

// Master hub: a part never saved is on, because its default is on.
a_reset();
$GLOBALS['MODS'] = array( 'vance_home_layout' => 'hub' );
check( 'master: hub with the article box never saved is on', vance_hub_article_active() === true );

// Master hub: a part saved off is off.
a_reset();
$GLOBALS['MODS'] = array( 'vance_home_layout' => 'hub', 'vance_hub_articles' => false );
check( 'master: hub with the article box saved off is off', vance_hub_article_active() === false );

/* ===================================================================== */
section( '2. Hooks, assets, body class, Customizer' );

$hooked = function ( $tag, $cb, $prio ) {
	return isset( $GLOBALS['HOOKS'][ $tag ][ $prio ] ) && in_array( $cb, $GLOBALS['HOOKS'][ $tag ][ $prio ], true );
};
check( 'hook: the_content filter at priority 30 (after wpautop, shortcodes, DOI links)', $hooked( 'the_content', 'vance_hub_article_content_filter', 30 ) );
check( 'hook: sidebar cards on vance_article_sidebar_top', $hooked( 'vance_article_sidebar_top', 'vance_hub_article_sidebar_top', 10 ) );
check( 'hook: body class', $hooked( 'body_class', 'vance_hub_article_body_class', 10 ) );
check( 'hook: assets at 20', $hooked( 'wp_enqueue_scripts', 'vance_hub_article_enqueue_assets', 20 ) );
check( 'hook: Customizer at 11, after the Home layout section exists', $hooked( 'customize_register', 'vance_hub_article_customize_register', 11 ) );

a_reset();
$GLOBALS['MODS'] = array( 'vance_home_layout' => 'hub', 'vance_hub_articles' => true );
vance_hub_article_enqueue_assets();
$st = isset( $GLOBALS['ENQUEUED']['style:vance-hub-article'] ) ? $GLOBALS['ENQUEUED']['style:vance-hub-article'] : null;
check( 'assets: hub-article.css enqueued when on', $st && substr( $st['src'], -strlen( '/assets/css/hub-article.css' ) ) === '/assets/css/hub-article.css' );
check( 'assets: the file really exists', file_exists( $THEME . '/assets/css/hub-article.css' ) );
check( 'assets: versioned by the file mtime', $st && (string) $st['ver'] === (string) filemtime( $THEME . '/assets/css/hub-article.css' ) );
check( 'assets: loads after main.css and oped-template.css', $st && $st['deps'] === array( 'vance-main-style', 'vance-oped-template' ) );
check( 'assets: the hub home script (Ask form handler) is enqueued in the footer', ! empty( $GLOBALS['ENQUEUED']['script:vance-hub-home']['in_footer'] ) );
check( 'body class: vhub-article added when on', in_array( 'vhub-article', vance_hub_article_body_class( array( 'single' ) ), true ) );

class A_Customize {
	public $panels   = array();
	public $settings = array();
	public $controls = array();
	public function add_panel( $id, $args ) { $this->panels[ $id ] = $args; }
	public function add_setting( $id, $args ) { $this->settings[ $id ] = $args; }
	public function add_control( $id, $args ) { $this->controls[ $id ] = $args; }
}
$cz = new A_Customize();
vance_hub_article_customize_register( $cz );
check( 'customizer: setting vance_hub_articles exists', isset( $cz->settings['vance_hub_articles'] ) );
check( 'customizer: default is ON (the master switch decides whether it shows)', isset( $cz->settings['vance_hub_articles'] ) && true === $cz->settings['vance_hub_articles']['default'] );
check( 'customizer: sanitised as a checkbox', isset( $cz->settings['vance_hub_articles'] ) && 'vance_hub_sanitize_checkbox' === $cz->settings['vance_hub_articles']['sanitize_callback'] );
check( 'customizer: a checkbox in section 4 (vance_hub_sec_pages)', isset( $cz->controls['vance_hub_articles'] ) && 'vance_hub_sec_pages' === $cz->controls['vance_hub_articles']['section'] && 'checkbox' === $cz->controls['vance_hub_articles']['type'] );
check( 'customizer: the label names articles and VanceHubClub', isset( $cz->controls['vance_hub_articles'] ) && false !== stripos( $cz->controls['vance_hub_articles']['label'], 'article' ) && false !== strpos( $cz->controls['vance_hub_articles']['label'], 'VanceHubClub' ) );

/* ===================================================================== */
section( '3. Category -> tool mapping' );

$MAP = vance_hub_article_tool_map();
check( 'map: food-nutrition -> recipes', vance_hub_article_pick_tool( array( 'food-nutrition' ), $MAP ) === 'recipes' );
check( 'map: nutrition-diet -> recipes', vance_hub_article_pick_tool( array( 'nutrition-diet' ), $MAP ) === 'recipes' );
check( 'map: gastro-recipes -> recipes', vance_hub_article_pick_tool( array( 'gastro-recipes' ), $MAP ) === 'recipes' );
check( 'map: a food slug among others still wins', vance_hub_article_pick_tool( array( 'content-gastro-living', 'food-nutrition' ), $MAP ) === 'recipes' );
check( 'map: the parent section alone is the survey', vance_hub_article_pick_tool( array( 'content-gastro-living' ), $MAP ) === 'survey' );
check( 'map: clinical reviews -> survey', vance_hub_article_pick_tool( array( 'content-clinical-reviews', 'clinical-trial-abstracts' ), $MAP ) === 'survey' );
check( 'map: unknown slug -> survey', vance_hub_article_pick_tool( array( 'no-such-category' ), $MAP ) === 'survey' );
check( 'map: no categories -> survey', vance_hub_article_pick_tool( array(), $MAP ) === 'survey' );
check( 'map: non-string slugs are ignored', vance_hub_article_pick_tool( array( null, 7, array( 'food-nutrition' ) ), $MAP ) === 'survey' );
check( 'map: a broken map (not an array) falls back', vance_hub_article_pick_tool( array( 'food-nutrition' ), 'junk' ) === 'survey' );
check( 'map: a map row that is not a list is skipped', vance_hub_article_pick_tool( array( 'food-nutrition' ), array( 'recipes' => 'food-nutrition' ) ) === 'survey' );

a_reset();
$GLOBALS['FILTER_RETURNS']['vance_hub_article_tool_map'] = array( 'discounts' => array( 'living-well-support' ) );
check( 'map: the filter can remap', vance_hub_article_pick_tool( array( 'living-well-support' ), vance_hub_article_tool_map() ) === 'discounts' );
$GLOBALS['FILTER_RETURNS']['vance_hub_article_tool_map'] = 'not-a-map';
check( 'map: a filter returning garbage gives an empty map', vance_hub_article_tool_map() === array() );

// Ancestors: a sub-category of Food & Nutrition maps to recipes.
a_reset();
$GLOBALS['CATS_BY_POST'][4905]    = array( a_cat( 301, 'gentle-breakfasts', 87 ) );
$GLOBALS['A']['ancestors'][301]   = array( 87, 75 );
$GLOBALS['A']['terms'][87]        = a_cat( 87, 'food-nutrition', 75 );
$GLOBALS['A']['terms'][75]        = a_cat( 75, 'content-gastro-living' );
$slugs = vance_hub_article_category_slugs( 4905 );
check( 'ancestors: own slug kept', in_array( 'gentle-breakfasts', $slugs, true ) );
check( 'ancestors: parent and grandparent added', in_array( 'food-nutrition', $slugs, true ) && in_array( 'content-gastro-living', $slugs, true ) );
check( 'ancestors: a grandchild of Food & Nutrition gets the meal planner', vance_hub_article_tool_key( 4905 ) === 'recipes' );
$GLOBALS['A']['terms'][87] = null;
check( 'ancestors: a missing ancestor term is skipped, not fatal', vance_hub_article_tool_key( 4905 ) === 'survey' );

a_reset();
$GLOBALS['CATS_BY_POST'][4905] = array( a_cat( 77, 'understanding-your-condition', 75 ) );
$GLOBALS['A']['ancestors'][77] = array( 75 );
$GLOBALS['A']['terms'][75]     = a_cat( 75, 'content-gastro-living' );
check( 'ancestors: a Gastro Living article outside food gets the survey', vance_hub_article_tool_key( 4905 ) === 'survey' );

/* ===================================================================== */
section( '4. Paragraph counting and insertion' );

check( 'count: 0 paragraphs', count( vance_hub_article_paragraph_ends( '<h2>A</h2><ul><li>x</li></ul>' ) ) === 0 );
check( 'count: 1', count( vance_hub_article_paragraph_ends( paras( 1 ) ) ) === 1 );
check( 'count: 3', count( vance_hub_article_paragraph_ends( paras( 3 ) ) ) === 3 );
check( 'count: 4', count( vance_hub_article_paragraph_ends( paras( 4 ) ) ) === 4 );
check( 'count: 25', count( vance_hub_article_paragraph_ends( paras( 25 ) ) ) === 25 );

$NESTED = '<div><p>a</p><p>b</p><p>c</p><p>d</p></div><blockquote><p>q</p></blockquote><figure><figcaption><p>f</p></figcaption></figure><ul><li><p>l</p></li></ul><table><tr><td><p>t</p></td></tr></table><p>Top 1.</p>';
check( 'count: <p> inside div/blockquote/figure/list/table is not top-level', count( vance_hub_article_paragraph_ends( $NESTED ) ) === 1 );
check( 'count: a <p> in a comment is not counted', count( vance_hub_article_paragraph_ends( '<!-- <p>x</p><p>y</p> --><p>1</p>' ) ) === 1 );
check( 'count: a <p> in a script is not counted', count( vance_hub_article_paragraph_ends( '<script>var s = "<p>x</p><p>y</p>";</script><p>1</p>' ) ) === 1 );
check( 'count: a <p> in a style is not counted', count( vance_hub_article_paragraph_ends( '<style>p:before{content:"<p>"}</style><p>1</p>' ) ) === 1 );
check( 'count: a quoted > in an attribute does not end the tag', count( vance_hub_article_paragraph_ends( '<p data-x="a>b">1</p><p title=\'c>d\'>2</p>' ) ) === 2 );
check( 'count: an unclosed inline tag inside a paragraph does not throw the count off', count( vance_hub_article_paragraph_ends( '<p>1 <span>open</p><p>2</p><p>3 <em>x</p><p>4</p>' ) ) === 4 );
check( 'count: a block start tag closes an open paragraph', count( vance_hub_article_paragraph_ends( '<p>1<div>x</div><p>2</p><p>3</p><p>4</p>' ) ) === 4 );
check( 'count: upper-case tags count', count( vance_hub_article_paragraph_ends( '<P>1</P><P CLASS="x">2</P>' ) ) === 2 );
check( 'count: <p> with attributes counts, <pre> does not', count( vance_hub_article_paragraph_ends( '<p class="lead">1</p><pre>2</pre><param name="x"><p>3</p>' ) ) === 2 );
check( 'count: void and self-closed elements do not nest', count( vance_hub_article_paragraph_ends( '<img src="a.jpg"><br/><hr /><p>1<br>x</p><input type="text"/><p>2</p>' ) ) === 2 );
check( 'count: a stray end tag is ignored', count( vance_hub_article_paragraph_ends( '</div></p><p>1</p><p>2</p>' ) ) === 2 );
check( 'count: an unterminated comment stops the scan safely', count( vance_hub_article_paragraph_ends( '<p>1</p><!-- never closed <p>2</p>' ) ) === 1 );
check( 'count: a lone < in text is skipped', count( vance_hub_article_paragraph_ends( '<p>3 < 4 and 5 > 2</p><p>2</p>' ) ) === 2 );

// Insertion positions.
$C4  = paras( 4 );
$out = vance_hub_article_insert( $C4, $BLOCK );
check( 'insert: 4 paragraphs -> after the second', at( $out, 'Para 2.</p>' ) < at( $out, $BLOCK ) && at( $out, $BLOCK ) < at( $out, '<p>Para 3.' ) );
check( 'insert: the content around it is unchanged', str_replace( "\n" . $BLOCK . "\n", '', $out ) === $C4 );
$out = vance_hub_article_insert( paras( 12 ), $BLOCK );
check( 'insert: many paragraphs -> after the second', at( $out, 'Para 2.</p>' ) < at( $out, $BLOCK ) && at( $out, $BLOCK ) < at( $out, '<p>Para 3.' ) );
foreach ( array( 1, 3 ) as $n ) {
	$out = vance_hub_article_insert( paras( $n ), $BLOCK );
	check( "insert: $n paragraph(s) -> after the content", at( $out, "Para $n.</p>" ) < at( $out, $BLOCK ) && substr( rtrim( $out ), -strlen( $BLOCK ) ) === $BLOCK );
}
$out = vance_hub_article_insert( '<h2>A</h2><ul><li>x</li></ul>', $BLOCK );
check( 'insert: 0 paragraphs -> after the content', substr( rtrim( $out ), -strlen( $BLOCK ) ) === $BLOCK && 0 === at( $out, '<h2>A</h2>' ) );
check( 'insert: empty content is left empty', vance_hub_article_insert( '', $BLOCK ) === '' );
check( 'insert: whitespace-only content is left alone', vance_hub_article_insert( "  \n", $BLOCK ) === "  \n" );
check( 'insert: an empty block changes nothing', vance_hub_article_insert( $C4, '' ) === $C4 );

$MIXED = "<!-- wp:paragraph -->\n<p>One.</p>\n<!-- /wp:paragraph -->\n<blockquote><p>Quote one.</p><p>Quote two.</p></blockquote>\n<h2>Heading</h2>\n<ul><li><p>List para.</p></li></ul>\n[vance_tool id=\"1\"]\n<p>Two.</p>\n<h3>Sub</h3>\n<p>Three.</p>\n<figure class=\"wp-block-image\"><img src=\"a.jpg\" alt=\"\"><figcaption>Cap</figcaption></figure>\n<p>Four.</p>";
$out = vance_hub_article_insert( $MIXED, $BLOCK );
check( 'insert: blocks, quotes, lists, headings, shortcodes in between -> after the 2nd top-level paragraph', at( $out, 'Two.</p>' ) < at( $out, $BLOCK ) && at( $out, $BLOCK ) < at( $out, '<h3>Sub' ) );
check( 'insert: the quote\'s paragraphs were not counted (not placed inside it)', at( $out, $BLOCK ) > at( $out, '</blockquote>' ) );

// The live article this layout was built against starts with a blockquote.
$LIVE = "<blockquote><p>Intro.</p>\n</blockquote>\n<h2>What is clinical remission?</h2>\n<p>A.</p>\n<h2>What is mucosal healing?</h2>\n<p>B.</p>\n<h2>Why it matters</h2>\n<p>C.</p>\n<h2>Key</h2>\n<p>D.</p>";
$out  = vance_hub_article_insert( $LIVE, $BLOCK );
check( 'insert: live article shape -> before "Why it matters"', at( $out, 'B.</p>' ) < at( $out, $BLOCK ) && at( $out, $BLOCK ) < at( $out, '<h2>Why it matters' ) );

$NEST4 = '<div class="wp-block-group"><p>a</p><p>b</p><p>c</p><p>d</p><p>e</p></div><p>Only top.</p>';
$out   = vance_hub_article_insert( $NEST4, $BLOCK );
check( 'insert: nested paragraphs never pull the block inside a wrapper', substr( rtrim( $out ), -strlen( $BLOCK ) ) === $BLOCK );

$once  = vance_hub_article_insert( $C4, $BLOCK );
$twice = vance_hub_article_insert( $once, $BLOCK );
check( 'idempotent: inserting twice gives the same content as once', $once === $twice );
check( 'idempotent: exactly one block', substr_count( $twice, 'data-vhub-aprompt' ) === 1 );

/* ===================================================================== */
section( '5. The the_content filter and its guards' );

/** Arrange a request where the filter should run: mod on, single article, main loop. */
function a_live() {
	a_reset();
	$GLOBALS['MODS'] = array( 'vance_home_layout' => 'hub', 'vance_hub_articles' => true );
	$GLOBALS['SURVEY_STEPS'] = 9;
	$GLOBALS['RECIPE_CELLS'] = array( array( 'key' => 'grid', 'value' => '90', 'label' => 'Recipes', 'href' => '' ) );
}

a_live();
$out = run_filter( $C4 );
check( 'filter: runs on the main post in the main loop of an article', substr_count( $out, 'data-vhub-aprompt' ) === 1 );
check( 'filter: the default tool is the survey', false !== strpos( $out, 'href="https://example.test/gastro-health-survey/"' ) );
check( 'filter: run twice on its own output, still one prompt', substr_count( run_filter( $out ), 'data-vhub-aprompt' ) === 1 );

/**
 * Run the filter as apply_filters( 'the_content' ) does: WordPress pushes the
 * hook name onto $wp_current_filter for the length of the call, then pops it.
 */
function run_real( $content, $inner = null ) {
	$GLOBALS['wp_current_filter'][] = 'the_content';
	if ( null !== $inner ) {
		$GLOBALS['NESTED_OUT'] = run_real( $inner );
	}
	$content = vance_hub_article_content_filter( $content );
	array_pop( $GLOBALS['wp_current_filter'] );
	return $content;
}
$GLOBALS['wp_current_filter'] = array();

// A shortcode or block that filters another string while the article is being
// built is a call from inside the_content: it must not get the prompt.
a_live();
$GLOBALS['vance_hub_article_prompted'] = array();
$GLOBALS['NESTED_OUT'] = '';
$outer = run_real( $C4, $C4 );
check( 'nested: a the_content call made inside another gets no prompt', false === strpos( $GLOBALS['NESTED_OUT'], 'data-vhub-aprompt' ) );
check( 'nested: the nested string comes back unchanged', $GLOBALS['NESTED_OUT'] === $C4 );
check( 'nested: the article body itself still gets its one prompt', substr_count( $outer, 'data-vhub-aprompt' ) === 1 );
check( 'nested: the call count is back to zero afterwards', vance_hub_article_depth() === 0 );

// One prompt per article per request: a later call for the same post (a sidebar
// widget, a direct wp_trim_excerpt) gets none.
a_live();
$GLOBALS['vance_hub_article_prompted'] = array();
$first  = run_real( $C4 );
$second = run_real( $C4 );
check( 'once: the first real run for the article gets the prompt', substr_count( $first, 'data-vhub-aprompt' ) === 1 );
check( 'once: a second real run for the same article gets none', $second === $C4 );
$GLOBALS['vance_hub_article_prompted'] = array();
check( 'once: control: with the record cleared, the prompt goes in again', substr_count( run_real( $C4 ), 'data-vhub-aprompt' ) === 1 );
$GLOBALS['vance_hub_article_prompted'] = array();
$GLOBALS['wp_current_filter'] = array( 'wp_head', 'the_content', 'the_title', 'the_content' );
check( 'depth: counts only the_content entries in the running-filter stack', vance_hub_article_depth() === 2 );
$GLOBALS['wp_current_filter'] = 'not-an-array';
check( 'depth: a missing or damaged stack reads as zero, not an error', vance_hub_article_depth() === 0 );
$GLOBALS['wp_current_filter'] = array();
check( 'hooks: the prompt is the only the_content filter this file adds',
	isset( $GLOBALS['HOOKS']['the_content'] ) && array_keys( $GLOBALS['HOOKS']['the_content'] ) === array( 30 ) );

$guards = array(
	'not in the loop (wp_head: AIOSEO description, schema, og tags; annotations review page)' => array( 'in_loop', false ),
	'not the main query'                         => array( 'main_query', false ),
	'not a single article'                        => array( 'singular_post', false ),
	'a feed'                                      => array( 'feed', true ),
	'admin'                                       => array( 'admin', true ),
	'admin-ajax'                                  => array( 'ajax', true ),
	'a password-protected post'                  => array( 'password', true ),
	'a secondary loop post (Read Next, related)'  => array( 'the_id', 777 ),
	'no current post'                             => array( 'the_id', 0 ),
);
foreach ( $guards as $label => $set ) {
	a_live();
	$GLOBALS['A'][ $set[0] ] = $set[1];
	check( "guard: nothing added for $label", run_filter( $C4 ) === $C4 );
}
foreach ( array( 'get_the_excerpt', 'wp_trim_excerpt' ) as $f ) {
	a_live();
	$GLOBALS['A']['doing'] = array( $f );
	check( "guard: nothing added while $f runs", run_filter( $C4 ) === $C4 );
}

// A Patient Downloads handout: single.php's own test, with a real handout file.
$DL = glob( $THEME . '/assets/downloads/*.pdf' );
check( 'handout: the downloads folder has a PDF to test with', ! empty( $DL ) );
if ( $DL ) {
	a_live();
	$GLOBALS['META'][4905]['_vpd_pdf_file'] = basename( $DL[0] );
	check( 'handout: recognised as a download post', vance_hub_article_is_download( 4905 ) === true );
	check( 'handout: no prompt in a handout', run_filter( $C4 ) === $C4 );
	$GLOBALS['META'][4905]['_vpd_pdf_file'] = 'no-such-file.pdf';
	check( 'handout: a meta value with no file is a normal article', vance_hub_article_is_download( 4905 ) === false && run_filter( $C4 ) !== $C4 );
}

// Food article -> the meal planner prompt.
a_live();
$GLOBALS['CATS_BY_POST'][4905] = array( a_cat( 87, 'food-nutrition', 75 ) );
$out = run_filter( $C4 );
check( 'filter: a food article gets the meal planner', false !== strpos( $out, 'href="https://example.test/gastro-meal-planner/"' ) && false === strpos( $out, 'gastro-health-survey' ) );

// A map that names a tool that does not exist falls back to the survey.
a_live();
$GLOBALS['FILTER_RETURNS']['vance_hub_article_tool_map'] = array( 'teleporter' => array( 'food-nutrition' ) );
$GLOBALS['CATS_BY_POST'][4905] = array( a_cat( 87, 'food-nutrition', 75 ) );
$out = run_filter( $C4 );
check( 'filter: an unknown mapped tool falls back to the survey', false !== strpos( $out, 'gastro-health-survey' ) );

/* ===================================================================== */
section( '6. The prompt markup' );

a_live();
$out = run_filter( $C4 );
preg_match( '/<aside class="vhub-aprompt[^"]*".*?<\/aside>/s', $out, $pm );
$P = isset( $pm[0] ) ? $pm[0] : '';
check( 'prompt: found', '' !== $P );
check( 'prompt: marked vhh-ui so annotation anchoring skips it', (bool) preg_match( '/class="[^"]*\bvhh-ui\b/', $P ) );
check( 'prompt: eyebrow "Try this now"', false !== strpos( $P, '<div class="vhub-aprompt__eyebrow">Try this now</div>' ) );
check( 'prompt: eyebrow and title are not <p> (main.css forces .container p to 16px on phones)', ! preg_match( '/<p class="vhub-aprompt__(eyebrow|title)/', $P ) );
check( 'prompt: survey copy from the hub home survey card', false !== strpos( $P, 'The Hub then points you to the guides, tools and recipes that fit.' ) );
check( 'prompt: makes no result or clinician claim', ! preg_match( '/result|summary|clinician|score|diagnos/i', wp_strip_all_tags( $P ) ) );
check( 'prompt: button to the survey with the tile wording', (bool) preg_match( '/<a class="vhub-aprompt__btn" href="https:\/\/example\.test\/gastro-health-survey\/">Start the survey /', $P ) );
check( 'prompt: the photo is a decorative background, hidden from assistive technology', (bool) preg_match( '/<div class="vhub-aprompt__img" aria-hidden="true" style="background-image:url\(\'[^"\']+ulcerative-colitis\.webp\?v=\d+\'\)"><\/div>/', $P ) );
check( 'prompt: no <img>, so the annotations plugin does not offer the photo for comment', false === stripos( $P, '<img' ) );
check( 'prompt: no heading element (keeps the article outline)', ! preg_match( '/<h[1-6]\b/i', $P ) );
check( 'prompt: named landmark', false !== strpos( $P, 'aria-label="Try this now"' ) );

$bad = vance_hub_article_prompt_html(
	array( 'mod' => 'x"><script>', 'href' => 'javascript:alert(1)', 'cta' => '<b>Go</b>', 'img' => 'https://example.test/a.jpg', 'alt' => '"><img onerror=1>' ),
	array( 'title' => '<script>t</script>', 'text' => 'a & b' )
);
check( 'escape: no raw script tag', false === stripos( $bad, '<script' ) );
check( 'escape: javascript: href dropped', false === strpos( $bad, 'javascript:' ) );
check( 'escape: the alt text is not output at all (the photo is a decorative background)', false === strpos( $bad, 'onerror' ) );
$quoted = vance_hub_article_prompt_html(
	array( 'mod' => 'survey', 'href' => 'https://example.test/t/', 'cta' => 'Go', 'img' => "https://example.test/a');color:red;x:url('b.jpg" ),
	array( 'title' => 't', 'text' => 'x' )
);
check( 'escape: a quote or bracket in the photo address cannot end the CSS url() early',
	false === strpos( $quoted, "a')" ) && false === strpos( $quoted, '&#039;)' ) && false !== strpos( $quoted, 'a%27%29;color:red' ) );
check( 'escape: the class modifier cannot break out', false === strpos( $bad, 'x"><' ) && false !== strpos( $bad, 'vhub-aprompt--x&quot;&gt;&lt;script&gt;' ) );
check( 'escape: cta and text escaped', false !== strpos( $bad, '&lt;b&gt;Go&lt;/b&gt;' ) && false !== strpos( $bad, 'a &amp; b' ) );
check( 'prompt: nothing without a URL', vance_hub_article_prompt_html( array( 'href' => '', 'cta' => 'x' ), array( 'title' => 't', 'text' => 'x' ) ) === '' );
check( 'prompt: nothing without copy', vance_hub_article_prompt_html( array( 'href' => 'https://e.test/', 'cta' => 'x' ), array() ) === '' );
check( 'prompt: no <img> when the tool has no image', false === strpos( vance_hub_article_prompt_html( array( 'mod' => 'survey', 'href' => 'https://e.test/', 'cta' => 'x', 'img' => '' ), vance_hub_article_prompt_copy( 'survey' ) ), '<img' ) );
check( 'copy: unknown tool has no prompt copy', vance_hub_article_prompt_copy( 'nope' ) === array() );

/* ===================================================================== */
section( '7. Sidebar cards' );

a_live();
$S = sidebar_html();
check( 'sidebar: Ask card renders', false !== strpos( $S, 'vhub-acard--ask' ) );
check( 'sidebar: Ask heading is an h4, like the other sidebar blocks', false !== strpos( $S, '<h4 id="vhub-acard-ask-title">Ask VANCE-Ai about this article</h4>' ) );
preg_match( '/<input[^>]*>/', $S, $im );
check( 'sidebar: the question input has NO name attribute', isset( $im[0] ) && ! preg_match( '/\sname=/', $im[0] ) );
check( 'sidebar: the input has a visible label', (bool) preg_match( '/<label class="vhub-aask__label" for="vhub-aask-input">/', $S ) && false !== strpos( $S, 'id="vhub-aask-input"' ) );
check( 'sidebar: no-JS fallback is the Ask AI page, by GET', (bool) preg_match( '/<form class="vhub-aask" action="https:\/\/example\.test\/ask-ai\/" method="get" data-vhub-ask-form>/', $S ) );
check( 'sidebar: highlight tip shown when highlight-to-ask is on (its default)', false !== strpos( $S, 'highlight any word' ) );
$GLOBALS['MODS']['vance_askai_highlight_enable'] = false;
check( 'sidebar: highlight tip hidden when it is off', false === strpos( sidebar_html(), 'highlight any word' ) );
unset( $GLOBALS['MODS']['vance_askai_highlight_enable'] );

check( 'sidebar: tools card renders', false !== strpos( $S, 'vhub-acard--tools' ) );
preg_match_all( '/<a class="vhub-atool vhub-atool--([a-z]+)" href="([^"]+)"/', $S, $tm );
check( 'sidebar: two tools, mapped first then discounts', $tm[1] === array( 'survey', 'discounts' ) );
check( 'sidebar: real tool URLs', $tm[2] === array( 'https://example.test/gastro-health-survey/', 'https://example.test/ibd-discounts/' ) );
$survey_tile_desc = '';
foreach ( vance_hub_tools() as $tile_row ) {
	if ( isset( $tile_row['mod'] ) && 'survey' === $tile_row['mod'] ) {
		$survey_tile_desc = (string) $tile_row['desc'];
	}
}
check( 'sidebar: survey row prints the tile desc from vance_hub_tools() (live survey count)', '' !== $survey_tile_desc && false !== strpos( $S, '<span class="vhub-atool__desc">' . esc_html( $survey_tile_desc ) . '</span>' ) );
check( 'sidebar: survey tile wording carries the live count', false !== strpos( $S, 'Answer 9 short questions and get guides, tools and recipes picked for you.' ) );
check( 'sidebar: no line repeats the prompt\'s wording', false === strpos( $S, 'points you to the guides' ) && false === strpos( $S, 'Not sure where to start?' ) );
check( 'sidebar: every image has real alt text', ! preg_match( '/<img(?![^>]*\salt="[^"]{5,}")[^>]*>/', $S ) );
check( 'sidebar: tiles card absent with no tiles saved', false === strpos( $S, 'vhub-acard--tiles' ) );
check( 'sidebar: no second related-articles list', false === stripos( $S, 'related' ) );
check( 'sidebar: no forum, poll, votes or member counts', ! preg_match( '/forum|poll|vote|useful\?|members? online|\d+ members/i', wp_strip_all_tags( $S ) ) );

a_live();
$GLOBALS['CATS_BY_POST'][4905] = array( a_cat( 87, 'food-nutrition', 75 ) );
preg_match_all( '/<a class="vhub-atool vhub-atool--([a-z]+)"/', sidebar_html(), $tm );
check( 'sidebar: a food article lists the meal planner first', $tm[1] === array( 'recipes', 'discounts' ) );

a_live();
$GLOBALS['FILTER_RETURNS']['vance_hub_article_second_tool'] = 'survey';
preg_match_all( '/<a class="vhub-atool vhub-atool--([a-z]+)"/', sidebar_html(), $tm );
check( 'sidebar: a second tool equal to the first is swapped, never listed twice', $tm[1] === array( 'survey', 'recipes' ) );

if ( $DL ) {
	a_live();
	$GLOBALS['META'][4905]['_vpd_pdf_file'] = basename( $DL[0] );
	$S2 = sidebar_html();
	check( 'handout: Ask card still renders', false !== strpos( $S2, 'vhub-acard--ask' ) );
	check( 'handout: no tools card', false === strpos( $S2, 'vhub-acard--tools' ) );
}

a_live();
$GLOBALS['MODS']['vance_hub_tile_2_title'] = '   ';
$GLOBALS['MODS']['vance_hub_tile_3_text']  = 'Text with no title';
check( 'tiles: blank titles only -> no card', false === strpos( sidebar_html(), 'vhub-acard--tiles' ) );

a_live();
$GLOBALS['MODS']['vance_hub_tile_1_badge'] = 'Members';
$GLOBALS['MODS']['vance_hub_tile_1_title'] = 'Live Q&A';
$GLOBALS['MODS']['vance_hub_tile_1_text']  = 'Ask the panel';
$GLOBALS['MODS']['vance_hub_tile_1_link']  = 'https://example.test/live/';
$GLOBALS['MODS']['vance_hub_tile_2_title'] = '<b>Bold</b> tile';
$GLOBALS['MODS']['vance_hub_tile_2_link']  = 'javascript:alert(1)';
$S3 = sidebar_html();
check( 'tiles: card renders once a tile has a title', false !== strpos( $S3, '<h4 id="vhub-acard-tiles-title">Inside the Hub this week</h4>' ) );
check( 'tiles: linked tile is a link with its badge, title and text', (bool) preg_match( '/<a class="vhub-atile" href="https:\/\/example\.test\/live\/"><span class="vhub-atile__badge">Members<\/span><span class="vhub-atile__title">Live Q&amp;A<\/span><span class="vhub-atile__text">Ask the panel<\/span><\/a>/', $S3 ) );
check( 'tiles: a javascript: link renders as a plain tile, not a link', false === strpos( $S3, 'javascript:' ) && (bool) preg_match( '/<div class="vhub-atile"><span class="vhub-atile__title">&lt;b&gt;Bold&lt;\/b&gt; tile<\/span><\/div>/', $S3 ) );
check( 'tiles: the cards come in order Ask, tools, tiles', at( $S3, 'vhub-acard--ask' ) < at( $S3, 'vhub-acard--tools' ) && at( $S3, 'vhub-acard--tools' ) < at( $S3, 'vhub-acard--tiles' ) );

$T = vance_hub_article_tools_card_html( array(
	array( 'mod' => 'a"b', 'href' => 'javascript:x', 'title' => '<i>T</i>', 'desc' => '<script>d</script>', 'img' => '', 'alt' => '' ),
) );
check( 'escape: tools card escapes title and desc, drops a javascript: href', false === stripos( $T, '<script' ) && false === strpos( $T, '<i>' ) && false === strpos( $T, 'javascript:' ) && false !== strpos( $T, 'vhub-atool--a&quot;b' ) );
check( 'tools: no rows -> no card', vance_hub_article_tools_card_html( array( array( 'href' => '', 'title' => 'x' ), 'junk' ) ) === '' );
check( 'tools: no image -> no <img>', false === strpos( $T, '<img' ) );
check( 'ask: no URL -> no card', vance_hub_article_ask_card_html( '', true ) === '' );
check( 'tiles: nothing -> no card', vance_hub_article_tiles_card_html( array() ) === '' && vance_hub_article_tiles_card_html( 'x' ) === '' );

// Exactly one h1 is the template's: nothing this file renders adds another.
check( 'outline: no h1 in any new block', ! preg_match( '/<h1\b/i', $S3 . $P ) );

/* ===================================================================== */
section( '8. single.php: the sidebar hook' );

$single = file_get_contents( $THEME . '/single.php' );
$code   = '';
foreach ( token_get_all( $single ) as $tok ) {
	if ( is_array( $tok ) && in_array( $tok[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
		continue;
	}
	$code .= is_array( $tok ) ? $tok[1] : $tok;
}
$aside = at( $code, '<aside class="oped-sidebar">' );
$hook  = at( $code, "do_action( 'vance_article_sidebar_top' )" );
check( 'single.php: the hook is a live call, not a comment', $hook > -1 );
check( 'single.php: the hook is the first thing inside the aside', $aside > -1 && $hook > $aside && '' === trim( preg_replace( '/<\?php\s*$/', '', substr( $code, $aside + strlen( '<aside class="oped-sidebar">' ), $hook - $aside - strlen( '<aside class="oped-sidebar">' ) ) ) ) );
check( 'single.php: called exactly once', substr_count( $code, "'vance_article_sidebar_top'" ) === 1 );
check( 'single.php: still one hero h1', substr_count( $code, '<h1 ' ) === 1 );
// PHP outputs any indentation that stands before an opening tag. The hook line
// must start at column 0, or every article gains whitespace with the layout off.
check( 'single.php: the hook line starts at column 0 (no whitespace output when off)', 1 === preg_match( '/^<\?php do_action\( \'vance_article_sidebar_top\' \);/m', $single ) );

$functions = file_get_contents( $THEME . '/functions.php' );
check( 'functions.php: requires hub-article.php after hub-layout.php', at( $functions, "'/inc/hub-layout.php'" ) > -1 && at( $functions, "'/inc/hub-article.php'" ) > at( $functions, "'/inc/hub-layout.php'" ) );

/* ===================================================================== */
section( '9. Readers that must never see the prompt' );

// These read post_content directly. If one of them ever switches to the
// filtered content, the prompt could reach the AI sources, the citation
// checker or the schema - so that switch must fail here first.
$readers = array( 'askai-functions.php', 'askai-kb.php', 'askai-content-sources.php', 'citation-check.php', 'article-conditions.php', 'medical-schema.php', 'article-schema-types.php', 'social-image.php', 'medical-review.php' );
foreach ( $readers as $f ) {
	$src = file_get_contents( $THEME . '/inc/' . $f );
	check( "reader: inc/$f does not run the the_content filter", false !== $src && ! preg_match( '/apply_filters\(\s*[\'"]the_content[\'"]/', $src ) && false === strpos( $src, 'get_the_content(' ) );
}
check( 'reader: the citation checker reads $post->post_content', (bool) preg_match( '/vance_extract_citations\(\s*\$post->post_content\s*\)/', file_get_contents( $THEME . '/inc/citation-check.php' ) ) );

/* ===================================================================== */
section( '5b. REST (last: a constant cannot be unset)' );

a_live();
check( 'rest: control - the same request adds the prompt before REST_REQUEST is set', run_filter( $C4 ) !== $C4 );
define( 'REST_REQUEST', true );
check( 'rest: nothing added during a REST request', run_filter( $C4 ) === $C4 );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . ' FAILED of ' . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK - $PASS checks passed.\n";
