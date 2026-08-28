<?php
/**
 * Template Name: About Us
 *
 * Layout mirrors the approved About mockup (hero → stats → story → evolution
 * timeline → mission → trust → testimonials → digital → CTA), rebuilt on the
 * Vance palette and type stack rather than the mockup's teal/navy + Playfair.
 *
 * Design tokens are declared once on `.vabout` below:
 *   teal #008080 / #006666 hover / #78bfbf on-dark   (main.css --primary-*)
 *   navy #0A1929, text #1F2937 / #6B7280
 *   Outfit 700/500 headings, Inter 400/500/600 body  (both already enqueued;
 *   note only those weights are loaded — do not reach for 800/900 here.)
 *
 * The mockup's rounded geometry (20px cards, pill badges) is scoped to this
 * page and driven by --vab-r-*, which predate the site-wide radius scale in
 * main.css. They now sit alongside it rather than fighting it; --vab-r-sm/md/lg
 * simply run larger than --radius-control/field/surface for this page's look.
 *
 * Every existing `vance_about_*` theme mod is still read, so Customizer content
 * survives this redesign. New settings added for the mockup's imagery are
 * optional — each section degrades gracefully when its image is unset.
 */

get_header(); ?>

<?php
/**
 * Per-section Customizer style overrides (colour/size). Unchanged contract.
 */
function vance_get_style_string($prefix, $default_bg = '') {
    $bg       = get_theme_mod($prefix . '_bg', $default_bg);
    $t_color  = get_theme_mod($prefix . '_title_color');
    $t_size   = get_theme_mod($prefix . '_title_size');
    $tx_color = get_theme_mod($prefix . '_text_color');
    $tx_size  = get_theme_mod($prefix . '_text_size');
    $tag_bg   = get_theme_mod($prefix . '_tag_bg');
    $tag_color= get_theme_mod($prefix . '_tag_color');

    $style = $bg ? "background:$bg;" : '';

    $inner_title_style = '';
    if ($t_color) $inner_title_style .= "color:$t_color !important;";
    if ($t_size) {
        $t_size = is_numeric($t_size) ? $t_size . 'px' : $t_size;
        $inner_title_style .= "font-size:$t_size !important;";
    }

    $inner_text_style = '';
    if ($tx_color) $inner_text_style .= "color:$tx_color !important;";
    if ($tx_size) {
        $tx_size = is_numeric($tx_size) ? $tx_size . 'px' : $tx_size;
        $inner_text_style .= "font-size:$tx_size !important;";
    }

    $inner_tag_style = '';
    if ($tag_bg)    $inner_tag_style .= "background:$tag_bg !important;";
    if ($tag_color) $inner_tag_style .= "color:$tag_color !important;";

    return [
        'section' => $style,
        'title'   => $inner_title_style,
        'text'    => $inner_text_style,
        'tag'     => $inner_tag_style,
    ];
}

/**
 * Inline icon set (Lucide geometry, 24×24 stroke). SVG rather than an icon font
 * so the page keeps working without a webfont request — and per house style,
 * every other template here inlines its icons too.
 */
function vance_about_icon($name, $size = 24, $stroke = 2) {
    $paths = array(
        'check'       => '<path d="M20 6 9 17l-5-5"/>',
        'flask'       => '<path d="M10 2v7.5a2 2 0 0 1-.2.9L4.7 20.6a1 1 0 0 0 .9 1.4h12.8a1 1 0 0 0 .9-1.4l-5.1-10.2a2 2 0 0 1-.2-.9V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/>',
        'globe'       => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
        'shield'      => '<path d="M20 13c0 5-3.5 7.5-7.7 9a1 1 0 0 1-.7 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.2-2.7a1.2 1.2 0 0 1 1.5 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'users'       => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
        'pill'        => '<path d="m10.5 20.5 10-10a5 5 0 1 0-7-7l-10 10a5 5 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/>',
        'lightbulb'   => '<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.8.8 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/>',
        'heartpulse'  => '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7Z"/><path d="M3.2 13h6.3l.5-1 2 4.5 2-7 1.5 3.5h5.3"/>',
        'microscope'  => '<path d="M6 18h8"/><path d="M3 22h18"/><path d="M14 22a7 7 0 1 0 0-14h-1"/><path d="M9 14h2"/><path d="M9 12a2 2 0 0 1-2-2V6h6v4a2 2 0 0 1-2 2Z"/><path d="M12 6V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3"/>',
        'heart'       => '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7Z"/>',
        'award'       => '<circle cx="12" cy="8" r="6"/><path d="M15.5 12.9 17 22l-5-3-5 3 1.5-9.1"/>',
        'book'        => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
        'sparkles'    => '<path d="M9.9 15.5A2 2 0 0 0 8.5 14.1l-6.1-1.6a.5.5 0 0 1 0-1L8.5 9.9A2 2 0 0 0 9.9 8.5l1.6-6.1a.5.5 0 0 1 1 0l1.5 6.1a2 2 0 0 0 1.5 1.4l6.1 1.6a.5.5 0 0 1 0 1l-6.1 1.5a2 2 0 0 0-1.5 1.5l-1.5 6.1a.5.5 0 0 1-1 0z"/>',
        'dashboard'   => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'stethoscope' => '<path d="M11 2v2"/><path d="M5 2v2"/><path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"/><path d="M8 15a6 6 0 0 0 12 0v-3"/><circle cx="20" cy="10" r="2"/>',
        'calculator'  => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8"/><path d="M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>',
        'graduation'  => '<path d="M21.4 10.9a1 1 0 0 0 0-1.8L12.8 5.2a2 2 0 0 0-1.6 0L2.6 9.1a1 1 0 0 0 0 1.8l8.6 3.9a2 2 0 0 0 1.6 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>',
        'lock'        => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'clock'       => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'zap'         => '<path d="M4 14a1 1 0 0 1-.8-1.6l9.9-10.2a.5.5 0 0 1 .9.5l-1.9 6a1 1 0 0 0 .9 1.3h7a1 1 0 0 1 .8 1.6l-9.9 10.2a.5.5 0 0 1-.9-.5l1.9-6a1 1 0 0 0-.9-1.3z"/>',
        'card'        => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
    );

    if (!isset($paths[$name])) return '';

    return sprintf(
        '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%2$s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
        (int) $size,
        esc_attr($stroke),
        $paths[$name]
    );
}

/**
 * Filled star, used for the testimonial rating rows.
 */
function vance_about_star($size = 14) {
    return sprintf(
        '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8-6.2-3.3-6.2 3.3L7 14.2l-5-4.9 6.9-1z"/></svg>',
        (int) $size
    );
}

/**
 * True when a hex colour is light enough that white text would fail on it.
 *
 * The hero and CTA are designed dark, so their copy is white. An admin can
 * override either background from the Customizer, and a light choice would
 * otherwise leave white-on-white text — this lets those sections flip to dark
 * copy instead. Threshold is relative luminance 0.45, which keeps white text
 * above ~4.5:1. Unparseable/empty values report false (assume dark).
 */
function vance_about_is_light_color($hex) {
    if (!is_string($hex)) return false;
    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) return false;

    $srgb = array();
    foreach (array(0, 2, 4) as $o) {
        $c = hexdec(substr($hex, $o, 2)) / 255;
        $srgb[] = ($c <= 0.03928) ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
    }
    $l = 0.2126 * $srgb[0] + 0.7152 * $srgb[1] + 0.0722 * $srgb[2];
    return $l > 0.45;
}

/**
 * Splits a display stat ("10,000+", "100%") into an animatable number and its
 * suffix so the counter can tick up without the admin re-entering anything.
 * Returns null when the value isn't numeric-leading — caller then prints it raw.
 */
