<?php
/**
 * Feed Favorites AJAX Management Class
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized AJAX request management.
 */
class Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_feed_favorites_sync', array( $this, 'handle_sync' ) );
		add_action( 'wp_ajax_feed_favorites_manual_sync', array( $this, 'handle_sync' ) );
		add_action( 'wp_ajax_feed_favorites_test_url', array( $this, 'handle_test_url' ) );
		add_action( 'wp_ajax_feed_favorites_test_feed', array( $this, 'handle_test_feed' ) );
		add_action( 'wp_ajax_feed_favorites_preview', array( $this, 'handle_preview' ) );
		add_action( 'wp_ajax_feed_favorites_reset_stats', array( $this, 'handle_reset_stats' ) );
	}

	/**
	 * Common security verification.
	 *
	 * @param string $action The action name for nonce verification.
	 * @return void
	 */
	private function verify_request( $action = 'feed_favorites_sync' ) {
		// Nonce & referer verification.
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_die( esc_html__( 'Missing nonce', 'feed-favorites' ) );
		}
		check_ajax_referer( $action, 'nonce' );

		// Check permissions.
		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'feed-favorites' ) );
		}

		// Rate limiting check.
		if ( ! $this->check_rate_limit() ) {
			wp_die( esc_html__( 'Rate limit exceeded. Please wait before trying again.', 'feed-favorites' ) );
		}
	}

	/**
	 * Rate limiting implementation.
	 *
	 * @return bool True if within rate limit, false otherwise.
	 */
	private function check_rate_limit() {
		$user_id = get_current_user_id();
		$action  = current_action();
		$key     = "feed_favorites_rate_limit_{$user_id}_{$action}";

		// Allow 5 requests per minute per user per action.
		$limit  = 5;
		$window = 60; // seconds.

		$current_count = get_transient( $key );

		if ( false === $current_count ) {
			set_transient( $key, 1, $window );
			return true;
		}

		if ( $current_count >= $limit ) {
			return false;
		}

		set_transient( $key, $current_count + 1, $window );
		return true;
	}

	/**
	 * Handle AJAX synchronization.
	 *
	 * @return void
	 */
	public function handle_sync() {
		$this->verify_request( 'feed_favorites_sync' );

		$sync   = new Sync();
		$result = $sync->manual_sync();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => esc_html( $result->get_error_message() ) ) );
		} else {
			wp_send_json_success( array( 'message' => esc_html( $result ) ) );
		}
	}

	/**
	 * Handle AJAX URL test.
	 *
	 * @return void
	 */
	public function handle_test_url() {
		$this->verify_request( 'feed_favorites_test_url' );

		// Sanitize & validate URL.
		$url_raw = (string) filter_input( INPUT_POST, 'url', FILTER_UNSAFE_RAW ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$url     = esc_url_raw( wp_unslash( $url_raw ) );
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid URL', 'feed-favorites' ) ) );
		}

		$result = Http::test_feed_url( $url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => esc_html( $result->get_error_message() ) ) );
		} else {
			wp_send_json_success( array( 'message' => esc_html( $result ) ) );
		}
	}

	/**
	 * Handle AJAX feed test (uses configured feed URL).
	 *
	 * @return void
	 */
	public function handle_test_feed() {
		$this->verify_request( 'feed_favorites_test_feed' );

		$feed_url = Config::get( 'feed_url' );
		if ( empty( $feed_url ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Feed URL not configured', 'feed-favorites' ) ) );
		}

		$result = Http::test_feed_url( $feed_url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => esc_html( $result->get_error_message() ) ) );
		} else {
			wp_send_json_success( array( 'message' => esc_html( $result ) ) );
		}
	}

	/**
	 * Handle AJAX preview.
	 *
	 * @return void
	 */
	public function handle_preview() {
		$this->verify_request( 'feed_favorites_preview' );

		// Sanitize & validate URL.
		$url_raw = (string) filter_input( INPUT_POST, 'url', FILTER_UNSAFE_RAW ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$url     = esc_url_raw( wp_unslash( $url_raw ) );
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid URL', 'feed-favorites' ) ) );
		}

		$preview = $this->get_feed_preview( $url );

		if ( is_wp_error( $preview ) ) {
			wp_send_json_error( array( 'message' => esc_html( $preview->get_error_message() ) ) );
		} else {
			wp_send_json_success( array( 'html' => wp_kses_post( $preview ) ) );
		}
	}

	/**
	 * Generate feed data preview.
	 *
	 * @param string $url The feed URL to preview.
	 * @return string|WP_Error HTML preview or error.
	 */
	private function get_feed_preview( $url ) {
		$body = Http::test_url( $url );

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$parsed = Http::parse_feed_document( $body );

		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		$items       = $parsed['items'];
		$total_count = count( $items );

		$html        = '<div class="rss-preview-items">';
		$count       = 0;
		$max_preview = 3;

		foreach ( $items as $item ) {
			if ( $count >= $max_preview ) {
				break;
			}

			if ( 'rss' === $parsed['type'] ) {
				$title     = sanitize_text_field( (string) $item->title );
				$author    = sanitize_text_field( (string) $item->author );
				$published = sanitize_text_field( (string) $item->pubDate ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			} else {
				$ns        = Http::ATOM_NS;
				$e         = $item->children( $ns );
				$title     = sanitize_text_field( (string) $e->title );
				$author    = isset( $e->author->name ) ? sanitize_text_field( (string) $e->author->name ) : '';
				$published = isset( $e->updated ) ? sanitize_text_field( (string) $e->updated ) : '';
				if ( '' === $published && isset( $e->published ) ) {
					$published = sanitize_text_field( (string) $e->published );
				}
			}

			$html .= '<div class="rss-preview-item">';
			$html .= '<div class="rss-preview-title">' . esc_html( $title ) . '</div>';
			$html .= '<div class="rss-preview-meta">';
			if ( $author ) {
				$html .= sprintf( /* translators: %s: author name */ esc_html__( 'By %s', 'feed-favorites' ), esc_html( $author ) ) . ' • ';
			}
			if ( $published ) {
				$ts = strtotime( $published );
				$html .= sprintf(
					/* translators: %s: date */
					esc_html__( 'Published on %s', 'feed-favorites' ),
					esc_html( false !== $ts ? gmdate( get_option( 'date_format' ), $ts ) : $published )
				);
			}
			$html .= '</div>';
			$html .= '</div>';

			++$count;
		}

		if ( $total_count > $max_preview ) {
			$html .= '<div class="rss-preview-more">... and ' . ( $total_count - $max_preview ) . ' more article(s)</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Handle AJAX statistics reset.
	 *
	 * @return void
	 */
	public function handle_reset_stats() {
		$this->verify_request( 'feed_favorites_reset_stats' );

		// Sanitize input data.
		$reset_type = isset( $_POST['reset_type'] ) ? sanitize_text_field( wp_unslash( $_POST['reset_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$logger = new Logger();
		$result = $logger->reset_stats( $reset_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => esc_html( $result->get_error_message() ) ) );
		} else {
			wp_send_json_success( array( 'message' => esc_html( $result ) ) );
		}
	}
}
