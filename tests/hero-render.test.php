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
// The shelf's slug is free-health-tools. 'tools-resources' is NOT here on
// purpose: that path 404s on the live site, and while this table carried it
// the suite proved a link that could not work.
$GLOBALS['PAGES'] = array(
    'ask-ai' => 1, 'knowledgebase' => 1,
    'gastro-health-survey' => 1, 'gastro-meal-planner' => 1,
    'malnutrition-calculator' => 1, 'free-health-tools' => 1,
    'contact-us' => 1,
);

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

// The three free-tool pages. Their copy comes from three DIFFERENT key
// families -- vance_hquiz_hero_*, vance_tool_recipes_*, vance_tool_malnutrition_*
// -- so each one is its own chance to have passed '' as the default.
set_mods( array( "vance_hquiz_hero_style" => "spotlight" ) );
$p3 = render( "hquiz" );
check( "survey: eyebrow is not empty",  strpos( $p3, "Self-Assessment" ) !== false );
check( "survey: headline is not empty", strpos( $p3, "Gastro Health Survey" ) !== false );
check( "survey: intro is not empty",    strpos( $p3, "evidence-based questionnaire" ) !== false );

set_mods( array( "vance_recipes_hero_style" => "spotlight" ) );
$p4 = render( "recipes" );
check( "planner: eyebrow is not empty",  strpos( $p4, "Meal Planning" ) !== false );
check( "planner: headline is not empty", strpos( $p4, "Gastro Recipes" ) !== false );
check( "planner: intro is not empty",    strpos( $p4, "EPA-rich" ) !== false );

set_mods( array( "vance_malnutrition_hero_style" => "spotlight" ) );
$p5 = render( "malnutrition" );
check( "calculator: eyebrow is not empty",  strpos( $p5, "IBD Screening" ) !== false );
check( "calculator: headline is not empty", strpos( $p5, "IBD Malnutrition Calculator" ) !== false );
check( "calculator: intro is not empty",    strpos( $p5, "MUST, IBD-NST" ) !== false );

// And every band is filled on a pristine site too -- it reads the OTHER tools'
// name and badge settings, which are just as unsaved.
check( "survey band names the planner",     strpos( $p3, "Meal Planner" ) !== false );
check( "survey band names the calculator",  strpos( $p3, "IBD Malnutrition Calculator" ) !== false );
check( "planner band names the survey",     strpos( $p4, "Gastro Health Survey" ) !== false );
check( "calculator band names the survey",  strpos( $p5, "Gastro Health Survey" ) !== false );

// Ask AI, Get Started Today and the User Guide. Ask AI's defaults are the
// third set on this page to disagree with what functions.php REGISTERS
// ('Beta Feature v1.0', 'Ask complex clinical questions...') -- the template's
// are what an unsaved front end actually renders, and these must be those.
set_mods( array( "vance_askai_hero_style" => "spotlight" ) );
$p6 = render( "askai" );
check( "askai: eyebrow is not empty",  strpos( $p6, "Information Assistant" ) !== false );
check( "askai: headline is not empty", strpos( $p6, "VANCE-Ai" ) !== false );
check( "askai: intro is not empty",    strpos( $p6, "drawn from articles published" ) !== false );

set_mods( array( "vance_evidence_hero_style" => "spotlight" ) );
$p7 = render( "evidence" );
check( "evidence: eyebrow is not empty",  strpos( $p7, "Evidence to Practice" ) !== false );
check( "evidence: headline is not empty", strpos( $p7, "into Action" ) !== false );
check( "evidence: intro is not empty",    strpos( $p7, "Rigorous clinical research" ) !== false );
check( "evidence: band has pillar 1",     strpos( $p7, "Clinical Trials" ) !== false );
check( "evidence: band has pillar 4",     strpos( $p7, "Expert Consensus" ) !== false );
check( "evidence: button 1 keeps the classic label",
       strpos( $p7, "Explore the Evidence Library" ) !== false );

set_mods( array( "vance_userguide_hero_style" => "spotlight" ) );
$p8 = render( "userguide" );
check( "userguide: eyebrow is not empty",  strpos( $p8, "User Guide" ) !== false );
check( "userguide: headline is not empty", strpos( $p8, "Get the most out of" ) !== false );
check( "userguide: intro is not empty",    strpos( $p8, "credible source you turn to" ) !== false );

// Education. Its description is the one on this site whose only other copy is
// the Customizer registration rather than the classic template -- the classic
// hero renders no description at all -- so a pristine render is the only place
// a lost default would be visible.
set_mods( array( "vance_education_hero_style" => "spotlight" ) );
$p12 = render( "education" );
check( "education: eyebrow is not empty",  strpos( $p12, "Elevate Your Expertise" ) !== false );
check( "education: headline is not empty", strpos( $p12, "Education &amp; Courses" ) !== false );
check( "education: intro is not empty",    strpos( $p12, "CPD-accredited modules for practitioners" ) !== false );

