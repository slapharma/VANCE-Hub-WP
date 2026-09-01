<?php
/**
 * The alt text for a post's featured image.
 *
 * Several card templates build their own <img> from
 * get_the_post_thumbnail_url(), which returns a URL and nothing else, and each
 * of them hard-coded alt="". That left the article thumbnails on the homepage
 * and the grouped archives with no accessible name, and it was never a
 * decision — it is what you get when a template swaps get_the_post_thumbnail()
 * for the _url() variant and has to write the tag by hand.
 *
 * The text itself is not missing. All 166 featured images on the site carry a
 * real description of the photograph, written at upload and stored on the
 * attachment: "A lit cigarette resting on the edge of a glass ashtray",
 * "Ready meal trays moving along a production line". This just fetches it.
 *
 * Returns an empty string when an image has no description, so the markup
 * falls back to alt="" rather than to a filename or a repeat of the headline.
 * A filename read aloud —
 * "transitioning-from-paediatric-to-adult-i-hero.jpg" — is worse than silence,
 * and the headline is already in the link.
 *
 * Only for images that carry meaning. Decorative icons in these same templates
 * keep their hard-coded alt="", which is correct for them.
 *
 * @package Vance_Health_Hub
 * @since   2026-09-01
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alt text for a post's featured image, or '' when it has none.
 *
 * @param int|null $post_id Post ID; defaults to the current post.
 * @return string
 */
function vance_thumbnail_alt( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();

	if ( ! $post_id ) {
		return '';
	}

	$thumb_id = get_post_thumbnail_id( $post_id );

	if ( ! $thumb_id ) {
		return '';
	}

	return trim( (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) );
}
