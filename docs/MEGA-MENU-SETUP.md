# Primary menu — mega panel setup

The code half of this feature ships in the theme:

| File | What it does |
|---|---|
| `assets/css/nav-mega.css` | Panel geometry and interior styling |
| `inc/nav-mega.php` | Three widgets + the inline icon set |
| `functions.php` | One `require_once` line |

The structure half is admin work in WordPress, described below. **Until the
admin steps are done, shipping the theme changes nothing user-visible** — the
menu keeps rendering as flyouts and the new CSS matches nothing. That is
deliberate: it makes the deploy safe to do at any time and the switch-on a
separate, reversible decision.

Plugin: **Max Mega Menu 3.10.5, free edition.** Everything below is inside the
free feature set. There is no `megamenu-pro` on this server and none is needed.

---

## 0. Before you start — what the audit found

Every URL in the tree below was fetched from the live site on 2026-08-28 and
checked for actual body content. **16 pages rendered a hero and an empty
`entry-content` div — no content at all.** They are excluded from the menu:

`register-as-a-patient` · `register-as-a-practitioner` · `take-a-quiz` ·
`take-a-quiz-2` · `vance-user-messages` · `hcp-learn-more` · `our-mission` ·
`products` · `contribute-to-the-hub` · `contribute` · `collaborate-with-us` ·
`advertise` · `podcast-guest` · `become-a-podcast-guest-on-the-hub` ·
`meal-plan` · `take-our-survey` · `clinical-reviews-2`

Two consequences worth knowing:

- **The whole "Work with us" column is gone.** Contribute, Collaborate,
  Advertise and both podcast pages are all blank, so there is nothing to put
  in it. Contact Us survives and moves into the Vance Medical column.
- **Both registration pages are blank**, so `Create an Account` points at
  `/register/` (95 words, working) instead.

All seven condition pages are substantial (545–783 words), so the CONDITIONS
panel is unaffected.

### Duplicate URLs — decided

| Pair | Canonical | Note |
|---|---|---|
| `/gastro-recipies/` · `/gastro-meal-planner/` | **`/gastro-recipies/`** | Byte-identical content on two URLs. Matches `page-gastro-recipies.php`. |
| `/gastro-health-survey/` · `/healthcare-quiz/` | **`/gastro-health-survey/`** | Also identical. Matches the label on `/free-health-tools/`. |

Both templates declare a `Template Name:` header, so they are assigned in the
page editor rather than bound to the slug — redirecting the loser is safe and
will not change which template renders.

The other five "pairs" needed no decision: one side of each was simply blank.

---

## 1. Build the tree — Appearance → Menus

Select the **Primary Menu**. Add the second and third levels by drag-and-drop.

In a Max Mega Menu grid, a **second-level** item renders as a column heading and
its **third-level** children render as the links beneath it. So the nesting below
*is* the layout — no code decides it.

**Three top-level items.** `HOME` is dropped (the logo links home on every page),
`ABOUT` and `FOR PROFESSIONALS` are folded into `THE HUB` as a second row, and
`CONDITIONS` takes the freed slot.

```
THE HUB                                      [custom link, Disable link]
│
├─ ROW 1 ─────────────────────────────────────────────────────────────
├─ Start here                                [custom link, Disable link]
│  ├─ Get Started                            /get-started-today/
│  ├─ How to Use the Hub                     /how-to-use-the-hub/
│  ├─ User Guide                             /user-guide/
│  └─ Create an Account                      /register/
├─ Free Health Tools                         /free-health-tools/
│  ├─ Malnutrition Calculator                /malnutrition-calculator/
│  ├─ Gastro Health Survey                   /gastro-health-survey/
│  └─ Recipes & Meal Planner                 /gastro-recipies/
├─ Your Account                              /dashboard/
│  ├─ My Dashboard                           /dashboard/
│  └─ My Notes                               /my-notes/
└─ [widget] CTA: Ask VANCE-Ai
│
├─ ROW 2 ─────────────────────────────────────────────────────────────
├─ For Professionals                         /healthcare-professionals/
│  ├─ HCP Hub                                /healthcare-professionals/
│  ├─ Clinical Data Reviews                  /category/content-clinical-reviews/
│  └─ Webinars & Courses                     /webinars-and-courses/
├─ Vance Medical                             /about-us/
│  ├─ Who We Are                             /about-us/
│  ├─ Our Heritage                           /our-heritage/
│  └─ Contact Us                             /contact-us/
└─ [widget] CTA: Register as a practitioner

KNOWLEDGEBASE                                /knowledgebase/
├─ Browse the library                        /knowledgebase/
│  ├─ All Articles                           /knowledgebase/
│  ├─ Gastro Health Explained                /gastro-health-explained/
│  ├─ Webinars & Courses                     /webinars-and-courses/
│  └─ Recipes & Meal Planner                 /gastro-recipies/
├─ By content type                           [custom link, Disable link]
│  ├─ Gastro Living Insights                 /category/content-gastro-living/
│  ├─ Gastro Health News                     /category/content-healthcare-news/
│  └─ Clinical Data Reviews                  /category/content-clinical-reviews/
└─ [widget] Featured Articles — 2, live

CONDITIONS                                   /gastro-health-explained/
└─ [widget] 7 condition tiles + 1 feature tile
   (no child menu items — this panel is one widget)
```

