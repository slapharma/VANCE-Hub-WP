<?php
/**
 * Category template: Healthcare News (slug: content-healthcare-news).
 *
 * Unlike Clinical Reviews / Gastro Living (grouped by real child categories via
 * template-parts/subcategory-grouped-archive.php), Healthcare News has no child
 * terms — it's grouped by publish recency instead: This Month, Last Month, and
 * This Year, each in its own section with a "View all" link. Anything older
 * than the current year falls into a trailing "Earlier" bucket so nothing is
 * ever silently dropped.
 *
 * Card markup mirrors archive.php's flat `.portal-grid` cards and the section
 * chrome (.va-subcat-group / .va-sub-grid) mirrors the taxonomy-grouped
 * template, so this page looks consistent with both without depending on
 * either — the buckets here are computed, not real WP terms, so they can't
 * use the per-sub-category "Grid columns" control in Sub-category Layouts.
 * Grid width instead comes from a category-scoped equivalent, "Grid Columns"
 * under Customizer → Category Heroes (vance_get_cat_grid_cols(), functions.php).
 *
 * @package sla-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$vhn_cat       = get_queried_object();
$vhn_grid_cols = ( $vhn_cat instanceof WP_Term ) ? vance_get_cat_grid_cols( $vhn_cat->term_id ) : '3';

/* -----------------------------------------------------------------------
 * Hero — same visual treatment archive.php gives this category by name.
 * -------------------------------------------------------------------- */
$vhn_hero_bg  = get_template_directory_uri() . '/assets/img/news_hero.png';
$vhn_tagline  = 'Stay Ahead of the Curve';
if ( $vhn_cat instanceof WP_Term ) {
    $vhn_specific_hero    = vance_get_theme_mod( "vance_cat_hero_{$vhn_cat->term_id}" );
    $vhn_specific_tagline = vance_get_theme_mod( "vance_cat_tagline_{$vhn_cat->term_id}" );
    if ( $vhn_specific_hero ) {
        $vhn_hero_bg = $vhn_specific_hero;
    }
    if ( $vhn_specific_tagline ) {
        $vhn_tagline = $vhn_specific_tagline;
    }
}

$vhn_title_color  = vance_get_theme_mod( 'vance_hero_title_color', '#ffffff' );
$vhn_mask_enabled = vance_get_theme_mod( 'vance_hero_mask_toggle', true );
$vhn_mask_opacity = vance_get_theme_mod( 'vance_hero_mask_opacity', 0.5 );

if ( $vhn_mask_enabled ) {
    $vhn_overlay_css = "background-image: linear-gradient(rgba(10, 25, 41, {$vhn_mask_opacity}), rgba(20, 40, 70, {$vhn_mask_opacity})), url('" . esc_url( $vhn_hero_bg ) . "');";
} else {
    $vhn_overlay_css = "background-image: url('" . esc_url( $vhn_hero_bg ) . "');";
}
$vhn_bg_props = 'background-position: center center; background-size: cover; background-repeat: no-repeat;';

