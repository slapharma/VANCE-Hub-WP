<?php
/**
 * Policy-page heroes — the spotlight layout, document variant.
 *
 * The five policy documents — Privacy Policy, Cookie Policy, Terms of Use,
 * Medical Disclaimer and the Accessibility Statement — all carried the same
 * dark `legal-hero`: a navy/teal veil over `assets/img/news_hero.png`, with
 * the copy declared inline in each of the four bespoke templates and the
 * fifth (the Cookie Policy) falling through to `page.php`'s generic `.hero`.
 * They now carry this instead.
 *
 * WHAT IS DIFFERENT ABOUT THIS ONE
 *
 * Every other spotlight hero on the site dissolves a photograph of people
 * into the right-hand side of the band. A policy document has no such
 * photograph to take — there is nothing to picture, and putting a stock
 * clinician beside a limitation-of-liability clause is worse than putting
 * nothing there. So the media slot holds a geometric motif instead: a soft
 * bloom, four concentric arcs and a dot field, drawn from the same teal as
 * the rest of the band. It says nothing, on purpose, and it says the same
 * nothing on all five pages.
 *
 * What distinguishes one document from another is therefore carried entirely
 * by the words and by the card's icon — a shield, a cookie, a contract, a
 * warning triangle, the universal access symbol.
 *
 * WHAT FILLS THE BAND
 *
 * The rule the other spotlight heroes follow (inc/page-hero-spotlight.php,
 * §3.4 of the hero-rollout handover) is to fill the utility band with the
 * thing the page does NOT already tell its visitor. On a policy page that is
 * unambiguous: somebody who has landed on the Cookie Policy from a consent
 * banner has no way of knowing that four sibling documents exist, and the
 * footer link that would tell them is a full document's scroll away. So the
 * band lists the other four, always four cells, laid out two-by-two.
 *
 * NO TOGGLE, BY REQUEST
 *
 * Unlike Contact, About and the tool pages, there is no
 * `vance_{page}_hero_style` here and no Customizer section: this hero
 * REPLACES the dark one rather than sitting beside it. That was the client's
 * explicit instruction on 2026-08-28. The consequence to be aware of is that
 * the copy below is the only copy — an admin cannot edit these headlines from
 * the Customizer, because the classic heroes they came from never had
 * settings either (all four templates hard-coded their own `<h1>`). Nothing
 * has become less editable than it was; it has simply moved file.
 *
 * WHY THE CSS IS INLINE RATHER THAN IN main.css
 *
 * Two reasons, in order of weight. First, `assets/css/main.css` was being
 * edited by a parallel session on the same working tree when this shipped,
 * and committing it would have swept their in-progress work live under this
 * commit's message — the exact accident §6.1 of the handover documents.
 * Second, it is what the four legal templates already did: each carried its
 * own `<style>` block, and this consolidates four of them into one.
 *
 * Only ONE page in the set renders at a time, so the block is printed once
 * per request and is small. Everything structural — the band, the type scale,
 * the eyebrow chip, the cell treatment, the card, the whole responsive ladder
 * and the doubled-class `!important` rules that opt the hero out of the
 * global mobile type normalisation — is inherited from the committed
 * `.vhh-hero-spotlight` block in main.css and is NOT repeated here.
 *
 * If a later session promotes this into main.css, take the whole
 * `vance_legal_hero_styles()` string; it is self-contained and depends on
 * nothing above it.
 *
 * @package vance-health-hub
 * @since   2026-08-28
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * The five documents, in the order they appear in each other's bands.
 *
 * `title` and `intro` are the copy each template rendered before this file
 * existed, carried across verbatim so that switching hero design could not
 * silently reword a legal document — with two deliberate exceptions, both
 * noted at their entries.
 *
 * `slug` / `path`: the link is resolved by slug so it follows the page if it
 * is ever moved, with the literal path as a last resort so a cell is never
 * href-less. Same mechanism as vance_page_hero_spotlight_page_url().
 *
 * @return array<string, array<string, string>>
 */
