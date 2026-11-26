<?php
/**
 * Keep the search title and meta description inside the length limits.
 *
 * An Ahrefs crawl on 2026-11-26 flagged 214 titles over 60 characters, 92
 * under 30, 179 descriptions over 160, 93 under 110 and 59 with none. The long
 * descriptions are AIOSEO's fallback, which copies the first few hundred
 * characters of the article. The short ones are the 90 recipe cards and the
 * IBD discount pages.
 *
 * Done at display time, as a filter, for the same reason as seo-title.php:
 * nothing here rewrites stored content, and removing the require line brings
 * back the old behaviour. A title or description written by hand that already
 * fits passes through unchanged.
 *
 * Only the <title> and meta description change. The page heading (H1) keeps the
 * full text.
 *
 * @package Vance_Health_Hub
 * @since   2026-11-26
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'VANCE_TITLE_MAX' ) ) {
	define( 'VANCE_TITLE_MAX', 60 );
}
if ( ! defined( 'VANCE_TITLE_MIN' ) ) {
	define( 'VANCE_TITLE_MIN', 20 );
}
if ( ! defined( 'VANCE_DESC_MAX' ) ) {
	define( 'VANCE_DESC_MAX', 160 );
}
if ( ! defined( 'VANCE_DESC_MIN' ) ) {
	define( 'VANCE_DESC_MIN', 110 );
}

/**
 * Remove words that must not end a cut title.
 *
 * @param string $text Text cut at a word boundary.
 * @return string
 */
function vance_meta_trim_tail( $text ) {
	// Trim with /u regexes, not rtrim(): a byte mask would eat the last byte of
	// characters such as "ē" or "Ó" and leave invalid UTF-8.
	$punct = '~[\s,;:\-–—|&]+$~u';
	$text  = preg_replace( $punct, '', $text );
	// A cut title must not end on a joining word ("... of the").
	while ( preg_match( '~\s+(?:a|an|and|or|of|the|to|in|on|for|with|vs|by|at|is|are)$~iu', $text ) ) {
		$text = preg_replace( '~\s+\S+$~u', '', $text );
		$text = preg_replace( $punct, '', $text );
	}
	return $text;
}

/**
 * Cut text to a limit at a word boundary and add an ellipsis.
 *
 * @param string $text  Plain text.
 * @param int    $limit Maximum length of the result, ellipsis included.
 * @return string
 */
function vance_meta_cut_words( $text, $limit ) {
	if ( mb_strlen( $text ) <= $limit ) {
		return $text;
	}
	$head = mb_substr( $text, 0, $limit - 1 );
	// Drop the partial word, unless the cut fell exactly on a word end.
	if ( ! preg_match( '~\s~u', mb_substr( $text, $limit - 1, 1 ) ) ) {
		$head = preg_replace( '~\s+\S*$~u', '', $head );
	}
	return vance_meta_trim_tail( $head ) . '…';
}

/**
 * Shorten a title to the maximum length.
 *
 * Prefers a clean cut at the last subtitle separator (": ", " - ", " | ") that
 * leaves at least 20 characters. Otherwise cuts at a word boundary.
 *
 * @param string $title Document title.
 * @return string
 */
function vance_meta_shorten_title( $title ) {
	$title = trim( preg_replace( '~\s+~u', ' ', (string) $title ) );

	if ( mb_strlen( $title ) <= VANCE_TITLE_MAX ) {
		return $title;
	}

	$best = '';
	if ( preg_match_all( '~(?::\s|\s[-–—|]\s|[?!]\s)~u', $title, $m, PREG_OFFSET_CAPTURE ) ) {
		foreach ( $m[0] as $hit ) {
			// Offsets are in bytes; convert the prefix to characters.
			$prefix = substr( $title, 0, $hit[1] );
			$cut    = rtrim( $prefix . ( in_array( trim( $hit[0] ), array( '?', '!' ), true ) ? trim( $hit[0] ) : '' ) );
			$len    = mb_strlen( $cut );
			if ( $len >= VANCE_TITLE_MIN && $len <= VANCE_TITLE_MAX ) {
				$best = $cut;
			}
		}
	}

	if ( '' !== $best ) {
		return $best;
	}

	return vance_meta_cut_words( $title, VANCE_TITLE_MAX );
}

