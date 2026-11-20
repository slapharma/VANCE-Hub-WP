<?php
/**
 * VanceHubClub member dashboard: member number, results chart, weekly check-in.
 *
 * Three optional blocks on the hub dashboard home (inc/hub-dashboard.php). Each
 * has its own switch in Appearance -> Customize -> Home layout, and each switch
 * is off by default. A block shows only when its switch is on AND the hub
 * dashboard home is active.
 *
 * Data rule (same as the rest of the hub): no invented number. The chart draws
 * only saved results, and it does not render with fewer than two of them.
 *
 * What this file WRITES (hub-dashboard.php itself only reads):
 *   _sla_member_code              one random code per member, made on first view
 *   _sla_weekly_check_in_history  check-in rows, through vance_hub_checkin_append()
 *   _sla_consent_checkin          '1' while the member agrees to the check-in
 *   _sla_consent_checkin_at       when the member agreed
 *   _sla_consent_checkin_text     the wording the member agreed to
 *   _sla_consent_checkin_withdrawn_at  when the member withdrew (delete)
 *
 * The meta keys keep the legacy `_sla_` prefix on purpose (see CLAUDE.md).
 *
 * Layout of this file: pure helpers first (no WordPress state), then the
 * switches, then storage, then the POST handler, then data, then render.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * Pure helpers
 * ====================================================================== */

/**
 * Characters a member code can hold. No 0, 1, I or O: a member can read the
 * code aloud or type it with no doubt about a character.
 *
 * @return string 32 characters.
 */
function vance_hub_member_code_alphabet() {
	return '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
}

/**
 * Make one member code.
 *
 * @param callable $rand function( int $min, int $max ): int. random_int in production.
 * @return string 'VHC-' and six characters.
 */
function vance_hub_member_code_make( $rand ) {
	$alphabet = vance_hub_member_code_alphabet();
	$last     = strlen( $alphabet ) - 1;
	$code     = '';
	for ( $i = 0; $i < 6; $i++ ) {
		$code .= $alphabet[ max( 0, min( $last, (int) call_user_func( $rand, 0, $last ) ) ) ];
	}
	return 'VHC-' . $code;
}

/**
 * True when a value is a well-formed member code.
 *
 * @param mixed $code Stored value.
 * @return bool
 */
function vance_hub_member_code_valid( $code ) {
	return is_string( $code ) && 1 === preg_match( '/^VHC-[2-9A-HJ-NP-Z]{6}$/D', $code );
}

/**
 * The answer keys of the weekly check-in. Answers are stored under these keys,
 * so a change to the question wording in Customize does not break the history.
 *
 * @return string[]
 */
function vance_hub_checkin_keys() {
	return array( 'pain', 'bowel', 'tired' );
}

/**
 * The highest answer on the check-in scale (0 = none).
 *
 * @return int
 */
function vance_hub_checkin_max() {
	return 4;
}

/**
 * Validate the posted check-in answers.
 *
 * @param mixed $raw Posted array, key => '0'..'4'.
 * @return array|null key => int for every key, or null when an answer is missing or out of range.
 */
function vance_hub_checkin_clean( $raw ) {
	if ( ! is_array( $raw ) ) {
		return null;
	}
	$clean = array();
	foreach ( vance_hub_checkin_keys() as $key ) {
		if ( ! isset( $raw[ $key ] ) ) {
			return null;
		}
		$value = $raw[ $key ];
		if ( is_int( $value ) ) {
			$value = (string) $value;
		}
		if ( ! is_string( $value ) || 1 !== strlen( $value ) || ! ctype_digit( $value ) || (int) $value > vance_hub_checkin_max() ) {
			return null;
		}
		$clean[ $key ] = (int) $value;
	}
	return $clean;
}

/**
 * True when the member can do a check-in now.
 *
 * Six days, not seven: a member who answers on Monday evening can answer again
 * on the next Monday morning.
 *
 * @param int $last_ts Time of the newest check-in, 0 when there is none.
 * @param int $now     Current time.
 * @return bool
 */
function vance_hub_checkin_due( $last_ts, $now ) {
	$last_ts = (int) $last_ts;
	return $last_ts <= 0 || ( (int) $now - $last_ts ) >= 6 * 86400;
}

/**
 * A chart series from tool-history rows.
 *
 * Rows with no numeric value under $key are left out: older saves of the
 * nutrition check hold only a text snapshot, and they have no score to plot.
 *
 * @param array  $entries     {ts, payload} rows, any order.
 * @param string $key         Payload key that holds the value.
 * @param float  $default_max Scale maximum when a row has no numeric 'maxScore'.
 * @param int    $limit       Keep the newest $limit points.
 * @return array[] {ts:int, value:float, max:float}, oldest first.
 */
function vance_hub_dashboard_series( $entries, $key, $default_max, $limit = 12 ) {
	$points = array();
	foreach ( (array) $entries as $entry ) {
		if ( ! is_array( $entry ) || ! isset( $entry['ts'], $entry['payload'] ) || ! is_array( $entry['payload'] ) ) {
			continue;
		}
		$payload = $entry['payload'];
		if ( ! is_numeric( $entry['ts'] ) || (int) $entry['ts'] <= 0 || ! isset( $payload[ $key ] ) || ! is_numeric( $payload[ $key ] ) ) {
			continue;
		}
		$max      = isset( $payload['maxScore'] ) && is_numeric( $payload['maxScore'] ) && (float) $payload['maxScore'] > 0 ? (float) $payload['maxScore'] : (float) $default_max;
		$points[] = array(
			'ts'    => (int) $entry['ts'],
			'value' => max( 0.0, min( $max, (float) $payload[ $key ] ) ),
			'max'   => $max,
		);
	}
	usort( $points, function ( $a, $b ) {
		return $a['ts'] - $b['ts'];
	} );
	$limit = max( 1, (int) $limit );
	return count( $points ) > $limit ? array_slice( $points, -$limit ) : $points;
}

