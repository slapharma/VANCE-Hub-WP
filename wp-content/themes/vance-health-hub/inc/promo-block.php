<?php
/**
 * Promo Block — the wide two-column promo band (heading, body, CTA, image).
 *
 * Lifted out of front-page.php's `case 'promo'` so the same markup can serve
 * more than one page, in the same shape as inc/prime-block.php: a renderer that
 * takes a fully-resolved values array, plus one thin wrapper per instance that
 * owns the theme-mod reads.
 *
 * Instances:
 *   - Homepage      vance_promo_*    (the original keys, so every saved value
 *                                     carries over untouched)
 *   - Knowledgebase vance_kbpromo_*  (independent copy for page-knowledgebase)
 *
 * The block's CSS moved with it, from front-page.php's inline <style> into
 * assets/css/main.css, because the Knowledgebase template does not load
 * front-page.php's styles. Nothing else in the theme defines a `.promo-*`
 * selector, and neither mobile sheet touches one, so the move cannot change
 * which rule wins.
 *
 * @package vance-health-hub
 * @since   2026-08-28
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Where the block may sit on the Knowledgebase page.
 *
 * Mirrors vance_prime_block_placement_choices() in inc/prime-block.php, but the
 * slots are this template's, not the archive's.
 */
function vance_kb_page_placement_choices() {
	return array(
		'below_hero'   => __( 'Below the hero, above the intro', 'vance-health-hub' ),
		'below_intro'  => __( 'Below the intro, above the category blocks', 'vance-health-hub' ),
		'above_footer' => __( 'Above the footer (end of the page)', 'vance-health-hub' ),
	);
}

/**
 * Resolve one instance's settings from its prefix.
 *
 * @param string $prefix Setting prefix, e.g. 'vance_promo_'.
 * @return array Values for vance_render_promo_block().
 */
function vance_promo_block_vals_for_prefix( $prefix ) {
	return array(
		'heading'      => vance_get_theme_mod( $prefix . 'heading', 'Experience the Hub' ),
		'text'         => vance_get_theme_mod( $prefix . 'text', '' ),
		'image'        => vance_get_theme_mod( $prefix . 'image' ),
		'bg'           => vance_get_theme_mod( $prefix . 'bg_color', '#F8FAFC' ),
		'text_color'   => vance_get_theme_mod( $prefix . 'text_color', '#0F172A' ),
		'button_text'  => vance_get_theme_mod( $prefix . 'button_text', 'Get Started Now' ),
		'button_link'  => vance_get_theme_mod( $prefix . 'button_link', wp_registration_url() ),
		'width'        => vance_get_theme_mod( $prefix . 'width', 'container' ),
		'layout'       => vance_get_theme_mod( $prefix . 'layout', 'right' ),
		'border_on'    => vance_get_theme_mod( $prefix . 'border_enable', false ),
		'border_width' => vance_get_theme_mod( $prefix . 'border_width', 1 ),
		'border_style' => vance_get_theme_mod( $prefix . 'border_style', 'solid' ),
		'border_color' => vance_get_theme_mod( $prefix . 'border_color', '#e2e8f0' ),
		'border_scope' => vance_get_theme_mod( $prefix . 'border_scope', 'container' ),
		'container_bg' => vance_get_theme_mod( $prefix . 'container_bg_color', '' ),
	);
}

/**
 * Render one Promo Block instance from a resolved values array.
 *
 * Deliberately does not read theme mods itself — the wrappers below own that,
 * which is what lets the same markup serve both instances.
 *
 * @param array $vals Output of vance_promo_block_vals_for_prefix().
 */
