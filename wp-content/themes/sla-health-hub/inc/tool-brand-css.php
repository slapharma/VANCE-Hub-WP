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

if ( ! function_exists( 'vance_tool_brand_css_recipes_embed' ) ) :
    /**
     * Modal-only rules for the IBD Recipes bundle.
     *
     * Full-page, the bundle opens on a 56px-padded teal gradient hero carrying a
     * gold pill badge, a 48px headline, a three-line paragraph and four emoji
     * stat chips. Inside the tool modal that lands under the modal's own title
     * bar and the action bar, so the reader met three headers before a single
     * recipe — the "much too noisy" hero.
     *
     * Here it collapses to one flush teal band holding just the section name.
     * The sticky search/filter bar then sits directly beneath it, which is the
     * part of the page anyone opening the planner actually came for.
     *
     * The hero carries no class of its own (the bundle styles it inline), so it
     * is matched on the one stable thing about it: the inline gradient. Anything
     * that fails to match simply keeps the full-page styling — no layout breaks.
     */
    function vance_tool_brand_css_recipes_embed() {
        return <<<CSS
/* === Modal mode: collapse the bundle's hero to a single band === */
main section[style*="linear-gradient(135deg"] {
    padding: 11px 24px !important;
    text-align: left !important;
}
main section[style*="linear-gradient(135deg"] > div {
    max-width: 1200px !important;
    text-align: left !important;
}
/* Direct div children are the badge and the emoji stat strip; the <p> is the
   marketing paragraph. All three restate what the modal title already says. */
main section[style*="linear-gradient(135deg"] > div > div,
main section[style*="linear-gradient(135deg"] > div > p {
    display: none !important;
}
main section[style*="linear-gradient(135deg"] h1 {
    font-size: 14px !important;
    font-weight: 700 !important;
    letter-spacing: 0.09em !important;
    text-transform: uppercase !important;
    line-height: 1.3 !important;
    margin: 0 !important;
}
/* The full-page rule below adds top padding to compensate for the hidden nav.
   With the band flush against the modal chrome that gap is dead space. */
main, [role="main"], body > div { padding-top: 0 !important; }
/* Recipe cards sit tighter in a modal's narrower column. */
main section[style*="max-width:1200px"] { padding-top: 20px !important; }
CSS;
    }
endif;

if ( ! function_exists( 'vance_tool_brand_css_recipes' ) ) :
    /**
     * IBD Recipes (Next.js) — hide the bundle's own header/nav (since we have
     * our own), drop logo strip, recolour primaries.
     *
     * @param bool $embed True when the tool is loaded inside the tool modal;
     *                    appends the compact-hero rules above.
     */
    function vance_tool_brand_css_recipes( $embed = false ) {
        $common = vance_tool_brand_css_common();
        $extra  = $embed ? "\n" . vance_tool_brand_css_recipes_embed() : '';
        return $common . "\n" . <<<CSS
/* Hide the internal Next.js header / top nav strip — we already have a hero
   above the iframe. Cover both the semantic <header> and any wrapping divs
   that Next likely uses (`[class*="header"]`, `[class*="navbar"]`,
   `[class*="topbar"]`). */
header,
nav,
[class*="Header" i],
[class*="header" i],
[class*="Navbar" i],
[class*="navbar" i],
[class*="TopBar" i],
[class*="topbar" i] {
    display: none !important;
}
/* But keep accessibility headers (h1/h2/h3) alive. */
h1, h2, h3, h4, h5, h6 { display: revert !important; }

/* Pad first content block since the hidden header ate the top spacing. */
main, [role="main"], body > div { padding-top: 16px !important; }

/* Common recipe-app accents (Tailwind orange-* / amber-*) → teal */
[class*="bg-orange-"],
[class*="bg-amber-"] { background-color: #008080 !important; }
[class*="text-orange-"],
[class*="text-amber-"] { color: #008080 !important; }
[class*="border-orange-"],
[class*="border-amber-"] { border-color: #008080 !important; }

/* === GREENS → BRAND TEALS === */
/* Tailwind / Vite-built greens (emerald, green, lime, teal-as-named) → brand palette */
[class*="bg-green-"],
[class*="bg-emerald-"],
[class*="bg-lime-"]      { background-color: #008080 !important; }
[class*="bg-green-50"],
[class*="bg-emerald-50"] { background-color: #f4ffff !important; }
[class*="bg-green-100"],
[class*="bg-emerald-100"]{ background-color: #def4f4 !important; }
[class*="bg-green-200"],
[class*="bg-emerald-200"]{ background-color: #aedbdb !important; }
[class*="bg-green-300"],
[class*="bg-emerald-300"]{ background-color: #78bfbf !important; }
[class*="text-green-"],
[class*="text-emerald-"],
[class*="text-lime-"]    { color: #008080 !important; }
[class*="border-green-"],
[class*="border-emerald-"],
[class*="border-lime-"]  { border-color: #008080 !important; }
[class*="ring-green-"],
[class*="ring-emerald-"] { --tw-ring-color: #008080 !important; }
[class*="from-green-"],
[class*="from-emerald-"],
[class*="from-lime-"]    { --tw-gradient-from: #008080 !important; }
[class*="to-green-"],
[class*="to-emerald-"],
[class*="to-lime-"]      { --tw-gradient-to: #006666 !important; }

/* Inline-style green hex catches (common Tailwind green shades + custom hexes used in the recipes bundle) */
[style*="#10b981" i],
[style*="#059669" i],
[style*="#047857" i],
[style*="#22c55e" i],
[style*="#16a34a" i],
[style*="#15803d" i],
[style*="#84cc16" i],
[style*="#65a30d" i],
[style*="#4d7c0f" i] {
    color: #008080 !important;
}
[style*="background:#10b981" i],
[style*="background-color:#10b981" i],
[style*="background: #10b981" i],
[style*="background-color: #10b981" i],
[style*="background:#059669" i],
[style*="background-color:#059669" i],
[style*="background:#22c55e" i],
[style*="background-color:#22c55e" i],
[style*="background:#16a34a" i],
[style*="background-color:#16a34a" i] {
    background-color: #008080 !important;
    background: #008080 !important;
}

/* SQUARE CORNERS — kill border-radius on every content container/card so the
   recipes app matches the rest of the brand (which uses border-radius: 0). */
*,
[class*="rounded"],
[class*="card"],
[class*="Card"],
[class*="container"],
[class*="Container"] {
    border-radius: 0 !important;
}
/* Keep the visual circles round (avatar, status dots, icon buttons) — those
   tend to use rounded-full / 9999px / 50%. Restore them by re-asserting. */
[class*="rounded-full"],
[class*="circle"],
[style*="border-radius:9999"],
[style*="border-radius: 9999"],
[style*="border-radius:50%"],
[style*="border-radius: 50%"] {
    border-radius: 9999px !important;
}
CSS
        . $extra;
    }
endif;
