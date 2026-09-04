<?php
/**
 * Page heroes — "spotlight" layout.
 *
 * The same light, search-led hero the homepage uses (inc/hero-spotlight.php),
 * carried across to /contact-us/, /about/ and the three free-tool pages: a
 * pale mint gradient band with the eyebrow, headline, intro and two CTAs on
 * the left, a photograph dissolving into the background on the right, and a
 * card floating over the lower right.
 *
 * Where the homepage puts its search field, each page puts the thing its own
 * visitors came for:
 *
 *   contact — the three direct lines (email, phone, opening hours), as real
 *             mailto:/tel: links, so somebody who would rather not use the
 *             form never has to scroll to find them.
 *   about   — the three assurance badges, which the dark hero already shows
 *             as a loose row of ticks.
 *   the      the OTHER free tools, plus the shelf they sit on. A visitor on
 *   three    /malnutrition-calculator/ can already see the calculator; what
 *   tools    they cannot see is that the survey and the meal planner exist
 *            and are equally free. The cells read each tool's own name and
 *            badge settings, so renaming a tool in the Customizer renames it
 *            in the other two heroes as well.
 *
 * None of those is new content: they all read settings the pages already
 * have. Same for the About card, which is stat 1 from the Trust Badges &
 * Stats section.
 *
 * NO PAGE SWITCHES ON ITS OWN. Every `vance_{page}_hero_style` defaults to
 * 'classic', so deploying this file changes nothing until an admin flips the
 * control. That mirrors `vance_hero_style` on the homepage, and it means the
 * dark hero and every one of its saved settings is always one toggle away.
 *
 * @package vance-health-hub
 * @since   2026-08-28
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Everything that differs between the two pages, in one place.
 *
 * The renderer, the value resolver and the Customizer registration all read
 * this, so a page cannot end up with a control the renderer ignores or a
 * default the control list has never heard of.
 *
 * @param string $page A key of vance_page_hero_spotlight_config().
 * @return array<string, mixed>|null Null for an unknown page.
 */
