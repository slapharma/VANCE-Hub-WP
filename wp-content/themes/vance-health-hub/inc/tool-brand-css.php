<?php
/**
 * Per-tool brand-CSS overrides — injected into the iframe by inc/tool-page-shell.php.
 *
 * The Vite/Next bundles for our tools were built with the legacy SLA orange
 * palette (`#fd4f00`) in places, plus generic semantic chrome (header, nav)
 * that we don't want on the public-facing tool pages. Rather than rebuild
 * the bundles, we inject CSS into their `contentDocument` at runtime.
 *
 * The selectors are deliberately generic — Vite minification obfuscates
 * class names, so we target:
 *   1. `[style*="fd4f00"]`  → catches inline styles carrying the legacy hex
 *   2. semantic elements (`button`, `header`)
 *   3. common Tailwind-ish patterns (`.bg-primary`, `[class*="primary"]`)
 *
 * Each function returns a CSS string. Call from a per-tool wrapper page:
 *   $vance_tool_brand_css = vance_tool_brand_css_calculator();
 *
 * vance_tool_brand_css_recipes() and _recipes_embed() were removed here in
 * Phase 5 of the recipe rebuild: page-gastro-recipies.php is a native page
 * now, with nothing left to inject CSS into.
 */

if ( ! function_exists( 'vance_tool_brand_css_common' ) ) :
    /**
     * CSS shared by every tool — kills inline orange + nudges body chrome.
     */
    function vance_tool_brand_css_common() {
        return <<<CSS
/* === VANCE brand override (injected from parent) === */
/* Replace legacy SLA orange (#fd4f00) wherever it was used inline. */
[style*="fd4f00" i] { color: #008080 !important; }
[style*="background:#fd4f00" i],
[style*="background-color:#fd4f00" i],
[style*="background: #fd4f00" i],
[style*="background-color: #fd4f00" i],
[style*="background:#FD4F00" i],
[style*="background-color:#FD4F00" i] {
    background-color: #008080 !important;
    background: #008080 !important;
}
[style*="border-color:#fd4f00" i],
[style*="border-color: #fd4f00" i] { border-color: #008080 !important; }
[style*="border:#fd4f00" i],
[style*="border:1px solid #fd4f00" i],
[style*="border: 1px solid #fd4f00" i] { border-color: #008080 !important; }

/* Generic primary-action recolour for Tailwind/Vite class names. */
button.primary,
button.btn-primary,
.btn-primary,
[class*="bg-primary"],
[class*="primary-bg"] {
    background-color: #008080 !important;
    border-color: #008080 !important;
}

/* Page chrome — give the bundle a clean white surface flush with the parent card. */
html, body {
    background: #ffffff !important;
    color: #0A1929 !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Outfit', 'Inter', sans-serif !important;
}
CSS;
    }
endif;

if ( ! function_exists( 'vance_tool_brand_css_calculator' ) ) :
    /**
     * Common + calculator-specific tweaks (malnutrition).
     */
    function vance_tool_brand_css_calculator() {
        $common = vance_tool_brand_css_common();
        return $common . "\n" . <<<CSS
/* Hide any internal page padding to avoid double padding inside our card. */
body { padding: 12px 16px !important; }
/* Force focus / hover ring to brand teal. */
*:focus { outline-color: #008080 !important; }
button:hover, .btn:hover { filter: brightness(0.92); }
CSS;
    }
endif;

