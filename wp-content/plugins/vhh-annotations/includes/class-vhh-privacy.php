<?php
/**
 * Personal-data exporter/eraser integration for annotations.
 *
 * Annotations can carry reviewer names and clinical remarks — they join
 * WP's privacy tooling (Tools → Export/Erase Personal Data) keyed by the
 * requester's email address.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_Privacy {

	public static function init() {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
	}

	public static function register_exporter( $exporters ) {
		$exporters['vhh-annotations'] = array(
			'exporter_friendly_name' => __( 'Article annotations', 'vhh-annotations' ),
			'callback'               => array( __CLASS__, 'export' ),
		);
		return $exporters;
	}

	public static function register_eraser( $erasers ) {
		$erasers['vhh-annotations'] = array(
			'eraser_friendly_name' => __( 'Article annotations', 'vhh-annotations' ),
			'callback'             => array( __CLASS__, 'erase' ),
		);
		return $erasers;
	}

	private static function comments_for_email( $email, $page ) {
		return get_comments(
			array(
				'type'         => VHH_Annotation_Store::TYPE,
				'author_email' => $email,
				// 'all' is WP_Comment_Query's no-status-filter value ('any'
				// would be matched literally against comment_approved).
				'status'       => 'all',
				'number'       => 50,
				'paged'        => max( 1, (int) $page ),
				'orderby'      => 'comment_ID',
				'order'        => 'ASC',
			)
		);
	}

	public static function export( $email, $page = 1 ) {
		$comments = self::comments_for_email( $email, $page );
		$items    = array();

		foreach ( $comments as $comment ) {
			$items[] = array(
				'group_id'    => 'vhh_annotations',
				'group_label' => __( 'Article annotations', 'vhh-annotations' ),
				'item_id'     => 'vhh-annotation-' . $comment->comment_ID,
				'data'        => array(
					array(
						'name'  => __( 'Article', 'vhh-annotations' ),
						'value' => get_the_title( $comment->comment_post_ID ),
					),
					array(
						'name'  => __( 'Comment', 'vhh-annotations' ),
						'value' => $comment->comment_content,
					),
					array(
						'name'  => __( 'Date', 'vhh-annotations' ),
						'value' => $comment->comment_date,
					),
				),
			);
		}

		return array(
			'data' => $items,
			'done' => count( $comments ) < 50,
		);
	}

	public static function erase( $email, $page = 1 ) {
		// Always query page 1: rows are hard-deleted as we go, so advancing
		// the page would skip every second batch of survivors.
		$comments = self::comments_for_email( $email, 1 );
		$removed  = false;

		foreach ( $comments as $comment ) {
			wp_delete_comment( $comment->comment_ID, true );
			$removed = true;
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => count( $comments ) < 50,
		);
	}
}
