<?php
/**
 * VanceHubClub daily check-in: the screens.
 *
 * The rules, the storage and the save handler are in hub-dashboard-features.php.
 * This file only draws things from the array that vance_hub_dashboard_features_data()
 * returns under 'checkin':
 *
 *   - the home card (form when a check-in is due, "done" line when not);
 *   - the pop-up that opens on the first visit of the day (vance_hub_checkin_modal_html());
 *   - the "Daily check-ins" section of My Health Profile (vance_hub_checkin_tab_html()),
 *     with the table, the charts and the delete control.
 *
 * Answers are shown as emoji faces. The stored values stay the numbers 0 to 4.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The spoken name of each point of the scale, for screen readers and tooltips.
 *
 * @return string[] Answer => name.
 */
function vance_hub_checkin_face_names() {
	return array(
		0 => __( 'None', 'vance-health-hub' ),
		1 => __( 'A little', 'vance-health-hub' ),
		2 => __( 'Some', 'vance-health-hub' ),
		3 => __( 'A lot', 'vance-health-hub' ),
		4 => __( 'Very bad', 'vance-health-hub' ),
	);
}

/**
 * The flash messages of the save and delete handler.
 *
 * @return string[] Code => text.
 */
function vance_hub_checkin_messages() {
	return array(
		'saved'   => __( 'Your check-in is saved.', 'vance-health-hub' ),
		'deleted' => __( 'Your check-in answers are deleted, and your agreement is withdrawn.', 'vance-health-hub' ),
		'consent' => __( 'Not saved. Tick the box to agree, then send the form again.', 'vance-health-hub' ),
		'early'   => __( 'Not saved. You did your check-in today.', 'vance-health-hub' ),
		'invalid' => __( 'Not saved. Answer all the questions, then send the form again.', 'vance-health-hub' ),
		'expired' => __( 'Not saved. This page was open for too long. Answer the questions again, then send the form.', 'vance-health-hub' ),
		'failed'  => __( 'Not saved. The Hub could not store your answers. Try again later.', 'vance-health-hub' ),
		'changed' => __( 'Not saved. The agreement text changed. Read it again, then send the form.', 'vance-health-hub' ),
		'kept'    => __( 'Not completed. The Hub could not delete your answers or withdraw your agreement. Try again later.', 'vance-health-hub' ),
	);
}

/**
 * The flash line, or ''.
 *
 * @param array $c 'checkin' data.
 * @return string
 */
function vance_hub_checkin_flash_html( $c ) {
	$messages = vance_hub_checkin_messages();
	if ( empty( $c['flash'] ) || ! isset( $messages[ $c['flash'] ] ) ) {
		return '';
	}
	$ok = in_array( $c['flash'], array( 'saved', 'deleted' ), true );
	return '<p class="vhd-flash' . ( $ok ? '' : ' vhd-flash--warn' ) . '" role="status">' . esc_html( $messages[ $c['flash'] ] ) . '</p>';
}

/**
 * The check-in form: one row of five faces for each question.
 *
 * @param array  $c      'checkin' data.
 * @param string $prefix Prefix of the field ids, so the card and the pop-up can both hold a form.
 * @return string
 */
