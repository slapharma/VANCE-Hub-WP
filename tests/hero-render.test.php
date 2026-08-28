<?php
/**
 * Renders the Contact and About spotlight heroes outside WordPress, against a
 * controllable bag of theme mods, and asserts on the real output.
 *
 * Every check here must be able to go red — see the --mutate flag at the
 * bottom, which breaks the source on purpose so the suite can be watched
 * failing before its green result is trusted.
 */

define( 'ABSPATH', true );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

/* ---- the mod bag the stubs read ------------------------------------- */
$GLOBALS['MODS'] = array();
function set_mods( array $m ) { $GLOBALS['MODS'] = $m; }

/* ---- WordPress stubs ------------------------------------------------- */
function vance_get_theme_mod( $key, $default = '' ) {
    return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
}
function get_theme_mod( $key, $default = '' ) { return vance_get_theme_mod( $key, $default ); }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/vance-health-hub'; }
function get_template_directory() { return $GLOBALS['THEME_DIR']; }
function home_url( $p = '/' ) { return 'https://example.test' . $p; }
function get_page_by_path( $slug ) { return isset( $GLOBALS['PAGES'][ $slug ] ) ? $slug : null; }
function get_permalink( $p ) { return 'https://example.test/' . $p . '/'; }
function get_search_query() { return ''; }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_url( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_attr_e( $t, $d = '' ) { echo esc_attr( $t ); }
function wp_kses_post( $t ) { return (string) $t; }
function __( $t, $d = '' ) { return $t; }
function _e( $t, $d = '' ) { echo $t; }
function vance_gi_hub_url() { return 'https://example.test/gastro-health-explained/'; }
function sanitize_hex_color( $c ) { return $c; }

$GLOBALS['THEME_DIR'] = $THEME;
$GLOBALS['PAGES'] = array( 'ask-ai' => 1, 'knowledgebase' => 1 );

require_once $THEME . '/inc/hero-spotlight.php';
require_once $THEME . '/inc/page-hero-spotlight.php';

/* ---- tiny assertion runner ------------------------------------------ */
$PASS = 0; $FAIL = 0;
function check( $name, $got, $want = true ) {
    global $PASS, $FAIL;
    $ok = ( $want === true ) ? ( $got === true ) : ( $got === $want );
    if ( $ok ) { $PASS++; echo "  ok   $name\n"; }
    else {
        $FAIL++;
        echo "  FAIL $name\n";
        echo "       expected: " . var_export( $want, true ) . "\n";
        echo "       got     : " . var_export( $got, true ) . "\n";
    }
}
function render( $page ) {
    ob_start();
    vance_render_page_hero_spotlight( $page );
    return ob_get_clean();
}
function tags_balanced( $html ) {
    $void = array( 'br','img','input','hr','meta','link','path','circle','rect','source' );
    $stack = array();
    preg_match_all( '#<(/?)([a-zA-Z][\w-]*)([^>]*?)(/?)>#', $html, $m, PREG_SET_ORDER );
    foreach ( $m as $t ) {
        $name = strtolower( $t[2] );
        if ( in_array( $name, $void, true ) || $t[4] === '/' ) { continue; }
        if ( $t[1] === '/' ) {
            if ( ! $stack || array_pop( $stack ) !== $name ) { return "mismatch at </$name>"; }
        } else { $stack[] = $name; }
    }
    return $stack ? 'unclosed: ' . implode( ',', $stack ) : true;
}

echo "\n=== 0. A PRISTINE site: nothing saved but the toggle ===\n";
// The bug this exists for: reading the shared copy keys with an '' default
// rendered an empty hero on the live site, while the Customizer preview -- which
// serves each setting's REGISTERED default -- looked perfectly correct.
set_mods( array( "vance_contact_hero_style" => "spotlight" ) );
$p1 = render( "contact" );
check( "contact: eyebrow is not empty",  strpos( $p1, "Get in Touch" ) !== false );
check( "contact: headline is not empty", strpos( $p1, "Hear From You" ) !== false );
check( "contact: intro is not empty",    strpos( $p1, "here to help" ) !== false );
check( "contact: band has the email",    strpos( $p1, "team@vancemedicalfoods.co.uk" ) !== false );
check( "contact: band has the phone",    strpos( $p1, "+44 (0)1628 526 005" ) !== false );
check( "contact: band has the hours",    strpos( $p1, "9:00 am" ) !== false );

set_mods( array( "vance_about_hero_style" => "spotlight" ) );
$p2 = render( "about" );
check( "about: eyebrow is not empty",  strpos( $p2, "About Vance Medical Hub" ) !== false );
check( "about: headline is not empty", strpos( $p2, "Driven by Science" ) !== false );
check( "about: intro is not empty",    strpos( $p2, "bridge pharmaceutical expertise" ) !== false );
check( "about: badge 1 present",       strpos( $p2, "Pharma-Grade Quality" ) !== false );
check( "about: badge 2 present",       strpos( $p2, "Clinician Approved" ) !== false );
check( "about: badge 3 present",       strpos( $p2, "Evidence-Based" ) !== false );
check( "about: stat figure present",   strpos( $p2, ">30+<" ) !== false );

// Nothing may render as an empty element on a pristine site.
foreach ( array( "contact" => $p1, "about" => $p2 ) as $pg => $html ) {
    check( "$pg: no empty headline",  preg_match( '/__title"><\/h1>/', $html ), 0 );
    check( "$pg: no empty eyebrow",   preg_match( '/__eyebrow"><\/span>/', $html ), 0 );
}

echo "\n=== 0b. Those defaults must match the CLASSIC templates, verbatim ===\n";
// If a template default is reworded and this file is not, switching hero design
// would silently change the page's words.
// The templates are PHP source, so an apostrophe inside a single-quoted
// literal is stored escaped. Normalise before comparing against the
// runtime string, or every default containing one reports a false drift.
$unescape = function ( $src ) { return str_replace( array( '\\\'', '\\\\' ), array( "'", '\\' ), $src ); };
$tpl = array(
    "contact" => $unescape( file_get_contents( $THEME . "/page-contact-us.php" ) ),
    "about"   => $unescape( file_get_contents( $THEME . "/page-about.php" ) ),
);
$pairs = array(
    array( "contact", "legacy_tag_default" ),   array( "contact", "legacy_desc_default" ),
    array( "about",   "legacy_tag_default" ),   array( "about",   "legacy_desc_default" ),
    array( "about",   "legacy_title_default" ), array( "contact", "legacy_title_default" ),
);
foreach ( $pairs as $pair ) {
    list( $pg, $field ) = $pair;
    $conf = vance_page_hero_spotlight_config( $pg );
    $val  = $conf[ $field ];
    check( "$pg/$field appears verbatim in the classic template",
           strpos( $tpl[ $pg ], $val ) !== false );
}

echo "\n=== 1. The toggle actually gates both pages ===\n";
set_mods( array() );
check( 'contact defaults to classic (spotlight OFF)', vance_page_hero_spotlight_active( 'contact' ), false );
check( 'about   defaults to classic (spotlight OFF)', vance_page_hero_spotlight_active( 'about' ),   false );
set_mods( array( 'vance_contact_hero_style' => 'spotlight', 'vance_about_hero_style' => 'spotlight' ) );
check( 'contact switches on', vance_page_hero_spotlight_active( 'contact' ) );
check( 'about   switches on', vance_page_hero_spotlight_active( 'about' ) );
set_mods( array( 'vance_contact_hero_style' => 'nonsense' ) );
check( 'a junk value falls back to classic', vance_page_hero_spotlight_active( 'contact' ), false );
check( 'an unknown page is never active', vance_page_hero_spotlight_active( 'sitemap' ), false );

echo "\n=== 2. Contact hero renders the live settings ===\n";
set_mods( array(
    'vance_contact_hero_style' => 'spotlight',
    'vance_contact_hero_tag'   => 'Get in Touch',
    'vance_contact_hero_title' => 'We\'d Love to <span class="highlight">Hear From You</span>',
    'vance_contact_hero_desc'  => 'Our team is here to help.',
    'vance_contact_email'      => 'team@vancemedicalfoods.co.uk',
    'vance_contact_phone'      => '+44 (0)1628 526 005',
    'vance_contact_hours'      => 'Monday – Friday, 9:00 am – 5:00 pm GMT',
) );
$c = render( 'contact' );
check( 'markup is balanced', tags_balanced( $c ) );
check( 'eyebrow comes from the classic hero key', strpos( $c, 'Get in Touch' ) !== false );
check( 'headline keeps its highlight span',       strpos( $c, '<span class="highlight">Hear From You</span>' ) !== false );
check( 'intro comes from the classic hero key',   strpos( $c, 'Our team is here to help.' ) !== false );
check( 'email cell is a real mailto:',            strpos( $c, 'href="mailto:team@vancemedicalfoods.co.uk"' ) !== false );
check( 'phone cell strips punctuation for tel:',  strpos( $c, 'href="tel:+441628526005"' ) !== false );
check( 'phone is still shown formatted',          strpos( $c, '+44 (0)1628 526 005' ) !== false );
check( 'hours cell is NOT a link',                strpos( $c, 'href="tel:Monday' ) === false );
check( 'band is the lines variant',               strpos( $c, 'vhh-hero-spotlight__slot--lines' ) !== false );
check( 'card is the text variant (has a heading)',strpos( $c, 'vhh-hero-spotlight__card-title' ) !== false );
check( 'card is NOT the stat variant',            strpos( $c, 'card--stat' ) === false );
check( 'ghost CTA resolves to the Ask AI page',   strpos( $c, 'https://example.test/ask-ai/' ) !== false );
check( 'primary CTA targets the form anchor',     strpos( $c, 'href="#contact-form"' ) !== false );
check( 'shares the homepage hero class',          strpos( $c, 'class="vhh-hero-spotlight vhh-hero-spotlight--page' ) !== false );

echo "\n=== 2b. tel: normalisation across the formats an admin might type ===\n";
$tel_cases = array(
    '+44 (0)1628 526 005' => '+441628526005',   // the live value: bracketed trunk 0 must go
    '+44 1628 526005'     => '+441628526005',
    '+44(0)1628526005'    => '+441628526005',
    '(01628) 526005'      => '01628526005',     // national form: its leading 0 is load-bearing
    '01628 526 005'       => '01628526005',
    '+1 (555) 010-9999'   => '+15550109999',    // a bracketed area code is NOT a trunk 0
);
foreach ( $tel_cases as $in => $want ) {
    check( "tel: '$in' -> '$want'", vance_page_hero_spotlight_tel( $in ), $want );
}

echo "\n=== 3. An empty setting drops its cell, it does not render blank ===\n";
set_mods( array(
    'vance_contact_hero_style' => 'spotlight',
    'vance_contact_email' => '', 'vance_contact_phone' => '', 'vance_contact_hours' => 'Mon-Fri',
) );
$c2 = render( 'contact' );
check( 'no mailto: cell when the email is cleared', strpos( $c2, 'mailto:' ) === false );
check( 'no tel: cell when the phone is cleared',    strpos( $c2, 'tel:' ) === false );
check( 'the surviving hours cell still renders',    strpos( $c2, 'Mon-Fri' ) !== false );
set_mods( array( 'vance_contact_hero_style' => 'spotlight',
                 'vance_contact_email' => '', 'vance_contact_phone' => '', 'vance_contact_hours' => '' ) );
$c3 = render( 'contact' );
check( 'the whole band disappears when all three are cleared', strpos( $c3, '__slot--lines' ) === false );
check( 'the hero itself still renders',                        strpos( $c3, '__title' ) !== false );

echo "\n=== 4. About hero reuses the badges and stat 1 ===\n";
set_mods( array(
    'vance_about_hero_style'   => 'spotlight',
    'vance_about_hero_tag'     => 'About Vance Medical Hub',
    'vance_about_hero_title'   => 'Trusted by Patients.<br><span class="highlight">Driven by Science.</span>',
    'vance_about_badge1_label' => 'Pharma-Grade Quality',
    'vance_about_badge2_label' => 'Clinician Approved',
    'vance_about_badge3_label' => 'Evidence-Based',
    'vance_about_stat1_num'    => '30+',
    'vance_about_stat1_label'  => 'Years of Pharmaceutical Experience',
) );
$a = render( 'about' );
check( 'markup is balanced', tags_balanced( $a ) );
check( 'badge 1 renders', strpos( $a, 'Pharma-Grade Quality' ) !== false );
check( 'badge 2 renders', strpos( $a, 'Clinician Approved' ) !== false );
check( 'badge 3 renders', strpos( $a, 'Evidence-Based' ) !== false );
check( 'band is the badges variant',   strpos( $a, 'vhh-hero-spotlight__slot--badges' ) !== false );
check( 'card is the stat variant',     strpos( $a, 'vhh-hero-spotlight__card--stat' ) !== false );
check( 'card shows stat 1 figure',     strpos( $a, '>30+<' ) !== false );
check( 'card shows stat 1 label',      strpos( $a, 'Years of Pharmaceutical Experience' ) !== false );
check( 'no text-card heading on About',strpos( $a, 'card-title' ) === false );
check( 'headline <br> survives',       strpos( $a, 'Trusted by Patients.<br>' ) !== false );
check( 'mission anchor is the ghost CTA', strpos( $a, 'href="#mission"' ) !== false );
check( 'story anchor is the primary CTA', strpos( $a, 'href="#our-story"' ) !== false );

echo "\n=== 5. The badges' own visibility switch is honoured ===\n";
set_mods( array( 'vance_about_hero_style' => 'spotlight', 'vance_about_badges_show' => false,
                 'vance_about_badge1_label' => 'Pharma-Grade Quality' ) );
$a2 = render( 'about' );
check( 'band hidden when Show Trust Badges is off', strpos( $a2, '__slot--badges' ) === false );
check( 'the rest of the hero survives',             strpos( $a2, '__card--stat' ) !== false );

echo "\n=== 6. Colours reach the style attribute, and the dissolve maths is right ===\n";
set_mods( array(
    'vance_about_hero_style'          => 'spotlight',
    'vance_about_hero_spot_bg_from'   => '#102030',
    'vance_about_hero_spot_card_bg_color' => '#ABCDEF',
) );
$a3 = render( 'about' );
check( 'custom band colour is emitted',   strpos( $a3, '--vhh-hs-from: #102030' ) !== false );
check( 'and as an rgb triple for the fade', strpos( $a3, '--vhh-hs-from-rgb: 16, 32, 48' ) !== false );
check( 'custom card colour is emitted',   strpos( $a3, '--vhh-hs-card-bg: #ABCDEF' ) !== false );
check( 'default teal survives elsewhere', strpos( $a3, '--vhh-hs-title: #04504E' ) !== false );

echo "\n=== 7. Customizer field list and renderer cannot drift ===\n";
foreach ( array( 'contact', 'about' ) as $p ) {
    $d = vance_page_hero_spotlight_field_defaults( $p );
    $v = vance_page_hero_spotlight_values( $p );
    $missing = array_diff( array_keys( $d ), array_keys( $v ) );
    check( "$p: every declared field is resolved", $missing, array() );
}
$cd = vance_page_hero_spotlight_field_defaults( 'contact' );
$ad = vance_page_hero_spotlight_field_defaults( 'about' );
check( 'contact declares a card heading', array_key_exists( 'card_title', $cd ) );
check( 'about does NOT (it shows stat 1)', array_key_exists( 'card_title', $ad ), false );
check( 'unknown page yields no fields', vance_page_hero_spotlight_field_defaults( 'sitemap' ), array() );
check( 'unknown page renders nothing', render( 'sitemap' ), '' );

echo "\n=== 8. The stylesheet backs what the renderer emits ===\n";
$css = file_get_contents( $THEME . "/assets/css/main.css" );

// Every class the renderer prints must have a rule, or it is dead markup.
$emitted = array();
foreach ( array( "contact", "about" ) as $p ) {
    set_mods( array( "vance_" . $p . "_hero_style" => "spotlight",
                     "vance_contact_email" => "a@b.co", "vance_contact_phone" => "+44 1", "vance_contact_hours" => "9-5",
                     "vance_about_badge1_label" => "One" ) );
    preg_match_all( '/class="([^"]*vhh-hero-spotlight[^"]*)"/', render( $p ), $m );
    foreach ( $m[1] as $attr ) { foreach ( explode( " ", $attr ) as $cls ) {
        if ( strpos( $cls, "vhh-hero-spotlight" ) === 0 ) { $emitted[ $cls ] = true; }
    } }
}
$styled = array( "vhh-hero-spotlight--page", "vhh-hero-spotlight--contact", "vhh-hero-spotlight--about",
                 "vhh-hero-spotlight__slot--lines", "vhh-hero-spotlight__slot--badges",
                 "vhh-hero-spotlight__line-body", "vhh-hero-spotlight__slot-wrap",
                 // structural hooks the HOMEPAGE hero already emits unstyled;
                 // this hero mirrors its markup, so they are inherited, not new
                 "vhh-hero-spotlight__copy", "vhh-hero-spotlight__card-body" );
$unstyled = array();
foreach ( array_keys( $emitted ) as $cls ) {
    if ( in_array( $cls, $styled, true ) ) { continue; }   // deliberate hooks, no rule needed
    if ( strpos( $css, "." . $cls ) === false ) { $unstyled[] = $cls; }
}
check( "every rendered class has a CSS rule (bar the named hooks)", $unstyled, array() );

// The band must size itself to however many cells survived, not to three.
preg_match_all( '/\.vhh-hero-spotlight__slot \{(.*?)\}/s', $css, $slots );
$rules = implode( "
", $slots[1] );
// Strip CSS comments first: the rule's own comment explains why it is NOT
// repeat(3, ...), and matching that would make this assertion unfailable.
$rules = preg_replace( '#/\*.*?\*/#s', '', $rules );
check( "band uses auto columns", strpos( $rules, "grid-auto-flow: column" ) !== false );
check( "band does NOT hard-code three columns", strpos( $rules, "repeat(3" ) === false );
check( "band resets auto-flow when it stacks", strpos( $css, "grid-auto-flow: row" ) !== false );

// The square-cards block must stay the last block in the file.
$i_new = strpos( $css, ".vhh-hero-spotlight__eyebrow" );
$i_sq  = strpos( $css, "Article cards stay square", 2000 );
check( "new hero CSS sits before the square-cards block", $i_new < $i_sq );

check( "braces balance", substr_count( $css, "{" ) === substr_count( $css, "}" ) );

echo "\n" . str_repeat( '-', 58 ) . "\n";
echo "  PASSED $PASS   FAILED $FAIL\n";
exit( $FAIL === 0 ? 0 : 1 );
