# IBD Discounts & Freebies tool — audit and build plan

Written 2026-09-03. Source document: `UK_IBD_Freebies_Discounts_Guide_2026.pdf`
(dated 1 September 2026, 10 pages, 26 schemes, 25 links). Every claim in it was
re-checked against the live provider page on 2 September 2026. The corrected
dataset is in [tools/discounts-seed.json](../tools/discounts-seed.json) and is the
thing the tool imports; the PDF itself should not be republished as-is.

---

## 1. Audit result

**Verdict: the guide is sound.** All 25 links return 200. Every price, number and
eligibility statement that is on a provider page was confirmed verbatim. Nothing was
contradicted. What needs changing is context: two schemes describe future rules as
current, three cite the wrong page for the fact they state, and several are missing
the sign-up route or a figure a reader would want.

### Must fix before publishing

| # | Scheme | Problem | Fix |
|---|---|---|---|
| 3 | WaterSure | Cites a gov.uk **news story** about a reform that is not in force. Ofwat and CCW "WaterSure" landing pages both 404. | Present the reform as **early 2027** (mandatory England, voluntary Wales): extends to disability-benefit households under £25,745 income, caps at the lower of average metered/average bill, single-occupier cap, drops the GP note. Cite the CCW FAQ or Citizens Advice for the current rules. |
| 15 | Bus pass | "Off-peak" is not on the cited page; and the restriction ends. | State the current window (9.30am to 11pm weekdays, all day weekends) and that it becomes **24/7 from 1 April 2027** (GOV.UK, 17 Aug 2026). |
| 20 | UC / LCWRA | Cited page has no amounts. | Add higher £429.80 / lower £217.26 a month, cited from `gov.uk/universal-credit/what-youll-get`. Lower rate frozen to 2030. |
| 25 | Vehicle tax | Cited page names neither the qualifying benefits nor the 50% route. | Cite `gov.uk/financial-help-disabled/vehicles-and-transport`. Full exemption = enhanced-mobility PIP / higher-mobility DLA / WPMS / AFIP; 50% = standard-mobility PIP. |
| 1 | RADAR key | Generic membership page cited; eligibility detail is on `/radar-key`. | Link `/radar-key`; say "tick the box on the join form, one per member, patients and parents/carers only". |

### Should add

- **Railcard eligibility widened 1 March 2026**: a Disabled Persons Bus Pass, Freedom Pass, or being unable to drive on medical grounds now qualifies. Many people with IBD hold one of those and no PIP.
- **PPC**: Crohn's/UC are not on the medical exemption list, but a **permanent stoma is** (form FP92A from the GP). That is the one free-prescription route open to part of this audience and the guide omits it.
- **Blue Badge fee**: up to £10 England, £20 Scotland, free Wales; up to 3 years.
- **Access Card**: £15 for 3 years; free Digital Access Pass exists.
- **PIP / AA rates 2026/27**: £76.70 / £114.60 a week (PIP daily living and AA); PIP mobility £30.30 / £80.00.
- **NHS Low Income Scheme**: online only if capital is £6,000 or less; paper HC1 otherwise.
- **English Heritage**: the route is "select the essential companion ticket when booking".
- **Kew**: concession is daytime only; peak concession £23 online / £26 gate.
- **Cadw**: free Disabled Person's Membership card by phone/email.
- **Can't Wait Card**: 30 languages. The "not legally binding" line is true but is not on any CCUK page; attribute it as general guidance.

### Things the audit learned about the sources (matters for the link checker)

- `nationaltrust.org.uk` sits behind a Radware bot check, `londonzoo.org`, `kew.org` and `nhsbsa.nhs.uk` return 403 to non-browser user agents. A checker must send a browser UA and whitelist the NT page, or it will report five dead links that are fine.
- The nhs.uk travel-costs page is past its stated review date (14 Aug 2026). Content still matches NHSBSA.
- gov.uk VAT-relief guidance was last updated in 2019.

---

## 2. Integration level per scheme

