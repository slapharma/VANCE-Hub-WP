<?php
/**
 * Template Name: Privacy Policy
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
.legal-wrap h3 {
    font-family: 'Outfit', sans-serif;
    font-size: 16px;
    font-weight: 700;
    color: var(--secondary-color);
    margin: 28px 0 8px;
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
.legal-table {
    width: 100%;
    border-collapse: collapse;
    margin: 16px 0 28px;
    font-size: 14.5px;
}
.legal-table th {
    background: rgba(0,128,128,0.08);
    color: var(--secondary-color);
    font-weight: 700;
    padding: 12px 16px;
    text-align: left;
    border: 1px solid #e2e8f0;
}
.legal-table td {
    padding: 11px 16px;
    border: 1px solid #e2e8f0;
    color: #4a5568;
    vertical-align: top;
    line-height: 1.6;
}
.legal-table tr:nth-child(even) td {
    background: #f8fafc;
}
.legal-rights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
    margin: 20px 0 28px;
}
.legal-right-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 18px 20px;
}
.legal-right-card strong {
    display: block;
    font-size: 13.5px;
    font-weight: 700;
    color: var(--secondary-color);
    margin-bottom: 6px;
}
.legal-right-card span {
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
}
</style>

<!-- HERO -->
<section class="legal-hero">
    <div class="container">
        <div style="max-width: 700px;">
            <span class="tag-label">Legal</span>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: clamp(32px, 5vw, 52px); font-weight: 900; color: white; margin: 16px 0 16px; line-height: 1.1;">
                Privacy Policy
            </h1>
            <p style="color: rgba(255,255,255,0.78); font-size: 18px; line-height: 1.6; max-width: 560px; margin: 0;">
                We are committed to protecting your personal data and your right to privacy. This policy explains how we collect, use, and safeguard your information.
            </p>
        </div>
    </div>
</section>

<!-- CONTENT -->
<div class="legal-wrap">

    <span class="legal-updated">&#128197; Last updated: 1 June 2026</span>

    <!-- TOC -->
    <div class="legal-toc">
        <h3>Contents</h3>
        <ol>
            <li><a href="#who-we-are">Who We Are</a></li>
            <li><a href="#data-collected">Information We Collect</a></li>
            <li><a href="#how-we-use">How We Use Your Information</a></li>
            <li><a href="#legal-basis">Legal Basis for Processing</a></li>
            <li><a href="#sharing">Sharing Your Data</a></li>
            <li><a href="#cookies">Cookies &amp; Tracking</a></li>
            <li><a href="#retention">Data Retention</a></li>
            <li><a href="#your-rights">Your Rights</a></li>
            <li><a href="#security">Data Security</a></li>
            <li><a href="#transfers">International Transfers</a></li>
            <li><a href="#children">Children's Privacy</a></li>
            <li><a href="#policy-changes">Changes to This Policy</a></li>
            <li><a href="#contact-privacy">Contact &amp; Complaints</a></li>
        </ol>
    </div>

    <!-- SECTIONS -->
    <h2 id="who-we-are">1. Who We Are</h2>
    <p>
        Vance Medical Foods Ltd ("Vance Medical", "we", "us", or "our") is the data controller responsible for the personal data collected through Vance Medical Hub at <a href="https://vancehealthhub.co.uk">vancehealthhub.co.uk</a>. We are registered in England and Wales, company number 17157853, registered office 3a Chestnut House, Farm Close, Shenley, Hertfordshire, WD7 9AD, United Kingdom.
    </p>
    <p>
        We are committed to processing your personal data lawfully, fairly and transparently, in accordance with the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018. If you have any questions about this policy or how we handle your data, contact us using the details in Section 13.
    </p>

    <h2 id="data-collected">2. Information We Collect</h2>

    <h3>Information you provide directly</h3>
    <ul>
        <li><strong>Account registration:</strong> name, email address, password (hashed), and account type (patient or healthcare professional)</li>
        <li><strong>Profile information:</strong> optional fields such as condition type, clinical specialty, and profile preferences</li>
        <li><strong>Contact forms:</strong> name, email address, and message content</li>
        <li><strong>Tool inputs:</strong> data you enter into calculators, health trackers, quizzes, and the AI chat feature</li>
    </ul>

    <h3>Information collected automatically</h3>
    <ul>
        <li><strong>Usage data:</strong> pages visited, features used, time on site, click interactions</li>
        <li><strong>Device &amp; technical data:</strong> IP address, browser type and version, operating system, referring URLs</li>
        <li><strong>Cookies:</strong> session and preference data as described in Section 6</li>
    </ul>

    <h3>Health information (special category data)</h3>
    <p>
        Some of the information you choose to give us is health information, which UK GDPR treats as a "special category" needing extra protection. This includes anything you enter into our calculators, quiz or the Ask AI assistant, and any results you choose to save to your account (for example from the malnutrition calculator, blood-test reference tool or omega-3 calculator).
    </p>
    <p>
        We only process this health information with your explicit consent, which you give when you choose to save a result or use a feature that stores it. We do not use it for advertising, and we do not share it with third parties for marketing. You can withdraw your consent and delete your saved results at any time from your dashboard, or by contacting us.
    </p>

    <h3>The Ask AI assistant</h3>
    <p>
        When you use Ask AI, the questions and messages you type are sent to a third-party artificial-intelligence processing provider to generate a response. Your conversation may be stored to operate and improve the service. Please do not enter information that identifies you or another person. Ask AI gives general information only and is described further in our <a href="https://vancehealthhub.co.uk/medical-disclaimer/">Medical Disclaimer</a>.
    </p>

    <h2 id="how-we-use">3. How We Use Your Information</h2>

    <table class="legal-table">
        <thead>
            <tr>
                <th>Purpose</th>
                <th>Examples</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Providing the Platform</strong></td>
                <td>Creating and managing your account; delivering personalised content and tool results</td>
            </tr>
            <tr>
                <td><strong>Communication</strong></td>
                <td>Responding to enquiries; sending service-related updates (e.g. password resets)</td>
            </tr>
            <tr>
                <td><strong>Improving the Platform</strong></td>
                <td>Analysing usage patterns; identifying bugs; developing new features</td>
            </tr>
            <tr>
                <td><strong>Safety &amp; Security</strong></td>
                <td>Detecting and preventing fraud, abuse, or unauthorised access</td>
            </tr>
            <tr>
                <td><strong>Legal Obligations</strong></td>
                <td>Complying with applicable law, regulatory requests, or court orders</td>
            </tr>
        </tbody>
    </table>

    <h2 id="legal-basis">4. Legal Basis for Processing</h2>
    <p>Under UK GDPR, we rely on the following lawful bases:</p>
    <ul>
        <li><strong>Contract performance:</strong> to create and manage your account and deliver the services you have requested.</li>
        <li><strong>Consent:</strong> for optional features and communications, including saving your health-tool results and sending you marketing email. You can withdraw consent at any time.</li>
        <li><strong>Explicit consent (Article 9):</strong> for processing health information, such as the tool results you choose to save. This is separate from, and additional to, the consent above.</li>
        <li><strong>Legitimate interests:</strong> to secure and improve the platform and to send service messages such as password resets, where our interests are not overridden by your rights.</li>
        <li><strong>Legal obligation:</strong> to comply with applicable law.</li>
    </ul>

    <h2 id="sharing">5. Sharing Your Data</h2>
    <p>We do not sell your personal data. We may share your information with:</p>
    <ul>
        <li><strong>Service providers acting as data processors on our behalf</strong>, under written data processing agreements. These include website hosting, website analytics, email and newsletter delivery, and the third-party AI provider that powers Ask AI. Each is contractually required to protect your data and to use it only on our documented instructions.</li>
        <li><strong>Professional advisors</strong> — such as lawyers or auditors — where necessary and under confidentiality obligations</li>
        <li><strong>Regulatory or legal authorities</strong> — where required by law or to protect the rights of Vance Medical or others</li>
    </ul>
    <p>
        Any third-party processors we engage are contractually required to implement appropriate technical and organisational security measures and to process data only on our documented instructions.
    </p>

    <h3>Marketing communications</h3>
    <p>
        We only send you marketing email if you have given us your separate, optional consent, for example by ticking the marketing box when you register. We will never make marketing a condition of creating an account. Every marketing email includes an unsubscribe link, and you can opt out at any time by using it or by contacting us. Withdrawing marketing consent does not affect the service messages we need to send to run your account.
    </p>

    <h2 id="cookies">6. Cookies &amp; Tracking</h2>
    <p>
        We use cookies and similar tracking technologies to operate and improve the Platform. Cookies are small text files stored on your device when you visit our site.
    </p>
    <table class="legal-table">
        <thead>
            <tr>
                <th>Type</th>
                <th>Purpose</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Essential</strong></td>
                <td>Required for the Platform to function — e.g. login sessions, security tokens</td>
            </tr>
            <tr>
                <td><strong>Functional</strong></td>
                <td>Remember your preferences and settings between visits</td>
            </tr>
            <tr>
                <td><strong>Analytics</strong></td>
                <td>Understand how visitors use the Platform (e.g. page views, popular content)</td>
            </tr>
        </tbody>
    </table>
    <p>
        You can control cookies through your browser settings. Disabling non-essential cookies will not affect your ability to use the core Platform, but some features may be limited.
    </p>

    <h2 id="retention">7. Data Retention</h2>
    <p>
        We retain your personal data only for as long as necessary to fulfil the purposes for which it was collected, or as required by law.
    </p>
    <ul>
        <li><strong>Account data</strong> is retained for the life of your account and for up to 2 years after deletion, for legal and audit purposes.</li>
        <li><strong>Saved tool results and health information</strong> are kept while your account is open and until you delete them. You can delete saved results at any time from your dashboard. When you close your account we delete or anonymise them, except where we must keep limited records by law.</li>
        <li><strong>Ask AI conversations</strong> are retained only as long as needed to operate and improve the service, and then deleted or anonymised.</li>
        <li><strong>Consent records</strong> (what you agreed to, and when) are kept for as long as needed to demonstrate compliance, typically for the life of your account plus up to 2 years.</li>
        <li><strong>Contact form data</strong> is retained for up to 12 months.</li>
        <li><strong>Analytics data</strong> is retained in aggregated, anonymised form indefinitely.</li>
    </ul>

    <h2 id="your-rights">8. Your Rights</h2>
    <p>Under UK GDPR, you have the following rights regarding your personal data:</p>
    <div class="legal-rights-grid">
        <div class="legal-right-card">
            <strong>Right of Access</strong>
            <span>Request a copy of the personal data we hold about you</span>
        </div>
        <div class="legal-right-card">
            <strong>Right to Rectification</strong>
            <span>Ask us to correct inaccurate or incomplete data</span>
        </div>
        <div class="legal-right-card">
            <strong>Right to Erasure</strong>
            <span>Request deletion of your data where there is no compelling reason to keep it</span>
        </div>
        <div class="legal-right-card">
            <strong>Right to Restriction</strong>
            <span>Ask us to restrict processing of your data in certain circumstances</span>
        </div>
        <div class="legal-right-card">
            <strong>Right to Portability</strong>
            <span>Receive your data in a structured, machine-readable format</span>
        </div>
        <div class="legal-right-card">
            <strong>Right to Object</strong>
            <span>Object to processing based on legitimate interests or for direct marketing</span>
        </div>
        <div class="legal-right-card">
            <strong>Right to Withdraw Consent</strong>
            <span>Withdraw any consent you have given, such as marketing or saving health results, at any time, without affecting earlier processing</span>
        </div>
    </div>
    <p>
        To exercise any of these rights, please contact us using the details in Section 13. We will respond within one calendar month. We may need to verify your identity before processing your request.
    </p>

    <h2 id="security">9. Data Security</h2>
    <p>
        We implement appropriate technical and organisational measures to protect your personal data against unauthorised access, accidental loss, destruction, or alteration. These include encrypted storage, access controls, and regular security assessments.
    </p>
    <p>
        While we take every reasonable precaution, no method of transmission over the internet is completely secure. If you become aware of any security vulnerability relating to our Platform, please contact us promptly at <a href="mailto:info@vancehealthhub.co.uk">info@vancehealthhub.co.uk</a>.
    </p>

    <h2 id="transfers">10. International Transfers</h2>
    <p>
        Some of our third-party service providers may process data outside the United Kingdom. Where this occurs, we ensure appropriate safeguards are in place — such as the International Data Transfer Agreement (IDTA) or adequacy decisions — to ensure your data receives equivalent protection.
    </p>

    <h2 id="children">11. Children's Privacy</h2>
    <p>
        Vance Medical Hub is intended for members of the public aged 18 and over in the United Kingdom. It is not directed at children, and we do not knowingly collect personal data from anyone under 18. If you believe someone under 18 has given us personal information, please contact us and we will delete it promptly.
    </p>

    <h2 id="policy-changes">12. Changes to This Policy</h2>
    <p>
        We may update this Privacy Policy periodically. The "Last updated" date at the top of this page will reflect when changes were made. Where changes are material, we will notify registered users by email or via a prominent notice on the Platform.
    </p>
    <p>
        We encourage you to review this Policy periodically to stay informed about how we are protecting your information.
    </p>

    <h2 id="contact-privacy">13. Contact &amp; Complaints</h2>
    <p>
        If you have any questions, concerns, or requests relating to this Privacy Policy or our data practices, please contact us:
    </p>

    <div class="legal-contact-box">
        <h3>Vance Medical Foods Ltd</h3>
        <p><strong>Registered office:</strong> 3a Chestnut House, Farm Close, Shenley, Hertfordshire, WD7 9AD, United Kingdom (company no. 17157853)</p>
        <p><strong>Email:</strong> <a href="mailto:info@vancehealthhub.co.uk">info@vancehealthhub.co.uk</a></p>
        <p><strong>Website:</strong> <a href="https://vancehealthhub.co.uk">vancehealthhub.co.uk</a></p>
        <p style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(0,128,128,0.15); font-size: 14px; color: #718096;">
            <strong>Supervisory Authority:</strong> If you are not satisfied with our response, you have the right to lodge a complaint with the UK Information Commissioner's Office (ICO):<br>
            <a href="https://ico.org.uk" target="_blank" rel="noopener">ico.org.uk</a> &nbsp;|&nbsp; Helpline: 0303 123 1113
        </p>
    </div>

</div><!-- /.legal-wrap -->
</main>

<?php get_footer(); ?>
