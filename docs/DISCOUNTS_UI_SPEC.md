# IBD Discounts & Freebies — UI spec

Design-system pass for `docs/DISCOUNTS_TOOL_PLAN.md` §10 step 1, before any markup
is written. No new tokens, no new component family: everything below is the
existing `vance-rh-*` (Recipes hub) and `vance-save-btn` (article bookmark)
language, extended with a `vance-discount-*` namespace. Where a rule below has no
citation it is new; everything else names the file it was lifted from so drift is
checkable later.

---

## 1. Tokens reused (none invented)

| Token | Value | Source |
|---|---|---|
| `--radius-pill` | 999px | chips, tier badge, status pills |
| `--radius-control` | 10px, fallback `var(--radius-control, 10px)` | Save/Apply buttons |
| `--radius-field` | 16px | search input, region `<select>` |
| `--radius-surface` | 24px | directory card, featured card, dashboard panels, modal |
| `--primary-color` (`#008080`) | active chip, category label, primary button, focus ring base |
| Card border/shadow | `1px solid #e2e8f0`, `box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05)` | `.vance-rh-card` (`assets/css/recipe-hub.css:21`) |
| Touch target | `min-height: 44px` on every chip/pill/button | `.vance-rh-chip` (`recipe-hub.css:16`), UX-guideline `touch-target-size` |
| Body copy | `#0f172a` heading, `#475569` secondary — never lighter | `.vance-rh-card-name`, WCAG contrast floor (4.5:1) |

Accent additions, both already load-bearing elsewhere on the site so no new palette entry:

| Color | Use | Why this one |
|---|---|---|
| `#10B981` (green) | tier-1 badge, "applied"/"received" status pill, is-saved state | Already the saved-state green on `.vance-save-btn.is-saved` (`single.php:117`) — reuse, don't invent a second green. |
| `#F59E0B` (amber) | tier-2 badge, "interested" status pill | New — needed because tier/status needs a 3rd hue and amber reads as "in progress" without implying success/failure (WCAG "color is not the only indicator" still applies: badge always carries text, never color alone). |
| `#94A3B8` (slate) | tier-3 badge, "declined" status pill | Neutral, already in the slate family used for `#475569`/`#e2e8f0`. |

---

## 2. Directory card (`.vance-discount-card`)

Structurally `.vance-rh-card` (border, radius-surface, shadow, `#fff` background)
but **not** an image-led card — no `.vance-rh-card-img` — because a scheme has a
provider, not a photo. Content-led layout instead:

```
.vance-discount-card
  .vance-discount-card__top        flex row, space-between
    .vance-discount-tier-badge     pill, 11px/700/uppercase, icon + "Tier 1" text
    .vance-discount-card-region    11px, slate, e.g. "England only"
  .vance-discount-card__provider   12px, uppercase, letter-spacing .3px, var(--primary-color)  (mirrors .vance-rh-card-cat)
  .vance-discount-card__title      16px/700, #0f172a, line-height 1.35             (mirrors .vance-rh-card-name)
  .vance-discount-card__value      15px/600, #0f172a — the value_summary, the single most important line on the card
  .vance-discount-card__cost       13px, #475569 — "£15/year" or "Free"
  .vance-discount-card__upcoming   only if upcoming_change is set: amber-tinted inline banner, 12px, icon + text, NOT a toast (must be readable with JS off)
  .vance-discount-card__actions    flex row, gap 8px, wraps on narrow cards
    .vance-discount-apply-btn      primary button, radius-control — label/behaviour per tier, see §5
    .vance-save-btn                REUSE VERBATIM (same class, same star icon, same AJAX contract, new action name — see plan §7 for the aria-pressed / icon-swap pattern already proven in single.php)
```

Card padding: `20px` (vs the recipe card's `16px` — this card carries more text
per card, one extra step of breathing room keeps it from feeling cramped).
Grid: `.vance-discount-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }` —
280px minimum, not the recipe hub's 240px, because the value/cost/tier lines wrap
badly under 280px.

**States**

| State | Rule |
|---|---|
| Hover | `transform: translateY(-2px)` + shadow deepens — same interaction as `.vance-rh-card` gets via its container hover if present; verify against actual computed hover before shipping, do not assume. |
| Focus (keyboard) | `:focus-visible` 3px outline in `--primary-color` at 40% opacity on the card's focusable children (title link, buttons) — never on the card `div` itself, since the card is not a single control. |
| Saved | Save button flips to `.is-saved` (green `#10B981`, filled star) — identical mechanism to the article Save button, just a different `post_id`/`action`. |
| Ineligible-but-visible | No greying-out. The plan is explicit (§6): everyone sees every scheme, logged out or not-a-match. A "not in your nation" or "you may not qualify" note is text, appended under `__cost`, 12px slate — never a disabled/faded card. Disabled-looking cards read as broken, and hiding eligibility contradicts §6's "likely, not eligible" language. |

---

