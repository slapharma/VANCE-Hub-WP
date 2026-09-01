<?php
/**
 * Gastro Indications heroes — the spotlight layout, condition-set variant.
 *
 * Eight pages: the Gastro Health Explained lobby and its seven condition
 * pages. Before this file they carried two heroes that had never looked like
 * each other, let alone like the rest of the site — the lobby a 420px dark
 * photographic band (`.hero.gi-hub-hero` in page-gi-health.php), the seven a
 * flat teal gradient with centred text and no image at all (`.gi-cp-hero` in
 * assets/css/gi-health.css). They now share one hero, and it is the same
 * `.vhh-hero-spotlight` section the homepage, the page heroes and the five
 * policy documents already render.
 *
 * WHAT BINDS THE SET
 *
 * The policy set (inc/legal-hero.php) holds together because each of the five
 * documents carries a band listing the other four. Conditions have six
 * siblings, which will not fit that band, so the rule keeps its intent and
 * changes its shape:
 *
 *     Always four cells — the three conditions this one is most often
 *     confused with, then the lobby.
 *
 * So the way back is in the same corner of every page, and the related cells
 * are chosen by what people actually mix up rather than alphabetically:
 * microscopic colitis reaches across to IBS, diverticular disease runs back to
 * IBS and colorectal cancer.
 *
 * THE RELATED MAP HAS ONE HOME
 *
 * `related` below is the same list the "Explore related conditions" block at
 * the foot of each condition page renders from — page-gi-condition.php reads
 * it through vance_gi_hero_related_slugs() rather than keeping the second copy
 * it used to carry. A visitor on the IBS page would otherwise be offered one
 * trio in the hero and a different trio at the bottom of the same page.
 *
 * THE LOBBY IS THE ODD ONE OUT, ON PURPOSE
 *
 * It is the only page in the set with CTAs, and the only one whose band is not
 * four cells: it lists all seven conditions as chips, on two fixed rows of
 * four and three. That repeats the seven photo cards further down the page,
 * which was the accepted trade — the whole set is then reachable from the
 * first screen.
 *
 * PURPLE HAS EXACTLY TWO JOBS
 *
 * #6B489E is the theme's own hero colour (inc/hero-spotlight.php's
 * `btn1_bg_color`), not a new hue. It carries the eyebrow on all eight pages
 * and the seven chips on the lobby — the label saying where you are, and the
 * control taking you somewhere. Nothing else in the hero is purple, and that
 * is why the CTA button here overrides the committed `--vhh-hs-cta-bg` default
 * of that same purple down to the deep teal ink colour: two purple
 * call-to-actions in one band compete, and the chips are the page's job.
 *
 * The override is a per-page custom property on this section only. No other
 * hero on the site changes.
 *
 * WHAT IS NOT IN THIS FILE
 *
 * The band, the type scale, the eyebrow pill's geometry, the card, the
 * photograph's dissolve and the whole responsive ladder — including the
 * doubled-class `!important` rules that opt this hero out of the global mobile
 * type normalisation in mobile-base.css — are inherited from the committed
 * `.vhh-hero-spotlight` block in assets/css/main.css and are NOT repeated
 * here. Only what is genuinely new to this set lives in
 * vance_gi_hero_styles().
 *
 * @package vance-health-hub
 * @since   2026-08-31
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * The theme's hero purple, and the two values derived from it.
 *
 * Named once so the eyebrow, the chips and any future purple cannot drift to
 * three slightly different lavenders. Measured against the surfaces they are
 * actually used on:
 *
 *   TEXT  #4A3270 on #EFE9F9 ............ 8.9:1  (AA needs 4.5)
 *   HOVER #FFFFFF on #6B489E ............ 6.9:1  (AA needs 4.5)
 *   EDGE  #8A6DB8 on the band's #ECF5F5 . 3.8:1  (needs 3.0)
 *
 * The eyebrow's border is the stronger #8A6DB8 rather than the chips' #CDBEE8
 * for a reason that is easy to undo by accident: that pill sits on the mint
 * band, not inside the white one, so its own lavender fill is barely 1.1:1
 * against #ECF5F5 and the BORDER is the only thing drawing its edge. The
 * #5E8F8D teal hairline it replaces carried 3.3:1 and existed for the same
 * reason — see the note on `.vhh-hero-spotlight__eyebrow` in main.css.
 */
const VANCE_GI_PURPLE      = '#6B489E'; // fill on hover, and the theme's own CTA purple
const VANCE_GI_PURPLE_INK  = '#4A3270'; // label text
const VANCE_GI_PURPLE_TINT = '#EFE9F9'; // chip and eyebrow fill
const VANCE_GI_PURPLE_EDGE = '#8A6DB8'; // eyebrow border, on the mint band
const VANCE_GI_PURPLE_LINE = '#CDBEE8'; // chip border, on the white band

/**
 * Per-condition hero copy, keyed by the slug used everywhere else in the GI
 * section — the /<slug>/ page, the `post_tag`, and the Discovery Suite's
 * `condition[]` value. vance_gi_conditions() in functions.php stays the
 * canonical slug registry; this carries only what the HERO needs.
 *
 *   eyebrow  the family, not the page. Four pages read "Inflammatory bowel
 *            disease", then one each for the three that sit outside it. This
 *            is the field that tells a reader on the Crohn's page that
 *            ulcerative colitis is a sibling and IBS is not.
 *   kicker   the small uppercase line above the name in a sibling's band cell
 *            — the one-phrase differentiator, the equivalent of the policy
 *            set's "Your data" / "The agreement".
 *   icon     a key of vance_gi_hero_icon().
 *   intro    the hero lede. Carried across from page-gi-condition.php's
 *            $cond_defaults verbatim except where noted, so switching hero
 *            design cannot silently reword a clinical page.
 *   related  the three conditions this one is most often confused with, in the
 *            order they appear in the band. Never contains its own slug — a
 *            page is not sold back to itself.
 *   focal    object-position for the photograph. The media box is roughly
 *            square while the assets are 3:2, so the crop is horizontal and
 *            the first number is the one that matters. Each was set by eye
 *            against the dissolve: a subject left of ~40% is feathered away.
 *            Admin-overridable per condition; see vance_gi_hero_photo().
 *
 * @return array<string, array<string, mixed>>
 */
