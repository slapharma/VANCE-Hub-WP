import io, pathlib, subprocess, sys, shutil
THEME = pathlib.Path(__file__).resolve().parent.parent / "wp-content/themes/vance-health-hub"
SRC = THEME / "inc/page-hero-spotlight.php"

# Most mutants live in the renderer, so a 3-tuple still means "patch SRC".
# A few have to reach the template that CALLS it or the stylesheet that backs
# it -- a hero the template never invokes, or a class with no rule behind it,
# are both silent failures the renderer alone cannot show. Those carry a
# fourth element naming the file, theme-relative.
TARGETS = {
    None: SRC,
    "page-education.php": THEME / "page-education.php",
    "assets/css/main.css": THEME / "assets/css/main.css",
}

# Line endings, handled the way mutate-gi.py already handled them. Both halves
# are needed and each one alone is a bug:
#
#   Path.read_text()/write_text() translate. Reading folds CRLF to \n, writing
#   expands \n to os.linesep, so on Windows an LF file silently comes back
#   CRLF. That is how an ad-hoc probe script rewrote all 985 lines of
#   inc/gi-hero.php on 2026-09-01, and its "restored: True" check missed it
#   because that check also read in text mode -- it compared the two files
#   AFTER the very translation it existed to catch.
#
#   But reading with newline='' ALONE is also wrong here: several mutants below
#   embed a bare \n to match across lines, and against CRLF text they match
#   nothing and report SKIP. Four of them did exactly that on the first attempt
#   at this fix. A mutant that silently tests nothing is the failure this
#   runner exists to prevent.
#
# So: match on LF in memory, remember each file's own convention, restore it on
# write. Bytes are preserved either way, and the check at the end compares
# bytes so a regression here cannot pass unnoticed.
_CRLF = {}

def read(p):
    with io.open(str(p), encoding="utf-8", newline="") as fh:
        text = fh.read()
    _CRLF[str(p)] = "\r\n" in text
    return text.replace("\r\n", "\n")

def write(p, s):
    if _CRLF.get(str(p)):
        s = s.replace("\n", "\r\n")
    with io.open(str(p), "w", encoding="utf-8", newline="") as fh:
        fh.write(s)

def raw(p):
    with io.open(str(p), "rb") as fh:
        return fh.read()

