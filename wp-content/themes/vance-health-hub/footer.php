    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <?php 
            $nl_action = vance_get_theme_mod('vance_newsletter_action');
            if ($nl_action):
                $nl_head = vance_get_theme_mod('vance_newsletter_heading', 'Join the Hub');
                $nl_desc = vance_get_theme_mod('vance_newsletter_desc', 'Get the latest clinical reviews and tools.');
            ?>
            <div class="newsletter-bar" style="background: #0A1929; border-radius: var(--radius-surface, 24px); padding: 40px; margin-bottom: 60px; color: white; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                <div style="flex: 1; min-width: min(300px, 100%);">
                    <h3 style="color: white; margin-bottom: 8px; font-size: 24px; font-weight: 700;"><?php echo esc_html($nl_head); ?></h3>
                    <p style="color: #94a3b8; margin: 0; font-size: 16px;"><?php echo esc_html($nl_desc); ?></p>
                </div>
                <!-- Generic Form action for Mailchimp/HubSpot -->
                <form action="<?php echo esc_url($nl_action); ?>" method="post" target="_blank" style="display: flex; gap: 10px; flex: 1; min-width: min(300px, 100%); flex-wrap: wrap;">
                    <input type="email" name="EMAIL" placeholder="Enter your professional email" required style="flex: 1 1 200px; padding: 12px 16px; font-size: 16px; border-radius: var(--radius-field, 16px); border: 1px solid #334155; background: #1e293b; color: white;">
                    <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_<?php echo md5($nl_action); ?>" tabindex="-1" value=""></div>
                    <button type="submit" class="btn btn-primary" style="white-space: nowrap; padding: 12px 24px;">Subscribe</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="footer-grid">
                <div class="footer-brand">
                    <?php
                    $f_logo = vance_get_theme_mod('vance_footer_logo');
                    $f_text = vance_get_theme_mod('vance_footer_brand_text', 'Pioneering gastro research and turning knowledge into better patient outcomes.');
                    ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:block; margin-bottom: 24px;">
                        <?php if($f_logo): ?>
                            <img src="<?php echo esc_url($f_logo); ?>" alt="Vance Medical" style="height: 72px;" loading="lazy" decoding="async">
                        <?php else: ?>
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/logo.png" alt="Vance Medical" style="height: 72px;" loading="lazy" decoding="async">
                        <?php endif; ?>
                    </a>
                    <p style="color: var(--secondary-color, #0A1929); font-weight: 700;"><?php echo esc_html($f_text); ?></p>
                </div>

                <div class="footer-col">
                    <h4><?php echo esc_html(vance_get_theme_mod('vance_footer_heading_col1', 'Topics')); ?></h4>
                    <?php
                    wp_nav_menu( array(
                        'theme_location' => 'footer-menu-1',
                        'container'      => false,
                        'fallback_cb'    => false,
                    ) );
                    ?>
                </div>

                <div class="footer-col">
                    <h4><?php echo esc_html(vance_get_theme_mod('vance_footer_heading_col2', 'For Professionals')); ?></h4>
                    <?php
                    wp_nav_menu( array(
                        'theme_location' => 'footer-menu-2',
                        'container'      => false,
                        'fallback_cb'    => false,
                    ) );
                    ?>
                </div>

                <div class="footer-col">
                    <h4><?php echo esc_html(vance_get_theme_mod('vance_footer_heading_col3', 'For Patients')); ?></h4>
                    <?php
                    wp_nav_menu( array(
                        'theme_location' => 'footer-menu-3',
                        'container'      => false,
                        'fallback_cb'    => false,
                    ) );
                    ?>
                </div>
            </div>

            <?php
            $vance_footer_disclaimer = vance_get_theme_mod(
                'vance_footer_medical_disclaimer',
                'Vance Medical Hub provides general information and community support for people affected by gastrointestinal conditions. It is not medical advice and is not a substitute for the care of your own healthcare team. Nothing on this site should be used to diagnose or treat a health problem or disease. Always speak to your GP, pharmacist, dietitian or other qualified healthcare professional before making changes to your diet, medication or treatment, and with any questions about a medical condition. If you think you may have a medical emergency, call 999 or NHS 111 straight away.'
            );
            $vance_footer_operator = vance_get_theme_mod( 'vance_footer_operator', 'Operated by Vance Medical Foods Ltd, 3a Chestnut House, Farm Close, Shenley, Hertfordshire, WD7 9AD, United Kingdom. Registered in England and Wales, company number 17157853.' );
            ?>
            <div class="footer-disclaimer" style="border-top: 1px solid rgba(148,163,184,0.22); margin-top: 32px; padding-top: 24px; color: #94a3b8; font-size: 13px; line-height: 1.75; max-width: 1000px;">
                <strong style="color:#cbd5e1;">Medical disclaimer.</strong> <?php echo esc_html( $vance_footer_disclaimer ); ?>
                <?php if ( $vance_footer_operator ) : ?>
                    <div style="margin-top: 12px;"><?php echo esc_html( $vance_footer_operator ); ?></div>
                <?php endif; ?>
            </div>

            <div class="footer-bottom">
                <div class="copyright"><?php echo esc_html(vance_get_theme_mod('vance_footer_copyright', '© ' . date('Y') . ' Vance Medical Foods Ltd. All rights reserved.')); ?></div>
                <div class="footer-links">
                    <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a>
                    <a href="<?php echo esc_url( home_url( '/terms-of-use/' ) ); ?>">Terms of Use</a>
                    <a href="<?php echo esc_url( home_url( '/cookie-policy-uk/' ) ); ?>">Cookie Policy</a>
                    <a href="<?php echo esc_url( home_url( '/medical-disclaimer/' ) ); ?>">Medical Disclaimer</a>
                    <a href="<?php echo esc_url( home_url( '/accessibility/' ) ); ?>">Accessibility</a>
                </div>
                <div class="social-links">
                    <!-- Icons would go here -->
                </div>
            </div>
        </div>
    </footer>


    <!-- Infographic Modal -->
    <div id="infographic-modal" class="infographic-modal">
        <span class="modal-close">&times;</span>
        <div class="modal-content">
            <img id="modal-image" alt="Enlarged Infographic">
        </div>
    </div>

    <!-- Guest Save Modal -->
    <div id="guest-save-modal" class="infographic-modal">
        <span class="modal-close" onclick="closeGuestModal()">&times;</span>
        <div class="modal-content" style="max-width: 450px; background: white; padding: 40px; border-radius: var(--radius-surface, 24px); text-align: center;">
            <div style="width: 64px; height: 64px; background: #def4f4; border-radius: var(--radius-control, 10px); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                <span style="font-size: 32px;">🔖</span>
            </div>
            <h2 style="font-size: 24px; color: #0f172a; margin-bottom: 12px; font-family: var(--font-heading);">Join the Vance Medical Hub</h2>
            <p style="font-size: 16px; color: #64748b; margin-bottom: 24px; line-height: 1.6;">Save your favorite articles, track your reading progress, and access exclusive professional resources by joining our community.</p>

            <div style="margin-bottom: 16px;">
                <?php echo do_shortcode('[google_login]'); ?>
            </div>

            <div style="text-align:center;color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:1px;margin:12px 0;position:relative;">
                <div style="position:absolute;left:0;right:0;top:50%;border-top:1px solid #E2E8F0;z-index:0;"></div>
                <span style="background:#fff;padding:0 10px;position:relative;z-index:1;">or</span>
            </div>

            <form id="guest-save-email-form" style="text-align:left;" novalidate>
                <input type="email" id="guest-save-email" name="email" required autocomplete="email" inputmode="email" placeholder="Email address" style="width:100%;box-sizing:border-box;padding:12px 14px;border:1px solid #E2E8F0;font-size:15px;margin-bottom:10px;border-radius:var(--radius-field, 16px);">
                <input type="password" id="guest-save-password" name="password" required minlength="8" autocomplete="new-password" placeholder="Password, 8 characters minimum" style="width:100%;box-sizing:border-box;padding:12px 14px;border:1px solid #E2E8F0;font-size:15px;margin-bottom:10px;border-radius:var(--radius-field, 16px);">
                <!-- Honeypot — bots fill anything visible; real users don't fill display:none fields -->
                <div style="position:absolute;left:-5000px;" aria-hidden="true">
                    <input type="text" id="guest-save-hp" tabindex="-1" value="">
                </div>
                <label style="display:flex;gap:8px;align-items:flex-start;font-size:12px;color:#64748b;text-align:left;margin-bottom:14px;cursor:pointer;font-weight:400;">
                    <input type="checkbox" id="guest-save-terms" required style="width:auto;margin-top:2px;">
                    <span>I agree to the <a href="<?php echo esc_url( home_url( '/terms-of-use/' ) ); ?>" target="_blank" style="color:#008080;">Terms</a> and <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" target="_blank" style="color:#008080;">Privacy Policy</a>.</span>
                </label>
                <div id="guest-save-error" role="alert" style="display:none;padding:10px 14px;margin-bottom:12px;background:#FEF2F2;border-left:3px solid #DC2626;color:#991B1B;font-size:13px;text-align:left;"></div>
                <button type="submit" id="guest-save-submit" style="width:100%;padding:14px;background:#008080;color:white;border:none;font-size:15px;font-weight:700;cursor:pointer;letter-spacing:0.4px;text-transform:uppercase;border-radius:var(--radius-control, 10px);">Create account</button>
            </form>

            <p style="font-size: 14px; color: #94a3b8; margin: 16px 0 0;">Already have an account? <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" style="color: #008080; font-weight: 600; text-decoration: none;">Sign In</a></p>
        </div>
    </div>

    <?php include get_template_directory() . '/inc/quiz-modal.php'; ?>
    <?php include get_template_directory() . '/inc/clinical-info-modal.php'; ?>
    <?php
    // Quick-signup modal. Its own include guard makes this safe alongside
    // inc/tool-page-shell.php, which also loads it; loading it here is what lets
    // the VANCE-Ai article popup offer "Register for FREE" without a page change.
    get_template_part( 'inc/register-modal' );
    ?>
    <?php
    // Unified glass tool modal — recipes / malnutrition open in an iframe overlay,
    // the quiz reuses its own inline modal. Lets any tool link open from anywhere.
    include get_template_directory() . '/inc/tool-modal.php';
    ?>

    <script>
    function openGuestModal() {
        document.getElementById('guest-save-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeGuestModal() {
        document.getElementById('guest-save-modal').style.display = 'none';
        document.body.style.overflow = '';
    }

    (function () {
        var form = document.getElementById('guest-save-email-form');
        if (!form) { return; }
        var errEl      = document.getElementById('guest-save-error');
        var submitBtn  = document.getElementById('guest-save-submit');
        var ajaxUrl    = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
        var signupNonce = '';

        // This page can be served from LiteSpeed's full-page cache, so a nonce
        // baked into the cached HTML would go stale. Refresh it via
        // admin-ajax.php (never page-cached) instead.
        fetch(ajaxUrl + '?action=vance_refresh_auth_nonces', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) { if (data.success) { signupNonce = data.data.signup; } })
            .catch(function () {});

        function showErr(msg) { errEl.textContent = msg; errEl.style.display = 'block'; }
        function clearErr() { errEl.textContent = ''; errEl.style.display = 'none'; }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearErr();
            var email = document.getElementById('guest-save-email').value.trim();
            var password = document.getElementById('guest-save-password').value;
            if (!email || email.indexOf('@') < 1) { showErr('Please enter a valid email.'); return; }
            if (password.length < 8) { showErr('Password must be at least 8 characters.'); return; }
            if (!document.getElementById('guest-save-terms').checked) {
                showErr('Please agree to the Terms and Privacy Policy to continue.');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating account…';

            var body = new URLSearchParams({
                action: 'vance_quick_register',
                nonce: signupNonce,
                email: email,
                password: password,
                consent_terms: '1',
                role: 'patient',
                vance_hp: document.getElementById('guest-save-hp').value,
                source: 'guest_save_modal'
            });

            fetch(ajaxUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: body.toString()
            }).then(function (r) { return r.json(); }).then(function (data) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create account';
                if (data.success) {
                    window.location.href = (data.data && data.data.redirect) || '/dashboard/?vance_welcome=1';
                } else {
                    showErr((data.data && data.data.message) || 'Something went wrong, please try again.');
                }
            }).catch(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create account';
                showErr('Network error, please try again.');
            });
        });
    })();
    </script>

    <style>
    .infographic-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.9);
        backdrop-filter: blur(5px);
        align-items: center;
        justify-content: center;
        padding: 40px;
    }
    .modal-content {
        position: relative;
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 90vh;
    }
    .modal-content img {
        width: 100%;
        height: auto;
        max-height: 90vh;
        object-fit: contain;
        border-radius: var(--radius-surface, 24px);
        box-shadow: 0 0 30px rgba(0,0,0,0.5);
    }
    .modal-close {
        position: absolute;
        top: 20px;
        right: 35px;
        color: #f1f1f1;
        font-size: 60px;
        font-weight: bold;
        transition: 0.3s;
        cursor: pointer;
        z-index: 10000;
        line-height: 1;
    }
    .modal-close:hover,
    .modal-close:focus {
        color: var(--primary-color);
        text-decoration: none;
    }
    @media only screen and (max-width: 700px){
        .modal-content {
            width: 100%;
        }
        .infographic-modal {
            padding: 20px;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('infographic-modal');
        const modalImg = document.getElementById('modal-image');
        const closeBtn = document.querySelector('.modal-close');

        // Function to open modal
        function openModal(src) {
            modal.style.display = "flex";
            modalImg.src = src;
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        // Close modal
        function closeModal() {
            modal.style.display = "none";
            modalImg.removeAttribute('src');
            document.body.style.overflow = '';
        }
        closeBtn.onclick = closeModal;

        // Close on outside click
        modal.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        }

        // Close on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                closeModal();
            }
        });



        // Add event listeners to infographic links
        document.querySelectorAll('.infographic-popup-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const largeSrc = this.getAttribute('data-large-src') || this.href;
                if (largeSrc && largeSrc !== '#') {
                    openModal(largeSrc);
                }
            });
        });


        // Flash Card Widget Logic
        document.querySelectorAll('.quiz-widget').forEach(widget => {
            const dataStr = widget.getAttribute('data-quiz');
            if (!dataStr) return;
            
            const cardData = JSON.parse(dataStr);
            const body = widget.querySelector('.quiz-widget-body');
            const progressBar = widget.querySelector('.quiz-progress-bar');
            let currentIdx = 0;

            function renderCard(idx) {
                const card = cardData[idx];

                body.innerHTML = `
                    <div class="flashcard-container active" style="perspective: 1000px; cursor: pointer;">
                        <div class="flashcard-inner" style="position: relative; width: 100%; min-height: 200px; text-align: center; transition: transform 0.6s; transform-style: preserve-3d;">
                            <!-- Front -->
                            <div class="flashcard-front" style="position: absolute; width: 100%; height: 100%; backface-visibility: hidden; display: flex; align-items: center; justify-content: center; padding: 20px; background: #f9fafb; border-radius: var(--radius-surface, 24px); border: 1px solid #e5e7eb;">
                                <div style="font-weight: 700; color: var(--secondary-color); font-size: 16px;">${card.question}</div>
                                <div style="position: absolute; bottom: 10px; font-size: 12px; color: var(--text-light);">Click to reveal answer</div>
                            </div>
                            <!-- Back -->
                            <div class="flashcard-back" style="position: absolute; width: 100%; height: 100%; backface-visibility: hidden; display: flex; align-items: center; justify-content: center; padding: 20px; background: #def4f4; border-radius: var(--radius-surface, 24px); border: 1px solid var(--primary-color); transform: rotateY(180deg);">
                                <div style="color: var(--secondary-color); font-size: 15px; line-height: 1.5;">${card.answer}</div>
                            </div>
                        </div>
                        <button class="quiz-next-btn" style="margin-top: 20px; display: none;">Next Card</button>
                    </div>
                `;

                const inner = body.querySelector('.flashcard-inner');
                const nextBtn = body.querySelector('.quiz-next-btn');
                const container = body.querySelector('.flashcard-container');

                container.addEventListener('click', function(e) {
                    if (e.target.classList.contains('quiz-next-btn')) return;
                    inner.style.transform = inner.style.transform === 'rotateY(180deg)' ? 'rotateY(0deg)' : 'rotateY(180deg)';
                    nextBtn.style.display = 'block';
                });

                // Update Progress
                const progress = ((idx) / cardData.length) * 100;
                progressBar.style.width = `${progress}%`;

                nextBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    currentIdx++;
                    if (currentIdx < cardData.length) {
                        renderCard(currentIdx);
                    } else {
                        showResults();
                    }
                });
            }

            function showResults() {
                progressBar.style.width = '100%';
                body.innerHTML = `
                    <div class="quiz-results" style="text-align: center; animation: fadeIn 0.5s;">
                        <div style="font-size: 48px; margin-bottom: 20px;">🎉</div>
                        <h3 style="margin-bottom: 10px;">All Done!</h3>
                        <p style="font-size: 16px; color: var(--text-light);">You've reviewed all ${cardData.length} flashcards.</p>
                        <button class="btn btn-primary" style="margin-top: 20px; width: 100%;" onclick="location.reload()">Start Over</button>
                    </div>
                `;
            }

            renderCard(0);
        });
    });
    </script>

