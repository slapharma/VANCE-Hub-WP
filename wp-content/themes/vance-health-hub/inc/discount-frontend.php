<?php
/**
 * Discount card renderer + apply-action resolver.
 *
 * `vance_render_discount_card()` is the one place a scheme becomes markup —
 * the directory grid and the single template both call it (plan §3/§5), so a
 * design change is made once. `vance_render_featured_discount()` (the promo/
 * sidebar variant, plan §8) is NOT here yet — it's a different shape (§7 of
 * docs/DISCOUNTS_UI_SPEC.md is explicit that it is not this card at a
 * different size) and has no caller until plan §10 step 4.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tier badge markup — icon + text, colour never alone (UI spec §4, WCAG
 * color-only rule). Icons are small inline SVGs, same stroke convention as
 * inc/page-hero-spotlight.php's icon set but declared separately: this file
 * has no reason to depend on that one, and the two are never near each other
 * on a page.
 *
 * @param int $tier 1, 2 or 3.
 * @return string HTML, already escaped (no dynamic values).
 */
function vance_discount_tier_badge( $tier ) {
	$map = array(
		1 => array(
			'label' => __( 'Apply on the hub', 'vance-health-hub' ),
			'class' => 'is-tier-1',
			'icon'  => '<path d="M4 12.5l5 5L20 6.5"/>',
		),
		2 => array(
			'label' => __( 'Opens provider site', 'vance-health-hub' ),
			'class' => 'is-tier-2',
			'icon'  => '<path d="M14 4h6v6"/><path d="M20 4L10 14"/><path d="M18 14v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5"/>',
		),
		3 => array(
			'label' => __( 'Phone / post / in person', 'vance-health-hub' ),
			'class' => 'is-tier-3',
			'icon'  => '<path d="M6.5 3h3l1.5 4.5-2 1.5a13 13 0 0 0 6 6l1.5-2L21 14.5v3a2.5 2.5 0 0 1-2.7 2.5A16.5 16.5 0 0 1 3.5 5.7 2.5 2.5 0 0 1 6 3z"/>',
		),
	);

	$tier = isset( $map[ $tier ] ) ? $tier : 3;
	$m    = $map[ $tier ];

	return sprintf(
		'<span class="vance-discount-tier-badge %1$s"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg><span>%3$s</span></span>',
		esc_attr( $m['class'] ),
		$m['icon'],
		esc_html( $m['label'] )
	);
}

/**
 * A scheme's tier, downgraded from 1 to 2 when frameable is false — the
 * fallback plan §10 step 6 asks for. A cross-origin iframe's own
 * X-Frame-Options/frame-ancestors block is not observable from the parent
 * page at all (same-origin policy hides it, and X-Frame-Options fires no JS
 * event whatsoever), so there is no reliable *client-side* runtime detection
 * to fall back on — `frameable` is kept fresh by the periodic
 * `wp vance discounts check` re-probe (inc/discount-check.php) instead, and a
 * tier-1 scheme whose provider adds a framing header degrades to the working
 * tier-2 popup automatically on the next check run, no code change needed.
 *
 * Both the tier badge and the Apply button read this rather than the raw
 * `tier` meta, so the two can never show a scheme as "Apply on the hub" while
 * behaving like a popup underneath — confirmed live 2026-09-04: before this
 * helper existed, flipping one scheme's frameable to false correctly changed
 * its button but left its badge still reading "Apply on the hub".
 *
 * @param array $row One row from vance_discount_directory_data().
 * @return int 1, 2 or 3.
 */
function vance_discount_effective_tier( $row ) {
	$tier = (int) $row['tier'];
	return ( 1 === $tier && empty( $row['frameable'] ) ) ? 2 : $tier;
}

/**
 * Resolve one scheme's Apply button: label, an optional note rendered OUTSIDE
 * the button (below it, not inside), href, and any data-* the click
 * behaviour needs. The button text is always the bare verb — "Apply" for
 * every actionable channel, "Learn more" / "Details coming soon" for the two
 * non-actionable fallbacks — with the channel-specific detail ("Opens Student
 * Finance England (equivalent bodies in Wales/Scotland/NI)", "Call 0800 917
 * 2222") moved to `note`. Baking that detail INTO the button label is what
 * produced unreadable, overflowing buttons on the live directory (found
 * 2026-09-04) — some provider names alone run past 60 characters.
 *
 * Copies plan §7's per-tier table; the tier-3 notes are the general case per
 * apply_type rather than the bespoke per-scheme copy ("Call 0800 917 2222",
 * the WaterSure supplier lookup) plan §7 describes — those are plan §10 step
 * 7 work and layer on top of this without changing the shape returned here.
 *
 * @param array $row One row from vance_discount_directory_data().
 * @return array{label:string,note:string,href:string,attrs:string}
 */
