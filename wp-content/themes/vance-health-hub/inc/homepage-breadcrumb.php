<?php
/**
 * Fix the homepage's BreadcrumbList, which describes whichever post happens
 * to be first in the "Latest Content" loop instead of the homepage itself.
 *
 * Root cause (audit S2): `show_on_front` is `posts`, so by the time `wp_head`
 * runs, the global `$post` most recently touched is the first post of the main
 * query — the loop's `the_post()` calls leave it set — and AIOSEO's breadcrumb
 * generator builds from that context rather than resetting for the front page.
 * The trail changes to a different wrong four-level path every time the
 * newest post changes; it never once describes the homepage's own location
 * (depth 1, "Home").
 *
 * Fixed here rather than by setting a static front page, because the site's
 * "Latest Content" homepage is a deliberate editorial choice, not a leftover
 * default — this only replaces the breadcrumb graph, same pattern as
 * inc/medical-schema.php and inc/organization-schema.php.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replace the BreadcrumbList node with a single Home item on the front page.
 *
 * @param array $graph The assembled @graph array.
 * @return array
 */
function vance_homepage_breadcrumb_output( $graph ) {
	if ( ! is_array( $graph ) || ! is_front_page() ) {
		return $graph;
	}

	$home_url = home_url( '/' );

	foreach ( $graph as $i => $entry ) {
		if ( ! is_array( $entry ) || ! isset( $entry['@type'] ) || 'BreadcrumbList' !== $entry['@type'] ) {
			continue;
		}

		$graph[ $i ]['itemListElement'] = array(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => __( 'Home', 'vance-health-hub' ),
				'item'     => $home_url,
			),
		);
	}

	return $graph;
}
add_filter( 'aioseo_schema_output', 'vance_homepage_breadcrumb_output' );
