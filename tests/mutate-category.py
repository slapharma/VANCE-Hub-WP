#!/usr/bin/env python
"""Break inc/category-hero.php (and the templates around it) on purpose, and
confirm category-hero.test.php goes red for each break.

A check that has never been observed failing has not been tested, only run.
Every line below must say `went RED`. Three failure modes to watch for:

  *** STAYED GREEN ***      the suite cannot detect that bug. Fix the test.
  SKIP (pattern not found)  the mutant's search string has drifted from the
                            source, so it is silently testing nothing.
  AMBIGUOUS (n matches)     same problem wearing a different hat. Narrow it.

Everything is restored in a finally: block, including on exception. If this is
interrupted, check `git diff` before doing anything else.

Like mutate-gi.py and mutate-legal.py this mutates the TEMPLATES as well as the
renderer -- but the assertions it is proving there are weaker, and knowing why
matters. An archive template cannot be included and executed by the suite (it
needs a live query, get_header() and a post loop), so section 9 reads their
source instead. The template mutants below therefore prove exactly two things:
that a commented-out call is caught (the suite strips comments before looking),
and that a template re-growing the dark band is caught. They do NOT prove the
call is reached at runtime. That gap is real; it is the reason the deploy
checklist in the file header ends at a live page.
"""

from __future__ import print_function
import io, os, subprocess, sys

HERE  = os.path.dirname(os.path.abspath(__file__))
THEME = os.path.join(os.path.dirname(HERE), 'wp-content', 'themes', 'vance-health-hub')

RENDERER  = os.path.join(THEME, 'inc', 'category-hero.php')
ARCHIVE   = os.path.join(THEME, 'archive.php')
GROUPED   = os.path.join(THEME, 'template-parts', 'subcategory-grouped-archive.php')
NEWS      = os.path.join(THEME, 'category-content-healthcare-news.php')
FUNCTIONS = os.path.join(THEME, 'functions.php')
MAINCSS   = os.path.join(THEME, 'assets', 'css', 'main.css')

