<?php
/**
 * Renders the five policy-document heroes outside WordPress and asserts on the
 * real output.
 *
 * Same shape as hero-render.test.php: WP stubs, a controllable bag of theme
 * mods, and assertions against the emitted HTML. Kept in its own file because
 * inc/legal-hero.php is a separate renderer with no toggle, no Customizer
 * registration and no shared config with inc/page-hero-spotlight.php — so it
 * shares none of that suite's fixtures either.
 *
 * Every check here must be able to go red. `python mutate-legal.py` breaks the
 * source on purpose and confirms it does.
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
function get_template_directory() { return $GLOBALS['THEME_DIR']; }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/vance-health-hub'; }
function home_url( $p = '/' ) { return 'https://example.test' . $p; }
function get_page_by_path( $slug ) { return isset( $GLOBALS['PAGES'][ $slug ] ) ? $slug : null; }
function get_permalink( $p ) { return 'https://example.test/' . $p . '/'; }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_url( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html_e( $t, $d = '' ) { echo esc_html( $t ); }
function esc_html__( $t, $d = '' ) { return esc_html( $t ); }
function __( $t, $d = '' ) { return $t; }

$GLOBALS['THEME_DIR'] = $THEME;
// Every document has a real WP page, so links resolve by slug rather than by
// the literal-path fallback. Section 5 removes them to exercise the fallback.
$GLOBALS['PAGES'] = array(
    'privacy-policy' => 1, 'cookie-policy-uk' => 1, 'terms-of-use' => 1,
    'medical-disclaimer' => 1, 'accessibility' => 1,
);

require_once $THEME . '/inc/legal-hero.php';

/*
 * --probe=<template> renders ONE template and exits.
 *
 * Section 9 needs each template rendered as the FIRST thing a request does,
 * because vance_legal_hero_styles() prints its block once per request behind a
 * static -- which is right for WordPress, where only one policy page renders,
 * and wrong for a suite that has already rendered eleven heroes by then. So
 * section 9 shells back into this file once per template.
 *
 * That is not a workaround: a fresh process is a truer model of the real
 * request than an in-process render, and the cascade order this section exists
 * to protect is a property of the whole emitted document.
 *
 * Top-level function declarations are hoisted, so render_template() and the WP
 * stubs in section 8 are callable from up here.
 */
