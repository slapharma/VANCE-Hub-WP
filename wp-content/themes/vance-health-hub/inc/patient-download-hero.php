<?php
/**
 * Spotlight-style hero for Patient Downloads companion posts.
 *
 * inc/page-hero-spotlight.php is the real spotlight machinery, but it is built
 * for the site's ~14 FIXED marketing pages: every page there gets its own
 * hand-written config block, Customizer panel and section. That is the wrong
 * tool for a growing set of blog posts (5 today, more every time a new
 * handout ships) — 5-10+ Customizer sections for content that keeps growing
 * is exactly the disproportionate machinery this codebase avoids elsewhere.
 *
 * So this file renders the SAME markup and CSS classes (`vhh-hero-spotlight`
 * and children, already ~117 rules in main.css) directly from the post's own
 * data — title, excerpt, featured image, and the two meta keys below — with
 * no Customizer registration at all. Visually identical to the spotlight
 * hero; a fraction of the weight. Same idea inc/page-hero-spotlight.php's
 * 'always' => true entries already use for the 404 (skip the toggle/theme_mod
 * layer), taken one step further since there is no fixed page key here to
 * hang a config entry on.
 *
 * @package vance-health-hub
 * @since   2026-09-04
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register the two meta keys a patient-download post carries.
 *
 * Presence of `_vpd_pdf_file` is also the SIGNAL single.php uses to decide
 * whether a post is a patient-download companion at all — see the guard in
 * single.php's hero section.
 */
function vance_patient_download_register_meta() {
	register_post_meta( 'post', '_vpd_pdf_file', array(
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_file_name',
	) );
	register_post_meta( 'post', '_vpd_eyebrow', array(
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
	) );
}
add_action( 'init', 'vance_patient_download_register_meta' );

/**
 * Up to 2 sibling handout posts (same category, excluding the current one),
 * plus a "See all handouts" cell that always appears — same shape as
 * vance_page_hero_spotlight_downloads() in inc/page-hero-spotlight.php, kept
 * as a separate copy here because that function's whole file is scoped to
 * the fixed-page Customizer system this hero deliberately does not use.
 *
 * @param int $post_id
 * @return array<int, array{key:string,label:string,value:string,href:string}>
 */
function vance_patient_download_hero_siblings( $post_id ) {
	$siblings = get_posts( array(
		'category_name'  => 'patient-handouts',
		'post__not_in'   => array( $post_id ),
		'posts_per_page' => 2,
		'orderby'        => 'rand',
		'post_status'    => 'publish',
	) );

	$cells = array();
	foreach ( $siblings as $sibling ) {
		$eyebrow = get_post_meta( $sibling->ID, '_vpd_eyebrow', true );
		$cells[] = array(
			'key'   => 'clipboard',
			'label' => $eyebrow ? $eyebrow : __( 'Patient Handout', 'vance-health-hub' ),
			'value' => get_the_title( $sibling ),
			'href'  => get_permalink( $sibling ),
		);
	}

	$cells[] = array(
		'key'   => 'grid',
		'label' => __( 'More', 'vance-health-hub' ),
		'value' => __( 'See all the handouts', 'vance-health-hub' ),
		'href'  => home_url( '/patient-downloads/' ),
	);

	return $cells;
}

/**
 * The hero itself. Called from single.php in place of the classic oped-hero
 * when the post carries `_vpd_pdf_file`.
 *
 * Started as the SAME 300px band a regular article's oped-hero uses (see the
 * inline height/min-height/align-items on that section in single.php), then
 * given an extra 75px (375px total) once the meta bar below it was removed
 * for these posts, rather than the much taller landing-page proportions this
 * markup renders at everywhere else on the site (homepage, Contact, About).
 * Even at 375px there is only room for eyebrow + title + one line of intro
 * plus the download actions,
 * so the white "more free handouts" band and the floating reassurance card
 * that the full-height version carries are dropped here, not shrunk to fit;
 * the same sibling-handout links move to the sidebar instead, see
 * vance_render_patient_download_sidebar() below.
 *
 * Every size override below is inline rather than a new main.css rule,
 * matching the same reasoning as vance_patient_download_cta_button()'s inline
 * colour fix: this hero's normal type scale (a 56px clamp() title, 36px of
 * space under the buttons) is sized for that taller box, not this one, and
 * scoping the override to a new CSS class would mean either fighting that
 * specificity or a second stylesheet deploy for a size that only this one
 * hero ever uses.
 *
 * Colour defaults are the same ones vance_hero_spotlight_field_defaults()
 * falls back to for every other spotlight hero on the site (main.css never
 * saw an admin touch these on a patient-download post, so there is nothing to
 * read from the Customizer here, they are simply the brand's spotlight
 * palette, inline).
 *
 * @param int $post_id
 */
