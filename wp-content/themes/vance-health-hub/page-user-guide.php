<?php
/**
 * Template Name: User Guide
 *
 * Public-facing walkthrough of the whole product: onboarding, the Content
 * Discovery Suite, VANCE-Ai, the free health tools, and My Dashboard (the
 * hub every other feature feeds into). Screenshots and GIFs live in
 * /assets/img/user-guide/ and were captured from the live site logged in
 * as a dedicated test account.
 *
 * To activate: create a Page titled "User Guide", slug `user-guide`, and
 * choose "User Guide" as the template (Page Attributes).
 *
 * Hero tag/title/description are read through vance_get_theme_mod() with
 * the defaults below as fallback, editable at Appearance → Customize →
 * Page - User Guide → Hero Section (controls registered in
 * customizer-pages.php). Everything else on the page (steps, journey,
 * tools, dashboard tabs) is intentionally not theme-mod-driven — it mirrors
 * real product data (inc/dashboard-features.php, the live tool list) and
 * editing it from the admin would risk drifting from what the site
 * actually ships.
 */
get_header();

$img_base = get_template_directory_uri() . '/assets/img/user-guide/';

// Signed-in visitors are already members — "Join For Free" makes no sense
// for them and the sitewide signup modal correctly no-ops when logged in
// (same as header.php's own My Dashboard link), so route straight to the
// dashboard instead and skip the modal-intercept id entirely.
$vug_is_logged_in = is_user_logged_in();
$vug_join_url      = $vug_is_logged_in ? home_url( '/dashboard/' ) : home_url( '/login/?tab=signup' );

/**
 * The 11 My Dashboard tabs, sourced from the real feature registry in
 * inc/dashboard-features.php so this copy can't drift from what the
 * product actually ships. Descriptions are lightly reworded for a
 * user-facing tone; the facts (what each tab does, on/off-by-default)
 * are unchanged from the registry.
 */
$dashboard_tabs = array(
	array( 'icon' => '📊', 'label' => 'Dashboard',      'desc' => 'Your home base — a snapshot of saved tools, recent notes, unread messages and your Health Profile progress.' ),
	array( 'icon' => '👤', 'label' => 'My Profile',      'desc' => 'Your name, avatar, bio and contact details.' ),
	array( 'icon' => '🩺', 'label' => 'Health Profile',  'desc' => 'Your health discovery results and clinical profile questionnaire — this is what personalises content across the site.' ),
	array( 'icon' => '🧮', 'label' => 'My Tools',        'desc' => 'Every saved calculator result (Malnutrition Calculator, Gastro Health Survey), so you can track changes over time.' ),
	array( 'icon' => '📚', 'label' => 'My Reading List', 'desc' => 'Articles you\'ve bookmarked to come back to.' ),
	array( 'icon' => '🎓', 'label' => 'My Courses',      'desc' => 'Learning content you\'ve enrolled in.' ),
	array( 'icon' => '🔍', 'label' => 'My Searches',     'desc' => 'Saved Discovery Suite searches, one click from your last filter.' ),
	array( 'icon' => '📝', 'label' => 'My Notes',        'desc' => 'Private notes only you can see — jot down questions for your next appointment.' ),
	array( 'icon' => '🤖', 'label' => 'My VANCE-Ai',     'desc' => 'Every saved conversation with VANCE-Ai, so answers stay findable later.' ),
	array( 'icon' => '💬', 'label' => 'My Messages',     'desc' => 'Broadcast messages from the Vance Medical team.' ),
	array( 'icon' => '📄', 'label' => 'My Documents',    'desc' => 'Upload letters, results and care plans, then ask VANCE-Ai about them directly. Off by default — enabled deliberately per site.' ),
);

/**
 * Render a still screenshot as an "animated shot": a slow ambient zoom +
 * light sweep on the base image, plus 1-2 floating content badges that
 * animate in on scroll. The captured screenshots came from a fresh test
 * account (mostly empty states — no saved articles/notes yet), so the
 * badges layer in example content as toast-style overlays rather than
 * trying to edit the underlying pixels. Reuses the existing .vug-reveal
 * scroll-observer in user-guide.js by carrying that class directly.
 *
 * @param string $img_base Base URL for /assets/img/user-guide/.
 * @param string $filename Image filename.
 * @param string $alt      Alt text.
 * @param string $caption  Figcaption text.
 * @param array  $badges   List of array('icon','text','pos','delay').
 * @param float  $delay    Entrance delay for the figure itself (stagger across a row).
 */
