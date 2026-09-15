<?php
/**
 * Recipe hub app — the recipe grid + 7x4 weekly planner + picker modal.
 *
 * Shared between page-gastro-recipies.php (the standalone hub page) and
 * page-dashboard.php's "My Recipes" tab, so the ~450 lines of markup that
 * drive assets/js/recipe-planner.js exist in exactly one place. Fully
 * self-contained — computes its own data rather than relying on variables a
 * caller might set, so `get_template_part( 'template-parts/recipe-hub-app' )`
 * is the entire contract.
 *
 * Pass `array( 'vance_rh_embedded' => true )` as get_template_part()'s third
 * argument when rendering inside the dashboard: adds a class that drops the
 * section padding/backgrounds that make sense on a full page but not inside a
 * dashboard card. Setting a local before the call does NOT work — WP loads the
 * part in load_template()'s own scope, so the caller's locals are invisible
 * here and the flag silently reads as false, which is what it did until
 * 2026-08-31. $args (WP 5.5+) is the only channel that reaches this file.
 *
 * CSS: assets/css/recipe-hub.css. JS + config: assets/js/recipe-planner.js,
 * enqueued by vance_health_hub_scripts() (functions.php) whenever this
 * part's markup is on the page — see that function's conditions.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// load_template() extracts $args into this scope, so a caller passing
// array( 'vance_rh_embedded' => true ) arrives as the local below; the $args
// lookup is the belt-and-braces path if extract() is ever skipped.
$vance_rh_embedded = ! empty( $vance_rh_embedded )
	|| ( isset( $args['vance_rh_embedded'] ) && $args['vance_rh_embedded'] );

$vance_cat_filter  = isset( $_GET['cat'] ) ? sanitize_key( wp_unslash( $_GET['cat'] ) ) : '';
// Comma-separated, order-preserved, deduped: several can be active together
// (a recipe can be both Oat-Free and Vegetarian), unlike category, which a
// recipe only ever has one of.
$vance_tags_filter = array();
if ( isset( $_GET['tags'] ) ) {
	foreach ( explode( ',', sanitize_text_field( wp_unslash( $_GET['tags'] ) ) ) as $t ) {
		$t = sanitize_key( $t );
		if ( '' !== $t && ! in_array( $t, $vance_tags_filter, true ) ) {
			$vance_tags_filter[] = $t;
		}
	}
}
$vance_recipes    = vance_recipe_planner_data();
$vance_categories = array(
	'breakfast' => __( 'Breakfast', 'vance-health-hub' ),
	'lunch'     => __( 'Lunch', 'vance-health-hub' ),
	'dinner'    => __( 'Dinner', 'vance-health-hub' ),
	'snacks'    => __( 'Snacks', 'vance-health-hub' ),
);
/**
 * Tag chips, sharing one row with the meal-category chips above rather than
 * every vance_recipe_tag term automatically appearing here — a fixed list
 * so a one-off editorial term (e.g. "5-Ingredient Meals for Busy Days",
 * count 1) never silently shows up as a filter chip. Add a slug => label
 * pair by hand when a new condition or dietary-attribute term is worth
 * filtering by. IBD/IBS/Ulcerative Colitis first (added 2026-09-15), then
 * the pre-existing dietary-attribute terms alphabetically.
 */