function vance_gi_hero_meta() {
	return array(
		'inflammatory-bowel-disease' => array(
			'eyebrow' => __( 'Inflammatory bowel disease', 'vance-health-hub' ),
			'kicker'  => __( 'The umbrella term', 'vance-health-hub' ),
			'icon'    => 'branch',
			// Rewritten for the hero. The classic lede opened "A chronic
			// condition of the digestive tract", which says nothing that the
			// headline has not already said; this one names the two conditions
			// underneath it, which is the whole job of an umbrella page.
			'intro'   => __( 'The umbrella term for long-term conditions — mainly Crohn’s disease and ulcerative colitis — that inflame the digestive tract. There is no single cure, but with the right plan many people live in long, stable remission.', 'vance-health-hub' ),
			'related' => array( 'ulcerative-colitis', 'crohns-disease', 'microscopic-colitis' ),
			'focal'   => '50% 30%',
		),
		'ulcerative-colitis' => array(
			'eyebrow' => __( 'Inflammatory bowel disease', 'vance-health-hub' ),
			'kicker'  => __( 'Colon and rectum', 'vance-health-hub' ),
			'icon'    => 'horseshoe',
			'intro'   => __( 'A form of IBD that inflames and ulcerates the lining of the colon and rectum. Most people with UC lead full, active lives once treatment settles the inflammation.', 'vance-health-hub' ),
			'related' => array( 'crohns-disease', 'inflammatory-bowel-disease', 'microscopic-colitis' ),
			'focal'   => '62% 28%',
		),
		'crohns-disease' => array(
			'eyebrow' => __( 'Inflammatory bowel disease', 'vance-health-hub' ),
			'kicker'  => __( 'Anywhere in the gut', 'vance-health-hub' ),
			'icon'    => 'tract',
			'intro'   => __( 'A form of IBD that can inflame any part of the gut, from mouth to anus, most often the end of the small intestine. With modern treatment, most people manage their symptoms well.', 'vance-health-hub' ),
			'related' => array( 'ulcerative-colitis', 'inflammatory-bowel-disease', 'microscopic-colitis' ),
			'focal'   => '68% 28%',
		),
		'microscopic-colitis' => array(
			'eyebrow' => __( 'Inflammatory bowel disease', 'vance-health-hub' ),
			'kicker'  => __( 'Under the microscope', 'vance-health-hub' ),
			'icon'    => 'lens',
			'intro'   => __( 'Inflammation of the colon that can only be seen under a microscope. A common and treatable cause of ongoing watery diarrhoea, particularly in older adults.', 'vance-health-hub' ),
			// The third cell reaches OUTSIDE the IBD family deliberately. IBS
			// is what microscopic colitis is most often mistaken for, and a
			// reader who has landed here from a search for watery diarrhoea is
			// better served by that link than by a fourth IBD page.
			'related' => array( 'ulcerative-colitis', 'crohns-disease', 'irritable-bowel-syndrome' ),
			'focal'   => '44% 26%',
		),
		'irritable-bowel-syndrome' => array(
			'eyebrow' => __( 'Functional gut disorder', 'vance-health-hub' ),
			'kicker'  => __( 'How the gut works', 'vance-health-hub' ),
			'icon'    => 'wave',
			'intro'   => __( 'A common, long-term disorder of how the gut works — pain, bloating and changes in bowel habit, without visible damage to the bowel itself.', 'vance-health-hub' ),
			'related' => array( 'microscopic-colitis', 'diverticular-disease', 'inflammatory-bowel-disease' ),
			'focal'   => '60% 32%',
		),
		'colorectal-cancer' => array(
			'eyebrow' => __( 'Bowel cancer', 'vance-health-hub' ),
			'kicker'  => __( 'Colon or rectum', 'vance-health-hub' ),
			'icon'    => 'scan',
			// The one page in the set where the tone has to carry weight. It
			// does it here, in the wording, and NOT with a different colour or
			// a larger headline — the band stays exactly as calm as the other
			// seven. A hero that shouts at somebody who has just been given
			// this diagnosis is the wrong hero.
			'intro'   => __( 'Cancer that develops in the colon or rectum, often growing slowly from small growths called polyps. Found at the earliest stage, it is among the most treatable cancers there is.', 'vance-health-hub' ),
			'related' => array( 'diverticular-disease', 'inflammatory-bowel-disease', 'ulcerative-colitis' ),
			'focal'   => '60% 26%',
		),
		'diverticular-disease' => array(
			'eyebrow' => __( 'Structural bowel condition', 'vance-health-hub' ),
			'kicker'  => __( 'Pouches in the colon', 'vance-health-hub' ),
			'icon'    => 'pouches',
			'intro'   => __( 'Small pouches that form in the wall of the colon as we get older. They are very common and usually harmless, but can cause pain or become inflamed.', 'vance-health-hub' ),
			'related' => array( 'irritable-bowel-syndrome', 'colorectal-cancer', 'inflammatory-bowel-disease' ),
			'focal'   => '48% 26%',
		),
	);
}

/**
 * Whether a slug is one of the seven.
 *
 * Driven from vance_gi_conditions() rather than from vance_gi_hero_meta(), so
 * a condition added to the canonical registry and forgotten here fails loudly
 * in vance_gi_hero_slug_meta() instead of quietly rendering the classic hero.
 *
 * @param string $slug A page's post_name.
 * @return bool
 */
