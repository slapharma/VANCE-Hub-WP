<?php
/**
 * Tool Widgets — homepage cards that open a focused tool in a modal.
 *
 * Replaces the old monolithic 'discovery' homepage block with two focused
 * tool widget cards:
 *
 *   - 'tool-widget-content-filters'  -> opens the chip-filter search UI in
 *                                       a modal (same filters the old
 *                                       Discovery block had: reading level
 *                                       toggles, healthcare pathway chips,
 *                                       content type chips, keyword input,
 *                                       GO button posts to /discovery-results/).
 *   - 'tool-widget-vance-ai'         -> opens a minimal chat window in a
 *                                       modal that talks to the existing
 *                                       /wp-json/vance-health/v1/ai-chat
 *                                       REST endpoint.
 *
 * IMPORTANT: this is NOT an iframe of the /discovery/ or /ask-ai/ page.
 * The tool UI is rendered inline INSIDE the modal, so the user gets a
 * focused, scoped tool instead of a duplicated full page with its own
 * header/footer. Earlier iframe version (commit d6a88d2) was incorrect
 * and was rebuilt per user feedback.
 *
 * @package sla-health-hub
 * @since   2026-05-25
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Emit the shared modal CSS once per page (used by both widgets' modals).
 * The actual modal HTML is per-widget so each tool stays scoped.
 */
