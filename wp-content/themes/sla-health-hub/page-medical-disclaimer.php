<?php
/**
 * Template Name: Medical Disclaimer
 *
 * Standalone medical disclaimer page. Bind a WP Page (slug: medical-disclaimer)
 * to this template. Linked from the global footer.
 */

get_header(); ?>

<main id="main-content">

<style>
.legal-hero {
    background: linear-gradient(135deg, rgba(10,25,41,0.92) 0%, rgba(0,80,80,0.88) 100%),
                url('<?php echo get_template_directory_uri(); ?>/assets/img/news_hero.png') no-repeat center center;
    background-size: cover;
    padding: 100px 0 80px;
    color: white;
}
.legal-wrap { max-width: 760px; margin: 0 auto; padding: 64px 24px 100px; }
.legal-wrap h2 {
    font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800;
    color: var(--secondary-color); margin: 48px 0 12px; padding-bottom: 10px;
    border-bottom: 2px solid rgba(0,128,128,0.15);
}
.legal-wrap h2:first-of-type { margin-top: 32px; }
.legal-wrap p { color: #4a5568; line-height: 1.85; font-size: 15.5px; margin: 0 0 16px; }
.legal-wrap ul { color: #4a5568; line-height: 1.85; font-size: 15.5px; margin: 0 0 16px; padding-left: 24px; }
.legal-wrap ul li { margin-bottom: 8px; }
.legal-wrap a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
.legal-wrap a:hover { text-decoration: underline; }
.legal-updated {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(0,128,128,0.08); color: var(--primary-color);
    font-size: 13px; font-weight: 700; padding: 6px 14px; border-radius: 20px;
    border: 1px solid rgba(0,128,128,0.2); margin-bottom: 32px; letter-spacing: 0.3px;
}
.legal-emergency-box {
    background: rgba(220,38,38,0.06); border-left: 4px solid #dc2626;
    border-radius: 0 8px 8px 0; padding: 18px 24px; margin: 8px 0 32px;
}
.legal-emergency-box p { margin: 0; font-size: 15px; color: #7f1d1d; font-weight: 600; line-height: 1.7; }
</style>

<section class="legal-hero">
    <div class="container">
        <div style="max-width: 700px;">
            <span class="tag-label">Important</span>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: clamp(32px, 5vw, 52px); font-weight: 900; color: white; margin: 16px 0 16px; line-height: 1.1;">
                Medical Disclaimer
            </h1>
            <p style="color: rgba(255,255,255,0.78); font-size: 18px; line-height: 1.6; max-width: 560px; margin: 0;">
                Please read this before using Vance Medical Hub, its articles, tools or AI assistant.
            </p>
        </div>
    </div>
</section>

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

    <h2>Our AI assistant</h2>
    <p>
        Our AI assistant gives general information only. It is automated, can be wrong or out of date, does not know your personal medical history, and does not provide a diagnosis, prescription or treatment plan. Please do not enter information that identifies you. It is not a substitute for advice from your own healthcare team.
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
