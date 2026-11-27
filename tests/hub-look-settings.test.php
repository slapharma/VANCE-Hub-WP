<?php
/**
 * Behaviour tests for inc/hub-look-settings.php, rendered through the real
 * templates: template-parts/header-hub.php, front-page-hub.php and the member
 * dashboard home (inc/hub-dashboard.php).
 *
 * Same shape as hub-dashboard.test.php: the stub block of hub-layout.test.php
 * (everything before its first $FRONT_PAGE line) is lifted and evaluated, so
 * every suite runs against one set of WordPress fakes. If that block cannot be
 * found, this suite stops with exit 2 rather than passing on nothing. Extra
 * stubs this suite needs are added below it.
 *
 * The brand colour settings (inc/hub-brand.php) are checked in
 * hub-brand.test.php, not here.
 *
 * WHAT IS COVERED
 *   1  the text reader: empty, spaces, custom, unknown name, non-string values,
 *      markers (a text saved without its marker gives the default), fill
 *   2  escaping through the real templates: header, member bar, home hero,
 *      dashboard greeting, step and welcome line
 *   3  the hero image: standard image, a valid attachment, missing size, no
 *      URL, invalid ids, and the template that prints it
 *   4  the header: the labels and the links for a visitor and a member, the
 *      link sanitiser and reader, a bad stored link
 *   5  the member bar: the switch in the header and on the home page, the Join
 *      block's Founding line, custom texts, the built-in singular and plural
 *   6  the dashboard: greeting, both h1 variants, the welcome rule, the step
 *      label, and the four next steps through vance_hub_dashboard_data()
 *
 * A check whose label says what should happen and which fails on the current
 * code is a finding, not a broken check. The runner lists every failure.
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

// The real esc_url_raw() also tidies characters. These inputs are already
// clean, so the identity is enough for the link checks.
function esc_url_raw( $url, $protocols = null ) { return (string) $url; }

function is_search() { return false; }
function get_search_query() { return ''; }
function has_nav_menu( $location ) { return false; }
function wp_nav_menu( $args = array() ) { return ''; }
function is_page( $page = '' ) { return false; }
function is_page_template( $template = '' ) { return false; }

function get_user_meta( $user_id, $key = '', $single = false ) {
	return isset( $GLOBALS['USER_META'][ $user_id ][ $key ] ) ? $GLOBALS['USER_META'][ $user_id ][ $key ] : '';
}
function vance_dashboard_feature_enabled( $slug ) {
	return array_key_exists( $slug, $GLOBALS['FEATURE_ON'] ) ? (bool) $GLOBALS['FEATURE_ON'][ $slug ] : true;
}
function vance_dashboard_feature_label( $slug ) {
	return isset( $GLOBALS['FEATURE_LABEL'][ $slug ] ) ? $GLOBALS['FEATURE_LABEL'][ $slug ] : '';
}
function vance_get_tool_history( $user_id, $tool_slug, $limit = 0 ) {
	return isset( $GLOBALS['HISTORY'][ $tool_slug ] ) ? $GLOBALS['HISTORY'][ $tool_slug ] : array();
}
function vance_admin_messages_for_user( $user_id, $include_read = false ) {
	return array();
}

// The hero photo. Each call is recorded so a test can show that no call was made.
function wp_get_attachment_image_src( $id, $size = 'thumbnail' ) {
	$GLOBALS['IMG_CALLS'][] = array( $id, $size );
	return array_key_exists( $id, $GLOBALS['IMG_SRC'] ) ? $GLOBALS['IMG_SRC'][ $id ] : false;
}

require_once $THEME . '/inc/hub-dashboard.php';
require_once $THEME . '/inc/hub-header.php';

/* ---- helpers ----------------------------------------------------------------- */

/** Reset every global this suite reads, on top of hub-layout's reset. */
function look_arrange( array $mods = array(), $logged_in = false, $is_front = false, array $get = array() ) {
	arrange( $mods, $is_front, array(), $get );
	$GLOBALS['LOGGED_IN']     = $logged_in;
	$GLOBALS['USER_META']     = array();
	$GLOBALS['HISTORY']       = array();
	$GLOBALS['FEATURE_ON']    = array();
	$GLOBALS['FEATURE_LABEL'] = array();
	$GLOBALS['IMG_CALLS']     = array();
	$GLOBALS['IMG_SRC']       = array();
	$GLOBALS['TEMPLATE']      = '';
	$GLOBALS['SLUG']          = '';
}

/** The header template part, as header.php prints it. */
function look_header_html() {
	ob_start();
	include $GLOBALS['THEME_DIR'] . '/template-parts/header-hub.php';
	return ob_get_clean();
}

/** The hub home template. The member bar prints here only when the header does not. */
function look_home_html() {
	ob_start();
	include $GLOBALS['THEME_DIR'] . '/front-page-hub.php';
	return ob_get_clean();
}

/** The member dashboard home, through the real entry point. */
function look_dashboard_html( $user ) {
	ob_start();
	vance_hub_dashboard_render_home( $user );
	return ob_get_clean();
}

/** A user object for the dashboard. */
function look_user( $id, $display_name, $registered = '2024-03-05 10:00:00' ) {
	$user                  = new WP_User();
	$user->ID              = $id;
	$user->display_name    = $display_name;
	$user->user_registered = $registered;
	return $user;
}

/** A dashboard view with every block empty. */
function look_view( $name, $menu_card = false ) {
	return array(
		'name'           => $name,
		'since'          => '',
		'ask'            => false,
		'step'           => null,
		'tools'          => array(),
		'stats'          => array(),
		'saved'          => array(),
		'saved_label'    => 'Saved articles',
		'saved_href'     => '?tab=reading-list',
		'show_messages'  => false,
		'messages'       => array(),
		'unread'         => 0,
		'messages_label' => 'Messages',
		'messages_href'  => '?tab=messages',
		'fresh'          => array(),
		'menu_card'      => $menu_card,
	);
}

/** Every text row in the registry, for a template that has a live step. */
function look_step_texts() {
	return array(
		'vance_hub_text_dash_survey_title'   => 'Custom survey title',
		'vance_hub_text_dash_survey_text'    => 'Custom survey text.',
		'vance_hub_text_dash_survey_button'  => 'Custom survey button',
		'vance_hub_text_dash_profile_title'  => 'Custom profile title',
		'vance_hub_text_dash_profile_text'   => 'Custom profile text.',
		'vance_hub_text_dash_profile_button' => 'Custom profile button',
		'vance_hub_text_dash_plan_title'     => 'Custom plan title',
		'vance_hub_text_dash_plan_text'      => 'Custom plan text.',
		'vance_hub_text_dash_plan_button'    => 'Custom plan button',
		'vance_hub_text_dash_check_title'    => 'Custom check title',
		'vance_hub_text_dash_check_text'     => 'Custom check text.',
		'vance_hub_text_dash_check_button'   => 'Custom check button',
	);
}

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/* ===================================================================== */
section( '1. The text reader' );

look_arrange();
check( 'registry: there are 24 editable texts', count( vance_hub_look_text_defaults() ) === 24 );
check( 'text: an unknown name gives the empty string', vance_hub_look_text( 'vance_hub_text_nope' ) === '' );
check( 'text: nothing saved gives the header default', vance_hub_look_text( 'vance_hub_text_signin' ) === 'Sign in' );
check( 'text: nothing saved gives the built-in (empty) for a member bar line', vance_hub_look_text( 'vance_hub_text_bar_total' ) === '' );

