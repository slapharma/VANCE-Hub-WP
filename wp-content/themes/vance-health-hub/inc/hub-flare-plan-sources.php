<?php
/**
 * VanceHubClub member dashboard: what the flare plan may read from the member's
 * other tools.
 *
 * The plan shows the member's own data from four stores. Each store is read only
 * through its own gate. A closed gate, a missing function or a damaged value gives
 * an empty part, with no error. Nothing here writes: no value is copied into the
 * plan on the server. Only the member's own save writes the plan.
 *
 *   part       store                            gate
 *   profile    _sla_clinical_profile            the general health agreement (_sla_consent_health)
 *   condition  _sla_healthcare_quiz_results     the general health agreement (_sla_consent_health)
 *   reading    _sla_reading_list                the member is the current user (a list of article ids)
 *   documents  vance_user_docs_get()            the My Documents feature is on
 *
 * The keys of _sla_clinical_profile (vance_clinical_profile_fields() in functions.php)
 * that have a matching plan section:
 *
 *   medication             -> medicines   (My medicines)
 *   trigger_foods          -> triggers    (My triggers)
 *   appointment_questions  -> questions   (Questions for my next appointment)
 *   next_appointment       -> review_on   (I will review this plan on), only a real Y-m-d date
 *
 * The plan gives no advice, no verdict and no score. A value is shown as the member
 * typed it. A document is a title and an id: never its text or its file.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The profile keys that have a matching plan section, with the line that says where
 * each value comes from.
 *
 * @return array[] profile key => {target, label}, in the order they show.
 */
function vance_hub_flare_plan_sources_profile_map() {
	return array(
		'medication'            => array(
			'target' => 'medicines',
			'label'  => __( 'Medication in my health profile', 'vance-health-hub' ),
		),
		'trigger_foods'         => array(
			'target' => 'triggers',
			'label'  => __( 'Trigger foods in my health profile', 'vance-health-hub' ),
		),
		'appointment_questions' => array(
			'target' => 'questions',
			'label'  => __( 'Appointment questions in my health profile', 'vance-health-hub' ),
		),
		'next_appointment'      => array(
			'target' => 'review_on',
			'label'  => __( 'Next appointment in my health profile', 'vance-health-hub' ),
		),
	);
}

/**
 * The values of the member's health profile that have a matching plan section.
 * Needs the general health agreement; the caller checks it.
 *
 * An item is left out when its value is empty, when it is not text, when the next
 * appointment is not a real Y-m-d date, or when the matching plan section is not
 * offered (an administrator turned it off and the member has nothing in it).
 *
 * @param int $uid Member.
 * @return array[] {key, label, value, target}
 */
function vance_hub_flare_plan_sources_profile( $uid ) {
	$stored = get_user_meta( (int) $uid, '_sla_clinical_profile', true );
	if ( ! is_array( $stored ) ) {
		return array();
	}
	// A section is offered when an administrator has it on, or the member has content in it.
	$sections = vance_hub_flare_plan_sections();
	$plan     = vance_hub_flare_plan_get( (int) $uid );
	$items    = array();
	foreach ( vance_hub_flare_plan_sources_profile_map() as $key => $map ) {
		$target = $map['target'];
		if ( ! isset( $sections[ $target ] ) || ! isset( $stored[ $key ] ) || ! is_string( $stored[ $key ] ) ) {
			continue;
		}
		$value = trim( $stored[ $key ] );
		if ( '' === $value ) {
			continue;
		}
		if ( 'review_on' === $target && ! vance_hub_flare_log_date_ok( $value ) ) {
			continue;
		}
		if ( empty( $sections[ $target ]['on'] ) && ! vance_hub_flare_plan_has_content( (array) $plan, $target ) ) {
			continue;
		}
		$items[] = array(
			'key'    => $key,
			'label'  => $map['label'],
			'value'  => $value,
			'target' => $target,
		);
	}
	return $items;
}

/**
 * The condition page of the member, from the "Which condition are you most concerned
 * with?" answer of the Self Assessment. Needs the general health agreement; the
 * caller checks it.
 *
 * Uses the site's own condition vocabulary (vance_condition_vocabulary()) and its
 * published condition pages (vance_condition_page()). It gives a page only when the
 * answer names exactly one condition that has a published page. Two or more
 * conditions, "Other", "General gut health / wellness" and the free text give nothing.
 *
 * @param int $uid Member.
 * @return array {title, url}, or an empty array.
 */
