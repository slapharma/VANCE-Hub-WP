<?php
/**
 * Content Widget — multi-instance homepage block.
 *
 * Registers 5 pre-configured content-widget slots (content-widget-1 .. -5)
 * that the admin can independently enable, position, and configure via the
 * Section Order control + the "Content Widgets" Customizer panel.
 *
 * Each instance lets the admin choose:
 *  - How many posts to show
 *  - Filter by category and/or tag
 *  - Layout: 'bento' (1 large featured + smaller grid) or 'grid' (uniform)
 *  - Text alignment within each card
 *  - In bento mode: featured-item position (left or right)
 *  - In grid mode: number of rows and number per row
 *  - Toggle: show post date / show author / show image
 *  - Custom colours: section background, card title, subtitle (eyebrow)
 *  - Section heading + subtitle copy
 *
 * Render is registry-driven via inc/customizer-sortable-control.php's
 * vance_homepage_sections filter, so front-page.php's default: dispatch
 * just works — no new case statement needed.
 *
 * @package sla-health-hub
 * @since   2026-05-25
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Number of content-widget slots to pre-register. Keep in sync with the
 * Customizer registration loop in functions.php.
 */
if ( ! defined( 'VANCE_CONTENT_WIDGET_INSTANCES' ) ) {
	define( 'VANCE_CONTENT_WIDGET_INSTANCES', 5 );
}

/**
 * Resolve the instance number from a section ID like "content-widget-3".
 * Returns false if the ID isn't a content-widget instance.
 */
function vance_content_widget_instance_from_id( $section_id ) {
	if ( preg_match( '/^content-widget-(\d+)$/', $section_id, $m ) ) {
		$n = (int) $m[1];
		if ( $n >= 1 && $n <= VANCE_CONTENT_WIDGET_INSTANCES ) {
			return $n;
		}
	}
	return false;
}

/**
 * Render one content-widget instance. Wired up by vance_homepage_sections so
 * each registered instance's render callback boils down to a one-liner that
 * calls this with its own number.
 */
