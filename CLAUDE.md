# CLAUDE.md — Vance HealthHub WP

**Live site:** https://vancehealthhub.co.uk
  (the legacy `https://gastrohealthhub.com` Hostinger account is dormant — TLS errors;
   `https://www.vancehealthhub.com` is a frameset wrapper pointing at the `.co.uk`
   and is NOT a hosting target.)
**Repo:** https://github.com/slapharma/VANCE-Hub-WP (this one)
**Theme path (in repo):** `wp-content/themes/vance-health-hub/`
**Theme path (on server):** `~/domains/vancehealthhub.co.uk/public_html/wp-content/themes/vance-health-hub/`
  (renamed from `~/domains/gastrohealthhub.com/...` during the domain swap — all earlier
   handover commands referencing the old path must be rewritten.)
**Host:** Hostinger, SSH `u767439438@82.29.185.3` port 65002, key `~/.ssh/hostinger_sla`
**Other domains on the same Hostinger account:** `vancemedical.co.uk`, `ibdhealthhub.com` (do not touch).

This repo consolidates and replaces the earlier `.gemini/SLAHealthHub` working tree and the archived `slapharma/SLAHealthHub` GitHub repo. The authoritative context for every decision below is [REBRAND-HANDOVER.md](REBRAND-HANDOVER.md) — **read that first** before any non-trivial work.

---

## Load-bearing constraints — break any of these and the site silently breaks

### 1. Theme folder is `vance-health-hub` (renamed 2026-08-18 from `sla-health-hub`)
The rename is done — repo, server folder, `wp_options.template`/`stylesheet`, the
`theme_mods_*` option key, and all in-DB `themes/sla-health-hub` URLs were migrated in one
window. See [TODO-RENAME.md](TODO-RENAME.md) for the sequence that was executed and the
rollback path.

The coupling that made it risky still exists, so treat any *further* slug change the same
way: the folder name must stay in lockstep with the `Text Domain:` header in `style.css`,
the ~600 `esc_html__(..., 'vance-health-hub')` call sites, `wp_options.template`,
`wp_options.stylesheet`, and the `theme_mods_vance-health-hub` option key. Changing the
folder alone breaks theme activation and silently empties every customizer setting.

### 2. DO NOT rename `_sla_*` user/post meta keys
92 call sites across `inc/dashboard-functions.php` + `functions.php`. Every user's profile image, bookmarks, notes, saved searches, quiz results, calculator history lives under these keys. Renaming orphans all user data. Form fields submit as `vance_*` and the handler translates back to `_sla_*` on write — keep that translation.

### 3. DO NOT touch `vance_get_theme_mod()` in `functions.php`
The legacy prefix is assembled via `implode('', array('s','l','a','_'))` **on purpose** to survive bulk text-replace passes. A naïve rename rewrites the literal and the customizer fallback silently returns defaults — every admin-saved logo, URL, social link disappears from the frontend with zero error.

### 4. NEVER run a bare `SLA` → `Vance` search-replace
It clobbers `SLA Pharma` (the parent entity kept unchanged per legal decision) and `translateX` substrings inside CSS. Use full-phrase substitutions:
- `SLA Health Ltd` → `Vance Medical Foods Ltd`
- `SLA Health` → `Vance Medical`
- `SLA Health Hub` → `Vance Medical Hub`
- `SLAHealthHub` → `VanceMedicalHub`

Only `\bSLA\b` (word-boundary) is safe as a bare pattern.

### 5. Corner radius is a token scale — never hard-code a radius, never re-square a card
The site was originally authored square. It now runs on a three-step scale defined once in
`assets/css/main.css` `:root`, taken from the homepage spotlight hero:

| Token | Value | Used for |
|---|---|---|
| `--radius-control` | 6px | buttons, icon tiles, chips, badges, avatars |
| `--radius-field` | 10px | inputs, selects, search bars, dropdown menus |
| `--radius-surface` | 14px | cards, panels, modals, banners, media boxes |
| `--radius-pill` | 999px | deliberately-round pills and toggles |
| `--radius-article` | 0 | **article cards only** |

`--radius-md` and `--radius-lg` are kept as aliases of control/surface so the ~43 older call
sites inherit the scale. Two rules:

1. **Article cards are the one square exception, enforced in exactly one block** — the
   "Article cards stay square" rule at the end of `main.css`. It sits last on purpose: several
   of those selectors are also styled earlier with `var(--radius-lg)`, and at equal specificity
   the later rule wins. Add or remove post tiles *there*, never by scattering `border-radius: 0`
   into the component. That block is also why a `.va-fl-featured` or `.bento-cell-*` renders
   square while its neighbours round.
2. **`border-radius: 0` inside a `@media (max-width: …)` is usually correct** — six rules square
   a modal/sheet/iframe when it goes full-bleed on mobile. Don't "fix" those.

PHP files use `var(--radius-*, Npx)` with a literal fallback because several render where
`main.css` is absent: the wp-login screen, admin message threads, and tool embeds.

### 6. Cross-file contracts — rename both sides in the same commit
| Contract | Server | Client |
|---|---|---|
| AJAX action names | `wp_ajax_vance_*` | `action: 'vance_*'` in fetch/ajax calls |
| Nonces | `check_ajax_referer('vance_dashboard_nonce', 'nonce')` | `wp_create_nonce('vance_dashboard_nonce')` |
| REST routes | `register_rest_route('vance-health/v1', ...)` | `fetch('/wp-json/vance-health/v1/ai-chat', ...)` |
| postMessage types | `if (e.data.type === 'VANCE_SAVE_MALNUTRITION_RESULT')` | `parent.postMessage({ type: 'VANCE_SAVE_MALNUTRITION_RESULT', ... })` |

### 7. Minified JS tool bundles are text-rewritten in place
`assets/tools/ai-widget/index-*.js`, `assets/tools/blood-test/index-*.js`, `assets/tools/malnutrition-calculator/index-*.js` are Vite build artifacts from the (separate) `temp_calc/` and `temp_malnutrition_calc/` React sources. The rebrand strings/colors/message types were patched directly into the bundles. If anyone rebuilds from source, the brand will revert — re-run the `LOCAL/vance_*.py` transformers on the source before build, or port the substitution lists into the build step.

