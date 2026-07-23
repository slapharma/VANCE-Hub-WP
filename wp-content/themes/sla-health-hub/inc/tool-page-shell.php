<?php
/**
 * Tool Page Shell — shared visual + behaviour for per-tool wrapper pages
 * (Omega-3, Malnutrition, Blood Test, IBD Recipes …).
 *
 * Layout mirrors `page-ask-ai.php`: tall dark hero (badge + H1 + subtitle) with
 * the tool iframe slotted into a card below, overlapping the hero by -40px so
 * it reads as a single composed unit.
 *
 * Caller contract — set BEFORE `require` / `get_template_part`:
 *   $vance_tool_slug          string  required, e.g. 'omega-3-calculator'
 *   $vance_tool_name          string  required, e.g. 'Omega-3 Calculator'
 *   $vance_tool_subtitle      string  required, hero paragraph copy
 *   $vance_tool_badge         string  optional, defaults to 'Free Tool'
 *   $vance_tool_hero_bg       string  optional, image URL (defaults to about_hero.png)
 *   $vance_tool_hero_overlay  int     optional, 0–100 (default 80)
 *   $vance_tool_iframe_height int     optional, px (default 720)
 *   $vance_tool_save_label    string  optional, 'Save my result' button label (default 'Save my result')
 *   $vance_tool_save_enabled  bool    optional, show the Save CTA (default true)
 *   $vance_tool_autoresize    bool    optional, set iframe height to its scrollHeight on load (default false)
 *   $vance_tool_brand_css     string  optional, raw CSS injected into the iframe contentDocument
 *   $vance_tool_iframe_src    string  optional, override the auto-derived iframe URL
 *                                     (use when the bundle lives somewhere other than
 *                                     /assets/tools/<slug>/index.html)
 *
 * The iframe URL is derived as `assets/tools/<slug>/index.html` and gets a
 * `?public=1&parent_origin=<host>` query string appended so the iframe can
 * detect public mode and target postMessage replies safely.
 *
 * Save flow: anonymous click → `inc/register-modal.php` opens. Logged-in
 * click → posts payload to `wp_ajax_vance_save_tool_result` (handler in
 * dashboard-functions.php) and toasts a confirmation.
 */

// --- Defaults & sanitisation ---
$slug          = isset( $vance_tool_slug ) ? sanitize_key( $vance_tool_slug ) : '';
$tool_name     = isset( $vance_tool_name ) ? $vance_tool_name : 'Tool';
$tool_subtitle = isset( $vance_tool_subtitle ) ? $vance_tool_subtitle : '';
$tool_badge    = isset( $vance_tool_badge ) ? $vance_tool_badge : 'Free Tool';
$hero_bg       = isset( $vance_tool_hero_bg ) && $vance_tool_hero_bg
    ? $vance_tool_hero_bg
    : get_template_directory_uri() . '/assets/img/about_hero.png';
$overlay_pct   = isset( $vance_tool_hero_overlay ) ? max( 0, min( 100, absint( $vance_tool_hero_overlay ) ) ) : 80;
$iframe_height = isset( $vance_tool_iframe_height ) ? absint( $vance_tool_iframe_height ) : 720;
$save_label    = isset( $vance_tool_save_label ) ? $vance_tool_save_label : 'Save my result';
$save_enabled  = isset( $vance_tool_save_enabled ) ? (bool) $vance_tool_save_enabled : true;
$autoresize    = isset( $vance_tool_autoresize ) ? (bool) $vance_tool_autoresize : false;
$brand_css     = isset( $vance_tool_brand_css ) ? (string) $vance_tool_brand_css : '';

// Optional caller overrides for badge / title / subtitle styling — empty string means "use default".
$tool_title_color    = isset( $vance_tool_title_color )    ? (string) $vance_tool_title_color    : '';
$tool_title_size     = isset( $vance_tool_title_size )     ? absint( $vance_tool_title_size )    : 0;
$tool_subtitle_color = isset( $vance_tool_subtitle_color ) ? (string) $vance_tool_subtitle_color : '';
$tool_subtitle_size  = isset( $vance_tool_subtitle_size )  ? absint( $vance_tool_subtitle_size )  : 0;
$tool_badge_bg       = isset( $vance_tool_badge_bg )       ? (string) $vance_tool_badge_bg       : '';
$tool_badge_color    = isset( $vance_tool_badge_color )    ? (string) $vance_tool_badge_color    : '';

if ( ! $slug ) {
    return; // misconfigured caller — bail silently
}

$alpha_top    = $overlay_pct / 100;
$alpha_bottom = min( 1, $alpha_top + 0.05 );

