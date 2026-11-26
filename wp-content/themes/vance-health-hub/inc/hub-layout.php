<?php
/**
 * Hub home layout: a second, switchable home page (tools and community first).
 *
 * The classic home page (front-page.php) stays the default. This file adds:
 *
 *   - a Customizer switch, "Home layout" (Home · Hero & Layout panel), with
 *     the values 'classic' (default) and 'hub';
 *   - an admin-only preview: ?vhh_layout=hub or ?vhh_layout=classic on the home
 *     page, honoured only for users who can edit_theme_options;
 *   - a template_include filter that swaps front-page.php for
 *     front-page-hub.php on the front page when the hub layout is active;
 *   - assets/css/hub-home.css and assets/js/hub-home.js, enqueued only when the
 *     hub layout renders;
 *   - the data helpers front-page-hub.php reads.
 *
 * THE DATA RULE. Every number, name and post on the hub home comes from a real
 * query or a Customizer setting. A section with nothing real behind it does not
 * render. Nothing here invents a figure, a person or a feature.
 *
 * The functions in the first block are pure (no WordPress calls) so they can be
 * tested with stubs. To back the whole feature out, delete the require line in
 * functions.php: the classic home page then renders exactly as before.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The shared preview resolver (vance_hub_preview_query).
require_once __DIR__ . '/hub-preview.php';

/* =========================================================================
 * Pure helpers (no WordPress calls)
 * ====================================================================== */

/**
 * The two home layouts. The first one is the default.
 *
 * @return string[]
 */
function vance_hub_layout_choices() {
	return array( 'classic', 'hub' );
}

/**
 * Sanitize a stored layout value. Anything unknown becomes 'classic'.
 *
 * @param mixed $value Raw value.
 * @return string 'classic' or 'hub'.
 */
function vance_hub_sanitize_layout( $value ) {
	$value = is_string( $value ) ? strtolower( trim( $value ) ) : '';
	return in_array( $value, vance_hub_layout_choices(), true ) ? $value : 'classic';
}

/**
 * Decide the layout from the saved mod, the preview query value and whether
 * the viewer may preview.
 *
 * A preview value is honoured only when $can_preview is true, and only when it
 * is one of the two known layouts. Any other query value is ignored, so the
 * saved mod decides.
 *
 * @param mixed $mod_value   Saved theme mod value.
 * @param mixed $query_value Value of ?vhh_layout= ('' when absent).
 * @param bool  $can_preview True when the viewer may preview layouts.
 * @return string 'classic' or 'hub'.
 */
function vance_hub_resolve_layout( $mod_value, $query_value, $can_preview ) {
	if ( $can_preview && is_string( $query_value ) ) {
		$query_value = strtolower( trim( $query_value ) );
		if ( in_array( $query_value, vance_hub_layout_choices(), true ) ) {
			return $query_value;
		}
	}
	return vance_hub_sanitize_layout( $mod_value );
}

/**
 * Sanitize a date setting to 'Y-m-d', or '' when it is not a real date.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function vance_hub_sanitize_date( $value ) {
	$value = is_string( $value ) ? trim( $value ) : '';
	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) ) {
		return '';
	}
	return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $value : '';
}

/**
 * True when a deadline date is valid and has not passed. The deadline day
 * itself still counts, so the copy reads "Join by <date>".
 *
 * Both values are 'Y-m-d' strings, so a string comparison orders them.
 *
 * @param mixed  $deadline Deadline date.
 * @param string $today    Today's date in the site's time zone, 'Y-m-d'.
 * @return bool
 */
function vance_hub_date_open( $deadline, $today ) {
	$deadline = vance_hub_sanitize_date( $deadline );
	$today    = vance_hub_sanitize_date( $today );
	if ( '' === $deadline || '' === $today ) {
		return false;
	}
	return strcmp( $deadline, $today ) >= 0;
}

/**
 * What the purple member bar shows.
 *
 *   'counts'   - total members (and joined this week), when the total has
 *                reached the threshold;
 *   'founding' - the Founding Member line, for logged-out visitors only, when
 *                the total is below the threshold and a deadline is open;
 *   ''         - nothing: the bar does not render.
 *
 * @param int  $total         Real number of registered users.
 * @param int  $threshold     Minimum total before counts show.
 * @param bool $founding_open True when the founding deadline is set and open.
 * @param bool $logged_in     True when the viewer is logged in.
 * @return string
 */
function vance_hub_member_bar_mode( $total, $threshold, $founding_open, $logged_in ) {
	$total     = max( 0, (int) $total );
	$threshold = max( 0, (int) $threshold );
	if ( $total > 0 && $total >= $threshold ) {
		return 'counts';
	}
	if ( $founding_open && ! $logged_in ) {
		return 'founding';
	}
	return '';
}

/**
 * Keep only the member tiles that have a title, at most four, in order.
 *
 * @param array $tiles List of arrays with keys badge, title, text, link.
 * @return array
 */
function vance_hub_filter_tiles( $tiles ) {
	$out = array();
	if ( ! is_array( $tiles ) ) {
		return $out;
	}
	foreach ( $tiles as $tile ) {
		if ( ! is_array( $tile ) ) {
			continue;
		}
		$title = isset( $tile['title'] ) ? trim( (string) $tile['title'] ) : '';
		if ( '' === $title ) {
			continue;
		}
		$out[] = array(
			'badge' => isset( $tile['badge'] ) ? trim( (string) $tile['badge'] ) : '',
			'title' => $title,
			'text'  => isset( $tile['text'] ) ? trim( (string) $tile['text'] ) : '',
			'link'  => isset( $tile['link'] ) ? trim( (string) $tile['link'] ) : '',
		);
		if ( count( $out ) >= 4 ) {
			break;
		}
	}
	return $out;
}

/**
 * Split the condition registry into photo tiles and a plain list.
 *
 * Featured slugs that are not in the registry are skipped. Everything else in
 * the registry goes to the list, in registry order.
 *
 * @param array    $cards    vance_gi_condition_cards() rows (each has 'slug').
 * @param string[] $featured Slugs to show as photo tiles, in display order.
 * @return array{0: array, 1: array} Photo tiles, then the rest.
 */
function vance_hub_split_conditions( $cards, $featured ) {
	$by_slug = array();
	foreach ( (array) $cards as $card ) {
		if ( is_array( $card ) && ! empty( $card['slug'] ) ) {
			$by_slug[ $card['slug'] ] = $card;
		}
	}
	$tiles = array();
	foreach ( (array) $featured as $slug ) {
		if ( isset( $by_slug[ $slug ] ) ) {
			$tiles[] = $by_slug[ $slug ];
			unset( $by_slug[ $slug ] );
		}
	}
	return array( $tiles, array_values( $by_slug ) );
}

