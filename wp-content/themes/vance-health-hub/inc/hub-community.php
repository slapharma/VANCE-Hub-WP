<?php
/**
 * VanceHubClub Community hub, version 1: questions, replies, a weekly poll.
 *
 * Off by default. With the Customizer checkbox "Community hub" (VanceHubClub
 * section) unticked, nothing public changes: /community/ answers 404, the club
 * header shows no Community link, and every member write is refused. An
 * administrator can preview it without saving: add ?vhh_layout=hub to the
 * /community/ address (?vhh_layout=classic forces it off).
 *
 * DATA MODEL (all keys start with _vance_community_; no _sla_* key is used)
 *
 *   vance_question   CPT. Title, plain-text content, one vance_space term.
 *                    Not public, not in REST, not in search, no single URL:
 *                    it is shown only inside the /community/ page, so it can
 *                    never reach the AIOSEO sitemap (that lists public types).
 *                    post_status: pending (waiting or reported), publish
 *                    (approved), draft (rejected).
 *   vance_reply      comment_type on a vance_question. One level, no parent.
 *                    comment_approved 0 (waiting, reported, rejected) or 1.
 *   vance_space      Taxonomy, seven terms seeded once.
 *   vance_poll       CPT. Title is the question. Answers in post meta.
 *
 *   Post / comment meta:
 *     _vance_community_state          waiting | approved | reported | rejected
 *     _vance_community_reject_reason  key of vance_community_reject_reasons()
 *     _vance_community_metoo          one row per member (value: user id)
 *     _vance_community_helpful        one row per member (comment meta)
 *     _vance_community_reporter       one row per reporter (value: user id)
 *     _vance_community_report         one row per report ("uid|reason|time")
 *     _vance_community_poll_answers   array of 3 to 5 strings (vance_poll)
 *     _vance_community_poll_active    '1' on the one active poll
 *     _vance_community_poll_closed    unix time the poll stopped being active
 *   User meta:
 *     _vance_community_vote_{poll id} answer index the member chose
 *     _vance_community_name_mode      first | nickname
 *     _vance_community_nickname       nickname, plain text
 *
 * Every count is computed from stored rows. Nothing on the page is typed in.
 *
 * Markup: inc/hub-community-view.php. Admin screens: inc/hub-community-admin.php.
 * Template: page-community.php. Assets: assets/css/hub-community.css,
 * assets/js/hub-community.js, assets/css/hub-community-admin.css.
 *
 * Needs inc/hub-layout.php (vance_hub_sanitize_checkbox, vance_hub_resolve_layout).
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/hub-preview.php';

/* =========================================================================
 * Fixed lists
 * ====================================================================== */

/**
 * The moderation capability.
 *
 * @return string
 */
function vance_community_cap() {
	return 'vance_moderate_community';
}

/**
 * The seven spaces, slug => label, in display order.
 *
 * @return array<string,string>
 */
function vance_community_spaces() {
	return array(
		'ibd'                => __( 'IBD', 'vance-health-hub' ),
		'ulcerative-colitis' => __( 'Ulcerative Colitis', 'vance-health-hub' ),
		'crohns-disease'     => __( "Crohn's Disease", 'vance-health-hub' ),
		'ibs'                => __( 'IBS', 'vance-health-hub' ),
		'food-and-recipes'   => __( 'Food and recipes', 'vance-health-hub' ),
		'work-and-travel'    => __( 'Work and travel', 'vance-health-hub' ),
		'carers'             => __( 'Carers', 'vance-health-hub' ),
	);
}

/**
 * Colour of a space badge and card edge.
 *
 * @param string $slug Space slug.
 * @return string teal | purple | navy
 */
function vance_community_space_tone( $slug ) {
	$tones = array(
		'food-and-recipes' => 'purple',
		'carers'           => 'purple',
		'work-and-travel'  => 'navy',
	);
	return isset( $tones[ $slug ] ) ? $tones[ $slug ] : 'teal';
}

/**
 * Reasons a moderator can give for a rejection.
 *
 * @return array<string,string>
 */
function vance_community_reject_reasons() {
	return array(
		'medical'  => __( 'Gives medical advice', 'vance-health-hub' ),
		'claim'    => __( 'Product or health claim', 'vance-health-hub' ),
		'unkind'   => __( 'Unkind or abusive', 'vance-health-hub' ),
		'personal' => __( 'Shares personal details', 'vance-health-hub' ),
		'offtopic' => __( 'Off topic', 'vance-health-hub' ),
	);
}

/**
 * Reasons a member can give for a report.
 *
 * @return array<string,string>
 */
function vance_community_report_reasons() {
	return array(
		'medical'  => __( 'Medical advice', 'vance-health-hub' ),
		'unkind'   => __( 'Unkind or abusive', 'vance-health-hub' ),
		'spam'     => __( 'Spam or selling', 'vance-health-hub' ),
		'personal' => __( 'Personal details', 'vance-health-hub' ),
	);
}

/**
 * Limits. Filterable as one array so a test or a site owner can change them.
 *
 * @return array<string,int>
 */
function vance_community_limits() {
	$limits = apply_filters( 'vance_community_limits', array(
		'questions_per_day' => 5,
		'replies_per_day'   => 20,
		'reports_per_day'   => 20,
		'title_min'         => 5,
		'title_max'         => 150,
		'text_max'          => 1000,
		'reply_min'         => 2,
		'reply_max'         => 1000,
		'nickname_max'      => 30,
		'per_page'          => 10,
	) );
	return is_array( $limits ) ? array_map( 'intval', $limits ) : array();
}

/**
 * One limit by name.
 *
 * @param string $name Key of vance_community_limits().
 * @return int
 */
function vance_community_limit( $name ) {
	$limits = vance_community_limits();
	return isset( $limits[ $name ] ) ? (int) $limits[ $name ] : 0;
}

/**
 * Words that name a Vance product. A post that contains one gets the
 * "names a product" flag in the queue. Case does not matter; whole words only.
 *
 * @return string[]
 */
function vance_community_product_terms() {
	$terms = apply_filters( 'vance_community_product_terms', array( 'Vance', 'EPAVANCE', 'B-VANCE', 'BVANCE', 'Vance Medical' ) );
	return is_array( $terms ) ? array_values( array_filter( $terms, function ( $t ) {
		return is_string( $t ) && '' !== trim( $t );
	} ) ) : array();
}

/* =========================================================================
 * Pure helpers (no WordPress calls apart from translation)
 * ====================================================================== */

/**
 * Decide whether the community hub is on. Same rule as
 * vance_hub_tool_result_resolve(): the saved checkbox stands in for the saved
 * layout, and an administrator's ?vhh_layout=hub|classic wins over it.
 *
 * @param mixed $mod_value   Saved vance_hub_community mod.
 * @param mixed $query_value Value of ?vhh_layout= ('' when absent or unread).
 * @param bool  $can_preview True when the viewer may preview layouts.
 * @return bool
 */
function vance_hub_community_resolve( $mod_value, $query_value, $can_preview ) {
	$saved = vance_hub_sanitize_checkbox( $mod_value ) ? 'hub' : 'classic';
	return 'hub' === vance_hub_resolve_layout( $saved, $query_value, $can_preview );
}

/**
 * True when the text names a product from the list.
 *
 * @param string   $text  Text to test.
 * @param string[] $terms Product words.
 * @return bool
 */
