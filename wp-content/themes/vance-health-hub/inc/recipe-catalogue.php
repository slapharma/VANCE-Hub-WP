<?php
/**
 * Recipe catalogue — the theme-side data layer over the `vance_recipe` CPT.
 *
 * WHY THIS EXISTS
 * ---------------
 * This file is the keystone of the native recipe rebuild (Phase 3): it keeps
 * the exact same 13 function signatures and return shapes that
 * page-dashboard.php has depended on since before the rebuild, but sources
 * them from the `vance_recipe` CPT (inc/recipe-cpt.php, inc/recipe-admin.php)
 * instead of the hand-mirrored arrays that used to live here and in
 * recipe-data.php, which mirrored a Next.js bundle export
 * (assets/tools/ibd-recipes/) that no longer drives anything user-facing.
 *
 * Because every caller — the dashboard's meal-plan cards, the viewer modal,
 * the PDF export, the shopping list, the credit lines — goes through these
 * functions rather than touching post meta directly, none of that code
 * needed to change when the data source did. `vance_recipe_expand_meal()`,
 * `vance_recipe_shopping_list()`, `vance_recipe_plan_recipes()` and
 * `vance_recipe_credit_line()` in particular MUST keep returning exactly the
 * same shape for the same logical recipe: page-dashboard.php calls them
 * directly and un-defensively.
 *
 * SAVED PLANS OLD AND NEW
 * ------------------------
 * A saved meal-plan row only ever recorded a recipe by slug (v3+) or by name
 * (v2 and earlier) — never the full recipe. `vance_recipe_resolve_slug()` is
 * the join back to a real recipe for both; an unresolved slug/name degrades
 * gracefully (no thumbnail, no link) rather than erroring, so a recipe an
 * admin later unpublishes or renames doesn't break anyone's saved history.
 *
 * IMAGES
 * ------
 * `vance_recipe_image_url()` returns the CPT's native featured image. There
 * is no remote/hotlink fallback any more — every recipe has a real uploaded
 * image (see inc/recipe-converter.php, which converted all 19 from drafts
 * that already had featured images).
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All published recipes as WP_Post objects, queried once per request. Both
 * vance_recipe_catalogue() and vance_recipe_data() build their per-request
 * caches from this single query rather than querying twice — meta and terms
 * are primed for every post in it by WP_Query's own cache priming, so
 * get_post_meta()/get_the_terms() calls against these posts elsewhere in the
 * request don't cost extra queries either.
 *
 * @return WP_Post[]
 */
