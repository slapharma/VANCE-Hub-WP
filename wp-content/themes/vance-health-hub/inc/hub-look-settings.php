<?php
/**
 * VanceHubClub look settings: hero image, header buttons, member bar, dashboard text.
 *
 * Four groups of Customizer settings, all in one file:
 *
 *   - the home page hero photo (vance_hub_hero_image): one media control in
 *     "Home page: blocks and counts";
 *   - the header: the wording of the Sign in, Join free and My Hub buttons and of
 *     the search box, and the link of each button (section "Header and member
 *     bar");
 *   - the member bar: one on/off switch (vance_hub_member_bar) and the wording of
 *     its lines and links (same section);
 *   - the member dashboard home: the greeting, the "Your next step" label and the
 *     title, text and button of each of the four next steps (section "Member
 *     dashboard: text").
 *
 * An empty text always means the default. For the member bar lines, the greeting
 * and the welcome line, the default is the built-in sentence that the template
 * builds with _n() and sprintf(). Those settings have an empty default, and the
 * reader returns '' to say "use the built-in sentence". With nothing saved, every
 * page is byte for byte what it was before this file existed.
 *
 * A text can need a marker, for example {count}. When the saved text does not
 * contain the marker, the reader returns the default. The caller fills the
 * markers with vance_hub_look_fill() and escapes the RESULT.
 *
 * Health and safety wording (consent, disclaimers, "not a diagnosis", urgent
 * help) is not here and never gets a setting.
 *
 * Needs inc/hub-layout.php (the section ids, vance_hub_sanitize_checkbox,
 * vance_hub_img). Loaded from functions.php directly after
 * inc/hub-home-settings.php. Every template that calls a function of this file
 * checks function_exists() first and falls back to its old literal.
 *
 * To back the feature out, delete the require line in functions.php. The
 * templates then print their literals again. Saved values stay in the database
 * and do no harm.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * Texts
 * ====================================================================== */

/**
 * The 24 editable texts: header, member bar and member dashboard.
 *
 * 'default' is the wording that the templates had before the texts became
 * editable. For the member bar lines, the greeting and the welcome line it is
 * '' on purpose: the built-in sentence stays in the template (it uses _n() and
 * sprintf()) and '' means "use it".
 * 'group' is where the text shows: header, bar or dash.
 * 'type' is the Customizer control type and picks the sanitiser.
 * 'needs' is '' or a marker, for example {count}. A saved text that does not
 * contain the marker is ignored.
 *
 * @return array[] Theme mod name => array( default, label, group, type, needs ).
 */
