<?php
/**
 * VanceHubClub member dashboard: the flare plan.
 *
 * One personal plan for each member, shown as a card in the "Flare plan"
 * sub-tab of the Health Profile tab (page-dashboard.php). The member writes three answers in their own words,
 * can change them, print them and delete them. The fourth section, "When to get
 * urgent help", is fixed text that an administrator enters in Customize.
 *
 * The card shows only when its switch is on AND the urgent help text is not
 * empty. There is no default urgent help text on purpose: the site has no named
 * medical reviewer, and clinical wording must come from a clinician.
 *
 * The card records the member's own words. It gives no advice.
 *
 * What this file WRITES:
 *   _sla_flare_plan                        {updated, signs, actions, contacts}
 *   _sla_consent_flare_plan                '1' while the member agrees
 *   _sla_consent_flare_plan_at             when the member agreed
 *   _sla_consent_flare_plan_text           the wording the member agreed to
 *   _sla_consent_flare_plan_withdrawn_at   when the member withdrew (delete)
 *
 * The meta keys keep the legacy `_sla_` prefix on purpose (see CLAUDE.md).
 *
 * The plan is NOT in vance_known_tool_slugs(): that list opens generic save and
 * rename handlers that accept any payload. The plan is written only by
 * vance_hub_flare_plan_handle_post(), after validation.
 *
 * It reuses the consent, lock and switch helpers of inc/hub-dashboard-features.php
 * (required first). One lock for each member: a check-in save and a plan save
 * run one after the other.
 *
 * Layout of this file: pure helpers, switch and texts, storage, POST handler,
 * privacy exporter and eraser, data, render.
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
 * The answer keys of the plan.
 *
 * @return string[]
 */
function vance_hub_flare_plan_keys() {
	return array( 'signs', 'actions', 'contacts' );
}

/**
 * The longest answer, in characters.
 *
 * @return int
 */
function vance_hub_flare_plan_max_chars() {
	return 1000;
}

/**
 * Clean the posted answers.
 *
 * @param mixed $raw Posted array, key => text. Still slashed, as in $_POST.
 * @return array|null key => clean text for every key, or null when the answers
 *                    are not an array or all three are empty.
 */
function vance_hub_flare_plan_clean( $raw ) {
	if ( ! is_array( $raw ) ) {
		return null;
	}
	$unslashed = array();
	foreach ( vance_hub_flare_plan_keys() as $key ) {
		$unslashed[ $key ] = isset( $raw[ $key ] ) && is_string( $raw[ $key ] ) ? wp_unslash( $raw[ $key ] ) : '';
	}
	return vance_hub_flare_plan_sanitise( $unslashed );
}

/**
 * Sanitise answers that are already unslashed. Safe to run twice: it removes
 * no slash, so text with a backslash keeps it.
 *
 * @param array $answers key => text, no slashes added.
 * @return array|null key => clean text, or null when all three are empty.
 */
function vance_hub_flare_plan_sanitise( $answers ) {
	if ( ! is_array( $answers ) ) {
		return null;
	}
	$limit = vance_hub_flare_plan_max_chars();
	$clean = array();
	$any   = false;
	foreach ( vance_hub_flare_plan_keys() as $key ) {
		$text = isset( $answers[ $key ] ) && is_string( $answers[ $key ] ) ? $answers[ $key ] : '';
		$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
		$text = trim( sanitize_textarea_field( $text ) );
		$text = vance_hub_flare_plan_limit_text( $text, $limit );
		if ( '' !== $text ) {
			$any = true;
		}
		$clean[ $key ] = $text;
	}
	return $any ? $clean : null;
}

/**
 * Cut a text to a number of characters.
 *
 * @param string $text  Text.
 * @param int    $limit Most characters to keep.
 * @return string
 */
function vance_hub_flare_plan_limit_text( $text, $limit ) {
	$limit = max( 1, (int) $limit );
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( (string) $text, 0, $limit );
	}
	// No mbstring on this server: cut by characters with PCRE, never by bytes.
	if ( 1 === preg_match( '/^.{0,' . $limit . '}/su', (string) $text, $match ) ) {
		return $match[0];
	}
	return substr( (string) $text, 0, $limit );
}

/**
 * A stored plan, checked. A damaged value gives null.
 *
 * @param mixed $stored The value of _sla_flare_plan.
 * @return array|null {updated:int, signs:string, actions:string, contacts:string}
 */
function vance_hub_flare_plan_normalise( $stored ) {
	if ( ! is_array( $stored ) || ! isset( $stored['updated'] ) || ! is_numeric( $stored['updated'] ) || (int) $stored['updated'] <= 0 ) {
		return null;
	}
	$plan = array( 'updated' => (int) $stored['updated'] );
	foreach ( vance_hub_flare_plan_keys() as $key ) {
		$plan[ $key ] = isset( $stored[ $key ] ) && is_string( $stored[ $key ] ) ? $stored[ $key ] : '';
	}
	return $plan;
}

/**
 * Text for display: escaped, with line breaks kept.
 *
 * @param string $text Member text or administrator text.
 * @return string HTML.
 */
function vance_hub_flare_plan_text_html( $text ) {
	return nl2br( esc_html( (string) $text ) );
}

/* =========================================================================
 * Switch and texts
 * ====================================================================== */

/**
 * Default wording. Editable in Customize. The urgent help text has no default.
 *
 * @return array name => default text.
 */
function vance_hub_flare_plan_text_defaults() {
	return array(
		'vance_hub_flare_plan_consent' => __( 'I agree that the Hub stores my flare plan and my flare dates in my account. I can delete them at any time.', 'vance-health-hub' ),
		'vance_hub_flare_plan_note'    => __( 'This plan is your own record. It is not medical advice. Share it with your health team.', 'vance-health-hub' ),
	);
}

/**
 * The urgent help text an administrator entered.
 *
 * @return string '' when none is set.
 */
function vance_hub_flare_plan_urgent_text() {
	return trim( (string) vance_get_theme_mod( 'vance_hub_flare_plan_urgent', '' ) );
}

/**
 * True when the card can show and save: the switch is on AND the urgent help
 * text is set.
 *
 * @return bool
 */
function vance_hub_flare_plan_available() {
	return vance_hub_dash_feature_on( 'vance_hub_dash_flare_plan' ) && '' !== vance_hub_flare_plan_urgent_text();
}

/**
 * Ask page caches to skip the member's dashboard while the flare plan can show.
 * The page holds the plan, and the appointment sheet holds medication, check-in
 * scores and screening results. WordPress already sends no-cache headers to a
 * logged-in member; this adds the flag that page-cache plugins read.
 */
