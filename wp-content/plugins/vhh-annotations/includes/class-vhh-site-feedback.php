<?php
/**
 * A single, hidden, internal post that non-post listing views (currently
 * just the homepage) attach general feedback to.
 *
 * The whole vhh_annotation system is built on comment_post_ID referencing a
 * real, published wp_posts row (VHH_Annotation_Store::create() requires it,
 * VHH_Plugin::post_type_allowed() gates on the post's type). Rather than a
 * parallel storage mechanism for "feedback not about any specific post,"
 * this gives such views a dedicated bucket post so every existing code path
 * — isolation, replies, resolve/delete, to-do generation, the AI-edit
 * prompt, the sidebar's cross-page list — keeps working unmodified.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_Site_Feedback {

	const POST_TYPE  = 'vhh_site_feedback';
	const OPTION_KEY = 'vhh_site_feedback_post_id';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	}

	/** Deliberately not public/queryable/searchable — this is plugin bookkeeping, not content. */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'               => 'VHH Site Feedback (internal)',
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'capability_type'     => 'post',
				'supports'            => array( 'title' ),
			)
		);
	}

	/**
	 * The single "Homepage" bucket post, created lazily on first use and
	 * cached in an option — id 0 only if wp_insert_post() itself fails.
	 *
	 * @return int
	 */
	public static function homepage_post_id() {
		$id = (int) get_option( self::OPTION_KEY );
		if ( $id && get_post( $id ) ) {
			return $id;
		}

		$id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Homepage',
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return 0;
		}

		update_option( self::OPTION_KEY, (int) $id );
		return (int) $id;
	}
}