function vance_page_hero_spotlight_config( $page ) {
	// Every photograph below was made FOR this hero and lives in one directory
	// of its own. Nothing here is borrowed any more: until 2026-08-31 these came
	// out of assets/img/about/ and assets/img/gi-health/, bought for pages that
	// wanted them for their own reasons, and two of them were a condition page's
	// picture doing double duty -- Ask AI had Crohn's, the User Guide had IBD.
	//
	// The brief they were cut to, which any replacement has to meet:
	//   1400x876 exactly, because the renderer prints those as the <img>'s
	//     width/height and a different shape makes that a lie the browser
	//     corrects after layout -- the shift the attribute exists to stop;
	//   subject right of centre, because the media box is only the right ~52%;
	//   left third bright and empty, because that edge is dissolved into the
	//     band by two gradients and anything dark there reads as a smear;
	//   focal point high, because object-position is 46% 14%.
	// tests/gen-heroes.py holds the prompts and the reasoning; hero-render's
	// section 5f asserts the files exist and are the right shape.
	$img_hero = get_template_directory_uri() . '/assets/img/heroes/';

	// The User Guide's downloadable PDF. page-user-guide.php defines VUG_PDF_FILE,
	// but only while that template is rendering -- this function also runs during
	// Customizer registration, where it is not defined. The literal is the
	// fallback, and tests/hero-render.test.php asserts it still matches the
	// constant in that template, so the two cannot drift.
	$pdf = get_template_directory_uri() . '/assets/downloads/'
		. ( defined( 'VUG_PDF_FILE' ) ? VUG_PDF_FILE : 'Vance-Health-Hub-User-Guide.pdf' );

	$conf = array(
		'contact' => array(
			'name'         => __( 'Contact Us', 'vance-health-hub' ),
			'short_name'   => __( 'Contact', 'vance-health-hub' ),
			'panel'        => 'vance_contact_panel',
			'section'      => 'vance_contact_hero_spotlight',
			// The section the DESIGN TOGGLE goes in — the page's existing hero
			// section, so it sits with the classic hero it switches away from.
			// Not derivable from the page key: the tool pages keep their hero
			// controls under the Tools panel, named after the tool.
			'style_section'    => 'vance_contact_hero',
			// Where the classic hero's fallbacks live. tests/hero-render.test.php
			// reads this file and asserts each legacy_*_default below appears in
			// it verbatim, so the two cannot drift and switching design cannot
			// silently reword the page.
			'classic_template' => 'page-contact-us.php',
			'priority'     => 9,
			// The classic hero's own copy keys. The spotlight hero reads these
			// directly rather than taking private copies: only the layout is
			// changing, so an admin editing "Description" should see it move in
			// whichever design is switched on. (The homepage needed private
			// copies because its classic hero is a multi-slide carousel and
			// slide 1's copy is entangled with the rest of the slider.)
			'legacy_tag'   => 'vance_contact_hero_tag',
			'legacy_title' => 'vance_contact_hero_title',
			'legacy_desc'  => 'vance_contact_hero_desc',
			// The fallbacks the CLASSIC hero passes to get_theme_mod(), copied
			// verbatim. They are what the page says on a site that has never
			// edited these fields, so the spotlight has to say the same or
			// switching design silently empties the hero -- and only on the
			// live site, because the Customizer preview serves each setting's
			// registered default and looks perfectly fine.
			'legacy_tag_default'   => 'Get in Touch',
			'legacy_title_default' => 'We\'d Love to <span class="highlight">Hear From You</span>',
			'legacy_desc_default'  => 'Whether you\'re a patient, healthcare professional, researcher, or media contact, our team is here to help. Reach out and we\'ll respond within one business day.',
			// A separate image key, NOT vance_contact_hero_img. The classic
			// default (hcp_hero.png) was chosen to sit under a 78% navy veil;
			// dropped onto a pale band with no veil it reads as a dark smear.
			'image'        => $img_hero . 'contact.jpg',
			'image_alt'    => __( 'A woman at a desk in a bright office, looking up from her keyboard mid-reply', 'vance-health-hub' ),
			'btn1_text'    => __( 'Send us a message', 'vance-health-hub' ),
			'btn1_link'    => '#contact-form',
			'btn2_text'    => __( 'Ask the Hub AI instead', 'vance-health-hub' ),
			'btn2_link'    => '', // empty => resolved from btn2_fallback_slug, see values().
			'btn2_fallback_slug' => 'ask-ai',
			'btn2_fallback_path' => '/ask-ai/',
			'slot'         => 'lines',
			'slot_label'   => __( 'Prefer to reach us directly?', 'vance-health-hub' ),
			'card'         => 'text',
			'card_icon'    => 'chat',
			'card_title'   => __( 'A real reply, within one business day', 'vance-health-hub' ),
			'card_text'    => __( 'Messages sent from this page reach the Vance Medical team directly, no ticket queue, no automated loop.', 'vance-health-hub' ),
		),
		'about' => array(
			'name'         => __( 'About Us', 'vance-health-hub' ),
			'short_name'   => __( 'About', 'vance-health-hub' ),
			'panel'        => 'vance_about_panel',
			'section'      => 'vance_about_hero_spotlight',
			'style_section'    => 'vance_about_hero',
			'classic_template' => 'page-about.php',
			'priority'     => 9,
			'legacy_tag'   => 'vance_about_hero_tag',
			'legacy_title' => 'vance_about_hero_title',
			'legacy_desc'  => 'vance_about_hero_desc',
			// As above: the classic hero's own fallbacks, copied verbatim.
			'legacy_tag_default'   => 'About Vance Medical Hub',
			'legacy_title_default' => 'Trusted by Patients.<br><span class="highlight">Driven by Science.</span>',
			'legacy_desc_default'  => 'We bridge pharmaceutical expertise with nutritional science to empower patients living with gastrointestinal conditions, delivering evidence-based care you can trust.',
			// Likewise not vance_about_hero_img: its saved default,
			// diverse-patients-clinic.jpg, is a low-resolution watermarked
			// stock frame that only survives because the navy veil hides it.
			'image'        => $img_hero . 'about.jpg',
			'image_alt'    => __( 'Two scientists at a laboratory bench, one holding a flask up to the light', 'vance-health-hub' ),
			'btn1_text'    => __( 'Read our story', 'vance-health-hub' ),
			'btn1_link'    => '#our-story',
			// #mission is an anchor this page already renders. Our Heritage is
			// deliberately not used: customizer-pages.php retires that panel at
			// the end of its registration, so the page is on its way out.
			'btn2_text'    => __( 'Our mission and values', 'vance-health-hub' ),
			'btn2_link'    => '#mission',
			'slot'         => 'badges',
			'slot_label'   => __( 'What everything we publish is held to', 'vance-health-hub' ),
			'card'         => 'stat',
			'card_icon'    => 'flask',
			'card_title'   => '',
			'card_text'    => __( 'Formulated and reviewed under the same regulatory frameworks as prescription medicines.', 'vance-health-hub' ),
		),

		/*
		 * ---- The three free tools ----------------------------------------
		 *
		 * Deliberately identical in shape, because they are sold as a set:
		 * the Tools & Resources page presents exactly these three under one
		 * "Free Tools" heading, and a visitor who lands on one of them from
		 * search has no way of knowing the other two exist. So the band that
		 * holds Contact's phone number holds, here, the other two tools and
		 * the shelf they sit on — see vance_page_hero_spotlight_tools().
		 *
		 * Each one's photograph is its own key, per the rule that a classic
		 * hero's background was chosen to sit under a ~78% navy veil and
		 * reads as a dark smear without one. Every image named below was
		 * looked at before it was chosen; all three are light down their
		 * left-hand side, which is the edge the band dissolves.
		 */
		'hquiz' => array(
			'name'         => __( 'Gastro Health Survey', 'vance-health-hub' ),
			'short_name'   => __( 'Survey', 'vance-health-hub' ),
			'panel'        => 'vance_hquiz_panel',
			'section'      => 'vance_hquiz_hero_spotlight',
			'section_title'    => __( 'Hero: Spotlight', 'vance-health-hub' ),
			'style_section'    => 'vance_hquiz_hero',
			'classic_template' => 'page-healthcare-quiz.php',
			'priority'     => 160,
			'legacy_tag'   => 'vance_hquiz_hero_badge',
			'legacy_title' => 'vance_hquiz_hero_title',
			'legacy_desc'  => 'vance_hquiz_hero_subtitle',
			'legacy_tag_default'   => 'Self-Assessment',
			'legacy_title_default' => 'Gastro Health Survey',
			'legacy_desc_default'  => 'A short, evidence-based questionnaire covering symptom patterns, dietary triggers, and lifestyle factors. Answers are private, get an instant summary you can share with your clinician.',
			// Was gi-health/ibs.webp -- also the IBS condition page's photograph,
			// taken because it was the best-lit asset available rather than the
			// right one. This one was made for the page: answering something
			// privately and unhurriedly, which is what the survey asks of you.
			'image'        => $img_hero . 'survey.jpg',
			'image_alt'    => __( 'A man sitting by a window at home, answering something on his phone', 'vance-health-hub' ),
			'btn1_text'    => __( 'Start the survey', 'vance-health-hub' ),
			// The quiz form itself, which is the first thing below the hero.
			'btn1_link'    => '#health-quiz-form',
			'btn2_text'    => __( 'Ask the Hub AI instead', 'vance-health-hub' ),
			'btn2_link'    => '',
			'btn2_fallback_slug' => 'ask-ai',
			'btn2_fallback_path' => '/ask-ai/',
			'slot'         => 'tools',
			'slot_label'   => __( 'The other free tools', 'vance-health-hub' ),
			'card'         => 'text',
			'card_icon'    => 'clipboard',
			'card_title'   => __( 'A summary you can hand to your clinician', 'vance-health-hub' ),
			'card_text'    => __( 'Answer once and the survey returns a plain-language summary of what you reported, yours to keep, print or take to an appointment.', 'vance-health-hub' ),
		),
		'recipes' => array(
			'name'         => __( 'Gastro Recipes & Meal Planner', 'vance-health-hub' ),
			'short_name'   => __( 'Meal planner', 'vance-health-hub' ),
			'panel'        => 'vance_tools_panel',
			'section'      => 'vance_tools_hero_recipes_spotlight',
			'section_title'    => __( 'Recipes Hero: Spotlight', 'vance-health-hub' ),
			'style_section'    => 'vance_tools_hero_recipes',
			'classic_template' => 'page-gastro-recipies.php',
			'priority'     => 160,
			'legacy_tag'   => 'vance_tool_recipes_badge',
			'legacy_title' => 'vance_tool_recipes_name',
			'legacy_desc'  => 'vance_tool_recipes_subtitle',
			// NB these are the TEMPLATE's fallbacks, not customizer-pages.php's,
			// which still say "IBD Recipes & Meal Planner". The template's are
			// what an unsaved site actually renders — get_theme_mod() answers an
			// unsaved read with the default the CALLER passes, and the registered
			// default only seeds the control and the Customizer preview.
			'legacy_tag_default'   => 'Meal Planning',
			'legacy_title_default' => 'Gastro Recipes & Meal Planner',
			'legacy_desc_default'  => 'EPA-rich, gut-friendly recipes with full nutrition data. Browse and build a weekly plan freely, saving plans takes two clicks to create your free account.',
			'image'        => $img_hero . 'recipes.jpg',
			'image_alt'    => __( 'Hands slicing vegetables on a pale kitchen worktop laid out with salmon, spinach and lemon', 'vance-health-hub' ),
			'btn1_text'    => __( 'Browse the recipes', 'vance-health-hub' ),
			'btn1_link'    => '#recipes',
			'btn2_text'    => __( 'Build a weekly plan', 'vance-health-hub' ),
			'btn2_link'    => '#planner',
			'slot'         => 'tools',
			'slot_label'   => __( 'The other free tools', 'vance-health-hub' ),
			'card'         => 'text',
			'card_icon'    => 'bowl',
			'card_title'   => __( 'Every recipe carries its nutrition data', 'vance-health-hub' ),
			'card_text'    => __( 'So a week of meals adds up to numbers (energy, protein, EPA) you can put in front of a dietitian rather than describe.', 'vance-health-hub' ),
		),
		'malnutrition' => array(
			'name'         => __( 'Malnutrition Calculator', 'vance-health-hub' ),
			'short_name'   => __( 'Calculator', 'vance-health-hub' ),
			'panel'        => 'vance_tools_panel',
			'section'      => 'vance_tools_hero_malnutrition_spotlight',
			'section_title'    => __( 'Calculator Hero: Spotlight', 'vance-health-hub' ),
			'style_section'    => 'vance_tools_hero_malnutrition',
			'classic_template' => 'page-malnutrition-calculator.php',
			'priority'     => 160,
			'legacy_tag'   => 'vance_tool_malnutrition_badge',
			'legacy_title' => 'vance_tool_malnutrition_name',
			'legacy_desc'  => 'vance_tool_malnutrition_subtitle',
			'legacy_tag_default'   => 'IBD Screening',
			'legacy_title_default' => 'IBD Malnutrition Calculator',
			'legacy_desc_default'  => 'Clinically-grounded 11-step malnutrition risk screener for IBD patients. Combines MUST, IBD-NST, and GLIM criteria into a single, actionable score.',
			'image'        => $img_hero . 'malnutrition.jpg',
			'image_alt'    => __( 'A dietitian sitting beside an older patient, going through a printed sheet with him', 'vance-health-hub' ),
			'btn1_text'    => __( 'Start the screening', 'vance-health-hub' ),
			// inc/tool-page-shell.php puts this id on the card the tool sits in.
			'btn1_link'    => '#tool',
			'btn2_text'    => __( 'Ask the Hub AI instead', 'vance-health-hub' ),
			'btn2_link'    => '',
			'btn2_fallback_slug' => 'ask-ai',
			'btn2_fallback_path' => '/ask-ai/',
			'slot'         => 'tools',
			'slot_label'   => __( 'The other free tools', 'vance-health-hub' ),
			'card'         => 'text',
			'card_icon'    => 'calculator',
			'card_title'   => __( 'MUST, IBD-NST and GLIM, in one pass', 'vance-health-hub' ),
			'card_text'    => __( 'Three validated screening frameworks combined into a single score, so one set of answers covers all of them.', 'vance-health-hub' ),
		),

		/*
		 * ---- Ask AI, Get Started Today, the User Guide -------------------
		 *
		 * Ask AI and the User Guide both take the `tools` band. Neither is one
		 * of the three free tools, so nothing is dropped from the list and the
		 * band shows all three -- see vance_page_hero_spotlight_tools(), which
		 * only adds the "browse all" cell when it has dropped something, so
		 * every band on the site is three cells wide.
		 *
		 * The User Guide could have had a band of jump links instead, but the
		 * page already renders a sticky sub-nav immediately below the hero
		 * that does exactly that, better. Pointing at the three things the
		 * guide is teaching you to use says something the sub-nav does not.
		 *
		 * Get Started Today gets its own band: the four evidence pillars, which
		 * are the page's whole argument and already exist as settings.
		 */
		'askai' => array(
			'name'         => __( 'Ask AI', 'vance-health-hub' ),
			'short_name'   => __( 'Ask AI', 'vance-health-hub' ),
			// Registered in functions.php at customize_register priority 10;
			// this file runs at 20, so both panel and section exist by then.
			'panel'        => 'vance_content_panel',
			'section'      => 'vance_askai_hero_spotlight',
			'section_title'    => __( 'VANCE-Ai Hero: Spotlight', 'vance-health-hub' ),
			'style_section'    => 'vance_askai_settings',
			'classic_template' => 'page-ask-ai.php',
			'priority'     => 160,
			'legacy_tag'   => 'vance_askai_hero_badge',
			'legacy_title' => 'vance_askai_hero_title',
			'legacy_desc'  => 'vance_askai_hero_subtitle',
			// The TEMPLATE's fallbacks, which are what an unsaved site renders.
			// functions.php registers different ones ('Beta Feature v1.0', 'Ask
			// complex clinical questions...') that only the Customizer ever sees.
			'legacy_tag_default'   => 'Information Assistant',
			'legacy_title_default' => 'VANCE-Ai',
			'legacy_desc_default'  => 'Ask anything about IBD, clinical nutrition and gastrointestinal health. Every answer is drawn from articles published on the Vance Medical Hub, with links to the sources used.',
			'image'        => $img_hero . 'askai.jpg',
			'image_alt'    => __( 'A man on a sofa with a laptop on his knees, typing a question', 'vance-health-hub' ),
			'btn1_text'    => __( 'Ask a question', 'vance-health-hub' ),
			// The chat mount point, which is the first thing below the hero.
			'btn1_link'    => '#vance-askai-inline',
			'btn2_text'    => __( 'Browse the Knowledgebase', 'vance-health-hub' ),
			'btn2_link'    => '',
			'btn2_fallback_slug' => 'knowledgebase',
			'btn2_fallback_path' => '/knowledgebase/',
			'slot'         => 'tools',
			'slot_label'   => __( 'Or use one of the free tools', 'vance-health-hub' ),
			'card'         => 'text',
			'card_icon'    => 'chat',
			'card_title'   => __( 'Every answer shows its sources', 'vance-health-hub' ),
			'card_text'    => __( 'Answers are drawn from articles published on this Hub and linked back to them, so you can read the thing itself rather than take the summary on trust.', 'vance-health-hub' ),
		),
		'evidence' => array(
			'name'         => __( 'Get Started Today', 'vance-health-hub' ),
			'short_name'   => __( 'Get Started', 'vance-health-hub' ),
			'panel'        => 'vance_evidence_panel',
			'section'      => 'vance_evidence_hero_spotlight',
			'style_section'    => 'vance_evidence_hero',
			'classic_template' => 'page-turn-evidence-into-action.php',
			'priority'     => 160,
			'legacy_tag'   => 'vance_evidence_hero_tag',
			'legacy_title' => 'vance_evidence_hero_title',
			'legacy_desc'  => 'vance_evidence_hero_desc',
			'legacy_tag_default'   => 'Evidence to Practice',
			'legacy_title_default' => 'Turn <span class="highlight">Evidence</span> into Action',
			'legacy_desc_default'  => 'Rigorous clinical research only matters when it reaches the patient. Vance Medical translates peer-reviewed science and real-world data into practical protocols that clinicians and patients can act on.',
			// Button 1's LABEL is shared with the classic hero, unlike every
			// other page here. It has to be: an admin relabelled it "Join Now!"
			// in the Customizer, so a spotlight button carrying the code default
			// would silently rename the page's primary CTA on switching design.
			// Declaring legacy_btn1 drops btn1_text from this page's own field
			// list, the same way the stat card drops card_title.
			'legacy_btn1'         => 'vance_evidence_hero_btn1_text',
			'legacy_btn1_default' => 'Explore the Evidence Library',
			// The classic hero pins this link rather than reading a theme mod,
			// because the saved one still pointed at #pillars long after the
			// label became "Join Now!". Pinned here for the same reason.
			'btn1_link'    => '/login/?tab=signup',
			'image'        => $img_hero . 'evidence.jpg',
			'image_alt'    => __( 'Two colleagues at a table of printed research papers, one pointing out a figure', 'vance-health-hub' ),
			'btn2_text'    => __( 'See the four sources', 'vance-health-hub' ),
			'btn2_link'    => '#pillars',
			'slot'         => 'pillars',
			'slot_label'   => __( 'Everything here is anchored in one of these', 'vance-health-hub' ),
			'card'         => 'text',
			'card_icon'    => 'flask',
			'card_title'   => __( 'Graded against recognised criteria', 'vance-health-hub' ),
			'card_text'    => __( 'Every recommendation is anchored in at least one of the four evidence streams and graded against internationally-recognised quality criteria.', 'vance-health-hub' ),
		),
		'userguide' => array(
			'name'         => __( 'User Guide', 'vance-health-hub' ),
			'short_name'   => __( 'User Guide', 'vance-health-hub' ),
			'panel'        => 'vance_userguide_panel',
			'section'      => 'vance_userguide_hero_spotlight',
			'style_section'    => 'vance_userguide_hero',
			'classic_template' => 'page-user-guide.php',
			'priority'     => 160,
			'legacy_tag'   => 'vance_userguide_hero_tag',
			'legacy_title' => 'vance_userguide_hero_title',
			'legacy_desc'  => 'vance_userguide_hero_desc',
			'legacy_tag_default'   => 'User Guide',
			'legacy_title_default' => 'Get the most out of <span class="highlight">Vance Medical Hub</span>',
			'legacy_desc_default'  => 'Vance Health Hub is built to be the credible source you turn to at every step of your healthcare journey: evidence-based research, clinically-grounded tools, and a private dashboard that keeps your data, notes and AI conversations in one place. This guide shows you how it all fits together.',
			'image'        => $img_hero . 'userguide.jpg',
			'image_alt'    => __( 'A younger woman leaning in to show an older woman something on a laptop at a kitchen table', 'vance-health-hub' ),
			'btn1_text'    => __( 'Start with your journey', 'vance-health-hub' ),
			'btn1_link'    => '#your-journey',
			// The classic hero's own PDF button. It survives the switch because
			// the same download is offered twice more further down the page, but
			// keeping it above the fold costs one attribute -- see btn2_download.
			'btn2_text'    => __( 'Download the PDF guide', 'vance-health-hub' ),
			'btn2_link'    => $pdf,
			'btn2_download' => true,
			'slot'         => 'tools',
			'slot_label'   => __( 'What the guide will show you', 'vance-health-hub' ),
			'card'         => 'text',
			'card_icon'    => 'book',
			'card_title'   => __( 'Tuned to your condition, once you join', 'vance-health-hub' ),
			'card_text'    => __( 'The research, tools and AI answers you see are filtered to the condition you tell us about, so the Hub stops being a generic health website.', 'vance-health-hub' ),
		),

		/*
		 * ---- Education & Courses -----------------------------------------
		 *
		 * The one page here that is selling something that does not exist
		 * yet. Everything below follows from that.
		 *
		 * The PHOTOGRAPH was made for this page on 2026-09-01 and replaced the
		 * motif this hero first shipped with. The motif was right while there
		 * was nothing to picture, and the objection was always to a STOCK
		 * frame -- somebody smiling at a laptop, the borrowed-generic-image
		 * problem the heroes/ directory exists to end -- rather than to a
		 * photograph as such. So the brief answers that directly: it does not
		 * show a course, because there is no cohort, classroom or platform to
		 * show. It shows the act the page is asking for, which is sitting down
		 * and working through something at your own pace. Paper and a pen
		 * rather than a screen, which is also what separates this page from
		 * the Knowledgebase. tests/gen-heroes.py holds the prompt, the
		 * reasoning, and the checks the frame was inspected against.
		 *
		 * The classic hero's own background is still no help, for the usual
		 * reason: like every other one on this site it was picked to sit under
		 * a dark veil and reads as a smear on a pale band.
		 *
		 * The BAND is what the Hub teaches with today -- see
		 * vance_page_hero_spotlight_learn(). A visitor who came here for a
		 * course and finds a waitlist should not leave empty-handed, and the
		 * Knowledgebase is 149 clinically-reviewed articles that exist now.
		 *
		 * The DESCRIPTION is the one piece of new wiring. vance_edu_hero_desc
		 * has been registered, defaulted and sanitized in customizer-pages.php
		 * since the page was built, and page-education.php has never rendered
		 * it -- a control that has silently done nothing for months. The
		 * spotlight hero gives it somewhere to land. That is also why this is
		 * the only page here with legacy_desc_file: section 0b of
		 * tests/hero-render.test.php holds each default against the file that
		 * carries the classic hero's copy of it, and for this one field that
		 * file is the Customizer registration rather than the template.
		 */
		'education' => array(
			'name'         => __( 'Education & Courses', 'vance-health-hub' ),
			'short_name'   => __( 'Education', 'vance-health-hub' ),
			'panel'        => 'vance_edu_panel',
			'section'      => 'vance_edu_hero_spotlight',
			'style_section'    => 'vance_edu_hero',
			'classic_template' => 'page-education.php',
			'priority'     => 9,
			'legacy_tag'   => 'vance_edu_hero_tag',
			'legacy_title' => 'vance_edu_hero_title',
			'legacy_desc'  => 'vance_edu_hero_desc',
			// The TEMPLATE's fallbacks for the first two, which are what the
			// live page renders today -- customizer-pages.php registers a
			// different tag ('Education') and title ('Courses are Coming
			// Soon') that only the Customizer preview has ever shown.
			'legacy_tag_default'   => 'Elevate Your Expertise',
			'legacy_title_default' => 'Education &amp; Courses',
			// ...and for the third, the registration IS the only copy, because
			// the template never reads it. See legacy_desc_file below.
			'legacy_desc_default'  => 'We\'re building self-paced courses for patients and CPD-accredited modules for practitioners. Join the waitlist to be the first to know when enrolment opens.',
			'legacy_desc_file'     => 'customizer-pages.php',
			// No 'motif' key any more: it is not a fallback that sits behind
			// the photograph, it is the OTHER branch of the renderer, and
			// leaving it declared would bring the dot field back the moment an
			// admin cleared Photograph. The two motif pages left are the
			// Knowledgebase and the 404.
			'image'        => $img_hero . 'education.jpg',
			'image_alt'    => __( 'A woman at a desk by a window, pausing over an open notebook with a pen in her hand', 'vance-health-hub' ),
			// Both anchors are rendered by page-education.php unconditionally,
			// and the waitlist form below #waitlist posts to admin-ajax and
			// works today -- it is not gated on the Mailchimp endpoint being
			// configured. Verified against the live page before being pinned.
			'btn1_text'    => __( 'Join the waitlist', 'vance-health-hub' ),
			'btn1_link'    => '#waitlist',
			'btn2_text'    => __( 'See the two tracks', 'vance-health-hub' ),
			'btn2_link'    => '#tracks',
			'slot'         => 'learn',
			'slot_label'   => __( 'While the courses are in build, start here', 'vance-health-hub' ),
			'card'         => 'text',
			'card_icon'    => 'book',
			'card_title'   => __( 'Two tracks, one waitlist', 'vance-health-hub' ),
			// Deliberately does NOT say "tell us which track you want": only
			// the modal opened from a track card carries a TRACK field, and the
			// form this hero's first button scrolls to does not.
			'card_text'    => __( 'Self-paced modules for living with IBD, and CPD-accredited deep dives for clinicians. Joining once covers both: one email per track, when that track opens.', 'vance-health-hub' ),
		),

		/*
		 * ---- Patient Downloads ---------------------------------------------
		 *
		 * The handout series has no Customizer setting per-PDF — the ten titles
		 * live as a literal array in page-patient-downloads.php, same call as
		 * vance_page_hero_spotlight_start() took for the 404's four cells. The
		 * band lists the two handouts actually shipped today and drops either
		 * one whose file goes missing, same file_exists() guard the template
		 * itself uses for vpd_pdf_meta() — see vance_page_hero_spotlight_downloads().
		 *
		 * No photograph yet (tests/gen-heroes.py has not been run for this
		 * page), so 'motif' is set exactly as it is for the Knowledgebase and
		 * the 404: the dot field renders until an admin uploads one.
		 */
		'patientdownloads' => array(
			'name'         => __( 'Patient Downloads', 'vance-health-hub' ),
			'short_name'   => __( 'Downloads', 'vance-health-hub' ),
			'panel'        => 'vance_patientdownloads_panel',
			'section'      => 'vance_patientdownloads_hero_spotlight',
			'style_section'    => 'vance_patientdownloads_hero',
			'classic_template' => 'page-patient-downloads.php',
			'priority'     => 9,
			'legacy_tag'   => 'vance_patientdownloads_hero_tag',
			'legacy_title' => 'vance_patientdownloads_hero_title',
			'legacy_desc'  => 'vance_patientdownloads_hero_desc',
			// The TEMPLATE's own fallbacks, copied verbatim — same reason as
			// every other entry here: an unsaved read has to match what
			// page-patient-downloads.php already renders.
			'legacy_tag_default'   => 'Patient Downloads',
			'legacy_title_default' => 'Printable guides for your <span class="highlight">next appointment</span>',
			'legacy_desc_default'  => 'Free, evidence-backed PDF handouts you can save to your phone or print, built for the moments a screen isn\'t the easiest way to have the conversation.',
			'motif'        => true,
			'image'        => '',
			'image_alt'    => '',
			'btn1_text'    => __( 'Browse the handouts', 'vance-health-hub' ),
			// The card grid, the first thing below the hero.
			'btn1_link'    => '#downloads-grid',
			'btn2_text'    => __( 'Ask the Hub AI instead', 'vance-health-hub' ),
			'btn2_link'    => '',
			'btn2_fallback_slug' => 'ask-ai',
			'btn2_fallback_path' => '/ask-ai/',
			'slot'         => 'downloads',
			'slot_label'   => __( 'Available to download now', 'vance-health-hub' ),
			'card'         => 'text',
			'card_icon'    => 'clipboard',
			'card_title'   => __( 'Built for the moments a screen isn\'t easiest', 'vance-health-hub' ),
			'card_text'    => __( 'Every handout is evidence-backed, free, and yours to keep, no account needed to download.', 'vance-health-hub' ),
		),

		/*
		 * ---- IBD Discounts & Freebies --------------------------------------
		 *
		 * A brand-new page (docs/DISCOUNTS_TOOL_PLAN.md), so unlike every other
		 * entry above it has no live classic hero to have copied fallbacks
		 * FROM — the legacy_*_default values below are written fresh rather
		 * than lifted from an existing template, and page-ibd-discounts.php's
		 * own classic-hero branch has to match them, the same direction of
		 * dependency as every other page here, just run for the first time
		 * instead of the usual second time.
		 *
		 * No photograph yet (tests/gen-heroes.py has not been run for this
		 * page, same position Patient Downloads started from) — 'motif' true,
		 * same as Patient Downloads/Knowledgebase/404.
		 *
		 * The band is the three live counts read straight off the CPT via
		 * vance_page_hero_spotlight_discounts() above — not a literal array,
		 * because a scheme count that can silently drift from the grid below
		 * it is worse than no count at all.
		 */
		'discounts' => array(
			'name'         => __( 'IBD Discounts & Freebies', 'vance-health-hub' ),
			'short_name'   => __( 'Discounts', 'vance-health-hub' ),
			'panel'        => 'vance_discounts_panel',
			'section'      => 'vance_discounts_hero_spotlight',
			'style_section'    => 'vance_discounts_hero',
			'classic_template' => 'page-ibd-discounts.php',
			'priority'     => 9,
			'legacy_tag'   => 'vance_discounts_hero_tag',
			'legacy_title' => 'vance_discounts_hero_title',
			'legacy_desc'  => 'vance_discounts_hero_desc',
			'legacy_tag_default'   => 'Discounts & Freebies',
			'legacy_title_default' => 'Discounts and freebies for <span class="highlight">life with IBD</span>',
			'legacy_desc_default'  => 'Every UK scheme worth knowing about (toilet access, days out, travel, tax and benefits), checked against the provider\'s own page, not copied from a leaflet.',
			'motif'        => true,
			'image'        => '',
			'image_alt'    => '',
			'btn1_text'    => __( 'Browse the directory', 'vance-health-hub' ),
			'btn1_link'    => '#discounts-grid',
			'btn2_text'    => __( 'Ask the Hub AI instead', 'vance-health-hub' ),
			'btn2_link'    => '',
			'btn2_fallback_slug' => 'ask-ai',
			'btn2_fallback_path' => '/ask-ai/',
			'slot'         => 'discounts',
			'slot_label'   => __( 'The directory in three numbers', 'vance-health-hub' ),
			'card'         => 'text',
			'card_icon'    => 'clipboard',
			'card_title'   => __( 'Checked against the live provider page', 'vance-health-hub' ),
			'card_text'    => __( 'Every scheme carries the date it was last verified, so a stale entry is visible wherever it appears rather than trusted forever.', 'vance-health-hub' ),
		),

		/*
		 * ---- The two shelves, and the 404 --------------------------------
		 *
		 * Free Health Tools and the Knowledgebase are the site's two shelves:
		 * pages whose whole job is to hand a visitor on to something else. So
		 * the band carries the handing-on, not facts about the shelf itself.
		 *
		 * The 404 is the one page here with no classic hero to switch back to
		 * and no Customizer copy of its own -- see 'always' below and the copy
		 * branch in vance_page_hero_spotlight_values().
		 */
		'tools' => array(
			'name'         => __( 'Free Health Tools', 'vance-health-hub' ),
			'short_name'   => __( 'Free tools', 'vance-health-hub' ),
			'panel'        => 'vance_tools_panel',
			'section'      => 'vance_tools_hero_spotlight',
			// Two OTHER spotlight sections already live in this panel, one per
			// tool. hero-customizer.test.php fails if any two share a title.
			'section_title'    => __( 'Shelf Hero: Spotlight', 'vance-health-hub' ),
			'style_section'    => 'vance_tools_hero',
			'classic_template' => 'page-tools-resources.php',
			'priority'     => 9,
			'legacy_tag'   => 'vance_tools_hero_tag',
			'legacy_title' => 'vance_tools_hero_title',
			'legacy_desc'  => 'vance_tools_hero_desc',
			'legacy_tag_default'   => 'Free Tools',
			'legacy_title_default' => 'Tools &amp; <span class="highlight">Resources</span>',
			'legacy_desc_default'  => 'Clinical calculators built on peer-reviewed evidence, free to use, no signup required. Save your results and build a meal plan by registering for a free account.',
			'image'        => $img_hero . 'free-tools.jpg',
			'image_alt'    => __( 'A woman at a bright kitchen table looking up from a tablet, a notebook and a glass of water beside her', 'vance-health-hub' ),
			'btn1_text'    => __( 'See the three tools', 'vance-health-hub' ),
			// The grid of tool cards, the first thing below the intro.
			'btn1_link'    => '#tools-grid',
			// Button 2 is the account CTA, and its LABEL and its LINK are both
			// inherited -- the same mechanism, and the same reason, as button 1
			// on Get Started Today. An admin has relabelled this one 'Join Now!'
			// in the Customizer, so a spotlight button carrying the code default
			// would silently rename the page's only CTA the day it switched.
			'legacy_btn2'              => 'vance_tools_hero_btn2_text',
			'legacy_btn2_default'      => 'Create Free Account',
			'legacy_btn2_link'         => 'vance_tools_hero_btn2_link',
			'legacy_btn2_link_default' => '/login/?tab=signup',
			'slot'         => 'tools',
			// The band lists all three tools and no 'browse all' cell: this page
			// IS the shelf, so nothing is dropped and nothing is sold back to
			// itself. vance_page_hero_spotlight_tools() reaches that on its own,
			// because it adds the shelf cell only when it has dropped something.
			'slot_label'   => __( 'Go straight to one', 'vance-health-hub' ),
			'card'         => 'text',
			'card_icon'    => 'grid',
			'card_title'   => __( 'All three are free, with no account', 'vance-health-hub' ),
			'card_text'    => __( 'Nothing here sits behind a signup. An account only adds somewhere to keep the results and pick them up again later.', 'vance-health-hub' ),
		),
		'kblobby' => array(
			'name'         => __( 'Knowledgebase', 'vance-health-hub' ),
			'short_name'   => __( 'Knowledgebase', 'vance-health-hub' ),
			'panel'        => 'vance_kblobby_panel',
			'section'      => 'vance_kblobby_hero_spotlight',
			'style_section'    => 'vance_kblobby_hero',
			'classic_template' => 'page-knowledgebase.php',
			'priority'     => 9,
			'legacy_tag'   => 'vance_kblobby_hero_tag',
			'legacy_title' => 'vance_kblobby_hero_title',
			'legacy_desc'  => 'vance_kblobby_hero_desc',
			// The TEMPLATE's fallbacks, not customizer-pages.php's: that file
			// registers a description mentioning 'courses' which only the
			// Customizer preview ever sees.
			'legacy_tag_default'   => 'Knowledgebase',
			'legacy_title_default' => 'The whole <span class="highlight">evidence library</span>, one door',
			'legacy_desc_default'  => 'Clinical reviews, gastro living guides and health news - every collection in the Vance Medical Hub, grouped so you can go straight to the one you need.',
			// No photograph IN CODE, by the same reasoning the five policy
			// documents settled on: a library of articles has nothing to picture,
			// and a stock clinician beside 'pick a collection' says less than
			// nothing.
			//
			// The live site nonetheless shows a photograph, set as a theme mod on
			// 2026-08-31: assets/img/heroes/knowledgebase.jpg, made for this hero.
			// That is deliberately the override path rather than a new default --
			// clearing Photograph in the Customizer brings the motif straight
			// back, and the motif stays the thing a fresh install renders.
			'motif'        => true,
			'image'        => '',
			'image_alt'    => '',
			'btn1_text'    => __( 'Pick a collection', 'vance-health-hub' ),
			'btn1_link'    => '#collections',
			'btn2_text'    => __( 'Ask the Hub AI instead', 'vance-health-hub' ),
			'btn2_link'    => '',
			'btn2_fallback_slug' => 'ask-ai',
			'btn2_fallback_path' => '/ask-ai/',
			// The one page whose CLASSIC hero already carried a search field, so
			// dropping it would be a straight loss. The band is therefore the
			// search -- exactly what the homepage hero puts in the same slot,
			// markup and stylesheet reused rather than restated.
			'slot'         => 'search',
			'slot_label'   => __( 'What would you like to know?', 'vance-health-hub' ),
			'search_placeholder' => __( 'Search the whole knowledgebase...', 'vance-health-hub' ),
			'card'         => 'text',
			'card_icon'    => 'book',
			'card_title'   => __( 'Every collection is clinically reviewed', 'vance-health-hub' ),
			'card_text'    => __( 'Nothing reaches a shelf here until a clinician has read it, and every article names the evidence it rests on.', 'vance-health-hub' ),
		),
		'e404' => array(
			'name'         => __( 'Page Not Found (404)', 'vance-health-hub' ),
			'short_name'   => __( 'Not found', 'vance-health-hub' ),
			'panel'        => 'vance_e404_panel',
			'section'      => 'vance_e404_hero_spotlight',
			'priority'     => 9,
			// NO TOGGLE. Every other page here switches between two designs; the
			// 404 has never had a hero at all, so there is nothing to switch back
			// to and a control offering 'Classic' would offer a design that does
			// not exist. Same call the five policy documents took, for the same
			// reason. Consequences: no style_section and no classic_template are
			// declared, and vance_page_hero_spotlight_active('e404') is always
			// true -- so this hero ships live rather than waiting for an admin.
			'always'       => true,
			// ...and therefore no legacy_* keys either: with no classic hero
			// there is no copy to inherit. The three *_default values below are
			// used as literals -- see vance_page_hero_spotlight_values().
			'legacy_tag_default'   => __( '404 error', 'vance-health-hub' ),
			'legacy_title_default' => __( 'We can&rsquo;t find that page', 'vance-health-hub' ),
			'legacy_desc_default'  => __( 'The address may have changed, or the page may have been retired. Nothing is lost. Everything the Hub publishes is reachable from the Knowledgebase.', 'vance-health-hub' ),
			'motif'        => true,
			'image'        => '',
			'image_alt'    => '',
			// THE START PAGE. The Knowledgebase lobby is the one door on this
			// site that leads to every other: the collections, the conditions,
			// the free tools, the newest articles and a search field are all on
			// it. The homepage is the second button rather than the first
			// because it sells the Hub; the lobby indexes it.
			'btn1_text'    => __( 'Start at the Knowledgebase', 'vance-health-hub' ),
			'btn1_link'    => '',
			'btn1_fallback_slug' => 'knowledgebase',
			'btn1_fallback_path' => '/knowledgebase/',
			'btn2_text'    => __( 'Back to the homepage', 'vance-health-hub' ),
			'btn2_link'    => '/',
			// As on the Knowledgebase: the motif is the code default and the live
			// site overrides it with assets/img/heroes/not-found.jpg, an empty
			// sunlit doorway. A photograph of PEOPLE is the one thing this page
			// must not have -- a stranger smiling beside "we cannot find that
			// page" is the reason the policy pages went abstract in the first
			// place. An architectural way-through is not that.
			'slot'         => 'start',
			'slot_label'   => __( 'Or go straight to one of these', 'vance-health-hub' ),
			'card'         => 'text',
			'card_icon'    => 'chat',
			'card_title'   => __( 'Followed a link from somewhere?', 'vance-health-hub' ),
			'card_text'    => __( 'Tell us where it was and we will fix it. There is a search box at the top of every page as well.', 'vance-health-hub' ),
		),
	);

	return isset( $conf[ $page ] ) ? $conf[ $page ] : null;
}

