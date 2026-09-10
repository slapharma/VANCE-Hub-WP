# SESSION HANDOVER — CliftonAI Hub template build

**Session date:** 2026-06-10
**Branch:** `claude/clone-wp-template-docs-BN0a1`
**PR:** [#1 draft](https://github.com/slapharma/VANCE-Hub-WP/pull/1) — *Add CliftonAI Hub template — rebranded clone of the WP theme*
**Commit:** `38a1309`
**Status:** ✅ shipped to draft PR; subscribed to PR events; no CI configured on the repo so no checks run.

---

## 1. What was asked

> "Create a full clone of the wp template and bundle it with all build notes,
> lessons learned and relevant MD files."

Clarified scope (via AskUserQuestion) before starting:

- **Form:** folder copy inside this repo (not a tarball, not a separate branch).
- **Branding:** strip Vance/SLA branding, rebrand to **CliftonAI** — AI
  consulting & app-solutions agency. Make categories niche-relevant.
- **Docs:** synthesize a fresh BUILD-NOTES + LESSONS-LEARNED MD (rather than
  just copying the originals verbatim).

---

## 2. What shipped

A self-contained `clifton-ai-template/` directory at the repo root:

```
clifton-ai-template/                       6.4M, 94 files
├── README.md                              entry point + quick start
├── BUILD-NOTES.md                         build pipeline + cross-file contracts
├── LESSONS-LEARNED.md                     11 lessons distilled from the prior rebrand
├── CLAUDE.md                              agent-facing instructions
├── TODO.md                                pre-deploy checklist (10 items)
├── docs/                                  inherited design notes (rebranded)
│   ├── ASK_AI_REDESIGN.md
│   ├── DISCOVERY_SUITE_IMPLEMENTATION.md
│   ├── IMPLEMENTATION_SUMMARY.md
│   └── UI_UPDATES_SUMMARY.md
└── wp-content/themes/cliftonai-hub/       the drop-in WP theme (34 PHP files, all lint-clean)
```

---

## 3. Decisions made (and the reasoning, for next session)

| Decision | Why |
| --- | --- |
| Theme folder = `cliftonai-hub` | Fresh template = no live DB = no need to defer the folder rename the upstream theme had to keep deferred. Text domain matches. |
| Identifier prefix = `clifton_` (was `vance_` / `sla_`) | Same reasoning. No `_sla_*` user-meta to preserve, so meta keys renamed to `_clifton_*` cleanly across 92 call sites. |
| REST namespace = `cliftonai/v1` | Matches brand. Server + all client `fetch` calls moved in lock-step. |
| postMessage type renamed `VANCE_SAVE_MALNUTRITION_RESULT` → `CLIFTONAI_SAVE_ROI_RESULT` | Niche relabel (malnutrition calc → ROI calc) forced the type rename; updated in both `page-dashboard.php` listener and `assets/tools/roi-calculator/index.html` sender. |
| Page slug renames | Healthcare → AI niche: Patients→Clients, Healthcare Professionals→Enterprise Partners, Blood Test→AI Readiness Assessment, Malnutrition Calculator→ROI Calculator, Healthcare Quiz→AI Maturity Quiz, Turn Evidence→Turn Insights, Our Heritage→Our Story. Page template files `page-{slug}.php` renamed to match. |
| Removed `assets/games/battleship-epa/` | Healthcare-specific Omega-3 promo game. Not relevant to an AI agency. The dashboard `high-score` tab's iframe was swapped for a placeholder block (the tab still exists for future use). |
| Kept the `clifton_get_theme_mod()` `legacy_prefix` fallback (the `implode( '', array( 's','l','a','_' ) )` trick) | Dead code in a fresh template — but harmless and removing it on the same pass risked masking a bug. Flagged in `TODO.md §8` for the first cleanup pass. |
| Did **not** rebuild React tool bundles from source | Source not in this repo. Bundles patched in-place via string substitution. Documented as a re-build trap in `LESSONS-LEARNED.md §6` and `TODO.md §6`. |
| `SLA Pharma` references rewritten (not preserved) | Upstream kept them deliberately because SLA Pharma was the legally-distinct parent entity. For a CliftonAI template that context doesn't apply — rewritten to generic CliftonAI mission copy. |
| `epavance-*` CSS classes renamed → `epa-cta-*` | Internal CSS class names in `main.css` that happened to contain "vance" as a substring. Renamed for hygiene since the EPA game was removed; classes are now orphan but harmless. |

---

## 4. Build pipeline used (for reproducibility)

Two transformer scripts in `/tmp/` (not committed — ephemeral, will be lost
when the container is reclaimed):

1. **`/tmp/cliftonai_rebrand.py`** — ordered (longest-first) string
   substitutions over every text-bearing file. **2,769 substitutions across
   45 files** in one pass.
2. **`/tmp/cliftonai_finalpass.py`** — cleanup of bare-word `\bSLA\b` /
   `\bVance\b` + the SLA Pharma legacy-copy block.

Plus three small inline Python snippets:

- Replace `vance-` (hyphen) handle prefixes → `clifton-` with negative
  lookbehind to avoid false-positives like `advance-`.
- Rename `epavance-*` CSS classes → `epa-cta-*` in `main.css`.
- Rename the postMessage type in `page-dashboard.php` + the ROI
  calculator's `index.html` shell.

If you need to re-run them, the source code is embedded verbatim in this
session's transcript and in PR #1's commit message intent — you can
reconstruct them from the substitution lists in `BUILD-NOTES.md §6`.

Final residue audit (zero matches across the bundle):
```
grep -rohE '(\bSLA\b|\bVance\b|gastrohealthhub|slahealth|vancemedical|sla-health-hub|vance-|VANCE_|_sla_|epavance)' \
  clifton-ai-template/ --include='*.php' --include='*.css' --include='*.html' --include='*.js' --include='*.svg'
```

---

## 5. Open items (what the next session should pick up)

### Blocking before first prod deploy
1. **API key** — paste OpenRouter (or compatible) key into Customize → Ask AI
   Configuration. Never into a PHP file. (`TODO.md §1`)
2. **Real domain + email** — replace placeholder `cliftonai.com` and
   `info@cliftonai.com` in customizer defaults. (`TODO.md §2`)
3. **Register `clifton_evidence_*` customizer controls** — the new
   "Turn Insights into Action" page reads ~20 theme mods that aren't yet
   registered in `customizer-pages.php`. (`TODO.md §3`)
4. **Create WP Pages** with the 15 slugs listed in `TODO.md §4` so the
   `page-{slug}.php` templates resolve.

### Nice-to-have / cleanup
5. Replace the dashboard `high-score` placeholder with a real widget or
   hide the tab. (`TODO.md §5`)
6. Re-export React tool bundles after source-side rebrand, OR rewrite the
   tools from scratch. (`TODO.md §6`)
7. Inline-copy review pass on `page-about.php`, `page-our-story.php`,
   `page-clients.php`, `page-enterprise-partners.php`,
   `inc/clinical-info-modal.php`, `inc/quiz-modal.php`. (`TODO.md §7`)
8. Strip the legacy-prefix fallback in `clifton_get_theme_mod()`. (`TODO.md §8`)
9. Gate `clifton-debug.php` behind `current_user_can('manage_options')` or
   delete it. (`TODO.md §9`)
10. Set up CI (no checks currently run on the PR). (`TODO.md §10`)

### Watch on PR #1
- I'm subscribed to `slapharma/VANCE-Hub-WP#1` events. CI failures and
  review comments will wake the session.
- Current status when this handover was written: **no CI configured**,
  **no review comments**, **no reviewer assigned**, draft.

---

## 6. Out of scope (deliberately not done in this session)

- Did **not** modify any files outside `clifton-ai-template/`. The
  upstream theme at `wp-content/themes/sla-health-hub/` and the root-level
  `CLAUDE.md` / `REBRAND-HANDOVER.md` / `TODO-RENAME.md` are unchanged.
- Did **not** rebuild the React widget source.
- Did **not** test the rebranded theme in a real WP install (no DB
  available in the remote container). Smoke-test plan is in PR description
  + `BUILD-NOTES.md §8`.
- Did **not** generate logos or new brand assets. The theme still ships
  the upstream `assets/img/logo.png` etc. — replace before going live.
- Did **not** rename remaining `assets/img/*_hero.png` files (hcp_hero,
  patient_hero, research_hero, etc.) — they're now misnamed for the AI
  niche but functionally fine.

---

## 7. Reproducing or extending

To continue working in this session direction:

```bash
git fetch origin claude/clone-wp-template-docs-BN0a1
git checkout claude/clone-wp-template-docs-BN0a1
cd clifton-ai-template
# Read in this order:
# README.md → BUILD-NOTES.md → LESSONS-LEARNED.md → CLAUDE.md → TODO.md
```

To prepare a real WP install:

```bash
cp -r clifton-ai-template/wp-content/themes/cliftonai-hub \
      /path/to/wordpress/wp-content/themes/
# Then in WP admin: activate + create Pages per TODO.md §4 + wire customizer.
```

To start a new agent session that picks this up cleanly, point them at
this file + `clifton-ai-template/CLAUDE.md` + the open PR.

---

## 8. Container-lifetime warning

The remote execution environment is ephemeral. Anything not committed
and pushed is gone when the container reclaims. As of this handover:

- ✅ Committed and pushed: everything in `clifton-ai-template/`.
- ❌ Not committed: `/tmp/cliftonai_rebrand.py`, `/tmp/cliftonai_finalpass.py`.
  These scripts are only in this transcript. If you want them as
  permanent tooling, copy them out of the chat into a `tools/` directory
  in the repo before the container dies.
