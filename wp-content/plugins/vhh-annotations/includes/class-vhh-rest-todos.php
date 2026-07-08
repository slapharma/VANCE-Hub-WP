<?php
/**
 * REST routes: /wp-json/vhh/v1/todos — the Claude approval loop.
 *
 * Claude (Application Password, moderate capability) POSTs proposed todos as
 * vhh-pending; a human approves in wp-admin; Claude pulls status=approved,
 * works them, then PATCHes action=done.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_REST_Todos {

	const NS = 'vhh/v1';

	public static function register_routes() {
		register_rest_route(
			self::NS,
			'/todos',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'list_todos' ),
					'permission_callback' => array( __CLASS__, 'can_manage' ),
					'args'                => array(
						'status' => array(
							'type'    => 'string',
							'enum'    => array( 'pending', 'approved', 'rejected', 'done', 'all' ),
							'default' => 'pending',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'create_todo' ),
					'permission_callback' => array( __CLASS__, 'can_manage' ),
					'args'                => array(
						'title'              => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'detail'             => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
						'target_post'        => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'source_annotations' => array(
							'type'    => 'array',
							'default' => array(),
							'items'   => array( 'type' => 'integer' ),
						),
						'claude_session'     => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'proposed_diff'      => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/todos/(?P<id>\d+)',
			array(
				'methods'             => 'PATCH',
				'callback'            => array( __CLASS__, 'patch_todo' ),
				'permission_callback' => array( __CLASS__, 'can_manage' ),
				'args'                => array(
					'id'     => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'action' => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'approve', 'reject', 'done' ),
					),
				),
			)
		);
	}

	public static function can_manage( WP_REST_Request $request ) {
		if ( ! VHH_Plugin::enabled() || ! VHH_Plugin::get( 'claude_export' ) ) {
			return new WP_Error( 'vhh_disabled', 'Claude integration is disabled.', array( 'status' => 403 ) );
		}
		if ( ! current_user_can( VHH_Capabilities::CAP_MODERATE ) ) {
			return new WP_Error( 'vhh_forbidden', 'Moderator capability required.', array( 'status' => 403 ) );
		}
		return true;
	}

	public static function list_todos( WP_REST_Request $request ) {
		$status = $request['status'];
		$query  = new WP_Query(
			array(
				'post_type'      => VHH_Todos_CPT::CPT,
				'post_status'    => 'all' === $status ? array_keys( VHH_Todos_CPT::STATUSES ) : 'vhh-' . $status,
				'posts_per_page' => 100,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);
		return rest_ensure_response(
			array(
				'todos' => array_map( array( 'VHH_Todos_CPT', 'to_api' ), $query->posts ),
			)
		);
	}

	public static function create_todo( WP_REST_Request $request ) {
		$limited = VHH_Rate_Limiter::check( 'todos', get_current_user_id(), 30, 60 );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		$target = get_post( $request['target_post'] );
		if ( ! $target ) {
			return new WP_Error( 'vhh_bad_post', 'Target post not found.', array( 'status' => 400 ) );
		}

		// Only accept source annotations that really are our type.
		$sources = array();
		foreach ( (array) $request['source_annotations'] as $annotation_id ) {
			if ( VHH_Annotation_Store::get( $annotation_id ) ) {
				$sources[] = (int) $annotation_id;
			}
		}

		// wp_insert_post()/update_metadata() expect slashed input — without
		// wp_slash(), backslashes in a proposed diff (regexes, escape
		// sequences) are stripped before the human ever reviews it.
		$todo_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => VHH_Todos_CPT::CPT,
					'post_status'  => 'vhh-pending',
					'post_title'   => $request['title'],
					'post_content' => $request['detail'],
				)
			),
			true
		);
		if ( is_wp_error( $todo_id ) ) {
			return $todo_id;
		}

		update_post_meta( $todo_id, '_vhh_target_post', absint( $request['target_post'] ) );
		update_post_meta( $todo_id, '_vhh_source_annotations', $sources );
		update_post_meta( $todo_id, '_vhh_claude_session', wp_slash( $request['claude_session'] ) );
		if ( $request['proposed_diff'] ) {
			update_post_meta( $todo_id, '_vhh_proposed_diff', wp_slash( $request['proposed_diff'] ) );
		}

		// Back-link absorbed annotations so the sidebar shows "in a proposed task".
		foreach ( $sources as $annotation_id ) {
			update_comment_meta( $annotation_id, '_vhh_claude_task_id', $todo_id );
		}

		$response = rest_ensure_response( VHH_Todos_CPT::to_api( get_post( $todo_id ) ) );
		$response->set_status( 201 );
		return $response;
	}

	public static function patch_todo( WP_REST_Request $request ) {
		$map = array(
			'approve' => 'vhh-approved',
			'reject'  => 'vhh-rejected',
			'done'    => 'vhh-done',
		);
		$result = VHH_Todos_CPT::transition( $request['id'], $map[ $request['action'] ] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response( VHH_Todos_CPT::to_api( get_post( $request['id'] ) ) );
	}
}
