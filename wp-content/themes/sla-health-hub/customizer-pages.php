<?php
// Included in functions.php
function vance_pages_customize_register( $wp_customize ) {
    // ---- HCP PAGE PANEL ----
    $wp_customize->add_panel( "vance_hcp_panel", array(
        "title"    => __( "HCP Page Settings", "sla-health-hub" ),
        "priority" => 46,
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
        "title"    => __( "Patient Page Settings", "sla-health-hub" ),
        "priority" => 47,
    ) );

    // Patient Hero
    $wp_customize->add_section( "vance_pat_hero", array( "title" => "Hero Section", "panel" => "vance_pat_panel" ) );
    $wp_customize->add_setting( "vance_pat_hero_tag", array( "default" => "Patient Portal", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_pat_hero_tag", array( "label" => "Tag Label", "section" => "vance_pat_hero", "type" => "text" ) );
    
    $wp_customize->add_setting( "vance_pat_hero_title", array( "default" => "Empowering Your <span class=\"highlight\">Wellness Journey</span>", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_pat_hero_title", array( "label" => "Title", "section" => "vance_pat_hero", "type" => "textarea" ) );
    
    $wp_customize->add_setting( "vance_pat_hero_desc", array( "default" => "More than just a news site—a truly useful platform providing the highest quality clinical information, innovative tools, and expert opinions to help you explore and manage your gastro healthcare concerns.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_pat_hero_desc", array( "label" => "Description", "section" => "vance_pat_hero", "type" => "textarea" ) );
    
    $wp_customize->add_setting("vance_pat_hero_bg", array("default"=>"","sanitize_callback"=>"esc_url_raw"));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, "vance_pat_hero_bg", array("label"=>"Hero Background Image","section"=>"vance_pat_hero")));

    // Patient Benefits
    $wp_customize->add_section( "vance_pat_benefits", array( "title" => "Benefits Section", "panel" => "vance_pat_panel" ) );
    $wp_customize->add_setting( "vance_pat_ben_tag", array( "default" => "Why Choose Vance Medical?", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_pat_ben_tag", array( "label" => "Tag Label", "section" => "vance_pat_benefits", "type" => "text" ) );
    
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
        1 => array("Ask Vance-i Expert", "Interact with our AI intelligence trained specifically in clinical gastro conditions for instant, reliable answers to your health questions."),
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
        "title"    => __( "About Us Page", "sla-health-hub" ),
        "priority" => 48,
    ) );

    // ── Hero ──────────────────────────────────────────────────
    $wp_customize->add_section( "vance_about_hero", array( "title" => "Hero Section", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_hero_tag",   array( "default" => "Our Story", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_hero_tag",   array( "label" => "Tag Label", "section" => "vance_about_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_hero_title", array( "default" => "From Pharma to <span class=\"highlight\">Healthcare</span>", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_about_hero_title", array( "label" => "Title (HTML allowed)", "section" => "vance_about_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_about_hero_sub",   array( "default" => "A Natural Evolution in Gastrointestinal Care", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_hero_sub",   array( "label" => "Sub-title (italic)", "section" => "vance_about_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_hero_desc",  array( "default" => "Vance Medical bridges the worlds of pharmaceutical science and patient-centred nutrition, delivering evidence-based medical food solutions for life with IBD.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_about_hero_desc",  array( "label" => "Description", "section" => "vance_about_hero", "type" => "textarea" ) );
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


    // ── Origin / Pillars ──────────────────────────────────────
    $wp_customize->add_section( "vance_about_origin", array( "title" => "Origin Section", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_origin_tag",   array( "default" => "From Pharma to Healthcare", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_origin_tag",   array( "label" => "Section Tag", "section" => "vance_about_origin", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_origin_title", array( "default" => "From Pharma to Healthcare", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_origin_title", array( "label" => "Heading", "section" => "vance_about_origin", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_origin_sub",   array( "default" => "A Natural Evolution in Gastrointestinal Care", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_origin_sub",   array( "label" => "Sub-heading", "section" => "vance_about_origin", "type" => "text" ) );

    $pillar_defaults = array(
        1 => array( "Heritage in Pharma",       "SLA Pharma has a long record of developing specialised gastrointestinal medicines under rigorous regulatory standards." ),
        2 => array( "Patient-Centric Innovation","We found that medicines alone often fall short for chronic IBD. There is a clear need for evidence-based nutritional support." ),
        3 => array( "The Birth of Vance Medical",  "Vance Medical bridges pharma and nutrition, delivering \"pharma-grade\" medical food products like EPAVANCE." ),
    );
    for ( $i = 1; $i <= 3; $i++ ) {
        $wp_customize->add_setting( "vance_about_p{$i}_title", array( "default" => $pillar_defaults[$i][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_about_p{$i}_title", array( "label" => "Pillar $i Title", "section" => "vance_about_origin", "type" => "text" ) );
        $wp_customize->add_setting( "vance_about_p{$i}_desc",  array( "default" => $pillar_defaults[$i][1], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( "vance_about_p{$i}_desc",  array( "label" => "Pillar $i Description", "section" => "vance_about_origin", "type" => "textarea" ) );
    }
    // Stats
    $stat_defaults = array(
        1 => array( "25+",    "Years of Experience" ),
        2 => array( "Global", "Regulatory Reach" ),
        3 => array( "100%",   "Pharma-Grade Standards" ),
    );
    for ( $i = 1; $i <= 3; $i++ ) {
        $wp_customize->add_setting( "vance_about_stat{$i}_num",   array( "default" => $stat_defaults[$i][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_about_stat{$i}_num",   array( "label" => "Stat $i Number", "section" => "vance_about_origin", "type" => "text" ) );
        $wp_customize->add_setting( "vance_about_stat{$i}_label", array( "default" => $stat_defaults[$i][1], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_about_stat{$i}_label", array( "label" => "Stat $i Label", "section" => "vance_about_origin", "type" => "text" ) );
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
        1 => array( "Evidence-Based",  "Every product and piece of content we produce meets the highest scientific and regulatory standards, rooted in peer-reviewed clinical research." ),
        2 => array( "Patient-First",   "We design every solution around the real-world challenges that patients face — not just clinical endpoints — because lived experience matters." ),
        3 => array( "Pharma-Grade",    "Our medical food products are developed with the same rigour applied to licensed medicines — providing a quality benchmark no ordinary supplement can match." ),
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

    // ── EPAVANCE Product Spotlight ────────────────────────────
    $wp_customize->add_section( "vance_about_product", array( "title" => "EPAVANCE Spotlight", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_prod_tag",       array( "default" => "Our Flagship Product", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_prod_tag",       array( "label" => "Section Tag", "section" => "vance_about_product", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_prod_title",     array( "default" => "Introducing EPAVANCE", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_prod_title",     array( "label" => "Title", "section" => "vance_about_product", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_prod_desc",      array( "default" => "EPAVANCE is a pharma-grade Omega-3 medical food especially formulated for patients with Inflammatory Bowel Disease. Unlike generic supplements, EPAVANCE is developed under the same rigorous manufacturing standards applied to licensed medicines.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_about_prod_desc",      array( "label" => "Description", "section" => "vance_about_product", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_about_prod_btn",       array( "default" => "Learn More About EPAVANCE", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_prod_btn",       array( "label" => "Button Label", "section" => "vance_about_product", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_prod_url",       array( "default" => "#", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_about_prod_url",       array( "label" => "Button URL", "section" => "vance_about_product", "type" => "url" ) );

    $feat_defaults = array(
        1 => array( "Pharma-Grade Manufacturing", "Produced under strict pharmaceutical cGMP standards — the highest tier of quality assurance in the industry." ),
        2 => array( "Clinically Researched",      "Supported by clinical evidence demonstrating meaningful benefit for IBD patients managing their nutritional needs." ),
        3 => array( "High-Dose EPA Omega-3",       "A precisely calibrated dose of EPA matched to the needs of IBD-associated gut inflammation." ),
        4 => array( "Regulatory Status",           "Classified as a Medical Food (FSMP), enabling it to occupy a unique, trusted position between medication and nutrition." ),
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

    // ── Platform Section ──────────────────────────────────────
    $wp_customize->add_section( "vance_about_platform", array( "title" => "Platform Section", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_plat_tag",   array( "default" => "The Digital Layer", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_plat_tag",   array( "label" => "Section Tag", "section" => "vance_about_platform", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_plat_title", array( "default" => "The Vance Medical Platform", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_plat_title", array( "label" => "Heading", "section" => "vance_about_platform", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_plat_desc",  array( "default" => "Beyond our medical food products, Vance Medical is building a world-class digital health hub - combining clinical-grade content, AI-powered tools, and a vibrant community for patients and healthcare professionals.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_about_plat_desc",  array( "label" => "Description", "section" => "vance_about_platform", "type" => "textarea" ) );

    $plat_defaults = array(
        1 => array( "Clinical Content Hub",    "Peer-reviewed research, expert opinions, and patient education curated by gastroenterologists and dietitians." ),
        2 => array( "Vance-i AI Assistant",      "A specialised AI trained on clinical gastroenterology to answer your health questions with precision and safety." ),
        3 => array( "Patient Dashboard",       "A secure personal portal to track health records, manage your IBD tools, and connect with your care pathway." ),
        4 => array( "HCP Professional Portal", "A dedicated space for healthcare practitioners to access protocols, CME, and collaborate with Vance experts." ),
        5 => array( "Health Calculators",      "Evidence-based clinical calculators for malnutrition screening, BMI, and disease activity scoring." ),
        6 => array( "Education Courses",       "Multi-chapter learning pathways developed by gastro specialists for both patients and clinicians." ),
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
    $wp_customize->add_setting( "vance_about_cta_title",      array( "default" => "Join the Vance Medical Community", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_cta_title",      array( "label" => "Heading", "section" => "vance_about_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_cta_desc",       array( "default" => "Whether you're a patient managing IBD, a clinician advancing your practice, or a researcher exploring gut health - there's a place for you at Vance Medical.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_about_cta_desc",       array( "label" => "Description", "section" => "vance_about_cta", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_about_cta_btn1_label", array( "default" => "I'm a Patient", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_cta_btn1_label", array( "label" => "Button 1 Label", "section" => "vance_about_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_cta_btn1_url",   array( "default" => "/patients/", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_about_cta_btn1_url",   array( "label" => "Button 1 URL", "section" => "vance_about_cta", "type" => "url" ) );
    $wp_customize->add_setting( "vance_about_cta_btn2_label", array( "default" => "I'm a Healthcare Professional", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_cta_btn2_label", array( "label" => "Button 2 Label", "section" => "vance_about_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_cta_btn2_url",   array( "default" => "/healthcare-professionals/", "sanitize_callback" => "esc_url_raw" ) );
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

    // ---- OUR HERITAGE PAGE PANEL ----
    $wp_customize->add_panel( "vance_heritage_panel", array(
        "title"    => __( "Our Heritage Page", "sla-health-hub" ),
        "priority" => 49,
    ) );

    // ── Hero ──────────────────────────────────────────────────
    $wp_customize->add_section( "vance_heritage_hero", array( "title" => "Hero Section", "panel" => "vance_heritage_panel" ) );
    $wp_customize->add_setting( "vance_heritage_hero_tag",   array( "default" => "Our Story", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_hero_tag",   array( "label" => "Tag Label", "section" => "vance_heritage_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_hero_title", array( "default" => "From Pharma to <span class=\"highlight\">Healthcare</span>", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_heritage_hero_title", array( "label" => "Title (HTML allowed)", "section" => "vance_heritage_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_heritage_hero_sub",   array( "default" => "A Natural Evolution in Gastrointestinal Care", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_hero_sub",   array( "label" => "Sub-title (italic)", "section" => "vance_heritage_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_hero_desc",  array( "default" => "Vance Medical bridges the worlds of pharmaceutical science and patient-centred nutrition, delivering evidence-based medical food solutions for life with IBD.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_heritage_hero_desc",  array( "label" => "Description", "section" => "vance_heritage_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_heritage_hero_img",    array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_heritage_hero_img", array( "label" => "Hero Background Image", "section" => "vance_heritage_hero" ) ) );
    // Styles for Hero Section
    $wp_customize->add_setting( "vance_heritage_hero_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_heritage_hero_show", array( "label" => "Show Section", "section" => "vance_heritage_hero", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_heritage_hero_bg_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_hero_bg_color", array( "label" => "Hero Background Colour (overrides image)", "section" => "vance_heritage_hero" ) ) );
    $wp_customize->add_setting( "vance_heritage_hero_tag_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_hero_tag_bg", array( "label" => "Tag Label Background Colour", "section" => "vance_heritage_hero" ) ) );
    $wp_customize->add_setting( "vance_heritage_hero_tag_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_hero_tag_color", array( "label" => "Tag Label Font Colour", "section" => "vance_heritage_hero" ) ) );
    $wp_customize->add_setting( "vance_heritage_hero_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_hero_title_color", array( "label" => "Title Colour", "section" => "vance_heritage_hero" ) ) );
    $wp_customize->add_setting( "vance_heritage_hero_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_hero_title_size", array( "label" => "Title Font Size (e.g. 48px)", "section" => "vance_heritage_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_hero_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_hero_text_color", array( "label" => "Description Text Colour", "section" => "vance_heritage_hero" ) ) );
    $wp_customize->add_setting( "vance_heritage_hero_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_hero_text_size", array( "label" => "Description Font Size (e.g. 20px)", "section" => "vance_heritage_hero", "type" => "text" ) );


    // ── Origin / Pillars ──────────────────────────────────────
    $wp_customize->add_section( "vance_heritage_origin", array( "title" => "Origin Section", "panel" => "vance_heritage_panel" ) );
    $wp_customize->add_setting( "vance_heritage_origin_tag",   array( "default" => "From Pharma to Healthcare", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_origin_tag",   array( "label" => "Section Tag", "section" => "vance_heritage_origin", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_origin_title", array( "default" => "From Pharma to Healthcare", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_origin_title", array( "label" => "Heading", "section" => "vance_heritage_origin", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_origin_sub",   array( "default" => "A Natural Evolution in Gastrointestinal Care", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_origin_sub",   array( "label" => "Sub-heading", "section" => "vance_heritage_origin", "type" => "text" ) );

    $pillar_defaults = array(
        1 => array( "Heritage in Pharma",       "SLA Pharma has a long record of developing specialised gastrointestinal medicines under rigorous regulatory standards." ),
        2 => array( "Patient-Centric Innovation","We found that medicines alone often fall short for chronic IBD. There is a clear need for evidence-based nutritional support." ),
        3 => array( "The Birth of Vance Medical",  "Vance Medical bridges pharma and nutrition, delivering \"pharma-grade\" medical food products like EPAVANCE." ),
    );
    for ( $i = 1; $i <= 3; $i++ ) {
        $wp_customize->add_setting( "vance_heritage_p{$i}_title", array( "default" => $pillar_defaults[$i][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_heritage_p{$i}_title", array( "label" => "Pillar $i Title", "section" => "vance_heritage_origin", "type" => "text" ) );
        $wp_customize->add_setting( "vance_heritage_p{$i}_desc",  array( "default" => $pillar_defaults[$i][1], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( "vance_heritage_p{$i}_desc",  array( "label" => "Pillar $i Description", "section" => "vance_heritage_origin", "type" => "textarea" ) );
    }
    // Stats
    $stat_defaults = array(
        1 => array( "25+",    "Years of Experience" ),
        2 => array( "Global", "Regulatory Reach" ),
        3 => array( "100%",   "Pharma-Grade Standards" ),
    );
    for ( $i = 1; $i <= 3; $i++ ) {
        $wp_customize->add_setting( "vance_heritage_stat{$i}_num",   array( "default" => $stat_defaults[$i][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_heritage_stat{$i}_num",   array( "label" => "Stat $i Number", "section" => "vance_heritage_origin", "type" => "text" ) );
        $wp_customize->add_setting( "vance_heritage_stat{$i}_label", array( "default" => $stat_defaults[$i][1], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_heritage_stat{$i}_label", array( "label" => "Stat $i Label", "section" => "vance_heritage_origin", "type" => "text" ) );
    // Styles for Origin Section
    $wp_customize->add_setting( "vance_heritage_origin_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_heritage_origin_show", array( "label" => "Show Section", "section" => "vance_heritage_origin", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_heritage_origin_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_origin_bg", array( "label" => "Background Colour", "section" => "vance_heritage_origin" ) ) );
    $wp_customize->add_setting( "vance_heritage_origin_tag_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_origin_tag_bg", array( "label" => "Tag Label Background Colour", "section" => "vance_heritage_origin" ) ) );
    $wp_customize->add_setting( "vance_heritage_origin_tag_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_origin_tag_color", array( "label" => "Tag Label Font Colour", "section" => "vance_heritage_origin" ) ) );
    $wp_customize->add_setting( "vance_heritage_origin_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_origin_title_color", array( "label" => "Title Colour", "section" => "vance_heritage_origin" ) ) );
    $wp_customize->add_setting( "vance_heritage_origin_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_origin_title_size", array( "label" => "Title Font Size (e.g. 40px)", "section" => "vance_heritage_origin", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_origin_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_origin_text_color", array( "label" => "Text Colour", "section" => "vance_heritage_origin" ) ) );
    $wp_customize->add_setting( "vance_heritage_origin_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_origin_text_size", array( "label" => "Text Font Size (e.g. 18px)", "section" => "vance_heritage_origin", "type" => "text" ) );

    }

    // ── Mission & Values ──────────────────────────────────────
    $wp_customize->add_section( "vance_heritage_mission", array( "title" => "Mission & Values", "panel" => "vance_heritage_panel" ) );
    $wp_customize->add_setting( "vance_heritage_mission_tag",   array( "default" => "Our Mission", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_mission_tag",   array( "label" => "Section Tag", "section" => "vance_heritage_mission", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_mission_title", array( "default" => "Bridging Science & <span class=\"highlight\">Patient Wellbeing</span>", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_heritage_mission_title", array( "label" => "Heading (HTML allowed)", "section" => "vance_heritage_mission", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_heritage_mission_desc",  array( "default" => "At Vance Medical, our mission is to empower patients living with chronic gastrointestinal conditions by making world-class clinical nutrition science accessible, actionable, and personal.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_heritage_mission_desc",  array( "label" => "Description", "section" => "vance_heritage_mission", "type" => "textarea" ) );

    $val_defaults = array(
        1 => array( "Evidence-Based",  "Every product and piece of content we produce meets the highest scientific and regulatory standards, rooted in peer-reviewed clinical research." ),
        2 => array( "Patient-First",   "We design every solution around the real-world challenges that patients face — not just clinical endpoints — because lived experience matters." ),
        3 => array( "Pharma-Grade",    "Our medical food products are developed with the same rigour applied to licensed medicines — providing a quality benchmark no ordinary supplement can match." ),
        4 => array( "Global Reach",    "With a regulatory footprint spanning multiple continents, Vance Medical delivers consistent, trusted solutions wherever patients and clinicians need them." ),
    );
    for ( $i = 1; $i <= 4; $i++ ) {
        $wp_customize->add_setting( "vance_heritage_val{$i}_title", array( "default" => $val_defaults[$i][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_heritage_val{$i}_title", array( "label" => "Value $i Title", "section" => "vance_heritage_mission", "type" => "text" ) );
        $wp_customize->add_setting( "vance_heritage_val{$i}_desc",  array( "default" => $val_defaults[$i][1], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( "vance_heritage_val{$i}_desc",  array( "label" => "Value $i Description", "section" => "vance_heritage_mission", "type" => "textarea" ) );
    // Styles for Mission & Values
    $wp_customize->add_setting( "vance_heritage_mission_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_heritage_mission_show", array( "label" => "Show Section", "section" => "vance_heritage_mission", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_heritage_mission_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_mission_bg", array( "label" => "Background Colour", "section" => "vance_heritage_mission" ) ) );
    $wp_customize->add_setting( "vance_heritage_mission_tag_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_mission_tag_bg", array( "label" => "Tag Label Background Colour", "section" => "vance_heritage_mission" ) ) );
    $wp_customize->add_setting( "vance_heritage_mission_tag_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_mission_tag_color", array( "label" => "Tag Label Font Colour", "section" => "vance_heritage_mission" ) ) );
    $wp_customize->add_setting( "vance_heritage_mission_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_mission_title_color", array( "label" => "Title Colour", "section" => "vance_heritage_mission" ) ) );
    $wp_customize->add_setting( "vance_heritage_mission_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_mission_title_size", array( "label" => "Title Font Size (e.g. 40px)", "section" => "vance_heritage_mission", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_mission_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_mission_text_color", array( "label" => "Text Colour", "section" => "vance_heritage_mission" ) ) );
    $wp_customize->add_setting( "vance_heritage_mission_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_mission_text_size", array( "label" => "Text Font Size (e.g. 18px)", "section" => "vance_heritage_mission", "type" => "text" ) );

    }

    // ── EPAVANCE Product Spotlight ────────────────────────────
    $wp_customize->add_section( "vance_heritage_product", array( "title" => "EPAVANCE Spotlight", "panel" => "vance_heritage_panel" ) );
    $wp_customize->add_setting( "vance_heritage_prod_tag",       array( "default" => "Our Flagship Product", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_prod_tag",       array( "label" => "Section Tag", "section" => "vance_heritage_product", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_prod_title",     array( "default" => "Introducing EPAVANCE", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_prod_title",     array( "label" => "Title", "section" => "vance_heritage_product", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_prod_desc",      array( "default" => "EPAVANCE is a pharma-grade Omega-3 medical food especially formulated for patients with Inflammatory Bowel Disease. Unlike generic supplements, EPAVANCE is developed under the same rigorous manufacturing standards applied to licensed medicines.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_heritage_prod_desc",      array( "label" => "Description", "section" => "vance_heritage_product", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_heritage_prod_btn",       array( "default" => "Learn More About EPAVANCE", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_prod_btn",       array( "label" => "Button Label", "section" => "vance_heritage_product", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_prod_url",       array( "default" => "#", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_heritage_prod_url",       array( "label" => "Button URL", "section" => "vance_heritage_product", "type" => "url" ) );

    $feat_defaults = array(
        1 => array( "Pharma-Grade Manufacturing", "Produced under strict pharmaceutical cGMP standards — the highest tier of quality assurance in the industry." ),
        2 => array( "Clinically Researched",      "Supported by clinical evidence demonstrating meaningful benefit for IBD patients managing their nutritional needs." ),
        3 => array( "High-Dose EPA Omega-3",       "A precisely calibrated dose of EPA matched to the needs of IBD-associated gut inflammation." ),
        4 => array( "Regulatory Status",           "Classified as a Medical Food (FSMP), enabling it to occupy a unique, trusted position between medication and nutrition." ),
    );
    for ( $i = 1; $i <= 4; $i++ ) {
        $wp_customize->add_setting( "vance_heritage_feat{$i}_title", array( "default" => $feat_defaults[$i][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_heritage_feat{$i}_title", array( "label" => "Feature $i Title", "section" => "vance_heritage_product", "type" => "text" ) );
        $wp_customize->add_setting( "vance_heritage_feat{$i}_desc",  array( "default" => $feat_defaults[$i][1], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( "vance_heritage_feat{$i}_desc",  array( "label" => "Feature $i Description", "section" => "vance_heritage_product", "type" => "textarea" ) );
    // Styles for EPAVANCE Spotlight
    $wp_customize->add_setting( "vance_heritage_product_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_heritage_product_show", array( "label" => "Show Section", "section" => "vance_heritage_product", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_heritage_product_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_product_bg", array( "label" => "Background Colour", "section" => "vance_heritage_product" ) ) );
    $wp_customize->add_setting( "vance_heritage_product_tag_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_product_tag_bg", array( "label" => "Tag Label Background Colour", "section" => "vance_heritage_product" ) ) );
    $wp_customize->add_setting( "vance_heritage_product_tag_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_product_tag_color", array( "label" => "Tag Label Font Colour", "section" => "vance_heritage_product" ) ) );
    $wp_customize->add_setting( "vance_heritage_product_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_product_title_color", array( "label" => "Title Colour", "section" => "vance_heritage_product" ) ) );
    $wp_customize->add_setting( "vance_heritage_product_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_product_title_size", array( "label" => "Title Font Size (e.g. 40px)", "section" => "vance_heritage_product", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_product_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_product_text_color", array( "label" => "Text Colour", "section" => "vance_heritage_product" ) ) );
    $wp_customize->add_setting( "vance_heritage_product_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_product_text_size", array( "label" => "Text Font Size (e.g. 18px)", "section" => "vance_heritage_product", "type" => "text" ) );

    }

    // ── Platform Section ──────────────────────────────────────
    $wp_customize->add_section( "vance_heritage_platform", array( "title" => "Platform Section", "panel" => "vance_heritage_panel" ) );
    $wp_customize->add_setting( "vance_heritage_plat_tag",   array( "default" => "The Digital Layer", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_plat_tag",   array( "label" => "Section Tag", "section" => "vance_heritage_platform", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_plat_title", array( "default" => "The Vance Medical Platform", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_plat_title", array( "label" => "Heading", "section" => "vance_heritage_platform", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_plat_desc",  array( "default" => "Beyond our medical food products, Vance Medical is building a world-class digital health hub - combining clinical-grade content, AI-powered tools, and a vibrant community for patients and healthcare professionals.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_heritage_plat_desc",  array( "label" => "Description", "section" => "vance_heritage_platform", "type" => "textarea" ) );

    $plat_defaults = array(
        1 => array( "Clinical Content Hub",    "Peer-reviewed research, expert opinions, and patient education curated by gastroenterologists and dietitians." ),
        2 => array( "Vance-i AI Assistant",      "A specialised AI trained on clinical gastroenterology to answer your health questions with precision and safety." ),
        3 => array( "Patient Dashboard",       "A secure personal portal to track health records, manage your IBD tools, and connect with your care pathway." ),
        4 => array( "HCP Professional Portal", "A dedicated space for healthcare practitioners to access protocols, CME, and collaborate with Vance experts." ),
        5 => array( "Health Calculators",      "Evidence-based clinical calculators for malnutrition screening, BMI, and disease activity scoring." ),
        6 => array( "Education Courses",       "Multi-chapter learning pathways developed by gastro specialists for both patients and clinicians." ),
    );
    for ( $i = 1; $i <= 6; $i++ ) {
        $wp_customize->add_setting( "vance_heritage_plat{$i}_title", array( "default" => $plat_defaults[$i][0], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( "vance_heritage_plat{$i}_title", array( "label" => "Platform Item $i Title", "section" => "vance_heritage_platform", "type" => "text" ) );
        $wp_customize->add_setting( "vance_heritage_plat{$i}_desc",  array( "default" => $plat_defaults[$i][1], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( "vance_heritage_plat{$i}_desc",  array( "label" => "Platform Item $i Description", "section" => "vance_heritage_platform", "type" => "textarea" ) );
    // Styles for Platform Section
    $wp_customize->add_setting( "vance_heritage_platform_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_heritage_platform_show", array( "label" => "Show Section", "section" => "vance_heritage_platform", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_heritage_platform_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_platform_bg", array( "label" => "Background Colour", "section" => "vance_heritage_platform" ) ) );
    $wp_customize->add_setting( "vance_heritage_platform_tag_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_platform_tag_bg", array( "label" => "Tag Label Background Colour", "section" => "vance_heritage_platform" ) ) );
    $wp_customize->add_setting( "vance_heritage_platform_tag_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_platform_tag_color", array( "label" => "Tag Label Font Colour", "section" => "vance_heritage_platform" ) ) );
    $wp_customize->add_setting( "vance_heritage_platform_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_platform_title_color", array( "label" => "Title Colour", "section" => "vance_heritage_platform" ) ) );
    $wp_customize->add_setting( "vance_heritage_platform_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_platform_title_size", array( "label" => "Title Font Size (e.g. 40px)", "section" => "vance_heritage_platform", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_platform_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_platform_text_color", array( "label" => "Text Colour", "section" => "vance_heritage_platform" ) ) );
    $wp_customize->add_setting( "vance_heritage_platform_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_platform_text_size", array( "label" => "Text Font Size (e.g. 18px)", "section" => "vance_heritage_platform", "type" => "text" ) );

    }

    // --- CTA Strip ---
    $wp_customize->add_section( "vance_heritage_cta", array( "title" => "CTA Strip", "panel" => "vance_heritage_panel" ) );
    $wp_customize->add_setting( "vance_heritage_cta_title",      array( "default" => "Join the Vance Medical Community", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_cta_title",      array( "label" => "Heading", "section" => "vance_heritage_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_cta_desc",       array( "default" => "Whether you're a patient managing IBD, a clinician advancing your practice, or a researcher exploring gut health - there's a place for you at Vance Medical.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_heritage_cta_desc",       array( "label" => "Description", "section" => "vance_heritage_cta", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_heritage_cta_btn1_label", array( "default" => "I'm a Patient", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_cta_btn1_label", array( "label" => "Button 1 Label", "section" => "vance_heritage_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_cta_btn1_url",   array( "default" => "/patients/", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_heritage_cta_btn1_url",   array( "label" => "Button 1 URL", "section" => "vance_heritage_cta", "type" => "url" ) );
    $wp_customize->add_setting( "vance_heritage_cta_btn2_label", array( "default" => "I'm a Healthcare Professional", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_cta_btn2_label", array( "label" => "Button 2 Label", "section" => "vance_heritage_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_cta_btn2_url",   array( "default" => "/healthcare-professionals/", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_heritage_cta_btn2_url",   array( "label" => "Button 2 URL", "section" => "vance_heritage_cta", "type" => "url" ) );
    // Styles for CTA Strip
    $wp_customize->add_setting( "vance_heritage_cta_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_heritage_cta_show", array( "label" => "Show Section", "section" => "vance_heritage_cta", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_heritage_cta_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_cta_bg", array( "label" => "Background Colour", "section" => "vance_heritage_cta" ) ) );
    $wp_customize->add_setting( "vance_heritage_cta_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_cta_title_color", array( "label" => "Title Colour", "section" => "vance_heritage_cta" ) ) );
    $wp_customize->add_setting( "vance_heritage_cta_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_cta_title_size", array( "label" => "Title Font Size (e.g. 36px)", "section" => "vance_heritage_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_cta_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_cta_text_color", array( "label" => "Text Colour", "section" => "vance_heritage_cta" ) ) );
    $wp_customize->add_setting( "vance_heritage_cta_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_cta_text_size", array( "label" => "Text Font Size (e.g. 18px)", "section" => "vance_heritage_cta", "type" => "text" ) );


    // ---- PROMO BLOCKS ----

    // ---- Promo Block 1 ----
    $wp_customize->add_section( "vance_about_promo1", array( "title" => "Promo Block 1", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_promo1_img", array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_about_promo1_img", array( "label" => "Image", "section" => "vance_about_promo1" ) ) );
    $wp_customize->add_setting( "vance_about_promo1_title", array( "default" => "Promo title", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_promo1_title", array( "label" => "Title", "section" => "vance_about_promo1", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_promo1_sub", array( "default" => "Promo subtitle", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_promo1_sub", array( "label" => "Subtitle", "section" => "vance_about_promo1", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_promo1_desc", array( "default" => "Promo description text goes here.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_about_promo1_desc", array( "label" => "Description", "section" => "vance_about_promo1", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_about_promo1_btn_lbl", array( "default" => "Learn More", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_promo1_btn_lbl", array( "label" => "Button Label", "section" => "vance_about_promo1", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_promo1_btn_url", array( "default" => "#", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_about_promo1_btn_url", array( "label" => "Button URL", "section" => "vance_about_promo1", "type" => "url" ) );
    $wp_customize->add_setting( "vance_about_promo1_layout", array( "default" => "img-left", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_promo1_layout", array( "label" => "Layout", "section" => "vance_about_promo1", "type" => "select", "choices" => array("img-left" => "Image Left", "img-right" => "Image Right") ) );

    // Styles for Promo Block 1
    $wp_customize->add_setting( "vance_about_promo1_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_about_promo1_show", array( "label" => "Show Section", "section" => "vance_about_promo1", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_about_promo1_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_promo1_bg", array( "label" => "Background Color", "section" => "vance_about_promo1" ) ) );
    $wp_customize->add_setting( "vance_about_promo1_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_promo1_title_color", array( "label" => "Title Color", "section" => "vance_about_promo1" ) ) );
    $wp_customize->add_setting( "vance_about_promo1_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_promo1_title_size", array( "label" => "Title Font Size (px)", "section" => "vance_about_promo1", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_promo1_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_promo1_text_color", array( "label" => "Text Color", "section" => "vance_about_promo1" ) ) );
    $wp_customize->add_setting( "vance_about_promo1_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_promo1_text_size", array( "label" => "Text Font Size (px)", "section" => "vance_about_promo1", "type" => "text" ) );

    // ---- Promo Block 2 ----
    $wp_customize->add_section( "vance_about_promo2", array( "title" => "Promo Block 2", "panel" => "vance_about_panel" ) );
    $wp_customize->add_setting( "vance_about_promo2_img", array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_about_promo2_img", array( "label" => "Image", "section" => "vance_about_promo2" ) ) );
    $wp_customize->add_setting( "vance_about_promo2_title", array( "default" => "Promo title", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_promo2_title", array( "label" => "Title", "section" => "vance_about_promo2", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_promo2_sub", array( "default" => "Promo subtitle", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_promo2_sub", array( "label" => "Subtitle", "section" => "vance_about_promo2", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_promo2_desc", array( "default" => "Promo description text goes here.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_about_promo2_desc", array( "label" => "Description", "section" => "vance_about_promo2", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_about_promo2_btn_lbl", array( "default" => "Learn More", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_promo2_btn_lbl", array( "label" => "Button Label", "section" => "vance_about_promo2", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_promo2_btn_url", array( "default" => "#", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_about_promo2_btn_url", array( "label" => "Button URL", "section" => "vance_about_promo2", "type" => "url" ) );
    $wp_customize->add_setting( "vance_about_promo2_layout", array( "default" => "img-left", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_promo2_layout", array( "label" => "Layout", "section" => "vance_about_promo2", "type" => "select", "choices" => array("img-left" => "Image Left", "img-right" => "Image Right") ) );

    // Styles for Promo Block 2
    $wp_customize->add_setting( "vance_about_promo2_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_about_promo2_show", array( "label" => "Show Section", "section" => "vance_about_promo2", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_about_promo2_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_promo2_bg", array( "label" => "Background Color", "section" => "vance_about_promo2" ) ) );
    $wp_customize->add_setting( "vance_about_promo2_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_promo2_title_color", array( "label" => "Title Color", "section" => "vance_about_promo2" ) ) );
    $wp_customize->add_setting( "vance_about_promo2_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_promo2_title_size", array( "label" => "Title Font Size (px)", "section" => "vance_about_promo2", "type" => "text" ) );
    $wp_customize->add_setting( "vance_about_promo2_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_about_promo2_text_color", array( "label" => "Text Color", "section" => "vance_about_promo2" ) ) );
    $wp_customize->add_setting( "vance_about_promo2_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_about_promo2_text_size", array( "label" => "Text Font Size (px)", "section" => "vance_about_promo2", "type" => "text" ) );

    // ---- Promo Block 1 ----
    $wp_customize->add_section( "vance_heritage_promo1", array( "title" => "Promo Block 1", "panel" => "vance_heritage_panel" ) );
    $wp_customize->add_setting( "vance_heritage_promo1_img", array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_heritage_promo1_img", array( "label" => "Image", "section" => "vance_heritage_promo1" ) ) );
    $wp_customize->add_setting( "vance_heritage_promo1_title", array( "default" => "Promo title", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_promo1_title", array( "label" => "Title", "section" => "vance_heritage_promo1", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_promo1_sub", array( "default" => "Promo subtitle", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_promo1_sub", array( "label" => "Subtitle", "section" => "vance_heritage_promo1", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_promo1_desc", array( "default" => "Promo description text goes here.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_heritage_promo1_desc", array( "label" => "Description", "section" => "vance_heritage_promo1", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_heritage_promo1_btn_lbl", array( "default" => "Learn More", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_promo1_btn_lbl", array( "label" => "Button Label", "section" => "vance_heritage_promo1", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_promo1_btn_url", array( "default" => "#", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_heritage_promo1_btn_url", array( "label" => "Button URL", "section" => "vance_heritage_promo1", "type" => "url" ) );
    $wp_customize->add_setting( "vance_heritage_promo1_layout", array( "default" => "img-left", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_promo1_layout", array( "label" => "Layout", "section" => "vance_heritage_promo1", "type" => "select", "choices" => array("img-left" => "Image Left", "img-right" => "Image Right") ) );

    // Styles for Promo Block 1
    $wp_customize->add_setting( "vance_heritage_promo1_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_heritage_promo1_show", array( "label" => "Show Section", "section" => "vance_heritage_promo1", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_heritage_promo1_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_promo1_bg", array( "label" => "Background Color", "section" => "vance_heritage_promo1" ) ) );
    $wp_customize->add_setting( "vance_heritage_promo1_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_promo1_title_color", array( "label" => "Title Color", "section" => "vance_heritage_promo1" ) ) );
    $wp_customize->add_setting( "vance_heritage_promo1_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_promo1_title_size", array( "label" => "Title Font Size (px)", "section" => "vance_heritage_promo1", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_promo1_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_promo1_text_color", array( "label" => "Text Color", "section" => "vance_heritage_promo1" ) ) );
    $wp_customize->add_setting( "vance_heritage_promo1_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_promo1_text_size", array( "label" => "Text Font Size (px)", "section" => "vance_heritage_promo1", "type" => "text" ) );

    // ---- Promo Block 2 ----
    $wp_customize->add_section( "vance_heritage_promo2", array( "title" => "Promo Block 2", "panel" => "vance_heritage_panel" ) );
    $wp_customize->add_setting( "vance_heritage_promo2_img", array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_heritage_promo2_img", array( "label" => "Image", "section" => "vance_heritage_promo2" ) ) );
    $wp_customize->add_setting( "vance_heritage_promo2_title", array( "default" => "Promo title", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_promo2_title", array( "label" => "Title", "section" => "vance_heritage_promo2", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_promo2_sub", array( "default" => "Promo subtitle", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_promo2_sub", array( "label" => "Subtitle", "section" => "vance_heritage_promo2", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_promo2_desc", array( "default" => "Promo description text goes here.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_heritage_promo2_desc", array( "label" => "Description", "section" => "vance_heritage_promo2", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_heritage_promo2_btn_lbl", array( "default" => "Learn More", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_promo2_btn_lbl", array( "label" => "Button Label", "section" => "vance_heritage_promo2", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_promo2_btn_url", array( "default" => "#", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_heritage_promo2_btn_url", array( "label" => "Button URL", "section" => "vance_heritage_promo2", "type" => "url" ) );
    $wp_customize->add_setting( "vance_heritage_promo2_layout", array( "default" => "img-left", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_promo2_layout", array( "label" => "Layout", "section" => "vance_heritage_promo2", "type" => "select", "choices" => array("img-left" => "Image Left", "img-right" => "Image Right") ) );

    // Styles for Promo Block 2
    $wp_customize->add_setting( "vance_heritage_promo2_show", array( "default" => true, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_heritage_promo2_show", array( "label" => "Show Section", "section" => "vance_heritage_promo2", "type" => "checkbox" ) );
    $wp_customize->add_setting( "vance_heritage_promo2_bg", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_promo2_bg", array( "label" => "Background Color", "section" => "vance_heritage_promo2" ) ) );
    $wp_customize->add_setting( "vance_heritage_promo2_title_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_promo2_title_color", array( "label" => "Title Color", "section" => "vance_heritage_promo2" ) ) );
    $wp_customize->add_setting( "vance_heritage_promo2_title_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_promo2_title_size", array( "label" => "Title Font Size (px)", "section" => "vance_heritage_promo2", "type" => "text" ) );
    $wp_customize->add_setting( "vance_heritage_promo2_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_heritage_promo2_text_color", array( "label" => "Text Color", "section" => "vance_heritage_promo2" ) ) );
    $wp_customize->add_setting( "vance_heritage_promo2_text_size", array( "default" => "", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_heritage_promo2_text_size", array( "label" => "Text Font Size (px)", "section" => "vance_heritage_promo2", "type" => "text" ) );

    // ---- CONTACT US PAGE PANEL ----
    $wp_customize->add_panel( "vance_contact_panel", array(
        "title"    => __( "Contact Us Page", "sla-health-hub" ),
        "priority" => 51,
    ) );

    // ── Hero ──────────────────────────────────────────────────
    $wp_customize->add_section( "vance_contact_hero", array( "title" => "Hero Section", "panel" => "vance_contact_panel" ) );
    $wp_customize->add_setting( "vance_contact_hero_tag",      array( "default" => "Get in Touch",             "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_contact_hero_tag",      array( "label" => "Tag Label",                  "section" => "vance_contact_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_contact_hero_title",    array( "default" => "We'd Love to Hear From You", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_contact_hero_title",    array( "label" => "Heading (HTML allowed)",     "section" => "vance_contact_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_contact_hero_desc",     array( "default" => "Whether you're a patient, healthcare professional, researcher, or media contact — our team is here to help.", "sanitize_callback" => "sanitize_textarea_field" ) );
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
    $wp_customize->add_setting( "vance_contact_email",       array( "default" => "info@gastrohealthhub.com",     "sanitize_callback" => "sanitize_email" ) );
    $wp_customize->add_control( "vance_contact_email",       array( "label" => "Email Address",              "section" => "vance_contact_info", "type" => "text" ) );
    $wp_customize->add_setting( "vance_contact_phone",       array( "default" => "+44 (0)1628 526 005",      "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_contact_phone",       array( "label" => "Phone Number",               "section" => "vance_contact_info", "type" => "text" ) );
    $wp_customize->add_setting( "vance_contact_address",     array( "default" => "Vance Medical Foods Ltd, 4 Renaissance Way, Wooburn Green, HP10 0DF, United Kingdom", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_contact_address",     array( "label" => "Office Address",             "section" => "vance_contact_info", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_contact_hours",       array( "default" => "Monday – Friday, 9:00 am – 5:00 pm GMT", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_contact_hours",       array( "label" => "Office Hours",               "section" => "vance_contact_info", "type" => "text" ) );

    // ============================================================
    // HERO OVERLAY OPACITY SLIDERS — per-page (0–100, mapped to alpha 0.00–1.00)
    // ============================================================
    $hero_overlay_pages = array(
        "vance_pat_hero"        => array( "default" => 70, "label" => "Patients hero overlay opacity (%)" ),
        "vance_hcp_hero"        => array( "default" => 75, "label" => "HCP hero overlay opacity (%)" ),
        "vance_about_hero"      => array( "default" => 78, "label" => "About hero overlay opacity (%)" ),
        "vance_heritage_hero"   => array( "default" => 78, "label" => "Heritage hero overlay opacity (%)" ),
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
        "title"    => __( "Hero Overlays (extra)", "sla-health-hub" ),
        "priority" => 49,
    ) );
    $wp_customize->add_section( "vance_overlays_misc", array(
        "title" => "Per-page Overlay Opacity",
        "panel" => "vance_overlays_panel",
        "description" => "Slide 0–100 to control how dark the photo overlay is on these heroes. Higher = darker.",
    ) );
    $extra_overlays = array(
        "vance_home_hero_overlay"     => array( "default" => 50, "label" => "Home hero overlay opacity (%)" ),
        "vance_evidence_hero_overlay" => array( "default" => 78, "label" => "Turn Evidence hero overlay opacity (%)" ),
        "vance_askai_hero_overlay"    => array( "default" => 85, "label" => "Ask AI hero overlay opacity (%)" ),
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
        "title"    => __( "Education Page Settings", "sla-health-hub" ),
        "priority" => 50,
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

    // Education Intro
    $wp_customize->add_section( "vance_edu_intro", array( "title" => "Intro Section", "panel" => "vance_edu_panel" ) );
    $wp_customize->add_setting( "vance_edu_intro_bg", array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_intro_bg", array( "label" => "Background Colour", "section" => "vance_edu_intro" ) ) );
    $wp_customize->add_setting( "vance_edu_intro_text_color", array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_intro_text_color", array( "label" => "Text Colour (blank = theme defaults)", "section" => "vance_edu_intro" ) ) );
    $wp_customize->add_setting( "vance_edu_intro_align", array( "default" => "center", "sanitize_callback" => "sanitize_key" ) );
    $wp_customize->add_control( "vance_edu_intro_align", array(
        "label"   => "Text Position",
        "section" => "vance_edu_intro",
        "type"    => "radio",
        "choices" => array( "left" => "Left", "center" => "Centre", "right" => "Right" ),
    ) );
    $wp_customize->add_setting( "vance_edu_intro_pad_top",    array( "default" => 60, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_edu_intro_pad_top",    array( "label" => "Padding Top (px)",    "section" => "vance_edu_intro", "type" => "number", "input_attrs" => array( "min" => 0, "max" => 200, "step" => 5 ) ) );
    $wp_customize->add_setting( "vance_edu_intro_pad_bottom", array( "default" => 20, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_edu_intro_pad_bottom", array( "label" => "Padding Bottom (px)", "section" => "vance_edu_intro", "type" => "number", "input_attrs" => array( "min" => 0, "max" => 200, "step" => 5 ) ) );

    // Education Tracks
    $wp_customize->add_section( "vance_edu_tracks", array( "title" => "Course Tracks", "panel" => "vance_edu_panel" ) );
    $wp_customize->add_setting( "vance_edu_tracks_eyebrow", array( "default" => "Two Tracks. One Standard.", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_edu_tracks_eyebrow", array( "label" => "Section Eyebrow", "section" => "vance_edu_tracks", "type" => "text" ) );
    $wp_customize->add_setting( "vance_edu_tracks_heading", array( "default" => "Built for the people who need it most", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_edu_tracks_heading", array( "label" => "Section Heading", "section" => "vance_edu_tracks", "type" => "text" ) );
    $wp_customize->add_setting( "vance_edu_tracks_desc",    array( "default" => "Every course is co-developed with practising clinicians and reviewed by patient advisors before release.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_edu_tracks_desc",    array( "label" => "Section Description", "section" => "vance_edu_tracks", "type" => "textarea" ) );
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
    $wp_customize->add_setting( "vance_edu_waitlist_desc",    array( "default" => "Be first to hear when patient or practitioner courses go live. We'll send a single email — no spam, easy unsubscribe.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_edu_waitlist_desc",    array( "label" => "Description", "section" => "vance_edu_waitlist", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_edu_waitlist_action",  array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_edu_waitlist_action",  array( "label" => "Form Action URL (Mailchimp/HubSpot endpoint — leave blank to hide form)", "section" => "vance_edu_waitlist", "type" => "url" ) );
    $wp_customize->add_setting( "vance_edu_waitlist_button",  array( "default" => "Notify Me", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_edu_waitlist_button",  array( "label" => "Button Label", "section" => "vance_edu_waitlist", "type" => "text" ) );
    $wp_customize->add_setting( "vance_edu_waitlist_bg_from",    array( "default" => "#008080", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_waitlist_bg_from",    array( "label" => "Gradient — From Colour", "section" => "vance_edu_waitlist" ) ) );
    $wp_customize->add_setting( "vance_edu_waitlist_bg_to",      array( "default" => "#006666", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_waitlist_bg_to",      array( "label" => "Gradient — To Colour",   "section" => "vance_edu_waitlist" ) ) );
    $wp_customize->add_setting( "vance_edu_waitlist_text_color", array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_edu_waitlist_text_color", array( "label" => "Text Colour",            "section" => "vance_edu_waitlist" ) ) );

    // ============================================================
    // TOOLS & RESOURCES PAGE PANEL
    // ============================================================
    $wp_customize->add_panel( "vance_tools_panel", array(
        "title"    => __( "Tools & Resources Page", "sla-health-hub" ),
        "priority" => 51,
    ) );

    // Tools Hero
    $wp_customize->add_section( "vance_tools_hero", array( "title" => "Hero Section", "panel" => "vance_tools_panel" ) );
    $wp_customize->add_setting( "vance_tools_hero_tag",   array( "default" => "Free Tools", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_tools_hero_tag",   array( "label" => "Tag Label", "section" => "vance_tools_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_tools_hero_title", array( "default" => "Tools &amp; <span class=\"highlight\">Resources</span>", "sanitize_callback" => "wp_kses_post" ) );
    $wp_customize->add_control( "vance_tools_hero_title", array( "label" => "Title (HTML allowed)", "section" => "vance_tools_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_tools_hero_desc",  array( "default" => "Clinical calculators built on peer-reviewed evidence — free to use, no signup required. Save your results and build a meal plan by registering for a free account.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_tools_hero_desc",  array( "label" => "Description", "section" => "vance_tools_hero", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_tools_hero_bg",    array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "vance_tools_hero_bg", array( "label" => "Hero Background Image", "section" => "vance_tools_hero" ) ) );
    $wp_customize->add_setting( "vance_tools_hero_overlay", array( "default" => 70, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_tools_hero_overlay", array( "label" => "Hero Overlay Opacity (%)", "section" => "vance_tools_hero", "type" => "number", "input_attrs" => array( "min" => 0, "max" => 100, "step" => 5 ) ) );

    // Tools Intro
    $wp_customize->add_section( "vance_tools_intro", array( "title" => "Intro Section", "panel" => "vance_tools_panel" ) );
    $wp_customize->add_setting( "vance_tools_intro_eyebrow", array( "default" => "Open Access", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_tools_intro_eyebrow", array( "label" => "Eyebrow / tag label", "section" => "vance_tools_intro", "type" => "text" ) );
    $wp_customize->add_setting( "vance_tools_intro_title", array( "default" => "Clinical-grade calculators, free for everyone", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_tools_intro_title", array( "label" => "Section Title", "section" => "vance_tools_intro", "type" => "text" ) );
    $wp_customize->add_setting( "vance_tools_intro_desc",  array( "default" => "Whether you're tracking your own health or supporting a patient, these tools turn evidence into a number you can act on. No login needed to use them — register if you want to save results to your dashboard.", "sanitize_callback" => "sanitize_textarea_field" ) );
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
    // Mods read by page-{slug}.php wrapper templates (omega-3, malnutrition,
    // blood-test, ibd-recipies). Each tool gets one section with name,
    // subtitle, hero bg image, and overlay slider.
    // ============================================================
    $tool_hero_specs = array(
        'omega'        => array(
            'section_id'   => 'vance_tools_hero_omega',
            'title'        => 'Omega-3 Calculator — Hero',
            'name_key'     => 'vance_tool_omega_name',
            'sub_key'      => 'vance_tool_omega_subtitle',
            'bg_key'       => 'vance_tool_omega_hero_bg',
            'overlay_key'  => 'vance_tool_omega_hero_overlay',
            'name_default' => 'Omega-3 Calculator',
            'sub_default'  => 'Calculate your personalised EPA + DHA target based on body weight, dietary intake, and clinical guidance — built on the latest gastroenterology evidence.',
        ),
        'malnutrition' => array(
            'section_id'   => 'vance_tools_hero_malnutrition',
            'title'        => 'Malnutrition Calculator — Hero',
            'name_key'     => 'vance_tool_malnutrition_name',
            'sub_key'      => 'vance_tool_malnutrition_subtitle',
            'bg_key'       => 'vance_tool_malnutrition_hero_bg',
            'overlay_key'  => 'vance_tool_malnutrition_hero_overlay',
            'name_default' => 'IBD Malnutrition Calculator',
            'sub_default'  => 'Clinically-grounded 11-step malnutrition risk screener for IBD patients. Combines MUST, IBD-NST, and GLIM criteria into a single, actionable score.',
        ),
        'blood'        => array(
            'section_id'   => 'vance_tools_hero_blood',
            'title'        => 'Blood Test Analyser — Hero',
            'name_key'     => 'vance_tool_blood_name',
            'sub_key'      => 'vance_tool_blood_subtitle',
            'bg_key'       => 'vance_tool_blood_hero_bg',
            'overlay_key'  => 'vance_tool_blood_hero_overlay',
            'name_default' => 'IBD Blood Test Analyser',
            'sub_default'  => 'Drop in your blood panel results and get plain-language analysis flagging anything outside reference ranges. Designed to help you prepare for your next clinic appointment.',
        ),
        'recipes'      => array(
            'section_id'   => 'vance_tools_hero_recipes',
            'title'        => 'IBD Recipes — Hero',
            'name_key'     => 'vance_tool_recipes_name',
            'sub_key'      => 'vance_tool_recipes_subtitle',
            'bg_key'       => 'vance_tool_recipes_hero_bg',
            'overlay_key'  => 'vance_tool_recipes_hero_overlay',
            'name_default' => 'IBD Recipes & Meal Planner',
            'sub_default'  => 'EPA-rich, gut-friendly recipes with full nutrition data. Browse and build a weekly plan freely — saving plans takes two clicks to create your free account.',
        ),
    );
    foreach ( $tool_hero_specs as $key => $spec ) {
        $wp_customize->add_section( $spec['section_id'], array( "title" => $spec['title'], "panel" => "vance_tools_panel" ) );
        $wp_customize->add_setting( $spec['name_key'], array( "default" => $spec['name_default'], "sanitize_callback" => "sanitize_text_field" ) );
        $wp_customize->add_control( $spec['name_key'], array( "label" => "Tool Name (H1)", "section" => $spec['section_id'], "type" => "text" ) );
        $wp_customize->add_setting( $spec['sub_key'],  array( "default" => $spec['sub_default'], "sanitize_callback" => "sanitize_textarea_field" ) );
        $wp_customize->add_control( $spec['sub_key'],  array( "label" => "Subtitle", "section" => $spec['section_id'], "type" => "textarea" ) );
        $wp_customize->add_setting( $spec['bg_key'],   array( "default" => "", "sanitize_callback" => "esc_url_raw" ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $spec['bg_key'], array( "label" => "Hero Background Image", "section" => $spec['section_id'] ) ) );
        $wp_customize->add_setting( $spec['overlay_key'], array( "default" => 80, "sanitize_callback" => "absint" ) );
        $wp_customize->add_control( $spec['overlay_key'], array(
            "label"       => "Hero Overlay Opacity (%)",
            "section"     => $spec['section_id'],
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
        "title"    => __( "Turn Evidence into Action", "sla-health-hub" ),
        "priority" => 52,
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
    $wp_customize->add_setting( "vance_evidence_hero_btn2_text", array( "default" => "Request a Clinical Consultation", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_evidence_hero_btn2_text", array( "label" => "Secondary Button Label", "section" => "vance_evidence_hero", "type" => "text" ) );
    $wp_customize->add_setting( "vance_evidence_hero_btn2_link", array( "default" => "/contact-us/", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_evidence_hero_btn2_link", array( "label" => "Secondary Button Link", "section" => "vance_evidence_hero", "type" => "url" ) );
    // Hero overlay slider lives in this section so admins find it next to the bg image (was in "Hero Overlays (extra)" only)
    $wp_customize->add_setting( "vance_evidence_hero_overlay_inline", array( "default" => 78, "sanitize_callback" => "absint" ) );
    $wp_customize->add_control( "vance_evidence_hero_overlay_inline", array(
        "label"       => "Hero Overlay Opacity (%) — duplicates the slider in “Hero Overlays (extra)”",
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
        3 => array( "Peer-Reviewed Science","Curated meta-analyses and systematic reviews from Gut, AJG, Lancet Gastro, JCN, and other indexed journals — summarised for bedside use." ),
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
        1 => array( "Synthesise", "Our medical writing team combines primary studies, guidelines, and registry data into a single graded position — with conflicts of interest and limitations flagged openly." ),
        2 => array( "Translate",  "We convert each position into two companion artefacts: a clinician-facing protocol card and a plain-language patient brief vetted by a patient advisory panel." ),
        3 => array( "Apply",      "Protocols feed the Vance Medical dashboard, the Ask AI assistant, and downloadable handouts — so evidence becomes a concrete decision at the point of care." ),
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
    $cat_choices = array( 0 => '— All categories —' );
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
    $wp_customize->add_setting( "vance_evidence_cta_desc",  array( "default" => "Free registration unlocks the full protocol library, the Ask AI clinical assistant, and printable patient handouts branded to your practice.", "sanitize_callback" => "sanitize_textarea_field" ) );
    $wp_customize->add_control( "vance_evidence_cta_desc",  array( "label" => "Description", "section" => "vance_evidence_cta", "type" => "textarea" ) );
    $wp_customize->add_setting( "vance_evidence_cta_btn1_text", array( "default" => "Register Free", "sanitize_callback" => "sanitize_text_field" ) );
    $wp_customize->add_control( "vance_evidence_cta_btn1_text", array( "label" => "Primary Button Label", "section" => "vance_evidence_cta", "type" => "text" ) );
    $wp_customize->add_setting( "vance_evidence_cta_btn1_link", array( "default" => "/register/", "sanitize_callback" => "esc_url_raw" ) );
    $wp_customize->add_control( "vance_evidence_cta_btn1_link", array( "label" => "Primary Button Link", "section" => "vance_evidence_cta", "type" => "url" ) );
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
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_cta_bg_from",    array( "label" => "CTA Gradient — From", "section" => "vance_evidence_styling" ) ) );
    $wp_customize->add_setting( "vance_evidence_cta_bg_to",      array( "default" => "#006666", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_cta_bg_to",      array( "label" => "CTA Gradient — To",   "section" => "vance_evidence_styling" ) ) );
    $wp_customize->add_setting( "vance_evidence_heading_color",  array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_heading_color",  array( "label" => "Section Heading Colour", "section" => "vance_evidence_styling" ) ) );
    $wp_customize->add_setting( "vance_evidence_body_color",     array( "default" => "", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_body_color",     array( "label" => "Body Text Colour", "section" => "vance_evidence_styling" ) ) );
    $wp_customize->add_setting( "vance_evidence_pillar_card_bg", array( "default" => "#ffffff", "sanitize_callback" => "sanitize_hex_color" ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "vance_evidence_pillar_card_bg", array( "label" => "Pillar / Process Card Background", "section" => "vance_evidence_styling" ) ) );

}
add_action( 'customize_register', 'vance_pages_customize_register', 20 );
