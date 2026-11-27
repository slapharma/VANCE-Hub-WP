<?php
/**
 * Behaviour tests for inc/hub-brand.php: the three brand colours (teal, navy,
 * purple), their shades, the inline CSS they print, and the Customizer guard.
 *
 * Same shape as hub-customizer.test.php, and on purpose its stub manager: this
 * suite runs every Customizer register function against a fake manager, so the
 * totals (149 settings, 146 controls, 12 sections) and the default of every
 * setting that existed before the look and brand work are checked for real.
 * The stub block cannot be shared with hub-layout.test.php, because the two
 * blocks define the same functions. This suite does not render any template.
 *
 * WHAT IS COVERED
 *   1  hex to channels, the WCAG contrast ratio, the colour mix (0, 1, a middle
 *      ratio, 3-digit and lower-case input, clamping)
 *   2  the sanitiser and the reader: junk, arrays, a pale colour, a CSS
 *      injection string, 3-digit values, the defaults
 *   3  the validator: a pale colour is refused, a dark one is accepted, a
 *      value that is not a colour passes to the sanitiser
 *   4  vance_hub_brand_css(): nothing at the defaults (both cases), only the
 *      changed colour's properties, the selector of each stylesheet, and no
 *      input text in the output
 *   5  every property in vance_hub_brand_map() is declared in its stylesheet's
 *      own block (the CSS files are read), and every shade is a valid colour
 *   6  vance_hub_brand_enqueue(): inline CSS only for enqueued handles, nothing
 *      at the defaults, and the hook at priority 30
 *   7  the Customizer guard: the settings that existed before this work keep
 *      their default, and the totals are 149, 146 and 12
 *
 * A check whose label says what should happen and which fails on the current
 * code is a finding, not a broken check. The runner lists every failure.
 */

define( 'ABSPATH', true );
define( 'HOUR_IN_SECONDS', 3600 );
date_default_timezone_set( 'UTC' );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

/* ---- error handling: a notice in the code under test is a failure, not a pass */

set_error_handler( function ( $no, $str, $file, $line ) {
	if ( ! ( error_reporting() & $no ) ) {
		return false;
	}
	throw new ErrorException( $str, 0, $no, $file, $line );
} );

/* ---- state the stubs read; brand_arrange() resets it ----------------------- */

$GLOBALS['MODS']    = array();
$GLOBALS['HOOKS']   = array();
$GLOBALS['STYLE_ON'] = array(); // Handles that wp_style_is( $handle, 'enqueued' ) reports.
$GLOBALS['INLINE']  = array();  // Handle => the inline CSS strings added, in order.

function brand_arrange( array $mods = array(), array $enqueued = array() ) {
	$GLOBALS['MODS']     = $mods;
	$GLOBALS['STYLE_ON'] = $enqueued;
	$GLOBALS['INLINE']   = array();
}

/* ---- WordPress stubs (the same set as hub-customizer.test.php) ----------- */

function __( $text, $domain = '' ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_url( $url ) { return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8', false ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }
function current_user_can( $cap ) { return false; }
function is_user_logged_in() { return ! empty( $GLOBALS['LOGGED_IN'] ); }
function vance_get_theme_mod( $key, $default = '' ) {
	return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
}
function wp_timezone_string() { return 'Europe/London'; }
function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['HOOKS'][ $tag ][ $priority ][] = $callback;
	return true;
}
function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) { return true; }
function add_submenu_page( $parent, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	return 'toplevel_page_' . $menu_slug;
}
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function add_query_arg( $key, $value = null, $url = '' ) {
	// Both WordPress forms: (key, value, url) and (array of pairs, url).
	if ( is_array( $key ) ) {
		$pairs = $key;
		$url   = $value;
	} else {
		$pairs = array( $key => $value );
	}
	$parts = array();
	foreach ( $pairs as $k => $v ) {
		$parts[] = rawurlencode( (string) $k ) . '=' . rawurlencode( (string) $v );
	}
	return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . implode( '&', $parts );
}

// Mirrors WordPress: a valid #RGB or #RRGGBB comes back as it was typed, anything
// else gives null (an empty string gives the empty string).
function sanitize_hex_color( $color ) {
	if ( '' === $color ) {
		return '';
	}
	if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
		return $color;
	}
	return null;
}

// Stylesheet state for vance_hub_brand_enqueue().
function wp_style_is( $handle, $status = 'enqueued' ) {
	return 'enqueued' === $status && in_array( $handle, $GLOBALS['STYLE_ON'], true );
}
function wp_add_inline_style( $handle, $data ) {
	$GLOBALS['INLINE'][ $handle ][] = $data;
	return true;
}

/** A setting as the manager returns it: only value() is used here. */
class SuiteSetting {
	private $v;
	public function __construct( $v ) { $this->v = $v; }
	public function value() { return $this->v; }
}

/** Stand-in for WP_Customize_Manager: records what the register functions add. */
class SuiteCustomizeFake {
	public $values   = array();
	public $panels   = array();
	public $sections = array();
	public $settings = array();
	public $controls = array();
	public function add_panel( $id, $args = array() ) { $this->panels[ $id ] = $args; }
	public function add_section( $id, $args = array() ) { $this->sections[ $id ] = $args; }
	public function add_setting( $id, $args = array() ) { $this->settings[ $id ] = $args; }
	public function add_control( $id, $args = array() ) { $this->controls[ $id ] = $args; }
	public function get_setting( $id ) {
		return array_key_exists( $id, $this->values ) ? new SuiteSetting( $this->values[ $id ] ) : null;
	}
}

/** A validity object as core passes it: add() records the error. */
class SuiteValidity {
	public $errors = array();
	public function add( $code, $message ) { $this->errors[ $code ] = $message; }
}

/* ---- load the real code under test ---------------------------------------- */

require_once $THEME . '/inc/hub-layout.php';
require_once $THEME . '/inc/hub-home-settings.php';
require_once $THEME . '/inc/hub-look-settings.php';
require_once $THEME . '/inc/hub-brand.php';
require_once $THEME . '/inc/hub-golive.php';
require_once $THEME . '/inc/hub-article.php';
require_once $THEME . '/inc/hub-dashboard-features.php';
require_once $THEME . '/inc/hub-tool-result.php';
require_once $THEME . '/inc/hub-header.php';
require_once $THEME . '/inc/hub-community.php';
require_once $THEME . '/inc/hub-flare-plan.php';
require_once $THEME . '/inc/hub-admin.php';

/* ---- helpers --------------------------------------------------------------- */

