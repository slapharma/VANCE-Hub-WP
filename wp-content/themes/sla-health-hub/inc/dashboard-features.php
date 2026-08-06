<?php
/**
 * Dashboard feature toggles — Appearance → Customize → Dashboard Features.
 *
 * One checkbox per member-dashboard tab, so a feature can be taken off the site
 * without deleting its code or its data.
 *
 * WHY THE REGISTRY LIVES HERE AND NOT IN page-dashboard.php
 * --------------------------------------------------------
 * The nav, the tab router, the home-page summary cards and the AJAX endpoints
 * all have to agree about whether a feature is on. Left to page-dashboard.php,
 * that agreement would be four separate `if`s that drift the moment someone
 * edits one of them — and the failure mode is not cosmetic: a hidden tab whose
 * endpoints still answer is a feature that only *looks* switched off.
 *
 * WHAT "OFF" MEANS
 * ----------------
 * Off hides the nav item, drops the tab's card from the dashboard home grid,
 * and bounces a direct `?tab=` link back to home. For My Documents it also
 * shuts the upload, delete and stream endpoints and stops the AI grounding
 * filter, because that feature has server-side surface area that a hidden tab
 * would otherwise leave reachable by anyone who kept an admin-ajax URL.
 *
 * Off is NOT deletion. Nothing is removed: user meta, uploaded files and saved
 * conversations all stay exactly where they are, and turning a feature back on
 * restores the tab with its contents intact. That is deliberate — these are
 * health records and saved notes, and a display setting must never be the thing
 * that destroys them.
 *
 * @package sla-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every toggleable dashboard feature.
 *
 * Keys are the `?tab=` slugs used by page-dashboard.php's `$nav_items` and its
 * router, so the two cannot disagree about what a feature is called.
 *
 * `home` is deliberately absent. It is the landing tab and the fallback for a
 * disabled one, so a toggle for it would let an admin lock every member out of
 * the dashboard entirely with a single checkbox.
 *
 * @return array<string, array{label:string, description:string, default:bool}>
 */
function vance_dashboard_features() {
	return array(
		'profile'        => array(
			'label'       => 'My Profile',
			'description' => 'Name, avatar, bio and contact details. Turning this off leaves members no way to edit their own profile.',
			'default'     => true,
		),
		'health-profile' => array(
			'label'       => 'Health Profile',
			'description' => 'Health discovery results and the clinical profile questionnaire.',
			'default'     => true,
		),
		'tools'          => array(
			'label'       => 'My Tools',
			'description' => 'Saved calculator and blood-test results.',
			'default'     => true,
		),
		'reading-list'   => array(
			'label'       => 'My Reading List',
			'description' => 'Bookmarked articles.',
			'default'     => true,
		),
		'courses'        => array(
			'label'       => 'My Courses',
			'description' => 'Enrolled learning content.',
			'default'     => true,
		),
		'searches'       => array(
			'label'       => 'My Searches',
			'description' => 'Saved searches.',
			'default'     => true,
		),
		'notes'          => array(
			'label'       => 'My Notes',
			'description' => 'Private member notes.',
			'default'     => true,
		),
		'ai-chats'       => array(
			'label'       => 'My VANCE-Ai',
			'description' => 'Saved VANCE-Ai conversations. This does not disable VANCE-Ai itself, only the dashboard history tab.',
			'default'     => true,
		),
		'messages'       => array(
			'label'       => 'My Messages',
			'description' => 'Broadcast messages from the team.',
			'default'     => true,
		),
		'documents'      => array(
			'label'       => 'My Documents',
			'description' => 'Member-uploaded letters, results and care plans, with the "Ask VANCE-Ai about this document" flow. Off by default: it stores special category health data, so it should be switched on deliberately rather than inherited. Switching it off also closes the upload, download and AI endpoints; nothing already uploaded is deleted.',
			'default'     => false,
		),
	);
}

/**
 * Is a dashboard feature switched on?
 *
 * Unknown slugs return true so that a tab which has no toggle — `home`, or one
 * added later and not yet registered — keeps working. A missing registry entry
 * should never be the reason a member's tab vanishes.
 *
 * @param string $slug Tab slug.
 * @return bool
 */
function vance_dashboard_feature_enabled( $slug ) {
	$features = vance_dashboard_features();
	if ( ! isset( $features[ $slug ] ) ) {
		return true;
	}
	return (bool) vance_get_theme_mod(
		'vance_dash_feature_' . str_replace( '-', '_', $slug ),
		$features[ $slug ]['default']
	);
}

/**
 * Strip disabled features out of a `$nav_items` map, and drop any section left
 * empty so the sidebar does not render a bare "Learning" heading with nothing
 * under it.
 *
 * @param array $nav_items Section => slug => item.
 * @return array
 */
function vance_dashboard_filter_nav( $nav_items ) {
	foreach ( $nav_items as $section => $items ) {
		foreach ( $items as $slug => $data ) {
			if ( ! vance_dashboard_feature_enabled( $slug ) ) {
				unset( $nav_items[ $section ][ $slug ] );
			}
		}
		if ( empty( $nav_items[ $section ] ) ) {
			unset( $nav_items[ $section ] );
		}
	}
	return $nav_items;
}

/**
 * Register the toggles.
 *
 * A plain top-level section rather than a child of an existing panel: none of
 * the current panels is about the member dashboard, and burying this under
 * "Advanced" would hide the one control an admin is most likely to come looking
 * for.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function vance_dashboard_features_customize( $wp_customize ) {
	$wp_customize->add_section( 'vance_dashboard_features', array(
		'title'       => __( 'Dashboard Features', 'sla-health-hub' ),
		'priority'    => 33,
		'description' => __( 'Show or hide each tab of the member dashboard. Switching a feature off hides its tab and closes its endpoints, but never deletes anything a member has already saved.', 'sla-health-hub' ),
	) );

	$priority = 10;
	foreach ( vance_dashboard_features() as $slug => $feature ) {
		$setting = 'vance_dash_feature_' . str_replace( '-', '_', $slug );

		$wp_customize->add_setting( $setting, array(
			'default'           => $feature['default'],
			'sanitize_callback' => 'vance_dashboard_sanitize_checkbox',
		) );
		$wp_customize->add_control( $setting, array(
			'label'       => $feature['label'],
			'description' => $feature['description'],
			'section'     => 'vance_dashboard_features',
			'type'        => 'checkbox',
			'priority'    => $priority,
		) );

		$priority += 10;
	}
}
add_action( 'customize_register', 'vance_dashboard_features_customize' );

/**
 * Checkbox sanitiser.
 *
 * The Customizer posts an unchecked box as the string '' and a checked one as
 * '1', while the registered default is a real bool, so this has to accept both
 * shapes and always hand back a bool.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function vance_dashboard_sanitize_checkbox( $value ) {
	return ( true === $value || '1' === $value || 1 === $value || 'true' === $value );
}
