<?php
/**
 * VanceHubClub community settings: the weekly poll switch and five wordings.
 *
 * Six Customizer settings, all in the section "Community" (vance_hub_sec_community),
 * under the community switch (vance_hub_community, priority 10):
 *
 *   - vance_hub_community_poll (priority 20): show the weekly poll. A sub-switch
 *     of the community. The community's own switch decides whether the page
 *     shows at all, so this one is read as its own value (default on). Off: the
 *     page prints no poll block and the vote handler refuses a vote. Saved
 *     votes stay in the database.
 *   - vance_hub_text_com_visitor_intro (30): the paragraph under the title on
 *     the page that a visitor sees.
 *   - vance_hub_text_com_member_intro (40): the paragraph under the title on the
 *     page that a member sees. The fixed sentence "A moderator reads every
 *     post." always follows a saved text, so a custom text cannot remove it.
 *   - vance_hub_text_com_note (50): one extra line at the end of the box
 *     "PRIVATE AND MODERATED" (visitors) and of the box "HOW THIS SPACE WORKS"
 *     (members). Empty means no line.
 *   - vance_hub_link_com_rules (60) and vance_hub_text_com_rules_label (70): the
 *     address and the words of the community rules link.
 *
 * An empty text always means the built-in wording. With nothing saved, every
 * page is byte for byte what it was before this file existed.
 *
 * Health and safety wording is not here and never gets a setting: "They do not
 * give medical advice", the sentences that say a moderator reads every post, the
 * nickname warning, "We never show your surname or your email address", the NHS
 * 111 and 999 text and the report and reject reasons stay in the templates.
 *
 * Needs inc/hub-layout.php (the section id, vance_hub_sanitize_checkbox and the
 * switch list) and inc/hub-look-settings.php (the link shape check and the link
 * sanitiser). Loaded from functions.php directly after inc/hub-brand.php. Every
 * template that calls a function of this file checks function_exists() first
 * and falls back to its old literal.
 *
 * To back the feature out, delete the require line in functions.php, and remove
 * the vance_hub_community_poll row from vance_hub_customizer_switches() in
 * inc/hub-layout.php. The templates then print their literals again, and the
 * poll shows again. Saved values stay in the database and do no harm.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * The poll switch
 * ====================================================================== */

/**
 * True when the weekly poll is switched on.
 *
 * The raw value of the sub-switch, not combined with the master switch: the
 * community's own vance_hub_community_active() already decides whether the page
 * shows, and vance_community_writes_open() whether a member may write.
 *
 * @return bool
 */
function vance_hub_community_poll_on() {
	return (bool) vance_hub_sanitize_checkbox( vance_get_theme_mod( 'vance_hub_community_poll', true ) );
}

/* =========================================================================
 * Texts
 * ====================================================================== */

/**
 * The four editable texts of the Community page.
 *
 * 'default' is the wording that the template had before the text became
 * editable. For the member intro it is the first two sentences only: the
 * template adds the fixed sentence "A moderator reads every post." after a
 * saved text. For the extra line it is '' and means "print nothing".
 * 'group' is where the text shows: intro, note or rules.
 * 'type' is the Customizer control type and picks the sanitiser.
 * 'needs' is '' for all four (the registry keeps the five-key shape of
 * vance_hub_look_text_defaults()).
 *
 * @return array[] Theme mod name => array( default, label, group, type, needs ).
 */
function vance_hub_community_text_defaults() {
	return array(
		'vance_hub_text_com_visitor_intro' => array(
			'default' => __( 'A private, moderated space for people living with digestive conditions to share experiences and connect. Free membership. Join at your own pace.', 'vance-health-hub' ),
			'label'   => __( 'Community: text under the title (visitors)', 'vance-health-hub' ),
			'group'   => 'intro',
			'type'    => 'textarea',
			'needs'   => '',
		),
		'vance_hub_text_com_member_intro'  => array(
			'default' => __( 'Ask people who live with a gut condition. Share what works for you.', 'vance-health-hub' ),
			'label'   => __( 'Community: text under the title (members)', 'vance-health-hub' ),
			'group'   => 'intro',
			'type'    => 'textarea',
			'needs'   => '',
		),
		'vance_hub_text_com_note'          => array(
			'default' => '',
			'label'   => __( 'Community: extra line in the information box', 'vance-health-hub' ),
			'group'   => 'note',
			'type'    => 'text',
			'needs'   => '',
		),
		'vance_hub_text_com_rules_label'   => array(
			'default' => __( 'Read the community rules', 'vance-health-hub' ),
			'label'   => __( 'Community: words of the rules link', 'vance-health-hub' ),
			'group'   => 'rules',
			'type'    => 'text',
			'needs'   => '',
		),
	);
}

/**
 * Notes that the Customizer shows under a control. Kept apart from the registry
 * so the registry rows keep exactly five keys.
 *
 * @return string[] Theme mod name => note.
 */
function vance_hub_community_text_notes() {
	return array(
		'vance_hub_text_com_visitor_intro' => __( 'This text shows on the page that a visitor sees, under the title.', 'vance-health-hub' ),
		'vance_hub_text_com_member_intro'  => __( 'This text shows on the page that a member sees, under the title. The sentence "A moderator reads every post." always follows your text. Do not write it yourself.', 'vance-health-hub' ),
		'vance_hub_text_com_note'          => __( 'The default is no extra line. A saved line shows at the end of the "PRIVATE AND MODERATED" box for visitors and of the "HOW THIS SPACE WORKS" box for members. Do not use it for medical advice.', 'vance-health-hub' ),
		'vance_hub_text_com_rules_label'   => __( 'The rules link shows only when it has an address: the link below, or the published page /community-rules/.', 'vance-health-hub' ),
	);
}