function vance_hub_flare_plan_no_cache() {
	if ( ! is_user_logged_in() || ! function_exists( 'is_page' ) || ! vance_hub_flare_plan_available() ) {
		return;
	}
	// The dashboard page is found by its slug or by its template, as the rest of
	// the theme does: the page may use another slug with the template bound.
	if ( ! is_page( 'dashboard' ) && ! ( function_exists( 'is_page_template' ) && is_page_template( 'page-dashboard.php' ) ) ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
}
add_action( 'template_redirect', 'vance_hub_flare_plan_no_cache', 1 );

/**
 * The consent wording a member agrees to now.
 *
 * @return string
 */
function vance_hub_flare_plan_consent_text() {
	$defaults = vance_hub_flare_plan_text_defaults();
	return vance_hub_dash_text( 'vance_hub_flare_plan_consent', $defaults['vance_hub_flare_plan_consent'] );
}

/**
 * Customizer controls: the switch and the three texts.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function vance_hub_flare_plan_customize( $wp_customize ) {
	$section = 'vance_home_layout';
	$wp_customize->add_setting( 'vance_hub_dash_flare_plan', array(
		'default'           => vance_hub_switch_default( 'vance_hub_dash_flare_plan' ),
		'sanitize_callback' => 'vance_hub_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'vance_hub_dash_flare_plan', array(
		'label'       => __( 'Member dashboard: show the flare plan', 'vance-health-hub' ),
		'description' => __( 'A card where the member writes their own plan for a flare. It is health data: turn it on only when the privacy policy covers it, and the urgent help text below is set. It shows only in the Health Profile tab of the member dashboard (sub-tab "Flare plan"), and only when the VanceHubClub dashboard is on.', 'vance-health-hub' ),
		'section'     => $section,
		'type'        => 'checkbox',
	) );

	$wp_customize->add_setting( 'vance_hub_flare_plan_urgent', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'vance_hub_flare_plan_urgent', array(
		'label'       => __( 'Flare plan: "When to get urgent help" text', 'vance-health-hub' ),
		'description' => __( 'Fixed text that every member sees on their plan. A clinician must write or approve it. The card does not show while this is empty. Turning the switch off hides the card and does not erase saved plans: members cannot delete a plan while it is hidden, so use the WordPress privacy tools (Tools > Erase Personal Data) for an erasure request.', 'vance-health-hub' ),
		'section'     => $section,
		'type'        => 'textarea',
	) );

	$labels = array(
		'vance_hub_flare_plan_consent' => __( 'Flare plan: consent text', 'vance-health-hub' ),
		'vance_hub_flare_plan_note'    => __( 'Flare plan: note below the plan', 'vance-health-hub' ),
	);
	foreach ( vance_hub_flare_plan_text_defaults() as $name => $default ) {
		$wp_customize->add_setting( $name, array(
			'default'           => $default,
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( $name, array(
			'label'       => $labels[ $name ],
			'description' => __( 'Leave empty to use the default.', 'vance-health-hub' ),
			'section'     => $section,
			'type'        => 'text',
		) );
	}
}
add_action( 'customize_register', 'vance_hub_flare_plan_customize', 30 );

/* =========================================================================
 * Storage
 * ====================================================================== */

/**
 * The user meta key of the plan.
 *
 * @return string
 */
function vance_hub_flare_plan_meta_key() {
	return '_sla_flare_plan';
}

/**
 * The member's plan.
 *
 * @param int $user_id Member.
 * @return array|null null when there is no plan or it is damaged.
 */
function vance_hub_flare_plan_get( $user_id ) {
	return vance_hub_flare_plan_normalise( get_user_meta( (int) $user_id, vance_hub_flare_plan_meta_key(), true ) );
}

/**
 * Store the member's plan, then read it back. A save replaces the old plan.
 *
 * The return value comes from the read-back, not from update_user_meta(): that
 * function also returns false when the stored value does not change.
 *
 * @param int   $user_id Member.
 * @param array $answers vance_hub_flare_plan_clean() result. Validated again here.
 * @param int   $now     Current time.
 * @return bool True when the stored plan equals what was sent.
 */
function vance_hub_flare_plan_write( $user_id, $answers, $now ) {
	$user_id = (int) $user_id;
	// sanitise, not clean: the answers are already unslashed. A second unslash
	// would remove every backslash a member typed.
	$clean = vance_hub_flare_plan_sanitise( $answers );
	if ( $user_id <= 0 || null === $clean ) {
		return false;
	}
	$plan = array( 'updated' => (int) $now ) + $clean;
	// wp_slash: update_user_meta() removes one level of slashes, so text that
	// holds a backslash is stored changed with no slash added here.
	update_user_meta( $user_id, vance_hub_flare_plan_meta_key(), wp_slash( $plan ) );
	return vance_hub_flare_plan_get( $user_id ) === $plan;
}

/* =========================================================================
 * Flare log: when a flare started and ended, and what helped
 * ====================================================================== */

/**
 * The user meta key of the flare log. Rows are {id, start, end, helped},
 * newest first. Dates are Y-m-d in the site time zone. 'end' is '' while the
 * flare is open.
 *
 * @return string
 */
function vance_hub_flare_log_meta_key() {
	return '_sla_flare_log';
}

/**
 * The most rows the log keeps.
 *
 * @return int
 */
function vance_hub_flare_log_max_rows() {
	return 50;
}

/**
 * The longest "what helped" text, in characters.
 *
 * @return int
 */
function vance_hub_flare_log_helped_max() {
	return 500;
}

/**
 * True when a value is a real calendar date written Y-m-d.
 *
 * @param mixed $date Value.
 * @return bool
 */
function vance_hub_flare_log_date_ok( $date ) {
	if ( ! is_string( $date ) || 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $m ) ) {
		return false;
	}
	return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
}

/**
 * A stored log, checked. Damaged rows are left out.
 *
 * @param mixed $stored The value of _sla_flare_log.
 * @return array[] {id, start, end, helped} rows, in stored order.
 */
function vance_hub_flare_log_normalise( $stored ) {
	if ( ! is_array( $stored ) ) {
		return array();
	}
	$rows = array();
	foreach ( $stored as $row ) {
		if ( ! is_array( $row ) || ! isset( $row['start'] ) || ! vance_hub_flare_log_date_ok( $row['start'] ) ) {
			continue;
		}
		// 'end' must be there and be a string: '' means open. A row with no 'end', or
		// with a non-string one, is damaged. It must not read as an open flare.
		if ( ! isset( $row['end'] ) || ! is_string( $row['end'] ) ) {
			continue;
		}
		$end = $row['end'];
		if ( '' !== $end && ( ! vance_hub_flare_log_date_ok( $end ) || $end < $row['start'] ) ) {
			continue;
		}
		$rows[] = array(
			'id'     => isset( $row['id'] ) && is_string( $row['id'] ) ? $row['id'] : '',
			'start'  => $row['start'],
			'end'    => $end,
			'helped' => isset( $row['helped'] ) && is_string( $row['helped'] ) ? vance_hub_flare_plan_limit_text( $row['helped'], vance_hub_flare_log_helped_max() ) : '',
		);
	}
	return array_slice( $rows, 0, vance_hub_flare_log_max_rows() );
}

/**
 * The member's flare log, newest first.
 *
 * @param int $user_id Member.
 * @return array[]
 */
function vance_hub_flare_log_get( $user_id ) {
	return vance_hub_flare_log_normalise( get_user_meta( (int) $user_id, vance_hub_flare_log_meta_key(), true ) );
}

/**
 * The position of the open flare in a log.
 *
 * @param array[] $rows Log rows.
 * @return int|null null when no flare is open.
 */
function vance_hub_flare_log_open_index( $rows ) {
	foreach ( array_values( (array) $rows ) as $i => $row ) {
		if ( isset( $row['end'] ) && '' === $row['end'] ) {
			return $i;
		}
	}
	return null;
}

/**
 * The days a flare lasted, counting the first and the last day.
 *
 * @param string $start Y-m-d.
 * @param string $end   Y-m-d.
 * @return int At least 1.
 */
function vance_hub_flare_log_days( $start, $end ) {
	$a = strtotime( $start . ' 00:00:00 UTC' );
	$b = strtotime( $end . ' 00:00:00 UTC' );
	if ( false === $a || false === $b || $b < $a ) {
		return 1;
	}
	return (int) round( ( $b - $a ) / 86400 ) + 1;
}

/**
 * Add a new open flare to a log.
 *
 * @param array[] $rows  Log rows, newest first.
 * @param string  $today Y-m-d.
 * @param string  $id    Row id.
 * @return array[]|null null when a flare is already open or the date is bad.
 */
function vance_hub_flare_log_start( $rows, $today, $id ) {
	if ( ! vance_hub_flare_log_date_ok( $today ) || null !== vance_hub_flare_log_open_index( $rows ) ) {
		return null;
	}
	$rows = array_values( (array) $rows );
	array_unshift( $rows, array( 'id' => (string) $id, 'start' => $today, 'end' => '', 'helped' => '' ) );
	return array_slice( $rows, 0, vance_hub_flare_log_max_rows() );
}

/**
 * Close the open flare of a log.
 *
 * @param array[] $rows   Log rows, newest first.
 * @param string  $today  Y-m-d.
 * @param string  $helped What helped, already clean.
 * @return array[]|null null when no flare is open or the date is bad.
 */
