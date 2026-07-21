<?php
/**
 * GI Health Customizer Controls
 *
 * Registers a "Page — GI Health" panel with sections for the hub page,
 * each of the seven condition pages, and shared colour tokens.
 *
 * Naming convention: vance_gi_{section}_{property}
 * All settings are read in page-gi-health.php and page-gi-condition.php
 * via vance_get_theme_mod() and injected as CSS custom properties.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function vance_gi_customize_register( WP_Customize_Manager $wp_customize ): void {

    /* ════════════════════════════════════════
       PANEL
    ════════════════════════════════════════ */
    $wp_customize->add_panel( 'vance_gi_panel', [
        'title'    => __( 'Page — GI Health', 'sla-health-hub' ),
        'priority' => 44,
    ] );

    /* ─── helper closures ─── */
    $colour = function( string $key, string $default, string $section, string $label ) use ( $wp_customize ): void {
        $wp_customize->add_setting( $key, [ 'default' => $default, 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'postMessage' ] );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $key, [ 'label' => $label, 'section' => $section ] ) );
    };

    $image = function( string $key, string $section, string $label, string $desc = '' ) use ( $wp_customize ): void {
        $wp_customize->add_setting( $key, [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
        $args = [ 'label' => $label, 'section' => $section ];
        if ( $desc ) { $args['description'] = $desc; }
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $key, $args ) );
    };

    /* ════════════════════════════════════════
       SECTION 1 — Hub: Hero
    ════════════════════════════════════════ */
    $wp_customize->add_section( 'vance_gi_hub_hero', [
        'title' => 'Hub — Hero',
        'panel' => 'vance_gi_panel',
    ] );

    $sec = 'vance_gi_hub_hero';
    $wp_customize->add_setting( 'vance_gi_hub_hero_eyebrow', [ 'default' => 'GI Health', 'sanitize_callback' => 'sanitize_text_field' ] );
    $wp_customize->add_control( 'vance_gi_hub_hero_eyebrow', [ 'label' => 'Eyebrow label', 'section' => $sec, 'type' => 'text' ] );

    $wp_customize->add_setting( 'vance_gi_hub_hero_heading', [ 'default' => 'Seven common gut conditions, clearly explained', 'sanitize_callback' => 'wp_kses_post' ] );
    $wp_customize->add_control( 'vance_gi_hub_hero_heading', [ 'label' => 'Heading (HTML allowed)', 'section' => $sec, 'type' => 'textarea' ] );

    $wp_customize->add_setting( 'vance_gi_hub_hero_lede', [ 'default' => 'Clinician-reviewed information on inflammatory bowel disease, IBS, colorectal cancer and more — written in plain language to help you understand, prepare and manage.', 'sanitize_callback' => 'sanitize_textarea_field' ] );
    $wp_customize->add_control( 'vance_gi_hub_hero_lede', [ 'label' => 'Lede paragraph', 'section' => $sec, 'type' => 'textarea' ] );

    $wp_customize->add_setting( 'vance_gi_hub_hero_btn1_text', [ 'default' => 'Explore conditions', 'sanitize_callback' => 'sanitize_text_field' ] );
    $wp_customize->add_control( 'vance_gi_hub_hero_btn1_text', [ 'label' => 'Primary button label', 'section' => $sec, 'type' => 'text' ] );
    $wp_customize->add_setting( 'vance_gi_hub_hero_btn1_url', [ 'default' => '#conditions', 'sanitize_callback' => 'esc_url_raw' ] );
    $wp_customize->add_control( 'vance_gi_hub_hero_btn1_url', [ 'label' => 'Primary button URL', 'section' => $sec, 'type' => 'url' ] );

    $wp_customize->add_setting( 'vance_gi_hub_hero_btn2_text', [ 'default' => 'My Dashboard', 'sanitize_callback' => 'sanitize_text_field' ] );
    $wp_customize->add_control( 'vance_gi_hub_hero_btn2_text', [ 'label' => 'Secondary button label', 'section' => $sec, 'type' => 'text' ] );
    $wp_customize->add_setting( 'vance_gi_hub_hero_btn2_url', [ 'default' => '/dashboard/', 'sanitize_callback' => 'esc_url_raw' ] );
    $wp_customize->add_control( 'vance_gi_hub_hero_btn2_url', [ 'label' => 'Secondary button URL', 'section' => $sec, 'type' => 'url' ] );

    $wp_customize->add_setting( 'vance_gi_hub_hero_bg_image', [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_gi_hub_hero_bg_image', [
        'label'   => 'Hero background image (optional, overlaid on gradient)',
        'section' => $sec,
    ] ) );
    $wp_customize->add_setting( 'vance_gi_hub_hero_bg_overlay', [ 'default' => 70, 'sanitize_callback' => 'absint' ] );
    $wp_customize->add_control( 'vance_gi_hub_hero_bg_overlay', [ 'label' => 'Image overlay opacity (0 = fully show image, 100 = fully hide it)', 'section' => $sec, 'type' => 'number', 'input_attrs' => [ 'min' => 0, 'max' => 100 ] ] );

    /* hero gradient colours */
    $colour( 'vance_gi_hub_hero_c1', '#003d3d', $sec, 'Hero gradient — dark start colour' );
    $colour( 'vance_gi_hub_hero_c2', '#006666', $sec, 'Hero gradient — mid colour' );
    $colour( 'vance_gi_hub_hero_c3', '#008080', $sec, 'Hero gradient — end colour' );

    /* ════════════════════════════════════════
       SECTION 2 — Hub: Conditions Grid
    ════════════════════════════════════════ */
    $wp_customize->add_section( 'vance_gi_hub_grid', [
        'title' => 'Hub — Conditions Grid',
        'panel' => 'vance_gi_panel',
    ] );
    $sec = 'vance_gi_hub_grid';

    $wp_customize->add_setting( 'vance_gi_hub_grid_heading', [ 'default' => 'Learn more about common GI conditions', 'sanitize_callback' => 'sanitize_textarea_field' ] );
    $wp_customize->add_control( 'vance_gi_hub_grid_heading', [ 'label' => 'Section heading', 'section' => $sec, 'type' => 'textarea' ] );

    $wp_customize->add_setting( 'vance_gi_hub_grid_subtitle', [ 'default' => 'Understanding your digestive health, one condition at a time', 'sanitize_callback' => 'sanitize_text_field' ] );
    $wp_customize->add_control( 'vance_gi_hub_grid_subtitle', [ 'label' => 'Section subtitle', 'section' => $sec, 'type' => 'text' ] );

    /* ════════════════════════════════════════
       SECTION 3 — Hub: Stats Band
    ════════════════════════════════════════ */
    $wp_customize->add_section( 'vance_gi_hub_stats', [
        'title' => 'Hub — Stats Band',
        'panel' => 'vance_gi_panel',
    ] );
    $sec = 'vance_gi_hub_stats';

    $wp_customize->add_setting( 'vance_gi_hub_stats_heading', [ 'default' => "You're not alone", 'sanitize_callback' => 'sanitize_text_field' ] );
    $wp_customize->add_control( 'vance_gi_hub_stats_heading', [ 'label' => 'Heading', 'section' => $sec, 'type' => 'text' ] );

    $wp_customize->add_setting( 'vance_gi_hub_stats_desc', [ 'default' => "Digestive conditions are more common than you might think. You're in good company.", 'sanitize_callback' => 'sanitize_textarea_field' ] );
    $wp_customize->add_control( 'vance_gi_hub_stats_desc', [ 'label' => 'Subtitle', 'section' => $sec, 'type' => 'textarea' ] );

    $stat_defaults = [
        1 => [ '1 in 7',   'UK adults live with IBS symptoms' ],
        2 => [ '500,000',  'People in the UK live with inflammatory bowel disease' ],
        3 => [ '9 in 10',  'Survive bowel cancer when it is found at the earliest stage' ],
    ];
    foreach ( $stat_defaults as $i => $d ) {
        $wp_customize->add_setting( "vance_gi_hub_stat{$i}_num", [ 'default' => $d[0], 'sanitize_callback' => 'sanitize_text_field' ] );
        $wp_customize->add_control( "vance_gi_hub_stat{$i}_num", [ 'label' => "Stat $i — number / headline figure", 'section' => $sec, 'type' => 'text' ] );
        $wp_customize->add_setting( "vance_gi_hub_stat{$i}_label", [ 'default' => $d[1], 'sanitize_callback' => 'sanitize_text_field' ] );
        $wp_customize->add_control( "vance_gi_hub_stat{$i}_label", [ 'label' => "Stat $i — label", 'section' => $sec, 'type' => 'text' ] );
    }

    /* ════════════════════════════════════════
       SECTION 4 — Hub: CTA Band
    ════════════════════════════════════════ */
    $wp_customize->add_section( 'vance_gi_hub_cta', [
        'title' => 'Hub — CTA Band',
        'panel' => 'vance_gi_panel',
    ] );
    $sec = 'vance_gi_hub_cta';

    $wp_customize->add_setting( 'vance_gi_hub_cta_heading', [ 'default' => 'Track your symptoms and learn what works for you', 'sanitize_callback' => 'sanitize_text_field' ] );
    $wp_customize->add_control( 'vance_gi_hub_cta_heading', [ 'label' => 'Heading', 'section' => $sec, 'type' => 'text' ] );

    $wp_customize->add_setting( 'vance_gi_hub_cta_desc', [ 'default' => 'The Vance Health Hub dashboard brings together symptom trackers, evidence-based tools and clinician-reviewed resources to help you manage your gut health day to day.', 'sanitize_callback' => 'sanitize_textarea_field' ] );
    $wp_customize->add_control( 'vance_gi_hub_cta_desc', [ 'label' => 'Description', 'section' => $sec, 'type' => 'textarea' ] );

    $wp_customize->add_setting( 'vance_gi_hub_cta_btn_text', [ 'default' => 'Go to My Dashboard', 'sanitize_callback' => 'sanitize_text_field' ] );
    $wp_customize->add_control( 'vance_gi_hub_cta_btn_text', [ 'label' => 'Button label', 'section' => $sec, 'type' => 'text' ] );
    $wp_customize->add_setting( 'vance_gi_hub_cta_btn_url', [ 'default' => '/dashboard/', 'sanitize_callback' => 'esc_url_raw' ] );
    $wp_customize->add_control( 'vance_gi_hub_cta_btn_url', [ 'label' => 'Button URL', 'section' => $sec, 'type' => 'url' ] );

    $colour( 'vance_gi_hub_cta_c1', '#008080', $sec, 'CTA gradient — left colour' );
    $colour( 'vance_gi_hub_cta_c2', '#006666', $sec, 'CTA gradient — right colour' );

    /* ════════════════════════════════════════
       SECTION 5 — Shared Colours
    ════════════════════════════════════════ */
    $wp_customize->add_section( 'vance_gi_colours', [
        'title' => 'GI Health — Colours',
        'panel' => 'vance_gi_panel',
    ] );
    $sec = 'vance_gi_colours';

    $colour( 'vance_gi_color_primary',        '#008080', $sec, 'Primary teal (buttons, borders, links)' );
    $colour( 'vance_gi_color_primary_hover',  '#006666', $sec, 'Primary teal — darker hover state' );
    $colour( 'vance_gi_color_primary_light',  '#78bfbf', $sec, 'Primary light (stat numbers, orb)' );
    $colour( 'vance_gi_color_primary_pale',   '#aedbdb', $sec, 'Primary pale (hero eyebrow, dots)' );
    $colour( 'vance_gi_color_primary_wash',   '#def4f4', $sec, 'Primary wash (callout bg, card icon bg)' );
    $colour( 'vance_gi_color_callout_border', '#008080', $sec, 'Callout box — left border colour' );
    $colour( 'vance_gi_color_keyfact_border', '#008080', $sec, 'Keyfact strip — bottom border colour' );
    $colour( 'vance_gi_color_sidebar_accent', '#008080', $sec, 'Left sidebar panel — top border colour' );

    /* ════════════════════════════════════════
       SECTIONS 6–12 — Condition Pages
    ════════════════════════════════════════ */
    $conditions = [
        'ibd'         => [ 'IBD (Inflammatory Bowel Disease)',   'Inflammatory Bowel Disease', 'The umbrella term for a group of long-term conditions that cause inflammation of the digestive tract, most commonly Crohn\'s disease and ulcerative colitis.' ],
        'uc'          => [ 'Ulcerative Colitis',                 'Ulcerative Colitis',          'A type of inflammatory bowel disease that causes inflammation and ulcers in the lining of the colon and rectum.' ],
        'crohns'      => [ 'Crohn\'s Disease',                   'Crohn\'s Disease',            'A type of inflammatory bowel disease that can cause inflammation anywhere in the digestive tract, most often the end of the small intestine.' ],
        'mc'          => [ 'Microscopic Colitis',                'Microscopic Colitis',         'Inflammation of the colon that can only be seen under a microscope, causing chronic, watery, non-bloody diarrhoea.' ],
        'ibs'         => [ 'IBS (Irritable Bowel Syndrome)',     'Irritable Bowel Syndrome',    'A common, long-term disorder of how the gut functions, causing abdominal pain, bloating and changes in bowel habit, without visible damage to the bowel.' ],
        'crc'         => [ 'Colorectal Cancer',                  'Colorectal Cancer',           'Cancer that begins in the colon or rectum. When found early, often through screening, it is one of the most treatable cancers.' ],
        'div'         => [ 'Diverticular Disease',               'Diverticular Disease &amp; Diverticulitis', 'Small pouches called diverticula can form in the wall of the colon as we get older. They are very common and usually harmless, but can sometimes cause symptoms or become inflamed.' ],
    ];

    foreach ( $conditions as $key => [$panel_label, $default_title, $default_lede] ) {
        $sec_id = "vance_gi_cond_{$key}";

        $wp_customize->add_section( $sec_id, [
            'title' => "Condition — {$panel_label}",
            'panel' => 'vance_gi_panel',
        ] );

        $wp_customize->add_setting( "{$sec_id}_title", [ 'default' => $default_title, 'sanitize_callback' => 'wp_kses_post' ] );
        $wp_customize->add_control( "{$sec_id}_title", [ 'label' => 'Page title (H1)', 'section' => $sec_id, 'type' => 'text' ] );

        $wp_customize->add_setting( "{$sec_id}_lede", [ 'default' => $default_lede, 'sanitize_callback' => 'sanitize_textarea_field' ] );
        $wp_customize->add_control( "{$sec_id}_lede", [ 'label' => 'Lede paragraph', 'section' => $sec_id, 'type' => 'textarea' ] );

        $wp_customize->add_setting( "{$sec_id}_image", [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "{$sec_id}_image", [
            'label'       => 'Featured image (shown below the title)',
            'description' => 'Optional. Recommended size 1200×400 px.',
            'section'     => $sec_id,
        ] ) );

        $wp_customize->add_setting( "{$sec_id}_image_caption", [ 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ] );
        $wp_customize->add_control( "{$sec_id}_image_caption", [ 'label' => 'Image caption (optional)', 'section' => $sec_id, 'type' => 'text' ] );
    }
}
add_action( 'customize_register', 'vance_gi_customize_register', 21 );


