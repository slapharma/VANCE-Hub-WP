<?php
/**
 * Tests for inc/hub-preview.php: VanceHubClub preview mode, and the five
 * readers that ask it what a request wants (hub-layout, hub-article,
 * hub-dashboard, hub-header, hub-tool-result), plus the admin screen box
 * (hub-admin.php renders it).
 *
 * Same shape as hub-admin.test.php: WordPress stubs, a bag of globals the stubs
 * read, check() lines, and exit 1 on any failure. Nothing here loads WordPress.
 * The real files are loaded, so the code under test is the code that ships.
 *
 * WHAT IS COVERED
 *   0  pins: option key, cookie name, share length, meta key, token fixture
 *   1  vance_hub_preview_pick(): the precedence rule, pure
 *   2  vance_hub_preview_query(): admin ?vhh_layout= wins, arrays and visitor
 *      queries ignored, preview mode second, nothing third
 *   3  the share token: landing, valid cookie on, wrong HMAC off, raw token as
 *      cookie off, expired off, token shape off, rotation kills the old cookie,
 *      sharing off kills the cookie, a bad ?vhh_preview sets nothing
 *   4  the redirect after landing and the exit link stay on this site; crafted
 *      paths cannot reach another host
 *   5  preview grants no capability: a share visitor stays logged out
 *   6  the admin flag and the admin actions: nonce, edit_theme_options, the
 *      back address round trip
 *   7  nocache, noindex, the LiteSpeed vary list, and the hook registrations
 *   8  the admin box (token shown to an admin only, once), the admin bar item,
 *      the banner, the banner's style
 *   9  every *_active() reader turns on under preview and off without it
 *  10  wiring: no reader reads $_GET['vhh_layout'] itself; hub-preview.php is
 *      loaded before the readers; the HMAC compare is hash_equals()
 *  11  regression checks for three fixed bugs (A, B, C): all must pass
 *
 * NOT COVERED, AND WHY
 *   The bytes of Set-Cookie and X-Robots-Tag. PHP CLI keeps no header list
 *   (headers_list() is empty), so those are checked by static inspection only,
 *   and the checks say so. The cookie itself is checked through the value a
 *   browser would send back (the $_COOKIE round trip), and through the redirect
 *   that follows the cookie write.
 */

// Output is buffered for the whole run. Without this, the section lines below
// would send headers, headers_sent() would be true, and nocache_headers() would
// be skipped exactly as it is after the first byte of a real page. WordPress
// sends nothing before the page, so the suite must not either.
ob_start();

define( 'ABSPATH', true );
define( 'YEAR_IN_SECONDS', 31536000 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'COOKIEPATH', '/' );
date_default_timezone_set( 'UTC' );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

// A live share token: 32 letters and digits, as wp_generate_password( 32, false, false ) makes.
define( 'TOK', 'AbCdEfGhIjKlMnOpQrStUvWxYz012345' );
// A second, different live token, for rotation.
define( 'TOK2', '987654321zyxwvutsrqponmlkjihgfed' );
// The auth salt the cookie HMAC uses (wp_salt( 'auth' ) in the stub).
define( 'SALT', 'test-auth-salt' );

/* ---- exceptions the stubs throw where WordPress would stop the request ----- */

/** wp_safe_redirect() then exit: the stub throws so the test can read the address. */
class RedirectStop extends Exception {
	public $location;
	public function __construct( $location ) {
		parent::__construct( 'redirect' );
		$this->location = $location;
	}
}
/** wp_die(): the handler stops here. */
class WpDieStop extends Exception {}
/** check_admin_referer() failed: WordPress dies here. */
class NonceFail extends Exception {}

/* ---- state the stubs read; reset_request() and reset_all() set it ---------- */

$GLOBALS['HOOKS'] = array();

/** Clears the request, the user and the stored state. Leaves the static preview mode alone. */
function clear_request() {
	$_GET     = array();
	$_POST    = array();
	$_REQUEST = array();
	$_COOKIE  = array();
	$_SERVER['REQUEST_URI']    = '/';
	$_SERVER['REQUEST_METHOD'] = 'GET';

	$GLOBALS['IS_ADMIN']         = false;
	$GLOBALS['DOING_AJAX']       = false;
	$GLOBALS['CUSTOMIZE_PREVIEW'] = false;
	$GLOBALS['IS_SINGULAR_POST'] = false;
	$GLOBALS['PAGE_SLUG']        = '';
	$GLOBALS['PAGE_TEMPLATE']    = '';
	$GLOBALS['USER']             = 0;
	$GLOBALS['CAPS']             = array();
	$GLOBALS['UMETA']            = array();
	$GLOBALS['OPT']              = array( 'date_format' => 'j F Y', 'time_format' => 'H:i' );
	$GLOBALS['OPT_AUTOLOAD']     = array();
	$GLOBALS['MODS']             = array();
	$GLOBALS['SALT']             = SALT;
	$GLOBALS['TOKENS']           = array();
	$GLOBALS['PASSWORD_CALLS']   = array();
	$GLOBALS['REDIRECTS']        = array();
	$GLOBALS['DIES']             = array();
	$GLOBALS['NOCACHE_CALLS']    = 0;
	$GLOBALS['COOKIE_WRITES']    = array();
	$GLOBALS['DO_ACTIONS']       = array();
	$GLOBALS['ENQUEUED']         = array();
	$GLOBALS['REGISTERED']       = array();
	$GLOBALS['INLINE']           = array();
	$GLOBALS['THEME_DIR']        = $GLOBALS['THEME'];
}

/** A request with nothing in it: a logged-out visitor, preview mode off. */
function reset_all() {
	clear_request();
	vance_hub_preview_mode( '' );
}

/* ---- WordPress stubs ------------------------------------------------------- */

function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['HOOKS'][ $tag ][ $priority ][] = $callback;
	return true;
}
function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	return add_action( $tag, $callback, $priority, $accepted_args );
}
function do_action( $tag, ...$args ) { $GLOBALS['DO_ACTIONS'][] = $tag; }
function apply_filters( $tag, $value, ...$args ) { return $value; }

function wp_unslash( $value ) { return is_string( $value ) ? stripslashes( $value ) : $value; }
function sanitize_key( $key ) {
	// Mirrors WordPress; on an array this throws a TypeError under PHP 8, as it does there.
	$key = strtolower( $key );
	return preg_replace( '/[^a-z0-9_\-]/', '', $key );
}

function current_user_can( $cap ) { return $GLOBALS['USER'] > 0 && ! empty( $GLOBALS['CAPS'][ $cap ] ); }
function is_user_logged_in() { return $GLOBALS['USER'] > 0; }
function get_current_user_id() { return (int) $GLOBALS['USER']; }
function get_user_meta( $uid, $key = '', $single = false ) { return $GLOBALS['UMETA'][ $uid ][ $key ] ?? ''; }
function update_user_meta( $uid, $key, $value ) { $GLOBALS['UMETA'][ $uid ][ $key ] = $value; return true; }
function delete_user_meta( $uid, $key ) { unset( $GLOBALS['UMETA'][ $uid ][ $key ] ); return true; }

function get_option( $name, $default = false ) { return array_key_exists( $name, $GLOBALS['OPT'] ) ? $GLOBALS['OPT'][ $name ] : $default; }
function update_option( $name, $value, $autoload = null ) {
	$GLOBALS['OPT'][ $name ]          = $value;
	$GLOBALS['OPT_AUTOLOAD'][ $name ] = $autoload;
	return true;
}
function delete_option( $name ) { unset( $GLOBALS['OPT'][ $name ] ); return true; }

function wp_generate_password( $length = 12, $special = true, $extra = false ) {
	$GLOBALS['PASSWORD_CALLS'][] = array( $length, $special, $extra );
	if ( empty( $GLOBALS['TOKENS'] ) ) {
		throw new RuntimeException( 'wp_generate_password: no token queued by the test' );
	}
	return array_shift( $GLOBALS['TOKENS'] );
}
function wp_salt( $scheme = 'auth' ) { return 'auth' === $scheme ? $GLOBALS['SALT'] : 'other-scheme'; }

function wp_create_nonce( $action = -1 ) { return 'n:' . $action; }
function check_admin_referer( $action = -1, $arg = '_wpnonce' ) {
	if ( ! isset( $_REQUEST[ $arg ] ) || $_REQUEST[ $arg ] !== wp_create_nonce( $action ) ) {
		throw new NonceFail( (string) $action );
	}
	return 1;
}
function wp_nonce_url( $url, $action = -1 ) { return add_query_arg( '_wpnonce', wp_create_nonce( $action ), $url ); }
function wp_nonce_field( $action = -1 ) {
	echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( wp_create_nonce( $action ) ) . '" />';
}

function wp_die( $message = '', $title = '', $args = array() ) {
	$GLOBALS['DIES'][] = (int) ( $args['response'] ?? 200 );
	throw new WpDieStop( (string) $message );
}

function wp_validate_redirect( $location, $fallback = '' ) {
	// Mirrors WordPress: a protocol-relative address is read as http:, and an
	// address whose host is not this site falls back. A relative path is kept as it is.
	$location = is_string( $location ) ? trim( $location ) : '';
	if ( '' === $location ) {
		return $fallback;
	}
	if ( '//' === substr( $location, 0, 2 ) ) {
		$location = 'http:' . $location;
	}
	$parts = parse_url( $location );
	if ( false === $parts ) {
		return $fallback;
	}
	if ( ! isset( $parts['host'] ) ) {
		return $location;
	}
	if ( isset( $parts['scheme'] ) && ! in_array( $parts['scheme'], array( 'http', 'https' ), true ) ) {
		return $fallback;
	}
	return in_array( strtolower( $parts['host'] ), array( 'example.test' ), true ) ? $location : $fallback;
}
function wp_safe_redirect( $location, $status = 302, $fallback = '' ) {
	$location = wp_validate_redirect( $location, admin_url() );
	$GLOBALS['REDIRECTS'][] = array( 'location' => $location, 'status' => $status );
	throw new RedirectStop( $location );
}

function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }

