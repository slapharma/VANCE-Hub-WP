<?php
/**
 * Recipes, Gastro Discounts and VANCE-Ai KB in the VanceHealthHub admin menu.
 *
 * The three post types register with 'show_in_menu' => false, so core adds no
 * top-level menu for them. This file adds one submenu item for each list
 * screen and one for each taxonomy screen, and keeps the VanceHealthHub menu
 * open while one of those screens shows.
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
 * Post type => menu label and taxonomy => menu label, in menu order.
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
 * Menu slug of a taxonomy screen. Core writes the ampersand as an entity in
 * its own taxonomy menu slugs, because the slug is printed as the link address.
 *
 * @param string $taxonomy  Taxonomy.
 * @param string $post_type Post type.
 * @return string
 */
function vance_admin_content_menu_tax_slug( $taxonomy, $post_type ) {
	return 'edit-tags.php?taxonomy=' . $taxonomy . '&amp;post_type=' . $post_type;
}

/**
 * The submenu item that belongs to a screen, or '' when the screen is not ours.
 *
 * A taxonomy screen wins over the post type, because a term screen has both.
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
				return vance_admin_content_menu_tax_slug( $taxonomy, $type );
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
		foreach ( $entry['taxonomies'] as $taxonomy => $label ) {
			add_submenu_page(
				'vance-content-hub',
				$entry['label'] . ': ' . $label,
				'&mdash; ' . $label,
				'manage_categories',
				vance_admin_content_menu_tax_slug( $taxonomy, $post_type )
			);
		}
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
