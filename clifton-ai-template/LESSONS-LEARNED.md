# LESSONS-LEARNED — building CliftonAI from a rebranded WP theme

Distilled from the SLA Health → Vance Medical rebrand journey and the
CliftonAI fork. Read this before doing any global text replace or
identifier rename in the theme.

---

## 1. Never commit an API key

The upstream theme committed an OpenRouter API key into
`inc/dashboard-functions.php`, split across a few `.` concatenations on
the assumption that the split would hide it from GitHub's secret
scanner. **It did not.** The key was exposed in the public repo and on
the live site.

The fix lives in this template: the AI chat backend reads the key via
`clifton_get_theme_mod( 'clifton_askai_api_key', '' )`. The customizer
control already exists. Enter the key once via WP Admin → Appearance →
Customize → Ask AI Configuration. **Never paste it into a PHP file.**

If you accidentally commit a key:
1. Revoke at https://openrouter.ai/keys (or the equivalent dashboard).
2. Rotate. The new key only ever enters via the customizer.
3. Rewrite history if the repo is private and the key was never in a
   public ref; otherwise rotation is sufficient.

---

## 2. A bare `SLA → Vance` (or `Vance → Clifton`) search-replace will
   silently break the codebase

Substrings the upstream rebrand hit accidentally:

- `SLA Pharma` — the parent pharma entity that the user explicitly kept.
  A blind `SLA → Vance` replace rewrote it to `Vance Pharma`. Reverted
  manually.
- `translateX(` and `translate3d(` in CSS — `Vance → Clifton` does not
  hit `translate`, but `Vance` matches inside `advance`, `relevance`,
  `glance`, and `clearance`. Always use word-boundary patterns
  (`\bVance\b`).
- CSS class names like `.epavance-banner` (`epa` + `vance`) — these are
  internal to a healthcare-specific Omega-3 promo game that has been
  removed from this template. They illustrate why a blind replace is
  dangerous.

**Rule:** use a Python script with regex word boundaries and
longest-first phrase ordering. Never run `sed -i 's/Vance/Clifton/g'`.

---

## 3. Always substitute longest phrase first

When you do `Vance Medical Foods Ltd → CliftonAI Solutions Ltd` AFTER
`Vance Medical → CliftonAI`, you've already destroyed the longer match.
The script in `BUILD-NOTES.md §2` orders the substitution list from
longest to shortest:

1. `Vance Medical Foods Ltd` → `CliftonAI Solutions Ltd`
2. `Vance Medical Hub` → `CliftonAI Hub`
3. `Vance Medical Ltd` → `CliftonAI Solutions Ltd`
4. `Vance Medical` → `CliftonAI`
5. `\bVance\b` → `Clifton`

If you add a new phrase, slot it in at the right depth.

---

## 4. Cross-file contracts move in lock-step

Five places in the codebase have a string on the server and a matching
string on the client, and you must change both in the same commit:

1. **AJAX action names.** `add_action( 'wp_ajax_clifton_save_note', ... )`
   on the PHP side, `action: 'clifton_save_note'` in the `fetch` body on
   the JS side. Mismatch returns a `0` from `admin-ajax.php` with no
   useful error.
2. **Nonces.** `wp_create_nonce( 'clifton_dashboard_nonce' )` printed
   into the page, `check_ajax_referer( 'clifton_dashboard_nonce', 'nonce' )`
   in the handler. Mismatch returns `-1`.
3. **REST namespace + route.** `register_rest_route( 'cliftonai/v1', '/ai-chat', ... )`
   in PHP, `fetch( '/wp-json/cliftonai/v1/ai-chat', ... )` in JS.
   Mismatch returns 404.
4. **postMessage types.** Embedded iframe tools post a typed message
   (`{ type: 'CLIFTONAI_SAVE_ROI_RESULT', payload }`); the dashboard's
   `window.addEventListener('message', ...)` filters on that type.
   Mismatch silently drops the result.
5. **Text domain.** `'cliftonai-hub'` second arg to `__()` /
   `esc_html__()` MUST equal the `Text Domain:` header in `style.css`
   AND the on-disk folder name. Mismatch silently disables translations.

The PHP-only `clifton_get_theme_mod( 'clifton_*', ... )` calls only need
to match `customizer-pages.php` settings, which lives in the same
language so it's easier.

---

## 5. Renaming user/post meta keys orphans live data

