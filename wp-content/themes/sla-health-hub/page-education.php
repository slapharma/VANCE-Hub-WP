<?php
/**
 * Template Name: Education (Coming Soon)
 *
 * Public-facing landing page for the Education / Courses category. Acts as a
 * "coming soon" placeholder while course content is being built, with a wait-
 * list signup so we can notify subscribers when each track goes live.
 *
 * To activate: create a Page titled "Education", set slug `education`, and
 * choose "Education (Coming Soon)" as the template under Page Attributes.
 *
 * All copy is read through vance_get_theme_mod() — override defaults via
 * Appearance → Customize → Education Page Settings.
 */
get_header(); ?>

<main id="main-content">

    <!-- HERO -->
    <?php
    $hero_bg      = vance_get_theme_mod( 'vance_edu_hero_bg', get_template_directory_uri() . '/assets/img/hcp_hero.png' );
    $hero_tag     = vance_get_theme_mod( 'vance_edu_hero_tag',   'Education' );
    $hero_title   = vance_get_theme_mod( 'vance_edu_hero_title', 'Courses are <span class="highlight">Coming Soon</span>' );
    $hero_desc    = vance_get_theme_mod( 'vance_edu_hero_desc',  "We're building self-paced courses for patients and CPD-accredited modules for practitioners. Join the waitlist to be the first to know when enrolment opens." );
    $hero_overlay = max( 0, min( 100, absint( vance_get_theme_mod( 'vance_edu_hero_overlay', 75 ) ) ) ) / 100;
    $hero_overlay_bottom = min( 1, $hero_overlay + 0.15 );
    ?>
    <section class="hero edu-hero" style="padding: 80px 0 120px; display: flex; align-items: center; background: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $hero_overlay ); ?>), rgba(10,25,41,<?php echo esc_attr( $hero_overlay_bottom ); ?>)), url('<?php echo esc_url( $hero_bg ); ?>') no-repeat center center; background-size: cover;">
        <div class="container">
            <div class="hero-content">
                <span class="tag-label"><?php echo esc_html( $hero_tag ); ?></span>
                <h1><?php echo wp_kses_post( $hero_title ); ?></h1>
                <p><?php echo esc_html( $hero_desc ); ?></p>
                <div class="hero-actions" style="margin-top: 24px;">
                    <a href="#waitlist" class="btn btn-primary">Join the Waitlist</a>
                    <a href="#tracks" class="btn btn-outline">See What's Coming</a>
                </div>
            </div>
        </div>
    </section>

    <!-- COURSE TRACKS -->
    <?php
    $track_defaults = array(
        1 => array( 'Patient Courses', "Self-paced modules on living with IBD: nutrition fundamentals, symptom tracking, mealtime confidence, and working with your care team. Designed in plain English with downloadable worksheets." ),
        2 => array( 'Practitioner Courses', "CPD-accredited deep dives on FSMP integration, Omega-3 dosing, malnutrition screening, and translating evidence into protocols. Built for gastroenterologists, dietitians, GPs, and pharmacists." ),
    );
    // Brand-only palette: [bg, stroke]
    $track_colors = array(
        1 => array( '#008080', '#ffffff' ),
        2 => array( '#aedbdb', '#008080' ),
    );
    $track_icons = array(
        // Heroicons: book-open + academic-cap
        1 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
        2 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>',
    );
    ?>
    <section id="tracks" class="section-padding edu-tracks-section" style="background: var(--accent-color);">
        <div class="container">
            <div class="text-center max-600 margin-b-60">
                <span class="tag-section">Two Tracks. One Standard.</span>
                <h2 style="color: var(--secondary-color);">Built for the people who need it most</h2>
                <p style="color: var(--text-light);">Every course is co-developed with practising clinicians and reviewed by patient advisors before release.</p>
            </div>
            <div class="grid-2 edu-track-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px;">
                <?php for ( $i = 1; $i <= 2; $i++ ) :
                    $t_title = vance_get_theme_mod( "vance_edu_track{$i}_title", $track_defaults[ $i ][0] );
                    $t_desc  = vance_get_theme_mod( "vance_edu_track{$i}_desc",  $track_defaults[ $i ][1] );
                ?>
                <div class="edu-track-card" style="display: flex; gap: 24px; padding: 36px; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); align-items: flex-start;">
                    <div class="edu-track-icon" style="flex-shrink: 0; width: 64px; height: 64px; background: <?php echo esc_attr( $track_colors[ $i ][0] ); ?>; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                        <svg width="32" height="32" fill="none" stroke="<?php echo esc_attr( $track_colors[ $i ][1] ); ?>" viewBox="0 0 24 24"><?php echo $track_icons[ $i ]; ?></svg>
                    </div>
                    <div>
                        <h3 style="font-size: 22px; color: var(--secondary-color); margin-bottom: 12px;"><?php echo esc_html( $t_title ); ?></h3>
                        <p style="color: var(--text-light); font-size: 15px; margin: 0; line-height: 1.7;"><?php echo esc_html( $t_desc ); ?></p>
                        <div style="margin-top: 16px; display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: var(--accent-color); border-radius: 999px; font-size: 12px; font-weight: 600; color: var(--primary-color); letter-spacing: 0.3px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--primary-color); display: inline-block;"></span>
                            In development
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <!-- WAITLIST -->
    <?php
    $wl_action  = vance_get_theme_mod( 'vance_edu_waitlist_action', '' );
    $wl_heading = vance_get_theme_mod( 'vance_edu_waitlist_heading', 'Join the Waitlist' );
    $wl_desc    = vance_get_theme_mod( 'vance_edu_waitlist_desc',    "Be first to hear when patient or practitioner courses go live. We'll send a single email — no spam, easy unsubscribe." );
    $wl_button  = vance_get_theme_mod( 'vance_edu_waitlist_button',  'Notify Me' );
    ?>
    <section id="waitlist" class="section-padding edu-waitlist-section" style="background: linear-gradient(135deg, #008080, #006666);">
        <div class="container" style="text-align: center; color: white;">
            <h2 style="color: white; margin-bottom: 16px;"><?php echo esc_html( $wl_heading ); ?></h2>
            <p class="max-600" style="font-size: 18px; margin: 0 auto 32px; color: rgba(255,255,255,0.92);"><?php echo esc_html( $wl_desc ); ?></p>

            <?php if ( $wl_action ) : ?>
            <form class="edu-waitlist-form" action="<?php echo esc_url( $wl_action ); ?>" method="post" target="_blank" style="display: flex; gap: 12px; max-width: 520px; margin: 0 auto; flex-wrap: wrap; justify-content: center;">
                <input type="email" name="EMAIL" placeholder="Enter your email" required style="flex: 1; min-width: 240px; padding: 14px 20px; border: none; border-radius: var(--radius-md); font-size: 16px;">
                <select name="ROLE" style="padding: 14px 20px; border: none; border-radius: var(--radius-md); font-size: 16px; background: white;">
                    <option value="">I am a…</option>
                    <option value="patient">Patient</option>
                    <option value="caregiver">Caregiver / Family</option>
                    <option value="hcp">Healthcare Professional</option>
                </select>
                <!-- Honeypot field (Mailchimp anti-bot pattern, mirrors footer.php newsletter) -->
                <div style="position: absolute; left: -5000px;" aria-hidden="true">
                    <input type="text" name="b_<?php echo esc_attr( md5( $wl_action ) ); ?>" tabindex="-1" value="">
                </div>
                <button type="submit" class="btn btn-primary edu-waitlist-submit" style="background: white; color: #008080; border: none;"><?php echo esc_html( $wl_button ); ?></button>
            </form>
            <p style="font-size: 13px; color: rgba(255,255,255,0.75); margin-top: 16px;">No spam. One launch email per track.</p>
            <?php else : ?>
            <p style="font-size: 14px; color: rgba(255,255,255,0.75); margin-top: 16px;">
                <em>Waitlist signup will activate once an admin sets the form action URL in Appearance → Customize → Education Page Settings → Waitlist Signup.</em>
            </p>
            <?php endif; ?>
        </div>
    </section>

</main>

<?php get_footer(); ?>
