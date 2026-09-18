<?php
/**
 * Apply the recipe label allocation in tools/recipe-labels.json to the live
 * vance_recipe posts.
 *
 *   wp eval-file apply-recipe-labels.php <labels.json> <backup.json>          # dry run
 *   wp eval-file apply-recipe-labels.php <labels.json> <backup.json> apply    # write
 *
 * Give each run its own <backup.json>: the script refuses to overwrite one, so
 * the dry run and the apply that follows it need different paths.
 *
 * Arguments are positional on purpose: `wp eval-file` swallows --flags, so a
 * `--dry-run` guard would never see its flag and the script would run for real.
 * Anything other than the literal word `apply` in third place is a dry run.
 *
 * recipe-labels.json is generated from Daisy Gershon's spreadsheet
 * (Recipe-Tag-Descriptor-Chart-Daisy Edit.xlsx), which is the source of truth
 * for which recipe carries which label. The slug => name list below must match
 * vance_recipe_label_model() in the theme's inc/recipe-frontend.php.
 *
 * What it does, per recipe: replaces every vance_recipe_tag term with the
 * recipe's labels. The one exception is the three condition terms (IBD, IBS,
 * Ulcerative Colitis): whether readers should be able to filter by condition is
 * an open decision of Daisy's, so an existing assignment is left in place. The
 * theme does not display them. Retired terms are unassigned, never deleted.
 *
 * Nothing is written until every recipe in the file has been checked against
 * the database, and the previous assignments are saved to <backup.json> first.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$vance_labels = array(
	'gluten-free'        => 'Gluten Free',
	'dairy-free'         => 'Dairy Free',
	'vegetarian'         => 'Vegetarian',
	'vegan-friendly'     => 'Vegan-Friendly',
	'low-fibre'          => 'Low Fibre',
	'high-fibre'         => 'High Fibre',
	'high-protein'       => 'High Protein',
	'low-fodmap'         => 'Low FODMAP',
	'no-onion'           => 'No onion',
	'no-garlic'          => 'No garlic',
	'easy-to-digest'     => 'Easy to digest',
	'nutrient-dense'     => 'Nutrient dense',
	'refined-sugar-free' => 'Refined sugar free',
	'omega-3-rich'       => 'Omega-3 Rich',
);
$vance_kept   = array( 'ibd', 'ibs', 'ulcerative-colitis' );
$vance_tax    = 'vance_recipe_tag';

// Section 6, step 4 of the handover: the counts to expect. Transcribed from the
// document rather than derived from the JSON, so the two can disagree.
$vance_expected = array(
	'gluten-free'        => 70,
	'dairy-free'         => 57,
	'vegetarian'         => 53,
	'vegan-friendly'     => 26,
	'low-fibre'          => 8,
	'high-fibre'         => 59,
	'high-protein'       => 52,
	'low-fodmap'         => 14,
	'no-onion'           => 67,
	'no-garlic'          => 62,
	'easy-to-digest'     => 17,
	'nutrient-dense'     => 63,
	'refined-sugar-free' => 89,
	'omega-3-rich'       => 23,
);

if ( empty( $args[0] ) || empty( $args[1] ) ) {
	WP_CLI::error( 'Usage: wp eval-file apply-recipe-labels.php <labels.json> <backup.json> [apply]' );
}
$vance_apply = isset( $args[2] ) && 'apply' === $args[2];

$vance_data = json_decode( (string) file_get_contents( $args[0] ), true );
if ( empty( $vance_data['recipes'] ) || ! is_array( $vance_data['recipes'] ) ) {
	WP_CLI::error( 'Could not read recipes from ' . $args[0] );
}
$vance_rows = $vance_data['recipes'];

/**
 * Titles are compared loosely: the spreadsheet was typed from the rendered
 * page, so entities, curly quotes and case can differ from post_title.
 */
$vance_norm = function ( $title ) {
	$title = html_entity_decode( (string) $title, ENT_QUOTES, 'UTF-8' );
	return preg_replace( '/[^a-z0-9]+/', '', strtolower( remove_accents( $title ) ) );
};

// --- 1. Check the whole file against the database before touching anything.
$vance_problems = array();
$vance_seen     = array();
foreach ( $vance_rows as $row ) {
	$id = (int) $row['id'];
	if ( isset( $vance_seen[ $id ] ) ) {
		$vance_problems[] = "$id appears twice in the file";
	}
	$vance_seen[ $id ] = true;

	$post = get_post( $id );
	if ( ! $post || 'vance_recipe' !== $post->post_type || 'publish' !== $post->post_status ) {
		$vance_problems[] = "$id is not a published vance_recipe";
		continue;
	}
	if ( $vance_norm( $post->post_title ) !== $vance_norm( $row['title'] ) ) {
		$vance_problems[] = "$id title differs: file '{$row['title']}', site '{$post->post_title}'";
	}
	foreach ( $row['labels'] as $slug ) {
		if ( ! isset( $vance_labels[ $slug ] ) ) {
			$vance_problems[] = "$id carries unknown label '$slug'";
		}
	}
}