function vance_hub_checkin_form_html( $c, $prefix ) {
	$faces = vance_hub_checkin_faces();
	$names = vance_hub_checkin_face_names();
	$icons = vance_hub_checkin_icons();
	$max   = (int) $c['max'];

	$html  = '<form class="vhd-checkin__form" method="post" action="">';
	$html .= '<input type="hidden" name="vance_hub_checkin_nonce" value="' . esc_attr( $c['nonce'] ) . '">';
	$html .= '<input type="hidden" name="vance_hub_checkin_do" value="save">';
	foreach ( $c['questions'] as $key => $question ) {
		$icon  = isset( $icons[ $key ] ) ? $icons[ $key ][0] : '';
		$html .= '<fieldset class="vhd-checkin__q"><legend><span class="vhd-checkin__icon" aria-hidden="true">' . esc_html( $icon ) . '</span> ' . esc_html( $question ) . '</legend>';
		$html .= '<div class="vhd-checkin__opts vhd-checkin__opts--faces">';
		for ( $n = 0; $n <= $max; $n++ ) {
			$id    = $prefix . '-' . $key . '-' . $n;
			$name  = isset( $names[ $n ] ) ? $names[ $n ] : (string) $n;
			$html .= '<input class="vhd-sr" type="radio" id="' . esc_attr( $id ) . '" name="vhd_checkin[' . esc_attr( $key ) . ']" value="' . $n . '" required>';
			$html .= '<label for="' . esc_attr( $id ) . '" title="' . esc_attr( $name ) . '"><span aria-hidden="true">' . esc_html( $faces[ $n ] ) . '</span><span class="vhd-sr">' . esc_html( $name ) . '</span></label>';
		}
		$html .= '</div></fieldset>';
	}
	if ( ! empty( $c['need_consent'] ) ) {
		// The reference of the wording shown here. The save refuses a tick
		// for wording that is not the wording in use at that time.
		$html .= '<input type="hidden" name="vhd_consent_ref" value="' . esc_attr( vance_hub_checkin_consent_ref( $c['consent'] ) ) . '">';
		$html .= '<label class="vhd-checkin__consent"><input type="checkbox" name="vhd_consent" value="1" required> <span>' . esc_html( $c['consent'] ) . '</span></label>';
	}
	$html .= '<button type="submit" class="vhd-btn vhd-btn--purple">' . esc_html__( 'Save my check-in', 'vance-health-hub' ) . ' <span aria-hidden="true">&#10003;</span></button>';
	$html .= '</form>';
	return $html;
}

/**
 * The home card: the form when a check-in is due, a short "done" line when not.
 * The answers and charts are in My Health Profile.
 *
 * @param array|null $c 'checkin' from vance_hub_dashboard_features_data().
 * @return string
 */
function vance_hub_dashboard_checkin_html( $c ) {
	if ( empty( $c ) || empty( $c['questions'] ) ) {
		return '';
	}
	$html  = '<section class="vhd-card vhd-checkin" aria-labelledby="vhd-checkin-title"><div class="vhd-card__head">';
	$html .= '<h2 id="vhd-checkin-title" class="vhd-h3">' . esc_html__( 'My daily check-in', 'vance-health-hub' ) . '</h2></div>';
	$html .= vance_hub_checkin_flash_html( $c );

	if ( ! empty( $c['due'] ) ) {
		$html .= vance_hub_checkin_form_html( $c, 'vhd-checkin' );
	} else {
		/* translators: %s: date of the last check-in. */
		$html .= '<p class="vhd-checkin__done"><span aria-hidden="true">&#9989;</span> ' . esc_html( sprintf( __( 'Done for today (%s). Come back tomorrow.', 'vance-health-hub' ), $c['last'] ) ) . '</p>';
	}
	if ( ! empty( $c['tab_href'] ) ) {
		$html .= '<a class="vhd-link" href="' . esc_url( $c['tab_href'] ) . '">' . esc_html__( 'See my check-ins', 'vance-health-hub' ) . ' <span aria-hidden="true">&rarr;</span></a>';
	}
	if ( '' !== $c['note'] ) {
		$html .= '<p class="vhd-small">' . esc_html( $c['note'] ) . '</p>';
	}
	$html .= '</section>';
	return $html;
}

/**
 * The pop-up for the first visit of the day. '' when no check-in is due, or when
 * the member just sent the form (the home card then shows the result).
 *
 * The script opens it once for each calendar day and browser. A cookie holds the
 * day it opened. Closing it, or saving, does not bring it back the same day.
 *
 * @param array|null $c 'checkin' data.
 * @return string
 */