function vance_render_content_widget( $n ) {
	$n = absint( $n );
	if ( $n < 1 || $n > VANCE_CONTENT_WIDGET_INSTANCES ) { return; }

	$prefix = "vance_cw{$n}_";

	// --- Settings ------------------------------------------------------------
	$heading        = vance_get_theme_mod( $prefix . 'heading',        '' );
	$subtitle       = vance_get_theme_mod( $prefix . 'subtitle',       '' );
	$count          = max( 1, absint( vance_get_theme_mod( $prefix . 'count', 6 ) ) );
	$category_id    = (int) vance_get_theme_mod( $prefix . 'category', 0 );
	$tag_slug       = trim( (string) vance_get_theme_mod( $prefix . 'tag', '' ) );
	$layout         = vance_get_theme_mod( $prefix . 'layout', 'grid' );
	$text_align     = vance_get_theme_mod( $prefix . 'text_align', 'left' );
	$featured_pos   = vance_get_theme_mod( $prefix . 'featured_position', 'left' );
	$rows           = max( 1, absint( vance_get_theme_mod( $prefix . 'rows', 1 ) ) );
	$per_row        = max( 1, min( 6, absint( vance_get_theme_mod( $prefix . 'per_row', 3 ) ) ) );
	$show_date      = (bool) vance_get_theme_mod( $prefix . 'show_date',   true );
	$show_author    = (bool) vance_get_theme_mod( $prefix . 'show_author', false );
	$show_image     = (bool) vance_get_theme_mod( $prefix . 'show_image',  true );
	$bg_color       = vance_get_theme_mod( $prefix . 'bg_color',       '#ffffff' );
	$title_color    = vance_get_theme_mod( $prefix . 'title_color',    '#0F172A' );
	$subtitle_color = vance_get_theme_mod( $prefix . 'subtitle_color', '#008080' );

	// New 2026-05-26 — display mode + truncation + full chrome controls.
	// display_mode: full / image_title / title_only.
	// Backwards-compat: if display_mode isn't saved but the older title_only
	// toggle is true, map to title_only.
	$display_mode = vance_get_theme_mod( $prefix . 'display_mode', '' );
	if ( $display_mode === '' ) {
		$display_mode = vance_get_theme_mod( $prefix . 'title_only', false ) ? 'title_only' : 'full';
	}
	if ( ! in_array( $display_mode, array( 'full', 'image_title', 'title_only' ), true ) ) {
		$display_mode = 'full';
	}

	$read_more_text    = trim( (string) vance_get_theme_mod( $prefix . 'read_more_text', 'Read more →' ) );
	$title_truncate    = absint( vance_get_theme_mod( $prefix . 'title_truncate', 0 ) );
	$desc_truncate     = absint( vance_get_theme_mod( $prefix . 'desc_truncate',  0 ) );
	$card_bg           = vance_get_theme_mod( $prefix . 'card_bg_color',       '#ffffff' );
	$card_border       = vance_get_theme_mod( $prefix . 'card_border_color',   '#e2e8f0' );
	$card_border_w     = absint( vance_get_theme_mod( $prefix . 'card_border_width', 1 ) );
	$title_hover       = vance_get_theme_mod( $prefix . 'title_hover_color',   '#008080' );
	$excerpt_color     = vance_get_theme_mod( $prefix . 'excerpt_color',       '#64748b' );
	$meta_color        = vance_get_theme_mod( $prefix . 'meta_color',          '#008080' );
	$rm_text           = vance_get_theme_mod( $prefix . 'rm_text_color',       '#ffffff' );
	$rm_bg             = vance_get_theme_mod( $prefix . 'rm_bg_color',         '#008080' );
	$rm_border         = vance_get_theme_mod( $prefix . 'rm_border_color',     '#008080' );
	$rm_hover_text     = vance_get_theme_mod( $prefix . 'rm_hover_text_color', '#ffffff' );
	$rm_hover_bg       = vance_get_theme_mod( $prefix . 'rm_hover_bg_color',   '#0A1929' );

	if ( ! in_array( $layout, array( 'grid', 'bento' ), true ) )                  { $layout = 'grid'; }
	if ( ! in_array( $text_align, array( 'left', 'center', 'right' ), true ) )    { $text_align = 'left'; }
	if ( ! in_array( $featured_pos, array( 'left', 'right' ), true ) )            { $featured_pos = 'left'; }
	if ( $layout === 'grid' ) {
		$count = max( 1, $rows * $per_row );
	}

	// --- Query ---------------------------------------------------------------
	$cpt = array(
		'post', 'news', 'research', 'oped', 'review',
		'whitepaper', 'podcast', 'webinar', 'course', 'infographic',
	);
	$args = array(
		'numberposts' => $count,
		'post_status' => 'publish',
		'post_type'   => $cpt,
		'orderby'     => 'date',
		'order'       => 'DESC',
	);
	if ( $category_id > 0 ) { $args['category'] = $category_id; }
	if ( $tag_slug !== '' ) { $args['tag']      = $tag_slug; }
	$posts = get_posts( $args );

	if ( empty( $posts ) ) { return; }

	$wrap_id = 'vance-cw-' . $n;

	// Per-mode toggles: title_only forces image+meta+excerpt off; image_title
	// keeps image on but turns meta+excerpt off; full leaves user toggles as-is.
	$render_image   = $show_image;
	$render_meta    = ( $show_date || $show_author );
	$render_excerpt = true;
	if ( $display_mode === 'title_only' ) {
		$render_image   = false;
		$render_meta    = false;
		$render_excerpt = false;
	} elseif ( $display_mode === 'image_title' ) {
		// image stays per user toggle, but force-on if not explicitly off
		$render_meta    = false;
		$render_excerpt = false;
	}

	$opts = array(
		'n'              => $n,
		'wrap_id'        => $wrap_id,
		'text_align'     => $text_align,
		'show_date'      => $show_date && $render_meta,
		'show_author'    => $show_author && $render_meta,
		'render_image'   => $render_image,
		'render_excerpt' => $render_excerpt,
		'display_mode'   => $display_mode,
		'read_more_text' => $read_more_text,
		'title_truncate' => $title_truncate,
		'desc_truncate'  => $desc_truncate,
		'title_color'    => $title_color,
		'title_hover'    => $title_hover,
		'excerpt_color'  => $excerpt_color,
		'meta_color'     => $meta_color,
		'card_bg'        => $card_bg,
		'card_border'    => $card_border,
		'card_border_w'  => $card_border_w,
		'rm_text'        => $rm_text,
		'rm_bg'          => $rm_bg,
		'rm_border'      => $rm_border,
		'rm_hover_text'  => $rm_hover_text,
		'rm_hover_bg'    => $rm_hover_bg,
		'featured_pos'   => $featured_pos,
		'per_row'        => $per_row,
	);

	// --- Render --------------------------------------------------------------
	?>
	<section id="<?php echo esc_attr( $wrap_id ); ?>" class="vance-content-widget vance-cw-<?php echo esc_attr( $layout ); ?>" style="background: <?php echo esc_attr( $bg_color ); ?>; padding: 80px 0;">
		<div class="container">
			<?php if ( $heading || $subtitle ) : ?>
			<div class="vance-cw-header" style="margin-bottom: 40px; text-align: <?php echo esc_attr( $text_align ); ?>;">
				<?php if ( $subtitle ) : ?>
					<div class="vance-cw-subtitle" style="color: <?php echo esc_attr( $subtitle_color ); ?>; font-size: 13px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; margin-bottom: 8px;"><?php echo esc_html( $subtitle ); ?></div>
				<?php endif; ?>
				<?php if ( $heading ) : ?>
					<h2 class="vance-cw-heading" style="color: <?php echo esc_attr( $title_color ); ?>; margin: 0;"><?php echo wp_kses_post( $heading ); ?></h2>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php
			if ( $layout === 'bento' ) {
				vance_cw_render_bento( $posts, $opts );
			} else {
				vance_cw_render_grid( $posts, $opts );
			}
			?>
		</div>
	</section>
	<?php
}

