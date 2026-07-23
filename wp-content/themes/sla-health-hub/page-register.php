<?php
/**
 * Template Name: Custom Registration
 *
 * Standalone /register/ page. Renders the SAME account creation form as the
 * tool-page / VANCE-Ai register modal (inc/register-modal.php): email,
 * password, "I am a…" audience dropdown, terms + marketing consent, honeypot.
 * Submits via AJAX to the shared `vance_quick_register` handler in
 * inc/dashboard-functions.php (constraint #5: action + nonce names paired).
 */

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/dashboard/'));
    exit;
}

$register_nonce = wp_create_nonce( 'vance_quick_register' );
$ajax_url       = admin_url( 'admin-ajax.php' );

// Prefill email from ?user_email= (used by cross-page CTAs).
$prefill_email = isset( $_GET['user_email'] ) ? sanitize_email( wp_unslash( $_GET['user_email'] ) ) : '';

// Back-compat: old links used ?role=practitioner|patient — map onto the
// audience dropdown values used by the shared form.
$prefill_role = 'patient';
if ( isset( $_GET['role'] ) ) {
    $role_map = array(
        'practitioner' => 'hcp',
        'hcp'          => 'hcp',
        'patient'      => 'patient',
        'caregiver'    => 'caregiver',
        'researcher'   => 'researcher',
        'other'        => 'other',
    );
    $requested = sanitize_key( wp_unslash( $_GET['role'] ) );
    if ( isset( $role_map[ $requested ] ) ) {
        $prefill_role = $role_map[ $requested ];
    }
}

$audience_roles = array(
    'patient'    => 'Patient',
    'caregiver'  => 'Caregiver / family',
    'hcp'        => 'Healthcare professional',
    'researcher' => 'Researcher',
    'other'      => 'Other',
);

get_header();
?>

<style>
.vance-register-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #0A1929 0%, #112240 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.register-container {
    background: white;
    border-radius: 0;
    padding: 48px;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    border-top: 4px solid var(--primary-color);
}

.register-header {
    text-align: center;
    margin-bottom: 32px;
}

.register-logo {
    width: 60px;
    height: 60px;
    background: var(--primary-color);
    border-radius: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 28px;
    color: white;
    font-weight: 800;
}

.register-title {
    font-family: 'Outfit', sans-serif;
    font-size: 28px;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 8px 0;
}

.register-subtitle {
    color: #64748B;
    font-size: 14px;
    margin: 0;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    color: #334155;
    margin-bottom: 8px;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #E2E8F0;
    border-radius: 0;
    font-size: 14px;
    transition: all 0.2s;
    font-family: inherit;
    background: #fff;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0,128,128, 0.1);
}

.form-hint {
    font-weight: 400;
    text-transform: none;
    opacity: 0.7;
}

.submit-btn {
    width: 100%;
    padding: 14px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 0;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-family: 'Outfit', sans-serif;
}

.submit-btn:hover {
    background: #006666;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,128,128, 0.3);
}

