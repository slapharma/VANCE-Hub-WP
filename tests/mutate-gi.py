#!/usr/bin/env python
"""Break inc/gi-hero.php (and the files around it) on purpose, and confirm
gi-hero.test.php goes red for each break.

A check that has never been observed failing has not been tested, only run.
Every line below must say `went RED`. Three failure modes to watch for:

  *** STAYED GREEN ***      the suite cannot detect that bug. Fix the test.
  SKIP (pattern not found)  the mutant's search string has drifted from the
                            source, so it is silently testing nothing.
  AMBIGUOUS (n matches)     same problem wearing a different hat. Narrow it.

Everything is restored in a finally: block, including on exception. If this is
interrupted, check `git diff` before doing anything else.

Like mutate-legal.py, this mutates the TEMPLATES as well as the renderer. That
is what proves section 11 has to INCLUDE them rather than grep them: a grep for
"vance_render_gi_hero" still matches when the call is commented out.
"""

from __future__ import print_function
import io, os, subprocess, sys

HERE  = os.path.dirname(os.path.abspath(__file__))
THEME = os.path.join(os.path.dirname(HERE), 'wp-content', 'themes', 'vance-health-hub')

RENDERER   = os.path.join(THEME, 'inc', 'gi-hero.php')
CUSTOMIZER = os.path.join(THEME, 'inc', 'customizer-gi-health.php')
HUB_TMPL   = os.path.join(THEME, 'page-gi-health.php')
COND_TMPL  = os.path.join(THEME, 'page-gi-condition.php')
FUNCTIONS  = os.path.join(THEME, 'functions.php')

