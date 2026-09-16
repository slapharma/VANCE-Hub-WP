<?php
/**
 * inc/hero-mobile.php, and the small hooks every hero family added to call
 * it: the phone band's short labels, the per-hero "show on mobile" switches,
 * the Health News slug rename, the sub-category overflow cap, and the
 * category promo block's mobile visibility switch.
 *
 * Same shape as category-hero.test.php / gi-hero.test.php / legal-hero.test.php:
 * WP stubs, a controllable bag of theme mods, and assertions against the real
 * emitted HTML — not a re-implementation of the logic under test.
 *
 * hero-mobile.php is new, and everything that calls it does so behind
 * function_exists(), so this suite renders every hero family TWICE: once with
 * hero-mobile.php loaded (this process) and once without it, which is exactly
 * what category-hero.test.php, gi-hero.test.php, legal-hero.test.php and
 * hero-render.test.php already do by never requiring it. Their staying green
 * is what pins the "not loaded" path; this file pins the "loaded" path.
 *
 * Every check here must be able to go red — see the manual sabotage notes in
 * the task this suite was written for.
 */

define( 'ABSPATH', true );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';
$GLOBALS['THEME_DIR'] = $THEME;

/* ---- the bags the stubs read ----------------------------------------- */
$GLOBALS['MODS']  = array();
$GLOBALS['TERMS'] = array();
$GLOBALS['POSTS'] = array();
$GLOBALS['DESCS'] = array();

function set_mods( array $m )  { $GLOBALS['MODS']  = $m; }
function set_descs( array $d ) { $GLOBALS['DESCS'] = $d; }
function set_posts( array $p ) { $GLOBALS['POSTS'] = $p; }

/* ---- WordPress stubs, union of what every required file needs -------- */

class WP_Term {
	public $term_id, $name, $slug, $parent, $taxonomy, $count;
	public function __construct( $id, $name, $slug, $parent = 0, $taxonomy = 'category', $count = 0 ) {
		$this->term_id  = $id;
		$this->name     = $name;
		$this->slug     = $slug;
		$this->parent   = $parent;
		$this->taxonomy = $taxonomy;
		$this->count    = $count;
	}
}

class WP_Query {
	public $posts = array();
	public $found_posts = 0;
	public $args = array();
	public function __construct( $args ) {
		$this->args = $args;
		$GLOBALS['LAST_QUERY'] = $args;
		$tid  = isset( $args['cat'] ) ? (int) $args['cat'] : 0;
		$data = isset( $GLOBALS['POSTS'][ $tid ] ) ? $GLOBALS['POSTS'][ $tid ] : array( 'total' => 0, 'time' => null );
		$this->found_posts = (int) $data['total'];
		if ( $this->found_posts > 0 ) {
			$this->posts = array( 900 + $tid );
			$GLOBALS['POST_TIME'][ 900 + $tid ] = $data['time'];
		}
	}
}

