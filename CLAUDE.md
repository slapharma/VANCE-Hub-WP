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
3. **Site Kit was never fully authorised.** Search Console and GA4 are both
   configured — property IDs present, gtag firing, data collecting since June —
   but the OAuth grant is missing the read scopes, so `get_data()` returns
   `missing_required_scopes` and nothing reaches WordPress. Nobody has been seeing
   this data in the dashboard they would naturally look at. Re-grant in Site Kit.
   Until then there is no measured basis for prioritising anything.
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
- Every **category archive** shows the light spotlight hero (`inc/category-hero.php`):
  eyebrow pill, teal headline, and a white band of live facts — Articles / Topics /
  Last added. The numbers are computed per request, so a wrong one means the query
  changed, not that a value went stale. Check a top-level section
  (`/category/content-gastro-living/`) AND a sub-category
  (`/category/food-nutrition/`): the sub-category must show a breadcrumb, inherit the
  parent's photograph, and NOT repeat the parent's name in the pill.
- `/gastro-health-explained/` and all seven condition pages show the light spotlight hero
  (`inc/gi-hero.php`): purple eyebrow, teal CTA, and the lobby's seven condition chips on
  **two** rows. Three rows means the copy column narrowed — check, don't "fix" the split.
- Dashboard → Profile edit saves (AJAX nonce + `_sla_*` meta round-trip)
- Malnutrition calculator completes (postMessage contract)
- WP Customizer opens and saves cleanly
- **Menu arrows render as chevrons**, not tofu boxes — Dashicons is dequeued for
  visitors and the arrows are drawn in CSS. Three visible indicators on desktop.
- **Article DOIs are links.** `wp vance citations` should report 595 ok and exit 0.
- **Articles show their condition chips** under the copy (`va-article-conditions`)
  and carry `about` → `MedicalCondition` in the schema. 143 of 149 do; six general
  pieces deliberately show nothing.
- **Every image has a real alt.** The homepage should report 27 images, 27
  described, none empty. If card thumbnails come back empty, a template is
  discarding `_wp_attachment_image_alt` again — see `inc/thumbnail-alt.php`.
- **`og:image` is present on every page**, falling back to
  `assets/img/og-default.jpg` where AIOSEO has none.

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

## Local-only helpers (in `LOCAL/`, gitignored)
- `vance_rebrand.py` — text rebrand transformer (round 1)
- `vance_color_swap.py` — orange → teal palette swap
- `vance_ai_rename.py` — chatbot VanceAI → AI rename (round 2)
- `check*.py`, `sanitize_php.py`, `recover.py`, `update_customizer.py` — PHP lint/sanity helpers

These are kept on disk but never committed or deployed. Re-run them on the React source trees in `temp_calc/` and `temp_malnutrition_calc/` before rebuilding the tool bundles (see constraint 7 above).