function vance_tool_widgets_emit_modal_css_once() {
	static $emitted = false;
	if ( $emitted ) { return; }
	$emitted = true;
		$c_backdrop   = vance_get_theme_mod( 'vance_modal_backdrop',        'rgba(10, 25, 41, 0.78)' );
		$c_panel_bg   = vance_get_theme_mod( 'vance_modal_panel_bg',        '#0A1929' );
		$c_text       = vance_get_theme_mod( 'vance_modal_text_color',      '#ffffff' );
		$c_header_bg  = vance_get_theme_mod( 'vance_modal_header_bg',       '#061119' );
		$c_title      = vance_get_theme_mod( 'vance_modal_title_color',     '#ffffff' );
		$c_bot_bg     = vance_get_theme_mod( 'vance_modal_bot_bubble_bg',   'rgba(255,255,255,0.08)' );
		$c_bot_text   = vance_get_theme_mod( 'vance_modal_bot_bubble_text', '#ffffff' );
		$c_user_bg    = vance_get_theme_mod( 'vance_modal_user_bubble_bg',  '#008080' );
		$c_user_text  = vance_get_theme_mod( 'vance_modal_user_bubble_text','#ffffff' );
		$c_input_bg   = vance_get_theme_mod( 'vance_modal_input_bg',        'rgba(255,255,255,0.94)' );
		$c_input_text = vance_get_theme_mod( 'vance_modal_input_text',      '#1a2332' );
		$c_send_bg    = vance_get_theme_mod( 'vance_modal_send_bg',         '#008080' );
	?>
	<style>
		.vance-tw-modal {
			position: fixed; inset: 0;
			z-index: 99999;
			display: none;
			align-items: center; justify-content: center;
			background: <?php echo $c_backdrop; ?>;
			opacity: 0;
			transition: opacity 0.25s ease;
		}
		.vance-tw-modal.is-open { display: flex; opacity: 1; }
		.vance-tw-modal__panel {
			position: relative;
			width: min(720px, 96vw);
			max-height: min(86vh, 800px);
			background: <?php echo $c_panel_bg; ?>;
			color: <?php echo $c_text; ?>;
			border-radius: 0;
			box-shadow: 0 40px 80px rgba(0, 0, 0, 0.40);
			overflow: hidden;
			display: flex; flex-direction: column;
			border: 1px solid rgba(255,255,255,0.08);
		}
		.vance-tw-modal__header {
			display: flex; align-items: center; justify-content: space-between;
			padding: 14px 22px;
			background: <?php echo $c_header_bg; ?>;
			border-bottom: 1px solid rgba(255,255,255,0.10);
		}
		.vance-tw-modal__title {
			margin: 0;
			font-size: 14px; font-weight: 700;
			font-family: 'Outfit', sans-serif;
			letter-spacing: 0.6px;
			text-transform: uppercase;
			color: <?php echo $c_title; ?>;
		}
		.vance-tw-modal__close {
			background: transparent; border: none;
			color: #ffffff; opacity: 0.85;
			font-size: 22px; line-height: 1;
			cursor: pointer; padding: 4px 10px;
			border-radius: 0;
		}
		.vance-tw-modal__close:hover { opacity: 1; background: rgba(255,255,255,0.10); }
		.vance-tw-modal__body { flex: 1; min-height: 0; padding: 22px 24px; overflow-y: auto; }
		.vance-tw-modal__footer { padding: 14px 22px; border-top: 1px solid rgba(255,255,255,0.10); background: <?php echo $c_header_bg; ?>; }
		/* Reused filter UI bits (same look as the old Discovery block) */
		.vance-tw-modal .filter-group { margin-bottom: 22px; }
		.vance-tw-modal .filter-label {
			font-family: 'Outfit', sans-serif; font-size: 11px; font-weight: 800;
			color: rgba(255,255,255,0.55); margin-bottom: 10px;
			text-transform: uppercase; letter-spacing: 1.4px;
		}
		.vance-tw-modal .toggle-row { display: flex; gap: 16px; flex-wrap: wrap; }
		.vance-tw-modal .toggle-item { display: flex; align-items: center; gap: 10px; cursor: pointer; }
		.vance-tw-modal .toggle-switch {
			width: 40px; height: 22px;
			background: rgba(255,255,255,0.10);
			border: 1px solid rgba(255,255,255,0.15);
			border-radius: 0; position: relative; transition: 0.2s;
			flex-shrink: 0;
		}
		.vance-tw-modal .toggle-switch::after {
			content: ''; position: absolute; top: 2px; left: 2px;
			width: 16px; height: 16px;
			background: rgba(255,255,255,0.60);
			border-radius: 0; transition: 0.2s;
		}
		.vance-tw-modal .toggle-item.active .toggle-switch { background: #008080; border-color: #008080; }
		.vance-tw-modal .toggle-item.active .toggle-switch::after { transform: translateX(18px); background: #ffffff; }
		.vance-tw-modal .toggle-label { font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.85); }
		.vance-tw-modal .chip-grid { display: flex; flex-wrap: wrap; gap: 8px; }
		.vance-tw-modal .text-chip {
			display: inline-flex; align-items: center; justify-content: center;
			min-width: 120px; padding: 8px 16px;
			background: rgba(255,255,255,0.06);
			border: 1px solid rgba(255,255,255,0.12);
			border-radius: 0; font-size: 13px; font-weight: 700;
			color: rgba(255,255,255,0.85); cursor: pointer; user-select: none;
			transition: all 0.15s;
		}
		.vance-tw-modal .text-chip:hover { background: rgba(255,255,255,0.10); }
		.vance-tw-modal .text-chip.selected { background: rgba(0,128,128,0.30); border-color: #008080; color: #ffffff; }
		.vance-tw-modal .keyword-input {
			width: 100%; box-sizing: border-box;
			padding: 11px 14px;
			background: rgba(255,255,255,0.94); color: #1a2332;
			border: 1px solid rgba(255,255,255,0.20); border-radius: 0;
			font-size: 14px;
		}
		.vance-tw-modal .vance-tw-btn-go {
			padding: 11px 28px;
			background: linear-gradient(135deg, #008080, #006666);
			color: #ffffff; border: none; border-radius: 0;
			font-family: 'Outfit', sans-serif; font-size: 14px;
			font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;
			cursor: pointer; box-shadow: 0 4px 14px rgba(0,128,128,0.35);
		}
		.vance-tw-modal .vance-tw-btn-go:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0,128,128,0.5); }
		.vance-tw-modal .vance-tw-btn-text {
			padding: 10px 16px;
			background: transparent;
			border: 1px solid rgba(255,255,255,0.20);
			color: rgba(255,255,255,0.85);
			border-radius: 0; cursor: pointer;
			font-size: 12px; font-weight: 700;
			text-transform: uppercase; letter-spacing: 0.4px;
		}
		/* Chat bits */
		.vance-tw-chat-messages {
			flex: 1; min-height: 280px; max-height: 50vh;
			overflow-y: auto;
			padding: 12px 4px;
			display: flex; flex-direction: column; gap: 10px;
		}
		.vance-tw-chat-bubble { padding: 10px 14px; border-radius: 4px; font-size: 13px; line-height: 1.5; max-width: 86%; }
		.vance-tw-chat-bubble.bot  { background: <?php echo $c_bot_bg; ?>; color: <?php echo $c_bot_text; ?>; align-self: flex-start; }
		.vance-tw-chat-bubble.user { background: <?php echo $c_user_bg; ?>;                 color: <?php echo $c_user_text; ?>; align-self: flex-end; }
		.vance-tw-chat-input-bar { display: flex; gap: 8px; }
		.vance-tw-chat-input {
			flex: 1; padding: 10px 14px;
			background: <?php echo $c_input_bg; ?>; color: <?php echo $c_input_text; ?>;
			border: 1px solid rgba(255,255,255,0.20); border-radius: 0;
			font-size: 14px;
		}
		.vance-tw-chat-send {
			padding: 10px 18px;
			background: <?php echo $c_send_bg; ?>;
			color: #ffffff; border: none; border-radius: 0;
			font-family: 'Outfit', sans-serif; font-size: 13px;
			font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px;
			cursor: pointer;
		}
	</style>
	<script>
	(function () {
		'use strict';
		// Open: click on any element with data-vance-tw-open="<modal-id>"
		// Close: click on any element with data-vance-tw-close (no value needed),
		// click on the backdrop, or hit Escape.
		document.addEventListener('click', function (e) {
			var opener = e.target.closest('[data-vance-tw-open]');
			if (opener) {
				e.preventDefault();
				var id = opener.getAttribute('data-vance-tw-open');
				var m  = document.getElementById(id);
				if (m) { m.classList.add('is-open'); m.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; }
				return;
			}
			var closer = e.target.closest('[data-vance-tw-close]');
			if (closer) {
				e.preventDefault();
				var m2 = closer.closest('.vance-tw-modal');
				if (m2) { m2.classList.remove('is-open'); m2.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; }
				return;
			}
			if (e.target.classList && e.target.classList.contains('vance-tw-modal')) {
				e.target.classList.remove('is-open');
				e.target.setAttribute('aria-hidden', 'true');
				document.body.style.overflow = '';
			}
		});
		document.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape') return;
			var open = document.querySelector('.vance-tw-modal.is-open');
			if (open) {
				open.classList.remove('is-open');
				open.setAttribute('aria-hidden', 'true');
				document.body.style.overflow = '';
			}
		});
	})();
	</script>
	<?php
}

