<?php
/**
 * VanceHubClub brand colours: teal, navy and purple.
 *
 * Three Customizer colour pickers, one per brand colour (section "Header and
 * member bar", after the member bar texts):
 *
 *   - vance_hub_color_teal   (default #008080): buttons, links, tool cards;
 *   - vance_hub_color_navy   (default #0A1929): headings, the dark hero and
 *     the dashboard side menu;
 *   - vance_hub_color_purple (default #6B489E): member and account things.
 *
 * How it works. Each of the seven hub stylesheets declares its colours as CSS
 * custom properties on one selector (see vance_hub_brand_map()). A saved colour
 * is not written into the stylesheets. This file prints one small rule after
 * each stylesheet that redeclares the properties of that colour on the same
 * selector, with the same specificity and a later position, so it wins. The
 * lighter and darker shades (hover, wash, line, tints) are calculated from the
 * saved colour by mixing it with white or black at a fixed ratio.
 *
 * With nothing saved, or with a colour that equals its default, the file prints
 * NO CSS at all for that colour. Every page is then byte for byte what it was
 * before this file existed.
 *
 * A colour that is too pale for white text (contrast against #FFFFFF below
 * 4.5:1) is refused twice: the Customizer refuses to save it (validate_callback),
 * and the reader gives the default for any stored value that is invalid or too
 * pale, so a value written by another route cannot break the buttons.
 *
 * The pure functions (vance_hub_brand_hex_to_rgb(), vance_hub_brand_contrast(),
 * vance_hub_brand_mix(), vance_hub_brand_shades(), vance_hub_brand_css()) call
 * no WordPress function, so a test can run them without WordPress.
 *
 * Not in scope: the classic layout, assets/css/main.css, the admin CSS, and the
 * preview bar (inc/hub-preview.php), which keep their own literals.
 *
 * Needs inc/hub-layout.php (the section id vance_hub_sec_header). Loaded from
 * functions.php directly after inc/hub-look-settings.php.
 *
 * To back the feature out, delete the require line in functions.php. The
 * stylesheets keep their own defaults, so every page looks as it did before.
 * Saved values stay in the database and do no harm.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * Pure colour functions
 * ====================================================================== */

/**
 * The three brand colours and their defaults.
 *
 * @return string[] Colour name => default #RRGGBB (upper case).
 */
function vance_hub_brand_defaults() {
	return array(
		'teal'   => '#008080',
		'navy'   => '#0A1929',
		'purple' => '#6B489E',
	);
}

/**
 * A colour as upper-case #RRGGBB. Pure.
 *
 * Accepts #RGB and #RRGGBB in any letter case. Nothing else is a colour.
 *
 * @param mixed $value Raw value.
 * @return string '#RRGGBB', or '' when the value is not a hex colour.
 */
function vance_hub_brand_normalise( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$value = trim( $value );
	if ( preg_match( '/^#([0-9a-fA-F]{3})$/', $value, $m ) ) {
		$short = $m[1];
		return strtoupper( '#' . $short[0] . $short[0] . $short[1] . $short[1] . $short[2] . $short[2] );
	}
	if ( preg_match( '/^#([0-9a-fA-F]{6})$/', $value, $m ) ) {
		return strtoupper( '#' . $m[1] );
	}
	return '';
}

/**
 * Hex colour to channels. Pure.
 *
 * @param mixed $hex #RGB or #RRGGBB.
 * @return int[]|null array( r, g, b ), or null when the value is not a colour.
 */
function vance_hub_brand_hex_to_rgb( $hex ) {
	$hex = vance_hub_brand_normalise( $hex );
	if ( '' === $hex ) {
		return null;
	}
	return array(
		(int) hexdec( substr( $hex, 1, 2 ) ),
		(int) hexdec( substr( $hex, 3, 2 ) ),
		(int) hexdec( substr( $hex, 5, 2 ) ),
	);
}

