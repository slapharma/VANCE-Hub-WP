<?php
/**
 * Prime Block — the shared "Featured Tools + Latest Content" engine.
 *
 * Extracted verbatim (in behaviour) from front-page.php's old
 * `case 'pathway_content':` so the same block can be rendered more than once
 * per request. The original code hard-coded every CSS selector on the bare
 * `.pathway-content-section` class, which meant two instances on one page
 * would fight over colours and layout; here every rule is scoped to the
 * instance's own wrapper id.
 *
 * Three instances ship:
 *   - Prime Block Home 1     — reads the ORIGINAL vance_pwc_* / vance_hquiz_* /
 *                              vance_askai_* keys, so the live site's existing
 *                              configuration carries over untouched.
 *   - Prime Block Home 2     — reads a fresh vance_pb2_* prefix.
 *   - Prime Block Categories — reads vance_pbc_*, rendered on every category
 *                              archive (not registry-driven; called directly
 *                              from the archive templates, matching the
 *                              vance_render_category_promo() precedent).
 *
 * @package vance-health-hub
 * @since   2026-08-21
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * The Content Hub post types the "Latest Content" column pulls from.
 */
function vance_prime_block_post_types() {
	return array(
		'post', 'news', 'research', 'oped', 'review',
		'whitepaper', 'podcast', 'webinar', 'course', 'infographic',
	);
}

/**
 * The layout choices, shared by all three instances' Customizer selects.
 */
function vance_prime_block_layout_choices() {
	return array(
		'left'           => __( 'Left of Latest Content',    'vance-health-hub' ),
		'right'          => __( 'Right of Latest Content',   'vance-health-hub' ),
		'stacked'        => __( 'Stacked (Tools on top)',    'vance-health-hub' ),
		'stacked_bottom' => __( 'Stacked (Tools on bottom)', 'vance-health-hub' ),
	);
}

/**
 * The tool-card style choices, shared by all three instances.
 */
function vance_prime_block_style_choices() {
	return array(
		'card'       => __( 'Card (paired tool tiles)',           'vance-health-hub' ),
		'card_thumb' => __( 'Card + Right Thumbnail',             'vance-health-hub' ),
		'image_text' => __( 'Image + Text (horizontal banner)',   'vance-health-hub' ),
		'image'      => __( 'Image-led banner',                   'vance-health-hub' ),
		'pill'       => __( 'Minimal pill banner',                'vance-health-hub' ),
	);
}

/**
 * Where the Categories instance sits on a category archive.
 *
 * The archive templates call vance_render_prime_block_categories() once per
 * slot; the block renders in whichever slot matches this setting and bails in
 * the other two. 'below_promo' is the historical position, so it stays the
 * default and nothing moves for an existing install.
 */
function vance_prime_block_placement_choices() {
	return array(
		'above_promo'  => __( 'Above the category promo block', 'vance-health-hub' ),
		'below_promo'  => __( 'Below the category promo block', 'vance-health-hub' ),
		'above_footer' => __( 'Above the footer (end of the page)', 'vance-health-hub' ),
	);
}

/**
 * Render one Prime Block instance from a fully-resolved values array.
 *
 * Deliberately does NOT read theme mods itself — the wrappers below own that,
 * which is what lets the same markup serve three differently-keyed instances.
 *
 * @param array $vals {
 *     @type string $wrap_id             DOM id + CSS scope for this instance.
 *     @type string $label               Tools column section label.
 *     @type string $layout              left|right|stacked|stacked_bottom.
 *     @type string $style               card|image_text|image|pill.
 *     @type string $section_bg          Section background colour.
 *     @type string $section_label_color Colour of the tools column label.
 *     @type string $title_color         Card title colour.
 *     @type string $title_hover_color   Card title colour on hover.
 *     @type string $desc_color          Card description colour.
 *     @type string $eyebrow_color       Card eyebrow colour (also banner accent).
 *     @type string $tools_column_bg     Optional bg for the tools column ('' = none).
 *     @type string $hover_color         Card hover background colour.
 *     @type string $icon_bg             Image-placeholder background colour.
 *     @type array  $cards               Card arrays: title/desc/eyebrow/image/link/fallback_icon.
 *     @type string $latest_title        Latest-content column label.
 *     @type int    $latest_count        How many posts to fetch.
 *     @type int    $latest_cat          Category id filter (0 = all).
 *     @type bool   $latest_show_date    Show the relative date in the fallback list.
 *     @type bool   $latest_show_thumbs  Show the postage-stamp thumbnail on each
 *                                       article row (the featured cell always
 *                                       keeps its image).
 *     @type bool   $accent_bar_show     Show the small accent bar beside each
 *                                       column heading.
 *     @type string $accent_bar_color    Colour of that accent bar.
 *     @type bool   $tighten_next_cw     Emit the legacy #vance-cw-1 top-padding trim.
 * }
 */
