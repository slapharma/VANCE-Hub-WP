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
 * The grid + planner markup itself lives in
 * template-parts/recipe-hub-app.php, shared with the dashboard's "My
 * Recipes" tab (page-dashboard.php, case 'my-recipes') — this file supplies
 * only the hero, the register-modal include, and the disclaimer, all of
 * which are specific to the standalone page.
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
?>

<main id="main-content" class="vance-recipe-hub<?php echo $vance_embed ? ' vance-recipe-hub--embed' : ''; ?>">
<style>
.vance-rh-hero { background: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $vance_hero_alpha_top ); ?>), rgba(10,25,41,<?php echo esc_attr( $vance_hero_alpha_bottom ); ?>)), url('<?php echo esc_url( $vance_hero_bg ); ?>') center/cover; padding: 72px 0 56px; color: #fff; text-align: center; border-bottom: 3px solid var(--primary-color); }
.vance-rh-badge { display: inline-block; background: <?php echo $vance_hero_badge_bg ? esc_attr( $vance_hero_badge_bg ) : 'rgba(255,255,255,0.15)'; ?>; color: <?php echo $vance_hero_badge_color ? esc_attr( $vance_hero_badge_color ) : '#fff'; ?>; font-size: 12px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; padding: 6px 16px; border-radius: 20px; margin-bottom: 18px; }
.vance-rh-title { font-family: 'Outfit', sans-serif; font-weight: 900; margin: 0 0 14px; line-height: 1.15; font-size: clamp(28px, 4vw, <?php echo esc_attr( (int) $vance_hero_title_size ); ?>px); color: <?php echo $vance_hero_title_color ? esc_attr( $vance_hero_title_color ) : '#fff'; ?>; }
.vance-rh-subtitle { max-width: 640px; margin: 0 auto; font-size: <?php echo esc_attr( (int) $vance_hero_sub_size ); ?>px; line-height: 1.65; color: <?php echo $vance_hero_sub_color ? esc_attr( $vance_hero_sub_color ) : 'rgba(255,255,255,0.9)'; ?>; }
.vance-recipe-hub--embed .vance-rh-hero { display: none; }
</style>

<section class="vance-rh-hero">
	<div class="container">
		<span class="vance-rh-badge"><?php echo esc_html( $vance_hero_badge ); ?></span>
		<h1 class="vance-rh-title"><?php echo esc_html( $vance_hero_name ); ?></h1>
		<p class="vance-rh-subtitle"><?php echo esc_html( $vance_hero_subtitle ); ?></p>
	</div>
</section>

<?php get_template_part( 'template-parts/recipe-hub-app' ); ?>

<?php get_template_part( 'inc/register-modal' ); ?>

<div class="container" style="max-width:900px;">
	<p style="margin:30px 0 0; padding:20px 24px; background:#EEF6F6; border-left:4px solid var(--primary-color); font-size:13.5px; line-height:1.75; color:#475569;">
		<strong style="color:var(--secondary-color);"><?php esc_html_e( 'For general information only.', 'vance-health-hub' ); ?></strong>
		<?php esc_html_e( 'These recipes and meal plans are for general information and are not a substitute for professional medical or dietary advice. Always talk to your GP, dietitian or healthcare team before making significant changes to your diet.', 'vance-health-hub' ); ?>
	</p>
</div>

</main>

<?php get_footer( $vance_embed ? 'embed' : '' ); ?>