/**
 * WCAG contrast ratio of two colours, from 1 to 21. Pure.
 *
 * @param string $hex_a First colour.
 * @param string $hex_b Second colour.
 * @return float The ratio, or 0.0 when one value is not a colour.
 */
function vance_hub_brand_contrast( $hex_a, $hex_b ) {
	$a = vance_hub_brand_hex_to_rgb( $hex_a );
	$b = vance_hub_brand_hex_to_rgb( $hex_b );
	if ( null === $a || null === $b ) {
		return 0.0;
	}
	$lum = function ( array $rgb ) {
		$out = array();
		foreach ( $rgb as $channel ) {
			$c     = $channel / 255;
			$out[] = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		}
		return 0.2126 * $out[0] + 0.7152 * $out[1] + 0.0722 * $out[2];
	};
	$la = $lum( $a );
	$lb = $lum( $b );
	$hi = max( $la, $lb );
	$lo = min( $la, $lb );
	return ( $hi + 0.05 ) / ( $lo + 0.05 );
}

/**
 * Mix a colour towards another. Pure.
 *
 * @param string $hex        Base colour.
 * @param string $target_hex Colour to mix towards (white or black for a shade).
 * @param float  $ratio      0 gives the base, 1 gives the target.
 * @return string '#RRGGBB' (upper case), or '' when a colour is not valid.
 */
function vance_hub_brand_mix( $hex, $target_hex, $ratio ) {
	$a = vance_hub_brand_hex_to_rgb( $hex );
	$b = vance_hub_brand_hex_to_rgb( $target_hex );
	if ( null === $a || null === $b ) {
		return '';
	}
	$ratio = max( 0.0, min( 1.0, (float) $ratio ) );
	$out   = '#';
	for ( $i = 0; $i < 3; $i++ ) {
		$value = (int) round( $a[ $i ] + ( $b[ $i ] - $a[ $i ] ) * $ratio );
		$out  .= sprintf( '%02X', max( 0, min( 255, $value ) ) );
	}
	return $out;
}

/**
 * How each derived shade is made: shade key => array( target, ratio ).
 *
 * Each ratio is chosen so that the DEFAULT colour gives the shade the stylesheets
 * were designed with, as closely as a white or black mix can (see the table in
 * the build notes). 'base' and 'rgb' are not mixes and are not listed here.
 *
 * @return array[] Colour name => shade key => array( '#FFFFFF' or '#000000', ratio ).
 */
function vance_hub_brand_recipes() {
	$white = '#FFFFFF';
	$black = '#000000';
	return array(
		'teal'   => array(
			'hover'   => array( $black, 0.200 ),
			'ink'     => array( $black, 0.405 ),
			'heading' => array( $black, 0.445 ),
			'wash'    => array( $white, 0.880 ),
			'line'    => array( $white, 0.850 ),
			'pale'    => array( $white, 0.690 ),
			'soft'    => array( $white, 0.480 ),
			'mid'     => array( $white, 0.300 ),
			'mist'    => array( $white, 0.905 ),
			'bg'      => array( $white, 0.950 ),
			'tint'    => array( $white, 0.965 ),
		),
		'navy'   => array(
			'lift' => array( $white, 0.055 ),
		),
		'purple' => array(
			'ink'    => array( $black, 0.130 ),
			'wash'   => array( $white, 0.935 ),
			'tint'   => array( $white, 0.875 ),
			'tint-b' => array( $white, 0.875 ),
			'soft'   => array( $white, 0.830 ),
			'mist'   => array( $white, 0.905 ),
			'line'   => array( $white, 0.750 ),
		),
	);
}

/**
 * Every shade of one brand colour. Pure.
 *
 * @param string $which 'teal', 'navy' or 'purple'.
 * @param string $hex   The base colour (#RGB or #RRGGBB).
 * @return string[] Shade key => value: 'base' (#RRGGBB), 'rgb' ("r, g, b", for
 *                  rgba()) and one #RRGGBB per recipe. Empty for an unknown
 *                  colour name or an invalid base.
 */
