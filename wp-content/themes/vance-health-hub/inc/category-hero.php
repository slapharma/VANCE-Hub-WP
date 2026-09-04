<?php
/**
 * Category archive heroes — the spotlight layout, section variant.
 *
 * The category archives were the last set on the site still rendering the old
 * dark band: a 350px `.hero` with a navy veil over one of four PNGs from 2026,
 * an eyebrow and a headline, and nothing else. Three templates carried their
 * own copy of it — archive.php, template-parts/subcategory-grouped-archive.php
 * and category-content-healthcare-news.php — which is why the same forty lines
 * of overlay-gradient arithmetic appear three times in this theme.
 *
 * They now render the same `.vhh-hero-spotlight` section as the homepage, the
 * page heroes, the eight GI pages and the five policy documents.
 *
 * WHAT BINDS THE SET
 *
 * A category page is a LOBBY, not a document: nobody arrives at
 * /category/content-gastro-living/ to read it, they arrive to find out what is
 * in it. So the three things this hero adds over the page variant all answer
 * "what is in here and how much of it":
 *
 *   the band   three live facts about the section — how many articles, how
 *              many topics, and when it was last added to. Computed per
 *              request from the section's own posts, never typed in, so a
 *              stale number is not possible.
 *   the card   what this section IS, in the section's own voice. One per
 *              top-level category, in code, the way the GI set's review card
 *              is — an editor renaming a category should not have to rewrite
 *              a promise about how it is put together.
 *   the crumb  on a sub-category only: the way back up to the parent, in the
 *              same corner of the page as the GI set's.
 *
 * WHY THE BAND IS NOT A JUMP LIST
 *
 * The obvious band for a lobby is a row of links to the sub-sections, and it
 * was the first thing tried. It cannot go here: on every grouped archive the
 * "Sub-category nav cards" strip renders IMMEDIATELY below this hero
 * (template-parts/inner-category-nav.php) and is already exactly that row.
 * Two rows of the same eight names, forty pixels apart, reads as a bug. The
 * strip's cells are same-page anchors and the hero's would have been archive
 * links, but nobody looking at the page can see that difference.
 *
 * So the band carries what the strip below cannot: the size and freshness of
 * the section. If the strip is ever removed, the jump list belongs here.
 *
 * SUB-CATEGORIES BORROW THE PARENT'S PHOTOGRAPH, DELIBERATELY
 *
 * Five sub-sections of Gastro Living would mean five more photographs cut to
 * the hero's brief, all of them saying "a person in a bright room", and the
 * set would stop reading as one section. The parent's picture on every child
 * is the thing that makes /food-nutrition/ look like part of Gastro Living.
 * Each child still has its own Photograph control, so any one of them can
 * break ranks without a code change.
 *
 * WHAT IS NOT IN THIS FILE
 *
 * The band's white card treatment, the cells, the icon tiles, the dividers,
 * the eyebrow pill, the type scale, the photograph's dissolve, the floating
 * card and the whole responsive ladder — including the doubled-class
 * `!important` rules that opt this hero out of the global mobile type
 * normalisation in mobile-base.css — are inherited from the committed
 * `.vhh-hero-spotlight` block in assets/css/main.css and are NOT repeated
 * here. Only what is genuinely new to this set lives in
 * vance_category_hero_styles().
 *
 * @package vance-health-hub
 * @since   2026-08-31
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Per-section hero copy, keyed by the top-level category's slug.
 *
 * Only TOP-LEVEL categories appear here. A sub-category reads its parent's
 * entry for the photograph, the card and the eyebrow, and supplies its own
 * name and its own WordPress description for the headline and the lede — see
 * vance_category_hero_meta().
 *
 *   eyebrow  the KIND of thing this section is, not its name. The name is
 *            already the headline directly underneath, and repeating it in
 *            the pill above wastes the one line that could say what sort of
 *            reading this is going to be.
 *   intro    the fallback lede. A category's WordPress description wins over
 *            it whenever one is set, because that field is editable from the
 *            ordinary Posts → Categories screen and this file is not.
 *   image    a file in assets/img/heroes/categories/. Missing files are not
 *            an error: the hero falls back to the same geometric motif the
 *            Knowledgebase, the 404 and the five policy documents use, so a
 *            section without a photograph still renders correctly.
 *   focal    object-position for the photograph. The media box is the right
 *            ~52% of the band while the assets are 1400x876, so the crop is
 *            horizontal and the first number is the one that matters.
 *   card     the floating card: an icon key, a heading and one sentence. It
 *            says what the section is FOR. It must not make a claim this
 *            codebase cannot stand behind — no licensing terms, no review
 *            cadence, no commercial-relationship promises.
 *
 * @return array<string, array<string, mixed>>
 */