function add_query_arg( $key, $value = null, $url = '' ) {
	// Both WordPress forms: (key, value, url) and (array of pairs, url). Like WordPress,
	// the new values are NOT encoded: the caller must encode them (see the WordPress
	// add_query_arg() docs). An unencoded '&' in a value therefore splits it.
	if ( is_array( $key ) ) {
		$pairs = $key;
		$url   = $value;
	} else {
		$pairs = array( $key => $value );
	}
	$parts = explode( '?', $url, 2 );
	$base  = $parts[0];
	$query = $parts[1] ?? '';
	$kept  = array();
	if ( '' !== $query ) {
		foreach ( explode( '&', $query ) as $seg ) {
			$k = urldecode( explode( '=', $seg, 2 )[0] );
			if ( ! array_key_exists( $k, $pairs ) ) {
				$kept[] = $seg;
			}
		}
	}
	foreach ( $pairs as $k => $v ) {
		$kept[] = (string) $k . '=' . (string) $v;
	}
	return $base . '?' . implode( '&', $kept );
}
function remove_query_arg( $key, $url = false ) {
	$keys  = is_array( $key ) ? $key : array( $key );
	$parts = explode( '?', $url, 2 );
	$base  = $parts[0];
	$query = $parts[1] ?? '';
	$kept  = array();
	if ( '' !== $query ) {
		foreach ( explode( '&', $query ) as $seg ) {
			$k = urldecode( explode( '=', $seg, 2 )[0] );
			if ( '' !== $seg && ! in_array( $k, $keys, true ) ) {
				$kept[] = $seg;
			}
		}
	}
	return empty( $kept ) ? $base : $base . '?' . implode( '&', $kept );
}

function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false ); }
function esc_url( $url ) {
	// The part of esc_url() that matters here: a scheme outside the allowlist is dropped.
	$url = (string) $url;
	if ( preg_match( '#^([a-z][a-z0-9+.\-]*):#i', $url, $m ) && ! in_array( strtolower( $m[1] ), array( 'http', 'https', 'mailto', 'tel' ), true ) ) {
		return '';
	}
	return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8', false );
}
function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
function esc_attr__( $text, $domain = '' ) { return esc_attr( $text ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }
function __( $text, $domain = '' ) { return $text; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_date( $format, $timestamp = null ) { return gmdate( $format, null === $timestamp ? time() : (int) $timestamp ); }
// The admin screen prints a go-live line (inc/hub-golive.php), which needs the site zone.
function wp_timezone() { return new DateTimeZone( 'UTC' ); }
function wp_timezone_string() { return 'UTC'; }

function is_admin() { return (bool) $GLOBALS['IS_ADMIN']; }
function wp_doing_ajax() { return (bool) $GLOBALS['DOING_AJAX']; }
function wp_doing_cron() { return false; }
function is_customize_preview() { return (bool) $GLOBALS['CUSTOMIZE_PREVIEW']; }
function is_ssl() { return false; }
function is_singular( $type = '' ) { return 'post' === $type && (bool) $GLOBALS['IS_SINGULAR_POST']; }
function is_page( $page = '' ) { return '' !== $page && $page === $GLOBALS['PAGE_SLUG']; }
function is_page_template( $template = '' ) { return '' !== $template && $template === $GLOBALS['PAGE_TEMPLATE']; }

function nocache_headers() { $GLOBALS['NOCACHE_CALLS']++; }
function wp_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
	$GLOBALS['REGISTERED'][ $handle ] = $src;
	return true;
}
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	$GLOBALS['ENQUEUED'][ 'style:' . $handle ] = $src;
}
function wp_add_inline_style( $handle, $data ) {
	$GLOBALS['INLINE'][ $handle ] = ( $GLOBALS['INLINE'][ $handle ] ?? '' ) . $data;
	return true;
}
function get_template_directory() { return $GLOBALS['THEME_DIR']; }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/vance-health-hub'; }
function vance_get_theme_mod( $key, $default = '' ) {
	return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
}

// The admin screen (hub-admin.php) reads these for its preview links. No published posts.
function get_posts( $args ) { return array(); }
function get_page_by_path( $slug ) { return null; }
function get_permalink( $post = 0 ) { return false; }
function add_submenu_page( $parent, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) { return ''; }

class WP_Admin_Bar {
	public $nodes = array();
	public function add_node( $node ) { $this->nodes[] = $node; }
}

/** Stand-in for WP_Customize_Manager, for hub-layout.php's and hub-header.php's register functions. */
class PreviewCustomizeFake {
	public $sections = array();
	public $settings = array();
	public $controls = array();
	public function add_section( $id, $args = array() ) { $this->sections[ $id ] = $args; }
	public function add_setting( $id, $args = array() ) { $this->settings[ $id ] = $args; }
	public function add_control( $id, $args = array() ) { $this->controls[ $id ] = $args; }
}

/* ---- a notice in the code under test is a failure, not a pass -------------- */

set_error_handler( function ( $no, $str, $file, $line ) {
	if ( ! ( error_reporting() & $no ) ) {
		return false;
	}
	throw new ErrorException( $str, 0, $no, $file, $line );
} );

/* ---- load the code under test, as functions.php does, in the same order ----- */

// Records every cookie write. The real function (guarded by function_exists in
// the theme) calls setcookie(), which PHP CLI cannot show. The static checks
// below read the real one.
$GLOBALS['COOKIE_WRITES'] = array();
function vance_hub_preview_send_cookie( $name, $value, $expires ) {
	$GLOBALS['COOKIE_WRITES'][] = array( 'name' => $name, 'value' => $value, 'expires' => (int) $expires );
}
function cookie_write( $name ) {
	foreach ( $GLOBALS['COOKIE_WRITES'] as $w ) {
		if ( $w['name'] === $name ) {
			return $w;
		}
	}
	return null;
}
require_once $THEME . '/inc/hub-preview.php';
require_once $THEME . '/inc/hub-layout.php';
require_once $THEME . '/inc/hub-home-settings.php';
require_once $THEME . '/inc/hub-look-settings.php';
require_once $THEME . '/inc/hub-golive.php';
require_once $THEME . '/inc/hub-article.php';
require_once $THEME . '/inc/hub-dashboard.php';
require_once $THEME . '/inc/hub-header.php';
require_once $THEME . '/inc/hub-tool-result.php';
require_once $THEME . '/inc/hub-admin.php';

reset_all();

/* ---- helpers --------------------------------------------------------------- */

/** The cookie a browser holds after a valid landing. Pins the HMAC format: a change must be made here too. */
function expected_cookie( $token ) {
	return hash_hmac( 'sha256', 'vance-hub-preview|' . $token, SALT );
}

/** Runs the init boot. Returns the redirect address it asked for, or null when it did not redirect. */
function run_boot() {
	try {
		vance_hub_preview_boot();
		return null;
	} catch ( RedirectStop $r ) {
		return $r->location;
	}
}

/** What a handler did: 'redirect', 'died', 'nonce', or 'no stop'. */
function outcome_of( $function_name ) {
	try {
		call_user_func( $function_name );
		return 'no stop';
	} catch ( RedirectStop $e ) {
		return 'redirect';
	} catch ( WpDieStop $e ) {
		return 'died';
	} catch ( NonceFail $e ) {
		return 'nonce';
	} catch ( Throwable $e ) {
		// Anything else the handler throws is a failure of the check, not a crash of the run.
		return 'threw ' . get_class( $e );
	}
}

function as_admin() {
	$GLOBALS['USER'] = 1;
	$GLOBALS['CAPS'] = array( 'edit_theme_options' => true, 'manage_options' => true );
}
function as_member() {
	$GLOBALS['USER'] = 2;
	$GLOBALS['CAPS'] = array();
}
function admin_flag_on( $uid = 1 ) { $GLOBALS['UMETA'][ $uid ]['_vance_hub_preview'] = '1'; }
function share_live( $token = TOK ) {
	$GLOBALS['OPT']['vance_hub_preview_share'] = array( 'token' => $token, 'expires' => time() + 14 * DAY_IN_SECONDS );
}
function share_visitor_cookie() { $_COOKIE['vance_hub_preview'] = expected_cookie( TOK ); }
/** Sets the request URI the way WordPress stores it: slashed (wp_magic_quotes), read back with wp_unslash(). */
function set_uri( $raw ) { $_SERVER['REQUEST_URI'] = addslashes( $raw ); }

function admin_box() { ob_start(); vance_hub_preview_admin_box(); return ob_get_clean(); }
function admin_screen() { ob_start(); vance_hub_admin_render(); return ob_get_clean(); }
function banner() { ob_start(); vance_hub_preview_banner(); return ob_get_clean(); }

/** The code of a PHP file with comments removed, so a commented-out call cannot pass a check. */
function code_only( $path ) {
	$out = '';
	foreach ( token_get_all( file_get_contents( $path ) ) as $t ) {
		if ( is_array( $t ) ) {
			if ( T_COMMENT === $t[0] || T_DOC_COMMENT === $t[0] ) {
				continue;
			}
			$out .= $t[1];
		} else {
			$out .= $t;
		}
	}
	return $out;
}

/** The text of one top-level function, from its signature to the closing brace in column 0. */
function function_body( $code, $name ) {
	preg_match( '/function\s+' . preg_quote( $name, '/' ) . '\s*\(.*?\n\}/s', $code, $m );
	return $m[0] ?? '';
}

$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

$PREVIEW_SRC  = code_only( $THEME . '/inc/hub-preview.php' );
$PREVIEW_FILE = $THEME . '/inc/hub-preview.php';

/* ===================================================================== */

/* ---- 0. pins and fixtures --------------------------------------------------- */
section( '0. Pins and fixtures' );

check( 'fixture: TOK is 32 letters and digits, as the generator makes', preg_match( '/^[A-Za-z0-9]{32}$/', TOK ) === 1 );
check( 'fixture: TOK2 is 32 letters and digits and differs from TOK', preg_match( '/^[A-Za-z0-9]{32}$/', TOK2 ) === 1 && TOK2 !== TOK );
check( 'pin: the share option key is vance_hub_preview_share (renaming it silently drops every live link)', vance_hub_preview_option() === 'vance_hub_preview_share' );
check( 'pin: the cookie is named vance_hub_preview', vance_hub_preview_cookie_name() === 'vance_hub_preview' );
check( 'pin: a share link lives 14 days', vance_hub_preview_share_days() === 14 );
check( 'pin: the admin flag meta key is _vance_hub_preview', vance_hub_preview_meta_key() === '_vance_hub_preview' );