/**
 * Every page this file can render, in Customizer registration order.
 *
 * Derived from the config rather than written out again, so adding a page is
 * one edit: the renderer, the Customizer and tests/hero-render.test.php all
 * walk this list.
 *
 * @return string[]
 */
function vance_page_hero_spotlight_pages() {
	return array( 'contact', 'about', 'hquiz', 'recipes', 'malnutrition', 'askai',
		'evidence', 'userguide', 'patientdownloads', 'discounts', 'education', 'tools', 'kblobby', 'e404' );
}

/**
 * A page's permalink, resolved by slug so it follows the page wherever it
 * lives, with the literal path as a last resort so a link is never href-less.
 *
 * @param string $slug     Page slug.
 * @param string $fallback Path to use when no page has that slug.
 * @return string
 */
function vance_page_hero_spotlight_page_url( $slug, $fallback ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( $fallback );
}

/**
 * Which hero design a page shows before anyone touches the Customizer.
 *
 * Deliberately a function rather than a literal repeated in two places. The
 * Customizer's registered `default` and the runtime fallback that
 * vance_page_hero_spotlight_active() passes to get_theme_mod() are separate
 * mechanisms — WordPress uses the first to seed the control and the second to
 * answer an unsaved read — so writing 'classic' twice lets them drift, and a
 * page would then preview one design and render the other.
 *
 * @return string 'classic' or 'spotlight'.
 */
