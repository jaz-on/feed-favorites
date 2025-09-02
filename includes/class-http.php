<?php
/**
 * Feed Favorites HTTP Client Class
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized HTTP request management.
 */
class Http {

	/**
	 * Plugin user agent.
	 *
	 * @var string
	 */
	private static $user_agent = 'FeedFavorites/' . FEED_FAVORITES_VERSION;

	/**
	 * Default headers.
	 *
	 * @var array
	 */
	private static $default_headers = array(
		'Accept' => 'application/atom+xml, application/xml, text/xml, */*',
	);

	/**
	 * Fetch a feed.
	 *
	 * @param string $url The URL to fetch.
	 * @param int    $timeout The timeout in seconds.
	 * @return array|WP_Error Response array or error.
	 */
	public static function fetch_feed( $url, $timeout = 15 ) {
		$args = array(
			'timeout'             => $timeout,
			'user-agent'          => self::$user_agent,
			'headers'             => self::$default_headers,
			'limit_response_size' => 1024 * 1024 * 2, // 2MB
		);

		return wp_remote_get( $url, $args );
	}

	/**
	 * Test URL connectivity.
	 *
	 * @param string $url The URL to test.
	 * @param int    $timeout The timeout in seconds.
	 * @return string|WP_Error Response body or error.
	 */
	public static function test_url( $url, $timeout = 15 ) {
		$response = self::fetch_feed( $url, $timeout );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_failed', __( 'Unable to connect to feed. Check the URL and your internet connection.', 'feed-favorites' ) );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			/* translators: %d: HTTP status code */
			return new WP_Error( 'http_error', sprintf( __( 'HTTP error %d. Feed is not accessible.', 'feed-favorites' ), $status_code ) );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return new WP_Error( 'empty_feed', __( 'Feed is empty.', 'feed-favorites' ) );
		}

		return $body;
	}

	/**
	 * Validate XML feed.
	 *
	 * @param string $body The XML body to validate.
	 * @return SimpleXMLElement|WP_Error Valid XML object or error.
	 */
	public static function validate_xml( $body ) {
		// Validate XML format (block external entity loader / network).
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_clear_errors();

		if ( ! $xml ) {
			return new WP_Error( 'invalid_xml', __( 'Content is not valid XML.', 'feed-favorites' ) );
		}

		// Check RSS structure.
		if ( ! isset( $xml->channel->item ) ) {
			return new WP_Error( 'invalid_rss', __( 'Format is not a valid RSS feed.', 'feed-favorites' ) );
		}

		return $xml;
	}

	/**
	 * Complete feed URL test.
	 *
	 * @param string $url The feed URL to test.
	 * @return string|WP_Error Success message or error.
	 */
	public static function test_feed_url( $url ) {
		$body = self::test_url( $url );

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$xml = self::validate_xml( $body );

		if ( is_wp_error( $xml ) ) {
			return $xml;
		}

		$entry_count = count( $xml->channel->item );

		if ( 0 === $entry_count ) {
			return __( 'Valid feed but empty. No starred articles found.', 'feed-favorites' );
		} else {
			/* translators: %d: Number of articles found */
			return sprintf( __( 'Valid RSS feed! %d starred article(s) found.', 'feed-favorites' ), $entry_count );
		}
	}
}