/* ---- 1. the precedence rule, pure ------------------------------------------- */
section( '1. vance_hub_preview_pick(): precedence, pure' );

check( 'pick: an explicit hub wins when preview is off', vance_hub_preview_pick( 'hub', false ) === 'hub' );
check( 'pick: an explicit classic wins when preview is on', vance_hub_preview_pick( 'classic', true ) === 'classic' );
check( 'pick: an explicit hub with preview on is hub', vance_hub_preview_pick( 'hub', true ) === 'hub' );
check( 'pick: an explicit classic with preview off is classic', vance_hub_preview_pick( 'classic', false ) === 'classic' );
check( 'pick: no explicit value and preview on is hub', vance_hub_preview_pick( '', true ) === 'hub' );
check( 'pick: no explicit value and preview off is the empty string (saved switches decide)', vance_hub_preview_pick( '', false ) === '' );
check( 'pick: an unknown explicit value falls through to preview on (hub)', vance_hub_preview_pick( 'banana', true ) === 'hub' );
check( 'pick: an unknown explicit value falls through to preview off (empty)', vance_hub_preview_pick( 'banana', false ) === '' );

/* ---- 2. the request resolver ------------------------------------------------ */
section( '2. vance_hub_preview_query(): what the request asks for' );

reset_all(); as_admin();
$_GET['vhh_layout'] = 'hub';
check( 'query: admin ?vhh_layout=hub with preview off is hub', vance_hub_preview_query() === 'hub' );

reset_all(); as_admin(); admin_flag_on(); run_boot();
$_GET['vhh_layout'] = 'classic';
check( 'query: admin ?vhh_layout=classic wins over the admin preview flag', vance_hub_preview_query() === 'classic' );

reset_all(); as_admin(); admin_flag_on(); run_boot();
check( 'query: admin flag on with no query is hub', vance_hub_preview_query() === 'hub' );

reset_all(); as_admin();
$_GET['vhh_layout'] = 'HUB';
check( 'query: the admin value is sanitised (HUB is hub)', vance_hub_preview_query() === 'hub' );

reset_all(); as_admin();
$_GET['vhh_layout'] = 'banana';
check( 'query: an unknown admin value with preview off is empty', vance_hub_preview_query() === '' );

reset_all(); as_admin();
$_GET['vhh_layout'] = '';
check( 'query: an empty admin value with preview off is empty', vance_hub_preview_query() === '' );

reset_all(); as_admin();
$_GET['vhh_layout'] = array( 'classic' );
check( 'query: an array admin value with preview off is ignored (empty, no TypeError)', vance_hub_preview_query() === '' );

reset_all(); as_admin(); admin_flag_on(); run_boot();
$_GET['vhh_layout'] = array( 'classic' );
check( 'query: an array admin value with the admin flag on is ignored (preview decides: hub)', vance_hub_preview_query() === 'hub' );

reset_all();
$_GET['vhh_layout'] = 'hub';
check( 'query: a logged-out visitor ?vhh_layout=hub is ignored (empty)', vance_hub_preview_query() === '' );

reset_all(); share_live(); share_visitor_cookie(); run_boot();
$_GET['vhh_layout'] = 'classic';
check( 'query: a share visitor ?vhh_layout=classic is ignored (the share preview decides: hub)', vance_hub_preview_query() === 'hub' );

reset_all(); as_member();
$_GET['vhh_layout'] = 'hub';
check( 'query: a member (no edit_theme_options) ?vhh_layout=hub is ignored (empty)', vance_hub_preview_query() === '' );

reset_all(); as_member(); admin_flag_on( 2 ); run_boot();
$_GET['vhh_layout'] = 'hub';
check( 'query: a member with a stale admin flag gets no preview at all (empty)', vance_hub_preview_query() === '' && vance_hub_preview_mode() === '' );

reset_all();
check( 'query: nothing in the request gives the empty string', vance_hub_preview_query() === '' );

reset_all(); share_live(); share_visitor_cookie(); run_boot();
check( 'query: a share visitor with no query is hub', vance_hub_preview_query() === 'hub' );

/* ---- 3. the share token --------------------------------------------------- */
section( '3. The share token: landing, cookie, rotation, sharing off, expiry' );

reset_all(); share_live();
set_uri( '/?vhh_preview=' . TOK );
$_GET['vhh_preview'] = TOK;
$LOC = run_boot();
check( 'landing: a valid token redirects to the same page without the token', $LOC === '/' );
check( 'landing: the landing redirect is a 302', ( $GLOBALS['REDIRECTS'][0]['status'] ?? 0 ) === 302 );
check( 'landing: the landing response is not cached', $GLOBALS['NOCACHE_CALLS'] === 1 );

reset_all(); share_live(); share_visitor_cookie();
$LOC = run_boot();
check( 'cookie on: a valid unexpired cookie puts the request in share preview', vance_hub_preview_mode() === 'share' && vance_hub_preview_on() );
check( 'cookie on: a valid cookie does not redirect', $LOC === null );

reset_all(); share_live();
$_COOKIE['vance_hub_preview'] = str_repeat( '0', 64 );
run_boot();
check( 'cookie off: a wrong HMAC of the right length does nothing', vance_hub_preview_mode() === '' );

reset_all(); share_live();
$_COOKIE['vance_hub_preview'] = hash_hmac( 'sha256', 'vance-hub-preview|' . TOK, 'some-other-salt' );
run_boot();
check( 'cookie off: an HMAC made with another secret key does nothing', vance_hub_preview_mode() === '' );

reset_all(); share_live();
$_COOKIE['vance_hub_preview'] = TOK;
run_boot();
check( 'cookie off: the raw token used as the cookie does nothing (the cookie must be the HMAC)', vance_hub_preview_mode() === '' );

reset_all(); $GLOBALS['OPT']['vance_hub_preview_share'] = array( 'token' => TOK, 'expires' => time() - 1 );
$_COOKIE['vance_hub_preview'] = expected_cookie( TOK );
run_boot();
check( 'expired: a valid cookie for a link that expired one second ago is off', vance_hub_preview_mode() === '' );

reset_all(); $GLOBALS['OPT']['vance_hub_preview_share'] = array( 'token' => TOK, 'expires' => time() );
$_COOKIE['vance_hub_preview'] = expected_cookie( TOK );
run_boot();
check( 'expired: a link whose expiry equals now is already off (not live)', vance_hub_preview_mode() === '' );

reset_all(); $GLOBALS['OPT']['vance_hub_preview_share'] = array( 'token' => str_repeat( 'a', 31 ), 'expires' => time() + DAY_IN_SECONDS );
$_COOKIE['vance_hub_preview'] = expected_cookie( str_repeat( 'a', 31 ) );
run_boot();
check( 'shape: a stored token of 31 characters is not a token, so its cookie is off', vance_hub_preview_mode() === '' );

reset_all(); $GLOBALS['OPT']['vance_hub_preview_share'] = array( 'token' => str_repeat( 'a', 33 ), 'expires' => time() + DAY_IN_SECONDS );
$_COOKIE['vance_hub_preview'] = expected_cookie( str_repeat( 'a', 33 ) );
run_boot();
check( 'shape: a stored token of 33 characters is not a token, so its cookie is off', vance_hub_preview_mode() === '' );

reset_all(); $GLOBALS['OPT']['vance_hub_preview_share'] = array( 'token' => 'AbCdEfGhIjKlMnOpQrStUvWxYz01234-', 'expires' => time() + DAY_IN_SECONDS );
$_COOKIE['vance_hub_preview'] = expected_cookie( 'AbCdEfGhIjKlMnOpQrStUvWxYz01234-' );
run_boot();
check( 'shape: a stored token with a non-alphanumeric character is off', vance_hub_preview_mode() === '' );

reset_all(); share_live( TOK );
share_visitor_cookie();
share_live( TOK2 );
run_boot();
check( 'rotation: the cookie of the old token is off once the link is replaced', vance_hub_preview_mode() === '' );

reset_all(); share_live( TOK2 );
$_COOKIE['vance_hub_preview'] = expected_cookie( TOK2 );
run_boot();
check( 'rotation: the cookie of the new token is on', vance_hub_preview_mode() === 'share' );

reset_all(); share_live();
share_visitor_cookie();
unset( $GLOBALS['OPT']['vance_hub_preview_share'] );
run_boot();
check( 'sharing off: the cookie stops working once the link is deleted', vance_hub_preview_mode() === '' );

reset_all(); share_live();
set_uri( '/?vhh_preview=WRONG' );
$_GET['vhh_preview'] = 'WRONG';
$LOC = run_boot();
check( 'bad token: a wrong ?vhh_preview does not redirect (no cookie is written before the redirect)', $LOC === null && $GLOBALS['REDIRECTS'] === array() );
check( 'bad token: a wrong ?vhh_preview leaves preview off', vance_hub_preview_mode() === '' );

reset_all(); $GLOBALS['OPT']['vance_hub_preview_share'] = array( 'token' => TOK, 'expires' => time() - 1 );
set_uri( '/?vhh_preview=' . TOK );
$_GET['vhh_preview'] = TOK;
$LOC = run_boot();
check( 'bad token: the right token of an expired link does not land', $LOC === null && vance_hub_preview_mode() === '' );

reset_all(); share_live();
set_uri( '/?vhh_preview=' . TOK );
$_GET['vhh_preview'] = array( TOK );
$LOC = run_boot();
check( 'bad token: an array in ?vhh_preview does not land (no TypeError, no redirect)', $LOC === null );

reset_all(); share_live();
set_uri( '/?vhh_preview=' );
$_GET['vhh_preview'] = '';
$LOC = run_boot();
check( 'bad token: an empty ?vhh_preview does not land', $LOC === null );