$vance_published = get_posts(
	array(
		'post_type'      => 'vance_recipe',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
foreach ( array_diff( $vance_published, array_keys( $vance_seen ) ) as $id ) {
	$vance_problems[] = "$id is published but not in the file";
}

$vance_file_counts = array_fill_keys( array_keys( $vance_labels ), 0 );
foreach ( $vance_rows as $row ) {
	foreach ( $row['labels'] as $slug ) {
		if ( isset( $vance_file_counts[ $slug ] ) ) {
			$vance_file_counts[ $slug ]++;
		}
	}
}
foreach ( $vance_expected as $slug => $n ) {
	if ( $vance_file_counts[ $slug ] !== $n ) {
		$vance_problems[] = "file has {$vance_file_counts[ $slug ]} x $slug, handover expects $n";
	}
}

if ( $vance_problems ) {
	foreach ( $vance_problems as $p ) {
		WP_CLI::warning( $p );
	}
	WP_CLI::error( count( $vance_problems ) . ' problem(s); nothing written.' );
}
WP_CLI::log( count( $vance_rows ) . ' recipes checked against the database, no problems.' );

// --- 2. Back up what is there now. Both modes, so a dry run proves the path.
// Never over an existing file: on a re-run that file is the only record of the
// state before the first run, and this would replace it with the state after.
if ( file_exists( $args[1] ) ) {
	WP_CLI::error( $args[1] . ' already exists; pass a new backup path. Nothing written.' );
}
$vance_backup = array();
foreach ( $vance_published as $id ) {
	$vance_backup[ $id ] = wp_get_object_terms( $id, $vance_tax, array( 'fields' => 'slugs' ) );
}
$vance_json = wp_json_encode(
	array(
		'taken'       => gmdate( 'c' ),
		'terms'       => get_terms(
			array(
				'taxonomy'   => $vance_tax,
				'hide_empty' => false,
				'fields'     => 'id=>name',
			)
		),
		'assignments' => $vance_backup,
	),
	JSON_PRETTY_PRINT
);
if ( ! $vance_json || false === file_put_contents( $args[1], $vance_json ) || filesize( $args[1] ) < 1000 ) {
	WP_CLI::error( 'Backup to ' . $args[1] . ' failed; nothing written.' );
}
WP_CLI::log( 'Previous assignments saved to ' . $args[1] );

// --- 3. Dry run: say what would change and stop.
if ( ! $vance_apply ) {
	$changed = 0;
	foreach ( $vance_rows as $row ) {
		$now  = $vance_backup[ (int) $row['id'] ];
		$next = array_merge( $row['labels'], array_intersect( $now, $vance_kept ) );
		sort( $now );
		sort( $next );
		if ( $now !== $next ) {
			$changed++;
		}
	}
	WP_CLI::success( "DRY RUN. $changed of " . count( $vance_rows ) . ' recipes would change. Add the word apply to write.' );
	return;
}

// --- 4. Terms: create what is missing, bring names into line.
$vance_term_ids = array();
foreach ( $vance_labels as $slug => $name ) {
	$term = get_term_by( 'slug', $slug, $vance_tax );
	if ( ! $term ) {
		$made = wp_insert_term( $name, $vance_tax, array( 'slug' => $slug ) );
		if ( is_wp_error( $made ) ) {
			WP_CLI::error( "Could not create $slug: " . $made->get_error_message() );
		}
		$vance_term_ids[ $slug ] = (int) $made['term_id'];
		continue;
	}
	if ( $term->name !== $name ) {
		wp_update_term( $term->term_id, $vance_tax, array( 'name' => $name ) );
	}
	$vance_term_ids[ $slug ] = (int) $term->term_id;
}
foreach ( $vance_kept as $slug ) {
	$term = get_term_by( 'slug', $slug, $vance_tax );
	if ( $term ) {
		$vance_term_ids[ $slug ] = (int) $term->term_id;
	}
}

// --- 5. Assign. Integer IDs: a string is treated as a term NAME to create.
foreach ( $vance_rows as $row ) {
	$id    = (int) $row['id'];
	$slugs = array_merge( $row['labels'], array_intersect( $vance_backup[ $id ], $vance_kept ) );
	$ids   = array();
	foreach ( $slugs as $slug ) {
		$ids[] = $vance_term_ids[ $slug ];
	}
	$set = wp_set_object_terms( $id, $ids, $vance_tax, false );
	if ( is_wp_error( $set ) ) {
		WP_CLI::error( "$id: " . $set->get_error_message() . ' Restore from ' . $args[1] );
	}
}

// --- 6. Verify from the database, not from what was just sent to it.
clean_object_term_cache( $vance_published, 'vance_recipe' );
$vance_bad = 0;
foreach ( $vance_rows as $row ) {
	$got  = array_diff( wp_get_object_terms( (int) $row['id'], $vance_tax, array( 'fields' => 'slugs' ) ), $vance_kept );
	$want = $row['labels'];
	sort( $got );
	sort( $want );
	if ( array_values( $got ) !== $want ) {
		WP_CLI::warning( $row['id'] . ' has [' . implode( ', ', $got ) . '], wanted [' . implode( ', ', $want ) . ']' );
		$vance_bad++;
	}
}
foreach ( $vance_expected as $slug => $n ) {
	$term = get_term( $vance_term_ids[ $slug ], $vance_tax );
	if ( (int) $term->count !== $n ) {
		WP_CLI::warning( "$slug count is {$term->count}, expected $n" );
		$vance_bad++;
	}
}
if ( $vance_bad ) {
	WP_CLI::error( "$vance_bad verification failure(s). Previous state is in " . $args[1] );
}
WP_CLI::success( 'Applied and verified: ' . count( $vance_rows ) . ' recipes, ' . count( $vance_labels ) . ' labels, every count as expected.' );