function vance_about_split_stat($raw) {
    if (!preg_match('/^\s*([0-9][0-9.,]*)\s*(.*)$/u', $raw, $m)) return null;
    $number   = $m[1];
    $plain    = str_replace(',', '', $number);
    if (!is_numeric($plain)) return null;
    $dot      = strpos($plain, '.');
    return array(
        'target'   => (float) $plain,
        'decimals' => ($dot === false) ? 0 : strlen(substr($plain, $dot + 1)),
        'grouped'  => (strpos($number, ',') !== false),
        'suffix'   => $m[2],
        'display'  => $number,
    );
}
?>

<?php
// Photography shipped with the theme (assets/img/about/), mirroring the approved
// mockup. These are defaults only — anything set in the Customizer wins.
$vabout_img = get_template_directory_uri() . '/assets/img/about/';
?>
<main id="main-content" class="vabout">

    <?php
    /* ============================ HERO ============================ */
    // Two hero designs share this slot, chosen in Customize → Page - About Us →
    // Hero Section → "About hero design":
    //   classic   — the dark navy band below (default; nothing changes on deploy).
    //   spotlight — the light, action-led hero, inc/page-hero-spotlight.php.
    // The spotlight reads the same tag/title/description settings the classic
    // hero uses, and fills its white band from the same Badge 1–3 labels, so
    // switching is a change of layout only.
    $vabout_spotlight = function_exists('vance_page_hero_spotlight_active') && vance_page_hero_spotlight_active('about');

    if (vance_get_theme_mod('vance_about_hero_show', true) && $vabout_spotlight) :
        vance_render_page_hero_spotlight('about');
    elseif (vance_get_theme_mod('vance_about_hero_show', true)) :
        $hero_img      = vance_get_theme_mod('vance_about_hero_img', $vabout_img . 'diverse-patients-clinic.jpg');
        $hero_bg_color = vance_get_theme_mod('vance_about_hero_bg_color');
        $hero_tag      = vance_get_theme_mod('vance_about_hero_tag', 'About Vance Medical Hub');
        $hero_title    = vance_get_theme_mod('vance_about_hero_title', 'Trusted by Patients.<br><span class="highlight">Driven by Science.</span>');
        $hero_desc     = vance_get_theme_mod('vance_about_hero_desc', 'We bridge pharmaceutical expertise with nutritional science to empower patients living with gastrointestinal conditions, delivering evidence-based care you can trust.');
        $styles        = vance_get_style_string('vance_about_hero');

        // Existing per-page overlay slider (0–100, default 78); bottom stop darkens for the vignette.
        $ov     = max(0, min(100, absint(vance_get_theme_mod('vance_about_hero_overlay', 78)))) / 100;
        $ov_bot = min(1, $ov + 0.15);

        $badge_defaults = array(1 => 'Pharma-Grade Quality', 2 => 'Clinician Approved', 3 => 'Evidence-Based');
    ?>
    <?php $hero_light = vance_about_is_light_color($hero_bg_color); ?>
    <section class="vabout-hero<?php echo $hero_light ? ' is-light' : ''; ?>" style="<?php echo $hero_bg_color ? 'background:' . esc_attr($hero_bg_color) . ';' : ''; ?>">
        <?php if (!$hero_bg_color && $hero_img) : ?>
            <div class="vabout-hero-media" style="background-image:url('<?php echo esc_url($hero_img); ?>');" role="presentation"></div>
        <?php endif; ?>
        <?php if (!$hero_bg_color) : ?>
            <div class="vabout-hero-veil" style="background:linear-gradient(135deg, rgba(10,25,41,<?php echo esc_attr($ov); ?>), rgba(19,45,66,<?php echo esc_attr($ov_bot); ?>));" role="presentation"></div>
        <?php endif; ?>

        <div class="container">
            <div class="vabout-hero-inner">
                <span class="vabout-pill vabout-pill-dark" style="<?php echo $styles['tag']; ?>"><?php echo esc_html($hero_tag); ?></span>
                <h1 style="<?php echo $styles['title']; ?>"><?php echo wp_kses_post($hero_title); ?></h1>
                <p class="vabout-hero-sub" style="<?php echo $styles['text']; ?>"><?php echo esc_html($hero_desc); ?></p>

                <?php if (vance_get_theme_mod('vance_about_badges_show', true)) : ?>
                <ul class="vabout-trustbadges">
                    <?php for ($i = 1; $i <= 3; $i++) :
                        $label = vance_get_theme_mod("vance_about_badge{$i}_label", $badge_defaults[$i]);
                        if (!$label) continue; ?>
                        <li><span class="vabout-tb-ico"><?php echo vance_about_icon('check', 16, 2.5); ?></span><?php echo esc_html($label); ?></li>
                    <?php endfor; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>


    <?php
    /* ============================ STATS ============================ */
    if (vance_get_theme_mod('vance_about_stats_show', true)) :
        $stat_defaults = array(
            1 => array('30+',      'Years of Pharmaceutical Experience', 'flask'),
            2 => array('100%',     'Pharma-Grade Standards Compliance',   'shield'),
            3 => array('100,000+', 'Patients Supported Globally',         'users'),
        );
    ?>
    <section class="vabout-stats<?php echo vance_get_theme_mod('vance_about_hero_show', true) ? ' vabout-stats-overlap' : ''; ?>">
        <div class="container">
            <div class="vabout-stats-grid">
                <?php for ($i = 1; $i <= 3; $i++) :
                    $num   = vance_get_theme_mod("vance_about_stat{$i}_num",   $stat_defaults[$i][0]);
                    $label = vance_get_theme_mod("vance_about_stat{$i}_label", $stat_defaults[$i][1]);
                    $parts = vance_about_split_stat($num);
                ?>
                <div class="vabout-stat reveal">
                    <span class="vabout-stat-ico"><?php echo vance_about_icon($stat_defaults[$i][2], 26); ?></span>
                    <p class="vabout-stat-figure">
                        <?php if ($parts) : ?>
                            <span class="vabout-stat-num"
                                  data-count-to="<?php echo esc_attr($parts['target']); ?>"
                                  data-decimals="<?php echo esc_attr($parts['decimals']); ?>"
                                  data-group="<?php echo $parts['grouped'] ? '1' : '0'; ?>"><?php echo esc_html($parts['display']); ?></span><?php if ($parts['suffix']) : ?><span class="vabout-stat-suffix"><?php echo esc_html($parts['suffix']); ?></span><?php endif; ?>
                        <?php else : ?>
                            <span class="vabout-stat-num"><?php echo esc_html($num); ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="vabout-stat-label"><?php echo esc_html($label); ?></p>
                    <span class="vabout-stat-bar" aria-hidden="true"><i></i></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>


    <?php
    /* ===================== STORY (Promo blocks) ===================== */
    $promo_defaults = array(
        1 => array(
            'show'  => true,
            'title' => 'From Pharma Heritage to Patient-Centred Innovation',
            'sub'   => 'Our Story',
            'desc'  => "For over three decades, our team has been at the forefront of gastrointestinal medicine, developing specialised treatments to the highest regulatory standards.\n\nThat deep clinical experience revealed a consistent gap: medicines alone often fall short for chronic gastro conditions. Patients need evidence-based nutritional support alongside standard medical intervention.\n\nVance Medical was founded to bridge that gap, combining pharmaceutical rigour with nutritional science to deliver medical food products and education to both patients and practitioners.",
            'btn_lbl' => '',
            'checks'  => array('Developed under pharmaceutical regulatory frameworks', 'Peer-reviewed clinical evidence base', 'Trusted by gastroenterologists worldwide'),
        ),
        2 => array(
            'show'  => false,
            'title' => 'Promo title',
            'sub'   => 'Promo subtitle',
            'desc'  => 'Promo description text goes here.',
            'btn_lbl' => 'Learn More',
            'checks'  => array('', '', ''),
        ),
    );

    for ($p = 1; $p <= 2; $p++) :
        $prefix = "vance_about_promo$p";
        $pd     = $promo_defaults[$p];
        if (!get_theme_mod($prefix . '_show', $pd['show'])) continue;

        $img     = get_theme_mod($prefix . '_img', $p === 1 ? $vabout_img . 'pharma-manufacturing.jpg' : '');
        $title   = get_theme_mod($prefix . '_title', $pd['title']);
        $sub     = get_theme_mod($prefix . '_sub', $pd['sub']);
        $desc    = get_theme_mod($prefix . '_desc', $pd['desc']);
        $btn_lbl = get_theme_mod($prefix . '_btn_lbl', $pd['btn_lbl']);
        $btn_url = get_theme_mod($prefix . '_btn_url', '#');
        $layout  = get_theme_mod($prefix . '_layout', 'img-left');
        $badge   = get_theme_mod($prefix . '_img_badge', $p === 1 ? 'Pharma-Grade Production' : '');
        $styles  = vance_get_style_string($prefix);
        $checks  = array_filter(array(
            get_theme_mod($prefix . '_check1', $pd['checks'][0]),
            get_theme_mod($prefix . '_check2', $pd['checks'][1]),
            get_theme_mod($prefix . '_check3', $pd['checks'][2]),
        ));
        // Blank lines split the copy into paragraphs; the first reads as the lead.
        $paras = array_values(array_filter(array_map('trim', preg_split('/\R{2,}|\R/u', (string) $desc))));
    ?>
    <?php /* #our-story is the spotlight hero's primary CTA target. On the first
             promo block only, so the anchor stays unique when block 2 is on. */ ?>
    <section<?php echo ($p === 1) ? ' id="our-story"' : ''; ?> class="vabout-story<?php echo ($p % 2 === 1) ? '' : ' vabout-bg-white'; ?>" style="<?php echo $styles['section']; ?>">
        <div class="container">
            <div class="vabout-story-grid<?php echo ($layout === 'img-right') ? ' is-flipped' : ''; ?>">
                <div class="vabout-story-media reveal">
                    <?php if ($img) : ?>
                        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async">
                    <?php else : ?>
                        <div class="vabout-media-fallback" role="presentation"><?php echo vance_about_icon('flask', 44, 1.4); ?></div>
                    <?php endif; ?>
                    <?php if ($badge) : ?>
                        <span class="vabout-media-badge"><?php echo vance_about_icon('award', 15); ?><?php echo esc_html($badge); ?></span>
                    <?php endif; ?>
                </div>

                <div class="vabout-story-body reveal">
                    <?php if ($sub) : ?><span class="vabout-pill" style="<?php echo $styles['tag']; ?>"><?php echo esc_html($sub); ?></span><?php endif; ?>
                    <h2 style="<?php echo $styles['title']; ?>"><?php echo esc_html($title); ?></h2>
                    <?php foreach ($paras as $idx => $para) : ?>
                        <p class="<?php echo $idx === 0 ? 'vabout-lead' : ''; ?>" style="<?php echo $styles['text']; ?>"><?php echo esc_html($para); ?></p>
                    <?php endforeach; ?>

                    <?php if ($checks) : ?>
                    <ul class="vabout-checklist">
                        <?php foreach ($checks as $check) : ?>
                            <li><span class="vabout-check-ico"><?php echo vance_about_icon('check', 14, 3); ?></span><?php echo esc_html($check); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <?php if ($btn_lbl) : ?>
                        <a href="<?php echo esc_url($btn_url); ?>" class="vabout-btn vabout-btn-primary"><?php echo esc_html($btn_lbl); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endfor; ?>


    <?php
    /* ==================== MISSION & VALUES ==================== */
    if (vance_get_theme_mod('vance_about_mission_show', true)) :
        $mission_tag   = vance_get_theme_mod('vance_about_mission_tag',   'Our Mission');
        $mission_title = vance_get_theme_mod('vance_about_mission_title', 'Bridging Science &amp; <span class="highlight">Patient Wellbeing</span>');
        $mission_desc  = vance_get_theme_mod('vance_about_mission_desc',  'At Vance Medical, our mission is to empower patients living with chronic gastrointestinal conditions by making world-class clinical nutrition science accessible, actionable, and personal.');
        $mission_img   = vance_get_theme_mod('vance_about_mission_img', $vabout_img . 'diverse-patients-clinic.jpg');
        $styles        = vance_get_style_string('vance_about_mission');

        $val_defaults = array(
            1 => array('Evidence-Based', 'Every product and piece of content meets the highest scientific and regulatory standards, rooted in peer-reviewed clinical research.', 'microscope'),
            2 => array('Patient-First',  'We design every solution around real-world challenges patients face, not just clinical endpoints, because lived experience matters.', 'heart'),
            3 => array('Pharma-Grade',   'Our medical food products are developed with the same rigour applied to licensed medicines, a quality benchmark no ordinary supplement can match.', 'award'),
            4 => array('Global Reach',   'With a regulatory footprint spanning multiple continents, we deliver consistent, trusted solutions wherever patients and clinicians need them.', 'globe'),
        );
    ?>
    <section id="mission" class="vabout-mission" style="<?php echo $styles['section']; ?>">
        <?php if ($mission_img) : ?>
            <div class="vabout-mission-media" style="background-image:url('<?php echo esc_url($mission_img); ?>');" role="presentation"></div>
            <div class="vabout-mission-veil" role="presentation"></div>
        <?php endif; ?>

        <div class="container vabout-mission-inner">
            <header class="vabout-head reveal">
                <span class="vabout-pill" style="<?php echo $styles['tag']; ?>"><?php echo esc_html($mission_tag); ?></span>
                <h2 style="<?php echo $styles['title']; ?>"><?php echo wp_kses_post($mission_title); ?></h2>
                <p class="vabout-mission-statement" style="<?php echo $styles['text']; ?>"><?php echo esc_html($mission_desc); ?></p>
            </header>

            <div class="vabout-pillars">
                <?php for ($i = 1; $i <= 4; $i++) :
                    $vt = vance_get_theme_mod("vance_about_val{$i}_title", $val_defaults[$i][0]);
                    $vd = vance_get_theme_mod("vance_about_val{$i}_desc",  $val_defaults[$i][1]);
                ?>
                <div class="vabout-pillar reveal">
                    <span class="vabout-pillar-ico"><?php echo vance_about_icon($val_defaults[$i][2], 22); ?></span>
                    <h4 style="<?php echo $styles['title']; ?>"><?php echo esc_html($vt); ?></h4>
                    <p style="<?php echo $styles['text']; ?>"><?php echo esc_html($vd); ?></p>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>


    <?php
    /* ==================== WHY PATIENTS TRUST US ==================== */
    if (vance_get_theme_mod('vance_about_product_show', true)) :
        $prod_tag   = vance_get_theme_mod('vance_about_prod_tag',   'Why Patients Trust Us');
        $prod_title = vance_get_theme_mod('vance_about_prod_title', 'Built on Decades of Clinical Excellence');
        $prod_desc  = vance_get_theme_mod('vance_about_prod_desc',  '');
        $styles     = vance_get_style_string('vance_about_product');

        $feat_defaults = array(
            1 => array('Nutrition-First Approach', 'Our team of gastroenterologists, dietitians, and pharmaceutical scientists develop solutions that fit naturally into your daily life.', 'heart',    'wellness-kitchen.jpg'),
            2 => array('Community & Support',      'Join a vibrant community of patients and practitioners sharing experiences, knowledge, and encouragement on the path to better gut health.', 'users',    'community-support.jpg'),
            3 => array('Digital Innovation',       'Our AI-powered tools and digital health platform put clinical-grade information at your fingertips, 24/7.', 'sparkles', 'digital-health-tech.jpg'),
        );
    ?>
    <section class="vabout-trust" style="<?php echo $styles['section']; ?>">
        <div class="container">
            <header class="vabout-head reveal">
                <span class="vabout-pill" style="<?php echo $styles['tag']; ?>"><?php echo esc_html($prod_tag); ?></span>
                <h2 style="<?php echo $styles['title']; ?>"><?php echo esc_html($prod_title); ?></h2>
                <?php if ($prod_desc) : ?><p style="<?php echo $styles['text']; ?>"><?php echo esc_html($prod_desc); ?></p><?php endif; ?>
            </header>

            <div class="vabout-trust-grid">
                <?php for ($i = 1; $i <= 3; $i++) :
                    $ft = vance_get_theme_mod("vance_about_feat{$i}_title", $feat_defaults[$i][0]);
                    $fd = vance_get_theme_mod("vance_about_feat{$i}_desc",  $feat_defaults[$i][1]);
                    $fi = vance_get_theme_mod("vance_about_feat{$i}_img", $vabout_img . $feat_defaults[$i][3]);
                ?>
                <article class="vabout-trust-card reveal">
                    <?php if ($fi) : ?>
                        <div class="vabout-trust-media"><img src="<?php echo esc_url($fi); ?>" alt="<?php echo esc_attr($ft); ?>" loading="lazy" decoding="async"></div>
                    <?php else : ?>
                        <div class="vabout-trust-media vabout-media-fallback" role="presentation"><?php echo vance_about_icon($feat_defaults[$i][2], 40, 1.4); ?></div>
                    <?php endif; ?>
                    <div class="vabout-trust-body">
                        <h3 style="<?php echo $styles['title']; ?>"><?php echo esc_html($ft); ?></h3>
                        <p style="<?php echo $styles['text']; ?>"><?php echo esc_html($fd); ?></p>
                    </div>
                </article>
                <?php endfor; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>


    <?php
    /* ======================= PATIENT STORIES ======================= */
    if (vance_get_theme_mod('vance_about_testimonials_show', true)) :
        $testi_tag   = vance_get_theme_mod('vance_about_testimonials_tag',   'Patient Stories');
        $testi_title = vance_get_theme_mod('vance_about_testimonials_title', 'Real People, Real Results');
        $testi_defaults = array(
            1 => array("The Vance Health Hub has completely changed how I manage my Crohn's disease. The nutritional guidance alongside my medication has made a real difference to my daily life.", 'S.M.', 'Sarah M.', "Living with Crohn's Disease"),
            2 => array('Living with colitis felt overwhelming until I found Vance. Knowing the guidance is pharmaceutical-grade and evidence-based gives me real confidence in the choices I make about my condition every day.', 'M.P.', 'M. Patel', 'Living with Colitis'),
            3 => array('Finally, a resource that combines proper medical science with practical nutrition advice. The VANCE-Ai tool helps me understand my condition without the jargon.', 'J.T.', 'James T.', 'Living with IBS'),
        );
    ?>
    <section class="vabout-testimonials vabout-bg-white">
        <div class="container">
            <header class="vabout-head reveal">
                <span class="vabout-pill"><?php echo esc_html($testi_tag); ?></span>
                <h2><?php echo esc_html($testi_title); ?></h2>
            </header>

            <div class="vabout-testi-grid">
                <?php for ($i = 1; $i <= 3; $i++) :
                    $q  = vance_get_theme_mod("vance_about_testi{$i}_quote",    $testi_defaults[$i][0]);
                    $in = vance_get_theme_mod("vance_about_testi{$i}_initials", $testi_defaults[$i][1]);
                    $nm = vance_get_theme_mod("vance_about_testi{$i}_name",     $testi_defaults[$i][2]);
                    $rl = vance_get_theme_mod("vance_about_testi{$i}_role",     $testi_defaults[$i][3]);
                ?>
                <figure class="vabout-testi reveal">
                    <div class="vabout-stars" role="img" aria-label="<?php esc_attr_e('Rated 5 out of 5', 'vance-health-hub'); ?>">
                        <?php for ($s = 0; $s < 5; $s++) echo vance_about_star(14); ?>
                    </div>
                    <blockquote>&ldquo;<?php echo esc_html($q); ?>&rdquo;</blockquote>
                    <figcaption>
                        <span class="vabout-avatar" aria-hidden="true"><?php echo esc_html($in); ?></span>
                        <span>
                            <strong><?php echo esc_html($nm); ?></strong>
                            <em><?php echo esc_html($rl); ?></em>
                        </span>
                    </figcaption>
                </figure>
                <?php endfor; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>


    <?php
    /* ==================== DIGITAL LAYER ==================== */
    if (vance_get_theme_mod('vance_about_platform_show', true)) :
        $plat_tag   = vance_get_theme_mod('vance_about_plat_tag',   'The Digital Layer');
        $plat_title = vance_get_theme_mod('vance_about_plat_title', 'Your Complete Digital Health Companion');
        $plat_desc  = vance_get_theme_mod('vance_about_plat_desc',  'Beyond our product pipeline, Vance Medical is building a world-class digital health hub, combining clinical-grade content, AI-powered tools, and a vibrant community for patients and healthcare professionals.');
        $plat_img   = vance_get_theme_mod('vance_about_digital_img', $vabout_img . 'digital-health-companion.png');
        $float1     = vance_get_theme_mod('vance_about_float1_label', 'Secure & Private');
        $float2     = vance_get_theme_mod('vance_about_float2_label', '24/7 Access');
        $styles     = vance_get_style_string('vance_about_platform');

        $plat_defaults = array(
            1 => array('Clinical Content Hub', 'Peer-reviewed research and patient education curated by gastroenterologists and dietitians.', 'book',      home_url('/gastro-health-explained/')),
            2 => array('VANCE-Ai',             'Specialised AI trained on clinical gastroenterology to answer your health questions safely.', 'sparkles', home_url('/ask-ai/')),
            3 => array('My Dashboard',         'Track health records, manage your Gastro tools, and connect with your care pathway.', 'dashboard',       home_url('/dashboard/')),
            4 => array('Health Calculators',   'Evidence-based clinical calculators for malnutrition screening, BMI, and disease scoring.', 'calculator',  home_url('/free-health-tools/')),
        );
    ?>
    <section class="vabout-digital" style="<?php echo $styles['section']; ?>">
        <div class="container">
            <div class="vabout-digital-grid">
                <div class="vabout-digital-body reveal">
                    <span class="vabout-pill" style="<?php echo $styles['tag']; ?>"><?php echo esc_html($plat_tag); ?></span>
                    <h2 style="<?php echo $styles['title']; ?>"><?php echo esc_html($plat_title); ?></h2>
                    <p class="vabout-lead" style="<?php echo $styles['text']; ?>"><?php echo esc_html($plat_desc); ?></p>

                    <ul class="vabout-features">
                        <?php for ($i = 1; $i <= 4; $i++) :
                            $pt  = vance_get_theme_mod("vance_about_plat{$i}_title", $plat_defaults[$i][0]);
                            $pdz = vance_get_theme_mod("vance_about_plat{$i}_desc",  $plat_defaults[$i][1]);
                            $purl= vance_get_theme_mod("vance_about_plat{$i}_url",   $plat_defaults[$i][3]);
                        ?>
                        <li>
                            <a class="vabout-feature-link" href="<?php echo esc_url($purl); ?>">
                                <span class="vabout-feat-ico"><?php echo vance_about_icon($plat_defaults[$i][2], 20); ?></span>
                                <span>
                                    <strong style="<?php echo $styles['title']; ?>"><?php echo esc_html($pt); ?></strong>
                                    <em style="<?php echo $styles['text']; ?>"><?php echo esc_html($pdz); ?></em>
                                </span>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </div>

                <div class="vabout-digital-media reveal">
                    <?php if ($plat_img) : ?>
                        <img src="<?php echo esc_url($plat_img); ?>" alt="<?php echo esc_attr($plat_title); ?>" loading="lazy" decoding="async">
                    <?php else : ?>
                        <div class="vabout-media-fallback vabout-media-tall" role="presentation"><?php echo vance_about_icon('dashboard', 48, 1.4); ?></div>
                    <?php endif; ?>
                    <?php if ($float1) : ?><span class="vabout-float vabout-float-1"><?php echo vance_about_icon('lock', 15); ?><?php echo esc_html($float1); ?></span><?php endif; ?>
                    <?php if ($float2) : ?><span class="vabout-float vabout-float-2"><?php echo vance_about_icon('clock', 15); ?><?php echo esc_html($float2); ?></span><?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>


    <?php
    /* ========================== CTA STRIP ========================== */
    if (vance_get_theme_mod('vance_about_cta_show', true)) :
        $cta_title    = vance_get_theme_mod('vance_about_cta_title',      'Ready to Take Control of Your Gastro Health?');
        $cta_desc     = vance_get_theme_mod('vance_about_cta_desc',       'Join thousands of patients and healthcare professionals who trust Vance Health Hub for evidence-based gastrointestinal care.');
        $cta_btn1_lbl = vance_get_theme_mod('vance_about_cta_btn1_label', 'Join For Free');
        $cta_btn1_url = vance_get_theme_mod('vance_about_cta_btn1_url',   '/login/?tab=signup');
        $cta_btn2_lbl = vance_get_theme_mod('vance_about_cta_btn2_label', 'Speak to Our Team');
        $cta_btn2_url = vance_get_theme_mod('vance_about_cta_btn2_url',   '/contact-us/');
        // No hex default here: unset means the navy gradient below, not the legacy orange.
        $styles       = vance_get_style_string('vance_about_cta');

        $reassure = array(
            array(vance_get_theme_mod('vance_about_cta_reassure1', 'Secure & Private'),   'lock'),
            array(vance_get_theme_mod('vance_about_cta_reassure2', 'No Payment Required'),'card'),
            array(vance_get_theme_mod('vance_about_cta_reassure3', 'Instant Access'),     'zap'),
        );
        // A light custom background needs dark copy — otherwise the outline
        // button and reassurance row render white-on-white.
        $cta_light = vance_about_is_light_color(get_theme_mod('vance_about_cta_bg', ''));
    ?>
    <section class="vabout-cta<?php echo $cta_light ? ' is-light' : ''; ?>" style="<?php echo $styles['section']; ?>">
        <div class="container">
            <h2 style="<?php echo $styles['title']; ?>"><?php echo esc_html($cta_title); ?></h2>
            <p style="<?php echo $styles['text']; ?>"><?php echo esc_html($cta_desc); ?></p>
            <div class="vabout-cta-actions">
                <?php if ($cta_btn1_lbl) : ?><a href="<?php echo esc_url($cta_btn1_url); ?>" class="vabout-btn vabout-btn-primary"><?php echo esc_html($cta_btn1_lbl); ?></a><?php endif; ?>
                <?php if ($cta_btn2_lbl) : ?><a href="<?php echo esc_url($cta_btn2_url); ?>" class="vabout-btn vabout-btn-ghost"><?php echo esc_html($cta_btn2_lbl); ?></a><?php endif; ?>
            </div>
            <ul class="vabout-reassure">
                <?php foreach ($reassure as $r) : if (!$r[0]) continue; ?>
                    <li><?php echo vance_about_icon($r[1], 15); ?><?php echo esc_html($r[0]); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <?php endif; ?>


    <?php
    /* ===================== EDITOR CONTENT ===================== */
    $vabout_has_content = false;
    if (have_posts()) {
        the_post();
        $vabout_has_content = (trim(get_the_content()) !== '');
        rewind_posts();
    }
    if ($vabout_has_content) : ?>
    <section class="vabout-content vabout-bg-white">
        <div class="container">
            <?php while (have_posts()) : the_post(); ?>
                <div class="entry-content"><?php the_content(); ?></div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>

