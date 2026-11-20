<?php
/**
 * WordPress stubs for the VanceHubClub Community suites:
 * tests/hub-community.test.php (logic) and tests/hub-community-view.test.php
 * (markup). Required by both, never run on its own.
 *
 * One in-memory store stands in for posts, comments, users, terms and the three
 * meta tables ($GLOBALS['WP']). The $wpdb stand-in answers the four SQL shapes
 * the community code issues, from the same rows, so a meta_value LIKE, a
 * GROUP BY on votes or a DISTINCT post_id reads the data the code wrote.
 *
 * Failure paths are exceptions: wp_send_json_* throws CsJsonReply, wp_die throws
 * CsDie, wp_safe_redirect throws CsRedirect. The code under test calls exit()
 * after each of them, so the exception stops the request before that line.
 *
 * Nothing here loads WordPress.
 */

define( 'ABSPATH', true );
define( 'DAY_IN_SECONDS', 86400 );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'OBJECT', 'OBJECT' );
date_default_timezone_set( 'UTC' );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

/* ---- exceptions that stand in for the end of a request ---------------------- */

class CsJsonReply extends Exception {
	public $success;
	public $data;
	public $status;
	public function __construct( $success, $data, $status ) {
		parent::__construct( 'json reply' );
		$this->success = $success;
		$this->data    = $data;
		$this->status  = $status;
	}
}

class CsDie extends Exception {
	public $status;
	public function __construct( $message, $status ) {
		parent::__construct( (string) $message );
		$this->status = $status;
	}
}

class CsRedirect extends Exception {
	public $location;
	public function __construct( $location ) {
		parent::__construct( 'redirect' );
		$this->location = $location;
	}
}

/* ---- object classes: the code tests instanceof against these ---------------- */

#[\AllowDynamicProperties]
class WP_Post {
	public function __construct( $data ) {
		foreach ( (array) $data as $k => $v ) {
			$this->$k = $v;
		}
	}
}

#[\AllowDynamicProperties]
class WP_Comment {
	public function __construct( $data ) {
		foreach ( (array) $data as $k => $v ) {
			$this->$k = $v;
		}
	}
}

#[\AllowDynamicProperties]
class WP_Term {
	public function __construct( $data ) {
		foreach ( (array) $data as $k => $v ) {
			$this->$k = $v;
		}
	}
}

class WP_Error {
	public $code;
	public $message;
	public function __construct( $code = '', $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}
	public function get_error_message() {
		return $this->message;
	}
}

class CsRole {
	public $name;
	public function __construct( $name ) {
		$this->name = $name;
	}
	public function has_cap( $cap ) {
		return ! empty( $GLOBALS['WP']['roles'][ $this->name ][ $cap ] );
	}
	public function add_cap( $cap, $grant = true ) {
		$GLOBALS['WP']['roles'][ $this->name ][ $cap ] = (bool) $grant;
	}
}

/**
 * WP_Query stand-in. Runs the same matcher as get_posts(), then pages the result.
 */
class WP_Query {
	public $posts         = array();
	public $found_posts   = 0;
	public $max_num_pages = 0;
	public $is_404        = false;

	public function __construct( $args = array() ) {
		if ( empty( $args ) ) {
			return;
		}
		$all                = cs_match_posts( $args );
		$this->found_posts  = count( $all );
		$per                = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 10;
		$paged              = isset( $args['paged'] ) ? max( 1, (int) $args['paged'] ) : 1;
		if ( $per > 0 ) {
			$this->max_num_pages = (int) ceil( count( $all ) / $per );
			$all                 = array_slice( $all, ( $paged - 1 ) * $per, $per );
		} else {
			$this->max_num_pages = $all ? 1 : 0;
		}
		if ( isset( $args['fields'] ) && 'ids' === $args['fields'] ) {
			$this->posts = array_map( function ( $p ) {
				return $p->ID;
			}, $all );
		} else {
			$this->posts = $all;
		}
	}

	public function have_posts() {
		return ! empty( $this->posts );
	}

	public function set_404() {
		$this->is_404 = true;
	}
}

/* ---- the $wpdb stand-in ------------------------------------------------------ */

class CsWpdb {
	public $posts       = 'wp_posts';
	public $comments    = 'wp_comments';
	public $postmeta    = 'wp_postmeta';
	public $commentmeta = 'wp_commentmeta';
	public $usermeta    = 'wp_usermeta';
	public $users       = 'wp_users';

	public function esc_like( $text ) {
		return addcslashes( (string) $text, '%_\\' );
	}

	public function prepare( $query, ...$args ) {
		$i = 0;
		return preg_replace_callback( '/%s/', function () use ( $args, &$i ) {
			$value = isset( $args[ $i ] ) ? (string) $args[ $i ] : '';
			$i++;
			return "'" . addslashes( $value ) . "'";
		}, $query );
	}

	public function get_col( $sql ) {
		return array_map( function ( $row ) {
			return reset( $row );
		}, $this->run( $sql ) );
	}

	public function get_results( $sql, $output = 'OBJECT' ) {
		return $this->run( $sql );
	}

