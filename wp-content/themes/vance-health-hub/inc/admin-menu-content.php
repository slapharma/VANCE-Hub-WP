<?php
/**
 * Recipes, Gastro Discounts and VANCE-Ai KB in the VanceHealthHub admin menu.
 *
 * The three post types register with 'show_in_menu' => false, so core adds no
 * top-level menu for them. This file adds one submenu item for each list
 * screen and keeps the VanceHealthHub menu open while a list, edit or
 * taxonomy screen of those post types shows.
 *
 * The taxonomy screens (Categories, Tags, Regions) have no menu item. Each
 * list screen shows links to them in the row above the table.
 *
 * The map below is the only place that names the screens. To move one back to
 * its own top-level menu, remove it from the map and set its 'show_in_menu'
 * back to true.
 *
 * @package VanceHealthHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post type => menu label and taxonomy => link label, in menu order.
 *
 * @return array
 */
function vance_admin_content_menu_map() {
	return array(
		'vance_recipe'   => array(
			'label'      => __( 'Recipes', 'vance-health-hub' ),
			'taxonomies' => array(
				'vance_recipe_cat' => __( 'Categories', 'vance-health-hub' ),
				'vance_recipe_tag' => __( 'Tags', 'vance-health-hub' ),
			),
		),
		'vance_discount' => array(
			'label'      => __( 'Gastro Discounts', 'vance-health-hub' ),
			'taxonomies' => array(
				'vance_discount_cat'    => __( 'Categories', 'vance-health-hub' ),
				'vance_discount_region' => __( 'Regions', 'vance-health-hub' ),
			),
		),
		'vance_kb'       => array(
			'label'      => __( 'VANCE-Ai KB', 'vance-health-hub' ),
			'taxonomies' => array(
				'vance_kb_type' => __( 'Categories', 'vance-health-hub' ),
			),
		),
	);
}

/**
 * Menu slug of a list screen.
 *
 * @param string $post_type Post type.
 * @return string
 */
function vance_admin_content_menu_list_slug( $post_type ) {
	return 'edit.php?post_type=' . $post_type;
}

/**
 * The submenu item that belongs to a screen, or '' when the screen is not ours.
 *
 * A taxonomy screen highlights the list item of the post type that owns the
 * taxonomy. The taxonomy is examined first, because a term screen can have an
 * empty post type.
 *
 * @param string $post_type Post type of the screen, or ''.
 * @param string $taxonomy  Taxonomy of the screen, or ''.
 * @return string
 */
function vance_admin_content_menu_target( $post_type, $taxonomy ) {
	$map       = vance_admin_content_menu_map();
	$post_type = (string) $post_type;
	$taxonomy  = (string) $taxonomy;

	if ( '' !== $taxonomy ) {
		foreach ( $map as $type => $entry ) {
			if ( isset( $entry['taxonomies'][ $taxonomy ] ) ) {
				return vance_admin_content_menu_list_slug( $type );
			}
		}
	}
	if ( '' !== $post_type && isset( $map[ $post_type ] ) ) {
		return vance_admin_content_menu_list_slug( $post_type );
	}
	return '';
}

/**
 * Register the menu items. Priority 1002: the parent menu is added at 999,
 * and the other VanceHealthHub items at 1000 and 1001, so these follow them.
 */
function vance_admin_content_menu() {
	foreach ( vance_admin_content_menu_map() as $post_type => $entry ) {
		add_submenu_page(
			'vance-content-hub',
			$entry['label'],
			$entry['label'],
			'edit_posts',
			vance_admin_content_menu_list_slug( $post_type )
		);
	}
}
add_action( 'admin_menu', 'vance_admin_content_menu', 1002 );

/**
 * Keep the VanceHealthHub menu open on a list, edit or taxonomy screen of the
 * three post types, with the correct item highlighted.
 *
 * Core gives the top-level menu the slug of its first visible submenu item
 * (Content Feedback for an administrator, a different item for an editor) and
 * records the change in $_wp_real_parent_file. Core does not apply that record
 * to the value this filter returns, so this function applies it.
 *
 * @param string $parent_file Parent file.
 * @return string
 */
function vance_admin_content_menu_parent_file( $parent_file ) {
	global $submenu_file, $typenow, $taxnow, $_wp_real_parent_file;
	$target = vance_admin_content_menu_target( $typenow, $taxnow );
	if ( '' === $target ) {
		return $parent_file;
	}
	$submenu_file = $target; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	if ( is_array( $_wp_real_parent_file ) && isset( $_wp_real_parent_file['vance-content-hub'] ) ) {
		return $_wp_real_parent_file['vance-content-hub'];
	}
	return 'vance-content-hub';
}
add_filter( 'parent_file', 'vance_admin_content_menu_parent_file' );

/**
 * Add links to the taxonomy screens in the row above a list table, because
 * those screens have no menu item.
 *
 * @param array  $views     The view links of the list screen.
 * @param string $post_type Post type of the list screen.
 * @return array
 */
function vance_admin_content_menu_tax_views( $views, $post_type ) {
	$map = vance_admin_content_menu_map();
	if ( ! is_array( $views ) || ! isset( $map[ $post_type ] ) || ! current_user_can( 'manage_categories' ) ) {
		return $views;
	}
	foreach ( $map[ $post_type ]['taxonomies'] as $taxonomy => $label ) {
		$url = add_query_arg(
			array(
				'taxonomy'  => $taxonomy,
				'post_type' => $post_type,
			),
			admin_url( 'edit-tags.php' )
		);
		$views[ 'vance_tax_' . $taxonomy ] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
	return $views;
}

/**
 * Attach the taxonomy links to each of the three list screens.
 */
function vance_admin_content_menu_register_views() {
	foreach ( array_keys( vance_admin_content_menu_map() ) as $post_type ) {
		add_filter(
			'views_edit-' . $post_type,
			static function ( $views ) use ( $post_type ) {
				return vance_admin_content_menu_tax_views( $views, $post_type );
			}
		);
	}
}
add_action( 'admin_init', 'vance_admin_content_menu_register_views' );