/**
 * Home page tile overrides: the symptoms picture from each condition page
 * (page-gi-condition.php, "Symptoms" row) and a shorter tile name.
 *
 * Only the home page uses this. The registry, the condition pages and the
 * GI hub keep the full titles and their own photographs.
 *
 * @return array<string, array{image:string, alt:string, name?:string}> Keyed by slug.
 */
function vance_hub_condition_tile_overrides() {
	return array(
		'crohns-disease'       => array(
			'image' => 'gi-health/conditions/symptoms-crohns.webp',
			'alt'   => __( "Body silhouette showing common Crohn's symptoms", 'vance-health-hub' ),
		),
		'ulcerative-colitis'   => array(
			'image' => 'gi-health/conditions/symptoms-uc.webp',
			'alt'   => __( 'Body silhouette showing common UC symptoms', 'vance-health-hub' ),
		),
		'diverticular-disease' => array(
			'image' => 'gi-health/conditions/symptoms-dd.webp',
			'alt'   => __( 'Body silhouette showing common Diverticular Disease symptoms', 'vance-health-hub' ),
			'name'  => __( 'Diverticular Disease', 'vance-health-hub' ),
		),
	);
}

/**
 * What one home page condition tile shows: picture, alt text and name.
 *
 * The override picture is used only when its file exists; otherwise the
 * registry photograph, otherwise no picture (the tile stays navy).
 *
 * @param array $card A vance_gi_condition_cards() row.
 * @return array{img:string, alt:string, name:string}
 */
function vance_hub_condition_tile( $card ) {
	$card  = is_array( $card ) ? $card : array();
	$slug  = isset( $card['slug'] ) ? (string) $card['slug'] : '';
	$over  = vance_hub_condition_tile_overrides();
	$over  = isset( $over[ $slug ] ) ? $over[ $slug ] : array();
	$name  = isset( $over['name'] ) ? $over['name'] : wp_specialchars_decode( isset( $card['title'] ) ? (string) $card['title'] : '' );
	$img   = isset( $over['image'] ) ? vance_hub_img( $over['image'] ) : '';
	$alt   = $img && isset( $over['alt'] ) ? $over['alt'] : '';
	if ( '' === $img && ! empty( $card['image'] ) ) {
		$img = vance_hub_img( 'gi-health/' . $card['image'] );
		$alt = ( $img && isset( $card['alt'] ) ) ? (string) $card['alt'] : '';
	}
	return array( 'img' => $img, 'alt' => $alt, 'name' => $name );
}

/**
 * Sanitize a Customizer checkbox to a real boolean.
 *
 * The Customizer posts '' or '1' for a checkbox; a stored mod can also be a
 * boolean. Anything that is not clearly "on" is off.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function vance_hub_sanitize_checkbox( $value ) {
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( is_int( $value ) ) {
		return 1 === $value;
	}
	if ( is_string( $value ) ) {
		return in_array( strtolower( trim( $value ) ), array( '1', 'true', 'on', 'yes' ), true );
	}
	return false;
}

/**
 * The inline style that gives the hub hero the classic spotlight hero's
 * colours. main.css paints .vhh-hero-spotlight with
 * linear-gradient(var(--vhh-hs-from), var(--vhh-hs-to)), and
 * inc/hero-spotlight.php sets those two properties inline on that element
 * only, from the vance_hero_spotlight_bg_from / _bg_to theme mods. The hub
 * hero reads the same two mods, so a colour the owner picks for the classic
 * hero follows through here.
 *
 * A value that is not a hex colour falls back to the classic default, so a
 * bad mod cannot inject CSS through the style attribute.
 *
 * @param mixed $from Top colour.
 * @param mixed $to   Bottom colour.
 * @return string
 */
function vance_hub_hero_style( $from, $to ) {
	$hex  = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';
	$from = is_string( $from ) && preg_match( $hex, trim( $from ) ) ? trim( $from ) : '#ECF5F5';
	$to   = is_string( $to ) && preg_match( $hex, trim( $to ) ) ? trim( $to ) : '#F6F9FA';
	return '--vhh-hs-from: ' . $from . '; --vhh-hs-to: ' . $to . ';';
}

/**
 * The top-level content sections the "Latest by section" block can show, in
 * display order. These are the category slugs the section archives are built
 * on (category-content-*.php, inc/category-hero.php,
 * inc/article-schema-types.php). A slug with no term on the site is skipped
 * when the block is built, so the list can be longer than the site.
 *
 * @return string[]
 */
function vance_hub_section_slugs() {
	$slugs = apply_filters( 'vance_hub_section_slugs', array(
		'content-gastro-living',
		'content-health-news',
		'content-clinical-reviews',
	) );
	return is_array( $slugs ) ? array_values( array_unique( array_filter( $slugs, 'is_string' ) ) ) : array();
}

/* =========================================================================
 * The switch
 * ====================================================================== */

/**
 * The saved layout mod, read through the theme's helper.
 *
 * @return string 'classic' or 'hub'.
 */
function vance_hub_saved_layout() {
	return vance_hub_sanitize_layout( vance_get_theme_mod( 'vance_home_layout', 'classic' ) );
}

/**
 * The master switch: true when the saved Home layout is VanceHubClub.
 *
 * "Home layout" turns every VanceHubClub part on and off together. With it on,
 * each part follows its own switch, so an administrator can turn single parts
 * off. With it off, every part is off, whatever its own switch says.
 * An administrator's preview (?vhh_layout=, preview mode, share link) is
 * applied by each part's resolver and is not changed by this.
 *
 * @return bool
 */
function vance_hub_master_on() {
	return 'hub' === vance_hub_saved_layout();
}

/**
 * Default of each part's own switch. A part that was never saved is on, so
 * the master switch turns everything on. The weekly check-in is the one
 * exception: its answers are health data, and its wording and the privacy
 * policy must be approved before it shows.
 *
 * This is the ONE place for these defaults: the Customizer registrations, the
 * VanceHubClub admin page and the gates all read it.
 *
 * @param string $mod Theme mod name.
 * @return bool
 */
function vance_hub_switch_default( $mod ) {
	// Health-data cards stay off until the privacy policy covers them.
	return ! in_array( $mod, array( 'vance_hub_dash_checkin', 'vance_hub_dash_flare_plan' ), true );
}

/**
 * A part's saved state under the master switch: the master is on AND the
 * part's own switch is on.
 *
 * @param string $mod Theme mod name.
 * @return bool
 */
function vance_hub_switch_on( $mod ) {
	return vance_hub_master_on()
		&& (bool) vance_hub_sanitize_checkbox( vance_get_theme_mod( $mod, vance_hub_switch_default( $mod ) ) );
}

/**
 * True when the hub home layout is active for this request.
 *
 * @return bool
 */