function vance_hub_checkin_modal_html( $c ) {
	if ( empty( $c ) || empty( $c['questions'] ) || empty( $c['due'] ) || ! empty( $c['flash'] ) ) {
		return '';
	}
	$day = (string) wp_date( 'Y-m-d', time() );

	$html  = '<div class="vhd-modal" id="vhd-checkin-modal" data-day="' . esc_attr( $day ) . '" data-uid="' . (int) get_current_user_id() . '" hidden>';
	$html .= '<div class="vhd-modal__backdrop" data-vhd-close></div>';
	$html .= '<div class="vhd-modal__box" role="dialog" aria-modal="true" aria-labelledby="vhd-checkin-modal-title" tabindex="-1">';
	$html .= '<button type="button" class="vhd-modal__x" data-vhd-close aria-label="' . esc_attr( __( 'Close', 'vance-health-hub' ) ) . '"><span aria-hidden="true">&times;</span></button>';
	$html .= '<h2 id="vhd-checkin-modal-title" class="vhd-h3"><span aria-hidden="true">&#128075;</span> ' . esc_html__( 'Quick check-in', 'vance-health-hub' ) . '</h2>';
	$html .= '<p class="vhd-small">' . esc_html__( 'Tap a face for each one. It takes 10 seconds.', 'vance-health-hub' ) . '</p>';
	$html .= vance_hub_checkin_form_html( $c, 'vhd-ci-modal' );
	$html .= '<button type="button" class="vhd-link vhd-modal__later" data-vhd-close>' . esc_html__( 'Not now', 'vance-health-hub' ) . '</button>';
	$html .= '</div></div>';

	// Plain script, no dependency. Every storage call is in try/catch: a browser
	// that blocks cookies just shows the pop-up on each visit to the page.
	$html .= '<script>(function(){var m=document.getElementById("vhd-checkin-modal");if(!m){return;}'
		. 'var day=m.getAttribute("data-day"),name="vhd_ci_seen_"+m.getAttribute("data-uid"),seen="";'
		. 'try{var p=document.cookie.split("; ");for(var i=0;i<p.length;i++){if(p[i].indexOf(name+"=")===0){seen=p[i].substring(name.length+1);}}}catch(e){}'
		. 'if(seen===day){return;}'
		. 'var box=m.querySelector(".vhd-modal__box"),back=document.activeElement;'
		. 'function mark(){try{document.cookie=name+"="+day+"; max-age=172800; path=/; SameSite=Lax";}catch(e){}}'
		. 'function close(){m.hidden=true;document.documentElement.classList.remove("vhd-modal-open");document.removeEventListener("keydown",key);if(back&&back.focus){try{back.focus();}catch(e){}}}'
		. 'function key(e){if(e.key==="Escape"){close();return;}if(e.key!=="Tab"){return;}'
		. 'var all=box.querySelectorAll("button,input,a[href]");if(!all.length){return;}var a=all[0],z=all[all.length-1];'
		. 'if(e.shiftKey&&(document.activeElement===a||document.activeElement===box)){e.preventDefault();z.focus();}else if(!e.shiftKey&&document.activeElement===z){e.preventDefault();a.focus();}}'
		. 'm.hidden=false;mark();document.documentElement.classList.add("vhd-modal-open");document.addEventListener("keydown",key);'
		. 'Array.prototype.forEach.call(m.querySelectorAll("[data-vhd-close]"),function(b){b.addEventListener("click",close);});'
		. 'box.focus();})();</script>';
	return $html;
}

/**
 * The "Daily check-ins" section of My Health Profile: the last 14 days as a
 * table of faces, one chart for each question, and the delete control.
 *
 * @param array|null $c 'checkin' data.
 * @return string '' when the feature is off.
 */