	/**
	 * Answers the four query shapes the community code uses:
	 *   SELECT [DISTINCT] col FROM tbl WHERE meta_key = 'k' [AND meta_value (=|LIKE) 'v']
	 *   SELECT meta_value, COUNT(*) AS votes FROM tbl WHERE meta_key = 'k' GROUP BY meta_value
	 * Anything else returns no rows, so an unexpected query shows up as a failed check.
	 */
	private function run( $sql ) {
		if ( ! preg_match( '/^SELECT (DISTINCT )?(.+?) FROM (\w+) WHERE meta_key = \'([^\']*)\'(?: AND meta_value (=|LIKE) \'([^\']*)\')?(?: GROUP BY meta_value)?$/s', (string) $sql, $m ) ) {
			$GLOBALS['WP']['unparsed_sql'][] = $sql;
			return array();
		}
		$tables = array( 'wp_postmeta' => 'post', 'wp_commentmeta' => 'comment', 'wp_usermeta' => 'user' );
		$type   = isset( $tables[ $m[3] ] ) ? $tables[ $m[3] ] : '';
		$op     = isset( $m[5] ) ? $m[5] : '';
		$want   = isset( $m[6] ) ? $m[6] : '';
		$col    = $m[2];
		$distinct = '' !== $m[1];
		$rows   = array();
		foreach ( $GLOBALS['WP']['meta'][ $type ] as $r ) {
			if ( $r['key'] !== $m[4] || is_array( $r['value'] ) ) {
				continue;
			}
			if ( '=' === $op && (string) $r['value'] !== $want ) {
				continue;
			}
			if ( 'LIKE' === $op && ! cs_like( (string) $r['value'], $want ) ) {
				continue;
			}
			$rows[] = $r;
		}
		if ( false !== strpos( $col, 'COUNT(*)' ) ) {
			$counts = array();
			foreach ( $rows as $r ) {
				$v           = (string) $r['value'];
				$counts[ $v ] = isset( $counts[ $v ] ) ? $counts[ $v ] + 1 : 1;
			}
			$out = array();
			foreach ( $counts as $v => $n ) {
				$out[] = array( 'meta_value' => (string) $v, 'votes' => (string) $n );
			}
			return $out;
		}
		$out  = array();
		$seen = array();
		foreach ( $rows as $r ) {
			if ( 'meta_id' === $col ) {
				$val = $r['mid'];
			} elseif ( 'meta_value' === $col ) {
				$val = $r['value'];
			} else {
				$val = $r['obj'];
			}
			if ( $distinct ) {
				if ( isset( $seen[ (string) $val ] ) ) {
					continue;
				}
				$seen[ (string) $val ] = true;
			}
			$out[] = array( $col => $val );
		}
		return $out;
	}
}
$GLOBALS['wpdb'] = new CsWpdb();

/** LIKE with % and _ wildcards and backslash escapes. */
function cs_like( $value, $pattern ) {
	$re  = '';
	$len = strlen( $pattern );
	for ( $i = 0; $i < $len; $i++ ) {
		$c = $pattern[ $i ];
		if ( '\\' === $c && $i + 1 < $len ) {
			$i++;
			$re .= preg_quote( $pattern[ $i ], '/' );
		} elseif ( '%' === $c ) {
			$re .= '.*';
		} elseif ( '_' === $c ) {
			$re .= '.';
		} else {
			$re .= preg_quote( $c, '/' );
		}
	}
	return (bool) preg_match( '/^' . $re . '$/s', (string) $value );
}

/* ---- state -------------------------------------------------------------------- */

function cs_reset() {
	// Hooks are added once, when the theme files load. A reset keeps them, so a
	// test can check a hook that was registered at load time.
	$kept_hooks = isset( $GLOBALS['WP']['hooks'] ) ? $GLOBALS['WP']['hooks'] : array();
	$GLOBALS['WP'] = array(
		'mods'        => array(),
		'caps'        => array(),
		'user'        => 0,
		'options'     => array( 'date_format' => 'Y-m-d', 'time_format' => 'H:i' ),
		'posts'       => array(),
		'comments'    => array(),
		'users'       => array(),
		'terms'       => array(),
		'term_rel'    => array(),
		'meta'        => array( 'post' => array(), 'comment' => array(), 'user' => array() ),
		'next_id'     => 100,
		'next_mid'    => 1,
		'roles'       => array( 'administrator' => array(), 'editor' => array(), 'author' => array(), 'subscriber' => array() ),
		'transients'  => array(),
		'post_types'  => array(),
		'taxonomies'  => array(),
		'hooks'       => $kept_hooks,
		'submenus'    => array(),
		'meta_boxes'  => array(),
		'enqueued'    => array(),
		'page_slug'   => '',
		'page_template' => '',
		'status'      => null,
		'nocache'     => false,
		'redirect'    => null,
		'menu'        => false,
		'unparsed_sql' => array(),
		'comment_queries' => array(),
	);
	$GLOBALS['wp_query'] = new WP_Query();
	$_POST               = array();
	$_GET                = array();
	$_SERVER['REQUEST_URI'] = '/';
}
cs_reset();

function cs_next_id() {
	return $GLOBALS['WP']['next_id']++;
}

/* ---- translation and escaping ------------------------------------------------- */