---

## Settled — do NOT re-investigate these

Three items sat under a "CRITICAL outstanding work" heading for months after they
had been fixed. Checked on 2026-09-01 and all three are done:

- **Exposed OpenRouter API key** — gone. No hard-coded key anywhere in
  `wp-content/`; `inc/askai-functions.php:1010` reads
  `vance_get_theme_mod( 'vance_askai_api_key', '' )`, which is exactly what the
  old note prescribed. The one grep hit left is a `placeholder=` attribute on an
  admin form.
- **`siteurl` / `home` on `http://`** — both are `https://vancehealthhub.co.uk`.
- **Legacy domains in the database** — no published content contains
  `slahealth.co.uk`, `gastrohealthhub.com` or `vancemedical.co.uk`. A search for
  `sla-health-hub` returns 281 rows, but every one is a `customize_changeset`
  post — Customizer autosave records holding the old theme_mod namespace. They
  are inert. Two option rows (`cmplz_options`, `acf_site_health`) still mention
  the old domains; low impact, worth a look when someone is next in Complianz.

### Security: exposed SSH deploy key — rotation UNCONFIRMED
On 2026-06-24 a misdirected manual deploy (run from the repo root) left the whole
repo root sitting in the live, web-served theme dir, exposing a **private SSH key**
(`.deploy_key`) plus the handover docs and compliance `.docx` files publicly over
HTTPS. The stray files were removed and the deploy command was replaced with
`git archive` (see Deploy workflow), which cannot ship untracked files at all.

**Whether the key was ever rotated is not recorded anywhere, and cannot be told
from the repo.** Confirm before assuming it was:
1. Generate a new keypair locally.
2. On the Hostinger server, replace the old public key in `~/.ssh/authorized_keys`; remove the leaked one.
3. Update the `HOSTINGER_SSH_KEY` GitHub Actions secret with the new private key.
4. Update local `~/.ssh/hostinger_sla`.
5. Confirm both the manual deploy and the GitHub Actions deploy still authenticate.

`.deploy_key` is gitignored, so it was never in the GitHub repo — the website was the only leak vector.

### ~~WP admin: bind the Turn Evidence page template~~ — DONE
Verified live 2026-08-28. The page is bound and published, but at slug
**`get-started-today`** (page id 398), not `turn-evidence-into-action` — that slug
404s. Anything referring to the old URL is stale.

### ~~Customizer controls missing for new evidence page~~ — DONE
`customizer-pages.php` registers ~110 `vance_evidence_*` settings, the full hero set
included. Verified 2026-08-28.

### ~~Editorial: 66 wrong citations in the patient guides~~ — RESOLVED 2026-09-01
Found by resolving all 635 DOIs on the site: 23 did not exist and 43 resolved to a
different paper than the citation named — right journal, right year, wrong article,
with the stated year matching the real paper in 40 of the 43. Not a typo pattern; a
mistyped DOI 404s rather than landing on a plausible neighbour. Clean before 24 July
2026 (1 bad in 88 refs), 12.4% after (65 in 525), with references per article rising
4.2 → 6.6 at the same point.

**26 were repaired** — re-queried against CrossRef on their own reference text and
accepted only where title, first author and year all agreed, with every replacement
confirmed to resolve before it was written. **40 could not be traced to a real paper
and were removed whole**, each article keeping four to eight references.
`wp vance citations` now reports 595 of 595 correct.

**What is still open:** removing a reference does not repair the sentence it was
supporting. Around 25 articles now carry claims with nothing behind them. That is an
editorial job, and it is the largest piece of unfinished work on the site.

**If you ever bulk-substitute DOIs again:** replace the specific occurrence, not
every match. Doing a `str_replace` over `post_content` broke a correct citation in
*10 of the Best Foods for a Happy, Healthy Gut*, which cited two papers from the
same journal where the identifier being corrected was legitimately attached to the
second one. The checker caught it on the next run.

---

## Outstanding work — as at 2026-09-01

Ordered by what costs most to leave alone.

1. **No named medical reviewer.** `inc/medical-review.php` ships the whole
   mechanism — an editor box, a visible line, `reviewedBy` and `lastReviewed` on
   the schema — and sets it on nothing, on purpose. Meanwhile `/about-us/` badges
   the site **"Clinician Approved"** and claims a "peer-reviewed clinical evidence
   base". That claim is not currently evidenced, which is a CAP/ASA exposure and
   not only an SEO one. One real clinician with a real qualification turns it on,
   one article at a time.
2. **Claims left unsourced.** See the citation item above: ~25 articles carry
   assertions whose reference was removed.
3. **Search Console and GA4 are connected and returning data.** Do not re-diagnose
   this. A `missing_required_scopes` error from `get_data()` almost certainly means
   the caller built the module before `wp_set_current_user()` — Site Kit binds the
   OAuth client to the current user at construction, so a module built as user 0 has
   no token no matter what is stored. Set the user first, then construct:

   ```php
   wp_set_current_user( 1 );                       // module ownerID
   $ctx     = new \Google\Site_Kit\Context( WP_PLUGIN_DIR . '/google-site-kit/google-site-kit.php' );
   $modules = new \Google\Site_Kit\Core\Modules\Modules( $ctx );
   $modules->get_module( 'search-console' )->get_data( 'searchanalytics', $args );
   ```

   Baseline on 2026-09-01, trailing 90 days: **6 clicks, 668 impressions**, 150
   queries and 70 pages with any impressions at all. Almost everything sits between
   position 40 and 90. Three queries are inside the top 20, all of them variants of
   the one clinical abstract that ranks — "the global burden of inflammatory bowel
   disease" at position 7. That page is the site's only click.
4. **Eleven scaffolded pages are still empty.** Noindexed and out of the sitemap
   (see `inc/seo-archive-robots.php`), so they do no harm, but they are still
   published and blank. Two are the same page twice: `/contribute/` against
   `/contribute-to-the-hub/`, `/podcast-guest/` against
   `/become-a-podcast-guest-on-the-hub/`. Write one of each and retire the other
   through `vance_retired_redirects()`.