function vance_hub_layout_active() {
	// vance_hub_preview_query() reads ?vhh_layout= only for users who may
	// preview (and only as a string), then falls back to preview mode.
	$query = vance_hub_preview_query();
	return 'hub' === vance_hub_resolve_layout( vance_get_theme_mod( 'vance_home_layout', 'classic' ), $query, '' !== $query );
}

/**
 * True when this request renders the hub home page.
 *
 * @return bool
 */
function vance_hub_is_hub_home() {
	return is_front_page() && vance_hub_layout_active();
}

/**
 * Swap front-page.php for front-page-hub.php when the hub layout is active.
 *
 * Only the classic home template is replaced. Any other template WordPress
 * chose for the front page (a feed, a 404, a plugin's template) is left alone.
 *
 * @param string $template Template WordPress resolved.
 * @return string
 */
function vance_hub_template_include( $template ) {
	if ( 'front-page.php' !== basename( (string) $template ) ) {
		return $template;
	}
	if ( ! vance_hub_is_hub_home() ) {
		return $template;
	}
	$hub = locate_template( 'front-page-hub.php' );
	return $hub ? $hub : $template;
}
add_filter( 'template_include', 'vance_hub_template_include', 20 );

/**
 * Hub stylesheet and script, only when the hub home renders.
 */
