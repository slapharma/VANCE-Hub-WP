<?php
/**
 * Link logged-out visitors straight to the sign-in page, not to a redirect.
 *
 * /dashboard/ sends a visitor who is not signed in to
 * /login/?redirect_to=<dashboard>. Every public page that linked to
 * /dashboard/ therefore linked to a 302. An Ahrefs crawl on 2026-11-26 counted
 * 241 indexable pages with a link to a redirect. The header already did this
 * for its own Dashboard link (header.php); this gives the other templates the
 * same behaviour.
 *
 * A signed-in visitor still gets /dashboard/. Any other URL passes through
 * unchanged.
 *
 * @package Vance_Health_Hub
 * @since   2026-11-26
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the URL a visitor should follow to reach the dashboard.
 *
 * @param string $url A URL or path. Only the bare /dashboard/ is rewritten.
 * @return string
 */
function vance_dashboard_link( $url = '/dashboard/' ) {
	if ( ! is_string( $url ) || is_user_logged_in() ) {
		return $url;
	}

	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( '/dashboard/' !== $path || null !== wp_parse_url( $url, PHP_URL_QUERY ) ) {
		return $url;
	}

	// Only this site's dashboard. A link to another host is left alone.
	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( null !== $host && strtolower( (string) $host ) !== strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) ) {
		return $url;
	}

	return add_query_arg( 'redirect_to', rawurlencode( home_url( '/dashboard/' ) ), home_url( '/login/' ) );
}