function vance_discount_apply_action( $row ) {
	$tier     = vance_discount_effective_tier( $row );
	$apply    = $row['apply_url'];
	$fallback = $row['official_url'];

	if ( 1 === $tier && $apply ) {
		return array(
			'label' => __( 'Apply', 'vance-health-hub' ),
			'note'  => '', // the tier badge already says "Apply on the hub".
			'href'  => $apply,
			'attrs' => 'data-vance-tool-open="discount-apply" data-apply-url="' . esc_attr( $apply ) . '" data-apply-title="' . esc_attr( $row['title'] ) . '"',
		);
	}

	if ( in_array( $tier, array( 1, 2 ), true ) && $apply ) {
		/* translators: %s: provider name */
		$note = $row['provider']
			? sprintf( __( 'Opens %s', 'vance-health-hub' ), $row['provider'] )
			: __( 'Opens provider site', 'vance-health-hub' );
		return array(
			'label' => __( 'Apply', 'vance-health-hub' ),
			'note'  => $note,
			'href'  => $apply,
			'attrs' => 'data-vance-discount-popup="1" target="_blank" rel="noopener"',
		);
	}

	// Tier 3, or a tier 1/2 record whose apply_url is missing (data problem —
	// `wp vance discounts check` should already be flagging it) falls through
	// to the channel-specific tier-3 handling.
	$type = $row['apply_type'];
	$href = $apply ? $apply : $fallback;

	// The one scheme with a bespoke tier-3 flow (plan §10 step 7): the
	// declaration itself has no fillable form fields at all (confirmed by
	// inspecting the PDF directly — no /AcroForm), and Part 1 is the
	// SUPPLIER's section, not something a patient-facing tool should touch.
	// What we can honestly help with is Part 2, the customer's own
	// declaration — assets/js/discounts.js's VAT modal builds a filled
	// replica of just that section as a fresh, downloadable PDF.
	if ( 'vat-relief-disability' === $row['slug'] && $apply ) {
		return array(
			'label' => __( 'Apply', 'vance-health-hub' ),
			'note'  => __( 'Fill in your declaration', 'vance-health-hub' ),
			'href'  => $apply,
			'attrs' => 'data-vance-vat-declaration="1"',
		);
	}

	// WaterSure's other bespoke tier-3 flow (plan §10 step 7): the scheme is
	// administered per-household by whichever of 20 water companies serves
	// that address, not centrally, so there's no single "apply" URL to send
	// anyone to. Routes to this scheme's own single page rather than the
	// CCW/Citizens Advice official_url, where vance_watersure_suppliers()
	// (inc/discount-data.php) is rendered as a pick-your-company list.
	if ( 'watersure' === $row['slug'] ) {
		return array(
			'label' => __( 'Apply', 'vance-health-hub' ),
			'note'  => __( 'Find your supplier', 'vance-health-hub' ),
			'href'  => trailingslashit( $row['permalink'] ) . '#water-companies',
			'attrs' => '',
		);
	}

	switch ( $type ) {
		case 'phone':
			// apply_contact is often a whole sentence ("0800 917 2222 (Mon-Fri
			// 8am-5pm); online only in some postcodes; Scotland: mygov.scot/...")
			// rather than a bare number — pull out just the number-shaped run
			// (a UK number is 10-11 digits, optionally grouped with spaces) so
			// the note stays short and the tel: href isn't a garbage
			// concatenation of every stray digit in the sentence (found live,
			// 2026-09-04: PIP and Warm Home Discount both broke this way).
			if ( preg_match( '/0[\d ]{9,13}\d/', (string) $row['apply_contact'], $pm ) ) {
				$phone  = trim( preg_replace( '/\s+/', ' ', $pm[0] ) );
				$digits = preg_replace( '/[^0-9+]/', '', $phone );
				return array(
					'label' => __( 'Apply', 'vance-health-hub' ),
					'note'  => sprintf( /* translators: %s: phone number */ __( 'Call %s', 'vance-health-hub' ), $phone ),
					'href'  => 'tel:' . $digits,
					'attrs' => '',
				);
			}
			break;

		case 'pdf':
			return array(
				'label' => __( 'Apply', 'vance-health-hub' ),
				'note'  => __( 'Download form', 'vance-health-hub' ),
				'href'  => $href,
				'attrs' => $href ? 'target="_blank" rel="noopener"' : '',
			);

		case 'at-venue':
			return array( 'label' => __( 'Apply', 'vance-health-hub' ), 'note' => __( 'Show at the gate', 'vance-health-hub' ), 'href' => $href, 'attrs' => 'target="_blank" rel="noopener"' );

		case 'at-booking':
			return array( 'label' => __( 'Apply', 'vance-health-hub' ), 'note' => __( 'Select at booking', 'vance-health-hub' ), 'href' => $href, 'attrs' => 'target="_blank" rel="noopener"' );

		case 'via-supplier':
			return array( 'label' => __( 'Apply', 'vance-health-hub' ), 'note' => __( 'Find your supplier', 'vance-health-hub' ), 'href' => $href, 'attrs' => 'target="_blank" rel="noopener"' );

		case 'via-council':
			return array( 'label' => __( 'Apply', 'vance-health-hub' ), 'note' => __( 'Apply via your council', 'vance-health-hub' ), 'href' => $href, 'attrs' => 'target="_blank" rel="noopener"' );

		case 'via-gp':
			return array( 'label' => __( 'Apply', 'vance-health-hub' ), 'note' => __( 'Ask your GP', 'vance-health-hub' ), 'href' => $href, 'attrs' => '' );

		case 'in-account':
			return array( 'label' => __( 'Apply', 'vance-health-hub' ), 'note' => __( 'Sign in to apply', 'vance-health-hub' ), 'href' => $href, 'attrs' => 'target="_blank" rel="noopener"' );

		case 'post':
			return array( 'label' => __( 'Apply', 'vance-health-hub' ), 'note' => __( 'Apply by post', 'vance-health-hub' ), 'href' => $href, 'attrs' => 'target="_blank" rel="noopener"' );
	}

	return array(
		'label' => $href ? __( 'Learn more', 'vance-health-hub' ) : __( 'Details coming soon', 'vance-health-hub' ),
		'note'  => '',
		'href'  => $href,
		'attrs' => $href ? 'target="_blank" rel="noopener"' : '',
	);
}

