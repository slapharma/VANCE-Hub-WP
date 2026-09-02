<?php
// Included in functions.php
function vance_pages_customize_register( $wp_customize ) {
    // ---- HCP PAGE PANEL ----
    $wp_customize->add_panel( "vance_hcp_panel", array(
        "title"    => __( "Page - For Practitioners (HCP)", "vance-health-hub" ),
        "priority" => 43,
    ) );

    // HCP Hero
    $wp_customize->add_section( "vance_hcp_hero", array( "title" => "Hero Section", "panel" => "vance_hcp_panel" ) );
    $wp_customize->add_setting( "vance_hcp_hero_tag", array( "default" => "Professional Portal", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_hcp_hero_tag", array( "label" => "Tag Label", "section" => "vance_hcp_hero", "type" => "text" ) );
    
    $wp_customize->add_setting( "vance_hcp_hero_title", array( "default" => "Advancing <span class=\"highlight\">Clinical Practice</span> Through Nutrition", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_hcp_hero_title", array( "label" => "Title", "section" => "vance_hcp_hero", "type" => "textarea" ) );
    
    $wp_customize->add_setting( "vance_hcp_hero_desc", array( "default" => "Evidence-based resources, clinical protocols, and CME opportunities designed for gastroenterologists, dietitians, GPs, and allied health professionals.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_hcp_hero_desc", array( "label" => "Description", "section" => "vance_hcp_hero", "type" => "textarea" ) );
    
    $wp_customize->add_setting("vance_hcp_hero_bg", array("default"=>"","sanitize_callback"=>"esc_url_raw"));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, "vance_hcp_hero_bg", array("label"=>"Hero Background Image","section"=>"vance_hcp_hero")));

    // HCP Resources (replacing previous)
    $wp_customize->add_section( "vance_hcp_resources", array( "title" => "Resources Section", "panel" => "vance_hcp_panel" ) );
    $wp_customize->add_setting( "vance_hcp_res_tag", array( "default" => "Join the Effort", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_hcp_res_tag", array( "label" => "Tag Label", "section" => "vance_hcp_resources", "type" => "text" ) );

    // Tag-section box outline colours (same controls as hero tag-label)
    $wp_customize->add_setting( "vance_hcp_res_tag_bg",     array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_hcp_res_tag_bg",     array( "label" => "Tag Background Colour", "section" => "vance_hcp_resources" ) ) );
    $wp_customize->add_setting( "vance_hcp_res_tag_color",  array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_hcp_res_tag_color",  array( "label" => "Tag Font Colour",       "section" => "vance_hcp_resources" ) ) );
    $wp_customize->add_setting( "vance_hcp_res_tag_border", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_hcp_res_tag_border", array( "label" => "Tag Border Colour",     "section" => "vance_hcp_resources" ) ) );
    
    $wp_customize->add_setting( "vance_hcp_res_title", array( "default" => "What You'll Access", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_hcp_res_title", array( "label" => "Title", "section" => "vance_hcp_resources", "type" => "text" ) );
    
    $wp_customize->add_setting( "vance_hcp_res_desc", array( "default" => "We invite passionate healthcare practitioners to join us in advancing clinical nutrition. Share your expertise and help shape the future of specialized healthcare content.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_hcp_res_desc", array( "label" => "Description", "section" => "vance_hcp_resources", "type" => "textarea" ) );

    $res_defaults = array(
        1 => array("Clinical Protocols", "Step-by-step treatment algorithms for common and complex GI conditions, including FSMP integration."),
        2 => array("Research Summaries", "Curated abstracts and commentary on the latest Omega-3, gut microbiome, and longevity research."),
        3 => array("Webinars & CME", "On-demand educational sessions with CPD accreditation from leading gastroenterology experts."),
        4 => array("Patient Handouts", "Downloadable, branded resources to share with patients to reinforce dietary and treatment advice.")
    );
    for($i=1; $i<=4; $i++) {
        $wp_customize->add_setting("vance_hcp_res{$i}_title", array("default"=>$res_defaults[$i][0],"sanitize_callback"=>"sanitize_text_field"));
        $wp_customize->add_control("vance_hcp_res{$i}_title", array("label"=>"Card $i Title", "section"=>"vance_hcp_resources", "type"=>"text"));
        $wp_customize->add_setting("vance_hcp_res{$i}_desc", array("default"=>$res_defaults[$i][1],"sanitize_callback"=>"sanitize_textarea_field"));
        $wp_customize->add_control("vance_hcp_res{$i}_desc", array("label"=>"Card $i Description", "section"=>"vance_hcp_resources", "type"=>"textarea"));
    }

    // HCP Collaborate
    $wp_customize->add_section( "vance_hcp_collab", array( "title" => "Collaborate Section", "panel" => "vance_hcp_panel" ) );
    $wp_customize->add_setting( "vance_hcp_collab_title", array( "default" => "Collaborate with SLA Pharma", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_hcp_collab_title", array( "label" => "Title", "section" => "vance_hcp_collab", "type" => "text" ) );
    
    $collab_defaults = array(
        1 => array("Submit Articles", "Publish your clinical insights and case studies to our global network of peers."),
        2 => array("Co-Author Content", "Partner with our medical writing team to develop robust, evidence-based clinical guides."),
        3 => array("Podcast Guest", "Join our clinical podcast series to discuss innovations, challenges, and success stories."),
        4 => array("Clinical Trials", "Work with us on our pipeline of clinical and in-market trials investigating novel specific treatments.")
    );
    for($i=1; $i<=4; $i++) {
        $wp_customize->add_setting("vance_hcp_col{$i}_title", array("default"=>$collab_defaults[$i][0],"sanitize_callback"=>"sanitize_text_field"));
        $wp_customize->add_control("vance_hcp_col{$i}_title", array("label"=>"Card $i Title", "section"=>"vance_hcp_collab", "type"=>"text"));
        $wp_customize->add_setting("vance_hcp_col{$i}_desc", array("default"=>$collab_defaults[$i][1],"sanitize_callback"=>"sanitize_textarea_field"));
        $wp_customize->add_control("vance_hcp_col{$i}_desc", array("label"=>"Card $i Description", "section"=>"vance_hcp_collab", "type"=>"textarea"));
    }
    
    // HCP CTA
    $wp_customize->add_section( "vance_hcp_cta", array( "title" => "CTA Section", "panel" => "vance_hcp_panel" ) );
    $wp_customize->add_setting( "vance_hcp_cta_title", array( "default" => "Join the Professional Network", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_hcp_cta_title", array( "label" => "Title", "section" => "vance_hcp_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_hcp_cta_desc", array( "default" => "Free registration gives you full access to protocols, research, and CME opportunities.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_hcp_cta_desc", array( "label" => "Description", "section" => "vance_hcp_cta", "type" => "textarea" ) );


    // ---- PATIENT PAGE PANEL ----
    $wp_customize->add_panel( "vance_pat_panel", array(
        "title"    => __( "Page - For Patients", "vance-health-hub" ),
        "priority" => 42,
    ) );

    // Patient Hero
    $wp_customize->add_section( "vance_pat_hero", array( "title" => "Hero Section", "panel" => "vance_pat_panel" ) );
    $wp_customize->add_setting( "vance_pat_hero_tag", array( "default" => "Patient Portal", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_pat_hero_tag", array( "label" => "Tag Label", "section" => "vance_pat_hero", "type" => "text" ) );
    
    $wp_customize->add_setting( "vance_pat_hero_title", array( "default" => "Empowering Your <span class=\"highlight\">Wellness Journey</span>", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_pat_hero_title", array( "label" => "Title", "section" => "vance_pat_hero", "type" => "textarea" ) );
    
    $wp_customize->add_setting( "vance_pat_hero_desc", array( "default" => "More than just a news site, a truly useful platform providing the highest quality clinical information, innovative tools, and expert opinions to help you explore and manage your gastro healthcare concerns.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_pat_hero_desc", array( "label" => "Description", "section" => "vance_pat_hero", "type" => "textarea" ) );
    
    $wp_customize->add_setting("vance_pat_hero_bg", array("default"=>"","sanitize_callback"=>"esc_url_raw"));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, "vance_pat_hero_bg", array("label"=>"Hero Background Image","section"=>"vance_pat_hero")));

    // Patient Benefits
    $wp_customize->add_section( "vance_pat_benefits", array( "title" => "Benefits Section", "panel" => "vance_pat_panel" ) );
    $wp_customize->add_setting( "vance_pat_ben_tag", array( "default" => "Why Choose Vance Medical?", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_pat_ben_tag", array( "label" => "Tag Label", "section" => "vance_pat_benefits", "type" => "text" ) );

    // Tag-section box outline colours (same controls as hero tag-label)
    $wp_customize->add_setting( "vance_pat_ben_tag_bg",     array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_pat_ben_tag_bg",     array( "label" => "Tag Background Colour", "section" => "vance_pat_benefits" ) ) );
    $wp_customize->add_setting( "vance_pat_ben_tag_color",  array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_pat_ben_tag_color",  array( "label" => "Tag Font Colour",       "section" => "vance_pat_benefits" ) ) );
    $wp_customize->add_setting( "vance_pat_ben_tag_border", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_pat_ben_tag_border", array( "label" => "Tag Border Colour",     "section" => "vance_pat_benefits" ) ) );
    
    $wp_customize->add_setting( "vance_pat_ben_title", array( "default" => "Not Just Another Community", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_pat_ben_title", array( "label" => "Title", "section" => "vance_pat_benefits", "type" => "text" ) );
    
    $wp_customize->add_setting( "vance_pat_ben_desc", array( "default" => "Vance Medical is a comprehensive suite of resources designed to aid your personal health journey. We bridge the gap between complex medical research and practical, daily wellness by providing clinical information in a format that is easy to understand.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_pat_ben_desc", array( "label" => "Description", "section" => "vance_pat_benefits", "type" => "textarea" ) );

    $ben_defaults = array(
        1 => array("Clear Clinical Info", "Access cutting-edge clinical information translated into a clear, easy-to-understand format tailored for patients, without the medical jargon."),
        2 => array("Renowned Expertise", "Engage with exclusive content, insights, and guidance produced directly by Vance Medical specialists and world-renowned gastro healthcare experts."),
        3 => array("Actionable Solutions", "Take control with highly interactive calculators, health trackers, and personalized AI to bring the clinic directly into your home life.")
    );
    for($i=1; $i<=3; $i++) {
        $wp_customize->add_setting("vance_pat_ben{$i}_title", array("default"=>$ben_defaults[$i][0],"sanitize_callback"=>"sanitize_text_field"));
        $wp_customize->add_control("vance_pat_ben{$i}_title", array("label"=>"Benefit $i Title", "section"=>"vance_pat_benefits", "type"=>"text"));
        $wp_customize->add_setting("vance_pat_ben{$i}_desc", array("default"=>$ben_defaults[$i][1],"sanitize_callback"=>"sanitize_textarea_field"));
        $wp_customize->add_control("vance_pat_ben{$i}_desc", array("label"=>"Benefit $i Description", "section"=>"vance_pat_benefits", "type"=>"textarea"));
    }

    // Patient Tools
    $wp_customize->add_section( "vance_pat_tools", array( "title" => "Tools Section", "panel" => "vance_pat_panel" ) );
    $wp_customize->add_setting( "vance_pat_tool_title", array( "default" => "Innovative Tools at Your Fingertips", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_pat_tool_title", array( "label" => "Title", "section" => "vance_pat_tools", "type" => "text" ) );
    
    $tool_defaults = array(
        1 => array("Ask VANCE-Ai", "Interact with our AI intelligence trained specifically in clinical gastro conditions for instant, reliable answers to your health questions."),
        2 => array("Bookmark & Clip", "Easily save important articles, clip vital paragraphs, and create your own customized research notes directly in your portal."),
        3 => array("History & AI Tracking", "Upload your medical history documents to allow Vance-i to securely analyze data, track your ongoing wellness, and spot trends."),
        4 => array("Healthcare Calculators", "Evaluate potential malnutrition, calculate BMI, and score related healthcare symptoms to stay on top of your physical needs."),
        5 => array("Exclusive Courses", "Enroll in customized, multi-chapter curriculums developed by gastro specialists focusing on diet, recovery, and lifestyle routines."),
        6 => array("Downloadable Guides", "Save and export patient-focused literature, daily checklists, and clear instructions for managing clinical nutrition products.")
    );
    for($i=1; $i<=6; $i++) {
        $wp_customize->add_setting("vance_pat_tool{$i}_title", array("default"=>$tool_defaults[$i][0],"sanitize_callback"=>"sanitize_text_field"));
        $wp_customize->add_control("vance_pat_tool{$i}_title", array("label"=>"Tool $i Title", "section"=>"vance_pat_tools", "type"=>"text"));
        $wp_customize->add_setting("vance_pat_tool{$i}_desc", array("default"=>$tool_defaults[$i][1],"sanitize_callback"=>"sanitize_textarea_field"));
        $wp_customize->add_control("vance_pat_tool{$i}_desc", array("label"=>"Tool $i Description", "section"=>"vance_pat_tools", "type"=>"textarea"));
    }
    
    // Patient CTA
    $wp_customize->add_section( "vance_pat_cta", array( "title" => "CTA Section", "panel" => "vance_pat_panel" ) );
    $wp_customize->add_setting( "vance_pat_cta_title", array( "default" => "Begin Your Journey", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_pat_cta_title", array( "label" => "Title", "section" => "vance_pat_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_pat_cta_desc", array( "default" => "Join thousands of patients taking control of their gut health and longevity. It's completely free to start using our clinical resources today.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_pat_cta_desc", array( "label" => "Description", "section" => "vance_pat_cta", "type" => "textarea" ) );


    // ---- ABOUT US PAGE PANEL ----
    $wp_customize->add_panel( "vance_about_panel", array(
        "title"    => __( "Page - About Us", "vance-health-hub" ),
        "priority" => 40,
    ) );

    // ── Hero ──────────────────────────────────────────────────
    $wp_customize->add_section( "vance_about_hero", array( "title" => "Hero Section", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_hero_tag",   array( "default" => "About Vance Medical Hub", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_hero_tag",   array( "label" => "Tag Label", "section" => "vance_about_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_hero_title", array( "default" => "Trusted by Patients. <span class=\"highlight\">Driven by Science.</span>", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_about_hero_title", array( "label" => "Title (HTML allowed)", "section" => "vance_about_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_about_hero_sub",   array( "default" => "A Natural Evolution in Gastrointestinal Care", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_hero_sub",   array( "label" => "Sub-title (unused by the current layout)", "section" => "vance_about_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_hero_desc",  array( "default" => "We bridge pharmaceutical expertise with nutritional science to empower patients living with gastrointestinal conditions, delivering evidence-based care you can trust.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_about_hero_desc",  array( "label" => "Description", "section" => "vance_about_hero", "type" => "textarea" ) );
    // NB: `vance_about_hero_overlay` is registered further down by the shared
    // $hero_overlay_pages loop ("vance_about_hero" . "_overlay") — don't add it
    // here as well, or the later registration silently replaces this one.
    $wp_customize->add_setting( "vance_about_hero_img",    array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_about_hero_img", array( "label" => "Hero Background Image", "section" => "vance_about_hero" ) ) );
    // Styles for Hero Section
    $wp_customize->add_setting( "vance_about_hero_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_about_hero_show", array( "label" => "Show Section", "section" => "vance_about_hero", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_about_hero_bg_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_hero_bg_color", array( "label" => "Hero Background Colour (overrides image)", "section" => "vance_about_hero" ) ) );
    $wp_customize->add_setting( "vance_about_hero_tag_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_hero_tag_bg", array( "label" => "Tag Label Background Colour", "section" => "vance_about_hero" ) ) );
    $wp_customize->add_setting( "vance_about_hero_tag_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_hero_tag_color", array( "label" => "Tag Label Font Colour", "section" => "vance_about_hero" ) ) );
    $wp_customize->add_setting( "vance_about_hero_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_hero_title_color", array( "label" => "Title Colour", "section" => "vance_about_hero" ) ) );
    $wp_customize->add_setting( "vance_about_hero_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_hero_title_size", array( "label" => "Title Font Size (e.g. 48px)", "section" => "vance_about_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_hero_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_hero_text_color", array( "label" => "Description Text Colour", "section" => "vance_about_hero" ) ) );
    $wp_customize->add_setting( "vance_about_hero_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_hero_text_size", array( "label" => "Description Font Size (e.g. 20px)", "section" => "vance_about_hero", "type" => "text" ) );


    // ── Trust Badges + Stats ──────────────────────────────────
    // Badges now render as glass pills inside the About hero rather than a separate strip.
    $wp_customize->add_section( "vance_about_trust", array( "title" => "Trust Badges & Stats", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_badges_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_about_badges_show", array( "label" => "Show Trust Badges (in hero)", "section" => "vance_about_trust", "type" => "checkbox" ) );
    $badge_defaults = array( 1 => "Rigorously Developed", 2 => "Citation-Backed Content", 3 => "Evidence-Based" );
    for ( $i = 1; $i <= 3; $i++ ) {
        $wp_customize->add_setting( "vance_about_badge{$i}_label", array( "default" => $badge_defaults[$i], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_about_badge{$i}_label", array( "label" => "Badge $i Label", "section" => "vance_about_trust", "type" => "text" ) );
    }
    $wp_customize->add_setting( "vance_about_stats_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_about_stats_show", array( "label" => "Show Stats Row", "section" => "vance_about_trust", "type" => "checkbox" ) );
    $trust_stat_defaults = array(
        1 => array( "30+",      "Years of Pharmaceutical Experience" ),
        2 => array( "12+",      "Countries with Regulatory Approval" ),
        3 => array( "100%",     "Regulatory Standards Compliance" ),
        4 => array( "10,000+",  "Patients Supported Globally" ),
    );
    for ( $i = 1; $i <= 4; $i++ ) {
        $wp_customize->add_setting( "vance_about_stat{$i}_num",   array( "default" => $trust_stat_defaults[$i][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_about_stat{$i}_num",   array( "label" => "Stat $i Number", "section" => "vance_about_trust", "type" => "text" ) );
        $wp_customize->add_setting( "vance_about_stat{$i}_label", array( "default" => $trust_stat_defaults[$i][1], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_about_stat{$i}_label", array( "label" => "Stat $i Label", "section" => "vance_about_trust", "type" => "text" ) );
    }

    // ── Origin / Pillars (The Vance Evolution) ────────────────
    $wp_customize->add_section( "vance_about_origin", array( "title" => "The Vance Evolution", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_origin_tag",   array( "default" => "The Vance Evolution", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_origin_tag",   array( "label" => "Section Tag", "section" => "vance_about_origin", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_origin_title", array( "default" => "A Natural Progression in Gastrointestinal Care", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_origin_title", array( "label" => "Heading", "section" => "vance_about_origin", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_origin_sub",   array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_origin_sub",   array( "label" => "Sub-heading", "section" => "vance_about_origin", "type" => "text" ) );

    $pillar_defaults = array(
        1 => array( "Pharma Heritage",             "Decades spent developing specialised gastrointestinal medicines to rigorous regulatory standards, building deep expertise in the conditions that affect patients most." ),
        2 => array( "Innovation Focus",             "That experience revealed a consistent gap: medicines alone often fall short. There is a clear need for evidence-based nutritional support alongside standard medical intervention." ),
        3 => array( "Patient-Centred Solutions",    "Vance Medical was founded to bridge that gap, combining pharmaceutical rigour with nutritional science to deliver medical food products and education to both patients and practitioners." ),
    );
    for ( $i = 1; $i <= 3; $i++ ) {
        $wp_customize->add_setting( "vance_about_p{$i}_title", array( "default" => $pillar_defaults[$i][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_about_p{$i}_title", array( "label" => "Pillar $i Title", "section" => "vance_about_origin", "type" => "text" ) );
        $wp_customize->add_setting( "vance_about_p{$i}_desc",  array( "default" => $pillar_defaults[$i][1], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( "vance_about_p{$i}_desc",  array( "label" => "Pillar $i Description", "section" => "vance_about_origin", "type" => "textarea" ) );
    // Styles for Origin Section
    $wp_customize->add_setting( "vance_about_origin_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_about_origin_show", array( "label" => "Show Section", "section" => "vance_about_origin", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_about_origin_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_origin_bg", array( "label" => "Background Colour", "section" => "vance_about_origin" ) ) );
    $wp_customize->add_setting( "vance_about_origin_tag_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_origin_tag_bg", array( "label" => "Tag Label Background Colour", "section" => "vance_about_origin" ) ) );
    $wp_customize->add_setting( "vance_about_origin_tag_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_origin_tag_color", array( "label" => "Tag Label Font Colour", "section" => "vance_about_origin" ) ) );
    $wp_customize->add_setting( "vance_about_origin_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_origin_title_color", array( "label" => "Title Colour", "section" => "vance_about_origin" ) ) );
    $wp_customize->add_setting( "vance_about_origin_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_origin_title_size", array( "label" => "Title Font Size (e.g. 40px)", "section" => "vance_about_origin", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_origin_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_origin_text_color", array( "label" => "Text Colour", "section" => "vance_about_origin" ) ) );
    $wp_customize->add_setting( "vance_about_origin_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_origin_text_size", array( "label" => "Text Font Size (e.g. 18px)", "section" => "vance_about_origin", "type" => "text" ) );

    }

    // ── Mission & Values ──────────────────────────────────────
    $wp_customize->add_section( "vance_about_mission", array( "title" => "Mission & Values", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_mission_tag",   array( "default" => "Our Mission", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_mission_tag",   array( "label" => "Section Tag", "section" => "vance_about_mission", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_mission_title", array( "default" => "Bridging Science & <span class=\"highlight\">Patient Wellbeing</span>", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_about_mission_title", array( "label" => "Heading (HTML allowed)", "section" => "vance_about_mission", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_about_mission_desc",  array( "default" => "At Vance Medical, our mission is to empower patients living with chronic gastrointestinal conditions by making world-class clinical nutrition science accessible, actionable, and personal.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_about_mission_desc",  array( "label" => "Description", "section" => "vance_about_mission", "type" => "textarea" ) );

    $val_defaults = array(
        1 => array( "Evidence-Based",  "Every product and piece of content meets the highest scientific and regulatory standards, rooted in cited clinical research." ),
        2 => array( "Patient-First",   "We design every solution around real-world challenges patients face, not just clinical endpoints, because lived experience matters." ),
        3 => array( "Rigorously Developed",    "Our medical food products are developed with the same rigour applied to licensed medicines, a quality benchmark no ordinary supplement can match." ),
        4 => array( "Global Reach",    "With a regulatory footprint spanning multiple continents, Vance Medical delivers consistent, trusted solutions wherever patients and clinicians need them." ),
    );
    for ( $i = 1; $i <= 4; $i++ ) {
        $wp_customize->add_setting( "vance_about_val{$i}_title", array( "default" => $val_defaults[$i][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_about_val{$i}_title", array( "label" => "Value $i Title", "section" => "vance_about_mission", "type" => "text" ) );
        $wp_customize->add_setting( "vance_about_val{$i}_desc",  array( "default" => $val_defaults[$i][1], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( "vance_about_val{$i}_desc",  array( "label" => "Value $i Description", "section" => "vance_about_mission", "type" => "textarea" ) );
    // Styles for Mission & Values
    $wp_customize->add_setting( "vance_about_mission_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_about_mission_show", array( "label" => "Show Section", "section" => "vance_about_mission", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_about_mission_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_mission_bg", array( "label" => "Background Colour", "section" => "vance_about_mission" ) ) );
    $wp_customize->add_setting( "vance_about_mission_tag_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_mission_tag_bg", array( "label" => "Tag Label Background Colour", "section" => "vance_about_mission" ) ) );
    $wp_customize->add_setting( "vance_about_mission_tag_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_mission_tag_color", array( "label" => "Tag Label Font Colour", "section" => "vance_about_mission" ) ) );
    $wp_customize->add_setting( "vance_about_mission_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_mission_title_color", array( "label" => "Title Colour", "section" => "vance_about_mission" ) ) );
    $wp_customize->add_setting( "vance_about_mission_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_mission_title_size", array( "label" => "Title Font Size (e.g. 40px)", "section" => "vance_about_mission", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_mission_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_mission_text_color", array( "label" => "Text Colour", "section" => "vance_about_mission" ) ) );
    $wp_customize->add_setting( "vance_about_mission_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_mission_text_size", array( "label" => "Text Font Size (e.g. 18px)", "section" => "vance_about_mission", "type" => "text" ) );

    }

    // ── Why Patients Trust Us (was EPAVANCE Product Spotlight) ─
    $wp_customize->add_section( "vance_about_product", array( "title" => "Why Patients Trust Us", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_prod_tag",       array( "default" => "Why Patients Trust Us", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_prod_tag",       array( "label" => "Section Tag", "section" => "vance_about_product", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_prod_title",     array( "default" => "Built on Decades of Clinical Excellence", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_prod_title",     array( "label" => "Title", "section" => "vance_about_product", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_prod_desc",      array( "default" => "", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_about_prod_desc",      array( "label" => "Description", "section" => "vance_about_product", "type" => "textarea" ) );

    $feat_defaults = array(
        1 => array( "Nutrition-First Approach", "Our team of gastroenterologists, dietitians, and pharmaceutical scientists develop solutions that fit naturally into your daily life." ),
        2 => array( "Community & Support",      "Join a vibrant community of patients and practitioners sharing experiences, knowledge, and encouragement on the path to better gut health." ),
        3 => array( "Digital Innovation",       "Our AI-powered tools and digital health platform put clinical-grade information at your fingertips, 24/7." ),
        4 => array( "Regulatory Status",        "Classified as a Medical Food (FSMP), enabling it to occupy a unique, trusted position between medication and nutrition." ),
    );
    for ( $i = 1; $i <= 4; $i++ ) {
        $wp_customize->add_setting( "vance_about_feat{$i}_title", array( "default" => $feat_defaults[$i][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_about_feat{$i}_title", array( "label" => "Feature $i Title", "section" => "vance_about_product", "type" => "text" ) );
        $wp_customize->add_setting( "vance_about_feat{$i}_desc",  array( "default" => $feat_defaults[$i][1], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( "vance_about_feat{$i}_desc",  array( "label" => "Feature $i Description", "section" => "vance_about_product", "type" => "textarea" ) );
    // Styles for EPAVANCE Spotlight
    $wp_customize->add_setting( "vance_about_product_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_about_product_show", array( "label" => "Show Section", "section" => "vance_about_product", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_about_product_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_product_bg", array( "label" => "Background Colour", "section" => "vance_about_product" ) ) );
    $wp_customize->add_setting( "vance_about_product_tag_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_product_tag_bg", array( "label" => "Tag Label Background Colour", "section" => "vance_about_product" ) ) );
    $wp_customize->add_setting( "vance_about_product_tag_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_product_tag_color", array( "label" => "Tag Label Font Colour", "section" => "vance_about_product" ) ) );
    $wp_customize->add_setting( "vance_about_product_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_product_title_color", array( "label" => "Title Colour", "section" => "vance_about_product" ) ) );
    $wp_customize->add_setting( "vance_about_product_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_product_title_size", array( "label" => "Title Font Size (e.g. 40px)", "section" => "vance_about_product", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_product_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_product_text_color", array( "label" => "Text Colour", "section" => "vance_about_product" ) ) );
    $wp_customize->add_setting( "vance_about_product_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_product_text_size", array( "label" => "Text Font Size (e.g. 18px)", "section" => "vance_about_product", "type" => "text" ) );

    }

    // ── Patient Stories ────────────────────────────────────────
    $wp_customize->add_section( "vance_about_testimonials", array( "title" => "Patient Stories", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_testimonials_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_about_testimonials_show", array( "label" => "Show Section", "section" => "vance_about_testimonials", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_about_testimonials_tag",   array( "default" => "Patient Stories", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_testimonials_tag",   array( "label" => "Section Tag", "section" => "vance_about_testimonials", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_testimonials_title", array( "default" => "Real People, Real Results", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_testimonials_title", array( "label" => "Heading", "section" => "vance_about_testimonials", "type" => "text" ) );

    $testi_defaults = array(
        1 => array( "The Vance Health Hub has completely changed how I manage my Crohn's disease. The nutritional guidance alongside my medication has made a real difference to my daily life.", "S.M.", "Sarah M.", "Living with Crohn's Disease" ),
        2 => array( "As a gastroenterologist, I recommend Vance to my patients because I trust their pharmaceutical-grade approach. The evidence base behind their products is exactly what I look for.", "D.P.", "Dr. Patel", "Consultant Gastroenterologist" ),
        3 => array( "Finally, a resource that combines proper medical science with practical nutrition advice. The VANCE-Ai tool helps me understand my condition without the jargon.", "J.T.", "James T.", "Living with IBS" ),
    );
    for ( $i = 1; $i <= 3; $i++ ) {
        $wp_customize->add_setting( "vance_about_testi{$i}_quote",    array( "default" => $testi_defaults[$i][0], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( "vance_about_testi{$i}_quote",    array( "label" => "Testimonial $i Quote", "section" => "vance_about_testimonials", "type" => "textarea" ) );
        $wp_customize->add_setting( "vance_about_testi{$i}_initials", array( "default" => $testi_defaults[$i][1], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_about_testi{$i}_initials", array( "label" => "Testimonial $i Initials", "section" => "vance_about_testimonials", "type" => "text" ) );
        $wp_customize->add_setting( "vance_about_testi{$i}_name",     array( "default" => $testi_defaults[$i][2], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_about_testi{$i}_name",     array( "label" => "Testimonial $i Name", "section" => "vance_about_testimonials", "type" => "text" ) );
        $wp_customize->add_setting( "vance_about_testi{$i}_role",     array( "default" => $testi_defaults[$i][3], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_about_testi{$i}_role",     array( "label" => "Testimonial $i Role", "section" => "vance_about_testimonials", "type" => "text" ) );
    }

    // ── Platform Section ──────────────────────────────────────
    $wp_customize->add_section( "vance_about_platform", array( "title" => "Platform Section", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_plat_tag",   array( "default" => "The Digital Layer", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_plat_tag",   array( "label" => "Section Tag", "section" => "vance_about_platform", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_plat_title", array( "default" => "Your Complete Digital Health Companion", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_plat_title", array( "label" => "Heading", "section" => "vance_about_platform", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_plat_desc",  array( "default" => "Beyond our product pipeline, Vance Medical is building a world-class digital health hub, combining clinical-grade content, AI-powered tools, and a vibrant community for patients and healthcare professionals.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_about_plat_desc",  array( "label" => "Description", "section" => "vance_about_platform", "type" => "textarea" ) );

    $plat_defaults = array(
        1 => array( "Clinical Content Hub",    "Clinical research and patient education curated by gastroenterologists and dietitians." ),
        2 => array( "VANCE-Ai",      "Specialised AI trained on clinical gastroenterology to answer your health questions safely." ),
        3 => array( "Patient Dashboard",       "Track health records, manage your Gastro tools, and connect with your care pathway." ),
        4 => array( "HCP Professional Portal", "Dedicated space for healthcare practitioners to access protocols and collaborate." ),
        5 => array( "Health Calculators",      "Evidence-based clinical calculators for malnutrition screening, BMI, and disease scoring." ),
        6 => array( "Education Courses",       "Multi-chapter learning pathways for both patients and clinicians." ),
    );
    for ( $i = 1; $i <= 6; $i++ ) {
        $wp_customize->add_setting( "vance_about_plat{$i}_title", array( "default" => $plat_defaults[$i][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_about_plat{$i}_title", array( "label" => "Platform Item $i Title", "section" => "vance_about_platform", "type" => "text" ) );
        $wp_customize->add_setting( "vance_about_plat{$i}_desc",  array( "default" => $plat_defaults[$i][1], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( "vance_about_plat{$i}_desc",  array( "label" => "Platform Item $i Description", "section" => "vance_about_platform", "type" => "textarea" ) );
    // Styles for Platform Section
    $wp_customize->add_setting( "vance_about_platform_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_about_platform_show", array( "label" => "Show Section", "section" => "vance_about_platform", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_about_platform_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_platform_bg", array( "label" => "Background Colour", "section" => "vance_about_platform" ) ) );
    $wp_customize->add_setting( "vance_about_platform_tag_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_platform_tag_bg", array( "label" => "Tag Label Background Colour", "section" => "vance_about_platform" ) ) );
    $wp_customize->add_setting( "vance_about_platform_tag_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_platform_tag_color", array( "label" => "Tag Label Font Colour", "section" => "vance_about_platform" ) ) );
    $wp_customize->add_setting( "vance_about_platform_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_platform_title_color", array( "label" => "Title Colour", "section" => "vance_about_platform" ) ) );
    $wp_customize->add_setting( "vance_about_platform_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_platform_title_size", array( "label" => "Title Font Size (e.g. 40px)", "section" => "vance_about_platform", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_platform_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_platform_text_color", array( "label" => "Text Colour", "section" => "vance_about_platform" ) ) );
    $wp_customize->add_setting( "vance_about_platform_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_platform_text_size", array( "label" => "Text Font Size (e.g. 18px)", "section" => "vance_about_platform", "type" => "text" ) );

    }

    // --- CTA Strip ---
    $wp_customize->add_section( "vance_about_cta", array( "title" => "CTA Strip", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_cta_title",      array( "default" => "Ready to Take Control of Your Gastro Health?", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_cta_title",      array( "label" => "Heading", "section" => "vance_about_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_cta_desc",       array( "default" => "Join thousands of patients and healthcare professionals who trust Vance Health Hub for evidence-based gastrointestinal care.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_about_cta_desc",       array( "label" => "Description", "section" => "vance_about_cta", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_about_cta_btn1_label", array( "default" => "Join For Free", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_cta_btn1_label", array( "label" => "Button 1 Label", "section" => "vance_about_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_cta_btn1_url",   array( "default" => "/register/", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_about_cta_btn1_url",   array( "label" => "Button 1 URL", "section" => "vance_about_cta", "type" => "url" ) );
    $wp_customize->add_setting( "vance_about_cta_btn2_label", array( "default" => "Speak to Our Team", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_cta_btn2_label", array( "label" => "Button 2 Label", "section" => "vance_about_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_cta_btn2_url",   array( "default" => "/contact-us/", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_about_cta_btn2_url",   array( "label" => "Button 2 URL", "section" => "vance_about_cta", "type" => "url" ) );
    // Styles for CTA Strip
    $wp_customize->add_setting( "vance_about_cta_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_about_cta_show", array( "label" => "Show Section", "section" => "vance_about_cta", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_about_cta_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_cta_bg", array( "label" => "Background Colour", "section" => "vance_about_cta" ) ) );
    $wp_customize->add_setting( "vance_about_cta_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_cta_title_color", array( "label" => "Title Colour", "section" => "vance_about_cta" ) ) );
    $wp_customize->add_setting( "vance_about_cta_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_cta_title_size", array( "label" => "Title Font Size (e.g. 36px)", "section" => "vance_about_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_cta_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_cta_text_color", array( "label" => "Text Colour", "section" => "vance_about_cta" ) ) );
    $wp_customize->add_setting( "vance_about_cta_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_cta_text_size", array( "label" => "Text Font Size (e.g. 18px)", "section" => "vance_about_cta", "type" => "text" ) );

    // ---- CONTACT US PAGE PANEL ----
    $wp_customize->add_panel( "vance_contact_panel", array(
        "title"    => __( "Page - Contact Us", "vance-health-hub" ),
        "priority" => 47,
    ) );

    // ── Hero ──────────────────────────────────────────────────
    $wp_customize->add_section( "vance_contact_hero", array( "title" => "Hero Section", "panel" => "vance_contact_panel" ) );
    $wp_customize->add_setting( "vance_contact_hero_tag",      array( "default" => "Get in Touch",             "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_contact_hero_tag",      array( "label" => "Tag Label",                  "section" => "vance_contact_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_contact_hero_title",    array( "default" => "We'd Love to Hear From You", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_contact_hero_title",    array( "label" => "Heading (HTML allowed)",     "section" => "vance_contact_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_contact_hero_desc",     array( "default" => "Whether you're a patient, healthcare professional, researcher, or media contact, our team is here to help.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_contact_hero_desc",     array( "label" => "Description",                "section" => "vance_contact_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_contact_hero_img",      array( "default" => "",                         "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_contact_hero_img", array( "label" => "Background Image", "section" => "vance_contact_hero" ) ) );
    $wp_customize->add_setting( "vance_contact_hero_bg_color", array( "default" => "",                         "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_contact_hero_bg_color", array( "label" => "Solid Background Color (overrides image)", "section" => "vance_contact_hero" ) ) );
    // Tag-label colours (rendered as the small "Get in Touch" pill above the H1).
    $wp_customize->add_setting( "vance_contact_hero_tag_bg",    array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_contact_hero_tag_bg",    array( "label" => "Tag Label Background Colour", "section" => "vance_contact_hero" ) ) );
    $wp_customize->add_setting( "vance_contact_hero_tag_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_contact_hero_tag_color", array( "label" => "Tag Label Font Colour",       "section" => "vance_contact_hero" ) ) );

    // ── Contact Info ──────────────────────────────────────────
    $wp_customize->add_section( "vance_contact_info", array( "title" => "Contact Information", "panel" => "vance_contact_panel" ) );
    $wp_customize->add_setting( "vance_contact_intro_title", array( "default" => "How Can We Help?",         "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_contact_intro_title", array( "label" => "Section Heading",            "section" => "vance_contact_info", "type" => "text" ) );
    $wp_customize->add_setting( "vance_contact_intro_text",  array( "default" => "Vance Medical is committed to providing exceptional support to every member of our community.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_contact_intro_text",  array( "label" => "Intro Paragraph",            "section" => "vance_contact_info", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_contact_email",       array( "default" => "team@vancemedicalfoods.co.uk", "sanitize_callback" => "sanitize_email" ) );
    $wp_customize->add_control( "vance_contact_email",       array( "label" => "Email Address",              "section" => "vance_contact_info", "type" => "text" ) );
    $wp_customize->add_setting( "vance_contact_phone",       array( "default" => "+44 (0)1628 526 005",      "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_contact_phone",       array( "label" => "Phone Number",               "section" => "vance_contact_info", "type" => "text" ) );
    $wp_customize->add_setting( "vance_contact_address",     array( "default" => "Vance Medical Foods Ltd, 4 Renaissance Way, Wooburn Green, HP10 0DF, United Kingdom", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_contact_address",     array( "label" => "Office Address",             "section" => "vance_contact_info", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_contact_hours",       array( "default" => "Monday – Friday, 9:00 am – 5:00 pm GMT", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_contact_hours",       array( "label" => "Office Hours",               "section" => "vance_contact_info", "type" => "text" ) );

    // ── Contact Form Spam Protection (reCAPTCHA v3) ─────────────────────
    // Get a free key pair at https://www.google.com/recaptcha/admin (register
    // the site under reCAPTCHA v3). Contact form silently skips verification
    // when either key is blank, so it keeps working un-protected until both
    // are set — see vance_contact_recaptcha_verify() in page-contact-us.php.
    $wp_customize->add_section( "vance_contact_recaptcha", array( "title" => "Contact Form Spam Protection", "panel" => "vance_contact_panel" ) );
    $wp_customize->add_setting( "vance_recaptcha_site_key",   array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_recaptcha_site_key",   array( "label" => "reCAPTCHA v3 Site Key",   "description" => "Public key, safe to expose in page source.", "section" => "vance_contact_recaptcha", "type" => "text" ) );
    $wp_customize->add_setting( "vance_recaptcha_secret_key", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_recaptcha_secret_key", array( "label" => "reCAPTCHA v3 Secret Key", "description" => "Private key, used server-side only.", "section" => "vance_contact_recaptcha", "type" => "text" ) );

    // ============================================================
    // HERO OVERLAY OPACITY SLIDERS — per-page (0–100, mapped to alpha 0.00–1.00)
    // ============================================================
    $hero_overlay_pages = array(
        "vance_pat_hero"        => array( "default" => 70, "label" => "Patients hero overlay opacity (%)" ),
        "vance_hcp_hero"        => array( "default" => 75, "label" => "HCP hero overlay opacity (%)" ),
        "vance_about_hero"      => array( "default" => 78, "label" => "About hero overlay opacity (%)" ),
        "vance_contact_hero"    => array( "default" => 78, "label" => "Contact hero overlay opacity (%)" ),
    );
    foreach ( $hero_overlay_pages as $section => $cfg ) {
        $setting = $section . "_overlay";
        $wp_customize->add_setting( $setting, array(
            "default"           => $cfg["default"],
            "sanitize_callback" => "absint",
        ) );
        $wp_customize->add_control( $setting, array(
            "label"       => $cfg["label"],
            "section"     => $section,
            "type"        => "number",
            "input_attrs" => array( "min" => 0, "max" => 100, "step" => 5 ),
        ) );
    }

    // For pages without a dedicated hero section in this file (evidence, ask-ai, home)
    // — group their overlay sliders into a shared panel.
    $wp_customize->add_panel( "vance_overlays_panel", array(
        "title"    => __( "Hero Overlays", "vance-health-hub" ),
        "priority" => 60,
    ) );
    $wp_customize->add_section( "vance_overlays_misc", array(
        "title" => "Per-page Overlay Opacity",
        "panel" => "vance_overlays_panel",
        "description" => "Slide 0–100 to control how dark the photo overlay is on these heroes. Higher = darker.",
    ) );
    $extra_overlays = array(
        "vance_home_hero_overlay"     => array( "default" => 50, "label" => "Home hero overlay opacity (%)" ),
        "vance_evidence_hero_overlay" => array( "default" => 78, "label" => "Turn Evidence hero overlay opacity (%)" ),
        "vance_askai_hero_overlay"    => array( "default" => 85, "label" => "VANCE-Ai hero overlay opacity (%)" ),
    );
    foreach ( $extra_overlays as $key => $cfg ) {
        $wp_customize->add_setting( $key, array(
            "default"           => $cfg["default"],
            "sanitize_callback" => "absint",
        ) );
        $wp_customize->add_control( $key, array(
            "label"       => $cfg["label"],
            "section"     => "vance_overlays_misc",
            "type"        => "number",
            "input_attrs" => array( "min" => 0, "max" => 100, "step" => 5 ),
        ) );
    }

    // ============================================================
    // EDUCATION (COMING SOON) PAGE PANEL
    // ============================================================
    $wp_customize->add_panel( "vance_edu_panel", array(
        "title"    => __( "Page - Education", "vance-health-hub" ),
        "priority" => 44,
    ) );

    // Education Hero
    $wp_customize->add_section( "vance_edu_hero", array( "title" => "Hero Section", "panel" => "vance_edu_panel" ) );
    $wp_customize->add_setting( "vance_edu_hero_tag",   array( "default" => "Education", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_edu_hero_tag",   array( "label" => "Tag Label", "section" => "vance_edu_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_edu_hero_title", array( "default" => "Courses are <span class=\"highlight\">Coming Soon</span>", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_edu_hero_title", array( "label" => "Title (HTML allowed)", "section" => "vance_edu_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_edu_hero_desc",  array( "default" => "We're building self-paced courses for patients and CPD-accredited modules for practitioners. Join the waitlist to be the first to know when enrolment opens.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_edu_hero_desc",  array( "label" => "Description", "section" => "vance_edu_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_edu_hero_bg",    array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_edu_hero_bg", array( "label" => "Hero Background Image", "section" => "vance_edu_hero" ) ) );
    $wp_customize->add_setting( "vance_edu_hero_overlay", array( "default" => 75, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_edu_hero_overlay", array( "label" => "Hero Overlay Opacity (%)", "section" => "vance_edu_hero", "type" => "number", "input_attrs" => array( "min" => 0, "max" => 100, "step" => 5 ) ) );
    $wp_customize->add_setting( "vance_edu_hero_eyebrow_color", array( "default" => "#008080", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_hero_eyebrow_color", array( "label" => "Hero Eyebrow Colour",       "section" => "vance_edu_hero" ) ) );
    $wp_customize->add_setting( "vance_edu_hero_title_color",   array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_hero_title_color",   array( "label" => "Hero Title Colour",         "section" => "vance_edu_hero" ) ) );

    // ── Education Intro Section (cloned from tools-resources intro, same shape) ──
    // 64px padding (20% less than the standard .section-padding's 80px). Eyebrow
    // pill above an H2 + description paragraph. Identical Customizer fields to
    // vance_tools_intro_* but in the vance_edu_intro_* namespace with course-
    // flavoured copy defaults.
    $wp_customize->add_section( "vance_edu_intro", array( "title" => "Intro Section", "panel" => "vance_edu_panel" ) );
    $wp_customize->add_setting( "vance_edu_intro_eyebrow", array( "default" => "Coming Soon", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_edu_intro_eyebrow", array( "label" => "Eyebrow / tag label", "section" => "vance_edu_intro", "type" => "text" ) );
    $wp_customize->add_setting( "vance_edu_intro_title", array( "default" => "Courses crafted by clinicians, for life with IBD", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_edu_intro_title", array( "label" => "Section Title", "section" => "vance_edu_intro", "type" => "text" ) );
    $wp_customize->add_setting( "vance_edu_intro_desc",  array( "default" => "Self-paced patient courses and CPD-accredited practitioner modules, written, reviewed, and field-tested by gastroenterologists and dietitians. Pick a track below to be notified when enrolment opens.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_edu_intro_desc",  array( "label" => "Description", "section" => "vance_edu_intro", "type" => "textarea" ) );
    // Section background + body text colour (controls colour of H2 + paragraph; eyebrow has its own pair).
    $wp_customize->add_setting( "vance_edu_intro_bg_color", array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_intro_bg_color", array( "label" => "Section Background Colour", "section" => "vance_edu_intro" ) ) );
    $wp_customize->add_setting( "vance_edu_intro_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_intro_text_color", array( "label" => "Title + Body Font Colour (blank = theme defaults)", "section" => "vance_edu_intro" ) ) );
    // Eyebrow pill colours.
    $wp_customize->add_setting( "vance_edu_intro_eyebrow_bg",    array( "default" => "rgba(0,128,128,0.08)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_edu_intro_eyebrow_bg",    array( "label" => "Eyebrow Background (hex or rgba)", "section" => "vance_edu_intro", "type" => "text" ) );
    $wp_customize->add_setting( "vance_edu_intro_eyebrow_color", array( "default" => "#008080", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_intro_eyebrow_color", array( "label" => "Eyebrow Font Colour", "section" => "vance_edu_intro" ) ) );

    // Education Tracks
    $wp_customize->add_section( "vance_edu_tracks", array( "title" => "Course Tracks", "panel" => "vance_edu_panel" ) );
    $wp_customize->add_setting( "vance_edu_tracks_bg",          array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_tracks_bg",          array( "label" => "Section Background Colour",     "section" => "vance_edu_tracks" ) ) );
    $wp_customize->add_setting( "vance_edu_tracks_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_tracks_title_color", array( "label" => "Title Colour (blank = theme)",  "section" => "vance_edu_tracks" ) ) );
    $wp_customize->add_setting( "vance_edu_tracks_text_color",  array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_tracks_text_color",  array( "label" => "Body Text Colour (blank = theme)", "section" => "vance_edu_tracks" ) ) );
    $track_defaults = array(
        1 => array( "Patient Courses", "Self-paced modules on living with IBD: nutrition fundamentals, symptom tracking, mealtime confidence, and working with your care team. Designed in plain English with downloadable worksheets." ),
        2 => array( "Practitioner Courses", "CPD-accredited deep dives on FSMP integration, Omega-3 dosing, malnutrition screening, and translating evidence into protocols. Built for gastroenterologists, dietitians, GPs, and pharmacists." ),
    );
    for ( $i = 1; $i <= 2; $i++ ) {
        $wp_customize->add_setting( "vance_edu_track{$i}_title", array( "default" => $track_defaults[$i][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_edu_track{$i}_title", array( "label" => "Track {$i} Title", "section" => "vance_edu_tracks", "type" => "text" ) );
        $wp_customize->add_setting( "vance_edu_track{$i}_desc",  array( "default" => $track_defaults[$i][1], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( "vance_edu_track{$i}_desc",  array( "label" => "Track {$i} Description", "section" => "vance_edu_tracks", "type" => "textarea" ) );
    }

    // Education Waitlist
    $wp_customize->add_section( "vance_edu_waitlist", array( "title" => "Waitlist Signup", "panel" => "vance_edu_panel" ) );
    $wp_customize->add_setting( "vance_edu_waitlist_heading", array( "default" => "Join the Waitlist", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_edu_waitlist_heading", array( "label" => "Heading", "section" => "vance_edu_waitlist", "type" => "text" ) );
    $wp_customize->add_setting( "vance_edu_waitlist_desc",    array( "default" => "Be first to hear when patient or practitioner courses go live. We'll send a single email, no spam, easy unsubscribe.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_edu_waitlist_desc",    array( "label" => "Description", "section" => "vance_edu_waitlist", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_edu_waitlist_action",  array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_edu_waitlist_action",  array( "label" => "Form Action URL (Mailchimp/HubSpot endpoint - leave blank to hide form)", "section" => "vance_edu_waitlist", "type" => "url" ) );
    $wp_customize->add_setting( "vance_edu_waitlist_button",  array( "default" => "Notify Me", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_edu_waitlist_button",  array( "label" => "Button Label", "section" => "vance_edu_waitlist", "type" => "text" ) );
    $wp_customize->add_setting( "vance_edu_waitlist_bg_from",    array( "default" => "#008080", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_waitlist_bg_from",    array( "label" => "Gradient - From Colour", "section" => "vance_edu_waitlist" ) ) );
    $wp_customize->add_setting( "vance_edu_waitlist_bg_to",      array( "default" => "#006666", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_waitlist_bg_to",      array( "label" => "Gradient - To Colour",   "section" => "vance_edu_waitlist" ) ) );
    $wp_customize->add_setting( "vance_edu_waitlist_text_color", array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_waitlist_text_color", array( "label" => "Text Colour",            "section" => "vance_edu_waitlist" ) ) );

    // ============================================================
    // TOOLS & RESOURCES PAGE PANEL
    // ============================================================
    $wp_customize->add_panel( "vance_tools_panel", array(
        "title"    => __( "Page - Tools & Resources", "vance-health-hub" ),
        "priority" => 45,
    ) );

    // Tools Hero
    $wp_customize->add_section( "vance_tools_hero", array( "title" => "Hero Section", "panel" => "vance_tools_panel" ) );
    $wp_customize->add_setting( "vance_tools_hero_tag",   array( "default" => "Free Tools", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_tools_hero_tag",   array( "label" => "Tag Label", "section" => "vance_tools_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_tools_hero_title", array( "default" => "Tools &amp; <span class=\"highlight\">Resources</span>", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_tools_hero_title", array( "label" => "Title (HTML allowed)", "section" => "vance_tools_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_tools_hero_desc",  array( "default" => "Clinical calculators built on peer-reviewed evidence, free to use, no signup required. Save your results and build a meal plan by registering for a free account.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_tools_hero_desc",  array( "label" => "Description", "section" => "vance_tools_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_tools_hero_bg",    array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_tools_hero_bg", array( "label" => "Hero Background Image", "section" => "vance_tools_hero" ) ) );
    $wp_customize->add_setting( "vance_tools_hero_overlay", array( "default" => 70, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_tools_hero_overlay", array( "label" => "Hero Overlay Opacity (%)", "section" => "vance_tools_hero", "type" => "number", "input_attrs" => array( "min" => 0, "max" => 100, "step" => 5 ) ) );

    // Hero CTA button (set text to empty to hide it). A second "Try a Tool"
    // button was removed 2026-08-07 — it anchor-linked to #tools-list, which
    // never matched anything on the page (the grid's real id is #tools-grid).
    $wp_customize->add_setting( "vance_tools_hero_btn2_text", array( "default" => "Create Free Account", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_tools_hero_btn2_text", array( "label" => "Button 2 - Text (blank to hide)", "section" => "vance_tools_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_tools_hero_btn2_link", array( "default" => "/register/", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_tools_hero_btn2_link", array( "label" => "Button 2 - Link", "section" => "vance_tools_hero", "type" => "url" ) );

    // Tools Intro
    $wp_customize->add_section( "vance_tools_intro", array( "title" => "Intro Section", "panel" => "vance_tools_panel" ) );
    $wp_customize->add_setting( "vance_tools_intro_eyebrow", array( "default" => "Open Access", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_tools_intro_eyebrow", array( "label" => "Eyebrow / tag label", "section" => "vance_tools_intro", "type" => "text" ) );
    $wp_customize->add_setting( "vance_tools_intro_title", array( "default" => "Clinical-grade calculators, free for everyone", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_tools_intro_title", array( "label" => "Section Title", "section" => "vance_tools_intro", "type" => "text" ) );
    $wp_customize->add_setting( "vance_tools_intro_desc",  array( "default" => "Whether you're tracking your own health or supporting a patient, these tools turn evidence into a number you can act on. No login needed to use them, register if you want to save results to your dashboard.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_tools_intro_desc",  array( "label" => "Description", "section" => "vance_tools_intro", "type" => "textarea" ) );
    // Section background + text colour (controls colour of H2 + paragraph; eyebrow has its own pair).
    $wp_customize->add_setting( "vance_tools_intro_bg_color", array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_tools_intro_bg_color", array( "label" => "Section Background Colour", "section" => "vance_tools_intro" ) ) );
    $wp_customize->add_setting( "vance_tools_intro_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_tools_intro_text_color", array( "label" => "Title + Body Font Colour (blank = theme defaults)", "section" => "vance_tools_intro" ) ) );
    // Eyebrow pill colours.
    $wp_customize->add_setting( "vance_tools_intro_eyebrow_bg",    array( "default" => "rgba(0,128,128,0.08)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_tools_intro_eyebrow_bg",    array( "label" => "Eyebrow Background (hex or rgba)", "section" => "vance_tools_intro", "type" => "text" ) );
    $wp_customize->add_setting( "vance_tools_intro_eyebrow_color", array( "default" => "#008080", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_tools_intro_eyebrow_color", array( "label" => "Eyebrow Font Colour", "section" => "vance_tools_intro" ) ) );

    // ============================================================
    // PER-TOOL HERO SECTIONS — grouped under the Tools panel.
    // Mods read by page-{slug}.php wrapper templates (malnutrition,
    // ibd-recipies). Each tool gets one section with name,
    // subtitle, hero bg image, and overlay slider.
    // ============================================================
    $tool_hero_specs = array(
        'malnutrition' => array(
            'section_id'   => 'vance_tools_hero_malnutrition',
            'title'        => 'Malnutrition Calculator, Hero',
            'name_key'     => 'vance_tool_malnutrition_name',
            'sub_key'      => 'vance_tool_malnutrition_subtitle',
            'bg_key'       => 'vance_tool_malnutrition_hero_bg',
            'overlay_key'  => 'vance_tool_malnutrition_hero_overlay',
            'name_default' => 'IBD Malnutrition Calculator',
            'sub_default'  => 'Clinically-grounded 11-step malnutrition risk screener for IBD patients. Combines MUST, IBD-NST, and GLIM criteria into a single, actionable score.',
        ),
        'recipes'      => array(
            'section_id'   => 'vance_tools_hero_recipes',
            'title'        => 'IBD Recipes, Hero',
            'name_key'     => 'vance_tool_recipes_name',
            'sub_key'      => 'vance_tool_recipes_subtitle',
            'bg_key'       => 'vance_tool_recipes_hero_bg',
            'overlay_key'  => 'vance_tool_recipes_hero_overlay',
            'name_default' => 'IBD Recipes & Meal Planner',
            'sub_default'  => 'EPA-rich, gut-friendly recipes with full nutrition data. Browse and build a weekly plan freely, saving plans takes two clicks to create your free account.',
        ),
    );
    foreach ( $tool_hero_specs as $key => $spec ) {
        $section = $spec['section_id'];
        $wp_customize->add_section( $section, array( "title" => $spec['title'], "panel" => "vance_tools_panel" ) );

        // Tool name (H1) + colour/size.
        $wp_customize->add_setting( $spec['name_key'], array( "default" => $spec['name_default'], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( $spec['name_key'], array( "label" => "Tool Name (H1)", "section" => $section, "type" => "text" ) );
        $wp_customize->add_setting( $spec['name_key'] . '_color', array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $spec['name_key'] . '_color', array( "label" => "Title Font Colour", "section" => $section ) ) );
        $wp_customize->add_setting( $spec['name_key'] . '_size', array( "default" => 56, "sanitize_callback" => "absint" ) );
        $wp_customize->add_control( $spec['name_key'] . '_size', array( "label" => "Title Font Size (px)", "section" => $section, "type" => "number", "input_attrs" => array( "min" => 24, "max" => 96, "step" => 2 ) ) );

        // Subtitle + colour/size.
        $wp_customize->add_setting( $spec['sub_key'],  array( "default" => $spec['sub_default'], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( $spec['sub_key'],  array( "label" => "Subtitle", "section" => $section, "type" => "textarea" ) );
        $wp_customize->add_setting( $spec['sub_key'] . '_color', array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $spec['sub_key'] . '_color', array( "label" => "Subtitle Font Colour", "section" => $section ) ) );
        $wp_customize->add_setting( $spec['sub_key'] . '_size', array( "default" => 19, "sanitize_callback" => "absint" ) );
        $wp_customize->add_control( $spec['sub_key'] . '_size', array( "label" => "Subtitle Font Size (px)", "section" => $section, "type" => "number", "input_attrs" => array( "min" => 12, "max" => 32, "step" => 1 ) ) );

        // Badge: text + bg + fg.
        $badge_key = str_replace( '_name', '_badge', $spec['name_key'] ); // vance_tool_omega_badge etc.
        $wp_customize->add_setting( $badge_key,           array( "default" => "Free Tool", "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( $badge_key,           array( "label" => "Badge Text", "section" => $section, "type" => "text" ) );
        $wp_customize->add_setting( $badge_key . '_bg',   array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) ); // accepts rgba()
        $wp_customize->add_control( $badge_key . '_bg',   array( "label" => "Badge Background (hex or rgba)", "section" => $section, "type" => "text" ) );
        $wp_customize->add_setting( $badge_key . '_color', array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $badge_key . '_color', array( "label" => "Badge Font Colour", "section" => $section ) ) );

        // Hero background image + overlay.
        $wp_customize->add_setting( $spec['bg_key'],   array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $spec['bg_key'], array( "label" => "Hero Background Image", "section" => $section ) ) );
        $wp_customize->add_setting( $spec['overlay_key'], array( "default" => 80, "sanitize_callback" => "absint" ) );
        $wp_customize->add_control( $spec['overlay_key'], array(
            "label"       => "Hero Overlay Opacity (%)",
            "section"     => $section,
            "type"        => "number",
            "input_attrs" => array( "min" => 0, "max" => 100, "step" => 5 ),
        ) );
    }

    // ============================================================
    // TURN EVIDENCE INTO ACTION — full content + styling controls
    // (gap flagged in CLAUDE.md §6.5; mirrors the mods read by
    //  page-turn-evidence-into-action.php, no parallel naming)
    // ============================================================
    $wp_customize->add_panel( "vance_evidence_panel", array(
        "title"    => __( "Page - Get Started", "vance-health-hub" ),
        "priority" => 41,
    ) );

    // ─── Hero ──────────────────────────────────────────────────────
    $wp_customize->add_section( "vance_evidence_hero", array( "title" => "Hero Section", "panel" => "vance_evidence_panel" ) );
    // Tag-label content + colours (the "Evidence to Practice" pill above the H1).
    $wp_customize->add_setting( "vance_evidence_hero_tag_bg",    array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_hero_tag_bg",    array( "label" => "Tag Label Background Colour", "section" => "vance_evidence_hero" ) ) );
    $wp_customize->add_setting( "vance_evidence_hero_tag_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_hero_tag_color", array( "label" => "Tag Label Font Colour",       "section" => "vance_evidence_hero" ) ) );
    // Hero body-text + title colour overrides (apply to the H1 + paragraph).
    $wp_customize->add_setting( "vance_evidence_hero_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_hero_title_color", array( "label" => "Hero Title Colour",     "section" => "vance_evidence_hero" ) ) );
    $wp_customize->add_setting( "vance_evidence_hero_text_color",  array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_hero_text_color",  array( "label" => "Hero Body Text Colour", "section" => "vance_evidence_hero" ) ) );

    $wp_customize->add_setting( "vance_evidence_hero_tag",   array( "default" => "Evidence to Practice", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_evidence_hero_tag",   array( "label" => "Tag Label", "section" => "vance_evidence_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_evidence_hero_title", array( "default" => "Turn <span class=\"highlight\">Evidence</span> into Action", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_evidence_hero_title", array( "label" => "Title (HTML allowed)", "section" => "vance_evidence_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_evidence_hero_desc",  array( "default" => "Rigorous clinical research only matters when it reaches the patient. Vance Medical translates peer-reviewed science and real-world data into practical protocols that clinicians and patients can act on.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_evidence_hero_desc",  array( "label" => "Description", "section" => "vance_evidence_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_evidence_hero_bg",    array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_evidence_hero_bg", array( "label" => "Hero Background Image", "section" => "vance_evidence_hero" ) ) );
    // Buttons
    $wp_customize->add_setting( "vance_evidence_hero_btn1_text", array( "default" => "Explore the Evidence Library", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_evidence_hero_btn1_text", array( "label" => "Primary Button Label", "section" => "vance_evidence_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_evidence_hero_btn1_link", array( "default" => "#pillars", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_evidence_hero_btn1_link", array( "label" => "Primary Button Link", "section" => "vance_evidence_hero", "type" => "url" ) );
    // Secondary hero button ("Dive into our Knowledgebase" / "Request a
    // Clinical Consultation") removed 2026-08-07 — see page-turn-evidence-into-action.php.
    // Hero overlay slider lives in this section so admins find it next to the bg image (was in "Hero Overlays (extra)" only)
    $wp_customize->add_setting( "vance_evidence_hero_overlay_inline", array( "default" => 78, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_evidence_hero_overlay_inline", array(
        "label"       => "Hero Overlay Opacity (%) - duplicates the slider in “Hero Overlays (extra)”",
        "section"     => "vance_evidence_hero",
        "type"        => "number",
        "input_attrs" => array( "min" => 0, "max" => 100, "step" => 5 ),
        "description" => "Note: the canonical slider is `vance_evidence_hero_overlay`; this is a convenience duplicate.",
    ) );

    // ─── Pillars ───────────────────────────────────────────────────
    $wp_customize->add_section( "vance_evidence_pillars", array( "title" => "Evidence Pillars", "panel" => "vance_evidence_panel" ) );
    // Pillars tag-label colours + per-section title/body colour overrides.
    $wp_customize->add_setting( "vance_evidence_pillars_tag_bg",    array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_pillars_tag_bg",    array( "label" => "Tag Label Background Colour", "section" => "vance_evidence_pillars" ) ) );
    $wp_customize->add_setting( "vance_evidence_pillars_tag_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_pillars_tag_color", array( "label" => "Tag Label Font Colour",       "section" => "vance_evidence_pillars" ) ) );
    $wp_customize->add_setting( "vance_evidence_pillars_tag_border", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_pillars_tag_border", array( "label" => "Tag Label Border Colour",     "section" => "vance_evidence_pillars" ) ) );
    $wp_customize->add_setting( "vance_evidence_pillars_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_pillars_title_color", array( "label" => "Section Title Colour",     "section" => "vance_evidence_pillars" ) ) );
    $wp_customize->add_setting( "vance_evidence_pillars_text_color",  array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_pillars_text_color",  array( "label" => "Section Body Text Colour", "section" => "vance_evidence_pillars" ) ) );

    $wp_customize->add_setting( "vance_evidence_pillars_tag",   array( "default" => "Our Evidence Standards", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_evidence_pillars_tag",   array( "label" => "Section Tag Label", "section" => "vance_evidence_pillars", "type" => "text" ) );
    $wp_customize->add_setting( "vance_evidence_pillars_title", array( "default" => "Four Sources. One Standard.", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_evidence_pillars_title", array( "label" => "Section Title", "section" => "vance_evidence_pillars", "type" => "text" ) );
    $wp_customize->add_setting( "vance_evidence_pillars_desc",  array( "default" => "Every recommendation we publish is anchored in at least one of these evidence streams and graded against internationally-recognised quality criteria.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_evidence_pillars_desc",  array( "label" => "Section Description", "section" => "vance_evidence_pillars", "type" => "textarea" ) );

    $pillar_defaults = array(
        1 => array( "Clinical Trials",      "Randomised controlled trials and phase II–IV studies investigating medical food and nutritional interventions in IBD, SIBO, and related GI conditions." ),
        2 => array( "Real-World Data",      "Longitudinal outcomes from registered patient cohorts, post-market surveillance, and anonymised dashboard analytics across thousands of IBD journeys." ),
        3 => array( "Peer-Reviewed Science","Curated meta-analyses and systematic reviews from Gut, AJG, Lancet Gastro, JCN, and other indexed journals, summarised for bedside use." ),
        4 => array( "Expert Consensus",     "Multidisciplinary panel statements from gastroenterologists, dietitians, and pharmacists who have validated the protocol pathways we publish." ),
    );
    for ( $i = 1; $i <= 4; $i++ ) {
        $wp_customize->add_setting( "vance_evidence_pillar{$i}_title", array( "default" => $pillar_defaults[ $i ][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_evidence_pillar{$i}_title", array( "label" => "Pillar {$i} Title", "section" => "vance_evidence_pillars", "type" => "text" ) );
        $wp_customize->add_setting( "vance_evidence_pillar{$i}_desc",  array( "default" => $pillar_defaults[ $i ][1], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( "vance_evidence_pillar{$i}_desc",  array( "label" => "Pillar {$i} Description", "section" => "vance_evidence_pillars", "type" => "textarea" ) );
    }

    // ─── Process (Insight to Practice) ────────────────────────────
    $wp_customize->add_section( "vance_evidence_proc", array( "title" => "From Insight to Practice", "panel" => "vance_evidence_panel" ) );
    // Per-section colour overrides.
    $wp_customize->add_setting( "vance_evidence_proc_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_proc_title_color", array( "label" => "Section Title Colour",     "section" => "vance_evidence_proc" ) ) );
    $wp_customize->add_setting( "vance_evidence_proc_text_color",  array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_proc_text_color",  array( "label" => "Section Body Text Colour", "section" => "vance_evidence_proc" ) ) );

    $wp_customize->add_setting( "vance_evidence_proc_title", array( "default" => "From Insight to Practice", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_evidence_proc_title", array( "label" => "Section Title", "section" => "vance_evidence_proc", "type" => "text" ) );
    $wp_customize->add_setting( "vance_evidence_proc_desc",  array( "default" => "The journey every piece of evidence takes before it reaches a clinician protocol or a patient-facing recommendation.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_evidence_proc_desc",  array( "label" => "Section Description", "section" => "vance_evidence_proc", "type" => "textarea" ) );

    $proc_defaults = array(
        1 => array( "Synthesise", "Our medical writing team combines primary studies, guidelines, and registry data into a single graded position, with conflicts of interest and limitations flagged openly." ),
        2 => array( "Translate",  "We convert each position into two companion artefacts: a clinician-facing protocol card and a plain-language patient brief vetted by a patient advisory panel." ),
        3 => array( "Apply",      "Protocols feed the Vance Medical dashboard, VANCE-Ai, and downloadable handouts, so evidence becomes a concrete decision at the point of care." ),
    );
    for ( $i = 1; $i <= 3; $i++ ) {
        $wp_customize->add_setting( "vance_evidence_proc{$i}_title", array( "default" => $proc_defaults[ $i ][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_evidence_proc{$i}_title", array( "label" => "Step {$i} Title", "section" => "vance_evidence_proc", "type" => "text" ) );
        $wp_customize->add_setting( "vance_evidence_proc{$i}_desc",  array( "default" => $proc_defaults[ $i ][1], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( "vance_evidence_proc{$i}_desc",  array( "label" => "Step {$i} Description", "section" => "vance_evidence_proc", "type" => "textarea" ) );
    }

    // ─── Featured Evidence (post query) ───────────────────────────
    $wp_customize->add_section( "vance_evidence_feat", array( "title" => "Featured Evidence (post grid)", "panel" => "vance_evidence_panel" ) );
    // Per-section colour overrides.
    $wp_customize->add_setting( "vance_evidence_feat_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_feat_title_color", array( "label" => "Section Title Colour",     "section" => "vance_evidence_feat" ) ) );
    $wp_customize->add_setting( "vance_evidence_feat_text_color",  array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_feat_text_color",  array( "label" => "Section Body Text Colour", "section" => "vance_evidence_feat" ) ) );

    $wp_customize->add_setting( "vance_evidence_feat_title", array( "default" => "Latest Evidence in Focus", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_evidence_feat_title", array( "label" => "Section Title", "section" => "vance_evidence_feat", "type" => "text" ) );
    $wp_customize->add_setting( "vance_evidence_feat_desc",  array( "default" => "Recent reviews, trial readouts, and protocol updates published by the Vance Medical editorial team.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_evidence_feat_desc",  array( "label" => "Section Description", "section" => "vance_evidence_feat", "type" => "textarea" ) );
    // Category selector (uses dropdown-pages style → category dropdown).
    $cat_choices = array( 0 => 'All categories' );
    $all_cats = get_categories( array( 'hide_empty' => false ) );
    if ( is_array( $all_cats ) ) {
        foreach ( $all_cats as $cat ) {
            $cat_choices[ (int) $cat->term_id ] = $cat->name;
        }
    }
    $wp_customize->add_setting( "vance_evidence_feat_category", array( "default" => 0, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_evidence_feat_category", array(
        "label"   => "Filter by Category",
        "section" => "vance_evidence_feat",
        "type"    => "select",
        "choices" => $cat_choices,
    ) );
    $wp_customize->add_setting( "vance_evidence_feat_count", array( "default" => 3, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_evidence_feat_count", array(
        "label"       => "Posts to Show",
        "section"     => "vance_evidence_feat",
        "type"        => "number",
        "input_attrs" => array( "min" => 1, "max" => 12, "step" => 1 ),
    ) );

    // ─── Final CTA ────────────────────────────────────────────────
    $wp_customize->add_section( "vance_evidence_cta", array( "title" => "Final CTA Section", "panel" => "vance_evidence_panel" ) );
    // Per-section colour overrides (default white-on-teal — leave blank to use defaults).
    $wp_customize->add_setting( "vance_evidence_cta_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_cta_title_color", array( "label" => "Title Colour",         "section" => "vance_evidence_cta" ) ) );
    $wp_customize->add_setting( "vance_evidence_cta_text_color",  array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_cta_text_color",  array( "label" => "Body Text Colour",     "section" => "vance_evidence_cta" ) ) );

    $wp_customize->add_setting( "vance_evidence_cta_title", array( "default" => "Put Evidence to Work for Your Patients", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_evidence_cta_title", array( "label" => "Title", "section" => "vance_evidence_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_evidence_cta_desc",  array( "default" => "Free registration unlocks the full protocol library, VANCE-Ai, and printable patient handouts branded to your practice.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_evidence_cta_desc",  array( "label" => "Description", "section" => "vance_evidence_cta", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_evidence_cta_btn1_text", array( "default" => "Register Free", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_evidence_cta_btn1_text", array( "label" => "Primary Button Label", "section" => "vance_evidence_cta", "type" => "text" ) );
    // Link control removed 2026-08-07 — pinned to /login/?tab=signup in
    // page-turn-evidence-into-action.php so it can't drift off-target again.
    $wp_customize->add_setting( "vance_evidence_cta_btn2_text", array( "default" => "Talk to Our Team", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_evidence_cta_btn2_text", array( "label" => "Secondary Button Label", "section" => "vance_evidence_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_evidence_cta_btn2_link", array( "default" => "/contact-us/", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_evidence_cta_btn2_link", array( "label" => "Secondary Button Link", "section" => "vance_evidence_cta", "type" => "url" ) );

    // ─── Styling (page-wide overrides) ────────────────────────────
    $wp_customize->add_section( "vance_evidence_styling", array( "title" => "Page Styling", "panel" => "vance_evidence_panel" ) );
    $wp_customize->add_setting( "vance_evidence_hero_bg_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_hero_bg_color", array( "label" => "Hero Background Colour (overrides image)", "section" => "vance_evidence_styling" ) ) );
    $wp_customize->add_setting( "vance_evidence_pillars_bg",     array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_pillars_bg",     array( "label" => "Pillars Section Background", "section" => "vance_evidence_styling" ) ) );
    $wp_customize->add_setting( "vance_evidence_proc_bg",        array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_proc_bg",        array( "label" => "Process Section Background", "section" => "vance_evidence_styling" ) ) );
    $wp_customize->add_setting( "vance_evidence_feat_bg",        array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_feat_bg",        array( "label" => "Featured-Evidence Background", "section" => "vance_evidence_styling" ) ) );
    $wp_customize->add_setting( "vance_evidence_cta_bg_from",    array( "default" => "#008080", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_cta_bg_from",    array( "label" => "CTA Gradient - From", "section" => "vance_evidence_styling" ) ) );
    $wp_customize->add_setting( "vance_evidence_cta_bg_to",      array( "default" => "#006666", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_cta_bg_to",      array( "label" => "CTA Gradient - To",   "section" => "vance_evidence_styling" ) ) );
    $wp_customize->add_setting( "vance_evidence_heading_color",  array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_heading_color",  array( "label" => "Section Heading Colour", "section" => "vance_evidence_styling" ) ) );
    $wp_customize->add_setting( "vance_evidence_body_color",     array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_body_color",     array( "label" => "Body Text Colour", "section" => "vance_evidence_styling" ) ) );
    $wp_customize->add_setting( "vance_evidence_pillar_card_bg", array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_pillar_card_bg", array( "label" => "Pillar / Process Card Background", "section" => "vance_evidence_styling" ) ) );

    // ============================================================
    // DISCOVERY SUITE — extra controls (panel headers, subtitle,
    // toggle on/off colours, chip selected/unselected, AskAi input).
    // Section vance_discovery_styling is already registered in
    // functions.php; we just add settings to it.
    // ============================================================
    // Subtitle colour (the H2-description paragraph).
    $wp_customize->add_setting( "vance_discovery_subtitle_color", array( "default" => "rgba(255,255,255,0.55)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_discovery_subtitle_color", array( "label" => "Subtitle Colour (hex or rgba)", "section" => "vance_discovery_styling", "type" => "text" ) );

    // Left-panel header: "DISCOVERY FILTERS"
    $wp_customize->add_setting( "vance_discovery_filters_label_text",  array( "default" => "Discovery Filters", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_discovery_filters_label_text",  array( "label" => "Filters Header - Text", "section" => "vance_discovery_styling", "type" => "text" ) );
    $wp_customize->add_setting( "vance_discovery_filters_label_size",  array( "default" => 12, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_discovery_filters_label_size",  array( "label" => "Filters Header - Font Size (px)", "section" => "vance_discovery_styling", "type" => "number", "input_attrs" => array( "min" => 8, "max" => 30, "step" => 1 ) ) );
    $wp_customize->add_setting( "vance_discovery_filters_label_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_filters_label_color", array( "label" => "Filters Header - Colour (blank = brand teal)", "section" => "vance_discovery_styling" ) ) );

    // Right-panel header: "AI CLINICAL INTELLIGENCE"
    $wp_customize->add_setting( "vance_discovery_ai_label_text",  array( "default" => "VANCE-Ai", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_discovery_ai_label_text",  array( "label" => "AI Header - Text", "section" => "vance_discovery_styling", "type" => "text" ) );
    $wp_customize->add_setting( "vance_discovery_ai_label_size",  array( "default" => 12, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_discovery_ai_label_size",  array( "label" => "AI Header - Font Size (px)", "section" => "vance_discovery_styling", "type" => "number", "input_attrs" => array( "min" => 8, "max" => 30, "step" => 1 ) ) );
    $wp_customize->add_setting( "vance_discovery_ai_label_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_ai_label_color", array( "label" => "AI Header - Colour (blank = white)", "section" => "vance_discovery_styling" ) ) );

    // Reading-level toggles (on / off states).
    $wp_customize->add_setting( "vance_discovery_toggle_off_bg",     array( "default" => "rgba(255,255,255,0.10)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_discovery_toggle_off_bg",     array( "label" => "Toggle Off - Background (hex/rgba)", "section" => "vance_discovery_styling", "type" => "text" ) );
    $wp_customize->add_setting( "vance_discovery_toggle_off_dot",    array( "default" => "rgba(255,255,255,0.60)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_discovery_toggle_off_dot",    array( "label" => "Toggle Off - Dot Colour", "section" => "vance_discovery_styling", "type" => "text" ) );
    $wp_customize->add_setting( "vance_discovery_toggle_on_bg",      array( "default" => "#008080", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_toggle_on_bg",      array( "label" => "Toggle On - Background", "section" => "vance_discovery_styling" ) ) );
    $wp_customize->add_setting( "vance_discovery_toggle_on_dot",     array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_toggle_on_dot",     array( "label" => "Toggle On - Dot Colour", "section" => "vance_discovery_styling" ) ) );

    // Chip (pathway + content-type) selected & unselected colour pairs.
    $wp_customize->add_setting( "vance_discovery_chip_off_bg",      array( "default" => "rgba(255,255,255,0.06)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_discovery_chip_off_bg",      array( "label" => "Chip Unselected - Background (hex/rgba)", "section" => "vance_discovery_styling", "type" => "text" ) );
    $wp_customize->add_setting( "vance_discovery_chip_off_border",  array( "default" => "rgba(255,255,255,0.12)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_discovery_chip_off_border",  array( "label" => "Chip Unselected - Border (hex/rgba)", "section" => "vance_discovery_styling", "type" => "text" ) );
    $wp_customize->add_setting( "vance_discovery_chip_off_text",    array( "default" => "rgba(255,255,255,0.75)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_discovery_chip_off_text",    array( "label" => "Chip Unselected - Text", "section" => "vance_discovery_styling", "type" => "text" ) );
    $wp_customize->add_setting( "vance_discovery_chip_on_bg",       array( "default" => "rgba(0,128,128,0.20)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_discovery_chip_on_bg",       array( "label" => "Chip Selected - Background (hex/rgba)", "section" => "vance_discovery_styling", "type" => "text" ) );
    $wp_customize->add_setting( "vance_discovery_chip_on_border",   array( "default" => "#008080", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_chip_on_border", array( "label" => "Chip Selected - Border", "section" => "vance_discovery_styling" ) ) );
    $wp_customize->add_setting( "vance_discovery_chip_on_text",     array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_chip_on_text", array( "label" => "Chip Selected - Text", "section" => "vance_discovery_styling" ) ) );

    // Ask AI input box.
    $wp_customize->add_setting( "vance_discovery_askai_input_bg",     array( "default" => "rgba(255,255,255,0.06)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_discovery_askai_input_bg",     array( "label" => "VANCE-Ai Input - Background (hex/rgba)", "section" => "vance_discovery_styling", "type" => "text" ) );
    $wp_customize->add_setting( "vance_discovery_askai_input_color",  array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_askai_input_color", array( "label" => "VANCE-Ai Input - Text Colour", "section" => "vance_discovery_styling" ) ) );
    $wp_customize->add_setting( "vance_discovery_askai_input_border", array( "default" => "rgba(255,255,255,0.12)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_discovery_askai_input_border", array( "label" => "VANCE-Ai Input - Border (hex/rgba)", "section" => "vance_discovery_styling", "type" => "text" ) );

    // Action buttons — solid colours (no gradient). Blank background = keep theme default.
    $wp_customize->add_setting( "vance_discovery_btn_go_bg",       array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_btn_go_bg",       array( "label" => "GO Button - Background (blank = theme default)", "section" => "vance_discovery_styling" ) ) );
    $wp_customize->add_setting( "vance_discovery_btn_go_color",    array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_btn_go_color",    array( "label" => "GO Button - Text Colour", "section" => "vance_discovery_styling" ) ) );

    $wp_customize->add_setting( "vance_discovery_btn_clear_bg",    array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_btn_clear_bg",    array( "label" => "Clear Button - Background (blank = theme default)", "section" => "vance_discovery_styling" ) ) );
    $wp_customize->add_setting( "vance_discovery_btn_clear_color", array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_btn_clear_color", array( "label" => "Clear Button - Text Colour", "section" => "vance_discovery_styling" ) ) );

    $wp_customize->add_setting( "vance_discovery_btn_save_bg",     array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_btn_save_bg",     array( "label" => "Save Search Button - Background (blank = theme default)", "section" => "vance_discovery_styling" ) ) );
    $wp_customize->add_setting( "vance_discovery_btn_save_color",  array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_btn_save_color",  array( "label" => "Save Search Button - Text Colour", "section" => "vance_discovery_styling" ) ) );

    $wp_customize->add_setting( "vance_discovery_btn_send_bg",     array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_btn_send_bg",     array( "label" => "Send Button - Background (blank = theme default)", "section" => "vance_discovery_styling" ) ) );
    $wp_customize->add_setting( "vance_discovery_btn_send_color",  array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_btn_send_color",  array( "label" => "Send Button - Text Colour", "section" => "vance_discovery_styling" ) ) );

    // Status text — "AI (Online)" and "Content Filters (Active)".
    $wp_customize->add_setting( "vance_discovery_status_ai_size",       array( "default" => 10, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_discovery_status_ai_size",       array( "label" => "AI Status (Online) - Font Size (px)", "section" => "vance_discovery_styling", "type" => "number", "input_attrs" => array( "min" => 8, "max" => 24, "step" => 1 ) ) );
    $wp_customize->add_setting( "vance_discovery_status_ai_color",      array( "default" => "#22C55E", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_discovery_status_ai_color", array( "label" => "AI Status (Online) - Text Colour", "section" => "vance_discovery_styling" ) ) );

    $wp_customize->add_setting( "vance_discovery_status_filters_size",  array( "default" => 10, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_discovery_status_filters_size",  array( "label" => "Filters Status (Active) - Font Size (px)", "section" => "vance_discovery_styling", "type" => "number", "input_attrs" => array( "min" => 8, "max" => 24, "step" => 1 ) ) );
    $wp_customize->add_setting( "vance_discovery_status_filters_color", array( "default" => "rgba(255,255,255,0.5)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_discovery_status_filters_color", array( "label" => "Filters Status (Active) - Text Colour (hex/rgba)", "section" => "vance_discovery_styling", "type" => "text" ) );

    // ============================================================
    // PREMIUM SUBSCRIBE SECTION — full Customizer panel
    // ============================================================
    $wp_customize->add_panel( "vance_premium_panel", array(
        "title"    => __( "Homepage: Premium Band", "vance-health-hub" ),
        "priority" => 48,
    ) );

    // Section: Content
    $wp_customize->add_section( "vance_premium_content", array( "title" => "Content", "panel" => "vance_premium_panel" ) );
    $wp_customize->add_setting( "vance_premium_eyebrow",       array( "default" => "Join the Inner Circle", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_premium_eyebrow",       array( "label" => "Eyebrow Text", "section" => "vance_premium_content", "type" => "text" ) );
    $wp_customize->add_setting( "vance_premium_heading",       array( "default" => "Access <span class=\"highlight\">IBD Clinical Resources</span>", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_premium_heading",       array( "label" => "Heading (HTML - wrap with <span class=\"highlight\"> for accent colour)", "section" => "vance_premium_content", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_premium_desc",          array( "default" => "Gain access to premium articles, monthly masterclasses, and a personalized health dashboard. Join 50,000+ members on the path to better living.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_premium_desc",          array( "label" => "Description", "section" => "vance_premium_content", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_premium_pill_1",        array( "default" => "Expert Reviews", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_premium_pill_1",        array( "label" => "Feature Pill 1", "section" => "vance_premium_content", "type" => "text" ) );
    $wp_customize->add_setting( "vance_premium_pill_2",        array( "default" => "Weekly Digests", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_premium_pill_2",        array( "label" => "Feature Pill 2", "section" => "vance_premium_content", "type" => "text" ) );

    // Section: Card (right column)
    $wp_customize->add_section( "vance_premium_card", array( "title" => "Signup Card", "panel" => "vance_premium_panel" ) );
    $wp_customize->add_setting( "vance_premium_card_heading",     array( "default" => "Start Your Journey", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_premium_card_heading",     array( "label" => "Card Heading", "section" => "vance_premium_card", "type" => "text" ) );
    $wp_customize->add_setting( "vance_premium_card_subheading", array( "default" => "", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_premium_card_subheading", array( "label" => "Card Subheading (optional, blank = hidden)", "section" => "vance_premium_card", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_premium_card_subheading_color", array( "default" => "#000000", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_card_subheading_color", array( "label" => "Card Subheading Colour", "section" => "vance_premium_card" ) ) );
    $wp_customize->add_setting( "vance_premium_input_placeholder", array( "default" => "Enter your email address", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_premium_input_placeholder", array( "label" => "Email Input Placeholder", "section" => "vance_premium_card", "type" => "text" ) );
    $wp_customize->add_setting( "vance_premium_button_label",     array( "default" => "Get Started Now →", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_premium_button_label",     array( "label" => "Button Label", "section" => "vance_premium_card", "type" => "text" ) );
    $wp_customize->add_setting( "vance_premium_button_link",      array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_premium_button_link",      array( "label" => "Button Link (blank = WP register URL)", "section" => "vance_premium_card", "type" => "url" ) );
    $wp_customize->add_setting( "vance_premium_card_footnote",   array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_premium_card_footnote",   array( "label" => "Footnote (small text below button)", "section" => "vance_premium_card", "type" => "text" ) );

    // Section: Colours
    $wp_customize->add_section( "vance_premium_colors", array( "title" => "Colours", "panel" => "vance_premium_panel" ) );
    $wp_customize->add_setting( "vance_premium_section_bg",  array( "default" => "#0f172a", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_section_bg",  array( "label" => "Section Background", "section" => "vance_premium_colors" ) ) );
    $wp_customize->add_setting( "vance_premium_eyebrow_color",  array( "default" => "#008080", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_eyebrow_color",  array( "label" => "Eyebrow Tag - Font Colour",        "section" => "vance_premium_colors" ) ) );
    $wp_customize->add_setting( "vance_premium_eyebrow_bg",     array( "default" => "",        "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_eyebrow_bg",     array( "label" => "Eyebrow Tag - Background Colour",  "section" => "vance_premium_colors" ) ) );
    $wp_customize->add_setting( "vance_premium_eyebrow_border", array( "default" => "",        "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_eyebrow_border", array( "label" => "Eyebrow Tag - Border Colour",      "section" => "vance_premium_colors" ) ) );
    $wp_customize->add_setting( "vance_premium_heading_color", array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_heading_color", array( "label" => "Heading Colour", "section" => "vance_premium_colors" ) ) );
    $wp_customize->add_setting( "vance_premium_highlight_color", array( "default" => "#008080", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_highlight_color", array( "label" => "Heading Highlight (.highlight span) Colour", "section" => "vance_premium_colors" ) ) );
    $wp_customize->add_setting( "vance_premium_desc_color",   array( "default" => "#94a3b8", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_desc_color", array( "label" => "Description Colour", "section" => "vance_premium_colors" ) ) );
    $wp_customize->add_setting( "vance_premium_pill_text_color", array( "default" => "#cbd5e1", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_pill_text_color", array( "label" => "Feature Pills - Text Colour", "section" => "vance_premium_colors" ) ) );
    $wp_customize->add_setting( "vance_premium_pill_check_color", array( "default" => "#008080", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_pill_check_color", array( "label" => "Feature Pills - Check Mark Colour", "section" => "vance_premium_colors" ) ) );
    $wp_customize->add_setting( "vance_premium_card_bg",      array( "default" => "rgba(255,255,255,0.05)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_premium_card_bg",      array( "label" => "Card Background (hex/rgba)", "section" => "vance_premium_colors", "type" => "text" ) );
    $wp_customize->add_setting( "vance_premium_card_border",  array( "default" => "rgba(255,255,255,0.10)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_premium_card_border",  array( "label" => "Card Border (hex/rgba)", "section" => "vance_premium_colors", "type" => "text" ) );
    $wp_customize->add_setting( "vance_premium_card_heading_color", array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_card_heading_color", array( "label" => "Card Heading Colour", "section" => "vance_premium_colors" ) ) );
    $wp_customize->add_setting( "vance_premium_input_bg",     array( "default" => "rgba(0,0,0,0.20)", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_premium_input_bg",     array( "label" => "Input - Background (hex/rgba)", "section" => "vance_premium_colors", "type" => "text" ) );
    $wp_customize->add_setting( "vance_premium_input_color",  array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_input_color", array( "label" => "Input - Text Colour", "section" => "vance_premium_colors" ) ) );
    $wp_customize->add_setting( "vance_premium_button_bg",    array( "default" => "#008080", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_button_bg", array( "label" => "Button - Background", "section" => "vance_premium_colors" ) ) );
    $wp_customize->add_setting( "vance_premium_button_color", array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_premium_button_color", array( "label" => "Button - Text Colour", "section" => "vance_premium_colors" ) ) );

    // Section: Sizing
    $wp_customize->add_section( "vance_premium_sizing", array( "title" => "Sizing", "panel" => "vance_premium_panel" ) );
    $wp_customize->add_setting( "vance_premium_pad_top",    array( "default" => 100, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_premium_pad_top",    array( "label" => "Section Padding Top (px)", "section" => "vance_premium_sizing", "type" => "number", "input_attrs" => array( "min" => 0, "max" => 240, "step" => 5 ) ) );
    $wp_customize->add_setting( "vance_premium_pad_bottom", array( "default" => 100, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_premium_pad_bottom", array( "label" => "Section Padding Bottom (px)", "section" => "vance_premium_sizing", "type" => "number", "input_attrs" => array( "min" => 0, "max" => 240, "step" => 5 ) ) );
    $wp_customize->add_setting( "vance_premium_heading_size", array( "default" => 42, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_premium_heading_size", array( "label" => "Heading Size (px)", "section" => "vance_premium_sizing", "type" => "number", "input_attrs" => array( "min" => 24, "max" => 72, "step" => 2 ) ) );
    $wp_customize->add_setting( "vance_premium_desc_size",    array( "default" => 18, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_premium_desc_size",    array( "label" => "Description Size (px)", "section" => "vance_premium_sizing", "type" => "number", "input_attrs" => array( "min" => 12, "max" => 28, "step" => 1 ) ) );

    // ============================================================
    // HEALTHCARE QUIZ — hero shell mods (mirrors AskAi visual layout)
    // ============================================================
    $wp_customize->add_panel( "vance_hquiz_panel", array(
        "title"    => __( "Page - Gastro Health Survey", "vance-health-hub" ),
        "priority" => 46,
    ) );
    $wp_customize->add_section( "vance_hquiz_hero", array( "title" => "Hero Section", "panel" => "vance_hquiz_panel" ) );
    $wp_customize->add_setting( "vance_hquiz_hero_badge",    array( "default" => "Self-Assessment", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_hquiz_hero_badge",    array( "label" => "Hero Badge Text", "section" => "vance_hquiz_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_hquiz_hero_title",    array( "default" => "Gastro Health Survey", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_hquiz_hero_title",    array( "label" => "Hero Title (H1)", "section" => "vance_hquiz_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_hquiz_hero_subtitle", array( "default" => "A short, evidence-based questionnaire covering symptom patterns, dietary triggers, and lifestyle factors. Answers are private, get an instant summary you can share with your clinician.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_hquiz_hero_subtitle", array( "label" => "Hero Subtitle", "section" => "vance_hquiz_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_hquiz_hero_bg",       array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_hquiz_hero_bg", array( "label" => "Hero Background Image", "section" => "vance_hquiz_hero" ) ) );
    $wp_customize->add_setting( "vance_hquiz_hero_overlay",  array( "default" => 85, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_hquiz_hero_overlay",  array(
        "label"       => "Hero Overlay Opacity (%)",
        "section"     => "vance_hquiz_hero",
        "type"        => "number",
        "input_attrs" => array( "min" => 0, "max" => 100, "step" => 5 ),
    ) );

    // ============================================================
    // USER GUIDE PAGE — page-user-guide.php already reads these three
    // vance_userguide_hero_* mods via vance_get_theme_mod() with inline
    // defaults; this section is the previously-missing admin UI for them
    // (same gap noted in that file's own doc comment).
    // ============================================================
    $wp_customize->add_panel( "vance_userguide_panel", array(
        "title"    => __( "Page - User Guide", "vance-health-hub" ),
        "priority" => 47,
    ) );
    $wp_customize->add_section( "vance_userguide_hero", array( "title" => "Hero Section", "panel" => "vance_userguide_panel" ) );
    $wp_customize->add_setting( "vance_userguide_hero_tag",   array( "default" => "User Guide", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_userguide_hero_tag",   array( "label" => "Tag Label", "section" => "vance_userguide_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_userguide_hero_title", array( "default" => 'Get the most out of <span class="highlight">Vance Medical Hub</span>', "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_userguide_hero_title", array( "label" => "Title (HTML allowed)", "section" => "vance_userguide_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_userguide_hero_desc",  array( "default" => "Vance Health Hub is built to be the credible source you turn to at every step of your healthcare journey — evidence-based research, clinically-grounded tools, and a private dashboard that keeps your data, notes and AI conversations in one place. This guide shows you how it all fits together.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_userguide_hero_desc",  array( "label" => "Description", "section" => "vance_userguide_hero", "type" => "textarea" ) );
    // Same bg image + overlay pair as the Tools & Resources hero (vance_tools_hero_bg/overlay).
    $wp_customize->add_setting( "vance_userguide_hero_bg",    array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_userguide_hero_bg", array( "label" => "Hero Background Image", "section" => "vance_userguide_hero" ) ) );
    $wp_customize->add_setting( "vance_userguide_hero_overlay", array( "default" => 70, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_userguide_hero_overlay", array( "label" => "Hero Overlay Opacity (%)", "section" => "vance_userguide_hero", "type" => "number", "input_attrs" => array( "min" => 0, "max" => 100, "step" => 5 ) ) );

    // ============================================================
    // PATIENT DOWNLOADS HUB (page-patient-downloads.php) — same three-mod
    // hero pattern as User Guide / Tools & Resources above.
    // ============================================================
    $wp_customize->add_panel( "vance_patientdownloads_panel", array(
        "title"    => __( "Page - Patient Downloads", "vance-health-hub" ),
        "priority" => 48,
    ) );
    $wp_customize->add_section( "vance_patientdownloads_hero", array( "title" => "Hero Section", "panel" => "vance_patientdownloads_panel" ) );
    $wp_customize->add_setting( "vance_patientdownloads_hero_tag",   array( "default" => "Patient Downloads", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_patientdownloads_hero_tag",   array( "label" => "Tag Label", "section" => "vance_patientdownloads_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_patientdownloads_hero_title", array( "default" => 'Printable guides for your <span class="highlight">next appointment</span>', "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_patientdownloads_hero_title", array( "label" => "Title (HTML allowed)", "section" => "vance_patientdownloads_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_patientdownloads_hero_desc",  array( "default" => "Free, evidence-backed PDF handouts you can save to your phone or print — built for the moments a screen isn't the easiest way to have the conversation.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_patientdownloads_hero_desc",  array( "label" => "Description", "section" => "vance_patientdownloads_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_patientdownloads_hero_bg",    array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_patientdownloads_hero_bg", array( "label" => "Hero Background Image", "section" => "vance_patientdownloads_hero" ) ) );
    $wp_customize->add_setting( "vance_patientdownloads_hero_overlay", array( "default" => 70, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_patientdownloads_hero_overlay", array( "label" => "Hero Overlay Opacity (%)", "section" => "vance_patientdownloads_hero", "type" => "number", "input_attrs" => array( "min" => 0, "max" => 100, "step" => 5 ) ) );

    // ============================================================
    // KNOWLEDGEBASE LOBBY (page-knowledgebase.php)
    // The block buttons themselves are NOT edited here — they mirror the
    // children of the KNOWLEDGEBASE item in Appearance -> Menus, so the
    // flyout and the lobby cannot drift apart. This panel is the page's
    // surrounding copy only, plus the label used to find that menu item.
    // ============================================================
    $wp_customize->add_panel( "vance_kblobby_panel", array(
        "title"    => __( "Page - Knowledgebase", "vance-health-hub" ),
        "priority" => 48,
    ) );

    $wp_customize->add_section( "vance_kblobby_hero", array( "title" => "Hero Section", "panel" => "vance_kblobby_panel" ) );
    $wp_customize->add_setting( "vance_kblobby_hero_tag",   array( "default" => "Knowledgebase", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_hero_tag",   array( "label" => "Tag Label", "section" => "vance_kblobby_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_hero_title", array( "default" => 'The whole <span class="highlight">evidence library</span>, one door', "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_kblobby_hero_title", array( "label" => "Title (HTML allowed)", "section" => "vance_kblobby_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_kblobby_hero_desc",  array( "default" => "Clinical reviews, gastro living guides, health news and courses - every collection in the Vance Medical Hub, grouped so you can go straight to the one you need.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_kblobby_hero_desc",  array( "label" => "Description", "section" => "vance_kblobby_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_kblobby_hero_bg",    array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_kblobby_hero_bg", array( "label" => "Hero Background Image", "section" => "vance_kblobby_hero" ) ) );
    $wp_customize->add_setting( "vance_kblobby_hero_overlay", array( "default" => 72, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_kblobby_hero_overlay", array( "label" => "Hero Overlay Opacity (%)", "section" => "vance_kblobby_hero", "type" => "number", "input_attrs" => array( "min" => 0, "max" => 100, "step" => 5 ) ) );

    $wp_customize->add_section( "vance_kblobby_intro", array( "title" => "Intro Section", "panel" => "vance_kblobby_panel" ) );
    $wp_customize->add_setting( "vance_kblobby_intro_eyebrow", array( "default" => "Start Here", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_intro_eyebrow", array( "label" => "Eyebrow", "section" => "vance_kblobby_intro", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_intro_title",   array( "default" => "Pick a collection", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_intro_title",   array( "label" => "Title", "section" => "vance_kblobby_intro", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_intro_desc",    array( "default" => "Every collection below is curated and clinically reviewed. Not sure where to begin? Search across all of them at once.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_kblobby_intro_desc",    array( "label" => "Description", "section" => "vance_kblobby_intro", "type" => "textarea" ) );

    $wp_customize->add_section( "vance_kblobby_layout", array( "title" => "Layout & Colour", "panel" => "vance_kblobby_panel" ) );

    $wp_customize->add_setting( "vance_kblobby_per_row", array( "default" => 2, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_kblobby_per_row", array(
        "label"       => "Cards per row",
        "description" => "Desktop only. Three-up drops to two on tablets, and every setting collapses to one card per row on phones.",
        "section"     => "vance_kblobby_layout",
        "type"        => "select",
        "choices"     => array( 1 => "1 per row", 2 => "2 per row", 3 => "3 per row" ),
    ) );

    $wp_customize->add_setting( "vance_kblobby_accent_mode", array( "default" => "single", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_accent_mode", array(
        "label"       => "Card accent colour",
        "description" => "One colour keeps the page calm and lets the icons and titles do the distinguishing. Per-collection borrows each category's own colour from its homepage Knowledge Base section, which is louder and implies a colour code the site does not otherwise use.",
        "section"     => "vance_kblobby_layout",
        "type"        => "select",
        "choices"     => array( "single" => "One colour for every card", "match" => "Per collection (match homepage sections)" ),
    ) );

    $wp_customize->add_setting( "vance_kblobby_accent_single", array( "default" => "#008080", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_kblobby_accent_single", array(
        "label"       => "Card accent",
        "description" => "Used when the setting above is \"One colour for every card\". Light colours are handled: the card darkens this automatically for text and icons so it always clears the 4.5:1 contrast minimum, while the card's edge keeps the colour exactly as picked.",
        "section"     => "vance_kblobby_layout",
    ) ) );

    $wp_customize->add_setting( "vance_kblobby_peek_count", array( "default" => 3, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_kblobby_peek_count", array(
        "label"       => "Preview links per card",
        "description" => "How many of the newest articles each card lists. 0 hides the preview. The Gastro Health hub lists its conditions instead.",
        "section"     => "vance_kblobby_layout",
        "type"        => "number",
        "input_attrs" => array( "min" => 0, "max" => 5, "step" => 1 ),
    ) );

    $wp_customize->add_setting( "vance_kblobby_peek_label", array( "default" => "Latest inside", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_peek_label", array(
        "label"   => "Preview heading",
        "section" => "vance_kblobby_layout",
        "type"    => "text",
    ) );

    $wp_customize->add_section( "vance_kblobby_labels", array( "title" => "Block Labels", "panel" => "vance_kblobby_panel" ) );

    $wp_customize->add_setting( "vance_kblobby_soon_label", array( "default" => "Coming soon", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_soon_label", array(
        "label"       => "Not-launched label",
        "description" => "Shown instead of an article count on a collection with nothing published in it yet.",
        "section"     => "vance_kblobby_labels",
        "type"        => "text",
    ) );

    $wp_customize->add_setting( "vance_kblobby_hidden_titles", array( "default" => "", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_kblobby_hidden_titles", array(
        "label"       => "Collections hidden from this page",
        "description" => "One block title per line. These keep their place in the KNOWLEDGEBASE menu but get no card here - for a destination the nav still needs to reach while the lobby has nothing worth promoting about it. Matching ignores case, spacing and punctuation, and treats \"&\" and \"and\" as the same word.",
        "section"     => "vance_kblobby_labels",
        "type"        => "textarea",
    ) );

    $wp_customize->add_setting( "vance_kblobby_soon_titles", array( "default" => "Webinars and Courses", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_kblobby_soon_titles", array(
        "label"       => "Collections not launched yet",
        "description" => "One block title per line. Those blocks show the label above instead of a count, even if the page they link to has content. A title listed as hidden above never appears at all, so listing it here as well has no effect. Matching ignores case, spacing and punctuation, and treats \"&\" and \"and\" as the same word. Categories with no posts get the label automatically and do not need listing here.",
        "section"     => "vance_kblobby_labels",
        "type"        => "textarea",
    ) );

    $wp_customize->add_section( "vance_kblobby_source", array( "title" => "Block Source", "panel" => "vance_kblobby_panel" ) );
    $wp_customize->add_setting( "vance_kblobby_menu_label", array( "default" => "Knowledgebase", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_menu_label", array(
        "label"       => "Menu item label",
        "description" => "Which item in the primary menu supplies the block buttons. Matching ignores case, spaces and hyphens, so \"KNOWLEDGEBASE\" and \"Knowledge Base\" both work. Once the menu item points at this page it is matched by link instead and this field stops mattering. If no match is found the page falls back to listing every top-level category.",
        "section"     => "vance_kblobby_source",
        "type"        => "text",
    ) );


    // ---- Scale strip (under the hero) ----
    // Priorities from 170 so these five sections sit AFTER the ones above,
    // which take WP's default of 160 and are ordered by registration.
    $wp_customize->add_section( "vance_kblobby_stats", array( "title" => "Scale Strip", "panel" => "vance_kblobby_panel", "priority" => 170 ) );
    $wp_customize->add_setting( "vance_kblobby_stats_show", array( "default" => true, "sanitize_callback" => "vance_sanitize_checkbox" ) );
    $wp_customize->add_control( "vance_kblobby_stats_show", array(
        "label"       => "Show the strip",
        "description" => "The row of figures under the hero. The numbers themselves are counted at render time and cannot be typed in - only their labels are editable, so the page can never claim more than the library holds. A figure that comes back as zero drops its cell.",
        "section"     => "vance_kblobby_stats",
        "type"        => "checkbox",
    ) );
    $wp_customize->add_setting( "vance_kblobby_stats_articles", array( "default" => "Articles", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_stats_articles", array( "label" => "Label - published articles", "section" => "vance_kblobby_stats", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_stats_shelves", array( "default" => "Collections", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_stats_shelves", array( "label" => "Label - collections", "description" => "Counts the cards actually rendered below, so hiding a collection updates this too.", "section" => "vance_kblobby_stats", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_stats_conditions", array( "default" => "Conditions covered", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_stats_conditions", array( "label" => "Label - conditions", "section" => "vance_kblobby_stats", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_stats_tools", array( "default" => "Free tools", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_stats_tools", array( "label" => "Label - free tools", "section" => "vance_kblobby_stats", "type" => "text" ) );

    // ---- Topics ----
    $wp_customize->add_section( "vance_kblobby_topics", array( "title" => "Topics Section", "panel" => "vance_kblobby_panel", "priority" => 171 ) );
    $wp_customize->add_setting( "vance_kblobby_topics_show", array( "default" => true, "sanitize_callback" => "vance_sanitize_checkbox" ) );
    $wp_customize->add_control( "vance_kblobby_topics_show", array(
        "label"       => "Show Section",
        "description" => "Tiles for the sub-categories inside the collections above - the subjects a visitor can actually browse. Only sub-categories that carry posts are listed, so a tile never opens an empty archive. The section hides itself when there are none.",
        "section"     => "vance_kblobby_topics",
        "type"        => "checkbox",
    ) );
    $wp_customize->add_setting( "vance_kblobby_topics_eyebrow", array( "default" => "By Topic", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_topics_eyebrow", array( "label" => "Eyebrow", "section" => "vance_kblobby_topics", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_topics_title", array( "default" => "Go straight to a subject", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_topics_title", array( "label" => "Title", "section" => "vance_kblobby_topics", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_topics_desc", array( "default" => "The collections above are whole shelves. These are the subjects inside them, so you can skip a step.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_kblobby_topics_desc", array( "label" => "Description", "section" => "vance_kblobby_topics", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_kblobby_topics_max", array( "default" => 8, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_kblobby_topics_max", array(
        "label"       => "Maximum tiles",
        "description" => "Busiest sub-categories first. 0 shows every one that has posts.",
        "section"     => "vance_kblobby_topics",
        "type"        => "number",
        "input_attrs" => array( "min" => 0, "max" => 24, "step" => 1 ),
    ) );

    // ---- Conditions ----
    $wp_customize->add_section( "vance_kblobby_cond", array( "title" => "Conditions Section", "panel" => "vance_kblobby_panel", "priority" => 172 ) );
    $wp_customize->add_setting( "vance_kblobby_cond_show", array( "default" => true, "sanitize_callback" => "vance_sanitize_checkbox" ) );
    $wp_customize->add_control( "vance_kblobby_cond_show", array(
        "label"       => "Show Section",
        "description" => "One tile per GI condition. The list is the same registry the homepage and the Gastro Health Explained hub use, so a condition added there appears here with no further work.",
        "section"     => "vance_kblobby_cond",
        "type"        => "checkbox",
    ) );
    $wp_customize->add_setting( "vance_kblobby_cond_eyebrow", array( "default" => "Conditions", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_cond_eyebrow", array( "label" => "Eyebrow", "section" => "vance_kblobby_cond", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_cond_title", array( "default" => "Start from your condition", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_cond_title", array( "label" => "Title", "section" => "vance_kblobby_cond", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_cond_desc", array( "default" => "Each condition has its own guide - what it is, how it is diagnosed, and what living with it actually involves.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_kblobby_cond_desc", array( "label" => "Description", "section" => "vance_kblobby_cond", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_kblobby_cond_link_text", array( "default" => "View all conditions", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_cond_link_text", array( "label" => "Link under the tiles", "description" => "Points at the Gastro Health Explained hub. Leave blank to hide it.", "section" => "vance_kblobby_cond", "type" => "text" ) );

    // ---- Tools ----
    $wp_customize->add_section( "vance_kblobby_tools", array( "title" => "Tools Section", "panel" => "vance_kblobby_panel", "priority" => 173 ) );
    $wp_customize->add_setting( "vance_kblobby_tools_show", array( "default" => true, "sanitize_callback" => "vance_sanitize_checkbox" ) );
    $wp_customize->add_control( "vance_kblobby_tools_show", array(
        "label"       => "Show Section",
        "description" => "The navy band. Like the collection cards, the tiles mirror the primary menu rather than being typed in here - so the nav and this page cannot disagree about what the Hub offers.",
        "section"     => "vance_kblobby_tools",
        "type"        => "checkbox",
    ) );
    $wp_customize->add_setting( "vance_kblobby_tools_eyebrow", array( "default" => "Free Tools", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_tools_eyebrow", array( "label" => "Eyebrow", "section" => "vance_kblobby_tools", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_tools_title", array( "default" => "Turn the evidence into a number", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_tools_title", array( "label" => "Title", "section" => "vance_kblobby_tools", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_tools_desc", array( "default" => "The Hub is not only reading. These are free to use with no account, and you can save every result to a private dashboard once you have one.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_kblobby_tools_desc", array( "label" => "Description", "section" => "vance_kblobby_tools", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_kblobby_tools_cta", array( "default" => "Open", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_tools_cta", array( "label" => "Tile link text", "section" => "vance_kblobby_tools", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_tools_menu_label", array( "default" => "Free Health Tools", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_tools_menu_label", array(
        "label"       => "Menu item label",
        "description" => "Which item in the primary menu supplies the tool tiles - its children become the tiles. Matching ignores case, spaces and hyphens. If no match is found the section falls back to the three calculators the Hub ships.",
        "section"     => "vance_kblobby_tools",
        "type"        => "text",
    ) );
    $wp_customize->add_setting( "vance_kblobby_tools_with_ai", array( "default" => true, "sanitize_callback" => "vance_sanitize_checkbox" ) );
    $wp_customize->add_control( "vance_kblobby_tools_with_ai", array(
        "label"       => "Add an Ask AI tile",
        "description" => "Ask AI sits in the menu's CTA banner rather than the tools column, so it is never picked up by the label above. Ticked, it is appended last - and only if the Ask AI page is published.",
        "section"     => "vance_kblobby_tools",
        "type"        => "checkbox",
    ) );

    // ---- Latest ----
    $wp_customize->add_section( "vance_kblobby_latest", array( "title" => "Latest Articles Section", "panel" => "vance_kblobby_panel", "priority" => 174 ) );
    $wp_customize->add_setting( "vance_kblobby_latest_show", array( "default" => true, "sanitize_callback" => "vance_sanitize_checkbox" ) );
    $wp_customize->add_control( "vance_kblobby_latest_show", array(
        "label"       => "Show Section",
        "description" => "The newest posts from every collection at once. The per-card previews above answer \"what is on this shelf\"; this answers \"what is new\", which is the other reason someone opens this page.",
        "section"     => "vance_kblobby_latest",
        "type"        => "checkbox",
    ) );
    $wp_customize->add_setting( "vance_kblobby_latest_eyebrow", array( "default" => "Just Published", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_latest_eyebrow", array( "label" => "Eyebrow", "section" => "vance_kblobby_latest", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_latest_title", array( "default" => "Newest across the library", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_kblobby_latest_title", array( "label" => "Title", "section" => "vance_kblobby_latest", "type" => "text" ) );
    $wp_customize->add_setting( "vance_kblobby_latest_desc", array( "default" => "The most recent additions, whichever collection they landed in.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_kblobby_latest_desc", array( "label" => "Description", "section" => "vance_kblobby_latest", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_kblobby_latest_count", array( "default" => 4, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_kblobby_latest_count", array(
        "label"       => "How many articles",
        "description" => "1-8. Four fills one row at desktop width.",
        "section"     => "vance_kblobby_latest",
        "type"        => "number",
        "input_attrs" => array( "min" => 1, "max" => 8, "step" => 1 ),
    ) );

    // ============================================================
    // SEARCH RESULTS PAGE (search.php)
    // The hero, the refine-search form and the no-results state. Some copy
    // fields take tokens the template substitutes at render time:
    //   {query} - what the visitor searched for (escaped before insertion)
    //   {count} - how many results were found
    // Anything else is literal.
    // ============================================================
    $wp_customize->add_panel( "vance_search_panel", array(
        "title"    => __( "Page - Search Results", "vance-health-hub" ),
        "priority" => 49,
    ) );

    // ---- Hero ----
    $wp_customize->add_section( "vance_search_hero", array( "title" => "Hero Section", "panel" => "vance_search_panel" ) );

    $wp_customize->add_setting( "vance_search_hero_tag", array( "default" => "Search Results", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_search_hero_tag", array( "label" => "Tag Label", "description" => "Small pill above the heading. Leave blank to hide it.", "section" => "vance_search_hero", "type" => "text" ) );

    $wp_customize->add_setting( "vance_search_hero_title", array( "default" => "Results for &#8220;{query}&#8221;", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_search_hero_title", array( "label" => "Title (HTML allowed)", "description" => "Shown when the visitor searched for something. Use {query} for the search term.", "section" => "vance_search_hero", "type" => "textarea" ) );

    $wp_customize->add_setting( "vance_search_hero_title_empty", array( "default" => "Search the Hub", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_search_hero_title_empty", array( "label" => "Title when the search box was empty", "section" => "vance_search_hero", "type" => "text" ) );

    $wp_customize->add_setting( "vance_search_hero_count_one", array( "default" => "{count} matching item.", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_search_hero_count_one", array( "label" => "Result count - one result", "description" => "Use {count} for the number. Leave blank to hide the line.", "section" => "vance_search_hero", "type" => "text" ) );

    $wp_customize->add_setting( "vance_search_hero_count_many", array( "default" => "{count} matching items.", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_search_hero_count_many", array( "label" => "Result count - none or many", "description" => "Use {count} for the number. Leave blank to hide the line.", "section" => "vance_search_hero", "type" => "text" ) );

    $wp_customize->add_setting( "vance_search_hero_bg", array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_search_hero_bg", array( "label" => "Hero Background Image", "description" => "Falls back to the global Category Hero image, then to the bundled news hero.", "section" => "vance_search_hero" ) ) );

    $wp_customize->add_setting( "vance_search_hero_overlay", array( "default" => 72, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_search_hero_overlay", array( "label" => "Hero Overlay Opacity (%)", "section" => "vance_search_hero", "type" => "number", "input_attrs" => array( "min" => 0, "max" => 100, "step" => 5 ) ) );

    $wp_customize->add_setting( "vance_search_hero_height", array( "default" => 300, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_search_hero_height", array( "label" => "Hero Height (px)", "section" => "vance_search_hero", "type" => "number", "input_attrs" => array( "min" => 180, "max" => 600, "step" => 10 ) ) );

    $wp_customize->add_setting( "vance_search_hero_title_color", array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_search_hero_title_color", array( "label" => "Title Colour", "section" => "vance_search_hero" ) ) );

    $wp_customize->add_setting( "vance_search_hero_title_size", array( "default" => 42, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_search_hero_title_size", array( "label" => "Title Size (px, desktop)", "description" => "Phones step down to 28px regardless.", "section" => "vance_search_hero", "type" => "number", "input_attrs" => array( "min" => 20, "max" => 72, "step" => 2 ) ) );

    // ---- Refine form ----
    $wp_customize->add_section( "vance_search_form", array( "title" => "Refine Search Form", "panel" => "vance_search_panel" ) );

    $wp_customize->add_setting( "vance_search_form_show", array( "default" => true, "sanitize_callback" => "vance_sanitize_checkbox" ) );
    $wp_customize->add_control( "vance_search_form_show", array( "label" => "Show the search box in the hero", "section" => "vance_search_form", "type" => "checkbox" ) );

    $wp_customize->add_setting( "vance_search_form_placeholder", array( "default" => "Refine your search...", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_search_form_placeholder", array( "label" => "Field Placeholder", "section" => "vance_search_form", "type" => "text" ) );

    $wp_customize->add_setting( "vance_search_form_button", array( "default" => "Search", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_search_form_button", array( "label" => "Button Label", "section" => "vance_search_form", "type" => "text" ) );

    $wp_customize->add_setting( "vance_search_form_btn_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_search_form_btn_bg", array( "label" => "Button Background", "description" => "Blank keeps the theme teal.", "section" => "vance_search_form" ) ) );

    $wp_customize->add_setting( "vance_search_form_btn_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_search_form_btn_color", array( "label" => "Button Text Colour", "description" => "Blank keeps white.", "section" => "vance_search_form" ) ) );

    // ---- No results ----
    $wp_customize->add_section( "vance_search_empty", array( "title" => "No Results State", "panel" => "vance_search_panel" ) );

    $wp_customize->add_setting( "vance_search_empty_title", array( "default" => "Nothing matched that search", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_search_empty_title", array( "label" => "Heading", "section" => "vance_search_empty", "type" => "text" ) );

    $wp_customize->add_setting( "vance_search_empty_desc", array( "default" => "Try a broader term, check the spelling, or start from one of these:", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_search_empty_desc", array( "label" => "Message", "section" => "vance_search_empty", "type" => "textarea" ) );

    $wp_customize->add_setting( "vance_search_empty_show_cats", array( "default" => true, "sanitize_callback" => "vance_sanitize_checkbox" ) );
    $wp_customize->add_control( "vance_search_empty_show_cats", array( "label" => "Show suggested category links", "section" => "vance_search_empty", "type" => "checkbox" ) );

    $wp_customize->add_setting( "vance_search_empty_cat_count", array( "default" => 6, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_search_empty_cat_count", array( "label" => "How many category links", "section" => "vance_search_empty", "type" => "number", "input_attrs" => array( "min" => 1, "max" => 12, "step" => 1 ) ) );

    // ============================================================
    // NOT FOUND (404) PANEL
    // ============================================================
    // The 404 has no settings of its own and never has -- 404.php hard-coded
    // its words. This panel exists so the spotlight hero registered below has
    // somewhere to put its section: a section whose panel does not exist is
    // dropped by WordPress without a warning, which is exactly the silent
    // failure the hero rollout handover warns about.
    //
    // It is deliberately empty apart from that section. The 404's copy stays
    // in inc/page-hero-spotlight.php; what an admin gets here is the
    // photograph, the colours, the buttons and the card.
    $wp_customize->add_panel( "vance_e404_panel", array(
        "title"       => __( "Page - Not Found (404)", "vance-health-hub" ),
        "description" => __( "The screen a visitor sees when a link is dead or an address is mistyped.", "vance-health-hub" ),
        "priority"    => 50,
    ) );

    /* ---- Contact / About "Spotlight" heroes ----
       Registered here rather than in the file that renders them because their
       sections hang off the Contact and About panels, which are built above.
       Adds a design toggle to each page's existing Hero Section (defaulting to
       the classic dark hero, so nothing changes until an admin switches it)
       plus one section per page for the spotlight's own settings. */
    if ( function_exists( 'vance_page_hero_spotlight_customize' ) ) {
        vance_page_hero_spotlight_customize( $wp_customize );
    }

    /* ---- "Our Heritage" is retired ----------------------------------------
       Its panel used to be registered above and then removed again right here,
       which is why ~200 settings existed that no admin could ever see. The
       registration is now gone outright, page-our-heritage.php is deleted, the
       WP Page is trashed and /our-heritage/ 301s to /about/ via
       inc/retired-redirects.php.

       The saved `vance_heritage_*` theme mods are deliberately still in the
       database. Nothing reads them, they cost nothing, and they are the only
       remaining copy of what that page said. ---------------------------------- */
}
add_action( 'customize_register', 'vance_pages_customize_register', 20 );