look_arrange( array( 'vance_hub_text_signin' => '' ) );
check( 'text: a saved empty string gives the default', vance_hub_look_text( 'vance_hub_text_signin' ) === 'Sign in' );

look_arrange( array( 'vance_hub_text_signin' => " \t \n " ) );
check( 'text: a saved whitespace-only string gives the default', vance_hub_look_text( 'vance_hub_text_signin' ) === 'Sign in' );

look_arrange( array( 'vance_hub_text_signin' => '  Log in  ' ) );
check( 'text: a custom text is returned trimmed', vance_hub_look_text( 'vance_hub_text_signin' ) === 'Log in' );

look_arrange( array( 'vance_hub_text_signin' => 'Connexion à mon compte 🔐' ) );
check( 'text: a unicode custom text is returned unchanged', vance_hub_look_text( 'vance_hub_text_signin' ) === 'Connexion à mon compte 🔐' );

look_arrange( array( 'vance_hub_text_signin' => str_repeat( 'a', 300 ) ) );
check( 'text: a 300 character custom text is returned in full', vance_hub_look_text( 'vance_hub_text_signin' ) === str_repeat( 'a', 300 ) );

look_arrange( array( 'vance_hub_text_signin' => array( 'Log in' ) ) );
check( 'text: an array saved in the mod gives the default', vance_hub_look_text( 'vance_hub_text_signin' ) === 'Sign in' );

look_arrange( array( 'vance_hub_text_signin' => 0 ) );
check( 'text: an integer 0 saved in the mod gives the default', vance_hub_look_text( 'vance_hub_text_signin' ) === 'Sign in' );

look_arrange( array( 'vance_hub_text_signin' => false ) );
check( 'text: a saved false gives the default', vance_hub_look_text( 'vance_hub_text_signin' ) === 'Sign in' );

look_arrange( array( 'vance_hub_text_bar_total' => '125 members' ) );
check( 'marker: a member total without {count} gives the built-in (empty)', vance_hub_look_text( 'vance_hub_text_bar_total' ) === '' );

look_arrange( array( 'vance_hub_text_bar_total' => '{COUNT} members' ) );
check( 'marker: the marker is case sensitive, so {COUNT} gives the built-in', vance_hub_look_text( 'vance_hub_text_bar_total' ) === '' );

look_arrange( array( 'vance_hub_text_bar_total' => '{count} members are with us' ) );
check( 'marker: a member total with {count} is kept', vance_hub_look_text( 'vance_hub_text_bar_total' ) === '{count} members are with us' );

look_arrange( array( 'vance_hub_text_bar_week' => 'New this week' ) );
check( 'marker: a week line without {count} gives the built-in (empty)', vance_hub_look_text( 'vance_hub_text_bar_week' ) === '' );

look_arrange( array( 'vance_hub_text_bar_founding' => 'Join by {deadline}' ) );
check( 'marker: a founding line with the wrong marker gives the built-in (empty)', vance_hub_look_text( 'vance_hub_text_bar_founding' ) === '' );

look_arrange( array( 'vance_hub_text_bar_founding' => 'Join by {date}' ) );
check( 'marker: a founding line with {date} is kept', vance_hub_look_text( 'vance_hub_text_bar_founding' ) === 'Join by {date}' );

look_arrange( array( 'vance_hub_text_dash_hello' => 'Hi there' ) );
check( 'marker: a greeting without {name} gives the built-in (empty)', vance_hub_look_text( 'vance_hub_text_dash_hello' ) === '' );

look_arrange( array( 'vance_hub_text_signin' => 'Sign in {count}' ) );
check( 'text: a text with braces and no marker need is kept as typed (the reader does not fill)',vance_hub_look_text( 'vance_hub_text_signin' ) === 'Sign in {count}' );

look_arrange( array( 'vance_hub_text_bar_total' => '{count} members' ) );
check( 'bar: vance_hub_look_bar_text() returns a saved member bar line', vance_hub_look_bar_text( 'vance_hub_text_bar_total' ) === '{count} members' );

look_arrange( array( 'vance_hub_text_signin' => 'Log in' ) );
check( 'bar: vance_hub_look_bar_text() refuses a header text (empty)', vance_hub_look_bar_text( 'vance_hub_text_signin' ) === '' );

check( 'bar: vance_hub_look_bar_text() of an unknown name is empty', vance_hub_look_bar_text( 'vance_hub_text_nope' ) === '' );

check( 'fill: one marker is replaced', vance_hub_look_fill( '{count} members', array( '{count}' => '1,234' ) ) === '1,234 members' );
check( 'fill: text with no marker is returned unchanged', vance_hub_look_fill( 'Founding member', array( '{date}' => '1 Dec' ) ) === 'Founding member' );
check( 'fill: an empty pair list changes nothing', vance_hub_look_fill( '{count} members', array() ) === '{count} members' );
check( 'fill: a marker used twice is replaced twice', vance_hub_look_fill( '{name} and {name}', array( '{name}' => 'Ruth' ) ) === 'Ruth and Ruth' );
check( 'fill: a value that holds a marker is not expanded again', vance_hub_look_fill( '{name}!', array( '{name}' => '{date}', '{date}' => 'X' ) ) === '{date}!' );

look_arrange( array( 'vance_hub_text_bar_founding' => 'Keep the mark until {date}.' ) );
check( 'founding: the line fills {date} with the formatted deadline', vance_hub_look_founding_line( '1 December 2026' ) === 'Keep the mark until 1 December 2026.' );

look_arrange();
check( 'founding: nothing saved gives the empty string (the built-in line)', vance_hub_look_founding_line( '1 December 2026' ) === '' );

look_arrange( array( 'vance_hub_text_bar_founding' => 'Keep the mark.' ) );
check( 'founding: a saved line without {date} gives the empty string', vance_hub_look_founding_line( '1 December 2026' ) === '' );

look_arrange( array( 'vance_hub_text_dash_hello' => 'Good to see you, {name}' ) );
check( 'hello: the greeting fills {name}', vance_hub_look_hello( 'Ruth' ) === 'Good to see you, Ruth' );

look_arrange( array( 'vance_hub_text_dash_hello' => '{name}' ) );
check( 'hello: a greeting that is only the marker, with an empty name, gives the empty string', vance_hub_look_hello( '' ) === '' );

look_arrange();
check( 'hello: nothing saved gives the empty string (the built-in line)', vance_hub_look_hello( 'Ruth' ) === '' );

/* ===================================================================== */
section( '2. Escaping through the real templates' );

look_arrange( array( 'vance_hub_text_signin' => '<script>alert("x")</script> & Go' ), false );
$H = look_header_html();
check( 'escape: a sign-in label with a script tag is printed escaped', strpos( $H, '>&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; &amp; Go</a>' ) !== false );
check( 'escape: no raw script from the label reaches the header', strpos( $H, '<script>alert' ) === false );

look_arrange( array( 'vance_hub_text_search' => '"><script>' ) );
$H = look_header_html();
check( 'escape: a search hint with quotes and a tag is escaped in the placeholder', strpos( $H, 'placeholder="&quot;&gt;&lt;script&gt;"' ) !== false );

