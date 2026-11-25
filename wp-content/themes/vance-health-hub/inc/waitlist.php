<?php
/**
 * B-Vance waitlist (/waitlist/): sign-up handler.
 *
 * Rules this file keeps:
 * - One purpose. The person asks to hear about the B-Vance launch. Nothing else.
 * - No health question, no condition, no symptom. Fields: email, first name
 *   (optional), one consent box.
 * - Product emails are a separate consent from the newsletter. This handler
 *   never adds a person to the newsletter list or tag, and the page offers no
 *   newsletter box. Newsletter campaigns must target their own list or tag,
 *   never "all subscribed contacts".
 * - The page is not linked from the tools or the condition articles.
 * - The wording the person agreed to is stored with the time, as evidence.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The consent wording. DRAFT: change it only with regulatory and legal review,
 * and raise the version number when it changes.
 *
 * @return string
 */
function vance_waitlist_consent_text() {
	return __( 'I agree that Vance Medical Foods Ltd may email me about the launch of B-Vance, and only about that. I can unsubscribe at any time. This is separate from any newsletter.', 'vance-health-hub' );
}

/**
 * Version of the consent wording, stored with each record.
 *
 * @return string
 */
function vance_waitlist_consent_version() {
	// Each stored row holds only this id. When the wording changes, raise the id
	// and KEEP the old wording in version control, as the evidence for old rows.
	return 'draft-1';
}

/**
 * The most consent rows kept. A request past this is refused.
 *
 * @return int
 */
function vance_waitlist_max_records() {
	return 5000;
}

/**
 * True for a source label that may become a CRM tag. Others are not tagged, so
 * a forged cookie cannot create new tags.
 *
 * @param string $source Cleaned source label.
 * @return bool
 */
function vance_waitlist_known_source( $source ) {
	return in_array( $source, array( 'instagram', 'tiktok', 'youtube', 'facebook', 'email' ), true );
}

/**
 * Handle the sign-up.
 */
