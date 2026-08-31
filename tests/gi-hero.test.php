<?php
/**
 * Renders the eight Gastro Indications heroes outside WordPress and asserts on
 * the real output.
 *
 * Same shape as legal-hero.test.php: WP stubs, a controllable bag of theme
 * mods, and assertions against the emitted HTML.
 *
 * ONE THING IS DIFFERENT, and it matters. inc/gi-hero.php does not carry its
 * own list of conditions — it reads vance_gi_conditions() and
 * vance_gi_condition_cards() out of functions.php, which is 8,700 lines and
 * cannot be included here. Rather than retype those two registries as stubs,
 * which would mean this suite tests a COPY of the data and would stay green
 * while the real thing rotted, section -1 lifts their source text out of
 * functions.php and evaluates it. If that extraction ever fails it is a hard
 * error, not a skip: a suite that silently stops testing the registry is worse
 * than no suite.
 *
 * Every check here must be able to go red. `python mutate-gi.py` breaks the
 * source on purpose and confirms it does.
 */

define( 'ABSPATH', true );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';
$GLOBALS['THEME_DIR'] = $THEME;

/* ---- the mod bag the stubs read ------------------------------------- */
$GLOBALS['MODS'] = array();
function set_mods( array $m ) { $GLOBALS['MODS'] = $m; }