/**
 * Lengthen a very short title with the site name.
 *
 * @param string $title Document title.
 * @param string $site  Site name.
 * @return string
 */
function vance_meta_pad_title( $title, $site ) {
	if ( '' === $title || mb_strlen( $title ) >= VANCE_TITLE_MIN || '' === $site ) {
		return $title;
	}
	$padded = $title . ' | ' . $site;
	return mb_strlen( $padded ) <= VANCE_TITLE_MAX ? $padded : $title;
}

/**
 * Filter: finish the document title.
 *
 * Runs after vance_strip_title_suffix() (priority 100000).
 *
 * @param string $title Finished document title.
 * @return string
 */
function vance_meta_title_length( $title ) {
	if ( ! is_string( $title ) || '' === $title ) {
		return $title;
	}
	if ( is_front_page() || is_home() ) {
		return $title;
	}
	$site  = trim( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$plain = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );
	$new   = vance_meta_pad_title( vance_meta_shorten_title( $plain ), $site );
	return ( $new === $plain ) ? $title : $new;
}
add_filter( 'pre_get_document_title', 'vance_meta_title_length', 100001 );

/**
 * Fit a description to 110-160 characters.
 *
 * @param string $desc   Plain-text description (may be empty).
 * @param string $source Plain text to build one from when $desc is empty.
 * @return string Fitted description, or '' when there is nothing to build from.
 */
function vance_meta_fit_description( $desc, $source = '' ) {
	$desc = trim( preg_replace( '~\s+~u', ' ', (string) $desc ) );
	if ( '' === $desc ) {
		$desc = trim( preg_replace( '~\s+~u', ' ', (string) $source ) );
	}
	if ( '' === $desc ) {
		return '';
	}

	if ( mb_strlen( $desc ) > VANCE_DESC_MAX ) {
		// One character past the limit, so a full stop must be followed by a
		// space: "pH 5.5" is never cut after the "5.".
		$window = mb_substr( $desc, 0, VANCE_DESC_MAX + 1 );
		// Last sentence end that leaves a useful description.
		if ( preg_match( '~^(.{70,}[.!?])\s~us', $window, $m ) ) {
			// Greedy match above already takes the longest sentence run.
			$desc = $m[1];
		} else {
			$desc = vance_meta_cut_words( $desc, VANCE_DESC_MAX );
		}
	}

	if ( mb_strlen( $desc ) < VANCE_DESC_MIN ) {
		$endings = array(
			' Evidence-based gut health information from Vance Health Hub.',
			' From Vance Health Hub.',
			' Vance Health Hub.',
			// Longer fillers for descriptions under about 50 characters.
			' Practical, evidence-based information on Crohn\'s, colitis, IBS and gut health from Vance Health Hub.',
			' Practical, evidence-based information on Crohn\'s, colitis, IBS and gut health from Vance Health Hub, for UK patients.',
		);
		$base = preg_match( '~[.!?…]$~u', $desc ) ? $desc : $desc . '.';
		foreach ( $endings as $end ) {
			$try = $base . $end;
			if ( mb_strlen( $try ) >= VANCE_DESC_MIN && mb_strlen( $try ) <= VANCE_DESC_MAX ) {
				return $try;
			}
		}
	}

	return $desc;
}

/**
 * Filter: finish the meta description.
 *
 * @param string $description AIOSEO's finished description.
 * @return string
 */
function vance_meta_description_length( $description ) {
	$plain  = is_string( $description ) ? wp_strip_all_tags( html_entity_decode( $description, ENT_QUOTES, 'UTF-8' ) ) : '';
	$source = '';

	// Singular pages with no description: build one from the excerpt or body.
	if ( '' === trim( $plain ) && is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post && post_password_required( $post ) === false ) {
			$raw    = '' !== trim( (string) $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
			$source = wp_strip_all_tags( strip_shortcodes( $raw ) );
		}
	}

	$fit = vance_meta_fit_description( $plain, $source );
	return ( '' === $fit || $fit === $plain ) ? $description : $fit;
}
add_filter( 'aioseo_description', 'vance_meta_description_length', 100001 );