// Build iframe URL with public-mode flag and parent origin (so the iframe
// can postMessage back without leaking to other origins if it ever upgrades).
$origin    = isset( $_SERVER['HTTP_HOST'] ) ? esc_url_raw( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] ) : home_url();
$iframe_default = get_template_directory_uri() . '/assets/tools/' . $slug . '/index.html';
$iframe_base    = isset( $vance_tool_iframe_src ) && $vance_tool_iframe_src ? $vance_tool_iframe_src : $iframe_default;
$iframe_src = add_query_arg(
    array(
        'public'        => is_user_logged_in() ? '0' : '1',
        'parent_origin' => $origin,
    ),
    $iframe_base
);

$is_logged_in = is_user_logged_in();
$nonce        = wp_create_nonce( 'vance_tool_save_' . $slug );
?>
<style>
.tool-page { background: #F8FAFC; min-height: 100vh; }
.tool-page-hero {
    background: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $alpha_top ); ?>), rgba(10,25,41,<?php echo esc_attr( $alpha_bottom ); ?>)), url('<?php echo esc_url( $hero_bg ); ?>') center/cover;
    padding: 80px 0;
    color: white;
    text-align: center;
    border-bottom: 3px solid var(--primary-color);
    position: relative;
}
.tool-page-hero h1 {
    font-family: 'Outfit', sans-serif;
    font-size: 56px; font-weight: 800;
    margin: 0 0 16px 0; letter-spacing: -1px; text-transform: uppercase; line-height: 1.05;
}
.tool-page-hero p { font-size: 19px; color: #CBD5E1; max-width: 760px; margin: 0 auto; font-weight: 500; }
.tool-page-hero .tool-page-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.10); padding: 6px 16px; border-radius: 0;
    margin-bottom: 24px; border: 1px solid rgba(255,255,255,0.2);
    font-size: 12px; letter-spacing: 0.6px; text-transform: uppercase; color: white;
}
.tool-page-badge .status-dot { width: 8px; height: 8px; background: #22C55E; box-shadow: 0 0 10px #22C55E; animation: vance-tool-pulse 2s infinite; }
@keyframes vance-tool-pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

.tool-page-container { max-width: 1100px; margin: -40px auto 0; padding: 0 20px 40px; position: relative; z-index: 10; }
.tool-page-card { background: white; border: 2px solid var(--primary-color); border-radius: 0; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.10); overflow: hidden; }
.tool-page-card__head {
    padding: 18px 24px; background: #F8FAFC; border-bottom: 1px solid #E2E8F0;
    display: flex; gap: 16px; align-items: center; justify-content: space-between; flex-wrap: wrap;
}
.tool-page-card__title { margin: 0; font-size: 17px; color: var(--secondary-color); font-weight: 700; }
.tool-page-card__actions { display: flex; gap: 10px; align-items: center; }
.tool-page-iframe { width: 100%; height: <?php echo (int) $iframe_height; ?>px; border: 0; display: block; background: #fff; }

.tool-page-disclaimer-top {
    background: #F8FAFC; border-bottom: 1px solid #E2E8F0;
    padding: 12px 24px; font-size: 12.5px; color: #64748b; line-height: 1.6;
}
.tool-page-disclaimer {
    background: #EEF6F6; border-left: 4px solid var(--primary-color);
    padding: 16px 22px; margin: 24px 0 0; font-size: 13px; line-height: 1.75; color: #475569;
}
.tool-page-disclaimer strong { color: var(--secondary-color); }

.tool-page-save {
    background: var(--primary-color); color: white; border: none; padding: 10px 20px;
    font-size: 14px; font-weight: 600; cursor: pointer; letter-spacing: 0.3px;
    transition: background 0.15s; display: inline-flex; align-items: center; gap: 8px;
}
.tool-page-save:hover { background: #006666; }
.tool-page-save[disabled] { opacity: 0.55; cursor: not-allowed; }
.tool-page-save .save-hint { font-size: 11px; font-weight: 500; opacity: 0.85; margin-left: 6px; }

.tool-page-toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
    background: var(--primary-color); color: white; padding: 14px 22px; font-weight: 600;
    box-shadow: 0 12px 32px rgba(10,25,41,0.20); z-index: 100002;
    opacity: 0; transition: opacity 0.2s; pointer-events: none; border-radius: 0;
}
.tool-page-toast.is-visible { opacity: 1; }

@media (max-width: 600px) {
    .tool-page-hero { padding: 50px 0; }
    .tool-page-hero h1 { font-size: 36px; }
    .tool-page-hero p { font-size: 16px; }
    .tool-page-iframe { height: <?php echo (int) ( $iframe_height * 0.85 ); ?>px; }
}
</style>

<div class="tool-page">

    <?php
    // Build optional inline-style strings (only emit declarations when an override is set).
    $badge_inline = '';
    if ( $tool_badge_bg )    { $badge_inline .= 'background:' . esc_attr( $tool_badge_bg ) . ';'; }
    if ( $tool_badge_color ) { $badge_inline .= 'color:' . esc_attr( $tool_badge_color ) . ';border-color:' . esc_attr( $tool_badge_color ) . ';'; }
    $h1_inline    = '';
    if ( $tool_title_color ) { $h1_inline .= 'color:' . esc_attr( $tool_title_color ) . ';'; }
    if ( $tool_title_size  ) { $h1_inline .= 'font-size:' . (int) $tool_title_size . 'px;'; }
    $sub_inline   = '';
    if ( $tool_subtitle_color ) { $sub_inline .= 'color:' . esc_attr( $tool_subtitle_color ) . ';'; }
    if ( $tool_subtitle_size  ) { $sub_inline .= 'font-size:' . (int) $tool_subtitle_size . 'px;'; }
    ?>
    <section class="tool-page-hero">
        <div class="container">
            <div class="tool-page-badge" style="<?php echo $badge_inline; ?>">
                <span class="status-dot"></span>
                <?php echo esc_html( $tool_badge ); ?>
            </div>
            <h1 style="<?php echo $h1_inline; ?>"><?php echo esc_html( $tool_name ); ?></h1>
            <?php if ( $tool_subtitle ) : ?>
                <p style="<?php echo $sub_inline; ?>"><?php echo esc_html( $tool_subtitle ); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <div class="tool-page-container">
        <div class="tool-page-card">
            <div class="tool-page-card__head">
                <h2 class="tool-page-card__title"><?php echo esc_html( $tool_name ); ?></h2>
                <div class="tool-page-card__actions">
                    <a href="<?php echo esc_url( $iframe_src ); ?>" target="_blank" rel="noopener" class="btn btn-outline" style="padding: 8px 16px; font-size: 13px;">Open full screen ↗</a>
                    <?php if ( $save_enabled ) : ?>
                    <button type="button"
                            class="tool-page-save"
                            data-tool-slug="<?php echo esc_attr( $slug ); ?>"
                            data-logged-in="<?php echo $is_logged_in ? '1' : '0'; ?>"
                            data-nonce="<?php echo esc_attr( $nonce ); ?>">
                        <?php echo esc_html( $save_label ); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="tool-page-disclaimer-top">
                General information only. Not a diagnosis or a substitute for professional medical advice. In an emergency call 999 or NHS 111.
            </div>
            <iframe class="tool-page-iframe<?php echo $autoresize ? ' tool-page-iframe--autoresize' : ''; ?>"
                    id="vance-tool-iframe-<?php echo esc_attr( $slug ); ?>"
                    src="<?php echo esc_url( $iframe_src ); ?>"
                    title="<?php echo esc_attr( $tool_name ); ?>"
                    loading="lazy"
                    allow="clipboard-write"
                    <?php echo $autoresize ? 'scrolling="no"' : ''; ?>></iframe>
        </div>

        <div class="tool-page-disclaimer">
            <strong>About this tool.</strong> This tool provides general information to help you understand your health and prepare for conversations with your healthcare team. It does not provide a medical diagnosis and is not a substitute for assessment by a qualified healthcare professional. Results are estimates based only on the information you enter and the general method described; they may not be accurate for your individual circumstances. Do not start, stop or change any treatment, medication or diet on the basis of this tool alone. If you have any concerns about your health, contact your GP, pharmacist or healthcare team. In an emergency call 999 or NHS 111.
        </div>

        <p style="margin: 24px 0 0; font-size: 13px; color: var(--text-light); text-align: center;">
            <?php if ( $is_logged_in ) : ?>
                Saved results appear in your <a href="/dashboard/" style="color: var(--primary-color);">dashboard</a>.
            <?php else : ?>
                Saving your result is free — we'll create your account in two clicks. Already have one? <a href="/login/?redirect_to=<?php echo esc_attr( urlencode( $_SERVER['REQUEST_URI'] ?? '/' ) ); ?>" style="color: var(--primary-color);">Sign in</a>.
            <?php endif; ?>
        </p>
    </div>

    <div class="tool-page-toast" id="vance-tool-toast" role="status" aria-live="polite"></div>
</div>

<?php
// Mount the shared register modal (handles anonymous save → quick register flow).
get_template_part( 'inc/register-modal' );
?>

<script>
(function () {
    var slug       = <?php echo wp_json_encode( $slug ); ?>;
    var loggedIn   = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    var nonce      = <?php echo wp_json_encode( $nonce ); ?>;
    var ajaxUrl    = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var iframeEl   = document.getElementById('vance-tool-iframe-' + slug);
    var saveBtn    = document.querySelector('.tool-page-save[data-tool-slug="' + slug + '"]');
    var toast      = document.getElementById('vance-tool-toast');
    var brandCss   = <?php echo wp_json_encode( $brand_css ); ?>;
    var autoresize = <?php echo $autoresize ? 'true' : 'false'; ?>;
    var minIframeHeight = <?php echo (int) $iframe_height; ?>;

    /**
     * Same-origin iframe styling. Inject a stylesheet into the iframe's
     * <head> so brand colours / chrome-hiding rules apply WITHOUT rebuilding
     * the bundle. Idempotent: bails if already injected.
     */
    function injectBrandCss() {
        if (!iframeEl || !brandCss) return;
        try {
            var doc = iframeEl.contentDocument;
            if (!doc) return;
            if (doc.getElementById('vance-tool-brand-css')) return; // already done
            var style = doc.createElement('style');
            style.id = 'vance-tool-brand-css';
            style.textContent = brandCss;
            (doc.head || doc.documentElement).appendChild(style);
        } catch (e) {
            // Cross-origin (shouldn't happen on this site) — silently no-op.
        }
    }

    /**
     * Auto-resize the iframe to its content's scrollHeight so the iframe
     * itself never shows an internal scrollbar. The page can still scroll.
     * Polls every 500ms for the first 5s after load, then on resize, then
     * on a slower 2s interval (catches dynamic content rendered after
     * initial paint without burning CPU).
     */
    function fitIframeToContent() {
        if (!iframeEl || !autoresize) return;
        try {
            var doc = iframeEl.contentDocument;
            if (!doc || !doc.documentElement) return;
            var h = Math.max(
                doc.documentElement.scrollHeight,
                doc.body ? doc.body.scrollHeight : 0,
                minIframeHeight
            );
            // Avoid layout thrash when the height is unchanged.
            var current = iframeEl.style.height ? parseInt(iframeEl.style.height, 10) : 0;
            if (Math.abs(current - h) > 4) {
                iframeEl.style.height = h + 'px';
            }
        } catch (e) {
            // Cross-origin — give up silently.
        }
    }

    function onIframeReady() {
        injectBrandCss();
        fitIframeToContent();
    }

    if (iframeEl) {
        iframeEl.addEventListener('load', function () {
            onIframeReady();
            // Poll for ~5s after load — catches React/Next hydration painting.
            var ticks = 0;
            var fast = setInterval(function () {
                ticks++;
                onIframeReady();
                if (ticks >= 10) clearInterval(fast);
            }, 500);
            // Slow tick afterwards for late-rendered content.
            setInterval(onIframeReady, 2000);
        });
        // Also re-fit when the parent window resizes (responsive bundles).
        window.addEventListener('resize', fitIframeToContent);
    }

    // pendingPayload is whatever the iframe last sent via postMessage. If empty
    // at save time, we fall back to scraping the iframe DOM directly (same-origin,
    // so this is safe). Either way the user gets a save.
    var pendingPayload = null;

    function showToast(msg, ms) {
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.add('is-visible');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(function () { toast.classList.remove('is-visible'); }, ms || 3500);
    }

    /**
     * Best-effort iframe snapshot. Same-origin only — cross-origin returns null.
     * Returns { kind: 'dom-snapshot', url, title, text } or null on failure.
     * Truncates text to 16k chars to keep the AJAX payload sensible.
     */
    function snapshotIframe() {
        if (!iframeEl) return null;
        try {
            var doc = iframeEl.contentDocument;
            var win = iframeEl.contentWindow;
            if (!doc || !win) return null;
            // Prefer a "result" container if the bundle exposes one; else the body.
            var resultEl = doc.querySelector('[data-vance-result], [data-result], .result, .results, .calc-result, #result, #results');
            var body = (resultEl || doc.body || {});
            var text = (body.innerText || body.textContent || '').trim();
            if (text.length > 16000) text = text.slice(0, 16000) + '…';
            return {
                kind:   'dom-snapshot',
                url:    win.location && win.location.href,
                title:  doc.title || '',
                text:   text,
                hasResultContainer: !!resultEl,
                capturedAt: new Date().toISOString()
            };
        } catch (e) {
            // Cross-origin (shouldn't happen on this site) or iframe not yet loaded.
            return null;
        }
    }

    /**
     * Iframes that cooperate by emitting postMessage get the richer payload.
     * Iframe contract:
     *   in  ← { type: 'VANCE_TOOL_RESULT',       tool: '<slug>', payload: {...} }
     *   in  ← { type: 'VANCE_TOOL_SAVE_REQUEST', tool: '<slug>', payload: {...} }
     *   out → { type: 'VANCE_TOOL_SAVE_RESULT',  tool: '<slug>', ok, pending, message }
     *
     * `VANCE_SAVE_MALNUTRITION_RESULT` is the malnutrition bundle's legacy flat
     * message (type + result fields at the top level, no `payload` wrapper).
     */
    function replyToIframe(msg) {
        try {
            if (iframeEl && iframeEl.contentWindow) {
                iframeEl.contentWindow.postMessage(Object.assign({ type: 'VANCE_TOOL_SAVE_RESULT', tool: slug }, msg), '*');
            }
        } catch (e) { /* iframe gone — nothing to tell */ }
    }

    window.addEventListener('message', function (e) {
        var d = e && e.data;
        if (!d || typeof d !== 'object') return;
        // Only trust our own iframe — these messages trigger a write.
        if (iframeEl && e.source !== iframeEl.contentWindow) return;

        if (d.type === 'VANCE_TOOL_RESULT' && d.tool === slug) {
            pendingPayload = d.payload || {};
            return;
        }
        if (d.type === 'VANCE_SAVE_MALNUTRITION_RESULT' && slug === 'malnutrition-calculator') {
            var flat = Object.assign({}, d);
            delete flat.type;
            pendingPayload = flat;
            return;
        }
        // The iframe's own save button — run the exact same save path as the
        // wrapper button so there is one code path and one source of truth.
        if (d.type === 'VANCE_TOOL_SAVE_REQUEST' && d.tool === slug) {
            if (d.payload && Object.keys(d.payload).length > 0) {
                pendingPayload = d.payload;
            }
            doSave(buildPayloadAtSaveTime(), replyToIframe);
        }
    });

    function buildPayloadAtSaveTime() {
        // Prefer postMessage payload (structured), fall back to DOM snapshot (best-effort).
        if (pendingPayload && Object.keys(pendingPayload).length > 0) {
            return Object.assign({ kind: 'postmessage' }, pendingPayload);
        }
        var snap = snapshotIframe();
        if (snap) return snap;
        return { kind: 'placeholder', note: 'No iframe data captured', capturedAt: new Date().toISOString() };
    }

    /**
     * Single save path, shared by the wrapper button and the iframe's own save
     * button. `report` (optional) is called with { ok, pending, message } so the
     * caller can reflect the true outcome — never assume success.
     */
    function doSave(payload, report) {
        report = report || function () {};

        // Anonymous → open register modal with the payload. Nothing is persisted
        // until the account exists, so report back as pending, not saved.
        if (!loggedIn) {
            if (window.VanceRegisterModal && typeof window.VanceRegisterModal.open === 'function') {
                window.VanceRegisterModal.open({
                    tool: slug,
                    payload: payload,
                    onSuccess: function (resp) {
                        report({ ok: true });
                        showToast('Account created — opening your dashboard…', 4000);
                        setTimeout(function () {
                            window.location.href = (resp && resp.redirect) || '/dashboard/?vance_welcome=1';
                        }, 600);
                    }
                });
                report({ ok: false, pending: true, message: '💾 Create your free account to save' });
            } else {
                // Modal partial missing — graceful fallback.
                window.location.href = '/register/?from_tool=' + encodeURIComponent(slug);
            }
            return;
        }

        // Logged-in → AJAX save direct.
        if (saveBtn) saveBtn.disabled = true;
        var fd = new FormData();
        fd.append('action', 'vance_save_tool_result');
        fd.append('nonce', nonce);
        fd.append('tool', slug);
        fd.append('payload', JSON.stringify(payload));
        fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (j && j.success) {
                    report({ ok: true });
                    showToast('Saved to your dashboard ✓', 3500);
                    if (saveBtn) saveBtn.disabled = false;
                } else {
                    var msg = (j && j.data && j.data.message) || 'Could not save — please try again.';
                    report({ ok: false, message: msg });
                    showToast(msg, 4500);
                    if (saveBtn) saveBtn.disabled = false;
                }
            })
            .catch(function () {
                report({ ok: false, message: 'Network error — please try again.' });
                showToast('Network error — please try again.', 4500);
                if (saveBtn) saveBtn.disabled = false;
            });
    }

    // Manual save click handler.
    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            doSave(buildPayloadAtSaveTime());
        });
    }
})();
</script>