function vance_render_patient_download_hero( $post_id ) {
	$pdf_file = get_post_meta( $post_id, '_vpd_pdf_file', true );
	if ( ! $pdf_file || ! file_exists( get_template_directory() . '/assets/downloads/' . $pdf_file ) ) {
		return;
	}

	$eyebrow  = get_post_meta( $post_id, '_vpd_eyebrow', true );
	$eyebrow  = $eyebrow ? $eyebrow : __( 'Patient Handout', 'vance-health-hub' );
	$title    = wp_kses_post( get_the_title( $post_id ) );
	$intro    = get_the_excerpt( $post_id );
	$pdf_url  = get_template_directory_uri() . '/assets/downloads/' . $pdf_file;

	$has_image = has_post_thumbnail( $post_id );
	$image     = $has_image ? get_the_post_thumbnail_url( $post_id, 'full' ) : '';
	$image_alt = $has_image ? get_post_meta( get_post_thumbnail_id( $post_id ), '_wp_attachment_image_alt', true ) : '';

	// Light purple palette, replacing the teal/mint every other spotlight hero
	// on the site uses. #9B8BD0 (the dot image below is recoloured to the same
	// value) is the LILAC accent guide_kit.py already defines for the PDF
	// handouts themselves, so the web page and the PDF it links to share one
	// accent rather than each inventing its own purple.
	$fade_from = vance_hex_to_rgb_triple( '#F1EDFA', '241, 237, 250' );
	$fade_to   = vance_hex_to_rgb_triple( '#FAF9FC', '250, 249, 252' );

	$style = sprintf(
		'--vhh-hs-from: #F1EDFA; --vhh-hs-to: #FAF9FC; --vhh-hs-from-rgb: %1$s; --vhh-hs-to-rgb: %2$s; --vhh-hs-title: #3D2A5C; --vhh-hs-intro: #4B4356; --vhh-hs-cta-bg: #6B489E; --vhh-hs-cta-fg: #ffffff; --vhh-hs-cta-hover: #583B82; height: 375px; min-height: 0; padding: 0; display: flex; align-items: center;',
		esc_attr( $fade_from ),
		esc_attr( $fade_to )
	);
	?>
	<section class="vhh-hero-spotlight vhh-hero-spotlight--page vhh-hero-spotlight--patientdownload<?php echo $has_image ? '' : ' vhh-hero-spotlight--has-motif'; ?>" style="<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput — each part escaped above ?>">

		<?php if ( $has_image ) : ?>
		<div class="vhh-hero-spotlight__media">
			<img src="<?php echo esc_url( $image ); ?>"
			     alt="<?php echo esc_attr( $image_alt ); ?>"
			     width="1400" height="876"
			     decoding="async" fetchpriority="high">
		</div>
		<?php else : ?>
		<?php
		// The site's usual dot-field motif (vance_page_hero_spotlight_motif())
		// is teal to match the OTHER spotlight heroes; this one is purple, so it
		// gets its own asset rather than fighting that function's hard-coded
		// colour. object-position keeps the pattern's dense upper-left swirl in
		// frame rather than the sparse fade at the bottom, which is what a
		// naive centre crop of a 1080x1350 portrait image into a 300px-tall box
		// would otherwise show.
		?>
		<div class="vhh-hero-spotlight__motif" aria-hidden="true">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/heroes/patient-download-dots.png' ); ?>"
			     alt="" width="1080" height="1350" decoding="async"
			     style="width: 100%; height: 100%; object-fit: cover; object-position: 30% 15%; display: block;">
		</div>
		<?php endif; ?>

		<?php
		// width:100% is load-bearing: the section above is display:flex (for the
		// 300px vertical centering), and a flex item with no explicit width
		// shrinks to its content's natural size rather than filling the row, so
		// .container's own max-width:1200px/margin:auto centering was capturing
		// a ~864px shrink-wrapped box instead of the full width, and re-centering
		// the whole hero noticeably right of where the article body below it
		// starts.
		?>
		<div class="container vhh-hero-spotlight__inner" style="width: 100%;">
			<div class="vhh-hero-spotlight__copy">

				<?php
				// Background/border here are hard-coded teal in main.css (only
				// the text colour reads the --vhh-hs-title var), so the purple
				// palette needs the same inline override the ghost CTA's border
				// gets just below.
				?>
				<span class="vhh-hero-spotlight__eyebrow" style="margin-bottom: 8px; background: #EDE7F7; border-color: #9B87C4;"><?php echo esc_html( $eyebrow ); ?></span>

				<h1 class="vhh-hero-spotlight__title" style="font-size: clamp(24px, 2.6vw, 32px); line-height: 1.15; max-width: 480px; margin: 0 0 10px;"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput — wp_kses_post above ?></h1>

				<?php if ( $intro !== '' ) : ?>
				<p class="vhh-hero-spotlight__intro" style="font-size: 14.5px; line-height: 1.5; max-width: 480px; margin: 0 0 16px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo esc_html( $intro ); ?></p>
				<?php endif; ?>

				<div class="vhh-hero-spotlight__actions" style="margin-bottom: 0;">
					<a class="vhh-hero-spotlight__cta" href="<?php echo esc_url( $pdf_url ); ?>" download style="padding: 11px 20px; font-size: 14px;">
						<span><?php esc_html_e( 'Download the PDF', 'vance-health-hub' ); ?></span>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
					</a>
					<a class="vhh-hero-spotlight__cta vhh-hero-spotlight__cta--ghost" href="<?php echo esc_url( home_url( '/patient-downloads/' ) ); ?>" style="padding: 11px 20px; font-size: 14px; border-color: #9B87C4;"><?php esc_html_e( 'Back to all handouts', 'vance-health-hub' ); ?></a>
				</div>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Sidebar block listing sibling handouts, replacing the "more free handouts"
 * band the full-height spotlight hero would otherwise carry (see the height
 * note on vance_render_patient_download_hero() above for why it moved here).
 * Markup matches the site's other simple sidebar blocks (e.g. Related
 * Articles in single.php).
 *
 * @param int $post_id
 */