function vance_gi_hero_has( $slug ) {
	if ( ! $slug || ! function_exists( 'vance_gi_conditions' ) ) { return false; }
	return array_key_exists( $slug, vance_gi_conditions() );
}

/**
 * One condition's hero metadata, or null.
 *
 * @param string $slug A page's post_name.
 * @return array<string, mixed>|null
 */
function vance_gi_hero_slug_meta( $slug ) {
	$meta = vance_gi_hero_meta();
	return isset( $meta[ $slug ] ) ? $meta[ $slug ] : null;
}

/**
 * The three related slugs for one condition, for the foot-of-page "Explore
 * related conditions" block as well as the hero band.
 *
 * page-gi-condition.php calls this instead of carrying its own $cp_explore
 * map. Returns an empty array for anything not in the set, so a caller can
 * fall back without a special case.
 *
 * @param string $slug A page's post_name.
 * @return array<int, string>
 */
function vance_gi_hero_related_slugs( $slug ) {
	$m = vance_gi_hero_slug_meta( $slug );
	return $m ? $m['related'] : array();
}

/**
 * A condition's display name, from the presentational registry.
 *
 * vance_gi_condition_cards() carries titles with their abbreviation in
 * brackets — "Inflammatory Bowel Disease (IBD)" — which is right on a card
 * with a photograph above it and too long for a band cell 170px wide. The
 * canonical registry's `label` is the plain form and is what a cell wants, so
 * that is what this returns. One source either way; nothing is retyped.
 *
 * THE APOSTROPHE. The two registries in functions.php disagree about Crohn's:
 * vance_gi_conditions()'s `label` writes a straight ASCII quote, and
 * vance_gi_condition_cards()'s `title` writes the typographic one. Neither is
 * wrong enough to be worth changing under the homepage tile grid and the
 * Discovery Suite, both of which read them — but a hero that sets a straight
 * quote in its H1 and a curly one three lines down in the intro looks like a
 * mistake, because it is one. Normalised here, at the display layer, where it
 * affects this hero and nothing else.
 *
 * @param string $slug A page's post_name.
 * @return string
 */
function vance_gi_hero_label( $slug ) {
	$conds = function_exists( 'vance_gi_conditions' ) ? vance_gi_conditions() : array();
	$label = isset( $conds[ $slug ]['label'] ) ? $conds[ $slug ]['label'] : $slug;
	return str_replace( "'", '’', $label );
}

/**
 * The four band cells for one condition: three siblings, then the lobby.
 *
 * Always four, because a page is never listed in its own band — which is what
 * lets the CSS lay them out two-by-two without coping with an odd count. Same
 * guarantee, and the same reason for it, as vance_legal_hero_siblings().
 *
 * @param string $slug A page's post_name.
 * @return array<int, array{icon: string, label: string, value: string, href: string}>
 */
function vance_gi_hero_cells( $slug ) {
	$meta  = vance_gi_hero_meta();
	$cells = array();

	foreach ( vance_gi_hero_related_slugs( $slug ) as $rel ) {
		// Guard rather than assume: a typo in `related` would otherwise emit a
		// cell with an empty icon and the raw slug as its name.
		if ( ! isset( $meta[ $rel ] ) ) { continue; }
		$cells[] = array(
			'icon'  => $meta[ $rel ]['icon'],
			'label' => $meta[ $rel ]['kicker'],
			'value' => vance_gi_hero_label( $rel ),
			'href'  => vance_gi_page_url( $rel ),
		);
	}

	$cells[] = array(
		'icon'  => 'grid',
		'label' => __( 'All seven', 'vance-health-hub' ),
		'value' => __( 'Gastro Health Explained', 'vance-health-hub' ),
		'href'  => vance_gi_hub_url(),
	);

	return $cells;
}

/**
 * The lobby hero's copy defaults.
 *
 * Declared HERE and read by inc/customizer-gi-health.php for its
 * `'default' =>` values, rather than typed out in both files. The test suite
 * for the page heroes exists because exactly that duplication went wrong once:
 * a default written in two places disagreed silently, and only the pristine
 * case — nothing saved at all — could see it.
 *
 * The primary CTA is no longer "Explore conditions". The chips ARE that
 * button, so the two actions became the routes the chips do not offer.
 *
 * @return array<string, string>
 */
function vance_gi_hero_hub_defaults() {
	return array(
		'eyebrow'   => __( 'Gastro health', 'vance-health-hub' ),
		'heading'   => __( 'Seven gut conditions, clearly explained', 'vance-health-hub' ),
		'lede'      => __( 'Clinician-reviewed information on inflammatory bowel disease, IBS, colorectal cancer and more — written in plain language to help you understand a diagnosis, prepare for appointments and manage day to day.', 'vance-health-hub' ),
		'btn1_text' => __( 'Take the Gastro Health Survey', 'vance-health-hub' ),
		'btn1_url'  => '/gastro-health-survey/',
		'btn2_text' => __( 'My Dashboard', 'vance-health-hub' ),
		'btn2_url'  => '/dashboard/',
	);
}

/**
 * The photograph for one hero.
 *
 * Resolution order, and why:
 *
 *  1. The condition's own Customizer image, if an admin has set one. On the
 *     lobby that is `vance_gi_hub_hero_bg_image`, whose ROLE has changed —
 *     it used to be a full-bleed background under a 70% navy veil and is now
 *     the photograph that dissolves into the right edge. The control's label
 *     and description were rewritten to match; a value already saved there
 *     keeps working and simply reads as the new design.
 *  2. The theme asset the condition already owns, from
 *     vance_gi_condition_cards() — the same file the homepage tile grid and
 *     the lobby's own card row use, so nothing is duplicated and no new
 *     photography is needed.
 *
 * The mtime cache-bust is the same technique page-gi-health.php and
 * inc/gastro-conditions.php use, and for the same reason: these photos get
 * swapped in place keeping the filename, and Hostinger serves them with a long
 * max-age. CAVEAT, repeated here because it has bitten before — if you swap a
 * photo with a copy that preserves mtime (cp -p, shutil.copy2, rsync -t) the
 * stamp travels with the old bytes and the URL will not change. `touch` it.
 *
 * @param string $slug A condition slug, or '' for the lobby.
 * @return array{src: string, alt: string, focal: string}|null Null when there
 *                is no photograph at all, which is not an error: the hero
 *                renders without a media slot and the copy simply runs the
 *                full width of the band.
 */
