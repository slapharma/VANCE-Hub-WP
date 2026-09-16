<?php
/**
 * Spotlight heroes on a phone: the white band's short labels, and the two
 * per-hero "show on mobile" switches.
 *
 * Every hero family on the site (the homepage, the page heroes, the category
 * archives, the GI set and the policy documents) renders the same
 * `.vhh-hero-spotlight` section, so the phone behaviour is decided once here
 * and in the "Spotlight heroes on a phone" block of assets/css/main.css.
 *
 * THE BAND ON ONE LINE
 *
 * Below 768px the band used to stack into one full-width row per cell, which
 * turned three facts into a 200px tower under the headline. It now stays a
 * single row. Numbers fit as they are; names do not, so a cell may carry a
 * short form that replaces the full one on a phone. The full text stays in the
 * markup, visually hidden, so a screen reader still hears "Gastro Recipes &
 * Meal Planner" and not "Meal Planner".
 *
 * THE SWITCHES
 *
 * Each hero gets "Show the white band on mobile" and "Show the floating card on
 * mobile", both defaulting ON so nothing changes until an admin asks. Turning
 * one off adds a modifier class to the section; the CSS hides the element
 * below 768px only. Desktop is untouched, and the existing "Show the white
 * band" / "Show the floating card" switches still remove it everywhere.
 *
 * The category, GI and policy heroes deliberately do not depend on each other,
 * so they call these helpers behind function_exists() and fall back to the
 * plain full-text render.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * A band cell's value, with an optional phone-width short form.
 *
 * @param string $full  The value as the desktop band shows it.
 * @param string $short The phone form. Empty, or the same as $full, renders
 *                      $full alone.
 * @return string Escaped HTML.
 */
function vance_hero_band_value( $full, $short = '' ) {
	$full  = (string) $full;
	$short = (string) $short;
	if ( $short === '' || $short === $full ) {
		return esc_html( $full );
	}
	return '<span class="vhh-hero-spotlight__full">' . esc_html( $full ) . '</span>'
		. '<span class="vhh-hero-spotlight__short" aria-hidden="true">' . esc_html( $short ) . '</span>';
}

/**
 * Phone-width short forms for band text an admin can edit.
 *
 * Keyed by the shipped default. An admin who rewrites a pillar or a badge gets
 * their own words back on a phone, truncated with an ellipsis if they do not
 * fit, rather than a short form describing text that is no longer there.
 *
 * @param string $text
 * @return string The short form, or '' when there is none.
 */
function vance_hero_band_short( $text ) {
	$map = array(
		// About: the three assurance badges.
		'Rigorously Developed'    => __( 'Rigorous', 'vance-health-hub' ),
		'Citation-Backed Content' => __( 'Cited', 'vance-health-hub' ),
		// Get Started Today: the four evidence pillars.
		'Clinical Trials'         => __( 'Trials', 'vance-health-hub' ),
		'Real-World Data'         => __( 'Real-World', 'vance-health-hub' ),
		'Peer-Reviewed Science'   => __( 'Science', 'vance-health-hub' ),
		'Expert Consensus'        => __( 'Consensus', 'vance-health-hub' ),
	);
	return isset( $map[ $text ] ) ? $map[ $text ] : '';
}

/**
 * The modifier classes for a hero whose band or card is switched off on a phone.
 *
 * @param bool $band_on_mobile
 * @param bool $card_on_mobile
 * @return string Leading space included, or ''. Safe to echo: fixed strings.
 */
function vance_hero_mobile_classes( $band_on_mobile, $card_on_mobile ) {
	$classes = '';
	if ( ! $band_on_mobile ) {
		$classes .= ' vhh-hero-spotlight--band-hide-mobile';
	}
	if ( ! $card_on_mobile ) {
		$classes .= ' vhh-hero-spotlight--card-hide-mobile';
	}
	return $classes;
}

/**
 * The classes for a hero whose two switches are stored under $band_id/$card_id.
 *
 * @param string $band_id Setting id; unset reads as ON.
 * @param string $card_id Setting id; unset reads as ON.
 * @return string
 */