function vance_hub_flare_log_end( $rows, $today, $helped ) {
	$rows = array_values( (array) $rows );
	$i    = vance_hub_flare_log_open_index( $rows );
	if ( null === $i || ! vance_hub_flare_log_date_ok( $today ) ) {
		return null;
	}
	$rows[ $i ]['end']    = $today < $rows[ $i ]['start'] ? $rows[ $i ]['start'] : $today;
	$rows[ $i ]['helped'] = (string) $helped;
	return $rows;
}

/**
 * Store the log, then read it back.
 *
 * @param int     $user_id Member.
 * @param array[] $rows    Log rows.
 * @return bool True when the stored log equals what was sent.
 */
function vance_hub_flare_log_write( $user_id, $rows ) {
	$user_id = (int) $user_id;
	$rows    = vance_hub_flare_log_normalise( $rows );
	if ( $user_id <= 0 || empty( $rows ) ) {
		return false;
	}
	// wp_slash: update_user_meta() removes one level of slashes.
	update_user_meta( $user_id, vance_hub_flare_log_meta_key(), wp_slash( $rows ) );
	return vance_hub_flare_log_get( $user_id ) === $rows;
}

/**
 * A date for display.
 *
 * @param string $ymd Y-m-d.
 * @return string For example "12 Nov 2026".
 */
function vance_hub_flare_log_label( $ymd ) {
	$ts = strtotime( $ymd . ' 12:00:00 UTC' );
	// UTC on purpose: the stored date is a calendar day, not an instant. A site
	// time zone would move it by a day.
	return false === $ts ? '' : (string) wp_date( 'j M Y', $ts, new DateTimeZone( 'UTC' ) );
}

/**
 * Start or end a flare for a member. The caller holds the member's lock.
 *
 * A flare is logged only for a member who has a saved plan and has agreed to
 * the wording in use now: the log rests on the same agreement as the plan.
 *
 * @param int    $user_id    Member.
 * @param string $do         'flare_start' or 'flare_end'.
 * @param int    $now        Current time.
 * @param mixed  $helped_raw Posted "what helped" text, still slashed.
 * @return string Result code.
 */
function vance_hub_flare_log_action( $user_id, $do, $now, $helped_raw ) {
	$user_id = (int) $user_id;
	if ( null === vance_hub_flare_plan_get( $user_id ) || ! vance_hub_flare_plan_available() ) {
		return 'noplan';
	}
	$keys = vance_hub_checkin_consent_keys( 'flare_plan' );
	if ( ! vance_hub_checkin_consent_ok( get_user_meta( $user_id, $keys[0], true ), get_user_meta( $user_id, $keys[2], true ), vance_hub_flare_plan_consent_text() ) ) {
		return 'changed';
	}
	$rows  = vance_hub_flare_log_get( $user_id );
	$today = (string) wp_date( 'Y-m-d', (int) $now );
	if ( 'flare_start' === $do ) {
		$new = vance_hub_flare_log_start( $rows, $today, uniqid( 'fl_' ) );
		if ( null === $new ) {
			return 'flare_open';
		}
		return vance_hub_flare_log_write( $user_id, $new ) ? 'flare_started' : 'failed';
	}
	$helped = is_string( $helped_raw ) ? wp_unslash( $helped_raw ) : '';
	$helped = trim( sanitize_textarea_field( str_replace( array( "\r\n", "\r" ), "\n", $helped ) ) );
	$helped = vance_hub_flare_plan_limit_text( $helped, vance_hub_flare_log_helped_max() );
	$new    = vance_hub_flare_log_end( $rows, $today, $helped );
	if ( null === $new ) {
		return 'flare_none';
	}
	return vance_hub_flare_log_write( $user_id, $new ) ? 'flare_ended' : 'failed';
}

/* =========================================================================
 * POST handler
 * ====================================================================== */

/**
 * Save or delete the plan. The form posts to the dashboard page itself, then
 * this redirects, so a page reload does not send the form again.
 *
 * Same order and result codes as vance_hub_checkin_handle_post().
 */
function vance_hub_flare_plan_handle_post() {
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['vance_hub_flare_nonce'] ) ) {
		return;
	}
	if ( ! is_user_logged_in() || ! is_string( $_POST['vance_hub_flare_nonce'] ) ) {
		return;
	}
	if ( ! vance_hub_dash_feature_on( 'vance_hub_dash_flare_plan' ) ) {
		return;
	}
	$back = remove_query_arg( 'vance_flare' );
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vance_hub_flare_nonce'] ) ), 'vance_hub_flare_plan' ) ) {
		wp_safe_redirect( add_query_arg( 'vance_flare', 'expired', $back ) );
		exit;
	}

	$uid    = get_current_user_id();
	$do     = isset( $_POST['vance_hub_flare_do'] ) && is_string( $_POST['vance_hub_flare_do'] ) ? sanitize_key( wp_unslash( $_POST['vance_hub_flare_do'] ) ) : '';
	$result = 'invalid';

	// The lock comes before the first read of the plan or the agreement.
	$known  = array( 'delete', 'save', 'flare_start', 'flare_end' );
	$locked = in_array( $do, $known, true ) && vance_hub_checkin_lock( $uid );

	if ( 'delete' === $do && ! $locked ) {
		$result = 'kept';
	} elseif ( in_array( $do, $known, true ) && ! $locked ) {
		$result = 'failed';
	} elseif ( 'flare_start' === $do || 'flare_end' === $do ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- vance_hub_flare_log_action() unslashes and sanitises it.
		$result = vance_hub_flare_log_action( $uid, $do, time(), isset( $_POST['vhd_flare_helped'] ) ? $_POST['vhd_flare_helped'] : '' );
	} elseif ( 'delete' === $do ) {
		// Delete the plan and the flare log AND withdraw the agreement. The time
		// and the wording of the old agreement stay as a record.
		// The log goes first: if it cannot be deleted, the plan stays, the card
		// still shows the Delete button, and the member can try again.
		delete_user_meta( $uid, vance_hub_flare_log_meta_key() );
		$result = 'deleted';
		if ( ! empty( get_user_meta( $uid, vance_hub_flare_log_meta_key(), true ) ) ) {
			// Read back: the log is still there. Do not touch the plan or the agreement.
			$result = 'kept';
		} else {
			delete_user_meta( $uid, vance_hub_flare_plan_meta_key() );
			if ( ! empty( get_user_meta( $uid, vance_hub_flare_plan_meta_key(), true ) ) ) {
				$result = 'kept';
			}
		}
		if ( 'kept' === $result ) {
			// Nothing more: the agreement stays while any data is left.
		} else {
			$result = 'deleted';
			$flag   = vance_hub_checkin_consent_keys( 'flare_plan' )[0];
			if ( get_user_meta( $uid, $flag, true ) ) {
				delete_user_meta( $uid, $flag );
				if ( get_user_meta( $uid, $flag, true ) ) {
					$result = 'kept';
				} else {
					update_user_meta( $uid, $flag . '_withdrawn_at', current_time( 'mysql' ) );
				}
			}
		}
	} elseif ( 'save' === $do ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- vance_hub_flare_plan_clean() unslashes and sanitises every field.
		$answers = vance_hub_flare_plan_clean( isset( $_POST['vhd_flare'] ) ? $_POST['vhd_flare'] : null );
		$wording = vance_hub_flare_plan_consent_text();
		$keys    = vance_hub_checkin_consent_keys( 'flare_plan' );
		$consent = vance_hub_checkin_consent_ok( get_user_meta( $uid, $keys[0], true ), get_user_meta( $uid, $keys[2], true ), $wording );

		if ( null === $answers ) {
			$result = 'invalid';
		} elseif ( ! $consent && empty( $_POST['vhd_consent'] ) ) {
			$result = 'consent';
		} elseif ( ! $consent && ! vance_hub_checkin_consent_ref_ok( $wording, isset( $_POST['vhd_consent_ref'] ) ? wp_unslash( $_POST['vhd_consent_ref'] ) : null ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- compared with hash_equals() only, never stored or output.
			$result = 'changed';
		} elseif ( '' === vance_hub_flare_plan_urgent_text() ) {
			// The card is hidden while the urgent help text is empty. No plan is
			// stored that the member could not see with that text.
			$result = 'failed';
		} else {
			// Order: the agreement first, then the plan. A plan with no agreement
			// must not exist. An agreement with no plan is put back to what it
			// was, so a failed save changes nothing.
			$before = $consent ? null : vance_hub_checkin_consent_snapshot( $uid, 'flare_plan' );
			$result = 'failed';
			if ( null !== $before && ! vance_hub_checkin_consent_record( $uid, $wording, 'flare_plan' ) ) {
				vance_hub_checkin_consent_restore( $uid, $before, 'flare_plan' );
			} elseif ( vance_hub_flare_plan_write( $uid, $answers, time() ) ) {
				$result = 'saved';
				// A new agreement ends the old withdrawal: the record must not show
				// "withdrawn" with a date older than "agreed".
				if ( null !== $before ) {
					delete_user_meta( $uid, $keys[0] . '_withdrawn_at' );
				}
			} elseif ( null !== $before ) {
				vance_hub_checkin_consent_restore( $uid, $before, 'flare_plan' );
			}
		}
	}

	if ( $locked ) {
		vance_hub_checkin_unlock( $uid );
	}

	wp_safe_redirect( add_query_arg( 'vance_flare', $result, $back ) );
	exit;
}
add_action( 'template_redirect', 'vance_hub_flare_plan_handle_post' );

