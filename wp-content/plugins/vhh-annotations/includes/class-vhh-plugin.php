<?php
/**
 * Orchestrator for VHH Annotations: settings access + hook wiring.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_Plugin {

	const OPTION = 'vhh_annotations_options';

	/** @var VHH_Plugin|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Data isolation is unconditional — annotations must never surface as
		// native comments even while the feature toggle is off.
		VHH_Annotation_Store::register_isolation_filters();

		add_action( 'rest_api_init', array( 'VHH_REST_Annotations', 'register_routes' ) );
		add_action( 'rest_api_init', array( 'VHH_REST_Todos', 'register_routes' ) );
		add_action( 'rest_api_init', array( 'VHH_REST_Review', 'register_routes' ) );

		VHH_Frontend::init();
		VHH_Customizer::init();
		VHH_Todos_CPT::init();
		VHH_Apply::init();
		VHH_Review_Emails::init();
		VHH_Privacy::init();

		// Re-sync role capabilities whenever the allowed-roles setting changes.
		add_action( 'update_option_' . self::OPTION, array( $this, 'on_options_updated' ), 10, 2 );
	}

	public function on_options_updated( $old, $new ) {
		self::flush_options_cache();
		VHH_Capabilities::sync_annotate_roles( self::extra_annotate_roles( is_array( $new ) ? $new : array() ) );
	}

	/** Roles beyond Admin/Editor granted vhh_annotate via allow_role_* flags. */
	public static function extra_annotate_roles( array $options ) {
		$roles = array();
		foreach ( $options as $key => $value ) {
			if ( 0 === strpos( $key, 'allow_role_' ) && $value ) {
				$roles[] = substr( $key, strlen( 'allow_role_' ) );
			}
		}
		return $roles;
	}

	/* ------------------------------------------------------------------ */

	public static function defaults() {
		return array(
			'enabled'           => false,
			'image_annotation'  => true,
			'email_review'      => false,
			'email_expiry_days' => 7,
			'pt_post'           => true,   // pt_{post_type} flags → post_types
			'highlight_color'   => '#008080',
			'resolved_style'    => 'dim',   // dim | hide
			'sidebar_position'  => 'right', // right | sheet | floating
			'notify_author'     => false,
			'claude_export'     => false,
			'claude_bot_user'   => 0,
			'auto_resolve'      => true,
			// AI-edit engine: blank = reuse the theme's Ask AI OpenRouter
			// key/model (vance_askai_api_key / vance_askai_model).
			'openrouter_key'    => '',
			'ai_edit_model'     => '',
			// allow_role_{role} flags extend vhh_annotate beyond Admin/Editor.
		);
	}

	/** @var array|null Memoized parsed options for this request. */
	private static $options_cache = null;

	public static function options() {
		// Memoized: get()/gate() run many times per request (the_content,
		// body_class, enqueue) — rebuild once, invalidate on option save.
		if ( null !== self::$options_cache ) {
			return self::$options_cache;
		}

		$saved   = get_option( self::OPTION, array() );
		$saved   = is_array( $saved ) ? $saved : array();
		$options = wp_parse_args( $saved, self::defaults() );

		// Derived post-type list from pt_* flags. Only fall back to array(
		// 'post' ) when NO pt_* flag was ever saved — an admin explicitly
		// unchecking every type means "nothing is annotatable", not "post".
		$any_saved_pt = false;
		foreach ( array_keys( $saved ) as $key ) {
			if ( 0 === strpos( $key, 'pt_' ) ) {
				$any_saved_pt = true;
				break;
			}
		}
		$post_types = array();
		foreach ( $options as $key => $value ) {
			if ( 0 === strpos( $key, 'pt_' ) && $value ) {
				$post_types[] = substr( $key, 3 );
			}
		}
		$options['post_types'] = ( $post_types || $any_saved_pt ) ? $post_types : array( 'post' );

		self::$options_cache = $options;
		return $options;
	}

	/** Drop the memoized options (after a settings save). */
	public static function flush_options_cache() {
		self::$options_cache = null;
	}

	/** @return mixed */
	public static function get( $key ) {
		$options = self::options();
		$value   = $options[ $key ] ?? null;
		return apply_filters( 'vhh_annotation_setting', $value, $key );
	}

	public static function enabled() {
		return (bool) self::get( 'enabled' );
	}

	public static function post_type_allowed( $post_type ) {
		return in_array( $post_type, (array) self::get( 'post_types' ), true );
	}

	public static function user_can_annotate() {
		return is_user_logged_in() && current_user_can( VHH_Capabilities::CAP_ANNOTATE );
	}

	public static function user_can_moderate() {
		return is_user_logged_in() && current_user_can( VHH_Capabilities::CAP_MODERATE );
	}

	/* ---------------------- activation lifecycle ---------------------- */

	public static function activate() {
		VHH_Capabilities::add_caps();
		add_option( self::OPTION, self::defaults() ); // no-op if it already exists
		// Re-grant any extra roles saved before a deactivate/reactivate cycle
		// (deactivate strips vhh_annotate from every role).
		VHH_Capabilities::sync_annotate_roles( self::extra_annotate_roles( self::options() ) );
		VHH_Todos_CPT::register(); // ensure CPT exists before flush
		flush_rewrite_rules();
	}

	public static function deactivate() {
		VHH_Capabilities::remove_caps();
		flush_rewrite_rules();
	}
}