function __( $text, $domain = '' ) { return $text; }
function _n( $single, $plural, $number, $domain = '' ) { return 1 === (int) $number ? $single : $plural; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8', false ); }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8', false ); }
function esc_html__( $t, $d = '' ) { return esc_html( $t ); }
function esc_attr__( $t, $d = '' ) { return esc_attr( $t ); }
function esc_html_e( $t, $d = '' ) { echo esc_html( $t ); }
function esc_attr_e( $t, $d = '' ) { echo esc_attr( $t ); }
function esc_url( $url ) {
	$url = (string) $url;
	if ( preg_match( '#^([a-z][a-z0-9+.\-]*):#i', $url, $m ) && ! in_array( strtolower( $m[1] ), array( 'http', 'https', 'mailto', 'tel' ), true ) ) {
		return '';
	}
	return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8', false );
}
function number_format_i18n( $n ) { return number_format( (float) $n ); }
function wp_specialchars_decode( $t ) { return htmlspecialchars_decode( (string) $t, ENT_QUOTES ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function human_time_diff( $from, $to = 0 ) {
	$d = abs( (int) $to - (int) $from );
	return $d < 3600 ? max( 1, (int) round( $d / 60 ) ) . ' minutes' : (int) round( $d / 3600 ) . ' hours';
}
function wp_date( $format, $ts = null ) { return date( $format, null === $ts ? time() : (int) $ts ); }
function selected( $a, $b, $echo = true ) {
	$r = (string) $a === (string) $b ? " selected='selected'" : '';
	if ( $echo ) { echo $r; }
	return $r;
}
function checked( $a, $b = true, $echo = true ) {
	$r = (string) $a === (string) $b ? " checked='checked'" : '';
	if ( $echo ) { echo $r; }
	return $r;
}
function disabled( $a, $b = true, $echo = true ) {
	$r = (string) $a === (string) $b ? " disabled='disabled'" : '';
	if ( $echo ) { echo $r; }
	return $r;
}

/* ---- sanitising ------------------------------------------------------------------ */

if ( ! function_exists( 'mb_strlen' ) ) {
	// This machine has no mbstring. These polyfills keep the multibyte branch of the
	// code under test on the path a production server with mbstring takes.
	function mb_strlen( $s, $enc = 'UTF-8' ) { return count( preg_split( '//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY ) ); }
}
if ( ! function_exists( 'mb_substr' ) ) {
	function mb_substr( $s, $start, $len = null, $enc = 'UTF-8' ) {
		$chars = preg_split( '//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY );
		return implode( '', array_slice( $chars, (int) $start, null === $len ? null : (int) $len ) );
	}
}
if ( ! function_exists( 'mb_strtoupper' ) ) {
	function mb_strtoupper( $s, $enc = 'UTF-8' ) { return strtoupper( (string) $s ); }
}

function wp_strip_all_tags( $text, $remove_breaks = false ) {
	$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $text );
	$text = strip_tags( $text );
	if ( $remove_breaks ) {
		$text = preg_replace( '/[\r\n\t ]+/', ' ', $text );
	}
	return trim( $text );
}
function wp_html_excerpt( $text, $length, $more = '' ) {
	return mb_strlen( $text ) > $length ? mb_substr( $text, 0, $length ) . $more : $text;
}
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ); }
function sanitize_text_field( $s ) { return trim( wp_strip_all_tags( (string) $s ) ); }
function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
function absint( $n ) { return abs( (int) $n ); }
function wp_is_post_revision( $post ) { return false; }

/* ---- options, mods, transients, caps, user ---------------------------------------- */

function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['WP']['options'] ) ? $GLOBALS['WP']['options'][ $name ] : $default;
}
function update_option( $name, $value, $autoload = null ) {
	$GLOBALS['WP']['options'][ $name ] = $value;
	return true;
}
function vance_get_theme_mod( $key, $default = '' ) {
	return array_key_exists( $key, $GLOBALS['WP']['mods'] ) ? $GLOBALS['WP']['mods'][ $key ] : $default;
}
function current_user_can( $cap ) { return ! empty( $GLOBALS['WP']['caps'][ $cap ] ); }
function is_user_logged_in() { return (int) $GLOBALS['WP']['user'] > 0; }
function get_current_user_id() { return (int) $GLOBALS['WP']['user']; }
function get_role( $name ) {
	return isset( $GLOBALS['WP']['roles'][ $name ] ) ? new CsRole( $name ) : null;
}
function set_transient( $key, $value, $expiry = 0 ) {
	$GLOBALS['WP']['transients'][ $key ] = $value;
	return true;
}
function get_transient( $key ) {
	return array_key_exists( $key, $GLOBALS['WP']['transients'] ) ? $GLOBALS['WP']['transients'][ $key ] : false;
}
function delete_transient( $key ) {
	unset( $GLOBALS['WP']['transients'][ $key ] );
	return true;
}

/* ---- hooks and registrations ------------------------------------------------------ */

function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['WP']['hooks'][ $tag ][] = array( 'cb' => $callback, 'priority' => $priority );
	return true;
}
function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	return add_action( $tag, $callback, $priority, $accepted_args );
}
function apply_filters( $tag, $value ) { return $value; }
/** True when $callback is hooked to $tag. */
function cs_hooked( $tag, $callback ) {
	if ( empty( $GLOBALS['WP']['hooks'][ $tag ] ) ) {
		return false;
	}
	foreach ( $GLOBALS['WP']['hooks'][ $tag ] as $h ) {
		if ( $h['cb'] === $callback ) {
			return true;
		}
	}
	return false;
}
function register_post_type( $name, $args = array() ) {
	$GLOBALS['WP']['post_types'][ $name ] = $args;
	return (object) array( 'name' => $name );
}
function register_taxonomy( $name, $object_type, $args = array() ) {
	$GLOBALS['WP']['taxonomies'][ $name ] = $args;
	return (object) array( 'name' => $name );
}
function taxonomy_exists( $name ) { return isset( $GLOBALS['WP']['taxonomies'][ $name ] ); }
function add_meta_box( $id, $title, $callback, $screen, $context = 'advanced', $priority = 'default' ) {
	$GLOBALS['WP']['meta_boxes'][ $id ] = $screen;
}
function add_submenu_page( $parent, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
	$GLOBALS['WP']['submenus'][] = array( 'parent' => $parent, 'menu_title' => $menu_title, 'capability' => $capability, 'menu_slug' => $menu_slug, 'callback' => $callback );
	return 'toplevel_page_' . $menu_slug;
}
function register_nav_menu( $location, $description ) { return true; }
function has_nav_menu( $location ) { return 'vance-hub-primary' === $location && $GLOBALS['WP']['menu']; }
function wp_nav_menu( $args = array() ) { return ''; }
function get_current_screen() { return null; }
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false ) {
	$GLOBALS['WP']['enqueued'][] = array( 'style', $handle, $src, $deps, $ver );
}
function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
	$GLOBALS['WP']['enqueued'][] = array( 'script', $handle, $src, $deps, $ver );
}
function status_header( $code ) { $GLOBALS['WP']['status'] = (int) $code; }
function nocache_headers() { $GLOBALS['WP']['nocache'] = true; }

