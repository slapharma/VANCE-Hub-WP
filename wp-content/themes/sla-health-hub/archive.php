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
                    <span class="eyebrow" style="<?php echo esc_attr( vance_category_tagline_style() ); ?>"><?php echo esc_html( $category_tagline ); ?></span>
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
                        $vance_read_time = vance_get_read_time(get_the_ID());
                        $vance_view_count = vance_get_view_count(get_the_ID());
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('news-card'); ?> data-vhh-post-id="<?php echo (int) get_the_ID(); ?>">
                        <div class="card-image" style="background-image: url('<?php echo get_the_post_thumbnail_url(); ?>'); background-color: #e2e8f0; position: relative;">
                            <?php echo vance_card_eyebrow_html( get_the_ID(), true ); ?>
                        </div>

                        <div class="card-content">
                            <header class="entry-header">
                                <?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark" class="card-stretched-link" style="font-size: 20px;">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
                            </header>

                            <div class="entry-content">
                                <?php the_excerpt(); ?>
                            </div>

                            <?php
                            // Sub-categories: post categories with a parent in the
                            // WP hierarchy (i.e. child terms). The parent/primary
                            // category is already implicit from archive context;
                            // we surface the deeper child plus any post tags.
                            $va_card_sub_cats = array();
                            $va_card_all_cats = get_the_category();
                            if ( ! empty( $va_card_all_cats ) ) {
                                foreach ( $va_card_all_cats as $va_cc ) {
                                    if ( ! empty( $va_cc->parent ) ) {
                                        $va_card_sub_cats[] = $va_cc;
                                    }
                                }
                            }
                            $va_card_tags = get_the_tags();
                            if ( ! is_array( $va_card_tags ) ) { $va_card_tags = array(); }

                            if ( ! empty( $va_card_sub_cats ) || ! empty( $va_card_tags ) ) :
                                // Cap visible chips so cards stay tidy.
                                $va_card_sub_cats = array_slice( $va_card_sub_cats, 0, 2 );
                                $va_card_tags     = array_slice( $va_card_tags, 0, 3 );
                            ?>
                                <div class="va-card-taxonomy" style="margin-top: 12px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                                    <?php foreach ( $va_card_sub_cats as $va_csc ) : ?>
                                        <a href="<?php echo esc_url( get_category_link( $va_csc->term_id ) ); ?>" class="va-card-chip va-card-chip--cat" style="display: inline-block; padding: 3px 9px; background: var(--primary-color, #008080); color: #fff; border-radius: 4px; font-size: 11px; font-weight: 600; text-decoration: none; line-height: 1.4; text-transform: uppercase; letter-spacing: 0.3px;">
                                            <?php echo esc_html( $va_csc->name ); ?>
                                        </a>
                                    <?php endforeach; ?>
                                    <?php foreach ( $va_card_tags as $va_ct ) : ?>
                                        <a href="<?php echo esc_url( get_tag_link( $va_ct->term_id ) ); ?>" class="va-card-chip va-card-chip--tag" style="display: inline-block; padding: 3px 9px; background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 11px; font-weight: 500; text-decoration: none; line-height: 1.4;">
                                            #<?php echo esc_html( $va_ct->name ); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php echo vance_card_meta_footer_html( get_the_ID() ); ?>
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