function vance_hub_flare_plan_sources_condition( $uid ) {
	if ( ! function_exists( 'vance_condition_vocabulary' ) || ! function_exists( 'vance_condition_page' ) ) {
		return array();
	}
	$quiz = get_user_meta( (int) $uid, '_sla_healthcare_quiz_results', true );
	if ( ! is_array( $quiz ) || ! isset( $quiz['condition_type'] ) || ! is_string( $quiz['condition_type'] ) || '' === trim( $quiz['condition_type'] ) ) {
		return array();
	}
	$found = array();
	// The quiz saves the ticked answers as one text, joined with ", ".
	foreach ( explode( ',', $quiz['condition_type'] ) as $part ) {
		$part = trim( $part );
		if ( '' === $part ) {
			continue;
		}
		foreach ( (array) vance_condition_vocabulary() as $slug => $patterns ) {
			foreach ( (array) $patterns as $pattern ) {
				if ( is_string( $pattern ) && 1 === @preg_match( $pattern, $part ) ) {
					$found[ (string) $slug ] = true;
					break;
				}
			}
		}
	}
	if ( 1 !== count( $found ) ) {
		return array();
	}
	$slugs = array_keys( $found );
	$page  = vance_condition_page( $slugs[0] );
	if ( ! is_object( $page ) ) {
		return array();
	}
	$title = trim( (string) get_the_title( $page ) );
	$url   = (string) get_permalink( $page );
	if ( '' === $title || '' === $url ) {
		return array();
	}
	return array( 'title' => $title, 'url' => $url );
}

/**
 * How many articles the member saved, and the link to the page that lists them.
 * Only for the member who is logged in: the list is the member's own, and it holds
 * article ids only.
 *
 * @param int $uid Member.
 * @return array {count, url}, or an empty array when the list is empty or not the current member's.
 */
function vance_hub_flare_plan_sources_reading( $uid ) {
	$uid = (int) $uid;
	if ( $uid <= 0 || ! function_exists( 'get_current_user_id' ) || (int) get_current_user_id() !== $uid ) {
		return array();
	}
	// The Reading List tab must be on, or the link would lead nowhere.
	if ( function_exists( 'vance_dashboard_feature_enabled' ) && ! vance_dashboard_feature_enabled( 'reading-list' ) ) {
		return array();
	}
	$list = get_user_meta( $uid, '_sla_reading_list', true );
	if ( ! is_array( $list ) ) {
		return array();
	}
	$ids = array();
	foreach ( $list as $id ) {
		$id = is_scalar( $id ) ? absint( $id ) : 0;
		if ( $id > 0 ) {
			$ids[ $id ] = true;
		}
	}
	if ( empty( $ids ) ) {
		return array();
	}
	return array(
		'count' => count( $ids ),
		'url'   => function_exists( 'vance_hub_dashboard_tab_url' ) ? vance_hub_dashboard_tab_url( 'reading-list' ) : '?tab=reading-list',
	);
}

/**
 * The member's documents: id and title only. Never the text or the file.
 *
 * @param int $uid Member.
 * @return array[] {id, title}, in the order of the member's list.
 */
function vance_hub_flare_plan_sources_documents( $uid ) {
	if ( ! vance_hub_flare_plan_docs_open() ) {
		return array();
	}
	$out = array();
	foreach ( (array) vance_user_docs_get( (int) $uid ) as $doc ) {
		if ( ! is_array( $doc ) || ! isset( $doc['id'] ) || (int) $doc['id'] <= 0 ) {
			continue;
		}
		$title = isset( $doc['name'] ) && is_string( $doc['name'] ) ? trim( $doc['name'] ) : '';
		$out[] = array(
			'id'    => (int) $doc['id'],
			'title' => '' !== $title ? $title : __( 'Document', 'vance-health-hub' ),
		);
	}
	return $out;
}

/**
 * What the flare plan may read from the member's other tools. Read only.
 *
 * Each part is empty when its gate is closed (see the table at the top of this file).
 *
 * @param int $uid Member.
 * @return array {profile: array[], condition: array, reading: array, documents: array[]}
 */
function vance_hub_flare_plan_sources( $uid ) {
	$uid = (int) $uid;
	$out = array(
		'profile'   => array(),
		'condition' => array(),
		'reading'   => array(),
		'documents' => array(),
	);
	// Only the member's own data, to the member: every part is empty for another user.
	if ( $uid <= 0 || (int) get_current_user_id() !== $uid ) {
		return $out;
	}
	if ( vance_hub_flare_plan_health_open( $uid ) ) {
		$out['profile']   = vance_hub_flare_plan_sources_profile( $uid );
		$out['condition'] = vance_hub_flare_plan_sources_condition( $uid );
	}
	$out['reading']   = vance_hub_flare_plan_sources_reading( $uid );
	$out['documents'] = vance_hub_flare_plan_sources_documents( $uid );
	return $out;
}
