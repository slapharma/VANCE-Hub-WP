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

    <!-- HERO -->
    <?php
    $hero_bg      = vance_get_theme_mod( 'vance_tools_hero_bg', get_template_directory_uri() . '/assets/img/hcp_hero.png' );
    $hero_tag     = vance_get_theme_mod( 'vance_tools_hero_tag',   'Free Tools' );
    $hero_title   = vance_get_theme_mod( 'vance_tools_hero_title', 'Tools &amp; <span class="highlight">Resources</span>' );
    $hero_desc    = vance_get_theme_mod( 'vance_tools_hero_desc',  'Clinical calculators built on peer-reviewed evidence — free to use, no signup required. Save your results and build a meal plan by registering for a free account.' );
    $hero_overlay = max( 0, min( 100, absint( vance_get_theme_mod( 'vance_tools_hero_overlay', 70 ) ) ) ) / 100;
    $hero_overlay_bottom = min( 1, $hero_overlay + 0.15 );
    ?>
    <section class="hero tools-hero" style="padding: 80px 0 120px; display: flex; align-items: center; background: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $hero_overlay ); ?>), rgba(10,25,41,<?php echo esc_attr( $hero_overlay_bottom ); ?>)), url('<?php echo esc_url( $hero_bg ); ?>') no-repeat center center; background-size: cover;">
        <div class="container">
            <div class="hero-content">
                <span class="tag-label"><?php echo esc_html( $hero_tag ); ?></span>
                <h1><?php echo wp_kses_post( $hero_title ); ?></h1>
                <p><?php echo esc_html( $hero_desc ); ?></p>
                <div class="hero-actions" style="margin-top: 24px;">
                    <a href="#tools-grid" class="btn btn-primary">Browse the Tools</a>
                    <a href="/register/" class="btn btn-outline">Save Your Results</a>
                </div>
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

    <!-- TOOLS GRID -->
    <?php
    $tpl_dir = get_template_directory_uri();
    $tools = array(
        array(
            'slug'     => 'omega-3-calculator',
            'name'     => 'Omega-3 Calculator',
            'tag'      => 'Nutrition',
            'desc'     => 'Calculate your personalised EPA + DHA target based on body weight, dietary intake, and clinical guidance. Built on the latest gastroenterology evidence.',
            'colors'   => array( '#008080', '#006666', '#ffffff' ),
            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12c2.5-3.5 6.5-5 9-5s6.5 1.5 9 5c-2.5 3.5-6.5 5-9 5s-6.5-1.5-9-5zm12 0a3 3 0 11-6 0 3 3 0 016 0z"/>',
            'requires_login' => false,
        ),
        array(
            'slug'     => 'malnutrition-calculator',
            'name'     => 'Malnutrition Calculator',
            'tag'      => 'IBD Screening',
            'desc'     => 'Clinically-grounded 11-step malnutrition risk screener for IBD patients. Combines MUST, IBD-NST, and GLIM criteria into a single, actionable score.',
            'colors'   => array( '#78bfbf', '#5fa3a3', '#ffffff' ),
            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
            'requires_login' => false,
        ),
        array(
            'slug'     => 'blood-test',
            'name'     => 'Blood Test Analyser',
            'tag'      => 'Lab Results',
            'desc'     => 'Drop in your blood panel results and get plain-language analysis flagging anything outside reference ranges. Designed to help you prepare for your next clinic appointment.',
            'colors'   => array( '#aedbdb', '#88c5c5', '#008080' ),
            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
            'requires_login' => false,
        ),
        array(
            'slug'     => 'ibd-recipes',
            'name'     => 'IBD Recipes & Meal Planner',
            'tag'      => 'Meal Planning',
            'desc'     => 'Browse EPA-rich, gut-friendly recipes with full nutrition data. Build weekly meal plans freely — saving plans to your account requires a free login.',
            'colors'   => array( '#def4f4', '#aedbdb', '#008080' ),
            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2h-4a2 2 0 01-2-2v-4a2 2 0 00-2-2H10a2 2 0 00-2 2v4a2 2 0 01-2 2H2V9z" transform="translate(0,-1)"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14h8M8 11h8" />',
            'requires_login' => true,
            'login_note'     => 'Browse and build freely — login required only to save plans.',
        ),
    );
    ?>
    <section id="tools-grid" class="section-padding tools-grid-section" style="background: var(--accent-color);">
        <div class="container">
            <div class="tools-card-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 28px;">
                <?php foreach ( $tools as $idx => $tool ) :
                    $launch_url = $tpl_dir . '/assets/tools/' . $tool['slug'] . '/index.html';
                    // Append public-mode flag so the React bundle can suppress save-only features for IBD recipes.
                    if ( ! empty( $tool['requires_login'] ) ) {
                        $launch_url = add_query_arg( 'public', '1', $launch_url );
                    }
                ?>
                <article class="tool-card tool-card--<?php echo esc_attr( $tool['slug'] ); ?>" style="display: flex; flex-direction: column; padding: 32px; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border-top: 4px solid <?php echo esc_attr( $tool['colors'][0] ); ?>;">
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

                    <?php if ( ! empty( $tool['login_note'] ) ) : ?>
                        <p class="tool-card__note" style="font-size: 12px; color: var(--primary-color); margin: 0 0 16px 0; padding: 8px 12px; background: var(--accent-color); border-radius: 8px;">
                            <?php echo esc_html( $tool['login_note'] ); ?>
                        </p>
                    <?php endif; ?>

                    <div class="tool-card__actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="<?php echo esc_url( $launch_url ); ?>" target="_blank" rel="noopener" class="btn btn-primary tool-card__launch" style="flex: 1; min-width: 140px; text-align: center;">Launch Tool →</a>
                        <button type="button" class="btn btn-outline tool-card__embed-toggle" data-tool-slug="<?php echo esc_attr( $tool['slug'] ); ?>" style="flex: 0 0 auto;">
                            Use Inline
                        </button>
                    </div>

                    <div class="tool-card__embed" data-tool-embed="<?php echo esc_attr( $tool['slug'] ); ?>" style="display: none; margin-top: 20px; border: 1px solid var(--accent-color); border-radius: 12px; overflow: hidden; background: #fafafa;">
                        <iframe class="tool-card__iframe"
                                data-tool-src="<?php echo esc_url( $launch_url ); ?>"
                                src=""
                                loading="lazy"
                                title="<?php echo esc_attr( $tool['name'] ); ?>"
                                style="width: 100%; height: 600px; border: 0; display: block;"></iframe>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

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

    <!-- Lazy-iframe toggle script (no jQuery dependency) -->
    <script>
    (function () {
        document.querySelectorAll('.tool-card__embed-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var slug = btn.getAttribute('data-tool-slug');
                var wrap = document.querySelector('[data-tool-embed="' + slug + '"]');
                if (!wrap) return;
                var iframe = wrap.querySelector('iframe');
                var isHidden = wrap.style.display === 'none' || wrap.style.display === '';
                if (isHidden) {
                    if (iframe && !iframe.src) {
                        iframe.src = iframe.getAttribute('data-tool-src');
                    }
                    wrap.style.display = 'block';
                    btn.textContent = 'Hide';
                } else {
                    wrap.style.display = 'none';
                    btn.textContent = 'Use Inline';
                }
            });
        });
    })();
    </script>

</main>

<?php get_footer(); ?>
