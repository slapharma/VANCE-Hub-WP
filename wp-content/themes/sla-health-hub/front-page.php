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
    --radius-md: 0;
    --radius-lg: 0;
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

.color-bar { width: 6px; height: 24px; border-radius: 0; }

/* BENTO GRID (News Style) */
.bento-grid-news {
    display: grid;
    grid-template-columns: 2fr 1fr;
    grid-template-rows: repeat(2, 200px);
    gap: 24px;
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

.tag {
    background: var(--primary-color);
    color: white;
    padding: 4px 12px;
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 700;
    border-radius: 0;
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
    border-radius: 0;
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
    border-radius: 0;
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
    border-radius: 0;
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
    border-radius: 0;
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

    /* PROMO BLOCK STYLES */
    .promo-block-section {
        padding: 60px 0;
        overflow: hidden;
    }
    .promo-container {
        display: flex;
        align-items: center;
        gap: 60px;
    }
    .promo-container.layout-left { flex-direction: row-reverse; }
    .promo-container.layout-top { flex-direction: column; text-align: center; }
    .promo-content { flex: 1; }
    .promo-image-box { flex: 1; border-radius: 0; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    .promo-image-box img { width: 100%; height: auto; display: block; }
    
    /* PATHWAY OVERLAP */
    .pathway-tiles-section {
        position: relative;
        z-index: 10;
        margin-top: -60px;
    }

    @media (max-width: 768px) {
        .promo-container { flex-direction: column !important; text-align: center; gap: 30px; }
        .promo-image-box { width: 100%; }
        .pathway-tiles-section { margin-top: 0; }
    }
</style>

    <?php
    // Section order is now driven by the sortable Customizer control:
    // Appearance -> Customize -> Vance Theme -> Homepage -> Section Order.
    // Stored as a comma-separated string of CHECKED section IDs in display
    // order. The previous "force-add testimonials/pathway_content" fallbacks
    // have been removed now that the admin has explicit per-section checkboxes.
    $section_order = vance_get_theme_mod('vance_homepage_section_order', 'hero,pathway,pathway_content,promo,cats,tool-widget-content-filters,tool-widget-vance-ai,join,kb,testimonials');
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

    foreach ($sections as $section_id) {
        $section_id = trim($section_id);
        switch ($section_id) {
            case 'hero':
                ?>
    <!-- Hero Section (Patient Style Structure) -->
    <?php
    $hero_bg = vance_get_theme_mod('vance_homepage_hero_image');
    if (!$hero_bg) {
        $hero_bg = get_template_directory_uri() . '/assets/img/news_hero.png';
    }
    
    $hero_tag        = vance_get_theme_mod('vance_hero_tag_label',  'HEALTHCARE KNOWLEDGE HUB');
    $hero_tag_bg     = vance_get_theme_mod('vance_hero_tag_bg',     '#ffffff');
    $hero_tag_color  = vance_get_theme_mod('vance_hero_tag_color',  '#f86409');
    $hero_tag_border = vance_get_theme_mod('vance_hero_tag_border', '#f86409');
    $hero_title = vance_get_theme_mod('vance_hero_custom_title', 'Your Partner in <span class="highlight">Lifelong Wellness</span>');
    $hero_subtitle = vance_get_theme_mod('vance_hero_custom_subtitle', 'Trusted, science-backed information to help you understand your health, manage your IBD condition, and live your best life through clinical nutrition.');
    
    $btn1_text = vance_get_theme_mod('vance_hero_button_1_text', "I'm a Practitioner");
    $btn1_link = vance_get_theme_mod('vance_hero_button_1_link', '/healthcare-professionals/');
    $btn2_text = vance_get_theme_mod('vance_hero_button_2_text', "I'm a Patient");
    $btn2_link = vance_get_theme_mod('vance_hero_button_2_link', '/patients/');

    // 2026-05-26: button colour controls (text + bg + border + hover variants).
    $btn1_text_color       = vance_get_theme_mod('vance_hero_btn1_text_color',       '#ffffff');
    $btn1_bg_color         = vance_get_theme_mod('vance_hero_btn1_bg_color',         '#008080');
    $btn1_border_color     = vance_get_theme_mod('vance_hero_btn1_border_color',     '#008080');
    $btn1_hover_text_color = vance_get_theme_mod('vance_hero_btn1_hover_text_color', '#ffffff');
    $btn1_hover_bg_color   = vance_get_theme_mod('vance_hero_btn1_hover_bg_color',   '#006666');
    $btn2_text_color       = vance_get_theme_mod('vance_hero_btn2_text_color',       '#ffffff');
    $btn2_bg_color         = vance_get_theme_mod('vance_hero_btn2_bg_color',         '');
    $btn2_border_color     = vance_get_theme_mod('vance_hero_btn2_border_color',     '#ffffff');
    $btn2_hover_text_color = vance_get_theme_mod('vance_hero_btn2_hover_text_color', '#0A1929');
    $btn2_hover_bg_color   = vance_get_theme_mod('vance_hero_btn2_hover_bg_color',   '#ffffff');
    $btn2_bg_decl          = $btn2_bg_color ? 'background: ' . esc_attr($btn2_bg_color) . ';' : 'background: transparent;';

    $mask_enabled = vance_get_theme_mod('vance_hero_mask_toggle', true);
    // Per-page slider (0-100) takes precedence over the legacy global mask_opacity (0-1) when set.
    $home_overlay_pct = vance_get_theme_mod('vance_home_hero_overlay', null);
    if ( $home_overlay_pct !== null && $home_overlay_pct !== '' ) {
        $mask_opacity = max(0, min(100, absint($home_overlay_pct))) / 100;
    } else {
        $mask_opacity = vance_get_theme_mod('vance_hero_mask_opacity', 0.5);
    }
    $hero_title_size = vance_get_theme_mod('vance_hero_title_size', 52);
    $hero_title_color = vance_get_theme_mod('vance_hero_title_color', '#ffffff');
    $hero_subtitle_color = vance_get_theme_mod('vance_hero_subtitle_color', '#cbd5e1');
    $hero_bg_color = vance_get_theme_mod('vance_hero_bg_color', '#0A1929');

    $hero_bg_style = "background-color: " . esc_attr($hero_bg_color) . "; background: url('" . esc_url($hero_bg) . "') no-repeat center center; background-size: cover;";
    if ($mask_enabled) {
        $alpha1 = $mask_opacity;
        $alpha2 = min(1, $alpha1 + 0.15);
        $hero_bg_style = "background-color: " . esc_attr($hero_bg_color) . "; background: linear-gradient(rgba(10, 25, 41, {$alpha1}), rgba(10, 25, 41, {$alpha2})), url('" . esc_url($hero_bg) . "') no-repeat center center; background-size: cover;";
    }
    ?>
    <style>
        .hero .vance-hero-btn-1:hover { background: <?php echo esc_attr($btn1_hover_bg_color); ?> !important; color: <?php echo esc_attr($btn1_hover_text_color); ?> !important; border-color: <?php echo esc_attr($btn1_hover_bg_color); ?> !important; }
        .hero .vance-hero-btn-2:hover { background: <?php echo esc_attr($btn2_hover_bg_color); ?> !important; color: <?php echo esc_attr($btn2_hover_text_color); ?> !important; border-color: <?php echo esc_attr($btn2_hover_bg_color); ?> !important; }
    </style>
    <section class="hero patient-hero" style="display: flex; align-items: center; <?php echo $hero_bg_style; ?> color: white; position: relative; overflow: hidden;">
        <div class="container" style="position:relative;z-index:1;">
            <div style="max-width: 800px;">
                <span class="tag-label" style="background: <?php echo esc_attr($hero_tag_bg); ?>; color: <?php echo esc_attr($hero_tag_color); ?>; border: 1.5px solid <?php echo esc_attr($hero_tag_border); ?>;"><?php echo esc_html($hero_tag); ?></span>
                <h1 style="font-size: <?php echo esc_attr($hero_title_size); ?>px; color: <?php echo esc_attr($hero_title_color); ?>; line-height: 1.1; margin: 16px 0 10px; font-weight: 800; font-family: 'Outfit', sans-serif;">
                    <?php 
                    // Ensure highlight spans inherit the customized color if they exist in the title string
                    $title_display = wp_kses_post($hero_title);
                    if (strpos($title_display, 'class="highlight"') !== false) {
                        $title_display = str_replace('class="highlight"', 'class="highlight" style="color: inherit;"', $title_display);
                    }
                    echo $title_display; 
                    ?>
                </h1>
                <p style="font-size: 20px; line-height: 1.6; color: <?php echo esc_attr($hero_subtitle_color); ?>; margin: 0 0 32px; max-width: 600px;">
                    <?php echo esc_html($hero_subtitle); ?>
                </p>
                <div class="hero-actions" style="display: flex; gap: 16px; flex-wrap: wrap;">
                    <?php 
                    $btn1_onclick = (strpos($btn1_link, 'quiz') !== false) ? 'onclick="event.preventDefault(); openQuizModal();"' : '';
                    $btn2_onclick = (strpos($btn2_link, 'quiz') !== false) ? 'onclick="event.preventDefault(); openQuizModal();"' : '';
                    ?>
                    <a href="<?php echo esc_url($btn1_link); ?>" <?php echo $btn1_onclick; ?> class="btn btn-primary vance-hero-btn-1" style="background: <?php echo esc_attr($btn1_bg_color); ?>; color: <?php echo esc_attr($btn1_text_color); ?>; border: 2px solid <?php echo esc_attr($btn1_border_color); ?>; padding: 14px 28px; border-radius: 0; font-weight: 700; text-decoration: none; transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;"><?php echo esc_html($btn1_text); ?></a>
                    <a href="<?php echo esc_url($btn2_link); ?>" <?php echo $btn2_onclick; ?> class="btn btn-outline vance-hero-btn-2" style="<?php echo $btn2_bg_decl; ?> color: <?php echo esc_attr($btn2_text_color); ?>; border: 2px solid <?php echo esc_attr($btn2_border_color); ?>; padding: 14px 28px; border-radius: 0; font-weight: 700; text-decoration: none; transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;"><?php echo esc_html($btn2_text); ?></a>
                </div>
            </div>
        </div>
    </section>
                <?php
                break;

            case 'pathway':
                $tile_radius = vance_get_theme_mod('vance_pathway_tile_radius', 16);
                $image_radius = vance_get_theme_mod('vance_pathway_tile_image_radius', 8);
                
                // Fetch latest posts using Customizer settings
                $latest_title = vance_get_theme_mod('vance_pathway_latest_title', 'LATEST CONTENT');
                $latest_count = vance_get_theme_mod('vance_pathway_latest_count', 3);
                $latest_cat   = (int) vance_get_theme_mod('vance_pathway_latest_category', 0);
                $show_date    = vance_get_theme_mod('vance_pathway_latest_show_date', true);

                // Include all Content Hub Station CPTs so their posts appear
                $cpt_post_types = array(
                    'post', 'news', 'research', 'oped', 'review',
                    'whitepaper', 'podcast', 'webinar', 'course', 'infographic',
                );

                $query_args = array(
                    'numberposts' => $latest_count,
                    'post_status' => 'publish',
                    'post_type'   => $cpt_post_types,
                    'orderby'     => 'date',
                    'order'       => 'DESC',
                );
                if ($latest_cat > 0) {
                    $query_args['category'] = $latest_cat;
                }
                $latest_posts = get_posts($query_args);
                ?>
    <!-- Enhanced Pathway Split View Section -->
    <?php
    $pathway_hover_color    = vance_get_theme_mod('vance_pathway_card_hover_color', '#008080');
    $pathway_icon_bg        = vance_get_theme_mod('vance_pathway_icon_bg_color', '#0A1929');
    $pathway_icon_hover_bg  = vance_get_theme_mod('vance_pathway_icon_hover_bg_color', 'rgba(255,255,255,0.2)');
    $pathway_who_label      = vance_get_theme_mod('vance_pathway_who_label', 'Who Am I?');
    $pathway_section_bg     = vance_get_theme_mod('vance_pathway_section_bg', '#f8fafc');
    ?>
    <style>
        .pathway-tiles-section {
            padding: 80px 0 60px;
            background: <?php echo esc_attr($pathway_section_bg); ?>;
        }
        .pathway-split-grid {
            display: grid;
            grid-template-columns: 3fr 7fr;
            gap: 40px;
            align-items: stretch;
        }
        .pathway-card-icon {
            width: 28px;
            /* Initial icon colour controlled by Customizer - use CSS filter to tint to icon_bg colour */
            filter: brightness(0) invert(1);
            opacity: 0.9;
            transition: filter 0.3s ease, opacity 0.3s ease;
        }
        .pathway-card:hover .pathway-card-icon {
            filter: brightness(0) invert(1);
            opacity: 1;
        }
        .pathway-card {
            text-decoration: none;
            display: flex;
            flex-direction: column;
            background: white;
            padding: 20px 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1.5px solid #e2e8f0;
            border-radius: 0;
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
            justify-content: space-between;
        }
        .pathway-card:hover {
            background: <?php echo esc_attr($pathway_hover_color); ?>;
            border-color: <?php echo esc_attr($pathway_hover_color); ?>;
            transform: translateY(-4px);
            box-shadow: 0 20px 45px rgba(0,0,0,0.12);
        }
        .pathway-card:hover h2,
        .pathway-card:hover p {
            color: white !important;
        }
        .pathway-card:hover .pathway-icon-wrap {
            background: <?php echo esc_attr($pathway_icon_hover_bg); ?> !important;
        }
        @media (max-width: 992px) {
            .pathway-split-grid { grid-template-columns: 1fr; }
            .bento-grid-news { grid-template-columns: 1fr; grid-template-rows: auto; }
            .pathway-tiles-section { padding-top: 60px; margin-top: 0; }
        }
    </style>
    <section class="pathway-tiles-section">
        <div class="container">
            <div class="pathway-split-grid">
                <!-- Left: Stacked Tiles -->
                <div class="pathway-tiles-stack" style="<?php echo $pwc_tools_stack_style; ?>">
                    <!-- Section Label: Who Am I? -->
                    <div class="section-label" style="margin-bottom: 24px; border-bottom: none; padding-bottom: 0;">
                        <div class="section-label-left">
                            <div class="color-bar" style="background: var(--primary-color); height: 20px;"></div>
                            <h2 style="font-size: 20px; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; font-family: 'Outfit', sans-serif; margin: 0; line-height: 20px; color: #0f172a;"><?php echo esc_html($pathway_who_label); ?></h2>
                        </div>
                    </div>
                    <!-- For Practitioners -->
                    <?php 
                    $prac_title = vance_get_theme_mod('vance_practitioner_tile_title', 'For Practitioners');
                    $prac_desc = vance_get_theme_mod('vance_practitioner_tile_desc', 'Access clinical reviews, evidence-based guidelines, and professional tools tailored for modern healthcare practitioners.');
                    $prac_extra = vance_get_theme_mod('vance_practitioner_tile_extra', 'Bridging science and clinical outcomes');
                    $prac_img = vance_get_theme_mod('vance_practitioner_tile_image');
                    $prac_link = vance_get_theme_mod('vance_practitioner_tile_link', '/healthcare-professionals/');
                    $prac_tile_radius = vance_get_theme_mod('vance_practitioner_tile_radius', 16);
                    $prac_img_radius = vance_get_theme_mod('vance_practitioner_image_radius', 8);
                    ?>
                    <a href="<?php echo esc_url($prac_link); ?>" class="pathway-card" style="border-radius: 0; flex: 1;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 14px;">
                                <div class="pathway-icon-wrap" style="width: 48px; height: 48px; background: <?php echo esc_attr($pathway_icon_bg); ?>; border-radius: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background 0.3s;">
                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/icons/icon-practitioner-new.png" alt="Practitioner" class="pathway-card-icon"> 
                                </div>
                                <h2 style="font-size: 22px; font-weight: 800; color: #0A1929; margin: 0; font-family: 'Outfit', sans-serif;"><?php echo esc_html($prac_title); ?></h2>
                            </div>
                            <p style="color: #64748b; font-size: 14px; margin: 0 0 10px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?php echo esc_html($prac_desc); ?></p>
                            <p style="font-weight: 700; color: var(--primary-color); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0px;"><?php echo esc_html($prac_extra); ?></p>
                        </div>
                        <?php if ($prac_img) : ?>
                        <div style="width: 100%; height: 80px; background: url('<?php echo esc_url($prac_img); ?>') center center / cover; border-radius: 0; margin-top: 10px;"></div>
                        <?php endif; ?>
                    </a>

                    <!-- For Patients -->
                    <?php 
                    $pat_title = vance_get_theme_mod('vance_patient_tile_title', 'For Patients');
                    $pat_desc = vance_get_theme_mod('vance_patient_tile_desc', 'Learn about chronic conditions, health optimization, and healthy living through our expert-led patient curriculum.');
                    $pat_extra = vance_get_theme_mod('vance_patient_tile_extra', 'Empowering your health journey daily');
                    $pat_img = vance_get_theme_mod('vance_patient_tile_image');
                    $pat_link = vance_get_theme_mod('vance_patient_tile_link', '/patients/');
                    $pat_tile_radius = vance_get_theme_mod('vance_patient_tile_radius', 16);
                    $pat_img_radius = vance_get_theme_mod('vance_patient_image_radius', 8);
                    ?>
                    <a href="<?php echo esc_url($pat_link); ?>" class="pathway-card" style="border-radius: 0; flex: 1;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 14px;">
                                <div class="pathway-icon-wrap" style="width: 48px; height: 48px; background: <?php echo esc_attr($pathway_icon_bg); ?>; border-radius: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background 0.3s;">
                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/icons/icon-patient-new.png" alt="Patient" class="pathway-card-icon">
                                </div>
                                <h2 style="font-size: 22px; font-weight: 800; color: #0A1929; margin: 0; font-family: 'Outfit', sans-serif;"><?php echo esc_html($pat_title); ?></h2>
                            </div>
                            <p style="color: #64748b; font-size: 14px; margin: 0 0 10px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?php echo esc_html($pat_desc); ?></p>
                            <p style="font-weight: 700; color: var(--primary-color); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0px;"><?php echo esc_html($pat_extra); ?></p>
                        </div>
                        <?php if ($pat_img) : ?>
                        <div style="width: 100%; height: 80px; background: url('<?php echo esc_url($pat_img); ?>') center center / cover; border-radius: 0; margin-top: 10px;"></div>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Right: Latest Content Bento -->
                <div class="latest-content-column">
                    <div class="section-label" style="margin-bottom: 24px; border-bottom: none; padding-bottom: 0;">
                        <div class="section-label-left">
                            <div class="color-bar" style="background: var(--primary-color); height: 20px;"></div>
                            <h2 style="font-size: 20px; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; font-family: 'Outfit', sans-serif; margin: 0; line-height: 20px; color: #0f172a;"><?php echo esc_html($latest_title); ?></h2>
                        </div>
                    </div>

                    <?php if (!empty($latest_posts) && count($latest_posts) >= 3) : ?>
                    <div class="bento-grid-news">
                        <?php $p = $latest_posts[0]; ?>
                        <a href="<?php echo get_permalink($p->ID); ?>" class="bento-cell-featured">
                            <img src="<?php echo get_the_post_thumbnail_url($p->ID, 'large') ?: 'https://via.placeholder.com/800x600'; ?>" alt="">
                            <div class="bento-content-overlay">
                                <span class="tag" style="background: var(--primary-color);">Featured</span>
                                <h3 style="font-size: 28px; color: white; margin-bottom: 12px;"><?php echo get_the_title($p->ID); ?></h3>
                                <?php if ($show_date) : ?>
                                <div class="meta" style="color: rgba(255,255,255,0.8);">By <?php echo get_the_author_meta('display_name', $p->post_author); ?> &bull; <?php echo get_the_date('', $p->ID); ?></div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div style="display: flex; flex-direction: column; gap: 24px; grid-row: 1 / -1;">
                            <?php for ($i = 1; $i <= 2; $i++) : $p = $latest_posts[$i]; ?>
                            <a href="<?php echo get_permalink($p->ID); ?>" class="bento-cell-side">
                                <span class="meta" style="color: var(--primary-color); margin-bottom: 8px;"><?php $cats = get_the_category($p->ID); echo !empty($cats) ? esc_html($cats[0]->name) : 'Latest'; ?></span>
                                <h4 class="heading-small"><?php echo get_the_title($p->ID); ?></h4>
                                <p class="text-body" style="font-size: 13px; margin-bottom: 8px;"><?php echo wp_trim_words(get_the_excerpt($p->ID), 12); ?></p>
                                <?php if ($show_date) : ?>
                                <div class="meta"><?php echo human_time_diff(get_post_time('U', false, $p->ID), current_time('timestamp')) . ' ago'; ?></div>
                                <?php endif; ?>
                            </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php elseif (!empty($latest_posts)) : ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($latest_posts as $p) : ?>
                            <a href="<?php echo get_permalink($p->ID); ?>" class="bento-cell-side">
                                <span class="meta" style="color: var(--primary-color); margin-bottom: 8px;"><?php $cats = get_the_category($p->ID); echo !empty($cats) ? esc_html($cats[0]->name) : 'Latest'; ?></span>
                                <h4 class="heading-small"><?php echo get_the_title($p->ID); ?></h4>
                                <p class="text-body" style="font-size: 13px; margin-bottom: 8px;"><?php echo wp_trim_words(get_the_excerpt($p->ID), 12); ?></p>
                                <?php if ($show_date) : ?>
                                <div class="meta"><?php echo human_time_diff(get_post_time('U', false, $p->ID), current_time('timestamp')) . ' ago'; ?></div>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div style="background: white; border-radius: 0; padding: 40px; text-align: center; border: 1px solid #e2e8f0;">
                            <p style="color: #64748b; margin: 0;">No posts found for this selection.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
                <?php
                break;

            case 'pathway_content':
                // Cloned from 'pathway' (Who Am I? tiles + Latest Content bento).
                // Replaces the two left-side audience cards with TOOL cards:
                // - Healthcare Quiz  -> /healthcare-quiz/
                // - Ask AI            -> /ask-ai/
                // Each card now gives the image full visual weight (180px hero strip,
                // not the original 80px afterthought) since this block is image-led.
                $pwc_hover_color    = vance_get_theme_mod('vance_pwc_card_hover_color', '#008080');
                $pwc_icon_bg        = vance_get_theme_mod('vance_pwc_icon_bg_color', '#0A1929');
                $pwc_icon_hover_bg  = vance_get_theme_mod('vance_pwc_icon_hover_bg_color', 'rgba(255,255,255,0.2)');
                $pwc_label          = vance_get_theme_mod('vance_pwc_label', 'Featured Tools');
                $pwc_section_bg     = vance_get_theme_mod('vance_pwc_section_bg', '#ffffff');
                $pwc_latest_title   = vance_get_theme_mod('vance_pwc_latest_title', 'LATEST CONTENT');
                $pwc_latest_count   = vance_get_theme_mod('vance_pwc_latest_count', 3);
                $pwc_latest_cat     = (int) vance_get_theme_mod('vance_pwc_latest_category', 0);
                $pwc_show_date      = vance_get_theme_mod('vance_pwc_latest_show_date', true);

                // 2026-05-26: layout select - 'left' (tools beside content, tools on left,
                // historical default), 'right' (tools beside content, tools on right),
                // 'stacked' (tools full-width row above the content list).
                $pwc_layout = vance_get_theme_mod('vance_pwc_layout', 'left');
                if ( ! in_array( $pwc_layout, array( 'left', 'right', 'stacked' ), true ) ) { $pwc_layout = 'left'; }

                // 2026-05-26: banner style + colour controls. style = card (default,
                // existing 2-card layout), image_text (horizontal banner), image
                // (image-led banner with overlay), pill (compact pill).
                $pwc_style                 = vance_get_theme_mod( 'vance_pwc_style', 'card' );
                if ( ! in_array( $pwc_style, array( 'card', 'image_text', 'image', 'pill' ), true ) ) { $pwc_style = 'card'; }
                $pwc_section_label_color   = vance_get_theme_mod( 'vance_pwc_section_label_color',   '#0f172a' );
                $pwc_card_title_color      = vance_get_theme_mod( 'vance_pwc_card_title_color',      '#0A1929' );
                $pwc_card_title_hover_color= vance_get_theme_mod( 'vance_pwc_card_title_hover_color','#ffffff' );
                $pwc_card_desc_color       = vance_get_theme_mod( 'vance_pwc_card_desc_color',       '#64748b' );
                $pwc_card_eyebrow_color    = vance_get_theme_mod( 'vance_pwc_card_eyebrow_color',    '#008080' );
                $pwc_tools_column_bg       = vance_get_theme_mod( 'vance_pwc_tools_column_bg',       '' );

                // Inline style for the tools column wrapper. When a bg is set,
                // include vertical+horizontal padding so the colour reads as a
                // coloured block instead of a sliver behind the cards.
                $pwc_tools_stack_style = 'display: flex; flex-direction: column; gap: 24px; height: 100%;';
                if ( $pwc_tools_column_bg !== '' ) {
                    $pwc_tools_stack_style .= ' background: ' . esc_attr( $pwc_tools_column_bg ) . '; padding: 24px;';
                }

                $pwc_cpt = array(
                    'post', 'news', 'research', 'oped', 'review',
                    'whitepaper', 'podcast', 'webinar', 'course', 'infographic',
                );
                $pwc_args = array(
                    'numberposts' => $pwc_latest_count,
                    'post_status' => 'publish',
                    'post_type'   => $pwc_cpt,
                    'orderby'     => 'date',
                    'order'       => 'DESC',
                );
                if ($pwc_latest_cat > 0) { $pwc_args['category'] = $pwc_latest_cat; }
                $pwc_latest_posts = get_posts($pwc_args);
                ?>
    <!-- Pathway Content (Featured Tools + Latest Content) -->
    <style>
        .pathway-content-section {
            padding: 80px 0 60px;
            background: <?php echo esc_attr($pwc_section_bg); ?>;
        }
        /* Local copy of the split-grid rules — the originals live in the
           'pathway' case and aren't emitted when pathway is hidden, so this
           block needs its own copy. */
        .pathway-content-section .pathway-split-grid {
            display: grid;
            grid-template-columns: 3fr 7fr;
            gap: 40px;
            align-items: stretch;
        }
        /* Layout: tools on the RIGHT - swap visual order via grid-column overrides
           so the DOM stays in the same order (a11y + SEO friendly). */
        .pathway-content-section.layout-right .pathway-split-grid {
            grid-template-columns: 7fr 3fr;
        }
        .pathway-content-section.layout-right .pathway-tiles-stack    { grid-column: 2; }
        .pathway-content-section.layout-right .latest-content-column  { grid-column: 1; grid-row: 1; }
        /* Layout: STACKED - tools row across the top, content list below. */
        .pathway-content-section.layout-stacked .pathway-split-grid {
            grid-template-columns: 1fr;
            gap: 48px;
        }
        .pathway-content-section.layout-stacked .pathway-tiles-stack {
            flex-direction: row !important;
            flex-wrap: wrap;
            height: auto !important;
            align-items: stretch;
        }
        .pathway-content-section.layout-stacked .pathway-tiles-stack > .section-label {
            flex-basis: 100%;
        }
        .pathway-content-section.layout-stacked .pwc-card { flex: 1 1 0 !important; min-width: 240px; }
        .pathway-content-section .pathway-tiles-stack {
            display: flex;
            flex-direction: column;
            gap: 24px;
            height: 100%;
        }
        /* The label is a flex item inside .pathway-tiles-stack, so the
           container's gap:24px already provides spacing to the next item.
           Zero out the label's own margin-bottom on THIS block so the first
           tool card starts at the same Y as the featured news card on the
           right (which has no flex gap). */
        .pathway-content-section .pathway-tiles-stack > .section-label { margin-bottom: 0 !important; }
        @media (max-width: 992px) {
            .pathway-content-section .pathway-split-grid { grid-template-columns: 1fr; }
            .pathway-content-section.layout-right .pathway-split-grid { grid-template-columns: 1fr; }
            /* On mobile every layout collapses to a single column with tools on top. */
            .pathway-content-section.layout-right .pathway-tiles-stack   { grid-column: 1; grid-row: 1; }
            .pathway-content-section.layout-right .latest-content-column { grid-column: 1; grid-row: 2; }
            .pathway-content-section.layout-stacked .pathway-tiles-stack { flex-direction: column !important; }
            .pathway-content-section .bento-grid-news { grid-template-columns: 1fr; grid-template-rows: auto; }
        }
        .pwc-card {
            text-decoration: none;
            display: flex;
            flex-direction: column;
            background: white;
            padding: 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1.5px solid #e2e8f0;
            border-radius: 0;
            transition: all 0.3s ease;
            overflow: hidden;
            /* flex:1 inside the height:100% stack splits available column
               height between the two cards so they both end at the same Y
               as the featured news card on the right. */
            flex: 1;
            min-height: 0;
        }
        .pwc-card:hover {
            background: <?php echo esc_attr($pwc_hover_color); ?>;
            border-color: <?php echo esc_attr($pwc_hover_color); ?>;
            transform: translateY(-4px);
            box-shadow: 0 20px 45px rgba(0,0,0,0.12);
        }
        .pwc-card-image {
            width: 100%;
            /* Was 180px — pushed each card to ~340px which made the stack
               overflow the right column. Now a tight 70px strip, so each
               card is ~150px and the two fit cleanly inside the featured
               news card's vertical envelope. */
            height: 70px;
            background-position: center center;
            background-size: cover;
            background-repeat: no-repeat;
            background-color: <?php echo esc_attr($pwc_icon_bg); ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .pwc-card-image .pwc-fallback-icon {
            font-size: 28px;
            color: #ffffff;
            opacity: 0.85;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            letter-spacing: 0.5px;
        }
        .pwc-card-body {
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            justify-content: space-between;
        }
        .pwc-card-body p {
            -webkit-line-clamp: 2;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .pwc-card:hover .pwc-card-body h2,
        .pwc-card:hover .pwc-card-body p {
            color: white !important;
        }
        @media (max-width: 992px) {
            .pathway-content-section { padding-top: 60px; }
            .pwc-card-image { height: 60px; }
        }
    </style>
    <section class="pathway-content-section layout-<?php echo esc_attr($pwc_layout); ?>">
        <div class="container">
            <div class="pathway-split-grid">
                <!-- Tools column (style: card / image_text / image / pill; position controlled by layout-* on parent section) -->
                <div class="pathway-tiles-stack" style="display: flex; flex-direction: column; gap: 24px; height: 100%;">
                    <!-- Section Label: Featured Tools -->
                    <div class="section-label" style="margin-bottom: 24px; border-bottom: none; padding-bottom: 0;">
                        <div class="section-label-left">
                            <div class="color-bar" style="background: var(--primary-color); height: 20px;"></div>
                            <h2 style="font-size: 20px; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; font-family: 'Outfit', sans-serif; margin: 0; line-height: 20px; color: <?php echo esc_attr( $pwc_section_label_color ); ?>;"><?php echo esc_html($pwc_label); ?></h2>
                        </div>
                    </div>

                    <?php
                    // Build the two tool cards into a normalised array, then render
                    // each one in whichever style the admin selected.
                    $pwc_cards = array(
                        array(
                            'title'         => vance_get_theme_mod('vance_hquiz_tile_title', 'Healthcare Quiz'),
                            'desc'          => vance_get_theme_mod('vance_hquiz_tile_desc',  'A 2-minute interactive quiz that points you to the most relevant tools, resources, and content for your situation.'),
                            'eyebrow'       => vance_get_theme_mod('vance_hquiz_tile_extra', 'Find your starting point'),
                            'image'         => vance_get_theme_mod('vance_hquiz_tile_image'),
                            'link'          => vance_get_theme_mod('vance_hquiz_tile_link',  '/healthcare-quiz/'),
                            'fallback_icon' => '?',
                        ),
                        array(
                            'title'         => vance_get_theme_mod('vance_askai_tile_title', 'Ask AI'),
                            'desc'          => vance_get_theme_mod('vance_askai_tile_desc',  'Ask any health question and get an evidence-backed answer in seconds. Powered by curated clinical content, available 24/7.'),
                            'eyebrow'       => vance_get_theme_mod('vance_askai_tile_extra', 'Personalised answers, 24/7'),
                            'image'         => vance_get_theme_mod('vance_askai_tile_image'),
                            'link'          => vance_get_theme_mod('vance_askai_tile_link',  '/ask-ai/'),
                            'fallback_icon' => 'AI',
                        ),
                    );

                    // Inline styles for the hover-state title colour. The base
                    // .pwc-card and banner :hover rules live in the existing
                    // <style> block at the top of the case; we override the
                    // title colour on hover via a scoped CSS rule per render.
                    ?>
                    <style>
                        .pathway-content-section .pwc-card:hover .pwc-card-title,
                        .pathway-content-section .pwc-banner:hover .pwc-banner-title { color: <?php echo esc_attr( $pwc_card_title_hover_color ); ?> !important; }
                        .pathway-content-section .pwc-banner {
                            display: flex;
                            text-decoration: none;
                            transition: transform 0.2s ease, box-shadow 0.2s ease;
                            flex: 1;
                            min-height: 0;
                        }
                        .pathway-content-section .pwc-banner:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(0,0,0,0.10); }
                        /* Image-led banner */
                        .pathway-content-section .pwc-banner--image > div { position: relative; overflow: hidden; padding: 26px 24px; color: #ffffff; min-height: 160px; flex: 1; }
                        .pathway-content-section .pwc-banner--image::after { content: ''; display: block; }
                        /* Pill banner */
                        .pathway-content-section .pwc-banner--pill > div { background: #ffffff; border: 1.5px solid #0A1929; padding: 16px 18px; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; min-height: 72px; flex: 1; }
                        /* Horizontal image+text banner */
                        .pathway-content-section .pwc-banner--image_text > div { display: flex; align-items: center; gap: 18px; padding: 22px; color: #ffffff; min-height: 140px; flex: 1; }
                    </style>

                    <?php foreach ( $pwc_cards as $card ) :
                        $c_title = esc_html( $card['title'] );
                        $c_desc  = esc_html( $card['desc'] );
                        $c_eye   = esc_html( $card['eyebrow'] );
                        $c_img   = $card['image'];
                        $c_link  = esc_url( $card['link'] );
                        $c_fi    = esc_html( $card['fallback_icon'] );

                        if ( $pwc_style === 'image' ) :
                            // Image-led banner with dark overlay. Falls back to a flat
                            // teal background when no image set.
                            $bg = $c_img
                                ? "background-image: linear-gradient(135deg, rgba(10,25,41,0.55) 0%, rgba(10,25,41,0.90) 100%), url('" . esc_url( $c_img ) . "'); background-size: cover; background-position: center;"
                                : "background: linear-gradient(135deg, " . esc_attr( $pwc_card_eyebrow_color ) . " 0%, #0A1929 100%);";
                    ?>
                        <a href="<?php echo $c_link; ?>" class="pwc-banner pwc-banner--image">
                            <div style="<?php echo $bg; ?>">
                                <?php if ( $c_eye !== '' ) : ?>
                                    <div style="display: inline-block; padding: 4px 10px; background: <?php echo esc_attr( $pwc_card_eyebrow_color ); ?>; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 12px;"><?php echo $c_eye; ?></div>
                                <?php endif; ?>
                                <h3 class="pwc-banner-title" style="margin: 0 0 6px; font-size: 22px; font-weight: 800; color: #ffffff; line-height: 1.15; font-family: 'Outfit', sans-serif;"><?php echo $c_title; ?></h3>
                                <p style="margin: 0; font-size: 13px; opacity: 0.9; max-width: 320px; line-height: 1.5;"><?php echo $c_desc; ?></p>
                            </div>
                        </a>
                    <?php elseif ( $pwc_style === 'pill' ) : ?>
                        <a href="<?php echo $c_link; ?>" class="pwc-banner pwc-banner--pill">
                            <div>
                                <div style="display: flex; align-items: flex-start; gap: 12px; min-width: 0; flex: 1;">
                                    <span style="flex-shrink: 0; width: 32px; height: 32px; background: <?php echo esc_attr( $pwc_card_eyebrow_color ); ?>; color: #ffffff; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; font-family: 'Outfit', sans-serif; margin-top: 2px;"><?php echo $c_fi; ?></span>
                                    <div style="min-width: 0; flex: 1;">
                                        <div class="pwc-banner-title" style="font-size: 14px; font-weight: 700; color: <?php echo esc_attr( $pwc_card_title_color ); ?>; font-family: 'Outfit', sans-serif; line-height: 1.3;"><?php echo $c_title; ?></div>
                                        <div style="font-size: 12px; color: <?php echo esc_attr( $pwc_card_desc_color ); ?>; line-height: 1.4; margin-top: 2px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?php echo $c_desc; ?></div>
                                    </div>
                                </div>
                                <span style="background: <?php echo esc_attr( $pwc_card_eyebrow_color ); ?>; color: #ffffff; padding: 8px 14px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; white-space: nowrap; flex-shrink: 0; margin-top: 2px;">Open &rarr;</span>
                            </div>
                        </a>
                    <?php elseif ( $pwc_style === 'image_text' ) : ?>
                        <a href="<?php echo $c_link; ?>" class="pwc-banner pwc-banner--image_text">
                            <div style="background: linear-gradient(135deg, <?php echo esc_attr( $pwc_card_eyebrow_color ); ?> 0%, #0A1929 100%);">
                                <div style="flex-shrink: 0; width: 64px; height: 64px; background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.22); display: flex; align-items: center; justify-content: center;">
                                    <?php if ( $c_img ) : ?>
                                        <img src="<?php echo esc_url( $c_img ); ?>" alt="" style="width: 36px; height: 36px; object-fit: contain; filter: brightness(0) invert(1);">
                                    <?php else : ?>
                                        <span style="color: #ffffff; font-size: 22px; font-weight: 800; font-family: 'Outfit', sans-serif;"><?php echo $c_fi; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <?php if ( $c_eye !== '' ) : ?>
                                        <div style="font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; opacity: 0.7; margin-bottom: 4px;"><?php echo $c_eye; ?></div>
                                    <?php endif; ?>
                                    <h3 class="pwc-banner-title" style="margin: 0 0 6px; font-size: 20px; font-weight: 800; color: #ffffff; font-family: 'Outfit', sans-serif;"><?php echo $c_title; ?></h3>
                                    <p style="margin: 0; font-size: 13px; opacity: 0.88; line-height: 1.4;"><?php echo $c_desc; ?></p>
                                </div>
                            </div>
                        </a>
                    <?php else : // 'card' (default — existing 2-card stacked layout) ?>
                        <a href="<?php echo $c_link; ?>" class="pwc-card" style="flex: 1;">
                            <div class="pwc-card-image" style="<?php echo $c_img ? 'background-image: url(\'' . esc_url( $c_img ) . '\');' : ''; ?>">
                                <?php if ( ! $c_img ) : ?>
                                    <span class="pwc-fallback-icon"><?php echo $c_fi; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="pwc-card-body">
                                <div>
                                    <h2 class="pwc-card-title" style="font-size: 22px; font-weight: 800; color: <?php echo esc_attr( $pwc_card_title_color ); ?>; margin: 0 0 10px 0; font-family: 'Outfit', sans-serif; transition: color 0.2s ease;"><?php echo $c_title; ?></h2>
                                    <p style="color: <?php echo esc_attr( $pwc_card_desc_color ); ?>; font-size: 14px; margin: 0 0 10px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?php echo $c_desc; ?></p>
                                </div>
                                <p style="font-weight: 700; color: <?php echo esc_attr( $pwc_card_eyebrow_color ); ?>; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;"><?php echo $c_eye; ?></p>
                            </div>
                        </a>
                    <?php endif; endforeach; ?>
                </div>

                <!-- Right: Latest Content Bento (independent customizer settings — vance_pwc_latest_*) -->
                <div class="latest-content-column">
                    <div class="section-label" style="margin-bottom: 24px; border-bottom: none; padding-bottom: 0;">
                        <div class="section-label-left">
                            <div class="color-bar" style="background: var(--primary-color); height: 20px;"></div>
                            <h2 style="font-size: 20px; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; font-family: 'Outfit', sans-serif; margin: 0; line-height: 20px; color: #0f172a;"><?php echo esc_html($pwc_latest_title); ?></h2>
                        </div>
                    </div>

                    <?php if (!empty($pwc_latest_posts) && count($pwc_latest_posts) >= 3) : ?>
                    <div class="bento-grid-news">
                        <?php $p = $pwc_latest_posts[0]; ?>
                        <a href="<?php echo get_permalink($p->ID); ?>" class="bento-cell-featured">
                            <img src="<?php echo get_the_post_thumbnail_url($p->ID, 'large') ?: 'https://via.placeholder.com/800x600'; ?>" alt="">
                            <div class="bento-content-overlay">
                                <span class="tag" style="background: var(--primary-color);">Featured</span>
                                <h3 style="font-size: 28px; color: white; margin-bottom: 12px;"><?php echo get_the_title($p->ID); ?></h3>
                                <?php if ($pwc_show_date) : ?>
                                <div class="meta" style="color: rgba(255,255,255,0.8);">By <?php echo get_the_author_meta('display_name', $p->post_author); ?> &bull; <?php echo get_the_date('', $p->ID); ?></div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div style="display: flex; flex-direction: column; gap: 24px; grid-row: 1 / -1;">
                            <?php for ($i = 1; $i <= 2; $i++) : $p = $pwc_latest_posts[$i]; ?>
                            <a href="<?php echo get_permalink($p->ID); ?>" class="bento-cell-side">
                                <span class="meta" style="color: var(--primary-color); margin-bottom: 8px;"><?php $cats = get_the_category($p->ID); echo !empty($cats) ? esc_html($cats[0]->name) : 'Latest'; ?></span>
                                <h4 class="heading-small"><?php echo get_the_title($p->ID); ?></h4>
                                <p class="text-body" style="font-size: 13px; margin-bottom: 8px;"><?php echo wp_trim_words(get_the_excerpt($p->ID), 12); ?></p>
                                <?php if ($pwc_show_date) : ?>
                                <div class="meta"><?php echo human_time_diff(get_post_time('U', false, $p->ID), current_time('timestamp')) . ' ago'; ?></div>
                                <?php endif; ?>
                            </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php elseif (!empty($pwc_latest_posts)) : ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($pwc_latest_posts as $p) : ?>
                            <a href="<?php echo get_permalink($p->ID); ?>" class="bento-cell-side">
                                <span class="meta" style="color: var(--primary-color); margin-bottom: 8px;"><?php $cats = get_the_category($p->ID); echo !empty($cats) ? esc_html($cats[0]->name) : 'Latest'; ?></span>
                                <h4 class="heading-small"><?php echo get_the_title($p->ID); ?></h4>
                                <p class="text-body" style="font-size: 13px; margin-bottom: 8px;"><?php echo wp_trim_words(get_the_excerpt($p->ID), 12); ?></p>
                                <?php if ($pwc_show_date) : ?>
                                <div class="meta"><?php echo human_time_diff(get_post_time('U', false, $p->ID), current_time('timestamp')) . ' ago'; ?></div>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div style="background: white; border-radius: 0; padding: 40px; text-align: center; border: 1px solid #e2e8f0;">
                            <p style="color: #64748b; margin: 0;">No posts found for this selection.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
                <?php
                break;

            case 'promo':
                if (vance_get_theme_mod('vance_promo_show', false)) :
                    $promo_h = vance_get_theme_mod('vance_promo_heading', 'Experience the Hub');
                    $promo_t = vance_get_theme_mod('vance_promo_text', '');
                    $promo_img = vance_get_theme_mod('vance_promo_image');
                    $promo_bg = vance_get_theme_mod('vance_promo_bg_color', '#F8FAFC');
                    $promo_txt_c = vance_get_theme_mod('vance_promo_text_color', '#0F172A');
                    $promo_btn_t = vance_get_theme_mod('vance_promo_button_text', 'Get Started Now');
                    $promo_btn_l = vance_get_theme_mod('vance_promo_button_link', wp_registration_url());
                    $promo_w = vance_get_theme_mod('vance_promo_width', 'container');
                    $promo_l = vance_get_theme_mod('vance_promo_layout', 'right');
                    ?>
    <section class="promo-block-section" style="background-color: <?php echo esc_attr($promo_bg); ?>; color: <?php echo esc_attr($promo_txt_c); ?>;">
        <div class="<?php echo $promo_w === 'container' ? 'container' : 'container-fluid'; ?>">
            <div class="promo-container layout-<?php echo esc_attr($promo_l); ?>">
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
                endif;
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
                <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>" class="vance-category-card" style="text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 12px; background: #0A1929; border-radius: 0; padding: 24px 12px; transition: all 0.3s; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #1E293B; width: 100%; max-width: 160px;">
                    <?php 
                    $cat_icon = $item['icon'] ?: vance_get_category_icon_url($cat->name);
                    ?>
                        <div style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.1); border-radius: 0;">
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
    $disc_ai_text          = vance_get_theme_mod('vance_discovery_ai_label_text', 'AI Clinical Intelligence');
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
    $btn_save_bg     = vance_get_theme_mod('vance_discovery_btn_save_bg',     '');
    $btn_save_color  = vance_get_theme_mod('vance_discovery_btn_save_color',  '#ffffff');
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

            <div class="discovery-panel" style="background: <?php echo esc_attr($panel_bg); ?>; border-radius: 0; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 30px 80px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.08); overflow: hidden; backdrop-filter: blur(20px);">

                <!-- EXPLORE CONTENT TAB -->
                <div class="tab-content active" id="tab-explore">
                    <div class="explore-layout" style="display: flex; min-height: 540px;">
                        
                        <!-- LEFT: Filters -->
                        <div class="explore-filters" style="flex: 1 1 55%; padding: 32px 36px; border-right: 1px solid rgba(255,255,255,0.07); display: flex; flex-direction: column;">

                            <!-- LEFT PANEL HEADER (matches right side) -->
                            <div class="panel-header-bar" style="display: flex; align-items: center; justify-content: space-between; padding: 0 0 14px 0; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, <?php echo esc_attr($border_color); ?>, #cc4400); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
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

                                <!-- READING LEVEL -->
                                <div class="filter-group">
                                    <div class="filter-label">Reading Level</div>
                                    <div class="toggle-row">
                                        <?php 
                                        $all_tags = get_terms(array('taxonomy' => 'post_tag', 'hide_empty' => false));
                                        if (is_wp_error($all_tags)) $all_tags = array();
                                        $reading_tags = array();
                                        foreach($all_tags as $tag) {
                                            if((stripos($tag->name, 'reading-') === 0 || stripos($tag->slug, 'reading-') === 0) && vance_get_theme_mod("vance_discovery_reading_show_{$tag->term_id}")) {
                                                $reading_tags[] = array(
                                                    'tag' => $tag,
                                                    'order' => vance_get_theme_mod("vance_discovery_reading_order_{$tag->term_id}", 10),
                                                    'text' => vance_get_theme_mod("vance_discovery_reading_text_{$tag->term_id}", str_replace('reading-', '', $tag->name))
                                                );
                                            }
                                        }
                                        usort($reading_tags, function($a, $b) { return $a['order'] - $b['order']; });
                                        foreach($reading_tags as $item) : 
                                            $tag = $item['tag'];
                                        ?>
                                        <label class="toggle-item" style="cursor: pointer;">
                                            <input type="checkbox" name="reading_level[]" value="<?php echo esc_attr($tag->slug); ?>" style="display: none;" onchange="this.parentElement.classList.toggle('active', this.checked)">
                                            <div class="toggle-switch"></div>
                                            <span class="toggle-label"><?php echo esc_html($item['text']); ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- HEALTHCARE PATHWAY -->
                                <div class="filter-group">
                                    <div class="filter-label">Healthcare Pathway</div>
                                    <div class="chip-grid">
                                        <?php 
                                        $path_tags = array();
                                        foreach($all_tags as $tag) {
                                            if((stripos($tag->name, 'path-') === 0 || stripos($tag->slug, 'path-') === 0) && vance_get_theme_mod("vance_discovery_path_show_{$tag->term_id}")) {
                                                $path_tags[] = array(
                                                    'tag' => $tag,
                                                    'order' => vance_get_theme_mod("vance_discovery_path_order_{$tag->term_id}", 10),
                                                    'text' => vance_get_theme_mod("vance_discovery_path_text_{$tag->term_id}", str_replace('path-', '', $tag->name))
                                                );
                                            }
                                        }
                                        usort($path_tags, function($a, $b) { return $a['order'] - $b['order']; });
                                        foreach($path_tags as $item) :
                                        ?>
                                        <label class="text-chip" style="margin: 0;">
                                            <input type="checkbox" name="pathway_tag[]" value="<?php echo esc_attr($item['tag']->slug); ?>" style="display: none;" onchange="this.parentElement.classList.toggle('selected', this.checked)">
                                            <span><?php echo esc_html($item['text']); ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- CONTENT TYPE -->
                                <div class="filter-group">
                                    <div class="filter-label">Content Type</div>
                                    <div class="chip-grid">
                                        <?php 
                                        $type_cats = array();
                                        $all_categories = get_categories(array('hide_empty' => false));
                                        foreach($all_categories as $cat) {
                                            if(vance_get_theme_mod("vance_discovery_type_show_{$cat->term_id}")) {
                                                $type_cats[] = array(
                                                    'cat' => $cat,
                                                    'order' => vance_get_theme_mod("vance_discovery_type_order_{$cat->term_id}", 10),
                                                    'text' => vance_get_theme_mod("vance_discovery_type_text_{$cat->term_id}", $cat->name)
                                                );
                                            }
                                        }
                                        usort($type_cats, function($a, $b) { return $a['order'] - $b['order']; });
                                        foreach($type_cats as $item) :
                                        ?>
                                        <label class="text-chip" style="margin: 0;">
                                            <input type="checkbox" name="content_type[]" value="<?php echo esc_attr($item['cat']->slug); ?>" style="display: none;" onchange="this.parentElement.classList.toggle('selected', this.checked)">
                                            <span><?php echo esc_html($item['text']); ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- KEYWORD SEARCH -->
                                <div class="filter-group" style="margin-bottom:0;">
                                    <div class="keyword-row">
                                        <input type="text" name="s" class="keyword-input" placeholder="Keyword Search (Optional)">
                                    </div>
                                </div>

                            </div><!-- /scrollable filter area -->

                            <!-- ACTION ROW — pinned to bottom, aligns with Send button -->
                            <div class="action-row" style="padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 14px;">
                                    <button type="submit" class="btn-go" style="<?php echo $btn_style($btn_go_bg, $btn_go_color); ?>">GO</button>
                                    <button type="reset" class="btn-text" onclick="setTimeout(()=>window.location.reload(), 100)" style="<?php echo $btn_style($btn_clear_bg, $btn_clear_color); ?>">Clear</button>
                                    <button type="button" class="btn-text" onclick="openSaveSearchModal()" style="<?php echo $btn_style($btn_save_bg, $btn_save_color); ?>">Save Search</button>
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
                                <?php if (is_user_logged_in()): ?>
                                <button class="save-btn" id="save-chat-btn" style="padding: 4px 10px; font-size: 10px; flex-shrink: 0;">
                                    <svg viewBox="0 0 24 24" style="width: 12px; height: 12px;"><path d="M17 21v-8H7v8M7 3v5h8M5 3h11l5 5v11a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" /></svg> Save
                                </button>
                                <?php endif; ?>
                            </div>

                            <div class="chat-messages" id="vance-ai-chat-messages" style="flex: 1; padding: 0; max-height: 380px; margin-bottom: 16px;">
                                <div class="msg bot">
                                    <div class="msg-avatar" style="width: 24px; height: 24px;">
                                        <svg viewBox="0 0 24 24" style="width: 14px; height: 14px;"><rect x="3" y="4" width="18" height="12" rx="2" /><line x1="8" y1="20" x2="16" y2="20" /><line x1="12" y1="16" x2="12" y2="20" /></svg>
                                    </div>
                                    <div class="msg-bubble" style="padding: 10px 14px;">Welcome. I can help you explore our IBD content. What would you like to know?</div>
                                </div>
                            </div>

                            <div class="chat-input-bar" style="padding: 14px 0 0 0; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 14px; background: transparent;">
                                <input type="text" id="vance-ai-chat-input" class="chat-input" placeholder="Ask AI..." style="padding: 10px 14px; font-size: 13px;">
                                <button class="chat-send" id="vance-ai-chat-send" style="padding: 10px 18px; font-size: 13px; <?php echo $btn_style($btn_send_bg, $btn_send_color); ?>">Send</button>
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
                                    <div class="agent-name">AI Clinical Intelligence</div>
                                    <div class="agent-status"><span class="status-dot"></span> Online</div>
                                </div>
                            </div>
                            <?php if (is_user_logged_in()): ?>
                            <button class="save-btn" id="save-chat-btn">
                                <svg viewBox="0 0 24 24"><path d="M17 21v-8H7v8M7 3v5h8M5 3h11l5 5v11a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" /></svg> Save Chat
                            </button>
                            <?php endif; ?>
                        </div>

                        <div class="chat-mode-toggle" style="display: flex; gap: 8px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 4px;">
                            <button class="mode-btn active" data-mode="web">Restrict to Web Content</button>
                            <button class="mode-btn" data-mode="research">Research All Published Data</button>
                        </div>

                        <div class="chat-messages" id="vance-ai-chat-messages">
                            <div class="msg bot">
                                <div class="msg-avatar">
                                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="12" rx="2" /><line x1="8" y1="20" x2="16" y2="20" /><line x1="12" y1="16" x2="12" y2="20" /></svg>
                                </div>
                                <div class="msg-bubble">Welcome. I can help you explore our IBD content. What would you like to know?</div>
                            </div>
                        </div>

                        <div class="chat-input-bar">
                            <input type="text" id="vance-ai-chat-input" class="chat-input" placeholder="Ask AI...">
                            <button class="chat-send" id="vance-ai-chat-send">Send</button>
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
            border-radius: 0;
            position: relative;
            transition: 0.3s;
            flex-shrink: 0;
        }
        .toggle-switch::after {
            content: ''; position: absolute;
            top: 2px; left: 2px;
            width: 16px; height: 16px;
            background: <?php echo esc_attr($disc_toggle_off_dot); ?>;
            border-radius: 0;
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
            border-radius: 0;
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
            border-radius: 0; 
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
            border-radius: 0; 
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
            border-radius: 0;
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
        .agent-avatar { width: 34px; height: 34px; background: linear-gradient(135deg, #008080, #FF8500); border-radius: 0; display: flex; align-items: center; justify-content: center; }
        .agent-avatar svg { width: 18px; height: 18px; stroke: white; fill: none; stroke-width: 2; }
        .agent-name { font-family: 'Outfit', sans-serif; font-size: 12px; font-weight: 800; color: white; text-transform: uppercase; letter-spacing: 0.5px; }
        .agent-status { font-size: 10px; color: #22C55E; font-weight: 700; display: flex; gap: 5px; align-items: center; }
        .status-dot { width: 6px; height: 6px; background: #22C55E; border-radius: 0; animation: pulse-dot 2s ease infinite; }
        .save-btn { padding: 6px 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 0; font-size: 11px; font-weight: 700; color: rgba(255, 255, 255, 0.6); cursor: pointer; display: flex; gap: 5px; align-items: center; transition: all 0.2s; }
        .save-btn:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: white; }
        .save-btn svg { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 2; }

        .chat-messages { flex: 1; padding: 0; max-height: 340px; overflow-y: auto; margin-bottom: 16px; scroll-behavior: smooth; }
        .msg { margin-bottom: 14px; display: flex; gap: 10px; }
        .msg.bot { flex-direction: row; }
        .msg.user { flex-direction: row-reverse; }
        .msg-avatar { width: 26px; height: 26px; border-radius: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .msg.bot .msg-avatar { background: rgba(0,128,128,0.25); }
        .msg.bot .msg-avatar svg { width: 14px; height: 14px; stroke: #008080; fill: none; stroke-width: 2; }
        .msg.user .msg-avatar { background: rgba(255,255,255,0.1); }
        .msg.user .msg-avatar svg { width: 14px; height: 14px; stroke: rgba(255,255,255,0.7); fill: none; stroke-width: 2; }
        .msg-bubble { max-width: 85%; padding: 12px 16px; border-radius: 0; font-size: 13px; line-height: 1.6; white-space: pre-wrap; }
        .msg.bot .msg-bubble { background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: <?php echo esc_attr($askai_text_color); ?>; }
        .msg.user .msg-bubble { background: rgba(0,128,128,0.3); border: 1px solid rgba(0,128,128,0.35); color: #ffffff; }

        .mode-btn { padding: 6px 14px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 0; font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.6); cursor: pointer; transition: all 0.2s; white-space: nowrap; }
        .mode-btn:hover { background: rgba(255,255,255,0.1); color: white; }
        .mode-btn.active { background: rgba(0,128,128,0.2); border-color: #008080; color: #FF8040; box-shadow: 0 0 0 1px rgba(0,128,128,0.3); }

        .chat-input-bar { padding: 14px 0 0 0; border-top: 1px solid rgba(255,255,255,0.07); background: transparent; display: flex; gap: 10px; }
        .chat-input { flex: 1; padding: 10px 14px; border: 1px solid <?php echo esc_attr($askai_input_border); ?>; border-radius: 0; font-size: 13px; outline: none; background: <?php echo esc_attr($askai_input_bg); ?>; color: <?php echo esc_attr($askai_input_color); ?>; transition: border-color 0.2s; }
        .chat-input::placeholder { color: rgba(255,255,255,0.3); }
        .chat-input:focus { border-color: rgba(0,128,128,0.5); }
        .chat-send { padding: 10px 18px; background: linear-gradient(135deg, #008080, #FF8500); color: white; border: none; border-radius: 0; font-weight: 700; font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .chat-send:hover { box-shadow: 0 4px 12px rgba(0,128,128,0.4); }
        
        .typing-indicator { display: flex; gap: 4px; padding: 5px 0; }
        .typing-dot { width: 5px; height: 5px; background: rgba(255,255,255,0.3); border-radius: 0; animation: typing 1.4s infinite ease-in-out both; }
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        @keyframes typing { 0%, 80%, 100% { transform: scale(0); opacity: 0.4; } 40% { transform: scale(1); opacity: 1; } }

        /* Scrollbar for chat */
        .chat-messages::-webkit-scrollbar { width: 4px; }
        .chat-messages::-webkit-scrollbar-track { background: transparent; }
        .chat-messages::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 0; }

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
            var chatInput = document.getElementById('vance-ai-chat-input');
            var chatSend = document.getElementById('vance-ai-chat-send');
            var chatMessages = document.getElementById('vance-ai-chat-messages');
            var saveBtn = document.getElementById('save-chat-btn');
            var messages = [];

            function appendMessage(role, text) {
                var mdText = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                var msgDiv = document.createElement('div');
                msgDiv.className = 'msg ' + (role === 'user' ? 'user' : 'bot');
                var avatarHtml = role === 'user' ? '<svg viewBox="0 0 24 24"><path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" /></svg>' : '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="12" rx="2" /><line x1="8" y1="20" x2="16" y2="20" /><line x1="12" y1="16" x2="12" y2="20" /></svg>';
                msgDiv.innerHTML = '<div class="msg-avatar">' + avatarHtml + '</div><div class="msg-bubble">' + mdText + '</div>';
                chatMessages.appendChild(msgDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                if (!text.includes('typing-indicator')) messages.push({role: role, content: text});
            }

            document.querySelectorAll('.mode-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.mode-btn').forEach(function(b) { b.classList.remove('active'); });
                    this.classList.add('active');
                });
            });

            function sendMessage() {
                var text = chatInput.value.trim();
                if(!text) return;
                appendMessage('user', text);
                chatInput.value = '';
                
                var typingDiv = document.createElement('div');
                typingDiv.className = 'msg bot typing';
                typingDiv.id = 'vance-ai-typing';
                typingDiv.innerHTML = '<div class="msg-avatar"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="12" rx="2" /></svg></div><div class="msg-bubble" style="background:transparent; border:none;"><div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div></div>';
                chatMessages.appendChild(typingDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                chatSend.disabled = true;
                
                fetch('<?php echo home_url("/wp-json/vance-health/v1/ai-chat"); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ messages: messages })
                })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('vance-ai-typing').remove();
                    chatSend.disabled = false;
                    if (data.success && data.reply) appendMessage('model', data.reply);
                    else appendMessage('model', "I'm having trouble connecting right now.");
                })
                .catch(err => {
                    document.getElementById('vance-ai-typing').remove();
                    chatSend.disabled = false;
                    appendMessage('model', "Network error encountered.");
                });
            }

            if(chatSend) chatSend.addEventListener('click', sendMessage);
            if(chatInput) chatInput.addEventListener('keypress', function(e) { if(e.key === 'Enter') sendMessage(); });

            if(saveBtn) {
                saveBtn.addEventListener('click', function() {
                    if(messages.length === 0) return alert("No conversation yet.");
                    
                    var dateStr = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                    var chatName = prompt("Enter a name for this chat:", "AI Chat - " + dateStr);
                    if (chatName === null) return; // User cancelled
                    if (chatName.trim() === '') chatName = "AI Chat - " + dateStr;
                    
                    var transcriptHtml = messages.map(m => '<p><strong>' + (m.role==='user'?'You':'AI') + ':</strong><br>' + m.content + '</p>').join('');
                    saveBtn.innerText = "Saving...";
                    
                    fetch('<?php echo home_url("/wp-json/vance-health/v1/save-chat"); ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>'},
                        body: JSON.stringify({ transcript: messages, title: chatName })
                    })
                    .then(r => r.json())
                    .then(d => {
                        if(d.success) saveBtn.innerHTML = "✅ Saved";
                        else saveBtn.innerHTML = "Save Chat";
                    });
                });
            }

            // Global Quiz Interceptor
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a');
                if (link && link.getAttribute('href') && link.getAttribute('href').includes('healthcare-quiz')) {
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
            border-top: 1px solid #E2E8F0;
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
                        <span class="kb-mini-hero__eyebrow" style="display: inline-block; padding: 6px 14px; margin-bottom: 18px; font-family: 'Inter', sans-serif; font-size: <?php echo (int) $kb_eyebrow_size; ?>px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: <?php echo esc_attr($kb_eyebrow_color); ?>; background: <?php echo esc_attr($kb_eyebrow_bg); ?>; border: 1px solid <?php echo esc_attr($kb_eyebrow_border); ?>; border-radius: 0; line-height: 1;"><?php echo esc_html($kb_eyebrow_text); ?></span>
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
            $post_count = ($layout === 'bento' || $layout === 'asymmetric' || $layout === 'posters') ? 3 : intval($sec['count']);
            
            $posts_array = get_posts(array(
                'numberposts' => $post_count,
                'category' => $cat->term_id,
                'orderby' => 'date',
                'order' => 'DESC',
                'post_type' => 'any',
                'post_status' => 'publish',
            ));
            
            if (empty($posts_array)) continue;
            // Accent colour now driven by per-category Customizer setting
            // (Appearance -> Customize -> Vance Theme -> Homepage ->
            // Knowledge Base Section -> "<Cat>" Accent Colour). Deterministic
            // fallback cycles a 5-colour palette by term_id so categories
            // without an explicit pick still get a stable colour across loads.
            $vance_kb_accent_palette = array('#F59E0B', '#0EA5E9', '#008080', '#10B981', '#8B5CF6');
            $kb_accent_default       = $vance_kb_accent_palette[ ((int) $cat->term_id) % count($vance_kb_accent_palette) ];
            $color       = vance_get_theme_mod("vance_kb_accent_{$cat->term_id}", $kb_accent_default);
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
                        <a href="<?php echo get_permalink($p->ID); ?>" class="bento-cell-featured">
                            <img src="<?php echo get_the_post_thumbnail_url($p->ID, 'large') ?: 'https://via.placeholder.com/800x600'; ?>" alt="">
                            <div class="bento-content-overlay">
                                <span class="tag" style="background:<?php echo $color; ?>">Featured</span>
                                <h3 style="font-size: 28px; color: white; margin-bottom: 12px;"><?php echo get_the_title($p->ID); ?></h3>
                                <div class="meta" style="color: rgba(255,255,255,0.8);">By <?php echo get_the_author_meta('display_name', $p->post_author); ?> • <?php echo get_the_date('', $p->ID); ?></div>
                            </div>
                        </a>
                        <div style="display: flex; flex-direction: column; gap: 24px; grid-row: 1 / -1;">
                            <?php for($i=1; $i<=2; $i++): $p = $posts_array[$i]; ?>
                            <a href="<?php echo get_permalink($p->ID); ?>" class="bento-cell-side">
                                <span class="meta" style="color:<?php echo $color; ?>; margin-bottom:8px;"><?php echo $cat->name; ?></span>
                                <h4 class="heading-small"><?php echo get_the_title($p->ID); ?></h4>
                                <p class="text-body" style="font-size: 13px; margin-bottom: 8px;"><?php echo wp_trim_words(get_the_excerpt($p->ID), 12); ?></p>
                                <div class="meta"><?php echo human_time_diff(get_post_time('U', false, $p->ID), current_time('timestamp')) . ' ago'; ?></div>
                            </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px;">
                        <?php foreach ($posts_array as $p): ?>
                        <article style="background: white; border-radius: 0; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; transition: all 0.3s; height: 100%; display: flex; flex-direction: column;">
                            <?php
                                $read_time = vance_get_read_time($p->ID);
                                $view_count = vance_get_view_count($p->ID);
                            ?>
                            <?php if (has_post_thumbnail($p->ID)): ?>
                                <div style="position: relative; overflow: hidden; height: 180px; background: #f1f5f9;">
                                    <img src="<?php echo get_the_post_thumbnail_url($p->ID, 'medium'); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    <div style="position: absolute; top: 10px; left: 12px; color: #ffffff; text-shadow: 0 1px 3px rgba(0,0,0,0.6); font-size: 12px; line-height: 1.3; font-weight: 600; display: flex; flex-direction: column; gap: 6px;">
                                        <div><?php echo get_the_date('', $p->ID); ?></div>
                                        <div style="font-weight: 500; opacity: 0.95;"><?php echo (int) $read_time; ?> min read</div>
                                        <div style="font-weight: 500; opacity: 0.95;"><?php echo number_format($view_count); ?> views</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column;">
                                <h3 style="font-size: 16px; margin-bottom: 10px; line-height: 1.4;">
                                    <a href="<?php echo get_permalink($p->ID); ?>" style="color: #0f172a; text-decoration: none; font-weight: 600;"><?php echo get_the_title($p->ID); ?></a>
                                </h3>
                                <p style="font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo wp_trim_words(get_the_excerpt($p->ID), 15); ?>
                                </p>
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
            $div_color     = vance_get_theme_mod( 'vance_divider_color',     '#e2e8f0' );
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
        .premium-subscribe-section .premium-pill-check { background: rgba(255,255,255,0.10); width: 24px; height: 24px; border-radius: 0; display: flex; align-items: center; justify-content: center; color: <?php echo esc_attr($prem_pill_check); ?>; }
        .premium-subscribe-section .premium-pill { display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; color: <?php echo esc_attr($prem_pill_text); ?>; }
        .premium-subscribe-section .premium-input::placeholder { color: rgba(255,255,255,0.55); }
    </style>
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
            <div style="flex-shrink: 0; background: <?php echo esc_attr($prem_card_bg); ?>; padding: 40px; border-radius: 0; border: 1px solid <?php echo esc_attr($prem_card_border); ?>; max-width: 400px; width: 100%;">
                <h3 style="font-size: 24px; font-weight: 700; margin-bottom: <?php echo $prem_card_subhead ? '8' : '24'; ?>px; color: <?php echo esc_attr($prem_card_heading_color); ?>;"><?php echo esc_html($prem_card_heading); ?></h3>
                <?php if ( $prem_card_subhead ) : ?>
                    <p style="color: <?php echo esc_attr($prem_desc_color); ?>; font-size: 14px; margin-bottom: 24px;"><?php echo esc_html($prem_card_subhead); ?></p>
                <?php endif; ?>
                <form action="<?php echo esc_url($prem_form_action); ?>" method="get" style="display: flex; flex-direction: column; gap: 16px;">
                    <input type="email" name="user_email" placeholder="<?php echo esc_attr($prem_input_ph); ?>" required class="premium-input" style="width: 100%; padding: 16px; border-radius: 0; border: 1px solid rgba(255,255,255,0.2); background: <?php echo esc_attr($prem_input_bg); ?>; color: <?php echo esc_attr($prem_input_color); ?>; font-size: 16px;">
                    <button type="submit" style="width: 100%; padding: 16px; border-radius: 0; border: none; background: <?php echo esc_attr($prem_button_bg); ?>; color: <?php echo esc_attr($prem_button_color); ?>; font-weight: 700; font-size: 16px; cursor: pointer; transition: background 0.2s;"><?php echo esc_html($prem_button_label); ?></button>
                    <?php if ( $prem_card_footnote ) : ?>
                        <p style="text-align: center; font-size: 12px; color: #64748b; margin: 0;"><?php echo esc_html($prem_card_footnote); ?></p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </section>

<?php get_footer(); ?>