/**
 * The Save button — visually and mechanically `.vance-save-btn` (see
 * single.php's `.va-save-main`), pointed at a discount-specific AJAX action
 * so its handler (inc/discount-dashboard.php, plan §10 step 5) can live
 * separately from the article bookmark one. Renders with the button inert if
 * that handler doesn't exist yet: clicking it will get WordPress's default
 * `0` response for an unregistered ajax action, which assets/js/discounts.js
 * treats as a no-op rather than an error, so this is safe to ship ahead of
 * step 5.
 *
 * @param int $post_id
 * @return string HTML.
 */
function vance_discount_save_button( $post_id ) {
	$logged_in = is_user_logged_in();
	$is_saved  = false;
	if ( $logged_in ) {
		$saved    = get_user_meta( get_current_user_id(), '_sla_saved_discounts', true );
		$is_saved = is_array( $saved ) && in_array( (int) $post_id, $saved, true );
	}
	$nonce = wp_create_nonce( 'vance_dashboard_nonce' );

	return sprintf(
		'<button type="button" class="vance-save-btn vance-discount-save%1$s" aria-pressed="%2$s" data-post-id="%3$d" data-nonce="%4$s" data-logged-in="%5$s">' .
		'<span class="va-save-icon" aria-hidden="true">%6$s</span><span class="va-save-text">%7$s</span></button>',
		$is_saved ? ' is-saved' : '',
		$is_saved ? 'true' : 'false',
		(int) $post_id,
		esc_attr( $nonce ),
		$logged_in ? '1' : '0',
		$is_saved ? '&#9733;' : '&#9734;',
		$is_saved ? esc_html__( 'Saved', 'vance-health-hub' ) : esc_html__( 'Save', 'vance-health-hub' )
	);
}

/**
 * The Apply button + its note, as one block — every caller that shows an
 * apply action (the grid card, the single template, the featured card) goes
 * through this so the button-vs-note split can't drift between them.
 *
 * @param array $action From vance_discount_apply_action().
 * @return string HTML.
 */