look_arrange( array( 'vance_hub_text_bar_link_visitor' => 'Tom & "Jerry" <b>' ), false );
$GLOBALS['USERS_TOTAL'] = 125;
$H = look_header_html();
check( 'escape: a member bar link with &, quotes and a tag is escaped', strpos( $H, '>Tom &amp; &quot;Jerry&quot; &lt;b&gt;' ) !== false );

look_arrange( array( 'vance_hub_text_bar_total' => '<i>{count}</i> & "x"' ) );
$GLOBALS['USERS_TOTAL'] = 125;
$H = look_header_html();
check( 'escape: a member total with markup is escaped in the bar', strpos( $H, '<strong>&lt;i&gt;125&lt;/i&gt; &amp; &quot;x&quot;</strong>' ) !== false );

look_arrange( array( 'vance_hub_text_bar_founding' => 'Join by {date} <em>' ), false, false );
$GLOBALS['MODS']['vance_hub_founding_deadline'] = '2026-12-01';
$H = look_home_html();
check( 'escape: a founding line with a tag is escaped in the bar on the home page', strpos( $H, 'Join by 1 December 2026 &lt;em&gt;' ) !== false );
check( 'escape: a founding line with a tag is escaped in the Join block too', strpos( $H, '<p class="vhub-join__founding">Join by 1 December 2026 &lt;em&gt;</p>' ) !== false );

look_arrange( array( 'vance_hub_hero_image' => 9001 ) );
$GLOBALS['IMG_SRC'][9001] = array( 'https://example.test/wp-content/uploads/hero.jpg', 1024, 768, false );
$GLOBALS['META'][9001]['_wp_attachment_image_alt'] = '"><script>x</script> & Ruth';
$H = look_home_html();
check( 'escape: a hero alt with quotes and a tag is escaped as an attribute', strpos( $H, 'alt="&quot;&gt;&lt;script&gt;x&lt;/script&gt; &amp; Ruth"' ) !== false );
check( 'escape: no raw script from the hero alt reaches the home page', strpos( $H, '<script>x</script>' ) === false );

look_arrange( array( 'vance_hub_text_dash_hello' => 'Hi {name} & "friends"' ) );
$D = look_view( '<b>Ruth</b>', true );
$H = vance_hub_dashboard_home_html( $D );
check( 'escape: a greeting with a name holding markup and a custom text is escaped', strpos( $H, '<h1 class="vhd-sr">Hi &lt;b&gt;Ruth&lt;/b&gt; &amp; &quot;friends&quot;</h1>' ) !== false );

look_arrange( array( 'vance_hub_text_dash_survey_title' => '<i>Start</i> & go' ) );
$D          = look_view( 'Ruth' );
$D['step']  = array( 'title' => vance_hub_look_text( 'vance_hub_text_dash_survey_title' ), 'text' => 'x', 'cta' => 'Go', 'href' => 'https://example.test/t/' );
$H          = vance_hub_dashboard_home_html( $D );
check( 'escape: a next-step title saved with markup is escaped', strpos( $H, '<h2 id="vhd-step-title" class="vhd-step__title">&lt;i&gt;Start&lt;/i&gt; &amp; go</h2>' ) !== false );

look_arrange( array( 'vance_hub_text_dash_welcome' => 'Hi <script>' ) );
$H = vance_hub_dashboard_member_card_html( array( 'name' => 'Ruth Adeyemi', 'first' => 'Ruth', 'since' => '', 'code' => '' ) );
check( 'escape: a welcome line with a tag is escaped in the side menu card', strpos( $H, '<span class="vhdt-member__label">Hi &lt;script&gt;</span>' ) !== false );

look_arrange();
$H = vance_hub_dashboard_member_card_html( array( 'name' => 'Ruth', 'first' => '<i>Ruth', 'since' => '', 'code' => '' ) );
check( 'escape: a built-in welcome with a first name holding a tag is escaped', strpos( $H, '<span class="vhdt-member__label">Welcome back &lt;i&gt;Ruth</span>' ) !== false );

/* ===================================================================== */
section( '3. The hero image' );

look_arrange();
$STANDARD = vance_hub_hero_image();
check( 'hero: nothing saved gives the theme photo src with its version', $STANDARD['src'] === 'https://example.test/wp-content/themes/vance-health-hub/assets/img/about/community-support.jpg?v=' . filemtime( $THEME . '/assets/img/about/community-support.jpg' ) );
check( 'hero: nothing saved gives the standard alt text', $STANDARD['alt'] === 'Five people sit in a circle of chairs and talk; one woman holds a clipboard' );
check( 'hero: nothing saved gives 800 wide', $STANDARD['width'] === 800 );
check( 'hero: nothing saved gives 650 high', $STANDARD['height'] === 650 );
check( 'hero: nothing saved asks the media library for nothing', $GLOBALS['IMG_CALLS'] === array() );

look_arrange( array( 'vance_hub_hero_image' => 0 ) );
check( 'hero: a saved 0 is the standard image too', vance_hub_hero_image() === $STANDARD );

$STD_ARRAY = array( 'src' => 'theme.jpg', 'alt' => 'Standard alt', 'width' => 800, 'height' => 650 );
check( 'apply: id 0 returns the standard array unchanged', vance_hub_hero_image_apply( 0, $STD_ARRAY ) === $STD_ARRAY );
check( 'apply: a negative id returns the standard array unchanged', vance_hub_hero_image_apply( -5, $STD_ARRAY ) === $STD_ARRAY );
look_arrange();
vance_hub_hero_image_apply( -5, $STD_ARRAY );
check( 'apply: a negative id makes no media library call', $GLOBALS['IMG_CALLS'] === array() );

look_arrange();
vance_hub_hero_image_apply( 0, $STD_ARRAY );
check( 'apply: id 0 makes no media library call', $GLOBALS['IMG_CALLS'] === array() );

look_arrange( array( 'vance_hub_hero_image' => 'abc' ) );
check( 'hero: a non-numeric saved id gives the standard image', vance_hub_hero_image() === $STANDARD && $GLOBALS['IMG_CALLS'] === array() );

look_arrange( array( 'vance_hub_hero_image' => '9001' ) );
$GLOBALS['IMG_SRC'][9001] = array( 'https://example.test/wp-content/uploads/hero.jpg', 1024, 768, false );
$GLOBALS['META'][9001]['_wp_attachment_image_alt'] = 'A woman draws a plan on a pad';
check( 'hero: a saved id given as a string is read as the id', vance_hub_hero_image()['src'] === 'https://example.test/wp-content/uploads/hero.jpg' );

look_arrange( array( 'vance_hub_hero_image' => 9001 ) );
$GLOBALS['IMG_SRC'][9001] = array( 'https://example.test/wp-content/uploads/hero-large.jpg', 1024, 768, false );
$GLOBALS['META'][9001]['_wp_attachment_image_alt'] = 'A woman draws a plan on a pad';
$HERO = vance_hub_hero_image();
check( 'hero: a valid attachment gives its large size URL', $HERO['src'] === 'https://example.test/wp-content/uploads/hero-large.jpg' );
check( 'hero: a valid attachment gives its own alt text', $HERO['alt'] === 'A woman draws a plan on a pad' );
check( 'hero: a valid attachment gives its own width', $HERO['width'] === 1024 );
check( 'hero: a valid attachment gives its own height', $HERO['height'] === 768 );
check( 'hero: the media library is asked for the large size of that id', $GLOBALS['IMG_CALLS'] === array( array( 9001, 'large' ) ) );

