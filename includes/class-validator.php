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
		'feed_url'      => array( 'required', 'url', 'feed_format' ), // Changed from feedbin_format.
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
					$logger = new \FeedFavorites\Logger();
					$logger->log( 'WARNING', "Non-HTTPS URL detected: {$url}" );
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
		return Config::is_valid_interval( $value ) ? $value : 7200;
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
				$result = self::validate_field( $field, $data[ $field ] );
				if ( is_wp_error( $result ) ) {
					$errors[ $field ] = $result->get_error_message();
				} else {
					$validated[ $field ] = $result;
				}
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
}
