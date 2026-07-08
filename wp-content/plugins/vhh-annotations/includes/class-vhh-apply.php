<?php
/**
 * AI-assisted edit engine: turn an approved vhh_todo into an actual article
 * edit via OpenRouter, with a mandatory human preview-and-confirm step.
 *
 * Flow (all on the to-do edit screen, "AI edit" meta box):
 *   approved todo → "Generate preview" → OpenRouter proposes revised HTML,
 *   stored in post meta → admin sees a before/after diff → "Confirm & apply"
 *   writes it to the article (reversible revision), marks the todo done and
 *   resolves its source annotations. "Discard" throws the proposal away.
 *
 * The article is never touched until the human confirms the exact diff.
 * Credentials are reused from the theme's Ask AI config (OpenRouter); nothing
 * is hardcoded.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_Apply {

	const ENDPOINT       = 'https://openrouter.ai/api/v1/chat/completions';
	const META_PROPOSED  = '_vhh_proposed_content';
	const META_SRC_HASH  = '_vhh_proposed_src_hash';
	const DEFAULT_MODEL  = 'anthropic/claude-opus-4.8';

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'admin_post_vhh_ai_preview', array( __CLASS__, 'handle_preview' ) );
		add_action( 'admin_post_vhh_ai_apply', array( __CLASS__, 'handle_apply' ) );
		add_action( 'admin_post_vhh_ai_discard', array( __CLASS__, 'handle_discard' ) );
		add_action( 'wp_ajax_vhh_ai_generate', array( __CLASS__, 'ajax_generate' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
	}

	public static function enqueue_admin( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || VHH_Todos_CPT::CPT !== $screen->post_type ) {
			return;
		}
		wp_enqueue_style( 'vhh-admin', VHH_ANN_URL . 'assets/css/admin.css', array(), VHH_ANN_VERSION . '-' . ( @filemtime( VHH_ANN_DIR . 'assets/css/admin.css' ) ?: '1' ) );
		wp_enqueue_script( 'vhh-admin', VHH_ANN_URL . 'assets/js/admin-todo.js', array(), VHH_ANN_VERSION . '-' . ( @filemtime( VHH_ANN_DIR . 'assets/js/admin-todo.js' ) ?: '1' ), true );
		wp_localize_script(
			'vhh-admin',
			'VHH_ADMIN',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'vhh_ai_generate' ),
				'generating' => __( 'Generating the edit with AI…', 'vhh-annotations' ),
				'failed'     => __( 'Generation failed', 'vhh-annotations' ),
			)
		);
	}

	/** AJAX: generate a preview and return the modal review HTML (no reload). */
	public static function ajax_generate() {
		if ( ! current_user_can( VHH_Capabilities::CAP_MODERATE ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden', 'vhh-annotations' ) ), 403 );
		}
		check_ajax_referer( 'vhh_ai_generate', 'nonce' );
		$todo_id = isset( $_POST['todo'] ) ? absint( $_POST['todo'] ) : 0;

		$res = self::generate( $todo_id );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}
		wp_send_json_success(
			array(
				'html'    => self::review_html( $todo_id ),
				'warning' => $res['warning'],
			)
		);
	}

	/**
	 * Block-level rendered redline: for each changed region, the "before" and
	 * "after" blocks rendered as HTML (so you see the formatted result, not
	 * escaped tags). Only changed blocks are shown — the unchanged bulk is
	 * skipped. Output is sanitized with wp_kses_post.
	 */
	private static function changed_excerpts_html( $current, $proposed ) {
		require_once ABSPATH . WPINC . '/wp-diff.php';

		$from = explode( "\n", str_replace( "\r", '', (string) $current ) );
		$to   = explode( "\n", str_replace( "\r", '', (string) $proposed ) );
		$diff = new Text_Diff( 'auto', array( $from, $to ) );

		$out = '';
		foreach ( $diff->getDiff() as $op ) {
			if ( 'Text_Diff_Op_copy' === get_class( $op ) ) {
				continue; // unchanged block — skip
			}
			$before = trim( implode( "\n", (array) $op->orig ) );
			$after  = trim( implode( "\n", (array) $op->final ) );
			if ( '' === $before && '' === $after ) {
				continue;
			}
			$out .= '<div class="vhh-xrp">';
			if ( '' !== $before ) {
				$out .= '<div class="vhh-xrp-before"><span class="vhh-xrp-label">' . esc_html__( 'Before', 'vhh-annotations' ) . '</span>'
					. '<div class="vhh-xrp-render">' . wp_kses_post( $before ) . '</div></div>';
			}
			if ( '' !== $after ) {
				$out .= '<div class="vhh-xrp-after"><span class="vhh-xrp-label">' . esc_html__( 'After', 'vhh-annotations' ) . '</span>'
					. '<div class="vhh-xrp-render">' . wp_kses_post( $after ) . '</div></div>';
			}
			$out .= '</div>';
		}
		if ( '' === $out ) {
			$out = '<p class="description">' . esc_html__( 'No block-level changes detected (the change may be whitespace-only).', 'vhh-annotations' ) . '</p>';
		}
		return $out;
	}

	/* ------------------------- credentials ---------------------------- */

	public static function api_key() {
		// Plugin override first (survives a theme switch), else the theme's
		// Ask AI OpenRouter key.
		$override = trim( (string) VHH_Plugin::get( 'openrouter_key' ) );
		if ( $override ) {
			return $override;
		}
		if ( function_exists( 'vance_get_theme_mod' ) ) {
			return trim( (string) vance_get_theme_mod( 'vance_askai_api_key', '' ) );
		}
		return trim( (string) get_theme_mod( 'vance_askai_api_key', '' ) );
	}

	public static function model() {
		$override = trim( (string) VHH_Plugin::get( 'ai_edit_model' ) );
		if ( $override ) {
			return $override;
		}
		$theme_model = function_exists( 'vance_get_theme_mod' )
			? trim( (string) vance_get_theme_mod( 'vance_askai_model', '' ) )
			: trim( (string) get_theme_mod( 'vance_askai_model', '' ) );
		return $theme_model ?: self::DEFAULT_MODEL;
	}

	/* --------------------------- generation --------------------------- */

	/**
	 * Ask the model to apply the to-do's instruction to its target article.
	 *
	 * @return array|WP_Error { proposed: string(HTML), warning: string|'' }
	 */
	public static function generate( $todo_id ) {
		$todo = get_post( $todo_id );
		if ( ! $todo || VHH_Todos_CPT::CPT !== $todo->post_type ) {
			return new WP_Error( 'vhh_not_found', 'To-do not found.' );
		}
		$post_id = (int) get_post_meta( $todo_id, '_vhh_target_post', true );
		$post    = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'vhh_bad_post', 'Target article not found.' );
		}
		$key = self::api_key();
		if ( '' === $key ) {
			return new WP_Error( 'vhh_no_key', 'No OpenRouter key. Set it in Appearance → Customize → Ask AI Configuration (or the Article Annotations override).' );
		}

		$instruction = $todo->post_title . "\n\n" . $todo->post_content;
		$source      = $post->post_content;

		$system = 'You are a meticulous copy editor for a medical content website (Vance Medical Hub). '
			. 'You receive the full HTML body of a published article and ONE editing instruction. '
			. 'Apply ONLY that instruction. Do not change any other wording, facts, HTML tags, attributes, '
			. 'links, or structure — preserve everything else byte-for-byte. Never invent medical claims. '
			. 'Output ONLY the complete revised article HTML: no commentary, no explanation, no markdown code fences.';

		$user = "EDITING INSTRUCTION:\n" . $instruction . "\n\nARTICLE HTML (return the full revised HTML):\n" . $source;

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout' => 90,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $key,
					'HTTP-Referer'  => home_url(),
					'X-Title'       => 'Vance Medical Hub — Annotation edits',
				),
				'body'    => wp_json_encode(
					array(
						'model'       => self::model(),
						'temperature' => 0.1,
						'max_tokens'  => 16000,
						'messages'    => array(
							array( 'role' => 'system', 'content' => $system ),
							array( 'role' => 'user', 'content' => $user ),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'vhh_api_error', 'Could not reach OpenRouter: ' . $response->get_error_message() );
		}
		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $code ) {
			$msg = isset( $data['error']['message'] ) ? $data['error']['message'] : ( 'HTTP ' . $code );
			return new WP_Error( 'vhh_api_error', 'OpenRouter error: ' . $msg );
		}

		$choice = $data['choices'][0] ?? array();
		$out    = isset( $choice['message']['content'] ) ? (string) $choice['message']['content'] : '';
		$finish = $choice['finish_reason'] ?? '';
		$out    = self::strip_fences( trim( $out ) );

		if ( '' === $out ) {
			return new WP_Error( 'vhh_empty', 'The model returned no content.' );
		}
		// A truncated response would silently chop the article — refuse it.
		if ( 'length' === $finish ) {
			return new WP_Error( 'vhh_truncated', 'The model hit its output limit and the result was truncated — not applying. Try a shorter article or a larger-context model.' );
		}

		// Length sanity check against the source.
		$warning = '';
		$ratio   = strlen( $source ) > 0 ? ( strlen( $out ) / strlen( $source ) ) : 1;
		if ( $ratio < 0.4 ) {
			return new WP_Error( 'vhh_too_short', sprintf( 'Result is only %d%% of the original length — likely incomplete. Not storing.', (int) ( $ratio * 100 ) ) );
		}
		if ( $ratio < 0.7 || $ratio > 1.5 ) {
			$warning = sprintf( 'Heads up: the revised length is %d%% of the original — review the diff carefully before confirming.', (int) ( $ratio * 100 ) );
		}

		update_post_meta( $todo_id, self::META_PROPOSED, wp_slash( $out ) );
		update_post_meta( $todo_id, self::META_SRC_HASH, md5( $source ) );

		return array( 'proposed' => $out, 'warning' => $warning );
	}

	private static function strip_fences( $text ) {
		if ( preg_match( '/^```[a-zA-Z]*\s*\n(.*)\n```$/s', $text, $m ) ) {
			return trim( $m[1] );
		}
		return $text;
	}

	/* ----------------------------- apply ------------------------------ */

	public static function apply( $todo_id ) {
		$proposed = get_post_meta( $todo_id, self::META_PROPOSED, true );
		if ( '' === (string) $proposed ) {
			return new WP_Error( 'vhh_no_proposal', 'No AI preview to apply — generate one first.' );
		}
		$post_id = (int) get_post_meta( $todo_id, '_vhh_target_post', true );
		$post    = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'vhh_bad_post', 'Target article not found.' );
		}
		// If the article changed since the preview was generated, don't clobber it.
		$src_hash = (string) get_post_meta( $todo_id, self::META_SRC_HASH, true );
		if ( $src_hash && $src_hash !== md5( $post->post_content ) ) {
			return new WP_Error( 'vhh_stale', 'The article changed since this preview was generated. Discard and regenerate before applying.' );
		}

		$res = wp_update_post(
			array( 'ID' => $post_id, 'post_content' => wp_slash( $proposed ) ),
			true
		);
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		delete_post_meta( $todo_id, self::META_PROPOSED );
		delete_post_meta( $todo_id, self::META_SRC_HASH );
		VHH_Todos_CPT::transition( $todo_id, 'vhh-done' );
		return true;
	}

	/* --------------------------- meta box ----------------------------- */

	public static function add_meta_box() {
		add_meta_box(
			'vhh-todo-ai-edit',
			__( 'Review & apply', 'vhh-annotations' ),
			array( __CLASS__, 'render_meta_box' ),
			VHH_Todos_CPT::CPT,
			'side',
			'high'
		);
	}

	/** Nonce'd link to the AI action handlers (vhh_ai_*). */
	private static function ai_link( $post_id, $do, $label, $primary ) {
		$url = wp_nonce_url(
			add_query_arg( array( 'action' => 'vhh_ai_' . $do, 'todo' => $post_id ), admin_url( 'admin-post.php' ) ),
			'vhh_ai_' . $do . '_' . $post_id
		);
		return '<a href="' . esc_url( $url ) . '" class="button ' . ( $primary ? 'button-primary' : '' ) . '" style="margin:2px 4px 2px 0;">' . esc_html( $label ) . '</a>';
	}

	/** Nonce'd link to the reject transition (reuses vhh_todo_transition). */
	private static function reject_link( $post_id ) {
		$url = wp_nonce_url(
			add_query_arg( array( 'action' => 'vhh_todo_transition', 'todo' => $post_id, 'to' => 'reject' ), admin_url( 'admin-post.php' ) ),
			'vhh_todo_' . $post_id
		);
		return '<a href="' . esc_url( $url ) . '" class="button" style="margin:2px 4px 2px 0;color:#b32d2e;">' . esc_html__( 'Reject', 'vhh-annotations' ) . '</a>';
	}

	/**
	 * The single control surface for a to-do: source context, then the
	 * generate → review-diff → confirm/reject flow. No separate approve step.
	 */
	public static function render_meta_box( $post ) {
		if ( ! current_user_can( VHH_Capabilities::CAP_MODERATE ) ) {
			return;
		}
		$status = $post->post_status;

		if ( 'vhh-done' === $status ) {
			echo '<p class="description">' . esc_html__( '✓ Edit applied — see the article’s Revisions to review or revert.', 'vhh-annotations' ) . '</p>';
			return;
		}
		if ( 'vhh-rejected' === $status ) {
			echo '<p class="description">' . esc_html__( '✕ Rejected. Delete this to-do, or re-generate a preview below to reconsider.', 'vhh-annotations' ) . '</p>';
		}

		// Source context — what this to-do is about — so you can decide before generating.
		$target   = get_post( (int) get_post_meta( $post->ID, '_vhh_target_post', true ) );
		$sources  = (array) get_post_meta( $post->ID, '_vhh_source_annotations', true );
		if ( $target ) {
			echo '<p><strong>' . esc_html__( 'Article:', 'vhh-annotations' ) . '</strong> <a href="' . esc_url( get_edit_post_link( $target->ID ) ) . '">' . esc_html( get_the_title( $target->ID ) ) . '</a> · <a href="' . esc_url( get_permalink( $target->ID ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'view', 'vhh-annotations' ) . '</a></p>';
		}
		if ( $sources ) {
			echo '<p style="margin-bottom:2px;"><strong>' . esc_html__( 'From comments:', 'vhh-annotations' ) . '</strong></p><ul style="margin:0 0 8px 14px;list-style:disc;">';
			foreach ( $sources as $sid ) {
				$c = get_comment( (int) $sid );
				if ( $c ) {
					echo '<li>' . esc_html( wp_trim_words( $c->comment_content, 24 ) ) . '</li>';
				}
			}
			echo '</ul>';
		}

		if ( '' === self::api_key() ) {
			echo '<p class="description">' . esc_html__( 'No OpenRouter key found. Set it in Appearance → Customize → Ask AI Configuration.', 'vhh-annotations' ) . '</p>';
			echo '<p>' . self::reject_link( $post->ID ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			return;
		}

		$proposed = (string) get_post_meta( $post->ID, self::META_PROPOSED, true );
		$has      = ( '' !== $proposed );
		$modal_id = 'vhh-diff-modal';

		// One button. With a preview already generated it just opens the modal
		// ("Review changes"); with none, it AJAX-generates and opens the modal
		// in one click — no page reload, no separate step.
		echo '<p class="description">' . esc_html__( 'Generate the edit with AI, review the diff in a popup, then confirm to apply it live (as a reversible revision).', 'vhh-annotations' ) . '</p>';
		echo '<p>';
		if ( $has ) {
			echo '<button type="button" class="button button-primary" data-vhh-open-modal="' . esc_attr( $modal_id ) . '">' . esc_html__( 'Review changes', 'vhh-annotations' ) . '</button> ';
		} else {
			echo '<button type="button" class="button button-primary" data-vhh-generate="' . esc_attr( $post->ID ) . '" data-vhh-modal="' . esc_attr( $modal_id ) . '">' . esc_html__( 'Generate AI edit preview', 'vhh-annotations' ) . '</button> ';
		}
		echo self::reject_link( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</p>';
		echo '<p class="description">' . esc_html( sprintf( __( 'Model: %s', 'vhh-annotations' ), self::model() ) ) . '</p>';

		// Modal shell. Body is pre-filled when a preview exists; otherwise the
		// AJAX generate response fills #vhh-ai-review and opens it.
		echo '<div id="' . esc_attr( $modal_id ) . '" class="vhh-modal" hidden>';
		echo '<div class="vhh-modal-backdrop" data-vhh-close-modal></div>';
		echo '<div class="vhh-modal-dialog" role="dialog" aria-modal="true" aria-label="' . esc_attr__( 'Review AI edit', 'vhh-annotations' ) . '">';
		echo '<button type="button" class="vhh-modal-close" data-vhh-close-modal aria-label="' . esc_attr__( 'Close', 'vhh-annotations' ) . '">&times;</button>';
		echo '<h2>' . esc_html__( 'Review AI edit', 'vhh-annotations' ) . '</h2>';
		if ( $target ) {
			echo '<p class="description" style="padding:0 22px;">' . esc_html( get_the_title( $target->ID ) ) . '</p>';
		}
		echo '<div id="vhh-ai-review">' . ( $has ? self::review_html( $post->ID ) : '' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div></div>';
	}

	/**
	 * Inner modal content: rendered changed excerpts + raw diff + action
	 * buttons. Shared by the meta box (preview already exists) and the AJAX
	 * generate response. Returns '' if there is no stored proposal.
	 */
	public static function review_html( $todo_id ) {
		$proposed = (string) get_post_meta( $todo_id, self::META_PROPOSED, true );
		if ( '' === $proposed ) {
			return '';
		}
		$target   = get_post( (int) get_post_meta( $todo_id, '_vhh_target_post', true ) );
		$src_hash = (string) get_post_meta( $todo_id, self::META_SRC_HASH, true );
		$stale    = ( $target && $src_hash && $src_hash !== md5( $target->post_content ) );

		require_once ABSPATH . 'wp-admin/includes/revision.php';
		$raw = $target ? wp_text_diff(
			$target->post_content,
			wp_unslash( $proposed ),
			array( 'title_left' => __( 'Current HTML', 'vhh-annotations' ), 'title_right' => __( 'Proposed HTML', 'vhh-annotations' ) )
		) : '';

		$out  = '<div class="vhh-modal-body">';
		if ( $stale ) {
			$out .= '<p class="notice notice-warning" style="padding:6px 10px;">' . esc_html__( 'The article changed since this preview. Discard and regenerate before applying.', 'vhh-annotations' ) . '</p>';
		}
		$out .= self::changed_excerpts_html( $target ? $target->post_content : '', wp_unslash( $proposed ) );
		if ( $raw ) {
			$out .= '<details class="vhh-rawdiff"><summary>' . esc_html__( 'Show raw HTML diff', 'vhh-annotations' ) . '</summary>' . $raw . '</details>';
		}
		$out .= '</div>';

		$out .= '<div class="vhh-modal-actions">';
		if ( ! $stale ) {
			$out .= self::ai_link( $todo_id, 'apply', __( 'Confirm & apply (live)', 'vhh-annotations' ), true );
		}
		$out .= self::ai_link( $todo_id, 'discard', __( 'Discard', 'vhh-annotations' ), false );
		// Regenerate re-runs the AJAX generate in place (no reload).
		$out .= '<button type="button" class="button" data-vhh-generate="' . esc_attr( $todo_id ) . '" data-vhh-modal="vhh-diff-modal">' . esc_html__( 'Regenerate', 'vhh-annotations' ) . '</button>';
		$out .= '</div>';
		return $out;
	}

	/* ------------------------- admin handlers ------------------------- */

	private static function guard( $do ) {
		if ( ! current_user_can( VHH_Capabilities::CAP_MODERATE ) ) {
			wp_die( 'Forbidden', 403 );
		}
		$todo_id = isset( $_REQUEST['todo'] ) ? absint( $_REQUEST['todo'] ) : 0;
		check_admin_referer( 'vhh_ai_' . $do . '_' . $todo_id );
		return $todo_id;
	}

	private static function back( $todo_id, $notice, $type = 'success' ) {
		wp_safe_redirect( add_query_arg(
			array( 'post' => $todo_id, 'action' => 'edit', 'vhh_ai_notice' => rawurlencode( $notice ), 'vhh_ai_type' => $type ),
			admin_url( 'post.php' )
		) );
		exit;
	}

	public static function handle_preview() {
		$todo_id = self::guard( 'preview' );
		$res     = self::generate( $todo_id );
		if ( is_wp_error( $res ) ) {
			self::back( $todo_id, $res->get_error_message(), 'error' );
		}
		self::back( $todo_id, $res['warning'] ?: __( 'Preview generated — review the diff below.', 'vhh-annotations' ), $res['warning'] ? 'warning' : 'success' );
	}

	public static function handle_apply() {
		$todo_id = self::guard( 'apply' );
		$res     = self::apply( $todo_id );
		if ( is_wp_error( $res ) ) {
			self::back( $todo_id, $res->get_error_message(), 'error' );
		}
		self::back( $todo_id, __( 'Edit applied to the article (saved as a revision) and to-do marked done.', 'vhh-annotations' ) );
	}

	public static function handle_discard() {
		$todo_id = self::guard( 'discard' );
		delete_post_meta( $todo_id, self::META_PROPOSED );
		delete_post_meta( $todo_id, self::META_SRC_HASH );
		self::back( $todo_id, __( 'Preview discarded.', 'vhh-annotations' ) );
	}

	public static function admin_notice() {
		if ( empty( $_GET['vhh_ai_notice'] ) ) {
			return;
		}
		$type = in_array( $_GET['vhh_ai_type'] ?? '', array( 'success', 'warning', 'error' ), true ) ? $_GET['vhh_ai_type'] : 'info';
		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $type ),
			esc_html( sanitize_text_field( wp_unslash( $_GET['vhh_ai_notice'] ) ) )
		);
	}
}