### Fix the parent-link bug while you are here

`ABOUT US` and `THE HUB` are currently custom links pointing at the homepage, so
clicking the parent bounces the visitor to the front page. Every item marked
**`[Disable link]`** above must have that box ticked:

> hover the item → **Mega Menu** → *General* tab → **Disable link** ✓

The parent then becomes a pure toggle. `nav-mega.css` already removes the pointer
cursor from a disabled heading, so it stops advertising itself as clickable.

---

## 2. Switch each top-level item to a mega panel

For each of the three top-level items:

1. Hover the item in Appearance → Menus, click the blue **Mega Menu** button.
2. *General* tab → **Sub Menu Display Mode** → **Mega Menu**.
3. Use the grid builder to add the rows and columns listed below.
4. Drag each second-level item into its column; drop the widgets into theirs.

| Panel | Grid (of 12) | Cells |
|---|---|---|
| THE HUB — row 1 | 3 · 3 · 3 · 3 | Start here · Free Health Tools · Your Account · **CTA widget** |
| THE HUB — row 2 | 3 · 3 · 6 | For Professionals · Vance Medical · **CTA widget** |
| KNOWLEDGEBASE | 3 · 3 · 6 | Browse the library · By content type · **Featured widget** |
| CONDITIONS | 12 | **Tiles widget** |

THE HUB carries two CTAs because it now serves two audiences — the second row is
the only conversion path clinicians have left in the header. `nav-mega.css`
draws a hairline divider between rows automatically.

---

## 3. Widget contents

All three widgets appear in the mega-menu builder's widget picker under their
`Hub Nav:` names. Leave the **Heading** field blank on any widget that sits next
to link columns which already carry their own headings.

### CONDITIONS → `Hub Nav: Icon Tiles`

- **Heading:** `Understanding digestive conditions`
- **Columns:** `3`
- **Tiles:** one per line, `icon | Title | Description | /path/`. Start a line
  with `*` to make it the wide feature tile.

```
organ | Inflammatory Bowel Disease | The umbrella term — start here | /inflammatory-bowel-disease/
pulse | Ulcerative Colitis | Symptoms, flares and treatment | /ulcerative-colitis/
drop | Crohn's Disease | Diagnosis through to daily life | /crohns-disease/
flask | Microscopic Colitis | Often missed, very treatable | /microscopic-colitis/
clipboard | Irritable Bowel Syndrome | Triggers, testing and diet | /irritable-bowel-syndrome/
ribbon | Colorectal Cancer | Screening and early signs | /colorectal-cancer/
book | Diverticular Disease | And diverticulitis | /diverticular-disease/
* quiz | Not sure where to start? | Take the short self-assessment and get a summary you can share with your clinician. | /gastro-health-survey/
```

Available icons: `sparkles, organ, pulse, drop, flask, clipboard, ribbon, book,
quiz, calculator, leaf, shield, users, stethoscope, play, grid, note, mail, map,
arrow`. An unrecognised name renders the tile with no icon rather than breaking.

### THE HUB, row 1 → `Hub Nav: CTA Rail`

| Field | Value |
|---|---|
| Eyebrow | `Always on` |
| Headline | `Ask VANCE-Ai anything about gut health` |
| Supporting line | `Evidence-based answers drawn from the Hub's own clinical library, any time of day.` |
| Button label | `Start a chat` |
| Button URL | `/ask-ai/` |
| Button icon | `sparkles` |

