<?php
/**
 * Template Name: Patient Downloads
 *
 * Hub page for the patient handout PDF series (10 planned, see the ideas
 * doc in the Patient Downloads shared-drive folder). Card grid modeled on
 * page-tools-resources.php; the download-button/file-size-meta pattern is
 * the same one page-user-guide.php uses for its own single PDF, generalised
 * here to take a filename per card since this page renders several.
 *
 * To activate: create a Page titled "Patient Downloads", slug
 * `patient-downloads`, and choose "Patient Downloads" as the template
 * (Page Attributes). The live "Patient Downloads" nav item currently points
 * at /user-guide/ as a stand-in — repointing it to this page, and adding a
 * child nav item for each handout as it goes live, is a manual wp-admin
 * step (the primary menu is hand-maintained, see CLAUDE.md).
 *
 * Customizer panel: Appearance → Customize → Page - Patient Downloads.
 */
get_header();

/**
 * "23 pages · PDF · 5.7 MB" for a given handout file, or just "PDF" if the
 * file is missing — mirrors vug_pdf_meta() in page-user-guide.php, so a
 * failed deploy degrades to a plain label rather than a PHP warning.
 *
 * @param string $filename File name under /assets/downloads/.
 * @param int    $pages    Page count (the one fact the file can't tell us cheaply).
 * @return string
 */
function vpd_pdf_meta( $filename, $pages ) {
	$path = get_template_directory() . '/assets/downloads/' . $filename;
	if ( ! $filename || ! file_exists( $path ) ) {
		return 'PDF';
	}
	$mb = filesize( $path ) / 1000000;
	return sprintf(
		/* translators: 1: page count, 2: file size in MB */
		__( '%1$d pages · PDF · %2$s MB', 'vance-health-hub' ),
		$pages,
		number_format_i18n( $mb, 1 )
	);
}

/**
 * Same download-button markup as vug_download_btn() in page-user-guide.php
 * (`download` attribute so the browser saves rather than opens a viewer tab).
 *
 * @param string $pdf_url URL of the PDF.
 * @param string $label   Button text.
 * @return string
 */
function vpd_download_btn( $pdf_url, $label = '' ) {
	$label = $label ? $label : __( 'Download PDF', 'vance-health-hub' );
	return sprintf(
		'<a href="%1$s" class="btn btn-primary vpd-card__btn" download><span class="vpd-card__icon" aria-hidden="true">%2$s</span><span>%3$s</span></a>',
		esc_url( $pdf_url ),
		'<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
		esc_html( $label )
	);
}

/**
 * All 10 planned handouts, in the build order proposed alongside this page
 * (appointment-prep and travel are the two the live nav already names).
 * 'file' + 'pages' are set only once a handout is actually built — everything
 * else renders from title/tag/desc alone in the "coming soon" state.
 */