5. **87 titles still run past 60 characters**, down from 150. The 16 worst — the
   clinical abstracts, up to 182 characters — have hand-written SEO titles, and
   dropping the site-name suffix fixed 45 more. The rest need writing individually.
6. **Google Tag Manager is 170 KB**, now the single largest asset on the site.
7. **No PageSpeed API key**, so there is no Core Web Vitals baseline. The keyless
   quota is shared and exhausted.
8. **No keyword or backlink source.** Nothing competitive can be done without one.
9. **15 recipe taxonomy archives are noindexed but still in the sitemap.** AIOSEO
   free ships no Term model and its sitemap query joins the post table only, so
   there is no supported way to exclude a term. Google fetches each once and drops
   it. Do not try to fix this with `aioseo_sitemap_exclude_terms` — see below.

---

## AIOSEO on this install — three traps

The plugin works, but its own options object misbehaves here, and two of its
filters do not have the shape you would expect. All three cost a live incident
before being written down.

1. **Its options object cannot be trusted for reads or writes.** Nested paths
   return `null` for values that demonstrably exist — that is why
   `inc/seo-archive-robots.php` exists at all, and why the title suffix is stripped
   in `inc/seo-title.php` rather than by setting the title format. The per-content-
   type formats are not even in the stored option; the plugin builds that structure
   at runtime. **Prefer a filter to a setting, every time.**
2. **`aioseo_schema_output` passes the whole `@graph`, not one node.** Treating the
   argument as a single node is a silent no-op: a list has no `@type` key, the guard
   returns early, and the property never reaches the page while every unit test that
   hands the function one node passes. `inc/medical-schema.php` has it right and is
   the file to copy.
3. **Never register `aioseo_sitemap_exclude_posts` / `_exclude_terms`.**
   `excludedObjectIds()` checks `has_filter()` before its early return, so merely
   registering a callback pushes it into
   `aioseo()->options->sitemap->{$type}->advancedSettings->{$option}` — which is
   null here. `array_merge()` throws, sitemap generation dies partway, and
   `vance_recipe-sitemap.xml` disappears. To keep a post out of the sitemap, write
   the stored `robots_noindex` / `robots_default` columns through the plugin's own
   `Post` model; the sitemap query reads those directly.

---

## Icons: Dashicons is not loaded for visitors

`inc/frontend-assets.php` dequeues Dashicons for logged-out users — 34.7 KB of
admin icon font that no public page used. Logged-in users keep it for the admin bar.

**If a glyph turns into a tofu box, this is why.** Max Mega Menu draws its dropdown
arrow and mobile close button as private-use-area characters from that font, and
nothing in the markup says so — there is no `dashicons` class anywhere, only a
`font-family` on a pseudo-element. A crawl for `class="dashicons"` finds nothing and
proves nothing. Both are now drawn from borders and a Latin-1 character instead (see
"Mega menu arrows" in `main.css`).

MegaMenu's icon picker also uses Dashicons, so assigning an icon to a menu item in
its settings will produce a tofu box. To check for others, read the computed
`font-family` of every `::before` and `::after` on a rendered page — markup
inspection cannot see this class of dependency.

---

## Citation checking

`inc/citation-check.php` resolves every DOI in a post against CrossRef about
30 seconds after publish and writes the verdict to a **Citations** column on the
Posts list and a table in the editor sidebar. It flags, it never blocks — a
network call between the publishing pipeline and a successful save would turn a
CrossRef outage into a content outage.

```bash
ssh -i ~/.ssh/hostinger_sla -p 65002 u767439438@82.29.185.3 "cd ~/domains/vancehealthhub.co.uk/public_html && wp vance citations --quiet-ok"
```

`--post=<id>` for one article, `--refresh` to ignore the 30-day cache. **Exits
non-zero when anything is wrong**, so it can gate a deploy.

Three things to know before changing it:

1. **The paper's title may legitimately be in the post title rather than the
   reference line.** The clinical abstract posts summarise one paper and put its
   title in the heading. Dropping that allowance turns 43 real mismatches into 48
   reported ones — it was the false positive in the first pass of the audit.
2. **Cache asymmetry is deliberate.** A hit caches 30 days because registered
   metadata never changes; a 404 caches only 3, because a DOI can be registered
   after an article quotes it; a transport failure is not cached at all, or one
   CrossRef wobble reads as a bad citation for a month.
3. **`inc/citation-links.php` is a separate concern** — it makes the DOIs
   clickable at display time. The checker reuses its punctuation helper, so it is
   required first in `functions.php`. Neither rewrites `post_content`.

---

## Recipe labels

On 2026-09-18 the 90 recipes' tags were rebuilt from Daisy Gershon's spreadsheet
(`Recipe-Tag-Descriptor-Chart-Daisy Edit.xlsx`, Google Drive id
`1oi3jzOYtc2CcWFYEjOcV7f2gsHsey5dq`), which is the source of truth for which
recipe carries which label — not the theme, not the database.

One taxonomy, `vance_recipe_tag`, 14 terms, in two layers. The split lives only
in `vance_recipe_label_model()` in `inc/recipe-frontend.php`; a term whose slug
isn't in that registry is never shown, whatever it's assigned to in the database.

- **8 core** — the only labels offered as hub filters: Gluten Free, Dairy Free,
  Vegetarian, Vegan-Friendly, Low Fibre, High Fibre, High Protein, Low FODMAP.
- **6 descriptors** — chips on a recipe card, not filters: No onion, No garlic,
  Easy to digest, Nutrient dense, Refined sugar free, Omega-3 Rich.

Three terms are assigned but never rendered on purpose: the condition terms
`ibd` (79), `ibs` (11), `ulcerative-colitis` (9). Whether readers should be able
to filter by condition is Daisy's open decision, not a bug. Six retired terms
(`garlic-free`, `onion-free`, `oat-free`, `mediterranean-style`,
`anti-inflammatory-breakfast-smoothies`, `5-ingredient-meals-for-busy-days`) are
left in the taxonomy unassigned (count 0) rather than deleted.