In the upstream production database, every user's profile image,
bookmarks, notes, saved searches, quiz results, calculator history,
uploaded posters, and clinical profile lived under `wp_usermeta` rows
with `meta_key LIKE '_sla_%'`. A blind rename to `_vance_*` would have
left the DB unchanged but the code would look at the new prefix and see
nothing — every user's content gone, silently.

The upstream rebrand kept `_sla_*` everywhere it touched the DB, and
translated the form field names back to `_sla_*` on submit. The fix
list was 92 call sites across `inc/dashboard-functions.php` and
`functions.php`.

For **this template** there is no DB to preserve, so `_sla_*` was
renamed cleanly to `_clifton_*` (92 call sites updated by the same
transformer script). When you bring real users onto this template, the
meta keys will be born `_clifton_*` and you never need a translation
layer.

If you ever fork this template to *replace* an existing live site,
remember: you cannot rename meta keys without a DB migration.

---

## 6. Minified bundles revert rebrands

The three React widgets in `assets/tools/*/` are Vite build outputs.
Strings inside the bundles (the AI bot's display name, the
postMessage type, the brand chrome) were patched directly into the
bundles by the transformer pass — the original source was not
re-built.

If anyone re-runs `npm run build` against the React source without
re-applying the rebrand to the source first, the freshly-built bundle
will revert the brand. Options:

- Re-apply the substitution list to the React source before building.
- Port the substitution list into the build step (a Vite plugin or
  post-build sed pass).
- Burn down the React source and rebuild fresh with the right strings
  from the start. Recommended for a new project — the original code
  was small.

---

## 7. WordPress slug-based template lookup is convention, not contract

WP looks for `page-{slug}.php` at template-resolution time based on
the live page slug, not the file's original name. When you renamed
`page-patients.php` → `page-clients.php` you also need a Page in WP
Admin with slug `clients` for the new template to be picked up. If
you forget to create the Page, the template file just sits unused
and the URL 404s.

The bundled `TODO.md` lists every page slug the template wants.

---

## 8. Customizer controls and template defaults can drift

The upstream new "Turn Evidence Into Action" page introduced ~20 new
`vance_evidence_*` theme mods that the template reads, but the
controls were never registered in `customizer-pages.php`. The page
renders fine using the code-side defaults; the admin just can't edit
the copy via the Customizer UI.

In this template, those reads are `clifton_evidence_*`. Same problem
inherited. See [TODO.md §3](TODO.md#3-register-clifton_evidence_-customizer-controls).

When you add a new template that reads theme mods, **register the
controls in the same commit** or the next maintainer will spend
twenty minutes wondering why their customizer save does nothing.

---

## 9. Three-layer caches will make you doubt your eyes

The upstream live site sits behind Hostinger's edge cache, the
LiteSpeed plugin's page cache, and the browser cache (via
`wp_enqueue_style`'s `?ver=` parameter). After a deploy:

1. Purge Hostinger cache (hPanel → Cache Manager → Purge All).
2. Purge LiteSpeed plugin cache from the WP admin bar.
3. Bump the `?ver=` query string on enqueued styles/scripts in
   `functions.php` if any of them changed.

If a deploy "doesn't seem to take", a layer-1 or layer-2 cache miss is
almost always the cause.

For this template, before you have a real host wired up, the only
cache to clear is the browser.

---

## 10. Defensive coding tricks become legibility bombs

The upstream `vance_get_theme_mod()` reads the new prefix, falls back
to a legacy prefix that is constructed via
`implode( '', array( 's', 'l', 'a', '_' ) )`. The obfuscation existed
so that a global `s/sla_/vance_/` replace wouldn't accidentally
rewrite the legacy fallback and disconnect the live admin from their
saved settings. It worked. It also took the next maintainer twenty
minutes to figure out what they were reading.

If you keep a defensive construction like this, **document it inline**
with a one-line comment that says what would break if it were
simplified. (In this template the function still has the obfuscation,
inherited; the legacy-prefix branch is harmless dead code since there
is no DB. Delete the branch when you do your first cleanup pass.)

---

## 11. AI-agent instruction files (`CLAUDE.md`) are load-bearing

The upstream `CLAUDE.md` was the single most useful artifact in the
handover — it captured *why* certain things must not be changed, in a
form an AI agent picks up before doing anything destructive. The
equivalent for this template lives at [./CLAUDE.md](CLAUDE.md). Keep
it up to date as you make architectural decisions; future-you (or a
future Claude session) will thank you.
