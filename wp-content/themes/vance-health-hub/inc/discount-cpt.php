<?php
/**
 * Discount CPT — "IBD Discounts & Freebies" directory (docs/DISCOUNTS_TOOL_PLAN.md).
 *
 * Registration, taxonomies, admin columns, and the one-shot `wp vance discounts
 * import` command that loads tools/discounts-seed.json. Copied from
 * inc/recipe-cpt.php's shape; the import command is closer to
 * inc/recipe-converter.php's CLI pattern than to vance_recipe_seed_terms()
 * (that one seeds four fixed terms — this imports ~26 full posts with meta),
 * but it lives here rather than a seventh file because there's nothing else in
 * this CPT's bootstrap file and the plan's file table doesn't reserve one.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VANCE_DISCOUNT_REWRITE_VER', 1 );

function vance_discount_register_cpt() {
	register_post_type(
		'vance_discount',
		array(
			'labels'             => array(
				'name'               => __( 'Discounts', 'vance-health-hub' ),
				'singular_name'      => __( 'Discount', 'vance-health-hub' ),
				'menu_name'          => __( 'IBD Discounts', 'vance-health-hub' ),
				'add_new'            => __( 'Add Scheme', 'vance-health-hub' ),
				'add_new_item'       => __( 'Add New Scheme', 'vance-health-hub' ),
				'edit_item'          => __( 'Edit Scheme', 'vance-health-hub' ),
				'new_item'           => __( 'New Scheme', 'vance-health-hub' ),
				'view_item'          => __( 'View Scheme', 'vance-health-hub' ),
				'search_items'       => __( 'Search Schemes', 'vance-health-hub' ),
				'not_found'          => __( 'No schemes found.', 'vance-health-hub' ),
				'not_found_in_trash' => __( 'No schemes found in Trash.', 'vance-health-hub' ),
				'all_items'          => __( 'All Schemes', 'vance-health-hub' ),
			),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_in_admin_bar'  => false,
			'show_in_rest'       => false, // Classic editor + hand-coded meta boxes, no ACF — matches vance_recipe.
			'menu_icon'          => 'dashicons-tickets-alt',
			'menu_position'      => 26,
			'supports'           => array( 'title', 'editor' ), // Editor field unused on the card/single but kept for an admin-only long-form note; not rendered front-end.
			'has_archive'        => false, // /ibd-discounts/ is a Page template, not a CPT archive — plan §5.
			'rewrite'            => array( 'slug' => 'ibd-discount', 'with_front' => false ),
			'query_var'          => true,
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		)
	);
}
add_action( 'init', 'vance_discount_register_cpt' );

function vance_discount_register_taxonomies() {
	register_taxonomy(
		'vance_discount_cat',
		'vance_discount',
		array(
			'labels'             => array(
				'name'          => __( 'Discount Categories', 'vance-health-hub' ),
				'singular_name' => __( 'Discount Category', 'vance-health-hub' ),
				'menu_name'     => __( 'Categories', 'vance-health-hub' ),
			),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_admin_column'  => true,
			'hierarchical'       => true,
			'rewrite'            => array( 'slug' => 'discount-category', 'with_front' => false ),
			'show_in_rest'       => false,
		)
	);

	register_taxonomy(
		'vance_discount_region',
		'vance_discount',
		array(
			'labels'             => array(
				'name'          => __( 'Discount Regions', 'vance-health-hub' ),
				'singular_name' => __( 'Discount Region', 'vance-health-hub' ),
				'menu_name'     => __( 'Regions', 'vance-health-hub' ),
			),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_admin_column'  => true,
			'hierarchical'       => true,
			'rewrite'            => array( 'slug' => 'discount-region', 'with_front' => false ),
			'show_in_rest'       => false,
		)
	);
}
add_action( 'init', 'vance_discount_register_taxonomies' );

/**
 * Fixed vocabularies, both from plan §5. Category slugs match the JSON seed's
 * `category` field verbatim, so import needs no translation table. Region
 * slugs are the five single-nation terms; a scheme valid in more than one
 * nation (JSON's "England+Wales" etc.) gets more than one term, never a term
 * of its own — see vance_discount_region_slugs_from_json().
 */