function vance_discount_render_apply_group( $action ) {
	ob_start();
	?>
	<div class="vance-discount-apply-group">
		<?php if ( $action['href'] ) : ?>
			<a href="<?php echo esc_url( $action['href'] ); ?>" class="vance-discount-apply-btn" <?php echo $action['attrs']; // phpcs:ignore WordPress.Security.EscapeOutput — built from esc_attr()'d parts in vance_discount_apply_action() ?>><?php echo esc_html( $action['label'] ); ?></a>
		<?php else : ?>
			<span class="vance-discount-apply-btn is-disabled"><?php echo esc_html( $action['label'] ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $action['note'] ) ) : ?>
			<span class="vance-discount-apply-note"><?php echo esc_html( $action['note'] ); ?></span>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * One scheme card — the directory grid and the single template's own summary
 * block both call this, so they can never drift. Markup follows
 * docs/DISCOUNTS_UI_SPEC.md §2: no image (a scheme has a provider, not a
 * photo), provider/title/value/cost stack, an upcoming-change banner when set,
 * then the tier badge + Save + Apply actions.
 *
 * @param int|array $post_id_or_row Post ID, or an already-fetched row.
 * @return string HTML.
 */
function vance_render_discount_card( $post_id_or_row ) {
	$row = is_array( $post_id_or_row ) ? $post_id_or_row : vance_discount_get( $post_id_or_row );
	if ( ! $row ) {
		return '';
	}

	$action = vance_discount_apply_action( $row );
	$region_note = ( $row['regions'] && ! in_array( 'uk', $row['regions'], true ) )
		? implode( ', ', $row['region_names'] ) . ' ' . __( 'only', 'vance-health-hub' )
		: '';

	ob_start();
	?>
	<div class="vance-discount-card"
		data-cat="<?php echo esc_attr( $row['category'] ? $row['category']['slug'] : '' ); ?>"
		data-region="<?php echo esc_attr( implode( ' ', $row['regions'] ) ); ?>"
		data-search="<?php echo esc_attr( strtolower( $row['title'] . ' ' . $row['provider'] ) ); ?>">
		<div class="vance-discount-card__top">
			<?php echo vance_discount_tier_badge( vance_discount_effective_tier( $row ) ); ?>
			<?php if ( $region_note ) : ?>
				<span class="vance-discount-card-region"><?php echo esc_html( $region_note ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( $row['provider'] ) : ?>
			<span class="vance-discount-card__provider"><?php echo esc_html( $row['provider'] ); ?></span>
		<?php endif; ?>
		<a href="<?php echo esc_url( $row['permalink'] ); ?>" class="vance-discount-card__title-link">
			<h3 class="vance-discount-card__title"><?php echo esc_html( $row['title'] ); ?></h3>
		</a>
		<?php if ( $row['value_summary'] ) : ?>
			<p class="vance-discount-card__value"><?php echo esc_html( $row['value_summary'] ); ?></p>
		<?php endif; ?>
		<?php if ( $row['cost'] ) : ?>
			<p class="vance-discount-card__cost"><?php echo esc_html( $row['cost'] ); ?></p>
		<?php endif; ?>
		<?php if ( $row['upcoming_change'] ) : ?>
			<p class="vance-discount-card__upcoming"><?php echo esc_html( $row['upcoming_change'] ); ?></p>
		<?php endif; ?>
		<div class="vance-discount-card__actions">
			<?php echo vance_discount_render_apply_group( $action ); ?>
			<?php echo vance_discount_save_button( $row['id'] ); ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * Featured discount card (plan §8) — sidebar/homepage/promo slots. NOT
 * `vance_render_discount_card()` at a different size: different context,
 * always singular, always with a "why this" eyebrow, and per
 * docs/DISCOUNTS_UI_SPEC.md §7 deliberately not square. No Save button here
 * — saving happens on the directory/single, not from a promo card.
 * ---------------------------------------------------------------------- */

/**
 * Every scheme an admin has flagged `_vance_discount_featured` — the pool
 * 'auto' falls back to and 'random' draws from.
 *
 * @return array<int, array<string, mixed>>
 */
function vance_discount_featured_pool() {
	$pool = array_values( array_filter(
		vance_discount_directory_data(),
		function ( $row ) {
			return ! empty( $row['featured'] );
		}
	) );
	// No admin has flagged anything yet — degrade to "every published
	// scheme" rather than render nothing, same call the plan makes for a
	// missing photograph (a motif, not a blank hero).
	return $pool ? $pool : vance_discount_directory_data();
}

/**
 * Article category/tag name fragments -> discount category slug. Deliberately
 * loose substring matching against the ARTICLE's own taxonomy names, not the
 * discount taxonomy's — an article never carries a `vance_discount_cat` term,
 * so the only signal available is what the article is *about*, in its own
 * words. False negatives (no match) are harmless: they fall back to the
 * featured pool, which is a fine card to show on any article.
 *
 * @return array<string,string> fragment => discount_cat slug
 */
function vance_discount_auto_category_map() {
	return array(
		'travel'      => 'travel',
		'holiday'     => 'travel',
		'flying'      => 'travel',
		'work'        => 'work',
		'career'      => 'work',
		'employ'      => 'work',
		'day'         => 'days-out',
		'lifestyle'   => 'days-out',
		'activit'     => 'days-out',
		'toilet'      => 'toilet-access',
		'bathroom'    => 'toilet-access',
		'urgency'     => 'toilet-access',
		'benefit'     => 'benefit',
		'financ'      => 'benefit',
		'money'       => 'benefit',
		'nhs'         => 'nhs',
		'prescription' => 'nhs',
		'tax'         => 'tax',
		'household'   => 'household',
		'home'        => 'household',
		'water'       => 'household',
	);
}

/**
 * Pick one scheme for an article, by its category/tag names -> a discount
 * category, then the featured (or lowest-tier) scheme within it. Falls back
 * to the whole featured pool, rotated by the article's post ID so the same
 * article always shows the same card rather than a different one per request.
 *
 * @param int $article_id
 * @return array<string, mixed>|null
 */
function vance_discount_auto_pick( $article_id ) {
	$map      = vance_discount_auto_category_map();
	$haystack = array();

	$cats = get_the_category( $article_id );
	foreach ( (array) $cats as $c ) {
		$haystack[] = strtolower( $c->name );
	}
	$tags = get_the_tags( $article_id );
	foreach ( (array) $tags as $t ) {
		$haystack[] = strtolower( $t->name );
	}

	$matched_cat = null;
	foreach ( $haystack as $name ) {
		foreach ( $map as $fragment => $discount_cat ) {
			if ( false !== strpos( $name, $fragment ) ) {
				$matched_cat = $discount_cat;
				break 2;
			}
		}
	}

	if ( $matched_cat ) {
		$in_cat = array_values( array_filter(
			vance_discount_directory_data(),
			function ( $row ) use ( $matched_cat ) {
				return $row['category'] && $matched_cat === $row['category']['slug'];
			}
		) );
		if ( $in_cat ) {
			// Prefer a featured one, then the lowest (easiest) tier, else the first.
			foreach ( $in_cat as $row ) {
				if ( $row['featured'] ) {
					return $row;
				}
			}
			usort( $in_cat, function ( $a, $b ) { return $a['tier'] <=> $b['tier']; } );
			return $in_cat[0];
		}
	}

	$pool = vance_discount_featured_pool();
	if ( ! $pool ) {
		return null;
	}
	return $pool[ $article_id % count( $pool ) ];
}

/**
 * @param string $mode 'pick' | 'auto' | 'member' | 'random'.
 * @param array  $args 'pick' needs post_id; 'auto'/'member' read the current
 *                      post from the loop unless post_id is given.
 * @return string HTML, or '' if there is nothing to show.
 */
function vance_render_featured_discount( $mode = 'auto', $args = array() ) {
	$article_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
	$row        = null;

	switch ( $mode ) {
		case 'pick':
			$row = isset( $args['post_id'] ) ? vance_discount_get( (int) $args['post_id'] ) : null;
			break;

		case 'member':
			// vance_discount_match() is the eligibility matcher, plan §6 —
			// not built until step 5. Degrade to 'auto' until it exists,
			// rather than a fatal on a function that isn't there yet.
			if ( is_user_logged_in() && function_exists( 'vance_discount_match' ) ) {
				$match = vance_discount_match( get_current_user_id() );
				$row   = ( $match && ! empty( $match['next_best_action'] ) ) ? vance_discount_get( $match['next_best_action'] ) : null;
			}
			if ( ! $row ) {
				$row = vance_discount_auto_pick( $article_id );
			}
			break;

		case 'random':
			$pool = vance_discount_featured_pool();
			$row  = $pool ? $pool[ array_rand( $pool ) ] : null;
			break;

		case 'auto':
		default:
			$row = vance_discount_auto_pick( $article_id );
	}

	if ( ! $row ) {
		return '';
	}

	$eyebrow = ( 'member' === $mode && is_user_logged_in() )
		? __( 'Recommended for you', 'vance-health-hub' )
		: __( 'Featured discount', 'vance-health-hub' );

	$action = vance_discount_apply_action( $row );

	ob_start();
	?>
	<div class="vance-discount-featured">
		<span class="vance-discount-featured__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
		<a href="<?php echo esc_url( $row['permalink'] ); ?>" class="vance-discount-featured__title-link">
			<h4 class="vance-discount-featured__title"><?php echo esc_html( $row['title'] ); ?></h4>
		</a>
		<?php if ( $row['value_summary'] ) : ?>
			<p class="vance-discount-featured__value"><?php echo esc_html( $row['value_summary'] ); ?></p>
		<?php endif; ?>
		<?php if ( $row['verified_on'] ) : ?>
			<p class="vance-discount-featured__verified"><?php echo esc_html( sprintf( __( 'Checked %s', 'vance-health-hub' ), $row['verified_on'] ) ); ?></p>
		<?php endif; ?>
		<?php if ( $action['href'] ) : ?>
			<a href="<?php echo esc_url( $action['href'] ); ?>" class="vance-discount-apply-btn vance-discount-featured-cta" <?php echo $action['attrs']; // phpcs:ignore WordPress.Security.EscapeOutput — built from esc_attr()'d parts in vance_discount_apply_action() ?>><?php echo esc_html( $action['label'] ); ?></a>
			<?php if ( ! empty( $action['note'] ) ) : ?>
				<span class="vance-discount-apply-note vance-discount-featured-note"><?php echo esc_html( $action['note'] ); ?></span>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * VAT declaration pre-fill (plan §10 step 7).
 *
 * The HMRC PDF has no fillable fields (confirmed by inspecting the file — no
 * /AcroForm marker), and its Part 1 is the SUPPLIER's section, filled in at
 * the till, not something a patient-facing tool should generate. This modal
 * only ever produces Part 2, the customer's own declaration, as a fresh PDF
 * built client-side with html2pdf.js (same library/version the recipe
 * meal-plan export already uses) — the member still signs it by hand and
 * hands it to their supplier, exactly as the real form instructs.
 * ---------------------------------------------------------------------- */

/**
 * Printed once in the footer, gated on the same page-context check as the
 * discounts script/style enqueue (functions.php) — cheap enough to not need
 * its own "is this scheme actually on the page" check.
 *
 * @return void
 */
function vance_discount_vat_modal_markup() {
	?>
	<div id="vance-vat-modal" class="vance-vat-modal" hidden>
		<div class="vance-vat-modal__panel" role="dialog" aria-modal="true" aria-labelledby="vance-vat-modal-title">
			<button type="button" class="vance-vat-modal__close" id="vance-vat-modal-close" aria-label="<?php esc_attr_e( 'Close', 'vance-health-hub' ); ?>">&times;</button>
			<h2 id="vance-vat-modal-title"><?php esc_html_e( 'Fill in your declaration', 'vance-health-hub' ); ?></h2>
			<p class="vance-vat-modal__intro"><?php esc_html_e( "This fills in Part 2 of the form: the section you complete yourself. Part 1 is filled in by the shop at the till. You'll still need to sign the result by hand.", 'vance-health-hub' ); ?></p>
			<label class="vance-vat-modal__field">
				<?php esc_html_e( 'Full name', 'vance-health-hub' ); ?>
				<input type="text" id="vance-vat-name">
			</label>
			<label class="vance-vat-modal__field">
				<?php esc_html_e( 'Address', 'vance-health-hub' ); ?>
				<textarea id="vance-vat-address" rows="3" placeholder="<?php esc_attr_e( 'House number and street, town, postcode', 'vance-health-hub' ); ?>"></textarea>
			</label>
			<label class="vance-vat-modal__field">
				<?php esc_html_e( 'Your disability or chronic sickness', 'vance-health-hub' ); ?>
				<textarea id="vance-vat-condition" rows="2" placeholder="<?php esc_attr_e( "e.g. Crohn's disease", 'vance-health-hub' ); ?>"></textarea>
			</label>
			<div class="vance-vat-modal__actions">
				<button type="button" class="vance-discount-apply-btn" id="vance-vat-generate"><?php esc_html_e( 'Generate my declaration', 'vance-health-hub' ); ?></button>
				<a href="#" id="vance-vat-blank-link" target="_blank" rel="noopener"><?php esc_html_e( 'Or download the blank form', 'vance-health-hub' ); ?></a>
			</div>
		</div>
	</div>
	<?php
}