function vance_hub_checkin_tab_html( $c ) {
	if ( empty( $c ) || empty( $c['questions'] ) ) {
		return '';
	}
	$faces = vance_hub_checkin_faces();
	$names = vance_hub_checkin_face_names();
	$icons = vance_hub_checkin_icons();

	$html  = '<div class="vhd"><section class="vhd-card vhd-checkin" aria-labelledby="vhd-ci-tab-title"><div class="vhd-card__head">';
	$html .= '<h2 id="vhd-ci-tab-title" class="vhd-h3">' . esc_html__( 'My daily check-ins', 'vance-health-hub' ) . '</h2>';
	if ( ! empty( $c['due'] ) && function_exists( 'vance_hub_dashboard_tab_url' ) ) {
		$html .= '<a class="vhd-link" href="' . esc_url( vance_hub_dashboard_tab_url( 'home' ) ) . '">' . esc_html__( 'Check in today', 'vance-health-hub' ) . ' <span aria-hidden="true">&rarr;</span></a>';
	}
	$html .= '</div>';
	$html .= vance_hub_checkin_flash_html( $c );

	if ( empty( $c['days'] ) ) {
		$html .= '<p class="vhd-checkin__done"><span aria-hidden="true">&#128203;</span> ' . esc_html__( 'No check-ins yet. Your first one shows here.', 'vance-health-hub' ) . '</p>';
	} else {
		$html .= '<div class="vhd-checkin__tablewrap"><table class="vhd-checkin__table"><caption class="vhd-sr">' . esc_html__( 'Your last check-ins', 'vance-health-hub' ) . '</caption><thead><tr><th scope="col">' . esc_html__( 'Day', 'vance-health-hub' ) . '</th>';
		foreach ( $icons as $key => $icon ) {
			$html .= '<th scope="col"><span aria-hidden="true">' . esc_html( $icon[0] ) . '</span> <span class="vhd-checkin__colname">' . esc_html( $icon[1] ) . '</span></th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( $c['days'] as $day ) {
			$html .= '<tr><th scope="row">' . esc_html( $day['date'] ) . '</th>';
			foreach ( $icons as $key => $icon ) {
				$n = isset( $day['answers'][ $key ] ) ? $day['answers'][ $key ] : null;
				if ( null !== $n && isset( $faces[ $n ] ) ) {
					$html .= '<td><span aria-hidden="true">' . esc_html( $faces[ $n ] ) . '</span><span class="vhd-sr">' . esc_html( $names[ $n ] ) . '</span></td>';
				} else {
					$html .= '<td><span aria-hidden="true">&ndash;</span><span class="vhd-sr">' . esc_html__( 'Not answered', 'vance-health-hub' ) . '</span></td>';
				}
			}
			$html .= '</tr>';
		}
		$html .= '</tbody></table></div>';
		$html .= '<p class="vhd-small vhd-checkin__key">' . esc_html( implode( '  ', array_map( function ( $n ) use ( $faces, $names ) {
			return $faces[ $n ] . ' ' . $names[ $n ];
		}, array_keys( $faces ) ) ) ) . '</p>';
	}

	// One small chart for each question. Higher is worse.
	$charts = '';
	foreach ( $c['questions'] as $key => $question ) {
		$chart = isset( $c['series'][ $key ] ) ? vance_hub_dashboard_chart_html( $c['series'][ $key ], $question ) : '';
		if ( '' !== $chart ) {
			$icon    = isset( $icons[ $key ] ) ? $icons[ $key ][0] . ' ' . $icons[ $key ][1] : $question;
			$charts .= '<div class="vhd-checkin__trend"><p class="vhd-checkin__trend-q">' . esc_html( $icon ) . '</p>' . $chart . '</div>';
		}
	}
	if ( '' !== $charts ) {
		$html .= '<div class="vhd-checkin__trends">' . $charts . '</div>';
		$html .= '<p class="vhd-small">' . esc_html__( 'Lower on the chart is better.', 'vance-health-hub' ) . '</p>';
	}
	if ( '' !== $c['note'] ) {
		$html .= '<p class="vhd-small">' . esc_html( $c['note'] ) . '</p>';
	}

	if ( (int) $c['count'] > 0 ) {
		$nonce = '<input type="hidden" name="vance_hub_checkin_nonce" value="' . esc_attr( $c['nonce'] ) . '">';
		$html .= '<details class="vhd-checkin__manage"><summary>' . esc_html__( 'Manage my check-in data', 'vance-health-hub' ) . '</summary>';
		/* translators: %s: number of saved check-ins. */
		$html .= '<p>' . esc_html( sprintf( _n( 'You have %s saved check-in.', 'You have %s saved check-ins.', (int) $c['count'], 'vance-health-hub' ), number_format_i18n( (int) $c['count'] ) ) ) . ' ' . esc_html__( 'You cannot get them back after you delete them.', 'vance-health-hub' ) . '</p>';
		$html .= '<form method="post" action="">' . $nonce . '<input type="hidden" name="vance_hub_checkin_do" value="delete">';
		$html .= '<button type="submit" class="vhd-btn vhd-btn--outline">' . esc_html__( 'Delete my answers and withdraw my agreement', 'vance-health-hub' ) . '</button></form></details>';
	}

	$html .= '</section></div>';
	return $html;
}

/**
 * The check-in data for My Health Profile. Reads only.
 *
 * @param WP_User $user Member.
 * @return array|null 'checkin' data, null when the feature is off.
 */
function vance_hub_checkin_tab_data( $user ) {
	if ( ! function_exists( 'vance_hub_dash_feature_on' ) || ! vance_hub_dash_feature_on( 'vance_hub_dash_checkin' ) ) {
		return null;
	}
	// The section needs hub-dashboard.css, and the check-in form is on the hub home.
	if ( function_exists( 'vance_hub_dashboard_active' ) && ! vance_hub_dashboard_active() ) {
		return null;
	}
	$data = vance_hub_dashboard_features_data( $user, array(), '' );
	return isset( $data['checkin'] ) ? $data['checkin'] : null;
}
