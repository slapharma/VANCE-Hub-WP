<?php
/**
 * VanceHubClub member dashboard: "Manage my data".
 *
 * A member can delete the health data they saved in the Hub, for each group or
 * all at once. The control is the last tab of the Health Profile tab row
 * (?tab=health-profile&hsub=data).
 *
 * Groups, and the user meta each one holds (keys are the legacy `_sla_*` keys,
 * never renamed):
 *
 *   assessment    _sla_healthcare_quiz_results   Self Assessment answers
 *   details       _sla_clinical_profile          Health details and lifestyle
 *   malnutrition  _sla_malnutrition_calculator_history, and every _sla_calc_results_*
 *   tools         _sla_<tool>_history            any other saved tool results
 *   checkins      daily check-in answers and the agreement
 *   flare         the flare plan and the agreement
 *
 * For the check-ins and the flare plan, the agreement is withdrawn the same way
 * as in their own delete: the "agreed" flag goes, and the withdrawal time is
 * stored. The old agreement time and wording stay as the record.
 *
 * Not in scope: the account itself, uploaded documents (_sla_profile_docs), notes,
 * saved chats, bookmarks, discounts, consult requests and messages. They are not health answers, and they have their own tabs.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * Groups
 * ====================================================================== */

/**
 * The meta key of a tool history, as vance_append_tool_history() builds it.
 *
 * @param string $tool_slug Tool slug.
 * @return string
 */
function vance_hub_data_tool_key( $tool_slug ) {
	return '_sla_' . str_replace( '-', '_', (string) $tool_slug ) . '_history';
}

/**
 * The groups of data a member can delete.
 *
 * @return array[] slug => {label, text, keys (string[]), consent (string|null: base of an agreement to withdraw)}.
 */
function vance_hub_data_groups() {
	$groups = array(
		'assessment'   => array(
			'label'   => __( 'Self Assessment answers', 'vance-health-hub' ),
			'text'    => __( 'The answers you gave in the health discovery quiz.', 'vance-health-hub' ),
			'keys'    => array( '_sla_healthcare_quiz_results' ),
			'consent' => null,
		),
		'details'      => array(
			'label'   => __( 'Health details and lifestyle', 'vance-health-hub' ),
			'text'    => __( 'Weight, height, medication, allergies, trigger foods, appointment notes and your own notes.', 'vance-health-hub' ),
			'keys'    => array( '_sla_clinical_profile' ),
			'consent' => null,
		),
		'malnutrition' => array(
			'label'   => __( 'Malnutrition screening results', 'vance-health-hub' ),
			'text'    => __( 'Every screening you saved from the calculator.', 'vance-health-hub' ),
			'keys'    => array( vance_hub_data_tool_key( 'malnutrition-calculator' ) ),
			'consent' => null,
		),
	);

	$other = array();
	if ( function_exists( 'vance_known_tool_slugs' ) ) {
		foreach ( vance_known_tool_slugs() as $slug ) {
			if ( 'malnutrition-calculator' !== $slug ) {
				$other[] = vance_hub_data_tool_key( $slug );
			}
		}
	}
	if ( ! empty( $other ) ) {
		$groups['tools'] = array(
			'label'   => __( 'Other saved tool results', 'vance-health-hub' ),
			'text'    => __( 'Results you saved from the other Hub tools.', 'vance-health-hub' ),
			'keys'    => $other,
			'consent' => null,
		);
	}

	if ( function_exists( 'vance_hub_checkin_meta_key' ) ) {
		$groups['checkins'] = array(
			'label'   => __( 'Daily check-ins', 'vance-health-hub' ),
			'text'    => __( 'Your daily answers, and your agreement to store them.', 'vance-health-hub' ),
			'keys'    => array( vance_hub_checkin_meta_key() ),
			'consent' => 'checkin',
		);
	}
	if ( function_exists( 'vance_hub_flare_plan_meta_key' ) ) {
		$groups['flare'] = array(
			'label'   => __( 'Flare plan', 'vance-health-hub' ),
			'text'    => __( 'Your plan for a flare, your flare log, and your agreement to store them.', 'vance-health-hub' ),
			'keys'    => array_merge( array( vance_hub_flare_plan_meta_key() ), function_exists( 'vance_hub_flare_log_meta_key' ) ? array( vance_hub_flare_log_meta_key() ) : array() ),
			'consent' => 'flare_plan',
		);
	}
	return $groups;
}

