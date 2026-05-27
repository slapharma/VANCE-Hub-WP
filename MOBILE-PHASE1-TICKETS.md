# MOBILE-PHASE1-TICKETS.md — Phase 1 status

Tracking sheet for the Phase 1 "Foundations" pass in [MOBILE-PLAN.md](MOBILE-PLAN.md),
cross-referenced to the findings in [MOBILE-AUDIT.md](MOBILE-AUDIT.md).

**Architecture (confirmed):** single responsive codebase. All mobile rules live in a
separate `assets/css/mobile-base.css`, enqueued *after* `main.css` and gated behind
`@media (max-width: 767.98px)` / `≤480px` queries so desktop never applies them. No
parallel "mobile site". See `functions.php:122-126`.

Status legend: ✅ done · 🔧 fixed this pass · ⏳ outstanding · 🔬 needs live test

| ID | Item | Audit ref | Status | Evidence |
|----|------|-----------|--------|----------|
| 1.1a | `mobile-base.css` created | §1.5, §3.2 | ✅ | file present, 272+ lines |
| 1.1b | Enqueued AFTER `main.css` | — | ✅ | `functions.php:122-126`, dep `vance-main-style`, ver `2.3.0-vance-mobile-phase1` |
| 1.1c | `html{-webkit-text-size-adjust}` + `body{overflow-x:hidden}` | §1.5, §3.2 | ✅ | mobile-base.css §1 |
| 1.1d | Global `input/select/textarea {font-size:16px}` (iOS zoom) | §5.1 | ✅ | mobile-base.css §2 |
| 1.1e | Fixed-header `body{padding-top:70px}` ≤768px | §2.3 | ✅ | mobile-base.css §3 |
| 1.1f | Tap targets ≥44px | §3.6 | ✅ | mobile-base.css §4 |
| 1.1g | `100dvh` drawer height | §2.5 | ✅ | mobile-base.css §6 |
| 1.1h | Safe-area-inset padding | §1.2 | ✅ | mobile-base.css §9 |
| 1.1i | `prefers-reduced-motion` | §3.7 | ✅ | mobile-base.css §10 |
| 1.1j | `@media (hover:none)` strips lift transforms | §3.7 | ✅ | mobile-base.css §11 |
| 1.2a | Keep Mega Menu Pro; hide bespoke `.mobile-menu-toggle` | §2.1 (P0) | ✅ | mobile-base.css §5 (`body.mega-menu-primary-menu`) |
| 1.2b | Fix hamburger icon-class contract (`.is-open`, not `.menu-icon`) | §2.2 (P0) | ✅ | `header.php:99-158` rewritten JS + mobile-base.css §5 |
| 1.2c | Reset scroll-lock on resize to desktop | §2.6 | ✅ | `header.php` `matchMedia` handler |
| 1.3a | **`page-dashboard.php:260` invalid `@media` in `style=""`** | §4.2 (P0-D) | 🔧 | replaced with `.dash-user-meta` class; rules in mobile-base.css §12 |
| 1.3b | Strip front-page hero inline `padding:95px 0 140px` | §4.1 (P0-E) | ✅ | literal no longer present in `front-page.php`; defensive `[style*=]` override in mobile-base.css §7 |
| 1.3c | `footer.php` `min-width:300px` overflow | §4.5 (P0-F) | ✅ | mobile-base.css §7 (`min-width:min(300px,100%)` + stack) |
| 1.4 | `viewport-fit=cover`, `theme-color`, apple metas | §1.2, §1.3 | ✅ | `header.php:5-9` |
| 1.5 | Tablet boundary `768px`→`767.98px` across `main.css` | §3.9, §10 | ⏳ | mobile-base.css uses 767.98 in its own rules, but legacy `@media (max-width:768px)` blocks inside `main.css` and dashboard inline `<style>` (lines 188, 328, 779, 832) are still exact-768. See note below. |

## Outstanding Phase 1 item — 1.5 tablet boundary

The boundary-precision swap was **not** applied to `main.css`'s own breakpoints. A pure
value swap (`max-width: 768px` → `max-width: 767.98px`) is low-risk, but `main.css`
already contains four duplicate mobile `.hero h1` rules (audit §3.1) and several
overlapping `@media` blocks ("override soup", audit §3.1). Recommendation: pair the
boundary swap with the duplicate-rule consolidation (audit priority #12) in a single
focused edit rather than a blind global find-replace, so the two messy concerns are
resolved together. Deferred to a Phase 1.5 follow-up commit, not a blocker for deploy.

## Exit criteria — require live device/Lighthouse testing (🔬)

- [ ] Lighthouse Mobile Accessibility ≥ 90 on `/`, `/dashboard/`, `/ask-ai/`, `/turn-evidence-into-action/`
- [ ] No horizontal scroll on iPhone SE (375px) / Galaxy A52 (412px)
- [ ] Tapping any input on iPhone Safari does not zoom
- [ ] Only one hamburger icon visible on phones (Mega Menu Pro's)

These cannot be executed from the build environment; run on a real device or
BrowserStack after deploy, then tick off.

## Deploy

Deploy is via GitHub Actions (`.github/workflows/deploy.yml`) on push to `main`
touching `wp-content/themes/sla-health-hub/**`. It writes a server-side
`sla-health-hub-pre-deploy-*.tar.gz` backup before extracting. Post-deploy manual
steps: purge Hostinger + LiteSpeed cache; CSS version already bumped to
`2.3.0-vance-mobile-phase1` in `functions.php`.
