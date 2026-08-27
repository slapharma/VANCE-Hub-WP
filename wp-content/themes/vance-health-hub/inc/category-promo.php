<?php
/**
 * Category promo block — a configurable glass promo card rendered on category
 * archive pages (archive.php + template-parts/subcategory-grouped-archive.php),
 * just below the inner-category nav.
 *
 * Per-category settings are registered in functions.php (Customizer →
 * Content → Category Promo Blocks) and keyed by term id so they survive
 * category renames. The CTA can open one of the interactive tools in the
 * unified glass tool modal (inc/tool-modal.php) via data-vance-tool-open, or
 * link to any custom URL. Reads use core get_theme_mod() — these keys are all
 * new (no legacy sla_* equivalents), so the saved value is authoritative.
 */

/**
 * The layout choices offered per category.
 *
 * All five share one markup skeleton — the differences are grid direction,
 * whether the image is a column or a background, and where the copy sits — so
 * a category can switch between them with no content change. 'image_left' is
 * the historical rendering and stays the default.
 */
function vance_cat_promo_layout_choices() {
	return array(
		'image_left'  => __( 'Image left, text right',            'vance-health-hub' ),
		'image_right' => __( 'Image right, text left',            'vance-health-hub' ),
		'stacked'     => __( 'Image on top, text below',          'vance-health-hub' ),
		'banner'      => __( 'Full-width banner (text on image)', 'vance-health-hub' ),
		'text'        => __( 'Text only (compact strip)',         'vance-health-hub' ),
	);
}

if ( ! function_exists( 'vance_render_category_promo' ) ) :
function vance_render_category_promo( $term_id ) {
    $term_id = (int) $term_id;
    if ( ! $term_id ) {
        return;
    }
    if ( ! get_theme_mod( "vance_cat_promo_show_{$term_id}", false ) ) {
        return;
    }

    $eyebrow = trim( (string) get_theme_mod( "vance_cat_promo_eyebrow_{$term_id}", '' ) );
    $heading = trim( (string) get_theme_mod( "vance_cat_promo_heading_{$term_id}", '' ) );
    $text    = trim( (string) get_theme_mod( "vance_cat_promo_text_{$term_id}", '' ) );
    $image   = trim( (string) get_theme_mod( "vance_cat_promo_image_{$term_id}", '' ) );
    $cta     = trim( (string) get_theme_mod( "vance_cat_promo_cta_label_{$term_id}", 'Explore' ) );
    $tool    = trim( (string) get_theme_mod( "vance_cat_promo_tool_{$term_id}", '' ) );
    $link    = trim( (string) get_theme_mod( "vance_cat_promo_link_{$term_id}", '' ) );

    $layout = (string) get_theme_mod( "vance_cat_promo_layout_{$term_id}", 'image_left' );
    if ( ! array_key_exists( $layout, vance_cat_promo_layout_choices() ) ) {
        $layout = 'image_left';
    }

    // Nothing meaningful to show — bail rather than render an empty card.
    // Checked BEFORE the text-only layout drops the image, so a category set
    // to "Text only" with an image but no copy still bails instead of
    // rendering an empty strip.
    if ( '' === $heading && '' === $text && '' === $image ) {
        return;
    }

    // "Text only" ignores whatever image is saved rather than deleting it, so
    // switching back to an image layout restores the previous picture.
    if ( 'text' === $layout ) {
        $image = '';
    }

    // Resolve the CTA target. A tool selection opens the unified modal; anything
    // else falls back to the custom link.
    $tool_urls = array(
        'ibd-recipes'             => home_url( '/gastro-meal-planner/' ),
        'malnutrition-calculator' => home_url( '/malnutrition-calculator/' ),
        'healthcare-quiz'         => home_url( '/gastro-health-survey/' ),
    );
    $href      = '';
    $data_attr = '';
    if ( isset( $tool_urls[ $tool ] ) ) {
        $href      = $tool_urls[ $tool ];
        $data_attr = ' data-vance-tool-open="' . esc_attr( $tool ) . '"';
    } elseif ( '' !== $link ) {
        $href = $link;
    }
    $has_cta = ( '' !== $cta && '' !== $href );

    // The banner layout paints the image onto the card itself under a dark
    // scrim, rather than giving it a column of its own — so the copy sits over
    // the photo. The scrim is deliberately heavy on the text side (0.92) so
    // white type clears 4.5:1 against any photograph, and thins out towards the
    // right where there is no text.
    $inner_classes = 'vance-cat-promo__inner vance-glass vance-glass--interactive vance-cat-promo__inner--' . $layout;
    if ( '' !== $image ) {
        $inner_classes .= ' has-image';
    }
    $inner_style = '';
    if ( 'banner' === $layout && '' !== $image ) {
        $inner_style = "background-image: linear-gradient(90deg, rgba(10,25,41,0.92) 0%, rgba(10,25,41,0.78) 55%, rgba(10,25,41,0.45) 100%), url('" . esc_url( $image ) . "');";
    }
    ?>
    <section class="vance-cat-promo" aria-label="<?php echo esc_attr( $heading ? $heading : 'Featured' ); ?>">
        <div class="container">
            <div class="<?php echo esc_attr( $inner_classes ); ?>"<?php echo $inner_style ? ' style="' . esc_attr( $inner_style ) . '"' : ''; ?>>
                <?php if ( $image && 'banner' !== $layout ) : ?>
                    <div class="vance-cat-promo__media" style="background-image:url('<?php echo esc_url( $image ); ?>');" role="img" aria-label="<?php echo esc_attr( $heading ); ?>"></div>
                <?php endif; ?>
                <div class="vance-cat-promo__body">
                    <?php if ( $eyebrow ) : ?><span class="vance-cat-promo__eyebrow"><?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
                    <?php if ( $heading ) : ?><h2 class="vance-cat-promo__title"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
                    <?php if ( $text ) : ?><p class="vance-cat-promo__text"><?php echo esc_html( $text ); ?></p><?php endif; ?>
                    <?php if ( $has_cta ) : ?>
                        <a class="vance-btn-inverted" href="<?php echo esc_url( $href ); ?>"<?php echo $data_attr; // phpcs:ignore ?>><?php echo esc_html( $cta ); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}
endif;
