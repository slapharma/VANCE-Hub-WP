<?php
/**
 * Front Page Template
 * Mosaic Dashboard Layout
 */

get_header(); 
?>

<style>
/* --- MOSAIC DASHBOARD STYLES --- */
:root {
    --primary-color: #008080;
    --secondary-color: #0A1929;
    --accent-color: #F3F4F6;
    --text-main: #1F2937;
    --text-light: #6B7280;
    /* --radius-md / --radius-lg deliberately not re-declared here: they are
       defined by the radius scale in main.css, and shadowing them locally is
       what previously squared every card on this page. */
}

body {
    background-color: #F8FAFC;
}

/* Section Headers */
.section-label {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 32px;
    /* margin-top: 60px; REMOVED to fit section padding */
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 16px;
    justify-content: space-between; /* Adjusted for view all link */
}

.section-label-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-label h2 { 
    margin: 0; 
    font-size: 24px; 
    font-weight: 700;
    color: var(--secondary-color);
    font-family: 'Outfit', sans-serif;
}

.color-bar { width: 6px; height: 24px; border-radius: var(--radius-control, 6px); }

/* BENTO GRID (News Style) */
.bento-grid-news {
    display: grid;
    grid-template-columns: 2fr 1fr;
    grid-template-rows: repeat(2, 200px);
    gap: 24px;
}

/* "--grow" variant: used by the Pathway / Featured-Tools "Latest Content"
   blocks so the side column can hold the featured + up to 5 more articles.
   The fixed 2-row height is relaxed to auto so the grid grows to fit the
   side list, and the featured cell stretches to match. The Knowledge Base
   category rows keep the plain .bento-grid-news (fixed 2-row featured + 2). */
.bento-grid-news.bento-grid-news--grow {
    /* Image column widened further (66%/34%, up from 60%/40%) so the
       widen reads clearly instead of disappearing as a few extra px. */
    grid-template-columns: 2fr 1fr;
    grid-template-rows: auto;
    align-items: stretch;
    /* Small breathing-room gap — flush (0px) read as the image and list
       overlapping/glued together with no separation. The shared border
       (moved here from .latest-list-box) still frames both as one section;
       this just keeps them from touching directly. */
    gap: 20px;
    border: 1.5px solid #e2e8f0;
}
.bento-grid-news.bento-grid-news--grow .bento-cell-featured {
    /* Fills the full 2fr column width. No aspect-ratio here: with the column
       widened to 2fr, forcing a 1:1 ratio shrank the box back down to a
       460px square that no longer filled its column, leaving a dead gap of
       page background between the photo and the article list. Capped by
       max-height only, so width always fills the column and height is
       whatever the row (article list) content naturally settles at. */
    width: 100%;
    max-height: 460px;
}

/* Latest Content side list — the "+4" articles collected into ONE box,
   each row showing just category + title (no excerpt). Rows flex to divide
   the box height evenly so it lines up with the featured cell. */
.latest-list-box {
    grid-row: 1 / -1;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    /* No border here — the shared border lives on .bento-grid-news--grow so
       the image and this list read as one bordered section, not two boxes
       pushed together. */
    overflow: hidden;
}
.latest-list-item {
    /* grow to fill the box (so rows line up with the featured cell) but never
       shrink below the content height — basis:auto keeps room for a 2-line
       title so it can't be clipped by the row edge. */
    flex: 1 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    /* Title pulled hard left (no left inset). Right padding narrowed again
       (50px -> 20px, 30px more) to move the thumbnail closer to the row's
       right edge without touching the title's position. The divider/border
       itself is unaffected since it's on the row box, not the padding, so it
       still runs the full width flush to the featured image. */
    padding: 10px 20px 10px 0;
    text-decoration: none;
    border-bottom: 1px solid #2f4f6f;
    transition: background 0.2s ease;
}
.latest-list-item:last-child { border-bottom: none; }
.latest-list-item:hover { background: #f8fafc; }
.latest-list-text {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 4px;
    flex: 1;
    min-width: 0;
}
/* Postage-stamp thumbnail pinned to the right edge of each row. */
.latest-list-thumb {
    width: 48px;
    height: 48px;
    object-fit: cover;
    flex-shrink: 0;
}
.latest-list-cat {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--primary-color); /* fallback; per-post hero-overlay colour set inline */
    line-height: 1.2;
}
.latest-list-title {
    margin: 0;
    font-family: 'Outfit', sans-serif;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.4;
    color: #0f172a;
    transition: color 0.2s ease;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.latest-list-item:hover .latest-list-title { color: var(--primary-color); }
@media (max-width: 992px) {
    /* On mobile the box sits below the featured cell full-width. */
    .latest-list-box { grid-row: auto; }
}

.bento-cell-featured {
    grid-row: 1 / -1; /* Spans both rows */
    position: relative;
    border-radius: 0;
    overflow: hidden;
    background: #0A1929;
    color: white;
    display: flex;
    align-items: flex-end;
    text-decoration: none;
}

.bento-cell-featured img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.6;
    transition: transform 0.4s;
}

.bento-cell-featured:hover img {
    transform: scale(1.05);
}

.bento-content-overlay {
    position: relative;
    z-index: 2;
    padding: 40px;
    background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
    width: 100%;
}