/**
 * The scale maximum of a series: the largest 'max' of its points.
 *
 * @param array $points vance_hub_dashboard_series() result.
 * @return float 0 when there are no points.
 */
function vance_hub_dashboard_series_max( $points ) {
	$max = 0.0;
	foreach ( (array) $points as $point ) {
		if ( isset( $point['max'] ) && (float) $point['max'] > $max ) {
			$max = (float) $point['max'];
		}
	}
	return $max;
}

/**
 * A line chart as inline SVG. Holds numbers only, no member text.
 *
 * @param array $points Series, oldest first.
 * @return string '' with fewer than two points: one point is not a trend.
 */
function vance_hub_dashboard_chart_svg( $points ) {
	$points = array_values( (array) $points );
	$count  = count( $points );
	$max    = vance_hub_dashboard_series_max( $points );
	if ( $count < 2 || $max <= 0 ) {
		return '';
	}
	$width  = 300;
	$height = 90;
	$pad    = 10;
	$coords = array();
	foreach ( $points as $i => $point ) {
		$x        = $pad + $i * ( $width - 2 * $pad ) / ( $count - 1 );
		$y        = $height - $pad - ( (float) $point['value'] / $max ) * ( $height - 2 * $pad );
		$coords[] = array( round( $x, 1 ), round( $y, 1 ) );
	}
	$line = array();
	$dots = '';
	foreach ( $coords as $c ) {
		$line[] = $c[0] . ',' . $c[1];
		// A zero-length segment with a round cap: it stays a circle when the
		// chart stretches to the card width, which a <circle> does not.
		$dots .= 'M' . $c[0] . ',' . $c[1] . 'h0';
	}
	return '<svg class="vhd-chart__svg" viewBox="0 0 ' . $width . ' ' . $height . '" preserveAspectRatio="none" aria-hidden="true" focusable="false">'
		. '<line class="vhd-chart__axis" x1="' . $pad . '" y1="' . ( $height - $pad ) . '" x2="' . ( $width - $pad ) . '" y2="' . ( $height - $pad ) . '"/>'
		. '<line class="vhd-chart__axis" x1="' . $pad . '" y1="' . $pad . '" x2="' . ( $width - $pad ) . '" y2="' . $pad . '"/>'
		. '<polyline class="vhd-chart__line" points="' . implode( ' ', $line ) . '"/>'
		. '<path class="vhd-chart__dots" d="' . $dots . '"/></svg>';
}

/**
 * A number for display: "3", not "3.0"; "2.5" stays "2.5".
 *
 * @param float $n Value.
 * @return string
 */
function vance_hub_dashboard_num( $n ) {
	$n = (float) $n;
	return (float) (int) $n === $n ? (string) (int) $n : (string) round( $n, 1 );
}

/* =========================================================================
 * Switches
 * ====================================================================== */

/**
 * True when one feature switch is on.
 *
 * @param string $name Theme mod name.
 * @return bool
 */
function vance_hub_dash_feature_on( $name ) {
	// Under the Home layout master switch (inc/hub-layout.php). An
	// administrator's preview does not wait for the master, as with the tab
	// look (vance_hub_dashboard_tabs_active()): ?vhh_layout=hub (or preview mode)
	// reads the feature's own switch alone, and ?vhh_layout=classic turns it off.
	$query = function_exists( 'vance_hub_preview_query' ) ? vance_hub_preview_query() : '';
	if ( '' !== $query ) {
		return 'hub' === $query
			&& (bool) vance_hub_sanitize_checkbox( vance_get_theme_mod( $name, vance_hub_switch_default( $name ) ) );
	}
	return vance_hub_switch_on( $name );
}

/**
 * A Customizer text with a fallback: an empty saved value gives the default.
 *
 * @param string $name    Theme mod name.
 * @param string $default Default text.
 * @return string
 */
function vance_hub_dash_text( $name, $default ) {
	$text = trim( (string) vance_get_theme_mod( $name, $default ) );
	return '' !== $text ? $text : $default;
}

/**
 * Default wording of the check-in. Editable in Customize.
 *
 * @return array name => default text.
 */
function vance_hub_checkin_text_defaults() {
	return array(
		'vance_hub_checkin_q_pain'  => __( 'How much abdominal pain did you have this week?', 'vance-health-hub' ),
		'vance_hub_checkin_q_bowel' => __( 'How much did bowel symptoms trouble you this week?', 'vance-health-hub' ),
		'vance_hub_checkin_q_tired' => __( 'How tired were you this week?', 'vance-health-hub' ),
		'vance_hub_checkin_consent' => __( 'I agree that the Hub stores my answers in my account. I can delete them at any time.', 'vance-health-hub' ),
		'vance_hub_checkin_note'    => __( 'This is a record for you. It is not medical advice. Speak to your health team if your symptoms get worse.', 'vance-health-hub' ),
	);
}

