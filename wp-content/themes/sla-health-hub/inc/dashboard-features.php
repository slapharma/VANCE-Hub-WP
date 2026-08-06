<?php
/**
 * Dashboard features — Appearance → Customize → Member Dashboard.
 *
 * Per feature: show or hide it, rename its menu item, and move it between the
 * sidebar's sections. Plus editable headings for the sections themselves.
 *
 * WHY THE REGISTRY LIVES HERE AND NOT IN page-dashboard.php
 * --------------------------------------------------------
 * The sidebar, the breadcrumb, the tab router, the home summary grid and the
 * AJAX endpoints all have to agree about whether a feature is on and what it is
 * called. Left in the template, that agreement would be five separate `if`s
 * that drift the moment someone edits one — and the failure mode is not
 * cosmetic: a hidden tab whose endpoints still answer is a feature that only
 * *looks* switched off, and a renamed feature that keeps its old name on the
 * dashboard home cards just looks broken.
 *
 * So page-dashboard.php no longer hardcodes its `$nav_items`; it asks
 * vance_dashboard_nav_items() to build the whole structure from this registry
 * plus whatever the admin has saved.
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
 * The sidebar's sections, in the order they render, mapped to their default
 * heading.
 *
 * `main` renders with no heading by design — it sits directly under the logo
 * and a label there reads as clutter. An empty string means "no heading", and
 * because of that `main` gets no rename control either.
 *
 * @return array<string,string> section slug => default heading
 */
function vance_dashboard_sections() {
	return array(
		'main'          => '',
		'learning'      => 'Learning',
		'communication' => 'Communication',
	);
}

/**
 * The heading an admin has chosen for a section, falling back to the default.
 *
 * @param string $section Section slug.
 * @return string Empty string means render no heading.
 */
function vance_dashboard_section_label( $section ) {
	$sections = vance_dashboard_sections();
	if ( ! isset( $sections[ $section ] ) ) {
		return '';
	}
	// A section with no default heading has no control, so never look one up.
	if ( '' === $sections[ $section ] ) {
		return '';
	}
	$saved = vance_get_theme_mod( 'vance_dash_section_label_' . $section, $sections[ $section ] );
	$saved = trim( (string) $saved );

	// An admin who empties the box means "back to normal", not "render a blank
	// heading with a border above it".
	return '' === $saved ? $sections[ $section ] : $saved;
}

/**
 * Every dashboard feature, in registry order.
 *
 * Keys are the `?tab=` slugs used by page-dashboard.php's router, so the nav
 * and the router cannot disagree about what a feature is called.
 *
 * Registry order is also the order features appear within whichever section
 * they are assigned to, so moving two features into one section stacks them in
 * the order below.
 *
 * `home` is present so it can be renamed and moved like anything else, but is
 * marked `toggleable => false`: it is the landing tab and the fallback for a
 * disabled one, so a checkbox for it would let an admin lock every member out
 * of the dashboard with a single click.
 *
 * @return array<string, array{label:string, icon:string, section:string, description:string, default:bool, toggleable:bool}>
 */
function vance_dashboard_features() {
	return array(
		'home'           => array(
			'label'       => 'Dashboard',
			'icon'        => '📊',
			'section'     => 'main',
			'description' => 'The landing tab. It cannot be switched off, because it is where members are sent when they open a tab that has been.',
			'default'     => true,
			'toggleable'  => false,
		),
		'profile'        => array(
			'label'       => 'My Profile',
			'icon'        => '👤',
			'section'     => 'main',
			'description' => 'Name, avatar, bio and contact details. Turning this off leaves members no way to edit their own profile.',
			'default'     => true,
			'toggleable'  => true,
		),
		'health-profile' => array(
			'label'       => 'Health Profile',
			'icon'        => '🩺',
			'section'     => 'main',
			'description' => 'Health discovery results and the clinical profile questionnaire.',
			'default'     => true,
			'toggleable'  => true,
		),
		'tools'          => array(
			'label'       => 'My Tools',
			'icon'        => '🧮',
			'section'     => 'main',
			'description' => 'Saved calculator and blood-test results.',
			'default'     => true,
			'toggleable'  => true,
		),
		'reading-list'   => array(
			'label'       => 'My Reading List',
			'icon'        => '📚',
			'section'     => 'learning',
			'description' => 'Bookmarked articles.',
			'default'     => true,
			'toggleable'  => true,
		),
		'courses'        => array(
			'label'       => 'My Courses',
			'icon'        => '🎓',
			'section'     => 'learning',
			'description' => 'Enrolled learning content.',
			'default'     => true,
			'toggleable'  => true,
		),
		'searches'       => array(
			'label'       => 'My Searches',
			'icon'        => '🔍',
			'section'     => 'learning',
			'description' => 'Saved searches.',
			'default'     => true,
			'toggleable'  => true,
		),
		'notes'          => array(
			'label'       => 'My Notes',
			'icon'        => '📝',
			'section'     => 'communication',
			'description' => 'Private member notes.',
			'default'     => true,
			'toggleable'  => true,
		),
		'ai-chats'       => array(
			'label'       => 'My VANCE-Ai',
			'icon'        => '🤖',
			'section'     => 'communication',
			'description' => 'Saved VANCE-Ai conversations. This does not disable VANCE-Ai itself, only the dashboard history tab.',
			'default'     => true,
			'toggleable'  => true,
		),
		'messages'       => array(
			'label'       => 'My Messages',
			'icon'        => '💬',
			'section'     => 'communication',
			'description' => 'Broadcast messages from the team.',
			'default'     => true,
			'toggleable'  => true,
		),
		'documents'      => array(
			'label'       => 'My Documents',
			'icon'        => '📄',
			'section'     => 'communication',
			'description' => 'Member-uploaded letters, results and care plans, with the "Ask VANCE-Ai about this document" flow. Off by default: it stores special category health data, so it should be switched on deliberately rather than inherited. Switching it off also closes the upload, download and AI endpoints; nothing already uploaded is deleted.',
			'default'     => false,
			'toggleable'  => true,
		),
	);
}

