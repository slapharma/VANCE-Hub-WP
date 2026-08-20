<?php
/**
 * Recipe CPT frontend — rendering helpers, JSON-LD, and the legacy-URL
 * redirects that keep old iframe-tool deep links working once the CPT goes
 * public in Phase 2.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the nutrition-facts panel for a recipe post.
 *
 * @param int $post_id
 * @return string Escaped HTML.
 */
function vance_recipe_nutrition_panel_html( $post_id ) {
	$kcal    = get_post_meta( $post_id, '_vance_recipe_kcal', true );
	$protein = get_post_meta( $post_id, '_vance_recipe_protein_g', true );
	$carbs   = get_post_meta( $post_id, '_vance_recipe_carbs_g', true );
	$fat     = get_post_meta( $post_id, '_vance_recipe_fat_g', true );
	$fibre   = get_post_meta( $post_id, '_vance_recipe_fibre_g', true );
	$epa     = get_post_meta( $post_id, '_vance_recipe_epa_mg', true );

	$macros = array(
		array( 'Protein', $protein, 'g' ),
		array( 'Carbs', $carbs, 'g' ),
		array( 'Fat', $fat, 'g' ),
		array( 'Fibre', $fibre, 'g' ),
	);
	if ( '' !== $epa ) {
		$macros[] = array( 'EPA', $epa, 'mg' );
	}

	ob_start();
	?>
	<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
		<h3 style="margin:0 0 4px;font-family:'Outfit',sans-serif;font-size:15px;font-weight:800;color:#0A1929;text-transform:uppercase;letter-spacing:0.4px;">Nutrition</h3>
		<p style="margin:0 0 16px;font-size:12.5px;color:#94a3b8;">Per serving</p>
		<?php if ( '' !== $kcal ) : ?>
		<div style="text-align:center;padding:12px 0 18px;border-bottom:1px solid #e2e8f0;margin-bottom:16px;">
			<div style="font-size:38px;font-weight:900;color:var(--primary-color);font-family:'Outfit',sans-serif;line-height:1;"><?php echo esc_html( $kcal ); ?></div>
			<div style="font-size:12.5px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-top:6px;">Calories (kcal)</div>
		</div>
		<?php endif; ?>
		<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;">
			<?php foreach ( $macros as $m ) :
				if ( '' === $m[1] ) {
					continue;
				}
				?>
				<div style="background:#f8fafc;border-radius:8px;padding:10px 12px;text-align:center;">
					<div style="font-size:18px;font-weight:800;color:#0A1929;"><?php echo esc_html( $m[1] ); ?><span style="font-size:12px;font-weight:600;color:#94a3b8;"><?php echo esc_html( $m[2] ); ?></span></div>
					<div style="font-size:11.5px;color:#64748b;text-transform:uppercase;letter-spacing:0.3px;margin-top:2px;"><?php echo esc_html( $m[0] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * The small, near-invisible Unsplash credit line for a recipe's featured
 * image, sourced from CPT meta (native replacement for
 * vance_recipe_credit_line() in recipe-catalogue.php, which reads the old
 * attribution.json keyed by legacy slug).
 *
 * @param int $post_id
 * @return string Escaped HTML, or '' if no credit is recorded.
 */
function vance_recipe_credit_line_html( $post_id ) {
	$author = get_post_meta( $post_id, '_vance_recipe_credit_author', true );
	if ( ! $author ) {
		return '';
	}
	$author_url = get_post_meta( $post_id, '_vance_recipe_credit_author_url', true );

	$name = $author_url
		? '<a href="' . esc_url( $author_url ) . '" target="_blank" rel="noopener nofollow" style="color:inherit;text-decoration:none;">' . esc_html( $author ) . '</a>'
		: esc_html( $author );

	return 'Photography: ' . $name . ' / Unsplash';
}

/**
 * schema.org Recipe JSON-LD for a recipe post.
 *
 * @param int $post_id
 * @return string <script type="application/ld+json"> tag, or '' if the
 *                recipe is missing the minimum fields schema.org requires.
 */
function vance_recipe_json_ld( $post_id ) {
	$name = get_the_title( $post_id );
	if ( ! $name ) {
		return '';
	}

	$ingredients = get_post_meta( $post_id, '_vance_recipe_ingredients', true );
	$method      = get_post_meta( $post_id, '_vance_recipe_method', true );

	$ingredient_lines = array();
	foreach ( (array) $ingredients as $section ) {
		foreach ( (array) ( isset( $section['items'] ) ? $section['items'] : array() ) as $item ) {
			$ingredient_lines[] = (string) $item;
		}
	}

	$steps = array();
	foreach ( (array) $method as $i => $step ) {
		$steps[] = array(
			'@type' => 'HowToStep',
			'text'  => (string) $step,
		);
	}

	$data = array(
		'@context'          => 'https://schema.org/',
		'@type'             => 'Recipe',
		'name'              => $name,
		'url'               => get_permalink( $post_id ),
	);

	$excerpt = get_the_excerpt( $post_id );
	if ( $excerpt ) {
		$data['description'] = $excerpt;
	}
	if ( has_post_thumbnail( $post_id ) ) {
		$data['image'] = array( get_the_post_thumbnail_url( $post_id, 'full' ) );
	}

	$prep = get_post_meta( $post_id, '_vance_recipe_prep_min', true );
	$cook = get_post_meta( $post_id, '_vance_recipe_cook_min', true );
	if ( '' !== $prep ) {
		$data['prepTime'] = 'PT' . (int) $prep . 'M';
	}
	if ( '' !== $cook ) {
		$data['cookTime'] = 'PT' . (int) $cook . 'M';
	}
	if ( '' !== $prep || '' !== $cook ) {
		$data['totalTime'] = 'PT' . ( (int) $prep + (int) $cook ) . 'M';
	}

	$servings = get_post_meta( $post_id, '_vance_recipe_servings', true );
	if ( '' !== $servings ) {
		$data['recipeYield'] = (string) (int) $servings;
	}

	$kcal = get_post_meta( $post_id, '_vance_recipe_kcal', true );
	if ( '' !== $kcal ) {
		$data['nutrition'] = array(
			'@type'    => 'NutritionInformation',
			'calories' => $kcal . ' calories',
		);
	}

	$terms = get_the_terms( $post_id, 'vance_recipe_cat' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$data['recipeCategory'] = $terms[0]->name;
	}

	if ( $ingredient_lines ) {
		$data['recipeIngredient'] = $ingredient_lines;
	}
	if ( $steps ) {
		$data['recipeInstructions'] = $steps;
	}

	return '<script type="application/ld+json">' . wp_json_encode( $data ) . '</script>';
}

/**
 * Legacy-URL redirects, active regardless of VANCE_RECIPE_PUBLIC (once a
 * recipe is a real page it should always be reachable at its canonical URL —
 * these just retarget the OLD iframe-tool deep links at it):
 *
 *  - /ibd-recipies/?recipe=<slug>  → 301 → /recipes/<slug>/
 *    (the pre-rebuild deep link into the Next.js bundle's per-recipe export)
 *  - /recipes/ (bare, no slug)     → 301 → the hub page
 */
function vance_recipe_legacy_redirects() {
	// Matched by REQUEST_URI path rather than is_page( 'ibd-recipies' ): that
	// Page doesn't exist any more (see page-ibd-recipies.php's header comment)
	// and never will again, so an is_page() check would only ever match a 404
	// and this branch would silently never fire for the old bookmarked links
	// it exists to catch.
	$path = trim( (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );

	if ( 'ibd-recipies' === $path && isset( $_GET['recipe'] ) ) {
		$slug = sanitize_title( wp_unslash( $_GET['recipe'] ) );
		$post = $slug ? get_page_by_path( $slug, OBJECT, 'vance_recipe' ) : null;
		if ( $post && 'publish' === $post->post_status ) {
			wp_safe_redirect( get_permalink( $post ), 301 );
			exit;
		}
		wp_safe_redirect( home_url( '/gastro-meal-planner/' ), 301 ); // Unknown slug: send to the hub instead of 404ing.
		exit;
	}

	if ( is_post_type_archive( 'vance_recipe' ) || ( is_404() && 'recipes' === $path ) ) {
		wp_safe_redirect( home_url( '/gastro-meal-planner/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'vance_recipe_legacy_redirects' );