/**
 * Generic card shell (image panel + body + button). Used by both widgets.
 * The button's data-vance-tw-open attribute opens the named modal.
 *
 * @param array $args { title, desc, cta, accent, bg_color, title_color,
 *                      desc_color, image, modal_id, fallback_icon_svg }
 */
function vance_tool_widget_card( $args ) {
	$title    = $args['title'];
	$desc     = $args['desc'];
	$cta      = $args['cta'];
	$accent   = $args['accent'];
	$bg_color = $args['bg_color'];
	$title_c  = $args['title_color'];
	$desc_c   = $args['desc_color'];
	$image    = $args['image'];
	$modal_id = $args['modal_id'];
	$svg      = $args['fallback_icon_svg'];

	$card_id = 'vance-tw-card-' . sanitize_html_class( $modal_id );
	?>
	<section class="vance-tool-widget" id="<?php echo esc_attr( $card_id ); ?>" style="background: <?php echo esc_attr( $bg_color ); ?>; padding: 60px 0;">
		<div class="container">
			<div class="vance-tool-widget__card" style="
				display: flex;
				background: #ffffff;
				border: 1.5px solid #e2e8f0;
				border-top: 4px solid <?php echo esc_attr( $accent ); ?>;
				box-shadow: 0 6px 24px rgba(0,0,0,0.06);
				overflow: hidden;
			">
				<div class="vance-tool-widget__image" style="
					flex: 0 0 38%;
					min-height: 220px;
					background-color: <?php echo esc_attr( $accent ); ?>;
					<?php echo $image ? "background-image: url('" . esc_url( $image ) . "'); background-size: cover; background-position: center center;" : ''; ?>
					display: flex; align-items: center; justify-content: center;
				">
					<?php if ( ! $image ) : ?>
						<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.5" aria-hidden="true"><?php echo $svg; ?></svg>
					<?php endif; ?>
				</div>
				<div class="vance-tool-widget__body" style="flex: 1; padding: 32px 36px; display: flex; flex-direction: column; justify-content: center;">
					<h2 style="font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; color: <?php echo esc_attr( $title_c ); ?>; margin: 0 0 12px 0;"><?php echo esc_html( $title ); ?></h2>
					<p style="font-size: 15px; line-height: 1.6; color: <?php echo esc_attr( $desc_c ); ?>; margin: 0 0 20px 0; max-width: 560px;"><?php echo esc_html( $desc ); ?></p>
					<div>
						<button type="button" class="btn btn-primary" style="background: <?php echo esc_attr( $accent ); ?>; border-color: <?php echo esc_attr( $accent ); ?>;" data-vance-tw-open="<?php echo esc_attr( $modal_id ); ?>"><?php echo esc_html( $cta ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<style>
			@media (max-width: 768px) {
				#<?php echo esc_attr( $card_id ); ?> .vance-tool-widget__card { flex-direction: column; }
				#<?php echo esc_attr( $card_id ); ?> .vance-tool-widget__image { flex: 0 0 180px; min-height: 180px; }
				#<?php echo esc_attr( $card_id ); ?> .vance-tool-widget__body { padding: 24px; }
			}
		</style>
	</section>
	<?php
}

/**
 * Modal shell. Takes the inline tool UI as a callable.
 */
function vance_tool_widget_modal( $modal_id, $title, $render_body_callable ) {
	?>
	<div id="<?php echo esc_attr( $modal_id ); ?>" class="vance-tw-modal" role="dialog" aria-modal="true" aria-hidden="true">
		<div class="vance-tw-modal__panel">
			<div class="vance-tw-modal__header">
				<h2 class="vance-tw-modal__title"><?php echo esc_html( $title ); ?></h2>
				<button type="button" class="vance-tw-modal__close" aria-label="Close" data-vance-tw-close>&times;</button>
			</div>
			<div class="vance-tw-modal__body">
				<?php call_user_func( $render_body_callable ); ?>
			</div>
		</div>
	</div>
	<?php
}

// =========================================================================
// Content Filters widget
// =========================================================================
function vance_render_tool_widget_content_filters() {
	$prefix = 'vance_tw_content_filters_';
	vance_tool_widgets_emit_modal_css_once();

	vance_tool_widget_card( array(
		'title'    => vance_get_theme_mod( $prefix . 'title',  'Content Filters' ),
		'desc'     => vance_get_theme_mod( $prefix . 'desc',   'Filter the knowledge base by reading level, pathway, content type and keywords — find exactly the article, study, or guide you need in seconds.' ),
		'cta'      => vance_get_theme_mod( $prefix . 'cta',    'Open Filters' ),
		'accent'   => vance_get_theme_mod( $prefix . 'accent', '#008080' ),
		'bg_color' => vance_get_theme_mod( $prefix . 'bg_color',    '#ffffff' ),
		'title_color' => vance_get_theme_mod( $prefix . 'title_color', '#0F172A' ),
		'desc_color'  => vance_get_theme_mod( $prefix . 'desc_color',  '#64748b' ),
		'image'    => vance_get_theme_mod( $prefix . 'image', '' ),
		'modal_id' => 'vance-tw-modal-content-filters',
		'fallback_icon_svg' => '<rect x="3" y="6" width="18" height="2"/><rect x="6" y="11" width="12" height="2"/><rect x="9" y="16" width="6" height="2"/>',
	) );

	vance_tool_widget_modal( 'vance-tw-modal-content-filters', 'Content Filters', 'vance_tw_render_content_filters_body' );
}

function vance_tw_render_content_filters_body() {
	$all_tags       = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) );
	if ( is_wp_error( $all_tags ) ) { $all_tags = array(); }
	$all_categories = get_categories( array( 'hide_empty' => false ) );

	// Reading levels (tags prefixed reading-)
	$reading_tags = array();
	foreach ( $all_tags as $tag ) {
		if ( ( stripos( $tag->name, 'reading-' ) === 0 || stripos( $tag->slug, 'reading-' ) === 0 ) && vance_get_theme_mod( "vance_discovery_reading_show_{$tag->term_id}" ) ) {
			$reading_tags[] = array(
				'tag'   => $tag,
				'order' => vance_get_theme_mod( "vance_discovery_reading_order_{$tag->term_id}", 10 ),
				'text'  => vance_get_theme_mod( "vance_discovery_reading_text_{$tag->term_id}", str_replace( 'reading-', '', $tag->name ) ),
			);
		}
	}
	usort( $reading_tags, function ( $a, $b ) { return $a['order'] - $b['order']; } );

	// Healthcare pathway chips (tags prefixed path-)
	$path_tags = array();
	foreach ( $all_tags as $tag ) {
		if ( ( stripos( $tag->name, 'path-' ) === 0 || stripos( $tag->slug, 'path-' ) === 0 ) && vance_get_theme_mod( "vance_discovery_path_show_{$tag->term_id}" ) ) {
			$path_tags[] = array(
				'tag'   => $tag,
				'order' => vance_get_theme_mod( "vance_discovery_path_order_{$tag->term_id}", 10 ),
				'text'  => vance_get_theme_mod( "vance_discovery_path_text_{$tag->term_id}", str_replace( 'path-', '', $tag->name ) ),
			);
		}
	}
	usort( $path_tags, function ( $a, $b ) { return $a['order'] - $b['order']; } );

	// Content type chips (categories)
	$type_cats = array();
	foreach ( $all_categories as $cat ) {
		if ( vance_get_theme_mod( "vance_discovery_type_show_{$cat->term_id}" ) ) {
			$type_cats[] = array(
				'cat'   => $cat,
				'order' => vance_get_theme_mod( "vance_discovery_type_order_{$cat->term_id}", 10 ),
				'text'  => vance_get_theme_mod( "vance_discovery_type_text_{$cat->term_id}", $cat->name ),
			);
		}
	}
	usort( $type_cats, function ( $a, $b ) { return $a['order'] - $b['order']; } );
	?>
	<form action="<?php echo esc_url( home_url( '/discovery-results/' ) ); ?>" method="GET" class="vance-tw-filters-form">

		<?php if ( ! empty( $reading_tags ) ) : ?>
		<div class="filter-group">
			<div class="filter-label">Reading Level</div>
			<div class="toggle-row">
				<?php foreach ( $reading_tags as $item ) : ?>
				<label class="toggle-item">
					<input type="checkbox" name="reading_level[]" value="<?php echo esc_attr( $item['tag']->slug ); ?>" style="display:none;" onchange="this.parentElement.classList.toggle('active', this.checked)">
					<div class="toggle-switch"></div>
					<span class="toggle-label"><?php echo esc_html( $item['text'] ); ?></span>
				</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $path_tags ) ) : ?>
		<div class="filter-group">
			<div class="filter-label">Healthcare Pathway</div>
			<div class="chip-grid">
				<?php foreach ( $path_tags as $item ) : ?>
				<label class="text-chip">
					<input type="checkbox" name="pathway_tag[]" value="<?php echo esc_attr( $item['tag']->slug ); ?>" style="display:none;" onchange="this.parentElement.classList.toggle('selected', this.checked)">
					<span><?php echo esc_html( $item['text'] ); ?></span>
				</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $type_cats ) ) : ?>
		<div class="filter-group">
			<div class="filter-label">Content Type</div>
			<div class="chip-grid">
				<?php foreach ( $type_cats as $item ) : ?>
				<label class="text-chip">
					<input type="checkbox" name="content_type[]" value="<?php echo esc_attr( $item['cat']->slug ); ?>" style="display:none;" onchange="this.parentElement.classList.toggle('selected', this.checked)">
					<span><?php echo esc_html( $item['text'] ); ?></span>
				</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<div class="filter-group" style="margin-bottom: 0;">
			<input type="text" name="s" class="keyword-input" placeholder="Keyword Search (optional)">
		</div>

		<div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.10); display: flex; gap: 12px; align-items: center;">
			<button type="submit" class="vance-tw-btn-go">GO</button>
			<button type="reset" class="vance-tw-btn-text" onclick="this.closest('form').querySelectorAll('.toggle-item.active, .text-chip.selected').forEach(function(el){ el.classList.remove('active','selected'); });">Clear</button>
		</div>
	</form>
	<?php
}

