<?php
/**
 * VanceHubClub site header: one header for every page, behind a switch.
 *
 * The classic header (header.php) stays the default. This file adds:
 *
 *   - a Customizer switch, "Site header: use the VanceHubClub header"
 *     (section VanceHubClub, default off);
 *   - the same admin-only preview as the other VanceHubClub switches:
 *     ?vhh_layout=hub shows the new header, ?vhh_layout=classic forces the old
 *     one, and only users who can edit_theme_options are listened to;
 *   - a menu location, vance-hub-primary, for the header navigation;
 *   - a fallback navigation of real pages only, used when no menu is assigned;
 *   - the stylesheet and script, enqueued only when the header is active;
 *   - the body class vhub-header.
 *
 * The markup is in template-parts/header-hub.php, which header.php includes in
 * place of the classic <header class="site-header"> when the header is active.
 *
 * THE DATA RULE. A link is shown only when the page it points to exists. There
 * is no Community link: that page does not exist yet. The purple member bar
 * under the header comes from vance_hub_member_bar() (real counts, the
 * Founding Member line, or nothing).
 *
 * The first block is pure (no WordPress calls) so it can be tested with stubs.
 * To back the whole feature out, delete the require line in functions.php: the
 * classic header then renders exactly as before.
 *
 * Needs inc/hub-layout.php (vance_hub_sanitize_checkbox, vance_hub_page_url,
 * vance_hub_member_bar, vance_hub_render_member_bar).
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The shared preview resolver (vance_hub_preview_query).
require_once __DIR__ . '/hub-preview.php';

/* =========================================================================
 * Pure helpers (no WordPress calls)
 * ====================================================================== */

/**
 * Decide whether the hub header is on, from the saved switch, the preview query
 * value and whether the viewer may preview.
 *
 * A preview value is honoured only when $can_preview is true, and only when it
 * is 'hub' or 'classic'. Any other value is ignored, so the saved switch decides.
 *
 * @param mixed $mod_value   Saved vance_hub_header mod.
 * @param mixed $query_value Value of ?vhh_layout= ('' when absent).
 * @param bool  $can_preview True when the viewer may preview layouts.
 * @return bool
 */
function vance_hub_header_resolve( $mod_value, $query_value, $can_preview ) {
	if ( $can_preview && is_string( $query_value ) ) {
		$query_value = strtolower( trim( $query_value ) );
		if ( 'hub' === $query_value ) {
			return true;
		}
		if ( 'classic' === $query_value ) {
			return false;
		}
	}
	return vance_hub_sanitize_checkbox( $mod_value );
}

/**
 * The fallback navigation items, from the URLs of the pages that exist.
 *
 * An item whose URL is '' (its page does not exist) is left out, Tools included.
 * No Community item is ever built: that page does not exist yet.
 *
 * @param string $tools_url      URL for Tools, or ''.
 * @param string $conditions_url URL for Conditions, or ''.
 * @param string $library_url    URL for Library, or ''.
 * @return array[] Rows of label and url.
 */
function vance_hub_header_build_items( $tools_url, $conditions_url, $library_url ) {
	$items = array();
	foreach ( array(
		array( 'label' => 'Tools', 'url' => (string) $tools_url ),
		array( 'label' => 'Conditions', 'url' => (string) $conditions_url ),
		array( 'label' => 'Library', 'url' => (string) $library_url ),
	) as $item ) {
		if ( '' !== $item['url'] ) {
			$items[] = $item;
		}
	}
	return $items;
}

/**
 * The path of a URL for comparing, with no trailing slash ('' for the site root).
 *
 * @param string $url URL or path.
 * @return string
 */
function vance_hub_header_path( $url ) {
	$path = parse_url( (string) $url, PHP_URL_PATH );
	return rtrim( is_string( $path ) ? $path : '', '/' );
}

