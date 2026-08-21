<?php
/**
 * Homepage hero — single slide or carousel.
 *
 * Replaces the body of front-page.php's `case 'hero':`. Slide 1 reads the
 * ORIGINAL, unchanged `vance_hero_*` keys and is always on, so the live site's
 * configured hero becomes "Slide 1" with zero admin re-entry and zero data
 * migration. Slides 2-5 read `vance_hero_slide{n}_*` and each sit behind their
 * own "show" checkbox, default off.
 *
 * With exactly one slide resolved (the default state) this emits the same
 * static markup the old case did — no track, no dots, no arrows, no JS. The
 * carousel chrome only appears once a second slide is enabled.
 *
 * WP's Customizer has no repeater control and this theme has never built one;
 * the established idiom for "N independently-configurable instances" is
 * inc/content-widget.php's fixed pre-registered slots, which this follows.
 *
 * @package vance-health-hub
 * @since   2026-08-21
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * How many hero slides to pre-register. Slide 1 is always on; 2..N are opt-in.
 * Keep in sync with the Customizer registration loop in functions.php.
 */
if ( ! defined( 'VANCE_HERO_SLIDE_INSTANCES' ) ) {
	define( 'VANCE_HERO_SLIDE_INSTANCES', 5 );
}

/**
 * The per-slide field defaults. Slide 1's live values come from the legacy
 * key names; slides 2+ use `vance_hero_slide{n}_<field>` and fall back to
 * these. Keeping the list in one place means the Customizer registration loop
 * and the renderer can never drift apart.
 *
 * @return array<string, mixed> field => default
 */
function vance_hero_slide_field_defaults() {
	return array(
		'image'                 => '',
		'tag_label'             => 'HEALTHCARE KNOWLEDGE HUB',
		'tag_bg'                => '#ffffff',
		'tag_color'             => '#008080',
		'tag_border'            => '#008080',
		'title'                 => 'Your Partner in <span class="highlight">Lifelong Wellness</span>',
		'subtitle'              => 'Trusted, science-backed information to help you understand your health, manage your IBD condition, and live your best life through clinical nutrition.',
		'subtitle_color'        => '#cbd5e1',
		'bg_color'              => '#0A1929',
		'title_size'            => 52,
		'title_color'           => '#ffffff',
		'mask_toggle'           => true,
		'mask_opacity_pct'      => 50,
		'btn1_text'             => "I'm a Practitioner",
		'btn1_link'             => '/healthcare-professionals/',
		'btn1_text_color'       => '#ffffff',
		'btn1_bg_color'         => '#008080',
		'btn1_border_color'     => '#008080',
		'btn1_hover_text_color' => '#ffffff',
		'btn1_hover_bg_color'   => '#006666',
		'btn2_text'             => "I'm a Patient",
		'btn2_link'             => '/patients/',
		'btn2_text_color'       => '#ffffff',
		'btn2_bg_color'         => '',
		'btn2_border_color'     => '#ffffff',
		'btn2_hover_text_color' => '#0A1929',
		'btn2_hover_bg_color'   => '#ffffff',
		// Hide this slide's two CTA buttons entirely (image + text only).
		'hide_buttons'          => false,
	);
}

/**
 * Resolve slide 1 from the original hero keys. Nothing here is new — this is
 * the same set of vance_get_theme_mod() reads the old `case 'hero':` did,
 * gathered into an array.
 */
