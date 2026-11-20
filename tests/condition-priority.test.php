<?php
/**
 * Condition priority: Crohn's disease, ulcerative colitis and diverticular
 * disease come first on every list of conditions the site shows.
 *
 * Covers inc/condition-priority.php (the helper itself) and every place that
 * uses it:
 *   - the registries: vance_gi_conditions(), vance_gi_condition_cards()
 *     (functions.php), vance_gi_hero_meta() and vance_gi_hero_related_slugs()
 *     (inc/gi-hero.php), vance_community_spaces() (inc/hub-community.php),
 *     vance_ai_gi_condition_slugs() (inc/askai-content-sources.php);
 *   - the home page tiles: vance_hub_split_conditions() (inc/hub-layout.php)
 *     fed the way front-page-hub.php feeds it;
 *   - the lobby's two chip rows (inc/gi-hero.php);
 *   - the customizer map in inc/customizer-structure.php, whose vance_gi_cond_*
 *     sections must follow vance_gi_conditions() order;
 *   - the template call sites in front-page-hub.php and
 *     page-discovery-results.php.
 *
 * functions.php cannot be included, so the registries are lifted out of their
 * source files with the same brace-matching eval that tests/gi-hero.test.php
 * uses. A failed lift is a hard error, not a skip: a suite that silently stops
 * testing the real registry is worse than no suite.
 *
 * Every check here must be able to go red. Confirmed by hand-sabotaging
 * inc/condition-priority.php and inc/customizer-structure.php and watching this
 * suite fail, then restoring the exact original bytes.
 */

define( 'ABSPATH', true );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';
$GLOBALS['THEME_DIR'] = $THEME;

/* ---- WordPress stubs ----------------------------------------------------- */
function __( $text, $domain = '' ) { return $text; }
function get_template_directory() { return $GLOBALS['THEME_DIR']; }
function add_action( $hook, $cb, $priority = 10, $args = 1 ) { }

/* ---- lift one top-level function's source out of a file ------------------ */
/*
 * Strict on purpose: anything unexpected is a fatal, because the failure this
 * guards against is the suite quietly testing a stub.
 */
