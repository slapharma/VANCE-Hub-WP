<?php
/**
 * Template Name: Omega-3 Calculator (Public)
 *
 * Public-facing wrapper around the Omega-3 Calculator iframe. Visual shell
 * matches `page-ask-ai.php` via inc/tool-page-shell.php — same hero, card,
 * and save-on-completion pattern.
 *
 * To activate: create a Page titled "Omega-3 Calculator", slug
 * `omega-3-calculator`, template = "Omega-3 Calculator (Public)".
 *
 * Customizer: Appearance → Customize → Tools & Resources Page → Omega-3.
 */
get_header();

$vance_tool_slug         = 'omega-3-calculator';
$vance_tool_name         = vance_get_theme_mod( 'vance_tool_omega_name', 'Omega-3 Calculator' );
$vance_tool_subtitle     = vance_get_theme_mod( 'vance_tool_omega_subtitle', 'Calculate your personalised EPA + DHA target based on body weight, dietary intake, and clinical guidance — built on the latest gastroenterology evidence.' );
$vance_tool_badge        = 'Nutrition Calculator';
$vance_tool_hero_bg      = vance_get_theme_mod( 'vance_tool_omega_hero_bg', get_template_directory_uri() . '/assets/img/about_hero.png' );
$vance_tool_hero_overlay = vance_get_theme_mod( 'vance_tool_omega_hero_overlay', 80 );
$vance_tool_save_label   = 'Save my Omega-3 plan';

require get_template_directory() . '/inc/tool-page-shell.php';

get_footer();