function vance_hero_slide_1_values() {
	$d = vance_hero_slide_field_defaults();

	$image = vance_get_theme_mod( 'vance_homepage_hero_image' );
	if ( ! $image ) {
		$image = get_template_directory_uri() . '/assets/img/news_hero.png';
	}

	// Per-page slider (0-100) takes precedence over the legacy global
	// mask_opacity (0.0-1.0) when set — preserved exactly as it was.
	$home_overlay_pct = vance_get_theme_mod( 'vance_home_hero_overlay', null );
	if ( $home_overlay_pct !== null && $home_overlay_pct !== '' ) {
		$mask_pct = max( 0, min( 100, absint( $home_overlay_pct ) ) );
	} else {
		$mask_pct = (int) round( ( (float) vance_get_theme_mod( 'vance_hero_mask_opacity', 0.5 ) ) * 100 );
	}

	return array(
		'image'                 => $image,
		'tag_label'             => vance_get_theme_mod( 'vance_hero_tag_label',  $d['tag_label'] ),
		'tag_bg'                => vance_get_theme_mod( 'vance_hero_tag_bg',     $d['tag_bg'] ),
		'tag_color'             => vance_get_theme_mod( 'vance_hero_tag_color',  '#f86409' ),
		'tag_border'            => vance_get_theme_mod( 'vance_hero_tag_border', '#f86409' ),
		'title'                 => vance_get_theme_mod( 'vance_hero_custom_title',    $d['title'] ),
		'subtitle'              => vance_get_theme_mod( 'vance_hero_custom_subtitle', $d['subtitle'] ),
		'subtitle_color'        => vance_get_theme_mod( 'vance_hero_subtitle_color',  $d['subtitle_color'] ),
		'bg_color'              => vance_get_theme_mod( 'vance_hero_bg_color',        $d['bg_color'] ),
		'title_size'            => vance_get_theme_mod( 'vance_hero_title_size',      $d['title_size'] ),
		'title_color'           => vance_get_theme_mod( 'vance_hero_title_color',     $d['title_color'] ),
		'mask_toggle'           => vance_get_theme_mod( 'vance_hero_mask_toggle',     $d['mask_toggle'] ),
		'mask_opacity_pct'      => $mask_pct,
		'btn1_text'             => vance_get_theme_mod( 'vance_hero_button_1_text', $d['btn1_text'] ),
		'btn1_link'             => vance_get_theme_mod( 'vance_hero_button_1_link', $d['btn1_link'] ),
		'btn1_text_color'       => vance_get_theme_mod( 'vance_hero_btn1_text_color',       $d['btn1_text_color'] ),
		'btn1_bg_color'         => vance_get_theme_mod( 'vance_hero_btn1_bg_color',         $d['btn1_bg_color'] ),
		'btn1_border_color'     => vance_get_theme_mod( 'vance_hero_btn1_border_color',     $d['btn1_border_color'] ),
		'btn1_hover_text_color' => vance_get_theme_mod( 'vance_hero_btn1_hover_text_color', $d['btn1_hover_text_color'] ),
		'btn1_hover_bg_color'   => vance_get_theme_mod( 'vance_hero_btn1_hover_bg_color',   $d['btn1_hover_bg_color'] ),
		'btn2_text'             => vance_get_theme_mod( 'vance_hero_button_2_text', $d['btn2_text'] ),
		'btn2_link'             => vance_get_theme_mod( 'vance_hero_button_2_link', $d['btn2_link'] ),
		'btn2_text_color'       => vance_get_theme_mod( 'vance_hero_btn2_text_color',       $d['btn2_text_color'] ),
		'btn2_bg_color'         => vance_get_theme_mod( 'vance_hero_btn2_bg_color',         $d['btn2_bg_color'] ),
		'btn2_border_color'     => vance_get_theme_mod( 'vance_hero_btn2_border_color',     $d['btn2_border_color'] ),
		'btn2_hover_text_color' => vance_get_theme_mod( 'vance_hero_btn2_hover_text_color', $d['btn2_hover_text_color'] ),
		'btn2_hover_bg_color'   => vance_get_theme_mod( 'vance_hero_btn2_hover_bg_color',   $d['btn2_hover_bg_color'] ),
		// Slide 1's own two toggles. These are NEW keys (there is no legacy
		// equivalent), so they sit outside the vance_hero_* group above.
		'hide_buttons'          => vance_get_theme_mod( 'vance_hero_slide1_hide_buttons', false ),
	);
}

/**
 * Resolve slide N (N >= 2) from its own `vance_hero_slide{n}_*` keys.
 */