/**
 * Truncate a string at $max characters (UTF-8 safe) with an ellipsis.
 * $max of 0 (or shorter than the input) leaves the string untouched.
 */
function vance_cw_truncate_chars( $text, $max ) {
	$text = (string) $text;
	$max  = (int) $max;
	if ( $max <= 0 ) { return $text; }
	if ( function_exists( 'mb_strlen' ) ) {
		if ( mb_strlen( $text ) <= $max ) { return $text; }
		return rtrim( mb_substr( $text, 0, $max ) ) . '…';
	}
	if ( strlen( $text ) <= $max ) { return $text; }
	return rtrim( substr( $text, 0, $max ) ) . '…';
}

/** Back-compat alias for the older helper name used by other code paths. */
function vance_cw_truncate_title( $title, $max ) {
	return vance_cw_truncate_chars( $title, $max );
}

/**
 * Render the "Read more" pill. Emitted at the bottom of every card so the
 * card-bottom alignment stays consistent across cards with different content
 * lengths (achieved via the flex layout in the card CSS).
 */
function vance_cw_read_more( $post_id, $opts, $for_dark_bg = false ) {
	if ( $opts['read_more_text'] === '' ) { return; }
	$n          = $opts['n'];
	$rm_class   = 'vance-cw-rm-' . $n;
	$text_col   = $for_dark_bg ? '#ffffff' : $opts['rm_text'];
	$bg_col     = $for_dark_bg ? 'rgba(255,255,255,0.18)' : $opts['rm_bg'];
	$border_col = $for_dark_bg ? 'rgba(255,255,255,0.30)' : $opts['rm_border'];
	?>
	<span class="<?php echo esc_attr( $rm_class ); ?>-btn" style="margin-top: auto; padding-top: 12px; align-self: flex-start;">
		<span style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: <?php echo esc_attr( $text_col ); ?>; background: <?php echo esc_attr( $bg_col ); ?>; border: 1px solid <?php echo esc_attr( $border_col ); ?>; transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;">
			<?php echo esc_html( $opts['read_more_text'] ); ?>
		</span>
	</span>
	<?php
}

/**
 * Bento layout: 1 large featured card + the remaining posts in a smaller grid.
 */