/**
 * The declaration block of the first rule whose selector is $selector, read
 * from a stylesheet: the text between "selector {" at the start of a line and
 * the closing "}" at the start of a line. '' when the rule is not there.
 */
function brand_block( $file, $selector ) {
	$css = file_get_contents( $file );
	if ( false === $css || ! preg_match( '/^' . preg_quote( $selector, '/' ) . '\s*\{(.*?)^\}/ms', $css, $m ) ) {
		return '';
	}
	return $m[1];
}

/** The stylesheet file of a hub handle: vance-hub-home is assets/css/hub-home.css. */
function brand_css_file( $handle ) {
	return $GLOBALS['THEME'] . '/assets/css/' . substr( $handle, strlen( 'vance-' ) ) . '.css';
}

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/* ===================================================================== */
section( '1. Channels, contrast and mix (pure)' );

check( 'channels: #008080 is 0, 128, 128', vance_hub_brand_hex_to_rgb( '#008080' ) === array( 0, 128, 128 ) );
check( 'channels: a 3-digit value expands: #fff is 255, 255, 255', vance_hub_brand_hex_to_rgb( '#fff' ) === array( 255, 255, 255 ) );
check( 'channels: a lower-case value reads the same as upper case', vance_hub_brand_hex_to_rgb( '#6b489e' ) === array( 107, 72, 158 ) );
check( 'channels: a value without the hash is not a colour', vance_hub_brand_hex_to_rgb( 'abc' ) === null );
check( 'channels: a value with a non-hex letter is not a colour', vance_hub_brand_hex_to_rgb( '#GG0000' ) === null );
check( 'channels: a five-digit value is not a colour', vance_hub_brand_hex_to_rgb( '#12345' ) === null );
check( 'channels: the empty string is not a colour', vance_hub_brand_hex_to_rgb( '' ) === null );
check( 'channels: an array is not a colour', vance_hub_brand_hex_to_rgb( array( '#008080' ) ) === null );

check( 'contrast: black on white is 21', abs( vance_hub_brand_contrast( '#000000', '#FFFFFF' ) - 21 ) < 0.001 );
check( 'contrast: white on black is 21 too (order does not matter)', abs( vance_hub_brand_contrast( '#FFFFFF', '#000000' ) - 21 ) < 0.001 );
check( 'contrast: a colour on itself is 1', abs( vance_hub_brand_contrast( '#008080', '#008080' ) - 1 ) < 0.001 );
check( 'contrast: #008080 on white is about 4.77', abs( vance_hub_brand_contrast( '#008080', '#FFFFFF' ) - 4.77 ) < 0.01 );
check( 'contrast: #767676 on white is about 4.54', abs( vance_hub_brand_contrast( '#767676', '#FFFFFF' ) - 4.54 ) < 0.01 );
check( 'contrast: a yellow on white is far under 4.5', vance_hub_brand_contrast( '#FFFF00', '#FFFFFF' ) < 1.2 );
check( 'contrast: an invalid colour gives 0.0', vance_hub_brand_contrast( 'red', '#FFFFFF' ) === 0.0 );
check( 'contrast: a 3-digit colour gives the same ratio as its 6-digit form', abs( vance_hub_brand_contrast( '#fff', '#000' ) - 21 ) < 0.001 );

check( 'mix: ratio 0 gives the base colour', vance_hub_brand_mix( '#008080', '#FFFFFF', 0 ) === '#008080' );
check( 'mix: ratio 1 gives the target colour', vance_hub_brand_mix( '#008080', '#FFFFFF', 1 ) === '#FFFFFF' );
check( 'mix: a middle ratio of black and white rounds 127.5 up to #808080', vance_hub_brand_mix( '#000000', '#FFFFFF', 0.5 ) === '#808080' );
check( 'mix: a 3-digit base gives the same middle as its 6-digit form', vance_hub_brand_mix( '#000', '#FFFFFF', 0.5 ) === '#808080' );
check( 'mix: a lower-case base is read and the result is upper case', vance_hub_brand_mix( '#abcdef', '#FFFFFF', 0 ) === '#ABCDEF' );
check( 'mix: a ratio above 1 is clamped to the target', vance_hub_brand_mix( '#000000', '#FFFFFF', 2.0 ) === '#FFFFFF' );
check( 'mix: a ratio below 0 is clamped to the base', vance_hub_brand_mix( '#000000', '#FFFFFF', -1 ) === '#000000' );
check( 'mix: an invalid base gives the empty string', vance_hub_brand_mix( 'nope', '#FFFFFF', 0.5 ) === '' );
check( 'mix: an invalid target gives the empty string', vance_hub_brand_mix( '#008080', '', 0.5 ) === '' );

/* ===================================================================== */
section( '2. Sanitiser and reader' );

$TEAL_SETTING = (object) array( 'id' => 'vance_hub_color_teal' );
$NAVY_SETTING = (object) array( 'id' => 'vance_hub_color_navy' );

check( 'sanitise: a valid colour is kept in upper case', vance_hub_sanitize_brand_color( '#0b6e99', $TEAL_SETTING ) === '#0B6E99' );
check( 'sanitise: a 3-digit value is expanded', vance_hub_sanitize_brand_color( '#abc', $TEAL_SETTING ) === '#AABBCC' );
check( 'sanitise: surrounding spaces are trimmed', vance_hub_sanitize_brand_color( '  #0B6E99 ', $TEAL_SETTING ) === '#0B6E99' );
check( 'sanitise: a word gives the teal default', vance_hub_sanitize_brand_color( 'red', $TEAL_SETTING ) === '#008080' );
check( 'sanitise: a word gives the navy default for the navy setting', vance_hub_sanitize_brand_color( 'red', $NAVY_SETTING ) === '#0A1929' );
check( 'sanitise: an array gives the default', vance_hub_sanitize_brand_color( array( '#0B6E99' ), $TEAL_SETTING ) === '#008080' );
check( 'sanitise: null gives the default', vance_hub_sanitize_brand_color( null, $TEAL_SETTING ) === '#008080' );
check( 'sanitise: a CSS injection string gives the default', vance_hub_sanitize_brand_color( '#008080;}body{x:y', $TEAL_SETTING ) === '#008080' );
check( 'sanitise: a value with a closing brace gives the default', vance_hub_sanitize_brand_color( '#0B6E99}', $TEAL_SETTING ) === '#008080' );
check( 'sanitise: a pale colour is kept (the validator refuses it, the reader gives the default)', vance_hub_sanitize_brand_color( '#FFFF00', $TEAL_SETTING ) === '#FFFF00' );
check( 'sanitise: with no setting object and junk, the result is the empty string', vance_hub_sanitize_brand_color( 'red', null ) === '' );

