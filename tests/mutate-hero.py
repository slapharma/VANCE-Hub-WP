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
