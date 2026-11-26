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
 * THE DATA RULE. A link is shown only when the page it points to exists. The
 * Community link is added only while the Community hub switch is on and the
 * /community/ page exists (vance_hub_header_add_community()). The purple member bar
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
 * No Community item is built here: vance_hub_header_add_community() adds it,
 * behind the Community hub switch.
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
	// The community page is a VanceHubClub page: it always uses the club header
	// while the Community hub is on (inc/hub-community.php).
	if ( function_exists( 'vance_community_on_page' ) && vance_community_on_page() ) {
		return true;
	}
	$query = vance_hub_preview_query();
	return vance_hub_header_resolve( vance_hub_switch_on( 'vance_hub_header' ), $query, '' !== $query );
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
 * Register the header switch in section 3 of the VanceHubClub panel
 * (inc/hub-layout.php names the sections and the switch labels).
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vance_hub_header_customize_register( $wp_customize ) {
	$switch = vance_hub_customizer_switch( 'vance_hub_header' );
	$wp_customize->add_setting( 'vance_hub_header', array(
		'default'           => vance_hub_switch_default( 'vance_hub_header' ),
		'sanitize_callback' => 'vance_hub_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'vance_hub_header', array(
		'label'       => $switch['label'],
		'description' => __( 'On: every page gets the VanceHubClub header: the logo, the menu, the VANCE-Ai button, Sign in and Join free (My Hub for members), and the purple member bar. The header shows the site\'s original primary menu with its dropdowns. It adds Community while the Community hub is on. To use a short menu, assign a menu to the "VanceHubClub header menu" location: only its top level appears. With no menu, the header lists the Tools, Conditions and Library pages that exist.', 'vance-health-hub' ),
		'section'     => $switch['section'],
		'type'        => 'checkbox',
		'priority'    => 10,
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
	$items = vance_hub_header_add_community( $items );
	return vance_hub_header_mark_current( $items, vance_hub_header_current_url() );
}

/**
 * Add the Community item after Tools, only while the Community hub is on
 * (inc/hub-community.php) and the /community/ page exists. With the hub off,
 * or without that file, the list is returned unchanged.
 *
 * @param array[] $items Rows of label and url.
 * @return array[]
 */
function vance_hub_header_add_community( $items ) {
	if ( ! function_exists( 'vance_hub_community_active' ) || ! vance_hub_community_active() ) {
		return $items;
	}
	// Only a published page: a draft or private /community/ would give visitors a 404.
	$page = get_page_by_path( 'community' );
	if ( ! $page || ! isset( $page->post_status ) || 'publish' !== $page->post_status ) {
		return $items;
	}
	$url = vance_hub_header_existing_page_url( 'community', '/community/' );
	if ( '' === $url ) {
		return $items;
	}
	$item = array( 'label' => __( 'Community', 'vance-health-hub' ), 'url' => $url );
	$at   = ( isset( $items[0]['label'] ) && __( 'Tools', 'vance-health-hub' ) === $items[0]['label'] ) ? 1 : 0;
	array_splice( $items, $at, 0, array( $item ) );
	return $items;
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
 * The header navigation as HTML, first match wins:
 *   1. a menu assigned to the "VanceHubClub header menu" location (top level only);
 *   2. the site's original primary menu, in full. Max Mega Menu renders it, with
 *      its dropdowns and its phone drawer, as in the classic header;
 *   3. the fallback list of real pages.
 * Every label and URL is escaped.
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

	if ( has_nav_menu( 'primary-menu' ) ) {
		// No depth limit: Max Mega Menu needs the whole tree for its panels.
		// Without the plugin, hub-header.css hides the nested lists.
		$menu = wp_nav_menu( array(
			'theme_location' => 'primary-menu',
			'container'      => false,
			'menu_class'     => 'vhh-nav__list',
			'fallback_cb'    => false,
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

/**
 * True when the header navigation HTML is Max Mega Menu's. The header then
 * leaves the phone menu to the plugin's own drawer (hub-header.css,
 * .vhh-header--mega).
 *
 * @param string $html Output of vance_hub_header_nav_html().
 * @return bool
 */
function vance_hub_header_nav_is_mega( $html ) {
	return false !== strpos( (string) $html, 'mega-menu-wrap' );
}

/**
 * The Community item as one <li> for the original primary menu, or '' when the
 * Community hub is off or its page is not published.
 *
 * With Max Mega Menu the item carries the plugin's top-level classes, so its
 * generated stylesheet styles it like the other top-level links. The classes
 * flag the item as a plain flyout with no children, so the plugin script adds
 * no toggle to it.
 *
 * @param bool $mega True when the menu is Max Mega Menu's.
 * @return string
 */
function vance_hub_header_community_li( $mega ) {
	$added = vance_hub_header_add_community( array() );
	if ( empty( $added[0]['url'] ) ) {
		return '';
	}
	$marked  = vance_hub_header_mark_current( array( $added[0] ), vance_hub_header_current_url() );
	$current = ! empty( $marked[0]['current'] );

	$label = (string) $added[0]['label'];
	if ( $mega ) {
		$li_class = 'mega-menu-item mega-menu-item-type-post_type mega-menu-item-object-page mega-align-bottom-left mega-menu-flyout vhh-nav__community' . ( $current ? ' mega-current-menu-item' : '' );
		$a_class  = ' class="mega-menu-link"';
		// The original menu's labels are typed in capitals (THE HUB, CONDITIONS),
		// with no text-transform, so Community is written the same way.
		$label = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $label, 'UTF-8' ) : strtoupper( $label );
	} else {
		$li_class = 'menu-item menu-item-type-post_type menu-item-object-page vhh-nav__community' . ( $current ? ' current-menu-item' : '' );
		$a_class  = '';
	}
	return '<li class="' . esc_attr( $li_class ) . '"><a' . $a_class . ' href="' . esc_url( $added[0]['url'] ) . '"'
		. ( $current ? ' aria-current="page"' : '' ) . '>'
		. esc_html( $label ) . '</a></li>';
}

/**
 * Add Community as the last top-level item of the original primary menu, only
 * inside the VanceHubClub header. The classic header and its menu stay as they
 * are. Added at render time, never stored as a menu item, so turning the
 * Community hub off removes it everywhere at once.
 *
 * Community is not added when the menu already links to the community page (an
 * editor added it by hand), or when the menu has no items (the header then uses
 * its fallback list).
 *
 * With Max Mega Menu, a visitor also gets a Sign in item that shows only in the
 * phone drawer: the phone bar has no room for the Sign in button, and the
 * drawer replaces the panel that held it.
 *
 * Priority 10, before the drawer-only items of inc/nav-mega.php (priority 20).
 *
 * @param string   $items Menu item markup.
 * @param stdClass $args  wp_nav_menu() arguments.
 * @return string
 */
function vance_hub_header_menu_community( $items, $args ) {
	if ( ! is_object( $args ) || empty( $args->theme_location ) || 'primary-menu' !== $args->theme_location ) {
		return $items;
	}
	if ( '' === trim( (string) $items ) || ! vance_hub_header_active() ) {
		return $items;
	}
	$mega = false !== strpos( (string) $items, 'mega-menu-item' );

	$community = vance_hub_header_community_li( $mega );
	if ( '' !== $community && preg_match( '#href="' . preg_quote( esc_url( vance_hub_header_existing_page_url( 'community', '/community/' ) ), '#' ) . '"#', (string) $items ) ) {
		$community = '';
	}

	$signin = '';
	if ( $mega && ! is_user_logged_in() ) {
		$signin = '<li class="mega-menu-item mega-menu-item-type-custom mega-menu-item-object-custom mega-align-bottom-left mega-menu-flyout vhh-nav__phone-only">'
			. '<a class="mega-menu-link" href="' . esc_url( home_url( '/login/' ) ) . '">' . esc_html__( 'Sign in', 'vance-health-hub' ) . '</a></li>';
	}
	return $items . $community . $signin;
}
add_filter( 'wp_nav_menu_items', 'vance_hub_header_menu_community', 10, 2 );
