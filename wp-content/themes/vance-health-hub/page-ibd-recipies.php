<?php
/**
 * Template Name: IBD Recipes & Meal Planner (Public)
 *
 * Public-facing wrapper around the IBD Recipes Next.js bundle. Visual shell
 * matches page-ask-ai.php via inc/tool-page-shell.php.
 *
 * IMPORTANT — slug typo: the live WP page is `/ibd-recipies/` (legacy
 * misspelling, preserved to keep inbound links working). The file is named
 * to match for auto-template-binding. The bundle asset folder uses the
 * correct spelling `assets/tools/ibd-recipes/` — we override the iframe URL
 * accordingly via $vance_tool_iframe_src below.
 */
// Chromeless when opened inside the unified tool modal (inc/tool-modal.php).
$vance_embed = ( isset( $_GET['tool_embed'] ) && $_GET['tool_embed'] === '1' );
get_header( $vance_embed ? 'embed' : '' );

require_once get_template_directory() . '/inc/tool-brand-css.php';

$vance_tool_slug          = 'ibd-recipes';
$vance_tool_name          = vance_get_theme_mod( 'vance_tool_recipes_name', 'IBD Recipes & Meal Planner' );
$vance_tool_subtitle      = vance_get_theme_mod( 'vance_tool_recipes_subtitle', 'EPA-rich, gut-friendly recipes with full nutrition data. Browse and build a weekly plan freely, saving plans takes two clicks to create your free account.' );
$vance_tool_badge          = vance_get_theme_mod( 'vance_tool_recipes_badge', 'Meal Planning' );
$vance_tool_badge_bg       = vance_get_theme_mod( 'vance_tool_recipes_badge_bg', '' );
$vance_tool_badge_color    = vance_get_theme_mod( 'vance_tool_recipes_badge_color', '' );
$vance_tool_title_color    = vance_get_theme_mod( 'vance_tool_recipes_name_color', '' );
$vance_tool_title_size     = vance_get_theme_mod( 'vance_tool_recipes_name_size', 56 );
$vance_tool_subtitle_color = vance_get_theme_mod( 'vance_tool_recipes_subtitle_color', '' );
$vance_tool_subtitle_size  = vance_get_theme_mod( 'vance_tool_recipes_subtitle_size', 19 );
$vance_tool_hero_bg       = vance_get_theme_mod( 'vance_tool_recipes_hero_bg', get_template_directory_uri() . '/assets/img/about_hero.png' );
$vance_tool_hero_overlay  = vance_get_theme_mod( 'vance_tool_recipes_hero_overlay', 80 );
$vance_tool_iframe_height = 1100; // recipes browser needs vertical room before autoresize kicks in
$vance_tool_save_label    = 'Save this meal plan';

// Recipes app autoresize keeps pace with the recipe-card list as the user scrolls/filters.
$vance_tool_autoresize    = true;
// Brand-restyle: hides the bundle's internal header/nav (logo strip) + recolours
// teal. In modal mode it also collapses the bundle's own tall gradient hero to a
// single band — see vance_tool_brand_css_recipes_embed().
$vance_tool_brand_css     = vance_tool_brand_css_recipes( $vance_embed );

// Asset folder uses the correct spelling; explicitly point the iframe at it
// (overrides the auto-derived URL which would use the slug-typo path).
$vance_tool_iframe_src    = get_template_directory_uri() . '/assets/tools/ibd-recipes/index.html';

// Deep link: `/ibd-recipies/?recipe=<slug>` opens one recipe's page directly,
// inside the normal site chrome and brand CSS. The dashboard's saved meal
// plans link here so "open the full recipe" lands on the recipe rather than
// the browser index.
//
// The slug is validated against the recipe catalogue before it reaches the
// path — never interpolated raw — so this cannot be walked out of the bundle
// directory. An unknown slug silently falls through to the index.
if ( isset( $_GET['recipe'] ) ) {
	$vance_recipe_slug = vance_recipe_resolve_slug( wp_unslash( $_GET['recipe'] ) );
	if ( $vance_recipe_slug ) {
		$vance_tool_iframe_src = get_template_directory_uri()
			. '/assets/tools/ibd-recipes/recipes/' . $vance_recipe_slug . '/index.html';
		$vance_tool_name       = vance_recipe_catalogue()[ $vance_recipe_slug ]['name'];
		$vance_tool_subtitle   = 'Full method, ingredients and nutrition for this recipe. Browse the rest of the collection, or build it into a weekly plan.';
		// A single recipe is a fixed-length page — it does not need the tall
		// default reserved for the scrolling recipe browser.
		$vance_tool_iframe_height = 900;
	}
}

require get_template_directory() . '/inc/tool-page-shell.php';

get_footer( $vance_embed ? 'embed' : '' );
