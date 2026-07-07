# Sub-category grouping + per-sub-category layouts — sla-health-hub (2026-06-03)

Adds sub-category grouping to the **Clinical Reviews** (`content-clinical-reviews`)
and **Gastro Living** (`content-gastro-living`) category pages. Each sub-category
(child category) now renders as its own block with an editable description and a
Customizer-selectable layout: **Standard Grid, Bento, Asymmetric, or Posters**.

Edited in place. No setting IDs were reused/renamed, so nothing existing is affected.

## Files added
- `category-content-clinical-reviews.php` — category template (WP hierarchy auto-routes
  the `content-clinical-reviews` slug to it; falls through to `archive.php` for all
  other categories, unchanged).
- `category-content-gastro-living.php` — same, for `content-gastro-living`.
- `template-parts/subcategory-grouped-archive.php` — shared body used by both: hero
  (mirrors `archive.php`), buckets the query's posts by child category, renders each
  group's description + chosen layout, and a "More Articles" fallback for posts that
  sit only in the parent category. Degrades to a single standard grid if the parent
  has no child categories.

## Files changed
- `functions.php`
  - New helpers (after `vance_sanitize_checkbox`): `vance_subcat_layout_choices()`,
    `vance_sanitize_subcat_layout()`, `vance_grouped_archive_parent_slugs()`
    (filterable — extend to roll out to more categories),
    `vance_get_subcat_layout()`, `vance_get_subcat_description()`.
  - New Customizer section **"Sub-Category Layouts"** (priority 34.5) inside the
    existing **Content & Knowledge Base** panel. For every child category of the two
    parents it registers two controls keyed by term id:
    `vance_subcat_layout_{term_id}` (select) and `vance_subcat_desc_{term_id}`
    (textarea; blank = use the category's own description). Keying by term id means
    values survive category renames.
- `assets/css/main.css` — appended one commented block: `.va-subcat-group` /
  `.va-subcat-head` / `.va-subcat-desc`, the four layouts
  (`.va-layout-grid|bento|asymmetric|posters`), the poster card, and responsive
  rules. All new classes are namespaced `va-`/`va-subcat-`/`va-layout-`/`va-poster-`
  — no collisions with existing styles.

## How to use (admin)
1. **Posts → Categories**: create child categories under *Clinical Reviews* and
   *Gastro Living* and assign posts to them.
2. **Appearance → Customize → Content & Knowledge Base → Sub-Category Layouts**:
   pick a layout and (optionally) write a description for each sub-category.
3. Visit the Clinical Reviews / Gastro Living pages — articles are now grouped, each
   group using its chosen layout.

## Notes / caveats
- No dedicated `category.php` exists in the theme, so other categories keep using
  `archive.php` exactly as before — this change is scoped to the two slugs only.
- Layout defaults to **Standard Grid** for any sub-category not yet configured.
- Pagination is preserved (`the_posts_pagination()`); each page's posts are bucketed.
- Could not run `php -l` (no PHP in the build environment). Verified manually:
  PHP open/close tags balance (47/47), brace balance (28/28), alternative-syntax
  control structures pair up (6 `endif`, 1 `endforeach`, 1 `endwhile`, 2 `else`),
  and `setup_postdata`/`wp_reset_postdata` are paired (2/2). Recommend a quick pass
  through an online PHP linter or a staging deploy before production.

## Reverting
Delete the three new files and remove the three appended blocks
(`functions.php` helpers, `functions.php` "Sub-Category Layouts" section,
`main.css` trailing block). No data migration needed.
