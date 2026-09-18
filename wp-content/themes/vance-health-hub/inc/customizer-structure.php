<?php
/**
 * Customizer information architecture.
 *
 * The panels, sections and controls are registered where they always were:
 * functions.php, customizer-pages.php, inc/customizer-gi-health.php,
 * inc/dashboard-features.php, inc/page-hero-spotlight.php, inc/hero-mobile.php
 * and the vhh-annotations plugin. This file runs after all of them and decides
 * only three things about each section: which panel it sits in, what it is
 * called, and where it sorts. THE TITLES AND PANELS IN THOSE FILES ARE NOT WHAT
 * THE ADMIN SEES, THE MAP BELOW IS.
 *
 * Nothing here touches a setting id, so nothing stored in
 * theme_mods_vance-health-hub changes. Section ids do not change either, which
 * matters for the families whose setting keys are built from the same variable
 * as the section id (vance_gi_cond_*, vance_kb_cat_*, vance_dash_feature_*,
 * vance_cat_promo_sec_*) and for the panel / section / style_section entries
 * in vance_page_hero_spotlight_config().
 *
 * WordPress has no nested panels, so the seven silos (Site, Home, Content,
 * Section, Pages, Members, Website Functions) are a title prefix plus a
 * priority band. To back the whole reorganisation out, delete the require
 * line in functions.php.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Panels as the admin sees them: id => array( title, priority ).
 *
 * An id that already exists is retitled and re-sorted. An id that does not is
 * created. Dynamic families (one section per category, one per dashboard
 * feature) stay in their original panel, so only the panel needs an entry.
 */
function vance_customizer_structure_panels() {
	return array(
		// Site: identity and chrome.
		'vance_brand_panel'            => array( 'Site · Brand & Header', 10 ),
		'vance_footer_panel'           => array( 'Site · Footer', 11 ),
		'vance_mobile_panel'           => array( 'Site · Mobile', 12 ),

		// Home.
		'vance_homepage_panel'         => array( 'Home · Hero & Layout', 20 ),
		'vance_home_blocks_panel'      => array( 'Home · Blocks', 21 ),
		'vance_kb_panel'               => array( 'Home · Category Blocks', 22 ),

		// Content: categories and articles.
		'vance_content_panel'          => array( 'Content · Categories', 30 ),
		'vance_cat_promo_panel'        => array( 'Content · Category Promo Blocks', 31 ),
		'vance_articles_panel'         => array( 'Content · Articles', 32 ),
		'vance_content_widgets_panel'  => array( 'Content · Reusable Widgets', 33 ),

		// Section: the key destinations.
		'vance_kblobby_panel'          => array( 'Section · Knowledgebase', 40 ),
		'vance_gi_panel'               => array( 'Section · Gastro Conditions', 41 ),
		'vance_tools_panel'            => array( 'Section · Recipes & Tools', 42 ),
		'vance_patientdownloads_panel' => array( 'Section · Patient Downloads', 43 ),
		'vance_discounts_panel'        => array( 'Section · Benefits & Access', 44 ),
		'vance_askai_panel'            => array( 'Section · VANCE-Ai', 45 ),
		'vance_discovery_panel'        => array( 'Section · Discovery Engine', 46 ),
		'vance_edu_panel'              => array( 'Section · Education & Survey', 47 ),

		// Pages.
		'vance_about_panel'            => array( 'Pages · About Us', 50 ),
		'vance_evidence_panel'         => array( 'Pages · Get Started', 51 ),
		'vance_pat_panel'              => array( 'Pages · Patients & Practitioners', 52 ),
		'vance_contact_panel'          => array( 'Pages · Contact, Guide, Search, 404', 53 ),

		// Members.
		'vance_dashboard_panel'        => array( 'Members · Dashboard', 60 ),

		// Website Functions.
		'vance_advanced_panel'         => array( 'Website Functions', 70 ),
	);
}

/**
 * Sections: panel id => ordered list of section id => title.
 *
 * Order in the list is order on screen; priorities are generated in steps of
 * ten. A null title keeps the registered one. Sections not listed are left
 * exactly as registered, which is how the dynamic families and the dashboard
 * features keep their place.
 */