/* ---- theme and URLs ------------------------------------------------------------------- */

function get_template_directory() { return $GLOBALS['THEME']; }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/vance-health-hub'; }
function locate_template( $name ) {
	$path = get_template_directory() . '/' . $name;
	return file_exists( $path ) ? $path : '';
}
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }
function is_page( $slug = '' ) { return '' !== $slug && $GLOBALS['WP']['page_slug'] === $slug; }
function is_page_template( $t = '' ) { return '' !== $t && $GLOBALS['WP']['page_template'] === $t; }
/**
 * Core's add_query_arg: values are inserted as given (build_query does not encode
 * them), so a caller that encodes first gets one level of encoding.
 */
function add_query_arg( $key, $value = null, $url = '' ) {
	if ( is_array( $key ) ) {
		$pairs = $key;
		$url   = (string) $value;
	} else {
		$pairs = array( $key => $value );
	}
	$parts = array();
	foreach ( $pairs as $k => $v ) {
		$parts[] = $k . '=' . $v;
	}
	return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . implode( '&', $parts );
}
function get_permalink( $post = 0 ) {
	if ( is_object( $post ) ) {
		return 'https://example.test/' . ( 'page' === $post->post_type ? $post->post_name . '/' : 'p/' . $post->ID . '/' );
	}
	$p = get_post( (int) $post );
	return $p ? 'https://example.test/p/' . $p->ID . '/' : false;
}
function get_edit_post_link( $id, $context = 'display' ) {
	return 'https://example.test/wp-admin/post.php?post=' . (int) $id . '&action=edit';
}
function get_page_by_path( $slug ) {
	foreach ( $GLOBALS['WP']['posts'] as $p ) {
		if ( 'page' === $p->post_type && $p->post_name === $slug ) {
			return $p;
		}
	}
	return null;
}

/* ---- nonces and request ends -------------------------------------------------------- */

function wp_create_nonce( $action ) { return 'nonce-' . $action; }
function wp_verify_nonce( $nonce, $action ) { return $nonce === 'nonce-' . $action ? 1 : false; }
function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
	$html = '<input type="hidden" name="' . $name . '" value="nonce-' . $action . '">';
	if ( $echo ) { echo $html; }
	return $html;
}
function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) {
	$given = isset( $_POST[ $query_arg ] ) && is_string( $_POST[ $query_arg ] ) ? $_POST[ $query_arg ] : '';
	if ( $given === 'nonce-' . $action ) {
		return 1;
	}
	if ( $die ) {
		throw new CsDie( '-1', 403 );
	}
	return false;
}
function check_admin_referer( $action = -1, $name = '_wpnonce' ) {
	$given = isset( $_POST[ $name ] ) && is_string( $_POST[ $name ] ) ? $_POST[ $name ] : '';
	if ( $given === 'nonce-' . $action ) {
		return 1;
	}
	throw new CsDie( 'Link expired', 403 );
}
function wp_send_json_success( $data = null, $status_code = null ) {
	throw new CsJsonReply( true, $data, $status_code ? (int) $status_code : 200 );
}
function wp_send_json_error( $data = null, $status_code = null ) {
	throw new CsJsonReply( false, $data, $status_code ? (int) $status_code : 200 );
}
function wp_die( $message = '', $title = '', $args = array() ) {
	throw new CsDie( (string) $message, isset( $args['response'] ) ? (int) $args['response'] : 200 );
}
function wp_safe_redirect( $location, $status = 302 ) {
	$GLOBALS['WP']['redirect'] = $location;
	throw new CsRedirect( $location );
}

/* ---- users ------------------------------------------------------------------------- */

/** Adds a user to the store. $meta is user meta (first_name, nickname rows, ...). */
function cs_user( $id, $email, array $meta = array() ) {
	$GLOBALS['WP']['users'][ $id ] = (object) array( 'ID' => (int) $id, 'user_email' => $email );
	foreach ( $meta as $k => $v ) {
		cs_add_meta( 'user', $id, $k, $v, false );
	}
	return (int) $id;
}
function get_user_by( $field, $value ) {
	if ( 'email' !== $field ) {
		return false;
	}
	foreach ( $GLOBALS['WP']['users'] as $u ) {
		if ( $u->user_email === $value ) {
			return $u;
		}
	}
	return false;
}

/* ---- meta -------------------------------------------------------------------------- */

