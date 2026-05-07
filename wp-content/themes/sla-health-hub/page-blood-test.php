<?php
/**
 * Template Name: Blood Test (Public)
 *
 * Public-facing wrapper around the IBD Blood Test Analyser. Visual shell now
 * matches page-ask-ai.php / page-omega-3-calculator.php / page-malnutrition-calculator.php
 * via inc/tool-page-shell.php — same hero, same iframe card, same Save flow.
 *
 * The previous template lived in this same file with its own bespoke hero +
 * info-card sections (~600 lines). Replaced wholesale to bring blood-test into
 * line with the rest of the tool suite.
 *
 * Filename intentionally preserved (`page-blood-test.php`) so WP's auto
 * template-binding to the existing /blood-test/ Page keeps working without an
 * admin re-pick.
 */
get_header();

require_once get_template_directory() . '/inc/tool-brand-css.php';

$vance_tool_slug         = 'blood-test';
$vance_tool_name         = vance_get_theme_mod( 'vance_tool_blood_name', 'IBD Blood Test Analyser' );
$vance_tool_subtitle     = vance_get_theme_mod( 'vance_tool_blood_subtitle', 'Drop in your blood panel results and get plain-language analysis flagging anything outside reference ranges. Designed to help you prepare for your next clinic appointment.' );
$vance_tool_badge        = 'Lab Results';
$vance_tool_hero_bg      = vance_get_theme_mod( 'vance_tool_blood_hero_bg', get_template_directory_uri() . '/assets/img/about_hero.png' );
$vance_tool_hero_overlay = vance_get_theme_mod( 'vance_tool_blood_hero_overlay', 80 );
$vance_tool_save_label   = 'Save my biomarker report';
$vance_tool_autoresize   = true;
$vance_tool_brand_css    = vance_tool_brand_css_calculator();

require get_template_directory() . '/inc/tool-page-shell.php';

get_footer();
