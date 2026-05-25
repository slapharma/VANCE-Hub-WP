# Reconciliation verification — 2026-05-25

Companion to `FORENSICS-REPORT.md` in this folder. Records every check run after
the recovery commit landed, so the work can be audited later.

## Git outcome

- **Reconciliation commit:** `6a7fd31` "feat(theme): commit May 12-13 customizer
  work + 2026-05-25 reconciliation" — 243 files changed, +4,890 / -438.
- **Recovery branch:** `recovery/2026-05-25-customizer-sync` pushed to GitHub
  with all 19 commits unique to it (1 reconciliation commit + 18 May-7 commits
  that had been sitting unpushed since 2026-05-07).
- **Local `main` HEAD = remote `recovery/2026-05-25-customizer-sync` HEAD:**
  `6a7fd31744223439a24e8843aca55bb05c5be9ad`.
- **Origin `main` is unchanged** (still at `1c3b808`). The user merges the
  recovery branch into `main` via PR when ready:
  https://github.com/slapharma/VANCE-Hub-WP/pull/new/recovery/2026-05-25-customizer-sync

## Load-bearing constraint checks (from CLAUDE.md §1-6)

| # | Constraint | Result |
|---|---|---|
| 1 | Theme folder still named `sla-health-hub` | OK |
| 2 | `_sla_*` meta keys: no key on live is missing from local (would orphan user data on redeploy) | OK — full-theme audit returned an empty diff |
| 3 | `vance_get_theme_mod` assembled prefix `implode('', array('s','l','a','_'))` still intact at functions.php:38 | OK — identical to live |
| 4 | `SLA Pharma` substrings not clobbered | OK — present in 3 templates as expected |
| 5 | AJAX action names stay `vance_*` (server) and nonce key `vance_dashboard_nonce` | OK — server-side handlers and nonce key both intact |
| 6 | Minified tool bundles (`ai-widget`, `blood-test`, `malnutrition-calculator`) retain Vance branding, no `SLA Health` leakage | OK on all three bundles |

## Syntactic checks (without a php interpreter in scope)

Brace balance and `<?php`/`?>` tag balance on the four most-edited PHP files
(php-cli not installed in either of the available shells, so this is a
structural sanity check, not a full parse):

| File | Brace open / close | `<?php` / `?>` |
|---|---|---|
| customizer-pages.php | 98 / 98 | 1 / 0 (file omits closing `?>` per WP convention) |
| functions.php | 415 / 415 | 91 / 90 (file omits trailing `?>` per WP convention) |
| inc/dashboard-functions.php | 154 / 154 | 1 / 0 (file omits trailing `?>` per WP convention) |
| front-page.php | 211 / 211 | 250 / 250 |

No imbalance.

## Three-way state after reconciliation

| Source | State now |
|---|---|
| Local working tree | Clean. All May 12-13 customizer additions committed at `6a7fd31`. |
| Local `main` | `6a7fd31`. 19 commits ahead of `origin/main`. |
| Remote `origin/main` | Unchanged — `1c3b808`. The user reviews+merges the recovery PR before this advances. |
| Remote `origin/recovery/2026-05-25-customizer-sync` | `6a7fd31`. Matches local. |
| Live (`vancehealthhub.co.uk`) | Still in the rolled-back state from 2026-05-25 12:43 GMT. **Not touched by this reconciliation.** |

## What this reconciliation deliberately did NOT do

- **Did not redeploy to live.** Per user direction, "push to a recovery branch first."
- **Did not merge to `main`.** The recovery branch is the staging point.
- **Did not address §6.1 security item** (exposed OpenRouter API key — still flagged in CLAUDE.md, separate workstream).
- **Did not address §6.2 / §6.3 database items** (http→https `siteurl`, leftover
  `slahealth.co.uk` strings — separate workstream requiring `wp search-replace`
  on the server).
- **Did not figure out who/what triggered the rollback.** Hostinger hPanel
  backup audit log is the next place to look.

## Next steps for the user (recommended order)

1. **Open the PR** for `recovery/2026-05-25-customizer-sync` and review the
   reconciliation commit at your pace.
2. **Re-run the pull script's [1/5] forensics block** by itself (with the v2
   base64 payload) to capture the **pre-deploy backup tarball list** server-side
   — that's the trail that should pinpoint *when* the rollback happened. Paste
   the output back when you have it.
3. **Decide redeploy strategy** for live:
   - *Cautious:* redeploy only `customizer-pages.php`, `functions.php`,
     `inc/dashboard-functions.php`, plus the page-templates and
     `assets/css/main.css`. Verify Customizer panels return, then redeploy
     the rest.
   - *Fast:* run the full tar-deploy in CLAUDE.md §"Deploy workflow" against
     the current local HEAD. (Note: the CLAUDE.md deploy block was patched in
     this commit to use the new `~/domains/vancehealthhub.co.uk/...` path.)
4. **Investigate hPanel backup audit log** to find the operator and timing of
   the 12:43 GMT rollback.
5. **Address the still-open security item** from CLAUDE.md §6.1 (OpenRouter
   key in source) before pushing to public `main`.