function vance_hub_brand_shades( $which, $hex ) {
	$recipes = vance_hub_brand_recipes();
	$base    = vance_hub_brand_normalise( $hex );
	if ( ! isset( $recipes[ $which ] ) || '' === $base ) {
		return array();
	}
	$rgb    = vance_hub_brand_hex_to_rgb( $base );
	$shades = array(
		'base' => $base,
		'rgb'  => $rgb[0] . ', ' . $rgb[1] . ', ' . $rgb[2],
	);
	foreach ( $recipes[ $which ] as $key => $recipe ) {
		$shades[ $key ] = vance_hub_brand_mix( $base, $recipe[0], $recipe[1] );
	}
	return $shades;
}

/* =========================================================================
 * Map and CSS
 * ====================================================================== */

/**
 * Where each brand colour goes: per stylesheet handle, the selector of the
 * declaration block and, per colour, property name => shade key.
 *
 * Built from the seven stylesheets (assets/css/hub-*.css). Every custom property
 * that holds a brand shade in those blocks is listed. A property that holds
 * something else (ink #1F2937, muted, slate, fonts) is not.
 *
 * @return array[] Handle => array( 'selector' => string, 'colors' => colour => property => shade key ).
 */
function vance_hub_brand_map() {
	return array(
		'vance-hub-home'           => array(
			'selector' => '.vhub',
			'colors'   => array(
				'teal'   => array(
					'--vhub-teal'         => 'base',
					'--vhub-teal-hover'   => 'hover',
					'--vhub-teal-ink'     => 'ink',
					'--vhub-teal-rgb'     => 'rgb',
					'--vhub-teal-heading' => 'heading',
					'--vhub-teal-soft'    => 'soft',
					'--vhub-teal-pale'    => 'pale',
					'--vhub-teal-mid'     => 'mid',
					'--vhub-teal-bg'      => 'bg',
					'--vhub-wash'         => 'wash',
					'--vhub-line'         => 'line',
				),
				'navy'   => array(
					'--vhub-navy'     => 'base',
					'--vhub-navy-rgb' => 'rgb',
				),
				'purple' => array(
					'--vhub-purple'        => 'base',
					'--vhub-purple-ink'    => 'ink',
					'--vhub-purple-tint-b' => 'tint-b',
					'--vhub-purple-line'   => 'line',
					'--vhub-focus'         => 'base',
				),
			),
		),
		'vance-hub-article'        => array(
			'selector' => 'body.vhub-article',
			'colors'   => array(
				'teal'   => array(
					'--vhub-teal'       => 'base',
					'--vhub-teal-hover' => 'hover',
					'--vhub-teal-ink'   => 'ink',
					'--vhub-teal-bg'    => 'bg',
					'--vhub-wash'       => 'wash',
					'--vhub-line'       => 'line',
				),
				'navy'   => array(
					'--vhub-navy'     => 'base',
					'--vhub-navy-rgb' => 'rgb',
				),
				'purple' => array(
					'--vhub-purple'      => 'base',
					'--vhub-purple-ink'  => 'ink',
					'--vhub-purple-wash' => 'wash',
					'--vhub-purple-tint' => 'tint',
					'--vhub-focus'       => 'base',
				),
			),
		),
		'vance-hub-tool-result'    => array(
			'selector' => '.vhub-tr',
			'colors'   => array(
				'teal'   => array(
					'--vhub-teal'         => 'base',
					'--vhub-teal-hover'   => 'hover',
					'--vhub-teal-ink'     => 'ink',
					'--vhub-teal-heading' => 'heading',
					'--vhub-teal-pale'    => 'pale',
					'--vhub-wash'         => 'wash',
				),
				'navy'   => array(
					'--vhub-navy'     => 'base',
					'--vhub-navy-rgb' => 'rgb',
				),
				'purple' => array(
					'--vhub-purple'       => 'base',
					'--vhub-purple-hover' => 'ink',
				),
			),
		),
		'vance-hub-header'         => array(
			'selector' => 'body.vhub-header',
			'colors'   => array(
				'teal'   => array(
					'--vhh-teal'       => 'base',
					'--vhh-teal-hover' => 'hover',
					'--vhh-teal-ink'   => 'ink',
					'--vhh-wash'       => 'wash',
					'--vhh-line'       => 'line',
				),
				'navy'   => array(
					'--vhh-navy'     => 'base',
					'--vhh-navy-rgb' => 'rgb',
				),
				'purple' => array(
					'--vhh-purple'     => 'base',
					'--vhh-purple-ink' => 'ink',
				),
			),
		),
		'vance-hub-dashboard'      => array(
			'selector' => '.vhd',
			'colors'   => array(
				'teal'   => array(
					'--vhd-teal'      => 'base',
					'--vhd-teal-ink'  => 'ink',
					'--vhd-teal-tint' => 'tint',
					'--vhd-wash'      => 'wash',
					'--vhd-line'      => 'line',
				),
				'navy'   => array(
					'--vhd-navy'     => 'base',
					'--vhd-navy-rgb' => 'rgb',
				),
				'purple' => array(
					'--vhd-purple'      => 'base',
					'--vhd-purple-ink'  => 'ink',
					'--vhd-purple-soft' => 'soft',
					'--vhd-purple-mist' => 'mist',
				),
			),
		),
		'vance-hub-dashboard-tabs' => array(
			'selector' => 'body.vhd-tabs',
			'colors'   => array(
				'teal'   => array(
					'--dash-primary'     => 'base',
					'--dash-border'      => 'line',
					'--vhdt-teal'        => 'base',
					'--vhdt-teal-ink'    => 'hover',
					'--vhdt-teal-hover'  => 'hover',
					'--vhdt-teal-deeper' => 'ink',
					'--vhdt-teal-pale'   => 'pale',
					'--vhdt-teal-bg'     => 'bg',
					'--vhdt-wash'        => 'wash',
					'--vhdt-heading'     => 'heading',
					'--vhdt-line'        => 'line',
				),
				'navy'   => array(
					'--dash-sidebar'  => 'base',
					'--vhdt-navy'     => 'base',
					'--vhdt-navy-rgb' => 'rgb',
					'--vhdt-navy-2'   => 'lift',
				),
				'purple' => array(
					'--vhdt-purple'     => 'base',
					'--vhdt-purple-rgb' => 'rgb',
					'--vhdt-purple-ink' => 'ink',
				),
			),
		),
		'vance-hub-community'      => array(
			'selector' => '.vcom',
			'colors'   => array(
				'teal'   => array(
					'--vcom-teal'      => 'base',
					'--vcom-teal-dark' => 'hover',
					'--vcom-teal-ink'  => 'ink',
					'--vcom-teal-soft' => 'soft',
					'--vcom-teal-pale' => 'pale',
					'--vcom-teal-mist' => 'mist',
					'--vcom-wash'      => 'wash',
					'--vcom-bg'        => 'bg',
					'--vcom-line'      => 'line',
				),
				'navy'   => array(
					'--vcom-navy'     => 'base',
					'--vcom-navy-rgb' => 'rgb',
				),
				'purple' => array(
					'--vcom-purple'      => 'base',
					'--vcom-purple-dark' => 'ink',
				),
			),
		),
	);
}

