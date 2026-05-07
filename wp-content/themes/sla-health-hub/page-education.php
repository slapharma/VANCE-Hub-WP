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
 * All copy + colours read through vance_get_theme_mod() — override defaults
 * via Appearance → Customize → Education Page Settings.
 */
get_header();

// ── Hero (compact 350px banner, archive-style) ─────────────────────────────
$hero_bg      = vance_get_theme_mod( 'vance_edu_hero_bg', get_template_directory_uri() . '/assets/img/education_hero.png' );
$hero_tag     = vance_get_theme_mod( 'vance_edu_hero_tag',   'Elevate Your Expertise' );
$hero_title   = vance_get_theme_mod( 'vance_edu_hero_title', 'Education &amp; Courses' );
$hero_overlay = max( 0, min( 100, absint( vance_get_theme_mod( 'vance_edu_hero_overlay', 75 ) ) ) ) / 100;
$hero_overlay_bottom  = min( 1, $hero_overlay + 0.10 );
$hero_eyebrow_color   = vance_get_theme_mod( 'vance_edu_hero_eyebrow_color', '#008080' );
$hero_title_color     = vance_get_theme_mod( 'vance_edu_hero_title_color', '#ffffff' );

// ── Tracks ─────────────────────────────────────────────────────────────────
$tracks_bg          = vance_get_theme_mod( 'vance_edu_tracks_bg',          'var(--accent-color)' );
$tracks_title_color = vance_get_theme_mod( 'vance_edu_tracks_title_color', '' );
$tracks_text_color  = vance_get_theme_mod( 'vance_edu_tracks_text_color',  '' );
$tracks_eyebrow     = vance_get_theme_mod( 'vance_edu_tracks_eyebrow',     'Two Tracks. One Standard.' );
$tracks_heading     = vance_get_theme_mod( 'vance_edu_tracks_heading',     'Built for the people who need it most' );
$tracks_desc        = vance_get_theme_mod( 'vance_edu_tracks_desc',        'Every course is co-developed with practising clinicians and reviewed by patient advisors before release.' );

// ── Waitlist ────────────────────────────────────────────────────────────────
$wl_action      = vance_get_theme_mod( 'vance_edu_waitlist_action', '' );
$wl_heading     = vance_get_theme_mod( 'vance_edu_waitlist_heading', 'Join the Waitlist' );
$wl_desc        = vance_get_theme_mod( 'vance_edu_waitlist_desc',    "Be first to hear when patient or practitioner courses go live. We'll send a single email — no spam, easy unsubscribe." );
$wl_button      = vance_get_theme_mod( 'vance_edu_waitlist_button',  'Notify Me' );
$wl_bg_from     = vance_get_theme_mod( 'vance_edu_waitlist_bg_from', '#008080' );
$wl_bg_to       = vance_get_theme_mod( 'vance_edu_waitlist_bg_to',   '#006666' );
$wl_text_color  = vance_get_theme_mod( 'vance_edu_waitlist_text_color', '#ffffff' );

$track_defaults = array(
    1 => array( 'Patient Courses', "Self-paced modules on living with IBD: nutrition fundamentals, symptom tracking, mealtime confidence, and working with your care team. Designed in plain English with downloadable worksheets." ),
    2 => array( 'Practitioner Courses', "CPD-accredited deep dives on FSMP integration, Omega-3 dosing, malnutrition screening, and translating evidence into protocols. Built for gastroenterologists, dietitians, GPs, and pharmacists." ),
);
$track_colors = array(
    1 => array( '#008080', '#ffffff' ),
    2 => array( '#aedbdb', '#008080' ),
);
$track_icons = array(
    1 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
    2 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>',
);
?>

