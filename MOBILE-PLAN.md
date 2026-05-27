# MOBILE-PLAN.md — Vance HealthHub WP

Mobile redesign plan covering responsive fixes + mobile-specific components. Companion to [MOBILE-AUDIT.md](MOBILE-AUDIT.md).

**Approach selected:** *Responsive + mobile-specific components* — keep the single template set, but add mobile-only structural pieces (bottom nav, sticky CTA, swipeable cards, condensed dashboard) where the desktop layout doesn't translate.

**Devices targeted:** iPhone Safari, Android Chrome, iPad portrait + landscape.

**Phasing:** five phases, each independently deployable. Phase 1 is the "stop the bleeding" hardening pass; phases 2-5 are progressively more ambitious.

---

## Phase 1 — Foundations (1–2 days, no visual redesign)

Goal: every existing page renders cleanly at 320–430px wide. No new components yet.

### 1.1 Global CSS hardening

Add a new file `assets/css/mobile-base.css` enqueued AFTER `main.css`, containing:

```css
/* iOS / Android base */
html { -webkit-text-size-adjust: 100%; }
body { overflow-x: hidden; }

/* Prevent iOS Safari zoom on focus — keep at 16px exactly. */
input, select, textarea, .chat-input, .form-input {
  font-size: 16px;
}

/* Fixed-header offset (matches the 70px mobile site-header) */
@media (max-width: 768px) {
  body:not(.dashboard-body):not(.no-header-offset) {
    padding-top: 70px;
  }
}

/* Tap targets */
@media (max-width: 768px) {
  .btn, button:not(.mega-toggle-animated), .pagination a {
    min-height: 44px;
    padding-block: 12px;
  }
}

/* Small phone breakpoint */
@media (max-width: 480px) {
  .container { padding: 0 16px; }
  h1 { font-size: 26px !important; }
  h2 { font-size: 22px !important; }
}

/* Use dynamic viewport units where supported, with fallback */
@supports (height: 100dvh) {
  @media (max-width: 768px) {
    .main-nav { height: calc(100dvh - 70px); }
  }
}

/* Safe-area inset for iPhones with notch */
@supports (padding: max(0px, env(safe-area-inset-bottom))) {
  .site-header { padding-top: env(safe-area-inset-top); }
  .vance-bottom-nav { padding-bottom: max(8px, env(safe-area-inset-bottom)); }
}
```

### 1.2 Resolve dual hamburger

**Decision needed:** keep Mega Menu Pro, OR keep theme's bespoke menu. Recommendation: keep Mega Menu Pro (it's actively used in the live site and handles accessibility + dropdowns better than the bespoke JS), delete the theme's `.mobile-menu-toggle` button and the JS block in `header.php:96-112`. This removes ~30 lines of code and resolves the conflict.

### 1.3 Page-specific P0 fixes

- `page-dashboard.php:260` — remove the invalid `@media` from inline style, move the responsive show/hide to a CSS class.
- `front-page.php:344` — strip the `padding: 95px 0 140px` from inline style. Move to `.hero.patient-hero` in `main.css`. Add a `@media (max-width: 768px)` reducing to `padding: 40px 0`.
- `footer.php:11, 16` — change `min-width: 300px` to `min-width: min(300px, 100%)`.

### 1.4 Add viewport-fit and theme-color

In `header.php`:

```html
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#0A1929" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#008080" media="(prefers-color-scheme: dark)">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
```

### 1.5 Tablet boundary fix

In `main.css`, change every `@media (max-width: 768px)` to `@media (max-width: 767.98px)` and every `@media (max-width: 1024px)` to `@media (max-width: 1023.98px)`. iPad at exact 768 / 1024 widths currently sits at the boundary.

### Exit criteria for Phase 1

- Lighthouse Mobile Accessibility ≥ 90 on `/`, `/dashboard/`, `/ask-ai/`, `/turn-evidence-into-action/`.
- No horizontal scroll on iPhone SE (375px) or Galaxy A52 (412px) on any public page.
- Tapping any input on iPhone Safari doesn't trigger zoom.
- Only one hamburger icon visible on phones.

---

## Phase 2 — Mobile-only components (3–5 days)

Add four mobile-only structural pieces. All gated by `@media (max-width: 768px)` so desktop is unchanged.

### 2.1 Mobile bottom navigation

Persistent 56px-tall bar fixed to the bottom of the viewport on phones for logged-in users. Five tabs:

```
[ 🏠 Home ] [ 🔍 Search ] [ 🤖 Ask AI ] [ 📊 Dashboard ] [ ⚙️ More ]
```

- New partial: `template-parts/mobile-bottom-nav.php`, included from `footer.php` when `wp_is_mobile()` returns true OR always — let CSS hide it on desktop.
- Hidden on the dashboard (which has its own sidebar).
- "More" opens a sheet with secondary nav (My Notes, AI Chats, Tools & Resources, Sign Out).
- Body gets `padding-bottom: 56px` on mobile to avoid the bottom nav covering content.
- Customizer: toggle to enable/disable, customise label text per tab, choose which 5 destinations from a multi-select.

### 2.2 Sticky CTA bar

On marketing pages (`page-healthcare-professionals.php`, `page-patients.php`, `page-education.php`, `page-turn-evidence-into-action.php`), a sticky CTA bar slides up from the bottom on mobile after the user scrolls past 30% of the page:

```
┌─────────────────────────────────┐
│  Ready to dive deeper?    [Sign up →] │
└─────────────────────────────────┘
```

- Per-page Customizer settings: enable, headline text, button text, button link, background colour.
- Dismissable with a small × — preference stored in `localStorage` (not user meta) so it doesn't survive a logout/login but doesn't annoy on every page load.
- Hidden when bottom nav is showing (logged-in users get bottom nav; logged-out users get sticky CTA).

### 2.3 Swipeable category cards on the homepage

`front-page.php:908` already has `.category-cards-grid { overflow-x: auto }` — promote this from accidental to deliberate:

- Add scroll-snap: `scroll-snap-type: x mandatory` on the container, `scroll-snap-align: start` on each card.
- Add bullet indicators below the row (small dots) updated via `IntersectionObserver`.
- Hide the desktop "5 cards in a row" layout below 768px; on mobile show a 1.5-card peek so users discover scrollability.
- Add `aria-label="Category carousel"` and arrow keys for accessibility.

### 2.4 Condensed dashboard cards

Dashboard cards (`page-dashboard.php`) on mobile should collapse to:

- Stacked single column (already happens via `auto-fit minmax(320px, 1fr)`).
- **NEW**: each card gets an accordion `<details>` wrapper on mobile only — title + key metric visible by default, full content opens on tap.
- Top of dashboard becomes a horizontally-scrolling row of "stat chips" instead of full-card stats:
  ```
  [ 📚 12 saved ] [ 📝 5 notes ] [ 🤖 3 chats ] [ 🎓 2 courses ]
  ```
  Each chip is a link to its respective tab. Replaces the 3-card stats block.
- Sidebar drawer gets a backdrop overlay (`.dash-sidebar-backdrop`) — tap to close.

---

## Phase 3 — Touch interactions and motion (2–3 days)

### 3.1 Touch-first hover replacements

Every `:hover { transform: translateY(-...) }` rule becomes `@media (hover: hover) { ... }` so it only fires on devices with a real pointer. On touch, the lift animation fires on `:active` (300ms transient).

### 3.2 Pull-to-refresh on dashboard

Lightweight: detect `touchstart` at `scrollTop === 0`, show a spinner if user pulls down > 60px, trigger a `location.reload()` (or a more surgical AJAX refresh of the visible cards).

### 3.3 Swipe gestures

- Dashboard sidebar drawer: swipe from left edge to open, swipe left on drawer to close (Hammer.js or a 30-line vanilla touch handler).
- Quiz modal (`inc/quiz-modal.php`): swipe between questions.

### 3.4 Loading states

Skeleton screens for AJAX-loaded sections of the dashboard (currently a blank flash before content appears).

---

## Phase 4 — Performance for mobile networks (2–3 days)

### 4.1 Image responsiveness

- All `<img>` in templates get explicit `width` and `height` attributes (prevents CLS).
- Add `loading="lazy"` and `decoding="async"` to every below-the-fold image.
- Generate WebP versions of hero images via WP-CLI media regen or a build step; serve via `<picture>` with `<source srcset>`.
- Hero images get `srcset` with 480w / 800w / 1200w / 1920w variants.

### 4.2 Tool bundle Tailwind compile

Currently each iframe loads `https://cdn.tailwindcss.com` (≈ 50KB compressed, plus the JIT runtime). Replace with a built `tailwind.css` per tool, ~5KB compressed. Run the `LOCAL/vance_*.py` transformers, then `npx tailwindcss -i ./src/index.css -o ./public/index.css --minify` during the build, then deploy.