## 3. Filter bar (`.vance-discount-controls`)

Direct copy of `.vance-rh-controls` structure (`recipe-hub-app.php:52-60`):
category chips as `<a href>` links with `?cat=` query args (no-JS-safe
server-side filter, exactly as recipe hub does it), `.is-active` state via
`var(--primary-color)` fill, **plus** one addition the recipe hub doesn't need —
a region `<select>`, because five of nine categories in `docs/DISCOUNTS_TOOL_PLAN.md`
§6 are nation-scoped and a scheme irrelevant to a Scottish member's region is
worth filtering out, not just noting.

```
.vance-discount-controls        flex row, wraps, gap 12px, align-items center, justify-content space-between
  .vance-discount-chips          IDENTICAL markup/CSS to .vance-rh-chips / .vance-rh-chip — nine categories + "All"
  .vance-discount-region-select  <select>, radius-field, same visual weight as .vance-rh-search
  .vance-discount-search         <input type="search">, IDENTICAL to .vance-rh-search
```

Category chip order (matches taxonomy order in plan §5): All, Toilet Access,
Days Out, Travel, Access Card, Benefits, NHS, Tax, Work, Household.

Client-side search/region filtering (JS) operates on `data-*` attributes on
`.vance-discount-card`, same pattern as `data-recipe-category` /
`data-recipe-name` (`recipe-hub-app.php:67`): `data-cat`, `data-region`,
`data-search` (lowercased title+provider). `?cat=` stays server-rendered for
no-JS; region and text search are JS-only enhancements (acceptable per plan §9's
"26 cards server-rendered with JS disabled" bar — category filtering alone must
survive that check, region/search need not).

**Empty state** (zero results after filter+search): centred block inside the
grid area, not a page-level message — `.vance-discount-empty`: an icon (SVG, not
emoji, per style guide), "No schemes match those filters", a single "Clear
filters" link that resets `?cat=` and JS filters together. Reserve the grid's
min-height so this doesn't cause a layout jump when a filter is cleared.

---

## 4. Tier badge (`.vance-discount-tier-badge`)

Pill, `--radius-pill`, `11px/700/uppercase`, icon + text — never color alone
(WCAG `color-only` rule). Icon is a small SVG glyph, not emoji, per the site's
existing SVG-icon convention (menu chevrons are drawn in CSS, not emoji — see
CLAUDE.md "Icons: Dashicons" section for why this site is careful here).

| Tier | Label | Color | Icon |
|---|---|---|---|
| 1 | "Apply on the hub" | `#10B981` on `#ECFDF5` | inline-check |
| 2 | "Opens provider site" | `#F59E0B` on `#FFFBEB` | external-link arrow |
| 3 | "Phone / post / in person" | `#94A3B8` on `#F1F5F9` | phone/document glyph |

Same three colors drive the dashboard's application-status pills (§6), so a
member only ever learns one badge-color vocabulary, not two.

---

## 5. Apply button, by tier

One class, `.vance-discount-apply-btn`, visually identical to `.vance-save-btn`
(`var(--primary-color)`, `--radius-control`, `12px 20px`, `700` weight) so the
two buttons on a card read as one family — but label and `data-*` payload vary,
resolved server-side from `_vance_discount_tier` (plan §4):

| Tier | Label | Click behaviour |
|---|---|---|
| 1 | "Apply on the hub" | Opens `VanceToolModal`-style panel (see §7) with the origin strip. |
| 2 | "Apply (opens {provider})" — provider name interpolated, not a bare "opens provider" | `window.open()` named popup; button shows a brief "Opening…" swap (loading-state UX rule) before the popup fires, guards double-click. |
| 3 | Verb matches the channel: "Call {number}" (tel: link on mobile, click-to-reveal on desktop), "Download form" (pdf), "Find your supplier" (postcode lookup for WaterSure), "Show at the gate" (opens the wallet-card view, not an external link) | Never a bare "Apply" — plan §7 already specifies per-channel copy; badge tier alone is not enough context for the member to know what happens next. |

Loading-button rule applies to all three: disable + spinner/opacity during the
async leg (popup pending confirmation, PDF generating), never a bare click with
no feedback.

---

## 6. Dashboard tab (`template-parts/dashboard/discounts.php`)

Three stacked panels, each `.vance-discount-surface` (`--radius-surface`,
`#fff`, same card border) — matches how other dashboard tabs box their content
(check `page-dashboard.php`'s existing tab markup before building; do not invent
a fourth panel style).

