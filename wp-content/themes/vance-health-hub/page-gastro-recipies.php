<?php
/**
 * Template Name: Gastro Recipes & Meal Planner (Public)
 *
 * Phase 3 of the recipe rebuild: native recipe grid + 7x4 weekly planner,
 * replacing the iframed Next.js bundle and inc/tool-page-shell.php entirely
 * for this page. All interactivity (the picker, drag-free "click a slot",
 * save) lives in assets/js/recipe-planner.js — vanilla JS, no build step,
 * config handed over via wp_localize_script() (functions.php,
 * vance_health_hub_scripts()) using vance_recipe_planner_script_config()
 * (inc/recipe-frontend.php).
 *
 * Hero copy/styling still reads the same vance_tool_recipes_* theme mods the
 * old iframe wrapper did, so the existing Customizer controls
 * (customizer-pages.php) keep working untouched.
 *
 * RENAMED from page-ibd-recipies.php 2026-08-20 (IBD -> Gastro branding).
 * The AJAX save/history tool slug ('ibd-recipes') and the CPT taxonomy term
 * slugs are unchanged (CLAUDE.md constraint 2; nothing else depends on this
 * file's own name — see inc/recipe-cpt.php).
 *
 * PAGE BINDING: the live WP Page is `/gastro-meal-planner/`, bound via its
 * `_wp_page_template` meta rather than the page-{slug}.php filename
 * convention (this file's name doesn't match the page's slug) — the
 * `Template Name:` header above is what makes that possible.
 */

// Chromeless when opened inside the unified tool modal (inc/tool-modal.php).
$vance_embed = ( isset( $_GET['tool_embed'] ) && '1' === $_GET['tool_embed'] );
get_header( $vance_embed ? 'embed' : '' );

$vance_hero_name          = vance_get_theme_mod( 'vance_tool_recipes_name', 'Gastro Recipes & Meal Planner' );
$vance_hero_subtitle      = vance_get_theme_mod( 'vance_tool_recipes_subtitle', 'EPA-rich, gut-friendly recipes with full nutrition data. Browse and build a weekly plan freely, saving plans takes two clicks to create your free account.' );
$vance_hero_badge         = vance_get_theme_mod( 'vance_tool_recipes_badge', 'Meal Planning' );
$vance_hero_badge_bg      = vance_get_theme_mod( 'vance_tool_recipes_badge_bg', '' );
$vance_hero_badge_color   = vance_get_theme_mod( 'vance_tool_recipes_badge_color', '' );
$vance_hero_title_color   = vance_get_theme_mod( 'vance_tool_recipes_name_color', '' );
$vance_hero_title_size    = vance_get_theme_mod( 'vance_tool_recipes_name_size', 56 );
$vance_hero_sub_color     = vance_get_theme_mod( 'vance_tool_recipes_subtitle_color', '' );
$vance_hero_sub_size      = vance_get_theme_mod( 'vance_tool_recipes_subtitle_size', 19 );
$vance_hero_bg            = vance_get_theme_mod( 'vance_tool_recipes_hero_bg', get_template_directory_uri() . '/assets/img/patient_hero.png' );
$vance_hero_overlay_pct   = vance_get_theme_mod( 'vance_tool_recipes_hero_overlay', 80 );
$vance_hero_alpha_top     = max( 0, min( 100, (int) $vance_hero_overlay_pct ) ) / 100;
$vance_hero_alpha_bottom  = min( 1, $vance_hero_alpha_top + 0.05 );

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