<main id="main-content">

    <!-- HERO -->
    <section class="hero edu-hero" style="height: 350px; min-height: 0; display: flex; align-items: center; padding: 0; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-image: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $hero_overlay ); ?>), rgba(20,40,70,<?php echo esc_attr( $hero_overlay_bottom ); ?>)), url('<?php echo esc_url( $hero_bg ); ?>'); background-position: center center; background-size: cover; background-repeat: no-repeat; z-index: 1;"></div>
        <div class="container" style="position: relative; z-index: 2; width: 100%;">
            <div class="hero-content" style="max-width: 800px;">
                <span class="eyebrow" style="color: <?php echo esc_attr( $hero_eyebrow_color ); ?>; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; font-size: 14px; display: block; margin-bottom: 10px;"><?php echo esc_html( $hero_tag ); ?></span>
                <h1 class="entry-title" style="font-size: 56px; color: <?php echo esc_attr( $hero_title_color ); ?>; font-weight: 700; margin: 0; line-height: 1.1;"><?php echo wp_kses_post( $hero_title ); ?></h1>
            </div>
        </div>
    </section>

    <!-- INTRO — cloned from page-tools-resources.php, same shape + 64px padding -->
    <?php
    $intro_title    = vance_get_theme_mod( 'vance_edu_intro_title',         'Courses crafted by clinicians, for life with IBD' );
    $intro_desc     = vance_get_theme_mod( 'vance_edu_intro_desc',          'Self-paced patient courses and CPD-accredited practitioner modules — written, reviewed, and field-tested by gastroenterologists and dietitians. Pick a track below to be notified when enrolment opens.' );
    $intro_eyebrow  = vance_get_theme_mod( 'vance_edu_intro_eyebrow',       'Coming Soon' );
    $intro_bg       = vance_get_theme_mod( 'vance_edu_intro_bg_color',      '#ffffff' );
    $intro_text_col = vance_get_theme_mod( 'vance_edu_intro_text_color',    '' ); // empty → use theme defaults
    $intro_eyb_bg   = vance_get_theme_mod( 'vance_edu_intro_eyebrow_bg',    'rgba(0,128,128,0.08)' );
    $intro_eyb_col  = vance_get_theme_mod( 'vance_edu_intro_eyebrow_color', '#008080' );
    // 64px padding = 20% less than the standard `.section-padding` (80px) — same as tools-resources.
    ?>
    <section class="edu-intro-section" style="background: <?php echo esc_attr( $intro_bg ); ?>; padding: 64px 0;">
        <div class="container">
            <div class="text-center max-600 edu-intro-inner" style="margin: 0 auto 0;">
                <span class="edu-intro-eyebrow" style="display: inline-block; padding: 6px 16px; background: <?php echo esc_attr( $intro_eyb_bg ); ?>; color: <?php echo esc_attr( $intro_eyb_col ); ?>; font-size: 12px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; margin-bottom: 14px; border-radius: 0;">
                    <?php echo esc_html( $intro_eyebrow ); ?>
                </span>
                <h2 class="edu-intro-title" style="<?php echo $intro_text_col ? 'color: ' . esc_attr( $intro_text_col ) . ';' : 'color: var(--secondary-color);'; ?> margin: 0 0 12px;"><?php echo esc_html( $intro_title ); ?></h2>
                <p class="edu-intro-desc" style="<?php echo $intro_text_col ? 'color: ' . esc_attr( $intro_text_col ) . '; opacity: 0.85;' : 'color: var(--text-light);'; ?> margin: 0;"><?php echo esc_html( $intro_desc ); ?></p>
            </div>
        </div>
    </section>

    <!-- COURSE TRACKS — clickable cards open the waitlist popup -->
    <section id="tracks" class="section-padding edu-tracks-section" style="background: <?php echo esc_attr( $tracks_bg ); ?>;">
        <div class="container">
            <div class="text-center max-600 margin-b-60">
                <span class="tag-section"><?php echo esc_html( $tracks_eyebrow ); ?></span>
                <h2 style="<?php echo $tracks_title_color ? 'color:' . esc_attr( $tracks_title_color ) . ';' : 'color: var(--secondary-color);'; ?>"><?php echo esc_html( $tracks_heading ); ?></h2>
                <p style="<?php echo $tracks_text_color ? 'color:' . esc_attr( $tracks_text_color ) . ';' : 'color: var(--text-light);'; ?>"><?php echo esc_html( $tracks_desc ); ?></p>
            </div>
            <div class="grid-2 edu-track-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px;">
                <?php for ( $i = 1; $i <= 2; $i++ ) :
                    $t_title = vance_get_theme_mod( "vance_edu_track{$i}_title", $track_defaults[ $i ][0] );
                    $t_desc  = vance_get_theme_mod( "vance_edu_track{$i}_desc",  $track_defaults[ $i ][1] );
                ?>
                <button type="button" class="edu-track-card" data-edu-waitlist-trigger data-edu-track="<?php echo esc_attr( $t_title ); ?>" style="display: flex; gap: 24px; padding: 36px; background: white; border: 1px solid transparent; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); align-items: flex-start; cursor: pointer; text-align: left; font: inherit; transition: transform 0.18s, box-shadow 0.18s, border-color 0.18s; width: 100%;">
                    <div class="edu-track-icon" style="flex-shrink: 0; width: 64px; height: 64px; background: <?php echo esc_attr( $track_colors[ $i ][0] ); ?>; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                        <svg width="32" height="32" fill="none" stroke="<?php echo esc_attr( $track_colors[ $i ][1] ); ?>" viewBox="0 0 24 24"><?php echo $track_icons[ $i ]; ?></svg>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="font-size: 22px; <?php echo $tracks_title_color ? 'color:' . esc_attr( $tracks_title_color ) . ';' : 'color: var(--secondary-color);'; ?> margin-bottom: 12px;"><?php echo esc_html( $t_title ); ?></h3>
                        <p style="<?php echo $tracks_text_color ? 'color:' . esc_attr( $tracks_text_color ) . ';' : 'color: var(--text-light);'; ?> font-size: 15px; margin: 0; line-height: 1.7;"><?php echo esc_html( $t_desc ); ?></p>
                        <div style="margin-top: 16px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <span style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: var(--accent-color); border-radius: 999px; font-size: 12px; font-weight: 600; color: var(--primary-color); letter-spacing: 0.3px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--primary-color); display: inline-block;"></span>
                                In development
                            </span>
                            <span style="font-size: 13px; color: var(--primary-color); font-weight: 600;">Tap to join the waitlist →</span>
                        </div>
                    </div>
                </button>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <!-- WAITLIST (full section, also reachable from in-page buttons) -->
    <section id="waitlist" class="section-padding edu-waitlist-section" style="background: linear-gradient(135deg, <?php echo esc_attr( $wl_bg_from ); ?>, <?php echo esc_attr( $wl_bg_to ); ?>);">
        <div class="container" style="text-align: center; color: <?php echo esc_attr( $wl_text_color ); ?>;">
            <h2 style="color: <?php echo esc_attr( $wl_text_color ); ?>; margin-bottom: 16px;"><?php echo esc_html( $wl_heading ); ?></h2>
            <p class="max-600" style="font-size: 18px; margin: 0 auto 32px; color: <?php echo esc_attr( $wl_text_color ); ?>; opacity: 0.92;"><?php echo esc_html( $wl_desc ); ?></p>

            <?php if ( $wl_action ) : ?>
            <form class="edu-waitlist-form" action="<?php echo esc_url( $wl_action ); ?>" method="post" target="_blank" style="display: flex; gap: 12px; max-width: 520px; margin: 0 auto; flex-wrap: wrap; justify-content: center;">
                <input type="email" name="EMAIL" placeholder="Enter your email" required style="flex: 1; min-width: 240px; padding: 14px 20px; border: none; border-radius: var(--radius-md); font-size: 16px;">
                <select name="ROLE" style="padding: 14px 20px; border: none; border-radius: var(--radius-md); font-size: 16px; background: white;">
                    <option value="">I am a…</option>
                    <option value="patient">Patient</option>
                    <option value="caregiver">Caregiver / Family</option>
                    <option value="hcp">Healthcare Professional</option>
                </select>
                <div style="position: absolute; left: -5000px;" aria-hidden="true">
                    <input type="text" name="b_<?php echo esc_attr( md5( $wl_action ) ); ?>" tabindex="-1" value="">
                </div>
                <button type="submit" class="btn btn-primary edu-waitlist-submit" style="background: white; color: <?php echo esc_attr( $wl_bg_from ); ?>; border: none;"><?php echo esc_html( $wl_button ); ?></button>
            </form>
            <p style="font-size: 13px; opacity: 0.78; margin-top: 16px;">No spam. One launch email per track.</p>
            <?php else : ?>
            <p style="font-size: 14px; opacity: 0.78; margin-top: 16px;">
                <em>Waitlist signup will activate once an admin sets the form action URL in Appearance → Customize → Education Page Settings → Waitlist Signup.</em>
            </p>
            <?php endif; ?>
        </div>
    </section>

