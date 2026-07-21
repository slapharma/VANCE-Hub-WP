<?php
/**
 * vhh_todo custom post type — Claude-proposed tasks held for human approval.
 *
 * Lifecycle: vhh-pending → vhh-approved | vhh-rejected → vhh-done.
 * Claude only ever acts on vhh-approved todos; approval happens here in
 * wp-admin (row actions on the list table).
 *
 * Meta:
 *   _vhh_source_annotations  array of annotation comment IDs
 *   _vhh_target_post         post the task is about
 *   _vhh_claude_session      run identifier from the extract skill
 *   _vhh_proposed_diff       optional proposed replacement text
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_Todos_CPT {

	const CPT = 'vhh_todo';

	const STATUSES = array(
		'vhh-pending'  => 'Pending approval',
		'vhh-approved' => 'Approved',
		'vhh-rejected' => 'Rejected',
		'vhh-done'     => 'Done',
	);

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_vhh_todo_transition', array( __CLASS__, 'handle_transition' ) );
		add_action( 'admin_post_vhh_generate_todos', array( __CLASS__, 'handle_generate_todos' ) );
		add_filter( 'display_post_states', array( __CLASS__, 'post_states' ), 10, 2 );
		add_action( 'pre_get_posts', array( __CLASS__, 'admin_default_status_filter' ) );
		// Single control surface is the "Review & apply" box (VHH_Apply): view
		// the to-do → generate the AI preview → confirm or reject, right there.
		// No separate approve step / approval meta box / row actions.
		// "Generate to-dos" action bar + result notice, rendered above the list.
		// (Deliberately NOT via restrict_manage_posts — that lives in the filter
		// bar and makes core render a superfluous "Filter" submit button.)
		add_action( 'admin_notices', array( __CLASS__, 'list_actions' ) );
		// This internal CPT has no use for the date-filter dropdown/button.
		add_filter( 'disable_months_dropdown', array( __CLASS__, 'disable_months' ), 10, 2 );
		// Guard: the editor's Publish/Save button must never knock a to-do out
		// of its workflow status — status only changes via the Approve/Reject
		// controls. Without this, hitting Publish sets post_status='publish',
		// which the workflow ignores and the list filter then hides.
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'guard_status' ), 10, 2 );
	}

	public static function disable_months( $disable, $post_type ) {
		return self::CPT === $post_type ? true : $disable;
	}

	/**
	 * Keep vhh_todo posts inside the workflow statuses. Editing title/body in
	 * the block editor and clicking Publish/Update saves the content but leaves
	 * the workflow status alone (new posts default to vhh-pending).
	 */
	public static function guard_status( $data, $postarr ) {
		if ( self::CPT !== ( $data['post_type'] ?? '' ) ) {
			return $data;
		}
		// Autosave/auto-draft: leave WP's internal handling be.
		if ( in_array( $data['post_status'], array( 'auto-draft', 'inherit', 'trash' ), true ) ) {
			return $data;
		}
		if ( isset( self::STATUSES[ $data['post_status'] ] ) ) {
			return $data; // already a workflow status (e.g. our transition())
		}
		// A standard editor status (publish/draft/pending/private) slipped in:
		// preserve the existing workflow status, or default a brand-new post.
		$existing = ( ! empty( $postarr['ID'] ) ) ? get_post_status( $postarr['ID'] ) : '';
		$data['post_status'] = isset( self::STATUSES[ $existing ] ) ? $existing : 'vhh-pending';
		return $data;
	}

	/**
	 * WordPress silently excludes 'internal' post statuses from the default
	 * "All" admin list view (and from any WP_Query post_status=>'any' lookup)
	 * unless a specific status tab is clicked — so a freshly-filed to-do
	 * looks like it "vanished." Force the default view to include all of
	 * our statuses; a real status filter click (post_status in $_GET) is
	 * left untouched.
	 */
	public static function admin_default_status_filter( $query ) {
		// NOTE: deliberately NOT gated on is_main_query() — the wp-admin post
		// list table builds its own WP_Query (via wp_edit_posts_query()) that
		// is never the site's "main query" object, so that check silently
		// prevented this filter from ever applying to the admin screen it
		// exists for.
		if ( ! is_admin() ) {
			return;
		}
		if ( self::CPT !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( ! $query->get( 'post_status' ) && empty( $_GET['post_status'] ) ) {
			$query->set( 'post_status', array_keys( self::STATUSES ) );
		}
	}

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'label'           => __( 'Content Feedback', 'vhh-annotations' ),
				'labels'          => array(
					'name'          => __( 'Content Feedback', 'vhh-annotations' ),
					'singular_name' => __( 'Content Feedback Item', 'vhh-annotations' ),
					'menu_name'     => __( 'Content Feedback', 'vhh-annotations' ),
					'all_items'     => __( 'Content Feedback', 'vhh-annotations' ),
				),
				'public'          => false,
				'show_ui'         => true,
				// Nested under the theme's "VanceHealthHub" menu (parent slug
				// 'vance-content-hub', created by vance_register_content_hub_menu()
				// in the theme); core's _add_post_type_submenus attaches it there.
				// This briefly appeared to "vanish"/403 earlier, but that was two
				// unrelated, now-fixed causes — Hostinger LSAPI serving stale
				// bytecode after deploys, and the 'practitioner' role revoking
				// edit_posts from multi-role admins — NOT the nesting itself.
				'show_in_menu'    => 'vance-content-hub',
				'supports'        => array( 'title', 'editor' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'show_in_rest'    => false,
			)
		);

		foreach ( self::STATUSES as $status => $label ) {
			register_post_status(
				$status,
				array(
					'label'                     => $label,
					'internal'                  => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					// 'internal' => true otherwise defaults this to true too,
					// which makes WP_Query's post_status=>'any' (used by
					// `wp post list`, our own /todos REST list, etc.) silently
					// skip these rows even though they're neither public nor
					// searchable in the sense that flag is meant to gate.
					'exclude_from_search'       => false,
					/* translators: %s: count */
					'label_count'               => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>' ),
				)
			);
		}
	}

	public static function post_states( $states, $post ) {
		if ( $post && self::CPT === $post->post_type && isset( self::STATUSES[ $post->post_status ] ) ) {
			$states[ $post->post_status ] = self::STATUSES[ $post->post_status ];
		}
		return $states;
	}

	/* -------------------- server-side extraction button ---------------- */

	/** Action bar (Generate button) + result notice on the to-do list screen. */
	public static function list_actions() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-' . self::CPT !== $screen->id || ! current_user_can( VHH_Capabilities::CAP_MODERATE ) ) {
			return;
		}

		// Result of a previous generate run.
		if ( isset( $_GET['vhh_generated'] ) ) {
			$n = absint( $_GET['vhh_generated'] );
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sprintf(
					/* translators: %d: number created */
					_n( 'Created %d to-do from new comments.', 'Created %d to-dos from new comments.', $n, 'vhh-annotations' ),
					$n
				) )
			);
		}

		$open = self::open_unlinked_annotation_count();
		$url  = wp_nonce_url(
			add_query_arg( array( 'action' => 'vhh_generate_todos' ), admin_url( 'admin-post.php' ) ),
			'vhh_generate_todos'
		);
		echo '<div class="notice notice-info" style="display:flex;align-items:center;gap:12px;">';
		echo '<p style="margin:.6em 0;">';
		if ( $open > 0 ) {
			printf(
				'<a href="%s" class="button button-primary">%s</a>',
				esc_url( $url ),
				esc_html( sprintf(
					/* translators: %d: number of comments */
					_n( 'Generate to-do from %d new comment', 'Generate to-dos from %d new comments', $open, 'vhh-annotations' ),
					$open
				) )
			);
		} else {
			echo '<span class="description">' . esc_html__( 'No new comments to turn into to-dos. Leave highlights on articles, then this button will offer to file them.', 'vhh-annotations' ) . '</span>';
		}
		echo '</p></div>';
	}

	/** Open annotations not yet linked to any to-do. */
	private static function open_unlinked_annotation_count() {
		$comments = VHH_Annotation_Store::get_recent( 'open', 500 );
		$n = 0;
		foreach ( $comments as $c ) {
			if ( ! (int) get_comment_meta( $c->comment_ID, '_vhh_claude_task_id', true ) ) {
				$n++;
			}
		}
		return $n;
	}

	/**
	 * Mechanical 1:1 extraction: one pending to-do per open, un-linked
	 * annotation. This is the self-service quick path; the Claude
	 * /extract-comments skill does smarter clustering + noise-filtering.
	 */
	public static function handle_generate_todos() {
		if ( ! current_user_can( VHH_Capabilities::CAP_MODERATE ) ) {
			wp_die( 'Forbidden', 403 );
		}
		check_admin_referer( 'vhh_generate_todos' );

		$comments = VHH_Annotation_Store::get_recent( 'open', 500 );
		$created  = 0;
		foreach ( $comments as $c ) {
			if ( (int) get_comment_meta( $c->comment_ID, '_vhh_claude_task_id', true ) ) {
				continue; // already in a to-do
			}
			$quote = VHH_Annotation_Store::quote_for( $c );
			$title = wp_trim_words( $c->comment_content, 10, '…' ) . ' — ' . wp_trim_words( $quote, 8, '…' );
			// thread_text = comment + its replies; a reply often narrows or
			// corrects the request, so it must reach the to-do and the AI.
			$detail = sprintf(
				"Comment #%d on \"%s\":\n\n> %s\n\n%s",
				$c->comment_ID,
				get_the_title( $c->comment_post_ID ),
				$quote,
				VHH_Annotation_Store::thread_text( $c )
			);
			$todo_id = wp_insert_post(
				wp_slash(
					array(
						'post_type'    => self::CPT,
						'post_status'  => 'vhh-pending',
						'post_title'   => $title,
						'post_content' => $detail,
					)
				)
			);
			if ( $todo_id && ! is_wp_error( $todo_id ) ) {
				update_post_meta( $todo_id, '_vhh_target_post', (int) $c->comment_post_ID );
				update_post_meta( $todo_id, '_vhh_source_annotations', array( (int) $c->comment_ID ) );
				update_post_meta( $todo_id, '_vhh_claude_session', 'admin-button-' . gmdate( 'Y-m-d' ) );
				update_comment_meta( $c->comment_ID, '_vhh_claude_task_id', $todo_id );
				$created++;
			}
		}
		wp_safe_redirect( add_query_arg(
			array( 'post_type' => self::CPT, 'vhh_generated' => $created ),
			admin_url( 'edit.php' )
		) );
		exit;
	}

	public static function handle_transition() {
		if ( ! current_user_can( VHH_Capabilities::CAP_MODERATE ) ) {
			wp_die( 'Forbidden', 403 );
		}
		$todo_id = isset( $_GET['todo'] ) ? absint( $_GET['todo'] ) : 0;
		check_admin_referer( 'vhh_todo_' . $todo_id );

		$to = isset( $_GET['to'] ) ? sanitize_key( $_GET['to'] ) : '';
		$map = array(
			'approve' => 'vhh-approved',
			'reject'  => 'vhh-rejected',
			'done'    => 'vhh-done',
		);
		if ( isset( $map[ $to ] ) ) {
			self::transition( $todo_id, $map[ $to ] );
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . self::CPT ) );
		exit;
	}

	/**
	 * Move a todo to a new status, firing hooks and the auto-resolve chain.
	 *
	 * @return true|WP_Error
	 */
	public static function transition( $todo_id, $new_status ) {
		$todo = get_post( $todo_id );
		if ( ! $todo || self::CPT !== $todo->post_type ) {
			return new WP_Error( 'vhh_not_found', 'To-do not found.', array( 'status' => 404 ) );
		}
		if ( ! isset( self::STATUSES[ $new_status ] ) ) {
			return new WP_Error( 'vhh_bad_status', 'Unknown status.', array( 'status' => 400 ) );
		}

		wp_update_post(
			array(
				'ID'          => $todo_id,
				'post_status' => $new_status,
			)
		);

		if ( 'vhh-approved' === $new_status ) {
			do_action( 'vhh_todo_approved', $todo_id );
		}

		if ( 'vhh-rejected' === $new_status ) {
			// Release the source annotations so future extraction runs can
			// re-propose them — a rejected task must not hide feedback forever.
			$sources = get_post_meta( $todo_id, '_vhh_source_annotations', true );
			foreach ( (array) $sources as $annotation_id ) {
				if ( (int) get_comment_meta( (int) $annotation_id, '_vhh_claude_task_id', true ) === (int) $todo_id ) {
					delete_comment_meta( (int) $annotation_id, '_vhh_claude_task_id' );
				}
			}
			do_action( 'vhh_todo_rejected', $todo_id );
		}

		if ( 'vhh-done' === $new_status ) {
			do_action( 'vhh_todo_done', $todo_id );
			if ( VHH_Plugin::get( 'auto_resolve' ) ) {
				$sources = get_post_meta( $todo_id, '_vhh_source_annotations', true );
				foreach ( (array) $sources as $annotation_id ) {
					VHH_Annotation_Store::resolve( (int) $annotation_id, get_current_user_id() );
				}
			}
		}
		return true;
	}

	/** API shape for REST. */
	public static function to_api( WP_Post $todo ) {
		return array(
			'id'                 => $todo->ID,
			'title'              => $todo->post_title,
			'detail'             => $todo->post_content,
			'status'             => str_replace( 'vhh-', '', $todo->post_status ),
			'target_post'        => (int) get_post_meta( $todo->ID, '_vhh_target_post', true ),
			'source_annotations' => array_map( 'intval', (array) get_post_meta( $todo->ID, '_vhh_source_annotations', true ) ),
			'claude_session'     => (string) get_post_meta( $todo->ID, '_vhh_claude_session', true ),
			'proposed_diff'      => (string) get_post_meta( $todo->ID, '_vhh_proposed_diff', true ),
			'created'            => gmdate( 'c', strtotime( $todo->post_date_gmt . ' UTC' ) ),
		);
	}
}
