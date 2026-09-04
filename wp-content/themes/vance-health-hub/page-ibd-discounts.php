<?php
/**
 * Template Name: IBD Discounts
 *
 * Hub page for "IBD Discounts & Freebies" (docs/DISCOUNTS_TOOL_PLAN.md).
 * Hero fork copied verbatim from page-patient-downloads.php's own — a
 * classic dark hero, with the light Spotlight hero available as a toggle
 * once inc/page-hero-spotlight.php's 'discounts' entry exists (it does, as
 * of this file).
 *
 * To activate: create a Page titled "IBD Discounts & Freebies", slug
 * `ibd-discounts`, and choose "IBD Discounts" as the template (Page
 * Attributes). Then `wp vance discounts import tools/discounts-seed.json`
 * and `wp vance discounts check` (plan §5's "Manual, on the live site" list).
 *
 * Customizer panel: Appearance → Customize → Page - IBD Discounts.
 */
get_header();
?>

<main id="main-content" class="ibd-discounts-page">

	<?php
	if ( function_exists( 'vance_page_hero_spotlight_active' ) && vance_page_hero_spotlight_active( 'discounts' ) ) :
		vance_render_page_hero_spotlight( 'discounts' );
	else :
		$hero_bg      = vance_get_theme_mod( 'vance_discounts_hero_bg', get_template_directory_uri() . '/assets/img/education_hero.png' );
		$hero_tag     = vance_get_theme_mod( 'vance_discounts_hero_tag', 'Discounts & Freebies' );
		$hero_title   = vance_get_theme_mod( 'vance_discounts_hero_title', 'Discounts and freebies for <span class="highlight">life with IBD</span>' );
		$hero_desc    = vance_get_theme_mod( 'vance_discounts_hero_desc', 'Every UK scheme worth knowing about — toilet access, days out, travel, tax and benefits — checked against the provider\'s own page, not copied from a leaflet.' );
		$hero_overlay = max( 0, min( 100, absint( vance_get_theme_mod( 'vance_discounts_hero_overlay', 70 ) ) ) ) / 100;
		$hero_overlay_bottom = min( 1, $hero_overlay + 0.15 );
		?>
		<section class="hero ibd-discounts-hero" style="padding: 72px 0 116px; min-height: 332px; display: flex; align-items: center; background: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $hero_overlay ); ?>), rgba(10,25,41,<?php echo esc_attr( $hero_overlay_bottom ); ?>)), url('<?php echo esc_url( $hero_bg ); ?>') no-repeat center center; background-size: cover;">
			<div class="container">
				<div class="hero-content">
					<span class="tag-label"><?php echo esc_html( $hero_tag ); ?></span>
					<h1><?php echo wp_kses_post( $hero_title ); ?></h1>
					<p><?php echo esc_html( $hero_desc ); ?></p>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php get_template_part( 'template-parts/discount-directory' ); ?>

</main>

<?php get_footer(); ?>
