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
	 * Atom namespace URI.
	 *
	 * @var string
	 */
	const ATOM_NS = 'http://www.w3.org/2005/Atom';

	/**
	 * Parse feed body into a list of native RSS items or Atom entries.
	 *
	 * @param string $body The XML body.
	 * @return array{type: string, items: SimpleXMLElement[]}|WP_Error
	 */
	public static function parse_feed_document( $body ) {
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_clear_errors();

		if ( ! $xml ) {
			return new WP_Error( 'invalid_xml', __( 'Content is not valid XML.', 'feed-favorites' ) );
		}

		if ( isset( $xml->channel->item ) ) {
			$items = array();
			foreach ( $xml->channel->item as $item ) {
				$items[] = $item;
			}
			return array(
				'type'  => 'rss',
				'items' => $items,
			);
		}

		if ( 'feed' === $xml->getName() ) {
			$atom  = $xml->children( self::ATOM_NS );
			$items = array();
			if ( isset( $atom->entry ) ) {
				foreach ( $atom->entry as $entry ) {
					$items[] = $entry;
				}
			}
			return array(
				'type'  => 'atom',
				'items' => $items,
			);
		}

		return new WP_Error( 'invalid_feed', __( 'Format is not a recognized RSS or Atom feed.', 'feed-favorites' ) );
	}

	/**
	 * Validate XML feed (RSS or Atom).
	 *
	 * @param string $body The XML body to validate.
	 * @return SimpleXMLElement|WP_Error Legacy RSS root element for backward compatibility, or error.
	 */
	public static function validate_xml( $body ) {
		$parsed = self::parse_feed_document( $body );

		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		if ( 'rss' !== $parsed['type'] ) {
			return new WP_Error( 'invalid_rss', __( 'Format is not a valid RSS feed.', 'feed-favorites' ) );
		}

		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_clear_errors();

		return $xml ? $xml : new WP_Error( 'invalid_xml', __( 'Content is not valid XML.', 'feed-favorites' ) );
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

		$parsed = self::parse_feed_document( $body );

		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		$entry_count = count( $parsed['items'] );

		if ( 0 === $entry_count ) {
			return __( 'Valid feed but empty. No starred articles found.', 'feed-favorites' );
		} else {
			return sprintf(
				/* translators: 1: Feed format label (RSS or Atom), 2: Number of entries */
				__( 'Valid %1$s feed! %2$d starred article(s) found.', 'feed-favorites' ),
				'rss' === $parsed['type'] ? __( 'RSS', 'feed-favorites' ) : __( 'Atom', 'feed-favorites' ),
				$entry_count
			);
		}
	}
}
