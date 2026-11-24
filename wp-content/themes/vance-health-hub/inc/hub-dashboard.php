<?php
/**
 * VanceHubClub member dashboard home.
 *
 * An opt-in replacement for the first screen of the member dashboard
 * (page-dashboard.php, `case 'home'`). The other dashboard tabs, the sidebar,
 * the AJAX handlers and every `_sla_*` meta key are untouched: this file only
 * READS member data, through the same keys and helpers the classic home reads.
 * The optional blocks that write (member number, weekly check-in) are in
 * inc/hub-dashboard-features.php.
 *
 * The switch is "Member dashboard: use the VanceHubClub home" in
 * Appearance -> Customize -> Home layout. It is off by default. Administrators
 * can preview either layout without saving: ?vhh_layout=hub or
 * ?vhh_layout=classic on the dashboard address.
 *
 * Data rule (same as the hub home page): no invented number, name or claim.
 * Every value comes from the member's own saved data or from a real query. A
 * block with nothing behind it does not render. The classic home tells a
 * practitioner "You have 3 patient updates pending review", which is a fixed
 * string; this layout has no equivalent line.
 *
 * Layout of this file: pure helpers first (no WordPress state, covered by
 * tests/hub-dashboard.test.php), then the switch, then the data collector,
 * then the renderer. The renderer takes the collected array and returns HTML,
 * so it can be tested with a hand-built array.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The shared preview resolver (vance_hub_preview_query).
require_once __DIR__ . '/hub-preview.php';

/* =========================================================================
 * Pure helpers
 * ====================================================================== */

/**
 * Decide whether the hub dashboard home is active.
 *
 * @param mixed  $mod_on      Saved switch value.
 * @param string $query       Sanitised ?vhh_layout value, '' when absent.
 * @param bool   $can_preview True when the viewer may use the preview query.
 * @return bool
 */
function vance_hub_dashboard_resolve( $mod_on, $query, $can_preview ) {
	if ( $can_preview && 'hub' === $query ) {
		return true;
	}
	if ( $can_preview && 'classic' === $query ) {
		return false;
	}
	return true === $mod_on || 1 === $mod_on || '1' === $mod_on;
}

/**
 * A meta value as a list. User meta that was never written reads as '', and a
 * damaged row can read as a string: both count as "nothing saved".
 *
 * @param mixed $value Raw meta value.
 * @return array
 */
function vance_hub_dashboard_list( $value ) {
	return is_array( $value ) ? $value : array();
}

/**
 * The newest timestamp in a tool-history list ({ts, payload} rows, newest
 * first as vance_get_tool_history() returns them). 0 when there is none.
 *
 * The maximum is taken, not row 0, so an out-of-order list still gives the
 * true latest date.
 *
 * @param array $entries Tool-history rows.
 * @return int Unix timestamp or 0.
 */
function vance_hub_dashboard_latest_ts( $entries ) {
	$latest = 0;
	foreach ( vance_hub_dashboard_list( $entries ) as $entry ) {
		if ( is_array( $entry ) && isset( $entry['ts'] ) && is_numeric( $entry['ts'] ) && (int) $entry['ts'] > $latest ) {
			$latest = (int) $entry['ts'];
		}
	}
	return $latest;
}

/**
 * The one next step to show, or '' when the member has done all of them.
 *
 * Order: survey, Health Profile, first meal plan, first nutrition check. A
 * step whose dashboard tab is switched off is skipped, because its result
 * would have nowhere to show.
 *
 * @param array $state   survey_done, profile_done (bool); plans, checks (int).
 * @param array $enabled Feature slug => bool, for 'health-profile' and 'my-recipes'.
 * @return string 'survey', 'profile', 'plan', 'check' or ''.
 */
function vance_hub_dashboard_next_step( $state, $enabled ) {
	$state   = vance_hub_dashboard_list( $state );
	$enabled = vance_hub_dashboard_list( $enabled );
	$profile = ! empty( $enabled['health-profile'] );
	$recipes = ! empty( $enabled['my-recipes'] );

	if ( $profile && empty( $state['survey_done'] ) ) {
		return 'survey';
	}
	if ( $profile && empty( $state['profile_done'] ) ) {
		return 'profile';
	}
	if ( $recipes && empty( $state['plans'] ) ) {
		return 'plan';
	}
	if ( $profile && empty( $state['checks'] ) ) {
		return 'check';
	}
	return '';
}

/**
 * The member's own progress line for one tool tile.
 *
 * @param string $mod   Tool key from vance_hub_tools(): survey, recipes, nutrition, discounts.
 * @param array  $state survey_done (bool); plans, checks, discounts (int);
 *                      plan_date, check_date (already formatted, '' when unknown).
 * @return array{done: bool, text: string}
 */
