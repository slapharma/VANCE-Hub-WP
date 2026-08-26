<?php
/**
 * Homepage hero — "spotlight" layout.
 *
 * The light, search-led hero from the 2026-08 homepage mockup: a pale mint
 * gradient band with the headline, intro, two CTAs and a prominent search
 * field on the left, a lifestyle photograph dissolving into the background on
 * the right, and a small trust card floating over the photograph's lower
 * corner.
 *
 * This does NOT replace inc/hero-carousel.php. Both renderers stay registered
 * and `vance_hero_style` picks between them, so the previous dark hero (and
 * every one of its saved slide settings) is one Customizer toggle away.
 *
 * Field/values/render split mirrors inc/hero-carousel.php so the Customizer
 * registration loop in functions.php can drive both from one field list.
 *
 * @package vance-health-hub
 * @since   2026-08-26
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Per-field defaults.
 *
 * The headline, intro and both CTAs are the exception to "the mockup's copy":
 * they default to whatever the CLASSIC hero is currently displaying, via
 * vance_hero_inherited_copy(). Switching designs is then a change of layout
 * only — nothing the homepage says, and nowhere it sends people, moves
 * underneath anyone, and no admin has to re-type copy that is already saved.
 *
 * A CTA link only auto-resolves to the conditions hub / Knowledgebase when the
 * inherited value is empty too — see vance_hero_spotlight_values(). Hard-coding
 * `/gastro-health-explained/` here would bake in a slug that has already
 * drifted once on the live site.
 *
 * @return array<string, mixed> field => default
 */
function vance_hero_spotlight_field_defaults() {
	$inherited = vance_hero_inherited_copy();

	return array(
		'image'             => '',
		'image_alt'         => 'A couple sitting together at a kitchen table over coffee',
		'bg_from'           => '#ECF5F5',
		'bg_to'             => '#F6F9FA',
		'title'             => $inherited['title'],
		'title_color'       => '#04504E',
		'intro'             => $inherited['intro'],
		'intro_color'       => '#3F4B4E',
		'btn1_text'         => $inherited['btn1_text'],
		'btn1_link'         => $inherited['btn1_link'],
		'btn1_bg_color'     => '#6B489E',
		'btn1_text_color'   => '#ffffff',
		'btn1_hover_bg'     => '#583B82',
		'btn2_text'         => $inherited['btn2_text'],
		'btn2_link'         => $inherited['btn2_link'],
		'show_search'       => true,
		'search_label'      => 'What would you like to know?',
		'search_placeholder'=> 'Search conditions, symptoms, treatments, recipes and articles...',
		'show_card'         => true,
		'card_title'        => 'Created for patients, built on expertise',
		'card_text'         => 'All content is written or reviewed by gastroenterology experts and explained for patients.',
		'card_bg_color'     => '#E5F1F1',
	);
}

/**
 * The words and CTA targets the homepage is showing right now, i.e. the
 * classic hero's slide-1 copy.
 *
 * Each chain ends at the CLASSIC hero's own default, not at the mockup's
 * wording: on a site that has never edited the hero, the classic defaults are
 * what visitors are actually reading, so those are what "keep what's there"
 * means for that site too.
 *
 * Note what is deliberately NOT copied across: the classic renderer bolts an
 * `onclick="openQuizModal()"` onto any link containing "quiz". It does not
 * need repeating here — front-page.php already runs a document-level click
 * interceptor for /healthcare-quiz/ and /gastro-health-survey/ hrefs, with a
 * plain-navigation fallback if the modal script is absent. So an inherited
 * quiz button keeps working, by a more robust route than the inline handler.
 *
 * @return array{title: string, intro: string, btn1_text: string, btn1_link: string, btn2_text: string, btn2_link: string}
 */
