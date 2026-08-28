# Handover — rolling the spotlight hero out to the remaining templates

**Date:** 2026-08-28
**Prereq reading:** [CLAUDE.md](CLAUDE.md) first, then this file. The load-bearing
constraints in CLAUDE.md (theme slug, `_sla_*` meta, `vance_get_theme_mod()`, the
radius token scale, the cross-file contracts) all still apply and are not repeated here.

---

## 1. What this task is

The homepage runs a light, action-led hero — the "spotlight" design. It has now been
carried across to **Contact Us**, **About Us** and the **three free-tool pages**. The
job for the next session is to carry it across to **every remaining page and post
type**.

Three heroes exist today:

| Design | Renderer | Used by |
|---|---|---|
| Spotlight (light, mint band) | [inc/hero-spotlight.php](wp-content/themes/vance-health-hub/inc/hero-spotlight.php) | homepage |
| Spotlight, page variant | [inc/page-hero-spotlight.php](wp-content/themes/vance-health-hub/inc/page-hero-spotlight.php) | eight pages: `/contact-us/`, `/about/`, the three free tools (`/gastro-health-survey/`, `/gastro-meal-planner/`, `/malnutrition-calculator/`), `/ask-ai/`, `/get-started-today/`, `/user-guide/` |
| Classic dark navy | `.hero` in `assets/css/main.css`, plus per-template bespoke ones | everything else |

**Read `inc/page-hero-spotlight.php` end to end before writing anything.** It is the
pattern to extend, it is heavily commented, and extending its config array is cheaper
and safer than writing a third renderer.

---

## 2. What already shipped (do not redo)

All committed and deployed to `vancehealthhub.co.uk` on 2026-08-28.

- `inc/page-hero-spotlight.php` — config, value resolution, renderer and Customizer
  registration for the Contact and About spotlight heroes
- `assets/css/main.css` — a new block for the page variant (eyebrow chip, utility
  band, figure card), inserted **before** the "Article cards stay square" block, which
  must stay last in the file
- `page-contact-us.php`, `page-about.php` — the design branch
- `functions.php` — the `require_once`, which must come **after** `hero-spotlight.php`
  (the page variant reuses its colour defaults and `vance_hex_to_rgb_triple()`)
- `customizer-pages.php` — calls `vance_page_hero_spotlight_customize()`
- `page-gi-health.php`, `page-gi-condition.php` — unrelated reveal-animation fix,
  see §7
- `tests/` — three harnesses plus two mutation runners, see [tests/README.md](tests/README.md)

### Update, later the same day — the three free-tools pages

Commit `411d002`, deployed and verified live. **All three default to `classic`, so
nothing about the live pages changed.**

- `inc/page-hero-spotlight.php` — three more config entries (`hquiz`, `recipes`,
  `malnutrition`), a `tools` slot variant, per-page card icons, and two config keys
  the original two pages did not need. See §3.5.
- `assets/css/main.css` — a small `__slot--tools` block. **It went out inside
  commit `0794cd4`, not `411d002`** — see §6.1.
- `page-healthcare-quiz.php`, `page-gastro-recipies.php` — the design branch
- `inc/tool-page-shell.php` — an opt-in `$vance_tool_hero_page`, so the shell can
  draw either hero. `page-malnutrition-calculator.php` sets it. Any future tool page
  built on the shell gets the choice by setting one variable.
- `template-parts/recipe-hub-app.php` — `id="recipes"`, which the category chips
  have always linked to and nothing on the page carried
- `functions.php`, `tests/*` — comment and coverage

**The band on all three carries the OTHER two tools plus a link to Tools &
Resources**, reading each tool's own name and badge settings. That was the one real
design decision and it is worth re-reading §3.4 before changing it: a visitor who
lands on one tool from search cannot otherwise tell the other two exist, and doing it
this way means renaming a tool in the Customizer renames it in the other two heroes
with no second copy to keep in sync. The alternative the client may prefer —
at-a-glance facts (time to complete, what you get) — needs new copy per page and is
a new `slot` variant, not an edit to this one.