/**
 * The meta keys of one group for one member.
 *
 * The malnutrition group also holds the older calculator save path,
 * `_sla_calc_results_<tool>` (functions.php, vance_save_calc_result). The tool
 * part of that key is free text, so the keys are read from the database.
 *
 * @param int    $uid  Member.
 * @param string $slug Group slug.
 * @return string[]
 */
function vance_hub_data_keys( $uid, $slug ) {
	$groups = vance_hub_data_groups();
	if ( ! isset( $groups[ $slug ] ) ) {
		return array();
	}
	$keys = $groups[ $slug ]['keys'];
	if ( 'malnutrition' === $slug && isset( $GLOBALS['wpdb'] ) && is_object( $GLOBALS['wpdb'] ) ) {
		global $wpdb;
		$like  = $wpdb->esc_like( '_sla_calc_results_' ) . '%';
		$found = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s", (int) $uid, $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- reads this member's own key names, nothing is cached.
		$keys  = array_values( array_unique( array_merge( $keys, array_map( 'strval', (array) $found ) ) ) );
	}
	return $keys;
}

/**
 * True when a stored value counts as data: anything but '', an empty array, 0.
 *
 * @param mixed $value Meta value.
 * @return bool
 */
function vance_hub_data_filled( $value ) {
	return ! empty( $value );
}

/**
 * True when the member has data in one group.
 *
 * @param int    $uid   Member.
 * @param string $slug  Group slug.
 * @return bool
 */
