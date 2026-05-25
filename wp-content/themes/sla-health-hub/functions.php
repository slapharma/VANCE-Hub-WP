<?php
/**
 * Vance Medical Hub Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// AI Visibility System — drop-in file that makes the site discoverable to LLMs/agents.
require_once get_template_directory() . '/ai-visibility.php';

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
    wp_enqueue_style( 'vance-main-style', get_template_directory_uri() . '/assets/css/main.css', array(), '2.1.1-vance' );
    
    // Enqueue Theme Stylesheet (style.css)
    wp_enqueue_style( 'vance-style', get_stylesheet_uri() );
}
add_action( 'wp_enqueue_scripts', 'vance_health_hub_scripts' );

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
            'primary-menu' => esc_html__( 'Primary Menu', 'sla-health-hub' ),
            'footer-menu-1' => esc_html__( 'Footer Menu Topics', 'sla-health-hub' ),
            'footer-menu-2' => esc_html__( 'Footer Menu Professionals', 'sla-health-hub' ),
            'footer-menu-3' => esc_html__( 'Footer Menu Patients', 'sla-health-hub' ),
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
        // Get all registered CPTs
        $cpts = array( 'news', 'research', 'oped', 'review', 'whitepaper', 'podcast', 'webinar', 'course', 'infographic' );
        
        // Include both standard posts and all CPTs
        $query->set( 'post_type', array_merge( array( 'post' ), $cpts ) );
    }
}
add_action( 'pre_get_posts', 'vance_include_cpts_in_category_archives' );

/**
 * Register Custom Post Types
 * News, Clinical Research Reviews, Op-Eds, Product reviews, White papers, Podcasts, Webinars, Courses, Infographics
 */
