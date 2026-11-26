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
 * When the switch is off or the urgent help text is empty, a member who has a
 * stored plan still gets a read-only card (see, print, delete), and delete and
 * "remove one log entry" still work (vance_hub_flare_plan_can()).
 *
 * What this file WRITES:
 *   transient vance_flare_draft_<uid>      what the member typed, 10 minutes, after a refused request
 *   _sla_flare_log                         [{id, start, end, helped}]
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
	$clean = array();
	$any   = false;
	foreach ( vance_hub_flare_plan_keys() as $key ) {
		$text = isset( $answers[ $key ] ) && is_string( $answers[ $key ] ) ? $answers[ $key ] : '';
		// No cut here: an over-length answer is refused (vance_hub_flare_plan_too_long()).
		$text = vance_hub_flare_plan_clean_text( $text );
		if ( '' !== $text ) {
			$any = true;
		}
		$clean[ $key ] = $text;
	}
	return $any ? $clean : null;
}

/**
 * Clean one member text. Pure. The text is stored as typed: no tag is removed
 * and no entity is changed, because every output is escaped with esc_html() or
 * esc_textarea(). A "<", ">", "&" or "%41" typed by the member survives.
 *
 * @param mixed $text Text, already unslashed.
 * @return string Text with LF line breaks, no control character except LF and TAB, trimmed.
 */
function vance_hub_flare_plan_clean_text( $text ) {
	$text = is_scalar( $text ) ? (string) $text : '';
	$text = wp_check_invalid_utf8( $text );
	$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
	$text = (string) preg_replace( '/[\x00-\x08\x0B-\x1F\x7F]/', '', $text );
	return trim( $text );
}

/**
 * The length of a text in characters, not bytes.
 *
 * @param string $text Text.
 * @return int
 */
function vance_hub_flare_plan_char_count( $text ) {
	$text = (string) $text;
	if ( function_exists( 'mb_strlen' ) ) {
		return (int) mb_strlen( $text );
	}
	// No mbstring on this server: count characters with PCRE, never bytes.
	$count = preg_match_all( '/./su', $text );
	return false === $count ? strlen( $text ) : (int) $count;
}

/**
 * True when any answer is longer than vance_hub_flare_plan_max_chars().
 *
 * @param mixed $answers key => cleaned text.
 * @return bool
 */