function vance_community_text_names_product( $text, $terms ) {
	$text = (string) $text;
	if ( '' === $text ) {
		return false;
	}
	foreach ( (array) $terms as $term ) {
		if ( ! is_string( $term ) || '' === trim( $term ) ) {
			continue;
		}
		$pattern = '/(?<![\p{L}\p{N}])' . preg_quote( trim( $term ), '/' ) . '(?![\p{L}\p{N}])/iu';
		if ( preg_match( $pattern, $text ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Plain text from a raw input: no tags, no control characters, trimmed, at most
 * $max characters. Line breaks are kept (at most one blank line in a row).
 *
 * @param mixed $raw Raw value.
 * @param int   $max Maximum length in characters.
 * @param bool  $multiline Keep line breaks.
 * @return string
 */
function vance_community_plain_text( $raw, $max, $multiline = true ) {
	if ( ! is_string( $raw ) ) {
		return '';
	}
	$text = wp_strip_all_tags( $raw );
	$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
	// A tab is a word break, not a control character to delete: it becomes a space.
	$text = str_replace( "\t", ' ', $text );
	// Remove control characters other than the line break.
	$text = preg_replace( '/[\x00-\x09\x0B-\x1F\x7F]/u', '', $text );
	if ( ! is_string( $text ) ) {
		return '';
	}
	if ( $multiline ) {
		$text = preg_replace( "/\n{3,}/", "\n\n", $text );
		$text = implode( "\n", array_map( 'trim', explode( "\n", (string) $text ) ) );
	} else {
		$text = preg_replace( '/\s+/u', ' ', $text );
	}
	$text = trim( (string) $text );
	if ( function_exists( 'mb_substr' ) ) {
		$text = mb_substr( $text, 0, max( 0, (int) $max ) );
	} else {
		$text = substr( $text, 0, max( 0, (int) $max ) );
	}
	return trim( $text );
}

/**
 * Length of a string in characters.
 *
 * @param string $text Text.
 * @return int
 */
function vance_community_strlen( $text ) {
	return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $text ) : strlen( (string) $text );
}

/**
 * The name a member posts under: the nickname when chosen and set, else the
 * first word of the first name, else "Member". Never the login, the email or
 * the surname. A value with an @ in it is refused, so an email address typed
 * into a name field is never shown.
 *
 * @param string $mode       'first' or 'nickname'.
 * @param string $first_name first_name user meta.
 * @param string $nickname   _vance_community_nickname user meta.
 * @return string
 */
function vance_community_pick_name( $mode, $first_name, $nickname ) {
	$nickname = trim( (string) $nickname );
	if ( 'nickname' === $mode && '' !== $nickname && false === strpos( $nickname, '@' ) ) {
		return $nickname;
	}
	$parts = preg_split( '/\s+/u', trim( (string) $first_name ) );
	$first = is_array( $parts ) && isset( $parts[0] ) ? trim( $parts[0] ) : '';
	if ( '' !== $first && false === strpos( $first, '@' ) ) {
		return $first;
	}
	return __( 'Member', 'vance-health-hub' );
}

/**
 * Share of each answer in whole percent, and the total.
 *
 * @param array $counts  answer index => votes.
 * @param int   $answers Number of answers.
 * @return array{total:int, rows:array<int,array{votes:int,share:int}>}
 */
function vance_community_poll_shares( $counts, $answers ) {
	$rows  = array();
	$total = 0;
	for ( $i = 0; $i < (int) $answers; $i++ ) {
		$votes        = isset( $counts[ $i ] ) ? max( 0, (int) $counts[ $i ] ) : 0;
		$rows[ $i ]   = array( 'votes' => $votes, 'share' => 0 );
		$total       += $votes;
	}
	if ( $total > 0 ) {
		foreach ( $rows as $i => $row ) {
			$rows[ $i ]['share'] = (int) round( 100 * $row['votes'] / $total );
		}
	}
	return array( 'total' => $total, 'rows' => $rows );
}

/* =========================================================================
 * The switch
 * ====================================================================== */

/**
 * True when the community hub is on for this request. Same capability-first read
 * as vance_hub_tool_result_active(): a visitor's ?vhh_layout= is never read.
 *
 * @return bool
 */
function vance_hub_community_active() {
	// The shared resolver (inc/hub-preview.php): an administrator's ?vhh_layout=
	// first, then preview mode (admin toggle or share link), then nothing.
	// Preview only shows the page; member writes still need the saved switch.
	$query = vance_hub_preview_query();
	return vance_hub_community_resolve( vance_get_theme_mod( 'vance_hub_community', false ), $query, '' !== $query );
}

/**
 * True when member writes are accepted: the saved switch is on, or the user
 * may preview (so an administrator can test the preview end to end). The
 * preview query is not on an admin-ajax request, so it cannot decide this.
 *
 * @return bool
 */
function vance_community_writes_open() {
	return vance_hub_sanitize_checkbox( vance_get_theme_mod( 'vance_hub_community', false ) ) || current_user_can( 'edit_theme_options' );
}

/**
 * True on the community page (by slug or by template), switch or no switch.
 *
 * @return bool
 */
function vance_community_is_page() {
	return is_page( 'community' ) || is_page_template( 'page-community.php' );
}

/**
 * True on the community page with the hub on.
 *
 * @return bool
 */
function vance_community_on_page() {
	return vance_community_is_page() && vance_hub_community_active();
}

/**
 * Address of the community page.
 *
 * @return string
 */
function vance_community_page_url() {
	$page = get_page_by_path( 'community' );
	if ( $page && 'publish' === $page->post_status ) {
		$url = get_permalink( $page );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}
	return home_url( '/community/' );
}

/**
 * Sign-up address that comes back to the community page.
 *
 * @return string
 */
function vance_community_join_url() {
	return add_query_arg( array(
		'tab'         => 'signup',
		'redirect_to' => rawurlencode( vance_community_page_url() ),
	), home_url( '/login/' ) );
}

/**
 * Sign-in address that comes back to the community page.
 *
 * @return string
 */
function vance_community_signin_url() {
	return add_query_arg( 'redirect_to', rawurlencode( vance_community_page_url() ), home_url( '/login/' ) );
}

/* =========================================================================
 * Types, taxonomy, capability, seed
 * ====================================================================== */

/**
 * Register the question and poll types and the space taxonomy.
 *
 * Every capability of both types maps to the moderation capability, so only
 * moderators open them in the admin, and authors or contributors cannot.
 */
function vance_community_register_types() {
	$cap  = vance_community_cap();
	$caps = array(
		'edit_post'              => $cap,
		'read_post'              => $cap,
		'delete_post'            => $cap,
		'edit_posts'             => $cap,
		'edit_others_posts'      => $cap,
		'publish_posts'          => $cap,
		'read_private_posts'     => $cap,
		'delete_posts'           => $cap,
		'delete_private_posts'   => $cap,
		'delete_published_posts' => $cap,
		'delete_others_posts'    => $cap,
		'edit_private_posts'     => $cap,
		'edit_published_posts'   => $cap,
		'create_posts'           => $cap,
	);

	register_post_type( 'vance_question', array(
		'labels'              => array(
			'name'          => __( 'Community questions', 'vance-health-hub' ),
			'singular_name' => __( 'Community question', 'vance-health-hub' ),
			'edit_item'     => __( 'Edit community question', 'vance-health-hub' ),
		),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => false,
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => false,
		'show_in_rest'        => false,
		'has_archive'         => false,
		'rewrite'             => false,
		'query_var'           => false,
		'supports'            => array( 'title', 'editor', 'author' ),
		'capabilities'        => $caps,
		'map_meta_cap'        => false,
	) );

	register_post_type( 'vance_poll', array(
		'labels'              => array(
			'name'          => __( 'Weekly polls', 'vance-health-hub' ),
			'singular_name' => __( 'Weekly poll', 'vance-health-hub' ),
			'add_new_item'  => __( 'Add a weekly poll', 'vance-health-hub' ),
			'edit_item'     => __( 'Edit weekly poll', 'vance-health-hub' ),
			'all_items'     => __( 'Weekly polls', 'vance-health-hub' ),
		),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => false,
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => false,
		'show_in_rest'        => false,
		'has_archive'         => false,
		'rewrite'             => false,
		'query_var'           => false,
		'supports'            => array( 'title' ),
		'capabilities'        => $caps,
		'map_meta_cap'        => false,
	) );

	register_taxonomy( 'vance_space', array( 'vance_question' ), array(
		'labels'            => array(
			'name'          => __( 'Community spaces', 'vance-health-hub' ),
			'singular_name' => __( 'Community space', 'vance-health-hub' ),
		),
		'public'            => false,
		'publicly_queryable' => false,
		'show_ui'           => true,
		'show_in_menu'      => false,
		'show_in_nav_menus' => false,
		'show_in_rest'      => false,
		'show_tagcloud'     => false,
		'show_admin_column' => true,
		'hierarchical'      => true,
		'rewrite'           => false,
		'query_var'         => false,
		'capabilities'      => array(
			'manage_terms' => $cap,
			'edit_terms'   => $cap,
			'delete_terms' => $cap,
			'assign_terms' => $cap,
		),
	) );
}
add_action( 'init', 'vance_community_register_types' );

/**
 * Give administrators and editors the moderation capability. Idempotent: a
 * role that already has it is not written again.
 */
function vance_community_grant_caps() {
	foreach ( array( 'administrator', 'editor' ) as $name ) {
		$role = get_role( $name );
		if ( $role && ! $role->has_cap( vance_community_cap() ) ) {
			$role->add_cap( vance_community_cap() );
		}
	}
}
add_action( 'init', 'vance_community_grant_caps', 11 );
add_action( 'after_switch_theme', 'vance_community_grant_caps' );

/**
 * Create the seven spaces once. A term that already exists is left as it is,
 * so a moderator's later rename survives. The flag is set only when every term
 * exists, so a failed insert is tried again on the next request.
 */
function vance_community_seed_spaces() {
	if ( get_option( 'vance_community_spaces_seeded' ) ) {
		return;
	}
	if ( ! taxonomy_exists( 'vance_space' ) ) {
		return;
	}
	$ok = true;
	foreach ( vance_community_spaces() as $slug => $label ) {
		if ( term_exists( $slug, 'vance_space' ) ) {
			continue;
		}
		$res = wp_insert_term( $label, 'vance_space', array( 'slug' => $slug ) );
		if ( is_wp_error( $res ) ) {
			$ok = false;
		}
	}
	if ( $ok ) {
		update_option( 'vance_community_spaces_seeded', 1, false );
	}
}
add_action( 'init', 'vance_community_seed_spaces', 20 );

/* =========================================================================
 * Customizer and the page
 * ====================================================================== */

/**
 * The Customizer switch, in the VanceHubClub section.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vance_hub_community_customize_register( $wp_customize ) {
	$wp_customize->add_setting( 'vance_hub_community', array(
		'default'           => false,
		'sanitize_callback' => 'vance_hub_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'vance_hub_community', array(
		'label'       => __( 'Community hub', 'vance-health-hub' ),
		'description' => __( 'Off: /community/ shows "page not found" and the header has no Community link. On: members ask questions, reply, vote in the weekly poll and report posts. A moderator approves every post first (VanceHealthHub > Community queue). When you switch it on, the theme makes the /community/ page if no page has that address. Administrators can preview it without saving: add ?vhh_layout=hub to the /community/ address.', 'vance-health-hub' ),
		'section'     => 'vance_home_layout',
		'type'        => 'checkbox',
		'priority'    => 10,
	) );
}
add_action( 'customize_register', 'vance_hub_community_customize_register', 11 );

/**
 * Make the /community/ page when the switch is saved on and no page has that
 * address (in any status, the bin included). An existing page is never
 * changed: the template is matched by slug as well, so it renders anyway.
 */
function vance_community_maybe_create_page() {
	if ( ! vance_hub_sanitize_checkbox( vance_get_theme_mod( 'vance_hub_community', false ) ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_theme_options' ) || ! current_user_can( 'publish_pages' ) ) {
		return;
	}
	$existing = get_posts( array(
		'post_type'      => 'page',
		'name'           => 'community',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	$trashed  = get_posts( array(
		'post_type'      => 'page',
		'name'           => 'community__trashed',
		'post_status'    => 'trash',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	if ( ! empty( $existing ) || ! empty( $trashed ) ) {
		return;
	}
	$id = wp_insert_post( array(
		'post_type'    => 'page',
		'post_title'   => __( 'Community', 'vance-health-hub' ),
		'post_name'    => 'community',
		'post_status'  => 'publish',
		'post_content' => '',
		'meta_input'   => array( '_wp_page_template' => 'page-community.php' ),
	), true );
	if ( is_wp_error( $id ) || ! $id ) {
		return;
	}
	vance_community_aioseo_noindex( (int) $id );
}
add_action( 'customize_save_after', 'vance_community_maybe_create_page' );

/**
 * Keep a page out of the AIOSEO sitemap the way CLAUDE.md says is safe: write
 * the stored robots columns through the plugin's own Post model. Never through
 * the aioseo_sitemap_exclude_* filters. Does nothing when AIOSEO is absent or
 * its model has another shape.
 *
 * @param int $post_id Page ID.
 */
function vance_community_aioseo_noindex( $post_id ) {
	$class = '\AIOSEO\Plugin\Common\Models\Post';
	if ( ! class_exists( $class ) || ! method_exists( $class, 'getPost' ) ) {
		return;
	}
	try {
		$model = call_user_func( array( $class, 'getPost' ), $post_id );
		if ( ! is_object( $model ) || ! method_exists( $model, 'save' ) ) {
			return;
		}
		$model->post_id        = $post_id;
		$model->robots_default = 0;
		$model->robots_noindex = 1;
		$model->save();
	} catch ( \Throwable $e ) {
		// The meta tag filter below still applies; the sitemap is a tidy-up.
		return;
	}
}

/**
 * Use page-community.php for the community page even when the page has no
 * template assigned.
 *
 * @param string $template Template WordPress resolved.
 * @return string
 */
function vance_community_template_include( $template ) {
	if ( ! vance_community_on_page() ) {
		return $template;
	}
	$own = locate_template( 'page-community.php' );
	return $own ? $own : $template;
}
add_filter( 'template_include', 'vance_community_template_include', 20 );

/**
 * With the hub off, the community page is "not found" for everyone who is not
 * previewing. With it on, a member's page is never cached: it carries a nonce
 * and replies a visitor must not get.
 */
function vance_community_template_redirect() {
	if ( ! vance_community_is_page() ) {
		return;
	}
	if ( ! vance_hub_community_active() ) {
		global $wp_query;
		if ( $wp_query instanceof WP_Query ) {
			$wp_query->set_404();
		}
		status_header( 404 );
		nocache_headers();
		return;
	}
	if ( is_user_logged_in() ) {
		nocache_headers();
	}
}
add_action( 'template_redirect', 'vance_community_template_redirect', 5 );

/**
 * Noindex the community page in v1 (core robots tag).
 *
 * @param array $robots Robots directives.
 * @return array
 */
function vance_community_wp_robots( $robots ) {
	if ( vance_community_is_page() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'] );
	}
	return $robots;
}
add_filter( 'wp_robots', 'vance_community_wp_robots', 20 );

/**
 * Noindex the community page in AIOSEO's own tag (its output filter is the
 * half that works on this install; see inc/seo-archive-robots.php).
 *
 * @param array $attributes AIOSEO robots attributes.
 * @return array
 */
function vance_community_aioseo_robots( $attributes ) {
	if ( is_array( $attributes ) && vance_community_is_page() ) {
		$attributes['noindex'] = 'noindex';
	}
	return $attributes;
}
add_filter( 'aioseo_robots_meta', 'vance_community_aioseo_robots' );

/**
 * Stylesheet and script, only on the community page with the hub on.
 */
function vance_community_enqueue() {
	if ( ! vance_community_on_page() ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style(
		'vance-hub-community',
		$uri . '/assets/css/hub-community.css',
		array( 'vance-main-style' ),
		@filemtime( $dir . '/assets/css/hub-community.css' ) ?: '1.0.0'
	);
	wp_enqueue_script(
		'vance-hub-community',
		$uri . '/assets/js/hub-community.js',
		array(),
		@filemtime( $dir . '/assets/js/hub-community.js' ) ?: '1.0.0',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'vance_community_enqueue', 20 );

/**
 * Body class on the community page.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function vance_community_body_class( $classes ) {
	if ( vance_community_on_page() ) {
		$classes[] = 'vcom-page';
	}
	return $classes;
}
add_filter( 'body_class', 'vance_community_body_class' );

/* =========================================================================
 * Keep replies apart from article comments
 * ====================================================================== */

/**
 * Leave replies out of the REST comments list for anyone who is not a
 * moderator. (The list already drops comments on a type that is not in REST;
 * this also keeps them out of the total.)
 *
 * @param array $args WP_Comment_Query args.
 * @return array
 */
function vance_community_rest_comment_query( $args ) {
	if ( ! current_user_can( vance_community_cap() ) ) {
		$not                  = isset( $args['type__not_in'] ) ? (array) $args['type__not_in'] : array();
		$not[]                = 'vance_reply';
		$args['type__not_in'] = array_values( array_unique( $not ) );
	}
	return $args;
}
add_filter( 'rest_comment_query', 'vance_community_rest_comment_query' );

/**
 * Leave replies out of the comment feeds.
 *
 * @param string $where SQL WHERE.
 * @return string
 */
function vance_community_comment_feed_where( $where ) {
	global $wpdb;
	return $where . $wpdb->prepare( " AND {$wpdb->comments}.comment_type != %s", 'vance_reply' );
}
add_filter( 'comment_feed_where', 'vance_community_comment_feed_where' );

/**
 * Leave replies out of the Recent Comments widget.
 *
 * @param array $args Widget comment args.
 * @return array
 */
function vance_community_widget_comments_args( $args ) {
	$not                  = isset( $args['type__not_in'] ) ? (array) $args['type__not_in'] : array();
	$not[]                = 'vance_reply';
	$args['type__not_in'] = array_values( array_unique( $not ) );
	return $args;
}
add_filter( 'widget_comments_args', 'vance_community_widget_comments_args' );

/* =========================================================================
 * Data access
 * ====================================================================== */

/**
 * The display name of a member.
 *
 * @param int $user_id User ID.
 * @return string
 */
function vance_community_display_name( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return __( 'Member', 'vance-health-hub' );
	}
	return vance_community_pick_name(
		(string) get_user_meta( $user_id, '_vance_community_name_mode', true ),
		(string) get_user_meta( $user_id, 'first_name', true ),
		(string) get_user_meta( $user_id, '_vance_community_nickname', true )
	);
}

/**
 * The space term of a question, or null.
 *
 * @param int $post_id Question ID.
 * @return WP_Term|null
 */
function vance_community_question_space( $post_id ) {
	$terms = get_the_terms( (int) $post_id, 'vance_space' );
	return ( is_array( $terms ) && ! empty( $terms ) && $terms[0] instanceof WP_Term ) ? $terms[0] : null;
}

/**
 * The space terms that exist, in the fixed order.
 *
 * @return WP_Term[] slug => term
 */
function vance_community_space_terms() {
	$out   = array();
	$terms = get_terms( array( 'taxonomy' => 'vance_space', 'hide_empty' => false ) );
	if ( ! is_array( $terms ) ) {
		return $out;
	}
	$by_slug = array();
	foreach ( $terms as $term ) {
		if ( $term instanceof WP_Term ) {
			$by_slug[ $term->slug ] = $term;
		}
	}
	foreach ( array_keys( vance_community_spaces() ) as $slug ) {
		if ( isset( $by_slug[ $slug ] ) ) {
			$out[ $slug ] = $by_slug[ $slug ];
			unset( $by_slug[ $slug ] );
		}
	}
	// A space a moderator added later goes after the seven.
	return $out + $by_slug;
}

/**
 * The state of a question or reply.
 *
 * @param string $kind 'question' or 'reply'.
 * @param int    $id   Post or comment ID.
 * @return string
 */
function vance_community_state( $kind, $id ) {
	$state = 'reply' === $kind
		? get_comment_meta( (int) $id, '_vance_community_state', true )
		: get_post_meta( (int) $id, '_vance_community_state', true );
	return in_array( $state, array( 'waiting', 'approved', 'reported', 'rejected' ), true ) ? $state : 'waiting';
}

/**
 * Count of stored rows for a meta key on one object.
 *
 * @param string $type 'post' or 'comment'.
 * @param int    $id   Object ID.
 * @param string $key  Meta key.
 * @return int
 */
function vance_community_meta_rows( $type, $id, $key ) {
	$rows = get_metadata( $type, (int) $id, $key, false );
	return is_array( $rows ) ? count( $rows ) : 0;
}

/**
 * True when this user has a row with their ID under the key.
 *
 * @param string $type    'post' or 'comment'.
 * @param int    $id      Object ID.
 * @param string $key     Meta key.
 * @param int    $user_id User ID.
 * @return bool
 */
function vance_community_has_row( $type, $id, $key, $user_id ) {
	if ( (int) $user_id <= 0 ) {
		return false;
	}
	$rows = get_metadata( $type, (int) $id, $key, false );
	return is_array( $rows ) && in_array( (string) (int) $user_id, array_map( 'strval', $rows ), true );
}

/**
 * How many questions or replies a member wrote in the last 24 hours, in any
 * state (a rejected post still counts).
 *
 * @param int    $user_id User ID.
 * @param string $kind    'question' or 'reply'.
 * @return int
 */
function vance_community_count_recent( $user_id, $kind ) {
	// Local-time columns on purpose: a pending or draft post stores a zero
	// post_date_gmt (its date "floats"), so a GMT query would never count it.
	// WP_Date_Query reads "24 hours ago" against the site's local time.
	if ( 'reply' === $kind ) {
		return (int) get_comments( array(
			'user_id'    => (int) $user_id,
			'type'       => 'vance_reply',
			'status'     => 'all',
			'count'      => true,
			'date_query' => array( array( 'column' => 'comment_date', 'after' => '24 hours ago', 'inclusive' => true ) ),
		) );
	}
	$q = new WP_Query( array(
		'post_type'      => 'vance_question',
		'post_status'    => array( 'pending', 'publish', 'draft' ),
		'author'         => (int) $user_id,
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'date_query'     => array( array( 'column' => 'post_date', 'after' => '24 hours ago', 'inclusive' => true ) ),
	) );
	return (int) $q->found_posts;
}

/**
 * How many reports a member made in the last 24 hours, from their report rows.
 *
 * @param int $user_id User ID.
 * @return int
 */
function vance_community_count_recent_reports( $user_id ) {
	global $wpdb;
	$like  = $wpdb->esc_like( (int) $user_id . '|' ) . '%';
	$rows  = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s", '_vance_community_report', $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$rows  = array_merge( $rows, (array) $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->commentmeta} WHERE meta_key = %s AND meta_value LIKE %s", '_vance_community_report', $like ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$since = time() - DAY_IN_SECONDS;
	$count = 0;
	foreach ( $rows as $row ) {
		$parts = explode( '|', (string) $row );
		if ( isset( $parts[2] ) && (int) $parts[2] >= $since ) {
			$count++;
		}
	}
	return $count;
}

/**
 * The approved replies of a question, oldest first.
 *
 * @param int $question_id Question ID.
 * @param int $number      0 for all.
 * @return WP_Comment[]
 */
function vance_community_replies( $question_id, $number = 0 ) {
	$args = array(
		'post_id' => (int) $question_id,
		'type'    => 'vance_reply',
		'status'  => 'approve',
		'orderby' => 'comment_date_gmt',
		'order'   => 'ASC',
	);
	if ( $number > 0 ) {
		$args['number'] = (int) $number;
	}
	$replies = get_comments( $args );
	return is_array( $replies ) ? $replies : array();
}

/**
 * The number of approved replies of a question.
 *
 * @param int $question_id Question ID.
 * @return int
 */
function vance_community_reply_count( $question_id ) {
	return (int) get_comments( array(
		'post_id' => (int) $question_id,
		'type'    => 'vance_reply',
		'status'  => 'approve',
		'count'   => true,
	) );
}

/**
 * The active poll, or null. One poll is active at a time; if two ever carry
 * the flag, the newest wins.
 *
 * @return WP_Post|null
 */
function vance_community_active_poll() {
	$polls = get_posts( array(
		'post_type'      => 'vance_poll',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_key'       => '_vance_community_poll_active', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
		'no_found_rows'  => true,
	) );
	return ( ! empty( $polls ) && $polls[0] instanceof WP_Post ) ? $polls[0] : null;
}

/**
 * The answers of a poll (3 to 5 strings), or an empty array when it has fewer
 * than three.
 *
 * @param int $poll_id Poll ID.
 * @return string[]
 */
function vance_community_poll_answers( $poll_id ) {
	$answers = get_post_meta( (int) $poll_id, '_vance_community_poll_answers', true );
	$answers = is_array( $answers ) ? array_values( array_filter( array_map( 'strval', $answers ), function ( $a ) {
		return '' !== trim( $a );
	} ) ) : array();
	$answers = array_slice( $answers, 0, 5 );
	return count( $answers ) >= 3 ? $answers : array();
}

/**
 * The user meta key that holds a member's vote in a poll.
 *
 * @param int $poll_id Poll ID.
 * @return string
 */
function vance_community_vote_key( $poll_id ) {
	return '_vance_community_vote_' . (int) $poll_id;
}

/**
 * Votes per answer index, counted from the stored user meta rows.
 *
 * @param int $poll_id Poll ID.
 * @return array<int,int>
 */
function vance_community_poll_counts( $poll_id ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value, COUNT(*) AS votes FROM {$wpdb->usermeta} WHERE meta_key = %s GROUP BY meta_value", vance_community_vote_key( $poll_id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$out  = array();
	foreach ( (array) $rows as $row ) {
		if ( isset( $row['meta_value'], $row['votes'] ) && ctype_digit( (string) $row['meta_value'] ) ) {
			$out[ (int) $row['meta_value'] ] = (int) $row['votes'];
		}
	}
	return $out;
}

/**
 * A member's vote in a poll: the answer index, or -1 when they have not voted.
 *
 * @param int $user_id User ID.
 * @param int $poll_id Poll ID.
 * @return int
 */
function vance_community_user_vote( $user_id, $poll_id ) {
	if ( (int) $user_id <= 0 ) {
		return -1;
	}
	$vote = get_user_meta( (int) $user_id, vance_community_vote_key( $poll_id ), true );
	return ( '' !== $vote && ctype_digit( (string) $vote ) ) ? (int) $vote : -1;
}

/**
 * True when a post or reply names a product.
 *
 * @param string $text Text.
 * @return bool
 */
function vance_community_names_product( $text ) {
	return vance_community_text_names_product( $text, vance_community_product_terms() );
}

/* =========================================================================
 * Moderation actions (used by the queue and by reports)
 * ====================================================================== */

/**
 * Set the state of a question or reply, and its visibility with it.
 *
 * @param string $kind   'question' or 'reply'.
 * @param int    $id     Post or comment ID.
 * @param string $state  waiting | approved | reported | rejected.
 * @param string $reason Reject reason key (rejected only).
 * @return bool
 */
function vance_community_set_state( $kind, $id, $state, $reason = '' ) {
	$id = (int) $id;
	if ( ! in_array( $state, array( 'waiting', 'approved', 'reported', 'rejected' ), true ) ) {
		return false;
	}
	if ( 'reply' === $kind ) {
		$comment = get_comment( $id );
		if ( ! $comment || 'vance_reply' !== $comment->comment_type ) {
			return false;
		}
		$res = wp_update_comment( array(
			'comment_ID'       => $id,
			'comment_approved' => 'approved' === $state ? 1 : 0,
		) );
		if ( false === $res || is_wp_error( $res ) ) {
			return false;
		}
		update_comment_meta( $id, '_vance_community_state', $state );
		if ( 'rejected' === $state && isset( vance_community_reject_reasons()[ $reason ] ) ) {
			update_comment_meta( $id, '_vance_community_reject_reason', $reason );
		} elseif ( 'rejected' !== $state ) {
			delete_comment_meta( $id, '_vance_community_reject_reason' );
		}
		return true;
	}

	$post = get_post( $id );
	if ( ! $post || 'vance_question' !== $post->post_type ) {
		return false;
	}
	$status = 'approved' === $state ? 'publish' : ( 'rejected' === $state ? 'draft' : 'pending' );
	if ( $post->post_status !== $status ) {
		$res = wp_update_post( array( 'ID' => $id, 'post_status' => $status ), true );
		if ( is_wp_error( $res ) || ! $res ) {
			return false;
		}
	}
	update_post_meta( $id, '_vance_community_state', $state );
	if ( 'rejected' === $state && isset( vance_community_reject_reasons()[ $reason ] ) ) {
		update_post_meta( $id, '_vance_community_reject_reason', $reason );
	} elseif ( 'rejected' !== $state ) {
		delete_post_meta( $id, '_vance_community_reject_reason' );
	}
	return true;
}

/**
 * Keep the state in step when a moderator publishes or unpublishes a question
 * in the WordPress editor instead of the queue.
 *
 * @param string  $new  New status.
 * @param string  $old  Old status.
 * @param WP_Post $post Post.
 */
function vance_community_sync_post_state( $new, $old, $post ) {
	if ( ! $post instanceof WP_Post || 'vance_question' !== $post->post_type || $new === $old ) {
		return;
	}
	if ( 'publish' === $new ) {
		update_post_meta( $post->ID, '_vance_community_state', 'approved' );
		delete_post_meta( $post->ID, '_vance_community_reject_reason' );
	} elseif ( 'publish' === $old && 'approved' === vance_community_state( 'question', $post->ID ) ) {
		update_post_meta( $post->ID, '_vance_community_state', 'waiting' );
	}
}
add_action( 'transition_post_status', 'vance_community_sync_post_state', 10, 3 );

/**
 * Keep the state in step when a moderator approves or unapproves a reply on
 * the WordPress Comments screen.
 *
 * @param string     $new     New status.
 * @param string     $old     Old status.
 * @param WP_Comment $comment Comment.
 */
function vance_community_sync_comment_state( $new, $old, $comment ) {
	if ( ! $comment instanceof WP_Comment || 'vance_reply' !== $comment->comment_type || $new === $old ) {
		return;
	}
	if ( 'approved' === $new ) {
		update_comment_meta( $comment->comment_ID, '_vance_community_state', 'approved' );
		delete_comment_meta( $comment->comment_ID, '_vance_community_reject_reason' );
	} elseif ( 'approved' === $old && 'approved' === vance_community_state( 'reply', $comment->comment_ID ) ) {
		update_comment_meta( $comment->comment_ID, '_vance_community_state', 'waiting' );
	}
}
add_action( 'transition_comment_status', 'vance_community_sync_comment_state', 10, 3 );

/* =========================================================================
 * Member writes (admin-ajax)
 *
 * Every action: nonce vance_community_nonce, a logged-in user, the switch on
 * (or a previewing administrator). Visitors get a 401 with the join address.
 * ====================================================================== */

/**
 * The AJAX action names, in one place for the PHP and the script.
 *
 * @return string[]
 */
function vance_community_ajax_actions() {
	return array(
		'vance_community_ask',
		'vance_community_reply',
		'vance_community_metoo',
		'vance_community_helpful',
		'vance_community_vote',
		'vance_community_report',
		'vance_community_profile',
	);
}

/**
 * Check the request. Ends the request with an error when it fails.
 *
 * @return int The member's user ID.
 */
function vance_community_ajax_guard() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array(
			'code'    => 'login',
			'message' => __( 'Join free or sign in to do this.', 'vance-health-hub' ),
			'join'    => vance_community_join_url(),
		), 401 );
	}
	if ( false === check_ajax_referer( 'vance_community_nonce', 'nonce', false ) ) {
		wp_send_json_error( array( 'code' => 'nonce', 'message' => __( 'This page is out of date. Reload the page and try again.', 'vance-health-hub' ) ), 403 );
	}
	if ( ! vance_community_writes_open() ) {
		wp_send_json_error( array( 'code' => 'closed', 'message' => __( 'The community is closed.', 'vance-health-hub' ) ), 403 );
	}
	return get_current_user_id();
}

/**
 * Visitors: every action answers "join first".
 */
function vance_community_ajax_visitor() {
	wp_send_json_error( array(
		'code'    => 'login',
		'message' => __( 'Join free or sign in to do this.', 'vance-health-hub' ),
		'join'    => vance_community_join_url(),
	), 401 );
}

/**
 * A POST field as a string ('' when absent or not a string).
 *
 * @param string $key Field name.
 * @return string
 */
function vance_community_post_field( $key ) {
	return ( isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked in vance_community_ajax_guard(); every caller sanitises.
}

/**
 * Ask a question. It goes to the queue as "waiting".
 */
function vance_community_ajax_ask() {
	$user_id = vance_community_ajax_guard();

	if ( vance_community_count_recent( $user_id, 'question' ) >= vance_community_limit( 'questions_per_day' ) ) {
		wp_send_json_error( array( 'message' => sprintf(
			/* translators: %d: number of questions. */
			__( 'You can ask %d questions a day. Please try again tomorrow.', 'vance-health-hub' ),
			vance_community_limit( 'questions_per_day' )
		) ), 429 );
	}

	$title = vance_community_plain_text( vance_community_post_field( 'title' ), vance_community_limit( 'title_max' ), false );
	$text  = vance_community_plain_text( vance_community_post_field( 'text' ), vance_community_limit( 'text_max' ), true );
	$space = sanitize_key( vance_community_post_field( 'space' ) );

	if ( vance_community_strlen( $title ) < vance_community_limit( 'title_min' ) ) {
		wp_send_json_error( array( 'message' => __( 'Type your question. Use a few words or more.', 'vance-health-hub' ) ), 400 );
	}
	$term = '' !== $space ? get_term_by( 'slug', $space, 'vance_space' ) : false;
	if ( ! $term instanceof WP_Term ) {
		wp_send_json_error( array( 'message' => __( 'Choose a space for your question.', 'vance-health-hub' ) ), 400 );
	}

	$id = wp_insert_post( array(
		'post_type'    => 'vance_question',
		'post_status'  => 'pending',
		'post_title'   => $title,
		'post_content' => $text,
		'post_author'  => $user_id,
	), true );
	if ( is_wp_error( $id ) || ! $id ) {
		wp_send_json_error( array( 'message' => __( 'Your question was not saved. Please try again.', 'vance-health-hub' ) ), 500 );
	}
	wp_set_object_terms( (int) $id, array( (int) $term->term_id ), 'vance_space', false );
	update_post_meta( (int) $id, '_vance_community_state', 'waiting' );

	wp_send_json_success( array(
		'message' => __( 'Thank you. A moderator reads your question before it shows.', 'vance-health-hub' ),
	) );
}

/**
 * Reply to an approved question. It goes to the queue as "waiting".
 */
function vance_community_ajax_reply() {
	$user_id = vance_community_ajax_guard();

	$question_id = absint( vance_community_post_field( 'question' ) );
	$question    = $question_id ? get_post( $question_id ) : null;
	if ( ! $question || 'vance_question' !== $question->post_type || 'publish' !== $question->post_status ) {
		wp_send_json_error( array( 'message' => __( 'This question is not open for replies.', 'vance-health-hub' ) ), 404 );
	}

	if ( vance_community_count_recent( $user_id, 'reply' ) >= vance_community_limit( 'replies_per_day' ) ) {
		wp_send_json_error( array( 'message' => sprintf(
			/* translators: %d: number of replies. */
			__( 'You can write %d replies a day. Please try again tomorrow.', 'vance-health-hub' ),
			vance_community_limit( 'replies_per_day' )
		) ), 429 );
	}

	$text = vance_community_plain_text( vance_community_post_field( 'text' ), vance_community_limit( 'reply_max' ), true );
	if ( vance_community_strlen( $text ) < vance_community_limit( 'reply_min' ) ) {
		wp_send_json_error( array( 'message' => __( 'Type your reply.', 'vance-health-hub' ) ), 400 );
	}

	// comment_author is the community display name, never the login or email.
	// No email or IP address is stored with a reply: the user ID is enough, and
	// less personal data is kept. wp_insert_comment() sends no notification.
	$comment_id = wp_insert_comment( array(
		'comment_post_ID'      => $question_id,
		'comment_content'      => $text,
		'comment_type'         => 'vance_reply',
		'comment_parent'       => 0,
		'comment_approved'     => 0,
		'user_id'              => $user_id,
		'comment_author'       => vance_community_display_name( $user_id ),
		'comment_author_email' => '',
		'comment_author_url'   => '',
		'comment_author_IP'    => '',
		'comment_agent'        => '',
	) );
	if ( ! $comment_id ) {
		wp_send_json_error( array( 'message' => __( 'Your reply was not saved. Please try again.', 'vance-health-hub' ) ), 500 );
	}
	update_comment_meta( (int) $comment_id, '_vance_community_state', 'waiting' );

	wp_send_json_success( array(
		'message' => __( 'Thank you. A moderator reads your reply before it shows.', 'vance-health-hub' ),
	) );
}

/**
 * Toggle one row for this member under a key: add it, or take it away.
 *
 * @param string $type    'post' or 'comment'.
 * @param int    $id      Object ID.
 * @param string $key     Meta key.
 * @param int    $user_id User ID.
 * @return array{active:bool,count:int}
 */
function vance_community_toggle_row( $type, $id, $key, $user_id ) {
	if ( vance_community_has_row( $type, $id, $key, $user_id ) ) {
		delete_metadata( $type, $id, $key, (string) $user_id );
		$active = false;
	} else {
		add_metadata( $type, $id, $key, (string) $user_id, false );
		$active = true;
	}
	return array( 'active' => $active, 'count' => vance_community_meta_rows( $type, $id, $key ) );
}

/**
 * "Me too" on an approved question.
 */
function vance_community_ajax_metoo() {
	$user_id  = vance_community_ajax_guard();
	$id       = absint( vance_community_post_field( 'id' ) );
	$question = $id ? get_post( $id ) : null;
	if ( ! $question || 'vance_question' !== $question->post_type || 'publish' !== $question->post_status ) {
		wp_send_json_error( array( 'message' => __( 'This question is not available.', 'vance-health-hub' ) ), 404 );
	}
	wp_send_json_success( vance_community_toggle_row( 'post', $id, '_vance_community_metoo', $user_id ) );
}

/**
 * "Helpful" on an approved reply.
 */
function vance_community_ajax_helpful() {
	$user_id = vance_community_ajax_guard();
	$id      = absint( vance_community_post_field( 'id' ) );
	$comment = $id ? get_comment( $id ) : null;
	if ( ! $comment || 'vance_reply' !== $comment->comment_type || '1' !== (string) $comment->comment_approved ) {
		wp_send_json_error( array( 'message' => __( 'This reply is not available.', 'vance-health-hub' ) ), 404 );
	}
	wp_send_json_success( vance_community_toggle_row( 'comment', $id, '_vance_community_helpful', $user_id ) );
}

/**
 * Vote in the active poll. One vote per member; the vote cannot be changed.
 */
function vance_community_ajax_vote() {
	$user_id = vance_community_ajax_guard();
	$poll_id = absint( vance_community_post_field( 'poll' ) );
	$answer  = vance_community_post_field( 'answer' );
	$active  = vance_community_active_poll();

	if ( ! $active || (int) $active->ID !== $poll_id ) {
		wp_send_json_error( array( 'message' => __( 'This poll is closed.', 'vance-health-hub' ) ), 404 );
	}
	$answers = vance_community_poll_answers( $poll_id );
	if ( ! ctype_digit( $answer ) || (int) $answer >= count( $answers ) ) {
		wp_send_json_error( array( 'message' => __( 'Choose an answer.', 'vance-health-hub' ) ), 400 );
	}
	// add_user_meta with $unique: a second vote (two taps, two tabs) is refused.
	if ( vance_community_user_vote( $user_id, $poll_id ) < 0 ) {
		add_user_meta( $user_id, vance_community_vote_key( $poll_id ), (string) (int) $answer, true );
	}
	wp_send_json_success( array( 'html' => vance_community_poll_html( $active, $user_id, true ) ) );
}

/**
 * Report a question or reply. The item is hidden at once and goes to the
 * Reported tab of the queue.
 */
function vance_community_ajax_report() {
	$user_id = vance_community_ajax_guard();
	$kind    = 'reply' === vance_community_post_field( 'kind' ) ? 'reply' : 'question';
	$id      = absint( vance_community_post_field( 'id' ) );
	$reason  = sanitize_key( vance_community_post_field( 'reason' ) );

	if ( ! isset( vance_community_report_reasons()[ $reason ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Choose a reason.', 'vance-health-hub' ) ), 400 );
	}
	if ( 'reply' === $kind ) {
		$comment = $id ? get_comment( $id ) : null;
		$ok      = $comment && 'vance_reply' === $comment->comment_type && '1' === (string) $comment->comment_approved;
		$type    = 'comment';
	} else {
		$post = $id ? get_post( $id ) : null;
		$ok   = $post && 'vance_question' === $post->post_type && 'publish' === $post->post_status;
		$type = 'post';
	}
	if ( ! $ok ) {
		wp_send_json_error( array( 'message' => __( 'This post is not available.', 'vance-health-hub' ) ), 404 );
	}
	if ( vance_community_count_recent_reports( $user_id ) >= vance_community_limit( 'reports_per_day' ) ) {
		wp_send_json_error( array( 'message' => __( 'You have sent many reports today. A moderator will look at them.', 'vance-health-hub' ) ), 429 );
	}

	// One report per member per post. A repeat report from the same member does
	// not hide the post again, so one member cannot keep re-hiding a post that a
	// moderator has approved after their report.
	if ( vance_community_has_row( $type, $id, '_vance_community_reporter', $user_id ) ) {
		wp_send_json_error( array( 'message' => __( 'You reported this post before. A moderator has checked it.', 'vance-health-hub' ) ), 409 );
	}
	add_metadata( $type, $id, '_vance_community_reporter', (string) $user_id, false );
	add_metadata( $type, $id, '_vance_community_report', $user_id . '|' . $reason . '|' . time(), false );
	vance_community_set_state( $kind, $id, 'reported' );

	wp_send_json_success( array(
		'message' => __( 'Thank you. This post is hidden until a moderator checks it.', 'vance-health-hub' ),
	) );
}

/**
 * Save how the member's name shows: first name or nickname.
 */
function vance_community_ajax_profile() {
	$user_id  = vance_community_ajax_guard();
	$mode     = 'nickname' === vance_community_post_field( 'mode' ) ? 'nickname' : 'first';
	$nickname = vance_community_plain_text( vance_community_post_field( 'nickname' ), vance_community_limit( 'nickname_max' ), false );

	if ( false !== strpos( $nickname, '@' ) ) {
		wp_send_json_error( array( 'message' => __( 'A nickname cannot contain an email address.', 'vance-health-hub' ) ), 400 );
	}
	if ( 'nickname' === $mode && vance_community_strlen( $nickname ) < 2 ) {
		wp_send_json_error( array( 'message' => __( 'Type a nickname of two letters or more.', 'vance-health-hub' ) ), 400 );
	}
	update_user_meta( $user_id, '_vance_community_name_mode', $mode );
	update_user_meta( $user_id, '_vance_community_nickname', $nickname );

	wp_send_json_success( array(
		'name'    => vance_community_display_name( $user_id ),
		'message' => __( 'Saved.', 'vance-health-hub' ),
	) );
}

/**
 * Hook every action for members, and the "join first" answer for visitors.
 */
function vance_community_register_ajax() {
	$handlers = array(
		'vance_community_ask'     => 'vance_community_ajax_ask',
		'vance_community_reply'   => 'vance_community_ajax_reply',
		'vance_community_metoo'   => 'vance_community_ajax_metoo',
		'vance_community_helpful' => 'vance_community_ajax_helpful',
		'vance_community_vote'    => 'vance_community_ajax_vote',
		'vance_community_report'  => 'vance_community_ajax_report',
		'vance_community_profile' => 'vance_community_ajax_profile',
	);
	foreach ( $handlers as $action => $callback ) {
		add_action( 'wp_ajax_' . $action, $callback );
		add_action( 'wp_ajax_nopriv_' . $action, 'vance_community_ajax_visitor' );
	}
}
vance_community_register_ajax();

/* =========================================================================
 * Personal data: exporter and eraser
 * ====================================================================== */

/**
 * Register the exporter.
 *
 * @param array $exporters Exporters.
 * @return array
 */
function vance_community_register_exporter( $exporters ) {
	$exporters['vance-community'] = array(
		'exporter_friendly_name' => __( 'Vance community', 'vance-health-hub' ),
		'callback'               => 'vance_community_export',
	);
	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'vance_community_register_exporter' );

/**
 * Register the eraser.
 *
 * @param array $erasers Erasers.
 * @return array
 */
function vance_community_register_eraser( $erasers ) {
	$erasers['vance-community'] = array(
		'eraser_friendly_name' => __( 'Vance community', 'vance-health-hub' ),
		'callback'             => 'vance_community_erase',
	);
	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'vance_community_register_eraser' );

/**
 * IDs of objects that carry a row with this user under a key.
 *
 * @param string $type    'post' or 'comment'.
 * @param string $key     Meta key.
 * @param int    $user_id User ID.
 * @return int[]
 */
function vance_community_ids_with_row( $type, $key, $user_id ) {
	global $wpdb;
	$table  = 'comment' === $type ? $wpdb->commentmeta : $wpdb->postmeta;
	$column = 'comment' === $type ? 'comment_id' : 'post_id';
	return array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT {$column} FROM {$table} WHERE meta_key = %s AND meta_value = %s", $key, (string) (int) $user_id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

/**
 * Export one member's community data.
 *
 * @param string $email Email address.
 * @param int    $page  Page (all data fits on page 1).
 * @return array
 */
function vance_community_export( $email, $page = 1 ) {
	$user = get_user_by( 'email', $email );
	$data = array();
	if ( ! $user || (int) $page > 1 ) {
		return array( 'data' => $data, 'done' => true );
	}
	$uid = (int) $user->ID;

	$questions = get_posts( array(
		'post_type'      => 'vance_question',
		'post_status'    => array( 'pending', 'publish', 'draft', 'trash' ),
		'author'         => $uid,
		'posts_per_page' => -1,
	) );
	foreach ( $questions as $q ) {
		$space  = vance_community_question_space( $q->ID );
		$data[] = array(
			'group_id'    => 'vance-community-questions',
			'group_label' => __( 'Community questions', 'vance-health-hub' ),
			'item_id'     => 'vance-question-' . $q->ID,
			'data'        => array(
				array( 'name' => __( 'Question', 'vance-health-hub' ), 'value' => $q->post_title ),
				array( 'name' => __( 'Text', 'vance-health-hub' ), 'value' => $q->post_content ),
				array( 'name' => __( 'Space', 'vance-health-hub' ), 'value' => $space ? $space->name : '' ),
				array( 'name' => __( 'State', 'vance-health-hub' ), 'value' => vance_community_state( 'question', $q->ID ) ),
				array( 'name' => __( 'Date', 'vance-health-hub' ), 'value' => $q->post_date ),
			),
		);
	}

	$replies = get_comments( array( 'user_id' => $uid, 'type' => 'vance_reply', 'status' => 'all' ) );
	foreach ( (array) $replies as $r ) {
		$data[] = array(
			'group_id'    => 'vance-community-replies',
			'group_label' => __( 'Community replies', 'vance-health-hub' ),
			'item_id'     => 'vance-reply-' . $r->comment_ID,
			'data'        => array(
				array( 'name' => __( 'Reply', 'vance-health-hub' ), 'value' => $r->comment_content ),
				array( 'name' => __( 'Question', 'vance-health-hub' ), 'value' => get_the_title( (int) $r->comment_post_ID ) ),
				array( 'name' => __( 'State', 'vance-health-hub' ), 'value' => vance_community_state( 'reply', $r->comment_ID ) ),
				array( 'name' => __( 'Date', 'vance-health-hub' ), 'value' => $r->comment_date ),
			),
		);
	}

	foreach ( vance_community_ids_with_row( 'post', '_vance_community_metoo', $uid ) as $pid ) {
		$data[] = array(
			'group_id'    => 'vance-community-metoo',
			'group_label' => __( 'Community "Me too"', 'vance-health-hub' ),
			'item_id'     => 'vance-metoo-' . $pid,
			'data'        => array( array( 'name' => __( 'Question', 'vance-health-hub' ), 'value' => get_the_title( $pid ) ) ),
		);
	}
	foreach ( vance_community_ids_with_row( 'comment', '_vance_community_helpful', $uid ) as $cid ) {
		$data[] = array(
			'group_id'    => 'vance-community-helpful',
			'group_label' => __( 'Community "Helpful"', 'vance-health-hub' ),
			'item_id'     => 'vance-helpful-' . $cid,
			'data'        => array( array( 'name' => __( 'Reply ID', 'vance-health-hub' ), 'value' => (string) $cid ) ),
		);
	}
	foreach ( array( 'post', 'comment' ) as $type ) {
		foreach ( vance_community_ids_with_row( $type, '_vance_community_reporter', $uid ) as $oid ) {
			$data[] = array(
				'group_id'    => 'vance-community-reports',
				'group_label' => __( 'Community reports', 'vance-health-hub' ),
				'item_id'     => 'vance-report-' . $type . '-' . $oid,
				'data'        => array( array( 'name' => 'post' === $type ? __( 'Question ID', 'vance-health-hub' ) : __( 'Reply ID', 'vance-health-hub' ), 'value' => (string) $oid ) ),
			);
		}
	}

	$meta = get_user_meta( $uid );
	foreach ( (array) $meta as $key => $values ) {
		if ( 0 === strpos( $key, '_vance_community_vote_' ) ) {
			$poll_id = (int) substr( $key, strlen( '_vance_community_vote_' ) );
			$answers = get_post_meta( $poll_id, '_vance_community_poll_answers', true );
			$index   = (int) ( is_array( $values ) ? reset( $values ) : $values );
			$data[]  = array(
				'group_id'    => 'vance-community-votes',
				'group_label' => __( 'Community poll votes', 'vance-health-hub' ),
				'item_id'     => 'vance-vote-' . $poll_id,
				'data'        => array(
					array( 'name' => __( 'Poll', 'vance-health-hub' ), 'value' => get_the_title( $poll_id ) ),
					array( 'name' => __( 'Answer', 'vance-health-hub' ), 'value' => ( is_array( $answers ) && isset( $answers[ $index ] ) ) ? (string) $answers[ $index ] : (string) $index ),
				),
			);
		}
	}
	$nick = (string) get_user_meta( $uid, '_vance_community_nickname', true );
	if ( '' !== $nick ) {
		$data[] = array(
			'group_id'    => 'vance-community-profile',
			'group_label' => __( 'Community name', 'vance-health-hub' ),
			'item_id'     => 'vance-community-profile',
			'data'        => array( array( 'name' => __( 'Nickname', 'vance-health-hub' ), 'value' => $nick ) ),
		);
	}

	return array( 'data' => $data, 'done' => true );
}

/**
 * Erase one member's community data: their questions (and the replies under
 * them), their replies, votes, "Me too" and "Helpful" rows, report rows and
 * name settings.
 *
 * @param string $email Email address.
 * @param int    $page  Page (all data is removed on page 1).
 * @return array
 */
function vance_community_erase( $email, $page = 1 ) {
	global $wpdb;
	$user = get_user_by( 'email', $email );
	$out  = array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
	if ( ! $user || (int) $page > 1 ) {
		return $out;
	}
	$uid     = (int) $user->ID;
	$removed = 0;

	$questions = get_posts( array(
		'post_type'      => 'vance_question',
		// 'any' leaves out the bin (and auto-drafts), so name every status.
		'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private', 'trash', 'auto-draft' ),
		'author'         => $uid,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	$retained = false;
	foreach ( $questions as $qid ) {
		$qid = (int) $qid;
		// Replies from other members belong to them. A question they replied to is
		// kept as an empty, hidden shell with no author, so their replies survive.
		$others = 0;
		foreach ( (array) get_comments( array( 'post_id' => $qid, 'type' => 'vance_reply', 'status' => 'all' ) ) as $reply ) {
			if ( (int) $reply->user_id !== $uid ) {
				$others++;
			}
		}
		if ( $others > 0 ) {
			wp_update_post( array(
				'ID'           => $qid,
				'post_author'  => 0,
				'post_status'  => 'draft',
				'post_title'   => __( 'Question removed at the member\'s request', 'vance-health-hub' ),
				'post_content' => '',
				// The old slug was made from the erased title, so replace it too.
				'post_name'    => 'question-removed-' . $qid,
			) );
			update_post_meta( $qid, '_vance_community_state', 'rejected' );
			delete_post_meta( $qid, '_vance_community_reject_reason' );
			delete_post_meta( $qid, '_vance_community_report' );
			delete_post_meta( $qid, '_vance_community_reporter' );
			$retained = true;
			$removed++;
			continue;
		}
		if ( wp_delete_post( $qid, true ) ) {
			$removed++;
		}
	}
	if ( $retained ) {
		$out['items_retained'] = true;
		$out['messages'][]     = __( 'A question that other members replied to was kept without its title, text or author, so their replies are not lost.', 'vance-health-hub' );
	}

	$replies = get_comments( array( 'user_id' => $uid, 'type' => 'vance_reply', 'status' => 'all', 'fields' => 'ids' ) );
	foreach ( (array) $replies as $cid ) {
		if ( wp_delete_comment( (int) $cid, true ) ) {
			$removed++;
		}
	}

	$value = (string) $uid;
	foreach ( array( '_vance_community_metoo', '_vance_community_reporter' ) as $key ) {
		$removed += count( vance_community_ids_with_row( 'post', $key, $uid ) );
		delete_metadata( 'post', 0, $key, $value, true );
	}
	foreach ( array( '_vance_community_helpful', '_vance_community_reporter' ) as $key ) {
		$removed += count( vance_community_ids_with_row( 'comment', $key, $uid ) );
		delete_metadata( 'comment', 0, $key, $value, true );
	}
	// Report rows start with "uid|". Removed one by one through the meta API so
	// the object caches are cleared with them.
	$like = $wpdb->esc_like( $uid . '|' ) . '%';
	$mids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s", '_vance_community_report', $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( (array) $mids as $mid ) {
		delete_metadata_by_mid( 'post', (int) $mid );
	}
	$mids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->commentmeta} WHERE meta_key = %s AND meta_value LIKE %s", '_vance_community_report', $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( (array) $mids as $mid ) {
		delete_metadata_by_mid( 'comment', (int) $mid );
	}

	foreach ( array_keys( (array) get_user_meta( $uid ) ) as $key ) {
		if ( 0 === strpos( $key, '_vance_community_' ) ) {
			delete_user_meta( $uid, $key );
			$removed++;
		}
	}

	$out['items_removed'] = $removed > 0;
	return $out;
}