// =========================================================================
// Vance AI widget
// =========================================================================
function vance_render_tool_widget_vance_ai() {
	$prefix = 'vance_tw_vance_ai_';
	vance_tool_widgets_emit_modal_css_once();

	vance_tool_widget_card( array(
		'title'    => vance_get_theme_mod( $prefix . 'title',  'Vance AI' ),
		'desc'     => vance_get_theme_mod( $prefix . 'desc',   'Ask any gastro health question and get an evidence-backed answer in seconds. Powered by curated clinical content — available 24/7.' ),
		'cta'      => vance_get_theme_mod( $prefix . 'cta',    'Open Chat' ),
		'accent'   => vance_get_theme_mod( $prefix . 'accent', '#0EA5E9' ),
		'bg_color' => vance_get_theme_mod( $prefix . 'bg_color',    '#ffffff' ),
		'title_color' => vance_get_theme_mod( $prefix . 'title_color', '#0F172A' ),
		'desc_color'  => vance_get_theme_mod( $prefix . 'desc_color',  '#64748b' ),
		'image'    => vance_get_theme_mod( $prefix . 'image', '' ),
		'modal_id' => 'vance-tw-modal-vance-ai',
		'fallback_icon_svg' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
	) );

	vance_tool_widget_modal( 'vance-tw-modal-vance-ai', 'Vance AI', 'vance_tw_render_vance_ai_body' );
}