# (file, description, find, replace)
MUTANTS = [

    # ---- copy, and the pristine case -----------------------------------
    # The failure mode this whole class exists for: an empty default renders
    # blank on the live site and perfectly in the Customizer preview, which
    # serves the registered default instead.
    (RENDERER,
     "the Clinical Reviews lede default is emptied",
     "'intro'   => __( 'Trial data and peer-reviewed papers read closely and written up in plain English — what was measured, in whom, and what the result does and does not show.', 'vance-health-hub' ),",
     "'intro'   => '',"),

    (RENDERER,
     "a card loses its heading",
     "'title' => __( 'Every review points back at the paper', 'vance-health-hub' ),",
     "'title' => '',"),

    (RENDERER,
     "the eyebrow stops naming the KIND and repeats the section name instead",
     "'eyebrow' => __( 'The evidence', 'vance-health-hub' ),",
     "'eyebrow' => __( 'Clinical Reviews', 'vance-health-hub' ),"),

    (RENDERER,
     "two sections end up declaring the same photograph",
     "'image'   => 'healthcare-news.jpg',",
     "'image'   => 'clinical-reviews.jpg',"),

    (RENDERER,
     "a card names an icon the icon set does not draw",
     "'icon'  => 'people',",
     "'icon'  => 'peple',"),

    (RENDERER,
     "a focal point is written as a bare number rather than an object-position pair",
     "'focal'   => '54% 24%',",
     "'focal'   => '54',"),

    # ---- the band ------------------------------------------------------
    (RENDERER,
     "the total reverts to $term->count, which misses every child's posts",
     "\t$q = new WP_Query( array(\n\t\t'cat'                    => $term_id,",
     "\t$q = new WP_Query( array(\n\t\t'category__in'           => array( $term_id ),\n\t\t'cat_disabled'           => $term_id,"),

    (RENDERER,
     "an empty section renders a band saying 0 articles",
     "\tif ( $facts['total'] > 0 ) {",
     "\tif ( true ) {"),

    (RENDERER,
     "a section with no sub-sections shows a Topics cell reading 0",
     "\tif ( $facts['topics'] > 0 ) {",
     "\tif ( true ) {"),

    (RENDERER,
     "the plural rule is dropped, so one article reads 'Articles'",
     "'key'   => _n( 'Article', 'Articles', $facts['total'], 'vance-health-hub' ),",
     "'key'   => __( 'Articles', 'vance-health-hub' ),"),

    (RENDERER,
     "the band's cells become anchors, which the shared CSS gives hover and arrow affordances to",
     '<div class="vhh-hero-spotlight__line">',
     '<a class="vhh-hero-spotlight__line" href="#">'),

    (RENDERER,
     "the band loses the shared lines class, so cells lose the tile, type and dividers",
     '__slot--lines vhh-hero-spotlight__slot--facts',
     '__slot--facts'),

    # ---- sub-categories ------------------------------------------------
    (RENDERER,
     "a sub-category stops inheriting the parent's registry entry",
     "\t$key  = $parent ? $parent->slug : $term->slug;",
     "\t$key  = $term->slug;"),

    (RENDERER,
     "the breadcrumb is dropped, so there is no way back up to the parent",
     "\t\t\t\t<?php if ( $parent ) : ?>",
     "\t\t\t\t<?php if ( false ) : ?>"),

    (RENDERER,
     "the breadcrumb stops being a named landmark",
     'aria-label="<?php esc_attr_e( \'Breadcrumb\', \'vance-health-hub\' ); ?>"',
     ''),

    (RENDERER,
     "the current page loses aria-current",
     '<span aria-current="page">',
     '<span>'),

    (RENDERER,
     "a sub-category's eyebrow reverts to the parent NAME the crumb already carries",
     "$eyebrow = $meta['eyebrow'];",
     "$eyebrow = $parent ? vance_category_hero_term_name( $parent ) : $meta['eyebrow'];"),

    (RENDERER,
     "a sub-category with no description of its own is given its PARENT's lede",
     "\tif ( $intro === '' && $meta && ! $parent ) {",
     "\tif ( $intro === '' && $meta ) {"),

    # ---- term names: the double-escape that shipped once ---------------
    (RENDERER,
     "the headline escapes the stored name directly, printing 'Food &amp;amp; Nutrition'",
     "\t\t$title_html = esc_html( vance_category_hero_term_name( $term ) );",
     "\t\t$title_html = esc_html( $term->name );"),

    (RENDERER,
     "the breadcrumb and the headline disagree about escaping again",
     "\t\t\t\t\t\techo esc_html( vance_category_hero_term_name( $term ) ); ?></span>",
     "\t\t\t\t\t\techo esc_html( $term->name ); ?></span>"),

    (RENDERER,
     "the decoder stops decoding",
     "\treturn html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );",
     "\treturn $term->name;"),

    # ---- photographs ----------------------------------------------------
    (RENDERER,
     "the file_exists guard goes, so a section with no photograph emits a 404 <img>",
     "\tif ( ! file_exists( $file ) ) { return null; }",
     "",),

    (RENDERER,
     "a sub-category stops inheriting the parent's uploaded photograph",
     "\tforeach ( array_filter( array( $term, $parent ) ) as $candidate ) {",
     "\tforeach ( array_filter( array( $term ) ) as $candidate ) {"),

    (RENDERER,
     "the legacy dark-band image leaks onto the pale band",
     "\t\t$saved = vance_get_theme_mod( \"vance_cat_photo_{$candidate->term_id}\", '' );",
     "\t\t$saved = vance_get_theme_mod( \"vance_cat_photo_{$candidate->term_id}\", '' );\n\t\tif ( $saved === '' ) { $saved = vance_get_theme_mod( \"vance_cat_hero_{$candidate->term_id}\", '' ); }"),

    (RENDERER,
     "an admin-uploaded photograph is given the registry's alt text, describing a different picture",
     "\t\t\treturn array( 'src' => $saved, 'alt' => '', 'focal' => $focal );",
     "\t\t\treturn array( 'src' => $saved, 'alt' => 'A researcher at a bright desk comparing two printed papers side by side', 'focal' => $focal );"),

    (RENDERER,
     "the motif branch goes, so a section with no photograph has an empty media column",
     "\t\t<div class=\"vhh-hero-spotlight__motif\" aria-hidden=\"true\"><?php",
     "\t\t<div class=\"vhh-hero-spotlight__nothing\"><?php"),

    (RENDERER,
     "the motif is announced to screen readers as if it meant something",
     'class="vhh-hero-spotlight__motif" aria-hidden="true"',
     'class="vhh-hero-spotlight__motif"'),

    (RENDERER,
     "the motif's gradient ids collide with inc/page-hero-spotlight.php's",
     'id="vhhCatBloom"',
     'id="vhhPageBloom"'),

    # ---- Customizer overrides -------------------------------------------
    (RENDERER,
     "the Tagline control stops overriding the eyebrow",
     "\t$eyebrow = vance_get_theme_mod( \"vance_cat_tagline_{$term->term_id}\", '' );",
     "\t$eyebrow = '';"),

    (RENDERER,
     "the key the Customizer actually registers stops being read, as before this change",
     "\t\t$title = vance_get_theme_mod( \"vance_cat_title_{$term->term_id}\", '' );",
     "\t\t$title = '';"),

    (RENDERER,
     "the key the old dark heroes read stops being honoured",
     "\t$title = vance_get_theme_mod( \"vance_cat_hero_title_override_{$term->term_id}\", '' );",
     "\t$title = '';"),

    (RENDERER,
     "the term description stops beating the registry lede",
     "\t$intro = trim( wp_strip_all_tags( term_description( $term ) ) );",
     "\t$intro = '';"),

    (RENDERER,
     "HTML in a term description is printed rather than stripped",
     "\t$intro = trim( wp_strip_all_tags( term_description( $term ) ) );",
     "\t$intro = trim( term_description( $term ) );"),

    # ---- what it must refuse --------------------------------------------
    (RENDERER,
     "the taxonomy guard goes, so a tag archive gets a category hero",
     "\tif ( ! ( $term instanceof WP_Term ) || $term->taxonomy !== 'category' ) { return false; }",
     "\tif ( ! ( $term instanceof WP_Term ) ) { return false; }"),

    (RENDERER,
     "a category the registry has never heard of loses its card",
     "\t$card = ( $meta && ! empty( $meta['card'] ) ) ? $meta['card'] : array(",
     "\t$card = ( $meta && ! empty( $meta['card'] ) ) ? $meta['card'] : array( 'icon' => '', 'title' => '', 'text' => '' ); $unused = array("),

    (RENDERER,
     "an unknown icon key returns a broken empty <svg> instead of nothing",
     "\tif ( ! isset( $paths[ $name ] ) ) { return ''; }",
     "\tif ( ! isset( $paths[ $name ] ) ) { $paths[ $name ] = ''; }"),

    # ---- the stylesheet --------------------------------------------------
    (RENDERER,
     "the print-once guard goes, so a template calling the hero twice emits two stylesheets",
     "\tstatic $done = false;\n\tif ( $done ) { return; }\n\t$done = true;",
     "\t$done = false;"),

    (RENDERER,
     "a radius is hard-coded instead of taking the token scale (CLAUDE.md §5)",
     "    border-bottom: 1px solid transparent;",
     "    border-bottom: 1px solid transparent;\n    border-radius: 4px;"),

    (RENDERER,
     "the breadcrumb's own rules go, so it renders as unstyled inline text",
     ".vhh-hero-spotlight__crumb {",
     ".vhh-hero-spotlight__crumb-DISABLED {"),

    # ---- the templates (SOURCE assertions -- see the docstring) ----------
    (ARCHIVE,
     "archive.php stops calling the renderer",
     "        ? vance_render_category_hero()",
     "        ? false"),

    (ARCHIVE,
     "archive.php calls it but no longer gates the old dark band on the result",
     "    if ( ! $vance_arch_hero_done ) :",
     "    if ( true ) :"),

    (GROUPED,
     "the grouped template's call is commented out -- a plain grep would still match",
     "        vance_render_category_hero();",
     "        // vance_render_category_hero();"),

    (GROUPED,
     "the grouped template loses $vance_cat, so every post silently falls into 'ungrouped'",
     "$vance_cat = get_queried_object();",
     "$vance_cat_unused = get_queried_object();"),

    (NEWS,
     "Healthcare News re-grows its own copy of the dark band",
     "<main>\n    <?php\n    if ( function_exists( 'vance_render_category_hero' ) ) {\n        vance_render_category_hero();\n    }\n    ?>",
     "<main>\n    <section class=\"hero\" style=\"height: 350px;\"></section>"),

    (FUNCTIONS,
     "functions.php stops loading the renderer",
     "require_once get_template_directory() . '/inc/category-hero.php';",
     "// require_once get_template_directory() . '/inc/category-hero.php';"),

    (FUNCTIONS,
     "the Photograph SETTING is registered under a key the renderer does not read",
     'add_setting( "vance_cat_photo_{$cat->term_id}"',
     'add_setting( "vance_cat_photo_LEGACY_{$cat->term_id}"'),

    (FUNCTIONS,
     "the setting exists but no control is attached, so the field never appears",
     'WP_Customize_Image_Control( $wp_customize, "vance_cat_photo_{$cat->term_id}"',
     'WP_Customize_Image_Control( $wp_customize, "vance_cat_hero_{$cat->term_id}"'),

    # ---- the CSS this hero leans on -------------------------------------
    (MAINCSS,
     "main.css loses the icon tile the band's cells are built from",
     ".vhh-hero-spotlight__line-ico {",
     ".vhh-hero-spotlight__line-ico-DISABLED {"),

    (MAINCSS,
     "main.css scopes the hover fill to every cell, not just links",
     "a.vhh-hero-spotlight__line:hover { background: #F3F9F9; }",
     ".vhh-hero-spotlight__line:hover { background: #F3F9F9; }"),

    (MAINCSS,
     "a structural class quietly gains rules, so the allowlist exemption is stale",
     ".vhh-hero-spotlight__card-icon {",
     ".vhh-hero-spotlight__card-body { color: red; }\n.vhh-hero-spotlight__card-icon {"),
]