function vance_hero_inherited_copy() {
	// hero-carousel.php is required immediately before this file, but guard
	// anyway so a partial deploy degrades to sensible copy instead of fatalling.
	$classic = function_exists( 'vance_hero_slide_field_defaults' )
		? vance_hero_slide_field_defaults()
		: array(
			'title'     => 'Your Partner in <span class="highlight">Lifelong Wellness</span>',
			'subtitle'  => 'Trusted, science-backed information to help you understand your health, manage your IBD condition, and live your best life through clinical nutrition.',
			'btn1_text' => "I'm a Practitioner",
			'btn1_link' => '/healthcare-professionals/',
			'btn2_text' => "I'm a Patient",
			'btn2_link' => '/patients/',
		);

	return array(
		'title'     => vance_get_theme_mod( 'vance_hero_custom_title',    $classic['title'] ),
		'intro'     => vance_get_theme_mod( 'vance_hero_custom_subtitle', $classic['subtitle'] ),
		'btn1_text' => vance_get_theme_mod( 'vance_hero_button_1_text',   $classic['btn1_text'] ),
		'btn1_link' => vance_get_theme_mod( 'vance_hero_button_1_link',   $classic['btn1_link'] ),
		'btn2_text' => vance_get_theme_mod( 'vance_hero_button_2_text',   $classic['btn2_text'] ),
		'btn2_link' => vance_get_theme_mod( 'vance_hero_button_2_link',   $classic['btn2_link'] ),
	);
}

/**
 * Turn `#e5f1f1` into `229, 241, 241` for use inside rgba().
 *
 * The two dissolve gradients need the hero background colour at both full and
 * zero alpha. `transparent` is not usable for the zero stop: in Safari it
 * still computes as rgba(0,0,0,0), so the ramp runs through grey and leaves a
 * dirty band where the photo meets the background.
 *
 * @param string $hex   Any form sanitize_hex_color() accepts.
 * @param string $fallback Returned when $hex is unparseable.
 * @return string "r, g, b"
 */