function vance_tw_render_vance_ai_body() {
	$endpoint = esc_url( home_url( '/wp-json/vance-health/v1/ai-chat' ) );
	$nonce    = wp_create_nonce( 'wp_rest' );
	?>
	<div class="vance-tw-chat-messages" id="vance-tw-chat-messages">
		<div class="vance-tw-chat-bubble bot">Welcome. I can help you explore IBD content. What would you like to know?</div>
	</div>
	<div class="vance-tw-chat-input-bar">
		<input type="text" class="vance-tw-chat-input" id="vance-tw-chat-input" placeholder="Ask Vance AI…" autocomplete="off">
		<button type="button" class="vance-tw-chat-send" id="vance-tw-chat-send">Send</button>
	</div>
	<script>
	(function () {
		'use strict';
		var ENDPOINT = '<?php echo $endpoint; ?>';
		var NONCE    = '<?php echo $nonce; ?>';
		var input    = document.getElementById('vance-tw-chat-input');
		var sendBtn  = document.getElementById('vance-tw-chat-send');
		var area     = document.getElementById('vance-tw-chat-messages');
		var history  = [];

			function vanceEscapeHtml(x){return String(x).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
			function vanceFormatMd(raw){
				var t = vanceEscapeHtml(raw);
				t = t.replace(/^[ \t]*#{1,6}[ \t]*(.+)$/gm, '<strong>$1</strong>');
				t = t.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
				t = t.replace(/^[ \t]*[-*][ \t]+(.+)$/gm, '\u2022 $1');
				t = t.replace(/\n/g, '<br>');
				return t;
			}
		function append(role, text) {
			if (!area) return;
			var bubble = document.createElement('div');
			bubble.className = 'vance-tw-chat-bubble ' + (role === 'user' ? 'user' : 'bot');
			if (role === 'user') { bubble.textContent = text; } else { bubble.innerHTML = vanceFormatMd(text); }
			area.appendChild(bubble);
			area.scrollTop = area.scrollHeight;
		}

		function send() {
			if (!input) return;
			var msg = (input.value || '').trim();
			if (!msg) return;
			append('user', msg);
			history.push({ role: 'user', content: msg });
			input.value = '';
			input.disabled = true;
			sendBtn.disabled = true;
			append('bot', '…');
			var thinking = area.lastElementChild;

			fetch(ENDPOINT, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
				body: JSON.stringify({ messages: history })
			})
			.then(function (r) { return r.json(); })
			.then(function (d) {
				if (thinking && thinking.parentNode === area) { area.removeChild(thinking); }
				var reply = (d && (d.reply || d.answer || d.content)) || 'Sorry, I could not reach the AI service just now.';
				append('bot', reply);
				history.push({ role: 'assistant', content: reply });
			})
			.catch(function () {
				if (thinking && thinking.parentNode === area) { area.removeChild(thinking); }
				append('bot', 'Sorry, that request failed. Please try again.');
			})
			.finally(function () {
				input.disabled  = false;
				sendBtn.disabled = false;
				input.focus();
			});
		}

		if (sendBtn) { sendBtn.addEventListener('click', send); }
		if (input)   { input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); send(); } }); }
	})();
	</script>
	<?php
}