function vance_recipe_all_posts() {
	static $posts = null;
	if ( null !== $posts ) {
		return $posts;
	}

	$posts = get_posts(
		array(
			'post_type'      => 'vance_recipe',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	return $posts;
}

/**
 * The full recipe list, keyed by slug (the CPT's post_name).
 *
 * @return array<string, array{id:int, name:string, category:string}>
 */
function vance_recipe_catalogue() {
	static $catalogue = null;
	if ( null !== $catalogue ) {
		return $catalogue;
	}

	$catalogue = array();
	foreach ( vance_recipe_all_posts() as $post ) {
		$cat_terms = get_the_terms( $post->ID, 'vance_recipe_cat' );
		$category  = ( $cat_terms && ! is_wp_error( $cat_terms ) && isset( $cat_terms[0] ) ) ? $cat_terms[0]->slug : '';

		$catalogue[ $post->post_name ] = array(
			'id'       => $post->ID,
			// Raw post_title, not get_the_title(): the latter HTML-entity-encodes
			// ampersands ("&" -> "&#038;"), which broke vance_recipe_name_key()'s
			// match against saved plans that recorded a raw "&" in the name
			// (verified live 2026-08-20 against a real account's pre-rebuild
			// v1/name-only saves — several recipes silently stopped resolving).
			'name'     => $post->post_title,
			'category' => $category,
		);
	}

	return $catalogue;
}

/**
 * Nutrition + ingredients + method, keyed by slug — the CPT-meta equivalent
 * of the old hand-mirrored recipe-data.php.
 *
 * @return array<string, array{servings:int, prep:int, cook:int, nutrition:array<string,int>, ingredients:array, instructions:array<int,string>}>
 */
function vance_recipe_data() {
	static $data = null;
	if ( null !== $data ) {
		return $data;
	}

	$data = array();
	foreach ( vance_recipe_all_posts() as $post ) {
		$ingredients = get_post_meta( $post->ID, '_vance_recipe_ingredients', true );
		$method      = get_post_meta( $post->ID, '_vance_recipe_method', true );

		$data[ $post->post_name ] = array(
			'servings'     => (int) get_post_meta( $post->ID, '_vance_recipe_servings', true ),
			'prep'         => (int) get_post_meta( $post->ID, '_vance_recipe_prep_min', true ),
			'cook'         => (int) get_post_meta( $post->ID, '_vance_recipe_cook_min', true ), // '' (unset, no-cook recipes) casts to 0.
			'nutrition'    => array(
				'calories' => (int) get_post_meta( $post->ID, '_vance_recipe_kcal', true ),
				'protein'  => (int) get_post_meta( $post->ID, '_vance_recipe_protein_g', true ),
				'carbs'    => (int) get_post_meta( $post->ID, '_vance_recipe_carbs_g', true ),
				'fat'      => (int) get_post_meta( $post->ID, '_vance_recipe_fat_g', true ),
				'fibre'    => (int) get_post_meta( $post->ID, '_vance_recipe_fibre_g', true ),
			),
			'ingredients'  => is_array( $ingredients ) ? $ingredients : array(),
			'instructions' => is_array( $method ) ? $method : array(),
		);
	}

	return $data;
}

/**
 * Normalise a recipe title for name-based matching.
 *
 * Saved meal plans predating slug capture recorded only the display name, so
 * the name is the only join key available for that history. Stripping case,
 * punctuation and whitespace makes the match tolerant of the ampersand /
 * en-dash / smart-quote drift that creeps in through the DOM.
 *
 * @param string $name
 * @return string
 */
function vance_recipe_name_key( $name ) {
	$name = (string) $name;
	// Normalise the dash and ampersand families before stripping punctuation,
	// so "Tuna & Red Lentil" and "Tuna and Red Lentil" collapse together.
	$name = str_ireplace( array( '&amp;', '&', ' and ' ), ' ', $name );
	$name = strtolower( $name );
	return preg_replace( '/[^a-z0-9]+/', '', $name );
}

/**
 * Name-key → slug index, built once per request from the catalogue.
 *
 * @return array<string, string>
 */
function vance_recipe_name_index() {
	static $index = null;
	if ( null !== $index ) {
		return $index;
	}
	$index = array();
	foreach ( vance_recipe_catalogue() as $slug => $recipe ) {
		$index[ vance_recipe_name_key( $recipe['name'] ) ] = $slug;
	}
	return $index;
}

/**
 * Resolve a saved meal row to a catalogue slug.
 *
 * Prefers the slug the extractor captured; falls back to a name match so plans
 * saved before slug capture still get thumbnails and links.
 *
 * @param string $slug Slug from the saved payload (may be empty).
 * @param string $name Recipe display name from the saved payload.
 * @return string Catalogue slug, or '' if this meal is not a known recipe.
 */
function vance_recipe_resolve_slug( $slug, $name = '' ) {
	$catalogue = vance_recipe_catalogue();

	$slug = sanitize_key( (string) $slug );
	if ( $slug && isset( $catalogue[ $slug ] ) ) {
		return $slug;
	}

	$index = vance_recipe_name_index();
	$key   = vance_recipe_name_key( $name );

	return ( $key && isset( $index[ $key ] ) ) ? $index[ $key ] : '';
}

/**
 * Thumbnail URL for a recipe — the CPT's native featured image.
 *
 * @param string $slug Catalogue slug.
 * @return string Absolute URL, or '' for an unknown slug or one with no
 *                featured image set.
 */
function vance_recipe_image_url( $slug ) {
	$catalogue = vance_recipe_catalogue();
	if ( ! isset( $catalogue[ $slug ] ) ) {
		return '';
	}
	$url = get_the_post_thumbnail_url( $catalogue[ $slug ]['id'], 'large' );
	return $url ? $url : '';
}

/**
 * Public URL for a single recipe — the native single-vance_recipe.php page.
 *
 * @param string $slug Catalogue slug.
 * @return string Absolute URL, or '' for an unknown slug.
 */
function vance_recipe_url( $slug ) {
	$catalogue = vance_recipe_catalogue();
	if ( ! isset( $catalogue[ $slug ] ) ) {
		return '';
	}
	return get_permalink( $catalogue[ $slug ]['id'] );
}

/**
 * Expand one saved meal row into everything a view needs to render it.
 *
 * Saved rows are {slot, name, calories, minutes, image, slug}; `slug` and a
 * usable `image` are both absent from older saves. This fills the gaps from the
 * catalogue and always returns the same shape, so templates and the JSON handed
 * to the viewer/PDF never have to branch on save vintage.
 *
 * @param array $meal One entry from a saved plan's days[].meals[].
 * @return array{slot:string, name:string, calories:string, minutes:string, slug:string, image:string, url:string}
 */
function vance_recipe_expand_meal( $meal ) {
	$meal = is_array( $meal ) ? $meal : array();

	$name = isset( $meal['name'] ) ? (string) $meal['name'] : '';
	$slug = vance_recipe_resolve_slug(
		isset( $meal['slug'] ) ? $meal['slug'] : '',
		$name
	);

	// A slug-derived image is authoritative; the payload's own `image` is only
	// used as a fallback for slugs the catalogue has never heard of.
	$image = $slug ? vance_recipe_image_url( $slug ) : '';
	if ( '' === $image && ! empty( $meal['image'] ) ) {
		$image = esc_url_raw( (string) $meal['image'] );
	}

	// Nutrition and timings come from the catalogue when the slug is known: the
	// saved payload only ever captured calories and total minutes, and older
	// saves captured neither.
	$data     = vance_recipe_data();
	$facts    = ( $slug && isset( $data[ $slug ] ) ) ? $data[ $slug ] : array();
	$calories = isset( $meal['calories'] ) && $meal['calories'] ? (int) $meal['calories'] : 0;
	if ( ! $calories && isset( $facts['nutrition']['calories'] ) ) {
		$calories = (int) $facts['nutrition']['calories'];
	}
	$minutes = isset( $meal['minutes'] ) && $meal['minutes'] ? (int) $meal['minutes'] : 0;
	if ( ! $minutes && isset( $facts['prep'] ) ) {
		$minutes = (int) $facts['prep'] + (int) $facts['cook'];
	}

	return array(
		'slot'      => isset( $meal['slot'] ) ? (string) $meal['slot'] : '',
		'name'      => $name,
		'calories'  => $calories ? (string) $calories : '',
		'minutes'   => $minutes ? (string) $minutes : '',
		'slug'      => $slug,
		'image'     => $image,
		'url'       => $slug ? vance_recipe_url( $slug ) : '',
		'nutrition' => isset( $facts['nutrition'] ) ? $facts['nutrition'] : array(),
		'servings'  => isset( $facts['servings'] ) ? (int) $facts['servings'] : 0,
	);
}

/**
 * Consolidated shopping list for a saved plan.
 *
 * Ingredients are free-text lines ("2 salmon fillets", "1 tbsp olive oil"), so
 * quantities cannot be summed reliably across recipes — "½ cup" and "100 g" of
 * the same thing have no common unit here. Rather than fake the arithmetic,
 * identical lines are collapsed and carry a ×N multiplier telling the user how
 * many times that quantity is needed across the week. That is honest and still
 * does the job a shopping list is for.
 *
 * @param array $days Expanded days from vance_recipe_expand_plan().
 * @return array<int, array{item:string, count:int}> Alphabetical.
 */
function vance_recipe_shopping_list( $days ) {
	$data   = vance_recipe_data();
	$counts = array();

	foreach ( (array) $days as $day ) {
		foreach ( (array) ( isset( $day['meals'] ) ? $day['meals'] : array() ) as $meal ) {
			$slug = isset( $meal['slug'] ) ? $meal['slug'] : '';
			if ( ! $slug || ! isset( $data[ $slug ]['ingredients'] ) ) {
				continue;
			}
			foreach ( $data[ $slug ]['ingredients'] as $section ) {
				foreach ( (array) ( isset( $section['items'] ) ? $section['items'] : array() ) as $item ) {
					$item = trim( (string) $item );
					if ( '' === $item ) {
						continue;
					}
					// Seasoning lines are store-cupboard staples written a dozen
					// different ways ("Pinch of sea salt", "Salt and black pepper,
					// to taste", "Sea salt and black pepper"). None of them tells
					// anyone to buy anything, and left in they dominate the list —
					// a 7-day plan produced nine separate salt entries. Drop any
					// line that is only salt/pepper plus filler words.
					$bare = preg_replace(
						'/\b(pinch|of|a|the|to|taste|freshly|ground|cracked|flaky|coarse|fine|sea|kosher|table|black|white|and|or|optional|plus|more|extra|season(ing|ed)?)\b/i',
						'',
						$item
					);
					$bare = trim( preg_replace( '/[^a-z]+/i', '', $bare ) );
					if ( '' === $bare || in_array( strtolower( $bare ), array( 'salt', 'pepper', 'saltpepper' ), true ) ) {
						continue;
					}
					$key = strtolower( $item );
					if ( isset( $counts[ $key ] ) ) {
						$counts[ $key ]['count']++;
					} else {
						$counts[ $key ] = array(
							'item'  => $item,
							'count' => 1,
						);
					}
				}
			}
		}
	}

	ksort( $counts );
	return array_values( $counts );
}

/**
 * Photographer attribution for a recipe's featured image, keyed by slug —
 * sourced from the CPT's photo-credit meta (inc/recipe-admin.php) rather
 * than the old assets/img/recipes/attribution.json.
 *
 * @return array<string, array{author:string, author_url:string}>
 */
function vance_recipe_attributions() {
	static $data = null;
	if ( null !== $data ) {
		return $data;
	}

	$data = array();
	foreach ( vance_recipe_all_posts() as $post ) {
		$author = get_post_meta( $post->ID, '_vance_recipe_credit_author', true );
		if ( ! $author ) {
			continue;
		}
		$data[ $post->post_name ] = array(
			'author'     => $author,
			'author_url' => get_post_meta( $post->ID, '_vance_recipe_credit_author_url', true ),
		);
	}
	return $data;
}

/**
 * Distinct photographers behind a given set of recipe slugs.
 *
 * Deduplicated by name and sorted, so a meal plan that uses one photographer's
 * work twice credits them once. Passing an empty array credits everyone, which
 * is what a view showing the whole catalogue needs.
 *
 * @param string[] $slugs Recipe slugs, or empty for all.
 * @return array<int, array{author:string, author_url:string}>
 */
function vance_recipe_credits( $slugs = array() ) {
	$all   = vance_recipe_attributions();
	$slugs = array_filter( (array) $slugs );
	$out   = array();

	foreach ( $all as $slug => $meta ) {
		if ( $slugs && ! in_array( $slug, $slugs, true ) ) {
			continue;
		}
		$author = isset( $meta['author'] ) ? trim( (string) $meta['author'] ) : '';
		if ( '' === $author || isset( $out[ $author ] ) ) {
			continue;
		}
		$out[ $author ] = array(
			'author'     => $author,
			'author_url' => isset( $meta['author_url'] ) ? (string) $meta['author_url'] : '',
		);
	}

	ksort( $out );
	return array_values( $out );
}

/**
 * The credit line itself — deliberately near-invisible.
 *
 * Small and low-contrast by request: it satisfies the licence without competing
 * with the content. Names link to the photographer's profile, which is the part
 * the licence actually cares about; `$linked = false` renders plain text for
 * contexts with no working links, such as the PDF.
 *
 * @param string[] $slugs  Recipe slugs in view, or empty for all.
 * @param bool     $linked Whether to link photographer names.
 * @return string Escaped HTML, or '' when there is nothing to credit.
 */
function vance_recipe_credit_line( $slugs = array(), $linked = true ) {
	$credits = vance_recipe_credits( $slugs );
	if ( ! $credits ) {
		return '';
	}

	$names = array();
	foreach ( $credits as $c ) {
		if ( $linked && $c['author_url'] ) {
			$names[] = '<a href="' . esc_url( $c['author_url'] ) . '" target="_blank" rel="noopener nofollow" style="color:inherit; text-decoration:none;">'
				. esc_html( $c['author'] ) . '</a>';
		} else {
			$names[] = esc_html( $c['author'] );
		}
	}

	return 'Photography: ' . implode( ', ', $names ) . ' / Unsplash';
}

/**
 * Full method + ingredients for every distinct recipe used in a plan.
 *
 * Keyed by slug and returned once per recipe rather than inlined on each meal
 * row: a 7-day plan repeats recipes freely, and the payload this feeds is
 * already the largest thing on the page. Twenty-eight copies of an ingredient
 * list and an eight-step method is several hundred KB of duplicated JSON for
 * no gain — the meal rows already carry `slug`, so the PDF looks the detail up.
 *
 * Order follows first appearance in the plan, so the PDF's recipe appendix
 * reads in the order the week is cooked.
 *
 * @param array $days Expanded days from vance_recipe_expand_plan().
 * @return array<string, array{name:string, image:string, url:string, servings:int, prep:int, cook:int, ingredients:array, instructions:array<int,string>}>
 */
function vance_recipe_plan_recipes( $days ) {
	$data = vance_recipe_data();
	$cat  = vance_recipe_catalogue();
	$out  = array();

	foreach ( (array) $days as $day ) {
		foreach ( (array) ( isset( $day['meals'] ) ? $day['meals'] : array() ) as $meal ) {
			$slug = isset( $meal['slug'] ) ? (string) $meal['slug'] : '';
			// No slug means the catalogue never matched this row (a hand-typed
			// or retired recipe). There is nothing to look up, so skip it — the
			// meal still renders in the day schedule, just without a method.
			if ( '' === $slug || isset( $out[ $slug ] ) || ! isset( $data[ $slug ] ) ) {
				continue;
			}
			$facts = $data[ $slug ];

			$out[ $slug ] = array(
				'name'         => isset( $cat[ $slug ]['name'] ) ? (string) $cat[ $slug ]['name'] : (string) $meal['name'],
				'image'        => isset( $meal['image'] ) ? (string) $meal['image'] : '',
				'url'          => vance_recipe_url( $slug ),
				'servings'     => isset( $facts['servings'] ) ? (int) $facts['servings'] : 0,
				'prep'         => isset( $facts['prep'] ) ? (int) $facts['prep'] : 0,
				'cook'         => isset( $facts['cook'] ) ? (int) $facts['cook'] : 0,
				'ingredients'  => isset( $facts['ingredients'] ) ? $facts['ingredients'] : array(),
				'instructions' => isset( $facts['instructions'] ) ? array_values( (array) $facts['instructions'] ) : array(),
			);
		}
	}

	return $out;
}

/**
 * Expand a whole saved plan's days[] for the viewer and the PDF.
 *
 * Also computes the per-plan hero image (first meal that resolves to a picture)
 * and the totals line, so the template, the modal and the PDF all agree instead
 * of each recomputing them slightly differently.
 *
 * @param array $days Saved plan days[].
 * @return array{days:array[], image:string, totals:array{days:int, meals:int, calories:int, minutes:int}}
 */
function vance_recipe_expand_plan( $days ) {
	$out   = array();
	$hero  = '';
	$meals = 0;
	$kcal  = 0;
	$mins  = 0;

	foreach ( (array) $days as $day ) {
		if ( ! is_array( $day ) || empty( $day['day'] ) ) {
			continue;
		}
		$rows     = array();
		$day_kcal = 0;
		foreach ( (array) ( isset( $day['meals'] ) ? $day['meals'] : array() ) as $meal ) {
			$row = vance_recipe_expand_meal( $meal );
			if ( '' === $row['name'] ) {
				continue;
			}
			if ( '' === $hero && '' !== $row['image'] ) {
				$hero = $row['image'];
			}
			$meals++;
			$day_kcal += (int) $row['calories'];
			$mins     += (int) $row['minutes'];
			$rows[]    = $row;
		}
		// The day header's own kcal figure is authoritative when the planner
		// rendered one; summing the meals is the fallback for rows where only
		// the per-meal figures came through.
		$day_total = ( isset( $day['calories'] ) && $day['calories'] ) ? (int) $day['calories'] : $day_kcal;
		$kcal     += $day_total;

		$out[] = array(
			'day'      => (string) $day['day'],
			'calories' => $day_total,
			'meals'    => $rows,
		);
	}

	return array(
		'days'   => $out,
		'image'  => $hero,
		'totals' => array(
			'days'     => count( $out ),
			'meals'    => $meals,
			'calories' => $kcal,
			'minutes'  => $mins,
		),
	);
}