function vance_hub_dashboard_tool_status( $mod, $state ) {
	$state = vance_hub_dashboard_list( $state );
	$int   = function ( $key ) use ( $state ) {
		return isset( $state[ $key ] ) ? max( 0, (int) $state[ $key ] ) : 0;
	};
	$str   = function ( $key ) use ( $state ) {
		return isset( $state[ $key ] ) && is_string( $state[ $key ] ) ? $state[ $key ] : '';
	};

	switch ( $mod ) {
		case 'survey':
			return ! empty( $state['survey_done'] )
				? array( 'done' => true, 'text' => __( 'Your answers are saved.', 'vance-health-hub' ) )
				: array( 'done' => false, 'text' => __( 'Not started.', 'vance-health-hub' ) );

		case 'recipes':
			$n = $int( 'plans' );
			if ( 0 === $n ) {
				return array( 'done' => false, 'text' => __( 'No saved meal plan.', 'vance-health-hub' ) );
			}
			/* translators: %s: number of saved meal plans. */
			$text = sprintf( _n( '%s saved meal plan.', '%s saved meal plans.', $n, 'vance-health-hub' ), number_format_i18n( $n ) );
			if ( '' !== $str( 'plan_date' ) ) {
				/* translators: %s: date of the newest saved meal plan. */
				$text .= ' ' . sprintf( __( 'Last saved %s.', 'vance-health-hub' ), $str( 'plan_date' ) );
			}
			return array( 'done' => true, 'text' => $text );

		case 'nutrition':
			$n = $int( 'checks' );
			if ( 0 === $n ) {
				return array( 'done' => false, 'text' => __( 'No saved check.', 'vance-health-hub' ) );
			}
			/* translators: %s: number of saved nutrition checks. */
			$text = sprintf( _n( '%s saved check.', '%s saved checks.', $n, 'vance-health-hub' ), number_format_i18n( $n ) );
			if ( '' !== $str( 'check_date' ) ) {
				/* translators: %s: date of the newest saved nutrition check. */
				$text .= ' ' . sprintf( __( 'Last check %s.', 'vance-health-hub' ), $str( 'check_date' ) );
			}
			return array( 'done' => true, 'text' => $text );

		case 'discounts':
			$n = $int( 'discounts' );
			if ( 0 === $n ) {
				return array( 'done' => false, 'text' => __( 'No saved scheme.', 'vance-health-hub' ) );
			}
			/* translators: %s: number of saved discount schemes. */
			return array( 'done' => true, 'text' => sprintf( _n( '%s saved scheme.', '%s saved schemes.', $n, 'vance-health-hub' ), number_format_i18n( $n ) ) );
	}
	return array( 'done' => false, 'text' => '' );
}

/**
 * Count the messages a member has not read.
 *
 * @param array $read_lists One "read by" list per message (user IDs, any scalar type).
 * @param int   $user_id    The member.
 * @return int
 */
function vance_hub_dashboard_unread_count( $read_lists, $user_id ) {
	$user_id = (int) $user_id;
	$unread  = 0;
	foreach ( vance_hub_dashboard_list( $read_lists ) as $readers ) {
		$readers = array_map( 'intval', array_filter( vance_hub_dashboard_list( $readers ), 'is_scalar' ) );
		if ( ! in_array( $user_id, $readers, true ) ) {
			$unread++;
		}
	}
	return $unread;
}

/* =========================================================================
 * The switch
 * ====================================================================== */

/**
 * True when the hub dashboard home is active for this request.
 *
 * Mirrors vance_hub_layout_active(): the preview query is read only for a
 * user who may edit theme options, and only when it is a string.
 *
 * @return bool
 */
function vance_hub_dashboard_active() {
	$query = vance_hub_preview_query();
	return vance_hub_dashboard_resolve(
		vance_hub_switch_on( 'vance_hub_show_dashboard' ),
		$query,
		'' !== $query
	);
}

/**
 * Stylesheet, only on the dashboard page and only when the layout is active.
 */