# (file, description, find, replace)
MUTANTS = [

    # ---- copy, and the pristine case -----------------------------------
    (RENDERER,
     "the Crohn's intro default is emptied -- the bug that shipped About blank",
     "'intro'   => __( 'A form of IBD that can inflame any part of the gut, from mouth to anus, most often the end of the small intestine. With modern treatment, most people manage their symptoms well.', 'vance-health-hub' ),",
     "'intro'   => '',"),

    (RENDERER,
     "the lobby heading default is emptied",
     "'heading'   => __( 'Seven gut conditions, clearly explained', 'vance-health-hub' ),",
     "'heading'   => '',"),

    (RENDERER,
     "the straight-quote normalisation is dropped, so the H1 and intro disagree",
     "\treturn str_replace( \"'\", '’', $label );",
     "\treturn $label;"),

    # ---- the band ------------------------------------------------------
    (RENDERER,
     "a condition IS sold back to itself in its own band",
     "\tforeach ( vance_gi_hero_related_slugs( $slug ) as $rel ) {",
     "\tforeach ( array_merge( array( $slug ), vance_gi_hero_related_slugs( $slug ) ) as $rel ) {"),

    (RENDERER,
     "the lobby cell is dropped, so there is no way back from a condition page",
     "\t$cells[] = array(\n\t\t'icon'  => 'grid',",
     "\tif ( false ) $cells[] = array(\n\t\t'icon'  => 'grid',"),

    (RENDERER,
     "IBS reverts to the old drifted trio the foot-of-page block used to carry",
     "'related' => array( 'microscopic-colitis', 'diverticular-disease', 'inflammatory-bowel-disease' ),",
     "'related' => array( 'inflammatory-bowel-disease', 'microscopic-colitis', 'colorectal-cancer' ),"),

    (RENDERER,
     "a related slug is misspelt, which used to emit a cell with a raw slug in it",
     "'related' => array( 'ulcerative-colitis', 'crohns-disease', 'irritable-bowel-syndrome' ),",
     "'related' => array( 'ulcerative-colitis', 'crohns-disease', 'irritable-bowel-syndromee' ),"),

    (RENDERER,
     "the eyebrow stops naming the family and repeats the page name instead",
     "'eyebrow' => __( 'Functional gut disorder', 'vance-health-hub' ),",
     "'eyebrow' => __( 'Irritable Bowel Syndrome', 'vance-health-hub' ),"),

    # ---- the lobby's chips ---------------------------------------------
    (RENDERER,
     "the chip split becomes 3 + 4, so the wide row is the second one",
     "$rows  = array( array_slice( $slugs, 0, 4 ), array_slice( $slugs, 4 ) );",
     "$rows  = array( array_slice( $slugs, 0, 3 ), array_slice( $slugs, 3 ) );"),

    (RENDERER,
     "the chips go back to one wrapping flow, which is what gave three rows",
     "\t$rows  = array( array_slice( $slugs, 0, 4 ), array_slice( $slugs, 4 ) );",
     "\t$rows  = array( $slugs );"),

    (RENDERER,
     "an icon creeps back into the chips, which is what would not fit two rows",
     "<a class=\"vhh-hero-spotlight__chip\" href=\"<?php echo esc_url( vance_gi_page_url( $slug ) ); ?>\">\n\t\t\t\t\t\t\t\t<span>",
     "<a class=\"vhh-hero-spotlight__chip\" href=\"<?php echo esc_url( vance_gi_page_url( $slug ) ); ?>\"><?php echo vance_gi_hero_icon( 'grid' ); ?>\n\t\t\t\t\t\t\t\t<span>"),

    (RENDERER,
     "the chip rows stop wrapping, which pushed chips out of the band mid-width",
     ".vhh-hero-spotlight__chip-row {\n    display: flex;\n    flex-wrap: wrap;\n    gap: 8px;\n}",
     ".vhh-hero-spotlight__chip-row {\n    display: flex;\n    gap: 8px;\n}"),

    (RENDERER,
     "the chip padding is trimmed, dropping it under the 44px touch target",
     "    padding: 13px 14px;\n    border-radius: var(--radius-control, 6px);",
     "    padding: 10px 14px;\n    border-radius: var(--radius-control, 6px);"),

    (RENDERER,
     "the chip label shrinks, which also drops it under 44px",
     "    font-size: 13px;\n    font-weight: 600;\n    line-height: 1.25;",
     "    font-size: 12px;\n    font-weight: 600;\n    line-height: 1.1;"),

    # ---- purple, and its two jobs --------------------------------------
    (RENDERER,
     "the eyebrow takes the chips' pale border, which cannot carry 3:1 on mint",
     "    border-color: <?php echo esc_attr( VANCE_GI_PURPLE_EDGE ); ?>;",
     "    border-color: <?php echo esc_attr( VANCE_GI_PURPLE_LINE ); ?>;"),

    (RENDERER,
     "the eyebrow is left teal, so purple stops belonging to the whole set",
     ".vhh-hero-spotlight--gi .vhh-hero-spotlight__eyebrow {",
     ".vhh-hero-spotlight--gi .vhh-hero-spotlight__eyebrow-DISABLED {"),

    (RENDERER,
     "the CTA is left on the committed purple, competing with the chips",
     "    --vhh-hs-cta-bg: #04504E;",
     "    --vhh-hs-cta-bg: #6B489E;"),

    (RENDERER,
     "the chip label loses its deep ink and inherits, at ~1.5:1 on the tint",
     "    color: <?php echo esc_attr( VANCE_GI_PURPLE_INK ); ?>;\n    white-space: nowrap;",
     "    white-space: nowrap;"),

    # ---- the photograph -------------------------------------------------
    # Deliberately stops at the dot and names no extension. As
    # "...lobby-walk.jpg" this mutant SKIPped from 04882ed (the WebP
    # conversion) until 2026-09-01 -- the single most important mutant in this
    # file, silently not running, while the checks it guards had themselves
    # gone unfailable for the same reason. Matching the stem survives the next
    # format change too.
    (RENDERER,
     "the lobby goes back to borrowing the IBD card's own photograph",
     "'/assets/img/gi-health/lobby-walk.",
     "'/assets/img/gi-health/ibd."),

    # The rot that started all of this: an image the registry names but which
    # is not on disk. vance_gi_hero_photo() returns null, the media slot is
    # dropped, and the page renders a hero with no photograph and no error.
    (FUNCTIONS,
     "a condition photograph is named but not on disk",
     "'image' => 'colorectal-cancer.webp',",
     "'image' => 'colorectal-cancer-v2.webp',"),

    # There was no mutant for the override branch itself, only for the alt text
    # it returns. Added when 'crc: theme asset is dropped' turned out to have
    # been unfailable since the WebP conversion -- it is the check this breaks,
    # so it needs something that can break it.
    (RENDERER,
     "an admin-chosen photograph is ignored and the theme asset renders anyway",
     "if ( $saved !== '' ) {",
     "if ( false ) {"),

    (RENDERER,
     "an admin-supplied photo keeps the stock alt text, describing another picture",
     "return array( 'src' => $saved, 'alt' => '', 'focal' => $focal );",
     "return array( 'src' => $saved, 'alt' => $alt, 'focal' => $focal );"),

    (RENDERER,
     "the mtime cache-bust is dropped, so a swapped photo never reaches anyone",
     "'src'   => add_query_arg( 'v', filemtime( $file ), $src ),",
     "'src'   => $src,"),

    (RENDERER,
     "a focal point drifts left, into the part of the frame that is dissolved",
     "'focal'   => '60% 26%',\n\t\t),\n\t\t'diverticular-disease'",
     "'focal'   => '20% 26%',\n\t\t),\n\t\t'diverticular-disease'"),

    (CUSTOMIZER,
     "the focal sanitiser becomes a pass-through, and it is printed into a style attr",
     "\treturn preg_match( '/^\\d{1,3}%\\s+\\d{1,3}%$/', $value ) ? $value : '50% 50%';",
     "\treturn $value;"),

    # ---- the card -------------------------------------------------------
    (RENDERER,
     "the review date is hard-coded, so it is stale the day it ships",
     "$reviewed = vance_get_theme_mod( 'vance_gi_reviewed', '' );",
     "$reviewed = vance_get_theme_mod( 'vance_gi_reviewed', 'August 2026' );"),

    (RENDERER,
     "the card's copy differs on the lobby, so the eight stop reading as one set",
     "esc_html_e( 'Written plainly, checked by a clinician', 'vance-health-hub' );",
     "esc_html_e( 'Written plainly, checked by a clinician.', 'vance-health-hub' );"),

    # ---- admin copy -----------------------------------------------------
    (RENDERER,
     "the saved condition title is ignored, discarding editing work on switch",
     "$title  = $key ? vance_get_theme_mod( \"vance_gi_cond_{$key}_title\", vance_gi_hero_label( $slug ) ) : vance_gi_hero_label( $slug );",
     "$title  = vance_gi_hero_label( $slug );"),

    (RENDERER,
     "the saved condition lede is ignored",
     "$intro  = $key ? vance_get_theme_mod( \"vance_gi_cond_{$key}_lede\", $meta['intro'] ) : $meta['intro'];",
     "$intro  = $meta['intro'];"),

    # ---- resolution -----------------------------------------------------
    (RENDERER,
     "an unknown slug renders the hero anyway instead of falling through",
     "\t$meta = vance_gi_hero_slug_meta( $slug );\n\tif ( ! $meta ) { return false; }",
     "\t$meta = vance_gi_hero_slug_meta( $slug );\n\tif ( ! $meta ) { $meta = current( vance_gi_hero_meta() ); }"),

    (RENDERER,
     "a relative button path is emitted raw, so it resolves against the current page",
     "\t$b1_url = $abs( $b1_url );",
     "\t$b1_url = $b1_url;"),

    # ---- the Customizer's defaults --------------------------------------
    (CUSTOMIZER,
     "the primary button default is typed out again instead of read from the renderer",
     "$wp_customize->add_setting( 'vance_gi_hub_hero_btn1_text', [ 'default' => $hub['btn1_text'], 'sanitize_callback' => 'sanitize_text_field' ] );",
     "$wp_customize->add_setting( 'vance_gi_hub_hero_btn1_text', [ 'default' => 'Explore conditions', 'sanitize_callback' => 'sanitize_text_field' ] );"),

    (RENDERER,
     "the lobby's focal drifts from the one its Customizer control offers",
     "$focal = vance_get_theme_mod( 'vance_gi_hub_hero_focal', '55% 50%' );",
     "$focal = vance_get_theme_mod( 'vance_gi_hub_hero_focal', '30% 50%' );"),

    (CUSTOMIZER,
     "a focal default is typed out and disagrees with the registry",
     "$wp_customize->add_setting( \"{$sec_id}_focal\", [ 'default' => $cond_focal, 'sanitize_callback' => 'vance_gi_sanitize_focal' ] );",
     "$wp_customize->add_setting( \"{$sec_id}_focal\", [ 'default' => '50% 50%', 'sanitize_callback' => 'vance_gi_sanitize_focal' ] );"),

    (CUSTOMIZER,
     "the review date gains a default, so every page claims one nobody set",
     "$wp_customize->add_setting( 'vance_gi_reviewed', [ 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ] );",
     "$wp_customize->add_setting( 'vance_gi_reviewed', [ 'default' => 'August 2026', 'sanitize_callback' => 'sanitize_text_field' ] );"),

    # ---- the templates. A grep would pass all three of these. ------------
    (HUB_TMPL,
     "the lobby's call to the renderer is COMMENTED OUT",
     "\n  vance_render_gi_hub_hero();",
     "\n  // vance_render_gi_hub_hero();"),

    (COND_TMPL,
     "the condition template's call to the renderer is COMMENTED OUT",
     "\n  $vance_gi_hero_done = vance_render_gi_hero( $slug );",
     "\n  $vance_gi_hero_done = false; // vance_render_gi_hero( $slug );"),

    (COND_TMPL,
     "the foot-of-page block goes back to its own hand-maintained related map",
     "    foreach ( vance_gi_hero_related_slugs( $slug ) as $cp_rel ) {",
     "    foreach ( array( 'colorectal-cancer', 'diverticular-disease', 'microscopic-colitis' ) as $cp_rel ) {"),

    # ---- the registry the suite lifts out of functions.php ---------------
    # functions.php is CRLF, so any needle carrying a bare \n can never match
    # it -- the first attempt here reported SKIP for exactly that reason, which
    # is a mutant silently testing nothing. Kept to a single line on purpose:
    # that is newline-agnostic.
    (FUNCTIONS,
     "an eighth condition joins the canonical registry and nobody tells the hero",
     "'diverticular-disease'       => array( 'key' => 'div',",
     "'coeliac-disease' => array( 'key' => 'coe', 'nav' => 'coe', 'label' => 'Coeliac Disease' ), 'diverticular-disease'       => array( 'key' => 'div',"),
]


def run_suite():
    p = subprocess.Popen(['php', 'gi-hero.test.php'],
                         cwd=HERE, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    p.communicate()
    return p.returncode


# This repo is core.autocrlf=true: LF in the object store, CRLF in the working
# copy. So a fresh clone hands you CRLF source, and every multi-line needle
# below -- which is most of them -- would fail to match and report SKIP. A
# mutant that silently tests nothing is the failure this whole runner exists to
# prevent, so matching happens on LF text and the file's own convention is
# restored on write. Files are put back byte-identical either way.
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
