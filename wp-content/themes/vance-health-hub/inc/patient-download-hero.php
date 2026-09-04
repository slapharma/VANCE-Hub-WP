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
 * Colour defaults are the same ones vance_hero_spotlight_field_defaults()
 * falls back to for every other spotlight hero on the site (main.css never
 * saw an admin touch these on a patient-download post, so there is nothing to
 * read from the Customizer here — they are simply the brand's spotlight
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

	$fade_from = vance_hex_to_rgb_triple( '#ECF5F5', '236, 245, 245' );
	$fade_to   = vance_hex_to_rgb_triple( '#F6F9FA', '246, 249, 250' );

	$style = sprintf(
		'--vhh-hs-from: #ECF5F5; --vhh-hs-to: #F6F9FA; --vhh-hs-from-rgb: %1$s; --vhh-hs-to-rgb: %2$s; --vhh-hs-title: #04504E; --vhh-hs-intro: #3F4B4E; --vhh-hs-cta-bg: #6B489E; --vhh-hs-cta-fg: #ffffff; --vhh-hs-cta-hover: #583B82; --vhh-hs-card-bg: #E5F1F1;',
		esc_attr( $fade_from ),
		esc_attr( $fade_to )
	);

	$slot_items = vance_patient_download_hero_siblings( $post_id );
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
		<div class="vhh-hero-spotlight__motif" aria-hidden="true"><?php
			echo vance_page_hero_spotlight_motif(); // phpcs:ignore WordPress.Security.EscapeOutput — static markup
		?></div>
		<?php endif; ?>

		<div class="container vhh-hero-spotlight__inner">
			<div class="vhh-hero-spotlight__copy">

				<span class="vhh-hero-spotlight__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>

				<h1 class="vhh-hero-spotlight__title"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput — wp_kses_post above ?></h1>

				<?php if ( $intro !== '' ) : ?>
				<p class="vhh-hero-spotlight__intro"><?php echo esc_html( $intro ); ?></p>
				<?php endif; ?>

				<div class="vhh-hero-spotlight__actions">
					<a class="vhh-hero-spotlight__cta" href="<?php echo esc_url( $pdf_url ); ?>" download>
						<span><?php esc_html_e( 'Download the PDF', 'vance-health-hub' ); ?></span>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
					</a>
					<a class="vhh-hero-spotlight__cta vhh-hero-spotlight__cta--ghost" href="<?php echo esc_url( home_url( '/patient-downloads/' ) ); ?>"><?php esc_html_e( 'Back to all handouts', 'vance-health-hub' ); ?></a>
				</div>

				<?php if ( $slot_items ) : ?>
				<div class="vhh-hero-spotlight__slot-wrap">
					<span class="vhh-hero-spotlight__slot-label"><?php esc_html_e( 'More free handouts', 'vance-health-hub' ); ?></span>
					<div class="vhh-hero-spotlight__slot vhh-hero-spotlight__slot--lines">
						<?php foreach ( $slot_items as $line ) :
							$tag = $line['href'] ? 'a' : 'div';
							?>
							<<?php echo $tag; ?> class="vhh-hero-spotlight__line"<?php if ( $line['href'] ) : ?> href="<?php echo esc_url( $line['href'] ); ?>"<?php endif; ?>>
								<span class="vhh-hero-spotlight__line-ico"><?php echo vance_page_hero_spotlight_icon( $line['key'] ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup ?></span>
								<span class="vhh-hero-spotlight__line-body">
									<span class="vhh-hero-spotlight__line-k"><?php echo esc_html( $line['label'] ); ?></span>
									<span class="vhh-hero-spotlight__line-v"><?php echo esc_html( $line['value'] ); ?></span>
								</span>
							</<?php echo $tag; ?>>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<aside class="vhh-hero-spotlight__card">
				<span class="vhh-hero-spotlight__card-icon" aria-hidden="true"><?php echo vance_page_hero_spotlight_icon( 'clipboard' ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup ?></span>
				<div class="vhh-hero-spotlight__card-body">
					<h2 class="vhh-hero-spotlight__card-title"><?php esc_html_e( 'Built for the moments a screen isn’t easiest', 'vance-health-hub' ); ?></h2>
					<p class="vhh-hero-spotlight__card-text"><?php esc_html_e( 'Every handout is evidence-backed, free, and yours to keep — no account needed to download.', 'vance-health-hub' ); ?></p>
				</div>
			</aside>
		</div>
	</section>
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