/**
 * One text: the saved wording, or the default.
 *
 * The default comes back when the saved value is empty, is only spaces, or is
 * not a string.
 *
 * @param string $mod Theme mod name (a key of vance_hub_community_text_defaults()).
 * @return string Plain text, not yet escaped. '' for an unknown name.
 */
function vance_hub_community_text( $mod ) {
	$defaults = vance_hub_community_text_defaults();
	if ( ! isset( $defaults[ $mod ] ) ) {
		return '';
	}
	$default = $defaults[ $mod ]['default'];
	$saved   = vance_get_theme_mod( $mod, $default );
	$saved   = is_string( $saved ) ? trim( $saved ) : '';
	return '' === $saved ? $default : $saved;
}

/**
 * A text that an administrator really changed, or '' to say "use the built-in
 * wording of the template".
 *
 * '' comes back when nothing is saved, when the saved text is only spaces and
 * when the saved text equals the default. The template then prints its own
 * literal, so a page with nothing saved stays byte for byte as it was.
 *
 * @param string $mod Theme mod name.
 * @return string Plain text, not yet escaped.
 */
function vance_hub_community_text_custom( $mod ) {
	$defaults = vance_hub_community_text_defaults();
	if ( ! isset( $defaults[ $mod ] ) ) {
		return '';
	}
	$text = vance_hub_community_text( $mod );
	return $text === $defaults[ $mod ]['default'] ? '' : $text;
}

/* =========================================================================
 * The rules link
 * ====================================================================== */

/**
 * The saved address of the community rules, as a full URL, or '' when none is
 * saved or the saved value is not a valid link.
 *
 * A saved site path ("/community-rules/") goes through home_url(), as the header
 * links do. A saved full address stays as it is. The template escapes the
 * result with esc_url().
 *
 * @return string
 */
function vance_hub_community_rules_custom_url() {
	if ( ! function_exists( 'vance_hub_look_link_shape' ) ) {
		return '';
	}
	$saved = vance_hub_look_link_shape( vance_get_theme_mod( 'vance_hub_link_com_rules', '' ) );
	if ( '' === $saved ) {
		return '';
	}
	return 0 === strpos( $saved, '/' ) ? home_url( $saved ) : $saved;
}

/* =========================================================================
 * Customizer
 * ====================================================================== */

/**
 * Register the controls of this file.
 *
 * Hooked on customize_register at priority 11, after vance_hub_customize_register()
 * (which makes the section) and after vance_hub_community_customize_register()
 * (which holds the community switch at priority 10 in the same section).
 * Priorities in "Community": the community switch is 10, the poll switch 20, the
 * two intros 30 and 40, the extra line 50, the rules link 60 and the rules words
 * 70.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vance_hub_community_settings_customize_register( $wp_customize ) {
	// The poll switch.
	$switch = vance_hub_customizer_switch( 'vance_hub_community_poll' );
	$wp_customize->add_setting( 'vance_hub_community_poll', array(
		'default'           => vance_hub_switch_default( 'vance_hub_community_poll' ),
		'sanitize_callback' => 'vance_hub_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'vance_hub_community_poll', array(
		'label'       => $switch['label'],
		'description' => __( 'On: the weekly poll shows in the side column of the Community page. Off: the poll does not show and members cannot vote. Votes that members already made stay saved.', 'vance-health-hub' ),
		'section'     => $switch['section'],
		'type'        => 'checkbox',
		'priority'    => 20,
	) );

	// The texts and the rules link, in the order of their priorities.
	$priority = array(
		'vance_hub_text_com_visitor_intro' => 30,
		'vance_hub_text_com_member_intro'  => 40,
		'vance_hub_text_com_note'          => 50,
		'vance_hub_text_com_rules_label'   => 70,
	);
	$notes    = vance_hub_community_text_notes();
	foreach ( vance_hub_community_text_defaults() as $mod => $row ) {
		$wp_customize->add_setting( $mod, array(
			'default'           => $row['default'],
			'sanitize_callback' => 'textarea' === $row['type'] ? 'sanitize_textarea_field' : 'sanitize_text_field',
		) );
		$wp_customize->add_control( $mod, array(
			'label'       => $row['label'],
			'description' => __( 'Leave empty to use the default.', 'vance-health-hub' ) . ' ' . $notes[ $mod ],
			'section'     => 'vance_hub_sec_community',
			'type'        => $row['type'],
			'priority'    => $priority[ $mod ],
		) );
	}

	// The rules link.
	$wp_customize->add_setting( 'vance_hub_link_com_rules', array(
		'default'           => '',
		'sanitize_callback' => 'vance_hub_sanitize_link',
	) );
	$wp_customize->add_control( 'vance_hub_link_com_rules', array(
		'label'       => __( 'Community: address of the rules link', 'vance-health-hub' ),
		'description' => __( 'Leave empty to use the default (the page /community-rules/, when it is published). Use an address that starts with http or https, or a path that starts with one slash. Any other value is not saved.', 'vance-health-hub' ),
		'section'     => 'vance_hub_sec_community',
		'type'        => 'text',
		'priority'    => 60,
	) );
}
add_action( 'customize_register', 'vance_hub_community_settings_customize_register', 11 );