<main id="main-content" class="vance-recipe-hub<?php echo $vance_embed ? ' vance-recipe-hub--embed' : ''; ?>">
<style>
.vance-rh-hero { background: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $vance_hero_alpha_top ); ?>), rgba(10,25,41,<?php echo esc_attr( $vance_hero_alpha_bottom ); ?>)), url('<?php echo esc_url( $vance_hero_bg ); ?>') center/cover; padding: 72px 0 56px; color: #fff; text-align: center; border-bottom: 3px solid var(--primary-color); }
.vance-rh-badge { display: inline-block; background: <?php echo $vance_hero_badge_bg ? esc_attr( $vance_hero_badge_bg ) : 'rgba(255,255,255,0.15)'; ?>; color: <?php echo $vance_hero_badge_color ? esc_attr( $vance_hero_badge_color ) : '#fff'; ?>; font-size: 12px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; padding: 6px 16px; border-radius: 20px; margin-bottom: 18px; }
.vance-rh-title { font-family: 'Outfit', sans-serif; font-weight: 900; margin: 0 0 14px; line-height: 1.15; font-size: clamp(28px, 4vw, <?php echo esc_attr( (int) $vance_hero_title_size ); ?>px); color: <?php echo $vance_hero_title_color ? esc_attr( $vance_hero_title_color ) : '#fff'; ?>; }
.vance-rh-subtitle { max-width: 640px; margin: 0 auto; font-size: <?php echo esc_attr( (int) $vance_hero_sub_size ); ?>px; line-height: 1.65; color: <?php echo $vance_hero_sub_color ? esc_attr( $vance_hero_sub_color ) : 'rgba(255,255,255,0.9)'; ?>; }
.vance-recipe-hub--embed .vance-rh-hero { display: none; }

