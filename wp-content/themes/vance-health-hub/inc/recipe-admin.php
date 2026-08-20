<?php
/**
 * Recipe CPT admin editor — meta boxes and save handler for `vance_recipe`.
 *
 * Ingredients and method are edited as plain textareas using a small
 * line-based syntax rather than a JS repeater over a hidden JSON field: a
 * textarea is naturally reorderable (drag lines, cut/paste), needs no build
 * step, and — because it is the only representation, not a JS-enhanced view
 * of a separate field — there is no sync-drift case between "JS ran" and
 * "JS didn't run" to get wrong. The parse/format helpers here are reused by
 * the Phase 1 draft converter (inc/recipe-converter.php) so admin edits and
 * the one-time conversion go through identical logic.
 *
 * Ingredients syntax — a line starting with "- " is an item under the
 * current section; any other non-blank line starts a new (optionally named)
 * section:
 *
 *   For the salad
 *   - 1 cup chickpeas, drained and rinsed
 *   - 1 tbsp olive oil
 *
 *   For the dressing
 *   - 1 tbsp tahini
 *
 * A recipe with one unnamed section just omits the header line.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse the ingredients textarea into the stored shape:
 * [{section:'', items:['2 salmon fillets', ...]}, ...]
 *
 * @param string $text
 * @return array
 */
function vance_recipe_parse_ingredients_text( $text ) {
	$lines    = preg_split( '/\r\n|\r|\n/', (string) $text );
	$sections = array();
	$section  = '';
	$items    = array();
	$has_any  = false;

	foreach ( $lines as $line ) {
		$trim = trim( $line );
		if ( '' === $trim ) {
			continue;
		}
		if ( '-' === $trim[0] ) {
			$item = trim( substr( $trim, 1 ) );
			if ( '' !== $item ) {
				$items[]  = $item;
				$has_any  = true;
			}
			continue;
		}
		if ( $has_any ) {
			$sections[] = array( 'section' => $section, 'items' => $items );
		}
		$section = $trim;
		$items   = array();
		$has_any = true; // A named header counts as content even before its first item.
	}
	if ( $has_any ) {
		$sections[] = array( 'section' => $section, 'items' => $items );
	}

	return $sections;
}

/**
 * Render the stored ingredients shape back to textarea syntax, for populating
 * the edit screen.
 *
 * @param array $sections
 * @return string
 */
function vance_recipe_ingredients_to_text( $sections ) {
	$lines = array();
	foreach ( (array) $sections as $sec ) {
		$name = isset( $sec['section'] ) ? trim( (string) $sec['section'] ) : '';
		if ( $lines ) {
			$lines[] = '';
		}
		if ( '' !== $name ) {
			$lines[] = $name;
		}
		foreach ( (array) ( isset( $sec['items'] ) ? $sec['items'] : array() ) as $item ) {
			$lines[] = '- ' . $item;
		}
	}
	return implode( "\n", $lines );
}

/**
 * Parse the method textarea into an ordered array of step strings. Strips a
 * leading "1." / "1)" if present, so pasted numbered text still works.
 *
 * @param string $text
 * @return string[]
 */
function vance_recipe_parse_method_text( $text ) {
	$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
	$steps = array();
	foreach ( $lines as $line ) {
		$trim = trim( $line );
		if ( '' === $trim ) {
			continue;
		}
		$steps[] = preg_replace( '/^\d+[\.\)]\s*/', '', $trim );
	}
	return $steps;
}

/**
 * @param string[] $steps
 * @return string
 */
function vance_recipe_method_to_text( $steps ) {
	return implode( "\n", array_map( 'strval', (array) $steps ) );
}

function vance_recipe_add_meta_boxes() {
	add_meta_box( 'vance_recipe_details', __( 'Recipe Details', 'vance-health-hub' ), 'vance_recipe_render_details_box', 'vance_recipe', 'side', 'default' );
	add_meta_box( 'vance_recipe_nutrition', __( 'Nutrition (per serving)', 'vance-health-hub' ), 'vance_recipe_render_nutrition_box', 'vance_recipe', 'side', 'default' );
	add_meta_box( 'vance_recipe_credit', __( 'Photo Credit (Unsplash)', 'vance-health-hub' ), 'vance_recipe_render_credit_box', 'vance_recipe', 'side', 'low' );
	add_meta_box( 'vance_recipe_ingredients', __( 'Ingredients', 'vance-health-hub' ), 'vance_recipe_render_ingredients_box', 'vance_recipe', 'normal', 'high' );
	add_meta_box( 'vance_recipe_method', __( 'Method', 'vance-health-hub' ), 'vance_recipe_render_method_box', 'vance_recipe', 'normal', 'high' );
}
add_action( 'add_meta_boxes_vance_recipe', 'vance_recipe_add_meta_boxes' );