look_arrange( array( 'vance_hub_hero_image' => 9001 ) );
$GLOBALS['IMG_SRC'][9001] = array( 'https://example.test/wp-content/uploads/hero-large.jpg', 1024, 768, false );
$GLOBALS['META'][9001]['_wp_attachment_image_alt'] = '';
$HERO = vance_hub_hero_image();
check( 'hero: an attachment with an empty alt gives an empty alt, not the standard one', $HERO['alt'] === '' );

look_arrange( array( 'vance_hub_hero_image' => 9001 ) );
$GLOBALS['IMG_SRC'][9001] = array( 'https://example.test/wp-content/uploads/hero-large.jpg', 1024, 768, false );
$GLOBALS['META'][9001]['_wp_attachment_image_alt'] = "   \t ";
check( 'hero: an attachment with a whitespace-only alt gives an empty alt', vance_hub_hero_image()['alt'] === '' );

look_arrange( array( 'vance_hub_hero_image' => 9001 ) );
$GLOBALS['IMG_SRC'][9001] = array( 'https://example.test/wp-content/uploads/hero-large.jpg', 0, 768, false );
check( 'hero: a width of 0 takes the standard 800', vance_hub_hero_image()['width'] === 800 );

look_arrange( array( 'vance_hub_hero_image' => 9001 ) );
$GLOBALS['IMG_SRC'][9001] = array( 'https://example.test/wp-content/uploads/hero-large.jpg', 1024 );
check( 'hero: a missing height takes the standard 650', vance_hub_hero_image()['height'] === 650 );
check( 'hero: a missing height keeps the attachment width', vance_hub_hero_image()['width'] === 1024 );

look_arrange( array( 'vance_hub_hero_image' => 9001 ) );
$GLOBALS['IMG_SRC'][9001] = array( 'https://example.test/wp-content/uploads/hero-large.jpg', 'wide', -10, false );
$HERO = vance_hub_hero_image();
check( 'hero: a non-numeric width and a negative height take the standard numbers', $HERO['width'] === 800 && $HERO['height'] === 650 );

look_arrange( array( 'vance_hub_hero_image' => 9001 ) );
$GLOBALS['IMG_SRC'][9001] = false;
check( 'hero: an attachment that does not exist gives the standard array', vance_hub_hero_image() === $STANDARD );

look_arrange( array( 'vance_hub_hero_image' => 9001 ) );
$GLOBALS['IMG_SRC'][9001] = array( '', 1024, 768, false );
check( 'hero: an attachment with an empty URL gives the standard array', vance_hub_hero_image() === $STANDARD );

look_arrange( array( 'vance_hub_hero_image' => 9001 ) );
$GLOBALS['IMG_SRC'][9001] = array( null, 1024, 768, false );
check( 'hero: an attachment whose URL is not a string gives the standard array', vance_hub_hero_image() === $STANDARD );

look_arrange( array( 'vance_hub_hero_image' => 9001 ) );
$GLOBALS['IMG_SRC'][9001] = 'https://example.test/not-an-array.jpg';
check( 'hero: a media result that is not an array gives the standard array', vance_hub_hero_image() === $STANDARD );

look_arrange( array( 'vance_hub_hero_image' => 9001 ) );
$GLOBALS['IMG_SRC'][9001] = array( 'https://example.test/wp-content/uploads/hero-large.jpg', 1024, 768, false );
$GLOBALS['META'][9001]['_wp_attachment_image_alt'] = 'A woman draws a plan on a pad';
$H = look_home_html();
check( 'hero: the home page prints the attachment with its size and alt', strpos( $H, '<img class="vhub-hero__img" src="https://example.test/wp-content/uploads/hero-large.jpg" width="1024" height="768" alt="A woman draws a plan on a pad">' ) !== false );

look_arrange( array( 'vance_hub_hero_image' => 9001 ) );
$GLOBALS['IMG_SRC'][9001] = array( 'https://example.test/wp-content/uploads/hero-large.jpg', 1024, 768, false );
$GLOBALS['META'][9001]['_wp_attachment_image_alt'] = '';
$H = look_home_html();
check( 'hero: with an empty attachment alt the home page never prints the standard alt', strpos( $H, 'Five people sit in a circle' ) === false );

look_arrange();
$H = look_home_html();
check( 'hero: nothing saved, the home page prints the standard alt', strpos( $H, 'alt="Five people sit in a circle of chairs and talk; one woman holds a clipboard"' ) !== false );
check( 'hero: nothing saved, the home page prints 800 by 650', strpos( $H, 'width="800" height="650"' ) !== false );

/* ===================================================================== */
section( '4. The header: labels, links and the bad stored link' );

look_arrange( array(), false );
$H = look_header_html();
check( 'header: a visitor sees the default Sign in button link', strpos( $H, 'vhh-actions__signin" href="https://example.test/login/">Sign in</a>' ) !== false );
check( 'header: a visitor sees the default Join free link', strpos( $H, 'class="vhh-btn vhh-btn--purple" href="https://example.test/login/?tab=signup">Join free</a>' ) !== false );
check( 'header: a visitor sees the default search hint', strpos( $H, 'placeholder="Search articles, tools, recipes..."' ) !== false );
check( 'header: a visitor does not see My Hub', strpos( $H, 'My Hub' ) === false );

look_arrange( array(), true );
$H = look_header_html();
check( 'header: a member sees the default My Hub link', strpos( $H, 'class="vhh-btn vhh-btn--purple" href="https://example.test/dashboard/">My Hub</a>' ) !== false );
check( 'header: a member does not see Sign in', strpos( $H, 'Sign in' ) === false );
check( 'header: a member does not see Join free', strpos( $H, 'Join free' ) === false );

look_arrange( array(
	'vance_hub_text_signin'   => 'Log in',
	'vance_hub_text_join'     => 'Create account',
	'vance_hub_text_myhub'    => 'Dashboard',
	'vance_hub_text_search'   => 'Find a topic',
	'vance_hub_link_signin'   => '/member-login/',
	'vance_hub_link_join'     => 'https://example.org/join',
	'vance_hub_link_myhub'    => '/my-area/',
), false );
$H = look_header_html();
check( 'header: the custom Sign in label and path are used', strpos( $H, 'vhh-actions__signin" href="https://example.test/member-login/">Log in</a>' ) !== false );
check( 'header: the custom Join label and full address are used', strpos( $H, 'class="vhh-btn vhh-btn--purple" href="https://example.org/join">Create account</a>' ) !== false );
check( 'header: the custom search hint is used', strpos( $H, 'placeholder="Find a topic"' ) !== false );

look_arrange( array(
	'vance_hub_text_myhub'  => 'Dashboard',
	'vance_hub_link_myhub'  => '/my-area/',
), true );
$H = look_header_html();
check( 'header: the custom My Hub label and path are used for a member', strpos( $H, 'class="vhh-btn vhh-btn--purple" href="https://example.test/my-area/">Dashboard</a>' ) !== false );

look_arrange( array( 'vance_hub_text_signin' => '   ' ), false );
$H = look_header_html();
check( 'header: a blank saved label falls back to Sign in', strpos( $H, 'vhh-actions__signin" href="https://example.test/login/">Sign in</a>' ) !== false );

