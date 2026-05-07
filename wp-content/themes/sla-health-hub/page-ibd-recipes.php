<?php
/**
 * Template Name: IBD Recipes & Meal Planner (Public)
 *
 * Public-facing wrapper around the IBD Recipes Next.js bundle. Visual shell
 * matches page-ask-ai.php via inc/tool-page-shell.php.
 *
 * To activate: create a Page titled "IBD Recipes & Meal Planner", slug
 * `ibd-recipes`, template = "IBD Recipes & Meal Planner (Public)".
 *
 * Note: the iframe height defaults higher (840px) because the recipe browser
 * needs more vertical room than the calculators.
 */
get_header();

$vance_tool_slug          = 'ibd-recipes';
$vance_tool_name          = vance_get_theme_mod( 'vance_tool_recipes_name', 'IBD Recipes & Meal Planner' );
$vance_tool_subtitle      = vance_get_theme_mod( 'vance_tool_recipes_subtitle', 'EPA-rich, gut-friendly recipes with full nutrition data. Browse and build a weekly plan freely — saving plans takes two clicks to create your free account.' );
$vance_tool_badge         = 'Meal Planning';
$vance_tool_hero_bg       = vance_get_theme_mod( 'vance_tool_recipes_hero_bg', get_template_directory_uri() . '/assets/img/about_hero.png' );
$vance_tool_hero_overlay  = vance_get_theme_mod( 'vance_tool_recipes_hero_overlay', 80 );
$vance_tool_iframe_height = 840;
$vance_tool_save_label    = 'Save this meal plan';

require get_template_directory() . '/inc/tool-page-shell.php';

get_footer();