/**
 * The CSS rule for one stylesheet. Pure.
 *
 * Only the colours that differ from their default are printed (compared without
 * regard to letter case). One rule: selector{--a:#XXXXXX;--b:...}.
 *
 * @param string   $handle Stylesheet handle, a key of vance_hub_brand_map().
 * @param string[] $colors Colour name => #RRGGBB, for example array( 'teal' => '#0B6E99' ).
 * @return string The rule, or '' when no colour differs or the handle is unknown.
 */
function vance_hub_brand_css( $handle, array $colors ) {
	$map = vance_hub_brand_map();
	if ( ! isset( $map[ $handle ] ) ) {
		return '';
	}
	$defaults = vance_hub_brand_defaults();
	$body     = '';
	foreach ( $map[ $handle ]['colors'] as $which => $props ) {
		if ( ! isset( $colors[ $which ], $defaults[ $which ] ) ) {
			continue;
		}
		$hex = vance_hub_brand_normalise( $colors[ $which ] );
		if ( '' === $hex || 0 === strcasecmp( $hex, $defaults[ $which ] ) ) {
			continue;
		}
		$shades = vance_hub_brand_shades( $which, $hex );
		foreach ( $props as $prop => $key ) {
			if ( isset( $shades[ $key ] ) && '' !== $shades[ $key ] ) {
				$body .= $prop . ':' . $shades[ $key ] . ';';
			}
		}
	}
	if ( '' === $body ) {
		return '';
	}
	return $map[ $handle ]['selector'] . '{' . $body . '}';
}