/**
 * Customizer controls for the three features.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function vance_hub_dash_features_customize( $wp_customize ) {
	$section  = 'vance_home_layout';
	$switches = array(
		'vance_hub_dash_member_no' => array(
			__( 'Member dashboard: show the member number', 'vance-health-hub' ),
			__( 'A random code for each member (for example VHC-7K2M9Q), made the first time the member opens the dashboard. It does not show how many members the Hub has.', 'vance-health-hub' ),
		),
		'vance_hub_dash_chart'     => array(
			__( 'Member dashboard: show the results chart', 'vance-health-hub' ),
			__( 'A chart of the member\'s saved nutrition risk checks. It shows with two or more saved scores. With one, the member sees that result.', 'vance-health-hub' ),
		),
		'vance_hub_dash_checkin'   => array(
			__( 'Member dashboard: show the weekly check-in', 'vance-health-hub' ),
			__( 'Three symptom questions, each on a scale from 0 to 4. The answers are health data: get the wording and the privacy policy approved before you turn this on.', 'vance-health-hub' ),
		),
	);
	foreach ( $switches as $name => $text ) {
		$wp_customize->add_setting( $name, array(
			'default'           => vance_hub_switch_default( $name ),
			'sanitize_callback' => 'vance_hub_sanitize_checkbox',
		) );
		$wp_customize->add_control( $name, array(
			'label'       => $text[0],
			'description' => $text[1] . ' ' . __( 'It shows only on the VanceHubClub dashboard home.', 'vance-health-hub' ),
			'section'     => $section,
			'type'        => 'checkbox',
		) );
	}

	$labels = array(
		'vance_hub_checkin_q_pain'  => __( 'Check-in: question 1 (pain)', 'vance-health-hub' ),
		'vance_hub_checkin_q_bowel' => __( 'Check-in: question 2 (bowel symptoms)', 'vance-health-hub' ),
		'vance_hub_checkin_q_tired' => __( 'Check-in: question 3 (tiredness)', 'vance-health-hub' ),
		'vance_hub_checkin_consent' => __( 'Check-in: consent text', 'vance-health-hub' ),
		'vance_hub_checkin_note'    => __( 'Check-in: note below the form', 'vance-health-hub' ),
	);
	foreach ( vance_hub_checkin_text_defaults() as $name => $default ) {
		$wp_customize->add_setting( $name, array(
			'default'           => $default,
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( $name, array(
			'label'       => $labels[ $name ],
			'description' => __( 'Keep the subject of each question the same: saved answers stay with their question. Leave empty to use the default.', 'vance-health-hub' ),
			'section'     => $section,
			'type'        => 'text',
		) );
	}
}
add_action( 'customize_register', 'vance_hub_dash_features_customize', 30 );

/* =========================================================================
 * Storage
 * ====================================================================== */

/**
 * The user meta key of the check-in history.
 *
 * The rows have the shape of the shared tool history ({id, ts, payload},
 * newest first), but the check-in is NOT in vance_known_tool_slugs() on
 * purpose. That list also opens the generic save, update, rename and
 * quick-register handlers, and those accept any payload. Check-in rows are
 * written only by vance_hub_checkin_handle_post(), after validation.
 *
 * @return string
 */
function vance_hub_checkin_meta_key() {
	return '_sla_weekly_check_in_history';
}

/**
 * The member's check-in rows, newest first. Damaged rows are left out.
 *
 * @param int $user_id Member.
 * @return array[] {ts, payload} rows.
 */
function vance_hub_checkin_history( $user_id ) {
	$rows = get_user_meta( (int) $user_id, vance_hub_checkin_meta_key(), true );
	if ( ! is_array( $rows ) ) {
		return array();
	}
	return array_values( array_filter( $rows, function ( $row ) {
		return is_array( $row ) && isset( $row['ts'], $row['payload'] ) && is_array( $row['payload'] );
	} ) );
}

/**
 * Add one validated check-in. Keeps the newest 50 rows.
 *
 * @param int   $user_id Member.
 * @param array $answers Answers, key => 0..4. Validated again here.
 * @param int   $now     Current time.
 * @return bool False when nothing was written.
 */
function vance_hub_checkin_append( $user_id, $answers, $now ) {
	$user_id = (int) $user_id;
	$answers = vance_hub_checkin_clean( $answers );
	if ( $user_id <= 0 || null === $answers ) {
		return false;
	}
	$answers['kind'] = 'check-in';
	$rows            = vance_hub_checkin_history( $user_id );
	array_unshift( $rows, array(
		'id'      => uniqid( 'ci_' ),
		'ts'      => (int) $now,
		'payload' => $answers,
	) );
	return false !== update_user_meta( $user_id, vance_hub_checkin_meta_key(), array_slice( $rows, 0, 50 ) );
}

/**
 * The member's code. Made and stored on first call.
 *
 * @param int $user_id Member.
 * @return string '' when no code could be stored.
 */
