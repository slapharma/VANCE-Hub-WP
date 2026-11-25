<?php
/**
 * VanceHubClub Community hub: the page markup.
 *
 * Called from page-community.php. Logic and data: inc/hub-community.php.
 *
 * THE VISITOR RULE. A visitor (not logged in) gets question titles, question
 * text and the FIRST approved reply of each question. The other replies are
 * never fetched for a visitor, so their text cannot reach the browser: the
 * card shows grey bars and "[count] more replies for members" instead. Every
 * action button is a link to the join page for a visitor.
 *
 * THE DATA RULE. A block with no real data does not render: no active poll
 * means no poll block, no posts of your own means no "Your posts" block.
 *
 * Every value is escaped where it is printed. Member text is plain text: it is
 * printed with esc_html() and never turned into links.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The arrow used after link text.
 *
 * @return string
 */
function vance_community_arrow() {
	return ' <span aria-hidden="true">&rarr;</span>';
}

/**
 * A time as "3 hours ago".
 *
 * @param int $timestamp Unix time.
 * @return string
 */
function vance_community_ago( $timestamp ) {
	$timestamp = (int) $timestamp;
	if ( $timestamp <= 0 ) {
		return '';
	}
	/* translators: %s: a time span such as "3 hours". */
	return sprintf( __( '%s ago', 'vance-health-hub' ), human_time_diff( $timestamp, time() ) );
}

/**
 * The first letter of a name, for the round avatar.
 *
 * @param string $name Display name.
 * @return string
 */
function vance_community_initial( $name ) {
	$name = trim( (string) $name );
	if ( '' === $name ) {
		return '?';
	}
	return function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $name, 0, 1 ) ) : strtoupper( substr( $name, 0, 1 ) );
}

/**
 * The space filter from the query string: a known space slug, or ''.
 *
 * @param WP_Term[] $spaces vance_community_space_terms().
 * @return string
 */
