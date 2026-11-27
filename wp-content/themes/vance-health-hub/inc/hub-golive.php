<?php
/**
 * VanceHubClub scheduled go-live.
 *
 * An administrator can pick a date and a time in the Customizer (section "On or
 * off"). When that time comes, the site layout changes to VanceHubClub by
 * itself. Nothing else changes: the daily check-in and the flare plan hold
 * health data, so they keep their own saved or default state.
 *
 * The date is a local date and time in the SITE time zone, saved as
 * YYYY-MM-DDTHH:MM in the theme mod vance_hub_golive_at. An empty value means
 * no planned change.
 *
 * Two triggers run the same function, vance_hub_golive_run():
 *
 *   - init, priority 20: every request. With no date saved, the cost is one
 *     theme mod read. This is the trigger that decides.
 *   - one WP-Cron event, vance_hub_golive_event, scheduled when the Customizer
 *     is saved. It makes the change happen on a site with little traffic.
 *
 * A page that a page cache serves does not reach WordPress, so the run cannot
 * start from that page. When it switches, the run purges the LiteSpeed Cache
 * plugin only. The hosting cache (Hostinger) is separate and this code cannot
 * purge it. An administrator must purge it by hand in the hosting panel after
 * the go-live time. Until then, or until that cache expires, visitors can see
 * the classic layout.
 *
 * The pure functions (vance_hub_sanitize_datetime, vance_hub_golive_timestamp,
 * vance_hub_golive_decide) read no theme mod, so tests can call them.
 *
 * Needs inc/hub-layout.php (vance_hub_saved_layout, vance_hub_sanitize_layout,
 * the section ids). Loaded from functions.php directly after
 * inc/hub-home-settings.php. To back the feature out, delete that require line.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * Pure functions
 * ====================================================================== */

/**
 * Sanitise the go-live setting.
 *
 * Accepts YYYY-MM-DDTHH:MM and YYYY-MM-DDTHH:MM:SS. The seconds are dropped.
 * The date must be a real date, the hour 0 to 23, the minute 0 to 59 and the
 * seconds 0 to 59.
 *
 * @param mixed $value Raw value.
 * @return string YYYY-MM-DDTHH:MM, or '' when the value is not a real date and time.
 */
function vance_hub_sanitize_datetime( $value ) {
	$value = is_string( $value ) ? trim( $value ) : '';
	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(?::(\d{2}))?$/', $value, $m ) ) {
		return '';
	}
	if ( ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
		return '';
	}
	if ( (int) $m[4] > 23 || (int) $m[5] > 59 ) {
		return '';
	}
	if ( isset( $m[6] ) && (int) $m[6] > 59 ) {
		return '';
	}
	return $m[1] . '-' . $m[2] . '-' . $m[3] . 'T' . $m[4] . ':' . $m[5];
}

/**
 * The Unix timestamp of a saved go-live value.
 *
 * The value is a local time in the given zone. A local time that does not exist
 * (the hour that daylight saving skips) moves forward by that hour, as PHP does.
 *
 * @param mixed        $value Saved value, YYYY-MM-DDTHH:MM.
 * @param DateTimeZone $tz    The zone of the value (the site time zone).
 * @return int Timestamp, or 0 when the value is not valid.
 */
function vance_hub_golive_timestamp( $value, DateTimeZone $tz ) {
	$value = vance_hub_sanitize_datetime( $value );
	if ( '' === $value ) {
		return 0;
	}
	// The leading ! sets every field that the format does not name (the
	// seconds) to zero. Without it PHP would use the seconds of the current time.
	$date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $value, $tz );
	return $date ? (int) $date->getTimestamp() : 0;
}

/**
 * Decide what a run does.
 *
 * @param mixed        $value  Saved go-live value.
 * @param int          $now    Now, as a Unix timestamp.
 * @param DateTimeZone $tz     The site time zone.
 * @param string       $layout The saved layout, 'classic', 'members' or 'hub'.
 *                             'members' (VanceHubClub for logged-in members only)
 *                             counts as 'classic' here: at the time it changes to
 *                             'hub' for everyone.
 * @return string One of:
 *                'none'   no valid date is saved;
 *                'wait'   the time is in the future;
 *                'switch' the time is reached and the layout is not 'hub';
 *                'clear'  the layout is already 'hub', so the date has no more use
 *                         (the time may or may not be reached).
 */
function vance_hub_golive_decide( $value, $now, DateTimeZone $tz, $layout ) {
	$timestamp = vance_hub_golive_timestamp( $value, $tz );
	if ( 0 === $timestamp ) {
		return 'none';
	}
	if ( 'hub' === $layout ) {
		return 'clear';
	}
	return (int) $now >= $timestamp ? 'switch' : 'wait';
}

/* =========================================================================
 * The run
 * ====================================================================== */

