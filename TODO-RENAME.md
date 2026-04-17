# Deferred: theme folder rename `sla-health-hub` → (new slug)

**Status:** Deferred on 2026-04-17. Tracked per [REBRAND-HANDOVER.md §6.6](REBRAND-HANDOVER.md). Low-value, high-risk — user decided to leave for later.

The folder name is never visible to end users. The effort is entirely cross-reference bookkeeping plus a coordinated deploy window.

## Why this is hard

- `Text Domain: sla-health-hub` in `style.css` is consumed by `esc_html__('...', 'sla-health-hub')` in **~169 call sites** across `functions.php`, `customizer-pages.php`, and every `page-*.php`.
- `wp_options.template` and `wp_options.stylesheet` in the live DB both store `sla-health-hub`. Renaming the folder without updating these breaks theme activation immediately.
- Hostinger live path `~/domains/gastrohealthhub.com/public_html/wp-content/themes/sla-health-hub/` must be renamed atomically with the local rename or the next deploy fails.
- `theme_mods_sla-health-hub` option key holds all customizer data — renaming requires a DB migration of that key or the admin's customizations disappear.
- Any `.mo` / `.po` translation files (currently none, but if ever added) are keyed by text domain.

## Coordinated sequence — all in one deploy window

1. **Pick the new slug.** Candidates: `vance-hub`, `gastrohealthhub`, `vance-medical`. `vance-hub` matches the GitHub repo name `VANCE-Hub-WP`.
2. **Local repo**
   - Rename folder `wp-content/themes/sla-health-hub/` → `wp-content/themes/<new-slug>/`.
   - Edit `style.css` header: `Text Domain: <new-slug>`.
   - Repo-wide find/replace: `'sla-health-hub'` → `'<new-slug>'` but **scoped to** `esc_html__`, `esc_attr__`, `_e`, `_x`, `_n`, `__`, `load_theme_textdomain` calls only.
   - Grep for any remaining `'sla-health-hub'` string literal — expect only `REBRAND-HANDOVER.md`, `CLAUDE.md`, and `TODO-RENAME.md` (this file) to match after the targeted replace.
   - Deploy command paths inherit from `$THEME` env var but the literal `sla-health-hub` appears in the backup filename and rollback commands — update those too.
3. **Server**
   ```bash
   ssh -i ~/.ssh/hostinger_sla -p 65002 u767439438@82.29.185.3 \
     "cd ~/domains/gastrohealthhub.com/public_html/wp-content/themes && \
      mv sla-health-hub <new-slug>"
   ```
4. **Database** — run from the live server via wp-cli (dry-run first)
   ```bash
   ssh -i ~/.ssh/hostinger_sla -p 65002 u767439438@82.29.185.3 \
     "cd ~/domains/gastrohealthhub.com/public_html && \
      wp option update template   '<new-slug>' && \
      wp option update stylesheet '<new-slug>' && \
      wp option update theme_mods_<new-slug> \"\$(wp option get theme_mods_sla-health-hub --format=json)\" --format=json && \
      wp option delete theme_mods_sla-health-hub"
   ```
5. **Re-activate the theme** (WP admin → Appearance → Themes → Activate) or via wp-cli `wp theme activate <new-slug>`. The admin UI may show a "broken theme" warning until activation because `template`/`stylesheet` options may be briefly inconsistent with the on-disk folder — do steps 3 and 4 in quick succession.
6. **Update the deploy script** in `CLAUDE.md` and any README references to the new slug.
7. **Smoke-test** per [REBRAND-HANDOVER.md §8](REBRAND-HANDOVER.md) — every smoke test should still pass because nothing user-facing changed.

## Things to NOT change during this rename

- `_sla_*` user/post meta keys — see CLAUDE.md constraint 2.
- The `implode('', array('s','l','a','_'))` trick inside `vance_get_theme_mod()` — see CLAUDE.md constraint 3.
- CSS custom properties `--primary-*` and hex literals — unrelated to the folder rename, and changing them here adds risk.
- GitHub org `slapharma` — user decision, parent pharma entity retained.

## Rollback

Keep a server-side backup before the rename:
```bash
ssh ... "tar czf ~/domains/gastrohealthhub.com/public_html/wp-content/themes/sla-health-hub-prerename-$(date +%Y-%m-%d-%H%M).tar.gz -C ~/domains/gastrohealthhub.com/public_html/wp-content/themes sla-health-hub"
```

If activation fails after rename, reverse steps 3–5:
```bash
wp option update template   'sla-health-hub'
wp option update stylesheet 'sla-health-hub'
mv <new-slug> sla-health-hub
```

## Effort estimate (rough, for planning only)

- Local refactor: 1–2 hours (169 call sites, but mechanical).
- Server + DB: 15 minutes if wp-cli access is ready.
- Testing: 30–60 minutes for full smoke-test pass.
- **Total:** ~3 hours of focused work, must be done start-to-finish in one session.
