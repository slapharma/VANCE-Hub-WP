<?php
/**
 * Register Modal — universal "save your result" → quick signup flow.
 *
 * Loaded by inc/tool-page-shell.php on every per-tool wrapper page. Exposes
 * `window.VanceRegisterModal.open({ tool, payload, onSuccess })` for any
 * front-end caller (currently the Save button on tool pages, but reusable
 * elsewhere).
 *
 * Server-side handler: `wp_ajax_nopriv_vance_quick_register` in
 * inc/dashboard-functions.php — creates the user, auto-logs in, stashes the
 * pending tool result under `_sla_<tool>_history` (mirrors existing meta
 * naming per CLAUDE.md constraint #2 — use `_sla_*` for user/post meta).
 *
 * Idempotent: rendering this partial multiple times on a page would duplicate
 * IDs, so the include guard below ensures one DOM tree only.
 */

if ( defined( 'VANCE_REGISTER_MODAL_RENDERED' ) ) {
    return;
}
define( 'VANCE_REGISTER_MODAL_RENDERED', true );

if ( is_user_logged_in() ) {
    // Logged-in users never see the register modal — but the JS hook is still
    // exposed so any caller that opens it gets a graceful no-op.
    ?>
    <script>window.VanceRegisterModal = { open: function () { /* already logged in */ } };</script>
    <?php
    return;
}

$register_nonce = wp_create_nonce( 'vance_quick_register' );
$ajax_url       = admin_url( 'admin-ajax.php' );
?>

