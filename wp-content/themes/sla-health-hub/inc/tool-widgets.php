<?php
/**
 * Tool Widgets — homepage cards that open a tool in a modal.
 *
 * Replaces the old monolithic 'discovery' homepage block (chip filters +
 * Ask AI input + reading-level toggles) with two focused tool widget cards:
 *
 *   - 'tool-widget-content-filters'  -> opens the Content Filters tool in a
 *                                       modal (iframe to /discovery/ by default)
 *   - 'tool-widget-vance-ai'         -> opens the Vance AI tool in a modal
 *                                       (iframe to /ask-ai/ by default)
 *
 * Each card has an image + title + description + button. The Section Order
 * Customizer control treats these like any other sections — drag to reorder,
 * tick checkboxes to show.
 *
 * Modal infrastructure (CSS + JS + HTML wrapper) is emitted once per page
 * via a static guard, regardless of how many tool widgets are enabled.
 *
 * MIGRATION: front-page.php's section-order parser substitutes 'discovery'
 * with these two widgets in saved orders, so the visual intent is preserved
 * for existing installs without admin re-configuration.
 *
 * @package sla-health-hub
 * @since   2026-05-25
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Emit the shared modal infrastructure (HTML + CSS + JS) exactly once per
 * page, regardless of how many tool widgets are enabled.
 */
function vance_tool_widgets_emit_modal_once() {
	static $emitted = false;
	if ( $emitted ) { return; }
	$emitted = true;
	?>
	<style>
		.vance-tool-modal {
			position: fixed; inset: 0;
			z-index: 99999;
			display: none;
			align-items: center; justify-content: center;
			background: rgba(10, 25, 41, 0.78);
			opacity: 0;
			transition: opacity 0.25s ease;
		}
		.vance-tool-modal.is-open {
			display: flex;
			opacity: 1;
		}
		.vance-tool-modal__panel {
			position: relative;
			width: min(1100px, 96vw);
			height: min(82vh, 800px);
			background: #ffffff;
			border-radius: 0;
			box-shadow: 0 40px 80px rgba(0, 0, 0, 0.40);
			overflow: hidden;
			display: flex; flex-direction: column;
		}
		.vance-tool-modal__header {
			display: flex; align-items: center; justify-content: space-between;
			padding: 14px 22px;
			background: #0A1929; color: #ffffff;
			border-bottom: 1px solid rgba(255,255,255,0.10);
		}
		.vance-tool-modal__title {
			margin: 0;
			font-size: 16px; font-weight: 700;
			font-family: 'Outfit', sans-serif;
			letter-spacing: 0.4px;
			text-transform: uppercase;
		}
		.vance-tool-modal__close {
			background: transparent; border: none;
			color: #ffffff; opacity: 0.85;
			font-size: 22px; line-height: 1;
			cursor: pointer; padding: 4px 10px;
			border-radius: 0;
			transition: opacity 0.2s, background 0.2s;
		}
		.vance-tool-modal__close:hover { opacity: 1; background: rgba(255,255,255,0.10); }
		.vance-tool-modal__body { flex: 1; min-height: 0; }
		.vance-tool-modal__iframe {
			width: 100%; height: 100%;
			border: 0;
			background: #ffffff;
		}
	</style>
	<div id="vance-tool-modal" class="vance-tool-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="vance-tool-modal-title">
		<div class="vance-tool-modal__panel">
			<div class="vance-tool-modal__header">
				<h2 id="vance-tool-modal-title" class="vance-tool-modal__title">Tool</h2>
				<button type="button" class="vance-tool-modal__close" aria-label="Close" data-vance-tool-modal-close>&times;</button>
			</div>
			<div class="vance-tool-modal__body">
				<iframe class="vance-tool-modal__iframe" src="about:blank" title="Vance tool" referrerpolicy="same-origin"></iframe>
			</div>
		</div>
	</div>
	<script>
	(function () {
		'use strict';
		var modal  = document.getElementById('vance-tool-modal');
		if (!modal) return;
		var iframe = modal.querySelector('.vance-tool-modal__iframe');
		var title  = modal.querySelector('.vance-tool-modal__title');
		var body   = document.body;

		function open(url, label) {
			if (!url) return;
			iframe.setAttribute('src', url);
			if (label) { title.textContent = label; }
			modal.classList.add('is-open');
			modal.setAttribute('aria-hidden', 'false');
			body.style.overflow = 'hidden';
		}
		function close() {
			modal.classList.remove('is-open');
			modal.setAttribute('aria-hidden', 'true');
			body.style.overflow = '';
			// Drop the iframe content so the tool stops running in the background.
			window.setTimeout(function () { iframe.setAttribute('src', 'about:blank'); }, 280);
		}

		// Open from any element with data-vance-tool-modal-open="<url>"
		document.addEventListener('click', function (e) {
			var trigger = e.target.closest('[data-vance-tool-modal-open]');
			if (trigger) {
				e.preventDefault();
				open(trigger.getAttribute('data-vance-tool-modal-open'),
				     trigger.getAttribute('data-vance-tool-modal-title') || 'Tool');
				return;
			}
			if (e.target.closest('[data-vance-tool-modal-close]')) {
				e.preventDefault();
				close();
				return;
			}
			// Click on backdrop (not panel)
			if (e.target === modal) { close(); }
		});

		// ESC key closes
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && modal.classList.contains('is-open')) { close(); }
		});

		// Expose globally for programmatic open if other code wants it.
		window.vanceToolModal = { open: open, close: close };
	})();
	</script>
	<?php
}

