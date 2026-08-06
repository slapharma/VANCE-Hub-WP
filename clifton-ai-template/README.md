# CliftonAI Hub — WordPress Template

A fully-rebranded clone of the Vance / SLA HealthHub WordPress theme, repurposed as a
starter template for **CliftonAI** — an AI consulting and app-solutions agency.
The original theme was a knowledge hub for patients and practitioners; this clone
preserves the dashboard, AI-chat, calculator, and discovery-suite scaffolding and
relabels it for an AI-agency content surface (Clients, Enterprise Partners, AI
Readiness Assessment, ROI Calculator, AI Maturity Quiz, Ask AI).

**Theme folder:** `wp-content/themes/cliftonai-hub/`
**Text Domain:** `cliftonai-hub`
**Status:** Drop-in WP theme. No live deploy. No DB. No user data.

> Read [BUILD-NOTES.md](BUILD-NOTES.md) before editing — it documents the
> cross-file contracts (AJAX action names, nonces, REST routes, postMessage
> types) that must move together.
>
> Read [LESSONS-LEARNED.md](LESSONS-LEARNED.md) before doing any global
> search-replace — the original rebrand left landmines that this template
> inherits.

## What's inside

```
clifton-ai-template/
├── README.md                  ← you are here
├── BUILD-NOTES.md             ← how this template was constructed; cross-file contracts
├── LESSONS-LEARNED.md         ← traps from the prior rebrand, distilled
├── CLAUDE.md                  ← agent instructions for maintaining the template
├── TODO.md                    ← what's left for the first real deploy
├── docs/                      ← inherited design/implementation notes (rebranded)
│   ├── ASK_AI_REDESIGN.md
│   ├── DISCOVERY_SUITE_IMPLEMENTATION.md
│   ├── IMPLEMENTATION_SUMMARY.md
│   └── UI_UPDATES_SUMMARY.md
└── wp-content/
    └── themes/
        └── cliftonai-hub/     ← the WordPress theme (drop into wp-content/themes/)
```

## Quick start

1. Copy `wp-content/themes/cliftonai-hub/` into your WordPress install's
   `wp-content/themes/` directory.
2. WP Admin → Appearance → Themes → activate **CliftonAI Hub**.
3. In WP Admin create the following Pages (empty content; templates render copy):
   `home`, `about`, `our-story`, `clients`, `enterprise-partners`, `ask-ai`,
   `ai-maturity-quiz`, `ai-readiness`, `dashboard`, `register`, `my-notes`,
   `discovery-results`, `pathway-results`, `turn-insights-into-action`,
   `contact-us`.
4. Set the homepage to a static page → `home`.
5. Appearance → Customize → wire up the customizer settings (logo, URLs,
   colours, hero copy). See [TODO.md](TODO.md) for the per-page checklist.
6. To enable the Ask AI feature, paste an OpenRouter (or compatible) API key
   into Appearance → Customize → Ask AI Configuration. **Do not commit the
   key.** See [LESSONS-LEARNED.md §1](LESSONS-LEARNED.md#1-never-commit-an-api-key).

## Brand & niche relabelling applied

Everywhere `SLA Health` / `Vance Medical` referred to a healthcare brand,
this template now says **CliftonAI**. Page slugs and dashboard categories
have been moved from a healthcare vocabulary to an AI-consultancy one:

| Healthcare original          | CliftonAI relabel               | Slug                      |
| ---------------------------- | ------------------------------- | ------------------------- |
| Patients                     | Clients                         | `/clients`                |
| Healthcare Professionals     | Enterprise Partners             | `/enterprise-partners`    |
| Healthcare Quiz              | AI Maturity Quiz                | `/ai-maturity-quiz`       |
| Blood Test                   | AI Readiness Assessment         | `/ai-readiness`           |
| Malnutrition Calculator      | ROI Calculator                  | (embedded tool)           |
| Turn Evidence into Action    | Turn Insights into Action       | `/turn-insights-into-action` |
| Our Heritage                 | Our Story                       | `/our-story`              |
| Ask AI                       | Ask AI                          | `/ask-ai` (unchanged — already on-brand) |

Healthcare-only assets (the `battleship-epa` Omega-3 promo game) have been
removed. The dashboard `high-score` tab now renders a placeholder slot for
your own gamification widget.

## License & attribution

Inherits the original theme license (`GPL v2 or later`, see
`wp-content/themes/cliftonai-hub/style.css`). Branding strings, copy, and
niche relabelling in this template are intended for the CliftonAI project
only — replace before redistributing.
