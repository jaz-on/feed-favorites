<?php
/**
 * Plugin capabilities.
 *
 * @package FeedFavorites
 * @since 1.0.2
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and removes the plugin management capability.
 */
class Capabilities {

	/**
	 * Capability required for settings, sync, and import admin actions.
	 */
	const MANAGE = 'manage_feed_favorites';

	/**
	 * Grant the capability to the administrator role (idempotent).
	 *
	 * @return void
	 */
	public static function register() {
		$role = get_role( 'administrator' );
		if ( $role && ! $role->has_cap( self::MANAGE ) ) {
			$role->add_cap( self::MANAGE );
		}
	}

	/**
	 * Remove the capability from the administrator role.
	 *
	 * @return void
	 */
	public static function remove_from_roles() {
		$role = get_role( 'administrator' );
		if ( $role && $role->has_cap( self::MANAGE ) ) {
			$role->remove_cap( self::MANAGE );
		}
	}
}
