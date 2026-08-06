<?php
/**
 * Recipe catalogue — the theme-side mirror of the IBD Recipes bundle's data.
 *
 * WHY THIS EXISTS
 * ---------------
 * The recipe data lives inside a Vite/Next build artifact
 * (`assets/tools/ibd-recipes/_next/static/chunks/*.js`) which the theme must not
 * depend on parsing at runtime, and which gets text-patched in place rather than
 * rebuilt (CLAUDE.md constraint 6). But the dashboard needs three things the
 * saved meal-plan payload does not carry:
 *
 *   1. a thumbnail for each meal,
 *   2. a link to the full recipe,
 *   3. a way to resolve BOTH new saves (which record a slug) and old saves
 *      (which only ever recorded the recipe's display name).
 *
 * So the slug → {name, image} mapping is mirrored here, in version control,
 * where it can be read by PHP without touching the bundle.
 *
 * KEEPING IT IN SYNC
 * ------------------
 * If recipes are added/renamed in the bundle, re-derive this list from
 * `_next/static/chunks/` — every entry there is `id:"…",name:"…",category:"…"
 * … image:"…"`. A slug present in a saved plan but missing here degrades
 * gracefully (no thumbnail, no link) rather than erroring.
 *
 * IMAGES
 * ------
 * `vance_recipe_image_url()` prefers a locally hosted file at
 * `assets/img/recipes/<slug>.(webp|jpg|jpeg|png)` and only falls back to the
 * bundle's remote URL when no local file exists. Local files are strongly
 * preferred: the remote Unsplash URLs are hotlinks with no guarantee of
 * permanence — one of them (tuna-lentil-pasta-salad) is already a hard 404 —
 * and remote images make PDF rendering dependent on a third party's CORS
 * headers.
 *
 * A visual check of all 19 remote images against their recipes (2026-08-05)
 * found only 4 correct: a seabass recipe illustrated with bacon-loaded fries,
 * energy balls with chocolate-chip cookies, granola with an orange splashing
 * into water, and so on. All 19 were therefore replaced with locally hosted
 * photos, each one looked at before it was committed. The `audit` note on every
 * entry records what the old remote image was, so the history is not lost.
 *
 * The `remote` URLs are kept only as a fallback for a missing local file. Note
 * that tuna-lentil-pasta-salad's remote is dead, so deleting that local file
 * would reintroduce a broken image rather than degrade to a working one.
 *
 * Attribution for the local files lives in assets/img/recipes/attribution.json.
 * The Unsplash API licence requires the photographer to be credited wherever
 * the photos appear — that credit is NOT yet rendered anywhere on the site.
 *
 * @package sla-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Nutrition + ingredients, machine-derived from the bundle. Kept in its own
// file so this one stays hand-authored and reviewable.
require_once __DIR__ . '/recipe-data.php';

/**
 * The full recipe list, keyed by slug (the bundle's `id`).
 *
 * @return array<string, array{name:string, category:string, remote:string, audit:string}>
 */