function vance_category_hero_meta() {
	static $meta = null;
	if ( $meta !== null ) { return $meta; }

	$meta = array(

		/* ---- The three sections that carry content today ---------------- */

		'content-clinical-reviews' => array(
			'eyebrow' => __( 'The evidence', 'vance-health-hub' ),
			'intro'   => __( 'Trial data and peer-reviewed papers read closely and written up in plain English: what was measured, in whom, and what the result does and does not show.', 'vance-health-hub' ),
			'image'   => 'clinical-reviews.jpg',
			'alt'     => __( 'A woman at a desk in a bright office reading a printed research paper, pen in hand, a stack of journals beside her', 'vance-health-hub' ),
			'focal'   => '52% 20%',
			'card'    => array(
				'icon'  => 'review',
				'title' => __( 'Every review points back at the paper', 'vance-health-hub' ),
				'text'  => __( 'Each summary is written from the published source and cites it, so you can go and read the original. None of it replaces advice from your own care team.', 'vance-health-hub' ),
			),
		),

		'content-gastro-living' => array(
			'eyebrow' => __( 'Living with it', 'vance-health-hub' ),
			'intro'   => __( 'Practical guidance for life with a gut condition: understanding a diagnosis, eating well around it, getting through tests and treatments, and finding support.', 'vance-health-hub' ),
			'image'   => 'gastro-living.jpg',
			'alt'     => __( 'A man sitting in an armchair by a bright window, lacing a walking boot, his coat over the chair beside him', 'vance-health-hub' ),
			'focal'   => '54% 24%',
			'card'    => array(
				'icon'  => 'people',
				'title' => __( 'Written for the day you are having', 'vance-health-hub' ),
				'text'  => __( 'Plain language, no jargon and no scare stories. Everything here is checked by a clinician before it is published.', 'vance-health-hub' ),
			),
		),

		'content-healthcare-news' => array(
			'eyebrow' => __( 'What changed', 'vance-health-hub' ),
			'intro'   => __( 'Developments in gastroenterology, nutrition science and health policy, reported as they happen and grouped by when they were published.', 'vance-health-hub' ),
			'image'   => 'healthcare-news.jpg',
			'alt'     => __( 'A doctor with a lanyard pausing against the wall of a bright hospital corridor to read something on a phone', 'vance-health-hub' ),
			'focal'   => '50% 18%',
			'card'    => array(
				'icon'  => 'signal',
				'title' => __( 'Newest first, always', 'vance-health-hub' ),
				'text'  => __( 'This section is grouped by publication date rather than by topic, so the top of the page is genuinely the top of the news.', 'vance-health-hub' ),
			),
		),

		/* ---- The sections that exist but are not populated yet ----------- */
		/*
		 * They render today, with the motif in place of a photograph, because
		 * a category with no posts is still reachable from the footer and the
		 * mega menu and should not be the one page on the site still wearing
		 * the old dark band. Each card says what the section IS rather than
		 * how good it is — see the note on `card` above.
		 */

		'content-expert-opinions' => array(
			'eyebrow' => __( 'Perspective', 'vance-health-hub' ),
			'intro'   => __( 'Signed commentary from clinicians and researchers: argued positions, clearly attributed, and kept separate from the evidence summaries.', 'vance-health-hub' ),
			'image'   => 'expert-opinions.jpg',
			'alt'     => '',
			'focal'   => '52% 20%',
			'card'    => array(
				'icon'  => 'quote',
				'title' => __( 'Argued, and attributed', 'vance-health-hub' ),
				'text'  => __( 'Every piece here carries a named author and takes a position. It sits apart from Clinical Reviews on purpose.', 'vance-health-hub' ),
			),
		),

		'content-education-courses' => array(
			'eyebrow' => __( 'Learning', 'vance-health-hub' ),
			'intro'   => __( 'Structured courses and recorded webinars for healthcare professionals, built to be worked through at your own pace.', 'vance-health-hub' ),
			'image'   => 'education-courses.jpg',
			'alt'     => '',
			'focal'   => '52% 20%',
			'card'    => array(
				'icon'  => 'cap',
				'title' => __( 'In your own time, in any order', 'vance-health-hub' ),
				'text'  => __( 'Nothing here is a timetable. Each course stands on its own and can be started, left and picked up again.', 'vance-health-hub' ),
			),
		),

		'content-white-papers' => array(
			'eyebrow' => __( 'In depth', 'vance-health-hub' ),
			'intro'   => __( 'Long-form analysis and position papers, for the questions a summary cannot settle.', 'vance-health-hub' ),
			'image'   => 'white-papers.jpg',
			'alt'     => '',
			'focal'   => '52% 20%',
			'card'    => array(
				'icon'  => 'doc',
				'title' => __( 'Longer, on purpose', 'vance-health-hub' ),
				'text'  => __( 'These are full documents rather than article summaries, for the times when the detail is the point.', 'vance-health-hub' ),
			),
		),

		'content-product-reviews' => array(
			'eyebrow' => __( 'Assessed', 'vance-health-hub' ),
			'intro'   => __( 'Medical foods, supplements and devices set against what the published evidence behind them actually says.', 'vance-health-hub' ),
			'image'   => 'product-reviews.jpg',
			'alt'     => '',
			'focal'   => '52% 20%',
			'card'    => array(
				'icon'  => 'scale',
				'title' => __( 'The basis, not just the verdict', 'vance-health-hub' ),
				'text'  => __( 'Each assessment sets out which evidence it is drawing on, so you can weigh it for yourself.', 'vance-health-hub' ),
			),
		),

		'content-infographic' => array(
			'eyebrow' => __( 'At a glance', 'vance-health-hub' ),
			'intro'   => __( 'Clinical information drawn out as a single picture, made to be read in seconds and understood on its own.', 'vance-health-hub' ),
			'image'   => 'infographic.jpg',
			'alt'     => '',
			'focal'   => '52% 20%',
			'card'    => array(
				'icon'  => 'image',
				'title' => __( 'One picture, one idea', 'vance-health-hub' ),
				'text'  => __( 'Each graphic covers a single topic, so it makes sense without the article it came from.', 'vance-health-hub' ),
			),
		),

		'content-media-library' => array(
			'eyebrow' => __( 'Watch and listen', 'vance-health-hub' ),
			'intro'   => __( 'Podcasts, recorded webinars and short video explainers, for when reading is not the easiest way in.', 'vance-health-hub' ),
			'image'   => 'media-library.jpg',
			'alt'     => '',
			'focal'   => '52% 20%',
			'card'    => array(
				'icon'  => 'play',
				'title' => __( 'Another way in', 'vance-health-hub' ),
				'text'  => __( 'Audio and video for the times when a page of text is not the route to the answer you want.', 'vance-health-hub' ),
			),
		),

		'content-tools-resources' => array(
			'eyebrow' => __( 'Yours to use', 'vance-health-hub' ),
			'intro'   => __( 'Calculators, planners and printable resources you can pick up and use straight away.', 'vance-health-hub' ),
			'image'   => 'tools-resources.jpg',
			'alt'     => '',
			'focal'   => '52% 20%',
			'card'    => array(
				'icon'  => 'tools',
				'title' => __( 'Free, and free to print', 'vance-health-hub' ),
				'text'  => __( 'Everything in this section is free to use. The printable resources are meant to be taken to an appointment.', 'vance-health-hub' ),
			),
		),

		'gastro-recipes' => array(
			'eyebrow' => __( 'Cooking', 'vance-health-hub' ),
			'intro'   => __( 'Recipes built around real ingredients and a gut that needs some thought, each one listing what is in it and what it gives you.', 'vance-health-hub' ),
			'image'   => 'gastro-recipes.jpg',
			'alt'     => '',
			'focal'   => '52% 24%',
			'card'    => array(
				'icon'  => 'bowl',
				'title' => __( 'Checkable against your own plan', 'vance-health-hub' ),
				'text'  => __( 'Every recipe states its ingredients and its nutrition, so it can be held up against whatever you have been advised.', 'vance-health-hub' ),
			),
		),
	);

	/**
	 * Filter the per-section hero copy.
	 *
	 * The escape hatch for a category this file has never heard of — a new
	 * top-level section can be given a photograph and a card from a child
	 * theme without editing the registry. Anything not listed here and not
	 * added by a filter still renders: see vance_category_hero_meta_for().
	 *
	 * @param array $meta Keyed by top-level category slug.
	 */
	$meta = apply_filters( 'vance_category_hero_meta', $meta );

	return $meta;
}

