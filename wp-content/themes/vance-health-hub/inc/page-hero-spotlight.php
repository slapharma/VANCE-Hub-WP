<?php
/**
 * Contact and About page heroes — "spotlight" layout.
 *
 * The same light, search-led hero the homepage uses (inc/hero-spotlight.php),
 * carried across to /contact-us/ and /about/: a pale mint gradient band with
 * the eyebrow, headline, intro and two CTAs on the left, a photograph
 * dissolving into the background on the right, and a card floating over the
 * lower right.
 *
 * Where the homepage puts its search field, each page puts the thing its own
 * visitors came for:
 *
 *   contact — the three direct lines (email, phone, opening hours), as real
 *             mailto:/tel: links, so somebody who would rather not use the
 *             form never has to scroll to find them.
 *   about   — the three assurance badges, which the dark hero already shows
 *             as a loose row of ticks.
 *
 * Neither of those is new content: both read the settings the pages already
 * have. Same for the About card, which is stat 1 from the Trust Badges &
 * Stats section.
 *
 * NEITHER PAGE SWITCHES ON ITS OWN. `vance_contact_hero_style` and
 * `vance_about_hero_style` both default to 'classic', so deploying this file
 * changes nothing until an admin flips the control. That mirrors
 * `vance_hero_style` on the homepage, and it means the dark hero and every
 * one of its saved settings is always one toggle away.
 *
 * @package vance-health-hub
 * @since   2026-08-28
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Everything that differs between the two pages, in one place.
 *
 * The renderer, the value resolver and the Customizer registration all read
 * this, so a page cannot end up with a control the renderer ignores or a
 * default the control list has never heard of.
 *
 * @param string $page 'contact' or 'about'.
 * @return array<string, mixed>|null Null for an unknown page.
 */
function vance_page_hero_spotlight_config( $page ) {
	$img = get_template_directory_uri() . '/assets/img/about/';

	$conf = array(
		'contact' => array(
			'name'         => __( 'Contact Us', 'vance-health-hub' ),
			'panel'        => 'vance_contact_panel',
			'section'      => 'vance_contact_hero_spotlight',
			'priority'     => 9,
			// The classic hero's own copy keys. The spotlight hero reads these
			// directly rather than taking private copies: only the layout is
			// changing, so an admin editing "Description" should see it move in
			// whichever design is switched on. (The homepage needed private
			// copies because its classic hero is a multi-slide carousel and
			// slide 1's copy is entangled with the rest of the slider.)
			'legacy_tag'   => 'vance_contact_hero_tag',
			'legacy_title' => 'vance_contact_hero_title',
			'legacy_desc'  => 'vance_contact_hero_desc',
			// The fallbacks the CLASSIC hero passes to get_theme_mod(), copied
			// verbatim. They are what the page says on a site that has never
			// edited these fields, so the spotlight has to say the same or
			// switching design silently empties the hero -- and only on the
			// live site, because the Customizer preview serves each setting's
			// registered default and looks perfectly fine.
			'legacy_tag_default'   => 'Get in Touch',
			'legacy_title_default' => 'We\'d Love to <span class="highlight">Hear From You</span>',
			'legacy_desc_default'  => 'Whether you\'re a patient, healthcare professional, researcher, or media contact, our team is here to help. Reach out and we\'ll respond within one business day.',
			// A separate image key, NOT vance_contact_hero_img. The classic
			// default (hcp_hero.png) was chosen to sit under a 78% navy veil;
			// dropped onto a pale band with no veil it reads as a dark smear.
			'image'        => $img . 'community-support.jpg',
			'image_alt'    => __( 'A support group sitting in conversation in a bright room', 'vance-health-hub' ),
			'btn1_text'    => __( 'Send us a message', 'vance-health-hub' ),
			'btn1_link'    => '#contact-form',
			'btn2_text'    => __( 'Ask the Hub AI instead', 'vance-health-hub' ),
			'btn2_link'    => '', // empty => resolved to the Ask AI page, see values().
			'slot'         => 'lines',
			'slot_label'   => __( 'Prefer to reach us directly?', 'vance-health-hub' ),
			'card'         => 'text',
			'card_title'   => __( 'A real reply, within one business day', 'vance-health-hub' ),
			'card_text'    => __( 'Messages sent from this page reach the Vance Medical team directly — no ticket queue, no automated loop.', 'vance-health-hub' ),
		),
		'about' => array(
			'name'         => __( 'About Us', 'vance-health-hub' ),
			'panel'        => 'vance_about_panel',
			'section'      => 'vance_about_hero_spotlight',
			'priority'     => 9,
			'legacy_tag'   => 'vance_about_hero_tag',
			'legacy_title' => 'vance_about_hero_title',
			'legacy_desc'  => 'vance_about_hero_desc',
			// As above: the classic hero's own fallbacks, copied verbatim.
			'legacy_tag_default'   => 'About Vance Medical Hub',
			'legacy_title_default' => 'Trusted by Patients.<br><span class="highlight">Driven by Science.</span>',
			'legacy_desc_default'  => 'We bridge pharmaceutical expertise with nutritional science to empower patients living with gastrointestinal conditions, delivering evidence-based care you can trust.',
			// Likewise not vance_about_hero_img: its saved default,
			// diverse-patients-clinic.jpg, is a low-resolution watermarked
			// stock frame that only survives because the navy veil hides it.
			'image'        => $img . 'research-lab.jpg',
			'image_alt'    => __( 'Two scientists reviewing results together in a laboratory', 'vance-health-hub' ),
			'btn1_text'    => __( 'Read our story', 'vance-health-hub' ),
			'btn1_link'    => '#our-story',
			// #mission is an anchor this page already renders. Our Heritage is
			// deliberately not used: customizer-pages.php retires that panel at
			// the end of its registration, so the page is on its way out.
			'btn2_text'    => __( 'Our mission and values', 'vance-health-hub' ),
			'btn2_link'    => '#mission',
			'slot'         => 'badges',
			'slot_label'   => __( 'What everything we publish is held to', 'vance-health-hub' ),
			'card'         => 'stat',
			'card_title'   => '',
			'card_text'    => __( 'Formulated and reviewed under the same regulatory frameworks as prescription medicines.', 'vance-health-hub' ),
		),
	);

	return isset( $conf[ $page ] ) ? $conf[ $page ] : null;
}

