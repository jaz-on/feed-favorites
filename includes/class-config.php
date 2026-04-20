<?php
/**
 * Feed Favorites Configuration Management Class
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized configuration management.
 */
class Config {

	/**
	 * Options prefix.
	 *
	 * @var string
	 */
	const OPTION_PREFIX = 'feed_favorites_';

	/**
	 * Default configuration.
	 *
	 * @var array
	 */
	private static $defaults = array(
		'feed_url'              => '',
		'auto_sync'             => '1',
		'sync_interval'         => '7200', // 2 hours default.
		'max_items'             => 50,
		'sync_post_author'      => 0,
		'last_sync_items'       => 0,
		'default_show_emoji'    => true,
		'default_open_new_tab' => true,
		'link_summary_required' => false,
		'commentary_required'   => false,
		'use_link_format'       => true,
	);

	/**
	 * Allowed synchronization intervals.
	 *
	 * @var array
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
	 * Get an option.
	 *
	 * @param string $key The option key.
	 * @param mixed  $default_value The default value if option doesn't exist.
	 * @return mixed The option value.
	 */
	public static function get( $key, $default_value = null ) {
		$option_name = self::OPTION_PREFIX . $key;
		$value       = get_option( $option_name );

		// If option doesn't exist, return default value.
		if ( false === $value && isset( self::$defaults[ $key ] ) ) {
			return self::$defaults[ $key ];
		}

		return false !== $value ? $value : $default_value;
	}

	/**
	 * Set an option.
	 *
	 * @param string $key The option key.
	 * @param mixed  $value The value to set.
	 * @return bool True on success, false on failure.
	 */
	public static function set( $key, $value ) {
		$option_name = self::OPTION_PREFIX . $key;
		return update_option( $option_name, $value );
	}

	/**
	 * Delete an option.
	 *
	 * @param string $key The option key.
	 * @return bool True on success, false on failure.
	 */
	public static function delete( $key ) {
		$option_name = self::OPTION_PREFIX . $key;
		return delete_option( $option_name );
	}

	/**
	 * Get all options.
	 *
	 * @return array All options with their values.
	 */
	public static function get_all() {
		$options = array();
		foreach ( array_keys( self::$defaults ) as $key ) {
			$options[ $key ] = self::get( $key );
		}
		return $options;
	}

	/**
	 * Get allowed intervals.
	 *
	 * @return array Allowed intervals with their labels.
	 */
	public static function get_allowed_intervals() {
		return self::$allowed_intervals;
	}

	/**
	 * Validate an interval.
	 *
	 * @param string $interval The interval to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_interval( $interval ) {
		return array_key_exists( $interval, self::$allowed_intervals );
	}

	/**
	 * Get default value for an option.
	 *
	 * @param string $key The option key.
	 * @return mixed|null The default value or null if not found.
	 */
	public static function get_default( $key ) {
		return isset( self::$defaults[ $key ] ) ? self::$defaults[ $key ] : null;
	}

	/**
	 * Initialize default options.
	 *
	 * @return void
	 */
	public static function init_defaults() {
		foreach ( self::$defaults as $key => $value ) {
			$option_name   = self::OPTION_PREFIX . $key;
			$current_value = get_option( $option_name );

			// If option doesn't exist, create it.
			if ( false === $current_value ) {
				add_option( $option_name, $value );
			} elseif ( 'sync_interval' === $key && ! self::is_valid_interval( $current_value ) ) {
				// If it is the sync interval and it is not in allowed values, fix it.
				update_option( $option_name, $value );
			}
		}
	}
}