.submit-btn[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.error-message {
    display: none;
    background: #FEE2E2;
    border-left: 4px solid #EF4444;
    padding: 12px 16px;
    border-radius: 0;
    margin-bottom: 20px;
    color: #991B1B;
    font-size: 14px;
}

.error-message.is-visible {
    display: block;
}

.login-link {
    text-align: center;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #E2E8F0;
    font-size: 14px;
    color: #64748B;
}

.login-link a {
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
}

.login-link a:hover {
    text-decoration: underline;
}

.consent-label {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    font-size: 13px;
    color: #475569;
    line-height: 1.6;
    cursor: pointer;
    font-weight: 400;
}

.consent-label input {
    width: auto;
    margin-top: 3px;
}

.consent-label a {
    color: var(--primary-color);
    font-weight: 600;
}
</style>

<div class="vance-register-page">
    <div class="register-container">
        <div class="register-header">
            <div class="register-logo">V</div>
            <h1 class="register-title">Create Your Account</h1>
            <p class="register-subtitle">Join the Vance Medical community today</p>
        </div>

        <div class="error-message" id="vance-register-error" role="alert"></div>

        <form id="vance-register-form" autocomplete="on" novalidate>
            <div class="form-group">
                <label class="form-label" for="user_email">Email</label>
                <input type="email" id="user_email" name="email" class="form-input" value="<?php echo esc_attr( $prefill_email ); ?>" required autocomplete="email" inputmode="email" placeholder="you@example.com">
            </div>

            <div class="form-group">
                <label class="form-label" for="user_password">Password <span class="form-hint">(8 characters minimum)</span></label>
                <input type="password" id="user_password" name="password" class="form-input" required minlength="8" autocomplete="new-password" placeholder="••••••••">
            </div>

            <div class="form-group">
                <label class="form-label" for="user_role">I am a…</label>
                <select id="user_role" name="role" class="form-input">
                    <?php foreach ( $audience_roles as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $prefill_role, $value ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Honeypot — bots fill anything visible; real users don't fill display:none fields -->
            <div style="position: absolute; left: -5000px;" aria-hidden="true">
                <input type="text" name="vance_hp" tabindex="-1" value="">
            </div>

            <div class="form-group">
                <label class="consent-label">
                    <input type="checkbox" name="consent_terms" id="vance-register-terms" value="1" required>
                    <span>I agree to the <a href="<?php echo esc_url( home_url('/terms-of-use/') ); ?>" target="_blank">Terms of Use</a> and <a href="<?php echo esc_url( home_url('/privacy-policy/') ); ?>" target="_blank">Privacy Policy</a>, and to any results or health information I save being stored so I can see them in my dashboard.</span>
                </label>
            </div>

            <div class="form-group">
                <label class="consent-label">
                    <input type="checkbox" name="consent_marketing" value="1">
                    <span>Email me occasional updates about new tools and resources. Optional, unsubscribe anytime.</span>
                </label>
            </div>

            <p style="font-size:12px; color:#94a3b8; margin:0 0 18px; line-height:1.6;">Vance Medical Hub is intended for members of the public aged 18 and over in the United Kingdom.</p>

            <button type="submit" class="submit-btn" id="vance-register-submit">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>">Sign In</a>
        </div>
    </div>
</div>

<script>
(function () {
    var form    = document.getElementById('vance-register-form');
    var submit  = document.getElementById('vance-register-submit');
    var errBox  = document.getElementById('vance-register-error');
    var ajaxUrl = <?php echo wp_json_encode( $ajax_url ); ?>;
    var nonce   = <?php echo wp_json_encode( $register_nonce ); ?>;

    function showErr(msg) { errBox.textContent = msg; errBox.classList.add('is-visible'); }
    function clearErr() { errBox.textContent = ''; errBox.classList.remove('is-visible'); }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErr();

        var email = (form.email.value || '').trim();
        var pw    = form.password.value || '';
        if (!email || email.indexOf('@') < 1) { showErr('Please enter a valid email.'); form.email.focus(); return; }
        if (pw.length < 8) { showErr('Password must be at least 8 characters.'); form.password.focus(); return; }
        var termsEl = document.getElementById('vance-register-terms');
        if (termsEl && !termsEl.checked) { showErr('Please agree to the Terms and Privacy Policy to continue.'); termsEl.focus(); return; }

        submit.disabled = true;
        submit.textContent = 'Creating your account…';

        var fd = new FormData(form);
        fd.set('action', 'vance_quick_register');
        fd.set('nonce', nonce);
        fd.set('source', 'register_page');

        fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
            .then(function (r) { return r.json(); })
            .catch(function () { return null; })
            .then(function (j) {
                submit.disabled = false;
                submit.textContent = 'Create Account';
                if (j && j.success) {
                    window.location.href = (j.data && j.data.redirect) || '/dashboard/?vance_welcome=1';
                } else {
                    var msg = (j && j.data && j.data.message) || 'Something went wrong. Please try again.';
                    showErr(msg);
                }
            });
    });
})();
</script>

<?php get_footer(); ?>