/* =========================================================================
 * Privacy: WordPress personal data exporter and eraser
 * ====================================================================== */

/**
 * Every user meta key this feature writes.
 *
 * @return string[]
 */
function vance_hub_flare_plan_all_meta_keys() {
	$keys   = vance_hub_checkin_consent_keys( 'flare_plan' );
	$keys[] = '_sla_consent_flare_plan_withdrawn_at';
	$keys[] = vance_hub_flare_plan_meta_key();
	$keys[] = vance_hub_flare_log_meta_key();
	return $keys;
}

/**
 * Add the exporter to the WordPress personal data tools.
 *
 * @param array $exporters Registered exporters.
 * @return array
 */
function vance_hub_flare_plan_register_exporter( $exporters ) {
	$exporters['vance-flare-plan'] = array(
		'exporter_friendly_name' => __( 'Flare plan', 'vance-health-hub' ),
		'callback'               => 'vance_hub_flare_plan_export',
	);
	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'vance_hub_flare_plan_register_exporter' );

/**
 * Add the eraser to the WordPress personal data tools.
 *
 * @param array $erasers Registered erasers.
 * @return array
 */
function vance_hub_flare_plan_register_eraser( $erasers ) {
	$erasers['vance-flare-plan'] = array(
		'eraser_friendly_name' => __( 'Flare plan', 'vance-health-hub' ),
		'callback'             => 'vance_hub_flare_plan_erase',
	);
	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'vance_hub_flare_plan_register_eraser' );

/**
 * Exporter: the plan and its agreement record.
 *
 * @param string $email Member email.
 * @param int    $page  Page, unused: one item only.
 * @return array {data, done}
 */
function vance_hub_flare_plan_export( $email, $page = 1 ) {
	$user = get_user_by( 'email', (string) $email );
	if ( ! $user ) {
		return array( 'data' => array(), 'done' => true );
	}
	$uid  = (int) $user->ID;
	$data = array();
	$plan = vance_hub_flare_plan_get( $uid );
	if ( null !== $plan ) {
		$data[] = array( 'name' => __( 'Last changed', 'vance-health-hub' ), 'value' => (string) wp_date( 'j M Y H:i', $plan['updated'] ) );
		$data[] = array( 'name' => __( 'My early signs', 'vance-health-hub' ), 'value' => $plan['signs'] );
		$data[] = array( 'name' => __( 'What I do first', 'vance-health-hub' ), 'value' => $plan['actions'] );
		$data[] = array( 'name' => __( 'Who I contact', 'vance-health-hub' ), 'value' => $plan['contacts'] );
	}
	foreach ( vance_hub_flare_log_get( $uid ) as $row ) {
		$span   = '' === $row['end'] ? $row['start'] . ' / ' . __( 'not ended', 'vance-health-hub' ) : $row['start'] . ' / ' . $row['end'];
		$value  = $span;
		$value .= '' !== $row['helped'] ? ' / ' . __( 'What helped', 'vance-health-hub' ) . ': ' . $row['helped'] : '';
		$data[] = array( 'name' => __( 'Flare (start / end)', 'vance-health-hub' ), 'value' => $value );
	}
	$record = array(
		'_sla_consent_flare_plan_at'           => __( 'Agreed on', 'vance-health-hub' ),
		'_sla_consent_flare_plan_text'         => __( 'Agreement wording', 'vance-health-hub' ),
		'_sla_consent_flare_plan_withdrawn_at' => __( 'Withdrawn on', 'vance-health-hub' ),
	);
	foreach ( $record as $key => $name ) {
		$value = get_user_meta( $uid, $key, true );
		if ( is_string( $value ) && '' !== $value ) {
			$data[] = array( 'name' => $name, 'value' => $value );
		}
	}
	if ( empty( $data ) ) {
		return array( 'data' => array(), 'done' => true );
	}
	return array(
		'data' => array(
			array(
				'group_id'    => 'vance-flare-plan',
				'group_label' => __( 'Flare plan', 'vance-health-hub' ),
				'item_id'     => 'vance-flare-plan-' . $uid,
				'data'        => $data,
			),
		),
		'done' => true,
	);
}

/**
 * Eraser: the plan and every agreement key.
 *
 * @param string $email Member email.
 * @param int    $page  Page, unused.
 * @return array {items_removed, items_retained, messages, done}
 */
function vance_hub_flare_plan_erase( $email, $page = 1 ) {
	$out  = array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
	$user = get_user_by( 'email', (string) $email );
	if ( ! $user ) {
		return $out;
	}
	$uid = (int) $user->ID;
	foreach ( vance_hub_flare_plan_all_meta_keys() as $key ) {
		if ( '' === get_user_meta( $uid, $key, true ) ) {
			continue;
		}
		delete_user_meta( $uid, $key );
		if ( '' !== get_user_meta( $uid, $key, true ) ) {
			$out['items_retained'] = true;
			/* translators: %s: user meta key. */
			$out['messages'][] = sprintf( __( 'The flare plan record %s could not be deleted.', 'vance-health-hub' ), $key );
		} else {
			$out['items_removed'] = true;
		}
	}
	return $out;
}

/* =========================================================================
 * Data
 * ====================================================================== */

/**
 * How complete the plan is: the three answers, and a review in the last 6 months.
 * It measures the plan, never the member's health.
 *
 * @param array $plan A normalised plan.
 * @param int   $now  Current time.
 * @return array {done:int, total:int, pct:int, missing:string[], stale:bool, months:int}
 */
function vance_hub_flare_plan_progress( $plan, $now ) {
	$done    = 0;
	$missing = array();
	foreach ( vance_hub_flare_plan_keys() as $key ) {
		if ( '' !== trim( (string) $plan[ $key ] ) ) {
			$done++;
		} else {
			$missing[] = $key;
		}
	}
	$age   = max( 0, (int) $now - (int) $plan['updated'] );
	$fresh = $age < 183 * 86400;
	if ( $fresh ) {
		$done++;
	}
	return array(
		'done'    => $done,
		'total'   => 4,
		'pct'     => (int) round( 100 * $done / 4 ),
		'missing' => $missing,
		'stale'   => ! $fresh,
		'months'  => (int) floor( $age / ( 30 * 86400 ) ),
	);
}

/**
 * The flare log, ready to show.
 *
 * @param array[] $log Log rows, newest first.
 * @param int     $now Current time.
 * @return array {open:array|null, rows:array[], count:int}. rows holds the 5 newest closed or open rows.
 */