function cs_add_meta( $type, $id, $key, $value, $unique = false ) {
	if ( $unique ) {
		foreach ( $GLOBALS['WP']['meta'][ $type ] as $r ) {
			if ( $r['obj'] === (int) $id && $r['key'] === $key ) {
				return false;
			}
		}
	}
	$mid = $GLOBALS['WP']['next_mid']++;
	$GLOBALS['WP']['meta'][ $type ][] = array( 'mid' => $mid, 'obj' => (int) $id, 'key' => $key, 'value' => $value );
	return $mid;
}
function cs_values( $type, $id, $key ) {
	$out = array();
	foreach ( $GLOBALS['WP']['meta'][ $type ] as $r ) {
		if ( $r['obj'] === (int) $id && $r['key'] === $key ) {
			$out[] = $r['value'];
		}
	}
	return $out;
}
function cs_get_meta( $type, $id, $key = '', $single = false ) {
	if ( '' === $key ) {
		$out = array();
		foreach ( $GLOBALS['WP']['meta'][ $type ] as $r ) {
			if ( $r['obj'] === (int) $id ) {
				$out[ $r['key'] ][] = $r['value'];
			}
		}
		return $out;
	}
	$vals = cs_values( $type, $id, $key );
	if ( $single ) {
		return $vals ? $vals[0] : '';
	}
	return $vals;
}
function cs_del_meta( $type, $id, $key, $value = null ) {
	$keep = array();
	foreach ( $GLOBALS['WP']['meta'][ $type ] as $r ) {
		$match = $r['obj'] === (int) $id && $r['key'] === $key && ( null === $value || (string) $r['value'] === (string) $value );
		if ( ! $match ) {
			$keep[] = $r;
		}
	}
	$GLOBALS['WP']['meta'][ $type ] = $keep;
	return true;
}
function get_metadata( $type, $id, $key = '', $single = false ) {
	return cs_get_meta( $type, $id, $key, $single );
}
function add_metadata( $type, $id, $key, $value, $unique = false ) {
	return cs_add_meta( $type, $id, $key, $value, $unique );
}
function delete_metadata( $type, $id, $key, $value = '', $delete_all = false ) {
	if ( $delete_all ) {
		$keep = array();
		foreach ( $GLOBALS['WP']['meta'][ $type ] as $r ) {
			$match = $r['key'] === $key && ( '' === $value || (string) $r['value'] === (string) $value );
			if ( ! $match ) {
				$keep[] = $r;
			}
		}
		$GLOBALS['WP']['meta'][ $type ] = $keep;
		return true;
	}
	return cs_del_meta( $type, $id, $key, '' === $value ? null : $value );
}
function delete_metadata_by_mid( $type, $mid ) {
	$keep = array();
	foreach ( $GLOBALS['WP']['meta'][ $type ] as $r ) {
		if ( $r['mid'] !== (int) $mid ) {
			$keep[] = $r;
		}
	}
	$GLOBALS['WP']['meta'][ $type ] = $keep;
	return true;
}
function get_post_meta( $id, $key = '', $single = false ) { return cs_get_meta( 'post', $id, $key, $single ); }
function update_post_meta( $id, $key, $value, $prev = '' ) {
	cs_del_meta( 'post', $id, $key );
	return (bool) cs_add_meta( 'post', $id, $key, $value, false );
}
function add_post_meta( $id, $key, $value, $unique = false ) { return cs_add_meta( 'post', $id, $key, $value, $unique ); }
function delete_post_meta( $id, $key, $value = '' ) { return cs_del_meta( 'post', $id, $key, '' === $value ? null : $value ); }
function get_comment_meta( $id, $key = '', $single = false ) { return cs_get_meta( 'comment', $id, $key, $single ); }
function add_comment_meta( $id, $key, $value, $unique = false ) { return cs_add_meta( 'comment', $id, $key, $value, $unique ); }
function update_comment_meta( $id, $key, $value, $prev = '' ) {
	cs_del_meta( 'comment', $id, $key );
	return (bool) cs_add_meta( 'comment', $id, $key, $value, false );
}
function delete_comment_meta( $id, $key, $value = '' ) { return cs_del_meta( 'comment', $id, $key, '' === $value ? null : $value ); }
function get_user_meta( $id, $key = '', $single = false ) { return cs_get_meta( 'user', $id, $key, $single ); }
function update_user_meta( $id, $key, $value, $prev = '' ) {
	cs_del_meta( 'user', $id, $key );
	return (bool) cs_add_meta( 'user', $id, $key, $value, false );
}
function add_user_meta( $id, $key, $value, $unique = false ) { return cs_add_meta( 'user', $id, $key, $value, $unique ); }
function delete_user_meta( $id, $key, $value = '' ) { return cs_del_meta( 'user', $id, $key, '' === $value ? null : $value ); }

/* ---- posts ------------------------------------------------------------------------- */

