<?php
/**
 * Template Name: Tools & Resources
 *
 * Public-facing landing page that surfaces the 4 dashboard tools without
 * requiring login. The save-result features remain logged-in-only — this page
 * exposes the calculators themselves for anyone to use.
 *
 * To activate: create a Page titled "Tools & Resources", set slug
 * `tools-resources`, and choose "Tools & Resources" as the template.
 *
 * Customizer panel: Appearance → Customize → Tools & Resources Page.
 */
get_header(); ?>

<main id="main-content">

    <!-- HERO (category-style: 350px banner, matches archive.php Tools/Resource branch) -->
    <?php
    $hero_bg      = vance_get_theme_mod( 'vance_tools_hero_bg', get_template_directory_uri() . '/assets/img/education_hero.png' );
    $hero_tag     = vance_get_theme_mod( 'vance_tools_hero_tag',   'Empower Your Practice' );
    $hero_title   = vance_get_theme_mod( 'vance_tools_hero_title', 'Tools &amp; Resources' );
    $hero_overlay = max( 0, min( 100, absint( vance_get_theme_mod( 'vance_tools_hero_overlay', 70 ) ) ) ) / 100;
    $hero_overlay_bottom = min( 1, $hero_overlay + 0.10 );
    $title_color  = vance_get_theme_mod( 'vance_hero_title_color', '#ffffff' );
    ?>
    <section class="hero tools-hero" style="height: 350px; min-height: 0; display: flex; align-items: center; padding: 0; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-image: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $hero_overlay ); ?>), rgba(20,40,70,<?php echo esc_attr( $hero_overlay_bottom ); ?>)), url('<?php echo esc_url( $hero_bg ); ?>'); background-position: center center; background-size: cover; background-repeat: no-repeat; z-index: 1;"></div>
        <div class="container" style="position: relative; z-index: 2; width: 100%;">
            <div class="hero-content" style="max-width: 800px;">
                <span class="eyebrow" style="color: #008080; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; font-size: 14px; display: block; margin-bottom: 10px;"><?php echo esc_html( $hero_tag ); ?></span>
                <h1 class="entry-title" style="font-size: 56px; color: <?php echo esc_attr( $title_color ); ?>; font-weight: 700; margin: 0; line-height: 1.1;"><?php echo wp_kses_post( $hero_title ); ?></h1>
            </div>
        </div>
    </section>

    <!-- INTRO -->
    <?php
    $intro_title = vance_get_theme_mod( 'vance_tools_intro_title', 'Clinical-grade calculators, free for everyone' );
    $intro_desc  = vance_get_theme_mod( 'vance_tools_intro_desc',  "Whether you're tracking your own health or supporting a patient, these tools turn evidence into a number you can act on. No login needed to use them — register if you want to save results to your dashboard." );
    ?>
    <section class="section-padding tools-intro-section" style="background: white;">
        <div class="container">
            <div class="text-center max-600 margin-b-60">
                <span class="tag-section">Open Access</span>
                <h2 style="color: var(--secondary-color);"><?php echo esc_html( $intro_title ); ?></h2>
                <p style="color: var(--text-light);"><?php echo esc_html( $intro_desc ); ?></p>
            </div>
        </div>
    </section>

    <!-- TOOLS GRID — each card links to its own dedicated page (ask-ai-style shell) -->
    <?php
    $tools = array(
        array(
            'slug'     => 'omega-3-calculator',
            'page_url' => '/omega-3-calculator/',
            'name'     => 'Omega-3 Calculator',
            'tag'      => 'Nutrition',
            'desc'     => 'Calculate your personalised EPA + DHA target based on body weight, dietary intake, and clinical guidance. Built on the latest gastroenterology evidence.',
            'colors'   => array( '#008080', '#006666', '#ffffff' ),
            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12c2.5-3.5 6.5-5 9-5s6.5 1.5 9 5c-2.5 3.5-6.5 5-9 5s-6.5-1.5-9-5zm12 0a3 3 0 11-6 0 3 3 0 016 0z"/>',
        ),
        array(
            'slug'     => 'malnutrition-calculator',
            'page_url' => '/malnutrition-calculator/',
            'name'     => 'Malnutrition Calculator',
            'tag'      => 'IBD Screening',
            'desc'     => 'Clinically-grounded 11-step malnutrition risk screener for IBD patients. Combines MUST, IBD-NST, and GLIM criteria into a single, actionable score.',
            'colors'   => array( '#78bfbf', '#5fa3a3', '#ffffff' ),
            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
        ),
        array(
            'slug'     => 'blood-test',
            'page_url' => '/blood-test/',
            'name'     => 'Blood Test Analyser',
            'tag'      => 'Lab Results',
            'desc'     => 'Drop in your blood panel results and get plain-language analysis flagging anything outside reference ranges. Designed to help you prepare for your next clinic appointment.',
            'colors'   => array( '#aedbdb', '#88c5c5', '#008080' ),
            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        ),
        array(
            'slug'     => 'ibd-recipes',
            'page_url' => '/ibd-recipes/',
            'name'     => 'IBD Recipes & Meal Planner',
            'tag'      => 'Meal Planning',
            'desc'     => 'Browse EPA-rich, gut-friendly recipes with full nutrition data. Build weekly meal plans freely — saving plans prompts a quick signup.',
            'colors'   => array( '#def4f4', '#aedbdb', '#008080' ),
            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2h-4a2 2 0 01-2-2v-4a2 2 0 00-2-2H10a2 2 0 00-2 2v4a2 2 0 01-2 2H2V9z" transform="translate(0,-1)"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14h8M8 11h8" />',
        ),
    );
    ?>
    <section id="tools-grid" class="section-padding tools-grid-section" style="background: var(--accent-color);">
        <div class="container">
            <div class="tools-card-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 28px;">
                <?php foreach ( $tools as $tool ) : ?>
                <a class="tool-card tool-card--<?php echo esc_attr( $tool['slug'] ); ?>" href="<?php echo esc_url( $tool['page_url'] ); ?>" style="display: flex; flex-direction: column; padding: 32px; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border-top: 4px solid <?php echo esc_attr( $tool['colors'][0] ); ?>; text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s;">
                    <div class="tool-card__head" style="display: flex; gap: 16px; align-items: flex-start; margin-bottom: 20px;">
                        <div class="tool-card__icon" style="flex-shrink: 0; width: 56px; height: 56px; background: linear-gradient(135deg, <?php echo esc_attr( $tool['colors'][0] ); ?>, <?php echo esc_attr( $tool['colors'][1] ); ?>); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <svg width="28" height="28" fill="none" stroke="<?php echo esc_attr( $tool['colors'][2] ); ?>" viewBox="0 0 24 24"><?php echo $tool['icon']; ?></svg>
                        </div>
                        <div>
                            <span style="display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: var(--primary-color); margin-bottom: 4px;"><?php echo esc_html( $tool['tag'] ); ?></span>
                            <h3 class="tool-card__title" style="font-size: 19px; color: var(--secondary-color); margin: 0; line-height: 1.3;"><?php echo esc_html( $tool['name'] ); ?></h3>
                        </div>
                    </div>
                    <p class="tool-card__desc" style="color: var(--text-light); font-size: 14px; margin: 0 0 20px 0; line-height: 1.6; flex: 1;"><?php echo esc_html( $tool['desc'] ); ?></p>
                    <span class="tool-card__cta" style="font-size: 14px; font-weight: 600; color: var(--primary-color); display: inline-flex; align-items: center; gap: 6px;">
                        Open <?php echo esc_html( $tool['name'] ); ?> →
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <style>
        .tool-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(10,25,41,0.10) !important; }
    </style>

    <!-- SAVE-RESULTS CTA -->
    <section class="section-padding tools-cta-section" style="background: linear-gradient(135deg, #008080, #006666);">
        <div class="container" style="text-align: center; color: white;">
            <h2 style="color: white; margin-bottom: 16px;">Want to save your results?</h2>
            <p class="max-600" style="font-size: 18px; margin: 0 auto 32px; color: rgba(255,255,255,0.92);">
                Free registration unlocks result history, personalised meal plans, the Ask AI clinical assistant, and printable handouts — all in your private dashboard.
            </p>
            <div class="hero-actions" style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                <a href="/register/" class="btn btn-primary" style="background: white; color: #008080; border: none;">Register Free</a>
                <a href="/login/" class="btn btn-outline" style="border-color: rgba(255,255,255,0.4); color: white;">Already have an account? Sign in</a>
            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>