function vance_gi_hero_photo( $slug = '' ) {
	$tmpl = get_template_directory_uri();
	$dir  = get_template_directory();

	if ( $slug === '' ) {
		/*
		 * The lobby's own picture, used by no other template. It was briefly
		 * borrowing gi-health/ibd.webp, which is the IBD card's own photograph
		 * two screens further down this very page.
		 *
		 * THIS IMAGE IS GENERATED, NOT PHOTOGRAPHED. Chosen by the client on
		 * 2026-08-31 over two Unsplash alternatives.
		 *
		 *   model  Kling 3 Omni (text2image), via OpenArt, 1168x880
		 *   brief  two adults walking away from camera on a tree-lined path,
		 *          mid-distance, soft overcast light, muted sage and blue-grey,
		 *          subject weighted right, no faces
		 *
		 * Two things follow from that and neither is obvious from the file:
		 *
		 *  1. There is no photographer to credit and no licence to honour, but
		 *     equally no provenance to point at. If the CAP review or the
		 *     site's editorial standards ever require generated imagery to be
		 *     disclosed to the reader, this is the image that needs the
		 *     disclosure, and the hero has nowhere to put one today.
		 *  2. Close inspection at 2x finds the usual generated-image faults —
		 *     one man's arm fuses into his hip, the other's free hand has soft
		 *     fingers, and a partial face appears in profile despite the brief
		 *     asking for none. None of it is visible at the size this renders,
		 *     where the figures stand ~200px tall in a ~619px box. Do NOT
		 *     enlarge the media slot or move the focal point left without
		 *     looking at those three places again.
		 */
		$rel   = '/assets/img/gi-health/lobby-walk.webp';
		$alt   = __( 'Two people walking side by side away from the camera along a tree-lined canal path', 'vance-health-hub' );
		$focal = vance_get_theme_mod( 'vance_gi_hub_hero_focal', '55% 50%' );
		$saved = vance_get_theme_mod( 'vance_gi_hub_hero_bg_image', '' );
	} else {
		$meta = vance_gi_hero_slug_meta( $slug );
		if ( ! $meta ) { return null; }

		$card = null;
		if ( function_exists( 'vance_gi_condition_cards' ) ) {
			foreach ( vance_gi_condition_cards() as $c ) {
				if ( $c['slug'] === $slug ) { $card = $c; break; }
			}
		}
		if ( ! $card ) { return null; }

		$rel = '/assets/img/gi-health/' . $card['image'];
		$alt = $card['alt'];

		$conds = vance_gi_conditions();
		$key   = isset( $conds[ $slug ]['key'] ) ? $conds[ $slug ]['key'] : '';
		$focal = $key ? vance_get_theme_mod( "vance_gi_cond_{$key}_focal", $meta['focal'] ) : $meta['focal'];
		$saved = $key ? vance_get_theme_mod( "vance_gi_cond_{$key}_image", '' ) : '';
	}

	if ( $saved !== '' ) {
		// An admin-chosen image has no alt text of its own to read, so the
		// stock description would be a lie about a different photograph.
		// Empty alt is correct here and not laziness: the hero's headline and
		// intro already carry the meaning, so the image is decorative and
		// announcing a wrong description is worse than announcing none.
		return array( 'src' => $saved, 'alt' => '', 'focal' => $focal );
	}

	$src  = $tmpl . $rel;
	$file = $dir . $rel;
	if ( ! file_exists( $file ) ) { return null; }

	return array(
		'src'   => add_query_arg( 'v', filemtime( $file ), $src ),
		'alt'   => $alt,
		'focal' => $focal,
	);
}

/**
 * One inline icon.
 *
 * Same 24-unit box and 1.9 stroke weight as inc/page-hero-spotlight.php and
 * inc/legal-hero.php, so all three hero families read as one set of marks.
 * Kept separate rather than calling either of those for the reason given in
 * legal-hero: a condition page should not stop rendering because a tool hero
 * was refactored.
 *
 * The seven condition marks are deliberately abstract. A literal drawing of an
 * inflamed bowel at 17px is unreadable, and a recognisable one on a page
 * somebody has opened about their own diagnosis is unkind. Each says only
 * where in the gut, or what kind of thing, and lets the words do the rest.
 *
 * @param string $name A key below.
 * @return string SVG markup. Static — no dynamic values, nothing to escape.
 */
