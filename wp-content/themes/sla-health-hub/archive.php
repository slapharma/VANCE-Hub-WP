<?php get_header(); ?>

<main>
    <!-- Hero Section -->
    <!-- Hero Section -->
    <?php
    $hero_bg = get_template_directory_uri() . '/assets/img/news_hero.png'; // Default
    if ( is_post_type_archive() ) {
        $pt = get_query_var( 'post_type' );
        if ( in_array( $pt, ['research', 'whitepaper'] ) ) {
            $hero_bg = get_template_directory_uri() . '/assets/img/research_hero.png';
        } elseif ( in_array( $pt, ['webinar', 'course', 'infographic'] ) ) {
            $hero_bg = get_template_directory_uri() . '/assets/img/education_hero.png';
        } elseif ( in_array( $pt, ['oped', 'review'] ) ) {
            $hero_bg = get_template_directory_uri() . '/assets/img/opinion_hero.png';
        }
    }
    // Handle category archives with enhanced descriptions and taglines
    $category_tagline = '';
    $category_description = '';
    if ( is_category() ) {
       $cat = get_queried_object();
       $cat_name = $cat->name;
       
       // Set hero background based on category
       if ( stripos($cat_name, 'research') !== false || stripos($cat_name, 'Clinical') !== false ) {
           $hero_bg = get_template_directory_uri() . '/assets/img/research_hero.png';
           $category_tagline = 'Evidence-Based Excellence';
           $category_description = 'Dive deep into peer-reviewed clinical research, systematic reviews, and evidence-based analysis that shapes modern healthcare practice.';
       } elseif ( stripos($cat_name, 'education') !== false || stripos($cat_name, 'Course') !== false ) {
           $hero_bg = get_template_directory_uri() . '/assets/img/education_hero.png';
           $category_tagline = 'Elevate Your Expertise';
           $category_description = 'Advance your professional development with CME-accredited courses, interactive webinars, and cutting-edge educational content designed for healthcare professionals.';
       } elseif ( stripos($cat_name, 'News') !== false || stripos($cat_name, 'Healthcare') !== false ) {
           $hero_bg = get_template_directory_uri() . '/assets/img/news_hero.png';
           $category_tagline = 'Stay Ahead of the Curve';
           $category_description = 'Breaking news, industry updates, and the latest developments in healthcare, nutrition science, and longevity medicine.';
       } elseif ( stripos($cat_name, 'Opinion') !== false || stripos($cat_name, 'Expert') !== false ) {
           $hero_bg = get_template_directory_uri() . '/assets/img/opinion_hero.png';
           $category_tagline = 'Insights That Inspire';
           $category_description = 'Thought-provoking perspectives from leading experts, thought leaders, and innovators shaping the future of healthcare and nutritional medicine.';
       } elseif ( stripos($cat_name, 'Media') !== false ) {
           $hero_bg = get_template_directory_uri() . '/assets/img/news_hero.png';
           $category_tagline = 'Learn Through Listening';
           $category_description = 'Engaging podcasts, informative webinars, and compelling video content that brings complex medical concepts to life.';
       } elseif ( stripos($cat_name, 'Infographic') !== false || stripos($cat_name, 'Gallery') !== false ) {
           $hero_bg = get_template_directory_uri() . '/assets/img/education_hero.png';
           $category_tagline = 'Visualize Knowledge';
           $category_description = 'Complex information made simple through beautifully designed infographics, visual guides, and educational graphics.';
       } elseif ( stripos($cat_name, 'Tool') !== false || stripos($cat_name, 'Resource') !== false ) {
           $hero_bg = get_template_directory_uri() . '/assets/img/education_hero.png';
           $category_tagline = 'Empower Your Practice';
           $category_description = 'Practical tools, calculators, and downloadable resources designed to enhance clinical decision-making and patient care.';
       } else {
           $hero_bg = get_template_directory_uri() . '/assets/img/news_hero.png';
           $category_tagline = 'Explore Our Knowledge Hub';
           $category_description = $cat->description ? $cat->description : 'Discover curated content from leading experts in healthcare and nutritional medicine.';
       }
    }
    
    // Override with individual category settings if available
    if ( is_category() ) {
        $cat_id = get_queried_object_id();
        $specific_hero = vance_get_theme_mod( "vance_cat_hero_{$cat_id}" );
        $specific_tagline = vance_get_theme_mod( "vance_cat_tagline_{$cat_id}" );
        
        if ( $specific_hero ) {
            $hero_bg = $specific_hero;
        }
        if ( $specific_tagline ) {
            $category_tagline = $specific_tagline;
        }
    }
    
    // Override default hero if a global category hero is set in Customizer and no specific hero was found
    if ( ! ( is_category() && vance_get_theme_mod( "vance_cat_hero_" . get_queried_object_id() ) ) ) {
        $custom_category_hero = vance_get_theme_mod('vance_category_hero_image');
        if ($custom_category_hero) {
            $hero_bg = $custom_category_hero;
        }
    }


    // Hero Settings
    $title_color = vance_get_theme_mod('vance_hero_title_color', '#ffffff');
    $title_size = vance_get_theme_mod('vance_hero_title_size', 52);
    $mask_enabled = vance_get_theme_mod('vance_hero_mask_toggle', true);
    $mask_opacity = vance_get_theme_mod('vance_hero_mask_opacity', 0.5); 

    $overlay_css = '';
    if ( $mask_enabled ) {
        // Use rgba for opacity (using the brand dark blue/navy colors)
        $overlay_css = "background-image: linear-gradient(rgba(10, 25, 41, {$mask_opacity}), rgba(20, 40, 70, {$mask_opacity})), url('" . esc_url($hero_bg) . "');";
    } else {
        $overlay_css = "background-image: url('" . esc_url($hero_bg) . "');";
    }
    
    // Common background properties
    $bg_props = "background-position: center center; background-size: cover; background-repeat: no-repeat;";
    ?>
    <section class="hero" style="height: 350px; min-height: 0; display: flex; align-items: center; padding: 0; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; <?php echo $overlay_css . ' ' . $bg_props; ?> z-index: 1;"></div>
        <div class="container" style="position: relative; z-index: 2; width: 100%;">
            <div class="hero-content" style="max-width: 800px;">
                <?php if ( is_category() && $category_tagline ) : ?>
                    <span class="eyebrow" style="color: #008080; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; font-size: 14px; display: block; margin-bottom: 10px;"><?php echo esc_html( $category_tagline ); ?></span>
                <?php endif; ?>

                <?php
                // Check for title override
                $display_title = '';
                $quiried_object = get_queried_object();
                if ( $quiried_object instanceof WP_Term ) {
                    $override = vance_get_theme_mod("vance_cat_hero_title_override_{$quiried_object->term_id}");
                    if ( $override ) {
                        $display_title = $override;
                    } else {
                        $display_title = get_the_archive_title();
                    }
                } else {
                    $display_title = get_the_archive_title();
                }
                ?>
                <h1 class="entry-title" style="font-size: 56px; color: <?php echo esc_attr($title_color); ?>; font-weight: 700; margin: 0; line-height: 1.1;"><?php echo wp_kses_post($display_title); ?></h1>
            </div>
        </div>
    </section>

    <?php get_template_part( 'template-parts/inner-category-nav' ); ?>

    <div class="container" style="padding: 60px 20px;">
        <?php if ( have_posts() ) : ?>

            <div class="portal-grid">
                <?php
                while ( have_posts() ) :
                    the_post();
                    ?>
                    <?php
                        $vance_word_count = str_word_count(strip_tags(strip_shortcodes(get_the_content())));
                        $vance_view_count = vance_get_view_count(get_the_ID());
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('news-card'); ?>>
                        <div class="card-image" style="background-image: url('<?php echo get_the_post_thumbnail_url(); ?>'); background-color: #e2e8f0; position: relative;">
                            <div style="position: absolute; top: 10px; left: 12px; color: #ffffff; text-shadow: 0 1px 3px rgba(0,0,0,0.6); font-size: 12px; line-height: 1.3; font-weight: 600; display: flex; flex-direction: column; gap: 6px;">
                                <div><?php echo get_the_date(); ?></div>
                                <div style="font-weight: 500; opacity: 0.95;"><?php echo number_format($vance_word_count); ?> words</div>
                                <div style="font-weight: 500; opacity: 0.95;"><?php echo number_format($vance_view_count); ?> views</div>
                            </div>
                        </div>

                        <div class="card-content">
                            <header class="entry-header">
                                <?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark" style="font-size: 20px;">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
                            </header>

                            <div class="entry-content">
                                <?php the_excerpt(); ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <div class="pagination" style="margin-top: 40px;">
                <?php the_posts_pagination(); ?>
            </div>

        <?php else : ?>
            <p>No content found.</p>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
