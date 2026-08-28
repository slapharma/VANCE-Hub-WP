import pathlib, subprocess
SRC = pathlib.Path(__file__).resolve().parent.parent / "wp-content/themes/vance-health-hub/inc/page-hero-spotlight.php"
ORIG = SRC.read_text(encoding="utf-8")
MUTANTS = [
 ("the original bug: '' default for the copy",
  "$vals['eyebrow'] = vance_get_theme_mod( $c['legacy_tag'],   $c['legacy_tag_default'] );",
  "$vals['eyebrow'] = vance_get_theme_mod( $c['legacy_tag'], '' );"),
 ("the original bug: '' default for the title",
  "$vals['title']   = vance_get_theme_mod( $c['legacy_title'], $c['legacy_title_default'] );",
  "$vals['title']   = vance_get_theme_mod( $c['legacy_title'], '' );"),
 ("the original bug: '' default for the badges",
  "vance_get_theme_mod( 'vance_about_badge1_label', 'Pharma-Grade Quality' )",
  "vance_get_theme_mod( 'vance_about_badge1_label', '' )"),
 ("the original bug: '' default for the phone",
  "vance_get_theme_mod( 'vance_contact_phone', '+44 (0)1628 526 005' )",
  "vance_get_theme_mod( 'vance_contact_phone', '' )"),
 ("a default reworded away from the template",
  "'legacy_tag_default'   => 'About Vance Medical Hub',",
  "'legacy_tag_default'   => 'About Us',"),
 ("a TOOL default reworded away from its template",
  "'legacy_tag_default'   => 'IBD Screening',",
  "'legacy_tag_default'   => 'IBD Screener',"),
 ("the original bug: '' default for a tool's name in the band",
  "'name_def' => 'Gastro Health Survey',",
  "'name_def' => '',"),
 ("the original bug: '' default for the survey's own subtitle",
  "'legacy_desc_default'  => 'A short, evidence-based questionnaire",
  "'legacy_desc_default'  => '', 'unused'  => 'A short, evidence-based questionnaire"),
]
try:
    for name, find, repl in MUTANTS:
        if find not in ORIG:
            print("SKIP (not found): " + name); continue
        SRC.write_text(ORIG.replace(find, repl, 1), encoding="utf-8")
        r = subprocess.run(["php", "hero-render.test.php"], capture_output=True, text=True)
        fails = [l.strip() for l in r.stdout.splitlines() if l.strip().startswith("FAIL")]
        print("%-44s %s (%d failing)" % (name, "went RED" if r.returncode else "*** STAYED GREEN ***", len(fails)))
        for f in fails[:2]: print("      " + f)
finally:
    SRC.write_text(ORIG, encoding="utf-8")
    print("\nsource restored:", SRC.read_text(encoding="utf-8") == ORIG)
