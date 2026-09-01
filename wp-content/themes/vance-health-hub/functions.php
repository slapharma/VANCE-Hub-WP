<?php
/**
 * Vance Medical Hub Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// AI Visibility System — drop-in file that makes the site discoverable to LLMs/agents.
require_once get_template_directory() . '/ai-visibility.php';
// Cross-page section renderers — defines vance_render_section_*() functions
// used both by source page templates (page-patients.php etc) AND by
// front-page.php when the admin enables those blocks on the homepage via the
// Section Order Customizer control.
require_once get_template_directory() . '/inc/cross-page-sections.php';
// Multi-instance Content Widget — 5 pre-registered latest-content blocks the
// admin can enable, position, and configure independently via the Section
// Order control + the "Content Widgets" Customizer panel.
require_once get_template_directory() . '/inc/content-widget.php';
// Tool Widgets — two modal-opening cards that replace the legacy Discovery
// block (Content Filters + Vance AI). Modal infrastructure (iframe + CSS + JS)
// is emitted once per page from inside the first widget's render.
require_once get_template_directory() . '/inc/tool-widgets.php';
// Sortable section registry — defines vance_get_available_sections() which
// front-page.php's default-case dispatch needs on EVERY page load (not just
// in the Customizer admin context). The custom WP_Customize_Control class
// inside is guarded by `class_exists( 'WP_Customize_Control' )` so it's safe
// to load on the frontend — only the function + registry filter pipeline
// runs there. Bug fix 2026-05-26: previously this file was only loaded
// inside customize_register, so registry-driven sections (content-widget-N,
// tool-widgets-row, patients-*, hcp-*) silently failed to render on the
// frontend.
require_once get_template_directory() . '/inc/customizer-sortable-control.php';
require_once get_template_directory() . '/inc/promo-block.php';
// Category promo block — configurable glass promo card on category archives.
// Renderer used by archive.php + template-parts/subcategory-grouped-archive.php;
// per-category Customizer controls are registered further down (Category Promo Blocks).
// inc/category-promo.php was folded into inc/promo-block.php on 2026-08-28:
// its markup became the shared design and both of its functions moved there.
// Prime Block — the shared "Featured Tools + Latest Content" engine behind
// Prime Block Home 1 / Home 2 (registry-driven homepage sections) and Prime
// Block Categories (called directly from the archive templates). Must load on
// the frontend too, not just in the Customizer, since it registers sections.
require_once get_template_directory() . '/inc/prime-block.php';
// Gastro Conditions — homepage tile grid for the GI condition pages.
require_once get_template_directory() . '/inc/gastro-conditions.php';
// Gastro Indications heroes — the spotlight hero shared by the Gastro Health
// Explained lobby and its seven condition pages. Loaded here rather than from
// the two templates (the way the policy heroes are) because
// inc/customizer-gi-health.php reads vance_gi_hero_hub_defaults() for its
// control defaults, and it registers on every admin request, not just on the
// two pages that render the hero.
require_once get_template_directory() . '/inc/gi-hero.php';
// Hero carousel — resolves hero slides and renders either the single static
// hero (default) or a carousel once a second slide is enabled.
require_once get_template_directory() . '/inc/hero-carousel.php';
// Spotlight hero — the light, search-led homepage hero. Which of the two
// renderers runs is decided by `vance_hero_style`; both stay loaded so the
// switch is instant and neither one's saved settings are ever discarded.
// Retired pages: a slug => destination table and the 301 that serves it, so a
// withdrawn URL lands somewhere useful instead of 404ing. Loaded early because
// it hooks template_redirect at priority 1.
require_once get_template_directory() . '/inc/retired-redirects.php';
// Noindex for tag and author archives. AIOSEO has the setting and stores it,
// but never applies it on 4.9.9 — the file explains the plugin bug in full.
require_once get_template_directory() . '/inc/seo-archive-robots.php';
// Drops the " - Vance Health Hub" suffix from titles, which was pushing 150 of
// them past the length Google will show. Front page keeps its brand-first title.
require_once get_template_directory() . '/inc/seo-title.php';
// Supplies a default og:image / twitter:image where AIOSEO has none — the
// homepage had no share image at all.
require_once get_template_directory() . '/inc/social-image.php';
// Drops the admin icon font for logged-out visitors and takes the Google
// sign-in client off the critical path. Loaded late: it dequeues other handles.
require_once get_template_directory() . '/inc/frontend-assets.php';
// The 635 DOIs already written into articles were printed as plain text and
// never resolved. Linked at display time, so nothing rewrites post_content.
require_once get_template_directory() . '/inc/citation-links.php';
// Resolves every DOI against CrossRef on publish and reports in the editor.
// Loaded after citation-links.php, whose punctuation helper it reuses.
require_once get_template_directory() . '/inc/citation-check.php';
// reviewedBy on articles, in the schema and above the copy. Ships empty on
// purpose: no post has a reviewer, so nothing is claimed until one is named.
require_once get_template_directory() . '/inc/medical-review.php';
// MedicalWebPage + MedicalCondition schema on the seven condition pages, added
// to AIOSEO's own graph rather than as a second JSON-LD block.
require_once get_template_directory() . '/inc/medical-schema.php';
// Ties each article to the condition pages it is about — `about` in the schema
// and a link under the copy. Loaded after medical-schema.php, whose condition
// registry and #medicalcondition @id it reuses.
require_once get_template_directory() . '/inc/article-conditions.php';
// NewsArticle for the news posts, Article for the guides and clinical reviews,
// AboutPage / ContactPage for the two pages that are more than a WebPage.
require_once get_template_directory() . '/inc/article-schema-types.php';
require_once get_template_directory() . '/inc/hero-spotlight.php';
// The same spotlight hero carried across to Contact, About and the three
// free-tool pages (Gastro Health Survey, meal planner, malnutrition
// calculator). Kept separate from the homepage renderer because each page
// fills the search field's slot with its own content, but it reuses the
// homepage's stylesheet block, colour defaults and vance_hex_to_rgb_triple()
// — so this must load AFTER hero-spotlight.php. All five default to their
// classic dark hero; one `vance_{page}_hero_style` each switches them over.
require_once get_template_directory() . '/inc/page-hero-spotlight.php';
// The same spotlight hero carried across to the category archives — the last
// set on the site still rendering the old 350px dark band. Loaded here rather
// than from the three archive templates because the Customizer's Category
// Heroes section registers a Photograph control per category and needs the
// registry available on every admin request, not just on an archive.
require_once get_template_directory() . '/inc/category-hero.php';
// Primary-menu mega panels — the stylesheet and the three widgets that fill a
// Max Mega Menu grid cell (icon tiles, CTA rail, live featured articles).
// Panel structure itself is admin-side; see docs/MEGA-MENU-SETUP.md (repo root).
require_once get_template_directory() . '/inc/nav-mega.php';

/**
 * Rebrand migration helper.
 *
 * Reads a theme mod under the new `vance_*` key. If that key has never been
 * saved (customizer hasn't been re-saved since the Vance Medical rebrand),
 * fall back transparently to the legacy key (pre-rebrand prefix) so existing
 * stored values (logos, URLs, copyright, social links, etc.) are preserved.
 *
 * Once an admin saves the customizer once, the `vance_*` value takes over.
 *
 * NOTE: The legacy prefix is constructed character-by-character on purpose,
 * so a future bulk rebrand pass over this file will not accidentally rewrite
 * the legacy string and turn the fallback into a no-op.
 *
 * @param string $vance_key The new vance_* theme_mod key.
 * @param mixed  $default   Default value if neither new nor legacy key is set.
 * @return mixed
 */
function vance_get_theme_mod( $vance_key, $default = false ) {
    $sentinel  = '__VANCE_THEME_MOD_UNSET__';
    $new_value = get_theme_mod( $vance_key, $sentinel );
    if ( $sentinel !== $new_value ) {
        return $new_value;
    }
    if ( strpos( $vance_key, 'vance_' ) === 0 ) {
        $legacy_prefix = implode( '', array( 's', 'l', 'a', '_' ) );
        $legacy_key    = $legacy_prefix . substr( $vance_key, 6 );
        $legacy_value  = get_theme_mod( $legacy_key, $sentinel );
        if ( $sentinel !== $legacy_value ) {
            return $legacy_value;
        }
    }
    return $default;
}

/**
 * Get a post's view count, seeding with a random 10-150 baseline on first read
 * so freshly-published articles never display as "0 views".
 */
function vance_get_view_count( $post_id ) {
    $count = get_post_meta( $post_id, '_vance_view_count', true );
    if ( '' === $count || null === $count ) {
        $count = wp_rand( 10, 150 );
        add_post_meta( $post_id, '_vance_view_count', $count, true );
    }
    return (int) $count;
}

/**
 * Estimate a post's reading time in whole minutes. Prefers the manually-set
 * `_oped_read_time` meta (the same convention single.php uses); otherwise
 * derives it from the word count at ~200 wpm. Reads the post by ID so it works
 * outside the loop too. Always returns at least 1.
 */
function vance_get_read_time( $post_id ) {
    $read_time = (int) get_post_meta( $post_id, '_oped_read_time', true );
    if ( $read_time < 1 ) {
        $content    = get_post_field( 'post_content', $post_id );
        $word_count = str_word_count( wp_strip_all_tags( strip_shortcodes( (string) $content ) ) );
        $read_time  = (int) ceil( $word_count / 200 );
    }
    return max( 1, $read_time );
}

/**
 * The seven GI Health conditions, keyed by the slug used in three places that
 * must agree: the /gi-health/<slug>/ child page, the `post_tag` applied to
 * articles about the condition, and the `condition[]` value the Discovery Suite
 * submits. Keeping them identical is what lets a condition page list its own
 * articles without a separate mapping table.
 *
 * Two short keys travel with each condition and they are NOT interchangeable —
 * diverticular disease is `div` in one and `diverticular` in the other:
 *   `key` names the customizer sections/settings (vance_gi_cond_<key>_*), so it
 *         is bound to admin-saved values already in the database.
 *   `nav` is the data-cond attribute the sidebar icons are selected on
 *         (assets/css/gi-health.css).
 */
function vance_gi_conditions() {
    return array(
        'inflammatory-bowel-disease' => array( 'key' => 'ibd',    'nav' => 'ibd',          'label' => 'Inflammatory Bowel Disease' ),
        'ulcerative-colitis'         => array( 'key' => 'uc',     'nav' => 'uc',           'label' => 'Ulcerative Colitis' ),
        'crohns-disease'             => array( 'key' => 'crohns', 'nav' => 'crohns',       'label' => 'Crohn\'s Disease' ),
        'microscopic-colitis'        => array( 'key' => 'mc',     'nav' => 'mc',           'label' => 'Microscopic Colitis' ),
        'irritable-bowel-syndrome'   => array( 'key' => 'ibs',    'nav' => 'ibs',          'label' => 'Irritable Bowel Syndrome' ),
        'colorectal-cancer'          => array( 'key' => 'crc',    'nav' => 'crc',          'label' => 'Colorectal Cancer' ),
        'diverticular-disease'       => array( 'key' => 'div',    'nav' => 'diverticular', 'label' => 'Diverticular Disease' ),
    );
}

/**
 * The presentational half of the GI condition registry: the photo, alt text,
 * display title and short description for each condition.
 *
 * vance_gi_conditions() above stays the canonical slug/key/nav registry; this
 * carries only what a *card* needs. Extracted out of page-gi-health.php so the
 * homepage "Gastro Conditions" section (inc/gastro-conditions.php) renders the
 * same seven conditions from the same source instead of keeping a second copy.
 *
 * Images live in /assets/img/gi-health/.
 *
 * @return array<int, array{slug:string,image:string,alt:string,title:string,desc:string}>
 */
function vance_gi_condition_cards() {
    return array(
        array(
            'slug'  => 'inflammatory-bowel-disease',
            'image' => 'ibd.webp',
            'alt'   => 'Four friends sitting and talking around a table in a cafe',
            'title' => 'Inflammatory Bowel Disease (IBD)',
            'desc'  => "The umbrella term for long-term conditions, mainly Crohn\u{2019}s disease and ulcerative colitis, that cause ongoing inflammation of the digestive tract.",
        ),
        array(
            'slug'  => 'ulcerative-colitis',
            'image' => 'ulcerative-colitis.webp',
            'alt'   => 'Two women walking and talking together on a sunlit city street',
            'title' => 'Ulcerative Colitis (UC)',
            'desc'  => 'A form of IBD causing inflammation and ulcers in the lining of the colon and rectum.',
        ),
        array(
            'slug'  => 'crohns-disease',
            'image' => 'crohns.webp',
            'alt'   => 'A man sitting at his kitchen table, reading a letter over a cup of tea',
            'title' => "Crohn\u{2019}s Disease",
            'desc'  => 'A form of IBD that can inflame any part of the gut, from mouth to anus, often the small intestine.',
        ),
        array(
            'slug'  => 'microscopic-colitis',
            'image' => 'microscopic-colitis.webp',
            'alt'   => 'An older woman wrapped in a blanket on a sofa, drinking from a mug',
            'title' => 'Microscopic Colitis',
            'desc'  => 'Inflammation of the colon visible only under a microscope, causing chronic watery diarrhoea.',
        ),
        array(
            'slug'  => 'irritable-bowel-syndrome',
            'image' => 'ibs.webp',
            'alt'   => 'A man and his dog looking out across the water from a waterfront pier',
            'title' => 'Irritable Bowel Syndrome (IBS)',
            'desc'  => 'A common, long-term condition affecting how the gut works, causing abdominal pain, bloating, and bouts of diarrhoea, constipation or both.',
        ),
        array(
            'slug'  => 'colorectal-cancer',
            'image' => 'colorectal-cancer.webp',
            'alt'   => 'Two men sitting on a sofa at home, talking and smiling together',
            'title' => 'Colorectal Cancer',
            'desc'  => 'Cancer that develops in the colon or rectum, often growing slowly from small growths called polyps.',
        ),
        array(
            'slug'  => 'diverticular-disease',
            'image' => 'diverticular-disease.webp',
            'alt'   => 'A carer handing a glass of water to an older woman sitting on a sofa',
            'title' => 'Diverticular Disease &amp; Diverticulitis',
            'desc'  => 'Small pouches that form in the wall of the colon, which can sometimes cause pain or become inflamed.',
        ),
    );
}

/**
 * Every category, ordered parent-first with children directly under their own
 * parent, ready for Customizer labelling.
 *
 * get_categories() returns one flat alphabetical list, which interleaves
 * parents and children: "Understanding Your Condition" lands between "Tools &
 * Resources" and "White Papers" with nothing to say it belongs to Gastro
 * Living. In a section that repeats eight controls per category that made the
 * sub-category settings effectively unfindable — the reason the category promo
 * block looked broken for sub-categories when it was in fact rendering
 * correctly all along.
 *
 * Names are entity-decoded here because term names are stored encoded
 * ("Diagnosis &amp; Treatment") and the Customizer escapes labels again on
 * output, which would otherwise render the raw entity to the admin.
 *
 * @return array List of rows: term (WP_Term), depth (int), name (string,
 *               decoded), indented (string, name prefixed by depth markers),
 *               path (string, full "Parent → Child" breadcrumb).
 */
function vance_customizer_category_tree() {
    static $cache = null;
    if ( null !== $cache ) {
        return $cache;
    }

    $terms = get_categories( array(
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );

    $by_parent = array();
    foreach ( $terms as $term ) {
        $by_parent[ (int) $term->parent ][] = $term;
    }

    $rows = array();
    $walk = function ( $parent_id, $depth, $path ) use ( &$walk, &$rows, $by_parent ) {
        if ( empty( $by_parent[ $parent_id ] ) ) {
            return;
        }
        foreach ( $by_parent[ $parent_id ] as $term ) {
            $name      = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
            $term_path = ( '' === $path ) ? $name : $path . ' → ' . $name;
            $rows[]    = array(
                'term'     => $term,
                'depth'    => $depth,
                'name'     => $name,
                'indented' => str_repeat( '— ', $depth ) . $name,
                'path'     => $term_path,
            );
            $walk( (int) $term->term_id, $depth + 1, $term_path );
        }
    };
    $walk( 0, 0, '' );

    // Orphans: a term whose parent no longer exists would never be reached by
    // the walk above and would silently lose its controls. Append them flat.
    if ( count( $rows ) < count( $terms ) ) {
        $seen = array();
        foreach ( $rows as $row ) {
            $seen[ (int) $row['term']->term_id ] = true;
        }
        foreach ( $terms as $term ) {
            if ( isset( $seen[ (int) $term->term_id ] ) ) {
                continue;
            }
            $name   = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
            $rows[] = array(
                'term'     => $term,
                'depth'    => 0,
                'name'     => $name,
                'indented' => $name,
                'path'     => $name,
            );
        }
    }

    $cache = $rows;
    return $cache;
}

/**
 * Register the full Customizer control set for one Prime Block instance.
 *
 * Home 1 does NOT go through here — it is pinned to the legacy
 * vance_pwc_* / vance_hquiz_* / vance_askai_* key names so the live site's
 * saved configuration keeps working. Every other instance uses a clean,
 * self-consistent prefix and can share this.
 *
 * Defaults mirror Home 1's out-of-the-box content, so a newly-enabled instance
 * is immediately usable. Keep in sync with
 * vance_prime_block_vals_for_prefix() in inc/prime-block.php.
 *
 * @param WP_Customize_Manager $wp_customize
 * @param string               $section_id
 * @param string               $prefix       e.g. 'vance_pb2_'
 * @param string               $title
 * @param float                $priority
 * @param string               $description
 * @param bool|string          $with_category_toggle What opt-in controls to put
 *                             at the top of the section:
 *                               false          - none (homepage instances, which
 *                                                are shown via Section Order)
 *                               true|categories- master switch, archive placement
 *                                                select, and the per-category
 *                                                on/off list
 *                               'kb_page'      - master switch and the
 *                                                Knowledgebase placement select
 *                                                only; there is one page, so a
 *                                                per-term list would be
 *                                                meaningless
 *                             Kept as bool|string rather than a new parameter so
 *                             the two existing call sites are untouched.
 * @param array                $defaults Per-instance default overrides, passed
 *                             straight through from
 *                             vance_prime_block_categories_defaults() and
 *                             friends. MUST match what the front end resolves
 *                             in vance_prime_block_vals_for_prefix(), or a
 *                             never-touched control will show a state the page
 *                             does not render.
 */
/**
 * Register one Promo Block instance's Customizer controls.
 *
 * Driven by the SAME key closure the front end resolves through
 * (vance_promo_keys_prefixed() / vance_promo_keys_term() in inc/promo-block.php),
 * so a control and the value it edits can never address different settings.
 * That matters more than usual here: one instance is prefix-addressed
 * (vance_promo_heading) and another is term-suffixed
 * (vance_cat_promo_heading_17), and nothing else in the theme reconciles those
 * two shapes.
 *
 * Defaults MUST match vance_promo_block_vals(), or a never-touched control
 * shows a state the page does not render.
 *
 * @param WP_Customize_Manager $wp_customize
 * @param string   $section_id
 * @param callable $key        fn(string $field): string
 * @param array    $args {
 *     @type string $show_key    Setting id for the visibility checkbox. Pass ''
 *                               to omit it (the caller gates visibility itself).
 *     @type string $show_label  Label for that checkbox.
 *     @type string $placement   'kb_page' to add the Knowledgebase position
 *                               select; '' for none.
 *     @type string $label_prefix Prepended to every control label, for the
 *                               category instances whose section title is a
 *                               "Parent -> Child" path.
 * }
 */
function vance_register_promo_block_controls( $wp_customize, $section_id, $key, array $args = array() ) {
    // Per-instance defaults, so the controls agree with what
    // vance_promo_block_vals() resolves for this same instance.
    $dflt = isset( $args['defaults'] ) ? $args['defaults'] : array();
    $d    = function ( $field, $fallback ) use ( $dflt ) {
        return array_key_exists( $field, $dflt ) ? $dflt[ $field ] : $fallback;
    };
    $show_key     = isset( $args['show_key'] ) ? $args['show_key'] : $key( 'show' );
    $show_label   = isset( $args['show_label'] ) ? $args['show_label'] : __( 'Show promo block', 'vance-health-hub' );
    $placement    = isset( $args['placement'] ) ? $args['placement'] : '';
    $p            = isset( $args['label_prefix'] ) ? $args['label_prefix'] : '';

    if ( $show_key ) {
        $wp_customize->add_setting( $show_key, array( 'default' => false, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
        $wp_customize->add_control( $show_key, array(
            'label'   => $p . $show_label,
            'section' => $section_id,
            'type'    => 'checkbox',
        ) );
    }

    if ( 'kb_page' === $placement ) {
        $wp_customize->add_setting( $key( 'placement' ), array( 'default' => 'below_intro', 'sanitize_callback' => 'sanitize_key' ) );
        $wp_customize->add_control( $key( 'placement' ), array(
            'label'   => $p . __( 'Position on the page', 'vance-health-hub' ),
            'section' => $section_id,
            'type'    => 'select',
            'choices' => vance_kb_page_placement_choices(),
        ) );
    }

    // -- Content ----------------------------------------------------------
    $wp_customize->add_setting( $key( 'layout' ), array( 'default' => $d( 'layout', 'image_left' ), 'sanitize_callback' => 'vance_promo_sanitize_layout' ) );
    $wp_customize->add_control( $key( 'layout' ), array(
        'label'       => $p . __( 'Layout', 'vance-health-hub' ),
        'description' => __( 'Banner puts the copy over the photo. Text only ignores the image without deleting it.', 'vance-health-hub' ),
        'section'     => $section_id,
        'type'        => 'select',
        'choices'     => vance_promo_layout_choices(),
    ) );

    $wp_customize->add_setting( $key( 'eyebrow' ), array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( $key( 'eyebrow' ), array( 'label' => $p . __( 'Eyebrow', 'vance-health-hub' ), 'section' => $section_id, 'type' => 'text' ) );

    $wp_customize->add_setting( $key( 'heading' ), array( 'default' => $d( 'heading', '' ), 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( $key( 'heading' ), array( 'label' => $p . __( 'Heading', 'vance-health-hub' ), 'section' => $section_id, 'type' => 'text' ) );

    $wp_customize->add_setting( $key( 'text' ), array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    $wp_customize->add_control( $key( 'text' ), array( 'label' => $p . __( 'Body text', 'vance-health-hub' ), 'section' => $section_id, 'type' => 'textarea' ) );

    $wp_customize->add_setting( $key( 'image' ), array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $key( 'image' ), array( 'label' => $p . __( 'Image', 'vance-health-hub' ), 'section' => $section_id ) ) );

    // -- Call to action ---------------------------------------------------
    $wp_customize->add_setting( $key( 'cta_label' ), array( 'default' => $d( 'cta_label', 'Explore' ), 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( $key( 'cta_label' ), array( 'label' => $p . __( 'Button label', 'vance-health-hub' ), 'section' => $section_id, 'type' => 'text' ) );

    $wp_customize->add_setting( $key( 'tool' ), array( 'default' => '', 'sanitize_callback' => 'sanitize_key' ) );
    $wp_customize->add_control( $key( 'tool' ), array(
        'label'       => $p . __( 'Button opens', 'vance-health-hub' ),
        'description' => __( 'Open an interactive tool in the modal, or leave as "Link to a custom URL" and fill the field below.', 'vance-health-hub' ),
        'section'     => $section_id,
        'type'        => 'select',
        'choices'     => vance_promo_tool_choices(),
    ) );

    // esc_url_raw (the category block's sanitizer) rather than the homepage's
    // sanitize_text_field, but kept as type=text rather than type=url: an
    // <input type="url"> refuses a relative path, and several of these links
    // are site-relative like /ask-ai/. esc_url_raw passes those through intact.
    $wp_customize->add_setting( $key( 'link' ), array( 'default' => $d( 'link', '' ), 'sanitize_callback' => 'esc_url_raw' ) );
    $wp_customize->add_control( $key( 'link' ), array(
        'label'       => $p . __( 'Button link', 'vance-health-hub' ),
        'description' => __( 'Used only when "Button opens" is set to a custom URL. A site-relative path such as /ask-ai/ is fine.', 'vance-health-hub' ),
        'section'     => $section_id,
        'type'        => 'text',
    ) );

    // -- Styling ----------------------------------------------------------
    // All blank/off by default, which is what keeps a category promo -- which
    // never had any of these -- rendering exactly as it did before the merge.
    $wp_customize->add_setting( $key( 'width' ), array( 'default' => 'container', 'sanitize_callback' => 'sanitize_key' ) );
    $wp_customize->add_control( $key( 'width' ), array(
        'label'   => $p . __( 'Width', 'vance-health-hub' ),
        'section' => $section_id,
        'type'    => 'select',
        'choices' => array( 'container' => __( 'Container (narrow)', 'vance-health-hub' ), 'full' => __( 'Full width', 'vance-health-hub' ) ),
    ) );

    $wp_customize->add_setting( $key( 'bg_color' ), array( 'default' => $d( 'bg_color', '' ), 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $key( 'bg_color' ), array(
        'label'       => $p . __( 'Band background colour', 'vance-health-hub' ),
        'description' => __( 'The full-width strip behind the card. Blank leaves the page background showing.', 'vance-health-hub' ),
        'section'     => $section_id,
    ) ) );

    $wp_customize->add_setting( $key( 'container_bg_color' ), array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $key( 'container_bg_color' ), array(
        'label'       => $p . __( 'Card background colour', 'vance-health-hub' ),
        'description' => __( 'Fills the card itself. Blank keeps the frosted glass look.', 'vance-health-hub' ),
        'section'     => $section_id,
    ) ) );

    $wp_customize->add_setting( $key( 'text_color' ), array( 'default' => $d( 'text_color', '' ), 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $key( 'text_color' ), array(
        'label'   => $p . __( 'Text colour', 'vance-health-hub' ),
        'section' => $section_id,
    ) ) );

    $wp_customize->add_setting( $key( 'border_enable' ), array( 'default' => false, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
    $wp_customize->add_control( $key( 'border_enable' ), array( 'label' => $p . __( 'Show border', 'vance-health-hub' ), 'section' => $section_id, 'type' => 'checkbox' ) );

    $wp_customize->add_setting( $key( 'border_scope' ), array( 'default' => 'container', 'sanitize_callback' => 'sanitize_key' ) );
    $wp_customize->add_control( $key( 'border_scope' ), array(
        'label'   => $p . __( 'Border around', 'vance-health-hub' ),
        'section' => $section_id,
        'type'    => 'select',
        'choices' => array( 'container' => __( 'The card', 'vance-health-hub' ), 'full' => __( 'The whole band', 'vance-health-hub' ) ),
    ) );

    $wp_customize->add_setting( $key( 'border_width' ), array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( $key( 'border_width' ), array(
        'label'       => $p . __( 'Border width (px)', 'vance-health-hub' ),
        'section'     => $section_id,
        'type'        => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 20, 'step' => 1 ),
    ) );

    $wp_customize->add_setting( $key( 'border_style' ), array( 'default' => 'solid', 'sanitize_callback' => 'sanitize_key' ) );
    $wp_customize->add_control( $key( 'border_style' ), array(
        'label'   => $p . __( 'Border style', 'vance-health-hub' ),
        'section' => $section_id,
        'type'    => 'select',
        'choices' => array( 'solid' => 'Solid', 'dashed' => 'Dashed', 'dotted' => 'Dotted', 'double' => 'Double' ),
    ) );

    $wp_customize->add_setting( $key( 'border_color' ), array( 'default' => '#e2e8f0', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $key( 'border_color' ), array(
        'label'   => $p . __( 'Border colour', 'vance-health-hub' ),
        'section' => $section_id,
    ) ) );
}

function vance_register_prime_block_controls( $wp_customize, $section_id, $prefix, $title, $priority, $description = '', $with_category_toggle = false, array $defaults = array() ) {
    $wp_customize->add_section( $section_id, array(
        'title'       => $title,
        'priority'    => $priority,
        'panel'       => 'vance_homepage_panel',
        'description' => $description,
    ) );

    if ( 'kb_page' === $with_category_toggle ) {
        $wp_customize->add_setting( $prefix . 'show', array( 'default' => false, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
        $wp_customize->add_control( $prefix . 'show', array(
            'label'       => __( 'Show on the Knowledgebase page', 'vance-health-hub' ),
            'description' => __( 'Off by default. Tick to add the block to /knowledgebase/.', 'vance-health-hub' ),
            'section'     => $section_id,
            'type'        => 'checkbox',
        ) );

        $wp_customize->add_setting( $prefix . 'placement', array( 'default' => 'below_intro', 'sanitize_callback' => 'sanitize_key' ) );
        $wp_customize->add_control( $prefix . 'placement', array(
            'label'   => __( 'Position on the page', 'vance-health-hub' ),
            'section' => $section_id,
            'type'    => 'select',
            'choices' => vance_kb_page_placement_choices(),
        ) );
    } elseif ( $with_category_toggle ) {
        $wp_customize->add_setting( $prefix . 'show_on_categories', array( 'default' => false, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
        $wp_customize->add_control( $prefix . 'show_on_categories', array(
            'label'       => __( 'Show on category archive pages', 'vance-health-hub' ),
            'description' => __( 'Master switch. Leave this unticked and the block never appears, whatever the per-category boxes below say.', 'vance-health-hub' ),
            'section'     => $section_id,
            'type'        => 'checkbox',
        ) );

        // Where the block sits on the archive. One setting for the whole
        // instance — the block is a single shared design shown on many pages,
        // so a per-category position would be 20-odd selects for a choice that
        // is really about the page template, not the category.
        $wp_customize->add_setting( $prefix . 'placement', array( 'default' => 'below_promo', 'sanitize_callback' => 'sanitize_key' ) );
        $wp_customize->add_control( $prefix . 'placement', array(
            'label'       => __( 'Position on the page', 'vance-health-hub' ),
            'description' => __( 'Where the block sits relative to the category promo block. "Above the footer" puts it after the article grid, at the very end of the page.', 'vance-health-hub' ),
            'section'     => $section_id,
            'type'        => 'select',
            'choices'     => vance_prime_block_placement_choices(),
        ) );

        // Per-category on/off. Defaults to ticked so the master switch keeps
        // its original "on everywhere" behaviour and these are an opt-OUT.
        // Listed parent-first with children indented underneath, because a flat
        // alphabetical list interleaves the two and gives no clue which parent
        // a sub-category belongs to.
        foreach ( vance_customizer_category_tree() as $vpb_row ) {
            $wp_customize->add_setting( $prefix . 'cat_' . $vpb_row['term']->term_id, array(
                'default'           => true,
                'sanitize_callback' => 'vance_sanitize_checkbox',
            ) );
            $wp_customize->add_control( $prefix . 'cat_' . $vpb_row['term']->term_id, array(
                'label'   => $vpb_row['indented'],
                'section' => $section_id,
                'type'    => 'checkbox',
            ) );
        }
    }

    $wp_customize->add_setting( $prefix . 'label', array( 'default' => 'Featured Tools', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( $prefix . 'label', array( 'label' => 'Section Label', 'section' => $section_id, 'type' => 'text' ) );

    $wp_customize->add_setting( $prefix . 'layout', array( 'default' => 'left', 'sanitize_callback' => 'sanitize_key' ) );
    $wp_customize->add_control( $prefix . 'layout', array(
        'label'       => __( 'Layout - Tools Position', 'vance-health-hub' ),
        'description' => __( 'Choose whether the tool cards sit beside the Latest Content list, or stack above or below it.', 'vance-health-hub' ),
        'section'     => $section_id,
        'type'        => 'select',
        'choices'     => vance_prime_block_layout_choices(),
    ) );

    $wp_customize->add_setting( $prefix . 'style', array( 'default' => 'card', 'sanitize_callback' => 'sanitize_key' ) );
    $wp_customize->add_control( $prefix . 'style', array(
        'label'       => __( 'Tool Card Style', 'vance-health-hub' ),
        'description' => __( 'Card = paired tool tiles with image header. Image + Text = horizontal banner (icon left, content right). Image = image-led banner with overlay text. Pill = compact pill banner with CTA on the right.', 'vance-health-hub' ),
        'section'     => $section_id,
        'type'        => 'select',
        'choices'     => vance_prime_block_style_choices(),
    ) );

    // -- Colours --
    $pb_colors = array(
        'section_bg'             => array( '#ffffff', 'Section Background' ),
        'section_label_color'    => array( '#0f172a', 'Section Label Colour' ),
        'card_title_color'       => array( '#0A1929', 'Card Title Colour' ),
        'card_title_hover_color' => array( '#ffffff', 'Card Title Colour (on hover)' ),
        'card_desc_color'        => array( '#64748b', 'Card Description Colour' ),
        'card_eyebrow_color'     => array( '#008080', 'Card Eyebrow / Extra-text Colour' ),
        'card_hover_color'       => array( '#008080', 'Card Hover Colour' ),
        'icon_bg_color'          => array( '#0A1929', 'Image Placeholder Background' ),
    );
    foreach ( $pb_colors as $key => $meta ) {
        $wp_customize->add_setting( $prefix . $key, array( 'default' => $meta[0], 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . $key, array( 'label' => $meta[1], 'section' => $section_id ) ) );
    }

    // -- The accent bar beside each column heading --
    // The small vertical rule to the left of "Featured Tools" / "Latest
    // Content". Hard-coded to the brand teal until now, and invisible outside
    // the homepage because its width rule lived in front-page.php.
    $wp_customize->add_setting( $prefix . 'accent_bar_show', array( 'default' => true, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
    $wp_customize->add_control( $prefix . 'accent_bar_show', array(
        'label'       => __( 'Show heading accent bar', 'vance-health-hub' ),
        'description' => __( 'The short vertical rule to the left of the "Featured Tools" and "Latest Content" headings. Untick to drop it from both.', 'vance-health-hub' ),
        'section'     => $section_id,
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( $prefix . 'accent_bar_color', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'accent_bar_color', array(
        'label'   => __( 'Heading Accent Bar Colour', 'vance-health-hub' ),
        'section' => $section_id,
    ) ) );

    $wp_customize->add_setting( $prefix . 'tools_column_bg', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'tools_column_bg', array(
        'label'       => __( 'Tools Column Background', 'vance-health-hub' ),
        'description' => __( 'Applied ONLY to the tools column. Leave blank for transparent; the column auto-pads when a colour is set so the band is visible.', 'vance-health-hub' ),
        'section'     => $section_id,
    ) ) );

    // -- The two tool cards --
    $pb_cards = array(
        'card1' => array(
            'label' => 'Card 1',
            'title' => 'Gastro Health Survey',
            'desc'  => 'A 2-minute interactive quiz that points you to the most relevant tools, resources, and content for your situation.',
            'extra' => 'Find your starting point',
            'link'  => '/gastro-health-survey/',
        ),
        'card2' => array(
            'label' => 'Card 2',
            'title' => 'VANCE-Ai',
            'desc'  => 'Ask any health question and get an evidence-backed answer in seconds. Powered by curated clinical content, available 24/7.',
            'extra' => 'Personalised answers, 24/7',
            'link'  => '/ask-ai/',
        ),
    );
    foreach ( $pb_cards as $card_key => $c ) {
        $wp_customize->add_setting( $prefix . $card_key . '_title', array( 'default' => $c['title'], 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . $card_key . '_title', array( 'label' => $c['label'] . ', Title', 'section' => $section_id, 'type' => 'text' ) );

        $wp_customize->add_setting( $prefix . $card_key . '_desc', array( 'default' => $c['desc'], 'sanitize_callback' => 'sanitize_textarea_field' ) );
        $wp_customize->add_control( $prefix . $card_key . '_desc', array( 'label' => $c['label'] . ', Description', 'section' => $section_id, 'type' => 'textarea' ) );

        $wp_customize->add_setting( $prefix . $card_key . '_extra', array( 'default' => $c['extra'], 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . $card_key . '_extra', array( 'label' => $c['label'] . ', Eyebrow / Extra text', 'section' => $section_id, 'type' => 'text' ) );

        $wp_customize->add_setting( $prefix . $card_key . '_image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $prefix . $card_key . '_image', array( 'label' => $c['label'] . ', Image', 'section' => $section_id ) ) );

        $wp_customize->add_setting( $prefix . $card_key . '_link', array( 'default' => $c['link'], 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . $card_key . '_link', array( 'label' => $c['label'] . ', Link', 'section' => $section_id, 'type' => 'text' ) );
    }

    // -- Latest Content column --
    $wp_customize->add_setting( $prefix . 'latest_title', array( 'default' => 'LATEST CONTENT', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( $prefix . 'latest_title', array( 'label' => 'Content Column, Section Label', 'section' => $section_id, 'type' => 'text' ) );

    $wp_customize->add_setting( $prefix . 'latest_count', array( 'default' => 6, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( $prefix . 'latest_count', array(
        'label'       => 'Content Column, Number of Posts',
        'description' => 'Bento layout shows 1 featured + the rest as side cards (6 = featured + 5).',
        'section'     => $section_id,
        'type'        => 'number',
        'input_attrs' => array( 'min' => 1, 'max' => 7, 'step' => 1 ),
    ) );

    $pb_cat_choices = array( 0 => 'All Categories' );
    foreach ( get_categories( array( 'hide_empty' => false ) ) as $pb_cat ) {
        $pb_cat_choices[ $pb_cat->term_id ] = $pb_cat->name;
    }
    $wp_customize->add_setting( $prefix . 'latest_category', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( $prefix . 'latest_category', array( 'label' => 'Content Column, Category Filter', 'section' => $section_id, 'type' => 'select', 'choices' => $pb_cat_choices ) );

    $wp_customize->add_setting( $prefix . 'latest_show_date', array( 'default' => true, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
    $wp_customize->add_control( $prefix . 'latest_show_date', array( 'label' => 'Content Column, Show Post Date', 'section' => $section_id, 'type' => 'checkbox' ) );

    // Default comes from the caller so the Categories block can ship with these
    // off while the homepage blocks keep them on. Mirrors the resolver default
    // in vance_prime_block_vals_for_prefix().
    $vpb_thumbs_default = ! array_key_exists( 'latest_show_thumbs', $defaults ) || ! empty( $defaults['latest_show_thumbs'] );
    $wp_customize->add_setting( $prefix . 'latest_show_thumbs', array( 'default' => $vpb_thumbs_default, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
    $wp_customize->add_control( $prefix . 'latest_show_thumbs', array(
        'label'       => __( 'Content Column, Show Article Thumbnails', 'vance-health-hub' ),
        'description' => __( 'The small square image on each row of the article list. The large featured article keeps its image either way.', 'vance-health-hub' ),
        'section'     => $section_id,
        'type'        => 'checkbox',
    ) );
}

/**
 * Resolve a GI condition page URL by slug.
 *
 * The conditions sit at the top level on the live site, so the child path is
 * tried first (in case the hierarchy is restored) and the bare slug gives the
 * canonical permalink today, avoiding the /gi-health/<slug>/ 301 hop.
 *
 * Lives here rather than in page-gi-health.php because more than one template
 * now needs it (that page, plus the homepage Gastro Conditions section).
 */
function vance_gi_page_url( string $slug ): string {
    $page = get_page_by_path( 'gi-health/' . $slug );
    if ( ! $page ) {
        $page = get_page_by_path( $slug );
    }
    if ( $page ) {
        return get_permalink( $page );
    }
    return home_url( '/' . $slug . '/' );
}

/**
 * Resolve the GI hub page URL. The hub lives at /gastro-health-explained/ on
 * the live site; the original `gi-health` slug is tried as a fallback in case
 * it is restored. Same lookup page-gi-condition.php does for its breadcrumb.
 */
function vance_gi_hub_url(): string {
    $page = get_page_by_path( 'gastro-health-explained' );
    if ( ! $page ) {
        $page = get_page_by_path( 'gi-health' );
    }
    if ( $page ) {
        return get_permalink( $page );
    }
    return home_url( '/gastro-health-explained/' );
}

/**
 * The facets the Discovery Suite offers, and their options.
 *
 * Every option is built from a term that actually carries posts, so anything a
 * visitor can tick returns results. The category tree supplies two of the axes:
 * top-level categories are the section, their children are the topic.
 *
 * Keys of `options` are the submitted values; values are the visible labels.
 * `field` is the form field name — the section facet is still submitted as
 * content_type[] so links and saved searches predating the rebuild keep working.
 */
function vance_discovery_facets() {
    $facets = array();

    /* hide_empty is the only filter applied. Do not also exclude the default
       category: on this site that option points at Healthcare News, a real
       section carrying articles, so excluding it silently dropped a whole
       facet option. Anything genuinely unused is already removed by hide_empty. */
    if ( vance_get_theme_mod( 'vance_discovery_show_section', true ) ) {
        $options = array();
        foreach ( get_categories( array( 'parent' => 0, 'hide_empty' => true ) ) as $cat ) {
            $options[ $cat->slug ] = $cat->name;
        }
        if ( $options ) {
            $facets['section'] = array( 'label' => 'Section', 'field' => 'content_type[]', 'multiple' => true, 'options' => $options );
        }
    }

    if ( vance_get_theme_mod( 'vance_discovery_show_topic', true ) ) {
        $options = array();
        foreach ( get_categories( array( 'hide_empty' => true ) ) as $cat ) {
            if ( $cat->parent ) {
                $options[ $cat->slug ] = $cat->name;
            }
        }
        if ( $options ) {
            $facets['topic'] = array( 'label' => 'Topic', 'field' => 'topic[]', 'multiple' => true, 'options' => $options );
        }
    }

    if ( vance_get_theme_mod( 'vance_discovery_show_condition', true ) ) {
        $options = array();
        foreach ( vance_gi_conditions() as $cond_slug => $cond ) {
            $term = get_term_by( 'slug', $cond_slug, 'post_tag' );
            if ( $term && ! is_wp_error( $term ) && $term->count > 0 ) {
                $options[ $cond_slug ] = $cond['label'];
            }
        }
        if ( $options ) {
            $facets['condition'] = array( 'label' => 'Condition', 'field' => 'condition[]', 'multiple' => true, 'options' => $options );
        }
    }

    if ( vance_get_theme_mod( 'vance_discovery_show_audience', true ) ) {
        $facets['audience'] = array(
            'label'    => 'Written for',
            'field'    => 'audience',
            'multiple' => false,
            'options'  => array( '' => 'Everyone', 'patient' => 'Patients & carers', 'hcp' => 'Healthcare professionals' ),
        );
    }

    return $facets;
}

/**
 * Turn a customizer colour into an rgba() string at the given alpha.
 *
 * Customizer colour controls only store opaque hex, so translucent surfaces
 * (the frosted modal, for instance) cannot be expressed as a setting. Deriving
 * the alpha here keeps the admin's chosen colour in charge of the tint instead
 * of hardcoding a second palette that would silently ignore it. Values that are
 * already rgba()/named are passed through untouched.
 */
function vance_rgba( $color, $alpha ) {
    $color = trim( (string) $color );
    if ( '' === $color || '#' !== $color[0] ) {
        return $color;
    }
    $hex = ltrim( $color, '#' );
    if ( 3 === strlen( $hex ) ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
        return $color;
    }
    return sprintf(
        'rgba(%d, %d, %d, %s)',
        hexdec( substr( $hex, 0, 2 ) ),
        hexdec( substr( $hex, 2, 2 ) ),
        hexdec( substr( $hex, 4, 2 ) ),
        rtrim( rtrim( number_format( (float) $alpha, 2, '.', '' ), '0' ), '.' )
    );
}

/**
 * Styles for the facet chips, emitted once alongside the markup.
 *
 * These live with the renderer on purpose. They used to sit in the modal's
 * stylesheet as `rgba(255,255,255,…)` values inherited from a dark panel; when
 * the modal background was set to white in the customizer every label and
 * unselected chip turned white-on-white and the filters became invisible.
 * Colours are custom properties so a dark surface can override them without
 * forking the rules again.
 */
function vance_discovery_facet_css() {
    static $emitted = false;
    if ( $emitted ) { return; }
    $emitted = true;
    ?>
    <style>
        .vance-facets {
            --vf-label: #334155;
            /* Slate-600, not the lighter slate-500 that reads fine on flat white:
               against the frosted panel that only reached 3.39:1. */
            --vf-hint: #475569;
            --vf-chip-bg: #ffffff;
            --vf-chip-border: #cbd5e1;
            --vf-chip-text: #0f172a;
            --vf-chip-hover-bg: #f1f5f9;
            --vf-chip-hover-border: #94a3b8;
            --vf-accent: #008080;
            --vf-accent-hover: #006666;
            --vf-accent-text: #ffffff;
        }
        .vance-facets .filter-group { margin: 0 0 18px; }
        .vance-facets .filter-group:last-child { margin-bottom: 0; }
        .vance-facets .filter-label {
            display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap;
            margin: 0 0 10px;
            font-family: 'Outfit', sans-serif;
            font-size: 13px; font-weight: 800;
            color: var(--vf-label);
            text-transform: uppercase; letter-spacing: 1px;
        }
        .vance-facets .filter-hint {
            font-size: 12px; font-weight: 600;
            letter-spacing: 0; text-transform: none;
            color: var(--vf-hint);
        }
        .vance-facets .chip-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .vance-facets .text-chip {
            position: relative;
            display: inline-flex; align-items: center; gap: 8px;
            min-height: 44px; padding: 10px 16px; margin: 0;
            background: var(--vf-chip-bg);
            border: 1px solid var(--vf-chip-border);
            color: var(--vf-chip-text);
            font-size: 14px; font-weight: 600; line-height: 1.2;
            cursor: pointer; user-select: none;
            transition: background-color .18s ease, border-color .18s ease, color .18s ease;
        }
        .vance-facets .text-chip:hover {
            background: var(--vf-chip-hover-bg);
            border-color: var(--vf-chip-hover-border);
        }
        /* The real control is visually hidden but still focusable, so chips stay
           reachable and operable by keyboard. */
        .vance-facets .chip-input {
            position: absolute; opacity: 0;
            width: 1px; height: 1px; margin: 0;
            pointer-events: none;
        }
        .vance-facets .text-chip:focus-within {
            outline: 3px solid var(--vf-accent);
            outline-offset: 2px;
        }
        /* Selection is signalled by the tick as well as the fill, so colour is
           never the only indicator. Space for it is always reserved, so choosing
           a chip cannot reflow the rows around it. */
        .vance-facets .chip-check {
            width: 16px; height: 16px; flex: none;
            fill: none; stroke: currentColor;
            stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round;
            opacity: 0;
            transition: opacity .18s ease;
        }
        .vance-facets .text-chip.selected {
            background: var(--vf-accent);
            border-color: var(--vf-accent);
            color: var(--vf-accent-text);
        }
        .vance-facets .text-chip.selected:hover {
            background: var(--vf-accent-hover);
            border-color: var(--vf-accent-hover);
        }
        .vance-facets .text-chip.selected .chip-check { opacity: 1; }
        @media (prefers-reduced-motion: reduce) {
            .vance-facets .text-chip,
            .vance-facets .chip-check { transition: none; }
        }
    </style>
    <?php
}

/**
 * Render the facet chip groups. Shared so the homepage widget modal and the
 * front-page block cannot drift apart — they had already diverged into two
 * near-identical copies of the old prefix-based filter code.
 */
function vance_discovery_render_facets() {
    static $instance = 0;
    $instance++;

    vance_discovery_facet_css();

    $toggle_js = "this.parentElement.classList.toggle('selected', this.checked)";
    $pick_js   = "this.closest('.chip-grid').querySelectorAll('.text-chip').forEach(function(l){l.classList.remove('selected')}); this.parentElement.classList.add('selected')";
    ?>
    <div class="vance-facets">
    <?php
    foreach ( vance_discovery_facets() as $facet_key => $facet ) {
        $is_multi = ! empty( $facet['multiple'] );
        $label_id = 'vance-facet-' . $instance . '-' . $facet_key;
        ?>
        <div class="filter-group" role="group" aria-labelledby="<?php echo esc_attr( $label_id ); ?>">
            <p class="filter-label" id="<?php echo esc_attr( $label_id ); ?>">
                <?php echo esc_html( $facet['label'] ); ?>
                <span class="filter-hint"><?php echo $is_multi ? 'pick any' : 'pick one'; ?></span>
            </p>
            <div class="chip-grid">
                <?php $is_first = true; foreach ( $facet['options'] as $option_value => $option_label ) :
                    // Single-choice facets default to their first option.
                    $preselect = ( ! $is_multi && $is_first );
                ?>
                <label class="text-chip<?php echo $preselect ? ' selected' : ''; ?>">
                    <input type="<?php echo $is_multi ? 'checkbox' : 'radio'; ?>"
                           class="chip-input"
                           name="<?php echo esc_attr( $facet['field'] ); ?>"
                           value="<?php echo esc_attr( $option_value ); ?>"
                           <?php echo $preselect ? 'checked' : ''; ?>
                           onchange="<?php echo $is_multi ? $toggle_js : $pick_js; ?>">
                    <svg class="chip-check" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M4.5 10.5l3.8 3.8L15.5 6.5"/></svg>
                    <span class="chip-text"><?php echo esc_html( $option_label ); ?></span>
                </label>
                <?php $is_first = false; endforeach; ?>
            </div>
        </div>
        <?php
    }
    ?>
    </div>
    <?php
}

/**
 * Strip leftover Markdown syntax from excerpt text.
 *
 * Post bodies are authored in Markdown and converted to HTML on `the_content`,
 * but WordPress excerpts (manual `post_excerpt`, or auto-excerpts taken from the
 * raw body) can carry literal Markdown tokens like `##`, `**`, or `[text](url)`.
 * Those render fine as a heading on the article page but leak as raw characters
 * into card excerpts. This scrubs the common tokens while leaving the words
 * intact. Hooked after WordPress' own excerpt trimming (priority 20).
 */
function vance_strip_markdown_from_excerpt( $excerpt ) {
    if ( ! is_string( $excerpt ) || $excerpt === '' ) {
        return $excerpt;
    }
    // ATX heading markers: one or more leading #, at the start of the text or
    // after whitespace (e.g. "## Understanding ..." -> "Understanding ...").
    // Safe mid-text because "#" + space is not natural prose.
    $excerpt = preg_replace( '/(^|\s)#{1,6}[ \t]+/', '$1', $excerpt );
    // Blockquote / list markers only at the very start of the excerpt — a lone
    // "-", "*", or ">" mid-sentence (ranges, "3 * 4") is real text, not a marker.
    $excerpt = preg_replace( '/^\s*(?:>|[-*+]|\d+\.)[ \t]+/', '', $excerpt );
    // Links / images: [text](url) or ![alt](url) -> text / alt.
    $excerpt = preg_replace( '/!?\[([^\]]*)\]\([^)]*\)/', '$1', $excerpt );
    // Bold / italic / inline-code wrappers -> inner text.
    $excerpt = preg_replace( '/(\*\*|__)(.+?)\1/s', '$2', $excerpt );
    $excerpt = preg_replace( '/(\*|_)(.+?)\1/s', '$2', $excerpt );
    $excerpt = preg_replace( '/`([^`]+)`/', '$1', $excerpt );
    // Collapse any doubled spaces the removals left behind.
    $excerpt = preg_replace( '/[ \t]{2,}/', ' ', $excerpt );
    return trim( $excerpt );
}
add_filter( 'get_the_excerpt', 'vance_strip_markdown_from_excerpt', 20 );

/**
 * Increment view count on single-post page loads. Skips bots, feeds, admin,
 * and previews. Tracked once per session per post via a short-lived cookie.
 */
function vance_track_post_view() {
    if ( is_admin() || is_feed() || is_preview() ) {
        return;
    }
    if ( ! is_singular( 'post' ) ) {
        return;
    }
    $post_id = get_queried_object_id();
    if ( ! $post_id ) {
        return;
    }
    $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if ( $ua && preg_match( '/bot|crawler|spider|crawling|facebookexternalhit|preview/i', $ua ) ) {
        return;
    }
    $cookie = 'vance_viewed_' . $post_id;
    if ( ! empty( $_COOKIE[ $cookie ] ) ) {
        return;
    }
    $current = vance_get_view_count( $post_id );
    update_post_meta( $post_id, '_vance_view_count', $current + 1 );
    if ( ! headers_sent() ) {
        setcookie( $cookie, '1', time() + HOUR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN );
    }
}
add_action( 'wp', 'vance_track_post_view' );

function vance_health_hub_scripts() {
    // Enqueue Google Fonts
    wp_enqueue_style( 'vance-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;700&display=swap', array(), null );
    
    // Enqueue Main Styles
    // We will copy the prototype CSS to a file named 'main.css' in the theme folder
    // Version bumped to force browser/edge cache-miss after Vance Medical rebrand (teal palette + larger logo).
    // 2026-06-03: bumped to 2.6.0 to ship the sub-category grouped-archive layouts
    // (.va-layout-grid/bento/asymmetric/posters). filemtime() suffix guarantees the
    // edge/browser cache busts whenever main.css changes from here on.
    wp_enqueue_style( 'vance-main-style', get_template_directory_uri() . '/assets/css/main.css', array(), '2.8.0-subcat-layouts-' . ( @filemtime( get_template_directory() . '/assets/css/main.css' ) ?: '1' ) );

    // Phase 1 mobile hardening overrides. Enqueued AFTER main.css so equal-specificity
    // rules here win the cascade. See assets/css/mobile-base.css + MOBILE-PLAN.md §1.
    wp_enqueue_style( 'vance-mobile-base', get_template_directory_uri() . '/assets/css/mobile-base.css', array( 'vance-main-style' ), '2.3.4-mobile-menu-transition-fix' );

    // Phase 2 mobile components (bottom nav, etc.). All rules are gated behind
    // @media (max-width:767.98px) AND behind Customizer toggles that default OFF,
    // so this file is inert on desktop and until a feature is explicitly enabled.
    // See MOBILE-PLAN.md §2.
    // The fixed version string alone would not bust the edge cache when this file
    // changes (2026-08-06: navy dividers), so it now carries a filemtime() suffix
    // like main.css does, and every later edit busts itself.
    wp_enqueue_style( 'vance-mobile-components', get_template_directory_uri() . '/assets/css/mobile-components.css', array( 'vance-mobile-base' ), '2.5.0-vance-mobile-phase3-' . ( @filemtime( get_template_directory() . '/assets/css/mobile-components.css' ) ?: '1' ) );

    // Enqueue Theme Stylesheet (style.css)
    wp_enqueue_style( 'vance-style', get_stylesheet_uri() );

    // Modal kit — the malnutrition calculator's design language, shared by the
    // health quiz, the Discovery/Content Filters modal, the clinical profile
    // editor and VANCE-Ai. Loaded site-wide because every one of those modals
    // is mounted from the footer on any page.
    wp_enqueue_style(
        'vance-modal-kit',
        get_template_directory_uri() . '/assets/css/vance-modal-kit.css',
        array( 'vance-main-style' ),
        @filemtime( get_template_directory() . '/assets/css/vance-modal-kit.css' ) ?: '1.0.0'
    );

    // Header search — the expandable field in .header-actions (header.php).
    // Site-wide: the header renders on every page. Tiny, no dependencies, and
    // deferred to the footer, so it costs nothing on templates that never open
    // it. See assets/js/vance-header-search.js.
    wp_enqueue_script(
        'vance-header-search',
        get_template_directory_uri() . '/assets/js/vance-header-search.js',
        array(),
        @filemtime( get_template_directory() . '/assets/js/vance-header-search.js' ) ?: '1.0.0',
        true
    );

    // Knowledgebase lobby — CSS enqueued only on that page.
    // is_page_template() alone is NOT enough here: this filename also matches
    // WP's page-{slug}.php convention, so a Page with slug `knowledgebase`
    // renders through the template with _wp_page_template still 'default' and
    // is_page_template() false — the page would load with no styles at all.
    // (Same trap already documented for page-dashboard.php further down.)
    if ( is_page_template( 'page-knowledgebase.php' ) || is_page( 'knowledgebase' ) ) {
        wp_enqueue_style(
            'vance-knowledgebase',
            get_template_directory_uri() . '/assets/css/knowledgebase.css',
            array( 'vance-main-style' ),
            @filemtime( get_template_directory() . '/assets/css/knowledgebase.css' ) ?: '1.0.0'
        );
    }

    // GI Health section — CSS enqueued only on hub + condition pages.
    if ( is_page_template( 'page-gi-health.php' ) || is_page_template( 'page-gi-condition.php' ) ) {
        wp_enqueue_style(
            'vance-gi-health',
            get_template_directory_uri() . '/assets/css/gi-health.css',
            array( 'vance-main-style' ),
            @filemtime( get_template_directory() . '/assets/css/gi-health.css' ) ?: '1.0.0'
        );
    }

    // References accordion — single articles + GI Health condition/hub pages.
    // Progressively enhances the "References & further reading" block into an
    // animated collapsible disclosure (assets/js/references-accordion.js).
    if ( is_single() || is_page_template( 'page-gi-health.php' ) || is_page_template( 'page-gi-condition.php' ) ) {
        wp_enqueue_style(
            'vance-references-accordion',
            get_template_directory_uri() . '/assets/css/references-accordion.css',
            array(),
            @filemtime( get_template_directory() . '/assets/css/references-accordion.css' ) ?: '1.0.0'
        );
        wp_enqueue_script(
            'vance-references-accordion',
            get_template_directory_uri() . '/assets/js/references-accordion.js',
            array(),
            @filemtime( get_template_directory() . '/assets/js/references-accordion.js' ) ?: '1.0.0',
            true
        );
    }

    // User Guide page — CSS/JS enqueued only on that page (scroll-reveal,
    // scrollspy sub-nav, screenshot/GIF lightbox).
    if ( is_page_template( 'page-user-guide.php' ) ) {
        wp_enqueue_style(
            'vance-user-guide',
            get_template_directory_uri() . '/assets/css/user-guide.css',
            array( 'vance-main-style' ),
            @filemtime( get_template_directory() . '/assets/css/user-guide.css' ) ?: '1.0.0'
        );
        wp_enqueue_script(
            'vance-user-guide',
            get_template_directory_uri() . '/assets/js/user-guide.js',
            array(),
            @filemtime( get_template_directory() . '/assets/js/user-guide.js' ) ?: '1.0.0',
            true
        );
    }

    // Gastro Recipes & Meal Planner — the recipe grid + 7x4 planner app
    // (template-parts/recipe-hub-app.php), used both by the standalone hub
    // page (Phase 3) and the dashboard's "My Recipes" tab (page-dashboard.php,
    // case 'my-recipes'). Config built by vance_recipe_planner_script_config()
    // in inc/recipe-frontend.php, which needs the query vars this same request
    // is rendering the page from (?plan=, ?add=), so it can't be precomputed.
    $vance_on_recipe_hub_page = is_page_template( 'page-gastro-recipies.php' );
    // is_page('dashboard'), not is_page_template('page-dashboard.php'): that
    // file is resolved via WP's page-{slug}.php filename convention, not an
    // explicit _wp_page_template meta value (confirmed empty on the live
    // page 2026-08-20), so is_page_template() for it is always false.
    $vance_on_dashboard_my_recipes = is_page( 'dashboard' )
        && isset( $_GET['tab'] ) && 'my-recipes' === $_GET['tab'];
    if ( $vance_on_recipe_hub_page || $vance_on_dashboard_my_recipes ) {
        wp_enqueue_style(
            'vance-recipe-hub',
            get_template_directory_uri() . '/assets/css/recipe-hub.css',
            array( 'vance-main-style' ),
            @filemtime( get_template_directory() . '/assets/css/recipe-hub.css' ) ?: '1.0.0'
        );
        wp_enqueue_script(
            'vance-recipe-planner',
            get_template_directory_uri() . '/assets/js/recipe-planner.js',
            array(),
            @filemtime( get_template_directory() . '/assets/js/recipe-planner.js' ) ?: '1.0.0',
            true
        );
        wp_localize_script( 'vance-recipe-planner', 'vanceRecipePlanner', vance_recipe_planner_script_config() );
    }

    // Single recipe page — servings scaler, "Add to meal plan" quick-add
    // modal, and PDF export. html2pdf.js is the same CDN build/version
    // page-dashboard.php already uses for meal-plan PDF export.
    if ( is_singular( 'vance_recipe' ) ) {
        wp_enqueue_script(
            'html2pdf',
            'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js',
            array(),
            '0.10.1',
            true
        );
        wp_enqueue_script(
            'vance-recipe-single',
            get_template_directory_uri() . '/assets/js/recipe-single.js',
            array( 'html2pdf' ),
            @filemtime( get_template_directory() . '/assets/js/recipe-single.js' ) ?: '1.0.0',
            true
        );
        // get_queried_object_id(), not get_the_ID(): this runs from wp_head(),
        // before single-vance_recipe.php's own the_post() call sets up the loop.
        wp_localize_script( 'vance-recipe-single', 'vanceRecipeSingle', vance_recipe_single_script_config( get_queried_object_id() ) );
    }

    // VANCE-Ai: loaded site-wide: the modal can be opened from any page, and the
    // highlight-to-ask pill needs to be live on every article.
    wp_enqueue_style(
        'vance-askai',
        get_template_directory_uri() . '/assets/css/vance-askai.css',
        array( 'vance-main-style' ),
        @filemtime( get_template_directory() . '/assets/css/vance-askai.css' ) ?: '1.0.0'
    );
    wp_enqueue_script(
        'vance-askai',
        get_template_directory_uri() . '/assets/js/vance-askai.js',
        array(),
        @filemtime( get_template_directory() . '/assets/js/vance-askai.js' ) ?: '1.0.0',
        true
    );
    wp_localize_script( 'vance-askai', 'vanceAskAi', vance_askai_script_data() );
}
add_action( 'wp_enqueue_scripts', 'vance_health_hub_scripts' );

/**
 * True when this request is a tool loaded chromelessly inside the tool modal
 * (inc/tool-modal.php appends ?tool_embed=1 to the iframe URL).
 */
function vance_is_tool_embed() {
    return isset( $_GET['tool_embed'] ) && '1' === $_GET['tool_embed'];
}

/**
 * Strip everything the embedded tool view cannot use.
 *
 * `get_header('embed')` gives the tool a chromeless shell, but it still runs
 * wp_head(), so the iframe was booting the entire site stack before the tool
 * itself got a connection. Measured on /ibd-recipies/?tool_embed=1: 13 requests
 * and a 1.7s load event before the tool's own iframe had started, of which the
 * tool needed almost none —
 *
 *   google-gsi        283ms   sign-in client, but the embed has no login form
 *   vance-askai (js)   98ms   chat + highlight-to-ask, neither reachable here
 *   dashicons/emoji           admin-bar and emoji shims, invisible in an iframe
 *
 * Dropping them leaves the tool competing for far fewer connections, so the
 * modal paints the tool instead of a spinner.
 *
 * Two things are deliberately left alone:
 *
 *   jQuery — nothing the theme renders in this view uses it, but plugin code on
 *   wp_footer may, and a script that declares it as a dependency would drag it
 *   back in regardless. It is also already warm in the browser cache from the
 *   parent page, so removing it would buy close to nothing for real risk.
 *
 *   Analytics and consent tags — they come from plugins (Site Kit, Complianz).
 *   Silently changing what a site measures, or what consent it collects, is the
 *   owner's call and not a performance fix to make unilaterally. Suppressing
 *   them in this view would cut roughly another 400ms.
 *
 * Priority 999 so this runs after every enqueue it needs to undo.
 */
function vance_tool_embed_slim_assets() {
    if ( ! vance_is_tool_embed() ) {
        return;
    }

    foreach ( array( 'vance-askai', 'google-gsi', 'vance-header-search' ) as $handle ) {
        wp_dequeue_script( $handle );
    }
    foreach ( array( 'vance-askai', 'dashicons' ) as $handle ) {
        wp_dequeue_style( $handle );
    }
}
add_action( 'wp_enqueue_scripts', 'vance_tool_embed_slim_assets', 999 );

/**
 * Emoji shim + generator cruft off in the embed view. Separate from the
 * dequeue pass above because these hang off wp_head/wp_print_styles rather
 * than being enqueued handles.
 */
function vance_tool_embed_trim_head() {
    if ( ! vance_is_tool_embed() ) {
        return;
    }
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'wlwmanifest_link' );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'rest_output_link_wp_head' );
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );
    show_admin_bar( false );
}
add_action( 'template_redirect', 'vance_tool_embed_trim_head' );

/**
 * Keep the per-user dashboard views out of every page cache.
 *
 * LiteSpeed's "cache logged-in users" private cache is enabled with a 30 minute
 * TTL and no URI or role exclusions, so /dashboard/ was being served from a
 * per-user snapshot. Saved VANCE-Ai chats, notes, bookmarks and calculator
 * results are all written by REST/AJAX calls that never purge that snapshot, so
 * a brand new entry stayed invisible for up to half an hour and read as "it did
 * not save". These pages are unique per user and change on every write; they
 * must never be cached.
 *
 * Matched by slug as well as by assigned template: they resolve through
 * WordPress's page-{slug}.php hierarchy rather than a saved template, so
 * is_page_template() alone returns false.
 *
 * Also covers the Healthcare Quiz and the tool-page-shell.php-based tool
 * pages (Malnutrition Calculator, IBD Recipes): each bakes
 * `is_user_logged_in()` straight into the emitted HTML/JS at render time
 * (e.g. `var loggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>` in
 * tool-page-shell.php, and an `if(is_user_logged_in())` PHP guard around the
 * quiz's whole save-AJAX block in page-healthcare-quiz.php). A visitor who
 * loads one of these pages anonymously, then registers/logs in without a
 * fresh full page load, is served the still-cached anonymous snapshot on
 * their next request — its JS believes they're logged out, so quiz/tool
 * results silently fail to save instead of persisting to `_sla_*` meta.
 */
/**
 * Permanent redirect for the Gastro Health Survey's retired URL.
 *
 * The WP page was renamed `healthcare-quiz` -> `gastro-health-survey` (post
 * 440, still on page-healthcare-quiz.php), which left `/healthcare-quiz/`
 * returning a 404 while the theme, two saved customizer links and anything
 * published externally still pointed at it. The in-theme links are now
 * updated; this covers everything outside the repo — bookmarks, emails,
 * search results, and PDFs already downloaded by readers.
 *
 * Kept deliberately narrow: one exact path, and it no-ops the moment a real
 * page ever claims that slug again.
 */
function vance_redirect_legacy_quiz_slug() {
	if ( is_admin() ) {
		return;
	}

	$path = trim( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ?: '', '/' );
	if ( 'healthcare-quiz' !== $path ) {
		return;
	}

	// If someone restores a page on the old slug, let it win over this rule.
	if ( get_page_by_path( 'healthcare-quiz' ) ) {
		return;
	}

	$target = get_page_by_path( 'gastro-health-survey' );
	if ( ! $target ) {
		return;
	}

	// Query string is dropped on purpose: the only links carrying one are the
	// customizer-preview URLs accidentally saved into theme mods.
	wp_safe_redirect( get_permalink( $target ), 301, 'Vance legacy quiz slug' );
	exit;
}
add_action( 'template_redirect', 'vance_redirect_legacy_quiz_slug', 1 );

/**
 * Permanent redirects for superseded pages.
 *
 * Distinct from vance_redirect_legacy_quiz_slug() above, which covers a slug
 * whose page no longer exists. Every source below IS a real published page —
 * it is just a duplicate or a dead end that should never have stayed live:
 *
 *   gastro-recipies      Renders the identical tool to /gastro-meal-planner/
 *                        via the same template. Audited 2026-08-28: the meal
 *                        planner (id 3293, template explicitly assigned,
 *                        modified 2026-08-20) carries all 18 internal links
 *                        in the theme; this one (id 767) carries none and is
 *                        the older of the two. The template FILE is named
 *                        page-gastro-recipies.php, which makes this look like
 *                        the canonical URL. It is not — that is a filename
 *                        coincidence, and the misspelling ("recipies") is
 *                        reason enough not to keep it as the public URL.
 *
 *   meal-plan            Blank. Renders a hero and an empty content div.
 *   take-our-survey      Blank. Superseded by /gastro-health-survey/.
 *   take-a-quiz-2        Blank. The "-2" suffix means a deleted page still
 *                        holds /take-a-quiz/. The quiz IS the survey.
 *   clinical-reviews-2   Blank. A static twin of a live category archive.
 *
 * Query strings are preserved, unlike the quiz rule above — its sources were
 * customizer-preview URLs where the query string was the bug. Here a visitor
 * could legitimately arrive with one (e.g. a UTM tag) and should keep it.
 *
 * 301 rather than 302 on purpose: the point is to consolidate duplicate
 * content for search. Note that browsers cache a 301 indefinitely, so
 * reverting one is slow for anyone who has already followed it.
 */
function vance_redirect_superseded_pages() {
	if ( is_admin() ) {
		return;
	}

	// slug => target. A string is a page path; array('category', $slug) is a term.
	$map = array(
		'gastro-recipies'    => 'gastro-meal-planner',
		'meal-plan'          => 'gastro-meal-planner',
		'take-our-survey'    => 'gastro-health-survey',
		'take-a-quiz-2'      => 'gastro-health-survey',
		'clinical-reviews-2' => array( 'category', 'content-clinical-reviews' ),
	);

	$path = trim( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ?: '', '/' );
	if ( ! isset( $map[ $path ] ) ) {
		return;
	}

	$target = $map[ $path ];

	if ( is_array( $target ) ) {
		$term = get_category_by_slug( $target[1] );
		if ( ! $term ) { return; }
		$url = get_category_link( $term );
	} else {
		$page = get_page_by_path( $target );
		// Never redirect into a 404: if the destination has gone, leave the
		// source rendering whatever it renders today.
		if ( ! $page || 'publish' !== $page->post_status ) { return; }
		$url = get_permalink( $page );
	}

	if ( ! $url ) {
		return;
	}

	$query = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY );
	if ( $query ) {
		$url .= ( strpos( $url, '?' ) === false ? '?' : '&' ) . $query;
	}

	wp_safe_redirect( $url, 301, 'Vance superseded page' );
	exit;
}
add_action( 'template_redirect', 'vance_redirect_superseded_pages', 1 );

function vance_no_cache_account_pages() {
    $slugs     = array( 'dashboard', 'my-notes', 'healthcare-quiz', 'malnutrition-calculator', 'gastro-meal-planner' );
    $templates = array( 'page-dashboard.php', 'page-my-notes.php', 'page-healthcare-quiz.php', 'page-malnutrition-calculator.php', 'page-gastro-recipies.php' );

    if ( ! is_page( $slugs ) && ! is_page_template( $templates ) ) {
        return;
    }

    nocache_headers();
    // No-op when LiteSpeed is inactive.
    do_action( 'litespeed_control_set_nocache', 'per-user account page' );
}
add_action( 'template_redirect', 'vance_no_cache_account_pages' );

/**
 * Config handed to assets/js/vance-askai.js.
 *
 * The REST nonce is always printed so the chat endpoint can identify a
 * logged-in user and auto-save their conversation; the JS falls back to an
 * anonymous request if a cached page ever serves a stale nonce.
 */
/**
 * How often the article intro popup may appear for one visitor.
 *
 * @return string One of x_per_hour, hourly, x_per_day, daily, weekly, monthly.
 */
function vance_askai_intro_frequency() {
    $allowed = array( 'x_per_hour', 'hourly', 'x_per_day', 'daily', 'weekly', 'monthly' );
    $value   = (string) vance_get_theme_mod( 'vance_askai_intro_frequency', 'monthly' );
    return in_array( $value, $allowed, true ) ? $value : 'monthly';
}

function vance_askai_script_data() {
    $post_id = is_singular( vance_ai_source_post_types() ) ? get_queried_object_id() : 0;

    // No article context on the front page (its <article> elements are teaser
    // cards, not reading copy) or on the dedicated VANCE-Ai page, which hosts the
    // full inline chat. Both would otherwise arm the selection pill.
    //
    // The VANCE-Ai page is matched by slug as well as by assigned template: it
    // resolves through WordPress's page-{slug}.php hierarchy rather than a saved
    // template, so is_page_template() alone returns false there.
    if ( $post_id && ( is_front_page() || is_page_template( 'page-ask-ai.php' ) || is_page( 'ask-ai' ) ) ) {
        $post_id = 0;
    }

    $allowed_links = array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) );

    if ( is_user_logged_in() ) {
        $foot = sprintf(
            /* translators: %s: dashboard URL */
            __( 'Saved to <a href="%s">your dashboard</a>. General information from this hub only, not personal medical advice.', 'vance-health-hub' ),
            esc_url( home_url( '/dashboard/?tab=ai-chats' ) )
        );
    } else {
        $foot = sprintf(
            /* translators: %s: registration URL */
            __( '<a href="%s">Register for FREE</a> to save your conversations. General information from this hub only, not personal medical advice.', 'vance-health-hub' ),
            esc_url( home_url( '/login/?tab=signup' ) )
        );
    }

    // Shown behind a "Important: how to use this assistant" disclosure in the
    // chat, so the full caveats travel with every surface the chat appears on
    // rather than living only on the VANCE-Ai page.
    $disclaimer = sprintf(
        /* translators: %s: medical disclaimer page URL */
        __( '<p><strong>VANCE-Ai gives general information only.</strong> It answers from articles published on this hub and a curated reference library. It is an automated assistant: it can be wrong, incomplete or out of date.</p>
<p>It does not know your medical history, and it does not provide a diagnosis, a prescription or a treatment plan. It is not a substitute for advice from your own healthcare team, and must not be used for urgent or emergency needs. <strong>If you feel unwell or think you may have a medical emergency, call 999, or NHS 111 now.</strong></p>
<p>Where an answer draws on general knowledge rather than this hub\'s library, it will say so on the line concerned.</p>
<p>Please do not type anything that identifies you or another person. Conversations are processed by a third-party AI provider and may be stored to improve the service. If you are signed in, your conversations are saved to your account and you can delete them at any time.</p>
<p>By using VANCE-Ai you accept that it is for general information only. <a href="%s" target="_blank" rel="noopener">Read the full medical disclaimer</a>.</p>', 'vance-health-hub' ),
        esc_url( home_url( '/medical-disclaimer/' ) )
    );

    // Popup copy is Customizer-editable; these fallbacks match the "Demystify
    // Your Health Journey" mockup, so a fresh install (or any field saved empty)
    // still renders the intended design.
    $intro_defaults = array(
        'title'    => __( 'Demystify Your Health Journey', 'vance-health-hub' ),
        'subtitle' => __( 'VANCE-Ai helps you understand complex medical terms instantly as you read.', 'vance-health-hub' ),
        'feat1_t'  => __( 'Highlight to explain', 'vance-health-hub' ),
        'feat1_d'  => __( 'Get clear, instant explanations for any medical term.', 'vance-health-hub' ),
        'feat2_t'  => __( 'Personalized reading levels', 'vance-health-hub' ),
        'feat2_d'  => __( 'Content tailored to your health literacy and preferences.', 'vance-health-hub' ),
        'lead'     => __( 'Enable Vance-Ai by Clicking Below', 'vance-health-hub' ),
        'cta'       => __( 'ACTIVATE', 'vance-health-hub' ),
        'cta2'      => __( 'ACTIVATE & TRY', 'vance-health-hub' ),
        'activated' => __( 'ACTIVATED', 'vance-health-hub' ),
        'trust'    => __( 'Secure. Private. Always by your side.', 'vance-health-hub' ),
    );

    // Fall back to the default when a setting exists but was saved empty, not
    // only when it is unset.
    $intro_field = function ( $key, $default ) {
        $value = trim( (string) vance_get_theme_mod( $key, '' ) );
        return '' === $value ? $default : $value;
    };

    $intro_title    = $intro_field( 'vance_askai_intro_title', $intro_defaults['title'] );
    $intro_subtitle = $intro_field( 'vance_askai_intro_subtitle', $intro_defaults['subtitle'] );
    $intro_lead     = $intro_field( 'vance_askai_intro_lead', $intro_defaults['lead'] );
    $intro_cta       = $intro_field( 'vance_askai_intro_cta', $intro_defaults['cta'] );
    $intro_cta2      = $intro_field( 'vance_askai_intro_cta2', $intro_defaults['cta2'] );
    $intro_activated = $intro_field( 'vance_askai_intro_activated', $intro_defaults['activated'] );
    $intro_trust    = $intro_field( 'vance_askai_intro_trust', $intro_defaults['trust'] );

    $intro_features = array(
        array(
            'title' => $intro_field( 'vance_askai_intro_feat1_title', $intro_defaults['feat1_t'] ),
            'desc'  => $intro_field( 'vance_askai_intro_feat1_desc', $intro_defaults['feat1_d'] ),
        ),
        array(
            'title' => $intro_field( 'vance_askai_intro_feat2_title', $intro_defaults['feat2_t'] ),
            'desc'  => $intro_field( 'vance_askai_intro_feat2_desc', $intro_defaults['feat2_d'] ),
        ),
    );

    // The popup logo defaults to the site header wordmark so it is branded out of
    // the box; a Customizer image overrides it.
    $intro_logo = trim( (string) vance_get_theme_mod( 'vance_askai_intro_logo', '' ) );
    if ( '' === $intro_logo ) {
        $intro_logo = get_template_directory_uri() . '/assets/img/logo.png';
    }

    $intro_image = trim( (string) vance_get_theme_mod( 'vance_askai_intro_image', '' ) );

    // Logged-in visitors already skip the "Register for free" link (below); when
    // this variant is switched on, an admin can also swap the headline copy,
    // image and buttons so returning members see a distinct message instead of
    // a sign-up pitch aimed at strangers.
    if ( is_user_logged_in() && (bool) vance_get_theme_mod( 'vance_askai_intro_loggedin_enable', false ) ) {
        $intro_title    = $intro_field( 'vance_askai_intro_loggedin_title', $intro_title );
        $intro_subtitle = $intro_field( 'vance_askai_intro_loggedin_subtitle', $intro_subtitle );
        $intro_lead     = $intro_field( 'vance_askai_intro_loggedin_lead', $intro_lead );
        $intro_cta      = $intro_field( 'vance_askai_intro_loggedin_cta', $intro_cta );
        $intro_cta2     = $intro_field( 'vance_askai_intro_loggedin_cta2', $intro_cta2 );
        $loggedin_image = trim( (string) vance_get_theme_mod( 'vance_askai_intro_loggedin_image', '' ) );
        if ( '' !== $loggedin_image ) {
            $intro_image = $loggedin_image;
        }
    }

    $levels = array();
    foreach ( vance_ai_reading_levels() as $key => $level ) {
        $levels[] = array(
            'key'   => $key,
            'label' => $level['label'],
        );
    }

    return array(
        'endpoint'      => esc_url_raw( rest_url( 'vance-health/v1/ai-chat' ) ),
        'clearEndpoint' => esc_url_raw( rest_url( 'vance-health/v1/ai-chat/clear' ) ),
        'nonce'         => wp_create_nonce( 'wp_rest' ),
        'isLoggedIn'    => is_user_logged_in(),
        'registerUrl'   => esc_url( home_url( '/login/?tab=signup' ) ),
        'postId'        => $post_id,
        'postTitle'     => $post_id ? get_the_title( $post_id ) : '',
        'postUrl'       => $post_id ? esc_url( get_permalink( $post_id ) ) : '',
        'highlight'     => (bool) vance_get_theme_mod( 'vance_askai_highlight_enable', true ),
        // "Add to note" rides on the same selection pill as highlight-to-ask, so
        // it shares that toggle rather than adding a second orphan setting.
        'ajaxUrl'       => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
        'notesNonce'    => is_user_logged_in() ? wp_create_nonce( 'vance_dashboard_nonce' ) : '',
        'notesUrl'      => esc_url( home_url( '/my-notes/' ) ),
        'introEnabled'  => (bool) vance_get_theme_mod( 'vance_askai_intro_popup', true ),
        'introFrequency' => vance_askai_intro_frequency(),
        'introPerDay'   => max( 1, absint( vance_get_theme_mod( 'vance_askai_intro_per_day', 2 ) ) ),
        'introPerHour'  => max( 1, absint( vance_get_theme_mod( 'vance_askai_intro_per_hour', 2 ) ) ),
        'introImage'    => esc_url( $intro_image ),
        'levels'        => $levels,
        'defaultLevel'  => 'knowledgeable',
        'title'         => __( 'VANCE-Ai', 'vance-health-hub' ),
        'subtitle'      => __( 'Answers from the Vance Medical Hub library', 'vance-health-hub' ),
        'placeholder'   => __( 'Ask about IBD, gut health or nutrition…', 'vance-health-hub' ),
        'intro'         => __( 'Ask a question and I will answer using articles published on this hub, with links to the ones I used.', 'vance-health-hub' ),
        'introTitle'    => $intro_title,
        'introSubtitle' => $intro_subtitle,
        'introFeatures' => $intro_features,
        'introLead'     => $intro_lead,
        'introCta'      => $intro_cta,
        'introCta2'     => $intro_cta2,
        'introActivated' => $intro_activated,
        'introTrust'    => $intro_trust,
        'introLogo'     => esc_url( $intro_logo ),
        'footNote'      => wp_kses( $foot, $allowed_links ),
        'disclaimer'    => wp_kses_post( $disclaimer ),
        // Kept short: they sit on a single row under the intro line.
        'suggestions'   => array(
            __( 'What is IBD?', 'vance-health-hub' ),
            __( 'Diet and IBD symptoms', 'vance-health-hub' ),
            __( 'Crohn\'s vs colitis', 'vance-health-hub' ),
        ),
        'i18n'          => array(
            'askPill'         => __( 'Ask VANCE-Ai', 'vance-health-hub' ),
            'notePill'        => __( 'Add to note', 'vance-health-hub' ),
            'noteTitle'       => __( 'Add highlight to a note', 'vance-health-hub' ),
            'noteExisting'    => __( 'Your notes', 'vance-health-hub' ),
            'noteLoading'     => __( 'Loading your notes…', 'vance-health-hub' ),
            'noteListFailed'  => __( 'Could not load your notes.', 'vance-health-hub' ),
            'noteNone'        => __( 'You have no notes yet. Create your first one below.', 'vance-health-hub' ),
            'noteNewLabel'    => __( 'Or create a new note', 'vance-health-hub' ),
            'noteNewHint'     => __( 'Note title', 'vance-health-hub' ),
            'noteCreate'      => __( 'Create note', 'vance-health-hub' ),
            'noteSaving'      => __( 'Saving…', 'vance-health-hub' ),
            'noteSaved'       => __( 'Added to your note.', 'vance-health-hub' ),
            'noteOpen'        => __( 'Open note', 'vance-health-hub' ),
            'noteFailed'      => __( 'Could not save to your note. Please try again.', 'vance-health-hub' ),
            'noteSignedOut'   => __( 'Register for FREE to save highlights straight into your own notes.', 'vance-health-hub' ),
            'noteFrom'        => __( 'From', 'vance-health-hub' ),
            'close'           => __( 'Close', 'vance-health-hub' ),
            'send'            => __( 'Send', 'vance-health-hub' ),
            'newChat'         => __( 'New chat', 'vance-health-hub' ),
            'clearChat'       => __( 'Clear', 'vance-health-hub' ),
            'clearConfirm'    => __( 'Clear this conversation? It will also be removed from your saved chats.', 'vance-health-hub' ),
            'levelLabel'      => __( 'Answer detail', 'vance-health-hub' ),
            'disclaimerTitle' => __( 'Important: how to use this assistant', 'vance-health-hub' ),
            'register'        => __( 'Register for FREE', 'vance-health-hub' ),
            'introRegister'   => __( 'New here? Register for free', 'vance-health-hub' ),
            'tryIt'           => __( 'Try it now', 'vance-health-hub' ),
            'failed'          => __( 'The assistant is unavailable right now. Please try again shortly.', 'vance-health-hub' ),
            'empty'           => __( 'No answer came back. Please try again.', 'vance-health-hub' ),
        ),
    );
}

function vance_health_hub_setup() {
    // Add default posts and comments RSS feed links to head.
    add_theme_support( 'automatic-feed-links' );

    // Let WordPress manage the document title.
    add_theme_support( 'title-tag' );

    // Enable support for Post Thumbnails on posts and pages.
    add_theme_support( 'post-thumbnails' );

    // Register Navigation Menus
    register_nav_menus(
        array(
            'primary-menu' => esc_html__( 'Primary Menu', 'vance-health-hub' ),
            'footer-menu-1' => esc_html__( 'Footer Menu Topics', 'vance-health-hub' ),
            'footer-menu-2' => esc_html__( 'Footer Menu Professionals', 'vance-health-hub' ),
            'footer-menu-3' => esc_html__( 'Footer Menu Patients', 'vance-health-hub' ),
        )
    );
}
add_action( 'after_setup_theme', 'vance_health_hub_setup' );

/**
 * Remove "Category:" prefix from archive titles
 */
function vance_remove_category_prefix( $title ) {
    if ( is_category() ) {
        $title = single_cat_title( '', false );
    }
    return $title;
}
add_filter( 'get_the_archive_title', 'vance_remove_category_prefix' );

/**
 * Include Custom Post Types in Category Archives
 * This ensures that both standard posts and CPTs appear when viewing a category
 */
function vance_include_cpts_in_category_archives( $query ) {
    // Only modify the main query on category archives
    if ( ! is_admin() && $query->is_main_query() && is_category() ) {
        // Include both standard posts and all CPTs
        $query->set( 'post_type', vance_discovery_post_types() );
    }
}
add_action( 'pre_get_posts', 'vance_include_cpts_in_category_archives' );

/**
 * Register Custom Post Types
 * News, Clinical Research Reviews, Op-Eds, Product reviews, White papers, Podcasts, Webinars, Courses, Infographics
 */
function vance_content_cpts() {
    return array(
        'news' => 'Healthcare News',
        'research' => 'Clinical Reviews',
        'oped' => 'Expert Opinions',
        'review' => 'Reviews',
        'whitepaper' => 'Tools & Resources',
        'podcast' => 'Media Library',
        'webinar' => 'Webinars',
        'course' => 'Education Courses',
        'infographic' => 'Infographic Gallery'
    );
}

/**
 * Every post type the Discovery Suite and the category archives search over.
 * Both used to carry their own copy of this list and had drifted — the results
 * page searched `any` (so it also picked up unrelated public types) while the
 * archives used an explicit list.
 */
function vance_discovery_post_types() {
    return array_merge( array( 'post' ), array_keys( vance_content_cpts() ) );
}

function vance_register_cpts() {
    foreach (vance_content_cpts() as $slug => $name) {
        $labels = array(
            'name'                  => _x( $name . 's', 'Post Type General Name', 'vance-health-hub' ),
            'singular_name'         => _x( $name, 'Post Type Singular Name', 'vance-health-hub' ),
            'menu_name'             => __( $name . 's', 'vance-health-hub' ),
            'all_items'             => __( 'All ' . $name . 's', 'vance-health-hub' ),
            'add_new_item'          => __( 'Add New ' . $name, 'vance-health-hub' ),
        );
        $args = array(
            'label'                 => __( $name, 'vance-health-hub' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'author' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'has_archive'           => true,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'map_meta_cap'          => true,
            'show_in_rest'          => true,
            // Hidden from the admin sidebar entirely. remove_menu_page() at
            // admin_menu:999 (and a later PHP_INT_MAX pass) proved unreliable on
            // live — the nine "…ss" menus rendered anyway — so prevent core from
            // ever adding the menu instead of trying to strip it afterwards.
            // Admins/editors keep edit access via direct edit.php?post_type=<slug>
            // URLs (capability_type=post → edit_posts). Managed via the
            // VanceHealthHub dashboard, not their own top-level menus.
            'show_in_menu'          => false,
            'taxonomies'            => array('category', 'post_tag'),
            'rewrite'               => array(
                'slug'                  => $slug,
                'with_front'            => false,
                'pages'                 => true,
                'feeds'                 => true,
            ),
        );
        register_post_type( $slug, $args );
    }
}
add_action( 'init', 'vance_register_cpts' );

/**
 * Explicitly grant CPT capabilities to Administrator
 */
function vance_grant_cpt_caps() {
    $role = get_role( 'administrator' );
    if ( ! $role ) return;

    foreach (array_keys(vance_content_cpts()) as $cpt) {
        $role->add_cap( "edit_{$cpt}" );
        $role->add_cap( "read_{$cpt}" );
        $role->add_cap( "delete_{$cpt}" );
        $role->add_cap( "edit_{$cpt}s" );
        $role->add_cap( "edit_others_{$cpt}s" );
        $role->add_cap( "publish_{$cpt}s" );
        $role->add_cap( "read_private_{$cpt}s" );
        $role->add_cap( "delete_{$cpt}s" );
        $role->add_cap( "delete_others_{$cpt}s" );
        $role->add_cap( "delete_private_{$cpt}s" );
        $role->add_cap( "delete_published_{$cpt}s" );
        $role->add_cap( "edit_private_{$cpt}s" );
        $role->add_cap( "edit_published_{$cpt}s" );
    }
}
add_action( 'admin_init', 'vance_grant_cpt_caps' );

/**
 * Flush rewrite rules on theme activation
 * This ensures the new permalink structure takes effect
 */
function vance_flush_rewrite_rules() {
    vance_register_cpts();
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'vance_flush_rewrite_rules' );

// Create Content Hub Menu
function vance_register_content_hub_menu() {
    add_menu_page(
        'VanceHealthHub',
        'VanceHealthHub',
        'manage_options',
        'vance-content-hub',
        'vance_render_content_hub_dashboard',
        'dashicons-category',
        1
    );

    add_submenu_page(
        'vance-content-hub',
        'Content Hub Station',
        'Content Hub Station',
        'manage_options',
        'vance-content-hub',
        'vance_render_content_hub_dashboard'
    );

    add_submenu_page(
        'vance-content-hub',
        'Customize Hub',
        'Customize Hub',
        'manage_options',
        'customize.php'
    );

    // Relocate CPTs manually to fix permission breakage while hiding top-level items
    $cpts = array(
        'news' => 'Healthcare News',
        'research' => 'Clinical Reviews',
        'oped' => 'Expert Opinions',
        'review' => 'Reviews', // Removes the buggy default "Reviewss" top-level menu entry
        'whitepaper' => 'Tools & Resources',
        'podcast' => 'Media Library',
        'webinar' => 'Webinars',
        'course' => 'Education Courses',
        'infographic' => 'Infographic Gallery'
    );

    foreach ($cpts as $slug => $name) {
        remove_menu_page('edit.php?post_type=' . $slug);
        // Submenus removed as per request to only show Station and Customize
    }
}
add_action( 'admin_menu', 'vance_register_content_hub_menu', 999 );

/**
 * Nest the Comments screen under Posts instead of its own top-level menu item.
 */
function vance_move_comments_under_posts() {
    remove_menu_page( 'edit-comments.php' );
    add_submenu_page( 'edit.php', 'Comments', 'Comments', 'moderate_comments', 'edit-comments.php' );
}
add_action( 'admin_menu', 'vance_move_comments_under_posts', 999 );

/**
 * Belt-and-braces removal of the nine content-CPT top-level admin menus
 * ("Healthcare Newss", "Reviewss", "Expert Opinionss", …). The removal inside
 * vance_register_content_hub_menu() runs at admin_menu:999, but on live those
 * entries were still appearing — so strip them again at the very end of the
 * admin_menu chain (after any callback that could re-add them) by unsetting the
 * exact post_type slugs from $menu. The user demonstrably sees these items, so
 * they are present in $menu at this point and this pass reliably clears them.
 * The vhh_todo "Content Feedback" menu is deliberately NOT in this list.
 */
function vance_strip_content_cpt_menus() {
    global $menu;
    if ( ! is_array( $menu ) ) {
        return;
    }
    $hide = array(
        'edit.php?post_type=news',
        'edit.php?post_type=research',
        'edit.php?post_type=oped',
        'edit.php?post_type=review',
        'edit.php?post_type=whitepaper',
        'edit.php?post_type=podcast',
        'edit.php?post_type=webinar',
        'edit.php?post_type=course',
        'edit.php?post_type=infographic',
    );
    foreach ( $menu as $i => $item ) {
        if ( isset( $item[2] ) && in_array( $item[2], $hide, true ) ) {
            unset( $menu[ $i ] );
        }
    }
}
add_action( 'admin_menu', 'vance_strip_content_cpt_menus', PHP_INT_MAX );

/**
 * Get SVG Icon for Category
 */
/**
 * The categories the inner category nav offers, top-level by post count.
 *
 * Lives here rather than inline in the template part because the article
 * sidebar's "Explore" panel offers the same set: an article page no longer
 * carries the nav bar, so the panel is its replacement, and the two listing
 * different categories would be worse than either listing none.
 *
 * @param int|null $limit Defaults to the vance_inner_nav_total_items setting.
 * @return WP_Term[]
 */
function vance_inner_nav_categories( $limit = null ) {
    if ( null === $limit ) {
        $limit = (int) vance_get_theme_mod( 'vance_inner_nav_total_items', 8 );
    }
    // Stored values may be 0 (an empty Customizer submit sanitised by absint).
    if ( $limit < 1 ) {
        $limit = 8;
    }

    $uncat   = get_category_by_slug( 'uncategorized' );
    $exclude = $uncat ? array( $uncat->term_id ) : array();

    return get_categories( array(
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => $limit,
        'hide_empty' => true,
        'exclude'    => $exclude,
    ) );
}

function vance_get_category_icon_url($name) {
    if (empty($name)) return '';
    
    $name = strtolower($name);
    $theme_dir = get_template_directory_uri();
    
    $mapping = array(
        'pharmaceutical' => 'pill.svg',
        'news' => 'megaphone.svg',
        'healthcare news' => 'megaphone.svg',
        'research' => 'analytics.svg',
        'clinical reviews' => 'analytics.svg',
        'expert opinions' => 'clipboard.svg',
        'oped' => 'clipboard.svg',
        'reviews' => 'star.svg',
        'product reviews' => 'star.svg',
        'tools & resources' => 'scale.svg',
        'whitepaper' => 'scale.svg',
        'media library' => 'microphone.svg',
        'podcast' => 'microphone.svg',
        'webinars' => 'video.svg',
        'education courses' => 'brain.svg',
        'course' => 'brain.svg',
        'infographic gallery' => 'dna.svg',
        'infographic' => 'dna.svg',
        'practitioner' => 'stethoscope.svg',
        'patient' => 'heart.svg',
        'industry' => 'hospital.svg',
        'neurology' => 'brain.svg',
        'cardiology' => 'pulse.svg',
        'osteology' => 'bone.svg',
        'respiratory' => 'lungs.svg',
        'orthopedic' => 'joint.svg',
        'dentistry' => 'tooth.svg',
        'ophthalmology' => 'eye.svg',
        'supplementation' => 'pill.svg',
        'medical food' => 'apple.svg',
        'lifestyle' => 'heart.svg'
    );
    
    foreach ($mapping as $key => $icon) {
        if (strpos($name, $key) !== false) {
            return $theme_dir . '/assets/img/icons/' . $icon;
        }
    }
    
    // Default if no match
    return $theme_dir . '/assets/img/icons/medkit.svg';
}

/**
 * Render Content Hub Management Dashboard
 */
function vance_render_content_hub_dashboard() {
    $cpts = array(
        'news' => array('name' => 'Healthcare News', 'icon' => 'dashicons-megaphone', 'desc' => 'Articles and updates about the healthcare industry.'),
        'research' => array('name' => 'Clinical Reviews', 'icon' => 'dashicons-analytics', 'desc' => 'In-depth reviews of clinical research and trials.'),
        'oped' => array('name' => 'Expert Opinions', 'icon' => 'dashicons-id-alt', 'desc' => 'Professional perspectives and thought leadership.'),
        'review' => array('name' => 'Product Reviews', 'icon' => 'dashicons-star-filled', 'desc' => 'Reviews of healthcare products and supplements.'),
        'whitepaper' => array('name' => 'Tools & Resources', 'icon' => 'dashicons-media-text', 'desc' => 'Technical papers, guides, and professional tools.'),
        'podcast' => array('name' => 'Media Library', 'icon' => 'dashicons-microphone', 'desc' => 'Audio content and professional discussions.'),
        'webinar' => array('name' => 'Webinars', 'icon' => 'dashicons-video-alt3', 'desc' => 'Educational webinars and video presentations.'),
        'course' => array('name' => 'Education Courses', 'icon' => 'dashicons-welcome-learn-more', 'desc' => 'Structured learning and professional development.'),
        'infographic' => array('name' => 'Infographic Gallery', 'icon' => 'dashicons-format-image', 'desc' => 'Visual clinical data and educational graphics.')
    );
    ?>
    <div class="wrap" style="max-width: 1200px; margin: 30px auto;">
        <div style="background: white; border-radius: var(--radius-surface, 14px); padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 32px; font-weight: 800; color: #0A1929; margin: 0 0 10px 0; font-family: 'Outfit', sans-serif;">CONTENT HUB STATION</h1>
                    <p style="font-size: 16px; color: #64748b; margin: 0;">Manage your healthcare content and clinical resources.</p>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
            <?php foreach ($cpts as $slug => $data) : ?>
            <div style="background: white; border-radius: var(--radius-surface, 14px); border: 1px solid #e2e8f0; padding: 30px; display: flex; flex-direction: column; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 20px;">
                    <div style="width: 48px; height: 48px; background: #f1f5f9; border-radius: var(--radius-control, 6px); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <img src="<?php echo vance_get_category_icon_url($data['name']); ?>" style="width: 28px; height: 28px; object-fit: contain; filter: none !important;">
                    </div>
                    <div>
                        <h2 style="font-size: 16px; font-weight: 700; color: #0A1929; margin: 0; text-transform: uppercase;"><?php echo esc_html($data['name']); ?></h2>
                        <p style="color: #64748b; font-size: 13px; margin: 5px 0 0 0; line-height: 1.5;"><?php echo esc_html($data['desc']); ?></p>
                    </div>
                </div>
                
                <div style="margin-top: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <a href="<?php echo admin_url('edit.php?post_type=' . $slug); ?>" class="button" style="text-align: center; border-radius: var(--radius-control, 6px);">View All</a>
                    <a href="<?php echo admin_url('post-new.php?post_type=' . $slug); ?>" class="button button-primary" style="text-align: center; background: #0A1929; border-color: #0A1929; border-radius: var(--radius-control, 6px);">+ Add New</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Render Media Hub Management Dashboard
 */
function vance_render_media_hub_dashboard() {
    $cpts = array(
        'podcast' => array('name' => 'Podcasts', 'icon' => 'dashicons-microphone', 'desc' => 'Audio content and professional discussions.'),
        'webinar' => array('name' => 'Webinars & Videos', 'icon' => 'dashicons-video-alt3', 'desc' => 'Educational webinars and video presentations.'),
        'course' => array('name' => 'Courses', 'icon' => 'dashicons-welcome-learn-more', 'desc' => 'Structured learning and professional development.'),
        'infographic' => array('name' => 'Infographics', 'icon' => 'dashicons-format-image', 'desc' => 'Visual clinical data and educational graphics.'),
        'event' => array('name' => 'Events', 'icon' => 'dashicons-calendar-alt', 'desc' => 'Manage upcoming and past events, conferences, and workshops.'),
    );
    ?>
    <div class="wrap" style="max-width: 1200px; margin: 30px auto;">
        <div style="background: white; border-radius: var(--radius-surface, 14px); padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 32px; font-weight: 800; color: #0A1929; margin: 0 0 10px 0; font-family: 'Outfit', sans-serif;">MEDIA HUB STATION</h1>
                    <p style="font-size: 16px; color: #64748b; margin: 0;">Manage your multimedia content and educational resources.</p>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
            <?php foreach ($cpts as $slug => $data) : ?>
            <div style="background: white; border-radius: var(--radius-surface, 14px); border: 1px solid #e2e8f0; padding: 30px; display: flex; flex-direction: column; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 20px;">
                    <div style="width: 48px; height: 48px; background: #f1f5f9; border-radius: var(--radius-control, 6px); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <img src="<?php echo vance_get_category_icon_url($data['name']); ?>" style="width: 28px; height: 28px; object-fit: contain; filter: none !important;">
                    </div>
                    <div>
                        <h2 style="font-size: 16px; font-weight: 700; color: #0A1929; margin: 0; text-transform: uppercase;"><?php echo esc_html($data['name']); ?></h2>
                        <p style="color: #64748b; font-size: 13px; margin: 5px 0 0 0; line-height: 1.5;"><?php echo esc_html($data['desc']); ?></p>
                    </div>
                </div>
                
                <div style="margin-top: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <a href="<?php echo admin_url('edit.php?post_type=' . $slug); ?>" class="button" style="text-align: center; border-radius: var(--radius-control, 6px);">View All</a>
                    <a href="<?php echo admin_url('post-new.php?post_type=' . $slug); ?>" class="button button-primary" style="text-align: center; background: #0A1929; border-color: #0A1929; border-radius: var(--radius-control, 6px);">+ Add New</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Sync My Dashboard Profile Image with get_avatar
 */
function vance_filter_get_avatar( $args, $id_or_email ) {
    $user_id = 0;
    if ( is_numeric( $id_or_email ) ) {
        $user_id = (int) $id_or_email;
    } elseif ( is_string( $id_or_email ) && ( $user = get_user_by( 'email', $id_or_email ) ) ) {
        $user_id = $user->ID;
    } elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) ) {
        $user_id = (int) $id_or_email->user_id;
    }

    if ( $user_id ) {
        $custom_avatar = get_user_meta( $user_id, '_sla_profile_image_url', true );
        if ( $custom_avatar ) {
            $args['url'] = $custom_avatar;
        }
    }
    return $args;
}
add_filter( 'get_avatar_data', 'vance_filter_get_avatar', 10, 2 );

// Auto-assign Category based on CPT
function vance_auto_assign_category( $post_id, $post, $update ) {
    // If it's a revision, skip
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    $cpts = array(
        'news' => 'Healthcare News',
        'research' => 'Clinical Reviews',
        'oped' => 'Expert Opinions',
        'review' => 'Expert Opinions',
        'whitepaper' => 'Tools & Resources',
        'podcast' => 'Media Library',
        'webinar' => 'Media Library',
        'course' => 'Education Courses',
        'infographic' => 'Infographic Gallery'
    );

    // Auto-assign category for CPTs
    if ( array_key_exists( $post->post_type, $cpts ) ) {
        $cat_name = $cpts[$post->post_type];
        $term = term_exists( $cat_name, 'category' );
        
        if ( ! $term ) {
            $term = wp_insert_term( $cat_name, 'category' );
        }
        
        if ( ! is_wp_error( $term ) ) {
            $term_id = is_array( $term ) ? $term['term_id'] : $term;
            // Replace existing categories with the auto-assigned one for CPTs
            wp_set_post_categories( $post_id, array( $term_id ), false );
        }
    }
}
add_action( 'save_post', 'vance_auto_assign_category', 10, 3 );

/**
 * Add admin notice to guide users on post creation workflow
 */
function vance_content_creation_notice() {
    $screen = get_current_screen();
    
    // Show notice on post edit screens
    if ( $screen && ( $screen->post_type === 'post' || in_array( $screen->post_type, array( 'news', 'research', 'oped', 'review', 'whitepaper', 'podcast', 'webinar', 'course', 'infographic' ) ) ) ) {
        if ( $screen->post_type === 'post' ) {
            echo '<div class="notice notice-info is-dismissible">
                <p><strong>Content Creation Guide:</strong> When creating standard posts, make sure to select the appropriate category. This category will be used in the primary menu.</p>
            </div>';
        } else {
            $cpt_names = array(
                'news' => 'Healthcare News',
                'research' => 'Clinical Reviews',
                'oped' => 'Expert Opinions',
                'review' => 'Expert Opinions',
                'whitepaper' => 'Tools & Resources',
                'podcast' => 'Media Library',
                'webinar' => 'Media Library',
                'course' => 'Education Courses',
                'infographic' => 'Infographic Gallery'
            );
            $cpt_name = isset( $cpt_names[ $screen->post_type ] ) ? $cpt_names[ $screen->post_type ] : $screen->post_type;
            echo '<div class="notice notice-info is-dismissible">
                <p><strong>Content Hub Post:</strong> This post will automatically be assigned to the "' . esc_html( $cpt_name ) . '" category. The URL will match standard posts (no post type slug).</p>
            </div>';
        }
    }
}
add_action( 'admin_notices', 'vance_content_creation_notice' );

/**
 * Filter post type links to remove post type slug
 * This ensures CPTs have the same URL structure as standard posts
 */
function vance_remove_cpt_slug_from_permalink( $post_link, $post ) {
    $cpts = array( 'news', 'research', 'oped', 'review', 'whitepaper', 'podcast', 'webinar', 'course', 'infographic' );
    
    if ( in_array( $post->post_type, $cpts ) && 'publish' === $post->post_status ) {
        // Remove post type slug from URL
        $post_link = str_replace( '/' . $post->post_type . '/', '/', $post_link );
    }
    
    return $post_link;
}
add_filter( 'post_type_link', 'vance_remove_cpt_slug_from_permalink', 10, 2 );

/**
 * Parse request to handle CPTs without post type slug in URL
 * This allows CPTs to be accessed with the same URL structure as standard posts
 */
function vance_parse_cpt_request( $wp ) {
    // Only parse if it's not an admin request and we have a name query var
    if ( is_admin() || ! isset( $wp->query_vars['name'] ) ) {
        return;
    }
    
    // Don't parse if we already have a post_type set
    if ( isset( $wp->query_vars['post_type'] ) ) {
        return;
    }
    
    $cpts = array( 'news', 'research', 'oped', 'review', 'whitepaper', 'podcast', 'webinar', 'course', 'infographic' );
    $name = $wp->query_vars['name'];
    
    if ( ! empty( $name ) ) {
        // Try to find the post in any of our CPTs
        foreach ( $cpts as $cpt ) {
            $post = get_page_by_path( $name, OBJECT, $cpt );
            if ( $post ) {
                $wp->query_vars['post_type'] = $cpt;
                $wp->query_vars['name'] = $name;
                break;
            }
        }
    }
}
add_action( 'parse_request', 'vance_parse_cpt_request' );

















/**
 * Google OAuth Login Integration
 * 
 * To enable Google OAuth login:
 * 1. Go to https://console.cloud.google.com/
 * 2. Create a new project or select existing
 * 3. Enable Google+ API
 * 4. Go to Credentials > Create Credentials > OAuth Client ID
 * 5. Set Application type to "Web application"
 * 6. Add your site URL to Authorized JavaScript origins
 * 7. Add callback URL to Authorized redirect URIs: https://yoursite.com/wp-admin/admin-ajax.php
 * 8. Copy Client ID and add to wp-config.php: define('GOOGLE_CLIENT_ID', 'your-client-id');
 * 9. Copy Client Secret and add: define('GOOGLE_CLIENT_SECRET', 'your-client-secret');
 */

// Enqueue Google Identity Services
function vance_enqueue_google_oauth_scripts() {
    if ( ! is_user_logged_in() ) {
        wp_enqueue_script( 'google-gsi', 'https://accounts.google.com/gsi/client', array(), null, true );
    }
}
add_action( 'wp_enqueue_scripts', 'vance_enqueue_google_oauth_scripts' );

// Google OAuth Login Button Shortcode
function vance_google_login_button_shortcode( $atts ) {
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        return '<div class="vance-user-logged-in">
            <span>Welcome, ' . esc_html( $current_user->display_name ) . '</span>
            <a href="' . wp_logout_url( home_url() ) . '" class="btn btn-outline" style="margin-left: 12px;">Logout</a>
        </div>';
    }
    
    $client_id = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
    
    if ( empty( $client_id ) ) {
        return '<a href="' . wp_login_url() . '" class="btn btn-primary">Login / Register</a>';
    }
    
    $nonce = wp_create_nonce( 'google_oauth_nonce' );

    // Capture redirect_to from URL, same-origin-only — fallback to /dashboard/
    $raw_redirect = isset( $_GET['redirect_to'] ) ? wp_unslash( $_GET['redirect_to'] ) : home_url( '/dashboard/' );
    $redirect_to = wp_validate_redirect( $raw_redirect, home_url( '/dashboard/' ) );

    return '
    <div id="google-login-container">
        <div id="g_id_onload"
             data-client_id="' . esc_attr( $client_id ) . '"
             data-context="signin"
             data-ux_mode="popup"
             data-callback="handleGoogleCredentialResponse"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin"
             data-type="standard"
             data-shape="rectangular"
             data-theme="outline"
             data-text="signin_with"
             data-size="large"
             data-logo_alignment="left">
        </div>
    </div>
    <script>
    window.vanceLoginRedirect = ' . wp_json_encode( $redirect_to ) . ';
    window.vanceGoogleNonce = ' . wp_json_encode( $nonce ) . ';
    // This page can be served from LiteSpeed\'s full-page cache, which freezes
    // the nonce above at whatever moment the cache last regenerated. Refresh
    // it via admin-ajax.php (never page-cached) so it matches this visitor;
    // on failure the original value stays as a fallback.
    fetch("' . esc_url( admin_url( 'admin-ajax.php' ) ) . '?action=vance_refresh_auth_nonces", { credentials: "same-origin" })
        .then(function(r){ return r.json(); })
        .then(function(data){ if (data.success && data.data.google) { window.vanceGoogleNonce = data.data.google; } })
        .catch(function(){});
    function handleGoogleCredentialResponse(response) {
        fetch("' . admin_url('admin-ajax.php') . '", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "action=vance_google_oauth_callback&credential=" + response.credential +
                  "&nonce=" + encodeURIComponent(window.vanceGoogleNonce) +
                  "&redirect_to=" + encodeURIComponent(window.vanceLoginRedirect || "")
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                var target = (data.data && data.data.redirect_to) || window.vanceLoginRedirect || window.location.href;
                window.location.href = target;
            } else {
                alert("Login failed: " + (data.data || "Unknown error"));
            }
        });
    }
    </script>';
}
add_shortcode( 'google_login', 'vance_google_login_button_shortcode' );

// Handle Google OAuth Callback
function vance_google_oauth_callback() {
    // Check if POST data exists
    if ( ! isset( $_POST['nonce'] ) || ! isset( $_POST['credential'] ) ) {
        wp_send_json_error( 'Missing required data' );
    }

    if ( ! wp_verify_nonce( $_POST['nonce'], 'google_oauth_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }
    // 20 attempts per 5 min per IP (looser than email login — JWT validation is cheap and abuse vector is account creation)
    if ( ! function_exists( 'vance_rate_limit' ) || ! vance_rate_limit( 'google', 20, 300 ) ) {
        wp_send_json_error( 'Too many sign-in attempts. Please wait a few minutes and try again.' );
    }
    
    $credential = sanitize_text_field( $_POST['credential'] );

    // Verify the ID token with Google before trusting any of its claims —
    // a locally-decoded, unverified JWT payload can be forged by anyone
    // (the claims are just base64 JSON, no signature check without this).
    $verify_response = wp_remote_get( 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode( $credential ) );
    if ( is_wp_error( $verify_response ) || 200 !== wp_remote_retrieve_response_code( $verify_response ) ) {
        wp_send_json_error( 'Could not verify Google sign-in. Please try again.' );
    }

    $payload = json_decode( wp_remote_retrieve_body( $verify_response ), true );
    $client_id = defined( 'GOOGLE_CLIENT_ID' ) ? GOOGLE_CLIENT_ID : '';

    if ( ! $payload || ! isset( $payload['email'] )
        || empty( $payload['aud'] ) || $payload['aud'] !== $client_id
        || empty( $payload['iss'] ) || ! in_array( $payload['iss'], array( 'accounts.google.com', 'https://accounts.google.com' ), true )
        || empty( $payload['exp'] ) || (int) $payload['exp'] < time()
    ) {
        wp_send_json_error( 'Invalid or expired Google token.' );
    }

    $email = sanitize_email( $payload['email'] );
    $name = isset( $payload['name'] ) ? sanitize_text_field( $payload['name'] ) : '';
    $google_id = isset( $payload['sub'] ) ? sanitize_text_field( $payload['sub'] ) : '';
    
    // Check if user exists
    $user = get_user_by( 'email', $email );
    
    if ( ! $user ) {
        // Create new user
        $username = sanitize_user( strstr( $email, '@', true ) );
        $username = str_replace( '.', '_', $username );
        
        // Make sure username is unique
        $base_username = $username;
        $i = 1;
        while ( username_exists( $username ) ) {
            $username = $base_username . $i;
            $i++;
        }
        
        $user_id = wp_create_user( $username, wp_generate_password(), $email );
        
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( $user_id->get_error_message() );
        }
        
        // Update user meta
        $name_parts = explode(' ', $name);
        wp_update_user( array(
            'ID' => $user_id,
            'display_name' => $name,
            'first_name' => isset( $name_parts[0] ) ? $name_parts[0] : '',
            'last_name' => isset( $name_parts[1] ) ? $name_parts[1] : ''
        ) );
        
        update_user_meta( $user_id, 'google_id', $google_id );

        // Default to Member Role
        update_user_meta( $user_id, '_sla_user_type', 'member' );
        update_user_meta( $user_id, '_sla_dashboard_role', 'member' );

        // Google has already verified the email address — mark as verified.
        // Honour Google's email_verified flag if present (always true for OIDC-compliant providers).
        $email_verified = isset( $payload['email_verified'] ) ? (bool) $payload['email_verified'] : true;
        update_user_meta( $user_id, '_vance_email_verified', $email_verified ? 1 : 0 );

        // Google signup has no "I am a..." step, so there's no _sla_audience_role
        // signal yet — default to 'other' (routes into the general Member nurture,
        // never guessed into the HCP track) rather than leaving it unset.
        update_user_meta( $user_id, '_sla_audience_role', 'other' );
        if ( function_exists( 'vance_sync_fluentcrm_contact' ) ) {
            vance_sync_fluentcrm_contact( $user_id, $email, 'other', false, 'google' );
        }
        if ( function_exists( 'vance_generate_referral_code_for_user' ) ) {
            vance_generate_referral_code_for_user( $user_id );
            vance_credit_referral_signup( $user_id );
        }

        $user = get_user_by( 'id', $user_id );
    } else {
        // Existing user signing in via Google — opportunistically mark verified if not already.
        $current_verified = get_user_meta( $user->ID, '_vance_email_verified', true );
        if ( '' === $current_verified || '0' === (string) $current_verified ) {
            $email_verified = isset( $payload['email_verified'] ) ? (bool) $payload['email_verified'] : true;
            if ( $email_verified ) {
                update_user_meta( $user->ID, '_vance_email_verified', 1 );
            }
        }
    }

    // @slapharmagroup.com is internal staff. Google's server-verified token (checked
    // above via tokeninfo) proves ownership of the address, so every sign-in through
    // this callback re-asserts admin — idempotent, and covers accounts created before
    // this rule existed. Deliberately NOT applied to the email/password signup path:
    // a plaintext email field isn't proof of ownership, and doing it there would let
    // anyone self-elevate by typing an @slapharmagroup.com address they don't own.
    if ( preg_match( '/@slapharmagroup\.com$/i', $email ) && ! in_array( 'administrator', (array) $user->roles, true ) ) {
        $user->set_role( 'administrator' );
        update_user_meta( $user->ID, '_sla_user_type', 'administrator' );
        update_user_meta( $user->ID, '_sla_dashboard_role', 'administrator' );
    }

    // Log the user in
    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, true );

    // Resolve safe post-login redirect (same-origin only)
    $raw_redirect = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '';
    $redirect_to  = wp_validate_redirect( $raw_redirect, home_url( '/dashboard/' ) );

    wp_send_json_success( array(
        'message'     => 'Logged in successfully',
        'redirect_to' => $redirect_to,
    ) );
}
add_action( 'wp_ajax_nopriv_vance_google_oauth_callback', 'vance_google_oauth_callback' );
add_action( 'wp_ajax_vance_google_oauth_callback', 'vance_google_oauth_callback' );

/**
 * Fresh auth nonces, fetched via AJAX rather than trusted from page HTML.
 *
 * LiteSpeed Cache serves this theme's pages from a full-page cache, so a
 * nonce embedded directly in server-rendered markup is frozen at whatever
 * moment the cache last regenerated — every visitor since then gets served
 * that same nonce, which wp_verify_nonce() then rejects because it no longer
 * matches their own anonymous-session token. admin-ajax.php requests are
 * never page-cached, so nonces fetched here are always current. Public and
 * unauthenticated on purpose: a nonce is a per-action anti-CSRF token, not a
 * secret, and the forms it protects (Google/email sign-in, signup, password
 * reset) are themselves unauthenticated.
 */
function vance_refresh_auth_nonces() {
    wp_send_json_success( array(
        'google'       => wp_create_nonce( 'google_oauth_nonce' ),
        'login'        => wp_create_nonce( 'vance_login_nonce' ),
        'signup'       => wp_create_nonce( 'vance_quick_register' ),
        'lostpassword' => wp_create_nonce( 'vance_lostpassword_nonce' ),
        'contact'      => wp_create_nonce( 'vance_contact_form' ),
    ) );
}
add_action( 'wp_ajax_nopriv_vance_refresh_auth_nonces', 'vance_refresh_auth_nonces' );
add_action( 'wp_ajax_vance_refresh_auth_nonces', 'vance_refresh_auth_nonces' );

/**
 * Verify a reCAPTCHA v3 token against Google's siteverify endpoint.
 * Returns true (i.e. "skip protection") when no secret key is configured yet,
 * so the contact form (page-contact-us.php) keeps working before an admin
 * sets one up in the Customizer.
 */
function vance_contact_recaptcha_verify( $token ) {
    $secret = vance_get_theme_mod( 'vance_recaptcha_secret_key', '' );
    if ( '' === $secret ) {
        return true;
    }
    if ( '' === $token ) {
        return false;
    }
    $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
        'timeout' => 10,
        'body'    => array(
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
        ),
    ) );
    if ( is_wp_error( $response ) ) {
        return true; // Google unreachable — don't block real submitters over a network hiccup.
    }
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    // v3 returns a 0.0–1.0 bot-likelihood score; 0.5 is Google's own suggested cutoff.
    return ! empty( $body['success'] ) && ( ! isset( $body['score'] ) || $body['score'] >= 0.5 );
}

/**
 * Shared validation + send logic for the contact form's native-POST fallback
 * (page-contact-us.php) and its AJAX handler (vance_ajax_contact_submit,
 * directly below). Expects already-sanitized values.
 * Returns array('success' => bool, 'error' => string).
 */
function vance_contact_process_submission( $name, $email, $subject, $message, $token ) {
    if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
        return array( 'success' => false, 'error' => 'Please fill in all required fields.' );
    }
    if ( ! is_email( $email ) ) {
        return array( 'success' => false, 'error' => 'Please enter a valid email address.' );
    }
    if ( ! vance_contact_recaptcha_verify( $token ) ) {
        return array( 'success' => false, 'error' => 'We could not verify this submission as human. Please try again.' );
    }

    $to        = get_option( 'admin_email' );
    $site_name = get_bloginfo( 'name' );
    // Verified Resend sending domain — matches the WP Mail SMTP "From Email"
    // set to the same address, so this stays correct even if that plugin
    // setting is ever bypassed or the mailer changes.
    $from_addr     = 'team@vancemedicalfoods.co.uk';
    $email_subject = $subject ? "Contact: $subject" : "New Contact Form Submission – Vance Medical";
    $body          = "Name: $name\nEmail: $email\n\n$message";
    $headers       = array(
        "From: {$site_name} <{$from_addr}>",
        // Reply-To goes to the submitter here, not team@ — so a staffer
        // reading the alert can hit reply and land straight in the
        // customer's inbox instead of their own team inbox.
        "Reply-To: $name <$email>",
    );

    if ( ! wp_mail( $to, $email_subject, $body, $headers ) ) {
        return array( 'success' => false, 'error' => 'There was a problem sending your message. Please try again or email us directly.' );
    }

    // Best-effort confirmation to the submitter — failure here should never
    // block the "Message Sent!" state, the admin copy already went out.
    wp_mail(
        $email,
        "We've received your message – {$site_name}",
        "Hi {$name},\n\nThanks for contacting {$site_name}. A member of our team will get back to you within one business day.\n\nFor your records, here's what you sent us:\n\n{$message}",
        array(
            "From: {$site_name} <{$from_addr}>",
            "Reply-To: {$site_name} <{$from_addr}>",
        )
    );

    return array( 'success' => true, 'error' => '' );
}

/**
 * AJAX path for the contact form — the primary path when JS is available.
 * Added after the native form-POST path proved unreliable: this page can be
 * served from LiteSpeed's full-page cache (freezing the nonce), and in some
 * browser contexts form.requestSubmit() produced no network request at all,
 * with a full-page-navigation submission having no way to recover or even
 * report that. Matches the existing AJAX pattern already used by the
 * waitlist form (page-education.php) and the quick-signup modal
 * (inc/register-modal.php) on this same site.
 */
function vance_ajax_contact_submit() {
    check_ajax_referer( 'vance_contact_form', 'nonce' );

    $result = vance_contact_process_submission(
        sanitize_text_field( wp_unslash( $_POST['contact_name'] ?? '' ) ),
        sanitize_email( wp_unslash( $_POST['contact_email'] ?? '' ) ),
        sanitize_text_field( wp_unslash( $_POST['contact_subject'] ?? '' ) ),
        sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ?? '' ) ),
        sanitize_text_field( wp_unslash( $_POST['vance_recaptcha_token'] ?? '' ) )
    );

    if ( $result['success'] ) {
        wp_send_json_success();
    } else {
        wp_send_json_error( array( 'message' => $result['error'] ) );
    }
}
add_action( 'wp_ajax_nopriv_vance_contact_submit', 'vance_ajax_contact_submit' );
add_action( 'wp_ajax_vance_contact_submit', 'vance_ajax_contact_submit' );

/**
 * Redirect bare GET hits on wp-login.php to the themed /login/ page.
 *
 * Preserves the original ?redirect_to= target so "My Dashboard" links
 * still land users on /dashboard/ after Google sign-in.
 *
 * Exemptions (must continue using wp-login.php):
 *   - Logged-in users (let WP show its own "You are already logged in" notice)
 *   - POST submissions (form-based username/password login)
 *   - Any ?action= flow: logout, lostpassword, rp, resetpass, register, postpass, confirmaction
 *   - Already on /login/ (no infinite loop)
 *   - Site admins (debug-friendly: append ?wp_admin_login=1 to bypass)
 */
function vance_redirect_wp_login_to_themed_login() {
    if ( is_user_logged_in() ) {
        return;
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ( 'GET' !== $method && 'HEAD' !== $method ) {
        return; // allow form POSTs to wp-login.php for native username/password fallback
    }
    // "Forgot password?" (the initial request-a-reset-link step) gets the
    // themed treatment too — see the `view=lostpassword` branch in
    // vance_auth_modal_shortcode(). Every other action (rp/resetpass — the
    // set-a-new-password step after clicking the emailed link, register,
    // logout, postpass, confirmaction) is left on WP's own screens; building
    // a fully custom flow for those wasn't asked for and is a lot more
    // surface area for a single "unbranded page" complaint.
    $is_lostpassword = isset( $_GET['action'] ) && 'lostpassword' === $_GET['action'];
    if ( ! empty( $_GET['action'] ) && ! $is_lostpassword ) {
        return;
    }
    if ( isset( $_GET['wp_admin_login'] ) ) {
        return; // escape hatch for admin
    }

    $raw_redirect = isset( $_GET['redirect_to'] ) ? wp_unslash( $_GET['redirect_to'] ) : home_url( '/dashboard/' );
    $redirect_to  = wp_validate_redirect( $raw_redirect, home_url( '/dashboard/' ) );

    $target = add_query_arg( 'redirect_to', urlencode( $redirect_to ), home_url( '/login/' ) );
    if ( $is_lostpassword ) {
        $target = add_query_arg( 'view', 'lostpassword', $target );
    }
    wp_safe_redirect( $target );
    exit;
}
add_action( 'login_init', 'vance_redirect_wp_login_to_themed_login' );

/**
 * Modal-style auth UI — Google + Email login + Email signup.
 *
 * Renders a self-contained modal (overlay + card + tabs) with three flows:
 *   1. Google Sign-In via the existing GSI client + vance_google_oauth_callback AJAX
 *   2. Email + Password login via vance_email_login AJAX (uses wp_authenticate())
 *   3. Email + Password signup via vance_email_signup AJAX (uses wp_create_user())
 *
 * All three honour the same ?redirect_to= query param (same-origin validated).
 * Uses :has() CSS to hide site chrome only when the overlay is present.
 */
function vance_auth_modal_shortcode( $atts ) {
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        return '<div style="max-width:460px;margin:40px auto;padding:48px 40px;background:#fff;border:1px solid #e2e8f0;border-radius:var(--radius-surface, 14px);box-shadow:0 10px 40px rgba(10,25,41,.08);text-align:center;font-family:var(--font-main, \'Inter\', sans-serif);">
            <span style="display:flex;align-items:center;justify-content:center;width:72px;height:72px;margin:0 auto 24px;border-radius:50%;background:rgba(0,128,128,.10);color:var(--primary-color,#008080);">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            </span>
            <h1 style="margin:0 0 8px;font-family:var(--font-heading, \'Outfit\', sans-serif);font-size:26px;font-weight:700;color:var(--secondary-color,#0A1929);line-height:1.25;">Welcome back, ' . esc_html( $current_user->display_name ) . '</h1>
            <p style="margin:0 0 32px;color:var(--text-light,#6B7280);font-size:15px;line-height:1.6;">You\'re signed in and ready to go.</p>
            <a href="' . esc_url( home_url( '/dashboard/' ) ) . '" class="btn btn-primary" style="width:100%;box-sizing:border-box;gap:10px;">Go to dashboard
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
            <p style="margin:7px 0 0;">
                <a href="' . esc_url( wp_logout_url( home_url() ) ) . '" style="display:inline-block;padding:13px 10px;margin:-13px -10px;font-size:14px;font-weight:500;color:var(--text-light,#6B7280);text-decoration:none;border-bottom:1px solid transparent;transition:color .15s,border-color .15s;"
                   onmouseover="this.style.color=\'var(--primary-color,#008080)\';this.style.borderColor=\'currentColor\';"
                   onmouseout="this.style.color=\'var(--text-light,#6B7280)\';this.style.borderColor=\'transparent\';">Not you? Logout</a>
            </p>
        </div>';
    }

    $client_id = defined( 'GOOGLE_CLIENT_ID' ) ? GOOGLE_CLIENT_ID : '';

    // "Forgot password?" branded view — see vance_redirect_wp_login_to_themed_login()
    // (functions.php) for the ?action=lostpassword -> ?view=lostpassword redirect.
    $is_lostpassword = isset( $_GET['view'] ) && 'lostpassword' === $_GET['view'];

    // Cross-page "Join Now" / "Register" CTAs land here with ?tab=signup so the
    // Sign Up panel opens directly instead of Sign In (the default).
    $initial_tab = ( isset( $_GET['tab'] ) && 'signup' === $_GET['tab'] ) ? 'signup' : 'signin';

    $raw_redirect = isset( $_GET['redirect_to'] ) ? wp_unslash( $_GET['redirect_to'] ) : home_url( '/dashboard/' );
    $redirect_to  = wp_validate_redirect( $raw_redirect, home_url( '/dashboard/' ) );

    $nonces = array(
        'google'        => wp_create_nonce( 'google_oauth_nonce' ),
        'login'         => wp_create_nonce( 'vance_login_nonce' ),
        // Sign-up tab posts to the same vance_quick_register handler as the
        // tool-page / VANCE-Ai register modal (constraint #5: paired names).
        'signup'        => wp_create_nonce( 'vance_quick_register' ),
        'lostpassword'  => wp_create_nonce( 'vance_lostpassword_nonce' ),
    );

    $ajax_url    = admin_url( 'admin-ajax.php' );

    $cfg = wp_json_encode( array(
        'ajaxUrl'    => $ajax_url,
        'redirectTo' => $redirect_to,
        'nonces'     => $nonces,
        'clientId'   => $client_id,
    ) );

    ob_start();
    ?>
    <style>
    .vance-auth-overlay{position:fixed;inset:0;background:rgba(15,30,30,0.55);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;z-index:99999;padding:16px;animation:vanceFadeIn .2s ease-out}
    @keyframes vanceFadeIn{from{opacity:0}to{opacity:1}}
    @keyframes vancePopIn{from{transform:scale(.96);opacity:0}to{transform:scale(1);opacity:1}}
    .vance-auth-modal{background:#fff;border-radius:var(--radius-surface, 14px);padding:26px 28px 22px;max-width:420px;width:100%;box-shadow:0 24px 72px rgba(0,0,0,0.3);animation:vancePopIn .25s ease-out;box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;position:relative;max-height:calc(100dvh - 24px);overflow-y:auto}
    .vance-auth-close{position:absolute;top:10px;right:12px;background:transparent;border:none;font-size:26px;color:#94a3b8;cursor:pointer;line-height:1;padding:4px 8px;z-index:2}
    .vance-auth-close:hover{color:#1a1a1a}
    .vance-auth-header{text-align:center;margin-bottom:14px}
    .vance-auth-header h2{margin:0 0 4px;color:#1a1a1a;font-size:22px;font-weight:700}
    .vance-auth-header p{margin:0;color:#666;font-size:13px}
    .vance-auth-google{display:flex;justify-content:center;margin-bottom:10px;min-height:44px}
    .vance-auth-divider{text-align:center;margin:10px 0;color:#999;position:relative;font-size:12px;text-transform:uppercase;letter-spacing:1px}
    .vance-auth-divider span{background:#fff;padding:0 12px;position:relative;z-index:1}
    .vance-auth-divider::before{content:'';position:absolute;top:50%;left:0;right:0;height:1px;background:#2f4f6f}
    .vance-auth-tabs{display:flex;gap:4px;margin-bottom:14px;background:#f1f5f5;padding:4px;border-radius:var(--radius-surface, 14px)}
    .vance-auth-tab{flex:1;padding:10px 16px;border:none;background:transparent;cursor:pointer;border-radius:var(--radius-control, 6px);font-weight:600;color:#666;transition:all .15s;font-size:14px}
    .vance-auth-tab.active{background:#fff;color:#008080;box-shadow:0 2px 6px rgba(0,0,0,0.06)}
    .vance-auth-error{background:#fee;color:#a00;padding:10px 14px;border-radius:var(--radius-surface, 14px);font-size:13px;margin-bottom:14px;display:none;border:1px solid #fcc}
    .vance-auth-error.active{display:block}
    .vance-auth-form{display:none}
    .vance-auth-form.active{display:block}
    .vance-auth-field{margin-bottom:10px}
    .vance-auth-field label{display:block;font-size:13px;font-weight:600;color:#444;margin-bottom:4px}
    .vance-auth-field input{width:100%;padding:9px 14px;border:1.5px solid #e0e6e6;border-radius:var(--radius-field, 10px);font-size:15px;box-sizing:border-box;transition:border-color .15s;font-family:inherit}
    .vance-auth-field input:focus{outline:none;border-color:#008080;box-shadow:0 0 0 3px rgba(0,128,128,0.1)}
    .vance-auth-field select{width:100%;padding:9px 14px;border:1.5px solid #e0e6e6;border-radius:var(--radius-field, 10px);font-size:15px;box-sizing:border-box;transition:border-color .15s;font-family:inherit;background:#fff}
    .vance-auth-field select:focus{outline:none;border-color:#008080;box-shadow:0 0 0 3px rgba(0,128,128,0.1)}
    .vance-auth-consent{display:flex;gap:8px;align-items:flex-start;font-size:12px;color:#666;line-height:1.5;cursor:pointer;margin:0 0 8px;font-weight:400}
    .vance-auth-consent input{width:auto;margin-top:2px}
    .vance-auth-consent a{color:#008080}
    .vance-auth-forgot{text-align:right;margin:-6px 0 14px}
    .vance-auth-forgot a{color:#008080;text-decoration:none;font-size:13px}
    .vance-auth-forgot a:hover{text-decoration:underline}
    .vance-auth-submit{width:100%;padding:13px;background:#008080;color:#fff;border:none;border-radius:var(--radius-control, 6px);font-size:15px;font-weight:600;cursor:pointer;transition:background .15s;font-family:inherit}
    .vance-auth-submit:hover:not(:disabled){background:#006666}
    .vance-auth-submit:disabled{background:#aaa;cursor:not-allowed}
    .vance-auth-footer{text-align:center;margin-top:12px;font-size:12px;color:#888}
    .vance-auth-pw-wrap{position:relative}
    .vance-auth-pw-toggle{position:absolute;top:50%;right:12px;transform:translateY(-50%);background:transparent;border:none;cursor:pointer;padding:4px;color:#94a3b8;line-height:1}
    .vance-auth-pw-toggle:hover{color:#334155}
    @media (max-width:480px){.vance-auth-modal{padding:28px 22px}}
    /* Hide site chrome only when the modal is rendered on the page */
    body:has(.vance-auth-overlay) .site-header,
    body:has(.vance-auth-overlay) .site-footer,
    body:has(.vance-auth-overlay) header.header,
    body:has(.vance-auth-overlay) footer.footer,
    body:has(.vance-auth-overlay) .main-header,
    body:has(.vance-auth-overlay) .main-footer,
    body:has(.vance-auth-overlay) .entry-header,
    body:has(.vance-auth-overlay) .page-header{display:none !important}
    body:has(.vance-auth-overlay){background:#f7fafa !important;overflow:hidden}
    </style>

    <div class="vance-auth-overlay" id="vance-auth-overlay" role="dialog" aria-modal="true" aria-labelledby="vance-auth-title">
        <div class="vance-auth-modal">
            <button type="button" class="vance-auth-close" id="vance-auth-close" aria-label="Close">&times;</button>
            <div class="vance-auth-header">
                <?php if ( $is_lostpassword ) : ?>
                <h2 id="vance-auth-title">Reset your password</h2>
                <p>Enter your email and we'll send you a reset link</p>
                <?php else : ?>
                <h2 id="vance-auth-title">Welcome</h2>
                <p>Sign in or create your account to continue</p>
                <?php endif; ?>
            </div>

            <div class="vance-auth-error" id="vance-auth-error" role="alert"></div>

            <?php if ( $is_lostpassword ) : ?>
            <form class="vance-auth-form active" id="vance-lostpassword" novalidate>
                <div class="vance-auth-field">
                    <label for="vance-lostpassword-email">Email</label>
                    <input id="vance-lostpassword-email" type="email" name="email" required autocomplete="email">
                </div>
                <button type="submit" class="vance-auth-submit" data-label="Send reset link">Send reset link</button>
            </form>
            <div class="vance-auth-footer">
                <a href="<?php echo esc_url( add_query_arg( 'redirect_to', urlencode( $redirect_to ), home_url( '/login/' ) ) ); ?>" style="color:#008080">Back to sign in</a>
            </div>
            <?php else : ?>

            <?php if ( $client_id ) : ?>
            <div class="vance-auth-google">
                <div id="g_id_onload"
                    data-client_id="<?php echo esc_attr( $client_id ); ?>"
                    data-context="signin" data-ux_mode="popup"
                    data-callback="handleGoogleCredentialResponse"
                    data-auto_prompt="false"></div>
                <div class="g_id_signin" data-type="standard" data-shape="rectangular"
                    data-theme="outline" data-text="continue_with" data-size="large"
                    data-logo_alignment="left"></div>
            </div>
            <div class="vance-auth-divider"><span>or</span></div>
            <?php endif; ?>

            <div class="vance-auth-tabs" role="tablist">
                <button class="vance-auth-tab<?php echo 'signin' === $initial_tab ? ' active' : ''; ?>" type="button" data-target="vance-signin" role="tab">Sign in</button>
                <button class="vance-auth-tab<?php echo 'signup' === $initial_tab ? ' active' : ''; ?>" type="button" data-target="vance-signup" role="tab">Sign up</button>
            </div>

            <form class="vance-auth-form<?php echo 'signin' === $initial_tab ? ' active' : ''; ?>" id="vance-signin" novalidate>
                <div class="vance-auth-field">
                    <label for="vance-signin-email">Email</label>
                    <input id="vance-signin-email" type="email" name="email" required autocomplete="email">
                </div>
                <div class="vance-auth-field">
                    <label for="vance-signin-password">Password</label>
                    <div class="vance-auth-pw-wrap">
                        <input id="vance-signin-password" type="password" name="password" required autocomplete="current-password" style="padding-right:40px;">
                        <button type="button" class="vance-auth-pw-toggle" data-toggle-for="vance-signin-password" aria-label="Show password">👁</button>
                    </div>
                </div>
                <div class="vance-auth-forgot"><a href="<?php echo esc_url( add_query_arg( array( 'view' => 'lostpassword', 'redirect_to' => urlencode( $redirect_to ) ), home_url( '/login/' ) ) ); ?>">Forgot password?</a></div>
                <button type="submit" class="vance-auth-submit" data-label="Sign in">Sign in</button>
            </form>

            <form class="vance-auth-form<?php echo 'signup' === $initial_tab ? ' active' : ''; ?>" id="vance-signup" novalidate>
                <div class="vance-auth-field">
                    <label for="vance-signup-email">Email</label>
                    <input id="vance-signup-email" type="email" name="email" required autocomplete="email" inputmode="email" placeholder="you@example.com">
                </div>
                <div class="vance-auth-field">
                    <label for="vance-signup-password">Password (min 8 characters)</label>
                    <div class="vance-auth-pw-wrap">
                        <input id="vance-signup-password" type="password" name="password" minlength="8" required autocomplete="new-password" placeholder="••••••••" style="padding-right:40px;">
                        <button type="button" class="vance-auth-pw-toggle" data-toggle-for="vance-signup-password" aria-label="Show password">👁</button>
                    </div>
                </div>
                <div class="vance-auth-field">
                    <label for="vance-signup-role">I am a…</label>
                    <select id="vance-signup-role" name="role">
                        <option value="patient">Patient</option>
                        <option value="caregiver">Caregiver / family</option>
                        <option value="hcp">Healthcare professional</option>
                        <option value="researcher">Researcher</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <!-- Honeypot — bots fill anything visible; real users don't fill display:none fields -->
                <div style="position:absolute;left:-5000px;" aria-hidden="true">
                    <input type="text" name="vance_hp" tabindex="-1" value="">
                </div>
                <label class="vance-auth-consent">
                    <input type="checkbox" name="consent_terms" value="1" required>
                    <span>I agree to the <a href="<?php echo esc_url( home_url( '/terms-of-use/' ) ); ?>" target="_blank">Terms</a> and <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" target="_blank">Privacy Policy</a>, and to any results or health information I save being stored so I can see them in my dashboard.</span>
                </label>
                <label class="vance-auth-consent">
                    <input type="checkbox" name="consent_marketing" value="1">
                    <span>Email me occasional updates about new tools and resources. Optional, unsubscribe anytime.</span>
                </label>
                <button type="submit" class="vance-auth-submit" data-label="Create account">Create account</button>
            </form>

            <div class="vance-auth-footer">
                By continuing you agree to our <a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>" style="color:#008080">Terms</a> &amp; <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>" style="color:#008080">Privacy</a>.
            </div>
            <?php endif; // $is_lostpassword ?>
        </div>
    </div>

    <script>
    (function(){
        var CFG = <?php echo $cfg; // already JSON-encoded by wp_json_encode ?>;

        // This page can be served from LiteSpeed's full-page cache, which
        // freezes CFG.nonces at whatever moment the cache last regenerated —
        // every visitor since then gets the same nonce, which wp_verify_nonce()
        // then rejects. Refresh via admin-ajax.php (never page-cached) so the
        // nonces match this visitor; on failure the original values remain as
        // a fallback rather than blocking the form.
        fetch(CFG.ajaxUrl + '?action=vance_refresh_auth_nonces', { credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(data){ if (data.success) { CFG.nonces = data.data; } })
            .catch(function(){});

        var errEl = document.getElementById('vance-auth-error');

        function showError(msg){
            errEl.textContent = msg;
            errEl.classList.add('active');
        }
        function clearError(){ errEl.classList.remove('active'); }

        function lockButton(btn, lockText){
            btn.dataset.label = btn.dataset.label || btn.textContent;
            btn.disabled = true;
            btn.textContent = lockText;
        }
        function unlockButton(btn){
            btn.disabled = false;
            btn.textContent = btn.dataset.label;
        }

        function postForm(action, nonce, fields){
            var body = new URLSearchParams();
            body.set('action', action);
            body.set('nonce', nonce);
            body.set('redirect_to', CFG.redirectTo);
            Object.keys(fields).forEach(function(k){ body.set(k, fields[k]); });
            return fetch(CFG.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: body.toString()
            }).then(function(r){ return r.json(); });
        }

        // Close — the modal IS the /login/ page, so leave it: back to the
        // same-origin page the user came from, else the homepage.
        function closeAuthModal(){
            var ref = document.referrer;
            var sameOrigin = false;
            try { sameOrigin = ref && new URL(ref).origin === window.location.origin && ref !== window.location.href; } catch (err) {}
            if (sameOrigin) { window.history.back(); }
            else { window.location.href = '<?php echo esc_js( home_url( '/' ) ); ?>'; }
        }
        document.getElementById('vance-auth-close').addEventListener('click', closeAuthModal);
        document.addEventListener('keydown', function(e){
            if ('Escape' === e.key) { closeAuthModal(); }
        });

        // Password show/hide toggles
        document.querySelectorAll('.vance-auth-pw-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.getAttribute('data-toggle-for'));
                var showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                btn.textContent = showing ? '👁' : '🙈';
                btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            });
        });

        // Tabs
        document.querySelectorAll('.vance-auth-tab').forEach(function(tab){
            tab.addEventListener('click', function(){
                document.querySelectorAll('.vance-auth-tab').forEach(function(t){ t.classList.remove('active'); });
                document.querySelectorAll('.vance-auth-form').forEach(function(f){ f.classList.remove('active'); });
                this.classList.add('active');
                document.getElementById(this.dataset.target).classList.add('active');
                clearError();
            });
        });

        // Reset-password request (only present on the ?view=lostpassword screen)
        var lostPwForm = document.getElementById('vance-lostpassword');
        if (lostPwForm) {
            lostPwForm.addEventListener('submit', function(e){
                e.preventDefault();
                clearError();
                var btn = this.querySelector('.vance-auth-submit');
                lockButton(btn, 'Sending…');
                postForm('vance_lostpassword', CFG.nonces.lostpassword, {
                    email: this.email.value.trim()
                }).then(function(data){
                    unlockButton(btn);
                    if (data.success) {
                        lostPwForm.innerHTML = '<p style="margin:0;color:#444;font-size:14px;line-height:1.6;">Check your email — if an account matches that address, a reset link is on its way.</p>';
                    } else {
                        showError((data.data && (data.data.message || data.data)) || 'Could not send reset link');
                    }
                }).catch(function(){ showError('Network error, try again'); unlockButton(btn); });
            });
        }

        // Email login
        var signinForm = document.getElementById('vance-signin');
        if (signinForm) {
        signinForm.addEventListener('submit', function(e){
            e.preventDefault();
            clearError();
            var btn = this.querySelector('.vance-auth-submit');
            lockButton(btn, 'Signing in…');
            postForm('vance_email_login', CFG.nonces.login, {
                email: this.email.value.trim(),
                password: this.password.value
            }).then(function(data){
                if (data.success) {
                    window.location.href = (data.data && data.data.redirect_to) || CFG.redirectTo;
                } else {
                    showError((data.data && (data.data.message || data.data)) || 'Sign in failed');
                    unlockButton(btn);
                }
            }).catch(function(){ showError('Network error, try again'); unlockButton(btn); });
        });
        }

        // Email signup — same form + vance_quick_register handler as the
        // tool-page / VANCE-Ai register modal.
        var signupForm = document.getElementById('vance-signup');
        if (signupForm) {
        signupForm.addEventListener('submit', function(e){
            e.preventDefault();
            clearError();
            var termsEl = this.querySelector('input[name="consent_terms"]');
            if (termsEl && !termsEl.checked) {
                showError('Please agree to the Terms and Privacy Policy to continue.');
                termsEl.focus();
                return;
            }
            var btn = this.querySelector('.vance-auth-submit');
            lockButton(btn, 'Creating account…');
            postForm('vance_quick_register', CFG.nonces.signup, {
                email: this.email.value.trim(),
                password: this.password.value,
                role: this.role.value,
                consent_terms: termsEl && termsEl.checked ? '1' : '',
                consent_marketing: this.consent_marketing.checked ? '1' : '',
                vance_hp: this.vance_hp.value,
                source: 'login_page',
                redirect: CFG.redirectTo
            }).then(function(data){
                if (data.success) {
                    window.location.href = (data.data && (data.data.redirect || data.data.redirect_to)) || CFG.redirectTo;
                } else {
                    showError((data.data && (data.data.message || data.data)) || 'Signup failed');
                    unlockButton(btn);
                }
            }).catch(function(){ showError('Network error, try again'); unlockButton(btn); });
        });
        }

        // Google
        window.vanceLoginRedirect = CFG.redirectTo;
        window.handleGoogleCredentialResponse = function(response){
            clearError();
            var body = new URLSearchParams();
            body.set('action', 'vance_google_oauth_callback');
            body.set('credential', response.credential);
            body.set('nonce', CFG.nonces.google);
            body.set('redirect_to', CFG.redirectTo);
            fetch(CFG.ajaxUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: body.toString()
            }).then(function(r){ return r.json(); }).then(function(data){
                if (data.success) {
                    window.location.href = (data.data && data.data.redirect_to) || CFG.redirectTo;
                } else {
                    showError(typeof data.data === 'string' ? data.data : 'Google sign-in failed');
                }
            }).catch(function(){ showError('Network error, try again'); });
        };
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'vance_auth_modal', 'vance_auth_modal_shortcode' );

/**
 * AJAX: email + password login.
 */
function vance_email_login_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_login_nonce' ) ) {
        wp_send_json_error( 'Invalid request, please refresh and try again.' );
    }
    // 10 attempts per 5 min per IP
    if ( ! vance_rate_limit( 'login', 10, 300 ) ) {
        wp_send_json_error( 'Too many login attempts. Please wait 5 minutes and try again.' );
    }

    $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

    if ( ! is_email( $email ) || '' === $password ) {
        wp_send_json_error( 'Please enter a valid email and password.' );
    }

    // Try email-as-login first; fall back to username if user record has different login
    $user = wp_authenticate( $email, $password );
    if ( is_wp_error( $user ) ) {
        $user_by_email = get_user_by( 'email', $email );
        if ( $user_by_email ) {
            $user = wp_authenticate( $user_by_email->user_login, $password );
        }
    }
    if ( is_wp_error( $user ) ) {
        wp_send_json_error( 'Incorrect email or password.' );
    }

    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, true );

    $raw_redirect = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '';
    $redirect_to  = wp_validate_redirect( $raw_redirect, home_url( '/dashboard/' ) );

    wp_send_json_success( array( 'redirect_to' => $redirect_to ) );
}
add_action( 'wp_ajax_nopriv_vance_email_login', 'vance_email_login_ajax' );
add_action( 'wp_ajax_vance_email_login', 'vance_email_login_ajax' );

/**
 * AJAX: request a password reset link — powers the branded
 * /login/?view=lostpassword screen (see vance_auth_modal_shortcode()).
 * Delegates to WP core's retrieve_password() so the email itself, the reset
 * key, and the eventual wp-login.php?action=rp flow are all standard WP.
 */
function vance_lostpassword_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_lostpassword_nonce' ) ) {
        wp_send_json_error( 'Invalid request, please refresh and try again.' );
    }
    if ( ! vance_rate_limit( 'lostpassword', 5, 300 ) ) {
        wp_send_json_error( 'Too many attempts. Please wait a few minutes and try again.' );
    }

    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    if ( ! is_email( $email ) ) {
        wp_send_json_error( 'Please enter a valid email address.' );
    }

    // Always report success regardless of whether the address matches an
    // account — the alternative (a "no such user" error) leaks which emails
    // are registered, which retrieve_password()'s own errors would do.
    retrieve_password( $email );
    wp_send_json_success();
}
add_action( 'wp_ajax_nopriv_vance_lostpassword', 'vance_lostpassword_ajax' );
add_action( 'wp_ajax_vance_lostpassword', 'vance_lostpassword_ajax' );

/**
 * AJAX: email + password signup.
 *
 * Defaults new users to the "member" role keys used by the Google flow so
 * existing dashboard logic continues to work identically.
 */
function vance_email_signup_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_signup_nonce' ) ) {
        wp_send_json_error( 'Invalid request, please refresh and try again.' );
    }
    // 5 signup attempts per 15 min per IP (prevents mass account creation)
    if ( ! vance_rate_limit( 'signup', 5, 900 ) ) {
        wp_send_json_error( 'Too many signup attempts. Please wait 15 minutes and try again.' );
    }

    $name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
    $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

    if ( '' === $name ) {
        wp_send_json_error( 'Please enter your name.' );
    }
    if ( ! is_email( $email ) ) {
        wp_send_json_error( 'Please enter a valid email address.' );
    }
    if ( strlen( $password ) < 8 ) {
        wp_send_json_error( 'Password must be at least 8 characters.' );
    }
    if ( email_exists( $email ) ) {
        wp_send_json_error( 'An account with this email already exists. Try signing in.' );
    }

    // Username from email local-part, deduplicated
    $username      = sanitize_user( str_replace( '.', '_', strstr( $email, '@', true ) ) );
    $base_username = $username;
    $i             = 1;
    while ( username_exists( $username ) ) {
        $username = $base_username . $i;
        $i++;
    }

    $user_id = wp_create_user( $username, $password, $email );
    if ( is_wp_error( $user_id ) ) {
        wp_send_json_error( $user_id->get_error_message() );
    }

    $name_parts = explode( ' ', $name, 2 );
    wp_update_user( array(
        'ID'           => $user_id,
        'display_name' => $name,
        'first_name'   => isset( $name_parts[0] ) ? $name_parts[0] : '',
        'last_name'    => isset( $name_parts[1] ) ? $name_parts[1] : '',
    ) );

    // Match the Google flow's role assignment for dashboard compatibility
    update_user_meta( $user_id, '_sla_user_type', 'member' );
    update_user_meta( $user_id, '_sla_dashboard_role', 'member' );

    // Mark email as unverified and dispatch verification email
    update_user_meta( $user_id, '_vance_email_verified', 0 );
    vance_send_verification_email( $user_id );

    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );

    // Send the new user to /verify-email/ regardless of requested redirect_to
    // (they hit the dashboard gate otherwise). The gate handles re-redirects on click.
    wp_send_json_success( array(
        'redirect_to'        => home_url( '/verify-email/' ),
        'requires_verification' => true,
    ) );
}
add_action( 'wp_ajax_nopriv_vance_email_signup', 'vance_email_signup_ajax' );
add_action( 'wp_ajax_vance_email_signup', 'vance_email_signup_ajax' );

/**
 * Education/Webinars page waitlist signup (self-hosted fallback used when no
 * third-party ESP action URL is configured in the Customizer — see
 * page-education.php). Stores signups in a single wp_option and emails the
 * admin per signup, per project decision (no CRM/ESP wired up yet).
 */
function vance_edu_waitlist_signup_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_edu_waitlist' ) ) {
        wp_send_json_error( array( 'message' => 'Invalid request, please refresh and try again.' ) );
    }
    if ( ! empty( $_POST['edu_waitlist_hp'] ) ) {
        wp_send_json_success(); // Honeypot tripped — pretend success, drop silently.
    }
    if ( ! vance_rate_limit( 'edu_waitlist', 5, 300 ) ) {
        wp_send_json_error( array( 'message' => 'Too many attempts. Please wait a few minutes and try again.' ) );
    }

    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $role  = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => 'Please enter a valid email address.' ) );
    }

    $signups = get_option( 'vance_edu_waitlist_signups', array() );
    if ( ! is_array( $signups ) ) {
        $signups = array();
    }
    foreach ( $signups as $existing ) {
        if ( isset( $existing['email'] ) && strtolower( $existing['email'] ) === strtolower( $email ) ) {
            wp_send_json_success(); // Already on the list — no error, no duplicate entry.
        }
    }
    $signups[] = array(
        'email' => $email,
        'role'  => $role,
        'ts'    => current_time( 'mysql' ),
    );
    update_option( 'vance_edu_waitlist_signups', $signups, false );

    wp_mail(
        get_option( 'admin_email' ),
        'New Education/Webinars waitlist signup – Vance Medical',
        "New waitlist signup:\n\nEmail: {$email}\nRole: " . ( $role ?: '(not specified)' )
    );

    wp_send_json_success();
}
add_action( 'wp_ajax_nopriv_vance_edu_waitlist_signup', 'vance_edu_waitlist_signup_ajax' );
add_action( 'wp_ajax_vance_edu_waitlist_signup', 'vance_edu_waitlist_signup_ajax' );

/* =====================================================================
 * Rate limiting helpers (transient-backed, IP-bucketed).
 * ===================================================================== */

/**
 * Resolve client IP honouring common proxy headers.
 * Falls back to REMOTE_ADDR, then to 0.0.0.0.
 */
function vance_get_client_ip() {
    foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' ) as $h ) {
        if ( ! empty( $_SERVER[ $h ] ) ) {
            $candidate = trim( explode( ',', $_SERVER[ $h ] )[0] );
            if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
                return $candidate;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Increment-and-check counter per (action_key, IP).
 * Returns true if request is within the allowance, false if it should be blocked.
 */
function vance_rate_limit( $action_key, $max_attempts = 10, $window_seconds = 300 ) {
    $ip         = vance_get_client_ip();
    $bucket_key = 'vance_rl_' . md5( $action_key . '|' . $ip );
    $hits       = (int) get_transient( $bucket_key );
    if ( $hits >= $max_attempts ) {
        return false;
    }
    set_transient( $bucket_key, $hits + 1, $window_seconds );
    return true;
}

/* =====================================================================
 * Email verification — token issue, send, verify, gate, resend.
 *
 * User meta:
 *   _vance_email_verified         — '1' verified, '0' unverified, missing = legacy-verified
 *   _vance_email_verify_token     — bcrypt hash of one-time verification token
 *   _vance_email_verify_sent      — unix ts of last verification email sent
 * ===================================================================== */

/**
 * Generate a one-time token, store its hash, and email a verification link.
 */
function vance_send_verification_email( $user_id ) {
    $user = get_user_by( 'id', $user_id );
    if ( ! $user ) {
        return false;
    }

    $token = wp_generate_password( 32, false, false );
    update_user_meta( $user_id, '_vance_email_verify_token', wp_hash_password( $token ) );
    update_user_meta( $user_id, '_vance_email_verify_sent', time() );

    $verify_url = add_query_arg(
        array(
            'verify_token' => $token,
            'verify_uid'   => $user_id,
        ),
        home_url( '/verify-email/' )
    );

    $site_name = get_bloginfo( 'name' );
    $subject   = sprintf( 'Verify your %s account', $site_name );
    $message   = sprintf(
        "Hi %s,\n\n" .
        "Welcome to %s! Please confirm your email by clicking the link below:\n\n" .
        "%s\n\n" .
        "This link will keep working until you successfully verify.\n" .
        "If you did not create this account, you can safely ignore this email.\n\n" .
        "%s",
        $user->display_name ? $user->display_name : $user->user_login,
        $site_name,
        $verify_url,
        $site_name
    );

    $headers = array( 'Content-Type: text/plain; charset=UTF-8' );

    return wp_mail( $user->user_email, $subject, $message, $headers );
}

/**
 * Process ?verify_token=X&verify_uid=N on any frontend request.
 * Runs early on template_redirect so we can short-circuit before page render.
 */
function vance_verify_email_handler() {
    if ( ! isset( $_GET['verify_token'], $_GET['verify_uid'] ) ) {
        return;
    }

    $uid         = absint( $_GET['verify_uid'] );
    $token       = sanitize_text_field( wp_unslash( $_GET['verify_token'] ) );
    $stored_hash = $uid ? get_user_meta( $uid, '_vance_email_verify_token', true ) : '';

    if ( ! $uid || ! $stored_hash || ! wp_check_password( $token, $stored_hash ) ) {
        wp_safe_redirect( add_query_arg( 'verify_error', '1', home_url( '/verify-email/' ) ) );
        exit;
    }

    update_user_meta( $uid, '_vance_email_verified', 1 );
    delete_user_meta( $uid, '_vance_email_verify_token' );

    // If the user is already logged-in as this account, send them to the dashboard.
    // Otherwise, send them to the login modal with redirect_to=dashboard.
    if ( is_user_logged_in() && get_current_user_id() === $uid ) {
        wp_safe_redirect( add_query_arg( 'verified', '1', home_url( '/dashboard/' ) ) );
    } else {
        wp_safe_redirect( add_query_arg(
            array(
                'verified'    => '1',
                'redirect_to' => urlencode( home_url( '/dashboard/' ) ),
            ),
            home_url( '/login/' )
        ) );
    }
    exit;
}
add_action( 'template_redirect', 'vance_verify_email_handler', 5 );

/**
 * Gate the /dashboard/ page for users with `_vance_email_verified === '0'`.
 * Missing meta is treated as verified (backwards compat with pre-existing users).
 */
function vance_gate_unverified_users() {
    if ( ! is_user_logged_in() || ! is_page( 'dashboard' ) ) {
        return;
    }
    $verified = get_user_meta( get_current_user_id(), '_vance_email_verified', true );
    if ( '' === $verified ) {
        return; // legacy users with no meta — let them through
    }
    if ( 1 !== (int) $verified ) {
        wp_safe_redirect( home_url( '/verify-email/' ) );
        exit;
    }
}
add_action( 'template_redirect', 'vance_gate_unverified_users' );

/**
 * AJAX: resend verification email. Rate-limited to prevent abuse.
 */
function vance_resend_verification_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_resend_nonce' ) ) {
        wp_send_json_error( 'Invalid request, please refresh and try again.' );
    }
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Please sign in first.' );
    }
    if ( ! vance_rate_limit( 'resend_verify', 3, 600 ) ) {
        wp_send_json_error( 'Too many resend requests. Please wait 10 minutes and try again.' );
    }
    $uid      = get_current_user_id();
    $verified = get_user_meta( $uid, '_vance_email_verified', true );
    if ( '1' === (string) $verified ) {
        wp_send_json_success( array( 'message' => 'Your email is already verified.', 'already_verified' => true ) );
    }
    if ( vance_send_verification_email( $uid ) ) {
        wp_send_json_success( array( 'message' => 'Verification email sent.' ) );
    }
    wp_send_json_error( 'Could not send verification email. Please contact support.' );
}
add_action( 'wp_ajax_vance_resend_verification', 'vance_resend_verification_ajax' );

/**
 * Shortcode: [vance_verify_email] — page content for the /verify-email/ landing.
 */
function vance_verify_email_shortcode() {
    $is_verified_now = isset( $_GET['verified'] ) && '1' === $_GET['verified'];
    $had_error       = isset( $_GET['verify_error'] ) && '1' === $_GET['verify_error'];

    if ( $is_verified_now ) {
        return '<div class="vance-verify-card" style="max-width:480px;margin:60px auto;padding:48px 32px;text-align:center;background:#fff;border-radius:var(--radius-surface, 14px);box-shadow:0 8px 32px rgba(0,128,128,0.08)">
            <div style="font-size:48px;margin-bottom:12px">&#10003;</div>
            <h1 style="margin:0 0 8px;color:#008080">Email verified</h1>
            <p style="color:#666;margin-bottom:24px">Your account is ready. Redirecting to your dashboard&hellip;</p>
            <a href="' . esc_url( home_url( '/dashboard/' ) ) . '" style="display:inline-block;padding:12px 28px;background:#008080;color:#fff;border-radius:var(--radius-control, 6px);text-decoration:none;font-weight:600">Go to dashboard</a>
            <script>setTimeout(function(){window.location.href=' . wp_json_encode( home_url( '/dashboard/' ) ) . ';},1500);</script>
        </div>';
    }

    if ( ! is_user_logged_in() ) {
        return '<div class="vance-verify-card" style="max-width:480px;margin:60px auto;padding:48px 32px;text-align:center;background:#fff;border-radius:var(--radius-surface, 14px);box-shadow:0 8px 32px rgba(0,0,0,0.08)">
            <h1 style="margin:0 0 8px;color:#1a1a1a">Verify your email</h1>
            <p style="color:#666;margin-bottom:24px">Please sign in to resend your verification email.</p>
            <a href="' . esc_url( home_url( '/login/' ) ) . '" style="display:inline-block;padding:12px 28px;background:#008080;color:#fff;border-radius:var(--radius-control, 6px);text-decoration:none;font-weight:600">Sign in</a>
        </div>';
    }

    $user        = wp_get_current_user();
    $resend_nonce = wp_create_nonce( 'vance_resend_nonce' );
    $ajax_url    = admin_url( 'admin-ajax.php' );

    $error_html = $had_error
        ? '<div style="background:#fee;color:#a00;padding:12px;border-radius:var(--radius-control, 6px);margin-bottom:16px;border:1px solid #fcc;font-size:14px">That verification link was invalid or has already been used. Request a new one below.</div>'
        : '';

    ob_start();
    ?>
    <div class="vance-verify-card" style="max-width:480px;margin:60px auto;padding:48px 32px;text-align:center;background:#fff;border-radius:var(--radius-surface, 14px);box-shadow:0 8px 32px rgba(0,0,0,0.08);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif">
        <div style="font-size:48px;margin-bottom:12px">&#9993;</div>
        <h1 style="margin:0 0 8px;color:#1a1a1a;font-size:24px">Check your inbox</h1>
        <p style="color:#666;margin-bottom:24px;line-height:1.5">
            We sent a verification link to <strong><?php echo esc_html( $user->user_email ); ?></strong>.<br>
            Click the link in that email to activate your account.
        </p>
        <?php echo $error_html; // sanitized literal above ?>
        <div id="vance-resend-status" style="margin-bottom:14px;font-size:14px"></div>
        <button id="vance-resend-btn" style="display:inline-block;padding:12px 28px;background:#008080;color:#fff;border:none;border-radius:var(--radius-control, 6px);font-size:15px;font-weight:600;cursor:pointer">Resend verification email</button>
        <p style="margin-top:24px;font-size:13px;color:#999">
            Already verified? <a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" style="color:#008080">Go to dashboard</a> &middot;
            <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="color:#008080">Sign out</a>
        </p>
    </div>
    <script>
    (function(){
        var btn = document.getElementById('vance-resend-btn');
        var status = document.getElementById('vance-resend-status');
        btn.addEventListener('click', function(){
            btn.disabled = true; btn.textContent = 'Sending…';
            var body = new URLSearchParams();
            body.set('action', 'vance_resend_verification');
            body.set('nonce', <?php echo wp_json_encode( $resend_nonce ); ?>);
            fetch(<?php echo wp_json_encode( $ajax_url ); ?>, {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: body.toString()
            }).then(function(r){ return r.json(); }).then(function(data){
                if (data.success) {
                    status.style.color = '#008080';
                    status.textContent = data.data.message || 'Verification email sent.';
                    if (data.data.already_verified) {
                        setTimeout(function(){ window.location.href = <?php echo wp_json_encode( home_url( '/dashboard/' ) ); ?>; }, 1200);
                        return;
                    }
                } else {
                    status.style.color = '#a00';
                    status.textContent = data.data || 'Failed to send. Please try again.';
                }
                btn.disabled = false; btn.textContent = 'Resend verification email';
            }).catch(function(){
                status.style.color = '#a00';
                status.textContent = 'Network error.';
                btn.disabled = false; btn.textContent = 'Resend verification email';
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'vance_verify_email', 'vance_verify_email_shortcode' );

/**
 * Include Op-Ed Template Functions
 * Provides meta boxes and asset management for Op-Ed posts
 */
require get_template_directory() . '/inc/oped-template-functions.php';

/**
 * Include Tool Embedding Functions
 * Provides shortcode for embedding React tools in posts
 */
require get_template_directory() . '/inc/tool-embed.php';

/**
 * AJAX: Save Calculator Result
 * Stores a calculator result entry into user meta, keyed by tool type.
 */
function vance_save_calc_result() {
    if ( ! is_user_logged_in() ) { wp_send_json_error( 'Not logged in' ); }
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_dashboard_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }

    $user_id  = get_current_user_id();
        $tool     = sanitize_key( isset($_POST['tool']) ? $_POST['tool'] : 'malnutrition' );
    $meta_key = '_sla_calc_results_' . $tool;

        $new_entry = array(
        'id'         => sanitize_text_field( isset($_POST['result_id']) ? $_POST['result_id'] : uniqid( 'r_' ) ),
        'date'       => sanitize_text_field( isset($_POST['date']) ? $_POST['date'] : current_time( 'c' ) ),
        'score'      => intval( isset($_POST['score']) ? $_POST['score'] : 0 ),
        'risk_level' => sanitize_key( isset($_POST['risk_level']) ? $_POST['risk_level'] : '' ),
        'risk_label' => sanitize_text_field( isset($_POST['risk_label']) ? $_POST['risk_label'] : '' ),
        'bmi'        => floatval( isset($_POST['bmi']) ? $_POST['bmi'] : 0 ),
        'bmi_cat'    => sanitize_text_field( isset($_POST['bmi_cat']) ? $_POST['bmi_cat'] : '' ),
        'ibd_type'   => sanitize_key( isset($_POST['ibd_type']) ? $_POST['ibd_type'] : '' ),
    );

    $results = get_user_meta( $user_id, $meta_key, true ) ?: array();
    // Prepend newest first, cap at 50 entries
    array_unshift( $results, $new_entry );
    $results = array_slice( $results, 0, 50 );
    update_user_meta( $user_id, $meta_key, $results );

    wp_send_json_success( array( 'saved' => true ) );
}
add_action( 'wp_ajax_vance_save_calc_result', 'vance_save_calc_result' );

/**
 * AJAX: Get Calculator Results
 * Returns saved results for the current user, sorted newest first.
 */
function vance_get_calc_results() {
    if ( ! is_user_logged_in() ) { wp_send_json_error( 'Not logged in' ); }
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_dashboard_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }

    $user_id  = get_current_user_id();
        $tool     = sanitize_key( isset($_POST['tool']) ? $_POST['tool'] : 'malnutrition' );
    $meta_key = '_sla_calc_results_' . $tool;
    $results  = get_user_meta( $user_id, $meta_key, true ) ?: array();

    wp_send_json_success( $results );
}
add_action( 'wp_ajax_vance_get_calc_results', 'vance_get_calc_results' );

/**
 * Include Dashboard Functions
 * Handles User Dashboard logic (AJAX, Profiles, Bookmarks)
 */
require get_template_directory() . '/inc/dashboard-functions.php';

/**
 * Include the Recipe Catalogue
 * Slug → {name, image, url} mirror of the IBD Recipes bundle, so the dashboard
 * can render meal thumbnails and full-recipe links against saved meal plans.
 * Must load before dashboard templates that expand saved plans.
 */
require get_template_directory() . '/inc/recipe-catalogue.php';

/**
 * Recipe CPT — native replacement for the iframed IBD Recipes bundle
 * (see the rebuild plan). CPT/taxonomy registration, admin meta boxes, and
 * the one-time WP-CLI draft converter. recipe-admin.php's parse/format
 * helpers and recipe-catalogue.php's data functions are both needed by the
 * converter, so this loads after recipe-catalogue.php above.
 */
require get_template_directory() . '/inc/recipe-cpt.php';
require get_template_directory() . '/inc/recipe-admin.php';
require get_template_directory() . '/inc/recipe-converter.php';
require get_template_directory() . '/inc/recipe-frontend.php';

/**
 * Include Ask AI Functions
 * Grounded chat over hub content: retrieval, system prompt, REST route,
 * and auto-save of conversations into the user's dashboard.
 */
require get_template_directory() . '/inc/askai-kb.php';
require get_template_directory() . '/inc/askai-functions.php';
require get_template_directory() . '/inc/askai-content-sources.php';

/**
 * Dashboard feature toggles
 * One Customizer checkbox per dashboard tab. Must load before
 * user-documents.php, which asks it whether My Documents is switched on before
 * answering any of its endpoints.
 */
require get_template_directory() . '/inc/dashboard-features.php';

/**
 * Include My Documents
 * Member-uploaded documents: upload, ownership-checked streaming, text
 * extraction, and the `vance_ai_sources` filter that makes a member's own
 * document the primary source when they ask VANCE-Ai about it. Must load
 * after askai-functions.php, which declares that filter.
 */
require get_template_directory() . '/inc/user-documents.php';


/**
 * Increase maximum upload size to 10MB
 */
function vance_increase_upload_size_limit( $limit ) {
    return 10 * 1024 * 1024; // 10MB in bytes
}
add_filter( 'upload_size_limit', 'vance_increase_upload_size_limit' );

/**
 * Customizer active_callback: show the Open-mode guardrail controls only when
 * the Answer mode setting is set to 'open'.
 *
 * @param WP_Customize_Control $control
 * @return bool
 */
function vance_askai_open_mode_control_active( $control ) {
    $setting = $control->manager->get_setting( 'vance_askai_mode' );
    return $setting && 'open' === $setting->value();
}

/**
 * Fetch the list of available models from OpenRouter for the Ask AI model dropdown.
 *
 * The /models endpoint is public (no API key required). Results are cached in a
 * transient for 12 hours so the Customizer stays responsive. On any failure a small
 * hardcoded fallback list is returned so the control is never empty.
 *
 * @return array Associative array of [ model_id => human label ].
 */
function vance_get_openrouter_models() {
    $fallback = array(
        'anthropic/claude-opus-4.8'   => 'Anthropic: Claude Opus 4.8 (anthropic/claude-opus-4.8)',
        'anthropic/claude-opus-4.7'   => 'Anthropic: Claude Opus 4.7 (anthropic/claude-opus-4.7)',
        'google/gemini-3.5-flash'     => 'Google: Gemini 3.5 Flash (google/gemini-3.5-flash)',
    );

    $cached = get_transient( 'vance_openrouter_models' );
    if ( is_array( $cached ) && ! empty( $cached ) ) {
        return $cached;
    }

    $response = wp_remote_get( 'https://openrouter.ai/api/v1/models', array( 'timeout' => 8 ) );
    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        return $fallback;
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
        return $fallback;
    }

    $choices = array();
    foreach ( $data['data'] as $entry ) {
        if ( empty( $entry['id'] ) ) {
            continue;
        }
        $id            = $entry['id'];
        $name          = ! empty( $entry['name'] ) ? $entry['name'] : $id;
        $choices[ $id ] = $name . ' (' . $id . ')';
    }

    if ( empty( $choices ) ) {
        return $fallback;
    }

    asort( $choices );
    set_transient( 'vance_openrouter_models', $choices, 12 * HOUR_IN_SECONDS );
    return $choices;
}

/**
 * Sanitize a colour value for the Customizer. Accepts hex (#fff / #ffffff) or rgb()/rgba().
 * Returns '' on anything else so the setting falls back to its default.
 */
function vance_sanitize_color( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) { return ''; }
	if ( preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $value ) ) { return $value; }
	if ( preg_match( '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/', $value ) ) { return $value; }
	return '';
}

/**
 * Default colours for the Vance AI modal (mirror the hardcoded CSS defaults).
 *
 * @return array [ setting_key => default_value ]
 */
function vance_modal_color_defaults() {
	return array(
		'vance_modal_backdrop'         => 'rgba(10, 25, 41, 0.78)',
		'vance_modal_panel_bg'         => '#0A1929',
		'vance_modal_text_color'       => '#ffffff',
		'vance_modal_header_bg'        => '#061119',
		'vance_modal_title_color'      => '#ffffff',
		'vance_modal_bot_bubble_bg'    => 'rgba(255,255,255,0.08)',
		'vance_modal_bot_bubble_text'  => '#ffffff',
		'vance_modal_user_bubble_bg'   => '#008080',
		'vance_modal_user_bubble_text' => '#ffffff',
		'vance_modal_input_bg'         => 'rgba(255,255,255,0.94)',
		'vance_modal_input_text'       => '#1a2332',
		'vance_modal_send_bg'          => '#008080',
	);
}

/**
 * Add Advanced Theme Settings to Customizer
 */
/**
 * Native HTML5 colour control for the Customizer.
 *
 * Renders a browser-native <input type="color"> instead of WordPress's
 * wp-color-picker (Iris) widget. Iris relies on Customizer controls-pane JS that
 * an unrelated script error can knock out — leaving the swatch dead (clicking
 * "Select Color" does nothing) while native range inputs keep working. The
 * native colour input needs no picker JS: it binds through the same core
 * setting-link mechanism the range sliders use, so it stays reliable. The value
 * is a #rrggbb hex string, saved via the standard setting link.
 *
 * Guarded by class_exists( 'WP_Customize_Control' ) so it is only defined on
 * Customizer requests (where that base class is loaded), matching the pattern in
 * inc/customizer-sortable-control.php.
 */
if ( class_exists( 'WP_Customize_Control' ) && ! class_exists( 'Vance_Customize_HTML5_Color_Control' ) ) {

    class Vance_Customize_HTML5_Color_Control extends WP_Customize_Control {

        public $type = 'vance_html5_color';

        public function render_content() {
            $value = (string) $this->value();
            if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/', $value ) ) {
                $value = '#434343';
            }
            ?>
            <?php if ( ! empty( $this->label ) ) : ?>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <?php endif; ?>
            <?php if ( ! empty( $this->description ) ) : ?>
                <span class="description customize-control-description"><?php echo wp_kses_post( $this->description ); ?></span>
            <?php endif; ?>
            <span class="vance-color-field" style="display:flex; align-items:center; gap:10px; margin-top:6px;">
                <input
                    type="color"
                    value="<?php echo esc_attr( $value ); ?>"
                    style="width:46px; height:34px; padding:2px; border:1px solid #dcdcde; border-radius:var(--radius-field, 10px); background:#fff; cursor:pointer;"
                    <?php $this->link(); ?>
                    oninput="var h=this.closest('.vance-color-field').querySelector('.vance-color-hex'); if(h){h.value=this.value.toUpperCase();}"
                />
                <input
                    type="text"
                    class="vance-color-hex"
                    value="<?php echo esc_attr( strtoupper( $value ) ); ?>"
                    readonly
                    aria-label="<?php esc_attr_e( 'Selected colour hex value', 'vance-health-hub' ); ?>"
                    style="width:96px; font-family:monospace;"
                />
            </span>
            <?php
        }
    }
}

/**
 * Sanitize a float value for a Customizer setting.
 *
 * WP_Customize_Setting::sanitize() invokes the sanitize_callback via
 * apply_filters() with TWO arguments ( $value, $setting ). PHP 8's internal
 * floatval() accepts exactly one argument and throws ArgumentCountError when
 * handed two — which fatals the entire customize_save request (HTTP 500,
 * frozen Customizer, Publish blocked). This userland wrapper takes the value,
 * harmlessly ignores the extra arg, and returns a float.
 *
 * @param mixed $value Raw setting value.
 * @return float
 */
function vance_sanitize_float( $value ) {
    return floatval( $value );
}

/**
 * Resolve the "main category" (top-level ancestor) of a post.
 *
 * Per-category overlay settings are keyed to top-level categories only, so a
 * post filed under a sub-category (e.g. a child of Gastro Living) inherits its
 * parent's overlay. We take the post's first/primary category and walk up the
 * hierarchy to its top-level ancestor.
 *
 * @param int|null $post_id Post ID (defaults to the current post).
 * @return int Top-level category term_id, or 0 when the post has no category.
 */
function vance_post_overlay_main_category_id( $post_id = null ) {
    $cats = get_the_category( $post_id );
    if ( empty( $cats ) ) {
        return 0;
    }
    $term  = $cats[0];
    $guard = 0;
    while ( $term && ! empty( $term->parent ) && $guard < 10 ) {
        $parent = get_term( $term->parent, 'category' );
        if ( ! $parent || is_wp_error( $parent ) ) {
            break;
        }
        $term = $parent;
        $guard++;
    }
    return (int) $term->term_id;
}

/**
 * Resolve overlay settings for a (top-level) category id.
 *
 * The category's own per-category settings win when its "Use custom overlay"
 * toggle is on; otherwise the global Post Hero Overlay settings are returned.
 * Pass 0 for the global settings. This is the single place the custom/global
 * fallback lives — the post- and category-keyed resolvers both defer to it.
 *
 * @param int $cat_id Top-level category term id (0 = global).
 * @return array{enable:bool,color:string,opacity:float,spread:float}
 */
function vance_overlay_settings_for_category( $cat_id ) {
    $cat_id     = (int) $cat_id;
    $use_custom = $cat_id && vance_get_theme_mod( "vance_post_overlay_{$cat_id}_custom", false );
    $p          = $use_custom ? "vance_post_overlay_{$cat_id}_" : 'vance_post_overlay_';

    return array(
        'enable'  => (bool) vance_get_theme_mod( $p . 'enable', true ),
        'color'   => vance_get_theme_mod( $p . 'color', '#434343' ),
        'opacity' => vance_get_theme_mod( $p . 'opacity', 1 ),
        'spread'  => vance_get_theme_mod( $p . 'spread', 100 ),
    );
}

/**
 * Resolve the effective overlay settings for a post (via its main category).
 *
 * @param int|null $post_id Post ID (defaults to the current post).
 * @return array{enable:bool,color:string,opacity:float,spread:float}
 */
function vance_resolve_post_overlay_settings( $post_id = null ) {
    return vance_overlay_settings_for_category( vance_post_overlay_main_category_id( $post_id ) );
}

/**
 * The per-category "source of truth" accent colour, keyed by category.
 *
 * Walks the given category up to its top-level ancestor (per-category overlay
 * settings live on top-level categories) and returns that category's overlay
 * colour — custom when it defines one, else the global overlay colour. This is
 * the category-keyed companion to vance_post_eyebrow_color() (post-keyed) so
 * category-level UI (homepage KB blocks, colour bars) can share the one
 * canonical colour instead of a separate per-widget setting.
 *
 * @param int $cat_id Category term id.
 * @return string Hex colour (never empty; falls back to #434343).
 */
function vance_category_source_color( $cat_id ) {
    $cat_id = (int) $cat_id;
    $guard  = 0;
    while ( $cat_id ) {
        $term = get_term( $cat_id, 'category' );
        if ( ! $term || is_wp_error( $term ) || empty( $term->parent ) || $guard >= 10 ) {
            break;
        }
        $cat_id = (int) $term->parent;
        $guard++;
    }
    $settings = vance_overlay_settings_for_category( $cat_id );
    return ! empty( $settings['color'] ) ? $settings['color'] : '#434343';
}

/**
 * Build the post hero overlay gradient layer.
 *
 * A single continuous full-bleed gradient running left → right across a post's
 * featured image: solid start colour (#434343 by default) on the left so the
 * overlaid title text stays legible, fading to fully transparent on the right.
 * Returns only the gradient layer — callers stack it above the image URL in a
 * `background-image` value. Returns an empty string when the overlay is off.
 *
 * @param array|null $settings Effective settings (enable/color/opacity/spread).
 *                             When null, the global Customizer settings are read
 *                             — Content & Knowledge Base → Post Hero Overlay.
 * @return string A CSS linear-gradient() value, or '' when the overlay is off.
 */
function vance_post_hero_overlay_gradient( $settings = null ) {
    if ( ! is_array( $settings ) ) {
        $settings = array(
            'enable'  => vance_get_theme_mod( 'vance_post_overlay_enable', true ),
            'color'   => vance_get_theme_mod( 'vance_post_overlay_color', '#434343' ),
            'opacity' => vance_get_theme_mod( 'vance_post_overlay_opacity', 1 ),
            'spread'  => vance_get_theme_mod( 'vance_post_overlay_spread', 100 ),
        );
    }

    if ( empty( $settings['enable'] ) ) {
        return '';
    }

    $color   = isset( $settings['color'] ) ? $settings['color'] : '#434343';
    $opacity = max( 0, min( 1, (float) ( isset( $settings['opacity'] ) ? $settings['opacity'] : 1 ) ) );
    $spread  = max( 10, min( 100, (float) ( isset( $settings['spread'] ) ? $settings['spread'] : 100 ) ) );

    // Normalise hex (#rgb or #rrggbb) → r,g,b so we can express it as rgba().
    $hex = ltrim( (string) $color, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
        $hex = '434343';
    }
    $r = hexdec( substr( $hex, 0, 2 ) );
    $g = hexdec( substr( $hex, 2, 2 ) );
    $b = hexdec( substr( $hex, 4, 2 ) );

    // Trim trailing zeros so "1.00" → "1" and "62.50" → "62.5".
    $fmt = function ( $n ) {
        return rtrim( rtrim( number_format( (float) $n, 2, '.', '' ), '0' ), '.' );
    };

    // One continuous gradient. The start colour holds SOLID across the first
    // half of the fade distance, then ramps to fully transparent by $spread% of
    // the width. Holding a solid plateau (instead of fading from the very left
    // edge) means a full Fade Distance genuinely covers the whole image —
    // without it, a plain 0%→100% fade is already 50% transparent at the
    // midpoint and visibly peters out around half-way.
    $solid = $spread * 0.5;
    return sprintf(
        'linear-gradient(to right, rgba(%1$d,%2$d,%3$d,%4$s) 0%%, rgba(%1$d,%2$d,%3$d,%4$s) %5$s%%, rgba(%1$d,%2$d,%3$d,0) %6$s%%)',
        $r, $g, $b, $fmt( $opacity ), $fmt( $solid ), $fmt( $spread )
    );
}

/**
 * Resolve a post's category-eyebrow colour.
 *
 * Mirrors the post's hero overlay colour (per top-level category when that
 * category defines a custom overlay, otherwise the global Post Hero Overlay
 * colour) so eyebrows and category labels always match the article hero.
 *
 * @param int|null $post_id Post ID (defaults to the current post).
 * @return string Hex colour (never empty; falls back to the primary teal).
 */
function vance_post_eyebrow_color( $post_id = null ) {
    if ( null === $post_id ) {
        $post_id = get_the_ID();
    }
    $settings = vance_resolve_post_overlay_settings( $post_id );

    return ! empty( $settings['color'] ) ? $settings['color'] : '#008080';
}

/**
 * Article-card category eyebrow chip.
 *
 * A small uppercase label pinned to the top-left corner of a card thumbnail.
 * Its background colour mirrors the post's hero overlay colour (see
 * vance_post_eyebrow_color()). By default the label is the post's MAIN
 * (top-level) category; pass $prefer_sub = true (used on category / archive
 * pages) to show the post's SUB-category instead, falling back to the main
 * category when the post has no child term. Uppercasing is done in CSS
 * (.card-eyebrow) to stay multibyte-safe.
 *
 * @param int|null $post_id    Post ID (defaults to the current post).
 * @param bool     $prefer_sub Show the sub-category label instead of the main one.
 * @return string Eyebrow chip HTML, or '' when the post has no category.
 */
function vance_card_eyebrow_html( $post_id = null, $prefer_sub = false ) {
    if ( null === $post_id ) {
        $post_id = get_the_ID();
    }
    $main_id = vance_post_overlay_main_category_id( $post_id );
    if ( ! $main_id ) {
        return '';
    }

    // Default label: the main (top-level) category.
    $label     = '';
    $main_term = get_term( $main_id, 'category' );
    if ( $main_term && ! is_wp_error( $main_term ) ) {
        $label = $main_term->name;
    }

    // On category / archive pages, prefer the deeper sub-category (first child term).
    if ( $prefer_sub ) {
        foreach ( get_the_category( $post_id ) as $c ) {
            if ( ! empty( $c->parent ) ) {
                $label = $c->name;
                break;
            }
        }
    }

    if ( '' === $label ) {
        return '';
    }

    return sprintf(
        '<span class="card-eyebrow" style="background:%1$s;">%2$s</span>',
        esc_attr( vance_post_eyebrow_color( $post_id ) ),
        esc_html( $label )
    );
}

/**
 * Article title, capped for a card.
 *
 * Knowledgebase cards sit in tracks as narrow as ~212px, where a full clinical
 * title (the longest on the homepage runs to 162 characters) pushes the card
 * two or three lines taller than its neighbours and drags the meta footer out
 * of alignment across the row. Capping in PHP rather than clamping in CSS is
 * deliberate: -webkit-line-clamp hides the overflow but still lets the browser
 * lay out the whole string, and the cap has to hold in the poster and bento
 * layouts too, whose titles sit over an image with no fixed line box.
 *
 * Distinct from vance_cw_truncate_chars() in inc/content-widget.php, which
 * cuts at exactly $max and appends the ellipsis after it: this one backs up to
 * the last word boundary and counts the ellipsis inside the budget, so a
 * 70-character cap returns at most 70 characters and never splits a word.
 * mbstring is guarded the same way that helper guards it.
 *
 * @param int|null $post_id Post ID (defaults to the current post).
 * @param int      $max     Maximum length of the returned string, in characters.
 * @return string Title unchanged when it already fits, else a truncated one.
 */
function vance_card_title( $post_id = null, $max = 70 ) {
    if ( null === $post_id ) {
        $post_id = get_the_ID();
    }
    $title = get_the_title( $post_id );
    $max   = max( 8, (int) $max );
    $mb    = function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) && function_exists( 'mb_strrpos' );

    $len = $mb ? mb_strlen( $title ) : strlen( $title );
    if ( $len <= $max ) {
        return $title;
    }

    // Reserve one character for the ellipsis, then back up to the last space.
    $cut = $mb ? mb_substr( $title, 0, $max - 1 ) : substr( $title, 0, $max - 1 );
    $sp  = $mb ? mb_strrpos( $cut, ' ' ) : strrpos( $cut, ' ' );
    if ( false !== $sp && $sp > 0 ) {
        $cut = $mb ? mb_substr( $cut, 0, $sp ) : substr( $cut, 0, $sp );
    }

    return rtrim( rtrim( $cut ), ",.;:-" ) . '…';
}

/**
 * Article-card meta footer (date · read-time · views).
 *
 * Rendered at the BOTTOM of an article card — previously these were overlaid on
 * the thumbnail. Segments are divided by CSS borders; see .card-meta-footer in
 * main.css. Read-time is omitted when unavailable; views can be suppressed for
 * contexts where the count isn't meaningful.
 *
 * @param int|null $post_id    Post ID (defaults to the current post).
 * @param bool     $show_views Whether to include the view-count segment.
 * @return string Footer HTML.
 */
function vance_card_meta_footer_html( $post_id = null, $show_views = true ) {
    if ( null === $post_id ) {
        $post_id = get_the_ID();
    }
    $date  = get_the_date( '', $post_id );
    $read  = function_exists( 'vance_get_read_time' ) ? (int) vance_get_read_time( $post_id ) : 0;
    $views = function_exists( 'vance_get_view_count' ) ? (int) vance_get_view_count( $post_id ) : 0;

    $out  = '<div class="card-meta-footer">';
    $out .= '<span class="card-meta-item">' . esc_html( $date ) . '</span>';
    if ( $read > 0 ) {
        $out .= '<span class="card-meta-item">' . esc_html( $read ) . ' min read</span>';
    }
    if ( $show_views ) {
        $out .= '<span class="card-meta-item">' . esc_html( number_format( $views ) ) . ' views</span>';
    }
    $out .= '</div>';

    return $out;
}

/**
 * Live-preview script for the Post Hero Overlay.
 *
 * The overlay settings use the 'postMessage' transport so dragging a slider or
 * picking a colour updates the hero instantly in the browser instead of forcing
 * a full server-side preview reload on every change. Reloading on each change
 * floods the Customizer preview messenger and eventually trips the "Looks like
 * something's gone wrong" error (and blocks Publish); postMessage avoids that
 * entirely. This script mirrors vance_post_hero_overlay_gradient() in JS and
 * repaints the .oped-hero-image element as settings change.
 *
 * Runs on wp_enqueue_scripts (not customize_preview_init) so the main query is
 * available and we can localise the previewed post's main category — letting the
 * preview switch between the global and per-category settings live.
 */
function vance_post_overlay_preview_js() {
    if ( ! is_customize_preview() ) {
        return;
    }
    wp_enqueue_script(
        'vance-post-overlay-preview',
        get_template_directory_uri() . '/assets/js/customizer-post-overlay.js',
        array( 'customize-preview' ),
        // filemtime cache-bust so preview picks up JS changes on every deploy
        // (a static version string would serve a stale, cached file).
        ( @filemtime( get_template_directory() . '/assets/js/customizer-post-overlay.js' ) ?: wp_get_theme()->get( 'Version' ) ),
        true
    );
    $cat_id = is_single() ? vance_post_overlay_main_category_id( get_queried_object_id() ) : 0;
    wp_localize_script( 'vance-post-overlay-preview', 'vancePostOverlayPreview', array(
        'catId' => $cat_id,
    ) );
}
add_action( 'wp_enqueue_scripts', 'vance_post_overlay_preview_js' );

function vance_customize_register( $wp_customize ) {
    // 0. Vance Theme Panels
    $wp_customize->add_panel( 'vance_brand_panel', array( 'title' => __( 'Brand Identity', 'vance-health-hub' ), 'priority' => 10 ) );
    $wp_customize->add_panel( 'vance_homepage_panel', array( 'title' => __( 'Homepage', 'vance-health-hub' ), 'priority' => 11 ) );
    $wp_customize->add_panel( 'vance_content_panel', array( 'title' => __( 'Content & Knowledge Base', 'vance-health-hub' ), 'priority' => 12 ) );
    $wp_customize->add_panel( 'vance_footer_panel', array( 'title' => __( 'Footer', 'vance-health-hub' ), 'priority' => 13 ) );
    $wp_customize->add_panel( 'vance_advanced_panel', array( 'title' => __( 'Advanced', 'vance-health-hub' ), 'priority' => 14 ) );
    $wp_customize->add_panel( 'vance_discovery_panel', array( 'title' => __( 'Discovery Engine', 'vance-health-hub' ), 'priority' => 11.5 ) );

    // Move Site Identity into Vance Theme Settings
    if ( $wp_customize->get_section('title_tagline') ) {
        $wp_customize->get_section('title_tagline')->panel = 'vance_brand_panel';
        $wp_customize->get_section('title_tagline')->priority = 5;
    }

    // 1. Social Media Links Section
    $wp_customize->add_section( 'vance_social_links', array(
        'title'    => __( 'Social Media Links', 'vance-health-hub' ),
        'priority' => 30,
        'panel'    => 'vance_brand_panel',
    ) );

    $social_networks = array(
        'linkedin'  => 'LinkedIn',
        'facebook'  => 'Facebook',
        'twitter'   => 'X (formerly Twitter)',
        'instagram' => 'Instagram',
    );

    foreach ( $social_networks as $key => $label ) {
        $wp_customize->add_setting( 'vance_social_' . $key, array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ) );

        $wp_customize->add_control( 'vance_social_' . $key, array(
            'label'   => $label,
            'section' => 'vance_social_links',
            'type'    => 'url',
        ) );
    }

    // 2. Hero Images & Content Section
    $wp_customize->add_section( 'vance_hero_settings', array(
        'title'       => __( 'Hero', 'vance-health-hub' ),
        'description' => __( 'Manage hero images, text, and styling.', 'vance-health-hub' ),
        'priority'    => 31,
        'panel'       => 'vance_homepage_panel',
    ) );

    // -- Which hero design the homepage uses ---------------------------------
    // Everything else in THIS section belongs to the classic dark hero (it is
    // that hero's slide 1). The spotlight hero's own copy lives in its own
    // section below, so switching designs never overwrites the other one's
    // settings — flip this back and the old hero returns intact.
    $wp_customize->add_setting( 'vance_hero_style', array(
        'default'           => 'spotlight',
        'sanitize_callback' => function ( $v ) { return in_array( $v, array( 'spotlight', 'carousel' ), true ) ? $v : 'spotlight'; },
    ) );
    $wp_customize->add_control( 'vance_hero_style', array(
        'label'       => __( 'Homepage hero design', 'vance-health-hub' ),
        'description' => __( 'Spotlight is the light, search-led hero. Classic is the dark hero configured by the rest of this section (and by the Hero Slide sections).', 'vance-health-hub' ),
        'section'     => 'vance_hero_settings',
        'type'        => 'select',
        'choices'     => array(
            'spotlight' => __( 'Spotlight — light, search-led', 'vance-health-hub' ),
            'carousel'  => __( 'Classic — dark hero / carousel', 'vance-health-hub' ),
        ),
    ) );

    // -- Spotlight hero ------------------------------------------------------
    // Same field-list-driven registration the Hero Slide sections use. Every
    // default comes from vance_hero_spotlight_field_defaults(), so the control
    // list and the renderer cannot drift apart.
    $wp_customize->add_section( 'vance_hero_spotlight_settings', array(
        'title'       => __( 'Hero — Spotlight', 'vance-health-hub' ),
        'description' => __( 'The light, search-led homepage hero. Only rendered while "Homepage hero design" (in the Hero section) is set to Spotlight. Headline, intro and both buttons start out inheriting whatever the classic hero says; editing them here stores a Spotlight-only override and leaves the classic hero untouched.', 'vance-health-hub' ),
        'priority'    => 30.5,
        'panel'       => 'vance_homepage_panel',
    ) );

    $vance_hs_fields = array(
        'image'              => array( 'type' => 'image',    'label' => 'Photograph', 'description' => 'Leave blank for the supplied photo, which already has its edges feathered to melt into the background. A replacement wants roughly 1400&times;875 and a light, uncluttered left-hand side.' ),
        'image_alt'          => array( 'type' => 'text',     'label' => 'Photograph — alt text' ),
        'title'              => array( 'type' => 'html',     'label' => 'Headline', 'description' => 'Prefilled from the classic hero, so the homepage keeps saying what it says today. Wrap words in &lt;span class="highlight"&gt;…&lt;/span&gt; to accent them in the brand teal.' ),
        'title_color'        => array( 'type' => 'color',    'label' => 'Headline Colour' ),
        'intro'              => array( 'type' => 'textarea', 'label' => 'Intro Paragraph' ),
        'intro_color'        => array( 'type' => 'color',    'label' => 'Body Text Colour' ),
        'bg_from'            => array( 'type' => 'color',    'label' => 'Background — Top' ),
        'bg_to'              => array( 'type' => 'color',    'label' => 'Background — Bottom', 'description' => 'The photograph is dissolved into these two colours, so changing them keeps its edges seamless.' ),
        'btn1_text'          => array( 'type' => 'text',     'label' => 'Button 1, Text' ),
        'btn1_link'          => array( 'type' => 'text',     'label' => 'Button 1, Link', 'description' => 'Prefilled from the classic hero. Clear it to fall back to the gastro conditions hub.' ),
        'btn1_bg_color'      => array( 'type' => 'color',    'label' => 'Button 1, Background' ),
        'btn1_text_color'    => array( 'type' => 'color',    'label' => 'Button 1, Text Colour' ),
        'btn1_hover_bg'      => array( 'type' => 'color',    'label' => 'Button 1, Background on Hover' ),
        'btn2_text'          => array( 'type' => 'text',     'label' => 'Button 2, Text' ),
        'btn2_link'          => array( 'type' => 'text',     'label' => 'Button 2, Link', 'description' => 'Prefilled from the classic hero. Clear it to fall back to the Knowledgebase.' ),
        'show_search'        => array( 'type' => 'checkbox', 'label' => 'Show the search field' ),
        'search_label'       => array( 'type' => 'text',     'label' => 'Search — Prompt' ),
        'search_placeholder' => array( 'type' => 'text',     'label' => 'Search — Placeholder' ),
        'show_card'          => array( 'type' => 'checkbox', 'label' => 'Show the trust card' ),
        'card_title'         => array( 'type' => 'text',     'label' => 'Trust Card — Heading' ),
        'card_text'          => array( 'type' => 'textarea', 'label' => 'Trust Card — Body' ),
        'card_bg_color'      => array( 'type' => 'color',    'label' => 'Trust Card — Background' ),
    );
    $vance_hs_defaults = vance_hero_spotlight_field_defaults();

    foreach ( $vance_hs_fields as $hs_field => $hs_meta ) {
        $hs_id = 'vance_hero_spotlight_' . $hs_field;

        switch ( $hs_meta['type'] ) {
            case 'color':
                $hs_sanitize = 'sanitize_hex_color';
                break;
            case 'image':
                $hs_sanitize = 'esc_url_raw';
                break;
            case 'checkbox':
                $hs_sanitize = 'vance_sanitize_checkbox';
                break;
            case 'textarea':
                $hs_sanitize = 'sanitize_textarea_field';
                break;
            case 'html':
                // The renderer runs the headline through wp_kses_post() and the
                // highlight <span> is documented, so sanitize to the same
                // allow-list on save rather than stripping every tag.
                $hs_sanitize = 'wp_kses_post';
                break;
            default:
                $hs_sanitize = 'sanitize_text_field';
        }

        $wp_customize->add_setting( $hs_id, array(
            'default'           => isset( $vance_hs_defaults[ $hs_field ] ) ? $vance_hs_defaults[ $hs_field ] : '',
            'sanitize_callback' => $hs_sanitize,
        ) );

        $hs_args = array(
            'label'   => $hs_meta['label'],
            'section' => 'vance_hero_spotlight_settings',
        );
        if ( isset( $hs_meta['description'] ) ) { $hs_args['description'] = $hs_meta['description']; }

        if ( $hs_meta['type'] === 'color' ) {
            $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $hs_id, $hs_args ) );
        } elseif ( $hs_meta['type'] === 'image' ) {
            $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $hs_id, $hs_args ) );
        } else {
            $hs_args['type'] = ( $hs_meta['type'] === 'html' ) ? 'text' : $hs_meta['type'];
            $wp_customize->add_control( $hs_id, $hs_args );
        }
    }

    $wp_customize->add_setting( 'vance_homepage_hero_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );

    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_homepage_hero_image', array(
        'label'       => __( 'Homepage Hero Image', 'vance-health-hub' ),
        'description' => __( 'High resolution (1920x800px) ensures clarity.', 'vance-health-hub' ),
        'section'     => 'vance_hero_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_category_hero_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );

    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_category_hero_image', array(
        'label'       => __( 'Category/Archive Hero Image', 'vance-health-hub' ),
        'description' => __( 'Default hero for all category pages.', 'vance-health-hub' ),
        'section'     => 'vance_hero_settings',
    ) ) );

    // Hero Text Content (New)
    $wp_customize->add_setting( 'vance_hero_custom_title', array(
        'default'           => 'Evidence-Based Healthcare Knowledge',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_hero_custom_title', array(
        'label'       => __( 'Homepage Hero Title', 'vance-health-hub' ),
        'section'     => 'vance_hero_settings',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_hero_custom_subtitle', array(
        'default'           => 'Pharma-grade clinical resources, research, and tools for healthcare professionals.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'vance_hero_custom_subtitle', array(
        'label'       => __( 'Homepage Hero Subtitle', 'vance-health-hub' ),
        'section'     => 'vance_hero_settings',
        'type'        => 'textarea',
    ) );

    // Hero Tag & Buttons
    $wp_customize->add_setting( 'vance_hero_tag_label', array(
        'default'           => 'HEALTHCARE KNOWLEDGE HUB',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_hero_tag_label', array(
        'label'   => __( 'Hero Tag Label', 'vance-health-hub' ),
        'section' => 'vance_hero_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_hero_button_1_text', array(
        'default'           => "I'm a Practitioner",
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_hero_button_1_text', array(
        'label'   => __( 'Hero Button 1 Text', 'vance-health-hub' ),
        'section' => 'vance_hero_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_hero_button_1_link', array(
        'default'           => '/healthcare-professionals/',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_hero_button_1_link', array(
        'label'   => __( 'Hero Button 1 Link', 'vance-health-hub' ),
        'section' => 'vance_hero_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_hero_button_2_text', array(
        'default'           => "I'm a Patient",
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_hero_button_2_text', array(
        'label'   => __( 'Hero Button 2 Text', 'vance-health-hub' ),
        'section' => 'vance_hero_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_hero_button_2_link', array(
        'default'           => '/patients/',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_hero_button_2_link', array(
        'label'   => __( 'Hero Button 2 Link', 'vance-health-hub' ),
        'section' => 'vance_hero_settings',
        'type'    => 'text',
    ) );

    // Hero Styling Settings
    $wp_customize->add_setting( 'vance_hero_title_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_title_color', array(
        'label'   => __( 'Hero Title Color', 'vance-health-hub' ),
        'section' => 'vance_hero_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_hero_title_size', array(
        'default'           => 52,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_hero_title_size', array(
        'label'       => __( 'Hero Title Size (px)', 'vance-health-hub' ),
        'section'     => 'vance_hero_settings',
        'type'        => 'range',
        'input_attrs' => array( 'min' => 24, 'max' => 100, 'step' => 1 ),
    ) );

    $wp_customize->add_setting( 'vance_hero_mask_toggle', array(
        'default'           => true,
        'sanitize_callback' => 'vance_sanitize_checkbox',
    ) );
    $wp_customize->add_control( 'vance_hero_mask_toggle', array(
        'label'   => __( 'Enable Dark Overlay Mask', 'vance-health-hub' ),
        'section' => 'vance_hero_settings',
        'type'    => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_hero_mask_opacity', array(
        'default'           => 0.5,
        'sanitize_callback' => 'vance_sanitize_float',
    ) );
    $wp_customize->add_control( 'vance_hero_mask_opacity', array(
        'label'       => __( 'Overlay Opacity (0.0 - 1.0)', 'vance-health-hub' ),
        'section'     => 'vance_hero_settings',
        'type'        => 'range',
        'input_attrs' => array( 'min' => 0, 'max' => 1, 'step' => 0.1 ),
    ) );

    // -------------------------------------------------------------------------
    // Post Hero Overlay
    // A single continuous full-bleed gradient laid over each post's featured
    // image, running left → right: solid on the left so overlaid title text
    // stays legible, fading to fully transparent on the right. Applied in
    // single.php via vance_post_hero_overlay_gradient().
    // -------------------------------------------------------------------------
    $wp_customize->add_section( 'vance_post_hero_overlay', array(
        'title'       => __( 'Post Hero Overlay', 'vance-health-hub' ),
        'description' => __( 'Gradient laid over the featured image on single posts. Fades from a solid colour on the left (keeping the title readable) to transparent on the right.', 'vance-health-hub' ),
        'priority'    => 33,
        'panel'       => 'vance_content_panel',
    ) );

    $wp_customize->add_setting( 'vance_post_overlay_enable', array(
        'default'           => true,
        'sanitize_callback' => 'vance_sanitize_checkbox',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'vance_post_overlay_enable', array(
        'label'   => __( 'Enable Post Hero Overlay', 'vance-health-hub' ),
        'section' => 'vance_post_hero_overlay',
        'type'    => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_post_overlay_color', array(
        'default'           => '#434343',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    if ( class_exists( 'Vance_Customize_HTML5_Color_Control' ) ) {
        // Native browser colour picker — reliable even if the Iris (wp-color-picker) widget fails to init.
        $wp_customize->add_control( new Vance_Customize_HTML5_Color_Control( $wp_customize, 'vance_post_overlay_color', array(
            'label'       => __( 'Overlay Start Colour', 'vance-health-hub' ),
            'description' => __( 'The solid colour on the left edge (default #434343).', 'vance-health-hub' ),
            'section'     => 'vance_post_hero_overlay',
        ) ) );
    } else {
        // Fallback to the core colour control if the custom class is unavailable.
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_post_overlay_color', array(
            'label'   => __( 'Overlay Start Colour', 'vance-health-hub' ),
            'section' => 'vance_post_hero_overlay',
        ) ) );
    }

    $wp_customize->add_setting( 'vance_post_overlay_opacity', array(
        'default'           => 1,
        'sanitize_callback' => 'vance_sanitize_float',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'vance_post_overlay_opacity', array(
        'label'       => __( 'Start Opacity (0.0 - 1.0)', 'vance-health-hub' ),
        'description' => __( 'Strength of the overlay at the left edge.', 'vance-health-hub' ),
        'section'     => 'vance_post_hero_overlay',
        'type'        => 'range',
        'input_attrs' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ),
    ) );

    $wp_customize->add_setting( 'vance_post_overlay_spread', array(
        'default'           => 100,
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'vance_post_overlay_spread', array(
        'label'       => __( 'Fade Distance (% of width)', 'vance-health-hub' ),
        'description' => __( 'How far across the image the overlay extends before clearing. The colour holds solid for the first half of this distance, then fades to transparent. 100% covers the whole width (fading out on the right); lower values keep more of the right side clear.', 'vance-health-hub' ),
        'section'     => 'vance_post_hero_overlay',
        'type'        => 'range',
        'input_attrs' => array( 'min' => 10, 'max' => 100, 'step' => 5 ),
    ) );

    // -------------------------------------------------------------------------
    // Post Hero Overlay — Per Category
    // For each TOP-LEVEL category, optionally override the global overlay above.
    // "Use custom overlay" off  → the category inherits the global settings.
    // "Use custom overlay" on   → this category's own enable/colour/opacity/fade
    //                             apply. Posts resolve to their main (top-level)
    //                             category via vance_resolve_post_overlay_settings().
    // -------------------------------------------------------------------------
    $wp_customize->add_section( 'vance_post_hero_overlay_cats', array(
        'title'       => __( 'Post Hero Overlay - Per Category', 'vance-health-hub' ),
        'description' => __( 'Give each main (top-level) category its own overlay. Tick “Use custom overlay” for a category to override the global settings; leave it off to inherit them. Posts in a sub-category use their top-level parent’s overlay.', 'vance-health-hub' ),
        'priority'    => 33.5,
        'panel'       => 'vance_content_panel',
    ) );

    foreach ( get_categories( array( 'hide_empty' => false, 'parent' => 0 ) ) as $vance_ov_cat ) {
        $cid   = (int) $vance_ov_cat->term_id;
        $cname = $vance_ov_cat->name;

        // Use custom? (master toggle for this category)
        $wp_customize->add_setting( "vance_post_overlay_{$cid}_custom", array(
            'default'           => false,
            'sanitize_callback' => 'vance_sanitize_checkbox',
            'transport'         => 'postMessage',
        ) );
        $wp_customize->add_control( "vance_post_overlay_{$cid}_custom", array(
            'label'   => sprintf( __( '%s: Use custom overlay', 'vance-health-hub' ), $cname ),
            'section' => 'vance_post_hero_overlay_cats',
            'type'    => 'checkbox',
        ) );

        // Enable overlay for this category.
        $wp_customize->add_setting( "vance_post_overlay_{$cid}_enable", array(
            'default'           => true,
            'sanitize_callback' => 'vance_sanitize_checkbox',
            'transport'         => 'postMessage',
        ) );
        $wp_customize->add_control( "vance_post_overlay_{$cid}_enable", array(
            'label'       => sprintf( __( '%s: Enable overlay', 'vance-health-hub' ), $cname ),
            'description' => __( 'Only applies when “Use custom overlay” is ticked. Untick to show this category’s posts with no overlay.', 'vance-health-hub' ),
            'section'     => 'vance_post_hero_overlay_cats',
            'type'        => 'checkbox',
        ) );

        // Start colour.
        $wp_customize->add_setting( "vance_post_overlay_{$cid}_color", array(
            'default'           => '#434343',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ) );
        $vance_ov_color_args = array(
            'label'   => sprintf( __( '%s: Start colour', 'vance-health-hub' ), $cname ),
            'section' => 'vance_post_hero_overlay_cats',
        );
        if ( class_exists( 'Vance_Customize_HTML5_Color_Control' ) ) {
            $wp_customize->add_control( new Vance_Customize_HTML5_Color_Control( $wp_customize, "vance_post_overlay_{$cid}_color", $vance_ov_color_args ) );
        } else {
            $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_post_overlay_{$cid}_color", $vance_ov_color_args ) );
        }

        // Start opacity.
        $wp_customize->add_setting( "vance_post_overlay_{$cid}_opacity", array(
            'default'           => 1,
            'sanitize_callback' => 'vance_sanitize_float',
            'transport'         => 'postMessage',
        ) );
        $wp_customize->add_control( "vance_post_overlay_{$cid}_opacity", array(
            'label'       => sprintf( __( '%s: Start opacity (0.0 - 1.0)', 'vance-health-hub' ), $cname ),
            'section'     => 'vance_post_hero_overlay_cats',
            'type'        => 'range',
            'input_attrs' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ),
        ) );

        // Fade distance.
        $wp_customize->add_setting( "vance_post_overlay_{$cid}_spread", array(
            'default'           => 100,
            'sanitize_callback' => 'absint',
            'transport'         => 'postMessage',
        ) );
        $wp_customize->add_control( "vance_post_overlay_{$cid}_spread", array(
            'label'       => sprintf( __( '%s: Fade distance (%% of width)', 'vance-health-hub' ), $cname ),
            'section'     => 'vance_post_hero_overlay_cats',
            'type'        => 'range',
            'input_attrs' => array( 'min' => 10, 'max' => 100, 'step' => 5 ),
        ) );
    }

    $wp_customize->add_setting( 'vance_hero_subtitle_color', array(
        'default'           => '#cbd5e1',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_subtitle_color', array(
        'label'   => __( 'Hero Subtitle Color', 'vance-health-hub' ),
        'section' => 'vance_hero_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_hero_bg_color', array(
        'default'           => '#0A1929',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_bg_color', array(
        'label'       => __( 'Hero Background Color', 'vance-health-hub' ),
        'description' => __( 'Solid background color - visible when no image is set, or as image load fallback.', 'vance-health-hub' ),
        'section'     => 'vance_hero_settings',
    ) ) );

    // 2026-05-26: hero eyebrow + button colour controls. The eyebrow tag was
    // already wired in the render (vance_hero_tag_bg/color/border) but had no
    // Customizer UI — register the controls here so admins can edit them.
    // Buttons get full chrome control (text + bg + border, default + hover).
    $wp_customize->add_setting( 'vance_hero_tag_bg', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_tag_bg', array(
        'label'   => __( 'Hero Eyebrow - Background', 'vance-health-hub' ),
        'section' => 'vance_hero_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_hero_tag_color', array(
        'default'           => '#008080',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_tag_color', array(
        'label'   => __( 'Hero Eyebrow - Text Colour', 'vance-health-hub' ),
        'section' => 'vance_hero_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_hero_tag_border', array(
        'default'           => '#008080',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_tag_border', array(
        'label'   => __( 'Hero Eyebrow - Border Colour', 'vance-health-hub' ),
        'section' => 'vance_hero_settings',
    ) ) );

    // Button 1 (Practitioner — primary fill)
    $wp_customize->add_setting( 'vance_hero_btn1_text_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_btn1_text_color', array( 'label' => 'Button 1, Text Colour', 'section' => 'vance_hero_settings' ) ) );

    $wp_customize->add_setting( 'vance_hero_btn1_bg_color', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_btn1_bg_color', array( 'label' => 'Button 1, Background', 'section' => 'vance_hero_settings' ) ) );

    $wp_customize->add_setting( 'vance_hero_btn1_border_color', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_btn1_border_color', array( 'label' => 'Button 1, Border', 'section' => 'vance_hero_settings' ) ) );

    $wp_customize->add_setting( 'vance_hero_btn1_hover_text_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_btn1_hover_text_color', array( 'label' => 'Button 1, Text on Hover', 'section' => 'vance_hero_settings' ) ) );

    $wp_customize->add_setting( 'vance_hero_btn1_hover_bg_color', array( 'default' => '#006666', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_btn1_hover_bg_color', array( 'label' => 'Button 1, Background on Hover', 'section' => 'vance_hero_settings' ) ) );

    // Button 2 (Patient — outline)
    $wp_customize->add_setting( 'vance_hero_btn2_text_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_btn2_text_color', array( 'label' => 'Button 2, Text Colour', 'section' => 'vance_hero_settings' ) ) );

    $wp_customize->add_setting( 'vance_hero_btn2_bg_color', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_btn2_bg_color', array(
        'label'       => 'Button 2, Background',
        'description' => 'Blank = transparent (outline button look).',
        'section'     => 'vance_hero_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_hero_btn2_border_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_btn2_border_color', array( 'label' => 'Button 2, Border', 'section' => 'vance_hero_settings' ) ) );

    $wp_customize->add_setting( 'vance_hero_btn2_hover_text_color', array( 'default' => '#0A1929', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_btn2_hover_text_color', array( 'label' => 'Button 2, Text on Hover', 'section' => 'vance_hero_settings' ) ) );

    $wp_customize->add_setting( 'vance_hero_btn2_hover_bg_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_btn2_hover_bg_color', array( 'label' => 'Button 2, Background on Hover', 'section' => 'vance_hero_settings' ) ) );

    // -- Slide 1's own toggles ----------------------------------------------
    // The hero controls above ARE slide 1. These two are new keys with no
    // legacy equivalent. "Enable" defaults to ON so nothing changes on an
    // untouched site; switching it off with no other slide enabled omits the
    // hero section entirely.
    $wp_customize->add_setting( 'vance_hero_slide1_show', array( 'default' => true, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
    $wp_customize->add_control( 'vance_hero_slide1_show', array(
        'label'       => __( 'Enable Slide 1', 'vance-health-hub' ),
        'description' => __( 'The hero settings in this section are Slide 1. Untick to drop it from the hero.', 'vance-health-hub' ),
        'section'     => 'vance_hero_settings',
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_hero_slide1_hide_buttons', array( 'default' => false, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
    $wp_customize->add_control( 'vance_hero_slide1_hide_buttons', array(
        'label'       => __( 'Slide 1: hide both CTA buttons', 'vance-health-hub' ),
        'description' => __( 'Renders this slide as eyebrow + title + subtitle only.', 'vance-health-hub' ),
        'section'     => 'vance_hero_settings',
        'type'        => 'checkbox',
    ) );

    // -- Carousel behaviour (global, not per-slide) --------------------------
    // Only has any effect once a second slide is enabled below; with one slide
    // the hero renders as a plain static section with no carousel JS at all.
    $wp_customize->add_setting( 'vance_hero_autoplay_enable', array( 'default' => false, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
    $wp_customize->add_control( 'vance_hero_autoplay_enable', array(
        'label'       => __( 'Carousel: autoplay', 'vance-health-hub' ),
        'description' => __( 'Advance slides automatically. Pauses on hover and keyboard focus, and is disabled entirely for visitors who prefer reduced motion. Only applies when 2 or more slides are enabled.', 'vance-health-hub' ),
        'section'     => 'vance_hero_settings',
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_hero_autoplay_interval', array( 'default' => 6, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( 'vance_hero_autoplay_interval', array(
        'label'       => __( 'Carousel: seconds per slide', 'vance-health-hub' ),
        'section'     => 'vance_hero_settings',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 2, 'max' => 30, 'step' => 1 ),
    ) );

    // -- Hero Slides 2..N ----------------------------------------------------
    // Slide 1 IS the block of hero controls above — it reads the original
    // vance_hero_* keys and is always on, so an existing site's hero simply
    // becomes slide 1 with no migration. Slides 2+ get their own section each
    // and are off by default, so nothing changes until an admin adds one.
    //
    // WP has no repeater control, so this follows the same fixed-slot idiom as
    // the Content Widgets panel: register the field list once, loop it.
    $vance_hero_slide_fields = array(
        'image'                 => array( 'type' => 'image',    'label' => 'Background Image' ),
        'tag_label'             => array( 'type' => 'text',     'label' => 'Eyebrow / Tag Label' ),
        'tag_bg'                => array( 'type' => 'color',    'label' => 'Eyebrow - Background' ),
        'tag_color'             => array( 'type' => 'color',    'label' => 'Eyebrow - Text Colour' ),
        'tag_border'            => array( 'type' => 'color',    'label' => 'Eyebrow - Border Colour' ),
        'title'                 => array( 'type' => 'html',     'label' => 'Title', 'description' => 'Wrap words in &lt;span class="highlight"&gt;…&lt;/span&gt; to accent them.' ),
        'title_color'           => array( 'type' => 'color',    'label' => 'Title Colour' ),
        'title_size'            => array( 'type' => 'range',    'label' => 'Title Size (px)', 'attrs' => array( 'min' => 24, 'max' => 100, 'step' => 1 ) ),
        'subtitle'              => array( 'type' => 'textarea', 'label' => 'Subtitle' ),
        'subtitle_color'        => array( 'type' => 'color',    'label' => 'Subtitle Colour' ),
        'bg_color'              => array( 'type' => 'color',    'label' => 'Background Colour', 'description' => 'Visible when no image is set, and as image-load fallback.' ),
        'mask_toggle'           => array( 'type' => 'checkbox', 'label' => 'Enable Dark Overlay Mask' ),
        'mask_opacity_pct'      => array( 'type' => 'range',    'label' => 'Overlay Opacity (%)', 'attrs' => array( 'min' => 0, 'max' => 100, 'step' => 5 ) ),
        'btn1_text'             => array( 'type' => 'text',     'label' => 'Button 1, Text' ),
        'btn1_link'             => array( 'type' => 'text',     'label' => 'Button 1, Link', 'description' => 'A link containing "quiz" opens the quiz modal instead of navigating.' ),
        'btn1_text_color'       => array( 'type' => 'color',    'label' => 'Button 1, Text Colour' ),
        'btn1_bg_color'         => array( 'type' => 'color',    'label' => 'Button 1, Background' ),
        'btn1_border_color'     => array( 'type' => 'color',    'label' => 'Button 1, Border' ),
        'btn1_hover_text_color' => array( 'type' => 'color',    'label' => 'Button 1, Text on Hover' ),
        'btn1_hover_bg_color'   => array( 'type' => 'color',    'label' => 'Button 1, Background on Hover' ),
        'btn2_text'             => array( 'type' => 'text',     'label' => 'Button 2, Text' ),
        'btn2_link'             => array( 'type' => 'text',     'label' => 'Button 2, Link' ),
        'btn2_text_color'       => array( 'type' => 'color',    'label' => 'Button 2, Text Colour' ),
        'btn2_bg_color'         => array( 'type' => 'color',    'label' => 'Button 2, Background', 'description' => 'Blank = transparent (outline button look).' ),
        'btn2_border_color'     => array( 'type' => 'color',    'label' => 'Button 2, Border' ),
        'btn2_hover_text_color' => array( 'type' => 'color',    'label' => 'Button 2, Text on Hover' ),
        'btn2_hover_bg_color'   => array( 'type' => 'color',    'label' => 'Button 2, Background on Hover' ),
        'hide_buttons'          => array( 'type' => 'checkbox', 'label' => 'Hide both CTA buttons', 'description' => 'Renders this slide as eyebrow + title + subtitle only.' ),
    );
    $vance_hero_slide_defaults = vance_hero_slide_field_defaults();

    for ( $hs = 2; $hs <= VANCE_HERO_SLIDE_INSTANCES; $hs++ ) {
        $hs_section = 'vance_hero_slide' . $hs . '_settings';
        $hs_prefix  = 'vance_hero_slide' . $hs . '_';

        $wp_customize->add_section( $hs_section, array(
            /* translators: %d: slide number */
            'title'       => sprintf( __( 'Hero Slide %d', 'vance-health-hub' ), $hs ),
            'priority'    => 31 + ( $hs / 100 ),
            'panel'       => 'vance_homepage_panel',
            'description' => __( 'An additional hero slide. The hero only becomes a carousel once at least one of these is enabled; with none, it stays a single static hero.', 'vance-health-hub' ),
        ) );

        $wp_customize->add_setting( $hs_prefix . 'show', array( 'default' => false, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
        $wp_customize->add_control( $hs_prefix . 'show', array(
            /* translators: %d: slide number */
            'label'   => sprintf( __( 'Enable Slide %d', 'vance-health-hub' ), $hs ),
            'section' => $hs_section,
            'type'    => 'checkbox',
        ) );

        foreach ( $vance_hero_slide_fields as $hs_field => $hs_meta ) {
            $hs_id      = $hs_prefix . $hs_field;
            $hs_default = isset( $vance_hero_slide_defaults[ $hs_field ] ) ? $vance_hero_slide_defaults[ $hs_field ] : '';

            switch ( $hs_meta['type'] ) {
                case 'color':
                    $hs_sanitize = 'sanitize_hex_color';
                    break;
                case 'image':
                    $hs_sanitize = 'esc_url_raw';
                    break;
                case 'range':
                    $hs_sanitize = 'absint';
                    break;
                case 'checkbox':
                    $hs_sanitize = 'vance_sanitize_checkbox';
                    break;
                case 'textarea':
                    $hs_sanitize = 'sanitize_textarea_field';
                    break;
                case 'html':
                    // The renderer runs this through wp_kses_post(), and the
                    // title is documented to accept a highlight <span> — so
                    // sanitize to the same allow-list rather than stripping
                    // every tag on save.
                    $hs_sanitize = 'wp_kses_post';
                    break;
                default:
                    $hs_sanitize = 'sanitize_text_field';
            }

            $wp_customize->add_setting( $hs_id, array(
                'default'           => $hs_default,
                'sanitize_callback' => $hs_sanitize,
            ) );

            $hs_args = array(
                'label'   => $hs_meta['label'],
                'section' => $hs_section,
            );
            if ( isset( $hs_meta['description'] ) ) { $hs_args['description'] = $hs_meta['description']; }

            if ( $hs_meta['type'] === 'color' ) {
                $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $hs_id, $hs_args ) );
            } elseif ( $hs_meta['type'] === 'image' ) {
                $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $hs_id, $hs_args ) );
            } else {
                $hs_args['type'] = ( $hs_meta['type'] === 'html' ) ? 'text' : $hs_meta['type'];
                if ( isset( $hs_meta['attrs'] ) ) { $hs_args['input_attrs'] = $hs_meta['attrs']; }
                $wp_customize->add_control( $hs_id, $hs_args );
            }
        }
    }

    // 2.5 Discovery Suite Settings (Nested under Vance Theme Settings)
    $wp_customize->add_section( 'vance_discovery_general', array(
        'title'    => __( 'General', 'vance-health-hub' ),
        'priority' => 10,
        'panel'    => 'vance_discovery_panel',
    ) );

    /* Which facets the Discovery Suite offers.

       This used to be configured per-term: a show/label/order trio for every tag
       and category, with the tag options sourced from `reading-`, `path-` and
       `indication-` prefixes. No tag with those prefixes was ever created on this
       site, so that UI configured filters that could never match anything. Facets
       are now built directly from the terms that actually carry posts, which
       leaves only the question of which facets to offer. */
    $discovery_facets = array(
        'section'   => __( 'Show the Section filter (top-level categories)', 'vance-health-hub' ),
        'topic'     => __( 'Show the Topic filter (child categories)', 'vance-health-hub' ),
        'condition' => __( 'Show the Condition filter (GI Health conditions)', 'vance-health-hub' ),
        'audience'  => __( 'Show the "Written for" filter (patients / professionals)', 'vance-health-hub' ),
    );
    foreach ( $discovery_facets as $facet_key => $facet_label ) {
        $wp_customize->add_setting( "vance_discovery_show_{$facet_key}", array(
            'default'           => true,
            'sanitize_callback' => 'vance_sanitize_checkbox',
        ) );
        $wp_customize->add_control( "vance_discovery_show_{$facet_key}", array(
            'label'   => $facet_label,
            'section' => 'vance_discovery_general',
            'type'    => 'checkbox',
        ) );
    }

    // 2.6.7 Prime Block Home 1 — the original "Pathway Content" block.
    // The section ID and every setting key are deliberately unchanged: the
    // rename is cosmetic, so everything the admin has already saved carries
    // over with zero data loss.
    $wp_customize->add_section( 'vance_pathway_content_settings', array(
        'title'       => __( 'Prime Block Home 1', 'vance-health-hub' ),
        'priority'    => 31.7,
        'panel'       => 'vance_homepage_panel',
        'description' => __( 'Featured tool cards beside a Latest Content list. Showing/hiding and position are controlled by Homepage → Section Order — add or remove "Prime Block Home 1" there.', 'vance-health-hub' ),
    ) );

    $wp_customize->add_setting( 'vance_pwc_label', array( 'default' => 'Featured Tools', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_pwc_label', array( 'label' => 'Section Label', 'section' => 'vance_pathway_content_settings', 'type' => 'text' ) );

    // Layout - choose where the tools column sits relative to the Latest Content
    // list. Added 2026-05-26. Defaults to 'left' to match the historical behaviour
    // (3fr tools / 7fr news split). 'stacked' renders both as full-width rows.
    $wp_customize->add_setting( 'vance_pwc_layout', array(
        'default'           => 'left',
        'sanitize_callback' => 'sanitize_key',
    ) );
    $wp_customize->add_control( 'vance_pwc_layout', array(
        'label'       => __( 'Layout - Tools Position', 'vance-health-hub' ),
        'description' => __( 'Choose whether Featured Tools sit beside the Latest Content list, or stack above it.', 'vance-health-hub' ),
        'section'     => 'vance_pathway_content_settings',
        'type'        => 'select',
        'choices'     => vance_prime_block_layout_choices(),
    ) );

    // 2026-05-26: Banner-style selector + colour controls for the two PWC tool
    // cards. When style != 'card' the existing 2-card layout is replaced with
    // banners matching the Tool Widgets Row visual language.
    $wp_customize->add_setting( 'vance_pwc_style', array(
        'default'           => 'card',
        'sanitize_callback' => 'sanitize_key',
    ) );
    $wp_customize->add_control( 'vance_pwc_style', array(
        'label'       => __( 'Tool Card Style', 'vance-health-hub' ),
        'description' => __( 'Card = current paired tool tiles with image header. Image + Text = horizontal banner (icon left, content right). Image = image-led banner with overlay text. Pill = compact pill banner with CTA on the right.', 'vance-health-hub' ),
        'section'     => 'vance_pathway_content_settings',
        'type'        => 'select',
        'choices'     => vance_prime_block_style_choices(),
    ) );

    // Section-level label colour
    $wp_customize->add_setting( 'vance_pwc_section_label_color', array(
        'default'           => '#0f172a',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_pwc_section_label_color', array(
        'label'   => __( 'Section Label Colour ("Featured Tools" heading)', 'vance-health-hub' ),
        'section' => 'vance_pathway_content_settings',
    ) ) );

    // Card title + hover
    $wp_customize->add_setting( 'vance_pwc_card_title_color', array(
        'default'           => '#0A1929',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_pwc_card_title_color', array(
        'label'   => __( 'Card Title Colour', 'vance-health-hub' ),
        'section' => 'vance_pathway_content_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_pwc_card_title_hover_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_pwc_card_title_hover_color', array(
        'label'       => __( 'Card Title Colour (on hover)', 'vance-health-hub' ),
        'description' => __( 'Applied when the card is hovered, especially when Card Hover Colour darkens the background.', 'vance-health-hub' ),
        'section'     => 'vance_pathway_content_settings',
    ) ) );

    // Card description
    $wp_customize->add_setting( 'vance_pwc_card_desc_color', array(
        'default'           => '#64748b',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_pwc_card_desc_color', array(
        'label'   => __( 'Card Description Colour', 'vance-health-hub' ),
        'section' => 'vance_pathway_content_settings',
    ) ) );

    // Card eyebrow (e.g. "Find your starting point", "Personalised answers, 24/7")
    $wp_customize->add_setting( 'vance_pwc_card_eyebrow_color', array(
        'default'           => '#008080',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_pwc_card_eyebrow_color', array(
        'label'       => __( 'Card Eyebrow / Extra-text Colour', 'vance-health-hub' ),
        'description' => __( 'The small uppercase line under each card description (e.g. "Find your starting point").', 'vance-health-hub' ),
        'section'     => 'vance_pathway_content_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_pwc_section_bg', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_pwc_section_bg', array( 'label' => 'Section Background', 'section' => 'vance_pathway_content_settings' ) ) );

    // 2026-05-26: Background colour for the LEFT/TOOLS column only. Independent
    // of the overall Section Background. Blank = transparent (current default).
    // When set, the tools column gets vertical + horizontal padding so the
    // colour reads as a coloured block.
    $wp_customize->add_setting( 'vance_pwc_tools_column_bg', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_pwc_tools_column_bg', array(
        'label'       => __( 'Featured Tools Column Background', 'vance-health-hub' ),
        'description' => __( 'Background colour applied ONLY to the Featured Tools column (left side). Leave blank for transparent. The column auto-pads when a colour is set so the band is visible.', 'vance-health-hub' ),
        'section'     => 'vance_pathway_content_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_pwc_card_hover_color', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_pwc_card_hover_color', array( 'label' => 'Card Hover Colour', 'section' => 'vance_pathway_content_settings' ) ) );

    $wp_customize->add_setting( 'vance_pwc_icon_bg_color', array( 'default' => '#0A1929', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_pwc_icon_bg_color', array( 'label' => 'Image Placeholder Background', 'section' => 'vance_pathway_content_settings' ) ) );

    // Card 1: Healthcare Quiz
    $wp_customize->add_setting( 'vance_hquiz_tile_title', array( 'default' => 'Gastro Health Survey', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_hquiz_tile_title', array( 'label' => 'Healthcare Quiz, Title', 'section' => 'vance_pathway_content_settings', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_hquiz_tile_desc', array( 'default' => 'A 2-minute interactive quiz that points you to the most relevant tools, resources, and content for your situation.', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    $wp_customize->add_control( 'vance_hquiz_tile_desc', array( 'label' => 'Healthcare Quiz, Description', 'section' => 'vance_pathway_content_settings', 'type' => 'textarea' ) );

    $wp_customize->add_setting( 'vance_hquiz_tile_extra', array( 'default' => 'Find your starting point', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_hquiz_tile_extra', array( 'label' => 'Healthcare Quiz, Eyebrow / Extra text', 'section' => 'vance_pathway_content_settings', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_hquiz_tile_image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_hquiz_tile_image', array( 'label' => 'Healthcare Quiz, Image', 'section' => 'vance_pathway_content_settings' ) ) );

    $wp_customize->add_setting( 'vance_hquiz_tile_link', array( 'default' => '/gastro-health-survey/', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_hquiz_tile_link', array( 'label' => 'Healthcare Quiz, Link', 'section' => 'vance_pathway_content_settings', 'type' => 'text' ) );

    // Card 2: Ask AI
    $wp_customize->add_setting( 'vance_askai_tile_title', array( 'default' => 'VANCE-Ai', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_askai_tile_title', array( 'label' => 'VANCE-Ai, Title', 'section' => 'vance_pathway_content_settings', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_askai_tile_desc', array( 'default' => 'Ask any health question and get an evidence-backed answer in seconds. Powered by curated clinical content, available 24/7.', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    $wp_customize->add_control( 'vance_askai_tile_desc', array( 'label' => 'VANCE-Ai, Description', 'section' => 'vance_pathway_content_settings', 'type' => 'textarea' ) );

    $wp_customize->add_setting( 'vance_askai_tile_extra', array( 'default' => 'Personalised answers, 24/7', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_askai_tile_extra', array( 'label' => 'VANCE-Ai, Eyebrow / Extra text', 'section' => 'vance_pathway_content_settings', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_askai_tile_image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_askai_tile_image', array( 'label' => 'VANCE-Ai, Image', 'section' => 'vance_pathway_content_settings' ) ) );

    $wp_customize->add_setting( 'vance_askai_tile_link', array( 'default' => '/ask-ai/', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_askai_tile_link', array( 'label' => 'VANCE-Ai, Link', 'section' => 'vance_pathway_content_settings', 'type' => 'text' ) );

    // Pathway Content — Latest Content (right column)
    $wp_customize->add_setting( 'vance_pwc_latest_title', array( 'default' => 'LATEST CONTENT', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_pwc_latest_title', array( 'label' => 'Right Column, Section Label', 'section' => 'vance_pathway_content_settings', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_pwc_latest_count', array( 'default' => 6, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( 'vance_pwc_latest_count', array( 'label' => 'Right Column, Number of Posts', 'description' => 'Bento layout shows 1 featured + the rest as side cards (6 = featured + 5).', 'section' => 'vance_pathway_content_settings', 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 7, 'step' => 1 ) ) );

    $wp_customize->add_setting( 'vance_pwc_latest_category', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $cats = get_categories( array( 'hide_empty' => false ) );
    $cat_choices = array( 0 => 'All Categories' );
    foreach ( $cats as $c ) { $cat_choices[ $c->term_id ] = $c->name; }
    $wp_customize->add_control( 'vance_pwc_latest_category', array( 'label' => 'Right Column, Category Filter', 'section' => 'vance_pathway_content_settings', 'type' => 'select', 'choices' => $cat_choices ) );

    $wp_customize->add_setting( 'vance_pwc_latest_show_date', array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    $wp_customize->add_control( 'vance_pwc_latest_show_date', array( 'label' => 'Right Column, Show Post Date', 'section' => 'vance_pathway_content_settings', 'type' => 'checkbox' ) );

    // Parity with the shared registrar (Home 2 / Categories). Home 1 keeps its
    // legacy vance_pwc_* keys, so these are declared by hand rather than
    // generated. Both default ON: this instance is the live homepage block and
    // nothing about it should move.
    $wp_customize->add_setting( 'vance_pwc_latest_show_thumbs', array( 'default' => true, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
    $wp_customize->add_control( 'vance_pwc_latest_show_thumbs', array(
        'label'       => __( 'Right Column, Show Article Thumbnails', 'vance-health-hub' ),
        'description' => __( 'The small square image on each row of the article list. The large featured article keeps its image either way.', 'vance-health-hub' ),
        'section'     => 'vance_pathway_content_settings',
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_pwc_accent_bar_show', array( 'default' => true, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
    $wp_customize->add_control( 'vance_pwc_accent_bar_show', array(
        'label'       => __( 'Show heading accent bar', 'vance-health-hub' ),
        'description' => __( 'The short vertical rule to the left of the "Featured Tools" and "Latest Content" headings.', 'vance-health-hub' ),
        'section'     => 'vance_pathway_content_settings',
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_pwc_accent_bar_color', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_pwc_accent_bar_color', array(
        'label'   => __( 'Heading Accent Bar Colour', 'vance-health-hub' ),
        'section' => 'vance_pathway_content_settings',
    ) ) );
    // 2.6.8 Prime Block Home 2 + Prime Block Categories.
    // Both use the clean vance_pb2_* / vance_pbc_* prefixes (Home 1 stays
    // pinned to the legacy vance_pwc_* / vance_hquiz_* / vance_askai_* keys so
    // its saved values survive), so one registration helper serves both.
    vance_register_prime_block_controls( $wp_customize, 'vance_prime_block_home2_settings', 'vance_pb2_', __( 'Prime Block Home 2', 'vance-health-hub' ), 31.72, __( 'A second, independently-configured Prime Block. Showing/hiding and position are controlled by Homepage → Section Order — add or remove "Prime Block Home 2" there.', 'vance-health-hub' ) );

    vance_register_prime_block_controls(
        $wp_customize,
        'vance_prime_block_categories_settings',
        'vance_pbc_',
        __( 'Prime Block Categories', 'vance-health-hub' ),
        31.74,
        __( 'One Prime Block, shown on the category archives you tick below. Off until you tick the master switch. Choose where it sits with "Position on the page"; every category is on by default, so untick the ones you want to leave it off.', 'vance-health-hub' ),
        true,
        vance_prime_block_categories_defaults()
    );

    vance_register_prime_block_controls(
        $wp_customize,
        'vance_prime_block_kb_settings',
        'vance_pbk_',
        __( 'Prime Block Knowledgebase', 'vance-health-hub' ),
        31.75,
        __( 'One Prime Block for the Knowledgebase page. Off until you tick the switch below; choose where it sits with "Position on the page".', 'vance-health-hub' ),
        'kb_page'
    );

    // 2.6.85 Promo Block Knowledgebase — an independent copy of the homepage
    // Promo Block for /knowledgebase/. Same controls, own vance_kbpromo_* keys,
    // and the same renderer (inc/promo-block.php), so the two cannot drift.
    $wp_customize->add_section( 'vance_kb_promo_block', array(
        'title'       => __( 'Promo Block Knowledgebase', 'vance-health-hub' ),
        'priority'    => 31.755,
        'panel'       => 'vance_homepage_panel',
        'description' => __( 'The promo card on the Knowledgebase page. Off until you tick the switch below.', 'vance-health-hub' ),
    ) );
    vance_register_promo_block_controls(
        $wp_customize,
        'vance_kb_promo_block',
        vance_promo_keys_prefixed( 'vance_kbpromo_' ),
        array(
            'show_label' => __( 'Show on the Knowledgebase page', 'vance-health-hub' ),
            'placement'  => 'kb_page',
            'defaults'   => vance_promo_prefixed_defaults(),
        )
    );

    // 2.6.9 Gastro Conditions — one big animated tile per GI condition plus a
    // "view all" tile. The condition list itself comes from
    // vance_gi_condition_cards(); only presentation is configurable here.
    $wp_customize->add_section( 'vance_gastro_conditions_settings', array(
        'title'       => __( 'Gastro Conditions', 'vance-health-hub' ),
        'priority'    => 31.76,
        'panel'       => 'vance_homepage_panel',
        'description' => __( 'Big linked tiles for each GI condition. Add "Gastro Conditions" to Homepage → Section Order to show it.', 'vance-health-hub' ),
    ) );

    $wp_customize->add_setting( 'vance_gc_heading', array( 'default' => 'Gastro Conditions', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_gc_heading', array( 'label' => 'Heading', 'section' => 'vance_gastro_conditions_settings', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_gc_subtitle', array( 'default' => 'Learn about the condition that matters to you', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_gc_subtitle', array( 'label' => 'Subtitle', 'section' => 'vance_gastro_conditions_settings', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_gc_heading_align', array( 'default' => 'center', 'sanitize_callback' => 'vance_sanitize_text_align' ) );
    $wp_customize->add_control( 'vance_gc_heading_align', array(
        'label'   => 'Heading Alignment',
        'section' => 'vance_gastro_conditions_settings',
        'type'    => 'select',
        'choices' => array( 'left' => 'Left', 'center' => 'Centre', 'right' => 'Right', 'justify' => 'Justified' ),
    ) );

    $wp_customize->add_setting( 'vance_gc_subtitle_align', array( 'default' => 'center', 'sanitize_callback' => 'vance_sanitize_text_align' ) );
    $wp_customize->add_control( 'vance_gc_subtitle_align', array(
        'label'       => 'Subtitle Alignment',
        'description' => 'Set independently of the heading. "Justified" only shows on a subtitle long enough to wrap.',
        'section'     => 'vance_gastro_conditions_settings',
        'type'        => 'select',
        'choices'     => array( 'left' => 'Left', 'center' => 'Centre', 'right' => 'Right', 'justify' => 'Justified' ),
    ) );

    $wp_customize->add_setting( 'vance_gc_section_bg', array( 'default' => '#f8fafc', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_gc_section_bg', array( 'label' => 'Section Background Colour', 'section' => 'vance_gastro_conditions_settings' ) ) );

    $wp_customize->add_setting( 'vance_gc_per_row', array( 'default' => 4, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( 'vance_gc_per_row', array(
        'label'       => 'Tiles Per Row',
        'description' => 'There are 8 tiles in total (7 conditions + "view all"), so 4 gives two even rows.',
        'section'     => 'vance_gastro_conditions_settings',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 1, 'max' => 6, 'step' => 1 ),
    ) );

    $wp_customize->add_setting( 'vance_gc_view_all_bg_color', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_gc_view_all_bg_color', array( 'label' => '"View all" Tile Background Colour', 'section' => 'vance_gastro_conditions_settings' ) ) );

    $wp_customize->add_setting( 'vance_gc_view_all_text', array( 'default' => 'VIEW ALL GASTRO CONDITIONS', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_gc_view_all_text', array( 'label' => '"View all" Tile Text', 'section' => 'vance_gastro_conditions_settings', 'type' => 'text' ) );

    // 2.7 Knowledgebase Mini-Hero Section
    $wp_customize->add_section( 'vance_kb_mini_hero', array(
        'title'    => __( 'Knowledge Base Mini-Hero', 'vance-health-hub' ),
        'priority' => 31.7,
        'panel'    => 'vance_content_panel',
    ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_title', array( 'default' => 'IBD Research KNOWLEDGEBASE', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_title', array( 'label' => 'Main Title Text', 'section' => 'vance_kb_mini_hero', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_subtitle', array( 'default' => 'Catch Up on the Latest Articles and More...', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_subtitle', array( 'label' => 'Subtitle Text', 'section' => 'vance_kb_mini_hero', 'type' => 'textarea' ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_bg', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_kb_mini_hero_bg', array(
        'label'       => 'Background Image',
        'description' => __( 'Optional. Leave blank to use the Background Color below instead.', 'vance-health-hub' ),
        'section'     => 'vance_kb_mini_hero',
    ) ) );

    // Solid background colour used when no Background Image is set. When an image
    // IS set, this colour acts as the fallback underneath the dark overlay.
    // Added 2026-05-26.
    $wp_customize->add_setting( 'vance_kb_mini_hero_bg_color', array(
        'default'           => '#0A1929',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_kb_mini_hero_bg_color', array(
        'label'       => __( 'Background Color', 'vance-health-hub' ),
        'description' => __( 'Used when no Background Image is selected. The dark overlay gradient is automatically dropped so this colour renders cleanly.', 'vance-health-hub' ),
        'section'     => 'vance_kb_mini_hero',
    ) ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_font_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_kb_mini_hero_font_color', array( 'label' => 'Font Color', 'section' => 'vance_kb_mini_hero' ) ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_height', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_height', array( 'label' => 'Min-Height (px)', 'section' => 'vance_kb_mini_hero', 'type' => 'number' ) );
    
    $wp_customize->add_setting( 'vance_kb_mini_hero_padding', array( 'default' => '60px 0 80px', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_padding', array( 'label' => 'Padding (e.g. 60px 0 80px)', 'section' => 'vance_kb_mini_hero', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_opacity', array( 'default' => 80, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_opacity', array( 'label' => 'Overlay Opacity (%)', 'section' => 'vance_kb_mini_hero', 'type' => 'range', 'input_attrs' => array( 'min' => 0, 'max' => 100, 'step' => 5 ) ) );

    $wp_customize->add_setting( 'vance_kb_wrapper_bg', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_kb_wrapper_bg', array(
        'label'       => __( 'Knowledge Base Section Background Color', 'vance-health-hub' ),
        'description' => __( 'Background color for the KB content sections below the mini-hero.', 'vance-health-hub' ),
        'section'     => 'vance_kb_mini_hero',
    ) ) );

    // --- Mini-Hero header copy: eyebrow + title + subtitle + alignment + divider ---
    // Extends the existing title / subtitle with eyebrow chip + per-field colour
    // and size controls, alignment selector, optional card background, divider.
    // All controls have safe defaults so existing sites keep their look.
    // Added 2026-05-26.

    // Eyebrow
    $wp_customize->add_setting( 'vance_kb_mini_hero_show_eyebrow', array(
        'default'           => true,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_show_eyebrow', array(
        'label'       => __( 'Show Eyebrow Chip', 'vance-health-hub' ),
        'description' => __( 'Small uppercase tag rendered above the title.', 'vance-health-hub' ),
        'section'     => 'vance_kb_mini_hero',
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_eyebrow', array(
        'default'           => 'KNOWLEDGE LIBRARY',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_eyebrow', array(
        'label'   => __( 'Eyebrow Text', 'vance-health-hub' ),
        'section' => 'vance_kb_mini_hero',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_eyebrow_size', array(
        'default'           => 12,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_eyebrow_size', array(
        'label'       => __( 'Eyebrow - Font Size (px)', 'vance-health-hub' ),
        'section'     => 'vance_kb_mini_hero',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 8, 'max' => 24, 'step' => 1 ),
    ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_eyebrow_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_kb_mini_hero_eyebrow_color', array(
        'label'   => __( 'Eyebrow - Text Colour', 'vance-health-hub' ),
        'section' => 'vance_kb_mini_hero',
    ) ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_eyebrow_bg', array(
        'default'           => 'rgba(255,255,255,0.10)',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_eyebrow_bg', array(
        'label'       => __( 'Eyebrow - Chip Background (hex or rgba)', 'vance-health-hub' ),
        'description' => __( 'Use rgba for translucent over the hero image.', 'vance-health-hub' ),
        'section'     => 'vance_kb_mini_hero',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_eyebrow_border', array(
        'default'           => 'rgba(255,255,255,0.20)',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_eyebrow_border', array(
        'label'       => __( 'Eyebrow - Border Colour (hex or rgba)', 'vance-health-hub' ),
        'description' => __( 'Set to "transparent" to remove the border.', 'vance-health-hub' ),
        'section'     => 'vance_kb_mini_hero',
        'type'        => 'text',
    ) );

    // Title
    $wp_customize->add_setting( 'vance_kb_mini_hero_title_size', array(
        'default'           => 38,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_title_size', array(
        'label'       => __( 'Title - Font Size (px)', 'vance-health-hub' ),
        'section'     => 'vance_kb_mini_hero',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 20, 'max' => 96, 'step' => 1 ),
    ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_title_color', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_kb_mini_hero_title_color', array(
        'label'       => __( 'Title - Text Colour', 'vance-health-hub' ),
        'description' => __( 'Blank = inherit from generic Font Color above.', 'vance-health-hub' ),
        'section'     => 'vance_kb_mini_hero',
    ) ) );

    // Subtitle
    $wp_customize->add_setting( 'vance_kb_mini_hero_subtitle_size', array(
        'default'           => 18,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_subtitle_size', array(
        'label'       => __( 'Subtitle - Font Size (px)', 'vance-health-hub' ),
        'section'     => 'vance_kb_mini_hero',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 12, 'max' => 32, 'step' => 1 ),
    ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_subtitle_color', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_kb_mini_hero_subtitle_color', array(
        'label'       => __( 'Subtitle - Text Colour', 'vance-health-hub' ),
        'description' => __( 'Blank = inherit from generic Font Color above.', 'vance-health-hub' ),
        'section'     => 'vance_kb_mini_hero',
    ) ) );

    // Alignment + header background + divider
    $wp_customize->add_setting( 'vance_kb_mini_hero_align', array(
        'default'           => 'center',
        'sanitize_callback' => 'sanitize_key',
    ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_align', array(
        'label'   => __( 'Header Alignment', 'vance-health-hub' ),
        'section' => 'vance_kb_mini_hero',
        'type'    => 'select',
        'choices' => array(
            'left'   => __( 'Left',   'vance-health-hub' ),
            'center' => __( 'Center', 'vance-health-hub' ),
            'right'  => __( 'Right',  'vance-health-hub' ),
        ),
    ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_header_bg', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_header_bg', array(
        'label'       => __( 'Header Block - Background (hex or rgba)', 'vance-health-hub' ),
        'description' => __( 'Optional background card behind eyebrow + title + subtitle. Blank = transparent.', 'vance-health-hub' ),
        'section'     => 'vance_kb_mini_hero',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_show_divider', array(
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_show_divider', array(
        'label'   => __( 'Show Divider Under Subtitle', 'vance-health-hub' ),
        'section' => 'vance_kb_mini_hero',
        'type'    => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_divider_color', array(
        'default'           => 'rgba(255,255,255,0.25)',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_divider_color', array(
        'label'   => __( 'Divider - Colour (hex or rgba)', 'vance-health-hub' ),
        'section' => 'vance_kb_mini_hero',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_divider_width', array(
        'default'           => 2,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_divider_width', array(
        'label'       => __( 'Divider - Thickness (px)', 'vance-health-hub' ),
        'section'     => 'vance_kb_mini_hero',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 1, 'max' => 8, 'step' => 1 ),
    ) );

    // 2.72 Knowledge Base Content (category blocks). Split from the Mini-Hero
    // so admins can place other blocks between the KB hero and category list.
    // Added 2026-05-26.
    $wp_customize->add_section( 'vance_kb_content', array(
        'title'       => __( 'Knowledge Base Content', 'vance-health-hub' ),
        'priority'    => 31.72,
        'panel'       => 'vance_content_panel',
        'description' => __( 'Standalone homepage section that renders the category content blocks below the Mini-Hero. Enable it from Section Order.', 'vance-health-hub' ),
    ) );

    $wp_customize->add_setting( 'vance_kb_content_bg', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_kb_content_bg', array(
        'label'       => __( 'Background Colour', 'vance-health-hub' ),
        'description' => __( 'Falls back to Knowledge Base Section Background Color from the Mini-Hero panel if left blank.', 'vance-health-hub' ),
        'section'     => 'vance_kb_content',
    ) ) );

    $wp_customize->add_setting( 'vance_kb_content_pad_top', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_kb_content_pad_top', array(
        'label'       => __( 'Padding Top (px)', 'vance-health-hub' ),
        'section'     => 'vance_kb_content',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 200, 'step' => 4 ),
    ) );

    $wp_customize->add_setting( 'vance_kb_content_pad_bottom', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_kb_content_pad_bottom', array(
        'label'       => __( 'Padding Bottom (px)', 'vance-health-hub' ),
        'section'     => 'vance_kb_content',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 200, 'step' => 4 ),
    ) );

    // 2.75 Tool Widgets Row (merged banners). Added 2026-05-26.
    $wp_customize->add_section( 'vance_tool_widgets_row', array(
        'title'       => __( 'Tool Widgets Row', 'vance-health-hub' ),
        'priority'    => 31.75,
        'panel'       => 'vance_content_panel',
        'description' => __( 'Single homepage row that houses both the Content Filters and Vance AI tool banners. Pick a banner style and customise each card.', 'vance-health-hub' ),
    ) );

    // Style select
    $wp_customize->add_setting( 'vance_twrow_style', array(
        'default'           => 'horizontal',
        'sanitize_callback' => 'sanitize_key',
    ) );
    $wp_customize->add_control( 'vance_twrow_style', array(
        'label'       => __( 'Banner Style', 'vance-health-hub' ),
        'description' => __( 'Horizontal: icon + content + CTA, gradient background. Image: hero image with dark overlay. Pill: compact single-line banner.', 'vance-health-hub' ),
        'section'     => 'vance_tool_widgets_row',
        'type'        => 'select',
        'choices'     => array(
            'horizontal' => __( 'Horizontal Banner (gradient)', 'vance-health-hub' ),
            'image'      => __( 'Image-led Banner', 'vance-health-hub' ),
            'pill'       => __( 'Minimal Pill Banner', 'vance-health-hub' ),
        ),
    ) );

    // Section chrome
    $wp_customize->add_setting( 'vance_twrow_section_bg', array(
        'default'           => '#F8FAFC',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_twrow_section_bg', array(
        'label'   => __( 'Section Background', 'vance-health-hub' ),
        'section' => 'vance_tool_widgets_row',
    ) ) );

    $wp_customize->add_setting( 'vance_twrow_pad_top', array(
        'default'           => 60,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_twrow_pad_top', array(
        'label'       => __( 'Section Padding Top (px)', 'vance-health-hub' ),
        'section'     => 'vance_tool_widgets_row',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 200, 'step' => 4 ),
    ) );

    $wp_customize->add_setting( 'vance_twrow_pad_bottom', array(
        'default'           => 60,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_twrow_pad_bottom', array(
        'label'       => __( 'Section Padding Bottom (px)', 'vance-health-hub' ),
        'section'     => 'vance_tool_widgets_row',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 200, 'step' => 4 ),
    ) );

    $wp_customize->add_setting( 'vance_twrow_show_heading', array(
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ) );
    $wp_customize->add_control( 'vance_twrow_show_heading', array(
        'label'   => __( 'Show Section Heading', 'vance-health-hub' ),
        'section' => 'vance_tool_widgets_row',
        'type'    => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_twrow_heading', array(
        'default'           => 'Quick Tools',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_twrow_heading', array(
        'label'   => __( 'Section Heading Text', 'vance-health-hub' ),
        'section' => 'vance_tool_widgets_row',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_twrow_heading_color', array(
        'default'           => '#0A1929',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_twrow_heading_color', array(
        'label'   => __( 'Section Heading Colour', 'vance-health-hub' ),
        'section' => 'vance_tool_widgets_row',
    ) ) );

    // Per-card controls (1 = Content Filters, 2 = Vance AI).
    foreach ( array(
        1 => array( 'label' => 'Card 1 (Content Filters)', 'eyebrow_default' => 'Filter content', 'fallback_prefix' => 'vance_tw_content_filters_' ),
        2 => array( 'label' => 'Card 2 (VANCE-Ai)',        'eyebrow_default' => 'AI assistant',   'fallback_prefix' => 'vance_tw_vance_ai_'        ),
    ) as $n => $meta ) {
        $prefix = 'vance_twrow_card' . $n . '_';

        $wp_customize->add_setting( $prefix . 'eyebrow', array( 'default' => $meta['eyebrow_default'], 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . 'eyebrow', array( 'label' => $meta['label'] . ' - Eyebrow', 'section' => 'vance_tool_widgets_row', 'type' => 'text' ) );

        $wp_customize->add_setting( $prefix . 'title', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . 'title', array( 'label' => $meta['label'] . ' - Title (blank = inherit from legacy widget)', 'section' => 'vance_tool_widgets_row', 'type' => 'text' ) );

        $wp_customize->add_setting( $prefix . 'desc', array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
        $wp_customize->add_control( $prefix . 'desc', array( 'label' => $meta['label'] . ' - Description (blank = inherit)', 'section' => 'vance_tool_widgets_row', 'type' => 'textarea' ) );

        $wp_customize->add_setting( $prefix . 'cta', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . 'cta', array( 'label' => $meta['label'] . ' - CTA text (blank = inherit)', 'section' => 'vance_tool_widgets_row', 'type' => 'text' ) );

        $wp_customize->add_setting( $prefix . 'accent', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'accent', array( 'label' => $meta['label'] . ' - Accent Colour', 'section' => 'vance_tool_widgets_row' ) ) );

        $wp_customize->add_setting( $prefix . 'bg_start', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'bg_start', array( 'label' => $meta['label'] . ' - Gradient Start (horizontal style)', 'section' => 'vance_tool_widgets_row' ) ) );

        $wp_customize->add_setting( $prefix . 'bg_end', array( 'default' => '#0A1929', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'bg_end', array( 'label' => $meta['label'] . ' - Gradient End (horizontal style)', 'section' => 'vance_tool_widgets_row' ) ) );

        $wp_customize->add_setting( $prefix . 'image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $prefix . 'image', array( 'label' => $meta['label'] . ' - Background Image (image style only)', 'section' => 'vance_tool_widgets_row' ) ) );
    }

    // 2.8 Join the Hub Section
    $wp_customize->add_section( 'vance_join_community', array(
        'title'    => __( 'Join the Hub Block', 'vance-health-hub' ),
        'priority' => 31.8,
        'panel'    => 'vance_content_panel',
    ) );

    $wp_customize->add_setting( 'vance_join_title', array(
        'default'           => 'Join the Hub',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_join_title', array(
        'label'   => __( 'Main Title', 'vance-health-hub' ),
        'section' => 'vance_join_community',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_join_subtitle', array(
        'default'           => 'Select your role to get started with a personalized experience.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'vance_join_subtitle', array(
        'label'   => __( 'Subtitle', 'vance-health-hub' ),
        'section' => 'vance_join_community',
        'type'    => 'textarea',
    ) );

    $wp_customize->add_setting( 'vance_join_practitioner_label', array(
        'default'           => "I'm a Healthcare Practitioner",
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_join_practitioner_label', array(
        'label'   => __( 'Practitioner Checkbox Label', 'vance-health-hub' ),
        'section' => 'vance_join_community',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_join_patient_label', array(
        'default'           => "I'm a Patient / Caregiver",
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_join_patient_label', array(
        'label'   => __( 'Patient Checkbox Label', 'vance-health-hub' ),
        'section' => 'vance_join_community',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_join_button_text', array(
        'default'           => 'REGISTER NOW',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_join_button_text', array(
        'label'   => __( 'Button Text', 'vance-health-hub' ),
        'section' => 'vance_join_community',
        'type'    => 'text',
    ) );


    // 2.9 Ask AI Settings
    $wp_customize->add_section( 'vance_askai_settings', array(
        'title'    => __( 'VANCE-Ai Configuration', 'vance-health-hub' ),
        'priority' => 31.9,
        'panel'    => 'vance_content_panel',
    ) );

    // Answer mode
    // Opening-turn behaviour. Applies to both answer modes; the rule is
    // appended to whichever system prompt is in play. A toggle rather than a
    // hard-coded behaviour so it can be switched off without a deploy if it
    // turns out to get in readers' way.
    $wp_customize->add_setting( 'vance_askai_clarify_first', array(
        'default'           => true,
        'sanitize_callback' => 'vance_sanitize_checkbox',
    ) );
    $wp_customize->add_control( 'vance_askai_clarify_first', array(
        'label'       => __( 'Ask 2 clarifying questions first', 'vance-health-hub' ),
        'description' => __( 'On the first question of a conversation, VANCE-Ai replies with two short clarifying questions instead of answering straight away. Follow-up turns answer normally.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'checkbox',
        'priority'    => 2,
    ) );

    $wp_customize->add_setting( 'vance_askai_mode', array(
        'default'           => 'grounded',
        'sanitize_callback' => 'sanitize_key',
    ) );
    $wp_customize->add_control( 'vance_askai_mode', array(
        'label'       => __( 'Answer mode', 'vance-health-hub' ),
        'description' => __( 'Grounded: VANCE-Ai answers only from this hub\'s own library (default, safest). Open: VANCE-Ai draws on its full general knowledge, constrained by the guardrails below.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'select',
        'priority'    => 1,
        'choices'     => array(
            'grounded' => __( 'Grounded — hub content only (default)', 'vance-health-hub' ),
            'open'     => __( 'Open — full AI knowledge with guardrails', 'vance-health-hub' ),
        ),
    ) );

    // Hero Settings
    $wp_customize->add_setting( 'vance_askai_hero_bg', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_askai_hero_bg', array(
        'label'   => __( 'Hero Background Image', 'vance-health-hub' ),
        'section' => 'vance_askai_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_askai_hero_title', array(
        'default'           => 'VANCE-Ai',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_hero_title', array(
        'label'   => __( 'Hero Title', 'vance-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_askai_hero_subtitle', array(
        'default'           => 'Ask complex clinical questions and get evidence-based answers instantly.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'vance_askai_hero_subtitle', array(
        'label'   => __( 'Hero Subtitle', 'vance-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'textarea',
    ) );

    $wp_customize->add_setting( 'vance_askai_hero_badge', array(
        'default'           => 'Beta Feature v1.0',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_hero_badge', array(
        'label'   => __( 'Hero Badge Text', 'vance-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'text',
    ) );

    // API Credentials
    $wp_customize->add_setting( 'vance_askai_api_key', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_api_key', array(
        'label'       => __( 'AI API Key', 'vance-health-hub' ),
        'description' => __( 'Enter your OpenAI or Anthropic API key', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_askai_api_provider', array(
        'default'           => 'openai',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_api_provider', array(
        'label'   => __( 'AI Provider', 'vance-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'select',
        'choices' => array(
            'openai'     => 'OpenAI (GPT-4)',
            'anthropic'  => 'Anthropic (Claude)',
            'google'     => 'Google (Gemini)',
        ),
    ) );

    $wp_customize->add_setting( 'vance_askai_model', array(
        'default'           => 'anthropic/claude-opus-4.8',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    // Build the model choices live from OpenRouter (cached). Always keep the
    // currently-saved value selectable even if the live list omits it.
    $vance_model_choices = vance_get_openrouter_models();
    $vance_current_model = get_theme_mod( 'vance_askai_model', '' );
    if ( $vance_current_model && ! isset( $vance_model_choices[ $vance_current_model ] ) ) {
        $vance_model_choices = array( $vance_current_model => $vance_current_model . ' (saved)' ) + $vance_model_choices;
    }
    $wp_customize->add_control( 'vance_askai_model', array(
        'label'       => __( 'AI Model', 'vance-health-hub' ),
        'description' => __( 'Pulled live from OpenRouter. Pick the model the VANCE-Ai chat should use. The list refreshes every 12 hours.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'select',
        'choices'     => $vance_model_choices,
    ) );

    // Open-mode guardrails (only shown when Answer mode = Open)
    $vance_open_guardrail_defaults = vance_ai_open_mode_guardrail_defaults();

    $wp_customize->add_setting( 'vance_askai_guardrail_claims', array(
        'default'           => $vance_open_guardrail_defaults['claims'],
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'vance_askai_guardrail_claims', array(
        'label'           => __( 'Open mode: supplement/FSMP claims guardrail', 'vance-health-hub' ),
        'description'     => __( 'UK/EU food-supplement and FSMP regulatory boundary. Edit the wording to match current legal/compliance guidance without a code change.', 'vance-health-hub' ),
        'section'         => 'vance_askai_settings',
        'type'            => 'textarea',
        'active_callback' => 'vance_askai_open_mode_control_active',
    ) );

    $wp_customize->add_setting( 'vance_askai_guardrail_sources', array(
        'default'           => $vance_open_guardrail_defaults['sources'],
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'vance_askai_guardrail_sources', array(
        'label'           => __( 'Open mode: UK guidance hierarchy guardrail', 'vance-health-hub' ),
        'description'     => __( 'Tier 1 (NHS/NICE/BSG) vs tier 2 (Crohn\'s & Colitis UK, Guts UK) source weighting.', 'vance-health-hub' ),
        'section'         => 'vance_askai_settings',
        'type'            => 'textarea',
        'active_callback' => 'vance_askai_open_mode_control_active',
    ) );

    $wp_customize->add_setting( 'vance_askai_guardrail_ontopic', array(
        'default'           => $vance_open_guardrail_defaults['ontopic'],
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'vance_askai_guardrail_ontopic', array(
        'label'           => __( 'Open mode: stay-on-topic guardrail', 'vance-health-hub' ),
        'section'         => 'vance_askai_settings',
        'type'            => 'textarea',
        'active_callback' => 'vance_askai_open_mode_control_active',
    ) );

    // Knowledge base grounding
    $wp_customize->add_setting( 'vance_askai_max_sources', array(
        'default'           => 5,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_askai_max_sources', array(
        'label'       => __( 'Articles per answer', 'vance-health-hub' ),
        'description' => __( 'How many hub articles are retrieved and given to the AI as source material for each question. Higher means broader answers but a slower, more expensive request.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 3, 'max' => 8, 'step' => 1 ),
    ) );

    $wp_customize->add_setting( 'vance_askai_highlight_enable', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );
    $wp_customize->add_control( 'vance_askai_highlight_enable', array(
        'label'       => __( 'Highlight-to-ask', 'vance-health-hub' ),
        'description' => __( 'Show an "Ask VANCE-Ai" button when a reader selects a word, phrase or passage in an article, opening the chat pre-filled with it.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_popup', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_popup', array(
        'label'       => __( 'Article intro popup', 'vance-health-hub' ),
        'description' => __( 'Show a short popup on articles explaining how VANCE-Ai can help the reader understand what they are about to read.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_frequency', array(
        'default'           => 'monthly',
        'sanitize_callback' => 'sanitize_key',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_frequency', array(
        'label'       => __( 'Popup: how often', 'vance-health-hub' ),
        'description' => __( 'How often one visitor may see the popup. Counted per browser.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'select',
        'choices'     => array(
            'x_per_hour' => __( 'A set number of times per hour', 'vance-health-hub' ),
            'hourly'     => __( 'Once per hour', 'vance-health-hub' ),
            'x_per_day'  => __( 'A set number of times per day', 'vance-health-hub' ),
            'daily'      => __( 'Once per day', 'vance-health-hub' ),
            'weekly'     => __( 'Once per week', 'vance-health-hub' ),
            'monthly'    => __( 'Once per month', 'vance-health-hub' ),
        ),
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_per_hour', array(
        'default'           => 2,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_per_hour', array(
        'label'       => __( 'Popup: times per hour', 'vance-health-hub' ),
        'description' => __( 'Only used when "How often" is set to a number of times per hour.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 1, 'max' => 60, 'step' => 1 ),
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_per_day', array(
        'default'           => 2,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_per_day', array(
        'label'       => __( 'Popup: times per day', 'vance-health-hub' ),
        'description' => __( 'Only used when "How often" is set to a number of times per day.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 1, 'max' => 20, 'step' => 1 ),
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_askai_intro_image', array(
        'label'       => __( 'Popup: image', 'vance-health-hub' ),
        'description' => __( 'Shown in the right-hand column of the popup. Leave empty to show a branded placeholder. Landscape or square images around 600x600 work best.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_askai_intro_title', array(
        'default'           => 'Demystify Your Health Journey',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_title', array(
        'label'       => __( 'Popup: heading', 'vance-health-hub' ),
        'description' => __( 'The large headline in the article intro popup.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_subtitle', array(
        'default'           => 'VANCE-Ai helps you understand complex medical terms instantly as you read.',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_subtitle', array(
        'label'   => __( 'Popup: subtitle', 'vance-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'textarea',
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_logo', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_askai_intro_logo', array(
        'label'       => __( 'Popup: logo', 'vance-health-hub' ),
        'description' => __( 'Shown top-left of the popup. Leave empty to use the site logo.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
    ) ) );

    // Feature row 1 (paired with the highlighter glyph).
    $wp_customize->add_setting( 'vance_askai_intro_feat1_title', array(
        'default'           => 'Highlight to explain',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_feat1_title', array(
        'label'   => __( 'Popup: feature 1 title', 'vance-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'text',
    ) );
    $wp_customize->add_setting( 'vance_askai_intro_feat1_desc', array(
        'default'           => 'Get clear, instant explanations for any medical term.',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_feat1_desc', array(
        'label'   => __( 'Popup: feature 1 description', 'vance-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'textarea',
    ) );

    // Feature row 2 (paired with the reading-levels/sliders glyph).
    $wp_customize->add_setting( 'vance_askai_intro_feat2_title', array(
        'default'           => 'Personalized reading levels',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_feat2_title', array(
        'label'   => __( 'Popup: feature 2 title', 'vance-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'text',
    ) );
    $wp_customize->add_setting( 'vance_askai_intro_feat2_desc', array(
        'default'           => 'Content tailored to your health literacy and preferences.',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_feat2_desc', array(
        'label'   => __( 'Popup: feature 2 description', 'vance-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'textarea',
    ) );

    // CTA lead-in, button label and trust line.
    $wp_customize->add_setting( 'vance_askai_intro_lead', array(
        'default'           => 'Enable Vance-Ai by Clicking Below',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_lead', array(
        'label'       => __( 'Popup: button lead-in', 'vance-health-hub' ),
        'description' => __( 'The prompt shown directly above the button.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_cta', array(
        'default'           => 'ACTIVATE',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_cta', array(
        'label'       => __( 'Popup: button 1 label', 'vance-health-hub' ),
        'description' => __( 'First button. Activates and closes the popup without opening the chat.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_cta2', array(
        'default'           => 'ACTIVATE & TRY',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_cta2', array(
        'label'       => __( 'Popup: button 2 label', 'vance-health-hub' ),
        'description' => __( 'Second button. Activates, then opens the VANCE-Ai chat.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_activated', array(
        'default'           => 'ACTIVATED',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_activated', array(
        'label'       => __( 'Popup: activated label', 'vance-health-hub' ),
        'description' => __( 'Shown briefly on a button after it is clicked (the confirmation state).', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_trust', array(
        'default'           => 'Secure. Private. Always by your side.',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_trust', array(
        'label'   => __( 'Popup: trust line', 'vance-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'text',
    ) );

    // --- Logged-in variant --------------------------------------------------
    // Off by default: a signed-in reader sees the same popup as everyone else,
    // minus the "Register for free" link. Switching this on lets an admin swap
    // the headline, image and buttons for something aimed at existing members
    // instead of a sign-up pitch.
    $wp_customize->add_setting( 'vance_askai_intro_loggedin_enable', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_loggedin_enable', array(
        'label'       => __( 'Popup: show a different version to logged-in visitors', 'vance-health-hub' ),
        'description' => __( 'When on, the fields below replace the heading, image and buttons for signed-in readers. Leave any field blank to keep the standard version for that field.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_loggedin_title', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_loggedin_title', array(
        'label'       => __( 'Popup (logged-in): heading', 'vance-health-hub' ),
        'description' => __( 'Blank = use the standard heading above.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_loggedin_subtitle', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_loggedin_subtitle', array(
        'label'       => __( 'Popup (logged-in): subtitle', 'vance-health-hub' ),
        'description' => __( 'Blank = use the standard subtitle above.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'textarea',
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_loggedin_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_askai_intro_loggedin_image', array(
        'label'       => __( 'Popup (logged-in): image', 'vance-health-hub' ),
        'description' => __( 'Blank = use the standard popup image (or placeholder) above.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_askai_intro_loggedin_lead', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_loggedin_lead', array(
        'label'       => __( 'Popup (logged-in): button lead-in', 'vance-health-hub' ),
        'description' => __( 'Blank = use the standard lead-in above.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_loggedin_cta', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_loggedin_cta', array(
        'label'       => __( 'Popup (logged-in): button 1 label', 'vance-health-hub' ),
        'description' => __( 'Blank = use the standard button 1 label above.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_askai_intro_loggedin_cta2', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_loggedin_cta2', array(
        'label'       => __( 'Popup (logged-in): button 2 label', 'vance-health-hub' ),
        'description' => __( 'Blank = use the standard button 2 label above.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'text',
    ) );

    // Legacy freeform body: superseded by the structured fields above and no
    // longer rendered by the redesigned popup. Kept so any previously saved copy
    // is not silently dropped from the database.
    $wp_customize->add_setting( 'vance_askai_intro_body', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
    ) );
    $wp_customize->add_control( 'vance_askai_intro_body', array(
        'label'       => __( 'Popup: body text (legacy)', 'vance-health-hub' ),
        'description' => __( 'No longer shown in the redesigned popup. Use the subtitle and feature fields above instead.', 'vance-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'textarea',
    ) );


    // Vance AI Modal — colour customization
    $wp_customize->add_section( 'vance_modal_colors', array(
        'title'    => __( 'VANCE-Ai - Modal Colours', 'vance-health-hub' ),
        'panel'    => 'vance_content_panel',
        'priority' => 161,
    ) );
    $vance_modal_labels = array(
        'vance_modal_backdrop'         => 'Backdrop overlay',
        'vance_modal_panel_bg'         => 'Panel background',
        'vance_modal_text_color'       => 'Body text',
        'vance_modal_header_bg'        => 'Header / footer background',
        'vance_modal_title_color'      => 'Title text',
        'vance_modal_bot_bubble_bg'    => 'Bot bubble background',
        'vance_modal_bot_bubble_text'  => 'Bot bubble text',
        'vance_modal_user_bubble_bg'   => 'User bubble background',
        'vance_modal_user_bubble_text' => 'User bubble text',
        'vance_modal_input_bg'         => 'Input background',
        'vance_modal_input_text'       => 'Input text',
        'vance_modal_send_bg'          => 'Send button',
    );
    foreach ( vance_modal_color_defaults() as $vance_mc_key => $vance_mc_default ) {
        $wp_customize->add_setting( $vance_mc_key, array(
            'default'           => $vance_mc_default,
            'sanitize_callback' => 'vance_sanitize_color',
        ) );
        $wp_customize->add_control( $vance_mc_key, array(
            'label'       => $vance_modal_labels[ $vance_mc_key ],
            'description' => 'Hex (#008080) or rgba(r,g,b,a)',
            'section'     => 'vance_modal_colors',
            'type'        => 'text',
        ) );
    }

    // 4. Dynamic Homepage & Inner Nav Category Cards
    $wp_customize->add_section( 'vance_homepage_categories', array(
        'title'       => __( 'Category Cards', 'vance-health-hub' ),
        'description' => __( 'Configure display logic for category cards across the site.', 'vance-health-hub' ),
        'priority'    => 33,
        'panel'       => 'vance_homepage_panel',
    ) );

    // --- HOMEPAGE SPECIFIC ---
    $wp_customize->add_setting( 'vance_homepage_cards_per_row', array(
        'default'           => 6,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_homepage_cards_per_row', array(
        'label'   => __( 'Homepage: Cards Per Row', 'vance-health-hub' ),
        'section' => 'vance_homepage_categories',
        'type'    => 'number',
        'input_attrs' => array('min' => 1, 'max' => 12),
    ) );

    $wp_customize->add_setting( 'vance_homepage_card_alignment', array(
        'default'           => 'center',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_homepage_card_alignment', array(
        'label'   => __( 'Homepage: Card Alignment', 'vance-health-hub' ),
        'section' => 'vance_homepage_categories',
        'type'    => 'select',
        'choices' => array(
            'left'   => 'Left',
            'center' => 'Center',
            'right'  => 'Right',
        ),
    ) );

    // --- INNER NAV SPECIFIC ---
    $wp_customize->add_setting( 'vance_show_inner_nav', array(
        'default'           => true,
        'sanitize_callback' => 'vance_sanitize_checkbox',
    ) );
    $wp_customize->add_control( 'vance_show_inner_nav', array(
        'label'   => __( 'Show Inner Page Horizontal Nav', 'vance-health-hub' ),
        'section' => 'vance_homepage_categories',
        'type'    => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_inner_nav_cards_per_row', array(
        'default'           => 8,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_inner_nav_cards_per_row', array(
        'label'   => __( 'Inner Nav: Cards Per Row', 'vance-health-hub' ),
        'section' => 'vance_homepage_categories',
        'type'    => 'number',
        'input_attrs' => array('min' => 1, 'max' => 16),
    ) );

    $wp_customize->add_setting( 'vance_inner_nav_total_items', array(
        'default'           => 8,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_inner_nav_total_items', array(
        'label'   => __( 'Inner Nav: Total Items to Show', 'vance-health-hub' ),
        'section' => 'vance_homepage_categories',
        'type'    => 'number',
        'input_attrs' => array('min' => 1, 'max' => 50),
    ) );

    $wp_customize->add_setting( 'vance_cats_section_bg', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_cats_section_bg', array(
        'label'   => __( 'Section Background Color', 'vance-health-hub' ),
        'section' => 'vance_homepage_categories',
    ) ) );

    // --- DISCOVERY SUITE STYLING ---
    $wp_customize->add_section( 'vance_discovery_styling', array(
        'title'    => __( 'Styling', 'vance-health-hub' ),
        'priority' => 60,
        'panel'    => 'vance_discovery_panel',
    ) );

    // Titles
    $wp_customize->add_setting( 'vance_discovery_title_text', array( 'default' => 'Content Discovery Suite', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_discovery_title_text', array( 'label' => 'Main Title Text', 'section' => 'vance_discovery_styling', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_discovery_subtitle_text', array( 'default' => 'Explore our comprehensive database...', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    $wp_customize->add_control( 'vance_discovery_subtitle_text', array( 'label' => 'Subtitle Text', 'section' => 'vance_discovery_styling', 'type' => 'textarea' ) );

    $wp_customize->add_setting( 'vance_askai_text_size', array( 'default' => 15, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( 'vance_askai_text_size', array( 'label' => 'VANCE-Ai Text Size (px)', 'section' => 'vance_discovery_styling', 'type' => 'number', 'input_attrs' => array('min' => 10, 'max' => 24) ) );

    $wp_customize->add_setting( 'vance_askai_text_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_askai_text_color', array( 'label' => 'VANCE-Ai Text Color (Hex or RGBA)', 'section' => 'vance_discovery_styling', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_discovery_field_title_size', array( 'default' => 10, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( 'vance_discovery_field_title_size', array( 'label' => 'Filter Title Size (px)', 'section' => 'vance_discovery_styling', 'type' => 'number', 'input_attrs' => array('min' => 8, 'max' => 30) ) );

    $wp_customize->add_setting( 'vance_discovery_field_title_color', array( 'default' => 'rgba(255,255,255,0.4)', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_discovery_field_title_color', array( 'label' => 'Filter Title Color (Hex or RGBA)', 'section' => 'vance_discovery_styling', 'type' => 'text' ) );
    
    $wp_customize->add_setting( 'vance_discovery_item_label_size', array( 'default' => 13, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( 'vance_discovery_item_label_size', array( 'label' => 'Filter Item Label Size (px)', 'section' => 'vance_discovery_styling', 'type' => 'number', 'input_attrs' => array('min' => 8, 'max' => 30) ) );

    $wp_customize->add_setting( 'vance_discovery_item_label_color', array( 'default' => 'rgba(255,255,255,0.75)', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_discovery_item_label_color', array( 'label' => 'Filter Item Label Color (Hex or RGBA)', 'section' => 'vance_discovery_styling', 'type' => 'text' ) );

    // Styles
    $wp_customize->add_setting( 'vance_discovery_title_size', array( 'default' => 32, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( 'vance_discovery_title_size', array( 'label' => 'Title Size (px)', 'section' => 'vance_discovery_styling', 'type' => 'range', 'input_attrs' => array('min' => 12, 'max' => 60) ) );

    $wp_customize->add_setting( 'vance_discovery_title_color', array( 'default' => '#0F172A', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_discovery_title_color', array( 'label' => 'Title Color', 'section' => 'vance_discovery_styling' ) ) );

    $wp_customize->add_setting( 'vance_discovery_title_align', array( 'default' => 'left', 'sanitize_callback' => 'sanitize_key' ) );
    $wp_customize->add_control( 'vance_discovery_title_align', array( 
        'label' => 'Alignment', 
        'section' => 'vance_discovery_styling', 
        'type' => 'select',
        'choices' => array('left' => 'Left', 'center' => 'Center', 'right' => 'Right')
    ));

    $wp_customize->add_setting( 'vance_discovery_border_color', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_discovery_border_color', array( 'label' => 'Panel Border Color', 'section' => 'vance_discovery_styling' ) ) );

    $wp_customize->add_setting( 'vance_discovery_button_radius', array( 'default' => 8, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( 'vance_discovery_button_radius', array(
        'label'       => 'Chip / Button Corner Radius (px)',
        'description' => 'Controls rounding on filter chips, search input and action buttons.',
        'section'     => 'vance_discovery_styling',
        'type'        => 'range',
        'input_attrs' => array('min' => 0, 'max' => 40, 'step' => 1),
    ));

    $wp_customize->add_setting( 'vance_discovery_panel_radius', array( 'default' => 20, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( 'vance_discovery_panel_radius', array(
        'label'       => 'Panel Corner Radius (px)',
        'description' => 'Controls the overall rounding of the Discovery Suite container card.',
        'section'     => 'vance_discovery_styling',
        'type'        => 'range',
        'input_attrs' => array('min' => 0, 'max' => 48, 'step' => 2),
    ));

    // Section Background (Gradient or Solid)
    $wp_customize->add_setting( 'vance_discovery_section_bg', array( 'default' => 'linear-gradient(160deg, #0A1929 0%, #0F2440 55%, #0A1929 100%)', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_discovery_section_bg', array(
        'label'    => 'Section Background',
        'description' => 'Accepts CSS color or gradient (e.g. #0A1929 or linear-gradient(...))',
        'section'  => 'vance_discovery_styling',
        'type'     => 'text',
    ));

    // Panel Background
    $wp_customize->add_setting( 'vance_discovery_panel_bg', array( 'default' => 'rgba(255,255,255,0.04)', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_discovery_panel_bg', array(
        'label'    => 'Panel Background',
        'description' => 'Accepts CSS color (e.g. rgba(255,255,255,0.04))',
        'section'  => 'vance_discovery_styling',
        'type'     => 'text',
    ));

    // --- INDIVIDUAL CARD CONTROLS ---
    $categories = get_categories( array( 'hide_empty' => false ) );
    foreach ( $categories as $cat ) {
        // Show/Hide Toggle
        // Default FALSE: a card only shows when its box is explicitly ticked.
        // This makes the saved value authoritative (ticking now stores true,
        // which differs from the default and therefore persists reliably) and
        // stops new categories/sub-categories auto-appearing. Front-end reads
        // this via core get_theme_mod() so a stale legacy sla_* value can't
        // override the choice — see front-page.php 'cats' case.
        $wp_customize->add_setting( "vance_cat_card_show_{$cat->term_id}", array(
            'default'           => false,
            'sanitize_callback' => 'vance_sanitize_checkbox',
        ) );
        $wp_customize->add_control( "vance_cat_card_show_{$cat->term_id}", array(
            'label'   => sprintf( __( 'Show "%s" Card', 'vance-health-hub' ), $cat->name ),
            'section' => 'vance_homepage_categories',
            'type'    => 'checkbox',
        ) );
        
        // Priority (Order)
        $wp_customize->add_setting( "vance_cat_card_priority_{$cat->term_id}", array(
            'default'           => 10,
            'sanitize_callback' => 'absint',
        ) );
        $wp_customize->add_control( "vance_cat_card_priority_{$cat->term_id}", array(
            'label'       => sprintf( __( '"%s" Priority (Order)', 'vance-health-hub' ), $cat->name ),
            'description' => __( 'Lower numbers appear first.', 'vance-health-hub' ),
            'section'     => 'vance_homepage_categories',
            'type'        => 'number',
            'input_attrs' => array( 'min' => 1, 'max' => 100 ),
        ) );
        
        // Custom Icon
        $wp_customize->add_setting( "vance_cat_card_icon_{$cat->term_id}", array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_cat_card_icon_{$cat->term_id}", array(
            'label'       => sprintf( __( '"%s" Icon', 'vance-health-hub' ), $cat->name ),
            'description' => __( 'Optional: Upload custom icon (recommended: 48x48px PNG).', 'vance-health-hub' ),
            'section'     => 'vance_homepage_categories',
        ) ) );
    }

    // 4. Social API Settings Section
    $wp_customize->add_section( 'vance_social_api', array(
        'title'       => __( 'Social API Settings', 'vance-health-hub' ),
        'description' => __( 'Configure the webhook URL for social media automation (e.g. Make/Zapier).', 'vance-health-hub' ),
        'priority'    => 33,
        'panel'       => 'vance_advanced_panel',
    ) );

    $wp_customize->add_setting( 'vance_social_webhook_url', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );

    $wp_customize->add_control( 'vance_social_webhook_url', array(
        'label'   => __( 'Automation Webhook URL', 'vance-health-hub' ),
        'section' => 'vance_social_api',
        'type'    => 'url',
    ) );

    // 4.5 Newsletter Settings
    $wp_customize->add_section( 'vance_newsletter', array(
        'title'       => __( 'Newsletter', 'vance-health-hub' ),
        'priority'    => 33,
        'panel'       => 'vance_footer_panel',
    ) );

    $wp_customize->add_setting( 'vance_newsletter_action', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( 'vance_newsletter_action', array(
        'label'       => __( 'Form Action URL', 'vance-health-hub' ),
        'description' => __( 'Mailchimp/Hubspot form action URL.', 'vance-health-hub' ),
        'section'     => 'vance_newsletter',
        'type'        => 'url',
    ) );

    $wp_customize->add_setting( 'vance_newsletter_heading', array(
        'default'           => 'Join the Hub',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_newsletter_heading', array(
        'label'   => __( 'Heading', 'vance-health-hub' ),
        'section' => 'vance_newsletter',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_newsletter_desc', array(
        'default'           => 'Get the latest clinical reviews and tools.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'vance_newsletter_desc', array(
        'label'   => __( 'Description', 'vance-health-hub' ),
        'section' => 'vance_newsletter',
        'type'    => 'textarea',
    ) );

    // 4.5 Header (theme-wide nav)
    $wp_customize->add_section( 'vance_header_nav', array(
        'title'       => __( 'Header Navigation', 'vance-health-hub' ),
        'priority'    => 1,
        'panel'       => 'vance_brand_panel',
        'description' => __( 'Theme-wide header controls.', 'vance-health-hub' ),
    ) );

    $wp_customize->add_setting( 'vance_show_dashboard_btn', array(
        'default'           => true,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ) );
    $wp_customize->add_control( 'vance_show_dashboard_btn', array(
        'label'       => __( 'Show "My Dashboard" button in header', 'vance-health-hub' ),
        'description' => __( 'When off, the dashboard CTA is hidden from the header on every page. Logged-in users can still reach the dashboard via their account menu.', 'vance-health-hub' ),
        'section'     => 'vance_header_nav',
        'type'        => 'checkbox',
    ) );

    // 4.6 Footer Brand & Widgets
    $wp_customize->add_section( 'vance_footer_brand', array(
        'title'       => __( 'Brand & Widgets', 'vance-health-hub' ),
        'priority'    => 33.5,
        'panel'       => 'vance_footer_panel',
    ) );

    $wp_customize->add_setting( 'vance_footer_logo', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_footer_logo', array( 'label' => 'Footer Logo Image', 'section' => 'vance_footer_brand' ) ) );

    $wp_customize->add_setting( 'vance_footer_brand_text', array( 'default' => 'Advancing IBD Research knowledge and tools transforming the modern healthcare environment.', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    $wp_customize->add_control( 'vance_footer_brand_text', array( 'label' => 'Brand Text below Logo', 'section' => 'vance_footer_brand', 'type' => 'textarea' ) );

    $wp_customize->add_setting( 'vance_footer_copyright', array( 'default' => '(c) 2024 Vance Medical Group. All rights reserved.', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_footer_copyright', array( 'label' => 'Copyright Text', 'section' => 'vance_footer_brand', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_footer_heading_col1', array( 'default' => 'Topics', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_footer_heading_col1', array( 'label' => 'Column 1 Heading', 'section' => 'vance_footer_brand', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_footer_heading_col2', array( 'default' => 'For Professionals', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_footer_heading_col2', array( 'label' => 'Column 2 Heading', 'section' => 'vance_footer_brand', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_footer_heading_col3', array( 'default' => 'For Patients', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_footer_heading_col3', array( 'label' => 'Column 3 Heading', 'section' => 'vance_footer_brand', 'type' => 'text' ) );

    // 5. Category Hero Overrides Section
    $wp_customize->add_section( 'vance_category_heroes', array(
        'title'       => __( 'Category Heroes', 'vance-health-hub' ),
        'description' => __( 'Set unique hero images, taglines, and titles for specific categories.', 'vance-health-hub' ),
        'priority'    => 34,
        'panel'       => 'vance_content_panel',
    ) );

    // --- Tagline pill styling (global — applies to every category hero tagline) ---
    $wp_customize->add_setting( 'vance_cat_tagline_text_color', array(
        'default'           => '#008080',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_cat_tagline_text_color', array(
        'label'       => __( 'Tagline: Text Colour', 'vance-health-hub' ),
        'description' => __( 'Colour of the small eyebrow tagline above every category title.', 'vance-health-hub' ),
        'section'     => 'vance_category_heroes',
    ) ) );

    $wp_customize->add_setting( 'vance_cat_tagline_bg', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_cat_tagline_bg', array(
        'label'       => __( 'Tagline: Background Colour', 'vance-health-hub' ),
        'description' => __( 'Leave empty for no background (transparent). Setting a colour turns the tagline into a pill.', 'vance-health-hub' ),
        'section'     => 'vance_category_heroes',
    ) ) );

    $wp_customize->add_setting( 'vance_cat_tagline_border_color', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_cat_tagline_border_color', array(
        'label'       => __( 'Tagline: Border Colour', 'vance-health-hub' ),
        'description' => __( 'Used together with Border Width below.', 'vance-health-hub' ),
        'section'     => 'vance_category_heroes',
    ) ) );

    $wp_customize->add_setting( 'vance_cat_tagline_border_width', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_cat_tagline_border_width', array(
        'label'       => __( 'Tagline: Border Width (px)', 'vance-health-hub' ),
        'description' => __( '0 = no border. Needs a Border Colour set above to show.', 'vance-health-hub' ),
        'section'     => 'vance_category_heroes',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 10, 'step' => 1 ),
    ) );

    foreach ( $categories as $cat ) {
        // Photograph — the LIGHT hero (inc/category-hero.php), which is what
        // every category archive renders. A separate key from the Hero Image
        // below on purpose: those images were chosen to sit under a 78% navy
        // veil, and on a pale mint band with no veil they read as a dark
        // smear. Same reasoning that gave Contact a second image key in
        // inc/page-hero-spotlight.php.
        //
        // Leave it empty and a top-level category uses its own photograph from
        // assets/img/heroes/categories/, or the teal motif if it has none; a
        // SUB-category inherits whatever its parent is showing, so setting one
        // picture on Gastro Living re-skins all five of its sub-sections.
        $wp_customize->add_setting( "vance_cat_photo_{$cat->term_id}", array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_cat_photo_{$cat->term_id}", array(
            'label'       => sprintf( __( '%s: Photograph', 'vance-health-hub' ), $cat->name ),
            'description' => __( 'Landscape, 1400&times;876, subject right of centre with a bright and empty left third &mdash; that edge dissolves into the band. Leave empty to inherit.', 'vance-health-hub' ),
            'section'     => 'vance_category_heroes',
        ) ) );

        // Hero Image — the legacy DARK band. Category archives no longer
        // render it (see inc/category-hero.php); it is still read by the
        // non-category branches of archive.php, and the setting is kept so no
        // site loses a saved value.
        $wp_customize->add_setting( "vance_cat_hero_{$cat->term_id}", array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_cat_hero_{$cat->term_id}", array(
            'label'       => sprintf( __( '%s: Hero Image (legacy dark band)', 'vance-health-hub' ), $cat->name ),
            'description' => __( 'Kept for tag and post-type archives. Category pages use Photograph above.', 'vance-health-hub' ),
            'section'     => 'vance_category_heroes',
        ) ) );

        // Tagline
        $wp_customize->add_setting( "vance_cat_tagline_{$cat->term_id}", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( "vance_cat_tagline_{$cat->term_id}", array(
            'label'   => sprintf( __( '%s: Tagline', 'vance-health-hub' ), $cat->name ),
            'section' => 'vance_category_heroes',
            'type'    => 'text',
        ) );

        // Title Override (New)
        $wp_customize->add_setting( "vance_cat_title_{$cat->term_id}", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( "vance_cat_title_{$cat->term_id}", array(
            'label'   => sprintf( __( '%s: Title Override', 'vance-health-hub' ), $cat->name ),
            'section' => 'vance_category_heroes',
            'type'    => 'text',
        ) );

        // Grid Columns — Healthcare News only. Its sections are computed date
        // buckets, not real sub-category terms, so it can't use the per-sub-category
        // "Grid columns" control in Sub-category Layouts; this is the equivalent
        // scoped to just this page. Reuses vance_sanitize_grid_cols() (below).
        if ( 'content-healthcare-news' === $cat->slug ) {
            $wp_customize->add_setting( "vance_cat_grid_cols_{$cat->term_id}", array(
                'default'           => '3',
                'sanitize_callback' => 'vance_sanitize_grid_cols',
            ) );
            $wp_customize->add_control( "vance_cat_grid_cols_{$cat->term_id}", array(
                'label'       => sprintf( __( '%s: Grid Columns', 'vance-health-hub' ), $cat->name ),
                'description' => __( 'Articles per row in each date section (This Month, Last Month, etc).', 'vance-health-hub' ),
                'section'     => 'vance_category_heroes',
                'type'        => 'select',
                'choices'     => array(
                    '3' => __( '3 columns', 'vance-health-hub' ),
                    '4' => __( '4 columns', 'vance-health-hub' ),
                    '5' => __( '5 columns', 'vance-health-hub' ),
                ),
            ) );
        }
    }

    // 5.4 Category Promo Blocks
    //
    // One section per category, not one shared section holding every category's
    // controls. The old flat section already ran to ~170 controls across 21
    // categories and only stayed navigable because every label was prefixed
    // with its "Parent -> Child" path; folding the promo blocks onto one
    // renderer adds the colour, border and width controls to each, which would
    // have taken it past 350. Same treatment, and same reasoning, as the
    // Knowledge Base panel.
    //
    // Controls come from the shared registrar, driven by the term-addressed key
    // closure, so a category promo offers exactly what the homepage and
    // Knowledgebase ones do.
    $wp_customize->add_panel( 'vance_cat_promo_panel', array(
        'title'       => __( 'Category Promo Blocks', 'vance-health-hub' ),
        'description' => __( 'A promo card on each category archive, below the sub-category nav and above the articles. One section per category, parents first with their sub-categories underneath. Tick "Show promo block" and give it a heading to switch one on.', 'vance-health-hub' ),
        'priority'    => 34.4,
    ) );

    $vance_cat_promo_priority = 10;

    foreach ( vance_customizer_category_tree() as $vance_promo_row ) {
        $cat         = $vance_promo_row['term'];
        $promo_sec   = 'vance_cat_promo_sec_' . $cat->term_id;
        // Term names are stored HTML-encoded, and section titles render through
        // an escaping JS template -- see the Knowledge Base panel for the same
        // decode and the same reason.
        $promo_title = wp_specialchars_decode( $vance_promo_row['path'], ENT_QUOTES );

        $wp_customize->add_section( $promo_sec, array(
            'title'       => $promo_title,
            'description' => sprintf(
                /* translators: 1: category slug, 2: term ID */
                __( 'Slug: %1$s &middot; Term ID: %2$d', 'vance-health-hub' ),
                esc_html( $cat->slug ),
                (int) $cat->term_id
            ),
            'priority'    => $vance_cat_promo_priority,
            'panel'       => 'vance_cat_promo_panel',
        ) );
        $vance_cat_promo_priority++;

        vance_register_promo_block_controls(
            $wp_customize,
            $promo_sec,
            vance_promo_keys_term( $cat->term_id )
        );
    }
    // 5.5 Sub-Category Layouts (Clinical Reviews & Gastro Living)
    // For each child category under the grouped-archive parents, expose a
    // layout picker (Standard Grid / Bento / Asymmetric / Posters) and a
    // description block. Settings are keyed by term id so values survive
    // renames. The matching front-end lives in
    // template-parts/subcategory-grouped-archive.php.
    $wp_customize->add_section( 'vance_subcategory_layouts', array(
        'title'       => __( 'Sub-Category Layouts', 'vance-health-hub' ),
        'description' => __( 'Choose a layout and intro description for each sub-category (child category) of Clinical Reviews and Gastro Living. These drive how each group of articles is laid out on those category pages. Create child categories under those two parents (Posts → Categories) to see them listed here.', 'vance-health-hub' ),
        'priority'    => 34.5,
        'panel'       => 'vance_content_panel',
    ) );

    $vance_layout_choices = vance_subcat_layout_choices();
    foreach ( vance_grouped_archive_parent_slugs() as $vance_parent_slug ) {
        $vance_parent_term = get_category_by_slug( $vance_parent_slug );
        if ( ! $vance_parent_term ) {
            continue;
        }
        $vance_child_terms = get_categories( array(
            'parent'     => $vance_parent_term->term_id,
            'hide_empty' => false,
        ) );
        if ( empty( $vance_child_terms ) ) {
            continue;
        }
        foreach ( $vance_child_terms as $vance_sub ) {
            // Order (lower numbers appear first)
            $wp_customize->add_setting( "vance_subcat_order_{$vance_sub->term_id}", array(
                'default'           => 10,
                'sanitize_callback' => 'absint',
            ) );
            $wp_customize->add_control( "vance_subcat_order_{$vance_sub->term_id}", array(
                'label'       => sprintf( __( '%1$s → %2$s: Order', 'vance-health-hub' ), $vance_parent_term->name, $vance_sub->name ),
                'description' => __( 'Lower numbers appear first. Ties fall back to alphabetical.', 'vance-health-hub' ),
                'section'     => 'vance_subcategory_layouts',
                'type'        => 'number',
                'input_attrs' => array( 'min' => 0, 'max' => 100, 'step' => 1 ),
            ) );

            // Layout picker
            $wp_customize->add_setting( "vance_subcat_layout_{$vance_sub->term_id}", array(
                'default'           => 'grid',
                'sanitize_callback' => 'vance_sanitize_subcat_layout',
            ) );
            $wp_customize->add_control( "vance_subcat_layout_{$vance_sub->term_id}", array(
                'label'       => sprintf( __( '%1$s → %2$s: Layout', 'vance-health-hub' ), $vance_parent_term->name, $vance_sub->name ),
                'section'     => 'vance_subcategory_layouts',
                'type'        => 'select',
                'choices'     => $vance_layout_choices,
            ) );

            // Bento sub-options (only take effect when Layout = Bento)
            $wp_customize->add_setting( "vance_subcat_bento_count_{$vance_sub->term_id}", array(
                'default'           => '2',
                'sanitize_callback' => 'vance_sanitize_bento_count',
            ) );
            $wp_customize->add_control( "vance_subcat_bento_count_{$vance_sub->term_id}", array(
                'label'       => sprintf( __( '%1$s → %2$s: Bento layout', 'vance-health-hub' ), $vance_parent_term->name, $vance_sub->name ),
                'description' => __( 'Only applies when Layout = Bento.', 'vance-health-hub' ),
                'section'     => 'vance_subcategory_layouts',
                'type'        => 'select',
                'choices'     => array(
                    '2' => __( 'Main + 2 small', 'vance-health-hub' ),
                    '4' => __( 'Main + 4 small', 'vance-health-hub' ),
                ),
            ) );
            $wp_customize->add_setting( "vance_subcat_bento_side_{$vance_sub->term_id}", array(
                'default'           => 'left',
                'sanitize_callback' => 'vance_sanitize_bento_side',
            ) );
            $wp_customize->add_control( "vance_subcat_bento_side_{$vance_sub->term_id}", array(
                'label'       => sprintf( __( '%1$s → %2$s: Bento main side', 'vance-health-hub' ), $vance_parent_term->name, $vance_sub->name ),
                'description' => __( 'Which side the large main article sits on (Bento only).', 'vance-health-hub' ),
                'section'     => 'vance_subcategory_layouts',
                'type'        => 'select',
                'choices'     => array(
                    'left'  => __( 'Main on left', 'vance-health-hub' ),
                    'right' => __( 'Main on right', 'vance-health-hub' ),
                ),
            ) );

            // Posters sub-option (only takes effect when Layout = Posters)
            $wp_customize->add_setting( "vance_subcat_posters_cols_{$vance_sub->term_id}", array(
                'default'           => '3',
                'sanitize_callback' => 'vance_sanitize_posters_cols',
            ) );
            $wp_customize->add_control( "vance_subcat_posters_cols_{$vance_sub->term_id}", array(
                'label'       => sprintf( __( '%1$s → %2$s: Posters per row', 'vance-health-hub' ), $vance_parent_term->name, $vance_sub->name ),
                'description' => __( 'How many poster cards per row (Posters only).', 'vance-health-hub' ),
                'section'     => 'vance_subcategory_layouts',
                'type'        => 'select',
                'choices'     => array(
                    '3' => __( '3 per row', 'vance-health-hub' ),
                    '4' => __( '4 per row', 'vance-health-hub' ),
                ),
            ) );

            // Standard Grid sub-option (only takes effect when Layout = Standard Grid)
            $wp_customize->add_setting( "vance_subcat_grid_cols_{$vance_sub->term_id}", array(
                'default'           => '3',
                'sanitize_callback' => 'vance_sanitize_grid_cols',
            ) );
            $wp_customize->add_control( "vance_subcat_grid_cols_{$vance_sub->term_id}", array(
                'label'       => sprintf( __( '%1$s → %2$s: Grid columns', 'vance-health-hub' ), $vance_parent_term->name, $vance_sub->name ),
                'description' => __( 'Articles per row (Standard Grid only).', 'vance-health-hub' ),
                'section'     => 'vance_subcategory_layouts',
                'type'        => 'select',
                'choices'     => array(
                    '3' => __( '3 per row', 'vance-health-hub' ),
                    '4' => __( '4 per row', 'vance-health-hub' ),
                    '5' => __( '5 per row', 'vance-health-hub' ),
                ),
            ) );

            // Rows cap (applies to Standard Grid, Posters, Asymmetric)
            $wp_customize->add_setting( "vance_subcat_rows_{$vance_sub->term_id}", array(
                'default'           => '0',
                'sanitize_callback' => 'vance_sanitize_rows',
            ) );
            $wp_customize->add_control( "vance_subcat_rows_{$vance_sub->term_id}", array(
                'label'       => sprintf( __( '%1$s → %2$s: Rows to show', 'vance-health-hub' ), $vance_parent_term->name, $vance_sub->name ),
                'description' => __( 'Limit how many rows appear (Standard Grid, Posters, Asymmetric). Extra articles stay reachable via “View all”. Choose All to show everything.', 'vance-health-hub' ),
                'section'     => 'vance_subcategory_layouts',
                'type'        => 'select',
                'choices'     => array(
                    '0' => __( 'All rows', 'vance-health-hub' ),
                    '1' => __( '1 row', 'vance-health-hub' ),
                    '2' => __( '2 rows', 'vance-health-hub' ),
                    '3' => __( '3 rows', 'vance-health-hub' ),
                    '4' => __( '4 rows', 'vance-health-hub' ),
                    '5' => __( '5 rows', 'vance-health-hub' ),
                    '6' => __( '6 rows', 'vance-health-hub' ),
                ),
            ) );

            // Description block
            $wp_customize->add_setting( "vance_subcat_desc_{$vance_sub->term_id}", array(
                'default'           => '',
                'sanitize_callback' => 'wp_kses_post',
            ) );
            $wp_customize->add_control( "vance_subcat_desc_{$vance_sub->term_id}", array(
                'label'       => sprintf( __( '%1$s → %2$s: Description', 'vance-health-hub' ), $vance_parent_term->name, $vance_sub->name ),
                'description' => __( 'Shown above this sub-category\'s articles. Leave blank to use the category\'s own description.', 'vance-health-hub' ),
                'section'     => 'vance_subcategory_layouts',
                'type'        => 'textarea',
            ) );
        }
    }

    // 6. Homepage Section Ordering — drag-and-drop sortable control
    // Class + helpers live in inc/customizer-sortable-control.php (now required
    // at the top of functions.php so the registry function is available on
    // frontend page loads too — see bug-fix comment there).
    // Re-require is idempotent (require_once) and kept here as belt-and-braces
    // in case any future refactor moves the top-level require.

    $wp_customize->add_section( 'vance_homepage_order', array(
        'title'    => __( 'Section Order', 'vance-health-hub' ),
        'priority' => 35,
        'panel'    => 'vance_homepage_panel',
    ) );

    $wp_customize->add_setting( 'vance_homepage_section_order', array(
        // Keep in step with front-page.php's own fallback for this mod.
        // 'pathway' was retired and 'pathway_content' became
        // 'prime-block-home-1' on 2026-08-21.
        'default'           => 'hero,prime-block-home-1,promo,cats,discovery,join,kb,testimonials',
        'sanitize_callback' => 'vance_sanitize_sortable_sections',
    ) );

    $wp_customize->add_control(
        new Vance_Customize_Sortable_Sections_Control(
            $wp_customize,
            'vance_homepage_section_order',
            array(
                'label'       => __( 'Homepage Section Order', 'vance-health-hub' ),
                'description' => __( 'Drag to reorder. Tick the checkbox to show a section on the homepage; untick to hide it. Sections are rendered top-to-bottom in the order shown.', 'vance-health-hub' ),
                'section'     => 'vance_homepage_order',
            )
        )
    );

    // 6b. Section Dividers
    // One shared look config + a per-section "show divider after this" toggle.
    // Renderer is in front-page.php's foreach loop right after each section's
    // case body runs: if vance_divider_after_<section_id> is true, emit a
    // styled <hr> between this section and the next one.
    $wp_customize->add_section( 'vance_section_dividers', array(
        'title'       => __( 'Section Dividers', 'vance-health-hub' ),
        'priority'    => 35.5,
        'panel'       => 'vance_homepage_panel',
        'description' => __( 'Insert a divider line between specific homepage sections. Configure the look once below, then tick which sections should have a divider rendered AFTER them.', 'vance-health-hub' ),
    ) );

    // -- Look config (shared across all dividers) --
    $wp_customize->add_setting( 'vance_divider_color', array(
        'default'           => '#2f4f6f',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_divider_color', array(
        'label'   => __( 'Divider Colour', 'vance-health-hub' ),
        'section' => 'vance_section_dividers',
    ) ) );

    // Optional background colour for the divider wrapper. Blank = transparent
    // (current behaviour). Set a colour to render a coloured band across the
    // whole row that the divider sits inside. Added 2026-05-26.
    $wp_customize->add_setting( 'vance_divider_bg_color', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_divider_bg_color', array(
        'label'       => __( 'Divider Background Colour', 'vance-health-hub' ),
        'description' => __( 'Background colour for the divider row. Leave blank for transparent.', 'vance-health-hub' ),
        'section'     => 'vance_section_dividers',
    ) ) );

    $wp_customize->add_setting( 'vance_divider_thickness', array(
        'default'           => 1,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_divider_thickness', array(
        'label'       => __( 'Thickness (px) - line stroke', 'vance-health-hub' ),
        'description' => __( 'How thick the line is.', 'vance-health-hub' ),
        'section'     => 'vance_section_dividers',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 1, 'max' => 20, 'step' => 1 ),
    ) );

    $wp_customize->add_setting( 'vance_divider_width', array(
        'default'           => 100,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_divider_width', array(
        'label'       => __( 'Width (%) - horizontal extent', 'vance-health-hub' ),
        'description' => __( '100 = full container width. Less than 100 centres a shorter line (e.g. 50 = half width, centred).', 'vance-health-hub' ),
        'section'     => 'vance_section_dividers',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 10, 'max' => 100, 'step' => 5 ),
    ) );

    $wp_customize->add_setting( 'vance_divider_style', array(
        'default'           => 'solid',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_divider_style', array(
        'label'   => __( 'Line Style', 'vance-health-hub' ),
        'section' => 'vance_section_dividers',
        'type'    => 'select',
        'choices' => array(
            'solid'  => 'Solid',
            'dashed' => 'Dashed',
            'dotted' => 'Dotted',
            'double' => 'Double',
        ),
    ) );

    $wp_customize->add_setting( 'vance_divider_margin', array(
        'default'           => 40,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_divider_margin', array(
        'label'       => __( 'Margin (px) - vertical space around the divider', 'vance-health-hub' ),
        'description' => __( 'Gap above and below the line. Larger = more breathing room between sections.', 'vance-health-hub' ),
        'section'     => 'vance_section_dividers',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 200, 'step' => 4 ),
    ) );

    $wp_customize->add_setting( 'vance_divider_padding', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_divider_padding', array(
        'label'       => __( 'Padding (px) - vertical space inside the divider wrapper', 'vance-health-hub' ),
        'description' => __( 'Adds space between the line and the edges of its own wrapper (rarely needed; usually leave at 0).', 'vance-health-hub' ),
        'section'     => 'vance_section_dividers',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 100, 'step' => 4 ),
    ) );

    // -- Per-section toggles. One checkbox per available section. --
    // We iterate the same registry the Section Order control uses, so any
    // section added to vance_get_available_sections() (Phase 2b, content
    // widgets, etc) automatically gets a divider toggle without code changes.
    if ( function_exists( 'vance_get_available_sections' ) ) {
        $divider_targets = vance_get_available_sections();
        foreach ( $divider_targets as $sid => $meta ) {
            $key = 'vance_divider_after_' . str_replace( '-', '_', $sid );
            $wp_customize->add_setting( $key, array(
                'default'           => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ) );
            $wp_customize->add_control( $key, array(
                'label'   => sprintf( 'Show divider AFTER: %s (%s)', $meta['label'], $meta['group'] ),
                'section' => 'vance_section_dividers',
                'type'    => 'checkbox',
            ) );
        }
    }

    // 6c. Content Widgets — N pre-registered latest-content slots.
    // The render functions live in inc/content-widget.php (required at the
    // top of functions.php). Here we just register the Customizer panel +
    // one section per instance, each with the full per-widget config.
    if ( ! defined( 'VANCE_CONTENT_WIDGET_INSTANCES' ) ) {
        define( 'VANCE_CONTENT_WIDGET_INSTANCES', 5 );
    }

    $wp_customize->add_panel( 'vance_content_widgets_panel', array(
        'title'       => __( 'Content Widgets', 'vance-health-hub' ),
        'priority'    => 14.5,
        'description' => __( 'Five reusable latest-content blocks. Enable any combination via Appearance → Customize → Homepage → Section Order, then configure each one here.', 'vance-health-hub' ),
    ) );

    // Build the category choices once (re-used across all 5 widget panels).
    $cw_cat_choices = array( 0 => 'All categories' );
    foreach ( get_categories( array( 'hide_empty' => false ) ) as $cat ) {
        $cw_cat_choices[ $cat->term_id ] = $cat->name;
    }

    for ( $cwn = 1; $cwn <= VANCE_CONTENT_WIDGET_INSTANCES; $cwn++ ) {
        $sec_id = 'vance_cw_' . $cwn;
        $prefix = 'vance_cw' . $cwn . '_';

        $wp_customize->add_section( $sec_id, array(
            'title'    => sprintf( __( 'Content Widget %d', 'vance-health-hub' ), $cwn ),
            'priority' => 10 + $cwn,
            'panel'    => 'vance_content_widgets_panel',
        ) );

        // -- Visibility ------------------------------------------------------
        // Section Order controls WHERE the widget sits; this controls whether
        // it renders at all. Before this existed, a widget was only ever
        // visible if the admin had also ticked it in the separate Section
        // Order screen, so configured widgets 2-5 silently showed nothing.
        $wp_customize->add_setting( $prefix . 'show', array( 'default' => true, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
        $wp_customize->add_control( $prefix . 'show', array(
            'label'       => 'Show this widget',
            'description' => 'Untick to hide this widget without removing it from Section Order.',
            'section'     => $sec_id,
            'type'        => 'checkbox',
        ) );

        // -- Heading + Subtitle copy --
        $wp_customize->add_setting( $prefix . 'heading', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . 'heading', array( 'label' => 'Heading (leave blank to hide)', 'section' => $sec_id, 'type' => 'text' ) );

        $wp_customize->add_setting( $prefix . 'subtitle', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . 'subtitle', array( 'label' => 'Subtitle / Eyebrow (above heading)', 'section' => $sec_id, 'type' => 'text' ) );

        // -- Query: count, category, tag --
        $wp_customize->add_setting( $prefix . 'count', array( 'default' => 6, 'sanitize_callback' => 'absint' ) );
        $wp_customize->add_control( $prefix . 'count', array( 'label' => 'Number of posts', 'section' => $sec_id, 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 24, 'step' => 1 ) ) );

        $wp_customize->add_setting( $prefix . 'category', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $wp_customize->add_control( $prefix . 'category', array( 'label' => 'Filter by category', 'section' => $sec_id, 'type' => 'select', 'choices' => $cw_cat_choices ) );

        $wp_customize->add_setting( $prefix . 'tag', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . 'tag', array( 'label' => 'Filter by tag slug (optional, e.g. "ibd")', 'section' => $sec_id, 'type' => 'text' ) );

        // -- Layout --
        $wp_customize->add_setting( $prefix . 'layout', array( 'default' => 'grid', 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . 'layout', array(
            'label'   => 'Layout',
            'section' => $sec_id,
            'type'    => 'select',
            'choices' => array(
                'grid'  => 'Uniform Grid (every card same size)',
                'bento' => 'Bento (1 large featured + smaller grid)',
                'promo' => 'Featured Promo Card + Grid',
            ),
        ) );

        // -- Featured promo card (Featured Promo Card + Grid layout only) ----
        // The promo tile takes the first cell of the uniform grid and reuses
        // the same card chrome (background, border, hover) as the post cards,
        // so it needs no separate colour controls beyond its icon.
        $cw_promo_active = function ( $control ) use ( $prefix ) {
            // Defensive null check: active_callback runs in wp-admin, where a
            // fatal would take the whole Customizer down.
            $setting = $control->manager->get_setting( $prefix . 'layout' );
            return $setting && 'promo' === $setting->value();
        };

        $wp_customize->add_setting( $prefix . 'promo_icon', array( 'default' => 'star', 'sanitize_callback' => 'sanitize_key' ) );
        $wp_customize->add_control( $prefix . 'promo_icon', array(
            'label'           => 'Promo Card — Icon',
            'description'     => 'Chosen from the theme icon set (assets/img/icons).',
            'section'         => $sec_id,
            'type'            => 'select',
            'choices'         => vance_cw_icon_choices(),
            'active_callback' => $cw_promo_active,
        ) );

        $wp_customize->add_setting( $prefix . 'promo_icon_bg_color', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'promo_icon_bg_color', array(
            'label'           => 'Promo Card — Icon Background Colour',
            'section'         => $sec_id,
            'active_callback' => $cw_promo_active,
        ) ) );

        $wp_customize->add_setting( $prefix . 'promo_heading', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . 'promo_heading', array(
            'label'           => 'Promo Card — Heading',
            'section'         => $sec_id,
            'type'            => 'text',
            'active_callback' => $cw_promo_active,
        ) );

        $wp_customize->add_setting( $prefix . 'promo_text', array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
        $wp_customize->add_control( $prefix . 'promo_text', array(
            'label'           => 'Promo Card — Body Text',
            'section'         => $sec_id,
            'type'            => 'textarea',
            'active_callback' => $cw_promo_active,
        ) );

        $wp_customize->add_setting( $prefix . 'promo_button_text', array( 'default' => 'Learn more', 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . 'promo_button_text', array(
            'label'           => 'Promo Card — Button Text',
            'section'         => $sec_id,
            'type'            => 'text',
            'active_callback' => $cw_promo_active,
        ) );

        $wp_customize->add_setting( $prefix . 'promo_button_link', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . 'promo_button_link', array(
            'label'           => 'Promo Card — Button Link',
            'description'     => 'Leave blank to render the card without a link or button.',
            'section'         => $sec_id,
            'type'            => 'text',
            'active_callback' => $cw_promo_active,
        ) );

        $wp_customize->add_setting( $prefix . 'text_align', array( 'default' => 'left', 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . 'text_align', array(
            'label'   => 'Text Alignment (inside cards + heading)',
            'section' => $sec_id,
            'type'    => 'select',
            'choices' => array( 'left' => 'Left', 'center' => 'Center', 'right' => 'Right' ),
        ) );

        $wp_customize->add_setting( $prefix . 'featured_position', array( 'default' => 'left', 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . 'featured_position', array(
            'label'       => 'Featured Card Position (Bento layout only)',
            'description' => 'Which side the big featured card sits on. Ignored in Uniform Grid.',
            'section'     => $sec_id,
            'type'        => 'select',
            'choices'     => array( 'left' => 'Left', 'right' => 'Right' ),
        ) );

        // -- Grid sizing (only relevant in Uniform Grid mode) --
        $wp_customize->add_setting( $prefix . 'rows', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
        $wp_customize->add_control( $prefix . 'rows', array( 'label' => 'Rows (Uniform Grid only)', 'section' => $sec_id, 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 6, 'step' => 1 ) ) );

        $wp_customize->add_setting( $prefix . 'per_row', array( 'default' => 3, 'sanitize_callback' => 'absint' ) );
        $wp_customize->add_control( $prefix . 'per_row', array( 'label' => 'Per Row (Uniform Grid only)', 'section' => $sec_id, 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 6, 'step' => 1 ) ) );

        // -- Meta toggles --
        $wp_customize->add_setting( $prefix . 'show_image', array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
        $wp_customize->add_control( $prefix . 'show_image', array( 'label' => 'Show post thumbnail', 'section' => $sec_id, 'type' => 'checkbox' ) );

        $wp_customize->add_setting( $prefix . 'show_date', array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
        $wp_customize->add_control( $prefix . 'show_date', array( 'label' => 'Show post date', 'section' => $sec_id, 'type' => 'checkbox' ) );

        $wp_customize->add_setting( $prefix . 'show_author', array( 'default' => false, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
        $wp_customize->add_control( $prefix . 'show_author', array( 'label' => 'Show post author', 'section' => $sec_id, 'type' => 'checkbox' ) );

        // -- Colours --
        $wp_customize->add_setting( $prefix . 'bg_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'bg_color', array( 'label' => 'Section Background Colour', 'section' => $sec_id ) ) );

        $wp_customize->add_setting( $prefix . 'title_color', array( 'default' => '#0F172A', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'title_color', array( 'label' => 'Card Title Colour', 'section' => $sec_id ) ) );

        $wp_customize->add_setting( $prefix . 'subtitle_color', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'subtitle_color', array( 'label' => 'Subtitle / Eyebrow Colour', 'section' => $sec_id ) ) );

        // -- Display mode + truncation (added 2026-05-26, revised) -----------
        // Display mode select replaces the older title_only checkbox. Three
        // choices: full card (default), image+title only, title only.
        // (Old title_only theme_mod is still read in the render as fallback so
        // existing customisations don't break.)
        $wp_customize->add_setting( $prefix . 'display_mode', array( 'default' => 'full', 'sanitize_callback' => 'sanitize_key' ) );
        $wp_customize->add_control( $prefix . 'display_mode', array(
            'label'       => 'Display Mode',
            'description' => 'Full card = image + meta + title + excerpt + Read More. Image + Title = thumbnail + title + Read More (no excerpt, no meta). Title only = title + Read More.',
            'section'     => $sec_id,
            'type'        => 'select',
            'choices'     => array(
                'full'        => 'Full card (image + meta + title + excerpt + Read More)',
                'image_title' => 'Image + Title + Read More',
                'title_only'  => 'Title + Read More only',
            ),
        ) );

        $wp_customize->add_setting( $prefix . 'read_more_text', array( 'default' => 'Read more →', 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $prefix . 'read_more_text', array( 'label' => '"Read more" link text', 'section' => $sec_id, 'type' => 'text' ) );

        $wp_customize->add_setting( $prefix . 'title_truncate', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $wp_customize->add_control( $prefix . 'title_truncate', array(
            'label'       => 'Truncate title at N characters',
            'description' => '0 = no truncation. Otherwise titles longer than N characters are cut off with an ellipsis.',
            'section'     => $sec_id,
            'type'        => 'number',
            'input_attrs' => array( 'min' => 0, 'max' => 200, 'step' => 1 ),
        ) );

        $wp_customize->add_setting( $prefix . 'desc_truncate', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $wp_customize->add_control( $prefix . 'desc_truncate', array(
            'label'       => 'Truncate description at N characters',
            'description' => '0 = no truncation. Applies only in Full card mode.',
            'section'     => $sec_id,
            'type'        => 'number',
            'input_attrs' => array( 'min' => 0, 'max' => 500, 'step' => 1 ),
        ) );

        // -- Card chrome (added 2026-05-26) ----------------------------------
        $wp_customize->add_setting( $prefix . 'card_bg_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'card_bg_color', array( 'label' => 'Card Background Colour', 'section' => $sec_id ) ) );

        $wp_customize->add_setting( $prefix . 'card_border_color', array( 'default' => '#e2e8f0', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'card_border_color', array( 'label' => 'Card Border Colour', 'section' => $sec_id ) ) );

        $wp_customize->add_setting( $prefix . 'card_border_width', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
        $wp_customize->add_control( $prefix . 'card_border_width', array(
            'label'       => 'Card Border Width (px)',
            'section'     => $sec_id,
            'type'        => 'number',
            'input_attrs' => array( 'min' => 0, 'max' => 6, 'step' => 1 ),
        ) );

        // -- Title hover + excerpt + meta (added 2026-05-26) -----------------
        $wp_customize->add_setting( $prefix . 'title_hover_color', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'title_hover_color', array( 'label' => 'Card Title Colour (on hover)', 'section' => $sec_id ) ) );

        $wp_customize->add_setting( $prefix . 'excerpt_color', array( 'default' => '#64748b', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'excerpt_color', array( 'label' => 'Excerpt / Description Colour', 'section' => $sec_id ) ) );

        $wp_customize->add_setting( $prefix . 'meta_color', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'meta_color', array( 'label' => 'Meta Colour (date / author / category)', 'section' => $sec_id ) ) );

        // -- Read more button (added 2026-05-26) -----------------------------
        $wp_customize->add_setting( $prefix . 'rm_text_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'rm_text_color', array( 'label' => '"Read more" Text Colour', 'section' => $sec_id ) ) );

        $wp_customize->add_setting( $prefix . 'rm_bg_color', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'rm_bg_color', array( 'label' => '"Read more" Background Colour', 'section' => $sec_id ) ) );

        $wp_customize->add_setting( $prefix . 'rm_border_color', array( 'default' => '#008080', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'rm_border_color', array( 'label' => '"Read more" Border Colour', 'section' => $sec_id ) ) );

        $wp_customize->add_setting( $prefix . 'rm_hover_text_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'rm_hover_text_color', array( 'label' => '"Read more" Text on Hover', 'section' => $sec_id ) ) );

        $wp_customize->add_setting( $prefix . 'rm_hover_bg_color', array( 'default' => '#0A1929', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $prefix . 'rm_hover_bg_color', array( 'label' => '"Read more" Background on Hover', 'section' => $sec_id ) ) );
    }

    // 6d. Tool Widgets — LEGACY Customizer panel was removed 2026-05-26.
    // The two single tool widgets were merged into one row; configure the
    // merged section under Vance Theme → Content → Tool Widgets Row (merged).
    // The vance_tw_content_filters_* and vance_tw_vance_ai_* theme_mods still
    // exist in the DB (read as fallback defaults by the merged renderer) but
    // are no longer exposed in the Customizer UI.

    // 7. Promo Content Block (New)
    // 7. Promo Block — the wide promo band. Registered through the shared
    // helper below so the Knowledgebase copy (vance_kbpromo_*) is guaranteed to
    // offer exactly the same controls with the same defaults.
    $wp_customize->add_section( 'vance_promo_block', array(
        'title'       => __( 'Promo Block', 'vance-health-hub' ),
        'priority'    => 31.55,
        'panel'       => 'vance_homepage_panel',
        'description' => __( 'The promo card on the homepage. Position comes from Homepage &rarr; Section Order.', 'vance-health-hub' ),
    ) );
    vance_register_promo_block_controls(
        $wp_customize,
        'vance_promo_block',
        vance_promo_keys_prefixed( 'vance_promo_' ),
        array(
            'show_label' => __( 'Show Promo Block', 'vance-health-hub' ),
            'defaults'   => vance_promo_prefixed_defaults(),
        )
    );

    // 8. Join Block Settings
    $wp_customize->add_section( 'vance_join_community', array(
        'title'    => __( 'Join Block', 'vance-health-hub' ),
        'priority' => 31.6,
        'panel'    => 'vance_homepage_panel',
    ) );

    // 8. Join Toggle
    $wp_customize->add_setting( 'vance_join_show', array( 'default' => true, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
    $wp_customize->add_control( 'vance_join_show', array( 'label' => 'Show "Join the Hub" Block', 'section' => 'vance_join_community', 'type' => 'checkbox' ) );

    $wp_customize->add_setting( 'vance_premium_section_bg', array(
        'default'           => '#0f172a',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_premium_section_bg', array(
        'label'   => __( 'Premium Subscribe Section Background Color', 'vance-health-hub' ),
        'section' => 'vance_join_community',
    ) ) );

    // 6. Dynamic Knowledgebase Sections
    //
    // One Customizer SECTION per category, not one shared section holding every
    // category's controls. The flat list had grown to ten controls x ~25
    // categories in a single scrolling pane, where the only thing separating
    // one category's "Number of Posts" from another's was the name quoted in
    // the label -- and three of the categories are called "Clinical Reviews",
    // "Clinical Trial Reviews" and "Clinical Trial Abstracts". A value entered
    // one row off lands on a different category and silently does nothing
    // visible, which is what happened on 2026-08-27.
    //
    // Setting IDs are deliberately unchanged: only the section each control is
    // attached to moves, so every saved theme mod carries over with no
    // migration. The labels drop their "%s" name prefix, which the section
    // title now carries.
    $wp_customize->add_panel( 'vance_kb_panel', array(
        'title'       => __( 'Knowledge Base Categories', 'vance-health-hub' ),
        'description' => __( 'One section per category, listed alphabetically. Each controls how that category appears as a content block on the homepage. The number after a name is its published post count, the same figure shown in Posts &rarr; Categories -- a category showing 0 has nothing to render.', 'vance-health-hub' ),
        // Sits between "Content &amp; Knowledge Base" (12) and "Footer" (13).
        'priority'    => 12.5,
    ) );

    // get_categories() returns name-ascending by default, so incrementing the
    // priority as we go renders the sections alphabetically.
    $categories  = get_categories( array( 'hide_empty' => false ) );
    $kb_priority = 10;

    foreach ( $categories as $cat ) {
        $kb_section = "vance_kb_cat_{$cat->term_id}";

        // Term names are stored HTML-encoded, so "Diagnosis & Treatment" comes
        // back as "Diagnosis &amp; Treatment". The Customizer renders section
        // titles through an escaping JS template, so the entity has to be
        // decoded here or the admin reads the raw "&amp;".
        $kb_cat_name = wp_specialchars_decode( $cat->name, ENT_QUOTES );

        $wp_customize->add_section( $kb_section, array(
            // The post count in the title is the disambiguator: it is what
            // would have shown at a glance that "Clinical Trial Reviews" is
            // empty and could never render whatever was set on it.
            'title'       => sprintf( '%1$s (%2$d)', $kb_cat_name, (int) $cat->count ),
            'description' => sprintf(
                /* translators: 1: category slug, 2: term ID */
                __( 'Slug: %1$s &middot; Term ID: %2$d', 'vance-health-hub' ),
                esc_html( $cat->slug ),
                (int) $cat->term_id
            ),
            'priority'    => $kb_priority,
            'panel'       => 'vance_kb_panel',
        ) );
        $kb_priority++;

        // Show/Hide Toggle
        $wp_customize->add_setting( "vance_kb_show_{$cat->term_id}", array(
            'default'           => true,
            'sanitize_callback' => 'vance_sanitize_checkbox',
        ) );
        $wp_customize->add_control( "vance_kb_show_{$cat->term_id}", array(
            'label'   => __( 'Show this section on the homepage', 'vance-health-hub' ),
            'section' => $kb_section,
            'type'    => 'checkbox',
        ) );

        // Priority (Order)
        $wp_customize->add_setting( "vance_kb_priority_{$cat->term_id}", array(
            'default'           => 10,
            'sanitize_callback' => 'absint',
        ) );
        $wp_customize->add_control( "vance_kb_priority_{$cat->term_id}", array(
            'label'       => __( 'Priority (Order)', 'vance-health-hub' ),
            'description' => __( 'Lower numbers appear first.', 'vance-health-hub' ),
            'section'     => $kb_section,
            'type'        => 'number',
            'input_attrs' => array( 'min' => 1, 'max' => 100 ),
        ) );

        // Description
        $wp_customize->add_setting( "vance_kb_desc_{$cat->term_id}", array(
            'default'           => $cat->description,
            'sanitize_callback' => 'sanitize_textarea_field',
        ) );
        $wp_customize->add_control( "vance_kb_desc_{$cat->term_id}", array(
            'label'   => __( 'Description', 'vance-health-hub' ),
            'section' => $kb_section,
            'type'    => 'textarea',
        ) );

        // Post Count
        $wp_customize->add_setting( "vance_kb_count_{$cat->term_id}", array(
            'default'           => 4,
            'sanitize_callback' => 'absint',
        ) );
        $wp_customize->add_control( "vance_kb_count_{$cat->term_id}", array(
            'label'       => __( 'Number of Posts', 'vance-health-hub' ),
            'section'     => $kb_section,
            'type'        => 'number',
            'input_attrs' => array( 'min' => 1, 'max' => 12, 'step' => 1 ),
        ) );

        $wp_customize->add_setting( "vance_kb_view_all_{$cat->term_id}", array(
            'default'           => 'View All',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( "vance_kb_view_all_{$cat->term_id}", array(
            'label'   => __( 'View All Label', 'vance-health-hub' ),
            'section' => $kb_section,
            'type'    => 'text',
        ) );

        // Layout
        $wp_customize->add_setting( "vance_kb_layout_{$cat->term_id}", array(
            'default'           => 'grid-4',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( "vance_kb_layout_{$cat->term_id}", array(
            'label'       => __( 'Layout', 'vance-health-hub' ),
            'description' => __( 'The two Standard Grid options use the same card, just sized to fit four or five per row on desktop; both step down to fewer columns on narrower screens. Raise "Number of Posts" to a multiple of the column count so the last row does not come up short.', 'vance-health-hub' ),
            'section'     => $kb_section,
            'type'        => 'select',
            'choices'     => array(
                'grid-4'     => 'Standard Grid (4 Cols)',
                'grid-5'     => 'Standard Grid (5 Cols)',
                'bento'      => 'Bento Grid (News Style)',
                'asymmetric' => 'Asymmetric (Review Style)',
                'posters'    => 'Posters (Opinion Style)',
            ),
        ) );

        // Posters sub-options (only take effect when Layout = Posters).
        // Posts fetched for the poster grid = columns x rows.
        $wp_customize->add_setting( "vance_kb_posters_cols_{$cat->term_id}", array(
            'default'           => '3',
            'sanitize_callback' => 'vance_sanitize_kb_posters_cols',
        ) );
        $wp_customize->add_control( "vance_kb_posters_cols_{$cat->term_id}", array(
            'label'       => __( 'Posters per row', 'vance-health-hub' ),
            'description' => __( 'How many poster cards per row (Posters layout only).', 'vance-health-hub' ),
            'section'     => $kb_section,
            'type'        => 'select',
            'choices'     => array(
                '3' => __( '3 per row', 'vance-health-hub' ),
                '4' => __( '4 per row', 'vance-health-hub' ),
                '5' => __( '5 per row', 'vance-health-hub' ),
            ),
        ) );

        $wp_customize->add_setting( "vance_kb_posters_rows_{$cat->term_id}", array(
            'default'           => '2',
            'sanitize_callback' => 'vance_sanitize_kb_posters_rows',
        ) );
        $wp_customize->add_control( "vance_kb_posters_rows_{$cat->term_id}", array(
            'label'       => __( 'Poster rows', 'vance-health-hub' ),
            'description' => __( 'How many rows of poster cards to show (Posters layout only).', 'vance-health-hub' ),
            'section'     => $kb_section,
            'type'        => 'select',
            'choices'     => array(
                '1' => __( '1 row', 'vance-health-hub' ),
                '2' => __( '2 rows', 'vance-health-hub' ),
                '3' => __( '3 rows', 'vance-health-hub' ),
            ),
        ) );

        // Accent colour — used on the section's left-of-heading color bar
        // and (in bento layout) on the Featured tag pill and side-cell meta.
        // Previous behaviour: random pick from 5 hardcoded colours every page
        // load. Now driven by a per-category Customizer value with a
        // deterministic fallback (cycles through the original 5-colour
        // palette by term_id, so unset categories still get a stable colour).
        $vance_kb_accent_palette = array( '#F59E0B', '#0EA5E9', '#008080', '#10B981', '#8B5CF6' );
        $kb_accent_default       = $vance_kb_accent_palette[ ( (int) $cat->term_id ) % count( $vance_kb_accent_palette ) ];

        $wp_customize->add_setting( "vance_kb_accent_{$cat->term_id}", array(
            'default'           => $kb_accent_default,
            'sanitize_callback' => 'sanitize_hex_color',
        ) );
        $wp_customize->add_control( new WP_Customize_Color_Control(
            $wp_customize,
            "vance_kb_accent_{$cat->term_id}",
            array(
                'label'       => __( 'Accent Colour', 'vance-health-hub' ),
                'description' => __( 'Sets the colour bar next to the section heading, and the Featured tag colour in Bento layout.', 'vance-health-hub' ),
                'section'     => $kb_section,
            )
        ) );

        // Title colour — overrides the default theme heading colour on the H2
        // displayed next to the accent bar. Default #0f172a matches the
        // existing dark heading colour, so unset categories look unchanged.
        $wp_customize->add_setting( "vance_kb_title_color_{$cat->term_id}", array(
            'default'           => '#0f172a',
            'sanitize_callback' => 'sanitize_hex_color',
        ) );
        $wp_customize->add_control( new WP_Customize_Color_Control(
            $wp_customize,
            "vance_kb_title_color_{$cat->term_id}",
            array(
                'label'       => __( 'Title Colour', 'vance-health-hub' ),
                'description' => __( 'Colour of the category heading (H2) shown next to the accent bar.', 'vance-health-hub' ),
                'section'     => $kb_section,
            )
        ) );
    }

    // 7. Scripts & Analytics
    $wp_customize->add_section( 'vance_scripts', array(
        'title'       => __( 'Scripts', 'vance-health-hub' ),
        'priority'    => 40,
        'description' => __( 'Add Google Analytics (GA4), Tag Manager, or other tracking scripts.', 'vance-health-hub' ),
        'panel'       => 'vance_advanced_panel',
    ) );

    $wp_customize->add_setting( 'vance_header_scripts', array(
        'default'           => '',
        'sanitize_callback' => 'vance_sanitize_scripts', // We need to allow script tags
    ) );
    $wp_customize->add_control( 'vance_header_scripts', array(
        'label'       => __( 'Header Scripts (Before </head>)', 'vance-health-hub' ),
        'section'     => 'vance_scripts',
        'type'        => 'textarea',
    ) );

    $wp_customize->add_setting( 'vance_footer_scripts', array(
        'default'           => '',
        'sanitize_callback' => 'vance_sanitize_scripts',
    ) );
    $wp_customize->add_control( 'vance_footer_scripts', array(
        'label'       => __( 'Footer Scripts (Before </body>)', 'vance-health-hub' ),
        'section'     => 'vance_scripts',
        'type'        => 'textarea',
    ) );

    // Move core Site Identity section to our panel
    if ( $wp_customize->get_section( 'title_tagline' ) ) {
        $wp_customize->get_section( 'title_tagline' )->panel = 'vance_brand_panel';
        $wp_customize->get_section( 'title_tagline' )->title = __( 'Site Title & Logo', 'vance-health-hub' );
    }
}
add_action( 'customize_register', 'vance_customize_register' );

/**
 * Sanitize scripts (allow HTML/Script tags)
 * WARNING: Only for trusted admin users
 */
function vance_sanitize_scripts( $input ) {
    return $input; // Allow everything for admins
}

/**
 * Checkbox sanitization callback
 */
function vance_sanitize_checkbox( $checked ) {
    return ( ( isset( $checked ) && true == $checked ) ? true : false );
}

/**
 * Whitelist a text-align keyword.
 *
 * These values are printed straight into a CSS declaration, so sanitize_text_field()
 * (which several older select controls in this file still use) is not enough — it
 * would happily pass `left; position:fixed; top:0` through into the stylesheet.
 * Anything not in the list falls back to the caller's default.
 *
 * @param string $value    Submitted value.
 * @param string $fallback Returned when $value isn't a known keyword.
 * @return string One of left|center|right|justify.
 */
function vance_sanitize_text_align( $value, $fallback = 'center' ) {
    $allowed = array( 'left', 'center', 'right', 'justify' );

    // Used BOTH as a Customizer sanitize_callback and as a plain guard at render
    // time. WP calls a sanitize_callback as ( $value, WP_Customize_Setting ), so
    // $fallback arrives as an object on that path — returning it unchecked would
    // write an object into the theme mod. Normalise before trusting it.
    if ( ! is_string( $fallback ) || ! in_array( $fallback, $allowed, true ) ) {
        $fallback = 'center';
    }

    return in_array( $value, $allowed, true ) ? $value : $fallback;
}

/**
 * Allowed layout keys for sub-category groups on the Clinical Reviews and
 * Gastro Living category archives.
 *
 * @return array slug => human label
 */
function vance_subcat_layout_choices() {
    return array(
        'grid'          => __( 'Standard Grid', 'vance-health-hub' ),
        'bento'         => __( 'Bento', 'vance-health-hub' ),
        'asymmetric'    => __( 'Asymmetric', 'vance-health-hub' ),
        'posters'       => __( 'Posters', 'vance-health-hub' ),
        'featured_list' => __( 'Featured + List (homepage style)', 'vance-health-hub' ),
    );
}

/**
 * Sanitize a sub-category layout choice — falls back to 'grid' if the
 * submitted value is not one of the registered layouts.
 */
function vance_sanitize_subcat_layout( $value ) {
    $value = is_string( $value ) ? $value : '';
    return array_key_exists( $value, vance_subcat_layout_choices() ) ? $value : 'grid';
}

/**
 * Parent category slugs whose child terms get per-sub-category layout +
 * description controls and grouped archive rendering. Extend this list to
 * roll the feature out to further categories.
 *
 * @return array of category slugs
 */
function vance_grouped_archive_parent_slugs() {
    return apply_filters( 'vance_grouped_archive_parent_slugs', array(
        'content-clinical-reviews',
        'content-gastro-living',
    ) );
}

/**
 * Resolve the layout chosen for a given sub-category term id (defaults grid).
 */
function vance_get_subcat_layout( $term_id ) {
    return vance_sanitize_subcat_layout( vance_get_theme_mod( "vance_subcat_layout_{$term_id}", 'grid' ) );
}

/**
 * Resolve the description for a given sub-category term. Falls back to the
 * term's own WordPress description when no Customizer override is set.
 */
function vance_get_subcat_description( $term ) {
    $custom = vance_get_theme_mod( "vance_subcat_desc_{$term->term_id}", '' );
    if ( '' !== trim( (string) $custom ) ) {
        return $custom;
    }
    return $term->description;
}

/**
 * Resolve the display order for a sub-category group (lower = first).
 */
function vance_get_subcat_order( $term_id ) {
    return (int) vance_get_theme_mod( "vance_subcat_order_{$term_id}", 10 );
}

/**
 * Build the inline style string for the category hero tagline (eyebrow),
 * combining the base typography with the global Customizer styling controls
 * (text colour, background pill, border). Shared by archive.php and the
 * grouped sub-category templates so both stay in sync.
 *
 * @return string ready for echo into a style="" attribute (already escaped-safe values).
 */
function vance_category_tagline_style() {
    $text   = vance_get_theme_mod( 'vance_cat_tagline_text_color', '#008080' );
    $bg     = vance_get_theme_mod( 'vance_cat_tagline_bg', '' );
    $bcolor = vance_get_theme_mod( 'vance_cat_tagline_border_color', '' );
    $bwidth = (int) vance_get_theme_mod( 'vance_cat_tagline_border_width', 0 );

    $text = sanitize_hex_color( $text ) ? $text : '#008080';

    $style  = 'text-transform: uppercase; letter-spacing: 1px; font-weight: 600; font-size: 14px; margin-bottom: 10px;';
    $style .= ' color: ' . $text . ';';

    $is_pill = ( sanitize_hex_color( $bg ) || ( $bwidth > 0 && sanitize_hex_color( $bcolor ) ) );
    // inline-block so a background/border hugs the text; plain block otherwise.
    $style .= $is_pill ? ' display: inline-block; padding: 5px 14px; border-radius: var(--radius-control, 6px);' : ' display: block;';

    if ( sanitize_hex_color( $bg ) ) {
        $style .= ' background-color: ' . $bg . ';';
    }
    if ( $bwidth > 0 && sanitize_hex_color( $bcolor ) ) {
        $style .= ' border: ' . $bwidth . 'px solid ' . $bcolor . ';';
    }
    return $style;
}

/**
 * Bento sub-options. Count = how many small cards sit beside the main feature
 * (2 or 4); side = whether the main feature sits on the left or right.
 */
function vance_sanitize_bento_count( $v ) {
    return in_array( $v, array( '2', '4' ), true ) ? $v : '2';
}
function vance_sanitize_bento_side( $v ) {
    return in_array( $v, array( 'left', 'right' ), true ) ? $v : 'left';
}
function vance_get_subcat_bento_count( $term_id ) {
    return vance_sanitize_bento_count( (string) vance_get_theme_mod( "vance_subcat_bento_count_{$term_id}", '2' ) );
}
function vance_get_subcat_bento_side( $term_id ) {
    return vance_sanitize_bento_side( (string) vance_get_theme_mod( "vance_subcat_bento_side_{$term_id}", 'left' ) );
}

/**
 * Posters sub-option. Columns = how many poster cards sit in each row (3 or 4).
 * Only takes effect when the sub-category Layout = Posters.
 */
function vance_sanitize_posters_cols( $v ) {
    return in_array( $v, array( '3', '4' ), true ) ? $v : '3';
}
function vance_get_subcat_posters_cols( $term_id ) {
    return vance_sanitize_posters_cols( (string) vance_get_theme_mod( "vance_subcat_posters_cols_{$term_id}", '3' ) );
}

/**
 * Homepage Knowledge Base "Posters" layout sub-options. Unlike the sub-category
 * posters (3 or 4 cols, with a separate rows cap), the homepage KB grid fetches
 * exactly columns x rows posts, so columns allow 3/4/5 and rows allow 1/2/3.
 */
function vance_sanitize_kb_posters_cols( $v ) {
    return in_array( $v, array( '3', '4', '5' ), true ) ? $v : '3';
}
function vance_get_kb_posters_cols( $term_id ) {
    return (int) vance_sanitize_kb_posters_cols( (string) vance_get_theme_mod( "vance_kb_posters_cols_{$term_id}", '3' ) );
}
function vance_sanitize_kb_posters_rows( $v ) {
    return in_array( $v, array( '1', '2', '3' ), true ) ? $v : '2';
}
function vance_get_kb_posters_rows( $term_id ) {
    return (int) vance_sanitize_kb_posters_rows( (string) vance_get_theme_mod( "vance_kb_posters_rows_{$term_id}", '2' ) );
}

/**
 * Standard Grid sub-option. Columns = how many article cards sit in each row
 * (3, 4 or 5). Only takes effect when the sub-category Layout = Standard Grid.
 */
function vance_sanitize_grid_cols( $v ) {
    return in_array( $v, array( '3', '4', '5' ), true ) ? $v : '3';
}
function vance_get_subcat_grid_cols( $term_id ) {
    return vance_sanitize_grid_cols( (string) vance_get_theme_mod( "vance_subcat_grid_cols_{$term_id}", '3' ) );
}

/**
 * Same option, scoped to a whole category rather than a sub-category — used
 * by category-content-healthcare-news.php, whose sections are computed date
 * buckets rather than real sub-category terms.
 */
function vance_get_cat_grid_cols( $term_id ) {
    return vance_sanitize_grid_cols( (string) vance_get_theme_mod( "vance_cat_grid_cols_{$term_id}", '3' ) );
}

/**
 * "Rows to show" cap for the Standard Grid, Posters and Asymmetric layouts.
 * The article limit is rows x per-row (per-row depends on the layout's column
 * count); 0 means show every article in the group. Extras remain reachable via
 * the group's "View all" link.
 */
function vance_sanitize_rows( $v ) {
    $v = (int) $v;
    if ( $v < 0 ) { $v = 0; }
    if ( $v > 6 ) { $v = 6; }
    return (string) $v;
}
function vance_get_subcat_rows( $term_id ) {
    return (int) vance_get_theme_mod( "vance_subcat_rows_{$term_id}", 0 );
}

/**
 * Grouped category archives (Clinical Reviews / Gastro Living) show every
 * sub-category on a single page — so load all matching posts and suppress
 * pagination. Other archives are untouched.
 */
function vance_grouped_archive_no_pagination( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }
    if ( ! $query->is_category() ) {
        return;
    }
    $slugs = vance_grouped_archive_parent_slugs();
    $cat   = $query->get_queried_object();
    // $cat may not be populated this early; fall back to the requested category var.
    $slug  = ( $cat instanceof WP_Term ) ? $cat->slug : $query->get( 'category_name' );
    if ( $slug && in_array( $slug, $slugs, true ) ) {
        $query->set( 'posts_per_page', -1 );
        $query->set( 'nopaging', true );
    }
}
add_action( 'pre_get_posts', 'vance_grouped_archive_no_pagination' );

/**
 * Get category choices for Customizer (all categories)
 */
function vance_get_category_choices() {
    $categories = get_categories( array( 'hide_empty' => false ) );
    $choices = array( '0' => 'All Categories' );
    foreach ( $categories as $cat ) {
        $choices[$cat->term_id] = $cat->name;
    }
    return $choices;
}

/**
 * Get category choices scoped to Content Hub Station CPT categories.
 * These categories are auto-assigned when content is created via the CPTs
 * registered in vance_register_cpts() (news, research, oped, etc.).
 */
function vance_get_cpt_category_choices() {
    // Map of CPT slugs => auto-assigned category names (mirrors vance_auto_assign_category)
    $cpt_category_names = array(
        'Healthcare News',
        'Clinical Reviews',
        'Expert Opinions',
        'Tools & Resources',
        'Media Library',
        'Webinars',
        'Education Courses',
        'Infographic Gallery',
    );

    $choices = array( '0' => 'All Content Types' );

    foreach ( $cpt_category_names as $name ) {
        $term = get_term_by( 'name', $name, 'category' );
        if ( $term && ! is_wp_error( $term ) ) {
            $choices[ $term->term_id ] = $term->name;
        }
    }

    return $choices;
}

/**
 * Social Media Meta Box
 */
function vance_add_social_share_meta_box() {
    $post_types = array_merge( array( 'post' ), array( 'news', 'research', 'oped', 'review', 'whitepaper', 'podcast', 'webinar', 'course', 'infographic' ) );
    add_meta_box(
        'vance_social_share',
        __( 'Social Media Automation', 'vance-health-hub' ),
        'vance_render_social_share_meta_box',
        $post_types,
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'vance_add_social_share_meta_box' );

function vance_render_social_share_meta_box( $post ) {
    $share_on_publish = get_post_meta( $post->ID, '_sla_share_on_publish', true );
    $channels = get_post_meta( $post->ID, '_sla_social_channels', true ) ?: array();
    $custom_msg = get_post_meta( $post->ID, '_sla_social_message', true );
    
    wp_nonce_field( 'vance_social_share_save', 'vance_social_share_nonce' );
    ?>
    <div style="margin-top: 10px;">
        <label style="font-weight: 600; display: block; margin-bottom: 8px;">
            <input type="checkbox" name="vance_share_on_publish" value="1" <?php checked( $share_on_publish, '1' ); ?>>
            <?php _e( 'Enable Auto-Post', 'vance-health-hub' ); ?>
        </label>
        
        <div id="vance-social-channels" style="margin-left: 24px; margin-bottom: 12px; <?php echo $share_on_publish ? '' : 'display:none;'; ?>">
            <p style="margin-bottom: 4px; font-weight: 600; font-size: 12px; color: #64748b;">Select Channels:</p>
            <label style="display: block; margin-bottom: 4px;">
                <input type="checkbox" name="vance_social_channels[]" value="linkedin" <?php checked( in_array('linkedin', $channels) ); ?>> LinkedIn
            </label>
            <label style="display: block; margin-bottom: 4px;">
                <input type="checkbox" name="vance_social_channels[]" value="twitter" <?php checked( in_array('twitter', $channels) ); ?>> X (Twitter)
            </label>
            <label style="display: block; margin-bottom: 4px;">
                <input type="checkbox" name="vance_social_channels[]" value="facebook" <?php checked( in_array('facebook', $channels) ); ?>> Facebook
            </label>
        </div>

        <div id="vance-social-message" style="margin-left: 0; margin-top: 12px; <?php echo $share_on_publish ? '' : 'display:none;'; ?>">
            <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 12px;">Custom Message (Optional)</label>
            <textarea name="vance_social_message" rows="3" style="width: 100%;" placeholder="Enter custom caption here..."><?php echo esc_textarea( $custom_msg ); ?></textarea>
            <p class="description" style="font-size: 11px;">If empty, the excerpt will be used.</p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('input[name="vance_share_on_publish"]').change(function() {
                if($(this).is(':checked')) {
                    $('#vance-social-channels, #vance-social-message').slideDown();
                } else {
                    $('#vance-social-channels, #vance-social-message').slideUp();
                }
            });
        });
        </script>
    </div>
    <?php
}

function vance_save_social_share_meta( $post_id ) {
    if ( ! isset( $_POST['vance_social_share_nonce'] ) || ! wp_verify_nonce( $_POST['vance_social_share_nonce'], 'vance_social_share_save' ) ) {
        return;
    }
    
    $share = isset( $_POST['vance_share_on_publish'] ) ? '1' : '0';
    update_post_meta( $post_id, '_sla_share_on_publish', $share );
    
    $channels = isset( $_POST['vance_social_channels'] ) ? (array) $_POST['vance_social_channels'] : array();
    update_post_meta( $post_id, '_sla_social_channels', $channels );
    
    if ( isset( $_POST['vance_social_message'] ) ) {
        update_post_meta( $post_id, '_sla_social_message', sanitize_textarea_field( $_POST['vance_social_message'] ) );
    }
}
add_action( 'save_post', 'vance_save_social_share_meta' );

/**
 * Trigger Social Share on Publish
 */
function vance_trigger_social_share( $new_status, $old_status, $post ) {
    if ( 'publish' !== $new_status || 'publish' === $old_status ) {
        return;
    }

    $should_share = get_post_meta( $post->ID, '_sla_share_on_publish', true );
    if ( '1' !== $should_share ) {
        return;
    }

    $webhook_url = vance_get_theme_mod( 'vance_social_webhook_url' );
    if ( empty( $webhook_url ) ) {
        return;
    }
    
    $channels = get_post_meta( $post->ID, '_sla_social_channels', true ) ?: array();
    $custom_msg = get_post_meta( $post->ID, '_sla_social_message', true );
    $description = $custom_msg ?: get_the_excerpt( $post );
    $featured_image = get_the_post_thumbnail_url( $post->ID, 'full' );

    $payload = array(
        'title'       => get_the_title( $post ),
        'url'         => get_permalink( $post ),
        'description' => $description,
        'image'       => $featured_image,
        'channels'    => $channels,
        'post_type'   => $post->post_type,
        'date'        => $post->post_date,
        'author'      => get_the_author_meta( 'display_name', $post->post_author ),
    );

    // Send to Webhook
    wp_remote_post( $webhook_url, array(
        'method'      => 'POST',
        'timeout'     => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking'    => false, 
        'headers'     => array( 'Content-Type' => 'application/json' ),
        'body'        => json_encode( $payload ),
        'cookies'     => array(),
    ) );
}
add_action( 'transition_post_status', 'vance_trigger_social_share', 10, 3 );
/**
 * Add Custom Roles
 */
function vance_setup_custom_roles() {
    add_role( 'practitioner', __( 'Practitioner', 'vance-health-hub' ), array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
    ));
    add_role( 'member', __( 'Member', 'vance-health-hub' ), array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
    ));

    // One-time migration: any user still on the legacy 'patient' role slug
    // (only possible via un-overridden Google OAuth signups) gets moved to
    // 'member', then the orphaned role definition is removed.
    if ( ! get_option( 'vance_member_role_migrated' ) ) {
        $legacy_patients = get_users( array( 'role' => 'patient', 'fields' => 'ID' ) );
        foreach ( $legacy_patients as $uid ) {
            ( new WP_User( $uid ) )->set_role( 'member' );
            update_user_meta( $uid, '_sla_user_type', 'member' );
            update_user_meta( $uid, '_sla_dashboard_role', 'member' );
        }
        remove_role( 'patient' );
        update_option( 'vance_member_role_migrated', 1 );
    }
}
add_action( 'init', 'vance_setup_custom_roles' );

/**
 * Referral link attribution: capture `?ref=CODE` into a 30-day cookie so the
 * credit-on-signup logic (vance_credit_referral_signup(), inc/dashboard-functions.php)
 * can find it even if the visitor doesn't sign up on their first page view.
 */
function vance_capture_referral_cookie() {
    if ( ! empty( $_GET['ref'] ) && ! isset( $_COOKIE['vance_ref'] ) && ! headers_sent() ) {
        $code = sanitize_text_field( wp_unslash( $_GET['ref'] ) );
        setcookie( 'vance_ref', $code, time() + 30 * DAY_IN_SECONDS, '/', '', is_ssl(), true );
    }
}
add_action( 'init', 'vance_capture_referral_cookie' );

/**
 * Authentication & Redirects
 */

// 1. Custom Login Logo
function vance_login_logo() { 
    ?> 
    <style type="text/css"> 
        #login h1 a, .login h1 a {
            background-image: url(<?php echo get_template_directory_uri(); ?>/assets/img/logo.png);
            height: 100px; 
            width: 300px; 
            background-size: contain; 
            background-repeat: no-repeat; 
            padding-bottom: 30px; 
        }
        body.login { background-color: #f8fafc; }
        .login form { box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-radius: var(--radius-surface, 14px); border: 1px solid #e2e8f0; }
        .wp-core-ui .button-primary { background: #008080; border-color: #008080; }
    </style>
    <?php 
}
add_action( 'login_enqueue_scripts', 'vance_login_logo' );

function vance_login_logo_url() {
    return home_url();
}
add_filter( 'login_headerurl', 'vance_login_logo_url' );

// 2. Login Redirect to Dashboard
function vance_login_redirect( $redirect_to, $request, $user ) {
    // Is there a user to check?
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        //check for admins
        if ( in_array( 'administrator', $user->roles ) ) {
            // Only redirect if no specific destination is set
            return ( $redirect_to && $redirect_to != admin_url() ) ? $redirect_to : admin_url();
        } else {
            return home_url( '/dashboard/' );
        }
    } else {
        return $redirect_to;
    }
}
add_filter( 'login_redirect', 'vance_login_redirect', 10, 3 );

/**
 * Default new signups to 'member' role
 */
function vance_default_user_role_on_register( $user_id ) {
    $user = new WP_User( $user_id );
    $user->set_role( 'member' );

    update_user_meta( $user_id, '_sla_user_type', 'member' );
    update_user_meta( $user_id, '_sla_dashboard_role', 'member' );
}
add_action( 'user_register', 'vance_default_user_role_on_register' );

/**
 * Rename 'Subscriber' role to 'Patient'
 */
function vance_rename_subscriber_role() {
    $role = get_role( 'subscriber' );
    if ( $role ) {
        // Just checking if we can safely rename it without global object mutation
        // but actually, maybe it's better to NOT do this if it's causing plugin issues.
    }
}
// add_action( 'init', 'vance_rename_subscriber_role' );

/**
 * Redirect default WP registration to custom registration page
 */
function vance_custom_registration_url($register_url) {
    return home_url('/register/');
}
add_filter('register_url', 'vance_custom_registration_url');

/**
 * Enhanced Login Page Styling
 */
function vance_enhanced_login_styles() {
    ?>
    <style type="text/css">
        body.login {
            background: linear-gradient(135deg, #0A1929 0%, #112240 100%);
        }
        
        #login {
            padding-top: 5%;
        }
        
        .login form {
            background: white;
            border-radius: var(--radius-surface, 14px);
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: none;
        }
        
        .login form .input {
            border-radius: var(--radius-field, 10px);
            border: 2px solid #E2E8F0;
            padding: 8px 12px;
            font-size: 14px;
        }
        
        .login form .input:focus {
            border-color: #008080;
            box-shadow: 0 0 0 3px rgba(0,128,128, 0.1);
        }
        
        .wp-core-ui .button-primary {
            background: #008080;
            border-color: #008080;
            border-radius: var(--radius-control, 6px);
            padding: 8px 24px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(0,128,128, 0.3);
            transition: all 0.2s;
        }
        
        .wp-core-ui .button-primary:hover {
            background: #e65100;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,128,128, 0.4);
        }
        
        #login form p {
            margin-bottom: 16px;
        }
        
        .login #nav a,
        .login #backtoblog a {
            color: white;
            font-weight: 600;
        }
        
        .login #nav a:hover,
        .login #backtoblog a:hover {
            color: #008080;
        }
        
        .login .message,
        .login .success {
            background: #def4f4;
            border-left: 4px solid #008080;
            border-radius: var(--radius-surface, 14px);
            padding: 12px 16px;
        }
        
        .login #login_error {
            background: #FEE2E2;
            border-left: 4px solid #EF4444;
            border-radius: var(--radius-surface, 14px);
            padding: 12px 16px;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'vance_enhanced_login_styles');

/**
 * Add "Create Account" link to login page
 */
function vance_add_register_link_to_login() {
    echo '<p style="text-align: center; margin-top: 20px;">
        <a href="' . home_url('/register/') . '" style="color: white; font-weight: 600; text-decoration: none; background: #008080; padding: 10px 24px; border-radius: var(--radius-control, 6px); display: inline-block; box-shadow: 0 4px 12px rgba(0,128,128, 0.3);">Create New Account</a>
    </p>';
}
add_action('login_footer', 'vance_add_register_link_to_login');

/**
 * Handle role-based registration redirect
 */
function vance_handle_registration_redirect() {
    if (isset($_GET['role']) && !is_user_logged_in()) {
        $role = sanitize_text_field($_GET['role']);
        if (in_array($role, array('practitioner', 'patient'))) {
            setcookie('vance_pending_role', $role, time() + 3600, '/');
        }
    }
}
add_action('init', 'vance_handle_registration_redirect');

/**
 * Set user role from cookie on registration
 */
function vance_set_role_from_cookie($user_id) {
    if (isset($_COOKIE['vance_pending_role'])) {
        $role = sanitize_text_field($_COOKIE['vance_pending_role']);
        $user = new WP_User($user_id);
        
        if ($role === 'practitioner') {
            $user->set_role('practitioner');
            update_user_meta($user_id, '_sla_user_type', 'practitioner');
            update_user_meta($user_id, '_sla_dashboard_role', 'practitioner');
        } else {
            $user->set_role('member');
            update_user_meta($user_id, '_sla_user_type', 'member');
            update_user_meta($user_id, '_sla_dashboard_role', 'member');
        }
        
        // Clear cookie
        setcookie('vance_pending_role', '', time() - 3600, '/');
    }
}
add_action('user_register', 'vance_set_role_from_cookie', 20);

/**
 * Hide Admin Bar for non-administrators
 */
function vance_hide_admin_bar() {
    if ( ! current_user_can( 'administrator' ) ) {
        show_admin_bar( false );
    }
}
add_action( 'after_setup_theme', 'vance_hide_admin_bar' );

/**
 * ==========================================
 * TESTIMONIALS SYSTEM
 * ==========================================
 */

/**
 * 1. Register Testimonial Post Type
 */
function vance_register_testimonial_cpt() {
    $labels = array(
        'name'                  => _x( 'Testimonials', 'Post Type General Name', 'vance-health-hub' ),
        'singular_name'         => _x( 'Testimonial', 'Post Type Singular Name', 'vance-health-hub' ),
        'menu_name'             => __( 'Testimonials', 'vance-health-hub' ),
        'all_items'             => __( 'All Testimonials', 'vance-health-hub' ),
        'add_new_item'          => __( 'Add New Testimonial', 'vance-health-hub' ),
        'new_item'              => __( 'New Testimonial', 'vance-health-hub' ),
    );
    $args = array(
        'label'                 => __( 'Testimonial', 'vance-health-hub' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail', 'custom-fields' ), 
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 20,
        'menu_icon'             => 'dashicons-format-quote',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false, 
        'capability_type'       => 'post',
    );
    register_post_type( 'testimonial', $args );
}
add_action( 'init', 'vance_register_testimonial_cpt' );

/**
 * 2. Add Customizer Settings for Testimonials
 */
function vance_customize_testimonials( $wp_customize ) {
    // Section
    $wp_customize->add_section( 'vance_testimonials_section', array(
        'title'    => __( 'Testimonials', 'vance-health-hub' ),
        'priority' => 45,
        'panel'    => 'vance_content_panel',
        'description' => 'Manage the "What Our Community Says" section.'
    ) );

    // Toggle Display
    $wp_customize->add_setting( 'vance_show_testimonials', array(
        'default'           => true,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_show_testimonials', array(
        'label'    => __( 'Show Section', 'vance-health-hub' ),
        'section'  => 'vance_testimonials_section',
        'type'     => 'checkbox',
    ) );

    // Heading
    $wp_customize->add_setting( 'vance_testimonial_heading', array(
        'default'           => 'What Our Community Says',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_testimonial_heading', array(
        'label'   => __( 'Section Heading', 'vance-health-hub' ),
        'section' => 'vance_testimonials_section',
        'type'    => 'text',
    ) );

    // Heading alignment
    $wp_customize->add_setting( 'vance_testimonial_heading_align', array(
        'default'           => 'left',
        'sanitize_callback' => 'sanitize_key',
    ) );
    $wp_customize->add_control( 'vance_testimonial_heading_align', array(
        'label'   => __( 'Heading Alignment', 'vance-health-hub' ),
        'section' => 'vance_testimonials_section',
        'type'    => 'select',
        'choices' => array(
            'left'   => 'Left',
            'center' => 'Center',
            'right'  => 'Right',
        ),
    ) );

    // Selection Mode
    $wp_customize->add_setting( 'vance_testimonial_select_type', array(
        'default'           => 'latest',
        'sanitize_callback' => 'sanitize_key',
    ) );
    $wp_customize->add_control( 'vance_testimonial_select_type', array(
        'label'   => __( 'Selection Mode', 'vance-health-hub' ),
        'section' => 'vance_testimonials_section',
        'type'    => 'select',
        'choices' => array(
            'latest' => 'Latest Published',
            'manual' => 'Manual Selection (IDs)'
        )
    ) );

    // Manual IDs
    $wp_customize->add_setting( 'vance_testimonial_ids', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_testimonial_ids', array(
        'label'       => __( 'Specific Testimonial IDs', 'vance-health-hub' ),
        'description' => __( 'Comma-separated list (e.g. 104, 156). Ignored if mode is "Latest".', 'vance-health-hub' ),
        'section'     => 'vance_testimonials_section',
        'type'        => 'text',
    ) );

    // Count
    $wp_customize->add_setting( 'vance_testimonial_count', array(
        'default'           => 3,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_testimonial_count', array(
        'label'       => __( 'Number to Show', 'vance-health-hub' ),
        'section'     => 'vance_testimonials_section',
        'type'    => 'number',
        'input_attrs' => array( 'min' => 1, 'max' => 9 ),
    ) );

    // Inline testimonials — used as fallback when no Testimonial CPT posts exist.
    // Lets editors author testimonials directly in the Customizer without creating posts.
    $inline_defaults = array(
        1 => array(
            'quote' => 'Vance Medical Hub has transformed how I manage my IBD. The clinical reviews are clear, current, and genuinely useful between consultant appointments.',
            'name'  => 'Sarah J.',
            'role'  => 'Living with Crohn\'s for 8 years',
        ),
        2 => array(
            'quote' => 'As a gastroenterology nurse I recommend this site to patients every week. The evidence-based summaries save them hours of confused Googling.',
            'name'  => 'Dr Imran K.',
            'role'  => 'IBD Specialist Nurse',
        ),
        3 => array(
            'quote' => 'The malnutrition calculator and recipe tools are the most practical resources I\'ve found anywhere. Finally something built for the patient, not the clinician.',
            'name'  => 'Marcus T.',
            'role'  => 'Ulcerative Colitis, diagnosed 2022',
        ),
    );

    foreach ( $inline_defaults as $i => $d ) {
        $wp_customize->add_setting( "vance_testimonial_inline_{$i}_quote", array(
            'default'           => $d['quote'],
            'sanitize_callback' => 'wp_kses_post',
        ) );
        $wp_customize->add_control( "vance_testimonial_inline_{$i}_quote", array(
            'label'       => sprintf( __( 'Testimonial %d - Quote', 'vance-health-hub' ), $i ),
            'description' => 1 === $i ? __( 'Inline testimonials show when no Testimonial posts exist. Leave Quote blank to skip a slot.', 'vance-health-hub' ) : '',
            'section'     => 'vance_testimonials_section',
            'type'        => 'textarea',
        ) );

        $wp_customize->add_setting( "vance_testimonial_inline_{$i}_name", array(
            'default'           => $d['name'],
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( "vance_testimonial_inline_{$i}_name", array(
            'label'   => sprintf( __( 'Testimonial %d - Name', 'vance-health-hub' ), $i ),
            'section' => 'vance_testimonials_section',
            'type'    => 'text',
        ) );

        $wp_customize->add_setting( "vance_testimonial_inline_{$i}_role", array(
            'default'           => $d['role'],
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( "vance_testimonial_inline_{$i}_role", array(
            'label'   => sprintf( __( 'Testimonial %d - Role / Subtitle', 'vance-health-hub' ), $i ),
            'section' => 'vance_testimonials_section',
            'type'    => 'text',
        ) );

        $wp_customize->add_setting( "vance_testimonial_inline_{$i}_image", array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_testimonial_inline_{$i}_image", array(
            'label'   => sprintf( __( 'Testimonial %d - Image (optional)', 'vance-health-hub' ), $i ),
            'section' => 'vance_testimonials_section',
        ) ) );
    }

    // ---------- Styling: layout, colours, font sizes ----------
    $style_fields = array(
        // Layout
        'vance_testimonial_pad_top'        => array( 'default' => 100, 'label' => 'Section Padding Top (px)',    'type' => 'number', 'sanitize' => 'absint' ),
        'vance_testimonial_pad_bottom'     => array( 'default' => 100, 'label' => 'Section Padding Bottom (px)', 'type' => 'number', 'sanitize' => 'absint' ),

        // Colours
        'vance_testimonial_section_bg'     => array( 'default' => '#F8FAFC', 'label' => 'Section Background',           'type' => 'color' ),
        'vance_testimonial_border_color'   => array( 'default' => '#e2e8f0', 'label' => 'Section Top Border',           'type' => 'color' ),
        'vance_testimonial_underline_color'=> array( 'default' => '#e5e7eb', 'label' => 'Heading Underline Colour',     'type' => 'color' ),
        'vance_testimonial_accent_color'   => array( 'default' => '#008080', 'label' => 'Accent Colour (bar + icon)',   'type' => 'color' ),
        'vance_testimonial_heading_color'  => array( 'default' => '#0A1929', 'label' => 'Heading Colour',               'type' => 'color' ),
        'vance_testimonial_card_bg'        => array( 'default' => '#ffffff', 'label' => 'Card Background',           'type' => 'color' ),
        'vance_testimonial_card_border'    => array( 'default' => '#e2e8f0', 'label' => 'Card Border',               'type' => 'color' ),
        'vance_testimonial_quote_color'    => array( 'default' => '#475569', 'label' => 'Quote Text Colour',         'type' => 'color' ),
        'vance_testimonial_name_color'     => array( 'default' => '#0f172a', 'label' => 'Author Name Colour',        'type' => 'color' ),
        'vance_testimonial_role_color'     => array( 'default' => '#64748b', 'label' => 'Role / Subtitle Colour',    'type' => 'color' ),
        'vance_testimonial_avatar_bg'      => array( 'default' => '#0A1929', 'label' => 'Avatar Fallback Background','type' => 'color' ),
        'vance_testimonial_avatar_color'   => array( 'default' => '#ffffff', 'label' => 'Avatar Fallback Text',      'type' => 'color' ),

        // Font sizes
        'vance_testimonial_heading_size'   => array( 'default' => 24, 'label' => 'Heading Font Size (px)',     'type' => 'number', 'sanitize' => 'absint' ),
        'vance_testimonial_quote_size'     => array( 'default' => 16, 'label' => 'Quote Font Size (px)',       'type' => 'number', 'sanitize' => 'absint' ),
        'vance_testimonial_name_size'      => array( 'default' => 16, 'label' => 'Author Name Font Size (px)', 'type' => 'number', 'sanitize' => 'absint' ),
        'vance_testimonial_role_size'      => array( 'default' => 12, 'label' => 'Role Font Size (px)',        'type' => 'number', 'sanitize' => 'absint' ),
    );

    foreach ( $style_fields as $setting_id => $cfg ) {
        $sanitize = isset( $cfg['sanitize'] )
            ? $cfg['sanitize']
            : ( 'color' === $cfg['type'] ? 'sanitize_hex_color' : 'sanitize_text_field' );

        $wp_customize->add_setting( $setting_id, array(
            'default'           => $cfg['default'],
            'sanitize_callback' => $sanitize,
        ) );

        if ( 'color' === $cfg['type'] ) {
            $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $setting_id, array(
                'label'   => __( $cfg['label'], 'vance-health-hub' ),
                'section' => 'vance_testimonials_section',
            ) ) );
        } else {
            $wp_customize->add_control( $setting_id, array(
                'label'   => __( $cfg['label'], 'vance-health-hub' ),
                'section' => 'vance_testimonials_section',
                'type'    => 'number',
                'input_attrs' => array( 'min' => 0, 'max' => 400, 'step' => 1 ),
            ) );
        }
    }
}
add_action( 'customize_register', 'vance_customize_testimonials' );

/**
 * 3. Testimonials Shortcode [testimonials]
 */
function vance_testimonials_shortcode( $atts ) {
    // Check toggle
    if ( ! vance_get_theme_mod( 'vance_show_testimonials', true ) ) {
        return '';
    }

    $heading = vance_get_theme_mod( 'vance_testimonial_heading', 'What Our Community Says' );
    $mode    = vance_get_theme_mod( 'vance_testimonial_select_type', 'latest' );
    $ids_str = vance_get_theme_mod( 'vance_testimonial_ids', '' );
    $count   = vance_get_theme_mod( 'vance_testimonial_count', 3 );

    $args = array(
        'post_type'      => 'testimonial',
        'post_status'    => 'publish',
        'posts_per_page' => $count,
    );

    if ( $mode === 'manual' && ! empty( $ids_str ) ) {
        $ids = array_map( 'intval', explode( ',', $ids_str ) );
        $args['post__in'] = $ids;
        $args['orderby']  = 'post__in';
    } else {
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
    }

    $query = new WP_Query( $args );

    // Collect items from CPT, or fall back to inline Customizer testimonials.
    $items = array();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $items[] = array(
                'quote' => get_the_content(),
                'name'  => get_the_title(),
                'role'  => get_post_meta( get_the_ID(), '_testimonial_role', true ),
                'image' => has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' ) : '',
            );
        }
        wp_reset_postdata();
    } else {
        for ( $i = 1; $i <= 3; $i++ ) {
            $quote = vance_get_theme_mod( "vance_testimonial_inline_{$i}_quote", '' );
            if ( ! $quote ) {
                continue;
            }
            $items[] = array(
                'quote' => $quote,
                'name'  => vance_get_theme_mod( "vance_testimonial_inline_{$i}_name", '' ),
                'role'  => vance_get_theme_mod( "vance_testimonial_inline_{$i}_role", '' ),
                'image' => vance_get_theme_mod( "vance_testimonial_inline_{$i}_image", '' ),
            );
        }
    }

    if ( empty( $items ) ) {
        return '';
    }

    // Style tokens
    $pad_top      = absint( vance_get_theme_mod( 'vance_testimonial_pad_top', 100 ) );
    $pad_bot      = absint( vance_get_theme_mod( 'vance_testimonial_pad_bottom', 100 ) );
    $sec_bg       = vance_get_theme_mod( 'vance_testimonial_section_bg', '#F8FAFC' );
    $sec_border   = vance_get_theme_mod( 'vance_testimonial_border_color', '#e2e8f0' );
    $underline    = vance_get_theme_mod( 'vance_testimonial_underline_color', '#e5e7eb' );
    $accent       = vance_get_theme_mod( 'vance_testimonial_accent_color', '#008080' );
    $h_align_raw  = vance_get_theme_mod( 'vance_testimonial_heading_align', 'left' );
    $h_align      = in_array( $h_align_raw, array( 'left', 'center', 'right' ), true ) ? $h_align_raw : 'left';
    $h_justify    = ( 'center' === $h_align ) ? 'center' : ( ( 'right' === $h_align ) ? 'flex-end' : 'flex-start' );
    $heading_col  = vance_get_theme_mod( 'vance_testimonial_heading_color', '#0A1929' );
    $card_bg      = vance_get_theme_mod( 'vance_testimonial_card_bg', '#ffffff' );
    $card_border  = vance_get_theme_mod( 'vance_testimonial_card_border', '#e2e8f0' );
    $quote_col    = vance_get_theme_mod( 'vance_testimonial_quote_color', '#475569' );
    $name_col     = vance_get_theme_mod( 'vance_testimonial_name_color', '#0f172a' );
    $role_col     = vance_get_theme_mod( 'vance_testimonial_role_color', '#64748b' );
    $avatar_bg    = vance_get_theme_mod( 'vance_testimonial_avatar_bg', '#0A1929' );
    $avatar_col   = vance_get_theme_mod( 'vance_testimonial_avatar_color', '#ffffff' );
    $heading_size = absint( vance_get_theme_mod( 'vance_testimonial_heading_size', 24 ) );
    $quote_size   = absint( vance_get_theme_mod( 'vance_testimonial_quote_size', 16 ) );
    $name_size    = absint( vance_get_theme_mod( 'vance_testimonial_name_size', 16 ) );
    $role_size    = absint( vance_get_theme_mod( 'vance_testimonial_role_size', 12 ) );

    ob_start();
    ?>
    <section class="vance-testimonials-section" style="padding: <?php echo $pad_top; ?>px 0 <?php echo $pad_bot; ?>px; background: <?php echo esc_attr( $sec_bg ); ?>; border-top: 1px solid <?php echo esc_attr( $sec_border ); ?>; position: relative; z-index: 10;">
        <div class="container">
            <?php if ( $heading ) : ?>
                <div class="section-label" style="display: flex; align-items: center; gap: 12px; margin-bottom: 40px; border-bottom: 2px solid <?php echo esc_attr( $underline ); ?>; padding-bottom: 16px; justify-content: <?php echo esc_attr( $h_justify ); ?>; text-align: <?php echo esc_attr( $h_align ); ?>;">
                    <div class="color-bar" style="background: <?php echo esc_attr( $accent ); ?>; width: 6px; height: <?php echo max( 16, $heading_size ); ?>px; border-radius: var(--radius-control, 6px);"></div>
                    <h2 style="margin: 0; font-size: <?php echo $heading_size; ?>px; font-weight: 800; color: <?php echo esc_attr( $heading_col ); ?>; font-family: 'Outfit', sans-serif; text-transform: uppercase;"><?php echo esc_html( $heading ); ?></h2>
                </div>
            <?php endif; ?>

            <?php
            // 2026-05-26: carousel rewrite. Cards shrunk ~40% (padding 40/32 -> 24/20,
            // avatar 56 -> 40). Desktop shows 5 / tablet 3 / mobile 1.2 cards.
            // Left/right arrows shift one card per click, disabled at the ends.
            // Carousel chrome only renders when items.length > visibleCount; with
            // fewer items, falls back to a centered flex row (no arrows).
            $tcount = count( $items );
            $tslider_id = 'vance-testimonials-' . wp_unique_id();
            ?>
            <div class="vance-testimonials-wrap" data-tcount="<?php echo (int) $tcount; ?>" id="<?php echo esc_attr( $tslider_id ); ?>" style="position: relative;">
                <div class="vance-testimonials-viewport" style="overflow: hidden;">
                    <div class="vance-testimonials-track" style="display: flex; gap: 16px; transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1); will-change: transform;">
                        <?php foreach ( $items as $item ) : ?>
                            <div class="vance-testimonial-card" style="flex: 0 0 auto; box-sizing: border-box; background: <?php echo esc_attr( $card_bg ); ?>; border-radius: var(--radius-surface, 14px); padding: 24px 20px; box-shadow: 0 6px 18px rgba(0,0,0,0.05); border: 1px solid <?php echo esc_attr( $card_border ); ?>; display: flex; flex-direction: column; position: relative;">
                                <div style="position: absolute; top: 14px; right: 14px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="<?php echo esc_attr( $accent ); ?>" style="opacity: 0.1;">
                                        <path d="M14.017 21L14.017 18C14.017 16.8954 14.9124 16 16.017 16H19.017C19.5693 16 20.017 15.5523 20.017 15V9C20.017 8.44772 19.5693 8 19.017 8H15.017C14.4647 8 14.017 8.44772 14.017 9V11C14.017 11.5523 13.5693 12 13.017 12H12.017V5H22.017V15C22.017 18.3137 19.3307 21 16.017 21H14.017ZM5.01697 21L5.01697 18C5.01697 16.8954 5.9124 16 7.01697 16H10.017C10.5693 16 11.017 15.5523 11.017 15V9C11.017 8.44772 10.5693 8 10.017 8H6.01697C5.46468 8 5.01697 8.44772 5.01697 9V11C5.01697 11.5523 4.56925 12 4.01697 12H3.01697V5H13.017V15C13.017 18.3137 10.3307 21 7.01697 21H5.01697Z"></path>
                                    </svg>
                                </div>

                                <div style="font-family: 'Inter', sans-serif; font-size: <?php echo max( 12, $quote_size - 2 ); ?>px; color: <?php echo esc_attr( $quote_col ); ?>; line-height: 1.55; font-style: italic; margin-bottom: 16px; flex-grow: 1; display: -webkit-box; -webkit-line-clamp: 6; -webkit-box-orient: vertical; overflow: hidden;">
                                    "<?php echo wp_kses_post( $item['quote'] ); ?>"
                                </div>

                                <div style="display: flex; align-items: center; gap: 12px; border-top: 1px solid <?php echo esc_attr( $card_border ); ?>; padding-top: 14px; margin-top: auto;">
                                    <?php if ( ! empty( $item['image'] ) ) : ?>
                                        <img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>" style="width: 40px; height: 40px; border-radius: var(--radius-control, 6px); object-fit: cover; border: 2px solid <?php echo esc_attr( $sec_bg ); ?>; flex-shrink: 0;">
                                    <?php else : ?>
                                        <div style="width: 40px; height: 40px; border-radius: var(--radius-control, 6px); background: <?php echo esc_attr( $avatar_bg ); ?>; color: <?php echo esc_attr( $avatar_col ); ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; font-family: 'Outfit', sans-serif; flex-shrink: 0;">
                                            <?php echo esc_html( strtoupper( substr( $item['name'], 0, 1 ) ) ); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div style="min-width: 0;">
                                        <h4 style="margin: 0; font-size: <?php echo max( 12, $name_size - 2 ); ?>px; font-weight: 700; color: <?php echo esc_attr( $name_col ); ?>; font-family: 'Outfit', sans-serif; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html( $item['name'] ); ?></h4>
                                        <?php if ( ! empty( $item['role'] ) ) : ?>
                                            <span style="font-size: <?php echo max( 10, $role_size - 1 ); ?>px; color: <?php echo esc_attr( $role_col ); ?>; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html( $item['role'] ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ( $tcount > 1 ) : ?>
                    <div class="vance-testimonials-controls" aria-hidden="false" style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" class="vance-tslide-prev" aria-label="Previous testimonial" style="width: 44px; height: 44px; border: 1px solid <?php echo esc_attr( $card_border ); ?>; background: <?php echo esc_attr( $card_bg ); ?>; border-radius: var(--radius-control, 6px); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; color: <?php echo esc_attr( $accent ); ?>; transition: opacity 0.2s, transform 0.2s;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        </button>
                        <button type="button" class="vance-tslide-next" aria-label="Next testimonial" style="width: 44px; height: 44px; border: 1px solid <?php echo esc_attr( $card_border ); ?>; background: <?php echo esc_attr( $card_bg ); ?>; border-radius: var(--radius-control, 6px); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; color: <?php echo esc_attr( $accent ); ?>; transition: opacity 0.2s, transform 0.2s;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <style>
                /* Card width varies by breakpoint to fit 5/3/1.2 visible.
                   Calc accounts for the 16px gap between cards. */
                #<?php echo esc_attr( $tslider_id ); ?> .vance-testimonial-card {
                    width: calc((100% - 4 * 16px) / 5);
                    min-height: 220px;
                }
                @media (max-width: 1024px) {
                    #<?php echo esc_attr( $tslider_id ); ?> .vance-testimonial-card {
                        width: calc((100% - 2 * 16px) / 3);
                    }
                }
                @media (max-width: 600px) {
                    #<?php echo esc_attr( $tslider_id ); ?> .vance-testimonial-card {
                        width: calc((100% - 0.2 * 16px) / 1.2);
                    }
                }
                #<?php echo esc_attr( $tslider_id ); ?> .vance-tslide-prev:hover:not([disabled]),
                #<?php echo esc_attr( $tslider_id ); ?> .vance-tslide-next:hover:not([disabled]) {
                    background: <?php echo esc_attr( $accent ); ?>;
                    color: #fff;
                    transform: translateY(-1px);
                }
                #<?php echo esc_attr( $tslider_id ); ?> .vance-tslide-prev[disabled],
                #<?php echo esc_attr( $tslider_id ); ?> .vance-tslide-next[disabled] {
                    opacity: 0.3;
                    cursor: not-allowed;
                }
            </style>

            <script>
            (function() {
                var root = document.getElementById('<?php echo esc_js( $tslider_id ); ?>');
                if (!root) { return; }
                var viewport = root.querySelector('.vance-testimonials-viewport');
                var track    = root.querySelector('.vance-testimonials-track');
                var prevBtn  = root.querySelector('.vance-tslide-prev');
                var nextBtn  = root.querySelector('.vance-tslide-next');
                var cards    = track ? track.children : null;
                if (!track || !cards || cards.length === 0 || !prevBtn || !nextBtn) { return; }

                var idx = 0;
                var GAP = 16; // matches .vance-testimonials-track gap

                function getVisible() {
                    var w = window.innerWidth;
                    if (w >= 1025) return 5;
                    if (w >= 601)  return 3;
                    return 1.2;
                }

                function step() {
                    // One card width + the gap that follows it.
                    return cards[0].getBoundingClientRect().width + GAP;
                }

                function maxIdx() {
                    return Math.max(0, cards.length - Math.floor(getVisible()));
                }

                function update() {
                    if (idx < 0) { idx = 0; }
                    if (idx > maxIdx()) { idx = maxIdx(); }
                    track.style.transform = 'translateX(' + (-idx * step()) + 'px)';
                    prevBtn.disabled = (idx <= 0);
                    nextBtn.disabled = (idx >= maxIdx());
                }

                prevBtn.addEventListener('click', function () { idx -= 1; update(); });
                nextBtn.addEventListener('click', function () { idx += 1; update(); });

                // Keyboard support: arrows when the carousel is focused.
                root.addEventListener('keydown', function (e) {
                    if (e.key === 'ArrowLeft')  { idx -= 1; update(); }
                    if (e.key === 'ArrowRight') { idx += 1; update(); }
                });

                var rT;
                window.addEventListener('resize', function () {
                    clearTimeout(rT);
                    rT = setTimeout(update, 80);
                });

                update();
            })();
            </script>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode( 'testimonials', 'vance_testimonials_shortcode' );

/**
 * 4. Helper: Add Role Field to Testimonials in Admin
 */
function vance_testimonial_meta_box() {
    add_meta_box( 'vance_testimonial_meta', 'Testimonial Details', 'vance_testimonial_meta_callback', 'testimonial', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'vance_testimonial_meta_box' );

function vance_testimonial_meta_callback( $post ) {
    $role = get_post_meta( $post->ID, '_testimonial_role', true );
    ?>
    <p>
        <label for="testimonial_role" style="font-weight: 600;">Author Role/Title:</label><br>
        <input type="text" id="testimonial_role" name="testimonial_role" value="<?php echo esc_attr( $role ); ?>" style="width: 100%; margin-top: 5px;" placeholder="e.g. Cardiologist, Patient, or CTO">
    </p>
    <?php
}

function vance_save_testimonial_meta( $post_id ) {
    if ( isset( $_POST['testimonial_role'] ) ) {
        update_post_meta( $post_id, '_testimonial_role', sanitize_text_field( $_POST['testimonial_role'] ) );
    }
}
add_action( 'save_post', 'vance_save_testimonial_meta' );

/**
 * Prefill wp-login.php?action=register email field from ?user_email= query param.
 * Used by the homepage Premium Subscribe form which submits via GET to the registration URL.
 */
function vance_prefill_register_email() {
    if ( empty( $_GET['user_email'] ) ) {
        return;
    }
    $email = sanitize_email( wp_unslash( $_GET['user_email'] ) );
    if ( ! $email ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var f = document.getElementById('user_email');
        if (f && !f.value) { f.value = <?php echo wp_json_encode( $email ); ?>; }
    });
    </script>
    <?php
}
add_action( 'login_form_register', 'vance_prefill_register_email' );





/**
 * HCP, Patient & About Us Pages Customizer Settings
 */
require_once get_template_directory() . '/customizer-pages.php';
require_once get_template_directory() . '/inc/customizer-gi-health.php';



/**
 * Every field stored in the `_sla_clinical_profile` user meta, mapped to the
 * sanitiser it needs. Single source of truth shared by the dashboard panel
 * (page-dashboard.php), the edit modal (inc/clinical-info-modal.php) and the
 * AJAX writer below — add a field here and all three pick it up, so a new
 * input can never be silently dropped on save.
 *
 * 'text' → single line, 'textarea' → multi-line, 'date' → YYYY-MM-DD only.
 */
function vance_clinical_profile_fields() {
    return array(
        'weight'                => 'text',
        'height'                => 'text',
        'usual_weight'          => 'text',
        'blood_pressure'        => 'text',
        'medication'            => 'textarea',
        'supplements'           => 'textarea',
        'allergies'             => 'textarea',
        'trigger_foods'         => 'textarea',
        'dietary_pattern'       => 'text',
        'digital_apps'          => 'textarea',
        'lifestyle_changes'     => 'textarea',
        'flare_up_freq'         => 'text',
        'last_flare_up'         => 'text',
        'next_appointment'      => 'date',
        'appointment_questions' => 'textarea',
        'additional_details'    => 'textarea',
    );
}

/**
 * Defaults for the clinical profile — every key present and empty, so callers
 * can read `$profile['allergies']` without an isset() dance.
 */
function vance_clinical_profile_defaults() {
    return array_fill_keys( array_keys( vance_clinical_profile_fields() ), '' );
}

/**
 * AJAX: Save Detailed Clinical Profile
 *
 * Both callers (the "Additional details" form on the dashboard panel and the
 * edit modal) are rendered with wp_nonce_field('vance_dashboard_nonce'), the
 * same nonce every other dashboard writer uses, and both post their inputs as
 * flat named fields.
 */
function vance_save_clinical_profile() {
    // This used to check 'vance_clinical_nonce' — a nonce no caller has ever
    // created — so check_ajax_referer() died with 403 before anything was
    // saved. jQuery.post() only has a success callback, so nothing ran and the
    // submit button sat on "Updating…" indefinitely: the reported freeze.
    // Verified by reverting this line locally and watching the request 403.
    if ( ! check_ajax_referer( 'vance_dashboard_nonce', 'nonce', false ) ) {
        wp_send_json_error( 'Your session has expired. Please refresh the page and try again.' );
    }
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    $user_id  = get_current_user_id();
    $existing = get_user_meta( $user_id, '_sla_clinical_profile', true );
    if ( ! is_array( $existing ) ) {
        $existing = array();
    }

    // Merge, never replace. The panel form posts only `additional_details` and
    // the modal posts only its own inputs, so a wholesale replace would let
    // whichever saved last wipe the other's answers. The old handler also read
    // a `profile_data` blob that neither form has ever sent, which is why even
    // a valid nonce would have returned "No data".
    $written = 0;
    foreach ( vance_clinical_profile_fields() as $key => $type ) {
        if ( ! isset( $_POST[ $key ] ) ) {
            continue;
        }
        $raw = wp_unslash( $_POST[ $key ] );
        if ( 'textarea' === $type ) {
            $existing[ $key ] = sanitize_textarea_field( $raw );
        } elseif ( 'date' === $type ) {
            $raw = sanitize_text_field( $raw );
            $existing[ $key ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '';
        } else {
            $existing[ $key ] = sanitize_text_field( $raw );
        }
        $written++;
    }

    if ( ! $written ) {
        wp_send_json_error( 'No data received' );
    }

    update_user_meta( $user_id, '_sla_clinical_profile', $existing );
    wp_send_json_success( 'Clinical profile updated' );
}
add_action( 'wp_ajax_vance_save_clinical_profile', 'vance_save_clinical_profile' );

/**
 * Add Quiz Modal Base Styles
 */
function vance_quiz_modal_styles() {
    ?>
    <style>
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-option-item {
            display: flex; align-items: center; gap: 15px; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: var(--radius-surface, 14px); cursor: pointer; transition: all 0.2s;
        }
        .modal-option-item:hover { border-color: #008080; background: #fffcf9; }
        .modal-option-item.selected { border-color: #008080; background: #def4f4; }
        .modal-option-item.selected .modal-option-radio { border-color: #008080 !important; background: #008080; box-shadow: inset 0 0 0 4px white; }
        .option-text { font-size: 15px; font-weight: 600; color: #334155; }
        .modal-btn-save { background: transparent; color: #94a3b8; border: 1px solid #e2e8f0; padding: 14px 24px; border-radius: var(--radius-control, 6px); font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .modal-btn-save:hover { background: #f8fafc; color: #475569; }
    </style>
    <?php
}
add_action( 'wp_footer', 'vance_quiz_modal_styles' );

/**
 * The Health Discovery Quiz, in order. Single source of truth for:
 *   - inc/quiz-modal.php   (renders these steps client-side)
 *   - page-dashboard.php   (lists saved answers, and needs each field's step
 *                           number so a row's "Edit" opens the right question)
 *
 * Both used to keep their own copy, and they drifted: the dashboard still
 * listed `current_tools` and `learning_pref`, questions the quiz no longer
 * asks, so every step index after `duration` pointed at the wrong question.
 *
 * Step shape: title, field (meta key), type (radio|checkbox), layout
 * (grid|list), label (short name for the dashboard row), opts. An option is
 * either a plain string, or an object with:
 *   v         value stored
 *   t         display text, when it differs from the value
 *   textInput a free-text follow-up appears when this option is chosen
 *   txtField  meta key that free text is stored under
 *   textLabel label above the free-text box
 *   depField / depCheckboxes  a nested multi-select revealed by this option
 */
function vance_quiz_steps() {
    return array(
        array(
            'title'  => 'What is your age?',
            'field'  => 'age',
            'label'  => 'Age range',
            'type'   => 'radio',
            'layout' => 'grid',
            'opts'   => array( 'Under 18', '18-24', '25-34', '35-44', '45-54', '55-64', '65+' ),
        ),
        array(
            'title'  => 'What is your gender?',
            'field'  => 'gender',
            'label'  => 'Gender',
            'type'   => 'radio',
            'layout' => 'list',
            'opts'   => array( 'Male', 'Female', 'Prefer not to say' ),
        ),
        array(
            'title'  => 'Do you currently have a gastrointestinal condition?',
            'field'  => 'gastro_condition',
            'label'  => 'Gastro condition',
            'type'   => 'radio',
            'layout' => 'list',
            'opts'   => array( 'Diagnosed', 'Symptoms but no diagnosis', 'No known disease or symptoms' ),
        ),
        array(
            'title'    => 'Which condition are you most concerned with?',
            'subtitle' => 'Select all that apply.',
            'field'    => 'condition_type',
            'label'    => 'Primary concern',
            'type'     => 'checkbox',
            'layout'   => 'list',
            'opts'     => array(
                "Crohn's", 'UC', 'IBS', 'General gut health / wellness',
                array( 'v' => 'Other', 'textInput' => true, 'txtField' => 'condition_type_other' ),
            ),
        ),
        array(
            'title'    => 'What are you primarily looking for today?',
            'subtitle' => 'Select all that apply.',
            'field'    => 'looking_for',
            'label'    => 'Searching for',
            'type'     => 'checkbox',
            'layout'   => 'list',
            'opts'     => array(
                'Research', 'Education', 'Health Tools', 'Community Support', 'Specialist Nutrition',
                array( 'v' => 'Other', 'textInput' => true, 'txtField' => 'looking_for_other' ),
            ),
        ),
        array(
            'title'  => 'How long have you been interested in gastrointestinal health?',
            'field'  => 'duration',
            'label'  => 'Duration of interest',
            'type'   => 'radio',
            'layout' => 'list',
            'opts'   => array(
                array( 'v' => 'Recently', 't' => 'Recently (less than 6 months)' ),
                array( 'v' => '1-3 Years', 't' => '1-3 Years' ),
                array( 'v' => '3+ Years', 't' => '3+ Years / Long-term' ),
            ),
        ),
        array(
            'title'  => 'Are you currently seeing a specialist for your health goals?',
            'field'  => 'seeing_specialist',
            'label'  => 'Seeing a specialist',
            'type'   => 'radio',
            'layout' => 'grid',
            'opts'   => array(
                array( 'v' => 'Yes', 'textInput' => true, 'txtField' => 'specialist_type', 'textLabel' => 'What type of specialist?' ),
                'No',
            ),
        ),
        array(
            'title'  => 'Do you currently use prescribed medication?',
            'field'  => 'use_medication',
            'label'  => 'Prescribed medication',
            'type'   => 'radio',
            'layout' => 'grid',
            'opts'   => array(
                array( 'v' => 'Yes', 'textInput' => true, 'txtField' => 'medication_details', 'textLabel' => 'Please specify:' ),
                'No',
            ),
        ),
        array(
            'title'  => 'Do you currently use food supplements?',
            'field'  => 'use_supplements',
            'label'  => 'Food supplements',
            'type'   => 'radio',
            'layout' => 'grid',
            'opts'   => array(
                array(
                    'v'             => 'Yes',
                    'depField'      => 'supplement_types',
                    'depCheckboxes' => array(
                        'Omega 3', 'Vitamin D', 'Probiotics', 'Iron', 'Zinc', 'Curcumin', 'Butyrate',
                        array( 'v' => 'Other', 'textInput' => true, 'txtField' => 'supplement_other' ),
                    ),
                ),
                'No',
            ),
        ),
    );
}

/**
 * field => short label, in quiz order. Used by the dashboard's saved-answers
 * list; the array position + 1 is the step number to open for editing.
 */
function vance_quiz_field_labels() {
    $labels = array();
    foreach ( vance_quiz_steps() as $step ) {
        $labels[ $step['field'] ] = $step['label'];
    }
    return $labels;
}

/**
 * AJAX: Save Healthcare Quiz Results
 * Stores quiz answers into user meta under _sla_healthcare_quiz_results.
 * Called by both the standalone page and the modal version of the quiz.
 */
function vance_save_quiz_results() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_quiz_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }

    $raw  = isset( $_POST['quiz_data'] ) ? (array) $_POST['quiz_data'] : array();
    $data = array();
    foreach ( $raw as $key => $val ) {
        if ( is_array( $val ) ) {
            $data[ $key ] = sanitize_text_field( implode( ', ', $val ) );
        } else {
            $data[ $key ] = sanitize_text_field( $val );
        }
    }

    if ( empty( $data ) ) {
        wp_send_json_error( 'No data received' );
    }

    $user_id = get_current_user_id();

    // Ensure we merge into an array to prevent fatal errors
    $existing = get_user_meta( $user_id, '_sla_healthcare_quiz_results', true );
    if ( ! is_array( $existing ) ) {
        $existing = array();
    }
    
    $merged = array_merge( $existing, $data );

    update_user_meta( $user_id, '_sla_healthcare_quiz_results', $merged );

    wp_send_json_success( array( 'saved' => true ) );
}
add_action( 'wp_ajax_vance_save_quiz_results', 'vance_save_quiz_results' );


/**
 * ---------------------------------------------------------------------------
 * Phase 2 mobile components — Customizer registration + helpers.
 * See MOBILE-PLAN.md §2. Every component defaults OFF so nothing renders on the
 * live site until an admin explicitly enables it under Appearance → Customize →
 * Mobile Experience.
 * ---------------------------------------------------------------------------
 */
function vance_mobile_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'vance_mobile_experience', array(
        'title'       => __( 'Mobile Experience', 'vance-health-hub' ),
        'priority'    => 47,
        'description' => __( 'App-like mobile-only components. These appear on phones (≤767px) and are hidden on desktop.', 'vance-health-hub' ),
    ) );

    // --- Bottom navigation bar ---
    $wp_customize->add_setting( 'vance_mobile_bottomnav_enable', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );
    $wp_customize->add_control( 'vance_mobile_bottomnav_enable', array(
        'label'   => __( 'Show mobile bottom navigation bar', 'vance-health-hub' ),
        'section' => 'vance_mobile_experience',
        'type'    => 'checkbox',
    ) );

    // Optional: restrict the bottom nav to logged-in users only.
    $wp_customize->add_setting( 'vance_mobile_bottomnav_loggedin_only', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );
    $wp_customize->add_control( 'vance_mobile_bottomnav_loggedin_only', array(
        'label'       => __( 'Bottom nav: logged-in users only', 'vance-health-hub' ),
        'description' => __( 'If ticked, the bar only shows to signed-in members.', 'vance-health-hub' ),
        'section'     => 'vance_mobile_experience',
        'type'        => 'checkbox',
    ) );

    // --- Sticky CTA bar (Phase 2.2) ---
    $wp_customize->add_setting( 'vance_mobile_stickycta_enable', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );
    $wp_customize->add_control( 'vance_mobile_stickycta_enable', array(
        'label'       => __( 'Show mobile sticky CTA bar', 'vance-health-hub' ),
        'description' => __( 'Slides up from the bottom on phones after the reader scrolls down. Hidden on the dashboard. If the bottom nav is also on, the CTA sits just above it.', 'vance-health-hub' ),
        'section'     => 'vance_mobile_experience',
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_mobile_stickycta_text', array(
        'default'           => 'Ready to take control of your gut health?',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_mobile_stickycta_text', array(
        'label'   => __( 'Sticky CTA: headline', 'vance-health-hub' ),
        'section' => 'vance_mobile_experience',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_mobile_stickycta_btn', array(
        'default'           => 'Join for Free',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_mobile_stickycta_btn', array(
        'label'   => __( 'Sticky CTA: button text', 'vance-health-hub' ),
        'section' => 'vance_mobile_experience',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_mobile_stickycta_link', array(
        'default'           => home_url( '/register/' ),
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( 'vance_mobile_stickycta_link', array(
        'label'   => __( 'Sticky CTA: button link', 'vance-health-hub' ),
        'section' => 'vance_mobile_experience',
        'type'    => 'url',
    ) );

    // --- Swipeable homepage category cards (Phase 2.3) ---
    $wp_customize->add_setting( 'vance_mobile_swipecards_enable', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );
    $wp_customize->add_control( 'vance_mobile_swipecards_enable', array(
        'label'       => __( 'Swipeable homepage category cards', 'vance-health-hub' ),
        'description' => __( 'On phones, turn the homepage category grid into a horizontal swipe carousel with dot indicators. Desktop is unchanged.', 'vance-health-hub' ),
        'section'     => 'vance_mobile_experience',
        'type'        => 'checkbox',
    ) );

    // --- Mobile dashboard enhancements (Phase 2.4) ---
    $wp_customize->add_setting( 'vance_mobile_dashboard_enhance', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );
    $wp_customize->add_control( 'vance_mobile_dashboard_enhance', array(
        'label'       => __( 'Mobile dashboard: touch interactions', 'vance-health-hub' ),
        'description' => __( 'On phones: dim + tap-to-close behind the open sidebar, swipe-left to close the sidebar, and pull-to-refresh at the top.', 'vance-health-hub' ),
        'section'     => 'vance_mobile_experience',
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_mobile_dashboard_accordion', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );
    $wp_customize->add_control( 'vance_mobile_dashboard_accordion', array(
        'label'       => __( 'Mobile dashboard: collapsible cards', 'vance-health-hub' ),
        'description' => __( 'On phones, make dashboard cards collapsible (tap the header to expand/collapse). Cards containing forms are left fully expanded.', 'vance-health-hub' ),
        'section'     => 'vance_mobile_experience',
        'type'        => 'checkbox',
    ) );
}
add_action( 'customize_register', 'vance_mobile_customize_register' );

/**
 * Decide whether the mobile bottom nav should render for the current request.
 * Centralised so the partial, the body-class filter and the body padding all
 * agree. Never renders on the dashboard (it has its own footer + sidebar).
 */
function vance_mobile_bottomnav_active() {
    if ( ! vance_get_theme_mod( 'vance_mobile_bottomnav_enable', false ) ) {
        return false;
    }
    if ( is_page( 'dashboard' ) || is_page_template( 'page-dashboard.php' ) ) {
        return false;
    }
    if ( vance_get_theme_mod( 'vance_mobile_bottomnav_loggedin_only', false ) && ! is_user_logged_in() ) {
        return false;
    }
    return true;
}

/**
 * Decide whether the mobile sticky CTA bar should render for this request.
 * Never on the dashboard. It MAY co-exist with the bottom nav — when both are
 * active, mobile-components.css lifts the CTA to sit just above the nav bar
 * (see body.has-vance-bottom-nav .vance-sticky-cta).
 */
function vance_mobile_stickycta_active() {
    if ( ! vance_get_theme_mod( 'vance_mobile_stickycta_enable', false ) ) {
        return false;
    }
    if ( is_page( 'dashboard' ) || is_page_template( 'page-dashboard.php' ) ) {
        return false;
    }
    return true;
}

/**
 * Add a body class when the bottom nav is active so mobile-components.css can
 * reserve bottom padding (only inside the mobile media query) and prevent the
 * fixed bar from covering page content.
 */
function vance_mobile_body_class( $classes ) {
    if ( vance_mobile_bottomnav_active() ) {
        $classes[] = 'has-vance-bottom-nav';
    }
    // Swipeable category cards only apply on the front page.
    if ( is_front_page() && vance_get_theme_mod( 'vance_mobile_swipecards_enable', false ) ) {
        $classes[] = 'has-vance-swipecards';
    }
    // page-dashboard.php hides .site-header itself and has its own full layout
    // (.dashboard-wrap / .dash-sidebar), so it must opt out of the fixed-header
    // top-padding compensation in mobile-base.css §3 — that rule already checks
    // for body.dashboard-body, it just never got added anywhere. Without this,
    // mobile visitors saw ~70px of blank white space (reserved for a header that
    // is deliberately hidden here) above the dashboard's own hamburger/breadcrumb
    // bar, with the real logo visible only inside the sidebar drawer once opened.
    // is_page_template() won't catch this: the "dashboard" page has no explicit
    // _wp_page_template meta set — WP resolves page-dashboard.php purely via the
    // slug-matching template hierarchy, so the check has to key off the slug.
    if ( is_page( 'dashboard' ) ) {
        $classes[] = 'dashboard-body';
    }
    return $classes;
}
add_filter( 'body_class', 'vance_mobile_body_class' );

// End of File — verified complete 2026-06-03