### THE HUB, row 2 → `Hub Nav: CTA Rail`

| Field | Value |
|---|---|
| Eyebrow | `For clinicians` |
| Headline | `Give your patients somewhere reliable to go` |
| Supporting line | `Register a practitioner account to share tools, track referrals and access the clinical library in full.` |
| Button label | `Create a practitioner account` |
| Button URL | `/register/` |
| Button icon | `shield` |

### KNOWLEDGEBASE → `Hub Nav: Featured Articles`

| Field | Value |
|---|---|
| Heading | `Latest from the Hub` |
| Category | `All categories` |
| How many | `2` |
| "See more" label | `See everything in the Knowledgebase` |
| "See more" URL | `/knowledgebase/` |

This is the one cell that has to be code rather than a Custom HTML widget: it
queries the two most recent posts on every render, so the panel cannot go stale.

---

## 4. Deploy and verify

Deploy the theme with the standard command in `CLAUDE.md`, then purge in order:
**Hostinger cache → LiteSpeed → Jetpack Boost.**

Jetpack Boost concatenates stylesheets into a single hashed bundle, so
`nav-mega.css` is **not** linked from the page by its own URL. Do not grep the
page HTML for it — that returns 0 whether the deploy worked or not.

Grep the bundle body instead, and include a control class that is already
deployed so you can tell "not there yet" from "my grep is wrong":

```bash
curl -s "$(curl -s https://vancehealthhub.co.uk/ | grep -oE 'https://[^"]+boost-cache/static/[^"]+\.min\.css' | head -1)" | grep -c "vance-nav-tile"
```

Verified working on 2026-08-28: returns `1` for `vance-nav-tile`, `vance-nav-cta`
and `vance-nav-feature`, and `1` for the control `vance-header-search`.

Note that a grep of the *page* for `vance-nav-tile__icon` stays at 0 until a
tiles widget is actually placed in a panel — that is a check of the admin work,
not of the deploy.

### Smoke tests

- [ ] Each of the three items opens a panel spanning the full container width,
      left edge level with the logo, right edge level with the search button
- [ ] Panel opens *below* the header, not inside it
- [ ] Clicking `THE HUB` no longer navigates to the homepage
- [ ] THE HUB shows two rows with a hairline divider between them
- [ ] Column headings are teal, uppercase, with a rule underneath
- [ ] Condition tiles show icons and hover to a pale teal
- [ ] `Tab` walks into a panel and `Esc` closes it (plugin-provided)
- [ ] Focus ring is visible on every panel link
- [ ] At 375px the drawer shows condition tiles as plain rows, no CTA card,
      no article thumbnails, every row at least 46px tall
- [ ] No horizontal scrollbar at 375px

---

## Things that will bite

**Never use `position: fixed` inside a panel.** `.site-header` carries
`transform: translateZ(0)` (a paint fix), which makes it a containing block for
fixed descendants — a fixed child is trapped in the header instead of the
viewport, on desktop where the transform still applies.

**The mobile drawer (QA review §5) is already fixed** — the `footer.php` script
added 2026-08-07 works. Measured live at 375px on 2026-08-28: the drawer opens
to `left: 0`, 300×812, painted navy, and all four links are hit-testable. What
was still wrong is that the drawer is `position: fixed` but was resolving
against the header rather than the viewport, so it was only correct while the
header sat at exactly `top: 0`. `mobile-base.css` now clears the transform below
768px, which anchors the drawer to the viewport. Re-verified with the same
probe after the change.

**Panel headings must not be `h*` or `p` elements.** `mobile-base.css` and
`mobile-components.css` load last and pin those sizes with `!important` below
768px. Everything in `nav-mega.css` uses `<a>` and `<span>` for this reason.

**Do not reuse `assets/img/icons/*.svg` here.** Those are pre-rebrand files with
`fill="#FF5A00"` baked in and render orange. `vance_nav_icon()` in
`inc/nav-mega.php` carries its own `currentColor` set instead.

**Regenerating the plugin's CSS does not break the panels.** `nav-mega.css`
marks only the load-bearing geometry `!important`, at matching specificity, for
exactly that reason. Every cosmetic rule wins on specificity alone and stays
overridable.
