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
 * True when the hub home layout is active for this request.
 *
 * @return bool
 */
function vance_hub_layout_active() {
	$query = '';
	// Only users who may preview have their query value read at all. The value
	// must also be a string: ?vhh_layout[]= arrives as an array, and
	// sanitize_key() throws a TypeError on one under PHP 8.
	$can_read = current_user_can( 'edit_theme_options' );
	if ( $can_read && isset( $_GET['vhh_layout'] ) && is_string( $_GET['vhh_layout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview switch, capability-gated.
		$query = sanitize_key( wp_unslash( $_GET['vhh_layout'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	$can_preview = '' !== $query && $can_read;
	return 'hub' === vance_hub_resolve_layout( vance_get_theme_mod( 'vance_home_layout', 'classic' ), $query, $can_preview );
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

/* =========================================================================
 * Customizer
 * ====================================================================== */

/**
 * Register the "Home layout" section and the hub home settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vance_hub_customize_register( $wp_customize ) {
	$section = 'vance_home_layout';

	$wp_customize->add_section( $section, array(
		'title'       => __( 'Home layout (VanceHealthHub / VanceHubClub)', 'vance-health-hub' ),
		'panel'       => 'vance_homepage_panel',
		'priority'    => 1,
		'description' => __( 'VanceHealthHub is the current home page. VanceHubClub puts the free tools and the community first. Administrators can preview either one without saving: add ?vhh_layout=hub (VanceHubClub) or ?vhh_layout=classic (VanceHealthHub) to the home page address.', 'vance-health-hub' ),
	) );

	$wp_customize->add_setting( 'vance_home_layout', array(
		'default'           => 'classic',
		'sanitize_callback' => 'vance_hub_sanitize_layout',
	) );
	$wp_customize->add_control( 'vance_home_layout', array(
		'label'   => __( 'Home layout', 'vance-health-hub' ),
		'section' => $section,
		'type'    => 'radio',
		'choices' => array(
			'classic' => __( 'VanceHealthHub (current home page)', 'vance-health-hub' ),
			'hub'     => __( 'VanceHubClub (tools and community first)', 'vance-health-hub' ),
		),
	) );

	$wp_customize->add_setting( 'vance_hub_member_min', array(
		'default'           => 100,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'vance_hub_member_min', array(
		'label'       => __( 'Hub: show member counts from', 'vance-health-hub' ),
		'description' => __( 'The purple member bar shows the real member total only when it reaches this number. Below it, the bar shows the Founding Member line (if a deadline is set) or nothing.', 'vance-health-hub' ),
		'section'     => $section,
		'type'        => 'number',
		'input_attrs' => array( 'min' => 0, 'step' => 1 ),
	) );

	$wp_customize->add_setting( 'vance_hub_founding_deadline', array(
		'default'           => '',
		'sanitize_callback' => 'vance_hub_sanitize_date',
	) );
	$wp_customize->add_control( 'vance_hub_founding_deadline', array(
		'label'       => __( 'Hub: Founding Member deadline', 'vance-health-hub' ),
		'description' => __( 'Leave empty to hide the Founding Member line. The line shows to logged-out visitors until the end of this date.', 'vance-health-hub' ),
		'section'     => $section,
		'type'        => 'date',
	) );

	for ( $i = 1; $i <= 4; $i++ ) {
		$wp_customize->add_setting( "vance_hub_tile_{$i}_badge", array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp_customize->add_control( "vance_hub_tile_{$i}_badge", array(
			/* translators: %d: tile number. */
			'label'       => sprintf( __( 'Hub: "Inside the Hub" tile %d badge', 'vance-health-hub' ), $i ),
			'description' => 1 === $i ? __( 'The "Inside the Hub this week" section shows only tiles that have a title. With no titles set, the whole section is hidden.', 'vance-health-hub' ) : '',
			'section'     => $section,
			'type'        => 'text',
		) );
		$wp_customize->add_setting( "vance_hub_tile_{$i}_title", array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp_customize->add_control( "vance_hub_tile_{$i}_title", array(
			/* translators: %d: tile number. */
			'label'   => sprintf( __( 'Hub: tile %d title', 'vance-health-hub' ), $i ),
			'section' => $section,
			'type'    => 'text',
		) );
		$wp_customize->add_setting( "vance_hub_tile_{$i}_text", array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
		$wp_customize->add_control( "vance_hub_tile_{$i}_text", array(
			/* translators: %d: tile number. */
			'label'   => sprintf( __( 'Hub: tile %d text', 'vance-health-hub' ), $i ),
			'section' => $section,
			'type'    => 'textarea',
		) );
		$wp_customize->add_setting( "vance_hub_tile_{$i}_link", array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
		$wp_customize->add_control( "vance_hub_tile_{$i}_link", array(
			/* translators: %d: tile number. */
			'label'   => sprintf( __( 'Hub: tile %d link', 'vance-health-hub' ), $i ),
			'section' => $section,
			'type'    => 'url',
		) );
	}
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
		? sprintf( _n( 'Gastro Health Survey: %s short question. The Hub then shows what fits you.', 'Gastro Health Survey: %s short questions. The Hub then shows what fits you.', $survey_n, 'vance-health-hub' ), number_format_i18n( $survey_n ) )
		: __( 'Gastro Health Survey. The Hub then shows what fits you.', 'vance-health-hub' );

	$recipe_desc = $recipes_n > 0
		/* translators: %s: number of published recipes. */
		? sprintf( _n( '%s gut-friendly recipe. Build a weekly meal plan.', '%s gut-friendly recipes. Build a weekly meal plan.', $recipes_n, 'vance-health-hub' ), $recipes_label )
		: __( 'Gut-friendly recipes. Build a weekly meal plan.', 'vance-health-hub' );

	return array(
		array(
			'mod'   => 'survey',
			'icon'  => 'clipboard',
			'title' => __( 'Find what fits me', 'vance-health-hub' ),
			'desc'  => $survey_desc,
			'cta'   => __( 'Start the survey', 'vance-health-hub' ),
			'href'  => vance_hub_page_url( 'gastro-health-survey', '/gastro-health-survey/' ),
			'img'   => vance_hub_img( 'gi-health/ulcerative-colitis.webp' ),
			'alt'   => __( 'Two women walking and talking together on a sunlit city street', 'vance-health-hub' ),
		),
		array(
			'mod'   => 'recipes',
			'icon'  => 'fork',
			'title' => __( 'Plan what to eat', 'vance-health-hub' ),
			'desc'  => $recipe_desc,
			'cta'   => __( 'Build my week', 'vance-health-hub' ),
			'href'  => vance_hub_page_url( 'gastro-meal-planner', '/gastro-meal-planner/' ),
			'img'   => vance_hub_img( 'about/wellness-kitchen.jpg' ),
			'alt'   => __( 'A woman in an apron chops vegetables at a kitchen counter', 'vance-health-hub' ),
		),
		array(
			'mod'   => 'nutrition',
			'icon'  => 'gauge',
			'title' => __( 'Check my nutrition risk', 'vance-health-hub' ),
			'desc'  => (string) vance_get_theme_mod( 'vance_tool_malnutrition_name', 'IBD Malnutrition Calculator' ) . '. ' . __( 'A short check with a clear result.', 'vance-health-hub' ),
			'cta'   => __( 'Start the check', 'vance-health-hub' ),
			'href'  => vance_hub_page_url( 'malnutrition-calculator', '/malnutrition-calculator/' ),
			'img'   => vance_hub_img( 'about/healthy-nutrition-food.jpg' ),
			'alt'   => __( 'Bowls of oats, sauerkraut, kimchi, yoghurt and beans beside artichokes, mushrooms, garlic and dragon fruit', 'vance-health-hub' ),
		),
		array(
			'mod'   => 'discounts',
			'icon'  => 'tag',
			'title' => __( 'Find discounts and access', 'vance-health-hub' ),
			'desc'  => __( 'UK schemes for toilet access, travel and benefits.', 'vance-health-hub' ),
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

	if ( empty( $items ) ) {
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
 * @return array<int, array{title: string, url: string, meal: string}>
 */
function vance_hub_recent_recipes( $limit = 2 ) {
	$query = new WP_Query( array(
		'post_type'      => 'vance_recipe',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	) );
	$out = array();
	foreach ( $query->posts as $post ) {
		$terms = get_the_terms( $post, 'vance_recipe_cat' );
		$meal  = ( is_array( $terms ) && ! empty( $terms ) ) ? $terms[0]->name : '';
		$out[] = array(
			'title' => get_the_title( $post ),
			'url'   => get_permalink( $post ),
			'meal'  => $meal,
		);
	}
	return $out;
}

/**
 * The newest articles for the library block: up to four published posts.
 *
 * @return array<int, array{title: string, url: string, cat: string, img: string, alt: string}>
 */
function vance_hub_library_posts() {
	$query = new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 4,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
	$out = array();
	foreach ( $query->posts as $post ) {
		$cat = '';
		foreach ( (array) get_the_category( $post->ID ) as $term ) {
			if ( 'uncategorized' !== $term->slug ) {
				$cat = $term->name;
				break;
			}
		}
		$img = get_the_post_thumbnail_url( $post, 'large' );
		$out[] = array(
			'title' => get_the_title( $post ),
			'url'   => get_permalink( $post ),
			'cat'   => wp_specialchars_decode( $cat ),
			'img'   => $img ? $img : '',
			'alt'   => function_exists( 'vance_thumbnail_alt' ) ? vance_thumbnail_alt( $post->ID ) : '',
		);
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