// =========================================================================
// Merged Tool Widgets Row (banner-style)
// =========================================================================
/**
 * Render BOTH tool widget banners side-by-side in a single homepage section.
 * Style is admin-selectable via Customizer:
 *   - 'horizontal' (default): icon-led horizontal banner with gradient
 *   - 'image':                background image with dark overlay
 *   - 'pill':                 compact pill banner with CTA on the right
 *
 * Per-card content reads from new vance_twrow_card{N}_* settings first, then
 * falls back to the legacy vance_tw_{content_filters,vance_ai}_* settings so
 * existing customisations carry over without re-saving anything.
 *
 * Both modals (content filters + vance AI) are still emitted so the cards open
 * the same focused tools they always did.
 *
 * @since 2026-05-26
 */
function vance_render_tool_widgets_row() {
	vance_tool_widgets_emit_modal_css_once();

	$style = vance_get_theme_mod( 'vance_twrow_style', 'horizontal' );
	if ( ! in_array( $style, array( 'horizontal', 'image', 'pill' ), true ) ) { $style = 'horizontal'; }

	$sec_bg     = vance_get_theme_mod( 'vance_twrow_section_bg', '#F8FAFC' );
	$pad_top    = absint( vance_get_theme_mod( 'vance_twrow_pad_top', 60 ) );
	$pad_bot    = absint( vance_get_theme_mod( 'vance_twrow_pad_bottom', 60 ) );
	$show_head  = (bool) vance_get_theme_mod( 'vance_twrow_show_heading', false );
	$head_text  = vance_get_theme_mod( 'vance_twrow_heading', 'Quick Tools' );
	$head_color = vance_get_theme_mod( 'vance_twrow_heading_color', '#0A1929' );

	// Per-card data, with fallbacks to legacy settings.
	$cards = array(
		array(
			'modal_id' => 'vance-tw-modal-content-filters',
			'eyebrow'  => vance_get_theme_mod( 'vance_twrow_card1_eyebrow', 'Filter content' ),
			'title'    => vance_get_theme_mod( 'vance_twrow_card1_title', '' )
				?: vance_get_theme_mod( 'vance_tw_content_filters_title', 'Content Filters' ),
			'desc'     => vance_get_theme_mod( 'vance_twrow_card1_desc', '' )
				?: vance_get_theme_mod( 'vance_tw_content_filters_desc',
					'Find research by topic, type and reading level.' ),
			'cta'      => vance_get_theme_mod( 'vance_twrow_card1_cta', '' )
				?: vance_get_theme_mod( 'vance_tw_content_filters_cta', 'Open' ),
			'accent'   => vance_get_theme_mod( 'vance_twrow_card1_accent', '#008080' ),
			'bg_start' => vance_get_theme_mod( 'vance_twrow_card1_bg_start', '#008080' ),
			'bg_end'   => vance_get_theme_mod( 'vance_twrow_card1_bg_end', '#0A1929' ),
			'image'    => vance_get_theme_mod( 'vance_twrow_card1_image', '' )
				?: vance_get_theme_mod( 'vance_tw_content_filters_image', '' ),
			'icon_svg' => '<rect x="3" y="6" width="18" height="2" fill="currentColor"/><rect x="6" y="11" width="12" height="2" fill="currentColor"/><rect x="9" y="16" width="6" height="2" fill="currentColor"/>',
		),
		array(
			'modal_id' => 'vance-tw-modal-vance-ai',
			'eyebrow'  => vance_get_theme_mod( 'vance_twrow_card2_eyebrow', 'AI assistant' ),
			'title'    => vance_get_theme_mod( 'vance_twrow_card2_title', '' )
				?: vance_get_theme_mod( 'vance_tw_vance_ai_title', 'Vance AI' ),
			'desc'     => vance_get_theme_mod( 'vance_twrow_card2_desc', '' )
				?: vance_get_theme_mod( 'vance_tw_vance_ai_desc',
					'Evidence-backed answers in seconds.' ),
			'cta'      => vance_get_theme_mod( 'vance_twrow_card2_cta', '' )
				?: vance_get_theme_mod( 'vance_tw_vance_ai_cta', 'Open' ),
			'accent'   => vance_get_theme_mod( 'vance_twrow_card2_accent', '#008080' ),
			'bg_start' => vance_get_theme_mod( 'vance_twrow_card2_bg_start', '#008080' ),
			'bg_end'   => vance_get_theme_mod( 'vance_twrow_card2_bg_end', '#0A1929' ),
			'image'    => vance_get_theme_mod( 'vance_twrow_card2_image', '' )
				?: vance_get_theme_mod( 'vance_tw_vance_ai_image', '' ),
			'icon_svg' => '<rect x="3" y="4" width="18" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><line x1="8" y1="20" x2="16" y2="20" stroke="currentColor" stroke-width="2"/><line x1="12" y1="16" x2="12" y2="20" stroke="currentColor" stroke-width="2"/>',
		),
	);
	?>
	<section class="vance-twrow vance-twrow--<?php echo esc_attr( $style ); ?>" style="padding: <?php echo $pad_top; ?>px 0 <?php echo $pad_bot; ?>px; background: <?php echo esc_attr( $sec_bg ); ?>;">
		<div class="container">
			<?php if ( $show_head && $head_text ) : ?>
				<h2 class="vance-twrow__heading" style="margin: 0 0 28px 0; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: <?php echo esc_attr( $head_color ); ?>; text-transform: uppercase; letter-spacing: 1px;"><?php echo esc_html( $head_text ); ?></h2>
			<?php endif; ?>

			<div class="vance-twrow__grid">
				<?php foreach ( $cards as $card ) : ?>
					<?php vance_twrow_render_banner( $card, $style ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<style>
		.vance-twrow__grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 20px;
		}
		.vance-twrow__banner {
			cursor: pointer;
			text-decoration: none;
			position: relative;
			transition: transform 0.2s ease, box-shadow 0.2s ease;
			background: transparent;
			border: 0;
			padding: 0;
			text-align: left;
			width: 100%;
			font-family: inherit;
		}
		.vance-twrow__banner:hover { transform: translateY(-2px); }
		/* Below 768px: horizontal scroll-snap row, two banners visible at 85% width with a peek */
		@media (max-width: 768px) {
			.vance-twrow__grid {
				grid-template-columns: 85% 85%;
				gap: 14px;
				overflow-x: auto;
				scroll-snap-type: x mandatory;
				-webkit-overflow-scrolling: touch;
				padding-bottom: 12px;
				margin: 0 -16px;
				padding-left: 16px;
				padding-right: 16px;
			}
			.vance-twrow__grid::-webkit-scrollbar { display: none; }
			.vance-twrow__grid { scrollbar-width: none; }
			.vance-twrow__banner { scroll-snap-align: start; }
		}
	</style>

	<?php
	// Both modals — same scoped bodies as the standalone widgets.
	vance_tool_widget_modal( 'vance-tw-modal-content-filters', 'Content Filters', 'vance_tw_render_content_filters_body' );
	vance_tool_widget_modal( 'vance-tw-modal-vance-ai',        'Vance AI',        'vance_tw_render_vance_ai_body'        );
}