### 4.3 Critical CSS for mobile

Extract the above-the-fold styles for `/` into a `<style>` block in `<head>` (≈ 10KB). Defer the rest of `main.css` via `media="print" onload="this.media='all'"` pattern. Saves one render-blocking round-trip on cold mobile loads.

### 4.4 Font loading

`Inter` and `Outfit` are loaded synchronously from Google Fonts (presumably). Switch to `font-display: swap` and consider self-hosting the two weights actually used.

---

## Phase 5 — Optional / future (1+ week, scope-dependent)

### 5.1 PWA shell

If long-term direction is app-like usage (especially for HCPs reviewing on-call):
- Add a `manifest.webmanifest`.
- Service worker caching `main.css`, key fonts, dashboard shell HTML.
- "Add to Home Screen" prompt after 2nd visit.
- Offline fallback page.

### 5.2 Mobile-only `page-dashboard-mobile.php`

If the responsive dashboard ends up feeling cramped after Phase 2, split it: detect `wp_is_mobile()` server-side and render an app-like dashboard with tab bar at bottom (no sidebar at all on mobile), large touch targets, and a bento-grid of feature tiles instead of dense cards.

### 5.3 Native share sheets

Replace the bookmark / save button with `navigator.share()` on supported browsers — feels native, falls back to current behaviour elsewhere.

### 5.4 Haptic feedback

`navigator.vibrate(10)` on key tap interactions (save, complete quiz, finish calculator). Subtle but premium feel on Android.

---

## Customizer additions (cross-phase)

All new mobile components must be Customizer-driven. Add a new top-level panel **"Mobile Experience"** with these sections:

- **Bottom Navigation** — enable, label + icon + link for tabs 1-5, background colour, active tint.
- **Sticky CTA Bar** — global toggle, per-page enable, headline, button text + link, colours.
- **Swipeable Cards** — toggle, indicator style (dots / numbers / none).
- **Mobile Dashboard** — accordion default state, stat chips order, sidebar backdrop colour.
- **Mobile Performance** — `loading="lazy"` enable, srcset enable, critical CSS injection.

---

## Testing matrix

For each phase, verify on:

| Device | Browser | Width | Notes |
|---|---|---|---|
| iPhone SE 3rd gen | Safari | 375 | Smallest current Apple device |
| iPhone 15 | Safari | 393 | Most common iPhone width |
| iPhone 15 Pro Max | Safari | 430 | Largest iPhone, notch tests |
| iPad portrait | Safari | 768 | Tablet boundary |
| iPad landscape | Safari | 1024 | Other tablet boundary |
| Pixel 7 | Chrome | 412 | Common Android |
| Galaxy A52 | Chrome | 412 | Common Android |
| Galaxy Fold | Chrome | 280 | Narrow-edge test |

Lighthouse mobile audit thresholds (per phase):
- Phase 1: Accessibility ≥ 90, Best Practices ≥ 90.
- Phase 2: Same, plus first interaction with bottom nav < 100ms.
- Phase 4: Performance ≥ 80 on simulated Slow 4G.

---

## Out of scope for this plan

- Theme folder rename (see [TODO-RENAME.md](TODO-RENAME.md)).
- Database `siteurl` / `home` HTTPS swap (see [REBRAND-HANDOVER.md](REBRAND-HANDOVER.md) §6.2).
- Tool bundle source rebuild — the bundles are responsive enough; only the Tailwind CDN swap is in scope here.
- Server-side detection / separate mobile templates — explicitly chose responsive + components instead.

---

## Risks and rollback

- **Phase 1 inline-style strip** is the highest risk because the inline styles often encode customizer values. Mitigation: convert inline `style="..."` into a CSS custom property emit (`style="--hero-padding-y: 95px;"`) and have the stylesheet honour the custom prop via `padding-block: var(--hero-padding-y, 100px)`. This preserves customisation while letting media queries override the default.
- **Phase 2 bottom nav** can't ship until the existing footer doesn't overlap. Mitigation: pad-bottom is added to body only when `body.has-mobile-nav` is present — the partial sets that class via JS on render.
- **Mega Menu Pro removal** (Phase 1.2 alternate path) is high-risk; the plugin may already have customer-set menus that don't exist in WP nav admin. Recommended: keep Mega Menu Pro, remove the theme's bespoke menu.
- Each phase ships behind a Customizer toggle so it can be A/B'd or rolled back via admin without redeploy.
