<?php
/**
 * Feed Favorites Validation Class
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized validation management.
 */
class Validator {

	/**
	 * Validation rules.
	 *
	 * @var array
	 */
	private static $rules = array(
		'feed_url'      => array( 'required', 'url', 'feed_format' ),
		'auto_sync'     => array( 'boolean' ),
		'sync_interval' => array( 'required', 'integer', 'valid_interval' ),
		'max_items'     => array( 'integer', 'min:0', 'max:200' ),
	);

	/**
	 * Validate feed URL.
	 *
	 * @param string $url The URL to validate.
	 * @return string|WP_Error Validated URL or error.
	 */
	public static function validate_feed_url( $url ) {
		if ( empty( $url ) ) {
			return new WP_Error( 'empty_url', __( 'Feed URL cannot be empty.', 'feed-favorites' ) );
		}

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', __( 'Feed URL is not valid.', 'feed-favorites' ) );
		}

		if ( ! self::is_valid_feed_url( $url ) ) {
			return new WP_Error( 'invalid_feed_format', __( 'Invalid RSS feed URL format. Please provide a valid RSS feed URL.', 'feed-favorites' ) );
		}

		return $url;
	}

	/**
	 * Validate RSS feed URL format (generic).
	 *
	 * @param string $url The URL to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid_feed_url( $url ) {
		$parsed = wp_parse_url( $url );

		if ( ! $parsed || ! isset( $parsed['host'] ) || ! isset( $parsed['path'] ) ) {
			return false;
		}

		// Enhanced security checks.
		if ( isset( $parsed['scheme'] ) ) {
			// Only allow HTTPS and HTTP (with warning).
			if ( ! in_array( $parsed['scheme'], array( 'https', 'http' ), true ) ) {
				return false;
			}

			// Prefer HTTPS for security.
			if ( 'https' !== $parsed['scheme'] ) {
				// Log warning for non-HTTPS URLs using WordPress logging.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// Use WordPress error logging instead of custom logger to avoid dependency issues.
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					// error_log( "Feed Favorites: Non-HTTPS URL detected: {$url}" );
				}
			}
		}

		// Block potentially dangerous URLs.
		$blocked_patterns = array(
			'javascript:',
			'data:',
			'file:',
			'ftp:',
			'localhost',
			'127.0.0.1',
			'::1',
			'0.0.0.0',
		);

		foreach ( $blocked_patterns as $pattern ) {
			if ( false !== stripos( $url, $pattern ) ) {
				return false;
			}
		}

		// Check that it's a valid RSS feed URL.
		// Accept common RSS feed patterns.
		$valid_patterns = array(
			'/feed/',
			'/rss/',
			'/atom/',
			'/starred/',
			'/favorites/',
			'/bookmarks/',
			'.xml',
			'.rss',
			'.atom',
		);

		$path              = $parsed['path'];
		$has_valid_pattern = false;

		foreach ( $valid_patterns as $pattern ) {
			if ( false !== strpos( $path, $pattern ) ) {
				$has_valid_pattern = true;
				break;
			}
		}

		if ( ! $has_valid_pattern ) {
			return false;
		}

		// Additional security: check for reasonable URL length.
		if ( strlen( $url ) > 500 ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate automatic synchronization.
	 *
	 * @param mixed $value The value to validate.
	 * @return int Validated value (0 or 1).
	 */
	public static function validate_auto_sync( $value ) {
		return in_array( $value, array( 0, 1 ), true ) ? $value : 0;
	}

	/**
	 * Validate synchronization interval.
	 *
	 * @param mixed $value The value to validate.
	 * @return int Validated interval value.
	 */
	public static function validate_sync_interval( $value ) {
		$value = intval( $value );

		// Check if Config class exists and has the required method.
		if ( class_exists( 'Config' ) && method_exists( 'Config', 'is_valid_interval' ) ) {
			return Config::is_valid_interval( $value ) ? $value : 7200;
		}

		// Fallback validation if Config class is not available.
		$allowed_intervals = array( 900, 1800, 3600, 7200, 14400, 86400 );
		return in_array( $value, $allowed_intervals, true ) ? $value : 7200;
	}

	/**
	 * Validate maximum number of items.
	 *
	 * @param mixed $value The value to validate.
	 * @return int Validated max items value.
	 */
	public static function validate_max_items( $value ) {
		if ( empty( $value ) ) {
			return 50;
		}

		$value = intval( $value );

		if ( $value < 0 ) {
			return 50;
		}

		if ( $value > 200 ) {
			return 200;
		}

		return $value;
	}

	/**
	 * Complete validation of a data set.
	 *
	 * @param array $data The data to validate.
	 * @return array|WP_Error Validated data or error.
	 */
	public static function validate_data( $data ) {
		$errors    = array();
		$validated = array();

		foreach ( self::$rules as $field => $rules ) {
			if ( isset( $data[ $field ] ) ) {
				$value = $data[ $field ];

				// Apply each rule to the value.
				foreach ( $rules as $rule ) {
					$result = self::validate_rule( $value, $rule );
					if ( is_wp_error( $result ) ) {
						$errors[ $field ] = $result->get_error_message();
						break; // Stop validation for this field on first error.
					}
				}

				// If no errors, add to validated data.
				if ( ! isset( $errors[ $field ] ) ) {
					$validated[ $field ] = $value;
				}
			} elseif ( in_array( 'required', $rules, true ) ) {
				// Field is required but not provided.
				$errors[ $field ] = __( 'This field is required.', 'feed-favorites' );
			}
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'validation_failed', __( 'Validation failed', 'feed-favorites' ), $errors );
		}

		return $validated;
	}

	/**
	 * Validate a specific field.
	 *
	 * @param string $field The field name.
	 * @param mixed  $value The value to validate.
	 * @return mixed|WP_Error Validated value or error.
	 */
	private static function validate_field( $field, $value ) {
		switch ( $field ) {
			case 'feed_url':
				return self::validate_feed_url( $value );
			case 'auto_sync':
				return self::validate_auto_sync( $value );
			case 'sync_interval':
				return self::validate_sync_interval( $value );
			case 'max_items':
				return self::validate_max_items( $value );
			default:
				return $value;
		}
	}

	/**
	 * Validate a value against a specific rule.
	 *
	 * @param mixed  $value The value to validate.
	 * @param string $rule  The rule to apply.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private static function validate_rule( $value, $rule ) {
		switch ( $rule ) {
			case 'required':
				return ! empty( $value ) ? true : new WP_Error( 'required_field', __( 'This field is required.', 'feed-favorites' ) );

			case 'url':
				return filter_var( $value, FILTER_VALIDATE_URL ) ? true : new WP_Error( 'invalid_url', __( 'Invalid URL format.', 'feed-favorites' ) );

			case 'integer':
				return is_numeric( $value ) && (int) $value === (int) $value ? true : new WP_Error( 'invalid_integer', __( 'Value must be an integer.', 'feed-favorites' ) );

			case 'boolean':
				return in_array( $value, array( 0, 1, '0', '1', true, false ), true ) ? true : new WP_Error( 'invalid_boolean', __( 'Value must be a boolean.', 'feed-favorites' ) );

			case 'feed_format':
				return self::is_valid_feed_url( $value ) ? true : new WP_Error( 'invalid_feed_format', __( 'Invalid RSS feed URL format.', 'feed-favorites' ) );

			case 'valid_interval':
				$allowed_intervals = array( 900, 1800, 3600, 7200, 14400, 86400 );
				return in_array( (int) $value, $allowed_intervals, true ) ? true : new WP_Error( 'invalid_interval', __( 'Invalid synchronization interval.', 'feed-favorites' ) );

			default:
				// Handle min:value and max:value rules.
				if ( strpos( $rule, 'min:' ) === 0 ) {
					$min = (int) substr( $rule, 4 );
					/* translators: %d: Minimum value */
					return (int) $value >= $min ? true : new WP_Error( 'min_value', sprintf( __( 'Value must be at least %d.', 'feed-favorites' ), $min ) );
				}

				if ( strpos( $rule, 'max:' ) === 0 ) {
					$max = (int) substr( $rule, 4 );
					/* translators: %d: Maximum value */
					return (int) $value <= $max ? true : new WP_Error( 'max_value', sprintf( __( 'Value must be at most %d.', 'feed-favorites' ), $max ) );
				}

				return true; // Unknown rule; assume valid.
		}
	}
}
