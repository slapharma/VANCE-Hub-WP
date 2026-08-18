<?php
/**
 * Template Name: Accessibility
 *
 * Accessibility statement page. Bind a WP Page (slug: accessibility) to this
 * template. Linked from the global footer. Update the contact email and the
 * conformance status once a WCAG 2.2 AA audit has been completed.
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
.legal-contact-box {
    background: linear-gradient(135deg, rgba(0,128,128,0.06), rgba(0,128,128,0.02));
    border: 1px solid rgba(0,128,128,0.2); border-radius: 12px; padding: 32px 36px; margin-top: 56px;
}
.legal-contact-box h3 { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 18px; color: var(--secondary-color); margin: 0 0 12px; }
.legal-contact-box p { margin: 0 0 8px; font-size: 15px; }
</style>

<section class="legal-hero">
    <div class="container">
        <div style="max-width: 700px;">
            <span class="tag-label">Accessibility</span>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: clamp(32px, 5vw, 52px); font-weight: 900; color: white; margin: 16px 0 16px; line-height: 1.1;">
                Accessibility Statement
            </h1>
            <p style="color: rgba(255,255,255,0.78); font-size: 18px; line-height: 1.6; max-width: 560px; margin: 0;">
                We want everyone to be able to use Vance Medical Hub.
            </p>
        </div>
    </div>
</section>

<div class="legal-wrap">

    <span class="legal-updated">&#128197; Last updated: 1 June 2026</span>

    <h2>Our commitment</h2>
    <p>
        Vance Medical Hub is committed to making its website accessible to as many people as possible, in line with the Equality Act 2010. We aim to meet the Web Content Accessibility Guidelines (WCAG) 2.2 at level AA.
    </p>

    <h2>What we are doing</h2>
    <ul>
        <li>Using clear, plain-English language wherever we can</li>
        <li>Providing text alternatives for meaningful images</li>
        <li>Designing for keyboard navigation and readable colour contrast</li>
        <li>Supporting text resizing and responsive layouts on mobile and desktop</li>
    </ul>

    <h2>Known limitations</h2>
    <p>
        Some content, including older articles and our interactive tools, may not yet fully meet WCAG 2.2 AA. We are reviewing these and working to improve them. If something does not work for you, please tell us and we will do our best to help and to provide the information in another format.
    </p>

    <h2>Tell us about a problem</h2>
    <p>
        If you experience any difficulty using this site, or need information in a different format, please contact us and we will respond as quickly as we can.
    </p>

    <div class="legal-contact-box">
        <h3>Contact us</h3>
        <p><strong>Email:</strong> <a href="mailto:info@vancehealthhub.co.uk">info@vancehealthhub.co.uk</a></p>
        <p style="margin-top:16px; font-size:14px; color:#718096;">
            We aim to acknowledge accessibility queries within five working days.
        </p>
    </div>

</div><!-- /.legal-wrap -->
</main>

<?php get_footer(); ?>