function vance_discount_seed_terms() {
	if ( get_option( 'vance_discount_terms_seeded' ) ) {
		return;
	}

	$cats = array(
		'toilet-access' => __( 'Toilet Access', 'vance-health-hub' ),
		'days-out'      => __( 'Days Out', 'vance-health-hub' ),
		'travel'        => __( 'Travel', 'vance-health-hub' ),
		'access-card'   => __( 'Access Card', 'vance-health-hub' ),
		'benefit'       => __( 'Benefits', 'vance-health-hub' ),
		'nhs'           => __( 'NHS', 'vance-health-hub' ),
		'tax'           => __( 'Tax', 'vance-health-hub' ),
		'work'          => __( 'Work', 'vance-health-hub' ),
		'household'     => __( 'Household', 'vance-health-hub' ),
	);
	foreach ( $cats as $slug => $name ) {
		if ( ! term_exists( $slug, 'vance_discount_cat' ) ) {
			wp_insert_term( $name, 'vance_discount_cat', array( 'slug' => $slug ) );
		}
	}

	$regions = array(
		'uk'       => __( 'UK', 'vance-health-hub' ),
		'england'  => __( 'England', 'vance-health-hub' ),
		'wales'    => __( 'Wales', 'vance-health-hub' ),
		'scotland' => __( 'Scotland', 'vance-health-hub' ),
		'ni'       => __( 'Northern Ireland', 'vance-health-hub' ),
	);
	foreach ( $regions as $slug => $name ) {
		if ( ! term_exists( $slug, 'vance_discount_region' ) ) {
			wp_insert_term( $name, 'vance_discount_region', array( 'slug' => $slug ) );
		}
	}

	update_option( 'vance_discount_terms_seeded', 1 );
}
add_action( 'init', 'vance_discount_seed_terms', 20 );

/**
 * Versioned rewrite flush — same mechanism as vance_recipe_maybe_flush_rewrite().
 */
function vance_discount_maybe_flush_rewrite() {
	if ( (int) get_option( 'vance_discount_rewrite_ver' ) !== VANCE_DISCOUNT_REWRITE_VER ) {
		flush_rewrite_rules();
		update_option( 'vance_discount_rewrite_ver', VANCE_DISCOUNT_REWRITE_VER );
	}
}
add_action( 'init', 'vance_discount_maybe_flush_rewrite', 30 );

/**
 * Admin list columns: provider, tier, verified date, featured. Category and
 * region columns come free from show_admin_column on the taxonomies. The
 * Links column (last-check verdict) is added by inc/discount-check.php, which
 * loads after this file.
 */
function vance_discount_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['vance_discount_provider'] = __( 'Provider', 'vance-health-hub' );
		}
	}
	$new['vance_discount_tier']     = __( 'Tier', 'vance-health-hub' );
	$new['vance_discount_verified'] = __( 'Verified', 'vance-health-hub' );
	$new['vance_discount_featured'] = __( 'Featured', 'vance-health-hub' );
	return $new;
}
add_filter( 'manage_vance_discount_posts_columns', 'vance_discount_admin_columns' );

function vance_discount_admin_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'vance_discount_provider':
			$provider = get_post_meta( $post_id, '_vance_discount_provider', true );
			echo ( '' !== $provider ) ? esc_html( $provider ) : '&#8212;';
			break;

		case 'vance_discount_tier':
			$tier = get_post_meta( $post_id, '_vance_discount_tier', true );
			echo ( '' !== $tier ) ? esc_html( 'Tier ' . $tier ) : '&#8212;';
			break;

		case 'vance_discount_verified':
			$date = get_post_meta( $post_id, '_vance_discount_verified_on', true );
			echo ( '' !== $date ) ? esc_html( $date ) : '&#8212;';
			break;

		case 'vance_discount_featured':
			echo get_post_meta( $post_id, '_vance_discount_featured', true ) ? '&#9733;' : '&#8212;';
			break;
	}
}
add_action( 'manage_vance_discount_posts_custom_column', 'vance_discount_admin_column_content', 10, 2 );

/**
 * Sortable by tier and verified date — both plain scalar meta, no range query
 * needed, so the default meta_key orderby is enough.
 */