Live counts after the 2026-09-18 apply: Gluten Free 70, Dairy Free 57,
Vegetarian 53, Vegan-Friendly 26, Low Fibre 8, High Fibre 59, High Protein 52,
Low FODMAP 14, No onion 67, No garlic 62, Easy to digest 17, Nutrient dense 63,
Refined sugar free 89, Omega-3 Rich 23.

Rules from Daisy's handover, worth knowing before touching an assignment:
labels describe the recipe as written, not an aspiration; don't add Low FODMAP
to any recipe that doesn't already carry it (14 recipes are on it, pending
dietitian sign-off against Monash); Low Fibre / High Fibre / High Protein are
formulas run against the nutrition meta, so editing a recipe's nutrition means
re-checking it against the sheet, not just trusting the existing term; five
recipes (3015, 4315, 4321, 4325, 4298) have a queried fibre figure and carry no
fibre label until it's recalculated.

**Re-applying after the spreadsheet changes:** regenerate `tools/recipe-labels.json`
from the sheet, copy it and `tools/apply-recipe-labels.php` to the server, then:

```bash
wp eval-file apply-recipe-labels.php <labels.json> <backup-dryrun.json>        # dry run
wp eval-file apply-recipe-labels.php <labels.json> <backup.json> apply     # write; new backup path, it refuses to overwrite one
```

Arguments are positional on purpose — `wp eval-file` swallows `--flags`, so a
`--dry-run` flag would never reach the script. It checks all 90 ids and titles
and the expected per-label counts against the database before writing anything,
backs up the previous assignments first, and after writing, verifies from the
database rather than from what it just sent. It exits non-zero on any mismatch.
The expected counts are hard-coded in the script itself and in
`tests/recipe-labels.test.php` — both must be updated together with the JSON
whenever the sheet changes, or the next apply will refuse to run. The pre-change
backup of the old assignments is `tools/recipe-tags-backup-2026-09-18.json`.

---

## Deploy workflow

**Use `git archive`, not `tar` on the working tree.** Run it from anywhere in the
repo; there is no working directory to get wrong.

```bash
git archive --format=tar HEAD:wp-content/themes/vance-health-hub | gzip -n | \
ssh -i ~/.ssh/hostinger_sla -p 65002 u767439438@82.29.185.3 \
  "set -e; \
   THEME=~/domains/vancehealthhub.co.uk/public_html/wp-content/themes/vance-health-hub; \
   cd \"\$THEME\"; \
   tar --warning=no-file-changed -czf \"\$THEME/../vance-health-hub-pre-deploy-\$(date +%Y-%m-%d-%H%M).tar.gz\" . || true; \
   tar xzf -; \
   echo 'DEPLOY_OK'"
```

Three reasons this replaced the old `tar`-the-working-tree command, all of them
things that actually went wrong:

1. **The repo lives inside a Google Drive folder.** Drive touches files while tar
   is reading them, so `tar` prints `file changed as we read it` and exits 1. On
   2026-08-31 that killed one deploy at the pipe and, on the retry, made the
   server-side backup return non-zero so `set -e` aborted before the extract. The
   `|| true` and `--warning=no-file-changed` above are for the backup step only —
   a warning while snapshotting must not stop the deploy.
2. **`git archive` reads the object store, not the disk**, so nothing can perturb
   it mid-stream, and it ships the **committed** tree — exactly what CI ships. The
   old command shipped the working tree, so the two paths could disagree.
3. **It cannot leak untracked files.** The 2026-06-24 incident — a deploy run
   from the repo root that published `.deploy_key`, the handover docs and the
   compliance `.docx` files over HTTPS — is impossible here, because untracked
   files are not in the archive at all. The directory guard and the long
   `--exclude` list existed to prevent that and are no longer needed.

⚠ It ships **HEAD**, so commit first. `git status --porcelain wp-content/themes/vance-health-hub`
should be empty before you run it, or you will deploy something other than what
you are looking at.

⚠ **`DEPLOY_OK` not printing does not mean the deploy failed**, and printing does
not prove the right bytes arrived. Verify by content:

```bash
cd wp-content/themes/vance-health-hub && md5sum inc/page-hero-spotlight.php assets/css/main.css
```

then the same on the server. Files this repo wrote with LF will differ from the
CRLF `git archive` produces — compare with `tr -d '\r'` before calling it a
mismatch.

After deploy: purge Hostinger cache (hPanel → Cache Manager → Purge All), LiteSpeed plugin cache if installed, and bump the `wp_enqueue_style` version string in `functions.php` if CSS changed.

Full deploy/rollback commands and the three-layer cache order are in [REBRAND-HANDOVER.md §5](REBRAND-HANDOVER.md).

### Plugin deploy (vhh-annotations)

The annotations companion plugin lives at `wp-content/plugins/vhh-annotations/` and deploys separately. Run this **only** from that directory (same guard pattern as the theme):

```bash
# Abort unless the current dir really is the plugin dir.
if [ ! -f vhh-annotations.php ] || ! grep -q 'Plugin Name' vhh-annotations.php; then
  echo 'ABORT: run this from wp-content/plugins/vhh-annotations/'; exit 1
fi
TSTAMP=$(date +%Y-%m-%d-%H%M) && \
tar czf - --exclude='./.git' --exclude='./.claude' . | \
ssh -i ~/.ssh/hostinger_sla -p 65002 u767439438@82.29.185.3 \
  "set -e; \
   PLUGIN=~/domains/vancehealthhub.co.uk/public_html/wp-content/plugins/vhh-annotations; \
   mkdir -p \"\$PLUGIN\" && cd \"\$PLUGIN\" && \
   if [ -f vhh-annotations.php ]; then tar czf \"\$PLUGIN/../vhh-annotations-pre-deploy-${TSTAMP}.tar.gz\" .; fi && \
   tar xzf - && \
   echo 'PLUGIN_DEPLOY_OK'"
```

First deploy only — activate over SSH: `cd ~/domains/vancehealthhub.co.uk/public_html && wp plugin activate vhh-annotations`. The feature ships with its master toggle **off** (Appearance → Customize → Article Annotations); activation alone changes nothing user-visible. CI (`.github/workflows/deploy.yml`) also ships the plugin automatically on pushes touching it.

---

