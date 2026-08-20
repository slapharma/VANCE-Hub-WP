<?php
/**
 * `wp vance recipes convert` — one-time conversion of the 19 "Gastro Recipes"
 * draft Posts (IDs 2991-3027) into native `vance_recipe` CPT entries.
 *
 * The draft bodies are only the source of truth for three things: the intro
 * sentence, the "why this works" paragraph, and the dietary-tag line. Every
 * other field (servings, prep/cook, nutrition, ingredients, method, photo
 * credit) already exists as reviewed, structured data in recipe-data.php,
 * recipe-catalogue.php and attribution.json — so those are used directly,
 * and the HTML is only PARSED so it can be diffed against that structured
 * data as a cross-check. Idempotent: a slug already converted to
 * `vance_recipe` is skipped, not re-processed.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * One recipe with a known collection-style prefix on its dietary-tags line
 * that is not itself a dietary tag (2026-08-05 review, see plan notes).
 */
function vance_recipe_cli_tag_line_overrides() {
	return array(
		'blueberry-almond-smoothie' => array( 'drop_first' => 1 ),
	);
}

function vance_recipe_cli_extract_between( $html, $start_regex, $end_regex ) {
	if ( ! preg_match( $start_regex, $html, $m, PREG_OFFSET_CAPTURE ) ) {
		return null;
	}
	$rest = substr( $html, $m[0][1] + strlen( $m[0][0] ) );
	if ( preg_match( $end_regex, $rest, $m2, PREG_OFFSET_CAPTURE ) ) {
		return substr( $rest, 0, $m2[0][1] );
	}
	return $rest;
}

function vance_recipe_cli_extract_li( $html ) {
	preg_match_all( '/<li>(.*?)<\/li>/is', (string) $html, $m );
	return array_map(
		function ( $t ) {
			return trim( html_entity_decode( wp_strip_all_tags( $t ), ENT_QUOTES ) );
		},
		$m[1]
	);
}

/**
 * @return array [{section, items[]}, ...]
 */