ORIGINALS = {k: read(p) for k, p in TARGETS.items()}
ORIGINAL_BYTES = {k: raw(p) for k, p in TARGETS.items()}

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

 # NB the old "an uploaded photograph no longer beats the motif" mutant lived
 # here and patched `if ( $s['image'] === '' && ! empty( $c['motif'] ) ) :`.
 # That test moved into the $has_motif assignment when the phone stylesheet
 # started keying off a rendered flag, so the mutant stopped matching and
 # printed SKIP -- a check quietly not running, which is worse than a red one.
 # It is now folded into "the motif flag reads the config, not the render" at
 # the bottom of this list, which breaks the same behaviour and fails the same
 # two assertions.

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

 # A filename typo is the likeliest way a hero photograph breaks, and it
 # breaks silently -- the renderer prints whatever string it is given.
 ("a hero photograph is misnamed",
  "$img_hero . 'free-tools.jpg'", "$img_hero . 'free-tools-v2.jpg'"),

 # The other way a photograph goes wrong, and the one this whole directory was
 # created to stop: a page quietly borrowing an image bought for another page.
 # 5f's exact count does NOT catch it -- the total is still ten and every file
 # still exists -- so it is 5g's per-page check that has to, and this proves it
 # does. Until 2026-08-31 Ask AI wore Crohn's and the User Guide wore IBD.
 ("a page goes back to borrowing another page's photograph",
  "$img_hero . 'education.jpg'", "$img_hero . 'userguide.jpg'"),

 # ...and the pages that deliberately have none must keep declaring the
 # motif, or they render an empty <img> instead.
 ("a motif page loses its motif flag",
  "'motif'        => true,", "'motif'        => false,"),

 # ---- Education & Courses --------------------------------------------

 # The whole reason this page's hero was worth building: vance_edu_hero_desc
 # has been registered, defaulted and sanitized since the page was made and
 # rendered NOWHERE. If the spotlight hero stops reading it, the control
 # quietly goes back to doing nothing.
 ("education stops reading vance_edu_hero_desc",
  "'legacy_desc'  => 'vance_edu_hero_desc',", "'legacy_desc'  => '',"),

 # ...and that default's only other copy is the Customizer registration, so
 # section 0b has to be pointed at the right file. Reword one side and the
 # two designs say different things.
 ("the education description default is reworded",
  "'legacy_desc_default'  => 'We" + chr(92) + "'re building self-paced courses",
  "'legacy_desc_default'  => 'We are building self-paced courses"),

 # A legacy_desc_file naming a file that is not there makes 0b search an
 # empty string, which passes nothing and reports nothing.
 ("legacy_desc_file points at a file that is not there",
  "'legacy_desc_file'     => 'customizer-pages.php',",
  "'legacy_desc_file'     => 'customizer-pages-v2.php',"),

 # The band is what a visitor who came for a course and found a waitlist
 # gets instead. An empty one leaves them with nothing.
 ("the learn band comes back empty",
  "$slot_items = vance_page_hero_spotlight_learn();", "$slot_items = array();"),

 # ...and its cells resolve by slug for the same reason the 404's do. A typo'd
 # slug is the realistic way that breaks, and it breaks silently -- the cell
 # still renders, still looks like a link, and lands on a 404.
 #
 # NB not "clear $GLOBALS['PAGES']": the whole point of the path fallback is
 # that it produces the SAME url the slug does, so that mutant is a no-op by
 # design and stayed green. It proved the fallback works, not the check.
 ("a learn band slug is typo'd",
  "'knowledgebase', '/knowledgebase/' ),\n\t\tarray( 'chat'",
  "'knowledgebase-v2', '/knowledgebase-v2/' ),\n\t\tarray( 'chat'"),

 # A CTA pointing at an id the page does not render scrolls nowhere and
 # says nothing about it.
 ("education's first button points at a dead anchor",
  "'btn1_link'    => '#waitlist',", "'btn1_link'    => '#join-the-waitlist',"),

 # The template is where a hero actually reaches a visitor. A commented-out
 # call leaves every renderer assertion passing against a dead page.
 ("page-education.php stops calling the renderer",
  "vance_render_page_hero_spotlight( 'education' );",
  "// vance_render_page_hero_spotlight( 'education' );", "page-education.php"),

 ("the classic education hero is deleted rather than kept",
  'class="hero edu-hero"', 'class="hero edu-hero-gone"', "page-education.php"),

 # A modifier class with no rule behind it is dead markup: the band would
 # render with the lines treatment and no link affordance at all.
 #
 # Two earlier attempts at this mutant were wrong, and both are worth keeping
 # written down:
 #   - renaming ONE of the four .slot--learn selectors left three behind, and
 #     section 8 asks `strpos( $css, "." . $cls )`, so it stayed green;
 #   - renaming the slot KEY ('learn' -> 'learn-band') dropped the switch
 #     through to the badges data while $slot_markup still said 'lines', which
 #     feeds plain strings to the lines markup and FATALS. Non-zero exit, zero
 #     failing assertions -- red for the wrong reason and no evidence at all.
 #     Same trap the search-band mutants above document.
 # So: rename every selector, and to a name the check's prefix search cannot
 # accidentally still match (--learn-DROPPED would; --DROPPED-learn does not).
 ("the learn band's CSS block is dropped",
  "vhh-hero-spotlight__slot--learn", "vhh-hero-spotlight__slot--DROPPED-learn",
  "assets/css/main.css", "all"),

 # ---- the motif flag --------------------------------------------------

 # THE BUG THIS SECTION EXISTS FOR, reproduced exactly: the phone rule goes
 # back to naming motif pages one at a time, Education is not one of the
 # names, and its headline ships flush under the site header on a phone.
 # Caught on the live site by measuring, not by any check -- hence 5h.
 ("the phone rule goes back to a hand-kept page list",
  ".vhh-hero-spotlight--has-motif {", ".vhh-hero-spotlight--kblobby,\n    .vhh-hero-spotlight--e404 {",
  "assets/css/main.css"),

 # The renderer stops emitting the flag at all, so the phone rule matches
 # nothing and every motif hero jams.
 ("the renderer stops emitting --has-motif",
  "$has_motif ? ' vhh-hero-spotlight--has-motif' : ''", "''"),

 # ...and the flag must track what was actually DRAWN. Pinned true, a
 # photograph page gets the motif's phone padding; pinned to the config, an
 # uploaded photograph on a motif page does too -- which is the case the old
 # page list got wrong even for the pages it named.
 ("the motif flag reads the config, not the render",
  "$has_motif = ( $s['image'] === '' && ! empty( $c['motif'] ) );",
  "$has_motif = ! empty( $c['motif'] );"),
]

def run():
    r = subprocess.run(["php", "hero-render.test.php"], capture_output=True, text=True)
    fails = [l.strip() for l in r.stdout.splitlines() if l.strip().startswith("FAIL")]
    return r.returncode, fails

try:
    for mutant in MUTANTS:
        name, find, repl = mutant[0], mutant[1], mutant[2]
        which = mutant[3] if len(mutant) > 3 else None
        # Default is one occurrence -- a mutant should be the smallest edit
        # that expresses the slip. "all" is for the case where one occurrence
        # is not enough to change what the suite can see: four CSS selectors
        # backing one class, where three survivors keep the check green.
        count = -1 if (len(mutant) > 4 and mutant[4] == "all") else 1
        path, orig = TARGETS[which], ORIGINALS[which]
        if find not in orig:
            print("SKIP (pattern not found): %s" % name); continue
        write(path, orig.replace(find, repl, count))
        code, fails = run()
        write(path, orig)                          # restore before the next mutant
        status = "went RED" if code != 0 else "*** STAYED GREEN ***"
        print("%-42s %s (%d failing)" % (name, status, len(fails)))
        for f in fails[:3]:
            print("      %s" % f)
finally:
    for which, path in TARGETS.items():
        write(path, ORIGINALS[which])
    # Compared as BYTES, so a line-ending flip is a failure and not invisible.
    ok = all(raw(p) == ORIGINAL_BYTES[k] for k, p in TARGETS.items())
    print("\nsource restored:", ok)