function vance_page_hero_spotlight_default_style() {
	return 'classic';
}

/**
 * Is the spotlight design switched on for this page?
 *
 * @param string $page A key of vance_page_hero_spotlight_config().
 * @return bool
 */
function vance_page_hero_spotlight_active( $page ) {
	$c = vance_page_hero_spotlight_config( $page );
	if ( ! $c ) {
		return false;
	}

	// A page whose config declares 'always' has no classic hero to switch
	// back to, so it registers no toggle and reads none. Only the 404 is in
	// that position today; everything else stays opt-in.
	if ( ! empty( $c['always'] ) ) {
		return true;
	}

	$style = vance_get_theme_mod(
		'vance_' . $page . '_hero_style',
		vance_page_hero_spotlight_default_style()
	);
	return $style === 'spotlight';
}

/**
 * Per-field defaults for one page.
 *
 * Colour defaults are the homepage hero's, read from
 * vance_hero_spotlight_field_defaults() rather than retyped, so the three
 * heroes cannot drift apart on brand colour.
 *
 * @param string $page A key of vance_page_hero_spotlight_config().
 * @return array<string, mixed> field => default
 */
function vance_page_hero_spotlight_field_defaults( $page ) {
	$c = vance_page_hero_spotlight_config( $page );
	if ( ! $c ) { return array(); }

	// hero-spotlight.php is required before this file, but guard anyway so a
	// partial deploy degrades to the brand colours instead of fatalling.
	$home = function_exists( 'vance_hero_spotlight_field_defaults' )
		? vance_hero_spotlight_field_defaults()
		: array(
			'bg_from'         => '#ECF5F5',
			'bg_to'           => '#F6F9FA',
			'title_color'     => '#04504E',
			'intro_color'     => '#3F4B4E',
			'btn1_bg_color'   => '#6B489E',
			'btn1_text_color' => '#ffffff',
			'btn1_hover_bg'   => '#583B82',
			'card_bg_color'   => '#E5F1F1',
		);

	$d = array(
		'image'           => '',
		'image_alt'       => $c['image_alt'],
		'bg_from'         => $home['bg_from'],
		'bg_to'           => $home['bg_to'],
		'title_color'     => $home['title_color'],
		'intro_color'     => $home['intro_color'],
		// ?? '' on the two labels because a page that inherits a button from
		// its classic hero declares no literal for it -- and the entry is
		// unset again a few lines down anyway. Reading it raised a PHP notice
		// on every Get Started Today render.
		'btn1_text'       => isset( $c['btn1_text'] ) ? $c['btn1_text'] : '',
		'btn1_link'       => $c['btn1_link'],
		'btn1_bg_color'   => $home['btn1_bg_color'],
		'btn1_text_color' => $home['btn1_text_color'],
		'btn1_hover_bg'   => $home['btn1_hover_bg'],
		'btn2_text'       => isset( $c['btn2_text'] ) ? $c['btn2_text'] : '',
		'btn2_link'       => isset( $c['btn2_link'] ) ? $c['btn2_link'] : '',
		'show_slot'       => true,
		'slot_label'      => $c['slot_label'],
		'show_card'       => true,
		'card_text'       => $c['card_text'],
		'card_bg_color'   => $home['card_bg_color'],
	);

	// Only the text card carries its own heading; the stat card's "heading" is
	// stat 1, which lives in the Trust Badges & Stats section already.
	if ( $c['card'] === 'text' ) {
		$d['card_title'] = $c['card_title'];
	}

	// A page that inherits button 1's label from its classic hero gets no
	// control of its own for it, exactly as the stat card gets no heading --
	// two places to type one label is how the classic hero's link and label
	// drifted apart in the first place.
	if ( ! empty( $c['legacy_btn1'] ) ) {
		unset( $d['btn1_text'] );
	}

	// Same again for button 2, which Free Health Tools needs for BOTH halves:
	// an admin relabelled that CTA 'Join Now!' and repointed it, and neither
	// edit should be undone by a change of hero design. Declaring the pair
	// drops both controls, so there is still exactly one place to type each.
	if ( ! empty( $c['legacy_btn2'] ) ) {
		unset( $d['btn2_text'] );
	}
	if ( ! empty( $c['legacy_btn2_link'] ) ) {
		unset( $d['btn2_link'] );
	}

	// The search band is the only one with anything to type into it, so it is
	// the only one that adds a field. Every other band is filled from settings
	// the page already has.
	if ( $c['slot'] === 'search' ) {
		$d['search_placeholder'] = $c['search_placeholder'];
	}

	return $d;
}

/**
 * Resolve every field for one page, applying the "empty means auto" fallbacks.
 *
 * @param string $page A key of vance_page_hero_spotlight_config().
 * @return array<string, mixed>
 */
function vance_page_hero_spotlight_values( $page ) {
	$c = vance_page_hero_spotlight_config( $page );
	if ( ! $c ) { return array(); }

	$prefix = 'vance_' . $page . '_hero_spot_';
	$vals   = array();
	foreach ( vance_page_hero_spotlight_field_defaults( $page ) as $field => $default ) {
		$vals[ $field ] = vance_get_theme_mod( $prefix . $field, $default );
	}

	if ( ! $vals['image'] ) {
		$vals['image'] = $c['image'];
	}

	// Copy comes from the classic hero's keys — see 'legacy_*' in the config.
	// The defaults matter as much as the keys: an unsaved setting returns
	// whatever default is passed here, so passing '' would render an empty
	// hero on every site that has not edited these fields.
	//
	// A page with no legacy_tag has no classic hero and therefore no copy
	// settings to inherit — the 404, and only the 404. Its three *_default
	// values are then the copy itself, which is the same call inc/legal-hero.php
	// made for the five policy documents: nothing has become less editable
	// than it was, because there was never a control for it.
	foreach ( array( 'eyebrow' => 'tag', 'title' => 'title', 'intro' => 'desc' ) as $field => $key ) {
		$vals[ $field ] = empty( $c[ 'legacy_' . $key ] )
			? $c[ 'legacy_' . $key . '_default' ]
			: vance_get_theme_mod( $c[ 'legacy_' . $key ], $c[ 'legacy_' . $key . '_default' ] );
	}

	if ( ! empty( $c['legacy_btn1'] ) ) {
		$vals['btn1_text'] = vance_get_theme_mod( $c['legacy_btn1'], $c['legacy_btn1_default'] );
	}
	if ( ! empty( $c['legacy_btn2'] ) ) {
		$vals['btn2_text'] = vance_get_theme_mod( $c['legacy_btn2'], $c['legacy_btn2_default'] );
	}
	if ( ! empty( $c['legacy_btn2_link'] ) ) {
		$vals['btn2_link'] = vance_get_theme_mod( $c['legacy_btn2_link'], $c['legacy_btn2_link_default'] );
	}

	// Resolve either button's link by SLUG when the config names one, so the
	// CTA follows the page wherever it is moved to. For button 2 this is only
	// reached when an admin has deliberately cleared the link; button 1 uses
	// it on the 404, whose whole job is to point at a page that still exists.
	foreach ( array( 'btn1', 'btn2' ) as $b ) {
		if ( ! $vals[ $b . '_link' ] && ! empty( $c[ $b . '_fallback_slug' ] ) ) {
			$vals[ $b . '_link' ] = vance_page_hero_spotlight_page_url(
				$c[ $b . '_fallback_slug' ],
				$c[ $b . '_fallback_path' ]
			);
		}
	}

	return $vals;
}

