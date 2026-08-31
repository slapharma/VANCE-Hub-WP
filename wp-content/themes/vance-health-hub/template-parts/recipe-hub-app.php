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
 * Pass `$embedded = true` (set as a local before the include, WP's
 * get_template_part() doesn't accept args pre-5.5-style here since we
 * target the plain include path) when rendering inside the dashboard: adds
 * a class that drops the section padding/backgrounds that make sense on a
 * full page but not inside a dashboard card.
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

$vance_rh_embedded = ! empty( $vance_rh_embedded );

$vance_cat_filter = isset( $_GET['cat'] ) ? sanitize_key( wp_unslash( $_GET['cat'] ) ) : '';
$vance_recipes    = vance_recipe_planner_data();
$vance_categories = array(
	'breakfast' => __( 'Breakfast', 'vance-health-hub' ),
	'lunch'     => __( 'Lunch', 'vance-health-hub' ),
	'dinner'    => __( 'Dinner', 'vance-health-hub' ),
	'snacks'    => __( 'Snacks', 'vance-health-hub' ),
);
$vance_base_url = home_url( '/gastro-meal-planner/' );
?>
<div class="<?php echo $vance_rh_embedded ? 'vance-rh-embedded' : ''; ?>">

<section class="vance-rh-section" id="recipes">
	<div class="container">
		<h2 class="vance-rh-h2"><?php esc_html_e( 'Recipes', 'vance-health-hub' ); ?></h2>
		<div class="vance-rh-controls">
			<div class="vance-rh-chips">
				<a class="vance-rh-chip<?php echo ( '' === $vance_cat_filter ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( $vance_base_url ); ?>#recipes"><?php esc_html_e( 'All', 'vance-health-hub' ); ?></a>
				<?php foreach ( $vance_categories as $cat_slug => $cat_label ) : ?>
					<a class="vance-rh-chip<?php echo ( $vance_cat_filter === $cat_slug ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'cat', $cat_slug, $vance_base_url ) . '#recipes' ); ?>"><?php echo esc_html( $cat_label ); ?></a>
				<?php endforeach; ?>
			</div>
			<input type="search" class="vance-rh-search" id="vance-rh-search" placeholder="<?php esc_attr_e( 'Search recipes…', 'vance-health-hub' ); ?>">
		</div>
		<div class="vance-rh-grid" id="vance-rh-grid">
			<?php foreach ( $vance_recipes as $r ) :
				if ( $vance_cat_filter && $vance_cat_filter !== $r['category'] ) {
					continue; // No-JS fallback: server-side filter when JS hasn't taken over.
				}
				?>
				<div class="vance-rh-card" data-recipe-category="<?php echo esc_attr( $r['category'] ); ?>" data-recipe-name="<?php echo esc_attr( strtolower( $r['name'] ) ); ?>">
					<button type="button" class="vance-rh-card-add" data-quick-add="<?php echo esc_attr( $r['slug'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Add %s to plan', 'vance-health-hub' ), $r['name'] ) ); ?>">+</button>
					<a href="<?php echo esc_url( $r['url'] ); ?>">
						<div class="vance-rh-card-img" style="background-image:url('<?php echo esc_url( $r['image'] ); ?>');"></div>
						<div class="vance-rh-card-body">
							<span class="vance-rh-card-cat"><?php echo esc_html( isset( $vance_categories[ $r['category'] ] ) ? $vance_categories[ $r['category'] ] : $r['category'] ); ?></span>
							<h3 class="vance-rh-card-name"><?php echo esc_html( $r['name'] ); ?></h3>
							<div class="vance-rh-card-facts"><?php echo $r['minutes'] ? esc_html( $r['minutes'] . ' min' ) : ''; ?><?php echo ( $r['minutes'] && $r['calories'] ) ? ' &middot; ' : ''; ?><?php echo $r['calories'] ? esc_html( $r['calories'] . ' kcal' ) : ''; ?></div>
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
				<button type="button" class="vance-rh-clear" id="vance-rh-clear"><?php esc_html_e( 'Clear meal plan', 'vance-health-hub' ); ?></button>
				<button type="button" class="vance-rh-autofill" id="vance-rh-autofill"><?php esc_html_e( 'Let Vance Create Your Plan', 'vance-health-hub' ); ?></button>
				<button type="button" class="vance-rh-save" id="vance-rh-save"><?php esc_html_e( 'Save this meal plan', 'vance-health-hub' ); ?></button>
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

		<p style="margin:20px 0 0; font-size:12px; color:#94A3B8; line-height:1.6;"><?php esc_html_e( 'Meal plans are a general guide, not personalised dietary advice. Check any dietary change with your healthcare team.', 'vance-health-hub' ); ?></p>
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