function vance_waitlist_signup_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vance_waitlist' ) ) {
		wp_send_json_error( array( 'message' => __( 'This page is out of date. Reload it and try again.', 'vance-health-hub' ) ), 403 );
	}
	if ( ! empty( $_POST['website'] ) ) {
		wp_send_json_success( array( 'message' => __( 'Thank you.', 'vance-health-hub' ) ) ); // Honeypot: drop silently.
	}
	if ( function_exists( 'vance_rate_limit' ) && ! vance_rate_limit( 'waitlist', 5, 900 ) ) {
		wp_send_json_error( array( 'message' => __( 'Too many tries. Please wait a few minutes.', 'vance-health-hub' ) ), 429 );
	}

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$first = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
	$first = function_exists( 'mb_substr' ) ? mb_substr( $first, 0, 60 ) : substr( $first, 0, 60 );

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'vance-health-hub' ) ), 422 );
	}
	if ( empty( $_POST['consent'] ) || '1' !== (string) wp_unslash( $_POST['consent'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Please tick the box to agree. We cannot add you without it.', 'vance-health-hub' ) ), 422 );
	}

	$source  = '';
	$current = function_exists( 'vance_attribution_current' ) ? vance_attribution_current() : array();
	if ( ! empty( $current['utm_source'] ) ) {
		$source = $current['utm_source'];
	}

	// Evidence of consent, kept whether or not FluentCRM is active. One row for
	// each address (a repeat updates it), the wording held as a version id, and
	// the list capped, so a flood of requests cannot grow the option without limit.
	// "confirmed" stays false: nobody has proved they own the address.
	$crm_state = 'none';
	$records   = get_option( 'vance_waitlist_consents', array() );
	if ( ! is_array( $records ) ) {
		$records = array();
	}
	$row = array(
		'email'     => $email,
		'at'        => gmdate( 'c' ),
		'version'   => vance_waitlist_consent_version(),
		'source'    => $source,
		'confirmed' => false,
	);
	$found = false;
	foreach ( $records as $i => $existing_row ) {
		if ( isset( $existing_row['email'] ) && 0 === strcasecmp( $existing_row['email'], $email ) ) {
			$records[ $i ] = $row;
			$found         = true;
			break;
		}
	}
	if ( ! $found ) {
		if ( count( $records ) >= vance_waitlist_max_records() ) {
			wp_send_json_error( array( 'message' => __( 'The list is busy. Please try again later.', 'vance-health-hub' ) ), 503 );
		}
		$records[] = $row;
	}
	update_option( 'vance_waitlist_consents', $records, false );

	// FluentCRM. A NEW address becomes a "pending" contact (double opt-in). An
	// address that is already a contact is NOT touched: its status is not changed
	// and it is not added to the waitlist list or tag from here, because nobody
	// has proved they own it. Such a person is added by hand after they confirm.
	if ( function_exists( 'FluentCrmApi' ) ) {
		try {
			$contacts = FluentCrmApi( 'contacts' );
			if ( ! $contacts->getContact( $email ) ) {
				$tags = array( 'waitlist-b-vance' );
				if ( '' !== $source && vance_waitlist_known_source( $source ) ) {
					$tags[] = 'utm-' . $source;
				}
				$contacts->createOrUpdate( array(
					'email'      => $email,
					'first_name' => $first,
					'status'     => 'pending',
					'tags'       => $tags,
					'lists'      => array( 'B-Vance Waitlist' ),
					'source'     => 'waitlist',
				) );
				$crm_state = 'pending';
			} else {
				$crm_state = 'existing-not-attached';
			}
		} catch ( \Throwable $e ) {
			// The consent record above is kept. The person sees no error. We log it.
			error_log( 'vance waitlist: FluentCRM call failed: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
	unset( $crm_state );

	// The same answer for a new and an existing address.
	wp_send_json_success( array( 'message' => __( 'Thank you. If this address is new to us, we will email you to confirm it.', 'vance-health-hub' ) ) );
}
add_action( 'wp_ajax_nopriv_vance_waitlist_signup', 'vance_waitlist_signup_ajax' );
add_action( 'wp_ajax_vance_waitlist_signup', 'vance_waitlist_signup_ajax' );

/**
 * Personal data export: the consent records held for an address.
 *
 * @param string $email Address.
 * @param int    $page  Page.
 * @return array
 */
function vance_waitlist_export( $email, $page = 1 ) {
	unset( $page );
	$data    = array();
	$records = get_option( 'vance_waitlist_consents', array() );
	foreach ( is_array( $records ) ? $records : array() as $row ) {
		if ( isset( $row['email'] ) && 0 === strcasecmp( $row['email'], $email ) ) {
			$data[] = array(
				'group_id'    => 'vance-waitlist',
				'group_label' => __( 'B-Vance waitlist', 'vance-health-hub' ),
				'item_id'     => 'vance-waitlist-' . md5( $row['at'] . $row['email'] ),
				'data'        => array(
					array( 'name' => __( 'Agreed at', 'vance-health-hub' ), 'value' => (string) $row['at'] ),
					array( 'name' => __( 'Wording version agreed to', 'vance-health-hub' ), 'value' => (string) $row['version'] ),
					array( 'name' => __( 'Source', 'vance-health-hub' ), 'value' => (string) $row['source'] ),
				),
			);
		}
	}
	return array( 'data' => $data, 'done' => true );
}

/**
 * Personal data erase: the consent records held for an address.
 *
 * @param string $email Address.
 * @param int    $page  Page.
 * @return array
 */
function vance_waitlist_erase( $email, $page = 1 ) {
	unset( $page );
	$records = get_option( 'vance_waitlist_consents', array() );
	$kept    = array();
	$removed = false;
	foreach ( is_array( $records ) ? $records : array() as $row ) {
		if ( isset( $row['email'] ) && 0 === strcasecmp( $row['email'], $email ) ) {
			$removed = true;
			continue;
		}
		$kept[] = $row;
	}
	if ( $removed ) {
		update_option( 'vance_waitlist_consents', $kept, false );
	}
	return array( 'items_removed' => $removed, 'items_retained' => false, 'messages' => array(), 'done' => true );
}

add_filter( 'wp_privacy_personal_data_exporters', function ( $exporters ) {
	$exporters['vance-waitlist'] = array(
		'exporter_friendly_name' => __( 'B-Vance waitlist', 'vance-health-hub' ),
		'callback'               => 'vance_waitlist_export',
	);
	return $exporters;
} );
add_filter( 'wp_privacy_personal_data_erasers', function ( $erasers ) {
	$erasers['vance-waitlist'] = array(
		'eraser_friendly_name' => __( 'B-Vance waitlist', 'vance-health-hub' ),
		'callback'             => 'vance_waitlist_erase',
	);
	return $erasers;
} );
