<?php
/**
 * Drop the " - Vance Health Hub" suffix from the browser and search title.
 *
 * A crawl on 2026-09-01 found 150 of 229 titles over 60 characters, the point
 * at which Google starts truncating. 228 of them ended in the same 19-character
 * suffix, which buys nothing on a result already labelled with the domain and
 * costs a fifth of the visible line. Removing it takes 45 titles under the
 * limit on its own.
 *
 * Done as a filter rather than by setting AIOSEO's title format, which is where
 * this belongs and is not somewhere it can safely be written on this install.
 * The per-post-type formats are absent from the stored aioseo_options — the
 * plugin generates that structure at runtime from defaults — so setting them
 * means fabricating a shape the plugin owns and regenerates. Reaching into
 * AIOSEO's internals is what took vance_recipe-sitemap.xml offline earlier the
 * same day; see the note in inc/seo-archive-robots.php.
 *
 * AIOSEO renders the title on pre_get_document_title at priority 99999, so this
 * runs after it and edits the finished string.
 *
 * If a future AIOSEO release makes the title format writable, set it to
 * "#post_title" in Search Appearance and delete this file — it becomes a no-op
 * either way, because the suffix it looks for will no longer be there.
 *
 * @package Vance_Health_Hub
 * @since   2026-09-01
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove a trailing site-name suffix from the document title.
 *
 * The separator is matched as a character class rather than read from AIOSEO's
 * settings, because the stored value is an HTML entity ('&#45;') and the
 * options object on this install cannot be relied on to return it. Any of the
 * usual separators is stripped, so changing the separator in the admin does not
 * quietly leave the suffix behind.
 *
 * The front page is left alone. Its title puts the brand first — "Vance Health
 * Hub - IBD, IBS & Gut Health Information UK" — which is right for the one page
 * whose job is to say whose site this is, and which this pattern would not
 * match anyway.
 *
 * @param string $title The finished document title.
 * @return string
 */
function vance_strip_title_suffix( $title ) {
	if ( ! is_string( $title ) || '' === $title ) {
		return $title;
	}

	if ( is_front_page() || is_home() ) {
		return $title;
	}

	$site = trim( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );

	if ( '' === $site ) {
		return $title;
	}

	$stripped = preg_replace(
		'~\s*(?:[-–—|·»:]|&#45;|&ndash;|&mdash;)\s*' . preg_quote( $site, '~' ) . '\s*$~ui',
		'',
		$title
	);

	// A title that is nothing but the site name would be emptied. Keep it.
	if ( ! is_string( $stripped ) || '' === trim( $stripped ) ) {
		return $title;
	}

	return $stripped;
}
add_filter( 'pre_get_document_title', 'vance_strip_title_suffix', 100000 );
