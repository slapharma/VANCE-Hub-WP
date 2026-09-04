<?php
/**
 * Discount dashboard AJAX — save/unsave, application status, and the Access
 * Folder checklist. Mirrors inc/dashboard-functions.php's
 * vance_dashboard_toggle_bookmark() pattern, with one deliberate difference
 * per docs/DISCOUNTS_TOOL_PLAN.md §5: no `wp_ajax_nopriv_*` hook on any of
 * these three. A logged-out click on Save opens the register modal
 * client-side (assets/js/discounts.js) and never reaches the server, so
 * there is no legitimate anonymous caller to support.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Toggle a scheme in `_sla_saved_discounts` — same shape as `_sla_reading_list`.
 */
function vance_dashboard_toggle_discount() {
	check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'Not logged in' );
	}

	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	if ( ! $post_id || 'vance_discount' !== get_post_type( $post_id ) ) {
		wp_send_json_error( 'Invalid scheme' );
	}

	$user_id = get_current_user_id();
	$saved   = get_user_meta( $user_id, '_sla_saved_discounts', true );
	$saved   = is_array( $saved ) ? $saved : array();

	if ( in_array( $post_id, $saved, true ) ) {
		$saved  = array_values( array_diff( $saved, array( $post_id ) ) );
		$action = 'removed';
	} else {
		$saved[] = $post_id;
		$action  = 'added';
	}

	update_user_meta( $user_id, '_sla_saved_discounts', $saved );

	wp_send_json_success( array( 'action' => $action, 'count' => count( $saved ) ) );
}
add_action( 'wp_ajax_vance_toggle_discount', 'vance_dashboard_toggle_discount' );

/**
 * Set a saved scheme's application status. `_sla_discount_status` is keyed by
 * post ID regardless of whether the scheme is currently in
 * `_sla_saved_discounts` — setting a status implies interest, so this also
 * adds the scheme to the saved list if it isn't there, rather than silently
 * recording a status for something the member's saved list doesn't show.
 */
function vance_dashboard_set_discount_status() {
	check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'Not logged in' );
	}

	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	$status  = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
	$note    = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';

	$allowed = array( 'interested', 'applied', 'received', 'declined' );
	if ( ! $post_id || 'vance_discount' !== get_post_type( $post_id ) || ! in_array( $status, $allowed, true ) ) {
		wp_send_json_error( 'Invalid request' );
	}

	$user_id  = get_current_user_id();
	$statuses = get_user_meta( $user_id, '_sla_discount_status', true );
	$statuses = is_array( $statuses ) ? $statuses : array();

	$statuses[ $post_id ] = array( 'status' => $status, 'ts' => time(), 'note' => $note );
	update_user_meta( $user_id, '_sla_discount_status', $statuses );

	$saved = get_user_meta( $user_id, '_sla_saved_discounts', true );
	$saved = is_array( $saved ) ? $saved : array();
	if ( ! in_array( $post_id, $saved, true ) ) {
		$saved[] = $post_id;
		update_user_meta( $user_id, '_sla_saved_discounts', $saved );
	}

	wp_send_json_success( array( 'status' => $status ) );
}
add_action( 'wp_ajax_vance_set_discount_status', 'vance_dashboard_set_discount_status' );

/**
 * Save the whole Access Folder in one call — a form submit, not a per-toggle
 * autosave, because the folder is a batch of ~20 checkboxes plus a region
 * select and toggling one at a time would be twenty round trips for one
 * screen. `assets/js/discounts.js` still fires this on every toggle
 * (docs/DISCOUNTS_UI_SPEC.md §6 asks for autosave-on-toggle UX); the request
 * just always carries the form's full current state rather than a diff, so
 * either firing pattern lands on the same stored shape.
 */
function vance_dashboard_save_access_folder() {
	check_ajax_referer( 'vance_dashboard_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'Not logged in' );
	}

	$known_signals = array_keys( vance_discount_signal_labels() );
	$submitted     = isset( $_POST['signals'] ) ? (array) wp_unslash( $_POST['signals'] ) : array();
	$submitted     = array_map( 'sanitize_key', $submitted );

	$folder = array();
	foreach ( $known_signals as $key ) {
		$folder[ $key ] = in_array( $key, $submitted, true );
	}

	$region = isset( $_POST['region'] ) ? sanitize_key( wp_unslash( $_POST['region'] ) ) : '';
	if ( in_array( $region, array( 'uk', 'england', 'wales', 'scotland', 'ni' ), true ) ) {
		$folder['region'] = $region;
	}

	update_user_meta( get_current_user_id(), '_sla_access_folder', $folder );

	$match = function_exists( 'vance_discount_match' ) ? vance_discount_match( get_current_user_id() ) : array( 'likely' => array() );

	wp_send_json_success( array( 'likely_count' => count( $match['likely'] ) ) );
}
add_action( 'wp_ajax_vance_save_access_folder', 'vance_dashboard_save_access_folder' );