brand_arrange();
check( 'reader: nothing saved gives the teal default', vance_hub_brand_color( 'teal' ) === '#008080' );
check( 'reader: nothing saved gives the navy default', vance_hub_brand_color( 'navy' ) === '#0A1929' );
check( 'reader: nothing saved gives the purple default', vance_hub_brand_color( 'purple' ) === '#6B489E' );
check( 'reader: an unknown colour name gives the empty string', vance_hub_brand_color( 'pink' ) === '' );
check( 'reader: the three colours as a map, at the defaults', vance_hub_brand_colors() === array( 'teal' => '#008080', 'navy' => '#0A1929', 'purple' => '#6B489E' ) );

brand_arrange( array( 'vance_hub_color_teal' => '#0b6e99' ) );
check( 'reader: a saved colour is read in upper case', vance_hub_brand_color( 'teal' ) === '#0B6E99' );

brand_arrange( array( 'vance_hub_color_teal' => '#036' ) );
check( 'reader: a saved dark 3-digit colour is expanded', vance_hub_brand_color( 'teal' ) === '#003366' );

brand_arrange( array( 'vance_hub_color_teal' => '#abc' ) );
check( 'reader: a saved 3-digit colour too pale for white (#AABBCC, contrast about 2.0) gives the default', vance_hub_brand_color( 'teal' ) === '#008080' );

brand_arrange( array( 'vance_hub_color_teal' => '#FFFF00' ) );
check( 'reader: a pale saved colour (#FFFF00) gives the teal default', vance_hub_brand_color( 'teal' ) === '#008080' );

brand_arrange( array( 'vance_hub_color_navy' => '#FFFFFF' ) );
check( 'reader: white saved for navy gives the navy default (white is too pale for text)', vance_hub_brand_color( 'navy' ) === '#0A1929' );

brand_arrange( array( 'vance_hub_color_teal' => '#008080;}body{x:y' ) );
check( 'reader: a CSS injection string saved in the mod gives the default', vance_hub_brand_color( 'teal' ) === '#008080' );

brand_arrange( array( 'vance_hub_color_teal' => 'red' ) );
check( 'reader: a word saved in the mod gives the default', vance_hub_brand_color( 'teal' ) === '#008080' );

brand_arrange( array( 'vance_hub_color_teal' => array( '#0B6E99' ) ) );
check( 'reader: an array saved in the mod gives the default', vance_hub_brand_color( 'teal' ) === '#008080' );

brand_arrange( array( 'vance_hub_color_teal' => null ) );
check( 'reader: null saved in the mod gives the default', vance_hub_brand_color( 'teal' ) === '#008080' );

brand_arrange( array( 'vance_hub_color_teal' => 12345 ) );
check( 'reader: a number saved in the mod gives the default', vance_hub_brand_color( 'teal' ) === '#008080' );

brand_arrange( array( 'vance_hub_color_purple' => '#4A2C7A' ) );
check( 'reader: a dark saved purple is read', vance_hub_brand_color( 'purple' ) === '#4A2C7A' );

// The validator: a pale colour is refused, a dark one accepted, a non-colour passes on.
$VALID_PALE = new SuiteValidity();
$RESULT     = vance_hub_validate_brand_color( $VALID_PALE, '#FFFF00', $TEAL_SETTING );
check( 'validator: a pale colour adds an error to the validity object', isset( $VALID_PALE->errors['vance_hub_color_too_pale'] ) );
check( 'validator: a pale colour returns the same validity object', $RESULT === $VALID_PALE );

$VALID_DARK = new SuiteValidity();
vance_hub_validate_brand_color( $VALID_DARK, '#0B6E99', $TEAL_SETTING );
check( 'validator: a dark colour adds no error', $VALID_DARK->errors === array() );

$VALID_EDGE_PASS = new SuiteValidity();
vance_hub_validate_brand_color( $VALID_EDGE_PASS, '#767676', $TEAL_SETTING );
check( 'validator: #767676 (contrast 4.54) is accepted, the boundary is 4.5', $VALID_EDGE_PASS->errors === array() );

$VALID_EDGE_FAIL = new SuiteValidity();
vance_hub_validate_brand_color( $VALID_EDGE_FAIL, '#777777', $TEAL_SETTING );
check( 'validator: #777777 (contrast 4.47) is refused, just under the boundary', isset( $VALID_EDGE_FAIL->errors['vance_hub_color_too_pale'] ) );

$VALID_JUNK = new SuiteValidity();
vance_hub_validate_brand_color( $VALID_JUNK, 'red', $TEAL_SETTING );
check( 'validator: a value that is not a colour adds no error (the sanitiser gives the default)', $VALID_JUNK->errors === array() );

check( 'validator: with no validity object, a pale colour gives an array of the code', vance_hub_validate_brand_color( null, '#FFFF00', $TEAL_SETTING ) === array( 'vance_hub_color_too_pale' => 'This colour is too pale for white text. Choose a darker colour.' ) );

/* ===================================================================== */
section( '3. The inline CSS: vance_hub_brand_css()' );

// Nothing at the defaults, for every stylesheet, in both letter cases.
$DEF_UP = array( 'teal' => '#008080', 'navy' => '#0A1929', 'purple' => '#6B489E' );
$DEF_LO = array( 'teal' => '#008080', 'navy' => '#0a1929', 'purple' => '#6b489e' );
check( 'css: at the defaults the home stylesheet gets no rule (upper case)', vance_hub_brand_css( 'vance-hub-home', $DEF_UP ) === '' );
check( 'css: at the defaults the home stylesheet gets no rule (lower case)', vance_hub_brand_css( 'vance-hub-home', $DEF_LO ) === '' );
check( 'css: at the defaults the article stylesheet gets no rule (upper case)', vance_hub_brand_css( 'vance-hub-article', $DEF_UP ) === '' );
check( 'css: at the defaults the article stylesheet gets no rule (lower case)', vance_hub_brand_css( 'vance-hub-article', $DEF_LO ) === '' );
check( 'css: at the defaults the tool-result stylesheet gets no rule (upper case)', vance_hub_brand_css( 'vance-hub-tool-result', $DEF_UP ) === '' );
check( 'css: at the defaults the tool-result stylesheet gets no rule (lower case)', vance_hub_brand_css( 'vance-hub-tool-result', $DEF_LO ) === '' );
check( 'css: at the defaults the header stylesheet gets no rule (upper case)', vance_hub_brand_css( 'vance-hub-header', $DEF_UP ) === '' );
check( 'css: at the defaults the header stylesheet gets no rule (lower case)', vance_hub_brand_css( 'vance-hub-header', $DEF_LO ) === '' );
check( 'css: at the defaults the dashboard stylesheet gets no rule (upper case)', vance_hub_brand_css( 'vance-hub-dashboard', $DEF_UP ) === '' );
check( 'css: at the defaults the dashboard stylesheet gets no rule (lower case)', vance_hub_brand_css( 'vance-hub-dashboard', $DEF_LO ) === '' );
check( 'css: at the defaults the dashboard-tabs stylesheet gets no rule (upper case)', vance_hub_brand_css( 'vance-hub-dashboard-tabs', $DEF_UP ) === '' );
check( 'css: at the defaults the dashboard-tabs stylesheet gets no rule (lower case)', vance_hub_brand_css( 'vance-hub-dashboard-tabs', $DEF_LO ) === '' );
check( 'css: at the defaults the community stylesheet gets no rule (upper case)', vance_hub_brand_css( 'vance-hub-community', $DEF_UP ) === '' );
check( 'css: at the defaults the community stylesheet gets no rule (lower case)', vance_hub_brand_css( 'vance-hub-community', $DEF_LO ) === '' );

