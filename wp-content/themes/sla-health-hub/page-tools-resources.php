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

    <!-- HERO (patients-hero style: full-height with description + CTA buttons) -->
    <?php
    $hero_bg        = vance_get_theme_mod( 'vance_tools_hero_bg', get_template_directory_uri() . '/assets/img/education_hero.png' );
    $hero_tag       = vance_get_theme_mod( 'vance_tools_hero_tag',   'Free Tools' );
    $hero_title     = vance_get_theme_mod( 'vance_tools_hero_title', 'Tools &amp; <span class="highlight">Resources</span>' );
    $hero_desc      = vance_get_theme_mod( 'vance_tools_hero_desc',  'Clinical calculators built on peer-reviewed evidence — free to use, no signup required. Save your results and build a meal plan by registering for a free account.' );
    $hero_overlay   = max( 0, min( 100, absint( vance_get_theme_mod( 'vance_tools_hero_overlay', 70 ) ) ) ) / 100;
    $hero_overlay_bottom = min( 1, $hero_overlay + 0.15 );
    $hero_btn1_text = vance_get_theme_mod( 'vance_tools_hero_btn1_text', 'Try a Tool' );
    $hero_btn1_link = vance_get_theme_mod( 'vance_tools_hero_btn1_link', '#tools-list' );
    $hero_btn2_text = vance_get_theme_mod( 'vance_tools_hero_btn2_text', 'Create Free Account' );
    $hero_btn2_link = vance_get_theme_mod( 'vance_tools_hero_btn2_link', '/register/' );
    ?>
    <section class="hero tools-hero" style="padding: 72px 0 116px; min-height: 332px; display: flex; align-items: center; background: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $hero_overlay ); ?>), rgba(10,25,41,<?php echo esc_attr( $hero_overlay_bottom ); ?>)), url('<?php echo esc_url( $hero_bg ); ?>') no-repeat center center; background-size: cover;">
        <div class="container">
            <div class="hero-content">
                <span class="tag-label"><?php echo esc_html( $hero_tag ); ?></span>
                <h1><?php echo wp_kses_post( $hero_title ); ?></h1>
                <p><?php echo esc_html( $hero_desc ); ?></p>
                <?php if ( $hero_btn1_text || $hero_btn2_text ) : ?>
                <div class="hero-actions" style="margin-top: 24px;">
                    <?php if ( $hero_btn1_text ) : ?>
                        <a href="<?php echo esc_url( $hero_btn1_link ); ?>" class="btn btn-primary"><?php echo esc_html( $hero_btn1_text ); ?></a>
                    <?php endif; ?>
                    <?php if ( $hero_btn2_text ) : ?>
                        <a href="<?php echo esc_url( $hero_btn2_link ); ?>" class="btn btn-outline"><?php echo esc_html( $hero_btn2_text ); ?></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- INTRO -->
    <?php
    $intro_title     = vance_get_theme_mod( 'vance_tools_intro_title', 'Clinical-grade calculators, free for everyone' );
    $intro_desc      = vance_get_theme_mod( 'vance_tools_intro_desc',  "Whether you're tracking your own health or supporting a patient, these tools turn evidence into a number you can act on. No login needed to use them — register if you want to save results to your dashboard." );
    $intro_eyebrow   = vance_get_theme_mod( 'vance_tools_intro_eyebrow',   'Open Access' );
    $intro_bg        = vance_get_theme_mod( 'vance_tools_intro_bg_color', '#ffffff' );
    $intro_text_col  = vance_get_theme_mod( 'vance_tools_intro_text_color', '' ); // empty → use theme defaults
    $intro_eyb_bg    = vance_get_theme_mod( 'vance_tools_intro_eyebrow_bg',    'rgba(0,128,128,0.08)' );
    $intro_eyb_col   = vance_get_theme_mod( 'vance_tools_intro_eyebrow_color', '#008080' );
    // Section padding is 20% less top/bottom than the standard `.section-padding`
    // (which is 80px top/bottom in main.css) → 64px.
    ?>
    <section class="tools-intro-section" style="background: <?php echo esc_attr( $intro_bg ); ?>; padding: 64px 0;">
        <div class="container">
            <div class="text-center max-600 tools-intro-inner" style="margin: 0 auto 0;">
                <span class="tools-intro-eyebrow" style="display: inline-block; padding: 6px 16px; background: <?php echo esc_attr( $intro_eyb_bg ); ?>; color: <?php echo esc_attr( $intro_eyb_col ); ?>; font-size: 12px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; margin-bottom: 14px; border-radius: 0;">
                    <?php echo esc_html( $intro_eyebrow ); ?>
                </span>
                <h2 class="tools-intro-title" style="<?php echo $intro_text_col ? 'color: ' . esc_attr( $intro_text_col ) . ';' : 'color: var(--secondary-color);'; ?> margin: 0 0 12px;"><?php echo esc_html( $intro_title ); ?></h2>
                <p class="tools-intro-desc" style="<?php echo $intro_text_col ? 'color: ' . esc_attr( $intro_text_col ) . '; opacity: 0.85;' : 'color: var(--text-light);'; ?> margin: 0;"><?php echo esc_html( $intro_desc ); ?></p>
            </div>
        </div>
    </section>

    <!-- TOOLS GRID — each card links to its own dedicated page (ask-ai-style shell) -->
    <?php
    $tools = array(
        // Omega-3 Calculator card removed 2026-07-21 per request. The tool page
        // (page-omega-3-calculator.php → /omega-3-calculator/) and its asset
        // bundle still exist and remain reachable by direct URL; only the card
        // on this Tools & Resources listing was pulled.
        //
        // Blood Test Analyser card removed 2026-07-23 per request. The tool page
        // (page-blood-test.php → /blood-test/) and its asset bundle still exist
        // and remain reachable by direct URL; only the card on this listing was pulled.
        array(
            // IBD Health Quiz lives as a PHP page template (page-healthcare-quiz.php),
            // not as a /assets/tools/ bundle — so we just link to /healthcare-quiz/.
            'slug'     => 'healthcare-quiz',
            'page_url' => '/healthcare-quiz/',
            'name'     => 'IBD Health Quiz',
            'tag'      => 'Self-Assessment',
            'desc'     => 'A short, evidence-based questionnaire covering symptom patterns, dietary triggers, and lifestyle factors. Get an instant summary you can share with your clinician.',
            'colors'   => array( '#78bfbf', '#aedbdb', '#008080' ),
            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.5M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>',
        ),
        array(
            'slug'     => 'ibd-recipes',
            // Live WP page is /ibd-recipies/ (legacy slug typo, intentionally preserved
            // to avoid breaking inbound links). Asset folder is /ibd-recipes/ (no typo).
            'page_url' => '/ibd-recipies/',
            'name'     => 'IBD Recipes & Meal Planner',
            'tag'      => 'Meal Planning',
            'desc'     => 'Browse EPA-rich, gut-friendly recipes with full nutrition data. Build weekly meal plans freely — saving plans prompts a quick signup.',
            'colors'   => array( '#def4f4', '#aedbdb', '#008080' ),
            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2h-4a2 2 0 01-2-2v-4a2 2 0 00-2-2H10a2 2 0 00-2 2v4a2 2 0 01-2 2H2V9z" transform="translate(0,-1)"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14h8M8 11h8" />',
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
    );
    ?>
    <section id="tools-grid" class="section-padding tools-grid-section" style="background: var(--accent-color);">
        <div class="container">
            <div class="tools-card-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px;">
                <?php foreach ( $tools as $tool ) : ?>
                <a class="tool-card tool-card--<?php echo esc_attr( $tool['slug'] ); ?>" href="<?php echo esc_url( $tool['page_url'] ); ?>" style="display: flex; flex-direction: column; padding: 32px; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border-top: 4px solid #008080; text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s;">
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
        /* Three tools per row on desktop (set inline); collapse straight to one
           column below tablet width (a 2-col stage would orphan the 3rd card).
           !important is needed to beat the inline grid-template-columns. */
        @media (max-width: 900px) {
            .tools-card-grid { grid-template-columns: 1fr !important; }
        }
    </style>

    <!-- SAVE-RESULTS CTA -->
    <section class="section-padding tools-cta-section" style="background: linear-gradient(135deg, #008080, #006666);">
        <div class="container" style="text-align: center; color: white;">
            <h2 style="color: white; margin-bottom: 16px;">Want to save your results?</h2>
            <p class="max-600" style="font-size: 18px; margin: 0 auto 32px; color: rgba(255,255,255,0.92);">
                Free registration unlocks result history, personalised meal plans, VANCE-ai, and printable handouts — all in your private dashboard.
            </p>
            <div class="hero-actions" style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                <a href="/register/" class="btn btn-primary" style="background: white; color: #008080; border: none;">Register Free</a>
                <a href="/login/" class="btn btn-outline" style="border-color: rgba(255,255,255,0.4); color: white;">Already have an account? Sign in</a>
            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>
