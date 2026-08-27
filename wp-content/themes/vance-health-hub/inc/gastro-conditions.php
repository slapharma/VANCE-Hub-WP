<?php
/**
 * Gastro Conditions — homepage section of big animated condition tiles.
 *
 * One tile per GI condition linking to its page under
 * /gastro-health-explained/, plus a final "view all" tile linking to the hub
 * page itself.
 *
 * The condition list is NOT duplicated here: it comes from
 * vance_gi_condition_cards() in functions.php, which page-gi-health.php also
 * renders from, so the two can never drift apart. URLs come from
 * vance_gi_page_url() / vance_gi_hub_url() for the same reason.
 *
 * Registry-driven: visibility is controlled by adding `gastro-conditions` to
 * Appearance → Customize → Homepage → Section Order.
 *
 * @package vance-health-hub
 * @since   2026-08-21
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Render the Gastro Conditions tile grid.
 */
function vance_render_gastro_conditions() {
	if ( ! function_exists( 'vance_gi_condition_cards' ) ) { return; }

	$cards = vance_gi_condition_cards();
	if ( empty( $cards ) ) { return; }

	$heading      = vance_get_theme_mod( 'vance_gc_heading',  'Gastro Conditions' );
	$subtitle     = vance_get_theme_mod( 'vance_gc_subtitle', 'Learn about the condition that matters to you' );
	// Printed into CSS below, so run through the keyword whitelist rather than
	// trusting the stored value — see vance_sanitize_text_align() in functions.php.
	$head_align   = vance_sanitize_text_align( vance_get_theme_mod( 'vance_gc_heading_align',  'center' ) );
	$sub_align    = vance_sanitize_text_align( vance_get_theme_mod( 'vance_gc_subtitle_align', 'center' ) );
	$section_bg   = vance_get_theme_mod( 'vance_gc_section_bg', '#f8fafc' );
	$per_row      = max( 1, min( 6, absint( vance_get_theme_mod( 'vance_gc_per_row', 4 ) ) ) );
	$view_all_bg  = vance_get_theme_mod( 'vance_gc_view_all_bg_color', '#008080' );
	$view_all_txt = vance_get_theme_mod( 'vance_gc_view_all_text', 'VIEW ALL GASTRO CONDITIONS' );

	$tmpl = get_template_directory_uri();
	$dir  = get_template_directory();
	?>
	<style>
		#vance-gastro-conditions {
			background: <?php echo esc_attr( $section_bg ); ?>;
			/* Top padding trimmed 50px off the standard 80px section rhythm —
			   this block follows another padded section on the homepage, so the
			   full 80px read as a gap rather than as breathing room. */
			padding: 30px 0 80px;
		}
		/* Alignment moved off the wrapper and onto the two children so the
		   heading and subtitle can be set independently (Customizer →
		   Homepage → Gastro Conditions). */
		#vance-gastro-conditions .vance-gc-header { margin-bottom: 44px; }
		#vance-gastro-conditions .vance-gc-header h2 {
			text-align: <?php echo esc_attr( $head_align ); ?>;
			font-family: 'Outfit', sans-serif;
			font-size: 38px;
			font-weight: 800;
			color: #0A1929;
			margin: 0;
			line-height: 1.15;
		}
		#vance-gastro-conditions .vance-gc-header p {
			text-align: <?php echo esc_attr( $sub_align ); ?>;
			font-size: 17px;
			color: #64748b;
			margin: 12px 0 0;
		}
		#vance-gastro-conditions .vance-gc-grid {
			display: grid;
			grid-template-columns: repeat(<?php echo (int) $per_row; ?>, minmax(0, 1fr));
			gap: 24px;
		}
		/* Each tile is a big photographic button: image fills it, a dark scrim
		   keeps the title legible, and the whole thing lifts on hover. */
		#vance-gastro-conditions .vance-gc-tile {
			position: relative;
			display: flex;
			align-items: flex-end;
			min-height: 220px;
			padding: 22px;
			overflow: hidden;
			text-decoration: none;
			background-color: #0A1929;
			background-size: cover;
			background-position: center center;
			/* Both declarations are load-bearing against the same bug. The
			   default `background-repeat: repeat` plus the default
			   `background-origin: padding-box` tiled the image's BOTTOM row of
			   pixels into the 1px border ring at the TOP of every tile -- the
			   background is clipped to the border box but positioned from the
			   padding box, so that ring fell outside the image's own area. The
			   scrim below is `inset: 0`, i.e. the padding box, so it never
			   covered the ring either: each tile wore a hairline of unrelated,
			   unscrimmed colour along its top edge. */
			background-repeat: no-repeat;
			background-origin: border-box;
			border: 1px solid rgba(10,25,41,0.08);
			transition: transform 0.28s ease, box-shadow 0.28s ease;
		}
		#vance-gastro-conditions .vance-gc-tile::before {
			content: '';
			position: absolute;
			inset: 0;
			background: linear-gradient(180deg, rgba(10,25,41,0.10) 0%, rgba(10,25,41,0.82) 100%);
			transition: background 0.28s ease;
		}
		#vance-gastro-conditions .vance-gc-tile:hover,
		#vance-gastro-conditions .vance-gc-tile:focus-visible {
			transform: translateY(-6px) scale(1.015);
			box-shadow: 0 22px 48px rgba(10,25,41,0.22);
			outline: none;
		}
		#vance-gastro-conditions .vance-gc-tile:hover::before,
		#vance-gastro-conditions .vance-gc-tile:focus-visible::before {
			background: linear-gradient(180deg, rgba(0,128,128,0.25) 0%, rgba(10,25,41,0.90) 100%);
		}
		#vance-gastro-conditions .vance-gc-tile-label {
			position: relative;
			z-index: 2;
			color: #ffffff;
			font-family: 'Outfit', sans-serif;
			font-size: 19px;
			font-weight: 800;
			line-height: 1.25;
		}
		#vance-gastro-conditions .vance-gc-tile-arrow {
			display: block;
			margin-top: 8px;
			font-size: 13px;
			font-weight: 700;
			letter-spacing: 0.6px;
			text-transform: uppercase;
			opacity: 0.85;
		}
		/* The "view all" tile is a flat brand-colour panel, deliberately
		   distinct from the seven photo tiles. */
		#vance-gastro-conditions .vance-gc-tile--all {
			background-color: <?php echo esc_attr( $view_all_bg ); ?>;
			background-image: none;
			align-items: center;
			justify-content: center;
			text-align: center;
			border-color: <?php echo esc_attr( $view_all_bg ); ?>;
		}
		#vance-gastro-conditions .vance-gc-tile--all::before { background: rgba(0,0,0,0); }
		#vance-gastro-conditions .vance-gc-tile--all:hover::before,
		#vance-gastro-conditions .vance-gc-tile--all:focus-visible::before { background: rgba(0,0,0,0.16); }
		#vance-gastro-conditions .vance-gc-tile--all .vance-gc-tile-label {
			font-size: 17px;
			letter-spacing: 0.8px;
			text-transform: uppercase;
		}
		/* Scroll-in reveal, scoped to this section so it doesn't depend on
		   page-gi-health.php's own inline reveal script being on the page. */
		#vance-gastro-conditions .vance-gc-reveal {
			opacity: 0;
			transform: translateY(18px);
			transition: opacity 0.5s ease, transform 0.5s ease;
			transition-delay: var(--gc-delay, 0s);
		}
		#vance-gastro-conditions .vance-gc-reveal.is-visible {
			opacity: 1;
			transform: none;
		}
		@media (max-width: 992px) {
			#vance-gastro-conditions .vance-gc-grid { grid-template-columns: repeat(<?php echo (int) min( 2, $per_row ); ?>, minmax(0, 1fr)); }
		}
		@media (max-width: 600px) {
			#vance-gastro-conditions .vance-gc-grid { grid-template-columns: 1fr; }
			#vance-gastro-conditions .vance-gc-header h2 { font-size: 30px; }
		}
		@media (prefers-reduced-motion: reduce) {
			#vance-gastro-conditions .vance-gc-tile { transition: none; }
			#vance-gastro-conditions .vance-gc-tile:hover,
			#vance-gastro-conditions .vance-gc-tile:focus-visible { transform: none; }
			#vance-gastro-conditions .vance-gc-reveal { opacity: 1; transform: none; transition: none; }
		}
	</style>
	<section id="vance-gastro-conditions" class="vance-gastro-conditions">
		<div class="container">
			<?php if ( $heading || $subtitle ) : ?>
			<div class="vance-gc-header">
				<?php if ( $heading ) : ?><h2><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
				<?php if ( $subtitle ) : ?><p><?php echo esc_html( $subtitle ); ?></p><?php endif; ?>
			</div>
			<?php endif; ?>

			<div class="vance-gc-grid">
				<?php foreach ( $cards as $i => $c ) :
					/* Cache-bust on the file's mtime — the row photos get swapped
					   in place keeping the filename, and Hostinger serves them
					   with a long max-age. Same technique as page-gi-health.php. */
					$img_rel  = '/assets/img/gi-health/' . $c['image'];
					$img_src  = $tmpl . $img_rel;
					$img_file = $dir . $img_rel;
					if ( file_exists( $img_file ) ) {
						$img_src = add_query_arg( 'v', filemtime( $img_file ), $img_src );
					}
					$delay = ( ( $i % $per_row ) * 0.07 ) . 's';
				?>
				<a href="<?php echo esc_url( vance_gi_page_url( $c['slug'] ) ); ?>"
				   class="vance-gc-tile vance-gc-reveal"
				   style="background-image: url('<?php echo esc_url( $img_src ); ?>'); --gc-delay: <?php echo esc_attr( $delay ); ?>;"
				   aria-label="<?php echo esc_attr( wp_strip_all_tags( $c['title'] ) ); ?>">
					<span class="vance-gc-tile-label">
						<?php echo wp_kses_post( $c['title'] ); ?>
						<span class="vance-gc-tile-arrow">Learn more &rarr;</span>
					</span>
				</a>
				<?php endforeach; ?>

				<?php $all_delay = ( ( count( $cards ) % $per_row ) * 0.07 ) . 's'; ?>
				<a href="<?php echo esc_url( vance_gi_hub_url() ); ?>"
				   class="vance-gc-tile vance-gc-tile--all vance-gc-reveal"
				   style="--gc-delay: <?php echo esc_attr( $all_delay ); ?>;">
					<span class="vance-gc-tile-label"><?php echo esc_html( $view_all_txt ); ?></span>
				</a>
			</div>
		</div>
	</section>
	<script>
	(function () {
		'use strict';
		var items = document.querySelectorAll('#vance-gastro-conditions .vance-gc-reveal');
		if (!items.length) { return; }
		var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		if (reduceMotion || !('IntersectionObserver' in window)) {
			Array.prototype.forEach.call(items, function (el) { el.classList.add('is-visible'); });
			return;
		}
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); }
			});
		}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
		Array.prototype.forEach.call(items, function (el) { io.observe(el); });
	})();
	</script>
	<?php
}

// ============================================================================
// Registry — one more orderable homepage section.
// ============================================================================

add_filter( 'vance_homepage_sections', function ( $sections ) {
	$sections['gastro-conditions'] = array(
		'label'  => 'Gastro Conditions (condition tiles)',
		'group'  => 'Homepage',
		'render' => 'vance_render_gastro_conditions',
	);
	return $sections;
} );
