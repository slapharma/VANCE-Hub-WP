<?php
/**
 * Grouped sub-category archive body.
 *
 * Used by category-content-clinical-reviews.php and
 * category-content-gastro-living.php. Renders the category hero, then groups
 * the current query's posts by their child (sub-)category. Each group shows an
 * editable description block and is laid out using the per-sub-category layout
 * chosen in Customizer → Content & Knowledge Base → Sub-Category Layouts:
 * Standard Grid, Bento, Asymmetric, or Posters.
 *
 * Posts that belong only to the parent category (no child term) are surfaced
 * only when there are no sub-category groups: a parent with no child categories
 * still renders every post as a single standard grid. When sub-category groups
 * exist, these parent-only posts are not shown (the trailing "More Articles"
 * catch-all was removed).
 *
 * @package sla-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* -------------------------------------------------------------------------
 * Single card renderer (guarded against redeclaration).
 * $layout drives markup: 'posters' uses an image-dominant overlaid card,
 * every other layout reuses the standard news-card structure.
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'vance_render_subcat_card' ) ) {
    function vance_render_subcat_card( $layout = 'grid', $index = 0 ) {
        $vance_read_time  = function_exists( 'vance_get_read_time' ) ? vance_get_read_time( get_the_ID() ) : 1;
        $vance_view_count = function_exists( 'vance_get_view_count' ) ? vance_get_view_count( get_the_ID() ) : 0;
        $thumb            = get_the_post_thumbnail_url( get_the_ID(), 'large' );

        // Item-level modifier class lets CSS vary spans per layout (e.g. the
        // first bento/asymmetric item becomes the feature tile).
        $item_class = 'va-sub-item va-sub-item--' . esc_attr( $layout ) . ' va-sub-item--' . ( ( (int) $index === 0 ) ? 'lead' : 'std' );

        if ( 'posters' === $layout ) : ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( $item_class . ' va-poster-card' ); ?>>
                <a class="va-poster-link" href="<?php the_permalink(); ?>" data-vhh-post-id="<?php echo (int) get_the_ID(); ?>" style="background-image: url('<?php echo esc_url( $thumb ); ?>');">
                    <span class="va-poster-shade" aria-hidden="true"></span>
                    <?php echo vance_card_eyebrow_html( get_the_ID(), true ); ?>
                    <div class="va-poster-body">
                        <div class="va-poster-meta"><?php echo esc_html( get_the_date() ); ?> &middot; <?php echo (int) $vance_read_time; ?> min read</div>
                        <h3 class="va-poster-title"><?php the_title(); ?></h3>
                    </div>
                </a>
            </article>
        <?php else : ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( $item_class . ' news-card' ); ?> data-vhh-post-id="<?php echo (int) get_the_ID(); ?>">
                <div class="card-image" style="background-image: url('<?php echo esc_url( $thumb ); ?>'); background-color: #e2e8f0; position: relative;">
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
        <?php endif;
    }
}

/* -------------------------------------------------------------------------
 * HERO — mirrors archive.php so these category pages stay visually consistent.
 * ---------------------------------------------------------------------- */
$vance_cat            = get_queried_object();
$hero_bg             = get_template_directory_uri() . '/assets/img/research_hero.png';
$category_tagline    = '';

if ( $vance_cat instanceof WP_Term ) {
    $cat_name = $vance_cat->name;
    if ( stripos( $cat_name, 'gastro' ) !== false || stripos( $cat_name, 'living' ) !== false || stripos( $cat_name, 'patient' ) !== false ) {
        $hero_bg          = get_template_directory_uri() . '/assets/img/news_hero.png';
        $category_tagline = 'Living Well, Every Day';
    } else {
        $hero_bg          = get_template_directory_uri() . '/assets/img/research_hero.png';
        $category_tagline = 'Evidence-Based Excellence';
    }

    // Per-category Customizer overrides (shared with archive.php).
    $specific_hero    = vance_get_theme_mod( "vance_cat_hero_{$vance_cat->term_id}" );
    $specific_tagline = vance_get_theme_mod( "vance_cat_tagline_{$vance_cat->term_id}" );
    if ( $specific_hero ) {
        $hero_bg = $specific_hero;
    }
    if ( $specific_tagline ) {
        $category_tagline = $specific_tagline;
    }
}

$title_color  = vance_get_theme_mod( 'vance_hero_title_color', '#ffffff' );
$mask_enabled = vance_get_theme_mod( 'vance_hero_mask_toggle', true );
$mask_opacity = vance_get_theme_mod( 'vance_hero_mask_opacity', 0.5 );

if ( $mask_enabled ) {
    $overlay_css = "background-image: linear-gradient(rgba(10, 25, 41, {$mask_opacity}), rgba(20, 40, 70, {$mask_opacity})), url('" . esc_url( $hero_bg ) . "');";
} else {
    $overlay_css = "background-image: url('" . esc_url( $hero_bg ) . "');";
}
$bg_props = 'background-position: center center; background-size: cover; background-repeat: no-repeat;';

