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

```bash
cd tests && php legal-hero.test.php
```

```bash
cd tests && php gi-hero.test.php
```

```bash
cd tests && php category-hero.test.php
```

```bash
cd tests && php hero-mobile.test.php
```

```bash
cd tests && php recipe-labels.test.php
```

```bash
cd tests && php hub-layout.test.php
```

```bash
cd tests && php hub-article.test.php
```

```bash
cd tests && php hub-dashboard.test.php
```

```bash
cd tests && php hub-dashboard-tabs.test.php
```

```bash
cd tests && php hub-dashboard-features.test.php
```

```bash
cd tests && php hub-admin.test.php
```

```bash
cd tests && php hub-tool-result.test.php
```

```bash
cd tests && node hub-tool-result.test.js
```

```bash
cd tests && php hub-header.test.php
```

```bash
cd tests && php hub-preview.test.php
```

```bash
cd tests && php hub-community.test.php
```

```bash
cd tests && php hub-community-view.test.php
```

```bash
cd tests && php hub-community-sample.test.php
```

```bash
cd tests; php hub-flare-plan.test.php
```

Every suite exits non-zero on failure. The checks labelled `DEFECT` in
`hub-community.test.php` guard bugs that were found and fixed; they must pass
like every other check. As of 2026-09-01 the first six were
341 / 147 / 22 / 182 / 260 / 87 checks; `hero-mobile.test.php` added 80.
`recipe-labels.test.php` is new and adds 48. **`hero-render.test.php` and
`hero-customizer.test.php` currently have failing checks that predate this
change** — a red run of either of those two is not evidence this change broke
something; treat it as separate, pre-existing work. The other six
(`reveal.test.js`, `legal-hero.test.php`, `gi-hero.test.php`,
`category-hero.test.php`, `hero-mobile.test.php`, `recipe-labels.test.php`) are
green.

### Never write an image filename in a test — the WebP lesson