function vance_hub_dashboard_enqueue_assets() {
	// The "dashboard" page has no assigned template (_wp_page_template is
	// 'default'): WordPress resolves page-dashboard.php by its slug, so
	// is_page_template() is false there. Test the slug too, as functions.php does.
	if ( ! ( is_page( 'dashboard' ) || is_page_template( 'page-dashboard.php' ) ) || ! is_user_logged_in() || ! vance_hub_dashboard_active() ) {
		return;
	}
	wp_enqueue_style(
		'vance-hub-dashboard',
		get_template_directory_uri() . '/assets/css/hub-dashboard.css',
		array( 'vance-main-style' ),
		@filemtime( get_template_directory() . '/assets/css/hub-dashboard.css' ) ?: '1.0.0'
	);
	// The print button of the flare plan card. Only when the card can show.
	if ( function_exists( 'vance_hub_flare_plan_available' ) && vance_hub_flare_plan_available() ) {
		wp_enqueue_script(
			'vance-hub-flare-plan',
			get_template_directory_uri() . '/assets/js/hub-flare-plan.js',
			array(),
			@filemtime( get_template_directory() . '/assets/js/hub-flare-plan.js' ) ?: '1.0.0',
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'vance_hub_dashboard_enqueue_assets', 20 );

/* =========================================================================
 * The VanceHubClub look on every other dashboard tab
 *
 * A second, separate switch ("Member dashboard: VanceHubClub look on every
 * tab", vance_hub_dashboard_tabs). It does nothing unless the VanceHubClub
 * dashboard is also active, so ?vhh_layout=hub previews it without saving.
 *
 * It is a restyle. The tabs keep their markup, forms, AJAX calls and nonces.
 * What it adds: a body class, one stylesheet (assets/css/hub-dashboard-tabs.css),
 * a member card and an "Ask VANCE-Ai" block in the side menu, and a small
 * eyebrow line above each tab heading. page-dashboard.php prints those three
 * only when vance_hub_dashboard_tabs_active() is true.
 * ====================================================================== */

/**
 * Decide whether the tab look is active.
 *
 * @param mixed $mod_on      Saved switch value, after the checkbox sanitiser.
 * @param bool  $dash_active True when the VanceHubClub dashboard is active.
 * @return bool
 */
function vance_hub_dashboard_tabs_resolve( $mod_on, $dash_active ) {
	return true === $dash_active && ( true === $mod_on || 1 === $mod_on || '1' === $mod_on );
}

/**
 * True when the VanceHubClub look applies to the dashboard tabs for this request.
 *
 * @return bool
 */
function vance_hub_dashboard_tabs_active() {
	// An administrator's preview does not wait for the master switch: it reads
	// the part's own switch alone. The part's own switch still counts, and the
	// dashboard flag still decides: ?vhh_layout=classic makes it false.
	$mod = 'vance_hub_dashboard_tabs';
	$own = (bool) vance_hub_sanitize_checkbox( vance_get_theme_mod( $mod, vance_hub_switch_default( $mod ) ) );
	return vance_hub_dashboard_tabs_resolve(
		'' !== vance_hub_preview_query() ? $own : vance_hub_switch_on( $mod ),
		vance_hub_dashboard_active()
	);
}

/**
 * True on the member dashboard page for a signed-in member. The page has no
 * assigned template, so it is matched by slug as well as by template.
 *
 * @return bool
 */
function vance_hub_dashboard_is_page() {
	return ( is_page( 'dashboard' ) || is_page_template( 'page-dashboard.php' ) ) && is_user_logged_in();
}

/**
 * Stylesheet for the tab look. Same page test as the home stylesheet, plus the
 * second switch. It loads after hub-dashboard.css (priority 21).
 */
function vance_hub_dashboard_tabs_enqueue_assets() {
	if ( ! vance_hub_dashboard_is_page() || ! vance_hub_dashboard_tabs_active() ) {
		return;
	}
	wp_enqueue_style(
		'vance-hub-dashboard-tabs',
		get_template_directory_uri() . '/assets/css/hub-dashboard-tabs.css',
		array( 'vance-main-style' ),
		@filemtime( get_template_directory() . '/assets/css/hub-dashboard-tabs.css' ) ?: '1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'vance_hub_dashboard_tabs_enqueue_assets', 21 );

/**
 * Body class that scopes every rule of hub-dashboard-tabs.css.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function vance_hub_dashboard_tabs_body_class( $classes ) {
	if ( vance_hub_dashboard_is_page() && vance_hub_dashboard_tabs_active() ) {
		$classes[] = 'vhd-tabs';
	}
	return $classes;
}
add_filter( 'body_class', 'vance_hub_dashboard_tabs_body_class' );

/**
 * The administrator preview value that must follow a member through the tabs.
 * Only 'hub' is carried: it is the one that turns the layout on. The query is
 * read only for a user who may edit theme options, as in
 * vance_hub_dashboard_active().
 *
 * @return string 'hub' or ''.
 */
function vance_hub_dashboard_preview_arg() {
	// Only the explicit query is carried. Preview mode (admin flag or share
	// cookie) already holds on every page, so it needs nothing in the link.
	return 'hub' === vance_hub_preview_explicit_query() ? 'hub' : '';
}

/**
 * The member card in the side menu. Only what the member's own account holds:
 * the name, the join month, and the member number when its switch is on. No
 * badge, streak or status word that no data stands behind.
 *
 * @param WP_User $user The logged-in member.
 * @return array{name: string, since: string, code: string}
 */
function vance_hub_dashboard_member_card_data( $user ) {
	$uid   = (int) $user->ID;
	$first = (string) get_user_meta( $uid, 'first_name', true );
	$last  = (string) get_user_meta( $uid, 'last_name', true );
	$name  = trim( $first . ' ' . $last );
	if ( '' === $name ) {
		$name = trim( (string) $user->display_name );
	}

	// Same rule as vance_hub_dashboard_data(): a blank or zero registration date is no date.
	$registered = trim( (string) $user->user_registered );
	$joined     = '' === $registered ? false : strtotime( $registered . ' UTC' );
	if ( false === $joined || $joined <= 0 ) {
		$joined = 0;
	}

	$code = '';
	if ( function_exists( 'vance_hub_dash_feature_on' ) && function_exists( 'vance_hub_member_code_for' ) && vance_hub_dash_feature_on( 'vance_hub_dash_member_no' ) ) {
		$code = (string) vance_hub_member_code_for( $uid );
	}

	// First word of the first name for the greeting, else of the full name.
	$parts = preg_split( '/\s+/u', '' !== trim( $first ) ? trim( $first ) : $name );
	$hi    = is_array( $parts ) && isset( $parts[0] ) ? (string) $parts[0] : '';
	// An email address used as a display name is never a greeting.
	if ( false !== strpos( $hi, '@' ) ) {
		$hi = '';
	}

	return array(
		'name'  => $name,
		'first' => $hi,
		'since' => $joined ? (string) wp_date( 'F Y', $joined ) : '',
		'code'  => $code,
	);
}

/**
 * The member card as HTML. Empty when there is no name to show.
 *
 * @param array $card vance_hub_dashboard_member_card_data() result.
 * @return string
 */
function vance_hub_dashboard_member_card_html( $card ) {
	if ( empty( $card['name'] ) ) {
		return '';
	}
	$html  = '<div class="vhdt-member">';
	$label = ! empty( $card['first'] )
		/* translators: %s: member's first name. */
		? sprintf( __( 'Welcome back %s', 'vance-health-hub' ), $card['first'] )
		: __( 'Welcome back', 'vance-health-hub' );
	$html .= '<span class="vhdt-member__label">' . esc_html( $label ) . '</span>';
	$html .= '<span class="vhdt-member__name">' . esc_html( $card['name'] ) . '</span>';
	$line  = array();
	if ( ! empty( $card['code'] ) ) {
		/* translators: %s: the member's code. */
		$line[] = sprintf( __( 'Member number %s', 'vance-health-hub' ), $card['code'] );
	}
	if ( ! empty( $card['since'] ) ) {
		/* translators: %s: month and year the member joined. */
		$line[] = sprintf( __( 'Member since %s', 'vance-health-hub' ), $card['since'] );
	}
	foreach ( $line as $text ) {
		$html .= '<span class="vhdt-member__meta">' . esc_html( $text ) . '</span>';
	}
	return $html . '</div>';
}

/**
 * The "Ask VANCE-Ai" block at the foot of the side menu. It opens the shared
 * chat modal on a new conversation, the same hook the home block and the
 * "New Chat" button use. Empty when the VANCE-Ai tab is switched off.
 *
 * @param bool $enabled True when the ai-chats dashboard tab is on.
 * @return string
 */
function vance_hub_dashboard_ask_block_html( $enabled ) {
	if ( ! $enabled ) {
		return '';
	}
	return '<button type="button" class="vhdt-ask" data-vance-askai-open="new">'
		. '<span class="vhdt-ask__title">' . esc_html__( 'Ask VANCE-Ai', 'vance-health-hub' ) . ' <span aria-hidden="true">&rarr;</span></span>'
		. '</button>';
}

/**
 * The small purple line above a tab heading.
 *
 * @return string
 */
function vance_hub_dashboard_tab_eyebrow_html() {
	return '<p class="vhdt-eyebrow">' . esc_html__( 'My Hub', 'vance-health-hub' ) . '</p>';
}

/**
 * Print the side-menu member card. Called from page-dashboard.php.
 *
 * @param WP_User $user The logged-in member.
 */
function vance_hub_dashboard_tabs_render_member_card( $user ) {
	if ( ! ( $user instanceof WP_User ) || ! $user->exists() ) {
		return;
	}
	echo vance_hub_dashboard_member_card_html( vance_hub_dashboard_member_card_data( $user ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in vance_hub_dashboard_member_card_html().
}

/**
 * Print the side-menu "Ask VANCE-Ai" block. Called from page-dashboard.php.
 */
function vance_hub_dashboard_tabs_render_ask_block() {
	$on = function_exists( 'vance_dashboard_feature_enabled' ) && vance_dashboard_feature_enabled( 'ai-chats' );
	echo vance_hub_dashboard_ask_block_html( $on ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in vance_hub_dashboard_ask_block_html().
}

/* =========================================================================
 * Data
 * ====================================================================== */

/**
 * A dashboard tab link. Relative on purpose, like every tab link in
 * page-dashboard.php: it stays on whatever address the dashboard page has.
 *
 * @param string $slug Feature slug.
 * @return string
 */
function vance_hub_dashboard_tab_url( $slug ) {
	$url = '?tab=' . rawurlencode( (string) $slug );
	// An administrator previewing with ?vhh_layout=hub keeps the preview on
	// every click. Nobody else has the query, so nobody else sees it added.
	if ( 'hub' === vance_hub_dashboard_preview_arg() ) {
		$url .= '&vhh_layout=hub';
	}
	return $url;
}

/**
 * Everything the hub dashboard home shows for one member.
 *
 * Reads only. The meta keys are the legacy `_sla_*` keys the rest of the
 * dashboard uses; they must not be renamed (see CLAUDE.md).
 *
 * @param WP_User $user The logged-in member.
 * @return array See vance_hub_dashboard_home_html() for the shape.
 */
function vance_hub_dashboard_data( $user ) {
	$uid = (int) $user->ID;
	$on  = function ( $slug ) {
		return function_exists( 'vance_dashboard_feature_enabled' ) && vance_dashboard_feature_enabled( $slug );
	};
	$label = function ( $slug, $fallback ) {
		$text = function_exists( 'vance_dashboard_feature_label' ) ? (string) vance_dashboard_feature_label( $slug ) : '';
		return '' !== $text ? $text : $fallback;
	};
	$date = function ( $ts ) {
		return $ts > 0 ? (string) wp_date( 'j M Y', $ts ) : '';
	};

	$first = (string) get_user_meta( $uid, 'first_name', true );
	if ( '' === $first ) {
		$first = (string) $user->display_name;
	}
	// Empty parses as "now" and the zero date as a negative year, so only a
	// non-blank value that parses to a positive timestamp counts as a join date.
	$registered = trim( (string) $user->user_registered );
	$joined     = '' === $registered ? false : strtotime( $registered . ' UTC' );
	if ( false === $joined || $joined <= 0 ) {
		$joined = 0;
	}

	$bookmarks = array_values( array_filter( array_map( 'absint', vance_hub_dashboard_list( get_user_meta( $uid, '_sla_reading_list', true ) ) ) ) );
	$plans     = function_exists( 'vance_get_tool_history' ) ? vance_get_tool_history( $uid, 'ibd-recipes', 0 ) : array();
	$checks    = function_exists( 'vance_get_tool_history' ) ? vance_get_tool_history( $uid, 'malnutrition-calculator', 0 ) : array();

	$state = array(
		'survey_done'  => ! empty( vance_hub_dashboard_list( get_user_meta( $uid, '_sla_healthcare_quiz_results', true ) ) ),
		'profile_done' => ! empty( vance_hub_dashboard_list( get_user_meta( $uid, '_sla_clinical_profile', true ) ) ),
		'plans'        => count( $plans ),
		'plan_date'    => $date( vance_hub_dashboard_latest_ts( $plans ) ),
		'checks'       => count( $checks ),
		'check_date'   => $date( vance_hub_dashboard_latest_ts( $checks ) ),
		'discounts'    => count( vance_hub_dashboard_list( get_user_meta( $uid, '_sla_saved_discounts', true ) ) ),
	);
	$enabled = array(
		'health-profile' => $on( 'health-profile' ),
		'my-recipes'     => $on( 'my-recipes' ),
	);

	// -- Next step ---------------------------------------------------------
	$tools_by_mod = array();
	foreach ( vance_hub_tools() as $tool ) {
		$tools_by_mod[ $tool['mod'] ] = $tool;
	}
	$steps = array(
		'survey'  => array(
			'title' => __( 'Do the Gastro Health Survey', 'vance-health-hub' ),
			'text'  => __( 'Your answers go into your Health Profile. The Hub then shows what fits you.', 'vance-health-hub' ),
			'cta'   => __( 'Start the survey', 'vance-health-hub' ),
			'href'  => isset( $tools_by_mod['survey'] ) ? $tools_by_mod['survey']['href'] : '',
		),
		'profile' => array(
			'title' => __( 'Complete your Health Profile', 'vance-health-hub' ),
			'text'  => __( 'Add your condition and your care details. You can change them at any time.', 'vance-health-hub' ),
			'cta'   => __( 'Open my Health Profile', 'vance-health-hub' ),
			'href'  => vance_hub_dashboard_tab_url( 'health-profile' ),
		),
		'plan'    => array(
			'title' => __( 'Build your first meal plan', 'vance-health-hub' ),
			'text'  => __( 'Choose recipes for the week. Your saved plans show on your dashboard.', 'vance-health-hub' ),
			'cta'   => __( 'Open the planner', 'vance-health-hub' ),
			'href'  => isset( $tools_by_mod['recipes'] ) ? $tools_by_mod['recipes']['href'] : '',
		),
		'check'   => array(
			'title' => __( 'Check your nutrition risk', 'vance-health-hub' ),
			'text'  => __( 'A short check with a clear result. Your saved checks show in your Health Profile.', 'vance-health-hub' ),
			'cta'   => __( 'Start the check', 'vance-health-hub' ),
			'href'  => isset( $tools_by_mod['nutrition'] ) ? $tools_by_mod['nutrition']['href'] : '',
		),
	);
	$step_key = vance_hub_dashboard_next_step( $state, $enabled );
	$step     = ( '' !== $step_key && '' !== $steps[ $step_key ]['href'] ) ? $steps[ $step_key ] : null;

	// -- Tools, each with the member's own progress -------------------------
	$tools = array();
	foreach ( $tools_by_mod as $mod => $tool ) {
		// The discounts tile follows its dashboard tab, like the classic card.
		if ( 'discounts' === $mod && ! $on( 'discounts' ) ) {
			continue;
		}
		$status = vance_hub_dashboard_tool_status( $mod, $state );
		// A member who has done the survey goes to the answers, not to a
		// button that says "Start".
		if ( 'survey' === $mod && $status['done'] && $enabled['health-profile'] ) {
			$tool['cta']  = __( 'See my answers', 'vance-health-hub' );
			$tool['href'] = vance_hub_dashboard_tab_url( 'health-profile' );
		}
		$tools[] = array(
			'mod'    => $mod,
			'icon'   => $tool['icon'],
			'title'  => $tool['title'],
			'cta'    => $tool['cta'],
			'href'   => $tool['href'],
			'img'    => $tool['img'],
			'done'   => $status['done'],
			'status' => $status['text'],
		);
	}

	// -- Saved-item counts, one tile per enabled tab -------------------------
	$counts = array(
		'reading-list' => array( count( $bookmarks ), __( 'Saved articles', 'vance-health-hub' ) ),
		'notes'        => array( count( vance_hub_dashboard_list( get_user_meta( $uid, '_sla_user_notes', true ) ) ), __( 'Notes', 'vance-health-hub' ) ),
		'ai-chats'     => array( count( vance_hub_dashboard_list( get_user_meta( $uid, '_sla_saved_chats', true ) ) ), __( 'VANCE-Ai chats', 'vance-health-hub' ) ),
		'documents'    => array( count( vance_hub_dashboard_list( get_user_meta( $uid, '_sla_profile_docs', true ) ) ), __( 'Documents', 'vance-health-hub' ) ),
	);
	$stats = array();
	foreach ( $counts as $slug => $row ) {
		if ( $on( $slug ) ) {
			$stats[] = array(
				'slug'  => $slug,
				'count' => (int) $row[0],
				'label' => $label( $slug, $row[1] ),
				'href'  => vance_hub_dashboard_tab_url( $slug ),
			);
		}
	}

	// -- Saved articles: the newest four the member bookmarked --------------
	$saved = array();
	if ( $on( 'reading-list' ) && ! empty( $bookmarks ) ) {
		$query = new WP_Query( array(
			'post__in'            => array_slice( array_reverse( $bookmarks ), 0, 20 ),
			'post_type'           => 'any',
			'post_status'         => 'publish',
			'posts_per_page'      => 4,
			'orderby'             => 'post__in',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		) );
		vance_hub_prime_thumbnails( $query );
		foreach ( $query->posts as $post ) {
			$saved[] = vance_hub_post_card( $post, 'medium' );
		}
	}

	// -- Messages from the team ---------------------------------------------
	$messages = array();
	$unread   = 0;
	if ( $on( 'messages' ) && function_exists( 'vance_admin_messages_for_user' ) ) {
		$posts      = (array) vance_admin_messages_for_user( $uid, true );
		$read_lists = array();
		foreach ( $posts as $i => $post ) {
			$read_lists[ $i ] = get_post_meta( $post->ID, '_sla_msg_read_by', true );
		}
		$unread = vance_hub_dashboard_unread_count( $read_lists, $uid );
		foreach ( array_slice( $posts, 0, 3, true ) as $i => $post ) {
			$messages[] = array(
				'title'  => (string) get_the_title( $post ),
				'date'   => (string) get_the_date( 'j M Y', $post ),
				'unread' => 1 === vance_hub_dashboard_unread_count( array( $read_lists[ $i ] ), $uid ),
			);
		}
	}

	// -- New in the library: newest posts the member has not saved ----------
	$fresh = array();
	$query = new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'post__not_in'        => $bookmarks,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
	vance_hub_prime_thumbnails( $query );
	foreach ( $query->posts as $post ) {
		$fresh[] = vance_hub_post_card( $post, 'medium' );
	}

	// -- Optional blocks: member number, results chart, weekly check-in -------
	$features = function_exists( 'vance_hub_dashboard_features_data' )
		? vance_hub_dashboard_features_data( $user, $checks, isset( $tools_by_mod['nutrition'] ) ? $tools_by_mod['nutrition']['href'] : '' )
		: array();

	return $features + array(
		'name'           => $first,
		'since'          => $joined ? (string) wp_date( 'F Y', $joined ) : '',
		'ask'            => $on( 'ai-chats' ),
		'step'           => $step,
		'tools'          => $tools,
		'stats'          => $stats,
		'saved'          => $saved,
		'saved_label'    => $label( 'reading-list', __( 'Saved articles', 'vance-health-hub' ) ),
		'saved_href'     => vance_hub_dashboard_tab_url( 'reading-list' ),
		'show_messages'  => $on( 'messages' ) && function_exists( 'vance_admin_messages_for_user' ),
		'messages'       => $messages,
		'unread'         => $unread,
		'messages_label' => $label( 'messages', __( 'Messages', 'vance-health-hub' ) ),
		'messages_href'  => vance_hub_dashboard_tab_url( 'messages' ),
		'fresh'          => $fresh,
	);
}

/* =========================================================================
 * Render
 * ====================================================================== */

/**
 * One article row (thumbnail, category, title, date).
 *
 * @param array $card vance_hub_post_card() row.
 * @return string
 */
function vance_hub_dashboard_post_row_html( $card ) {
	$html = '<li><a class="vhd-post" href="' . esc_url( $card['url'] ) . '">';
	if ( '' !== $card['img'] ) {
		// alt="" on purpose: the headline inside this link names the article.
		$html .= '<img class="vhd-post__img" src="' . esc_url( $card['img'] ) . '" alt="" loading="lazy" decoding="async" width="72" height="72">';
	} else {
		$html .= '<span class="vhd-post__img vhd-post__img--none" aria-hidden="true"></span>';
	}
	$html .= '<span class="vhd-post__body">';
	if ( '' !== $card['cat'] ) {
		$html .= '<span class="vhd-post__cat">' . esc_html( $card['cat'] ) . '</span>';
	}
	$html .= '<span class="vhd-post__title">' . esc_html( $card['title'] ) . '</span>';
	if ( '' !== $card['date'] ) {
		$html .= '<time class="vhd-post__date" datetime="' . esc_attr( $card['iso'] ) . '">' . esc_html( $card['date'] ) . '</time>';
	}
	$html .= '</span></a></li>';
	return $html;
}

/**
 * The hub dashboard home as HTML.
 *
 * @param array $d vance_hub_dashboard_data() result.
 * @return string
 */
function vance_hub_dashboard_home_html( $d ) {
	$arrow = ' <span aria-hidden="true">&rarr;</span>';
	$html  = '<div class="vhd">';

	// -- Greeting ----------------------------------------------------------
	/* translators: %s: member's first name. */
	$hello = sprintf( __( 'Hello, %s', 'vance-health-hub' ), $d['name'] );
	if ( ! empty( $d['menu_card'] ) ) {
		// The side menu's member card already shows the name, the join month and
		// the member number, so the page keeps only its heading, for screen readers.
		$html .= '<h1 class="vhd-sr">' . esc_html( $hello ) . '</h1>';
	} else {
		$html .= '<section class="vhd-hello" aria-labelledby="vhd-hello-title"><div class="vhd-hello__copy">';
		$html .= '<p class="vhd-eyebrow">' . esc_html__( 'My Hub', 'vance-health-hub' ) . '</p>';
		$html .= '<h1 id="vhd-hello-title" class="vhd-hello__title">' . esc_html( $hello ) . '</h1>';
		if ( '' !== $d['since'] ) {
			/* translators: %s: month and year the member joined. */
			$html .= '<p class="vhd-hello__since">' . esc_html( sprintf( __( 'Member since %s', 'vance-health-hub' ), $d['since'] ) ) . '</p>';
		}
		if ( ! empty( $d['member_code'] ) ) {
			/* translators: %s: the member's code. */
			$html .= '<p class="vhd-hello__code">' . esc_html( sprintf( __( 'Member number %s', 'vance-health-hub' ), $d['member_code'] ) ) . '</p>';
		}
		$html .= '</div></section>';
	}

	// -- Top row: next step, Ask VANCE-Ai, weekly check-in ------------------
	$top = '';
	if ( ! empty( $d['step'] ) ) {
		$step = $d['step'];
		$top .= '<section class="vhd-step" aria-labelledby="vhd-step-title"><div class="vhd-step__copy">';
		$top .= '<p class="vhd-eyebrow vhd-eyebrow--light">' . esc_html__( 'Your next step', 'vance-health-hub' ) . '</p>';
		$top .= '<h2 id="vhd-step-title" class="vhd-step__title">' . esc_html( $step['title'] ) . '</h2>';
		$top .= '<p class="vhd-step__text">' . esc_html( $step['text'] ) . '</p></div>';
		$top .= '<a class="vhd-btn vhd-btn--white" href="' . esc_url( $step['href'] ) . '">' . esc_html( $step['cta'] ) . $arrow . '</a>';
		$top .= '</section>';
	}
	if ( ! empty( $d['ask'] ) ) {
		// Opens the shared VANCE-Ai modal on a new conversation, the same hook
		// the classic "New Chat" button uses.
		$top .= '<section class="vhd-ask" aria-labelledby="vhd-ask-title">';
		$top .= '<p class="vhd-eyebrow vhd-eyebrow--light">' . esc_html__( 'VANCE-Ai', 'vance-health-hub' ) . '</p>';
		$top .= '<h2 id="vhd-ask-title" class="vhd-ask__title">' . esc_html__( 'Need an answer now?', 'vance-health-hub' ) . '</h2>';
		$top .= '<p class="vhd-ask__text">' . esc_html__( 'VANCE-Ai answers at any hour and shows its sources.', 'vance-health-hub' ) . '</p>';
		$top .= '<button type="button" class="vhd-btn vhd-btn--white" data-vance-askai-open="new">' . esc_html__( 'Ask VANCE-Ai', 'vance-health-hub' ) . $arrow . '</button>';
		$top .= '</section>';
	}
	if ( ! empty( $d['checkin'] ) && function_exists( 'vance_hub_dashboard_checkin_html' ) ) {
		$top .= vance_hub_dashboard_checkin_html( $d['checkin'] );
	}
	if ( '' !== $top ) {
		$html .= '<div class="vhd-top">' . $top . '</div>';
	}

	// -- Flare plan: full width, after the top row ---------------------------
	if ( ! empty( $d['flare_plan'] ) && function_exists( 'vance_hub_flare_plan_html' ) ) {
		$html .= vance_hub_flare_plan_html( $d['flare_plan'] );
	}

	// -- Tools -------------------------------------------------------------
	if ( ! empty( $d['tools'] ) ) {
		$html .= '<section class="vhd-block" aria-labelledby="vhd-tools-title">';
		$html .= '<h2 id="vhd-tools-title" class="vhd-h2">' . esc_html__( 'My tools', 'vance-health-hub' ) . '</h2>';
		$html .= '<ul class="vhd-tools">';
		foreach ( $d['tools'] as $tool ) {
			$html .= '<li><a class="vhd-tool vhd-tool--' . esc_attr( $tool['mod'] ) . '" href="' . esc_url( $tool['href'] ) . '">';
			if ( '' !== $tool['img'] ) {
				// alt="" on purpose: decorative, the tile title names the tool.
				$html .= '<img class="vhd-tool__img" src="' . esc_url( $tool['img'] ) . '" alt="" loading="lazy" decoding="async">';
			}
			$html .= '<span class="vhd-tool__body">';
			$html .= '<span class="vhd-tool__title">' . vance_hub_icon( $tool['icon'] ) . esc_html( $tool['title'] ) . '</span>';
			if ( '' !== $tool['status'] ) {
				$html .= '<span class="vhd-tool__status' . ( $tool['done'] ? ' is-done' : '' ) . '">' . ( $tool['done'] ? vance_hub_icon( 'check' ) : '' ) . esc_html( $tool['status'] ) . '</span>';
			}
			$html .= '<span class="vhd-tool__cta">' . esc_html( $tool['cta'] ) . $arrow . '</span>';
			$html .= '</span></a></li>';
		}
		$html .= '</ul></section>';
	}

	// -- Results chart (optional) ------------------------------------------
	if ( ! empty( $d['results'] ) && function_exists( 'vance_hub_dashboard_results_html' ) ) {
		$html .= vance_hub_dashboard_results_html( $d['results'] );
	}

	// -- Saved-item counts ---------------------------------------------------
	if ( ! empty( $d['stats'] ) ) {
		$html .= '<section class="vhd-block" aria-labelledby="vhd-stats-title">';
		$html .= '<h2 id="vhd-stats-title" class="vhd-h2">' . esc_html__( 'My saved items', 'vance-health-hub' ) . '</h2>';
		$html .= '<ul class="vhd-stats">';
		foreach ( $d['stats'] as $stat ) {
			$html .= '<li><a class="vhd-stat" href="' . esc_url( $stat['href'] ) . '">';
			$html .= '<span class="vhd-stat__n">' . esc_html( number_format_i18n( $stat['count'] ) ) . '</span>';
			$html .= '<span class="vhd-stat__label">' . esc_html( $stat['label'] ) . '</span>';
			$html .= '</a></li>';
		}
		$html .= '</ul></section>';
	}

	// -- Saved articles and messages, side by side ---------------------------
	$has_saved = ! empty( $d['saved'] );
	$has_msgs  = ! empty( $d['show_messages'] );
	if ( $has_saved || $has_msgs ) {
		$html .= '<div class="vhd-cols' . ( $has_saved && $has_msgs ? '' : ' vhd-cols--one' ) . '">';
		if ( $has_saved ) {
			$html .= '<section class="vhd-card" aria-labelledby="vhd-saved-title"><div class="vhd-card__head">';
			$html .= '<h2 id="vhd-saved-title" class="vhd-h3">' . esc_html( $d['saved_label'] ) . '</h2>';
			$html .= '<a class="vhd-link" href="' . esc_url( $d['saved_href'] ) . '">' . esc_html__( 'See all', 'vance-health-hub' ) . $arrow . '</a></div>';
			$html .= '<ul class="vhd-posts">';
			foreach ( $d['saved'] as $card ) {
				$html .= vance_hub_dashboard_post_row_html( $card );
			}
			$html .= '</ul></section>';
		}
		if ( $has_msgs ) {
			$html .= '<section class="vhd-card" aria-labelledby="vhd-msgs-title"><div class="vhd-card__head">';
			$html .= '<h2 id="vhd-msgs-title" class="vhd-h3">' . esc_html( $d['messages_label'] ) . '</h2>';
			if ( $d['unread'] > 0 ) {
				/* translators: %s: number of unread messages. */
				$html .= '<span class="vhd-badge">' . esc_html( sprintf( _n( '%s new', '%s new', $d['unread'], 'vance-health-hub' ), number_format_i18n( $d['unread'] ) ) ) . '</span>';
			}
			$html .= '</div>';
			if ( empty( $d['messages'] ) ) {
				$html .= '<p class="vhd-empty">' . esc_html__( 'No messages from the team.', 'vance-health-hub' ) . '</p>';
			} else {
				$html .= '<ul class="vhd-msgs">';
				foreach ( $d['messages'] as $msg ) {
					$html .= '<li><a class="vhd-msg' . ( $msg['unread'] ? ' is-unread' : '' ) . '" href="' . esc_url( $d['messages_href'] ) . '">';
					$html .= '<span class="vhd-msg__title">' . esc_html( $msg['title'] ) . '</span>';
					$html .= '<span class="vhd-msg__meta">' . esc_html( $msg['date'] );
					if ( $msg['unread'] ) {
						$html .= ' &middot; ' . esc_html__( 'Not read', 'vance-health-hub' );
					}
					$html .= '</span></a></li>';
				}
				$html .= '</ul>';
				$html .= '<a class="vhd-link" href="' . esc_url( $d['messages_href'] ) . '">' . esc_html__( 'See all messages', 'vance-health-hub' ) . $arrow . '</a>';
			}
			$html .= '</section>';
		}
		$html .= '</div>';
	}

	// -- New in the library --------------------------------------------------
	if ( ! empty( $d['fresh'] ) ) {
		$html .= '<section class="vhd-card" aria-labelledby="vhd-fresh-title"><div class="vhd-card__head">';
		$html .= '<h2 id="vhd-fresh-title" class="vhd-h3">' . esc_html__( 'New in the library', 'vance-health-hub' ) . '</h2></div>';
		$html .= '<ul class="vhd-posts vhd-posts--row">';
		foreach ( $d['fresh'] as $card ) {
			$html .= vance_hub_dashboard_post_row_html( $card );
		}
		$html .= '</ul></section>';
	}

	$html .= '</div>';
	return $html;
}

/**
 * Print the hub dashboard home. Called from page-dashboard.php, `case 'home'`.
 *
 * @param WP_User $user The logged-in member.
 */
function vance_hub_dashboard_render_home( $user ) {
	if ( ! ( $user instanceof WP_User ) || ! $user->exists() ) {
		return;
	}
	$data = vance_hub_dashboard_data( $user );
	// The side menu shows the member card only with the VanceHubClub tab look,
	// and only when the member has a name: without it the card prints nothing,
	// so the page keeps its own greeting and member number.
	$card              = vance_hub_dashboard_member_card_data( $user );
	$data['menu_card'] = function_exists( 'vance_hub_dashboard_tabs_active' ) && vance_hub_dashboard_tabs_active() && '' !== $card['name'];
	echo vance_hub_dashboard_home_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every value is escaped in vance_hub_dashboard_home_html().
}