**Photographs: every candidate was opened before it was chosen, and that mattered.**
The obvious pick for the survey, `about/gut-health-wellness.jpg`, is a flat-lay on a
**black chalkboard** — exactly the dark smear §6.4 warns about. The survey ended up
on `gi-health/ibs.jpg`, which is also the IBS condition page's photograph: the
best-lit asset in the theme for a pale band, carrying nothing IBS-specific, but a
dedicated one would be better. The planner uses `about/wellness-kitchen.jpg` and the
calculator `about/digital-health-tech.jpg`; both are genuinely light down the left
edge, which is the edge the band dissolves.

**Card overlap.** The quiz and calculator cards pull up `-40px` to bite into deep
navy. The spotlight band has no depth to bite into, so both wind that back to `+28px`
under `.quiz-page-wrapper.is-spotlight` / `.tool-page--spotlight`. **Any further
template whose content overlaps its hero needs the same.**

### Update — Ask AI, Get Started Today, the User Guide, and Our Heritage retired

Same day. **All three new toggles default to `classic`.**

- Three more config entries: `askai`, `evidence`, `userguide`. Ask AI's Customizer
  controls live in `functions.php` (section `vance_askai_settings`, panel
  `vance_content_panel`) rather than `customizer-pages.php` — that file registers at
  `customize_register` priority 10 and this one at 20, so the section exists in time.
- `page-ask-ai.php` also needed the `-40px` overlap wound back
  (`.askai-page--spotlight`).
- **Get Started Today lives at `/get-started-today/`, not
  `/turn-evidence-into-action/`, which 404s.** CLAUDE.md's "bind the Turn Evidence page
  template" task is therefore already done; corrected there.
- Two new mechanisms, both earning their keep on exactly one page so far:
  - `legacy_btn1` / `legacy_btn1_default` — button 1's LABEL inherited from the
    classic hero, the way the eyebrow/headline/intro already are. Get Started Today
    needs it: an admin relabelled that CTA "Join Now!" in the Customizer, so a
    spotlight button carrying the code default would have silently renamed the page's
    primary call to action. Declaring it drops `btn1_text` from that page's own field
    list, so there is still only one place to type the label.
  - `btn2_download` — adds the `download` attribute, for the User Guide's PDF.
- **The band is always three cells.** `vance_page_hero_spotlight_tools()` adds the
  "browse all free tools" cell only when it has dropped something. A tool page always
  drops itself, so it lands; Ask AI and the User Guide are not tools, so all three are
  listed and a fourth cell would squeeze them. It also means clearing a tool's name
  brings the shelf back rather than leaving a gap.
- Get Started Today gets a band of its own, `pillars` — the four evidence pillars, read
  from the settings the pillar cards further down the page already use. It renders the
  **badges** markup, so there are still only two band shapes in the CSS; its modifier
  only lays four cells out two-by-two, and does so inside `@media (min-width: 901px)`
  so it cannot beat the shared rule that stacks every band on a phone.

**One thing was lost on purpose, and one by accident of design:**

- `#evd-hero-join-btn`, the analytics id on Get Started Today's join CTA, is not on the
  spotlight button. The label and the pinned `/login/?tab=signup` link both survive;
  the click attribution does not. Add the id to the renderer if that number matters.
- The User Guide hero's `$pdf_meta` line (file size and date) beside the download. The
  same download appears twice more further down that page, both times with the meta,
  which is the only reason this was acceptable.

**Our Heritage is retired**, per §4.2's warning and an explicit decision:

- `page-our-heritage.php` deleted, and its ~420 lines of Customizer registration
  removed from `customizer-pages.php`. That block used to be registered and then
  removed again at the end of the same function, so ~200 settings existed that no
  admin could ever see.
- `inc/retired-redirects.php` is new: a `slug => destination` table and a 301. It runs
  on the request **path**, not on the queried object, so it works whether the page is
  published, trashed or gone. `/our-heritage/` → `/about/`.
- The WP Page (id 389) is trashed, and the orphaned template removed from the server
  by hand — **necessary, because neither deploy path ever deletes.** Both untar over
  the live theme, so a file deleted in the repo lives on indefinitely on the server.
- The saved `vance_heritage_*` theme mods are deliberately still in the database. They
  are the only remaining copy of what that page said.

**Current live state, all five:**

| Page | Toggle | State |
|---|---|---|
| Contact Us | `vance_contact_hero_style` | **spotlight**, with the white band deliberately **off** (`vance_contact_hero_spot_show_slot => false`) — the client's choice, do not "fix" it |
| About Us | `vance_about_hero_style` | **spotlight** |
| Gastro Health Survey | `vance_hquiz_hero_style` | classic — never switched on |
| Meal planner | `vance_recipes_hero_style` | classic — never switched on |
| Malnutrition calculator | `vance_malnutrition_hero_style` | classic — never switched on |