## Smoke tests after any deploy
- Front page loads with teal primary (`#008080`)
- Header logo ~25% larger than stock (225px desktop)
- `/ask-ai/` heading reads **"VANCE Ai"** (not "Ask AI" — that wording is stale) and
  chat sends/receives (REST route `wp-json/vance-health/v1` + customizer API key)
- `/get-started-today/` renders the evidence pillars (NOT `/turn-evidence-into-action/`,
  which 404s)
- The recipe hub is at **`/gastro-meal-planner/`** (NOT `/ibd-recipies/`, which 404s,
  and not `/gastro-recipies/`, which 301s). It lists every published `vance_recipe`,
  and its hero's white band counts the collection live — one cell for the total and
  one per meal slot, from `vance_page_hero_spotlight_recipe_stats()`. A wrong count
  means the query changed, not that a value went stale.
- Every **category archive** shows the light spotlight hero (`inc/category-hero.php`):
  eyebrow pill, teal headline, and a white band of live facts — Articles / Topics /
  Last added. The numbers are computed per request, so a wrong one means the query
  changed, not that a value went stale. Check a top-level section
  (`/category/content-gastro-living/`) AND a sub-category
  (`/category/food-nutrition/`): the sub-category must show a breadcrumb, inherit the
  parent's photograph, and NOT repeat the parent's name in the pill. Below 768px the
  band stays on **one row** — long values switch to a short form (`Aug 2025`, not
  `August 2025`) via `vance_hero_band_value()`; the full text is still in the markup
  for a screen reader, just visually hidden. Every spotlight hero, this one included,
  now has two Customizer checkboxes — "Show the white band on mobile" and "Show the
  floating card on mobile" — both defaulting ON, so a fresh install should show no
  difference from before.
- `/gastro-health-explained/` and all seven condition pages show the light spotlight hero
  (`inc/gi-hero.php`): purple eyebrow, teal CTA, and the lobby's seven condition chips on
  **two** rows **on desktop**. Three rows means the copy column narrowed — check, don't
  "fix" the split. Below 768px the chips switch to **one horizontally-scrolling row**
  instead — two (or three) rows there means the phone CSS in `main.css` didn't load.
- Dashboard → Profile edit saves (AJAX nonce + `_sla_*` meta round-trip)
- Malnutrition calculator completes (postMessage contract)
- WP Customizer opens and saves cleanly
- **Menu arrows render as chevrons**, not tofu boxes — Dashicons is dequeued for
  visitors and the arrows are drawn in CSS. Three visible indicators on desktop.
- **Article DOIs are links.** `wp vance citations` should report 595 ok and exit 0.
- **Recipe hub's Recipe Tags dropdown lists exactly the 8 core labels** (no
  descriptors, no condition terms). Cards show descriptor chips underneath.
  `?tags=low-fodmap` shows 14 of the 90 recipes.
- **Articles show their condition chips** under the copy (`va-article-conditions`)
  and carry `about` → `MedicalCondition` in the schema. 143 of 149 do; six general
  pieces deliberately show nothing.
- **Every image has a real alt.** The homepage should report 27 images, 27
  described, none empty. If card thumbnails come back empty, a template is
  discarding `_wp_attachment_image_alt` again — see `inc/thumbnail-alt.php`.
- **`og:image` is present on every page**, falling back to
  `assets/img/og-default.jpg` where AIOSEO has none.
- **The Customizer shows 24 theme panels**, grouped Site / Home / Content /
  Section / Pages / Members / Website Functions. No panel called "Hero
  Overlays" or "Advanced" — those are the old names; see "Customizer
  structure" above.

A quick way to run most of this: fetch every URL in the sitemap and assert on
content, not status codes. All 217 returned 200 with zero PHP notices on
2026-09-01.

---

## Repo structure
```
.
├── CLAUDE.md                 ← this file
├── REBRAND-HANDOVER.md       ← authoritative rebrand context
├── TODO-RENAME.md            ← deferred theme-folder rename sequence
├── docs/                     ← design/implementation notes
│   ├── ASK_AI_REDESIGN.md
│   ├── DISCOVERY_SUITE_IMPLEMENTATION.md
│   ├── IMPLEMENTATION_SUMMARY.md
│   └── UI_UPDATES_SUMMARY.md
├── wp-content/
│   ├── themes/
│   │   └── vance-health-hub/   ← the actual WordPress theme
│   └── plugins/
│       └── vhh-annotations/  ← highlight/comment companion plugin (see Plugin deploy)
└── LOCAL/                    ← gitignored, one-shot transformer scripts
```

### The SEO includes, and which problem each one solves

All are required from `functions.php` and all exist because a setting either did
not work or did not exist. Load order matters where noted.

| File | Does |
|---|---|
| `inc/seo-archive-robots.php` | Noindexes tag and author archives, the eleven unwritten pages, and undescribed recipe terms. Self-healing: write the page or the term description and it indexes again. Author archive is gated on the author having a bio. |
| `inc/seo-title.php` | Strips the ` - Vance Health Hub` suffix from titles. Front page keeps its brand-first title. |
| `inc/citation-links.php` | Makes the 635 DOIs clickable at display time. Nothing rewrites `post_content`. |
| `inc/citation-check.php` | Resolves every DOI against CrossRef on publish; `wp vance citations`. **Requires citation-links.php first** — reuses its punctuation helper. |
| `inc/medical-review.php` | `reviewedBy` + a visible review line. Ships empty on purpose. |
| `inc/article-conditions.php` | `about` → `MedicalCondition` and the condition chips under an article. **Requires medical-schema.php first** — reuses its registry and `#medicalcondition` @id. |
| `inc/social-image.php` | Default `og:image` / `twitter:image`, and `og:site_name` as a name rather than name-plus-tagline. |
| `inc/frontend-assets.php` | Dequeues Dashicons for visitors; defers the Google sign-in client to first interaction or idle. |
| `inc/thumbnail-alt.php` | `vance_thumbnail_alt()` — the featured image's alt, for the card templates that build their own `<img>` from a thumbnail URL. |

Two conventions worth keeping. **Everything is display-time**: none of these
rewrite stored content, so backing any of them out is deleting a `require` line.
And **everything degrades to the previous behaviour** rather than inventing data —
an image with no alt still renders `alt=""`, a post with no reviewer renders
nothing, an article with no confident condition match gets no chip.

