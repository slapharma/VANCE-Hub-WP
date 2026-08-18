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

    // Nothing meaningful to show — bail rather than render an empty card.
    if ( '' === $heading && '' === $text && '' === $image ) {
        return;
    }

    // Resolve the CTA target. A tool selection opens the unified modal; anything
    // else falls back to the custom link.
    $tool_urls = array(
        'ibd-recipes'             => home_url( '/ibd-recipies/' ),
        'malnutrition-calculator' => home_url( '/malnutrition-calculator/' ),
        'healthcare-quiz'         => home_url( '/healthcare-quiz/' ),
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
    ?>
    <section class="vance-cat-promo" aria-label="<?php echo esc_attr( $heading ? $heading : 'Featured' ); ?>">
        <div class="container">
            <div class="vance-cat-promo__inner vance-glass vance-glass--interactive<?php echo $image ? ' has-image' : ''; ?>">
                <?php if ( $image ) : ?>
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
