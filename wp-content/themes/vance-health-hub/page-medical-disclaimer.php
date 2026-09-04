<?php
/**
 * Template Name: Medical Disclaimer
 *
 * Standalone medical disclaimer page. Bind a WP Page (slug: medical-disclaimer)
 * to this template. Linked from the global footer.
 */

get_header(); ?>

<main id="main-content">

<?php
/*
 * The policy-document stylesheet, printed BEFORE this template's own <style>
 * because the order is load-bearing: the box rules below collide with
 * `.legal-wrap p` at equal specificity and have always won by coming later.
 * Idempotent -- the hero render further down will not print it twice.
 */
require_once get_template_directory() . '/inc/legal-hero.php';
vance_legal_hero_styles();
?>

<style>
.legal-emergency-box {
    background: rgba(220,38,38,0.06); border-left: 4px solid #dc2626;
    border-radius: 0 var(--radius-surface, 14px) var(--radius-surface, 14px) 0; padding: 18px 24px; margin: 8px 0 32px;
}
.legal-emergency-box p { margin: 0; font-size: 15px; color: #7f1d1d; font-weight: 600; line-height: 1.7; }
</style>

<?php
/*
 * The policy-document hero. Replaces the dark `legal-hero` band that stood
 * here until 2026-08-28; there is no toggle back to it, by request. The
 * renderer is required in by the prelude at the top of this file.
 */
vance_render_legal_hero( 'disclaimer' );
?>

<div class="legal-wrap">

    <span class="legal-updated">&#128197; Last updated: 1 June 2026</span>

    <h2>General information only</h2>
    <p>
        Vance Medical Hub provides general information and community support for people affected by gastrointestinal conditions. It is not medical advice and is not a substitute for the care of your own healthcare team. Nothing on this site should be used to diagnose or treat a health problem or disease.
    </p>
    <p>
        Always speak to your GP, pharmacist, dietitian or other qualified healthcare professional before making changes to your diet, medication or treatment, and with any questions you may have about a medical condition. Never disregard professional advice, or delay seeking it, because of something you have read or used on this site.
    </p>

    <h2>Our tools and calculators</h2>
    <p>
        The interactive tools on this site (including our calculators and quiz) are general references to help you understand your health and prepare for conversations with your healthcare team. They do not provide a medical diagnosis and are not a substitute for assessment by a qualified healthcare professional. Any results are estimates based only on the information you enter and the general method described, and may not be accurate for your individual circumstances.
    </p>

    <h2>VANCE-Ai</h2>
    <p>
        VANCE-Ai gives general information only. It is automated, can be wrong or out of date, does not know your personal medical history, and does not provide a diagnosis, prescription or treatment plan. Please do not enter information that identifies you. It is not a substitute for advice from your own healthcare team.
    </p>

    <h2>In an emergency</h2>
    <div class="legal-emergency-box">
        <p>
            Do not use this site for urgent or emergency needs. If you feel unwell, or think you may have a medical emergency, call 999 or NHS 111 straight away.
        </p>
    </div>

    <h2>About us</h2>
    <p>
        Vance Medical Hub is operated by Vance Medical Foods Ltd. Where we mention our own or other companies' products we identify this clearly, and we do not provide product recommendations in place of advice from your healthcare professional. Foods for Special Medical Purposes must be used under medical supervision.
    </p>
    <p>
        For related terms, please see our <a href="<?php echo esc_url( home_url( '/terms-of-use/' ) ); ?>">Terms of Use</a> and <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a>.
    </p>

</div><!-- /.legal-wrap -->
</main>

<?php get_footer(); ?>
