<?php
/**
 * Pages for the organic social plan: /community/, /community-rules/,
 * /ibs-tools/ and /waitlist/.
 *
 * WordPress picks page-{slug}.php by itself, so the templates need no assignment.
 * A page is database content, so this file makes the missing ones once.
 *
 * - It never changes a page that exists, in any status, the bin included.
 * - /community-rules/ and /waitlist/ are made as DRAFTS. A person publishes them
 *   after review. /ibs-tools/ and /community/ are made published.
 * - /waitlist/ and, while its text is a draft, /community-rules/ send noindex.
 *
 * To run it without waiting for an administrator to open wp-admin:
 *   wp eval 'vance_marketing_ensure_pages();'
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The pages this file keeps.
 *
 * @return array<string,array> Slug => title, status, template.
 */
function vance_marketing_pages() {
	return array(
		'community'       => array(
			'title'    => __( 'Community', 'vance-health-hub' ),
			'status'   => 'publish',
			'template' => 'page-community.php',
		),
		'community-rules' => array(
			'title'    => __( 'Community rules', 'vance-health-hub' ),
			'status'   => 'draft',
			'template' => '',
		),
		'ibs-tools'       => array(
			'title'    => __( 'Free IBS tools', 'vance-health-hub' ),
			'status'   => 'publish',
			'template' => '',
		),
		'waitlist'        => array(
			'title'    => __( 'B-Vance waitlist', 'vance-health-hub' ),
			'status'   => 'draft',
			'template' => '',
		),
	);
}

/**
 * True when a page with this slug exists in any status, the bin included.
 *
 * @param string $slug Page slug.
 * @return bool
 */