function vance_recipe_meta_field( $post, $key, $label, $type = 'number', $extra = '' ) {
	$value = get_post_meta( $post->ID, $key, true );
	printf(
		'<p><label for="%1$s" style="display:block;font-weight:600;margin-bottom:2px;">%2$s</label>' .
		'<input type="%3$s" id="%1$s" name="%1$s" value="%4$s" style="width:100%%;" %5$s></p>',
		esc_attr( $key ),
		esc_html( $label ),
		esc_attr( $type ),
		esc_attr( $value ),
		$extra
	);
}

function vance_recipe_render_details_box( $post ) {
	wp_nonce_field( 'vance_recipe_save_meta', 'vance_recipe_meta_nonce' );
	vance_recipe_meta_field( $post, 'vance_recipe_servings', __( 'Servings', 'vance-health-hub' ), 'number', 'min="1" step="1"' );
	vance_recipe_meta_field( $post, 'vance_recipe_prep_min', __( 'Prep (minutes)', 'vance-health-hub' ), 'number', 'min="0" step="1"' );
	vance_recipe_meta_field( $post, 'vance_recipe_cook_min', __( 'Cook (minutes) — leave blank if no-cook', 'vance-health-hub' ), 'number', 'min="0" step="1"' );

	$legacy = get_post_meta( $post->ID, '_vance_recipe_legacy_slug', true );
	if ( $legacy ) {
		printf(
			'<p style="margin-top:10px;color:#646970;"><strong>%s:</strong><br><code>%s</code></p>',
			esc_html__( 'Legacy slug', 'vance-health-hub' ),
			esc_html( $legacy )
		);
	}
}

function vance_recipe_render_nutrition_box( $post ) {
	vance_recipe_meta_field( $post, 'vance_recipe_kcal', __( 'Calories (kcal)', 'vance-health-hub' ), 'number', 'min="0" step="1"' );
	vance_recipe_meta_field( $post, 'vance_recipe_protein_g', __( 'Protein (g)', 'vance-health-hub' ), 'number', 'min="0" step="1"' );
	vance_recipe_meta_field( $post, 'vance_recipe_carbs_g', __( 'Carbs (g)', 'vance-health-hub' ), 'number', 'min="0" step="1"' );
	vance_recipe_meta_field( $post, 'vance_recipe_fat_g', __( 'Fat (g)', 'vance-health-hub' ), 'number', 'min="0" step="1"' );
	vance_recipe_meta_field( $post, 'vance_recipe_fibre_g', __( 'Fibre (g)', 'vance-health-hub' ), 'number', 'min="0" step="1"' );
	vance_recipe_meta_field( $post, 'vance_recipe_epa_mg', __( 'EPA (mg) — optional', 'vance-health-hub' ), 'number', 'min="0" step="1"' );
}

function vance_recipe_render_credit_box( $post ) {
	vance_recipe_meta_field( $post, 'vance_recipe_credit_author', __( 'Photographer name', 'vance-health-hub' ), 'text' );
	vance_recipe_meta_field( $post, 'vance_recipe_credit_author_url', __( 'Photographer profile URL', 'vance-health-hub' ), 'url' );
	vance_recipe_meta_field( $post, 'vance_recipe_credit_source_url', __( 'Photo URL (Unsplash)', 'vance-health-hub' ), 'url' );
}

function vance_recipe_render_ingredients_box( $post ) {
	$sections = get_post_meta( $post->ID, '_vance_recipe_ingredients', true );
	$text     = vance_recipe_ingredients_to_text( is_array( $sections ) ? $sections : array() );
	?>
	<p style="color:#646970;">
		<?php esc_html_e( 'One ingredient per line, prefixed with "- ". A line without that prefix starts a new (optional) section heading.', 'vance-health-hub' ); ?>
	</p>
	<textarea name="vance_recipe_ingredients_text" rows="16" style="width:100%;font-family:monospace;"><?php echo esc_textarea( $text ); ?></textarea>
	<?php
}

