<?php
/**
 * Recipe CPT — native replacement for the iframed IBD Recipes bundle.
 *
 * `VANCE_RECIPE_PUBLIC` gates the front end in one place: false through
 * Phase 1 (admin-only, converting the 19 drafts) so no half-styled page can
 * go live, flipped to true in Phase 2 once single-vance_recipe.php exists.
 * Bump VANCE_RECIPE_REWRITE_VER whenever that flag or the rewrite slug
 * changes — the theme's only other rewrite flush is on after_switch_theme
 * (functions.php), which will not fire on a deploy.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VANCE_RECIPE_PUBLIC', true ); // Phase 2: single-vance_recipe.php exists — recipes are live.
define( 'VANCE_RECIPE_REWRITE_VER', 2 );

function vance_recipe_register_cpt() {
	register_post_type(
		'vance_recipe',
		array(
			'labels'             => array(
				'name'               => __( 'Recipes', 'vance-health-hub' ),
				'singular_name'      => __( 'Recipe', 'vance-health-hub' ),
				'menu_name'          => __( 'Recipes', 'vance-health-hub' ),
				'add_new'            => __( 'Add Recipe', 'vance-health-hub' ),
				'add_new_item'       => __( 'Add New Recipe', 'vance-health-hub' ),
				'edit_item'          => __( 'Edit Recipe', 'vance-health-hub' ),
				'new_item'           => __( 'New Recipe', 'vance-health-hub' ),
				'view_item'          => __( 'View Recipe', 'vance-health-hub' ),
				'search_items'       => __( 'Search Recipes', 'vance-health-hub' ),
				'not_found'          => __( 'No recipes found.', 'vance-health-hub' ),
				'not_found_in_trash' => __( 'No recipes found in Trash.', 'vance-health-hub' ),
				'all_items'          => __( 'All Recipes', 'vance-health-hub' ),
			),
			'public'             => true,
			'publicly_queryable' => VANCE_RECIPE_PUBLIC,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_in_admin_bar'  => false,
			'show_in_rest'       => false, // Classic editor + hand-coded meta boxes, no ACF.
			'menu_icon'          => 'dashicons-carrot',
			'menu_position'      => 25,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'has_archive'        => false, // The hub at /ibd-recipies/ is the browse page, not a CPT archive.
			'rewrite'            => array( 'slug' => 'recipes', 'with_front' => false ),
			'query_var'          => true,
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		)
	);
}
add_action( 'init', 'vance_recipe_register_cpt' );

function vance_recipe_register_taxonomies() {
	register_taxonomy(
		'vance_recipe_cat',
		'vance_recipe',
		array(
			'labels'             => array(
				'name'          => __( 'Recipe Categories', 'vance-health-hub' ),
				'singular_name' => __( 'Recipe Category', 'vance-health-hub' ),
				'menu_name'     => __( 'Categories', 'vance-health-hub' ),
			),
			'public'             => true,
			'publicly_queryable' => VANCE_RECIPE_PUBLIC,
			'show_ui'            => true,
			'show_admin_column'  => true,
			'hierarchical'       => true,
			'rewrite'            => array( 'slug' => 'recipe-category', 'with_front' => false ),
			'show_in_rest'       => false,
		)
	);

	register_taxonomy(
		'vance_recipe_tag',
		'vance_recipe',
		array(
			'labels'             => array(
				'name'          => __( 'Recipe Tags', 'vance-health-hub' ),
				'singular_name' => __( 'Recipe Tag', 'vance-health-hub' ),
				'menu_name'     => __( 'Tags', 'vance-health-hub' ),
			),
			'public'             => true,
			'publicly_queryable' => VANCE_RECIPE_PUBLIC,
			'show_ui'            => true,
			'show_admin_column'  => true,
			'hierarchical'       => false,
			'rewrite'            => array( 'slug' => 'recipe-tag', 'with_front' => false ),
			'show_in_rest'       => false,
		)
	);
}
add_action( 'init', 'vance_recipe_register_taxonomies' );

/**
 * Seed the four recipe categories once. Slugs deliberately match the
 * `category` values already used in vance_recipe_catalogue() (breakfast,
 * lunch, dinner, snacks), so the Phase 1 converter can map straight across.
 */
function vance_recipe_seed_terms() {
	if ( get_option( 'vance_recipe_terms_seeded' ) ) {
		return;
	}

	$cats = array(
		'breakfast' => __( 'Breakfast', 'vance-health-hub' ),
		'lunch'     => __( 'Lunch', 'vance-health-hub' ),
		'dinner'    => __( 'Dinner', 'vance-health-hub' ),
		'snacks'    => __( 'Snacks', 'vance-health-hub' ),
	);

	foreach ( $cats as $slug => $name ) {
		if ( ! term_exists( $slug, 'vance_recipe_cat' ) ) {
			wp_insert_term( $name, 'vance_recipe_cat', array( 'slug' => $slug ) );
		}
	}

	update_option( 'vance_recipe_terms_seeded', 1 );
}
add_action( 'init', 'vance_recipe_seed_terms', 20 );