function vance_gi_hero_icon( $name ) {
	$paths = array(
		// A node with two branches: an umbrella term with conditions under it.
		'branch'    => '<path d="M12 5.1v3.5"/><path d="M12 8.6c0 2.9-4.4 2.6-4.4 6.1v4.2"/><path d="M12 8.6c0 2.9 4.4 2.6 4.4 6.1v4.2"/><circle cx="12" cy="3.6" r="1.6"/>',
		// A horseshoe with marks down its descending limb: continuous disease
		// in the colon and rectum.
		'horseshoe' => '<path d="M5.6 20V9.4a6.4 6.4 0 0 1 12.8 0V20"/><path d="M17.4 12.6h.02"/><path d="M16.6 16h.02"/><path d="M16.9 19.3h.02"/>',
		// A tract from top to bottom with scattered marks: patchy, anywhere.
		'tract'     => '<path d="M7 3.4v6.3a5 5 0 0 0 5 5 5 5 0 0 1 5 5v.9"/><path d="M7 6.4h.02"/><path d="M9.1 12.5h.02"/><path d="M14.3 15.7h.02"/><path d="M17 19.4h.02"/>',
		// A lens over a small wave: only visible under magnification.
		'lens'      => '<circle cx="10.6" cy="10.6" r="6.1"/><path d="M15.1 15.1l4.9 4.9"/><path d="M7.9 11.2c.9-1.5 1.9-1.5 2.7 0 .9 1.5 1.9 1.5 2.7 0"/>',
		// Two waves of unequal amplitude: function, not damage.
		'wave'      => '<path d="M3 9.1c1.6-3 3.3-3 4.9 0s3.3 3 4.9 0 3.3-3 4.9 0"/><path d="M3 15.5c1.6 2.4 3.3 2.4 4.9 0s3.3-2.4 4.9 0 3.3 2.4 4.9 0"/>',
		// Concentric rings on a centre point: finding it early.
		'scan'      => '<circle cx="12" cy="12" r="7.4"/><circle cx="12" cy="12" r="3.5"/><circle cx="12" cy="12" r="1.05" fill="currentColor" stroke="none"/><path d="M12 2.6v1.9"/><path d="M12 19.5v1.9"/><path d="M2.6 12h1.9"/><path d="M19.5 12h1.9"/>',
		// A wall with pouches budding off it.
		'pouches'   => '<path d="M3.4 14.2h17.2"/><circle cx="7.6" cy="10.6" r="2.2"/><circle cx="13.4" cy="10.1" r="2.6"/><circle cx="18.3" cy="11" r="1.7"/>',
		// Three tiles and a plus: the lobby, and more of them.
		'grid'      => '<rect x="3.2" y="3.2" width="7.4" height="7.4" rx="1.6"/><rect x="13.4" y="3.2" width="7.4" height="7.4" rx="1.6"/><rect x="3.2" y="13.4" width="7.4" height="7.4" rx="1.6"/><path d="M13.4 17.1h7.4"/><path d="M17.1 13.4v7.4"/>',
		// The card's mark, identical on all eight pages.
		'review'    => '<path d="M15.2 4.4h1.9a2.3 2.3 0 0 1 2.3 2.3v12.1a2.3 2.3 0 0 1-2.3 2.3H6.9a2.3 2.3 0 0 1-2.3-2.3V6.7a2.3 2.3 0 0 1 2.3-2.3h1.9"/><rect x="8.8" y="2.6" width="6.4" height="3.7" rx="1.3"/><path d="M8.9 13.2l2.2 2.2 4.2-4.4"/>',
		'arrow'     => '<path d="M5 12h13.5"/><path d="M13 6.5l5.5 5.5-5.5 5.5"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) { return ''; }

	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"'
		. ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
		. $paths[ $name ] . '</svg>';
}

/**
 * The block of CSS this hero adds on top of the committed
 * `.vhh-hero-spotlight` rules in assets/css/main.css.
 *
 * Printed once per request behind a static — only one page in the set ever
 * renders — so a template that includes the hero twice cannot emit it twice.
 *
 * Radii are written as `var(--radius-*, Npx)` with a literal fallback, per
 * CLAUDE.md §5, because this block can render where main.css has not defined
 * the scale.
 *
 * @return void
 */
function vance_gi_hero_styles() {
	static $done = false;
	if ( $done ) { return; }
	$done = true;
	?>
<style id="vhh-gi-hero-css">
/* Gastro Indications hero — inc/gi-hero.php.
   Everything not listed here is inherited from the .vhh-hero-spotlight block
   in assets/css/main.css. Do not restate it. */

/* --- Purple, job one: the eyebrow, on all eight pages -------------------- */

/* Scoped to --gi so no other spotlight hero on the site turns purple. The
   border is the strong #8A6DB8 and not the chips' pale line: this pill sits on
   the mint band, where its own fill is barely 1.1:1, so the border IS its
   edge. See the constant block at the top of this file for the measurements. */
.vhh-hero-spotlight--gi .vhh-hero-spotlight__eyebrow {
    color: <?php echo esc_attr( VANCE_GI_PURPLE_INK ); ?>;
    background: <?php echo esc_attr( VANCE_GI_PURPLE_TINT ); ?>;
    border-color: <?php echo esc_attr( VANCE_GI_PURPLE_EDGE ); ?>;
}

/* --- The CTA steps back to teal ----------------------------------------- */

/* The shared rule reads --vhh-hs-cta-bg, whose default is this same purple.
   Setting the variable rather than the property means the button keeps every
   other declaration it has, and no !important is needed anywhere. */
.vhh-hero-spotlight--gi {
    --vhh-hs-cta-bg: #04504E;
    --vhh-hs-cta-hover: #023B3A;
}

/* --- The band of related conditions ------------------------------------- */

/* Four cells, always. Identical treatment to the policy set's band, and for
   the same reasons — see inc/legal-hero.php, whose comments explain the
   min-width 901px scoping and the interior-lines fix in full. If either band
   changes, change both.

   The `overflow-wrap: normal` reset: the shared lines band sets `anywhere` so
   one unbreakable email address cannot widen the grid. Condition names are
   ordinary words and that rule would break them mid-word. */
.vhh-hero-spotlight__slot--conds .vhh-hero-spotlight__line-v {
    overflow-wrap: normal;
}

.vhh-hero-spotlight__slot--conds .vhh-hero-spotlight__line::after {
    content: "";
    flex: 0 0 auto;
    margin-left: auto;
    width: 7px;
    height: 7px;
    border-top: 2px solid currentColor;
    border-right: 2px solid currentColor;
    transform: rotate(45deg);
    color: var(--vhh-hs-title, #04504E);
    opacity: 0.45;
    transition: transform 0.18s ease, opacity 0.18s ease;
}

.vhh-hero-spotlight__slot--conds a.vhh-hero-spotlight__line:hover::after,
.vhh-hero-spotlight__slot--conds a.vhh-hero-spotlight__line:focus-visible::after {
    opacity: 1;
    transform: rotate(45deg) translate(2px, -2px);
}

@media (prefers-reduced-motion: reduce) {
    .vhh-hero-spotlight__slot--conds .vhh-hero-spotlight__line::after {
        transition: none;
    }
}

@media (min-width: 901px) {
    .vhh-hero-spotlight__slot--conds {
        grid-auto-flow: row;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .vhh-hero-spotlight__slot--conds .vhh-hero-spotlight__line {
        border-right: 0;
        border-bottom: 1px solid #DCE9E8;
    }

    .vhh-hero-spotlight__slot--conds .vhh-hero-spotlight__line:nth-child(odd) {
        border-right: 1px solid #DCE9E8;
    }

    .vhh-hero-spotlight__slot--conds .vhh-hero-spotlight__line:nth-last-child(-n + 2) {
        border-bottom: 0;
    }
}

/* --- Purple, job two: the lobby's seven chips ---------------------------- */

/* Two FIXED rows of four and three, not one wrapping flow. flex-wrap decides
   the row count from whatever the font happens to measure, and the first
   version of this band came out at three rows on the design width because of
   it. A fixed split makes two rows a property of the markup.

   Each chip grows to share its row, so the shorter second row carries wider
   chips rather than a hole where a fourth would be.

   `flex-wrap` is nevertheless ON, at every width. Between the 900px stacking
   breakpoint and about 1150px the copy column is too narrow for four names on
   one line, and a nowrap row there pushed ~160px of chips out through the
   band's rounded overflow clip. Wrapping costs a third row in that window and
   makes overflow impossible at every width. Measured: two rows at the design
   measure and again once the layout stacks, three in the window between. */
.vhh-hero-spotlight__slot--chips {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 14px;
}

.vhh-hero-spotlight__chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

/* No icon here, unlike the band cells above. Seven full condition names plus
   seven 17px tiles do not fit two rows of this column at any size that keeps
   the names readable, and on the one page whose whole job is orientation the
   words win. The icons still carry the set on the other seven pages.

   13px of padding on a 13px/1.25 label measures 44px, which is the touch-target
   minimum exactly. Do not trim it to match the band cells' optics. */
.vhh-hero-spotlight__chip {
    flex: 1 1 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 13px 14px;
    border-radius: var(--radius-control, 6px);
    background: <?php echo esc_attr( VANCE_GI_PURPLE_TINT ); ?>;
    border: 1px solid <?php echo esc_attr( VANCE_GI_PURPLE_LINE ); ?>;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.2s ease, border-color 0.2s ease;
}

.vhh-hero-spotlight__chip span {
    font-family: var(--font-heading, 'Outfit', sans-serif);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.25;
    color: <?php echo esc_attr( VANCE_GI_PURPLE_INK ); ?>;
    white-space: nowrap;
    transition: color 0.2s ease;
}

.vhh-hero-spotlight__chip:hover,
.vhh-hero-spotlight__chip:focus-visible {
    background: <?php echo esc_attr( VANCE_GI_PURPLE ); ?>;
    border-color: <?php echo esc_attr( VANCE_GI_PURPLE ); ?>;
}

.vhh-hero-spotlight__chip:hover span,
.vhh-hero-spotlight__chip:focus-visible span {
    color: #ffffff;
}

.vhh-hero-spotlight__chip:focus-visible {
    outline: 3px solid <?php echo esc_attr( VANCE_GI_PURPLE_INK ); ?>;
    outline-offset: 2px;
}

/* --- Breadcrumb ---------------------------------------------------------- */

/* The policy heroes have none: a policy document is a leaf with no parent to
   climb to. A condition page sits under the lobby, and the band's fourth cell
   is at the BOTTOM of the copy column — too far to serve as orientation for a
   reader who has arrived from a search result and wants to know where they
   are before they read anything. */
.vhh-hero-spotlight__crumb {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    align-items: center;
    font-size: 13px;
    color: #4F6462;
    margin: 0 0 16px;
}

.vhh-hero-spotlight__crumb a {
    color: var(--vhh-hs-title, #04504E);
    font-weight: 600;
    text-decoration: none;
    border-bottom: 1px solid rgba(4, 80, 78, 0.32);
}

.vhh-hero-spotlight__crumb a:hover,
.vhh-hero-spotlight__crumb a:focus-visible {
    border-bottom-color: var(--vhh-hs-title, #04504E);
}

.vhh-hero-spotlight__crumb-sep { color: #9BB0AE; }

/* --- The card's one addition --------------------------------------------- */

.vhh-hero-spotlight__card-meta {
    font-size: 12.5px;
    color: #4F6462;
    margin: 12px 0 0;
    padding-top: 11px;
    border-top: 1px solid #C7DEDD;
}

.vhh-hero-spotlight__card-meta b {
    color: var(--vhh-hs-title, #04504E);
    font-weight: 600;
}

/* --- Headline ------------------------------------------------------------ */

/* main.css caps the headline at 520px to hold the homepage's three-line
   headline off the search field below it, and at 420px below 1100px. Every
   headline here is a condition name that belongs on one line — the longest,
   "Irritable Bowel Syndrome", measures 626px at the scale's 56px ceiling — so
   both caps are in the worst possible place. 660px clears it with room and
   stays inside the copy column, so it cannot reach the photograph. */
.vhh-hero-spotlight--gi .vhh-hero-spotlight__title {
    max-width: 660px;
}

/* --- Spacing ------------------------------------------------------------- */

/* The lobby's next section opens with .section-padding, which carries 80px of
   its own. Stacked on the hero's bottom padding that is ~144px of empty band
   between the intro and the stats card. */
.vhh-hero-spotlight--gi + .section-padding {
    padding-top: 40px;
}

/* The condition pages open with .gi-cp-container, whose 20px is fine, so it is
   deliberately not touched here. */

@media (max-width: 900px) {
    /* main.css zeroes the hero's TOP padding at this width because that is
       where the photograph drops back into flow and provides the spacing
       itself. A hero whose photograph is missing — an admin cleared the
       Customizer image and the theme asset is gone — has nothing to drop, and
       would jam against the site header. */
    .vhh-hero-spotlight--gi.has-no-media {
        padding: 40px 0 44px;
    }
}
</style>
	<?php
}

/**
 * The card, identical on all eight pages.
 *
 * What a set of medical pages owes a reader, and repeating it verbatim is the
 * point — it is the one element that does not change between the lobby and the
 * seven, and so it is a large part of what makes them read as one thing.
 *
 * THE DATE IS OPT-IN. There is no per-page reviewed-on field in this theme, so
 * the line renders only when an admin has set the site-wide value at
 * Appearance → Customize → Page - GI Health → Conditions, Shared. Left unset,
 * the card simply does not claim a date. A stale date on a clinical page is
 * worse than no date, and a hard-coded one would be stale the moment it
 * shipped.
 *
 * @return void
 */
function vance_gi_hero_card() {
	$reviewed = vance_get_theme_mod( 'vance_gi_reviewed', '' );
	?>
	<aside class="vhh-hero-spotlight__card">
		<span class="vhh-hero-spotlight__card-icon" aria-hidden="true"><?php
			echo vance_gi_hero_icon( 'review' ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup
		?></span>
		<div class="vhh-hero-spotlight__card-body">
			<h2 class="vhh-hero-spotlight__card-title"><?php
				esc_html_e( 'Written plainly, checked by a clinician', 'vance-health-hub' );
			?></h2>
			<p class="vhh-hero-spotlight__card-text"><?php
				esc_html_e( 'Every page in this set is reviewed before it is published. None of it replaces advice from your own care team.', 'vance-health-hub' );
			?></p>
			<?php if ( $reviewed !== '' ) : ?>
			<p class="vhh-hero-spotlight__card-meta"><?php
				printf(
					/* translators: %s: the date the condition pages were last reviewed, e.g. "August 2026". */
					esc_html__( 'Last reviewed %s', 'vance-health-hub' ),
					'<b>' . esc_html( $reviewed ) . '</b>' // phpcs:ignore WordPress.Security.EscapeOutput — escaped here
				);
			?></p>
			<?php endif; ?>
		</div>
	</aside>
	<?php
}

/**
 * The photograph's markup, or nothing.
 *
 * First in source order for the same reason as the other spotlight heroes: on
 * desktop it is absolutely positioned so source order is irrelevant, and when
 * the layout stacks it lands above the headline with no `order` and no second
 * copy of the markup.
 *
 * @param array{src: string, alt: string, focal: string}|null $photo
 * @return void
 */
function vance_gi_hero_media( $photo ) {
	if ( ! $photo ) { return; }
	?>
	<div class="vhh-hero-spotlight__media">
		<img src="<?php echo esc_url( $photo['src'] ); ?>"
		     alt="<?php echo esc_attr( $photo['alt'] ); ?>"
		     style="object-position: <?php echo esc_attr( $photo['focal'] ); ?>;">
	</div>
	<?php
}

/**
 * Render the hero for one condition page.
 *
 * @param string $slug A page's post_name. Anything not in the set renders
 *                     nothing, so the caller can fall through to its own hero.
 * @return bool True if a hero was rendered.
 */
function vance_render_gi_hero( $slug ) {
	$meta = vance_gi_hero_slug_meta( $slug );
	if ( ! $meta ) { return false; }

	// The admin-saved title and lede win over the literals above. Both settings
	// already exist and are already populated on the live site, so the hero
	// must read them or a switch of design would silently discard editing work.
	$conds  = vance_gi_conditions();
	$key    = isset( $conds[ $slug ]['key'] ) ? $conds[ $slug ]['key'] : '';
	$title  = $key ? vance_get_theme_mod( "vance_gi_cond_{$key}_title", vance_gi_hero_label( $slug ) ) : vance_gi_hero_label( $slug );
	$intro  = $key ? vance_get_theme_mod( "vance_gi_cond_{$key}_lede", $meta['intro'] ) : $meta['intro'];
	$photo  = vance_gi_hero_photo( $slug );
	$cells  = vance_gi_hero_cells( $slug );

	$hub_url   = vance_gi_hub_url();
	$hub_page  = get_page_by_path( 'gastro-health-explained' );
	if ( ! $hub_page ) { $hub_page = get_page_by_path( 'gi-health' ); }
	$hub_label = $hub_page ? get_the_title( $hub_page ) : __( 'Gastro Health Explained', 'vance-health-hub' );

	vance_gi_hero_styles();
	?>
	<section class="vhh-hero-spotlight vhh-hero-spotlight--page vhh-hero-spotlight--gi vhh-hero-spotlight--gi-<?php
		echo esc_attr( $key ? $key : $slug ); ?><?php echo $photo ? '' : ' has-no-media'; ?>">

		<?php vance_gi_hero_media( $photo ); ?>

		<div class="container vhh-hero-spotlight__inner">
			<div class="vhh-hero-spotlight__copy">

				<p class="vhh-hero-spotlight__crumb">
					<a href="<?php echo esc_url( $hub_url ); ?>"><?php echo esc_html( $hub_label ); ?></a>
					<span class="vhh-hero-spotlight__crumb-sep" aria-hidden="true">/</span>
					<span><?php echo wp_kses_post( $title ); ?></span>
				</p>

				<span class="vhh-hero-spotlight__eyebrow"><?php echo esc_html( $meta['eyebrow'] ); ?></span>

				<h1 class="vhh-hero-spotlight__title"><?php echo wp_kses_post( $title ); ?></h1>

				<?php if ( $intro !== '' ) : ?>
				<p class="vhh-hero-spotlight__intro"><?php echo esc_html( $intro ); ?></p>
				<?php endif; ?>

				<div class="vhh-hero-spotlight__slot-wrap">
					<span class="vhh-hero-spotlight__slot-label"><?php
						esc_html_e( 'Others in this set', 'vance-health-hub' );
					?></span>

					<div class="vhh-hero-spotlight__slot vhh-hero-spotlight__slot--lines vhh-hero-spotlight__slot--conds">
						<?php foreach ( $cells as $cell ) : ?>
						<a class="vhh-hero-spotlight__line" href="<?php echo esc_url( $cell['href'] ); ?>">
							<span class="vhh-hero-spotlight__line-ico"><?php
								echo vance_gi_hero_icon( $cell['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup
							?></span>
							<span class="vhh-hero-spotlight__line-body">
								<span class="vhh-hero-spotlight__line-k"><?php echo esc_html( $cell['label'] ); ?></span>
								<span class="vhh-hero-spotlight__line-v"><?php echo esc_html( $cell['value'] ); ?></span>
							</span>
						</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<?php vance_gi_hero_card(); ?>
		</div>
	</section>
	<?php
	return true;
}

/**
 * Render the lobby hero.
 *
 * @return void
 */
function vance_render_gi_hub_hero() {
	$d = vance_gi_hero_hub_defaults();

	$eyebrow = vance_get_theme_mod( 'vance_gi_hub_hero_eyebrow',   $d['eyebrow'] );
	$heading = vance_get_theme_mod( 'vance_gi_hub_hero_heading',   $d['heading'] );
	$lede    = vance_get_theme_mod( 'vance_gi_hub_hero_lede',      $d['lede'] );
	$b1_txt  = vance_get_theme_mod( 'vance_gi_hub_hero_btn1_text', $d['btn1_text'] );
	$b1_url  = vance_get_theme_mod( 'vance_gi_hub_hero_btn1_url',  $d['btn1_url'] );
	$b2_txt  = vance_get_theme_mod( 'vance_gi_hub_hero_btn2_text', $d['btn2_text'] );
	$b2_url  = vance_get_theme_mod( 'vance_gi_hub_hero_btn2_url',  $d['btn2_url'] );

	// The defaults are stored as paths so they follow the site's own host, the
	// way page-gi-health.php's dashboard default already did. A full URL saved
	// by an admin is left alone.
	$abs = function ( $u ) {
		return ( $u === '' || strpos( $u, '#' ) === 0 || preg_match( '#^[a-z]+:|^//#i', $u ) )
			? $u : home_url( $u );
	};
	$b1_url = $abs( $b1_url );
	$b2_url = $abs( $b2_url );

	$photo = vance_gi_hero_photo( '' );

	// Two fixed rows, four then three, in registry order. See the CSS.
	$slugs = array_keys( vance_gi_hero_meta() );
	$rows  = array( array_slice( $slugs, 0, 4 ), array_slice( $slugs, 4 ) );

	vance_gi_hero_styles();
	?>
	<section class="vhh-hero-spotlight vhh-hero-spotlight--page vhh-hero-spotlight--gi vhh-hero-spotlight--gi-hub<?php
		echo $photo ? '' : ' has-no-media'; ?>">

		<?php vance_gi_hero_media( $photo ); ?>

		<div class="container vhh-hero-spotlight__inner">
			<div class="vhh-hero-spotlight__copy">

				<?php if ( $eyebrow !== '' ) : ?>
				<span class="vhh-hero-spotlight__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
				<?php endif; ?>

				<h1 class="vhh-hero-spotlight__title"><?php echo wp_kses_post( $heading ); ?></h1>

				<?php if ( $lede !== '' ) : ?>
				<p class="vhh-hero-spotlight__intro"><?php echo esc_html( $lede ); ?></p>
				<?php endif; ?>

				<?php if ( $b1_txt !== '' || $b2_txt !== '' ) : ?>
				<div class="vhh-hero-spotlight__actions">
					<?php if ( $b1_txt !== '' ) : ?>
					<a class="vhh-hero-spotlight__cta" href="<?php echo esc_url( $b1_url ); ?>"><?php
						echo esc_html( $b1_txt );
						echo vance_gi_hero_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup
					?></a>
					<?php endif; ?>
					<?php if ( $b2_txt !== '' ) : ?>
					<a class="vhh-hero-spotlight__cta vhh-hero-spotlight__cta--ghost" href="<?php
						echo esc_url( $b2_url ); ?>"><?php echo esc_html( $b2_txt ); ?></a>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<div class="vhh-hero-spotlight__slot-wrap">
					<span class="vhh-hero-spotlight__slot-label"><?php
						esc_html_e( 'Jump straight to a condition', 'vance-health-hub' );
					?></span>

					<div class="vhh-hero-spotlight__slot vhh-hero-spotlight__slot--chips">
						<?php foreach ( $rows as $row ) : ?>
						<div class="vhh-hero-spotlight__chip-row">
							<?php foreach ( $row as $slug ) : ?>
							<a class="vhh-hero-spotlight__chip" href="<?php echo esc_url( vance_gi_page_url( $slug ) ); ?>">
								<span><?php echo esc_html( vance_gi_hero_label( $slug ) ); ?></span>
							</a>
							<?php endforeach; ?>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<?php vance_gi_hero_card(); ?>
		</div>
	</section>
	<?php
}