function vance_customizer_structure_sections() {
	$classic   = 'Hero (classic) + design switch';
	$spotlight = 'Hero (spotlight)';

	return array(
		'vance_brand_panel'            => array(
			'title_tagline'      => 'Site title & logo',
			'vance_header_nav'   => 'Header navigation',
			'vance_social_links' => 'Social media links',
		),
		'vance_footer_panel'           => array(
			'vance_newsletter'   => 'Newsletter',
			'vance_footer_brand' => 'Brand & widgets',
		),
		'vance_mobile_panel'           => array(
			'vance_mobile_experience'   => 'Bottom navigation & phone components',
			'vance_menu_promo_banners'  => 'Menu drawer banners',
			'vance_legal_heroes_mobile' => 'Policy page heroes on phones',
		),

		'vance_homepage_panel'         => array(
			'vance_homepage_order'          => 'Section order',
			'vance_section_dividers'        => 'Section dividers',
			'vance_hero_settings'           => $classic,
			'vance_hero_spotlight_settings' => $spotlight,
			'vance_hero_slide2_settings'    => 'Hero slide 2',
			'vance_hero_slide3_settings'    => 'Hero slide 3',
			'vance_hero_slide4_settings'    => 'Hero slide 4',
			'vance_hero_slide5_settings'    => 'Hero slide 5',
		),
		'vance_home_blocks_panel'      => array(
			'vance_pathway_content_settings'   => 'Prime block 1',
			'vance_prime_block_home2_settings' => 'Prime block 2',
			'vance_promo_block'                => 'Promo block',
			'vance_join_community'             => 'Join block',
			'vance_kb_mini_hero'               => 'Knowledgebase band',
			'vance_kb_content'                 => 'Knowledgebase content blocks',
			'vance_tool_widgets_row'           => 'Tool widgets row',
			'vance_gastro_conditions_settings' => 'Gastro condition tiles',
			'vance_testimonials_section'       => 'Testimonials',
			'vance_premium_content'            => 'Premium band: content',
			'vance_premium_card'               => 'Premium band: signup card',
			'vance_premium_colors'             => 'Premium band: colours',
			'vance_premium_sizing'             => 'Premium band: sizing',
		),

		'vance_content_panel'          => array(
			'vance_category_heroes'                 => 'Category heroes',
			'vance_subcategory_layouts'             => 'Sub-category layouts',
			'vance_homepage_categories'             => 'Category cards',
			'vance_prime_block_categories_settings' => 'Prime block on category pages',
		),
		'vance_articles_panel'         => array(
			'vance_post_hero_overlay'      => 'Article hero overlay',
			'vance_post_hero_overlay_cats' => 'Article hero overlay per category',
			'vhh_annotations'              => 'Article annotations',
		),
		'vance_content_widgets_panel'  => array(
			'vance_cw_1' => null,
			'vance_cw_2' => null,
			'vance_cw_3' => null,
			'vance_cw_4' => null,
			'vance_cw_5' => null,
		),

		'vance_kblobby_panel'          => array(
			'vance_kblobby_hero'            => $classic,
			'vance_kblobby_hero_spotlight'  => $spotlight,
			'vance_kblobby_intro'           => 'Intro',
			'vance_kblobby_layout'          => 'Layout & colour',
			'vance_kblobby_labels'          => 'Block labels',
			'vance_kblobby_source'          => 'Block source',
			'vance_kblobby_stats'           => 'Scale strip',
			'vance_kblobby_topics'          => 'Topics',
			'vance_kblobby_cond'            => 'Conditions',
			'vance_kblobby_tools'           => 'Tools',
			'vance_kblobby_latest'          => 'Latest articles',
			'vance_prime_block_kb_settings' => 'Prime block',
			'vance_kb_promo_block'          => 'Promo block',
		),
		'vance_gi_panel'               => array(
			'vance_gi_hub_hero'    => 'Hub: hero',
			'vance_gi_hub_grid'    => 'Hub: conditions grid',
			'vance_gi_hub_stats'   => 'Hub: stats band',
			'vance_gi_hub_cta'     => 'Hub: closing call to action',
			'vance_gi_colours'     => 'Colours, all condition pages',
			'vance_gi_shared'      => 'Last reviewed date, all condition pages',
			'vance_gi_cond_ibd'    => 'Condition: IBD (Inflammatory Bowel Disease)',
			'vance_gi_cond_uc'     => 'Condition: Ulcerative Colitis',
			'vance_gi_cond_crohns' => 'Condition: Crohn\'s Disease',
			'vance_gi_cond_mc'     => 'Condition: Microscopic Colitis',
			'vance_gi_cond_ibs'    => 'Condition: IBS (Irritable Bowel Syndrome)',
			'vance_gi_cond_crc'    => 'Condition: Colorectal Cancer',
			'vance_gi_cond_div'    => 'Condition: Diverticular Disease',
		),
		'vance_tools_panel'            => array(
			'vance_tools_hero'                       => 'Tools shelf: ' . $classic,
			'vance_tools_hero_spotlight'             => 'Tools shelf: ' . $spotlight,
			'vance_tools_intro'                      => 'Tools shelf: Intro',
			'vance_tools_hero_recipes'               => 'Recipes: ' . $classic,
			'vance_tools_hero_recipes_spotlight'     => 'Recipes: ' . $spotlight,
			'vance_tools_hero_malnutrition'          => 'Malnutrition calculator: ' . $classic,
			'vance_tools_hero_malnutrition_spotlight' => 'Malnutrition calculator: ' . $spotlight,
		),
		'vance_patientdownloads_panel' => array(
			'vance_patientdownloads_hero'           => $classic,
			'vance_patientdownloads_hero_spotlight' => $spotlight,
		),
		'vance_discounts_panel'        => array(
			'vance_discounts_hero'           => $classic,
			'vance_discounts_hero_spotlight' => $spotlight,
			'vance_discounts_featured'       => 'Featured discounts',
			'vance_discounts_suggest'        => 'Suggest a discount',
		),
		'vance_askai_panel'            => array(
			'vance_askai_settings'       => 'Configuration + hero design switch',
			'vance_askai_hero_spotlight' => $spotlight,
			'vance_modal_colors'         => 'Modal colours',
		),
		'vance_discovery_panel'        => array(
			'vance_discovery_general' => 'General',
			'vance_discovery_styling' => 'Styling',
		),
		'vance_edu_panel'              => array(
			'vance_edu_hero'             => 'Education: ' . $classic,
			'vance_edu_hero_spotlight'   => 'Education: ' . $spotlight,
			'vance_edu_intro'            => 'Education: Intro',
			'vance_edu_tracks'           => 'Education: Course tracks',
			'vance_edu_waitlist'         => 'Education: Waitlist signup',
			'vance_hquiz_hero'           => 'Gastro Health Survey: ' . $classic,
			'vance_hquiz_hero_spotlight' => 'Gastro Health Survey: ' . $spotlight,
		),

		'vance_about_panel'            => array(
			'vance_about_hero'           => $classic,
			'vance_about_hero_spotlight' => $spotlight,
			'vance_about_trust'          => 'Trust badges & stats',
			'vance_about_origin'         => 'The Vance Evolution',
			'vance_about_mission'        => 'Mission & values',
			'vance_about_product'        => 'Why patients trust us',
			'vance_about_testimonials'   => 'Patient stories',
			'vance_about_platform'       => 'Platform',
			'vance_about_cta'            => 'Closing call to action',
		),
		'vance_evidence_panel'         => array(
			'vance_evidence_hero'           => $classic,
			'vance_evidence_hero_spotlight' => $spotlight,
			'vance_evidence_pillars'        => 'Evidence pillars',
			'vance_evidence_proc'           => 'From insight to practice',
			'vance_evidence_feat'           => 'Featured evidence (post grid)',
			'vance_evidence_cta'            => 'Closing call to action',
			'vance_evidence_styling'        => 'Page styling',
		),
		'vance_pat_panel'              => array(
			'vance_pat_hero'      => 'Patients: Hero',
			'vance_pat_benefits'  => 'Patients: Benefits',
			'vance_pat_tools'     => 'Patients: Tools',
			'vance_pat_cta'       => 'Patients: Closing call to action',
			'vance_hcp_hero'      => 'Practitioners: Hero',
			'vance_hcp_resources' => 'Practitioners: Resources',
			'vance_hcp_collab'    => 'Practitioners: Collaborate',
			'vance_hcp_cta'       => 'Practitioners: Closing call to action',
		),
		'vance_contact_panel'          => array(
			'vance_contact_hero'             => 'Contact: ' . $classic,
			'vance_contact_hero_spotlight'   => 'Contact: ' . $spotlight,
			'vance_contact_info'             => 'Contact: Contact information',
			'vance_userguide_hero'           => 'User Guide: ' . $classic,
			'vance_userguide_hero_spotlight' => 'User Guide: ' . $spotlight,
			'vance_search_hero'              => 'Search results: Hero',
			'vance_search_form'              => 'Search results: Refine search form',
			'vance_search_empty'             => 'Search results: No results state',
			'vance_e404_hero_spotlight'      => 'Not found (404): ' . $spotlight,
		),

		'vance_advanced_panel'         => array(
			'vance_scripts'            => 'Tracking scripts',
			'vance_social_api'         => 'Social posting webhook',
			'vance_contact_recaptcha'  => 'Contact form spam protection',
			'custom_css'               => null,
		),
	);
}