function vance_legal_hero_docs() {
	return array(
		'privacy' => array(
			'eyebrow' => __( 'Privacy', 'vance-health-hub' ),
			'title'   => __( 'Privacy Policy', 'vance-health-hub' ),
			'short'   => __( 'Privacy Policy', 'vance-health-hub' ),
			'kicker'  => __( 'Your data', 'vance-health-hub' ),
			// Reworded by the client on 2026-08-28, tightening the two clauses.
			// The dark hero's wording was 'your right to privacy ... safeguard
			// your information'; tests/legal-hero.test.php asserts the old text
			// stays gone, so nobody restores it as a 'fix'.
			'intro'   => __( 'We are committed to protecting your personal data and privacy. This policy explains how we collect, use, and protect your data.', 'vance-health-hub' ),
			'icon'    => 'shield',
			'slug'    => 'privacy-policy',
			'path'    => '/privacy-policy/',
		),
		'cookies' => array(
			'eyebrow' => __( 'Cookies', 'vance-health-hub' ),
			// The one document with no hero copy of its own to inherit: it is
			// Complianz-generated and falls through to page.php, whose generic
			// hero prints the page title and nothing else. page.php passes
			// get_the_title() as an override so the live title still wins;
			// this literal is what the tests and any un-titled render use.
			'title'   => __( 'Cookie Policy (UK)', 'vance-health-hub' ),
			'short'   => __( 'Cookie Policy', 'vance-health-hub' ),
			'kicker'  => __( 'Your choices', 'vance-health-hub' ),
			// Written for this hero, because there was no intro to carry
			// across — the generic hero has never had one.
			'intro'   => __( 'What cookies and similar technologies this site sets, what each one is for, and how to change your choices at any time.', 'vance-health-hub' ),
			'icon'    => 'cookie',
			'slug'    => 'cookie-policy-uk',
			'path'    => '/cookie-policy-uk/',
		),
		'terms' => array(
			'eyebrow' => __( 'Legal', 'vance-health-hub' ),
			'title'   => __( 'Terms of Use', 'vance-health-hub' ),
			'short'   => __( 'Terms of Use', 'vance-health-hub' ),
			'kicker'  => __( 'The agreement', 'vance-health-hub' ),
			// Reworded in one place only: the classic hero said "the Gastro
			// Health Hub platform", a pre-rebrand name that survives nowhere
			// else on the page and reads as a different company's terms.
			// CLAUDE.md §4 forbids a bare SLA/Gastro sweep for exactly this
			// kind of collateral damage, so it is corrected by hand, here.
			'intro'   => __( 'Please read these terms carefully before using Vance Medical Hub. By accessing the service, you agree to be bound by them.', 'vance-health-hub' ),
			'icon'    => 'contract',
			'slug'    => 'terms-of-use',
			'path'    => '/terms-of-use/',
		),
		'disclaimer' => array(
			'eyebrow' => __( 'Important', 'vance-health-hub' ),
			'title'   => __( 'Medical Disclaimer', 'vance-health-hub' ),
			'short'   => __( 'Medical Disclaimer', 'vance-health-hub' ),
			'kicker'  => __( 'Before you rely on this', 'vance-health-hub' ),
			'intro'   => __( 'Please read this before using Vance Medical Hub, its articles, tools or VANCE-Ai.', 'vance-health-hub' ),
			'icon'    => 'alert',
			'slug'    => 'medical-disclaimer',
			'path'    => '/medical-disclaimer/',
		),
		'accessibility' => array(
			'eyebrow' => __( 'Accessibility', 'vance-health-hub' ),
			'title'   => __( 'Accessibility Statement', 'vance-health-hub' ),
			'short'   => __( 'Accessibility Statement', 'vance-health-hub' ),
			'kicker'  => __( 'Using this site', 'vance-health-hub' ),
			// Rewritten by the client on 2026-08-28. The dark hero's line was
			// "We want everyone to be able to use Vance Medical Hub.", which sat
			// under a 52px headline and read as a caption; an intermediate
			// version ran to four lines. This is the settled wording.
			'intro'   => __( 'This statement sets out the standards we hold, and how to tell us when something does not work for you.', 'vance-health-hub' ),
			'icon'    => 'access',
			'slug'    => 'accessibility',
			'path'    => '/accessibility/',
		),
	);
}

/**
 * Which document, if any, a page slug belongs to.
 *
 * page.php uses this. Four of the five documents have templates of their own
 * and name their document outright; the Cookie Policy is Complianz-generated
 * content on the default template, so the generic page renderer has to
 * recognise it by slug. Driven from the registry rather than a second list, so
 * a document cannot be renamed in one place and missed in the other — and so
 * that if any of the other four ever loses its template it still gets the
 * right hero instead of the generic one.
 *
 * @param string $slug A page's post_name.
 * @return string A key of vance_legal_hero_docs(), or '' for anything else.
 */
