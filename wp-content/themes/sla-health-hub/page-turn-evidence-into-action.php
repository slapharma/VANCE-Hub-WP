<?php
/**
 * Template Name: Turn Evidence into Action
 *
 * Auto-bound to any Page with slug `turn-evidence-into-action` via WP's
 * template hierarchy; also selectable from the Page Attributes template
 * dropdown (Template Name above).
 *
 * All user-facing copy is read through vance_get_theme_mod() with sensible
 * placeholder defaults — override each field in Appearance → Customize.
 * Customizer section/control registrations should follow the same
 * `vance_evidence_*` naming used in this file (mirror the pattern in
 * customizer-pages.php when you wire them up).
 */
get_header(); ?>

<main id="main-content">

    <!-- HERO -->
    <?php
    $hero_bg    = vance_get_theme_mod( 'vance_evidence_hero_bg', get_template_directory_uri() . '/assets/img/hcp_hero.png' );
    $hero_tag   = vance_get_theme_mod( 'vance_evidence_hero_tag', 'Evidence to Practice' );
    $hero_title = vance_get_theme_mod( 'vance_evidence_hero_title', 'Turn <span class="highlight">Evidence</span> into Action' );
    $hero_desc  = vance_get_theme_mod( 'vance_evidence_hero_desc', 'Rigorous clinical research only matters when it reaches the patient. Vance Medical translates peer-reviewed science and real-world data into practical protocols that clinicians and patients can act on.' );
    $hero_btn1  = vance_get_theme_mod( 'vance_evidence_hero_btn1_text', 'Explore the Evidence Library' );
    $hero_btn1_link = vance_get_theme_mod( 'vance_evidence_hero_btn1_link', '#pillars' );
    $hero_btn2  = vance_get_theme_mod( 'vance_evidence_hero_btn2_text', 'Request a Clinical Consultation' );
    $hero_btn2_link = vance_get_theme_mod( 'vance_evidence_hero_btn2_link', '/contact-us/' );
    ?>
    <section class="hero evidence-hero" style="padding: 80px 0 120px; display: flex; align-items: center; background: linear-gradient(rgba(10,25,41,0.78), rgba(10,25,41,0.92)), url('<?php echo esc_url( $hero_bg ); ?>') no-repeat center center; background-size: cover;">
        <div class="container">
            <div class="hero-content">
                <span class="tag-label"><?php echo esc_html( $hero_tag ); ?></span>
                <h1><?php echo wp_kses_post( $hero_title ); ?></h1>
                <p><?php echo esc_html( $hero_desc ); ?></p>
                <div class="hero-actions" style="margin-top: 24px;">
                    <a href="<?php echo esc_url( $hero_btn1_link ); ?>" class="btn btn-primary"><?php echo esc_html( $hero_btn1 ); ?></a>
                    <a href="<?php echo esc_url( $hero_btn2_link ); ?>" class="btn btn-outline"><?php echo esc_html( $hero_btn2 ); ?></a>
                </div>
            </div>
        </div>
    </section>

    <!-- EVIDENCE PILLARS -->
    <?php
    $pillars_tag   = vance_get_theme_mod( 'vance_evidence_pillars_tag',   'Our Evidence Standards' );
    $pillars_title = vance_get_theme_mod( 'vance_evidence_pillars_title', 'Four Sources. One Standard.' );
    $pillars_desc  = vance_get_theme_mod( 'vance_evidence_pillars_desc',  'Every recommendation we publish is anchored in at least one of these evidence streams and graded against internationally-recognised quality criteria.' );

    $pillar_defaults = array(
        1 => array( 'Clinical Trials',     'Randomised controlled trials and phase II–IV studies investigating medical food and nutritional interventions in IBD, SIBO, and related GI conditions.' ),
        2 => array( 'Real-World Data',     'Longitudinal outcomes from registered patient cohorts, post-market surveillance, and anonymised dashboard analytics across thousands of IBD journeys.' ),
        3 => array( 'Peer-Reviewed Science','Curated meta-analyses and systematic reviews from Gut, AJG, Lancet Gastro, JCN, and other indexed journals — summarised for bedside use.' ),
        4 => array( 'Expert Consensus',    'Multidisciplinary panel statements from gastroenterologists, dietitians, and pharmacists who have validated the protocol pathways we publish.' ),
    );
    $pillar_colors = array(
        1 => 'var(--primary-color), #006666',
        2 => '#0ea5e9, #0284c7',
        3 => '#10b981, #059669',
        4 => '#8b5cf6, #7c3aed',
    );
    // Heroicons (24 outline) — flask, chart-bar, book-open, user-group.
    $pillar_icons = array(
        1 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>',
        2 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19a3 3 0 11-6 0 3 3 0 016 0zm12-3a3 3 0 11-6 0 3 3 0 016 0z"/>',
        3 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
        4 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
    );
    ?>
    <section id="pillars" class="section-padding" style="background: var(--accent-color);">
        <div class="container">
            <div class="text-center max-600 margin-b-60">
                <span class="tag-section"><?php echo esc_html( $pillars_tag ); ?></span>
                <h2 style="color: var(--secondary-color);"><?php echo esc_html( $pillars_title ); ?></h2>
                <p style="color: var(--text-light);"><?php echo esc_html( $pillars_desc ); ?></p>
            </div>
            <div class="grid-2 resource-grid">
                <?php for ( $i = 1; $i <= 4; $i++ ) :
                    $card_title = vance_get_theme_mod( "vance_evidence_pillar{$i}_title", $pillar_defaults[ $i ][0] );
                    $card_desc  = vance_get_theme_mod( "vance_evidence_pillar{$i}_desc",  $pillar_defaults[ $i ][1] );
                ?>
                <div style="display: flex; gap: 20px; padding: 32px; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                    <div style="flex-shrink: 0; width: 56px; height: 56px; background: linear-gradient(135deg, <?php echo $pillar_colors[ $i ]; ?>); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <svg width="28" height="28" fill="none" stroke="white" viewBox="0 0 24 24"><?php echo $pillar_icons[ $i ]; ?></svg>
                    </div>
                    <div>
                        <h4 style="font-size: 18px; color: var(--secondary-color); margin-bottom: 8px;"><?php echo esc_html( $card_title ); ?></h4>
                        <p style="color: var(--text-light); font-size: 14px; margin: 0;"><?php echo esc_html( $card_desc ); ?></p>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <!-- FROM INSIGHT TO PRACTICE (process) -->
    <?php
    $proc_title = vance_get_theme_mod( 'vance_evidence_proc_title', 'From Insight to Practice' );
    $proc_desc  = vance_get_theme_mod( 'vance_evidence_proc_desc',  'The journey every piece of evidence takes before it reaches a clinician protocol or a patient-facing recommendation.' );

    $proc_defaults = array(
        1 => array( 'Synthesise', 'Our medical writing team combines primary studies, guidelines, and registry data into a single graded position — with conflicts of interest and limitations flagged openly.' ),
        2 => array( 'Translate',  'We convert each position into two companion artefacts: a clinician-facing protocol card and a plain-language patient brief vetted by a patient advisory panel.' ),
        3 => array( 'Apply',      'Protocols feed the Vance Medical dashboard, the Ask AI assistant, and downloadable handouts — so evidence becomes a concrete decision at the point of care.' ),
    );
    ?>
    <section class="section-padding" style="background: white;">
        <div class="container">
            <div class="text-center max-600 margin-b-60">
                <h2 style="color: var(--secondary-color);"><?php echo esc_html( $proc_title ); ?></h2>
                <p style="color: var(--text-light);"><?php echo esc_html( $proc_desc ); ?></p>
            </div>
            <div class="grid-3" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
                <?php for ( $i = 1; $i <= 3; $i++ ) :
                    $step_title = vance_get_theme_mod( "vance_evidence_proc{$i}_title", $proc_defaults[ $i ][0] );
                    $step_desc  = vance_get_theme_mod( "vance_evidence_proc{$i}_desc",  $proc_defaults[ $i ][1] );
                ?>
                <div style="position: relative; padding: 40px 28px; background: var(--accent-color); border-radius: var(--radius-lg); border-top: 4px solid var(--primary-color);">
                    <div style="position: absolute; top: -20px; left: 28px; width: 40px; height: 40px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px;"><?php echo $i; ?></div>
                    <h3 style="font-size: 20px; color: var(--secondary-color); margin: 12px 0;"><?php echo esc_html( $step_title ); ?></h3>
                    <p style="font-size: 14px; color: var(--text-light); margin: 0; line-height: 1.6;"><?php echo esc_html( $step_desc ); ?></p>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <!-- FEATURED EVIDENCE (recent posts from configurable category) -->
    <?php
    $feat_title = vance_get_theme_mod( 'vance_evidence_feat_title', 'Latest Evidence in Focus' );
    $feat_desc  = vance_get_theme_mod( 'vance_evidence_feat_desc',  'Recent reviews, trial readouts, and protocol updates published by the Vance Medical editorial team.' );
    $feat_cat   = (int) vance_get_theme_mod( 'vance_evidence_feat_category', 0 );
    $feat_count = (int) vance_get_theme_mod( 'vance_evidence_feat_count', 3 );

    $feat_args = array(
        'post_type'           => array( 'post', 'research', 'review' ),
        'posts_per_page'      => $feat_count ?: 3,
        'ignore_sticky_posts' => true,
    );
    if ( $feat_cat > 0 ) {
        $feat_args['cat'] = $feat_cat;
    }
    $feat_query = new WP_Query( $feat_args );
    ?>
    <?php if ( $feat_query->have_posts() ) : ?>
    <section class="section-padding" style="background: var(--accent-color);">
        <div class="container">
            <div class="text-center max-600 margin-b-60">
                <h2 style="color: var(--secondary-color);"><?php echo esc_html( $feat_title ); ?></h2>
                <p style="color: var(--text-light);"><?php echo esc_html( $feat_desc ); ?></p>
            </div>
            <div class="grid-3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                <?php while ( $feat_query->have_posts() ) : $feat_query->the_post(); ?>
                <article style="background: white; border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); display: flex; flex-direction: column;">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <a href="<?php the_permalink(); ?>" style="display: block; aspect-ratio: 16/9; overflow: hidden;">
                            <?php the_post_thumbnail( 'medium_large', array( 'style' => 'width:100%; height:100%; object-fit:cover;' ) ); ?>
                        </a>
                    <?php endif; ?>
                    <div style="padding: 24px; flex: 1; display: flex; flex-direction: column;">
                        <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: var(--primary-color); margin-bottom: 8px;">
                            <?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ?? 'Evidence' ); ?>
                        </div>
                        <h3 style="font-size: 18px; color: var(--secondary-color); margin-bottom: 12px; line-height: 1.4;">
                            <a href="<?php the_permalink(); ?>" style="color: inherit; text-decoration: none;"><?php the_title(); ?></a>
                        </h3>
                        <p style="color: var(--text-light); font-size: 14px; margin: 0 0 16px 0; flex: 1;"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
                        <a href="<?php the_permalink(); ?>" class="view-all-link" style="align-self: flex-start;">
                            Read the evidence
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </article>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA -->
    <?php
    $cta_title = vance_get_theme_mod( 'vance_evidence_cta_title', 'Put Evidence to Work for Your Patients' );
    $cta_desc  = vance_get_theme_mod( 'vance_evidence_cta_desc',  'Free registration unlocks the full protocol library, the Ask AI clinical assistant, and printable patient handouts branded to your practice.' );
    $cta_btn1  = vance_get_theme_mod( 'vance_evidence_cta_btn1_text', 'Register Free' );
    $cta_btn1_link = vance_get_theme_mod( 'vance_evidence_cta_btn1_link', '/register/' );
    $cta_btn2  = vance_get_theme_mod( 'vance_evidence_cta_btn2_text', 'Talk to Our Team' );
    $cta_btn2_link = vance_get_theme_mod( 'vance_evidence_cta_btn2_link', '/contact-us/' );
    ?>
    <section class="section-padding" style="background: linear-gradient(135deg, var(--secondary-color), #112240);">
        <div class="container" style="text-align: center; color: white;">
            <h2 style="color: white; margin-bottom: 16px;"><?php echo esc_html( $cta_title ); ?></h2>
            <p class="max-600" style="font-size: 18px; margin-bottom: 32px; color: rgba(255,255,255,0.85);"><?php echo esc_html( $cta_desc ); ?></p>
            <div class="hero-actions" style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo esc_url( $cta_btn1_link ); ?>" class="btn btn-primary"><?php echo esc_html( $cta_btn1 ); ?></a>
                <a href="<?php echo esc_url( $cta_btn2_link ); ?>" class="btn btn-outline" style="border-color: rgba(255,255,255,0.4); color: white;"><?php echo esc_html( $cta_btn2 ); ?></a>
            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>