The three tool pages were verified live in the classic state on 2026-08-28: each still
renders its own dark hero, `vhh-hero-spotlight` appears nowhere in their HTML, and the
two new anchors do — which is what proved the deploy had landed. Nobody has seen any
of the three spotlight heroes rendered.

Design reference (mockups, as-built): https://claude.ai/code/artifact/45add81d-a122-45c1-8497-8ac4c2635d9c

---

## 3. The pattern to follow

### 3.1 Every page gets a toggle, defaulting to the old hero

`vance_{page}_hero_style`, values `classic` | `spotlight`, defaulting to `classic` via
`vance_page_hero_spotlight_default_style()`. Deploying must change nothing until an
admin flips it. The default is deliberately a **function, not a literal** — the
Customizer's registered default and the runtime `get_theme_mod()` fallback are separate
mechanisms and drifted apart when both were written by hand.

The template branch is:

```php
if ( function_exists( 'vance_page_hero_spotlight_active' ) && vance_page_hero_spotlight_active( 'patients' ) ) :
    vance_render_page_hero_spotlight( 'patients' );
else :
    // ... the existing classic hero, untouched ...
endif;
```

### 3.2 Reuse the homepage's CSS class, do not copy it

The section renders as `.vhh-hero-spotlight .vhh-hero-spotlight--page`, so the band,
the photograph's dissolve, the type scale, the CTAs, the card and the whole responsive
ladder come from one stylesheet block. That inheritance also carries the doubled-class
`!important` rules that opt the hero out of the global mobile type normalisation — see
§6.3. Only genuinely new sub-components get new CSS.

### 3.3 Copy is shared, never duplicated

The eyebrow, headline and intro read the **classic hero's own Customizer keys**, so
editing them moves whichever design is switched on and nothing has to be retyped to
switch back. Only layout-specific settings take new `vance_{page}_hero_spot_*` keys.

### 3.4 Fill the utility band with what the page's visitors came for

The slot where the homepage puts its search field is the design's one variable. It
should be filled from settings the page **already has**, not new ones:

- Contact → email / phone / opening hours, as real `mailto:` / `tel:` links
- About → the three assurance badges, with stat 1 as the floating card
- each free tool → the **other** two tools and a link to Tools & Resources, reading
  each one's own name and badge settings (`vance_page_hero_spotlight_tools()`)

For each new page, find the equivalent before inventing anything.

The tools band is the clearest illustration of the rule, because the obvious answer
was wrong. A tool page's visitor came for the tool — but the tool is already on the
screen below the fold, so putting facts about it in the band says nothing new. What
they cannot see is that the other two tools exist and are equally free. Ask what the
page does **not** already tell them.

Mechanically it is also the cheapest kind of variant to add: it reuses the `lines`
markup unchanged, so the renderer branches on "not badges" and the only new CSS is
word-wrapping and a chevron.

### 3.5 Adding a page, concretely

1. Add an entry to `vance_page_hero_spotlight_config()`, and its key to
   `vance_page_hero_spotlight_pages()`. Everything else — defaults, value
   resolution, Customizer controls — is driven from those two.
2. Two of those config keys cannot be derived from the page key, and getting either
   wrong fails **silently in WordPress** rather than loudly in the suite:
   - `style_section` — the existing Customizer section the design toggle goes in.
     It is not always `vance_{page}_hero`: the tool pages keep their hero controls
     under the Tools panel, in sections named after the tool. A toggle registered
     into a section that does not exist is dropped without a warning.
   - `classic_template` — the file holding the classic hero's own fallbacks, which
     is what section 0b holds the `legacy_*_default` values against.
   Give it a `section_title` too if another spotlight section already shares its
   panel; `hero-customizer.test.php` fails if two in one panel share a title.
3. If the page needs a band or card shape that does not exist yet, add a `slot` or
   `card` variant and its CSS. A `slot` that is a list of icon + caption + value
   should reuse the `lines` markup and add only a modifier, as `tools` does — the
   renderer already branches on "not badges".
