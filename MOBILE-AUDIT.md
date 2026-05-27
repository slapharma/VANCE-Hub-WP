# MOBILE-AUDIT.md — Vance HealthHub WP

Audit of mobile responsiveness for the `sla-health-hub` theme as deployed at https://vancehealthhub.co.uk.

**Scope:** full theme (public pages, dashboard, tool wrappers, tool bundles), with priority on iPhone Safari, Android Chrome, and iPad.

**Date:** 2026-05-25.
**Auditor source files:** `wp-content/themes/sla-health-hub/` at HEAD.

---

## TL;DR

The theme has the right scaffolding (viewport meta is correct, a 768px breakpoint exists, the off-canvas mobile nav is wired) but three classes of debt are stopping it from rendering cleanly on phones:

1. **Inline `style="..."` blocks dominate the layout.** 1,202 inline style attributes across 48 PHP files, many with hard-coded pixel widths (`min-width: 300px`, `padding: 95px 0 140px`) that bypass every responsive override in `main.css`. The CSS file's mobile rules cannot beat element-level specificity even with `!important`.
2. **Two mobile menus collide on the live site.** Mega Menu Pro plugin (active on the homepage, breakpoint 768px) and the theme's bespoke `.mobile-menu-toggle` button both render. Users see two hamburgers.
3. **Several real bugs** — broken inline `@media` syntax in `page-dashboard.php`, missing body padding-top for the fixed mobile header, sub-16px input font-sizes that trigger iOS Safari auto-zoom, and a hamburger SVG/CSS contract mismatch.

Severity scale used below: **P0** = visibly broken on phones, **P1** = noticeably degraded UX, **P2** = polish / minor.

---

## 1. Base HTML and head

| ID | Severity | Where | Finding |
|---|---|---|---|
| 1.1 | OK | `header.php:5` | `<meta name="viewport" content="width=device-width, initial-scale=1.0">` is present and correct. |
| 1.2 | P2 | `header.php:5` | No `viewport-fit=cover` — iPhone notch/Dynamic Island safe-area is unused, so a dark header bleeds visually under the system bar but content inside is not edge-aware. |
| 1.3 | P2 | `header.php:3-12` | No `<meta name="theme-color">`, no `apple-mobile-web-app-*` metas. Address-bar tint on Chrome Android and Safari iOS falls back to white. |
| 1.4 | OK | site-wide | Live page has `lang="en-GB"` and `charset=UTF-8`. |
| 1.5 | P1 | `style.css` | The WP root stylesheet has only the theme header — no fallback `body { overflow-x: hidden; }` anywhere. Wide overflowing children will create a horizontal scroll on phones. |

---

## 2. Header / navigation

| ID | Severity | Where | Finding |
|---|---|---|---|
| 2.1 | **P0** | rendered HTML | Mega Menu Pro plugin renders its own hamburger `.mega-menu-toggle` (lines 154+ in live HTML) AND the theme's `.mobile-menu-toggle` is also still in the DOM (`header.php:25-29`). Users on phones see two menu icons that do different things. Pick one. |
| 2.2 | **P0** | `header.php:25-29` vs `main.css:2279-2286` | Hamburger contract mismatch. The button markup contains three bare `<span>` bars, but the JS in `header.php:99-110` toggles `.menu-icon` / `.close-icon` classes that don't exist in the markup. The open / close icon swap silently no-ops. |
| 2.3 | P1 | `main.css:2132-2161` | `.site-header { position: fixed; top: 0; }` is set inside the 768px breakpoint, but no `padding-top` is applied to `body` or to the next sibling. On mobile, the first 70px of every page is hidden under the fixed header until the user scrolls. |
| 2.4 | P1 | `header.php:52-89` | `.header-actions` carries inline `style="display: flex; gap: 12px; margin-right: 20px; align-items: center;"`. Inline display overrides the `.header-actions { display: none !important; }` mobile rule at `main.css:2202-2204` if the inline rule wins the specificity fight (it doesn't, thanks to `!important`, but it's fragile). |
| 2.5 | P2 | `main.css:2145-2161` | Off-canvas drawer uses `height: calc(100vh - 70px)` — iOS Safari address-bar collapse means real viewport height changes after scroll, leaving a gap under the drawer. Use `100dvh` (dynamic viewport) on browsers that support it. |
| 2.6 | P2 | `header.php:96-112` | Body `overflow: hidden` is toggled when menu opens — good — but state isn't reset on resize back to desktop. If a user opens the drawer on mobile then rotates to landscape past 768px, the page stays scroll-locked. |