`inc/hero-mobile.php` is loaded the same way but is not an SEO include: it holds
the phone-only "show on mobile" band/card switches and the band's short labels,
shared by every `.vhh-hero-spotlight` family (homepage, page heroes, category
archives, GI heroes, policy documents). It is required early in `functions.php`
(line 37) because the category, GI, legal and page hero files call its helpers
behind `function_exists()` and fall back to the plain full-text render if it is
ever missing. `inc/customizer-structure.php` is loaded right after
`inc/customizer-gi-health.php` and is also not an SEO include: it decides where
every Customizer panel and section appears on screen, not what those panels
contain. See "Customizer structure" below.

### VanceHubClub preview mode — `inc/hub-preview.php`

Required in `functions.php` before the `hub-*` files, and by each of them. Every
`vance_hub_*_active()` asks `vance_hub_preview_query()` (`'hub'`, `'classic'` or
`''`): an admin's `?vhh_layout=` wins, else preview mode gives `'hub'`, else the
saved switches decide. Preview mode is on for:

- **an admin** with the `_vance_hub_preview` user meta (VanceHubClub admin page or
  "Club preview" in the admin bar, nonce + `edit_theme_options`);
- **a share link** `/?vhh_preview=<token>` (option `vance_hub_preview_share`,
  14 days). It sets the HttpOnly cookie `vance_hub_preview` = HMAC of the token.
  The cookie is checked against the current token on every request, so "New
  link" / "Turn sharing off" kills every cookie.

Presentation only: no capability, no login bypass. Preview responses send
`nocache_headers()`, `litespeed_control_set_nocache`, `X-Robots-Tag` and a
`wp_robots` noindex. The state is decided on `init`; the test harnesses never
fire it, so preview mode is off in them.

**Cache caveat.** A page cache that serves a cached classic page bypasses PHP.
The cookie is added through LSCWP's documented `litespeed_vary_curr_cookies`
(X-LiteSpeed-Vary header on each response) and `litespeed_vary_cookies` (the
`.htaccess` Cache-Vary rule, written only when LSCWP rewrites `.htaccess`, e.g.
on a settings save). After deploy: purge every cache and re-save LSCWP settings.
Not verified on the live server yet; Hostinger's own cache layer is untested.

### Flare plan — `inc/hub-flare-plan.php`

A member's own plan for a flare. It shows as the "My flare plan" card in the Health Profile
tab of the VanceHubClub dashboard, on the "Flare plan" sub-tab. `page-dashboard.php`
(`case 'health-profile'`) calls `vance_hub_flare_plan_data()` for the current member and
prints the card with `vance_hub_flare_plan_html()`. The card is not in the dashboard home
render (`inc/hub-dashboard.php`) and not in `vance_hub_dashboard_features_data()`
(`inc/hub-dashboard-features.php`). The member writes three answers: early signs,
what they do first, and who they contact. Each answer holds up to 1000 characters.
The member can edit, print and delete the plan. The fourth section, "When to get
urgent help", is fixed text that an administrator enters.

The card also holds a flare log, a "How am I today?" quick check, a step-by-step
builder for a new plan, a plan progress bar and an appointment sheet. The card
shows them in this order when a plan is saved: progress bar, quick check, the three
answers, the urgent help text, the flare log, the print button, the appointment
sheet, "Edit my plan", and "Manage my flare plan data". With no saved plan, the card
shows the builder and the urgent help text only. Every part sits behind the same
switch. The flare log and the appointment sheet store nothing new of their own.

This is health data. The card records the member's own words and gives no advice.

- **Health Profile sub-tabs:** the Health Profile tab (`?tab=health-profile`) shows plain links
  built with `add_query_arg()`, so they work with no script. `Self Assessment` (`hsub=profile`)
  and `Screening results` (`hsub=screening`) always show. `Daily check-ins` (`hsub=checkins`)
  shows with the VanceHubClub dashboard on and check-in data. `Flare plan` (`hsub=flare`)
  shows only when `vance_hub_flare_plan_data()` returns data. `Manage my data` (`hsub=data`)
  shows with the VanceHubClub dashboard on, as the last link in the row.
- **Panels:** all three panels stay in the page. A panel that is not open has the `hidden`
  attribute, and `.hp-panel[hidden] { display: none !important; }` in
  `assets/css/hub-dashboard.css` hides it. The bar is `nav.vhd-subtabs`. Its styles are at the
  end of that file, and the bar is hidden when printing.
- **Which panel opens:** `hsub` picks the open panel. A missing or unknown value opens
  `profile`. With no `hsub`, a `vance_flare` code in the URL opens `flare`. With no `hsub`, a
  check-in delete result opens `checkins`, and a "Manage my data" result opens `data`. An
  explicit `hsub` wins over all of them.
- **Hub layout off:** the bar and the hidden panels need `vance_hub_dashboard_active()`. When
  it is false, every section shows, with no bar. The flare card does not show, because the
  data is fetched only when the hub dashboard is active.
- **After a save or delete:** the forms post to the current URL (`action=""`). The handler
  redirects to that URL with `vance_flare` set to the result code, so `tab` and `hsub` stay as
  they were. If `hsub` was not in the URL, the code still opens the Flare plan panel.
- **Switch:** `vance_hub_dash_flare_plan`. Default OFF, set in `vance_hub_switch_default()`
  (`inc/hub-layout.php`). The comment there says health-data cards stay off until the
  privacy policy covers them. Turn it on only after the policy covers the plan. The
  flare log, the quick check and the appointment sheet all sit inside this card, so the
  same switch turns them off too. The switch does not hide the "Manage my data" tab.
- **Urgent help text:** theme mod `vance_hub_flare_plan_urgent`, set in Customize under
  `vance_home_layout`. It has no default. While it is empty the card does not show and
  a save is refused. Do not write this text as an agent. A clinician must write or
  approve it, and the site has no named medical reviewer (see Outstanding work, item 1).
