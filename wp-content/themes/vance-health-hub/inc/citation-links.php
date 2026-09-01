<?php
/**
 * Turn the DOIs already written into articles into working citation links.
 *
 * A crawl on 2026-09-01 found 635 DOIs across 122 published articles and not
 * one of them clickable, alongside zero outbound links to the NHS, NICE,
 * PubMed, the BMJ or anywhere else. The identifiers were all there — printed
 * as flat characters after a "DOI:" label, or inside a full Vancouver citation
 * — so the references existed and simply did not resolve. On a site whose
 * proposition is curated clinical research, that is the single cheapest
 * credibility signal available, and it was switched off.
 *
 * This runs at display time rather than rewriting post_content, for three
 * reasons: 122 articles do not need a destructive one-shot migration to solve a
 * presentation problem; articles published tomorrow get the same treatment with
 * nobody remembering to run anything; and reverting is deleting one require
 * line rather than restoring a backup.
 *
 * What it deliberately does not do:
 *
 *   - Touch anything already inside an <a>. Seven articles had hand-made
 *     doi.org links before this existed and they are left exactly as written.
 *   - Touch anything inside a tag. The split below keeps markup and text apart,
 *     so a DOI that appears in an attribute cannot be rewritten into one.
 *   - Swallow the punctuation after a citation. Thirty-four of the 635 end a
 *     sentence, so "…n1554." must link the DOI and leave the full stop outside
 *     it, or the link 404s at doi.org.
 *
 * @package Vance_Health_Hub
 * @since   2026-09-01
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trim trailing sentence punctuation off a matched DOI.
 *
 * Closing brackets need care rather than a blanket strip: a DOI may legitimately
 * contain them — 10.1002/(SICI)1097-0258 is a real prefix — so a ')' only counts
 * as punctuation when there is no '(' inside the DOI to have opened it.
 *
 * @param string $doi Raw matched text.
 * @return array{0:string,1:string} The DOI, and the punctuation removed from it.
 */
function vance_split_doi_punctuation( $doi ) {
	$trail = '';

	while ( '' !== $doi ) {
		$last = substr( $doi, -1 );

		$is_punctuation = ( false !== strpos( '.,;:', $last ) );

		if ( ! $is_punctuation && ( ')' === $last || ']' === $last ) ) {
			$open           = ( ')' === $last ) ? '(' : '[';
			$is_punctuation = substr_count( $doi, $open ) < substr_count( $doi, $last );
		}

		if ( ! $is_punctuation ) {
			break;
		}

		$trail = $last . $trail;
		$doi   = substr( $doi, 0, -1 );
	}

	return array( $doi, $trail );
}

/**
 * Replace bare DOIs in one run of plain text with links to doi.org.
 *
 * Matches an optional doi.org prefix so a DOI already written as a full URL
 * becomes one link rather than a link wrapped around a link. '&' is excluded
 * from the DOI itself so an HTML entity in the surrounding copy can never be
 * absorbed into the href.
 *
 * @param string $text Plain text, guaranteed to contain no markup.
 * @return string
 */
function vance_linkify_dois_in_text( $text ) {
	$pattern = '~(?:https?://(?:dx\.)?doi\.org/)?10\.\d{4,9}/[^\s<>"\'&]+~';

	return preg_replace_callback(
		$pattern,
		static function ( $matches ) {
			list( $shown, $trail ) = vance_split_doi_punctuation( $matches[0] );

			if ( ! preg_match( '~(10\.\d{4,9}/.+)$~', $shown, $found ) ) {
				return $matches[0];
			}

			return sprintf(
				'<a class="vhh-doi" href="%s" target="_blank" rel="noopener">%s</a>%s',
				esc_url( 'https://doi.org/' . $found[1] ),
				esc_html( $shown ),
				$trail
			);
		},
		$text
	);
}

/**
 * Link the DOIs in post content.
 *
 * Splits the content so that existing anchors, script and style blocks, and
 * every HTML tag pass through untouched, and only the text between them is
 * rewritten. preg_split() with PREG_SPLIT_DELIM_CAPTURE puts those captured
 * delimiters at the odd offsets, leaving the even offsets as plain text.
 *
 * @param string $content Post content.
 * @return string
 */
function vance_linkify_dois( $content ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	// Cheap bail: no DOI prefix anywhere, nothing to do on the other 46 posts.
	if ( false === strpos( $content, '10.' ) ) {
		return $content;
	}

	$parts = preg_split(
		'~(<a\b[^>]*>.*?</a>|<script\b[^>]*>.*?</script>|<style\b[^>]*>.*?</style>|<[^>]+>)~is',
		$content,
		-1,
		PREG_SPLIT_DELIM_CAPTURE
	);

	if ( ! is_array( $parts ) ) {
		return $content;
	}

	foreach ( $parts as $i => $part ) {
		if ( 0 === $i % 2 && '' !== $part ) {
			$parts[ $i ] = vance_linkify_dois_in_text( $part );
		}
	}

	return implode( '', $parts );
}
add_filter( 'the_content', 'vance_linkify_dois', 20 );