/**
 * Generic tool-widget card renderer. Both Content Filters and Vance AI use
 * the same card shell; only their text, image, modal URL and accent colour
 * differ. Driven entirely by Customizer values, with sensible defaults.
 *
 * @param string $key Widget identifier — 'content_filters' or 'vance_ai'.
 * @param array  $defaults Fallback values for title/desc/cta/url/etc.
 */
function vance_render_tool_widget( $key, $defaults ) {
	$prefix = 'vance_tw_' . $key . '_';

	$title    = vance_get_theme_mod( $prefix . 'title',     $defaults['title'] );
	$desc     = vance_get_theme_mod( $prefix . 'desc',      $defaults['desc'] );
	$cta      = vance_get_theme_mod( $prefix . 'cta',       $defaults['cta'] );
	$image    = vance_get_theme_mod( $prefix . 'image',     '' );
	$url      = vance_get_theme_mod( $prefix . 'url',       $defaults['url'] );
	$accent   = vance_get_theme_mod( $prefix . 'accent',    $defaults['accent'] );
	$bg_color = vance_get_theme_mod( $prefix . 'bg_color',  '#ffffff' );
	$title_c  = vance_get_theme_mod( $prefix . 'title_color', '#0F172A' );
	$desc_c   = vance_get_theme_mod( $prefix . 'desc_color',  '#64748b' );

	vance_tool_widgets_emit_modal_once();

	$card_id = 'vance-tw-' . sanitize_html_class( $key );
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
						<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.5" aria-hidden="true">
							<?php echo $defaults['icon_svg']; ?>
						</svg>
					<?php endif; ?>
				</div>
				<div class="vance-tool-widget__body" style="flex: 1; padding: 32px 36px; display: flex; flex-direction: column; justify-content: center;">
					<h2 style="font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; color: <?php echo esc_attr( $title_c ); ?>; margin: 0 0 12px 0;"><?php echo esc_html( $title ); ?></h2>
					<p style="font-size: 15px; line-height: 1.6; color: <?php echo esc_attr( $desc_c ); ?>; margin: 0 0 20px 0; max-width: 560px;"><?php echo esc_html( $desc ); ?></p>
					<div>
						<button
							type="button"
							class="btn btn-primary"
							style="background: <?php echo esc_attr( $accent ); ?>; border-color: <?php echo esc_attr( $accent ); ?>;"
							data-vance-tool-modal-open="<?php echo esc_url( $url ); ?>"
							data-vance-tool-modal-title="<?php echo esc_attr( $title ); ?>"
						><?php echo esc_html( $cta ); ?></button>
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
 * Defaults for the two pre-shipped tool widgets. Each defaults entry feeds
 * vance_render_tool_widget() above. Modal target URLs default to the
 * existing in-theme tool pages so the widget works out of the box.
 */
function vance_tool_widget_defaults( $key ) {
	$defaults = array(
		'content_filters' => array(
			'title'  => 'Content Filters',
			'desc'   => 'Filter the knowledge base by reading level, pathway, content type and keywords — find exactly the article, study, or guide you need in seconds.',
			'cta'    => 'Open Filters',
			'url'    => home_url( '/discovery/' ),
			'accent' => '#008080',
			'icon_svg' => '<rect x="3" y="6" width="18" height="2"/><rect x="6" y="11" width="12" height="2"/><rect x="9" y="16" width="6" height="2"/>',
		),
		'vance_ai' => array(
			'title'  => 'Vance AI',
			'desc'   => 'Ask any gastro health question and get an evidence-backed answer in seconds. Powered by curated clinical content — available 24/7.',
			'cta'    => 'Ask Vance AI',
			'url'    => home_url( '/ask-ai/' ),
			'accent' => '#0EA5E9',
			'icon_svg' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
		),
	);
	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : array();
}

function vance_render_tool_widget_content_filters() {
	vance_render_tool_widget( 'content_filters', vance_tool_widget_defaults( 'content_filters' ) );
}

function vance_render_tool_widget_vance_ai() {
	vance_render_tool_widget( 'vance_ai', vance_tool_widget_defaults( 'vance_ai' ) );
}

// ============================================================================
// Registry — add the two tool widgets to the homepage section list AND drop
// the legacy 'discovery' option so it no longer appears in the Section Order
// Customizer control (saved values still render via case 'discovery': as a
// fallback for admins who explicitly want the combined block — see the
// migration in front-page.php's section-order parser).
// ============================================================================

add_filter( 'vance_homepage_sections', function ( $sections ) {
	$sections[ 'tool-widget-content-filters' ] = array(
		'label'  => 'Tool Widget: Content Filters (modal)',
		'group'  => 'Tool Widgets',
		'render' => 'vance_render_tool_widget_content_filters',
	);
	$sections[ 'tool-widget-vance-ai' ] = array(
		'label'  => 'Tool Widget: Vance AI (modal)',
		'group'  => 'Tool Widgets',
		'render' => 'vance_render_tool_widget_vance_ai',
	);
	// Hide the legacy combined Discovery block from the new Section Order
	// control so admins are steered toward the two-widget split. The
	// underlying case 'discovery': in front-page.php still renders if
	// somehow an admin manually edits the saved string to include it.
	unset( $sections['discovery'] );
	return $sections;
}, 20 ); // priority 20 so we run AFTER cross-page-sections.php's filter
