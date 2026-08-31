import pathlib, subprocess, sys, shutil
SRC = pathlib.Path(__file__).resolve().parent.parent / "wp-content/themes/vance-health-hub/inc/page-hero-spotlight.php"
ORIG = SRC.read_text(encoding="utf-8")

MUTANTS = [
 ("toggle default flipped to spotlight",
  "return 'classic';", "return 'spotlight';"),
 ("tel: trunk-zero fix removed",
  "$phone = preg_replace( '/" + chr(92) + "(" + chr(92) + "s*0" + chr(92) + "s*" + chr(92) + ")/', '', $phone );",
  "$phone = $phone;"),
 ("badge visibility switch ignored",
  "if ( $c['slot'] === 'badges' && ! vance_get_theme_mod( 'vance_about_badges_show', true ) ) {",
  "if ( false ) {"),
 # The three copy fields are resolved in one loop now, so this mutant covers
 # eyebrow, headline and intro at once.
 ("the copy stops reading the classic keys",
  ": vance_get_theme_mod( $c[ 'legacy_' . $key ], $c[ 'legacy_' . $key . '_default' ] );",
  ": '';"),
 ("About card reverts to the text variant",
  "if ( $c['card'] === 'stat' ) :", "if ( false ) :"),
 ("a tool page lists itself in its own band",
  "if ( $key === $page ) {", "if ( false ) {"),
 ("the tools band stops reading the tools' own names",
  "$name = vance_get_theme_mod( $t['name_key'], $t['name_def'] );",
  "$name = $t['name_def'];"),
 ("the tools band loses its modifier class",
  "$slot_class .= ' vhh-hero-spotlight__slot--' . $c['slot'];", "$slot_class .= '';"),
 ("every card goes back to one hard-wired icon",
  "vance_page_hero_spotlight_icon( $c['card_icon'] )",
  "vance_page_hero_spotlight_icon( 'chat' )"),
 ("the shelf cell is added unconditionally again",
  "if ( count( $cells ) < count( $tools ) ) {", "if ( true ) {"),
 ("the pillars band stops reading its settings",
  "$title = vance_get_theme_mod( 'vance_evidence_pillar' . $i . '_title', $default );",
  "$title = '';"),
 ("button 1 stops inheriting the classic label",
  "$vals['btn1_text'] = vance_get_theme_mod( $c['legacy_btn1'], $c['legacy_btn1_default'] );",
  "$vals['btn1_text'] = 'Explore the Evidence Library';"),
 ("the PDF button loses its download attribute",
  "! empty( $c['btn2_download'] ) ? ' download' : ''", "''"),
 # NB not "$slot_markup = 'lines'": that feeds plain strings to the lines
 # markup and fatals, so the suite exits non-zero without a single failing
 # assertion -- red for the wrong reason, and no evidence of coverage. This
 # mutant is the realistic version of the same slip (a slot listed under the
 # wrong markup) and it produces real FAILs.
 ("the tools band is mis-listed as a badges band",
  "} elseif ( in_array( $c['slot'], array( 'badges', 'pillars' ), true ) ) {",
  "} elseif ( in_array( $c['slot'], array( 'badges', 'pillars', 'tools' ), true ) ) {"),

 # ---- the two shelves and the 404 ------------------------------------

 # The 404 has no toggle, so if 'always' stops being honoured it silently
 # falls back to the shared default -- which is 'classic', a design it does
 # not have. The page would render nothing at all.
 ("the 404 stops being always-on",
  "if ( ! empty( $c['always'] ) ) {", "if ( false ) {"),

 # /tools-resources/ 404s on the live site. This is the bug that was sitting
 # in the tools band unseen, because no tool page had the hero switched on.
 ("the shelf cell points at the dead path again",
  "vance_page_hero_spotlight_page_url( 'free-health-tools', '/free-health-tools/' )",
  "vance_page_hero_spotlight_page_url( 'tools-resources', '/tools-resources/' )"),

 ("button 2 stops inheriting the classic label",
  "$vals['btn2_text'] = vance_get_theme_mod( $c['legacy_btn2'], $c['legacy_btn2_default'] );",
  "$vals['btn2_text'] = 'Create Free Account';"),

 ("button 2 stops inheriting the classic link",
  "$vals['btn2_link'] = vance_get_theme_mod( $c['legacy_btn2_link'], $c['legacy_btn2_link_default'] );",
  "$vals['btn2_link'] = '/login/?tab=signup';"),

 # The motif is a default, not a ceiling: an uploaded photograph must win,
 # or the Photograph control on those two pages is decoration.
 ("an uploaded photograph no longer beats the motif",
  "if ( $s['image'] === '' && ! empty( $c['motif'] ) ) :",
  "if ( ! empty( $c['motif'] ) ) :"),

 # The first `$c['slot'] === 'search'` in the file is this one -- the field,
 # not the markup. Without the second line of the pattern this mutant reads
 # as covering the band and covers the placeholder control instead.
 ("the search band loses its placeholder field",
  "if ( $c['slot'] === 'search' ) {" + chr(10) + chr(9)*2 + "$d['search_placeholder']",
  "if ( false ) {" + chr(10) + chr(9)*2 + "$d['search_placeholder']"),

 # NB not "$slot_markup = 'search'" -> "if ( false )": that feeds the string
 # 'search' to the lines markup and fatals with a TypeError, so the suite
 # exits non-zero without a single failing assertion -- red for the wrong
 # reason, and no evidence of coverage. This is the realistic version of the
 # same slip: the markup branch is dropped while $slot_markup still says
 # 'search', so the band falls through to the badges markup and renders a
 # tick beside the word 'search'. Ugly, silent, and 2 real FAILs.
 ("the search band renders as badges, not a form",
  "<?php if ( $slot_markup === 'search' ) : ?>" + chr(10) +
  chr(9)*5 + "<?php /* The homepage hero's own search markup",
  "<?php if ( false ) : ?>" + chr(10) +
  chr(9)*5 + "<?php /* The homepage hero's own search markup"),

 # A <span> looks identical and is not a label: the prompt stops being a
 # click target for the field under it.
 ("the search prompt goes back to a bare span",
  "<?php if ( $slot_markup === 'search' ) : ?>" + chr(10) +
  chr(9)*5 + "<label class=" + chr(34) + "vhh-hero-spotlight__slot-label" + chr(34),
  "<?php if ( false ) : ?>" + chr(10) +
  chr(9)*5 + "<label class=" + chr(34) + "vhh-hero-spotlight__slot-label" + chr(34)),

 # The 404 band resolves every cell by slug so a renamed page keeps its
 # link. Losing that turns four links into four plain <div>s.
 ("the 404 band stops resolving its links",
  "'href'  => vance_page_hero_spotlight_page_url( $c[3], $c[4] ),",
  "'href'  => '',"),
]

try:
    for name, find, repl in MUTANTS:
        if find not in ORIG:
            print("SKIP (pattern not found): %s" % name); continue
        SRC.write_text(ORIG.replace(find, repl, 1), encoding="utf-8")
        r = subprocess.run([sys.executable and "php", "hero-render.test.php"], capture_output=True, text=True)
        fails = [l.strip() for l in r.stdout.splitlines() if l.strip().startswith("FAIL")]
        status = "went RED" if r.returncode != 0 else "*** STAYED GREEN ***"
        print("%-42s %s (%d failing)" % (name, status, len(fails)))
        for f in fails[:3]:
            print("      %s" % f)
finally:
    SRC.write_text(ORIG, encoding="utf-8")
    print("\nsource restored:", SRC.read_text(encoding='utf-8') == ORIG)
