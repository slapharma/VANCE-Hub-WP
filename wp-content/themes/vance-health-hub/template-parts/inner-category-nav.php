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

    /* The sub-category buttons had their own frosted hover here. They now share
       .cat-mini-card:hover with every other one -- white fill, same border, no
       lift. */
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
                
                // One button treatment for both modes: transparent by default,
                // white on hover, a #008080 border that does not change between
                // those two states, and #008080 label text. The sub-category mode
                // used to be a frosted-glass button -- the translucent fill, the
                // backdrop blur and the lift shadow all go with the rest of the
                // decoration, because a blurred backdrop is not transparent.
                //
                // `transition: none` rather than just omitting one: main.css sets
                // `a { transition: color 0.2s }` sitewide, which these anchors
                // inherit. It animates nothing here now that the label is #008080
                // in both states, but leaving it would mean the buttons still
                // carry a transition, so it is turned off explicitly.
                //
                // No font-family in here on purpose. The anchor inherits the site
                // face (--font-main, Inter) from body, which is already what it
                // renders in; restating it would just be a second place to keep
                // in step.
                $card_style = "display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; white-space: nowrap; overflow: hidden; width: 100%; border-radius: var(--radius-control, 6px); background: transparent; border: 1px solid #008080; transition: none;";
                $card_style .= ( $vance_subcat_parent > 0 ) ? " padding: 14px 18px;" : " padding: 12px;";

                $text_size  = ( $vance_subcat_parent > 0 ) ? "13px" : "12px";
                $text_style = "font-size: {$text_size}; font-weight: 600; color: #008080; margin: 0; line-height: 1.2; overflow: hidden; text-overflow: ellipsis;";

                $icon_container_style = "width: 20px; height: 20px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: var(--radius-control, 6px);";
                $icon_img_style = "width: 12px; height: 12px; object-fit: contain;";

                // The current category still has to be identifiable, so it keeps
                // a fill -- but a tint of the same teal, and the same #008080
                // border as the rest rather than one of its own. Its old label
                // colours (#0f766e here, #c2410c on the global nav) would have
                // been the only non-teal text left once everything else went
                // #008080.
                if ( $is_active ) {
                    $card_style .= " background: rgba(0,128,128,0.10);";
                    $text_style  = str_replace( 'font-weight: 600', 'font-weight: 700', $text_style );
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

/* Hover changes the fill and nothing else: white in, border stays #008080,
   no lift and no shadow. !important because the base look is set inline. */
.cat-mini-card:hover {
    background: #ffffff !important;
    border-color: #008080 !important;
}
</style>