// One changed colour: only its properties, under the stylesheet's selector.
$ONE_TEAL = array( 'teal' => '#0B6E99', 'navy' => '#0A1929', 'purple' => '#6B489E' );
$CSS_HOME = vance_hub_brand_css( 'vance-hub-home', $ONE_TEAL );
check( 'css: a changed teal on the home stylesheet starts with the .vhub selector and its base property', strpos( $CSS_HOME, '.vhub{--vhub-teal:#0B6E99;' ) === 0 );
check( 'css: a changed teal on the home stylesheet ends with the closing brace', substr( $CSS_HOME, -1 ) === '}' );
check( 'css: a changed teal on the home stylesheet sets the hover shade (#0B6E99 mixed to black at 0.2)', strpos( $CSS_HOME, '--vhub-teal-hover:' . vance_hub_brand_mix( '#0B6E99', '#000000', 0.2 ) . ';' ) !== false );
check( 'css: a changed teal on the home stylesheet sets the rgb triple for rgba()', strpos( $CSS_HOME, '--vhub-teal-rgb:11, 110, 153;' ) !== false );
check( 'css: a changed teal does not touch the navy properties', strpos( $CSS_HOME, '--vhub-navy' ) === false );
check( 'css: a changed teal does not touch the purple properties', strpos( $CSS_HOME, '--vhub-purple' ) === false );

$ONE_NAVY = array( 'teal' => '#008080', 'navy' => '#123456', 'purple' => '#6B489E' );
$CSS_NAVY = vance_hub_brand_css( 'vance-hub-dashboard', $ONE_NAVY );
check( 'css: a changed navy on the dashboard stylesheet uses the .vhd selector', strpos( $CSS_NAVY, '.vhd{--vhd-navy:#123456;' ) === 0 );
check( 'css: a changed navy does not touch the teal properties', strpos( $CSS_NAVY, '--vhd-teal' ) === false );

$ONE_PURPLE = array( 'teal' => '#008080', 'navy' => '#0A1929', 'purple' => '#4A2C7A' );
$CSS_PURP   = vance_hub_brand_css( 'vance-hub-dashboard-tabs', $ONE_PURPLE );
check( 'css: a changed purple on the dashboard-tabs stylesheet uses the body.vhd-tabs selector', strpos( $CSS_PURP, 'body.vhd-tabs{--vhdt-purple:#4A2C7A;' ) === 0 );

// The selector of every stylesheet, with a changed colour.
$ALL_CHANGED = array( 'teal' => '#0B6E99', 'navy' => '#123456', 'purple' => '#4A2C7A' );
check( 'css: the home rule uses .vhub', strpos( vance_hub_brand_css( 'vance-hub-home', $ALL_CHANGED ), '.vhub{' ) === 0 );
check( 'css: the article rule uses body.vhub-article', strpos( vance_hub_brand_css( 'vance-hub-article', $ALL_CHANGED ), 'body.vhub-article{' ) === 0 );
check( 'css: the tool-result rule uses .vhub-tr', strpos( vance_hub_brand_css( 'vance-hub-tool-result', $ALL_CHANGED ), '.vhub-tr{' ) === 0 );
check( 'css: the header rule uses body.vhub-header', strpos( vance_hub_brand_css( 'vance-hub-header', $ALL_CHANGED ), 'body.vhub-header{' ) === 0 );
check( 'css: the dashboard rule uses .vhd', strpos( vance_hub_brand_css( 'vance-hub-dashboard', $ALL_CHANGED ), '.vhd{' ) === 0 );
check( 'css: the dashboard-tabs rule uses body.vhd-tabs', strpos( vance_hub_brand_css( 'vance-hub-dashboard-tabs', $ALL_CHANGED ), 'body.vhd-tabs{' ) === 0 );
check( 'css: the community rule uses .vcom', strpos( vance_hub_brand_css( 'vance-hub-community', $ALL_CHANGED ), '.vcom{' ) === 0 );

// Unknown handle or colour name.
check( 'css: an unknown handle gives the empty string', vance_hub_brand_css( 'vance-hub-nope', $ALL_CHANGED ) === '' );
check( 'css: a colour name the map does not know is ignored', vance_hub_brand_css( 'vance-hub-home', array( 'pink' => '#FF00FF' ) ) === '' );

// Input text never reaches the output.
check( 'css: a CSS injection string gives no rule at all', vance_hub_brand_css( 'vance-hub-home', array( 'teal' => '#008080;}body{x:y' ) ) === '' );
check( 'css: a script tag gives no rule at all', vance_hub_brand_css( 'vance-hub-home', array( 'teal' => '<script>alert(1)</script>' ) ) === '' );
check( 'css: a value with a closing brace gives no rule at all', vance_hub_brand_css( 'vance-hub-home', array( 'teal' => '#0B6E99}body{x:y' ) ) === '' );
check( 'css: a valid colour gives exactly one closing brace (no injected block)', substr_count( $CSS_HOME, '}' ) === 1 );
check( 'css: a valid colour gives no angle bracket', strpos( $CSS_HOME, '<' ) === false && strpos( $CSS_HOME, '>' ) === false );
check( 'css: a 3-digit colour is printed as its upper-case 6-digit form', strpos( vance_hub_brand_css( 'vance-hub-home', array( 'teal' => '#0bf' ) ), '--vhub-teal:#00BBFF;' ) !== false );

/* ===================================================================== */
section( '4. The map against the stylesheets' );