function vance_legal_hero_doc_for_slug( $slug ) {
	if ( ! $slug ) { return ''; }

	foreach ( vance_legal_hero_docs() as $key => $d ) {
		if ( $d['slug'] === $slug ) {
			return $key;
		}
	}

	return '';
}

/**
 * A document's permalink, resolved by slug with the literal path as a
 * last resort so a band cell is never href-less.
 *
 * @param string $slug Page slug.
 * @param string $path Path to use when no page has that slug.
 * @return string
 */
function vance_legal_hero_url( $slug, $path ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( $path );
}

/**
 * The other four documents, for one document's utility band.
 *
 * A page is never sold back to itself, so this always returns exactly four
 * cells — which is why the band's CSS can lay them out two-by-two without
 * having to cope with an odd count.
 *
 * @param string $doc The document doing the rendering.
 * @return array<int, array{icon: string, label: string, value: string, href: string}>
 */
function vance_legal_hero_siblings( $doc ) {
	$cells = array();

	foreach ( vance_legal_hero_docs() as $key => $d ) {
		if ( $key === $doc ) {
			continue;
		}
		$cells[] = array(
			'icon'  => $d['icon'],
			'label' => $d['kicker'],
			'value' => $d['short'],
			'href'  => vance_legal_hero_url( $d['slug'], $d['path'] ),
		);
	}

	return $cells;
}

/**
 * One inline icon.
 *
 * Same 24-unit box and 1.9 stroke weight as the icons in
 * inc/page-hero-spotlight.php, so the two hero families read as one set. Kept
 * separate rather than calling that file's vance_page_hero_spotlight_icon()
 * because nothing else here depends on it, and a policy page should not stop
 * rendering because a tool hero was refactored.
 *
 * @param string $name shield|cookie|contract|alert|access|mail
 * @return string SVG markup. Static — no dynamic values, nothing to escape.
 */