function vance_recipe_render_method_box( $post ) {
	$steps = get_post_meta( $post->ID, '_vance_recipe_method', true );
	$text  = vance_recipe_method_to_text( is_array( $steps ) ? $steps : array() );
	?>
	<p style="color:#646970;"><?php esc_html_e( 'One step per line, in order.', 'vance-health-hub' ); ?></p>
	<textarea name="vance_recipe_method_text" rows="12" style="width:100%;font-family:monospace;"><?php echo esc_textarea( $text ); ?></textarea>
	<?php
}

/**
 * Save handler. Form fields submit as `vance_recipe_*`; this writes them to
 * `_vance_recipe_*` post meta, matching the theme's `_sla_*` translation
 * convention elsewhere (CLAUDE.md constraint 2).
 */
function vance_recipe_save_meta( $post_id, $post ) {
	if ( 'vance_recipe' !== $post->post_type ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['vance_recipe_meta_nonce'] ) || ! wp_verify_nonce( $_POST['vance_recipe_meta_nonce'], 'vance_recipe_save_meta' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$int_fields = array(
		'vance_recipe_servings'  => '_vance_recipe_servings',
		'vance_recipe_prep_min'  => '_vance_recipe_prep_min',
		'vance_recipe_cook_min'  => '_vance_recipe_cook_min',
		'vance_recipe_kcal'      => '_vance_recipe_kcal',
		'vance_recipe_protein_g' => '_vance_recipe_protein_g',
		'vance_recipe_carbs_g'   => '_vance_recipe_carbs_g',
		'vance_recipe_fat_g'     => '_vance_recipe_fat_g',
		'vance_recipe_fibre_g'   => '_vance_recipe_fibre_g',
		'vance_recipe_epa_mg'    => '_vance_recipe_epa_mg',
	);
	foreach ( $int_fields as $field => $meta_key ) {
		$raw = isset( $_POST[ $field ] ) ? trim( (string) wp_unslash( $_POST[ $field ] ) ) : '';
		if ( '' === $raw ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, (int) $raw );
		}
	}

	$url_fields = array(
		'vance_recipe_credit_author'     => '_vance_recipe_credit_author',
		'vance_recipe_credit_author_url' => '_vance_recipe_credit_author_url',
		'vance_recipe_credit_source_url' => '_vance_recipe_credit_source_url',
	);
	foreach ( $url_fields as $field => $meta_key ) {
		$raw = isset( $_POST[ $field ] ) ? trim( (string) wp_unslash( $_POST[ $field ] ) ) : '';
		if ( '' === $raw ) {
			delete_post_meta( $post_id, $meta_key );
		} elseif ( false !== strpos( $meta_key, '_url' ) ) {
			update_post_meta( $post_id, $meta_key, esc_url_raw( $raw ) );
		} else {
			update_post_meta( $post_id, $meta_key, sanitize_text_field( $raw ) );
		}
	}

	if ( isset( $_POST['vance_recipe_ingredients_text'] ) ) {
		$sections = vance_recipe_parse_ingredients_text( wp_unslash( $_POST['vance_recipe_ingredients_text'] ) );
		$sections = array_map(
			function ( $sec ) {
				return array(
					'section' => sanitize_text_field( $sec['section'] ),
					'items'   => array_map( 'sanitize_text_field', $sec['items'] ),
				);
			},
			$sections
		);
		if ( $sections ) {
			update_post_meta( $post_id, '_vance_recipe_ingredients', $sections );
		} else {
			delete_post_meta( $post_id, '_vance_recipe_ingredients' );
		}
	}

	if ( isset( $_POST['vance_recipe_method_text'] ) ) {
		$steps = vance_recipe_parse_method_text( wp_unslash( $_POST['vance_recipe_method_text'] ) );
		$steps = array_map( 'sanitize_text_field', $steps );
		if ( $steps ) {
			update_post_meta( $post_id, '_vance_recipe_method', $steps );
		} else {
			delete_post_meta( $post_id, '_vance_recipe_method' );
		}
	}
}
add_action( 'save_post', 'vance_recipe_save_meta', 10, 2 );