function vance_hero_slide_n_values( $n ) {
	$n      = absint( $n );
	$prefix = 'vance_hero_slide' . $n . '_';
	$vals   = array();
	foreach ( vance_hero_slide_field_defaults() as $field => $default ) {
		$vals[ $field ] = vance_get_theme_mod( $prefix . $field, $default );
	}
	if ( ! $vals['image'] ) {
		$vals['image'] = get_template_directory_uri() . '/assets/img/news_hero.png';
	}
	return $vals;
}

/**
 * Every slide that should render, in slide-number order: slide 1 unless it has
 * been explicitly switched off, plus any of 2..N whose "show" box is ticked.
 *
 * Slide 1 defaults to ON, so an untouched site is unaffected. Switching it off
 * with no other slide enabled resolves to zero slides and the hero section is
 * omitted entirely — which is a legitimate thing to want.
 */
function vance_hero_resolved_slides() {
	$slides = array();
	if ( vance_get_theme_mod( 'vance_hero_slide1_show', true ) ) {
		$slides[] = vance_hero_slide_1_values();
	}
	for ( $n = 2; $n <= VANCE_HERO_SLIDE_INSTANCES; $n++ ) {
		if ( vance_get_theme_mod( 'vance_hero_slide' . $n . '_show', false ) ) {
			$slides[] = vance_hero_slide_n_values( $n );
		}
	}
	return $slides;
}

/**
 * Build the `style` attribute value for one slide's <section>/<div>.
 */
function vance_hero_slide_bg_style( array $s ) {
	$bg_color = esc_attr( $s['bg_color'] );
	$img      = esc_url( $s['image'] );
	if ( ! empty( $s['mask_toggle'] ) ) {
		$alpha1 = max( 0, min( 100, absint( $s['mask_opacity_pct'] ) ) ) / 100;
		$alpha2 = min( 1, $alpha1 + 0.15 );
		return "background-color: {$bg_color}; background: linear-gradient(rgba(10, 25, 41, {$alpha1}), rgba(10, 25, 41, {$alpha2})), url('{$img}') no-repeat center center; background-size: cover;";
	}
	return "background-color: {$bg_color}; background: url('{$img}') no-repeat center center; background-size: cover;";
}

/**
 * Emit one slide's inner content block (eyebrow, title, subtitle, buttons).
 * Shared by the single-slide and carousel paths so they can never diverge.
 *
 * @param array $s     Resolved slide values.
 * @param int   $index Zero-based slide index (used for per-slide button classes).
 */