/**
 * Render a single banner in the chosen style.
 * Modal opening uses data-vance-tw-open which the shared CSS+JS already binds.
 */
function vance_twrow_render_banner( $card, $style ) {
	$title    = $card['title'];
	$desc     = $card['desc'];
	$cta      = $card['cta'];
	$eyebrow  = $card['eyebrow'];
	$accent   = $card['accent'];
	$bg_start = $card['bg_start'];
	$bg_end   = $card['bg_end'];
	$image    = $card['image'];
	$modal_id = $card['modal_id'];
	$svg      = $card['icon_svg'];

	if ( $style === 'image' ) {
		// Option 2 — image-led banner with dark overlay.
		$bg_layers = $image
			? "background-color: " . esc_attr( $bg_end ) . "; background-image: linear-gradient(135deg, rgba(10,25,41,0.55) 0%, rgba(10,25,41,0.92) 100%), url('" . esc_url( $image ) . "'); background-position: center; background-size: cover; background-repeat: no-repeat;"
			: "background: linear-gradient(135deg, " . esc_attr( $bg_start ) . " 0%, " . esc_attr( $bg_end ) . " 100%);";
		?>
		<button type="button" class="vance-twrow__banner vance-twrow__banner--image" data-vance-tw-open="<?php echo esc_attr( $modal_id ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
			<div style="position: relative; padding: 28px 24px; color: white; min-height: 160px; overflow: hidden; <?php echo $bg_layers; ?>">
				<?php if ( $eyebrow ) : ?>
					<div style="display: inline-block; padding: 4px 10px; background: <?php echo esc_attr( $accent ); ?>; opacity: 0.92; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 14px;"><?php echo esc_html( $eyebrow ); ?></div>
				<?php endif; ?>
				<h3 style="margin: 0 0 8px; font-size: 24px; font-weight: 800; color: white; line-height: 1.15; font-family: 'Outfit', sans-serif;"><?php echo esc_html( $title ); ?></h3>
				<p style="margin: 0 0 16px; font-size: 14px; opacity: 0.92; max-width: 320px; line-height: 1.5;"><?php echo esc_html( $desc ); ?></p>
				<span style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.30); font-size: 12px; font-weight: 700; color: white; text-transform: uppercase; letter-spacing: 0.4px;"><?php echo esc_html( $cta ); ?> &rarr;</span>
			</div>
		</button>
		<?php
	} elseif ( $style === 'pill' ) {
		// Option 3 — minimal pill banner with right-side CTA.
		?>
		<button type="button" class="vance-twrow__banner vance-twrow__banner--pill" data-vance-tw-open="<?php echo esc_attr( $modal_id ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
			<!-- 2026-05-26: pill desc now wraps to multiple lines on long copy.
			     Vertical alignment switched from `center` to `flex-start` so
			     icon + content sit at the top when desc spans 2-3 lines.
			     min-height kept (72px) but the row grows when content needs it. -->
			<div style="background: #ffffff; border: 1.5px solid #0A1929; padding: 16px 18px; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; min-height: 72px;">
				<div style="display: flex; align-items: flex-start; gap: 12px; min-width: 0; flex: 1;">
					<svg width="22" height="22" viewBox="0 0 24 24" style="color: <?php echo esc_attr( $accent ); ?>; flex-shrink: 0; margin-top: 2px;" aria-hidden="true"><?php echo $svg; ?></svg>
					<div style="min-width: 0; flex: 1;">
						<div style="font-size: 14px; font-weight: 700; color: #0A1929; font-family: 'Outfit', sans-serif; line-height: 1.3;"><?php echo esc_html( $title ); ?></div>
						<div style="font-size: 12px; color: #64748b; line-height: 1.4; margin-top: 2px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?php echo esc_html( $desc ); ?></div>
					</div>
				</div>
				<span style="background: <?php echo esc_attr( $accent ); ?>; color: white; padding: 8px 14px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; white-space: nowrap; flex-shrink: 0; margin-top: 2px;"><?php echo esc_html( $cta ); ?> &rarr;</span>
			</div>
		</button>
		<?php
	} else {
		// Option 1 (default) — wide horizontal banner with icon left, content right.
		?>
		<button type="button" class="vance-twrow__banner vance-twrow__banner--horizontal" data-vance-tw-open="<?php echo esc_attr( $modal_id ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
			<div style="background: linear-gradient(135deg, <?php echo esc_attr( $bg_start ); ?> 0%, <?php echo esc_attr( $bg_end ); ?> 100%); padding: 24px; color: white; display: flex; align-items: center; gap: 18px; min-height: 140px;">
				<div style="flex-shrink: 0; width: 56px; height: 56px; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.20); display: flex; align-items: center; justify-content: center; color: white;">
					<svg width="26" height="26" viewBox="0 0 24 24" aria-hidden="true"><?php echo $svg; ?></svg>
				</div>
				<div style="flex: 1; min-width: 0;">
					<?php if ( $eyebrow ) : ?>
						<div style="font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; opacity: 0.7; margin-bottom: 4px;"><?php echo esc_html( $eyebrow ); ?></div>
					<?php endif; ?>
					<h3 style="margin: 0 0 4px; font-size: 18px; font-weight: 800; color: white; font-family: 'Outfit', sans-serif;"><?php echo esc_html( $title ); ?></h3>
					<p style="margin: 0 0 10px; font-size: 13px; opacity: 0.88; line-height: 1.4;"><?php echo esc_html( $desc ); ?></p>
					<span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; opacity: 0.95;"><?php echo esc_html( $cta ); ?> &rarr;</span>
				</div>
			</div>
		</button>
		<?php
	}
}

// =========================================================================
// Registry
// =========================================================================
add_filter( 'vance_homepage_sections', function ( $sections ) {
	// New merged section — the primary entry going forward.
	$sections[ 'tool-widgets-row' ] = array(
		'label'  => 'Tool Widgets Row (merged banners)',
		'group'  => 'Tool Widgets',
		'render' => 'vance_render_tool_widgets_row',
	);
	// Old standalone widgets kept for backwards-compat. Hidden from the
	// section-order picker so admins don't pick them by accident going forward.
	$sections[ 'tool-widget-content-filters' ] = array(
		'label'    => 'Tool Widget: Content Filters (legacy single)',
		'group'    => 'Tool Widgets',
		'render'   => 'vance_render_tool_widget_content_filters',
		'hidden'   => true,
	);
	$sections[ 'tool-widget-vance-ai' ] = array(
		'label'    => 'Tool Widget: Vance AI (legacy single)',
		'group'    => 'Tool Widgets',
		'render'   => 'vance_render_tool_widget_vance_ai',
		'hidden'   => true,
	);
	unset( $sections['discovery'] );
	return $sections;
}, 20 );
