# CLAUDE.md — CliftonAI Hub Template

**Brand:** CliftonAI — AI consulting & app-solutions agency
**Theme path:** `wp-content/themes/cliftonai-hub/`
**Text domain:** `cliftonai-hub`
**Status:** template; no live site, no DB, no committed credentials.

This template is a fork of the SLA Health / Vance Medical WP theme,
rebranded for an AI-agency niche. Read [BUILD-NOTES.md](BUILD-NOTES.md)
and [LESSONS-LEARNED.md](LESSONS-LEARNED.md) before any non-trivial
edit — they explain what is load-bearing and what landmines the prior
rebrand left.

---

## Load-bearing constraints — break any and the theme silently breaks

### 1. Cross-file contracts move in the same commit

| Contract | Server | Client |
| --- | --- | --- |
| AJAX action names | `add_action( 'wp_ajax_clifton_*', ... )` | `action: 'clifton_*'` in `fetch`/`jQuery.ajax` calls |
| Nonces | `check_ajax_referer( 'clifton_dashboard_nonce', 'nonce' )` | `wp_create_nonce( 'clifton_dashboard_nonce' )` |
| REST routes | `register_rest_route( 'cliftonai/v1', ... )` | `fetch( '/wp-json/cliftonai/v1/...' )` |
| postMessage types | `if ( e.data.type === 'CLIFTONAI_SAVE_ROI_RESULT' )` | `parent.postMessage({ type: 'CLIFTONAI_SAVE_ROI_RESULT', ... })` |
| Text domain | `'cliftonai-hub'` second arg to `__()` | `Text Domain: cliftonai-hub` in `style.css` + folder name |

Renaming one side without the other returns `0` / `-1` / `404` / silently
drops messages. See [LESSONS-LEARNED.md §4](LESSONS-LEARNED.md#4-cross-file-contracts-move-in-lock-step).

### 2. Do not run a bare global search-replace on brand strings

Use word-boundary regexes (`\bVance\b`, `\bSLA\b`) and order substitutions
longest-first. The transformer in `/tmp/cliftonai_rebrand.py` is the
template's reference for how to do this safely. See
[LESSONS-LEARNED.md §2-3](LESSONS-LEARNED.md#2-a-bare-sla--vance-or-vance--clifton-search-replace-will).

### 3. Never commit an Ask AI API key

The Ask AI feature reads its key from
`clifton_get_theme_mod( 'clifton_askai_api_key', '' )`. Enter the key via
WP Admin → Appearance → Customize → Ask AI Configuration. Never paste a
key into a PHP file. See [LESSONS-LEARNED.md §1](LESSONS-LEARNED.md#1-never-commit-an-api-key).

### 4. Minified tool bundles in `assets/tools/*` need source-side rebrand

The three React widgets are pre-built Vite bundles. If anyone rebuilds
from source without first applying the brand substitution to the source,
the brand reverts. See [LESSONS-LEARNED.md §6](LESSONS-LEARNED.md#6-minified-bundles-revert-rebrands).

### 5. Don't rename `_clifton_*` user/post meta keys after live users exist

For this template there is no DB and meta keys can be renamed freely.
Once a real site is running, renaming meta keys orphans all user data.
See [LESSONS-LEARNED.md §5](LESSONS-LEARNED.md#5-renaming-userpost-meta-keys-orphans-live-data).

---

## Adding a page template

1. Create `wp-content/themes/cliftonai-hub/page-{slug}.php`.
2. In WP Admin create a Page with that slug; WP will pick up the template
   automatically.
3. If the template reads new customizer settings, **register them in
   `customizer-pages.php` in the same commit** or admins won't be able to
   edit copy.
4. Use `clifton_get_theme_mod( 'clifton_*', $default )` to read theme mods,
   never `get_theme_mod()` directly.
5. Wrap user-facing strings in `esc_html__( '...', 'cliftonai-hub' )`.

---

## Adding an AJAX endpoint

1. Server: in `inc/dashboard-functions.php`, register
   `add_action( 'wp_ajax_clifton_{name}', '{handler}' )` and the
   `nopriv` variant if anonymous calls are allowed.
2. Inside the handler, call `check_ajax_referer( 'clifton_dashboard_nonce', 'nonce' )`.
3. Client: in the relevant `page-*.php` template, embed
   `<script>const cliftonNonce = '<?php echo esc_js( wp_create_nonce( 'clifton_dashboard_nonce' ) ); ?>';</script>`
   and a `fetch` body of `action=clifton_{name}&nonce=...&payload=...`.
4. Smoke-test in a browser before commit. Watch the network panel for the
   `0` (action mismatch) or `-1` (nonce mismatch) responses.

---

## Adding a REST route

1. Server: in `inc/dashboard-functions.php`, hook
   `rest_api_init` and call
   `register_rest_route( 'cliftonai/v1', '/{name}', [ 'methods' => 'POST', 'callback' => ..., 'permission_callback' => ... ] )`.
2. Client: `fetch( '/wp-json/cliftonai/v1/{name}', { ... } )`.
3. Set a sensible `permission_callback` — never `__return_true` in
   production unless the endpoint is genuinely public.

---

## Adding an embedded tool

1. Build the React app (Vite) with the brand substitution applied to
   source — see [LESSONS-LEARNED.md §6](LESSONS-LEARNED.md#6-minified-bundles-revert-rebrands).
2. Output to `assets/tools/{slug}/` — keep `index.html` and the hashed
   `index-*.js` together.
3. In the tool, post results via
   `parent.postMessage({ type: 'CLIFTONAI_SAVE_{NAME}_RESULT', payload }, '*')`.
4. In `page-dashboard.php`, add a `case '{tab-slug}':` branch that
   embeds an iframe pointing at the tool's `index.html` and an
   `addEventListener('message', ...)` listener filtering on the
   `CLIFTONAI_SAVE_{NAME}_RESULT` type.

---

## Smoke tests after any change

- Front page loads with teal primary (`#008080`).
- `/ask-ai/` page heading reads "Ask AI" and the chat sends and
  receives. (Requires a customizer-stored API key.)
- `/turn-insights-into-action/` renders the four pillars.
- Dashboard → Profile edit saves cleanly (AJAX nonce + `_clifton_*`
  meta round-trip).
- ROI Calculator completes and writes back to the dashboard
  (`CLIFTONAI_SAVE_ROI_RESULT` postMessage contract).
- WP Customizer opens and saves without PHP warnings.

---

## Repo structure

```
clifton-ai-template/
├── README.md
├── BUILD-NOTES.md          ← how this template was built
├── LESSONS-LEARNED.md      ← traps the prior rebrand exposed
├── CLAUDE.md               ← this file
├── TODO.md                 ← pending items
├── docs/                   ← design notes inherited from upstream
└── wp-content/
    └── themes/
        └── cliftonai-hub/  ← the WordPress theme
```

---

## Today's date

This file references the upstream date `2026-04-17` as the original
handover. The CliftonAI fork was created on `2026-05-15`. Update this
line if you significantly revise the constraints above.