/**
 * The registry entry that governs one term.
 *
 * A top-level category reads its own. A sub-category reads its PARENT's —
 * that is the rule that makes /food-nutrition/ look like part of Gastro
 * Living rather than like a page of its own. See the file header.
 *
 * @param WP_Term $term
 * @return array{meta: array<string, mixed>|null, parent: WP_Term|null}
 */
function vance_category_hero_meta_for( $term ) {
	$parent = null;

	if ( $term->parent ) {
		$maybe = get_term( $term->parent, 'category' );
		if ( $maybe instanceof WP_Term ) { $parent = $maybe; }
	}

	$key  = $parent ? $parent->slug : $term->slug;
	$all  = vance_category_hero_meta();
	$meta = isset( $all[ $key ] ) ? $all[ $key ] : null;

	return array( 'meta' => $meta, 'parent' => $parent );
}

/**
 * A term's name, ready to be escaped once.
 *
 * WordPress stores term names HTML-encoded — the live taxonomy holds
 * `Food &amp; Nutrition` and `Tests &amp; Treatments`, not the bare ampersand.
 * That leaves two wrong answers and one right one:
 *
 *   esc_html( $term->name )        -> `Food &amp;amp; Nutrition`, which the
 *                                     browser draws as "Food &amp; Nutrition".
 *   wp_kses_post( $term->name )    -> right on screen, but it is a markup
 *                                     filter standing in for an escaper, so a
 *                                     name containing a bare `<` walks
 *                                     straight through.
 *   decode once, then escape once  -> correct for both.
 *
 * The old dark heroes only ever printed the name in one place (the headline,
 * through wp_kses_post) so the inconsistency had nowhere to show. This hero
 * prints it in three — headline, breadcrumb and, for a parent, the eyebrow —
 * and two of them would have disagreed.
 *
 * @param WP_Term $term
 * @return string Decoded, NOT escaped. Every caller escapes it.
 */
function vance_category_hero_term_name( $term ) {
	// UTF-8 literal rather than get_bloginfo('charset'): WordPress has emitted
	// UTF-8 unconditionally since 4.2, and reading the option here would make
	// this function need a database.
	return html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
}

/**
 * How big this section is, and when it last grew.
 *
 * ONE query, and it is the query WordPress would run for the archive anyway:
 * `cat` (unlike `category__in`) includes a term's descendants, so a parent
 * counts its children's posts and reads the newest date across all of them.
 * That is the number a visitor means by "how much is in here" — Gastro Living
 * itself holds 89 posts across five sub-sections, and `$term->count` would
 * report only those pinned directly to the parent.
 *
 * `fields => ids` and `posts_per_page => 1` keep it to one row plus the count;
 * `found_posts` still reflects the whole set because pagination is left on.
 *
 * @param int $term_id
 * @return array{total: int, topics: int, latest: string} `latest` is a
 *               localised "August 2026", or '' when the section is empty.
 */
