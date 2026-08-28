#!/usr/bin/env python
"""Break inc/legal-hero.php (and the templates that call it) on purpose, and
confirm legal-hero.test.php goes red for each break.

A check that has never been observed failing has not been tested, only run.
Every line below must say `went RED`. Two failure modes to watch for:

  *** STAYED GREEN ***    the suite cannot detect that bug. Fix the test.
  SKIP (pattern not found)  the mutant's search string has drifted from the
                            source, so it is silently testing nothing.

Everything is restored in a finally: block, including on exception. If this is
interrupted, check `git diff` before doing anything else.
"""

from __future__ import print_function
import io, os, subprocess, sys

HERE  = os.path.dirname(os.path.abspath(__file__))
THEME = os.path.join(os.path.dirname(HERE), 'wp-content', 'themes', 'vance-health-hub')

RENDERER = os.path.join(THEME, 'inc', 'legal-hero.php')
TERMS    = os.path.join(THEME, 'page-terms-of-use.php')
PAGE     = os.path.join(THEME, 'page.php')

# (file, description, find, replace)
MUTANTS = [
    (RENDERER,
     "the intro default is emptied -- the bug that shipped the About hero blank",
     "'intro'   => __( 'We are committed to protecting your personal data and privacy. This policy explains how we collect, use, and protect your data.', 'vance-health-hub' ),",
     "'intro'   => '',"),

    (RENDERER,
     "the superseded Privacy wording is restored alongside the client's",
     "'intro'   => __( 'We are committed to protecting your personal data and privacy. This policy explains how we collect, use, and protect your data.', 'vance-health-hub' ),",
     "'intro'   => __( 'We are committed to protecting your personal data and your right to privacy. This policy explains how we collect, use, and safeguard your information.', 'vance-health-hub' ),"),

    (RENDERER,
     "the Accessibility intro creeps back to its four-line length",
     "'intro'   => __( 'This statement sets out the standards we hold, and how to tell us when something does not work for you.', 'vance-health-hub' ),",
     "'intro'   => __( 'We want everyone to be able to use Vance Medical Hub. This statement sets out the standard we hold the site to, where we currently fall short, and how to tell us when something does not work for you.', 'vance-health-hub' ),"),

    (RENDERER,
     "the headline cap goes back to a value that wraps the longest title",
     ".vhh-hero-spotlight--legal .vhh-hero-spotlight__title {\n    max-width: 640px;\n}",
     ".vhh-hero-spotlight--legal .vhh-hero-spotlight__title {\n    max-width: 520px;\n}"),

    (RENDERER,
     "the contact email is read with an '' default instead of the theme's",
     "vance_get_theme_mod( 'vance_contact_email', 'team@vancemedicalfoods.co.uk' )",
     "vance_get_theme_mod( 'vance_contact_email', '' )"),

    (RENDERER,
     "a document IS sold back to itself in its own band",
     "\t\tif ( $key === $doc ) {\n\t\t\tcontinue;\n\t\t}",
     "\t\tif ( false ) {\n\t\t\tcontinue;\n\t\t}"),

    (RENDERER,
     "the pre-rebrand 'Gastro Health Hub' comes back to the Terms intro",
     "'Please read these terms carefully before using Vance Medical Hub.",
     "'Please read these terms carefully before using the Gastro Health Hub platform.",),

    (RENDERER,
     "a photograph is emitted instead of the motif",
     '<div class="vhh-hero-spotlight__motif" aria-hidden="true">',
     '<div class="vhh-hero-spotlight__media"><img src="/assets/img/x.jpg" alt=""></div><div class="vhh-hero-spotlight__motif" aria-hidden="true">'),

    (RENDERER,
     "a band class is misspelt, so it matches no rule",
     'vhh-hero-spotlight__slot--docs">',
     'vhh-hero-spotlight__slot--doc">'),

    (RENDERER,
     "the 2x2 rule loses its min-width scope, so the band never stacks on a phone",
     "@media (min-width: 901px) {\n    .vhh-hero-spotlight__slot--docs {",
     "@media (min-width: 1px) {\n    .vhh-hero-spotlight__slot--docs {"),

    (RENDERER,
     "the mobile top padding main.css zeroes for the photograph is not restored",
     "    .vhh-hero-spotlight--legal {\n        padding: 40px 0 44px;\n    }",
     "    .vhh-hero-spotlight--legal {\n        margin: 0;\n    }"),

    (RENDERER,
     "the stylesheet is printed on every render, not once",
     "\tstatic $done = false;\n\tif ( $done ) { return; }",
     "\tstatic $done = false;\n\tif ( false ) { return; }"),

    (RENDERER,
     "an empty title override blanks the headline instead of falling back",
     "if ( isset( $overrides[ $field ] ) && $overrides[ $field ] !== '' ) {",
     "if ( isset( $overrides[ $field ] ) ) {"),

    (RENDERER,
     "the slug lookup matches anything, so every page becomes a policy page",
     "\t\tif ( $d['slug'] === $slug ) {",
     "\t\tif ( true ) {"),

    (RENDERER,
     "the 760px measure is dropped, so the Cookie Policy runs full-width again",
     ".legal-wrap {\n    max-width: 760px;",
     ".legal-wrap {\n    max-width: 1200px;"),

    (RENDERER,
     "the Complianz guard goes, so long cookie names blow the grid out again",
     ".legal-wrap .cookies-per-purpose > * {\n    overflow-wrap: anywhere;",
     ".legal-wrap .cookies-per-purpose > * {\n    overflow-wrap: break-word;"),

    (TERMS,
     "a template stops printing the shared block early, so the box rules lose",
     "require_once get_template_directory() . '/inc/legal-hero.php';\nvance_legal_hero_styles();",
     "require_once get_template_directory() . '/inc/legal-hero.php';"),

    (PAGE,
     "the Cookie Policy body goes back to the generic full-width container",
     '<div class="legal-wrap">\n        <article id="post-',
     '<div class="container" style="padding: 60px 20px;">\n        <article id="post-'),

    (TERMS,
     "a template stops calling the renderer, so it ships with no hero at all",
     "vance_render_legal_hero( 'terms' );",
     "// vance_render_legal_hero( 'terms' );"),

    (PAGE,
     "page.php stops recognising the Cookie Policy, so it keeps the dark hero",
     "$vance_legal_doc = vance_legal_hero_doc_for_slug(",
     "$vance_legal_doc = ''; $unused = vance_legal_hero_doc_for_slug("),
]


def run_suite():
    p = subprocess.Popen([sys.executable and 'php', 'legal-hero.test.php'],
                         cwd=HERE, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    p.communicate()
    return p.returncode


def read(path):
    with io.open(path, encoding='utf-8', newline='') as fh:
        return fh.read()


def write(path, text):
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
            if find not in src:
                print('  SKIP (pattern not found)  %s' % desc)
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

    print('\n  %d mutants, %d stayed green, %d skipped' %
          (len(MUTANTS), stayed_green, skipped))

    if run_suite() != 0:
        print('  !! the suite is RED after restore -- check git diff')
        return 1

    return 1 if (stayed_green or skipped) else 0


if __name__ == '__main__':
    sys.exit(main())
