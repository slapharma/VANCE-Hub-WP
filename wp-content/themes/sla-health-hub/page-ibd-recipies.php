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
get_header();

require_once get_template_directory() . '/inc/tool-brand-css.php';

$vance_tool_slug          = 'ibd-recipes';
$vance_tool_name          = vance_get_theme_mod( 'vance_tool_recipes_name', 'IBD Recipes & Meal Planner' );
$vance_tool_subtitle      = vance_get_theme_mod( 'vance_tool_recipes_subtitle', 'EPA-rich, gut-friendly recipes with full nutrition data. Browse and build a weekly plan freely — saving plans takes two clicks to create your free account.' );
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
// Brand-restyle: hides the bundle's internal header/nav (logo strip) + recolours teal.
$vance_tool_brand_css     = vance_tool_brand_css_recipes();

// Asset folder uses the correct spelling; explicitly point the iframe at it
// (overrides the auto-derived URL which would use the slug-typo path).
$vance_tool_iframe_src    = get_template_directory_uri() . '/assets/tools/ibd-recipes/index.html';

require get_template_directory() . '/inc/tool-page-shell.php';

get_footer();