// Every handle has its file and its selector. One check per handle.
check( 'map: seven handles', count( vance_hub_brand_map() ) === 7 );
check( 'map: the home handle reads assets/css/hub-home.css', file_exists( brand_css_file( 'vance-hub-home' ) ) );
check( 'map: the article handle reads assets/css/hub-article.css', file_exists( brand_css_file( 'vance-hub-article' ) ) );
check( 'map: the tool-result handle reads assets/css/hub-tool-result.css', file_exists( brand_css_file( 'vance-hub-tool-result' ) ) );
check( 'map: the header handle reads assets/css/hub-header.css', file_exists( brand_css_file( 'vance-hub-header' ) ) );
check( 'map: the dashboard handle reads assets/css/hub-dashboard.css', file_exists( brand_css_file( 'vance-hub-dashboard' ) ) );
check( 'map: the dashboard-tabs handle reads assets/css/hub-dashboard-tabs.css', file_exists( brand_css_file( 'vance-hub-dashboard-tabs' ) ) );
check( 'map: the community handle reads assets/css/hub-community.css', file_exists( brand_css_file( 'vance-hub-community' ) ) );

// Every property the map names is declared in its stylesheet's own block.
$MAP = vance_hub_brand_map();

$HOME_BLOCK = brand_block( brand_css_file( 'vance-hub-home' ), $MAP['vance-hub-home']['selector'] );
check( 'css block: the home block is found', $HOME_BLOCK !== '' );
check( 'css block: home declares --vhub-teal', strpos( $HOME_BLOCK, '--vhub-teal:' ) !== false );
check( 'css block: home declares --vhub-teal-hover', strpos( $HOME_BLOCK, '--vhub-teal-hover:' ) !== false );
check( 'css block: home declares --vhub-teal-ink', strpos( $HOME_BLOCK, '--vhub-teal-ink:' ) !== false );
check( 'css block: home declares --vhub-teal-rgb', strpos( $HOME_BLOCK, '--vhub-teal-rgb:' ) !== false );
check( 'css block: home declares --vhub-teal-heading', strpos( $HOME_BLOCK, '--vhub-teal-heading:' ) !== false );
check( 'css block: home declares --vhub-teal-soft', strpos( $HOME_BLOCK, '--vhub-teal-soft:' ) !== false );
check( 'css block: home declares --vhub-teal-pale', strpos( $HOME_BLOCK, '--vhub-teal-pale:' ) !== false );
check( 'css block: home declares --vhub-teal-mid', strpos( $HOME_BLOCK, '--vhub-teal-mid:' ) !== false );
check( 'css block: home declares --vhub-teal-bg', strpos( $HOME_BLOCK, '--vhub-teal-bg:' ) !== false );
check( 'css block: home declares --vhub-wash', strpos( $HOME_BLOCK, '--vhub-wash:' ) !== false );
check( 'css block: home declares --vhub-line', strpos( $HOME_BLOCK, '--vhub-line:' ) !== false );
check( 'css block: home declares --vhub-navy', strpos( $HOME_BLOCK, '--vhub-navy:' ) !== false );
check( 'css block: home declares --vhub-navy-rgb', strpos( $HOME_BLOCK, '--vhub-navy-rgb:' ) !== false );
check( 'css block: home declares --vhub-purple', strpos( $HOME_BLOCK, '--vhub-purple:' ) !== false );
check( 'css block: home declares --vhub-purple-ink', strpos( $HOME_BLOCK, '--vhub-purple-ink:' ) !== false );
check( 'css block: home declares --vhub-purple-tint-b', strpos( $HOME_BLOCK, '--vhub-purple-tint-b:' ) !== false );
check( 'css block: home declares --vhub-purple-line', strpos( $HOME_BLOCK, '--vhub-purple-line:' ) !== false );
check( 'css block: home declares --vhub-focus', strpos( $HOME_BLOCK, '--vhub-focus:' ) !== false );

$ARTICLE_BLOCK = brand_block( brand_css_file( 'vance-hub-article' ), $MAP['vance-hub-article']['selector'] );
check( 'css block: the article block is found', $ARTICLE_BLOCK !== '' );
check( 'css block: article declares every teal property the map names', strpos( $ARTICLE_BLOCK, '--vhub-teal:' ) !== false && strpos( $ARTICLE_BLOCK, '--vhub-teal-hover:' ) !== false && strpos( $ARTICLE_BLOCK, '--vhub-teal-ink:' ) !== false && strpos( $ARTICLE_BLOCK, '--vhub-teal-bg:' ) !== false && strpos( $ARTICLE_BLOCK, '--vhub-wash:' ) !== false && strpos( $ARTICLE_BLOCK, '--vhub-line:' ) !== false );
check( 'css block: article declares every purple property the map names', strpos( $ARTICLE_BLOCK, '--vhub-purple:' ) !== false && strpos( $ARTICLE_BLOCK, '--vhub-purple-ink:' ) !== false && strpos( $ARTICLE_BLOCK, '--vhub-purple-wash:' ) !== false && strpos( $ARTICLE_BLOCK, '--vhub-purple-tint:' ) !== false && strpos( $ARTICLE_BLOCK, '--vhub-focus:' ) !== false );
check( 'css block: article declares --vhub-navy and --vhub-navy-rgb', strpos( $ARTICLE_BLOCK, '--vhub-navy:' ) !== false && strpos( $ARTICLE_BLOCK, '--vhub-navy-rgb:' ) !== false );

$TR_BLOCK = brand_block( brand_css_file( 'vance-hub-tool-result' ), $MAP['vance-hub-tool-result']['selector'] );
check( 'css block: the tool-result block is found', $TR_BLOCK !== '' );
check( 'css block: tool-result declares its teal, wash, navy and purple-hover properties', strpos( $TR_BLOCK, '--vhub-teal:' ) !== false && strpos( $TR_BLOCK, '--vhub-teal-heading:' ) !== false && strpos( $TR_BLOCK, '--vhub-teal-pale:' ) !== false && strpos( $TR_BLOCK, '--vhub-wash:' ) !== false && strpos( $TR_BLOCK, '--vhub-navy-rgb:' ) !== false && strpos( $TR_BLOCK, '--vhub-purple-hover:' ) !== false );