/* =========================================================================
 * Settings
 * ====================================================================== */

/**
 * Theme mod name for a brand colour.
 *
 * @param string $which 'teal', 'navy' or 'purple'.
 * @return string
 */
function vance_hub_brand_mod( $which ) {
	return 'vance_hub_color_' . $which;
}

/**
 * Sanitiser of the three colour settings.
 *
 * Gives a valid #RRGGBB (3-digit values are expanded), or the default of the
 * setting. The contrast rule is not applied here: validate_callback refuses a
 * pale colour before it reaches this function, and the reader applies the rule
 * again.
 *
 * @param mixed                     $value   Raw value.
 * @param WP_Customize_Setting|null $setting The setting (core passes it).
 * @return string
 */
function vance_hub_sanitize_brand_color( $value, $setting = null ) {
	$hex = '';
	if ( is_string( $value ) && function_exists( 'sanitize_hex_color' ) ) {
		$clean = sanitize_hex_color( trim( $value ) );
		$hex   = is_string( $clean ) ? vance_hub_brand_normalise( $clean ) : '';
	}
	if ( '' === $hex ) {
		// Outside WordPress, or a value the core function refuses: the pure
		// function expands 3-digit values and checks the shape.
		$hex = vance_hub_brand_normalise( $value );
	}
	if ( '' !== $hex ) {
		return $hex;
	}
	$defaults = vance_hub_brand_defaults();
	foreach ( $defaults as $which => $default ) {
		if ( is_object( $setting ) && isset( $setting->id ) && vance_hub_brand_mod( $which ) === $setting->id ) {
			return $default;
		}
	}
	return '';
}

/**
 * Validator of the three colour settings: refuse a colour that is too pale for
 * white text.
 *
 * Core calls this with a WP_Error that it later reads. A value that is not a
 * colour passes: the sanitiser gives it the default.
 *
 * @param WP_Error|object|null $validity Validity object from core.
 * @param mixed                $value    The value the admin chose.
 * @param object|null          $setting  The setting.
 * @return WP_Error|object|array The same $validity, with an error added when the
 *                               contrast against white is below 4.5. Outside
 *                               WordPress (no WP_Error class) and with no object
 *                               to add to, an array code => message.
 */
function vance_hub_validate_brand_color( $validity, $value = '', $setting = null ) {
	$hex = vance_hub_brand_normalise( $value );
	if ( '' === $hex || vance_hub_brand_contrast( $hex, '#FFFFFF' ) >= 4.5 ) {
		return $validity;
	}
	$code    = 'vance_hub_color_too_pale';
	$message = __( 'This colour is too pale for white text. Choose a darker colour.', 'vance-health-hub' );
	if ( is_object( $validity ) && method_exists( $validity, 'add' ) ) {
		$validity->add( $code, $message );
		return $validity;
	}
	if ( class_exists( 'WP_Error' ) ) {
		return new WP_Error( $code, $message );
	}
	return array( $code => $message );
}

/**
 * One brand colour: the saved value, or the default.
 *
 * An invalid value, or a colour that is too pale for white text, gives the
 * default.
 *
 * @param string $which 'teal', 'navy' or 'purple'.
 * @return string '#RRGGBB' in upper case; '' for an unknown colour name.
 */