$vance_tags = array(
	'ibd'                => __( 'IBD', 'vance-health-hub' ),
	'ibs'                => __( 'IBS', 'vance-health-hub' ),
	'ulcerative-colitis' => __( 'Ulcerative Colitis', 'vance-health-hub' ),
	'dairy-free'         => __( 'Dairy-Free', 'vance-health-hub' ),
	'garlic-free'        => __( 'Garlic-Free', 'vance-health-hub' ),
	'gluten-free'        => __( 'Gluten-Free', 'vance-health-hub' ),
	'high-fibre'         => __( 'High Fibre', 'vance-health-hub' ),
	'high-protein'       => __( 'High Protein', 'vance-health-hub' ),
	'mediterranean-style' => __( 'Mediterranean-Style', 'vance-health-hub' ),
	'nutrient-dense'     => __( 'Nutrient-Dense', 'vance-health-hub' ),
	'oat-free'           => __( 'Oat-Free', 'vance-health-hub' ),
	'omega-3-rich'       => __( 'Omega-3 Rich', 'vance-health-hub' ),
	'onion-free'         => __( 'Onion-Free', 'vance-health-hub' ),
	'refined-sugar-free' => __( 'Refined Sugar-Free', 'vance-health-hub' ),
	'vegan-friendly'     => __( 'Vegan-Friendly', 'vance-health-hub' ),
	'vegetarian'         => __( 'Vegetarian', 'vance-health-hub' ),
);
$vance_base_url = home_url( '/gastro-meal-planner/' );
// Admin-only "Date Uploaded" sort + the upload date shown on each card —
// current_user_can( 'manage_options' ) is the standard single-site proxy
// for "is an Administrator". Matches the gate already in
// vance_recipe_planner_data(), which is WHY $vance_recipes only carries
// dateUploaded/dateTimestamp at all when this is true; nothing here can
// show a date to anyone that function already decided not to hand over.
$vance_is_admin  = current_user_can( 'manage_options' );
$vance_sort_date = $vance_is_admin && isset( $_GET['sort'] ) && 'date' === $_GET['sort'];
?>
<div class="<?php echo $vance_rh_embedded ? 'vance-rh-embedded' : ''; ?>">

