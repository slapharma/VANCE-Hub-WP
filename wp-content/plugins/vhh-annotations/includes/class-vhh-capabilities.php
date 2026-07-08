<?php
/**
 * Capability management for VHH Annotations.
 *
 * Two capabilities:
 *  - vhh_annotate              create highlights/comments (Customizer can extend to more roles)
 *  - vhh_moderate_annotations  resolve, delete others' notes, approve Claude to-dos, export
 *
 * Administrator and Editor always hold both; the Customizer "allowed roles"
 * setting only ever widens vhh_annotate to additional roles.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_Capabilities {

	const CAP_ANNOTATE = 'vhh_annotate';
	const CAP_MODERATE = 'vhh_moderate_annotations';

	/** Roles that always hold both caps and cannot be revoked via settings. */
	const CORE_ROLES = array( 'administrator', 'editor' );

	public static function add_caps() {
		foreach ( self::CORE_ROLES as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->add_cap( self::CAP_ANNOTATE );
				$role->add_cap( self::CAP_MODERATE );
			}
		}
	}

	public static function remove_caps() {
		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( self::CAP_ANNOTATE );
			$role->remove_cap( self::CAP_MODERATE );
		}
	}

	/**
	 * Reconcile vhh_annotate across all roles with the saved "allowed roles"
	 * setting. Core roles are always granted regardless of the list.
	 *
	 * @param string[] $allowed_roles Role slugs that should hold vhh_annotate.
	 */
	public static function sync_annotate_roles( array $allowed_roles ) {
		$allowed = array_unique( array_merge( self::CORE_ROLES, array_map( 'sanitize_key', $allowed_roles ) ) );
		foreach ( wp_roles()->role_objects as $name => $role ) {
			if ( in_array( $name, $allowed, true ) ) {
				$role->add_cap( self::CAP_ANNOTATE );
			} else {
				$role->remove_cap( self::CAP_ANNOTATE );
			}
		}
	}
}
