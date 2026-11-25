<?php
/**
 * VanceHubClub member dashboard: the flare plan.
 *
 * One personal plan for each member, shown as a card on the dashboard home
 * (inc/hub-dashboard.php). The member writes three answers in their own words,
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
		if ( function_exists( 'mb_substr' ) ) {
			$text = mb_substr( $text, 0, $limit );
		} elseif ( 1 === preg_match( '/^.{0,' . (int) $limit . '}/su', $text, $match ) ) {
			// No mbstring on this server: cut by characters with PCRE, never by bytes.
			$text = $match[0];
		} else {
			$text = substr( $text, 0, $limit );
		}
		if ( '' !== $text ) {
			$any = true;
		}
		$clean[ $key ] = $text;
	}
	return $any ? $clean : null;
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
		'vance_hub_flare_plan_consent' => __( 'I agree that the Hub stores my flare plan in my account. I can delete it at any time.', 'vance-health-hub' ),
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
		'description' => __( 'A card where the member writes their own plan for a flare. It is health data: turn it on only when the privacy policy covers it, and the urgent help text below is set. It shows only on the VanceHubClub dashboard home.', 'vance-health-hub' ),
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
	$locked = ( 'delete' === $do || 'save' === $do ) && vance_hub_checkin_lock( $uid );

	if ( 'delete' === $do && ! $locked ) {
		$result = 'kept';
	} elseif ( 'save' === $do && ! $locked ) {
		$result = 'failed';
	} elseif ( 'delete' === $do ) {
		// Delete the plan AND withdraw the agreement. The time and the wording
		// of the old agreement stay as a record.
		delete_user_meta( $uid, vance_hub_flare_plan_meta_key() );
		if ( ! empty( get_user_meta( $uid, vance_hub_flare_plan_meta_key(), true ) ) ) {
			$result = 'kept';
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
	return array(
		'plan'         => $plan,
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
 * The flare plan card.
 *
 * @param array|null $f 'flare_plan' from vance_hub_dashboard_features_data().
 * @return string
 */
function vance_hub_flare_plan_html( $f ) {
	// Not empty(): the text "0" is set text, as in vance_hub_flare_plan_available().
	if ( empty( $f ) || ! isset( $f['urgent'] ) || '' === trim( (string) $f['urgent'] ) ) {
		return '';
	}
	$messages = array(
		'saved'   => __( 'Your flare plan is saved.', 'vance-health-hub' ),
		'deleted' => __( 'Your flare plan is deleted, and your agreement is withdrawn.', 'vance-health-hub' ),
		'consent' => __( 'Not saved. Tick the box to agree, then send the form again.', 'vance-health-hub' ),
		'invalid' => __( 'Not saved. Write at least one answer, then send the form again.', 'vance-health-hub' ),
		'expired' => __( 'Not saved. This page was open for too long. Send the form again.', 'vance-health-hub' ),
		'failed'  => __( 'Not saved. The Hub could not store your plan. Try again later.', 'vance-health-hub' ),
		'changed' => __( 'Not saved. The agreement text changed. Read it again, then send the form.', 'vance-health-hub' ),
		'kept'    => __( 'Not completed. The Hub could not delete your plan or withdraw your agreement. Try again later.', 'vance-health-hub' ),
	);
	$plan  = isset( $f['plan'] ) && is_array( $f['plan'] ) ? $f['plan'] : null;
	$nonce = '<input type="hidden" name="vance_hub_flare_nonce" value="' . esc_attr( $f['nonce'] ) . '">';

	$html  = '<section class="vhd-card vhd-flare" aria-labelledby="vhd-flare-title"><div class="vhd-card__head">';
	$html .= '<h2 id="vhd-flare-title" class="vhd-h3">' . esc_html__( 'My flare plan', 'vance-health-hub' ) . '</h2></div>';

	if ( isset( $f['flash'] ) && isset( $messages[ $f['flash'] ] ) ) {
		$ok    = in_array( $f['flash'], array( 'saved', 'deleted' ), true );
		$html .= '<p class="vhd-flash' . ( $ok ? '' : ' vhd-flash--warn' ) . '" role="status">' . esc_html( $messages[ $f['flash'] ] ) . '</p>';
	}

	// The form: always open with no plan, inside "Edit my plan" with a plan.
	$form  = '<form class="vhd-flare__form" method="post" action="">' . $nonce;
	$form .= '<input type="hidden" name="vance_hub_flare_do" value="save">';
	$form .= vance_hub_flare_plan_fields_html( $plan, null === $plan ? 'vhd-flare' : 'vhd-flare-edit' );
	if ( ! empty( $f['need_consent'] ) ) {
		$form .= '<input type="hidden" name="vhd_consent_ref" value="' . esc_attr( vance_hub_checkin_consent_ref( $f['consent'] ) ) . '">';
		$form .= '<label class="vhd-flare__consent"><input type="checkbox" name="vhd_consent" value="1" required> <span>' . esc_html( $f['consent'] ) . '</span></label>';
	}
	$form .= '<button type="submit" class="vhd-btn vhd-btn--purple">' . esc_html__( 'Save my plan', 'vance-health-hub' ) . '</button></form>';

	if ( null === $plan ) {
		$html .= $form;
	} else {
		$sections = array(
			'signs'    => __( 'My early signs', 'vance-health-hub' ),
			'actions'  => __( 'What I do first', 'vance-health-hub' ),
			'contacts' => __( 'Who I contact', 'vance-health-hub' ),
		);
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
		$html .= '<div class="vhd-flare__actions">';
		// Hidden until hub-flare-plan.js shows it: with no script it does nothing.
		$html .= '<button type="button" class="vhd-btn vhd-btn--outline vhd-flare__print" data-vhd-flare-print hidden>' . esc_html__( 'Print my plan', 'vance-health-hub' ) . '</button></div>';
		$html .= '<details class="vhd-flare__edit"><summary>' . esc_html__( 'Edit my plan', 'vance-health-hub' ) . '</summary>' . $form . '</details>';
		$html .= '<details class="vhd-flare__manage"><summary>' . esc_html__( 'Manage my flare plan data', 'vance-health-hub' ) . '</summary>';
		$html .= '<p>' . esc_html__( 'You cannot get your plan back after you delete it.', 'vance-health-hub' ) . '</p>';
		$html .= '<form method="post" action="">' . $nonce . '<input type="hidden" name="vance_hub_flare_do" value="delete">';
		$html .= '<button type="submit" class="vhd-btn vhd-btn--outline">' . esc_html__( 'Delete my plan and withdraw my agreement', 'vance-health-hub' ) . '</button></form></details>';
	}

	$html .= '</section>';
	return $html;
}