function get_post( $id = null ) {
	$id = is_object( $id ) ? (int) $id->ID : (int) $id;
	return isset( $GLOBALS['WP']['posts'][ $id ] ) ? $GLOBALS['WP']['posts'][ $id ] : null;
}
function get_the_title( $post = 0 ) {
	$p = is_object( $post ) ? $post : get_post( (int) $post );
	return $p ? (string) $p->post_title : '';
}
function get_post_time( $format = 'U', $gmt = false, $post = null ) {
	$p = is_object( $post ) ? $post : get_post( (int) $post );
	return $p ? strtotime( $p->post_date ) : 0;
}
function wp_insert_post( $args, $wp_error = false ) {
	$id  = cs_next_id();
	$now = date( 'Y-m-d H:i:s', time() );
	$p   = array_merge( array(
		'post_type'     => 'post',
		'post_status'   => 'draft',
		'post_author'   => 0,
		'post_title'    => '',
		'post_content'  => '',
		'post_name'     => '',
		'post_date'     => $now,
		'post_date_gmt' => $now,
		'post_parent'   => 0,
	), $args );
	$p['ID'] = $id;
	$GLOBALS['WP']['posts'][ $id ] = new WP_Post( $p );
	if ( ! empty( $args['meta_input'] ) ) {
		foreach ( $args['meta_input'] as $k => $v ) {
			update_post_meta( $id, $k, $v );
		}
	}
	return $id;
}
function wp_update_post( $args, $wp_error = false ) {
	$id = (int) $args['ID'];
	if ( ! isset( $GLOBALS['WP']['posts'][ $id ] ) ) {
		return 0;
	}
	$p = (array) $GLOBALS['WP']['posts'][ $id ];
	foreach ( $args as $k => $v ) {
		$p[ $k ] = $v;
	}
	$GLOBALS['WP']['posts'][ $id ] = new WP_Post( $p );
	return $id;
}
function wp_delete_post( $id, $force = false ) {
	$id = (int) $id;
	if ( ! isset( $GLOBALS['WP']['posts'][ $id ] ) ) {
		return false;
	}
	$post = $GLOBALS['WP']['posts'][ $id ];
	foreach ( cs_match_comments( array( 'post_id' => $id, 'status' => 'all' ) ) as $c ) {
		wp_delete_comment( $c->comment_ID, true );
	}
	cs_del_obj_meta( 'post', $id );
	unset( $GLOBALS['WP']['posts'][ $id ], $GLOBALS['WP']['term_rel'][ $id ] );
	return $post;
}
function cs_del_obj_meta( $type, $id ) {
	$keep = array();
	foreach ( $GLOBALS['WP']['meta'][ $type ] as $r ) {
		if ( $r['obj'] !== (int) $id ) {
			$keep[] = $r;
		}
	}
	$GLOBALS['WP']['meta'][ $type ] = $keep;
}
function cs_match_posts( $args ) {
	$out = array();
	foreach ( $GLOBALS['WP']['posts'] as $p ) {
		if ( isset( $args['post_type'] ) && ! in_array( $p->post_type, (array) $args['post_type'], true ) ) {
			continue;
		}
		if ( isset( $args['post_status'] ) ) {
			$want = (array) $args['post_status'];
			if ( in_array( 'any', $want, true ) ) {
				if ( 'trash' === $p->post_status ) {
					continue;
				}
			} elseif ( ! in_array( $p->post_status, $want, true ) ) {
				continue;
			}
		}
		if ( isset( $args['author'] ) && (int) $args['author'] !== (int) $p->post_author ) {
			continue;
		}
		if ( isset( $args['name'] ) && $args['name'] !== $p->post_name ) {
			continue;
		}
		if ( ! empty( $args['post__not_in'] ) && in_array( (int) $p->ID, array_map( 'intval', $args['post__not_in'] ), true ) ) {
			continue;
		}
		if ( isset( $args['meta_key'] ) && ! cs_has_meta( 'post', $p->ID, $args['meta_key'], isset( $args['meta_value'] ) ? $args['meta_value'] : null ) ) {
			continue;
		}
		if ( isset( $args['meta_query'] ) && ! cs_meta_query_ok( 'post', $p->ID, $args['meta_query'] ) ) {
			continue;
		}
		if ( isset( $args['tax_query'][0] ) && ! cs_has_term( $p->ID, $args['tax_query'][0]['taxonomy'], $args['tax_query'][0]['terms'] ) ) {
			continue;
		}
		if ( isset( $args['date_query'][0] ) && ! cs_after( $p->post_date, $args['date_query'][0]['after'] ) ) {
			continue;
		}
		$out[] = $p;
	}
	usort( $out, function ( $a, $b ) {
		$d = strtotime( $b->post_date ) - strtotime( $a->post_date );
		return 0 !== $d ? $d : $b->ID - $a->ID;
	} );
	return $out;
}
/** WordPress meta_query: clauses with key, value and compare (= or NOT EXISTS), joined by relation (AND default). */
function cs_meta_query_ok( $type, $id, $mq ) {
	$or      = isset( $mq['relation'] ) && 'OR' === strtoupper( $mq['relation'] );
	$results = array();
	foreach ( $mq as $k => $clause ) {
		if ( 'relation' === $k ) {
			continue;
		}
		$exists = cs_has_meta( $type, $id, $clause['key'], isset( $clause['value'] ) ? $clause['value'] : null );
		$results[] = ( isset( $clause['compare'] ) && 'NOT EXISTS' === $clause['compare'] )
			? ! cs_has_meta( $type, $id, $clause['key'] )
			: $exists;
	}
	if ( ! $results ) {
		return true;
	}
	return $or ? in_array( true, $results, true ) : ! in_array( false, $results, true );
}
function cs_has_meta( $type, $id, $key, $value = null ) {
	foreach ( $GLOBALS['WP']['meta'][ $type ] as $r ) {
		if ( $r['obj'] === (int) $id && $r['key'] === $key && ( null === $value || (string) $r['value'] === (string) $value ) ) {
			return true;
		}
	}
	return false;
}
/** Calls $fn($i) for $i = 0 .. $n-1. A fixture helper, so the tests themselves hold no loops. */
function cs_repeat( $n, $fn ) {
	for ( $i = 0; $i < $n; $i++ ) {
		$fn( $i );
	}
}