reset_all(); share_live();
$_SERVER['REQUEST_METHOD'] = 'POST';
set_uri( '/?vhh_preview=' . TOK );
$_GET['vhh_preview'] = TOK;
$LOC = run_boot();
check( 'landing: a POST carrying the token does not land (GET only)', $LOC === null );

reset_all(); share_live();
$GLOBALS['IS_ADMIN'] = true;
set_uri( '/wp-admin/?vhh_preview=' . TOK );
$_GET['vhh_preview'] = TOK;
$LOC = run_boot();
check( 'landing: a request in wp-admin does not land', $LOC === null );

reset_all(); share_live();
set_uri( '/knowledge/?vhh_preview_exit=1' );
$_GET['vhh_preview_exit'] = '1';
$LOC = run_boot();
check( 'exit: the exit link without a cookie does nothing (no redirect)', $LOC === null );

reset_all(); share_live(); share_visitor_cookie();
set_uri( '/knowledge/?vhh_preview_exit=1' );
$_GET['vhh_preview_exit'] = '1';
$LOC = run_boot();
check( 'exit: the exit link clears the cookie and redirects without the exit argument', $LOC === '/knowledge/' );

reset_all(); share_live();
$_COOKIE['vance_hub_preview'] = 'not-a-valid-cookie';
run_boot();
check( 'dead cookie: a cookie that no longer matches is cleared, and the response is not cached', $GLOBALS['NOCACHE_CALLS'] === 1 && vance_hub_preview_mode() === '' );

reset_all(); share_live(); share_visitor_cookie();
run_boot();
check( 'live cookie: boot itself does not mark the response uncacheable (template_redirect does)', $GLOBALS['NOCACHE_CALLS'] === 0 );

/* ---- 3s. static checks on the token code ------------------------------------- */
section( '3s. Static: the HMAC compare, the cookie value and the cookie flags' );

$COOKIE_OK = function_body( $PREVIEW_SRC, 'vance_hub_preview_cookie_ok' );
$TOKEN_OK  = function_body( $PREVIEW_SRC, 'vance_hub_preview_token_ok' );
$BOOT      = function_body( $PREVIEW_SRC, 'vance_hub_preview_boot' );
$SETCOOKIE = function_body( $PREVIEW_SRC, 'vance_hub_preview_set_cookie' );

check( 'static: the cookie check body was found (guards the next checks against an empty match)', strlen( $COOKIE_OK ) > 0 && strlen( $BOOT ) > 0 );
check( 'static: the cookie check compares with hash_equals()', strpos( $COOKIE_OK, 'hash_equals(' ) !== false );
check( 'static: the cookie check does not compare the cookie with ===', strpos( $COOKIE_OK, '=== $cookie' ) === false && strpos( $COOKIE_OK, '$cookie ===' ) === false );
check( 'static: the token check compares with hash_equals()', strpos( $TOKEN_OK, 'hash_equals(' ) !== false );
check( 'static: the boot writes the HMAC to the cookie, never the token', preg_match( '/vance_hub_preview_set_cookie\(\s*vance_hub_preview_cookie_value\(/', $BOOT ) === 1 );
preg_match( '/function vance_hub_preview_send_cookie\(.*?\r?\n\t\}\r?\n/s', $PREVIEW_SRC, $SC_M );
$SENDCOOKIE = $SC_M[0] ?? '';
check( 'static: the cookie writer was found and calls setcookie()', strpos( $SENDCOOKIE, 'setcookie(' ) !== false );
check( 'static: the cookie is HttpOnly and SameSite=Lax', strpos( $SENDCOOKIE, "'httponly' => true" ) !== false && strpos( $SENDCOOKIE, "'samesite' => 'Lax'" ) !== false );
check( 'static: set_cookie writes through the writer for both cookie names', substr_count( $SETCOOKIE, 'vance_hub_preview_send_cookie(' ) === 2 && strpos( $SETCOOKIE, 'vance_hub_preview_vary_cookie_name()' ) !== false );

/* ---- 4. the redirect after landing stays on this site ------------------------ */
section( '4. The redirect after landing: same site, no open redirect' );

check( 'safe path: an empty path becomes /', vance_hub_preview_safe_path( '' ) === '/' );
check( 'safe path: a path with no leading slash becomes /', vance_hub_preview_safe_path( 'shop' ) === '/' );
check( 'safe path: / stays /', vance_hub_preview_safe_path( '/' ) === '/' );
check( 'safe path: a path with a query is kept as it is', vance_hub_preview_safe_path( '/knowledge/?a=1' ) === '/knowledge/?a=1' );
check( 'safe path: a protocol-relative path becomes /', vance_hub_preview_safe_path( '//evil.example/x' ) === '/' );
check( 'safe path: a backslash path becomes /', vance_hub_preview_safe_path( '/\\evil.example/' ) === '/' );
check( 'safe path: an absolute address becomes /', vance_hub_preview_safe_path( 'https://evil.example/' ) === '/' );
check( 'safe path: a newline in the path becomes /', vance_hub_preview_safe_path( "/a\nb" ) === '/' );
check( 'safe path: a NUL byte in the path becomes /', vance_hub_preview_safe_path( "/a\x00b" ) === '/' );
check( 'safe path: a DEL character in the path becomes /', vance_hub_preview_safe_path( "/a\x7Fb" ) === '/' );
check( 'safe path: null becomes /', vance_hub_preview_safe_path( null ) === '/' );
check( 'safe path: an array becomes /', vance_hub_preview_safe_path( array( '/x' ) ) === '/' );
check( 'safe path: a non-ASCII path is kept (no false rejection of accented slugs)', vance_hub_preview_safe_path( "/caf\u{e9}/" ) === "/caf\u{e9}/" );

reset_all(); share_live();
set_uri( '//evil.example/x?vhh_preview=' . TOK );
$_GET['vhh_preview'] = TOK;
$LOC = run_boot();
check( 'crafted landing: //evil.example/... lands on /', $LOC === '/' );
check( 'crafted landing: //evil.example/... never redirects to another host (this site or no host)', in_array( parse_url( (string) $LOC, PHP_URL_HOST ), array( null, 'example.test' ), true ) );

reset_all(); share_live();
set_uri( '/\\evil.example/?vhh_preview=' . TOK );
$_GET['vhh_preview'] = TOK;
$LOC = run_boot();
check( 'crafted landing: a backslash path lands on / and never on the other host', $LOC === '/' && parse_url( $LOC, PHP_URL_HOST ) === null );

reset_all(); share_live();
set_uri( 'https://evil.example/?vhh_preview=' . TOK );
$_GET['vhh_preview'] = TOK;
$LOC = run_boot();
check( 'crafted landing: an absolute address in the request lands on /', $LOC === '/' );

reset_all(); share_live();
set_uri( "/a\r\nSet-Cookie: x?vhh_preview=" . TOK );
$_GET['vhh_preview'] = TOK;
$LOC = run_boot();
check( 'crafted landing: a CRLF in the request path lands on /', $LOC === '/' );

reset_all(); share_live();
set_uri( '/knowledge/?a=1&vhh_preview=' . TOK . '&b=2' );
$_GET['vhh_preview'] = TOK;
$LOC = run_boot();
check( 'landing: the token argument is removed and the other arguments stay in order', $LOC === '/knowledge/?a=1&b=2' );

reset_all(); share_live();
set_uri( '/?vhh_preview=' . TOK );
$_GET['vhh_preview'] = TOK;
$LOC = run_boot();
check( 'landing: a token alone on the home page lands on /', $LOC === '/' );

/* ---- 5. preview grants no capability ------------------------------------------ */
section( '5. Preview grants no capability' );

reset_all(); share_live(); share_visitor_cookie(); run_boot();
check( 'no capability: a share visitor has no edit_theme_options', current_user_can( 'edit_theme_options' ) === false );
check( 'no capability: a share visitor is still logged out', is_user_logged_in() === false );
check( 'no capability: the capability list is unchanged by the share preview', $GLOBALS['CAPS'] === array() );
check( 'no capability: the admin flag is false for a share visitor', vance_hub_preview_admin_flag() === false );

reset_all(); share_live(); share_visitor_cookie(); run_boot();
$_GET['vhh_layout'] = 'hub';
check( 'no capability: a share visitor cannot read the admin query (explicit query is empty)', vance_hub_preview_explicit_query() === '' );

reset_all(); share_live(); share_visitor_cookie(); run_boot();
$GLOBALS['PAGE_SLUG'] = 'dashboard';
check( 'no capability: the dashboard home is on for a share visitor (presentation only)', vance_hub_dashboard_active() === true );
vance_hub_dashboard_enqueue_assets();
check( 'no capability: but the dashboard assets are not loaded for a logged-out share visitor', $GLOBALS['ENQUEUED'] === array() );