function vance_discount_sortable_columns( $columns ) {
	$columns['vance_discount_tier']     = 'vance_discount_tier';
	$columns['vance_discount_verified'] = 'vance_discount_verified';
	return $columns;
}
add_filter( 'manage_edit-vance_discount_sortable_columns', 'vance_discount_sortable_columns' );

function vance_discount_sortable_orderby( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$orderby = $query->get( 'orderby' );
	if ( 'vance_discount_tier' === $orderby ) {
		$query->set( 'meta_key', '_vance_discount_tier' );
		$query->set( 'orderby', 'meta_value_num' );
	} elseif ( 'vance_discount_verified' === $orderby ) {
		$query->set( 'meta_key', '_vance_discount_verified_on' );
		$query->set( 'orderby', 'meta_value' );
	}
}
add_action( 'pre_get_posts', 'vance_discount_sortable_orderby' );

/* -------------------------------------------------------------------------
 * wp vance discounts import <file>
 * ---------------------------------------------------------------------- */

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * Every meta key this CPT writes, and the JSON field it comes from. Kept as
	 * one map so the importer and (eventually) an export/audit tool read from a
	 * single source of truth — see docs/DISCOUNTS_TOOL_PLAN.md §4.
	 *
	 * @return array<string,string>
	 */
	function vance_discount_scalar_field_map() {
		return array(
			'provider'        => '_vance_discount_provider',
			'value_summary'   => '_vance_discount_value',
			'cost'            => '_vance_discount_cost',
			'what_you_get'    => '_vance_discount_what',
			'who_qualifies'   => '_vance_discount_who',
			'ibd_note'        => '_vance_discount_ibd_note',
			'official_url'    => '_vance_discount_official_url',
			'apply_url'       => '_vance_discount_apply_url',
			'apply_type'      => '_vance_discount_apply_type',
			'apply_contact'   => '_vance_discount_apply_contact',
			'upcoming_change' => '_vance_discount_upcoming',
			'verified_on'     => '_vance_discount_verified_on',
			'confidence'      => '_vance_discount_confidence',
		);
	}

	/**
	 * "England+Wales" -> ['england','wales']; "UK" -> ['uk']. Unknown tokens are
	 * dropped rather than fatal — a typo in the seed should not abort the whole
	 * import, `wp vance discounts check` catches data problems separately.
	 *
	 * @param string $region_field Raw `region` value from the seed.
	 * @return string[] Region term slugs.
	 */
	function vance_discount_region_slugs_from_json( $region_field ) {
		$known = array( 'uk', 'england', 'wales', 'scotland', 'ni' );
		$slugs = array();
		foreach ( explode( '+', (string) $region_field ) as $part ) {
			$slug = sanitize_key( $part );
			if ( 'northernireland' === $slug || 'northern-ireland' === $slug ) {
				$slug = 'ni';
			}
			if ( in_array( $slug, $known, true ) ) {
				$slugs[] = $slug;
			}
		}
		return $slugs ? $slugs : array( 'uk' );
	}

	/**
	 * Upsert one scheme post from its seed record. Matched by post_name (the
	 * seed's `slug`), so importing twice updates in place rather than
	 * duplicating — safe to re-run whenever the seed changes (e.g. once the
	 * pending `schemes_additional` research pass lands, plan §11/§12).
	 *
	 * @param array $scheme One entry from the seed's `schemes` array.
	 * @return int Post ID.
	 */
	function vance_discount_import_one( $scheme ) {
		$slug     = sanitize_title( $scheme['slug'] );
		$existing = get_page_by_path( $slug, OBJECT, 'vance_discount' );

		$postarr = array(
			'post_type'   => 'vance_discount',
			'post_status' => 'publish',
			'post_name'   => $slug,
			'post_title'  => wp_strip_all_tags( $scheme['title'] ),
		);

		if ( $existing ) {
			$postarr['ID'] = $existing->ID;
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $post_id ) ) {
			return 0;
		}

		foreach ( vance_discount_scalar_field_map() as $json_key => $meta_key ) {
			$value = isset( $scheme[ $json_key ] ) ? $scheme[ $json_key ] : '';
			if ( '' === $value || null === $value ) {
				delete_post_meta( $post_id, $meta_key );
			} elseif ( false !== strpos( $meta_key, '_url' ) ) {
				update_post_meta( $post_id, $meta_key, esc_url_raw( $value ) );
			} else {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $value ) );
			}
		}

		$evidence = isset( $scheme['evidence_accepted'] ) ? (array) $scheme['evidence_accepted'] : array();
		if ( $evidence ) {
			update_post_meta( $post_id, '_vance_discount_evidence', implode( "\n", array_map( 'sanitize_text_field', $evidence ) ) );
		} else {
			delete_post_meta( $post_id, '_vance_discount_evidence' );
		}

		$signals = isset( $scheme['eligibility_signals'] ) ? (array) $scheme['eligibility_signals'] : array();
		if ( $signals ) {
			update_post_meta( $post_id, '_vance_discount_signals', array_map( 'sanitize_key', $signals ) );
		} else {
			delete_post_meta( $post_id, '_vance_discount_signals' );
		}

		$tier = isset( $scheme['integration_tier'] ) ? (int) $scheme['integration_tier'] : 0;
		if ( $tier >= 1 && $tier <= 3 ) {
			update_post_meta( $post_id, '_vance_discount_tier', $tier );
		} else {
			delete_post_meta( $post_id, '_vance_discount_tier' );
		}

		update_post_meta( $post_id, '_vance_discount_frameable', ! empty( $scheme['frameable'] ) ? 1 : 0 );

		$related = isset( $scheme['related_posts'] ) ? array_map( 'intval', (array) $scheme['related_posts'] ) : array();
		if ( $related ) {
			update_post_meta( $post_id, '_vance_discount_related_posts', $related );
		} else {
			delete_post_meta( $post_id, '_vance_discount_related_posts' );
		}

		// featured is admin-set, never overwritten by an import re-run.
		if ( ! $existing ) {
			update_post_meta( $post_id, '_vance_discount_featured', 0 );
		}

		wp_set_object_terms( $post_id, sanitize_key( isset( $scheme['category'] ) ? $scheme['category'] : '' ), 'vance_discount_cat', false );
		wp_set_object_terms( $post_id, vance_discount_region_slugs_from_json( isset( $scheme['region'] ) ? $scheme['region'] : '' ), 'vance_discount_region', false );

		return $post_id;
	}

	/**
	 * Import (or re-sync) the discount seed. Reads both `schemes` and, once
	 * populated, `schemes_additional` (plan §11) — the two arrays share the same
	 * record shape, so no separate code path is needed for the research-pass
	 * additions.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to the seed JSON, e.g. tools/discounts-seed.json.
	 *
	 * @param array $args Positional args.
	 * @return void
	 */
	function vance_discount_cli_import( $args ) {
		$path = $args[0];
		if ( ! is_readable( $path ) ) {
			WP_CLI::error( "Cannot read seed file: {$path}" );
		}

		$data = json_decode( file_get_contents( $path ), true );
		if ( ! is_array( $data ) ) {
			WP_CLI::error( 'Seed file is not valid JSON.' );
		}

		$schemes = array_merge(
			isset( $data['schemes'] ) ? $data['schemes'] : array(),
			isset( $data['schemes_additional'] ) ? $data['schemes_additional'] : array()
		);

		if ( ! $schemes ) {
			WP_CLI::error( 'No schemes found in seed file.' );
		}

		$created = 0;
		$updated = 0;

		foreach ( $schemes as $scheme ) {
			if ( empty( $scheme['slug'] ) || empty( $scheme['title'] ) ) {
				WP_CLI::warning( 'Skipped a record with no slug/title.' );
				continue;
			}
			$was_existing = (bool) get_page_by_path( sanitize_title( $scheme['slug'] ), OBJECT, 'vance_discount' );
			$post_id      = vance_discount_import_one( $scheme );
			if ( ! $post_id ) {
				WP_CLI::warning( "Failed to import: {$scheme['slug']}" );
				continue;
			}
			$was_existing ? $updated++ : $created++;
			WP_CLI::log( ( $was_existing ? 'updated  ' : 'created  ' ) . $scheme['slug'] );
		}

		WP_CLI::success( sprintf( '%d created, %d updated, %d total schemes.', $created, $updated, $created + $updated ) );
	}

	WP_CLI::add_command( 'vance discounts import', 'vance_discount_cli_import' );
}