function vance_category_hero_facts( $term_id ) {
	static $cache = array();
	if ( isset( $cache[ $term_id ] ) ) { return $cache[ $term_id ]; }

	$q = new WP_Query( array(
		'cat'                    => $term_id,
		'post_status'            => 'publish',
		'posts_per_page'         => 1,
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'fields'                  => 'ids',
		'ignore_sticky_posts'    => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	) );

	$latest = '';
	if ( ! empty( $q->posts ) ) {
		// date_i18n over get_the_date so the month name follows the site's
		// locale rather than the template's hard-coded English.
		$latest = date_i18n( 'F Y', (int) get_post_time( 'U', false, $q->posts[0] ) );
	}

	$topics = get_categories( array(
		'parent'     => $term_id,
		'hide_empty' => true,
		'fields'     => 'ids',
	) );

	$cache[ $term_id ] = array(
		'total'  => (int) $q->found_posts,
		'topics' => is_array( $topics ) ? count( $topics ) : 0,
		'latest' => $latest,
	);

	wp_reset_postdata();

	return $cache[ $term_id ];
}

/**
 * The photograph, or null for the motif.
 *
 * Resolution order, most specific first:
 *
 *   1. this term's own Photograph control (vance_cat_photo_<id>);
 *   2. the PARENT's Photograph control, for a sub-category — so setting one
 *      picture on Gastro Living re-skins all five of its sub-sections;
 *   3. the registry's file in assets/img/heroes/categories/;
 *   4. nothing, and the caller draws the motif.
 *
 * Step 3 is gated on file_exists rather than assumed, which is what lets the
 * nine sections that have no photograph yet render correctly today.
 *
 * NOTE the deliberate absence of `vance_cat_hero_<id>`, the control the dark
 * band reads. Those images were chosen to sit under a 78% navy veil; dropped
 * onto a pale mint band with no veil they read as a dark smear. Same reason
 * inc/page-hero-spotlight.php gave Contact a second image key rather than
 * reusing vance_contact_hero_img.
 *
 * @param WP_Term      $term
 * @param WP_Term|null $parent
 * @param array|null   $meta
 * @return array{src: string, alt: string, focal: string}|null
 */
function vance_category_hero_photo( $term, $parent, $meta ) {
	$focal = ( $meta && ! empty( $meta['focal'] ) ) ? $meta['focal'] : '52% 20%';

	foreach ( array_filter( array( $term, $parent ) ) as $candidate ) {
		$saved = vance_get_theme_mod( "vance_cat_photo_{$candidate->term_id}", '' );
		if ( $saved !== '' ) {
			// An admin-chosen image has no alt text of its own to read, so the
			// registry's description would be a lie about a different
			// photograph. Empty alt is correct and not laziness: the headline
			// and lede already carry the meaning, which makes the image
			// decorative, and announcing a wrong description is worse than
			// announcing none.
			return array( 'src' => $saved, 'alt' => '', 'focal' => $focal );
		}
	}

	if ( ! $meta || empty( $meta['image'] ) ) { return null; }

	$rel  = '/assets/img/heroes/categories/' . $meta['image'];
	$file = get_template_directory() . $rel;
	if ( ! file_exists( $file ) ) { return null; }

	return array(
		'src'   => add_query_arg( 'v', filemtime( $file ), get_template_directory_uri() . $rel ),
		'alt'   => isset( $meta['alt'] ) ? $meta['alt'] : '',
		'focal' => $focal,
	);
}

/**
 * One inline icon.
 *
 * Same 24-unit box and 1.9 stroke weight as inc/page-hero-spotlight.php,
 * inc/legal-hero.php and inc/gi-hero.php, so all four hero families read as
 * one set of marks. Kept separate rather than calling any of those for the
 * reason legal-hero gives: a category archive should not stop rendering
 * because a tool hero was refactored.
 *
 * @param string $name A key below.
 * @return string SVG markup. Static — no dynamic values, nothing to escape.
 */