reset_all(); share_live(); share_visitor_cookie(); run_boot();
$_REQUEST = array( 'state' => 'on', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_toggle' ) );
check( 'no capability: a share visitor cannot turn the admin flag on (wp_die 403, no flag written)', outcome_of( 'vance_hub_preview_handle_toggle' ) === 'died' && ! isset( $GLOBALS['UMETA'][1]['_vance_hub_preview'] ) );

reset_all(); share_live(); share_visitor_cookie(); run_boot();
$_REQUEST = array( 'state' => 'on', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_share' ) );
check( 'no capability: a share visitor cannot create a share link (wp_die 403, no option written)', outcome_of( 'vance_hub_preview_handle_share' ) === 'died' && $GLOBALS['OPT']['vance_hub_preview_share']['token'] === TOK );

reset_all(); as_member(); admin_flag_on( 2 );
check( 'no capability: a member with a stale admin flag has no admin flag (the capability decides)', vance_hub_preview_admin_flag() === false );

reset_all(); as_member(); admin_flag_on( 2 ); run_boot();
check( 'no capability: a member with a stale admin flag is not in preview mode', vance_hub_preview_mode() === '' );

/* Static: the preview code never changes who the user is. */
check( 'static: hub-preview.php never sets the current user or an auth cookie', strpos( $PREVIEW_SRC, 'wp_set_current_user' ) === false && strpos( $PREVIEW_SRC, 'wp_set_auth_cookie' ) === false && strpos( $PREVIEW_SRC, 'wp_signon' ) === false );

/* ---- 6. the admin flag and the admin actions --------------------------------- */
section( '6. The admin flag and the admin actions: nonce, capability, back address' );

reset_all(); as_admin();
$_REQUEST = array( 'state' => 'on' );
check( 'toggle: without a nonce the flag is not changed (the nonce check stops it)', outcome_of( 'vance_hub_preview_handle_toggle' ) === 'nonce' && ! isset( $GLOBALS['UMETA'][1]['_vance_hub_preview'] ) );

reset_all(); as_admin();
$_REQUEST = array( 'state' => 'on', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_share' ) );
check( 'toggle: a nonce made for another action is rejected', outcome_of( 'vance_hub_preview_handle_toggle' ) === 'nonce' && ! isset( $GLOBALS['UMETA'][1]['_vance_hub_preview'] ) );

reset_all(); as_admin();
$_REQUEST = array( 'state' => 'on', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_toggle' ) );
check( 'toggle: with a valid nonce, state=on sets the flag to 1', outcome_of( 'vance_hub_preview_handle_toggle' ) === 'redirect' && ( $GLOBALS['UMETA'][1]['_vance_hub_preview'] ?? '' ) === '1' );

reset_all(); as_admin(); admin_flag_on();
$_REQUEST = array( 'state' => 'off', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_toggle' ) );
outcome_of( 'vance_hub_preview_handle_toggle' );
check( 'toggle: with a valid nonce, state=off removes the flag', ! isset( $GLOBALS['UMETA'][1]['_vance_hub_preview'] ) );

reset_all(); as_admin(); admin_flag_on();
$_REQUEST = array( 'state' => 'maybe', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_toggle' ) );
outcome_of( 'vance_hub_preview_handle_toggle' );
check( 'toggle: an unknown state changes nothing', ( $GLOBALS['UMETA'][1]['_vance_hub_preview'] ?? '' ) === '1' );

reset_all(); as_member();
$_REQUEST = array( 'state' => 'on', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_toggle' ) );
check( 'toggle: a member with a valid nonce gets wp_die 403 and no flag', outcome_of( 'vance_hub_preview_handle_toggle' ) === 'died' && $GLOBALS['DIES'] === array( 403 ) && ! isset( $GLOBALS['UMETA'][2]['_vance_hub_preview'] ) );

reset_all();
$_REQUEST = array( 'state' => 'on', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_toggle' ) );
check( 'toggle: a logged-out visitor gets wp_die 403 and no flag', outcome_of( 'vance_hub_preview_handle_toggle' ) === 'died' && $GLOBALS['DIES'] === array( 403 ) );

reset_all(); as_admin();
$_REQUEST = array( 'state' => 'on', 'back' => '/knowledge/?x=1', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_toggle' ) );
outcome_of( 'vance_hub_preview_handle_toggle' );
check( 'toggle: a back address on this site is returned to', $GLOBALS['REDIRECTS'][0]['location'] === '/knowledge/?x=1' );

reset_all(); as_admin();
$_REQUEST = array( 'state' => 'on', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_toggle' ) );
outcome_of( 'vance_hub_preview_handle_toggle' );
check( 'toggle: with no back address it returns to the VanceHubClub admin page', $GLOBALS['REDIRECTS'][0]['location'] === 'https://example.test/wp-admin/admin.php?page=vance-hub-club' );

reset_all(); as_admin();
$_REQUEST = array( 'state' => 'on', 'back' => '//evil.example/x', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_toggle' ) );
outcome_of( 'vance_hub_preview_handle_toggle' );
check( 'toggle: a protocol-relative back address is not followed off site (this site or no host)', in_array( parse_url( (string) $GLOBALS['REDIRECTS'][0]['location'], PHP_URL_HOST ), array( null, 'example.test' ), true ) );

reset_all(); as_admin();
$_REQUEST = array( 'state' => 'on', 'back' => 'https://evil.example/', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_toggle' ) );
outcome_of( 'vance_hub_preview_handle_toggle' );
check( 'toggle: an absolute back address is not followed off site', parse_url( $GLOBALS['REDIRECTS'][0]['location'], PHP_URL_HOST ) === null );

reset_all(); as_admin();
$_REQUEST = array( 'state' => 'on', 'back' => addslashes( '/\\evil.example/' ), '_wpnonce' => wp_create_nonce( 'vance_hub_preview_toggle' ) );
outcome_of( 'vance_hub_preview_handle_toggle' );
check( 'toggle: a backslash back address is not followed off site', parse_url( $GLOBALS['REDIRECTS'][0]['location'], PHP_URL_HOST ) === null );

reset_all();
check( 'toggle link: the toggle address carries the action name the handler is hooked to', strpos( vance_hub_preview_toggle_url( true ), 'action=vance_hub_preview_toggle' ) !== false && isset( $GLOBALS['HOOKS']['admin_post_vance_hub_preview_toggle'] ) );
check( 'toggle link: the toggle address carries a nonce for vance_hub_preview_toggle', strpos( vance_hub_preview_toggle_url( true ), '_wpnonce=n:vance_hub_preview_toggle' ) !== false );

reset_all(); as_admin();
$_POST    = array( 'op' => 'create' );
$_REQUEST = array( 'op' => 'create' );
check( 'share: without the share nonce no link is made', outcome_of( 'vance_hub_preview_handle_share' ) === 'nonce' && ! isset( $GLOBALS['OPT']['vance_hub_preview_share'] ) );

reset_all(); as_admin();
$GLOBALS['TOKENS'] = array( TOK );
$_POST    = array( 'op' => 'create' );
$_REQUEST = array( 'op' => 'create', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_share' ) );
$BEFORE = time();
outcome_of( 'vance_hub_preview_handle_share' );
$SHARE = $GLOBALS['OPT']['vance_hub_preview_share'] ?? array();
check( 'share: create stores a 32-character token from the generator', ( $SHARE['token'] ?? '' ) === TOK && $GLOBALS['PASSWORD_CALLS'] === array( array( 32, false, false ) ) );
check( 'share: create sets the expiry 14 days ahead (within 5 seconds)', abs( ( $SHARE['expires'] ?? 0 ) - ( $BEFORE + 14 * DAY_IN_SECONDS ) ) <= 5 );
check( 'share: create is not autoloaded', ( $GLOBALS['OPT_AUTOLOAD']['vance_hub_preview_share'] ?? true ) === false );

reset_all(); as_admin(); share_live( TOK );
share_visitor_cookie();
$GLOBALS['TOKENS'] = array( TOK2 );
$_POST    = array( 'op' => 'create' );
$_REQUEST = array( 'op' => 'create', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_share' ) );
outcome_of( 'vance_hub_preview_handle_share' );
run_boot();
check( 'share: New link kills the cookie of the old link at once', vance_hub_preview_mode() === '' && ( $GLOBALS['OPT']['vance_hub_preview_share']['token'] ?? '' ) === TOK2 );

reset_all(); as_admin(); share_live( TOK );
$GLOBALS['TOKENS'] = array( TOK2 );
$_POST    = array( 'op' => 'create' );
$_REQUEST = array( 'op' => 'create', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_share' ) );
outcome_of( 'vance_hub_preview_handle_share' );
set_uri( '/?vhh_preview=' . TOK );
$_GET['vhh_preview'] = TOK;
check( 'share: New link makes the old link unable to land', run_boot() === null );

reset_all(); as_admin(); share_live( TOK );
share_visitor_cookie();
$_POST    = array( 'op' => 'off' );
$_REQUEST = array( 'op' => 'off', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_share' ) );
outcome_of( 'vance_hub_preview_handle_share' );
run_boot();
check( 'share: Turn sharing off deletes the link and ends the cookie', ! isset( $GLOBALS['OPT']['vance_hub_preview_share'] ) && vance_hub_preview_mode() === '' );

reset_all(); as_admin(); share_live( TOK );
$_POST    = array( 'op' => 'nuke' );
$_REQUEST = array( 'op' => 'nuke', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_share' ) );
outcome_of( 'vance_hub_preview_handle_share' );
check( 'share: an unknown op changes no link', ( $GLOBALS['OPT']['vance_hub_preview_share']['token'] ?? '' ) === TOK );

reset_all(); as_member();
$_POST    = array( 'op' => 'create' );
$_REQUEST = array( 'op' => 'create', '_wpnonce' => wp_create_nonce( 'vance_hub_preview_share' ) );
check( 'share: a member gets wp_die 403 and no link is made', outcome_of( 'vance_hub_preview_handle_share' ) === 'died' && ! isset( $GLOBALS['OPT']['vance_hub_preview_share'] ) );

check( 'share: the share handler is hooked to admin-post under the same action name the form posts', isset( $GLOBALS['HOOKS']['admin_post_vance_hub_preview_share'] ) && strpos( $GLOBALS['HOOKS']['admin_post_vance_hub_preview_share'][10][0] ?? '', 'vance_hub_preview_handle_share' ) === 0 );

/* ---- 7. nocache, noindex, the vary list, hooks ------------------------------- */
section( '7. nocache and noindex only in preview; the LiteSpeed vary list; hooks' );

reset_all();
vance_hub_preview_template_redirect();
check( 'nocache off: a visitor page sends no nocache and fires no LiteSpeed nocache', $GLOBALS['NOCACHE_CALLS'] === 0 && ! in_array( 'litespeed_control_set_nocache', $GLOBALS['DO_ACTIONS'], true ) );

reset_all(); as_admin(); admin_flag_on(); run_boot();
vance_hub_preview_template_redirect();
check( 'nocache on (admin): the response is nocache and LiteSpeed nocache fires', $GLOBALS['NOCACHE_CALLS'] === 1 && in_array( 'litespeed_control_set_nocache', $GLOBALS['DO_ACTIONS'], true ) );

reset_all(); share_live(); share_visitor_cookie(); run_boot();
vance_hub_preview_template_redirect();
check( 'nocache on (share): the response is nocache and LiteSpeed nocache fires', $GLOBALS['NOCACHE_CALLS'] === 1 && in_array( 'litespeed_control_set_nocache', $GLOBALS['DO_ACTIONS'], true ) );

reset_all();
$ROBOTS_OFF = array( 'index' => true, 'follow' => true, 'max-image-preview' => 'large' );
check( 'noindex off: the robots directives pass through unchanged', vance_hub_preview_robots( $ROBOTS_OFF ) === $ROBOTS_OFF );

reset_all(); as_admin(); admin_flag_on(); run_boot();
$ROBOTS_ON = vance_hub_preview_robots( array( 'index' => true, 'follow' => true, 'max-image-preview' => 'large' ) );
check( 'noindex on (admin): noindex and nofollow are set, index and follow are removed', ( $ROBOTS_ON['noindex'] ?? false ) === true && ( $ROBOTS_ON['nofollow'] ?? false ) === true && ! isset( $ROBOTS_ON['index'] ) && ! isset( $ROBOTS_ON['follow'] ) );
check( 'noindex on (admin): other robots directives are kept', ( $ROBOTS_ON['max-image-preview'] ?? '' ) === 'large' );

reset_all(); share_live(); share_visitor_cookie(); run_boot();
$ROBOTS_SHARE = vance_hub_preview_robots( array( 'index' => true, 'follow' => true ) );
check( 'noindex on (share): noindex is set and index is removed', ( $ROBOTS_SHARE['noindex'] ?? false ) === true && ! isset( $ROBOTS_SHARE['index'] ) );

check( 'vary: the cookie is added to an existing list', vance_hub_preview_vary_cookies( array( 'other' ) ) === array( 'other', 'vance_hub_preview' ) );
check( 'vary: a non-array list becomes a list of just the cookie', vance_hub_preview_vary_cookies( null ) === array( 'vance_hub_preview' ) );
check( 'vary: the LiteSpeed filters both take the cookie, at the late priority', isset( $GLOBALS['HOOKS']['litespeed_vary_curr_cookies'][9999] ) && isset( $GLOBALS['HOOKS']['litespeed_vary_cookies'][9999] ) );

check( 'hook: the init boot runs at priority 1', in_array( 'vance_hub_preview_boot', $GLOBALS['HOOKS']['init'][1] ?? array(), true ) );
check( 'hook: the template_redirect nocache runs first (priority 0)', in_array( 'vance_hub_preview_template_redirect', $GLOBALS['HOOKS']['template_redirect'][0] ?? array(), true ) );
check( 'hook: the robots filter runs late (priority 99)', in_array( 'vance_hub_preview_robots', $GLOBALS['HOOKS']['wp_robots'][99] ?? array(), true ) );
check( 'hook: the admin bar item is added at priority 90', in_array( 'vance_hub_preview_admin_bar', $GLOBALS['HOOKS']['admin_bar_menu'][90] ?? array(), true ) );
check( 'hook: the banner prints in the footer at priority 100', in_array( 'vance_hub_preview_banner', $GLOBALS['HOOKS']['wp_footer'][100] ?? array(), true ) );
check( 'hook: the banner style is added on the front end', in_array( 'vance_hub_preview_enqueue', $GLOBALS['HOOKS']['wp_enqueue_scripts'][10] ?? array(), true ) );
check( 'hook: the admin flag toggle is hooked to admin-post under its action name', in_array( 'vance_hub_preview_handle_toggle', $GLOBALS['HOOKS']['admin_post_vance_hub_preview_toggle'][10] ?? array(), true ) );

$HDR = code_only( $PREVIEW_FILE );
check( 'static: the X-Robots-Tag header is sent with noindex, nofollow (headers are not observable in CLI)', strpos( $HDR, "header( 'X-Robots-Tag: noindex, nofollow', true )" ) !== false );
check( 'static: the nocache path calls nocache_headers() (headers are not observable in CLI)', strpos( function_body( $HDR, 'vance_hub_preview_nocache' ), 'nocache_headers()' ) !== false );

/* ---- 7b. the cache-key cookie (LiteSpeed _lscache_vary* prefix) ---------------- */
section( '7b. The cache-key cookie: set, healed and cleared with the share cookie' );

function expected_vary( $token ) { return substr( hash_hmac( 'sha256', 'vance-hub-preview-vary|' . $token, SALT ), 0, 16 ); }
function vary_cookie() { return '_lscache_vary_vhh'; }

check( 'vary cookie: the name is _lscache_vary_vhh', vance_hub_preview_vary_cookie_name() === '_lscache_vary_vhh' );
check( 'vary cookie: the name starts with _lscache_vary (the server varies on that prefix by itself)', strpos( vance_hub_preview_vary_cookie_name(), '_lscache_vary' ) === 0 );
check( 'vary cookie: the name is not LSCWP\'s own _lscache_vary (LSCWP reads and rewrites that one)', vance_hub_preview_vary_cookie_name() !== '_lscache_vary' );
check( 'vary cookie: the share cookie keeps its name and value (existing cookies stay valid)', vance_hub_preview_cookie_name() === 'vance_hub_preview' && vance_hub_preview_cookie_value( TOK, SALT ) === hash_hmac( 'sha256', 'vance-hub-preview|' . TOK, SALT ) );
check( 'vary cookie: the value is 16 hex characters and differs per token', preg_match( '/^[0-9a-f]{16}$/', expected_vary( TOK ) ) === 1 && vance_hub_preview_vary_value( TOK, SALT ) === expected_vary( TOK ) && vance_hub_preview_vary_value( TOK, SALT ) !== vance_hub_preview_vary_value( TOK2, SALT ) );
check( 'vary cookie: the value does not contain the token or the share cookie value', strpos( expected_vary( TOK ), TOK ) === false && expected_vary( TOK ) !== substr( expected_cookie( TOK ), 0, 16 ) );

reset_all(); share_live();
set_uri( '/?vhh_preview=' . TOK );
$_GET['vhh_preview'] = TOK;
run_boot();
$W_MAIN = cookie_write( 'vance_hub_preview' );
$W_VARY = cookie_write( vary_cookie() );
check( 'landing: writes the share cookie with the HMAC', $W_MAIN !== null && $W_MAIN['value'] === expected_cookie( TOK ) );
check( 'landing: writes the cache-key cookie with the token-derived value', $W_VARY !== null && $W_VARY['value'] === expected_vary( TOK ) );
check( 'landing: both cookies expire when the link expires', $W_MAIN !== null && $W_VARY !== null && $W_MAIN['expires'] === $W_VARY['expires'] && $W_VARY['expires'] === $GLOBALS['OPT']['vance_hub_preview_share']['expires'] );
check( 'landing: exactly two cookies are written', count( $GLOBALS['COOKIE_WRITES'] ) === 2 );

reset_all(); share_live();
$_GET['vhh_preview'] = 'WRONG';
set_uri( '/?vhh_preview=WRONG' );
run_boot();
check( 'bad token: writes no cookie of either name', $GLOBALS['COOKIE_WRITES'] === array() );

reset_all(); share_live(); run_boot();
check( 'public visitor: no cookie written, response stays cacheable (no cache fragmentation)', $GLOBALS['COOKIE_WRITES'] === array() && $GLOBALS['NOCACHE_CALLS'] === 0 && vance_hub_preview_mode() === '' );

reset_all(); share_live(); share_visitor_cookie(); $_COOKIE[ vary_cookie() ] = expected_vary( TOK ); run_boot();
check( 'holder with both cookies: preview on, nothing rewritten', vance_hub_preview_mode() === 'share' && $GLOBALS['COOKIE_WRITES'] === array() );

reset_all(); share_live(); share_visitor_cookie(); run_boot();
$W_MAIN = cookie_write( 'vance_hub_preview' );
$W_VARY = cookie_write( vary_cookie() );
check( 'holder of an old link (share cookie only): preview on, and the cache-key cookie is given', vance_hub_preview_mode() === 'share' && $W_VARY !== null && $W_VARY['value'] === expected_vary( TOK ) && $W_MAIN !== null && $W_MAIN['value'] === expected_cookie( TOK ) );

reset_all(); share_live( TOK2 ); $_COOKIE['vance_hub_preview'] = expected_cookie( TOK2 ); $_COOKIE[ vary_cookie() ] = expected_vary( TOK ); run_boot();
check( 'new-token share cookie with an old-token cache-key cookie: preview on, cache-key cookie rewritten to the new value', vance_hub_preview_mode() === 'share' && ( cookie_write( vary_cookie() )['value'] ?? '' ) === expected_vary( TOK2 ) );

reset_all(); share_live( TOK2 ); share_visitor_cookie(); $_COOKIE[ vary_cookie() ] = expected_vary( TOK ); run_boot();
check( 'rotation: old share cookie and old cache-key cookie are both cleared, preview off, response not cached', vance_hub_preview_mode() === '' && ( cookie_write( 'vance_hub_preview' )['value'] ?? 'x' ) === '' && ( cookie_write( vary_cookie() )['value'] ?? 'x' ) === '' && $GLOBALS['NOCACHE_CALLS'] === 1 );

reset_all(); share_live(); $_COOKIE[ vary_cookie() ] = 'forged-value'; run_boot();
check( 'lone or forged cache-key cookie: preview stays off, both cookies cleared, response not cached', vance_hub_preview_mode() === '' && ( cookie_write( vary_cookie() )['value'] ?? 'x' ) === '' && $GLOBALS['NOCACHE_CALLS'] === 1 );

reset_all(); share_live(); $_COOKIE['vance_hub_preview'] = expected_cookie( TOK ); $_COOKIE[ vary_cookie() ] = expected_vary( TOK );
unset( $GLOBALS['OPT']['vance_hub_preview_share'] ); run_boot();
check( 'sharing off: both cookies are cleared and the response is not cached', vance_hub_preview_mode() === '' && ( cookie_write( vary_cookie() )['value'] ?? 'x' ) === '' && $GLOBALS['NOCACHE_CALLS'] === 1 );

reset_all(); share_live(); $_COOKIE['vance_hub_preview'] = 'bad'; $_SERVER['REQUEST_METHOD'] = 'POST'; run_boot();
check( 'POST with a dead cookie: nothing is written (GET only)', $GLOBALS['COOKIE_WRITES'] === array() );

reset_all(); share_live(); share_visitor_cookie(); $_COOKIE[ vary_cookie() ] = expected_vary( TOK );
set_uri( '/knowledge/?vhh_preview_exit=1' ); $_GET['vhh_preview_exit'] = '1';
$LOC = run_boot();
check( 'exit: clears both cookies, then redirects without the argument', $LOC === '/knowledge/' && ( cookie_write( 'vance_hub_preview' )['value'] ?? 'x' ) === '' && ( cookie_write( vary_cookie() )['value'] ?? 'x' ) === '' );

reset_all(); share_live(); $_COOKIE[ vary_cookie() ] = expected_vary( TOK );
set_uri( '/?vhh_preview_exit=1' ); $_GET['vhh_preview_exit'] = '1';
$LOC = run_boot();
check( 'exit: a visitor with only the cache-key cookie can still exit', $LOC === '/' && ( cookie_write( vary_cookie() )['value'] ?? 'x' ) === '' );

reset_all(); as_admin(); admin_flag_on(); run_boot();
check( 'admin flag: writes no cookie (the admin is logged in, so LSCWP already keeps the page private)', $GLOBALS['COOKIE_WRITES'] === array() && vance_hub_preview_mode() === 'admin' );

check( 'static: the vary cookie check uses hash_equals()', strpos( function_body( $PREVIEW_SRC, 'vance_hub_preview_vary_ok' ), 'hash_equals(' ) !== false );

/* ---- 8. the admin box, the admin bar, the banner ----------------------------- */
section( '8. Admin box, admin bar and banner: who sees the token' );

reset_all(); share_live();
check( 'box: a logged-out visitor gets no box, even with a live link', admin_box() === '' );

reset_all(); share_live(); as_member();
$MEMBER_BOX = admin_box();
check( 'box: a member with no edit_theme_options gets no box', $MEMBER_BOX === '' );
check( 'box: the token is not printed to a member', strpos( $MEMBER_BOX, TOK ) === false );

reset_all(); share_live(); share_visitor_cookie(); run_boot();
$VISITOR_BOX = admin_box();
check( 'box: a share visitor gets no box and no token', $VISITOR_BOX === '' && strpos( banner(), TOK ) === false );

reset_all(); share_live(); as_admin();
$ADMIN_BOX = admin_box();
check( 'box: an admin gets the preview heading', strpos( $ADMIN_BOX, '<h2>Preview mode (you only)</h2>' ) !== false );
check( 'box: an admin with a live link sees the link in the read-only field', strpos( $ADMIN_BOX, 'value="https://example.test/?vhh_preview=' . TOK . '"' ) !== false );
check( 'box: the token is printed exactly once (the link field only)', substr_count( $ADMIN_BOX, TOK ) === 1 );
check( 'box: a live link has the Copy button', strpos( $ADMIN_BOX, 'id="vhh-preview-copy"' ) !== false && strpos( $ADMIN_BOX, '<script>' ) !== false );
check( 'box: a live link shows its expiry date line', strpos( $ADMIN_BOX, 'The link expires on' ) !== false );
check( 'box: a live link offers New link and Turn sharing off', strpos( $ADMIN_BOX, '>New link<' ) !== false && strpos( $ADMIN_BOX, '>Turn sharing off<' ) !== false );

reset_all(); as_admin();
$NONE_BOX = admin_box();
check( 'box: with no link the box says sharing is off and offers Create link', strpos( $NONE_BOX, 'Sharing is off. There is no link.' ) !== false && strpos( $NONE_BOX, '>Create link<' ) !== false );
check( 'box: with no link there is no copy script and no token', strpos( $NONE_BOX, '<script>' ) === false && strpos( $NONE_BOX, 'vhh_preview=' ) === false );

reset_all(); as_admin(); $GLOBALS['OPT']['vance_hub_preview_share'] = array( 'token' => TOK, 'expires' => time() - 60 );
$EXPIRED_BOX = admin_box();
check( 'box: an expired link says so and is not printed', strpos( $EXPIRED_BOX, 'The link expired on' ) !== false && strpos( $EXPIRED_BOX, TOK ) === false );

reset_all(); as_admin();
check( 'box: with the flag off, the box says Off and offers Turn preview on (state=on)', strpos( admin_box(), 'Turn preview on' ) !== false && strpos( admin_box(), 'state=on' ) !== false );
reset_all(); as_admin(); admin_flag_on();
$FLAG_BOX = admin_box();
check( 'box: with the flag on, the box says On and offers Turn preview off (state=off)', strpos( $FLAG_BOX, 'Turn preview off' ) !== false && strpos( $FLAG_BOX, 'state=off' ) !== false );

reset_all(); as_admin(); share_live();
$BOX_FORMS = admin_box();
check( 'box: the share forms post the action name the handler is hooked to', strpos( $BOX_FORMS, 'name="action" value="vance_hub_preview_share"' ) !== false && isset( $GLOBALS['HOOKS']['admin_post_vance_hub_preview_share'] ) );
check( 'box: the share forms carry the share nonce, not the toggle nonce', strpos( $BOX_FORMS, 'name="_wpnonce" value="n:vance_hub_preview_share"' ) !== false );

reset_all(); as_admin(); share_live();
$SCREEN_ADMIN_BOX = admin_screen();
check( 'screen: the VanceHubClub admin screen has the box for a user with edit_theme_options and manage_options', strpos( $SCREEN_ADMIN_BOX, '<h2>Preview mode (you only)</h2>' ) !== false );

reset_all(); $GLOBALS['CAPS'] = array( 'manage_options' => true );
$GLOBALS['USER'] = 3; share_live();
$SCREEN_NO_EDIT = admin_screen();
check( 'screen: a user who may manage options but not edit the theme gets no box', strpos( $SCREEN_NO_EDIT, 'Preview mode (you only)' ) === false && strpos( $SCREEN_NO_EDIT, TOK ) === false );

reset_all(); as_admin(); share_live();
$SCREEN_ADMIN = admin_screen();
check( 'screen: the token is printed once on the whole admin screen', substr_count( $SCREEN_ADMIN, TOK ) === 1 );

reset_all(); as_admin();
$WP_BAR = new WP_Admin_Bar();
vance_hub_preview_admin_bar( $WP_BAR );
check( 'admin bar: with the flag off the item reads Club preview: off and turns on', count( $WP_BAR->nodes ) === 1 && $WP_BAR->nodes[0]['title'] === 'Club preview: off' && strpos( $WP_BAR->nodes[0]['href'], 'state=on' ) !== false );

reset_all(); as_admin(); admin_flag_on();
$WP_BAR2 = new WP_Admin_Bar();
vance_hub_preview_admin_bar( $WP_BAR2 );
check( 'admin bar: with the flag on the item reads Club preview: on and turns off', count( $WP_BAR2->nodes ) === 1 && $WP_BAR2->nodes[0]['title'] === 'Club preview: on' && strpos( $WP_BAR2->nodes[0]['href'], 'state=off' ) !== false );

reset_all(); share_live(); share_visitor_cookie(); run_boot();
$WP_BAR3 = new WP_Admin_Bar();
vance_hub_preview_admin_bar( $WP_BAR3 );
check( 'admin bar: a share visitor gets no admin bar item', $WP_BAR3->nodes === array() );

reset_all();
check( 'banner: with preview off the banner prints nothing', banner() === '' );

reset_all(); share_live(); share_visitor_cookie(); set_uri( '/knowledge/?vhh_preview=' . TOK );
run_boot();
$BANNER_SHARE = banner();
check( 'banner: a share visitor sees the banner with an Exit preview link', strpos( $BANNER_SHARE, 'vhh-preview-bar' ) !== false && strpos( $BANNER_SHARE, 'Exit preview' ) !== false && strpos( $BANNER_SHARE, 'vhh_preview_exit=1' ) !== false );

reset_all(); as_admin(); admin_flag_on(); run_boot();
$BANNER_ADMIN = banner();
check( 'banner: an admin in preview sees the banner, and its exit link turns the flag off', strpos( $BANNER_ADMIN, 'Exit preview' ) !== false && strpos( $BANNER_ADMIN, 'state=off' ) !== false );

reset_all(); share_live(); share_visitor_cookie(); run_boot();
vance_hub_preview_enqueue();
check( 'banner style: the banner CSS is added inline on a preview page', strpos( $GLOBALS['INLINE']['vance-hub-preview'] ?? '', '.vhh-preview-bar{' ) !== false );

reset_all();
vance_hub_preview_enqueue();
check( 'banner style: no banner CSS on a normal page', ! isset( $GLOBALS['INLINE']['vance-hub-preview'] ) );

/* ---- 9. every *_active() reader turns on under preview and off without it ------ */
section( '9. The six readers: on under preview, off without it' );

/** Saved switches all off, except the tab look, which can only take effect with the dashboard. */
function saved_off() {
	$GLOBALS['MODS'] = array(
		'vance_home_layout'        => 'classic',
		'vance_hub_articles'       => false,
		'vance_hub_show_dashboard' => false,
		'vance_hub_dashboard_tabs' => true,
		'vance_hub_header'         => false,
		'vance_hub_tool_result'    => false,
	);
}
/** Saved switches all on. */
function saved_on() {
	$GLOBALS['MODS'] = array(
		'vance_home_layout'        => 'hub',
		'vance_hub_articles'       => true,
		'vance_hub_show_dashboard' => true,
		'vance_hub_dashboard_tabs' => true,
		'vance_hub_header'         => true,
		'vance_hub_tool_result'    => true,
	);
}

reset_all(); saved_off(); $GLOBALS['IS_SINGULAR_POST'] = true;
check( 'off: no preview and switches off: layout is off', vance_hub_layout_active() === false );
check( 'off: no preview and switches off: article is off', vance_hub_article_active() === false );
check( 'off: no preview and switches off: dashboard is off', vance_hub_dashboard_active() === false );
check( 'off: no preview and switches off: the tab look is off (its dashboard is off)', vance_hub_dashboard_tabs_active() === false );
check( 'off: no preview and switches off: header is off', vance_hub_header_active() === false );
check( 'off: no preview and switches off: result page is off', vance_hub_tool_result_active() === false );

reset_all(); saved_off(); $GLOBALS['IS_SINGULAR_POST'] = true; as_admin(); admin_flag_on(); run_boot();
check( 'admin preview: layout is on', vance_hub_layout_active() === true );
check( 'admin preview: article is on', vance_hub_article_active() === true );
check( 'admin preview: dashboard is on', vance_hub_dashboard_active() === true );
check( 'admin preview: the tab look is on', vance_hub_dashboard_tabs_active() === true );
check( 'admin preview: header is on', vance_hub_header_active() === true );
check( 'admin preview: result page is on', vance_hub_tool_result_active() === true );

reset_all(); saved_off(); $GLOBALS['IS_SINGULAR_POST'] = true; share_live(); share_visitor_cookie(); run_boot();
check( 'share preview: layout is on', vance_hub_layout_active() === true );
check( 'share preview: article is on', vance_hub_article_active() === true );
check( 'share preview: dashboard is on', vance_hub_dashboard_active() === true );
check( 'share preview: the tab look is on', vance_hub_dashboard_tabs_active() === true );
check( 'share preview: header is on', vance_hub_header_active() === true );
check( 'share preview: result page is on', vance_hub_tool_result_active() === true );

reset_all(); saved_on(); $GLOBALS['IS_SINGULAR_POST'] = true; as_admin(); admin_flag_on(); run_boot(); $_GET['vhh_layout'] = 'classic';
check( 'explicit classic beats preview: layout is off', vance_hub_layout_active() === false );
check( 'explicit classic beats preview: article is off', vance_hub_article_active() === false );
check( 'explicit classic beats preview: dashboard is off', vance_hub_dashboard_active() === false );
check( 'explicit classic beats preview: the tab look is off', vance_hub_dashboard_tabs_active() === false );
check( 'explicit classic beats preview: header is off', vance_hub_header_active() === false );
check( 'explicit classic beats preview: result page is off', vance_hub_tool_result_active() === false );

reset_all(); saved_on(); $GLOBALS['IS_SINGULAR_POST'] = true;
check( 'saved on, visitor, no preview: layout still on (preview did not break the saved switch)', vance_hub_layout_active() === true );
check( 'saved on, visitor, no preview: article still on', vance_hub_article_active() === true );
check( 'saved on, visitor, no preview: dashboard still on', vance_hub_dashboard_active() === true );
check( 'saved on, visitor, no preview: the tab look still on', vance_hub_dashboard_tabs_active() === true );
check( 'saved on, visitor, no preview: header still on', vance_hub_header_active() === true );
check( 'saved on, visitor, no preview: result page still on', vance_hub_tool_result_active() === true );

reset_all(); saved_on(); $GLOBALS['PAGE_SLUG'] = 'dashboard'; as_admin(); admin_flag_on(); run_boot();
check( 'header: on the member dashboard the header stays classic, even under admin preview', vance_hub_header_active() === false );

reset_all(); saved_off(); $GLOBALS['PAGE_TEMPLATE'] = 'page-healthcare-quiz.php'; as_admin(); admin_flag_on(); run_boot();
check( 'survey: the result page shows on the survey page under admin preview', vance_hub_tool_result_on_survey() === true );

reset_all(); saved_off(); $GLOBALS['PAGE_TEMPLATE'] = 'page-healthcare-quiz.php';
check( 'survey: the result page does not show on the survey page with preview off', vance_hub_tool_result_on_survey() === false );

reset_all(); as_admin(); admin_flag_on(); run_boot();
check( 'dashboard link: the admin flag adds nothing to the link (the flag holds on every page)', vance_hub_dashboard_preview_arg() === '' );

reset_all(); as_admin(); $_GET['vhh_layout'] = 'hub';
check( 'dashboard link: an admin ?vhh_layout=hub is carried in the link as hub', vance_hub_dashboard_preview_arg() === 'hub' );

reset_all(); share_live(); share_visitor_cookie(); run_boot(); $_GET['vhh_layout'] = 'hub';
check( 'dashboard link: a share visitor gets nothing carried in the link', vance_hub_dashboard_preview_arg() === '' );

/* ---- 10. wiring ----------------------------------------------------------------- */
section( '10. Wiring: one resolver, loaded first' );

check( 'wiring: the positive control holds (hub-preview.php does read $_GET vhh_layout, so the grep below is not vacuous)', strpos( $PREVIEW_SRC, "_GET['vhh_layout']" ) !== false );
check( 'wiring: hub-layout.php does not read $_GET vhh_layout itself', strpos( code_only( $THEME . '/inc/hub-layout.php' ), "_GET['vhh_layout']" ) === false );
check( 'wiring: hub-article.php does not read $_GET vhh_layout itself', strpos( code_only( $THEME . '/inc/hub-article.php' ), "_GET['vhh_layout']" ) === false );
check( 'wiring: hub-dashboard.php does not read $_GET vhh_layout itself', strpos( code_only( $THEME . '/inc/hub-dashboard.php' ), "_GET['vhh_layout']" ) === false );
check( 'wiring: hub-header.php does not read $_GET vhh_layout itself', strpos( code_only( $THEME . '/inc/hub-header.php' ), "_GET['vhh_layout']" ) === false );
check( 'wiring: hub-tool-result.php does not read $_GET vhh_layout itself', strpos( code_only( $THEME . '/inc/hub-tool-result.php' ), "_GET['vhh_layout']" ) === false );

$FUNCTIONS = file_get_contents( $THEME . '/functions.php' );
check( 'wiring: functions.php loads hub-preview.php before hub-layout.php and hub-admin.php', strpos( $FUNCTIONS, 'inc/hub-preview.php' ) !== false && strpos( $FUNCTIONS, 'inc/hub-preview.php' ) < strpos( $FUNCTIONS, 'inc/hub-layout.php' ) && strpos( $FUNCTIONS, 'inc/hub-preview.php' ) < strpos( $FUNCTIONS, 'inc/hub-admin.php' ) );

check( 'wiring: hub-layout.php loads hub-preview.php itself', strpos( code_only( $THEME . '/inc/hub-layout.php' ), "/hub-preview.php'" ) !== false );
check( 'wiring: hub-article.php loads hub-preview.php itself', strpos( code_only( $THEME . '/inc/hub-article.php' ), "/hub-preview.php'" ) !== false );
check( 'wiring: hub-dashboard.php loads hub-preview.php itself', strpos( code_only( $THEME . '/inc/hub-dashboard.php' ), "/hub-preview.php'" ) !== false );
check( 'wiring: hub-header.php loads hub-preview.php itself', strpos( code_only( $THEME . '/inc/hub-header.php' ), "/hub-preview.php'" ) !== false );
check( 'wiring: hub-tool-result.php loads hub-preview.php itself', strpos( code_only( $THEME . '/inc/hub-tool-result.php' ), "/hub-preview.php'" ) !== false );

/* ---- 11. Regression checks for three fixed bugs: all must pass ---------------- */
section( '11. Regression checks (bugs A, B and C, fixed)' );

// BUG A (fixed). vance_hub_preview_mode() is a function-level static. Boot used to
// set the mode on but never clear it, so a second boot in the same PHP process kept
// the old mode. Boot now starts from off. This sequence does not call reset_all(),
// because reset_all() would hide the bug.
clear_request(); share_live(); share_visitor_cookie(); run_boot();
check( 'BUG A precondition: the first request is in share preview', vance_hub_preview_on() );
clear_request();
run_boot();
check( 'BUG A: the next request in the same PHP process, with no cookie and no login, is not in preview', ! vance_hub_preview_on() );
check( 'BUG A: and it gets no preview query (the mode has not leaked into the readers)', vance_hub_preview_query() === '' );
reset_all();

// BUG B (fixed). The toggle link must carry back= encoded exactly once. WordPress
// add_query_arg() does not encode values, so the source encodes it. Build the address
// the same way the admin bar does, read its query the way PHP reads $_GET, then run
// the handler. The second case has two query args, so an unencoded '&' would split it.
reset_all(); as_admin();
$TOGGLE_URL = vance_hub_preview_toggle_url( true, '/knowledge/?x=1' );
parse_str( (string) parse_url( $TOGGLE_URL, PHP_URL_QUERY ), $TOGGLE_QUERY );
$_REQUEST = $TOGGLE_QUERY;
outcome_of( 'vance_hub_preview_handle_toggle' );
check( 'BUG B: the toggle returns to the page it came from (/knowledge/?x=1), not to the home page', ( $GLOBALS['REDIRECTS'][0]['location'] ?? '' ) === '/knowledge/?x=1' );
reset_all(); as_admin();
$TOGGLE_URL = vance_hub_preview_toggle_url( true, '/?s=gut&paged=2' );
parse_str( (string) parse_url( $TOGGLE_URL, PHP_URL_QUERY ), $TOGGLE_QUERY );
check( 'BUG B: a back path with two args stays one value, and adds no parameter of its own', ( $TOGGLE_QUERY['back'] ?? '' ) === '/?s=gut&paged=2' && ! isset( $TOGGLE_QUERY['paged'] ) && ( $TOGGLE_QUERY['state'] ?? '' ) === 'on' );
$_REQUEST = $TOGGLE_QUERY;
outcome_of( 'vance_hub_preview_handle_toggle' );
check( 'BUG B: the toggle returns to /?s=gut&paged=2 with both args', ( $GLOBALS['REDIRECTS'][0]['location'] ?? '' ) === '/?s=gut&paged=2' );

// BUG C. The banner's exit link is built from vance_hub_preview_current_path()
// called with no arguments, and that call does not strip vhh_preview (the strip
// list is empty). So a share visitor whose address still holds the token gets
// the token printed in the banner's href. The token must appear only in the
// admin box. Normal landings strip the token first, so this needs an address that
// still carries it while the cookie is valid.
reset_all(); share_live(); share_visitor_cookie(); set_uri( '/knowledge/?vhh_preview=' . TOK );
run_boot();
check( 'BUG C: the banner never prints the token, even when the address still holds it', strpos( banner(), TOK ) === false );

/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