- **Storage:** `_sla_flare_plan` holds `{updated, signs, actions, contacts}`. The flare
  log is `_sla_flare_log` (see Flare log below). The agreement uses the check-in key
  pattern with base `flare_plan`:
  `_sla_consent_flare_plan` ('1' while the member agrees), `_sla_consent_flare_plan_at`,
  `_sla_consent_flare_plan_text` (the wording agreed to), and
  `_sla_consent_flare_plan_withdrawn_at` (set on delete). Never rename these keys
  (constraint 2).
- **Shared helpers:** `vance_hub_checkin_consent_keys()`, `vance_hub_checkin_consent_snapshot()`,
  `vance_hub_checkin_consent_restore()` and `vance_hub_checkin_consent_record()` take a
  `$base` argument. The plan passes `'flare_plan'`. The plan also reuses
  `vance_hub_checkin_consent_ok()`, the consent reference helpers and
  `vance_hub_checkin_lock()` from `inc/hub-dashboard-features.php`. The lock is one per
  member, so a plan save and a check-in save never run at the same time.
- **Not a generic tool:** the plan is not in `vance_known_tool_slugs()`
  (`inc/dashboard-functions.php`). That list opens the generic save handlers. The plan
  is written only by `vance_hub_flare_plan_handle_post()`, after validation.
- **Form:** posts to the page it is on (`action=""`), which is the Health Profile tab. Nonce action `vance_hub_flare_plan`, field
  `vance_hub_flare_nonce`. Field `vance_hub_flare_do` is `save` or `delete`. Answers are
  in `vhd_flare[signs]`, `vhd_flare[actions]` and `vhd_flare[contacts]`. The handler
  redirects with `?vance_flare=<code>`, and that code picks the message.
- **Builder (new plan only):** with no saved plan, the Form is a step-by-step builder. The
  form carries `data-vhd-wizard`. `assets/js/hub-flare-plan.js` shows one box at a time with
  Back and Next, and the text "Step n of 4". The consent box, when it shows, is on the last
  step. With no script every box shows on one page, and the form still posts. A saved plan
  opens the plain form under "Edit my plan" instead.
- **Progress bar:** the card shows "My plan: n of 4 done" with a bar. The four parts are the
  three answers (each counts when it has text) and one review: the review counts while the
  plan's last change is less than 183 days old. Each empty answer gets an "Add:" hint. A plan
  older than 183 days gets a hint with the months since the last change. The bar measures the
  plan only. It says nothing about the member's health.
- **How am I today? (quick check):** shown only with a saved plan. Three sliders from 0 to 4:
  Tummy pain, Bowel symptoms and Tiredness. The block is in the markup with the `hidden`
  attribute, and `assets/js/hub-flare-plan.js` removes it. With no script the block never
  shows. "Compare with my plan" reads the sliders and the newest check-in from
  `vance_hub_checkin_history()`. It shows both sets of values, names the symptoms that are
  higher than the last check-in, and scrolls to the plan and highlights it. It gives no
  verdict. With no saved check-in it says so. Nothing is saved or sent: the script makes no
  request.
- **Flare log:** `_sla_flare_log` holds rows `{id, start, end, helped}`, newest first, at most
  50 rows. `start` and `end` are Y-m-d dates in the site time zone. `end` is `''` while the
  flare is open. `helped` is "what helped", at most 500 characters. On read, a row with a bad
  date, a missing or non-string `end`, or an `end` before its `start` is dropped without a
  message.
  - **Start:** POST `vance_hub_flare_do=flare_start` records a row with today's date and no
    end. The card button is "I am in a flare".
  - **End:** POST `vance_hub_flare_do=flare_end` with the optional text in `vhd_flare_helped`
    sets `end` to today. If today is earlier than `start`, `end` becomes `start`. The card
    button is "My flare has ended". The "Edit my plan" box opens after an end.
  - **Needs:** a saved plan, the switch on with the urgent help text set, and an agreement to
    the consent wording in use now. No saved plan, or no urgent help text, gives `noplan`. A
    missing or old agreement gives `changed`. With the switch off the handler does nothing, so
    the POST gets no result code.
  - **Result codes:** `flare_started`, `flare_ended`, `flare_open` (a flare is already open),
    `flare_none` (no open flare to end), `noplan`, `changed` (the agreement is missing or the
    wording changed), `failed`. The card shows one fixed message for each code.
  - **Cap:** a start with 50 rows already stored drops the oldest row, with no warning.
  - **Days:** the count includes the first and the last day. An open flare counts up to today.
  - **Display:** the card and the sheet show the five newest rows. The card shows the total
    as "N flares logged".
  - **No edit, no single-row delete.** A member removes log rows only by deleting the plan and
    the log together, or by the "Manage my data" flare group.
- **Consent wording:** the default now names the plan and the flare dates: "I agree that the
  Hub stores my flare plan and my flare dates in my account. I can delete them at any time."
  Customize sets it through `vance_hub_flare_plan_consent` (empty uses the default). The
  agreement stores the wording it was given, and `vance_hub_checkin_consent_ok()` compares it
  with the wording in use, exactly. So a member who agreed to the older wording is asked
  again. The plan form shows the box, and a flare start or end returns `changed` until the
  member saves the plan with the box ticked. Any edit to the consent text in Customize asks
  every member again.
- **Appointment sheet:** `vance_hub_flare_sheet_data()` builds it on each view from data the
  member already saved. It stores nothing new. Sources:
  - `_sla_clinical_profile`: `next_appointment` (shown as "j M Y" when `strtotime()` reads it,
    otherwise left out), `appointment_questions` ("Questions to ask") and `medication`
    ("Medication").
  - The three plan answers, where not empty.
  - The five newest flare log rows, under "My flares".
  - Up to 14 newest check-ins from `vance_hub_checkin_history()`, as a table of Pain, Bowel
    and Tired (0 to 4).
  - The latest nutrition risk check: the score from the `malnutrition-calculator` history,
    with its risk label when one is stored.
  It starts "Prepared on <date> from what I saved in the Hub." and ends "Written by the member.
  It is not medical advice." It sits in a closed `<details>` box, "Appointment sheet", with its
  own button "Print appointment sheet" (`data-vhd-flare-print="sheet"`).