function vance_register_cpts() {
    $cpts = array(
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

    foreach ($cpts as $slug => $name) {
        $labels = array(
            'name'                  => _x( $name . 's', 'Post Type General Name', 'sla-health-hub' ),
            'singular_name'         => _x( $name, 'Post Type Singular Name', 'sla-health-hub' ),
            'menu_name'             => __( $name . 's', 'sla-health-hub' ),
            'all_items'             => __( 'All ' . $name . 's', 'sla-health-hub' ),
            'add_new_item'          => __( 'Add New ' . $name, 'sla-health-hub' ),
        );
        $args = array(
            'label'                 => __( $name, 'sla-health-hub' ),
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
            'show_in_menu'          => true, // Fixed: Setting this to true and manually moving solves permissions
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

    $cpts = array('news', 'research', 'oped', 'review', 'whitepaper', 'podcast', 'webinar', 'course', 'infographic');
    foreach ($cpts as $cpt) {
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
        'Gastro Health Hub',
        'Gastro Health Hub',
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
        // 'review' => 'Reviews', // REMOVED from Content Hub menu per user request
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
 * Get SVG Icon for Category
 */
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
        <div style="background: white; border-radius: 0; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 32px; font-weight: 800; color: #0A1929; margin: 0 0 10px 0; font-family: 'Outfit', sans-serif;">CONTENT HUB STATION</h1>
                    <p style="font-size: 16px; color: #64748b; margin: 0;">Manage your healthcare content and clinical resources.</p>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
            <?php foreach ($cpts as $slug => $data) : ?>
            <div style="background: white; border-radius: 0; border: 1px solid #e2e8f0; padding: 30px; display: flex; flex-direction: column; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 20px;">
                    <div style="width: 48px; height: 48px; background: #f1f5f9; border-radius: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <img src="<?php echo vance_get_category_icon_url($data['name']); ?>" style="width: 28px; height: 28px; object-fit: contain; filter: none !important;">
                    </div>
                    <div>
                        <h2 style="font-size: 16px; font-weight: 700; color: #0A1929; margin: 0; text-transform: uppercase;"><?php echo esc_html($data['name']); ?></h2>
                        <p style="color: #64748b; font-size: 13px; margin: 5px 0 0 0; line-height: 1.5;"><?php echo esc_html($data['desc']); ?></p>
                    </div>
                </div>
                
                <div style="margin-top: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <a href="<?php echo admin_url('edit.php?post_type=' . $slug); ?>" class="button" style="text-align: center; border-radius: 0;">View All</a>
                    <a href="<?php echo admin_url('post-new.php?post_type=' . $slug); ?>" class="button button-primary" style="text-align: center; background: #0A1929; border-color: #0A1929; border-radius: 0;">+ Add New</a>
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
        <div style="background: white; border-radius: 0; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 32px; font-weight: 800; color: #0A1929; margin: 0 0 10px 0; font-family: 'Outfit', sans-serif;">MEDIA HUB STATION</h1>
                    <p style="font-size: 16px; color: #64748b; margin: 0;">Manage your multimedia content and educational resources.</p>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
            <?php foreach ($cpts as $slug => $data) : ?>
            <div style="background: white; border-radius: 0; border: 1px solid #e2e8f0; padding: 30px; display: flex; flex-direction: column; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 20px;">
                    <div style="width: 48px; height: 48px; background: #f1f5f9; border-radius: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <img src="<?php echo vance_get_category_icon_url($data['name']); ?>" style="width: 28px; height: 28px; object-fit: contain; filter: none !important;">
                    </div>
                    <div>
                        <h2 style="font-size: 16px; font-weight: 700; color: #0A1929; margin: 0; text-transform: uppercase;"><?php echo esc_html($data['name']); ?></h2>
                        <p style="color: #64748b; font-size: 13px; margin: 5px 0 0 0; line-height: 1.5;"><?php echo esc_html($data['desc']); ?></p>
                    </div>
                </div>
                
                <div style="margin-top: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <a href="<?php echo admin_url('edit.php?post_type=' . $slug); ?>" class="button" style="text-align: center; border-radius: 0;">View All</a>
                    <a href="<?php echo admin_url('post-new.php?post_type=' . $slug); ?>" class="button button-primary" style="text-align: center; background: #0A1929; border-color: #0A1929; border-radius: 0;">+ Add New</a>
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
    function handleGoogleCredentialResponse(response) {
        fetch("' . admin_url('admin-ajax.php') . '", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "action=vance_google_oauth_callback&credential=" + response.credential +
                  "&nonce=' . $nonce . '" +
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
    
    // Decode JWT token (basic decode - in production use a proper JWT library)
    $parts = explode('.', $credential);
    if ( count($parts) !== 3 || ! isset( $parts[1] ) ) {
        wp_send_json_error( 'Invalid token format' );
    }
    
    $payload = json_decode( base64_decode( strtr( $parts[1], '-_', '+/' ) ), true );
    
    if ( ! $payload || ! isset( $payload['email'] ) ) {
        wp_send_json_error( 'Could not decode token' );
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

        // Default to Patient Role
        update_user_meta( $user_id, '_sla_user_type', 'patient' );
        update_user_meta( $user_id, '_sla_dashboard_role', 'patient' );

        // Google has already verified the email address — mark as verified.
        // Honour Google's email_verified flag if present (always true for OIDC-compliant providers).
        $email_verified = isset( $payload['email_verified'] ) ? (bool) $payload['email_verified'] : true;
        update_user_meta( $user_id, '_vance_email_verified', $email_verified ? 1 : 0 );

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
    if ( ! empty( $_GET['action'] ) ) {
        return;
    }
    if ( isset( $_GET['wp_admin_login'] ) ) {
        return; // escape hatch for admin
    }

    $raw_redirect = isset( $_GET['redirect_to'] ) ? wp_unslash( $_GET['redirect_to'] ) : home_url( '/dashboard/' );
    $redirect_to  = wp_validate_redirect( $raw_redirect, home_url( '/dashboard/' ) );

    $target = add_query_arg( 'redirect_to', urlencode( $redirect_to ), home_url( '/login/' ) );
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
        return '<div class="vance-user-logged-in" style="text-align:center;padding:40px 20px;">
            <p>Welcome back, ' . esc_html( $current_user->display_name ) . '.</p>
            <p><a href="' . esc_url( home_url( '/dashboard/' ) ) . '" class="btn btn-primary">Go to dashboard</a>
            &nbsp;<a href="' . esc_url( wp_logout_url( home_url() ) ) . '" class="btn btn-outline">Logout</a></p>
        </div>';
    }

    $client_id = defined( 'GOOGLE_CLIENT_ID' ) ? GOOGLE_CLIENT_ID : '';

    $raw_redirect = isset( $_GET['redirect_to'] ) ? wp_unslash( $_GET['redirect_to'] ) : home_url( '/dashboard/' );
    $redirect_to  = wp_validate_redirect( $raw_redirect, home_url( '/dashboard/' ) );

    $nonces = array(
        'google' => wp_create_nonce( 'google_oauth_nonce' ),
        'login'  => wp_create_nonce( 'vance_login_nonce' ),
        'signup' => wp_create_nonce( 'vance_signup_nonce' ),
    );

    $ajax_url    = admin_url( 'admin-ajax.php' );
    $lost_pw_url = wp_lostpassword_url( $redirect_to );

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
    .vance-auth-modal{background:#fff;border-radius:16px;padding:36px 32px;max-width:420px;width:100%;box-shadow:0 24px 72px rgba(0,0,0,0.3);animation:vancePopIn .25s ease-out;box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
    .vance-auth-header{text-align:center;margin-bottom:24px}
    .vance-auth-header h2{margin:0 0 6px;color:#1a1a1a;font-size:24px;font-weight:700}
    .vance-auth-header p{margin:0;color:#666;font-size:14px}
    .vance-auth-google{display:flex;justify-content:center;margin-bottom:18px;min-height:44px}
    .vance-auth-divider{text-align:center;margin:18px 0;color:#999;position:relative;font-size:12px;text-transform:uppercase;letter-spacing:1px}
    .vance-auth-divider span{background:#fff;padding:0 12px;position:relative;z-index:1}
    .vance-auth-divider::before{content:'';position:absolute;top:50%;left:0;right:0;height:1px;background:#e5ebeb}
    .vance-auth-tabs{display:flex;gap:4px;margin-bottom:20px;background:#f1f5f5;padding:4px;border-radius:10px}
    .vance-auth-tab{flex:1;padding:10px 16px;border:none;background:transparent;cursor:pointer;border-radius:7px;font-weight:600;color:#666;transition:all .15s;font-size:14px}
    .vance-auth-tab.active{background:#fff;color:#008080;box-shadow:0 2px 6px rgba(0,0,0,0.06)}
    .vance-auth-error{background:#fee;color:#a00;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;display:none;border:1px solid #fcc}
    .vance-auth-error.active{display:block}
    .vance-auth-form{display:none}
    .vance-auth-form.active{display:block}
    .vance-auth-field{margin-bottom:14px}
    .vance-auth-field label{display:block;font-size:13px;font-weight:600;color:#444;margin-bottom:6px}
    .vance-auth-field input{width:100%;padding:11px 14px;border:1.5px solid #e0e6e6;border-radius:8px;font-size:15px;box-sizing:border-box;transition:border-color .15s;font-family:inherit}
    .vance-auth-field input:focus{outline:none;border-color:#008080;box-shadow:0 0 0 3px rgba(0,128,128,0.1)}
    .vance-auth-forgot{text-align:right;margin:-6px 0 14px}
    .vance-auth-forgot a{color:#008080;text-decoration:none;font-size:13px}
    .vance-auth-forgot a:hover{text-decoration:underline}
    .vance-auth-submit{width:100%;padding:13px;background:#008080;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;transition:background .15s;font-family:inherit}
    .vance-auth-submit:hover:not(:disabled){background:#006666}
    .vance-auth-submit:disabled{background:#aaa;cursor:not-allowed}
    .vance-auth-footer{text-align:center;margin-top:18px;font-size:13px;color:#888}
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
            <div class="vance-auth-header">
                <h2 id="vance-auth-title">Welcome</h2>
                <p>Sign in or create your account to continue</p>
            </div>

            <?php if ( $client_id ) : ?>
            <div class="vance-auth-google">
                <div id="g_id_onload"
                    data-client_id="<?php echo esc_attr( $client_id ); ?>"
                    data-context="signin" data-ux_mode="popup"
                    data-callback="handleGoogleCredentialResponse"
                    data-auto_prompt="false"></div>
                <div class="g_id_signin" data-type="standard" data-shape="rectangular"
                    data-theme="outline" data-text="continue_with" data-size="large"
                    data-logo_alignment="left" data-width="340"></div>
            </div>
            <div class="vance-auth-divider"><span>or</span></div>
            <?php endif; ?>

            <div class="vance-auth-tabs" role="tablist">
                <button class="vance-auth-tab active" type="button" data-target="vance-signin" role="tab">Sign in</button>
                <button class="vance-auth-tab" type="button" data-target="vance-signup" role="tab">Sign up</button>
            </div>

            <div class="vance-auth-error" id="vance-auth-error" role="alert"></div>

            <form class="vance-auth-form active" id="vance-signin" novalidate>
                <div class="vance-auth-field">
                    <label for="vance-signin-email">Email</label>
                    <input id="vance-signin-email" type="email" name="email" required autocomplete="email">
                </div>
                <div class="vance-auth-field">
                    <label for="vance-signin-password">Password</label>
                    <input id="vance-signin-password" type="password" name="password" required autocomplete="current-password">
                </div>
                <div class="vance-auth-forgot"><a href="<?php echo esc_url( $lost_pw_url ); ?>">Forgot password?</a></div>
                <button type="submit" class="vance-auth-submit" data-label="Sign in">Sign in</button>
            </form>

            <form class="vance-auth-form" id="vance-signup" novalidate>
                <div class="vance-auth-field">
                    <label for="vance-signup-name">Full name</label>
                    <input id="vance-signup-name" type="text" name="name" required autocomplete="name">
                </div>
                <div class="vance-auth-field">
                    <label for="vance-signup-email">Email</label>
                    <input id="vance-signup-email" type="email" name="email" required autocomplete="email">
                </div>
                <div class="vance-auth-field">
                    <label for="vance-signup-password">Password (min 8 characters)</label>
                    <input id="vance-signup-password" type="password" name="password" minlength="8" required autocomplete="new-password">
                </div>
                <button type="submit" class="vance-auth-submit" data-label="Create account">Create account</button>
            </form>

            <div class="vance-auth-footer">
                By continuing you agree to our <a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>" style="color:#008080">Terms</a> &amp; <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>" style="color:#008080">Privacy</a>.
            </div>
        </div>
    </div>

    <script>
    (function(){
        var CFG = <?php echo $cfg; // already JSON-encoded by wp_json_encode ?>;
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

        // Email login
        document.getElementById('vance-signin').addEventListener('submit', function(e){
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
            }).catch(function(){ showError('Network error — try again'); unlockButton(btn); });
        });

        // Email signup
        document.getElementById('vance-signup').addEventListener('submit', function(e){
            e.preventDefault();
            clearError();
            var btn = this.querySelector('.vance-auth-submit');
            lockButton(btn, 'Creating account…');
            postForm('vance_email_signup', CFG.nonces.signup, {
                name: this.name.value.trim(),
                email: this.email.value.trim(),
                password: this.password.value
            }).then(function(data){
                if (data.success) {
                    window.location.href = (data.data && data.data.redirect_to) || CFG.redirectTo;
                } else {
                    showError((data.data && (data.data.message || data.data)) || 'Signup failed');
                    unlockButton(btn);
                }
            }).catch(function(){ showError('Network error — try again'); unlockButton(btn); });
        });

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
            }).catch(function(){ showError('Network error — try again'); });
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
        wp_send_json_error( 'Invalid request — please refresh and try again.' );
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
 * AJAX: email + password signup.
 *
 * Defaults new users to the "patient" role keys used by the Google flow so
 * existing dashboard logic continues to work identically.
 */
function vance_email_signup_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_signup_nonce' ) ) {
        wp_send_json_error( 'Invalid request — please refresh and try again.' );
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
    update_user_meta( $user_id, '_sla_user_type', 'patient' );
    update_user_meta( $user_id, '_sla_dashboard_role', 'patient' );

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
        "— %s",
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
        wp_send_json_error( 'Invalid request — please refresh and try again.' );
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
        return '<div class="vance-verify-card" style="max-width:480px;margin:60px auto;padding:48px 32px;text-align:center;background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,128,128,0.08)">
            <div style="font-size:48px;margin-bottom:12px">&#10003;</div>
            <h1 style="margin:0 0 8px;color:#008080">Email verified</h1>
            <p style="color:#666;margin-bottom:24px">Your account is ready. Redirecting to your dashboard&hellip;</p>
            <a href="' . esc_url( home_url( '/dashboard/' ) ) . '" style="display:inline-block;padding:12px 28px;background:#008080;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Go to dashboard</a>
            <script>setTimeout(function(){window.location.href=' . wp_json_encode( home_url( '/dashboard/' ) ) . ';},1500);</script>
        </div>';
    }

    if ( ! is_user_logged_in() ) {
        return '<div class="vance-verify-card" style="max-width:480px;margin:60px auto;padding:48px 32px;text-align:center;background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.08)">
            <h1 style="margin:0 0 8px;color:#1a1a1a">Verify your email</h1>
            <p style="color:#666;margin-bottom:24px">Please sign in to resend your verification email.</p>
            <a href="' . esc_url( home_url( '/login/' ) ) . '" style="display:inline-block;padding:12px 28px;background:#008080;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Sign in</a>
        </div>';
    }

    $user        = wp_get_current_user();
    $resend_nonce = wp_create_nonce( 'vance_resend_nonce' );
    $ajax_url    = admin_url( 'admin-ajax.php' );

    $error_html = $had_error
        ? '<div style="background:#fee;color:#a00;padding:12px;border-radius:8px;margin-bottom:16px;border:1px solid #fcc;font-size:14px">That verification link was invalid or has already been used. Request a new one below.</div>'
        : '';

    ob_start();
    ?>
    <div class="vance-verify-card" style="max-width:480px;margin:60px auto;padding:48px 32px;text-align:center;background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.08);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif">
        <div style="font-size:48px;margin-bottom:12px">&#9993;</div>
        <h1 style="margin:0 0 8px;color:#1a1a1a;font-size:24px">Check your inbox</h1>
        <p style="color:#666;margin-bottom:24px;line-height:1.5">
            We sent a verification link to <strong><?php echo esc_html( $user->user_email ); ?></strong>.<br>
            Click the link in that email to activate your account.
        </p>
        <?php echo $error_html; // sanitized literal above ?>
        <div id="vance-resend-status" style="margin-bottom:14px;font-size:14px"></div>
        <button id="vance-resend-btn" style="display:inline-block;padding:12px 28px;background:#008080;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer">Resend verification email</button>
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
 * Increase maximum upload size to 10MB
 */
function vance_increase_upload_size_limit( $limit ) {
    return 10 * 1024 * 1024; // 10MB in bytes
}
add_filter( 'upload_size_limit', 'vance_increase_upload_size_limit' );

/**
 * Add Advanced Theme Settings to Customizer
 */
function vance_customize_register( $wp_customize ) {
    // 0. Vance Theme Panels
    $wp_customize->add_panel( 'vance_brand_panel', array( 'title' => __( 'Vance Theme -> Brand Identity', 'sla-health-hub' ), 'priority' => 10 ) );
    $wp_customize->add_panel( 'vance_homepage_panel', array( 'title' => __( 'Vance Theme -> Homepage', 'sla-health-hub' ), 'priority' => 11 ) );
    $wp_customize->add_panel( 'vance_content_panel', array( 'title' => __( 'Vance Theme -> Content', 'sla-health-hub' ), 'priority' => 12 ) );
    $wp_customize->add_panel( 'vance_footer_panel', array( 'title' => __( 'Vance Theme -> Footer', 'sla-health-hub' ), 'priority' => 13 ) );
    $wp_customize->add_panel( 'vance_advanced_panel', array( 'title' => __( 'Vance Theme -> Advanced', 'sla-health-hub' ), 'priority' => 14 ) );

    // Move Site Identity into Vance Theme Settings
    if ( $wp_customize->get_section('title_tagline') ) {
        $wp_customize->get_section('title_tagline')->panel = 'vance_brand_panel';
        $wp_customize->get_section('title_tagline')->priority = 5;
    }

    // 1. Social Media Links Section
    $wp_customize->add_section( 'vance_social_links', array(
        'title'    => __( 'Social Media Links', 'sla-health-hub' ),
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
        'title'       => __( 'Hero', 'sla-health-hub' ),
        'description' => __( 'Manage hero images, text, and styling.', 'sla-health-hub' ),
        'priority'    => 31,
        'panel'       => 'vance_homepage_panel',
    ) );

    $wp_customize->add_setting( 'vance_homepage_hero_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );

    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_homepage_hero_image', array(
        'label'       => __( 'Homepage Hero Image', 'sla-health-hub' ),
        'description' => __( 'High resolution (1920x800px) ensures clarity.', 'sla-health-hub' ),
        'section'     => 'vance_hero_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_category_hero_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );

    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_category_hero_image', array(
        'label'       => __( 'Category/Archive Hero Image', 'sla-health-hub' ),
        'description' => __( 'Default hero for all category pages.', 'sla-health-hub' ),
        'section'     => 'vance_hero_settings',
    ) ) );

    // Hero Text Content (New)
    $wp_customize->add_setting( 'vance_hero_custom_title', array(
        'default'           => 'Evidence-Based Healthcare Knowledge',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_hero_custom_title', array(
        'label'       => __( 'Homepage Hero Title', 'sla-health-hub' ),
        'section'     => 'vance_hero_settings',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_hero_custom_subtitle', array(
        'default'           => 'Pharma-grade clinical resources, research, and tools for healthcare professionals.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'vance_hero_custom_subtitle', array(
        'label'       => __( 'Homepage Hero Subtitle', 'sla-health-hub' ),
        'section'     => 'vance_hero_settings',
        'type'        => 'textarea',
    ) );

    // Hero Tag & Buttons
    $wp_customize->add_setting( 'vance_hero_tag_label', array(
        'default'           => 'HEALTHCARE KNOWLEDGE HUB',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_hero_tag_label', array(
        'label'   => __( 'Hero Tag Label', 'sla-health-hub' ),
        'section' => 'vance_hero_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_hero_button_1_text', array(
        'default'           => "I'm a Practitioner",
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_hero_button_1_text', array(
        'label'   => __( 'Hero Button 1 Text', 'sla-health-hub' ),
        'section' => 'vance_hero_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_hero_button_1_link', array(
        'default'           => '/healthcare-professionals/',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_hero_button_1_link', array(
        'label'   => __( 'Hero Button 1 Link', 'sla-health-hub' ),
        'section' => 'vance_hero_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_hero_button_2_text', array(
        'default'           => "I'm a Patient",
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_hero_button_2_text', array(
        'label'   => __( 'Hero Button 2 Text', 'sla-health-hub' ),
        'section' => 'vance_hero_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_hero_button_2_link', array(
        'default'           => '/patients/',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_hero_button_2_link', array(
        'label'   => __( 'Hero Button 2 Link', 'sla-health-hub' ),
        'section' => 'vance_hero_settings',
        'type'    => 'text',
    ) );

    // Hero Styling Settings
    $wp_customize->add_setting( 'vance_hero_title_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_title_color', array(
        'label'   => __( 'Hero Title Color', 'sla-health-hub' ),
        'section' => 'vance_hero_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_hero_title_size', array(
        'default'           => 52,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_hero_title_size', array(
        'label'       => __( 'Hero Title Size (px)', 'sla-health-hub' ),
        'section'     => 'vance_hero_settings',
        'type'        => 'range',
        'input_attrs' => array( 'min' => 24, 'max' => 100, 'step' => 1 ),
    ) );

    $wp_customize->add_setting( 'vance_hero_mask_toggle', array(
        'default'           => true,
        'sanitize_callback' => 'vance_sanitize_checkbox',
    ) );
    $wp_customize->add_control( 'vance_hero_mask_toggle', array(
        'label'   => __( 'Enable Dark Overlay Mask', 'sla-health-hub' ),
        'section' => 'vance_hero_settings',
        'type'    => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_hero_mask_opacity', array(
        'default'           => 0.5,
        'sanitize_callback' => 'floatval',
    ) );
    $wp_customize->add_control( 'vance_hero_mask_opacity', array(
        'label'       => __( 'Overlay Opacity (0.0 - 1.0)', 'sla-health-hub' ),
        'section'     => 'vance_hero_settings',
        'type'        => 'range',
        'input_attrs' => array( 'min' => 0, 'max' => 1, 'step' => 0.1 ),
    ) );

    $wp_customize->add_setting( 'vance_hero_subtitle_color', array(
        'default'           => '#cbd5e1',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_hero_subtitle_color', array(
        'label'   => __( 'Hero Subtitle Color', 'sla-health-hub' ),
        'section' => 'vance_hero_settings',
    ) ) );

    // 2.5 Discovery Suite Settings (Nested under Vance Theme Settings)
    $wp_customize->add_section( 'vance_discovery_general', array(
        'title'    => __( 'Discovery Engine (General)', 'sla-health-hub' ),
        'priority' => 31.5,
        'panel'    => 'vance_homepage_panel',
    ) );

    $wp_customize->add_section( 'vance_discovery_reading', array(
        'title'    => __( 'Discovery Engine (Reading Levels)', 'sla-health-hub' ),
        'panel'    => 'vance_homepage_panel',
        'priority' => 31.6,
    ) );

    $wp_customize->add_section( 'vance_discovery_type', array(
        'title'    => __( 'Discovery Engine (Content Types)', 'sla-health-hub' ),
        'panel'    => 'vance_homepage_panel',
        'priority' => 31.7,
    ) );

    $wp_customize->add_section( 'vance_discovery_path', array(
        'title'    => __( 'Discovery Engine (Healthcare Pathways)', 'sla-health-hub' ),
        'panel'    => 'vance_homepage_panel',
        'priority' => 31.8,
    ) );

    $wp_customize->add_section( 'vance_discovery_focus', array(
        'title'    => __( 'Discovery Engine (IBD Research Focus)', 'sla-health-hub' ),
        'panel'    => 'vance_homepage_panel',
        'priority' => 31.9,
    ) );

    // Get all tags for Discovery Suite configuration
    $all_tags = get_terms( array(
        'taxonomy'   => 'post_tag',
        'hide_empty' => false,
    ) );

    // Ensure we have an array
    if ( is_wp_error( $all_tags ) || ! is_array( $all_tags ) ) {
        $all_tags = array();
    }
    
    // Filter tags by prefix (Case-insensitive check of both name and slug)
    $reading_tags = array_filter($all_tags, function($tag) {
        return stripos($tag->name, 'reading-') === 0 || stripos($tag->slug, 'reading-') === 0;
    });
    
    $path_tags = array_filter($all_tags, function($tag) {
        return stripos($tag->name, 'path-') === 0 || stripos($tag->slug, 'path-') === 0;
    });
    
    $indication_tags = array_filter($all_tags, function($tag) {
        return stripos($tag->name, 'indication-') === 0 || stripos($tag->slug, 'indication-') === 0;
    });
    
    // Get categories for Content Types
    $categories = get_categories(array('hide_empty' => false));
    
    // Reading Level Tags (reading- prefix)
    if (empty($reading_tags)) {
        $wp_customize->get_section('vance_discovery_reading')->description = __('No tags found with "reading-" prefix. Please create tags like "reading-novice" in the WordPress admin to see options here.', 'sla-health-hub');
    }
    
    foreach ($reading_tags as $tag) {
        // Show/Hide Toggle
        $wp_customize->add_setting("vance_discovery_reading_show_{$tag->term_id}", array(
            'default'           => false,
            'sanitize_callback' => 'vance_sanitize_checkbox',
        ));
        $wp_customize->add_control("vance_discovery_reading_show_{$tag->term_id}", array(
            'label'   => sprintf(__('Show: %s', 'sla-health-hub'), $tag->name),
            'section' => 'vance_discovery_reading',
            'type'    => 'checkbox',
        ));
        
        // Display Text
        $wp_customize->add_setting("vance_discovery_reading_text_{$tag->term_id}", array(
            'default'           => str_replace('reading-', '', $tag->name),
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control("vance_discovery_reading_text_{$tag->term_id}", array(
            'label'   => sprintf(__('Display Text: %s', 'sla-health-hub'), $tag->name),
            'section' => 'vance_discovery_reading',
            'type'    => 'text',
        ));
        
        // Display Order
        $wp_customize->add_setting("vance_discovery_reading_order_{$tag->term_id}", array(
            'default'           => 10,
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control("vance_discovery_reading_order_{$tag->term_id}", array(
            'label'   => sprintf(__('Order: %s', 'sla-health-hub'), $tag->name),
            'section' => 'vance_discovery_reading',
            'type'    => 'number',
        ));
    }
    
    // Content Type Categories
    foreach ($categories as $cat) {
        // Show/Hide Toggle
        $wp_customize->add_setting("vance_discovery_type_show_{$cat->term_id}", array(
            'default'           => false,
            'sanitize_callback' => 'vance_sanitize_checkbox',
        ));
        $wp_customize->add_control("vance_discovery_type_show_{$cat->term_id}", array(
            'label'   => sprintf(__('Show: %s', 'sla-health-hub'), $cat->name),
            'section' => 'vance_discovery_type',
            'type'    => 'checkbox',
        ));
        
        // Display Text
        $wp_customize->add_setting("vance_discovery_type_text_{$cat->term_id}", array(
            'default'           => $cat->name,
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control("vance_discovery_type_text_{$cat->term_id}", array(
            'label'   => sprintf(__('Display Text: %s', 'sla-health-hub'), $cat->name),
            'section' => 'vance_discovery_type',
            'type'    => 'text',
        ));
        
        // Display Order
        $wp_customize->add_setting("vance_discovery_type_order_{$cat->term_id}", array(
            'default'           => 10,
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control("vance_discovery_type_order_{$cat->term_id}", array(
            'label'   => sprintf(__('Order: %s', 'sla-health-hub'), $cat->name),
            'section' => 'vance_discovery_type',
            'type'    => 'number',
        ));
    }
    
    // IBD Research Path Tags (path- prefix)
    if (empty($path_tags)) {
        $wp_customize->get_section('vance_discovery_path')->description = __('No tags found with "path-" prefix. Please create tags like "path-clinical" in the WordPress admin to see options here.', 'sla-health-hub');
    }
    
    foreach ($path_tags as $tag) {
        // Show/Hide Toggle
        $wp_customize->add_setting("vance_discovery_path_show_{$tag->term_id}", array(
            'default'           => false,
            'sanitize_callback' => 'vance_sanitize_checkbox',
        ));
        $wp_customize->add_control("vance_discovery_path_show_{$tag->term_id}", array(
            'label'   => sprintf(__('Show: %s', 'sla-health-hub'), $tag->name),
            'section' => 'vance_discovery_path',
            'type'    => 'checkbox',
        ));
        
        // Display Text
        $wp_customize->add_setting("vance_discovery_path_text_{$tag->term_id}", array(
            'default'           => str_replace('path-', '', $tag->name),
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control("vance_discovery_path_text_{$tag->term_id}", array(
            'label'   => sprintf(__('Display Text: %s', 'sla-health-hub'), $tag->name),
            'section' => 'vance_discovery_path',
            'type'    => 'text',
        ));
        
        // Display Order
        $wp_customize->add_setting("vance_discovery_path_order_{$tag->term_id}", array(
            'default'           => 10,
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control("vance_discovery_path_order_{$tag->term_id}", array(
            'label'   => sprintf(__('Order: %s', 'sla-health-hub'), $tag->name),
            'section' => 'vance_discovery_path',
            'type'    => 'number',
        ));
    }
    
    // IBD Research Focus Tags (indication- prefix)
    if (empty($indication_tags)) {
        $wp_customize->get_section('vance_discovery_focus')->description = __('No tags found with "indication-" prefix. Please create tags like "indication-cardio" in the WordPress admin to see options here.', 'sla-health-hub');
    }
    
    foreach ($indication_tags as $tag) {
        // Show/Hide Toggle
        $wp_customize->add_setting("vance_discovery_focus_show_{$tag->term_id}", array(
            'default'           => false,
            'sanitize_callback' => 'vance_sanitize_checkbox',
        ));
        $wp_customize->add_control("vance_discovery_focus_show_{$tag->term_id}", array(
            'label'   => sprintf(__('Show: %s', 'sla-health-hub'), $tag->name),
            'section' => 'vance_discovery_focus',
            'type'    => 'checkbox',
        ));
        
        // Display Text
        $wp_customize->add_setting("vance_discovery_focus_text_{$tag->term_id}", array(
            'default'           => str_replace('indication-', '', $tag->name),
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control("vance_discovery_focus_text_{$tag->term_id}", array(
            'label'   => sprintf(__('Display Text: %s', 'sla-health-hub'), $tag->name),
            'section' => 'vance_discovery_focus',
            'type'    => 'text',
        ));
        
        // Display Order
        $wp_customize->add_setting("vance_discovery_focus_order_{$tag->term_id}", array(
            'default'           => 10,
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control("vance_discovery_focus_order_{$tag->term_id}", array(
            'label'   => sprintf(__('Order: %s', 'sla-health-hub'), $tag->name),
            'section' => 'vance_discovery_focus',
            'type'    => 'number',
        ));
    }

    // 2.6 Pathway Tile Settings
    $wp_customize->add_section( 'vance_pathway_tiles_settings', array(
        'title'    => __( 'Pathway Tiles', 'sla-health-hub' ),
        'priority' => 31.6,
        'panel'    => 'vance_homepage_panel',
    ) );

    // Practitioner Tile
    $wp_customize->add_setting( 'vance_practitioner_tile_title', array(
        'default'           => 'For Practitioners',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_practitioner_tile_title', array(
        'label'   => __( 'Practitioner Tile Title', 'sla-health-hub' ),
        'section' => 'vance_pathway_tiles_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_practitioner_tile_desc', array(
        'default'           => 'Access clinical reviews, evidence-based guidelines, and professional tools tailored for modern healthcare practitioners.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'vance_practitioner_tile_desc', array(
        'label'   => __( 'Practitioner Tile Description', 'sla-health-hub' ),
        'section' => 'vance_pathway_tiles_settings',
        'type'    => 'textarea',
    ) );

    $wp_customize->add_setting( 'vance_practitioner_tile_extra', array(
        'default'           => 'Bridging science and clinical outcomes',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_practitioner_tile_extra', array(
        'label'   => __( 'Practitioner Tile Extra Font Text', 'sla-health-hub' ),
        'section' => 'vance_pathway_tiles_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_practitioner_tile_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_practitioner_tile_image', array(
        'label'   => __( 'Practitioner Tile Bottom Image', 'sla-health-hub' ),
        'section' => 'vance_pathway_tiles_settings',
    ) ) );

    // Patient Tile
    $wp_customize->add_setting( 'vance_patient_tile_title', array(
        'default'           => 'For Patients',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_patient_tile_title', array(
        'label'   => __( 'Patient Tile Title', 'sla-health-hub' ),
        'section' => 'vance_pathway_tiles_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_patient_tile_desc', array(
        'default'           => 'Learn about chronic conditions, health optimization, and healthy living through our expert-led patient curriculum.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'vance_patient_tile_desc', array(
        'label'   => __( 'Patient Tile Description', 'sla-health-hub' ),
        'section' => 'vance_pathway_tiles_settings',
        'type'    => 'textarea',
    ) );

    $wp_customize->add_setting( 'vance_patient_tile_extra', array(
        'default'           => 'Empowering your health journey daily',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_patient_tile_extra', array(
        'label'   => __( 'Patient Tile Extra Font Text', 'sla-health-hub' ),
        'section' => 'vance_pathway_tiles_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_patient_tile_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_patient_tile_image', array(
        'label'   => __( 'Patient Tile Bottom Image', 'sla-health-hub' ),
        'section' => 'vance_pathway_tiles_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_practitioner_tile_link', array( 'default' => '/healthcare-professionals/', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_practitioner_tile_link', array( 'label' => 'Practitioner Tile Link', 'section' => 'vance_pathway_tiles_settings', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_patient_tile_link', array( 'default' => '/patients/', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_patient_tile_link', array( 'label' => 'Patient Tile Link', 'section' => 'vance_pathway_tiles_settings', 'type' => 'text' ) );

    // Border Radius Controls - Patient
    $wp_customize->add_setting( 'vance_patient_tile_radius', array(
        'default'           => 16,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_patient_tile_radius', array(
        'label'   => __( 'Patient Tile Radius (px)', 'sla-health-hub' ),
        'section' => 'vance_pathway_tiles_settings',
        'type'    => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 100 ),
    ) );

    $wp_customize->add_setting( 'vance_patient_image_radius', array(
        'default'           => 8,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_patient_image_radius', array(
        'label'   => __( 'Patient Image Radius (px)', 'sla-health-hub' ),
        'section' => 'vance_pathway_tiles_settings',
        'type'    => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 100 ),
    ) );

    // Border Radius Controls - Practitioner
    $wp_customize->add_setting( 'vance_practitioner_tile_radius', array(
        'default'           => 16,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_practitioner_tile_radius', array(
        'label'   => __( 'Practitioner Tile Radius (px)', 'sla-health-hub' ),
        'section' => 'vance_pathway_tiles_settings',
        'type'    => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 100 ),
    ) );

    $wp_customize->add_setting( 'vance_practitioner_image_radius', array(
        'default'           => 8,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_practitioner_image_radius', array(
        'label'   => __( 'Practitioner Image Radius (px)', 'sla-health-hub' ),
        'section' => 'vance_pathway_tiles_settings',
        'type'    => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 100 ),
    ) );

    // Pathway card hover colour
    $wp_customize->add_setting( 'vance_pathway_card_hover_color', array(
        'default'           => '#008080',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_pathway_card_hover_color', array(
        'label'       => __( 'Card Hover Background Colour', 'sla-health-hub' ),
        'description' => __( 'Background colour applied to pathway cards on hover. Text and icon automatically invert to white.', 'sla-health-hub' ),
        'section'     => 'vance_pathway_tiles_settings',
    ) ) );

    // Pathway section label
    $wp_customize->add_setting( 'vance_pathway_who_label', array(
        'default'           => 'Who Am I?',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_pathway_who_label', array(
        'label'   => __( 'Section Label', 'sla-health-hub' ),
        'section' => 'vance_pathway_tiles_settings',
        'type'    => 'text',
    ) );

    // Pathway card icon background colour
    $wp_customize->add_setting( 'vance_pathway_icon_bg_color', array(
        'default'           => '#0A1929',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_pathway_icon_bg_color', array(
        'label'       => __( 'Icon Initial Background Colour', 'sla-health-hub' ),
        'section'     => 'vance_pathway_tiles_settings',
    ) ) );

    // Pathway card icon hover background colour
    $wp_customize->add_setting( 'vance_pathway_icon_hover_bg_color', array(
        'default'           => 'rgba(255,255,255,0.2)',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_pathway_icon_hover_bg_color', array(
        'label'       => __( 'Icon Hover Background Colour (Hex or RGBA)', 'sla-health-hub' ),
        'section'     => 'vance_pathway_tiles_settings',
        'type'        => 'text',
    ) );

    // 2.6.5 Latest Content Grid Settings (Right side of Pathway section)
    $wp_customize->add_section( 'vance_pathway_latest_settings', array(
        'title'    => __( 'Pathway Section: Latest Content', 'sla-health-hub' ),
        'priority' => 31.65,
        'panel'    => 'vance_homepage_panel',
    ) );

    $wp_customize->add_setting( 'vance_pathway_latest_title', array(
        'default'           => 'LATEST CONTENT',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_pathway_latest_title', array(
        'label'   => __( 'Grid Title', 'sla-health-hub' ),
        'section' => 'vance_pathway_latest_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_pathway_latest_count', array(
        'default'           => 3,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_pathway_latest_count', array(
        'label'   => __( 'Number of Posts', 'sla-health-hub' ),
        'description' => __( 'The bento layout style works best with 3 items.', 'sla-health-hub' ),
        'section' => 'vance_pathway_latest_settings',
        'type'    => 'number',
        'input_attrs' => array( 'min' => 1, 'max' => 10 ),
    ) );

    $wp_customize->add_setting( 'vance_pathway_latest_category', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_pathway_latest_category', array(
        'label'   => __( 'Filter by Category', 'sla-health-hub' ),
        'description' => __( 'Select a specific category or show latest from all.', 'sla-health-hub' ),
        'section' => 'vance_pathway_latest_settings',
        'type'    => 'select',
        'choices' => vance_get_cpt_category_choices(),
    ) );

    $wp_customize->add_setting( 'vance_pathway_latest_show_date', array(
        'default'           => true,
        'sanitize_callback' => 'vance_sanitize_checkbox',
    ) );
    $wp_customize->add_control( 'vance_pathway_latest_show_date', array(
        'label'   => __( 'Show Post Date', 'sla-health-hub' ),
        'section' => 'vance_pathway_latest_settings',
        'type'    => 'checkbox',
    ) );

    // 2.7 Knowledgebase Mini-Hero Section
    $wp_customize->add_section( 'vance_kb_mini_hero', array(
        'title'    => __( 'Knowledge Base Mini-Hero', 'sla-health-hub' ),
        'priority' => 31.7,
        'panel'    => 'vance_content_panel',
    ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_title', array( 'default' => 'IBD Research KNOWLEDGEBASE', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_title', array( 'label' => 'Main Title Text', 'section' => 'vance_kb_mini_hero', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_subtitle', array( 'default' => 'Catch Up on the Latest Articles and More...', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_subtitle', array( 'label' => 'Subtitle Text', 'section' => 'vance_kb_mini_hero', 'type' => 'textarea' ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_bg', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_kb_mini_hero_bg', array( 'label' => 'Background Image', 'section' => 'vance_kb_mini_hero' ) ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_font_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_kb_mini_hero_font_color', array( 'label' => 'Font Color', 'section' => 'vance_kb_mini_hero' ) ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_height', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_height', array( 'label' => 'Min-Height (px)', 'section' => 'vance_kb_mini_hero', 'type' => 'number' ) );
    
    $wp_customize->add_setting( 'vance_kb_mini_hero_padding', array( 'default' => '60px 0 80px', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_padding', array( 'label' => 'Padding (e.g. 60px 0 80px)', 'section' => 'vance_kb_mini_hero', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_kb_mini_hero_opacity', array( 'default' => 80, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( 'vance_kb_mini_hero_opacity', array( 'label' => 'Overlay Opacity (%)', 'section' => 'vance_kb_mini_hero', 'type' => 'range', 'input_attrs' => array( 'min' => 0, 'max' => 100, 'step' => 5 ) ) );

    // 2.8 Join the Hub Section
    $wp_customize->add_section( 'vance_join_community', array(
        'title'    => __( 'Join the Hub Block', 'sla-health-hub' ),
        'priority' => 31.8,
        'panel'    => 'vance_content_panel',
    ) );

    $wp_customize->add_setting( 'vance_join_title', array(
        'default'           => 'Join the Hub',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_join_title', array(
        'label'   => __( 'Main Title', 'sla-health-hub' ),
        'section' => 'vance_join_community',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_join_subtitle', array(
        'default'           => 'Select your role to get started with a personalized experience.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'vance_join_subtitle', array(
        'label'   => __( 'Subtitle', 'sla-health-hub' ),
        'section' => 'vance_join_community',
        'type'    => 'textarea',
    ) );

    $wp_customize->add_setting( 'vance_join_practitioner_label', array(
        'default'           => "I'm a Healthcare Practitioner",
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_join_practitioner_label', array(
        'label'   => __( 'Practitioner Checkbox Label', 'sla-health-hub' ),
        'section' => 'vance_join_community',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_join_patient_label', array(
        'default'           => "I'm a Patient / Caregiver",
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_join_patient_label', array(
        'label'   => __( 'Patient Checkbox Label', 'sla-health-hub' ),
        'section' => 'vance_join_community',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_join_button_text', array(
        'default'           => 'REGISTER NOW',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_join_button_text', array(
        'label'   => __( 'Button Text', 'sla-health-hub' ),
        'section' => 'vance_join_community',
        'type'    => 'text',
    ) );


    // 2.9 Ask AI Settings
    $wp_customize->add_section( 'vance_askai_settings', array(
        'title'    => __( 'Ask AI Configuration', 'sla-health-hub' ),
        'priority' => 31.9,
        'panel'    => 'vance_content_panel',
    ) );

    // Hero Settings
    $wp_customize->add_setting( 'vance_askai_hero_bg', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_askai_hero_bg', array(
        'label'   => __( 'Hero Background Image', 'sla-health-hub' ),
        'section' => 'vance_askai_settings',
    ) ) );

    $wp_customize->add_setting( 'vance_askai_hero_title', array(
        'default'           => 'Clinical AI Assistant',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_hero_title', array(
        'label'   => __( 'Hero Title', 'sla-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_askai_hero_subtitle', array(
        'default'           => 'Ask complex clinical questions and get evidence-based answers instantly.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'vance_askai_hero_subtitle', array(
        'label'   => __( 'Hero Subtitle', 'sla-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'textarea',
    ) );

    $wp_customize->add_setting( 'vance_askai_hero_badge', array(
        'default'           => 'Beta Feature v1.0',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_hero_badge', array(
        'label'   => __( 'Hero Badge Text', 'sla-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'text',
    ) );

    // API Credentials
    $wp_customize->add_setting( 'vance_askai_api_key', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_api_key', array(
        'label'       => __( 'AI API Key', 'sla-health-hub' ),
        'description' => __( 'Enter your OpenAI or Anthropic API key', 'sla-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vance_askai_api_provider', array(
        'default'           => 'openai',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_api_provider', array(
        'label'   => __( 'AI Provider', 'sla-health-hub' ),
        'section' => 'vance_askai_settings',
        'type'    => 'select',
        'choices' => array(
            'openai'     => 'OpenAI (GPT-4)',
            'anthropic'  => 'Anthropic (Claude)',
            'google'     => 'Google (Gemini)',
        ),
    ) );

    $wp_customize->add_setting( 'vance_askai_model', array(
        'default'           => 'gpt-4',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_askai_model', array(
        'label'       => __( 'AI Model', 'sla-health-hub' ),
        'description' => __( 'e.g., gpt-4, claude-3-opus, gemini-pro', 'sla-health-hub' ),
        'section'     => 'vance_askai_settings',
        'type'        => 'text',
    ) );


    // 4. Dynamic Homepage & Inner Nav Category Cards
    $wp_customize->add_section( 'vance_homepage_categories', array(
        'title'       => __( 'Category Cards', 'sla-health-hub' ),
        'description' => __( 'Configure display logic for category cards across the site.', 'sla-health-hub' ),
        'priority'    => 33,
        'panel'       => 'vance_homepage_panel',
    ) );

    // --- HOMEPAGE SPECIFIC ---
    $wp_customize->add_setting( 'vance_homepage_cards_per_row', array(
        'default'           => 6,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_homepage_cards_per_row', array(
        'label'   => __( 'Homepage: Cards Per Row', 'sla-health-hub' ),
        'section' => 'vance_homepage_categories',
        'type'    => 'number',
        'input_attrs' => array('min' => 1, 'max' => 12),
    ) );

    $wp_customize->add_setting( 'vance_homepage_card_alignment', array(
        'default'           => 'center',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_homepage_card_alignment', array(
        'label'   => __( 'Homepage: Card Alignment', 'sla-health-hub' ),
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
        'label'   => __( 'Show Inner Page Horizontal Nav', 'sla-health-hub' ),
        'section' => 'vance_homepage_categories',
        'type'    => 'checkbox',
    ) );

    $wp_customize->add_setting( 'vance_inner_nav_cards_per_row', array(
        'default'           => 8,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_inner_nav_cards_per_row', array(
        'label'   => __( 'Inner Nav: Cards Per Row', 'sla-health-hub' ),
        'section' => 'vance_homepage_categories',
        'type'    => 'number',
        'input_attrs' => array('min' => 1, 'max' => 16),
    ) );

    $wp_customize->add_setting( 'vance_inner_nav_total_items', array(
        'default'           => 8,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_inner_nav_total_items', array(
        'label'   => __( 'Inner Nav: Total Items to Show', 'sla-health-hub' ),
        'section' => 'vance_homepage_categories',
        'type'    => 'number',
        'input_attrs' => array('min' => 1, 'max' => 50),
    ) );

    // --- DISCOVERY SUITE STYLING ---
    $wp_customize->add_section( 'vance_discovery_styling', array(
        'title'    => __( 'Discovery Engine (Styling)', 'sla-health-hub' ),
        'priority' => 32,
        'panel'    => 'vance_homepage_panel',
    ) );

    // Titles
    $wp_customize->add_setting( 'vance_discovery_title_text', array( 'default' => 'Content Discovery Suite', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_discovery_title_text', array( 'label' => 'Main Title Text', 'section' => 'vance_discovery_styling', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_discovery_subtitle_text', array( 'default' => 'Explore our comprehensive database...', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    $wp_customize->add_control( 'vance_discovery_subtitle_text', array( 'label' => 'Subtitle Text', 'section' => 'vance_discovery_styling', 'type' => 'textarea' ) );

    $wp_customize->add_setting( 'vance_askai_text_size', array( 'default' => 15, 'sanitize_callback' => 'absint' ) );
    $wp_customize->add_control( 'vance_askai_text_size', array( 'label' => 'Ask AI Text Size (px)', 'section' => 'vance_discovery_styling', 'type' => 'number', 'input_attrs' => array('min' => 10, 'max' => 24) ) );

    $wp_customize->add_setting( 'vance_askai_text_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_askai_text_color', array( 'label' => 'Ask AI Text Color (Hex or RGBA)', 'section' => 'vance_discovery_styling', 'type' => 'text' ) );

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

    // --- DISCOVERY SUITE GRID LOGIC ---
    $wp_customize->add_setting( 'vance_discovery_path_cols', array(
        'default'           => 4,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_discovery_path_cols', array(
        'label'   => __( 'Discovery: Path Columns', 'sla-health-hub' ),
        'section' => 'vance_discovery_path',
        'type'    => 'number',
        'input_attrs' => array('min' => 1, 'max' => 6),
    ) );

    $wp_customize->add_setting( 'vance_discovery_type_cols', array(
        'default'           => 6,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_discovery_type_cols', array(
        'label'   => __( 'Discovery: Type Columns', 'sla-health-hub' ),
        'section' => 'vance_discovery_type',
        'type'    => 'number',
        'input_attrs' => array('min' => 1, 'max' => 10),
    ) );

    // --- INDIVIDUAL CARD CONTROLS ---
    $categories = get_categories( array( 'hide_empty' => false ) );
    foreach ( $categories as $cat ) {
        // Show/Hide Toggle
        $wp_customize->add_setting( "vance_cat_card_show_{$cat->term_id}", array(
            'default'           => true,
            'sanitize_callback' => 'vance_sanitize_checkbox',
        ) );
        $wp_customize->add_control( "vance_cat_card_show_{$cat->term_id}", array(
            'label'   => sprintf( __( 'Show "%s" Card', 'sla-health-hub' ), $cat->name ),
            'section' => 'vance_homepage_categories',
            'type'    => 'checkbox',
        ) );
        
        // Priority (Order)
        $wp_customize->add_setting( "vance_cat_card_priority_{$cat->term_id}", array(
            'default'           => 10,
            'sanitize_callback' => 'absint',
        ) );
        $wp_customize->add_control( "vance_cat_card_priority_{$cat->term_id}", array(
            'label'       => sprintf( __( '"%s" Priority (Order)', 'sla-health-hub' ), $cat->name ),
            'description' => __( 'Lower numbers appear first.', 'sla-health-hub' ),
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
            'label'       => sprintf( __( '"%s" Icon', 'sla-health-hub' ), $cat->name ),
            'description' => __( 'Optional: Upload custom icon (recommended: 48x48px PNG).', 'sla-health-hub' ),
            'section'     => 'vance_homepage_categories',
        ) ) );
    }

    // 4. Social API Settings Section
    $wp_customize->add_section( 'vance_social_api', array(
        'title'       => __( 'Social API Settings', 'sla-health-hub' ),
        'description' => __( 'Configure the webhook URL for social media automation (e.g. Make/Zapier).', 'sla-health-hub' ),
        'priority'    => 33,
        'panel'       => 'vance_advanced_panel',
    ) );

    $wp_customize->add_setting( 'vance_social_webhook_url', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );

    $wp_customize->add_control( 'vance_social_webhook_url', array(
        'label'   => __( 'Automation Webhook URL', 'sla-health-hub' ),
        'section' => 'vance_social_api',
        'type'    => 'url',
    ) );

    // 4.5 Newsletter Settings
    $wp_customize->add_section( 'vance_newsletter', array(
        'title'       => __( 'Newsletter', 'sla-health-hub' ),
        'priority'    => 33,
        'panel'       => 'vance_footer_panel',
    ) );

    $wp_customize->add_setting( 'vance_newsletter_action', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( 'vance_newsletter_action', array(
        'label'       => __( 'Form Action URL', 'sla-health-hub' ),
        'description' => __( 'Mailchimp/Hubspot form action URL.', 'sla-health-hub' ),
        'section'     => 'vance_newsletter',
        'type'        => 'url',
    ) );

    $wp_customize->add_setting( 'vance_newsletter_heading', array(
        'default'           => 'Join the Hub',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_newsletter_heading', array(
        'label'   => __( 'Heading', 'sla-health-hub' ),
        'section' => 'vance_newsletter',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vance_newsletter_desc', array(
        'default'           => 'Get the latest clinical reviews and tools.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'vance_newsletter_desc', array(
        'label'   => __( 'Description', 'sla-health-hub' ),
        'section' => 'vance_newsletter',
        'type'    => 'textarea',
    ) );

    // 4.6 Footer Brand & Widgets
    $wp_customize->add_section( 'vance_footer_brand', array(
        'title'       => __( 'Brand & Widgets', 'sla-health-hub' ),
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
        'title'       => __( 'Category Heroes', 'sla-health-hub' ),
        'description' => __( 'Set unique hero images, taglines, and titles for specific categories.', 'sla-health-hub' ),
        'priority'    => 34,
        'panel'       => 'vance_content_panel',
    ) );

    foreach ( $categories as $cat ) {
        // Hero Image
        $wp_customize->add_setting( "vance_cat_hero_{$cat->term_id}", array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_cat_hero_{$cat->term_id}", array(
            'label'   => sprintf( __( '%s: Hero Image', 'sla-health-hub' ), $cat->name ),
            'section' => 'vance_category_heroes',
        ) ) );

        // Tagline
        $wp_customize->add_setting( "vance_cat_tagline_{$cat->term_id}", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( "vance_cat_tagline_{$cat->term_id}", array(
            'label'   => sprintf( __( '%s: Tagline', 'sla-health-hub' ), $cat->name ),
            'section' => 'vance_category_heroes',
            'type'    => 'text',
        ) );

        // Title Override (New)
        $wp_customize->add_setting( "vance_cat_title_{$cat->term_id}", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( "vance_cat_title_{$cat->term_id}", array(
            'label'   => sprintf( __( '%s: Title Override', 'sla-health-hub' ), $cat->name ),
            'section' => 'vance_category_heroes',
            'type'    => 'text',
        ) );
    }

    // 6. Homepage Section Ordering
    $wp_customize->add_section( 'vance_homepage_order', array(
        'title'    => __( 'Section Order', 'sla-health-hub' ),
        'priority' => 35,
        'panel'    => 'vance_homepage_panel',
    ) );

    $wp_customize->add_setting( 'vance_homepage_section_order', array(
        'default'           => 'hero,pathway,promo,cats,discovery,kb,testimonials',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_homepage_section_order', array(
        'label'       => __( 'Section Order (Comma-separated IDs)', 'sla-health-hub' ),
        'description' => __( 'IDs: hero, pathway, promo, cats, discovery, kb, testimonials', 'sla-health-hub' ),
        'section'     => 'vance_homepage_order',
        'type'        => 'text',
    ) );

    // 7. Promo Content Block (New)
    $wp_customize->add_section( 'vance_promo_block', array(
        'title'    => __( 'Promo Block', 'sla-health-hub' ),
        'priority' => 31.55,
        'panel'    => 'vance_homepage_panel',
    ) );

    $wp_customize->add_setting( 'vance_promo_show', array( 'default' => false, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
    $wp_customize->add_control( 'vance_promo_show', array( 'label' => 'Show Promo Block', 'section' => 'vance_promo_block', 'type' => 'checkbox' ) );

    $wp_customize->add_setting( 'vance_promo_heading', array( 'default' => 'Experience the Hub', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_promo_heading', array( 'label' => 'Heading', 'section' => 'vance_promo_block', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_promo_text', array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    $wp_customize->add_control( 'vance_promo_text', array( 'label' => 'Body Text', 'section' => 'vance_promo_block', 'type' => 'textarea' ) );

    $wp_customize->add_setting( 'vance_promo_image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vance_promo_image', array( 'label' => 'Promo Image', 'section' => 'vance_promo_block' ) ) );

    $wp_customize->add_setting( 'vance_promo_bg_color', array( 'default' => '#F8FAFC', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_promo_bg_color', array( 'label' => 'Background Color', 'section' => 'vance_promo_block' ) ) );

    $wp_customize->add_setting( 'vance_promo_text_color', array( 'default' => '#0F172A', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vance_promo_text_color', array( 'label' => 'Text Color', 'section' => 'vance_promo_block' ) ) );

    $wp_customize->add_setting( 'vance_promo_button_text', array( 'default' => 'Get Started Now', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_promo_button_text', array( 'label' => 'Button Text', 'section' => 'vance_promo_block', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_promo_button_link', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'vance_promo_button_link', array( 'label' => 'Button Link', 'section' => 'vance_promo_block', 'type' => 'text' ) );

    $wp_customize->add_setting( 'vance_promo_width', array( 'default' => 'container', 'sanitize_callback' => 'sanitize_key' ) );
    $wp_customize->add_control( 'vance_promo_width', array( 'label' => 'Width', 'section' => 'vance_promo_block', 'type' => 'select', 'choices' => array('container' => 'Container (Narrow)', 'full' => 'Full Width') ) );

    $wp_customize->add_setting( 'vance_promo_layout', array( 'default' => 'right', 'sanitize_callback' => 'sanitize_key' ) );
    $wp_customize->add_control( 'vance_promo_layout', array( 'label' => 'Image Position', 'section' => 'vance_promo_block', 'type' => 'select', 'choices' => array('left' => 'Left', 'right' => 'Right', 'top' => 'Top') ) );

    // 8. Join Block Settings
    $wp_customize->add_section( 'vance_join_community', array(
        'title'    => __( 'Join Block', 'sla-health-hub' ),
        'priority' => 31.6,
        'panel'    => 'vance_homepage_panel',
    ) );

    // 8. Join Toggle
    $wp_customize->add_setting( 'vance_join_show', array( 'default' => true, 'sanitize_callback' => 'vance_sanitize_checkbox' ) );
    $wp_customize->add_control( 'vance_join_show', array( 'label' => 'Show "Join the Hub" Block', 'section' => 'vance_join_community', 'type' => 'checkbox' ) );

    // 6. Dynamic Knowledgebase Sections
    $wp_customize->add_section( 'vance_knowledgebase_sections', array(
        'title'       => __( 'Knowledge Base', 'sla-health-hub' ),
        'description' => __( 'Control which categories appear as content sections on the homepage.', 'sla-health-hub' ),
        'priority'    => 35,
        'panel'       => 'vance_content_panel',
    ) );

    // KB Mini-Hero Settings have been consolidated in the "Knowledge Base Mini-Hero" section above.

    $categories = get_categories( array( 'hide_empty' => false ) );
    
    foreach ( $categories as $cat ) {
        // Show/Hide Toggle
        $wp_customize->add_setting( "vance_kb_show_{$cat->term_id}", array(
            'default'           => true,
            'sanitize_callback' => 'vance_sanitize_checkbox',
        ) );
        $wp_customize->add_control( "vance_kb_show_{$cat->term_id}", array(
            'label'   => sprintf( __( 'Show "%s" Section', 'sla-health-hub' ), $cat->name ),
            'section' => 'vance_knowledgebase_sections',
            'type'    => 'checkbox',
        ) );
        
        // Priority (Order)
        $wp_customize->add_setting( "vance_kb_priority_{$cat->term_id}", array(
            'default'           => 10,
            'sanitize_callback' => 'absint',
        ) );
        $wp_customize->add_control( "vance_kb_priority_{$cat->term_id}", array(
            'label'       => sprintf( __( '"%s" Priority (Order)', 'sla-health-hub' ), $cat->name ),
            'description' => __( 'Lower numbers appear first.', 'sla-health-hub' ),
            'section'     => 'vance_knowledgebase_sections',
            'type'        => 'number',
            'input_attrs' => array( 'min' => 1, 'max' => 100 ),
        ) );
        
        // Description
        $wp_customize->add_setting( "vance_kb_desc_{$cat->term_id}", array(
            'default'           => $cat->description,
            'sanitize_callback' => 'sanitize_textarea_field',
        ) );
        $wp_customize->add_control( "vance_kb_desc_{$cat->term_id}", array(
            'label'   => sprintf( __( '"%s" Description', 'sla-health-hub' ), $cat->name ),
            'section' => 'vance_knowledgebase_sections',
            'type'    => 'textarea',
        ) );
        
        // Post Count
        $wp_customize->add_setting( "vance_kb_count_{$cat->term_id}", array(
            'default'           => 4,
            'sanitize_callback' => 'absint',
        ) );
        $wp_customize->add_control( "vance_kb_count_{$cat->term_id}", array(
            'label'       => sprintf( __( '"%s" Number of Posts', 'sla-health-hub' ), $cat->name ),
            'section'     => 'vance_knowledgebase_sections',
            'type'        => 'number',
            'input_attrs' => array( 'min' => 1, 'max' => 12, 'step' => 1 ),
        ) );
        
        $wp_customize->add_setting( "vance_kb_view_all_{$cat->term_id}", array(
            'default'           => 'View All',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( "vance_kb_view_all_{$cat->term_id}", array(
            'label'   => sprintf( __( '"%s" View All Label', 'sla-health-hub' ), $cat->name ),
            'section' => 'vance_knowledgebase_sections',
            'type'    => 'text',
        ) );

        // Layout
        $wp_customize->add_setting( "vance_kb_layout_{$cat->term_id}", array(
            'default'           => 'grid-4',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( "vance_kb_layout_{$cat->term_id}", array(
            'label'   => sprintf( __( '"%s" Layout', 'sla-health-hub' ), $cat->name ),
            'section' => 'vance_knowledgebase_sections',
            'type'    => 'select',
            'choices' => array(
                'grid-4'     => 'Standard Grid (4 Cols)',
                'bento'      => 'Bento Grid (News Style)',
                'asymmetric' => 'Asymmetric (Review Style)',
                'posters'    => 'Posters (Opinion Style)',
            ),
        ) );
    }

    // 7. Scripts & Analytics
    $wp_customize->add_section( 'vance_scripts', array(
        'title'       => __( 'Scripts', 'sla-health-hub' ),
        'priority'    => 40,
        'description' => __( 'Add Google Analytics (GA4), Tag Manager, or other tracking scripts.', 'sla-health-hub' ),
        'panel'       => 'vance_advanced_panel',
    ) );

    $wp_customize->add_setting( 'vance_header_scripts', array(
        'default'           => '',
        'sanitize_callback' => 'vance_sanitize_scripts', // We need to allow script tags
    ) );
    $wp_customize->add_control( 'vance_header_scripts', array(
        'label'       => __( 'Header Scripts (Before </head>)', 'sla-health-hub' ),
        'section'     => 'vance_scripts',
        'type'        => 'textarea',
    ) );

    $wp_customize->add_setting( 'vance_footer_scripts', array(
        'default'           => '',
        'sanitize_callback' => 'vance_sanitize_scripts',
    ) );
    $wp_customize->add_control( 'vance_footer_scripts', array(
        'label'       => __( 'Footer Scripts (Before </body>)', 'sla-health-hub' ),
        'section'     => 'vance_scripts',
        'type'        => 'textarea',
    ) );

    // Move core Site Identity section to our panel
    if ( $wp_customize->get_section( 'title_tagline' ) ) {
        $wp_customize->get_section( 'title_tagline' )->panel = 'vance_brand_panel';
        $wp_customize->get_section( 'title_tagline' )->title = __( 'Site Title & Logo', 'sla-health-hub' );
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
        __( 'Social Media Automation', 'sla-health-hub' ),
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
            <?php _e( 'Enable Auto-Post', 'sla-health-hub' ); ?>
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
    add_role( 'practitioner', __( 'Practitioner', 'sla-health-hub' ), array(
        'read' => true, 
        'edit_posts' => false,
        'delete_posts' => false,
    ));
    add_role( 'patient', __( 'Patient', 'sla-health-hub' ), array(
        'read' => true, 
        'edit_posts' => false,
        'delete_posts' => false,
    ));
}
add_action( 'init', 'vance_setup_custom_roles' );

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
        .login form { box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-radius: 0; border: 1px solid #e2e8f0; }
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
 * Default new signups to 'patient' role
 */
function vance_default_user_role_on_register( $user_id ) {
    $user = new WP_User( $user_id );
    $user->set_role( 'patient' );
    
    update_user_meta( $user_id, '_sla_user_type', 'patient' );
    update_user_meta( $user_id, '_sla_dashboard_role', 'patient' );
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
            border-radius: 0;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: none;
        }
        
        .login form .input {
            border-radius: 0;
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
            border-radius: 0;
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
            border-radius: 0;
            padding: 12px 16px;
        }
        
        .login #login_error {
            background: #FEE2E2;
            border-left: 4px solid #EF4444;
            border-radius: 0;
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
        <a href="' . home_url('/register/') . '" style="color: white; font-weight: 600; text-decoration: none; background: #008080; padding: 10px 24px; border-radius: 0; display: inline-block; box-shadow: 0 4px 12px rgba(0,128,128, 0.3);">Create New Account</a>
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
            $user->set_role('subscriber');
            update_user_meta($user_id, '_sla_user_type', 'patient');
            update_user_meta($user_id, '_sla_dashboard_role', 'patient');
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
        'name'                  => _x( 'Testimonials', 'Post Type General Name', 'sla-health-hub' ),
        'singular_name'         => _x( 'Testimonial', 'Post Type Singular Name', 'sla-health-hub' ),
        'menu_name'             => __( 'Testimonials', 'sla-health-hub' ),
        'all_items'             => __( 'All Testimonials', 'sla-health-hub' ),
        'add_new_item'          => __( 'Add New Testimonial', 'sla-health-hub' ),
        'new_item'              => __( 'New Testimonial', 'sla-health-hub' ),
    );
    $args = array(
        'label'                 => __( 'Testimonial', 'sla-health-hub' ),
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
        'title'    => __( 'Content: Testimonials', 'sla-health-hub' ),
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
        'label'    => __( 'Show Section', 'sla-health-hub' ),
        'section'  => 'vance_testimonials_section',
        'type'     => 'checkbox',
    ) );

    // Heading
    $wp_customize->add_setting( 'vance_testimonial_heading', array(
        'default'           => 'What Our Community Says',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vance_testimonial_heading', array(
        'label'   => __( 'Section Heading', 'sla-health-hub' ),
        'section' => 'vance_testimonials_section',
        'type'    => 'text',
    ) );

    // Heading alignment
    $wp_customize->add_setting( 'vance_testimonial_heading_align', array(
        'default'           => 'left',
        'sanitize_callback' => 'sanitize_key',
    ) );
    $wp_customize->add_control( 'vance_testimonial_heading_align', array(
        'label'   => __( 'Heading Alignment', 'sla-health-hub' ),
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
        'label'   => __( 'Selection Mode', 'sla-health-hub' ),
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
        'label'       => __( 'Specific Testimonial IDs', 'sla-health-hub' ),
        'description' => __( 'Comma-separated list (e.g. 104, 156). Ignored if mode is "Latest".', 'sla-health-hub' ),
        'section'     => 'vance_testimonials_section',
        'type'        => 'text',
    ) );

    // Count
    $wp_customize->add_setting( 'vance_testimonial_count', array(
        'default'           => 3,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'vance_testimonial_count', array(
        'label'       => __( 'Number to Show', 'sla-health-hub' ),
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
            'quote' => 'The omega-3 calculator and recipe tools are the most practical resources I\'ve found anywhere. Finally something built for the patient, not the clinician.',
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
            'label'       => sprintf( __( 'Testimonial %d — Quote', 'sla-health-hub' ), $i ),
            'description' => 1 === $i ? __( 'Inline testimonials show when no Testimonial posts exist. Leave Quote blank to skip a slot.', 'sla-health-hub' ) : '',
            'section'     => 'vance_testimonials_section',
            'type'        => 'textarea',
        ) );

        $wp_customize->add_setting( "vance_testimonial_inline_{$i}_name", array(
            'default'           => $d['name'],
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( "vance_testimonial_inline_{$i}_name", array(
            'label'   => sprintf( __( 'Testimonial %d — Name', 'sla-health-hub' ), $i ),
            'section' => 'vance_testimonials_section',
            'type'    => 'text',
        ) );

        $wp_customize->add_setting( "vance_testimonial_inline_{$i}_role", array(
            'default'           => $d['role'],
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( "vance_testimonial_inline_{$i}_role", array(
            'label'   => sprintf( __( 'Testimonial %d — Role / Subtitle', 'sla-health-hub' ), $i ),
            'section' => 'vance_testimonials_section',
            'type'    => 'text',
        ) );

        $wp_customize->add_setting( "vance_testimonial_inline_{$i}_image", array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_testimonial_inline_{$i}_image", array(
            'label'   => sprintf( __( 'Testimonial %d — Image (optional)', 'sla-health-hub' ), $i ),
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
                'label'   => __( $cfg['label'], 'sla-health-hub' ),
                'section' => 'vance_testimonials_section',
            ) ) );
        } else {
            $wp_customize->add_control( $setting_id, array(
                'label'   => __( $cfg['label'], 'sla-health-hub' ),
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
                    <div class="color-bar" style="background: <?php echo esc_attr( $accent ); ?>; width: 6px; height: <?php echo max( 16, $heading_size ); ?>px; border-radius: 0;"></div>
                    <h2 style="margin: 0; font-size: <?php echo $heading_size; ?>px; font-weight: 800; color: <?php echo esc_attr( $heading_col ); ?>; font-family: 'Outfit', sans-serif; text-transform: uppercase;"><?php echo esc_html( $heading ); ?></h2>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
                <?php foreach ( $items as $item ) : ?>
                    <div style="background: <?php echo esc_attr( $card_bg ); ?>; border-radius: 0; padding: 40px 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid <?php echo esc_attr( $card_border ); ?>; display: flex; flex-direction: column; position: relative;">
                        <div style="position: absolute; top: 24px; right: 24px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="<?php echo esc_attr( $accent ); ?>" style="opacity: 0.1;">
                                <path d="M14.017 21L14.017 18C14.017 16.8954 14.9124 16 16.017 16H19.017C19.5693 16 20.017 15.5523 20.017 15V9C20.017 8.44772 19.5693 8 19.017 8H15.017C14.4647 8 14.017 8.44772 14.017 9V11C14.017 11.5523 13.5693 12 13.017 12H12.017V5H22.017V15C22.017 18.3137 19.3307 21 16.017 21H14.017ZM5.01697 21L5.01697 18C5.01697 16.8954 5.9124 16 7.01697 16H10.017C10.5693 16 11.017 15.5523 11.017 15V9C11.017 8.44772 10.5693 8 10.017 8H6.01697C5.46468 8 5.01697 8.44772 5.01697 9V11C5.01697 11.5523 4.56925 12 4.01697 12H3.01697V5H13.017V15C13.017 18.3137 10.3307 21 7.01697 21H5.01697Z"></path>
                            </svg>
                        </div>

                        <div style="font-family: 'Inter', sans-serif; font-size: <?php echo $quote_size; ?>px; color: <?php echo esc_attr( $quote_col ); ?>; line-height: 1.7; font-style: italic; margin-bottom: 24px; flex-grow: 1;">
                            "<?php echo wp_kses_post( $item['quote'] ); ?>"
                        </div>

                        <div style="display: flex; align-items: center; gap: 16px; border-top: 1px solid <?php echo esc_attr( $card_border ); ?>; padding-top: 24px; margin-top: auto;">
                            <?php if ( ! empty( $item['image'] ) ) : ?>
                                <img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>" style="width: 56px; height: 56px; border-radius: 0; object-fit: cover; border: 3px solid <?php echo esc_attr( $sec_bg ); ?>;">
                            <?php else : ?>
                                <div style="width: 56px; height: 56px; border-radius: 0; background: <?php echo esc_attr( $avatar_bg ); ?>; color: <?php echo esc_attr( $avatar_col ); ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; font-family: 'Outfit', sans-serif;">
                                    <?php echo esc_html( strtoupper( substr( $item['name'], 0, 1 ) ) ); ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h4 style="margin: 0; font-size: <?php echo $name_size; ?>px; font-weight: 700; color: <?php echo esc_attr( $name_col ); ?>; font-family: 'Outfit', sans-serif;"><?php echo esc_html( $item['name'] ); ?></h4>
                                <?php if ( ! empty( $item['role'] ) ) : ?>
                                    <span style="font-size: <?php echo $role_size; ?>px; color: <?php echo esc_attr( $role_col ); ?>; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo esc_html( $item['role'] ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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



/**
 * AJAX: Save Detailed Clinical Profile
 */
function vance_save_clinical_profile() {
    check_ajax_referer( 'vance_clinical_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) wp_send_json_error( 'Not logged in' );

    $user_id = get_current_user_id();
    $data = isset($_POST['profile_data']) ? $_POST['profile_data'] : array();
    
    if ( ! is_array( $data ) && is_string( $data ) ) {
        $data = json_decode( stripslashes( $data ), true );
    }

    if ( ! empty( $data ) ) {
        update_user_meta( $user_id, '_sla_clinical_profile', $data );
        wp_send_json_success( 'Clinical profile updated' );
    } else {
        wp_send_json_error( 'No data' );
    }
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
            display: flex; align-items: center; gap: 15px; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: 0; cursor: pointer; transition: all 0.2s;
        }
        .modal-option-item:hover { border-color: #008080; background: #fffcf9; }
        .modal-option-item.selected { border-color: #008080; background: #def4f4; }
        .modal-option-item.selected .modal-option-radio { border-color: #008080 !important; background: #008080; box-shadow: inset 0 0 0 4px white; }
        .option-text { font-size: 15px; font-weight: 600; color: #334155; }
        .modal-btn-save { background: transparent; color: #94a3b8; border: 1px solid #e2e8f0; padding: 14px 24px; border-radius: 0; font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .modal-btn-save:hover { background: #f8fafc; color: #475569; }
    </style>
    <?php
}
add_action( 'wp_footer', 'vance_quiz_modal_styles' );

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


// End of File