/* ---- WordPress stubs ------------------------------------------------- */
function vance_get_theme_mod( $key, $default = '' ) {
    return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
}
function get_theme_mod( $key, $default = '' ) { return vance_get_theme_mod( $key, $default ); }
function get_template_directory() { return $GLOBALS['THEME_DIR']; }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/vance-health-hub'; }
function home_url( $p = '/' ) { return 'https://example.test' . $p; }
function get_page_by_path( $slug ) { return isset( $GLOBALS['PAGES'][ $slug ] ) ? $slug : null; }
function get_permalink( $p ) { return 'https://example.test/' . $p . '/'; }
function get_the_title( $p = 0 ) { return isset( $GLOBALS['TITLES'][ $p ] ) ? $GLOBALS['TITLES'][ $p ] : 'Gastro Health Explained'; }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_url( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html_e( $t, $d = '' ) { echo esc_html( $t ); }
function esc_html__( $t, $d = '' ) { return esc_html( $t ); }
function __( $t, $d = '' ) { return $t; }
function wp_kses_post( $t ) { return $t; }
function add_query_arg( $k, $v, $url ) {
    return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . $k . '=' . rawurlencode( $v );
}

// Every page in the set has a real WP page, so links resolve by slug rather
// than by the literal-path fallback. Section 6 removes them to exercise it.
$GLOBALS['PAGES'] = array(
    'gastro-health-explained'    => 1,
    'inflammatory-bowel-disease' => 1, 'ulcerative-colitis'       => 1,
    'crohns-disease'             => 1, 'microscopic-colitis'      => 1,
    'irritable-bowel-syndrome'   => 1, 'colorectal-cancer'        => 1,
    'diverticular-disease'       => 1,
);
$GLOBALS['TITLES'] = array( 'gastro-health-explained' => 'Gastro Health Explained' );

/* ===================================================================== */
/* -1. The real registries, lifted out of functions.php                   */
/* ===================================================================== */
/*
 * Pulls one top-level function's source out of a file by brace matching and
 * evaluates it. Deliberately strict: anything unexpected is a fatal, because
 * the failure this guards against is the suite quietly testing a stub.
 */
function lift_function( $file, $name ) {
    $src = file_get_contents( $file );
    if ( $src === false ) { fwrite( STDERR, "FATAL: cannot read $file\n" ); exit( 2 ); }

    $sig = "\nfunction $name(";
    $at  = strpos( $src, $sig );
    if ( $at === false ) {
        fwrite( STDERR, "FATAL: $name() not found in $file — the extraction has drifted.\n" );
        exit( 2 );
    }
    $open = strpos( $src, '{', $at );
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

$FN = $THEME . '/functions.php';
lift_function( $FN, 'vance_gi_conditions' );
lift_function( $FN, 'vance_gi_condition_cards' );
lift_function( $FN, 'vance_gi_page_url' );
lift_function( $FN, 'vance_gi_hub_url' );

require_once $THEME . '/inc/gi-hero.php';

/*
 * --probe=<template> renders ONE template and exits — same reason as
 * legal-hero.test.php §9: vance_gi_hero_styles() prints its block once per
 * request behind a static, so a section that needs a first-render document has
 * to shell back into this file. See section 11.
 */
if ( isset( $argv[1] ) && strpos( $argv[1], '--probe=' ) === 0 ) {
    echo probe_template( substr( $argv[1], strlen( '--probe=' ) ) );
    exit( 0 );
}

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
function render_cond( $slug ) {
    ob_start();
    vance_render_gi_hero( $slug );
    return ob_get_clean();
}
function render_hub() {
    ob_start();
    vance_render_gi_hub_hero();
    return ob_get_clean();
}
/** Everything outside the <style> block, so a class name in a CSS selector
    cannot be mistaken for emitted markup. */
function body( $html ) { return preg_replace( '#<style\b.*?</style>#s', '', $html ); }
/** Just the <style> block. */
function styles( $html ) {
    return preg_match( '#<style\b.*?</style>#s', $html, $m ) ? $m[0] : '';
}
function tags_balanced( $html ) {
    $void = array( 'br','img','input','hr','meta','link','path','circle','rect','source','stop','use' );
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
/** The seven, in registry order. */
$SLUGS = array_keys( vance_gi_hero_meta() );

/* ===================================================================== */
echo "\n=== 0. A PRISTINE site: nothing saved at all ===\n";
/*
 * The bug this exists for is the one that shipped the About hero blank:
 * get_theme_mod() answers an unsaved read with the default the CALLER passes,
 * so a '' default renders an empty hero on the live site while looking
 * perfectly correct in the Customizer preview. This hero reads eleven mods per
 * condition page and seven on the lobby, so it has eleven chances to do it.
 */
set_mods( array() );

$first = render_cond( 'crohns-disease' );
// The stylesheet is printed once per request behind a static, so this very
// first render is the only one that carries it. Section 5 asserts on it.
$GLOBALS['FIRST_CSS'] = styles( $first );
check( 'the first render carries the stylesheet', strlen( $GLOBALS['FIRST_CSS'] ) > 400 );
check( 'a later render does NOT repeat it', styles( render_hub() ), '' );

check( 'crohns: headline renders',   strpos( $first, "Crohn’s Disease</h1>" ) !== false );
check( 'crohns: eyebrow renders',    strpos( $first, '__eyebrow">Inflammatory bowel disease<' ) !== false );
check( 'crohns: intro renders',      strpos( $first, 'from mouth to anus' ) !== false );
check( 'crohns: card title renders', strpos( $first, 'checked by a clinician' ) !== false );
check( 'crohns: band label renders', strpos( $first, 'Others in this set' ) !== false );
check( 'crohns: photograph renders', strpos( $first, 'gi-health/crohns.jpg' ) !== false );

foreach ( $SLUGS as $s ) {
    $h = body( render_cond( $s ) );
    check( "$s: renders a non-empty hero", strlen( $h ) > 600 );
    check( "$s: has a headline with text", (bool) preg_match( '#__title">\s*\S#', $h ) );
    check( "$s: has an intro with text",   (bool) preg_match( '#__intro">\s*\S#', $h ) );
    check( "$s: has an eyebrow with text", (bool) preg_match( '#__eyebrow">\s*\S#', $h ) );
}

$hub = body( render_hub() );
check( 'hub: headline renders',  strpos( $hub, 'Seven gut conditions, clearly explained' ) !== false );
check( 'hub: eyebrow renders',   strpos( $hub, '__eyebrow">Gastro health<' ) !== false );
check( 'hub: lede renders',      strpos( $hub, 'prepare for appointments' ) !== false );
check( 'hub: slot label renders',strpos( $hub, 'Jump straight to a condition' ) !== false );

/* ===================================================================== */
echo "\n=== 1. The band: always four cells, never itself ===\n";
set_mods( array() );

foreach ( $SLUGS as $s ) {
    $h = body( render_cond( $s ) );
    preg_match( '#__slot--conds">(.*?)</div>\s*</div>\s*</div>#s', $h, $m );
    $band = isset( $m[1] ) ? $m[1] : '';
    check( "$s: band has exactly 4 cells", substr_count( $band, '__line" href=' ), 4 );

    // The page is never sold back to itself. Matched on the href, because the
    // NAME of a condition legitimately appears in a sibling's copy.
    $own = vance_gi_page_url( $s );
    check( "$s: band does not link to itself", strpos( $band, 'href="' . esc_url( $own ) . '"' ) === false );

    // The fourth cell is always the lobby, in the same corner of every page.
    $cells = array();
    preg_match_all( '#__line" href="([^"]+)"#', $band, $hm );
    $cells = $hm[1];
    check( "$s: 4th cell is the lobby", end( $cells ), esc_url( vance_gi_hub_url() ) );
    check( "$s: lobby cell says All seven", strpos( $band, 'All seven' ) !== false );
}

// The band order is the registry order, not whatever the loop happened to do.
$h = body( render_cond( 'irritable-bowel-syndrome' ) );
preg_match_all( '#__line-v">([^<]+)<#', $h, $vm );
check( 'ibs: band lists mc, div, ibd then the lobby',
    $vm[1],
    array( 'Microscopic Colitis', 'Diverticular Disease', 'Inflammatory Bowel Disease', 'Gastro Health Explained' ) );

/* ===================================================================== */
echo "\n=== 2. The related map is shared, and internally sound ===\n";
$meta = vance_gi_hero_meta();
$conds = vance_gi_conditions();

check( 'meta covers exactly the canonical seven',
    array_keys( $meta ) === array_keys( $conds ) ? true : array_keys( $meta ) );

foreach ( $meta as $slug => $m ) {
    check( "$slug: has exactly 3 related", count( $m['related'] ), 3 );
    check( "$slug: related are all real conditions",
        count( array_diff( $m['related'], array_keys( $conds ) ) ), 0 );
    check( "$slug: related does not contain itself",
        in_array( $slug, $m['related'], true ), false );
    check( "$slug: related has no duplicates",
        count( array_unique( $m['related'] ) ), 3 );
    check( "$slug: related_slugs() agrees with meta",
        vance_gi_hero_related_slugs( $slug ), $m['related'] );
}

check( 'related_slugs() is empty for a stranger', vance_gi_hero_related_slugs( 'not-a-condition' ), array() );

/* ===================================================================== */
echo "\n=== 3. The eyebrow names the family, not the page ===\n";
$families = array();
foreach ( $meta as $slug => $m ) { $families[ $slug ] = $m['eyebrow']; }

check( 'four pages share the IBD family',
    count( array_keys( $families, 'Inflammatory bowel disease', true ) ), 4 );
check( 'IBS is a functional gut disorder',  $families['irritable-bowel-syndrome'], 'Functional gut disorder' );
check( 'CRC is bowel cancer',               $families['colorectal-cancer'],        'Bowel cancer' );
check( 'DD is a structural condition',      $families['diverticular-disease'],     'Structural bowel condition' );
/* The point of the field is that it names something LARGER than the page. Six
   of the seven therefore carry an eyebrow that is not their own name.
   Inflammatory Bowel Disease is the deliberate exception and always will be:
   it IS the family, so its eyebrow and its headline necessarily agree. Asserted
   as an exception rather than skipped, so that if a second page ever starts
   repeating itself in its eyebrow this goes red. */
foreach ( $meta as $slug => $m ) {
    $same = strcasecmp( $m['eyebrow'], vance_gi_hero_label( $slug ) ) === 0;
    check( "$slug: eyebrow names the family, not the page",
        $same, $slug === 'inflammatory-bowel-disease' );
}

/* ===================================================================== */
echo "\n=== 4. The lobby's chips: seven, on two fixed rows of 4 + 3 ===\n";
set_mods( array() );
$hub = body( render_hub() );

check( 'hub: exactly 7 chips', substr_count( $hub, '__chip" href=' ), 7 );
check( 'hub: exactly 2 chip rows', substr_count( $hub, '__chip-row">' ), 2 );

preg_match_all( '#__chip-row">(.*?)</div>#s', $hub, $rm );
check( 'hub: first row holds 4',  substr_count( $rm[1][0], '__chip" href=' ), 4 );
check( 'hub: second row holds 3', substr_count( $rm[1][1], '__chip" href=' ), 3 );

preg_match_all( '#__chip" href="[^"]*">\s*<span>([^<]+)</span>#', $hub, $cm );
check( 'hub: chips are the seven, in registry order',
    $cm[1], array_map( 'vance_gi_hero_label', $SLUGS ) );

// Chips carry no icon; the band cells do. This is the trade that bought two
// rows, and it is easy to "fix" back by accident.
preg_match( '#__slot--chips">.*?</div>\s*</div>\s*</div>#s', $hub, $chipband );
check( 'hub: chips carry no <svg>', strpos( $chipband[0], '<svg' ), false );
check( 'hub: band cells DO carry <svg>',
    strpos( body( render_cond( 'crohns-disease' ) ), '__line-ico"><svg' ) !== false );

// Every chip goes somewhere real.
preg_match_all( '#__chip" href="([^"]+)"#', $hub, $chm );
check( 'hub: every chip has a resolved href',
    count( array_filter( $chm[1], function ( $u ) { return strpos( $u, 'https://example.test' ) === 0; } ) ), 7 );

/* ===================================================================== */
echo "\n=== 5. Purple has exactly two jobs ===\n";
set_mods( array() );
$css = $GLOBALS['FIRST_CSS'];

check( 'css: the eyebrow is purple', strpos( $css, '.vhh-hero-spotlight--gi .vhh-hero-spotlight__eyebrow {' ) !== false );
check( 'css: eyebrow uses the tint',  strpos( $css, 'background: ' . VANCE_GI_PURPLE_TINT ) !== false );
check( 'css: eyebrow uses the strong border on the mint band',
    strpos( $css, 'border-color: ' . VANCE_GI_PURPLE_EDGE ) !== false );
// The eyebrow's edge must NOT be the chips' pale line: that pill sits on the
// mint band where its own fill is ~1.1:1, so the border is the only edge it has.
check( 'css: eyebrow border is not the chips\' pale line',
    strpos( $css, 'border-color: ' . VANCE_GI_PURPLE_LINE ) === false );

check( 'css: the CTA steps back to teal', strpos( $css, '--vhh-hs-cta-bg: #04504E' ) !== false );
check( 'css: the CTA is not left purple', strpos( $css, '--vhh-hs-cta-bg: ' . VANCE_GI_PURPLE ) === false );
check( 'css: the teal override is scoped to this set',
    (bool) preg_match( '#\.vhh-hero-spotlight--gi \{[^}]*--vhh-hs-cta-bg#s', $css ) );

check( 'css: chips hover to the solid purple',
    (bool) preg_match( '#__chip:hover,\s*\.vhh-hero-spotlight__chip:focus-visible \{\s*background: ' . preg_quote( VANCE_GI_PURPLE, '#' ) . '#s', $css ) );
check( 'css: chip label is the deep ink',
    (bool) preg_match( '#__chip span \{[^}]*color: ' . preg_quote( VANCE_GI_PURPLE_INK, '#' ) . ';#s', $css ) );
check( 'css: the eyebrow is the deep ink too',
    (bool) preg_match( '#__eyebrow \{[^}]*color: ' . preg_quote( VANCE_GI_PURPLE_INK, '#' ) . ';#s', $css ) );

// Purple is the theme's own, not a new hue.
$spot = file_get_contents( $GLOBALS['THEME_DIR'] . '/inc/hero-spotlight.php' );
check( 'the purple is the theme\'s existing hero purple',
    stripos( $spot, VANCE_GI_PURPLE ) !== false );

// Nothing else in the hero is purple.
$hub_body = body( render_hub() );
check( 'markup carries no inline purple at all',
    stripos( $hub_body, VANCE_GI_PURPLE ) === false && stripos( $hub_body, VANCE_GI_PURPLE_TINT ) === false );

// Chips must keep clearing the 44px touch target. 13px padding + 13px/1.25
// label + 2px border = 44. Asserted on the declarations, since there is no
// layout engine here.
check( 'css: chip padding is still 13px vertical', strpos( $css, 'padding: 13px 14px' ) !== false );
check( 'css: chip label is still 13px/1.25',
    (bool) preg_match( '#__chip span \{[^}]*font-size: 13px;[^}]*line-height: 1\.25;#s', $css ) );

// The rows wrap at every width. Without this a mid-width band pushes chips out
// through the rounded overflow clip.
check( 'css: chip rows wrap',
    (bool) preg_match( '#__chip-row \{[^}]*flex-wrap: wrap;#s', $css ) );

/* ===================================================================== */
echo "\n=== 6. The photograph ===\n";
set_mods( array() );

$h = body( render_cond( 'colorectal-cancer' ) );
check( 'crc: uses its own theme asset', strpos( $h, 'gi-health/colorectal-cancer.jpg' ) !== false );
check( 'crc: asset is cache-busted on mtime', (bool) preg_match( '#colorectal-cancer\.jpg\?v=\d+#', $h ) );
check( 'crc: focal comes from the registry', strpos( $h, 'object-position: 60% 26%' ) !== false );
check( 'crc: photo carries the card\'s alt text', strpos( $h, 'alt="Two men sitting' ) !== false );

$hub = body( render_hub() );
check( 'hub: uses its own picture', strpos( $hub, 'gi-health/lobby-walk.jpg' ) !== false );
check( 'hub: lobby focal comes from the renderer', strpos( $hub, 'object-position: 55% 50%' ) !== false );
// It must NOT borrow the IBD card's photograph, which is what it did before.
// Matched on the exact filename: lobby-walk.jpg now lives in that same
// directory, so a check for 'gi-health/' alone would pass on either.
check( 'hub: does not borrow gi-health/ibd.jpg', strpos( $hub, 'gi-health/ibd.jpg' ) === false );
// Nor any of the other six condition photographs.
$borrowed = array();
foreach ( vance_gi_condition_cards() as $c ) {
    if ( strpos( $hub, '/' . $c['image'] ) !== false ) { $borrowed[] = $c['image']; }
}
check( 'hub: borrows none of the seven condition photos', $borrowed, array() );
// The file has to actually exist. If it does not, vance_gi_hero_photo() returns
// null, the media slot is silently dropped and the hero loses its photograph
// with no error raised anywhere.
check( 'hub: the picture file is really on disk',
    file_exists( $GLOBALS['THEME_DIR'] . '/assets/img/gi-health/lobby-walk.jpg' ) );

// An admin image wins, and takes an empty alt with it — the stock description
// would be a lie about a different photograph.
set_mods( array( 'vance_gi_cond_crc_image' => 'https://cdn.test/mine.jpg' ) );
$h = body( render_cond( 'colorectal-cancer' ) );
check( 'crc: admin image wins',            strpos( $h, 'https://cdn.test/mine.jpg' ) !== false );
check( 'crc: theme asset is dropped',      strpos( $h, 'colorectal-cancer.jpg' ) === false );
check( 'crc: admin image has empty alt',   strpos( $h, 'alt=""' ) !== false );

// An admin focal wins over the registry's.
set_mods( array( 'vance_gi_cond_crc_focal' => '20% 80%' ) );
$h = body( render_cond( 'colorectal-cancer' ) );
check( 'crc: admin focal wins', strpos( $h, 'object-position: 20% 80%' ) !== false );

// The focal sanitiser is a whitelist, not a filter — it is printed into a
// style attribute.
require_once $GLOBALS['THEME_DIR'] . '/inc/customizer-gi-health.php';
check( 'focal: a good value passes',  vance_gi_sanitize_focal( '55% 45%' ), '55% 45%' );
check( 'focal: junk falls to centre', vance_gi_sanitize_focal( 'red; background:url(x)' ), '50% 50%' );
check( 'focal: a CSS keyword is not a percentage pair', vance_gi_sanitize_focal( 'center top' ), '50% 50%' );
check( 'focal: an empty value falls to centre', vance_gi_sanitize_focal( '' ), '50% 50%' );

/* ===================================================================== */
echo "\n=== 7. The card, and its opt-in date ===\n";
set_mods( array() );
$h = body( render_cond( 'ulcerative-colitis' ) );
check( 'card: renders on a condition page', strpos( $h, '__card-title' ) !== false );
check( 'card: makes NO date claim when unset', strpos( $h, 'Last reviewed' ) === false );

set_mods( array( 'vance_gi_reviewed' => 'August 2026' ) );
$h = body( render_cond( 'ulcerative-colitis' ) );
check( 'card: shows the date once set', strpos( $h, 'Last reviewed <b>August 2026</b>' ) !== false );
$hub = body( render_hub() );
check( 'card: the lobby carries the same date', strpos( $hub, 'Last reviewed <b>August 2026</b>' ) !== false );

// The card is identical across the set — that is a large part of what makes
// the eight read as one thing.
set_mods( array() );
$cards = array();
foreach ( array_merge( $SLUGS, array( '__hub' ) ) as $s ) {
    $html = $s === '__hub' ? body( render_hub() ) : body( render_cond( $s ) );
    preg_match( '#<aside class="vhh-hero-spotlight__card">.*?</aside>#s', $html, $m );
    $cards[] = isset( $m[0] ) ? $m[0] : "MISSING on $s";
}
check( 'card: byte-identical on all eight pages', count( array_unique( $cards ) ), 1 );
/* The check above cannot go red on its own: one function renders the card for
   all eight pages, so they are identical by construction. That is the right
   design and the wrong test -- it proves nothing a mutation could break. The
   copy itself is what can silently drift, so assert that too. */
check( 'card: the title is the agreed wording',
    strpos( $cards[0], '>Written plainly, checked by a clinician</h2>' ) !== false );
check( 'card: the body is the agreed wording',
    strpos( $cards[0], 'None of it replaces advice from your own care team.' ) !== false );

/* ===================================================================== */
echo "\n=== 8. Admin-saved copy still wins ===\n";
set_mods( array(
    'vance_gi_cond_uc_title' => 'A Renamed Condition',
    'vance_gi_cond_uc_lede'  => 'A rewritten lede.',
) );
$h = body( render_cond( 'ulcerative-colitis' ) );
check( 'uc: saved title wins',   strpos( $h, 'A Renamed Condition</h1>' ) !== false );
check( 'uc: saved lede wins',    strpos( $h, 'A rewritten lede.' ) !== false );
check( 'uc: default lede is gone', strpos( $h, 'inflames and ulcerates' ) === false );
// The breadcrumb's tail follows the saved title, not the registry label.
check( 'uc: breadcrumb follows the saved title',
    (bool) preg_match( '#__crumb.*?A Renamed Condition#s', $h ) );
// A sibling's band cell keeps the REGISTRY label, not the neighbour's rename —
// the cell is about the other page, and reading its title mod here would mean
// seven extra reads per render.
$h2 = body( render_cond( 'crohns-disease' ) );
check( 'crohns: band cell for UC uses the registry label',
    strpos( $h2, '__line-v">Ulcerative Colitis<' ) !== false );

set_mods( array(
    'vance_gi_hub_hero_heading'   => 'A New Lobby Heading',
    'vance_gi_hub_hero_btn1_text' => '',
) );
$hub = body( render_hub() );
check( 'hub: saved heading wins', strpos( $hub, 'A New Lobby Heading' ) !== false );
check( 'hub: an emptied button label hides the button',
    substr_count( $hub, '<a class="vhh-hero-spotlight__cta' ), 1 );  // only the ghost remains

/* ===================================================================== */
echo "\n=== 9. Slug and URL resolution ===\n";
set_mods( array() );

check( 'a stranger renders nothing', render_cond( 'about-us' ), '' );
check( 'a stranger returns false',   vance_render_gi_hero( 'about-us' ), false );
check( 'an empty slug returns false', vance_render_gi_hero( '' ), false );
check( 'has() knows the seven',      vance_gi_hero_has( 'crohns-disease' ) );
check( 'has() rejects a stranger',   vance_gi_hero_has( 'about-us' ), false );

// With no WP pages at all, every link falls back to the literal path rather
// than rendering href-less.
$PAGES_SAVED = $GLOBALS['PAGES'];
$GLOBALS['PAGES'] = array();
$h = body( render_cond( 'crohns-disease' ) );
check( 'fallback: band links still resolve', substr_count( $h, '__line" href="https://example.test/' ), 4 );
check( 'fallback: no empty hrefs anywhere',  strpos( $h, 'href=""' ), false );
$hub = body( render_hub() );
check( 'fallback: every chip still resolves', substr_count( $hub, '__chip" href="https://example.test/' ), 7 );
$GLOBALS['PAGES'] = $PAGES_SAVED;

// The lobby's button paths become absolute; a full URL an admin saved does not.
set_mods( array() );
$hub = body( render_hub() );
check( 'hub: btn1 path is made absolute', strpos( $hub, 'href="https://example.test/gastro-health-survey/"' ) !== false );
set_mods( array( 'vance_gi_hub_hero_btn1_url' => 'https://elsewhere.test/x/' ) );
$hub = body( render_hub() );
check( 'hub: an absolute saved URL is left alone', strpos( $hub, 'https://elsewhere.test/x/' ) !== false );
set_mods( array( 'vance_gi_hub_hero_btn1_url' => '#conditions' ) );
$hub = body( render_hub() );
check( 'hub: a fragment is left alone', strpos( $hub, 'href="#conditions"' ) !== false );

/* ===================================================================== */
echo "\n=== 10. The markup itself ===\n";
set_mods( array() );
foreach ( array_merge( $SLUGS, array( '__hub' ) ) as $s ) {
    $html = $s === '__hub' ? render_hub() : render_cond( $s );
    check( "$s: tags balanced", tags_balanced( body( $html ) ) );
    check( "$s: one h1 only", substr_count( $html, '<h1' ), 1 );
    check( "$s: no raw PHP left in the output", strpos( $html, '<?php' ), false );
    check( "$s: every icon is aria-hidden",
        substr_count( $html, '<svg' ), substr_count( $html, 'aria-hidden="true" focusable="false"' ) );
}
// The apostrophe in Crohn's must survive escaping as an entity, not as a
// mangled byte — the pages are served UTF-8 and the name is user-visible.
$h = render_cond( 'crohns-disease' );
check( 'crohns: apostrophe is intact', strpos( $h, "Crohn\xe2\x80\x99s" ) !== false );
check( 'no mojibake anywhere', (bool) preg_match( '#Ã|â€#', $h ), false );

/* ===================================================================== */
echo "\n=== 11. The templates actually CALL the renderer ===\n";
/*
 * Included and run, never grepped. legal-hero.test.php learned this the hard
 * way: the grep version of its equivalent section passed a mutation that
 * commented the call out, because `// vance_render_gi_hero( $slug );` still
 * contains the string being searched for.
 */
function probe_template( $which ) {
    // Stubs only these two templates need, declared here so the probe process
    // has them before it includes anything.
    if ( ! function_exists( 'get_header' ) ) {
        eval( '
        function get_header( $n = null ) {}
        function get_footer( $n = null ) {}
        function have_posts() { return false; }
        function the_post() {}
        function the_title() {}
        function the_content() {}
        function get_queried_object_id() { return 1; }
        function get_post_field( $f, $id = 0 ) { return $GLOBALS["PROBE_SLUG"]; }
        function is_page_template( $t ) { return false; }
        function wp_strip_all_tags( $t ) { return strip_tags( $t ); }
        function absint( $n ) { return abs( (int) $n ); }
        function esc_textarea( $t ) { return esc_html( $t ); }
        function sanitize_title( $t ) { return strtolower( preg_replace( "/[^a-z0-9]+/i", "-", $t ) ); }
        function wp_json_encode( $d ) { return json_encode( $d ); }
        function is_user_logged_in() { return false; }
        function get_search_query() { return ""; }
        function vance_sanitize_text_align( $v ) { return $v; }
        function wp_list_pluck( $list, $field ) {
            $out = array();
            foreach ( (array) $list as $k => $v ) { $out[ $k ] = is_array( $v ) ? $v[ $field ] : $v->$field; }
            return $out;
        }
        function get_the_ID() { return 1; }
        function has_post_thumbnail( $p = null ) { return false; }
        function get_the_post_thumbnail_url( $p = null, $s = "full" ) { return ""; }
        function wp_reset_postdata() {}
        function get_posts( $a = array() ) { return array(); }
        function get_bloginfo( $s = "" ) { return "Vance Medical Hub"; }
        function checked( $a, $b = true, $e = true ) { return ""; }
        function selected( $a, $b = true, $e = true ) { return ""; }
        function wp_nonce_field() {}
        function wp_create_nonce( $a = "" ) { return "nonce"; }
        function admin_url( $p = "" ) { return "https://example.test/wp-admin/" . $p; }
        function get_categories( $a = array() ) { return array(); }
        function get_category_link( $t ) { return "https://example.test/cat/"; }
        function paginate_links( $a = array() ) { return ""; }
        function esc_js( $t ) { return $t; }
        function vance_discovery_post_types() { return array( "post" ); }
        function is_customize_preview() { return false; }
        function get_query_var( $v, $d = "" ) { return $d; }
        function is_singular( $t = "" ) { return true; }
        function get_option( $k, $d = false ) { return $d; }
        class WP_Query {
            public $posts = array();
            public function __construct( $a = array() ) {}
            public function have_posts() { return false; }
            public function the_post() {}
        }
        ' );
    }
    ob_start();
    if ( $which === 'hub' ) {
        $GLOBALS['PROBE_SLUG'] = 'gastro-health-explained';
        include $GLOBALS['THEME_DIR'] . '/page-gi-health.php';
    } else {
        $GLOBALS['PROBE_SLUG'] = $which;
        include $GLOBALS['THEME_DIR'] . '/page-gi-condition.php';
    }
    return ob_get_clean();
}

$php = PHP_BINARY;
$self = __FILE__;
foreach ( array( 'hub', 'crohns-disease', 'colorectal-cancer' ) as $t ) {
    $out = shell_exec( escapeshellarg( $php ) . ' ' . escapeshellarg( $self ) . ' ' . escapeshellarg( "--probe=$t" ) . ' 2>&1' );
    $out = (string) $out;
    check( "$t: template emits the spotlight hero", strpos( $out, 'vhh-hero-spotlight--gi' ) !== false );
    check( "$t: template emits the hero stylesheet", strpos( $out, 'id="vhh-gi-hero-css"' ) !== false );
    check( "$t: template emits no PHP fatal", stripos( $out, 'Fatal error' ), false );
}
// And the dark bands they replaced are gone from the rendered page.
$out = (string) shell_exec( escapeshellarg( $php ) . ' ' . escapeshellarg( $self ) . ' ' . escapeshellarg( '--probe=hub' ) . ' 2>&1' );
check( 'hub: the old dark band is gone', strpos( $out, 'gi-hub-hero' ), false );
$out = (string) shell_exec( escapeshellarg( $php ) . ' ' . escapeshellarg( $self ) . ' ' . escapeshellarg( '--probe=crohns-disease' ) . ' 2>&1' );
check( 'crohns: the flat teal .gi-cp-hero is gone', strpos( $out, 'class="gi-cp-hero"' ), false );
check( 'crohns: the classic .gi-cond-hero did not fire', strpos( $out, 'gi-cond-hero' ), false );
// The foot-of-page block now agrees with the hero band.
check( 'crohns: explore block matches the hero band',
    substr_count( $out, 'href="https://example.test/ulcerative-colitis/"' ) >= 2 );

/* ===================================================================== */
echo "\n=== 12. The Customizer cannot disagree with the renderer ===\n";
/*
 * The failure this catches is silent in WordPress and loud here: a default
 * typed into the control and again into the renderer, which then drift. Only
 * a pristine site shows it, and by then it is live.
 */
class WP_Customize_Manager {
    public $settings = array();
    public $controls = array();
    public $sections = array();
    public function add_panel( $id, $a = array() ) {}
    public function add_section( $id, $a = array() ) { $this->sections[ $id ] = $a; }
    public function add_setting( $id, $a = array() ) { $this->settings[ $id ] = $a; }
    public function add_control( $id, $a = array() ) {
        if ( is_object( $id ) ) { $this->controls[ $id->id ] = $id->args; return; }
        $this->controls[ $id ] = $a;
    }
}
class WP_Customize_Control { public $id; public $args;
    public function __construct( $m, $id, $a = array() ) { $this->id = $id; $this->args = $a; } }
class WP_Customize_Color_Control extends WP_Customize_Control {}
class WP_Customize_Image_Control extends WP_Customize_Control {}
function add_action( $h, $f, $p = 10, $n = 1 ) {}

$wp = new WP_Customize_Manager();
vance_gi_customize_register( $wp );

$d = vance_gi_hero_hub_defaults();
$pairs = array(
    'vance_gi_hub_hero_eyebrow'   => 'eyebrow',
    'vance_gi_hub_hero_heading'   => 'heading',
    'vance_gi_hub_hero_lede'      => 'lede',
    'vance_gi_hub_hero_btn1_text' => 'btn1_text',
    'vance_gi_hub_hero_btn1_url'  => 'btn1_url',
    'vance_gi_hub_hero_btn2_text' => 'btn2_text',
    'vance_gi_hub_hero_btn2_url'  => 'btn2_url',
);
foreach ( $pairs as $setting => $field ) {
    check( "customizer: $setting default matches the renderer",
        isset( $wp->settings[ $setting ]['default'] ) ? $wp->settings[ $setting ]['default'] : '(missing)',
        $d[ $field ] );
}

// Every condition's focal default is the one the renderer will actually use.
foreach ( vance_gi_conditions() as $slug => $reg ) {
    $sid = "vance_gi_cond_{$reg['key']}_focal";
    check( "customizer: $sid default matches the registry",
        isset( $wp->settings[ $sid ]['default'] ) ? $wp->settings[ $sid ]['default'] : '(missing)',
        $meta[ $slug ]['focal'] );
    check( "customizer: $sid is sanitised as a focal pair",
        isset( $wp->settings[ $sid ]['sanitize_callback'] ) ? $wp->settings[ $sid ]['sanitize_callback'] : '(missing)',
        'vance_gi_sanitize_focal' );
}

/* The lobby's focal necessarily lives in two files -- a literal default in the
   renderer and one in the control -- so it is exactly the pair that drifts.
   Asserted against what the hero actually RENDERS, not against a third
   literal typed here, which would just be a fourth thing to keep in step. */
set_mods( array() );
preg_match( '#object-position: ([^;"]+)#', body( render_hub() ), $fm );
check( 'customizer: the lobby focal default matches what the hero renders',
    isset( $wp->settings['vance_gi_hub_hero_focal']['default'] ) ? $wp->settings['vance_gi_hub_hero_focal']['default'] : '(missing)',
    isset( $fm[1] ) ? trim( $fm[1] ) : '(not rendered)' );

check( 'customizer: the shared review-date setting exists',
    isset( $wp->settings['vance_gi_reviewed'] ) );
check( 'customizer: the review date defaults to EMPTY, so nothing is claimed',
    $wp->settings['vance_gi_reviewed']['default'], '' );
check( 'customizer: the retired overlay control is still registered',
    isset( $wp->settings['vance_gi_hub_hero_bg_overlay'] ) );
check( 'customizer: the retired controls say so in their label',
    strpos( $wp->controls['vance_gi_hub_hero_bg_overlay']['label'], 'no longer used' ) !== false );

/* ===================================================================== */
printf( "\n%d passed, %d failed\n", $PASS, $FAIL );
exit( $FAIL > 0 ? 1 : 0 );