function vance_hex_to_rgb_triple( $hex, $fallback = '255, 255, 255' ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( strlen( $hex ) === 3 ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( ! preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
		return $fallback;
	}
	return hexdec( substr( $hex, 0, 2 ) ) . ', ' . hexdec( substr( $hex, 2, 2 ) ) . ', ' . hexdec( substr( $hex, 4, 2 ) );
}

/**
 * Resolve every field, applying the "empty means auto" fallbacks.
 *
 * @return array<string, mixed>
 */
function vance_hero_spotlight_values() {
	$d    = vance_hero_spotlight_field_defaults();
	$vals = array();
	foreach ( $d as $field => $default ) {
		$vals[ $field ] = vance_get_theme_mod( 'vance_hero_spotlight_' . $field, $default );
	}

	if ( ! $vals['image'] ) {
		$vals['image'] = get_template_directory_uri() . '/assets/img/hero-couple.webp';
	}

	// Only reached when the inherited link is empty as well, i.e. an admin has
	// deliberately cleared it. Button 1 then falls to the gastro conditions
	// hub, resolved through the same helper the conditions grid uses so both
	// follow the page wherever it lives.
	if ( ! $vals['btn1_link'] && function_exists( 'vance_gi_hub_url' ) ) {
		$vals['btn1_link'] = vance_gi_hub_url();
	}

	// Likewise button 2 → the Knowledgebase lobby, by slug, with the literal
	// path as a last resort so the button is never href-less.
	if ( ! $vals['btn2_link'] ) {
		$kb = get_page_by_path( 'knowledgebase' );
		$vals['btn2_link'] = $kb ? get_permalink( $kb ) : home_url( '/knowledgebase/' );
	}

	return $vals;
}

/**
 * Render the spotlight hero.
 *
 * Markup order is media-then-container on purpose: on desktop the media is
 * absolutely positioned so source order is irrelevant, and below the stacking
 * breakpoint it drops back into flow — where being first puts the photograph
 * above the headline, which is the intended mobile order. No `order` property
 * and no duplicated markup.
 */
function vance_render_hero_spotlight() {
	$s = vance_hero_spotlight_values();

	$fade_from = vance_hex_to_rgb_triple( $s['bg_from'], '236, 245, 245' );
	$fade_to   = vance_hex_to_rgb_triple( $s['bg_to'], '246, 249, 250' );

	// The title accepts a highlight span, matching the carousel hero's contract.
	$title = wp_kses_post( $s['title'] );

	$style = sprintf(
		'--vhh-hs-from: %1$s; --vhh-hs-to: %2$s; --vhh-hs-from-rgb: %3$s; --vhh-hs-to-rgb: %4$s; --vhh-hs-title: %5$s; --vhh-hs-intro: %6$s; --vhh-hs-cta-bg: %7$s; --vhh-hs-cta-fg: %8$s; --vhh-hs-cta-hover: %9$s; --vhh-hs-card-bg: %10$s;',
		esc_attr( $s['bg_from'] ),
		esc_attr( $s['bg_to'] ),
		esc_attr( $fade_from ),
		esc_attr( $fade_to ),
		esc_attr( $s['title_color'] ),
		esc_attr( $s['intro_color'] ),
		esc_attr( $s['btn1_bg_color'] ),
		esc_attr( $s['btn1_text_color'] ),
		esc_attr( $s['btn1_hover_bg'] ),
		esc_attr( $s['card_bg_color'] )
	);
	?>
	<section class="vhh-hero-spotlight" style="<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput — each part escaped above ?>">

		<div class="vhh-hero-spotlight__media">
			<?php /* Above the fold and the page's LCP candidate — eager, high priority,
			         and with intrinsic dimensions so it reserves its box and cannot
			         shift the headline as it decodes. */ ?>
			<img src="<?php echo esc_url( $s['image'] ); ?>"
			     alt="<?php echo esc_attr( $s['image_alt'] ); ?>"
			     width="1400" height="876"
			     decoding="async" fetchpriority="high">
		</div>

		<div class="container vhh-hero-spotlight__inner">
			<div class="vhh-hero-spotlight__copy">
				<h1 class="vhh-hero-spotlight__title"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput — wp_kses_post above ?></h1>

				<?php if ( $s['intro'] !== '' ) : ?>
				<p class="vhh-hero-spotlight__intro"><?php echo esc_html( $s['intro'] ); ?></p>
				<?php endif; ?>

				<?php if ( $s['btn1_text'] !== '' || $s['btn2_text'] !== '' ) : ?>
				<div class="vhh-hero-spotlight__actions">
					<?php if ( $s['btn1_text'] !== '' ) : ?>
					<a class="vhh-hero-spotlight__cta" href="<?php echo esc_url( $s['btn1_link'] ); ?>">
						<span><?php echo esc_html( $s['btn1_text'] ); ?></span>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h13"/><path d="M13 6l6 6-6 6"/></svg>
					</a>
					<?php endif; ?>
					<?php if ( $s['btn2_text'] !== '' ) : ?>
					<a class="vhh-hero-spotlight__cta vhh-hero-spotlight__cta--ghost" href="<?php echo esc_url( $s['btn2_link'] ); ?>"><?php echo esc_html( $s['btn2_text'] ); ?></a>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $s['show_search'] ) ) : ?>
				<div class="vhh-hero-spotlight__search">
					<?php /* A real <label for>, not an aria-label: the prompt is visible
					         copy in the design, so tying it to the field is free and gives
					         the label its own click target. */ ?>
					<label class="vhh-hero-spotlight__search-label" for="vhh-hero-search"><?php echo esc_html( $s['search_label'] ); ?></label>
					<form role="search" method="get" class="vhh-hero-spotlight__search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<input type="search"
						       id="vhh-hero-search"
						       class="vhh-hero-spotlight__search-field"
						       name="s"
						       value="<?php echo esc_attr( get_search_query() ); ?>"
						       placeholder="<?php echo esc_attr( $s['search_placeholder'] ); ?>"
						       autocomplete="off">
						<button type="submit" class="vhh-hero-spotlight__search-submit" aria-label="<?php esc_attr_e( 'Search the Hub', 'vance-health-hub' ); ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/></svg>
						</button>
					</form>
				</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $s['show_card'] ) && ( $s['card_title'] !== '' || $s['card_text'] !== '' ) ) : ?>
			<aside class="vhh-hero-spotlight__card">
				<span class="vhh-hero-spotlight__card-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M16 20v-1.6a3.4 3.4 0 0 0-3.4-3.4H7.4A3.4 3.4 0 0 0 4 18.4V20"/><circle cx="10" cy="8" r="3.2"/><path d="M20 20v-1.6a3.4 3.4 0 0 0-2.6-3.3"/><path d="M15.4 4.9a3.2 3.2 0 0 1 0 6.2"/></svg>
				</span>
				<div class="vhh-hero-spotlight__card-body">
					<?php if ( $s['card_title'] !== '' ) : ?>
					<h2 class="vhh-hero-spotlight__card-title"><?php echo esc_html( $s['card_title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( $s['card_text'] !== '' ) : ?>
					<p class="vhh-hero-spotlight__card-text"><?php echo esc_html( $s['card_text'] ); ?></p>
					<?php endif; ?>
				</div>
			</aside>
			<?php endif; ?>
		</div>
	</section>
	<?php
}
