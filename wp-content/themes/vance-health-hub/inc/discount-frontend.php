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
 * Resolve one scheme's Apply button: label, href, and any data-* the click
 * behaviour needs. Copies plan §7's per-tier table; the tier-3 labels are the
 * general case per apply_type rather than the bespoke per-scheme copy
 * ("Call 0800 917 2222", the WaterSure supplier lookup) plan §7 describes —
 * those are plan §10 step 7 work and layer on top of this without changing
 * the shape returned here.
 *
 * @param array $row One row from vance_discount_directory_data().
 * @return array{label:string,href:string,attrs:string}
 */
function vance_discount_apply_action( $row ) {
	$tier    = (int) $row['tier'];
	$apply   = $row['apply_url'];
	$fallback = $row['official_url'];

	// Tier 1 also requires frameable=true, kept fresh by the periodic
	// `wp vance discounts check` re-probe (inc/discount-check.php) — this is
	// the actual fallback plan §10 step 6 asks for. A cross-origin iframe's
	// own X-Frame-Options/frame-ancestors block is not observable from the
	// parent page at all (same-origin policy hides it, and X-Frame-Options
	// fires no JS event whatsoever), so there is no reliable *client-side*
	// runtime detection to fall back on — the server-side recheck is the one
	// that actually works, and a tier-1 scheme whose provider adds a framing
	// header degrades to the tier-2 popup automatically on the next check run
	// without any code change here.
	if ( 1 === $tier && $apply && ! empty( $row['frameable'] ) ) {
		return array(
			'label' => __( 'Apply on the hub', 'vance-health-hub' ),
			'href'  => $apply,
			'attrs' => 'data-vance-tool-open="discount-apply" data-apply-url="' . esc_attr( $apply ) . '" data-apply-title="' . esc_attr( $row['title'] ) . '"',
		);
	}

	// Reached by tier 2, and by tier 1 whose frameable check above was false
	// (the fallback) — both get the identical popup treatment.
	if ( in_array( $tier, array( 1, 2 ), true ) && $apply ) {
		/* translators: %s: provider name */
		$label = $row['provider']
			? sprintf( __( 'Apply (opens %s)', 'vance-health-hub' ), $row['provider'] )
			: __( 'Apply (opens provider)', 'vance-health-hub' );
		return array(
			'label' => $label,
			'href'  => $apply,
			'attrs' => 'data-vance-discount-popup="1" target="_blank" rel="noopener"',
		);
	}

	// Tier 3, or a tier 1/2 record whose apply_url is missing (data problem —
	// `wp vance discounts check` should already be flagging it) falls through
	// to the channel-specific tier-3 handling.
	$type = $row['apply_type'];
	$href = $apply ? $apply : $fallback;

	switch ( $type ) {
		case 'phone':
			// apply_contact is often a whole sentence ("0800 917 2222 (Mon-Fri
			// 8am-5pm); online only in some postcodes; Scotland: mygov.scot/...")
			// rather than a bare number — pull out just the number-shaped run
			// (a UK number is 10-11 digits, optionally grouped with spaces) so
			// the button label stays short and the tel: href isn't a garbage
			// concatenation of every stray digit in the sentence (found live,
			// 2026-09-04: PIP and Warm Home Discount both broke this way).
			if ( preg_match( '/0[\d ]{9,13}\d/', (string) $row['apply_contact'], $pm ) ) {
				$phone  = trim( preg_replace( '/\s+/', ' ', $pm[0] ) );
				$digits = preg_replace( '/[^0-9+]/', '', $phone );
				return array(
					'label' => sprintf( /* translators: %s: phone number */ __( 'Call %s', 'vance-health-hub' ), $phone ),
					'href'  => 'tel:' . $digits,
					'attrs' => '',
				);
			}
			break;

		case 'pdf':
			return array(
				'label' => __( 'Download form', 'vance-health-hub' ),
				'href'  => $href,
				'attrs' => $href ? 'target="_blank" rel="noopener"' : '',
			);

		case 'at-venue':
			return array( 'label' => __( 'Show at the gate', 'vance-health-hub' ), 'href' => $href, 'attrs' => 'target="_blank" rel="noopener"' );

		case 'at-booking':
			return array( 'label' => __( 'Select at booking', 'vance-health-hub' ), 'href' => $href, 'attrs' => 'target="_blank" rel="noopener"' );

		case 'via-supplier':
			return array( 'label' => __( 'Find your supplier', 'vance-health-hub' ), 'href' => $href, 'attrs' => 'target="_blank" rel="noopener"' );

		case 'via-council':
			return array( 'label' => __( 'Apply via your council', 'vance-health-hub' ), 'href' => $href, 'attrs' => 'target="_blank" rel="noopener"' );

		case 'via-gp':
			return array( 'label' => __( 'Ask your GP', 'vance-health-hub' ), 'href' => $href, 'attrs' => '' );

		case 'in-account':
			return array( 'label' => __( 'Sign in to apply', 'vance-health-hub' ), 'href' => $href, 'attrs' => 'target="_blank" rel="noopener"' );

		case 'post':
			return array( 'label' => __( 'Apply by post', 'vance-health-hub' ), 'href' => $href, 'attrs' => 'target="_blank" rel="noopener"' );
	}

	return array(
		'label' => $href ? __( 'Learn more', 'vance-health-hub' ) : __( 'Details coming soon', 'vance-health-hub' ),
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
			<?php echo vance_discount_tier_badge( $row['tier'] ); ?>
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
			<?php if ( $action['href'] ) : ?>
				<a href="<?php echo esc_url( $action['href'] ); ?>" class="vance-discount-apply-btn" <?php echo $action['attrs']; // phpcs:ignore WordPress.Security.EscapeOutput — built from esc_attr()'d parts above ?>><?php echo esc_html( $action['label'] ); ?></a>
			<?php else : ?>
				<span class="vance-discount-apply-btn is-disabled"><?php echo esc_html( $action['label'] ); ?></span>
			<?php endif; ?>
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
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
