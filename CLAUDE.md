# CLAUDE.md — Vance HealthHub WP

**Live site:** https://vancehealthhub.co.uk
  (the legacy `https://gastrohealthhub.com` Hostinger account is dormant — TLS errors;
   `https://www.vancehealthhub.com` is a frameset wrapper pointing at the `.co.uk`
   and is NOT a hosting target.)
**Repo:** https://github.com/slapharma/VANCE-Hub-WP (this one)
**Theme path (in repo):** `wp-content/themes/sla-health-hub/`
**Theme path (on server):** `~/domains/vancehealthhub.co.uk/public_html/wp-content/themes/sla-health-hub/`
  (renamed from `~/domains/gastrohealthhub.com/...` during the domain swap — all earlier
   handover commands referencing the old path must be rewritten.)
**Host:** Hostinger, SSH `u767439438@82.29.185.3` port 65002, key `~/.ssh/hostinger_sla`
**Other domains on the same Hostinger account:** `vancemedical.co.uk`, `ibdhealthhub.com` (do not touch).

This repo consolidates and replaces the earlier `.gemini/SLAHealthHub` working tree and the archived `slapharma/SLAHealthHub` GitHub repo. The authoritative context for every decision below is [REBRAND-HANDOVER.md](REBRAND-HANDOVER.md) — **read that first** before any non-trivial work.

---

## Load-bearing constraints — break any of these and the site silently breaks

### 1. DO NOT rename the theme folder `sla-health-hub`
Users never see it. Renaming requires a coordinated 5-step sequence: folder rename on disk + server, update `wp_options.template` and `wp_options.stylesheet`, update Text Domain in `style.css`, update ~169 `esc_html__(..., 'sla-health-hub')` call sites, re-activate the theme. See [TODO-RENAME.md](TODO-RENAME.md).

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

### 5. Cross-file contracts — rename both sides in the same commit
| Contract | Server | Client |
|---|---|---|
| AJAX action names | `wp_ajax_vance_*` | `action: 'vance_*'` in fetch/ajax calls |
| Nonces | `check_ajax_referer('vance_dashboard_nonce', 'nonce')` | `wp_create_nonce('vance_dashboard_nonce')` |
| REST routes | `register_rest_route('vance-health/v1', ...)` | `fetch('/wp-json/vance-health/v1/ai-chat', ...)` |
| postMessage types | `if (e.data.type === 'VANCE_SAVE_MALNUTRITION_RESULT')` | `parent.postMessage({ type: 'VANCE_SAVE_MALNUTRITION_RESULT', ... })` |

### 6. Minified JS tool bundles are text-rewritten in place
`assets/tools/ai-widget/index-*.js`, `assets/tools/blood-test/index-*.js`, `assets/tools/malnutrition-calculator/index-*.js` are Vite build artifacts from the (separate) `temp_calc/` and `temp_malnutrition_calc/` React sources. The rebrand strings/colors/message types were patched directly into the bundles. If anyone rebuilds from source, the brand will revert — re-run the `LOCAL/vance_*.py` transformers on the source before build, or port the substitution lists into the build step.

---

## CRITICAL outstanding work — do these first

### Security: exposed OpenRouter API key
`inc/dashboard-functions.php:682-684` contains a split-string OpenRouter API key (committed to public GitHub, deployed live). The split doesn't hide it. Action:
1. Revoke at https://openrouter.ai/keys
2. Generate replacement
3. Swap to `vance_get_theme_mod('vance_askai_api_key', '')` (the customizer setting already exists in `functions.php`)
4. Add guard for unset key
5. Enter new key via WP admin → Appearance → Customize → Ask AI Configuration

See [REBRAND-HANDOVER.md §6.1](REBRAND-HANDOVER.md) for full steps.

### DB cleanup (requires wp-cli on server)
- `siteurl` and `home` are `http://` — should be `https://` (§6.2)
- Body text, post meta, customizer serialised values still contain `slahealth.co.uk` / `vancemedical.co.uk` — run targeted `wp search-replace` per (§6.3)
- `_sla_*` meta keys — **do NOT** search-replace these

### WP admin: bind the Turn Evidence page template
Create a WP Page titled `Turn Evidence into Action`, slug `turn-evidence-into-action`, empty content. Template defaults render all copy (§6.4).

### Customizer controls missing for new evidence page
`page-turn-evidence-into-action.php` reads ~20 `vance_evidence_*` theme mods but the controls aren't registered in `customizer-pages.php`. Page works with defaults; admin cannot edit copy until registered (§6.5).

---

## Deploy workflow

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
   THEME=~/domains/vancehealthhub.co.uk/public_html/wp-content/themes/sla-health-hub; \
   cd \"\$THEME\" && \
   tar czf \"\$THEME/../sla-health-hub-pre-deploy-${TSTAMP}.tar.gz\" . && \
   tar xzf - && \
   echo 'DEPLOY_OK'"
```

After deploy: purge Hostinger cache (hPanel → Cache Manager → Purge All), LiteSpeed plugin cache if installed, and bump the `wp_enqueue_style` version string in `functions.php` if CSS changed.

Full deploy/rollback commands and the three-layer cache order are in [REBRAND-HANDOVER.md §5](REBRAND-HANDOVER.md).

---

## Smoke tests after any deploy
- Front page loads with teal primary (`#008080`)
- Header logo ~25% larger than stock (225px desktop)
- `/ask-ai/` page heading reads "Ask AI" and chat sends/receives (REST route + API key)
- `/turn-evidence-into-action/` renders the four evidence pillars
- Dashboard → Profile edit saves (AJAX nonce + `_sla_*` meta round-trip)
- Malnutrition calculator completes (postMessage contract)
- Footer links go to `https://gastrohealthhub.com/...`
- WP Customizer opens and saves cleanly

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
│   └── themes/
│       └── sla-health-hub/   ← the actual WordPress theme
└── LOCAL/                    ← gitignored, one-shot transformer scripts
```

## Local-only helpers (in `LOCAL/`, gitignored)
- `vance_rebrand.py` — text rebrand transformer (round 1)
- `vance_color_swap.py` — orange → teal palette swap
- `vance_ai_rename.py` — chatbot VanceAI → AI rename (round 2)
- `check*.py`, `sanitize_php.py`, `recover.py`, `update_customizer.py` — PHP lint/sanity helpers

These are kept on disk but never committed or deployed. Re-run them on the React source trees in `temp_calc/` and `temp_malnutrition_calc/` before rebuilding the tool bundles (see constraint 6 above).