$vpd_downloads = array(
	array(
		'slug'  => 'appointment-preparation',
		'title' => 'Preparing for Your Doctor Appointment',
		'tag'   => 'Appointment Prep',
		'desc'  => 'A quick-reference checklist, your care team explained, and the questions worth asking — for GP, gastroenterology or dietetic appointments.',
		'file'  => 'Vance-Health-Hub-Appointment-Preparation.pdf',
		'pages' => 4,
	),
	array(
		'slug'  => 'ibd-travel-checklist',
		'title' => 'Your IBD Travel Checklist',
		'tag'   => 'Travel',
		'desc'  => 'Medication, letters, vaccines, insurance and toilet access sorted before you go — practical prep for flying and holidays with Crohn\'s or colitis.',
		'file'  => 'Vance-Health-Hub-IBD-Travel-Checklist.pdf',
		'pages' => 4,
	),
	array(
		'slug'  => 'ibd-symptom-checker',
		'title' => 'Is It a Flare — or Something Else?',
		'tag'   => 'Self-Triage',
		'desc'  => 'The tests that tell a flare apart from an infection or IBS, plus the red flags that mean get help now, not later.',
		'file'  => 'Vance-Health-Hub-IBD-Symptom-Checker.pdf',
		'pages' => 3,
	),
	array(
		'slug'  => 'ibd-flare-survival-guide',
		'title' => 'The IBD Flare Survival Guide',
		'tag'   => 'Flare Management',
		'desc'  => 'What to do when symptoms get worse — practical steps for the first 48 hours.',
		'file'  => 'Vance-Health-Hub-IBD-Flare-Survival-Guide.pdf',
		'pages' => 4,
	),
	array(
		'slug'  => 'practical-food-guide',
		'title' => 'The Practical Food Guide for Crohn’s & Colitis',
		'tag'   => 'Food & Nutrition',
		'desc'  => 'What to eat, what to watch, and how to tell the difference between a trigger and a coincidence.',
		'file'  => 'Vance-Health-Hub-Practical-Food-Guide.pdf',
		'pages' => 4,
	),
	array(
		'slug'  => '7-day-meal-plan',
		'title' => 'The 7-Day Gut-Friendly Meal Plan',
		'tag'   => 'Meal Planning',
		'desc'  => 'A full week of gut-friendly meals for Crohn’s and colitis, ready to follow or adapt.',
		'file'  => '',
		'pages' => 0,
	),
	array(
		'slug'  => 'ibd-trigger-tracker',
		'title' => 'The IBD Trigger Tracker',
		'tag'   => 'Self-Monitoring',
		'desc'  => 'A structured way to find out what could be making your symptoms worse.',
		'file'  => '',
		'pages' => 0,
	),
	array(
		'slug'  => 'ibd-fatigue',
		'title' => 'IBD Fatigue: 15 Things That Can Help',
		'tag'   => 'Fatigue',
		'desc'  => 'Practical ideas for when you’re completely exhausted and rest alone isn’t fixing it.',
		'file'  => '',
		'pages' => 0,
	),
	array(
		'slug'  => 'ibd-emergency-kit',
		'title' => 'The IBD Emergency Kit',
		'tag'   => 'Preparedness',
		'desc'  => 'What to keep at home, at work and on the go, so a flare doesn’t catch you unprepared.',
		'file'  => '',
		'pages' => 0,
	),
	array(
		'slug'  => 'ibd-partner-guide',
		'title' => 'The IBD Partner Guide',
		'tag'   => 'Family & Friends',
		'desc'  => 'What the people closest to you actually need to know to help, not hover.',
		'file'  => '',
		'pages' => 0,
	),
);
?>

