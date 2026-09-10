<?php
/**
 * Comments — the theme's first comment template.
 *
 * Added 2026-09-10 for recipe pages. Nothing else on the site calls
 * comments_template(), and adding this file does NOT switch comments on
 * anywhere: WordPress only loads it where a template asks for it AND the post
 * has comments open. Articles are unaffected.
 *
 * The markup is deliberately plain and the styling lives in
 * assets/css/main.css under "Comments", not inline, because a comment thread
 * is a list of unknown length — the inline-style habit the rest of this theme
 * uses would have to repeat itself once per comment.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * A password-protected post must not leak its discussion. Core checks this in
 * every bundled theme's comments.php for the same reason.
 */
if ( post_password_required() ) {
	return;
}
?>

<section id="comments" class="vance-comments">

	<?php if ( have_comments() ) : ?>
		<h2 class="vance-comments__title">
			<?php
			$vance_comment_count = get_comments_number();
			printf(
				/* translators: %s: comment count */
				esc_html( _n( '%s comment', '%s comments', $vance_comment_count, 'vance-health-hub' ) ),
				esc_html( number_format_i18n( $vance_comment_count ) )
			);
			?>
		</h2>

		<ol class="vance-comments__list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 44,
					'reply_text'  => esc_html__( 'Reply', 'vance-health-hub' ),
				)
			);
			?>
		</ol>

		<?php
		// Only prints anything once the thread runs past the per-page setting.
		the_comments_pagination(
			array(
				'prev_text' => esc_html__( 'Older comments', 'vance-health-hub' ),
				'next_text' => esc_html__( 'Newer comments', 'vance-health-hub' ),
			)
		);
		?>
	<?php endif; ?>

	<?php
	/*
	 * "Comments are closed" is only worth saying when there is a thread to
	 * explain. On a recipe with no comments and commenting switched off, the
	 * honest rendering is nothing at all.
	 */
	if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :
		?>
		<p class="vance-comments__closed"><?php esc_html_e( 'Comments are closed.', 'vance-health-hub' ); ?></p>
		<?php
	endif;

	comment_form(
		array(
			'class_form'           => 'vance-comment-form',
			'title_reply'          => esc_html__( 'Leave a comment', 'vance-health-hub' ),
			'title_reply_to'       => esc_html__( 'Reply to %s', 'vance-health-hub' ),
			'title_reply_before'   => '<h2 id="reply-title" class="vance-comments__title vance-comments__title--form">',
			'title_reply_after'    => '</h2>',
			'label_submit'         => esc_html__( 'Post comment', 'vance-health-hub' ),
			'class_submit'         => 'vance-comment-form__submit',
			// Accurate as the site is configured: comment_moderation is off but
			// comment_previously_approved is on, so a first comment is held and
			// later ones from the same person are not. If either option changes,
			// change this line with it.
			'comment_notes_before' => '<p class="vance-comment-form__notes">' . esc_html__( 'Your email address is not published. Your first comment is checked before it appears.', 'vance-health-hub' ) . '</p>',
			'comment_field'        => sprintf(
				'<p class="comment-form-comment"><label for="comment">%1$s</label><textarea id="comment" name="comment" rows="5" required></textarea></p>',
				esc_html__( 'Your comment', 'vance-health-hub' )
			),
		)
	);
	?>

</section>
