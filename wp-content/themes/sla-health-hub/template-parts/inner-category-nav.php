<?php
/**
 * Inner Page Category Navigation (Mini Cards)
 */

/*
 * Two modes:
 *  - Default (global): the site's most-used top-level categories. Respects the
 *    "Show Inner Page Horizontal Nav" Customizer toggle.
 *  - Sub-category mode: pass $args['subcategories_of'] = <parent term id> via
 *    get_template_part( ..., null, array( 'subcategories_of' => $id ) ) to list
 *    ONLY that parent's populated child categories. Used on the Gastro Living
 *    category page. Being an explicit per-page request, it renders regardless of
 *    the global toggle.
 */
$vance_subcat_parent = isset( $args['subcategories_of'] ) ? (int) $args['subcategories_of'] : 0;

if ( $vance_subcat_parent > 0 ) {
    // Sub-category mode — this parent's children that have posts, alphabetical.
    $cats = get_categories( array(
        'parent'     => $vance_subcat_parent,
        'orderby'    => 'name',
        'order'      => 'ASC',
        'hide_empty' => true,
    ) );
    // Fit the sub-categories on a single row (clamped 1..12).
    $col_count = min( max( 1, count( $cats ) ), 12 );
} else {
    // Global mode — respect the on/off toggle.
    if ( ! vance_get_theme_mod( 'vance_show_inner_nav', true ) ) {
        return;
    }

    // Get Uncategorized ID to exclude
    $uncat = get_category_by_slug( 'uncategorized' );
    $exclude_ids = array();
    if ( $uncat ) {
        $exclude_ids[] = $uncat->term_id;
    }

    // Stored values may be 0 (an empty Customizer submit sanitised by absint).
    // Clamp to sane defaults so we never emit repeat(0, 1fr) — invalid CSS that
    // collapses the horizontal bar into a single vertical column.
    $total_items = (int) vance_get_theme_mod( 'vance_inner_nav_total_items', 8 );
    if ( $total_items < 1 ) { $total_items = 8; }
    $col_count = (int) vance_get_theme_mod( 'vance_inner_nav_cards_per_row', 8 );
    if ( $col_count < 1 ) { $col_count = 8; }

    $cats = get_categories( array(
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => $total_items,
        'hide_empty' => true,
        'exclude'    => $exclude_ids,
    ) );
}

// Nothing to show → bail (avoids an empty bar overlapping the hero).
if ( empty( $cats ) ) {
    return;
}
?>
<style>
    .orange-icon { filter: brightness(0) saturate(100%) invert(35%) sepia(97%) saturate(2283%) hue-rotate(3deg) brightness(101%) contrast(106%); }
</style>
<?php if ( $vance_subcat_parent > 0 ) : ?>
<style>
    /* Sub-category nav cards jump to sections on THIS page. Smooth-scroll and
       offset the target heading so it clears the sticky header. */
    html { scroll-behavior: smooth; }
    .va-subcat-title { scroll-margin-top: 110px; }

    /* Glassy hover for the sub-category buttons (base look is set inline).
       Higher specificity than the shared .cat-mini-card:hover so it wins. */
    .inner-cat-nav .cat-mini-card--glass:hover {
        background: rgba(255,255,255,0.85) !important;
        border-color: rgba(0,128,128,0.45) !important;
        box-shadow: 0 12px 30px rgba(15,23,42,0.14) !important;
        transform: translateY(-2px);
    }
</style>
<?php endif; ?>
<?php
// Sub-category mode sits BELOW the hero as a full-width, subtly tinted gradient
// band (gives the glass buttons something to frost against). Global mode keeps
// the historical -50px overlap into the hero.
$wrapper_style = ( $vance_subcat_parent > 0 )
    ? 'position: relative; z-index: 1; margin: 0 0 40px; padding: 30px 0; background: linear-gradient(180deg, #e9f3f3 0%, #f6fafa 100%); border-bottom: 1px solid rgba(15,23,42,0.06);'
    : 'position: relative; z-index: 20; margin-top: -50px; margin-bottom: 40px; pointer-events: none;';