/**
 * Mark the item that matches the current page.
 *
 * An item is current when its path equals the page path, or, for any item that
 * is not the site root, when the page path is below it (a condition page below
 * Conditions). At most one item is current: the first match wins.
 *
 * @param array[] $items       Rows of label and url.
 * @param string  $current_url URL of the page being shown.
 * @return array[] The same rows with a boolean 'current'.
 */
function vance_hub_header_mark_current( $items, $current_url ) {
	$here  = vance_hub_header_path( $current_url );
	$found = false;
	foreach ( $items as $i => $item ) {
		$path    = vance_hub_header_path( $item['url'] );
		$match   = ! $found && ( $path === $here || ( '' !== $path && 0 === strpos( $here . '/', $path . '/' ) ) );
		$found   = $found || $match;
		$items[ $i ]['current'] = $match;
	}
	return $items;
}

/* =========================================================================
 * The switch
 * ====================================================================== */

/**
 * True on the member dashboard page. The page has no assigned template, so it
 * is matched by slug as well as by template (the same test as
 * vance_hub_dashboard_is_page() in hub-dashboard.php, without its login check:
 * the header must stay off for every viewer of the page).
 *
 * @return bool
 */
function vance_hub_header_on_dashboard() {
	return is_page( 'dashboard' ) || is_page_template( 'page-dashboard.php' );
}

/**
 * True when the VanceHubClub header is active for this request.
 *
 * Same capability-first read as vance_hub_layout_active(): a visitor's
 * ?vhh_layout= is never looked at, and an array value is ignored.
 *
 * @return bool
 */
function vance_hub_header_active() {
	// The member dashboard has its own sidebar layout and hides the site header
	// (page-dashboard.php), so it keeps the classic header: the hub header and
	// the member bar would stay on screen and push the fixed sidebar down.
	if ( vance_hub_header_on_dashboard() ) {
		return false;
	}
	$query = vance_hub_preview_query();
	return vance_hub_header_resolve( vance_get_theme_mod( 'vance_hub_header', false ), $query, '' !== $query );
}

/**
 * Register the header menu location. Added to the locations the theme already
 * registers; Max Mega Menu keeps the classic primary-menu location.
 */
function vance_hub_header_register_menu() {
	register_nav_menu( 'vance-hub-primary', __( 'VanceHubClub header menu', 'vance-health-hub' ) );
}
add_action( 'after_setup_theme', 'vance_hub_header_register_menu', 20 );

/**
 * Stylesheet and script, only when the hub header is active.
 */
