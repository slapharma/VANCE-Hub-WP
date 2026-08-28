# Handover — rolling the spotlight hero out to the remaining templates

**Date:** 2026-08-28
**Prereq reading:** [CLAUDE.md](CLAUDE.md) first, then this file. The load-bearing
constraints in CLAUDE.md (theme slug, `_sla_*` meta, `vance_get_theme_mod()`, the
radius token scale, the cross-file contracts) all still apply and are not repeated here.

---

## 1. What this task is

The homepage runs a light, action-led hero — the "spotlight" design. It has now been
carried across to **Contact Us** and **About Us**. The job for the next session is to
carry it across to **every remaining page and post type**.

Three heroes exist today:

| Design | Renderer | Used by |
|---|---|---|
| Spotlight (light, mint band) | [inc/hero-spotlight.php](wp-content/themes/vance-health-hub/inc/hero-spotlight.php) | homepage |
| Spotlight, page variant | [inc/page-hero-spotlight.php](wp-content/themes/vance-health-hub/inc/page-hero-spotlight.php) | `/contact-us/`, `/about/` |
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

**Current live state:** both toggles are switched **on**. On Contact, the white band is
deliberately **off** (`vance_contact_hero_spot_show_slot => false`) — that is the
client's choice, do not "fix" it.

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

For each new page, find the equivalent before inventing anything.

### 3.5 Adding a page, concretely

1. Add an entry to `vance_page_hero_spotlight_config()`. Everything else — defaults,
   value resolution, Customizer controls — is driven from it.
2. If the page needs a band or card shape that does not exist yet, add a `slot` or
   `card` variant and its CSS.
3. Add the branch to the template.
4. Add the page to sections 0 and 0b of `tests/hero-render.test.php`.
5. Run all three suites **and** both mutation runners.

---

## 4. The remaining work, by template

`vance_get_theme_mod()` key prefixes are given so you can find each hero's saved
settings without hunting.

### 4.1 The big win — 16 templates share one `.hero` class

`.hero` is defined once in `assets/css/main.css:518` (dark navy gradient, `min-height:
460px`, `padding: 100px 0 160px`). These templates use it:

```
archive.php                    page-healthcare-professionals.php   page-turn-evidence-into-action.php
category-content-healthcare-news.php  page-healthcare-quiz.php     page-user-guide.php
page-ask-ai.php                page-knowledgebase.php              page.php
page-education.php             page-patients.php                   search.php
page-gi-condition.php          page-tools-resources.php            template-parts/subcategory-grouped-archive.php
page-gi-health.php
```

**Do not simply restyle `.hero`.** It would convert all sixteen at once with no toggle
and no way back, and several of them override it locally. Convert them one at a time
behind their own toggles. Judgement call worth raising with the client early: sixteen
separate Customizer toggles may be worse UX than one estate-wide switch with per-page
opt-outs.

| Template | Key prefix | Notes |
|---|---|---|
| `page-patients.php` | `vance_pat_hero_*` | Has an overlay slider registered by the shared `$hero_overlay_pages` loop |
| `page-healthcare-professionals.php` | `vance_hcp_hero_*` | Same |
| `page-education.php` | `vance_edu_hero_*` | |
| `page-tools-resources.php` | `vance_tools_hero_*` | |
| `page-knowledgebase.php` | `vance_kblobby_hero_*` | Separate from the `kb-mini-hero` in `front-page.php` |
| `page-turn-evidence-into-action.php` | `vance_evidence_hero_*` | CLAUDE.md §6.5 says these controls are unregistered. **That is stale** — verified 2026-08-28, `customizer-pages.php` registers 110 `vance_evidence_*` settings including the full hero set. Nothing to fix; CLAUDE.md needs correcting |
| `page-user-guide.php` | `vance_userguide_hero_*` | |
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
| `page-ask-ai.php` | `askai-hero` | `vance_askai_hero_*` |
| `page-healthcare-quiz.php` | `quiz-hero` | `vance_hquiz_hero_*` |
| `page-gastro-recipies.php` | `vance-rh-hero` | — (see `recipe-hub.css`) |
| `page-terms-of-use.php`, `tpl-privacy-policy.php`, `page-accessibility.php`, `page-medical-disclaimer.php` | `legal-hero` | — Four templates share it; convert together |
| `page-our-heritage.php` | `vance-about-hero` | `vance_heritage_hero_*` |

⚠ **`page-our-heritage.php` is being retired.** `customizer-pages.php` removes its whole
panel at the end of registration. Confirm with the client before spending effort — it is
an unlinked clone of About.

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

---

## 6. Things that will bite

### 6.1 A parallel session shares this working tree
Another agent commits to `main` throughout the day. **Never `git add -A`** — stage your
own paths explicitly. `git fetch` before every push; HEAD moved four times during the
Contact/About work.

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