**a. Eligibility summary** — one line, one number, top of the tab:
"With a PIP letter and a water meter you likely qualify for **11** of these" —
verbatim pattern from plan §3. The bolded count is the only chart-like element
here; a number, not a chart, because one number is the whole insight (per
`dataviz`-adjacent principle: don't chart what a sentence says better).

**b. Access Folder checklist** — a list of toggle rows, not a form with a submit
button (autosave on toggle, AJAX, same nonce pattern as the bookmark button):

```
.vance-discount-folder-row       flex row, space-between, padding 12px 0, border-bottom 1px solid #e2e8f0 (last-child none)
  label (the evidence key's human name, e.g. "PIP")
  toggle switch — NOT a checkbox: this is a persistent profile setting the
    member flips rarely, and the site has no existing toggle-switch component
    to copy, so build one: track 40x22px, thumb 18px, --radius-pill, uses
    --primary-color when on. Meets the 44px touch target via the row's full-width
    hit area, not the visual switch alone (WCAG touch-target-size: the visual
    control can be smaller than 44px if the tappable area around it is not).
```

**c. Saved schemes list** — reuses `.vance-discount-card` at a denser variant
(`.vance-discount-card--compact`: no `__value`/`__cost` breathing room, just
title + status pill + the two action buttons), each row also carrying a status
`<select>` (interested/applied/received/declined) styled as the same pill
colors as the tier badge (§4) so status and tier share one visual language
without being confusable — status pills sit to the right of the title, tier
badges to the left, never both rendered as the identical pill in the identical
position.

Empty state (no saved schemes): same `.vance-discount-empty` pattern as §3,
copy: "You haven't saved any schemes yet — browse the directory to get started",
link to `/ibd-discounts/`.

---

## 7. Featured discount card (`.vance-discount-featured`)

Explicitly NOT `.vance-discount-card` at a different size — different context
(sidebar/homepage/promo, always singular, always with a "why this" framing),
different shape. Deliberately **not square** (CLAUDE.md radius rule: article
cards are the one square exception, and this is not an article card) —
`--radius-surface` throughout, horizontal layout on desktop
(icon/glyph block left, text right), stacked on mobile:

```
.vance-discount-featured
  .vance-discount-featured__eyebrow    12px uppercase var(--primary-color) — "Featured discount" or "Recommended for you" (member mode)
  .vance-discount-featured__title
  .vance-discount-featured__value
  .vance-discount-featured__verified   11px slate — "Checked {verified_on}" — ALWAYS rendered, per plan §8 ("a stale date is visible wherever the card is")
  .vance-discount-featured-cta         same button family as §5, single button only (no Save button in this slot — saving happens on the directory/single, not from a promo card)
```

---

## 8. Tier-1 in-hub apply modal

Extends `inc/tool-modal.php`'s existing glass-modal markup (`.vance-tool-modal`,
`.vance-tool-modal__panel`, `__bar`, `__body` — see that file's structure) rather
than inventing a second modal chrome. One addition, mandatory per plan §2's tier-1
caveat:

```
.vance-tool-modal__bar                  UNCHANGED
.vance-discount-origin-strip            NEW — sits between __bar and __body
  "You are on accesscard.online" (domain read from data-apply-url's host at open
  time, not hard-coded — Merlin's Nimbus app is a second tier-1 provider on a
  different domain, plan §2 tier 1 list)
  background: #F1F5F9, 13px, slate text, small external-link icon
  NOT the same color as the modal chrome — it must read as "this strip is about
  the framed page", not as part of the hub's own UI
.vance-tool-modal__body                 UNCHANGED (iframe + loading spinner)
```

`securitypolicyviolation` listener (plan §2 tier-1 caveat, `frame-src`) closes
this modal and fires the tier-2 popup path instead — this is the one place in
the whole tool where a runtime capability check controls which UI the member
ends up seeing, matching the site's existing rule (CLAUDE.md-adjacent lesson:
detect the restriction at runtime, never pre-emptively downgrade).

---

## 9. Accessibility checklist (from the `ux` domain search, applied to this tool specifically)

- Tier badges and status pills: icon + text always, color never alone (`color-only`).
- Access Folder toggle rows: native `<button role="switch" aria-checked>`, not a
  styled checkbox with no ARIA — screen readers must announce state changes.
- AJAX save/status/folder actions: on failure, `role="alert"` inline near the
  control that failed, not a toast the member may not see (`error-feedback`,
  `screen-reader` rules).
- Every chip/pill/button ≥44px touch target including the Access Folder switch's
  hit area (`touch-target-size`).
- `prefers-reduced-motion`: card hover-lift and the modal's open/close transition
  both need a reduced-motion fallback (instant, no transform) — the site already
  respects this pattern for `.va-sticky-save` (`transition:transform .25s ease`);
  audit whether that one already has a media-query guard before assuming it does.
- Keyboard: tab order through a card is title → Apply → Save, matching visual
  left-to-right/top-to-bottom order; the region `<select>` and search input are
  reachable before the chips wrap.

---

## 10. What this spec does not decide

Left to implementation, because they're data/behaviour questions, not visual
ones: the exact WaterSure supplier-lookup UI (plan §7 tier-3 note), the VAT
declaration PDF pre-fill mechanism, and the precise copy for each tier-3
apply_type variant beyond the pattern in §5. All three should still use the
component classes above — no new card, button, or badge component should be
invented for them.