function lift_function( $file, $name ) {
    $src = file_get_contents( $file );
    if ( $src === false ) { fwrite( STDERR, "FATAL: cannot read $file\n" ); exit( 2 ); }

    $sig = "\nfunction $name(";
    $at  = strpos( $src, $sig );
    if ( $at === false ) {
        fwrite( STDERR, "FATAL: $name() not found in $file - the extraction has drifted.\n" );
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

lift_function( $THEME . '/functions.php', 'vance_gi_conditions' );
lift_function( $THEME . '/functions.php', 'vance_gi_condition_cards' );
lift_function( $THEME . '/inc/gi-hero.php', 'vance_gi_hero_meta' );
lift_function( $THEME . '/inc/gi-hero.php', 'vance_gi_hero_slug_meta' );
lift_function( $THEME . '/inc/gi-hero.php', 'vance_gi_hero_related_slugs' );
lift_function( $THEME . '/inc/hub-community.php', 'vance_community_spaces' );
lift_function( $THEME . '/inc/askai-content-sources.php', 'vance_ai_gi_condition_slugs' );
lift_function( $THEME . '/inc/hub-layout.php', 'vance_hub_split_conditions' );
if ( ! function_exists( 'wp_parse_url' ) ) {
    function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
}
lift_function( $THEME . '/inc/nav-mega.php', 'vance_nav_tiles_in_priority' );

require_once $THEME . '/inc/condition-priority.php';
require_once $THEME . '/inc/customizer-structure.php';

/* ---- the runner ---------------------------------------------------------- */
$PASS = 0; $FAIL = 0;
function check( $name, $got, $want ) {
    global $PASS, $FAIL;
    if ( $got === $want ) { $PASS++; echo "  ok   $name\n"; return; }
    $FAIL++;
    echo "  FAIL $name\n";
    echo "       expected: " . var_export( $want, true ) . "\n";
    echo "       got     : " . var_export( $got, true ) . "\n";
}
function section( $title ) { echo "\n=== $title ===\n"; }

/*
 * The seven GI conditions in the order they must come out: the three priority
 * conditions first, then the other four in the order they had before this
 * change (ibd, microscopic colitis, IBS, colorectal cancer).
 */
$ALL_GI = array(
    'crohns-disease',
    'ulcerative-colitis',
    'diverticular-disease',
    'inflammatory-bowel-disease',
    'microscopic-colitis',
    'irritable-bowel-syndrome',
    'colorectal-cancer',
);

/* The same seven, sorted, for the set comparison. */
$ALL_GI_SORTED = array(
    'colorectal-cancer',
    'crohns-disease',
    'diverticular-disease',
    'inflammatory-bowel-disease',
    'irritable-bowel-syndrome',
    'microscopic-colitis',
    'ulcerative-colitis',
);

/* ===================================================================== */
section( '0. The priority list itself' );
/* ===================================================================== */

check( 'priority list is crohns, ulcerative colitis, diverticular disease, in that order',
    vance_condition_priority(),
    array( 'crohns-disease', 'ulcerative-colitis', 'diverticular-disease' ) );

/* ===================================================================== */
section( '1. vance_condition_slug_of()' );
/* ===================================================================== */

check( 'slug_of: a row with a slug field gives the slug field, not the key',
    vance_condition_slug_of( 'irritable-bowel-syndrome', array( 'slug' => 'crohns-disease' ) ),
    'crohns-disease' );
check( 'slug_of: a string key gives the key (map keyed by slug)',
    vance_condition_slug_of( 'ulcerative-colitis', array( 'label' => 'UC' ) ),
    'ulcerative-colitis' );
check( 'slug_of: an int key with a string value gives the value (plain list)',
    vance_condition_slug_of( 0, 'ulcerative-colitis' ),
    'ulcerative-colitis' );
check( 'slug_of: an int key with a null value names no condition',
    vance_condition_slug_of( 3, null ),
    '' );
check( 'slug_of: a non-string slug field is not a slug',
    vance_condition_slug_of( 0, array( 'slug' => 5 ) ),
    '' );

/* ===================================================================== */
section( '2. vance_sort_conditions(): the normal case and the shapes' );
/* ===================================================================== */

check( 'list of strings: priority slugs first, in priority order, rest in order',
    vance_sort_conditions( array(
        'inflammatory-bowel-disease',
        'ulcerative-colitis',
        'irritable-bowel-syndrome',
        'diverticular-disease',
        'crohns-disease',
    ) ),
    array(
        'crohns-disease',
        'ulcerative-colitis',
        'diverticular-disease',
        'inflammatory-bowel-disease',
        'irritable-bowel-syndrome',
    ) );

check( 'map keyed by slug: priority keys first, keys and values kept',
    vance_sort_conditions( array(
        'inflammatory-bowel-disease' => 'IBD',
        'ulcerative-colitis'         => 'UC',
        'crohns-disease'             => "Crohn's",
    ) ),
    array(
        'crohns-disease'             => "Crohn's",
        'ulcerative-colitis'         => 'UC',
        'inflammatory-bowel-disease' => 'IBD',
    ) );

check( 'list of rows with a slug field: each row moves whole, fields intact',
    vance_sort_conditions( array(
        array( 'slug' => 'irritable-bowel-syndrome', 'title' => 'IBS' ),
        array( 'slug' => 'diverticular-disease',     'title' => 'Diverticular' ),
        array( 'slug' => 'crohns-disease',           'title' => "Crohn's" ),
    ) ),
    array(
        array( 'slug' => 'crohns-disease',           'title' => "Crohn's" ),
        array( 'slug' => 'diverticular-disease',     'title' => 'Diverticular' ),
        array( 'slug' => 'irritable-bowel-syndrome', 'title' => 'IBS' ),
    ) );

check( 'map of rows: keys kept, each row\'s contents kept',
    vance_sort_conditions( array(
        'irritable-bowel-syndrome' => array( 'key' => 'ibs',    'label' => 'IBS' ),
        'crohns-disease'           => array( 'key' => 'crohns', 'label' => "Crohn's" ),
    ) ),
    array(
        'crohns-disease'           => array( 'key' => 'crohns', 'label' => "Crohn's" ),
        'irritable-bowel-syndrome' => array( 'key' => 'ibs',    'label' => 'IBS' ),
    ) );

/* ===================================================================== */
section( '3. vance_sort_conditions(): stability and the edges' );
/* ===================================================================== */

// The non-priority input is deliberately not alphabetical, so a sort-by-slug
// sabotage goes red.
check( 'non-priority entries keep their relative order (not alphabetical)',
    vance_sort_conditions( array(
        'irritable-bowel-syndrome',
        'inflammatory-bowel-disease',
        'microscopic-colitis',
        'colorectal-cancer',
        'crohns-disease',
    ) ),
    array(
        'crohns-disease',
        'irritable-bowel-syndrome',
        'inflammatory-bowel-disease',
        'microscopic-colitis',
        'colorectal-cancer',
    ) );

check( 'empty array comes back empty',
    vance_sort_conditions( array() ),
    array() );

check( 'list with no priority slug comes back unchanged',
    vance_sort_conditions( array(
        'irritable-bowel-syndrome',
        'inflammatory-bowel-disease',
        'microscopic-colitis',
        'colorectal-cancer',
    ) ),
    array(
        'irritable-bowel-syndrome',
        'inflammatory-bowel-disease',
        'microscopic-colitis',
        'colorectal-cancer',
    ) );

check( 'two rows for one priority slug keep their relative order',
    vance_sort_conditions( array(
        array( 'slug' => 'ulcerative-colitis',       'n' => 1 ),
        array( 'slug' => 'irritable-bowel-syndrome', 'n' => 0 ),
        array( 'slug' => 'ulcerative-colitis',       'n' => 2 ),
        array( 'slug' => 'crohns-disease',           'n' => 3 ),
    ) ),
    array(
        array( 'slug' => 'crohns-disease',           'n' => 3 ),
        array( 'slug' => 'ulcerative-colitis',       'n' => 1 ),
        array( 'slug' => 'ulcerative-colitis',       'n' => 2 ),
        array( 'slug' => 'irritable-bowel-syndrome', 'n' => 0 ),
    ) );

check( 'int keys and non-string values do not crash, and keep their place',
    vance_sort_conditions( array(
        5                 => 42,
        'not-a-condition' => null,
        'crohns-disease'  => 'x',
        7                 => array( 'no' => 'slug' ),
    ) ),
    array(
        'crohns-disease'  => 'x',
        5                 => 42,
        'not-a-condition' => null,
        7                 => array( 'no' => 'slug' ),
    ) );

check( 'list of non-string entries stays a list, priority string first',
    vance_sort_conditions( array( null, 'crohns-disease', 3, array( 'slug' => 5 ) ) ),
    array( 'crohns-disease', null, 3, array( 'slug' => 5 ) ) );

/* ===================================================================== */
section( '4. Real registries: vance_gi_conditions()' );
/* ===================================================================== */

$conds = vance_gi_conditions();
check( 'vance_gi_conditions: keys in the full priority order',
    array_keys( $conds ), $ALL_GI );
check( 'vance_gi_conditions: first three are crohns, uc, diverticular',
    array_slice( array_keys( $conds ), 0, 3 ),
    array( 'crohns-disease', 'ulcerative-colitis', 'diverticular-disease' ) );
check( 'vance_gi_conditions: the same seven conditions as before (set unchanged)',
    count( $conds ), 7 );
$cond_keys_sorted = array_keys( $conds );
sort( $cond_keys_sorted );
check( 'vance_gi_conditions: sorted keys match the seven known slugs',
    $cond_keys_sorted, $ALL_GI_SORTED );
check( 'vance_gi_conditions: customizer keys in the priority order',
    array_column( $conds, 'key' ),
    array( 'crohns', 'uc', 'div', 'ibd', 'mc', 'ibs', 'crc' ) );

/* ===================================================================== */
section( '5. Real registries: vance_gi_condition_cards() and vance_gi_hero_meta()' );
/* ===================================================================== */

check( 'vance_gi_condition_cards: slugs in the full priority order',
    array_column( vance_gi_condition_cards(), 'slug' ), $ALL_GI );
check( 'vance_gi_condition_cards: first three are crohns, uc, diverticular',
    array_slice( array_column( vance_gi_condition_cards(), 'slug' ), 0, 3 ),
    array( 'crohns-disease', 'ulcerative-colitis', 'diverticular-disease' ) );

check( 'vance_gi_hero_meta: keys in the full priority order',
    array_keys( vance_gi_hero_meta() ), $ALL_GI );
check( 'vance_gi_hero_meta: first three are crohns, uc, diverticular',
    array_slice( array_keys( vance_gi_hero_meta() ), 0, 3 ),
    array( 'crohns-disease', 'ulcerative-colitis', 'diverticular-disease' ) );

/* ===================================================================== */
section( '6. Real registries: related lists and the AI slug list' );
/* ===================================================================== */

// Each expected list is the page's own `related` list, with priority conditions
// moved to the front and the rest in their old order.
check( 'related_slugs: IBD (related uc, crohns, mc) gives crohns, uc, mc',
    vance_gi_hero_related_slugs( 'inflammatory-bowel-disease' ),
    array( 'crohns-disease', 'ulcerative-colitis', 'microscopic-colitis' ) );
check( 'related_slugs: IBS (related mc, div, ibd) gives div, mc, ibd',
    vance_gi_hero_related_slugs( 'irritable-bowel-syndrome' ),
    array( 'diverticular-disease', 'microscopic-colitis', 'inflammatory-bowel-disease' ) );
check( 'related_slugs: colorectal cancer (related div, ibd, uc) gives uc, div, ibd',
    vance_gi_hero_related_slugs( 'colorectal-cancer' ),
    array( 'ulcerative-colitis', 'diverticular-disease', 'inflammatory-bowel-disease' ) );
check( 'related_slugs: a stranger gives an empty list',
    vance_gi_hero_related_slugs( 'not-a-condition' ),
    array() );

check( 'vance_ai_gi_condition_slugs: full priority order',
    vance_ai_gi_condition_slugs(), $ALL_GI );

/* ===================================================================== */
section( '7. Home page tiles: vance_hub_split_conditions() fed as front-page-hub.php feeds it' );
/* ===================================================================== */

list( $tiles, $rest ) = vance_hub_split_conditions( vance_gi_condition_cards(), vance_condition_priority() );
check( 'home tiles: the three featured tiles are crohns, uc, diverticular, in that order',
    array_column( $tiles, 'slug' ),
    array( 'crohns-disease', 'ulcerative-colitis', 'diverticular-disease' ) );
check( 'home list: the other four follow, in their old order',
    array_column( $rest, 'slug' ),
    array( 'inflammatory-bowel-disease', 'microscopic-colitis', 'irritable-bowel-syndrome', 'colorectal-cancer' ) );

/* ===================================================================== */
section( '8. Community spaces' );
/* ===================================================================== */

check( 'vance_community_spaces: condition spaces first (crohns, uc), then the rest in order',
    array_keys( vance_community_spaces() ),
    array( 'crohns-disease', 'ulcerative-colitis', 'diverticular-disease', 'ibd', 'ibs', 'food-and-recipes', 'work-and-travel', 'carers' ) );

/* ===================================================================== */
section( '9. Lobby chips: 4 + 3, sliced from the priority-ordered meta' );
/* ===================================================================== */

$lobby_slugs = array_keys( vance_gi_hero_meta() );
check( 'lobby row one holds the four leading conditions, priority first',
    array_slice( $lobby_slugs, 0, 4 ),
    array( 'crohns-disease', 'ulcerative-colitis', 'diverticular-disease', 'inflammatory-bowel-disease' ) );
check( 'lobby row two holds the other three',
    array_slice( $lobby_slugs, 4 ),
    array( 'microscopic-colitis', 'irritable-bowel-syndrome', 'colorectal-cancer' ) );

$gi_hero_src = file_get_contents( $THEME . '/inc/gi-hero.php' );
check( 'lobby source slices the rows from vance_gi_hero_meta() keys',
    strpos( $gi_hero_src, '$slugs = array_keys( vance_gi_hero_meta() );' ) !== false, true );
check( 'lobby source cuts row one at four and row two after it',
    strpos( $gi_hero_src, 'array_slice( $slugs, 0, 4 ), array_slice( $slugs, 4 )' ) !== false, true );

/* ===================================================================== */
section( '10. Customizer map follows vance_gi_conditions() order' );
/* ===================================================================== */

$gi_panel_ids = array_keys( vance_customizer_structure_sections()['vance_gi_panel'] );
$want_cust_ids = array_map(
    function ( $k ) { return 'vance_gi_cond_' . $k; },
    array_column( vance_gi_conditions(), 'key' )
);
check( 'customizer: vance_gi_cond_* sections in the gi panel match vance_gi_conditions() order',
    array_values( preg_grep( '#^vance_gi_cond_#', $gi_panel_ids ) ),
    $want_cust_ids );
check( 'customizer: the first three condition sections are crohns, uc, diverticular',
    array_slice( array_values( preg_grep( '#^vance_gi_cond_#', $gi_panel_ids ) ), 0, 3 ),
    array( 'vance_gi_cond_crohns', 'vance_gi_cond_uc', 'vance_gi_cond_div' ) );

/* ===================================================================== */
section( '11. Template call sites' );
/* ===================================================================== */

$fp_src = file_get_contents( $THEME . '/front-page-hub.php' );
check( 'front-page-hub.php: the featured call reads vance_condition_priority()',
    strpos( $fp_src, 'vance_condition_priority()' ) !== false, true );

$split_at = strpos( $fp_src, 'vance_hub_split_conditions(' );
check( 'front-page-hub.php: the vance_hub_split_conditions() call is present',
    $split_at !== false, true );
$split_end = strpos( $fp_src, ');', $split_at );
$split_call = substr( $fp_src, $split_at, $split_end - $split_at );
check( 'front-page-hub.php: the featured call hard-codes no crohns slug',
    strpos( $split_call, "'crohns-disease'" ) === false, true );
check( 'front-page-hub.php: the featured call hard-codes no uc slug',
    strpos( $split_call, "'ulcerative-colitis'" ) === false, true );
check( 'front-page-hub.php: the featured call hard-codes no diverticular slug',
    strpos( $split_call, "'diverticular-disease'" ) === false, true );

$pdr_src = file_get_contents( $THEME . '/page-discovery-results.php' );
check( 'page-discovery-results.php: the submitted condition slugs go through vance_sort_conditions()',
    strpos( $pdr_src, 'vance_sort_conditions(' ) !== false, true );

/* ===================================================================== */
section( '12. Mega menu: the CONDITIONS tile grid' );

// The live widget text, in the order it is stored (tools/build-mega-menu.php).
$nav_tiles = array();
foreach ( array(
    'inflammatory-bowel-disease', 'ulcerative-colitis', 'crohns-disease', 'microscopic-colitis',
    'irritable-bowel-syndrome', 'colorectal-cancer', 'diverticular-disease',
) as $s ) {
    $nav_tiles[] = array( 'icon' => 'x', 'title' => $s, 'desc' => '', 'url' => "https://example.test/$s/", 'feature' => false );
}
$nav_tiles[] = array( 'icon' => 'quiz', 'title' => 'quiz', 'desc' => '', 'url' => 'https://example.test/gastro-health-survey/', 'feature' => true );

$nav_out = vance_nav_tiles_in_priority( $nav_tiles );
check( 'nav tiles: priority conditions first, the rest in stored order, the quiz tile last',
    array_column( $nav_out, 'title' ),
    array( 'crohns-disease', 'ulcerative-colitis', 'diverticular-disease', 'inflammatory-bowel-disease',
        'microscopic-colitis', 'irritable-bowel-syndrome', 'colorectal-cancer', 'quiz' ) );
check( 'nav tiles: a tile row comes back unchanged (no added keys)', $nav_out[0], $nav_tiles[2] );
check( 'nav tiles: a relative URL and a URL with a query still match',
    array_column( vance_nav_tiles_in_priority( array(
        array( 'title' => 'a', 'url' => '/inflammatory-bowel-disease/' ),
        array( 'title' => 'b', 'url' => '/diverticular-disease/?ref=menu' ),
    ) ), 'title' ),
    array( 'b', 'a' ) );
check( 'nav tiles: an empty grid stays empty', vance_nav_tiles_in_priority( array() ), array() );
check( 'nav tiles: a tile with no url does not crash and keeps its place',
    array_column( vance_nav_tiles_in_priority( array(
        array( 'title' => 'a' ),
        array( 'title' => 'b', 'url' => '/crohns-disease/' ),
    ) ), 'title' ),
    array( 'b', 'a' ) );
check( 'nav-mega.php: the widget renders through vance_nav_tiles_in_priority()',
    strpos( file_get_contents( $THEME . '/inc/nav-mega.php' ), '$tiles = vance_nav_tiles_in_priority(' ) !== false, true );

/* ===================================================================== */
$TOTAL = $PASS + $FAIL;
echo "\n$PASS passed, $FAIL failed of $TOTAL checks\n";
if ( $FAIL > 0 ) {
    echo "FAIL — $FAIL of $TOTAL checks failed.\n";
    exit( 1 );
}
echo "OK — $PASS checks passed.\n";
exit( 0 );