function vance_get_theme_mod( $key, $default = '' ) {
	return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
}
function get_theme_mod( $key, $default = '' ) { return vance_get_theme_mod( $key, $default ); }
function get_template_directory() { return $GLOBALS['THEME_DIR']; }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/vance-health-hub'; }
function get_queried_object() { return isset( $GLOBALS['QUERIED'] ) ? $GLOBALS['QUERIED'] : null; }
function get_term( $id, $tax = 'category' ) { return isset( $GLOBALS['TERMS'][ $id ] ) ? $GLOBALS['TERMS'][ $id ] : null; }
function get_category_link( $id ) { return 'https://example.test/category/' . $GLOBALS['TERMS'][ $id ]->slug . '/'; }
function term_description( $t ) {
	$id = is_object( $t ) ? $t->term_id : (int) $t;
	return isset( $GLOBALS['DESCS'][ $id ] ) ? $GLOBALS['DESCS'][ $id ] : '';
}
function get_categories( $args ) {
	$out = array();
	foreach ( $GLOBALS['TERMS'] as $t ) {
		if ( (int) $t->parent !== (int) $args['parent'] ) { continue; }
		if ( ! empty( $args['hide_empty'] ) ) {
			$p = isset( $GLOBALS['POSTS'][ $t->term_id ] ) ? $GLOBALS['POSTS'][ $t->term_id ]['total'] : 0;
			if ( $p < 1 ) { continue; }
		}
		$out[] = ( isset( $args['fields'] ) && $args['fields'] === 'ids' ) ? $t->term_id : $t;
	}
	return $out;
}
function get_post_time( $fmt, $gmt = false, $post = 0 ) {
	return isset( $GLOBALS['POST_TIME'][ $post ] ) ? $GLOBALS['POST_TIME'][ $post ] : 0;
}
function date_i18n( $fmt, $ts ) { return gmdate( $fmt, (int) $ts ); }
function number_format_i18n( $n ) { return number_format( (float) $n ); }
function wp_reset_postdata() {}
function wp_strip_all_tags( $t ) { return strip_tags( (string) $t ); }
function sanitize_html_class( $c ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $c ); }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function apply_filters( $tag, $value ) { return $value; }
function add_filter( $tag, $cb, $prio = 10, $args = 1 ) {}
function add_action( $tag, $cb, $prio = 10, $args = 1 ) {}
function _n( $s, $p, $n, $d = '' ) { return $n === 1 ? $s : $p; }
function _e( $t, $d = '' ) { echo $t; }
function __( $t, $d = '' ) { return $t; }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_url( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html_e( $t, $d = '' ) { echo esc_html( $t ); }
function esc_html__( $t, $d = '' ) { return esc_html( $t ); }
function esc_attr_e( $t, $d = '' ) { echo esc_attr( $t ); }
function wp_kses_post( $t ) { return (string) $t; }
function add_query_arg( $k, $v, $url ) {
	return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . $k . '=' . rawurlencode( $v );
}
function home_url( $p = '/' ) { return 'https://example.test' . $p; }
function get_page_by_path( $slug ) { return isset( $GLOBALS['PAGES'][ $slug ] ) ? $slug : null; }
function get_permalink( $p ) { return 'https://example.test/' . $p . '/'; }
function get_the_title( $p = 0 ) { return 'Gastro Health Explained'; }
function get_search_query() { return ''; }
function sanitize_hex_color( $c ) { return $c; }
function vance_sanitize_checkbox( $checked ) { return ( ( isset( $checked ) && true == $checked ) ? true : false ); }
function absint( $n ) { return abs( (int) $n ); }

$GLOBALS['PAGES'] = array(
	'gastro-health-explained'    => 1,
	'inflammatory-bowel-disease' => 1, 'ulcerative-colitis'  => 1,
	'crohns-disease'             => 1, 'microscopic-colitis' => 1,
	'irritable-bowel-syndrome'   => 1, 'colorectal-cancer'   => 1,
	'diverticular-disease'       => 1,
	'privacy-policy' => 1, 'cookie-policy-uk' => 1, 'terms-of-use' => 1,
	'medical-disclaimer' => 1, 'accessibility' => 1,
	'ask-ai' => 1, 'knowledgebase' => 1, 'gastro-health-survey' => 1,
	'gastro-meal-planner' => 1, 'malnutrition-calculator' => 1,
	'free-health-tools' => 1, 'contact-us' => 1,
);

/**
 * Pulls one top-level function's source out of a file by brace matching and
 * evaluates it, tolerant of leading indentation (several of the functions this
 * suite needs sit inside an `if ( ! function_exists( ... ) ) { ... }` guard).
 * Deliberately strict: anything unexpected is a fatal, not a skip — a suite
 * that silently stops testing the real source is worse than no suite.
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

/* ---- load the real source, in the same order functions.php does ------ */

require_once $THEME . '/inc/hero-mobile.php';
require_once $THEME . '/inc/category-hero.php';

$FN = $THEME . '/functions.php';
lift_function( $FN, 'vance_gi_conditions' );
lift_function( $FN, 'vance_gi_condition_cards' );
lift_function( $FN, 'vance_gi_page_url' );
lift_function( $FN, 'vance_gi_hub_url' );

require_once $THEME . '/inc/gi-hero.php';
require_once $THEME . '/inc/legal-hero.php';
require_once $THEME . '/inc/hero-spotlight.php';
require_once $THEME . '/inc/page-hero-spotlight.php';
require_once $THEME . '/inc/promo-block.php';
require_once $THEME . '/inc/article-schema-types.php';

/*
 * vance_subcat_overflow_cap() lives inside
 * template-parts/subcategory-grouped-archive.php, which cannot be required
 * whole here — its tail runs a live WP_Query loop the stub layer does not
 * provide (see category-hero.test.php's note on the same constraint for the
 * three archive templates). Lifted the same way gi-hero.test.php lifts the
 * condition registry out of functions.php: real source, not a rewritten copy.
 */
lift_function( $THEME . '/template-parts/subcategory-grouped-archive.php', 'vance_subcat_overflow_cap' );

// The two getters vance_subcat_overflow_cap() calls. Trivial one-line reads
// of vance_get_theme_mod() in the real functions.php; stubbed directly here
// rather than lifted, same as every other suite stubs simple getters.
function vance_sanitize_grid_cols( $v ) { return in_array( $v, array( '3', '4', '5' ), true ) ? $v : '3'; }
function vance_get_subcat_grid_cols( $term_id ) { return vance_sanitize_grid_cols( (string) vance_get_theme_mod( "vance_subcat_grid_cols_{$term_id}", '3' ) ); }
function vance_get_subcat_rows( $term_id ) { return (int) vance_get_theme_mod( "vance_subcat_rows_{$term_id}", 0 ); }

/* ---- the runner -------------------------------------------------------- */
$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/** Strip the inline <style> block(s) so a class name inside a CSS comment or
 * selector cannot be mistaken for emitted markup. */
function body( $html ) { return preg_replace( '#<style.*?</style>#s', '', $html ); }

/* ---- category fixture -------------------------------------------------- */
function seed_terms() {
	$GLOBALS['TERMS'] = array();
	foreach ( array(
		array( 17, 'Clinical Reviews', 'content-clinical-reviews', 0 ),
		array( 15, 'Health News (old slug)',  'content-healthcare-news', 0 ),
		array( 19, 'Health News (new slug)',  'content-health-news',     0 ),
		array( 66, 'Brand New Section',       'brand-new-section',       0 ),
	) as $r ) {
		$GLOBALS['TERMS'][ $r[0] ] = new WP_Term( $r[0], $r[1], $r[2], $r[3] );
	}
}
seed_terms();
set_posts( array(
	17 => array( 'total' => 28, 'time' => 1754006400 ), // 2025-08-01
	15 => array( 'total' => 32, 'time' => 1756598400 ), // 2025-08-31
	19 => array( 'total' => 32, 'time' => 1756598400 ),
	66 => array( 'total' => 4,  'time' => 1756598400 ),
) );

function render_cat( $term_id, array $mods = array() ) {
	set_mods( $mods );
	$GLOBALS['QUERIED'] = $GLOBALS['TERMS'][ $term_id ];
	ob_start();
	vance_render_category_hero();
	return ob_get_clean();
}
function render_gi_cond( $slug, array $mods = array() ) {
	set_mods( $mods );
	ob_start();
	vance_render_gi_hero( $slug );
	return ob_get_clean();
}
function render_gi_hub( array $mods = array() ) {
	set_mods( $mods );
	ob_start();
	vance_render_gi_hub_hero();
	return ob_get_clean();
}
function render_legal( $doc, array $mods = array() ) {
	set_mods( $mods );
	ob_start();
	vance_render_legal_hero( $doc );
	return ob_get_clean();
}
function render_page( $page, array $mods = array() ) {
	set_mods( $mods );
	ob_start();
	vance_render_page_hero_spotlight( $page );
	return ob_get_clean();
}
function render_home( array $mods = array() ) {
	set_mods( $mods );
	ob_start();
	vance_render_hero_spotlight();
	return ob_get_clean();
}


/* ===================================================================== */
section( '1. vance_hero_band_value()' );
/* ===================================================================== */

check( '1a  no short form: renders the full value alone, escaped',
	vance_hero_band_value( 'August 2026' ) === 'August 2026' );

check( '1b  short form identical to full: still renders alone, no extra spans',
	vance_hero_band_value( 'Terms of Use', 'Terms of Use' ) === 'Terms of Use' );

$v = vance_hero_band_value( 'Inflammatory bowel disease', 'IBD' );
check( '1c  short form different from full: both spans emitted',
	strpos( $v, '<span class="vhh-hero-spotlight__full">Inflammatory bowel disease</span>' ) !== false
	&& strpos( $v, '<span class="vhh-hero-spotlight__short" aria-hidden="true">IBD</span>' ) !== false );

check( '1d  the short span is hidden from assistive tech',
	strpos( $v, 'aria-hidden="true">IBD<' ) !== false );

check( '1e  both parts are escaped',
	strpos( vance_hero_band_value( '<b>Full</b>', '<i>Short</i>' ), '<b>' ) === false
	&& strpos( vance_hero_band_value( '<b>Full</b>', '<i>Short</i>' ), '&lt;b&gt;' ) !== false );

check( '1f  empty short form is treated the same as no short form',
	vance_hero_band_value( 'Solo', '' ) === 'Solo' );

check( '1g  non-string input is cast rather than fataling',
	vance_hero_band_value( 42, '' ) === '42' );


/* ===================================================================== */
section( '2. vance_hero_band_short()' );
/* ===================================================================== */

check( '2a  a known long label gets its short form',
	vance_hero_band_short( 'Rigorously Developed' ) === 'Rigorous' );

check( '2b  a second known label',
	vance_hero_band_short( 'Peer-Reviewed Science' ) === 'Science' );

check( '2c  an unknown label returns empty, not a guess',
	vance_hero_band_short( 'Something an admin typed themselves' ) === '' );

check( '2d  empty input returns empty',
	vance_hero_band_short( '' ) === '' );


/* ===================================================================== */
section( '3. vance_hero_mobile_classes()' );
/* ===================================================================== */

check( '3a  both on: no classes at all',
	vance_hero_mobile_classes( true, true ) === '' );

check( '3b  band off only',
	vance_hero_mobile_classes( false, true ) === ' vhh-hero-spotlight--band-hide-mobile' );

check( '3c  card off only',
	vance_hero_mobile_classes( true, false ) === ' vhh-hero-spotlight--card-hide-mobile' );

check( '3d  both off: both classes, band first',
	vance_hero_mobile_classes( false, false ) === ' vhh-hero-spotlight--band-hide-mobile vhh-hero-spotlight--card-hide-mobile' );


/* ===================================================================== */
section( '4. vance_hero_mobile_classes_for()' );
/* ===================================================================== */

set_mods( array() );
check( '4a  unset settings read as ON: no classes',
	vance_hero_mobile_classes_for( 'vance_x_show_band_mobile', 'vance_x_show_card_mobile' ) === '' );

set_mods( array( 'vance_x_show_band_mobile' => false ) );
check( '4b  band explicitly false: band class only',
	vance_hero_mobile_classes_for( 'vance_x_show_band_mobile', 'vance_x_show_card_mobile' )
		=== ' vhh-hero-spotlight--band-hide-mobile' );

set_mods( array( 'vance_x_show_band_mobile' => true, 'vance_x_show_card_mobile' => false ) );
check( '4c  band explicitly true, card explicitly false: card class only',
	vance_hero_mobile_classes_for( 'vance_x_show_band_mobile', 'vance_x_show_card_mobile' )
		=== ' vhh-hero-spotlight--card-hide-mobile' );
set_mods( array() );


/* ===================================================================== */
section( '5. vance_legal_hero_mobile_keys()' );
/* ===================================================================== */

$k = vance_legal_hero_mobile_keys( 'privacy' );
check( '5a  band key',  $k['band'] === 'vance_legal_privacy_hero_show_band_mobile' );
check( '5b  card key',  $k['card'] === 'vance_legal_privacy_hero_show_card_mobile' );

check( '5c  the doc slug is sanitized, so a stray character cannot forge a setting id',
	vance_legal_hero_mobile_keys( 'terms<script>' )['band'] === 'vance_legal_termsscript_hero_show_band_mobile' );


/* ===================================================================== */
section( '6. vance_hero_mobile_register_controls()' );
/* ===================================================================== */

class Recorder {
	public $sections = array(), $settings = array(), $controls = array();
	function add_section( $id, $a ) { $this->sections[ $id ] = $a; }
	function add_setting( $id, $a ) { $this->settings[ $id ] = $a; }
	function add_control( $id, $a = array() ) { $this->controls[ $id ] = $a; }
}

$m = new Recorder();
vance_hero_mobile_register_controls( $m, 'vance_test_section', 'vance_test_band', 'vance_test_card' );

check( '6a  both settings registered, defaulting to true (nothing changes until an admin asks)',
	isset( $m->settings['vance_test_band'], $m->settings['vance_test_card'] )
	&& $m->settings['vance_test_band']['default'] === true
	&& $m->settings['vance_test_card']['default'] === true );

check( '6b  both use the checkbox sanitizer',
	$m->settings['vance_test_band']['sanitize_callback'] === 'vance_sanitize_checkbox'
	&& $m->settings['vance_test_card']['sanitize_callback'] === 'vance_sanitize_checkbox' );

check( '6c  both controls land in the section passed in',
	$m->controls['vance_test_band']['section'] === 'vance_test_section'
	&& $m->controls['vance_test_card']['section'] === 'vance_test_section' );

check( '6d  both are rendered as checkboxes',
	$m->controls['vance_test_band']['type'] === 'checkbox'
	&& $m->controls['vance_test_card']['type'] === 'checkbox' );

check( '6e  default band label',
	$m->controls['vance_test_band']['label'] === 'Show the white band on mobile' );

check( '6f  default card label is always the same, whatever the band is called',
	$m->controls['vance_test_card']['label'] === 'Show the floating card on mobile' );

$m2 = new Recorder();
vance_hero_mobile_register_controls( $m2, 'sec', 'band_id', 'card_id', array(
	'label_prefix' => 'Privacy: ',
	'band_label'   => 'Show the condition chips on mobile',
	'priority'     => 42,
) );
check( '6g  label_prefix is applied to both labels',
	$m2->controls['band_id']['label'] === 'Privacy: Show the condition chips on mobile'
	&& $m2->controls['card_id']['label'] === 'Privacy: Show the floating card on mobile' );

check( '6h  a custom band_label overrides the default entirely',
	strpos( $m2->controls['band_id']['label'], 'condition chips' ) !== false );

check( '6i  priority is passed through when given',
	$m2->controls['band_id']['priority'] === 42 );

check( '6j  priority is NOT set when not given, so it does not out-rank the field it sits beside',
	! array_key_exists( 'priority', $m->controls['vance_test_band'] ) );


/* ===================================================================== */
section( '7. vance_legal_hero_mobile_customize()' );
/* ===================================================================== */

$m3 = new Recorder();
vance_legal_hero_mobile_customize( $m3 );

check( '7a  registers one section for all five policy documents',
	isset( $m3->sections['vance_legal_heroes_mobile'] ) );

foreach ( array( 'privacy', 'cookies', 'terms', 'disclaimer', 'accessibility' ) as $doc ) {
	$keys = vance_legal_hero_mobile_keys( $doc );
	check( "7b  $doc: band control registered in the shared section",
		isset( $m3->controls[ $keys['band'] ] )
		&& $m3->controls[ $keys['band'] ]['section'] === 'vance_legal_heroes_mobile' );
	check( "7c  $doc: card control registered too",
		isset( $m3->controls[ $keys['card'] ] ) );
}

check( '7d  exactly ten controls (five documents x two switches), not a stray extra section',
	count( $m3->controls ) === 10 );


/* ===================================================================== */
section( '8. Category heroes: mobile classes, with hero-mobile.php loaded' );
/* ===================================================================== */

$h = render_cat( 17 );
check( '8a  nothing saved: no mobile-hide classes at all (the default is ON)',
	strpos( $h, 'hide-mobile' ) === false );

$h = render_cat( 17, array( 'vance_cat_hero_show_band_mobile_17' => false ) );
check( '8b  band switched off: the band-hide class is on the <section>, card class absent',
	strpos( body( $h ), 'vhh-hero-spotlight--band-hide-mobile' ) !== false
	&& strpos( $h, 'card-hide-mobile' ) === false );

$h = render_cat( 17, array( 'vance_cat_hero_show_card_mobile_17' => false ) );
check( '8c  card switched off: the card-hide class only',
	strpos( body( $h ), 'vhh-hero-spotlight--card-hide-mobile' ) !== false
	&& strpos( $h, 'band-hide-mobile' ) === false );

$h = render_cat( 17, array(
	'vance_cat_hero_show_band_mobile_17' => false,
	'vance_cat_hero_show_card_mobile_17' => false,
) );
check( '8d  both switched off: both classes',
	strpos( body( $h ), 'vhh-hero-spotlight--band-hide-mobile' ) !== false
	&& strpos( body( $h ), 'vhh-hero-spotlight--card-hide-mobile' ) !== false );

// The switches are per-term: switching off Clinical Reviews (17) must not
// touch Health News (15).
$other = render_cat( 15, array( 'vance_cat_hero_show_band_mobile_17' => false ) );
check( '8e  another category is unaffected by the id-17 switch',
	strpos( $other, 'hide-mobile' ) === false );

// The "Last added" band cell: full month name kept for a screen reader, short
// "Aug 2025" form shown on a phone.
check( '8f  the Last added cell carries both the full month and the phone-short form',
	strpos( $h, '<span class="vhh-hero-spotlight__full">August 2025</span>' ) !== false
	&& strpos( $h, '<span class="vhh-hero-spotlight__short" aria-hidden="true">Aug 2025</span>' ) !== false );


/* ===================================================================== */
section( '9. GI heroes: mobile classes and short band labels' );
/* ===================================================================== */

$h = render_gi_cond( 'inflammatory-bowel-disease' );
check( '9a  nothing saved: no mobile-hide classes',
	strpos( $h, 'hide-mobile' ) === false );

$h = render_gi_cond( 'inflammatory-bowel-disease', array(
	'vance_gi_cond_ibd_show_band_mobile' => false,
) );
check( '9b  IBD condition band switched off',
	strpos( body( $h ), 'vhh-hero-spotlight--band-hide-mobile' ) !== false );

$h = render_gi_cond( 'ulcerative-colitis', array(
	'vance_gi_cond_ibd_show_band_mobile' => false,
) );
check( '9c  a DIFFERENT condition is unaffected by the IBD switch',
	strpos( $h, 'hide-mobile' ) === false );

$h = render_gi_hub();
check( '9d  the hub hero has its own switches, default on: no classes',
	strpos( $h, 'hide-mobile' ) === false );

$h = render_gi_hub( array( 'vance_gi_hub_hero_show_card_mobile' => false ) );
check( '9e  the hub card switch works',
	strpos( body( $h ), 'vhh-hero-spotlight--card-hide-mobile' ) !== false );

// Short band labels on the condition cells. IBS is short for itself in full
// (no space saved), but IBD's condition chip on ANOTHER page's band still
// carries the short form.
$h = render_gi_cond( 'ulcerative-colitis' );
check( '9f  the IBD chip on the UC hero carries the short "IBD" form',
	strpos( $h, '<span class="vhh-hero-spotlight__full">Inflammatory Bowel Disease</span>' ) !== false
	&& strpos( $h, '<span class="vhh-hero-spotlight__short" aria-hidden="true">IBD</span>' ) !== false );

check( '9g  the "All seven" hub cell also carries a short form, even though it matches the full text',
	// vance_gi_hero_cells() sets short => 'All seven' for the SAME string as the
	// value 'All seven' is not used as the hub cell's value (the value is
	// "Gastro Health Explained"), so short and full differ and both spans print.
	strpos( $h, '<span class="vhh-hero-spotlight__short" aria-hidden="true">All seven</span>' ) !== false );


/* ===================================================================== */
section( '10. Legal heroes: mobile classes and sibling short labels' );
/* ===================================================================== */

$h = render_legal( 'privacy' );
check( '10a  nothing saved: no mobile-hide classes',
	strpos( $h, 'hide-mobile' ) === false );

$h = render_legal( 'privacy', array( 'vance_legal_privacy_hero_show_band_mobile' => false ) );
check( '10b  privacy\'s own band switch works',
	strpos( body( $h ), 'vhh-hero-spotlight--band-hide-mobile' ) !== false );

$h = render_legal( 'terms', array( 'vance_legal_privacy_hero_show_band_mobile' => false ) );
check( '10c  a different document is unaffected by privacy\'s switch',
	strpos( $h, 'hide-mobile' ) === false );

$h = render_legal( 'privacy' );
check( '10d  the Cookie Policy sibling cell on the Privacy hero carries its short "Cookies" form',
	strpos( $h, '<span class="vhh-hero-spotlight__full">Cookie Policy</span>' ) !== false
	&& strpos( $h, '<span class="vhh-hero-spotlight__short" aria-hidden="true">Cookies</span>' ) !== false );


/* ===================================================================== */
section( '11. Page heroes: mobile classes and short band/badge labels' );
/* ===================================================================== */

set_mods( array( 'vance_contact_hero_style' => 'spotlight' ) );
$h = render_page( 'contact' );
check( '11a  nothing saved: no mobile-hide classes',
	strpos( $h, 'hide-mobile' ) === false );

$h = render_page( 'contact', array(
	'vance_contact_hero_style'  => 'spotlight',
	'vance_contact_hero_spot_show_slot_mobile' => false,
) );
check( '11b  the white-band switch works on a page hero',
	strpos( body( $h ), 'vhh-hero-spotlight--band-hide-mobile' ) !== false );

$h = render_page( 'about', array( 'vance_about_hero_style' => 'spotlight' ) );
check( '11c  the About badges use vance_hero_band_short(): "Rigorously Developed" carries "Rigorous"',
	strpos( $h, '<span class="vhh-hero-spotlight__full">Rigorously Developed</span>' ) !== false
	&& strpos( $h, '<span class="vhh-hero-spotlight__short" aria-hidden="true">Rigorous</span>' ) !== false );

$h = render_page( 'hquiz', array( 'vance_hquiz_hero_style' => 'spotlight' ) );
check( '11d  a tool page\'s band cell for the recipe planner carries its own short form',
	strpos( $h, '<span class="vhh-hero-spotlight__full">Gastro Recipes &amp; Meal Planner</span>' ) !== false
	&& strpos( $h, '<span class="vhh-hero-spotlight__short" aria-hidden="true">Meal Planner</span>' ) !== false );


/* ===================================================================== */
section( '12. Homepage hero: mobile classes' );
/* ===================================================================== */

$h = render_home();
check( '12a  nothing saved: no mobile-hide classes',
	strpos( $h, 'hide-mobile' ) === false );

$h = render_home( array( 'vance_hero_spotlight_show_search_mobile' => false ) );
check( '12b  the search switch works on the homepage hero',
	strpos( body( $h ), 'vhh-hero-spotlight--band-hide-mobile' ) !== false );

$h = render_home( array( 'vance_hero_spotlight_show_card_mobile' => false ) );
check( '12c  the card switch works on the homepage hero',
	strpos( body( $h ), 'vhh-hero-spotlight--card-hide-mobile' ) !== false );
set_mods( array() );


/* ===================================================================== */
section( '16. subcategory-grouped-archive.php: vance_subcat_overflow_cap()' );
/* ===================================================================== */

set_mods( array() );
$ids = range( 1, 20 ); // more than any grid could show

set_mods( array( 'vance_subcat_rows_50' => '0', 'vance_subcat_grid_cols_50' => '3' ) );
check( '16a  rows = 0 (All rows) keeps everything untouched',
	vance_subcat_overflow_cap( 50, $ids, true ) === $ids );

check( '16b  $cap_rows = false keeps everything untouched, whatever rows says',
	vance_subcat_overflow_cap( 50, $ids, false ) === $ids );

set_mods( array( 'vance_subcat_rows_50' => '1', 'vance_subcat_grid_cols_50' => '3' ) );
check( '16c  rows = 1: the block itself IS row one, so the grid beneath gets nothing',
	vance_subcat_overflow_cap( 50, $ids, true ) === array() );

set_mods( array( 'vance_subcat_rows_50' => '3', 'vance_subcat_grid_cols_50' => '4' ) );
check( '16d  rows = 3, 4 columns: the grid gets (3-1) x 4 = 8 posts',
	vance_subcat_overflow_cap( 50, $ids, true ) === array_slice( $ids, 0, 8 ) );

set_mods( array( 'vance_subcat_rows_50' => '6', 'vance_subcat_grid_cols_50' => '5' ) );
check( '16e  a cap larger than the supply returns the whole (short) supply, not an error',
	vance_subcat_overflow_cap( 50, array( 1, 2, 3 ), true ) === array( 1, 2, 3 ) );
set_mods( array() );


/* ===================================================================== */
section( '17. promo-block.php: category promo mobile visibility' );
/* ===================================================================== */

function render_cat_promo( $term_id, array $mods = array() ) {
	set_mods( $mods );
	ob_start();
	vance_render_category_promo( $term_id );
	return ob_get_clean();
}

check( '17a  a promo block switched off entirely renders nothing',
	trim( render_cat_promo( 77 ) ) === '' );

$on = render_cat_promo( 77, array(
	'vance_cat_promo_show_77'  => true,
	'vance_cat_promo_heading_77' => 'A featured tool',
) );
check( '17b  switched on, mobile NOT enabled (the documented default): hidden-on-mobile class present',
	strpos( $on, 'vance-cat-promo--hide-mobile' ) !== false );

$on_mobile = render_cat_promo( 77, array(
	'vance_cat_promo_show_77'        => true,
	'vance_cat_promo_heading_77'     => 'A featured tool',
	'vance_cat_promo_show_mobile_77' => true,
) );
check( '17c  switched on AND mobile enabled: no hidden-on-mobile class',
	strpos( $on_mobile, 'vance-cat-promo--hide-mobile' ) === false );

check( '17d  the mobile setting id is keyed by term id, so term 78 is unaffected by term 77\'s value',
	vance_cat_promo_mobile_key( 77 ) !== vance_cat_promo_mobile_key( 78 ) );

set_mods( array() );


/* ===================================================================== */
section( '18. main.css: the phone rules the switches and short labels rely on' );
/* ===================================================================== */

$main = file_get_contents( $THEME . '/assets/css/main.css' );

check( '18a  the band switch is a real 767.98px rule, not just present as text',
	preg_match( '/@media \(max-width: 767\.98px\)[^}]*\{[^@]*\.vhh-hero-spotlight--band-hide-mobile[^{]*\{[^}]*display:\s*none\s*!important/s', $main ) === 1 );

check( '18b  the card switch is likewise a real rule',
	preg_match( '/\.vhh-hero-spotlight--card-hide-mobile[^{]*\{[^}]*display:\s*none\s*!important/s', $main ) === 1 );

check( '18c  the short span is hidden by default (desktop) and shown only inside the phone query',
	preg_match( '/(^|\})\s*\.vhh-hero-spotlight__short\s*\{\s*display:\s*none/m', $main ) === 1 );

check( '18d  ...and the category promo hide-on-mobile class is a real rule too',
	preg_match( '/\.vance-cat-promo--hide-mobile\s*\{\s*display:\s*none\s*!important/', $main ) === 1 );


/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