<main id="main-content" class="patient-downloads-page">

	<?php
	if ( function_exists( 'vance_page_hero_spotlight_active' ) && vance_page_hero_spotlight_active( 'patientdownloads' ) ) :
		vance_render_page_hero_spotlight( 'patientdownloads' );
	else :
		$hero_bg      = vance_get_theme_mod( 'vance_patientdownloads_hero_bg', get_template_directory_uri() . '/assets/img/education_hero.png' );
		$hero_tag     = vance_get_theme_mod( 'vance_patientdownloads_hero_tag', 'Patient Downloads' );
		$hero_title   = vance_get_theme_mod( 'vance_patientdownloads_hero_title', 'Printable guides for your <span class="highlight">next appointment</span>' );
		$hero_desc    = vance_get_theme_mod( 'vance_patientdownloads_hero_desc', 'Free, evidence-backed PDF handouts you can save to your phone or print — built for the moments a screen isn’t the easiest way to have the conversation.' );
		$hero_overlay = max( 0, min( 100, absint( vance_get_theme_mod( 'vance_patientdownloads_hero_overlay', 70 ) ) ) ) / 100;
		$hero_overlay_bottom = min( 1, $hero_overlay + 0.15 );
		?>
		<section class="hero patientdownloads-hero" style="padding: 72px 0 116px; min-height: 332px; display: flex; align-items: center; background: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $hero_overlay ); ?>), rgba(10,25,41,<?php echo esc_attr( $hero_overlay_bottom ); ?>)), url('<?php echo esc_url( $hero_bg ); ?>') no-repeat center center; background-size: cover;">
			<div class="container">
				<div class="hero-content">
					<span class="tag-label"><?php echo esc_html( $hero_tag ); ?></span>
					<h1><?php echo wp_kses_post( $hero_title ); ?></h1>
					<p><?php echo esc_html( $hero_desc ); ?></p>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<!-- CARD GRID -->
	<section id="downloads-grid" class="section-padding vpd-grid-section" style="background: var(--accent-color);">
		<div class="container">
			<div class="vpd-card-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px;">
				<?php foreach ( $vpd_downloads as $d ) :
					$is_live  = ! empty( $d['file'] ) && file_exists( get_template_directory() . '/assets/downloads/' . $d['file'] );
					$pdf_url  = $is_live ? get_template_directory_uri() . '/assets/downloads/' . $d['file'] : '';
					$meta     = $is_live ? vpd_pdf_meta( $d['file'], $d['pages'] ) : '';
					// The companion post — see inc/patient-download-hero.php — shares
					// this handout's own 'slug', a field this array has carried since
					// the page was built but never read until now.
					$post_obj  = $is_live ? get_page_by_path( $d['slug'], OBJECT, 'post' ) : null;
					$post_link = ( $post_obj && $post_obj->post_status === 'publish' ) ? get_permalink( $post_obj ) : '';
					?>
				<div class="vpd-card<?php echo $is_live ? '' : ' vpd-card--soon'; ?>" style="display: flex; flex-direction: column; padding: 32px; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border-top: 4px solid <?php echo $is_live ? '#008080' : '#CBD5E1'; ?>;">
					<div class="vpd-card__head" style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 14px;">
						<span style="display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: <?php echo $is_live ? 'var(--primary-color)' : 'var(--text-light)'; ?>;"><?php echo esc_html( $d['tag'] ); ?></span>
						<?php if ( ! $is_live ) : ?>
						<span class="vpd-card__soon" style="flex-shrink: 0; padding: 2px 9px; border-radius: var(--radius-pill, 999px); background: var(--accent-color, #F3F4F6); color: var(--text-light); font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Soon</span>
						<?php endif; ?>
					</div>
					<h3 class="vpd-card__title" style="font-size: 18px; color: var(--secondary-color); margin: 0 0 10px; line-height: 1.35;">
						<?php if ( $post_link ) : ?>
						<a href="<?php echo esc_url( $post_link ); ?>" style="color: inherit; text-decoration: none;"><?php echo wp_kses_post( $d['title'] ); ?></a>
						<?php else : ?>
						<?php echo wp_kses_post( $d['title'] ); ?>
						<?php endif; ?>
					</h3>
					<p class="vpd-card__desc" style="color: var(--text-light); font-size: 14px; margin: 0 0 20px 0; line-height: 1.6; flex: 1;"><?php echo esc_html( $d['desc'] ); ?></p>
					<?php if ( $is_live ) : ?>
						<?php echo vpd_download_btn( $pdf_url ); ?>
						<span class="vpd-card__meta" style="display: block; margin-top: 8px; font-size: 12px; color: var(--text-light);"><?php echo esc_html( $meta ); ?></span>
						<?php if ( $post_link ) : ?>
						<a href="<?php echo esc_url( $post_link ); ?>" class="vpd-card__readmore" style="display: block; margin-top: 10px; font-size: 13px; font-weight: 600; color: var(--primary-color);">Read the summary &rarr;</a>
						<?php endif; ?>
					<?php else : ?>
						<span class="btn vpd-card__btn vpd-card__btn--disabled" style="display: inline-flex; align-items: center; justify-content: center; background: var(--accent-color, #F3F4F6); color: var(--text-light); cursor: default;">Coming soon</span>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<style>
		@media (max-width: 900px) {
			.vpd-card-grid { grid-template-columns: 1fr !important; }
		}
		.vpd-card--soon { opacity: 0.82; }
	</style>

</main>

<?php get_footer(); ?>