?>
<div class="inner-cat-nav-wrapper" style="<?php echo $wrapper_style; ?>">
    <div class="container">
        <?php if ( $vance_subcat_parent > 0 ) : ?>
        <div class="inner-cat-nav-intro" style="text-align: center; margin-bottom: 18px;">
            <div style="font-family: 'Outfit', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e; margin-bottom: 5px;">Explore this category</div>
            <div style="font-size: 14px; color: #475569; line-height: 1.4;">Choose a topic below to jump straight to that section &darr;</div>
        </div>
        <?php endif; ?>
        <!-- Grid layout for desktop, Scroll for mobile -->
        <div class="inner-cat-nav" style="pointer-events: auto;">
            
            <?php foreach ( $cats as $cat ) :
                $is_active = ( is_category() && get_queried_object_id() === $cat->term_id );
                // Sub-category mode: link to the matching section on THIS page
                // (each group's <h2> carries id="va-subcat-<term_id>"). Global mode
                // keeps linking to the category archive.
                $card_href = ( $vance_subcat_parent > 0 )
                    ? '#va-subcat-' . (int) $cat->term_id
                    : esc_url( get_category_link( $cat->term_id ) );
                $icon = vance_get_theme_mod("vance_cat_card_icon_{$cat->term_id}", '');
                
                if ( $vance_subcat_parent > 0 ) {
                    // Glassy button: frosted translucent fill, blur, soft shadow,
                    // rounded. No icon in this mode.
                    $card_style = "display: flex; align-items: center; justify-content: center; gap: 6px; padding: 14px 18px; text-decoration: none; white-space: nowrap; overflow: hidden; width: 100%; border-radius: 0; background: rgba(255,255,255,0.55); -webkit-backdrop-filter: blur(14px) saturate(140%); backdrop-filter: blur(14px) saturate(140%); border: 1px solid rgba(15,23,42,0.10); box-shadow: 0 6px 20px rgba(15,23,42,0.06), inset 0 1px 0 rgba(255,255,255,0.65); transition: all 0.2s;";
                    $text_style = "font-size: 13px; font-weight: 600; color: #0f172a; margin: 0; line-height: 1.2; overflow: hidden; text-overflow: ellipsis;";
                } else {
                    $card_style = "display: flex; align-items: center; justify-content: center; gap: 6px; background: #F8FAFC; border: 1px solid #e2e8f0; border-radius: 0; padding: 12px; text-decoration: none; transition: all 0.2s; white-space: nowrap; box-shadow: 0 1px 2px rgba(0,0,0,0.05); width: 100%; overflow: hidden;";
                    $text_style = "font-size: 12px; font-weight: 600; color: #334155; margin: 0; line-height: 1.2; overflow: hidden; text-overflow: ellipsis;";
                }

                $icon_container_style = "width: 20px; height: 20px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 0;";
                $icon_img_style = "width: 12px; height: 12px; object-fit: contain;";

                if ( $is_active ) {
                    if ( $vance_subcat_parent > 0 ) {
                        $card_style .= " border-color: rgba(0,128,128,0.6); background: rgba(0,128,128,0.10);";
                        $text_style  = "font-size: 13px; font-weight: 700; color: #0f766e; margin: 0; line-height: 1.2;";
                    } else {
                        $card_style .= " border-color: #008080; background: #def4f4;";
                        $text_style  = "font-size: 12px; font-weight: 700; color: #c2410c;";
                    }
                }
            ?>
                <a href="<?php echo $card_href; ?>" class="cat-mini-card <?php echo $vance_subcat_parent > 0 ? 'cat-mini-card--glass ' : ''; ?><?php echo $is_active ? 'active' : ''; ?>" style="<?php echo $card_style; ?>" title="<?php echo esc_attr( $cat->name ); ?>">
                    <?php if ( $vance_subcat_parent <= 0 ) : ?>
                        <?php $cat_icon = $icon ?: vance_get_category_icon_url($cat->name); ?>
                        <div style="<?php echo $icon_container_style; ?>">
                            <?php if ($cat_icon): ?>
                                <img src="<?php echo esc_url($cat_icon); ?>" alt="" class="orange-icon" style="<?php echo $icon_img_style; ?>">
                            <?php else: ?>
                                <div style="font-size: 12px;">📁</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <span style="<?php echo $text_style; ?>"><?php echo esc_html( $cat->name ); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
/* Mobile: Horizontal Scroll */
.inner-cat-nav {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 2px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}
.inner-cat-nav::-webkit-scrollbar { display: none; }
.cat-mini-card { flex: 0 0 auto; width: auto !important; }

/* Desktop: Configurable Grid */
@media (min-width: 992px) {
    .inner-cat-nav {
        display: grid;
        grid-template-columns: repeat(<?php echo esc_attr($col_count); ?>, 1fr);
        gap: 10px;
        overflow-x: visible;
        justify-content: center;
    }
    .cat-mini-card { 
        width: 100% !important;
        flex: 1;
    }
}

.cat-mini-card:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1) !important;
    border-color: #008080 !important;
}
</style>
