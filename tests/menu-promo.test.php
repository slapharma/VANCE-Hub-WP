<?php
/**
 * inc/promo-block.php's two Mobile Menu Banners instances, inc/nav-mega.php's
 * vance_nav_menu_promo_items() filter that appends them to the primary menu,
 * and functions.php's vance_register_promo_block_controls() 'compact' mode
 * that drops the controls a 300px drawer has no room for.
 *
 * Same shape as hero-mobile.test.php: WP stubs, a controllable bag of theme
 * mods, and assertions against the real emitted HTML/registrar output — not a
 * re-implementation of the logic under test.
 *
 * inc/nav-mega.php cannot be required whole — it registers WP_Widget
 * subclasses at file scope that this stub layer does not provide (see
 * hero-mobile.test.php's note on the same constraint for the archive
 * templates) — so vance_nav_menu_promo_items() is lifted out of it by brace
 * matching, same technique hero-mobile.test.php uses on functions.php.
 * vance_register_promo_block_controls() is lifted out of functions.php for
 * the same reason: functions.php is not loadable standalone.
 *
 * Every check here must be able to go red — confirmed by hand-sabotaging
 * promo-block.php, nav-mega.php and functions.php in turn and watching this
 * suite fail, then restoring the exact bytes (see the task this suite was
 * written for; nothing here is committed as a mutate-*.py runner).
 */

define( 'ABSPATH', true );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

/* ---- the bag the stub reads ------------------------------------------- */
$GLOBALS['MODS'] = array();
function set_mods( array $m ) { $GLOBALS['MODS'] = $m; }

