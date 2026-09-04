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