function vance_hub_brand_color( $which ) {
	$defaults = vance_hub_brand_defaults();
	if ( ! isset( $defaults[ $which ] ) ) {
		return '';
	}
	$hex = vance_hub_brand_normalise( vance_get_theme_mod( vance_hub_brand_mod( $which ), $defaults[ $which ] ) );
	if ( '' === $hex || vance_hub_brand_contrast( $hex, '#FFFFFF' ) < 4.5 ) {
		return $defaults[ $which ];
	}
	return $hex;
}

/**
 * The three brand colours as the page uses them.
 *
 * @return string[] Colour name => #RRGGBB.
 */
function vance_hub_brand_colors() {
	$out = array();
	foreach ( array_keys( vance_hub_brand_defaults() ) as $which ) {
		$out[ $which ] = vance_hub_brand_color( $which );
	}
	return $out;
}

/* =========================================================================
 * Output
 * ====================================================================== */

/**
 * Print the colour rules after each hub stylesheet that is on the page.
 *
 * Hooked on wp_enqueue_scripts at priority 30: every hub stylesheet is enqueued
 * at 20 (the dashboard tab look at 21). An inline style prints directly after
 * its stylesheet, with the same selector and specificity, so it wins. A handle
 * that is not enqueued gets nothing, and a handle with no colour change gets
 * nothing.
 */
function vance_hub_brand_enqueue() {
	$colors   = vance_hub_brand_colors();
	$defaults = vance_hub_brand_defaults();
	if ( $colors === $defaults ) {
		return;
	}
	foreach ( array_keys( vance_hub_brand_map() ) as $handle ) {
		if ( ! wp_style_is( $handle, 'enqueued' ) ) {
			continue;
		}
		$css = vance_hub_brand_css( $handle, $colors );
		if ( '' !== $css ) {
			wp_add_inline_style( $handle, $css );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'vance_hub_brand_enqueue', 30 );

/* =========================================================================
 * Customizer
 * ====================================================================== */

/**
 * Register the three colour settings and controls.
 *
 * Hooked on customize_register at the default priority, after
 * vance_hub_customize_register() has made the section. The controls sit in
 * "Header and member bar" at priorities 160, 170 and 180, after the member bar
 * texts (110 to 150).
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vance_hub_brand_customize_register( $wp_customize ) {
	$labels = array(
		'teal'   => __( 'Brand colour: teal', 'vance-health-hub' ),
		'navy'   => __( 'Brand colour: navy', 'vance-health-hub' ),
		'purple' => __( 'Brand colour: purple', 'vance-health-hub' ),
	);
	$priority = 150;
	foreach ( vance_hub_brand_defaults() as $which => $default ) {
		$priority += 10;
		$id        = vance_hub_brand_mod( $which );
		$wp_customize->add_setting( $id, array(
			'default'           => $default,
			'sanitize_callback' => 'vance_hub_sanitize_brand_color',
			'validate_callback' => 'vance_hub_validate_brand_color',
		) );
		$args = array(
			'label'    => $labels[ $which ],
			'section'  => 'vance_hub_sec_header',
			'priority' => $priority,
		);
		if ( 'teal' === $which ) {
			$args['description'] = __( 'The colour applies to VanceHubClub pages only. The theme calculates the lighter and darker shades. The theme refuses a colour that is too pale for white text.', 'vance-health-hub' );
		}
		if ( class_exists( 'Vance_Customize_HTML5_Color_Control' ) ) {
			$wp_customize->add_control( new Vance_Customize_HTML5_Color_Control( $wp_customize, $id, $args ) );
		} elseif ( class_exists( 'WP_Customize_Color_Control' ) ) {
			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, $args ) );
		} else {
			// No colour control class (a test): a plain text control, so the
			// setting still has a control.
			$args['type'] = 'text';
			$wp_customize->add_control( $id, $args );
		}
	}
}
add_action( 'customize_register', 'vance_hub_brand_customize_register' );
