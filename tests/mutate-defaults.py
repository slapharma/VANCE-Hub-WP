import pathlib, subprocess
SRC = pathlib.Path(__file__).resolve().parent.parent / "wp-content/themes/vance-health-hub/inc/page-hero-spotlight.php"
ORIG = SRC.read_text(encoding="utf-8")
MUTANTS = [
 # All three copy fields resolve in one loop now, so one mutant covers the
 # eyebrow, the headline and the intro on every page at once.
 ("the original bug: '' default for the copy",
  ": vance_get_theme_mod( $c[ 'legacy_' . $key ], $c[ 'legacy_' . $key . '_default' ] );",
  ": vance_get_theme_mod( $c[ 'legacy_' . $key ], '' );"),
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
 ("the original bug: '' default for a pillar in the band",
  "1 => 'Clinical Trials',", "1 => '',"),
 ("the original bug: '' default for the inherited join label",
  "'legacy_btn1_default' => 'Explore the Evidence Library',",
  "'legacy_btn1_default' => '',"),
 ("Ask AI falls back to what functions.php registers, not the template",
  "'legacy_tag_default'   => 'Information Assistant',",
  "'legacy_tag_default'   => 'Beta Feature v1.0',"),
 ("the User Guide PDF filename drifts from the template's constant",
  "'Vance-Health-Hub-User-Guide.pdf' );", "'Vance-Health-Hub-User-Guide-v2.pdf' );"),

 # ---- the two shelves and the 404 ------------------------------------

 ("the original bug: '' default for the inherited account label",
  "'legacy_btn2_default'      => 'Create Free Account',",
  "'legacy_btn2_default'      => '',"),
 ("the original bug: '' default for the inherited account link",
  "'legacy_btn2_link_default' => '/login/?tab=signup',",
  "'legacy_btn2_link_default' => '',"),
 ("the SHELF default reworded away from its template",
  "'legacy_tag_default'   => 'Free Tools',",
  "'legacy_tag_default'   => 'Free Health Tools',"),
 ("the KNOWLEDGEBASE default reworded away from its template",
  "'legacy_tag_default'   => 'Knowledgebase',",
  "'legacy_tag_default'   => 'Knowledge Base',"),
 # The 404 has no template to hold its words against, so section 0 is the
 # only thing standing between it and an empty headline.
 ("the 404's own headline emptied",
  "'legacy_title_default' => __( 'We can&rsquo;t find that page', 'vance-health-hub' ),",
  "'legacy_title_default' => '',"),
 ("the 404's search-band placeholder emptied",
  "'search_placeholder' => __( 'Search the whole knowledgebase...', 'vance-health-hub' ),",
  "'search_placeholder' => '',"),
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