/** A date column is after a bound. '24 hours ago' is the only bound the code uses. */
function cs_after( $date, $bound ) {
	$limit = '24 hours ago' === $bound ? time() - DAY_IN_SECONDS : strtotime( $bound );
	return strtotime( $date ) >= $limit;
}
function get_posts( $args = array() ) {
	$all = cs_match_posts( $args );
	$per = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 5;
	if ( $per > 0 ) {
		$all = array_slice( $all, 0, $per );
	}
	if ( isset( $args['fields'] ) && 'ids' === $args['fields'] ) {
		return array_map( function ( $p ) {
			return $p->ID;
		}, $all );
	}
	return $all;
}

/* ---- comments (replies are comments of type vance_reply) ---------------------------------- */

function get_comment( $id ) {
	$id = (int) $id;
	return isset( $GLOBALS['WP']['comments'][ $id ] ) ? $GLOBALS['WP']['comments'][ $id ] : null;
}
function wp_insert_comment( $data ) {
	$id  = cs_next_id();
	$now = date( 'Y-m-d H:i:s', time() );
	$c   = array_merge( array(
		'comment_post_ID'      => 0,
		'comment_author'       => '',
		'comment_author_email' => '',
		'comment_author_url'   => '',
		'comment_author_IP'    => '',
		'comment_date'         => $now,
		'comment_date_gmt'     => $now,
		'comment_content'      => '',
		'comment_approved'     => '1',
		'comment_agent'        => '',
		'comment_type'         => '',
		'comment_parent'       => 0,
		'user_id'              => 0,
	), $data );
	$c['comment_approved'] = (string) $c['comment_approved'];
	$c['comment_ID']       = $id;
	$GLOBALS['WP']['comments'][ $id ] = new WP_Comment( $c );
	return $id;
}
function wp_update_comment( $data ) {
	$id = (int) $data['comment_ID'];
	if ( ! isset( $GLOBALS['WP']['comments'][ $id ] ) ) {
		return false;
	}
	$c = (array) $GLOBALS['WP']['comments'][ $id ];
	foreach ( $data as $k => $v ) {
		$c[ $k ] = 'comment_approved' === $k ? (string) $v : $v;
	}
	$GLOBALS['WP']['comments'][ $id ] = new WP_Comment( $c );
	return 1;
}
function wp_delete_comment( $id, $force = false ) {
	$id = (int) $id;
	if ( ! isset( $GLOBALS['WP']['comments'][ $id ] ) ) {
		return false;
	}
	unset( $GLOBALS['WP']['comments'][ $id ] );
	cs_del_obj_meta( 'comment', $id );
	return true;
}
function get_comments( $args = array() ) {
	// Every query is recorded, so a suite can check how much a request fetched.
	$GLOBALS['WP']['comment_queries'][] = $args;
	$all = cs_match_comments( $args );
	if ( isset( $args['count'] ) && $args['count'] ) {
		return count( $all );
	}
	if ( isset( $args['number'] ) && (int) $args['number'] > 0 ) {
		$all = array_slice( $all, 0, (int) $args['number'] );
	}
	if ( isset( $args['fields'] ) && 'ids' === $args['fields'] ) {
		return array_map( function ( $c ) {
			return $c->comment_ID;
		}, $all );
	}
	return $all;
}
function cs_match_comments( $args ) {
	$order = isset( $args['order'] ) ? strtoupper( $args['order'] ) : 'DESC';
	$out   = array();
	foreach ( $GLOBALS['WP']['comments'] as $c ) {
		if ( isset( $args['post_id'] ) && (int) $args['post_id'] !== (int) $c->comment_post_ID ) {
			continue;
		}
		if ( isset( $args['type'] ) && $args['type'] !== $c->comment_type ) {
			continue;
		}
		if ( isset( $args['user_id'] ) && (int) $args['user_id'] !== (int) $c->user_id ) {
			continue;
		}
		if ( isset( $args['status'] ) && 'approve' === $args['status'] && '1' !== (string) $c->comment_approved ) {
			continue;
		}
		if ( isset( $args['meta_key'] ) && ! cs_has_meta( 'comment', $c->comment_ID, $args['meta_key'], isset( $args['meta_value'] ) ? $args['meta_value'] : null ) ) {
			continue;
		}
		if ( isset( $args['meta_query'] ) && ! cs_meta_query_ok( 'comment', $c->comment_ID, $args['meta_query'] ) ) {
			continue;
		}
		if ( isset( $args['date_query'][0] ) && ! cs_after( $c->comment_date, $args['date_query'][0]['after'] ) ) {
			continue;
		}
		$out[] = $c;
	}
	usort( $out, function ( $a, $b ) use ( $order ) {
		$d = $a->comment_ID - $b->comment_ID;
		return 'ASC' === $order ? $d : -$d;
	} );
	return $out;
}

/* ---- terms: the vance_space taxonomy ------------------------------------------------- */