</main>

<style>
/* ============================================================
   About Us — scoped design system.
   Colour/type come straight from main.css custom properties so a
   palette change upstream flows through here automatically.
   ============================================================ */
.vabout {
    /* Brand */
    --vab-teal:      var(--primary-color, #008080);
    --vab-teal-dark: var(--primary-hover, #006666);   /* hover: darken, keeps 4.5:1 on white text */
    --vab-teal-soft: var(--primary-light, #78bfbf);   /* teal that reads on navy */
    --vab-teal-wash: var(--primary-wash,  #def4f4);
    --vab-navy:      var(--secondary-color, #0A1929);
    --vab-navy-2:    #132D42;                         /* mid stop for navy gradients */
    --vab-ink:       var(--text-main,  #1F2937);
    --vab-muted:     var(--text-light, #6B7280);

    /* Surfaces */
    --vab-white: #ffffff;
    --vab-warm:  #fafbfd;
    --vab-light: #f7fafc;
    --vab-line:  #e2e8f0;

    /* Shape — set all four to 0 to return to the house square-corner style */
    /* Aliases onto the site-wide scale in main.css rather than a second set of
       numbers. The literal fallbacks are this page's original geometry, kept
       only for the case where main.css has not loaded. */
    --vab-r-sm:  var(--radius-control, 6px);
    --vab-r-md:  var(--radius-field, 10px);
    --vab-r-lg:  var(--radius-surface, 14px);
    --vab-r-pill: var(--radius-pill, 999px);

    /* Elevation */
    --vab-sh-sm: 0 1px 3px rgba(0,0,0,.08);
    --vab-sh-md: 0 4px 12px rgba(0,0,0,.10);
    --vab-sh-lg: 0 10px 40px rgba(0,0,0,.12);
    --vab-sh-xl: 0 20px 60px rgba(0,0,0,.15);

    --vab-ease: cubic-bezier(.4,0,.2,1);
}

/* These two are rounded directly rather than by an overflow-hidden wrapper.
   The !important is historical - it beat a `border-radius:0 !important` reset
   that main.css no longer applies - but it is kept so this page's deliberately
   larger geometry still wins over the site-wide scale. */
.vabout .vabout-btn { border-radius: var(--vab-r-pill) !important; }
.vabout .vabout-digital-media img { border-radius: var(--vab-r-lg) !important; }

.vabout section { position: relative; }
.vabout-bg-white { background: var(--vab-white); }

/* ---------- Typography ---------- */
.vabout h1, .vabout h2, .vabout h3, .vabout h4 {
    font-family: var(--font-heading, 'Outfit', sans-serif);
    line-height: 1.18;
    color: var(--vab-navy);
}
.vabout h1 { font-size: clamp(2.25rem, 4.6vw, 3.6rem); font-weight: 700; letter-spacing: -.02em; }
.vabout h2 { font-size: clamp(1.8rem, 3.4vw, 2.75rem); font-weight: 700; letter-spacing: -.015em; }
.vabout h3 { font-size: 1.3rem;  font-weight: 700; }
.vabout h4 { font-size: 1.05rem; font-weight: 700; }
.vabout p  { color: var(--vab-muted); line-height: 1.7; }
.vabout .vabout-lead { font-size: 1.1rem; font-weight: 500; color: var(--vab-ink); }
.vabout .highlight { color: var(--vab-teal); }

/* Section header block */
.vabout-head { text-align: center; max-width: 760px; margin: 0 auto 60px; }
.vabout-head h2 { margin: 14px 0 0; }
.vabout-head p  { margin-top: 16px; font-size: 1.05rem; }

/* ---------- Pills ---------- */
.vabout-pill {
    display: inline-block;
    padding: 7px 16px;
    border-radius: var(--radius-pill, 999px);
    background: rgba(0,128,128,.10);
    color: var(--vab-teal-dark);
    font-family: var(--font-main, 'Inter', sans-serif);
    font-size: .78rem;
    font-weight: 600;
    letter-spacing: .09em;
    text-transform: uppercase;
    border: 0;
    margin: 0;
}
.vabout-pill-dark {
    background: rgba(120,191,191,.18);
    border: 1px solid rgba(120,191,191,.35);
    color: var(--vab-teal-soft);
    /* The hero eyebrow drops off --radius-pill onto the control step so it
       matches .tag-label, the equivalent pill in the contact-us hero. The
       lighter .vabout-pill further down the page keeps its full round. */
    border-radius: var(--radius-control, 6px);
}

/* ---------- Buttons ---------- */
.vabout-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;              /* ≥44px touch target */
    padding: 14px 34px;
    font-family: var(--font-main, 'Inter', sans-serif);
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .25s var(--vab-ease), transform .25s var(--vab-ease), box-shadow .25s var(--vab-ease), border-color .25s var(--vab-ease);
}
.vabout-btn-primary { background: var(--vab-teal); color: #fff; box-shadow: 0 4px 20px rgba(0,128,128,.35); }
.vabout-btn-primary:hover { background: var(--vab-teal-dark); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,128,128,.45); }
.vabout-btn-ghost { background: transparent; color: #fff; border: 2px solid rgba(255,255,255,.35); }
.vabout-btn-ghost:hover { background: rgba(255,255,255,.08); border-color: #fff; color: #fff; transform: translateY(-2px); }
.vabout a:focus-visible,
.vabout .vabout-btn:focus-visible { outline: 3px solid var(--vab-teal-soft); outline-offset: 3px; }

/* ---------- Media fallbacks ---------- */
.vabout-media-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 220px;
    background: linear-gradient(135deg, var(--vab-teal-wash), #eef5f7);
    color: var(--vab-teal);
}
.vabout-media-tall { min-height: 460px; border-radius: var(--vab-r-lg); }

/* ============================ HERO ============================ */
.vabout-hero {
    position: relative;
    display: flex;
    align-items: flex-start;
    min-height: 332px;
    padding: 72px 0 116px;
    overflow: hidden;
    background: linear-gradient(135deg, var(--vab-navy), var(--vab-navy-2) 55%, #0d3540);
}
.vabout-hero-media,
.vabout-hero-veil { position: absolute; inset: 0; }
.vabout-hero-media { background-size: cover; background-position: center; }
.vabout-hero::after {
    content: "";
    position: absolute; left: 0; right: 0; bottom: -1px;
    height: 120px;
    background: linear-gradient(to top, var(--vab-white), transparent);
}
.vabout-hero > .container { width: 100%; }
.vabout-hero-inner { position: relative; z-index: 2; max-width: 820px; margin: 0; }
.vabout-hero h1 { color: #fff; margin: 22px 0 20px; }
/* Needs the extra class: `.vabout p` would otherwise out-specify this and paint
   the subtitle muted grey on the navy hero (fails contrast). */
.vabout .vabout-hero-sub { color: rgba(255,255,255,.85); font-size: 1.18rem; line-height: 1.75; max-width: 640px; margin: 0 0 36px; }

.vabout-trustbadges { display: flex; flex-wrap: wrap; gap: 14px; list-style: none; margin: 0; padding: 0; }
.vabout-trustbadges li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 20px;
    border-radius: var(--vab-r-pill);
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.18);
    -webkit-backdrop-filter: blur(10px);
    backdrop-filter: blur(10px);
    color: rgba(255,255,255,.94);
    font-size: .88rem;
    font-weight: 500;
}
.vabout-tb-ico { display: flex; color: var(--vab-teal-soft); }

/* ============================ STATS ============================ */
/* Stats sit clear of the hero rather than overlapping it — 100px of breathing
   room below the hero's fade-out, per client direction. */
.vabout-stats { background: var(--vab-white); padding: 50px 0 40px; position: relative; z-index: 3; }
.vabout-stats-overlap { margin-top: 0; padding-top: 50px; }
.vabout-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
.vabout-stat {
    position: relative;
    overflow: hidden;
    padding: 34px 26px;
    text-align: center;
    background: var(--vab-white);
    border: 1px solid var(--vab-line);
    border-radius: var(--vab-r-lg);
    box-shadow: var(--vab-sh-sm);
    transition: transform .4s var(--vab-ease), box-shadow .4s var(--vab-ease), border-color .4s var(--vab-ease);
    /* Column layout so the accent bar can be pinned to the bottom of the card.
       Grid stretches every card to the tallest, so the bars line up regardless
       of whether a label wraps onto a second line. */
    display: flex;
    flex-direction: column;
}
.vabout-stat::before {
    content: "";
    position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--vab-teal), var(--vab-teal-soft));
    transform: scaleX(0);
    transition: transform .4s var(--vab-ease);
}
.vabout-stat:hover { transform: translateY(-8px); box-shadow: var(--vab-sh-xl); border-color: var(--vab-teal); }
.vabout-stat:hover::before { transform: scaleX(1); }

/* Hover pulse. The lift is baked into every keyframe so the animation and the
   :hover transform above agree — otherwise the animation would reset the card
   to its unlifted position on the first frame. */
@keyframes vabout-stat-pulse {
    0%, 100% { transform: translateY(-8px) scale(1);     box-shadow: var(--vab-sh-xl); }
    50%      { transform: translateY(-8px) scale(1.025); box-shadow: 0 26px 70px rgba(0,128,128,.28); }
}
.vabout-stat:hover { animation: vabout-stat-pulse 1.7s var(--vab-ease) infinite; }
.vabout-stat-ico {
    display: flex; align-items: center; justify-content: center;
    width: 58px; height: 58px; margin: 0 auto 18px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(0,128,128,.12), rgba(0,128,128,.04));
    color: var(--vab-teal);
}
.vabout-stat-figure { display: flex; align-items: baseline; justify-content: center; gap: 2px; margin: 0; }
.vabout-stat-num { font-family: var(--font-heading, 'Outfit', sans-serif); font-size: 2.1rem; font-weight: 700; line-height: 1; color: var(--vab-navy); }
.vabout-stat-suffix { font-family: var(--font-heading, 'Outfit', sans-serif); font-size: 1.25rem; font-weight: 700; color: var(--vab-teal); }
/* 20px bottom margin is the guaranteed minimum gap; the bar's auto top margin
   then absorbs whatever slack a shorter (single-line) label leaves, so every
   bar lands on the same baseline across the row. */
.vabout-stat-label { margin: 12px 0 20px; font-size: .88rem; font-weight: 500; color: var(--vab-muted); }
.vabout-stat-bar { display: block; height: 4px; margin-top: auto; border-radius: var(--radius-control, 6px); background: var(--vab-line); overflow: hidden; }
.vabout-stat-bar i { display: block; height: 100%; width: 0; border-radius: var(--radius-control, 6px); background: linear-gradient(90deg, var(--vab-teal), var(--vab-teal-soft)); transition: width 1.4s var(--vab-ease); }
.vabout-stat.is-visible .vabout-stat-bar i { width: 100%; }

/* ============================ STORY ============================ */
.vabout-story { background: var(--vab-warm); padding: 50px 0; }
.vabout-story-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
.vabout-story-grid.is-flipped .vabout-story-media { order: 2; }
.vabout-story-media { position: relative; border-radius: var(--vab-r-lg); overflow: hidden; box-shadow: var(--vab-sh-lg); }
.vabout-story-media img { display: block; width: 100%; height: 500px; object-fit: cover; }
.vabout-media-badge {
    position: absolute; left: 20px; bottom: 20px;
    display: flex; align-items: center; gap: 8px;
    padding: 10px 18px;
    border-radius: var(--vab-r-pill);
    background: rgba(10,25,41,.9);
    -webkit-backdrop-filter: blur(10px);
    backdrop-filter: blur(10px);
    color: #fff; font-size: .84rem; font-weight: 500;
}
.vabout-media-badge svg { color: var(--vab-teal-soft); }
.vabout-story-body h2 { margin: 14px 0 22px; }
.vabout-story-body p { margin: 0 0 16px; }
.vabout-checklist { list-style: none; margin: 30px 0 0; padding: 0; display: flex; flex-direction: column; gap: 14px; }
.vabout-checklist li { display: flex; align-items: flex-start; gap: 12px; font-size: .95rem; font-weight: 500; color: var(--vab-ink); }
.vabout-check-ico {
    display: flex; align-items: center; justify-content: center;
    flex: 0 0 22px; width: 22px; height: 22px; margin-top: 1px;
    border-radius: 50%; background: var(--vab-teal-wash); color: var(--vab-teal-dark);
}
.vabout-story-body .vabout-btn { margin-top: 30px; }

/* =========================== MISSION =========================== */
.vabout-mission { padding: 59px 0; overflow: hidden; background: var(--vab-light); }
.vabout-mission-media { position: absolute; inset: 0; background-size: cover; background-position: center; }
.vabout-mission-veil {
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(0,128,128,.14), rgba(247,250,252,.95) 35%, rgba(255,255,255,.93) 65%, rgba(0,128,128,.10));
}
.vabout-mission-inner { position: relative; z-index: 2; }
.vabout-mission-statement { max-width: 700px; margin: 18px auto 0; font-size: 1.15rem; line-height: 1.8; }
.vabout-pillars { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-top: 44px; }
.vabout-pillar {
    padding: 32px 22px;
    text-align: center;
    background: rgba(255,255,255,.88);
    border: 1px solid var(--vab-line);
    border-radius: var(--vab-r-md);
    box-shadow: var(--vab-sh-sm);
    -webkit-backdrop-filter: blur(10px);
    backdrop-filter: blur(10px);
    transition: transform .3s var(--vab-ease), box-shadow .3s var(--vab-ease), background .3s var(--vab-ease);
}
.vabout-pillar:hover { transform: translateY(-4px); background: #fff; box-shadow: var(--vab-sh-md); }
.vabout-pillar-ico {
    display: flex; align-items: center; justify-content: center;
    width: 50px; height: 50px; margin: 0 auto 16px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(0,128,128,.16), rgba(0,128,128,.05));
    color: var(--vab-teal);
}
.vabout-pillar h4 { margin: 0 0 10px; }
.vabout-pillar p  { margin: 0; font-size: .9rem; line-height: 1.65; }

/* ============================ TRUST ============================ */
.vabout-trust { background: var(--vab-warm); padding: 50px 0; }
.vabout-trust-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; }
.vabout-trust-card {
    background: var(--vab-white);
    border: 1px solid var(--vab-line);
    border-radius: var(--vab-r-lg);
    box-shadow: var(--vab-sh-sm);
    overflow: hidden;
    transition: transform .4s var(--vab-ease), box-shadow .4s var(--vab-ease);
}
.vabout-trust-card:hover { transform: translateY(-6px); box-shadow: var(--vab-sh-lg); }
.vabout-trust-media { height: 220px; overflow: hidden; }
.vabout-trust-media img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s var(--vab-ease); }
.vabout-trust-card:hover .vabout-trust-media img { transform: scale(1.03); }
.vabout-trust-body { padding: 28px; }
.vabout-trust-body h3 { margin: 0 0 12px; }
.vabout-trust-body p  { margin: 0; font-size: .95rem; }

/* ========================= TESTIMONIALS ========================= */
.vabout-testimonials { padding: 50px 0; }
.vabout-testi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; }
.vabout-testi {
    position: relative;
    margin: 0;
    padding: 34px;
    background: var(--vab-white);
    border: 1px solid var(--vab-line);
    border-radius: var(--vab-r-lg);
    transition: box-shadow .3s var(--vab-ease), border-color .3s var(--vab-ease);
}
.vabout-testi::before {
    content: "\201C";
    position: absolute; top: 14px; right: 26px;
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 4rem; line-height: 1;
    color: rgba(0,128,128,.16);
}
.vabout-testi:hover { box-shadow: var(--vab-sh-lg); border-color: var(--vab-teal); }
.vabout-stars { display: flex; gap: 3px; margin-bottom: 16px; color: #E0A106; }
.vabout-testi blockquote { margin: 0 0 24px; padding: 0; border: 0; font-size: .96rem; font-style: italic; line-height: 1.75; color: var(--vab-muted); }
.vabout-testi figcaption { display: flex; align-items: center; gap: 14px; }
.vabout-avatar {
    display: flex; align-items: center; justify-content: center;
    flex: 0 0 44px; width: 44px; height: 44px;
    border-radius: 50%;
    background: var(--vab-teal); color: #fff;
    font-family: var(--font-heading, 'Outfit', sans-serif);
    font-size: .82rem; font-weight: 700;
}
.vabout-testi figcaption strong { display: block; font-family: var(--font-heading, 'Outfit', sans-serif); font-size: .93rem; font-weight: 700; color: var(--vab-navy); }
.vabout-testi figcaption em { display: block; font-size: .84rem; font-style: normal; color: var(--vab-muted); }

/* =========================== DIGITAL =========================== */
.vabout-digital { background: var(--vab-light); padding: 50px 0; }
.vabout-digital-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 60px; align-items: center; }
.vabout-digital-body h2 { margin: 14px 0 16px; }
.vabout-digital-body .vabout-lead { margin: 0 0 34px; }
.vabout-features { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.vabout-feature-link {
    display: flex; align-items: flex-start; gap: 16px;
    padding: 14px 16px;
    border-radius: var(--vab-r-sm);
    text-decoration: none;
    color: inherit;
    transition: background .3s var(--vab-ease);
}
.vabout-feature-link:hover { background: rgba(0,128,128,.06); }
.vabout-feature-link:focus-visible { outline: 2px solid var(--vab-teal); outline-offset: 2px; }
.vabout-feat-ico {
    display: flex; align-items: center; justify-content: center;
    flex: 0 0 44px; width: 44px; height: 44px;
    border-radius: var(--radius-control, 6px);
    background: linear-gradient(135deg, rgba(0,128,128,.16), rgba(0,128,128,.05));
    color: var(--vab-teal);
}
.vabout-features strong { display: block; margin-bottom: 4px; font-family: var(--font-heading, 'Outfit', sans-serif); font-size: 1rem; font-weight: 700; color: var(--vab-ink); }
.vabout-features em { display: block; font-size: .9rem; font-style: normal; line-height: 1.55; color: var(--vab-muted); }

.vabout-digital-media { position: relative; }
.vabout-digital-media img { width: 100%; height: 500px; object-fit: contain; background: var(--vab-white); border-radius: var(--vab-r-lg); box-shadow: var(--vab-sh-lg); }
.vabout-float {
    position: absolute;
    display: flex; align-items: center; gap: 10px;
    padding: 13px 20px;
    border-radius: var(--vab-r-pill);
    background: #fff;
    box-shadow: var(--vab-sh-lg);
    color: var(--vab-ink);
    font-size: .85rem; font-weight: 600;
    animation: vabout-float 3s ease-in-out infinite;
}
.vabout-float svg { color: var(--vab-teal); }
.vabout-float-1 { top: 28px; right: -18px; }
.vabout-float-2 { bottom: 38px; left: -18px; animation-delay: 1.5s; }
@keyframes vabout-float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

/* ============================= CTA ============================= */
.vabout-cta {
    padding: 42px 0;
    text-align: center;
    background: linear-gradient(135deg, var(--vab-navy), var(--vab-navy-2));
}
.vabout-cta h2 { color: #fff; margin: 0 0 16px; }
.vabout-cta > .container > p { max-width: 620px; margin: 0 auto 34px; font-size: 1.08rem; color: rgba(255,255,255,.82); }
.vabout-cta-actions { display: flex; flex-wrap: wrap; gap: 16px; justify-content: center; margin-bottom: 28px; }
.vabout-reassure { display: flex; flex-wrap: wrap; justify-content: center; gap: 28px; list-style: none; margin: 0; padding: 0; }
.vabout-reassure li { display: flex; align-items: center; gap: 8px; font-size: .86rem; color: rgba(255,255,255,.72); }
.vabout-reassure svg { color: var(--vab-teal-soft); }

/* Light-background variants.
   Both the hero and the CTA ship dark, so their copy is white. When an admin
   picks a light background in the Customizer, `is-light` is added server-side
   (see vance_about_is_light_color) and the white copy is swapped for dark —
   without this, the outline button and reassurance row vanish into the panel. */
.vabout-cta.is-light h2 { color: var(--vab-navy); }
.vabout-cta.is-light > .container > p { color: var(--vab-ink); }
.vabout-cta.is-light .vabout-reassure li { color: var(--vab-muted); }
.vabout-cta.is-light .vabout-reassure svg { color: var(--vab-teal); }
.vabout-cta.is-light .vabout-btn-ghost { color: var(--vab-teal-dark); border-color: var(--vab-teal); }
.vabout-cta.is-light .vabout-btn-ghost:hover { background: rgba(0,128,128,.08); border-color: var(--vab-teal-dark); color: var(--vab-teal-dark); }

.vabout-hero.is-light h1 { color: var(--vab-navy); }
.vabout-hero.is-light .vabout-hero-sub,
.vabout .vabout-hero.is-light .vabout-hero-sub { color: var(--vab-ink); }
.vabout-hero.is-light::after { background: none; }
.vabout-hero.is-light .vabout-pill-dark { background: rgba(0,128,128,.10); border-color: rgba(0,128,128,.25); color: var(--vab-teal-dark); }
.vabout-hero.is-light .vabout-trustbadges li { background: rgba(0,128,128,.06); border-color: rgba(0,128,128,.18); color: var(--vab-ink); }
.vabout-hero.is-light .vabout-tb-ico { color: var(--vab-teal); }

/* ====================== BLACK COPY (client direction) ======================
   Headings and body copy render black across the light sections. Achieved by
   re-pointing the ink tokens per section rather than blanket !important rules,
   so the Customizer's own per-section colour overrides still win.

   Deliberately excluded:
   - Testimonials — keeps the navy/grey hierarchy, as requested.
   - Hero and the default dark CTA — their copy sits on navy, so black would be
     unreadable. (A light CTA background flips to dark copy via `.is-light`.)
   - The teal eyebrow pills and stat suffixes stay teal: they're brand accents,
     not titles or body copy. */
.vabout-stats,
.vabout-story,
.vabout-mission,
.vabout-trust,
.vabout-digital,
.vabout-content {
    --vab-ink:   #000;
    --vab-muted: #000;
    --vab-navy:  #000;
}

/* ========================= PAGE CONTENT ========================= */
.vabout-content { padding: 40px 0; }
.vabout-content .entry-content { line-height: 1.8; }

/* ====================== SCROLL REVEAL ======================
   Only hides content once JS confirms it can reveal it again. */
.vabout-js .vabout .reveal { opacity: 0; transform: translateY(28px); transition: opacity .8s var(--vab-ease), transform .8s var(--vab-ease); }
.vabout-js .vabout .reveal.is-visible { opacity: 1; transform: none; }

/* =========================== RESPONSIVE =========================== */
@media (max-width: 1024px) {
    .vabout-stats-grid,
    .vabout-pillars { grid-template-columns: repeat(2, 1fr); }
    .vabout-trust-grid { grid-template-columns: repeat(2, 1fr); }
    .vabout-testi-grid { grid-template-columns: 1fr; max-width: 620px; margin: 0 auto; }
    .vabout-digital-grid { grid-template-columns: 1fr; gap: 48px; }
    .vabout-digital-media img,
    .vabout-media-tall { height: 400px; min-height: 400px; }
}
@media (max-width: 768px) {
    .vabout-hero-sub { font-size: 1.05rem; }
    .vabout-trustbadges { flex-direction: column; align-items: flex-start; }
    .vabout-stats { padding: 32px 0 30px; }
    .vabout-stats-overlap { margin-top: 0; padding-top: 32px; }
    .vabout-stats-grid { grid-template-columns: 1fr 1fr; gap: 16px; }
    .vabout-stat { padding: 26px 16px; }
    .vabout-stat-num { font-size: 1.6rem; }
    .vabout-stat-suffix { font-size: 1rem; }
    .vabout-story, .vabout-trust,
    .vabout-testimonials, .vabout-digital { padding: 32px 0; }
    .vabout-mission { padding: 36px 0; }
    .vabout-story-grid { grid-template-columns: 1fr; gap: 36px; }
    .vabout-story-grid.is-flipped .vabout-story-media { order: 0; }
    .vabout-story-media img { height: 320px; }
    .vabout-trust-grid { grid-template-columns: 1fr; max-width: 480px; margin: 0 auto; }
    .vabout-head { margin-bottom: 44px; }
    .vabout-float-1 { right: 10px; }
    .vabout-float-2 { left: 10px; }
    .vabout-cta-actions .vabout-btn { width: 100%; }
}
@media (max-width: 480px) {
    .vabout-stats-grid, .vabout-pillars { grid-template-columns: 1fr; }
}

/* ====================== REDUCED MOTION ====================== */
@media (prefers-reduced-motion: reduce) {
    .vabout-js .vabout .reveal { opacity: 1; transform: none; transition: none; }
    .vabout *, .vabout *::before, .vabout *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: .01ms !important;
    }
    .vabout-stat-bar i { width: 100%; }
}
</style>

<script>
(function () {
    'use strict';

    var root  = document.documentElement;
    var scope = document.querySelector('.vabout');
    if (!scope) return;

    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Only allow the reveal styles to hide content now that JS is running.
    if ('IntersectionObserver' in window) root.classList.add('vabout-js');

    /* ---- Count-up for the stat figures ---- */
    function runCounter(el) {
        var target   = parseFloat(el.getAttribute('data-count-to'));
        var decimals = parseInt(el.getAttribute('data-decimals'), 10) || 0;
        var group    = el.getAttribute('data-group') === '1';
        if (isNaN(target)) return;

        function render(v) {
            var s = v.toFixed(decimals);
            if (group) {
                var bits = s.split('.');
                bits[0] = bits[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                s = bits.join('.');
            }
            el.textContent = s;
        }

        if (reduced) { render(target); return; }

        var duration = 1600, start = null;
        function step(ts) {
            if (start === null) start = ts;
            var p = Math.min((ts - start) / duration, 1);
            render(target * (1 - Math.pow(1 - p, 3)));   // easeOutCubic
            if (p < 1) requestAnimationFrame(step);
        }
        render(0);
        requestAnimationFrame(step);
    }

    /* ---- Reveal on scroll, then fire any counters inside ---- */
    var revealables = scope.querySelectorAll('.reveal');

    if (!('IntersectionObserver' in window)) {
        // No observer: show everything and settle the numbers immediately.
        Array.prototype.forEach.call(revealables, function (el) { el.classList.add('is-visible'); });
        Array.prototype.forEach.call(scope.querySelectorAll('[data-count-to]'), function (el) {
            el.textContent = el.getAttribute('data-count-to');
        });
        return;
    }

    function show(el) {
        if (el.classList.contains('is-visible')) return;
        el.classList.add('is-visible');
        Array.prototype.forEach.call(el.querySelectorAll('[data-count-to]'), runCounter);
    }

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            show(entry.target);
            io.unobserve(entry.target);
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    Array.prototype.forEach.call(revealables, function (el) { io.observe(el); });

    // Failsafe: content must never be left invisible. If the observer hasn't
    // reported on an element that is already within the viewport shortly after
    // load — throttled tab, suppressed callbacks, anything unexpected — reveal
    // it directly. Anything below the fold still animates in on scroll.
    setTimeout(function () {
        Array.prototype.forEach.call(revealables, function (el) {
            var r = el.getBoundingClientRect();
            if (r.top < (window.innerHeight || 0) && r.bottom > 0) { show(el); io.unobserve(el); }
        });
    }, 1200);

    // Last-resort net for the whole page, in case the viewport test above is
    // also unreliable: after 5s nothing may still be hidden.
    setTimeout(function () {
        Array.prototype.forEach.call(revealables, function (el) { show(el); io.unobserve(el); });
    }, 5000);
}());
</script>

<?php get_footer(); ?>
