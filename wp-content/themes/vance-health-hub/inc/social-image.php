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
		'alt'    => __( 'Vance Health Hub: trusted information for living with a gastrointestinal condition', 'vance-health-hub' ),
	);
}

/**
 * Fill in og:image and its dimensions when AIOSEO has not set one.
 *
 * @param array $meta Facebook/Open Graph tags, keyed by property.
 * @return array
 */
function vance_og_image_fallback( $meta ) {
	vance_aioseo_facebook_tags_fired( true );

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
 * Track whether AIOSEO called its own Facebook-tags filter this request.
 *
 * Confirmed 2026-09 (audit T3): `aioseo_facebook_tags` never fires on category
 * or other taxonomy archives, so `vance_og_image_fallback()` above never runs
 * there and every archive ships with no og:image. This flag is how the
 * `wp_head` fallback below knows AIOSEO left the page untouched, without
 * needing to inspect output that has already been printed.
 *
 * @param bool|null $set Pass true to record that the filter fired.
 * @return bool
 */
function vance_aioseo_facebook_tags_fired( $set = null ) {
	static $fired = false;
	if ( true === $set ) {
		$fired = true;
	}
	return $fired;
}

/**
 * Print a fallback og:image directly on archive pages AIOSEO's social filter
 * never reaches. Self-healing: if a future AIOSEO release fires
 * aioseo_facebook_tags on taxonomy archives, $fired flips true and this stays
 * silent.
 */
function vance_og_image_archive_fallback() {
	if ( ! is_category() && ! is_tax() ) {
		return;
	}

	if ( vance_aioseo_facebook_tags_fired() ) {
		return;
	}

	$image = vance_default_social_image();

	printf(
		'<meta property="og:image" content="%1$s" /><meta property="og:image:width" content="%2$d" /><meta property="og:image:height" content="%3$d" /><meta property="og:image:alt" content="%4$s" />' . "\n",
		esc_url( $image['url'] ),
		(int) $image['width'],
		(int) $image['height'],
		esc_attr( $image['alt'] )
	);
}
add_action( 'wp_head', 'vance_og_image_archive_fallback', 30 );

/**
 * og:site_name is the name of the site, not a description of it.
 *
 * AIOSEO's default format for this tag is "#site_title #separator_sa #tagline",
 * which produced "Vance Health Hub - Curated clinical research, latest news…".
 * Lengthening the tagline to a proper 158-character meta description on
 * 2026-09-01 pushed it to 177 characters, which is what made an existing
 * oddity obvious: every share card was being told the site is called that
 * whole sentence.
 *
 * The description already travels in og:description. This tag just needs the
 * name.
 *
 * @param array $meta Facebook/Open Graph tags, keyed by property.
 * @return array
 */
function vance_og_site_name( $meta ) {
	if ( ! is_array( $meta ) ) {
		return $meta;
	}

	$name = trim( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );

	if ( '' !== $name ) {
		$meta['og:site_name'] = $name;
	}

	return $meta;
}
add_filter( 'aioseo_facebook_tags', 'vance_og_site_name' );

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