function vance_hub_data_has( $uid, $slug ) {
	$groups = vance_hub_data_groups();
	if ( ! isset( $groups[ $slug ] ) ) {
		return false;
	}
	foreach ( vance_hub_data_keys( $uid, $slug ) as $key ) {
		if ( vance_hub_data_filled( get_user_meta( (int) $uid, $key, true ) ) ) {
			return true;
		}
	}
	// An agreement still in force counts too, so a member can always withdraw it,
	// even when the answers are already gone.
	if ( null !== $groups[ $slug ]['consent'] && function_exists( 'vance_hub_checkin_consent_keys' ) ) {
		$flag = vance_hub_checkin_consent_keys( $groups[ $slug ]['consent'] )[0];
		if ( get_user_meta( (int) $uid, $flag, true ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Delete one group for one member, and check from the database that it is gone.
 *
 * @param int    $uid  Member.
 * @param string $slug Group slug.
 * @return bool True when the data is gone.
 */
function vance_hub_data_erase( $uid, $slug ) {
	$uid    = (int) $uid;
	$groups = vance_hub_data_groups();
	if ( $uid <= 0 || ! isset( $groups[ $slug ] ) ) {
		return false;
	}
	$group = $groups[ $slug ];
	$ok    = true;
	foreach ( vance_hub_data_keys( $uid, $slug ) as $key ) {
		delete_user_meta( $uid, $key );
		if ( vance_hub_data_filled( get_user_meta( $uid, $key, true ) ) ) {
			$ok = false;
		}
	}
	// Withdraw the agreement. The data went first: an agreement with no data is
	// harmless, data with no agreement is not.
	if ( $ok && null !== $group['consent'] && function_exists( 'vance_hub_checkin_consent_keys' ) ) {
		$flag = vance_hub_checkin_consent_keys( $group['consent'] )[0];
		if ( get_user_meta( $uid, $flag, true ) ) {
			delete_user_meta( $uid, $flag );
			if ( get_user_meta( $uid, $flag, true ) ) {
				$ok = false;
			} else {
				update_user_meta( $uid, $flag . '_withdrawn_at', current_time( 'mysql' ) );
			}
		}
	}
	return $ok;
}

/* =========================================================================
 * POST handler
 * ====================================================================== */

/**
 * Delete one group, or all, for the logged-in member. The form posts to the
 * dashboard page itself; this redirects back, so a reload does not send it again.
 *
 * Result codes (query arg vance_data): deleted, nothing, confirm, kept, expired, invalid.
 */
function vance_hub_data_handle_post() {
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['vance_hub_data_nonce'] ) ) {
		return;
	}
	if ( ! is_user_logged_in() || ! is_string( $_POST['vance_hub_data_nonce'] ) ) {
		return;
	}
	// The control only exists with the hub dashboard on, so nothing else posts here.
	if ( ! function_exists( 'vance_hub_dashboard_active' ) || ! vance_hub_dashboard_active() ) {
		return;
	}
	$back = remove_query_arg( 'vance_data' );
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vance_hub_data_nonce'] ) ), 'vance_hub_manage_data' ) ) {
		wp_safe_redirect( add_query_arg( 'vance_data', 'expired', $back ) );
		exit;
	}

	$uid    = get_current_user_id();
	$do     = isset( $_POST['vance_hub_data_do'] ) && is_string( $_POST['vance_hub_data_do'] ) ? sanitize_key( wp_unslash( $_POST['vance_hub_data_do'] ) ) : '';
	$groups = vance_hub_data_groups();
	$result = 'invalid';

	if ( 'all' !== $do && ! isset( $groups[ $do ] ) ) {
		$result = 'invalid';
	} elseif ( empty( $_POST['vhd_confirm'] ) || '1' !== $_POST['vhd_confirm'] ) {
		// Nothing is deleted without the tick.
		$result = 'confirm';
	} else {
		$slugs = 'all' === $do ? array_keys( $groups ) : array( $do );
		// The same lock as the check-in and flare plan saves: a save that runs at
		// the same time cannot write data back after the delete.
		$locked = function_exists( 'vance_hub_checkin_lock' ) ? vance_hub_checkin_lock( $uid ) : true;
		if ( ! $locked ) {
			$result = 'kept';
		} else {
			$result  = 'nothing';
			$failed  = false;
			foreach ( $slugs as $slug ) {
				if ( ! vance_hub_data_has( $uid, $slug ) ) {
					continue;
				}
				$result = 'deleted';
				if ( ! vance_hub_data_erase( $uid, $slug ) ) {
					$failed = true;
				}
			}
			if ( $failed ) {
				$result = 'kept';
			}
			if ( function_exists( 'vance_hub_checkin_unlock' ) ) {
				vance_hub_checkin_unlock( $uid );
			}
		}
	}

	wp_safe_redirect( add_query_arg( 'vance_data', $result, $back ) );
	exit;
}
add_action( 'template_redirect', 'vance_hub_data_handle_post' );

/* =========================================================================
 * Render
 * ====================================================================== */

/**
 * The flash messages.
 *
 * @return string[] Code => text.
 */
function vance_hub_data_messages() {
	return array(
		'deleted' => __( 'Your data is deleted.', 'vance-health-hub' ),
		'nothing' => __( 'There was nothing to delete.', 'vance-health-hub' ),
		'confirm' => __( 'Not deleted. Tick the box to confirm, then send the form again.', 'vance-health-hub' ),
		'kept'    => __( 'Not completed. The Hub could not delete all of it. Try again later.', 'vance-health-hub' ),
		'expired' => __( 'Not deleted. This page was open for too long. Send the form again.', 'vance-health-hub' ),
		'invalid' => __( 'Not deleted. Send the form again.', 'vance-health-hub' ),
	);
}

/**
 * One delete form.
 *
 * @param string $do      Group slug or 'all'.
 * @param string $button  Button text.
 * @param string $id      Id of the confirm box.
 * @param string $nonce   Nonce value.
 * @return string
 */
function vance_hub_data_form_html( $do, $button, $id, $nonce ) {
	$html  = '<form class="vhd-manage__form" method="post" action="">';
	$html .= '<input type="hidden" name="vance_hub_data_nonce" value="' . esc_attr( $nonce ) . '">';
	$html .= '<input type="hidden" name="vance_hub_data_do" value="' . esc_attr( $do ) . '">';
	$html .= '<label class="vhd-manage__confirm" for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="vhd_confirm" value="1" required> <span>' . esc_html__( 'I understand. I cannot get it back.', 'vance-health-hub' ) . '</span></label>';
	$html .= '<button type="submit" class="vhd-btn vhd-btn--danger">' . esc_html( $button ) . '</button>';
	$html .= '</form>';
	return $html;
}

/**
 * The "Manage my data" panel.
 *
 * @param int    $uid   Member.
 * @param string $flash Result code from the last delete, or ''.
 * @return string
 */
function vance_hub_data_panel_html( $uid, $flash = '' ) {
	$groups   = vance_hub_data_groups();
	$messages = vance_hub_data_messages();
	$nonce    = wp_create_nonce( 'vance_hub_manage_data' );

	$html  = '<div class="vhd"><section class="vhd-card vhd-manage" aria-labelledby="vhd-manage-title"><div class="vhd-card__head">';
	$html .= '<h2 id="vhd-manage-title" class="vhd-h3">' . esc_html__( 'Manage my data', 'vance-health-hub' ) . '</h2></div>';
	$html .= '<p class="vhd-small">' . esc_html__( 'You choose what the Hub keeps. Delete one group, or everything below. A deleted group cannot be restored. Your account, uploaded documents, notes, saved chats and messages are not changed.', 'vance-health-hub' ) . '</p>';
	if ( isset( $messages[ $flash ] ) ) {
		$ok    = in_array( $flash, array( 'deleted', 'nothing' ), true );
		$html .= '<p class="vhd-flash' . ( $ok ? '' : ' vhd-flash--warn' ) . '" role="status">' . esc_html( $messages[ $flash ] ) . '</p>';
	}

	$any   = false;
	$html .= '<ul class="vhd-manage__list">';
	foreach ( $groups as $slug => $group ) {
		$has = vance_hub_data_has( $uid, $slug );
		$any = $any || $has;
		$html .= '<li class="vhd-manage__row"><div class="vhd-manage__what">';
		$html .= '<strong>' . esc_html( $group['label'] ) . '</strong>';
		$html .= '<span class="vhd-manage__status' . ( $has ? ' is-saved' : '' ) . '">' . esc_html( $has ? __( 'Saved', 'vance-health-hub' ) : __( 'Nothing saved', 'vance-health-hub' ) ) . '</span>';
		$html .= '<span class="vhd-small">' . esc_html( $group['text'] ) . '</span></div>';
		if ( $has ) {
			$html .= vance_hub_data_form_html( $slug, __( 'Delete', 'vance-health-hub' ), 'vhd-data-' . $slug, $nonce );
		}
		$html .= '</li>';
	}
	$html .= '</ul>';

	if ( $any ) {
		$html .= '<div class="vhd-manage__all"><strong>' . esc_html__( 'Delete everything above', 'vance-health-hub' ) . '</strong>';
		$html .= vance_hub_data_form_html( 'all', __( 'Delete all my health data', 'vance-health-hub' ), 'vhd-data-all', $nonce ) . '</div>';
	}
	$html .= '</section></div>';
	return $html;
}

/**
 * The last result code from the address, limited to the known codes.
 *
 * @return string '' when there is none.
 */
function vance_hub_data_flash_from_request() {
	if ( ! isset( $_GET['vance_data'] ) || ! is_string( $_GET['vance_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- selects one fixed message, changes nothing.
		return '';
	}
	$code = sanitize_key( wp_unslash( $_GET['vance_data'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( vance_hub_data_messages()[ $code ] ) ? $code : '';
}