function vance_hero_mobile_classes_for( $band_id, $card_id ) {
	return vance_hero_mobile_classes(
		(bool) vance_get_theme_mod( $band_id, true ),
		(bool) vance_get_theme_mod( $card_id, true )
	);
}

/**
 * Register the two phone switches for one hero.
 *
 * @param WP_Customize_Manager $wp_customize
 * @param string               $section  Section id the hero's controls live in.
 * @param string               $band_id  Setting id for the band switch.
 * @param string               $card_id  Setting id for the card switch.
 * @param array                $args     label_prefix (string), band_label
 *                                       (string), priority (int).
 * @return void
 */
function vance_hero_mobile_register_controls( $wp_customize, $section, $band_id, $card_id, array $args = array() ) {
	$prefix     = isset( $args['label_prefix'] ) ? $args['label_prefix'] : '';
	$band_label = isset( $args['band_label'] ) ? $args['band_label'] : __( 'Show the white band on mobile', 'vance-health-hub' );

	$controls = array(
		$band_id => $band_label,
		$card_id => __( 'Show the floating card on mobile', 'vance-health-hub' ),
	);

	foreach ( $controls as $id => $label ) {
		$wp_customize->add_setting( $id, array(
			'default'           => true,
			'sanitize_callback' => 'vance_sanitize_checkbox',
		) );
		$control = array(
			'label'       => $prefix . $label,
			'description' => __( 'Untick to hide it on screens narrower than 768px. Desktop is unaffected.', 'vance-health-hub' ),
			'section'     => $section,
			'type'        => 'checkbox',
		);
		if ( isset( $args['priority'] ) ) {
			$control['priority'] = (int) $args['priority'];
		}
		$wp_customize->add_control( $id, $control );
	}
}

/**
 * Setting ids for one policy document's phone switches.
 *
 * @param string $doc A key of vance_legal_hero_docs().
 * @return array{band: string, card: string}
 */
function vance_legal_hero_mobile_keys( $doc ) {
	$doc = sanitize_key( $doc );
	return array(
		'band' => 'vance_legal_' . $doc . '_hero_show_band_mobile',
		'card' => 'vance_legal_' . $doc . '_hero_show_card_mobile',
	);
}

/**
 * The policy documents have no Customizer section of their own, so their phone
 * switches get one: a section per hero would be five sections holding two
 * checkboxes each.
 *
 * @param WP_Customize_Manager $wp_customize
 * @return void
 */
function vance_legal_hero_mobile_customize( $wp_customize ) {
	// The policy templates load this file themselves, so on a Customizer
	// request it has not been loaded yet.
	$legal = get_template_directory() . '/inc/legal-hero.php';
	if ( ! function_exists( 'vance_legal_hero_docs' ) && file_exists( $legal ) ) {
		require_once $legal;
	}
	if ( ! function_exists( 'vance_legal_hero_docs' ) ) {
		return;
	}

	$wp_customize->add_section( 'vance_legal_heroes_mobile', array(
		'title'       => __( 'Policy Document Heroes', 'vance-health-hub' ),
		'description' => __( 'The spotlight hero on the Privacy Policy, Cookie Policy, Terms of Use, Medical Disclaimer and Accessibility Statement. Their wording is set in code; these switches only control what shows on a phone.', 'vance-health-hub' ),
		'priority'    => 160,
	) );

	foreach ( vance_legal_hero_docs() as $doc => $d ) {
		$keys = vance_legal_hero_mobile_keys( $doc );
		vance_hero_mobile_register_controls( $wp_customize, 'vance_legal_heroes_mobile', $keys['band'], $keys['card'], array(
			'label_prefix' => html_entity_decode( wp_strip_all_tags( $d['short'] ), ENT_QUOTES, 'UTF-8' ) . ': ',
		) );
	}
}
add_action( 'customize_register', 'vance_legal_hero_mobile_customize', 30 );