/* ---- WordPress stubs, union of what promo-block.php needs -------------- */
function vance_get_theme_mod( $key, $default = '' ) {
	return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
}
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/vance-health-hub'; }
function home_url( $p = '/' ) { return 'https://example.test' . $p; }
function wp_registration_url() { return 'https://example.test/wp-login.php?action=register'; }
function __( $t, $d = '' ) { return $t; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_url( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function absint( $n ) { return abs( (int) $n ); }
function vance_sanitize_checkbox( $checked ) { return ( ( isset( $checked ) && true == $checked ) ? true : false ); }

/** Minimal Customizer stand-ins, just enough to record what got registered. */
class WP_Customize_Color_Control {
	public $id, $args;
	public function __construct( $wp_customize, $id, $args = array() ) { $this->id = $id; $this->args = $args; }
}
class WP_Customize_Image_Control extends WP_Customize_Color_Control {}

class Recorder {
	public $sections = array(), $settings = array(), $controls = array();
	function add_section( $id, $a ) { $this->sections[ $id ] = $a; }
	function add_setting( $id, $a ) { $this->settings[ $id ] = $a; }
	function add_control( $id_or_obj, $a = array() ) {
		if ( is_object( $id_or_obj ) ) {
			$this->controls[ $id_or_obj->id ] = $id_or_obj->args;
		} else {
			$this->controls[ $id_or_obj ] = $a;
		}
	}
}

/**
 * Same brace-matching lift as hero-mobile.test.php: pulls one top-level
 * function's source out of a file and evaluates it. Fatal, not a silent skip,
 * if the signature has drifted — a suite that stops testing the real source
 * without saying so is worse than no suite.
 */
function lift_function( $file, $name ) {
	$src = file_get_contents( $file );
	if ( $src === false ) { fwrite( STDERR, "FATAL: cannot read $file\n" ); exit( 2 ); }

	$sig = "function $name(";
	$at  = strpos( $src, $sig );
	if ( $at === false ) {
		fwrite( STDERR, "FATAL: $name() not found in $file — the extraction has drifted.\n" );
		exit( 2 );
	}
	$open  = strpos( $src, '{', $at );
	$depth = 0;
	for ( $i = $open; $i < strlen( $src ); $i++ ) {
		if ( $src[ $i ] === '{' ) { $depth++; }
		elseif ( $src[ $i ] === '}' ) {
			$depth--;
			if ( $depth === 0 ) {
				eval( substr( $src, $at, $i - $at + 1 ) );
				return true;
			}
		}
	}
	fwrite( STDERR, "FATAL: unbalanced braces reading $name() from $file\n" );
	exit( 2 );
}

/* ---- load the real source ---------------------------------------------- */
require_once $THEME . '/inc/promo-block.php';
lift_function( $THEME . '/inc/nav-mega.php', 'vance_nav_menu_promo_items' );
lift_function( $THEME . '/functions.php', 'vance_register_promo_block_controls' );

/* ---- the runner ---------------------------------------------------------- */
$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

function render_menu_promos( array $mods = array() ) {
	set_mods( $mods );
	return vance_menu_promo_items_html();
}


/* ===================================================================== */
section( '1. vance_menu_promo_items_html() — both slots off' );
/* ===================================================================== */

check( '1a  nothing saved at all: empty string',
	render_menu_promos() === '' );

check( '1b  both show switches explicitly false: empty string',
	render_menu_promos( array(
		'vance_menupromo1_show' => false,
		'vance_menupromo2_show' => false,
	) ) === '' );


/* ===================================================================== */
section( '2. Shown but empty: the renderer\'s own bail-out still applies' );
/* ===================================================================== */

check( '2a  slot 1 switched on with no heading, text or image: no <li> at all',
	render_menu_promos( array( 'vance_menupromo1_show' => true ) ) === '' );

check( '2b  slot 2 switched on with no content either: still empty',
	render_menu_promos( array( 'vance_menupromo2_show' => true ) ) === '' );

check( '2c  one empty slot and one real one: only the real one renders',
	( function () {
		$html = render_menu_promos( array(
			'vance_menupromo1_show'    => true, // empty — must be skipped
			'vance_menupromo2_show'    => true,
			'vance_menupromo2_heading' => 'Real banner',
		) );
		return strpos( $html, 'vance-menu-promo--1' ) === false
			&& strpos( $html, 'vance-menu-promo--2' ) !== false;
	} )() );


/* ===================================================================== */
section( '3. Wrapping <li> and slot numbering' );
/* ===================================================================== */

$h = render_menu_promos( array(
	'vance_menupromo1_show'    => true,
	'vance_menupromo1_heading' => 'Banner one',
) );
check( '3a  slot 1 wraps in an <li> carrying both the shared and the numbered class',
	strpos( $h, '<li class="vance-menu-promo vance-menu-promo--1">' ) !== false );
check( '3b  the <li> is closed',
	substr( trim( $h ), -5 ) === '</li>' );

$h2 = render_menu_promos( array(
	'vance_menupromo1_show'    => true,
	'vance_menupromo1_heading' => 'Banner one',
	'vance_menupromo2_show'    => true,
	'vance_menupromo2_heading' => 'Banner two',
) );
check( '3c  both slots on: two <li>s, in slot order, each with its own text',
	strpos( $h2, 'vance-menu-promo--1' ) < strpos( $h2, 'vance-menu-promo--2' )
	&& strpos( $h2, 'Banner one' ) !== false
	&& strpos( $h2, 'Banner two' ) !== false );


/* ===================================================================== */
section( '4. Layout is coerced to the two that fit a 300px drawer' );
/* ===================================================================== */

$h = render_menu_promos( array(
	'vance_menupromo1_show'    => true,
	'vance_menupromo1_heading' => 'Coerced',
	'vance_menupromo1_layout'  => 'image_left',
) );
check( '4a  a stored page layout ("image_left") renders with the stacked inner class',
	strpos( $h, 'vance-cat-promo__inner--stacked' ) !== false );
check( '4b  ...and NOT with its own (page-only) inner class',
	strpos( $h, 'vance-cat-promo__inner--image_left' ) === false );

$h = render_menu_promos( array(
	'vance_menupromo1_show'    => true,
	'vance_menupromo1_heading' => 'Untouched',
	'vance_menupromo1_layout'  => 'banner',
) );
check( '4c  "banner", one of the two the drawer offers, passes through unchanged',
	strpos( $h, 'vance-cat-promo__inner--banner' ) !== false );

$h = render_menu_promos( array(
	'vance_menupromo1_show'    => true,
	'vance_menupromo1_heading' => 'Text only ignored',
	'vance_menupromo1_layout'  => 'text',
) );
check( '4d  "text" (also a real page layout, also not offered here) is coerced to stacked too',
	strpos( $h, 'vance-cat-promo__inner--stacked' ) !== false
	&& strpos( $h, 'vance-cat-promo__inner--text' ) === false );


/* ===================================================================== */
section( '5. Banner layout with an image: the inline scrim' );
/* ===================================================================== */

$h = render_menu_promos( array(
	'vance_menupromo1_show'    => true,
	'vance_menupromo1_heading' => 'On a photo',
	'vance_menupromo1_layout'  => 'banner',
	'vance_menupromo1_image'   => 'https://example.test/img/drawer-banner.jpg',
) );
check( '5a  banner + image: the scrim gradient and the photo URL are inlined together',
	strpos( $h, 'linear-gradient(90deg' ) !== false
	// esc_url() runs with ENT_QUOTES, so the single quotes around the url()
	// come out HTML-entity-encoded in the style attribute.
	&& strpos( $h, "url(&#039;https://example.test/img/drawer-banner.jpg&#039;)" ) !== false );

check( '5b  the card carries the has-image modifier class',
	strpos( $h, 'has-image' ) !== false );

$h_stacked = render_menu_promos( array(
	'vance_menupromo1_show'    => true,
	'vance_menupromo1_heading' => 'No photo',
	'vance_menupromo1_layout'  => 'stacked',
) );
check( '5c  no image saved: no scrim gradient at all',
	strpos( $h_stacked, 'linear-gradient(90deg' ) === false );


/* ===================================================================== */
section( '6. Page-only options never reach the menu markup' );
/* ===================================================================== */

$h = render_menu_promos( array(
	'vance_menupromo1_show'            => true,
	'vance_menupromo1_heading'         => 'Featured',
	'vance_menupromo1_side_tool_show'  => true,
	'vance_menupromo1_side_tool'       => 'healthcare-quiz',
	'vance_menupromo1_width'           => 'full',
	'vance_menupromo1_bg_color'        => '#ff00ff',
	'vance_menupromo1_border_scope'    => 'full',
) );

check( '6a  side_tool_show=true stored, but no sidebar column is rendered',
	strpos( $h, 'vance-promo-columns' ) === false
	&& strpos( $h, 'vance-promo-tool-card' ) === false );

check( '6b  width=full stored, but the container stays the narrow one, not container-fluid',
	strpos( $h, 'container-fluid' ) === false
	&& strpos( $h, 'class="container"' ) !== false );

check( '6c  bg_color stored, but the forced-empty band background never appears in the markup',
	strpos( $h, '#ff00ff' ) === false );

check( '6d  border_scope=full stored, but the wrapper carries no style attribute built from it',
	// band_style would only gain content from band_bg (forced '') or text_color
	// (not set here) or a "full" border, all forced off — so the <div> that
	// wraps the whole banner has no style="" at all.
	preg_match( '/<div class="vance-cat-promo[^"]*"\s+style="/', $h ) !== 1 );


/* ===================================================================== */
section( '7. Wrapper and heading tags: div + span, never section/h2/aria-label' );
/* ===================================================================== */

$h = render_menu_promos( array(
	'vance_menupromo1_show'    => true,
	'vance_menupromo1_heading' => 'Tips & <b>Tricks</b>',
) );

check( '7a  the heading is escaped and sits in a <span>, not an <h2>',
	strpos( $h, '<span class="vance-cat-promo__title">Tips &amp; &lt;b&gt;Tricks&lt;/b&gt;</span>' ) !== false );

check( '7b  no <h2> anywhere in the menu markup',
	stripos( $h, '<h2' ) === false );

check( '7c  no <section> anywhere in the menu markup',
	stripos( $h, '<section' ) === false );

check( '7d  no aria-label anywhere in the menu markup',
	stripos( $h, 'aria-label' ) === false );

check( '7e  the wrapper really is a <div>, carrying the block class',
	preg_match( '/<div class="vance-cat-promo[^"]*">/', $h ) === 1 );


/* ===================================================================== */
section( '8. The unified tool-modal CTA still works inside the drawer' );
/* ===================================================================== */

$h = render_menu_promos( array(
	'vance_menupromo1_show'      => true,
	'vance_menupromo1_heading'   => 'Take the survey',
	'vance_menupromo1_button_text' => 'Start now',
	'vance_menupromo1_tool'      => 'healthcare-quiz',
) );
check( '8a  a tool CTA gets data-vance-tool-open, not a bare link',
	strpos( $h, 'data-vance-tool-open="healthcare-quiz"' ) !== false );

check( '8b  the CTA points at the tool\'s real page',
	strpos( $h, 'href="https://example.test/gastro-health-survey/"' ) !== false );

$h_url = render_menu_promos( array(
	'vance_menupromo2_show'      => true,
	'vance_menupromo2_heading'   => 'Custom link banner',
	'vance_menupromo2_button_text' => 'Go',
	'vance_menupromo2_button_link' => '/ask-ai/',
) );
check( '8c  no tool selected: falls back to the plain custom link, no data-vance-tool-open',
	strpos( $h_url, 'href="/ask-ai/"' ) !== false
	&& strpos( $h_url, 'data-vance-tool-open' ) === false );


/* ===================================================================== */
section( '9. vance_render_promo_block() page instance is unchanged' );
/* ===================================================================== */

function render_page_promo( $term_id, array $mods ) {
	set_mods( $mods );
	ob_start();
	vance_render_category_promo( $term_id );
	return ob_get_clean();
}

$page = render_page_promo( 501, array(
	'vance_cat_promo_show_501'    => true,
	'vance_cat_promo_heading_501' => 'A category feature',
) );
check( '9a  a page instance still renders a <section> with an aria-label',
	preg_match( '/<section class="vance-cat-promo[^"]*" aria-label="A category feature"/', $page ) === 1 );

check( '9b  ...and the heading is still an <h2>',
	strpos( $page, '<h2 class="vance-cat-promo__title">A category feature</h2>' ) !== false );

check( '9c  ...and it is properly closed as a </section>, not a stray </div>',
	substr( trim( $page ), -10 ) === '</section>' );


/* ===================================================================== */
section( '10. vance_nav_menu_promo_items(): only the primary menu gets the banners' );
/* ===================================================================== */

set_mods( array(
	'vance_menupromo1_show'    => true,
	'vance_menupromo1_heading' => 'Drawer banner',
) );

$primary_args = (object) array( 'theme_location' => 'primary-menu' );
$out = vance_nav_menu_promo_items( '<li>Existing link</li>', $primary_args );
check( '10a  primary-menu: existing items are kept, banners appended after them',
	strpos( $out, '<li>Existing link</li>' ) === 0
	&& strpos( $out, 'vance-menu-promo' ) > strpos( $out, '<li>Existing link</li>' ) );

$footer_args = (object) array( 'theme_location' => 'footer-menu' );
$out2 = vance_nav_menu_promo_items( '<li>Existing link</li>', $footer_args );
check( '10b  a different theme_location: items returned completely untouched',
	$out2 === '<li>Existing link</li>' );

$empty_args = (object) array( 'theme_location' => '' );
$out3 = vance_nav_menu_promo_items( '<li>Existing link</li>', $empty_args );
check( '10c  an empty theme_location: also untouched',
	$out3 === '<li>Existing link</li>' );

set_mods( array() );


/* ===================================================================== */
section( '11. vance_register_promo_block_controls(): compact mode' );
/* ===================================================================== */

$key = vance_promo_keys_prefixed( 'vance_menupromo1_' );

$compact = new Recorder();
vance_register_promo_block_controls( $compact, 'vance_menu_promo_banners', $key, array(
	'show_label'     => 'Show this banner',
	'label_prefix'   => 'Banner 1: ',
	'defaults'       => vance_menu_promo_defaults(),
	'layout_choices' => vance_menu_promo_layout_choices(),
	'compact'        => true,
) );

foreach ( array(
	'side_tool_show'      => 'side_tool_show',
	'side_tool'            => 'side_tool',
	'side_tool_bg_color'   => 'side_tool_bg_color',
	'width'                => 'width',
	'bg_color'              => 'bg_color',
	'border_scope'          => 'border_scope',
) as $field ) {
	check( "11a  compact drops the '$field' control",
		! array_key_exists( $key( $field ), $compact->controls ) );
}

foreach ( array( 'show', 'layout', 'eyebrow', 'heading', 'text', 'image', 'cta_label', 'tool', 'link',
	'container_bg_color', 'text_color', 'border_enable', 'border_width', 'border_style', 'border_color' ) as $field ) {
	check( "11b  compact still registers the '$field' control",
		array_key_exists( $key( $field ), $compact->controls ) );
}

check( '11c  compact\'s layout control offers only the two drawer-safe layouts, not all five',
	$compact->controls[ $key( 'layout' ) ]['choices'] === vance_menu_promo_layout_choices() );

check( '11d  compact\'s layout description drops the "Text only" sentence (that layout is not offered)',
	strpos( $compact->controls[ $key( 'layout' ) ]['description'], 'Text only' ) === false );

check( '11e  the show checkbox carries the banner-specific label and prefix',
	$compact->controls[ $key( 'show' ) ]['label'] === 'Banner 1: Show this banner' );


/* ===================================================================== */
section( '12. vance_register_promo_block_controls(): non-compact is unaffected' );
/* ===================================================================== */

$key2 = vance_promo_keys_term( 501 );
$full = new Recorder();
vance_register_promo_block_controls( $full, 'vance_cat_promo_section_501', $key2 );

foreach ( array( 'side_tool_show', 'side_tool', 'side_tool_bg_color', 'width', 'bg_color', 'border_scope' ) as $field ) {
	check( "12a  a normal (non-compact) instance still registers '$field'",
		array_key_exists( $key2( $field ), $full->controls ) );
}

check( '12b  a normal instance\'s layout offers all five choices, not the drawer\'s two',
	$full->controls[ $key2( 'layout' ) ]['choices'] === vance_promo_layout_choices() );

check( '12c  a normal instance\'s layout description keeps the "Text only" sentence',
	strpos( $full->controls[ $key2( 'layout' ) ]['description'], 'Text only' ) !== false );


/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