4. Add the branch to the template. Respect any embed/chromeless mode it has.
5. Add the page to **section 0** of `tests/hero-render.test.php` — the pristine
   case. Sections 0b, 7 and 8 walk `vance_page_hero_spotlight_pages()` and need no
   edit. Section 0 is still non-negotiable and still hand-written, because 0b can
   only check defaults that section 0 has proved are real.
6. Run all three suites **and** both mutation runners.

---

## 4. The remaining work, by template

`vance_get_theme_mod()` key prefixes are given so you can find each hero's saved
settings without hunting.

### 4.1 The big win — 15 templates share one `.hero` class

`.hero` is defined once in `assets/css/main.css:518` (dark navy gradient, `min-height:
460px`, `padding: 100px 0 160px`). These templates use it:

```
archive.php                    page-healthcare-professionals.php   page-turn-evidence-into-action.php
category-content-healthcare-news.php                               page-user-guide.php
page-ask-ai.php                page-knowledgebase.php              page.php
page-education.php             page-patients.php                   search.php
page-gi-condition.php          page-tools-resources.php            template-parts/subcategory-grouped-archive.php
page-gi-health.php
```

⚠ This list said sixteen and named `page-healthcare-quiz.php`. **That was wrong** —
that template has never carried `.hero`; its hero is the bespoke `quiz-hero` styled
inline, which is why it is listed in §4.2 as well. It is now converted. Fifteen
remain, and the sentence below still holds for all of them.

**Do not simply restyle `.hero`.** It would convert all fifteen at once with no toggle
and no way back, and several of them override it locally. Convert them one at a time
behind their own toggles. Judgement call worth raising with the client early: fifteen
separate Customizer toggles may be worse UX than one estate-wide switch with per-page
opt-outs.

| Template | Key prefix | Notes |
|---|---|---|
| `page-patients.php` | `vance_pat_hero_*` | Has an overlay slider registered by the shared `$hero_overlay_pages` loop |
| `page-healthcare-professionals.php` | `vance_hcp_hero_*` | Same |
| `page-education.php` | `vance_edu_hero_*` | |
| `page-tools-resources.php` | `vance_tools_hero_*` | |
| `page-knowledgebase.php` | `vance_kblobby_hero_*` | Separate from the `kb-mini-hero` in `front-page.php` |
| ~~`page-turn-evidence-into-action.php`~~ | `vance_evidence_hero_*` | **DONE** — `vance_evidence_hero_style`. Lives at `/get-started-today/`; `/turn-evidence-into-action/` 404s |
| ~~`page-user-guide.php`~~ | `vance_userguide_hero_*` | **DONE** — `vance_userguide_hero_style` |
| `page-gi-health.php`, `page-gi-condition.php` | — | Also carry the `.gi-reveal` animation, see §7 |
| `page.php` | — | **Generic page fallback.** Covers Accessibility, Medical Disclaimer and any page without its own template. Highest blast radius on this list — do it late and check what actually uses it |
| `archive.php` | `vance_cat_hero_*` | **Category archives** — a post type, per the brief |
| `search.php` | `vance_search_hero_*` | Search results |
| `category-content-healthcare-news.php`, `template-parts/subcategory-grouped-archive.php` | — | Category variants |

### 4.2 Bespoke heroes, styled inside their own template

These have no `main.css` rule; their CSS is inline in the template, so converting them
means deleting that CSS too.

| Template | Class | Key prefix |
|---|---|---|
| ~~`page-ask-ai.php`~~ | ~~`askai-hero`~~ | **DONE** — `vance_askai_hero_style`. Its controls are in `functions.php`, section `vance_askai_settings` |
| ~~`page-healthcare-quiz.php`~~ | ~~`quiz-hero`~~ | **DONE** — `vance_hquiz_hero_style` |
| ~~`page-gastro-recipies.php`~~ | ~~`vance-rh-hero`~~ | **DONE** — `vance_recipes_hero_style` |
| ~~`page-malnutrition-calculator.php`~~ | ~~`tool-page-hero`~~ | **DONE** — `vance_malnutrition_hero_style`. It was never listed here; its hero comes from `inc/tool-page-shell.php`, which now draws either |
| `page-terms-of-use.php`, `tpl-privacy-policy.php`, `page-accessibility.php`, `page-medical-disclaimer.php` | `legal-hero` | — Four templates share it; convert together |
| ~~`page-our-heritage.php`~~ | ~~`vance-about-hero`~~ | **RETIRED**, not converted — template deleted, page trashed, `/our-heritage/` 301s to `/about/` |