function vance_hub_member_code_for( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return '';
	}
	$key  = '_sla_member_code';
	$code = get_user_meta( $user_id, $key, true );
	if ( vance_hub_member_code_valid( $code ) ) {
		return $code;
	}
	for ( $try = 0; $try < 5; $try++ ) {
		$new   = vance_hub_member_code_make( 'random_int' );
		$taken = get_users( array(
			'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one indexed lookup, first dashboard view only.
			'meta_value' => $new, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'fields'     => 'ID',
			'number'     => 1,
		) );
		if ( ! empty( $taken ) ) {
			continue;
		}
		// unique = true: a second request in the same moment cannot add a second code.
		if ( add_user_meta( $user_id, $key, $new, true ) ) {
			return $new;
		}
		$stored = get_user_meta( $user_id, $key, true );
		if ( vance_hub_member_code_valid( $stored ) ) {
			return $stored;
		}
		// A damaged value holds the key: replace it.
		update_user_meta( $user_id, $key, $new );
		return $new;
	}
	return '';
}

/**
 * The consent wording a member agrees to now.
 *
 * @return string
 */
function vance_hub_checkin_consent_text() {
	$defaults = vance_hub_checkin_text_defaults();
	return vance_hub_dash_text( 'vance_hub_checkin_consent', $defaults['vance_hub_checkin_consent'] );
}

/**
 * True when the member agreed to the check-in, and to the wording in use now.
 *
 * The check-in has its own record. The general health flag
 * (_sla_consent_health) is set by a save in any tool, with other wording, so it
 * does not count here. A change to the wording in Customize asks again.
 *
 * @param mixed  $flag    _sla_consent_checkin.
 * @param mixed  $agreed  _sla_consent_checkin_text: the wording the member agreed to.
 * @param string $current The wording in use now.
 * @return bool
 */
function vance_hub_checkin_consent_ok( $flag, $agreed, $current ) {
	return ! empty( $flag ) && is_string( $agreed ) && '' !== $agreed && $agreed === (string) $current;
}

/**
 * A reference to one consent wording. The form carries the reference of the
 * wording it showed, so the save can prove the member saw the wording that is
 * recorded.
 *
 * @param string $wording Consent wording.
 * @return string 64 hex characters.
 */
function vance_hub_checkin_consent_ref( $wording ) {
	return hash( 'sha256', (string) $wording );
}

/**
 * True when the posted reference is the reference of the wording in use now.
 *
 * @param string $current The wording in use now.
 * @param mixed  $posted  The reference the form sent.
 * @return bool
 */
function vance_hub_checkin_consent_ref_ok( $current, $posted ) {
	return is_string( $posted ) && hash_equals( vance_hub_checkin_consent_ref( $current ), $posted );
}

/**
 * The three keys of the agreement record.
 *
 * @return string[]
 */
function vance_hub_checkin_consent_keys() {
	return array( '_sla_consent_checkin', '_sla_consent_checkin_at', '_sla_consent_checkin_text' );
}

/**
 * The agreement record as it is now, to put back if a save fails.
 *
 * @param int $user_id Member.
 * @return array key => stored value ('' when absent).
 */
function vance_hub_checkin_consent_snapshot( $user_id ) {
	$snapshot = array();
	foreach ( vance_hub_checkin_consent_keys() as $key ) {
		$snapshot[ $key ] = get_user_meta( (int) $user_id, $key, true );
	}
	return $snapshot;
}

/**
 * Put an agreement record back to a snapshot.
 *
 * @param int   $user_id  Member.
 * @param array $snapshot vance_hub_checkin_consent_snapshot() result.
 */
function vance_hub_checkin_consent_restore( $user_id, $snapshot ) {
	foreach ( vance_hub_checkin_consent_keys() as $key ) {
		$value = isset( $snapshot[ $key ] ) ? $snapshot[ $key ] : '';
		if ( is_string( $value ) && '' !== $value ) {
			update_user_meta( (int) $user_id, $key, wp_slash( $value ) );
		} else {
			delete_user_meta( (int) $user_id, $key );
		}
	}
}

/**
 * Record the member's agreement to one wording, then read it back.
 *
 * The return value comes from the read-back, not from update_user_meta():
 * that function also returns false when the stored value does not change.
 *
 * @param int    $user_id Member.
 * @param string $wording The wording the member agreed to.
 * @return bool True when the stored record is valid for this wording and
 *              holds the time of this agreement.
 */
function vance_hub_checkin_consent_record( $user_id, $wording ) {
	$user_id = (int) $user_id;
	$at      = (string) current_time( 'mysql' );
	update_user_meta( $user_id, '_sla_consent_checkin', '1' );
	update_user_meta( $user_id, '_sla_consent_checkin_at', $at );
	// wp_slash: update_user_meta() removes one level of slashes. With no slash
	// added here, wording that holds a backslash is stored changed, never
	// matches again, and the member is asked each week.
	update_user_meta( $user_id, '_sla_consent_checkin_text', wp_slash( $wording ) );
	// The time is part of the record: a record with no time, or with the time
	// of an older agreement to other wording, is wrong evidence.
	return '' !== $at
		&& get_user_meta( $user_id, '_sla_consent_checkin_at', true ) === $at
		&& vance_hub_checkin_consent_ok( get_user_meta( $user_id, '_sla_consent_checkin', true ), get_user_meta( $user_id, '_sla_consent_checkin_text', true ), $wording );
}

/* =========================================================================
 * Check-in: one request per member at a time
 * ====================================================================== */

/**
 * The name of the member's database lock.
 *
 * GET_LOCK() names are shared by every database on the server, so the name
 * holds the database name and the table prefix. The hash keeps it below the
 * 64-character limit.
 *
 * The hash is keyed with a secret of this site. Another customer on the same
 * database server can know the database name, the prefix and a user id. With
 * no secret they could make the name, hold the lock, and block a member's save
 * or withdrawal.
 *
 * @param int $user_id Member.
 * @return string 46 characters.
 */