$vhn_display_title = get_the_archive_title();
if ( $vhn_cat instanceof WP_Term ) {
    $vhn_override = vance_get_theme_mod( "vance_cat_hero_title_override_{$vhn_cat->term_id}" );
    if ( $vhn_override ) {
        $vhn_display_title = $vhn_override;
    }
}
?>
<main>
    <section class="hero" style="height: 350px; min-height: 0; display: flex; align-items: center; padding: 0; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; <?php echo $vhn_overlay_css . ' ' . $vhn_bg_props; ?> z-index: 1;"></div>
        <div class="container" style="position: relative; z-index: 2; width: 100%;">
            <div class="hero-content" style="max-width: 800px;">
                <?php if ( $vhn_tagline ) : ?>
                    <span class="eyebrow" style="<?php echo esc_attr( vance_category_tagline_style() ); ?>"><?php echo esc_html( $vhn_tagline ); ?></span>
                <?php endif; ?>
                <h1 class="entry-title" style="font-size: 56px; color: <?php echo esc_attr( $vhn_title_color ); ?>; font-weight: 700; margin: 0; line-height: 1.1;"><?php echo wp_kses_post( $vhn_display_title ); ?></h1>
            </div>
        </div>
    </section>

    <?php get_template_part( 'template-parts/inner-category-nav' ); ?>

    <?php
    if ( function_exists( 'vance_render_category_promo' ) ) {
        vance_render_category_promo( get_queried_object_id() );
    }
    ?>

    <?php
    /* -------------------------------------------------------------------
     * Single card renderer — same structure as vance_render_subcat_card()
     * in template-parts/subcategory-grouped-archive.php ('grid' branch),
     * kept local rather than shared since that file's other layout modes
     * (bento/posters/featured_list) don't apply to computed date buckets.
     * ------------------------------------------------------------------ */
    if ( ! function_exists( 'vance_render_healthcare_news_card' ) ) {
        function vance_render_healthcare_news_card() {
            $vhn_thumb = get_the_post_thumbnail_url( get_the_ID(), 'large' );
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'va-sub-item news-card' ); ?> data-vhh-post-id="<?php echo (int) get_the_ID(); ?>">
                <div class="card-image" style="background-image: url('<?php echo esc_url( $vhn_thumb ); ?>'); background-color: #e2e8f0; position: relative;">
                    <?php echo vance_card_eyebrow_html( get_the_ID(), true ); ?>
                </div>
                <div class="card-content">
                    <header class="entry-header">
                        <?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark" class="card-stretched-link" style="font-size: 20px;">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
                    </header>
                    <div class="entry-content"><?php the_excerpt(); ?></div>
                    <?php echo vance_card_meta_footer_html( get_the_ID() ); ?>
                </div>
            </article>
            <?php
        }
    }
    ?>

    <div class="container" style="padding: 60px 20px;">
        <?php
        $vhn_query = new WP_Query( array(
            'post_type'      => vance_discovery_post_types(),
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'tax_query'      => array( array(
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => get_queried_object_id(),
            ) ),
        ) );

        if ( $vhn_query->have_posts() ) :
            // Boundaries computed in the site's configured timezone (not server/GMT),
            // matched against each post's own site-timezone datetime below, so "This
            // Month" means the same thing here as it does in wp-admin's post list.
            $vhn_tz                = current_datetime()->getTimezone();
            $vhn_this_month_start  = ( new DateTimeImmutable( 'first day of this month 00:00:00', $vhn_tz ) )->getTimestamp();
            $vhn_last_month_start  = ( new DateTimeImmutable( 'first day of last month 00:00:00', $vhn_tz ) )->getTimestamp();
            $vhn_year_start        = ( new DateTimeImmutable( current_datetime()->format( 'Y' ) . '-01-01 00:00:00', $vhn_tz ) )->getTimestamp();

            $vhn_buckets = array(
                'this_month' => array( 'label' => 'This Month', 'ids' => array() ),
                'last_month' => array( 'label' => 'Last Month', 'ids' => array() ),
                'this_year'  => array( 'label' => 'Earlier This Year', 'ids' => array() ),
                'earlier'    => array( 'label' => 'Earlier', 'ids' => array() ),
            );

            while ( $vhn_query->have_posts() ) : $vhn_query->the_post();
                $vhn_ts = get_post_datetime( get_the_ID() )->getTimestamp();
                if ( $vhn_ts >= $vhn_this_month_start ) {
                    $vhn_buckets['this_month']['ids'][] = get_the_ID();
                } elseif ( $vhn_ts >= $vhn_last_month_start ) {
                    $vhn_buckets['last_month']['ids'][] = get_the_ID();
                } elseif ( $vhn_ts >= $vhn_year_start ) {
                    $vhn_buckets['this_year']['ids'][] = get_the_ID();
                } else {
                    $vhn_buckets['earlier']['ids'][] = get_the_ID();
                }
            endwhile;
            wp_reset_postdata();

            $vhn_view_all_base   = get_category_link( get_queried_object_id() );
            $vhn_last_month_date = ( new DateTimeImmutable( '@' . $vhn_last_month_start ) )->setTimezone( $vhn_tz );
            $vhn_view_all_urls   = array(
                'this_month' => add_query_arg( 'm', current_datetime()->format( 'Ym' ), $vhn_view_all_base ),
                'last_month' => add_query_arg( 'm', $vhn_last_month_date->format( 'Ym' ), $vhn_view_all_base ),
                'this_year'  => add_query_arg( 'm', current_datetime()->format( 'Y' ), $vhn_view_all_base ),
                'earlier'    => $vhn_view_all_base,
            );

            foreach ( $vhn_buckets as $vhn_key => $vhn_bucket ) :
                if ( empty( $vhn_bucket['ids'] ) ) {
                    continue;
                }
                ?>
                <section class="va-subcat-group va-subcat-group--grid" aria-labelledby="va-subcat-<?php echo esc_attr( $vhn_key ); ?>">
                    <header class="va-subcat-head">
                        <h2 class="va-subcat-title" id="va-subcat-<?php echo esc_attr( $vhn_key ); ?>"><?php echo esc_html( $vhn_bucket['label'] ); ?></h2>
                        <a class="va-subcat-viewall" href="<?php echo esc_url( $vhn_view_all_urls[ $vhn_key ] ); ?>">View all <?php echo esc_html( $vhn_bucket['label'] ); ?> &rarr;</a>
                    </header>
                    <div class="va-sub-grid va-layout-grid va-grid--cols-<?php echo esc_attr( $vhn_grid_cols ); ?>">
                        <?php
                        foreach ( $vhn_bucket['ids'] as $vhn_pid ) {
                            $post = get_post( $vhn_pid ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
                            setup_postdata( $post );
                            vance_render_healthcare_news_card();
                        }
                        wp_reset_postdata();
                        ?>
                    </div>
                </section>
            <?php endforeach; ?>

        <?php else : ?>
            <p>No content found.</p>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
