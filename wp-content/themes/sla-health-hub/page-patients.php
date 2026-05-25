<?php
/**
 * Template Name: Patients
 *
 * Thin wrapper: hero is page-local, the three lower sections (Benefits, Tools,
 * CTA) are emitted via shared render functions in inc/cross-page-sections.php
 * so the same markup can be re-used on the homepage via the Section Order
 * Customizer control. Customizer keys (vance_pat_*) are unchanged.
 */
get_header(); ?>

<main id="main-content">

    <!-- HERO SECTION (page-local — the homepage has its own hero) -->
    <?php
    $hero_bg      = vance_get_theme_mod( 'vance_pat_hero_bg', get_template_directory_uri() . '/assets/img/patient_hero.png' );
    $hero_tag     = vance_get_theme_mod( 'vance_pat_hero_tag',   'Patient Portal' );
    $hero_title   = vance_get_theme_mod( 'vance_pat_hero_title', 'Empowering Your <span class="highlight">Wellness Journey</span>' );
    $hero_desc    = vance_get_theme_mod( 'vance_pat_hero_desc',  'More than just a news site—a truly useful platform providing the highest quality clinical information, innovative tools, and expert opinions to help you explore and manage your gastro healthcare concerns.' );
    $hero_overlay = max( 0, min( 100, absint( vance_get_theme_mod( 'vance_pat_hero_overlay', 70 ) ) ) ) / 100;
    $hero_overlay_bottom = min( 1, $hero_overlay + 0.15 );
    ?>
    <section class="hero patient-hero" style="padding: 80px 0 120px; display: flex; align-items: center; background: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $hero_overlay ); ?>), rgba(10,25,41,<?php echo esc_attr( $hero_overlay_bottom ); ?>)), url('<?php echo esc_url( $hero_bg ); ?>') no-repeat center center; background-size: cover;">
        <div class="container">
            <div class="hero-content">
                <span class="tag-label"><?php echo esc_html( $hero_tag ); ?></span>
                <h1><?php echo wp_kses_post( $hero_title ); ?></h1>
                <p><?php echo esc_html( $hero_desc ); ?></p>
                <div class="hero-actions" style="margin-top: 24px;">
                    <a href="#benefits" class="btn btn-primary">Explore Resources</a>
                    <a href="#subscribe" class="btn btn-outline">Subscribe Free</a>
                </div>
            </div>
        </div>
    </section>

    <?php
    // Shared sections — see inc/cross-page-sections.php for the markup. These
    // function calls keep this template thin and let the same blocks appear on
    // the homepage if the admin selects them in the Section Order control.
    vance_render_section_patients_benefits();
    vance_render_section_patients_tools();
    vance_render_section_patients_cta();
    ?>

</main>

<?php get_footer(); ?>