// Link shape: the pure check. Each row is one input.
check( 'shape: javascript: is refused', vance_hub_look_link_shape( 'javascript:alert(1)' ) === '' );
check( 'shape: data: is refused', vance_hub_look_link_shape( 'data:text/html,hi' ) === '' );
check( 'shape: mailto: is refused', vance_hub_look_link_shape( 'mailto:someone@example.test' ) === '' );
check( 'shape: a protocol-relative address is refused', vance_hub_look_link_shape( '//evil.example' ) === '' );
check( 'shape: a slash then a backslash is refused', vance_hub_look_link_shape( '/\\evil.example' ) === '' );
check( 'shape: a backslash anywhere is refused', vance_hub_look_link_shape( 'a\\b' ) === '' );
check( 'shape: a newline inside the link is refused', vance_hub_look_link_shape( "/log\nin/" ) === '' );
check( 'shape: a space inside the link is refused', vance_hub_look_link_shape( 'my link' ) === '' );
check( 'shape: a bare word is refused', vance_hub_look_link_shape( 'login' ) === '' );
check( 'shape: http:// with no host is refused', vance_hub_look_link_shape( 'http://' ) === '' );
check( 'shape: https:// with no host is refused', vance_hub_look_link_shape( 'https://' ) === '' );
check( 'shape: a host that is empty before the path is refused', vance_hub_look_link_shape( 'https:///x' ) === '' );
check( 'shape: a site path is accepted', vance_hub_look_link_shape( '/login/' ) === '/login/' );
check( 'shape: a site path with a query is accepted', vance_hub_look_link_shape( '/login/?tab=signup' ) === '/login/?tab=signup' );
check( 'shape: a single slash is accepted', vance_hub_look_link_shape( '/' ) === '/' );
check( 'shape: https is accepted', vance_hub_look_link_shape( 'https://example.org/join' ) === 'https://example.org/join' );
check( 'shape: http is accepted', vance_hub_look_link_shape( 'http://example.org' ) === 'http://example.org' );
check( 'shape: an upper-case scheme is accepted', vance_hub_look_link_shape( 'HTTPS://EXAMPLE.ORG' ) === 'HTTPS://EXAMPLE.ORG' );
check( 'shape: surrounding spaces are trimmed before the check', vance_hub_look_link_shape( '  /login/  ' ) === '/login/' );
check( 'shape: a null is refused', vance_hub_look_link_shape( null ) === '' );
check( 'shape: a number is refused', vance_hub_look_link_shape( 123 ) === '' );
check( 'shape: an array is refused', vance_hub_look_link_shape( array( '/login/' ) ) === '' );
check( 'shape: the empty string is refused', vance_hub_look_link_shape( '' ) === '' );

// Sanitiser: the same rules before a value is saved.
check( 'sanitise: javascript: is not saved', vance_hub_sanitize_link( 'javascript:alert(1)' ) === '' );
check( 'sanitise: a protocol-relative address is not saved', vance_hub_sanitize_link( '//evil.example' ) === '' );
check( 'sanitise: a backslash path is not saved', vance_hub_sanitize_link( '/\\evil.example' ) === '' );
check( 'sanitise: a valid site path is saved', vance_hub_sanitize_link( '/login/?tab=signup' ) === '/login/?tab=signup' );
check( 'sanitise: a valid https address is saved', vance_hub_sanitize_link( 'https://example.org/join' ) === 'https://example.org/join' );
check( 'sanitise: a non-string is not saved', vance_hub_sanitize_link( array( 'https://example.org' ) ) === '' );

// The reader: a saved link, or today's address.
look_arrange();
check( 'link: nothing saved gives the sign-in address', vance_hub_look_link( 'signin' ) === 'https://example.test/login/' );
check( 'link: nothing saved gives the join address', vance_hub_look_link( 'join' ) === 'https://example.test/login/?tab=signup' );
check( 'link: nothing saved gives the My Hub address', vance_hub_look_link( 'myhub' ) === 'https://example.test/dashboard/' );
check( 'link: an unknown name gives the empty string', vance_hub_look_link( 'nope' ) === '' );

look_arrange( array( 'vance_hub_link_signin' => 'javascript:alert(1)' ) );
check( 'link: a bad value already stored gives the default at read time', vance_hub_look_link( 'signin' ) === 'https://example.test/login/' );

look_arrange( array( 'vance_hub_link_signin' => '//evil.example' ) );
check( 'link: a stored protocol-relative address gives the default', vance_hub_look_link( 'signin' ) === 'https://example.test/login/' );

look_arrange( array( 'vance_hub_link_join' => '/\\evil.example' ) );
check( 'link: a stored backslash path gives the default', vance_hub_look_link( 'join' ) === 'https://example.test/login/?tab=signup' );

look_arrange( array( 'vance_hub_link_join' => "/log\nin/" ) );
check( 'link: a stored value with a newline gives the default', vance_hub_look_link( 'join' ) === 'https://example.test/login/?tab=signup' );

look_arrange( array( 'vance_hub_link_join' => 'data:text/html,hi' ) );
check( 'link: a stored data: address gives the default', vance_hub_look_link( 'join' ) === 'https://example.test/login/?tab=signup' );

look_arrange( array( 'vance_hub_link_join' => array( 'https://example.org' ) ) );
check( 'link: a stored array gives the default', vance_hub_look_link( 'join' ) === 'https://example.test/login/?tab=signup' );

look_arrange( array( 'vance_hub_link_signin' => '/member-login/' ) );
check( 'link: a stored site path goes through home_url()', vance_hub_look_link( 'signin' ) === 'https://example.test/member-login/' );

look_arrange( array( 'vance_hub_link_signin' => 'https://example.org/member' ) );
check( 'link: a stored full address is kept as it is', vance_hub_look_link( 'signin' ) === 'https://example.org/member' );

look_arrange( array( 'vance_hub_link_signin' => 'javascript:alert(1)' ), false );
$H = look_header_html();
check( 'header: a bad stored sign-in link renders the default href', strpos( $H, 'vhh-actions__signin" href="https://example.test/login/">' ) !== false );
check( 'header: a bad stored sign-in link never reaches the page', strpos( $H, 'javascript:' ) === false );

/* ===================================================================== */
section( '5. The member bar and the switch' );

// The switch, in the header part: counts mode and founding mode, four stored values.
look_arrange( array( 'vance_hub_member_bar' => false ), false );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'switch: false, counts mode: the header part prints no member bar', strpos( look_header_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => '0' ), false );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'switch: "0", counts mode: the header part prints no member bar', strpos( look_header_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => 0 ), false );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'switch: 0, counts mode: the header part prints no member bar', strpos( look_header_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => '' ), false );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'switch: empty, counts mode: the header part prints no member bar', strpos( look_header_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => false, 'vance_hub_founding_deadline' => '2026-12-01' ), false );
check( 'switch: false, founding mode: the header part prints no member bar', strpos( look_header_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => '0', 'vance_hub_founding_deadline' => '2026-12-01' ), false );
check( 'switch: "0", founding mode: the header part prints no member bar', strpos( look_header_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => 0, 'vance_hub_founding_deadline' => '2026-12-01' ), false );
check( 'switch: 0, founding mode: the header part prints no member bar', strpos( look_header_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => '', 'vance_hub_founding_deadline' => '2026-12-01' ), false );
check( 'switch: empty, founding mode: the header part prints no member bar', strpos( look_header_html(), '<div class="vhub-bar">' ) === false );