function vance_community_current_space( $spaces ) {
	$raw  = isset( $_GET['space'] ) && is_string( $_GET['space'] ) ? sanitize_key( wp_unslash( $_GET['space'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter.
	return isset( $spaces[ $raw ] ) ? $raw : '';
}

/**
 * The feed page number from the query string.
 *
 * @return int
 */
function vance_community_current_page() {
	$raw = isset( $_GET['vcpage'] ) && is_string( $_GET['vcpage'] ) ? absint( wp_unslash( $_GET['vcpage'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pager.
	return max( 1, min( 500, $raw ) );
}

/**
 * The address of the feed with a space and a page.
 *
 * @param string $space Space slug or ''.
 * @param int    $page  Page number.
 * @return string
 */
function vance_community_feed_url( $space, $page = 1 ) {
	$args = array();
	if ( '' !== $space ) {
		$args['space'] = $space;
	}
	if ( $page > 1 ) {
		$args['vcpage'] = (int) $page;
	}
	$url = vance_community_page_url();
	return $args ? add_query_arg( $args, $url ) : $url;
}

/**
 * Join-first link that looks like an action button (for visitors).
 *
 * @param string $label Visible text.
 * @param string $class Extra class.
 * @return string
 */
function vance_community_join_link( $label, $class = '' ) {
	return '<a class="vcom-btn ' . esc_attr( $class ) . '" href="' . esc_url( vance_community_join_url() ) . '">' . esc_html( $label ) . '</a>';
}

/**
 * The report control. Members get a small form; visitors a join link.
 *
 * @param string $kind   'question' or 'reply'.
 * @param int    $id     Post or comment ID.
 * @param bool   $member Viewer is a member.
 * @return string
 */
function vance_community_report_html( $kind, $id, $member ) {
	if ( ! $member ) {
		return '<a class="vcom-report__link" href="' . esc_url( vance_community_join_url() ) . '">' . esc_html__( 'Report', 'vance-health-hub' ) . '</a>';
	}
	$fid   = 'vcom-rep-' . ( 'reply' === $kind ? 'r' : 'q' ) . (int) $id;
	$html  = '<details class="vcom-report"><summary class="vcom-report__link">' . esc_html__( 'Report', 'vance-health-hub' ) . '</summary>';
	$html .= '<form class="vcom-report__form" data-vcom-form="report">';
	$html .= '<input type="hidden" name="kind" value="' . esc_attr( 'reply' === $kind ? 'reply' : 'question' ) . '">';
	$html .= '<input type="hidden" name="id" value="' . esc_attr( (string) (int) $id ) . '">';
	$html .= '<label for="' . esc_attr( $fid ) . '">' . esc_html__( 'Why do you report this post?', 'vance-health-hub' ) . '</label>';
	$html .= '<select id="' . esc_attr( $fid ) . '" name="reason" required><option value="">' . esc_html__( 'Choose a reason', 'vance-health-hub' ) . '</option>';
	foreach ( vance_community_report_reasons() as $key => $label ) {
		$html .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</option>';
	}
	$html .= '</select>';
	$html .= '<button type="submit" class="vcom-btn vcom-btn--navy">' . esc_html__( 'Send the report', 'vance-health-hub' ) . '</button>';
	$html .= '<p class="vcom-msg" role="status" data-vcom-msg></p>';
	$html .= '</form></details>';
	return $html;
}

/**
 * One approved reply.
 *
 * @param WP_Comment $reply   Reply.
 * @param bool       $member  Viewer is a member.
 * @param int        $user_id Viewer's ID (0 for a visitor).
 * @return string
 */
function vance_community_reply_html( $reply, $member, $user_id ) {
	$id   = (int) $reply->comment_ID;
	$html = '<div class="vcom-reply" id="vcom-r-' . esc_attr( (string) $id ) . '" data-vcom-item>';
	$html .= '<p class="vcom-reply__text"><strong>' . esc_html( vance_community_author_name( 'reply', $id, (int) $reply->user_id ) ) . '</strong> <span aria-hidden="true">&middot;</span> ' . esc_html( wp_strip_all_tags( (string) $reply->comment_content ) ) . '</p>';
	$count = vance_community_meta_rows( 'comment', $id, '_vance_community_helpful' );
	$html .= '<div class="vcom-reply__actions">';
	if ( $member ) {
		$on    = vance_community_has_row( 'comment', $id, '_vance_community_helpful', $user_id );
		$html .= '<button type="button" class="vcom-chip-btn" data-vcom-toggle="helpful" data-id="' . esc_attr( (string) $id ) . '" aria-pressed="' . ( $on ? 'true' : 'false' ) . '">'
			. esc_html__( 'Helpful', 'vance-health-hub' ) . ' <span aria-hidden="true">&middot;</span> <span data-vcom-count>' . esc_html( number_format_i18n( $count ) ) . '</span></button>';
	} else {
		$html .= '<a class="vcom-chip-btn" href="' . esc_url( vance_community_join_url() ) . '">' . esc_html__( 'Helpful', 'vance-health-hub' ) . ' <span aria-hidden="true">&middot;</span> ' . esc_html( number_format_i18n( $count ) ) . '</a>';
	}
	$html .= vance_community_report_html( 'reply', $id, $member );
	$html .= '</div></div>';
	return $html;
}

/**
 * One question card.
 *
 * @param WP_Post $q       Question.
 * @param bool    $member  Viewer is a member.
 * @param int     $user_id Viewer's ID (0 for a visitor).
 * @return string
 */
function vance_community_card_html( $q, $member, $user_id ) {
	$id     = (int) $q->ID;
	$name   = vance_community_author_name( 'question', $id, (int) $q->post_author );
	$space  = vance_community_question_space( $id );
	$tone   = $space ? vance_community_space_tone( $space->slug ) : 'teal';
	$text   = trim( wp_strip_all_tags( (string) $q->post_content ) );
	$total  = vance_community_reply_count( $id );
	$metoo  = vance_community_meta_rows( 'post', $id, '_vance_community_metoo' );
	$arrow  = vance_community_arrow();

	// Visitors: only the first reply is fetched. Members: all of them.
	$replies = $total > 0 ? vance_community_replies( $id, $member ? 0 : 1 ) : array();
	$first   = $replies ? array_shift( $replies ) : null;
	$more    = max( 0, $total - ( $first ? 1 : 0 ) );

	$html  = '<article class="vcom-card vcom-card--' . esc_attr( $tone ) . '" id="vcom-q-' . esc_attr( (string) $id ) . '" data-vcom-item aria-labelledby="vcom-q-title-' . esc_attr( (string) $id ) . '">';
	$html .= '<div class="vcom-card__meta">';
	$html .= '<span class="vcom-avatar vcom-avatar--' . esc_attr( $tone ) . '" aria-hidden="true">' . esc_html( vance_community_initial( $name ) ) . '</span>';
	$html .= '<span class="vcom-card__name">' . esc_html( $name ) . '</span>';
	$html .= '<span class="vcom-card__time">' . esc_html( vance_community_ago( (int) get_post_time( 'U', true, $q ) ) ) . '</span>';
	if ( $space ) {
		$html .= '<span class="vcom-badge vcom-badge--' . esc_attr( $tone ) . '">' . esc_html( $space->name ) . '</span>';
	}
	$html .= '</div>';
	$html .= '<h2 class="vcom-card__title" id="vcom-q-title-' . esc_attr( (string) $id ) . '">' . esc_html( get_the_title( $q ) ) . '</h2>';
	if ( '' !== $text ) {
		$html .= '<p class="vcom-card__text">' . esc_html( $text ) . '</p>';
	}

	if ( $first ) {
		$html .= '<div class="vcom-replies" id="vcom-replies-' . esc_attr( (string) $id ) . '">';
		$html .= vance_community_reply_html( $first, $member, $user_id );
		if ( $more > 0 ) {
			if ( $member ) {
				$html .= '<details class="vcom-more"><summary>' . esc_html( sprintf(
					/* translators: %s: number of replies. */
					_n( 'Show %s more reply', 'Show %s more replies', $more, 'vance-health-hub' ),
					number_format_i18n( $more )
				) ) . '</summary>';
				foreach ( $replies as $reply ) {
					$html .= vance_community_reply_html( $reply, $member, $user_id );
				}
				$html .= '</details>';
			} else {
				// Grey bars only. The text of these replies is not on the server's
				// side of this response at all: vance_community_replies() fetched
				// one reply.
				$html .= '<div class="vcom-locked" aria-hidden="true">';
				for ( $i = 0; $i < min( 3, $more ); $i++ ) {
					$html .= '<span class="vcom-locked__bar"></span>';
				}
				$html .= '</div>';
				$html .= '<div class="vcom-locked__foot"><p class="vcom-locked__count">' . esc_html( sprintf(
					/* translators: %s: number of replies. */
					_n( '%s more reply for members', '%s more replies for members', $more, 'vance-health-hub' ),
					number_format_i18n( $more )
				) ) . '</p>';
				$html .= '<a class="vcom-btn vcom-btn--purple" href="' . esc_url( vance_community_join_url() ) . '">' . esc_html__( 'Join free to read', 'vance-health-hub' ) . $arrow . '</a></div>';
			}
		}
		$html .= '</div>';
	}

	// Actions.
	$html .= '<div class="vcom-card__actions">';
	if ( $member ) {
		$on    = vance_community_has_row( 'post', $id, '_vance_community_metoo', $user_id );
		$html .= '<button type="button" class="vcom-btn vcom-btn--purple" data-vcom-toggle="metoo" data-id="' . esc_attr( (string) $id ) . '" aria-pressed="' . ( $on ? 'true' : 'false' ) . '">'
			. esc_html__( 'Me too', 'vance-health-hub' ) . ' <span aria-hidden="true">&middot;</span> <span data-vcom-count>' . esc_html( number_format_i18n( $metoo ) ) . '</span></button>';
	} else {
		$html .= '<a class="vcom-btn vcom-btn--purple" href="' . esc_url( vance_community_join_url() ) . '">' . esc_html__( 'Me too', 'vance-health-hub' ) . ' <span aria-hidden="true">&middot;</span> ' . esc_html( number_format_i18n( $metoo ) ) . '</a>';
	}
	if ( $total > 0 ) {
		$html .= '<a class="vcom-btn vcom-btn--wash" href="#vcom-replies-' . esc_attr( (string) $id ) . '">' . esc_html( sprintf(
			/* translators: %s: number of replies. */
			_n( '%s reply', '%s replies', $total, 'vance-health-hub' ),
			number_format_i18n( $total )
		) ) . '</a>';
	}
	if ( $member ) {
		$rid   = 'vcom-reply-text-' . $id;
		$html .= '<details class="vcom-write"><summary class="vcom-btn vcom-btn--outline">' . esc_html__( 'Write a reply', 'vance-health-hub' ) . '</summary>';
		$html .= '<form class="vcom-write__form" data-vcom-form="reply">';
		$html .= '<input type="hidden" name="question" value="' . esc_attr( (string) $id ) . '">';
		$html .= '<label for="' . esc_attr( $rid ) . '">' . esc_html__( 'Your reply', 'vance-health-hub' ) . '</label>';
		$html .= '<textarea id="' . esc_attr( $rid ) . '" name="text" rows="4" required minlength="' . esc_attr( (string) vance_community_limit( 'reply_min' ) ) . '" maxlength="' . esc_attr( (string) vance_community_limit( 'reply_max' ) ) . '"></textarea>';
		$html .= '<p class="vcom-note">' . esc_html__( 'Share your experience. Do not give medical advice. A moderator reads your reply before it shows.', 'vance-health-hub' ) . '</p>';
		$html .= '<button type="submit" class="vcom-btn vcom-btn--purple">' . esc_html__( 'Send my reply', 'vance-health-hub' ) . '</button>';
		$html .= '<p class="vcom-msg" role="status" data-vcom-msg></p>';
		$html .= '</form></details>';
	} else {
		$html .= '<a class="vcom-btn vcom-btn--outline" href="' . esc_url( vance_community_join_url() ) . '">' . esc_html__( 'Reply', 'vance-health-hub' ) . '</a>';
	}
	$html .= vance_community_report_html( 'question', $id, $member );
	$html .= '</div>';

	$html .= '</article>';
	return $html;
}

/**
 * The weekly poll block, or '' when the poll has fewer than three answers.
 *
 * Before a member votes, the answers are buttons. After the vote, the block
 * shows the share of each answer and "Your vote". A visitor's answers are join
 * links. The result is never shown before a vote.
 *
 * @param WP_Post $poll    Poll.
 * @param int     $user_id Viewer's ID (0 for a visitor).
 * @param bool    $member  Viewer is a member.
 * @return string
 */
function vance_community_poll_html( $poll, $user_id, $member ) {
	if ( ! $poll instanceof WP_Post ) {
		return '';
	}
	$answers = vance_community_poll_answers( $poll->ID );
	if ( ! $answers ) {
		return '';
	}
	$pid    = (int) $poll->ID;
	$vote   = $member ? vance_community_user_vote( $user_id, $pid ) : -1;
	$shares = vance_community_poll_shares( vance_community_poll_counts( $pid ), count( $answers ) );

	$html  = '<section class="vcom-poll" data-vcom-poll="' . esc_attr( (string) $pid ) . '" aria-labelledby="vcom-poll-title">';
	$html .= '<p class="vcom-eyebrow">' . esc_html__( "THIS WEEK'S QUESTION", 'vance-health-hub' ) . '</p>';
	$html .= '<h2 class="vcom-poll__title" id="vcom-poll-title">' . esc_html( get_the_title( $poll ) ) . '</h2>';

	if ( $member && $vote >= 0 && isset( $answers[ $vote ] ) ) {
		$html .= '<ul class="vcom-poll__results">';
		foreach ( $answers as $i => $answer ) {
			$share = (int) $shares['rows'][ $i ]['share'];
			$mine  = $i === $vote;
			$html .= '<li class="vcom-poll__result' . ( $mine ? ' is-mine' : '' ) . '" style="--vcom-share:' . esc_attr( (string) $share ) . '%">';
			$html .= '<span class="vcom-poll__answer">' . esc_html( $answer ) . ( $mine ? ' <em>' . esc_html__( '(your vote)', 'vance-health-hub' ) . '</em>' : '' ) . '</span>';
			$html .= '<span class="vcom-poll__share">' . esc_html( $share . '%' ) . '</span>';
			$html .= '</li>';
		}
		$html .= '</ul>';
		$html .= '<p class="vcom-poll__count">' . esc_html( sprintf(
			/* translators: %s: number of members who voted. */
			_n( '%s member voted.', '%s members voted.', $shares['total'], 'vance-health-hub' ),
			number_format_i18n( $shares['total'] )
		) ) . '</p>';
	} else {
		$html .= '<div class="vcom-poll__options">';
		foreach ( $answers as $i => $answer ) {
			if ( $member ) {
				$html .= '<button type="button" class="vcom-poll__opt" data-vcom-vote="' . esc_attr( (string) $i ) . '">' . esc_html( $answer ) . '</button>';
			} else {
				$html .= '<a class="vcom-poll__opt" href="' . esc_url( vance_community_join_url() ) . '">' . esc_html( $answer ) . '</a>';
			}
		}
		$html .= '</div>';
		if ( $shares['total'] > 0 ) {
			$line = sprintf(
				/* translators: %s: number of members who voted. */
				_n( '%s member voted. Vote to see the result.', '%s members voted. Vote to see the result.', $shares['total'], 'vance-health-hub' ),
				number_format_i18n( $shares['total'] )
			);
		} else {
			$line = __( 'Vote to see the result.', 'vance-health-hub' );
		}
		if ( ! $member ) {
			$line .= ' ' . __( 'Join free to vote.', 'vance-health-hub' );
		}
		$html .= '<p class="vcom-poll__count">' . esc_html( $line ) . '</p>';
	}
	$html .= '<p class="vcom-msg vcom-msg--light" role="status" data-vcom-msg></p>';
	$html .= '</section>';
	return $html;
}

/**
 * The member's own questions and replies with their state (latest ten).
 * Returns '' when there are none.
 *
 * @param int $user_id Member ID.
 * @return string
 */
function vance_community_my_posts_html( $user_id ) {
	$rows = array();

	$questions = get_posts( array(
		'post_type'      => 'vance_question',
		'post_status'    => array( 'pending', 'publish', 'draft' ),
		'author'         => (int) $user_id,
		'posts_per_page' => 10,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	) );
	foreach ( $questions as $q ) {
		$rows[] = array(
			'time'   => (int) get_post_time( 'U', false, $q ),
			'type'   => __( 'Question', 'vance-health-hub' ),
			'text'   => get_the_title( $q ),
			'state'  => vance_community_state( 'question', $q->ID ),
			'reason' => (string) get_post_meta( $q->ID, '_vance_community_reject_reason', true ),
		);
	}
	$replies = get_comments( array(
		'user_id' => (int) $user_id,
		'type'    => 'vance_reply',
		'status'  => 'all',
		'number'  => 10,
		'orderby' => 'comment_date_gmt',
		'order'   => 'DESC',
	) );
	foreach ( (array) $replies as $r ) {
		$rows[] = array(
			'time'   => (int) strtotime( (string) $r->comment_date ),
			'type'   => __( 'Reply', 'vance-health-hub' ),
			'text'   => wp_html_excerpt( wp_strip_all_tags( (string) $r->comment_content ), 80, '...' ),
			'state'  => vance_community_state( 'reply', $r->comment_ID ),
			'reason' => (string) get_comment_meta( $r->comment_ID, '_vance_community_reject_reason', true ),
		);
	}
	if ( ! $rows ) {
		return '';
	}
	usort( $rows, function ( $a, $b ) {
		return $b['time'] - $a['time'];
	} );
	$rows = array_slice( $rows, 0, 10 );

	$labels  = array(
		'waiting'  => __( 'Waiting for a moderator', 'vance-health-hub' ),
		'approved' => __( 'Live', 'vance-health-hub' ),
		'reported' => __( 'Hidden after a report', 'vance-health-hub' ),
		'rejected' => __( 'Not published', 'vance-health-hub' ),
	);
	$reasons = vance_community_reject_reasons();

	$html  = '<section class="vcom-box vcom-mine" aria-labelledby="vcom-mine-title">';
	$html .= '<h2 class="vcom-eyebrow vcom-eyebrow--purple" id="vcom-mine-title">' . esc_html__( 'YOUR POSTS', 'vance-health-hub' ) . '</h2>';
	$html .= '<ul class="vcom-mine__list">';
	foreach ( $rows as $row ) {
		$state = $row['state'];
		$html .= '<li class="vcom-mine__item">';
		$html .= '<span class="vcom-mine__type">' . esc_html( $row['type'] ) . '</span> ';
		$html .= '<span class="vcom-mine__text">' . esc_html( $row['text'] ) . '</span>';
		$html .= '<span class="vcom-mine__state vcom-mine__state--' . esc_attr( $state ) . '">' . esc_html( $labels[ $state ] );
		if ( 'rejected' === $state && isset( $reasons[ $row['reason'] ] ) ) {
			$html .= ': ' . esc_html( $reasons[ $row['reason'] ] );
		}
		$html .= '</span></li>';
	}
	$html .= '</ul></section>';
	return $html;
}

/**
 * The ask form (members) or the join bar (visitors).
 *
 * @param bool      $member  Viewer is a member.
 * @param int       $user_id Viewer's ID.
 * @param WP_Term[] $spaces  Space terms.
 * @param string    $current Current space slug.
 * @return string
 */
function vance_community_ask_html( $member, $user_id, $spaces, $current ) {
	$arrow = vance_community_arrow();
	if ( ! $member ) {
		$html  = '<div class="vcom-ask">';
		$html .= '<p class="vcom-ask__label">' . esc_html__( 'Ask the community', 'vance-health-hub' ) . '</p>';
		$html .= '<a class="vcom-ask__bar vcom-ask__bar--link" href="' . esc_url( vance_community_join_url() ) . '">';
		$html .= '<span class="vcom-ask__fake">' . esc_html__( 'Type your question', 'vance-health-hub' ) . '</span>';
		$html .= '<span class="vcom-btn vcom-btn--purple">' . esc_html__( 'Join free to ask', 'vance-health-hub' ) . $arrow . '</span></a>';
		$html .= '<p class="vcom-ask__signin">' . esc_html__( 'Already a member?', 'vance-health-hub' ) . ' <a href="' . esc_url( vance_community_signin_url() ) . '">' . esc_html__( 'Sign in', 'vance-health-hub' ) . '</a></p>';
		$html .= '</div>';
		return $html;
	}

	$limits = vance_community_limits();
	$html   = '<form class="vcom-ask" data-vcom-form="ask">';
	$html  .= '<label class="vcom-ask__label" for="vcom-ask-title">' . esc_html__( 'Ask the community', 'vance-health-hub' ) . '</label>';
	$html  .= '<div class="vcom-ask__bar">';
	$html  .= '<input id="vcom-ask-title" type="text" name="title" required minlength="' . esc_attr( (string) $limits['title_min'] ) . '" maxlength="' . esc_attr( (string) $limits['title_max'] ) . '" placeholder="' . esc_attr__( 'Type your question', 'vance-health-hub' ) . '" autocomplete="off">';
	$html  .= '<button type="submit" class="vcom-btn vcom-btn--purple">' . esc_html__( 'Post my question', 'vance-health-hub' ) . $arrow . '</button>';
	$html  .= '</div>';
	$html  .= '<div class="vcom-ask__more">';
	$html  .= '<div class="vcom-ask__field vcom-ask__field--wide"><label for="vcom-ask-text">' . esc_html__( 'More detail (you can leave this empty)', 'vance-health-hub' ) . '</label>';
	$html  .= '<textarea id="vcom-ask-text" name="text" rows="3" maxlength="' . esc_attr( (string) $limits['text_max'] ) . '"></textarea></div>';
	$html  .= '<div class="vcom-ask__field"><label for="vcom-ask-space">' . esc_html__( 'Space', 'vance-health-hub' ) . '</label>';
	$html  .= '<select id="vcom-ask-space" name="space" required><option value="">' . esc_html__( 'Choose a space', 'vance-health-hub' ) . '</option>';
	foreach ( $spaces as $slug => $term ) {
		$html .= '<option value="' . esc_attr( $slug ) . '"' . selected( $current, $slug, false ) . '>' . esc_html( $term->name ) . '</option>';
	}
	$html .= '</select></div></div>';
	$html .= '<p class="vcom-ask__as">' . esc_html__( 'You post as', 'vance-health-hub' ) . ' <strong data-vcom-name>' . esc_html( vance_community_display_name( $user_id ) ) . '</strong>. ' . esc_html__( 'A moderator reads every question before it shows.', 'vance-health-hub' ) . '</p>';
	$html .= '<p class="vcom-ask__safety">' . esc_html__( 'If you feel very unwell, call 111 or 999.', 'vance-health-hub' ) . '</p>';
	$html .= '<p class="vcom-msg vcom-msg--light" role="status" data-vcom-msg></p>';
	$html .= '</form>';

	// The name setting is its own form: forms cannot be nested.
	$mode  = (string) get_user_meta( $user_id, '_vance_community_name_mode', true );
	$nick  = (string) get_user_meta( $user_id, '_vance_community_nickname', true );
	$html .= '<details class="vcom-as"><summary>' . esc_html__( 'Change how your name shows', 'vance-health-hub' ) . '</summary>';
	$html .= '<form class="vcom-as__form" data-vcom-form="profile">';
	$html .= '<fieldset><legend>' . esc_html__( 'Show my posts with', 'vance-health-hub' ) . '</legend>';
	$html .= '<label><input type="radio" name="mode" value="first"' . checked( 'nickname' !== $mode, true, false ) . '> ' . esc_html__( 'My first name', 'vance-health-hub' ) . '</label>';
	$html .= '<label><input type="radio" name="mode" value="nickname"' . checked( 'nickname' === $mode, true, false ) . '> ' . esc_html__( 'A nickname', 'vance-health-hub' ) . '</label>';
	$html .= '</fieldset>';
	$html .= '<label for="vcom-as-nick">' . esc_html__( 'Nickname', 'vance-health-hub' ) . '</label>';
	$html .= '<input id="vcom-as-nick" type="text" name="nickname" maxlength="' . esc_attr( (string) $limits['nickname_max'] ) . '" value="' . esc_attr( $nick ) . '" autocomplete="nickname">';
	$html .= '<p class="vcom-note vcom-note--light">' . esc_html__( 'We never show your surname or your email address.', 'vance-health-hub' ) . '</p>';
	$html .= '<button type="submit" class="vcom-btn vcom-btn--teal">' . esc_html__( 'Save', 'vance-health-hub' ) . '</button>';
	$html .= '<p class="vcom-msg vcom-msg--light" role="status" data-vcom-msg></p>';
	$html .= '</form></details>';
	return $html;
}

/**
 * The public benefits page a visitor sees at /community/. Static text and the
 * join and sign-in links only: it reads nothing from the member store.
 *
 * @return string
 */
function vance_community_public_html() {
	$arrow = vance_community_arrow();
	$rules = vance_community_rules_url();

	$html  = '<div class="vcom vcom--public" id="vcom">';
	$html .= '<section class="vcom-hero" aria-labelledby="vcom-title"><div class="vcom-wrap vcom-hero__in"><div class="vcom-hero__copy">';
	$html .= '<p class="vcom-pill">' . esc_html__( 'THE COMMUNITY', 'vance-health-hub' ) . '</p>';
	$html .= '<h1 class="vcom-hero__title" id="vcom-title">' . esc_html__( 'Some things are easier to talk about with people who', 'vance-health-hub' ) . ' <span>' . esc_html__( 'get it', 'vance-health-hub' ) . '</span></h1>';
	$html .= '<p class="vcom-hero__lead">' . esc_html__( 'A private, moderated space for people living with digestive conditions to share experiences and connect. Free membership. Join at your own pace.', 'vance-health-hub' ) . '</p>';
	$html .= '<p class="vcom-ask__signin"><a class="vcom-btn vcom-btn--purple" href="' . esc_url( vance_community_join_url() ) . '">' . esc_html__( 'Join free', 'vance-health-hub' ) . $arrow . '</a> ' . esc_html__( 'Already a member?', 'vance-health-hub' ) . ' <a href="' . esc_url( vance_community_signin_url() ) . '">' . esc_html__( 'Sign in', 'vance-health-hub' ) . '</a></p>';
	$html .= '</div></div></section>';

	$html .= '<div class="vcom-wrap vcom-main">';
	$html .= '<section class="vcom-box" aria-labelledby="vcom-b1">';
	$html .= '<h2 class="vcom-eyebrow vcom-eyebrow--purple" id="vcom-b1">' . esc_html__( 'WHAT YOU GET', 'vance-health-hub' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Membership is free.', 'vance-health-hub' ) . '</p>';
	$html .= '<p>' . esc_html__( 'Read first. Post when you feel ready. You choose how much to share, and when.', 'vance-health-hub' ) . '</p>';
	$html .= '<p>' . esc_html__( 'Members share experience. They do not give medical advice.', 'vance-health-hub' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="vcom-box" aria-labelledby="vcom-b2">';
	$html .= '<h2 class="vcom-eyebrow vcom-eyebrow--purple" id="vcom-b2">' . esc_html__( 'PRIVATE AND MODERATED', 'vance-health-hub' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Discussions and member profiles are only for signed-in members. Search engines do not list this page.', 'vance-health-hub' ) . '</p>';
	$html .= '<p>' . esc_html__( 'A moderator reads every post before it shows.', 'vance-health-hub' ) . '</p>';
	$html .= '<p>' . esc_html__( 'You can post with your first name or a nickname. A nickname is not always anonymity: other members may still recognise you from what you write. Share only what you are happy for others to read.', 'vance-health-hub' ) . '</p>';
	if ( '' !== $rules ) {
		$html .= '<a class="vcom-link" href="' . esc_url( $rules ) . '">' . esc_html__( 'Read the community rules', 'vance-health-hub' ) . $arrow . '</a>';
	}
	$html .= '</section>';

	$html .= '<section class="vcom-box" aria-labelledby="vcom-b3">';
	$html .= '<h2 class="vcom-eyebrow vcom-eyebrow--purple" id="vcom-b3">' . esc_html__( 'IF YOU NEED HELP NOW', 'vance-health-hub' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'The Community is not an emergency service. For urgent medical advice, call NHS 111. In an emergency, call 999.', 'vance-health-hub' ) . '</p>';
	$html .= '</section>';
	$html .= '</div></div>';
	return $html;
}

/**
 * Address of the community rules page, or '' while that page is not published
 * (so a visitor is never sent to a 404).
 *
 * @return string
 */
function vance_community_rules_url() {
	$rules = get_page_by_path( 'community-rules' );
	if ( $rules && 'publish' === $rules->post_status ) {
		$url = get_permalink( $rules );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}
	return '';
}

/**
 * The /community/ page.
 *
 * @return string
 */
function vance_community_render_page() {
	if ( ! vance_hub_community_active() ) {
		return '';
	}
	// The Community is private. A visitor sees the benefits page only: no
	// question, reply, name, count or poll is read from the store for them.
	if ( ! is_user_logged_in() ) {
		return vance_community_public_html();
	}
	$member  = is_user_logged_in();
	$user_id = $member ? get_current_user_id() : 0;
	$spaces  = vance_community_space_terms();
	$current = vance_community_current_space( $spaces );
	$page    = vance_community_current_page();
	$arrow   = vance_community_arrow();

	$config = array( 'member' => $member );
	if ( $member ) {
		$config['ajaxUrl'] = admin_url( 'admin-ajax.php' );
		$config['nonce']   = wp_create_nonce( 'vance_community_nonce' );
		$config['msgError'] = __( 'Something went wrong. Please try again.', 'vance-health-hub' );
	}

	$html = '<div class="vcom" id="vcom" data-vcom data-config="' . esc_attr( wp_json_encode( $config ) ) . '">';

	// -- Hero -----------------------------------------------------------------
	$image = vance_hub_img( 'about/community-support.jpg' );
	$html .= '<section class="vcom-hero" aria-labelledby="vcom-title"><div class="vcom-wrap vcom-hero__in"><div class="vcom-hero__copy">';
	$html .= '<p class="vcom-pill">' . esc_html__( 'THE COMMUNITY', 'vance-health-hub' ) . '</p>';
	$html .= '<h1 class="vcom-hero__title" id="vcom-title">' . esc_html__( 'You are not the only one with', 'vance-health-hub' ) . ' <span>' . esc_html__( 'this question', 'vance-health-hub' ) . '</span></h1>';
	$html .= '<p class="vcom-hero__lead">' . esc_html__( 'Ask people who live with a gut condition. Share what works for you. A moderator reads every post.', 'vance-health-hub' ) . '</p>';
	$html .= vance_community_ask_html( $member, $user_id, $spaces, $current );
	$html .= '<p class="vcom-hero__note">' . esc_html__( 'Visitors read the first reply to each question. Members read every reply.', 'vance-health-hub' ) . '</p>';
	$html .= '</div>';
	if ( '' !== $image ) {
		$html .= '<img class="vcom-hero__img" src="' . esc_url( $image ) . '" alt="' . esc_attr__( 'People talk together in a support group', 'vance-health-hub' ) . '" loading="eager" decoding="async">';
	}
	$html .= '</div></section>';

	// -- Space chips ----------------------------------------------------------
	if ( $spaces ) {
		// Plain links, so the filter works with no JS. hub-community.js swaps the
		// feed in place on click (data-vcom-space), so the page does not jump.
		$html .= '<nav class="vcom-wrap vcom-spaces" aria-label="' . esc_attr__( 'Community spaces', 'vance-health-hub' ) . '" data-vcom-spaces><div class="vcom-spaces__track">';
		$html .= '<a class="vcom-space' . ( '' === $current ? ' is-current' : '' ) . '" href="' . esc_url( vance_community_feed_url( '' ) ) . '" data-vcom-space=""' . ( '' === $current ? ' aria-current="page"' : '' ) . '>' . esc_html__( 'All spaces', 'vance-health-hub' ) . '</a>';
		foreach ( $spaces as $slug => $term ) {
			$on    = $slug === $current;
			$html .= '<a class="vcom-space' . ( $on ? ' is-current' : '' ) . '" href="' . esc_url( vance_community_feed_url( $slug ) ) . '" data-vcom-space="' . esc_attr( $slug ) . '"' . ( $on ? ' aria-current="page"' : '' ) . '>' . esc_html( $term->name ) . '</a>';
		}
		$html .= '</div></nav>';
	}

	$html .= '<div class="vcom-wrap vcom-main">';

	// -- Feed ------------------------------------------------------------------
	$args = array(
		'post_type'      => 'vance_question',
		'post_status'    => 'publish',
		'posts_per_page' => vance_community_limit( 'per_page' ),
		'paged'          => $page,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	if ( '' !== $current ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'vance_space', 'field' => 'slug', 'terms' => $current ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	// Sample questions are for staff only. The clause is added here, after the
	// filter, so no filter callback can take it away.
	/**
	 * Filters the feed query.
	 *
	 * @param array $args WP_Query arguments.
	 */
	$feed = new WP_Query( vance_community_sample_feed_args( (array) apply_filters( 'vance_community_feed_args', $args ) ) );

	$html .= '<section class="vcom-feed" id="vcom-feed" aria-label="' . esc_attr__( 'Questions', 'vance-health-hub' ) . '" data-vcom-feed>';
	if ( $feed->have_posts() ) {
		foreach ( $feed->posts as $q ) {
			$html .= vance_community_card_html( $q, $member, $user_id );
		}
		$pager = '';
		if ( $page > 1 ) {
			$pager .= '<a class="vcom-btn vcom-btn--outline" href="' . esc_url( vance_community_feed_url( $current, $page - 1 ) ) . '">' . esc_html__( 'Newer posts', 'vance-health-hub' ) . '</a>';
		}
		if ( $page < (int) $feed->max_num_pages ) {
			$pager .= '<a class="vcom-btn vcom-btn--outline vcom-more-posts" href="' . esc_url( vance_community_feed_url( $current, $page + 1 ) ) . '">' . esc_html__( 'Show more posts', 'vance-health-hub' ) . '</a>';
		}
		if ( '' !== $pager ) {
			$html .= '<div class="vcom-pager">' . $pager . '</div>';
		}
	} else {
		$html .= '<div class="vcom-empty">';
		$html .= '<h2 class="vcom-empty__title">' . esc_html( '' !== $current ? __( 'No questions in this space yet', 'vance-health-hub' ) : __( 'No questions yet', 'vance-health-hub' ) ) . '</h2>';
		$html .= '<p>' . esc_html( $member ? __( 'Ask the first one. Use the form at the top of the page.', 'vance-health-hub' ) : __( 'Join free and ask the first one.', 'vance-health-hub' ) ) . '</p>';
		if ( ! $member ) {
			$html .= '<a class="vcom-btn vcom-btn--purple" href="' . esc_url( vance_community_join_url() ) . '">' . esc_html__( 'Join free', 'vance-health-hub' ) . $arrow . '</a>';
		}
		$html .= '</div>';
	}
	$html .= '</section>';

	// -- Side rail ---------------------------------------------------------------
	$html .= '<aside class="vcom-rail" aria-label="' . esc_attr__( 'About the community', 'vance-health-hub' ) . '">';

	$html .= vance_community_poll_html( vance_community_active_poll(), $user_id, $member );

	if ( $member ) {
		$html .= vance_community_my_posts_html( $user_id );
	}

	$html .= '<section class="vcom-box vcom-rules" aria-labelledby="vcom-rules-title">';
	$html .= '<h2 class="vcom-eyebrow vcom-eyebrow--purple" id="vcom-rules-title">' . esc_html__( 'HOW THIS SPACE WORKS', 'vance-health-hub' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Members share experience. They do not give medical advice.', 'vance-health-hub' ) . '</p>';
	$html .= '<p>' . esc_html__( 'A moderator reads every post before it shows.', 'vance-health-hub' ) . '</p>';
	$html .= '<p>' . esc_html__( 'You can post with your first name or a nickname.', 'vance-health-hub' ) . '</p>';
	$rules = get_page_by_path( 'community-rules' );
	if ( $rules && 'publish' === $rules->post_status ) {
		$html .= '<a class="vcom-link" href="' . esc_url( get_permalink( $rules ) ) . '">' . esc_html__( 'Read the community rules', 'vance-health-hub' ) . $arrow . '</a>';
	}
	$html .= '</section>';

	$ask_page = get_page_by_path( 'ask-ai' );
	$ask_url  = ( $ask_page && 'publish' === $ask_page->post_status ) ? get_permalink( $ask_page ) : '';
	$html    .= '<section class="vcom-box vcom-askai" aria-labelledby="vcom-askai-title">';
	$html    .= '<h2 class="vcom-askai__title" id="vcom-askai-title">' . esc_html__( 'Need an answer now?', 'vance-health-hub' ) . '</h2>';
	$html    .= '<p>' . esc_html__( 'VANCE-Ai answers at any hour and shows its sources.', 'vance-health-hub' ) . '</p>';
	if ( $ask_url ) {
		$html .= '<a class="vcom-btn vcom-btn--white" href="' . esc_url( $ask_url ) . '" data-vance-askai-open="new">' . esc_html__( 'Ask VANCE-Ai', 'vance-health-hub' ) . $arrow . '</a>';
	} else {
		$html .= '<button type="button" class="vcom-btn vcom-btn--white" data-vance-askai-open="new" aria-haspopup="dialog">' . esc_html__( 'Ask VANCE-Ai', 'vance-health-hub' ) . $arrow . '</button>';
	}
	$html .= '</section>';

	$html .= '</aside>';
	$html .= '</div>'; // .vcom-main
	$html .= '</div>'; // .vcom
	return $html;
}