<?php
// Phase 2.1 — mobile bottom navigation (renders only when enabled in
// Customizer → Mobile Experience, and never on the dashboard). Self-gated.
include get_template_directory() . '/template-parts/mobile-bottom-nav.php';
// Phase 2.2 — mobile sticky CTA bar (self-gated; never shows alongside the
// bottom nav or on the dashboard).
include get_template_directory() . '/template-parts/mobile-sticky-cta.php';
// Phase 2.3 — swipeable homepage category cards (self-gated; front page only).
include get_template_directory() . '/template-parts/mobile-swipe-cards.php';
?>

<script>
/*
 * Max Mega Menu mobile panel — fix for a stuck-transition bug that leaves
 * the off-canvas panel permanently visibility:hidden / left:-300px.
 *
 * Confirmed root cause (2026-08-07): the plugin correctly toggles
 * aria-expanded and the `.mega-menu-open` class on click, and its own
 * generated stylesheet does have the correct higher-specificity "open"
 * rule (visibility:visible; left:0) — but the panel's `left`/`visibility`
 * are under an active CSS transition, and that transition is stuck at its
 * closed-state value on first open. Per the CSS cascade spec, an
 * in-progress transition's interpolated value outranks EVERYTHING else,
 * including inline `!important` — so no ordinary CSS override can win.
 * The only reliable fix is to briefly disable the transition, force the
 * open values, reflow, then restore the transition so future opens/closes
 * still animate normally.
 */
(function () {
    var toggle = document.querySelector( '.mega-menu-toggle' );
    var panel  = document.getElementById( 'mega-menu-primary-menu' );
    if ( ! toggle || ! panel ) {
        return;
    }

    function sync() {
        var isOpen = toggle.classList.contains( 'mega-menu-open' );
        panel.style.setProperty( 'transition', 'none', 'important' );
        void panel.offsetHeight; // force reflow so the transition-none takes hold before we set values
        if ( isOpen ) {
            panel.style.setProperty( 'visibility', 'visible', 'important' );
            panel.style.setProperty( 'left', '0px', 'important' );
        } else {
            panel.style.removeProperty( 'visibility' );
            panel.style.removeProperty( 'left' );
        }
        void panel.offsetHeight;
        panel.style.removeProperty( 'transition' );
    }

    new MutationObserver( sync ).observe( toggle, { attributes: true, attributeFilter: [ 'class' ] } );
})();
</script>

<?php wp_footer(); ?>
<?php echo vance_get_theme_mod( 'vance_footer_scripts' ); ?>
</body>
</html>