**`page-ask-ai.php` is the last unconverted tool page.** It is not one of the three
the client calls free tools — those are the three on the Tools & Resources grid — but
if it is converted, its band should almost certainly be the same `tools` variant, and
`vance_page_hero_spotlight_tools()` will need a fourth entry and a decision about
whether Ask AI belongs in the other three pages' bands too.

### 4.3 Post types

- **`single.php`** — the `oped-hero`, styled in `assets/css/oped-template.css` and
  `main.css`. This is the article hero every post uses; it carries per-post category
  colour via a hero-overlay setting. **Highest-traffic hero on the site — do it last,
  and expect it to need its own design pass** rather than a straight port. The pale mint
  band may not suit a full-bleed article header.
- **`archive.php`** — category archives, listed above.

---

## 5. Verify like this

There is no WP install locally, so the loop is: lint → harnesses → mutation runners →
deploy → fetch the live URL and grep for real content.

```bash
cd tests && php hero-render.test.php && php hero-customizer.test.php && node reveal.test.js
```

```bash
cd tests && python mutate-hero.py && python mutate-defaults.py
```

`php -l` every touched file. PHP and Node are both on PATH on this machine.

Counts after Ask AI / Get Started / User Guide: **193 / 79 / 22**, and 14 + 12 mutants
all red.

⚠ **A mutant that goes red by crashing is not evidence of coverage.** One added this
round forced every band through the lines markup, which fed plain strings to code
expecting arrays: PHP fatal, non-zero exit, and not one failing assertion. It read as
"went RED" in the runner's output. Replaced with the realistic version of the same
slip — a slot listed under the wrong markup — which produces 18 real FAILs. **Check the
failing count, not just the colour.**

### 5.1 Traps that have already cost real time

- **The Customizer preview is not evidence about the live site.** It serves each
  setting's *registered* default; `get_theme_mod()` on the front end returns the
  default you *pass*. Reading a key with `''` renders an empty hero on the live site
  while looking perfect in the preview. This shipped once — the live About hero went
  out with no headline, no intro and no badges. **Always copy the classic template's
  own default verbatim, and always render the pristine case in tests.**
- **A status code proves nothing.** Assert on content — grep the live HTML for the
  actual words.
- **Grep the Boost bundle, not the page,** to prove CSS shipped. See the memory note
  on `boost-cache/static/*.min.css`.
- **Read what is really saved** before calling something a bug:
  ```bash
  ssh -i ~/.ssh/hostinger_sla -p 65002 u767439438@82.29.185.3 "cd ~/domains/vancehealthhub.co.uk/public_html && wp eval 'print_r(get_option(\"theme_mods_vance-health-hub\"));'"
  ```
  A missing band turned out to be an admin's own saved `false`, not a defect.
- **No rendered page is visible from this harness.** The Browser pane does not display,
  cannot open `file://` paths, and cannot load `claude.ai` artifact URLs. Ask the client
  for a screenshot for anything visual, and say plainly when you have not seen it.
- **You CAN open the image files.** The Read tool renders them. Do it before choosing
  any hero photograph — filenames lie by omission. `gut-health-wellness.jpg` sounds
  like the obvious survey image and is a flat-lay on black.
- **Pick a live-grep marker that only the markup can satisfy.** Grepping the deployed
  page for `is-spotlight` reported a hit on a page rendering the classic hero: it was
  matching the rule `.quiz-page-wrapper.is-spotlight` inside the template's own inline
  `<style>`. Grep for the attribute (`class="quiz-page-wrapper"`) instead. And pick at
  least one marker that is **new in the commit you just pushed** — `id="recipes"` and
  `id="tool"` are what actually proved the deploy had landed, because they are absent
  from the commit before it.

---

## 6. Things that will bite

### 6.1 A parallel session shares this working tree
Another agent commits to `main` throughout the day. **Never `git add -A`** — stage your
own paths explicitly. `git fetch` before every push; HEAD moved four times during the
Contact/About work, and twice more during the free-tools work — including once
*between* the commit and the push.