- **Manage my data:** `inc/hub-data-manage.php`. The tab is `?tab=health-profile&hsub=data`, the
  last link in the sub-tab row. It shows when the VanceHubClub dashboard is on
  (`vance_hub_dashboard_active()`), whatever the flare switch says. The `flare` group clears
  `_sla_flare_plan` and `_sla_flare_log` and withdraws the agreement the same way as the card's
  delete. A group shows its delete form only when it holds data.
- **Delete:** removes the plan AND the flare log, and withdraws the agreement. The agreement
  time and wording stay as a record. The card's "Manage my flare plan data" box runs this
  delete.
- **Privacy:** registered with the WordPress personal data tools under `vance-flare-plan`.
  The exporter writes the plan, every flare log row (start, end and what helped) and the
  agreement record. The eraser removes the plan, the flare log and every key listed above.
- **Print:** `@media print` in `assets/css/hub-dashboard.css` shows only the part being printed
  and hides the rest of the page. Before printing, `assets/js/hub-flare-plan.js` adds the class
  `vhd-print-target` to that part and `vhd-print-path` to each of its ancestors. It removes both
  on `afterprint`, and it adds `vhd-printing` to the `html` element while the marks are set. The
  part is the plan card (`.vhd-flare`), or the appointment sheet (`.vhd-flare-sheet`) when its
  box is open or its button was clicked. The plan print leaves out the progress bar, the quick
  check, the flare log, the appointment sheet, "Edit my plan" and "Manage my flare plan data".
  The buttons are "Print my plan" (`data-vhd-flare-print="plan"`) and "Print appointment sheet"
  (`data-vhd-flare-print="sheet"`). Both stay hidden until the script runs, so a page with no
  script shows no button that does nothing. A browser's own Print command, with no button, prints
  the sheet when its box is open and the plan otherwise. When the card sits in a hidden sub-tab,
  the script marks nothing and the page prints as the browser shows it.
- **Tests:** `tests/hub-flare-plan.test.php`. Run `cd tests; php hub-flare-plan.test.php`.
  It has 372 checks and exits 1 when any check fails. The checks cover the flare log, the quick
  check, the builder, the progress bar and the appointment sheet. The Health Profile tab is
  checked by reading `page-dashboard.php` as text, not by rendering it. The "Manage my data"
  flare group is covered by `tests/hub-data-manage.test.php`: it checks that the group holds the
  plan and the log, that erasing it clears both and withdraws the agreement, and that only a
  group with data shows a form.

## Customizer structure: one map decides where everything sits

Panels, sections and controls are still registered where they always were:
`functions.php`, `customizer-pages.php`, `inc/customizer-gi-health.php`,
`inc/dashboard-features.php`, `inc/page-hero-spotlight.php`,
`inc/hero-mobile.php`, and the `vhh-annotations` plugin. `inc/customizer-structure.php`
runs after all of that, on `customize_register` at priority 999 (after every
registration hook in the theme, which top out at 30, and after the plugin's),
and rewrites three things on every section it names: which panel it sits in,
its title, and its order. The `title` and `panel` args written at registration
are no longer what the admin sees. If you're hunting for why a panel says
something different from its `add_panel()` call, this file is why.

WordPress has no nested panels, so the seven silos read as a title prefix plus
a priority band:

| Silo | Priority band |
|---|---|
| Site | 10-19 |
| Home | 20-29 |
| Content | 30-39 |
| Section | 40-49 |
| Pages | 50-59 |
| Members | 60 |
| Website Functions | 70 |

That's 24 theme panels in total.

To move, rename or reorder a section, edit the map in this file, not the
`add_section()` call at its point of registration; the map wins regardless of
what the registration call says. To add a new section, register it as usual
and then add it to the map, otherwise it appears wherever its own `panel` arg
puts it, silently outside the intended silo.

Never change a setting id as part of reorganising. Settings are `theme_mods`
keyed by setting id alone, so moving a section between panels or retitling it
is free, but renaming a setting orphans the value a user already saved.
Section ids stay fixed for the same reason plus one more: several families
build their setting keys from the same variable they use as the section id
(`vance_gi_cond_*`, `vance_kb_cat_*`, `vance_dash_feature_*`,
`vance_cat_promo_sec_*`), and `vance_page_hero_spotlight_config()` names
sections and panels by id too.

The apply step also removes any `vance_`-prefixed section left with no
controls, and any `vance_`-prefixed panel left with no sections. That's how
the old "Hero Overlays" panel disappears once its three overlay sliders move
into the hero sections they darken.

Hero section naming follows one rule everywhere: "Hero (classic) + design
switch" comes before "Hero (spotlight)", in that order. The classic section is
the one holding the design toggle, the same section named as `style_section`
in that page's spotlight config.

Backing the whole thing out is deleting the one `require` line in
`functions.php`.

**Tests:** `php tests/customizer-structure.test.php` (plain PHP, no WordPress
required). It includes a source-level guard that fails if the map names a
section id that none of the registration files actually produce, because
`apply()` silently skips an id it can't find, so a typo here fails quietly in
the admin and needs a static check to catch it instead.

**Verifying against the live tree without deploying:** the Customizer manager
can be built under WP-CLI's `eval-file`, but the theme only defines its custom
control classes when `WP_Customize_Control` exists at theme load time, so the
`eval-file` run needs a `--require` prelude that loads
`wp-includes/class-wp-customize-{setting,panel,section,control}.php` on
`plugins_loaded`, and core's `register_controls()` has to be called by hand
before firing `do_action('customize_register')`. This is a known trap, not a
recipe: the scripts that did this were session scratch files, not anything
kept in the repo.

## Local-only helpers (in `LOCAL/`, gitignored)
- `vance_rebrand.py` — text rebrand transformer (round 1)
- `vance_color_swap.py` — orange → teal palette swap
- `vance_ai_rename.py` — chatbot VanceAI → AI rename (round 2)
- `check*.py`, `sanitize_php.py`, `recover.py`, `update_customizer.py` — PHP lint/sanity helpers

These are kept on disk but never committed or deployed. Re-run them on the React source trees in `temp_calc/` and `temp_malnutrition_calc/` before rebuilding the tool bundles (see constraint 7 above).