/**
 * Output inline CSS custom-property overrides for GI Health colour settings.
 * Hooked to wp_head so it lands before the body and after gi-health.css.
 * Only fires on pages using the GI Health templates.
 */
function vance_gi_inline_css(): void {
    if ( ! is_page_template( 'page-gi-health.php' ) && ! is_page_template( 'page-gi-condition.php' ) ) {
        return;
    }

    $p   = fn( string $key, string $fb ) => esc_attr( vance_get_theme_mod( $key, $fb ) );

    $primary        = $p( 'vance_gi_color_primary',        '#008080' );
    $primary_hover  = $p( 'vance_gi_color_primary_hover',  '#006666' );
    $primary_light  = $p( 'vance_gi_color_primary_light',  '#78bfbf' );
    $primary_pale   = $p( 'vance_gi_color_primary_pale',   '#aedbdb' );
    $primary_wash   = $p( 'vance_gi_color_primary_wash',   '#def4f4' );
    $callout_border = $p( 'vance_gi_color_callout_border', '#008080' );
    $keyfact_border = $p( 'vance_gi_color_keyfact_border', '#008080' );
    $sidebar_accent = $p( 'vance_gi_color_sidebar_accent', '#008080' );

    /* Hub-specific tokens, only needed on hub page — harmless on condition pages */
    $hero_c1   = $p( 'vance_gi_hub_hero_c1', '#003d3d' );
    $hero_c2   = $p( 'vance_gi_hub_hero_c2', '#006666' );
    $hero_c3   = $p( 'vance_gi_hub_hero_c3', '#008080' );
    $cta_c1    = $p( 'vance_gi_hub_cta_c1', '#008080' );
    $cta_c2    = $p( 'vance_gi_hub_cta_c2', '#006666' );

    echo "<style id=\"vance-gi-tokens\">\n:root{\n";
    echo "--gi-primary:{$primary};\n";
    echo "--gi-primary-hover:{$primary_hover};\n";
    echo "--gi-primary-light:{$primary_light};\n";
    echo "--gi-primary-pale:{$primary_pale};\n";
    echo "--gi-primary-wash:{$primary_wash};\n";
    echo "--gi-callout-bg:{$primary_wash};\n";
    echo "--gi-callout-border:{$callout_border};\n";
    echo "--gi-card-icon-bg:{$primary_wash};\n";
    echo "--gi-keyfact-border:{$keyfact_border};\n";
    echo "--gi-sidebar-accent:{$sidebar_accent};\n";
    echo "--gi-hero-c1:{$hero_c1};\n";
    echo "--gi-hero-c2:{$hero_c2};\n";
    echo "--gi-hero-c3:{$hero_c3};\n";
    echo "--gi-cta-c1:{$cta_c1};\n";
    echo "--gi-cta-c2:{$cta_c2};\n";
    echo "}\n</style>\n";
}
add_action( 'wp_head', 'vance_gi_inline_css', 99 );
