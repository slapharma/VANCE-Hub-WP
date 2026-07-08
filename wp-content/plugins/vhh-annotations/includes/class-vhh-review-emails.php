<?php
/**
 * Email review UX: "Request review" meta box, review emails, and the
 * standalone reviewer page (served via template_include on the post
 * permalink with ?vhh_review=TOKEN — GET never mutates anything).
 *
 * Also owns the optional "notify post author on new annotation" email.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_Review_Emails {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'admin_post_vhh_send_review', array( __CLASS__, 'handle_meta_box_send' ) );
		add_filter( 'template_include', array( __CLASS__, 'maybe_review_template' ) );
		add_action( 'vhh_annotation_created', array( __CLASS__, 'maybe_notify_author' ), 10, 2 );
	}

	/* --------------------------- review emails -------------------------- */

	/**
	 * Send the two-link (approve / request changes) review email.
	 *
	 * @return bool
	 */
	public static function send_review_email( WP_Post $post, $email, $name ) {
		$days      = (int) VHH_Plugin::get( 'email_expiry_days' );
		$permalink = get_permalink( $post );

		$approve_token = VHH_REST_Review::mint( $post->ID, $email, $name, 'approve', $days );
		$changes_token = VHH_REST_Review::mint( $post->ID, $email, $name, 'changes', $days );

		$approve_url = add_query_arg( 'vhh_review', rawurlencode( $approve_token ), $permalink );
		$changes_url = add_query_arg( 'vhh_review', rawurlencode( $changes_token ), $permalink );

		$subject = sprintf(
			/* translators: %s: post title */
			__( 'Review requested: %s', 'vhh-annotations' ),
			$post->post_title
		);
		$body = sprintf(
			"%s\n\n%s\n%s\n\n%s\n%s\n\n%s\n%s\n\n%s",
			sprintf( __( 'Hi %s,', 'vhh-annotations' ), $name ),
			sprintf( __( 'You have been asked to review the article "%s" on %s.', 'vhh-annotations' ), $post->post_title, get_bloginfo( 'name' ) ),
			$permalink,
			__( 'APPROVE — the article is good to go:', 'vhh-annotations' ),
			$approve_url,
			__( 'REQUEST CHANGES — highlight text or images and leave comments:', 'vhh-annotations' ),
			$changes_url,
			sprintf( __( 'These links are personal to you and expire in %d days.', 'vhh-annotations' ), $days )
		);

		return wp_mail( $email, $subject, $body );
	}

	/* ----------------------------- meta box ----------------------------- */

	public static function add_meta_box() {
		if ( ! VHH_Plugin::enabled() || ! VHH_Plugin::get( 'email_review' ) || ! current_user_can( VHH_Capabilities::CAP_MODERATE ) ) {
			return;
		}
		foreach ( (array) VHH_Plugin::get( 'post_types' ) as $post_type ) {
			add_meta_box( 'vhh-request-review', __( 'Request review', 'vhh-annotations' ), array( __CLASS__, 'render_meta_box' ), $post_type, 'side' );
		}
	}

	public static function render_meta_box( $post ) {
		$approvals = get_post_meta( $post->ID, '_vhh_approvals', true );
		if ( is_array( $approvals ) && $approvals ) {
			echo '<p><strong>' . esc_html__( 'Approvals:', 'vhh-annotations' ) . '</strong></p><ul style="margin:0 0 8px 16px;">';
			foreach ( $approvals as $email => $info ) {
				echo '<li>' . esc_html( ( $info['name'] ?: $email ) . ' — ' . mysql2date( get_option( 'date_format' ), $info['at'] ) ) . '</li>';
			}
			echo '</ul>';
		}
		if ( 'publish' !== $post->post_status ) {
			echo '<p class="description">' . esc_html__( 'Publish the post first — review links point at the live article.', 'vhh-annotations' ) . '</p>';
			return;
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="vhh_send_review">
			<input type="hidden" name="post_id" value="<?php echo esc_attr( $post->ID ); ?>">
			<?php wp_nonce_field( 'vhh_send_review_' . $post->ID ); ?>
			<p>
				<label for="vhh-review-emails"><?php esc_html_e( 'Reviewer emails (one per line, optionally "Name <email>"):', 'vhh-annotations' ); ?></label>
				<textarea id="vhh-review-emails" name="vhh_review_emails" rows="3" style="width:100%;"></textarea>
			</p>
			<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Send review links', 'vhh-annotations' ); ?></button></p>
		</form>
		<?php
	}

	public static function handle_meta_box_send() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		check_admin_referer( 'vhh_send_review_' . $post_id );
		if ( ! current_user_can( VHH_Capabilities::CAP_MODERATE ) || ! VHH_Plugin::get( 'email_review' ) || ! VHH_Plugin::enabled() ) {
			wp_die( 'Forbidden', 403 );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( 'Post not found', 404 );
		}

		$lines = preg_split( '/[\r\n]+/', (string) wp_unslash( $_POST['vhh_review_emails'] ?? '' ) );
		$sent  = 0;
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( ! $line ) {
				continue;
			}
			$name  = '';
			$email = $line;
			if ( preg_match( '/^(.*)<([^>]+)>\s*$/', $line, $m ) ) {
				$name  = trim( $m[1] );
				$email = trim( $m[2] );
			}
			$email = sanitize_email( $email );
			if ( is_email( $email ) && self::send_review_email( $post, $email, $name ?: $email ) ) {
				$sent++;
			}
		}

		wp_safe_redirect( add_query_arg( array( 'post' => $post_id, 'action' => 'edit', 'vhh_sent' => $sent ), admin_url( 'post.php' ) ) );
		exit;
	}

	/* --------------------------- reviewer page --------------------------- */

	public static function maybe_review_template( $template ) {
		if ( empty( $_GET['vhh_review'] ) || ! is_singular() ) {
			return $template;
		}
		if ( ! VHH_Plugin::enabled() || ! VHH_Plugin::get( 'email_review' ) ) {
			return $template;
		}
		$payload = VHH_REST_Review::verify( sanitize_text_field( wp_unslash( $_GET['vhh_review'] ) ) );
		if ( is_wp_error( $payload ) || (int) $payload['p'] !== get_queried_object_id() ) {
			return $template; // invalid token → plain article, no reviewer UI
		}

		// Stash for the template.
		$GLOBALS['vhh_review_payload'] = $payload;
		$GLOBALS['vhh_review_token']   = sanitize_text_field( wp_unslash( $_GET['vhh_review'] ) );
		return VHH_ANN_DIR . 'templates/review-page.php';
	}

	/* ------------------------- author notification ----------------------- */

	public static function maybe_notify_author( $annotation_id, $post_id ) {
		if ( ! VHH_Plugin::get( 'notify_author' ) ) {
			return;
		}
		$post   = get_post( $post_id );
		$author = $post ? get_userdata( $post->post_author ) : null;
		if ( ! $author || ! $author->user_email ) {
			return;
		}
		$comment = get_comment( $annotation_id );
		if ( ! $comment || (int) $comment->user_id === (int) $post->post_author ) {
			return; // don't notify authors about their own notes
		}
		wp_mail(
			$author->user_email,
			sprintf( __( 'New annotation on "%s"', 'vhh-annotations' ), $post->post_title ),
			sprintf(
				"%s\n\n\"%s\"\n\n%s",
				sprintf( __( '%s left a note on your article.', 'vhh-annotations' ), $comment->comment_author ),
				$comment->comment_content,
				get_permalink( $post_id )
			)
		);
	}
}