/**
 * Setting key for a feature. Theme mod names cannot carry the hyphens the tab
 * slugs use, so `ai-chats` is stored as `..._ai_chats`.
 *
 * @param string $prefix Setting prefix.
 * @param string $slug   Feature slug.
 * @return string
 */
function vance_dashboard_setting_key( $prefix, $slug ) {
	return $prefix . str_replace( '-', '_', $slug );
}

/**
 * Is a dashboard feature switched on?
 *
 * Unknown slugs return true so that a tab which has no registry entry — one
 * added later and not yet registered — keeps working. A missing entry should
 * never be the reason a member's tab vanishes.
 *
 * @param string $slug Tab slug.
 * @return bool
 */
function vance_dashboard_feature_enabled( $slug ) {
	$features = vance_dashboard_features();
	if ( ! isset( $features[ $slug ] ) ) {
		return true;
	}
	if ( empty( $features[ $slug ]['toggleable'] ) ) {
		return true;
	}
	return (bool) vance_get_theme_mod(
		vance_dashboard_setting_key( 'vance_dash_feature_', $slug ),
		$features[ $slug ]['default']
	);
}

/**
 * The menu label an admin has chosen for a feature, falling back to its default.
 *
 * Falls back on an empty saved value too: clearing the box means "use the
 * original name", not "put a blank item in the sidebar".
 *
 * @param string $slug Tab slug.
 * @return string
 */
function vance_dashboard_feature_label( $slug ) {
	$features = vance_dashboard_features();
	if ( ! isset( $features[ $slug ] ) ) {
		return '';
	}
	$default = $features[ $slug ]['label'];
	$saved   = trim( (string) vance_get_theme_mod( vance_dashboard_setting_key( 'vance_dash_label_', $slug ), $default ) );
	return '' === $saved ? $default : $saved;
}

/**
 * The section a feature has been assigned to.
 *
 * Validated against the known sections rather than trusted: a stale saved value
 * — a section that has since been removed from the theme — would otherwise
 * conjure a phantom section into the sidebar, or drop the feature out of the
 * nav entirely. Either way the member loses a tab because of a setting they
 * cannot see.
 *
 * @param string $slug Tab slug.
 * @return string
 */
function vance_dashboard_feature_section( $slug ) {
	$features = vance_dashboard_features();
	if ( ! isset( $features[ $slug ] ) ) {
		return 'main';
	}
	$default = $features[ $slug ]['section'];
	$saved   = (string) vance_get_theme_mod( vance_dashboard_setting_key( 'vance_dash_section_', $slug ), $default );

	return array_key_exists( $saved, vance_dashboard_sections() ) ? $saved : $default;
}

/**
 * Build the sidebar structure: section => slug => {label, icon}.
 *
 * Replaces the hardcoded `$nav_items` array page-dashboard.php used to carry.
 * Disabled features are left out, and a section left empty by the toggles is
 * dropped so the sidebar never renders a bare heading with nothing under it.
 *
 * @return array<string, array<string, array{label:string, icon:string}>>
 */
function vance_dashboard_nav_items() {
	$nav = array();
	foreach ( array_keys( vance_dashboard_sections() ) as $section ) {
		$nav[ $section ] = array();
	}

	foreach ( vance_dashboard_features() as $slug => $feature ) {
		if ( ! vance_dashboard_feature_enabled( $slug ) ) {
			continue;
		}
		$section = vance_dashboard_feature_section( $slug );
		$nav[ $section ][ $slug ] = array(
			'label' => vance_dashboard_feature_label( $slug ),
			'icon'  => $feature['icon'],
		);
	}

	foreach ( $nav as $section => $items ) {
		if ( empty( $items ) ) {
			unset( $nav[ $section ] );
		}
	}

	return $nav;
}