// Title (respect the existing override setting used by archive.php).
$display_title = get_the_archive_title();
if ( $vance_cat instanceof WP_Term ) {
    $override = vance_get_theme_mod( "vance_cat_hero_title_override_{$vance_cat->term_id}" );
    if ( $override ) {
        $display_title = $override;
    }
}
?>
<main>
    <section class="hero" style="height: 350px; min-height: 0; display: flex; align-items: center; padding: 0; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; <?php echo $overlay_css . ' ' . $bg_props; ?> z-index: 1;"></div>
        <div class="container" style="position: relative; z-index: 2; width: 100%;">
            <div class="hero-content" style="max-width: 800px;">
                <?php if ( $category_tagline ) : ?>
                    <span class="eyebrow" style="<?php echo esc_attr( vance_category_tagline_style() ); ?>"><?php echo esc_html( $category_tagline ); ?></span>
                <?php endif; ?>
                <h1 class="entry-title" style="font-size: 56px; color: <?php echo esc_attr( $title_color ); ?>; font-weight: 700; margin: 0; line-height: 1.1;"><?php echo wp_kses_post( $display_title ); ?></h1>
            </div>
        </div>
    </section>

    <?php
    // "Sub-category nav cards" — the glass jump buttons that link to the
    // #va-subcat-<id> section anchors rendered below. These auto-activate for
    // ANY category that has populated child categories, so every grouped
    // archive (Gastro Living, Clinical Reviews, and any category routed here by
    // archive.php) gets them without a per-page flag. An explicit
    // inner_nav_subcats => true arg forces the same behaviour. Categories with
    // no populated sub-categories fall through to the standard global inner nav.
    $vance_nav_parent    = get_queried_object();
    $vance_nav_parent_id = ( $vance_nav_parent instanceof WP_Term ) ? (int) $vance_nav_parent->term_id : 0;
    $vance_show_subnav   = ! empty( $args['inner_nav_subcats'] );
    if ( ! $vance_show_subnav && $vance_nav_parent_id > 0 ) {
        $vance_nav_children = get_categories( array(
            'parent'     => $vance_nav_parent_id,
            'hide_empty' => true,
            'number'     => 1,
            'fields'     => 'ids',
        ) );
        $vance_show_subnav = ! empty( $vance_nav_children );
    }
    if ( $vance_show_subnav ) {
        get_template_part( 'template-parts/inner-category-nav', null, array( 'subcategories_of' => $vance_nav_parent_id ) );
    } else {
        get_template_part( 'template-parts/inner-category-nav' );
    }
    ?>

    <div class="container" style="padding: 60px 20px;">
        <?php if ( have_posts() ) : ?>
            <?php
            /* -----------------------------------------------------------------
             * Build the child-term map for this parent, then bucket every post
             * in the current query into its sub-category (or the ungrouped
             * fallback). Buckets preserve query order within each group.
             * --------------------------------------------------------------- */
            $vance_child_terms = array();
            if ( $vance_cat instanceof WP_Term ) {
                $vance_child_terms = get_categories( array(
                    'parent'     => $vance_cat->term_id,
                    'hide_empty' => false,
                ) );
            }
            // Order groups by the per-sub-category "Order" Customizer control
            // (lower first); ties fall back to alphabetical by name.
            usort( $vance_child_terms, function ( $a, $b ) {
                $oa = vance_get_subcat_order( $a->term_id );
                $ob = vance_get_subcat_order( $b->term_id );
                if ( $oa === $ob ) {
                    return strcasecmp( $a->name, $b->name );
                }
                return ( $oa < $ob ) ? -1 : 1;
            } );
            $vance_child_ids = array();
            foreach ( $vance_child_terms as $vct ) {
                $vance_child_ids[ $vct->term_id ] = $vct;
            }

            $vance_buckets   = array();   // term_id => array of post IDs
            $vance_ungrouped = array();   // post IDs with no child term
            foreach ( $vance_child_ids as $tid => $term_obj ) {
                $vance_buckets[ $tid ] = array();
            }

            while ( have_posts() ) :
                the_post();
                $vance_assigned = false;
                foreach ( (array) get_the_category() as $vpc ) {
                    if ( isset( $vance_child_ids[ $vpc->term_id ] ) ) {
                        $vance_buckets[ $vpc->term_id ][] = get_the_ID();
                        $vance_assigned = true;
                        break;
                    }
                }
                if ( ! $vance_assigned ) {
                    $vance_ungrouped[] = get_the_ID();
                }
            endwhile;

            /* -----------------------------------------------------------------
             * Render each sub-category group: description block + chosen layout.
             * --------------------------------------------------------------- */
            foreach ( $vance_child_ids as $tid => $term_obj ) :
                $vance_post_ids = $vance_buckets[ $tid ];
                if ( empty( $vance_post_ids ) ) {
                    continue; // Skip empty sub-categories.
                }
                $vance_layout = vance_get_subcat_layout( $tid );
                $vance_desc   = vance_get_subcat_description( $term_obj );
                ?>
                <section class="va-subcat-group va-subcat-group--<?php echo esc_attr( $vance_layout ); ?>" aria-labelledby="va-subcat-<?php echo (int) $tid; ?>">
                    <header class="va-subcat-head">
                        <h2 class="va-subcat-title" id="va-subcat-<?php echo (int) $tid; ?>"><?php echo esc_html( $term_obj->name ); ?></h2>
                        <?php if ( '' !== trim( (string) $vance_desc ) ) : ?>
                            <div class="va-subcat-desc"><?php echo wp_kses_post( wpautop( $vance_desc ) ); ?></div>
                        <?php endif; ?>
                        <a class="va-subcat-viewall" href="<?php echo esc_url( get_category_link( $tid ) ); ?>">View all <?php echo esc_html( $term_obj->name ); ?> &rarr;</a>
                    </header>

                    <?php if ( 'bento' === $vance_layout ) :
                        // Bento = one large main feature + 2 or 4 small cards beside
                        // it (main on left or right), with any extra posts flowing
                        // into a standard grid below.
                        $vance_bcount = (int) vance_get_subcat_bento_count( $tid );
                        $vance_bside  = vance_get_subcat_bento_side( $tid );
                        $vance_ids    = $vance_post_ids;
                        $vance_main   = array_shift( $vance_ids );
                        $vance_side   = array_splice( $vance_ids, 0, $vance_bcount );
                        $vance_rest   = $vance_ids;
                        $vance_solo   = empty( $vance_side );
                        ?>
                        <div class="va-bento va-bento--count-<?php echo (int) $vance_bcount; ?> va-bento--<?php echo esc_attr( $vance_bside ); ?><?php echo $vance_solo ? ' va-bento--solo' : ''; ?>">
                            <div class="va-bento-main">
                                <?php $post = get_post( $vance_main ); setup_postdata( $post ); vance_render_subcat_card( 'bento', 0 ); wp_reset_postdata(); ?>
                            </div>
                            <?php if ( ! $vance_solo ) : ?>
                                <div class="va-bento-side">
                                    <?php $vance_bi = 1; foreach ( $vance_side as $vpid ) { $post = get_post( $vpid ); setup_postdata( $post ); vance_render_subcat_card( 'bento', $vance_bi ); $vance_bi++; } wp_reset_postdata(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ( ! empty( $vance_rest ) ) : ?>
                            <div class="va-sub-grid va-layout-grid va-bento-overflow">
                                <?php $vance_ri = 0; foreach ( $vance_rest as $vpid ) { $post = get_post( $vpid ); setup_postdata( $post ); vance_render_subcat_card( 'grid', $vance_ri ); $vance_ri++; } wp_reset_postdata(); ?>
                            </div>
                        <?php endif; ?>
                    <?php else : ?>
                        <div class="va-sub-grid va-layout-<?php echo esc_attr( $vance_layout ); ?>">
                            <?php
                            $vance_i = 0;
                            foreach ( $vance_post_ids as $vpid ) {
                                $post = get_post( $vpid ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
                                setup_postdata( $post );
                                vance_render_subcat_card( $vance_layout, $vance_i );
                                $vance_i++;
                            }
                            wp_reset_postdata();
                            ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>

            <?php
            /* -----------------------------------------------------------------
             * Ungrouped fallback — posts only in the parent category.
             * --------------------------------------------------------------- */
            if ( ! empty( $vance_ungrouped ) ) :
                $vance_has_groups = false;
                foreach ( $vance_buckets as $b ) {
                    if ( ! empty( $b ) ) { $vance_has_groups = true; break; }
                }
                // Only surface parent-only posts when there are no sub-category
                // groups, so a category without sub-categories still renders its
                // posts as a single grid. When groups exist, these leftovers are
                // intentionally dropped (the trailing "More Articles" section was
                // removed).
                if ( ! $vance_has_groups ) :
                    ?>
                    <section class="va-subcat-group va-subcat-group--grid">
                        <div class="va-sub-grid va-layout-grid">
                            <?php
                            $vance_i = 0;
                            foreach ( $vance_ungrouped as $vpid ) {
                                $post = get_post( $vpid ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
                                setup_postdata( $post );
                                vance_render_subcat_card( 'grid', $vance_i );
                                $vance_i++;
                            }
                            wp_reset_postdata();
                            ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>

            <div class="pagination" style="margin-top: 40px;">
                <?php the_posts_pagination(); ?>
            </div>

        <?php else : ?>
            <p>No content found.</p>
        <?php endif; ?>
    </div>
</main>
