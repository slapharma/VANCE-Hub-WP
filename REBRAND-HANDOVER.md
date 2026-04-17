# Vance Medical Rebrand — Handover

**Project:** SLA Health WordPress theme → Vance Medical Foods Ltd rebrand
**Live site:** https://gastrohealthhub.com (formerly slahealth.co.uk)
**Repo:** https://github.com/slapharma/SLAHealthHub (now redirecting to `x.Archive-SLAHealthHub` — archived on GitHub)
**Hosting:** Hostinger, SSH `u767439438@82.29.185.3` port 65002, key at `~/.ssh/hostinger_sla`
**Theme directory name (unchanged):** `wp-content/themes/sla-health-hub/` — see note under *Architecture Decisions* below.
**Last deploy:** 2026-04-17 14:26 UTC, commit `1ab0101`
**Handover date:** 2026-04-17

This document captures every change made, every decision and why, everything deliberately left undone, and what a fresh developer/agent needs to know to continue work without breaking anything.

---

## 1. Summary of work completed

| Round | What changed | Commit |
|---|---|---|
| 1 | Text/identifier rebrand: brand names, domain, AI bot name, AJAX hooks, REST routes, nonces, user-visible copy. Teal palette swap (orange #FF5A00 → teal #008080). Header logo 25% larger. | `08500e2` |
| 2 | New page template `page-turn-evidence-into-action.php`. AI chatbot displayed name changed VanceAI → AI (30 replacements in 6 files). | `5eb1315` |
| 3 | Domain swap `vancemedical.co.uk` → `gastrohealthhub.com` (26 replacements in 9 files). Deploy path updated — Hostinger renamed the server directory when the domain was changed. | `1ab0101` |

All three commits are pushed to `origin/main` and deployed live.

---

## 2. Brand substitution map (locked — do not change)

These mappings were established by user decision during the rebrand. They are load-bearing for any future edits:

| From | To | Notes |
|---|---|---|
| `SLA Health Ltd` | `Vance Medical Foods Ltd` | Legal entity rebrand |
| `SLA Health` | `Vance Medical` | Trading name |
| `SLA Health Hub` | `Vance Medical Hub` | Theme name |
| `SLAHealthHub` | `VanceMedicalHub` | CamelCase refs |
| `SLAi` | `AI` | Chatbot displayed name. **Originally** renamed to `VanceAI` then later to `AI`. |
| `SLA Pharma` | **UNCHANGED** | Kept as-is. User decision: SLA Pharma is a distinct parent pharma entity that did NOT rebrand. Vance Medical Foods Ltd is a separate trading entity. |
| `SLA Pharma UK Ltd` (address) | `Vance Medical Foods Ltd` at same address | Legal address in Contact page updated to the new trading entity |
| `slahealth.co.uk` | `gastrohealthhub.com` | Canonical public domain (was briefly `vancemedical.co.uk` in rounds 1–2) |
| `info@vancemedical.co.uk` | `info@gastrohealthhub.com` | Contact email |
| Palette: `#FF5A00` orange family | `#008080` teal family | See section 4 for the full palette |
| Logo `.site-logo` width | 180px → 225px desktop, 140px → 175px mobile | +25% |

### What was deliberately NOT renamed (and why)

| Item | Reason |
|---|---|
| Theme folder `sla-health-hub/` | WordPress identifies the theme by folder name AND by the `Text Domain: sla-health-hub` header in `style.css`. Both are used by `esc_html__(..., 'sla-health-hub')` across ~169 call sites and by any `.mo/.po` translation files. Renaming requires a coordinated filesystem move + update of `wp_options.template` and `wp_options.stylesheet` + re-activating the theme. Low-value, high-risk. Deferred. |
| `_sla_*` user/post meta keys (92 refs) | Every user's profile image, bookmarks, notes, saved searches, quiz results, calculator history, uploaded posters, and clinical profile lives under `_sla_*` meta keys in `wp_usermeta` and `wp_postmeta`. Renaming the keys would orphan all of it. Instead, form field names are translated back to `_sla_*` on write (see `inc/dashboard-functions.php:47–62`). |
| Internal DOM IDs / CSS classes / CSS custom properties (`#vance-ai-chat-input`, `.ask-vance-ai-side`, `--vance-ai-*`) | Invisible to end users. Cross-referenced by JS and PHP — any rename must be coordinated or features break. Kept internally consistent. |
| GitHub org name `slapharma` | User decision: SLA Pharma remains as parent entity. |

---

## 3. Architecture decisions — non-obvious things the next dev must understand

### 3.1 `vance_get_theme_mod()` fallback helper

Defined at the top of `functions.php`. Wraps `get_theme_mod()` and reads the new `vance_*` customizer key first, falling back to the legacy `sla_*` key if the new one has never been saved. Every existing `sla_foo` customizer value in the live database (logos, URLs, copyright text, social links, etc.) keeps rendering until an admin re-saves each setting in the Customizer UI.

**Critical implementation detail:** the legacy prefix is assembled character-by-character (`implode('', array('s','l','a','_'))`) on purpose. A naive bulk text-replace pass (as happened during round 1) would otherwise rewrite the literal `'sla_'` string inside this function and silently break the fallback — turning every customizer read into a no-op that returns the default. If you ever run another prefix sweep, leave this function alone.

### 3.2 Cross-file contracts that break if one side changes

These are the places where a rename MUST happen on both sides in the same commit, or features silently break in production:

| Contract | Server side | Client side |
|---|---|---|
| AJAX action names | `add_action('wp_ajax_vance_*', ...)` in `inc/dashboard-functions.php` + `functions.php` | `action: 'vance_*'` in `$.post(...)` / `fetch(...)` calls in `page-dashboard.php`, `single.php`, `inc/quiz-modal.php`, `page-healthcare-quiz.php` |
| Nonces | `wp_verify_nonce(..., 'vance_dashboard_nonce')` / `check_ajax_referer('vance_dashboard_nonce', 'nonce')` | `wp_create_nonce('vance_dashboard_nonce')` — must match verifier exactly |
| REST routes | `register_rest_route('vance-health/v1', ...)` in `inc/dashboard-functions.php` | `fetch('/wp-json/vance-health/v1/ai-chat', ...)` in `page-ask-ai.php` and `front-page.php` |
| postMessage types | `if (e.data.type === 'VANCE_SAVE_MALNUTRITION_RESULT')` in `page-dashboard.php` | `parent.postMessage({ type: 'VANCE_SAVE_MALNUTRITION_RESULT', ... })` in `assets/tools/malnutrition-calculator/index.html` |
| User meta key preservation | `update_user_meta($user_id, '_sla_foo', ...)` with `_sla_*` prefix | Form inputs submit as `name="vance_foo"`; handler translates prefix back to `_sla_*` on write |

If you rename any of these, grep the codebase for every occurrence in the same commit.

### 3.3 Minified JS tool bundles

`assets/tools/ai-widget/index-Bhrt5h83.js`, `assets/tools/blood-test/index-C2j5FWyV.js`, and `assets/tools/malnutrition-calculator/index-C2j5FWyV.js` are **minified build artifacts** from a separate React/Vite source tree (under `../../../temp_calc/` and `../../../temp_malnutrition_calc/` in the dev machine). They were text-rewritten in place during the rebrand (brand strings, color hex codes, postMessage types).

If anyone rebuilds these bundles from source, the source will still contain the old brand/colors/message types. Re-run `vance_rebrand.py`, `vance_color_swap.py`, and `vance_ai_rename.py` on the source tree before building, OR port the substitution lists into the source directly.

### 3.4 Color palette is baked in literal hex as well as CSS variables

Orange #FF5A00 wasn't only defined in `:root` — 109 raw `#FF5A00` hex values were scattered across 23 files in inline `style="..."` attributes. A single CSS custom property change would have only fixed maybe 30% of the orange. Round 1 of the rebrand did a bulk hex substitution to fix this.

Current palette:

```css
--primary-color:   #008080;   /* Vance teal */
--primary-hover:   #006666;   /* derived darker teal (palette had no hover stop) */
--primary-light:   #78bfbf;
--primary-pale:    #aedbdb;
--primary-wash:    #def4f4;   /* chip/pill/badge backgrounds */
--secondary-color: #0A1929;   /* navy — retained; pairs with teal */
--accent-color:    #F3F4F6;
```

When anyone adds new components, **use the CSS variables** (`var(--primary-color)`) not the literal hex. Future theme-color work will then be a single file edit instead of another 100+-occurrence grep.

### 3.5 Cache-bust via wp_enqueue version

`functions.php` passes `'2.0.0-vance'` as the version arg to `wp_enqueue_style('vance-main-style', ...)`. WordPress appends `?ver=2.0.0-vance` to the CSS URL so browsers re-fetch after a deploy. **Bump this string on every meaningful CSS change** (or switch to a content-hash-based approach post-rebrand) or visitors will see stale CSS for up to a week depending on CDN TTLs.

---

## 4. File inventory

### Files created in the rebrand

| File | Purpose |
|---|---|
| `page-turn-evidence-into-action.php` | New landing page template: hero, 4 evidence pillars, Synthesise→Translate→Apply process, dynamic Featured Evidence WP_Query, CTA. All copy via `vance_get_theme_mod('vance_evidence_*', ...)`. Bound to WP Pages with slug `turn-evidence-into-action`, OR selectable via Template Name dropdown. |
| `vance-debug.php` | Renamed from `sla-debug.php`. Debug script for verifying PHP works on the server. `REMOVE AFTER USE` noted in header. Safe to delete. |

### Files deliberately excluded from production deploys

These live in the theme source tree but must NOT ship to Hostinger:

| File | Why excluded |
|---|---|
| `vance_rebrand.py` | One-shot transformer script for the round-1 text rebrand. No production use. |
| `vance_color_swap.py` | One-shot transformer for the teal palette swap. |
| `vance_ai_rename.py` | One-shot transformer for the VanceAI → AI rename. |
| `functions.php.bak`, `functions.php.bak2` | Pre-rebrand backups. |
| `front-page-original.php` | Pre-rebrand backup of front-page. |
| `inc/dashboard-functions-backup.txt` | Pre-rebrand backup. |
| `.claude/` | Claude Code harness state — contains SSH commands referencing live paths. |
| `screenshot.png`, `Documents - Shortcut.lnk`, `check.py`, `debug_quote.py` | Dev-machine artifacts. |

The tar-over-ssh deploy pattern (section 5) excludes all of these.

### Files that survived rebrand but contain legacy-prefixed identifiers (intentional)

| File | Legacy identifier retained | Reason |
|---|---|---|
| `style.css` | `Text Domain: sla-health-hub` | WP translation coupling |
| `functions.php`, `customizer-pages.php`, all `page-*.php` | `esc_html__('...', 'sla-health-hub')` — ~169 sites | Text domain must match folder name until coordinated rename |
| `inc/dashboard-functions.php`, `functions.php` | `update_user_meta(..., '_sla_foo', ...)` — 92 sites | Preserves existing user data in `wp_usermeta` / `wp_postmeta` |
| `customizer-pages.php` and template reads | `sla_*` keys exist in DB; helper reads them as fallback | See `vance_get_theme_mod()` in section 3.1 |

---

## 5. Deploy workflow

### Server layout (Hostinger)

```
~/domains/
  gastrohealthhub.com/
    public_html/                         ← WordPress root
      wp-config.php                      ← DB credentials (DB_NAME: u767439438_AE7nx)
      wp-content/
        themes/
          sla-health-hub/                ← THIS theme (folder name unchanged)
          sla-health-hub-pre-*.tar.gz    ← rollback backups created on each deploy
  ibdhealthhub.com/                      ← second WP install, DB u767439438_8P8HX
                                         ← UNRELATED to this theme, do not touch
```

**Note:** the old `~/domains/slahealth.co.uk/` directory is gone — Hostinger renamed it automatically when the primary domain was changed. Any deploy script pointing at `domains/slahealth.co.uk/` will 404.

### Deploy command (tar-over-ssh — `rsync` unavailable on Windows Git Bash)

From `wp-content/themes/sla-health-hub/`:

```bash
TSTAMP=$(date +%Y-%m-%d-%H%M) && \
tar czf - \
  --exclude='./.git' --exclude='./.claude' \
  --exclude='./vance_rebrand.py' --exclude='./vance_color_swap.py' --exclude='./vance_ai_rename.py' \
  --exclude='./functions.php.bak' --exclude='./functions.php.bak2' \
  --exclude='./front-page-original.php' --exclude='./inc/dashboard-functions-backup.txt' \
  --exclude='./screenshot.png' --exclude='./Documents - Shortcut.lnk' \
  --exclude='./check.py' --exclude='./debug_quote.py' \
  . | \
ssh -i ~/.ssh/hostinger_sla -p 65002 u767439438@82.29.185.3 \
  "set -e; \
   THEME=~/domains/gastrohealthhub.com/public_html/wp-content/themes/sla-health-hub; \
   cd \"\$THEME\" && \
   tar czf \"\$THEME/../sla-health-hub-pre-deploy-${TSTAMP}.tar.gz\" . && \
   tar xzf - && \
   echo 'DEPLOY_OK'"
```

After deploy:
1. Hostinger hPanel → Cache Manager → **Purge All** (server-level cache)
2. WP admin → LiteSpeed Cache → Toolbox → Purge All (plugin cache, if installed)
3. Browser hard-refresh (`Ctrl+F5`) or use the `?ver=N.N.N` cache-bust in `functions.php` by bumping the version string

### Rollback command

```bash
# Replace YYYY-MM-DD-HHMM with the timestamp of the backup you want
ssh -i ~/.ssh/hostinger_sla -p 65002 u767439438@82.29.185.3 \
  "cd ~/domains/gastrohealthhub.com/public_html/wp-content/themes/ && \
   rm -rf sla-health-hub && mkdir sla-health-hub && cd sla-health-hub && \
   tar xzf ../sla-health-hub-pre-deploy-YYYY-MM-DD-HHMM.tar.gz"
```

Existing backups on server (most recent first):
- `sla-health-hub-pre-domain-2026-04-17-1426.tar.gz` — before domain swap (round 3)
- `sla-health-hub-pre-evidence-2026-04-17-1409.tar.gz` — before round 2
- `sla-health-hub-pre-vance-2026-04-17-1328.tar.gz` — before round 1 (oldest state)

### Git push (GitHub)

Remote is HTTPS — `https://github.com/slapharma/SLAHealthHub.git`. Credentials are cached on the dev machine (Windows credential manager). The repo has been archived on GitHub's side and redirects to `slapharma/x.Archive-SLAHealthHub`; pushes still work through the redirect but emit a warning every time. Consider moving to a fresh `slapharma/GastroHealthHub` repo when time permits.

---

## 6. Outstanding work

Ordered by severity.

### 6.1 CRITICAL — OpenRouter API key exposed in source

**Location:** `inc/dashboard-functions.php:682–684`

```php
$key_part_1 = 'sk-or-v1-';
$key_part_2 = '7278a92f426edb19fbffe0bb4c71878e15e0da1c934b73e8177df6a66f8afb60';
$api_key = $key_part_1 . $key_part_2;
```

The "split into two variables" comment claims this is to dodge WAF 409 blocks — it doesn't hide the key from anyone reading the file. The key is:
- Committed in git (public GitHub) since at least commit `b91f44e` (pre-rebrand)
- Deployed to the live web server
- Billable on OpenRouter — whoever extracts it can incur charges

**Fix steps (10 minutes):**
1. Revoke the key at https://openrouter.ai/keys
2. Generate a replacement
3. Add a Customizer setting (`vance_askai_api_key` already registered in `functions.php`; a quick check shows the control exists but the code at 682-684 ignores it)
4. Replace lines 682–684 with `$api_key = vance_get_theme_mod('vance_askai_api_key', '');` plus a guard that returns early with a user-facing error if unset
5. Enter the new key in WP admin → Appearance → Customize → Ask AI Configuration → AI API Key
6. Purge git history of the old key (`git filter-repo` or BFG) or accept that it's burned and only the revocation matters — the latter is usually sufficient

### 6.2 HIGH — `siteurl` and `home` in WP database are `http://`

**Current state (verified 2026-04-17 14:26 UTC):**
```
wp_options.siteurl = http://gastrohealthhub.com
wp_options.home    = http://gastrohealthhub.com
```

If gastrohealthhub.com has an SSL cert (Hostinger usually provisions one automatically), visitors land on HTTPS but WordPress generates `http://` canonical links, stylesheet URLs, and image URLs. Modern browsers block mixed content, so pages may render half-styled or generate console warnings.

**Fix (one command, 5 seconds):**
```bash
curl -sSI https://gastrohealthhub.com | head -3   # confirm the cert is live
ssh -i ~/.ssh/hostinger_sla -p 65002 u767439438@82.29.185.3 \
  "cd ~/domains/gastrohealthhub.com/public_html && \
   wp option update siteurl 'https://gastrohealthhub.com' && \
   wp option update home    'https://gastrohealthhub.com'"
```

### 6.3 HIGH — Database text still contains legacy brand URLs

Hostinger's domain-change tool updated `siteurl` and `home` but did NOT do a full text search-replace. Any of these may still contain `slahealth.co.uk` or `vancemedical.co.uk`:
- `wp_posts.post_content` — editor-authored body text
- `wp_posts.post_excerpt`, `wp_posts.guid`
- `wp_postmeta` — custom fields, SEO meta
- `wp_options` — widget settings, plugin options, `theme_mods_sla-health-hub` serialised values (logos, URLs the admin saved in the Customizer)
- `wp_termmeta`, `wp_usermeta`
- Menu items with absolute URLs

**Fix — run these on the live server (dry-run first, always):**
```bash
ssh -i ~/.ssh/hostinger_sla -p 65002 u767439438@82.29.185.3 \
  "cd ~/domains/gastrohealthhub.com/public_html && \
   wp search-replace 'slahealth.co.uk'    'gastrohealthhub.com' --all-tables --precise --dry-run && \
   wp search-replace 'vancemedical.co.uk' 'gastrohealthhub.com' --all-tables --precise --dry-run"
# Remove --dry-run once preview looks correct.
# --precise handles PHP-serialised strings (theme_mods, widget data) correctly.
```

Brand text search-replace should also cover the post body copy that's still talking about "SLA Health":
```bash
wp search-replace 'SLA Health Ltd' 'Vance Medical Foods Ltd' --all-tables --precise --dry-run
wp search-replace 'SLA Health'     'Vance Medical'           --all-tables --precise --dry-run
# DO NOT run a bare 'SLA' → 'Vance' search-replace on the DB.
# It would clobber 'SLA Pharma' (the parent pharma entity we kept).
```

### 6.4 MEDIUM — WP Page for the Turn Evidence into Action template

The page template (`page-turn-evidence-into-action.php`) is deployed. WordPress still needs a Page object to bind it to a URL:

1. WP admin → Pages → Add New
2. Title: `Turn Evidence into Action`
3. Check the slug is `turn-evidence-into-action` (should auto-generate)
4. Publish with empty content. The template renders all copy from `vance_get_theme_mod('vance_evidence_*', 'default...')` defaults.
5. Visit `https://gastrohealthhub.com/turn-evidence-into-action/` to confirm.

### 6.5 MEDIUM — Customizer controls for the new evidence page

The `page-turn-evidence-into-action.php` template reads ~20 customizer keys (`vance_evidence_hero_title`, `vance_evidence_pillar{1-4}_title/desc`, `vance_evidence_proc{1-3}_title/desc`, `vance_evidence_cta_*`, etc.) but the controls for editing these in WP admin are NOT registered in `customizer-pages.php`. The page renders fine with defaults — but the admin cannot edit the copy without editing the template file.

**Fix:** add a section `vance_evidence_section` in `customizer-pages.php` following the same pattern as `vance_hcp_section`. ~60–80 lines of `add_setting` / `add_control` calls.

### 6.6 LOW — Theme folder rename

`wp-content/themes/sla-health-hub/` is still the folder name. Only cosmetic — end users never see it — but a future agent may want to rename for consistency. Coordinated sequence:
1. Rename folder on disk AND server
2. Update `wp_options.template` and `wp_options.stylesheet` to new name
3. Update `Text Domain:` header in `style.css`
4. Update every `esc_html__(..., 'sla-health-hub')` call across ~169 sites
5. Re-activate the theme (WP admin → Appearance → Themes)

Don't attempt piecemeal — all five must happen in one deploy window.

### 6.7 LOW — GitHub repo naming

`slapharma/SLAHealthHub` is archived (redirects to `x.Archive-SLAHealthHub`). Consider creating `slapharma/GastroHealthHub` as a fresh repo, re-pointing the `origin` remote, and pushing the history. Not blocking anything; just tidy.

### 6.8 LOW — Parent-directory zip backups still staged in git status

The git root (`C:\Users\clift\.gemini\SLAHealthHub\`) has ~26 `sla-health-hub (N).zip` files showing as deleted in `git status`. These are old theme snapshots. A separate cleanup commit should `git rm` them to tidy the working tree. They're unrelated to any rebrand work.

### 6.9 LOW — Dev scripts left in theme directory

`vance_rebrand.py`, `vance_color_swap.py`, `vance_ai_rename.py` are still on disk. They're excluded from production deploys but remain in the working tree. Once you've verified nothing needs another sweep, delete all three:
```bash
cd wp-content/themes/sla-health-hub/
rm vance_rebrand.py vance_color_swap.py vance_ai_rename.py
```

---

## 7. Known gotchas / landmines

### 7.1 Never run `SLA` → `Vance` as a bare DB search-replace

It will rewrite `SLA Pharma` (the parent pharma entity we explicitly retained) and `translateX` substrings inside CSS values. Always use the full-phrase substitutions: `SLA Health Ltd` → `Vance Medical Foods Ltd`, `SLA Health` → `Vance Medical`, etc. The regex `\bSLA\b` with word boundaries is the only safe standalone pattern.

### 7.2 Don't rename `vance_get_theme_mod()`

The function's body contains the literal prefix `'sla_'` (assembled via `implode`) for the legacy fallback. A careless rename pass could break the fallback without any visible symptom — customizer reads silently return defaults even though the stored data is still there. If you rename the function, keep the `implode(..., array('s','l','a','_'))` trick.

### 7.3 `LF will be replaced by CRLF` warnings during git operations

Harmless. Windows Git normalises line endings on commit. Every file we've touched triggers it because the dev machine is Windows and the server/linter expects LF. If you want it quiet, configure `.gitattributes` with `* text=auto eol=lf` and run `git add --renormalize .`.

### 7.4 grep -c under `set -e` in deploy scripts

Using `grep -c pattern` as a "should not exist" check under `set -e` will abort the shell because grep returns exit code 1 when there are zero matches. The deploy script handles this by piping through `wc -l` or by using `|| true`. Watch for this pattern if you write your own verification steps.

### 7.5 The minified JS in `assets/tools/*/index-*.js`

Text-rewritten in place. If the build pipeline rebuilds these from the `temp_calc/` or `temp_malnutrition_calc/` React sources, all the rebrand changes in the bundles will be blown away. Either re-run the transformer scripts on the sources before build, or integrate the substitutions into the build step.

### 7.6 Hostinger's three cache layers

Order of cache to purge when a deploy doesn't appear to take effect:
1. Browser (`Ctrl+F5` or incognito)
2. WP plugin cache (LiteSpeed Cache if installed)
3. Hostinger hPanel → Cache Manager → Purge All (server-level)
4. CDN / Cloudflare edge if configured
5. Increment the `wp_enqueue_style` version string in `functions.php` for permanent defeat

### 7.7 Two WordPress sites on the same Hostinger account

`~/domains/gastrohealthhub.com/` and `~/domains/ibdhealthhub.com/` share the SSH account. Different databases. Any `wp-cli` command must be run from the correct `public_html` directory, and any search-replace that operates `--all-tables` targets one site. Double-check the `cd` before any destructive operation.

### 7.8 Customizer stored values have migration cost

Every `vance_*` customizer key defaults to reading through `vance_get_theme_mod()` which falls back to the legacy `sla_*` key. The first time an admin clicks Publish in the Customizer, WordPress saves the current merged value under the `vance_*` key and starts using that going forward. If you bulk-rewrite the customizer keys again in the future, preserve this fallback pattern or users will lose every customisation (logos, contact email, social URLs, etc.).

---

## 8. Smoke tests for any future deploy

After deploying, verify these still work — they have cross-file contracts that can silently break:

| Test | What it exercises |
|---|---|
| Front page loads with teal primary | CSS variable + hex literal swap worked |
| Header logo is visibly larger than a stock WP theme | `.site-logo { width: 225px }` |
| `/ask-ai/` page heading reads "Ask AI" | AI bot rename round 2 |
| Chat in `/ask-ai/` sends/receives | REST route `vance-health/v1/ai-chat` + OpenRouter API key |
| `/turn-evidence-into-action/` renders the evidence pillars | Page template bound, template defaults rendering |
| Dashboard → Profile → edit and save | AJAX `wp_ajax_vance_save_profile` hook + nonce + `_sla_*` meta key translation |
| Malnutrition calculator → complete a calc | `VANCE_SAVE_MALNUTRITION_RESULT` postMessage contract (both halves) |
| Footer legal links go to `https://gastrohealthhub.com/...` | Domain swap round 3 |
| Admin Customizer opens, saves cleanly | `vance_*` customizer registrations in `customizer-pages.php` + `functions.php` |

---

## 9. Commit log for the rebrand

```
1ab0101  Switch canonical domain: vancemedical.co.uk -> gastrohealthhub.com
5eb1315  Add Turn Evidence into Action template + rename chatbot VanceAI -> AI
08500e2  Rebrand SLA Health -> Vance Medical (copy + teal palette + larger logo)
b91f44e  SLAHealthHub _ WP-Template  (pre-rebrand baseline)
```

All pushed to `origin/main`. A clean `git log b91f44e..HEAD` shows exactly what the rebrand touched.

---

## 10. Contacts and credentials (for the human accepting handover)

| Item | Where to find |
|---|---|
| Hostinger hPanel login | Whoever administers the slapharma Hostinger account |
| SSH key for deploys | `~/.ssh/hostinger_sla` on the dev machine; revoke and rotate if dev machine is retired |
| GitHub access | `slapharma` org on github.com — repo is archived and redirects |
| WordPress admin for gastrohealthhub.com | Via WP admin at `https://gastrohealthhub.com/wp-admin/` |
| OpenRouter account | **Revoke the exposed key first.** Account is presumably held by Vance Medical / SLA Pharma — contact the account holder. |

End of handover.