function vance_recipe_catalogue() {
	static $catalogue = null;
	if ( null !== $catalogue ) {
		return $catalogue;
	}

	$u = 'https://images.unsplash.com/';

	$catalogue = array(
		// --- Breakfast ---
		'blueberry-chia-pudding'     => array(
			'name'     => 'Anti-Inflammatory Blueberry Chia Pudding',
			'category' => 'breakfast',
			'remote'   => $u . 'photo-1511690656952-34342bb7c2f2?w=800&q=80',
			'audit'    => 'replaced', // was wrong — shows a chickpea & olive salad bowl
		),
		'blueberry-almond-smoothie'  => array(
			'name'     => 'Blueberry Almond Anti-Inflammatory Smoothie',
			'category' => 'breakfast',
			'remote'   => $u . 'photo-1502741224143-90386d7f8c82?w=800&q=80',
			'audit'    => 'replaced', // was wrong — shows a strawberry smoothie
		),
		'gf-protein-pancakes'        => array(
			'name'     => 'Gluten-Free High-Protein Pancakes',
			'category' => 'breakfast',
			'remote'   => $u . 'photo-1567620905732-2d1ec7ab7445?w=800&q=80',
			'audit'    => 'replaced', // was already correct
		),
		'gf-protein-granola'         => array(
			'name'     => 'Gluten-Free High-Protein High-Fibre Granola',
			'category' => 'breakfast',
			'remote'   => $u . 'photo-1517093728432-a0440f8d45af?w=800&q=80',
			'audit'    => 'replaced', // was wrong — shows an orange splashing into blue water
		),
		'harissa-eggs-avocado'       => array(
			'name'     => 'Harissa Fried Eggs & Avocado on Sourdough',
			'category' => 'breakfast',
			'remote'   => $u . 'photo-1525351484163-7529414344d8?w=800&q=80',
			'audit'    => 'replaced', // was already correct
		),
		'mango-ginger-smoothie'      => array(
			'name'     => 'Mango Ginger Gut-Friendly Smoothie',
			'category' => 'breakfast',
			'remote'   => $u . 'photo-1553530666-ba11a7da3888?w=800&q=80',
			'audit'    => 'replaced', // was wrong — shows a deep-purple berry smoothie
		),
		'strawberry-chia-smoothie'   => array(
			'name'     => 'Strawberry Chia Breakfast Smoothie',
			'category' => 'breakfast',
			'remote'   => $u . 'photo-1570696516188-ade861b84a49?w=800&q=80',
			'audit'    => 'replaced', // was weak — pink berry smoothie, but raspberries not strawberries
		),

		// --- Lunch ---
		'crispy-chickpea-salad'      => array(
			'name'     => 'Crispy Chickpea & Avocado Salad',
			'category' => 'lunch',
			'remote'   => $u . 'photo-1512621776951-a57141f2eefd?w=800&q=80',
			'audit'    => 'replaced', // was already correct
		),
		'sardine-avocado-bowl'       => array(
			'name'     => 'Mediterranean Sardine & Avocado Bowl',
			'category' => 'lunch',
			'remote'   => $u . 'photo-1546069901-ba9599a7e63c?w=800&q=80',
			'audit'    => 'replaced', // was weak — a poke-style bowl; no sardines and no avocado
		),
		'tuna-lentil-pasta-salad'    => array(
			'name'     => 'Tuna & Red Lentil Pasta Salad',
			'category' => 'lunch',
			'remote'   => $u . 'photo-1473093226555-0b6efd8b61f3?w=800&q=80',
			'audit'    => 'replaced', // was a hard 404 — rendered as a broken image on the live site
		),

		// --- Dinner ---
		'ginger-chicken-stir-fry'    => array(
			'name'     => 'Ginger Chicken & Vegetable Stir Fry',
			'category' => 'dinner',
			'remote'   => $u . 'photo-1603133872878-684f208fb84b?w=800&q=80',
			'audit'    => 'replaced', // was weak — a plate of fried rice, not a stir fry
		),
		'chicken-tacos-avocado-slaw' => array(
			'name'     => 'Gluten-Free Chicken Tacos with Avocado Slaw',
			'category' => 'dinner',
			'remote'   => $u . 'photo-1565299585323-38d6b0865b47?w=800&q=80',
			'audit'    => 'replaced', // was weak — tacos, but chickpea & sweet potato — no chicken
		),
		'lemon-herb-salmon'          => array(
			'name'     => 'Lemon Herb Salmon with Greens',
			'category' => 'dinner',
			'remote'   => $u . 'photo-1467003909585-2f8a72700288?w=800&q=80',
			'audit'    => 'replaced', // was already correct
		),
		'mediterranean-lentil-bowl'  => array(
			'name'     => 'Mediterranean Lentil & Roasted Vegetable Bowl',
			'category' => 'dinner',
			'remote'   => $u . 'photo-1540189549336-e6e99c3679fe?w=800&q=80',
			'audit'    => 'replaced', // was wrong — green salad and a glass of orange juice; no lentils
		),
		'mediterranean-seabass'      => array(
			'name'     => 'Mediterranean Seabass with Roasted Vegetables',
			'category' => 'dinner',
			'remote'   => $u . 'photo-1485962398705-ef6a13c41e8f?w=800&q=80',
			'audit'    => 'replaced', // was wrong — bacon-and-cheese loaded fries
		),
		'sweet-potato-ginger-soup'   => array(
			'name'     => 'Sweet Potato & Ginger Soup',
			'category' => 'dinner',
			'remote'   => $u . 'photo-1547592180-85f173990554?w=800&q=80',
			'audit'    => 'replaced', // was wrong — a rice bowl with green beans; no soup
		),

		// --- Snacks ---
		'apple-almond-butter-plate'  => array(
			'name'     => 'Apple Almond Butter Snack Plate',
			'category' => 'snacks',
			'remote'   => $u . 'photo-1568702846914-96b305d2aaeb?w=800&q=80',
			'audit'    => 'replaced', // was weak — a single bare apple on a grey backdrop
		),
		'date-nut-energy-balls'      => array(
			'name'     => 'Protein Date & Nut Energy Balls',
			'category' => 'snacks',
			'remote'   => $u . 'photo-1558961363-fa8fdf82db35?w=800&q=80',
			'audit'    => 'replaced', // was wrong — a bowl of chocolate chip cookies
		),
		'turmeric-roasted-chickpeas' => array(
			'name'     => 'Turmeric Roasted Chickpeas',
			'category' => 'snacks',
			'remote'   => $u . 'photo-1498837167922-ddd27525d352?w=800&q=80',
			'audit'    => 'replaced', // was wrong — a salad-bar spread of raw ingredients
		),
	);

	return $catalogue;
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
 * Thumbnail URL for a recipe.
 *
 * A locally hosted file always wins over the bundle's remote hotlink — see the
 * file header for why. Returns '' for an unknown slug so callers can render a
 * placeholder rather than a broken <img>.
 *
 * @param string $slug Catalogue slug.
 * @return string Absolute URL, or ''.
 */
function vance_recipe_image_url( $slug ) {
	static $local = null;

	$catalogue = vance_recipe_catalogue();
	if ( ! isset( $catalogue[ $slug ] ) ) {
		return '';
	}

	// Scan the local override directory once, not once per meal — a 7-day plan
	// renders up to 28 meals and this would otherwise be 28 stat() storms.
	if ( null === $local ) {
		$local = array();
		$dir   = get_template_directory() . '/assets/img/recipes';
		if ( is_dir( $dir ) ) {
			foreach ( (array) glob( $dir . '/*.{webp,jpg,jpeg,png}', GLOB_BRACE ) as $path ) {
				$file = basename( $path );
				$key  = preg_replace( '/\.[^.]+$/', '', $file );
				// First extension wins in glob order (webp before jpg), which is
				// the preference we want anyway.
				if ( ! isset( $local[ $key ] ) ) {
					$local[ $key ] = get_template_directory_uri() . '/assets/img/recipes/' . $file;
				}
			}
		}
	}

	if ( isset( $local[ $slug ] ) ) {
		return $local[ $slug ];
	}

	return $catalogue[ $slug ]['remote'];
}

/**
 * Public URL for a single recipe, for opening in a new tab.
 *
 * Points at the WP wrapper page (`page-ibd-recipies.php`) with a `recipe` query
 * arg rather than at the raw bundle path under /wp-content/, so the recipe
 * opens inside the site chrome with the brand CSS applied. The wrapper
 * validates the slug against this same catalogue before building an iframe URL
 * from it.
 *
 * @param string $slug Catalogue slug.
 * @return string Absolute URL, or '' for an unknown slug.
 */
function vance_recipe_url( $slug ) {
	if ( ! isset( vance_recipe_catalogue()[ $slug ] ) ) {
		return '';
	}
	return home_url( '/ibd-recipies/?recipe=' . rawurlencode( $slug ) );
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