function vance_render_promo_block( array $vals ) {
	$promo_h     = $vals['heading'];
	$promo_t     = $vals['text'];
	$promo_img   = $vals['image'];
	$promo_bg    = $vals['bg'];
	$promo_txt_c = $vals['text_color'];
	$promo_btn_t = $vals['button_text'];
	$promo_btn_l = $vals['button_link'];
	$promo_w     = $vals['width'];
	$promo_l     = $vals['layout'];

	// Optional border. Scope mirrors the Width control: 'full' outlines the
	// full-bleed band, 'container' the inner card.
	$promo_border_decl = '';
	if ( $vals['border_on'] ) {
		$promo_bw = absint( $vals['border_width'] );
		$promo_bs = $vals['border_style'];
		if ( ! in_array( $promo_bs, array( 'solid', 'dashed', 'dotted', 'double' ), true ) ) { $promo_bs = 'solid'; }
		$promo_bc = $vals['border_color'];
		if ( $promo_bw > 0 ) {
			$promo_border_decl = ' border: ' . $promo_bw . 'px ' . esc_attr( $promo_bs ) . ' ' . esc_attr( $promo_bc ) . ';';
		}
	}
	$promo_border_scope = $vals['border_scope'];
	$promo_border_full  = ( $promo_border_scope === 'full' ) ? $promo_border_decl : '';
	$promo_border_inner = ( $promo_border_scope !== 'full' ) ? $promo_border_decl : '';

	// Inner container styling: border (when scoped here) plus the optional
	// container background colour. Blank colour = transparent, so the section
	// band shows through as before.
	$promo_inner_style  = $promo_border_inner;
	$promo_container_bg = $vals['container_bg'];
	if ( $promo_container_bg !== '' ) {
		$promo_inner_style .= ' background-color: ' . esc_attr( $promo_container_bg ) . ';';
	}
	// Omit the attribute entirely when nothing is set, so the markup is
	// unchanged for sites using neither.
	$promo_inner_style = trim( $promo_inner_style );
	$promo_inner_attr  = $promo_inner_style !== '' ? ' style="' . $promo_inner_style . '"' : '';
	?>
    <section class="promo-block-section" style="background-color: <?php echo esc_attr($promo_bg); ?>; color: <?php echo esc_attr($promo_txt_c); ?>;<?php echo $promo_border_full; ?>">
        <div class="<?php echo $promo_w === 'container' ? 'container' : 'container-fluid'; ?>">
            <div class="promo-container layout-<?php echo esc_attr($promo_l); ?>"<?php echo $promo_inner_attr; ?>>
                <div class="promo-content">
                    <h2 style="font-family: 'Outfit', sans-serif; font-size: 38px; font-weight: 800; margin-bottom: 24px; color: inherit;"><?php echo esc_html($promo_h); ?></h2>
                    <div style="font-size: 18px; line-height: 1.6; opacity: 0.9; margin-bottom: 32px;"><?php echo wpautop(esc_html($promo_t)); ?></div>
                    <a href="<?php echo esc_url($promo_btn_l); ?>" class="btn btn-primary" style="background: var(--primary-color); color: white; padding: 14px 40px; font-weight: 800;"><?php echo esc_html($promo_btn_t); ?></a>
                </div>
                <?php if ($promo_img) : ?>
                <div class="promo-image-box">
                    <img src="<?php echo esc_url($promo_img); ?>" alt="Promo">
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
	<?php
}

/**
 * Homepage instance. Called from front-page.php's `case 'promo'`; visibility is
 * the vance_promo_show checkbox exactly as before, and position comes from
 * Homepage → Section Order.
 */
function vance_render_promo_home() {
	if ( ! vance_get_theme_mod( 'vance_promo_show', false ) ) {
		return;
	}
	vance_render_promo_block( vance_promo_block_vals_for_prefix( 'vance_promo_' ) );
}

/**
 * Knowledgebase instance. The template calls this once per placement slot; it
 * renders in the slot matching its "Position on the page" setting and bails in
 * the others — the same pattern vance_render_prime_block_categories() uses.
 *
 * @param string $slot Which call site this is; see vance_kb_page_placement_choices().
 */
function vance_render_promo_knowledgebase( $slot = 'below_intro' ) {
	if ( ! vance_get_theme_mod( 'vance_kbpromo_show', false ) ) {
		return;
	}
	$placement = vance_get_theme_mod( 'vance_kbpromo_placement', 'below_intro' );
	if ( ! array_key_exists( $placement, vance_kb_page_placement_choices() ) ) {
		$placement = 'below_intro';
	}
	if ( $placement !== $slot ) {
		return;
	}
	vance_render_promo_block( vance_promo_block_vals_for_prefix( 'vance_kbpromo_' ) );
}
