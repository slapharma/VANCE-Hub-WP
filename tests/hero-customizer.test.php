<?php
/**
 * Runs vance_page_hero_spotlight_customize() against a recording stub of
 * WP_Customize_Manager and asserts on what it registered.
 */

define( 'ABSPATH', true );
$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

$GLOBALS['MODS'] = array();
function vance_get_theme_mod( $k, $d = '' ) { return array_key_exists( $k, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $k ] : $d; }
function get_theme_mod( $k, $d = '' ) { return vance_get_theme_mod( $k, $d ); }
function get_template_directory_uri() { return 'https://example.test/theme'; }
function get_template_directory() { return $GLOBALS['THEME_DIR']; }
function home_url( $p = '/' ) { return 'https://example.test' . $p; }
function get_page_by_path( $s ) { return null; }
function get_permalink( $p ) { return 'https://example.test/x/'; }
function get_search_query() { return ''; }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_url( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_attr_e( $t, $d = '' ) { echo esc_attr( $t ); }
function wp_kses_post( $t ) { return (string) $t; }
function __( $t, $d = '' ) { return $t; }
function vance_gi_hub_url() { return 'https://example.test/hub/'; }
function vance_sanitize_checkbox( $c ) { return (bool) $c; }
function sanitize_hex_color( $c ) { return $c; }
function sanitize_text_field( $c ) { return $c; }
function sanitize_textarea_field( $c ) { return $c; }
function esc_url_raw( $c ) { return $c; }

class WP_Customize_Color_Control { public $id; function __construct( $m, $id, $a ) { $this->id = $id; $m->rec_control( $id, $a, 'color' ); } }
class WP_Customize_Image_Control { public $id; function __construct( $m, $id, $a ) { $this->id = $id; $m->rec_control( $id, $a, 'image' ); } }

class Recorder {
    public $sections = array(), $settings = array(), $controls = array();
    function add_section( $id, $a ) { $this->sections[ $id ] = $a; }
    function add_setting( $id, $a ) { $this->settings[ $id ] = $a; }
    function add_control( $id, $a = array(), $t = null ) {
        if ( is_object( $id ) ) { return; } // already recorded via rec_control
        $this->controls[ $id ] = $a;
    }
    function rec_control( $id, $a, $t ) { $a['__type'] = $t; $this->controls[ $id ] = $a; }
}

$GLOBALS['THEME_DIR'] = $THEME;
require_once $THEME . '/inc/hero-spotlight.php';
require_once $THEME . '/inc/page-hero-spotlight.php';

$PASS = 0; $FAIL = 0;
function check( $name, $got, $want = true ) {
    global $PASS, $FAIL;
    $ok = ( $want === true ) ? ( $got === true ) : ( $got === $want );
    if ( $ok ) { $PASS++; echo "  ok   $name\n"; }
    else { $FAIL++; echo "  FAIL $name\n       expected: " . var_export( $want, true ) . "\n       got     : " . var_export( $got, true ) . "\n"; }
}

$m = new Recorder();
vance_page_hero_spotlight_customize( $m );

echo "\n=== Registration ===\n";
check( 'contact toggle registered', isset( $m->settings['vance_contact_hero_style'] ) );
check( 'about   toggle registered', isset( $m->settings['vance_about_hero_style'] ) );
check( 'contact toggle defaults to classic', $m->settings['vance_contact_hero_style']['default'], 'classic' );
check( 'about   toggle defaults to classic', $m->settings['vance_about_hero_style']['default'], 'classic' );
check( 'toggle sits in the page\'s existing Hero Section', $m->controls['vance_contact_hero_style']['section'], 'vance_contact_hero' );
check( 'contact spotlight section registered', isset( $m->sections['vance_contact_hero_spotlight'] ) );
check( 'about   spotlight section registered', isset( $m->sections['vance_about_hero_spotlight'] ) );
check( 'contact section hangs off the contact panel', $m->sections['vance_contact_hero_spotlight']['panel'], 'vance_contact_panel' );
check( 'about   section hangs off the about panel',   $m->sections['vance_about_hero_spotlight']['panel'],   'vance_about_panel' );

echo "\n=== Sanitizers actually reject junk ===\n";
$san = $m->settings['vance_contact_hero_style']['sanitize_callback'];
check( "'spotlight' survives", $san( 'spotlight' ), 'spotlight' );
check( "'classic' survives",   $san( 'classic' ),   'classic' );
check( "junk falls back",      $san( '<script>' ), 'classic' );
check( "empty falls back",     $san( '' ),         'classic' );

echo "\n=== The toggle lands in each page's own hero section ===\n";
// Not derivable from the page key: the tool pages keep their hero controls
// under the Tools panel, in sections named after the tool. A toggle registered
// into a section that does not exist is silently dropped by WordPress.
$expected_sections = array(
    'contact'      => 'vance_contact_hero',
    'about'        => 'vance_about_hero',
    'hquiz'        => 'vance_hquiz_hero',
    'recipes'      => 'vance_tools_hero_recipes',
    'malnutrition' => 'vance_tools_hero_malnutrition',
    // Registered in functions.php, not customizer-pages.php -- at
    // customize_register priority 10, while this runs at 20.
    'askai'        => 'vance_askai_settings',
    'evidence'     => 'vance_evidence_hero',
    'userguide'    => 'vance_userguide_hero',
);
foreach ( vance_page_hero_spotlight_pages() as $p ) {
    check( "$p toggle registered", isset( $m->settings[ 'vance_' . $p . '_hero_style' ] ) );
    check( "$p toggle defaults to classic", $m->settings[ 'vance_' . $p . '_hero_style' ]['default'], 'classic' );
    check( "$p toggle sits in $expected_sections[$p]",
           $m->controls[ 'vance_' . $p . '_hero_style' ]['section'], $expected_sections[ $p ] );
    $c = vance_page_hero_spotlight_config( $p );
    check( "$p spotlight section hangs off " . $c['panel'],
           $m->sections[ $c['section'] ]['panel'], $c['panel'] );
}

echo "\n=== Section titles are distinct where sections share a panel ===\n";
// Three of these live under the Tools panel; two of them have to say which
// tool they belong to or an admin cannot tell them apart.
$titles = array();
foreach ( vance_page_hero_spotlight_pages() as $p ) {
    $c = vance_page_hero_spotlight_config( $p );
    $titles[ $c['panel'] ][] = $m->sections[ $c['section'] ]['title'];
}
foreach ( $titles as $panel => $list ) {
    check( "$panel: no two spotlight sections share a title",
           count( array_unique( $list ) ), count( $list ) );
}

echo "\n=== Every field the renderer reads has a control, and vice versa ===\n";
foreach ( vance_page_hero_spotlight_pages() as $p ) {
    $fields   = array_keys( vance_page_hero_spotlight_field_defaults( $p ) );
    $prefix   = 'vance_' . $p . '_hero_spot_';
    $declared = array();
    foreach ( array_keys( $m->settings ) as $id ) {
        if ( strpos( $id, $prefix ) === 0 ) { $declared[] = substr( $id, strlen( $prefix ) ); }
    }
    sort( $fields ); sort( $declared );
    check( "$p: controls match the renderer's fields exactly", $declared, $fields );

    $no_control = array();
    foreach ( $declared as $f ) { if ( ! isset( $m->controls[ $prefix . $f ] ) ) { $no_control[] = $f; } }
    check( "$p: every setting has a visible control", $no_control, array() );

    $defaults = vance_page_hero_spotlight_field_defaults( $p );
    $bad = array();
    foreach ( $declared as $f ) {
        if ( $m->settings[ $prefix . $f ]['default'] !== $defaults[ $f ] ) { $bad[] = $f; }
    }
    check( "$p: registered defaults equal the renderer's defaults", $bad, array() );
}

echo "\n=== The image control is the separate key, not the classic hero's ===\n";
check( 'contact image control exists', isset( $m->controls['vance_contact_hero_spot_image'] ) );
check( 'and is an image control',      $m->controls['vance_contact_hero_spot_image']['__type'], 'image' );
check( 'the classic image key is untouched', isset( $m->settings['vance_contact_hero_img'] ), false );

echo "\n" . str_repeat( '-', 58 ) . "\n  PASSED $PASS   FAILED $FAIL\n";
exit( $FAIL === 0 ? 0 : 1 );