function vance_hero_render_slide_content( array $s, $index ) {
	$idx  = (int) $index;
	$b1   = 'vance-hero-btn-1 vance-hero-s' . $idx . '-b1';
	$b2   = 'vance-hero-btn-2 vance-hero-s' . $idx . '-b2';
	// A link containing "quiz" opens the quiz modal instead of navigating.
	// Derived from the link value itself, so it keeps working per-slide with
	// no extra field.
	$btn1_onclick = ( strpos( (string) $s['btn1_link'], 'quiz' ) !== false ) ? 'onclick="event.preventDefault(); openQuizModal();"' : '';
	$btn2_onclick = ( strpos( (string) $s['btn2_link'], 'quiz' ) !== false ) ? 'onclick="event.preventDefault(); openQuizModal();"' : '';
	$btn2_bg_decl = $s['btn2_bg_color'] ? 'background: ' . esc_attr( $s['btn2_bg_color'] ) . ';' : 'background: transparent;';

	// Ensure highlight spans inherit the customized colour if present.
	$title_display = wp_kses_post( $s['title'] );
	if ( strpos( $title_display, 'class="highlight"' ) !== false ) {
		$title_display = str_replace( 'class="highlight"', 'class="highlight" style="color: inherit;"', $title_display );
	}
	?>
	<div style="max-width: 800px;">
		<?php if ( $s['tag_label'] !== '' ) : ?>
		<span class="tag-label" style="background: <?php echo esc_attr( $s['tag_bg'] ); ?>; color: <?php echo esc_attr( $s['tag_color'] ); ?>; border: 1.5px solid <?php echo esc_attr( $s['tag_border'] ); ?>;"><?php echo esc_html( $s['tag_label'] ); ?></span>
		<?php endif; ?>
		<h1 style="font-size: <?php echo esc_attr( $s['title_size'] ); ?>px; color: <?php echo esc_attr( $s['title_color'] ); ?>; line-height: 1.1; margin: 16px 0 10px; font-weight: 800; font-family: 'Outfit', sans-serif;">
			<?php echo $title_display; // phpcs:ignore WordPress.Security.EscapeOutput — wp_kses_post above ?>
		</h1>
		<p style="font-size: 20px; line-height: 1.6; color: <?php echo esc_attr( $s['subtitle_color'] ); ?>; margin: 0 0 32px; max-width: 600px;">
			<?php echo esc_html( $s['subtitle'] ); ?>
		</p>
		<?php if ( empty( $s['hide_buttons'] ) ) : ?>
		<div class="hero-actions" style="display: flex; gap: 16px; flex-wrap: wrap;">
			<a href="<?php echo esc_url( $s['btn1_link'] ); ?>" <?php echo $btn1_onclick; // phpcs:ignore ?> class="btn btn-primary <?php echo esc_attr( $b1 ); ?>" style="background: <?php echo esc_attr( $s['btn1_bg_color'] ); ?>; color: <?php echo esc_attr( $s['btn1_text_color'] ); ?>; border: 2px solid <?php echo esc_attr( $s['btn1_border_color'] ); ?>; padding: 14px 28px; border-radius: 0; font-weight: 700; text-decoration: none; transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;"><?php echo esc_html( $s['btn1_text'] ); ?></a>
			<a href="<?php echo esc_url( $s['btn2_link'] ); ?>" <?php echo $btn2_onclick; // phpcs:ignore ?> class="btn btn-outline <?php echo esc_attr( $b2 ); ?>" style="<?php echo $btn2_bg_decl; // phpcs:ignore ?> color: <?php echo esc_attr( $s['btn2_text_color'] ); ?>; border: 2px solid <?php echo esc_attr( $s['btn2_border_color'] ); ?>; padding: 14px 28px; border-radius: 0; font-weight: 700; text-decoration: none; transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;"><?php echo esc_html( $s['btn2_text'] ); ?></a>
		</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Emit the per-slide button hover rules. One rule pair per slide, keyed on the
 * per-slide button classes so each slide keeps its own hover colours.
 */
function vance_hero_render_button_hover_css( array $slides ) {
	?>
	<style>
		<?php foreach ( $slides as $i => $s ) : ?>
		.hero .vance-hero-s<?php echo (int) $i; ?>-b1:hover { background: <?php echo esc_attr( $s['btn1_hover_bg_color'] ); ?> !important; color: <?php echo esc_attr( $s['btn1_hover_text_color'] ); ?> !important; border-color: <?php echo esc_attr( $s['btn1_hover_bg_color'] ); ?> !important; }
		.hero .vance-hero-s<?php echo (int) $i; ?>-b2:hover { background: <?php echo esc_attr( $s['btn2_hover_bg_color'] ); ?> !important; color: <?php echo esc_attr( $s['btn2_hover_text_color'] ); ?> !important; border-color: <?php echo esc_attr( $s['btn2_hover_bg_color'] ); ?> !important; }
		<?php endforeach; ?>
	</style>
	<?php
}

/**
 * Render the homepage hero: a single static section, or a carousel once two or
 * more slides are enabled.
 */
function vance_render_hero_carousel() {
	$slides = vance_hero_resolved_slides();
	if ( empty( $slides ) ) { return; }

	vance_hero_render_button_hover_css( $slides );

	// ---- Single slide: the historical static markup, unchanged -------------
	if ( count( $slides ) === 1 ) {
		$s = $slides[0];
		?>
	<!-- Hero Section (Patient Style Structure) -->
	<section class="hero patient-hero" style="display: flex; align-items: flex-start; <?php echo vance_hero_slide_bg_style( $s ); // phpcs:ignore ?> color: white; position: relative; overflow: hidden;">
		<?php /* width:100% + flex:1 1 auto — .container is `margin: 0 auto`, so as a
		         flex item it shrinks to its content and self-centres, drifting
		         horizontally with the title length. Forcing it to fill the row
		         pins the text block to the container gutter, matching
		         page-contact-us.php (see the same fix at main.css:507). */ ?>
		<div class="container" style="position:relative;z-index:1;width:100%;flex:1 1 auto;">
			<?php vance_hero_render_slide_content( $s, 0 ); ?>
		</div>
	</section>
		<?php
		return;
	}

	// ---- Two or more slides: carousel --------------------------------------
	$count    = count( $slides );
	$autoplay = (bool) vance_get_theme_mod( 'vance_hero_autoplay_enable', false );
	$interval = max( 2, absint( vance_get_theme_mod( 'vance_hero_autoplay_interval', 6 ) ) );
	?>
	<style>
		.vance-hero-carousel { position: relative; overflow: hidden; }
		.vance-hero-carousel .vance-hero-track {
			display: flex;
			width: 100%;
			/* transform is the animated property, matching the technique the
			   testimonials carousel already uses. */
			transition: transform 0.5s ease;
			will-change: transform;
		}
		.vance-hero-carousel .vance-hero-slide {
			flex: 0 0 100%;
			min-width: 100%;
			display: flex;
			align-items: flex-start;
			color: white;
			position: relative;
			overflow: hidden;
		}
		/* Same container fix as the single-slide path: .container is
		   `margin: 0 auto`, so left unchecked it shrinks to content and
		   self-centres inside the flex row. */
		.vance-hero-carousel .vance-hero-slide > .container { position: relative; z-index: 1; width: 100%; flex: 1 1 auto; }
		.vance-hero-carousel .vance-hero-nav {
			position: absolute;
			top: 50%;
			transform: translateY(-50%);
			z-index: 5;
			width: 44px;
			height: 44px;
			display: flex;
			align-items: center;
			justify-content: center;
			background: rgba(255,255,255,0.12);
			border: 1px solid rgba(255,255,255,0.35);
			color: #ffffff;
			font-size: 20px;
			line-height: 1;
			cursor: pointer;
			padding: 0;
			transition: background 0.2s ease, border-color 0.2s ease;
		}
		.vance-hero-carousel .vance-hero-nav:hover,
		.vance-hero-carousel .vance-hero-nav:focus-visible { background: rgba(255,255,255,0.28); border-color: #ffffff; outline: none; }
		.vance-hero-carousel .vance-hero-prev { left: 16px; }
		.vance-hero-carousel .vance-hero-next { right: 16px; }
		.vance-hero-carousel .vance-hero-dots {
			position: absolute;
			bottom: 20px;
			left: 0;
			right: 0;
			z-index: 5;
			display: flex;
			justify-content: center;
			gap: 10px;
		}
		.vance-hero-carousel .vance-hero-dot {
			width: 10px;
			height: 10px;
			padding: 0;
			border: 1px solid rgba(255,255,255,0.7);
			background: transparent;
			cursor: pointer;
			transition: background 0.2s ease;
		}
		.vance-hero-carousel .vance-hero-dot[aria-current="true"] { background: #ffffff; }
		@media (max-width: 768px) {
			.vance-hero-carousel .vance-hero-nav { width: 36px; height: 36px; font-size: 16px; }
			.vance-hero-carousel .vance-hero-prev { left: 6px; }
			.vance-hero-carousel .vance-hero-next { right: 6px; }
		}
		@media (prefers-reduced-motion: reduce) {
			.vance-hero-carousel .vance-hero-track { transition: none; }
		}
	</style>
	<!-- Hero Section (carousel — <?php echo (int) $count; ?> slides) -->
	<div class="hero patient-hero vance-hero-carousel"
	     data-vance-hero-carousel
	     data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
	     data-interval="<?php echo (int) $interval; ?>"
	     role="region"
	     aria-roledescription="carousel"
	     aria-label="<?php esc_attr_e( 'Homepage highlights', 'vance-health-hub' ); ?>"
	     tabindex="0"
	     style="padding: 0; min-height: 0;">
		<div class="vance-hero-track">
			<?php foreach ( $slides as $i => $s ) : ?>
			<div class="vance-hero-slide hero"
			     role="group"
			     aria-roledescription="slide"
			     aria-label="<?php echo esc_attr( sprintf( __( 'Slide %1$d of %2$d', 'vance-health-hub' ), $i + 1, $count ) ); ?>"
			     <?php echo $i > 0 ? 'aria-hidden="true"' : ''; ?>
			     style="<?php echo vance_hero_slide_bg_style( $s ); // phpcs:ignore ?>">
				<div class="container">
					<?php vance_hero_render_slide_content( $s, $i ); ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<button type="button" class="vance-hero-nav vance-hero-prev" aria-label="<?php esc_attr_e( 'Previous slide', 'vance-health-hub' ); ?>">&#8249;</button>
		<button type="button" class="vance-hero-nav vance-hero-next" aria-label="<?php esc_attr_e( 'Next slide', 'vance-health-hub' ); ?>">&#8250;</button>

		<div class="vance-hero-dots" role="group" aria-label="<?php esc_attr_e( 'Choose slide', 'vance-health-hub' ); ?>">
			<?php for ( $i = 0; $i < $count; $i++ ) : ?>
			<button type="button" class="vance-hero-dot"
			        data-slide="<?php echo (int) $i; ?>"
			        aria-current="<?php echo $i === 0 ? 'true' : 'false'; ?>"
			        aria-label="<?php echo esc_attr( sprintf( __( 'Go to slide %d', 'vance-health-hub' ), $i + 1 ) ); ?>"></button>
			<?php endfor; ?>
		</div>
	</div>
	<script>
	(function () {
		'use strict';
		var root = document.querySelector('[data-vance-hero-carousel]');
		if (!root) { return; }

		var track  = root.querySelector('.vance-hero-track');
		var slides = root.querySelectorAll('.vance-hero-slide');
		var dots   = root.querySelectorAll('.vance-hero-dot');
		var total  = slides.length;
		if (total < 2 || !track) { return; }

		// prefers-reduced-motion disables autoplay AND the slide transition
		// entirely — the same handling page-gi-health.php's reveal script uses.
		var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		var index = 0;
		var timer = null;

		function go(next) {
			index = ((next % total) + total) % total;
			track.style.transform = 'translateX(' + (-100 * index) + '%)';
			for (var i = 0; i < total; i++) {
				slides[i].setAttribute('aria-hidden', i === index ? 'false' : 'true');
				if (dots[i]) { dots[i].setAttribute('aria-current', i === index ? 'true' : 'false'); }
			}
		}

		root.querySelector('.vance-hero-prev').addEventListener('click', function () { stop(); go(index - 1); });
		root.querySelector('.vance-hero-next').addEventListener('click', function () { stop(); go(index + 1); });
		Array.prototype.forEach.call(dots, function (dot) {
			dot.addEventListener('click', function () { stop(); go(parseInt(dot.getAttribute('data-slide'), 10) || 0); });
		});

		root.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft')  { stop(); go(index - 1); }
			if (e.key === 'ArrowRight') { stop(); go(index + 1); }
		});

		function start() {
			if (timer || reduceMotion) { return; }
			if (root.getAttribute('data-autoplay') !== '1') { return; }
			var secs = parseInt(root.getAttribute('data-interval'), 10) || 6;
			timer = window.setInterval(function () { go(index + 1); }, secs * 1000);
		}
		function stop() {
			if (timer) { window.clearInterval(timer); timer = null; }
		}

		// Pause on hover and on keyboard focus; resume when the pointer/focus leaves.
		root.addEventListener('mouseenter', stop);
		root.addEventListener('mouseleave', start);
		root.addEventListener('focusin', stop);
		root.addEventListener('focusout', function (e) {
			if (!root.contains(e.relatedTarget)) { start(); }
		});

		go(0);
		start();
	})();
	</script>
	<?php
}
