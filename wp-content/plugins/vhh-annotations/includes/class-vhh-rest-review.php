<?php
/**
 * Email review flow — token minting/verification + token-authed REST routes.
 *
 * Token format: base64url(json_payload) . '.' . base64url(hmac_sha256)
 * Payload: { p: post_id, e: email, n: name, a: 'approve'|'changes', x: exp }
 * Keyed with wp_salt('auth'); constant-time compare; expiry enforced.
 *
 * GET routes NEVER mutate — Outlook/Gmail link scanners prefetch GET links.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_REST_Review {

	const NS = 'vhh/v1';

	/* ------------------------------ tokens ------------------------------ */

	private static function b64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	private static function b64url_decode( $data ) {
		return base64_decode( strtr( $data, '-_', '+/' ) );
	}

	public static function mint( $post_id, $email, $name, $action, $expiry_days ) {
		$payload = wp_json_encode(
			array(
				'p' => absint( $post_id ),
				'e' => sanitize_email( $email ),
				'n' => sanitize_text_field( $name ),
				'a' => ( 'approve' === $action ) ? 'approve' : 'changes',
				'x' => time() + ( absint( $expiry_days ) * DAY_IN_SECONDS ),
			)
		);
		$sig = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
		return self::b64url_encode( $payload ) . '.' . self::b64url_encode( $sig );
	}

	/**
	 * @return array|WP_Error Decoded payload on success.
	 */
	public static function verify( $token ) {
		$parts = explode( '.', (string) $token );
		if ( 2 !== count( $parts ) ) {
			return new WP_Error( 'vhh_bad_token', 'Invalid review link.', array( 'status' => 403 ) );
		}
		$payload_raw = self::b64url_decode( $parts[0] );
		$sig         = self::b64url_decode( $parts[1] );
		$expected    = hash_hmac( 'sha256', $payload_raw, wp_salt( 'auth' ) );
		if ( ! $payload_raw || ! is_string( $sig ) || ! hash_equals( $expected, (string) $sig ) ) {
			return new WP_Error( 'vhh_bad_token', 'Invalid review link.', array( 'status' => 403 ) );
		}
		$payload = json_decode( $payload_raw, true );
		if ( ! is_array( $payload ) || empty( $payload['p'] ) || empty( $payload['x'] ) ) {
			return new WP_Error( 'vhh_bad_token', 'Invalid review link.', array( 'status' => 403 ) );
		}
		if ( time() > (int) $payload['x'] ) {
			return new WP_Error( 'vhh_token_expired', 'This review link has expired.', array( 'status' => 403 ) );
		}
		if ( ! get_post( (int) $payload['p'] ) ) {
			return new WP_Error( 'vhh_bad_token', 'Invalid review link.', array( 'status' => 403 ) );
		}
		return $payload;
	}

	/* ------------------------------ routes ------------------------------ */

	public static function register_routes() {
		register_rest_route(
			self::NS,
			'/review/send',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'send' ),
				'permission_callback' => array( __CLASS__, 'can_send' ),
				'args'                => array(
					'post'      => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'reviewers' => array(
						'type'     => 'array',
						'required' => true,
					),
				),
			)
		);

		$token_arg = array(
			'token' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

		// Reviewer submission endpoint. POST only — GET pages are served by
		// template_include on the post permalink and never mutate.
		register_rest_route(
			self::NS,
			'/review/(?P<token>[A-Za-z0-9_\-.]+)/annotate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'annotate' ),
				'permission_callback' => '__return_true', // token verified in handler
				'args'                => $token_arg + array(
					'comment'     => array(
						'type'     => 'string',
						'required' => true,
					),
					'target_type' => array(
						'type'    => 'string',
						'enum'    => array( 'text', 'image', 'insertion' ),
						'default' => 'text',
					),
					'selector'    => array( 'required' => false ),
					'overall'     => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/review/(?P<token>[A-Za-z0-9_\-.]+)/annotations',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'list_for_reviewer' ),
				'permission_callback' => '__return_true',
				'args'                => $token_arg,
			)
		);

		register_rest_route(
			self::NS,
			'/review/(?P<token>[A-Za-z0-9_\-.]+)/approve',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'approve' ),
				'permission_callback' => '__return_true',
				'args'                => $token_arg,
			)
		);
	}

	public static function can_send( WP_REST_Request $request ) {
		if ( ! VHH_Plugin::enabled() || ! VHH_Plugin::get( 'email_review' ) ) {
			return new WP_Error( 'vhh_disabled', 'Email review is disabled.', array( 'status' => 403 ) );
		}
		if ( ! current_user_can( VHH_Capabilities::CAP_MODERATE ) ) {
			return new WP_Error( 'vhh_forbidden', 'Moderator capability required.', array( 'status' => 403 ) );
		}
		return true;
	}

	/** Common gate for token routes: feature on + valid token. */
	private static function token_gate( WP_REST_Request $request ) {
		if ( ! VHH_Plugin::enabled() || ! VHH_Plugin::get( 'email_review' ) ) {
			return new WP_Error( 'vhh_disabled', 'Email review is disabled.', array( 'status' => 403 ) );
		}
		return self::verify( $request['token'] );
	}

	/* ----------------------------- handlers ----------------------------- */

	public static function send( WP_REST_Request $request ) {
		$limited = VHH_Rate_Limiter::check( 'review_send', get_current_user_id(), 5, 60 );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}
		$post = get_post( $request['post'] );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return new WP_Error( 'vhh_bad_post', 'Reviews require a published post.', array( 'status' => 400 ) );
		}

		$sent = array();
		foreach ( (array) $request['reviewers'] as $reviewer ) {
			$email = sanitize_email( is_array( $reviewer ) ? ( $reviewer['email'] ?? '' ) : (string) $reviewer );
			$name  = sanitize_text_field( is_array( $reviewer ) ? ( $reviewer['name'] ?? '' ) : '' );
			if ( ! is_email( $email ) ) {
				continue;
			}
			$ok = VHH_Review_Emails::send_review_email( $post, $email, $name ?: $email );
			if ( $ok ) {
				$sent[] = $email;
			}
		}
		if ( ! $sent ) {
			return new WP_Error( 'vhh_send_failed', 'No review emails could be sent.', array( 'status' => 500 ) );
		}
		return rest_ensure_response( array( 'sent' => $sent ) );
	}

	public static function annotate( WP_REST_Request $request ) {
		$payload = self::token_gate( $request );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}
		$limited = VHH_Rate_Limiter::check( 'write', 'token_' . md5( $payload['e'] ), 30, 60 );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		// Same creation path as the logged-in channel — validation rules can't diverge.
		return VHH_REST_Annotations::create_from_request(
			$request,
			array(
				'post_id'      => (int) $payload['p'],
				'user_id'      => 0,
				'author_name'  => $payload['n'] ?: $payload['e'],
				'author_email' => (string) $payload['e'],
				'agent'        => 'email-review',
			)
		);
	}

	public static function list_for_reviewer( WP_REST_Request $request ) {
		$payload = self::token_gate( $request );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}
		// Confidentiality: a token proves an invite, not staff clearance.
		// External reviewers only ever see their OWN notes — internal
		// logged-in annotations (names, remarks) must not leak outward.
		$comments = array_filter(
			VHH_Annotation_Store::get_for_post( (int) $payload['p'], 'all' ),
			static function ( $c ) use ( $payload ) {
				return 'email-review' === $c->comment_agent
					&& 0 === (int) $c->user_id
					&& strtolower( $c->comment_author_email ) === strtolower( (string) $payload['e'] );
			}
		);
		return rest_ensure_response(
			array(
				'annotations' => array_map( array( 'VHH_Annotation_Store', 'to_api' ), array_values( $comments ) ),
			)
		);
	}

	public static function approve( WP_REST_Request $request ) {
		$payload = self::token_gate( $request );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}
		$limited = VHH_Rate_Limiter::check( 'write', 'token_' . md5( $payload['e'] ), 30, 60 );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}
		if ( 'approve' !== $payload['a'] ) {
			return new WP_Error( 'vhh_forbidden', 'This link cannot approve.', array( 'status' => 403 ) );
		}

		// Approvals are votes, not vetoes; one vote per reviewer email.
		$post_id   = (int) $payload['p'];
		$approvals = get_post_meta( $post_id, '_vhh_approvals', true );
		$approvals = is_array( $approvals ) ? $approvals : array();
		$approvals[ $payload['e'] ] = array(
			'name' => $payload['n'],
			'at'   => gmdate( 'c' ),
		);
		update_post_meta( $post_id, '_vhh_approvals', $approvals );

		return rest_ensure_response( array( 'approved' => true ) );
	}
}