function vance_render_patient_download_sidebar( $post_id ) {
	$siblings = vance_patient_download_hero_siblings( $post_id );
	if ( ! $siblings ) {
		return;
	}
	?>
	<div class="oped-sidebar-block oped-patient-download-siblings">
		<h4><?php esc_html_e( 'More Handouts', 'vance-health-hub' ); ?></h4>
		<div class="oped-sidebar-content" style="display: flex; flex-direction: column; gap: 10px;">
			<?php foreach ( $siblings as $line ) : ?>
			<a href="<?php echo esc_url( $line['href'] ); ?>" style="display: block; text-decoration: none; padding: 10px 12px; border-radius: var(--radius-control, 10px); background: var(--accent-color, #F3F4F6);">
				<span style="display: block; font-size: 10px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: var(--primary-color, #008080);"><?php echo esc_html( $line['label'] ); ?></span>
				<span style="display: block; font-size: 13.5px; font-weight: 600; color: var(--secondary-color, #0A1929); margin-top: 2px;"><?php echo esc_html( $line['value'] ); ?></span>
			</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Second, identical-style download button for the foot of the article body —
 * same `btn btn-primary` + icon markup as vpd_download_btn() in
 * page-patient-downloads.php, copied rather than shared since that function
 * is scoped to a template file that only runs on the hub page.
 *
 * @param string $pdf_url
 * @return string
 */
function vance_patient_download_cta_button( $pdf_url ) {
	// .oped-article-body a's own teal/underline rule outranks .btn-primary's
	// white — inline style wins the specificity fight without a CSS deploy.
	return sprintf(
		'<p class="vpd-post-cta"><a href="%1$s" class="btn btn-primary" download style="color:#fff;text-decoration:none;"><span class="vpd-post-cta__icon" aria-hidden="true">%2$s</span><span>%3$s</span></a></p>',
		esc_url( $pdf_url ),
		'<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
		esc_html__( 'Download the full PDF', 'vance-health-hub' )
	);
}