/**
 * Run the go-live check, and switch the site to VanceHubClub when the time is
 * reached.
 *
 * Safe to run two times in a row, and from two requests at once. Every write is
 * idempotent: it sets the layout to 'hub', removes the date, or sets a time stamp.
 * Once the first run has removed the date, a second run decides 'none'. A run
 * that starts before the first has removed the date writes the same values again.
 * The layout is written first and the date second. A request that stops between
 * the two leaves layout 'hub' and a date, and the next run decides 'clear' and
 * removes the date. The only repeated effects of two overlapping runs are the
 * cache purge and the vance_hub_went_live action, and both are harmless twice.
 *
 * It writes nothing else. In particular it never turns on the daily check-in or
 * the flare plan (vance_hub_dash_checkin, vance_hub_dash_flare_plan): they hold
 * health data and keep their own state.
 *
 * @return string The decision: 'none', 'wait', 'switch' or 'clear'. 'none' also
 *                when the run is skipped in the Customizer preview.
 */
function vance_hub_golive_run() {
	// THE MOST IMPORTANT LINE OF THE FEATURE. In the Customizer preview a theme
	// mod reads as its UNSAVED, previewed value. An administrator who types a
	// date and does not publish it would otherwise make a real, saved switch.
	// The preview never changes anything that is saved.
	if ( is_customize_preview() ) {
		return 'none';
	}

	// One theme mod read when no date is saved: the usual case returns here.
	$value = vance_hub_sanitize_datetime( vance_get_theme_mod( 'vance_hub_golive_at', '' ) );
	if ( '' === $value ) {
		return 'none';
	}

	$decision = vance_hub_golive_decide( $value, time(), wp_timezone(), vance_hub_saved_layout() );

	if ( 'switch' === $decision ) {
		set_theme_mod( 'vance_home_layout', 'hub' );
		remove_theme_mod( 'vance_hub_golive_at' );
		set_theme_mod( 'vance_hub_golive_done_at', time() );
		// Purge the LiteSpeed Cache plugin, so its visitors get the new layout.
		// Nothing listens when that plugin is not active, and that is harmless.
		// This does NOT purge the hosting cache (Hostinger): an administrator
		// purges that by hand in the hosting panel.
		do_action( 'litespeed_purge_all' );
		do_action( 'vance_hub_went_live' );
	} elseif ( 'clear' === $decision ) {
		remove_theme_mod( 'vance_hub_golive_at' );
	}

	return $decision;
}
add_action( 'init', 'vance_hub_golive_run', 20 );
add_action( 'vance_hub_golive_event', 'vance_hub_golive_run' );

/**
 * Plan the WP-Cron event again after the Customizer saves.
 *
 * Removes any event that is planned, then plans one at the saved time when that
 * time is valid and in the future. The event makes the switch happen on a site
 * with little traffic. The init trigger still decides, so a missed event does
 * not stop the switch.
 */
function vance_hub_golive_reschedule() {
	wp_clear_scheduled_hook( 'vance_hub_golive_event' );

	$value     = vance_hub_sanitize_datetime( vance_get_theme_mod( 'vance_hub_golive_at', '' ) );
	$timestamp = vance_hub_golive_timestamp( $value, wp_timezone() );
	if ( $timestamp > time() ) {
		wp_schedule_single_event( $timestamp, 'vance_hub_golive_event' );
	}
}
add_action( 'customize_save_after', 'vance_hub_golive_reschedule' );

/* =========================================================================
 * Customizer
 * ====================================================================== */

/**
 * Register the go-live setting and its control, in the "On or off" section.
 *
 * A function of its own, hooked on customize_register after
 * vance_hub_customize_register(), so the section exists when this runs.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vance_hub_golive_customize_register( $wp_customize ) {
	$wp_customize->add_setting( 'vance_hub_golive_at', array(
		'default'           => '',
		'sanitize_callback' => 'vance_hub_sanitize_datetime',
	) );
	$wp_customize->add_control( 'vance_hub_golive_at', array(
		'label'       => __( 'Go-live date and time', 'vance-health-hub' ),
		/* translators: 1: site time zone name, 2: number of the check-in section, 3: number of the flare plan section. */
		'description' => sprintf( __( 'The site changes to VanceHubClub at this time. The time is in the site time zone (%1$s). If the layout is "VanceHubClub for members only", it changes to VanceHubClub for all at this time. The change is saved, and this field then becomes empty. This change does not turn on the daily check-in or the flare plan. You turn them on in sections %2$d and %3$d. Leave this field empty for no planned change. A time that is already past changes the site on the next request, so check the year and the time before you publish. The change runs on the first request that reaches WordPress after the time. The theme then purges the LiteSpeed cache. The hosting cache is separate. Purge it by hand in the hosting panel after the go-live time, or visitors can see the classic layout until that cache expires.', 'vance-health-hub' ), esc_html( wp_timezone_string() ), vance_hub_customizer_section_number( 'vance_hub_sec_checkin' ), vance_hub_customizer_section_number( 'vance_hub_sec_flare' ) ),
		'section'     => 'vance_home_layout',
		'type'        => 'datetime-local',
		'priority'    => 20,
	) );
}
add_action( 'customize_register', 'vance_hub_golive_customize_register' );