function vance_hub_flare_log_view( $log, $now ) {
	$today = (string) wp_date( 'Y-m-d', (int) $now );
	$open  = null;
	$rows  = array();
	foreach ( $log as $i => $row ) {
		$end    = '' === $row['end'] ? $today : $row['end'];
		$view   = array(
			'start'  => vance_hub_flare_log_label( $row['start'] ),
			'end'    => '' === $row['end'] ? '' : vance_hub_flare_log_label( $row['end'] ),
			'days'   => vance_hub_flare_log_days( $row['start'], $end ),
			'helped' => $row['helped'],
		);
		if ( '' === $row['end'] && null === $open ) {
			$open = $view;
		}
		if ( count( $rows ) < 5 ) {
			$rows[] = $view;
		}
	}
	return array( 'open' => $open, 'rows' => $rows, 'count' => count( $log ) );
}

/**
 * The newest saved check-in, for the "How am I today?" comparison.
 *
 * @param int $uid Member.
 * @return array|null {date, pain, bowel, tired}, null when there is none.
 */
function vance_hub_flare_plan_quick_data( $uid ) {
	if ( ! function_exists( 'vance_hub_checkin_history' ) ) {
		return null;
	}
	$rows = vance_hub_checkin_history( (int) $uid );
	if ( empty( $rows ) || ! isset( $rows[0]['ts'], $rows[0]['payload'] ) || ! is_numeric( $rows[0]['ts'] ) || (int) $rows[0]['ts'] <= 0 ) {
		return null;
	}
	$last = array( 'date' => (string) wp_date( 'j M Y', (int) $rows[0]['ts'] ) );
	foreach ( array( 'pain', 'bowel', 'tired' ) as $key ) {
		if ( ! isset( $rows[0]['payload'][ $key ] ) || ! is_numeric( $rows[0]['payload'][ $key ] ) ) {
			return null;
		}
		$last[ $key ] = max( 0, min( 4, (int) $rows[0]['payload'][ $key ] ) );
	}
	return $last;
}

/**
 * What the appointment sheet shows. Everything is read from data the member
 * already saved. Nothing new is stored.
 *
 * @param int     $uid  Member.
 * @param array   $plan The member's plan.
 * @param array[] $log  Log rows, newest first.
 * @param int     $now  Current time.
 * @return array
 */
function vance_hub_flare_sheet_data( $uid, $plan, $log, $now ) {
	$uid     = (int) $uid;
	$profile = get_user_meta( $uid, '_sla_clinical_profile', true );
	$profile = is_array( $profile ) ? $profile : array();
	$text    = function ( $key ) use ( $profile ) {
		return isset( $profile[ $key ] ) && is_string( $profile[ $key ] ) ? trim( $profile[ $key ] ) : '';
	};
	$appointment = '';
	if ( '' !== $text( 'next_appointment' ) ) {
		// A plain date (Y-m-d) is a calendar day: read it as UTC so that no PHP
		// default zone moves it. Another format is read as the server reads it.
		$typed       = $text( 'next_appointment' );
		$ts          = 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $typed ) ? strtotime( $typed . ' 12:00:00 UTC' ) : strtotime( $typed );
		// Shown in UTC, to match.
		$appointment = $ts ? (string) wp_date( 'j M Y', $ts, new DateTimeZone( 'UTC' ) ) : '';
	}

	$checkins = array();
	if ( function_exists( 'vance_hub_checkin_history' ) ) {
		foreach ( array_slice( vance_hub_checkin_history( $uid ), 0, 14 ) as $row ) {
			$p = $row['payload'];
			if ( ! isset( $p['pain'], $p['bowel'], $p['tired'] ) || ! is_numeric( $row['ts'] ) || (int) $row['ts'] <= 0 ) {
				continue;
			}
			$checkins[] = array(
				'date'  => (string) wp_date( 'j M Y', (int) $row['ts'] ),
				'pain'  => (int) $p['pain'],
				'bowel' => (int) $p['bowel'],
				'tired' => (int) $p['tired'],
			);
		}
	}

	$screening = null;
	if ( function_exists( 'vance_get_tool_history' ) && function_exists( 'vance_hub_dashboard_series' ) ) {
		$rows   = vance_get_tool_history( $uid, 'malnutrition-calculator', 0 );
		$points = vance_hub_dashboard_series( $rows, 'score', 8 );
		if ( ! empty( $points ) ) {
			$last      = $points[ count( $points ) - 1 ];
			$risk      = '';
			foreach ( (array) $rows as $entry ) {
				if ( isset( $entry['ts'], $entry['payload']['riskLabel'] ) && (int) $entry['ts'] === $last['ts'] && is_string( $entry['payload']['riskLabel'] ) ) {
					$risk = $entry['payload']['riskLabel'];
					break;
				}
			}
			$screening = array(
				'date'  => (string) wp_date( 'j M Y', $last['ts'] ),
				'score' => vance_hub_dashboard_num( $last['value'] ),
				'max'   => vance_hub_dashboard_num( $last['max'] ),
				'risk'  => $risk,
			);
		}
	}

	$view = vance_hub_flare_log_view( $log, $now );
	return array(
		'prepared'    => (string) wp_date( 'j M Y', (int) $now ),
		'appointment' => $appointment,
		'questions'   => $text( 'appointment_questions' ),
		'medication'  => $text( 'medication' ),
		'checkins'    => $checkins,
		'screening'   => $screening,
		'flares'      => $view['rows'],
		'flare_count' => $view['count'],
	);
}

/**
 * The flare plan data for one member.
 *
 * @param int $uid Member.
 * @return array|null null when the card must not show.
 */
