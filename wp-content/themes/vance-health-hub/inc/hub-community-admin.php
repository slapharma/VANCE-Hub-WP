<?php
/**
 * VanceHubClub Community hub: the admin screens.
 *
 *   - "Community queue" (admin.php?page=vance-community-queue), with a count
 *     badge of the posts that wait for a moderator. Tabs: Waiting, Reported,
 *     Approved, Rejected. Each row can be approved, edited in the WordPress
 *     editor, or rejected with a fixed reason.
 *   - "Weekly poll" (the vance_poll list and editor), with a box for the 3 to
 *     5 answers and the "active poll" setting. One poll is active at a time.
 *
 * Both sit in the VanceHealthHub admin menu next to "VanceHubClub". Both need
 * the vance_moderate_community capability (administrators and editors).
 *
 * Moderation is a plain form post to admin-post.php
 * (action vance_community_moderate, nonce vance_community_moderate).
 *
 * Needs inc/hub-community.php.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * Menu
 * ====================================================================== */

/**
 * Posts that wait for a moderator: waiting plus reported, questions plus replies.
 *
 * @return int
 */
function vance_community_queue_badge_count() {
	$n = 0;
	foreach ( array( 'waiting', 'reported' ) as $state ) {
		$n += vance_community_queue_count( $state );
	}
	return $n;
}

/**
 * The meta query for one tab. It follows the reader's rule in
 * vance_community_state(): an item with no state saved (a question made in the
 * WordPress editor, for example) reads as waiting, so Waiting must list it too.
 *
 * @param string $state Tab state.
 * @return array
 */
function vance_community_state_meta_query( $state ) {
	$has = array(
		'key'   => '_vance_community_state',
		'value' => $state,
	);
	if ( 'waiting' !== $state ) {
		return array( $has );
	}
	return array(
		'relation' => 'OR',
		$has,
		array(
			'key'     => '_vance_community_state',
			'compare' => 'NOT EXISTS',
		),
	);
}

/**
 * Number of questions and replies in a state.
 *
 * @param string $state Tab state.
 * @return int
 */
