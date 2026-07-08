<?php
/**
 * Storage layer for VHH Annotations.
 *
 * Every annotation is a wp_comments row with comment_type = 'vhh_annotation'
 * plus structured comment meta. ALL reads/writes go through this class.
 *
 * comment_approved doubles as the annotation status:
 *   '1'            open
 *   'vhh-resolved' resolved
 *   'trash'        soft-deleted (kept for audit)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_Annotation_Store {

	const TYPE            = 'vhh_annotation';
	const STATUS_OPEN     = '1';
	const STATUS_RESOLVED = 'vhh-resolved';
	const STATUS_TRASH    = 'trash';

	const MAX_BODY = 2000;

	/**
	 * Create an annotation.
	 *
	 * @param array $args {
	 *   @type int    $post_id     Target post (required, published).
	 *   @type int    $user_id     Author user ID (0 for email reviewers).
	 *   @type string $author_name Display name when user_id is 0.
	 *   @type string $body        Comment text (1..2000 chars).
	 *   @type string $target_type 'text' | 'image' | '' (overall).
	 *   @type array  $selector    Pre-validated selector array or null.
	 *   @type bool   $overall     Overall-feedback entry (no selector).
	 *   @type string $agent       Channel: 'viaApp' | 'email-review'.
	 * }
	 * @return int|WP_Error Comment ID.
	 */
	public static function create( array $args ) {
		$post_id = absint( $args['post_id'] ?? 0 );
		$post    = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return new WP_Error( 'vhh_bad_post', 'Annotations require a published post.', array( 'status' => 400 ) );
		}

		$body = sanitize_textarea_field( (string) ( $args['body'] ?? '' ) );
		if ( '' === trim( $body ) || mb_strlen( $body ) > self::MAX_BODY ) {
			return new WP_Error( 'vhh_bad_body', 'Comment must be 1-2000 characters.', array( 'status' => 400 ) );
		}

		$parent = absint( $args['parent'] ?? 0 );
		$reply  = $parent > 0;

		// A reply hangs off a parent annotation: no selector, no overall flag.
		$overall     = ! $reply && ! empty( $args['overall'] );
		$target_type = ( $reply || $overall ) ? '' : (string) ( $args['target_type'] ?? 'text' );
		$selector    = ( $reply || $overall ) ? null : ( $args['selector'] ?? null );

		if ( $reply ) {
			$parent_c = self::get( $parent );
			if ( ! $parent_c ) {
				return new WP_Error( 'vhh_bad_parent', 'Parent annotation not found.', array( 'status' => 400 ) );
			}
		} elseif ( ! $overall && ( ! is_array( $selector ) || empty( $selector['type'] ) ) ) {
			return new WP_Error( 'vhh_bad_selector', 'Inline annotations require a selector.', array( 'status' => 400 ) );
		}

		$user_id = absint( $args['user_id'] ?? 0 );
		$user    = $user_id ? get_userdata( $user_id ) : null;

		// wp_insert_comment()/update_metadata() wp_unslash() their input, so
		// everything must go in slashed or backslashes/escapes are stripped —
		// which would corrupt the selector JSON (quotes, \uXXXX escapes) and
		// silently orphan the highlight.
		$comment_id = wp_insert_comment(
			wp_slash(
				array(
					'comment_post_ID'      => $post_id,
					'comment_parent'       => $parent,
					'user_id'              => $user_id,
					'comment_author'       => $user ? $user->display_name : sanitize_text_field( (string) ( $args['author_name'] ?? '' ) ),
					'comment_author_email' => $user ? $user->user_email : sanitize_email( (string) ( $args['author_email'] ?? '' ) ),
					'comment_content'      => $body,
					'comment_type'         => self::TYPE,
					'comment_approved'     => self::STATUS_OPEN,
					'comment_agent'        => sanitize_text_field( (string) ( $args['agent'] ?? 'viaApp' ) ),
				)
			)
		);

		if ( ! $comment_id ) {
			return new WP_Error( 'vhh_insert_failed', 'Could not save annotation.', array( 'status' => 500 ) );
		}

		update_comment_meta( $comment_id, '_vhh_target_type', $target_type );
		update_comment_meta( $comment_id, '_vhh_overall', $overall ? '1' : '' );
		update_comment_meta( $comment_id, '_vhh_content_hash', md5( $post->post_content ) );
		if ( is_array( $selector ) ) {
			update_comment_meta( $comment_id, '_vhh_selector', wp_slash( wp_json_encode( $selector ) ) );
		}

		return (int) $comment_id;
	}

	/**
	 * Fetch annotations for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $status  'open' | 'resolved' | 'all' (all = open + resolved, never trash).
	 * @return WP_Comment[]
	 */
	public static function get_for_post( $post_id, $status = 'open' ) {
		$statuses = array(
			'open'     => array( 'approve' ),                 // get_comments maps 'approve' => '1'
			'resolved' => array( self::STATUS_RESOLVED ),
			'all'      => array( 'approve', self::STATUS_RESOLVED ),
		);
		$want     = $statuses[ $status ] ?? $statuses['open'];

		// WP_Comment_Query accepts an array of statuses — one query, pre-sorted.
		// parent => 0: only top-level annotations here; replies attach via to_api().
		return get_comments(
			array(
				'post_id' => absint( $post_id ),
				'type'    => self::TYPE,
				'status'  => $want,
				'parent'  => 0,
				'orderby' => 'comment_ID',
				'order'   => 'ASC',
			)
		);
	}

	/**
	 * Fetch recent annotations across ALL posts (site-wide), most recent
	 * first — backs the sidebar's "Other pages" section.
	 *
	 * @param string $status 'open' | 'resolved' | 'all'.
	 * @param int    $limit  Max rows.
	 * @return WP_Comment[]
	 */
	public static function get_recent( $status = 'all', $limit = 100 ) {
		$statuses = array(
			'open'     => array( 'approve' ),
			'resolved' => array( self::STATUS_RESOLVED ),
			'all'      => array( 'approve', self::STATUS_RESOLVED ),
		);
		$want     = $statuses[ $status ] ?? $statuses['all'];

		return get_comments(
			array(
				'type'    => self::TYPE,
				'status'  => $want,
				'parent'  => 0,
				'orderby' => 'comment_ID',
				'order'   => 'DESC',
				'number'  => absint( $limit ),
			)
		);
	}

	/** Get one annotation (of our type) or null. */
	public static function get( $id ) {
		$comment = get_comment( absint( $id ) );
		if ( ! $comment || self::TYPE !== $comment->comment_type ) {
			return null;
		}
		return $comment;
	}

	public static function resolve( $id, $user_id ) {
		return self::set_status(
			$id,
			self::STATUS_RESOLVED,
			array(
				'_vhh_resolved_by' => absint( $user_id ),
				'_vhh_resolved_at' => gmdate( 'c' ),
			)
		);
	}

	public static function unresolve( $id ) {
		$ok = self::set_status( $id, self::STATUS_OPEN, array() );
		if ( true === $ok ) {
			delete_comment_meta( $id, '_vhh_resolved_by' );
			delete_comment_meta( $id, '_vhh_resolved_at' );
		}
		return $ok;
	}

	public static function soft_delete( $id, $user_id ) {
		return self::set_status(
			$id,
			self::STATUS_TRASH,
			array(
				'_vhh_deleted_by' => absint( $user_id ),
				'_vhh_deleted_at' => gmdate( 'c' ),
			)
		);
	}

	private static function set_status( $id, $status, array $meta ) {
		$comment = self::get( $id );
		if ( ! $comment ) {
			return new WP_Error( 'vhh_not_found', 'Annotation not found.', array( 'status' => 404 ) );
		}
		$ok = wp_update_comment(
			array(
				'comment_ID'       => $comment->comment_ID,
				'comment_approved' => $status,
			)
		);
		if ( is_wp_error( $ok ) || false === $ok ) {
			return new WP_Error( 'vhh_update_failed', 'Could not update annotation.', array( 'status' => 500 ) );
		}
		foreach ( $meta as $key => $value ) {
			update_comment_meta( $comment->comment_ID, $key, $value );
		}
		return true;
	}

	/**
	 * API/response shape shared by REST and the Claude export.
	 */
	public static function to_api( WP_Comment $c ) {
		$selector_json = get_comment_meta( $c->comment_ID, '_vhh_selector', true );
		$selector      = $selector_json ? json_decode( $selector_json, true ) : null;
		$stored_hash   = (string) get_comment_meta( $c->comment_ID, '_vhh_content_hash', true );
		$current_hash  = self::post_content_hash( (int) $c->comment_post_ID );
		$post_brief    = self::post_brief( (int) $c->comment_post_ID );

		$status = 'open';
		if ( self::STATUS_RESOLVED === $c->comment_approved ) {
			$status = 'resolved';
		} elseif ( self::STATUS_TRASH === $c->comment_approved ) {
			$status = 'deleted';
		}

		return array(
			'id'             => (int) $c->comment_ID,
			'post'           => (int) $c->comment_post_ID,
			// Lets the sidebar link to an annotation's article from any other page.
			'post_title'     => $post_brief['title'],
			'post_permalink' => $post_brief['permalink'],
			'author'       => array(
				'id'     => (int) $c->user_id,
				'name'   => $c->comment_author,
				'avatar' => get_avatar_url( $c, array( 'size' => 48 ) ),
			),
			'category'     => $post_brief['category'],
			'target_type'  => (string) get_comment_meta( $c->comment_ID, '_vhh_target_type', true ),
			'selector'     => $selector,
			'overall'      => (bool) get_comment_meta( $c->comment_ID, '_vhh_overall', true ),
			'comment'      => $c->comment_content,
			'status'       => $status,
			'stale'        => ( $stored_hash && $current_hash && $stored_hash !== $current_hash ),
			'channel'      => $c->comment_agent,
			// comment_date_gmt is UTC — format it as UTC (mysql2date would
			// re-interpret it in the site timezone and shift the timestamp).
			'created'      => gmdate( 'c', strtotime( $c->comment_date_gmt . ' UTC' ) ),
			'resolved_by'  => (int) get_comment_meta( $c->comment_ID, '_vhh_resolved_by', true ),
			'resolved_at'  => (string) get_comment_meta( $c->comment_ID, '_vhh_resolved_at', true ),
			'claude_task'  => (int) get_comment_meta( $c->comment_ID, '_vhh_claude_task_id', true ),
			'replies'      => self::get_replies( (int) $c->comment_ID ),
		);
	}

	/** Threaded replies (flat list, oldest first) for one annotation. */
	public static function get_replies( $parent_id ) {
		$children = get_comments(
			array(
				'parent'  => absint( $parent_id ),
				'type'    => self::TYPE,
				'status'  => 'approve',
				'orderby' => 'comment_ID',
				'order'   => 'ASC',
			)
		);
		$out = array();
		foreach ( $children as $r ) {
			$out[] = array(
				'id'      => (int) $r->comment_ID,
				'author'  => array(
					'id'     => (int) $r->user_id,
					'name'   => $r->comment_author,
					'avatar' => get_avatar_url( $r, array( 'size' => 40 ) ),
				),
				'comment' => $r->comment_content,
				'created' => gmdate( 'c', strtotime( $r->comment_date_gmt . ' UTC' ) ),
			);
		}
		return $out;
	}

	/**
	 * Incremental export for the Claude workflow.
	 *
	 * @param int|null $post_id Restrict to one post, or null for all.
	 * @param int      $since_id Only annotations with comment_ID > cursor.
	 * @param string   $status  'open' | 'all'.
	 * @param int      $limit   Batch size.
	 * @return array { items: array[], next_cursor: int }
	 */
	public static function export( $post_id, $since_id, $status = 'open', $limit = 200 ) {
		global $wpdb;

		$where = $wpdb->prepare( 'comment_type = %s AND comment_parent = 0 AND comment_ID > %d', self::TYPE, absint( $since_id ) );
		if ( $post_id ) {
			$where .= $wpdb->prepare( ' AND comment_post_ID = %d', absint( $post_id ) );
		}
		if ( 'open' === $status ) {
			$where .= $wpdb->prepare( ' AND comment_approved = %s', self::STATUS_OPEN );
		} else {
			$where .= $wpdb->prepare( ' AND comment_approved IN (%s, %s)', self::STATUS_OPEN, self::STATUS_RESOLVED );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where built via prepare above.
		$ids = $wpdb->get_col( "SELECT comment_ID FROM {$wpdb->comments} WHERE {$where} ORDER BY comment_ID ASC LIMIT " . absint( $limit ) );

		$items = array();
		if ( $ids ) {
			// One batched fetch with primed meta cache instead of per-ID queries.
			$comments = get_comments(
				array(
					'comment__in'               => array_map( 'intval', $ids ),
					'type'                      => self::TYPE,
					'status'                    => 'all',
					'orderby'                   => 'comment_ID',
					'order'                     => 'ASC',
					'update_comment_meta_cache' => true,
				)
			);
			$items    = array_map( array( __CLASS__, 'to_api' ), $comments );
		}
		return array(
			'items'       => $items,
			'next_cursor' => $ids ? (int) end( $ids ) : (int) $since_id,
		);
	}

	/** md5 of post_content, memoized per request (to_api runs per annotation). */
	private static function post_content_hash( $post_id ) {
		static $cache = array();
		if ( ! isset( $cache[ $post_id ] ) ) {
			$post               = get_post( $post_id );
			$cache[ $post_id ] = $post ? md5( $post->post_content ) : '';
		}
		return $cache[ $post_id ];
	}

	/** Title + permalink + primary category, memoized per request. */
	private static function post_brief( $post_id ) {
		static $cache = array();
		if ( ! isset( $cache[ $post_id ] ) ) {
			$cats     = get_the_category( $post_id );
			$category = '';
			foreach ( (array) $cats as $cat ) {
				if ( 'uncategorized' !== $cat->slug ) {
					$category = $cat->name;
					break;
				}
			}
			if ( '' === $category && ! empty( $cats ) ) {
				$category = $cats[0]->name;
			}
			// Term names can carry HTML entities (e.g. "Food &amp; Nutrition").
			// The sidebar renders via textContent, so send decoded plain text.
			$category = wp_specialchars_decode( $category, ENT_QUOTES );
			$cache[ $post_id ] = array(
				'title'     => get_the_title( $post_id ),
				'permalink' => (string) get_permalink( $post_id ),
				'category'  => $category,
			);
		}
		return $cache[ $post_id ];
	}

	/* ---------------------------------------------------------------------
	 * Isolation filters — keep annotations out of every native comment
	 * surface (front-end lists, counts, feeds). Registered unconditionally
	 * from VHH_Plugin so the data never leaks even when the feature is off.
	 * ------------------------------------------------------------------- */

	public static function register_isolation_filters() {
		add_action( 'pre_get_comments', array( __CLASS__, 'exclude_from_comment_queries' ) );
		add_filter( 'comment_feed_where', array( __CLASS__, 'exclude_from_feeds' ) );
		add_filter( 'pre_wp_update_comment_count_now', array( __CLASS__, 'filter_comment_count' ), 10, 3 );
	}

	/** Hide our type from any WP_Comment_Query that doesn't explicitly ask for it. */
	public static function exclude_from_comment_queries( $query ) {
		$vars = $query->query_vars;
		if ( ! empty( $vars['type'] ) || ! empty( $vars['type__in'] ) ) {
			return; // explicit type request (possibly ours) — leave alone
		}
		$not_in   = (array) ( $vars['type__not_in'] ?? array() );
		$not_in[] = self::TYPE;
		$query->query_vars['type__not_in'] = array_unique( $not_in );
	}

	public static function exclude_from_feeds( $where ) {
		global $wpdb;
		return $where . $wpdb->prepare( ' AND comment_type != %s', self::TYPE );
	}

	/** Keep wp_posts.comment_count free of annotations. */
	public static function filter_comment_count( $new, $old, $post_id ) {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_post_ID = %d AND comment_approved = '1' AND comment_type != %s",
				$post_id,
				self::TYPE
			)
		);
	}
}
