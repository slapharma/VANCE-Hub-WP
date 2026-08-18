<?php
/**
 * Template Name: Healthcare Professionals
 *
 * Thin wrapper: hero is page-local, the three lower sections (Resources,
 * Collaborate, CTA) are emitted via shared render functions in
 * inc/cross-page-sections.php so the same markup can be re-used on the
 * homepage via the Section Order Customizer control. Customizer keys
 * (vance_hcp_*) are unchanged.
 */
get_header(); ?>

<main id="main-content">

    <!-- HERO SECTION (page-local — the homepage has its own hero) -->
    <?php
    $hero_bg      = vance_get_theme_mod( 'vance_hcp_hero_bg', get_template_directory_uri() . '/assets/img/hcp_hero.png' );
    $hero_tag     = vance_get_theme_mod( 'vance_hcp_hero_tag', 'Professional Portal' );
    $hero_title   = vance_get_theme_mod( 'vance_hcp_hero_title', 'Advancing <span class="highlight">Clinical Practice</span> Through Nutrition' );
    $hero_desc    = vance_get_theme_mod( 'vance_hcp_hero_desc', 'Evidence-based resources, clinical protocols, and CME opportunities designed for gastroenterologists, dietitians, GPs, and allied health professionals.' );
    $hero_overlay = max( 0, min( 100, absint( vance_get_theme_mod( 'vance_hcp_hero_overlay', 75 ) ) ) ) / 100;
    $hero_overlay_bottom = min( 1, $hero_overlay + 0.15 );
    ?>
    <section class="hero hcp-hero" style="padding: 80px 0 120px; display: flex; align-items: center; background: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $hero_overlay ); ?>), rgba(10,25,41,<?php echo esc_attr( $hero_overlay_bottom ); ?>)), url('<?php echo esc_url( $hero_bg ); ?>') no-repeat center center; background-size: cover;">
        <div class="container">
            <div class="hero-content">
                <span class="tag-label"><?php echo esc_html( $hero_tag ); ?></span>
                <h1><?php echo wp_kses_post( $hero_title ); ?></h1>
                <p><?php echo esc_html( $hero_desc ); ?></p>
                <div class="hero-actions" style="margin-top: 24px;">
                    <a href="#resources" class="btn btn-primary">Access Resources</a>
                    <a href="#register" class="btn btn-outline">Register Free</a>
                </div>
            </div>
        </div>
    </section>

    <?php
    // Shared sections — see inc/cross-page-sections.php for the markup.
    vance_render_section_hcp_resources();
    vance_render_section_hcp_collaborate();
    vance_render_section_hcp_cta();
    ?>

</main>

<?php get_footer(); ?>