function vance_hub_flare_plan_too_long( $answers ) {
	if ( ! is_array( $answers ) ) {
		return false;
	}
	$limit = vance_hub_flare_plan_max_chars();
	foreach ( vance_hub_flare_plan_keys() as $key ) {
		if ( isset( $answers[ $key ] ) && is_string( $answers[ $key ] ) && vance_hub_flare_plan_char_count( $answers[ $key ] ) > $limit ) {
			return true;
		}
	}
	return false;
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
 * @param bool   $tel  True to link telephone numbers (see vance_hub_flare_plan_tel_pattern()).
 * @return string HTML.
 */
function vance_hub_flare_plan_text_html( $text, $tel = false ) {
	$text = (string) $text;
	if ( ! $tel ) {
		return nl2br( esc_html( $text ) );
	}
	// The matches are found in the raw text and each piece is escaped on its own,
	// so an entity made by esc_html() can never be read as part of a number.
	$html = '';
	$pos  = 0;
	if ( preg_match_all( vance_hub_flare_plan_tel_pattern(), $text, $found, PREG_OFFSET_CAPTURE ) ) {
		foreach ( $found[0] as $hit ) {
			$match  = $hit[0];
			$offset = (int) $hit[1];
			$href   = vance_hub_flare_plan_tel_href( $match );
			if ( '' === $href ) {
				continue;
			}
			$html .= esc_html( substr( $text, $pos, $offset - $pos ) ) . '<a href="' . esc_attr( 'tel:' . $href ) . '">' . esc_html( $match ) . '</a>';
			$pos   = $offset + strlen( $match );
		}
	}
	$html .= esc_html( substr( $text, $pos ) );
	return nl2br( $html );
}

/**
 * The pattern that finds a telephone number in member text. Two kinds only:
 * a number that starts with + or 0 and is made of digits, spaces, hyphens and
 * round brackets, and the short codes 999, 111 and 112.
 *
 * The long form ends on a digit, so a closing bracket after the number stays
 * outside the link. A short code links only after "call", "dial", "ring",
 * "phone" or "NHS", after another short code and "or" ("111 or 999"), or when
 * it is alone on its line: "pulse 112" and "112 mg" are not telephone numbers.
 * vance_hub_flare_plan_tel_href() then checks the digit count and refuses dates
 * and lists of single digits.
 *
 * @return string PCRE pattern.
 */
function vance_hub_flare_plan_tel_pattern() {
	return '/(?<![\w+\/\-])(?:\((?=0\d*\)))?(?:\+|0)(?:\d|[ \-](?=[\d(])|\)(?=[ \-]?\d)|\(\d+\))*\d|(?:(?<=\bcall )|(?<=\bdial )|(?<=\bring )|(?<=\bphone )|(?<=\bNHS )|(?<=\b(?:999|111|112) or ))(?:999|111|112)(?![\w\-\/:]|[.,]\d)|^(?:999|111|112)(?=[ \t]*$)/mi';
}

/**
 * The tel: target for one match of vance_hub_flare_plan_tel_pattern().
 *
 * @param string $match The matched text.
 * @return string Digits, with a leading + when the text had one. '' when the match is not a number to link.
 */
function vance_hub_flare_plan_tel_href( $match ) {
	$match = (string) $match;
	if ( 1 === preg_match( '/^(?:999|111|112)$/D', $match ) ) {
		return $match;
	}
	$digits = (string) preg_replace( '/\D/', '', $match );
	$count  = strlen( $digits );
	if ( $count < 9 || $count > 15 ) {
		return '';
	}
	// A list of single digits or pairs, such as scores "0 3 4 2 1 0 4 3 2", is not a number.
	if ( 1 !== preg_match( '/\d{3}/', $match ) ) {
		return '';
	}
	// A date written with hyphens or spaces, such as 01-12-2026 10, is not a number.
	if ( 1 === preg_match( '/^\d{1,2}[ \-]\d{1,2}[ \-]\d{4}(?!\d)/', $match ) ) {
		return '';
	}
	return ( '+' === $match[0] ? '+' : '' ) . $digits;
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
 * Who may do what. 'view', 'save' and 'log' need the card to be available (the
 * switch on and the urgent help text set). 'delete' (the plan, the whole log and
 * the removal of one log entry) needs only a logged-in member, so a member can
 * always delete what is stored, even while the card is switched off.
 *
 * @param string $what 'view', 'save', 'log' or 'delete'.
 * @return bool False for any other value.
 */
function vance_hub_flare_plan_can( $what ) {
	switch ( (string) $what ) {
		case 'view':
		case 'save':
		case 'log':
			return vance_hub_flare_plan_available();
		case 'delete':
			return is_user_logged_in();
	}
	return false;
}

/**
 * Ask page caches to skip the member's dashboard while the flare plan can show.
 * The page holds the plan, and the appointment sheet holds medication, check-in
 * scores and screening results. WordPress already sends no-cache headers to a
 * logged-in member; this adds the flag that page-cache plugins read.
 *
 * It does not depend on vance_hub_flare_plan_available(): the read-only card
 * (switch off, plan stored) shows the plan too.
 */
function vance_hub_flare_plan_no_cache() {
	if ( ! is_user_logged_in() || ! function_exists( 'is_page' ) ) {
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
		'description' => __( 'Fixed text that every member sees on their plan. A clinician must write or approve it. The card does not show while this is empty. Turning the switch off stops new plans and changes. A member who has a saved plan can still see, print and delete it.', 'vance-health-hub' ),
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
	// An over-length answer is refused, never cut.
	if ( $user_id <= 0 || null === $clean || vance_hub_flare_plan_too_long( $clean ) ) {
		return false;
	}
	$plan = array( 'updated' => (int) $now ) + $clean;
	// wp_slash: update_user_meta() removes one level of slashes, so text that
	// holds a backslash is stored changed with no slash added here.
	update_user_meta( $user_id, vance_hub_flare_plan_meta_key(), wp_slash( $plan ) );
	return vance_hub_flare_plan_get( $user_id ) === $plan;
}

/**
 * Put a raw stored value back after a failed write. '' means there was none:
 * the key is deleted.
 *
 * @param int    $user_id Member.
 * @param string $key     User meta key.
 * @param mixed  $raw     The value read with get_user_meta( ..., true ) before the write.
 */
function vance_hub_flare_plan_restore_raw( $user_id, $key, $raw ) {
	if ( '' === $raw || null === $raw || false === $raw ) {
		delete_user_meta( (int) $user_id, $key );
		return;
	}
	// wp_slash: update_user_meta() removes one level of slashes.
	update_user_meta( (int) $user_id, $key, wp_slash( $raw ) );
}

/* =========================================================================
 * Draft: what the member typed, kept after a refused request
 * ====================================================================== */

/**
 * The most characters of one draft field. A size cap for the transient, not the
 * member's limit.
 *
 * @return int
 */
function vance_hub_flare_plan_draft_cap() {
	return 4000;
}

/**
 * The transient name of a member's draft.
 *
 * @param int $user_id Member.
 * @return string
 */
function vance_hub_flare_plan_draft_key( $user_id ) {
	return 'vance_flare_draft_' . (int) $user_id;
}

/**
 * A draft, cleaned: the shape array( 'do', 'answers', 'helped' ) or null.
 *
 * @param mixed $draft Draft.
 * @return array|null null when the shape is wrong or every text is empty.
 */
function vance_hub_flare_plan_draft_normalise( $draft ) {
	if ( ! is_array( $draft ) || ! isset( $draft['do'] ) || ! in_array( $draft['do'], array( 'save', 'flare_end' ), true ) ) {
		return null;
	}
	$cap     = vance_hub_flare_plan_draft_cap();
	$source  = isset( $draft['answers'] ) && is_array( $draft['answers'] ) ? $draft['answers'] : array();
	$answers = array();
	$any     = false;
	foreach ( vance_hub_flare_plan_keys() as $key ) {
		$text = isset( $source[ $key ] ) && is_string( $source[ $key ] ) ? vance_hub_flare_plan_limit_text( vance_hub_flare_plan_clean_text( $source[ $key ] ), $cap ) : '';
		$any  = $any || '' !== $text;
		$answers[ $key ] = $text;
	}
	$helped = isset( $draft['helped'] ) && is_string( $draft['helped'] ) ? vance_hub_flare_plan_limit_text( vance_hub_flare_plan_clean_text( $draft['helped'] ), $cap ) : '';
	if ( ! $any && '' === $helped ) {
		return null;
	}
	return array( 'do' => $draft['do'], 'answers' => $answers, 'helped' => $helped );
}

/**
 * Keep a draft for 10 minutes.
 *
 * @param int   $user_id Member.
 * @param array $draft   array( 'do' => 'save'|'flare_end', 'answers' => array, 'helped' => string ).
 * @return bool True when a draft was stored.
 */
function vance_hub_flare_plan_draft_set( $user_id, $draft ) {
	$user_id = (int) $user_id;
	$draft   = vance_hub_flare_plan_draft_normalise( $draft );
	if ( $user_id <= 0 || null === $draft ) {
		return false;
	}
	return (bool) set_transient( vance_hub_flare_plan_draft_key( $user_id ), $draft, 600 );
}

/**
 * True when the browser says this request came from a page of this site.
 *
 * Used only where the nonce did not pass, to decide if typed text is kept.
 * It fails closed: with no header to judge by, the answer is false.
 *
 * Order: Sec-Fetch-Site (the browser sets it; a page cannot), then Origin,
 * then Referer. "same-site" is not enough: another host of the same domain is
 * not this site.
 *
 * @return bool
 */
function vance_hub_flare_plan_same_origin_request() {
	if ( isset( $_SERVER['HTTP_SEC_FETCH_SITE'] ) && is_string( $_SERVER['HTTP_SEC_FETCH_SITE'] ) && '' !== $_SERVER['HTTP_SEC_FETCH_SITE'] ) {
		return 'same-origin' === strtolower( trim( $_SERVER['HTTP_SEC_FETCH_SITE'] ) );
	}
	$home = wp_parse_url( home_url() );
	if ( ! is_array( $home ) || empty( $home['host'] ) ) {
		return false;
	}
	foreach ( array( 'HTTP_ORIGIN', 'HTTP_REFERER' ) as $name ) {
		if ( ! isset( $_SERVER[ $name ] ) || ! is_string( $_SERVER[ $name ] ) || '' === trim( $_SERVER[ $name ] ) ) {
			continue;
		}
		// The first header that is there decides. It is compared only, never stored or output.
		$from = wp_parse_url( trim( wp_unslash( $_SERVER[ $name ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! is_array( $from ) || empty( $from['host'] ) || empty( $from['scheme'] ) ) {
			return false;
		}
		$same_port = ( isset( $from['port'] ) ? (int) $from['port'] : 0 ) === ( isset( $home['port'] ) ? (int) $home['port'] : 0 );
		return strtolower( $from['host'] ) === strtolower( $home['host'] ) && strtolower( $from['scheme'] ) === strtolower( isset( $home['scheme'] ) ? $home['scheme'] : '' ) && $same_port;
	}
	return false;
}

/**
 * Take the draft: return it and delete it.
 *
 * @param int $user_id Member.
 * @return array|null
 */
function vance_hub_flare_plan_draft_take( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return null;
	}
	$draft = get_transient( vance_hub_flare_plan_draft_key( $user_id ) );
	delete_transient( vance_hub_flare_plan_draft_key( $user_id ) );
	return vance_hub_flare_plan_draft_normalise( $draft );
}

/**
 * Delete the draft.
 *
 * @param int $user_id Member.
 */
function vance_hub_flare_plan_draft_clear( $user_id ) {
	if ( (int) $user_id > 0 ) {
		delete_transient( vance_hub_flare_plan_draft_key( (int) $user_id ) );
	}
}

/**
 * The draft of the current POST request: what the member typed, unslashed.
 *
 * @param string $do 'save' or 'flare_end'. Any other value gives null.
 * @return array|null array( 'do', 'answers', 'helped' ) before cleaning.
 */
function vance_hub_flare_plan_draft_from_post( $do ) {
	if ( ! in_array( $do, array( 'save', 'flare_end' ), true ) ) {
		return null;
	}
	$answers = array();
	// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- unslashed here, cleaned by vance_hub_flare_plan_draft_set().
	$posted = isset( $_POST['vhd_flare'] ) && is_array( $_POST['vhd_flare'] ) ? $_POST['vhd_flare'] : array();
	foreach ( vance_hub_flare_plan_keys() as $key ) {
		$answers[ $key ] = isset( $posted[ $key ] ) && is_string( $posted[ $key ] ) ? wp_unslash( $posted[ $key ] ) : '';
	}
	$helped = isset( $_POST['vhd_flare_helped'] ) && is_string( $_POST['vhd_flare_helped'] ) ? wp_unslash( $_POST['vhd_flare_helped'] ) : '';
	// phpcs:enable
	return array( 'do' => $do, 'answers' => $answers, 'helped' => $helped );
}

/**
 * The result codes that show a warning. The codes that show success are in
 * vance_hub_flare_plan_success_codes().
 *
 * @return string[]
 */
function vance_hub_flare_plan_error_codes() {
	return array( 'consent', 'invalid', 'toolong', 'expired', 'failed', 'changed', 'kept', 'partial', 'noplan', 'flare_open', 'flare_none' );
}

/**
 * The result codes that show success.
 *
 * @return string[]
 */
function vance_hub_flare_plan_success_codes() {
	return array( 'saved', 'deleted', 'flare_started', 'flare_started_trimmed', 'flare_ended', 'flare_removed' );
}

/**
 * What is stored now, for the delete.
 *
 * @param int $user_id Member.
 * @return array {log:bool, plan:bool, consent:bool}, true = present.
 */
function vance_hub_flare_plan_stored_state( $user_id ) {
	$user_id = (int) $user_id;
	$keys    = vance_hub_checkin_consent_keys( 'flare_plan' );
	return array(
		'log'     => ! empty( get_user_meta( $user_id, vance_hub_flare_log_meta_key(), true ) ),
		'plan'    => ! empty( get_user_meta( $user_id, vance_hub_flare_plan_meta_key(), true ) ),
		'consent' => ! empty( get_user_meta( $user_id, $keys[0], true ) ),
	);
}

/**
 * Write the time the agreement was withdrawn. It is read back once and written
 * once more when the read-back does not match.
 *
 * @param int $user_id Member.
 * @return bool
 */
function vance_hub_flare_plan_write_withdrawn( $user_id ) {
	$user_id = (int) $user_id;
	$keys    = vance_hub_checkin_consent_keys( 'flare_plan' );
	$key     = $keys[0] . '_withdrawn_at';
	$at      = (string) current_time( 'mysql' );
	for ( $try = 0; $try < 2; $try++ ) {
		update_user_meta( $user_id, $key, $at );
		if ( get_user_meta( $user_id, $key, true ) === $at ) {
			return true;
		}
	}
	return false;
}

/**
 * The one delete: the flare log, then the plan, then the agreement flag (and
 * then the time of the withdrawal). The caller holds the member lock. Each part
 * is read back after its delete, and the run stops at the first part that is
 * still there. The draft is always deleted.
 *
 * @param int $user_id Member.
 * @return array {log:bool, plan:bool, consent:bool, ok:bool}. A flag is true when that part is now absent. ok is true when all three are.
 */
function vance_hub_flare_plan_delete_all( $user_id ) {
	$user_id = (int) $user_id;
	$out     = array( 'log' => false, 'plan' => false, 'consent' => false, 'ok' => false );
	if ( $user_id <= 0 ) {
		return $out;
	}
	vance_hub_flare_plan_draft_clear( $user_id );
	$keys = vance_hub_checkin_consent_keys( 'flare_plan' );

	delete_user_meta( $user_id, vance_hub_flare_log_meta_key() );
	$out['log'] = empty( get_user_meta( $user_id, vance_hub_flare_log_meta_key(), true ) );
	if ( $out['log'] ) {
		delete_user_meta( $user_id, vance_hub_flare_plan_meta_key() );
		$out['plan'] = empty( get_user_meta( $user_id, vance_hub_flare_plan_meta_key(), true ) );
	}
	if ( $out['log'] && $out['plan'] ) {
		if ( get_user_meta( $user_id, $keys[0], true ) ) {
			delete_user_meta( $user_id, $keys[0] );
			$out['consent'] = empty( get_user_meta( $user_id, $keys[0], true ) );
			if ( $out['consent'] ) {
				vance_hub_flare_plan_write_withdrawn( $user_id );
			}
		} else {
			$out['consent'] = true;
		}
	}
	// A part the run did not reach is reported as it is now, not as deleted.
	if ( ! $out['log'] || ! $out['plan'] ) {
		$state          = vance_hub_flare_plan_stored_state( $user_id );
		$out['plan']    = $out['plan'] || ! $state['plan'];
		$out['consent'] = $out['consent'] || ! $state['consent'];
	}
	$out['ok'] = $out['log'] && $out['plan'] && $out['consent'];
	return $out;
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
	// The raw stored log, to put back when the write fails.
	$raw = get_user_meta( $user_id, vance_hub_flare_log_meta_key(), true );
	if ( 'flare_start' === $do ) {
		$new = vance_hub_flare_log_start( $rows, $today, uniqid( 'fl_' ) );
		if ( null === $new ) {
			return 'flare_open';
		}
		$trimmed = count( $rows ) >= vance_hub_flare_log_max_rows();
		if ( ! vance_hub_flare_log_write( $user_id, $new ) ) {
			vance_hub_flare_plan_restore_raw( $user_id, vance_hub_flare_log_meta_key(), $raw );
			return 'failed';
		}
		return $trimmed ? 'flare_started_trimmed' : 'flare_started';
	}
	$helped = is_string( $helped_raw ) ? vance_hub_flare_plan_clean_text( wp_unslash( $helped_raw ) ) : '';
	// Over-length text is refused, never cut.
	if ( vance_hub_flare_plan_char_count( $helped ) > vance_hub_flare_log_helped_max() ) {
		return 'toolong';
	}
	$new = vance_hub_flare_log_end( $rows, $today, $helped );
	if ( null === $new ) {
		return 'flare_none';
	}
	if ( ! vance_hub_flare_log_write( $user_id, $new ) ) {
		vance_hub_flare_plan_restore_raw( $user_id, vance_hub_flare_log_meta_key(), $raw );
		return 'failed';
	}
	return 'flare_ended';
}

/**
 * The id of a log row, for the "Remove this entry" form. A row with no usable
 * id (old data, or an id with other characters than letters, digits and
 * underscore) gets a stable id made from its two dates.
 *
 * @param array $row {id, start, end}.
 * @return string Letters, digits and underscore only.
 */
function vance_hub_flare_log_row_id( $row ) {
	$id = isset( $row['id'] ) && is_string( $row['id'] ) ? $row['id'] : '';
	if ( '' !== $id && 1 === preg_match( '/^[A-Za-z0-9_]+$/D', $id ) ) {
		return $id;
	}
	return 'r' . md5( ( isset( $row['start'] ) ? $row['start'] : '' ) . ( isset( $row['end'] ) ? $row['end'] : '' ) );
}

/**
 * Remove one entry, open or closed, from a member's flare log. The caller holds
 * the member's lock. Needs no plan and no agreement: removing is a delete.
 * The first row with the id goes. When it was the last row the meta key is deleted.
 *
 * @param int   $user_id Member.
 * @param mixed $row_id  Posted row id, already unslashed.
 * @return string 'flare_removed', 'flare_none' (no such row) or 'failed'.
 */
function vance_hub_flare_log_remove( $user_id, $row_id ) {
	$user_id = (int) $user_id;
	$row_id  = is_string( $row_id ) ? $row_id : '';
	if ( $user_id <= 0 || '' === $row_id || 1 !== preg_match( '/^[A-Za-z0-9_]+$/D', $row_id ) ) {
		return 'flare_none';
	}
	$key  = vance_hub_flare_log_meta_key();
	$raw  = get_user_meta( $user_id, $key, true );
	$rows = vance_hub_flare_log_normalise( $raw );
	$at   = null;
	foreach ( $rows as $i => $row ) {
		if ( vance_hub_flare_log_row_id( $row ) === $row_id ) {
			$at = $i;
			break;
		}
	}
	if ( null === $at ) {
		return 'flare_none';
	}
	unset( $rows[ $at ] );
	$rows = array_values( $rows );
	if ( empty( $rows ) ) {
		delete_user_meta( $user_id, $key );
		return empty( get_user_meta( $user_id, $key, true ) ) ? 'flare_removed' : 'failed';
	}
	if ( ! vance_hub_flare_log_write( $user_id, $rows ) ) {
		vance_hub_flare_plan_restore_raw( $user_id, $key, $raw );
		return 'failed';
	}
	return 'flare_removed';
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
	$back = remove_query_arg( 'vance_flare' );
	$uid  = get_current_user_id();
	$do   = isset( $_POST['vance_hub_flare_do'] ) && is_string( $_POST['vance_hub_flare_do'] ) ? sanitize_key( wp_unslash( $_POST['vance_hub_flare_do'] ) ) : '';
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vance_hub_flare_nonce'] ) ), 'vance_hub_flare_plan' ) ) {
		// The member is logged in here, but the request is not proved to be theirs.
		// Keep the typed text only when the browser says the form came from this
		// site: a page left open too long. A form on another site must not be able
		// to put text into a member's flare plan form.
		if ( vance_hub_flare_plan_same_origin_request() ) {
			vance_hub_flare_plan_draft_set( $uid, vance_hub_flare_plan_draft_from_post( $do ) );
		}
		vance_hub_flare_plan_redirect( $back, 'expired' );
	}

	$result = 'invalid';

	// Which gate each action needs. The switch and the urgent help text close
	// every action but the deletes: a member can always delete what is stored.
	$gates  = array(
		'delete'       => 'delete',
		'flare_remove' => 'delete',
		'save'         => 'save',
		'flare_start'  => 'log',
		'flare_end'    => 'log',
	);
	$locked = false;
	if ( isset( $gates[ $do ] ) ) {
		if ( ! vance_hub_flare_plan_can( $gates[ $do ] ) ) {
			$result = 'failed';
		} elseif ( ! vance_hub_checkin_lock( $uid ) ) {
			// The lock comes before the first read of the plan or the agreement.
			$result = 'delete' === $do ? 'kept' : 'failed';
		} else {
			$locked = true;
		}
	}

	if ( ! $locked ) {
		// Nothing runs without the lock. $result is already set: invalid, failed or kept.
	} elseif ( 'flare_start' === $do || 'flare_end' === $do ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- vance_hub_flare_log_action() unslashes and cleans it.
		$result = vance_hub_flare_log_action( $uid, $do, time(), isset( $_POST['vhd_flare_helped'] ) ? $_POST['vhd_flare_helped'] : '' );
	} elseif ( 'flare_remove' === $do ) {
		$row    = isset( $_POST['vhd_flare_row'] ) && is_string( $_POST['vhd_flare_row'] ) ? wp_unslash( $_POST['vhd_flare_row'] ) : '';
		$result = vance_hub_flare_log_remove( $uid, $row );
	} elseif ( 'delete' === $do ) {
		// Delete the flare log, then the plan, then withdraw the agreement. One
		// function and one order, shared with "Manage my data". The time and the
		// wording of the old agreement stay as a record.
		$had  = vance_hub_flare_plan_stored_state( $uid );
		$gone = vance_hub_flare_plan_delete_all( $uid );
		if ( $gone['ok'] ) {
			$result = 'deleted';
		} elseif ( ( $had['log'] && $gone['log'] ) || ( $had['plan'] && $gone['plan'] ) || ( $had['consent'] && $gone['consent'] ) ) {
			// Something was deleted and something stayed.
			$result = 'partial';
		} else {
			$result = 'kept';
		}
	} elseif ( 'save' === $do ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- vance_hub_flare_plan_clean() unslashes and cleans every field.
		$answers = vance_hub_flare_plan_clean( isset( $_POST['vhd_flare'] ) ? $_POST['vhd_flare'] : null );
		$wording = vance_hub_flare_plan_consent_text();
		$keys    = vance_hub_checkin_consent_keys( 'flare_plan' );
		$consent = vance_hub_checkin_consent_ok( get_user_meta( $uid, $keys[0], true ), get_user_meta( $uid, $keys[2], true ), $wording );

		if ( null === $answers ) {
			$result = 'invalid';
		} elseif ( vance_hub_flare_plan_too_long( $answers ) ) {
			// Refused, never cut. Nothing is written, not even the agreement.
			$result = 'toolong';
		} elseif ( ! $consent && empty( $_POST['vhd_consent'] ) ) {
			$result = 'consent';
		} elseif ( ! $consent && ! vance_hub_checkin_consent_ref_ok( $wording, isset( $_POST['vhd_consent_ref'] ) ? wp_unslash( $_POST['vhd_consent_ref'] ) : null ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- compared with hash_equals() only, never stored or output.
			$result = 'changed';
		} else {
			// Order: the agreement first, then the plan. A plan with no agreement
			// must not exist. A failed save changes nothing: the old plan (or no
			// plan) and the old agreement are put back.
			$before   = $consent ? null : vance_hub_checkin_consent_snapshot( $uid, 'flare_plan' );
			$raw_plan = get_user_meta( $uid, vance_hub_flare_plan_meta_key(), true );
			$result   = 'failed';
			if ( null !== $before && ! vance_hub_checkin_consent_record( $uid, $wording, 'flare_plan' ) ) {
				vance_hub_checkin_consent_restore( $uid, $before, 'flare_plan' );
			} elseif ( vance_hub_flare_plan_write( $uid, $answers, time() ) ) {
				$result = 'saved';
				// A new agreement ends the old withdrawal: the record must not show
				// "withdrawn" with a date older than "agreed".
				if ( null !== $before ) {
					delete_user_meta( $uid, $keys[0] . '_withdrawn_at' );
				}
			} else {
				vance_hub_flare_plan_restore_raw( $uid, vance_hub_flare_plan_meta_key(), $raw_plan );
				if ( null !== $before ) {
					vance_hub_checkin_consent_restore( $uid, $before, 'flare_plan' );
				}
			}
		}
	}

	if ( $locked ) {
		vance_hub_checkin_unlock( $uid );
	}

	// The draft: gone after a success and after every delete. Kept (10 minutes)
	// when a save or an end ends in any other result, so the member can try again.
	if ( 'delete' === $do ) {
		vance_hub_flare_plan_draft_clear( $uid );
	} elseif ( 'save' === $do || 'flare_end' === $do ) {
		if ( 'saved' === $result || 'flare_ended' === $result ) {
			vance_hub_flare_plan_draft_clear( $uid );
		} else {
			vance_hub_flare_plan_draft_set( $uid, vance_hub_flare_plan_draft_from_post( $do ) );
		}
	}

	vance_hub_flare_plan_redirect( $back, $result );
}
add_action( 'template_redirect', 'vance_hub_flare_plan_handle_post' );

/**
 * Redirect back to the card with a result code, then stop. The fragment moves
 * the browser to the card, where the message is.
 *
 * @param string $back   The page URL without vance_flare.
 * @param string $result Result code.
 */
function vance_hub_flare_plan_redirect( $back, $result ) {
	wp_safe_redirect( add_query_arg( 'vance_flare', $result, $back ) . '#vhd-flare' );
	exit;
}

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
	$agreed_now = ! empty( get_user_meta( $uid, '_sla_consent_flare_plan', true ) );
	foreach ( $record as $key => $name ) {
		// An old withdrawal time is not shown while the agreement is in force.
		if ( $agreed_now && '_sla_consent_flare_plan_withdrawn_at' === $key ) {
			continue;
		}
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
	// The member lock: a save from the member cannot write data back during the erase.
	if ( ! vance_hub_checkin_lock( $uid ) ) {
		$out['items_retained'] = true;
		$out['messages'][]     = __( 'The flare plan data could not be erased now, because another request of this member was running. Run the erase again.', 'vance-health-hub' );
		return $out;
	}
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
	vance_hub_flare_plan_draft_clear( $uid );
	vance_hub_checkin_unlock( $uid );
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
			'id'     => vance_hub_flare_log_row_id( $row ),
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
 * True when the daily check-ins may be read for this member: the check-in
 * switch is on AND the member's check-in agreement is valid for the wording in
 * use now. The same two tests as the check-in card itself.
 *
 * @param int $uid Member.
 * @return bool
 */
function vance_hub_flare_plan_checkin_open( $uid ) {
	if ( ! function_exists( 'vance_hub_checkin_history' ) || ! vance_hub_dash_feature_on( 'vance_hub_dash_checkin' ) ) {
		return false;
	}
	$keys = vance_hub_checkin_consent_keys( 'checkin' );
	return vance_hub_checkin_consent_ok( get_user_meta( (int) $uid, $keys[0], true ), get_user_meta( (int) $uid, $keys[2], true ), vance_hub_checkin_consent_text() );
}

/**
 * True when the member's saved health details and screening results may be
 * read: the general health agreement the Hub records on a first save.
 *
 * @param int $uid Member.
 * @return bool
 */
function vance_hub_flare_plan_health_open( $uid ) {
	return ! empty( get_user_meta( (int) $uid, '_sla_consent_health', true ) );
}

/**
 * The newest saved check-in, for the "How am I today?" comparison.
 *
 * @param int $uid Member.
 * @return array|null {date, pain, bowel, tired}, null when there is none or the check-ins are closed.
 */
function vance_hub_flare_plan_quick_data( $uid ) {
	if ( ! vance_hub_flare_plan_checkin_open( $uid ) ) {
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
	$uid = (int) $uid;
	// Each store is read only through its own gate. A closed gate leaves that
	// part of the sheet empty.
	$health  = vance_hub_flare_plan_health_open( $uid );
	$profile = $health ? get_user_meta( $uid, '_sla_clinical_profile', true ) : array();
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
	if ( vance_hub_flare_plan_checkin_open( $uid ) ) {
		foreach ( array_slice( vance_hub_checkin_history( $uid ), 0, 14 ) as $row ) {
			$p = $row['payload'];
			if ( ! isset( $p['pain'], $p['bowel'], $p['tired'] ) || ! is_numeric( $row['ts'] ) || (int) $row['ts'] <= 0 ) {
				continue;
			}
			$checkins[] = array(
				'date'  => (string) wp_date( 'j M Y', (int) $row['ts'] ),
				'pain'  => max( 0, min( 4, (int) $p['pain'] ) ),
				'bowel' => max( 0, min( 4, (int) $p['bowel'] ) ),
				'tired' => max( 0, min( 4, (int) $p['tired'] ) ),
			);
		}
	}

	// Score, maximum and date only. The risk label is not shown on the sheet.
	$screening = null;
	if ( $health && function_exists( 'vance_get_tool_history' ) && function_exists( 'vance_hub_dashboard_series' ) ) {
		$rows   = vance_get_tool_history( $uid, 'malnutrition-calculator', 0 );
		$points = vance_hub_dashboard_series( $rows, 'score', 8 );
		if ( ! empty( $points ) ) {
			$last      = $points[ count( $points ) - 1 ];
			$screening = array(
				'date'  => (string) wp_date( 'j M Y', $last['ts'] ),
				'score' => vance_hub_dashboard_num( $last['value'] ),
				'max'   => vance_hub_dashboard_num( $last['max'] ),
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
 * Two modes. When the card is available, the full view. When it is not (the
 * switch is off or the urgent help text is empty) and the member HAS a stored
 * plan, a read-only view ('readonly' => true): the plan, the log, print and
 * delete, and nothing to write. With no stored plan it gives null.
 *
 * @param int $uid Member.
 * @return array|null null when the card must not show.
 */
function vance_hub_flare_plan_data( $uid ) {
	$uid       = (int) $uid;
	$available = vance_hub_flare_plan_available();
	$plan      = vance_hub_flare_plan_get( $uid );
	if ( ! $available && null === $plan ) {
		return null;
	}
	$defaults = vance_hub_flare_plan_text_defaults();
	$keys     = vance_hub_checkin_consent_keys( 'flare_plan' );
	$wording  = vance_hub_flare_plan_consent_text();
	$flash    = '';
	if ( isset( $_GET['vance_flare'] ) && is_string( $_GET['vance_flare'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- selects one fixed message, changes nothing.
		$flash = sanitize_key( wp_unslash( $_GET['vance_flare'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	$log  = null !== $plan ? vance_hub_flare_log_get( $uid ) : array();
	$note = vance_hub_dash_text( 'vance_hub_flare_plan_note', $defaults['vance_hub_flare_plan_note'] );

	if ( ! $available ) {
		return array(
			'plan'         => $plan,
			'progress'     => null,
			'log'          => vance_hub_flare_log_view( $log, time() ),
			'quick'        => null,
			'sheet'        => null,
			'updated'      => (string) wp_date( 'j M Y', $plan['updated'] ),
			'urgent'       => vance_hub_flare_plan_urgent_text(),
			'need_consent' => false,
			'consent'      => $wording,
			'note'         => $note,
			'nonce'        => wp_create_nonce( 'vance_hub_flare_plan' ),
			'flash'        => $flash,
			'draft'        => null,
			'readonly'     => true,
		);
	}

	$need_consent = ! vance_hub_checkin_consent_ok( get_user_meta( $uid, $keys[0], true ), get_user_meta( $uid, $keys[2], true ), $wording );
	// The draft is taken (and so deleted) only when the page shows a warning AND
	// the form that the draft fills is on the page. A "what helped" draft needs
	// the end form: a plan, a valid agreement and an open flare. If not, the draft
	// stays until it expires, so the text is not lost with nothing shown.
	$draft = null;
	if ( in_array( $flash, vance_hub_flare_plan_error_codes(), true ) ) {
		$peek   = vance_hub_flare_plan_draft_normalise( get_transient( vance_hub_flare_plan_draft_key( $uid ) ) );
		$usable = null !== $peek && ( ! vance_hub_flare_plan_draft_is( $peek, 'flare_end' ) || ( null !== $plan && ! $need_consent && null !== vance_hub_flare_log_open_index( $log ) ) );
		if ( $usable ) {
			$draft = vance_hub_flare_plan_draft_take( $uid );
		}
	}
	return array(
		'plan'         => $plan,
		'progress'     => null !== $plan ? vance_hub_flare_plan_progress( $plan, time() ) : null,
		'log'          => vance_hub_flare_log_view( $log, time() ),
		'quick'        => null !== $plan ? vance_hub_flare_plan_quick_data( $uid ) : null,
		// No appointment sheet while the member has not agreed to the wording in use.
		'sheet'        => null !== $plan && ! $need_consent ? vance_hub_flare_sheet_data( $uid, $plan, $log, time() ) : null,
		'updated'      => null !== $plan ? (string) wp_date( 'j M Y', $plan['updated'] ) : '',
		'urgent'       => vance_hub_flare_plan_urgent_text(),
		'need_consent' => $need_consent,
		'consent'      => $wording,
		'note'         => $note,
		'nonce'        => wp_create_nonce( 'vance_hub_flare_plan' ),
		'flash'        => $flash,
		'draft'        => $draft,
		'readonly'     => false,
	);
}

/* =========================================================================
 * Render (array in, HTML out)
 * ====================================================================== */

/**
 * True when a view holds a draft for one kind of request.
 *
 * @param mixed  $draft 'draft' from vance_hub_flare_plan_data().
 * @param string $do    'save' or 'flare_end'.
 * @return bool
 */
function vance_hub_flare_plan_draft_is( $draft, $do ) {
	return is_array( $draft ) && isset( $draft['do'] ) && $do === $draft['do'];
}

/**
 * The line above a form that holds a kept draft.
 *
 * @return string HTML.
 */
function vance_hub_flare_plan_draft_line_html() {
	return '<p class="vhd-small vhd-flare__kept">' . esc_html__( 'We kept what you typed. Check it, then save again.', 'vance-health-hub' ) . '</p>';
}

/**
 * The three text boxes, filled with the saved plan when there is one.
 *
 * When a draft of a plan save is passed, the boxes hold what the member typed
 * and the saved plan is not used.
 *
 * @param array|null $plan  The saved plan.
 * @param string     $id    Prefix for the field ids.
 * @param array|null $draft 'draft' from vance_hub_flare_plan_data(), or null.
 * @return string
 */
function vance_hub_flare_plan_fields_html( $plan, $id, $draft = null ) {
	if ( ! vance_hub_flare_plan_draft_is( $draft, 'save' ) ) {
		$draft = null;
	}
	$fields = array(
		'signs'    => array( __( 'My early signs', 'vance-health-hub' ), __( 'How do you notice a flare is starting?', 'vance-health-hub' ) ),
		'actions'  => array( __( 'What I do first', 'vance-health-hub' ), __( 'What do you do in the first days of a flare?', 'vance-health-hub' ) ),
		'contacts' => array( __( 'Who I contact', 'vance-health-hub' ), __( 'Names and phone numbers of the people you call.', 'vance-health-hub' ) ),
	);
	$html = '';
	foreach ( $fields as $key => $text ) {
		$value = null !== $plan && isset( $plan[ $key ] ) ? $plan[ $key ] : '';
		if ( null !== $draft ) {
			$value = isset( $draft['answers'][ $key ] ) ? $draft['answers'][ $key ] : '';
		}
		$fid = $id . '-' . $key;
		$html .= '<div class="vhd-flare__field"><label for="' . esc_attr( $fid ) . '" class="vhd-flare__label">' . esc_html( $text[0] ) . '</label>';
		$html .= '<p class="vhd-flare__hint" id="' . esc_attr( $fid ) . '-hint">' . esc_html( $text[1] ) . '</p>';
		$html .= '<textarea class="vhd-flare__input" id="' . esc_attr( $fid ) . '" name="vhd_flare[' . esc_attr( $key ) . ']" rows="4" maxlength="' . (int) vance_hub_flare_plan_max_chars() . '" aria-describedby="' . esc_attr( $fid ) . '-hint">' . esc_textarea( $value ) . '</textarea>';
		$html .= '<p class="vhd-flare__count" hidden data-count-for="' . esc_attr( $fid ) . '" data-count-max="' . (int) vance_hub_flare_plan_max_chars() . '" data-count-text="' . esc_attr__( '%d of %d characters', 'vance-health-hub' ) . '"></p></div>';
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
	$draft = isset( $f['draft'] ) ? $f['draft'] : null;
	$kept  = vance_hub_flare_plan_draft_is( $draft, 'save' ) ? vance_hub_flare_plan_draft_line_html() : '';
	$form  = $kept . '<form class="vhd-flare__form" method="post" action=""' . ( $wizard ? ' data-vhd-wizard data-step-text="' . esc_attr__( 'Step %1$s of %2$s', 'vance-health-hub' ) . '" data-next-text="' . esc_attr__( 'Next', 'vance-health-hub' ) . '" data-back-text="' . esc_attr__( 'Back', 'vance-health-hub' ) . '"' : '' ) . '>' . $nonce;
	$form .= '<input type="hidden" name="vance_hub_flare_do" value="save">';
	if ( $wizard ) {
		// Top of the builder, before the first field. Shown with and without the script.
		$form .= '<p class="vhd-flare__hint">' . esc_html__( 'Write one or more. You can leave the others empty.', 'vance-health-hub' ) . '</p>';
		$form .= '<p class="vhd-flare__store">' . esc_html__( 'Your plan is stored in your account. You can delete it at any time.', 'vance-health-hub' );
		$policy = function_exists( 'get_privacy_policy_url' ) ? (string) get_privacy_policy_url() : '';
		if ( '' !== $policy ) {
			$form .= ' <a href="' . esc_url( $policy ) . '">' . esc_html__( 'Privacy policy', 'vance-health-hub' ) . '</a>';
		}
		$form .= '</p>';
	}
	$form .= vance_hub_flare_plan_fields_html( $plan, $wizard ? 'vhd-flare' : 'vhd-flare-edit', $draft );
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
	// The three answers only. The review is its own paragraph below the list.
	$written = 3 - count( (array) $pr['missing'] );
	/* translators: %d: number of plan sections written, out of 3. */
	$text  = sprintf( __( '%1$d of 3 sections written', 'vance-health-hub' ), $written );
	$html  = '<div class="vhd-flare__progress"><p class="vhd-flare__progress-text" id="vhd-flare-progress-text">' . esc_html( $text ) . '</p>';
	$html .= '<div class="vhd-flare__bar" role="progressbar" aria-valuemin="0" aria-valuemax="3" aria-valuenow="' . (int) $written . '" aria-labelledby="vhd-flare-progress-text"><span style="width:' . (int) round( 100 * $written / 3 ) . '%"></span></div>';
	$hints = '';
	foreach ( (array) $pr['missing'] as $key ) {
		if ( isset( $names[ $key ] ) ) {
			/* translators: %s: the name of a plan section. */
			$hints .= '<li>' . esc_html( sprintf( __( 'Add: %s', 'vance-health-hub' ), $names[ $key ] ) ) . '</li>';
		}
	}
	if ( '' !== $hints ) {
		$html .= '<ul class="vhd-flare__hints">' . $hints . '</ul>';
	}
	if ( ! empty( $pr['stale'] ) ) {
		$html .= '<p class="vhd-flare__review">' . esc_html__( 'You last changed this plan more than 6 months ago. Check that it is still correct.', 'vance-health-hub' ) . '</p>';
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
		/* translators: %s: the value of one slider, from 0 to 4. */
		'valuetext' => __( '%s of 4', 'vance-health-hub' ),
		'follow'  => __( 'Your plan is below.', 'vance-health-hub' ),
		'last_at' => null !== $q ? $q['date'] : '',
		'last_v'  => null !== $q ? array( 'pain' => $q['pain'], 'bowel' => $q['bowel'], 'tired' => $q['tired'] ) : null,
	);
	$html  = '<section class="vhd-flare__quick" data-vhd-flare-quick data-config="' . esc_attr( (string) json_encode( $config ) ) . '" hidden aria-labelledby="vhd-flare-quick-title">';
	$html .= '<h3 id="vhd-flare-quick-title" class="vhd-flare__h">' . esc_html__( 'How am I today?', 'vance-health-hub' ) . '</h3>';
	$html .= '<p class="vhd-flare__hint">' . esc_html__( 'Move each slider. 0 is none. 4 is very bad. Nothing is saved, and this is not a diagnosis.', 'vance-health-hub' ) . '</p>';
	foreach ( $labels as $key => $label ) {
		$id    = 'vhd-flare-q-' . $key;
		$html .= '<div class="vhd-flare__q"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . ' <output for="' . esc_attr( $id ) . '">&#8211;</output></label>';
		$html .= '<input type="range" min="0" max="4" step="1" value="0" id="' . esc_attr( $id ) . '" data-vhd-q="' . esc_attr( $key ) . '"></div>';
	}
	// Disabled until the member has moved all three sliders. The script sets it.
	$html .= '<button type="button" class="vhd-btn vhd-btn--outline" data-vhd-quick-show disabled>' . esc_html__( 'Compare with my plan', 'vance-health-hub' ) . '</button>';
	$html .= '<div class="vhd-flare__quick-result" data-vhd-quick-result tabindex="-1" role="status" aria-live="polite"></div></section>';
	return $html;
}

/**
 * "Remove this entry": one flare log row. A tick box, marked required, stands in
 * front of the button, so one tap cannot remove an entry.
 *
 * @param string $id    Row id from vance_hub_flare_log_view().
 * @param string $nonce Nonce field HTML.
 * @return string
 */
function vance_hub_flare_plan_remove_form_html( $id, $nonce ) {
	$html  = '<form class="vhd-flare__remove" method="post" action="">' . $nonce . '<input type="hidden" name="vance_hub_flare_do" value="flare_remove">';
	$html .= '<input type="hidden" name="vhd_flare_row" value="' . esc_attr( $id ) . '">';
	$html .= '<label class="vhd-flare__removeok"><input type="checkbox" name="vhd_flare_remove_ok" value="1" required> <span>' . esc_html__( 'Yes, remove it', 'vance-health-hub' ) . '</span></label>';
	$html .= '<button type="submit" class="vhd-btn vhd-btn--outline">' . esc_html__( 'Remove this entry', 'vance-health-hub' ) . '</button></form>';
	return $html;
}

/**
 * The flare log block: start a flare, end it with what helped, and the history.
 *
 * @param array      $log      'log' from vance_hub_flare_plan_data().
 * @param string     $nonce    Nonce field HTML.
 * @param bool       $readonly True for the read-only card: the history, with no start or end control.
 * @param array|null $draft    'draft' from vance_hub_flare_plan_data(): a kept "what helped" text fills the end form.
 * @return string
 */
function vance_hub_flare_plan_log_html( $log, $nonce, $readonly = false, $draft = null ) {
	$html  = '<section class="vhd-flare__log" aria-labelledby="vhd-flare-log-title"><h3 id="vhd-flare-log-title" class="vhd-flare__h">' . esc_html__( 'My flare log', 'vance-health-hub' ) . '</h3>';
	$open  = isset( $log['open'] ) && is_array( $log['open'] ) ? $log['open'] : null;
	if ( null !== $open ) {
		/* translators: 1: start date, 2: day number. */
		$html .= '<p>' . esc_html( sprintf( __( 'I am in a flare. It started on %1$s. Today is day %2$s.', 'vance-health-hub' ), $open['start'], number_format_i18n( (int) $open['days'] ) ) ) . '</p>';
		if ( ! $readonly ) {
			$kept   = vance_hub_flare_plan_draft_is( $draft, 'flare_end' );
			$helped = $kept && isset( $draft['helped'] ) ? (string) $draft['helped'] : '';
			$html  .= ( $kept ? vance_hub_flare_plan_draft_line_html() : '' ) . '<form class="vhd-flare__logform" method="post" action="">' . $nonce . '<input type="hidden" name="vance_hub_flare_do" value="flare_end">';
			$html  .= '<label for="vhd-flare-helped" class="vhd-flare__label">' . esc_html__( 'What helped? (optional)', 'vance-health-hub' ) . '</label>';
			$html  .= '<textarea class="vhd-flare__input" id="vhd-flare-helped" name="vhd_flare_helped" rows="3" maxlength="' . (int) vance_hub_flare_log_helped_max() . '">' . esc_textarea( $helped ) . '</textarea>';
			$html  .= '<p class="vhd-flare__count" hidden data-count-for="vhd-flare-helped" data-count-max="' . (int) vance_hub_flare_log_helped_max() . '" data-count-text="' . esc_attr__( '%d of %d characters', 'vance-health-hub' ) . '"></p>';
			$html  .= '<button type="submit" class="vhd-btn vhd-btn--purple">' . esc_html__( 'My flare has ended', 'vance-health-hub' ) . '</button></form>';
		}
		if ( isset( $open['id'] ) && '' !== $open['id'] ) {
			$html .= vance_hub_flare_plan_remove_form_html( $open['id'], $nonce );
		}
	} elseif ( ! $readonly ) {
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
			if ( isset( $row['id'] ) && '' !== $row['id'] ) {
				$html .= vance_hub_flare_plan_remove_form_html( $row['id'], $nonce );
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
			$html .= $sec( $title, '<p>' . vance_hub_flare_plan_text_html( $plan[ $key ], 'contacts' === $key ) . '</p>' );
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
		$line = sprintf( __( 'Score %1$s of %2$s, on %3$s.', 'vance-health-hub' ), $sc['score'], $sc['max'], $sc['date'] );
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
	if ( empty( $f ) ) {
		return '';
	}
	// Not empty(): the text "0" is set text, as in vance_hub_flare_plan_available().
	// The read-only card (switch off, plan stored) shows with no urgent help text.
	$readonly = ! empty( $f['readonly'] );
	if ( ! $readonly && ( ! isset( $f['urgent'] ) || '' === trim( (string) $f['urgent'] ) ) ) {
		return '';
	}
	$f['urgent'] = isset( $f['urgent'] ) ? (string) $f['urgent'] : '';
	$messages    = array(
		'saved'         => __( 'Your flare plan is saved.', 'vance-health-hub' ),
		'deleted'       => __( 'Your flare plan and flare log are deleted, and your agreement is withdrawn.', 'vance-health-hub' ),
		'toolong'       => __( 'Not saved. A text is too long. Make it shorter, then save again.', 'vance-health-hub' ),
		'partial'       => __( 'Part of your data is deleted. Part of it is still stored. Try again to delete the rest.', 'vance-health-hub' ),
		/* translators: %d: the most entries the flare log keeps. */
		'flare_started_trimmed' => sprintf( __( 'Your flare is recorded. The log keeps %d entries, so the oldest entry was removed.', 'vance-health-hub' ), vance_hub_flare_log_max_rows() ),
		'flare_removed' => __( 'The entry is removed.', 'vance-health-hub' ),
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

	// id="vhd-flare": the redirect after every request ends in #vhd-flare.
	$html  = '<section id="vhd-flare" class="vhd-card vhd-flare" aria-labelledby="vhd-flare-title"><div class="vhd-card__head">';
	$html .= '<h2 id="vhd-flare-title" class="vhd-h3">' . esc_html__( 'My flare plan', 'vance-health-hub' ) . '</h2></div>';
	if ( null === $plan ) {
		$html .= '<p class="vhd-flare__intro">' . esc_html__( 'A flare plan is your own note of what a flare looks like for you, what you do first, and who you contact. You can print it and take it to an appointment.', 'vance-health-hub' ) . '</p>';
	}

	if ( isset( $messages[ $flash ] ) ) {
		// A warning is an alert, a success is a status. tabindex -1 lets the script move focus here.
		$ok    = in_array( $flash, vance_hub_flare_plan_success_codes(), true );
		$html .= '<p class="vhd-flash' . ( $ok ? '' : ' vhd-flash--warn' ) . '" id="vhd-flare-flash" tabindex="-1" role="' . ( $ok ? 'status' : 'alert' ) . '">' . esc_html( $messages[ $flash ] ) . '</p>';
	}

	if ( $readonly ) {
		$html .= '<p class="vhd-small vhd-flare__readonly">' . esc_html__( 'Editing is turned off. You can still print or delete your plan.', 'vance-health-hub' ) . '</p>';
	}

	// The urgent help box shows only when its text is set. With a saved plan it sits in
	// the "Urgent help and my contacts" block; with no plan it sits after the builder.
	$urgent_html = '';
	if ( '' !== trim( $f['urgent'] ) ) {
		$urgent_html = '<div class="vhd-flare__sec vhd-flare__urgent"><h3 class="vhd-flare__h">' . esc_html__( 'When to get urgent help', 'vance-health-hub' ) . '</h3><p>' . vance_hub_flare_plan_text_html( $f['urgent'], true ) . '</p></div>';
	}

	if ( null === $plan ) {
		// No plan yet: the step-by-step builder, open. The read-only card has no builder.
		if ( ! $readonly ) {
			$html .= vance_hub_flare_plan_form_html( $f, null, $nonce, true );
		}
	} else {
		$sections = array(
			'signs'   => __( 'My early signs', 'vance-health-hub' ),
			'actions' => __( 'What I do first', 'vance-health-hub' ),
		);
		// Urgent help and contacts come first: before the progress bar and the quick check.
		$contacts_text = '' !== $plan['contacts'] ? vance_hub_flare_plan_text_html( $plan['contacts'], true ) : '<em>' . esc_html__( 'Nothing written.', 'vance-health-hub' ) . '</em>';
		$html .= '<div class="vhd-flare__now"><h3 class="vhd-flare__h">' . esc_html__( 'Urgent help and my contacts', 'vance-health-hub' ) . '</h3>' . $urgent_html . '<div class="vhd-flare__sec"><h3 class="vhd-flare__h">' . esc_html__( 'Who I contact', 'vance-health-hub' ) . '</h3><p>' . $contacts_text . '</p></div></div>';
		if ( ! $readonly ) {
			$html .= vance_hub_flare_plan_progress_html( isset( $f['progress'] ) ? $f['progress'] : null );
			$html .= vance_hub_flare_plan_quick_html( isset( $f['quick'] ) ? $f['quick'] : null );
		}
		$html .= '<div class="vhd-flare__plan">';
		foreach ( $sections as $key => $title ) {
			$text  = '' !== $plan[ $key ] ? vance_hub_flare_plan_text_html( $plan[ $key ], 'contacts' === $key ) : '<em>' . esc_html__( 'Nothing written.', 'vance-health-hub' ) . '</em>';
			$html .= '<div class="vhd-flare__sec"><h3 class="vhd-flare__h">' . esc_html( $title ) . '</h3><p>' . $text . '</p></div>';
		}
		if ( '' !== $f['updated'] ) {
			/* translators: %s: date of the last change. */
			$html .= '<p class="vhd-small vhd-flare__date">' . esc_html( sprintf( __( 'Last changed on %s.', 'vance-health-hub' ), $f['updated'] ) ) . '</p>';
		}
		$html .= '</div>';
	}

	// Fixed text: shown to every member, with or without a plan. The read-only
	// card shows the box only when an administrator's text is set. With a saved plan
	// the box is in the "Urgent help and my contacts" block above.
	if ( null === $plan ) {
		$html .= $urgent_html;
	}

	if ( isset( $f['note'] ) && '' !== $f['note'] ) {
		$html .= '<p class="vhd-small">' . esc_html( $f['note'] ) . '</p>';
	}

	if ( null !== $plan ) {
		$draft = isset( $f['draft'] ) ? $f['draft'] : null;
		if ( ! $readonly && ! empty( $f['need_consent'] ) ) {
			// The agreement is missing or its wording changed: the log buttons would
			// only fail. Point the member to the plan form, which asks again.
			$html .= '<section class="vhd-flare__log" aria-labelledby="vhd-flare-log-title"><h3 id="vhd-flare-log-title" class="vhd-flare__h">' . esc_html__( 'My flare log', 'vance-health-hub' ) . '</h3><p>' . esc_html__( 'To use the flare log, read the agreement again and save your plan. Open "Edit my plan" below.', 'vance-health-hub' ) . '</p></section>';
		} else {
			$html .= vance_hub_flare_plan_log_html( isset( $f['log'] ) ? $f['log'] : array(), $nonce, $readonly, $draft );
		}
		$html .= '<div class="vhd-flare__actions">';
		// Hidden until hub-flare-plan.js shows it: with no script it does nothing.
		$html .= '<button type="button" class="vhd-btn vhd-btn--outline vhd-flare__print" data-vhd-flare-print="plan" hidden>' . esc_html__( 'Print my plan', 'vance-health-hub' ) . '</button></div>';
		if ( ! $readonly ) {
			// No sheet block while the sheet is empty (it is null while the agreement is missing).
			if ( ! empty( $f['sheet'] ) ) {
				$html .= '<details class="vhd-flare__sheetbox"><summary>' . esc_html__( 'Appointment sheet', 'vance-health-hub' ) . '</summary>' . vance_hub_flare_plan_sheet_html( $f['sheet'], $plan ) . '</details>';
			}
			// After a flare ends, or when a kept draft fills the form, the edit box opens.
			$edit_open = 'flare_ended' === $flash || ! empty( $f['need_consent'] ) || vance_hub_flare_plan_draft_is( $draft, 'save' );
			$html     .= '<details class="vhd-flare__edit"' . ( $edit_open ? ' open' : '' ) . '><summary>' . esc_html__( 'Edit my plan', 'vance-health-hub' ) . '</summary>' . vance_hub_flare_plan_form_html( $f, $plan, $nonce, false ) . '</details>';
		}
		$html .= '<details class="vhd-flare__manage"><summary>' . esc_html__( 'Manage my flare plan data', 'vance-health-hub' ) . '</summary>';
		$html .= '<p>' . esc_html__( 'You cannot get your plan and your flare log back after you delete them.', 'vance-health-hub' ) . '</p>';
		$html .= '<form method="post" action="">' . $nonce . '<input type="hidden" name="vance_hub_flare_do" value="delete">';
		$html .= '<label class="vhd-flare__consent"><input type="checkbox" name="vhd_flare_delete_ok" value="1" required> <span>' . esc_html__( 'Yes, delete my plan and my flare log.', 'vance-health-hub' ) . '</span></label>';
		$html .= '<button type="submit" class="vhd-btn vhd-btn--outline vhd-btn--danger">' . esc_html__( 'Delete my plan and flare log, and withdraw my agreement', 'vance-health-hub' ) . '</button></form></details>';
	}

	$html .= '</section>';
	return $html;
}
