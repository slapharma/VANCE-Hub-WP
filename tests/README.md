# tests/

Harnesses for the theme's PHP renderers and inline JS. They run **outside
WordPress** against hand-written stubs, so there is no WP install, no database
and no build step — just PHP and Node on PATH.

These live at the repo root, **not** inside the theme, on purpose: CI deploys
with `git archive HEAD:wp-content/themes/vance-health-hub`, so anything here is
never shipped to the live server.

## Running them

```bash
cd tests && php hero-render.test.php
```

```bash
cd tests && php hero-customizer.test.php
```

```bash
cd tests && node reveal.test.js
```

All three exit non-zero on failure. As of 2026-08-28: 84 / 22 / 22 checks.

## What each covers

| File | Covers |
|---|---|
| `hero-render.test.php` | `inc/page-hero-spotlight.php` — the Contact/About spotlight heroes: the design toggle, copy inheritance, both utility bands, both card variants, `tel:` normalisation, and the CSS in `assets/css/main.css` that backs the classes the renderer emits |
| `hero-customizer.test.php` | The Customizer registration for those heroes — sections, panels, sanitizers, and that the control list matches the renderer's field list exactly |
| `reveal.test.js` | The `.gi-reveal` scroll animation in `page-gi-health.php` / `page-gi-condition.php`, extracted from the templates themselves, under every condition that used to leave content invisible |

## Mutation runners — read this before trusting a green run

A check that has never been observed failing has not been tested, only run.
Both runners break the source on purpose, confirm the suite goes red, then
restore it:

```bash
cd tests && python mutate-hero.py
```

```bash
cd tests && python mutate-defaults.py
```

Every line must say `went RED`. Two failure modes to watch for:

- `*** STAYED GREEN ***` — the suite cannot detect that bug. Fix the test, not
  the mutant. This is how the toggle-default bug was found: the default was
  written in two places that could silently disagree.
- `SKIP (pattern not found)` — the mutant's search string has drifted from the
  source, so it is silently testing nothing. Repair the pattern.

Both runners restore the file in a `finally:` block, including on exception.
If one is interrupted, check `git diff` before doing anything else.

## Adding a hero to the suite

`hero-render.test.php` is driven by `vance_page_hero_spotlight_config()`, so a
new page added to that config picks up most coverage automatically. Two
sections need a line each per page:

- **Section 0** — the pristine case: nothing saved but the toggle, asserting
  real copy still renders. This is non-negotiable; a `''` default renders empty
  on the live site while looking perfect in the Customizer preview, and only
  this section catches it.
- **Section 0b** — asserts each `legacy_*_default` appears verbatim in the
  classic template, so the two cannot drift apart.
