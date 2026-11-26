<?php
/**
 * Sign-up source (UTM) and tool events, for the organic social plan.
 *
 * Two jobs, both privacy-safe by design:
 *
 * 1. SOURCE. A visit with utm_* in the address sets a first-party cookie
 *    (`vance_utm`, 30 days, last touch wins). When a visitor registers, by any
 *    route, the source is copied to user meta and, where FluentCRM syncs the
 *    contact, to a tag and a custom value. The cookie is set only when the
 *    visitor has consented to statistics (Complianz), because it measures
 *    marketing. Without consent no source is stored: the sign-up shows no source.
 *
 * 2. EVENTS. assets/js/vance-events.js pushes `vance_tool_start` and
 *    `vance_tool_complete` to the dataLayer, with a tool name from a fixed list.
 *    It never carries a question, an answer, a result or a condition. A start
 *    and a completed use are different events.
 *
 * Nothing here reads or sends health information. Do not add any.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The campaign keys kept, in order.
 *
 * @return string[]
 */
function vance_attribution_keys() {
	return array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content' );
}

/**
 * One campaign value, reduced to a short safe token.
 *
 * Lower case, letters, digits, dot, underscore and hyphen only, 40 characters
 * at most. A value is a label, never free text.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function vance_attribution_clean( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$value = strtolower( trim( $value ) );
	$value = preg_replace( '/[^a-z0-9_.-]/', '', $value );
	return substr( (string) $value, 0, 40 );
}

/**
 * The cleaned campaign values of an array (a query string or a cookie).
 *
 * @param array $input Source array.
 * @return array<string,string> Only the keys that hold a value.
 */
function vance_attribution_extract( $input ) {
	$out = array();
	if ( ! is_array( $input ) ) {
		return $out;
	}
	foreach ( vance_attribution_keys() as $key ) {
		if ( isset( $input[ $key ] ) ) {
			$clean = vance_attribution_clean( wp_unslash( $input[ $key ] ) );
			if ( '' !== $clean ) {
				$out[ $key ] = $clean;
			}
		}
	}
	return $out;
}

/**
 * True when the visitor allows the source cookie.
 *
 * Complianz decides when it is active. With no consent plugin the cookie is
 * allowed, because the site then has no consent banner to honour.
 *
 * @return bool
 */
function vance_attribution_consent() {
	if ( function_exists( 'cmplz_has_consent' ) ) {
		return (bool) cmplz_has_consent( 'statistics' );
	}
	return true;
}

/**
 * Set the source cookie from the address of a front-end page view.
 */
function vance_attribution_capture() {
	if ( is_admin() || is_user_logged_in() || headers_sent() ) {
		return;
	}
	if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
		return;
	}
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'GET';
	if ( 'GET' !== $method ) {
		return;
	}
	$values = vance_attribution_extract( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only campaign labels, each reduced to a short token.
	if ( empty( $values['utm_source'] ) || ! vance_attribution_consent() ) {
		return;
	}
	setcookie( 'vance_utm', http_build_query( $values ), array(
		'expires'  => time() + 30 * DAY_IN_SECONDS,
		'path'     => '/',
		'secure'   => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	) );
	$_COOKIE['vance_utm'] = http_build_query( $values );
}
add_action( 'init', 'vance_attribution_capture', 9 );

/**
 * The visitor's stored source, or an empty array.
 *
 * @return array<string,string>
 */
function vance_attribution_current() {
	if ( empty( $_COOKIE['vance_utm'] ) || ! is_string( $_COOKIE['vance_utm'] ) ) {
		return array();
	}
	$parsed = array();
	parse_str( wp_unslash( $_COOKIE['vance_utm'] ), $parsed );
	return vance_attribution_extract( $parsed );
}

/**
 * Copy the source to the new member. Runs for every sign-up route, because every
 * route ends in wp_create_user() or wp_insert_user().
 *
 * @param int $user_id New user ID.
 */
function vance_attribution_record_signup( $user_id ) {
	$values = vance_attribution_current();
	if ( empty( $values['utm_source'] ) ) {
		return;
	}
	foreach ( $values as $key => $value ) {
		update_user_meta( $user_id, '_vance_signup_' . substr( $key, 4 ), $value );
	}
}
add_action( 'user_register', 'vance_attribution_record_signup', 30 );

/**
 * A member's recorded source label ('' when none).
 *
 * @param int $user_id User ID.
 * @return string
 */
function vance_attribution_user_source( $user_id ) {
	$source = get_user_meta( (int) $user_id, '_vance_signup_source', true );
	return is_string( $source ) ? vance_attribution_clean( $source ) : '';
}

/**
 * Add the source to the FluentCRM contact fields built by
 * vance_sync_fluentcrm_contact().
 *
 * @param array $contact Contact data for createOrUpdate().
 * @param int   $user_id User ID.
 * @return array
 */
function vance_attribution_crm_contact( $contact, $user_id ) {
	$source = vance_attribution_user_source( $user_id );
	if ( '' === $source || ! is_array( $contact ) ) {
		return $contact;
	}
	if ( ! isset( $contact['tags'] ) || ! is_array( $contact['tags'] ) ) {
		$contact['tags'] = array();
	}
	$contact['tags'][] = 'utm-' . $source;
	if ( ! isset( $contact['custom_values'] ) || ! is_array( $contact['custom_values'] ) ) {
		$contact['custom_values'] = array();
	}
	$contact['custom_values']['utm_source'] = $source;
	return $contact;
}
add_filter( 'vance_fluentcrm_contact', 'vance_attribution_crm_contact', 10, 2 );

/**
 * The tool names the events script accepts. Keep in step with
 * assets/js/vance-events.js.
 *
 * @return string[]
 */
function vance_event_tools() {
	return array( 'ask_ai', 'meal_planner', 'self_assessment' );
}

/**
 * Load the events script on every front-end page. It is a few hundred bytes and
 * sends nothing by itself.
 */
function vance_events_enqueue() {
	$file = get_template_directory() . '/assets/js/vance-events.js';
	wp_enqueue_script(
		'vance-events',
		get_template_directory_uri() . '/assets/js/vance-events.js',
		array(),
		@filemtime( $file ) ?: '1.0.0',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'vance_events_enqueue', 5 );