// The switch, on the home page (the header is not active, so the page prints the bar).
look_arrange( array( 'vance_hub_member_bar' => false ), false, true );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'switch: false, counts mode: the home page prints no member bar', strpos( look_home_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => '0' ), false, true );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'switch: "0", counts mode: the home page prints no member bar', strpos( look_home_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => 0 ), false, true );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'switch: 0, counts mode: the home page prints no member bar', strpos( look_home_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => '' ), false, true );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'switch: empty, counts mode: the home page prints no member bar', strpos( look_home_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => false, 'vance_hub_founding_deadline' => '2026-12-01' ), false, true );
check( 'switch: false, founding mode: the home page prints no member bar', strpos( look_home_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => '0', 'vance_hub_founding_deadline' => '2026-12-01' ), false, true );
check( 'switch: "0", founding mode: the home page prints no member bar', strpos( look_home_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => 0, 'vance_hub_founding_deadline' => '2026-12-01' ), false, true );
check( 'switch: 0, founding mode: the home page prints no member bar', strpos( look_home_html(), '<div class="vhub-bar">' ) === false );

look_arrange( array( 'vance_hub_member_bar' => '', 'vance_hub_founding_deadline' => '2026-12-01' ), false, true );
check( 'switch: empty, founding mode: the home page prints no member bar', strpos( look_home_html(), '<div class="vhub-bar">' ) === false );

// The switch on: the bar shows.
look_arrange( array( 'vance_hub_member_bar' => true ), false );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'switch: on, counts mode: the header part prints the bar', strpos( look_header_html(), '<div class="vhub-bar">' ) !== false );

look_arrange( array( 'vance_hub_member_bar' => '1' ), false );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'switch: "1", counts mode: the header part prints the bar', strpos( look_header_html(), '<div class="vhub-bar">' ) !== false );

// The Join block keeps its Founding line while the bar is off.
look_arrange( array( 'vance_hub_member_bar' => false, 'vance_hub_founding_deadline' => '2026-12-01' ), false, true );
$GLOBALS['USERS_TOTAL'] = 0;
$H = look_home_html();
check( 'switch: off, the Join block still prints its Founding Member line', strpos( $H, '<p class="vhub-join__founding">Join by 1 December 2026 and keep the Founding Member mark.</p>' ) !== false );
check( 'switch: off, the Join block line is printed and no bar is', strpos( $H, '<div class="vhub-bar">' ) === false );

// Custom texts with markers.
look_arrange( array( 'vance_hub_text_bar_total' => '{count} people are with us' ), false );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'custom: a member total with {count} shows the filled text', strpos( look_header_html(), '<strong>125 people are with us</strong>' ) !== false );

look_arrange( array( 'vance_hub_text_bar_total' => 'Members only' ), false );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'custom: a member total without {count} shows the built-in line', strpos( look_header_html(), '<strong>125 members</strong>' ) !== false );

look_arrange( array( 'vance_hub_text_bar_week' => '{count} new since Monday' ), false );
$GLOBALS['USERS_TOTAL'] = 125;
$GLOBALS['USERS_WEEK']  = 12;
check( 'custom: a week line with {count} shows the filled text', strpos( look_header_html(), '12 new since Monday' ) !== false );

look_arrange( array( 'vance_hub_text_bar_week' => '{count} new since Monday' ), false );
$GLOBALS['USERS_TOTAL'] = 125;
$GLOBALS['USERS_WEEK']  = 0;
check( 'custom: a week line is not shown at all when nobody joined this week', strpos( look_header_html(), 'new since Monday' ) === false );

look_arrange( array( 'vance_hub_text_bar_link_visitor' => 'Learn more' ), false );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'custom: the visitor link text is used', strpos( look_header_html(), 'class="vhub-bar__link" href="https://example.test/login/?tab=signup">Learn more' ) !== false );

look_arrange( array( 'vance_hub_text_bar_link_member' => 'Open my hub' ), true );
$GLOBALS['USERS_TOTAL'] = 125;
check( 'custom: the member link text and the dashboard address are used', strpos( look_header_html(), 'class="vhub-bar__link" href="https://example.test/dashboard/">Open my hub' ) !== false );

// The founding line is the same text in the bar and in the Join block.
look_arrange( array( 'vance_hub_text_bar_founding' => 'Founding places end {date}.', 'vance_hub_founding_deadline' => '2026-12-01' ), false, true );
$GLOBALS['USERS_TOTAL'] = 0;
$H = look_home_html();
check( 'founding: the custom line shows in the bar on the home page', strpos( $H, '<p class="vhub-bar__text">Founding places end 1 December 2026.' ) !== false );
check( 'founding: the same custom line shows in the Join block', strpos( $H, '<p class="vhub-join__founding">Founding places end 1 December 2026.</p>' ) !== false );
check( 'founding: the custom line appears exactly twice on the home page (bar and Join block)', substr_count( $H, 'Founding places end 1 December 2026.' ) === 2 );

look_arrange( array( 'vance_hub_founding_deadline' => '2026-12-01' ), false, false );
$GLOBALS['USERS_TOTAL'] = 0;
$H = look_header_html();
check( 'founding: nothing saved, the header bar shows the built-in founding line', strpos( $H, '<p class="vhub-bar__text">Join by 1 December 2026 and keep the Founding Member mark.</p>' ) !== false );

// Built-in singular and plural, nothing saved. The threshold is lowered so one member shows counts.
look_arrange( array( 'vance_hub_member_min' => 1 ), false );
$GLOBALS['USERS_TOTAL'] = 1;
check( 'built-in: one member reads "1 member"', strpos( look_header_html(), '<strong>1 member</strong>' ) !== false );

look_arrange( array( 'vance_hub_member_min' => 1 ), false );
$GLOBALS['USERS_TOTAL'] = 2;
check( 'built-in: two members read "2 members"', strpos( look_header_html(), '<strong>2 members</strong>' ) !== false );

look_arrange( array( 'vance_hub_member_min' => 1 ), false );
$GLOBALS['USERS_TOTAL'] = 2;
$GLOBALS['USERS_WEEK']  = 1;
check( 'built-in: one new member reads "1 joined this week"', strpos( look_header_html(), '1 joined this week' ) !== false );

look_arrange( array( 'vance_hub_member_min' => 1 ), false );
$GLOBALS['USERS_TOTAL'] = 2;
$GLOBALS['USERS_WEEK']  = 2;
check( 'built-in: two new members read "2 joined this week"', strpos( look_header_html(), '2 joined this week' ) !== false );

look_arrange( array(), false );
$GLOBALS['USERS_TOTAL'] = 125;
$GLOBALS['USERS_WEEK']  = 12;
$H = look_header_html();
check( 'built-in: 125 members with 12 new reads "125 members" and "12 joined this week"', strpos( $H, '<strong>125 members</strong>' ) !== false && strpos( $H, '12 joined this week' ) !== false );

