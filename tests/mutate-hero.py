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
 ("eyebrow stops reading the classic key",
  "$vals['eyebrow'] = vance_get_theme_mod( $c['legacy_tag'],   $c['legacy_tag_default'] );",
  "$vals['eyebrow'] = '';"),
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
  "array( 'badges', 'pillars' ), true ) ? 'badges' : 'lines';",
  "array( 'badges', 'pillars', 'tools' ), true ) ? 'badges' : 'lines';"),
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