/**
 * Which hero design a page shows before anyone touches the Customizer.
 *
 * Deliberately a function rather than a literal repeated in two places. The
 * Customizer's registered `default` and the runtime fallback that
 * vance_page_hero_spotlight_active() passes to get_theme_mod() are separate
 * mechanisms — WordPress uses the first to seed the control and the second to
 * answer an unsaved read — so writing 'classic' twice lets them drift, and a
 * page would then preview one design and render the other.
 *
 * @return string 'classic' or 'spotlight'.
 */
function vance_page_hero_spotlight_default_style() {
	return 'classic';
}

/**
 * Is the spotlight design switched on for this page?
 *
 * @param string $page 'contact' or 'about'.
 * @return bool
 */
function vance_page_hero_spotlight_active( $page ) {
	if ( ! vance_page_hero_spotlight_config( $page ) ) {
		return false;
	}
	$style = vance_get_theme_mod(
		'vance_' . $page . '_hero_style',
		vance_page_hero_spotlight_default_style()
	);
	return $style === 'spotlight';
}

/**
 * Per-field defaults for one page.
 *
 * Colour defaults are the homepage hero's, read from
 * vance_hero_spotlight_field_defaults() rather than retyped, so the three
 * heroes cannot drift apart on brand colour.
 *
 * @param string $page 'contact' or 'about'.
 * @return array<string, mixed> field => default
 */