function vance_legal_hero_icon( $name ) {
	$paths = array(
		'shield'   => '<path d="M12 2.9l7.3 2.8v5.6c0 4.4-3 8.3-7.3 9.8-4.3-1.5-7.3-5.4-7.3-9.8V5.7z"/><path d="M9.1 12.1l2 2 3.8-4"/>',
		// The bite out of the top-right is drawn as part of the outline rather
		// than as a second shape, so the whole biscuit is one stroked path.
		'cookie'   => '<path d="M12.6 3.05a9 9 0 1 0 8.35 8.35 3.3 3.3 0 0 1-4.2-1.15 3.3 3.3 0 0 1-4.15-7.2z"/><path d="M9.1 9.4h.02"/><path d="M8.4 14.6h.02"/><path d="M13.2 15.1h.02"/><path d="M12.4 11.6h.02"/>',
		'contract' => '<path d="M14.1 3H7.4A2.4 2.4 0 0 0 5 5.4v13.2A2.4 2.4 0 0 0 7.4 21h9.2a2.4 2.4 0 0 0 2.4-2.4V8.1z"/><path d="M14.1 3v5.1h5"/><path d="M8.6 12.9h6.8"/><path d="M8.6 16.5h4.4"/>',
		'alert'    => '<path d="M10.3 4.1a2 2 0 0 1 3.4 0l7.1 12.4a2 2 0 0 1-1.7 3H4.9a2 2 0 0 1-1.7-3z"/><path d="M12 9.5v4"/><path d="M12 16.6h.02"/>',
		// The universal access symbol: a pictogram, not a photograph, and the
		// one place on these five pages where a figure is the correct mark.
		'access'   => '<circle cx="12" cy="12" r="9"/><path d="M12 8.4v4.1"/><path d="M7.4 9.3c3 .9 6.2.9 9.2 0"/><path d="M12 12.5l-2.3 5"/><path d="M12 12.5l2.3 5"/><circle cx="12" cy="6.6" r="1.05" fill="currentColor" stroke="none"/>',
		'mail'     => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M3 7l9 6 9-6"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) { return ''; }

	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"'
		. ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
		. $paths[ $name ] . '</svg>';
}

/**
 * The geometric motif that stands in for the photograph.
 *
 * Deliberately abstract. Four concentric arcs centred beyond the top-right
 * corner, a radial bloom behind them and a dot field below, all in the band's
 * own teal at low alpha. It carries no meaning and needs none: the eyebrow,
 * the headline and the card icon already say which document this is, and a
 * motif that tried to illustrate "terms of use" would be a gavel.
 *
 * `preserveAspectRatio="xMaxYMid slice"` pins it to the right edge, so the
 * arcs keep their centre off-canvas at every width instead of drifting into
 * the middle of the band as the box gets shorter.
 *
 * Gradient ids are prefixed because a page may carry other inline SVG; an id
 * collision would repaint whichever one lost.
 *
 * @return string SVG markup. Static.
 */
function vance_legal_hero_motif() {
	// A 7x4 dot field, built rather than typed out so the spacing is one
	// number instead of twenty-eight coordinates to keep in step.
	$dots = '';
	for ( $row = 0; $row < 4; $row++ ) {
		for ( $col = 0; $col < 7; $col++ ) {
			$dots .= sprintf(
				'<circle cx="%d" cy="%d" r="2.1"/>',
				392 + ( $col * 30 ),
				322 + ( $row * 30 )
			);
		}
	}

	return '<svg viewBox="0 0 640 520" preserveAspectRatio="xMaxYMid slice" aria-hidden="true" focusable="false">'
		. '<defs>'
		. '<radialGradient id="vhhLegalBloom" cx="70%" cy="26%" r="64%">'
		. '<stop offset="0%" stop-color="#AFD6D4" stop-opacity="0.60"/>'
		. '<stop offset="52%" stop-color="#CBE4E2" stop-opacity="0.26"/>'
		. '<stop offset="100%" stop-color="#CBE4E2" stop-opacity="0"/>'
		. '</radialGradient>'
		. '<linearGradient id="vhhLegalArc" x1="0" y1="1" x2="1" y2="0">'
		. '<stop offset="0%" stop-color="#04504E" stop-opacity="0.03"/>'
		. '<stop offset="48%" stop-color="#04504E" stop-opacity="0.30"/>'
		. '<stop offset="100%" stop-color="#04504E" stop-opacity="0.07"/>'
		. '</linearGradient>'
		. '</defs>'
		. '<rect width="640" height="520" fill="url(#vhhLegalBloom)"/>'
		. '<g fill="none" stroke="url(#vhhLegalArc)">'
		. '<circle cx="486" cy="150" r="118" stroke-width="1.5"/>'
		. '<circle cx="486" cy="150" r="188" stroke-width="1.2"/>'
		. '<circle cx="486" cy="150" r="262" stroke-width="1"/>'
		. '<circle cx="486" cy="150" r="342" stroke-width="0.9"/>'
		. '</g>'
		. '<g fill="#04504E" opacity="0.13" stroke="none">' . $dots . '</g>'
		. '</svg>';
}

/**
 * The block of CSS this hero adds on top of the committed `.vhh-hero-spotlight`
 * rules in assets/css/main.css.
 *
 * Printed once per request — only one of the five documents ever renders — and
 * guarded by a static so a template that includes the hero twice cannot emit
 * it twice.
 *
 * @return void
 */
function vance_legal_hero_styles() {
	static $done = false;
	if ( $done ) { return; }
	$done = true;
	?>
<style id="vhh-legal-hero-css">
/* Policy-document hero — inc/legal-hero.php.
   Everything not listed here is inherited from the .vhh-hero-spotlight block
   in assets/css/main.css. Do not restate it. */

/* --- The motif that stands in for the photograph ------------------------- */

.vhh-hero-spotlight__motif {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    width: 54%;
    /* Same stacking treatment as __media: behind the copy, inert to the
       pointer, and inside the section's `isolation: isolate` so the negative
       index cannot escape behind the page background. */
    z-index: -1;
    pointer-events: none;
}

.vhh-hero-spotlight__motif svg {
    display: block;
    width: 100%;
    height: 100%;
}

/* --- The band of sibling documents --------------------------------------- */

/* The cells are the `lines` markup unchanged — icon tile, caption, value — so
   the tile, the type, the dividers and the mobile stack all come from main.css.
   Two things are added, and the chevron is written out rather than borrowed
   from __slot--tools: that modifier belongs to the free-tools band, which is
   still being extended, and a legal page should not change shape because a
   tool page did. */

.vhh-hero-spotlight__slot--docs .vhh-hero-spotlight__line-v {
    /* The lines band sets `overflow-wrap: anywhere` so one unbreakable email
       address cannot widen the grid. Document names are ordinary words and
       that rule would break them mid-word. */
    overflow-wrap: normal;
}

/* Every cell here is a link, so the affordance has to read as one before
   anybody hovers. Two borders rather than a glyph: an empty pseudo-element is
   not announced to a screen reader, and the cell's own words already say where
   it goes. */
.vhh-hero-spotlight__slot--docs .vhh-hero-spotlight__line::after {
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

.vhh-hero-spotlight__slot--docs a.vhh-hero-spotlight__line:hover::after,
.vhh-hero-spotlight__slot--docs a.vhh-hero-spotlight__line:focus-visible::after {
    opacity: 1;
    transform: rotate(45deg) translate(2px, -2px);
}

@media (prefers-reduced-motion: reduce) {
    .vhh-hero-spotlight__slot--docs .vhh-hero-spotlight__line::after {
        transition: none;
    }
}

/* Four cells, always — a document is never listed in its own band. Four equal
   columns inside the copy column measure under 170px each, which puts
   "Accessibility Statement" on three lines, so this band goes two-by-two, the
   same answer the four evidence pillars reached.

   Scoped to min-width 901px deliberately. The shared rule in main.css collapses
   every band to a single stack at max-width 900px; an unscoped
   grid-template-columns here sits in a later stylesheet and would win at every
   width, so the band would never stack on a phone. */
@media (min-width: 901px) {
    .vhh-hero-spotlight__slot--docs {
        grid-auto-flow: row;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    /* The shared rule rules off the right of every cell but the last, which is
       right for one row and wrong for a 2x2 — it leaves a hairline floating
       down the right of cell 3. Draw the grid's interior lines instead. */
    .vhh-hero-spotlight__slot--docs .vhh-hero-spotlight__line {
        border-right: 0;
        border-bottom: 1px solid #DCE9E8;
    }

    .vhh-hero-spotlight__slot--docs .vhh-hero-spotlight__line:nth-child(odd) {
        border-right: 1px solid #DCE9E8;
    }

    .vhh-hero-spotlight__slot--docs .vhh-hero-spotlight__line:nth-last-child(-n + 2) {
        border-bottom: 0;
    }
}

/* --- Headline ------------------------------------------------------------ */

/* main.css caps the headline at 520px, which holds the homepage's three-line
   headline off the search field below it. Every headline here is a document
   title that belongs on ONE line, and the longest -- "Accessibility Statement"
   -- measures 579px at the type scale's 56px ceiling. At 520px it therefore fit
   on a 1280px screen with 4px to spare and wrapped on anything wider, which is
   the worst possible place for a cap to sit.

   640px clears the longest title at the ceiling with room, and stays inside the
   690px copy column, so it cannot reach the motif. It deliberately also
   overrides the 420px cap main.css applies below 1100px: that cap exists to
   hold a three-line headline in shape, and these are one line. */
.vhh-hero-spotlight--legal .vhh-hero-spotlight__title {
    max-width: 640px;
}

/* --- The card's one addition --------------------------------------------- */

/* The shared card escapes its text; this one carries a mailto: link, so the
   anchor is emitted by this file and only needs colouring. */
.vhh-hero-spotlight__card-text a {
    color: var(--vhh-hs-title, #04504E);
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 2px;
    overflow-wrap: anywhere;
}

.vhh-hero-spotlight__card-text a:hover,
.vhh-hero-spotlight__card-text a:focus-visible {
    color: var(--primary-color, #008080);
}

/* --- The document body ---------------------------------------------------- */

/* `.legal-wrap` is the 760px measure the policy text is set in. It used to be
   declared four times over -- once inline in each of the four bespoke
   templates, byte-identical in all four -- and not at all for the Cookie
   Policy, which is Complianz-generated content on page.php and so ran at the
   1200px container width while its four siblings ran at 760px.

   The nine rules that were identical in all four live here now. Each template
   keeps only its OWN extras (.legal-contact-box, .legal-toc,
   .legal-emergency-box, .legal-disclaimer-box, .legal-table, .legal-rights-grid),
   because those genuinely differ per page.

   ORDER IS LOAD-BEARING, so the four templates call vance_legal_hero_styles()
   ABOVE their own <style> block rather than relying on the hero render further
   down to print it. Four of their extras collide with `.legal-wrap p` at
   EQUAL specificity (0,1,1) -- `.legal-contact-box p`, `.legal-emergency-box p`,
   `.legal-disclaimer-box p` -- so the winner is decided by source order alone.
   They won before this consolidation and they must keep winning. */

.legal-wrap {
    max-width: 760px;
    margin: 0 auto;
    padding: 64px 24px 100px;
}

.legal-wrap h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 22px;
    font-weight: 800;
    color: var(--secondary-color);
    margin: 48px 0 12px;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba(0,128,128,0.15);
}

.legal-wrap h2:first-of-type { margin-top: 32px; }

.legal-wrap p {
    color: #4a5568;
    line-height: 1.85;
    font-size: 15.5px;
    margin: 0 0 16px;
}

.legal-wrap ul {
    color: #4a5568;
    line-height: 1.85;
    font-size: 15.5px;
    margin: 0 0 16px;
    padding-left: 24px;
}

.legal-wrap ul li { margin-bottom: 8px; }

.legal-wrap a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}

.legal-wrap a:hover { text-decoration: underline; }

.legal-updated {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(0,128,128,0.08);
    color: var(--primary-color);
    font-size: 13px;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: var(--radius-surface, 14px);
    border: 1px solid rgba(0,128,128,0.2);
    margin-bottom: 32px;
    letter-spacing: 0.3px;
}

/* The Cookie Policy's tables, which no other document in the set has.

   Complianz lays each cookie out as a three-column grid whose tracks are sized
   from their content. Google Site Kit registers cookies named
   `googlesitekit_1.182.0_657cf9db12b75b3f20dea04eaef74818_modules::analytics-4`
   -- one unbreakable 70-character token -- which forced the first track to
   565px and pushed the grid 41px past a 760px measure. It fitted at the old
   1200px width, so narrowing the page is what exposed it.

   `overflow-wrap: anywhere` rather than `break-word` on purpose: only
   `anywhere` reduces an element's intrinsic min-content width, which is the
   thing the grid track is sized from. `break-word` would let the text wrap and
   leave the track just as wide. Measured on the live document: 41px over to
   nothing over, with no horizontal scroll anywhere on the page.

   Scoped under .legal-wrap and written against Complianz's markup, so if the
   plugin renames these classes the rule simply stops matching and the document
   is no worse off than it was. */
.legal-wrap .cookies-per-purpose > * {
    overflow-wrap: anywhere;
    min-width: 0;
}

/* Complianz also sets its own type scale, and it sets it on an ID:
   `#cmplz-document p, #cmplz-document li, #cmplz-document td` at 14px. That is
   specificity (1,0,1) against `.legal-wrap p`'s (0,1,1), so the plugin won and
   the Cookie Policy was set a point and a half smaller than the four documents
   beside it -- the same mismatch as the width, one level down.

   Matched at (1,1,1) rather than reached for with !important, and the values
   are deliberately the same three `.legal-wrap p` sets a few rules above;
   tests/legal-hero.test.php asserts the two stay in step, because a font-size
   changed in one place and not the other is invisible until you put the five
   documents side by side. */
.legal-wrap #cmplz-document p,
.legal-wrap #cmplz-document li,
.legal-wrap #cmplz-document td {
    font-size: 15.5px;
    line-height: 1.85;
    color: #4a5568;
}

/* --- Spacing ------------------------------------------------------------- */

/* The four bespoke templates open their body with .legal-wrap, which carries
   64px of its own top padding. Stacked on the hero's bottom padding that is
   ~128px of empty band between the intro and the first heading. */
.vhh-hero-spotlight--legal + .legal-wrap {
    padding-top: 44px;
}

@media (max-width: 900px) {
    /* main.css zeroes the hero's TOP padding at this width, because that is
       where the photograph drops back into flow and provides the spacing
       itself. This hero has no photograph to drop, so without this it would
       jam against the site header. */
    .vhh-hero-spotlight--legal {
        padding: 40px 0 44px;
    }

    .vhh-hero-spotlight--legal .vhh-hero-spotlight__motif {
        /* Full width and further back: on one column the motif sits behind the
           copy rather than beside it. At this alpha the headline still measures
           over 6:1 against the lightest point of the bloom. */
        width: 100%;
        opacity: 0.55;
    }
}
</style>
	<?php
}

/**
 * Render the hero for one policy document.
 *
 * Emits the homepage hero's own section class, so the band, the type scale,
 * the eyebrow chip, the utility band's white card treatment, the floating card
 * and the whole responsive ladder are inherited from one stylesheet block —
 * including the doubled-class `!important` rules that opt the hero out of the
 * global mobile type normalisation in mobile-base.css.
 *
 * The motif is first in source order for the same reason the photograph is in
 * the other spotlight heroes: on desktop it is absolutely positioned so source
 * order is irrelevant, and nothing about it needs to move when the layout
 * stacks.
 *
 * @param string               $doc       A key of vance_legal_hero_docs().
 * @param array<string,string> $overrides Optional. 'title', 'eyebrow' or
 *                                        'intro', for a page whose live title
 *                                        should win over the literal here —
 *                                        page.php passes get_the_title().
 * @return void
 */
function vance_render_legal_hero( $doc, $overrides = array() ) {
	$docs = vance_legal_hero_docs();
	if ( ! isset( $docs[ $doc ] ) ) { return; }

	$d = $docs[ $doc ];

	foreach ( array( 'eyebrow', 'title', 'intro' ) as $field ) {
		if ( isset( $overrides[ $field ] ) && $overrides[ $field ] !== '' ) {
			$d[ $field ] = $overrides[ $field ];
		}
	}

	// The address the rest of the theme already uses, read from the key
	// page-contact-us.php reads, with that page's own fallback copied verbatim.
	// Passing '' would render a card offering an empty mailto: on any site that
	// has never opened Contact Information — get_theme_mod() answers an unsaved
	// read with whatever default the CALLER passes.
	$email = vance_get_theme_mod( 'vance_contact_email', 'team@vancemedicalfoods.co.uk' );

	$siblings = vance_legal_hero_siblings( $doc );

	vance_legal_hero_styles();
	?>
	<section class="vhh-hero-spotlight vhh-hero-spotlight--page vhh-hero-spotlight--legal vhh-hero-spotlight--<?php echo esc_attr( $doc ); ?>">

		<div class="vhh-hero-spotlight__motif" aria-hidden="true"><?php
			echo vance_legal_hero_motif(); // phpcs:ignore WordPress.Security.EscapeOutput — static markup
		?></div>

		<div class="container vhh-hero-spotlight__inner">
			<div class="vhh-hero-spotlight__copy">

				<span class="vhh-hero-spotlight__eyebrow"><?php echo esc_html( $d['eyebrow'] ); ?></span>

				<h1 class="vhh-hero-spotlight__title"><?php echo esc_html( $d['title'] ); ?></h1>

				<?php if ( $d['intro'] !== '' ) : ?>
				<p class="vhh-hero-spotlight__intro"><?php echo esc_html( $d['intro'] ); ?></p>
				<?php endif; ?>

				<?php if ( $siblings ) : ?>
				<div class="vhh-hero-spotlight__slot-wrap">
					<span class="vhh-hero-spotlight__slot-label"><?php
						esc_html_e( 'The other documents in this set', 'vance-health-hub' );
					?></span>

					<div class="vhh-hero-spotlight__slot vhh-hero-spotlight__slot--lines vhh-hero-spotlight__slot--docs">
						<?php foreach ( $siblings as $cell ) : ?>
						<a class="vhh-hero-spotlight__line" href="<?php echo esc_url( $cell['href'] ); ?>">
							<span class="vhh-hero-spotlight__line-ico"><?php
								echo vance_legal_hero_icon( $cell['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup
							?></span>
							<span class="vhh-hero-spotlight__line-body">
								<span class="vhh-hero-spotlight__line-k"><?php echo esc_html( $cell['label'] ); ?></span>
								<span class="vhh-hero-spotlight__line-v"><?php echo esc_html( $cell['value'] ); ?></span>
							</span>
						</a>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<aside class="vhh-hero-spotlight__card">
				<span class="vhh-hero-spotlight__card-icon" aria-hidden="true"><?php
					echo vance_legal_hero_icon( $d['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput — static markup
				?></span>
				<div class="vhh-hero-spotlight__card-body">
					<h2 class="vhh-hero-spotlight__card-title"><?php
						esc_html_e( 'Questions about this document?', 'vance-health-hub' );
					?></h2>
					<?php if ( $email !== '' ) : ?>
					<p class="vhh-hero-spotlight__card-text"><?php
						printf(
							/* translators: %s: the team's email address, already wrapped in a mailto: link. */
							esc_html__( 'Write to the Vance Medical team at %s and a person will come back to you.', 'vance-health-hub' ),
							'<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput — both halves escaped here
						);
					?></p>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</section>
	<?php
}
