<?php
/**
 * Uninstall handler for VHH Annotations.
 *
 * Removes the plugin's settings and capabilities. Annotation rows in
 * wp_comments and vhh_todo posts are user content and are deliberately
 * PRESERVED — delete them manually (or via WP-CLI) if a full purge is wanted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'vhh_annotations_options' );

if ( function_exists( 'wp_roles' ) ) {
	foreach ( wp_roles()->role_objects as $role ) {
		$role->remove_cap( 'vhh_annotate' );
		$role->remove_cap( 'vhh_moderate_annotations' );
	}
}