/**
 * Single controls that move to a different section: control id => section id.
 *
 * The three overlay sliders were the only contents of the "Hero Overlays"
 * panel; each now sits with the classic hero it darkens.
 */
function vance_customizer_structure_controls() {
	return array(
		'vance_home_hero_overlay'     => 'vance_hero_settings',
		'vance_evidence_hero_overlay' => 'vance_evidence_hero',
		'vance_askai_hero_overlay'    => 'vance_askai_settings',
	);
}

/**
 * Apply the structure. Runs at 999, after every registration hook in the theme
 * (the latest is 30) and the plugin.
 *
 * Anything the map names that is not registered is skipped rather than
 * created, so switching the annotations plugin off, or deleting a section at
 * its source, degrades to a shorter panel and not to an error.
 */
function vance_customizer_structure_apply( $wp_customize ) {
	foreach ( vance_customizer_structure_panels() as $panel_id => $spec ) {
		$panel = $wp_customize->get_panel( $panel_id );
		if ( $panel ) {
			$panel->title    = $spec[0];
			$panel->priority = $spec[1];
		} else {
			$wp_customize->add_panel( $panel_id, array( 'title' => $spec[0], 'priority' => $spec[1] ) );
		}
	}

	foreach ( vance_customizer_structure_sections() as $panel_id => $sections ) {
		$priority = 0;
		foreach ( $sections as $section_id => $title ) {
			$priority += 10;
			$section   = $wp_customize->get_section( $section_id );
			if ( ! $section ) {
				continue;
			}
			$section->panel    = $panel_id;
			$section->priority = $priority;
			if ( null !== $title ) {
				$section->title = $title;
			}
		}
	}

	foreach ( vance_customizer_structure_controls() as $control_id => $section_id ) {
		$control = $wp_customize->get_control( $control_id );
		if ( $control && $wp_customize->get_section( $section_id ) ) {
			$control->section = $section_id;
		}
	}

	// Drop whatever the moves above emptied, whether it is a panel the map
	// folded into another or a section whose only controls moved out.
	$used_sections = array();
	foreach ( $wp_customize->controls() as $control ) {
		$used_sections[ $control->section ] = true;
	}
	$used_panels = array();
	foreach ( $wp_customize->sections() as $section ) {
		if ( 0 !== strpos( $section->id, 'vance_' ) ) {
			$used_panels[ $section->panel ] = true;
			continue;
		}
		if ( empty( $used_sections[ $section->id ] ) ) {
			$wp_customize->remove_section( $section->id );
			continue;
		}
		$used_panels[ $section->panel ] = true;
	}
	foreach ( $wp_customize->panels() as $panel ) {
		if ( 0 === strpos( $panel->id, 'vance_' ) && empty( $used_panels[ $panel->id ] ) ) {
			$wp_customize->remove_panel( $panel->id );
		}
	}
}
add_action( 'customize_register', 'vance_customizer_structure_apply', 999 );