function vance_hub_checkin_lock_name( $user_id ) {
	global $wpdb;
	$site = ( defined( 'DB_NAME' ) ? DB_NAME : '' ) . '|' . ( isset( $wpdb->prefix ) ? $wpdb->prefix : '' );
	return 'vance_checkin_' . substr( hash_hmac( 'sha256', $site . '|' . (int) $user_id, wp_salt( 'auth' ) ), 0, 32 );
}

/**
 * Take the member's lock. A save and a delete from the same member then run
 * one after the other, never at the same time.
 *
 * GET_LOCK() and not a user meta row: add_user_meta( ..., true ) reads, then
 * writes, so two requests can both get it. The database gives this lock to one
 * connection only, and frees it when the connection closes, so a request that
 * stops with an error cannot hold it.
 *
 * WordPress loaded this member's meta before the lock, at login. That copy can
 * be older than a write from the request that held the lock, so it is dropped
 * here. Each read after this comes from the database.
 *
 * @param int $user_id Member.
 * @param int $wait    Seconds to wait for a request that holds the lock.
 * @return bool False when the lock was not given. Write nothing then.
 */
function vance_hub_checkin_lock( $user_id, $wait = 5 ) {
	global $wpdb;
	$user_id = (int) $user_id;
	if ( $user_id <= 0 || ! is_object( $wpdb ) ) {
		return false;
	}
	$got = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', vance_hub_checkin_lock_name( $user_id ), (int) $wait ) );
	// 1 = given. 0 = another request held it for the full wait. NULL = error.
	if ( '1' !== (string) $got ) {
		return false;
	}
	wp_cache_delete( $user_id, 'user_meta' );
	return true;
}

/**
 * Free the member's lock.
 *
 * @param int $user_id Member.
 */
function vance_hub_checkin_unlock( $user_id ) {
	global $wpdb;
	if ( is_object( $wpdb ) ) {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', vance_hub_checkin_lock_name( (int) $user_id ) ) );
	}
}

/* =========================================================================
 * Check-in: POST handler
 * ====================================================================== */

/**
 * Save or delete check-in answers. The form posts to the dashboard page
 * itself, then this redirects, so a page reload does not send the form again.
 */
function vance_hub_checkin_handle_post() {
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['vance_hub_checkin_nonce'] ) ) {
		return;
	}
	if ( ! is_user_logged_in() || ! is_string( $_POST['vance_hub_checkin_nonce'] ) ) {
		return;
	}
	if ( ! vance_hub_dash_feature_on( 'vance_hub_dash_checkin' ) ) {
		return;
	}
	$back = remove_query_arg( 'vance_checkin' );
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vance_hub_checkin_nonce'] ) ), 'vance_hub_checkin' ) ) {
		// A page left open for many hours has an old nonce. Nothing is written,
		// and the member gets a message, not a blank form.
		wp_safe_redirect( add_query_arg( 'vance_checkin', 'expired', $back ) );
		exit;
	}

	$uid     = get_current_user_id();
	$started = time();
	$do     = isset( $_POST['vance_hub_checkin_do'] ) && is_string( $_POST['vance_hub_checkin_do'] ) ? sanitize_key( wp_unslash( $_POST['vance_hub_checkin_do'] ) ) : '';
	$result = 'invalid';

	// The lock comes before the first read of the answers or the agreement.
	// Two requests from one member (two tabs, a double click) then cannot both
	// read the same state and write over each other.
	$locked = ( 'delete' === $do || 'save' === $do ) && vance_hub_checkin_lock( $uid );

	if ( 'delete' === $do && ! $locked ) {
		// Another request from this member did not finish in time. Nothing is
		// deleted, and the message says so.
		$result = 'kept';
	} elseif ( 'save' === $do && ! $locked ) {
		$result = 'failed';
	} elseif ( 'delete' === $do ) {
		// Delete the answers AND withdraw the agreement. The time and the
		// wording of the old agreement stay as a record.
		delete_user_meta( $uid, vance_hub_checkin_meta_key() );
		if ( ! empty( get_user_meta( $uid, vance_hub_checkin_meta_key(), true ) ) ) {
			// Read back: the answers are still there. Do not tell the member
			// that they are gone, and do not withdraw the agreement they rest on.
			$result = 'kept';
		} else {
			$result = 'deleted';
			if ( get_user_meta( $uid, '_sla_consent_checkin', true ) ) {
				delete_user_meta( $uid, '_sla_consent_checkin' );
				if ( get_user_meta( $uid, '_sla_consent_checkin', true ) ) {
					// Read back: the agreement is still active. 'deleted' tells
					// the member that it is withdrawn, so do not say it.
					$result = 'kept';
				} else {
					update_user_meta( $uid, '_sla_consent_checkin_withdrawn_at', current_time( 'mysql' ) );
				}
			}
		}
	} elseif ( 'save' === $do ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- vance_hub_checkin_clean() accepts only single digits 0-4.
		$answers = vance_hub_checkin_clean( isset( $_POST['vhd_checkin'] ) ? wp_unslash( $_POST['vhd_checkin'] ) : null );
		$wording = vance_hub_checkin_consent_text();
		$consent = vance_hub_checkin_consent_ok( get_user_meta( $uid, '_sla_consent_checkin', true ), get_user_meta( $uid, '_sla_consent_checkin_text', true ), $wording );
		$history = vance_hub_checkin_history( $uid );

		if ( null === $answers ) {
			$result = 'invalid';
		} elseif ( ! $consent && empty( $_POST['vhd_consent'] ) ) {
			$result = 'consent';
		} elseif ( ! $consent && ! vance_hub_checkin_consent_ref_ok( $wording, isset( $_POST['vhd_consent_ref'] ) ? wp_unslash( $_POST['vhd_consent_ref'] ) : null ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- compared with hash_equals() only, never stored or output.
			// The wording changed after the form was shown. The member ticked
			// the box for other wording, so nothing is recorded.
			$result = 'changed';
		} elseif ( ! vance_hub_checkin_due( vance_hub_dashboard_latest_ts( $history ), time() ) ) {
			$result = 'early';
		} else {
			// Order: the agreement first, then the answers. Answers with no
			// agreement must not exist. An agreement with no answers is put
			// back to what it was, so a failed save changes nothing.
			$before = $consent ? null : vance_hub_checkin_consent_snapshot( $uid );
			// 'failed', not 'invalid': the answers were good. Do not tell the
			// member to answer the questions again.
			$result = 'failed';
			if ( null !== $before && ! vance_hub_checkin_consent_record( $uid, $wording ) ) {
				vance_hub_checkin_consent_restore( $uid, $before );
			} elseif ( vance_hub_checkin_append( $uid, $answers, time() ) ) {
				$result = 'saved';
			} elseif ( null !== $before && vance_hub_dashboard_latest_ts( vance_hub_checkin_history( $uid ) ) < $started ) {
				// Not when answers arrived after this request started: a second
				// request from the same member (two tabs, a double click) saved
				// them, and they rest on the agreement. Taking the agreement
				// back would leave those answers with no record.
				vance_hub_checkin_consent_restore( $uid, $before );
			}
		}
	}

	if ( $locked ) {
		vance_hub_checkin_unlock( $uid );
	}

	wp_safe_redirect( add_query_arg( 'vance_checkin', $result, $back ) );
	exit;
}
add_action( 'template_redirect', 'vance_hub_checkin_handle_post' );