function vance_cw_render_bento( $posts, $opts ) {
	if ( empty( $posts ) ) { return; }
	$n            = $opts['n'];
	$featured_pos = $opts['featured_pos'];
	$text_align   = $opts['text_align'];
	$show_date    = $opts['show_date'];
	$show_author  = $opts['show_author'];
	$render_image = $opts['render_image'];
	$render_excerpt = $opts['render_excerpt'];
	$title_color  = $opts['title_color'];
	$title_hover  = $opts['title_hover'];
	$excerpt_col  = $opts['excerpt_color'];
	$meta_col     = $opts['meta_color'];
	$card_bg      = $opts['card_bg'];
	$card_border  = $opts['card_border'];
	$card_bw      = $opts['card_border_w'];
	$trunc_t      = $opts['title_truncate'];
	$trunc_d      = $opts['desc_truncate'];
	$rm_hover_t   = $opts['rm_hover_text'];
	$rm_hover_b   = $opts['rm_hover_bg'];

	$featured = array_shift( $posts );
	$cols = ( $featured_pos === 'right' ) ? '7fr 5fr' : '5fr 7fr';
	?>
	<style>
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-bento-grid {
			display: grid;
			grid-template-columns: <?php echo esc_attr( $cols ); ?>;
			gap: 32px;
		}
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-featured-cell {
			<?php echo $featured_pos === 'right' ? 'order: 2;' : 'order: 1;'; ?>
		}
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-side-cells {
			<?php echo $featured_pos === 'right' ? 'order: 1;' : 'order: 2;'; ?>
			display: flex;
			flex-direction: column;
			gap: 24px;
		}
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-bento-featured {
			display: flex;
			flex-direction: column;
			position: relative;
			min-height: 360px;
			border-radius: 0;
			overflow: hidden;
			text-decoration: none;
			background: #0A1929;
		}
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-bento-featured img {
			position: absolute; inset: 0;
			width: 100%; height: 100%;
			object-fit: cover;
			opacity: 0.7;
		}
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-bento-featured .overlay {
			position: relative; z-index: 2;
			padding: 32px;
			background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.75) 100%);
			color: white;
			min-height: 360px;
			display: flex;
			flex-direction: column;
			justify-content: flex-end;
			text-align: <?php echo esc_attr( $text_align ); ?>;
		}
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-side-card {
			display: flex;
			flex-direction: column;
			background: <?php echo esc_attr( $card_bg ); ?>;
			border: <?php echo (int) $card_bw; ?>px solid <?php echo esc_attr( $card_border ); ?>;
			padding: 18px 22px;
			text-decoration: none;
			text-align: <?php echo esc_attr( $text_align ); ?>;
			transition: all 0.2s ease;
			min-height: 100%;
		}
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-side-card:hover {
			border-color: <?php echo esc_attr( $title_hover ); ?>;
			box-shadow: 0 6px 20px rgba(0,0,0,0.08);
		}
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-side-card:hover h4 { color: <?php echo esc_attr( $title_hover ); ?> !important; }
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-side-card:hover .vance-cw-rm-<?php echo (int) $n; ?>-btn > span { color: <?php echo esc_attr( $rm_hover_t ); ?>; background: <?php echo esc_attr( $rm_hover_b ); ?>; border-color: <?php echo esc_attr( $rm_hover_b ); ?>; }
		@media (max-width: 992px) {
			#vance-cw-<?php echo (int) $n; ?> .vance-cw-bento-grid { grid-template-columns: 1fr; }
			#vance-cw-<?php echo (int) $n; ?> .vance-cw-featured-cell,
			#vance-cw-<?php echo (int) $n; ?> .vance-cw-side-cells { order: unset; }
		}
	</style>
	<div class="vance-cw-bento-grid">
		<div class="vance-cw-featured-cell">
			<a href="<?php echo esc_url( get_permalink( $featured->ID ) ); ?>" class="vance-cw-bento-featured">
				<?php if ( $render_image ) :
					$thumb = get_the_post_thumbnail_url( $featured->ID, 'large' );
					if ( $thumb ) : ?>
						<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title( $featured->ID ) ); ?>">
					<?php endif;
				endif; ?>
				<div class="overlay">
					<?php vance_cw_render_meta_strip( $featured, $show_date, $show_author, '#ffffff' ); ?>
					<h3 style="font-size: 28px; color: white; margin: 8px 0 0 0; line-height: 1.2;"><?php echo esc_html( vance_cw_truncate_chars( get_the_title( $featured->ID ), $trunc_t ) ); ?></h3>
					<?php vance_cw_read_more( $featured->ID, $opts, true ); ?>
				</div>
			</a>
		</div>
		<div class="vance-cw-side-cells">
			<?php foreach ( $posts as $p ) : ?>
				<a href="<?php echo esc_url( get_permalink( $p->ID ) ); ?>" class="vance-cw-side-card">
					<?php vance_cw_render_meta_strip( $p, $show_date, $show_author, $meta_col ); ?>
					<h4 style="font-size: 16px; color: <?php echo esc_attr( $title_color ); ?>; margin: 6px 0 6px 0; line-height: 1.3; transition: color 0.15s ease;"><?php echo esc_html( vance_cw_truncate_chars( get_the_title( $p->ID ), $trunc_t ) ); ?></h4>
					<?php if ( $render_excerpt ) :
						$excerpt = wp_trim_words( get_the_excerpt( $p->ID ), 14 );
						if ( $trunc_d > 0 ) { $excerpt = vance_cw_truncate_chars( $excerpt, $trunc_d ); }
						?>
						<p style="font-size: 13px; color: <?php echo esc_attr( $excerpt_col ); ?>; margin: 0;"><?php echo esc_html( $excerpt ); ?></p>
					<?php endif; ?>
					<?php vance_cw_read_more( $p->ID, $opts ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Uniform grid layout: every card is the same size in an N-per-row grid.
 */
function vance_cw_render_grid( $posts, $opts ) {
	if ( empty( $posts ) ) { return; }
	$n            = $opts['n'];
	$per_row      = $opts['per_row'];
	$text_align   = $opts['text_align'];
	$show_date    = $opts['show_date'];
	$show_author  = $opts['show_author'];
	$render_image = $opts['render_image'];
	$render_excerpt = $opts['render_excerpt'];
	$title_color  = $opts['title_color'];
	$title_hover  = $opts['title_hover'];
	$excerpt_col  = $opts['excerpt_color'];
	$meta_col     = $opts['meta_color'];
	$card_bg      = $opts['card_bg'];
	$card_border  = $opts['card_border'];
	$card_bw      = $opts['card_border_w'];
	$trunc_t      = $opts['title_truncate'];
	$trunc_d      = $opts['desc_truncate'];
	$rm_hover_t   = $opts['rm_hover_text'];
	$rm_hover_b   = $opts['rm_hover_bg'];
	?>
	<style>
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-grid {
			display: grid;
			grid-template-columns: repeat(<?php echo (int) $per_row; ?>, 1fr);
			gap: 24px;
			align-items: stretch;
		}
		/* Each card is a flex column so the Read More can pin to the bottom
		   via margin-top:auto. The body grows; the button stays anchored. */
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-card {
			display: flex;
			flex-direction: column;
			background: <?php echo esc_attr( $card_bg ); ?>;
			border: <?php echo (int) $card_bw; ?>px solid <?php echo esc_attr( $card_border ); ?>;
			text-decoration: none;
			overflow: hidden;
			transition: all 0.2s ease;
			text-align: <?php echo esc_attr( $text_align ); ?>;
			height: 100%;
		}
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-card:hover {
			border-color: <?php echo esc_attr( $title_hover ); ?>;
			box-shadow: 0 8px 24px rgba(0,0,0,0.08);
			transform: translateY(-2px);
		}
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-card:hover h4 { color: <?php echo esc_attr( $title_hover ); ?> !important; }
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-card:hover .vance-cw-rm-<?php echo (int) $n; ?>-btn > span { color: <?php echo esc_attr( $rm_hover_t ); ?>; background: <?php echo esc_attr( $rm_hover_b ); ?>; border-color: <?php echo esc_attr( $rm_hover_b ); ?>; }
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-card-image {
			width: 100%;
			aspect-ratio: 16/9;
			background-size: cover;
			background-position: center center;
			background-color: #0A1929;
			flex-shrink: 0;
		}
		/* Body is the flex child that grows; Read More sits at its bottom. */
		#vance-cw-<?php echo (int) $n; ?> .vance-cw-card-body {
			padding: 20px 22px;
			display: flex;
			flex-direction: column;
			flex: 1 1 auto;
		}
		@media (max-width: 992px) {
			#vance-cw-<?php echo (int) $n; ?> .vance-cw-grid { grid-template-columns: repeat(<?php echo (int) min( 2, $per_row ); ?>, 1fr); }
		}
		@media (max-width: 600px) {
			#vance-cw-<?php echo (int) $n; ?> .vance-cw-grid { grid-template-columns: 1fr; }
		}
	</style>
	<div class="vance-cw-grid">
		<?php foreach ( $posts as $p ) :
			$thumb = $render_image ? get_the_post_thumbnail_url( $p->ID, 'large' ) : '';
			?>
			<a href="<?php echo esc_url( get_permalink( $p->ID ) ); ?>" class="vance-cw-card">
				<?php if ( $render_image ) : ?>
					<div class="vance-cw-card-image" style="<?php echo $thumb ? "background-image: url('" . esc_url( $thumb ) . "');" : ''; ?>"></div>
				<?php endif; ?>
				<div class="vance-cw-card-body">
					<?php vance_cw_render_meta_strip( $p, $show_date, $show_author, $meta_col ); ?>
					<h4 style="font-size: 17px; color: <?php echo esc_attr( $title_color ); ?>; margin: 6px 0 8px 0; line-height: 1.3; transition: color 0.15s ease;"><?php echo esc_html( vance_cw_truncate_chars( get_the_title( $p->ID ), $trunc_t ) ); ?></h4>
					<?php if ( $render_excerpt ) :
						$excerpt = wp_trim_words( get_the_excerpt( $p->ID ), 18 );
						if ( $trunc_d > 0 ) { $excerpt = vance_cw_truncate_chars( $excerpt, $trunc_d ); }
						?>
						<p style="font-size: 13px; color: <?php echo esc_attr( $excerpt_col ); ?>; margin: 0;"><?php echo esc_html( $excerpt ); ?></p>
					<?php endif; ?>
					<?php vance_cw_read_more( $p->ID, $opts ); ?>
				</div>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Tiny shared helper for the date/author/category meta strip above the title.
 * Skipped entirely when both date + author are off.
 */
function vance_cw_render_meta_strip( $post, $show_date, $show_author, $forced_color ) {
	if ( ! $show_date && ! $show_author ) { return; }
	$bits  = array();
	$style = $forced_color
		? 'color: ' . esc_attr( $forced_color ) . '; opacity: 0.85;'
		: 'color: var(--primary-color);';

	$cats = get_the_category( $post->ID );
	$cat  = ( ! empty( $cats ) ) ? $cats[0]->name : '';
	if ( $cat ) { $bits[] = esc_html( $cat ); }
	if ( $show_author ) { $bits[] = esc_html( get_the_author_meta( 'display_name', $post->post_author ) ); }
	if ( $show_date )   { $bits[] = esc_html( get_the_date( '', $post->ID ) ); }

	if ( ! empty( $bits ) ) {
		echo '<div class="vance-cw-meta" style="font-size: 12px; font-weight: 600; letter-spacing: 0.3px; text-transform: uppercase; ' . $style . '">' . implode( ' &bull; ', $bits ) . '</div>';
	}
}

// ============================================================================
// Registry — register N content-widget instances with the homepage section
// control. Each instance has its own 'render' callable that delegates to
// vance_render_content_widget() with the instance number baked in.
// ============================================================================

add_filter( 'vance_homepage_sections', function ( $sections ) {
	for ( $i = 1; $i <= VANCE_CONTENT_WIDGET_INSTANCES; $i++ ) {
		$n = $i; // closure capture
		$sections[ 'content-widget-' . $n ] = array(
			'label'  => 'Content Widget ' . $n . ' (latest posts)',
			'group'  => 'Content Widgets',
			'render' => function () use ( $n ) { vance_render_content_widget( $n ); },
		);
	}
	return $sections;
} );
