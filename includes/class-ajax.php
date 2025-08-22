<?php
/**
 * Feed Favorites AJAX Management Class
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized AJAX request management
 */
class Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_feed_favorites_sync', array( $this, 'handle_sync' ) );
		add_action( 'wp_ajax_feed_favorites_test_url', array( $this, 'handle_test_url' ) );
		add_action( 'wp_ajax_feed_favorites_preview', array( $this, 'handle_preview' ) );
		add_action( 'wp_ajax_feed_favorites_reset_stats', array( $this, 'handle_reset_stats' ) );
	}

	/**
	 * Common security verification
	 */
	private function verify_request( $action = 'feed_favorites_sync' ) {
		// Nonce & referer verification
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_die( esc_html__( 'Missing nonce', 'feed-favorites' ) );
		}
		check_ajax_referer( $action, 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'feed-favorites' ) );
		}

		// Rate limiting check
		if ( ! $this->check_rate_limit() ) {
			wp_die( esc_html__( 'Rate limit exceeded. Please wait before trying again.', 'feed-favorites' ) );
		}
	}

	/**
	 * Rate limiting implementation
	 */
	private function check_rate_limit() {
		$user_id = get_current_user_id();
		$action  = current_action();
		$key     = "feed_favorites_rate_limit_{$user_id}_{$action}";

		// Allow 5 requests per minute per user per action
		$limit  = 5;
		$window = 60; // seconds

		$current_count = get_transient( $key );

		if ( $current_count === false ) {
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
	 * Handle AJAX synchronization
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
	 * Handle AJAX URL test
	 */
	public function handle_test_url() {
		$this->verify_request( 'feed_favorites_test_url' );

		// Sanitize input data
		$url = isset( $_POST['url'] ) ? sanitize_url( $_POST['url'] ) : '';

		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Empty URL', 'feed-favorites' ) ) );
		}

		$result = Http::test_feed_url( $url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => esc_html( $result->get_error_message() ) ) );
		} else {
			wp_send_json_success( array( 'message' => esc_html( $result ) ) );
		}
	}

	/**
	 * Handle AJAX preview
	 */
	public function handle_preview() {
		$this->verify_request( 'feed_favorites_preview' );

		// Sanitize input data
		$url = isset( $_POST['url'] ) ? sanitize_url( $_POST['url'] ) : '';

		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Empty URL', 'feed-favorites' ) ) );
		}

		$preview = $this->get_feed_preview( $url );

		if ( is_wp_error( $preview ) ) {
			wp_send_json_error( array( 'message' => esc_html( $preview->get_error_message() ) ) );
		} else {
			wp_send_json_success( array( 'html' => wp_kses_post( $preview ) ) );
		}
	}

	/**
	 * Generate feed data preview
	 */
	private function get_feed_preview( $url ) {
		$body = Http::test_url( $url );

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$xml = Http::validate_xml( $body );

		if ( is_wp_error( $xml ) ) {
			return $xml;
		}

		$items       = $xml->channel->item;
		$total_count = count( $xml->channel->item );

		// Generate HTML preview
		$html        = '<div class="rss-preview-items">';
		$count       = 0;
		$max_preview = 3;

		foreach ( $items as $item ) {
			if ( $count >= $max_preview ) {
				break;
			}

			$title     = sanitize_text_field( (string) $item->title );
			$author    = sanitize_text_field( (string) $item->author );
			$published = sanitize_text_field( (string) $item->pubDate );

			$html .= '<div class="rss-preview-item">';
			$html .= '<div class="rss-preview-title">' . esc_html( $title ) . '</div>';
			$html .= '<div class="rss-preview-meta">';
			if ( $author ) {
				$html .= 'By ' . esc_html( $author ) . ' • ';
			}
			if ( $published ) {
				$html .= 'Published on ' . date( 'd/m/Y', strtotime( $published ) );
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
	 * Handle AJAX statistics reset
	 */
	public function handle_reset_stats() {
		$this->verify_request( 'feed_favorites_reset_stats' );

		// Sanitize input data
		$reset_type = isset( $_POST['reset_type'] ) ? sanitize_text_field( $_POST['reset_type'] ) : '';

		$logger = new Logger();
		$result = $logger->reset_stats( $reset_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => esc_html( $result->get_error_message() ) ) );
		} else {
			wp_send_json_success( array( 'message' => esc_html( $result ) ) );
		}
	}
}