look_arrange( array( 'vance_hub_member_bar' => true ), true );
$GLOBALS['USERS_TOTAL'] = 125;
$H = look_header_html();
check( 'built-in: a member sees the bar with the built-in My Hub link text', strpos( $H, 'class="vhub-bar__link" href="https://example.test/dashboard/">Go to My Hub' ) !== false );

/* ===================================================================== */
section( '6. The member dashboard home' );

look_arrange( array( 'vance_hub_text_dash_hello' => 'Good to see you, {name}' ) );
$H = vance_hub_dashboard_home_html( look_view( 'Ruth' ) );
check( 'greeting: a custom greeting with {name} fills the page heading', strpos( $H, '<h1 id="vhd-hello-title" class="vhd-hello__title">Good to see you, Ruth</h1>' ) !== false );

look_arrange( array( 'vance_hub_text_dash_hello' => 'Good to see you' ) );
$H = vance_hub_dashboard_home_html( look_view( 'Ruth' ) );
check( 'greeting: a custom greeting without {name} is not used, the built-in shows', strpos( $H, '<h1 id="vhd-hello-title" class="vhd-hello__title">Hello, Ruth</h1>' ) !== false );

look_arrange();
$H = vance_hub_dashboard_home_html( look_view( 'Ruth' ) );
check( 'greeting: nothing saved, the built-in greeting shows', strpos( $H, '>Hello, Ruth</h1>' ) !== false );

look_arrange( array( 'vance_hub_text_dash_hello' => '   ' ) );
$H = vance_hub_dashboard_home_html( look_view( 'Ruth' ) );
check( 'greeting: a blank saved greeting falls back to the built-in', strpos( $H, '>Hello, Ruth</h1>' ) !== false );

look_arrange();
$H = vance_hub_dashboard_home_html( look_view( '<b>Ruth</b>' ) );
check( 'greeting: a name with markup is escaped in the built-in greeting', strpos( $H, '>Hello, &lt;b&gt;Ruth&lt;/b&gt;</h1>' ) !== false );

look_arrange( array( 'vance_hub_text_dash_hello' => 'Hi {name}' ) );
$H = vance_hub_dashboard_home_html( look_view( 'Ruth', true ) );
check( 'greeting: with the side menu card the page keeps a screen reader heading with the custom text', strpos( $H, '<h1 class="vhd-sr">Hi Ruth</h1>' ) !== false );

look_arrange();
$H = vance_hub_dashboard_home_html( look_view( 'Ruth', true ) );
check( 'greeting: the side menu variant prints the visually hidden heading', strpos( $H, '<h1 class="vhd-sr">Hello, Ruth</h1>' ) !== false );
check( 'greeting: the side menu variant prints no visible greeting section', strpos( $H, 'vhd-hello__copy' ) === false );

look_arrange();
$H = vance_hub_dashboard_home_html( look_view( 'Ruth', false ) );
check( 'greeting: the classic variant prints the visible greeting section', strpos( $H, '<section class="vhd-hello" aria-labelledby="vhd-hello-title">' ) !== false );

// The welcome rule, through the real card builder and its HTML.
look_arrange();
$CARD_FIRST = array( 'name' => 'Ruth Adeyemi', 'first' => 'Ruth', 'since' => '', 'code' => '' );
$CARD_NONE  = array( 'name' => 'ruth@example.test', 'first' => '', 'since' => '', 'code' => '' );
check( 'welcome: nothing saved, a member with a first name reads "Welcome back Ruth"', strpos( vance_hub_dashboard_member_card_html( $CARD_FIRST ), '<span class="vhdt-member__label">Welcome back Ruth</span>' ) !== false );
check( 'welcome: nothing saved, a member with no first name reads "Welcome back"', strpos( vance_hub_dashboard_member_card_html( $CARD_NONE ), '<span class="vhdt-member__label">Welcome back</span>' ) !== false );

// Table of the pure rule: template, first name, expected line.
check( 'welcome rule: a first name fills {name}', vance_hub_look_welcome_apply( 'Welcome back, {name}!', 'Ruth' ) === 'Welcome back, Ruth!' );
check( 'welcome rule: "Welcome back, {name}!" with no first name gives "Welcome back!"', vance_hub_look_welcome_apply( 'Welcome back, {name}!', '' ) === 'Welcome back!' );
check( 'welcome rule: a first name of spaces counts as no first name', vance_hub_look_welcome_apply( 'Welcome back, {name}!', '   ' ) === 'Welcome back!' );
check( 'welcome rule: "Welcome back {name}" with no first name gives "Welcome back"', vance_hub_look_welcome_apply( 'Welcome back {name}', '' ) === 'Welcome back' );
check( 'welcome rule: a semicolon before the marker is removed with it', vance_hub_look_welcome_apply( 'Welcome back; {name}!', '' ) === 'Welcome back!' );
check( 'welcome rule: a colon before the marker is removed with it', vance_hub_look_welcome_apply( 'Welcome back: {name}', '' ) === 'Welcome back' );
check( 'welcome rule: a comma and a tab run before the marker are removed with it', vance_hub_look_welcome_apply( "Welcome back,\t\t{name}!", '' ) === 'Welcome back!' );
check( 'welcome rule: a template that is only the marker gives the empty string', vance_hub_look_welcome_apply( '{name}', '' ) === '' );
check( 'welcome rule: a template that is only the marker with a first name gives the name', vance_hub_look_welcome_apply( '{name}', 'Ruth' ) === 'Ruth' );
check( 'welcome rule: a template with no marker is kept as it is with a first name', vance_hub_look_welcome_apply( 'Good to see you', 'Ruth' ) === 'Good to see you' );
check( 'welcome rule: a template with no marker is kept as it is with no first name', vance_hub_look_welcome_apply( 'Good to see you', '' ) === 'Good to see you' );
check( 'welcome rule: a dash after the marker stays where it is', vance_hub_look_welcome_apply( 'Welcome - {name}', 'Ruth' ) === 'Welcome - Ruth' );

look_arrange( array( 'vance_hub_text_dash_welcome' => 'Welcome back, {name}!' ) );
$H = vance_hub_dashboard_member_card_html( $CARD_FIRST );
check( 'welcome: a saved line with a first name shows the filled line in the card', strpos( $H, '<span class="vhdt-member__label">Welcome back, Ruth!</span>' ) !== false );
$H = vance_hub_dashboard_member_card_html( $CARD_NONE );
check( 'welcome: a saved line with no first name shows the line without the name', strpos( $H, '<span class="vhdt-member__label">Welcome back!</span>' ) !== false );

look_arrange( array( 'vance_hub_text_dash_welcome' => 'Good to see you' ) );
$H = vance_hub_dashboard_member_card_html( $CARD_FIRST );
check( 'welcome: a saved line with no marker does not name the member', strpos( $H, '<span class="vhdt-member__label">Good to see you</span>' ) !== false );

look_arrange( array( 'vance_hub_text_dash_welcome' => '{name}' ) );
$H = vance_hub_dashboard_member_card_html( $CARD_NONE );
check( 'welcome: a saved line that reduces to empty falls back to the built-in "Welcome back"', strpos( $H, '<span class="vhdt-member__label">Welcome back</span>' ) !== false );
$H = vance_hub_dashboard_member_card_html( $CARD_FIRST );
check( 'welcome: a saved "{name}" with a first name shows the first name', strpos( $H, '<span class="vhdt-member__label">Ruth</span>' ) !== false );