/**
 * Versioned rewrite flush. Runs after registration (priority 30) and only
 * fires when VANCE_RECIPE_REWRITE_VER actually changed, so it costs nothing
 * on the common case.
 */
function vance_recipe_maybe_flush_rewrite() {
	if ( (int) get_option( 'vance_recipe_rewrite_ver' ) !== VANCE_RECIPE_REWRITE_VER ) {
		flush_rewrite_rules();
		update_option( 'vance_recipe_rewrite_ver', VANCE_RECIPE_REWRITE_VER );
	}
}
add_action( 'init', 'vance_recipe_maybe_flush_rewrite', 30 );

/**
 * Admin list table columns: thumbnail, category, kcal, servings, time.
 */
function vance_recipe_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['vance_recipe_thumb'] = __( 'Photo', 'vance-health-hub' );
		}
	}
	$new['vance_recipe_category'] = __( 'Category', 'vance-health-hub' );
	$new['vance_recipe_kcal']     = __( 'Kcal', 'vance-health-hub' );
	$new['vance_recipe_servings'] = __( 'Servings', 'vance-health-hub' );
	$new['vance_recipe_time']     = __( 'Time', 'vance-health-hub' );
	return $new;
}
add_filter( 'manage_vance_recipe_posts_columns', 'vance_recipe_admin_columns' );

function vance_recipe_admin_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'vance_recipe_thumb':
			echo get_the_post_thumbnail( $post_id, array( 48, 48 ), array( 'style' => 'border-radius:var(--radius-control, 6px);object-fit:cover;' ) );
			break;

		case 'vance_recipe_category':
			$terms = get_the_terms( $post_id, 'vance_recipe_cat' );
			echo ( $terms && ! is_wp_error( $terms ) )
				? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) )
				: '&#8212;';
			break;

		case 'vance_recipe_kcal':
			$kcal = get_post_meta( $post_id, '_vance_recipe_kcal', true );
			echo ( '' !== $kcal ) ? esc_html( $kcal ) : '&#8212;';
			break;

		case 'vance_recipe_servings':
			$servings = get_post_meta( $post_id, '_vance_recipe_servings', true );
			echo ( '' !== $servings ) ? esc_html( $servings ) : '&#8212;';
			break;

		case 'vance_recipe_time':
			$prep = get_post_meta( $post_id, '_vance_recipe_prep_min', true );
			$cook = get_post_meta( $post_id, '_vance_recipe_cook_min', true );
			if ( '' === $prep && '' === $cook ) {
				echo '&#8212;';
			} else {
				echo esc_html( ( (int) $prep + (int) $cook ) . ' min' );
			}
			break;
	}
}
add_action( 'manage_vance_recipe_posts_custom_column', 'vance_recipe_admin_column_content', 10, 2 );

/**
 * Restore the vance_recipe query var while VANCE_RECIPE_PUBLIC is false.
 *
 * WP core's WP::parse_request() only carries a post type's own rewrite query
 * var through to $wp->query_vars when is_post_type_viewable() is true, which
 * (for a non-builtin type) requires publicly_queryable. With that false, a
 * URL matching /recipes/%postname%/ still resolves $wp->matched_rule
 * correctly but the vance_recipe var is silently dropped — verified live
 * 2026-08-20: the request fell through to the default/home query and
 * returned 200 with the theme's generic homepage content instead of 404 (no
 * recipe data leaked, but not the clean "not live yet" Phase 1 needs).
 * Putting the var back here — sourced from $wp->matched_query, which IS
 * still set correctly — lets WP_Query resolve it as a normal singular query
 * again, so is_singular('vance_recipe') below works and can 404 it properly.
 *
 * WP::parse_request() itself only translates a bare post-type query var into
 * post_type+name inside the SAME loop that's gated by is_post_type_viewable()
 * (see $post_type_query_vars in wp-includes/class-wp.php), so setting just
 * `vance_recipe` here isn't enough — post_type/name have to be set directly.
 * This action fires at the very end of parse_request(), after core's own
 * "strip post_type unless publicly_queryable" pass already ran, so it isn't
 * re-stripped.
 */
function vance_recipe_restore_query_var( $wp ) {
	if ( VANCE_RECIPE_PUBLIC || empty( $wp->matched_query ) ) {
		return;
	}
	parse_str( $wp->matched_query, $matched );
	if ( ! empty( $matched['vance_recipe'] ) ) {
		$wp->query_vars['vance_recipe'] = $matched['vance_recipe'];
		$wp->query_vars['post_type']    = 'vance_recipe';
		$wp->query_vars['name']         = $matched['vance_recipe'];
	}
}
add_action( 'parse_request', 'vance_recipe_restore_query_var' );

/**
 * Belt-and-braces 404 for single recipes while VANCE_RECIPE_PUBLIC is false.
 */
function vance_recipe_block_frontend_when_private() {
	if ( ! VANCE_RECIPE_PUBLIC && is_singular( 'vance_recipe' ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
	}
}
add_action( 'template_redirect', 'vance_recipe_block_frontend_when_private' );