function vance_hub_flare_plan_data( $uid ) {
	if ( ! vance_hub_flare_plan_available() ) {
		return null;
	}
	$uid      = (int) $uid;
	$plan     = vance_hub_flare_plan_get( $uid );
	$defaults = vance_hub_flare_plan_text_defaults();
	$keys     = vance_hub_checkin_consent_keys( 'flare_plan' );
	$wording  = vance_hub_flare_plan_consent_text();
	$flash    = '';
	if ( isset( $_GET['vance_flare'] ) && is_string( $_GET['vance_flare'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- selects one fixed message, changes nothing.
		$flash = sanitize_key( wp_unslash( $_GET['vance_flare'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	$log = null !== $plan ? vance_hub_flare_log_get( $uid ) : array();
	return array(
		'plan'         => $plan,
		'progress'     => null !== $plan ? vance_hub_flare_plan_progress( $plan, time() ) : null,
		'log'          => vance_hub_flare_log_view( $log, time() ),
		'quick'        => null !== $plan ? vance_hub_flare_plan_quick_data( $uid ) : null,
		'sheet'        => null !== $plan ? vance_hub_flare_sheet_data( $uid, $plan, $log, time() ) : null,
		'updated'      => null !== $plan ? (string) wp_date( 'j M Y', $plan['updated'] ) : '',
		'urgent'       => vance_hub_flare_plan_urgent_text(),
		'need_consent' => ! vance_hub_checkin_consent_ok( get_user_meta( $uid, $keys[0], true ), get_user_meta( $uid, $keys[2], true ), $wording ),
		'consent'      => $wording,
		'note'         => vance_hub_dash_text( 'vance_hub_flare_plan_note', $defaults['vance_hub_flare_plan_note'] ),
		'nonce'        => wp_create_nonce( 'vance_hub_flare_plan' ),
		'flash'        => $flash,
	);
}

/* =========================================================================
 * Render (array in, HTML out)
 * ====================================================================== */

/**
 * The three text boxes, filled with the saved plan when there is one.
 *
 * @param array|null $plan The saved plan.
 * @param string     $id   Prefix for the field ids.
 * @return string
 */
function vance_hub_flare_plan_fields_html( $plan, $id ) {
	$fields = array(
		'signs'    => array( __( 'My early signs', 'vance-health-hub' ), __( 'How do you notice a flare is starting?', 'vance-health-hub' ) ),
		'actions'  => array( __( 'What I do first', 'vance-health-hub' ), __( 'What do you do in the first days of a flare?', 'vance-health-hub' ) ),
		'contacts' => array( __( 'Who I contact', 'vance-health-hub' ), __( 'Names and phone numbers of the people you call.', 'vance-health-hub' ) ),
	);
	$html = '';
	foreach ( $fields as $key => $text ) {
		$value = null !== $plan && isset( $plan[ $key ] ) ? $plan[ $key ] : '';
		$fid   = $id . '-' . $key;
		$html .= '<div class="vhd-flare__field"><label for="' . esc_attr( $fid ) . '" class="vhd-flare__label">' . esc_html( $text[0] ) . '</label>';
		$html .= '<p class="vhd-flare__hint" id="' . esc_attr( $fid ) . '-hint">' . esc_html( $text[1] ) . '</p>';
		$html .= '<textarea class="vhd-flare__input" id="' . esc_attr( $fid ) . '" name="vhd_flare[' . esc_attr( $key ) . ']" rows="4" maxlength="' . (int) vance_hub_flare_plan_max_chars() . '" aria-describedby="' . esc_attr( $fid ) . '-hint">' . esc_textarea( $value ) . '</textarea></div>';
	}
	return $html;
}

/**
 * The plan form. A new plan gets the step-by-step builder: hub-flare-plan.js
 * shows one box at a time. With no script, every box shows.
 *
 * @param array      $f      Card data.
 * @param array|null $plan   The saved plan.
 * @param string     $nonce  Nonce field HTML.
 * @param bool       $wizard True for the step-by-step builder.
 * @return string
 */
function vance_hub_flare_plan_form_html( $f, $plan, $nonce, $wizard ) {
	$form  = '<form class="vhd-flare__form" method="post" action=""' . ( $wizard ? ' data-vhd-wizard data-step-text="' . esc_attr__( 'Step %1$s of %2$s', 'vance-health-hub' ) . '" data-next-text="' . esc_attr__( 'Next', 'vance-health-hub' ) . '" data-back-text="' . esc_attr__( 'Back', 'vance-health-hub' ) . '"' : '' ) . '>' . $nonce;
	$form .= '<input type="hidden" name="vance_hub_flare_do" value="save">';
	$form .= vance_hub_flare_plan_fields_html( $plan, $wizard ? 'vhd-flare' : 'vhd-flare-edit' );
	$form .= '<div class="vhd-flare__final">';
	if ( ! empty( $f['need_consent'] ) ) {
		$form .= '<input type="hidden" name="vhd_consent_ref" value="' . esc_attr( vance_hub_checkin_consent_ref( $f['consent'] ) ) . '">';
		$form .= '<label class="vhd-flare__consent"><input type="checkbox" name="vhd_consent" value="1" required> <span>' . esc_html( $f['consent'] ) . '</span></label>';
	}
	$form .= '<button type="submit" class="vhd-btn vhd-btn--purple">' . esc_html__( 'Save my plan', 'vance-health-hub' ) . '</button></div></form>';
	return $form;
}

/**
 * The plan progress bar and the hints under it.
 *
 * @param array|null $pr 'progress' from vance_hub_flare_plan_data().
 * @return string
 */
function vance_hub_flare_plan_progress_html( $pr ) {
	if ( empty( $pr ) ) {
		return '';
	}
	$names = array(
		'signs'    => __( 'My early signs', 'vance-health-hub' ),
		'actions'  => __( 'What I do first', 'vance-health-hub' ),
		'contacts' => __( 'Who I contact', 'vance-health-hub' ),
	);
	/* translators: 1: sections done, 2: total sections. */
	$text  = sprintf( __( 'My plan: %1$s of %2$s done', 'vance-health-hub' ), (int) $pr['done'], (int) $pr['total'] );
	$html  = '<div class="vhd-flare__progress"><p class="vhd-flare__progress-text" id="vhd-flare-progress-text">' . esc_html( $text ) . '</p>';
	$html .= '<div class="vhd-flare__bar" role="progressbar" aria-valuemin="0" aria-valuemax="' . (int) $pr['total'] . '" aria-valuenow="' . (int) $pr['done'] . '" aria-labelledby="vhd-flare-progress-text"><span style="width:' . (int) $pr['pct'] . '%"></span></div>';
	$hints = '';
	foreach ( (array) $pr['missing'] as $key ) {
		if ( isset( $names[ $key ] ) ) {
			/* translators: %s: the name of a plan section. */
			$hints .= '<li>' . esc_html( sprintf( __( 'Add: %s', 'vance-health-hub' ), $names[ $key ] ) ) . '</li>';
		}
	}
	if ( ! empty( $pr['stale'] ) ) {
		/* translators: %s: number of months. */
		$hints .= '<li>' . esc_html( sprintf( __( 'You last changed your plan %s months ago. Check that it is still right.', 'vance-health-hub' ), number_format_i18n( (int) $pr['months'] ) ) ) . '</li>';
	}
	if ( '' !== $hints ) {
		$html .= '<ul class="vhd-flare__hints">' . $hints . '</ul>';
	}
	return $html . '</div>';
}

/**
 * "How am I today?": three sliders. Nothing is sent or saved: hub-flare-plan.js
 * compares the answers with the member's last check-in and points to the plan.
 * It gives no verdict. With no script the block stays hidden.
 *
 * @param array|null $q 'quick' from vance_hub_flare_plan_data(): the last check-in, or null.
 * @return string
 */
function vance_hub_flare_plan_quick_html( $q ) {
	$labels = array(
		'pain'  => __( 'Tummy pain', 'vance-health-hub' ),
		'bowel' => __( 'Bowel symptoms', 'vance-health-hub' ),
		'tired' => __( 'Tiredness', 'vance-health-hub' ),
	);
	$config = array(
		'labels'  => $labels,
		'today'   => __( 'Today', 'vance-health-hub' ),
		/* translators: %s: date of the last check-in. */
		'last'    => __( 'Last check-in (%s)', 'vance-health-hub' ),
		/* translators: %s: list of symptoms. */
		'higher'  => __( 'Higher than your last check-in: %s.', 'vance-health-hub' ),
		'same'    => __( 'None is higher than your last check-in.', 'vance-health-hub' ),
		'nolast'  => __( 'You have no saved check-in to compare with.', 'vance-health-hub' ),
		'follow'  => __( 'Read your early signs and your first steps below. Follow your plan if you notice your early signs. If you are worried, use the urgent help section.', 'vance-health-hub' ),
		'last_at' => null !== $q ? $q['date'] : '',
		'last_v'  => null !== $q ? array( 'pain' => $q['pain'], 'bowel' => $q['bowel'], 'tired' => $q['tired'] ) : null,
	);
	$html  = '<section class="vhd-flare__quick" data-vhd-flare-quick data-config="' . esc_attr( (string) json_encode( $config ) ) . '" hidden aria-labelledby="vhd-flare-quick-title">';
	$html .= '<h3 id="vhd-flare-quick-title" class="vhd-flare__h">' . esc_html__( 'How am I today?', 'vance-health-hub' ) . '</h3>';
	$html .= '<p class="vhd-flare__hint">' . esc_html__( 'Move each slider. 0 is none. 4 is very bad. Nothing is saved, and this is not a diagnosis.', 'vance-health-hub' ) . '</p>';
	foreach ( $labels as $key => $label ) {
		$id    = 'vhd-flare-q-' . $key;
		$html .= '<div class="vhd-flare__q"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . ' <output for="' . esc_attr( $id ) . '">0</output></label>';
		$html .= '<input type="range" min="0" max="4" step="1" value="0" id="' . esc_attr( $id ) . '" data-vhd-q="' . esc_attr( $key ) . '"></div>';
	}
	$html .= '<button type="button" class="vhd-btn vhd-btn--outline" data-vhd-quick-show>' . esc_html__( 'Compare with my plan', 'vance-health-hub' ) . '</button>';
	$html .= '<div class="vhd-flare__quick-result" data-vhd-quick-result role="status" aria-live="polite"></div></section>';
	return $html;
}

/**
 * The flare log block: start a flare, end it with what helped, and the history.
 *
 * @param array  $log   'log' from vance_hub_flare_plan_data().
 * @param string $nonce Nonce field HTML.
 * @return string
 */
function vance_hub_flare_plan_log_html( $log, $nonce ) {
	$html  = '<section class="vhd-flare__log" aria-labelledby="vhd-flare-log-title"><h3 id="vhd-flare-log-title" class="vhd-flare__h">' . esc_html__( 'My flare log', 'vance-health-hub' ) . '</h3>';
	$open  = isset( $log['open'] ) && is_array( $log['open'] ) ? $log['open'] : null;
	if ( null !== $open ) {
		/* translators: 1: start date, 2: day number. */
		$html .= '<p>' . esc_html( sprintf( __( 'I am in a flare. It started on %1$s. Today is day %2$s.', 'vance-health-hub' ), $open['start'], number_format_i18n( (int) $open['days'] ) ) ) . '</p>';
		$html .= '<form class="vhd-flare__logform" method="post" action="">' . $nonce . '<input type="hidden" name="vance_hub_flare_do" value="flare_end">';
		$html .= '<label for="vhd-flare-helped" class="vhd-flare__label">' . esc_html__( 'What helped? (optional)', 'vance-health-hub' ) . '</label>';
		$html .= '<textarea class="vhd-flare__input" id="vhd-flare-helped" name="vhd_flare_helped" rows="3" maxlength="' . (int) vance_hub_flare_log_helped_max() . '"></textarea>';
		$html .= '<button type="submit" class="vhd-btn vhd-btn--purple">' . esc_html__( 'My flare has ended', 'vance-health-hub' ) . '</button></form>';
	} else {
		$html .= '<form class="vhd-flare__logform" method="post" action="">' . $nonce . '<input type="hidden" name="vance_hub_flare_do" value="flare_start">';
		$html .= '<button type="submit" class="vhd-btn vhd-btn--outline">' . esc_html__( 'I am in a flare', 'vance-health-hub' ) . '</button></form>';
	}
	if ( ! empty( $log['rows'] ) ) {
		$html .= '<ul class="vhd-flare__history">';
		foreach ( $log['rows'] as $row ) {
			if ( '' === $row['end'] ) {
				/* translators: 1: start date, 2: number of days so far. */
				$line = sprintf( __( '%1$s to now (%2$s days so far)', 'vance-health-hub' ), $row['start'], number_format_i18n( (int) $row['days'] ) );
			} else {
				/* translators: 1: start date, 2: end date, 3: number of days. */
				$line = sprintf( _n( '%1$s to %2$s (%3$s day)', '%1$s to %2$s (%3$s days)', (int) $row['days'], 'vance-health-hub' ), $row['start'], $row['end'], number_format_i18n( (int) $row['days'] ) );
			}
			$html .= '<li><span>' . esc_html( $line ) . '</span>';
			if ( '' !== $row['helped'] ) {
				$html .= '<span class="vhd-flare__helped">' . esc_html__( 'What helped:', 'vance-health-hub' ) . ' ' . vance_hub_flare_plan_text_html( $row['helped'] ) . '</span>';
			}
			$html .= '</li>';
		}
		$html .= '</ul>';
		/* translators: %s: number of flares in the log. */
		$html .= '<p class="vhd-small">' . esc_html( sprintf( _n( '%s flare logged.', '%s flares logged.', (int) $log['count'], 'vance-health-hub' ), number_format_i18n( (int) $log['count'] ) ) ) . '</p>';
	}
	return $html . '</section>';
}

/**
 * The appointment sheet: one page to bring to a doctor or nurse. Data the
 * member already saved, written by the member. It is not medical advice.
 *
 * @param array      $s    'sheet' from vance_hub_flare_plan_data().
 * @param array|null $plan The member's plan.
 * @return string
 */
function vance_hub_flare_plan_sheet_html( $s, $plan ) {
	if ( empty( $s ) || null === $plan ) {
		return '';
	}
	$sec = function ( $title, $body ) {
		return '<div class="vhd-sheet__sec"><h4 class="vhd-sheet__h">' . esc_html( $title ) . '</h4>' . $body . '</div>';
	};
	$html  = '<div class="vhd-flare-sheet"><h3 class="vhd-flare__h">' . esc_html__( 'Appointment sheet', 'vance-health-hub' ) . '</h3>';
	/* translators: %s: date the sheet was made. */
	$html .= '<p class="vhd-small">' . esc_html( sprintf( __( 'Prepared on %s from what I saved in the Hub.', 'vance-health-hub' ), $s['prepared'] ) ) . '</p>';
	if ( '' !== $s['appointment'] ) {
		$html .= $sec( __( 'Next appointment', 'vance-health-hub' ), '<p>' . esc_html( $s['appointment'] ) . '</p>' );
	}
	if ( '' !== $s['questions'] ) {
		$html .= $sec( __( 'Questions to ask', 'vance-health-hub' ), '<p>' . vance_hub_flare_plan_text_html( $s['questions'] ) . '</p>' );
	}
	$names = array(
		'signs'    => __( 'My early signs', 'vance-health-hub' ),
		'actions'  => __( 'What I do first', 'vance-health-hub' ),
		'contacts' => __( 'Who I contact', 'vance-health-hub' ),
	);
	foreach ( $names as $key => $title ) {
		if ( '' !== $plan[ $key ] ) {
			$html .= $sec( $title, '<p>' . vance_hub_flare_plan_text_html( $plan[ $key ] ) . '</p>' );
		}
	}
	if ( '' !== $s['medication'] ) {
		$html .= $sec( __( 'Medication', 'vance-health-hub' ), '<p>' . vance_hub_flare_plan_text_html( $s['medication'] ) . '</p>' );
	}
	if ( ! empty( $s['flares'] ) ) {
		$list = '<ul class="vhd-sheet__list">';
		foreach ( $s['flares'] as $row ) {
			$end   = '' === $row['end'] ? __( 'now', 'vance-health-hub' ) : $row['end'];
			/* translators: 1: start date, 2: end date, 3: number of days. */
			$line  = sprintf( __( '%1$s to %2$s (%3$s days)', 'vance-health-hub' ), $row['start'], $end, number_format_i18n( (int) $row['days'] ) );
			$list .= '<li>' . esc_html( $line );
			if ( '' !== $row['helped'] ) {
				$list .= ' - ' . esc_html__( 'what helped:', 'vance-health-hub' ) . ' ' . vance_hub_flare_plan_text_html( $row['helped'] );
			}
			$list .= '</li>';
		}
		$html .= $sec( __( 'My flares', 'vance-health-hub' ), $list . '</ul>' );
	}
	if ( ! empty( $s['checkins'] ) ) {
		$table  = '<table class="vhd-sheet__table"><thead><tr><th scope="col">' . esc_html__( 'Date', 'vance-health-hub' ) . '</th><th scope="col">' . esc_html__( 'Pain', 'vance-health-hub' ) . '</th><th scope="col">' . esc_html__( 'Bowel', 'vance-health-hub' ) . '</th><th scope="col">' . esc_html__( 'Tired', 'vance-health-hub' ) . '</th></tr></thead><tbody>';
		foreach ( $s['checkins'] as $row ) {
			$table .= '<tr><td>' . esc_html( $row['date'] ) . '</td><td>' . (int) $row['pain'] . '</td><td>' . (int) $row['bowel'] . '</td><td>' . (int) $row['tired'] . '</td></tr>';
		}
		$table .= '</tbody></table><p class="vhd-small">' . esc_html__( '0 is none. 4 is very bad.', 'vance-health-hub' ) . '</p>';
		$html  .= $sec( __( 'Recent check-ins', 'vance-health-hub' ), $table );
	}
	if ( ! empty( $s['screening'] ) ) {
		$sc = $s['screening'];
		/* translators: 1: score, 2: maximum score, 3: date. */
		$line = sprintf( __( 'Score %1$s of %2$s, on %3$s.', 'vance-health-hub' ), $sc['score'], $sc['max'], $sc['date'] ) . ( '' !== $sc['risk'] ? ' ' . $sc['risk'] . '.' : '' );
		$html .= $sec( __( 'Latest nutrition risk check', 'vance-health-hub' ), '<p>' . esc_html( $line ) . '</p><p class="vhd-small">' . esc_html__( 'A lower score is a lower risk. It is an estimate, not a diagnosis.', 'vance-health-hub' ) . '</p>' );
	}
	$html .= '<p class="vhd-small">' . esc_html__( 'Written by the member. It is not medical advice.', 'vance-health-hub' ) . '</p>';
	$html .= '<div class="vhd-flare__actions"><button type="button" class="vhd-btn vhd-btn--outline" data-vhd-flare-print="sheet" hidden>' . esc_html__( 'Print appointment sheet', 'vance-health-hub' ) . '</button></div></div>';
	return $html;
}

/**
 * The flare plan card.
 *
 * @param array|null $f vance_hub_flare_plan_data() result.
 * @return string
 */
function vance_hub_flare_plan_html( $f ) {
	// Not empty(): the text "0" is set text, as in vance_hub_flare_plan_available().
	if ( empty( $f ) || ! isset( $f['urgent'] ) || '' === trim( (string) $f['urgent'] ) ) {
		return '';
	}
	$messages = array(
		'saved'         => __( 'Your flare plan is saved.', 'vance-health-hub' ),
		'deleted'       => __( 'Your flare plan and flare log are deleted, and your agreement is withdrawn.', 'vance-health-hub' ),
		'consent'       => __( 'Not saved. Tick the box to agree, then send the form again.', 'vance-health-hub' ),
		'invalid'       => __( 'Not saved. Write at least one answer, then send the form again.', 'vance-health-hub' ),
		'expired'       => __( 'Not saved. This page was open for too long. Send the form again.', 'vance-health-hub' ),
		'failed'        => __( 'Not saved. The Hub could not store your data. Try again later.', 'vance-health-hub' ),
		'changed'       => __( 'Not saved. Your agreement is missing, or the agreement text changed. Read it again, then save your plan.', 'vance-health-hub' ),
		'kept'          => __( 'Not completed. The Hub could not delete your data or withdraw your agreement. Try again later.', 'vance-health-hub' ),
		'noplan'        => __( 'Not saved. Save your flare plan first.', 'vance-health-hub' ),
		'flare_started' => __( 'Your flare is logged. Look after yourself. Your plan is below.', 'vance-health-hub' ),
		'flare_ended'   => __( 'Your flare is logged as ended. Look at your plan: what would you change?', 'vance-health-hub' ),
		'flare_open'    => __( 'A flare is already open. End it first.', 'vance-health-hub' ),
		'flare_none'    => __( 'There is no open flare to end.', 'vance-health-hub' ),
	);
	$plan  = isset( $f['plan'] ) && is_array( $f['plan'] ) ? $f['plan'] : null;
	$flash = isset( $f['flash'] ) ? $f['flash'] : '';
	$nonce = '<input type="hidden" name="vance_hub_flare_nonce" value="' . esc_attr( $f['nonce'] ) . '">';

	$html  = '<section class="vhd-card vhd-flare" aria-labelledby="vhd-flare-title"><div class="vhd-card__head">';
	$html .= '<h2 id="vhd-flare-title" class="vhd-h3">' . esc_html__( 'My flare plan', 'vance-health-hub' ) . '</h2></div>';

	if ( isset( $messages[ $flash ] ) ) {
		$ok    = in_array( $flash, array( 'saved', 'deleted', 'flare_started', 'flare_ended' ), true );
		$html .= '<p class="vhd-flash' . ( $ok ? '' : ' vhd-flash--warn' ) . '" role="status">' . esc_html( $messages[ $flash ] ) . '</p>';
	}

	if ( null === $plan ) {
		// No plan yet: the step-by-step builder, open.
		$html .= vance_hub_flare_plan_form_html( $f, null, $nonce, true );
	} else {
		$sections = array(
			'signs'    => __( 'My early signs', 'vance-health-hub' ),
			'actions'  => __( 'What I do first', 'vance-health-hub' ),
			'contacts' => __( 'Who I contact', 'vance-health-hub' ),
		);
		$html .= vance_hub_flare_plan_progress_html( isset( $f['progress'] ) ? $f['progress'] : null );
		$html .= vance_hub_flare_plan_quick_html( isset( $f['quick'] ) ? $f['quick'] : null );
		$html .= '<div class="vhd-flare__plan">';
		foreach ( $sections as $key => $title ) {
			$text  = '' !== $plan[ $key ] ? vance_hub_flare_plan_text_html( $plan[ $key ] ) : '<em>' . esc_html__( 'Nothing written.', 'vance-health-hub' ) . '</em>';
			$html .= '<div class="vhd-flare__sec"><h3 class="vhd-flare__h">' . esc_html( $title ) . '</h3><p>' . $text . '</p></div>';
		}
		if ( '' !== $f['updated'] ) {
			/* translators: %s: date of the last change. */
			$html .= '<p class="vhd-small vhd-flare__date">' . esc_html( sprintf( __( 'Last changed on %s.', 'vance-health-hub' ), $f['updated'] ) ) . '</p>';
		}
		$html .= '</div>';
	}

	// Fixed text: shown to every member, with or without a plan.
	$html .= '<div class="vhd-flare__sec vhd-flare__urgent"><h3 class="vhd-flare__h">' . esc_html__( 'When to get urgent help', 'vance-health-hub' ) . '</h3><p>' . vance_hub_flare_plan_text_html( $f['urgent'] ) . '</p></div>';

	if ( '' !== $f['note'] ) {
		$html .= '<p class="vhd-small">' . esc_html( $f['note'] ) . '</p>';
	}

	if ( null !== $plan ) {
		if ( ! empty( $f['need_consent'] ) ) {
			// The agreement is missing or its wording changed: the log buttons would
			// only fail. Point the member to the plan form, which asks again.
			$html .= '<section class="vhd-flare__log" aria-labelledby="vhd-flare-log-title"><h3 id="vhd-flare-log-title" class="vhd-flare__h">' . esc_html__( 'My flare log', 'vance-health-hub' ) . '</h3><p>' . esc_html__( 'To use the flare log, read the agreement again and save your plan. Open "Edit my plan" below.', 'vance-health-hub' ) . '</p></section>';
		} else {
			$html .= vance_hub_flare_plan_log_html( isset( $f['log'] ) ? $f['log'] : array(), $nonce );
		}
		$html .= '<div class="vhd-flare__actions">';
		// Hidden until hub-flare-plan.js shows it: with no script it does nothing.
		$html .= '<button type="button" class="vhd-btn vhd-btn--outline vhd-flare__print" data-vhd-flare-print="plan" hidden>' . esc_html__( 'Print my plan', 'vance-health-hub' ) . '</button></div>';
		$html .= '<details class="vhd-flare__sheetbox"><summary>' . esc_html__( 'Appointment sheet', 'vance-health-hub' ) . '</summary>' . vance_hub_flare_plan_sheet_html( isset( $f['sheet'] ) ? $f['sheet'] : null, $plan ) . '</details>';
		// After a flare ends the edit box opens: the member can change the plan.
		$html .= '<details class="vhd-flare__edit"' . ( 'flare_ended' === $flash || ! empty( $f['need_consent'] ) ? ' open' : '' ) . '><summary>' . esc_html__( 'Edit my plan', 'vance-health-hub' ) . '</summary>' . vance_hub_flare_plan_form_html( $f, $plan, $nonce, false ) . '</details>';
		$html .= '<details class="vhd-flare__manage"><summary>' . esc_html__( 'Manage my flare plan data', 'vance-health-hub' ) . '</summary>';
		$html .= '<p>' . esc_html__( 'You cannot get your plan and your flare log back after you delete them.', 'vance-health-hub' ) . '</p>';
		$html .= '<form method="post" action="">' . $nonce . '<input type="hidden" name="vance_hub_flare_do" value="delete">';
		$html .= '<button type="submit" class="vhd-btn vhd-btn--outline">' . esc_html__( 'Delete my plan and flare log, and withdraw my agreement', 'vance-health-hub' ) . '</button></form></details>';
	}

	$html .= '</section>';
	return $html;
}