function vance_render_prime_block( array $vals ) {
	$wrap_id = isset( $vals['wrap_id'] ) ? sanitize_html_class( $vals['wrap_id'] ) : 'vance-prime-block';

	$layout = isset( $vals['layout'] ) ? $vals['layout'] : 'left';
	if ( ! array_key_exists( $layout, vance_prime_block_layout_choices() ) ) { $layout = 'left'; }

	$style = isset( $vals['style'] ) ? $vals['style'] : 'card';
	if ( ! array_key_exists( $style, vance_prime_block_style_choices() ) ) { $style = 'card'; }

	$label               = (string) ( isset( $vals['label'] ) ? $vals['label'] : 'Featured Tools' );
	$section_bg          = (string) ( isset( $vals['section_bg'] ) ? $vals['section_bg'] : '#ffffff' );
	$section_label_color = (string) ( isset( $vals['section_label_color'] ) ? $vals['section_label_color'] : '#0f172a' );
	$title_color         = (string) ( isset( $vals['title_color'] ) ? $vals['title_color'] : '#0A1929' );
	$title_hover_color   = (string) ( isset( $vals['title_hover_color'] ) ? $vals['title_hover_color'] : '#ffffff' );
	$desc_color          = (string) ( isset( $vals['desc_color'] ) ? $vals['desc_color'] : '#64748b' );
	$eyebrow_color       = (string) ( isset( $vals['eyebrow_color'] ) ? $vals['eyebrow_color'] : '#008080' );
	$tools_column_bg     = (string) ( isset( $vals['tools_column_bg'] ) ? $vals['tools_column_bg'] : '' );
	$hover_color         = (string) ( isset( $vals['hover_color'] ) ? $vals['hover_color'] : '#008080' );
	$icon_bg             = (string) ( isset( $vals['icon_bg'] ) ? $vals['icon_bg'] : '#0A1929' );
	$cards               = ( isset( $vals['cards'] ) && is_array( $vals['cards'] ) ) ? $vals['cards'] : array();
	$latest_title        = (string) ( isset( $vals['latest_title'] ) ? $vals['latest_title'] : 'LATEST CONTENT' );
	$latest_count        = max( 1, absint( isset( $vals['latest_count'] ) ? $vals['latest_count'] : 6 ) );
	$latest_cat          = absint( isset( $vals['latest_cat'] ) ? $vals['latest_cat'] : 0 );
	$latest_show_date    = ! empty( $vals['latest_show_date'] );
	$tighten_next_cw     = ! empty( $vals['tighten_next_cw'] );

	// Both of these default to ON when the key is absent, so a values array
	// written before they existed keeps rendering exactly as it did.
	$latest_show_thumbs  = ! isset( $vals['latest_show_thumbs'] ) || ! empty( $vals['latest_show_thumbs'] );
	$accent_bar_show     = ! isset( $vals['accent_bar_show'] )    || ! empty( $vals['accent_bar_show'] );
	$accent_bar_color    = (string) ( isset( $vals['accent_bar_color'] ) ? $vals['accent_bar_color'] : '' );
	if ( '' === $accent_bar_color ) { $accent_bar_color = '#008080'; }

	// Tools column inline style. When a background colour is set, add padding
	// so the colour reads as a block rather than a sliver behind the cards.
	$tools_stack_style = 'display: flex; flex-direction: column; gap: 24px; height: 100%;';
	if ( $tools_column_bg !== '' ) {
		$tools_stack_style .= ' background: ' . esc_attr( $tools_column_bg ) . '; padding: 24px;';
	}

	// --- Latest content query ------------------------------------------------
	$args = array(
		'numberposts' => $latest_count,
		'post_status' => 'publish',
		'post_type'   => vance_prime_block_post_types(),
		'orderby'     => 'date',
		'order'       => 'DESC',
	);
	if ( $latest_cat > 0 ) { $args['category'] = $latest_cat; }
	$latest_posts = get_posts( $args );

	// Every selector below is prefixed with this so two instances on one page
	// can carry completely different colours and layouts.
	$sel = '#' . $wrap_id;

	// Section classes: 'stacked_bottom' becomes 'layout-stacked-bottom'.
	$layout_class = 'layout-' . str_replace( '_', '-', $layout );
	?>
	<!-- Prime Block (Featured Tools + Latest Content) -->
	<style>
		<?php echo $sel; ?>.pathway-content-section {
			/* Bottom padding was 0, so in the stacked-bottom layout the Featured
			   Tools row ran straight into whatever section follows. 50px gives
			   it room to breathe without reopening the gap the #vance-cw-1 trim
			   below was added to close. */
			padding: 80px 0 50px;
			background: <?php echo esc_attr( $section_bg ); ?>;
		}
		<?php if ( $tighten_next_cw ) : ?>
		/* The next section down (Quick Reads / Most Popular Articles) is a
		   generic content-widget instance whose own 80px top padding is shared
		   by every content-widget placement sitewide — editing that file would
		   shrink the gap everywhere it's used, not just here. Scoping the
		   reduction to that specific widget's own id keeps it local. */
		#vance-cw-1 { padding-top: 45px !important; }
		<?php endif; ?>
		/* Local copy of the split-grid rules — the originals lived in the
		   retired 'pathway' case, so this block carries its own. */
		<?php echo $sel; ?> .pathway-split-grid {
			display: grid;
			/* Tools column pinned to ~the height of one stacked tool card so the
			   two cards render roughly SQUARE; content takes the rest. */
			grid-template-columns: 220px minmax(0, 1fr);
			gap: 40px;
			align-items: stretch;
		}
		/* Layout: tools on the RIGHT — swap visual order via grid-column
		   overrides so the DOM stays in the same order (a11y + SEO friendly). */
		<?php echo $sel; ?>.layout-right .pathway-split-grid {
			grid-template-columns: minmax(0, 1fr) 220px;
		}
		<?php echo $sel; ?>.layout-right .pathway-tiles-stack   { grid-column: 2; }
		<?php echo $sel; ?>.layout-right .latest-content-column { grid-column: 1; grid-row: 1; }
		/* Layout: STACKED — tools row across the full width, content list in
		   the other row. Shared by both stacked variants. */
		<?php echo $sel; ?>.layout-stacked .pathway-split-grid,
		<?php echo $sel; ?>.layout-stacked-bottom .pathway-split-grid {
			grid-template-columns: 1fr;
			gap: 48px;
		}
		<?php echo $sel; ?>.layout-stacked .pathway-tiles-stack,
		<?php echo $sel; ?>.layout-stacked-bottom .pathway-tiles-stack {
			flex-direction: row !important;
			flex-wrap: wrap;
			height: auto !important;
			align-items: stretch;
		}
		<?php echo $sel; ?>.layout-stacked .pathway-tiles-stack > .section-label,
		<?php echo $sel; ?>.layout-stacked-bottom .pathway-tiles-stack > .section-label {
			flex-basis: 100%;
		}
		<?php echo $sel; ?>.layout-stacked .pwc-card,
		<?php echo $sel; ?>.layout-stacked-bottom .pwc-card { flex: 1 1 0 !important; min-width: 240px; }
		/* Layout: STACKED, TOOLS ON BOTTOM — identical single-column grid, but
		   the two children swap visual position via `order`. DOM order is
		   untouched, matching the left/right swap technique above. */
		<?php echo $sel; ?>.layout-stacked-bottom .pathway-tiles-stack   { order: 2; }
		<?php echo $sel; ?>.layout-stacked-bottom .latest-content-column { order: 1; }
		<?php echo $sel; ?> .pathway-tiles-stack {
			display: flex;
			flex-direction: column;
			gap: 24px;
			height: 100%;
		}
		/* The label is a flex item inside .pathway-tiles-stack, so the
		   container's gap:24px already provides spacing to the next item. Zero
		   out the label's own margin-bottom so the first tool card starts at
		   the same Y as the featured news card on the right (no flex gap). */
		<?php echo $sel; ?> .pathway-tiles-stack > .section-label { margin-bottom: 0 !important; }
		/* The tools column stretches to fill the row (height:100% above), but
		   .latest-content-column was a plain block — its label + bento grid only
		   took their own content height, leaving the article list shorter than
		   the tools column. Making this a flex column too, with the grid as the
		   flexible item, lets it stretch to match. */
		<?php echo $sel; ?> .latest-content-column {
			display: flex;
			flex-direction: column;
			height: 100%;
		}
		<?php echo $sel; ?> .latest-content-column .bento-grid-news.bento-grid-news--grow {
			flex: 1;
			min-height: 0;
		}
		@media (max-width: 992px) {
			<?php echo $sel; ?> .pathway-split-grid { grid-template-columns: 1fr; }
			<?php echo $sel; ?>.layout-right .pathway-split-grid { grid-template-columns: 1fr; }
			/* On mobile every layout collapses to a single column, tools on top. */
			<?php echo $sel; ?>.layout-right .pathway-tiles-stack   { grid-column: 1; grid-row: 1; }
			<?php echo $sel; ?>.layout-right .latest-content-column { grid-column: 1; grid-row: 2; }
			/* `flex-wrap` has to be reset here as well as the direction. The
			   stacked rule above pairs `row` with `wrap` so the tool cards can
			   flow onto a second line; flipping only the direction leaves a
			   WRAPPING COLUMN, and this container is a stretched grid item, so
			   its height is definite. Anything that does not fit vertically
			   therefore wrapped into a second flex column ~300px to the RIGHT
			   of the viewport, dragging the document to 674px wide on a 390px
			   phone -- 284px of horizontal scroll on the homepage, and enough
			   to make Chrome widen the mobile layout viewport, which is what
			   made the Ask AI intro modal render oversized and clipped. */
			<?php echo $sel; ?>.layout-stacked .pathway-tiles-stack,
			<?php echo $sel; ?>.layout-stacked-bottom .pathway-tiles-stack { flex-direction: column !important; flex-wrap: nowrap; }
			<?php echo $sel; ?>.layout-stacked-bottom .pathway-tiles-stack   { order: 2; }
			<?php echo $sel; ?>.layout-stacked-bottom .latest-content-column { order: 1; }
			<?php echo $sel; ?> .bento-grid-news { grid-template-columns: 1fr; grid-template-rows: auto; }
		}
		<?php echo $sel; ?> .pwc-card {
			text-decoration: none;
			display: flex;
			flex-direction: column;
			background: white;
			padding: 0;
			box-shadow: 0 4px 20px rgba(0,0,0,0.05);
			border: 1.5px solid #e2e8f0;
			border-radius: var(--radius-surface, 14px);
			transition: all 0.3s ease;
			overflow: hidden;
			/* flex:1 inside the height:100% stack splits available column height
			   between the two cards so they both end at the same Y as the
			   featured news card on the right. */
			flex: 1;
			min-height: 0;
		}
		<?php echo $sel; ?> .pwc-card:hover {
			background: <?php echo esc_attr( $hover_color ); ?>;
			border-color: <?php echo esc_attr( $hover_color ); ?>;
			transform: translateY(-4px);
			box-shadow: 0 20px 45px rgba(0,0,0,0.12);
		}
		<?php echo $sel; ?> .pwc-card-image {
			width: 100%;
			/* A tight 70px strip, so each card is ~150px and the two fit cleanly
			   inside the featured news card's vertical envelope. */
			height: 70px;
			background-position: center center;
			background-size: cover;
			background-repeat: no-repeat;
			background-color: <?php echo esc_attr( $icon_bg ); ?>;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
		}
		<?php echo $sel; ?> .pwc-card-image .pwc-fallback-icon {
			font-size: 28px;
			color: #ffffff;
			opacity: 0.85;
			font-weight: 800;
			font-family: 'Outfit', sans-serif;
			letter-spacing: 0.5px;
		}
		<?php echo $sel; ?> .pwc-card-body {
			padding: 16px 20px;
			display: flex;
			flex-direction: column;
			flex: 1;
			min-height: 0;
			justify-content: space-between;
		}
		<?php echo $sel; ?> .pwc-card-body p {
			-webkit-line-clamp: 2;
			display: -webkit-box;
			-webkit-box-orient: vertical;
			overflow: hidden;
		}
		<?php echo $sel; ?> .pwc-card:hover .pwc-card-body h2,
		<?php echo $sel; ?> .pwc-card:hover .pwc-card-body p {
			color: white !important;
		}
		/* 'card_thumb' — same card chrome, but laid out as a row: copy on the
		   left, a fixed-width photo thumbnail on the right, instead of the
		   full-width image strip across the top. */
		<?php echo $sel; ?> .pwc-card--thumb {
			flex-direction: row;
			align-items: stretch;
		}
		<?php echo $sel; ?> .pwc-card--thumb .pwc-card-body {
			flex: 1 1 auto;
			min-width: 0;
		}
		<?php echo $sel; ?> .pwc-card-thumb {
			flex: 0 0 96px;
			width: 96px;
			align-self: stretch;
			background-position: center center;
			background-size: cover;
			background-repeat: no-repeat;
			background-color: <?php echo esc_attr( $icon_bg ); ?>;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		<?php echo $sel; ?> .pwc-card-thumb .pwc-fallback-icon {
			font-size: 24px;
			color: #ffffff;
			opacity: 0.85;
			font-weight: 800;
			font-family: 'Outfit', sans-serif;
		}
		/* The tools column is only ~220px wide in the side-by-side layouts, so
		   a 96px thumbnail would leave too little room for the copy. Shrink it
		   there and let the stacked layouts (full width) keep the larger one. */
		<?php echo $sel; ?>.layout-left .pwc-card-thumb,
		<?php echo $sel; ?>.layout-right .pwc-card-thumb { flex-basis: 72px; width: 72px; }
		@media (max-width: 600px) {
			<?php echo $sel; ?> .pwc-card-thumb { flex-basis: 72px; width: 72px; }
		}
		<?php echo $sel; ?> .pwc-card:hover .pwc-card-title,
		<?php echo $sel; ?> .pwc-banner:hover .pwc-banner-title { color: <?php echo esc_attr( $title_hover_color ); ?> !important; }
		<?php echo $sel; ?> .pwc-banner {
			display: flex;
			text-decoration: none;
			transition: transform 0.2s ease, box-shadow 0.2s ease;
			flex: 1;
			min-height: 0;
			/* The three banner styles are surfaces, so they take
			   --radius-surface like .pwc-card above -- these are the same
			   Featured Tools tiles, just a different chosen style, and they
			   were the only variant still rendering square. The radius has to
			   be repeated on the child because the child is what actually
			   paints the background/gradient; `overflow: hidden` on the anchor
			   alone would clip it, but then the pill variant's 1.5px border
			   would be sliced square inside a rounded mask. */
			border-radius: var(--radius-surface, 14px);
		}
		<?php echo $sel; ?> .pwc-banner > div {
			border-radius: inherit;
			overflow: hidden;
		}
		/* Icon tile inside the image+text banner -- an icon tile on the scale,
		   so --radius-control, matching the buttons and chips around it. */
		<?php echo $sel; ?> .pwc-banner--image_text > div > div:first-child {
			border-radius: var(--radius-control, 6px);
		}
		<?php echo $sel; ?> .pwc-banner:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(0,0,0,0.10); }
		/* Image-led banner */
		<?php echo $sel; ?> .pwc-banner--image > div { position: relative; overflow: hidden; padding: 26px 24px; color: #ffffff; min-height: 160px; flex: 1; }
		<?php echo $sel; ?> .pwc-banner--image::after { content: ''; display: block; }
		/* Pill banner */
		<?php echo $sel; ?> .pwc-banner--pill > div { background: #ffffff; border: 1.5px solid #0A1929; padding: 16px 18px; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; min-height: 72px; flex: 1; }
		/* Horizontal image+text banner */
		<?php echo $sel; ?> .pwc-banner--image_text > div { display: flex; align-items: center; gap: 18px; padding: 22px; color: #ffffff; min-height: 140px; flex: 1; }
		@media (max-width: 992px) {
			<?php echo $sel; ?>.pathway-content-section { padding-top: 60px; }
			<?php echo $sel; ?> .pwc-card-image { height: 60px; }
		}

		/* ====================================================================
		 * Chrome the homepage used to supply.
		 *
		 * .section-label, .section-label-left, .color-bar, .tag and the whole
		 * .latest-list-* family were only ever declared inside front-page.php's
		 * inline <style>, and .bento-grid-news--grow only exists there too
		 * (main.css carries the plain .bento-grid-news, not the grow variant).
		 * So a Prime Block rendered anywhere OTHER than the homepage lost all
		 * of it: the 6px accent bar collapsed into a full-width slab stacked
		 * above the heading, and every article row dropped its thumbnail
		 * underneath the title instead of pinning it to the right edge.
		 *
		 * Declaring them here — scoped to this instance's own id, like every
		 * other rule in this block — makes the block genuinely self-contained
		 * and identical wherever it is placed. Scoping is what keeps this from
		 * fighting front-page.php: an id beats a bare class, so on the homepage
		 * these win for THIS block and leave every other section alone.
		 * ================================================================== */
		<?php echo $sel; ?> .section-label {
			display: flex;
			align-items: center;
			gap: 12px;
			justify-content: space-between;
		}
		<?php echo $sel; ?> .section-label-left {
			display: flex;
			align-items: center;
			gap: 12px;
			min-width: 0;
		}
		<?php echo $sel; ?> .section-label h2 { margin: 0; font-family: 'Outfit', sans-serif; }
		/* Never grow, never shrink: a bare `width` alone loses to the flex
		   container's own sizing, which is how this became a full-width bar. */
		<?php echo $sel; ?> .color-bar { flex: 0 0 6px; width: 6px; border-radius: var(--radius-control, 6px); }
		<?php echo $sel; ?> .tag {
			background: var(--primary-color, #008080);
			color: #ffffff;
			padding: 4px 12px;
			font-size: 11px;
			text-transform: uppercase;
			font-weight: 700;
			border-radius: var(--radius-control, 6px);
			display: inline-block;
			margin-bottom: 12px;
		}
		<?php echo $sel; ?> .bento-grid-news {
			display: grid;
			grid-template-columns: 2fr 1fr;
			grid-template-rows: repeat(2, 200px);
			gap: 24px;
		}
		<?php echo $sel; ?> .bento-grid-news.bento-grid-news--grow {
			grid-template-rows: auto;
			align-items: stretch;
			/* Small gap rather than flush: the shared border below frames the
			   image and the list as one section, this just stops them touching. */
			gap: 20px;
			border: 1.5px solid #e2e8f0;
		}
		<?php echo $sel; ?> .bento-grid-news.bento-grid-news--grow .bento-cell-featured {
			width: 100%;
			max-height: 460px;
		}
		<?php echo $sel; ?> .latest-list-box {
			grid-row: 1 / -1;
			display: flex;
			flex-direction: column;
			background: #ffffff;
			overflow: hidden;
		}
		<?php echo $sel; ?> .latest-list-item {
			/* Grow to divide the box height evenly (so the rows line up with
			   the featured cell) but never shrink below content height. */
			flex: 1 0 auto;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 8px;
			padding: 10px <?php echo $latest_show_thumbs ? '20px' : '16px'; ?> 10px 0;
			text-decoration: none;
			border-bottom: 1px solid #2f4f6f;
			transition: background 0.2s ease;
		}
		<?php echo $sel; ?> .latest-list-item:last-child { border-bottom: none; }
		<?php echo $sel; ?> .latest-list-item:hover { background: #f8fafc; }
		<?php echo $sel; ?> .latest-list-item:focus-visible {
			outline: 3px solid <?php echo esc_attr( $accent_bar_color ); ?>;
			outline-offset: -3px;
		}
		<?php echo $sel; ?> .latest-list-text {
			display: flex;
			flex-direction: column;
			justify-content: center;
			gap: 4px;
			flex: 1;
			min-width: 0;
		}
		/* Postage-stamp thumbnail pinned to the right edge of each row. */
		<?php echo $sel; ?> .latest-list-thumb {
			width: 48px;
			height: 48px;
			object-fit: cover;
			flex-shrink: 0;
		}
		<?php echo $sel; ?> .latest-list-cat {
			font-size: 9px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			color: var(--primary-color, #008080); /* per-post overlay colour set inline */
			line-height: 1.2;
		}
		<?php echo $sel; ?> .latest-list-title {
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
		<?php echo $sel; ?> .latest-list-item:hover .latest-list-title { color: var(--primary-color, #008080); }
		/* card-meta-footer (main.css) recoloured for the dark featured overlay. */
		<?php echo $sel; ?> .bento-content-overlay .card-meta-footer {
			border-top-color: rgba(255,255,255,0.25);
			color: rgba(255,255,255,0.78);
		}
		<?php echo $sel; ?> .bento-content-overlay .card-meta-footer .card-meta-item + .card-meta-item {
			border-left-color: rgba(255,255,255,0.25);
		}
		<?php echo $sel; ?> .pwc-card:focus-visible,
		<?php echo $sel; ?> .pwc-banner:focus-visible,
		<?php echo $sel; ?> .bento-cell-featured:focus-visible {
			outline: 3px solid <?php echo esc_attr( $accent_bar_color ); ?>;
			outline-offset: 3px;
		}
		@media (max-width: 992px) {
			/* The list drops below the featured cell, full width. */
			<?php echo $sel; ?> .latest-list-box { grid-row: auto; }
		}
		@media (prefers-reduced-motion: reduce) {
			<?php echo $sel; ?> .pwc-card,
			<?php echo $sel; ?> .pwc-banner,
			<?php echo $sel; ?> .latest-list-item,
			<?php echo $sel; ?> .latest-list-title,
			<?php echo $sel; ?> .bento-cell-featured img { transition: none !important; }
			<?php echo $sel; ?> .pwc-card:hover,
			<?php echo $sel; ?> .pwc-banner:hover { transform: none !important; }
			<?php echo $sel; ?> .bento-cell-featured:hover img { transform: none !important; }
		}
	</style>
	<section id="<?php echo esc_attr( $wrap_id ); ?>" class="pathway-content-section <?php echo esc_attr( $layout_class ); ?>">
		<div class="container">
			<div class="pathway-split-grid">
				<!-- Tools column (style: card / image_text / image / pill; position controlled by layout-* on parent section) -->
				<div class="pathway-tiles-stack" style="<?php echo $tools_stack_style; // phpcs:ignore WordPress.Security.EscapeOutput ?>">
					<div class="section-label" style="margin-bottom: 24px; border-bottom: none; padding-bottom: 0;">
						<div class="section-label-left">
							<?php if ( $accent_bar_show ) : ?>
							<div class="color-bar" style="background: <?php echo esc_attr( $accent_bar_color ); ?>; height: 20px;"></div>
							<?php endif; ?>
							<h2 style="font-size: 20px; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; font-family: 'Outfit', sans-serif; margin: 0; line-height: 20px; color: <?php echo esc_attr( $section_label_color ); ?>;"><?php echo esc_html( $label ); ?></h2>
						</div>
					</div>

					<?php foreach ( $cards as $card ) :
						$c_title = esc_html( (string) ( isset( $card['title'] ) ? $card['title'] : '' ) );
						$c_desc  = esc_html( (string) ( isset( $card['desc'] ) ? $card['desc'] : '' ) );
						$c_eye   = esc_html( (string) ( isset( $card['eyebrow'] ) ? $card['eyebrow'] : '' ) );
						$c_img   = (string) ( isset( $card['image'] ) ? $card['image'] : '' );
						$c_link  = esc_url( (string) ( isset( $card['link'] ) ? $card['link'] : '' ) );
						$c_fi    = esc_html( (string) ( isset( $card['fallback_icon'] ) ? $card['fallback_icon'] : '' ) );

						if ( $style === 'image' ) :
							// Image-led banner with dark overlay. Falls back to a flat
							// gradient when no image is set.
							$bg = $c_img
								? "background-image: linear-gradient(135deg, rgba(10,25,41,0.55) 0%, rgba(10,25,41,0.90) 100%), url('" . esc_url( $c_img ) . "'); background-size: cover; background-position: center;"
								: "background: linear-gradient(135deg, " . esc_attr( $eyebrow_color ) . " 0%, #0A1929 100%);";
					?>
						<a href="<?php echo $c_link; ?>" class="pwc-banner pwc-banner--image">
							<div style="<?php echo $bg; // phpcs:ignore WordPress.Security.EscapeOutput ?>">
								<?php if ( $c_eye !== '' ) : ?>
									<div style="display: inline-block; padding: 4px 10px; background: <?php echo esc_attr( $eyebrow_color ); ?>; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 12px;"><?php echo $c_eye; ?></div>
								<?php endif; ?>
								<h3 class="pwc-banner-title" style="margin: 0 0 6px; font-size: 22px; font-weight: 800; color: #ffffff; line-height: 1.15; font-family: 'Outfit', sans-serif;"><?php echo $c_title; ?></h3>
								<p style="margin: 0; font-size: 13px; opacity: 0.9; max-width: 320px; line-height: 1.5;"><?php echo $c_desc; ?></p>
							</div>
						</a>
					<?php elseif ( $style === 'pill' ) : ?>
						<a href="<?php echo $c_link; ?>" class="pwc-banner pwc-banner--pill">
							<div>
								<div style="display: flex; align-items: flex-start; gap: 12px; min-width: 0; flex: 1;">
									<span style="flex-shrink: 0; width: 32px; height: 32px; background: <?php echo esc_attr( $eyebrow_color ); ?>; color: #ffffff; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; font-family: 'Outfit', sans-serif; margin-top: 2px;"><?php echo $c_fi; ?></span>
									<div style="min-width: 0; flex: 1;">
										<div class="pwc-banner-title" style="font-size: 14px; font-weight: 700; color: <?php echo esc_attr( $title_color ); ?>; font-family: 'Outfit', sans-serif; line-height: 1.3;"><?php echo $c_title; ?></div>
										<div style="font-size: 12px; color: <?php echo esc_attr( $desc_color ); ?>; line-height: 1.4; margin-top: 2px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?php echo $c_desc; ?></div>
									</div>
								</div>
								<span style="background: <?php echo esc_attr( $eyebrow_color ); ?>; color: #ffffff; padding: 8px 14px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; white-space: nowrap; flex-shrink: 0; margin-top: 2px;">Open &rarr;</span>
							</div>
						</a>
					<?php elseif ( $style === 'image_text' ) : ?>
						<a href="<?php echo $c_link; ?>" class="pwc-banner pwc-banner--image_text">
							<div style="background: linear-gradient(135deg, <?php echo esc_attr( $eyebrow_color ); ?> 0%, #0A1929 100%);">
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
					<?php elseif ( $style === 'card_thumb' ) : // copy left, photo thumbnail right ?>
						<a href="<?php echo $c_link; ?>" class="pwc-card pwc-card--thumb" style="flex: 1;">
							<div class="pwc-card-body">
								<div>
									<h2 class="pwc-card-title" style="font-size: 20px; font-weight: 800; color: <?php echo esc_attr( $title_color ); ?>; margin: 0 0 8px 0; font-family: 'Outfit', sans-serif; transition: color 0.2s ease;"><?php echo $c_title; ?></h2>
									<p style="color: <?php echo esc_attr( $desc_color ); ?>; font-size: 13px; margin: 0 0 8px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?php echo $c_desc; ?></p>
								</div>
								<?php if ( $c_eye !== '' ) : ?>
									<p style="font-weight: 700; color: <?php echo esc_attr( $eyebrow_color ); ?>; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;"><?php echo $c_eye; ?></p>
								<?php endif; ?>
							</div>
							<div class="pwc-card-thumb" style="<?php echo $c_img ? 'background-image: url(\'' . esc_url( $c_img ) . '\');' : ''; ?>">
								<?php if ( ! $c_img ) : ?>
									<span class="pwc-fallback-icon"><?php echo $c_fi; ?></span>
								<?php endif; ?>
							</div>
						</a>
					<?php else : // 'card' (default — paired stacked tiles) ?>
						<a href="<?php echo $c_link; ?>" class="pwc-card" style="flex: 1;">
							<div class="pwc-card-image" style="<?php echo $c_img ? 'background-image: url(\'' . esc_url( $c_img ) . '\');' : ''; ?>">
								<?php if ( ! $c_img ) : ?>
									<span class="pwc-fallback-icon"><?php echo $c_fi; ?></span>
								<?php endif; ?>
							</div>
							<div class="pwc-card-body">
								<div>
									<h2 class="pwc-card-title" style="font-size: 22px; font-weight: 800; color: <?php echo esc_attr( $title_color ); ?>; margin: 0 0 10px 0; font-family: 'Outfit', sans-serif; transition: color 0.2s ease;"><?php echo $c_title; ?></h2>
									<p style="color: <?php echo esc_attr( $desc_color ); ?>; font-size: 14px; margin: 0 0 10px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?php echo $c_desc; ?></p>
								</div>
								<p style="font-weight: 700; color: <?php echo esc_attr( $eyebrow_color ); ?>; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;"><?php echo $c_eye; ?></p>
							</div>
						</a>
					<?php endif; endforeach; ?>
				</div>

				<!-- Latest Content Bento -->
				<div class="latest-content-column">
					<div class="section-label" style="margin-bottom: 24px; border-bottom: none; padding-bottom: 0;">
						<div class="section-label-left">
							<?php if ( $accent_bar_show ) : ?>
							<div class="color-bar" style="background: <?php echo esc_attr( $accent_bar_color ); ?>; height: 20px;"></div>
							<?php endif; ?>
							<h2 style="font-size: 20px; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; font-family: 'Outfit', sans-serif; margin: 0; line-height: 20px; color: #0f172a;"><?php echo esc_html( $latest_title ); ?></h2>
						</div>
					</div>

					<?php if ( ! empty( $latest_posts ) && count( $latest_posts ) >= 3 ) : ?>
					<div class="bento-grid-news bento-grid-news--grow">
						<?php $p = $latest_posts[0]; ?>
						<a href="<?php echo esc_url( get_permalink( $p->ID ) ); ?>" class="bento-cell-featured" data-vhh-post-id="<?php echo (int) $p->ID; ?>">
							<img src="<?php echo esc_url( get_the_post_thumbnail_url( $p->ID, 'large' ) ?: 'https://via.placeholder.com/800x600' ); ?>" alt="">
							<div class="bento-content-overlay">
								<span class="tag" style="background: var(--primary-color);">Featured</span>
								<h3 style="font-size: 28px; color: white; margin-bottom: 12px;"><?php echo esc_html( get_the_title( $p->ID ) ); ?></h3>
								<p class="bento-featured-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $p->ID ), 24 ) ); ?></p>
								<?php echo vance_card_meta_footer_html( $p->ID ); ?>
							</div>
						</a>
						<div class="latest-list-box">
							<?php foreach ( array_slice( $latest_posts, 1 ) as $p ) : ?>
							<a href="<?php echo esc_url( get_permalink( $p->ID ) ); ?>" class="latest-list-item" data-vhh-post-id="<?php echo (int) $p->ID; ?>">
								<div class="latest-list-text">
									<span class="latest-list-cat" style="color: <?php echo esc_attr( vance_post_eyebrow_color( $p->ID ) ); ?>;"><?php
										// Label with the MAIN (top-level) parent category, matching the eyebrow colour source.
										$main_cat = get_term( vance_post_overlay_main_category_id( $p->ID ), 'category' );
										echo ( $main_cat && ! is_wp_error( $main_cat ) ) ? esc_html( $main_cat->name ) : 'Latest';
									?></span>
									<h4 class="latest-list-title"><?php echo esc_html( get_the_title( $p->ID ) ); ?></h4>
								</div>
								<?php
								// The featured cell above keeps its image either way; this
								// controls only the postage-stamp thumbnails on the list rows.
								$list_thumb = $latest_show_thumbs ? get_the_post_thumbnail_url( $p->ID, 'thumbnail' ) : '';
								if ( $list_thumb ) : ?>
								<img class="latest-list-thumb" src="<?php echo esc_url( $list_thumb ); ?>" alt="" loading="lazy" width="48" height="48">
								<?php endif; ?>
							</a>
							<?php endforeach; ?>
						</div>
					</div>
					<?php elseif ( ! empty( $latest_posts ) ) : ?>
						<div style="display: flex; flex-direction: column; gap: 16px;">
							<?php foreach ( $latest_posts as $p ) : ?>
							<a href="<?php echo esc_url( get_permalink( $p->ID ) ); ?>" class="bento-cell-side">
								<span class="meta" style="color: var(--primary-color); margin-bottom: 8px;"><?php $cats = get_the_category( $p->ID ); echo ! empty( $cats ) ? esc_html( $cats[0]->name ) : 'Latest'; ?></span>
								<h4 class="heading-small"><?php echo esc_html( get_the_title( $p->ID ) ); ?></h4>
								<p class="text-body" style="font-size: 13px; margin-bottom: 8px;"><?php echo esc_html( wp_trim_words( get_the_excerpt( $p->ID ), 12 ) ); ?></p>
								<?php if ( $latest_show_date ) : ?>
								<div class="meta"><?php echo esc_html( human_time_diff( get_post_time( 'U', false, $p->ID ), current_time( 'timestamp' ) ) . ' ago' ); ?></div>
								<?php endif; ?>
							</a>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<div style="background: white; border-radius: var(--radius-surface, 14px); padding: 40px; text-align: center; border: 1px solid #e2e8f0;">
							<p style="color: #64748b; margin: 0;">No posts found for this selection.</p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Prime Block Home 1 — reads the ORIGINAL pathway-content keys so everything
 * the admin already configured on the live site carries over untouched. This
 * is why Home 1 can't share the prefix resolver below.
 */
function vance_render_prime_block_home1() {
	vance_render_prime_block( array(
		'wrap_id'             => 'vance-prime-block-home-1',
		'label'               => vance_get_theme_mod( 'vance_pwc_label', 'Featured Tools' ),
		'layout'              => vance_get_theme_mod( 'vance_pwc_layout', 'left' ),
		'style'               => vance_get_theme_mod( 'vance_pwc_style', 'card' ),
		'section_bg'          => vance_get_theme_mod( 'vance_pwc_section_bg', '#ffffff' ),
		'section_label_color' => vance_get_theme_mod( 'vance_pwc_section_label_color', '#0f172a' ),
		'title_color'         => vance_get_theme_mod( 'vance_pwc_card_title_color', '#0A1929' ),
		'title_hover_color'   => vance_get_theme_mod( 'vance_pwc_card_title_hover_color', '#ffffff' ),
		'desc_color'          => vance_get_theme_mod( 'vance_pwc_card_desc_color', '#64748b' ),
		'eyebrow_color'       => vance_get_theme_mod( 'vance_pwc_card_eyebrow_color', '#008080' ),
		'tools_column_bg'     => vance_get_theme_mod( 'vance_pwc_tools_column_bg', '' ),
		'hover_color'         => vance_get_theme_mod( 'vance_pwc_card_hover_color', '#008080' ),
		'icon_bg'             => vance_get_theme_mod( 'vance_pwc_icon_bg_color', '#0A1929' ),
		'cards'               => array(
			array(
				'title'         => vance_get_theme_mod( 'vance_hquiz_tile_title', 'Gastro Health Survey' ),
				'desc'          => vance_get_theme_mod( 'vance_hquiz_tile_desc',  'A 2-minute interactive quiz that points you to the most relevant tools, resources, and content for your situation.' ),
				'eyebrow'       => vance_get_theme_mod( 'vance_hquiz_tile_extra', 'Find your starting point' ),
				'image'         => vance_get_theme_mod( 'vance_hquiz_tile_image' ),
				'link'          => vance_get_theme_mod( 'vance_hquiz_tile_link',  '/gastro-health-survey/' ),
				'fallback_icon' => '?',
			),
			array(
				'title'         => vance_get_theme_mod( 'vance_askai_tile_title', 'VANCE-Ai' ),
				'desc'          => vance_get_theme_mod( 'vance_askai_tile_desc',  'Ask any health question and get an evidence-backed answer in seconds. Powered by curated clinical content, available 24/7.' ),
				'eyebrow'       => vance_get_theme_mod( 'vance_askai_tile_extra', 'Personalised answers, 24/7' ),
				'image'         => vance_get_theme_mod( 'vance_askai_tile_image' ),
				'link'          => vance_get_theme_mod( 'vance_askai_tile_link',  '/ask-ai/' ),
				'fallback_icon' => 'AI',
			),
		),
		'latest_title'        => vance_get_theme_mod( 'vance_pwc_latest_title', 'LATEST CONTENT' ),
		'latest_count'        => vance_get_theme_mod( 'vance_pwc_latest_count', 6 ),
		'latest_cat'          => (int) vance_get_theme_mod( 'vance_pwc_latest_category', 0 ),
		'latest_show_date'    => vance_get_theme_mod( 'vance_pwc_latest_show_date', true ),
		'latest_show_thumbs'  => vance_get_theme_mod( 'vance_pwc_latest_show_thumbs', true ),
		'accent_bar_show'     => vance_get_theme_mod( 'vance_pwc_accent_bar_show', true ),
		'accent_bar_color'    => vance_get_theme_mod( 'vance_pwc_accent_bar_color', '#008080' ),
		'tighten_next_cw'     => true,
	) );
}

/**
 * Shared resolver for the instances that use a clean, self-consistent prefix
 * (Home 2 and Categories). Defaults mirror Home 1's out-of-the-box content so
 * a freshly-enabled instance is immediately usable.
 *
 * @param string $prefix   Setting prefix, e.g. 'vance_pb2_'.
 * @param string $wrap_id  DOM id for the instance.
 * @param array  $defaults Per-instance default overrides. Only the keys that
 *                         genuinely differ between instances live here — today
 *                         that is 'latest_show_thumbs', which ships OFF for the
 *                         Categories block (the archive layout reads cleaner
 *                         without them) and ON for the homepage blocks. Must
 *                         stay in step with the defaults passed to
 *                         vance_register_prime_block_controls() in functions.php,
 *                         or the Customizer will show a state the front end
 *                         does not render.
 * @return array Values array for vance_render_prime_block().
 */
function vance_prime_block_vals_for_prefix( $prefix, $wrap_id, array $defaults = array() ) {
	$thumbs_default = ! array_key_exists( 'latest_show_thumbs', $defaults ) || ! empty( $defaults['latest_show_thumbs'] );

	return array(
		'wrap_id'             => $wrap_id,
		'label'               => vance_get_theme_mod( $prefix . 'label', 'Featured Tools' ),
		'layout'              => vance_get_theme_mod( $prefix . 'layout', 'left' ),
		'style'               => vance_get_theme_mod( $prefix . 'style', 'card' ),
		'section_bg'          => vance_get_theme_mod( $prefix . 'section_bg', '#ffffff' ),
		'section_label_color' => vance_get_theme_mod( $prefix . 'section_label_color', '#0f172a' ),
		'title_color'         => vance_get_theme_mod( $prefix . 'card_title_color', '#0A1929' ),
		'title_hover_color'   => vance_get_theme_mod( $prefix . 'card_title_hover_color', '#ffffff' ),
		'desc_color'          => vance_get_theme_mod( $prefix . 'card_desc_color', '#64748b' ),
		'eyebrow_color'       => vance_get_theme_mod( $prefix . 'card_eyebrow_color', '#008080' ),
		'tools_column_bg'     => vance_get_theme_mod( $prefix . 'tools_column_bg', '' ),
		'hover_color'         => vance_get_theme_mod( $prefix . 'card_hover_color', '#008080' ),
		'icon_bg'             => vance_get_theme_mod( $prefix . 'icon_bg_color', '#0A1929' ),
		'cards'               => array(
			array(
				'title'         => vance_get_theme_mod( $prefix . 'card1_title', 'Gastro Health Survey' ),
				'desc'          => vance_get_theme_mod( $prefix . 'card1_desc',  'A 2-minute interactive quiz that points you to the most relevant tools, resources, and content for your situation.' ),
				'eyebrow'       => vance_get_theme_mod( $prefix . 'card1_extra', 'Find your starting point' ),
				'image'         => vance_get_theme_mod( $prefix . 'card1_image' ),
				'link'          => vance_get_theme_mod( $prefix . 'card1_link',  '/gastro-health-survey/' ),
				'fallback_icon' => '?',
			),
			array(
				'title'         => vance_get_theme_mod( $prefix . 'card2_title', 'VANCE-Ai' ),
				'desc'          => vance_get_theme_mod( $prefix . 'card2_desc',  'Ask any health question and get an evidence-backed answer in seconds. Powered by curated clinical content, available 24/7.' ),
				'eyebrow'       => vance_get_theme_mod( $prefix . 'card2_extra', 'Personalised answers, 24/7' ),
				'image'         => vance_get_theme_mod( $prefix . 'card2_image' ),
				'link'          => vance_get_theme_mod( $prefix . 'card2_link',  '/ask-ai/' ),
				'fallback_icon' => 'AI',
			),
		),
		'latest_title'        => vance_get_theme_mod( $prefix . 'latest_title', 'LATEST CONTENT' ),
		'latest_count'        => vance_get_theme_mod( $prefix . 'latest_count', 6 ),
		'latest_cat'          => (int) vance_get_theme_mod( $prefix . 'latest_category', 0 ),
		'latest_show_date'    => vance_get_theme_mod( $prefix . 'latest_show_date', true ),
		'latest_show_thumbs'  => vance_get_theme_mod( $prefix . 'latest_show_thumbs', $thumbs_default ),
		'accent_bar_show'     => vance_get_theme_mod( $prefix . 'accent_bar_show', true ),
		'accent_bar_color'    => vance_get_theme_mod( $prefix . 'accent_bar_color', '#008080' ),
	);
}

/** Prime Block Home 2 — a second, independently-configured homepage instance. */
function vance_render_prime_block_home2() {
	vance_render_prime_block( vance_prime_block_vals_for_prefix( 'vance_pb2_', 'vance-prime-block-home-2' ) );
}

/**
 * Per-instance default overrides for the Categories block. See the $defaults
 * docblock on vance_prime_block_vals_for_prefix() — functions.php reads the
 * same array when registering the controls, so the two can't drift.
 */
function vance_prime_block_categories_defaults() {
	return array(
		// The archive already leads with a hero and (usually) a promo card, so
		// the extra 48px thumbnails on every list row made the column noisy.
		'latest_show_thumbs' => false,
	);
}

/**
 * Is the Prime Block switched on for this particular category?
 *
 * Defaults to TRUE so ticking the master switch behaves the way it always has
 * — on for every archive — and unticking individual categories is the opt-out.
 * Non-category archives (post-type archives, tag pages) have no term to key
 * off, so they follow the master switch alone.
 */
function vance_prime_block_category_enabled( $term_id ) {
	$term_id = (int) $term_id;
	if ( $term_id <= 0 ) {
		return true;
	}
	return (bool) vance_get_theme_mod( 'vance_pbc_cat_' . $term_id, true );
}

/**
 * Prime Block Categories — one configured block, shown on the category
 * archives it is enabled for. Gated behind its own opt-in checkbox, in the
 * same bail-early style as vance_render_category_promo().
 *
 * The archive templates call this once per placement slot; it renders in the
 * slot matching the "Position on the page" setting and bails in the other two.
 * The default slot matches the historical position, so a template that has not
 * been updated to pass a slot still renders the block exactly where it was.
 *
 * @param string $slot Which call site this is: above_promo|below_promo|above_footer.
 */
function vance_render_prime_block_categories( $slot = 'below_promo' ) {
	if ( ! vance_get_theme_mod( 'vance_pbc_show_on_categories', false ) ) {
		return;
	}

	$placement = vance_get_theme_mod( 'vance_pbc_placement', 'below_promo' );
	if ( ! array_key_exists( $placement, vance_prime_block_placement_choices() ) ) {
		$placement = 'below_promo';
	}
	if ( $placement !== $slot ) {
		return;
	}

	if ( is_category() && ! vance_prime_block_category_enabled( get_queried_object_id() ) ) {
		return;
	}

	vance_render_prime_block( vance_prime_block_vals_for_prefix(
		'vance_pbc_',
		'vance-prime-block-categories',
		vance_prime_block_categories_defaults()
	) );
}

/**
 * Prime Block Knowledgebase — one configured block on the Knowledgebase page.
 *
 * Same shape as vance_render_prime_block_categories(): gated behind its own
 * opt-in checkbox, and the template calls it once per placement slot so it
 * renders in the slot matching "Position on the page" and bails in the others.
 * Slots are the Knowledgebase template's own, so it shares
 * vance_kb_page_placement_choices() with the Knowledgebase promo block rather
 * than the archive's vance_prime_block_placement_choices().
 *
 * @param string $slot Which call site this is; see vance_kb_page_placement_choices().
 */
function vance_render_prime_block_knowledgebase( $slot = 'below_intro' ) {
	if ( ! vance_get_theme_mod( 'vance_pbk_show', false ) ) {
		return;
	}

	$placement = vance_get_theme_mod( 'vance_pbk_placement', 'below_intro' );
	if ( ! function_exists( 'vance_kb_page_placement_choices' )
		|| ! array_key_exists( $placement, vance_kb_page_placement_choices() ) ) {
		$placement = 'below_intro';
	}
	if ( $placement !== $slot ) {
		return;
	}

	vance_render_prime_block( vance_prime_block_vals_for_prefix(
		'vance_pbk_',
		'vance-prime-block-knowledgebase'
	) );
}

// ============================================================================
// Registry — the two homepage instances become orderable sections. The
// Categories instance is NOT registered: it's called directly from the archive
// templates, matching the vance_render_category_promo() precedent.
// ============================================================================

add_filter( 'vance_homepage_sections', function ( $sections ) {
	$sections['prime-block-home-1'] = array(
		'label'  => 'Prime Block Home 1',
		'group'  => 'Homepage',
		'render' => 'vance_render_prime_block_home1',
	);
	$sections['prime-block-home-2'] = array(
		'label'  => 'Prime Block Home 2',
		'group'  => 'Homepage',
		'render' => 'vance_render_prime_block_home2',
	);
	return $sections;
} );
