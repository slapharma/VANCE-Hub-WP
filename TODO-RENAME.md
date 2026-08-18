# DONE: theme folder rename `sla-health-hub` → `vance-health-hub`

**Status:** Completed 2026-08-18. Deferred on 2026-04-17, executed in a single window.
Supersedes the deferral note tracked per [REBRAND-HANDOVER.md §6.6](REBRAND-HANDOVER.md).

The folder name is never visible to end users. This was cross-reference bookkeeping plus a
coordinated deploy window.

## What was actually coupled

- `Text Domain: sla-health-hub` in `style.css`, consumed by ~600 `esc_html__`/`__`/`esc_attr__`
  call sites. (The old estimate of 169 undercounted.) Note `load_theme_textdomain()` is never
  called and there are no `.mo`/`.po` files, so the text domain is inert — it was renamed for
  consistency, not because anything would have broken.
- `wp_options.template` and `wp_options.stylesheet` — both stored `sla-health-hub`.
- `theme_mods_sla-health-hub` — 39,626 bytes of serialised customizer data.
- ~2,900 baked-in `themes/sla-health-hub` paths inside the `assets/tools/ibd-recipes/`
  static Next.js export (HTML + `.txt` payloads). **This was the real bulk of the work** and
  is what breaks if the folder is renamed without redeploying the theme.
- 1,040 in-DB references to `themes/sla-health-hub` (4 posts, 6 usermeta, 20 FluentCRM
  campaign bodies, 1 vhh-annotations selector, rest Jetpack Boost transients).
- `.github/workflows/deploy.yml` path filter, `REMOTE_THEME`, `LOCAL_THEME`, backup filename
  pattern; `email-templates/*.html` logo URLs; `LIVE-PULL/` and `LOCAL/` helper scripts.

All live PHP resolves assets through `get_template_directory_uri()`, so no template code
carried a hardcoded path.

## Sequence that was executed

1. **Repo** — `git mv` the folder, then a blanket `sla-health-hub` → `vance-health-hub`
   substitution across 237 text files in the theme. Verified by reconstructing every changed
   file from `HEAD` with the same substitution and byte-comparing: 237/237 identical, so the
   commit provably contains nothing but the slug change. All 73 PHP files lint clean.
2. **Server, zero-downtime cutover** — `cp -a sla-health-hub vance-health-hub` first, so both
   folders existed and the site kept serving from the old one while the DB was flipped.
3. **Database** — single statement, no JSON round-trip:
   ```sql
   UPDATE wp_options SET option_value='vance-health-hub' WHERE option_name IN ('template','stylesheet');
   UPDATE wp_options SET option_name='theme_mods_vance-health-hub' WHERE option_name='theme_mods_sla-health-hub';
   ```
   Renaming `option_name` in SQL preserves the serialised blob byte-for-byte — confirmed by
   MD5 `1028a4dc5b6a2c9953271a576bca35b6` / 39,626 bytes matching before and after. Do **not**
   use `wp option get --format=json | wp option update --format=json`; that round-trip can
   mangle serialised PHP.
4. **In-DB URLs** — `wp search-replace 'themes/sla-health-hub' 'themes/vance-health-hub'
   --all-tables --precise` (1,040 rows), plus a second scoped pass for the JSON-escaped form
   `themes\/sla-health-hub`, which the first pattern misses. One `vhh-annotations`
   `_vhh_selector` row stored the path that way.
5. **Old folder removed** after verification; theme re-listed as active automatically (no
   `wp theme activate` needed — the option flip is what activates it, and avoiding
   `switch_theme` also avoids re-running `after_switch_theme` hooks).
6. Caches flushed: `wp cache flush`, `wp litespeed-purge all`, `wp transient delete --all`.

## Deliberately NOT rewritten

- **262 published + 39 trashed `customize_changeset` posts** keyed `sla-health-hub::<setting>`.
  These are historical changesets. WordPress only applies changesets matching the current
  stylesheet, so they are inert. Rewriting the key would make stale customizer states
  applicable to the live theme again — actively harmful.
- `wp_wpmailsmtp_debug_events.initiator` (3 rows) and one `wp_aioseo_seo_analyzer_results`
  row — historical logs and a regenerating cache.
- `_sla_*` user/post meta keys — CLAUDE.md constraint 2.
- The `implode('', array('s','l','a','_'))` legacy prefix in `vance_get_theme_mod()` —
  CLAUDE.md constraint 3. It is `_sla_`, a different string, and was never at risk.
- Historical docs (`REBRAND-HANDOVER.md` body, `RECONCILIATION-2026-05-25/`, dated
  `HANDOVER-*.md`, `LIVE-PULL/backups/*.json`). A dated banner was added to the top of
  `REBRAND-HANDOVER.md` instead.
- Server backup tarballs already named `sla-health-hub-pre-*.tar.gz` keep their original names.

## Rollback

Backups taken immediately before the cutover, all outside the web root at `~/`:

- `~/sla-health-hub-prerename-2026-08-18-0951.tar.gz` (26M, full theme)
- `~/db-prerename-2026-08-18.sql` (17M, full DB)
- `~/theme_mods-prerename-2026-08-18.json` (34K)

To reverse:

```bash
cd ~/domains/vancehealthhub.co.uk/public_html/wp-content/themes
tar xzf ~/sla-health-hub-prerename-2026-08-18-0951.tar.gz
cd ~/domains/vancehealthhub.co.uk/public_html
wp db query "UPDATE wp_options SET option_value='sla-health-hub' WHERE option_name IN ('template','stylesheet'); UPDATE wp_options SET option_name='theme_mods_sla-health-hub' WHERE option_name='theme_mods_vance-health-hub';"
wp search-replace 'themes/vance-health-hub' 'themes/sla-health-hub' --all-tables --precise
rm -rf wp-content/themes/vance-health-hub
wp cache flush; wp litespeed-purge all
```

Then `git revert` the rename commit in this repo so CI stops deploying to the new path.

## Note: `wp db export` is broken on this host

`wp db export` exits 255 with no message (silently, even with `--debug`). `mysqldump` itself
works fine. Use it directly:

```bash
cd ~/domains/vancehealthhub.co.uk/public_html
DBN=$(wp config get DB_NAME); DBU=$(wp config get DB_USER); DBH=$(wp config get DB_HOST)
export MYSQL_PWD=$(wp config get DB_PASSWORD)
mysqldump --add-drop-table --single-transaction --quick -h"$DBH" -u"$DBU" "$DBN" > ~/db-backup.sql
```