if ( isset( $argv[1] ) && strpos( $argv[1], '--probe=' ) === 0 ) {
    echo render_template( substr( $argv[1], strlen( '--probe=' ) ) );
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
function render( $doc, $overrides = array() ) {
    // The stylesheet is printed once per request behind a static, so a suite
    // that renders eleven heroes would only see it in the first. Each render
    // therefore runs in its own process-visible reset: the static cannot be
    // reached from here, so section 7 asserts on the FIRST render instead and
    // every other section strips the block before matching.
    ob_start();
    vance_render_legal_hero( $doc, $overrides );
    return ob_get_clean();
}
function body( $html ) {
    // Everything outside the <style> block, so a class name that happens to
    // appear in a CSS selector cannot be mistaken for emitted markup.
    return preg_replace( '#<style\b.*?</style>#s', '', $html );
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

$DOCS = array_keys( vance_legal_hero_docs() );

/* ===================================================================== */
echo "\n=== 0. A PRISTINE site: nothing saved at all ===\n";
/*
 * The bug this exists for is the one that shipped the About hero empty:
 * get_theme_mod() answers an unsaved read with the default the CALLER passes,
 * so a '' default renders an empty hero on the live site while looking
 * perfectly correct in the Customizer preview. This hero reads exactly one
 * theme mod -- the contact email -- but the same rule holds for it.
 */
set_mods( array() );

$first = render( 'privacy' );
check( 'privacy: headline renders',   strpos( $first, 'Privacy Policy' ) !== false );
check( 'privacy: eyebrow renders',    strpos( $first, '__eyebrow">Privacy<' ) !== false );
check( 'privacy: intro renders',      strpos( $first, 'protecting your personal data' ) !== false );
check( 'privacy: card email is the theme default, not empty',
    strpos( $first, 'mailto:team@vancemedicalfoods.co.uk' ) !== false );
check( 'privacy: no empty mailto: anywhere', strpos( $first, 'mailto:"' ) === false );

foreach ( $DOCS as $doc ) {
    $h = body( render( $doc ) );
    $d = vance_legal_hero_docs()[ $doc ];
    check( "$doc: headline is not empty", strpos( $h, '__title">' . esc_html( $d['title'] ) . '<' ) !== false );
    check( "$doc: intro is not empty",    trim( $d['intro'] ) !== '' && strpos( $h, esc_html( $d['intro'] ) ) !== false );
    check( "$doc: eyebrow is not empty",  strpos( $h, '__eyebrow">' . esc_html( $d['eyebrow'] ) . '<' ) !== false );
}

/* ===================================================================== */
echo "\n=== 1. The copy is the copy the dark heroes carried ===\n";
/*
 * Switching hero design must not silently reword a legal document. Each
 * headline below is asserted against the literal the template rendered before
 * this hero existed.
 *
 * The intros that were deliberately CHANGED are listed as changes, with the
 * old wording asserted GONE. That direction matters more than it looks: an
 * intro that merely "contains the new text" would also pass if somebody
 * restored the old sentence alongside it, and three of these four rewrites
 * were requested precisely because the old sentence was wrong or too long.
 */
$carried_titles = array(
    'privacy'       => 'Privacy Policy',
    'terms'         => 'Terms of Use',
    'disclaimer'    => 'Medical Disclaimer',
    'accessibility' => 'Accessibility Statement',
    'cookies'       => 'Cookie Policy (UK)',
);
foreach ( $carried_titles as $doc => $title ) {
    check( "$doc: headline is unchanged from the dark hero",
        vance_legal_hero_docs()[ $doc ]['title'], $title );
}

check( 'disclaimer: intro is carried verbatim',
    vance_legal_hero_docs()['disclaimer']['intro'],
    'Please read this before using Vance Medical Hub, its articles, tools or VANCE-Ai.' );

// The three deliberate changes.
check( 'terms: the pre-rebrand "Gastro Health Hub" is gone',
    stripos( vance_legal_hero_docs()['terms']['intro'], 'Gastro Health Hub' ) === false );
check( 'terms: and the intro names Vance Medical Hub instead',
    strpos( vance_legal_hero_docs()['terms']['intro'], 'Vance Medical Hub' ) !== false );
// Privacy and Accessibility, reworded by the client on 2026-08-28.
check( 'privacy: intro is the client-supplied wording',
    vance_legal_hero_docs()['privacy']['intro'],
    'We are committed to protecting your personal data and privacy. This policy explains how we collect, use, and protect your data.' );
check( 'privacy: the superseded wording is gone',
    strpos( vance_legal_hero_docs()['privacy']['intro'], 'safeguard your information' ), false );
check( 'accessibility: intro is the client-supplied wording',
    vance_legal_hero_docs()['accessibility']['intro'],
    'This statement sets out the standards we hold, and how to tell us when something does not work for you.' );
check( 'accessibility: the superseded opening sentence is gone',
    strpos( vance_legal_hero_docs()['accessibility']['intro'], 'We want everyone to be able to use' ), false );
// It was four rendered lines at the old length; the cap keeps it from creeping
// back there without anybody noticing.
check( 'accessibility: intro is short enough to stay under three lines',
    strlen( vance_legal_hero_docs()['accessibility']['intro'] ) < 130 );
check( 'cookies: has an intro of its own (the generic hero had none)',
    strlen( vance_legal_hero_docs()['cookies']['intro'] ) > 40 );

/* ===================================================================== */
echo "\n=== 2. The band: always the other four, never itself ===\n";
foreach ( $DOCS as $doc ) {
    $cells = vance_legal_hero_siblings( $doc );
    check( "$doc: band carries exactly four cells", count( $cells ), 4 );

    $self = vance_legal_hero_docs()[ $doc ];
    $vals = array_column( $cells, 'value' );
    check( "$doc: is not sold back to itself", in_array( $self['short'], $vals, true ), false );

    $hrefs = array_column( $cells, 'href' );
    check( "$doc: every cell has an href", count( array_filter( $hrefs ) ), 4 );
    check( "$doc: its own URL is not in the band",
        in_array( 'https://example.test/' . $self['slug'] . '/', $hrefs, true ), false );
}

// Four cells is what the 2x2 CSS depends on -- an odd count would put a
// hairline down the right of the last row.
check( 'the registry holds five documents, which is what makes the band four',
    count( vance_legal_hero_docs() ), 5 );

/* ===================================================================== */
echo "\n=== 3. The markup the stylesheet is written against ===\n";
$h = body( render( 'terms' ) );

check( 'section carries the shared spotlight class',
    strpos( $h, 'class="vhh-hero-spotlight vhh-hero-spotlight--page vhh-hero-spotlight--legal' ) !== false );
check( 'and the per-document modifier',
    strpos( $h, 'vhh-hero-spotlight--terms"' ) !== false );
check( 'the band carries the lines markup class',
    strpos( $h, 'vhh-hero-spotlight__slot--lines' ) !== false );
check( 'and the docs modifier the 2x2 rule targets',
    strpos( $h, 'vhh-hero-spotlight__slot--docs' ) !== false );
check( 'the band does NOT borrow the free-tools modifier',
    strpos( $h, 'vhh-hero-spotlight__slot--tools' ) === false );
check( 'a motif is emitted where the photograph would be',
    strpos( $h, 'vhh-hero-spotlight__motif' ) !== false );
check( 'and NO photograph is', strpos( $h, 'vhh-hero-spotlight__media' ) === false );
check( 'no <img> at all — the motif is inline SVG', strpos( $h, '<img' ) === false );
check( 'the card renders', strpos( $h, 'vhh-hero-spotlight__card"' ) !== false );
check( 'tags balance', tags_balanced( $h ) );

foreach ( $DOCS as $doc ) {
    check( "$doc: tags balance", tags_balanced( body( render( $doc ) ) ) );
}

/* ===================================================================== */
echo "\n=== 4. No humans, and no photography ===\n";
/*
 * The one hard constraint the client set. Asserted structurally rather than by
 * eye: the renderer must emit no <img>, no background-image, and nothing from
 * the theme's photograph directories.
 */
foreach ( $DOCS as $doc ) {
    $h = render( $doc );
    check( "$doc: emits no <img>",            strpos( $h, '<img' ) === false );
    check( "$doc: emits no background-image", stripos( $h, 'background-image' ) === false );
    check( "$doc: references no photo asset", stripos( $h, '/assets/img/' ) === false );
}

/* ===================================================================== */
echo "\n=== 5. Links resolve by slug, and fall back to a path ===\n";
$h = body( render( 'privacy' ) );
check( 'a sibling link resolves through the WP page',
    strpos( $h, 'https://example.test/terms-of-use/' ) !== false );

// No WP pages at all: every cell must still carry a working href.
$GLOBALS['PAGES'] = array();
$h = body( render( 'privacy' ) );
check( 'with no WP pages, the literal path is used',
    strpos( $h, 'https://example.test/terms-of-use/' ) !== false );
check( 'and no cell loses its href', substr_count( $h, '__line" href="' ), 4 );
$GLOBALS['PAGES'] = array(
    'privacy-policy' => 1, 'cookie-policy-uk' => 1, 'terms-of-use' => 1,
    'medical-disclaimer' => 1, 'accessibility' => 1,
);

/* ===================================================================== */
echo "\n=== 6. Overrides, the slug lookup, and unknown input ===\n";
$h = body( render( 'cookies', array( 'title' => 'Cookie Policy (United Kingdom)' ) ) );
check( 'an override title wins over the registry literal',
    strpos( $h, 'Cookie Policy (United Kingdom)' ) !== false );
check( 'and the literal is not ALSO rendered',
    strpos( $h, '__title">Cookie Policy (UK)<' ) === false );

$h = body( render( 'cookies', array( 'title' => '' ) ) );
check( 'an EMPTY override falls back to the literal, not to a blank headline',
    strpos( $h, '__title">Cookie Policy (UK)<' ) !== false );

foreach ( vance_legal_hero_docs() as $key => $d ) {
    check( "slug lookup: {$d['slug']} => $key", vance_legal_hero_doc_for_slug( $d['slug'] ), $key );
}
check( 'slug lookup: an unrelated page is not a document',
    vance_legal_hero_doc_for_slug( 'about' ), '' );
check( 'slug lookup: an empty slug is not a document',
    vance_legal_hero_doc_for_slug( '' ), '' );

ob_start(); vance_render_legal_hero( 'nope' ); $none = ob_get_clean();
check( 'an unknown document renders nothing', trim( $none ), '' );

/* ===================================================================== */
echo "\n=== 7. The stylesheet ships, once, and backs what is emitted ===\n";
/*
 * $first is the FIRST render this process performed, so it is the one that
 * carries the <style> block; every later render is expected not to repeat it.
 */
check( 'the first render carries the stylesheet',
    strpos( $first, 'id="vhh-legal-hero-css"' ) !== false );
check( 'a later render does not repeat it',
    strpos( render( 'terms' ), 'id="vhh-legal-hero-css"' ) === false );

preg_match( '#<style id="vhh-legal-hero-css">(.*?)</style>#s', $first, $m );
$css = isset( $m[1] ) ? $m[1] : '';
check( 'the stylesheet is not empty', strlen( $css ) > 400 );
check( 'braces balance', substr_count( $css, '{' ), substr_count( $css, '}' ) );

check( 'the motif has a rule',   strpos( $css, '.vhh-hero-spotlight__motif' ) !== false );
check( 'the band has a 2x2 rule', strpos( $css, 'repeat(2, minmax(0, 1fr))' ) !== false );
check( 'the 2x2 is scoped above the stacking breakpoint, or it never stacks',
    preg_match( '#@media \(min-width: 901px\)\s*\{[^@]*?slot--docs#s', $css ) === 1 );
check( 'the mobile rule restores the top padding main.css zeroes for the photo',
    preg_match( '#@media \(max-width: 900px\)\s*\{\s*/\*.*?\*/\s*\.vhh-hero-spotlight--legal \{\s*padding: \d+px#s', $css ) === 1 );
check( 'the card link is coloured',
    strpos( $css, '.vhh-hero-spotlight__card-text a' ) !== false );

/*
 * The headline cap. main.css's 520px let "Accessibility Statement" fit at
 * 1280px and wrap on anything wider -- so this is asserted as a NUMBER large
 * enough for the longest title at the type scale's 56px ceiling (579px
 * measured), not merely as "a rule exists".
 */
preg_match( '#\.vhh-hero-spotlight--legal \.vhh-hero-spotlight__title \{\s*max-width: (\d+)px#', $css, $mw );
check( 'the headline has a cap of its own', isset( $mw[1] ) );
check( 'and it clears the longest title at the 56px ceiling',
    isset( $mw[1] ) && (int) $mw[1] >= 600 );
check( 'without reaching past the copy column',
    isset( $mw[1] ) && (int) $mw[1] <= 690 );

/*
 * Every class the renderer emits either has a rule in the block above, or one
 * in main.css, or is a named structural hook. Anything that is none of those
 * is a class doing nothing -- usually a typo, which is invisible in a browser
 * because the element simply inherits.
 *
 * The hook list is the same one hero-render.test.php keeps, because this hero
 * mirrors that hero's markup: they are the wrappers the shared stylesheet
 * addresses through their parents rather than by name.
 */
$MAIN = file_get_contents( $THEME . '/assets/css/main.css' );
$hooks = array(
    'container',
    'vhh-hero-spotlight--page',
    'vhh-hero-spotlight__copy',
    'vhh-hero-spotlight__slot-wrap',
    'vhh-hero-spotlight__slot--lines',
    'vhh-hero-spotlight__card-body',
);
// The per-document modifier is a hook for document-specific tweaks; none of
// the five needs one yet, which is the point of them all looking alike.
foreach ( $DOCS as $doc ) { $hooks[] = 'vhh-hero-spotlight--' . $doc; }

$emitted = array();
foreach ( $DOCS as $doc ) {
    preg_match_all( '#class="([^"]*)"#', body( render( $doc ) ), $cm );
    foreach ( $cm[1] as $attr ) {
        foreach ( preg_split( '/\s+/', trim( $attr ) ) as $cls ) {
            if ( $cls !== '' ) { $emitted[ $cls ] = true; }
        }
    }
}
$orphans = array();
foreach ( array_keys( $emitted ) as $cls ) {
    if ( in_array( $cls, $hooks, true ) ) { continue; }
    if ( strpos( $css, '.' . $cls ) === false && strpos( $MAIN, '.' . $cls ) === false ) {
        $orphans[] = $cls;
    }
}
check( 'every emitted class has a rule (bar the named hooks): ' . implode( ',', $orphans ), $orphans, array() );

/* ===================================================================== */
echo "\n=== 8. The templates really render it ===\n";
/*
 * A renderer nothing calls is a renderer that ships dark, and grepping a
 * template for the call cannot tell the difference between a live statement
 * and a commented-out one -- a mutation that commented the call out passed a
 * grep-based version of this section. So the templates are INCLUDED here,
 * against enough of a WordPress to run, and the assertions are on what they
 * actually emit.
 */
function render_template( $file, $slug = 'some-page', $title = 'Some Page' ) {
    $GLOBALS['POST']  = array( 'slug' => $slug, 'title' => $title );
    $GLOBALS['LOOP']  = 1;
    ob_start();
    include $GLOBALS['THEME_DIR'] . '/' . $file;
    return ob_get_clean();
}
function get_header( $n = null ) { echo "<!--header-->\n"; }
function get_footer( $n = null ) { echo "<!--footer-->\n"; }
function have_posts() { return $GLOBALS['LOOP']-- > 0; }
function the_post() {}
function get_the_ID() { return 1; }
function the_ID() { echo 1; }
function get_post_field( $field, $id = 0 ) { return $GLOBALS['POST']['slug']; }
function get_the_title( $id = 0 ) { return $GLOBALS['POST']['title']; }
function the_title() { echo esc_html( $GLOBALS['POST']['title'] ); }
function has_post_thumbnail() { return false; }
function get_the_post_thumbnail_url( $id = 0, $s = '' ) { return ''; }
function post_class() { echo 'class="page"'; }
function the_content() { echo "<!--content-->\n"; }

$templates = array(
    'page-accessibility.php'      => 'accessibility',
    'page-medical-disclaimer.php' => 'disclaimer',
    'page-terms-of-use.php'       => 'terms',
    'tpl-privacy-policy.php'      => 'privacy',
);
foreach ( $templates as $file => $doc ) {
    $out = render_template( $file );
    check( "$file: renders the spotlight hero for '$doc'",
        strpos( $out, 'vhh-hero-spotlight--' . $doc . '"' ) !== false );
    check( "$file: renders its own headline",
        strpos( $out, '__title">' . esc_html( vance_legal_hero_docs()[ $doc ]['title'] ) . '<' ) !== false );
    check( "$file: emits no dark legal-hero band",
        strpos( $out, 'class="legal-hero"' ) === false );
    check( "$file: emits no .legal-hero CSS rule either",
        strpos( $out, '.legal-hero {' ) === false );
    check( "$file: still emits the body wrapper the page content depends on",
        strpos( $out, 'class="legal-wrap"' ) !== false );
    check( "$file: and .legal-wrap keeps its styling",
        strpos( $out, '.legal-wrap' ) !== false );
}

// page.php: the Cookie Policy takes the spotlight hero, everything else on the
// generic template keeps the dark one.
$out = render_template( 'page.php', 'cookie-policy-uk', 'Cookie Policy (UK)' );
check( 'page.php: the Cookie Policy gets the spotlight hero',
    strpos( $out, 'vhh-hero-spotlight--cookies"' ) !== false );
check( 'page.php: and does NOT also get the generic dark hero',
    strpos( $out, '<section class="hero"' ) === false );
check( 'page.php: it still renders the page body',
    strpos( $out, '<!--content-->' ) !== false );

$out = render_template( 'page.php', 'cookie-policy-uk', 'Cookie Policy (United Kingdom)' );
check( 'page.php: the LIVE title wins over the registry literal',
    strpos( $out, 'Cookie Policy (United Kingdom)' ) !== false );

$out = render_template( 'page.php', 'some-other-page', 'Some Other Page' );
check( 'page.php: an ordinary page keeps the generic dark hero',
    strpos( $out, '<section class="hero"' ) !== false );
check( 'page.php: and gets no spotlight hero',
    strpos( $out, 'vhh-hero-spotlight' ) === false );
check( 'page.php: and still renders its title',
    strpos( $out, 'Some Other Page' ) !== false );

/* ===================================================================== */
echo "\n=== 9. The document body: one 760px measure, in one place ===\n";
/*
 * The Cookie Policy used to run at page.php's 1200px container width while its
 * four siblings ran at 760px, because .legal-wrap was declared inline in each
 * of the four bespoke templates and nowhere else. The nine rules that were
 * byte-identical in all four now live in the renderer's stylesheet.
 */
check( 'the stylesheet defines the 760px measure',
    preg_match( '#\.legal-wrap \{[^}]*max-width: 760px#s', $css ) === 1 );
foreach ( array( '.legal-wrap h2', '.legal-wrap p', '.legal-wrap ul',
                 '.legal-wrap a', '.legal-updated' ) as $sel ) {
    check( "the stylesheet defines $sel",
        preg_match( '#' . preg_quote( $sel, '#' ) . '\s*\{#', $css ) === 1 );
}

// The Cookie Policy's tables. Google Site Kit's 70-character cookie names
// forced Complianz's first grid track to 565px and pushed the grid 41px past a
// 760px measure. `anywhere` and not `break-word`: only `anywhere` reduces the
// intrinsic min-content width the track is sized from.
check( 'the Complianz cookie grid is allowed to break long names',
    preg_match( '#\.cookies-per-purpose > \*\s*\{[^}]*overflow-wrap: anywhere#s', $css ) === 1 );

/*
 * Complianz sets its type scale on an ID, so `.legal-wrap p` loses to it on
 * specificity and the Cookie Policy was set smaller than its four siblings.
 * The override has to STAY id-qualified to keep winning, and its size has to
 * stay in step with `.legal-wrap p` -- a size changed in one place and not the
 * other is invisible until the five documents are put side by side.
 */
preg_match( '#\.legal-wrap p \{[^}]*font-size: ([\d.]+)px#s', $css, $shared_p );
preg_match(
    '#(\.legal-wrap \#cmplz-document [a-z,\s\#a-z-]*?)\{[^}]*font-size: ([\d.]+)px#s',
    $css, $cmplz_p
);
check( 'the Complianz type scale is overridden at all', isset( $cmplz_p[2] ) );

/*
 * The subheading. Complianz sets `#cmplz-document h2, #cmplz-document h3` to
 * one size in a single rule, so the Cookie Policy's h3s rendered at exactly
 * their h2 size. The shared rule carries the id in a second selector so it
 * outranks that at (1,1,1) -- lose the id and the plugin wins again, and the
 * page silently goes back to having no heading hierarchy.
 */
preg_match( '#(\.legal-wrap h3,\s*\.legal-wrap \#cmplz-document h3)\s*\{([^}]*)\}#s', $css, $h3 );
check( 'the subheading rule covers both the plain and the Complianz case',
    isset( $h3[1] ) );
check( 'and it does so in ONE rule, so the two cannot drift',
    isset( $h3[2] ) && substr_count( $h3[2], 'font-size' ) === 1 );
preg_match( '#font-size: ([\d.]+)px#', isset( $h3[2] ) ? $h3[2] : '', $h3size );
preg_match( '#\.legal-wrap h2 \{[^}]*font-size: ([\d.]+)px#s', $css, $h2size );
check( 'the subheading is smaller than the heading above it',
    isset( $h3size[1], $h2size[1] ) && (float) $h3size[1] < (float) $h2size[1] );
// Complianz's own h4 is 15px and h5 14px, so an h3 at or below 15px would
// flatten the bottom of the ladder instead of the top.
check( 'and still larger than the plugin h4 beneath it',
    isset( $h3size[1] ) && (float) $h3size[1] > 15.0 );
check( 'the override is id-qualified, or it cannot outrank the plugin',
    isset( $cmplz_p[1] ) && strpos( $cmplz_p[1], '#cmplz-document' ) !== false );
check( 'and its body size is in step with .legal-wrap p',
    isset( $shared_p[1], $cmplz_p[2] ) ? $cmplz_p[2] : 'missing',
    isset( $shared_p[1] ) ? $shared_p[1] : 'missing' );

/*
 * ORDER. `.legal-contact-box p`, `.legal-emergency-box p` and
 * `.legal-disclaimer-box p` all collide with `.legal-wrap p` at EQUAL
 * specificity (0,1,1), so source order alone decides the winner, and the box
 * rules won before the consolidation. That is why the four templates call
 * vance_legal_hero_styles() above their own <style> rather than letting the
 * hero render print it further down.
 */
foreach ( $templates as $file => $doc ) {
    // A fresh process, so the once-per-request stylesheet guard is unused --
    // see the --probe note at the top of this file.
    $out = shell_exec(
        escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ )
        . ' ' . escapeshellarg( '--probe=' . $file )
    );
    $shared = strpos( $out, '.legal-wrap p {' );
    check( "$file: the shared measure is printed", $shared !== false );

    /*
     * Every `.legal-<box> <tag>` in a template collides with the shared
     * `.legal-wrap <tag>` for the same tag at equal specificity, so ALL of them
     * have to stay after it -- not just the `p` ones. `.legal-contact-box h3`
     * and `.legal-toc h3` joined the list when h3 moved into the shared block.
     */
    preg_match_all( '#\.legal-([a-z-]+) ([a-z][a-z0-9]*) \{#', $out, $bm, PREG_OFFSET_CAPTURE | PREG_SET_ORDER );
    $shared_at = array();
    $boxes     = array();
    foreach ( $bm as $hit ) {
        $tag = $hit[2][0];
        if ( $hit[1][0] === 'wrap' ) {
            // First occurrence only: the shared block is printed first.
            if ( ! isset( $shared_at[ $tag ] ) ) { $shared_at[ $tag ] = $hit[0][1]; }
        } else {
            $boxes[] = array( 'sel' => $hit[0][0], 'tag' => $tag, 'at' => $hit[0][1] );
        }
    }
    check( "$file: it has box rules that collide with the shared ones",
        count( $boxes ) > 0 );

    $early = array();
    foreach ( $boxes as $b ) {
        // Only a box rule for a tag the shared block also styles can collide.
        if ( isset( $shared_at[ $b['tag'] ] ) && $b['at'] < $shared_at[ $b['tag'] ] ) {
            $early[] = $b['sel'];
        }
    }
    check( "$file: every colliding box rule still comes AFTER its shared rule: " . implode( ',', $early ),
        $early, array() );

    // And the template must not declare the shared rules a second time, or the
    // consolidation has bought nothing.
    check( "$file: declares the shared measure exactly once",
        substr_count( $out, '.legal-wrap p {' ), 1 );
    check( "$file: declares the shared subheading exactly once",
        substr_count( $out, '.legal-wrap h3,' ) + substr_count( $out, '.legal-wrap h3 {' ), 1 );
}

// page.php's two branches.
$out = render_template( 'page.php', 'cookie-policy-uk', 'Cookie Policy (UK)' );
check( 'page.php: a policy document is set in .legal-wrap',
    strpos( $out, '<div class="legal-wrap">' ) !== false );
check( 'page.php: and NOT in the generic full-width container',
    strpos( $out, '<div class="container" style="padding: 60px 20px;">' ) === false );
check( 'page.php: no inline line-height fighting .legal-wrap p',
    strpos( $out, '<div class="entry-content" style="line-height: 1.8;">' ) === false );

$out = render_template( 'page.php', 'some-other-page', 'Some Other Page' );
check( 'page.php: an ordinary page keeps the generic container',
    strpos( $out, '<div class="container" style="padding: 60px 20px;">' ) !== false );
check( 'page.php: and is not narrowed to the policy measure',
    strpos( $out, '<div class="legal-wrap">' ) === false );

/* ===================================================================== */
echo "\n----------------------------------------------------------\n";
echo "  PASSED $PASS   FAILED $FAIL\n\n";
exit( $FAIL > 0 ? 1 : 0 );