/* =========================================================================
 * Data
 * ====================================================================== */

/**
 * Add display dates to a series.
 *
 * @param array $points vance_hub_dashboard_series() result.
 * @return array[] The same points, each with 'date'.
 */
function vance_hub_dashboard_series_dates( $points ) {
	foreach ( $points as $i => $point ) {
		$points[ $i ]['date'] = (string) wp_date( 'j M Y', $point['ts'] );
	}
	return $points;
}

/**
 * The data of the three features for one member.
 *
 * @param WP_User $user       The logged-in member.
 * @param array   $checks     The member's nutrition check rows.
 * @param string  $check_href Address of the nutrition check tool.
 * @return array member_code (string), results (array|null), checkin (array|null).
 */
function vance_hub_dashboard_features_data( $user, $checks, $check_href ) {
	$uid = (int) $user->ID;
	$out = array(
		'member_code' => '',
		'results'     => null,
		'checkin'     => null,
	);

	if ( vance_hub_dash_feature_on( 'vance_hub_dash_member_no' ) ) {
		$out['member_code'] = vance_hub_member_code_for( $uid );
	}

	if ( vance_hub_dash_feature_on( 'vance_hub_dash_chart' ) ) {
		$points = vance_hub_dashboard_series_dates( vance_hub_dashboard_series( $checks, 'score', 8 ) );
		if ( ! empty( $points ) ) {
			// The risk words of the newest scored check, as the calculator saved them.
			$risk      = '';
			$newest_ts = $points[ count( $points ) - 1 ]['ts'];
			foreach ( (array) $checks as $entry ) {
				if ( isset( $entry['ts'], $entry['payload']['riskLabel'] ) && (int) $entry['ts'] === $newest_ts && is_string( $entry['payload']['riskLabel'] ) ) {
					$risk = $entry['payload']['riskLabel'];
					break;
				}
			}
			$out['results'] = array(
				'points' => $points,
				'risk'   => $risk,
				'href'   => (string) $check_href,
			);
		}
	}

	if ( vance_hub_dash_feature_on( 'vance_hub_dash_checkin' ) ) {
		$history   = vance_hub_checkin_history( $uid );
		$last      = vance_hub_dashboard_latest_ts( $history );
		$defaults  = vance_hub_checkin_text_defaults();
		$questions = array();
		$series    = array();
		foreach ( vance_hub_checkin_keys() as $key ) {
			$name              = 'vance_hub_checkin_q_' . $key;
			$questions[ $key ] = vance_hub_dash_text( $name, $defaults[ $name ] );
			$series[ $key ]    = vance_hub_dashboard_series_dates( vance_hub_dashboard_series( $history, $key, vance_hub_checkin_max() ) );
		}
		$flash = '';
		if ( isset( $_GET['vance_checkin'] ) && is_string( $_GET['vance_checkin'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- selects one fixed message, changes nothing.
			$flash = sanitize_key( wp_unslash( $_GET['vance_checkin'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$out['checkin'] = array(
			'due'          => vance_hub_checkin_due( $last, time() ),
			'last'         => $last > 0 ? (string) wp_date( 'j M Y', $last ) : '',
			'next'         => $last > 0 ? (string) wp_date( 'j M Y', $last + 6 * 86400 ) : '',
			'count'        => count( $history ),
			'questions'    => $questions,
			'series'       => $series,
			'max'          => vance_hub_checkin_max(),
			'need_consent' => ! vance_hub_checkin_consent_ok( get_user_meta( $uid, '_sla_consent_checkin', true ), get_user_meta( $uid, '_sla_consent_checkin_text', true ), vance_hub_checkin_consent_text() ),
			'consent'      => vance_hub_checkin_consent_text(),
			'note'         => vance_hub_dash_text( 'vance_hub_checkin_note', $defaults['vance_hub_checkin_note'] ),
			'nonce'        => wp_create_nonce( 'vance_hub_checkin' ),
			'flash'        => $flash,
		);
	}

	return $out;
}

/* =========================================================================
 * Render (array in, HTML out)
 * ====================================================================== */

/**
 * One chart with its text alternative.
 *
 * @param array  $points Series with 'date', oldest first.
 * @param string $label  What the chart shows, for screen readers.
 * @return string '' with fewer than two points.
 */
function vance_hub_dashboard_chart_html( $points, $label ) {
	$svg = vance_hub_dashboard_chart_svg( $points );
	if ( '' === $svg ) {
		return '';
	}
	$first = $points[0];
	$last  = $points[ count( $points ) - 1 ];
	$html  = '<figure class="vhd-chart">' . $svg;
	$html .= '<figcaption class="vhd-chart__range"><span>' . esc_html( $first['date'] ) . '</span><span>' . esc_html( $last['date'] ) . '</span></figcaption>';
	// The same values as text, for a member who cannot see the chart.
	$html .= '<ol class="vhd-sr" aria-label="' . esc_attr( $label ) . '">';
	foreach ( $points as $point ) {
		/* translators: 1: date, 2: value, 3: scale maximum. */
		$html .= '<li>' . esc_html( sprintf( __( '%1$s: %2$s of %3$s', 'vance-health-hub' ), $point['date'], vance_hub_dashboard_num( $point['value'] ), vance_hub_dashboard_num( $point['max'] ) ) ) . '</li>';
	}
	$html .= '</ol></figure>';
	return $html;
}

/**
 * The "My results" block: saved nutrition risk checks.
 *
 * @param array|null $r 'results' from vance_hub_dashboard_features_data().
 * @return string
 */
function vance_hub_dashboard_results_html( $r ) {
	if ( empty( $r ) || empty( $r['points'] ) ) {
		return '';
	}
	$points = array_values( $r['points'] );
	$last   = $points[ count( $points ) - 1 ];
	$arrow  = ' <span aria-hidden="true">&rarr;</span>';

	$html  = '<section class="vhd-card vhd-results" aria-labelledby="vhd-results-title"><div class="vhd-card__head">';
	$html .= '<h2 id="vhd-results-title" class="vhd-h3">' . esc_html__( 'My nutrition risk checks', 'vance-health-hub' ) . '</h2>';
	if ( ! empty( $r['href'] ) ) {
		$html .= '<a class="vhd-link" href="' . esc_url( $r['href'] ) . '">' . esc_html__( 'Check again', 'vance-health-hub' ) . $arrow . '</a>';
	}
	$html .= '</div>';
	/* translators: 1: score, 2: maximum score, 3: date. */
	$line = sprintf( __( 'Latest score: %1$s of %2$s, on %3$s.', 'vance-health-hub' ), vance_hub_dashboard_num( $last['value'] ), vance_hub_dashboard_num( $last['max'] ), $last['date'] );
	if ( ! empty( $r['risk'] ) ) {
		$line .= ' ' . $r['risk'] . '.';
	}
	$html .= '<p class="vhd-results__latest">' . esc_html( $line ) . '</p>';
	$html .= '<p class="vhd-results__hint">' . esc_html__( 'A lower score is a lower risk.', 'vance-health-hub' ) . '</p>';
	$html .= vance_hub_dashboard_chart_html( $points, __( 'Nutrition risk scores by date', 'vance-health-hub' ) );
	$html .= '<p class="vhd-small">' . esc_html__( 'Results are an estimate from the answers you gave. They are not a diagnosis. Speak to your health team about any concern.', 'vance-health-hub' ) . '</p>';
	$html .= '</section>';
	return $html;
}

/**
 * The weekly check-in block: the form when a check-in is due, the member's
 * own answers by date, and the delete control.
 *
 * @param array|null $c 'checkin' from vance_hub_dashboard_features_data().
 * @return string
 */
function vance_hub_dashboard_checkin_html( $c ) {
	if ( empty( $c ) || empty( $c['questions'] ) ) {
		return '';
	}
	$max      = (int) $c['max'];
	$messages = array(
		'saved'   => __( 'Your check-in is saved.', 'vance-health-hub' ),
		'deleted' => __( 'Your check-in answers are deleted, and your agreement is withdrawn.', 'vance-health-hub' ),
		'consent' => __( 'Not saved. Tick the box to agree, then send the form again.', 'vance-health-hub' ),
		'early'   => __( 'Not saved. You did your check-in for this week.', 'vance-health-hub' ),
		'invalid' => __( 'Not saved. Answer all the questions, then send the form again.', 'vance-health-hub' ),
		'expired' => __( 'Not saved. This page was open for too long. Answer the questions again, then send the form.', 'vance-health-hub' ),
		'failed'  => __( 'Not saved. The Hub could not store your answers. Try again later.', 'vance-health-hub' ),
		'changed' => __( 'Not saved. The agreement text changed. Read it again, then send the form.', 'vance-health-hub' ),
		'kept'    => __( 'Not completed. The Hub could not delete your answers or withdraw your agreement. Try again later.', 'vance-health-hub' ),
	);
	$nonce = '<input type="hidden" name="vance_hub_checkin_nonce" value="' . esc_attr( $c['nonce'] ) . '">';

	$html  = '<section class="vhd-card vhd-checkin" aria-labelledby="vhd-checkin-title"><div class="vhd-card__head">';
	$html .= '<h2 id="vhd-checkin-title" class="vhd-h3">' . esc_html__( 'My weekly check-in', 'vance-health-hub' ) . '</h2></div>';

	if ( isset( $messages[ $c['flash'] ] ) ) {
		$ok    = in_array( $c['flash'], array( 'saved', 'deleted' ), true );
		$html .= '<p class="vhd-flash' . ( $ok ? '' : ' vhd-flash--warn' ) . '" role="status">' . esc_html( $messages[ $c['flash'] ] ) . '</p>';
	}

	if ( ! empty( $c['due'] ) ) {
		$html .= '<form class="vhd-checkin__form" method="post" action="">' . $nonce;
		$html .= '<input type="hidden" name="vance_hub_checkin_do" value="save">';
		/* translators: %s: highest value of the scale. */
		$html .= '<p class="vhd-checkin__scale">' . esc_html( sprintf( __( 'Choose a number for each question. 0 is none. %s is very bad.', 'vance-health-hub' ), number_format_i18n( $max ) ) ) . '</p>';
		foreach ( $c['questions'] as $key => $question ) {
			$html .= '<fieldset class="vhd-checkin__q"><legend>' . esc_html( $question ) . '</legend><div class="vhd-checkin__opts">';
			for ( $n = 0; $n <= $max; $n++ ) {
				$id    = 'vhd-checkin-' . $key . '-' . $n;
				$html .= '<input class="vhd-sr" type="radio" id="' . esc_attr( $id ) . '" name="vhd_checkin[' . esc_attr( $key ) . ']" value="' . $n . '" required>';
				$html .= '<label for="' . esc_attr( $id ) . '">' . esc_html( number_format_i18n( $n ) ) . '</label>';
			}
			$html .= '</div></fieldset>';
		}
		if ( ! empty( $c['need_consent'] ) ) {
			// The reference of the wording shown here. The save refuses a tick
			// for wording that is not the wording in use at that time.
			$html .= '<input type="hidden" name="vhd_consent_ref" value="' . esc_attr( vance_hub_checkin_consent_ref( $c['consent'] ) ) . '">';
			$html .= '<label class="vhd-checkin__consent"><input type="checkbox" name="vhd_consent" value="1" required> <span>' . esc_html( $c['consent'] ) . '</span></label>';
		}
		$html .= '<button type="submit" class="vhd-btn vhd-btn--purple">' . esc_html__( 'Save my check-in', 'vance-health-hub' ) . '</button>';
		$html .= '</form>';
	} else {
		/* translators: 1: date of the last check-in, 2: date the next one opens. */
		$html .= '<p class="vhd-checkin__done">' . esc_html( sprintf( __( 'You did your check-in on %1$s. The next one opens on %2$s.', 'vance-health-hub' ), $c['last'], $c['next'] ) ) . '</p>';
	}

	// The member's own answers by date: one small chart per question.
	$charts = '';
	foreach ( $c['questions'] as $key => $question ) {
		$chart = isset( $c['series'][ $key ] ) ? vance_hub_dashboard_chart_html( $c['series'][ $key ], $question ) : '';
		if ( '' !== $chart ) {
			$charts .= '<div class="vhd-checkin__trend"><p class="vhd-checkin__trend-q">' . esc_html( $question ) . '</p>' . $chart . '</div>';
		}
	}
	if ( '' !== $charts ) {
		$html .= '<div class="vhd-checkin__trends">' . $charts . '</div>';
	}

	if ( '' !== $c['note'] ) {
		$html .= '<p class="vhd-small">' . esc_html( $c['note'] ) . '</p>';
	}

	if ( (int) $c['count'] > 0 ) {
		$html .= '<details class="vhd-checkin__manage"><summary>' . esc_html__( 'Manage my check-in data', 'vance-health-hub' ) . '</summary>';
		/* translators: %s: number of saved check-ins. */
		$html .= '<p>' . esc_html( sprintf( _n( 'You have %s saved check-in.', 'You have %s saved check-ins.', (int) $c['count'], 'vance-health-hub' ), number_format_i18n( (int) $c['count'] ) ) ) . ' ' . esc_html__( 'You cannot get them back after you delete them.', 'vance-health-hub' ) . '</p>';
		$html .= '<form method="post" action="">' . $nonce . '<input type="hidden" name="vance_hub_checkin_do" value="delete">';
		$html .= '<button type="submit" class="vhd-btn vhd-btn--outline">' . esc_html__( 'Delete my answers and withdraw my agreement', 'vance-health-hub' ) . '</button></form></details>';
	}

	$html .= '</section>';
	return $html;
}