_CRLF = {}


def read(path):
    with io.open(path, encoding='utf-8', newline='') as fh:
        text = fh.read()
    _CRLF[path] = '\r\n' in text
    return text.replace('\r\n', '\n')


def write(path, text):
    if _CRLF.get(path):
        text = text.replace('\n', '\r\n')
    with io.open(path, 'w', encoding='utf-8', newline='') as fh:
        fh.write(text)


def run_suite():
    return subprocess.call(
        ['php', os.path.join(HERE, 'category-hero.test.php')],
        stdout=open(os.devnull, 'w'), stderr=subprocess.STDOUT)


def main():
    if run_suite() != 0:
        print('The suite is already RED before any mutation. Fix that first.')
        return 1

    originals = {}
    for path, _, _, _ in MUTANTS:
        originals.setdefault(path, read(path))

    stayed_green = 0
    skipped = 0
    try:
        for path, desc, find, repl in MUTANTS:
            src = originals[path]
            hits = src.count(find)
            if hits == 0:
                print('  SKIP (pattern not found)  %s' % desc)
                skipped += 1
                continue
            if hits > 1:
                print('  AMBIGUOUS (%d matches)     %s' % (hits, desc))
                skipped += 1
                continue
            write(path, src.replace(find, repl, 1))
            rc = run_suite()
            write(path, src)
            if rc != 0:
                print('  went RED                  %s' % desc)
            else:
                print('  *** STAYED GREEN ***      %s' % desc)
                stayed_green += 1
    finally:
        for path, text in originals.items():
            write(path, text)

    print('')
    if stayed_green or skipped:
        print('%d stayed green, %d skipped. Neither is acceptable.' % (stayed_green, skipped))
        return 1
    print('All %d mutants went red.' % len(MUTANTS))
    return 0


if __name__ == '__main__':
    sys.exit(main())
