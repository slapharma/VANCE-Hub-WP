<?php
/**
 * Make sure every page has a social share image.
 *
 * AIOSEO derives og:image from the featured image, which covers articles and
 * leaves everything without one bare. On 2026-09-01 the homepage — the URL most
 * likely to be pasted into Slack, WhatsApp or LinkedIn — carried og:title,
 * og:description, og:url, og:type, og:site_name and og:locale, and no image at
 * all. A link with no image gets a small grey card instead of a preview.
 *
 * The site had no default configured either: AIOSEO's defaultImage and
 * defaultImagePosts are both empty, and its Social settings live in the same
 * options structure that cannot be written safely on this install — see the
 * note in inc/seo-archive-robots.php.
 *
 * So the default is supplied here, and only ever as a fallback: if AIOSEO has
 * already worked out an image for the page, this leaves it alone. It fills in
 * width, height and alt alongside it, because a card renders faster and more
 * reliably when the dimensions do not have to be fetched to be known.
 *
 * The image is a JPEG rather than the WebP hero it was cut from. WebP support
 * across the crawlers that build these previews is still uneven, and this is
 * one 98 KB file fetched by scrapers rather than by readers.
 *
 * @package Vance_Health_Hub
 * @since   2026-09-01
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The site-wide fallback share image.
 *
 * @return array{url:string,width:int,height:int,alt:string}
 */
function vance_default_social_image() {
	return array(
		'url'    => get_template_directory_uri() . '/assets/img/og-default.jpg',
		'width'  => 1200,
		'height' => 630,
		'alt'    => __( 'Vance Health Hub — trusted information for living with a gastrointestinal condition', 'vance-health-hub' ),
	);
}

/**
 * Fill in og:image and its dimensions when AIOSEO has not set one.
 *
 * @param array $meta Facebook/Open Graph tags, keyed by property.
 * @return array
 */
function vance_og_image_fallback( $meta ) {
	if ( ! is_array( $meta ) ) {
		return $meta;
	}

	if ( ! empty( $meta['og:image'] ) ) {
		return $meta;
	}

	$image = vance_default_social_image();

	$meta['og:image']        = $image['url'];
	$meta['og:image:width']  = (string) $image['width'];
	$meta['og:image:height'] = (string) $image['height'];
	$meta['og:image:alt']    = $image['alt'];

	return $meta;
}
add_filter( 'aioseo_facebook_tags', 'vance_og_image_fallback' );

/**
 * Same for the Twitter/X card, which was carrying title and description with
 * no image and so rendering as a summary rather than a large card.
 *
 * @param array $meta Twitter tags, keyed by name.
 * @return array
 */
function vance_twitter_image_fallback( $meta ) {
	if ( ! is_array( $meta ) ) {
		return $meta;
	}

	if ( ! empty( $meta['twitter:image'] ) ) {
		return $meta;
	}

	$image = vance_default_social_image();

	$meta['twitter:image']     = $image['url'];
	$meta['twitter:image:alt'] = $image['alt'];

	return $meta;
}
add_filter( 'aioseo_twitter_tags', 'vance_twitter_image_fallback' );
