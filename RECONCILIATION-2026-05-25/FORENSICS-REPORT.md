# Reconciliation forensics — 2026-05-25

Trigger: user reported "missing WP customizer tools for the last couple of weeks" and asked
for a three-way comparison across local, GitHub, and live (`www.vancehealthhub.com`).

## Headline

The **live site was rolled back** to a state predating most of the May 7 commits. Today
(2026-05-25 12:43 GMT) someone redeployed that older state. Local working tree still
contains all the original work plus ~3,839 lines of uncommitted customizer additions
from 2026-05-12 / 2026-05-13. GitHub was frozen at the initial security commit.

Nothing was lost on disk. The "missing" customizer tools are sitting in local commits
and uncommitted edits that simply haven't been pushed to GitHub or restored to the
live server.

## Three-way state at the moment of the comparison

| Source | What it has | Last-touch fingerprint |
|---|---|---|
| **Local working tree** | All 18 May-7 commits + ~3,839 lines uncommitted customizer additions (functions.php, customizer-pages.php) + 4 untracked theme files (page-terms-of-use.php, tpl-privacy-policy.php, assets/tools/ibd-recipes/, assets/tools/omega-3-calculator/) | functions.php mtime 2026-05-13 08:59 |
| **GitHub `origin/main`** | Frozen at commit `1c3b808` ("Security: move OpenRouter API key from source to Customizer"). 18 unpushed local commits ahead. | last push pre-May-7 |
| **Live (`vancehealthhub.co.uk` via Hostinger 82.29.185.3)** | A state that has *all* 145+ customizer settings, 18 customizer sections and 6 customizer panels REMOVED relative to local HEAD. All theme files share mtime `2026-05-25 12:43:26` — fingerprint of a bulk `tar xzf` restore. | redeploy 2026-05-25 12:43 GMT |

## Why I'm sure it's a rollback (not just stale)

1. **Set comparison of customizer settings** — `customizer-pages.php` on live registers 253
   `add_setting()` calls; in committed HEAD it registers 398. Live is a **subset** of HEAD
   (zero new settings exist on live that aren't in HEAD). Pure regression, not divergent edit.
2. **Whole panels are gone.** `vance_edu_panel`, `vance_evidence_panel`, `vance_hquiz_panel`,
   `vance_overlays_panel`, `vance_premium_panel`, `vance_tools_panel` — 6 entire admin
   panels that exist in HEAD do not exist on live.
3. **`inc/dashboard-functions.php`** on live is 843 lines; committed HEAD is 1,215 lines.
   Live is 372 lines behind, on a file the user-facing dashboard depends on.
4. **All theme files on live share the same mtime** — that only happens after a bulk
   archive extraction. Selective edits would leave staggered mtimes.

See `missing-from-live-settings.txt`, `missing-from-live-sections.txt`,
`missing-from-live-panels.txt` next to this file for the full lists.

## Why GitHub looked "missing customizer tools"

GitHub `origin/main` has been frozen at `1c3b808` since the initial security commit
(prior to May 7). The 18 May-7 customizer commits never reached GitHub, and the
May 12-13 customizer additions were never even committed locally. Anyone looking
at the GitHub UI would see no customizer work.

## Why session transcripts didn't help

Only 4 sessions remain in local transcript history, none WP-related (all Claude
Desktop / MCP diagnostics). The transcripts of the May 7-13 customizer work
have aged out. The git log + file mtimes serve as the authoritative timeline.

## How the user-facing domain split sits

| URL | What it actually is |
|---|---|
| `https://vancehealthhub.co.uk` | The real WordPress site. Hostinger, LiteSpeed, PHP 8.3.30. Theme path `~/domains/vancehealthhub.co.uk/public_html/wp-content/themes/sla-health-hub/`. |
| `https://www.vancehealthhub.com` | A frameset wrapper on AWS that loads `vancehealthhub.co.uk` inside a `<frame>`. Not a hosting target. |
| `https://gastrohealthhub.com` | Dormant — DNS still points at Hostinger `82.29.185.3` but TLS errors. The old hosting directory was renamed during the domain swap. |
| Other Hostinger domains on same account | `vancemedical.co.uk`, `ibdhealthhub.com` — do not touch. |

CLAUDE.md has been patched in this reconciliation commit to reflect the new server
path (the deploy block previously referenced `~/domains/gastrohealthhub.com/...`,
which no longer exists on the box).

## What this reconciliation commit contains

1. The May 12-13 customizer work, committed in one big commit as agreed:
   - 13 modified theme PHP files (customizer-pages.php, functions.php, front-page.php,
     footer.php, footer-dashboard.php, archive.php, header.php, index.php, plus
     page-* templates and create_icons.ps1)
   - 4 untracked theme additions (page-terms-of-use.php, tpl-privacy-policy.php,
     assets/tools/ibd-recipes/, assets/tools/omega-3-calculator/)
   - Deleted battleship-epa game (superseded by pacman-vance in the May 7 commits)
   - Rebrand-recolour of the icon SVGs and logo.png
   - assets/css/main.css bump for the new customizer-driven CSS variables
2. Updated docs/ markdown files
3. CLAUDE.md patched for the new server path + domain layout
4. .gitignore extended to exclude LIVE-PULL/, .playwright-mcp/, tasks/, and per-file
   `.bak-*` backups so future reconciliations don't accidentally commit them
5. This forensics report and the missing-from-live-*.txt evidence files

## What this reconciliation does NOT touch

- **The live server.** Per the user's "push to a recovery branch first" preference,
  nothing has been redeployed to Hostinger. The 12:43 GMT live state remains in place
  until the user explicitly chooses to redeploy.
- **GitHub `main`.** This work goes to `recovery/2026-05-25-customizer-sync` for the
  user to review and merge. The 18 backed-up May-7 commits ride along on the recovery
  branch and become reachable on GitHub once it is pushed.
- **Database.** The §6.1 / §6.2 / §6.3 CLAUDE.md cleanup items (exposed OpenRouter
  key, http→https siteurl, leftover `slahealth.co.uk` strings) are unchanged.

## Recommended next steps (after this commit is pushed)

1. **Diff `LIVE-PULL/sla-health-hub` against `wp-content/themes/sla-health-hub`** one
   more time at your leisure — every difference is a candidate restore target.
2. **Decide the redeploy strategy.** Two options:
   - **Hot restore:** run the standard tar-deploy from CLAUDE.md §"Deploy workflow"
     against local working tree. Live will jump to your May 13 state. (Risk:
     untested in production for ~2 weeks.)
   - **Staged restore:** redeploy just `customizer-pages.php`, `functions.php`,
     `inc/dashboard-functions.php`, the page templates, and `assets/css/main.css`
     first. Verify Customizer panels appear, then redeploy the rest.
3. **Investigate WHY the rollback happened.** The bulk-mtime fingerprint suggests
   either an hPanel "restore from backup" action or a re-upload from a stale local
   working tree by a different operator. Hostinger has 7-day automatic backups —
   pull the backup audit log from hPanel.
4. **Lock down deploys.** Consider requiring all deploys to flow through `git push`
   to GitHub, then `git pull` on the server, so rollbacks leave an obvious commit
   trail.
