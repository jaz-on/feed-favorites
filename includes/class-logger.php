<?php
/**
 * Feed Favorites Logging Class
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log and statistics management
 */
class Logger {

	/**
	 * Maximum number of logs to keep
	 */
	const MAX_LOGS = 100;

	/**
	 * Constructor
	 */
	public function __construct() {
		// No initialization needed
	}

	/**
	 * Error log
	 */
	public function log_error( $message ) {
		$this->log( 'ERROR', $message );
	}

	/**
	 * Success log
	 */
	public function log_success( $message ) {
		$this->log( 'SUCCESS', $message );
	}

	/**
	 * Information log
	 */
	public function log_info( $message ) {
		$this->log( 'INFO', $message );
	}

	/**
	 * Main logging system
	 */
	public function log( $level, $message ) {
		$logs = get_option( 'feed_favorites_logs', array() );

		$log_entry = array(
			'timestamp' => time(), // Unix timestamp for compatibility
			'level'     => sanitize_text_field( $level ),
			'message'   => sanitize_text_field( $message ),
		);

		// Add log at beginning of array
		array_unshift( $logs, $log_entry );

		// Limit number of logs
		$logs = array_slice( $logs, 0, self::MAX_LOGS );

		update_option( 'feed_favorites_logs', $logs );
	}

	/**
	 * Get statistics
	 */
	public function get_stats() {
		$stats = array(
			'total_posts' => $this->get_total_posts(),
			'last_sync'   => get_option( 'feed_favorites_last_sync' ),
			'sync_count'  => get_option( 'feed_favorites_sync_count', 0 ),
			'error_count' => get_option( 'feed_favorites_error_count', 0 ),
		);

		return $stats;
	}

	/**
	 * Get total number of posts
	 */
	private function get_total_posts() {
		$count = wp_count_posts( 'favorite' );
		if ( is_object( $count ) && property_exists( $count, 'publish' ) ) {
			return $count->publish;
		} else {
			return 0;
		}
	}

	/**
	 * Get recent logs
	 */
	public function get_recent_logs( $limit = 10 ) {
		$logs = get_option( 'feed_favorites_logs', array() );
		return array_slice( $logs, 0, $limit );
	}

	/**
	 * Clean up old logs
	 */
	public function cleanup_old_logs() {
		$logs = get_option( 'feed_favorites_logs', array() );

		if ( count( $logs ) > self::MAX_LOGS ) {
			$logs = array_slice( $logs, 0, self::MAX_LOGS );
			update_option( 'feed_favorites_logs', $logs );
		}
	}

	/**
	 * Delete all logs
	 */
	public function clear_logs() {
		delete_option( 'feed_favorites_logs' );
	}

	/**
	 * Export logs
	 */
	public function export_logs() {
		$logs = get_option( 'feed_favorites_logs', array() );

		$export = array(
			'export_date'    => current_time( 'mysql' ),
			'plugin_version' => FEED_FAVORITES_VERSION,
			'logs'           => $logs,
		);

		return $export;
	}

	/**
	 * Format timestamp for display
	 */
	public function format_timestamp( $timestamp ) {
		if ( is_string( $timestamp ) ) {
			return $timestamp;
		} elseif ( is_numeric( $timestamp ) ) {
			return date( 'Y-m-d H:i:s', $timestamp );
		} else {
			return 'Invalid timestamp';
		}
	}

	/**
	 * Reset statistics and/or logs
	 */
	public function reset_stats( $reset_type = 'all' ) {
		switch ( $reset_type ) {
			case 'logs':
				$this->clear_logs();
				return __( 'Logs reset successfully.', 'feed-favorites' );

			case 'stats':
				$this->reset_statistics();
				return __( 'Statistics reset successfully.', 'feed-favorites' );

			case 'system_notice':
				delete_option( 'feed_favorites_system_check_shown' );
				return __( 'System notice reset successfully. It will show again on next page load.', 'feed-favorites' );

			case 'all':
				$this->clear_logs();
				$this->reset_statistics();
				delete_option( 'feed_favorites_system_check_shown' );
				return __( 'Logs, statistics, and system notice reset successfully.', 'feed-favorites' );

			default:
				return new WP_Error( 'invalid_reset_type', __( 'Invalid reset type.', 'feed-favorites' ) );
		}
	}

	/**
	 * Reset statistics
	 */
	private function reset_statistics() {
		// Reset counters
		update_option( 'feed_favorites_sync_count', 0 );
		update_option( 'feed_favorites_error_count', 0 );
		update_option( 'feed_favorites_last_sync', '' );

		// Note: We don't delete existing posts, only statistics
		// If user wants to delete posts, they can do it manually
	}
}