/**
 * The downloadable guide, and the human-readable weight of it.
 *
 * The file size is read off disk so the label can never drift from the actual
 * PDF; the page count is the one fact the file cannot tell us cheaply, so it
 * lives here as a constant and is updated whenever the PDF is rebuilt
 * (build toolchain: LOCAL/user-guide-pdf/).
 */
define( 'VUG_PDF_FILE',  'Vance-Health-Hub-User-Guide.pdf' );
define( 'VUG_PDF_PAGES', 23 );

/**
 * "23 pages · PDF · 5.7 MB" — or just "PDF" if the file is missing, so a
 * failed deploy degrades to a plain label rather than a PHP warning.
 *
 * @return string
 */
function vug_pdf_meta() {
	$path = get_template_directory() . '/assets/downloads/' . VUG_PDF_FILE;
	if ( ! file_exists( $path ) ) {
		return 'PDF';
	}
	// Decimal MB, matching what the browser's own download shelf reports.
	$mb = filesize( $path ) / 1000000;
	return sprintf(
		/* translators: 1: page count, 2: file size in MB */
		__( '%1$d pages · PDF · %2$s MB', 'vance-health-hub' ),
		VUG_PDF_PAGES,
		number_format_i18n( $mb, 1 )
	);
}

/**
 * The download call to action, offered at each point a reader is likely to
 * want to keep the guide rather than keep scrolling: the hero, mid-page after
 * the tools, and the closing CTA. The sticky sub-nav used to carry a fourth
 * copy as a filled 'pill'; that was removed so the bar holds section anchors
 * only, and the variant went with it.
 *
 * All variants point at the same file and carry `download` so the browser
 * saves it instead of opening a viewer tab.
 *
 * @param string $pdf_url URL of the PDF.
 * @param string $variant 'hero' | 'solid' | 'onteal'.
 * @param string $label   Button text.
 * @return string
 */
function vug_download_btn( $pdf_url, $variant = 'solid', $label = '' ) {
	$label   = $label ? $label : __( 'Download User Guide', 'vance-health-hub' );
	$classes = array(
		'hero'   => 'btn btn-outline vug-dl',
		'solid'  => 'btn btn-primary vug-dl',
		'onteal' => 'btn vug-dl vug-dl--onteal',
	);
	$class = isset( $classes[ $variant ] ) ? $classes[ $variant ] : $classes['solid'];

	return sprintf(
		'<a href="%1$s" class="%2$s" download><span class="vug-dl__icon" aria-hidden="true">%3$s</span><span>%4$s</span></a>',
		esc_url( $pdf_url ),
		esc_attr( $class ),
		// Inline so it needs no sprite and inherits currentColor.
		'<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
		esc_html( $label )
	);
}

function vug_anim_shot( $img_base, $filename, $alt, $caption, $badges = array(), $delay = 0 ) {
	ob_start();
	?>
	<figure class="vug-media vug-anim-shot vug-reveal" data-vug-lightbox style="--vug-delay: <?php echo esc_attr( $delay ); ?>s;">
		<span class="vug-anim-shot__frame">
			<img src="<?php echo esc_url( $img_base . $filename ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy">
		</span>
		<?php foreach ( $badges as $badge ) : ?>
		<span class="vug-badge vug-badge--<?php echo esc_attr( $badge['pos'] ); ?>" style="--vug-badge-delay: <?php echo esc_attr( $badge['delay'] ); ?>s;">
			<?php echo esc_html( trim( $badge['text'] ) ); ?>
		</span>
		<?php endforeach; ?>
		<figcaption><?php echo esc_html( $caption ); ?></figcaption>
	</figure>
	<?php
	return ob_get_clean();
}

