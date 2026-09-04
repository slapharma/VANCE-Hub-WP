<?php
/**
 * Discount directory data — one flat array shared by the directory grid, the
 * single template and (later) the featured renderer and dashboard, so there
 * is exactly one place that turns `vance_discount` posts + meta into the
 * shape a template renders. Copies the "compute once, cache for the request"
 * approach `vance_recipe_planner_data()` uses in inc/recipe-catalogue.php.
 *
 * The eligibility matcher (`vance_discount_match()`, plan §6) is NOT here yet
 * — it belongs with the dashboard/Access Folder work (plan §10 step 5) and
 * has no caller until that lands.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every published scheme, newest-tier-first-then-alphabetical is not
 * meaningful here — sorted by tier (1 before 2 before 3) so the easiest
 * wins surface first in an unfiltered grid, then by title.
 *
 * @return array<int, array<string, mixed>>
 */
function vance_discount_directory_data() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$posts = get_posts(
		array(
			'post_type'      => 'vance_discount',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$rows = array();
	foreach ( $posts as $post ) {
		$cat_terms    = get_the_terms( $post->ID, 'vance_discount_cat' );
		$region_terms = get_the_terms( $post->ID, 'vance_discount_region' );
		$tier         = (int) get_post_meta( $post->ID, '_vance_discount_tier', true );
		$signals      = get_post_meta( $post->ID, '_vance_discount_signals', true );
		$related      = get_post_meta( $post->ID, '_vance_discount_related_posts', true );
		$evidence     = get_post_meta( $post->ID, '_vance_discount_evidence', true );

		$rows[] = array(
			'id'              => $post->ID,
			'slug'            => $post->post_name,
			'title'           => get_the_title( $post ),
			'permalink'       => get_permalink( $post ),
			'provider'        => get_post_meta( $post->ID, '_vance_discount_provider', true ),
			'value_summary'   => get_post_meta( $post->ID, '_vance_discount_value', true ),
			'cost'            => get_post_meta( $post->ID, '_vance_discount_cost', true ),
			'what_you_get'    => get_post_meta( $post->ID, '_vance_discount_what', true ),
			'who_qualifies'   => get_post_meta( $post->ID, '_vance_discount_who', true ),
			'ibd_note'        => get_post_meta( $post->ID, '_vance_discount_ibd_note', true ),
			'evidence'        => $evidence ? array_filter( array_map( 'trim', explode( "\n", $evidence ) ) ) : array(),
			'signals'         => is_array( $signals ) ? $signals : array(),
			'official_url'    => get_post_meta( $post->ID, '_vance_discount_official_url', true ),
			'apply_url'       => get_post_meta( $post->ID, '_vance_discount_apply_url', true ),
			'apply_type'      => get_post_meta( $post->ID, '_vance_discount_apply_type', true ),
			'apply_contact'   => get_post_meta( $post->ID, '_vance_discount_apply_contact', true ),
			'tier'            => $tier,
			'frameable'       => (bool) get_post_meta( $post->ID, '_vance_discount_frameable', true ),
			'upcoming_change' => get_post_meta( $post->ID, '_vance_discount_upcoming', true ),
			'verified_on'     => get_post_meta( $post->ID, '_vance_discount_verified_on', true ),
			'confidence'      => get_post_meta( $post->ID, '_vance_discount_confidence', true ),
			'featured'        => (bool) get_post_meta( $post->ID, '_vance_discount_featured', true ),
			'related_posts'   => is_array( $related ) ? $related : array(),
			'category'        => ( $cat_terms && ! is_wp_error( $cat_terms ) ) ? array( 'slug' => $cat_terms[0]->slug, 'name' => $cat_terms[0]->name ) : null,
			'regions'         => ( $region_terms && ! is_wp_error( $region_terms ) ) ? wp_list_pluck( $region_terms, 'slug' ) : array(),
			'region_names'    => ( $region_terms && ! is_wp_error( $region_terms ) ) ? wp_list_pluck( $region_terms, 'name' ) : array(),
		);
	}

	usort(
		$rows,
		function ( $a, $b ) {
			$tier_a = $a['tier'] ?: 9;
			$tier_b = $b['tier'] ?: 9;
			return ( $tier_a === $tier_b ) ? strcasecmp( $a['title'], $b['title'] ) : ( $tier_a <=> $tier_b );
		}
	);

	$cache = $rows;
	return $cache;
}

/**
 * One scheme's row from the directory data, or null.
 *
 * @param int $post_id
 * @return array<string, mixed>|null
 */
function vance_discount_get( $post_id ) {
	foreach ( vance_discount_directory_data() as $row ) {
		if ( (int) $row['id'] === (int) $post_id ) {
			return $row;
		}
	}
	return null;
}

/**
 * The current member's Access Folder — `_sla_access_folder` (plan §4): a flat
 * `{signal_key: bool}` map plus a `region` string. Never a document, always a
 * checklist, per plan §6.
 *
 * @param int $user_id
 * @return array<string, mixed>
 */
function vance_discount_access_folder( $user_id ) {
	$folder = get_user_meta( $user_id, '_sla_access_folder', true );
	return is_array( $folder ) ? $folder : array();
}

/**
 * Eligibility matcher (plan §6). Three buckets, never the word "eligible" —
 * that's the provider's call, not this site's, and the page copy says so
 * verbatim wherever this feeds a template. A scheme matches when the member
 * holds any one of its `eligibility_signals`, OR the scheme's only signal is
 * `ibd_diagnosis` (nothing else gates it), OR the member has flagged
 * `needs_companion` and the scheme itself is signalled that way.
 *
 * Region acts as a hard filter, not a signal: a scheme scoped to a nation the
 * member isn't in is never "likely" or "possible", it's simply out of reach —
 * its own bucket rather than silently dropped, so the folder screen can still
 * say why 8 of 34 schemes never show up as a maybe.
 *
 * @param int $user_id
 * @return array{likely:array,possible:array,not_in_region:array,next_best_action:?int}
 */
function vance_discount_match( $user_id ) {
	$folder = vance_discount_access_folder( $user_id );
	$region = isset( $folder['region'] ) ? $folder['region'] : '';

	$likely = array();
	$possible = array();
	$not_in_region = array();

	foreach ( vance_discount_directory_data() as $row ) {
		if ( $region && $row['regions'] && ! in_array( 'uk', $row['regions'], true ) && ! in_array( $region, $row['regions'], true ) ) {
			$not_in_region[] = $row;
			continue;
		}

		$signals      = $row['signals'];
		$gating       = array_values( array_diff( $signals, array( 'ibd_diagnosis' ) ) );
		$is_match     = false;

		if ( empty( $gating ) ) {
			// No gating signal beyond "has IBD" (or no signal at all) — open to
			// anyone this directory is for.
			$is_match = true;
		} else {
			foreach ( $gating as $sig ) {
				if ( ! empty( $folder[ $sig ] ) ) {
					$is_match = true;
					break;
				}
			}
		}

		if ( $is_match ) {
			$likely[] = $row;
		} elseif ( ! empty( $row['evidence'] ) ) {
			// No signal held, but the scheme names evidence a member could go
			// and get — a real "maybe", not a dead end.
			$possible[] = $row;
		}
	}

	return array(
		'likely'            => $likely,
		'possible'          => $possible,
		'not_in_region'     => $not_in_region,
		'next_best_action'  => vance_discount_next_best_action_id( $folder ),
	);
}

/**
 * The cheapest unlock a member does not already hold, as a post ID — plan
 * §6: "For most members that is the Access Card (£15, unlocks eight
 * companion schemes) or CCUK membership (£15, unlocks RADAR + Can't Wait)."
 * Named literally rather than computed by scanning every scheme's signal
 * list for the biggest unlock, because the plan already did that analysis —
 * recomputing it at runtime would risk landing on a technically-bigger but
 * practically-worse recommendation (e.g. a benefit that takes months to
 * assess) for a £15 membership card that unlocks same-day.
 *
 * @param array $folder From vance_discount_access_folder().
 * @return int|null Post ID, or null once both unlocks are already held.
 */
function vance_discount_next_best_action_id( $folder ) {
	$candidates = array(
		'access_card' => 'nimbus-access-card',
		'ccuk_member' => 'ccuk-radar-key',
	);

	foreach ( $candidates as $signal => $slug ) {
		if ( empty( $folder[ $signal ] ) ) {
			$post = get_page_by_path( $slug, OBJECT, 'vance_discount' );
			if ( $post ) {
				return $post->ID;
			}
		}
	}

	return null;
}

/**
 * Live counts for the hero band (plan §5's page-hero-spotlight.php edit) and
 * anywhere else a headline number about the directory is needed. Computed
 * per request from the same cached array everything else reads, not stored —
 * a wrong number here means the query changed, not that a value went stale
 * (same principle CLAUDE.md's smoke-test section states for the category
 * hero's live facts band).
 *
 * @return array{total:int,free:int,tier1:int}
 */
function vance_discount_counts() {
	$rows  = vance_discount_directory_data();
	$total = count( $rows );
	$free  = 0;
	$tier1 = 0;

	foreach ( $rows as $row ) {
		$cost = strtolower( trim( (string) $row['cost'] ) );
		if ( '' === $cost || 0 === strpos( $cost, 'free' ) ) {
			$free++;
		}
		if ( 1 === (int) $row['tier'] ) {
			$tier1++;
		}
	}

	return array( 'total' => $total, 'free' => $free, 'tier1' => $tier1 );
}

/**
 * All distinct categories present in the data, in taxonomy term order (not
 * alphabetical) — plan §5 fixes the order: toilet-access, days-out, travel,
 * access-card, benefit, nhs, tax, work, household. Only categories that
 * actually have a published scheme are returned, so the filter bar never
 * offers an empty chip.
 *
 * @return array<int, array{slug:string,name:string}>
 */
function vance_discount_categories_in_use() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'vance_discount_cat',
			'hide_empty' => true,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}

	$order = array_flip( array( 'toilet-access', 'days-out', 'travel', 'access-card', 'benefit', 'nhs', 'tax', 'work', 'household' ) );
	usort(
		$terms,
		function ( $a, $b ) use ( $order ) {
			$pa = isset( $order[ $a->slug ] ) ? $order[ $a->slug ] : 99;
			$pb = isset( $order[ $b->slug ] ) ? $order[ $b->slug ] : 99;
			return $pa <=> $pb;
		}
	);

	return array_map(
		function ( $t ) {
			return array( 'slug' => $t->slug, 'name' => $t->name );
		},
		$terms
	);
}

