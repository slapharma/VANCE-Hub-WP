<?php
/**
 * REST routes: /wp-json/vhh/v1/annotations, /annotations/{id}, /export.
 *
 * Auth: cookie + X-WP-Nonce for the front-end; Application Passwords for the
 * Claude bot (handled by core — permissions below only check capabilities).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_REST_Annotations {

	const NS = 'vhh/v1';

	public static function register_routes() {
		register_rest_route(
			self::NS,
			'/annotations',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'list_annotations' ),
					'permission_callback' => array( __CLASS__, 'can_annotate' ),
					'args'                => array(
						'post'   => array(
							'type'              => 'integer',
							'default'           => 0,
							'sanitize_callback' => 'absint',
							'description'       => 'Post ID, or 0 to list recent annotations across every post.',
						),
						'status' => array(
							'type'    => 'string',
							'enum'    => array( 'open', 'resolved', 'all' ),
							'default' => 'all',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'create_annotation' ),
					'permission_callback' => array( __CLASS__, 'can_annotate' ),
					'args'                => array(
						'post'        => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'comment'     => array(
							'type'     => 'string',
							'required' => true,
						),
						'target_type' => array(
							'type'    => 'string',
							'enum'    => array( 'text', 'image', 'insertion' ),
							'default' => 'text',
						),
						'selector'    => array(
							// Object or JSON string; strictly validated in the handler.
							'required' => false,
						),
						'overall'     => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'parent'      => array(
							'type'              => 'integer',
							'default'           => 0,
							'sanitize_callback' => 'absint',
							'description'       => 'Parent annotation ID when posting a reply.',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/annotations/(?P<id>\d+)',
			array(
				'methods'             => 'PATCH',
				'callback'            => array( __CLASS__, 'patch_annotation' ),
				'permission_callback' => array( __CLASS__, 'can_patch' ),
				'args'                => array(
					'id'     => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'action' => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'resolve', 'unresolve', 'delete' ),
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/export',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'export' ),
				'permission_callback' => array( __CLASS__, 'can_export' ),
				'args'                => array(
					'post'   => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'since'  => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
						'description'       => 'Cursor: last comment_ID already consumed.',
					),
					'status' => array(
						'type'    => 'string',
						'enum'    => array( 'open', 'all' ),
						'default' => 'open',
					),
					'format' => array(
						'type'    => 'string',
						'enum'    => array( 'json', 'merged' ),
						'default' => 'json',
					),
				),
			)
		);
	}

	/* ------------------------- permissions ---------------------------- */

	public static function feature_enabled() {
		if ( ! VHH_Plugin::enabled() ) {
			return new WP_Error( 'vhh_disabled', 'Annotations are disabled.', array( 'status' => 403 ) );
		}
		return true;
	}

	public static function can_annotate( WP_REST_Request $request ) {
		$enabled = self::feature_enabled();
		if ( is_wp_error( $enabled ) ) {
			return $enabled;
		}
		// Any logged-in user can view, comment, and reply — it's a collaborative
		// review surface. Moderation (resolve/delete/export) still needs the
		// vhh_moderate_annotations capability (see can_patch / can_export).
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'vhh_unauthorized', 'Login required.', array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	public static function can_patch( WP_REST_Request $request ) {
		$base = self::can_annotate( $request );
		if ( is_wp_error( $base ) ) {
			return $base;
		}
		$annotation = VHH_Annotation_Store::get( $request['id'] );
		if ( ! $annotation ) {
			return new WP_Error( 'vhh_not_found', 'Annotation not found.', array( 'status' => 404 ) );
		}
		$is_owner = (int) $annotation->user_id === get_current_user_id() && (int) $annotation->user_id > 0;
		if ( ! $is_owner && ! current_user_can( VHH_Capabilities::CAP_MODERATE ) ) {
			return new WP_Error( 'vhh_forbidden', 'Only the note owner or a moderator can do that.', array( 'status' => 403 ) );
		}
		return true;
	}

	public static function can_export( WP_REST_Request $request ) {
		$enabled = self::feature_enabled();
		if ( is_wp_error( $enabled ) ) {
			return $enabled;
		}
		if ( ! VHH_Plugin::get( 'claude_export' ) ) {
			return new WP_Error( 'vhh_disabled', 'Export endpoint is disabled.', array( 'status' => 403 ) );
		}
		if ( ! current_user_can( VHH_Capabilities::CAP_MODERATE ) ) {
			return new WP_Error( 'vhh_forbidden', 'Moderator capability required.', array( 'status' => 403 ) );
		}
		return true;
	}

	/* --------------------------- handlers ----------------------------- */

	public static function list_annotations( WP_REST_Request $request ) {
		$post_id = (int) $request['post'];
		if ( $post_id ) {
			if ( ! get_post( $post_id ) ) {
				return new WP_Error( 'vhh_bad_post', 'Post not found.', array( 'status' => 404 ) );
			}
			$comments = VHH_Annotation_Store::get_for_post( $post_id, $request['status'] );
		} else {
			// post=0 → site-wide recent list, backing the sidebar's "Other pages" section.
			$comments = VHH_Annotation_Store::get_recent( $request['status'] );
		}
		return rest_ensure_response(
			array(
				'annotations' => array_map( array( 'VHH_Annotation_Store', 'to_api' ), $comments ),
			)
		);
	}

	public static function create_annotation( WP_REST_Request $request ) {
		$limited = VHH_Rate_Limiter::check( 'write', get_current_user_id(), 30, 60 );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}
		return self::create_from_request(
			$request,
			array(
				'post_id' => (int) $request['post'],
				'user_id' => get_current_user_id(),
				'agent'   => 'viaApp',
			)
		);
	}

	/**
	 * Shared creation path for both channels (logged-in and email-review
	 * token) so validation rules can never diverge between them.
	 *
	 * @param WP_REST_Request $request  Carries comment/target_type/selector/overall.
	 * @param array           $identity post_id, user_id, author_name?, agent.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_from_request( WP_REST_Request $request, array $identity ) {
		$post_id = (int) $identity['post_id'];
		$post    = get_post( $post_id );
		if ( ! $post || ! VHH_Plugin::post_type_allowed( $post->post_type ) ) {
			return new WP_Error( 'vhh_bad_post', 'This post cannot be annotated.', array( 'status' => 400 ) );
		}

		$parent      = absint( $request->get_param( 'parent' ) );
		$overall     = (bool) $request['overall'];
		$target_type = $request['target_type'];
		$selector    = null;

		// Replies (parent > 0) carry no selector — just body text.
		if ( ! $parent && ! $overall ) {
			if ( 'image' === $target_type && ! VHH_Plugin::get( 'image_annotation' ) ) {
				return new WP_Error( 'vhh_disabled', 'Image annotation is disabled.', array( 'status' => 403 ) );
			}
			if ( 'insertion' === $target_type && ! VHH_Plugin::get( 'insertion_annotation' ) ) {
				return new WP_Error( 'vhh_disabled', 'Insertion-point annotation is disabled.', array( 'status' => 403 ) );
			}
			$selector = VHH_Selector::validate( $request->get_param( 'selector' ), $target_type );
			if ( is_wp_error( $selector ) ) {
				return $selector;
			}
		}

		$id = VHH_Annotation_Store::create(
			array(
				'post_id'      => $post_id,
				'parent'       => $parent,
				'user_id'      => (int) ( $identity['user_id'] ?? 0 ),
				'author_name'  => (string) ( $identity['author_name'] ?? '' ),
				'author_email' => (string) ( $identity['author_email'] ?? '' ),
				'body'         => (string) $request->get_param( 'comment' ),
				'target_type' => $target_type,
				'selector'    => $selector,
				'overall'     => $overall,
				'agent'       => (string) ( $identity['agent'] ?? 'viaApp' ),
			)
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		do_action( 'vhh_annotation_created', $id, $post_id );

		$response = rest_ensure_response( VHH_Annotation_Store::to_api( get_comment( $id ) ) );
		$response->set_status( 201 );
		return $response;
	}

	public static function patch_annotation( WP_REST_Request $request ) {
		$id      = $request['id'];
		$user_id = get_current_user_id();

		switch ( $request['action'] ) {
			case 'resolve':
				$result = VHH_Annotation_Store::resolve( $id, $user_id );
				break;
			case 'unresolve':
				$result = VHH_Annotation_Store::unresolve( $id );
				break;
			case 'delete':
				$result = VHH_Annotation_Store::soft_delete( $id, $user_id );
				break;
			default:
				$result = new WP_Error( 'vhh_bad_action', 'Unknown action.', array( 'status' => 400 ) );
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response( VHH_Annotation_Store::to_api( get_comment( $id ) ) );
	}

	public static function export( WP_REST_Request $request ) {
		$result = VHH_Annotation_Store::export(
			$request['post'] ?: null,
			$request['since'],
			$request['status'],
			200
		);

		// Group by post and add post context for the Claude workflow.
		$posts = array();
		foreach ( $result['items'] as $item ) {
			$pid = $item['post'];
			if ( ! isset( $posts[ $pid ] ) ) {
				$post          = get_post( $pid );
				$posts[ $pid ] = array(
					'post'        => array(
						'id'        => $pid,
						'title'     => $post ? $post->post_title : '',
						'permalink' => get_permalink( $pid ),
						'edit_link' => add_query_arg( array( 'post' => $pid, 'action' => 'edit' ), admin_url( 'post.php' ) ),
					),
					'annotations' => array(),
				);
			}
			$posts[ $pid ]['annotations'][] = $item;
		}

		if ( 'merged' === $request['format'] ) {
			// Legacy denormalized blob per post: inline notes as
			// > "quote"\n↳ comment, then overall feedback lines.
			foreach ( $posts as &$entry ) {
				$blob = '';
				foreach ( $entry['annotations'] as $a ) {
					if ( ! empty( $a['overall'] ) ) {
						continue;
					}
					$quote = isset( $a['selector']['exact'] ) ? $a['selector']['exact'] : '[image note]';
					$blob .= '> "' . $quote . '"' . "\n" . '↳ ' . $a['comment'] . "\n";
					// Replies routinely refine or correct the original note —
					// fold them into the blob or the Claude workflow never sees them.
					foreach ( (array) $a['replies'] as $r ) {
						$blob .= '  ↳ ' . $r['author']['name'] . ': ' . $r['comment'] . "\n";
					}
					$blob .= "\n";
				}
				foreach ( $entry['annotations'] as $a ) {
					if ( ! empty( $a['overall'] ) ) {
						$blob .= $a['comment'] . "\n";
						foreach ( (array) $a['replies'] as $r ) {
							$blob .= '  ↳ ' . $r['author']['name'] . ': ' . $r['comment'] . "\n";
						}
					}
				}
				$entry['merged'] = rtrim( $blob ) . "\n";
			}
			unset( $entry );
		}

		return rest_ensure_response(
			array(
				'posts'       => array_values( $posts ),
				'next_cursor' => $result['next_cursor'],
				'exported_at' => gmdate( 'c' ),
			)
		);
	}
}