$tools = array(
	array(
		'slug'   => 'healthcare-quiz',
		'url'    => '/gastro-health-survey/',
		'name'   => 'Gastro Health Survey',
		'tag'    => 'Self-Assessment',
		'desc'   => 'A short, evidence-based questionnaire covering symptom patterns, dietary triggers and lifestyle factors. Get an instant summary you can share with your clinician.',
		'img'    => 'healthcare-quiz-start.png',
		'badge_text' => '9 quick questions',
	),
	array(
		'slug'   => 'ibd-recipes',
		'url'    => '/gastro-meal-planner/',
		'name'   => 'IBD Recipes &amp; Meal Planner',
		'tag'    => 'Meal Planning',
		'desc'   => 'Browse EPA-rich, gut-friendly recipes with full nutrition data and build a weekly meal plan.',
		'img'    => 'ibd-recipes-browse.png',
		'badge_text' => '19 recipes ready',
	),
	array(
		'slug'   => 'malnutrition-calculator',
		'url'    => '/malnutrition-calculator/',
		'name'   => 'Malnutrition Calculator',
		'tag'    => 'IBD Screening',
		'desc'   => 'An 11-step malnutrition risk screener combining MUST, IBD-NST and GLIM criteria into a single, actionable score.',
		'img'    => 'malnutrition-calculator-form.png',
		'badge_text' => '11-step screener',
	),
);
?>