function vance_hub_look_text_defaults() {
	return array(
		// Header buttons and search.
		'vance_hub_text_signin'           => array(
			'default' => __( 'Sign in', 'vance-health-hub' ),
			'label'   => __( 'Header: Sign in button', 'vance-health-hub' ),
			'group'   => 'header',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_join'             => array(
			'default' => __( 'Join free', 'vance-health-hub' ),
			'label'   => __( 'Header: Join free button', 'vance-health-hub' ),
			'group'   => 'header',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_myhub'            => array(
			'default' => __( 'My Hub', 'vance-health-hub' ),
			'label'   => __( 'Header: My Hub button (members)', 'vance-health-hub' ),
			'group'   => 'header',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_search'           => array(
			'default' => __( 'Search articles, tools, recipes...', 'vance-health-hub' ),
			'label'   => __( 'Header: search box hint', 'vance-health-hub' ),
			'group'   => 'header',
			'type'    => 'text',
			'needs'   => '',
		),
		// Member bar.
		'vance_hub_text_bar_total'        => array(
			'default' => '',
			'label'   => __( 'Member bar: member total', 'vance-health-hub' ),
			'group'   => 'bar',
			'type'    => 'text',
			'needs'   => '{count}',
		),
		'vance_hub_text_bar_week'         => array(
			'default' => '',
			'label'   => __( 'Member bar: new members this week', 'vance-health-hub' ),
			'group'   => 'bar',
			'type'    => 'text',
			'needs'   => '{count}',
		),
		'vance_hub_text_bar_founding'     => array(
			'default' => '',
			'label'   => __( 'Founding Member line', 'vance-health-hub' ),
			'group'   => 'bar',
			'type'    => 'text',
			'needs'   => '{date}',
		),
		'vance_hub_text_bar_link_visitor' => array(
			'default' => __( 'See what members get', 'vance-health-hub' ),
			'label'   => __( 'Member bar: link for visitors', 'vance-health-hub' ),
			'group'   => 'bar',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_bar_link_member'  => array(
			'default' => __( 'Go to My Hub', 'vance-health-hub' ),
			'label'   => __( 'Member bar: link for members', 'vance-health-hub' ),
			'group'   => 'bar',
			'type'    => 'text',
			'needs'   => '',
		),
		// Member dashboard home.
		'vance_hub_text_dash_hello'       => array(
			'default' => '',
			'label'   => __( 'Dashboard: greeting', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'text',
			'needs'   => '{name}',
		),
		'vance_hub_text_dash_welcome'     => array(
			'default' => '',
			'label'   => __( 'Dashboard: welcome line in the side menu card', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_dash_step_label'  => array(
			'default' => __( 'Your next step', 'vance-health-hub' ),
			'label'   => __( 'Dashboard: small label above the next step', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_dash_survey_title'  => array(
			'default' => __( 'Do the Gastro Self-Assessment', 'vance-health-hub' ),
			'label'   => __( 'Next step, Self-Assessment: title', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_dash_survey_text'   => array(
			'default' => __( 'Your answers go into your Health Profile. The Hub then shows what fits you.', 'vance-health-hub' ),
			'label'   => __( 'Next step, Self-Assessment: text', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'textarea',
			'needs'   => '',
		),
		'vance_hub_text_dash_survey_button' => array(
			'default' => __( 'Start the survey', 'vance-health-hub' ),
			'label'   => __( 'Next step, Self-Assessment: button', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_dash_profile_title'  => array(
			'default' => __( 'Complete your Health Profile', 'vance-health-hub' ),
			'label'   => __( 'Next step, Health Profile: title', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_dash_profile_text'   => array(
			'default' => __( 'Add your condition and your care details. You can change them at any time.', 'vance-health-hub' ),
			'label'   => __( 'Next step, Health Profile: text', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'textarea',
			'needs'   => '',
		),
		'vance_hub_text_dash_profile_button' => array(
			'default' => __( 'Open my Health Profile', 'vance-health-hub' ),
			'label'   => __( 'Next step, Health Profile: button', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_dash_plan_title'     => array(
			'default' => __( 'Build your first meal plan', 'vance-health-hub' ),
			'label'   => __( 'Next step, meal plan: title', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_dash_plan_text'      => array(
			'default' => __( 'Choose recipes for the week. Your saved plans show on your dashboard.', 'vance-health-hub' ),
			'label'   => __( 'Next step, meal plan: text', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'textarea',
			'needs'   => '',
		),
		'vance_hub_text_dash_plan_button'    => array(
			'default' => __( 'Open the planner', 'vance-health-hub' ),
			'label'   => __( 'Next step, meal plan: button', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_dash_check_title'    => array(
			'default' => __( 'Check your nutrition risk', 'vance-health-hub' ),
			'label'   => __( 'Next step, nutrition check: title', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_dash_check_text'     => array(
			'default' => __( 'A short check with a clear result. Your saved checks show in your Health Profile.', 'vance-health-hub' ),
			'label'   => __( 'Next step, nutrition check: text', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'textarea',
			'needs'   => '',
		),
		'vance_hub_text_dash_check_button'   => array(
			'default' => __( 'Start the check', 'vance-health-hub' ),
			'label'   => __( 'Next step, nutrition check: button', 'vance-health-hub' ),
			'group'   => 'dash',
			'type'    => 'text',
			'needs'   => '',
		),
	);
}

/**
 * Notes that the Customizer shows under a control, for the texts whose default
 * is the built-in sentence. Kept apart from the registry so the registry rows
 * keep exactly five keys.
 *
 * @return string[] Theme mod name => note.
 */
function vance_hub_look_text_notes() {
	return array(
		'vance_hub_text_bar_total'    => __( 'Use {count} for the number of members. The built-in line is "125 members".', 'vance-health-hub' ),
		'vance_hub_text_bar_week'     => __( 'Use {count} for the number of new members. The built-in line is "12 joined this week". It shows only when at least one member joined this week.', 'vance-health-hub' ),
		'vance_hub_text_bar_founding' => __( 'Use {date} for the deadline. The built-in line is "Join by 31 December 2026 and keep the Founding Member mark." The same line shows in the Join block on the home page.', 'vance-health-hub' ),
		'vance_hub_text_dash_hello'   => __( 'Use {name} for the name of the member. The built-in line is "Hello, Ruth".', 'vance-health-hub' ),
		'vance_hub_text_dash_welcome' => __( 'You can use {name} for the first name of the member. A member with no first name sees the line without it. The built-in line is "Welcome back Ruth".', 'vance-health-hub' ),
	);
}

/**
 * One text: the saved wording, or the default.
 *
 * The default comes back when the saved value is empty, is only spaces, is not
 * a string, or does not contain the marker that the text needs. For a text
 * whose default is '', that means "use the built-in sentence".
 *
 * @param string $mod Theme mod name (a key of vance_hub_look_text_defaults()).
 * @return string Plain text, not yet escaped, markers not yet filled. '' for an
 *                unknown name.
 */
function vance_hub_look_text( $mod ) {
	$defaults = vance_hub_look_text_defaults();
	if ( ! isset( $defaults[ $mod ] ) ) {
		return '';
	}
	$row   = $defaults[ $mod ];
	$saved = vance_get_theme_mod( $mod, $row['default'] );
	$saved = is_string( $saved ) ? trim( $saved ) : '';
	if ( '' === $saved ) {
		return $row['default'];
	}
	if ( '' !== $row['needs'] && false === strpos( $saved, $row['needs'] ) ) {
		return $row['default'];
	}
	return $saved;
}

/**
 * A member bar text, for the three lines that have a built-in sentence.
 *
 * @param string $mod Theme mod name of a member bar text.
 * @return string The saved text, or '' to say "use the built-in sentence" (and
 *                for a name that is not a member bar text). The two link texts
 *                have a default and never return ''.
 */
function vance_hub_look_bar_text( $mod ) {
	$defaults = vance_hub_look_text_defaults();
	if ( ! isset( $defaults[ $mod ] ) || 'bar' !== $defaults[ $mod ]['group'] ) {
		return '';
	}
	return vance_hub_look_text( $mod );
}

/**
 * Put values into the markers of a text.
 *
 * The caller escapes the RESULT for the place where it prints. This function
 * trusts neither the template nor the values.
 *
 * @param string   $text  Text with markers, for example "{count} members".
 * @param string[] $pairs Marker => value.
 * @return string
 */
function vance_hub_look_fill( $text, array $pairs ) {
	return strtr( (string) $text, $pairs );
}

/**
 * The Founding Member line from a saved text, or '' to use the built-in line.
 *
 * One function for the two places that print this line: the member bar and the
 * Join block on the home page.
 *
 * @param string $date The deadline, already formatted for display.
 * @return string Plain text, not yet escaped. '' when no valid text is saved.
 */
function vance_hub_look_founding_line( $date ) {
	$text = vance_hub_look_bar_text( 'vance_hub_text_bar_founding' );
	return '' === $text ? '' : vance_hub_look_fill( $text, array( '{date}' => (string) $date ) );
}

/**
 * The dashboard greeting from a saved text, or '' to use the built-in line.
 *
 * @param string $name Name of the member.
 * @return string Plain text, not yet escaped.
 */
function vance_hub_look_hello( $name ) {
	$text = vance_hub_look_text( 'vance_hub_text_dash_hello' );
	if ( '' === $text ) {
		return '';
	}
	return trim( vance_hub_look_fill( $text, array( '{name}' => (string) $name ) ) );
}

/**
 * Fill the welcome template for one member. Pure.
 *
 * With a first name, {name} becomes the first name. With no first name, the
 * marker goes away together with what dangles around it:
 *   1. each {name} is removed, with the spaces before it and one comma, semicolon
 *      or colon directly before those spaces ("Welcome back, {name}!" gives
 *      "Welcome back!");
 *   2. every run of two or more spaces or tabs becomes one space;
 *   3. spaces, commas, semicolons and colons at the start are removed;
 *   4. spaces at the start and at the end are removed.
 * Nothing else changes: a dash or any other character stays where it is.
 *
 * @param string $template Saved text, may hold {name}.
 * @param string $first    First name of the member, '' when there is none.
 * @return string Plain text, not yet escaped. May be '' (the caller then uses
 *                the built-in line).
 */
function vance_hub_look_welcome_apply( $template, $first ) {
	$template = (string) $template;
	$first    = trim( (string) $first );
	if ( '' !== $first ) {
		return trim( strtr( $template, array( '{name}' => $first ) ) );
	}
	$text = preg_replace( '/[ \t]*[,;:]?[ \t]*\{name\}/', '', $template );
	$text = preg_replace( '/[ \t]{2,}/', ' ', (string) $text );
	$text = preg_replace( '/^[ \t,;:]+/', '', (string) $text );
	return trim( (string) $text );
}

/**
 * The welcome line of the side menu card from a saved text, or '' to use the
 * built-in line.
 *
 * @param string $first First name of the member, '' when there is none.
 * @return string Plain text, not yet escaped.
 */
function vance_hub_look_welcome( $first ) {
	$text = vance_hub_look_text( 'vance_hub_text_dash_welcome' );
	return '' === $text ? '' : vance_hub_look_welcome_apply( $text, $first );
}

/* =========================================================================
 * Links
 * ====================================================================== */

/**
 * The three header button links: which => theme mod and default path.
 *
 * @return array[] 'signin', 'join' and 'myhub', each array( mod, path ).
 */
function vance_hub_look_link_defaults() {
	return array(
		'signin' => array( 'vance_hub_link_signin', '/login/' ),
		'join'   => array( 'vance_hub_link_join', '/login/?tab=signup' ),
		'myhub'  => array( 'vance_hub_link_myhub', '/dashboard/' ),
	);
}

/**
 * The shape check of a link. Pure: it calls no WordPress function.
 *
 * A link is valid when it is:
 *   - an address that starts with http:// or https:// and has a host; or
 *   - a site path that starts with one slash ("/login/"), not two.
 * A link with a space, a control character or a backslash is not valid, and
 * neither is any other scheme (javascript:, data:, mailto:) or a bare word.
 *
 * @param mixed $value Raw value.
 * @return string The trimmed link, or '' when it is not valid.
 */
function vance_hub_look_link_shape( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$value = trim( $value );
	if ( '' === $value || preg_match( '/[[:cntrl:][:space:]]/', $value ) || false !== strpos( $value, chr( 92 ) ) ) {
		return '';
	}
	if ( 0 === strpos( $value, '/' ) ) {
		return 0 === strpos( $value, '//' ) ? '' : $value;
	}
	return preg_match( '~^https?://[^/?#]+~i', $value ) ? $value : '';
}

/**
 * Sanitise one link setting: the shape check first, then esc_url_raw() with
 * only http and https allowed.
 *
 * The shape check comes first because esc_url_raw() alone does not give an
 * empty value for "javascript:alert(1)": it removes the scheme and keeps the
 * rest.
 *
 * @param mixed $value Raw value.
 * @return string A valid link, or ''.
 */
function vance_hub_sanitize_link( $value ) {
	$link = vance_hub_look_link_shape( $value );
	if ( '' === $link ) {
		return '';
	}
	return function_exists( 'esc_url_raw' ) ? esc_url_raw( $link, array( 'http', 'https' ) ) : $link;
}

/**
 * One header button link: the saved link, or today's address.
 *
 * A saved site path ("/login/") goes through home_url(), so it also works on a
 * site that lives in a sub-folder. A saved full address stays as it is. The
 * template escapes the result with esc_url().
 *
 * @param string $which 'signin', 'join' or 'myhub'.
 * @return string URL. '' for an unknown name.
 */
function vance_hub_look_link( $which ) {
	$defaults = vance_hub_look_link_defaults();
	if ( ! isset( $defaults[ $which ] ) ) {
		return '';
	}
	list( $mod, $path ) = $defaults[ $which ];
	$saved = vance_hub_look_link_shape( vance_get_theme_mod( $mod, '' ) );
	if ( '' === $saved ) {
		return home_url( $path );
	}
	return 0 === strpos( $saved, '/' ) ? home_url( $saved ) : $saved;
}

/* =========================================================================
 * Hero image
 * ====================================================================== */

/**
 * Make a URL safe inside a CSS url('...') in a style attribute. Pure.
 *
 * esc_url() leaves ( and ) as they are, and its &#039; for ' is decoded again
 * by the browser inside the attribute. Each of the three would end the CSS
 * string early. They become %27, %28 and %29, which every browser reads back as
 * the same characters in the address. The caller still escapes the result.
 * Use it only for the CSS url(), not for an <img src>.
 *
 * @param string $url URL.
 * @return string URL with ', ( and ) percent-encoded.
 */
function vance_hub_look_css_url( $url ) {
	return strtr( (string) $url, array( "'" => '%27', '(' => '%28', ')' => '%29' ) );
}

/**
 * Put the saved hero image on the standard one. Pure: no theme mod is read.
 *
 * With a valid attachment, the image is the 'large' size of that attachment and
 * the alt text is the alt text of the attachment. That alt text may be empty.
 * The standard alt describes another picture, so it is never kept with a custom
 * image. When the attachment gives no URL (it was deleted, or it is not an
 * image), the standard array comes back unchanged. When the attachment gives no
 * positive width or height, the standard number is used for the missing one, so
 * the markup keeps both attributes.
 *
 * @param int   $id       Attachment id, 0 for none.
 * @param array $standard Today's image: src, alt, width and height.
 * @return array src, alt, width, height.
 */
function vance_hub_hero_image_apply( $id, array $standard ) {
	$id = max( 0, (int) $id );
	if ( $id < 1 || ! function_exists( 'wp_get_attachment_image_src' ) ) {
		return $standard;
	}
	$image = wp_get_attachment_image_src( $id, 'large' );
	if ( ! is_array( $image ) || empty( $image[0] ) || ! is_string( $image[0] ) ) {
		return $standard;
	}
	$width  = isset( $image[1] ) && is_numeric( $image[1] ) ? (int) $image[1] : 0;
	$height = isset( $image[2] ) && is_numeric( $image[2] ) ? (int) $image[2] : 0;
	return array(
		'src'    => $image[0],
		'alt'    => trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
		'width'  => $width > 0 ? $width : ( isset( $standard['width'] ) ? (int) $standard['width'] : 800 ),
		'height' => $height > 0 ? $height : ( isset( $standard['height'] ) ? (int) $standard['height'] : 650 ),
	);
}

/**
 * The hero image that front-page-hub.php prints.
 *
 * With nothing saved, the standard image: the theme photo, its alt text, 800 by
 * 650. The src is '' when the theme file is missing, and the template then
 * prints no image.
 *
 * @return array src, alt, width, height.
 */
function vance_hub_hero_image() {
	$standard = array(
		'src'    => vance_hub_img( 'about/community-support.jpg' ),
		'alt'    => __( 'Five people sit in a circle of chairs and talk; one woman holds a clipboard', 'vance-health-hub' ),
		'width'  => 800,
		'height' => 650,
	);
	return vance_hub_hero_image_apply( (int) vance_get_theme_mod( 'vance_hub_hero_image', 0 ), $standard );
}

/* =========================================================================
 * Customizer
 * ====================================================================== */

/**
 * Register the controls of this file.
 *
 * Hooked on customize_register at the default priority, after
 * vance_hub_customize_register() and vance_hub_header_customize_register(), so
 * the panel and its sections exist when this runs. Priorities:
 *
 *   - "Home page: blocks and counts": the hero image at 70 (the block order and
 *     the counts use 10 to 60);
 *   - "Header and member bar": the header switch is 10 and the two existing
 *     member bar fields are 20 and 30. The new member bar switch is 15, the four
 *     header texts are 40 to 70, the three links are 80 to 100 and the five
 *     member bar texts are 110 to 150;
 *   - "Member dashboard: text": the 15 texts, 10 to 150.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vance_hub_look_customize_register( $wp_customize ) {
	// Hero image.
	$wp_customize->add_setting( 'vance_hub_hero_image', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
	) );
	$image_args = array(
		'label'       => __( 'Hero photo', 'vance-health-hub' ),
		'description' => __( 'The photo next to the title at the top of the home page. Leave empty to use the standard photo. Fill the "Alternative Text" field of the photo in the media library: screen readers read it.', 'vance-health-hub' ),
		'section'     => 'vance_hub_sec_home',
		'priority'    => 70,
	);
	if ( class_exists( 'WP_Customize_Media_Control' ) ) {
		$image_args['mime_type'] = 'image';
		$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'vance_hub_hero_image', $image_args ) );
	} else {
		// No core media control (a test): a plain number control, so the
		// setting still has a control.
		$image_args['type'] = 'number';
		$wp_customize->add_control( 'vance_hub_hero_image', $image_args );
	}

	// Member bar switch.
	$switch = vance_hub_customizer_switch( 'vance_hub_member_bar' );
	$wp_customize->add_setting( 'vance_hub_member_bar', array(
		'default'           => vance_hub_switch_default( 'vance_hub_member_bar' ),
		'sanitize_callback' => 'vance_hub_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'vance_hub_member_bar', array(
		'label'       => $switch['label'],
		'description' => __( 'On: the purple bar shows below the header with the member total or the Founding Member line. Off: the bar does not show. The Founding Member line in the Join block of the home page does not change.', 'vance-health-hub' ),
		'section'     => $switch['section'],
		'type'        => 'checkbox',
		'priority'    => 15,
	) );

	// The 24 texts.
	$where    = array(
		'header' => __( 'This text shows in the header on every page.', 'vance-health-hub' ),
		'bar'    => __( 'This text shows in the purple member bar.', 'vance-health-hub' ),
		'dash'   => __( 'This text shows on the home screen of the member dashboard.', 'vance-health-hub' ),
	);
	$sections = array(
		'header' => 'vance_hub_sec_header',
		'bar'    => 'vance_hub_sec_header',
		'dash'   => 'vance_hub_sec_dashboard_text',
	);
	$next     = array( 'header' => 40, 'bar' => 110, 'dash' => 10 );
	$seen     = array();
	$notes    = vance_hub_look_text_notes();
	foreach ( vance_hub_look_text_defaults() as $mod => $row ) {
		$group       = $row['group'];
		$priority    = $next[ $group ];
		$next[ $group ] += 10;
		$description = __( 'Leave empty to use the default.', 'vance-health-hub' );
		if ( '' !== $row['needs'] ) {
			/* translators: %s: a marker such as {count}. */
			$description .= ' ' . sprintf( __( 'The text must contain %s, or the default is used.', 'vance-health-hub' ), $row['needs'] );
		}
		if ( isset( $notes[ $mod ] ) ) {
			$description .= ' ' . $notes[ $mod ];
		}
		if ( ! isset( $seen[ $group ] ) ) {
			$seen[ $group ] = true;
			$description   .= ' ' . $where[ $group ];
		}
		$wp_customize->add_setting( $mod, array(
			'default'           => $row['default'],
			'sanitize_callback' => 'textarea' === $row['type'] ? 'sanitize_textarea_field' : 'sanitize_text_field',
		) );
		$wp_customize->add_control( $mod, array(
			'label'       => $row['label'],
			'description' => $description,
			'section'     => $sections[ $group ],
			'type'        => $row['type'],
			'priority'    => $priority,
		) );
	}

	// The three header links, priorities 80 to 100.
	$labels   = array(
		'signin' => __( 'Header: Sign in link', 'vance-health-hub' ),
		'join'   => __( 'Header: Join free link', 'vance-health-hub' ),
		'myhub'  => __( 'Header: My Hub link (members)', 'vance-health-hub' ),
	);
	$priority = 70;
	foreach ( vance_hub_look_link_defaults() as $which => $row ) {
		$priority += 10;
		$wp_customize->add_setting( $row[0], array(
			'default'           => '',
			'sanitize_callback' => 'vance_hub_sanitize_link',
		) );
		$wp_customize->add_control( $row[0], array(
			'label'       => $labels[ $which ],
			/* translators: %s: the default path of the link, for example /login/. */
			'description' => sprintf( __( 'Leave empty to use the default (%s). Use an address that starts with http or https, or a path that starts with one slash. Any other value is not saved.', 'vance-health-hub' ), $row[1] ),
			'section'     => 'vance_hub_sec_header',
			'type'        => 'text',
			'priority'    => $priority,
		) );
	}
}
add_action( 'customize_register', 'vance_hub_look_customize_register' );
