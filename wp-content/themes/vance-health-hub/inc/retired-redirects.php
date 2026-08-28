<?php
/**
 * Retired pages — send a withdrawn URL somewhere useful instead of nowhere.
 *
 * When a page is taken out of service its WP Page is trashed, which makes its
 * URL 404. That is fine for a page nobody ever linked to and wrong for one
 * anybody might have bookmarked, so each retired slug gets a permanent home
 * here and a 301 to it.
 *
 * A 301 is cached hard by browsers and by search engines. Adding a slug here
 * is therefore close to irreversible from the visitor's side: if the page is
 * ever brought back, remove its row FIRST and expect the redirect to keep
 * firing for people who have already seen it. That is also why the redirect
 * runs on the request path rather than on the post — an untrashed page would
 * otherwise still be swallowed by its own redirect.
 *
 * @package vance-health-hub
 * @since   2026-08-28
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * retired slug => the SLUG of the page that replaces it.
 *
 * A slug, not a path, and resolved through get_page_by_path() below, because a
 * hard-coded path lands one hop short. The first version of this file sent
 * /our-heritage/ to '/about/' -- which is not the About page, it is a slug
 * WordPress itself 301s to /about-us/. That made a two-hop chain out of a
 * redirect whose whole job is to be the last one. Letting WordPress name the
 * destination also means a future slug change carries the redirect with it.
 *
 * @return array<string, array{slug: string, path: string}>
 */
function vance_retired_redirects() {
	return array(
		/*
		 * Our Heritage, retired 2026-08-28. An unlinked clone of About Us:
		 * nothing on the site pointed at it — not the menus, not the footer,
		 * not the homepage — so About is both the honest destination and
		 * where its content already lives in a maintained form.
		 *
		 * Its ~200 Customizer settings were deleted from customizer-pages.php,
		 * but the saved `vance_heritage_*` theme mods are deliberately left in
		 * the database. They cost nothing, and they are the only copy of what
		 * that page said.
		 */
		'our-heritage' => array( 'slug' => 'about-us', 'path' => '/about-us/' ),
	);
}

/**
 * 301 a retired slug to its replacement.
 *
 * Runs on the request path, not on the queried object, so it works whether the
 * page is trashed, deleted, or still published.
 *
 * @return void
 */
function vance_retired_redirect() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	$path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	if ( ! is_string( $path ) ) {
		return;
	}

	$slug = trim( $path, '/' );
	// Only ever a top-level slug. A deeper path that merely starts with one
	// is somebody else's URL.
	if ( $slug === '' || strpos( $slug, '/' ) !== false ) {
		return;
	}

	$map = vance_retired_redirects();
	if ( ! isset( $map[ $slug ] ) ) {
		return;
	}

	// Resolve to the canonical permalink so this is the only hop. The literal
	// path is a last resort for a site where that page has been renamed or
	// removed -- it will chain through WordPress's own canonical redirect, which
	// is still better than a 404.
	$dest   = $map[ $slug ];
	$page   = get_page_by_path( $dest['slug'] );
	$target = $page ? get_permalink( $page ) : home_url( $dest['path'] );

	// A row whose destination is its own slug would loop the browser.
	if ( untrailingslashit( $target ) === untrailingslashit( home_url( $path ) ) ) {
		return;
	}

	wp_safe_redirect( $target, 301 );
	exit;
}
add_action( 'template_redirect', 'vance_retired_redirect', 1 );