<main id="main-content" class="user-guide-page">

	<!-- HERO (same "patients-hero" structure as page-tools-resources.php: full-bleed
	     background image + gradient overlay, tag-label/h1/p, single hero-actions button) -->
	<?php
	$hero_bg      = vance_get_theme_mod( 'vance_userguide_hero_bg', get_template_directory_uri() . '/assets/img/education_hero.png' );
	$hero_tag     = vance_get_theme_mod( 'vance_userguide_hero_tag', 'User Guide' );
	$hero_title   = vance_get_theme_mod( 'vance_userguide_hero_title', 'Get the most out of <span class="highlight">Vance Medical Hub</span>' );
	$hero_desc    = vance_get_theme_mod( 'vance_userguide_hero_desc', 'Vance Health Hub is built to be the credible source you turn to at every step of your healthcare journey — evidence-based research, clinically-grounded tools, and a private dashboard that keeps your data, notes and AI conversations in one place. This guide shows you how it all fits together.' );
	$hero_overlay = max( 0, min( 100, absint( vance_get_theme_mod( 'vance_userguide_hero_overlay', 70 ) ) ) ) / 100;
	$hero_overlay_bottom = min( 1, $hero_overlay + 0.15 );
	$pdf_url      = get_template_directory_uri() . '/assets/downloads/' . VUG_PDF_FILE;
	$pdf_meta     = vug_pdf_meta();
	?>
	<section class="hero userguide-hero" style="padding: 72px 0 116px; min-height: 332px; display: flex; align-items: center; background: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $hero_overlay ); ?>), rgba(10,25,41,<?php echo esc_attr( $hero_overlay_bottom ); ?>)), url('<?php echo esc_url( $hero_bg ); ?>') no-repeat center center; background-size: cover;">
		<div class="container">
			<div class="hero-content">
				<span class="tag-label"><?php echo esc_html( $hero_tag ); ?></span>
				<h1><?php echo wp_kses_post( $hero_title ); ?></h1>
				<p><?php echo esc_html( $hero_desc ); ?></p>
				<div class="hero-actions" style="margin-top: 24px;">
					<?php echo vug_download_btn( $pdf_url, 'hero' ); ?>
					<span class="vug-dl__meta vug-dl__meta--hero"><?php echo esc_html( $pdf_meta ); ?></span>
				</div>
			</div>
		</div>
	</section>

	<!-- IN-PAGE NAV (same sticky pill-nav structure as the GI condition pages' .gi-cp-nav —
	     replicated locally in user-guide.css rather than loading gi-health.css on this page) -->
	<nav class="vug-subnav" aria-label="Guide sections">
		<ul>
			<li><a href="#your-journey">Your Journey</a></li>
			<li><a href="#onboarding">Getting Started</a></li>
			<li><a href="#discovery-suite">Discovery Suite</a></li>
			<li><a href="#vance-ai">VANCE-Ai</a></li>
			<li><a href="#free-tools">Free Health Tools</a></li>
			<li><a href="#my-dashboard" class="vug-subnav__highlight">My Dashboard</a></li>
		</ul>
	</nav>

	<!-- ============ YOUR HEALTHCARE JOURNEY (credibility + framing) ============ -->
	<section id="your-journey" class="vug-section vug-section--alt">
		<div class="container">
			<div class="vug-section__intro vug-reveal">
				<span class="vug-eyebrow">A Trusted Foundation</span>
				<h2>Built on evidence, organised around your healthcare journey</h2>
				<p class="max-600">Vance Health Hub exists to put clinically-reviewed information and clinically-grounded tools in your hands — not vague advice from an anonymous forum. Articles are clinically reviewed, the calculators are built on validated frameworks (MUST, IBD-NST, GLIM), and every VANCE-Ai answer comes with its sources attached. The rest of this guide follows the same four steps most people move through:</p>
			</div>
			<div class="vug-journey">
				<div class="vug-journey__step vug-reveal" style="--vug-delay: 0s;">
					<span class="vug-journey__num">1</span>
					<h3>Research</h3>
					<p>Use the Discovery Suite to find clinically-reviewed articles and recipes filtered to your exact condition and topic — not a generic search engine result.</p>
				</div>
				<div class="vug-journey__step vug-reveal" style="--vug-delay: 0.08s;">
					<span class="vug-journey__num">2</span>
					<h3>Personalise</h3>
					<p>Complete your Health Profile once during onboarding, and the articles, tools and recommendations across the whole site adjust to you.</p>
				</div>
				<div class="vug-journey__step vug-reveal" style="--vug-delay: 0.16s;">
					<span class="vug-journey__num">3</span>
					<h3>Ask</h3>
					<p>Get a sourced, plain-language answer from VANCE-Ai the moment a question comes up, and keep the conversation for later.</p>
				</div>
				<div class="vug-journey__step vug-reveal" style="--vug-delay: 0.24s;">
					<span class="vug-journey__num">4</span>
					<h3>Track &amp; Share</h3>
					<p>Run a calculator or quiz whenever things change, save every result and note to My Dashboard, and bring the record to your next appointment.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ ONBOARDING ============ -->
	<section id="onboarding" class="vug-section">
		<div class="container">
			<div class="vug-row vug-reveal">
				<div class="vug-row__media">
					<figure class="vug-media" data-vug-lightbox>
						<img src="<?php echo esc_url( $img_base . 'onboarding-to-dashboard.gif' ); ?>" alt="Walkthrough: joining Vance Medical Hub and landing on My Dashboard" loading="lazy">
						<figcaption>Joining takes under a minute — click to watch</figcaption>
					</figure>
					<div class="vug-shot-strip">
						<?php
						echo vug_anim_shot( $img_base, 'home-hero.png', 'Vance Medical Hub homepage', 'Homepage', array(
							array( 'text' => 'Free to join', 'pos' => 'top-right', 'delay' => 0.2 ),
						) );
						echo vug_anim_shot( $img_base, 'nav-menu.png', 'Main navigation showing the VANCE-AI button and My Dashboard link', 'Main navigation', array(
							array( 'text' => 'VANCE-Ai · always on', 'pos' => 'top-right', 'delay' => 0.35 ),
						) );
						?>
					</div>
				</div>
				<div class="vug-row__text">
					<span class="vug-eyebrow">Step 1</span>
					<h2>Getting started in under 2 minutes</h2>
					<ol class="vug-steps">
						<li><strong>Create your free account.</strong> Click <em>“Join For Free”</em> from the homepage or the footer — no payment details, ever.</li>
						<li><strong>Land straight in My Dashboard.</strong> Confirming your account takes you directly to your personal dashboard, not a generic welcome screen.</li>
						<li><strong>Complete your Health Profile.</strong> A few quick questions personalise the articles, tools and recommendations you see across the whole site.</li>
					</ol>
					<p class="vug-note">From that point on, Vance Health Hub isn't a generic health website — it's tuned to your condition, so the research, tools and AI answers you see are actually relevant to your own healthcare journey.</p>
					<a href="<?php echo esc_url( $vug_join_url ); ?>" class="btn btn-primary"<?php echo $vug_is_logged_in ? '' : ' id="vug-onboarding-join-btn"'; ?>>Join For Free</a>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ DISCOVERY SUITE ============ -->
	<section id="discovery-suite" class="vug-section vug-section--alt">
		<div class="container">
			<div class="vug-row vug-row--reverse vug-reveal">
				<div class="vug-row__media">
					<figure class="vug-media" data-vug-lightbox>
						<img src="<?php echo esc_url( $img_base . 'discovery-suite-flow.gif' ); ?>" alt="Filtering content with the Content Discovery Suite" loading="lazy">
						<figcaption>Filtering the Hub by condition and topic — click to watch</figcaption>
					</figure>
					<div class="vug-shot-strip">
						<?php
						echo vug_anim_shot( $img_base, 'discovery-suite-search.png', 'Discovery Results page filtered by keyword and condition', 'Filtered results', array(
							array( 'text' => '12 results found', 'pos' => 'top-right', 'delay' => 0.2 ),
						) );
						?>
					</div>
				</div>
				<div class="vug-row__text">
					<span class="vug-eyebrow">Find what matters to you</span>
					<h2>The Content Discovery Suite</h2>
					<p>Instead of scrolling an endless archive, the Discovery Suite on the homepage lets you filter every article, recipe and resource by condition, topic and content type at once. Results update as you filter, and any search you like can be saved to <strong>My Searches</strong> in your dashboard for one-click access next time.</p>
					<p>Because every result comes from clinically-reviewed content rather than the open web, you can trust what you find enough to actually bring it to your next appointment — that's the difference between researching your condition and just searching for it.</p>
					<a href="/#discovery-suite" class="btn btn-outline">Try the Discovery Suite</a>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ VANCE-AI ============ -->
	<section id="vance-ai" class="vug-section">
		<div class="container">
			<div class="vug-row vug-reveal">
				<div class="vug-row__media">
					<figure class="vug-media" data-vug-lightbox>
						<img src="<?php echo esc_url( $img_base . 'vance-ai-flow.gif' ); ?>" alt="Asking VANCE-Ai a health question and receiving an answer" loading="lazy">
						<figcaption>Ask a question, get a sourced answer — click to watch</figcaption>
					</figure>
					<div class="vug-shot-strip">
						<?php
						echo vug_anim_shot( $img_base, 'vance-ai-chat.png', 'VANCE-Ai chat with a cited answer', 'Cited answer', array(
							array( 'text' => 'Answer sourced', 'pos' => 'top-right', 'delay' => 0.2 ),
						) );
						?>
					</div>
				</div>
				<div class="vug-row__text">
					<span class="vug-eyebrow">Your AI health assistant</span>
					<h2>Ask VANCE-Ai anything</h2>
					<p>The <strong>VANCE-Ai</strong> button lives in the header on every page — open it any time to ask a question in plain language. On articles, you can also highlight any passage of text and ask VANCE-Ai about it directly. Every conversation is saved automatically to <strong>My VANCE-Ai</strong> in your dashboard, so nothing gets lost.</p>
					<p>Because every answer is sourced rather than invented, you can check where it came from yourself, or bring the saved conversation straight to your clinician instead of trying to remember what an AI told you weeks ago.</p>
					<a href="/ask-ai/" class="btn btn-primary" data-vance-askai-open aria-haspopup="dialog">Chat with VANCE-Ai</a>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ FREE HEALTH TOOLS ============ -->
	<section id="free-tools" class="vug-section vug-section--alt">
		<div class="container">
			<div class="vug-section__intro vug-reveal">
				<span class="vug-eyebrow">Open Access</span>
				<h2>Free health tools, no signup required</h2>
				<p class="max-600">Every clinical tool is free to use on the spot. Register only if you want your results saved automatically to your dashboard. Each one is built on a validated clinical scoring framework, so you get a real number to act on instead of a guess — and because every result is saved with a date, running the same tool again later shows you a trend, not just a snapshot.</p>
			</div>
			<div class="vug-tools-grid">
				<?php foreach ( $tools as $i => $tool ) : ?>
				<figure class="vug-tool-card vug-anim-shot vug-reveal" data-vug-lightbox style="--vug-delay: <?php echo esc_attr( $i * 0.1 ); ?>s;">
					<span class="vug-anim-shot__frame">
						<img src="<?php echo esc_url( $img_base . $tool['img'] ); ?>" alt="<?php echo esc_attr( wp_strip_all_tags( $tool['name'] ) ); ?> screenshot" loading="lazy">
					</span>
					<span class="vug-badge vug-badge--top-right" style="--vug-badge-delay: <?php echo esc_attr( 0.3 + $i * 0.15 ); ?>s;">
						<?php echo esc_html( trim( $tool['badge_text'] ) ); ?>
					</span>
					<figcaption>
						<span class="vug-tool-card__tag"><?php echo esc_html( $tool['tag'] ); ?></span>
						<h3><?php echo wp_kses_post( $tool['name'] ); ?></h3>
						<p><?php echo esc_html( $tool['desc'] ); ?></p>
						<a href="<?php echo esc_url( $tool['url'] ); ?>">Open <?php echo wp_kses_post( $tool['name'] ); ?> →</a>
					</figcaption>
				</figure>
				<?php endforeach; ?>
			</div>
			<div class="vug-shot-strip vug-shot-strip--center">
				<?php
				echo vug_anim_shot( $img_base, 'healthcare-quiz-results.png', 'Gastro Health Survey completion screen', 'Quiz result', array(
					array( 'text' => 'Profile updated', 'pos' => 'top-right', 'delay' => 0.2 ),
				) );
				echo vug_anim_shot( $img_base, 'malnutrition-calculator-result.png', 'Malnutrition Calculator risk result', 'Calculator result', array(
					array( 'text' => 'Medium risk flagged', 'pos' => 'top-right', 'delay' => 0.35 ),
				) );
				?>
			</div>
		</div>
	</section>

	<!-- ============ DOWNLOAD BAND ============
	     Mid-scroll, straight after the free tools: the point where a reader has
	     seen enough to want to keep the guide rather than re-find this page. -->
	<section class="vug-dl-band">
		<div class="container">
			<div class="vug-dl-band__inner vug-reveal">
				<div class="vug-dl-band__text">
					<span class="vug-eyebrow vug-eyebrow--light"><?php esc_html_e( 'Take it with you', 'vance-health-hub' ); ?></span>
					<h2><?php esc_html_e( 'The whole guide, as a PDF', 'vance-health-hub' ); ?></h2>
					<p><?php esc_html_e( 'Everything on this page — every tool, every screenshot, plus a first-week plan and an appointment prep checklist — in one file you can keep on your phone or print for your next appointment.', 'vance-health-hub' ); ?></p>
				</div>
				<div class="vug-dl-band__action">
					<?php echo vug_download_btn( $pdf_url, 'onteal' ); ?>
					<span class="vug-dl__meta"><?php echo esc_html( $pdf_meta ); ?></span>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ MY DASHBOARD (centrepiece) ============ -->
	<section id="my-dashboard" class="vug-section vug-dashboard-section">
		<div class="container">
			<div class="vug-section__intro vug-reveal">
				<span class="vug-eyebrow">Your health command centre</span>
				<h2>My Dashboard ties everything together</h2>
				<p class="max-600">Every tool result, saved article, AI conversation and note in one private place. It's where onboarding leads, and where you'll spend most of your time — the difference between researching your condition once and actually managing it over time.</p>
			</div>

			<ul class="vug-benefits vug-reveal">
				<li><strong>Never re-explain your history.</strong> Your Health Profile, notes and saved results are there every time you log in — no starting from zero with a new clinician or a new appointment.</li>
				<li><strong>See real patterns, not memories.</strong> Run the same calculator or quiz again after a flare-up or a diet change and compare it against your last result instead of relying on how you think you felt.</li>
				<li><strong>Walk into appointments prepared.</strong> Open My Dashboard on your phone in the waiting room and show your clinician actual numbers, saved articles and notes — not a vague description.</li>
			</ul>

			<div class="vug-row vug-reveal">
				<div class="vug-row__media">
					<figure class="vug-media" data-vug-lightbox>
						<img src="<?php echo esc_url( $img_base . 'my-dashboard-flow.gif' ); ?>" alt="Navigating between My Dashboard sections" loading="lazy">
						<figcaption>Moving between dashboard sections — click to watch</figcaption>
					</figure>
				</div>
				<div class="vug-row__text">
					<span class="vug-eyebrow">Overview</span>
					<h2>Everything, one click away</h2>
					<p>The dashboard sidebar groups every feature into three areas — your health record, your learning, and your communication — so nothing is ever more than one click from the home tab.</p>
					<a href="/dashboard/" class="btn btn-primary">Open My Dashboard</a>
				</div>
			</div>

			<div class="vug-dash-grid">
				<?php foreach ( $dashboard_tabs as $i => $tab ) : ?>
				<div class="vug-dash-card vug-reveal" style="--vug-delay: <?php echo esc_attr( ( $i % 4 ) * 0.08 ); ?>s;">
					<span class="vug-dash-card__icon" aria-hidden="true"><?php echo esc_html( $tab['icon'] ); ?></span>
					<h3><?php echo esc_html( $tab['label'] ); ?></h3>
					<p><?php echo esc_html( $tab['desc'] ); ?></p>
				</div>
				<?php endforeach; ?>
			</div>

			<div class="vug-dash-shots">
				<?php
				echo vug_anim_shot( $img_base, 'my-dashboard-overview.png', 'My Dashboard overview', 'Dashboard', array(
					array( 'text' => '3 updates', 'pos' => 'top-right', 'delay' => 0.15 ),
				) );
				echo vug_anim_shot( $img_base, 'my-dashboard-profile.png', 'My Profile section of My Dashboard', 'My Profile', array(
					array( 'text' => 'Profile saved', 'pos' => 'top-right', 'delay' => 0.3 ),
				) );
				// "show saved articles on the My Dashboard screenshot" — the captured
				// state is a fresh test account (empty Reading List), so two example
				// saved-article toasts animate in over it to show what a populated
				// list looks like without editing the underlying screenshot pixels.
				echo vug_anim_shot( $img_base, 'my-dashboard-bookmarks.png', 'My Reading List section of My Dashboard', 'My Reading List', array(
					array( 'text' => 'IBD Flare Nutrition Checklist — saved', 'pos' => 'top-right', 'delay' => 0.2 ),
					array( 'text' => 'The Malabsorption Diet Explained — saved', 'pos' => 'bottom-left', 'delay' => 0.75 ),
				), 0.45 );
				echo vug_anim_shot( $img_base, 'my-dashboard-notes.png', 'My Notes section of My Dashboard', 'My Notes', array(
					array( 'text' => '"Ask GP about iron levels" — added', 'pos' => 'bottom-left', 'delay' => 0.3 ),
				), 0.6 );
				?>
			</div>
		</div>
	</section>

	<!-- ============ CLOSING CTA ============ -->
	<section class="section-padding vug-cta-section">
		<div class="container" style="text-align: center; color: white;">
			<h2 style="color: white; margin-bottom: 16px;">Ready to see your own dashboard?</h2>
			<p class="max-600" style="font-size: 18px; margin: 0 auto 32px; color: rgba(255,255,255,0.92);">
				Free registration takes under a minute and unlocks every feature in this guide.
			</p>
			<div class="hero-actions" style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
				<a href="<?php echo esc_url( $vug_join_url ); ?>" class="btn btn-primary"<?php echo $vug_is_logged_in ? '' : ' id="vug-cta-join-btn"'; ?> style="background: white; color: #008080; border: none;">Register Free</a>
				<?php echo vug_download_btn( $pdf_url, 'onteal' ); ?>
				<?php if ( ! $vug_is_logged_in ) : ?>
				<a href="/login/" class="btn btn-outline" style="border-color: rgba(255,255,255,0.4); color: white;">Already have an account? Sign in</a>
				<?php endif; ?>
			</div>
			<p class="vug-cta-section__meta"><?php echo esc_html( $pdf_meta ); ?></p>
		</div>
	</section>

	<!-- LIGHTBOX (populated by user-guide.js) -->
	<div class="vug-lightbox" id="vug-lightbox" hidden>
		<button type="button" class="vug-lightbox__close" aria-label="Close">&times;</button>
		<img src="" alt="" id="vug-lightbox__img">
	</div>

</main>

<script>
// Open the sitewide quick-signup overlay (inc/register-modal.php, loaded globally
// via footer.php) instead of navigating to /login/?tab=signup — same pattern as
// page-turn-evidence-into-action.php. Href stays as a progressive-enhancement
// fallback for no-JS / VanceRegisterModal unavailable.
(function () {
    [ 'vug-onboarding-join-btn', 'vug-cta-join-btn' ].forEach( function ( id ) {
        var btn = document.getElementById( id );
        if ( ! btn ) { return; }
        btn.addEventListener( 'click', function ( e ) {
            if ( window.VanceRegisterModal && typeof window.VanceRegisterModal.open === 'function' ) {
                e.preventDefault();
                window.VanceRegisterModal.open( {} );
            }
        } );
    } );
})();
</script>

<?php get_footer(); ?>