function vance_recipe_cli_parse_html_sections( $html ) {
	$parts = preg_split( '/<h3>(.*?)<\/h3>/is', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( count( $parts ) < 2 ) {
		return array( array( 'section' => '', 'items' => vance_recipe_cli_extract_li( $parts[0] ) ) );
	}
	$sections = array();
	for ( $i = 1; $i < count( $parts ); $i += 2 ) {
		$title      = trim( html_entity_decode( wp_strip_all_tags( $parts[ $i ] ), ENT_QUOTES ) );
		$body       = isset( $parts[ $i + 1 ] ) ? $parts[ $i + 1 ] : '';
		$sections[] = array( 'section' => $title, 'items' => vance_recipe_cli_extract_li( $body ) );
	}
	return $sections;
}

/**
 * Parse one draft's post_content and cross-check it against recipe-data.php /
 * recipe-catalogue.php / attribution.json. Returns the parsed fields plus an
 * `errors` array — empty means the recipe is safe to convert.
 */
function vance_recipe_cli_parse_and_check( $slug, $content ) {
	$errors = array();
	$data   = vance_recipe_data();
	$cat    = vance_recipe_catalogue();
	$attr   = vance_recipe_attributions();

	if ( ! isset( $data[ $slug ], $cat[ $slug ] ) ) {
		return array( 'errors' => array( "no recipe-data.php / catalogue entry for slug '{$slug}'" ) );
	}
	$facts = $data[ $slug ];
	$meta  = $cat[ $slug ];

	// --- Structural landmarks ---
	if ( ! preg_match( '/<h2>(.*?)<\/h2>/is', $content, $m_intro ) ) {
		$errors[] = 'no intro <h2> found';
	}
	$intro_html = isset( $m_intro[1] ) ? trim( $m_intro[1] ) : '';

	$ingredients_html = vance_recipe_cli_extract_between( $content, '/<h2>\s*Ingredients\s*<\/h2>/i', '/<h2>\s*Method\s*<\/h2>/i' );
	$method_html      = vance_recipe_cli_extract_between( $content, '/<h2>\s*Method\s*<\/h2>/i', '/<h2>\s*Nutrition per serving\s*<\/h2>/i' );
	$nutrition_html   = vance_recipe_cli_extract_between( $content, '/<h2>\s*Nutrition per serving\s*<\/h2>/i', '/<h2>\s*Why this works for gut health\s*<\/h2>/i' );
	$why_html         = vance_recipe_cli_extract_between( $content, '/<h2>\s*Why this works for gut health\s*<\/h2>/i', '/<a\s+href="\/ibd-recipies\/|<small>/i' );

	foreach ( array( 'Ingredients' => $ingredients_html, 'Method' => $method_html, 'Nutrition per serving' => $nutrition_html, 'Why this works for gut health' => $why_html ) as $label => $val ) {
		if ( null === $val ) {
			$errors[] = "expected heading not found: {$label}";
		}
	}
	if ( $errors ) {
		return array( 'errors' => $errors ); // Structure is too broken to keep checking.
	}
	$why_html = trim( $why_html );

	// --- Ingredients cross-check ---
	$parsed_sections = vance_recipe_cli_parse_html_sections( $ingredients_html );
	$flatten         = function ( $sections ) {
		$out = array();
		foreach ( (array) $sections as $s ) {
			$name = isset( $s['section'] ) ? trim( (string) $s['section'] ) : '';
			foreach ( (array) ( isset( $s['items'] ) ? $s['items'] : array() ) as $item ) {
				$out[] = $name . '|' . trim( (string) $item );
			}
		}
		return $out;
	};
	$parsed_flat = $flatten( $parsed_sections );
	$data_flat   = $flatten( $facts['ingredients'] );
	if ( $parsed_flat !== $data_flat ) {
		$errors[] = sprintf(
			'ingredients mismatch — HTML has %d item(s), recipe-data.php has %d; first diff at %s',
			count( $parsed_flat ),
			count( $data_flat ),
			wp_json_encode( array_slice( array_diff( $parsed_flat, $data_flat ), 0, 1 ) )
		);
	}

	// --- Method cross-check ---
	$parsed_steps = vance_recipe_cli_extract_li( $method_html );
	$data_steps   = array_map( 'trim', (array) $facts['instructions'] );
	if ( $parsed_steps !== $data_steps ) {
		$errors[] = sprintf( 'method mismatch — HTML has %d step(s), recipe-data.php has %d', count( $parsed_steps ), count( $data_steps ) );
	}

	// --- Nutrition cross-check ---
	preg_match_all( '/<td>\s*([\d.]+)/i', $nutrition_html, $m_nut );
	$nut_vals = array_map( 'intval', $m_nut[1] );
	$expected_nut = array(
		(int) $facts['nutrition']['calories'],
		(int) $facts['nutrition']['protein'],
		(int) $facts['nutrition']['carbs'],
		(int) $facts['nutrition']['fat'],
		(int) $facts['nutrition']['fibre'],
	);
	if ( $nut_vals !== $expected_nut ) {
		$errors[] = 'nutrition mismatch — HTML: ' . wp_json_encode( $nut_vals ) . ' vs recipe-data.php: ' . wp_json_encode( $expected_nut );
	}

	// --- Prep / cook / serves / meal cross-check ---
	preg_match( '/<strong>Prep:<\/strong>\s*(\d+)\s*min/i', $content, $m_prep );
	preg_match( '/<strong>Cook:<\/strong>\s*(\d+)\s*min/i', $content, $m_cook );
	preg_match( '/<strong>Serves:<\/strong>\s*(\d+)/i', $content, $m_serves );
	preg_match( '/<strong>Meal:<\/strong>\s*([A-Za-z]+)/i', $content, $m_meal );

	$prep_min   = isset( $m_prep[1] ) ? (int) $m_prep[1] : null;
	$cook_min   = isset( $m_cook[1] ) ? (int) $m_cook[1] : 0; // Absent means no-cook.
	$serves     = isset( $m_serves[1] ) ? (int) $m_serves[1] : null;
	$meal_label = isset( $m_meal[1] ) ? trim( $m_meal[1] ) : '';

	if ( null === $prep_min || $prep_min !== (int) $facts['prep'] ) {
		$errors[] = "prep mismatch — HTML: " . var_export( $prep_min, true ) . ", recipe-data.php: {$facts['prep']}";
	}
	if ( $cook_min !== (int) $facts['cook'] ) {
		$errors[] = "cook mismatch — HTML: {$cook_min}, recipe-data.php: {$facts['cook']}";
	}
	if ( null === $serves || $serves !== (int) $facts['servings'] ) {
		$errors[] = "servings mismatch — HTML: " . var_export( $serves, true ) . ", recipe-data.php: {$facts['servings']}";
	}
	// "Meal:" is written singular ("Snack"); the catalogue category is plural
	// ("snacks") for that one case, so accept either form matching.
	$meal_norm = strtolower( $meal_label );
	if ( $meal_norm !== $meta['category'] && $meal_norm . 's' !== $meta['category'] ) {
		$errors[] = "meal/category mismatch — HTML: '{$meal_label}', catalogue: '{$meta['category']}'";
	}

	// --- Dietary tags line ---
	preg_match( '/<em>(.*?)<\/em>/is', $content, $m_em );
	$tag_line = isset( $m_em[1] ) ? trim( html_entity_decode( wp_strip_all_tags( $m_em[1] ), ENT_QUOTES ) ) : '';
	if ( '' === $tag_line ) {
		$errors[] = 'no dietary-tags <em> line found';
	}
	$tags = array_values( array_filter( array_map( 'trim', explode( '·', $tag_line ) ) ) );
	$override = vance_recipe_cli_tag_line_overrides();
	if ( isset( $override[ $slug ]['drop_first'] ) ) {
		$tags = array_slice( $tags, (int) $override[ $slug ]['drop_first'] );
	}

	// --- Photo credit cross-check ---
	preg_match(
		'/<small>Photo by <a href="([^"]+)"[^>]*>([^<]+)<\/a> on <a href="([^"]+)"[^>]*>Unsplash<\/a><\/small>/is',
		$content,
		$m_credit
	);
	if ( ! $m_credit ) {
		$errors[] = 'no photo credit <small> line found';
	} elseif ( ! isset( $attr[ $slug ] ) ) {
		$errors[] = "no attribution.json entry for slug '{$slug}'";
	} else {
		$a = $attr[ $slug ];
		if ( trim( $m_credit[2] ) !== trim( $a['author'] ) ) {
			$errors[] = "credit author mismatch — HTML: '{$m_credit[2]}', attribution.json: '{$a['author']}'";
		}
		if ( trim( $m_credit[1] ) !== trim( $a['author_url'] ) ) {
			$errors[] = "credit author_url mismatch — HTML: '{$m_credit[1]}', attribution.json: '{$a['author_url']}'";
		}
		if ( trim( $m_credit[3] ) !== trim( $a['photo_url'] ) ) {
			$errors[] = "credit source_url mismatch — HTML: '{$m_credit[3]}', attribution.json: '{$a['photo_url']}'";
		}
	}

	return array(
		'errors'      => $errors,
		'intro_html'  => $intro_html,
		'why_html'    => $why_html,
		'tags'        => $tags,
		'category'    => $meta['category'],
		'facts'       => $facts,
		'credit'      => isset( $attr[ $slug ] ) ? $attr[ $slug ] : array(),
	);
}

function vance_recipe_cli_convert( $args, $assoc_args ) {
	$dry_run  = isset( $assoc_args['dry-run'] );
	$catalogue = vance_recipe_catalogue();
	$report   = array();
	$any_error = false;

	foreach ( $catalogue as $slug => $meta ) {
		$existing_recipe = get_page_by_path( $slug, OBJECT, 'vance_recipe' );
		if ( $existing_recipe ) {
			$report[ $slug ] = array( 'status' => 'already converted (skip)', 'errors' => array() );
			continue;
		}

		$draft = get_page_by_path( $slug, OBJECT, 'post' );
		if ( ! $draft ) {
			$report[ $slug ] = array( 'status' => 'ERROR', 'errors' => array( "no draft Post found at slug '{$slug}'" ) );
			$any_error = true;
			continue;
		}

		$parsed = vance_recipe_cli_parse_and_check( $slug, $draft->post_content );
		if ( $parsed['errors'] ) {
			$report[ $slug ] = array( 'status' => 'ERROR', 'errors' => $parsed['errors'], 'post_id' => $draft->ID );
			$any_error = true;
			continue;
		}

		$report[ $slug ] = array( 'status' => 'OK', 'errors' => array(), 'post_id' => $draft->ID, 'parsed' => $parsed );
	}

	// --- Print the diff table ---
	foreach ( $report as $slug => $row ) {
		if ( $row['errors'] ) {
			WP_CLI::log( WP_CLI::colorize( "%R{$slug}: {$row['status']}%n" ) );
			foreach ( $row['errors'] as $err ) {
				WP_CLI::log( "    - {$err}" );
			}
		} else {
			WP_CLI::log( WP_CLI::colorize( "%G{$slug}: {$row['status']}%n" ) );
		}
	}

	if ( $any_error ) {
		WP_CLI::error( 'One or more recipes failed the cross-check above. Nothing was written. Fix the mismatch (or the recipe-data.php / catalogue source if THAT is wrong) and re-run — this command is idempotent.' );
		return;
	}

	if ( $dry_run ) {
		WP_CLI::success( sprintf( 'Dry run only — %d recipe(s) checked, all clean. Re-run without --dry-run to convert.', count( $report ) ) );
		return;
	}

	// --- Write ---
	$converted = 0;
	foreach ( $report as $slug => $row ) {
		if ( 'OK' !== $row['status'] ) {
			continue; // Already converted.
		}
		$parsed = $row['parsed'];
		$facts  = $parsed['facts'];
		$post_id = $row['post_id'];

		$new_content = trim( "<h2>{$parsed['intro_html']}</h2>\n\n<p>{$parsed['why_html']}</p>" );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_type'    => 'vance_recipe',
				'post_content' => $new_content,
				'post_status'  => 'publish',
			)
		);

		wp_set_post_terms( $post_id, array( $parsed['category'] ), 'vance_recipe_cat', false );
		if ( $parsed['tags'] ) {
			wp_set_post_terms( $post_id, $parsed['tags'], 'vance_recipe_tag', false );
		}

		update_post_meta( $post_id, '_vance_recipe_legacy_slug', $slug );
		update_post_meta( $post_id, '_vance_recipe_servings', (int) $facts['servings'] );
		update_post_meta( $post_id, '_vance_recipe_prep_min', (int) $facts['prep'] );
		if ( (int) $facts['cook'] > 0 ) {
			update_post_meta( $post_id, '_vance_recipe_cook_min', (int) $facts['cook'] );
		} else {
			delete_post_meta( $post_id, '_vance_recipe_cook_min' );
		}
		update_post_meta( $post_id, '_vance_recipe_kcal', (int) $facts['nutrition']['calories'] );
		update_post_meta( $post_id, '_vance_recipe_protein_g', (int) $facts['nutrition']['protein'] );
		update_post_meta( $post_id, '_vance_recipe_carbs_g', (int) $facts['nutrition']['carbs'] );
		update_post_meta( $post_id, '_vance_recipe_fat_g', (int) $facts['nutrition']['fat'] );
		update_post_meta( $post_id, '_vance_recipe_fibre_g', (int) $facts['nutrition']['fibre'] );
		update_post_meta( $post_id, '_vance_recipe_ingredients', $facts['ingredients'] );
		update_post_meta( $post_id, '_vance_recipe_method', array_values( (array) $facts['instructions'] ) );

		if ( $parsed['credit'] ) {
			update_post_meta( $post_id, '_vance_recipe_credit_author', sanitize_text_field( $parsed['credit']['author'] ) );
			update_post_meta( $post_id, '_vance_recipe_credit_author_url', esc_url_raw( $parsed['credit']['author_url'] ) );
			update_post_meta( $post_id, '_vance_recipe_credit_source_url', esc_url_raw( $parsed['credit']['photo_url'] ) );
		}

		$converted++;
		WP_CLI::log( "converted {$slug} (post {$post_id})" );
	}

	WP_CLI::success( sprintf( '%d recipe(s) converted, %d already converted.', $converted, count( $report ) - $converted ) );
}

WP_CLI::add_command( 'vance recipes convert', 'vance_recipe_cli_convert' );