/* ============================================================================
 * CUSTOMIZER
 * ========================================================================= */

/**
 * Register the panel, one section per feature.
 *
 * A section each rather than thirty-odd controls in a single list: with three
 * controls per feature a flat section becomes a wall of near-identical fields
 * where it is easy to rename the wrong thing. Each section is titled with the
 * feature's *current* label, so once a rename is published the panel reads back
 * as the admin's own names rather than the theme's.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function vance_dashboard_features_customize( $wp_customize ) {
	$wp_customize->add_panel( 'vance_dashboard_panel', array(
		'title'       => __( 'Member Dashboard', 'sla-health-hub' ),
		'priority'    => 33,
		'description' => __( 'Show or hide each tab of the member dashboard, rename it, and move it between the sidebar sections. Hiding a feature closes its endpoints too, but never deletes anything a member has already saved.', 'sla-health-hub' ),
	) );

	// --- Section headings --------------------------------------------------
	$wp_customize->add_section( 'vance_dashboard_headings', array(
		'title'       => __( 'Section headings', 'sla-health-hub' ),
		'panel'       => 'vance_dashboard_panel',
		'priority'    => 5,
		'description' => __( 'The small headings above each group in the sidebar. Worth editing after moving features around, since a group holding new things is often no longer described by its original name. A group with no features left in it is hidden automatically.', 'sla-health-hub' ),
	) );

	foreach ( vance_dashboard_sections() as $section => $default_heading ) {
		if ( '' === $default_heading ) {
			continue; // `main` renders no heading.
		}
		$setting = 'vance_dash_section_label_' . $section;
		$wp_customize->add_setting( $setting, array(
			'default'           => $default_heading,
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( $setting, array(
			'label'       => sprintf( __( '"%s" heading', 'sla-health-hub' ), $default_heading ),
			'description' => __( 'Leave empty to restore the original.', 'sla-health-hub' ),
			'section'     => 'vance_dashboard_headings',
			'type'        => 'text',
		) );
	}

	// --- One section per feature -------------------------------------------
	$section_choices = array();
	foreach ( vance_dashboard_sections() as $slug => $default_heading ) {
		$section_choices[ $slug ] = ( '' === $default_heading )
			? __( 'Top (no heading)', 'sla-health-hub' )
			: vance_dashboard_section_label( $slug );
	}

	$priority = 10;
	foreach ( vance_dashboard_features() as $slug => $feature ) {
		$section_id = 'vance_dash_feature_' . str_replace( '-', '_', $slug );

		$wp_customize->add_section( $section_id, array(
			// The admin's own name for it, so the panel list reflects renames.
			'title'       => vance_dashboard_feature_label( $slug ),
			'panel'       => 'vance_dashboard_panel',
			'priority'    => $priority,
			'description' => $feature['description'],
		) );

		// Show / hide.
		if ( ! empty( $feature['toggleable'] ) ) {
			$show = vance_dashboard_setting_key( 'vance_dash_feature_', $slug );
			$wp_customize->add_setting( $show, array(
				'default'           => $feature['default'],
				'sanitize_callback' => 'vance_dashboard_sanitize_checkbox',
			) );
			$wp_customize->add_control( $show, array(
				'label'    => __( 'Show this feature', 'sla-health-hub' ),
				'section'  => $section_id,
				'type'     => 'checkbox',
				'priority' => 10,
			) );
		}

		// Rename.
		$label = vance_dashboard_setting_key( 'vance_dash_label_', $slug );
		$wp_customize->add_setting( $label, array(
			'default'           => $feature['label'],
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( $label, array(
			'label'       => __( 'Menu label', 'sla-health-hub' ),
			'description' => sprintf(
				/* translators: %s: the feature's original name. */
				__( 'Shown in the sidebar, the page heading and the dashboard home card. Leave empty to restore "%s".', 'sla-health-hub' ),
				$feature['label']
			),
			'section'     => $section_id,
			'type'        => 'text',
			'priority'    => 20,
		) );

		// Move.
		$place = vance_dashboard_setting_key( 'vance_dash_section_', $slug );
		$wp_customize->add_setting( $place, array(
			'default'           => $feature['section'],
			'sanitize_callback' => 'vance_dashboard_sanitize_section',
		) );
		$wp_customize->add_control( $place, array(
			'label'       => __( 'Sidebar group', 'sla-health-hub' ),
			'description' => __( 'Features appear within a group in the order they are listed in this panel.', 'sla-health-hub' ),
			'section'     => $section_id,
			'type'        => 'select',
			'choices'     => $section_choices,
			'priority'    => 30,
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

/**
 * Section sanitiser — only a section the theme actually renders may be stored.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function vance_dashboard_sanitize_section( $value ) {
	$sections = vance_dashboard_sections();
	return array_key_exists( (string) $value, $sections ) ? (string) $value : 'main';
}