$HDR_BLOCK = brand_block( brand_css_file( 'vance-hub-header' ), $MAP['vance-hub-header']['selector'] );
check( 'css block: the header block is found', $HDR_BLOCK !== '' );
check( 'css block: header declares its --vhh- teal, navy and purple properties', strpos( $HDR_BLOCK, '--vhh-teal:' ) !== false && strpos( $HDR_BLOCK, '--vhh-teal-hover:' ) !== false && strpos( $HDR_BLOCK, '--vhh-teal-ink:' ) !== false && strpos( $HDR_BLOCK, '--vhh-wash:' ) !== false && strpos( $HDR_BLOCK, '--vhh-line:' ) !== false && strpos( $HDR_BLOCK, '--vhh-navy:' ) !== false && strpos( $HDR_BLOCK, '--vhh-navy-rgb:' ) !== false && strpos( $HDR_BLOCK, '--vhh-purple:' ) !== false && strpos( $HDR_BLOCK, '--vhh-purple-ink:' ) !== false );

$DASH_BLOCK = brand_block( brand_css_file( 'vance-hub-dashboard' ), $MAP['vance-hub-dashboard']['selector'] );
check( 'css block: the dashboard block is found', $DASH_BLOCK !== '' );
check( 'css block: dashboard declares its --vhd- teal, navy and purple properties', strpos( $DASH_BLOCK, '--vhd-teal:' ) !== false && strpos( $DASH_BLOCK, '--vhd-teal-ink:' ) !== false && strpos( $DASH_BLOCK, '--vhd-teal-tint:' ) !== false && strpos( $DASH_BLOCK, '--vhd-wash:' ) !== false && strpos( $DASH_BLOCK, '--vhd-line:' ) !== false && strpos( $DASH_BLOCK, '--vhd-navy:' ) !== false && strpos( $DASH_BLOCK, '--vhd-navy-rgb:' ) !== false && strpos( $DASH_BLOCK, '--vhd-purple:' ) !== false && strpos( $DASH_BLOCK, '--vhd-purple-ink:' ) !== false && strpos( $DASH_BLOCK, '--vhd-purple-soft:' ) !== false && strpos( $DASH_BLOCK, '--vhd-purple-mist:' ) !== false );

$TABS_BLOCK = brand_block( brand_css_file( 'vance-hub-dashboard-tabs' ), $MAP['vance-hub-dashboard-tabs']['selector'] );
check( 'css block: the dashboard-tabs block is found', $TABS_BLOCK !== '' );
check( 'css block: dashboard-tabs declares the --dash- primary, border and sidebar properties', strpos( $TABS_BLOCK, '--dash-primary:' ) !== false && strpos( $TABS_BLOCK, '--dash-border:' ) !== false && strpos( $TABS_BLOCK, '--dash-sidebar:' ) !== false );
check( 'css block: dashboard-tabs declares its --vhdt- teal, navy and purple properties', strpos( $TABS_BLOCK, '--vhdt-teal:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-teal-ink:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-teal-hover:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-teal-deeper:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-teal-pale:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-teal-bg:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-wash:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-heading:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-line:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-navy:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-navy-rgb:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-navy-2:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-purple:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-purple-rgb:' ) !== false && strpos( $TABS_BLOCK, '--vhdt-purple-ink:' ) !== false );

$COM_BLOCK = brand_block( brand_css_file( 'vance-hub-community' ), $MAP['vance-hub-community']['selector'] );
check( 'css block: the community block is found', $COM_BLOCK !== '' );
check( 'css block: community declares its --vcom- teal, navy and purple properties', strpos( $COM_BLOCK, '--vcom-teal:' ) !== false && strpos( $COM_BLOCK, '--vcom-teal-dark:' ) !== false && strpos( $COM_BLOCK, '--vcom-teal-ink:' ) !== false && strpos( $COM_BLOCK, '--vcom-teal-soft:' ) !== false && strpos( $COM_BLOCK, '--vcom-teal-pale:' ) !== false && strpos( $COM_BLOCK, '--vcom-teal-mist:' ) !== false && strpos( $COM_BLOCK, '--vcom-wash:' ) !== false && strpos( $COM_BLOCK, '--vcom-bg:' ) !== false && strpos( $COM_BLOCK, '--vcom-line:' ) !== false && strpos( $COM_BLOCK, '--vcom-navy:' ) !== false && strpos( $COM_BLOCK, '--vcom-navy-rgb:' ) !== false && strpos( $COM_BLOCK, '--vcom-purple:' ) !== false && strpos( $COM_BLOCK, '--vcom-purple-dark:' ) !== false );