/**
 * The 20 water and sewerage retail companies serving household customers in
 * England and Wales, each with a live, content-verified WaterSure (or
 * identically-named branded equivalent, e.g. "Bill Cap" / "WaterSure Wales" /
 * "WaterSure Plus") page — plan §10 step 7's "no API, ship a static list".
 * WaterSure is administered per-household by whichever company serves that
 * address, not centrally, so there is nothing to look up by postcode without
 * a real address-lookup API; this lets a member pick by name/region instead.
 *
 * Every URL was fetched live and confirmed to name WaterSure (or the
 * equivalent scheme) on 2026-09-04 — several of these sites 403 a
 * non-browser fetch (Cloudflare/Akamai/Azure WAFs), which is why a handful
 * were instead confirmed via a recent Wayback Machine snapshot plus the
 * live page's own sitemap.xml; none were guessed from memory. Scotland and
 * Northern Ireland run their own separate schemes and are out of scope here.
 *
 * Two corrections worth recording because they contradicted each other
 * during research: (1) Bournemouth Water is NOT folded into South West
 * Water's customer-facing site — it is still its own live domain with its
 * own WaterSure page (bournemouthwater.co.uk/renew-watersure-tariff,
 * confirmed 200 with real content 2026-09-04), despite Pennon Group owning
 * both. (2) Hafren Dyfrdwy's public-facing brand is "HD Cymru" — the legal
 * company name is unchanged, only the customer site's branding differs.
 *
 * @return array<int, array{name:string, regions:string, url:string}>
 */