<style>
.vance-reg-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(10,25,41,0.78); backdrop-filter: blur(4px);
    z-index: 100050; align-items: center; justify-content: center; padding: 20px;
}
.vance-reg-overlay.is-open { display: flex; }
.vance-reg-modal {
    background: white; max-width: 460px; width: 100%; padding: 36px 32px;
    box-shadow: 0 30px 80px rgba(10,25,41,0.30); position: relative; border-radius: 0;
    border-top: 4px solid var(--primary-color);
}
.vance-reg-modal h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 26px; color: var(--secondary-color); margin: 0 0 8px 0; line-height: 1.2;
}
.vance-reg-modal p.lead { color: var(--text-light); font-size: 15px; margin: 0 0 20px 0; line-height: 1.6; }
.vance-reg-modal label { display: block; font-size: 12px; font-weight: 600; color: var(--secondary-color); margin: 14px 0 6px; letter-spacing: 0.3px; text-transform: uppercase; }
.vance-reg-modal input,
.vance-reg-modal select {
    width: 100%; padding: 12px 14px; border: 1px solid #E2E8F0; font-size: 15px;
    background: #fff; box-sizing: border-box; border-radius: 0;
}
.vance-reg-modal input:focus,
.vance-reg-modal select:focus { outline: none; border-color: var(--primary-color); }
.vance-reg-modal__submit {
    width: 100%; padding: 14px; margin-top: 20px;
    background: var(--primary-color); color: white; border: none;
    font-size: 15px; font-weight: 700; cursor: pointer; letter-spacing: 0.4px; text-transform: uppercase;
}
.vance-reg-modal__submit:hover { background: #006666; }
.vance-reg-modal__submit[disabled] { opacity: 0.6; cursor: not-allowed; }
.vance-reg-modal__close {
    position: absolute; top: 12px; right: 14px; background: transparent; border: none;
    font-size: 26px; color: #94a3b8; cursor: pointer; line-height: 1; padding: 4px 8px;
}
.vance-reg-modal__close:hover { color: var(--secondary-color); }
.vance-reg-modal__signin { font-size: 13px; color: var(--text-light); text-align: center; margin: 16px 0 0; }
.vance-reg-modal__signin a { color: var(--primary-color); font-weight: 600; }
.vance-reg-modal__error {
    display: none; padding: 10px 14px; margin-top: 12px;
    background: #FEF2F2; border-left: 3px solid #DC2626; color: #991B1B; font-size: 13px;
}
.vance-reg-modal__error.is-visible { display: block; }
.vance-reg-modal__terms { font-size: 11px; color: var(--text-light); margin: 12px 0 0; line-height: 1.55; }
</style>

<div class="vance-reg-overlay" id="vance-reg-overlay" role="dialog" aria-modal="true" aria-labelledby="vance-reg-title">
    <div class="vance-reg-modal">
        <button type="button" class="vance-reg-modal__close" id="vance-reg-close" aria-label="Close">×</button>
        <h2 id="vance-reg-title">Save your result</h2>
        <p class="lead">Create a free account to save this and get it back in your dashboard whenever you need it.</p>

        <form id="vance-reg-form" autocomplete="on" novalidate>
            <label for="vance-reg-email">Email</label>
            <input type="email" id="vance-reg-email" name="email" required autocomplete="email" inputmode="email" placeholder="you@example.com">

            <label for="vance-reg-password">Password <span style="font-weight: 400; text-transform: none; opacity: 0.7;">— 8 characters minimum</span></label>
            <input type="password" id="vance-reg-password" name="password" required minlength="8" autocomplete="new-password" placeholder="••••••••">

            <label for="vance-reg-role">I am a…</label>
            <select id="vance-reg-role" name="role">
                <option value="patient">Patient</option>
                <option value="caregiver">Caregiver / family</option>
                <option value="hcp">Healthcare professional</option>
                <option value="researcher">Researcher</option>
                <option value="other">Other</option>
            </select>

            <input type="hidden" name="tool" id="vance-reg-tool" value="">
            <input type="hidden" name="payload" id="vance-reg-payload" value="">
            <input type="hidden" name="nonce" value="<?php echo esc_attr( $register_nonce ); ?>">

            <!-- Honeypot — bots fill anything visible; real users don't fill display:none fields -->
            <div style="position: absolute; left: -5000px;" aria-hidden="true">
                <input type="text" name="vance_hp" tabindex="-1" value="">
            </div>

            <label class="vance-reg-modal__consent" style="display:flex; gap:8px; align-items:flex-start; text-transform:none; font-weight:400; font-size:12.5px; color:var(--text-light); margin:16px 0 0; letter-spacing:0; cursor:pointer;">
                <input type="checkbox" name="consent_terms" id="vance-reg-terms" value="1" style="width:auto; margin-top:2px;" required>
                <span>I agree to the <a href="/terms-of-use/" target="_blank" style="color:var(--primary-color);">Terms</a> and <a href="/privacy-policy/" target="_blank" style="color:var(--primary-color);">Privacy Policy</a>, and to my saved result being stored as health information so I can see it in my dashboard.</span>
            </label>
            <label class="vance-reg-modal__consent" style="display:flex; gap:8px; align-items:flex-start; text-transform:none; font-weight:400; font-size:12.5px; color:var(--text-light); margin:10px 0 0; letter-spacing:0; cursor:pointer;">
                <input type="checkbox" name="consent_marketing" id="vance-reg-marketing" value="1" style="width:auto; margin-top:2px;">
                <span>Email me occasional updates about new tools and resources. Optional, unsubscribe anytime.</span>
            </label>

            <div class="vance-reg-modal__error" id="vance-reg-error" role="alert"></div>

            <button type="submit" class="vance-reg-modal__submit" id="vance-reg-submit">
                Create account &amp; save result
            </button>
            <p class="vance-reg-modal__terms">Your saved result is stored securely, is only visible to you, and can be deleted anytime from your dashboard.</p>
        </form>

        <p class="vance-reg-modal__signin">
            Already have an account? <a href="/login/" id="vance-reg-signin-link">Sign in</a>
        </p>
    </div>
</div>

<script>
(function () {
    var overlay = document.getElementById('vance-reg-overlay');
    var form    = document.getElementById('vance-reg-form');
    var closeBtn = document.getElementById('vance-reg-close');
    var submit  = document.getElementById('vance-reg-submit');
    var errBox  = document.getElementById('vance-reg-error');
    var emailEl = document.getElementById('vance-reg-email');
    var pwEl    = document.getElementById('vance-reg-password');
    var roleEl  = document.getElementById('vance-reg-role');
    var toolEl  = document.getElementById('vance-reg-tool');
    var pyldEl  = document.getElementById('vance-reg-payload');
    var signinLink = document.getElementById('vance-reg-signin-link');

    var ajaxUrl = <?php echo wp_json_encode( $ajax_url ); ?>;
    var onSuccessCb = null;

    function showErr(msg) { errBox.textContent = msg; errBox.classList.add('is-visible'); }
    function clearErr() { errBox.textContent = ''; errBox.classList.remove('is-visible'); }

    function open(opts) {
        opts = opts || {};
        clearErr();
        toolEl.value = opts.tool || '';
        pyldEl.value = opts.payload ? JSON.stringify(opts.payload) : '';
        onSuccessCb = typeof opts.onSuccess === 'function' ? opts.onSuccess : null;
        // Tweak Sign-in link redirect to bring users back to where they were.
        if (signinLink) {
            signinLink.href = '/login/?redirect_to=' + encodeURIComponent(window.location.pathname + window.location.search);
        }
        overlay.classList.add('is-open');
        // Focus the email input on next tick (after render).
        setTimeout(function () { try { emailEl.focus(); } catch (e) {} }, 50);
        document.body.style.overflow = 'hidden';
    }

    function close() {
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
        onSuccessCb = null;
    }

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) close();
    });
    closeBtn.addEventListener('click', close);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) close();
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErr();

        var email = (emailEl.value || '').trim();
        var pw    = pwEl.value || '';
        if (!email || email.indexOf('@') < 1) { showErr('Please enter a valid email.'); emailEl.focus(); return; }
        if (pw.length < 8) { showErr('Password must be at least 8 characters.'); pwEl.focus(); return; }
        var termsEl = document.getElementById('vance-reg-terms');
        if (termsEl && !termsEl.checked) { showErr('Please agree to the Terms and Privacy Policy to continue.'); termsEl.focus(); return; }

        submit.disabled = true;
        submit.textContent = 'Creating your account…';

        var fd = new FormData(form);
        fd.set('action', 'vance_quick_register');

        fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
            .then(function (r) { return r.json(); })
            .catch(function () { return null; })
            .then(function (j) {
                submit.disabled = false;
                submit.textContent = 'Create account & save result';
                if (j && j.success) {
                    var cb = onSuccessCb;
                    close();
                    if (cb) cb(j.data || {});
                    else window.location.href = (j.data && j.data.redirect) || '/dashboard/?vance_welcome=1';
                } else {
                    var msg = (j && j.data && j.data.message) || 'Something went wrong — please try again.';
                    showErr(msg);
                }
            });
    });

    window.VanceRegisterModal = { open: open, close: close };
})();
</script>
