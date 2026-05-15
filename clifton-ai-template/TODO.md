# TODO — first prod deploy of CliftonAI Hub

Pending items for the first real CliftonAI deploy. Roughly in order of
how blocking they are.

---

## 1. Wire up the Ask AI API key

The Ask AI feature is gated on a customizer-stored API key. Until set,
`/ask-ai/` will render but the chat will fail.

- WP Admin → Appearance → Customize → **Ask AI Configuration**.
- Paste an OpenRouter (or compatible) API key.
- Save & publish.
- Smoke-test by sending a message at `/ask-ai/`.

Do **not** paste the key into a PHP file. See
[LESSONS-LEARNED.md §1](LESSONS-LEARNED.md#1-never-commit-an-api-key).

---

## 2. Replace placeholder URLs and contact info

The rebrand transformer substituted any old domain (`slahealth.co.uk`,
`vancemedical.co.uk`, `gastrohealthhub.com`) for `cliftonai.com`. Confirm
or change every place this appears via the customizer rather than in code.

- WP Admin → Settings → General → `Site Address` and `WordPress Address`
  (should be `https://your-real-domain`, not `http://`).
- WP Admin → Appearance → Customize → check social links, footer
  copyright, contact email, any `info@cliftonai.com` placeholders.

---

## 3. Register `clifton_evidence_*` customizer controls

`page-turn-insights-into-action.php` reads ~20 `clifton_evidence_*`
theme mods (hero copy, pillar titles, CTA labels, palette overrides).
The reads happen via `clifton_get_theme_mod( ..., $default )` so the
page works with code-side defaults, but **the controls are not yet
registered** in `customizer-pages.php` — admins can't edit the copy.

Action: open `customizer-pages.php`, find the block where Ask AI / About
panels register their settings, and add a `Turn Insights into Action`
panel + sections + controls mirroring the keys read in
`page-turn-insights-into-action.php`.

---

## 4. Create WP Pages for every template slug

WP only picks up `page-{slug}.php` if a Page with that slug exists. Create
empty Pages (no content needed — templates render copy from customizer
defaults) with these slugs:

- `home`           → set as homepage in Settings → Reading
- `about`
- `our-story`
- `clients`
- `enterprise-partners`
- `ask-ai`
- `ai-maturity-quiz`
- `ai-readiness`
- `dashboard`
- `register`
- `my-notes`
- `discovery-results`
- `pathway-results`
- `turn-insights-into-action`
- `contact-us`

For each, Page Attributes → Template = `Default Template` (the slug match
takes precedence).

---

## 5. Replace the dashboard `high-score` placeholder

`page-dashboard.php` case `'high-score'` was the embed slot for a
healthcare-specific Omega-3 promo game in the upstream theme. It has
been replaced with a placeholder block.

Decide: embed an AI-niche interactive (a model-playground iframe, a
demo product walkthrough, a leaderboard for some internal metric), or
hide the tab entirely. If hiding: remove the tab from the dashboard's
side-nav and delete the `case 'high-score':` branch.

---

## 6. Re-export the React tool bundles after source-side rebrand

The three tool widgets in `assets/tools/{ai-widget, ai-readiness,
roi-calculator}/` are minified Vite outputs whose brand strings were
patched in-place. If you rebuild from source without applying the
brand-substitution map to the source first, the brand reverts.

Decision tree:

- **Don't intend to rebuild?** Nothing to do.
- **Will rebuild from existing React source?** Apply the substitution
  list from [BUILD-NOTES.md §2](BUILD-NOTES.md#2-build-pipeline) to the
  source first, then `npm run build`, then copy `dist/*` into
  `assets/tools/<slug>/`.
- **Replacing the tools entirely?** Write new React apps, output to the
  same `assets/tools/<slug>/` paths, post `CLIFTONAI_SAVE_<NAME>_RESULT`
  messages, and the dashboard listeners keep working.

See [LESSONS-LEARNED.md §6](LESSONS-LEARNED.md#6-minified-bundles-revert-rebrands).

---

## 7. Niche-relabel review for inline copy

The transformer rewrote obvious labels (Patients → Clients, Healthcare
Quiz → AI Maturity Quiz, etc.) but some inline copy still reads like
the source healthcare brand. A reasonable pass:

- `page-about.php` — review the heritage/mission paragraphs end-to-end.
- `page-our-story.php` — same.
- `page-clients.php` — confirm the hero, value-prop blocks, FAQ refer to
  AI consulting clients rather than patients.
- `page-enterprise-partners.php` — confirm CTAs and the partner
  collaboration block read like a B2B AI agency pitch.
- `page-turn-insights-into-action.php` — review the four pillar copy.
- `inc/clinical-info-modal.php` — this used to be a clinical disclaimer.
  Decide whether it stays as a generic "About this content" modal or is
  removed.
- `inc/quiz-modal.php` — review quiz copy for the AI Maturity Quiz.

---

## 8. Strip the legacy `_sla_*` fallback in `clifton_get_theme_mod()`

`functions.php` line ~37-44 reads a `legacy_prefix = 'sla_'`
fallback to support the original upstream live site. For a fresh
template that branch is dead code. Delete it on the first cleanup
pass. (Mentioned in [BUILD-NOTES.md §7](BUILD-NOTES.md#7-customizer-settings).)

---

## 9. Decide the fate of `clifton-debug.php` and the mockup HTMLs

The theme ships:

- `clifton-debug.php` — was `vance-debug.php`, a debug surface that
  prints internal state. Should be gated behind a capability check
  (`current_user_can( 'manage_options' )`) before going live, or
  removed.
- `homepage-mockup.html`, `dashboard-mockup.html`,
  `dashboard-practitioner-mockup.html`, `mosaic_preview.html`,
  `unified-discovery-suite-prototype.html` — static design mockups from
  the upstream theme. Safe to keep (they aren't routed by WP) but
  delete them on cleanup if you don't want them snooped via direct
  URL.

---

## 10. Set up CI / deploy

The upstream repo deployed via SSH + tar. For CliftonAI, set up:

- A `main` (or `production`) branch.
- A CI step that runs `php -l` over every `.php` file (catches syntax
  errors before they hit the server).
- An automated deploy (rsync, SFTP, GitHub Action, etc.) — the
  template is small enough that any path works.
- A staging site for smoke tests before promoting to prod.