<section class="vance-rh-section" id="recipes">
	<div class="container">
		<h2 class="vance-rh-h2"><?php esc_html_e( 'Recipes', 'vance-health-hub' ); ?></h2>
		<?php
		// Carried into a category chip's own link so picking a meal type
		// doesn't drop the active tag selection, and vice versa — the two
		// filter independently (AND together), not exclusively. $vance_tags_arg
		// is the one bit shared by every category-chip href below.
		$vance_tags_arg = $vance_tags_filter ? array( 'tags' => implode( ',', $vance_tags_filter ) ) : array();

		// Filtered once here (not inside the grid loop with `continue`) so the
		// result count above the grid and the cards actually rendered come
		// from the exact same list, rather than the count being a second,
		// separately-written copy of this same logic that could drift from it.
		$vance_visible_recipes = array_filter(
			$vance_recipes,
			function ( $r ) use ( $vance_cat_filter, $vance_tags_filter ) {
				if ( $vance_cat_filter && $vance_cat_filter !== $r['category'] ) {
					return false;
				}
				return ! array_diff( $vance_tags_filter, $r['tags'] ); // every selected tag present (AND).
			}
		);
		$vance_filters_active = ( '' !== $vance_cat_filter || $vance_tags_filter );

		// Admin-only, and only possible at all because vance_recipe_planner_data()
		// only put dateTimestamp on $vance_recipes in the first place for an
		// Administrator — see the gate there.
		if ( $vance_sort_date ) {
			usort(
				$vance_visible_recipes,
				function ( $a, $b ) {
					return ( isset( $b['dateTimestamp'] ) ? $b['dateTimestamp'] : 0 ) <=> ( isset( $a['dateTimestamp'] ) ? $a['dateTimestamp'] : 0 );
				}
			);
		}
		?>
		<div class="vance-rh-controls">
			<div class="vance-rh-chips vance-rh-chips--meal" id="vance-rh-filter-chips">
				<a class="vance-rh-chip<?php echo ( '' === $vance_cat_filter && ! $vance_tags_filter ) ? ' is-active' : ''; ?>" data-chip-all="1" href="<?php echo esc_url( $vance_base_url . '#recipes' ); ?>"><?php esc_html_e( 'All', 'vance-health-hub' ); ?></a>
				<?php foreach ( $vance_categories as $cat_slug => $cat_label ) : ?>
					<a class="vance-rh-chip<?php echo ( $vance_cat_filter === $cat_slug ) ? ' is-active' : ''; ?>" data-chip-cat="<?php echo esc_attr( $cat_slug ); ?>" href="<?php echo esc_url( add_query_arg( array_merge( array( 'cat' => $cat_slug ), $vance_tags_arg ), $vance_base_url ) . '#recipes' ); ?>"><?php echo esc_html( $cat_label ); ?></a>
				<?php endforeach; ?>
			</div>
			<?php
			/**
			 * Tag dropdown: a checkbox per condition/dietary term, multi-select,
			 * AND-combined with each other and with the category above — same
			 * filtering semantics as the old inline tag-chip row, just a
			 * different control. Rendered OPEN (no `hidden`) by default so the
			 * checkboxes work with no JS at all (the "Apply" button below submits
			 * the surrounding form as a normal GET request); JS then collapses it
			 * into an actual dropdown and applies each change instantly instead of
			 * needing the button.
			 */
			?>
			<div class="vance-rh-tags-dropdown">
				<button type="button" class="vance-rh-chip vance-rh-tags-toggle" id="vance-rh-tags-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="vance-rh-tags-panel">
					<?php esc_html_e( 'Tags', 'vance-health-hub' ); ?>
					<span class="vance-rh-tags-count" id="vance-rh-tags-count"<?php echo $vance_tags_filter ? '' : ' hidden'; ?>><?php echo esc_html( count( $vance_tags_filter ) ); ?></span>
				</button>
				<form class="vance-rh-tags-panel" id="vance-rh-tags-panel" method="get" action="<?php echo esc_url( $vance_base_url ); ?>">
					<?php if ( $vance_cat_filter ) : ?>
						<input type="hidden" name="cat" value="<?php echo esc_attr( $vance_cat_filter ); ?>">
					<?php endif; ?>
					<?php foreach ( $vance_tags as $tag_slug => $tag_label ) : ?>
						<label class="vance-rh-tag-check">
							<input type="checkbox" name="tags[]" value="<?php echo esc_attr( $tag_slug ); ?>" data-chip-tag="<?php echo esc_attr( $tag_slug ); ?>"<?php checked( in_array( $tag_slug, $vance_tags_filter, true ) ); ?>>
							<?php echo esc_html( $tag_label ); ?>
						</label>
					<?php endforeach; ?>
					<div class="vance-rh-tags-panel-actions">
						<button type="button" id="vance-rh-tags-clear" class="vance-rh-tags-clear"><?php esc_html_e( 'Clear', 'vance-health-hub' ); ?></button>
						<button type="submit" class="vance-rh-tags-apply"><?php esc_html_e( 'Apply', 'vance-health-hub' ); ?></button>
					</div>
				</form>
			</div>
			<input type="search" class="vance-rh-search" id="vance-rh-search" placeholder="<?php esc_attr_e( 'Search recipes…', 'vance-health-hub' ); ?>">
			<?php if ( $vance_is_admin ) : ?>
				<?php
				// Toggle link: on when NOT already sorted, off (back to the default
				// order) when it is — carries the current cat/tags filters either way,
				// same $vance_tags_arg the category chips above already carry.
				$vance_sort_args = array_merge( $vance_cat_filter ? array( 'cat' => $vance_cat_filter ) : array(), $vance_tags_arg );
				if ( ! $vance_sort_date ) {
					$vance_sort_args['sort'] = 'date';
				}
				?>
				<a class="vance-rh-chip vance-rh-admin-sort<?php echo $vance_sort_date ? ' is-active' : ''; ?>" id="vance-rh-sort-date" data-sort-date="1" data-sort-active="<?php echo $vance_sort_date ? '1' : '0'; ?>" href="<?php echo esc_url( add_query_arg( $vance_sort_args, $vance_base_url ) . '#recipes' ); ?>" title="<?php esc_attr_e( 'Admin only', 'vance-health-hub' ); ?>">
					<?php esc_html_e( 'Date Uploaded', 'vance-health-hub' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<p class="vance-rh-count" id="vance-rh-count"<?php echo $vance_filters_active ? '' : ' hidden'; ?>>
			<?php
			printf(
				/* translators: 1: number of matching recipes, 2: total number of recipes */
				esc_html__( 'Showing %1$d of %2$d recipes', 'vance-health-hub' ),
				count( $vance_visible_recipes ),
				count( $vance_recipes )
			);
			?>
		</p>
		<div class="vance-rh-grid" id="vance-rh-grid">
			<?php foreach ( $vance_visible_recipes as $r ) : ?>
				<div class="vance-rh-card" data-recipe-category="<?php echo esc_attr( $r['category'] ); ?>" data-recipe-tags="<?php echo esc_attr( implode( ',', $r['tags'] ) ); ?>" data-recipe-name="<?php echo esc_attr( strtolower( $r['name'] ) ); ?>"<?php echo isset( $r['dateTimestamp'] ) ? ' data-recipe-date="' . esc_attr( $r['dateTimestamp'] ) . '"' : ''; ?>>
					<button type="button" class="vance-rh-card-add" data-quick-add="<?php echo esc_attr( $r['slug'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Add %s to plan', 'vance-health-hub' ), $r['name'] ) ); ?>">+</button>
					<a href="<?php echo esc_url( $r['url'] ); ?>">
						<div class="vance-rh-card-img" style="background-image:url('<?php echo esc_url( $r['image'] ); ?>');"></div>
						<div class="vance-rh-card-body">
							<span class="vance-rh-card-cat"><?php echo esc_html( isset( $vance_categories[ $r['category'] ] ) ? $vance_categories[ $r['category'] ] : $r['category'] ); ?></span>
							<h3 class="vance-rh-card-name"><?php echo esc_html( $r['name'] ); ?></h3>
							<div class="vance-rh-card-facts"><?php echo $r['minutes'] ? esc_html( $r['minutes'] . ' min' ) : ''; ?><?php echo ( $r['minutes'] && $r['calories'] ) ? ' &middot; ' : ''; ?><?php echo $r['calories'] ? esc_html( $r['calories'] . ' kcal' ) : ''; ?></div>
							<?php if ( isset( $r['dateUploaded'] ) ) : ?>
								<div class="vance-rh-card-date"><?php esc_html_e( 'Uploaded', 'vance-health-hub' ); ?> <?php echo esc_html( $r['dateUploaded'] ); ?></div>
							<?php endif; ?>
						</div>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="vance-rh-section vance-rh-section--grey" id="planner">
	<div class="container">
		<div class="vance-rh-planner-head">
			<div>
				<h2 class="vance-rh-h2" style="margin-bottom:6px;"><?php esc_html_e( 'Your Weekly Plan', 'vance-health-hub' ); ?></h2>
				<input type="text" class="vance-rh-plan-name" id="vance-rh-plan-name" placeholder="<?php esc_attr_e( 'Name this plan (optional)', 'vance-health-hub' ); ?>">
			</div>
			<?php // Layout in recipe-hub.css — it needs a media query, which an inline style can't carry. ?>
			<div class="vance-rh-planner-actions">
				<div class="vance-rh-totals">
					<span><b id="vance-rh-total-meals">0</b> <?php esc_html_e( 'meals', 'vance-health-hub' ); ?></span>
					<span><b id="vance-rh-total-kcal">0</b> kcal</span>
				</div>
				<?php // Own wrapper so the three wrap as one block — see recipe-hub.css. ?>
				<div class="vance-rh-planner-buttons">
					<button type="button" class="vance-rh-clear" id="vance-rh-clear"><?php esc_html_e( 'Clear meal plan', 'vance-health-hub' ); ?></button>
					<button type="button" class="vance-rh-autofill" id="vance-rh-autofill"><?php esc_html_e( 'Let Vance Create Your Plan', 'vance-health-hub' ); ?></button>
					<button type="button" class="vance-rh-save" id="vance-rh-save"><?php esc_html_e( 'Save this meal plan', 'vance-health-hub' ); ?></button>
				</div>
			</div>
		</div>

		<div class="vance-rh-armed" id="vance-rh-armed">
			<span id="vance-rh-armed-text"></span>
			<button type="button" id="vance-rh-armed-cancel"><?php esc_html_e( 'Cancel', 'vance-health-hub' ); ?></button>
		</div>

		<div class="vance-rh-days" id="vance-rh-days">
			<?php foreach ( wp_list_pluck( vance_recipe_planner_days_skeleton(), 'day' ) as $day_name ) : ?>
				<div class="vance-rh-day" data-day="<?php echo esc_attr( $day_name ); ?>">
					<div class="vance-rh-day-head">
						<span class="vance-rh-day-name"><?php echo esc_html( $day_name ); ?></span>
						<span class="vance-rh-day-kcal" data-day-kcal></span>
					</div>
					<div class="vance-rh-slots">
						<?php foreach ( array( 'breakfast', 'lunch', 'dinner', 'snack' ) as $slot_key ) : ?>
							<div class="vance-rh-slot" data-day="<?php echo esc_attr( $day_name ); ?>" data-slot="<?php echo esc_attr( $slot_key ); ?>">
								<span class="vance-rh-slot-label"><?php echo esc_html( ucfirst( $slot_key ) ); ?></span>
								<div class="vance-rh-slot-body"></div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<p style="margin:20px 0 0; font-size:12px; color:#94A3B8; line-height:1.6;"><?php esc_html_e( 'Meal plans are a general guide, not personalised dietary advice. Check any dietary change with your health team.', 'vance-health-hub' ); ?></p>
	</div>
</section>

<div class="vance-rh-picker" id="vance-rh-picker" role="dialog" aria-modal="true" aria-hidden="true">
	<div class="vance-rh-picker-panel">
		<div class="vance-rh-picker-head">
			<strong id="vance-rh-picker-title"><?php esc_html_e( 'Choose a recipe', 'vance-health-hub' ); ?></strong>
			<button type="button" class="vance-rh-picker-close" id="vance-rh-picker-close" aria-label="<?php esc_attr_e( 'Close', 'vance-health-hub' ); ?>">&times;</button>
		</div>
		<input type="search" class="vance-rh-picker-search" id="vance-rh-picker-search" placeholder="<?php esc_attr_e( 'Search recipes…', 'vance-health-hub' ); ?>">
		<div class="vance-rh-picker-list" id="vance-rh-picker-list"></div>
	</div>
</div>

<?php
/**
 * Save dialog. "Update this plan" is only offered when a saved plan was opened
 * for editing (?plan=<key>) — otherwise there is nothing to update and the
 * dialog collapses to naming a new plan. JS toggles that row's visibility.
 */
?>
<div class="vance-rh-savemodal" id="vance-rh-savemodal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="vance-rh-savemodal-title">
	<div class="vance-rh-savemodal-panel">
		<div class="vance-rh-picker-head">
			<strong id="vance-rh-savemodal-title"><?php esc_html_e( 'Save this meal plan', 'vance-health-hub' ); ?></strong>
			<button type="button" class="vance-rh-picker-close" id="vance-rh-savemodal-close" aria-label="<?php esc_attr_e( 'Close', 'vance-health-hub' ); ?>">&times;</button>
		</div>
		<div class="vance-rh-savemodal-body">
			<div class="vance-rh-saveopt" id="vance-rh-saveopt-current" hidden>
				<div class="vance-rh-saveopt-text">
					<strong><?php esc_html_e( 'Save current plan', 'vance-health-hub' ); ?></strong>
					<span id="vance-rh-saveopt-current-sub"><?php esc_html_e( 'Overwrite the plan you opened, keeping its name.', 'vance-health-hub' ); ?></span>
				</div>
				<button type="button" class="vance-rh-save" id="vance-rh-save-current"><?php esc_html_e( 'Update', 'vance-health-hub' ); ?></button>
			</div>
			<div class="vance-rh-saveopt">
				<div class="vance-rh-saveopt-text" style="width:100%;">
					<strong><?php esc_html_e( 'Save as a new plan', 'vance-health-hub' ); ?></strong>
					<span><?php esc_html_e( 'Keeps any existing plan untouched and adds this one to your dashboard.', 'vance-health-hub' ); ?></span>
					<?php /* aria-label, not a .screen-reader-text <label>: that class is only
					         defined scoped under .vance-askai in this theme, so an unscoped one
					         would render as a visible duplicate label above the field. */ ?>
					<input type="text" id="vance-rh-save-newname" class="vance-rh-plan-name" style="width:100%;margin-top:10px;" aria-label="<?php esc_attr_e( 'Name for the new plan', 'vance-health-hub' ); ?>" placeholder="<?php esc_attr_e( 'Name this plan', 'vance-health-hub' ); ?>" maxlength="120">
				</div>
				<button type="button" class="vance-rh-save" id="vance-rh-save-new"><?php esc_html_e( 'Save new', 'vance-health-hub' ); ?></button>
			</div>
			<?php /* Start-over option. Local only: it empties the week in the browser,
			         it never touches a plan already saved to the dashboard. */ ?>
			<div class="vance-rh-saveopt vance-rh-saveopt--danger">
				<div class="vance-rh-saveopt-text">
					<strong><?php esc_html_e( 'Clear meal plan', 'vance-health-hub' ); ?></strong>
					<span><?php esc_html_e( 'Empties every meal from the week below so you can start again. Plans already saved to your dashboard are not affected.', 'vance-health-hub' ); ?></span>
				</div>
				<button type="button" class="vance-rh-clear" id="vance-rh-clear-modal"><?php esc_html_e( 'Clear', 'vance-health-hub' ); ?></button>
			</div>
		</div>
	</div>
</div>

<div class="vance-rh-toast" id="vance-rh-toast"></div>

</div>