.vance-rh-section { padding: 48px 0; }
.vance-rh-section--grey { background: #f8fafc; }
.vance-rh-h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: #0A1929; margin: 0 0 20px; }

.vance-rh-controls { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.vance-rh-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.vance-rh-chip { display: inline-block; padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid #e2e8f0; color: #475569; background: #fff; }
.vance-rh-chip.is-active { background: var(--primary-color); border-color: var(--primary-color); color: #fff; }
.vance-rh-search { flex: 0 0 240px; max-width: 100%; padding: 9px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; }

.vance-rh-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
.vance-rh-card { position: relative; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); text-decoration: none; display: block; }
.vance-rh-card-img { height: 150px; background-color: #cbd5e1; background-size: cover; background-position: center; }
.vance-rh-card-body { padding: 16px; }
.vance-rh-card-cat { display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: 0.3px; text-transform: uppercase; color: var(--primary-color); margin-bottom: 6px; }
.vance-rh-card-name { font-size: 15px; font-weight: 700; color: #0f172a; line-height: 1.35; margin: 0 0 8px; }
.vance-rh-card-facts { font-size: 12px; color: #64748b; }
.vance-rh-card-add { position: absolute; top: 10px; right: 10px; width: 34px; height: 34px; border-radius: 50%; background: rgba(10,25,41,0.75); color: #fff; border: none; font-size: 18px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.vance-rh-card-add:hover { background: var(--primary-color); }

.vance-rh-planner-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
.vance-rh-plan-name { padding: 9px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; min-width: 220px; }
.vance-rh-totals { display: flex; gap: 18px; font-size: 13px; color: #475569; }
.vance-rh-totals b { color: #0A1929; font-family: 'Outfit', sans-serif; }
.vance-rh-save { background: var(--primary-color); color: #fff; border: none; font-weight: 700; font-size: 14px; padding: 12px 26px; border-radius: 8px; cursor: pointer; }
.vance-rh-save:disabled { opacity: 0.6; cursor: default; }

.vance-rh-days { display: flex; flex-direction: column; gap: 14px; }
.vance-rh-day { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; }
.vance-rh-day-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
.vance-rh-day-name { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 15px; color: #0A1929; }
.vance-rh-day-kcal { font-size: 12px; color: #64748b; }
.vance-rh-slots { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
@media (max-width: 720px) { .vance-rh-slots { grid-template-columns: repeat(2, 1fr); } }
.vance-rh-slot { border: 1px dashed #cbd5e1; border-radius: 8px; padding: 10px; min-height: 92px; display: flex; flex-direction: column; }
.vance-rh-slot-label { font-size: 10.5px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase; color: #94a3b8; margin-bottom: 6px; }
.vance-rh-slot-add { flex: 1; background: none; border: none; color: #94a3b8; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.vance-rh-slot-add:hover { color: var(--primary-color); }
.vance-rh-slot-filled { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
.vance-rh-slot-meal-name { font-size: 12.5px; font-weight: 600; color: #0f172a; line-height: 1.3; }
.vance-rh-slot-meal-facts { font-size: 11px; color: #64748b; margin-top: 4px; }
.vance-rh-slot-remove { align-self: flex-end; background: none; border: none; color: #94a3b8; font-size: 11px; cursor: pointer; padding: 4px 0 0; }
.vance-rh-slot-remove:hover { color: #dc2626; }

.vance-rh-armed { display: none; background: #EEF6F6; border: 1px solid var(--primary-color); border-radius: 10px; padding: 14px 18px; margin-bottom: 18px; font-size: 14px; color: #0A1929; align-items: center; justify-content: space-between; gap: 12px; }
.vance-rh-armed.is-visible { display: flex; }
.vance-rh-armed button { background: none; border: 1px solid var(--primary-color); color: var(--primary-color); border-radius: 6px; padding: 6px 14px; font-size: 12.5px; font-weight: 700; cursor: pointer; }

.vance-rh-picker { display: none; position: fixed; inset: 0; z-index: 100020; align-items: center; justify-content: center; padding: 20px; background: rgba(10,25,41,0.72); }
.vance-rh-picker.is-open { display: flex; }
.vance-rh-picker-panel { background: #fff; border-radius: 14px; width: 100%; max-width: 560px; max-height: 80vh; display: flex; flex-direction: column; overflow: hidden; }
.vance-rh-picker-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; border-bottom: 1px solid #e2e8f0; }
.vance-rh-picker-close { background: none; border: none; font-size: 22px; cursor: pointer; color: #64748b; }
.vance-rh-picker-search { margin: 14px 20px 0; padding: 9px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; }
.vance-rh-picker-list { overflow-y: auto; padding: 14px 20px 20px; display: flex; flex-direction: column; gap: 8px; }
.vance-rh-picker-item { display: flex; align-items: center; gap: 12px; padding: 8px; border-radius: 8px; cursor: pointer; border: 1px solid transparent; text-align: left; background: none; width: 100%; }
.vance-rh-picker-item:hover { background: #f8fafc; border-color: #e2e8f0; }
.vance-rh-picker-thumb { width: 44px; height: 44px; border-radius: 6px; background-size: cover; background-position: center; background-color: #cbd5e1; flex: none; }
.vance-rh-picker-name { font-size: 13.5px; font-weight: 600; color: #0f172a; }
.vance-rh-picker-facts { font-size: 11.5px; color: #64748b; }

.vance-rh-toast { position: fixed; left: 50%; bottom: 24px; transform: translate(-50%, 20px); background: #0A1929; color: #fff; font-size: 13.5px; padding: 12px 22px; border-radius: 8px; z-index: 100060; opacity: 0; pointer-events: none; transition: opacity 200ms ease, transform 200ms ease; }
.vance-rh-toast.is-visible { opacity: 1; transform: translate(-50%, 0); }
</style>

<section class="vance-rh-hero">
	<div class="container">
		<span class="vance-rh-badge"><?php echo esc_html( $vance_hero_badge ); ?></span>
		<h1 class="vance-rh-title"><?php echo esc_html( $vance_hero_name ); ?></h1>
		<p class="vance-rh-subtitle"><?php echo esc_html( $vance_hero_subtitle ); ?></p>
	</div>
</section>

<section class="vance-rh-section">
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
			<div style="display:flex;align-items:center;gap:18px;">
				<div class="vance-rh-totals">
					<span><b id="vance-rh-total-meals">0</b> <?php esc_html_e( 'meals', 'vance-health-hub' ); ?></span>
					<span><b id="vance-rh-total-kcal">0</b> kcal</span>
				</div>
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

<div class="vance-rh-toast" id="vance-rh-toast"></div>

<?php get_template_part( 'inc/register-modal' ); ?>

<div class="container" style="max-width:900px;">
	<p style="margin:30px 0 0; padding:20px 24px; background:#EEF6F6; border-left:4px solid var(--primary-color); font-size:13.5px; line-height:1.75; color:#475569;">
		<strong style="color:var(--secondary-color);"><?php esc_html_e( 'For general information only.', 'vance-health-hub' ); ?></strong>
		<?php esc_html_e( 'These recipes and meal plans are for general information and are not a substitute for professional medical or dietary advice. Always talk to your GP, dietitian or healthcare team before making significant changes to your diet.', 'vance-health-hub' ); ?>
	</p>
</div>

</main>

<?php get_footer( $vance_embed ? 'embed' : '' ); ?>