**It runs both ways, and the other direction is the one that surprises you.** During
the free-tools work the parallel session staged broadly and swept this session's
finished `assets/css/main.css` into *its* commit (`0794cd4`, "Shorten the section-seam
blend by a further 30%"), which was pushed and deployed before this session committed
anything. So:

- Stylesheet changes can ship **ahead of** the PHP that uses them. Harmless here — the
  rules only matched classes nothing emitted yet — but do not assume your CSS and your
  templates go live together.
- **Re-check `git diff --cached` right before committing, not just `git status` at the
  start.** The staged `main.css` at commit time contained only the other session's two
  unrelated colour tweaks; committing it would have put their in-progress work live
  under this session's message. `git diff --cached -U0 -- <file> | grep '^@@'` tells
  you in one line whether the hunks are anywhere near your work.
- A file "still differing from HEAD" does **not** mean your change is uncommitted. Check
  the marker, not the file: `git show HEAD:<file> | grep -c <something-you-added>`.
- `git show --stat HEAD~1` can name a commit you do not recognise because HEAD moved
  under you mid-session. Resolve commits by SHA, not by `HEAD~n`, once you know another
  agent is committing.

### 6.2 Push = live deploy
`.github/workflows/deploy.yml` fires on any push to `main` touching the theme, and ships
`git archive HEAD:wp-content/themes/vance-health-hub` — the **committed** tree, not the
working tree. Uncommitted work is never deployed. Repo owner is `slapharma`, so run
`gh auth switch --user slapharma` before every push; the account drifts.

### 6.3 Mobile type normalisation
`mobile-base.css` and `mobile-components.css` load last and pin bare `h1` / `h2` with
`!important` below 480px. The spotlight hero opts out by doubling its own class
(`.vhh-hero-spotlight .vhh-hero-spotlight__title`). Any new hero heading needs the same
or it renders larger than the body text beside it.

### 6.4 Hero photographs
Every classic hero's image was chosen to sit under a ~78% navy veil. Dropped onto a pale
band with no veil they read as dark smears, and some are low-resolution watermarked stock
that only survived because the veil hid them. **Give each converted hero its own image
key** (`vance_{page}_hero_spot_image`) rather than reusing the classic one, exactly as
Contact and About do. The homepage asset also has an alpha feather baked into its left
edge that the CSS gradients only finish — un-feathered images can show a seam.

### 6.5 The band sizes itself
`.vhh-hero-spotlight__slot` uses `grid-auto-flow: column`, not `repeat(3, ...)`, because
cells whose setting is empty are dropped. A fixed track count leaves a blank slab.

---

## 7. Unrelated fix in the same commits — the `.gi-reveal` animation

`page-gi-health.php` and `page-gi-condition.php` hid `.gi-reveal` content via
unconditional CSS with no JS gate and no failsafe, so any failure to observe left content
permanently invisible. `page-gi-health.php` also bound its init to `DOMContentLoaded`,
which has usually already fired by the time Jetpack Boost's deferred bundle runs the
script. Both now skip the animation under `is_customize_preview()`, reveal on-screen
items at 1.2s and everything at 5s, run regardless of `readyState`, and carry a
`<noscript>` override. Covered by `tests/reveal.test.js`.

**The same trap exists in `page-about.php`** (`.vabout-js .vabout .reveal`), fixed the
same way. If you meet another reveal-on-scroll animation while converting heroes, check
it for all four failure modes before leaving it alone.

---

## 8. CLAUDE.md's "CRITICAL outstanding work" list is partly stale — verify before acting

Two of the items I spot-checked on 2026-08-28 were **already done**, so do not take that
section at face value:

| CLAUDE.md claims | Actually |
|---|---|
| Hard-coded OpenRouter API key in `inc/dashboard-functions.php` | **Done.** No literal key anywhere in the theme; `inc/askai-functions.php:1010` reads `vance_get_theme_mod( 'vance_askai_api_key', '' )`. The key still needs to have been *rotated* after its exposure — confirm with the client |
| `vance_evidence_*` Customizer controls unregistered (§6.5) | **Done.** `customizer-pages.php` registers 110 of them, hero set included |

Not checked this session, so still assume open:

- **Exposed SSH deploy key** from the 2026-06-24 misdirected deploy — rotate.
- `siteurl` / `home` still `http://`.
- Body text / post meta still containing `slahealth.co.uk` / `vancemedical.co.uk`.

Worth doing early: re-verify that whole section and correct CLAUDE.md, so the next
reader after you does not chase work that is finished.
