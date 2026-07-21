<?php
/**
 * Customizer section: Appearance → Customize → Article Annotations.
 *
 * All settings live inside the single autoloaded option
 * vhh_annotations_options (type => 'option', names like
 * vhh_annotations_options[enabled]).
 *
 * Every sanitize_callback is a plugin-defined static method — never a raw
 * single-arg PHP builtin (the Customizer calls callbacks with two args and
 * PHP 8 builtins fatal on the extra one; see the theme's vance_sanitize_float
 * incident).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_Customizer {

	const SECTION = 'vhh_annotations';

	public static function init() {
		add_action( 'customize_register', array( __CLASS__, 'register' ) );
	}

	/* ------------------------- sanitize wrappers ------------------------ */

	public static function sanitize_checkbox( $value ) {
		return ! empty( $value ) ? 1 : 0;
	}

	public static function sanitize_expiry_days( $value ) {
		$value = (int) $value;
		return max( 1, min( 30, $value ?: 7 ) );
	}

	public static function sanitize_color( $value ) {
		$color = sanitize_hex_color( (string) $value );
		return $color ? $color : '#008080';
	}

	public static function sanitize_resolved_style( $value ) {
		return in_array( $value, array( 'dim', 'hide' ), true ) ? $value : 'dim';
	}

	public static function sanitize_sidebar_position( $value ) {
		return in_array( $value, array( 'right', 'sheet', 'floating' ), true ) ? $value : 'right';
	}

	public static function sanitize_user_id( $value ) {
		$id = absint( $value );
		return ( $id && get_userdata( $id ) ) ? $id : 0;
	}

	/* ----------------------------- register ----------------------------- */

	private static function setting_id( $key ) {
		return VHH_Plugin::OPTION . '[' . $key . ']';
	}

	private static function add_checkbox( $wp_customize, $key, $label, $description = '' ) {
		$defaults = VHH_Plugin::defaults();
		$wp_customize->add_setting(
			self::setting_id( $key ),
			array(
				'type'              => 'option',
				'default'           => ! empty( $defaults[ $key ] ) ? 1 : 0,
				'capability'        => 'manage_options',
				'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
			)
		);
		$wp_customize->add_control(
			self::setting_id( $key ),
			array(
				'label'       => $label,
				'description' => $description,
				'section'     => self::SECTION,
				'type'        => 'checkbox',
			)
		);
	}

	public static function register( $wp_customize ) {
		$wp_customize->add_section(
			self::SECTION,
			array(
				'title'       => __( 'Article Annotations', 'vhh-annotations' ),
				'priority'    => 175,
				'description' => __( 'Highlight-and-comment system for articles. Only logged-in users with the annotate capability ever see any of it.', 'vhh-annotations' ),
			)
		);

		/* Master toggle */
		self::add_checkbox(
			$wp_customize,
			'enabled',
			__( 'Enable annotations', 'vhh-annotations' ),
			__( 'Off = zero assets on the page and all annotation endpoints return 403.', 'vhh-annotations' )
		);

		/* Roles (Admin + Editor always on) */
		$extendable_roles = array( 'author', 'contributor', 'subscriber', 'practitioner', 'patient' );
		$wp_roles         = wp_roles();
		foreach ( $extendable_roles as $role ) {
			if ( ! isset( $wp_roles->roles[ $role ] ) ) {
				continue;
			}
			self::add_checkbox(
				$wp_customize,
				'allow_role_' . $role,
				sprintf(
					/* translators: %s: role display name */
					__( 'Allow role: %s', 'vhh-annotations' ),
					translate_user_role( $wp_roles->roles[ $role ]['name'] )
				)
			);
		}

		/* Post types */
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			if ( 'attachment' === $type->name ) {
				continue;
			}
			self::add_checkbox(
				$wp_customize,
				'pt_' . $type->name,
				sprintf(
					/* translators: %s: post type label */
					__( 'Annotatable: %s', 'vhh-annotations' ),
					$type->labels->name
				)
			);
		}

		/* Feature toggles */
		self::add_checkbox( $wp_customize, 'image_annotation', __( 'Enable image annotation', 'vhh-annotations' ) );
		self::add_checkbox( $wp_customize, 'insertion_annotation', __( 'Enable insertion-point annotation', 'vhh-annotations' ), __( 'Lets reviewers flag "add a new paragraph here" between existing blocks, not just comment on existing text/images.', 'vhh-annotations' ) );
		self::add_checkbox( $wp_customize, 'email_review', __( 'Enable email review links', 'vhh-annotations' ) );

		$wp_customize->add_setting(
			self::setting_id( 'email_expiry_days' ),
			array(
				'type'              => 'option',
				'default'           => 7,
				'capability'        => 'manage_options',
				'sanitize_callback' => array( __CLASS__, 'sanitize_expiry_days' ),
			)
		);
		$wp_customize->add_control(
			self::setting_id( 'email_expiry_days' ),
			array(
				'label'       => __( 'Email link expiry (days)', 'vhh-annotations' ),
				'section'     => self::SECTION,
				'type'        => 'number',
				'input_attrs' => array(
					'min' => 1,
					'max' => 30,
				),
			)
		);

		/* Appearance */
		$wp_customize->add_setting(
			self::setting_id( 'highlight_color' ),
			array(
				'type'              => 'option',
				'default'           => '#008080',
				'capability'        => 'manage_options',
				'sanitize_callback' => array( __CLASS__, 'sanitize_color' ),
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				self::setting_id( 'highlight_color' ),
				array(
					'label'   => __( 'Highlight color', 'vhh-annotations' ),
					'section' => self::SECTION,
				)
			)
		);

		$wp_customize->add_setting(
			self::setting_id( 'resolved_style' ),
			array(
				'type'              => 'option',
				'default'           => 'dim',
				'capability'        => 'manage_options',
				'sanitize_callback' => array( __CLASS__, 'sanitize_resolved_style' ),
			)
		);
		$wp_customize->add_control(
			self::setting_id( 'resolved_style' ),
			array(
				'label'   => __( 'Resolved-mark style', 'vhh-annotations' ),
				'section' => self::SECTION,
				'type'    => 'select',
				'choices' => array(
					'dim'  => __( 'Dim', 'vhh-annotations' ),
					'hide' => __( 'Hide', 'vhh-annotations' ),
				),
			)
		);

		$wp_customize->add_setting(
			self::setting_id( 'sidebar_position' ),
			array(
				'type'              => 'option',
				'default'           => 'right',
				'capability'        => 'manage_options',
				'sanitize_callback' => array( __CLASS__, 'sanitize_sidebar_position' ),
			)
		);
		$wp_customize->add_control(
			self::setting_id( 'sidebar_position' ),
			array(
				'label'   => __( 'Notes panel position', 'vhh-annotations' ),
				'section' => self::SECTION,
				'type'    => 'select',
				'choices' => array(
					'right'    => __( 'Right rail', 'vhh-annotations' ),
					'sheet'    => __( 'Bottom sheet', 'vhh-annotations' ),
					'floating' => __( 'Floating panel', 'vhh-annotations' ),
				),
			)
		);

		/* Notifications */
		self::add_checkbox( $wp_customize, 'notify_author', __( 'Email post author on new annotation', 'vhh-annotations' ) );

		/* Claude integration */
		self::add_checkbox(
			$wp_customize,
			'claude_export',
			__( 'Claude integration: enable export endpoint', 'vhh-annotations' ),
			__( 'Allows an Application Password user with the moderate capability to pull annotations and file to-dos.', 'vhh-annotations' )
		);

		$bot_choices = array( 0 => __( '— none —', 'vhh-annotations' ) );
		foreach ( get_users( array( 'role__in' => array( 'administrator', 'editor' ), 'number' => 50, 'fields' => array( 'ID', 'display_name' ) ) ) as $user ) {
			$bot_choices[ (int) $user->ID ] = $user->display_name;
		}
		$wp_customize->add_setting(
			self::setting_id( 'claude_bot_user' ),
			array(
				'type'              => 'option',
				'default'           => 0,
				'capability'        => 'manage_options',
				'sanitize_callback' => array( __CLASS__, 'sanitize_user_id' ),
			)
		);
		$wp_customize->add_control(
			self::setting_id( 'claude_bot_user' ),
			array(
				'label'       => __( 'Claude bot user', 'vhh-annotations' ),
				'description' => __( 'The Application Password user Claude authenticates as (audit attribution).', 'vhh-annotations' ),
				'section'     => self::SECTION,
				'type'        => 'select',
				'choices'     => $bot_choices,
			)
		);

		self::add_checkbox( $wp_customize, 'auto_resolve', __( 'Auto-resolve annotations when their to-do completes', 'vhh-annotations' ) );

		/* AI-edit engine (reuses the Ask AI OpenRouter key). */
		$wp_customize->add_setting(
			self::setting_id( 'ai_edit_model' ),
			array(
				'type'              => 'option',
				'default'           => '',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			self::setting_id( 'ai_edit_model' ),
			array(
				'label'       => __( 'AI-edit model override', 'vhh-annotations' ),
				'description' => __( 'Optional. The "Generate AI edit preview" button on approved to-dos reuses your Ask AI OpenRouter key. Leave blank to also reuse the Ask AI model, or set an OpenRouter model slug (e.g. openai/gpt-4o) just for edits.', 'vhh-annotations' ),
				'section'     => self::SECTION,
				'type'        => 'text',
			)
		);
	}
}
