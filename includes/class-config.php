<?php
/**
 * Feed Favorites Configuration Management Class
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized configuration management
 */
class Config {

	/**
	 * Options prefix
	 */
	const OPTION_PREFIX = 'feed_favorites_';

	/**
	 * Default configuration
	 */
	private static $defaults = array(
		'feed_url'      => '',
		'auto_sync'     => '1',
		'sync_interval' => '7200', // 2 hours default
		'max_items'     => 50,
	);

	/**
	 * Allowed synchronization intervals
	 */
	private static $allowed_intervals = array(
		'900'   => '15 minutes',
		'1800'  => '30 minutes',
		'3600'  => '1 hour',
		'7200'  => '2 hours (recommended)',
		'14400' => '4 hours',
		'86400' => '1 day',
	);

	/**
	 * Get an option
	 */
	public static function get( $key, $default = null ) {
		$option_name = self::OPTION_PREFIX . $key;
		$value       = get_option( $option_name );

		// If option doesn't exist, return default value
		if ( $value === false && isset( self::$defaults[ $key ] ) ) {
			return self::$defaults[ $key ];
		}

		return $value !== false ? $value : $default;
	}

	/**
	 * Set an option
	 */
	public static function set( $key, $value ) {
		$option_name = self::OPTION_PREFIX . $key;
		return update_option( $option_name, $value );
	}

	/**
	 * Delete an option
	 */
	public static function delete( $key ) {
		$option_name = self::OPTION_PREFIX . $key;
		return delete_option( $option_name );
	}

	/**
	 * Get all options
	 */
	public static function get_all() {
		$options = array();
		foreach ( array_keys( self::$defaults ) as $key ) {
			$options[ $key ] = self::get( $key );
		}
		return $options;
	}

	/**
	 * Get allowed intervals
	 */
	public static function get_allowed_intervals() {
		return self::$allowed_intervals;
	}

	/**
	 * Validate an interval
	 */
	public static function is_valid_interval( $interval ) {
		return array_key_exists( $interval, self::$allowed_intervals );
	}

	/**
	 * Get default value for an option
	 */
	public static function get_default( $key ) {
		return isset( self::$defaults[ $key ] ) ? self::$defaults[ $key ] : null;
	}

	/**
	 * Initialize default options
	 */
	public static function init_defaults() {
		foreach ( self::$defaults as $key => $value ) {
			$option_name   = self::OPTION_PREFIX . $key;
			$current_value = get_option( $option_name );

			// If option doesn't exist, create it
			if ( $current_value === false ) {
				add_option( $option_name, $value );
			}
			// If it's the sync interval and it's not in allowed values, fix it
			elseif ( $key === 'sync_interval' && ! self::is_valid_interval( $current_value ) ) {
				update_option( $option_name, $value );
			}
		}
	}
}