`gi-hero.test.php` sat red on four checks from 04882ed ("WebP for the condition
photography") until 2026-09-01. The suite hard-coded `.jpg`; the images became
`.webp` and nobody updated it. The four reds were the harmless half.

The other half is the one to remember. Three checks assert the **absence** of a
filename, and a string the renderer can no longer emit is absent no matter how
broken the code is — so they went green and unfailable, in silence:

| check | why it could not fail |
|---|---|
| `hub: does not borrow gi-health/ibd.jpg` | the lobby borrowing `ibd.webp` would have passed |
| `crc: theme asset is dropped` | the admin override could break entirely and pass |
| `hub: the picture file is really on disk` | the JPEGs stayed in the repo as the rollback, so it checked a file the renderer no longer used |

`mutate-gi.py` did not catch it either: its `lobby-walk.jpg` mutant had drifted
into `SKIP (pattern not found)` in the same commit — the most important mutant
in the file, silently not running, guarding checks that had themselves stopped
working.

So: **filenames come from the source of truth, never from a literal here.**
Conditions read `vance_gi_condition_cards()`; the lobby's is parsed back out of
the `$rel` literal in `gi-hero.php`. Both are guarded with a "did the lookup
return anything at all" check, because an empty string makes every `strpos()`
below it match. Mutant patterns stop at the dot (`.../lobby-walk.`) so they
survive the next format change. And `every condition photograph the registry
names is on disk` now covers all seven, which is the check that would have
caught a conversion that missed a file.

Four of them have a mutation runner beside them — `mutate-hero.py`,
`mutate-legal.py`, `mutate-gi.py`, `mutate-category.py`. Run the runner, not
just the suite: a green suite proves the checks execute, and only a red
mutant proves they can fail. Every line must read `went RED`.

## What each covers

| File | Covers |
|---|---|
| `hero-render.test.php` | `inc/page-hero-spotlight.php` — all twelve spotlight page heroes: the design toggle, copy inheritance, every utility band, both card variants, per-page card icons, `tel:` normalisation, and the CSS in `assets/css/main.css` that backs the classes the renderer emits |
| `hero-customizer.test.php` | The Customizer registration for those heroes — sections, panels, sanitizers, which section each toggle lands in, that two sections sharing a panel cannot share a title, and that the control list matches the renderer's field list exactly |
| `reveal.test.js` | The `.gi-reveal` scroll animation in `page-gi-health.php` / `page-gi-condition.php`, extracted from the templates themselves, under every condition that used to leave content invisible |
| `gi-hero.test.php` | `inc/gi-hero.php` — the Gastro Health Explained lobby and the seven condition heroes: the four-cell band and its never-itself guard, the lobby's seven chips on two fixed rows, purple's two jobs and the teal CTA override, photograph resolution and the focal-point whitelist, the opt-in review date, and **both templates included and run** so a commented-out call cannot pass. Section -1 lifts `vance_gi_conditions()` and `vance_gi_condition_cards()` out of `functions.php` and evaluates them, so the suite tests the real registry rather than a copy of it that could rot |
| `category-hero.test.php` | `inc/category-hero.php` — the category-archive heroes: the live facts band and the cells it drops rather than showing a zero, sub-category inheritance (photograph, card and family eyebrow from the parent) and the breadcrumb, term-name decoding, photograph resolution and its motif fallback, both title-override keys, and the three archive templates read as **source** — see the caveat below |
| `legal-hero.test.php` | `inc/legal-hero.php` — the five policy-document heroes: copy carried across from the dark heroes verbatim, the band of sibling documents, the no-photography constraint, slug resolution and its path fallback, the inline stylesheet, and the five templates **included and run** so a commented-out call cannot pass |
| `hero-mobile.test.php` | `inc/hero-mobile.php` — the phone-only "show on mobile" band/card switches and the band's short-label helper (`vance_hero_band_value()`), run against every hero family **twice**: once with `hero-mobile.php` loaded, pinning the "loaded" path, and once without, since the other five suites here never require it and so pin the "not loaded" fallback. Also covers the shared Policy Document Heroes Customizer section the switches added, `vance_subcat_overflow_cap()` lifted from `template-parts/subcategory-grouped-archive.php`, and the category promo block's default-off "Show on mobile" visibility class |
| `hub-layout.test.php` | `inc/hub-layout.php` and `front-page-hub.php` — the switchable Hub home page: layout resolution, and who may preview it through `?vhh_layout=` (the `edit_theme_options` gate); the `template_include` swap and nothing beyond it; the hooks it depends on; the date helpers, the member bar mode and the Founding Member deadline; member counts (cache, week query, failure path); tile filtering and the condition split; and the rendered template under the data rule, with nothing saved and with real data, as guest and member; section 15 covers the promo block in `front-page-hub.php` and the two Customizer switches (`vance_hub_show_promo`, `vance_hub_show_dashboard`) and the keys their readers use |
| `hub-article.test.php` | `inc/hub-article.php` and its one line in `single.php`: the opt-in VanceHubClub article layout. Inert with the setting off; the `?vhh_layout=` preview (administrators both ways, a visitor's value ignored, an array value safe); the category-to-tool map with ancestors; top-level paragraph counting and the insert rule (after the 2nd of 4 or more, else after the content; idempotent); the `the_content` guards (main loop, main post, feeds, admin, AJAX, excerpts, password and handout posts, REST); the prompt and the three sidebar cards, escaping included; the sidebar hook read from `single.php` with comments stripped; and that the AI-source, citation and schema readers still read `post_content`, not the filtered content. It evaluates the stub block of `hub-layout.test.php`, so both suites use one set of WordPress fakes |
| `recipe-labels.test.php` | `inc/recipe-frontend.php` — the recipe label model: `vance_recipe_label_model()`'s 8 core / 6 descriptor slugs and that the two layers never overlap; `vance_recipe_labels_for()` splitting a post's terms by layer in MODEL order regardless of assignment order, dropping terms outside the model, and tolerating `get_the_terms()` returning `false` or a `WP_Error`; and `vance_recipe_planner_data()`'s `tags` (core then descriptor slugs) versus `chips` (descriptor display labels only) split. Also checks `tools/recipe-labels.json` (90 recipes, unique ids, every label slug in the model, per-label counts against the handover's expected totals) and `tools/apply-recipe-labels.php`'s `$vance_labels` list against the model — parsed as text, never `require()`'d, since the script exits immediately without `WP_CLI` defined |
| `hub-dashboard.test.php` | `inc/hub-dashboard.php` and its three edits to `page-dashboard.php` — the opt-in VanceHubClub member dashboard home: the switch (saved value, the `?vhh_layout=` preview gated on `edit_theme_options`, odd query values); the pure helpers (`list`, `latest_ts`, `next_step` with skipped steps, `tool_status` plurals and dates, `unread_count` with string IDs and damaged rows, `tab_url`); `vance_hub_dashboard_data()` as reads only, with disabled tabs removing their tiles, the saved-articles query for published posts only, and the messages and new-in-library queries; the HTML, with every member-supplied string escaped, absent blocks, the unread badge, the one-column layout and the check mark; the enqueue conditions; and `page-dashboard.php` by static inspection. Evaluates the stub block of `hub-layout.test.php`, so both suites share one set of WordPress fakes |
| `hub-dashboard-tabs.test.php` | The second switch `vance_hub_dashboard_tabs` - the VanceHubClub look on every member-dashboard tab: `resolve()` and `active()` (needs the VanceHubClub dashboard too; the preview works); the `vhd-tabs` body class (dashboard page only, signed-in only); the stylesheet enqueue on a non-home tab and the cases where it must not load; `?vhh_layout=hub` kept in tab links for an administrator only; the member card (real data only, escaped, member number only when its own switch is on) and the Ask VANCE-Ai block; `page-dashboard.php` by static inspection (every new line guarded by the flag); a contract guard that compares form field names, element ids, AJAX actions and nonce names with the commit this work started from (with mutated copies that must fail); and that every CSS selector sits under `body.vhd-tabs`. The guard runs `git show`, so the repository must hold that commit |
| `hub-dashboard-features.test.php` | `inc/hub-dashboard-features.php` - the three optional member-dashboard blocks (member number, results chart, weekly check-in): the pure helpers (member code make and valid, check-in answers, the six-day rule, chart series, chart SVG, number display); member-code storage (stored, absent, damaged, taken, lost race, user 0); the check-in POST handler (switch, nonce and the expired redirect, validation, consent, early, the save row, delete); the check-in store (history read, append, the 50-row cap); the render with escaping, the delete control and the flash messages; the switch gating in `vance_hub_dashboard_features_data()`; and that `weekly-check-in` is NOT in `vance_known_tool_slugs()`, which keeps the generic tool handlers closed to check-in rows |
| `hub-flare-plan.test.php` | `inc/hub-flare-plan.php` - the flare plan card on the member dashboard: the pure helpers (keys, the 1000-character limit, cleaning and sanitising answers, a damaged stored plan); the switch, default OFF, and the urgent help text with no default (the card and the save both need it); the storage and write path (read back, backslashes, failures); the POST handler (gating, expired nonce, save and delete order, consent, stale wording, the lock, rollback of the agreement); the `$base` parameter of the shared check-in agreement helpers, with the check-in keys unchanged; that `flare_plan` is NOT in `vance_known_tool_slugs()`; the WordPress personal data exporter and eraser; and the render (escaping, line breaks, the print button, the consent box, flash messages) |
| `hub-admin.test.php` | `inc/hub-admin.php` - the read-only VanceHubClub admin screen under Vance Content Hub: each switch default checked against the real Customizer registrations of `hub-layout.php`, `hub-article.php`, `hub-dashboard-features.php` and `hub-community.php` (twelve switches) (and every switch mod a setting in section `vance_home_layout`); the status read (unsaved, saved true/false/'1'/''/0, a default-on switch saved off); the submenu (parent, capability, slug, callback, hook and priority 1000); the preview links (home always, article only when a published post exists, dashboard only when the page exists, `vhh_layout=hub` on each, a false permalink omits its link); the render (no output without `manage_options`, escaping of labels and URLs, On/Off against the status, the layout line against `vance_hub_saved_layout()`); and that section `vance_home_layout` has no `panel` key |
| `hub-tool-result.test.php` | `inc/hub-tool-result.php` and its wiring - the opt-in VanceHubClub result page for the Gastro Health Survey: the switch and the `?vhh_layout=` preview (who may preview, array and hostile query values); the survey-page check, hooks, assets and Customizer control (default false); the field spec built from the REAL `vance_quiz_steps()` cut out of `functions.php`; the sign-up address with its return path; the next-step cards (a missing page leaves its card out); the markup for guest and member (the data rule: no invented question, no member figure, no email form, no chart, no privacy line, no PDF, no Community card, the Saved-to-My-Hub card only for a member and only with a dashboard page); escaping; the data gatherer (real pages only, Google only when `GOOGLE_CLIENT_ID` and the shortcode exist, `redirect_to` put back); and the wiring (template call, load order, survey IDs and save action kept, radius tokens, 44px buttons, print rule) |
| `hub-tool-result.test.js` | The tile logic in `assets/js/hub-tool-result.js` (answers to "What you told us" tiles): no answer gives no tile, survey order, multi-answer, free text, display map, follow-up fields, leftover text ignored, markup stays text, and the save shape |
| `hub-preview.test.php` | `inc/hub-preview.php` and the five readers that call it (`hub-layout`, `hub-article`, `hub-dashboard`, `hub-header`, `hub-tool-result`), plus the admin box that `hub-admin.php` renders - VanceHubClub preview mode: the precedence in `vance_hub_preview_pick()`; the request resolver (admin `?vhh_layout=` first, then preview mode, then nothing; arrays and visitor values ignored); the share token (valid cookie on, wrong or raw-token cookie off, expired, token shape, rotated, sharing off, a bad `?vhh_preview` sets nothing) round-tripped through `$_COOKIE`; the redirect after landing and the exit link, same site only; that preview grants no capability; the admin flag and the share actions (nonce, `edit_theme_options`, back address); nocache and noindex only in preview; the LiteSpeed vary list; the admin box (token shown to an admin only, once), the admin bar item and the banner; and every `*_active()` reader on under preview and off without it. Section 11 holds regression checks for three fixed bugs (A: preview mode outlived its request in one process, B: the toggle's back address was lost or split at `&`, C: the banner printed the share token). Every check must pass; a red run is a real failure |
| `hub-header.test.php` | `inc/hub-header.php`, `template-parts/header-hub.php`, `header.php` and the member bar in `front-page-hub.php` - the site-header switch (pure decision, the request read with `edit_theme_options` gating `?vhh_layout=`, an array value ignored); the `vance-hub-primary` menu location, the assets (only when active, depend on `vance-main-style`), the `vhub-header` body class and the Customizer registration; the fallback navigation (real pages only, a missing page leaves its link out, never a Community link, the current item gets `aria-current="page"`); that an assigned menu wins and the classic `primary-menu` is never used; the markup (guest Sign in / Join free, member My Hub, VANCE-Ai carries `data-vance-askai-open`, search block kept, phone menu button with `aria-expanded` and `aria-controls`); that `header.php` prints the hub header and not the classic one when active and the reverse when not; escaping; and that the purple member bar renders exactly once (header + hub home, classic header + hub home, other pages, no data = no bar) |
| `hub-community.test.php` | The VanceHubClub Community hub, logic side: `inc/hub-community.php`, `inc/hub-community-admin.php` and the switch's header link in `inc/hub-header.php`. The switch and the administrator's `?vhh_layout=hub` preview; the page gate (404 for visitors while off, no-cache for members, template, robots, enqueue, body class); the page made on save; the types, capability and seeded spaces; the seven member actions refused for a logged-out visitor, a bad or missing nonce, and the switch off; ask, reply, me-too, helpful, vote and report with their daily limits, one-per-member rules and counts from stored rows; display names; text limits, tags and control characters; the product flag; moderation (nonce, capability, the fixed reject reasons, the queue); the poll editor; the privacy exporter and eraser (a question another member replied to is kept as an authorless hidden shell). Five checks labelled `DEFECT` guard fixed bugs and must pass |
| `hub-community-view.test.php` | `inc/hub-community-view.php`, the queue screen, the header nav and the wiring: switch off renders nothing; the visitor rule (only the first approved reply in the HTML, no text of the others, and one reply fetched per card, checked on the recorded queries); the member view; "Your posts"; the poll before and after a vote and for a visitor; escaping of seeded markup; the data rule for the rules and VANCE-Ai links; the feed filter and paging; the queue's escaping, flags, reasons, nonce fields and notices; that `assets/js/hub-community.js` names the same seven actions the server registers and writes server HTML only where the server escaped it. Does not run the JavaScript |
| `hub-community-sample.test.php` | `inc/hub-community-sample.php`, the staff-only sample data: the 20 questions (space split, reply counts, text limits, plain text, no product names); load (published, author-less, marked, named, spaced, me-too and helpful rows with the value 0, dates in the past and in order); a second load refused; the stored name shown only on a marked item with no author; the feed clause (staff and editors see samples, members and visitors do not); remove (only marked questions and their replies); the two admin-post handlers (`manage_options`, nonce, redirect notices), the queue box and the notice |
| `hub-community-stubs.php` | Not a suite. The WordPress stubs both community suites load: one in-memory store for posts, comments, users, terms and meta, a `$wpdb` stand-in for the four SQL shapes the code uses, and the exceptions that stand in for `wp_send_json_*`, `wp_die` and `wp_safe_redirect` |

## Not harnesses — the hero photographs

`gen-heroes.py` and `process-heroes.py` are one-shot generators, not tests. They
made the twelve images in `assets/img/heroes/` (OpenRouter,
`google/gemini-3-pro-image`, about $0.14 each — `education.jpg` cost $0.139228
on 2026-09-01) and cropped them to the 1400×876 box the renderer declares.
`process-heroes.py` takes whatever `gen-heroes.py` left in `tests/generated/`
rather than a list to keep in step with it.

**Pass the name you mean to both of them.** `gen-heroes.py` with no arguments
regenerates every prompt in the registry against a paid API; `process-heroes.py`
with no arguments publishes every frame sitting in `tests/generated/`, which is
gitignored scratch that accumulates — it holds 17 files today, including stale
variants of already-shipped heroes.

**Look at the frame before you ship it.** Generated images fail in specific
places: hands and fingers, limbs fusing into torsos, faces appearing where the
brief asked for none, and invented lettering wherever a page or screen is in
shot. `lobby-walk.webp` has all three of the first kind and is only usable
because the figures render ~200px tall. Crop and enlarge the hands and the face
before accepting a frame; `education.jpg` was checked that way.

They live here, not in `LOCAL/`, because the prompts ARE the spec: a replacement
photograph has to be composed the same way or the hero looks broken — subject
right of centre, left third bright and empty because that edge is dissolved,
focal point high because `object-position` is `46% 14%`. The docstring in
`gen-heroes.py` gives the reason for each line. `hero-render.test.php` §5f is the
part that can fail: it asserts every photograph the config names is on disk and
is really 1400×876, and that the count is **exactly ten** — ten, not twelve,
because the Knowledgebase and the 404 name none in code: they declare the motif
and are overridden by a theme mod on the live site. Education was a third of
those until it was given a photograph on 2026-09-01.

That count does **not** catch a page borrowing another page's photograph — the
total stays ten and every file still exists. §5g's per-page check is what
catches that, and `mutate-hero.py` has a mutant for it. Until 2026-08-31 Ask AI
wore Crohn's and the User Guide wore IBD, so it is not a hypothetical.

`gen-heroes.py` needs `OPENROUTER_API_KEY` in the environment and spends real
credit. It writes into `tests/generated/`, which is not committed.

⚠ **Which OpenRouter key matters.** There are two on this project and they are on
different accounts. The one that has usually been in the environment is a
personal dev key with a **$5 lifetime cap**, and it is exhausted — it fails with
a bare `HTTP 402` that reads like the account is empty when it is not. The one
with budget is the account behind the site's own `vance_askai_api_key` setting,
which is also what powers VANCE-Ai in production, so check that account's balance
before spending it on pictures. `GET https://openrouter.ai/api/v1/key` tells you
the cap and what is left on whichever key you are holding; `/api/v1/credits`
tells you the account total.

## Mutation runners — read this before trusting a green run

A check that has never been observed failing has not been tested, only run.
All four runners break the source on purpose, confirm the suite goes red,
then restore it:

```bash
cd tests && python mutate-hero.py
```

```bash
cd tests && python mutate-defaults.py
```

```bash
cd tests && python mutate-legal.py
```

```bash
cd tests && python mutate-gi.py
```

Every line must say `went RED`. Two failure modes to watch for:

- `*** STAYED GREEN ***` — the suite cannot detect that bug. Fix the test, not
  the mutant. This is how the toggle-default bug was found: the default was
  written in two places that could silently disagree.
- `SKIP (pattern not found)` — the mutant's search string has drifted from the
  source, so it is silently testing nothing. Repair the pattern.

All four restore the file in a `finally:` block, including on exception.
If one is interrupted, check `git diff` before doing anything else.

`mutate-legal.py` also mutates two of the TEMPLATES, not just the renderer —
that is what proved §8 of its suite had to include the templates rather than
grep them. See the note at the end of this file. `mutate-gi.py` does the same,
and additionally mutates `functions.php`, because `gi-hero.test.php` reads the
condition registry out of it.

### Three things mutate-gi.py caught on its first run

Worth reading before writing a check of your own, because all three are checks
that looked fine and could not fail:

- **A selector search without its brace.** `strpos($css, '…__eyebrow')` still
  matched after the rule was renamed to `…__eyebrow-DISABLED`. Same class of
  bug as grepping a template for a call that has been commented out. Search for
  `'…__eyebrow {'`.
- **A colour search that was not scoped.** The chips and the eyebrow use the
  same ink, so a bare search for it passed with the chip's own `color`
  declaration deleted. Scope the match to the block you mean.
- **An assertion that could not fail by construction.** "the card is
  byte-identical on all eight pages" is true because one function renders it
  for all eight — no mutation can make them differ. That is the right design
  and the wrong test. The copy itself is what can drift, so assert that.

And one mutant that silently tested nothing: `functions.php` is **CRLF**, so a
needle carrying a bare `
` can never match it. It reported `SKIP`. Keep
`functions.php` mutants to a single line.

## Adding a hero to the suite

`hero-render.test.php` is driven by `vance_page_hero_spotlight_pages()`, so a
new page added to that config picks up most coverage automatically — sections
0b, 7 and 8 all walk the list and need no edit.

One section still needs a line per page:

- **Section 0** — the pristine case: nothing saved but the toggle, asserting
  real copy still renders. This is non-negotiable; a `''` default renders empty
  on the live site while looking perfect in the Customizer preview, and only
  this section catches it. Section 0b then proves those same defaults appear
  verbatim in the file named by the config's `classic_template`, so the two
  cannot drift apart — but 0b can only check the defaults 0 has proved are
  real, so write both.

A page with **no classic hero** — the 404 is the only one — declares no
`legacy_tag` and no `classic_template`, so 0b skips it and section 0 is the
only thing standing between it and an empty headline. Write it there, and add
a `mutate-defaults.py` mutant that empties the literal.

A page that declares `'always' => true` registers **no toggle**, so
`hero-customizer.test.php` asserts the absence rather than the presence. Do
not "fix" that by giving it a `style_section`.

Two things a new page must also declare in the config, because neither is
derivable from the page key and getting either wrong fails silently in
WordPress rather than loudly here:

- `style_section` — the existing Customizer section the design toggle goes in.
- `classic_template` — the file holding the classic hero's own fallbacks.

## A note on template-level checks

`legal-hero.test.php` §8 **includes** each of the five policy templates against
a dozen tiny WordPress stubs, rather than grepping them for the renderer call.
That is not belt-and-braces: the grep version of the section passed a mutation
that commented the call out, because `// vance_render_legal_hero( 'terms' );`
still contains the string being searched for. Any future check that a template
"calls" something should run the template, not read it.

## The three category photographs

Made 2026-08-31 on the same pipeline as the other eleven (OpenRouter,
`google/gemini-3-pro-image`, about $0.14 each), cropped to 1400x876 and living
in `assets/img/heroes/categories/`. `process-heroes.py` routes anything named
`cat-*` there; the registry in `inc/category-hero.php` names them by slug.

`cat-healthcare-news` took two attempts. The first prompt asked for three
colleagues round a meeting table and got textbook corporate stock -- the one
thing `SHARED` explicitly rules out -- and it said "business", not "health". It
was rewritten to a single clinician in a hospital corridor with the documentary
constraints named rather than assumed. `gen-heroes.py` holds the second prompt
and the reasoning for the change.

**Alt text is written from the finished photograph, never from the prompt.**
Two of these three shipped alt describing a different picture entirely, because
the descriptions were written before the images existed: `gastro-living.jpg` is
a man lacing a walking boot and was announced to screen readers as "two people
talking over coffee at a bright kitchen table". Confidently describing the
wrong photograph is worse than describing none, and nothing on the page looks
different when it happens.

Section 8 now asserts that any registry entry with a file on disk carries real
alt text, and that the file is really 1400x876. Neither check can tell whether
the words match the picture. Look at the image.

The nine categories with no posts have no photograph and render the motif
instead, exactly as the Knowledgebase, the 404 and the five policy documents
do. A section with nothing in it does not need a photograph of somebody
enjoying it.

## process-heroes.py takes name arguments now, and you should use them

`./generated` accumulates: it is gitignored scratch holding every frame ever
produced, including whatever an interrupted or mistaken run left there. Running
`process-heroes.py` bare reprocesses all of it, which republishes all of it --
so a stray regeneration of an existing hero silently replaces a shipped
photograph, and the diff is a binary that announces nothing.

On 2026-08-31 an accidental argument-less `gen-heroes.py` put four fresh
variants of already-shipped heroes into `./generated`. A bare process run would
have swapped all four on the live site. Name what you mean:

```bash
python process-heroes.py cat-clinical-reviews cat-gastro-living cat-healthcare-news
```

## A second note on template-level checks

`category-hero.test.php` §9 does exactly what the note above says not to do: it
reads the three archive templates rather than running them. That is a known
weaker check, not an oversight. `archive.php` and its two siblings need a live
`WP_Query`, `get_header()` and a post loop, none of which the stub layer here
provides, so including them is not a small addition — it is a second harness.

What §9 does buy, and `mutate-category.py` proves: it strips comments before
searching, so `// vance_render_category_hero();` fails it, which is the exact
mutation that defeated the naive grep in `legal-hero.test.php`. What it cannot
prove is that the call is *reached* at runtime. Only loading a real category
page proves that, which is why the deploy checklist ends there.