// The two shelves. Free Health Tools reads a FOURTH key family
// (vance_tools_hero_*) and is the first page to inherit button 2 as well,
// so its pristine render is the only place a '' default there would show.
set_mods( array( "vance_tools_hero_style" => "spotlight" ) );
$p9 = render( "tools" );
check( "free tools: eyebrow is not empty",  strpos( $p9, "Free Tools" ) !== false );
check( "free tools: headline is not empty", strpos( $p9, "Resources" ) !== false );
check( "free tools: intro is not empty",    strpos( $p9, "peer-reviewed evidence" ) !== false );
check( "free tools: button 2 is not empty", strpos( $p9, "Create Free Account" ) !== false );
check( "free tools: button 2 has a link",   strpos( $p9, "/login/?tab=signup" ) !== false );

set_mods( array( "vance_kblobby_hero_style" => "spotlight" ) );
$p10 = render( "kblobby" );
check( "knowledgebase: eyebrow is not empty",  strpos( $p10, "Knowledgebase" ) !== false );
check( "knowledgebase: headline is not empty", strpos( $p10, "evidence library" ) !== false );
check( "knowledgebase: intro is not empty",    strpos( $p10, "every collection in the Vance Medical Hub" ) !== false );
check( "knowledgebase: the search field has a placeholder",
       strpos( $p10, "Search the whole knowledgebase" ) !== false );

// The 404 has no toggle and no saved copy at all -- literally nothing set.
set_mods( array() );
$p11 = render( "e404" );
check( "404: eyebrow is not empty",  strpos( $p11, "404 error" ) !== false );
check( "404: headline is not empty", strpos( $p11, "find that page" ) !== false );
check( "404: intro is not empty",    strpos( $p11, "may have changed" ) !== false );

