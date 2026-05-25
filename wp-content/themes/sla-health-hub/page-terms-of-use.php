<?php
/**
 * Template Name: Terms of Use
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
.legal-wrap {
    max-width: 760px;
    margin: 0 auto;
    padding: 64px 24px 100px;
}
.legal-wrap h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 22px;
    font-weight: 800;
    color: var(--secondary-color);
    margin: 48px 0 12px;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba(0,128,128,0.15);
}
.legal-wrap h2:first-of-type {
    margin-top: 32px;
}
.legal-wrap p {
    color: #4a5568;
    line-height: 1.85;
    font-size: 15.5px;
    margin: 0 0 16px;
}
.legal-wrap ul {
    color: #4a5568;
    line-height: 1.85;
    font-size: 15.5px;
    margin: 0 0 16px;
    padding-left: 24px;
}
.legal-wrap ul li {
    margin-bottom: 8px;
}
.legal-wrap a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}
.legal-wrap a:hover {
    text-decoration: underline;
}
.legal-updated {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(0,128,128,0.08);
    color: var(--primary-color);
    font-size: 13px;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid rgba(0,128,128,0.2);
    margin-bottom: 32px;
    letter-spacing: 0.3px;
}
.legal-toc {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 28px 32px;
    margin-bottom: 48px;
}
.legal-toc h3 {
    font-family: 'Outfit', sans-serif;
    font-size: 14px;
    font-weight: 800;
    color: var(--secondary-color);
    margin: 0 0 16px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}
.legal-toc ol {
    margin: 0;
    padding-left: 20px;
    color: #64748b;
    font-size: 14px;
    line-height: 2;
}
.legal-toc ol a {
    color: #64748b;
    text-decoration: none;
    font-weight: 500;
}
.legal-toc ol a:hover {
    color: var(--primary-color);
}
.legal-contact-box {
    background: linear-gradient(135deg, rgba(0,128,128,0.06), rgba(0,128,128,0.02));
    border: 1px solid rgba(0,128,128,0.2);
    border-radius: 12px;
    padding: 32px 36px;
    margin-top: 56px;
}
.legal-contact-box h3 {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: 18px;
    color: var(--secondary-color);
    margin: 0 0 12px;
}
.legal-contact-box p {
    margin: 0 0 8px;
    font-size: 15px;
}
.legal-disclaimer-box {
    background: rgba(248,100,9,0.06);
    border-left: 4px solid #f86409;
    border-radius: 0 8px 8px 0;
    padding: 20px 24px;
    margin: 24px 0 32px;
}
.legal-disclaimer-box p {
    margin: 0;
    font-size: 14.5px;
    color: #7c4a1e;
    font-weight: 500;
    line-height: 1.7;
}
</style>

<!-- HERO -->
<section class="legal-hero">
    <div class="container">
        <div style="max-width: 700px;">
            <span class="tag-label">Legal</span>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: clamp(32px, 5vw, 52px); font-weight: 900; color: white; margin: 16px 0 16px; line-height: 1.1;">
                Terms of Use
            </h1>
            <p style="color: rgba(255,255,255,0.78); font-size: 18px; line-height: 1.6; max-width: 560px; margin: 0;">
                Please read these terms carefully before using the Gastro Health Hub platform. By accessing our service, you agree to be bound by these terms.
            </p>
        </div>
    </div>
</section>

<!-- CONTENT -->
<div class="legal-wrap">

    <span class="legal-updated">&#128197; Last updated: 17 April 2025</span>

    <!-- TOC -->
    <div class="legal-toc">
        <h3>Contents</h3>
        <ol>
            <li><a href="#acceptance">Acceptance of Terms</a></li>
            <li><a href="#about">About This Platform</a></li>
            <li><a href="#medical-disclaimer">Medical Disclaimer</a></li>
            <li><a href="#accounts">User Accounts</a></li>
            <li><a href="#acceptable-use">Acceptable Use</a></li>
            <li><a href="#intellectual-property">Intellectual Property</a></li>
            <li><a href="#third-party">Third-Party Links &amp; Services</a></li>
            <li><a href="#warranties">Disclaimer of Warranties</a></li>
            <li><a href="#liability">Limitation of Liability</a></li>
            <li><a href="#changes">Changes to These Terms</a></li>
            <li><a href="#governing-law">Governing Law</a></li>
            <li><a href="#contact-legal">Contact Us</a></li>
        </ol>
    </div>

    <!-- SECTIONS -->
    <h2 id="acceptance">1. Acceptance of Terms</h2>
    <p>
        These Terms of Use ("Terms") govern your access to and use of the Gastro Health Hub website and platform located at <a href="https://gastrohealthhub.com">gastrohealthhub.com</a> ("Platform"), operated by Vance Medical Foods Ltd ("Vance Medical", "we", "us", or "our").
    </p>
    <p>
        By accessing or using the Platform in any way, you confirm that you have read, understood, and agree to be bound by these Terms, together with our <a href="/privacy-policy/">Privacy Policy</a>. If you do not agree, please discontinue use of the Platform immediately.
    </p>

    <h2 id="about">2. About This Platform</h2>
    <p>
        Gastro Health Hub is an educational health information platform operated by Vance Medical Foods Ltd. It is designed to provide evidence-based content relating to gastrointestinal health, inflammatory bowel disease (IBD), clinical nutrition, and related topics, for both patients and registered healthcare professionals.
    </p>
    <p>
        The Platform includes interactive tools such as health calculators, an AI-assisted chat feature, personalised dashboards, and curated clinical content. These features are provided for educational and informational purposes only.
    </p>

    <h2 id="medical-disclaimer">3. Medical Disclaimer</h2>
    <div class="legal-disclaimer-box">
        <p>
            <strong>Important:</strong> The content on this Platform is provided for general information and educational purposes only. It does not constitute medical advice, diagnosis, or treatment. Always consult a qualified healthcare professional before making any medical decision or changing your treatment plan.
        </p>
    </div>
    <p>
        The AI-assisted features on this Platform are informational tools only. They are not a substitute for professional clinical judgement. No patient–doctor relationship is created by your use of the Platform or its tools.
    </p>
    <p>
        In a medical emergency, call your local emergency services immediately. Do not rely on this Platform for urgent medical guidance.
    </p>

    <h2 id="accounts">4. User Accounts</h2>
    <p>
        Certain features of the Platform require you to register for an account. When you register, you agree to:
    </p>
    <ul>
        <li>Provide accurate, current, and complete information</li>
        <li>Maintain and promptly update your account information</li>
        <li>Keep your password confidential and not share it with third parties</li>
        <li>Notify us immediately of any unauthorised use of your account</li>
        <li>Accept responsibility for all activity that occurs under your account</li>
    </ul>
    <p>
        We reserve the right to suspend or terminate accounts that violate these Terms, contain false information, or are involved in conduct we deem harmful to the Platform or its users.
    </p>

    <h2 id="acceptable-use">5. Acceptable Use</h2>
    <p>You agree not to use the Platform to:</p>
    <ul>
        <li>Post, transmit, or distribute content that is unlawful, harmful, offensive, defamatory, or misleading</li>
        <li>Impersonate any person, healthcare professional, or entity</li>
        <li>Harvest or collect personal data about other users without their consent</li>
        <li>Attempt to gain unauthorised access to any part of the Platform, its servers, or connected systems</li>
        <li>Introduce viruses, malware, or other malicious code</li>
        <li>Use the Platform for any commercial purpose without our prior written consent</li>
        <li>Reproduce, distribute, or create derivative works from Platform content without authorisation</li>
        <li>Engage in any conduct that disrupts, degrades, or interferes with the Platform's operation</li>
    </ul>

    <h2 id="intellectual-property">6. Intellectual Property</h2>
    <p>
        All content on this Platform — including but not limited to text, graphics, logos, icons, images, audio clips, tool interfaces, and software — is the property of Vance Medical Foods Ltd or its content licensors and is protected by UK and international copyright, trade mark, and other intellectual property laws.
    </p>
    <p>
        You are granted a limited, non-exclusive, non-transferable licence to access and use the Platform for personal, non-commercial purposes. No content may be copied, reproduced, republished, uploaded, modified, transmitted, or distributed without our prior written permission, except as expressly permitted by these Terms.
    </p>

    <h2 id="third-party">7. Third-Party Links &amp; Services</h2>
    <p>
        The Platform may contain links to third-party websites or services. These are provided for your convenience only. Vance Medical does not endorse, control, or take responsibility for the content, accuracy, or practices of any third-party site. Accessing third-party links is entirely at your own risk.
    </p>
    <p>
        The Platform uses third-party services including analytics tools and AI model providers. Use of these services is subject to their respective terms and privacy policies.
    </p>

    <h2 id="warranties">8. Disclaimer of Warranties</h2>
    <p>
        The Platform and its content are provided on an "as is" and "as available" basis, without warranties of any kind — express or implied — including but not limited to implied warranties of merchantability, fitness for a particular purpose, accuracy, or non-infringement.
    </p>
    <p>
        We do not warrant that the Platform will be uninterrupted, error-free, secure, or free from viruses or other harmful components. We reserve the right to modify, suspend, or discontinue any part of the Platform at any time without notice.
    </p>

    <h2 id="liability">9. Limitation of Liability</h2>
    <p>
        To the fullest extent permitted by applicable law, Vance Medical Foods Ltd and its affiliates, directors, employees, and licensors shall not be liable for any indirect, incidental, special, consequential, or punitive damages, or any loss of profits, data, goodwill, or other intangible losses arising out of or in connection with your use of, or inability to use, the Platform.
    </p>
    <p>
        In no event shall our aggregate liability exceed the greater of £100 or the amount you have paid us in the twelve months preceding the claim. Nothing in these Terms excludes or limits liability for death or personal injury caused by negligence, fraud, or any other liability that cannot be excluded under English law.
    </p>

    <h2 id="changes">10. Changes to These Terms</h2>
    <p>
        We may update these Terms from time to time to reflect changes in law, technology, or our services. When we do, we will update the "Last updated" date at the top of this page. Where changes are material, we will make reasonable efforts to notify registered users by email or via an in-platform notice.
    </p>
    <p>
        Your continued use of the Platform after any change constitutes your acceptance of the revised Terms.
    </p>

    <h2 id="governing-law">11. Governing Law</h2>
    <p>
        These Terms are governed by and construed in accordance with the laws of England and Wales. Any dispute arising under or in connection with these Terms shall be subject to the exclusive jurisdiction of the courts of England and Wales.
    </p>

    <h2 id="contact-legal">12. Contact Us</h2>
    <p>
        If you have any questions about these Terms, please contact us:
    </p>

    <div class="legal-contact-box">
        <h3>Vance Medical Foods Ltd</h3>
        <p><strong>Email:</strong> <a href="mailto:info@gastrohealthhub.com">info@gastrohealthhub.com</a></p>
        <p><strong>Phone:</strong> +44 (0)1628 526 005</p>
        <p><strong>Website:</strong> <a href="https://gastrohealthhub.com">gastrohealthhub.com</a></p>
        <p style="margin-top:16px; font-size:14px; color:#718096;">
            Vance Medical Foods Ltd is registered in England and Wales. Registered office details are available on request.
        </p>
    </div>

</div><!-- /.legal-wrap -->
</main>

<?php get_footer(); ?>
