# BUILD-NOTES — CliftonAI Hub Template

How this template was constructed from the upstream Vance / SLA HealthHub
WordPress theme, what to customize first, and which contracts must move
together when you edit identifiers.

---

## 1. Provenance

Source: `wp-content/themes/sla-health-hub/` in the parent repo
[VANCE-Hub-WP](https://github.com/slapharma/VANCE-Hub-WP). That theme had
already been through two ownership changes:

1. **SLA Health Ltd** (gastrointestinal nutrition brand) — original build.
2. **Vance Medical Foods Ltd** — rebrand round 1: text + colour swap, AI
   chatbot rename, identifier prefix migration with a `_sla_*` legacy
   compatibility layer for live user data.
3. **CliftonAI** (this template) — fresh fork, healthcare nouns swapped for
   AI-consultancy nouns, no legacy DB to preserve, all identifiers cleanly
   prefixed `clifton_*` / `cliftonai-*`.

Because there is **no live data** for this template, several defensive
hacks the prior rebrand had to keep (the `implode('', array('s','l','a','_'))`
obfuscated prefix, the `_sla_*` → form-field translation layer, the
deferred theme-folder rename) have been resolved cleanly. See
[LESSONS-LEARNED.md](LESSONS-LEARNED.md) for the why.

---

## 2. Build pipeline

Two transformer scripts (kept in `/tmp/` during the build, not committed)
performed the rebrand:

1. **`cliftonai_rebrand.py`** — ordered (longest-first) string substitutions
   over every `.php`, `.css`, `.html`, `.js`, `.json`, `.svg`, `.txt`,
   `.md`, `.ps1` file under the theme directory. 2,769 substitutions across
   45 files in the first pass.
2. **`cliftonai_finalpass.py`** — cleanup of bare-word `\bSLA\b` / `\bVance\b`
   plus the SLA-Pharma legal-copy block (the prior rebrand had left it
   intentionally unchanged because SLA Pharma was the kept parent entity;
   for CliftonAI it is irrelevant, so it was rewritten as a generic
   AI-consultancy mission statement).

Order of substitutions matters — longer phrases substitute before shorter
ones so a partial match cannot clobber a longer one. See
[LESSONS-LEARNED.md §3](LESSONS-LEARNED.md#3-always-substitute-longest-phrase-first).

After string substitutions, `page-*.php` template files were renamed to
match new slugs (WordPress's slug-based template lookup). Tool subdirs
under `assets/tools/` were also renamed (`malnutrition-calculator` →
`roi-calculator`, `blood-test` → `ai-readiness`).

The healthcare-only `assets/games/battleship-epa/` Omega-3 promo game was
deleted entirely; its embed in `page-dashboard.php` (case `'high-score'`)
was replaced with a placeholder block.

---

## 3. Cross-file contracts — rename both sides in the same commit

If you change any of these identifiers, change every row in the same edit
or features will silently break.

| Contract | Server side (PHP) | Client side (HTML / JS) |
| --- | --- | --- |
| AJAX action names | `wp_ajax_clifton_*` hook registration in `functions.php` and `inc/dashboard-functions.php` | `action: 'clifton_*'` field in `fetch`/`jQuery.ajax` calls embedded in template files |
| Nonces | `check_ajax_referer( 'clifton_dashboard_nonce', 'nonce' )` | `wp_create_nonce( 'clifton_dashboard_nonce' )` printed inline in the templates |
| REST routes | `register_rest_route( 'cliftonai/v1', '/ai-chat', ... )` in `inc/dashboard-functions.php` | `fetch( '/wp-json/cliftonai/v1/ai-chat', ... )` in `page-ask-ai.php` |
| postMessage types | `if ( e.data.type === 'CLIFTONAI_SAVE_ROI_RESULT' )` listener in `page-dashboard.php` | `parent.postMessage({ type: 'CLIFTONAI_SAVE_ROI_RESULT', ... })` in `assets/tools/roi-calculator/index.html` (and inside the bundled JS, if you rebuild from source) |
| Theme mod keys | `clifton_get_theme_mod( 'clifton_*' )` reads | `$wp_customize->add_setting( 'clifton_*', ... )` writes (in `customizer-pages.php`) |
| Text domain | `'cliftonai-hub'` second arg to `esc_html__()` / `__()` | `Text Domain: cliftonai-hub` header in `style.css` AND the literal folder name `cliftonai-hub/` |

---

## 4. Theme structure

```
wp-content/themes/cliftonai-hub/
├── style.css                       Theme header + (mostly) empty stylesheet — actual CSS is in assets/css/
├── functions.php                   Bootstrap, enqueues, customizer registration, helpers, oEmbed handlers, custom post types
├── header.php / footer.php         Site-wide header/footer
├── footer-dashboard.php            Footer variant for the dashboard
├── front-page.php                  Homepage template
├── page.php / single.php           Generic page + post templates
├── archive.php / index.php         Archive + fallback
├── customizer-pages.php            Per-page customizer settings (~hundreds of theme mods)
├── ai-visibility.php               Admin meta box for "AI visibility" tagging
├── clifton-debug.php               Standalone debug page (gated, do NOT expose publicly)
│
├── page-about.php                  About page
├── page-our-story.php              Our Story (was: Our Heritage)
├── page-clients.php                Clients (was: Patients)
├── page-enterprise-partners.php    Enterprise Partners (was: Healthcare Professionals)
├── page-ai-maturity-quiz.php       AI Maturity Quiz (was: Healthcare Quiz)
├── page-ai-readiness.php           AI Readiness Assessment (was: Blood Test)
├── page-discovery-results.php      Discovery Suite results
├── page-pathway-results.php        Pathway Recommendations results
├── page-ask-ai.php                 Ask AI chat surface (REST + customizer-stored API key)
├── page-turn-insights-into-action.php  Insights → Action pillar page
├── page-dashboard.php              User dashboard (~26 tabs/cases switch)
├── page-my-notes.php               My Notes (saved-content surface)
├── page-register.php               Registration
├── page-contact-us.php             Contact form
│
├── inc/
│   ├── dashboard-functions.php     AJAX handlers, REST routes, profile/user-meta CRUD, AI chat backend
│   ├── oped-template-functions.php Op-Ed style article template helpers
│   ├── tool-embed.php              Generic shortcode/embed for the tools/ React widgets
│   ├── quiz-modal.php              Quiz modal markup
│   └── clinical-info-modal.php     "Disclaimer / about this content" modal
│
├── template-parts/
│   └── inner-category-nav.php      Sub-nav rendered inside category pages
│
└── assets/
    ├── css/
    │   ├── main.css                Site-wide CSS — palette here (CliftonAI teal #008080)
    │   └── oped-template.css       Op-Ed page CSS
    ├── img/                        Brand imagery + per-category hero images + SVG icons
    └── tools/
        ├── ai-widget/              Ask AI chat widget (Vite-built React)
        ├── ai-readiness/           AI Readiness Assessment (Vite-built React, was blood-test)
        └── roi-calculator/         ROI Calculator (Vite-built React, was malnutrition-calculator)
```

---

## 5. Bundled tools (`assets/tools/*`)

The three React widgets in `assets/tools/` are pre-built Vite bundles. The
template ships only the build artefacts (`index-*.js`, `index.html`); the
React source is **not** in this template. If you need to rebuild them you
must:

1. Recover the React source from the upstream repo (see `temp_calc/` and
   `temp_malnutrition_calc/` in the parent VANCE-Hub-WP environment) or
   start fresh.
2. Apply the same brand and identifier substitutions to the source
   (search-replace SLA/Vance/CliftonAI; rename postMessage types) before
   running `npm run build`.
3. Drop the new `dist/` content back into `assets/tools/<widget>/`.

If you skip step 2 the rebrand will revert silently the next time anyone
rebuilds. See [LESSONS-LEARNED.md §6](LESSONS-LEARNED.md#6-minified-bundles-revert-rebrands).

---

## 6. Niche-relabelling map (applied to this template)

| Healthcare original | CliftonAI relabel | Where it shows up |
| --- | --- | --- |
| `Patients` / `patients` | `Clients` / `clients` | page title, slug, dashboard tab label, hero copy, customizer defaults |
| `Healthcare Professionals` | `Enterprise Partners` | page title, slug, dashboard tab, customizer defaults, sign-up form options |
| `Healthcare Professional` | `Enterprise Partner` | role labels |
| `Healthcare Quiz` | `AI Maturity Quiz` | page title, slug, quiz modal copy, results template |
| `Blood Test` | `AI Readiness Assessment` | page title, slug, tool-bundle subdir, dashboard CTA |
| `Malnutrition Calculator` | `ROI Calculator` | page title, tool-bundle subdir, dashboard tab, postMessage type (`CLIFTONAI_SAVE_ROI_RESULT`) |
| `Malnutrition` | `ROI` | inline copy where it stood alone |
| `Turn Evidence into Action` | `Turn Insights into Action` | page title, slug, hero copy |
| `Our Heritage` | `Our Story` | page title, slug |
| `SLA Pharma has a long record of...` | `CliftonAI brings a decade of expertise...` | About / Our Story pillar copy |

The substitution `Heritage in Pharma` → `Heritage in AI` was applied
verbatim — review and rewrite if you want a non-cliché replacement.

---

## 7. Customizer settings

The theme exposes ~hundreds of theme mods in `customizer-pages.php` for
per-page hero copy, taglines, palette overrides, CTA buttons, etc. They
are all read via `clifton_get_theme_mod( 'clifton_*', $default )` — never
via `get_theme_mod()` directly. The wrapper used to provide a `_sla_*`
legacy fallback for the upstream rebrand; for this template that fallback
is harmless dead code (kept for now so the function signature is stable —
delete it if you want a smaller footprint).

The new `vance_evidence_*` (now `clifton_evidence_*`) controls used by
`page-turn-insights-into-action.php` are **not yet registered** in
`customizer-pages.php`. The page renders with code-default copy; admins
cannot edit it via the UI until the controls are added. See
[TODO.md §3](TODO.md#3-register-clifton_evidence_-customizer-controls).

---

## 8. Smoke-test checklist after deploy

1. Front page loads with teal primary (`#008080`) and the larger header logo
   (~225px desktop).
2. `/ask-ai/` chat sends and receives — confirms the REST route is reachable
   and your OpenRouter API key is wired in via the customizer.
3. `/turn-insights-into-action/` renders the four pillars.
4. Dashboard → Profile edit saves and persists — confirms the AJAX nonce
   and `_clifton_*` user-meta round-trip.
5. ROI Calculator completes and the result appears in the dashboard tab
   — confirms the `CLIFTONAI_SAVE_ROI_RESULT` postMessage contract.
6. Footer links resolve (replace placeholder `cliftonai.com` with your
   real domain in customizer settings).
7. WP Customizer opens and saves cleanly — no PHP warnings.

---

## 9. Things to do before first prod deploy

See [TODO.md](TODO.md). The non-negotiables are:

- Provide an Ask AI API key via the customizer (do **not** hardcode).
- Replace placeholder email `info@cliftonai.com` and the placeholder
  `cliftonai.com` URLs throughout customizer defaults.
- Register the missing `clifton_evidence_*` customizer controls.
- Replace the dashboard `high-score` placeholder with your own widget or
  hide the tab.
- Re-export the React tool bundles after rebranding source (see §5).