// Nothing may render as an empty element on a pristine site.
foreach ( array( "contact" => $p1, "about" => $p2,
                 "hquiz" => $p3, "recipes" => $p4, "malnutrition" => $p5,
                 "askai" => $p6, "evidence" => $p7, "userguide" => $p8,
                 "education" => $p12,
                 "tools" => $p9, "kblobby" => $p10, "e404" => $p11 ) as $pg => $html ) {
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
// Which file holds each page's classic hero is declared in the config, so
// this loop covers any page added there without being edited again.
foreach ( vance_page_hero_spotlight_pages() as $pg ) {
    $conf = vance_page_hero_spotlight_config( $pg );
    // A page with no legacy_tag has no classic hero, so there is no template
    // holding a second copy of its words and nothing here to hold together.
    // Its copy is asserted in section 0 instead, and section 5e proves the
    // config -- not a theme mod -- is where it comes from.
    if ( empty( $conf["legacy_tag"] ) ) {
        check( "$pg: declares no classic_template, and needs none",
               isset( $conf["classic_template"] ), false );
        continue;
    }
    $file = $THEME . "/" . $conf["classic_template"];
    if ( ! file_exists( $file ) ) {
        check( "$pg: classic_template " . $conf["classic_template"] . " exists", false );
        continue;
    }
    $src = $unescape( file_get_contents( $file ) );
    foreach ( array( "legacy_tag_default", "legacy_title_default", "legacy_desc_default" ) as $field ) {
        // Almost always the classic template: it passes the fallback to
        // get_theme_mod(), so its literal is what an unsaved site renders and
        // is the second copy this check exists to pin down.
        //
        // Education is the exception, and declares which file holds its
        // description instead. Its classic hero renders no description at all
        // -- vance_edu_hero_desc has been registered, defaulted and doing
        // nothing in customizer-pages.php since the page was built -- so the
        // registration is the only other copy of that string, and the template
        // is the wrong place to look. An unconditional search of both files
        // would have been one line, but it would also let a genuinely mislaid
        // default pass on any of the other eleven pages.
        $where = ( $field === "legacy_desc_default" && ! empty( $conf["legacy_desc_file"] ) )
            ? $conf["legacy_desc_file"]
            : $conf["classic_template"];
        $hay = ( $where === $conf["classic_template"] )
            ? $src
            : $unescape( (string) @file_get_contents( $THEME . "/" . $where ) );
        check( "$pg/$field appears verbatim in " . $where,
               strpos( $hay, $conf[ $field ] ) !== false );
    }
}

// ...and legacy_desc_file must never point at a file that is not there, or the
// check above degrades into searching an empty string and passing nothing.
foreach ( vance_page_hero_spotlight_pages() as $pg ) {
    $conf = vance_page_hero_spotlight_config( $pg );
    if ( empty( $conf["legacy_desc_file"] ) ) { continue; }
    check( "$pg: legacy_desc_file " . $conf["legacy_desc_file"] . " exists",
           file_exists( $THEME . "/" . $conf["legacy_desc_file"] ) );
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
// The stat card's icon is config too, and it is the FIRST of the two call
// sites -- so without this, hard-wiring both back to one icon goes unnoticed.
check( 'card icon is the flask, not the speech bubble',
       strpos( $a, 'M9.5 3v6.2' ) !== false );
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

echo "\n=== 5b. The three tool pages carry each other, never themselves ===\n";
set_mods( array(
    'vance_malnutrition_hero_style' => 'spotlight',
    'vance_hquiz_hero_title'        => 'Gastro Health Survey',
    'vance_tool_recipes_name'       => 'Gastro Recipes & Meal Planner',
    'vance_tool_malnutrition_name'  => 'IBD Malnutrition Calculator',
) );
$m = render( 'malnutrition' );
check( 'markup is balanced', tags_balanced( $m ) );
check( 'band is the lines markup',        strpos( $m, 'vhh-hero-spotlight__slot--lines' ) !== false );
check( 'and carries the tools modifier',  strpos( $m, 'vhh-hero-spotlight__slot--tools' ) !== false );
check( 'lists the survey',                strpos( $m, 'Gastro Health Survey' ) !== false );
check( 'lists the meal planner',          strpos( $m, 'Meal Planner' ) !== false );
check( 'does NOT list itself in the band',
       strpos( $m, 'href="https://example.test/malnutrition-calculator/"' ), false );
check( 'links resolve to real permalinks',
       strpos( $m, 'href="https://example.test/gastro-meal-planner/"' ) !== false );
check( 'and ends with the whole shelf',
       strpos( $m, 'href="https://example.test/free-health-tools/"' ) !== false );
check( 'primary CTA targets the tool card', strpos( $m, 'href="#tool"' ) !== false );
check( 'ghost CTA resolves to Ask AI',      strpos( $m, 'https://example.test/ask-ai/' ) !== false );
check( 'card is the text variant',          strpos( $m, 'card-title' ) !== false );

// Each page drops itself, so each band is a different pair.
set_mods( array( 'vance_recipes_hero_style' => 'spotlight' ) );
$r = render( 'recipes' );
check( 'the planner does not list itself',
       strpos( $r, 'href="https://example.test/gastro-meal-planner/"' ), false );
check( 'the planner lists the calculator', strpos( $r, 'IBD Malnutrition Calculator' ) !== false );
check( 'planner CTAs are its own anchors',
       strpos( $r, 'href="#recipes"' ) !== false && strpos( $r, 'href="#planner"' ) !== false );

// Renaming a tool in the Customizer renames it in the other two heroes.
set_mods( array( 'vance_recipes_hero_style' => 'spotlight',
                 'vance_tool_malnutrition_name' => 'Nutrition Risk Check' ) );
$r2 = render( 'recipes' );
check( 'a renamed tool is renamed in the band', strpos( $r2, 'Nutrition Risk Check' ) !== false );
check( 'and the old name is gone',              strpos( $r2, 'IBD Malnutrition Calculator' ) === false );

// A cleared name drops its cell rather than rendering an empty one.
set_mods( array( 'vance_recipes_hero_style' => 'spotlight',
                 'vance_tool_malnutrition_name' => '' ) );
$r3 = render( 'recipes' );
check( 'a cleared tool is dropped from the band',
       strpos( $r3, 'href="https://example.test/malnutrition-calculator/"' ) === false );
check( 'the surviving cells still render',
       strpos( $r3, 'Gastro Health Survey' ) !== false );
check( 'the shelf cell is always there',
       strpos( $r3, 'href="https://example.test/free-health-tools/"' ) !== false );

// The card icon is per-page, not one hard-wired speech bubble.
foreach ( array( 'hquiz' => 'M8.8 11.6h6.4', 'recipes' => 'M3.4 11.4h17.2',
                 'malnutrition' => 'x="4.6" y="2.6"' ) as $pg => $needle ) {
    set_mods( array( 'vance_' . $pg . '_hero_style' => 'spotlight' ) );
    check( "$pg: card shows its own icon", strpos( render( $pg ), $needle ) !== false );
}

echo "\n=== 5c. Ask AI, Get Started Today and the User Guide ===\n";

// The band is three cells WHATEVER page it is on. A tool page always drops one
// tool (its own) so the shelf cell lands; Ask AI and the User Guide are not
// tools, so all three are listed and the shelf would make a fourth.
set_mods( array( 'vance_askai_hero_style' => 'spotlight' ) );
$ai = render( 'askai' );
check( 'markup is balanced', tags_balanced( $ai ) );
check( 'askai lists all three tools',
       strpos( $ai, 'Gastro Health Survey' ) !== false
    && strpos( $ai, 'Meal Planner' ) !== false
    && strpos( $ai, 'IBD Malnutrition Calculator' ) !== false );
check( 'askai has NO "browse all" cell (that would be a fourth)',
       strpos( $ai, 'href="https://example.test/free-health-tools/"' ), false );
check( 'every band is exactly three cells',
       substr_count( $ai, 'vhh-hero-spotlight__line-ico' ), 3 );
set_mods( array( 'vance_malnutrition_hero_style' => 'spotlight' ) );
check( 'including on a tool page, where one is the shelf',
       substr_count( render( 'malnutrition' ), 'vhh-hero-spotlight__line-ico' ), 3 );
// ...and it stays three when an admin clears a tool, because the shelf appears.
set_mods( array( 'vance_askai_hero_style' => 'spotlight', 'vance_tool_recipes_name' => '' ) );
$ai2 = render( 'askai' );
check( 'a cleared tool brings the shelf cell back',
       strpos( $ai2, 'href="https://example.test/free-health-tools/"' ) !== false );
check( 'and the band is still three cells',
       substr_count( $ai2, 'vhh-hero-spotlight__line-ico' ), 3 );

set_mods( array( 'vance_askai_hero_style' => 'spotlight' ) );
check( 'askai ghost CTA resolves to the Knowledgebase',
       strpos( render( 'askai' ), 'https://example.test/knowledgebase/' ) !== false );

// Get Started Today: the four pillars, in the badges markup.
set_mods( array( 'vance_evidence_hero_style' => 'spotlight',
                 'vance_evidence_pillar2_title' => 'Registry Outcomes' ) );
$ev = render( 'evidence' );
check( 'markup is balanced', tags_balanced( $ev ) );
check( 'band uses the badges markup',   strpos( $ev, 'vhh-hero-spotlight__slot--badges' ) !== false );
check( 'and carries the pillars modifier', strpos( $ev, 'vhh-hero-spotlight__slot--pillars' ) !== false );
check( 'a renamed pillar renames in the band', strpos( $ev, 'Registry Outcomes' ) !== false );
check( 'and the old name is gone',             strpos( $ev, 'Real-World Data' ) === false );
check( 'four cells, not three',                substr_count( $ev, 'vhh-hero-spotlight__badge-ico' ), 4 );
check( 'the join CTA keeps its pinned link',   strpos( $ev, 'href="/login/?tab=signup"' ) !== false );
check( 'pillars anchor is the ghost CTA',      strpos( $ev, 'href="#pillars"' ) !== false );

// Button 1's LABEL is inherited from the classic hero on this page only, so a
// Customizer relabel follows the design switch instead of reverting.
set_mods( array( 'vance_evidence_hero_style' => 'spotlight',
                 'vance_evidence_hero_btn1_text' => 'Join Now!' ) );
check( 'a relabelled join button follows the switch',
       strpos( render( 'evidence' ), 'Join Now!' ) !== false );
$evd = vance_page_hero_spotlight_field_defaults( 'evidence' );
check( 'evidence declares NO btn1_text of its own', array_key_exists( 'btn1_text', $evd ), false );
$cond = vance_page_hero_spotlight_field_defaults( 'contact' );
check( 'every other page still does',              array_key_exists( 'btn1_text', $cond ) );

// The User Guide keeps the PDF, as a real download.
set_mods( array( 'vance_userguide_hero_style' => 'spotlight' ) );
$ug = render( 'userguide' );
check( 'markup is balanced', tags_balanced( $ug ) );
check( 'the PDF is button 2',        strpos( $ug, 'Vance-Health-Hub-User-Guide.pdf' ) !== false );
check( 'and is a real download',     strpos( $ug, '.pdf" download>' ) !== false );
check( 'no other button downloads',  substr_count( $ug, ' download>' ), 1 );
set_mods( array( 'vance_contact_hero_style' => 'spotlight' ) );
check( 'Contact\'s buttons carry no download attribute',
       strpos( render( 'contact' ), ' download>' ), false );

// The PDF filename is duplicated: page-user-guide.php defines it as a constant
// this file cannot see during Customizer registration, so page-hero-spotlight
// carries a literal fallback. Hold the two together.
$ug_src = file_get_contents( $THEME . "/page-user-guide.php" );
$ug_pdf = basename( vance_page_hero_spotlight_config( 'userguide' )['btn2_link'] );
check( "the PDF fallback ($ug_pdf) matches VUG_PDF_FILE in the template",
       preg_match( "/define\(\s*'VUG_PDF_FILE',\s*'" . preg_quote( $ug_pdf, '/' ) . "'/", $ug_src ), 1 );

echo "\n=== 5d. The two shelves ===\n";

// Free Health Tools IS the shelf, so its band lists all three tools and no
// 'browse all' cell -- selling the page back to itself. That falls out of
// vance_page_hero_spotlight_tools() rather than being special-cased, so this
// is where the falling-out is checked.
set_mods( array( 'vance_tools_hero_style' => 'spotlight' ) );
$tp = render( 'tools' );
check( 'markup is balanced', tags_balanced( $tp ) );
check( 'the shelf lists all three tools',
       strpos( $tp, 'Gastro Health Survey' ) !== false
    && strpos( $tp, 'Meal Planner' ) !== false
    && strpos( $tp, 'IBD Malnutrition Calculator' ) !== false );
check( 'and does NOT link to itself',
       strpos( $tp, 'href="https://example.test/free-health-tools/"' ), false );
check( 'still exactly three cells',
       substr_count( $tp, 'vhh-hero-spotlight__line-ico' ), 3 );
check( 'primary CTA targets the card grid', strpos( $tp, 'href="#tools-grid"' ) !== false );

// Button 2's LABEL and LINK are both inherited here, which no other page
// does. An admin has relabelled this CTA 'Join Now!' and repointed it on the
// live site, so a spotlight button carrying the code default would rename
// and re-aim the page's only call to action the day the design switched.
set_mods( array( 'vance_tools_hero_style' => 'spotlight',
                 'vance_tools_hero_btn2_text' => 'Join Now!',
                 'vance_tools_hero_btn2_link' => '/register/' ) );
$tp2 = render( 'tools' );
check( 'a relabelled account button follows the switch', strpos( $tp2, 'Join Now!' ) !== false );
check( 'and so does its link',                           strpos( $tp2, 'href="/register/"' ) !== false );
check( 'the code default is gone',   strpos( $tp2, 'Create Free Account' ) === false );
$td = vance_page_hero_spotlight_field_defaults( 'tools' );
check( 'free tools declares NO btn2_text of its own', array_key_exists( 'btn2_text', $td ), false );
check( 'nor a btn2_link',                             array_key_exists( 'btn2_link', $td ), false );
$cd2 = vance_page_hero_spotlight_field_defaults( 'contact' );
check( 'every other page still declares both',
       array_key_exists( 'btn2_text', $cd2 ) && array_key_exists( 'btn2_link', $cd2 ) );

// The Knowledgebase lobby: a search field where every other page has cells,
// and a motif where every other page has a photograph.
set_mods( array( 'vance_kblobby_hero_style' => 'spotlight' ) );
$kb = render( 'kblobby' );
check( 'markup is balanced', tags_balanced( $kb ) );
check( 'the band is a real search form',
       strpos( $kb, 'class="vhh-hero-spotlight__search-form"' ) !== false );
check( 'the field submits to the site search',
       strpos( $kb, 'name="s"' ) !== false && strpos( $kb, 'action="https://example.test/"' ) !== false );
check( 'the prompt is a real <label for>, not a bare span',
       strpos( $kb, '<label class="vhh-hero-spotlight__slot-label" for="vhh-page-hero-search">' ) !== false );
check( 'and it points at the field that exists',
       strpos( $kb, 'id="vhh-page-hero-search"' ) !== false );
check( 'no cell markup leaks in',   strpos( $kb, '__slot--lines' ) === false );
check( 'a motif stands in for the photograph',
       strpos( $kb, 'vhh-hero-spotlight__motif' ) !== false );
check( 'and there is no <img> at all', strpos( $kb, '<img' ) === false );

// ...but the motif is a DEFAULT, not a ceiling. Uploading a photograph in
// the Customizer has to take over, or the control is a lie.
set_mods( array( 'vance_kblobby_hero_style' => 'spotlight',
                 'vance_kblobby_hero_spot_image' => 'https://example.test/x.jpg' ) );
$kb2 = render( 'kblobby' );
check( 'an uploaded photograph replaces the motif',
       strpos( $kb2, 'src="https://example.test/x.jpg"' ) !== false );
check( 'and the motif is gone', strpos( $kb2, '__motif' ) === false );

echo "\n=== 5e. The 404 ===\n";

// It is the one page here with no second design, so it must be on with
// nothing saved -- and must STAY on when somebody saves 'classic' into the
// key every other page reads.
set_mods( array() );
check( '404 is on with nothing saved', vance_page_hero_spotlight_active( 'e404' ) );
set_mods( array( 'vance_e404_hero_style' => 'classic' ) );
check( 'and cannot be switched off by a stray theme mod',
       vance_page_hero_spotlight_active( 'e404' ) );
check( 'while every other page still respects its toggle',
       vance_page_hero_spotlight_active( 'contact' ), false );

// Its copy comes from the config, not from a theme mod -- so writing the
// key another page would read must NOT move it.
set_mods( array( 'vance_e404_hero_tag' => 'HIJACKED', 'vance_e404_hero_title' => 'HIJACKED' ) );
$er = render( 'e404' );
check( 'copy is not read from theme mods', strpos( $er, 'HIJACKED' ) === false );
check( 'the config copy is what renders',  strpos( $er, '404 error' ) !== false );

set_mods( array() );
$e4 = render( 'e404' );
check( 'markup is balanced', tags_balanced( $e4 ) );
check( 'a motif stands in for the photograph',
       strpos( $e4, 'vhh-hero-spotlight__motif' ) !== false );
check( 'the band is the start variant',
       strpos( $e4, 'vhh-hero-spotlight__slot--start' ) !== false );
check( 'four suggested destinations, not three',
       substr_count( $e4, 'vhh-hero-spotlight__line-ico' ), 4 );
check( 'every one of them is a link',
       substr_count( $e4, '<a class="vhh-hero-spotlight__line"' ), 4 );

// The start page. Button 1 resolves BY SLUG: a 404 whose recovery link is a
// hard-coded path is one rename away from sending a lost visitor to a
// second 404.
check( 'the start page is the Knowledgebase',
       strpos( $e4, 'href="https://example.test/knowledgebase/"' ) !== false );
check( 'the homepage is the second button, not the first',
       strpos( $e4, 'href="/"' ) !== false );
check( 'the Knowledgebase is not ALSO a band cell',
       substr_count( $e4, 'https://example.test/knowledgebase/' ), 1 );
foreach ( array( 'free-health-tools', 'ask-ai', 'gastro-health-survey', 'contact-us' ) as $slug ) {
    check( "the band offers /$slug/",
           strpos( $e4, 'href="https://example.test/' . $slug . '/"' ) !== false );
}

// ...and when a page has been renamed away, the cell falls back to a path
// rather than rendering href-less.
$saved_pages = $GLOBALS['PAGES'];
unset( $GLOBALS['PAGES']['ask-ai'] );
$e5 = render( 'e404' );
check( 'a missing page still yields a link',
       strpos( $e5, 'href="https://example.test/ask-ai/"' ) !== false );
$GLOBALS['PAGES'] = $saved_pages;

echo "\n=== 5g. Education & Courses ===\n";

set_mods( array( 'vance_education_hero_style' => 'spotlight' ) );
$ed = render( 'education' );
check( 'markup is balanced', tags_balanced( $ed ) );

// This hero shipped with the motif on 2026-09-01 and was given a photograph of
// its own later the same day. Both assertions are kept the right way round
// rather than deleted: the failure that matters here is the page silently
// falling BACK to the motif, which is what happens if the file goes missing --
// vance_gi_hero_photo()'s sibling logic returns the motif branch and nothing
// raises an error. Section 5f proves the file is on disk and 1400x876.
check( 'a photograph is rendered, not the motif',
       strpos( $ed, 'vhh-hero-spotlight__media' ) !== false );
check( 'and the motif is gone',
       strpos( $ed, 'vhh-hero-spotlight__motif' ), false );
check( 'it is the photograph made for this page',
       strpos( $ed, '/assets/img/heroes/education.jpg' ) !== false );
check( 'and it carries real alt text',
       (bool) preg_match( '#/assets/img/heroes/education\.jpg"\s+alt="[^"]{20,}"#', $ed ) );

check( 'the band is the learn variant',
       strpos( $ed, 'vhh-hero-spotlight__slot--learn' ) !== false );
check( 'three destinations',
       substr_count( $ed, 'vhh-hero-spotlight__line-ico' ), 3 );
check( 'every one of them is a link',
       substr_count( $ed, '<a class="vhh-hero-spotlight__line"' ), 3 );
foreach ( array( 'knowledgebase', 'ask-ai', 'free-health-tools' ) as $slug ) {
    check( "the band offers /$slug/",
           strpos( $ed, 'href="https://example.test/' . $slug . '/"' ) !== false );
}

// Both buttons are in-page anchors, and both targets are rendered by
// page-education.php unconditionally -- a CTA pointing at an id that is not
// on the page scrolls nowhere and reports nothing.
check( 'button 1 scrolls to the waitlist', strpos( $ed, 'href="#waitlist"' ) !== false );
check( 'button 2 scrolls to the tracks',   strpos( $ed, 'href="#tracks"' ) !== false );
$edu_tpl = file_get_contents( $THEME . '/page-education.php' );
check( 'and the template really renders #waitlist', strpos( $edu_tpl, 'id="waitlist"' ) !== false );
check( 'and the template really renders #tracks',   strpos( $edu_tpl, 'id="tracks"' ) !== false );

// The template must actually CALL the renderer, and behind the toggle. A
// commented-out call would leave every check above passing against a hero
// that never reaches a visitor -- and a plain substring search does NOT
// notice that, because `// vance_render_page_hero_spotlight( 'education' );`
// still contains the string. So strip comments first. (gi-hero and legal-hero
// include and run their templates instead, which is stronger; this one carries
// a `get_header()` and a page-wide waitlist script that would need stubbing
// for a call site of three lines. The mutation runner has a commented-out
// mutant for exactly this and it must read `went RED`.)
$edu_live = preg_replace( '#/\*.*?\*/#s', '', $edu_tpl );           // block comments
$edu_live = preg_replace( '#(^|[^:])//.*$#m', '$1', $edu_live );    // line comments, sparing https://
check( 'page-education.php calls the spotlight renderer',
       strpos( $edu_live, "vance_render_page_hero_spotlight( 'education' )" ) !== false );
check( '...gated on the toggle',
       strpos( $edu_live, "vance_page_hero_spotlight_active( 'education' )" ) !== false );
check( '...and the classic hero is still there to fall back to',
       strpos( $edu_tpl, 'class="hero edu-hero"' ) !== false );

// The description is the point of the exercise: vance_edu_hero_desc has been
// registered and rendered nowhere since the page was built. If this stops
// being read, the control goes back to doing nothing and nothing else notices.
set_mods( array( 'vance_education_hero_style' => 'spotlight',
                 'vance_edu_hero_desc' => 'EDITED IN THE CUSTOMIZER' ) );
check( 'the intro reads vance_edu_hero_desc',
       strpos( render( 'education' ), 'EDITED IN THE CUSTOMIZER' ) !== false );

echo "\n=== 5h. The motif hint the phone stylesheet keys off ===\n";

// This section exists because of a live bug. The phone rule that stops a motif
// hero jamming its headline under the site header used to name --kblobby and
// --e404 one at a time; Education shipped as a third motif page and rendered
// flush, because nothing anywhere connected "declares a motif" to "is in that
// selector list". The renderer now says so itself, and this holds the two ends
// together.
$motif_pages = array();
foreach ( vance_page_hero_spotlight_pages() as $pg ) {
    $conf = vance_page_hero_spotlight_config( $pg );
    set_mods( array( 'vance_' . $pg . '_hero_style' => 'spotlight',
                     'vance_contact_email' => 'a@b.co', 'vance_about_badge1_label' => 'One' ) );
    $html    = render( $pg );
    $drawn   = strpos( $html, 'vhh-hero-spotlight__motif' ) !== false;
    $flagged = strpos( $html, 'vhh-hero-spotlight--has-motif' ) !== false;
    check( "$pg: the --has-motif flag agrees with what was drawn", $flagged, $drawn );
    if ( $drawn ) { $motif_pages[] = $pg; }
}
// ...and the loop must have SEEN a motif page and a photograph page, or the
// assertion above is satisfied by everything being false.
check( 'at least two pages draw the motif', count( $motif_pages ) >= 2 );
check( 'and most pages do not', count( $motif_pages ) < count( vance_page_hero_spotlight_pages() ) );

// An uploaded photograph must clear the flag too -- that is the case the old
// page list got wrong even for the pages it named.
set_mods( array( 'vance_kblobby_hero_style' => 'spotlight',
                 'vance_kblobby_hero_spot_image' => 'https://example.test/x.jpg' ) );
$kb_photo = render( 'kblobby' );
check( 'an uploaded photograph clears the motif flag',
       strpos( $kb_photo, 'vhh-hero-spotlight--has-motif' ), false );

// The phone rule must key off the flag, not off a list of page modifiers.
// Line endings differ between what this repo writes (LF) and what a checkout
// produces (CRLF), so normalise before matching rather than anchoring on \n.
$css_now = str_replace( "\r\n", "\n", file_get_contents( $THEME . '/assets/css/main.css' ) );
$mstart  = strpos( $css_now, '--- The motif pages on a phone' );
// To the end of the enclosing media query: the first closing brace sitting in
// column 0 after the block starts.
$mend    = ( $mstart !== false ) ? strpos( $css_now, "\n}\n", $mstart ) : false;
$motif_block = ( $mstart !== false && $mend !== false )
    ? substr( $css_now, $mstart, $mend - $mstart )
    : '';
check( 'the phone block exists', $motif_block !== '' );
check( 'and keys off --has-motif',
       strpos( $motif_block, '.vhh-hero-spotlight--has-motif' ) !== false );
check( 'and names no page modifier of its own',
       preg_match( '/\.vhh-hero-spotlight--(?!has-motif|page)[a-z0-9]+/', $motif_block ), 0 );

echo "\n=== 5f. Every photograph the config names is really on disk ===\n";

// A filename typo renders a broken <img> on a live hero and nothing else
// in this suite would notice: the renderer happily prints any string, and
// section 8 only checks CSS classes. The stub's get_template_directory_uri()
// is a known prefix, so the URL maps straight back to a path.
$uri_prefix = get_template_directory_uri();
$seen_image = 0;
foreach ( vance_page_hero_spotlight_pages() as $pg ) {
    $conf = vance_page_hero_spotlight_config( $pg );
    // The motif pages name no photograph in code, on purpose.
    if ( $conf['image'] === '' ) {
        check( "$pg: names no photograph, and declares the motif instead",
               ! empty( $conf['motif'] ) );
        continue;
    }
    check( "$pg: photograph is a theme URL",
           strpos( $conf['image'], $uri_prefix . '/' ) === 0 );
    $rel  = substr( $conf['image'], strlen( $uri_prefix ) );
    check( "$pg: " . basename( $rel ) . " exists in the theme",
           file_exists( $THEME . $rel ) );
    $seen_image++;
}
// ...and the loop must actually have looked at something. Without this a
// config where every page had lost its image would pass in silence.
check( 'the loop checked at least eight photographs', $seen_image >= 8 );

// Everything in assets/img/heroes/ was made for this hero and must be 1400x876,
// which is what the renderer declares as the <img> width/height. A file of
// another shape makes that attribute a lie the browser corrects after layout --
// the exact shift it exists to stop. (The older borrowed photographs elsewhere
// in assets/img/ are all different shapes and are NOT held to this; the point of
// the heroes/ directory is that everything in it was cut to the box.)
$made_for_this = array();
foreach ( vance_page_hero_spotlight_pages() as $pg ) {
    $conf = vance_page_hero_spotlight_config( $pg );
    if ( strpos( $conf['image'], '/assets/img/heroes/' ) !== false ) {
        $made_for_this[ $pg ] = substr( $conf['image'], strlen( $uri_prefix ) );
    }
}
foreach ( $made_for_this as $pg => $rel ) {
    $size = @getimagesize( $THEME . $rel );
    check( "$pg: " . basename( $rel ) . " is 1400x876, as the <img> attributes claim",
           $size ? array( $size[0], $size[1] ) : false, array( 1400, 876 ) );
}
// An exact count, not >=: this is the assertion that fails if a page quietly
// goes back to borrowing an image bought for another page. Ten of the twelve --
// the Knowledgebase and the 404 name none in code, they take the motif and are
// overridden by a theme mod on the live site. Education was a third of those
// until 2026-09-01, when it was given a photograph of its own.
check( 'ten heroes carry a photograph made for them', count( $made_for_this ), 10 );

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
foreach ( vance_page_hero_spotlight_pages() as $p ) {
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
foreach ( vance_page_hero_spotlight_pages() as $p ) {
    set_mods( array( "vance_" . $p . "_hero_style" => "spotlight",
                     "vance_contact_email" => "a@b.co", "vance_contact_phone" => "+44 1", "vance_contact_hours" => "9-5",
                     "vance_about_badge1_label" => "One" ) );
    preg_match_all( '/class="([^"]*vhh-hero-spotlight[^"]*)"/', render( $p ), $m );
    foreach ( $m[1] as $attr ) { foreach ( explode( " ", $attr ) as $cls ) {
        if ( strpos( $cls, "vhh-hero-spotlight" ) === 0 ) { $emitted[ $cls ] = true; }
    } }
}
$styled = array( "vhh-hero-spotlight--page",
                 "vhh-hero-spotlight__slot--lines", "vhh-hero-spotlight__slot--badges",
                 "vhh-hero-spotlight__line-body", "vhh-hero-spotlight__slot-wrap",
                 // structural hooks the HOMEPAGE hero already emits unstyled;
                 // this hero mirrors its markup, so they are inherited, not new
                 "vhh-hero-spotlight__copy", "vhh-hero-spotlight__card-body" );
// The per-page modifier is a hook for future page-specific tweaks; none of
// the five needs one yet, so none has a rule.
foreach ( vance_page_hero_spotlight_pages() as $p ) { $styled[] = "vhh-hero-spotlight--" . $p; }

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