function vance_community_queue_count( $state ) {
	$q = new WP_Query( array(
		'post_type'      => 'vance_question',
		'post_status'    => array( 'pending', 'publish', 'draft' ),
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => vance_community_state_meta_query( $state ), // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	$replies = (int) get_comments( array(
		'type'       => 'vance_reply',
		'status'     => 'all',
		'count'      => true,
		'meta_query' => vance_community_state_meta_query( $state ), // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	return (int) $q->found_posts + $replies;
}

/**
 * Register the two menu items. Priority 1001: the parent menu is added at 999
 * and the VanceHubClub item at 1000, so these follow it.
 */
function vance_community_admin_menu() {
	$cap   = vance_community_cap();
	$label = __( 'Community queue', 'vance-health-hub' );
	if ( current_user_can( $cap ) ) {
		$count = vance_community_queue_badge_count();
		if ( $count > 0 ) {
			$label .= ' <span class="awaiting-mod count-' . esc_attr( (string) $count ) . '"><span class="pending-count">' . esc_html( number_format_i18n( $count ) ) . '</span></span>';
		}
	}
	add_submenu_page(
		'vance-content-hub',
		__( 'Community queue', 'vance-health-hub' ),
		$label,
		$cap,
		'vance-community-queue',
		'vance_community_queue_render'
	);
	add_submenu_page(
		'vance-content-hub',
		__( 'Weekly poll', 'vance-health-hub' ),
		__( 'Weekly poll', 'vance-health-hub' ),
		$cap,
		'edit.php?post_type=vance_poll'
	);
}
add_action( 'admin_menu', 'vance_community_admin_menu', 1001 );

/**
 * Keep the VanceHealthHub menu open while a poll or question is edited.
 *
 * @param string $parent_file Parent file.
 * @return string
 */
function vance_community_admin_parent_file( $parent_file ) {
	global $submenu_file, $typenow;
	if ( 'vance_poll' === $typenow ) {
		$submenu_file = 'edit.php?post_type=vance_poll'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		return 'vance-content-hub';
	}
	if ( 'vance_question' === $typenow ) {
		$submenu_file = 'vance-community-queue'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		return 'vance-content-hub';
	}
	return $parent_file;
}
add_filter( 'parent_file', 'vance_community_admin_parent_file' );

/**
 * Admin stylesheet, only on the queue and the poll screens.
 *
 * @param string $hook Admin page hook.
 */
function vance_community_admin_enqueue( $hook ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$ours   = false !== strpos( (string) $hook, 'vance-community-queue' )
		|| ( $screen && in_array( $screen->post_type, array( 'vance_poll', 'vance_question' ), true ) );
	if ( ! $ours ) {
		return;
	}
	$dir = get_template_directory();
	wp_enqueue_style(
		'vance-hub-community-admin',
		get_template_directory_uri() . '/assets/css/hub-community-admin.css',
		array(),
		@filemtime( $dir . '/assets/css/hub-community-admin.css' ) ?: '1.0.0'
	);
}
add_action( 'admin_enqueue_scripts', 'vance_community_admin_enqueue' );

/* =========================================================================
 * Queue
 * ====================================================================== */

/**
 * The queue tabs.
 *
 * @return array<string,string>
 */
function vance_community_queue_tabs() {
	return array(
		'waiting'  => __( 'Waiting', 'vance-health-hub' ),
		'reported' => __( 'Reported', 'vance-health-hub' ),
		'approved' => __( 'Approved', 'vance-health-hub' ),
		'rejected' => __( 'Rejected', 'vance-health-hub' ),
	);
}

/**
 * The report rows of an object as "reason (count)" text.
 *
 * @param string $type 'post' or 'comment'.
 * @param int    $id   Object ID.
 * @return string
 */
function vance_community_report_summary( $type, $id ) {
	$rows    = get_metadata( $type, (int) $id, '_vance_community_report', false );
	$labels  = vance_community_report_reasons();
	$tally   = array();
	foreach ( (array) $rows as $row ) {
		$parts  = explode( '|', (string) $row );
		$reason = isset( $parts[1] ) && isset( $labels[ $parts[1] ] ) ? $labels[ $parts[1] ] : __( 'Other', 'vance-health-hub' );
		$tally[ $reason ] = isset( $tally[ $reason ] ) ? $tally[ $reason ] + 1 : 1;
	}
	$out = array();
	foreach ( $tally as $reason => $n ) {
		$out[] = $reason . ' (' . $n . ')';
	}
	return implode( ', ', $out );
}

/**
 * The rows of one tab, newest first: up to 100 questions and 100 replies.
 *
 * @param string $state Tab state.
 * @return array[]
 */
function vance_community_queue_rows( $state ) {
	$rows      = array();
	$questions = get_posts( array(
		'post_type'      => 'vance_question',
		'post_status'    => array( 'pending', 'publish', 'draft' ),
		'posts_per_page' => 100,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => vance_community_state_meta_query( $state ), // phpcs:ignore WordPress.DB.SlowDBQuery
		'no_found_rows'  => true,
	) );
	foreach ( $questions as $q ) {
		$space  = vance_community_question_space( $q->ID );
		$body   = $q->post_title . "\n" . $q->post_content;
		$rows[] = array(
			'kind'    => 'question',
			'id'      => (int) $q->ID,
			'time'    => (int) get_post_time( 'U', false, $q ),
			'title'   => $q->post_title,
			'text'    => wp_strip_all_tags( (string) $q->post_content ),
			'context' => '',
			'member'  => vance_community_display_name( (int) $q->post_author ),
			'space'   => $space ? $space->name : '',
			'product' => vance_community_names_product( $body ),
			'reports' => vance_community_report_summary( 'post', $q->ID ),
			'reason'  => (string) get_post_meta( $q->ID, '_vance_community_reject_reason', true ),
			'edit'    => (string) get_edit_post_link( $q->ID, 'raw' ),
		);
	}

	$replies = get_comments( array(
		'type'       => 'vance_reply',
		'status'     => 'all',
		'number'     => 100,
		'orderby'    => 'comment_date_gmt',
		'order'      => 'DESC',
		'meta_query' => vance_community_state_meta_query( $state ), // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	foreach ( (array) $replies as $r ) {
		$space  = vance_community_question_space( (int) $r->comment_post_ID );
		$rows[] = array(
			'kind'    => 'reply',
			'id'      => (int) $r->comment_ID,
			'time'    => (int) strtotime( (string) $r->comment_date ),
			'title'   => '',
			'text'    => wp_strip_all_tags( (string) $r->comment_content ),
			'context' => get_the_title( (int) $r->comment_post_ID ),
			'member'  => vance_community_display_name( (int) $r->user_id ),
			'space'   => $space ? $space->name : '',
			'product' => vance_community_names_product( (string) $r->comment_content ),
			'reports' => vance_community_report_summary( 'comment', $r->comment_ID ),
			'reason'  => (string) get_comment_meta( $r->comment_ID, '_vance_community_reject_reason', true ),
			'edit'    => admin_url( 'comment.php?action=editcomment&c=' . (int) $r->comment_ID ),
		);
	}

	usort( $rows, function ( $a, $b ) {
		return $b['time'] - $a['time'];
	} );
	return $rows;
}

/**
 * The current tab from the query string.
 *
 * @return string
 */
function vance_community_queue_current_tab() {
	$tab = isset( $_GET['tab'] ) && is_string( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'waiting'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab switch.
	return isset( vance_community_queue_tabs()[ $tab ] ) ? $tab : 'waiting';
}

/**
 * One moderation button form.
 *
 * @param array  $row   Queue row.
 * @param string $do    approve | reject.
 * @param string $tab   Current tab.
 * @return string
 */
function vance_community_queue_action_form( $row, $do, $tab ) {
	$html  = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="vcom-q-form">';
	$html .= '<input type="hidden" name="action" value="vance_community_moderate">';
	$html .= '<input type="hidden" name="kind" value="' . esc_attr( $row['kind'] ) . '">';
	$html .= '<input type="hidden" name="id" value="' . esc_attr( (string) $row['id'] ) . '">';
	$html .= '<input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
	$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
	$html .= wp_nonce_field( 'vance_community_moderate', '_wpnonce', true, false );
	if ( 'reject' === $do ) {
		$sid   = 'vcom-reason-' . $row['kind'] . '-' . $row['id'];
		$html .= '<label class="screen-reader-text" for="' . esc_attr( $sid ) . '">' . esc_html__( 'Reason', 'vance-health-hub' ) . '</label>';
		$html .= '<select id="' . esc_attr( $sid ) . '" name="reason" required><option value="">' . esc_html__( 'Reason', 'vance-health-hub' ) . '</option>';
		foreach ( vance_community_reject_reasons() as $key => $label ) {
			$html .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</option>';
		}
		$html .= '</select> ';
		$html .= '<button type="submit" class="button">' . esc_html__( 'Reject', 'vance-health-hub' ) . '</button>';
	} else {
		$html .= '<button type="submit" class="button button-primary">' . esc_html__( 'Approve', 'vance-health-hub' ) . '</button>';
	}
	$html .= '</form>';
	return $html;
}

/**
 * The queue screen.
 */
function vance_community_queue_render() {
	if ( ! current_user_can( vance_community_cap() ) ) {
		return;
	}
	$tab     = vance_community_queue_current_tab();
	$rows    = vance_community_queue_rows( $tab );
	$reasons = vance_community_reject_reasons();
	$notice  = isset( $_GET['vcom_done'] ) && is_string( $_GET['vcom_done'] ) ? sanitize_key( wp_unslash( $_GET['vcom_done'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$base    = admin_url( 'admin.php?page=vance-community-queue' );
	?>
	<div class="wrap vcom-queue">
		<h1><?php esc_html_e( 'Community queue', 'vance-health-hub' ); ?></h1>
		<?php if ( ! vance_hub_sanitize_checkbox( vance_get_theme_mod( 'vance_hub_community', false ) ) ) : ?>
			<div class="notice notice-info"><p><?php esc_html_e( 'The Community hub switch is off. Visitors and members cannot see the community page.', 'vance-health-hub' ); ?></p></div>
		<?php endif; ?>
		<?php if ( 'approved' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Approved. The post shows on the community page.', 'vance-health-hub' ); ?></p></div>
		<?php elseif ( 'rejected' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Rejected. The member sees the reason on the community page.', 'vance-health-hub' ); ?></p></div>
		<?php elseif ( 'error' === $notice ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The change was not saved. Choose a reason to reject, then try again.', 'vance-health-hub' ); ?></p></div>
		<?php endif; ?>

		<p><?php esc_html_e( 'Members share experience. They do not give medical advice. Approve a post only when it follows the rules.', 'vance-health-hub' ); ?></p>

		<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Queue tabs', 'vance-health-hub' ); ?>">
			<?php foreach ( vance_community_queue_tabs() as $key => $label ) : ?>
				<a class="nav-tab<?php echo $key === $tab ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', $key, $base ) ); ?>"<?php echo $key === $tab ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $label ); ?> <span class="count">(<?php echo esc_html( number_format_i18n( vance_community_queue_count( $key ) ) ); ?>)</span>
				</a>
			<?php endforeach; ?>
		</nav>

		<?php if ( ! $rows ) : ?>
			<p class="vcom-queue__empty"><?php esc_html_e( 'Nothing here.', 'vance-health-hub' ); ?></p>
		<?php else : ?>
			<table class="widefat striped vcom-queue__table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Type', 'vance-health-hub' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Post', 'vance-health-hub' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Member', 'vance-health-hub' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Space', 'vance-health-hub' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'vance-health-hub' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td>
							<?php echo esc_html( 'reply' === $row['kind'] ? __( 'Reply', 'vance-health-hub' ) : __( 'Question', 'vance-health-hub' ) ); ?>
							<br><span class="vcom-queue__date"><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['time'] ) ); ?></span>
						</td>
						<td class="vcom-queue__post">
							<?php if ( '' !== $row['title'] ) : ?>
								<strong><?php echo esc_html( $row['title'] ); ?></strong>
							<?php endif; ?>
							<?php if ( '' !== $row['context'] ) : ?>
								<span class="vcom-queue__context"><?php
									/* translators: %s: question title. */
									echo esc_html( sprintf( __( 'On: %s', 'vance-health-hub' ), $row['context'] ) );
								?></span>
							<?php endif; ?>
							<?php if ( '' !== trim( $row['text'] ) ) : ?>
								<p class="vcom-queue__text"><?php echo esc_html( $row['text'] ); ?></p>
							<?php endif; ?>
							<p class="vcom-queue__flags">
								<?php if ( $row['product'] ) : ?>
									<span class="vcom-flag vcom-flag--product"><?php esc_html_e( 'Names a product', 'vance-health-hub' ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== $row['reports'] ) : ?>
									<span class="vcom-flag vcom-flag--report"><?php
										/* translators: %s: report reasons with counts. */
										echo esc_html( sprintf( __( 'Reported: %s', 'vance-health-hub' ), $row['reports'] ) );
									?></span>
								<?php endif; ?>
								<?php if ( 'rejected' === $tab && isset( $reasons[ $row['reason'] ] ) ) : ?>
									<span class="vcom-flag"><?php
										/* translators: %s: reject reason. */
										echo esc_html( sprintf( __( 'Reason: %s', 'vance-health-hub' ), $reasons[ $row['reason'] ] ) );
									?></span>
								<?php endif; ?>
							</p>
						</td>
						<td><?php echo esc_html( $row['member'] ); ?></td>
						<td><?php echo esc_html( $row['space'] ); ?></td>
						<td class="vcom-queue__actions">
							<?php
							if ( 'approved' !== $tab ) {
								echo vance_community_queue_action_form( $row, 'approve', $tab ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the builder.
							}
							if ( '' !== $row['edit'] ) {
								echo '<a class="button" href="' . esc_url( $row['edit'] ) . '">' . esc_html__( 'Edit', 'vance-health-hub' ) . '</a>';
							}
							if ( 'rejected' !== $tab ) {
								echo vance_community_queue_action_form( $row, 'reject', $tab ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the builder.
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Handle Approve and Reject from the queue.
 */
function vance_community_moderate_handler() {
	if ( ! current_user_can( vance_community_cap() ) ) {
		wp_die( esc_html__( 'You cannot moderate the community.', 'vance-health-hub' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'vance_community_moderate' );

	$kind   = ( isset( $_POST['kind'] ) && 'reply' === $_POST['kind'] ) ? 'reply' : 'question';
	$id     = isset( $_POST['id'] ) && is_string( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$do     = isset( $_POST['do'] ) && is_string( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
	$reason = isset( $_POST['reason'] ) && is_string( $_POST['reason'] ) ? sanitize_key( wp_unslash( $_POST['reason'] ) ) : '';
	$tab    = isset( $_POST['tab'] ) && is_string( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'waiting';
	if ( ! isset( vance_community_queue_tabs()[ $tab ] ) ) {
		$tab = 'waiting';
	}

	$done = 'error';
	if ( $id > 0 && 'approve' === $do ) {
		$done = vance_community_set_state( $kind, $id, 'approved' ) ? 'approved' : 'error';
	} elseif ( $id > 0 && 'reject' === $do && isset( vance_community_reject_reasons()[ $reason ] ) ) {
		$done = vance_community_set_state( $kind, $id, 'rejected', $reason ) ? 'rejected' : 'error';
	}

	wp_safe_redirect( add_query_arg( array(
		'page'      => 'vance-community-queue',
		'tab'       => $tab,
		'vcom_done' => $done,
	), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_vance_community_moderate', 'vance_community_moderate_handler' );

/* =========================================================================
 * Weekly poll editor
 * ====================================================================== */

/**
 * Add the answers box to the poll editor.
 */
function vance_community_poll_meta_box() {
	add_meta_box(
		'vance-community-poll',
		__( 'Answers and status', 'vance-health-hub' ),
		'vance_community_poll_meta_box_render',
		'vance_poll',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_vance_poll', 'vance_community_poll_meta_box' );

/**
 * True when anyone has voted in the poll.
 *
 * @param int $poll_id Poll ID.
 * @return bool
 */
function vance_community_poll_has_votes( $poll_id ) {
	return array_sum( vance_community_poll_counts( $poll_id ) ) > 0;
}

/**
 * The answers box.
 *
 * @param WP_Post $post Poll.
 */
function vance_community_poll_meta_box_render( $post ) {
	$stored  = get_post_meta( $post->ID, '_vance_community_poll_answers', true );
	$stored  = is_array( $stored ) ? array_values( $stored ) : array();
	$active  = '1' === (string) get_post_meta( $post->ID, '_vance_community_poll_active', true );
	$locked  = vance_community_poll_has_votes( $post->ID );
	$counts  = vance_community_poll_counts( $post->ID );
	wp_nonce_field( 'vance_poll_meta', 'vance_poll_meta_nonce' );
	?>
	<p><?php esc_html_e( 'The poll title is the question members see. Give 3 to 5 short answers.', 'vance-health-hub' ); ?></p>
	<?php if ( $locked ) : ?>
		<p class="vcom-poll-admin__locked"><?php esc_html_e( 'Members have voted, so the answers cannot change. Make a new poll to ask something else.', 'vance-health-hub' ); ?></p>
	<?php endif; ?>
	<table class="form-table" role="presentation">
		<tbody>
		<?php for ( $i = 0; $i < 5; $i++ ) : ?>
			<tr>
				<th scope="row"><label for="vcom-poll-answer-<?php echo esc_attr( (string) $i ); ?>"><?php
					/* translators: %d: answer number. */
					echo esc_html( sprintf( __( 'Answer %d', 'vance-health-hub' ), $i + 1 ) );
				?></label></th>
				<td>
					<input type="text" class="regular-text" id="vcom-poll-answer-<?php echo esc_attr( (string) $i ); ?>" name="vance_poll_answers[]" maxlength="80" value="<?php echo esc_attr( isset( $stored[ $i ] ) ? (string) $stored[ $i ] : '' ); ?>"<?php disabled( $locked ); ?>>
					<?php if ( $locked && isset( $stored[ $i ] ) ) : ?>
						<span class="vcom-poll-admin__votes"><?php
							/* translators: %s: number of votes. */
							echo esc_html( sprintf( _n( '%s vote', '%s votes', isset( $counts[ $i ] ) ? $counts[ $i ] : 0, 'vance-health-hub' ), number_format_i18n( isset( $counts[ $i ] ) ? $counts[ $i ] : 0 ) ) );
						?></span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endfor; ?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Active poll', 'vance-health-hub' ); ?></th>
			<td>
				<label><input type="checkbox" name="vance_poll_active" value="1"<?php checked( $active ); ?>> <?php esc_html_e( 'Show this poll on the community page', 'vance-health-hub' ); ?></label>
				<p class="description"><?php esc_html_e( 'Only one poll is active. When you make this poll active, the poll that was active closes and keeps its result. The poll must also be published.', 'vance-health-hub' ); ?></p>
			</td>
		</tr>
		</tbody>
	</table>
	<?php
}

/**
 * Save the answers and the active flag.
 *
 * @param int     $post_id Poll ID.
 * @param WP_Post $post    Poll.
 */
function vance_community_poll_save( $post_id, $post ) {
	if ( ! isset( $_POST['vance_poll_meta_nonce'] ) || ! is_string( $_POST['vance_poll_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vance_poll_meta_nonce'] ) ), 'vance_poll_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( vance_community_cap() ) ) {
		return;
	}

	$notice = '';
	if ( ! vance_community_poll_has_votes( $post_id ) ) {
		$raw     = isset( $_POST['vance_poll_answers'] ) && is_array( $_POST['vance_poll_answers'] ) ? wp_unslash( $_POST['vance_poll_answers'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each item sanitised below.
		$answers = array();
		foreach ( array_slice( $raw, 0, 5 ) as $answer ) {
			$answer = vance_community_plain_text( is_string( $answer ) ? $answer : '', 80, false );
			if ( '' !== $answer ) {
				$answers[] = $answer;
			}
		}
		update_post_meta( $post_id, '_vance_community_poll_answers', $answers );
	}
	$has_answers = count( vance_community_poll_answers( $post_id ) ) >= 3;

	$want   = ! empty( $_POST['vance_poll_active'] );
	$was    = '1' === (string) get_post_meta( $post_id, '_vance_community_poll_active', true );
	$can_on = $has_answers && 'publish' === $post->post_status;

	if ( $want && $can_on ) {
		// Close every other active poll. Its votes stay, so its result is kept.
		$others = get_posts( array(
			'post_type'      => 'vance_poll',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post__not_in'   => array( $post_id ),
			'meta_key'       => '_vance_community_poll_active', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
		) );
		foreach ( $others as $other ) {
			delete_post_meta( (int) $other, '_vance_community_poll_active' );
			update_post_meta( (int) $other, '_vance_community_poll_closed', time() );
		}
		update_post_meta( $post_id, '_vance_community_poll_active', '1' );
		delete_post_meta( $post_id, '_vance_community_poll_closed' );
	} else {
		if ( $was ) {
			delete_post_meta( $post_id, '_vance_community_poll_active' );
			update_post_meta( $post_id, '_vance_community_poll_closed', time() );
		}
		if ( $want ) {
			$notice = $has_answers ? 'publish' : 'answers';
		}
	}

	if ( '' !== $notice ) {
		set_transient( 'vance_poll_notice_' . get_current_user_id(), $notice, 60 );
	}
}
add_action( 'save_post_vance_poll', 'vance_community_poll_save', 10, 2 );

/**
 * A poll that is moved to the bin or unpublished stops being active.
 *
 * @param string  $new  New status.
 * @param string  $old  Old status.
 * @param WP_Post $post Post.
 */
function vance_community_poll_status_change( $new, $old, $post ) {
	if ( $post instanceof WP_Post && 'vance_poll' === $post->post_type && 'publish' === $old && 'publish' !== $new ) {
		if ( '1' === (string) get_post_meta( $post->ID, '_vance_community_poll_active', true ) ) {
			delete_post_meta( $post->ID, '_vance_community_poll_active' );
			update_post_meta( $post->ID, '_vance_community_poll_closed', time() );
		}
	}
}
add_action( 'transition_post_status', 'vance_community_poll_status_change', 10, 3 );

/**
 * Show why a poll could not be made active.
 */
function vance_community_poll_admin_notice() {
	$key    = 'vance_poll_notice_' . get_current_user_id();
	$notice = get_transient( $key );
	if ( ! $notice ) {
		return;
	}
	delete_transient( $key );
	$text = 'answers' === $notice
		? __( 'The poll is not active. Give at least 3 answers first.', 'vance-health-hub' )
		: __( 'The poll is not active. Publish it first, then make it active.', 'vance-health-hub' );
	echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
}
add_action( 'admin_notices', 'vance_community_poll_admin_notice' );

/**
 * Poll list columns.
 *
 * @param array $columns Columns.
 * @return array
 */
function vance_community_poll_columns( $columns ) {
	$out = array();
	foreach ( $columns as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'title' === $key ) {
			$out['vcom_active'] = __( 'Active', 'vance-health-hub' );
			$out['vcom_votes']  = __( 'Votes', 'vance-health-hub' );
		}
	}
	return $out;
}
add_filter( 'manage_vance_poll_posts_columns', 'vance_community_poll_columns' );

/**
 * Poll list column values.
 *
 * @param string $column  Column key.
 * @param int    $post_id Poll ID.
 */
function vance_community_poll_column_value( $column, $post_id ) {
	if ( 'vcom_active' === $column ) {
		echo esc_html( '1' === (string) get_post_meta( $post_id, '_vance_community_poll_active', true ) ? __( 'Yes', 'vance-health-hub' ) : '' );
	} elseif ( 'vcom_votes' === $column ) {
		echo esc_html( number_format_i18n( array_sum( vance_community_poll_counts( $post_id ) ) ) );
	}
}
add_action( 'manage_vance_poll_posts_custom_column', 'vance_community_poll_column_value', 10, 2 );
