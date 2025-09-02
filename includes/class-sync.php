<?php
/**
 * Feed Favorites Synchronization Class
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data synchronization management.
 */
class Sync {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );
	}

	/**
	 * Manual synchronization.
	 *
	 * @return string|WP_Error Success message or error.
	 */
	public function manual_sync() {
		// Security check - verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'insufficient_permissions', __( 'Insufficient permissions to perform manual synchronization', 'feed-favorites' ) );
		}

		// Simple transient lock to avoid concurrent runs.
		$lock_key = 'feed_favorites_sync_lock';
		if ( get_transient( $lock_key ) ) {
			return new WP_Error( 'sync_locked', __( 'A synchronization is already in progress. Please try again later.', 'feed-favorites' ) );
		}
		set_transient( $lock_key, 1, 5 * 60 ); // 5 minutes.

		$feed_url = Config::get( 'feed_url' );

		if ( empty( $feed_url ) ) {
			delete_transient( $lock_key );
			return new WP_Error( 'no_feed_url', __( 'Feed URL not configured', 'feed-favorites' ) );
		}

		$result = $this->sync_feed( $feed_url );

		if ( is_wp_error( $result ) ) {
			$this->log_error( 'Manual synchronization failed: ' . $result->get_error_message() );
			$this->update_stats( false );
			delete_transient( $lock_key );
			return $result;
		} else {
			$this->log_success( 'Manual synchronization successful: ' . $result . ' items processed' );
			$this->update_stats( true, $result );
			delete_transient( $lock_key );
			/* translators: %d: Number of items processed */
			return sprintf( __( 'Synchronization successful: %d items processed', 'feed-favorites' ), $result );
		}
	}

	/**
	 * Automatic synchronization via cron.
	 *
	 * @return void
	 */
	public function automatic_sync() {
		$feed_url = Config::get( 'feed_url' );

		if ( empty( $feed_url ) ) {
			$this->log_error( 'Automatic synchronization failed: Feed URL not configured' );
			return;
		}

		$result = $this->sync_feed( $feed_url );

		if ( is_wp_error( $result ) ) {
			$this->log_error( 'Automatic synchronization failed: ' . $result->get_error_message() );
			$this->update_stats( false );
		} else {
			$this->log_success( 'Automatic synchronization successful: ' . $result . ' items processed' );
			$this->update_stats( true, $result );
		}
	}

	/**
	 * Feed synchronization.
	 *
	 * @param string $feed_url The feed URL to sync.
	 * @return int|WP_Error Number of items processed or error.
	 */
	private function sync_feed( $feed_url ) {
		// Get feed content.
		$response = Http::fetch_feed( $feed_url, 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return new WP_Error( 'empty_feed', __( 'Feed is empty', 'feed-favorites' ) );
		}

		// Parse XML with validation.
		$xml = Http::validate_xml( $body );

		if ( is_wp_error( $xml ) ) {
			return $xml;
		}

		$count     = 0;
		$max_items = intval( Config::get( 'max_items', 50 ) );

		$items = $xml->channel->item;

		// Process entries with limitation.
		foreach ( $items as $item ) {
			// If max_items = 0, process all items.
			// Otherwise, respect limitation.
			if ( $max_items > 0 && $count >= $max_items ) {
				break;
			}

			$result = $this->process_entry( $item );
			if ( $result ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Process a feed entry.
	 *
	 * @param SimpleXMLElement $entry The RSS entry to process.
	 * @return bool True if processed successfully, false otherwise.
	 */
	private function process_entry( $entry ) {
		// Extract and validate data.
		$data = $this->extract_entry_data( $entry );

		if ( is_wp_error( $data ) ) {
			return false;
		}

		// Check if article already exists.
		if ( $this->entry_exists( $data['link'] ) ) {
			return false;
		}

		// Create post.
		$post_id = $this->create_post( $data );

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Update ACF fields.
		$this->update_acf_fields( $post_id, $data );

		return true;
	}

	/**
	 * Extract RSS entry data.
	 *
	 * @param SimpleXMLElement $item The RSS item element.
	 * @return array|WP_Error Extracted data or error.
	 */
	private function extract_entry_data( $item ) {
		$data = array(
			'title'        => sanitize_text_field( (string) $item->title ),
			'link'         => esc_url_raw( (string) $item->link ),
			'content'      => wp_kses_post( (string) $item->description ),
			/* phpcs:ignore WordPress.NamingConventions.ValidVariableName */
			'published'    => sanitize_text_field( (string) $item->pubDate ),
			'author'       => sanitize_text_field( (string) $item->author ),
			'source_title' => sanitize_text_field( (string) $item->source ),
			'source_url'   => esc_url_raw( (string) $item->link ), // RSS doesn't have separate source.
		);

		// Validate required data.
		if ( empty( $data['title'] ) || empty( $data['link'] ) ) {
			return new WP_Error( 'invalid_entry', __( 'Invalid entry data', 'feed-favorites' ) );
		}

		return $data;
	}

	/**
	 * Check if entry exists.
	 *
	 * @param string $link The link to check.
	 * @return bool True if entry exists, false otherwise.
	 */
	private function entry_exists( $link ) {
		$existing_post = get_posts(
			array(
				'post_type'              => 'favorite',
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Optimized with limits and cache flags
					array(
						'key'     => 'feed_link',
						'value'   => $link,
						'compare' => '=',
					),
				),
				'posts_per_page'         => 1,
				'post_status'            => 'any',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return ! empty( $existing_post );
	}

	/**
	 * Create a post.
	 *
	 * @param array $data The post data.
	 * @return int|WP_Error The post ID or error.
	 */
	private function create_post( $data ) {
		$status        = Config::get( 'auto_sync', '1' ) ? 'publish' : 'draft';
		$published_gmt = gmdate( 'Y-m-d H:i:s', strtotime( $data['published'] ) );

		$post_data = array(
			'post_title'    => $data['title'],
			'post_content'  => $data['content'],
			'post_status'   => $status,
			'post_type'     => 'favorite',
			'post_date_gmt' => $published_gmt,
			'post_author'   => get_current_user_id(),
		);

		return wp_insert_post( $post_data, true );
	}

	/**
	 * Update ACF fields.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $data The data to update.
	 * @return void
	 */
	private function update_acf_fields( $post_id, $data ) {
		// Always persist feed_link as a native post meta for duplicate detection.
		update_post_meta( $post_id, 'feed_link', $data['link'] );

		if ( ! function_exists( 'update_field' ) ) {
			return;
		}

		$fields = array(
			'feed_link'           => $data['link'],
			'feed_author'         => $data['author'],
			'feed_source_title'   => $data['source_title'],
			'feed_source_url'     => $data['source_url'],
			'feed_published_date' => $data['published'],
		);

		foreach ( $fields as $field => $value ) {
			update_field( $field, $value, $post_id );
		}
	}

	/**
	 * Add custom cron intervals.
	 *
	 * @param array $schedules The existing cron schedules.
	 * @return array Modified schedules.
	 */
	public function add_cron_intervals( $schedules ) {
		$interval = intval( Config::get( 'sync_interval', 3600 ) );

		$schedules['feed_favorites_interval'] = array(
			'interval' => $interval,
			/* translators: %d: Number of seconds */
			'display'  => sprintf( __( 'Every %d seconds', 'feed-favorites' ), $interval ),
		);

		return $schedules;
	}

	/**
	 * Log error.
	 *
	 * @param string $message The error message.
	 * @return void
	 */
	private function log_error( $message ) {
		// Create logger instance when needed.
		$logger = new Logger();
		$logger->log( 'ERROR', $message );
	}

	/**
	 * Log success.
	 *
	 * @param string $message The success message.
	 * @return void
	 */
	private function log_success( $message ) {
		// Create logger instance when needed.
		$logger = new Logger();
		$logger->log( 'SUCCESS', $message );
	}

	/**
	 * Update statistics.
	 *
	 * @param bool $success True for success, false for failure.
	 * @return void
	 */
	private function update_stats( $success = true ) {
		Config::set( 'last_sync', current_time( 'mysql' ) );

		if ( $success ) {
			$sync_count = get_option( 'feed_favorites_sync_count', 0 );
			update_option( 'feed_favorites_sync_count', $sync_count + 1 );
		} else {
			$error_count = get_option( 'feed_favorites_error_count', 0 );
			update_option( 'feed_favorites_error_count', $error_count + 1 );
		}
	}
}