function vance_hub_header_enqueue_assets() {
	if ( ! vance_hub_header_active() ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style(
		'vance-hub-header',
		$uri . '/assets/css/hub-header.css',
		array( 'vance-main-style' ),
		@filemtime( $dir . '/assets/css/hub-header.css' ) ?: '1.0.0'
	);
	wp_enqueue_script(
		'vance-hub-header',
		$uri . '/assets/js/hub-header.js',
		array(),
		@filemtime( $dir . '/assets/js/hub-header.js' ) ?: '1.0.0',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'vance_hub_header_enqueue_assets', 20 );

/**
 * Body class while the hub header is active.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function vance_hub_header_body_class( $classes ) {
	if ( vance_hub_header_active() ) {
		$classes[] = 'vhub-header';
	}
	return $classes;
}
add_filter( 'body_class', 'vance_hub_header_body_class' );

/* =========================================================================
 * Customizer
 * ====================================================================== */

/**
 * Register the header switch in the VanceHubClub section.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vance_hub_header_customize_register( $wp_customize ) {
	$wp_customize->add_setting( 'vance_hub_header', array(
		'default'           => false,
		'sanitize_callback' => 'vance_hub_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'vance_hub_header', array(
		'label'       => __( 'Site header: use the VanceHubClub header', 'vance-health-hub' ),
		'description' => __( 'Replaces the header on every page with the VanceHubClub header: logo, a short menu, the VANCE-Ai button, Sign in and Join free (My Hub for members), and the purple member bar under it. Assign a menu to the "VanceHubClub header menu" location to choose the links; with no menu, the header lists the Tools, Conditions and Library pages that exist. Submenus are not shown: only the top level of the menu appears. Administrators can preview it without saving: add ?vhh_layout=hub to any page address. To see it on every page, turn on preview mode (VanceHubClub admin page or "Club preview" in the admin bar).', 'vance-health-hub' ),
		'section'     => 'vance_home_layout',
		'type'        => 'checkbox',
	) );
}
add_action( 'customize_register', 'vance_hub_header_customize_register' );

/* =========================================================================
 * Data for template-parts/header-hub.php
 * ====================================================================== */

/**
 * The URL of a page by slug, or '' when no such page exists. The answer is kept
 * for the rest of the request, so the header costs one lookup per slug.
 *
 * @param string $slug     Page slug.
 * @param string $fallback Site-relative path for vance_hub_page_url().
 * @param bool   $reset    True to empty the kept answers (tests only).
 * @return string
 */
function vance_hub_header_existing_page_url( $slug, $fallback, $reset = false ) {
	static $kept = array();
	if ( $reset ) {
		$kept = array();
		return '';
	}
	if ( ! isset( $kept[ $slug ] ) ) {
		$kept[ $slug ] = get_page_by_path( $slug ) ? vance_hub_page_url( $slug, $fallback ) : '';
	}
	return $kept[ $slug ];
}

/**
 * The fallback navigation items for this site: real pages only, with the
 * current one marked.
 *
 * @return array[] Rows of label, url and current.
 */
function vance_hub_header_fallback_items() {
	$tools_url      = vance_hub_header_existing_page_url( 'tools-resources', '/tools-resources/' );
	$conditions_url = vance_hub_header_existing_page_url( 'gastro-health-explained', '/gastro-health-explained/' );
	$library_url    = vance_hub_header_existing_page_url( 'knowledgebase', '/knowledgebase/' );

	$labels = array(
		'Tools'      => __( 'Tools', 'vance-health-hub' ),
		'Conditions' => __( 'Conditions', 'vance-health-hub' ),
		'Library'    => __( 'Library', 'vance-health-hub' ),
	);
	$items  = vance_hub_header_build_items( $tools_url, $conditions_url, $library_url );
	foreach ( $items as $i => $item ) {
		$items[ $i ]['label'] = $labels[ $item['label'] ];
	}
	return vance_hub_header_mark_current( $items, vance_hub_header_current_url() );
}

/**
 * Path (and query) of the page being shown. Only its path is compared with the
 * item paths; it is never printed.
 *
 * @return string
 */
function vance_hub_header_current_url() {
	return isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
}

/**
 * The header navigation as HTML: the assigned menu when there is one, else the
 * fallback list. Every label and URL is escaped.
 *
 * @return string
 */
function vance_hub_header_nav_html() {
	if ( has_nav_menu( 'vance-hub-primary' ) ) {
		$menu = wp_nav_menu( array(
			'theme_location' => 'vance-hub-primary',
			'container'      => false,
			'menu_class'     => 'vhh-nav__list',
			'fallback_cb'    => false,
			'depth'          => 1,
			'echo'           => false,
		) );
		if ( is_string( $menu ) && '' !== trim( $menu ) ) {
			return $menu;
		}
	}

	$html = '';
	foreach ( vance_hub_header_fallback_items() as $item ) {
		$html .= '<li class="vhh-nav__item"><a class="vhh-nav__link" href="' . esc_url( $item['url'] ) . '"'
			. ( ! empty( $item['current'] ) ? ' aria-current="page"' : '' ) . '>'
			. esc_html( $item['label'] ) . '</a></li>';
	}
	return '' === $html ? '' : '<ul class="vhh-nav__list">' . $html . '</ul>';
}