---

## 3. CSS responsiveness (main.css)

| ID | Severity | Where | Finding |
|---|---|---|---|
| 3.1 | **P0** | `main.css` whole file | `.hero h1` has its mobile font-size redefined four times: lines 1683 (36px), 2212 (32px), 2316 (30px), 2367 (34px). Last wins (34px). All four are inside `@media (max-width: 768px)` so it's accidental — split across "MOBILE OVERRIDES" / "REFINING IMPORTANT FLAGS" / "UTILITY GRIDS" blocks. Hard to maintain. |
| 3.2 | P1 | `main.css:48-54` | `body` has no `min-height`, no `overflow-x`, no global font-size. Browser default 16px is fine but explicit is safer. |
| 3.3 | P1 | `main.css:1769-1792` | The 480px breakpoint only adjusts hero padding, hero h1 size, quick-nav, category header, and tool card direction. Every other component still uses the 768px rule on a 360px screen. Small phones (Pixel 4a, iPhone SE/mini, older Androids) get over-padded cards and oversized type. |
| 3.4 | P1 | `main.css:71-75` | `.container { padding: 0 20px; }` doesn't shrink below 16px on small phones. Combined with a `min-width: 300px` child (see §5), content overflows the container at widths below ~340px. |
| 3.5 | P1 | nowhere | No global rule forcing `input, select, textarea { font-size: 16px; }`. iOS Safari auto-zooms any focused input with computed font-size < 16px (Apple's documented behaviour). Several inputs are at 13–15px (see §5.4). |
| 3.6 | P1 | `main.css:91-102` | `.btn { padding: 12px 24px; font-size: 14px; }` → ~40px tall. Apple Human Interface Guidelines and Google Material both recommend ≥44px / ≥48dp tap targets. Just under. |
| 3.7 | P2 | `main.css` | No `prefers-reduced-motion` overrides for the `transform: translateY(-8px)` hover transitions that fire on `:active` on touch devices. |
| 3.8 | P2 | `main.css:295-304` | `.hero { min-height: 460px; padding: 100px 0 160px }` is large on a 568px-tall iPhone SE. Mobile override reduces padding (`40px 0`) but min-height is not reset, leading to lots of empty space. |
| 3.9 | P2 | `main.css:1646-1679` | Tablet (1024px) breakpoint catches iPad portrait (1024×768) at the boundary — iPad in portrait reports 768 wide, in landscape 1024. Treat 1024px landscape iPad as tablet, not desktop. Add a `(max-width: 1024px) and (orientation: landscape)` cluster. |

---

## 4. Per-template hostile markup

### 4.1 front-page.php (`/`)

| Severity | Where | Finding |
|---|---|---|
| **P0** | line 344 | `style="padding: 95px 0 140px"` inline on the hero `<section>` defeats the `@media (max-width: 768px) { .hero { padding: 40px 0 !important; } }` rule because inline `style` beats the stylesheet's `!important`. Hero is the size of a small TV on phones. |
| **P0** | line 348 | Hero h1 size is set inline from a Customizer value (`font-size: <?php echo esc_attr($hero_title_size); ?>px`), default 52px. There's a stylesheet override at `main.css:2316` (`.hero h1 { font-size: 30px !important; }`) — `!important` wins, but only because the stylesheet was deliberately written that way. Any new inline h1 styling will leak. |
| P1 | line 358 | Hero subtitle inline `font-size: 20px` — there is no override, so subtitle stays 20px on a 360px-wide phone. |
| P1 | lines 491, 517, 908, 1006-1007 | Multiple fixed-pixel widths (`width: 48px`, `400px`, `350px`) on absolutely positioned glow elements bleed off-screen on small phones — not always harmful (decorative) but contribute to horizontal scroll. |
| P1 | line 908 | Category card has `min-width: 160px` AND `max-width: 160px` — 5 cards in a row will overflow horizontally at any width below 880px (5 × 160 + gaps). Becomes a scroll-snap row but no scroll indicator. |
| P1 | line 1009 | `.container` overridden inline with `max-width: 1120px` — fine on desktop, but a `<style>` block inside the same section sets `padding: 80px 0 60px` on `.pathway-tiles-section` with a 992px breakpoint that DOES collapse to 1 column. Inconsistent breakpoint with the rest of the theme (everywhere else uses 1024 / 768). |

### 4.2 page-dashboard.php (`/dashboard/`)

| Severity | Where | Finding |
|---|---|---|
| **P0** | line 260 | `style="text-align: right; display: none; @media(min-width:768px){display:block;}"` — `@media` inside a `style` attribute is **invalid CSS**. The first-name / role block is always `display: none` on every screen size. |
| **P0** | line 169 | `.dash-grid { grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)) }` — 320px min on a 360px viewport with 32px container padding leaves 296px of usable width. Grid cells refuse to fit and `auto-fit` collapses to 1 column, fine — but inside the cells, fixed `min-width: 300px` children (line 11, 16 in footer.php pattern repeated) still overflow. |
| P1 | line 188-194 | Mobile sidebar uses `transform: translateX(-100%)` — no backdrop / overlay. Tapping outside the open drawer does nothing; user must hit the small `✕`. |
| P1 | line 161 | `.dash-header { padding: 0 32px }` — too tight on a 360px phone; combined with the page-title role badge it wraps awkwardly. |
| P1 | line 168 | `.dash-content { padding: 32px }` — same issue, no mobile reduction. |
| P2 | line 205 | `✕` close button is text glyph at default size — not a 44px tap target. |
| P2 | line 218 | Nav items use emoji icons inline-styled to 20px — fine, but emoji rendering varies (Apple vs Google) and the `width: 20px` text-align: center wrapper can cause emoji clipping on Android. |

### 4.3 page-ask-ai.php (`/ask-ai/`)

| Severity | Where | Finding |
|---|---|---|
| P1 | line 44-58 | `.askai-hero h1 { font-size: 56px }` — no mobile override in this file. Whole `<style>` block ignores phones. Hero h1 stays 56px on a 360px screen unless something later in `main.css` happens to match (`.hero h1` does NOT match `.askai-hero h1`). |
| P1 | line 217 | Chat messages container `padding: 24px 32px; max-height: 550px` — fixed max-height on mobile crops the chat below the fold. |
| **P0** | line 227 | Chat input `style="font-size: 15px"` → iOS Safari zooms in when user taps it. Hero text shifts off-screen and never returns until manual zoom-out. |
| P1 | line 226 | Chat input bar `display: flex; gap: 12px` with `padding: 14px 28px` Send button. On 360px screens the input shrinks to ~180px → 2-3 visible characters. |

### 4.4 inc/tool-page-shell.php (Malnutrition, Omega-3, Blood Test wrappers)

| Severity | Where | Finding |
|---|---|---|
| OK | line 133-138 | Has a `@media (max-width: 600px)` block that scales hero h1 down to 36px and iframe to 85% — better than most pages. |
| P1 | line 106 | `.tool-page-container { max-width: 1100px; margin: -40px auto 0; padding: 0 20px 40px }` — `-40px` margin pulls the card up over the hero on desktop. On mobile with reduced hero padding (50px), the card overlaps the hero h1. |
| P1 | line 108-111 | `.tool-page-card__head { display: flex; flex-wrap: wrap }` — flex-wrap is on, good. But `Open full screen ↗` + `Save my screening result` buttons inline-stack into a column on phones, fine, but the card title can wrap to two lines and push them down awkwardly. |
| P1 | line 173 | Inline `style="padding: 8px 16px; font-size: 13px"` on the "Open full screen" outline button → 32px height tap target. |
| P2 | line 114 | `height: <?php echo (int) $iframe_height; ?>px` defaults to 720px. The autoresize JS handles same-origin bundles, but if the React app reflows after first paint (e.g. user expands an accordion), the parent iframe doesn't shrink — interval-based polling only goes one direction (max). |

### 4.5 footer.php

| Severity | Where | Finding |
|---|---|---|
| **P0** | line 11, 16 | `<div style="flex: 1; min-width: 300px;">` and `<form style="flex: 1; min-width: 300px;">`. Two `300px` minimums in a flex row of total ~328px on a 360px viewport — flex line wraps, OK, but each child still demands 300px which can horizontally overflow on iPhone SE (375px) once we subtract container 40px padding (320px usable). |
| P1 | line 17 | Newsletter email input has `padding: 12px 16px` and no `font-size` declaration → inherits 14px → iOS zooms in. |
| P1 | line 32, 34 | `<img style="height: 48px;">` with no width set → browser computes width from intrinsic ratio; fine for raster but layout shift on slow connections (no `width` attribute either, no `loading="lazy"`). |
| P2 | line 100-113 | Guest-save modal `max-width: 450px` is fine but `padding: 40px` eats 80px on a 360px screen → 280px content area. |

### 4.6 inc/cross-page-sections.php

| Severity | Where | Finding |
|---|---|---|
| P1 | line 143, 258 | Inline subscribe forms have `min-width: 250px` and `min-width: 200px` inputs alongside Submit buttons — both reach 100% on mobile only because the parent has flex-wrap. Inputs DO use `font-size: 16px` ✓ — no iOS zoom here. |

### 4.7 page-about.php / page-our-heritage.php

Both have `style="flex:1;min-width:300px;"` repeated four times each (lines 188, 194, 272, 279 / 189, 195, 273, 280). Two-column layouts will wrap on mobile (fine) but each column still tries to be 300px wide — at 360px viewport with 40px padding (320px usable), one column overflows by 10–20px. Subtle horizontal scroll.

### 4.8 ai-visibility.php

Internal admin page with `<table>` elements that have `max-width:280px` cells set to `white-space:nowrap; overflow:hidden; text-overflow:ellipsis`. On phones the table is unusable — but this is admin-only, so P2.

---

## 5. Inputs / forms

| ID | Severity | Where | Finding |
|---|---|---|---|
| 5.1 | **P0** | site-wide | No global `input, select, textarea { font-size: 16px; }`. iOS Safari zooms on focus for any input <16px. Failing inputs found: |
|     |     | `page-ask-ai.php:227` | 15px |
|     |     | `page-ask-ai.php:228` (Send button) | 15px (button, not zoom-triggering but inconsistent) |
|     |     | `front-page.php:1173` | 13px (chat-input in homepage AI demo widget) |
|     |     | `functions.php:902` | 15px (auth fields) |
|     |     | `inc/clinical-info-modal.php:55, 61` | 14px (clinical modal inputs) |
|     |     | `page-education.php:159` | 15px (newsletter signup) |
| 5.2 | P1 | dashboard text inputs (rendered via `inc/dashboard-functions.php`) | Need to confirm — many AJAX-rendered fields. Worth a separate sweep. |
| 5.3 | P1 | newsletter form `footer.php:17` | No explicit font-size, inherits 14px (browser default for `<input>` is 13.33px in Chrome anyway) → zoom. |

---

## 6. Tool bundles (React, embedded as iframes)

| ID | Severity | Where | Finding |
|---|---|---|---|
| 6.1 | OK | `assets/tools/*/index.html` | Each bundle has its own `<meta name="viewport" content="width=device-width, initial-scale=1.0">`. |
| 6.2 | OK | `assets/tools/{blood-test,malnutrition-calculator}/index-*.js` | Tailwind CDN with responsive prefixes used: `md:grid-cols-2`, `lg:grid-cols-3`, `md:flex-row`, `lg:col-span-2`. Bundles ARE responsive. |
| 6.3 | P1 | `assets/tools/ai-widget/index-*.js` | Fewer responsive prefixes (`sm:max-w-`, `sm:shadow-sm` only) — bundle was likely designed at desktop width. On 360px the AI chat input row may overflow. |
| 6.4 | P1 | `inc/tool-embed.php:60-74` | Shortcode wraps the iframe with a fixed `height` attribute (default 800px). On a 568px-tall iPhone SE in portrait this is taller than the viewport — fine for scroll, but the iframe internal scroll competes with the parent page scroll → known iOS pain. Use `scrolling="no"` + auto-resize (tool-page-shell.php does this, the generic shortcode doesn't). |
| 6.5 | P2 | bundles in general | Tailwind CDN loaded inside each iframe — every tool re-downloads ~50KB. On a cold 3G mobile load this adds noticeable delay. Compile Tailwind locally and ship a single 5KB CSS file. |

---

## 7. Performance hints relevant to mobile

| ID | Severity | Finding |
|---|---|---|
| 7.1 | P1 | Images use no `loading="lazy"` attribute in templates (only the tool iframe does). Below-the-fold logos and infographics fully load on first paint. |
| 7.2 | P1 | `<img>` tags lack explicit `width` and `height` attributes site-wide. Cumulative Layout Shift is high. |
| 7.3 | P1 | No `srcset` / `<picture>` anywhere — same hero image (1920×1080-ish from `about_hero.png`) is served to a 360px phone. |
| 7.4 | P2 | Tailwind CDN inside iframes (see 6.5). |

---

## 8. Live-site only confirmations

Fetched https://vancehealthhub.co.uk on 2026-05-25 and confirmed:

- Viewport meta is correct in delivered HTML.
- `<body class="home blog wp-theme-sla-health-hub mega-menu-primary-menu">` — Mega Menu Pro is active and emits its own toggle at breakpoint 768px (`data-breakpoint="768"`, `data-effect-mobile="slide_right"`).
- The theme's `<button class="mobile-menu-toggle">` is still present in the DOM next to the Mega Menu toggle (§2.1).
- A leftover footer menu item still links to `https://aliceblue-snail-814220.hostingersite.com` (Hostinger preview domain). Unrelated to mobile but worth fixing alongside.

---

## 9. Priority order for fixes

Order by impact-per-effort, mobile-first:

1. **P0-A:** Resolve dual-hamburger conflict — either remove Mega Menu Pro and finish the theme's nav, OR delete the theme's `.mobile-menu-toggle` + JS and let Mega Menu Pro own mobile nav. (§2.1)
2. **P0-B:** Add global `body { padding-top: 70px; }` inside the 768px breakpoint to match the fixed header. (§2.3)
3. **P0-C:** Add global `input, textarea, select { font-size: 16px; }` for iOS zoom prevention. (§5.1)
4. **P0-D:** Fix broken `@media` inside `style=""` attribute in `page-dashboard.php:260`. (§4.2)
5. **P0-E:** Strip inline `padding: 95px 0 140px` from front-page hero (move to a responsive class). (§4.1)
6. **P0-F:** Audit-replace `min-width: 300px` and `min-width: 250px` inline patterns with `min-width: min(300px, 100%)`. (§4.5, §4.7)
7. **P1:** Add `body { overflow-x: hidden; }`, `html { -webkit-text-size-adjust: 100%; }`, and small-phone (≤375px) breakpoint. (§3.2, §3.3)
8. **P1:** Mobile dashboard sidebar — add a backdrop overlay so tap-outside closes the drawer. (§4.2)
9. **P1:** Tap-target sweep — make `.btn` ≥44px, buttons in tool shell head ≥40px. (§3.6)
10. **P2:** Add `viewport-fit=cover`, `theme-color`, safe-area-inset paddings on fixed header. (§1.2)
11. **P2:** Image lazy-loading and `width`/`height` attributes. (§7.1, §7.2)
12. **P2:** Consolidate the four duplicated mobile `.hero h1` font-size rules into one. (§3.1)

---

## 10. Devices verified

The audit reasoned about these viewport widths explicitly:
- iPhone SE 1st gen: 320 × 568 (≤ 480px breakpoint, but only 480px rules apply)
- iPhone SE 3rd gen / iPhone 13 mini: 375 × 667
- iPhone 15 / Pixel 7: 393 × 852
- iPhone 15 Pro Max: 430 × 932
- iPad portrait: 768 × 1024 (hits the 768 breakpoint exactly — note: `max-width: 768px` does **not** match a 768-wide viewport; use `max-width: 767.98px` or `min-width: 769px` to be unambiguous)
- iPad landscape: 1024 × 768 (hits the 1024 breakpoint exactly — same issue)

The off-by-one boundary issue (last bullets) means iPad portrait may render with the desktop layout depending on user-agent zoom — confirm with a real device.

---

## Appendix A — file:line index

```
header.php:5            viewport meta (OK)
header.php:25           bespoke .mobile-menu-toggle button (conflict with Mega Menu Pro)
header.php:96-112       hamburger JS (broken icon class contract)
footer.php:10-21        newsletter bar with twin min-width:300px
front-page.php:344      hero <section> inline padding 95/140
front-page.php:348      hero h1 inline font-size
front-page.php:908      category-card max-width:160px
front-page.php:1173     chat-input font-size:13px
page-dashboard.php:188  mobile breakpoint
page-dashboard.php:260  invalid @media in style="" attribute
page-ask-ai.php:44      .askai-hero h1 56px (no mobile override)
page-ask-ai.php:227     chat input font-size:15px (iOS zoom)
inc/tool-page-shell.php:133  600px breakpoint for hero/iframe
assets/css/main.css:1640-1792  primary responsive block
assets/css/main.css:2125-2371  override soup (4 dup mobile h1 rules)
```