function vance_page_hero_spotlight_field_defaults( $page ) {
	$c = vance_page_hero_spotlight_config( $page );
	if ( ! $c ) { return array(); }

	// hero-spotlight.php is required before this file, but guard anyway so a
	// partial deploy degrades to the brand colours instead of fatalling.
	$home = function_exists( 'vance_hero_spotlight_field_defaults' )
		? vance_hero_spotlight_field_defaults()
		: array(
			'bg_from'         => '#ECF5F5',
			'bg_to'           => '#F6F9FA',
			'title_color'     => '#04504E',
			'intro_color'     => '#3F4B4E',
			'btn1_bg_color'   => '#6B489E',
			'btn1_text_color' => '#ffffff',
			'btn1_hover_bg'   => '#583B82',
			'card_bg_color'   => '#E5F1F1',
		);

	$d = array(
		'image'           => '',
		'image_alt'       => $c['image_alt'],
		'bg_from'         => $home['bg_from'],
		'bg_to'           => $home['bg_to'],
		'title_color'     => $home['title_color'],
		'intro_color'     => $home['intro_color'],
		'btn1_text'       => $c['btn1_text'],
		'btn1_link'       => $c['btn1_link'],
		'btn1_bg_color'   => $home['btn1_bg_color'],
		'btn1_text_color' => $home['btn1_text_color'],
		'btn1_hover_bg'   => $home['btn1_hover_bg'],
		'btn2_text'       => $c['btn2_text'],
		'btn2_link'       => $c['btn2_link'],
		'show_slot'       => true,
		'slot_label'      => $c['slot_label'],
		'show_card'       => true,
		'card_text'       => $c['card_text'],
		'card_bg_color'   => $home['card_bg_color'],
	);

	// Only the text card carries its own heading; the stat card's "heading" is
	// stat 1, which lives in the Trust Badges & Stats section already.
	if ( $c['card'] === 'text' ) {
		$d['card_title'] = $c['card_title'];
	}

	return $d;
}

/**
 * Resolve every field for one page, applying the "empty means auto" fallbacks.
 *
 * @param string $page 'contact' or 'about'.
 * @return array<string, mixed>
 */
function vance_page_hero_spotlight_values( $page ) {
	$c = vance_page_hero_spotlight_config( $page );
	if ( ! $c ) { return array(); }

	$prefix = 'vance_' . $page . '_hero_spot_';
	$vals   = array();
	foreach ( vance_page_hero_spotlight_field_defaults( $page ) as $field => $default ) {
		$vals[ $field ] = vance_get_theme_mod( $prefix . $field, $default );
	}

	if ( ! $vals['image'] ) {
		$vals['image'] = $c['image'];
	}

	// Copy comes from the classic hero's keys — see 'legacy_*' in the config.
	// The defaults matter as much as the keys: an unsaved setting returns
	// whatever default is passed here, so passing '' would render an empty
	// hero on every site that has not edited these fields.
	$vals['eyebrow'] = vance_get_theme_mod( $c['legacy_tag'],   $c['legacy_tag_default'] );
	$vals['title']   = vance_get_theme_mod( $c['legacy_title'], $c['legacy_title_default'] );
	$vals['intro']   = vance_get_theme_mod( $c['legacy_desc'],  $c['legacy_desc_default'] );

	// Only reached when an admin has deliberately cleared button 2's link.
	// Resolved by slug so it follows the page wherever it lives, with the
	// literal path as a last resort so the button is never href-less.
	if ( ! $vals['btn2_link'] && $page === 'contact' ) {
		$ai = get_page_by_path( 'ask-ai' );
		$vals['btn2_link'] = $ai ? get_permalink( $ai ) : home_url( '/ask-ai/' );
	}

	return $vals;
}

/**
 * One inline icon from the set this hero uses.
 *
 * Same stroke weight and 24-unit box as the homepage hero's icons so the two
 * read as one family.
 *
 * @param string $name mail|phone|clock|check|chat|flask
 * @return string SVG markup, already safe (no dynamic values).
 */