Every apply endpoint was probed for `X-Frame-Options` and `Content-Security-Policy:
frame-ancestors` on 2 September 2026 (results in the seed's `frameable` field).

| Tier | Meaning | Count | Schemes |
|---|---|---|---|
| **1 — on-hub** | Online form, no framing headers: can open inside the hub's tool modal. Must still be tested in a real browser because iframes get partitioned cookies and some apps break on that. | 3 | Access Card application (`app.accesscard.online`), Merlin Digital Access Pass (same Nimbus app; needed for Ride Access Pass at Merlin + Chessington) |
| **2 — popup hand-off** | Online form exists but refuses framing. Open in a named popup window from the hub, with the hub-side checklist and "I applied" tracker around it. | 10 | CCUK join (RADAR + Can't Wait), CEA Card, Railcard, Blue Badge, Attendance Allowance, NHS Low Income Scheme, PPC, Access to Work, Madame Tussauds ticketing |
| **3 — prepare only** | No online form: phone, post, PDF, at the venue, via your water company or council, or inside a UC account. The hub prepares the person: what to bring, what to say, tap-to-call, a pre-filled PDF, a reminder. | 13 | WaterSure, National Trust, English Heritage, Merlin/Chessington companion ticket, London Zoo, Kew, RHS, Cadw, Bus pass, PIP (phone), UC health element, Travel costs (HC5), VAT declaration, Vehicle tax |

No scheme offers an API, affiliate feed or embeddable widget. There is nothing to
"sign up for on the hub" in the sense of the hub submitting on the user's behalf, and
we should not build that: several of these are benefit claims and the hub must never
hold benefit evidence or submit a claim. What the hub can own is everything around
the form: eligibility matching, evidence readiness, the hand-off, and the follow-up.

**Tier 1 caveat.** Nimbus is the only frameable application and it is a
third-party form that collects disability evidence. Framing it inside our page
makes it look like ours. Show it in the tool modal with a visible "You are on
accesscard.online" strip, not chromeless, and fall back to a popup if the modal
reports a `securitypolicyviolation` or the app's session breaks under partitioned
storage. Build the real thing and detect the restriction at runtime, do not
pre-emptively downgrade.

---

## 3. What the tool is

**Working name:** IBD Discounts & Freebies. Page slug `/ibd-discounts/`.

Three surfaces on one dataset:

1. **Directory page** — searchable, filterable list of every scheme, each a card with
   value, cost, who qualifies, evidence accepted, an "Apply" action matched to its
   tier, and a Save button.
2. **Featured discount card** — one reusable renderer that any promo slot, article
   sidebar, homepage section or dashboard card can call, picking a scheme by hand
   (Customizer) or automatically by context (article category/tags or the member's
   profile).
3. **My Discounts** dashboard tab — saved schemes, application status, an *Access
   Folder* checklist of evidence the member holds, and an eligibility view ("with a
   PIP letter and a water meter you likely qualify for 11 of these").

The personalisation is what makes it "actionable" rather than a list. The evidence
folder is lifted straight from page 10 of the PDF ("What evidence is worth keeping
ready?"), which is the best idea in the document.

---

## 4. Data model

CPT `vance_discount`, native meta boxes, no ACF (the theme uses none; the plugin on
the server is unused by theme code). Mirrors `inc/recipe-cpt.php` /
`inc/recipe-admin.php` exactly: field `vance_discount_x` submits, meta `_vance_discount_x`
stores, empty deletes the meta.

| Meta | Type | Notes |
|---|---|---|
| `_vance_discount_provider` | text | |
| `_vance_discount_value` | text | one-line saving, shown on cards |
| `_vance_discount_cost` | text | |
| `_vance_discount_what` | textarea | |
| `_vance_discount_who` | textarea | |
| `_vance_discount_ibd_note` | textarea | |
| `_vance_discount_evidence` | textarea, one per line | parsed like recipe ingredients |
| `_vance_discount_signals` | checkbox set | matcher keys, see §6 |
| `_vance_discount_official_url` | url | |
| `_vance_discount_apply_url` | url | |
| `_vance_discount_apply_type` | select | online / phone / post / pdf / at-venue / at-booking / via-supplier / via-council / via-gp / in-account |
| `_vance_discount_apply_contact` | text | phone/email/postal for tier 3 |
| `_vance_discount_tier` | select 1/2/3 | |
| `_vance_discount_frameable` | bool | from the probe; the modal reads it |
| `_vance_discount_upcoming` | textarea | dated future change, rendered as a banner |
| `_vance_discount_verified_on` | date | |
| `_vance_discount_confidence` | select | |
| `_vance_discount_featured` | bool | admin flag for the featured pool |
| `_vance_discount_related_posts` | ids | hand-curated article links |

Taxonomies: `vance_discount_cat` (toilet-access, days-out, travel, access-card,
benefit, nhs, tax, work, household), `vance_discount_region` (UK, England, Wales,
Scotland, NI), both `show_admin_column`. `has_archive => false`, the Page is the hub.
Seeded from `tools/discounts-seed.json` by a one-shot `wp vance discounts import`
guarded by an option, same pattern as `vance_recipe_seed_terms()`.

Per-user state, all under existing-style `_sla_*` keys (constraint 2 says never
rename, it does not forbid adding):

| Key | Shape |
|---|---|
| `_sla_saved_discounts` | flat array of post IDs, same as `_sla_reading_list` |
| `_sla_discount_status` | `{post_id: {status: interested|applied|received|declined, ts, note}}` |
| `_sla_access_folder` | `{signal_key: bool}` for the evidence checklist plus `region`, `state_pension_age`, `employed`, `water_meter` |

---

## 5. Files

Following the recipe and Patient Downloads precedents. Nothing rewrites stored
content; backing any piece out is deleting a `require`.

**Create**

| File | Does |
|---|---|
| `inc/discount-cpt.php` | CPT, taxonomies, rewrite-version flush, admin columns (provider, tier, verified date, featured). Copy `recipe-cpt.php`. |
| `inc/discount-admin.php` | Meta boxes and save handler. Copy `recipe-admin.php` including `vance_recipe_meta_field()`'s shape. |
| `inc/discount-data.php` | `vance_discount_directory_data()`: one flat array shared by server grid, featured renderer, dashboard and JS. `vance_discount_match($user_id)` for the eligibility view. |
| `inc/discount-frontend.php` | `vance_render_discount_card($id, $ctx)`, `vance_render_featured_discount($mode, $args)`, the apply-action resolver (tier → button + behaviour), JSON-LD. |
| `inc/discount-dashboard.php` | AJAX handlers: `vance_toggle_discount`, `vance_set_discount_status`, `vance_save_access_folder`, all on `vance_dashboard_nonce`, all `is_user_logged_in()` first, no `nopriv` hook. |
| `inc/discount-check.php` | `wp vance discounts check`: every `official_url` and `apply_url` fetched with a browser UA, asserts 200 plus a sentinel string per record (price or key phrase), re-probes framing headers, writes a **Links** admin column. Copy `inc/citation-check.php` including its cache asymmetry. |
| `page-ibd-discounts.php` | `Template Name: IBD Discounts`. Hero fork via `vance_page_hero_spotlight_active('discounts')`, then `template-parts/discount-directory.php`. |
| `single-vance_discount.php` | One scheme, full detail, related articles, apply action. |
| `template-parts/discount-directory.php` | Filter chips as `?cat=` links with server-side filtering (no-JS safe) plus client-side search on `data-*` attributes, copied from `recipe-hub-app.php:37-80`. |
| `template-parts/dashboard/discounts.php` | The tab panel, included from the switch rather than inlined (the dashboard file is 308 KB already). |
| `assets/css/discounts.css`, `assets/js/discounts.js` | Enqueued only on the template, the single, and `?tab=discounts`. |

**Edit**

| File | Edit |
|---|---|
| `functions.php` ~:3698 | `require` the five inc files after the recipe block. `discount-check.php` after `discount-data.php`. |
| `functions.php` :1240ff | Enqueue gated on `is_page_template('page-ibd-discounts.php')`, `is_singular('vance_discount')`, dashboard tab. |
| `customizer-pages.php` | `vance_discounts_panel` + `vance_discounts_hero` (tag/title/desc/bg/overlay), copied from Patient Downloads :1152-1166. Add a **Featured discounts** section: three picker controls (dropdown of published schemes) for homepage, sidebar default, and promo. |
| `inc/page-hero-spotlight.php` | The four edits from the checklist: config entry (`slot => 'discounts'`, band = three live counts: schemes, free-to-apply, tier-1), pages list, `case 'discounts':` with a `vance_page_hero_spotlight_discounts()` reading the CPT (not a second literal array), and the `$notes` entry with all three keys. Defaults to classic until toggled, by design. |
| `inc/dashboard-features.php` :101 | `'discounts' => ['label' => 'My Discounts', 'icon' => '🏷️', 'section' => 'main', 'default' => true, 'toggleable' => true]`. Sidebar, breadcrumb, H1 and Customizer toggle come free. |
| `page-dashboard.php` | `case 'discounts': get_template_part('template-parts/dashboard/discounts');` in the switch; subtitle at :516; home summary card at :574 (saved count + next action). |
| `single.php` :442 | New `.oped-sidebar-block oped-featured-discount` calling `vance_render_featured_discount('auto')`, and a `do_action('vance_article_sidebar')` while there so the next feature does not need to edit this file. |
| `inc/promo-block.php` :61/:71 | Add the discounts page to the CTA choices. |
| `inc/prime-block.php` | Optional: a "discount" card type that renders the featured card in place of a hand-typed card. |
| `inc/tool-modal.php` :42/:60 | Register `discount-apply` as an **inline** entry whose iframe `src` is set at open time from the card's `data-apply-url`; only used when `frameable` is true. |
| `page-tools-resources.php` :87 | Fourth tile in `$tools`. |
| `inc/article-schema-types.php` :91 | Map the page slug to `CollectionPage`. Singles get `GovernmentService` or `Offer` via a small `aioseo_schema_output` filter copied from `medical-schema.php:270`, remembering the argument is the whole graph. |
| `inc/seo-archive-robots.php` | Nothing: `has_archive` is false and the Page is real content. |

**Manual, on the live site**

1. Create the Page, slug `ibd-discounts`, template *IBD Discounts*.
2. `wp vance discounts import tools/discounts-seed.json`, then `wp vance discounts check`.
3. Menu: THE HUB → Free Health Tools → fourth child (docs/MEGA-MENU-SETUP.md:206). Hand-edit in wp-admin; never re-run `tools/build-mega-menu.php` against the live menu.
4. Customizer: switch the spotlight hero on for the page, pick three featured schemes.
5. Purge the three cache layers, then run the smoke list in §9.

---

## 6. Eligibility matcher and Access Folder

The folder is a checklist, stored as booleans, never a document. Keys:

`pip`, `dla`, `adp`, `aa`, `carers_allowance`, `blue_badge`, `bus_pass`,
`ccuk_member`, `access_card`, `stoma`, `uc`, `pension_credit`, `housing_benefit`,
`low_income`, `water_meter`, `state_pension_age`, `employed`, `needs_companion`,
`child_under_16`, `region` (one of five).

Each scheme carries `eligibility_signals`. Match = any signal held, or the scheme has
only `ibd_diagnosis`, or `needs_companion` is set and the scheme is a companion scheme.
Region filters out schemes outside the member's nation. Output three buckets: *likely*,
*possible* (no signal held but evidence route exists), *not in your nation*. The word
on the page is always "likely", never "eligible": eligibility is the provider's call
and the PDF's own disclaimer goes on the page verbatim.

The matcher also drives the **next best action**: the cheapest unlock the member does
not hold. For most members that is the Access Card (£15, unlocks eight companion
schemes) or CCUK membership (£15, unlocks RADAR + Can't Wait). The dashboard home card
shows that one thing.

---

## 7. Apply action by tier

| Tier | Button | Behaviour |
|---|---|---|
| 1 | "Apply on the hub" | Opens `VanceToolModal` with the provider form in the iframe, an origin strip above it, and a `securitypolicyviolation` listener that swaps to the popup route and records the failure. |
| 2 | "Apply (opens provider)" | `window.open(url, 'vance-apply', 'popup,width=…')`. Before opening, shows the scheme's evidence list as a checklist against the folder. On return focus, asks "Did you apply?" and sets status. |
| 3 | Varies: "Call 0800 917 2222", "Download form", "Find your water company", "Show at the gate" | Tap-to-call on mobile; pre-filled PDF for VAT declaration; postcode → supplier lookup for WaterSure (no API, ship a static list of the 20 English and Welsh water companies with their WaterSure pages); "Show at the gate" renders a wallet-style card of what to say and bring. |

Logged-out users see everything and can click through; Save, status and the folder
open `window.VanceRegisterModal.open({tool: 'discounts', payload: {post_id}})` and
resume via `onSuccess`, the same pattern as `tool-page-shell.php:571`.

Outbound clicks: there is no tracking helper in the theme and GTM is injected by a
plugin. Push `dataLayer.push({event: 'discount_apply', scheme, tier})` from
`discounts.js` guarded by `window.dataLayer`, and log the hand-off server-side in
`_sla_discount_status` regardless, so the dashboard works without GTM.

---

## 8. Featured discount card

One function, four callers.

```
vance_render_featured_discount( $mode, $args = [] )
  'pick'    → $args['post_id'], set by Customizer
  'auto'    → article context: category/tag → scheme category (travel → Railcard,
              work → Access to Work, days-out → CEA, toilet → RADAR/Access Card);
              falls back to the featured pool, rotates by post ID
  'member'  → logged-in: the matcher's next best action; else 'auto'
  'random'  → from the featured pool
```

Callers: `single.php` sidebar (`auto`), homepage section via the
`vance_homepage_sections` filter (`pick`), promo/prime blocks (`pick`), dashboard
home card (`member`). Every instance links to the single and carries the scheme's
`verified_on`, so a stale date is visible wherever the card is.

Cards obey the radius scale: `--radius-surface` for the card, `--radius-control` for
the tier badge and button. Not an article card, so not square.

---

## 9. Verification

Each of these must be able to fail.

- `php -l` on every new file; `wp vance discounts check` exits 0 with 34 ok (26 seed + 8 `schemes_additional`) and exits non-zero when one seed URL is changed to a 404.
- Directory page: 34 cards server-rendered with JS disabled; `?cat=travel` shows the travel schemes (Railcard, bus pass, Blue Badge, vehicle tax, Motability, TfL — 6); search "railcard" leaves 1.
- Tier 1 modal: open Access Card apply in a real browser with third-party cookies blocked and reach step two of the form. If it does not, the runtime fallback fires and the popup opens: assert the popup, not the modal.
- Tier 2: popup opens the exact `apply_url`; status changes to *applied* only after the confirm dialog.
- Dashboard: save on a card, `?tab=discounts` lists it; tick `pip` in the folder, likely count rises by the number of PIP-gated schemes (10); switch region to Scotland, English-only schemes leave.
- Logged out: Save opens the register modal, and after registration the discount is already saved.
- Featured card on an article in *Travel* renders the Railcard, on *Career* renders Access to Work, on an uncategorised post renders a pool item.
- Every image has alt; `og:image` present on the page and a single; no PHP notices in the sitemap fetch.
- Customizer opens and saves with the new panel (it has broken on a missing `$notes` key before).

---

## 10. Order of work

1. ~~Design system pass (`ui-ux-pro-max`) for the card, filter bar, tier badges and the folder checklist, before any markup.~~ — DONE, see [DISCOUNTS_UI_SPEC.md](DISCOUNTS_UI_SPEC.md).
2. CPT + admin + import + check (no UI) — code done: `inc/discount-cpt.php`,
   `inc/discount-admin.php`, `inc/discount-check.php`, required from
   `functions.php`, `php -l` clean. **Still to do:** deploy, then on staging
   `wp vance discounts import tools/discounts-seed.json` and
   `wp vance discounts check`, and eyeball a couple of edited posts in
   wp-admin (meta boxes, checkbox-set, taxonomies) before trusting the import.
3. ~~Directory page + single + hero wiring.~~ — DONE (code): `inc/discount-data.php`
   (directory data + live counts + categories-in-use), `inc/discount-frontend.php`
   (tier badge, apply-action resolver, Save button, card renderer),
   `page-ibd-discounts.php`, `single-vance_discount.php`,
   `template-parts/discount-directory.php` (chips server-rendered via `?cat=`,
   region + search client-side), `assets/css/discounts.css` + `discounts.js`,
   enqueue gating in `functions.php`. Hero: added the `discounts` entry, band
   function and switch case to `inc/page-hero-spotlight.php`, and a classic
   hero (`vance_discounts_panel`/`vance_discounts_hero`) to
   `customizer-pages.php` — same toggle-defaults-to-classic pattern as every
   other page there, since this page had no pre-existing classic hero to fork
   from. All `php -l` clean. **Not built this step:** the featured-card
   renderer (step 4), the tier-1 modal's origin strip (step 6 — tier-1 Apply
   buttons are plain links for now), AIOSEO schema/JSON-LD (deferred — see
   note below), and the dashboard's Save AJAX handler, so the Save button
   optimistically flips and reverts (assets/js/discounts.js says why).
   **Still to do:** everything in step 2's "still to do" (needs a live WP
   environment), then create the Page + template in wp-admin and eyeball the
   directory/single/hero in a browser.

   *Schema/JSON-LD deferred on purpose:* plan §5 routes this through
   `aioseo_schema_output`, which CLAUDE.md flags as a real trap (the filter
   receives the whole `@graph`, not one node — treating it as one node is a
   silent no-op that passes every naive test) and a neighbouring trap
   (`aioseo_sitemap_exclude_posts`/`_terms` breaks the sitemap merely by being
   registered). Wiring that correctly wants its own careful pass against
   `inc/medical-schema.php`'s working example, not a rushed add alongside
   everything else in this step.
4. ~~Featured renderer + sidebar + Customizer pickers.~~ — DONE (code).
   `vance_render_featured_discount( $mode, $args )` added to
   `inc/discount-frontend.php`: `pick` (explicit post_id), `auto` (article
   category/tag name matched against a fragment→discount-category map,
   preferring a featured scheme in that category then lowest tier, else the
   featured pool rotated by article ID), `member` (degrades to `auto` until
   `vance_discount_match()` exists in step 5 — guarded with `function_exists()`,
   not a hard dependency), `random`. Card markup/CSS per
   `docs/DISCOUNTS_UI_SPEC.md` §7 — not square, no Save button.
   Customizer: new "Featured Discounts" section in the discounts panel with
   three dropdowns (homepage/sidebar/promo slots) of published schemes, "—
   Let the page decide —" as the 0 default. `single.php` now renders the
   sidebar block right before "Attached Document", reading the sidebar pick
   first and falling back to `auto`, plus a new `do_action('vance_article_sidebar')`
   so the next sidebar feature doesn't need to edit this file again. Enqueue
   in `functions.php` widened to `is_singular('post')` so every article gets
   `discounts.css` (the featured card's classes live there, not duplicated
   into `main.css`). `php -l` clean on all four touched files.
   **Not built this step:** the homepage-section and promo/prime-block
   *callers* — the Customizer pickers for those two slots exist and store a
   value, but nothing reads `vance_discount_featured_homepage` or
   `_promo` yet (plan §5's `inc/promo-block.php` and homepage-section edits,
   not named in this step's title). Wiring them is a small follow-up once
   there's a real homepage section/promo slot to point at.
5. ~~Dashboard tab + AJAX + folder + matcher.~~ — DONE (code).
   `vance_discount_match($user_id)` (§6's three buckets: likely/possible/
   not_in_region) and `vance_discount_next_best_action_id()` (the literal
   Access Card / CCUK membership pair from this plan, not a recomputed
   biggest-unlock scan) added to `inc/discount-data.php`.
   `inc/discount-dashboard.php`: `vance_toggle_discount`, `vance_set_discount_status`,
   `vance_save_access_folder` — all `check_ajax_referer('vance_dashboard_nonce')`,
   all `is_user_logged_in()` first, **no `nopriv` hook on any of the three**
   as specified. Setting a status also adds the scheme to the saved list if
   it wasn't there, so a status is never recorded for something the saved
   list doesn't show.
   `template-parts/dashboard/discounts.php`: eligibility summary line, saved
   schemes as compact cards with a status `<select>`, and the Access Folder
   as `role="switch"` toggle rows (not styled checkboxes) including region —
   region, `state_pension_age`, `employed` and `water_meter` render as
   ordinary rows in the same grid as every other signal, since they're
   already entries in `vance_discount_signal_labels()` (no special-casing
   needed beyond region, which is its own `<select>` above the grid).
   Registry: `discounts` added to `inc/dashboard-features.php` (nav item,
   section, toggle come free from the existing registry-driven sidebar);
   `page-dashboard.php` gets the subtitle line, the tab-content `case`, and a
   home-grid summary card (saved count + `next_best_action`'s title, linked).
   Enqueue widened to the `?tab=discounts` dashboard URL. `discounts.js`
   sends the Access Folder's full current state on every toggle (the AJAX
   handler expects that, not a diff) and the status `<select>`'s change event.
   `php -l` clean on all six touched/new files.
   **Not built:** the status note field (`_sla_discount_status[id].note` is
   written by the handler but nothing in the UI collects one yet — plan §4
   defines the shape, this step didn't need to fill every field of it), and
   the "possible"/"not_in_region" buckets aren't rendered anywhere yet (only
   `likely`'s count surfaces, in the one-line summary) — a fuller eligibility
   view showing all three is a small follow-up once there's a real user
   testing it, not blocking anything else.
6. Tier 1 modal test in a real browser; fallback.
7. WaterSure supplier list; VAT PDF pre-fill.
8. Menu, Customizer toggles, cache purge, smoke list.

Steps 2 to 5 are each one commit and one deploy; every one degrades to "nothing
rendered" if its data is missing.

---

## 11. Content beyond the PDF

A separate research pass on 2 September 2026 looked for schemes the guide missed.
Its verified additions are appended to the seed under `schemes_additional` with the
same fields and a `source: "research 2026-09-02"` marker, so the import can take or
leave them. See §12.

## 12. Additional schemes

Research pass completed 2026-09-03. 8 schemes verified live and appended to
the seed under `schemes_additional` (same record shape as `schemes`, each
carrying `"source": "research 2026-09-03"`). No new taxonomy term or
`eligibility_signals` key was needed — every one maps onto the existing
category/region/signal vocabulary in §5/§6.

| Scheme | What's new | Tier |
|---|---|---|
| Council Tax Disabled Band Reduction Scheme | Bill charged at the band below (or 17% off for Band A) for a home adapted for a disabled resident | 3 |
| Motability Scheme | Lease a car/WAV/scooter/powered wheelchair using enhanced-rate PIP/DLA/ADP mobility | 3 |
| Disabled Facilities Grant | Council grant up to £30k (England) / £36k (Wales) / £25k (NI) for home adaptations | 3 |
| Warm Home Discount | £150 one-off electricity bill discount; high-cost-to-heat test now removed for 2026/27 | 3 |
| London Congestion Charge & ULEZ disability exemptions | Free/exempt driving in London for Blue Badge holders and disabled-tax-class or mobility-benefit vehicles (ULEZ routes expire Oct 2027) | 2 |
| Disabled Students' Allowances (DSA) | Up to £27,783/year non-repayable, non-means-tested study support | 2 |
| Carer's Allowance | £86.45/week for a family member/friend caring 35+ hrs/week for someone with IBD — note this is the *carer's* benefit, keyed off the patient's own pip/dla/aa/adp signal since the matcher (§6) has no "has a carer" flag yet | 2 |
| Priority Services Register | Free extra support (power-cut priority, accessible bills, etc.) from energy/water suppliers | 3 |

**Rejected** (verified against a live page but didn't clear the bar, or couldn't
be verified at all — don't re-research these without a new angle):

- **Mobile/broadband social tariffs** — real and relevant, but Ofcom's
  social-tariffs page 403'd every fetch attempt (Cloudflare bot check), and
  there's no stable gov.uk page to cite instead. Worth retrying with the same
  browser-UA approach `inc/discount-check.php` already uses for
  nationaltrust.org.uk/londonzoo.org/kew.org/nhsbsa.nhs.uk.
- **TV Licence concession** — real, but for sight impairment, not IBD; no
  connection to this audience.
- **TfL Freedom Pass** — this is London boroughs' funding of the same national
  scheme already in the seed as `disabled-bus-pass`; a separate entry would
  duplicate it under a different brand name.
- **Household Support Fund** — each council sets its own rules/amounts with no
  stable end date; too locally variable to state one fixed value/eligibility.
- **CCUK "financial support" page** — a signposting hub to PIP/UC/Turn2us
  (already covered elsewhere), not a scheme in its own right. Their Local
  Grants fund is for organisations, not individuals.
- **Supermarket priority delivery slots** — an operational accommodation, not
  a price discount; no official page, criteria vary per retailer.
- **Gym/leisure-centre disability concessions** — real but per-council/trust
  with no UK-wide page to verify against; would need one entry per operator.
