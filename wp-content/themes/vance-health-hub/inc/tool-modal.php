<?php
/**
 * Unified glass Tool Modal — opens the site's interactive tools in one frosted
 * overlay from ANYWHERE: the tools page, the dashboard, a promo block, the
 * footer menu, a featured-tool widget, or any <a> that links to a tool page.
 *
 * Mounted site-wide via footer.php + footer-dashboard.php. Deliberately NOT
 * mounted on the chromeless embed page (footer-embed.php), so a tool loaded in
 * the modal can never recursively spawn another modal inside itself.
 *
 * Behaviour:
 *   - Iframe tools (malnutrition-calculator) load their own WP page with
 *     ?tool_embed=1 (chromeless) inside the modal iframe; the existing
 *     tool-page-shell save / brand-inject / autoresize plumbing runs unchanged.
 *   - The health quiz reuses its existing inline modal (openQuizModal()).
 *   - Progressive enhancement: every trigger is a real <a href> to the tool
 *     page, so tools still work with JS off or when opened in a new tab.
 *
 * The recipe hub/planner (/gastro-meal-planner/) is deliberately NOT a modal
 * tool here — direction 2026-08-20: it's a full page, always the primary
 * entry point, linked to directly from nav/footer/tool cards. Loading the
 * whole hub chromelessly inside this modal's iframe is also what caused the
 * "Add to meal plan" CTA on a single recipe page to open here at the recipe
 * list instead of scrolling to the Weekly Plan — the modal's `tool.url` is a
 * bare page URL with no query string, so a link's own `?add=<slug>#planner`
 * was silently discarded by the interceptor below. "Add to meal plan" now
 * opens its own small native picker instead (assets/js/recipe-quickadd.js) —
 * see single-vance_recipe.php — which is what "modal only for tool features"
 * means in practice: a purpose-built modal for one action, not the whole tool
 * loaded inside a modal.
 *
 * Public API:  window.VanceToolModal.open('<slug>'),  window.VanceToolModal.close()
 * Triggers:    [data-vance-tool-open="<slug>"]  OR  any <a> to a known tool path.
 *              Opt a link out with  data-no-tool-modal  or  target="_blank".
 */

if ( defined( 'VANCE_TOOL_MODAL_RENDERED' ) ) {
    return;
}
define( 'VANCE_TOOL_MODAL_RENDERED', true );

$vance_tm_tools = array(
    'malnutrition-calculator' => array(
        'title'  => vance_get_theme_mod( 'vance_tool_malnutrition_name', 'IBD Malnutrition Calculator' ),
        'url'    => home_url( '/malnutrition-calculator/' ),
        'inline' => false,
    ),
    'healthcare-quiz' => array(
        'title'  => 'Gastro Health Survey',
        'url'    => home_url( '/healthcare-quiz/' ),
        'inline' => true, // reuses openQuizModal()
    ),
);

// pathname -> slug map for the global <a> interceptor (lowercased, no trailing slash).
// /gastro-meal-planner and its old aliases are deliberately absent — see the
// file header. Any lingering data-vance-tool-open="ibd-recipes" attribute
// elsewhere in the theme now just no-ops (openModal() returns false for an
// unknown slug and the click falls through to a normal navigation).
$vance_tm_paths = array(
    '/malnutrition-calculator' => 'malnutrition-calculator',
    '/healthcare-quiz'         => 'healthcare-quiz',
);
?>
<div id="vance-tool-modal" class="vance-tool-modal vance-glass-scrim" role="dialog" aria-modal="true"
     aria-hidden="true" aria-labelledby="vance-tool-modal-title">
    <div class="vance-tool-modal__panel vance-glass-panel" tabindex="-1">
        <div class="vance-tool-modal__bar">
            <h2 id="vance-tool-modal-title" class="vance-tool-modal__title">Tool</h2>
            <?php // No "open full page" escape hatch: the modal is the tool surface. ?>
            <div class="vance-tool-modal__bar-actions">
                <button type="button" class="vance-tool-modal__close" data-vance-tool-close aria-label="Close tool">&times;</button>
            </div>
        </div>
        <div class="vance-tool-modal__body">
            <?php // No loading="lazy": src is only set when the modal opens, and deferring it then is the whole delay. ?>
            <iframe id="vance-tool-modal-iframe" title="Vance tool" allow="clipboard-write" loading="eager" fetchpriority="high"></iframe>
            <div class="vance-tool-modal__loading" aria-hidden="true"><span class="vance-tool-modal__spinner"></span></div>
        </div>
    </div>