function vance_page_hero_spotlight_icon( $name ) {
	$paths = array(
		'mail'  => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M3 7l9 6 9-6"/>',
		'phone' => '<path d="M6.5 3h3l1.5 4.5-2 1.5a13 13 0 0 0 6 6l1.5-2L21 14.5v3a2.5 2.5 0 0 1-2.7 2.5A16.5 16.5 0 0 1 3.5 5.7 2.5 2.5 0 0 1 6 3z"/>',
		'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.3l3.4 2"/>',
		'check' => '<path d="M4 12.5l5 5L20 6.5"/>',
		'chat'  => '<path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 9.2 9.2 0 0 1-3.9-.9L3 20.5l1.6-4.7A8.4 8.4 0 0 1 12 3.1a8.4 8.4 0 0 1 9 8.4z"/><path d="M8.5 11.5h.01M12 11.5h.01M15.5 11.5h.01"/>',
		'flask' => '<path d="M9.5 3v6.2L4.6 17a2.6 2.6 0 0 0 2.2 4h10.4a2.6 2.6 0 0 0 2.2-4l-4.9-7.8V3"/><path d="M8 3h8"/><path d="M7.6 14.6h8.8"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) { return ''; }

	// 'check' is drawn on a small dark tile, so it needs a heavier stroke to
	// stay legible at 15px.
	$weight = ( $name === 'check' ) ? '2.6' : '1.9';

	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $weight
		. '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
		. $paths[ $name ] . '</svg>';
}

/**
 * Turn a human-readable phone number into a dialable tel: target.
 *
 * The saved value carries spaces, brackets and dashes so it reads well —
 * "+44 (0)1628 526 005". Stripping to digits alone is NOT enough: that keeps
 * the trunk prefix inside the brackets and yields +4401628526005, which is not
 * a number and fails to connect. In UK/ITU notation a bracketed lone zero is
 * exactly the digit you drop once you have dialled the country code, so it is
 * removed first — but only when the number is in international form, since a
 * purely national "(01628) 526005" needs its leading zero kept.
 *
 * @param string $phone Whatever an admin typed.
 * @return string Digits, with at most a leading '+'.
 */
function vance_page_hero_spotlight_tel( $phone ) {
	$phone = (string) $phone;

	// Only when a country code is present. `(01628)` is left alone: its zero is
	// load-bearing, and the group holds more than a single 0.
	if ( strpos( $phone, '+' ) !== false ) {
		$phone = preg_replace( '/\(\s*0\s*\)/', '', $phone );
	}

	$digits = preg_replace( '/[^0-9+]/', '', $phone );

	// A '+' is only meaningful in the leading position.
	$lead   = ( isset( $digits[0] ) && $digits[0] === '+' ) ? '+' : '';

	return $lead . str_replace( '+', '', $digits );
}

/**
 * The three direct-contact cells for the Contact hero's utility band.
 *
 * Reads the settings the contact page already has, so nothing here is a new
 * thing for an admin to keep in sync. A cell whose setting is empty is
 * dropped rather than rendered blank.
 *
 * @return array<int, array{key: string, label: string, value: string, href: string}>
 */
function vance_page_hero_spotlight_lines() {
	// Defaults copied from page-contact-us.php, which passes these same
	// fallbacks to get_theme_mod() further down the page. Passing '' would
	// empty the band wherever an admin has never opened Contact Information.
	$email = vance_get_theme_mod( 'vance_contact_email', 'team@vancemedicalfoods.co.uk' );
	$phone = vance_get_theme_mod( 'vance_contact_phone', '+44 (0)1628 526 005' );
	$hours = vance_get_theme_mod( 'vance_contact_hours', 'Monday – Friday, 9:00 am – 5:00 pm GMT' );

	$lines = array();

	if ( $email ) {
		$lines[] = array(
			'key'   => 'mail',
			'label' => __( 'Email', 'vance-health-hub' ),
			'value' => $email,
			'href'  => 'mailto:' . $email,
		);
	}
	if ( $phone ) {
		$lines[] = array(
			'key'   => 'phone',
			'label' => __( 'Phone', 'vance-health-hub' ),
			'value' => $phone,
			'href'  => 'tel:' . vance_page_hero_spotlight_tel( $phone ),
		);
	}
	if ( $hours ) {
		$lines[] = array(
			'key'   => 'clock',
			'label' => __( 'Open', 'vance-health-hub' ),
			'value' => $hours,
			'href'  => '', // not a link
		);
	}

	return $lines;
}

/**
 * Render the spotlight hero for one page.
 *
 * Markup order is media-then-container for the same reason as the homepage
 * hero: on desktop the media is absolutely positioned so source order is
 * irrelevant, and below the stacking breakpoint it drops back into flow —
 * where being first puts the photograph above the headline, which is the
 * intended mobile order. No `order` property, no duplicated markup.
 *
 * The section carries the homepage hero's own class so it inherits the band,
 * the dissolve, the type scale, the CTAs and the card from one stylesheet
 * block — including the doubled-class !important rules that opt the hero out
 * of the global mobile type normalisation. The modifier only adds what these
 * two pages need on top.
 *
 * @param string $page 'contact' or 'about'.
 * @return void
 */
function vance_render_page_hero_spotlight( $page ) {
	$c = vance_page_hero_spotlight_config( $page );
	if ( ! $c ) { return; }

	$s = vance_page_hero_spotlight_values( $page );

	$fade_from = vance_hex_to_rgb_triple( $s['bg_from'], '236, 245, 245' );
	$fade_to   = vance_hex_to_rgb_triple( $s['bg_to'], '246, 249, 250' );

	// The title accepts a highlight span, matching both other heroes.
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

	$slot_items = ( $c['slot'] === 'lines' )
		? vance_page_hero_spotlight_lines()
		// Defaults copied from page-about.php's own $badge_defaults, for the
		// same reason as the hero copy above: '' here empties the band on any
		// site that has never edited the badges.
		: array_values( array_filter( array(
			vance_get_theme_mod( 'vance_about_badge1_label', 'Pharma-Grade Quality' ),
			vance_get_theme_mod( 'vance_about_badge2_label', 'Clinician Approved' ),
			vance_get_theme_mod( 'vance_about_badge3_label', 'Evidence-Based' ),
		) ) );

	// The About badges have their own long-standing visibility switch; honour
	// it here rather than making an admin turn the same row off twice.
	$show_slot = ! empty( $s['show_slot'] ) && $slot_items;
	if ( $c['slot'] === 'badges' && ! vance_get_theme_mod( 'vance_about_badges_show', true ) ) {
		$show_slot = false;
	}
	?>
	<section class="vhh-hero-spotlight vhh-hero-spotlight--page vhh-hero-spotlight--<?php echo esc_attr( $page ); ?>" style="<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput — each part escaped above ?>">

		<div class="vhh-hero-spotlight__media">
			<?php /* Above the fold and the page's LCP candidate — eager, high
			         priority, and with intrinsic dimensions so it reserves its
			         box and cannot shift the headline as it decodes. */ ?>
			<img src="<?php echo esc_url( $s['image'] ); ?>"
			     alt="<?php echo esc_attr( $s['image_alt'] ); ?>"
			     width="1400" height="876"
			     decoding="async" fetchpriority="high">
		</div>

		<div class="container vhh-hero-spotlight__inner">
			<div class="vhh-hero-spotlight__copy">

				<?php if ( $s['eyebrow'] !== '' ) : ?>
				<span class="vhh-hero-spotlight__eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></span>
				<?php endif; ?>

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

				<?php if ( $show_slot ) : ?>
				<div class="vhh-hero-spotlight__slot-wrap">
					<?php if ( $s['slot_label'] !== '' ) : ?>
					<span class="vhh-hero-spotlight__slot-label"><?php echo esc_html( $s['slot_label'] ); ?></span>
					<?php endif; ?>

					<?php if ( $c['slot'] === 'lines' ) : ?>
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
					<?php else : ?>
					<ul class="vhh-hero-spotlight__slot vhh-hero-spotlight__slot--badges">
						<?php foreach ( $slot_items as $badge ) : ?>
						<li class="vhh-hero-spotlight__badge">
							<span class="vhh-hero-spotlight__badge-ico"><?php echo vance_page_hero_spotlight_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup ?></span>
							<span class="vhh-hero-spotlight__badge-t"><?php echo esc_html( $badge ); ?></span>
						</li>
						<?php endforeach; ?>
					</ul>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $s['show_card'] ) ) : ?>
				<?php if ( $c['card'] === 'stat' ) :
					$stat_num   = vance_get_theme_mod( 'vance_about_stat1_num', '30+' );
					$stat_label = vance_get_theme_mod( 'vance_about_stat1_label', 'Years of Pharmaceutical Experience' );
					if ( $stat_num !== '' || $s['card_text'] !== '' ) : ?>
				<aside class="vhh-hero-spotlight__card vhh-hero-spotlight__card--stat">
					<span class="vhh-hero-spotlight__card-icon" aria-hidden="true"><?php echo vance_page_hero_spotlight_icon( 'flask' ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup ?></span>
					<?php if ( $stat_num !== '' ) : ?>
					<p class="vhh-hero-spotlight__stat-fig">
						<span class="vhh-hero-spotlight__stat-num"><?php echo esc_html( $stat_num ); ?></span>
						<?php if ( $stat_label !== '' ) : ?>
						<span class="vhh-hero-spotlight__stat-lab"><?php echo esc_html( $stat_label ); ?></span>
						<?php endif; ?>
					</p>
					<span class="vhh-hero-spotlight__stat-rule" aria-hidden="true"></span>
					<?php endif; ?>
					<?php if ( $s['card_text'] !== '' ) : ?>
					<p class="vhh-hero-spotlight__card-text"><?php echo esc_html( $s['card_text'] ); ?></p>
					<?php endif; ?>
				</aside>
				<?php endif; ?>

				<?php else :
					$card_title = isset( $s['card_title'] ) ? $s['card_title'] : '';
					if ( $card_title !== '' || $s['card_text'] !== '' ) : ?>
				<aside class="vhh-hero-spotlight__card">
					<span class="vhh-hero-spotlight__card-icon" aria-hidden="true"><?php echo vance_page_hero_spotlight_icon( 'chat' ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup ?></span>
					<div class="vhh-hero-spotlight__card-body">
						<?php if ( $card_title !== '' ) : ?>
						<h2 class="vhh-hero-spotlight__card-title"><?php echo esc_html( $card_title ); ?></h2>
						<?php endif; ?>
						<?php if ( $s['card_text'] !== '' ) : ?>
						<p class="vhh-hero-spotlight__card-text"><?php echo esc_html( $s['card_text'] ); ?></p>
						<?php endif; ?>
					</div>
				</aside>
				<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Register the toggle and every spotlight field for both pages.
 *
 * Called from customizer-pages.php, inside the same customize_register hook
 * that builds the Contact and About panels — the sections below hang off
 * those panels, so they have to be registered after them.
 *
 * @param WP_Customize_Manager $wp_customize
 * @return void
 */
function vance_page_hero_spotlight_customize( $wp_customize ) {

	$shared_fields = array(
		'image'           => array( 'type' => 'image',    'label' => 'Photograph', 'description' => 'Separate from the classic hero\'s background image, which was picked to sit under a dark veil. This one is dissolved into a pale background, so it wants to be light and uncluttered down its left-hand side — roughly 1400&times;875.' ),
		'image_alt'       => array( 'type' => 'text',     'label' => 'Photograph — alt text' ),
		'bg_from'         => array( 'type' => 'color',    'label' => 'Background — Top' ),
		'bg_to'           => array( 'type' => 'color',    'label' => 'Background — Bottom', 'description' => 'The photograph is dissolved into these two colours, so changing them keeps its edges seamless.' ),
		'title_color'     => array( 'type' => 'color',    'label' => 'Headline Colour' ),
		'intro_color'     => array( 'type' => 'color',    'label' => 'Body Text Colour' ),
		'btn1_text'       => array( 'type' => 'text',     'label' => 'Button 1, Text' ),
		'btn1_link'       => array( 'type' => 'text',     'label' => 'Button 1, Link' ),
		'btn1_bg_color'   => array( 'type' => 'color',    'label' => 'Button 1, Background' ),
		'btn1_text_color' => array( 'type' => 'color',    'label' => 'Button 1, Text Colour' ),
		'btn1_hover_bg'   => array( 'type' => 'color',    'label' => 'Button 1, Background on Hover' ),
		'btn2_text'       => array( 'type' => 'text',     'label' => 'Button 2, Text' ),
		'btn2_link'       => array( 'type' => 'text',     'label' => 'Button 2, Link' ),
		'show_slot'       => array( 'type' => 'checkbox', 'label' => 'Show the white band' ),
		'slot_label'      => array( 'type' => 'text',     'label' => 'White band — Prompt' ),
		'show_card'       => array( 'type' => 'checkbox', 'label' => 'Show the floating card' ),
		'card_title'      => array( 'type' => 'text',     'label' => 'Card — Heading' ),
		'card_text'       => array( 'type' => 'textarea', 'label' => 'Card — Body' ),
		'card_bg_color'   => array( 'type' => 'color',    'label' => 'Card — Background' ),
	);

	// Per-page wording for the two controls that need it.
	$notes = array(
		'contact' => array(
			'toggle'     => 'Spotlight is the light hero: mint band, dissolving photograph, two buttons and your email, phone and opening hours in a white band. Classic is the dark navy hero configured by the rest of this panel.',
			'section'    => 'The light hero for this page. Only rendered while "Contact hero design" (in the Hero Section) is set to Spotlight. The eyebrow, headline and description are shared with the classic hero — edit them in the Hero Section, and they follow whichever design is switched on.',
			'slot_label' => 'Sits above the white band. The band itself is filled from Email Address, Phone Number and Office Hours in the Contact Information section.',
		),
		'about' => array(
			'toggle'     => 'Spotlight is the light hero: mint band, dissolving photograph, two buttons and your trust badges in a white band. Classic is the dark navy hero configured by the rest of this panel.',
			'section'    => 'The light hero for this page. Only rendered while "About hero design" (in the Hero Section) is set to Spotlight. The eyebrow, headline and description are shared with the classic hero — edit them in the Hero Section, and they follow whichever design is switched on.',
			'slot_label' => 'Sits above the white band. The band itself is filled from Badge 1–3 in the Trust Badges &amp; Stats section, and the card shows Stat 1 from that same section.',
		),
	);

	foreach ( array( 'contact', 'about' ) as $page ) {
		$c = vance_page_hero_spotlight_config( $page );
		if ( ! $c ) { continue; }

		$defaults = vance_page_hero_spotlight_field_defaults( $page );

		// -- The toggle, in the page's existing Hero Section so it sits with
		//    the classic hero it switches away from.
		$style_id = 'vance_' . $page . '_hero_style';
		$wp_customize->add_setting( $style_id, array(
			'default'           => vance_page_hero_spotlight_default_style(),
			'sanitize_callback' => function ( $v ) {
				return in_array( $v, array( 'classic', 'spotlight' ), true )
					? $v
					: vance_page_hero_spotlight_default_style();
			},
		) );
		$wp_customize->add_control( $style_id, array(
			'label'       => sprintf( __( '%s hero design', 'vance-health-hub' ), $c['name'] === 'Contact Us' ? __( 'Contact', 'vance-health-hub' ) : __( 'About', 'vance-health-hub' ) ),
			'description' => $notes[ $page ]['toggle'],
			'section'     => 'vance_' . $page . '_hero',
			'type'        => 'select',
			'priority'    => 1,
			'choices'     => array(
				'classic'   => __( 'Classic — dark navy hero', 'vance-health-hub' ),
				'spotlight' => __( 'Spotlight — light, action-led', 'vance-health-hub' ),
			),
		) );

		// -- The spotlight's own section.
		$wp_customize->add_section( $c['section'], array(
			'title'       => sprintf( __( 'Hero — Spotlight', 'vance-health-hub' ) ),
			'description' => $notes[ $page ]['section'],
			'priority'    => $c['priority'],
			'panel'       => $c['panel'],
		) );

		foreach ( $shared_fields as $field => $meta ) {
			// The stat card has no heading of its own — it shows Stat 1.
			if ( ! array_key_exists( $field, $defaults ) ) { continue; }

			$id = 'vance_' . $page . '_hero_spot_' . $field;

			switch ( $meta['type'] ) {
				case 'color':
					$sanitize = 'sanitize_hex_color';
					break;
				case 'image':
					$sanitize = 'esc_url_raw';
					break;
				case 'checkbox':
					$sanitize = 'vance_sanitize_checkbox';
					break;
				case 'textarea':
					$sanitize = 'sanitize_textarea_field';
					break;
				default:
					$sanitize = 'sanitize_text_field';
			}

			$wp_customize->add_setting( $id, array(
				'default'           => $defaults[ $field ],
				'sanitize_callback' => $sanitize,
			) );

			$args = array(
				'label'   => $meta['label'],
				'section' => $c['section'],
			);
			if ( isset( $meta['description'] ) ) { $args['description'] = $meta['description']; }
			if ( $field === 'slot_label' ) { $args['description'] = $notes[ $page ]['slot_label']; }

			if ( $meta['type'] === 'color' ) {
				$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, $args ) );
			} elseif ( $meta['type'] === 'image' ) {
				$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $id, $args ) );
			} else {
				$args['type'] = $meta['type'];
				$wp_customize->add_control( $id, $args );
			}
		}
	}
}