.bento-featured-excerpt {
    margin: 0 0 14px;
    font-size: 14px;
    line-height: 1.55;
    color: rgba(255,255,255,0.85);
    max-width: 560px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* card-meta-footer (main.css) recoloured for the dark featured overlay. */
.bento-content-overlay .card-meta-footer {
    border-top-color: rgba(255,255,255,0.25);
    color: rgba(255,255,255,0.78);
}
.bento-content-overlay .card-meta-footer .card-meta-item + .card-meta-item {
    border-left-color: rgba(255,255,255,0.25);
}

.tag {
    background: var(--primary-color);
    color: white;
    padding: 4px 12px;
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 700;
    border-radius: var(--radius-control, 6px);
    display: inline-block;
    margin-bottom: 12px;
}

.bento-cell-side {
    background: white;
    border-radius: 0;
    padding: 24px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
    height: 100%;
    overflow: hidden;
}

.bento-cell-side:hover {
    border-color: var(--primary-color);
    transform: translateX(4px);
}

.bento-cell-side .heading-small {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.35;
}

.bento-cell-side .text-body {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* REVIEWS ASYMMETRIC GRID */
.bento-grid-reviews {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr;
    gap: 24px;
}

.review-card-wide {
    background: white;
    border-radius: var(--radius-surface, 14px);
    overflow: hidden;
    border: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.review-card-wide .review-img {
    height: 240px;
    background: #e2e8f0;
    position: relative;
    background-size: cover;
    background-position: center;
}

.review-card-standard {
    background: white;
    border-radius: var(--radius-surface, 14px);
    overflow: hidden;
    border: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.review-card-standard .review-img {
    height: 160px;
    background: #e2e8f0;
    background-size: cover;
    background-position: center;
}

/* EXPERT OPINIONS POSTERS */
.bento-grid-opinions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.opinion-card {
    background: white;
    border-radius: var(--radius-surface, 14px);
    padding: 32px;
    text-align: center;
    border: 1px solid #e5e7eb;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.opinion-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 6px;
    background: var(--primary-color);
}

.author-avatar {
    width: 80px;
    height: 80px;
    border-radius: var(--radius-control, 6px);
    background: #f8fafc;
    margin: 0 auto 20px;
    border: 4px solid #f8fafc;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    object-fit: cover;
}

/* TYPOGRAPHY UTILS */
.heading-medium { margin: 0 0 12px 0; font-size: 18px; line-height: 1.4; color: var(--secondary-color); font-weight: 700; }
.heading-small { margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: var(--secondary-color); }
.text-body { margin: 0 0 16px 0; font-size: 14px; color: var(--text-light); line-height: 1.5; }
.meta-text { font-size: 12px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }

    /* RESPONSIVE */
    @media (max-width: 992px) {
        .bento-grid-news { grid-template-columns: 1fr; grid-template-rows: auto; }
        .bento-cell-featured { min-height: 400px; margin-bottom: 24px; }
        .bento-grid-reviews { grid-template-columns: 1fr; }
        .bento-grid-opinions { grid-template-columns: 1fr; }
    }

    /* The Promo Block's styles moved to assets/css/main.css when the block
       was shared with the Knowledgebase template, which does not load this
       inline sheet. Nothing else in the theme defines a .promo-* selector. */

    /* The .pathway-tiles-section overlap rules that used to live here went with
       the retired 'pathway' (Who Am I? tiles) section — nothing emits that
       class any more. */


</style>

    <?php
    // Section order is now driven by the sortable Customizer control:
    // Appearance -> Customize -> Vance Theme -> Homepage -> Section Order.
    // Stored as a comma-separated string of CHECKED section IDs in display
    // order. The previous "force-add testimonials/pathway_content" fallbacks
    // have been removed now that the admin has explicit per-section checkboxes.
    $section_order = vance_get_theme_mod('vance_homepage_section_order', 'hero,prime-block-home-1,promo,cats,tool-widget-content-filters,tool-widget-vance-ai,join,kb,testimonials');
    $sections      = array_filter( array_map( 'trim', explode( ',', $section_order ) ) );
    // Migration: the legacy combined 'discovery' block (chip filters + Ask AI
    // input + reading-level toggles) has been split into two focused modal-
    // opening tool widgets: 'tool-widget-content-filters' and
    // 'tool-widget-vance-ai'. For admins whose saved section_order still
    // includes 'discovery', substitute it in-place so the visual intent is
    // preserved automatically. The original case 'discovery': block below is
    // kept as a fallback (someone might want the combined block back), but
    // the registry hides it from the Section Order Customizer control.
    if ( in_array( 'discovery', $sections, true ) ) {
        $rewritten = array();
        foreach ( $sections as $sid ) {
            if ( $sid === 'discovery' ) {
                $rewritten[] = 'tool-widget-content-filters';
                $rewritten[] = 'tool-widget-vance-ai';
            } else {
                $rewritten[] = $sid;
            }
        }
        $sections = $rewritten;
    }

    // Migration (2026-05-26): the two split tool widgets have been merged
    // back into one banner row, 'tool-widgets-row'. Substitute either
    // legacy ID with the merged ID; if both are present, drop the second
    // occurrence so we don't render the merged row twice.
    $legacy_tw_ids = array( 'tool-widget-content-filters', 'tool-widget-vance-ai' );
    if ( count( array_intersect( $sections, $legacy_tw_ids ) ) > 0 ) {
        $rewritten = array();
        $injected  = false;
        foreach ( $sections as $sid ) {
            if ( in_array( $sid, $legacy_tw_ids, true ) ) {
                if ( ! $injected ) {
                    $rewritten[] = 'tool-widgets-row';
                    $injected    = true;
                }
                // otherwise: skip (deduped)
            } else {
                $rewritten[] = $sid;
            }
        }
        $sections = $rewritten;
    }

    // One-time migration (2026-05-26): the legacy combined 'kb' case rendered
    // both the mini-hero AND the category content blocks together. They're
    // now split so admins can insert other blocks between them. For sites
    // upgrading, inject 'kb-content' right after 'kb' ONCE and persist back
    // to the saved order so the admin's subsequent toggles in Section Order
    // are respected. Flag is a simple option that we set after the first run.
    if ( ! get_option( 'vance_kb_content_split_migrated' ) ) {
        if ( in_array( 'kb', $sections, true ) && ! in_array( 'kb-content', $sections, true ) ) {
            $rewritten = array();
            foreach ( $sections as $sid ) {
                $rewritten[] = $sid;
                if ( $sid === 'kb' ) {
                    $rewritten[] = 'kb-content';
                }
            }
            $sections = $rewritten;
            set_theme_mod( 'vance_homepage_section_order', implode( ',', $sections ) );
        }
        update_option( 'vance_kb_content_split_migrated', 1, false );
    }

    // Migration (2026-08-21): 'pathway' (the Who Am I? tiles) has been retired,
    // and 'pathway_content' became the registry-driven 'prime-block-home-1'.
    // Rewrite the saved order in place so the admin's chosen POSITION is
    // preserved, and drop 'pathway' entirely.
    //
    // This one has to PERSIST, unlike the read-time substitutions above:
    // neither retired ID is in vance_get_available_sections() any more, and
    // vance_sanitize_sortable_sections() drops unregistered IDs on save. Left
    // as a read-time-only rewrite, the first time an admin saved anything in
    // the Customizer the saved order would silently lose 'pathway_content'
    // and Prime Block Home 1 would vanish from the homepage. Writing the
    // migrated order back once — behind a one-shot option flag, exactly like
    // the kb → kb-content migration above — closes that window.
    $vance_pathway_dirty = in_array( 'pathway_content', $sections, true ) || in_array( 'pathway', $sections, true );
    if ( $vance_pathway_dirty ) {
        $sections = vance_migrate_retired_pathway_sections( $sections );
    }
    if ( ! get_option( 'vance_prime_block_migrated' ) ) {
        if ( $vance_pathway_dirty ) {
            set_theme_mod( 'vance_homepage_section_order', implode( ',', $sections ) );
        }
        update_option( 'vance_prime_block_migrated', 1, false );
    }

    // Content Widget visibility (2026-08-21): fill in any widget that is
    // switched on and configured but was never added to Section Order. See
    // vance_append_enabled_content_widgets() for why the test is narrower than
    // "show is true". Computed fresh per request and never persisted, so
    // toggling the checkbox takes effect immediately in both directions.
    $sections = vance_append_enabled_content_widgets( $sections );

    // Section seams: a zero-height marker between consecutive sections that
    // blurs 40px either side of the join, so one section's background colour
    // (or image, or gradient) fades into the next instead of meeting it at a
    // hard edge. See "HOMEPAGE SECTION SEAMS" in main.css. Emitted BEFORE each
    // section rather than after, so the last section never trails one.
    $vance_seam_after = false;

    foreach ($sections as $section_id) {
        $section_id = trim($section_id);
        if ( $vance_seam_after ) {
            echo '<div class="vance-hp-seam" aria-hidden="true"></div>';
        }
        $vance_seam_after = true;
        switch ($section_id) {
            case 'hero':
                // Two hero designs share this slot, chosen in Customize →
                // Homepage → Hero:
                //   spotlight — the light, search-led hero (default).
                //   carousel  — the previous dark hero; renders as a single
                //               static slide until a second slide is enabled.
                //               Slide 1 still reads the original vance_hero_*
                //               keys, so switching back restores the old hero
                //               exactly as it was configured.
                if (vance_get_theme_mod('vance_hero_style', 'spotlight') === 'carousel') {
                    vance_render_hero_carousel();
                } else {
                    vance_render_hero_spotlight();
                }
                break;

            case 'promo':
                // Markup and settings live in inc/promo-block.php so the
                // Knowledgebase template can render the same block from its own
                // vance_kbpromo_* keys. The visibility check moved in with it.
                vance_render_promo_home();
                break;

            case 'cats':
                // Layout settings
                $cards_per_row = vance_get_theme_mod('vance_homepage_cards_per_row', 6);
                $justification = vance_get_theme_mod('vance_homepage_card_alignment', 'center');
                $cats_section_bg = vance_get_theme_mod('vance_cats_section_bg', '#ffffff');
                $all_cats = get_categories(array('hide_empty' => false));
                $cards = array();
                
                foreach ($all_cats as $cat) {
                    // Authoritative read: core get_theme_mod (NOT vance_get_theme_mod)
                    // so a stale legacy sla_cat_card_show_* value can't override the
                    // tick. Default false => only explicitly-ticked categories show.
                    if ( get_theme_mod( "vance_cat_card_show_{$cat->term_id}", false ) ) {
                        $cards[] = array(
                            'cat' => $cat,
                            'priority' => vance_get_theme_mod("vance_cat_card_priority_{$cat->term_id}", 10),
                            'icon' => vance_get_theme_mod("vance_cat_card_icon_{$cat->term_id}", ''),
                        );
                    }
                }
                
                if (!empty($cards)) :
                    usort($cards, function($a, $b) {
                        return $a['priority'] - $b['priority'];
                    });
                    
                    $grid_cols = "repeat($cards_per_row, 1fr)";
                    $justify = ($justification === 'left') ? 'start' : (($justification === 'right') ? 'end' : 'center');
                ?>
    <!-- CATEGORY CARDS SECTION -->
    <section class="category-cards-section" style="padding: 20px 0 40px; position: relative; background-color: <?php echo esc_attr($cats_section_bg); ?>;">
        <div class="container">
            <div style="display: grid; grid-template-columns: <?php echo $grid_cols; ?>; gap: 15px; justify-items: <?php echo $justify; ?>;">
                <?php foreach ($cards as $item): 
                    $cat = $item['cat'];
                ?>
                <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>" class="vance-category-card" style="text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 12px; background: #0A1929; border-radius: var(--radius-surface, 14px); padding: 24px 12px; transition: all 0.3s; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #1E293B; width: 100%; max-width: 160px;">
                    <?php 
                    $cat_icon = $item['icon'] ?: vance_get_category_icon_url($cat->name);
                    ?>
                        <div style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.1); border-radius: var(--radius-control, 6px);">
                            <?php if ($cat_icon): ?>
                                <img src="<?php echo esc_url($cat_icon); ?>" alt="" class="orange-icon" style="width: 24px; height: 24px; object-fit: contain; filter: brightness(0) invert(1);">
                            <?php else: ?>
                                <div style="font-size: 20px;">📁</div>
                            <?php endif; ?>
                        </div>
                    <h3 style="font-size: 13px; font-weight: 700; color: white; text-align: center; margin: 0; line-height: 1.2;"><?php echo esc_html($cat->name); ?></h3>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
                <?php
                endif;
                break;

            case 'discovery':
                $disc_title = vance_get_theme_mod('vance_discovery_title_text', 'CONTENT DISCOVERY SUITE');
                $disc_sub = vance_get_theme_mod('vance_discovery_subtitle_text', 'Use the controls below to customise and filter IBD research, clinical news, and resources relevant to you.');
                $disc_size = vance_get_theme_mod('vance_discovery_title_size', 32);
                $disc_color = vance_get_theme_mod('vance_discovery_title_color', '#0F172A');
                $disc_align = vance_get_theme_mod('vance_discovery_title_align', 'left');
                ?>
    <?php 
    $chip_radius = 0;
    $panel_radius = 0;
    $border_color = vance_get_theme_mod('vance_discovery_border_color', '#008080');
    $section_bg = vance_get_theme_mod('vance_discovery_section_bg', 'linear-gradient(160deg, #0A1929 0%, #0F2440 55%, #0A1929 100%)');
    $panel_bg = vance_get_theme_mod('vance_discovery_panel_bg', 'rgba(255,255,255,0.04)');
    
    // Customisation for labels
    $disc_field_title_size  = (int) vance_get_theme_mod('vance_discovery_field_title_size', 10);
    $disc_field_title_color = vance_get_theme_mod('vance_discovery_field_title_color', 'rgba(255,255,255,0.4)');
    $disc_item_label_size   = (int) vance_get_theme_mod('vance_discovery_item_label_size', 13);
    $disc_item_label_color  = vance_get_theme_mod('vance_discovery_item_label_color', 'rgba(255,255,255,0.75)');

    // Customisation for Ask AI
    $askai_text_size  = (int) vance_get_theme_mod('vance_askai_text_size', 13);
    $askai_text_color = vance_get_theme_mod('vance_askai_text_color', '#ffffff');

    // ---- Extra discovery customizer mods (added round 11) ----
    // Subtitle colour for the H2-description paragraph.
    $disc_subtitle_color   = vance_get_theme_mod('vance_discovery_subtitle_color', 'rgba(255,255,255,0.55)');
    // Panel header labels ("Discovery Filters" / "AI Clinical Intelligence").
    $disc_filters_text     = vance_get_theme_mod('vance_discovery_filters_label_text', 'Discovery Filters');
    $disc_filters_size     = (int) vance_get_theme_mod('vance_discovery_filters_label_size', 12);
    $disc_filters_color    = vance_get_theme_mod('vance_discovery_filters_label_color', '') ?: $border_color;
    $disc_ai_text          = vance_get_theme_mod('vance_discovery_ai_label_text', 'VANCE-Ai');
    $disc_ai_size          = (int) vance_get_theme_mod('vance_discovery_ai_label_size', 12);
    $disc_ai_color         = vance_get_theme_mod('vance_discovery_ai_label_color', '') ?: '#ffffff';
    // Toggle on/off colours.
    $disc_toggle_off_bg    = vance_get_theme_mod('vance_discovery_toggle_off_bg',  'rgba(255,255,255,0.10)');
    $disc_toggle_off_dot   = vance_get_theme_mod('vance_discovery_toggle_off_dot', 'rgba(255,255,255,0.60)');
    $disc_toggle_on_bg     = vance_get_theme_mod('vance_discovery_toggle_on_bg',   '#008080');
    $disc_toggle_on_dot    = vance_get_theme_mod('vance_discovery_toggle_on_dot',  '#ffffff');
    // Chip colours.
    $disc_chip_off_bg      = vance_get_theme_mod('vance_discovery_chip_off_bg',     'rgba(255,255,255,0.06)');
    $disc_chip_off_border  = vance_get_theme_mod('vance_discovery_chip_off_border', 'rgba(255,255,255,0.12)');
    $disc_chip_off_text    = vance_get_theme_mod('vance_discovery_chip_off_text',   'rgba(255,255,255,0.75)');
    $disc_chip_on_bg       = vance_get_theme_mod('vance_discovery_chip_on_bg',      'rgba(0,128,128,0.20)');
    $disc_chip_on_border   = vance_get_theme_mod('vance_discovery_chip_on_border',  '#008080');
    $disc_chip_on_text     = vance_get_theme_mod('vance_discovery_chip_on_text',    '#ffffff');
    // Ask AI input.
    $askai_input_bg        = vance_get_theme_mod('vance_discovery_askai_input_bg',     'rgba(255,255,255,0.06)');
    $askai_input_color     = vance_get_theme_mod('vance_discovery_askai_input_color',  '#ffffff');
    $askai_input_border    = vance_get_theme_mod('vance_discovery_askai_input_border', 'rgba(255,255,255,0.12)');

    // Action button colours (solid, no gradient). Blank = keep existing class default.
    $btn_go_bg       = vance_get_theme_mod('vance_discovery_btn_go_bg',       '');
    $btn_go_color    = vance_get_theme_mod('vance_discovery_btn_go_color',    '#ffffff');
    $btn_clear_bg    = vance_get_theme_mod('vance_discovery_btn_clear_bg',    '');
    $btn_clear_color = vance_get_theme_mod('vance_discovery_btn_clear_color', '#ffffff');
    $btn_send_bg     = vance_get_theme_mod('vance_discovery_btn_send_bg',     '');
    $btn_send_color  = vance_get_theme_mod('vance_discovery_btn_send_color',  '#ffffff');

    // Status text — "AI (Online)" and "Content Filters (Active)".
    $status_ai_size       = (int) vance_get_theme_mod('vance_discovery_status_ai_size',       10);
    $status_ai_color      = vance_get_theme_mod('vance_discovery_status_ai_color',            '#22C55E');
    $status_filters_size  = (int) vance_get_theme_mod('vance_discovery_status_filters_size',  10);
    $status_filters_color = vance_get_theme_mod('vance_discovery_status_filters_color',       'rgba(255,255,255,0.5)');

    $btn_style = function($bg, $color) {
        if (!$bg) return '';
        return 'background:' . esc_attr($bg) . ' !important;'
             . 'background-image:none !important;'
             . 'border-color:' . esc_attr($bg) . ' !important;'
             . 'color:' . esc_attr($color) . ' !important;';
    };
    ?>
    <section id="discovery-suite" class="discovery-suite-section" style="padding: 60px 0 60px; background: <?php echo esc_attr($section_bg); ?>; position: relative; overflow: hidden;">
        <!-- Background shimmer effects -->
        <div style="position: absolute; top: -80px; right: -80px; width: 400px; height: 400px; background: radial-gradient(circle, rgba(0,128,128,0.15) 0%, transparent 70%); pointer-events: none;"></div>
        <div style="position: absolute; bottom: -60px; left: -60px; width: 350px; height: 350px; background: radial-gradient(circle, rgba(34,197,94,0.08) 0%, transparent 70%); pointer-events: none;"></div>

        <div class="container" style="max-width: 1120px; margin: 0 auto; position: relative; z-index: 1;">

            <header style="margin-bottom: 36px; text-align: <?php echo esc_attr($disc_align); ?>;">
                <h2 style="font-family: 'Outfit', sans-serif; font-size: <?php echo esc_attr($disc_size); ?>px; font-weight: 900; margin: 0 0 10px; color: <?php echo esc_attr($disc_color); ?>; letter-spacing: -0.5px; line-height: 1.15;"><?php echo esc_html($disc_title); ?></h2>
                <p style="color: <?php echo esc_attr($disc_subtitle_color); ?>; font-size: 15px; margin: 0; max-width: 680px; line-height: 1.6; <?php echo $disc_align === 'center' ? 'margin: 0 auto;' : ''; ?>"><?php echo esc_html($disc_sub); ?></p>
            </header>

            <div class="discovery-panel" style="background: <?php echo esc_attr($panel_bg); ?>; border-radius: var(--radius-surface, 14px); border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 30px 80px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.08); overflow: hidden; backdrop-filter: blur(20px);">

                <!-- EXPLORE CONTENT TAB -->
                <div class="tab-content active" id="tab-explore">
                    <div class="explore-layout" style="display: flex; min-height: 540px;">
                        
                        <!-- LEFT: Filters -->
                        <div class="explore-filters" style="flex: 1 1 55%; padding: 32px 36px; border-right: 1px solid rgba(255,255,255,0.07); display: flex; flex-direction: column;">

                            <!-- LEFT PANEL HEADER (matches right side) -->
                            <div class="panel-header-bar" style="display: flex; align-items: center; justify-content: space-between; padding: 0 0 14px 0; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, <?php echo esc_attr($border_color); ?>, #cc4400); border-radius: var(--radius-control, 6px); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <svg viewBox="0 0 24 24" style="width: 16px; height: 16px;" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                                    </div>
                                    <div>
                                        <div style="font-size: <?php echo (int) $disc_filters_size; ?>px; font-weight: 700; color: <?php echo esc_attr($disc_filters_color); ?>; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;"><?php echo esc_html($disc_filters_text); ?></div>
                                        <div style="font-size: <?php echo (int) $status_filters_size; ?>px; color: <?php echo esc_attr($status_filters_color); ?>; margin-top: 1px;"><span style="display:inline-block; width:6px; height:6px; background:#22c55e; border-radius:50%; margin-right:4px; vertical-align:middle;"></span>Active</div>
                                    </div>
                                </div>
                            </div>

                            <form action="<?php echo home_url('/discovery-results/'); ?>" method="GET" id="discovery-form" style="display: flex; flex-direction: column; flex: 1;">
                            <div style="flex: 1; overflow-y: auto; padding-right: 4px;">

                                <?php
                                /* Section / Topic / Condition / Written-for, built from terms that
                                   carry posts. Shared with the homepage Content Filters widget
                                   (inc/tool-widgets.php) so the two cannot drift apart. */
                                vance_discovery_render_facets();
                                ?>


                                <!-- KEYWORD SEARCH -->
                                <div class="filter-group" style="margin-bottom:0;">
                                    <div class="keyword-row">
                                        <input type="text" name="keyword" class="keyword-input" placeholder="Keyword Search (Optional)">
                                    </div>
                                </div>

                            </div><!-- /scrollable filter area -->

                            <!-- ACTION ROW — pinned to bottom, aligns with Send button -->
                            <div class="action-row" style="padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 14px;">
                                    <button type="submit" class="btn-go" style="<?php echo $btn_style($btn_go_bg, $btn_go_color); ?>">GO</button>
                                    <button type="reset" class="btn-text" onclick="setTimeout(()=>window.location.reload(), 100)" style="<?php echo $btn_style($btn_clear_bg, $btn_clear_color); ?>">Clear</button>
                                </div>
                            </form>
                        </div>

                        <!-- RIGHT: ASK AI -->
                        <div class="explore-preview ask-vance-ai-side" style="flex: 1 1 45%; padding: 28px 32px; border-left: 1px solid rgba(255,255,255,0.07); background: rgba(0,0,0,0.12); display: flex; flex-direction: column;">
                            <div class="chat-agent-bar panel-header-bar" style="display: flex; align-items: center; justify-content: space-between; padding: 0 0 14px 0; background: transparent; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 16px;">
                                <div class="agent-left" style="display: flex; align-items: center; gap: 10px;">
                                    <div class="agent-avatar" style="width: 32px; height: 32px; flex-shrink: 0;">
                                        <svg viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                                            <rect x="2" y="3" width="20" height="14" rx="2" /><line x1="8" y1="21" x2="16" y2="21" /><line x1="12" y1="17" x2="12" y2="21" /><circle cx="9" cy="10" r="1.5" fill="white" stroke="none" /><circle cx="15" cy="10" r="1.5" fill="white" stroke="none" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="agent-name" style="font-size: <?php echo (int) $disc_ai_size; ?>px; font-weight: 700; color: <?php echo esc_attr($disc_ai_color); ?>; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;"><?php echo esc_html($disc_ai_text); ?></div>
                                        <div class="agent-status" style="font-size: <?php echo (int) $status_ai_size; ?>px; color: <?php echo esc_attr($status_ai_color); ?>; margin-top: 1px;"><span class="status-dot"></span> Online</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Launcher for the shared VANCE-Ai chat (assets/js/vance-askai.js).
                                 One chat surface site-wide, so a conversation started here
                                 continues on any article and saves to the user's dashboard. -->
                            <div class="askai-teaser" style="flex: 1; display: flex; flex-direction: column; justify-content: center; gap: 18px; padding: 6px 0 4px;">
                                <p style="margin: 0; font-size: 14px; line-height: 1.7; color: rgba(255,255,255,0.82);">Ask a question in plain English and get an answer built only from the articles published on this hub, with a link to every source used.</p>
                                <div>
                                    <button type="button" class="chat-send" data-vance-askai-open style="padding: 12px 22px; font-size: 13px; cursor: pointer; <?php echo $btn_style($btn_send_bg, $btn_send_color); ?>">Open VANCE-Ai</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ASK AI TAB -->
                <div class="tab-content" id="tab-ask">
                    <div class="chat-layout">
                        <div class="chat-agent-bar">
                            <div class="agent-left">
                                <div class="agent-avatar">
                                    <svg viewBox="0 0 24 24">
                                        <rect x="2" y="3" width="20" height="14" rx="2" /><line x1="8" y1="21" x2="16" y2="21" /><line x1="12" y1="17" x2="12" y2="21" /><circle cx="9" cy="10" r="1.5" fill="white" stroke="none" /><circle cx="15" cy="10" r="1.5" fill="white" stroke="none" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="agent-name">VANCE-Ai</div>
                                    <div class="agent-status"><span class="status-dot"></span> Online</div>
                                </div>
                            </div>
                        </div>

                        <!-- Launcher for the shared VANCE-Ai chat. The old inline chat here
                             duplicated the element IDs used by the Explore panel above, so
                             only one of the two was ever wired up. -->
                        <div class="askai-teaser" style="padding: 24px 0 8px; display: flex; flex-direction: column; gap: 20px;">
                            <p style="margin: 0; font-size: 15px; line-height: 1.7; color: rgba(255,255,255,0.82); max-width: 620px;">Ask anything about IBD, gut health or clinical nutrition. Answers are drawn only from articles published on the Vance Medical Hub, and every answer links to the articles it used.</p>
                            <div>
                                <button type="button" class="chat-send" data-vance-askai-open style="cursor: pointer;">Open VANCE-Ai</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        @keyframes pulse-dot { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.7); } }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* --- Filter Groups --- */
        .filter-group { margin-bottom: 22px; }
        .filter-label { 
            font-family: 'Outfit', sans-serif; 
            font-size: <?php echo $disc_field_title_size; ?>px; 
            font-weight: 800; 
            color: <?php echo esc_attr($disc_field_title_color); ?>; 
            margin-bottom: 12px; 
            text-transform: uppercase; 
            letter-spacing: 1.5px; 
        }

        /* --- Reading Level Toggles --- */
        .toggle-row { display: flex; gap: 16px; flex-wrap: wrap; }
        .toggle-item { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .toggle-switch {
            width: 40px; height: 22px;
            background: <?php echo esc_attr($disc_toggle_off_bg); ?>;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: var(--radius-pill, 999px);
            position: relative;
            transition: 0.3s;
            flex-shrink: 0;
        }
        .toggle-switch::after {
            content: ''; position: absolute;
            top: 2px; left: 2px;
            width: 16px; height: 16px;
            background: <?php echo esc_attr($disc_toggle_off_dot); ?>;
            border-radius: var(--radius-pill, 999px);
            box-shadow: 0 1px 4px rgba(0,0,0,0.3);
            transition: 0.3s;
        }
        .toggle-item.active .toggle-switch { background: <?php echo esc_attr($disc_toggle_on_bg); ?>; border-color: <?php echo esc_attr($disc_toggle_on_bg); ?>; }
        .toggle-item.active .toggle-switch::after { transform: translateX(18px); background: <?php echo esc_attr($disc_toggle_on_dot); ?>; }
        .toggle-label { font-size: <?php echo $disc_item_label_size; ?>px; font-weight: 600; color: <?php echo esc_attr($disc_item_label_color); ?>; }

        /* --- Text Chips (Pathway & Type) --- */
        .chip-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .text-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 140px;
            padding: 8px 16px;
            background: <?php echo esc_attr($disc_chip_off_bg); ?>;
            border: 1px solid <?php echo esc_attr($disc_chip_off_border); ?>;
            border-radius: var(--radius-control, 6px);
            font-size: <?php echo $disc_item_label_size; ?>px;
            font-weight: 700;
            color: <?php echo esc_attr($disc_chip_off_text); ?>;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            letter-spacing: 0.3px;
            user-select: none;
        }
        .text-chip:hover {
            border-color: rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateY(-1px);
        }
        .text-chip.selected {
            background: <?php echo esc_attr($disc_chip_on_bg); ?>;
            border-color: <?php echo esc_attr($disc_chip_on_border); ?>;
            color: <?php echo esc_attr($disc_chip_on_text); ?>;
            box-shadow: 0 0 0 1px rgba(0,128,128,0.3), 0 4px 12px rgba(0,128,128,0.15);
        }

        /* --- Keyword Input --- */
        .keyword-row { display: flex; align-items: center; gap: 12px; }
        .keyword-input { 
            flex: 1; 
            padding: 11px 16px; 
            border: 1px solid rgba(255,255,255,0.25); 
            border-radius: var(--radius-field, 10px); 
            font-size: 14px; 
            outline: none; 
            background: rgba(255,255,255,0.92); 
            color: #1a2332;
            transition: border-color 0.2s, background 0.2s;
        }
        .keyword-input::placeholder { color: #555; }
        .keyword-input:focus { border-color: #008080; background: #fff; }

        /* --- Action Buttons --- */
        .action-row { display: flex; align-items: center; gap: 14px; margin-top: 20px; }
        .btn-go { 
            padding: 11px 32px; 
            background: linear-gradient(135deg, #008080, #FF8500); 
            color: white; 
            border: none; 
            border-radius: var(--radius-control, 6px); 
            font-family: 'Outfit', sans-serif; 
            font-size: 14px; 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            cursor: pointer; 
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(0,128,128,0.35);
        }
        .btn-go:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,0.5); }
        .btn-text { 
            background: transparent; 
            border: 1px solid #476f95; 
            border-radius: var(--radius-control, 6px);
            padding: 10px 18px;
            font-size: 12px; 
            font-weight: 700; 
            color: #476f95; 
            cursor: pointer; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.2s;
        }
        .btn-text:hover { 
            border-color: #5a8ab5; 
            color: #5a8ab5; 
            background: rgba(71,111,149,0.12);
        }

        /* --- Right side AI panel --- */
        .chat-agent-bar { padding: 0 0 14px 0; background: transparent; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
        .agent-left { display: flex; align-items: center; gap: 12px; }
        .agent-avatar { width: 34px; height: 34px; background: linear-gradient(135deg, #008080, #FF8500); border-radius: var(--radius-control, 6px); display: flex; align-items: center; justify-content: center; }
        .agent-avatar svg { width: 18px; height: 18px; stroke: white; fill: none; stroke-width: 2; }
        .agent-name { font-family: 'Outfit', sans-serif; font-size: 12px; font-weight: 800; color: white; text-transform: uppercase; letter-spacing: 0.5px; }
        .agent-status { font-size: 10px; color: #22C55E; font-weight: 700; display: flex; gap: 5px; align-items: center; }
        .status-dot { width: 6px; height: 6px; background: #22C55E; border-radius: var(--radius-pill, 999px); animation: pulse-dot 2s ease infinite; }
        .save-btn { padding: 6px 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: var(--radius-control, 6px); font-size: 11px; font-weight: 700; color: rgba(255, 255, 255, 0.6); cursor: pointer; display: flex; gap: 5px; align-items: center; transition: all 0.2s; }
        .save-btn:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: white; }
        .save-btn svg { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 2; }

        .chat-messages { flex: 1; padding: 0; max-height: 340px; overflow-y: auto; margin-bottom: 16px; scroll-behavior: smooth; }
        .msg { margin-bottom: 14px; display: flex; gap: 10px; }
        .msg.bot { flex-direction: row; }
        .msg.user { flex-direction: row-reverse; }
        .msg-avatar { width: 26px; height: 26px; border-radius: var(--radius-control, 6px); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .msg.bot .msg-avatar { background: rgba(0,128,128,0.25); }
        .msg.bot .msg-avatar svg { width: 14px; height: 14px; stroke: #008080; fill: none; stroke-width: 2; }
        .msg.user .msg-avatar { background: rgba(255,255,255,0.1); }
        .msg.user .msg-avatar svg { width: 14px; height: 14px; stroke: rgba(255,255,255,0.7); fill: none; stroke-width: 2; }
        .msg-bubble { max-width: 85%; padding: 12px 16px; border-radius: var(--radius-surface, 14px); font-size: 13px; line-height: 1.6; white-space: pre-wrap; }
        .msg.bot .msg-bubble { background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: <?php echo esc_attr($askai_text_color); ?>; }
        .msg.user .msg-bubble { background: rgba(0,128,128,0.3); border: 1px solid rgba(0,128,128,0.35); color: #ffffff; }

        .mode-btn { padding: 6px 14px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: var(--radius-control, 6px); font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.6); cursor: pointer; transition: all 0.2s; white-space: nowrap; }
        .mode-btn:hover { background: rgba(255,255,255,0.1); color: white; }
        .mode-btn.active { background: rgba(0,128,128,0.2); border-color: #008080; color: #FF8040; box-shadow: 0 0 0 1px rgba(0,128,128,0.3); }

        .chat-input-bar { padding: 14px 0 0 0; border-top: 1px solid rgba(255,255,255,0.07); background: transparent; display: flex; gap: 10px; }
        .chat-input { flex: 1; padding: 10px 14px; border: 1px solid <?php echo esc_attr($askai_input_border); ?>; border-radius: var(--radius-field, 10px); font-size: 13px; outline: none; background: <?php echo esc_attr($askai_input_bg); ?>; color: <?php echo esc_attr($askai_input_color); ?>; transition: border-color 0.2s; }
        .chat-input::placeholder { color: rgba(255,255,255,0.3); }
        .chat-input:focus { border-color: rgba(0,128,128,0.5); }
        .chat-send { padding: 10px 18px; background: linear-gradient(135deg, #008080, #FF8500); color: white; border: none; border-radius: var(--radius-control, 6px); font-weight: 700; font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .chat-send:hover { box-shadow: 0 4px 12px rgba(0,128,128,0.4); }
        
        .typing-indicator { display: flex; gap: 4px; padding: 5px 0; }
        .typing-dot { width: 5px; height: 5px; background: rgba(255,255,255,0.3); border-radius: var(--radius-control, 6px); animation: typing 1.4s infinite ease-in-out both; }
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        @keyframes typing { 0%, 80%, 100% { transform: scale(0); opacity: 0.4; } 40% { transform: scale(1); opacity: 1; } }

        /* Scrollbar for chat */
        .chat-messages::-webkit-scrollbar { width: 4px; }
        .chat-messages::-webkit-scrollbar-track { background: transparent; }
        .chat-messages::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: var(--radius-control, 6px); }

        @media (max-width: 768px) {
            .explore-layout { flex-direction: column !important; }
            .explore-filters, .ask-vance-ai-side { flex: none !important; width: 100% !important; border-left: none !important; border-top: 1px solid rgba(255,255,255,0.07) !important; }
        }
        </style>

        <script>
        function switchDiscoveryTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            if (tab === 'explore') {
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
                document.getElementById('tab-explore').classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('tab-ask').classList.add('active');
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            // Global Quiz Interceptor
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a');
                if (link && link.getAttribute('href') && /(healthcare-quiz|gastro-health-survey)/.test(link.getAttribute('href'))) {
                    e.preventDefault();
                    if (typeof openQuizModal === 'function') {
                        openQuizModal();
                    } else {
                        window.location.href = link.getAttribute('href');
                    }
                }
            });
        });
        </script>
    </section>
    
    <style>
    @media (max-width: 991px) {
        .ask-vance-ai-side {
            border-left: none !important;
            padding-left: 0 !important;
            padding-top: 40px;
            border-top: 1px solid #2f4f6f;
        }
    }
    </style>
                <?php
                break;


            case 'kb':
                $kb_title = vance_get_theme_mod('vance_kb_mini_hero_title', 'IBD RESEARCH CENTRE');
                $kb_subtitle = vance_get_theme_mod('vance_kb_mini_hero_subtitle', 'Catch Up on the Latest Articles and More...');
                $kb_wrapper_bg = vance_get_theme_mod('vance_kb_wrapper_bg', '#ffffff');
                $kb_padding = vance_get_theme_mod('vance_kb_mini_hero_padding', '60px 0 80px');
                $kb_height = vance_get_theme_mod('vance_kb_mini_hero_height', '');
                $kb_font_color = vance_get_theme_mod('vance_kb_mini_hero_font_color', '#ffffff');
                $kb_opacity = (int) vance_get_theme_mod('vance_kb_mini_hero_opacity', 80) / 100;
                $kb_opacity_2 = min(1, $kb_opacity + 0.1);
                $kb_mini_bg = vance_get_theme_mod('vance_kb_mini_hero_bg');
                // 2026-05-26: removed patient_hero.png fallback. When admin clears the
                // Background Image, render a solid Background Color instead and drop
                // the dark overlay gradient so the colour reads cleanly.
                $kb_mini_bg_color = vance_get_theme_mod('vance_kb_mini_hero_bg_color', '#0A1929');

                // Mini-Hero header controls (eyebrow + per-field colour/size/align/divider).
                // Added 2026-05-26. Blank colour values fall back to $kb_font_color so
                // existing sites keep their look without re-saving anything.
                $kb_show_eyebrow      = (bool) vance_get_theme_mod('vance_kb_mini_hero_show_eyebrow', true);
                $kb_eyebrow_text      = vance_get_theme_mod('vance_kb_mini_hero_eyebrow', 'KNOWLEDGE LIBRARY');
                $kb_eyebrow_size      = (int)  vance_get_theme_mod('vance_kb_mini_hero_eyebrow_size', 12);
                $kb_eyebrow_color     = vance_get_theme_mod('vance_kb_mini_hero_eyebrow_color', '#ffffff') ?: $kb_font_color;
                $kb_eyebrow_bg        = vance_get_theme_mod('vance_kb_mini_hero_eyebrow_bg', 'rgba(255,255,255,0.10)');
                $kb_eyebrow_border    = vance_get_theme_mod('vance_kb_mini_hero_eyebrow_border', 'rgba(255,255,255,0.20)');
                $kb_title_size        = (int)  vance_get_theme_mod('vance_kb_mini_hero_title_size', 38);
                $kb_title_color       = vance_get_theme_mod('vance_kb_mini_hero_title_color', '') ?: $kb_font_color;
                $kb_subtitle_size     = (int)  vance_get_theme_mod('vance_kb_mini_hero_subtitle_size', 18);
                $kb_subtitle_color    = vance_get_theme_mod('vance_kb_mini_hero_subtitle_color', '') ?: $kb_font_color;
                $kb_align             = vance_get_theme_mod('vance_kb_mini_hero_align', 'center');
                if ( ! in_array( $kb_align, array( 'left', 'center', 'right' ), true ) ) { $kb_align = 'center'; }
                $kb_header_bg         = vance_get_theme_mod('vance_kb_mini_hero_header_bg', '');
                $kb_show_divider      = (bool) vance_get_theme_mod('vance_kb_mini_hero_show_divider', false);
                $kb_divider_color     = vance_get_theme_mod('vance_kb_mini_hero_divider_color', 'rgba(255,255,255,0.25)');
                $kb_divider_width     = max(1, (int) vance_get_theme_mod('vance_kb_mini_hero_divider_width', 2));

                // Map alignment -> margin rules so left/right alignments don't get
                // visually centred by the 600px subtitle cap.
                $kb_subtitle_margin = ($kb_align === 'center') ? '0 auto' : '0';
                if ($kb_align === 'right') { $kb_subtitle_margin = '0 0 0 auto'; }
                $kb_divider_margin  = ($kb_align === 'center') ? '24px auto 0' : '24px 0 0';
                if ($kb_align === 'right') { $kb_divider_margin = '24px 0 0 auto'; }

                // Optional header-block (card behind the copy). Empty bg = no card chrome,
                // so the layout is identical to the legacy render when admin hasn't opted in.
                $kb_header_block_style = 'max-width: 800px;';
                if ($kb_align === 'center') { $kb_header_block_style .= ' margin: 0 auto;'; }
                if ($kb_align === 'right')  { $kb_header_block_style .= ' margin: 0 0 0 auto;'; }
                if ($kb_header_bg !== '') {
                    $kb_header_block_style .= ' background: ' . esc_attr($kb_header_bg) . '; padding: 32px 36px;';
                }

                // Build hero background. With an image: dark overlay gradient + image.
                // Without an image: solid background color, no gradient overlay.
                if ( $kb_mini_bg ) {
                    $hero_style = "position: relative; padding: " . esc_attr($kb_padding) . "; background-color: " . esc_attr($kb_mini_bg_color) . "; background-image: linear-gradient(rgba(10, 25, 41, " . $kb_opacity . "), rgba(10, 25, 41, " . $kb_opacity_2 . ")), url('" . esc_url($kb_mini_bg) . "'); background-position: center; background-size: cover; background-repeat: no-repeat; text-align: " . esc_attr($kb_align) . "; color: " . esc_attr($kb_font_color) . ";";
                } else {
                    $hero_style = "position: relative; padding: " . esc_attr($kb_padding) . "; background-color: " . esc_attr($kb_mini_bg_color) . "; background-image: none; text-align: " . esc_attr($kb_align) . "; color: " . esc_attr($kb_font_color) . ";";
                }
                if ($kb_height) {
                    $hero_style .= " min-height: " . esc_attr($kb_height) . "px; display: flex; align-items: center;";
                }
                ?>
    <section class="kb-section-wrapper" style="border-top: 2px solid var(--primary-color); background-color: <?php echo esc_attr($kb_wrapper_bg); ?>;">
        <section class="kb-mini-hero" style="<?php echo $hero_style; ?>">
            <div class="container" style="width: 100%;">
                <div class="kb-mini-hero__header" style="<?php echo $kb_header_block_style; ?>">
                    <?php if ($kb_show_eyebrow && trim($kb_eyebrow_text) !== '') : ?>
                        <span class="kb-mini-hero__eyebrow" style="display: inline-block; padding: 6px 14px; margin-bottom: 18px; font-family: 'Inter', sans-serif; font-size: <?php echo (int) $kb_eyebrow_size; ?>px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: <?php echo esc_attr($kb_eyebrow_color); ?>; background: <?php echo esc_attr($kb_eyebrow_bg); ?>; border: 1px solid <?php echo esc_attr($kb_eyebrow_border); ?>; border-radius: var(--radius-control, 6px); line-height: 1;"><?php echo esc_html($kb_eyebrow_text); ?></span>
                    <?php endif; ?>
                    <h2 class="kb-mini-hero__title" style="font-family: 'Outfit', sans-serif; font-size: <?php echo (int) $kb_title_size; ?>px; font-weight: 800; line-height: 1.1; margin: 0 0 12px 0; color: <?php echo esc_attr($kb_title_color); ?>;"><?php echo esc_html($kb_title); ?></h2>
                    <p class="kb-mini-hero__subtitle" style="font-size: <?php echo (int) $kb_subtitle_size; ?>px; opacity: 0.85; max-width: 600px; margin: <?php echo esc_attr($kb_subtitle_margin); ?>; color: <?php echo esc_attr($kb_subtitle_color); ?>; line-height: 1.5;"><?php echo esc_html($kb_subtitle); ?></p>
                    <?php if ($kb_show_divider) : ?>
                        <div class="kb-mini-hero__divider" aria-hidden="true" style="width: 80px; height: <?php echo (int) $kb_divider_width; ?>px; background: <?php echo esc_attr($kb_divider_color); ?>; margin: <?php echo esc_attr($kb_divider_margin); ?>;"></div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </section>
                <?php
                break;

            case 'kb-content':
                // Category content blocks. Standalone section so admins can put
                // other blocks (e.g. tool-widgets-row) between the KB hero and
                // this content area. 2026-05-26.
                $kbc_bg       = vance_get_theme_mod( 'vance_kb_content_bg', vance_get_theme_mod( 'vance_kb_wrapper_bg', '#ffffff' ) );
                $kbc_pad_top  = absint( vance_get_theme_mod( 'vance_kb_content_pad_top', 0 ) );
                $kbc_pad_bot  = absint( vance_get_theme_mod( 'vance_kb_content_pad_bottom', 0 ) );
                ?>
    <section class="kb-content-wrapper" style="background-color: <?php echo esc_attr($kbc_bg); ?>; padding: <?php echo $kbc_pad_top; ?>px 0 <?php echo $kbc_pad_bot; ?>px;">
        <?php
        $kb_cats = get_categories(array('hide_empty' => false));
        $kb_sections = array();
        foreach ($kb_cats as $cat) {
            if (vance_get_theme_mod("vance_kb_show_{$cat->term_id}", true)) {
                $kb_sections[] = array(
                    'cat' => $cat,
                    'priority' => vance_get_theme_mod("vance_kb_priority_{$cat->term_id}", 10),
                    'count' => vance_get_theme_mod("vance_kb_count_{$cat->term_id}", 4),
                    'layout' => vance_get_theme_mod("vance_kb_layout_{$cat->term_id}", 'grid-4'),
                    'view_all' => vance_get_theme_mod("vance_kb_view_all_{$cat->term_id}", 'View All'),
                );
            }
        }
        usort($kb_sections, function($a, $b) { return $a['priority'] - $b['priority']; });

        foreach ($kb_sections as $sec):
            $cat = $sec['cat'];
            $layout = $sec['layout'];
            if ($cat->name === 'Expert Opinions' || $cat->slug === 'expert-opinions') { $layout = 'bento'; }
            // Posters fetch exactly columns x rows cards (admin-controlled, 3-5
            // cols x 1-3 rows); bento/asymmetric are fixed at 3; grids use the
            // per-category count. Cols default is reused by the render branch.
            $kb_posters_cols = ($layout === 'posters') ? vance_get_kb_posters_cols($cat->term_id) : 3;
            $kb_posters_rows = ($layout === 'posters') ? vance_get_kb_posters_rows($cat->term_id) : 2;
            if ($layout === 'posters') {
                $post_count = $kb_posters_cols * $kb_posters_rows;
            } elseif ($layout === 'bento' || $layout === 'asymmetric') {
                $post_count = 3;
            } else {
                $post_count = intval($sec['count']);
            }
            
            $posts_array = get_posts(array(
                'numberposts' => $post_count,
                'category' => $cat->term_id,
                'orderby' => 'date',
                'order' => 'DESC',
                'post_type' => 'any',
                'post_status' => 'publish',
            ));
            
            if (empty($posts_array)) continue;
            // Accent colour is sourced from the single per-category "source of
            // truth" colour — the Post Hero Overlay colour (this category's
            // custom colour when set, otherwise the global overlay colour), via
            // vance_category_source_color(). This keeps the colour bar, featured
            // tag and category meta in sync with the category's article hero
            // instead of a separate per-widget setting. Falls back to a stable
            // per-term palette colour if the helper isn't available.
            $vance_kb_accent_palette = array('#F59E0B', '#0EA5E9', '#008080', '#10B981', '#8B5CF6');
            $kb_accent_default       = $vance_kb_accent_palette[ ((int) $cat->term_id) % count($vance_kb_accent_palette) ];
            $color       = function_exists('vance_category_source_color')
                ? vance_category_source_color($cat->term_id)
                : vance_get_theme_mod("vance_kb_accent_{$cat->term_id}", $kb_accent_default);
            // Per-category title colour (new) + the previously-orphaned
            // description text the admin set but which wasn't being rendered.
            $title_color = vance_get_theme_mod("vance_kb_title_color_{$cat->term_id}", '#0f172a');
            $cat_desc    = trim( (string) vance_get_theme_mod("vance_kb_desc_{$cat->term_id}", '') );
        ?>
        <section style="padding: 60px 0;">
            <div class="container">
                <div class="section-label">
                    <div class="section-label-left">
                        <div class="color-bar" style="background: <?php echo esc_attr($color); ?>"></div>
                        <h2 style="color: <?php echo esc_attr($title_color); ?>;"><?php echo esc_html($cat->name); ?></h2>
                    </div>
                    <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>" style="color: var(--primary-color); font-weight: 600; text-decoration: none; font-size: 14px;"><?php echo esc_html($sec['view_all']); ?> →</a>
                </div>
                <?php if ( $cat_desc !== '' ) : ?>
                    <p style="max-width: 720px; margin: 4px 0 28px 0; color: #475569; font-size: 15px; line-height: 1.55;"><?php echo esc_html( $cat_desc ); ?></p>
                <?php endif; ?>

                <?php if ($layout === 'bento' && count($posts_array) >= 3): ?>
                    <div class="bento-grid-news">
                        <?php $p = $posts_array[0]; ?>
                        <a href="<?php echo get_permalink($p->ID); ?>" class="bento-cell-featured" data-vhh-post-id="<?php echo (int) $p->ID; ?>">
                            <img src="<?php echo get_the_post_thumbnail_url($p->ID, 'large') ?: 'https://via.placeholder.com/800x600'; ?>" alt="">
                            <div class="bento-content-overlay">
                                <span class="tag" style="background:<?php echo $color; ?>">Featured</span>
                                <h3 style="font-size: 28px; color: white; margin-bottom: 12px;"><?php echo esc_html(vance_card_title($p->ID)); ?></h3>
                                <div class="meta" style="color: rgba(255,255,255,0.8);">By <?php echo get_the_author_meta('display_name', $p->post_author); ?> • <?php echo get_the_date('', $p->ID); ?></div>
                            </div>
                        </a>
                        <div style="display: flex; flex-direction: column; gap: 24px; grid-row: 1 / -1;">
                            <?php for($i=1; $i<=2; $i++): $p = $posts_array[$i]; ?>
                            <a href="<?php echo get_permalink($p->ID); ?>" class="bento-cell-side">
                                <span class="meta" style="color:<?php echo $color; ?>; margin-bottom:8px;"><?php echo $cat->name; ?></span>
                                <h4 class="heading-small"><?php echo esc_html(vance_card_title($p->ID)); ?></h4>
                                <p class="text-body" style="font-size: 13px; margin-bottom: 8px;"><?php echo wp_trim_words(get_the_excerpt($p->ID), 12); ?></p>
                                <div class="meta"><?php echo human_time_diff(get_post_time('U', false, $p->ID), current_time('timestamp')) . ' ago'; ?></div>
                            </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php elseif ($layout === 'posters' && count($posts_array) >= 1): ?>
                    <div class="va-sub-grid va-layout-posters va-posters--cols-<?php echo (int) $kb_posters_cols; ?>">
                        <?php foreach ($posts_array as $p):
                            $poster_thumb = get_the_post_thumbnail_url($p->ID, 'large');
                            $poster_read  = vance_get_read_time($p->ID);
                        ?>
                        <article class="va-poster-card">
                            <a class="va-poster-link" href="<?php echo esc_url(get_permalink($p->ID)); ?>" data-vhh-post-id="<?php echo (int) $p->ID; ?>" style="background-image: url('<?php echo esc_url($poster_thumb); ?>');">
                                <span class="va-poster-shade" aria-hidden="true"></span>
                                <?php echo vance_card_eyebrow_html($p->ID, true); ?>
                                <div class="va-poster-body">
                                    <div class="va-poster-meta"><?php echo esc_html(get_the_date('', $p->ID)); ?> &middot; <?php echo (int) $poster_read; ?> min read</div>
                                    <h3 class="va-poster-title"><?php echo esc_html(vance_card_title($p->ID)); ?></h3>
                                </div>
                            </a>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php else:
                    /* Standard Grid. The column count is set by the minimum
                       track width, not a fixed `repeat(n)`: auto-fill then drops
                       a column on its own as the viewport narrows, which is how
                       the 4-col grid has always behaved. Inside a 1160px
                       container with a 24px gap, a 260px minimum lands on four
                       columns and a 200px minimum on five. Classes rather than
                       an inline style so the phone breakpoint in main.css can
                       reach them — an inline grid-template-columns would win
                       over any stylesheet rule. */
                    $kb_grid_min = ( $layout === 'grid-5' ) ? 200 : 260;
                ?>
                    <div class="va-kb-grid va-kb-grid--min-<?php echo (int) $kb_grid_min; ?>">
                        <?php foreach ($posts_array as $p): ?>
                        <article class="news-card" style="background: white; border-radius: 0; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; transition: all 0.3s; height: 100%; display: flex; flex-direction: column;">
                            <?php
                                $read_time = vance_get_read_time($p->ID);
                                $view_count = vance_get_view_count($p->ID);
                            ?>
                            <?php if (has_post_thumbnail($p->ID)): ?>
                                <div style="position: relative; overflow: hidden; height: 180px; background: #f1f5f9;">
                                    <img src="<?php echo get_the_post_thumbnail_url($p->ID, 'medium'); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php echo vance_card_eyebrow_html($p->ID); ?>
                                </div>
                            <?php endif; ?>
                            <div style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column;">
                                <h3 style="font-size: 16px; margin-bottom: 10px; line-height: 1.4;">
                                    <a href="<?php echo get_permalink($p->ID); ?>" class="card-stretched-link" style="color: #0f172a; text-decoration: none; font-weight: 600;"><?php echo esc_html(vance_card_title($p->ID)); ?></a>
                                </h3>
                                <p style="font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo wp_trim_words(get_the_excerpt($p->ID), 15); ?>
                                </p>
                                <?php echo vance_card_meta_footer_html($p->ID); ?>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </section>
                <?php
                break;

            case 'testimonials':
                echo vance_testimonials_shortcode(array());
                break;

            default:
                // Registry-driven dispatch. Any section ID that's registered
                // via vance_get_available_sections() with a 'render' callable
                // will fire here — including all the cross-page named blocks
                // (patients-*, hcp-*, content-widget-* and any future
                // additions). This means there is no upper limit on how many
                // sections the admin can tick in the Section Order Customizer
                // control; every checked section displays.
                if ( function_exists( 'vance_get_available_sections' ) ) {
                    $registry = vance_get_available_sections();
                    if ( isset( $registry[ $section_id ]['render'] ) && is_callable( $registry[ $section_id ]['render'] ) ) {
                        call_user_func( $registry[ $section_id ]['render'] );
                    }
                }
                break;
        }

        // -------------------------------------------------------------
        // SECTION DIVIDER (per-section toggle)
        // After each section's case body runs, check whether the admin has
        // ticked "Show divider AFTER {section}" in Appearance -> Customize
        // -> Homepage -> Section Dividers. If so, emit a styled <hr> using
        // the shared look config (colour, thickness, width, style, margin,
        // padding). Section IDs with hyphens are stored with underscores in
        // the setting key (Customizer doesn't like hyphenated keys mid-name
        // in some contexts, and underscores keep saved values stable).
        // -------------------------------------------------------------
        $divider_key = 'vance_divider_after_' . str_replace( '-', '_', $section_id );
        if ( vance_get_theme_mod( $divider_key, false ) ) {
            $div_color     = vance_get_theme_mod( 'vance_divider_color',     '#2f4f6f' );
            $div_bg_color  = vance_get_theme_mod( 'vance_divider_bg_color',  '' );
            $div_thickness = absint( vance_get_theme_mod( 'vance_divider_thickness', 1 ) );
            $div_width_pct = absint( vance_get_theme_mod( 'vance_divider_width',     100 ) );
            $div_style     = vance_get_theme_mod( 'vance_divider_style',     'solid' );
            $div_margin    = absint( vance_get_theme_mod( 'vance_divider_margin',    40 ) );
            $div_padding   = absint( vance_get_theme_mod( 'vance_divider_padding',   0 ) );
            $div_width_pct = max( 10, min( 100, $div_width_pct ) );
            $allowed_styles = array( 'solid', 'dashed', 'dotted', 'double' );
            if ( ! in_array( $div_style, $allowed_styles, true ) ) { $div_style = 'solid'; }
            // 2026-05-26: optional background colour on the divider wrapper.
            // When blank, omit the declaration entirely so the row is transparent.
            // When a bg colour IS set, force a sensible min padding so the band
            // is visible (otherwise the wrapper is only 1px tall — just the <hr>
            // line — and the colour shows as a near-invisible sliver).
            $div_bg_decl = '';
            if ( $div_bg_color ) {
                $div_bg_decl = 'background: ' . esc_attr( $div_bg_color ) . ';';
                if ( $div_padding < 16 ) {
                    $div_padding = 32; // visible band height
                }
            }
            ?>
            <div class="vance-section-divider-wrap" style="padding: <?php echo $div_padding; ?>px 0; margin: <?php echo $div_margin; ?>px 0; <?php echo $div_bg_decl; ?>">
                <hr class="vance-section-divider" style="
                    border: 0;
                    border-top: <?php echo $div_thickness; ?>px <?php echo esc_attr( $div_style ); ?> <?php echo esc_attr( $div_color ); ?>;
                    width: <?php echo $div_width_pct; ?>%;
                    margin: 0 auto;
                " aria-hidden="true">
            </div>
            <?php
        }
    }
    ?>
</main>

<!-- Modals & Scripts -->

    <!-- PREMIUM SUBSCRIBE SECTION -->
    <?php
    $prem_section_bg     = vance_get_theme_mod('vance_premium_section_bg',     '#0f172a');
    $prem_pad_top        = absint( vance_get_theme_mod('vance_premium_pad_top',    100) );
    $prem_pad_bot        = absint( vance_get_theme_mod('vance_premium_pad_bottom', 100) );
    $prem_eyebrow        = vance_get_theme_mod('vance_premium_eyebrow',        'Join the Inner Circle');
    $prem_eyebrow_color  = vance_get_theme_mod('vance_premium_eyebrow_color',  '#008080');
    $prem_eyebrow_bg     = vance_get_theme_mod('vance_premium_eyebrow_bg',     '');
    $prem_eyebrow_border = vance_get_theme_mod('vance_premium_eyebrow_border', '');
    $prem_eyebrow_style  = '';
    if ( $prem_eyebrow_color )  { $prem_eyebrow_style .= 'color:' . esc_attr( $prem_eyebrow_color ) . ';'; }
    if ( $prem_eyebrow_bg )     { $prem_eyebrow_style .= 'background:' . esc_attr( $prem_eyebrow_bg ) . ';'; }
    if ( $prem_eyebrow_border ) { $prem_eyebrow_style .= 'border-color:' . esc_attr( $prem_eyebrow_border ) . ';'; }
    $prem_heading        = vance_get_theme_mod('vance_premium_heading',        'Access <span class="highlight">IBD Clinical Resources</span>');
    $prem_heading_color  = vance_get_theme_mod('vance_premium_heading_color',  '#ffffff');
    $prem_heading_size   = absint( vance_get_theme_mod('vance_premium_heading_size', 42) );
    $prem_highlight      = vance_get_theme_mod('vance_premium_highlight_color', '#008080');
    $prem_desc           = vance_get_theme_mod('vance_premium_desc',           'Gain access to premium articles, monthly masterclasses, and a personalized health dashboard. Join 50,000+ members on the path to better living.');
    $prem_desc_color     = vance_get_theme_mod('vance_premium_desc_color',     '#94a3b8');
    $prem_desc_size      = absint( vance_get_theme_mod('vance_premium_desc_size', 18) );
    $prem_pill_1         = vance_get_theme_mod('vance_premium_pill_1',         'Expert Reviews');
    $prem_pill_2         = vance_get_theme_mod('vance_premium_pill_2',         'Weekly Digests');
    $prem_pill_text      = vance_get_theme_mod('vance_premium_pill_text_color', '#cbd5e1');
    $prem_pill_check     = vance_get_theme_mod('vance_premium_pill_check_color', '#008080');
    $prem_card_bg        = vance_get_theme_mod('vance_premium_card_bg',        'rgba(255,255,255,0.05)');
    $prem_card_border    = vance_get_theme_mod('vance_premium_card_border',    'rgba(255,255,255,0.10)');
    $prem_card_heading   = vance_get_theme_mod('vance_premium_card_heading',   'Start Your Journey');
    $prem_card_heading_color = vance_get_theme_mod('vance_premium_card_heading_color', '#ffffff');
    $prem_card_subhead   = vance_get_theme_mod('vance_premium_card_subheading', '');
    /* Own color, independent of $prem_desc_color: that one is tuned for the
       left-column description sitting on the dark section background, but
       this subheading sits inside the light signup card (vance_premium_card_bg,
       #f4ffff live) — reusing the light grey there made it nearly invisible. */
    $prem_card_subhead_color = vance_get_theme_mod('vance_premium_card_subheading_color', '#000000');
    $prem_input_bg       = vance_get_theme_mod('vance_premium_input_bg',       'rgba(0,0,0,0.20)');
    $prem_input_color    = vance_get_theme_mod('vance_premium_input_color',    '#ffffff');
    $prem_input_ph       = vance_get_theme_mod('vance_premium_input_placeholder', 'Enter your email address');
    $prem_button_label   = vance_get_theme_mod('vance_premium_button_label',   'Get Started Now →');
    $prem_button_link    = vance_get_theme_mod('vance_premium_button_link',    '');
    $prem_button_bg      = vance_get_theme_mod('vance_premium_button_bg',      '#008080');
    $prem_button_color   = vance_get_theme_mod('vance_premium_button_color',   '#ffffff');
    $prem_card_footnote  = vance_get_theme_mod('vance_premium_card_footnote',  '');
    $prem_form_action    = $prem_button_link ?: wp_registration_url();
    ?>
    <style>
        .premium-subscribe-section .highlight { color: <?php echo esc_attr($prem_highlight); ?>; }
        .premium-subscribe-section .premium-pill-check { background: rgba(255,255,255,0.10); width: 24px; height: 24px; border-radius: var(--radius-control, 6px); display: flex; align-items: center; justify-content: center; color: <?php echo esc_attr($prem_pill_check); ?>; }
        .premium-subscribe-section .premium-pill { display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; color: <?php echo esc_attr($prem_pill_text); ?>; }
        .premium-subscribe-section .premium-input::placeholder { color: rgba(255,255,255,0.55); }
    </style>
    <!-- Seam into the premium band. This section sits outside the Section
         Order loop, so it needs its own; it is also the sharpest join on the
         page (light grey straight into #2f4f6f). -->
    <div class="vance-hp-seam" aria-hidden="true"></div>
    <section class="premium-subscribe-section" style="background: <?php echo esc_attr($prem_section_bg); ?>; padding: <?php echo $prem_pad_top; ?>px 0 <?php echo $prem_pad_bot; ?>px; color: <?php echo esc_attr($prem_heading_color); ?>;">
        <div class="container" style="display: flex; align-items: center; justify-content: space-between; gap: 60px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px;">
                <span class="tag-label" style="<?php echo $prem_eyebrow_style; ?>"><?php echo esc_html($prem_eyebrow); ?></span>
                <h2 style="font-family: 'Outfit', sans-serif; font-size: <?php echo $prem_heading_size; ?>px; font-weight: 800; line-height: 1.1; margin-bottom: 24px; color: <?php echo esc_attr($prem_heading_color); ?>;"><?php echo wp_kses_post($prem_heading); ?></h2>
                <p style="font-size: <?php echo $prem_desc_size; ?>px; color: <?php echo esc_attr($prem_desc_color); ?>; line-height: 1.6; margin-bottom: 32px; max-width: 500px;">
                    <?php echo esc_html($prem_desc); ?>
                </p>
                <div style="display: flex; gap: 24px; align-items: center; flex-wrap: wrap;">
                    <?php if ( $prem_pill_1 ) : ?>
                        <div class="premium-pill"><span class="premium-pill-check">✓</span> <?php echo esc_html($prem_pill_1); ?></div>
                    <?php endif; ?>
                    <?php if ( $prem_pill_2 ) : ?>
                        <div class="premium-pill"><span class="premium-pill-check">✓</span> <?php echo esc_html($prem_pill_2); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div style="flex-shrink: 0; background: <?php echo esc_attr($prem_card_bg); ?>; padding: 40px; border-radius: var(--radius-surface, 14px); border: 1px solid <?php echo esc_attr($prem_card_border); ?>; max-width: 400px; width: 100%;">
                <h3 style="font-size: 24px; font-weight: 700; margin-bottom: <?php echo $prem_card_subhead ? '8' : '24'; ?>px; color: <?php echo esc_attr($prem_card_heading_color); ?>;"><?php echo esc_html($prem_card_heading); ?></h3>
                <?php if ( $prem_card_subhead ) : ?>
                    <p style="color: <?php echo esc_attr($prem_card_subhead_color); ?>; font-size: 14px; margin-bottom: 24px;"><?php echo esc_html($prem_card_subhead); ?></p>
                <?php endif; ?>
                <form action="<?php echo esc_url($prem_form_action); ?>" method="get" style="display: flex; flex-direction: column; gap: 16px;">
                    <input type="email" name="user_email" placeholder="<?php echo esc_attr($prem_input_ph); ?>" required class="premium-input" style="width: 100%; padding: 16px; border-radius: var(--radius-field, 10px); border: 1px solid rgba(255,255,255,0.2); background: <?php echo esc_attr($prem_input_bg); ?>; color: <?php echo esc_attr($prem_input_color); ?>; font-size: 16px;">
                    <button type="submit" style="width: 100%; padding: 16px; border-radius: var(--radius-control, 6px); border: none; background: <?php echo esc_attr($prem_button_bg); ?>; color: <?php echo esc_attr($prem_button_color); ?>; font-weight: 700; font-size: 16px; cursor: pointer; transition: background 0.2s;"><?php echo esc_html($prem_button_label); ?></button>
                    <?php if ( $prem_card_footnote ) : ?>
                        <p style="text-align: center; font-size: 12px; color: #64748b; margin: 0;"><?php echo esc_html($prem_card_footnote); ?></p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </section>

<?php get_footer(); ?>