</div>
<style>
.vance-tool-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 100000;
    padding: clamp(12px, 3vw, 40px);
    align-items: center;
    justify-content: center;
}
.vance-tool-modal.is-open { display: flex; }
.vance-tool-modal__panel {
    width: 100%;
    max-width: 1180px;
    height: min(92vh, 980px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding: 0;
}
.vance-tool-modal__bar {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 20px;
    border-bottom: 1px solid rgba(0,128,128,0.18);
    background: rgba(255,255,255,0.55);
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
}
.vance-tool-modal__title {
    font-family: 'Outfit', sans-serif;
    font-size: 18px;
    font-weight: 800;
    color: #0A1929;
    margin: 0;
}
.vance-tool-modal__bar-actions { display: flex; align-items: center; gap: 14px; }
.vance-tool-modal__close {
    width: 40px; height: 40px;
    display: flex; align-items: center; justify-content: center;
    font-size: 26px; line-height: 1; color: #0A1929;
    background: rgba(255,255,255,0.6);
    border: 1px solid rgba(255,255,255,0.7);
    border-radius: 0 !important;
    cursor: pointer;
    transition: background-color .2s ease;
}
.vance-tool-modal__close:hover { background: #fff; }
.vance-tool-modal__close:focus-visible { outline: 3px solid var(--primary-pale); outline-offset: 2px; }
.vance-tool-modal__body { position: relative; flex: 1 1 auto; min-height: 0; background: #fff; }
.vance-tool-modal__body iframe { width: 100%; height: 100%; border: 0; display: block; background: #fff; }
.vance-tool-modal__loading {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    background: #fff;
}
.vance-tool-modal__loading.is-hidden { display: none; }
.vance-tool-modal__spinner {
    width: 44px; height: 44px;
    border: 4px solid var(--primary-pale);
    border-top-color: var(--primary-color);
    border-radius: 50%;
    animation: vanceToolSpin .8s linear infinite;
}
@keyframes vanceToolSpin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) {
    .vance-tool-modal__close { transition: none; }
    .vance-tool-modal__spinner { animation-duration: 1.6s; }
}
@media (max-width: 600px) {
    .vance-tool-modal { padding: 0; }
    .vance-tool-modal__panel { max-width: none; height: 100vh; height: 100dvh; border-radius: 0 !important; }
}
</style>
<script>
(function () {
    var CFG = <?php echo wp_json_encode( array( 'tools' => $vance_tm_tools, 'paths' => $vance_tm_paths ) ); ?>;
    var modal = document.getElementById('vance-tool-modal');
    if (!modal) { return; }
    var panel      = modal.querySelector('.vance-tool-modal__panel');
    var titleEl    = document.getElementById('vance-tool-modal-title');
    var iframe     = document.getElementById('vance-tool-modal-iframe');
    var loading    = modal.querySelector('.vance-tool-modal__loading');
    var closeBtn   = modal.querySelector('.vance-tool-modal__close');
    var lastTrigger = null;
    var loadedSlug  = null;

    function normalizeSlug(slug) {
        return slug ? String(slug).toLowerCase() : '';
    }
    function addEmbedParam(url) {
        return url + (url.indexOf('?') === -1 ? '?' : '&') + 'tool_embed=1';
    }

    function openModal(slug, trigger) {
        slug = normalizeSlug(slug);
        var tool = CFG.tools[slug];
        if (!tool) { return false; }
        lastTrigger = trigger || document.activeElement || null;

        // The quiz keeps its own inline modal.
        if (tool.inline) {
            if (typeof window.openQuizModal === 'function') {
                var qm = document.getElementById('vance-quiz-modal');
                // Modal kit toggles `is-open`, not an inline display style.
                if (qm && qm.classList.contains('is-open')) { return true; } // already open
                window.openQuizModal();
                return true;
            }
            window.location.href = tool.url; // no inline modal on this page
            return true;
        }

        // Iframe tools.
        titleEl.textContent = tool.title || 'Tool';
        if (loadedSlug !== slug) {
            if (loading) { loading.classList.remove('is-hidden'); }
            iframe.src = addEmbedParam(tool.url);
            loadedSlug = slug;
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        (closeBtn || panel).focus();
        return true;
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (lastTrigger && document.contains(lastTrigger) && typeof lastTrigger.focus === 'function') {
            lastTrigger.focus();
        }
        lastTrigger = null;
    }

    if (iframe) {
        iframe.addEventListener('load', function () {
            if (loading) { loading.classList.add('is-hidden'); }
        });
    }

    window.VanceToolModal = { open: openModal, close: closeModal };

    /**
     * Warm a tool on intent.
     *
     * The tool lives two documents deep — the modal iframe loads a WordPress
     * wrapper page, which loads the bundle in an iframe of its own — so a cold
     * click meant waiting for two round trips plus everything the wrapper page
     * pulls in. Prefetching on hover/focus/touch buys the few hundred
     * milliseconds between "the pointer is heading for this" and the click,
     * which is usually the whole perceived delay.
     *
     * <link rel="prefetch"> only, never a hidden iframe: it is a low-priority
     * hint the browser is free to ignore or drop under load, it never executes
     * the page, and it costs nothing for anyone who does not hover a tool.
     * Each tool is warmed at most once per page.
     */
    var warmed = {};
    function warmTool(slug) {
        slug = normalizeSlug(slug);
        var tool = CFG.tools[slug];
        if (!tool || tool.inline || warmed[slug]) { return; }
        warmed[slug] = true;
        try {
            var link = document.createElement('link');
            link.rel = 'prefetch';
            link.as = 'document';
            link.href = addEmbedParam(tool.url);
            document.head.appendChild(link);
        } catch (e) { /* prefetch is an optimisation — never break the click path */ }
    }

    function slugFromEvent(e) {
        if (!e.target || !e.target.closest) { return ''; }
        var openEl = e.target.closest('[data-vance-tool-open]');
        if (openEl) { return openEl.getAttribute('data-vance-tool-open'); }
        var a = e.target.closest('a[href]');
        if (!a || a.hasAttribute('data-no-tool-modal')) { return ''; }
        if (a.origin && a.origin !== window.location.origin) { return ''; }
        return CFG.paths[(a.pathname || '').replace(/\/+$/, '').toLowerCase()] || '';
    }

    ['pointerenter', 'focusin', 'touchstart'].forEach(function (evt) {
        document.addEventListener(evt, function (e) {
            var slug = slugFromEvent(e);
            if (slug) { warmTool(slug); }
        }, { capture: true, passive: true });
    });

    // --- Delegated triggers ---
    document.addEventListener('click', function (e) {
        if (e.defaultPrevented) { return; }
        if (!e.target || !e.target.closest) { return; }

        // 1) explicit opener attribute
        var openEl = e.target.closest('[data-vance-tool-open]');
        if (openEl) {
            if (openModal(openEl.getAttribute('data-vance-tool-open'), openEl)) { e.preventDefault(); }
            return;
        }

        // 2) global interceptor for plain links to a tool page
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
        var a = e.target.closest('a[href]');
        if (!a) { return; }
        if (a.hasAttribute('data-no-tool-modal')) { return; }
        if (a.target && a.target !== '' && a.target !== '_self') { return; } // respect _blank etc.
        if (a.origin && a.origin !== window.location.origin) { return; }      // same-origin only
        var path = (a.pathname || '').replace(/\/+$/, '').toLowerCase();
        var slug = CFG.paths[path];
        if (!slug) { return; }
        if (openModal(slug, a)) { e.preventDefault(); }
    }, false);

    // --- Close affordances ---
    modal.addEventListener('click', function (e) {
        if (e.target === modal) { closeModal(); return; }              // backdrop
        if (e.target.closest && e.target.closest('[data-vance-tool-close]')) { closeModal(); }
    });
    document.addEventListener('keydown', function (e) {
        if (!modal.classList.contains('is-open')) { return; }
        if (e.key === 'Escape') { closeModal(); return; }
        if (e.key === 'Tab') {
            var nodes = panel.querySelectorAll('a[href], button, iframe, [tabindex]:not([tabindex="-1"])');
            var f = Array.prototype.filter.call(nodes, function (el) { return el === iframe || el.offsetParent !== null; });
            if (!f.length) { return; }
            var first = f[0], last = f[f.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    });
})();
</script>