/**
 * One inline icon from the set this hero uses.
 *
 * Same stroke weight and 24-unit box as the homepage hero's icons so the two
 * read as one family.
 *
 * @param string $name mail|phone|clock|check|chat|flask|clipboard|bowl|calculator|grid|book
 * @return string SVG markup, already safe (no dynamic values).
 */
function vance_page_hero_spotlight_icon( $name ) {
	$paths = array(
		'mail'  => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M3 7l9 6 9-6"/>',
		'phone' => '<path d="M6.5 3h3l1.5 4.5-2 1.5a13 13 0 0 0 6 6l1.5-2L21 14.5v3a2.5 2.5 0 0 1-2.7 2.5A16.5 16.5 0 0 1 3.5 5.7 2.5 2.5 0 0 1 6 3z"/>',
		'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.3l3.4 2"/>',
		'check' => '<path d="M4 12.5l5 5L20 6.5"/>',
		'chat'  => '<path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 9.2 9.2 0 0 1-3.9-.9L3 20.5l1.6-4.7A8.4 8.4 0 0 1 12 3.1a8.4 8.4 0 0 1 9 8.4z"/><path d="M8.5 11.5h.01M12 11.5h.01M15.5 11.5h.01"/>',
		'flask' => '<path d="M9.5 3v6.2L4.6 17a2.6 2.6 0 0 0 2.2 4h10.4a2.6 2.6 0 0 0 2.2-4l-4.9-7.8V3"/><path d="M8 3h8"/><path d="M7.6 14.6h8.8"/>',
		// The three free tools, one each, plus the shelf they sit on.
		'clipboard'  => '<path d="M9 4.5H7.4A2.4 2.4 0 0 0 5 6.9v11.7A2.4 2.4 0 0 0 7.4 21h9.2a2.4 2.4 0 0 0 2.4-2.4V6.9a2.4 2.4 0 0 0-2.4-2.4H15"/><rect x="9" y="2.6" width="6" height="3.8" rx="1.4"/><path d="M8.8 11.6h6.4"/><path d="M8.8 15.4h4.2"/>',
		'bowl'       => '<path d="M3.4 11.4h17.2a8.6 8.6 0 0 1-17.2 0z"/><path d="M8.2 8.4c0-1.7 1.3-2 1.3-3.4"/><path d="M12 8.4c0-1.7 1.3-2 1.3-3.4"/><path d="M15.8 8.4c0-1.7 1.3-2 1.3-3.4"/>',
		'calculator' => '<rect x="4.6" y="2.6" width="14.8" height="18.8" rx="2.4"/><rect x="8" y="6" width="8" height="3.2" rx="1"/><path d="M8.6 13h.02"/><path d="M12 13h.02"/><path d="M15.4 13h.02"/><path d="M8.6 17h.02"/><path d="M12 17h.02"/><path d="M15.4 17h.02"/>',
		'grid'       => '<rect x="3.4" y="3.4" width="7.2" height="7.2" rx="1.8"/><rect x="13.4" y="3.4" width="7.2" height="7.2" rx="1.8"/><rect x="3.4" y="13.4" width="7.2" height="7.2" rx="1.8"/><rect x="13.4" y="13.4" width="7.2" height="7.2" rx="1.8"/>',
		'book'       => '<path d="M4 4.6A1.6 1.6 0 0 1 5.6 3H11v18H5.6A1.6 1.6 0 0 1 4 19.4z"/><path d="M20 4.6A1.6 1.6 0 0 0 18.4 3H13v18h5.4a1.6 1.6 0 0 0 1.6-1.6z"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) { return ''; }

	// 'check' is drawn on a small dark tile, so it needs a heavier stroke to
	// stay legible at 15px.
	$weight = ( $name === 'check' ) ? '2.6' : '1.9';

	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $weight
		. '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
		. $paths[ $name ] . '</svg>';
}

/**
 * Turn a human-readable phone number into a dialable tel: target.
 *
 * The saved value carries spaces, brackets and dashes so it reads well —
 * "+44 (0)1628 526 005". Stripping to digits alone is NOT enough: that keeps
 * the trunk prefix inside the brackets and yields +4401628526005, which is not
 * a number and fails to connect. In UK/ITU notation a bracketed lone zero is
 * exactly the digit you drop once you have dialled the country code, so it is
 * removed first — but only when the number is in international form, since a
 * purely national "(01628) 526005" needs its leading zero kept.
 *
 * @param string $phone Whatever an admin typed.
 * @return string Digits, with at most a leading '+'.
 */
function vance_page_hero_spotlight_tel( $phone ) {
	$phone = (string) $phone;

	// Only when a country code is present. `(01628)` is left alone: its zero is
	// load-bearing, and the group holds more than a single 0.
	if ( strpos( $phone, '+' ) !== false ) {
		$phone = preg_replace( '/\(\s*0\s*\)/', '', $phone );
	}

	$digits = preg_replace( '/[^0-9+]/', '', $phone );

	// A '+' is only meaningful in the leading position.
	$lead   = ( isset( $digits[0] ) && $digits[0] === '+' ) ? '+' : '';

	return $lead . str_replace( '+', '', $digits );
}

/**
 * The three direct-contact cells for the Contact hero's utility band.
 *
 * Reads the settings the contact page already has, so nothing here is a new
 * thing for an admin to keep in sync. A cell whose setting is empty is
 * dropped rather than rendered blank.
 *
 * @return array<int, array{key: string, label: string, value: string, href: string}>
 */
function vance_page_hero_spotlight_lines() {
	// Defaults copied from page-contact-us.php, which passes these same
	// fallbacks to get_theme_mod() further down the page. Passing '' would
	// empty the band wherever an admin has never opened Contact Information.
	$email = vance_get_theme_mod( 'vance_contact_email', 'team@vancemedicalfoods.co.uk' );
	$phone = vance_get_theme_mod( 'vance_contact_phone', '+44 (0)1628 526 005' );
	$hours = vance_get_theme_mod( 'vance_contact_hours', 'Monday – Friday, 9:00 am – 5:00 pm GMT' );

	$lines = array();

	if ( $email ) {
		$lines[] = array(
			'key'   => 'mail',
			'label' => __( 'Email', 'vance-health-hub' ),
			'value' => $email,
			'href'  => 'mailto:' . $email,
		);
	}
	if ( $phone ) {
		$lines[] = array(
			'key'   => 'phone',
			'label' => __( 'Phone', 'vance-health-hub' ),
			'value' => $phone,
			'href'  => 'tel:' . vance_page_hero_spotlight_tel( $phone ),
		);
	}
	if ( $hours ) {
		$lines[] = array(
			'key'   => 'clock',
			'label' => __( 'Open', 'vance-health-hub' ),
			'value' => $hours,
			'href'  => '', // not a link
		);
	}

	return $lines;
}

/**
 * The other free tools, for a tool page's utility band.
 *
 * The Tools & Resources page presents exactly three tools under one "Free
 * Tools" heading. A visitor who arrives on one of them from search sees only
 * that one, so each tool hero's band carries the other two and a link to the
 * shelf.
 *
 * Every name and badge is read from the tool's OWN hero settings -- the same
 * keys its page renders its own H1 and badge from -- so renaming a tool in the
 * Customizer renames it here too, and there is no second copy to keep in sync.
 * The defaults are the ones the classic templates pass, for the usual reason:
 * get_theme_mod() answers an unsaved read with the caller's default, so '' here
 * would empty the band on any site that has never edited these fields.
 *
 * A tool whose name has been deliberately cleared is dropped rather than
 * rendered as an empty cell -- the band's grid sizes itself to what survives.
 *
 * @param string $page The page doing the rendering; it is never listed.
 * @return array<int, array{key: string, label: string, value: string, href: string}>
 */
function vance_page_hero_spotlight_tools( $page ) {
	$tools = array(
		'hquiz' => array(
			'icon'     => 'clipboard',
			'slug'     => 'gastro-health-survey',
			'path'     => '/gastro-health-survey/',
			'name_key' => 'vance_hquiz_hero_title',
			'name_def' => 'Gastro Health Survey',
			'tag_key'  => 'vance_hquiz_hero_badge',
			'tag_def'  => 'Self-Assessment',
		),
		'recipes' => array(
			'icon'     => 'bowl',
			'slug'     => 'gastro-meal-planner',
			'path'     => '/gastro-meal-planner/',
			'name_key' => 'vance_tool_recipes_name',
			'name_def' => 'Gastro Recipes & Meal Planner',
			'tag_key'  => 'vance_tool_recipes_badge',
			'tag_def'  => 'Meal Planning',
		),
		'malnutrition' => array(
			'icon'     => 'calculator',
			'slug'     => 'malnutrition-calculator',
			'path'     => '/malnutrition-calculator/',
			'name_key' => 'vance_tool_malnutrition_name',
			'name_def' => 'IBD Malnutrition Calculator',
			'tag_key'  => 'vance_tool_malnutrition_badge',
			'tag_def'  => 'IBD Screening',
		),
	);

	$cells = array();

	foreach ( $tools as $key => $t ) {
		if ( $key === $page ) {
			continue; // never sell a page back to itself
		}
		$name = vance_get_theme_mod( $t['name_key'], $t['name_def'] );
		if ( $name === '' ) {
			continue;
		}
		$cells[] = array(
			'key'   => $t['icon'],
			'label' => vance_get_theme_mod( $t['tag_key'], $t['tag_def'] ),
			'value' => $name,
			'href'  => vance_page_hero_spotlight_page_url( $t['slug'], $t['path'] ),
		);
	}

	// The shelf, for anyone who wants none of the ones listed -- but only when
	// a cell is actually missing. On a tool page one tool is always dropped (its
	// own), so this lands and the band is three wide. On Ask AI and the User
	// Guide, which are not tools, all three are listed and adding a fourth cell
	// would squeeze them all. Either way the band is exactly three columns, and
	// it stays three if an admin clears a tool's name.
	if ( count( $cells ) < count( $tools ) ) {
		$cells[] = array(
			'key'   => 'grid',
			'label' => __( 'More', 'vance-health-hub' ),
			'value' => __( 'Browse all free tools', 'vance-health-hub' ),
			// The shelf's slug is free-health-tools, NOT tools-resources — that is
			// only what page-tools-resources.php's docblock suggests, and
			// /tools-resources/ 404s on the live site. Nobody had seen this cell,
			// because no tool page had the spotlight hero switched on yet.
			'href'  => vance_page_hero_spotlight_page_url( 'free-health-tools', '/free-health-tools/' ),
		);
	}

	return $cells;
}

/**
 * The four places worth starting from, for the 404's utility band.
 *
 * The only band on the site written as literals rather than read from
 * settings, for the reason given at 'always' in the config: the 404 has no
 * Customizer copy of its own, so reading three of these four from theme mods
 * and typing the fourth would be worse than typing all four — an admin would
 * have no way of telling which cells they could change.
 *
 * The Knowledgebase is deliberately NOT here. It is button 1, the start page,
 * and a cell repeating it would spend a quarter of the band on a link that is
 * already the largest thing on the screen.
 *
 * @return array<int, array{key: string, label: string, value: string, href: string}>
 */
function vance_page_hero_spotlight_start() {
	$cells = array(
		array( 'grid', __( 'Free tools', 'vance-health-hub' ),
			__( 'Free Health Tools', 'vance-health-hub' ), 'free-health-tools', '/free-health-tools/' ),
		array( 'chat', __( 'Ask a question', 'vance-health-hub' ),
			__( 'VANCE-Ai', 'vance-health-hub' ), 'ask-ai', '/ask-ai/' ),
		array( 'clipboard', __( 'Self-assessment', 'vance-health-hub' ),
			__( 'Gastro Health Survey', 'vance-health-hub' ), 'gastro-health-survey', '/gastro-health-survey/' ),
		array( 'mail', __( 'Still stuck', 'vance-health-hub' ),
			__( 'Contact us', 'vance-health-hub' ), 'contact-us', '/contact-us/' ),
	);

	$out = array();
	foreach ( $cells as $c ) {
		$out[] = array(
			'key'   => $c[0],
			'label' => $c[1],
			'value' => $c[2],
			// By slug, so a renamed page keeps its cell rather than sending a
			// visitor who is already lost to a second 404.
			'href'  => vance_page_hero_spotlight_page_url( $c[3], $c[4] ),
		);
	}

	return $out;
}

/**
 * What the Hub teaches with today, for Education & Courses' utility band.
 *
 * The courses are a waitlist. Somebody who searched for a gastro course and
 * landed on "coming soon" has, at that moment, nothing to do — so the band
 * carries the three things they came for that already exist: the article
 * library, the assistant that answers from it, and the free tools.
 *
 * Literals rather than theme mods, for the reason vance_page_hero_spotlight_start()
 * gives: these are page NAMES, and the one setting that holds a page name here
 * — Ask AI's H1 — is a headline an admin may reword at any time without
 * meaning to rename the page. vance_page_hero_spotlight_tools() reads its
 * names from settings because there the setting IS the tool's name, kept in
 * one place across four heroes; nothing like that is true of these three.
 *
 * Every href resolves by slug so a renamed page keeps its cell.
 *
 * @return array<int, array{key: string, label: string, value: string, href: string}>
 */
function vance_page_hero_spotlight_learn() {
	$cells = array(
		// First, and deliberately: 149 clinically-reviewed articles is the
		// education that exists on this site today. The other two are ways of
		// using it.
		array( 'book', __( 'Read up', 'vance-health-hub' ),
			__( 'Knowledgebase', 'vance-health-hub' ), 'knowledgebase', '/knowledgebase/' ),
		array( 'chat', __( 'Ask a question', 'vance-health-hub' ),
			__( 'VANCE-Ai', 'vance-health-hub' ), 'ask-ai', '/ask-ai/' ),
		array( 'grid', __( 'Use them free', 'vance-health-hub' ),
			__( 'Free Health Tools', 'vance-health-hub' ), 'free-health-tools', '/free-health-tools/' ),
	);

	$out = array();
	foreach ( $cells as $c ) {
		$out[] = array(
			'key'   => $c[0],
			'label' => $c[1],
			'value' => $c[2],
			'href'  => vance_page_hero_spotlight_page_url( $c[3], $c[4] ),
		);
	}

	return $out;
}

/**
 * The handouts actually shipped today, for Patient Downloads' utility band.
 *
 * Literal, not read from Customizer settings — same call
 * vance_page_hero_spotlight_start() took for the 404, for the same reason:
 * page-patient-downloads.php's ten handouts are a literal array with no
 * per-title setting of their own. Only 'file' + 'pages' being set means a
 * handout has actually shipped; the file_exists() guard mirrors the one the
 * template already runs in vpd_pdf_meta(), so a handout pulled from the server
 * drops out of the band instead of linking to a 404.
 *
 * A third cell always points back at the grid itself, the same "shelf" cell
 * vance_page_hero_spotlight_tools() adds when something is left off the band.
 *
 * @return array<int, array{key: string, label: string, value: string, href: string}>
 */
function vance_page_hero_spotlight_downloads() {
	$handouts = array(
		array(
			'icon'  => 'clipboard',
			'tag'   => 'Appointment Prep',
			'title' => 'Preparing for Your Doctor Appointment',
			'file'  => 'Vance-Health-Hub-Appointment-Preparation.pdf',
		),
		array(
			'icon'  => 'book',
			'tag'   => 'Travel',
			'title' => 'Your IBD Travel Checklist',
			'file'  => 'Vance-Health-Hub-IBD-Travel-Checklist.pdf',
		),
	);

	$cells = array();
	foreach ( $handouts as $h ) {
		if ( ! file_exists( get_template_directory() . '/assets/downloads/' . $h['file'] ) ) {
			continue;
		}
		$cells[] = array(
			'key'   => $h['icon'],
			'label' => $h['tag'],
			'value' => $h['title'],
			'href'  => get_template_directory_uri() . '/assets/downloads/' . $h['file'],
		);
	}

	$cells[] = array(
		'key'   => 'grid',
		'label' => __( 'More', 'vance-health-hub' ),
		'value' => __( 'See all the handouts', 'vance-health-hub' ),
		'href'  => '#downloads-grid',
	);

	return $cells;
}

/**
 * Live scheme counts for IBD Discounts & Freebies' utility band. Reads
 * inc/discount-data.php's vance_discount_counts() rather than a second
 * literal array — the plan's explicit instruction for this slot — so the
 * band can never disagree with the directory grid it sits above.
 *
 * Guarded by function_exists() the same way vance_page_hero_spotlight_field_defaults()
 * guards its call into hero-spotlight.php: this file loads before
 * discount-data.php in functions.php's require order, so at parse time the
 * function doesn't exist yet, only by the time a page actually renders.
 *
 * @return array<int, array{key:string,label:string,value:string,href:string}>
 */
function vance_page_hero_spotlight_discounts() {
	if ( ! function_exists( 'vance_discount_counts' ) ) {
		return array();
	}
	$counts = vance_discount_counts();

	return array(
		array(
			'key'   => 'clipboard',
			'label' => __( 'Schemes', 'vance-health-hub' ),
			'value' => (string) $counts['total'],
			'href'  => '#discounts-grid',
		),
		array(
			'key'   => 'check',
			'label' => __( 'Free to apply', 'vance-health-hub' ),
			'value' => (string) $counts['free'],
			'href'  => '#discounts-grid',
		),
		array(
			'key'   => 'grid',
			'label' => __( 'Apply on the hub', 'vance-health-hub' ),
			'value' => (string) $counts['tier1'],
			'href'  => '#discounts-grid',
		),
	);
}

/**
 * The geometric motif that stands in for the photograph.
 *
 * Used by the pages whose config sets 'motif' and whose photograph setting is
 * empty — the Knowledgebase lobby and the 404. Both are pages about a body of
 * material or a state of the site rather than about people, and a stock
 * clinician beside either says less than nothing. Uploading an image in the
 * Customizer takes over, so this is a default and not a ceiling.
 *
 * inc/legal-hero.php carries its own copy of the same motif on purpose: it is
 * loaded only by the policy templates, is covered by its own suite and its own
 * mutation runner, and a policy page should not stop rendering because a page
 * hero was refactored. If the two are ever merged, merge the CSS with them —
 * that file declares `.vhh-hero-spotlight__motif` inline as well.
 *
 * @return string SVG markup. Static — no dynamic values, nothing to escape.
 */
function vance_page_hero_spotlight_motif() {
	// A 7x4 dot field, built rather than typed out so the spacing is one
	// number instead of twenty-eight coordinates to keep in step.
	$dots = '';
	for ( $row = 0; $row < 4; $row++ ) {
		for ( $col = 0; $col < 7; $col++ ) {
			$dots .= sprintf(
				'<circle cx="%d" cy="%d" r="2.1"/>',
				392 + ( $col * 30 ),
				322 + ( $row * 30 )
			);
		}
	}

	// Gradient ids are prefixed because a page may carry other inline SVG, and
	// an id collision repaints whichever one lost. They differ from the legal
	// hero's for the same reason, even though the two never render together.
	return '<svg viewBox="0 0 640 520" preserveAspectRatio="xMaxYMid slice" aria-hidden="true" focusable="false">'
		. '<defs>'
		. '<radialGradient id="vhhPageBloom" cx="70%" cy="26%" r="64%">'
		. '<stop offset="0%" stop-color="#AFD6D4" stop-opacity="0.60"/>'
		. '<stop offset="52%" stop-color="#CBE4E2" stop-opacity="0.26"/>'
		. '<stop offset="100%" stop-color="#CBE4E2" stop-opacity="0"/>'
		. '</radialGradient>'
		. '<linearGradient id="vhhPageArc" x1="0" y1="1" x2="1" y2="0">'
		. '<stop offset="0%" stop-color="#04504E" stop-opacity="0.03"/>'
		. '<stop offset="48%" stop-color="#04504E" stop-opacity="0.30"/>'
		. '<stop offset="100%" stop-color="#04504E" stop-opacity="0.07"/>'
		. '</linearGradient>'
		. '</defs>'
		. '<rect width="640" height="520" fill="url(#vhhPageBloom)"/>'
		. '<g fill="none" stroke="url(#vhhPageArc)">'
		. '<circle cx="486" cy="150" r="118" stroke-width="1.5"/>'
		. '<circle cx="486" cy="150" r="188" stroke-width="1.2"/>'
		. '<circle cx="486" cy="150" r="262" stroke-width="1"/>'
		. '<circle cx="486" cy="150" r="342" stroke-width="0.9"/>'
		. '</g>'
		. '<g fill="#04504E" opacity="0.13" stroke="none">' . $dots . '</g>'
		. '</svg>';
}

/**
 * The four evidence pillars, for Get Started Today's utility band.
 *
 * The page's own argument is "Four Sources. One Standard.", and the four are
 * already settings -- the pillar cards further down the page render the same
 * titles. Reading them here means an admin renames a pillar once.
 *
 * Defaults are page-turn-evidence-into-action.php's own $pillar_defaults,
 * copied verbatim for the usual reason: '' would empty the band on any site
 * that has never edited them.
 *
 * @return string[] Pillar titles, empty ones dropped.
 */
function vance_page_hero_spotlight_pillars() {
	$defaults = array(
		1 => 'Clinical Trials',
		2 => 'Real-World Data',
		3 => 'Peer-Reviewed Science',
		4 => 'Expert Consensus',
	);

	$out = array();
	foreach ( $defaults as $i => $default ) {
		$title = vance_get_theme_mod( 'vance_evidence_pillar' . $i . '_title', $default );
		if ( $title !== '' ) {
			$out[] = $title;
		}
	}

	return $out;
}

/**
 * Render the spotlight hero for one page.
 *
 * Markup order is media-then-container for the same reason as the homepage
 * hero: on desktop the media is absolutely positioned so source order is
 * irrelevant, and below the stacking breakpoint it drops back into flow —
 * where being first puts the photograph above the headline, which is the
 * intended mobile order. No `order` property, no duplicated markup.
 *
 * The section carries the homepage hero's own class so it inherits the band,
 * the dissolve, the type scale, the CTAs and the card from one stylesheet
 * block — including the doubled-class !important rules that opt the hero out
 * of the global mobile type normalisation. The modifier only adds what these
 * two pages need on top.
 *
 * @param string $page A key of vance_page_hero_spotlight_config().
 * @return void
 */
function vance_render_page_hero_spotlight( $page ) {
	$c = vance_page_hero_spotlight_config( $page );
	if ( ! $c ) { return; }

	$s = vance_page_hero_spotlight_values( $page );

	$fade_from = vance_hex_to_rgb_triple( $s['bg_from'], '236, 245, 245' );
	$fade_to   = vance_hex_to_rgb_triple( $s['bg_to'], '246, 249, 250' );

	// The title accepts a highlight span, matching both other heroes.
	$title = wp_kses_post( $s['title'] );

	$style = sprintf(
		'--vhh-hs-from: %1$s; --vhh-hs-to: %2$s; --vhh-hs-from-rgb: %3$s; --vhh-hs-to-rgb: %4$s; --vhh-hs-title: %5$s; --vhh-hs-intro: %6$s; --vhh-hs-cta-bg: %7$s; --vhh-hs-cta-fg: %8$s; --vhh-hs-cta-hover: %9$s; --vhh-hs-card-bg: %10$s;',
		esc_attr( $s['bg_from'] ),
		esc_attr( $s['bg_to'] ),
		esc_attr( $fade_from ),
		esc_attr( $fade_to ),
		esc_attr( $s['title_color'] ),
		esc_attr( $s['intro_color'] ),
		esc_attr( $s['btn1_bg_color'] ),
		esc_attr( $s['btn1_text_color'] ),
		esc_attr( $s['btn1_hover_bg'] ),
		esc_attr( $s['card_bg_color'] )
	);

	switch ( $c['slot'] ) {
		case 'lines':
			$slot_items = vance_page_hero_spotlight_lines();
			break;
		case 'tools':
			$slot_items = vance_page_hero_spotlight_tools( $page );
			break;
		case 'pillars':
			$slot_items = vance_page_hero_spotlight_pillars();
			break;
		case 'start':
			$slot_items = vance_page_hero_spotlight_start();
			break;
		case 'learn':
			$slot_items = vance_page_hero_spotlight_learn();
			break;
		case 'downloads':
			$slot_items = vance_page_hero_spotlight_downloads();
			break;
		case 'discounts':
			$slot_items = vance_page_hero_spotlight_discounts();
			break;
		case 'search':
			// Not a list of cells at all — the band is a form. Non-empty so the
			// shared `$show_slot` test below reads it as present.
			$slot_items = array( 'search' );
			break;
		default:
			// Defaults copied from page-about.php's own $badge_defaults, for the
			// same reason as the hero copy above: '' here empties the band on any
			// site that has never edited the badges.
			$slot_items = array_values( array_filter( array(
				vance_get_theme_mod( 'vance_about_badge1_label', 'Rigorously Developed' ),
				vance_get_theme_mod( 'vance_about_badge2_label', 'Citation-Backed Content' ),
				vance_get_theme_mod( 'vance_about_badge3_label', 'Evidence-Based' ),
			) ) );
	}

	// The About badges have their own long-standing visibility switch; honour
	// it here rather than making an admin turn the same row off twice.
	// Two shapes of band, not five. 'lines' is an icon tile beside a caption
	// and a value, optionally a link; 'badges' is a tick beside a phrase.
	// Every slot is one of those two with a modifier on top, which is what
	// keeps the cell treatment, the dividers and the responsive stack in a
	// single place.
	if ( $c['slot'] === 'search' ) {
		$slot_markup = 'search';
	} elseif ( in_array( $c['slot'], array( 'badges', 'pillars' ), true ) ) {
		$slot_markup = 'badges';
	} else {
		$slot_markup = 'lines';
	}
	$slot_class  = 'vhh-hero-spotlight__slot--' . $slot_markup;
	if ( $c['slot'] !== $slot_markup ) {
		$slot_class .= ' vhh-hero-spotlight__slot--' . $c['slot'];
	}

	$show_slot = ! empty( $s['show_slot'] ) && $slot_items;
	if ( $c['slot'] === 'badges' && ! vance_get_theme_mod( 'vance_about_badges_show', true ) ) {
		$show_slot = false;
	}

	// Whether THIS render is drawing the motif rather than a photograph, which
	// the phone stylesheet has to know: a photograph drops back into flow below
	// 900px and provides the hero's top spacing itself, a motif is absolutely
	// positioned at every width and never drops, so a motif hero that keeps the
	// shared zeroed padding jams its headline against the site header.
	//
	// Emitted as a class rather than left to a hand-kept list of page
	// modifiers. That list said kblobby and e404, and adding Education as a
	// third motif page did exactly what you would expect -- it rendered flush
	// under the header on a phone, because nobody remembers a selector list in
	// another file. This is a property of the render, so the render states it.
	//
	// Note it is `--has-motif`, not `--motif`: `__motif` is already the element
	// class on the div below, and two classes one character apart would be
	// read wrong eventually.
	$has_motif = ( $s['image'] === '' && ! empty( $c['motif'] ) );
	?>
	<section class="vhh-hero-spotlight vhh-hero-spotlight--page vhh-hero-spotlight--<?php echo esc_attr( $page ); ?><?php echo $has_motif ? ' vhh-hero-spotlight--has-motif' : ''; ?>" style="<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput — each part escaped above ?>">

		<?php if ( $has_motif ) : ?>
		<?php /* No photograph, and the config says none is wanted — see
		         vance_page_hero_spotlight_motif(). An admin who uploads one in
		         the Customizer gets the photograph branch instead, with no code
		         change: that is the whole point of the empty default. */ ?>
		<div class="vhh-hero-spotlight__motif" aria-hidden="true"><?php
			echo vance_page_hero_spotlight_motif(); // phpcs:ignore WordPress.Security.EscapeOutput — static markup
		?></div>
		<?php else : ?>
		<div class="vhh-hero-spotlight__media">
			<?php /* Above the fold and the page's LCP candidate — eager, high
			         priority, and with intrinsic dimensions so it reserves its
			         box and cannot shift the headline as it decodes. */ ?>
			<img src="<?php echo esc_url( $s['image'] ); ?>"
			     alt="<?php echo esc_attr( $s['image_alt'] ); ?>"
			     width="1400" height="876"
			     decoding="async" fetchpriority="high">
		</div>
		<?php endif; ?>

		<div class="container vhh-hero-spotlight__inner">
			<div class="vhh-hero-spotlight__copy">

				<?php if ( $s['eyebrow'] !== '' ) : ?>
				<span class="vhh-hero-spotlight__eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></span>
				<?php endif; ?>

				<h1 class="vhh-hero-spotlight__title"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput — wp_kses_post above ?></h1>

				<?php if ( $s['intro'] !== '' ) : ?>
				<p class="vhh-hero-spotlight__intro"><?php echo esc_html( $s['intro'] ); ?></p>
				<?php endif; ?>

				<?php if ( $s['btn1_text'] !== '' || $s['btn2_text'] !== '' ) : ?>
				<div class="vhh-hero-spotlight__actions">
					<?php if ( $s['btn1_text'] !== '' ) : ?>
					<a class="vhh-hero-spotlight__cta" href="<?php echo esc_url( $s['btn1_link'] ); ?>">
						<span><?php echo esc_html( $s['btn1_text'] ); ?></span>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h13"/><path d="M13 6l6 6-6 6"/></svg>
					</a>
					<?php endif; ?>
					<?php if ( $s['btn2_text'] !== '' ) : ?>
					<?php /* `download` only where the config says the target is a file: on
					         a normal link it is meaningless, and on a cross-origin one the
					         browser ignores it anyway. */ ?>
					<a class="vhh-hero-spotlight__cta vhh-hero-spotlight__cta--ghost" href="<?php echo esc_url( $s['btn2_link'] ); ?>"<?php echo ! empty( $c['btn2_download'] ) ? ' download' : ''; ?>><?php echo esc_html( $s['btn2_text'] ); ?></a>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<?php if ( $show_slot ) : ?>
				<div class="vhh-hero-spotlight__slot-wrap">
					<?php if ( $s['slot_label'] !== '' ) : ?>
					<?php /* A real <label for> when the band below is a search field, a
					         plain <span> when it is a row of cells there is nothing to
					         label. Same class either way, so the type comes from the one
					         rule in main.css that also sets the homepage's. */ ?>
					<?php if ( $slot_markup === 'search' ) : ?>
					<label class="vhh-hero-spotlight__slot-label" for="vhh-page-hero-search"><?php echo esc_html( $s['slot_label'] ); ?></label>
					<?php else : ?>
					<span class="vhh-hero-spotlight__slot-label"><?php echo esc_html( $s['slot_label'] ); ?></span>
					<?php endif; ?>
					<?php endif; ?>

					<?php if ( $slot_markup === 'search' ) : ?>
					<?php /* The homepage hero's own search markup and classes, so the
					         white card, the field, the button and the focus ring all come
					         from the one block in main.css. The id differs from the
					         homepage's because the label's `for` has to point at exactly
					         one field, and nothing stops a future page carrying both. */ ?>
					<form role="search" method="get" class="vhh-hero-spotlight__search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<input type="search"
						       id="vhh-page-hero-search"
						       class="vhh-hero-spotlight__search-field"
						       name="s"
						       value="<?php echo esc_attr( get_search_query() ); ?>"
						       placeholder="<?php echo esc_attr( $s['search_placeholder'] ); ?>"
						       autocomplete="off">
						<button type="submit" class="vhh-hero-spotlight__search-submit" aria-label="<?php esc_attr_e( 'Search the Hub', 'vance-health-hub' ); ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/></svg>
						</button>
					</form>
					<?php elseif ( $slot_markup === 'lines' ) : ?>
					<div class="vhh-hero-spotlight__slot <?php echo esc_attr( $slot_class ); ?>">
						<?php foreach ( $slot_items as $line ) :
							$tag = $line['href'] ? 'a' : 'div';
							?>
							<<?php echo $tag; ?> class="vhh-hero-spotlight__line"<?php if ( $line['href'] ) : ?> href="<?php echo esc_url( $line['href'] ); ?>"<?php endif; ?>>
								<span class="vhh-hero-spotlight__line-ico"><?php echo vance_page_hero_spotlight_icon( $line['key'] ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup ?></span>
								<span class="vhh-hero-spotlight__line-body">
									<span class="vhh-hero-spotlight__line-k"><?php echo esc_html( $line['label'] ); ?></span>
									<span class="vhh-hero-spotlight__line-v"><?php echo esc_html( $line['value'] ); ?></span>
								</span>
							</<?php echo $tag; ?>>
						<?php endforeach; ?>
					</div>
					<?php else : ?>
					<ul class="vhh-hero-spotlight__slot <?php echo esc_attr( $slot_class ); ?>">
						<?php foreach ( $slot_items as $badge ) : ?>
						<li class="vhh-hero-spotlight__badge">
							<span class="vhh-hero-spotlight__badge-ico"><?php echo vance_page_hero_spotlight_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup ?></span>
							<span class="vhh-hero-spotlight__badge-t"><?php echo esc_html( $badge ); ?></span>
						</li>
						<?php endforeach; ?>
					</ul>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $s['show_card'] ) ) : ?>
				<?php if ( $c['card'] === 'stat' ) :
					$stat_num   = vance_get_theme_mod( 'vance_about_stat1_num', '30+' );
					$stat_label = vance_get_theme_mod( 'vance_about_stat1_label', 'Years of Pharmaceutical Experience' );
					if ( $stat_num !== '' || $s['card_text'] !== '' ) : ?>
				<aside class="vhh-hero-spotlight__card vhh-hero-spotlight__card--stat">
					<span class="vhh-hero-spotlight__card-icon" aria-hidden="true"><?php echo vance_page_hero_spotlight_icon( $c['card_icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup ?></span>
					<?php if ( $stat_num !== '' ) : ?>
					<p class="vhh-hero-spotlight__stat-fig">
						<span class="vhh-hero-spotlight__stat-num"><?php echo esc_html( $stat_num ); ?></span>
						<?php if ( $stat_label !== '' ) : ?>
						<span class="vhh-hero-spotlight__stat-lab"><?php echo esc_html( $stat_label ); ?></span>
						<?php endif; ?>
					</p>
					<span class="vhh-hero-spotlight__stat-rule" aria-hidden="true"></span>
					<?php endif; ?>
					<?php if ( $s['card_text'] !== '' ) : ?>
					<p class="vhh-hero-spotlight__card-text"><?php echo esc_html( $s['card_text'] ); ?></p>
					<?php endif; ?>
				</aside>
				<?php endif; ?>

				<?php else :
					$card_title = isset( $s['card_title'] ) ? $s['card_title'] : '';
					if ( $card_title !== '' || $s['card_text'] !== '' ) : ?>
				<aside class="vhh-hero-spotlight__card">
					<span class="vhh-hero-spotlight__card-icon" aria-hidden="true"><?php echo vance_page_hero_spotlight_icon( $c['card_icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup ?></span>
					<div class="vhh-hero-spotlight__card-body">
						<?php if ( $card_title !== '' ) : ?>
						<h2 class="vhh-hero-spotlight__card-title"><?php echo esc_html( $card_title ); ?></h2>
						<?php endif; ?>
						<?php if ( $s['card_text'] !== '' ) : ?>
						<p class="vhh-hero-spotlight__card-text"><?php echo esc_html( $s['card_text'] ); ?></p>
						<?php endif; ?>
					</div>
				</aside>
				<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Register the toggle and every spotlight field for both pages.
 *
 * Called from customizer-pages.php, inside the same customize_register hook
 * that builds the Contact and About panels — the sections below hang off
 * those panels, so they have to be registered after them.
 *
 * @param WP_Customize_Manager $wp_customize
 * @return void
 */
function vance_page_hero_spotlight_customize( $wp_customize ) {

	$shared_fields = array(
		'image'           => array( 'type' => 'image',    'label' => 'Photograph', 'description' => 'Separate from the classic hero\'s background image, which was picked to sit under a dark veil. This one is dissolved into a pale background, so it wants to be light and uncluttered down its left-hand side, roughly 1400&times;875.' ),
		'image_alt'       => array( 'type' => 'text',     'label' => 'Photograph, alt text' ),
		'bg_from'         => array( 'type' => 'color',    'label' => 'Background, Top' ),
		'bg_to'           => array( 'type' => 'color',    'label' => 'Background, Bottom', 'description' => 'The photograph is dissolved into these two colours, so changing them keeps its edges seamless.' ),
		'title_color'     => array( 'type' => 'color',    'label' => 'Headline Colour' ),
		'intro_color'     => array( 'type' => 'color',    'label' => 'Body Text Colour' ),
		'btn1_text'       => array( 'type' => 'text',     'label' => 'Button 1, Text' ),
		'btn1_link'       => array( 'type' => 'text',     'label' => 'Button 1, Link' ),
		'btn1_bg_color'   => array( 'type' => 'color',    'label' => 'Button 1, Background' ),
		'btn1_text_color' => array( 'type' => 'color',    'label' => 'Button 1, Text Colour' ),
		'btn1_hover_bg'   => array( 'type' => 'color',    'label' => 'Button 1, Background on Hover' ),
		'btn2_text'       => array( 'type' => 'text',     'label' => 'Button 2, Text' ),
		'btn2_link'       => array( 'type' => 'text',     'label' => 'Button 2, Link' ),
		'show_slot'       => array( 'type' => 'checkbox', 'label' => 'Show the white band' ),
		'slot_label'      => array( 'type' => 'text',     'label' => 'White band, Prompt' ),
		'search_placeholder' => array( 'type' => 'text',  'label' => 'White band, Search field placeholder' ),
		'show_card'       => array( 'type' => 'checkbox', 'label' => 'Show the floating card' ),
		'card_title'      => array( 'type' => 'text',     'label' => 'Card, Heading' ),
		'card_text'       => array( 'type' => 'textarea', 'label' => 'Card, Body' ),
		'card_bg_color'   => array( 'type' => 'color',    'label' => 'Card, Background' ),
	);

	// Per-page wording for the two controls that need it. The three tool pages
	// share theirs verbatim: they are the same design filled from the same kind
	// of setting, and three near-identical paragraphs would drift.
	$tool_note = array(
		'toggle'     => 'Spotlight is the light hero: mint band, dissolving photograph, two buttons, and the other two free tools in a white band. Classic is the dark hero configured by the rest of this section.',
		'section'    => 'The light hero for this page. Only rendered while this page&rsquo;s hero design is set to Spotlight. The badge, title and subtitle are shared with the classic hero &mdash; edit them where you always have, and they follow whichever design is switched on.',
		'slot_label' => 'Sits above the white band. The band itself lists the OTHER two free tools, taking each one&rsquo;s name and badge from that tool&rsquo;s own hero settings, and ends with a link to Tools &amp; Resources. Nothing to type here beyond the prompt.',
	);

	$notes = array(
		'contact' => array(
			'toggle'     => 'Spotlight is the light hero: mint band, dissolving photograph, two buttons and your email, phone and opening hours in a white band. Classic is the dark navy hero configured by the rest of this panel.',
			'section'    => 'The light hero for this page. Only rendered while "Contact hero design" (in the Hero Section) is set to Spotlight. The eyebrow, headline and description are shared with the classic hero, edit them in the Hero Section, and they follow whichever design is switched on.',
			'slot_label' => 'Sits above the white band. The band itself is filled from Email Address, Phone Number and Office Hours in the Contact Information section.',
		),
		'about' => array(
			'toggle'     => 'Spotlight is the light hero: mint band, dissolving photograph, two buttons and your trust badges in a white band. Classic is the dark navy hero configured by the rest of this panel.',
			'section'    => 'The light hero for this page. Only rendered while "About hero design" (in the Hero Section) is set to Spotlight. The eyebrow, headline and description are shared with the classic hero, edit them in the Hero Section, and they follow whichever design is switched on.',
			'slot_label' => 'Sits above the white band. The band itself is filled from Badge 1–3 in the Trust Badges &amp; Stats section, and the card shows Stat 1 from that same section.',
		),
		'hquiz'        => $tool_note,
		'recipes'      => $tool_note,
		'malnutrition' => $tool_note,
		// Ask AI, Get Started Today and the User Guide shipped on 2026-08-28
		// with no entry here at all, so all three read an undefined index and
		// registered their controls with a null description. Their bands are
		// the tools band, so they take the tool wording for the last line.
		'askai'     => array(
			'toggle'     => 'Spotlight is the light hero: mint band, motif or photograph, two buttons, and the three free tools in a white band. Classic is the dark hero configured by the rest of this section.',
			'section'    => 'The light hero for this page. Only rendered while this page&rsquo;s hero design is set to Spotlight. The badge, title and subtitle are shared with the classic hero &mdash; edit them where you always have, and they follow whichever design is switched on.',
			'slot_label' => 'Sits above the white band. The band itself lists the three free tools, taking each one&rsquo;s name and badge from that tool&rsquo;s own hero settings. Nothing to type here beyond the prompt.',
		),
		'evidence'  => array(
			'toggle'     => 'Spotlight is the light hero: mint band, dissolving photograph, two buttons and the four evidence pillars in a white band. Classic is the dark navy hero configured by the rest of this panel.',
			'section'    => 'The light hero for this page. Only rendered while &ldquo;Get Started hero design&rdquo; (in the Hero Section) is set to Spotlight. The eyebrow, headline, description and the first button&rsquo;s label are all shared with the classic hero &mdash; edit them there, and they follow whichever design is switched on.',
			'slot_label' => 'Sits above the white band. The band itself is filled from Pillar 1&ndash;4 in this panel, so renaming a pillar renames it here too.',
		),
		'userguide' => array(
			'toggle'     => 'Spotlight is the light hero: mint band, dissolving photograph, two buttons, and the three free tools in a white band. The PDF download stays as the second button. Classic is the dark navy hero configured by the rest of this panel.',
			'section'    => 'The light hero for this page. Only rendered while &ldquo;User Guide hero design&rdquo; (in the Hero Section) is set to Spotlight. The eyebrow, headline and description are shared with the classic hero &mdash; edit them there, and they follow whichever design is switched on.',
			'slot_label' => 'Sits above the white band. The band itself lists the three free tools, taking each one&rsquo;s name and badge from that tool&rsquo;s own hero settings.',
		),
		'patientdownloads' => array(
			'toggle'     => 'Spotlight is the light hero: mint band, motif or photograph, two buttons, and the shipped handouts in a white band. Classic is the dark hero configured by the rest of this section.',
			'section'    => 'The light hero for this page. Only rendered while this page&rsquo;s hero design is set to Spotlight. The tag, title and description are shared with the classic hero &mdash; edit them where you always have, and they follow whichever design is switched on. Leave Photograph empty to keep the teal motif; upload one and it takes over.',
			'slot_label' => 'Sits above the white band. The band itself lists the handouts that have actually shipped (read from the template, not a setting) and ends with a link back to the card grid. Nothing to type here beyond the prompt.',
		),
		'discounts' => array(
			'toggle'     => 'Spotlight is the light hero: mint band, geometric motif or photograph, two buttons, and the directory\'s three live counts in a white band. Classic is the dark navy hero configured by the rest of this section.',
			'section'    => 'The light hero for this page. Only rendered while &ldquo;Discounts hero design&rdquo; (in the Hero Section) is set to Spotlight. The tag, title and description are shared with the classic hero &mdash; edit them there, and they follow whichever design is switched on. Leave Photograph empty to keep the teal motif.',
			'slot_label' => 'Sits above the white band. The band itself is the live scheme count, free-to-apply count and tier-1 (apply-on-the-hub) count, read straight from the published schemes &mdash; nothing to type here beyond the prompt.',
		),
		'tools'     => array(
			'toggle'     => 'Spotlight is the light hero: mint band, dissolving photograph, two buttons, and the three tools listed in a white band. Classic is the dark navy hero configured by the rest of this section.',
			'section'    => 'The light hero for this page. Only rendered while &ldquo;Free tools hero design&rdquo; (in the Hero Section) is set to Spotlight. The tag, title, description AND the account button&rsquo;s label and link are all shared with the classic hero &mdash; edit them in the Hero Section, and they follow whichever design is switched on.',
			'slot_label' => 'Sits above the white band. The band itself lists the three free tools, taking each one&rsquo;s name and badge from that tool&rsquo;s own hero settings. Nothing to type here beyond the prompt.',
		),
		'education' => array(
			'toggle'     => 'Spotlight is the light hero: mint band, dissolving photograph, two buttons, and the Knowledgebase, VANCE-Ai and the free tools in a white band. Classic is the dark navy hero configured by the rest of this section.',
			'section'    => 'The light hero for this page. Only rendered while &ldquo;Education hero design&rdquo; (in the Hero Section) is set to Spotlight. The tag, title and description are shared with the classic hero &mdash; edit them in the Hero Section, and they follow whichever design is switched on. Note that Description has no effect on the classic hero, which renders only the tag and the title. The supplied photograph was shot for this hero; a replacement wants the same shape and a bright, uncluttered left-hand side, because that edge is dissolved into the band.',
			'slot_label' => 'Sits above the white band. The band itself points at the Knowledgebase, VANCE-Ai and Free Health Tools &mdash; the things a visitor can use today while the courses are still in build &mdash; all resolved by page slug. Nothing to type here beyond the prompt.',
		),
		'kblobby'   => array(
			'toggle'     => 'Spotlight is the light hero: mint band, geometric motif, two buttons and the knowledgebase search field in a white band. Classic is the dark navy hero configured by the rest of this section.',
			'section'    => 'The light hero for this page. Only rendered while &ldquo;Knowledgebase hero design&rdquo; (in the Hero Section) is set to Spotlight. The tag, title and description are shared with the classic hero &mdash; edit them in the Hero Section, and they follow whichever design is switched on. Leave Photograph empty to keep the teal motif; upload one and it takes over.',
			'slot_label' => 'The label above the search field. The field itself searches the whole site, exactly as the dark hero&rsquo;s did.',
		),
		'e404'      => array(
			// There is no toggle on this page, so the first line is never shown.
			// It is here because every other page has one and $notes is read by
			// key; an entry short of a key is what broke the last three.
			'toggle'     => '',
			'section'    => 'The hero on the &ldquo;page not found&rdquo; screen. It has no classic alternative and no toggle &mdash; this is the only design the 404 has. Its wording is set in inc/page-hero-spotlight.php rather than here, because the 404 has never had Customizer copy; colours, buttons, the card and the photograph are all editable below. Leave Photograph empty to keep the teal motif.',
			'slot_label' => 'Sits above the white band. The band itself lists Free Health Tools, VANCE-Ai, the Gastro Health Survey and Contact us, all resolved by page slug so a renamed page keeps its link.',
		),
	);

	foreach ( vance_page_hero_spotlight_pages() as $page ) {
		$c = vance_page_hero_spotlight_config( $page );
		if ( ! $c ) { continue; }

		$defaults = vance_page_hero_spotlight_field_defaults( $page );

		// -- The toggle, in the page's existing Hero Section so it sits with
		//    the classic hero it switches away from. A page whose config says
		//    'always' has no classic hero, so it gets no control: offering
		//    'Classic' there would offer a design that does not exist, and
		//    vance_page_hero_spotlight_active() never reads the mod anyway.
		if ( empty( $c['always'] ) ) :
		$style_id = 'vance_' . $page . '_hero_style';
		$wp_customize->add_setting( $style_id, array(
			'default'           => vance_page_hero_spotlight_default_style(),
			'sanitize_callback' => function ( $v ) {
				return in_array( $v, array( 'classic', 'spotlight' ), true )
					? $v
					: vance_page_hero_spotlight_default_style();
			},
		) );
		$wp_customize->add_control( $style_id, array(
			'label'       => sprintf( __( '%s hero design', 'vance-health-hub' ), $c['short_name'] ),
			'description' => $notes[ $page ]['toggle'],
			// NOT 'vance_' . $page . '_hero': the tool pages keep their hero
			// controls under the Tools panel, in sections named after the tool.
			'section'     => $c['style_section'],
			'type'        => 'select',
			'priority'    => 1,
			'choices'     => array(
				'classic'   => __( 'Classic: dark navy hero', 'vance-health-hub' ),
				'spotlight' => __( 'Spotlight: light, action-led', 'vance-health-hub' ),
			),
		) );
		endif;

		// -- The spotlight's own section.
		// Three of these sit in the same panel as each other, so two of them
		// carry a title that says which tool they belong to.
		$wp_customize->add_section( $c['section'], array(
			'title'       => isset( $c['section_title'] ) ? $c['section_title'] : __( 'Hero: Spotlight', 'vance-health-hub' ),
			'description' => $notes[ $page ]['section'],
			'priority'    => $c['priority'],
			'panel'       => $c['panel'],
		) );

		foreach ( $shared_fields as $field => $meta ) {
			// The stat card has no heading of its own — it shows Stat 1.
			if ( ! array_key_exists( $field, $defaults ) ) { continue; }

			$id = 'vance_' . $page . '_hero_spot_' . $field;

			switch ( $meta['type'] ) {
				case 'color':
					$sanitize = 'sanitize_hex_color';
					break;
				case 'image':
					$sanitize = 'esc_url_raw';
					break;
				case 'checkbox':
					$sanitize = 'vance_sanitize_checkbox';
					break;
				case 'textarea':
					$sanitize = 'sanitize_textarea_field';
					break;
				default:
					$sanitize = 'sanitize_text_field';
			}

			$wp_customize->add_setting( $id, array(
				'default'           => $defaults[ $field ],
				'sanitize_callback' => $sanitize,
			) );

			$args = array(
				'label'   => $meta['label'],
				'section' => $c['section'],
			);
			if ( isset( $meta['description'] ) ) { $args['description'] = $meta['description']; }
			if ( $field === 'slot_label' ) { $args['description'] = $notes[ $page ]['slot_label']; }

			if ( $meta['type'] === 'color' ) {
				$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, $args ) );
			} elseif ( $meta['type'] === 'image' ) {
				$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $id, $args ) );
			} else {
				$args['type'] = $meta['type'];
				$wp_customize->add_control( $id, $args );
			}
		}
	}
}