// The shades: every colour of a custom value is a valid #RRGGBB, and the rule is well formed.
$SH_TEAL = vance_hub_brand_shades( 'teal', '#0B6E99' );
$SH_NAVY = vance_hub_brand_shades( 'navy', '#123456' );
$SH_PURP = vance_hub_brand_shades( 'purple', '#4A2C7A' );
check( 'shades: teal makes 13 keys (base, rgb and 11 recipes)', count( $SH_TEAL ) === 13 );
check( 'shades: navy makes 3 keys (base, rgb and lift)', count( $SH_NAVY ) === 3 );
check( 'shades: purple makes 9 keys (base, rgb and 7 recipes)', count( $SH_PURP ) === 9 );
check( 'shades: the teal base is the saved colour in upper case', $SH_TEAL['base'] === '#0B6E99' );
check( 'shades: the teal rgb is the triple for rgba()', $SH_TEAL['rgb'] === '11, 110, 153' );
check( 'shades: every teal shade is a valid #RRGGBB', preg_match( '/^#[0-9A-F]{6}$/', $SH_TEAL['hover'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_TEAL['ink'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_TEAL['heading'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_TEAL['wash'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_TEAL['line'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_TEAL['pale'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_TEAL['soft'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_TEAL['mid'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_TEAL['mist'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_TEAL['bg'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_TEAL['tint'] ) === 1 );
check( 'shades: every navy and purple shade is a valid #RRGGBB', preg_match( '/^#[0-9A-F]{6}$/', $SH_NAVY['lift'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_PURP['ink'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_PURP['wash'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_PURP['tint'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_PURP['tint-b'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_PURP['soft'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_PURP['mist'] ) === 1 && preg_match( '/^#[0-9A-F]{6}$/', $SH_PURP['line'] ) === 1 );
check( 'shades: an invalid base gives no shades', vance_hub_brand_shades( 'teal', 'nope' ) === array() );
check( 'shades: an unknown colour name gives no shades', vance_hub_brand_shades( 'pink', '#0B6E99' ) === array() );

// A whole rule for a custom colour in every handle: each value is a colour or an rgb triple.
$RULE_ALL = vance_hub_brand_css( 'vance-hub-dashboard-tabs', $ALL_CHANGED );
check( 'shades: every value in the dashboard-tabs rule is a #RRGGBB or an rgb triple', preg_match( '/^body\.vhd-tabs\{(--[a-z0-9-]+:(#[0-9A-F]{6}|[0-9]{1,3}, [0-9]{1,3}, [0-9]{1,3});)+\}$/', $RULE_ALL ) === 1 );
$RULE_HOME = vance_hub_brand_css( 'vance-hub-home', $ALL_CHANGED );
check( 'shades: every value in the home rule is a #RRGGBB or an rgb triple', preg_match( '/^\.vhub\{(--[a-z0-9-]+:(#[0-9A-F]{6}|[0-9]{1,3}, [0-9]{1,3}, [0-9]{1,3});)+\}$/', $RULE_HOME ) === 1 );

/* ===================================================================== */
section( '5. Enqueueing: vance_hub_brand_enqueue()' );

// The hook: priority 30, after the stylesheets at 20 and 21.
check( 'hook: the enqueue runs on wp_enqueue_scripts at priority 30', isset( $GLOBALS['HOOKS']['wp_enqueue_scripts'][30] ) && in_array( 'vance_hub_brand_enqueue', $GLOBALS['HOOKS']['wp_enqueue_scripts'][30], true ) );
check( 'hook: the Customizer registration runs on customize_register', isset( $GLOBALS['HOOKS']['customize_register'][10] ) && in_array( 'vance_hub_brand_customize_register', $GLOBALS['HOOKS']['customize_register'][10], true ) );

brand_arrange( array(), array( 'vance-hub-home', 'vance-hub-article', 'vance-hub-tool-result', 'vance-hub-header', 'vance-hub-dashboard', 'vance-hub-dashboard-tabs', 'vance-hub-community' ) );
vance_hub_brand_enqueue();
check( 'enqueue: at the defaults no inline CSS is added, with all seven handles on the page', $GLOBALS['INLINE'] === array() );

brand_arrange( array( 'vance_hub_color_teal' => '#008080' ), array( 'vance-hub-home', 'vance-hub-article' ) );
vance_hub_brand_enqueue();
check( 'enqueue: a teal saved at its default adds nothing even when handles are on the page', $GLOBALS['INLINE'] === array() );

brand_arrange( array( 'vance_hub_color_teal' => '#0B6E99' ), array( 'vance-hub-home' ) );
vance_hub_brand_enqueue();
check( 'enqueue: a changed colour adds inline CSS to the enqueued home handle', isset( $GLOBALS['INLINE']['vance-hub-home'] ) && count( $GLOBALS['INLINE']['vance-hub-home'] ) === 1 );
check( 'enqueue: the inline CSS for home is the rule vance_hub_brand_css() gives', isset( $GLOBALS['INLINE']['vance-hub-home'][0] ) && $GLOBALS['INLINE']['vance-hub-home'][0] === vance_hub_brand_css( 'vance-hub-home', array( 'teal' => '#0B6E99', 'navy' => '#0A1929', 'purple' => '#6B489E' ) ) );
check( 'enqueue: a handle that is not enqueued gets nothing', ! isset( $GLOBALS['INLINE']['vance-hub-article'] ) && ! isset( $GLOBALS['INLINE']['vance-hub-community'] ) );

brand_arrange( array( 'vance_hub_color_teal' => '#0B6E99' ), array( 'vance-hub-article' ) );
vance_hub_brand_enqueue();
check( 'enqueue: only the enqueued article handle is given inline CSS', array_keys( $GLOBALS['INLINE'] ) === array( 'vance-hub-article' ) );

brand_arrange( array( 'vance_hub_color_teal' => '#0B6E99' ), array( 'vance-hub-header', 'vance-hub-tool-result' ) );
vance_hub_brand_enqueue();
check( 'enqueue: two enqueued handles each get one inline rule and no other', count( $GLOBALS['INLINE'] ) === 2 && count( $GLOBALS['INLINE']['vance-hub-header'] ) === 1 && count( $GLOBALS['INLINE']['vance-hub-tool-result'] ) === 1 );

brand_arrange( array( 'vance_hub_color_teal' => '#FFFF00' ), array( 'vance-hub-home' ) );
vance_hub_brand_enqueue();
check( 'enqueue: a pale saved colour adds nothing (the reader gives the default)', $GLOBALS['INLINE'] === array() );

brand_arrange( array( 'vance_hub_color_teal' => '#008080;}body{x:y' ), array( 'vance-hub-home' ) );
vance_hub_brand_enqueue();
check( 'enqueue: a CSS injection string adds nothing to the page', $GLOBALS['INLINE'] === array() );

brand_arrange( array( 'vance_hub_color_navy' => '#123456', 'vance_hub_color_purple' => '#4A2C7A' ), array( 'vance-hub-community' ) );
vance_hub_brand_enqueue();
check( 'enqueue: navy and purple changed together give one rule with both colours', isset( $GLOBALS['INLINE']['vance-hub-community'][0] ) && strpos( $GLOBALS['INLINE']['vance-hub-community'][0], '--vcom-navy:#123456;' ) !== false && strpos( $GLOBALS['INLINE']['vance-hub-community'][0], '--vcom-purple:#4A2C7A;' ) !== false );

/* ===================================================================== */
section( '6. The Customizer guard' );

// The same manager and the same registration order as hub-customizer.test.php.
$M = new SuiteCustomizeFake();
vance_hub_customize_register( $M );
vance_hub_home_customize_register( $M );
vance_hub_look_customize_register( $M );
vance_hub_brand_customize_register( $M );
vance_hub_golive_customize_register( $M );
vance_hub_article_customize_register( $M );
vance_hub_dash_features_customize( $M );
vance_hub_tool_result_customize_register( $M );
vance_hub_header_customize_register( $M );
vance_hub_community_customize_register( $M );
vance_hub_flare_plan_customize( $M );

// The settings that existed before the look and brand work: id => default. That is
// the 40 original rows, the 28 new rows, and the 25 tool card and go-live rows.
$PRE_WORK = array(
	'vance_home_layout'                 => 'classic',
	'vance_hub_show_promo'              => true,
	'vance_hub_show_sections'           => true,
	'vance_hub_show_more'               => true,
	'vance_hub_tile_1_badge'            => '',
	'vance_hub_tile_1_title'            => '',
	'vance_hub_tile_1_text'             => '',
	'vance_hub_tile_1_link'             => '',
	'vance_hub_tile_2_badge'            => '',
	'vance_hub_tile_2_title'            => '',
	'vance_hub_tile_2_text'             => '',
	'vance_hub_tile_2_link'             => '',
	'vance_hub_tile_3_badge'            => '',
	'vance_hub_tile_3_title'            => '',
	'vance_hub_tile_3_text'             => '',
	'vance_hub_tile_3_link'             => '',
	'vance_hub_tile_4_badge'            => '',
	'vance_hub_tile_4_title'            => '',
	'vance_hub_tile_4_text'             => '',
	'vance_hub_tile_4_link'             => '',
	'vance_hub_header'                  => true,
	'vance_hub_member_min'              => 100,
	'vance_hub_founding_deadline'       => '',
	'vance_hub_articles'                => true,
	'vance_hub_tool_result'             => true,
	'vance_hub_show_dashboard'          => true,
	'vance_hub_dashboard_tabs'          => true,
	'vance_hub_dash_member_no'          => true,
	'vance_hub_dash_chart'              => true,
	'vance_hub_community'               => true,
	'vance_hub_dash_checkin'            => false,
	'vance_hub_checkin_q_pain'          => 'How is your tummy pain today?',
	'vance_hub_checkin_q_bowel'         => 'How are your bowel symptoms today?',
	'vance_hub_checkin_q_tired'         => 'How tired are you today?',
	'vance_hub_checkin_consent'         => 'I agree that the Hub stores my answers in my account. I can delete them at any time.',
	'vance_hub_checkin_note'            => 'This is a record for you. It is not medical advice. Speak to your health team if your symptoms get worse.',
	'vance_hub_dash_flare_plan'         => false,
	'vance_hub_flare_plan_urgent'       => '',
	'vance_hub_flare_plan_consent'      => 'I agree that the Hub stores my flare plan and my flare dates in my account. I can delete them at any time.',
	'vance_hub_flare_plan_note'         => 'This plan is your own record. It is not medical advice. Share it with your health team.',
	'vance_hub_block_order'             => 'tools,inside,conditions,community,promo,library,sections,more,join',
	'vance_hub_count_sections'          => 3,
	'vance_hub_count_more'              => 6,
	'vance_hub_count_library'           => 4,
	'vance_hub_count_recipes'           => 2,
	'vance_hub_count_quotes'            => 4,
	'vance_hub_text_hero_pill'          => 'Your Gastro Health Community',
	'vance_hub_text_hero_title'         => 'What do you need help with',
	'vance_hub_text_hero_accent'        => 'today?',
	'vance_hub_text_hero_sub'           => 'Take the Gastro Self-Assessment, plan meals, read the science and get plain answers from our VANCE-Ai.',
	'vance_hub_text_survey_label'       => 'Gastro Self-Assessment',
	'vance_hub_text_survey_title'       => 'Not sure where to start?',
	'vance_hub_text_survey_text'        => 'Answer a few short questions about you and your gut. The Hub then points you to the guides, tools and recipes that fit.',
	'vance_hub_text_survey_button'      => 'Start the survey',
	'vance_hub_text_fact_1_title'       => 'Free Forever',
	'vance_hub_text_fact_1_text'        => 'Every tool available 24/7',
	'vance_hub_text_fact_2_title'       => 'Activity driven',
	'vance_hub_text_fact_2_text'        => 'Save searches, answers and results.',
	'vance_hub_text_fact_3_title'       => 'Verified Science',
	'vance_hub_text_fact_3_text'        => 'Every clinical source referenced.',
	'vance_hub_text_join_pill'          => 'Membership is free',
	'vance_hub_text_join_title'         => 'Your place is ready.',
	'vance_hub_text_join_text'          => 'Members keep their survey answers, tool results and meal plans. They can save articles and notes, and their VANCE-Ai chats stay in their dashboard.',
	'vance_hub_text_join_card'          => 'Take your place',
	'vance_hub_text_join_button'        => 'Create my free account',
	'vance_hub_text_member_title'       => 'Pick up where you left off.',
	'vance_hub_text_member_text'        => 'Your saved articles, notes, results and chats are in your dashboard.',
	'vance_hub_text_member_button'      => 'Go to My Hub',
	'vance_hub_tool_1_pick'             => 'survey',
	'vance_hub_tool_1_title'            => '',
	'vance_hub_tool_1_text'             => '',
	'vance_hub_tool_1_button'           => '',
	'vance_hub_tool_1_link'             => '',
	'vance_hub_tool_1_image'            => 0,
	'vance_hub_tool_2_pick'             => 'nutrition',
	'vance_hub_tool_2_title'            => '',
	'vance_hub_tool_2_text'             => '',
	'vance_hub_tool_2_button'           => '',
	'vance_hub_tool_2_link'             => '',
	'vance_hub_tool_2_image'            => 0,
	'vance_hub_tool_3_pick'             => 'recipes',
	'vance_hub_tool_3_title'            => '',
	'vance_hub_tool_3_text'             => '',
	'vance_hub_tool_3_button'           => '',
	'vance_hub_tool_3_link'             => '',
	'vance_hub_tool_3_image'            => 0,
	'vance_hub_tool_4_pick'             => 'discounts',
	'vance_hub_tool_4_title'            => '',
	'vance_hub_tool_4_text'             => '',
	'vance_hub_tool_4_button'           => '',
	'vance_hub_tool_4_link'             => '',
	'vance_hub_tool_4_image'            => 0,
	'vance_hub_golive_at'               => '',
);

foreach ( $PRE_WORK as $id => $default ) {
	check( "guard: $id is still registered with its default", isset( $M->settings[ $id ] ) && $M->settings[ $id ]['default'] === $default );
}
check( 'guard: the pre-work table has the 93 settings that existed before this work', count( $PRE_WORK ) === 93 );

// 125 and 122 from the look and brand work, plus the 24 flare plan section settings and controls.
check( 'guard: 149 settings are registered in total', count( $M->settings ) === 149 );
check( 'guard: 146 controls are registered in total', count( $M->controls ) === 146 );
check( 'guard: 12 sections are registered in total', count( $M->sections ) === 12 );
check( 'guard: exactly one panel is registered', count( $M->panels ) === 1 );

// The brand defaults, as settings: the three colours are at the stylesheet defaults.
check( 'guard: the teal setting defaults to #008080', $M->settings['vance_hub_color_teal']['default'] === '#008080' );
check( 'guard: the navy setting defaults to #0A1929', $M->settings['vance_hub_color_navy']['default'] === '#0A1929' );
check( 'guard: the purple setting defaults to #6B489E', $M->settings['vance_hub_color_purple']['default'] === '#6B489E' );
check( 'guard: each brand colour has a validator as well as a sanitiser', $M->settings['vance_hub_color_teal']['validate_callback'] === 'vance_hub_validate_brand_color' && $M->settings['vance_hub_color_navy']['validate_callback'] === 'vance_hub_validate_brand_color' && $M->settings['vance_hub_color_purple']['validate_callback'] === 'vance_hub_validate_brand_color' );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