</main>

<!-- WAITLIST POPUP — rendered once, opened by any [data-edu-waitlist-trigger] click -->
<div class="edu-wl-modal" id="edu-wl-modal" role="dialog" aria-modal="true" aria-labelledby="edu-wl-title" style="display: none; position: fixed; inset: 0; background: rgba(10,25,41,0.78); backdrop-filter: blur(4px); z-index: 100050; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: white; max-width: 460px; width: 100%; padding: 36px 32px; box-shadow: 0 30px 80px rgba(10,25,41,0.30); border-top: 4px solid <?php echo esc_attr( $wl_bg_from ); ?>; position: relative;">
        <button type="button" id="edu-wl-close" aria-label="Close" style="position: absolute; top: 12px; right: 14px; background: transparent; border: none; font-size: 26px; color: #94a3b8; cursor: pointer; line-height: 1; padding: 4px 8px;">×</button>
        <h2 id="edu-wl-title" style="font-family: 'Outfit', sans-serif; font-size: 24px; color: var(--secondary-color); margin: 0 0 8px;"><?php echo esc_html( $wl_heading ); ?></h2>
        <p id="edu-wl-context" style="color: var(--text-light); font-size: 14px; margin: 0 0 18px; line-height: 1.6;"><?php echo esc_html( $wl_desc ); ?></p>

        <?php if ( $wl_action ) : ?>
        <form action="<?php echo esc_url( $wl_action ); ?>" method="post" target="_blank" autocomplete="on" novalidate>
            <label style="display: block; font-size: 12px; font-weight: 600; color: var(--secondary-color); margin: 14px 0 6px; letter-spacing: 0.3px; text-transform: uppercase;">Email</label>
            <input type="email" name="EMAIL" required placeholder="you@example.com" style="width: 100%; padding: 12px 14px; border: 1px solid #E2E8F0; font-size: 15px; background: #fff; box-sizing: border-box;">

            <label style="display: block; font-size: 12px; font-weight: 600; color: var(--secondary-color); margin: 14px 0 6px; letter-spacing: 0.3px; text-transform: uppercase;">I am a…</label>
            <select name="ROLE" style="width: 100%; padding: 12px 14px; border: 1px solid #E2E8F0; font-size: 15px; background: #fff; box-sizing: border-box;">
                <option value="">Select…</option>
                <option value="patient">Patient</option>
                <option value="caregiver">Caregiver / Family</option>
                <option value="hcp">Healthcare Professional</option>
            </select>

            <input type="hidden" name="TRACK" id="edu-wl-track-input" value="">
            <div style="position: absolute; left: -5000px;" aria-hidden="true">
                <input type="text" name="b_<?php echo esc_attr( md5( $wl_action ) ); ?>" tabindex="-1" value="">
            </div>

            <button type="submit" style="width: 100%; padding: 14px; margin-top: 20px; background: <?php echo esc_attr( $wl_bg_from ); ?>; color: white; border: none; font-size: 15px; font-weight: 700; cursor: pointer; letter-spacing: 0.4px; text-transform: uppercase;">
                <?php echo esc_html( $wl_button ); ?>
            </button>
            <p style="font-size: 11px; color: var(--text-light); margin: 12px 0 0; line-height: 1.55;">No spam. One launch email per track. <a href="/privacy-policy/" style="color: var(--primary-color);">Privacy Policy</a>.</p>
        </form>
        <?php else : ?>
        <p style="font-size: 13px; color: var(--text-light); margin: 0;"><em>Waitlist signup is being configured — please check back soon.</em></p>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('edu-wl-modal');
    if (!modal) return;
    var closeBtn = document.getElementById('edu-wl-close');
    var trackInp = document.getElementById('edu-wl-track-input');
    var ctxEl    = document.getElementById('edu-wl-context');

    function open(track) {
        if (track && trackInp) trackInp.value = track;
        if (track && ctxEl) {
            ctxEl.textContent = "We'll email you when " + track + " go live. One launch email per track — no spam.";
        }
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function close() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    document.querySelectorAll('[data-edu-waitlist-trigger]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            open(el.getAttribute('data-edu-track') || '');
        });
    });
    closeBtn.addEventListener('click', close);
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') close();
    });
})();
</script>

<style>
.edu-track-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(10,25,41,0.10);
    border-color: var(--primary-color) !important;
}
.edu-track-card:focus-visible {
    outline: 2px solid var(--primary-color);
    outline-offset: 4px;
}
</style>

<?php get_footer(); ?>