function vance_category_hero_icon( $name ) {
	$paths = array(
		/* --- the band's three facts --- */
		// Stacked sheets: a body of articles.
		'stack'  => '<path d="M7.4 3.2h9.2a1.8 1.8 0 0 1 1.8 1.8v11.4a1.8 1.8 0 0 1-1.8 1.8H7.4a1.8 1.8 0 0 1-1.8-1.8V5a1.8 1.8 0 0 1 1.8-1.8Z"/><path d="M8.9 7.1h6.2"/><path d="M8.9 10.3h6.2"/><path d="M8.9 13.5h3.7"/><path d="M20.6 7.6v11.6a1.8 1.8 0 0 1-1.8 1.8H8.2"/>',
		// Four tiles: the section divides into topics.
		'tiles'  => '<rect x="3.4" y="3.4" width="7.2" height="7.2" rx="1.6"/><rect x="13.4" y="3.4" width="7.2" height="7.2" rx="1.6"/><rect x="3.4" y="13.4" width="7.2" height="7.2" rx="1.6"/><rect x="13.4" y="13.4" width="7.2" height="7.2" rx="1.6"/>',
		// A clock: when this last grew.
		'clock'  => '<circle cx="12" cy="12" r="8.6"/><path d="M12 7.1V12l3.2 1.9"/>',

		/* --- the cards, one per section --- */
		// Clipboard with a tick: checked against a source.
		'review' => '<path d="M15.2 4.4h1.9a2.3 2.3 0 0 1 2.3 2.3v12.1a2.3 2.3 0 0 1-2.3 2.3H6.9a2.3 2.3 0 0 1-2.3-2.3V6.7a2.3 2.3 0 0 1 2.3-2.3h1.9"/><rect x="8.8" y="2.6" width="6.4" height="3.7" rx="1.3"/><path d="M8.9 13.2l2.2 2.2 4.2-4.4"/>',
		// Two figures, one slightly behind: written for people, with people.
		'people' => '<circle cx="9.2" cy="8.1" r="3.3"/><path d="M3.4 19.9a5.9 5.9 0 0 1 11.6 0"/><path d="M16.2 5.2a3.3 3.3 0 0 1 0 6.4"/><path d="M17.4 14.4a5.9 5.9 0 0 1 3.2 5.5"/>',
		// Broadcast arcs from a point: news arriving.
		'signal' => '<circle cx="12" cy="12" r="1.9"/><path d="M8.2 8.2a5.4 5.4 0 0 0 0 7.6"/><path d="M15.8 15.8a5.4 5.4 0 0 0 0-7.6"/><path d="M5.5 5.5a9.2 9.2 0 0 0 0 13"/><path d="M18.5 18.5a9.2 9.2 0 0 0 0-13"/>',
		// An open quotation: a named voice.
		'quote'  => '<path d="M9.4 6.6c-2.9 1-4.6 3.4-4.6 6.5v4.3h5.4v-5.4H7.5c0-1.9.9-3.2 2.6-3.9Z"/><path d="M19.2 6.6c-2.9 1-4.6 3.4-4.6 6.5v4.3h5.4v-5.4h-2.7c0-1.9.9-3.2 2.6-3.9Z"/>',
		// A mortarboard: structured learning.
		'cap'    => '<path d="M12 4.1 2.9 8.6 12 13.1l9.1-4.5L12 4.1Z"/><path d="M6.4 10.9v4.6c0 1.6 2.5 2.9 5.6 2.9s5.6-1.3 5.6-2.9v-4.6"/><path d="M21.1 8.6v5.2"/>',
		// A page with a folded corner: a full document.
		'doc'    => '<path d="M14.1 3.1H7.4a1.9 1.9 0 0 0-1.9 1.9v14a1.9 1.9 0 0 0 1.9 1.9h9.2a1.9 1.9 0 0 0 1.9-1.9V7.4l-4.4-4.3Z"/><path d="M14.1 3.1v4.3h4.4"/><path d="M8.6 12.4h6.8"/><path d="M8.6 16.1h6.8"/>',
		// A balance: weighed against something.
		'scale'  => '<path d="M12 4.2v15.6"/><path d="M7.2 19.8h9.6"/><path d="M4.4 6.6h15.2"/><path d="M4.4 6.6 1.9 12.6a2.9 2.9 0 0 0 5 0L4.4 6.6Z"/><path d="M19.6 6.6l-2.5 6a2.9 2.9 0 0 0 5 0l-2.5-6Z"/>',
		// A framed picture with a horizon: a graphic.
		'image'  => '<rect x="3.3" y="4.4" width="17.4" height="15.2" rx="2.1"/><circle cx="8.6" cy="9.6" r="1.7"/><path d="M20.7 15.6l-4.6-4.3-8.3 8.3"/>',
		// A play triangle in a circle.
		'play'   => '<circle cx="12" cy="12" r="8.6"/><path d="M10.2 8.5l6 3.5-6 3.5V8.5Z"/>',
		// A spanner crossed with nothing — a single tool, read at 26px.
		'tools'  => '<path d="M15.1 3.4a5.1 5.1 0 0 0-4.4 7.7L3.9 17.9a1.8 1.8 0 0 0 2.6 2.6l6.8-6.8a5.1 5.1 0 0 0 6.6-6.9l-2.9 2.9-2.6-.7-.7-2.6 2.9-2.9a5.1 5.1 0 0 0-1.5-.1Z"/>',
		// A bowl with steam: cooking.
		'bowl'   => '<path d="M3.4 11.6h17.2a8.6 8.6 0 0 1-8.6 8.1 8.6 8.6 0 0 1-8.6-8.1Z"/><path d="M9.4 8.4c0-1.4 1.4-1.4 1.4-2.8s-1.4-1.4-1.4-2.8"/><path d="M14 8.4c0-1.4 1.4-1.4 1.4-2.8S14 4.2 14 2.8"/>',
		// The generic card mark, for a section the registry has never heard of.
		'folder' => '<path d="M3.4 7.1a1.9 1.9 0 0 1 1.9-1.9h3.6l2 2.6h7.8a1.9 1.9 0 0 1 1.9 1.9v8.9a1.9 1.9 0 0 1-1.9 1.9H5.3a1.9 1.9 0 0 1-1.9-1.9V7.1Z"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) { return ''; }

	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"'
		. ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
		. $paths[ $name ] . '</svg>';
}

/**
 * The geometric motif that stands in for a photograph.
 *
 * Drawn for the sections that have no picture yet. The same figure the
 * Knowledgebase lobby, the 404 and the five policy documents use — a bloom, a
 * set of arcs and a dot field — reproduced here rather than called for the
 * reason inc/legal-hero.php gives for its own copy: this file is loaded by
 * every category archive on the site and must not depend on a page-hero
 * refactor. The gradient ids differ from both other copies because two
 * identical ids on one document repaint whichever lost.
 *
 * It is a DEFAULT, not a ceiling: every category keeps its Photograph control,
 * and setting one switches the renderer to the __media branch.
 *
 * @return string SVG markup. Static — no dynamic values, nothing to escape.
 */
function vance_category_hero_motif() {
	$dots = '';
	for ( $row = 0; $row < 4; $row++ ) {
		for ( $col = 0; $col < 7; $col++ ) {
			$dots .= sprintf( '<circle cx="%d" cy="%d" r="2.1"/>', 392 + ( $col * 30 ), 322 + ( $row * 30 ) );
		}
	}

	return '<svg viewBox="0 0 640 520" preserveAspectRatio="xMaxYMid slice" aria-hidden="true" focusable="false">'
		. '<defs>'
		. '<radialGradient id="vhhCatBloom" cx="70%" cy="26%" r="64%">'
		. '<stop offset="0%" stop-color="#AFD6D4" stop-opacity="0.60"/>'
		. '<stop offset="52%" stop-color="#CBE4E2" stop-opacity="0.26"/>'
		. '<stop offset="100%" stop-color="#CBE4E2" stop-opacity="0"/>'
		. '</radialGradient>'
		. '<linearGradient id="vhhCatArc" x1="0" y1="1" x2="1" y2="0">'
		. '<stop offset="0%" stop-color="#04504E" stop-opacity="0.03"/>'
		. '<stop offset="48%" stop-color="#04504E" stop-opacity="0.30"/>'
		. '<stop offset="100%" stop-color="#04504E" stop-opacity="0.07"/>'
		. '</linearGradient>'
		. '</defs>'
		. '<rect width="640" height="520" fill="url(#vhhCatBloom)"/>'
		. '<g fill="none" stroke="url(#vhhCatArc)">'
		. '<circle cx="486" cy="150" r="118" stroke-width="1.5"/>'
		. '<circle cx="486" cy="150" r="188" stroke-width="1.2"/>'
		. '<circle cx="486" cy="150" r="262" stroke-width="1"/>'
		. '<circle cx="486" cy="150" r="342" stroke-width="0.9"/>'
		. '</g>'
		. '<g fill="#04504E" opacity="0.13" stroke="none">' . $dots . '</g>'
		. '</svg>';
}

/**
 * The block of CSS this hero adds on top of the committed
 * `.vhh-hero-spotlight` rules in assets/css/main.css.
 *
 * Printed once per request behind a static, so a template that calls the hero
 * twice cannot emit it twice. Inline rather than in main.css for the reason
 * inc/gi-hero.php is: the set is new, the rules are still moving, and inline
 * CSS is not subject to the Jetpack Boost asset bundle, whose cache is the
 * slowest thing on this site to invalidate.
 *
 * Radii are written as `var(--radius-*, Npx)` with a literal fallback, per
 * CLAUDE.md §5, because this block can render where main.css has not defined
 * the scale.
 *
 * @return void
 */
function vance_category_hero_styles() {
	static $done = false;
	if ( $done ) { return; }
	$done = true;
	?>
<style id="vhh-category-hero-css">
/* ===========================================================================
   CATEGORY ARCHIVE HEROES  (inc/category-hero.php)
   Everything not listed here is inherited from the .vhh-hero-spotlight block
   in assets/css/main.css. Do not restate it.
   ======================================================================== */

/* --- The band of facts --------------------------------------------------- */

/* The `lines` markup unchanged — icon tile, caption, value — so the tile, the
   type, the dividers and the mobile stack all come from the shared rules.
   Two things are added. */

.vhh-hero-spotlight__slot--facts .vhh-hero-spotlight__line-v {
    /* The shared lines rule sets `overflow-wrap: anywhere` so one unbreakable
       email address cannot widen the band. These values are a number and a
       month name; that rule would break "September" across two lines. */
    overflow-wrap: normal;
    /* "89" beside "5" beside "August 2026" — the figures are the point of the
       band, so they are set a step larger than a contact line's value and
       given even digit widths so the three cells do not jitter between a
       section with 8 posts and one with 89. */
    font-size: 17px;
    font-variant-numeric: tabular-nums;
}

/* No cell in this band is a link, so it must not offer the tools band's
   affordance arrow or its hover fill. Nothing to add — the shared rules only
   apply those to `a.vhh-hero-spotlight__line`, and these are <div>s. */

/* --- Breadcrumb (sub-category pages only) -------------------------------- */

/* Identical values to the crumb in inc/gi-hero.php. Copied rather than shared
   for the same reason as the motif above: the two never render on one page,
   and a category archive should not depend on the GI set's stylesheet being
   present. If they are ever merged, merge both copies. */

.vhh-hero-spotlight__crumb {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    line-height: 1.4;
    color: #4F6462;
    margin: 0 0 14px;
}

.vhh-hero-spotlight__crumb a {
    color: var(--vhh-hs-title, #04504E);
    font-weight: 600;
    text-decoration: none;
    border-bottom: 1px solid transparent;
}

.vhh-hero-spotlight__crumb a:hover,
.vhh-hero-spotlight__crumb a:focus-visible {
    border-bottom-color: var(--vhh-hs-title, #04504E);
}

.vhh-hero-spotlight__crumb-sep { color: #9BB0AE; }

/* --- Section-specific --------------------------------------------------- */

/* A category name is one or two short words — "Clinical Reviews", "Gastro
   Living" — where the page heroes carry a sentence ("We'd Love to Hear From
   You"). The shared 520px cap was measured on those, and on two words it
   leaves the headline floating in a column twice its width, so it comes in.
   Not a font-size change: the type scale is shared across all four hero
   families deliberately. */
.vhh-hero-spotlight--cat .vhh-hero-spotlight__title {
    max-width: 460px;
}

/* The next thing on every category archive is either the sub-category nav
   strip (which brings its own 30px top margin) or the post grid (which brings
   60px of container padding). Both are enough; the hero does not add more. */
.vhh-hero-spotlight--cat + .inner-cat-nav-wrapper {
    margin-top: 0;
}

@media (max-width: 900px) {
    /* The photograph drops into the flow above the headline at this width and
       the site header sits directly above it, so the band's own top padding
       goes — same as the GI set and the two motif pages. */
    .vhh-hero-spotlight--cat {
        padding-top: 0;
    }

    /* Full width and further back: on one column the motif sits behind the
       copy rather than beside it. At this alpha the headline still measures
       over 6:1 against the lightest point of the bloom. */
    .vhh-hero-spotlight--cat .vhh-hero-spotlight__motif {
        width: 100%;
        opacity: 0.55;
    }

    /* With no photograph there is nothing above the copy to hold it off the
       header. */
    .vhh-hero-spotlight--cat.has-motif {
        padding-top: 40px;
    }

    .vhh-hero-spotlight--cat .vhh-hero-spotlight__title {
        max-width: none;
    }
}
</style>
	<?php
}

/**
 * Render the hero for one category archive.
 *
 * Emits the `<section>` only — the caller owns `<main>`, exactly as the GI and
 * policy heroes do.
 *
 * @param WP_Term|null $term Defaults to the queried object. Anything that is
 *                          not a category term renders nothing, so a caller
 *                          on a tag or post-type archive falls through to its
 *                          own hero.
 * @return bool True if a hero was rendered.
 */
function vance_render_category_hero( $term = null ) {
	if ( $term === null ) { $term = get_queried_object(); }
	if ( ! ( $term instanceof WP_Term ) || $term->taxonomy !== 'category' ) { return false; }

	$resolved = vance_category_hero_meta_for( $term );
	$meta     = $resolved['meta'];
	$parent   = $resolved['parent'];

	/* --- Copy ------------------------------------------------------------ */

	/*
	 * The headline. Two override keys are read, not one, and both are
	 * deliberate: `vance_cat_hero_title_override_<id>` is what all three dark
	 * heroes have always read, and `vance_cat_title_<id>` is what the
	 * Customizer control in functions.php actually registers. They have never
	 * matched, which is why the "Title Override" field in Appearance →
	 * Customize → Content → Category Heroes has never done anything. Reading
	 * both means neither an admin who typed into that field nor a site with a
	 * value saved under the old key loses their work here.
	 */
	$title = vance_get_theme_mod( "vance_cat_hero_title_override_{$term->term_id}", '' );
	if ( $title === '' ) {
		$title = vance_get_theme_mod( "vance_cat_title_{$term->term_id}", '' );
	}

	/*
	 * Two output paths, because the two sources are not the same kind of
	 * string. An ADMIN override goes through wp_kses_post so it can carry the
	 * <span class="highlight"> every other hero on the site allows in a
	 * headline. A TERM NAME is plain text out of the database and is decoded
	 * once, then escaped once — see vance_category_hero_term_name().
	 */
	if ( $title !== '' ) {
		$title_html = wp_kses_post( $title );
	} else {
		$title_html = esc_html( vance_category_hero_term_name( $term ) );
	}

	/*
	 * The eyebrow — INHERITED, exactly like the photograph and the card, so
	 * every page in a section wears the same label.
	 *
	 * It first said the parent's NAME on a sub-category, on the reasoning that
	 * naming the section is the useful thing to do on a page called "Food &
	 * Nutrition". Rendering it proved otherwise: the breadcrumb immediately
	 * above already says "Gastro Living", so the pill directly beneath it read
	 * "GASTRO LIVING" a second time, forty pixels lower and in capitals. Two
	 * labels, one fact.
	 *
	 * $meta is already resolved from the parent, so taking its eyebrow gives a
	 * child the family's KIND — "Living with it" on all five sub-sections of
	 * Gastro Living — which is the thing the crumb does NOT say. Falling back
	 * to the parent's name only matters for a section with no registry entry
	 * at all, where there is no duplication to cause.
	 *
	 * The long-standing per-category Tagline control still wins over both.
	 */
	$eyebrow = vance_get_theme_mod( "vance_cat_tagline_{$term->term_id}", '' );
	if ( $eyebrow === '' ) {
		if ( $meta ) {
			$eyebrow = $meta['eyebrow'];
		} elseif ( $parent ) {
			$eyebrow = vance_category_hero_term_name( $parent );
		}
	}

	/*
	 * The lede. The term's own WordPress description wins, because that field
	 * is editable from Posts → Categories by anyone with the rights, and this
	 * file is not. Only a TOP-LEVEL section falls back to the registry: a
	 * sub-category with no description of its own gets no lede rather than
	 * its parent's, which would be describing the wrong page.
	 */
	$intro = trim( wp_strip_all_tags( term_description( $term ) ) );
	if ( $intro === '' && $meta && ! $parent ) {
		$intro = $meta['intro'];
	}

	$photo = vance_category_hero_photo( $term, $parent, $meta );
	$facts = vance_category_hero_facts( $term->term_id );

	/* --- The band -------------------------------------------------------- */

	/*
	 * Cells are appended only when they have something true to say, and the
	 * shared __slot rule uses grid-auto-flow rather than a fixed three-column
	 * track precisely so a band of two does not leave a blank slab of white.
	 * An empty section produces no cells and the band is dropped entirely —
	 * "0 articles" in a white panel is worse than no panel.
	 */
	$cells = array();

	if ( $facts['total'] > 0 ) {
		$cells[] = array(
			'icon'  => 'stack',
			'key'   => _n( 'Article', 'Articles', $facts['total'], 'vance-health-hub' ),
			'value' => number_format_i18n( $facts['total'] ),
		);
	}

	if ( $facts['topics'] > 0 ) {
		$cells[] = array(
			'icon'  => 'tiles',
			'key'   => _n( 'Topic', 'Topics', $facts['topics'], 'vance-health-hub' ),
			'value' => number_format_i18n( $facts['topics'] ),
		);
	}

	if ( $facts['latest'] !== '' ) {
		$cells[] = array(
			'icon'  => 'clock',
			'key'   => __( 'Last added', 'vance-health-hub' ),
			'value' => $facts['latest'],
		);
	}

	/* --- The card -------------------------------------------------------- */

	$card = ( $meta && ! empty( $meta['card'] ) ) ? $meta['card'] : array(
		// A section the registry has never heard of — a category added after
		// this file was written. It still gets a card, because the hero's
		// two-column grid has a hole in it otherwise, and the card says the
		// one thing that is true of every section of this site.
		'icon'  => 'folder',
		'title' => __( 'Checked before it is published', 'vance-health-hub' ),
		'text'  => __( 'Everything in this section is reviewed before it goes live. None of it replaces advice from your own care team.', 'vance-health-hub' ),
	);

	$slug_class = sanitize_html_class( $term->slug );

	vance_category_hero_styles();
	?>
	<section class="vhh-hero-spotlight vhh-hero-spotlight--page vhh-hero-spotlight--cat vhh-hero-spotlight--cat-<?php
		echo esc_attr( $slug_class ); ?><?php echo $photo ? '' : ' has-motif'; ?>">

		<?php /* First in source order in both branches: on desktop it is
		         absolutely positioned so source order is irrelevant, and when
		         the layout stacks it lands above the headline with no `order`
		         and no second copy of the markup. */ ?>
		<?php if ( $photo ) : ?>
		<div class="vhh-hero-spotlight__media">
			<?php /* Above the fold and this page's LCP candidate — eager, high
			         priority, and with intrinsic dimensions so it reserves its
			         box and cannot shift the headline as it decodes. The
			         1400x876 is the shape every file in assets/img/heroes/ is
			         cut to; see tests/process-heroes.py. */ ?>
			<img src="<?php echo esc_url( $photo['src'] ); ?>"
			     alt="<?php echo esc_attr( $photo['alt'] ); ?>"
			     width="1400" height="876"
			     style="object-position: <?php echo esc_attr( $photo['focal'] ); ?>;"
			     decoding="async" fetchpriority="high">
		</div>
		<?php else : ?>
		<div class="vhh-hero-spotlight__motif" aria-hidden="true"><?php
			echo vance_category_hero_motif(); // phpcs:ignore WordPress.Security.EscapeOutput — static markup
		?></div>
		<?php endif; ?>

		<div class="container vhh-hero-spotlight__inner">
			<div class="vhh-hero-spotlight__copy">

				<?php if ( $parent ) : ?>
				<?php /* A real <nav> with a name, because this IS the way back up
				         and a screen reader should be able to find it as one.
				         Only sub-categories get it: on a top-level section the
				         crumb would read "Home / Clinical Reviews", which is the
				         site header's job. */ ?>
				<nav class="vhh-hero-spotlight__crumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'vance-health-hub' ); ?>">
					<a href="<?php echo esc_url( get_category_link( $parent->term_id ) ); ?>"><?php
						echo esc_html( vance_category_hero_term_name( $parent ) ); ?></a>
					<span class="vhh-hero-spotlight__crumb-sep" aria-hidden="true">/</span>
					<span aria-current="page"><?php
						echo esc_html( vance_category_hero_term_name( $term ) ); ?></span>
				</nav>
				<?php endif; ?>

				<?php if ( $eyebrow !== '' ) : ?>
				<span class="vhh-hero-spotlight__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
				<?php endif; ?>

				<h1 class="vhh-hero-spotlight__title"><?php
					echo $title_html; // phpcs:ignore WordPress.Security.EscapeOutput — escaped above, by whichever branch produced it
				?></h1>

				<?php if ( $intro !== '' ) : ?>
				<p class="vhh-hero-spotlight__intro"><?php echo esc_html( $intro ); ?></p>
				<?php endif; ?>

				<?php if ( $cells ) : ?>
				<div class="vhh-hero-spotlight__slot-wrap">
					<span class="vhh-hero-spotlight__slot-label"><?php
						esc_html_e( 'What is in this section', 'vance-health-hub' );
					?></span>

					<?php /* <div>s, not <a>s: nothing in this band navigates. The
					         shared rules scope every link affordance — the hover
					         fill, the focus ring, the arrow — to
					         `a.vhh-hero-spotlight__line`, so plain cells inherit
					         the tile, the type and the dividers and none of the
					         behaviour. */ ?>
					<div class="vhh-hero-spotlight__slot vhh-hero-spotlight__slot--lines vhh-hero-spotlight__slot--facts">
						<?php foreach ( $cells as $cell ) : ?>
						<div class="vhh-hero-spotlight__line">
							<span class="vhh-hero-spotlight__line-ico"><?php
								echo vance_category_hero_icon( $cell['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup
							?></span>
							<span class="vhh-hero-spotlight__line-body">
								<span class="vhh-hero-spotlight__line-k"><?php echo esc_html( $cell['key'] ); ?></span>
								<span class="vhh-hero-spotlight__line-v"><?php echo esc_html( $cell['value'] ); ?></span>
							</span>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<aside class="vhh-hero-spotlight__card">
				<span class="vhh-hero-spotlight__card-icon" aria-hidden="true"><?php
					echo vance_category_hero_icon( $card['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup
				?></span>
				<div class="vhh-hero-spotlight__card-body">
					<h2 class="vhh-hero-spotlight__card-title"><?php echo esc_html( $card['title'] ); ?></h2>
					<p class="vhh-hero-spotlight__card-text"><?php echo esc_html( $card['text'] ); ?></p>
				</div>
			</aside>
		</div>
	</section>
	<?php
	return true;
}