// Member card data: a name that is an email address is never a greeting.
look_arrange();
$USER_EMAIL = look_user( 9, 'ruth@example.test' );
$CARD       = vance_hub_dashboard_member_card_data( $USER_EMAIL );
check( 'welcome: a display name that is an email address gives no first name', $CARD['first'] === '' );
look_arrange( array( 'vance_hub_text_dash_welcome' => 'Welcome back, {name}!' ) );
$H = vance_hub_dashboard_member_card_html( $CARD );
check( 'welcome: an email display name with a saved "{name}" line shows the line without a name', strpos( $H, '<span class="vhdt-member__label">Welcome back!</span>' ) !== false );

// Step label.
look_arrange( array( 'vance_hub_text_dash_step_label' => 'Up next' ) );
$D         = look_view( 'Ruth' );
$D['step'] = array( 'title' => 'T', 'text' => 'x', 'cta' => 'Go', 'href' => 'https://example.test/t/' );
$H         = vance_hub_dashboard_home_html( $D );
check( 'step label: a custom label is printed above the next step', strpos( $H, '<p class="vhd-eyebrow vhd-eyebrow--light">Up next</p>' ) !== false );

look_arrange( array( 'vance_hub_text_dash_step_label' => '' ) );
$H = vance_hub_dashboard_home_html( $D );
check( 'step label: a blank saved label falls back to "Your next step"', strpos( $H, '<p class="vhd-eyebrow vhd-eyebrow--light">Your next step</p>' ) !== false );

look_arrange();
$H = vance_hub_dashboard_home_html( $D );
check( 'step label: nothing saved gives "Your next step"', strpos( $H, '<p class="vhd-eyebrow vhd-eyebrow--light">Your next step</p>' ) !== false );

// The four next steps through the real collector: nothing saved (survey), then each later step.
look_arrange( look_step_texts() );
$D = vance_hub_dashboard_data( look_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'step survey: with nothing saved the next step is the survey, with its custom title', is_array( $D['step'] ) && $D['step']['title'] === 'Custom survey title' );
check( 'step survey: its custom text is in the collector', is_array( $D['step'] ) && $D['step']['text'] === 'Custom survey text.' );
check( 'step survey: its custom button is in the collector', is_array( $D['step'] ) && $D['step']['cta'] === 'Custom survey button' );
$H = vance_hub_dashboard_home_html( $D );
check( 'step survey: the rendered title is the custom one', strpos( $H, '<h2 id="vhd-step-title" class="vhd-step__title">Custom survey title</h2>' ) !== false );
check( 'step survey: the rendered text is the custom one', strpos( $H, '<p class="vhd-step__text">Custom survey text.</p>' ) !== false );
check( 'step survey: the rendered button is the custom one', strpos( $H, 'vhd-btn vhd-btn--white" href="' ) !== false && strpos( $H, '>Custom survey button <span' ) !== false );

look_arrange( array_merge( look_step_texts(), array( 'vance_hub_text_dash_survey_title' => 'Unused survey title' ) ) );
$GLOBALS['USER_META'][7]['_sla_healthcare_quiz_results'] = array( 'q1' => 'a' );
$D = vance_hub_dashboard_data( look_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'step profile: with the survey done the next step is the profile, with its custom title', is_array( $D['step'] ) && $D['step']['title'] === 'Custom profile title' );
check( 'step profile: its custom text is in the collector', is_array( $D['step'] ) && $D['step']['text'] === 'Custom profile text.' );
check( 'step profile: its custom button is in the collector', is_array( $D['step'] ) && $D['step']['cta'] === 'Custom profile button' );
$H = vance_hub_dashboard_home_html( $D );
check( 'step profile: the rendered title, text and button are the custom ones', strpos( $H, 'class="vhd-step__title">Custom profile title</h2>' ) !== false && strpos( $H, 'class="vhd-step__text">Custom profile text.</p>' ) !== false && strpos( $H, '>Custom profile button <span' ) !== false );

look_arrange( look_step_texts() );
$GLOBALS['USER_META'][7]['_sla_healthcare_quiz_results'] = array( 'q1' => 'a' );
$GLOBALS['USER_META'][7]['_sla_clinical_profile'] = array( 'condition' => 'ibd' );
$D = vance_hub_dashboard_data( look_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'step plan: with the survey and profile done and no plan the next step is the plan', is_array( $D['step'] ) && $D['step']['title'] === 'Custom plan title' );
check( 'step plan: its custom text is in the collector', is_array( $D['step'] ) && $D['step']['text'] === 'Custom plan text.' );
check( 'step plan: its custom button is in the collector', is_array( $D['step'] ) && $D['step']['cta'] === 'Custom plan button' );
$H = vance_hub_dashboard_home_html( $D );
check( 'step plan: the rendered title, text and button are the custom ones', strpos( $H, 'class="vhd-step__title">Custom plan title</h2>' ) !== false && strpos( $H, 'class="vhd-step__text">Custom plan text.</p>' ) !== false && strpos( $H, '>Custom plan button <span' ) !== false );

look_arrange( look_step_texts() );
$GLOBALS['USER_META'][7]['_sla_healthcare_quiz_results'] = array( 'q1' => 'a' );
$GLOBALS['USER_META'][7]['_sla_clinical_profile'] = array( 'condition' => 'ibd' );
$GLOBALS['HISTORY']['ibd-recipes'] = array( array( 'ts' => strtotime( '2026-11-05 10:00:00 UTC' ), 'payload' => array( 'week' => 1 ) ) );
$D = vance_hub_dashboard_data( look_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'step check: with a plan saved and no check the next step is the check, with its custom title', is_array( $D['step'] ) && $D['step']['title'] === 'Custom check title' );
check( 'step check: its custom text is in the collector', is_array( $D['step'] ) && $D['step']['text'] === 'Custom check text.' );
check( 'step check: its custom button is in the collector', is_array( $D['step'] ) && $D['step']['cta'] === 'Custom check button' );
$H = vance_hub_dashboard_home_html( $D );
check( 'step check: the rendered title, text and button are the custom ones', strpos( $H, 'class="vhd-step__title">Custom check title</h2>' ) !== false && strpos( $H, 'class="vhd-step__text">Custom check text.</p>' ) !== false && strpos( $H, '>Custom check button <span' ) !== false );

// Built-in step text with nothing saved, through the same collector.
look_arrange();
$D = vance_hub_dashboard_data( look_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'step default: nothing saved gives the built-in survey title', is_array( $D['step'] ) && $D['step']['title'] === 'Do the Gastro Self-Assessment' );
check( 'step default: nothing saved gives the built-in survey button', is_array( $D['step'] ) && $D['step']['cta'] === 'Start the survey' );

// A blank saved title falls back to the built-in one, not to nothing.
look_arrange( array( 'vance_hub_text_dash_survey_title' => '  ', 'vance_hub_text_dash_survey_button' => '' ) );
$D = vance_hub_dashboard_data( look_user( 7, 'Ruth', '2024-03-05 10:00:00' ) );
check( 'step default: a blank saved survey title gives the built-in title', is_array( $D['step'] ) && $D['step']['title'] === 'Do the Gastro Self-Assessment' );
check( 'step default: a blank saved survey button gives the built-in button', is_array( $D['step'] ) && $D['step']['cta'] === 'Start the survey' );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
