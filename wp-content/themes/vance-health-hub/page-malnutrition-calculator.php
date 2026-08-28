<?php
/**
 * Template Name: Malnutrition Calculator (Public)
 *
 * Public-facing wrapper around the Malnutrition Calculator iframe. Visual
 * shell matches page-ask-ai.php via inc/tool-page-shell.php.
 *
 * To activate: create a Page titled "Malnutrition Calculator", slug
 * `malnutrition-calculator`, template = "Malnutrition Calculator (Public)".
 */
// Chromeless when opened inside the unified tool modal (inc/tool-modal.php).
$vance_embed = ( isset( $_GET['tool_embed'] ) && $_GET['tool_embed'] === '1' );
get_header( $vance_embed ? 'embed' : '' );

require_once get_template_directory() . '/inc/tool-brand-css.php';

$vance_tool_slug         = 'malnutrition-calculator';
$vance_tool_name         = vance_get_theme_mod( 'vance_tool_malnutrition_name', 'IBD Malnutrition Calculator' );
$vance_tool_subtitle     = vance_get_theme_mod( 'vance_tool_malnutrition_subtitle', 'Clinically-grounded 11-step malnutrition risk screener for IBD patients. Combines MUST, IBD-NST, and GLIM criteria into a single, actionable score.' );
$vance_tool_badge          = vance_get_theme_mod( 'vance_tool_malnutrition_badge', 'IBD Screening' );
$vance_tool_badge_bg       = vance_get_theme_mod( 'vance_tool_malnutrition_badge_bg', '' );
$vance_tool_badge_color    = vance_get_theme_mod( 'vance_tool_malnutrition_badge_color', '' );
$vance_tool_title_color    = vance_get_theme_mod( 'vance_tool_malnutrition_name_color', '' );
$vance_tool_title_size     = vance_get_theme_mod( 'vance_tool_malnutrition_name_size', 56 );
$vance_tool_subtitle_color = vance_get_theme_mod( 'vance_tool_malnutrition_subtitle_color', '' );
$vance_tool_subtitle_size  = vance_get_theme_mod( 'vance_tool_malnutrition_subtitle_size', 19 );
$vance_tool_hero_bg      = vance_get_theme_mod( 'vance_tool_malnutrition_hero_bg', get_template_directory_uri() . '/assets/img/patient_hero.png' );
$vance_tool_hero_overlay = vance_get_theme_mod( 'vance_tool_malnutrition_hero_overlay', 80 );
$vance_tool_save_label   = 'Save my screening result';
$vance_tool_autoresize   = true;
$vance_tool_brand_css    = vance_tool_brand_css_calculator();
// Lets the shell draw the spotlight hero when this page's design is switched
// to it; 'classic' until an admin says otherwise.
$vance_tool_hero_page    = 'malnutrition';

require get_template_directory() . '/inc/tool-page-shell.php';

get_footer( $vance_embed ? 'embed' : '' );
