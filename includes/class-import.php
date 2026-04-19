<?php
/**
 * Feed Favorites JSON Import Class
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RSS favorites JSON import management.
 */
class Import {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_post_feed_favorites_json_import', array( $this, 'handle_json_import' ) );
	}

	/**
	 * Handle JSON import.
	 *
	 * @return void
	 */
	public function handle_json_import() {
		// Security verification.
		if ( ! isset( $_POST['feed_favorites_json_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['feed_favorites_json_nonce'] ) ), 'feed_favorites_json_import' ) ) {
			wp_die( esc_html__( 'Security', 'feed-favorites' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'feed-favorites' ) );
		}

		// Sanitize input data.
		$batch_size   = isset( $_POST['rss_batch_size'] ) ? intval( $_POST['rss_batch_size'] ) : 20;
		$import_limit = isset( $_POST['rss_import_limit'] ) ? intval( $_POST['rss_import_limit'] ) : 50;

		// Check file and sanitize file upload data.
		if ( ! isset( $_FILES['rss_json_file'] ) || ! isset( $_FILES['rss_json_file']['error'] ) || UPLOAD_ERR_OK !== $_FILES['rss_json_file']['error'] ) {
			$this->redirect_with_error( __( 'Error uploading file', 'feed-favorites' ) );
		}

		// Sanitize file array to prevent any potential issues.
		$file = array_map( 'sanitize_text_field', $_FILES['rss_json_file'] );
		// Preserve original tmp_name for file operations (don't sanitize file paths).
		if ( isset( $_FILES['rss_json_file']['tmp_name'] ) ) {
			$file['tmp_name'] = $_FILES['rss_json_file']['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		// Security check - verify that the file was uploaded via HTTP POST.
		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			$this->redirect_with_error( __( 'Security violation: Invalid file upload', 'feed-favorites' ) );
		}

		// Enhanced file validation.
		$file_extension     = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		$allowed_extensions = array( 'json', 'xml' );

		if ( ! in_array( $file_extension, $allowed_extensions, true ) ) {
			$this->redirect_with_error( __( 'File must be JSON or XML format', 'feed-favorites' ) );
		}

		// Check file size (max 10MB).
		$max_size = 10 * 1024 * 1024; // 10MB.
		if ( $file['size'] > $max_size ) {
			$this->redirect_with_error( __( 'File size exceeds maximum allowed size (10MB)', 'feed-favorites' ) );
		}

		// Validate MIME type.
		$finfo     = finfo_open( FILEINFO_MIME_TYPE );
		$mime_type = finfo_file( $finfo, $file['tmp_name'] );
		// phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated -- finfo_close() still required on PHP 8.2.
		finfo_close( $finfo );

		$allowed_mimes = array(
			'json' => array( 'application/json', 'text/plain' ),
			'xml'  => array( 'application/xml', 'text/xml', 'text/plain' ),
		);

		if ( ! in_array( $mime_type, $allowed_mimes[ $file_extension ], true ) ) {
			$this->redirect_with_error( __( 'Invalid file type detected', 'feed-favorites' ) );
		}

		// Read file with error handling (local file only, not URL).
		$file_content = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $file_content ) {
			$this->redirect_with_error( __( 'Unable to read file', 'feed-favorites' ) );
		}

		// Process based on file type.
		if ( 'json' === $file_extension ) {
			$data = json_decode( $file_content, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				$this->redirect_with_error( __( 'Invalid JSON file', 'feed-favorites' ) );
			}
		} else {
			// XML processing (to be implemented later).
			$this->redirect_with_error( __( 'XML import not yet implemented', 'feed-favorites' ) );
		}

		// Validate options.
		if ( $batch_size < 5 || $batch_size > 100 ) {
			$batch_size = 20;
		}

		if ( $import_limit < 0 || $import_limit > 1000 ) {
			$import_limit = 50;
		}

		// Check system requirements before import.
		$system_check = $this->check_system_requirements( $data, $batch_size, $import_limit );
		if ( is_wp_error( $system_check ) ) {
			$this->redirect_with_error( $system_check->get_error_message() );
		}

		// Process import with batches.
		$result = $this->process_json_import_batched( $data, $batch_size, $import_limit );

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_error( $result->get_error_message() );
		} else {
			/* translators: 1: Number of imported articles, 2: Number of batches */
			$this->redirect_with_success( sprintf( __( 'Import successful! %1$d articles imported in %2$d batches', 'feed-favorites' ), $result['imported'], $result['batches'] ) );
		}
	}

	/**
	 * Process JSON import with batches.
	 *
	 * @param array $data The JSON data to import.
	 * @param int   $batch_size The size of each batch.
	 * @param int   $import_limit The maximum number of items to import.
	 * @return array|WP_Error Import result or error.
	 */
	private function process_json_import_batched( $data, $batch_size, $import_limit ) {
		$logger = new Logger();

		// Detect JSON format.
		$entries = $this->detect_and_extract_entries( $data );
		if ( is_wp_error( $entries ) ) {
			return $entries;
		}

		$total_entries = count( $entries );

		// Apply import limit.
		if ( $import_limit > 0 && $total_entries > $import_limit ) {
			$entries       = array_slice( $entries, 0, $import_limit );
			$total_entries = $import_limit;
		}

		/* translators: 1: Number of articles to process, 2: Batch size */
		$logger->log_info( sprintf( __( 'Starting JSON import by batches: %1$d articles to process, batch size: %2$d', 'feed-favorites' ), $total_entries, $batch_size ) );

		$imported_count = 0;
		$skipped_count  = 0;
		$batch_count    = 0;

		// Process by batches.
		$batches = array_chunk( $entries, $batch_size );

		foreach ( $batches as $batch_index => $batch ) {
			++$batch_count;
			$batch_start = $batch_index * $batch_size + 1;
			$batch_end   = min( ( $batch_index + 1 ) * $batch_size, $total_entries );

			// Check available memory before processing batch.
			$memory_limit = ini_get( 'memory_limit' );
			$memory_usage = memory_get_usage( true );
			$memory_peak  = memory_get_peak_usage( true );

			/* translators: 1: Current batch number, 2: Total batches, 3: Start index, 4: End index, 5: Memory usage, 6: Memory limit */
			$format_batch = __( 'Processing batch %1$d/%2$d (articles %3$d-%4$d) - Memory: %5$s/%6$s', 'feed-favorites' );
			$message      = sprintf(
				$format_batch,
				$batch_count,
				count( $batches ),
				$batch_start,
				$batch_end,
				size_format( $memory_usage ),
				$memory_limit
			);
			$logger->log_info( $message );

			// If memory usage is too high, pause longer.
			if ( $memory_usage > 100 * 1024 * 1024 ) { // 100MB.
				$logger->log_error( __( 'High memory usage detected, pausing for memory cleanup', 'feed-favorites' ) );
				usleep( 1000000 ); // 1 second.
				if ( function_exists( 'gc_collect_cycles' ) ) {
					gc_collect_cycles();
				}
			}

			foreach ( $batch as $entry ) {
				$result = $this->process_json_entry( $entry );
				if ( true === $result ) {
					++$imported_count;
				} else {
					++$skipped_count;
				}
			}

			// Pause between batches to avoid server overload and memory issues.
			if ( $batch_count < count( $batches ) ) {
				usleep( 500000 ); // 0.5 second.

				// Force garbage collection to free memory.
				if ( function_exists( 'gc_collect_cycles' ) ) {
					gc_collect_cycles();
				}
			}
		}

		/* translators: 1: Number of imported articles, 2: Number of skipped articles, 3: Number of batches processed */
		$logger->log_success( sprintf( __( 'JSON import completed: %1$d imported, %2$d skipped, %3$d batches processed', 'feed-favorites' ), $imported_count, $skipped_count, $batch_count ) );

		return array(
			'imported' => $imported_count,
			'skipped'  => $skipped_count,
			'batches'  => $batch_count,
			'total'    => $total_entries,
		);
	}

	/**
	 * Process JSON import (old method - kept for compatibility).
	 *
	 * @param array $data The JSON data to import.
	 * @return int|WP_Error Number of imported items or error.
	 */
	private function process_json_import( $data ) {
		$logger = new Logger();
		$sync   = new Sync();

		// Detect JSON format.
		$entries = $this->detect_and_extract_entries( $data );
		if ( is_wp_error( $entries ) ) {
			return $entries;
		}

		$total_entries  = count( $entries );
		$imported_count = 0;
		$skipped_count  = 0;

		/* translators: %d: Number of articles to process */
		$logger->log_info( sprintf( __( 'Starting JSON import: %d articles to process', 'feed-favorites' ), $total_entries ) );

		foreach ( $entries as $entry ) {
			$result = $this->process_json_entry( $entry );
			if ( true === $result ) {
				++$imported_count;
			} else {
				++$skipped_count;
			}
		}

		/* translators: 1: Number of imported articles, 2: Number of skipped articles */
		$logger->log_success( sprintf( __( 'JSON import completed: %1$d imported, %2$d skipped', 'feed-favorites' ), $imported_count, $skipped_count ) );

		return $imported_count;
	}

	/**
	 * Process a JSON entry.
	 *
	 * @param array $entry The JSON entry to process.
	 * @return bool True if processed successfully, false otherwise.
	 */
	private function process_json_entry( $entry ) {
		$logger = new Logger();

		// Validate required data.
		if ( empty( $entry['title'] ) || empty( $entry['url'] ) ) {
			$logger->log_error( 'JSON import: Missing data for entry - empty title or URL' );
			return false;
		}

		// Check if article already exists.
		if ( Post_Meta::entry_exists( $entry['url'] ) ) {
			$logger->log_info( 'JSON import: Article already exists - ' . $entry['url'] );
			return false;
		}

		// Prepare data.
		$data = array(
			'title'        => sanitize_text_field( $entry['title'] ),
			'link'         => esc_url_raw( $entry['url'] ),
			'content'      => isset( $entry['content'] ) ? wp_kses_post( $entry['content'] ) : '',
			'published'    => isset( $entry['published_at'] ) ? sanitize_text_field( $entry['published_at'] ) : current_time( 'mysql' ),
			'author'       => isset( $entry['author'] ) ? sanitize_text_field( $entry['author'] ) : '',
			'source_title' => isset( $entry['feed_title'] ) ? sanitize_text_field( $entry['feed_title'] ) : '',
			'source_url'   => isset( $entry['feed_url'] ) ? esc_url_raw( $entry['feed_url'] ) : '',
		);

		// Check that CPT exists.
		if ( ! post_type_exists( 'favorite' ) ) {
			$logger->log_error( 'JSON import: CPT favorite not registered - unable to create post' );
			return false;
		}

		// Create post.
		$post_id = $this->create_post( $data );

		if ( is_wp_error( $post_id ) ) {
			$logger->log_error( 'JSON import: Error creating post - ' . $post_id->get_error_message() );
			return false;
		}

		// Update post meta.
		$this->update_post_meta( $post_id, $data );

		$logger->log_success( 'JSON import: Post created successfully - ID: ' . $post_id . ' - Title: ' . $data['title'] );
		return true;
	}


	/**
	 * Create a post.
	 *
	 * @param array $data The post data.
	 * @return int|WP_Error The post ID or error.
	 */
	private function create_post( $data ) {
		$post_data = array(
			'post_title'   => $data['title'],
			'post_content' => $data['content'],
			'post_status'  => 'publish',
			'post_type'    => 'favorite',
			'post_date'    => $data['published'],
			'post_author'  => get_current_user_id(),
		);

		$post_id = wp_insert_post( $post_data, true );

		// Set post format to 'link' if enabled.
		if ( ! is_wp_error( $post_id ) && Config::get( 'use_link_format', true ) ) {
			set_post_format( $post_id, 'link' );
		}

		return $post_id;
	}

	/**
	 * Update post meta.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $data The data to update.
	 * @return void
	 */
	private function update_post_meta( $post_id, $data ) {
		// Always persist feed_link as a native post meta for duplicate detection.
		update_post_meta( $post_id, 'feed_link', $data['link'] );

		// Update native WordPress post meta.
		Post_Meta::update( $post_id, Post_Meta::EXTERNAL_URL, $data['link'] );
		Post_Meta::update( $post_id, Post_Meta::SOURCE_AUTHOR, $data['author'] );
		Post_Meta::update( $post_id, Post_Meta::SOURCE_SITE, $data['source_title'] );
		Post_Meta::update( $post_id, Post_Meta::SOURCE_TYPE, 'rss_auto' );

		// Set link_summary from content.
		if ( ! empty( $data['content'] ) ) {
			$summary = wp_trim_words( $data['content'], 50, '...' );
			Post_Meta::update( $post_id, Post_Meta::LINK_SUMMARY, wp_kses_post( $summary ) );
		}

		// Set link_commentary empty by default (user can add later).
		Post_Meta::update( $post_id, Post_Meta::LINK_COMMENTARY, '' );
	}

	/**
	 * Redirect with error message.
	 *
	 * @param string $message The error message.
	 * @return void
	 */
	private function redirect_with_error( $message ) {
		$logger = new Logger();
		$logger->log_error( 'JSON import: ' . $message );

		wp_safe_redirect(
			wp_nonce_url(
				add_query_arg(
					array(
						'post_type'    => 'favorite',
						'page'         => 'feed-favorites',
						'import_error' => rawurlencode( $message ),
					),
					admin_url( 'edit.php' )
				),
				'feed_favorites_admin'
			)
		);
		exit;
	}

	/**
	 * Redirect with success message.
	 *
	 * @param string $message The success message.
	 * @return void
	 */
	private function redirect_with_success( $message ) {
		$logger = new Logger();
		$logger->log_success( 'JSON import: ' . $message );

		wp_safe_redirect(
			wp_nonce_url(
				add_query_arg(
					array(
						'post_type'      => 'favorite',
						'page'           => 'feed-favorites',
						'import_success' => rawurlencode( $message ),
					),
					admin_url( 'edit.php' )
				),
				'feed_favorites_admin'
			)
		);
		exit;
	}

	/**
	 * Detect and extract entries based on JSON format.
	 *
	 * @param array $data The JSON data to analyze.
	 * @return array|WP_Error Normalized entries or error.
	 */
	public function detect_and_extract_entries( $data ) {
		// Format 1: Simple format (starred.json).
		if ( is_array( $data ) && ! empty( $data ) && isset( $data[0]['id'] ) && isset( $data[0]['title'] ) && isset( $data[0]['url'] ) ) {
			return $this->normalize_simple_format( $data );
		}

		// Format 2: FreshRSS/Google Reader format (starred_2025-07-13.json).
		if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
			return $this->normalize_freshrss_format( $data['items'] );
		}

		// Format 3: Old format with starred_entries (compatibility).
		if ( isset( $data['starred_entries'] ) && is_array( $data['starred_entries'] ) ) {
			return $data['starred_entries'];
		}

		return new WP_Error( 'invalid_format', __( 'Unrecognized JSON format. Supported formats: simple array, FreshRSS/Google Reader format, or format with "starred_entries"', 'feed-favorites' ) );
	}

	/**
	 * Normalize simple format (starred.json).
	 *
	 * @param array $entries The entries to normalize.
	 * @return array Normalized entries.
	 */
	private function normalize_simple_format( $entries ) {
		$normalized = array();

		foreach ( $entries as $entry ) {
			$normalized[] = array(
				'title'        => $entry['title'] ?? '',
				'url'          => $entry['url'] ?? '',
				'content'      => $entry['content'] ?? '',
				'published_at' => $entry['published'] ?? current_time( 'mysql' ),
				'author'       => $entry['author'] ?? '',
				'feed_title'   => '',
				'feed_url'     => '',
			);
		}

		return $normalized;
	}

	/**
	 * Normalize FreshRSS/Google Reader format (starred_2025-07-13.json).
	 *
	 * @param array $items The items to normalize.
	 * @return array Normalized items.
	 */
	private function normalize_freshrss_format( $items ) {
		$normalized = array();

		foreach ( $items as $item ) {
			// Extract URL from canonical or alternate.
			$url = '';
			if ( isset( $item['canonical'][0]['href'] ) ) {
				$url = $item['canonical'][0]['href'];
			} elseif ( isset( $item['alternate'][0]['href'] ) ) {
				$url = $item['alternate'][0]['href'];
			}

			// Extract content.
			$content = '';
			if ( isset( $item['content']['content'] ) ) {
				$content = $item['content']['content'];
			}

			// Extract publication date.
			$published = current_time( 'mysql' );
			if ( isset( $item['published'] ) ) {
							// Convert Unix timestamp to MySQL format.
				if ( is_numeric( $item['published'] ) ) {
					$published = gmdate( 'Y-m-d H:i:s', $item['published'] );
				} else {
					$published = $item['published'];
				}
			}

			// Extract source information.
			$source_title = '';
			$source_url   = '';
			if ( isset( $item['origin'] ) ) {
				$source_title = $item['origin']['title'] ?? '';
				$source_url   = $item['origin']['htmlUrl'] ?? '';
			}

			$normalized[] = array(
				'title'        => $item['title'] ?? '',
				'url'          => $url,
				'content'      => $content,
				'published_at' => $published,
				'author'       => $item['author'] ?? '',
				'feed_title'   => $source_title,
				'feed_url'     => $source_url,
			);
		}

		return $normalized;
	}

	/**
	 * Check system requirements before import.
	 *
	 * @param array $data         The data to import.
	 * @param int   $batch_size   The batch size used for import.
	 * @param int   $import_limit The maximum number of items to import.
	 * @return bool|WP_Error True if requirements met, error otherwise.
	 */
	private function check_system_requirements( $data, $batch_size, $import_limit ) {
		$logger = new Logger();

		// Get system limits.
		$memory_limit        = ini_get( 'memory_limit' );
		$max_execution_time  = ini_get( 'max_execution_time' );
		$upload_max_filesize = ini_get( 'upload_max_filesize' );
		$post_max_size       = ini_get( 'post_max_size' );

		// Convert to bytes for comparison.
		$memory_limit_bytes        = $this->convert_to_bytes( $memory_limit );
		$upload_max_filesize_bytes = $this->convert_to_bytes( $upload_max_filesize );
		$post_max_size_bytes       = $this->convert_to_bytes( $post_max_size );

		// Estimate required memory based on data size.
		$estimated_entries = $this->estimate_entries_count( $data );
		$estimated_memory  = $estimated_entries * 1024 * 10; // ~10KB per entry

		// Check memory requirements.
		if ( $memory_limit_bytes > 0 && $estimated_memory > $memory_limit_bytes * 0.8 ) {
			/* translators: 1: Estimated memory usage, 2: Available memory limit */
			$format_memory_low = __( 'Memory limit too low for import. Estimated: %1$s, Available: %2$s', 'feed-favorites' );
			$err_message       = sprintf(
				$format_memory_low,
				size_format( $estimated_memory ),
				$memory_limit
			);
			$logger->log_error( $err_message );

			/* translators: 1: Estimated memory requirement, 2: Available memory limit */
			$format_memory_req = __( 'Insufficient memory for import. Estimated requirement: %1$s, Available: %2$s. Please reduce batch size or contact your hosting provider to increase memory limit.', 'feed-favorites' );
			return new WP_Error(
				'insufficient_memory',
				sprintf(
					$format_memory_req,
					size_format( $estimated_memory ),
					$memory_limit
				)
			);
		}

		// Check execution time.
		if ( $max_execution_time > 0 && $max_execution_time < 300 ) {
			/* translators: %s: Execution time limit in seconds */
			$format_time_low = __( 'Execution time limit too low: %s seconds', 'feed-favorites' );
			$time_message    = sprintf( $format_time_low, $max_execution_time );
			$logger->log_error( $time_message );

			/* translators: %s: Execution time limit in seconds */
			$format_time_req = __( 'Execution time limit too low (%s seconds). Large imports may timeout. Contact your hosting provider to increase max_execution_time.', 'feed-favorites' );
			return new WP_Error(
				'insufficient_time',
				sprintf( $format_time_req, $max_execution_time )
			);
		}

		// Check file upload limits.
		if ( $upload_max_filesize_bytes > 0 && $upload_max_filesize_bytes < 50 * 1024 * 1024 ) {
			/* translators: %s: Upload file size limit */
			$format_upload_low = __( 'Upload file size limit too low: %s', 'feed-favorites' );
			$upload_message    = sprintf( $format_upload_low, $upload_max_filesize );
			$logger->log_error( $upload_message );

			/* translators: %s: Upload file size limit */
			$format_upload_req = __( 'Upload file size limit too low (%s). Large export files may fail to upload. Contact your hosting provider.', 'feed-favorites' );
			return new WP_Error(
				'insufficient_upload_size',
				sprintf( $format_upload_req, $upload_max_filesize )
			);
		}

		/* translators: 1: Memory limit, 2: Execution time, 3: Upload file size limit, 4: Number of estimated entries */
		$format_info    = __( 'System check passed. Memory: %1$s, Time: %2$ss, Upload: %3$s, Estimated entries: %4$d', 'feed-favorites' );
		$info_message   = sprintf(
			$format_info,
			$memory_limit,
			0 === $max_execution_time ? 'unlimited' : $max_execution_time,
			$upload_max_filesize,
			$estimated_entries
		);
		$logger->log_info( $info_message );

		return true;
	}

	/**
	 * Estimate number of entries in data.
	 *
	 * @param array $data The data to analyze.
	 * @return int Number of estimated entries.
	 */
	private function estimate_entries_count( $data ) {
		if ( is_array( $data ) ) {
			if ( isset( $data['items'] ) ) {
				return count( $data['items'] );
			} elseif ( isset( $data['starred_entries'] ) ) {
				return count( $data['starred_entries'] );
			} else {
				return count( $data );
			}
		}
		return 0;
	}

	/**
	 * Convert PHP size string to bytes.
	 *
	 * @param string $size_str The size string to convert.
	 * @return int The size in bytes.
	 */
	private function convert_to_bytes( $size_str ) {
		$size_str = trim( $size_str );
		$last     = strtolower( $size_str[ strlen( $size_str ) - 1 ] );
		$size     = (int) $size_str;

		switch ( $last ) {
			case 'g':
				$size *= 1024;
				// Fall through to multiply by 1024 again for MB.
			case 'm':
				$size *= 1024;
				// Fall through to multiply by 1024 again for KB.
			case 'k':
				$size *= 1024;
		}

		return $size;
	}
}