function vance_marketing_page_exists( $slug ) {
	foreach ( array( $slug, $slug . '__trashed' ) as $name ) {
		$found = get_posts( array(
			'post_type'      => 'page',
			'name'           => $name,
			// 'any' leaves the bin out, so list every status, trash included.
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		if ( ! empty( $found ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Make the pages that are missing. Safe to run again.
 *
 * @return string[] Slugs made.
 */
function vance_marketing_ensure_pages() {
	$made = array();
	foreach ( vance_marketing_pages() as $slug => $page ) {
		if ( vance_marketing_page_exists( $slug ) ) {
			continue;
		}
		$args = array(
			'post_type'    => 'page',
			'post_title'   => $page['title'],
			'post_name'    => $slug,
			'post_status'  => $page['status'],
			'post_content' => '',
		);
		if ( '' !== $page['template'] ) {
			$args['meta_input'] = array( '_wp_page_template' => $page['template'] );
		}
		$id = wp_insert_post( $args, true );
		if ( ! is_wp_error( $id ) && $id ) {
			$made[] = $slug;
			if ( function_exists( 'vance_community_aioseo_noindex' ) && in_array( $slug, array( 'community', 'community-rules', 'waitlist' ), true ) ) {
				vance_community_aioseo_noindex( (int) $id );
			}
		}
	}
	return $made;
}

/**
 * Make the pages once, the first time an editor opens wp-admin after deploy.
 */
function vance_marketing_maybe_ensure_pages() {
	if ( ! current_user_can( 'publish_pages' ) || get_option( 'vance_marketing_pages_v1' ) ) {
		return;
	}
	vance_marketing_ensure_pages();
	update_option( 'vance_marketing_pages_v1', 1, false );
}
add_action( 'admin_init', 'vance_marketing_maybe_ensure_pages' );

/**
 * True on the rules page while the text is the built-in draft (the page itself
 * has no content of its own).
 *
 * @return bool
 */
function vance_marketing_rules_is_draft() {
	if ( ! is_page( 'community-rules' ) ) {
		return false;
	}
	$post = get_queried_object();
	return ! ( $post instanceof WP_Post ) || '' === trim( (string) $post->post_content );
}

/**
 * Noindex: the waitlist always, the rules while they are a draft.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function vance_marketing_wp_robots( $robots ) {
	if ( is_page( 'waitlist' ) || vance_marketing_rules_is_draft() ) {
		$robots['noindex'] = true;
		$robots['follow']  = false;
		unset( $robots['index'] );
	}
	return $robots;
}
add_filter( 'wp_robots', 'vance_marketing_wp_robots', 20 );

/**
 * The same, in AIOSEO's own tag (its output filter is the half that works here).
 *
 * @param array $attributes AIOSEO robots attributes.
 * @return array
 */
function vance_marketing_aioseo_robots( $attributes ) {
	if ( is_array( $attributes ) && ( is_page( 'waitlist' ) || vance_marketing_rules_is_draft() ) ) {
		$attributes['noindex'] = 'noindex';
	}
	return $attributes;
}
add_filter( 'aioseo_robots_meta', 'vance_marketing_aioseo_robots' );

/**
 * A page address with the visitor's campaign labels kept.
 *
 * The address of a bio link or a DM carries utm_* tags. A link on the landing
 * page passes the same tags on, so the next page still sees the source. This
 * reads the request only. It stores nothing and it redirects nothing.
 *
 * @param string $path Path with the final slash, such as '/ask-ai/'.
 * @return string
 */
function vance_marketing_tool_url( $path ) {
	$url = home_url( $path );
	if ( ! function_exists( 'vance_attribution_extract' ) ) {
		return $url;
	}
	$labels = vance_attribution_extract( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only campaign labels, each reduced to a short token.
	return empty( $labels ) ? $url : add_query_arg( $labels, $url );
}

/**
 * The three tools on the landing page. The paths are exact, with the final slash.
 *
 * @return array[]
 */
function vance_marketing_tools() {
	return array(
		array(
			'path'  => '/ask-ai/',
			'name'  => __( 'VANCE-Ai', 'vance-health-hub' ),
			'text'  => __( 'Ask a question about living with IBS. Answers show their sources. It does not diagnose and it is not a replacement for your clinician.', 'vance-health-hub' ),
			'cta'   => __( 'Ask VANCE-Ai', 'vance-health-hub' ),
		),
		array(
			'path'  => '/gastro-meal-planner/',
			'name'  => __( 'Gastro Meal Planner', 'vance-health-hub' ),
			'text'  => __( 'Plan a week of meals from the Hub recipes. Free to use.', 'vance-health-hub' ),
			'cta'   => __( 'Plan my week', 'vance-health-hub' ),
		),
		array(
			'path'  => '/gastro-health-survey/',
			'name'  => __( 'Gastro Self-Assessment', 'vance-health-hub' ),
			'text'  => __( 'A short questionnaire about your symptoms, food and lifestyle. You get a summary to take to your clinician. It is not a diagnosis.', 'vance-health-hub' ),
			'cta'   => __( 'Start the Self-Assessment', 'vance-health-hub' ),
		),
	);
}

/**
 * Stylesheet for the plan pages, only on those pages.
 */
function vance_marketing_enqueue() {
	if ( ! is_page( array( 'community-rules', 'ibs-tools', 'waitlist' ) ) ) {
		return;
	}
	$file = get_template_directory() . '/assets/css/marketing-pages.css';
	wp_enqueue_style(
		'vance-marketing-pages',
		get_template_directory_uri() . '/assets/css/marketing-pages.css',
		array( 'vance-main-style' ),
		@filemtime( $file ) ?: '1.0.0'
	);
	if ( is_page( 'waitlist' ) ) {
		$js = get_template_directory() . '/assets/js/waitlist.js';
		wp_enqueue_script(
			'vance-waitlist',
			get_template_directory_uri() . '/assets/js/waitlist.js',
			array(),
			@filemtime( $js ) ?: '1.0.0',
			true
		);
		wp_localize_script( 'vance-waitlist', 'vanceWaitlist', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'vance_waitlist' ),
		) );
	}
}
add_action( 'wp_enqueue_scripts', 'vance_marketing_enqueue', 25 );
