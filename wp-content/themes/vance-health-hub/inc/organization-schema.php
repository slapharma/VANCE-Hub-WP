<?php
/**
 * Add `logo` and `sameAs` to the sitewide Organization schema node.
 *
 * AIOSEO's Organization node carries only name, description, url, @id — no
 * `logo`. Google's structured-data guidelines require the publisher to have a
 * logo (ImageObject) for the Article rich result, so every Article/NewsArticle
 * page on the site was ineligible. `sameAs` is also absent, which weakens
 * Organization/knowledge-panel disambiguation.
 *
 * Follows the same pattern as inc/medical-schema.php: hook AIOSEO's own
 * `aioseo_schema_output` graph filter rather than print a second JSON-LD
 * block, and remember that filter passes the whole `@graph` array, not one
 * node — see that file's docblock for why treating the argument as a single
 * node is a silent no-op.
 *
 * `sameAs` is built from the same `vance_social_*` theme mods the header's
 * social icons already read (header.php), so this can never list a profile
 * the site itself doesn't link to, and a mod left empty in the Customizer
 * just drops out rather than shipping a placeholder URL.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add logo and sameAs to the Organization node.
 *
 * @param array $graph The assembled @graph array.
 * @return array
 */
function vance_organization_schema_output( $graph ) {
	if ( ! is_array( $graph ) ) {
		return $graph;
	}

	foreach ( $graph as $i => $entry ) {
		if ( ! is_array( $entry ) || ! isset( $entry['@type'] ) || 'Organization' !== $entry['@type'] ) {
			continue;
		}

		$site_url = trailingslashit( home_url() );

		if ( empty( $entry['logo'] ) ) {
			$graph[ $i ]['logo'] = array(
				'@type'  => 'ImageObject',
				'@id'    => $site_url . '#logo',
				'url'    => get_template_directory_uri() . '/assets/img/logo.png',
				'width'  => 1024,
				'height' => 576,
			);
		}

		if ( empty( $entry['sameAs'] ) ) {
			$same_as = array();
			foreach ( array( 'linkedin', 'facebook', 'twitter', 'instagram' ) as $key ) {
				$url = vance_get_theme_mod( 'vance_social_' . $key );
				if ( $url ) {
					$same_as[] = $url;
				}
			}
			if ( $same_as ) {
				$graph[ $i ]['sameAs'] = $same_as;
			}
		}

		break;
	}

	return $graph;
}
add_filter( 'aioseo_schema_output', 'vance_organization_schema_output' );