function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
function wp_insert_term( $name, $taxonomy, $args = array() ) {
	$slug = isset( $args['slug'] ) ? $args['slug'] : sanitize_key( strtolower( str_replace( ' ', '-', $name ) ) );
	if ( term_exists( $slug, $taxonomy ) ) {
		return new WP_Error( 'term_exists', 'exists' );
	}
	$id = cs_next_id();
	$GLOBALS['WP']['terms'][ $id ] = new WP_Term( array( 'term_id' => $id, 'name' => $name, 'slug' => $slug, 'taxonomy' => $taxonomy ) );
	return array( 'term_id' => $id, 'term_taxonomy_id' => $id );
}
function term_exists( $slug, $taxonomy = '' ) {
	foreach ( $GLOBALS['WP']['terms'] as $t ) {
		if ( $t->slug === $slug && $t->taxonomy === $taxonomy ) {
			return array( 'term_id' => $t->term_id );
		}
	}
	return 0;
}
function get_term_by( $field, $value, $taxonomy = '' ) {
	foreach ( $GLOBALS['WP']['terms'] as $t ) {
		if ( 'slug' === $field && $t->slug === $value && $t->taxonomy === $taxonomy ) {
			return $t;
		}
	}
	return false;
}
function get_terms( $args = array() ) {
	$out = array();
	foreach ( $GLOBALS['WP']['terms'] as $t ) {
		if ( isset( $args['taxonomy'] ) && $args['taxonomy'] !== $t->taxonomy ) {
			continue;
		}
		$out[] = $t;
	}
	return $out;
}
function cs_has_term( $post_id, $taxonomy, $slug ) {
	foreach ( cs_post_terms( $post_id, $taxonomy ) as $t ) {
		if ( $t->slug === $slug ) {
			return true;
		}
	}
	return false;
}
function cs_post_terms( $post_id, $taxonomy ) {
	$out = array();
	if ( ! isset( $GLOBALS['WP']['term_rel'][ (int) $post_id ] ) ) {
		return $out;
	}
	foreach ( $GLOBALS['WP']['term_rel'][ (int) $post_id ] as $tid ) {
		if ( isset( $GLOBALS['WP']['terms'][ $tid ] ) && $GLOBALS['WP']['terms'][ $tid ]->taxonomy === $taxonomy ) {
			$out[] = $GLOBALS['WP']['terms'][ $tid ];
		}
	}
	return $out;
}
function get_the_terms( $post_id, $taxonomy ) {
	$terms = cs_post_terms( $post_id, $taxonomy );
	return $terms ? $terms : false;
}
function wp_set_object_terms( $post_id, $term_ids, $taxonomy, $append = false ) {
	$keep = array();
	foreach ( (array) ( isset( $GLOBALS['WP']['term_rel'][ (int) $post_id ] ) ? $GLOBALS['WP']['term_rel'][ (int) $post_id ] : array() ) as $tid ) {
		if ( ! isset( $GLOBALS['WP']['terms'][ $tid ] ) || $GLOBALS['WP']['terms'][ $tid ]->taxonomy !== $taxonomy ) {
			$keep[] = $tid;
		}
	}
	foreach ( $term_ids as $tid ) {
		$keep[] = (int) $tid;
	}
	$GLOBALS['WP']['term_rel'][ (int) $post_id ] = $keep;
	return array_map( 'intval', $term_ids );
}

/* ---- fixtures: one place that builds data the way the real flow does --------------- */

/**
 * A question in a given state, with its space. The post status and the state
 * meta are set the way the code sets them, so the fixture is never a state the
 * code could not reach.
 *
 * @param int    $author  Member ID.
 * @param string $state   waiting | approved | reported | rejected.
 * @param string $title   Title.
 * @param string $content Body.
 * @param int    $age     Seconds old (0 = now).
 * @param string $space   Space slug.
 * @return int Question ID.
 */
function cs_seed_question( $author, $state, $title, $content = '', $age = 0, $space = 'ibd' ) {
	$status = 'approved' === $state ? 'publish' : ( 'rejected' === $state ? 'draft' : 'pending' );
	$date   = date( 'Y-m-d H:i:s', time() - (int) $age );
	$id     = wp_insert_post( array(
		'post_type'    => 'vance_question',
		'post_status'  => $status,
		'post_author'  => (int) $author,
		'post_title'   => $title,
		'post_content' => $content,
		'post_date'    => $date,
		'post_date_gmt' => $date,
	) );
	$term = get_term_by( 'slug', $space, 'vance_space' );
	if ( $term ) {
		wp_set_object_terms( $id, array( $term->term_id ), 'vance_space', false );
	}
	update_post_meta( $id, '_vance_community_state', $state );
	return $id;
}

/**
 * A reply. comment_approved and the state meta follow the state, as they do in
 * the code (waiting and reported and rejected are 0, approved is 1).
 *
 * @return int Comment ID.
 */
function cs_seed_reply( $question_id, $user_id, $content, $state = 'approved', $age = 0 ) {
	$date = date( 'Y-m-d H:i:s', time() - (int) $age );
	$id   = wp_insert_comment( array(
		'comment_post_ID'  => (int) $question_id,
		'comment_content'  => $content,
		'comment_type'     => 'vance_reply',
		'comment_approved' => 'approved' === $state ? 1 : 0,
		'user_id'          => (int) $user_id,
		'comment_author'   => 'Member',
		'comment_date'     => $date,
		'comment_date_gmt' => $date,
	) );
	update_comment_meta( $id, '_vance_community_state', $state );
	return $id;
}

/** A question's space slug, read back from its term. */
function cs_space_of( $question_id ) {
	$terms = cs_post_terms( $question_id, 'vance_space' );
	return $terms ? $terms[0]->slug : '';
}