function vance_hub_enqueue_assets() {
	if ( ! vance_hub_is_hub_home() ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style(
		'vance-hub-home',
		$uri . '/assets/css/hub-home.css',
		array( 'vance-main-style' ),
		@filemtime( $dir . '/assets/css/hub-home.css' ) ?: '1.0.0'
	);
	wp_enqueue_script(
		'vance-hub-home',
		$uri . '/assets/js/hub-home.js',
		array(),
		@filemtime( $dir . '/assets/js/hub-home.js' ) ?: '1.0.0',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'vance_hub_enqueue_assets', 20 );

/**
 * Body class for the hub home.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function vance_hub_body_class( $classes ) {
	if ( vance_hub_is_hub_home() ) {
		$classes[] = 'vhub-home';
	}
	return $classes;
}
add_filter( 'body_class', 'vance_hub_body_class' );

/**
 * Drop has-vance-swipecards on the hub home.
 *
 * vance_mobile_body_class() (functions.php) adds it on any front page when the
 * swipe-card setting is on. The swipe cards upgrade the classic home's
 * category-card grid, which the hub home does not have, so the class would
 * describe something that is not on the page. No stylesheet or script keys
 * off the class today; removing it keeps it that way if one is added later.
 * Runs at priority 20, after the theme's filter.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function vance_hub_body_class_cleanup( $classes ) {
	if ( vance_hub_is_hub_home() ) {
		$classes = array_values( array_diff( (array) $classes, array( 'has-vance-swipecards' ) ) );
	}
	return $classes;
}
add_filter( 'body_class', 'vance_hub_body_class_cleanup', 20 );

/* =========================================================================
 * Customizer
 * ====================================================================== */

/**
 * The VanceHubClub panel of the Customizer.
 */
const VANCE_HUB_CUSTOMIZER_PANEL = 'vance_hub_panel';

/**
 * The sections of the VanceHubClub panel, in screen order.
 *
 * This is the only place that names the sections. Every hub file registers
 * its controls into one of these ids. The number in each title is the order
 * of setup. The first section keeps the id 'vance_home_layout', which is also
 * the name of the master setting.
 *
 * @return array[] Section id => title and description.
 */
function vance_hub_customizer_sections() {
	return array(
		'vance_home_layout'       => array(
			'title'       => __( '1. On or off', 'vance-health-hub' ),
			'description' => __( 'The master switch for all of VanceHubClub.', 'vance-health-hub' ),
		),
		'vance_hub_sec_home'      => array(
			'title'       => __( '2. Home page', 'vance-health-hub' ),
			'description' => __( 'The parts of the VanceHubClub home page.', 'vance-health-hub' ),
		),
		'vance_hub_sec_header'    => array(
			'title'       => __( '3. Header and member bar', 'vance-health-hub' ),
			'description' => __( 'The header on every page, and the purple member bar below it.', 'vance-health-hub' ),
		),
		'vance_hub_sec_pages'     => array(
			'title'       => __( '4. Articles and Self-Assessment', 'vance-health-hub' ),
			'description' => __( 'The article pages and the end of the Gastro Self-Assessment.', 'vance-health-hub' ),
		),
		'vance_hub_sec_dashboard' => array(
			'title'       => __( '5. Member dashboard', 'vance-health-hub' ),
			'description' => __( 'What a member sees after sign in.', 'vance-health-hub' ),
		),
		'vance_hub_sec_community' => array(
			'title'       => __( '6. Community', 'vance-health-hub' ),
			'description' => __( 'The Community hub at /community/.', 'vance-health-hub' ),
		),
		'vance_hub_sec_checkin'   => array(
			'title'       => __( '7. Daily check-in (health data)', 'vance-health-hub' ),
			'description' => __( 'The answers are health data. Get the wording and the privacy policy approved before you turn the check-in on.', 'vance-health-hub' ),
		),
		'vance_hub_sec_flare'     => array(
			'title'       => __( '8. Flare plan (health data)', 'vance-health-hub' ),
			'description' => __( 'The plan is health data. Turn it on only when the privacy policy covers it and a clinician has approved the urgent help text.', 'vance-health-hub' ),
		),
	);
}

/**
 * The on/off switches: section and label, in screen order.
 *
 * The Customizer registrations and the Vance Club Layout screen
 * (inc/hub-admin.php) read this list, so the two always show the same words.
 *
 * @return array[] Setting id => section id and label.
 */
function vance_hub_customizer_switches() {
	return array(
		'vance_hub_show_promo'      => array( 'section' => 'vance_hub_sec_home', 'label' => __( 'Show the promo block', 'vance-health-hub' ) ),
		'vance_hub_show_sections'   => array( 'section' => 'vance_hub_sec_home', 'label' => __( 'Show "Latest by section"', 'vance-health-hub' ) ),
		'vance_hub_show_more'       => array( 'section' => 'vance_hub_sec_home', 'label' => __( 'Show "More to read"', 'vance-health-hub' ) ),
		'vance_hub_header'          => array( 'section' => 'vance_hub_sec_header', 'label' => __( 'Use the VanceHubClub header', 'vance-health-hub' ) ),
		'vance_hub_articles'        => array( 'section' => 'vance_hub_sec_pages', 'label' => __( 'Use the VanceHubClub article layout', 'vance-health-hub' ) ),
		'vance_hub_tool_result'     => array( 'section' => 'vance_hub_sec_pages', 'label' => __( 'Show the Self-Assessment result page', 'vance-health-hub' ) ),
		'vance_hub_show_dashboard'  => array( 'section' => 'vance_hub_sec_dashboard', 'label' => __( 'Use the VanceHubClub dashboard home', 'vance-health-hub' ) ),
		'vance_hub_dashboard_tabs'  => array( 'section' => 'vance_hub_sec_dashboard', 'label' => __( 'Use the VanceHubClub look on every tab', 'vance-health-hub' ) ),
		'vance_hub_dash_member_no'  => array( 'section' => 'vance_hub_sec_dashboard', 'label' => __( 'Show the member number', 'vance-health-hub' ) ),
		'vance_hub_dash_chart'      => array( 'section' => 'vance_hub_sec_dashboard', 'label' => __( 'Show the results chart', 'vance-health-hub' ) ),
		'vance_hub_community'       => array( 'section' => 'vance_hub_sec_community', 'label' => __( 'Turn on the Community hub', 'vance-health-hub' ) ),
		'vance_hub_dash_checkin'    => array( 'section' => 'vance_hub_sec_checkin', 'label' => __( 'Show the daily check-in', 'vance-health-hub' ) ),
		'vance_hub_dash_flare_plan' => array( 'section' => 'vance_hub_sec_flare', 'label' => __( 'Show the flare plan', 'vance-health-hub' ) ),
	);
}

/**
 * Section and label of one switch, for its Customizer control.
 *
 * @param string $mod Setting id of the switch.
 * @return array 'section' and 'label'.
 */
function vance_hub_customizer_switch( $mod ) {
	$switches = vance_hub_customizer_switches();
	return $switches[ $mod ];
}

/**
 * Show a control only while "Use the VanceHubClub dashboard home" is on in
 * the Customizer. With no such setting, the control shows.
 *
 * @param WP_Customize_Control $control The control.
 * @return bool
 */
function vance_hub_customizer_dashboard_home_on( $control ) {
	$setting = $control->manager->get_setting( 'vance_hub_show_dashboard' );
	return $setting ? (bool) vance_hub_sanitize_checkbox( $setting->value() ) : true;
}

/**
 * Register the VanceHubClub panel, its sections, and the settings that this
 * file owns: the master switch, the home page and the member bar.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vance_hub_customize_register( $wp_customize ) {
	$wp_customize->add_panel( VANCE_HUB_CUSTOMIZER_PANEL, array(
		// Top of the Customizer: this panel changes the layout of the whole site.
		'title'       => __( 'VanceHubClub', 'vance-health-hub' ),
		'priority'    => 1,
		'description' => __( 'VanceHubClub is the new site layout. It puts the free tools and the community first. The classic layout is VanceHealthHub. Do section 1 first. Then set each part in sections 2 to 8. To see VanceHubClub before you turn it on, use Preview mode on the Vance Club Layout screen (VanceHealthHub menu), or add ?vhh_layout=hub to one page address. Only administrators see a preview.', 'vance-health-hub' ),
	) );

	$order = 0;
	foreach ( vance_hub_customizer_sections() as $id => $section ) {
		$order += 10;
		$wp_customize->add_section( $id, array(
			'title'       => $section['title'],
			'description' => $section['description'],
			'panel'       => VANCE_HUB_CUSTOMIZER_PANEL,
			'priority'    => $order,
		) );
	}

	/* ---- 1. On or off ---- */

	$wp_customize->add_setting( 'vance_home_layout', array(
		'default'           => 'classic',
		'sanitize_callback' => 'vance_hub_sanitize_layout',
	) );
	$wp_customize->add_control( 'vance_home_layout', array(
		'label'       => __( 'Site layout', 'vance-health-hub' ),
		'description' => __( 'Classic: every VanceHubClub part is off. VanceHubClub: every part is on, unless you turn that part off in sections 2 to 8. The daily check-in and the flare plan are different: they start off, and you turn them on in sections 7 and 8. To see one page in a layout with no change to the site, add ?vhh_layout=hub or ?vhh_layout=classic to its address.', 'vance-health-hub' ),
		'section'     => 'vance_home_layout',
		'type'        => 'radio',
		'priority'    => 10,
		'choices'     => array(
			'classic' => __( 'Classic (VanceHealthHub)', 'vance-health-hub' ),
			'hub'     => __( 'VanceHubClub', 'vance-health-hub' ),
		),
	) );

	/* ---- 3. Header and member bar (the header switch is in inc/hub-header.php) ---- */

	$wp_customize->add_setting( 'vance_hub_member_min', array(
		'default'           => 100,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'vance_hub_member_min', array(
		'label'       => __( 'Show the member total from this number', 'vance-health-hub' ),
		'description' => __( 'The member bar shows the real member total only when the total reaches this number. Below this number, the bar shows the Founding Member line (if a deadline is set) or nothing.', 'vance-health-hub' ),
		'section'     => 'vance_hub_sec_header',
		'type'        => 'number',
		'priority'    => 20,
		'input_attrs' => array( 'min' => 0, 'step' => 1 ),
	) );

	$wp_customize->add_setting( 'vance_hub_founding_deadline', array(
		'default'           => '',
		'sanitize_callback' => 'vance_hub_sanitize_date',
	) );
	$wp_customize->add_control( 'vance_hub_founding_deadline', array(
		'label'       => __( 'Founding Member deadline', 'vance-health-hub' ),
		'description' => __( 'Logged-out visitors see the Founding Member line until the end of this date. Leave empty to hide the line.', 'vance-health-hub' ),
		'section'     => 'vance_hub_sec_header',
		'type'        => 'date',
		'priority'    => 30,
	) );

	/* ---- 2. Home page ---- */

	$switch = vance_hub_customizer_switch( 'vance_hub_show_promo' );
	$wp_customize->add_setting( 'vance_hub_show_promo', array(
		'default'           => true,
		'sanitize_callback' => 'vance_hub_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'vance_hub_show_promo', array(
		'label'       => $switch['label'],
		'description' => __( 'This is the same promo block as on the classic home page. Its content and its own switch are in Home · Blocks > Promo block. The block shows only when the two switches are on.', 'vance-health-hub' ),
		'section'     => $switch['section'],
		'type'        => 'checkbox',
		'priority'    => 10,
	) );

	$switch = vance_hub_customizer_switch( 'vance_hub_show_sections' );
	$wp_customize->add_setting( 'vance_hub_show_sections', array(
		'default'           => true,
		'sanitize_callback' => 'vance_hub_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'vance_hub_show_sections', array(
		'label'       => $switch['label'],
		'description' => __( 'One column for each content section (Gastro Living, Health News, Clinical Reviews) with its newest articles. A section with no articles does not show.', 'vance-health-hub' ),
		'section'     => $switch['section'],
		'type'        => 'checkbox',
		'priority'    => 20,
	) );

	$switch = vance_hub_customizer_switch( 'vance_hub_show_more' );
	$wp_customize->add_setting( 'vance_hub_show_more', array(
		'default'           => true,
		'sanitize_callback' => 'vance_hub_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'vance_hub_show_more', array(
		'label'       => $switch['label'],
		'description' => __( 'A grid of a maximum of six more recent articles that are not already on the page.', 'vance-health-hub' ),
		'section'     => $switch['section'],
		'type'        => 'checkbox',
		'priority'    => 30,
	) );

	for ( $i = 1; $i <= 4; $i++ ) {
		// Tile 1 uses priorities 110 to 113, tile 2 uses 120 to 123, and so on.
		$base = 100 + ( 10 * $i );
		$wp_customize->add_setting( "vance_hub_tile_{$i}_badge", array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp_customize->add_control( "vance_hub_tile_{$i}_badge", array(
			/* translators: %d: tile number. */
			'label'       => sprintf( __( 'Tile %d: badge', 'vance-health-hub' ), $i ),
			'description' => 1 === $i ? __( 'The four tiles make the "Inside the Hub this week" row. A tile shows only when it has a title. With no titles, the row does not show.', 'vance-health-hub' ) : '',
			'section'     => 'vance_hub_sec_home',
			'type'        => 'text',
			'priority'    => $base,
		) );
		$wp_customize->add_setting( "vance_hub_tile_{$i}_title", array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp_customize->add_control( "vance_hub_tile_{$i}_title", array(
			/* translators: %d: tile number. */
			'label'    => sprintf( __( 'Tile %d: title', 'vance-health-hub' ), $i ),
			'section'  => 'vance_hub_sec_home',
			'type'     => 'text',
			'priority' => $base + 1,
		) );
		$wp_customize->add_setting( "vance_hub_tile_{$i}_text", array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
		$wp_customize->add_control( "vance_hub_tile_{$i}_text", array(
			/* translators: %d: tile number. */
			'label'    => sprintf( __( 'Tile %d: text', 'vance-health-hub' ), $i ),
			'section'  => 'vance_hub_sec_home',
			'type'     => 'textarea',
			'priority' => $base + 2,
		) );
		$wp_customize->add_setting( "vance_hub_tile_{$i}_link", array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
		$wp_customize->add_control( "vance_hub_tile_{$i}_link", array(
			/* translators: %d: tile number. */
			'label'    => sprintf( __( 'Tile %d: link', 'vance-health-hub' ), $i ),
			'section'  => 'vance_hub_sec_home',
			'type'     => 'url',
			'priority' => $base + 3,
		) );
	}

	/* ---- 5. Member dashboard (the number and the chart are in inc/hub-dashboard-features.php) ---- */

	$switch = vance_hub_customizer_switch( 'vance_hub_show_dashboard' );
	$wp_customize->add_setting( 'vance_hub_show_dashboard', array(
		'default'           => vance_hub_switch_default( 'vance_hub_show_dashboard' ),
		'sanitize_callback' => 'vance_hub_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'vance_hub_show_dashboard', array(
		'label'       => $switch['label'],
		'description' => __( 'On: the first screen of the member dashboard shows a greeting, one next step, the free tools with the member\'s progress, and the member\'s saved items. The other tabs do not change.', 'vance-health-hub' ),
		'section'     => $switch['section'],
		'type'        => 'checkbox',
		'priority'    => 10,
	) );

	$switch = vance_hub_customizer_switch( 'vance_hub_dashboard_tabs' );
	$wp_customize->add_setting( 'vance_hub_dashboard_tabs', array(
		'default'           => vance_hub_switch_default( 'vance_hub_dashboard_tabs' ),
		'sanitize_callback' => 'vance_hub_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'vance_hub_dashboard_tabs', array(
		'label'           => $switch['label'],
		'description'     => __( 'On: each other dashboard tab gets a navy side menu with a member card and an "Ask VANCE-Ai" block, and new headings, cards and buttons. The forms and the data stay the same. This switch needs the dashboard home above.', 'vance-health-hub' ),
		'section'         => $switch['section'],
		'type'            => 'checkbox',
		'priority'        => 20,
		'active_callback' => 'vance_hub_customizer_dashboard_home_on',
	) );
}
add_action( 'customize_register', 'vance_hub_customize_register' );

/* =========================================================================
 * Data for front-page-hub.php
 * ====================================================================== */

/**
 * A page URL by slug, with a literal path as the last resort.
 *
 * @param string $slug     Page slug.
 * @param string $fallback Site-relative path.
 * @return string
 */
function vance_hub_page_url( $slug, $fallback ) {
	if ( function_exists( 'vance_page_hero_spotlight_page_url' ) ) {
		return vance_page_hero_spotlight_page_url( $slug, $fallback );
	}
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( $fallback );
}

/**
 * URL of a theme image, cache-busted on its mtime, or '' when the file is not
 * on disk (the caller then renders no image at all).
 *
 * @param string $rel Path under assets/img/, for example 'about/x.jpg'.
 * @return string
 */
function vance_hub_img( $rel ) {
	$file = get_template_directory() . '/assets/img/' . $rel;
	if ( ! file_exists( $file ) ) {
		return '';
	}
	return add_query_arg( 'v', filemtime( $file ), get_template_directory_uri() . '/assets/img/' . $rel );
}

/**
 * Real member counts, cached for an hour.
 *
 * @return array{total: int, week: int}
 */
function vance_hub_member_counts() {
	$cached = get_transient( 'vance_hub_member_counts' );
	if ( is_array( $cached ) && isset( $cached['total'], $cached['week'] ) ) {
		return $cached;
	}

	// Staff accounts are not members. Both figures use the same filter, so the
	// public total and the weekly figure always describe the same set of people.
	$base = array(
		'number'       => 1,
		'fields'       => 'ID',
		'count_total'  => true,
		'role__not_in' => vance_hub_staff_roles(),
	);

	// One counted query, not count_users(): that function also tallies every
	// role from usermeta, which this bar never shows.
	$total_query = new WP_User_Query( $base );
	$total       = max( 0, (int) $total_query->get_total() );

	$week_query = new WP_User_Query( $base + array(
		'date_query' => array(
			array(
				'column'    => 'user_registered',
				'after'     => '1 week ago',
				'inclusive' => true,
			),
		),
	) );
	$week = min( $total, max( 0, (int) $week_query->get_total() ) );

	// A zero total is either a new site or a failed query. Keep it for a short
	// time only, so a failure does not hide the bar for an hour.
	$counts = array( 'total' => $total, 'week' => $week );
	set_transient( 'vance_hub_member_counts', $counts, $total > 0 ? HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS );
	return $counts;
}

/**
 * Roles that are staff, not members, and stay out of the public counts.
 *
 * @return string[]
 */
function vance_hub_staff_roles() {
	$roles = apply_filters( 'vance_hub_staff_roles', array( 'administrator', 'editor', 'author', 'contributor' ) );
	return is_array( $roles ) ? array_values( array_filter( $roles, 'is_string' ) ) : array();
}

/**
 * Drop the cached counts. Not hooked to user_register on purpose: a burst of
 * sign-ups would then rebuild the counts on every page view. The cache expiry
 * refreshes the figures.
 */
function vance_hub_flush_member_counts() {
	delete_transient( 'vance_hub_member_counts' );
}

/**
 * The Founding Member deadline when it is set and still open, else ''.
 *
 * @return string 'Y-m-d' or ''.
 */
function vance_hub_founding_deadline() {
	$deadline = vance_hub_sanitize_date( (string) vance_get_theme_mod( 'vance_hub_founding_deadline', '' ) );
	return vance_hub_date_open( $deadline, wp_date( 'Y-m-d' ) ) ? $deadline : '';
}

/**
 * A 'Y-m-d' date formatted for display in the site's date format.
 *
 * @param string $date 'Y-m-d'.
 * @return string
 */
function vance_hub_format_date( $date ) {
	$dt = date_create_immutable( $date . ' 12:00:00', wp_timezone() );
	return $dt ? wp_date( get_option( 'date_format' ), $dt->getTimestamp() ) : '';
}

/**
 * The member bar: what to show, from real data only.
 *
 * @return array{mode: string, total: int, week: int, deadline: string}
 */
function vance_hub_member_bar() {
	$counts    = vance_hub_member_counts();
	$threshold = absint( vance_get_theme_mod( 'vance_hub_member_min', 100 ) );
	$deadline  = vance_hub_founding_deadline();
	$mode      = vance_hub_member_bar_mode( $counts['total'], $threshold, '' !== $deadline, is_user_logged_in() );
	return array(
		'mode'     => $mode,
		'total'    => $counts['total'],
		'week'     => $counts['week'],
		'deadline' => $deadline,
	);
}

/**
 * Print the purple member bar: real counts, the Founding Member line, or
 * nothing (see vance_hub_member_bar()).
 *
 * One function, two callers: the VanceHubClub header (every page) and
 * front-page-hub.php (only when the hub header is not active, so the bar is
 * printed once). The markup keeps the vhub- classes; hub-home.css styles them
 * on the home page and hub-header.css styles them everywhere else.
 *
 * @param array $args {
 *     Optional.
 *     @type string $join_href Where "See what members get" points for visitors.
 *                             The home page passes its own #vhub-join anchor;
 *                             on any other page the sign-up page is the default.
 * }
 */
function vance_hub_render_member_bar( $args = array() ) {
	$bar       = vance_hub_member_bar();
	$logged_in = is_user_logged_in();
	$join_href = isset( $args['join_href'] ) && is_string( $args['join_href'] ) && '' !== $args['join_href']
		? $args['join_href']
		: home_url( '/login/?tab=signup' );

	if ( 'counts' === $bar['mode'] ) :
		?>
	<div class="vhub-bar">
		<div class="vhub-wrap vhub-bar__inner">
			<p class="vhub-bar__text">
				<strong><?php
					/* translators: %s: number of registered members. */
					echo esc_html( sprintf( _n( '%s member', '%s members', $bar['total'], 'vance-health-hub' ), number_format_i18n( $bar['total'] ) ) );
				?></strong>
				<?php if ( $bar['week'] > 0 ) : ?>
					<span aria-hidden="true">&middot;</span>
					<?php
					/* translators: %s: number of members who joined in the last seven days. */
					echo esc_html( sprintf( _n( '%s joined this week', '%s joined this week', $bar['week'], 'vance-health-hub' ), number_format_i18n( $bar['week'] ) ) );
					?>
				<?php endif; ?>
			</p>
			<?php if ( $logged_in ) : ?>
				<a class="vhub-bar__link" href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>"><?php esc_html_e( 'Go to My Hub', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></a>
			<?php else : ?>
				<a class="vhub-bar__link" href="<?php echo esc_url( $join_href ); ?>"><?php esc_html_e( 'See what members get', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></a>
			<?php endif; ?>
		</div>
	</div>
		<?php
	elseif ( 'founding' === $bar['mode'] ) :
		?>
	<div class="vhub-bar">
		<div class="vhub-wrap vhub-bar__inner">
			<p class="vhub-bar__text"><?php
				/* translators: %s: Founding Member deadline date. */
				echo esc_html( sprintf( __( 'Join by %s and keep the Founding Member mark.', 'vance-health-hub' ), vance_hub_format_date( $bar['deadline'] ) ) );
			?></p>
			<a class="vhub-bar__link" href="<?php echo esc_url( $join_href ); ?>"><?php esc_html_e( 'See what members get', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></a>
		</div>
	</div>
		<?php
	endif;
}

/**
 * The "Inside the Hub this week" tiles from the Customizer. Empty by default.
 *
 * @return array
 */
function vance_hub_member_tiles() {
	$raw = array();
	for ( $i = 1; $i <= 4; $i++ ) {
		$raw[] = array(
			'badge' => (string) vance_get_theme_mod( "vance_hub_tile_{$i}_badge", '' ),
			'title' => (string) vance_get_theme_mod( "vance_hub_tile_{$i}_title", '' ),
			'text'  => (string) vance_get_theme_mod( "vance_hub_tile_{$i}_text", '' ),
			'link'  => (string) vance_get_theme_mod( "vance_hub_tile_{$i}_link", '' ),
		);
	}
	return vance_hub_filter_tiles( $raw );
}

/**
 * The four tool tiles, with real URLs and real counts.
 *
 * Survey and malnutrition links point at the real pages. The tool modal
 * (inc/tool-modal.php, printed by footer.php) catches clicks on those paths and
 * opens the survey modal or the calculator overlay in place.
 *
 * @return array
 */
function vance_hub_tools() {
	$survey_n = function_exists( 'vance_quiz_steps' ) ? count( vance_quiz_steps() ) : 0;

	// The live published-recipe total, from the recipe hub hero's own stats
	// helper. Its 'grid' cell is the whole collection and is absent at zero.
	$recipes_label = '';
	$recipes_n     = 0;
	if ( function_exists( 'vance_page_hero_spotlight_recipe_stats' ) ) {
		foreach ( vance_page_hero_spotlight_recipe_stats() as $cell ) {
			if ( 'grid' === $cell['key'] ) {
				$recipes_label = (string) $cell['value'];
				$recipes_n     = (int) preg_replace( '/[^0-9]/', '', $recipes_label );
				break;
			}
		}
	}

	$survey_desc = $survey_n > 0
		/* translators: %s: number of survey questions. */
		? sprintf( _n( 'Answer %s short question and get guides, tools and recipes picked for you.', 'Answer %s short questions and get guides, tools and recipes picked for you.', $survey_n, 'vance-health-hub' ), number_format_i18n( $survey_n ) )
		: __( 'Answer a few short questions and get guides, tools and recipes picked for you.', 'vance-health-hub' );

	$recipe_desc = $recipes_n > 0
		/* translators: %s: number of published recipes. */
		? sprintf( _n( 'Choose from %s gut-friendly recipe and leave with a weekly meal plan.', 'Choose from %s gut-friendly recipes and leave with a weekly meal plan.', $recipes_n, 'vance-health-hub' ), $recipes_label )
		: __( 'Choose from gut-friendly recipes and leave with a weekly meal plan.', 'vance-health-hub' );

	return array(
		array(
			'mod'   => 'survey',
			'icon'  => 'clipboard',
			'title' => __( 'Start here...', 'vance-health-hub' ),
			'desc'  => $survey_desc,
			'cta'   => __( 'Start the survey', 'vance-health-hub' ),
			'href'  => vance_hub_page_url( 'gastro-health-survey', '/gastro-health-survey/' ),
			'img'   => vance_hub_img( 'gi-health/ulcerative-colitis.webp' ),
			'alt'   => __( 'Two women walking and talking together on a sunlit city street', 'vance-health-hub' ),
		),
		array(
			'mod'   => 'nutrition',
			'icon'  => 'gauge',
			'title' => __( 'Are you eating right?', 'vance-health-hub' ),
			'desc'  => __( 'A short check that shows your malnutrition risk, so you know what to raise with your care team.', 'vance-health-hub' ),
			'cta'   => __( 'Start the check', 'vance-health-hub' ),
			'href'  => vance_hub_page_url( 'malnutrition-calculator', '/malnutrition-calculator/' ),
			'img'   => vance_hub_img( 'about/healthy-nutrition-food.jpg' ),
			'alt'   => __( 'Bowls of oats, sauerkraut, kimchi, yoghurt and beans beside artichokes, mushrooms, garlic and dragon fruit', 'vance-health-hub' ),
		),
		array(
			'mod'   => 'recipes',
			'icon'  => 'fork',
			'title' => __( 'Not sure what to cook?', 'vance-health-hub' ),
			'desc'  => $recipe_desc,
			'cta'   => __( 'Build my week', 'vance-health-hub' ),
			'href'  => vance_hub_page_url( 'gastro-meal-planner', '/gastro-meal-planner/' ),
			'img'   => vance_hub_img( 'about/wellness-kitchen.jpg' ),
			'alt'   => __( 'A woman in an apron chops vegetables at a kitchen counter', 'vance-health-hub' ),
		),
		array(
			'mod'   => 'discounts',
			'icon'  => 'tag',
			'title' => __( 'Discounts & benefits', 'vance-health-hub' ),
			'desc'  => __( 'See the UK schemes for toilet access, travel and benefits, and what you can apply for.', 'vance-health-hub' ),
			'cta'   => __( 'See the schemes', 'vance-health-hub' ),
			'href'  => vance_hub_page_url( 'ibd-discounts', '/ibd-discounts/' ),
			'img'   => vance_hub_img( 'gi-health/lobby-walk.webp' ),
			'alt'   => __( 'Two people walking side by side away from the camera along a tree-lined canal path', 'vance-health-hub' ),
		),
	);
}

/**
 * Community quotes, from the same sources the classic home page uses
 * (vance_testimonials_shortcode() in functions.php): the `testimonial` post
 * type first, then the three inline Customizer testimonials. The classic
 * page's on/off switch for testimonials is respected.
 *
 * @param int $limit Maximum number of quotes.
 * @return array<int, array{quote: string, name: string, role: string}>
 */
function vance_hub_testimonials( $limit = 2 ) {
	if ( ! vance_get_theme_mod( 'vance_show_testimonials', true ) ) {
		return array();
	}

	$items = array();
	$mode  = vance_get_theme_mod( 'vance_testimonial_select_type', 'latest' );
	$ids   = (string) vance_get_theme_mod( 'vance_testimonial_ids', '' );
	$args  = array(
		'post_type'      => 'testimonial',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'no_found_rows'  => true,
	);
	if ( 'manual' === $mode && '' !== $ids ) {
		$args['post__in'] = array_map( 'intval', explode( ',', $ids ) );
		$args['orderby']  = 'post__in';
	} else {
		$args['orderby'] = 'date';
		$args['order']   = 'DESC';
	}

	$query = new WP_Query( $args );
	foreach ( $query->posts as $post ) {
		$quote = trim( wp_strip_all_tags( (string) $post->post_content ) );
		if ( '' === $quote ) {
			continue;
		}
		$items[] = array(
			'quote' => $quote,
			'name'  => get_the_title( $post ),
			'role'  => (string) get_post_meta( $post->ID, '_testimonial_role', true ),
		);
	}

	// Top up from the inline Customizer quotes when the post type holds fewer
	// than the block needs, so the block stays full as posts are added.
	if ( count( $items ) < $limit ) {
		for ( $i = 1; $i <= 3 && count( $items ) < $limit; $i++ ) {
			$quote = trim( (string) vance_get_theme_mod( "vance_testimonial_inline_{$i}_quote", '' ) );
			if ( '' === $quote ) {
				continue;
			}
			$items[] = array(
				'quote' => $quote,
				'name'  => (string) vance_get_theme_mod( "vance_testimonial_inline_{$i}_name", '' ),
				'role'  => (string) vance_get_theme_mod( "vance_testimonial_inline_{$i}_role", '' ),
			);
		}
	}

	return array_slice( $items, 0, $limit );
}

/**
 * The newest published recipes, each with its first meal category.
 *
 * @param int $limit Number of recipes.
 * @return array<int, array{title: string, url: string, meal: string, img: string, alt: string}>
 */
function vance_hub_recent_recipes( $limit = 2 ) {
	return vance_hub_recipe_rows( array(
		'posts_per_page' => $limit,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
}

/**
 * The most-viewed published recipes. Real counts only: recipe pages are counted
 * by vance_track_post_view() under _vance_recipe_views (never seeded), and a recipe with no recorded
 * view is not listed, so this is empty until the counts exist.
 *
 * @param int $limit Number of recipes.
 * @return array Same rows as vance_hub_recent_recipes().
 */
function vance_hub_popular_recipes( $limit = 2 ) {
	return vance_hub_recipe_rows( array(
		'posts_per_page' => $limit,
		'meta_query'     => array(
			'views' => array(
				'key'     => '_vance_recipe_views',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'NUMERIC',
			),
		),
		'orderby'        => array( 'views' => 'DESC', 'date' => 'DESC' ),
	) );
}

/**
 * Recipe query args to the card rows the community block prints.
 *
 * @param array $args WP_Query args (merged over the recipe defaults).
 * @return array<int, array{title: string, url: string, meal: string, img: string, alt: string}>
 */
function vance_hub_recipe_rows( $args ) {
	$query = new WP_Query( array_merge( array(
		'post_type'     => 'vance_recipe',
		'post_status'   => 'publish',
		'no_found_rows' => true,
	), $args ) );
	$out = array();
	foreach ( $query->posts as $post ) {
		$terms = get_the_terms( $post, 'vance_recipe_cat' );
		$meal  = ( is_array( $terms ) && ! empty( $terms ) ) ? $terms[0]->name : '';
		$img   = get_the_post_thumbnail_url( $post, 'thumbnail' );
		$out[] = array(
			'title' => get_the_title( $post ),
			'url'   => get_permalink( $post ),
			'meal'  => $meal,
			'img'   => $img ? $img : '',
			'alt'   => function_exists( 'vance_thumbnail_alt' ) ? vance_thumbnail_alt( $post->ID ) : '',
		);
	}
	return $out;
}

/**
 * One article as the card fields the hub templates print. Everything is read
 * from the post; nothing is made up. 'alt' is the attachment's own description
 * or '' (see inc/thumbnail-alt.php); 'read' is the theme's word-count reading
 * time, or 0 when that helper is not loaded.
 *
 * @param WP_Post $post     Post.
 * @param string  $img_size Thumbnail size.
 * @return array{id: int, title: string, url: string, cat: string, img: string, alt: string, date: string, iso: string, read: int}
 */
function vance_hub_post_card( $post, $img_size = 'large' ) {
	$cat = '';
	foreach ( (array) get_the_category( $post->ID ) as $term ) {
		if ( 'uncategorized' !== $term->slug ) {
			$cat = $term->name;
			break;
		}
	}
	$img = get_the_post_thumbnail_url( $post, $img_size );
	return array(
		'id'    => (int) $post->ID,
		'title' => get_the_title( $post ),
		'url'   => get_permalink( $post ),
		'cat'   => wp_specialchars_decode( $cat ),
		'img'   => $img ? $img : '',
		'alt'   => function_exists( 'vance_thumbnail_alt' ) ? vance_thumbnail_alt( $post->ID ) : '',
		'date'  => (string) get_the_date( '', $post ),
		'iso'   => (string) get_the_date( 'c', $post ),
		'read'  => function_exists( 'vance_get_read_time' ) ? (int) vance_get_read_time( $post->ID ) : 0,
		// Stored meta only. vance_get_view_count() seeds a random figure and writes it
		// on first read, which a page render must not do.
		'views' => (int) get_post_meta( $post->ID, '_vance_view_count', true ),
	);
}

/**
 * The newest articles for the library block: up to four published posts.
 *
 * @return array<int, array> vance_hub_post_card() rows.
 */
function vance_hub_library_posts() {
	$query = new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 4,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
	vance_hub_prime_thumbnails( $query );
	$out = array();
	foreach ( $query->posts as $post ) {
		$out[] = vance_hub_post_card( $post, 'large' );
	}
	return $out;
}

/**
 * Load the featured images of a query's posts in one go.
 *
 * These helpers read $query->posts directly and never run the loop, so
 * WordPress does not prime the attachment cache for them. Without this, each
 * card costs its own attachment and attachment-meta lookups.
 *
 * @param WP_Query $query A finished post query.
 */
function vance_hub_prime_thumbnails( $query ) {
	if ( function_exists( 'update_post_thumbnail_cache' ) && ! empty( $query->posts ) ) {
		update_post_thumbnail_cache( $query );
	}
}

/**
 * The "Latest by section" columns: for each content section that really
 * exists on the site, its newest posts that are not already on the page.
 *
 * A slug with no category term is skipped, and so is a section whose query
 * returns nothing. The `cat` query argument includes child categories, so a
 * Gastro Living article filed under one of its sub-categories counts.
 *
 * @param int[] $shown IDs already on the page. Updated with every post used.
 * @param int   $per   Posts per section.
 * @return array<int, array{name: string, url: string, slug: string, posts: array}>
 */
function vance_hub_section_columns( &$shown, $per = 3 ) {
	$columns = array();
	$per     = max( 1, (int) $per );
	foreach ( vance_hub_section_slugs() as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( ! is_object( $term ) || empty( $term->term_id ) ) {
			continue;
		}
		$query = new WP_Query( array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'cat'                 => (int) $term->term_id,
			'posts_per_page'      => $per,
			'post__not_in'        => array_values( array_map( 'intval', $shown ) ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		) );
		vance_hub_prime_thumbnails( $query );
		$posts = array();
		foreach ( $query->posts as $post ) {
			if ( in_array( (int) $post->ID, $shown, true ) ) {
				continue;
			}
			$posts[] = vance_hub_post_card( $post, 'medium' );
			$shown[] = (int) $post->ID;
		}
		if ( empty( $posts ) ) {
			continue;
		}
		$link      = get_term_link( $term );
		$columns[] = array(
			'name'  => wp_specialchars_decode( (string) $term->name ),
			'url'   => is_string( $link ) ? $link : '',
			'slug'  => (string) $term->slug,
			'posts' => $posts,
		);
	}
	return $columns;
}

/**
 * The "More to read" grid: recent posts that are not already on the page.
 *
 * @param int[] $shown IDs already on the page. Updated with every post used.
 * @param int   $limit Maximum number of posts.
 * @return array<int, array> vance_hub_post_card() rows.
 */
function vance_hub_more_posts( &$shown, $limit = 6 ) {
	$query = new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, (int) $limit ),
		'post__not_in'        => array_values( array_map( 'intval', $shown ) ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
	vance_hub_prime_thumbnails( $query );
	$out = array();
	foreach ( $query->posts as $post ) {
		if ( in_array( (int) $post->ID, $shown, true ) ) {
			continue;
		}
		$out[]   = vance_hub_post_card( $post, 'medium_large' );
		$shown[] = (int) $post->ID;
	}
	return $out;
}

/**
 * Inline SVG icons for the tool tiles and the fact band. Decorative only.
 *
 * @param string $name Icon key.
 * @return string Trusted SVG markup.
 */
function vance_hub_icon( $name ) {
	$paths = array(
		'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M9 10h6M9 14h6M9 18h3"/>',
		'fork'      => '<path d="M7 3v8a2 2 0 0 0 2 2v8M5 3v5M9 3v5M17 3c-2 2-3 4-3 7h3v11"/>',
		'gauge'     => '<path d="M4 18a8 8 0 1 1 16 0M12 18l4-6M4 18h16"/>',
		'tag'       => '<path d="M3 12l9-9h8v8l-9 9z"/><circle cx="16" cy="8" r="1.5"/>',
		'check'     => '<path d="M5 12l5 5L20 7"/>',
		'person'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'book'      => '<path d="M4 5h7v14H4zM13 5h7v14h-7z"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="vhub-icon" aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths[ $name ] . '</svg>';
}