function vance_watersure_suppliers() {
	return array(
		array( 'name' => 'Affinity Water', 'regions' => 'Herts, Beds, Bucks, Essex, Surrey, parts of NW London', 'url' => 'https://www.affinitywater.co.uk/watersure' ),
		array( 'name' => 'Anglian Water', 'regions' => 'East of England — Cambs, Norfolk, Suffolk, Essex, Lincs, Beds, Northants', 'url' => 'https://www.anglianwater.co.uk/services/extra-support/tariff-options/watersure' ),
		array( 'name' => 'Bournemouth Water', 'regions' => 'Poole, Bournemouth, parts of Dorset, Hampshire and Wiltshire', 'url' => 'https://www.bournemouthwater.co.uk/renew-watersure-tariff' ),
		array( 'name' => 'Bristol Water', 'regions' => 'Bristol, North Somerset, South Gloucestershire', 'url' => 'https://www.bristolwater.co.uk/home/account-and-services/bills-and-payments/watersure-eligibility-checker' ),
		array( 'name' => 'Cambridge Water', 'regions' => 'Cambridge and the surrounding area', 'url' => 'https://www.cambridge-water.co.uk/household/my-bills-and-payments/my-bill-explained/other-charges-and-tariffs/watersure/' ),
		array( 'name' => 'Dŵr Cymru Welsh Water', 'regions' => 'Most of Wales, plus Herefordshire and border areas', 'url' => 'https://www.dwrcymru.com/en/help-with-your-bills/watersure-tariff' ),
		array( 'name' => 'Essex & Suffolk Water', 'regions' => 'Essex, SE London boroughs, and the Suffolk/Norfolk coast', 'url' => 'https://www.eswater.co.uk/watersure' ),
		array( 'name' => 'Hafren Dyfrdwy (HD Cymru)', 'regions' => 'Wrexham, Denbighshire, Flintshire, northern Powys', 'url' => 'https://www.hdcymru.co.uk/my-account/help-when-you-need-it/help-with-paying-your-bill/watersure-scheme/' ),
		array( 'name' => 'Northumbrian Water', 'regions' => 'Tyneside, Wearside, Teesside, Northumberland, County Durham', 'url' => 'https://www.nwl.co.uk/watersure' ),
		array( 'name' => 'Portsmouth Water', 'regions' => 'Portsmouth and south-east Hampshire', 'url' => 'https://www.portsmouthwater.co.uk/customer-services/help-with-my-bills/watersure-application/' ),
		array( 'name' => 'SES Water', 'regions' => 'Surrey, Kent, Sussex, parts of south London', 'url' => 'https://www.seswater.co.uk/household/help-support/financial-support' ),
		array( 'name' => 'Severn Trent Water', 'regions' => 'The Midlands — Birmingham, Notts, Derbyshire, Leicestershire, Staffordshire', 'url' => 'https://www.stwater.co.uk/help-and-contact/help-with-paying-your-bill/watersure-scheme/' ),
		array( 'name' => 'South East Water', 'regions' => 'Kent, Sussex, Surrey, Hampshire, Berkshire', 'url' => 'https://www.southeastwater.co.uk/help/priority-services/help-paying-your-bill/' ),
		array( 'name' => 'South Staffs Water', 'regions' => 'Walsall, Sandwell, Dudley, Cannock, Lichfield, Sutton Coldfield', 'url' => 'https://www.south-staffs-water.co.uk/household/my-bills-and-payments/my-bill-explained/other-charges-and-tariffs/watersure/' ),
		array( 'name' => 'South West Water', 'regions' => 'Devon and Cornwall, plus parts of Dorset and Somerset', 'url' => 'https://www.southwestwater.co.uk/household/help-support/financial-support/renewing-your-watersure-tariff' ),
		array( 'name' => 'Southern Water', 'regions' => 'Kent, Sussex, Hampshire, Isle of Wight', 'url' => 'https://www.southernwater.co.uk/help-and-support/what-if-i-cant-pay-my-bill/' ),
		array( 'name' => 'Thames Water', 'regions' => 'London and the Thames Valley', 'url' => 'https://www.thameswater.co.uk/help/account-and-billing/financial-support/watersure' ),
		array( 'name' => 'United Utilities', 'regions' => 'North West England — Cumbria, Lancashire, Greater Manchester, Merseyside, Cheshire', 'url' => 'https://www.unitedutilities.com/watersure' ),
		array( 'name' => 'Wessex Water', 'regions' => 'Dorset, Somerset, Wiltshire, parts of Gloucestershire and Hampshire', 'url' => 'https://www.wessexwater.co.uk/bills-and-accounts/help-to-pay-your-bill/bill-cap-scheme-watersure' ),
		array( 'name' => 'Yorkshire Water', 'regions' => 'West, South, North and East Riding of Yorkshire', 'url' => 'https://www.yorkshirewater.com/bill-account/help-paying-your-bill/' ),
	);
}
