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

    // Category list comes from vance_inner_nav_categories() so the article
    // sidebar's "Explore" panel, which replaced this bar on single posts, is
    // guaranteed to offer the same set.
    $cats = vance_inner_nav_categories();

    // Stored values may be 0 (an empty Customizer submit sanitised by absint).
    // Clamp to a sane default so we never emit repeat(0, 1fr) — invalid CSS
    // that collapses the horizontal bar into a single vertical column.
    $col_count = (int) vance_get_theme_mod( 'vance_inner_nav_cards_per_row', 8 );
    if ( $col_count < 1 ) { $col_count = 8; }
}

// Nothing to show → bail (avoids an empty bar overlapping the hero).
if ( empty( $cats ) ) {
    return;
}
?>
<?php
// The category icons that used to sit in these buttons are gone. They were a
// 20px chip plus a 12px glyph inside a button whose only job is a category
// name, and at eight across they spent ~26px of the ~110px available on
// decoration. vance_get_category_icon_url() is untouched -- front-page.php
// still uses it.
?>
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
    // Global mode used to be pulled up 50px so the bar straddled the bottom of
    // the hero, which is why it also needed z-index: 20 to sit above it and
    // pointer-events: none so the overlapping strip did not swallow clicks
    // meant for the hero. It now sits fully below the hero, so all three go: a
    // plain block in the flow with a normal gap above and below.
    : 'position: relative; margin: 30px 0 40px;';
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
        <div class="inner-cat-nav">
            
            <?php foreach ( $cats as $cat ) :
                $is_active = ( is_category() && get_queried_object_id() === $cat->term_id );
                // Sub-category mode: link to the matching section on THIS page
                // (each group's <h2> carries id="va-subcat-<term_id>"). Global mode
                // keeps linking to the category archive.
                $card_href = ( $vance_subcat_parent > 0 )
                    ? '#va-subcat-' . (int) $cat->term_id
                    : esc_url( get_category_link( $cat->term_id ) );
                
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
                $card_style = "display: flex; align-items: center; justify-content: center; text-decoration: none; white-space: nowrap; overflow: hidden; width: 100%; border-radius: var(--radius-control, 6px); background: transparent; border: 1px solid #008080; transition: none;";
                // Padding carries most of the height: two 10px lines are only ~25px,
                // so the 24px of vertical padding was the larger half of the old 55px.
                $card_style .= ( $vance_subcat_parent > 0 ) ? " padding: 10px 14px;" : " padding: 8px;";

                $text_size  = ( $vance_subcat_parent > 0 ) ? "11px" : "10px";
                $text_style = "font-size: {$text_size}; font-weight: 600; color: #008080; margin: 0; line-height: 1.2; overflow: hidden; text-overflow: ellipsis;";
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
.cat-mini-card {
    flex: 0 0 auto;
    width: auto !important;
    /* 44px touch minimum. Also the desktop height: 55px was the old size and
       this is the 20% reduction, which happens to land exactly on the floor. */
    min-height: 44px;
}

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

    /* Let the label wrap on the desktop grid. At eight across in a 1160px
       container each button gets ~84px of text, and seven of the eight category
       names need more -- "Understanding Your Condition" needs 175px, so it
       truncated to about half. Nothing fits eight across on one line at a
       legible size, so the label wraps onto two or three lines instead and the
       grid stretches every button to the tallest, keeping the row even.

       !important because the base look is set inline, and scoped to this
       breakpoint because below it the bar is a horizontal scroll row of chips,
       where single-line labels are correct. */
    .inner-cat-nav .cat-mini-card {
        white-space: normal !important;
        align-items: center;
        min-height: 44px;
    }
    .inner-cat-nav .cat-mini-card span {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: clip !important;
        text-align: center;
    }
}

/* Hover changes the fill and nothing else: white in, border stays #008080,
   no lift and no shadow. !important because the base look is set inline. */
.cat-mini-card:hover {
    background: #ffffff !important;
    border-color: #008080 !important;
}
</style>
