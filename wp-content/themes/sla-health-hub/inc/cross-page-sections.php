<?php
/**
 * Cross-page section renderers.
 *
 * Each named block on a non-homepage template (patients, healthcare-pros,
 * turn-evidence, tools-resources) is wrapped in a small `vance_render_section_*()`
 * function below. The source page template calls the function instead of
 * holding the markup inline, AND front-page.php can call the same function
 * when an admin adds the block to the homepage via the Section Order control.
 *
 * Source-of-truth lives here. Templates are thin wrappers. The block's
 * Customizer settings (vance_pat_ben_*, vance_hcp_res_*, etc.) are unchanged,
 * so admin-saved values keep working everywhere.
 *
 * Registry registration happens at the bottom of this file via the
 * `vance_homepage_sections` filter — picked up by
 * inc/customizer-sortable-control.php's vance_get_available_sections().
 *
 * @package sla-health-hub
 * @since   2026-05-25
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ============================================================================
// PATIENTS — Benefits, Tools, CTA
// ============================================================================

function vance_render_section_patients_benefits() {
	$ben_tag        = vance_get_theme_mod( 'vance_pat_ben_tag',        'Why Choose Vance Medical?' );
	$ben_title      = vance_get_theme_mod( 'vance_pat_ben_title',      'Not Just Another Community' );
	$ben_desc       = vance_get_theme_mod( 'vance_pat_ben_desc',       'Vance Medical is a comprehensive suite of resources designed to aid your personal health journey. We bridge the gap between complex medical research and practical, daily wellness by providing clinical information in a format that is easy to understand.' );
	$ben_tag_bg     = vance_get_theme_mod( 'vance_pat_ben_tag_bg',     '' );
	$ben_tag_color  = vance_get_theme_mod( 'vance_pat_ben_tag_color',  '' );
	$ben_tag_border = vance_get_theme_mod( 'vance_pat_ben_tag_border', '' );
	$ben_tag_style  = '';
	if ( $ben_tag_bg )     { $ben_tag_style .= 'background:' . esc_attr( $ben_tag_bg ) . ';'; }
	if ( $ben_tag_color )  { $ben_tag_style .= 'color:' . esc_attr( $ben_tag_color ) . ';'; }
	if ( $ben_tag_border ) { $ben_tag_style .= 'border-color:' . esc_attr( $ben_tag_border ) . ';'; }

	$ben_defaults = array(
		1 => array( 'Clear Clinical Info',   'Access cutting-edge clinical information translated into a clear, easy-to-understand format tailored for patients, without the medical jargon.' ),
		2 => array( 'Renowned Expertise',    'Engage with exclusive content, insights, and guidance produced directly by Vance Medical specialists and world-renowned gastro healthcare experts.' ),
		3 => array( 'Actionable Solutions',  'Take control with highly interactive calculators, health trackers, and personalized AI to bring the clinic directly into your home life.' ),
	);
	$ben_colors = array(
		1 => array( '#008080', '#ffffff' ),
		2 => array( '#78bfbf', '#ffffff' ),
		3 => array( '#aedbdb', '#008080' ),
	);
	$ben_icons  = array(
		1 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
		2 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
		3 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
	);
	?>
	<section id="benefits" class="section-padding" style="background: white;">
		<div class="container">
			<div class="text-center max-600 margin-b-60">
				<span class="tag-section" style="<?php echo $ben_tag_style; ?>"><?php echo esc_html( $ben_tag ); ?></span>
				<h2 style="color: var(--secondary-color);"><?php echo esc_html( $ben_title ); ?></h2>
				<p style="color: var(--text-light);"><?php echo esc_html( $ben_desc ); ?></p>
			</div>
			<div class="grid-3 benefit-grid margin-b-60">
				<?php for ( $i = 1; $i <= 3; $i++ ) :
					$ben_t = vance_get_theme_mod( "vance_pat_ben{$i}_title", $ben_defaults[ $i ][0] );
					$ben_d = vance_get_theme_mod( "vance_pat_ben{$i}_desc",  $ben_defaults[ $i ][1] );
				?>
				<div class="patient-benefit-card" style="text-align: center; padding: 40px 24px; background: var(--accent-color); border-radius: var(--radius-lg);">
					<div class="patient-benefit-icon" style="width: 64px; height: 64px; background: <?php echo esc_attr( $ben_colors[ $i ][0] ); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
						<svg width="32" height="32" fill="none" stroke="<?php echo esc_attr( $ben_colors[ $i ][1] ); ?>" viewBox="0 0 24 24"><?php echo $ben_icons[ $i ]; ?></svg>
					</div>
					<h3 style="font-size: 20px; margin-bottom: 12px; color: var(--secondary-color);"><?php echo esc_html( $ben_t ); ?></h3>
					<p style="color: var(--text-light); font-size: 15px;"><?php echo esc_html( $ben_d ); ?></p>
				</div>
				<?php endfor; ?>
			</div>
		</div>
	</section>
	<?php
}

function vance_render_section_patients_tools() {
	$tool_title = vance_get_theme_mod( 'vance_pat_tool_title', 'Innovative Tools at Your Fingertips' );
	$tool_defaults = array(
		1 => array( 'Ask VANCE-ai',      'Interact with our AI intelligence trained specifically in clinical gastro conditions for instant, reliable answers to your health questions.' ),
		2 => array( 'Bookmark & Clip',        'Easily save important articles, clip vital paragraphs, and create your own customized research notes directly in your portal.' ),
		3 => array( 'History & AI Tracking',  'Upload your medical history documents to allow Vance-i to securely analyze data, track your ongoing wellness, and spot trends.' ),
		4 => array( 'Healthcare Calculators', 'Evaluate potential malnutrition, calculate BMI, and score related healthcare symptoms to stay on top of your physical needs.' ),
		5 => array( 'Exclusive Courses',      'Enroll in customized, multi-chapter curriculums developed by gastro specialists focusing on diet, recovery, and lifestyle routines.' ),
		6 => array( 'Downloadable Guides',    'Save and export patient-focused literature, daily checklists, and clear instructions for managing clinical nutrition products.' ),
	);
	$tool_colors = array(
		1 => array( '#008080', '#006666', '#ffffff' ),
		2 => array( '#78bfbf', '#5fa3a3', '#ffffff' ),
		3 => array( '#aedbdb', '#88c5c5', '#008080' ),
		4 => array( '#008080', '#4a9999', '#ffffff' ),
		5 => array( '#78bfbf', '#aedbdb', '#008080' ),
		6 => array( '#def4f4', '#aedbdb', '#008080' ),
	);
	$tool_icons = array(
		1 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>',
		2 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>',
		3 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
		4 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
		5 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
		6 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
	);
	?>
	<section class="section-padding" style="background: var(--accent-color);">
		<div class="container">
			<h2 class="text-center" style="color: var(--secondary-color); margin-bottom: 40px;"><?php echo esc_html( $tool_title ); ?></h2>
			<div class="grid-3">
				<?php for ( $i = 1; $i <= 6; $i++ ) :
					$t_title = vance_get_theme_mod( "vance_pat_tool{$i}_title", $tool_defaults[ $i ][0] );
					$t_desc  = vance_get_theme_mod( "vance_pat_tool{$i}_desc",  $tool_defaults[ $i ][1] );
				?>
				<div class="patient-tool-card" style="display: flex; gap: 16px; padding: 28px; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
					<div class="patient-tool-icon" style="flex-shrink: 0; width: 48px; height: 48px; background: linear-gradient(135deg, <?php echo esc_attr( $tool_colors[ $i ][0] ); ?>, <?php echo esc_attr( $tool_colors[ $i ][1] ); ?>); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
						<svg width="24" height="24" fill="none" stroke="<?php echo esc_attr( $tool_colors[ $i ][2] ); ?>" viewBox="0 0 24 24"><?php echo $tool_icons[ $i ]; ?></svg>
					</div>
					<div>
						<h4 style="font-size: 16px; color: var(--secondary-color); margin-bottom: 6px;"><?php echo esc_html( $t_title ); ?></h4>
						<p style="color: var(--text-light); font-size: 13px; margin: 0;"><?php echo esc_html( $t_desc ); ?></p>
					</div>
				</div>
				<?php endfor; ?>
			</div>
		</div>
	</section>
	<?php
}

function vance_render_section_patients_cta() {
	$cta_title = vance_get_theme_mod( 'vance_pat_cta_title', 'Begin Your Journey' );
	$cta_desc  = vance_get_theme_mod( 'vance_pat_cta_desc',  "Join thousands of patients taking control of their gut health and longevity. It's completely free to start using our clinical resources today." );
	?>
	<section id="subscribe" class="section-padding patient-cta-section" style="background: linear-gradient(135deg, #008080, #006666);">
		<div class="container" style="text-align: center; color: white;">
			<h2 style="color: white; margin-bottom: 16px;"><?php echo esc_html( $cta_title ); ?></h2>
			<p class="max-600" style="font-size: 18px; margin-bottom: 32px; color: rgba(255,255,255,0.85);"><?php echo esc_html( $cta_desc ); ?></p>
			<form class="patient-subscribe-form" style="display: flex; gap: 12px; max-width: 500px; margin: 0 auto;">
				<input type="email" placeholder="Enter your email" class="form-input" style="flex: 1; min-width: 250px; padding: 14px 20px; border: none; border-radius: var(--radius-md); font-size: 16px;">
				<button type="submit" class="btn btn-primary">Subscribe Free</button>
			</form>
			<p style="font-size: 13px; color: rgba(255,255,255,0.7); margin-top: 16px;">Free forever. Unsubscribe anytime.</p>
		</div>
	</section>
	<?php
}

// ============================================================================
// HEALTHCARE PROFESSIONALS — Resources, Collaborate, CTA
// ============================================================================

function vance_render_section_hcp_resources() {
	$res_tag        = vance_get_theme_mod( 'vance_hcp_res_tag',        'Join the Effort' );
	$res_title      = vance_get_theme_mod( 'vance_hcp_res_title',      "What You'll Access" );
	$res_desc       = vance_get_theme_mod( 'vance_hcp_res_desc',       'We invite passionate healthcare practitioners to join us in advancing clinical nutrition. Share your expertise and help shape the future of specialized healthcare content.' );
	$res_tag_bg     = vance_get_theme_mod( 'vance_hcp_res_tag_bg',     '' );
	$res_tag_color  = vance_get_theme_mod( 'vance_hcp_res_tag_color',  '' );
	$res_tag_border = vance_get_theme_mod( 'vance_hcp_res_tag_border', '' );
	$res_tag_style  = '';
	if ( $res_tag_bg )     { $res_tag_style .= 'background:' . esc_attr( $res_tag_bg ) . ';'; }
	if ( $res_tag_color )  { $res_tag_style .= 'color:' . esc_attr( $res_tag_color ) . ';'; }
	if ( $res_tag_border ) { $res_tag_style .= 'border-color:' . esc_attr( $res_tag_border ) . ';'; }

	$res_defaults = array(
		1 => array( 'Clinical Protocols',  'Step-by-step treatment algorithms for common and complex GI conditions, including FSMP integration.' ),
		2 => array( 'Research Summaries',  'Curated abstracts and commentary on the latest Omega-3, gut microbiome, and longevity research.' ),
		3 => array( 'Webinars & CME',      'On-demand educational sessions with CPD accreditation from leading gastroenterology experts.' ),
		4 => array( 'Patient Handouts',    'Downloadable, branded resources to share with patients to reinforce dietary and treatment advice.' ),
	);
	$res_colors = array(
		1 => array( '#008080', '#006666', '#ffffff' ),
		2 => array( '#78bfbf', '#5fa3a3', '#ffffff' ),
		3 => array( '#aedbdb', '#88c5c5', '#008080' ),
		4 => array( '#def4f4', '#aedbdb', '#008080' ),
	);
	$res_icons = array(
		1 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
		2 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>',
		3 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>',
		4 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
	);
	?>
	<section id="resources" class="section-padding" style="background: var(--accent-color);">
		<div class="container">
			<div class="text-center max-600 margin-b-60">
				<span class="tag-section" style="<?php echo $res_tag_style; ?>"><?php echo esc_html( $res_tag ); ?></span>
				<h2 style="color: var(--secondary-color);"><?php echo esc_html( $res_title ); ?></h2>
				<p style="color: var(--text-light);"><?php echo esc_html( $res_desc ); ?></p>
			</div>
			<div class="grid-2 resource-grid">
				<?php for ( $i = 1; $i <= 4; $i++ ) :
					$card_title = vance_get_theme_mod( "vance_hcp_res{$i}_title", $res_defaults[ $i ][0] );
					$card_desc  = vance_get_theme_mod( "vance_hcp_res{$i}_desc",  $res_defaults[ $i ][1] );
				?>
				<div class="hcp-resource-card" style="display: flex; gap: 20px; padding: 32px; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
					<div class="hcp-resource-icon" style="flex-shrink: 0; width: 56px; height: 56px; background: linear-gradient(135deg, <?php echo esc_attr( $res_colors[ $i ][0] ); ?>, <?php echo esc_attr( $res_colors[ $i ][1] ); ?>); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
						<svg width="28" height="28" fill="none" stroke="<?php echo esc_attr( $res_colors[ $i ][2] ); ?>" viewBox="0 0 24 24"><?php echo $res_icons[ $i ]; ?></svg>
					</div>
					<div>
						<h4 style="font-size: 18px; color: var(--secondary-color); margin-bottom: 8px;"><?php echo esc_html( $card_title ); ?></h4>
						<p style="color: var(--text-light); font-size: 14px; margin: 0;"><?php echo esc_html( $card_desc ); ?></p>
					</div>
				</div>
				<?php endfor; ?>
			</div>
		</div>
	</section>
	<?php
}

function vance_render_section_hcp_collaborate() {
	$collab_title = vance_get_theme_mod( 'vance_hcp_collab_title', 'Collaborate with SLA Pharma' );
	$collab_defaults = array(
		1 => array( 'Submit Articles',  'Publish your clinical insights and case studies to our global network of peers.' ),
		2 => array( 'Co-Author Content','Partner with our medical writing team to develop robust, evidence-based clinical guides.' ),
		3 => array( 'Podcast Guest',    'Join our clinical podcast series to discuss innovations, challenges, and success stories.' ),
		4 => array( 'Clinical Trials',  'Work with us on our pipeline of clinical and in-market trials investigating novel specific treatments.' ),
	);
	$collab_colors = array(
		1 => '#008080',
		2 => '#78bfbf',
		3 => '#aedbdb',
		4 => '#def4f4',
	);
	?>
	<section class="section-padding" style="background: white;">
		<div class="container">
			<h2 class="text-center" style="color: var(--secondary-color); margin-bottom: 40px;"><?php echo esc_html( $collab_title ); ?></h2>
			<div class="grid-4 service-grid">
				<?php for ( $i = 1; $i <= 4; $i++ ) :
					$col_title = vance_get_theme_mod( "vance_hcp_col{$i}_title", $collab_defaults[ $i ][0] );
					$col_desc  = vance_get_theme_mod( "vance_hcp_col{$i}_desc",  $collab_defaults[ $i ][1] );
				?>
				<div class="hcp-collab-card" style="padding: 32px 24px; background: var(--accent-color); border-radius: var(--radius-lg); text-align: center; border-top: 4px solid <?php echo esc_attr( $collab_colors[ $i ] ); ?>;">
					<h4 style="font-size: 17px; color: var(--secondary-color); margin-bottom: 12px;"><?php echo esc_html( $col_title ); ?></h4>
					<p style="font-size: 13px; color: var(--text-light); margin: 0;"><?php echo esc_html( $col_desc ); ?></p>
				</div>
				<?php endfor; ?>
			</div>
		</div>
	</section>
	<?php
}

function vance_render_section_hcp_cta() {
	$cta_title = vance_get_theme_mod( 'vance_hcp_cta_title', 'Join the Professional Network' );
	$cta_desc  = vance_get_theme_mod( 'vance_hcp_cta_desc',  'Free registration gives you full access to protocols, research, and CME opportunities.' );
	?>
	<section id="register" class="section-padding hcp-cta-section" style="background: linear-gradient(135deg, #008080, #006666);">
		<div class="container" style="text-align: center; color: white;">
			<h2 style="color: white; margin-bottom: 16px;"><?php echo esc_html( $cta_title ); ?></h2>
			<p class="max-600" style="font-size: 18px; margin-bottom: 32px; color: rgba(255,255,255,0.85);"><?php echo esc_html( $cta_desc ); ?></p>
			<form class="hcp-register-form" style="display: flex; gap: 12px; max-width: 600px; margin: 0 auto; flex-wrap: wrap; justify-content: center;">
				<input type="email" placeholder="Professional email" class="form-input" style="flex: 1; min-width: 200px; padding: 14px 20px; border: none; border-radius: var(--radius-md); font-size: 16px;">
				<select style="padding: 14px 20px; border: none; border-radius: var(--radius-md); font-size: 16px; background: white;">
					<option>Select Role</option>
					<option>Gastroenterologist</option>
					<option>Dietitian</option>
					<option>GP / PCP</option>
					<option>Pharmacist</option>
					<option>Nurse</option>
					<option>Other HCP</option>
				</select>
				<button type="submit" class="btn btn-primary">Register Free</button>
			</form>
		</div>
	</section>
	<?php
}

// ============================================================================
// Registry — register all cross-page blocks with the homepage section control.
// ============================================================================

add_filter( 'vance_homepage_sections', function ( $sections ) {
	$cross = array(
		'patients-benefits' => array( 'label' => 'Patient: Benefits (3 cards)',    'group' => 'Patients',  'render' => 'vance_render_section_patients_benefits' ),
		'patients-tools'    => array( 'label' => 'Patient: Innovative Tools',     'group' => 'Patients',  'render' => 'vance_render_section_patients_tools'    ),
		'patients-cta'      => array( 'label' => 'Patient: Subscribe CTA',        'group' => 'Patients',  'render' => 'vance_render_section_patients_cta'      ),
		'hcp-resources'     => array( 'label' => 'HCP: Resources (4 cards)',      'group' => 'HCP',       'render' => 'vance_render_section_hcp_resources'     ),
		'hcp-collaborate'   => array( 'label' => 'HCP: Collaborate (4 cards)',    'group' => 'HCP',       'render' => 'vance_render_section_hcp_collaborate'   ),
		'hcp-cta'           => array( 'label' => 'HCP: Register CTA (form)',      'group' => 'HCP',       'render' => 'vance_render_section_hcp_cta'           ),
	);
	return array_merge( $sections, $cross );
} );
