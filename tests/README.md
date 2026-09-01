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

All six exit non-zero on failure, and all six are green. As of 2026-09-01:
341 / 147 / 22 / 182 / 260 / 87 checks.

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
