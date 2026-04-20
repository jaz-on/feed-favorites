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
	 * Transient key for sync lock.
	 *
	 * @var string
	 */
	const LOCK_KEY = 'feed_favorites_sync_lock';

	/**
	 * Lock TTL in seconds.
	 *
	 * @var int
	 */
	const LOCK_TTL = 300;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );
	}

	/**
	 * Try to acquire the sync lock.
	 *
	 * @return bool True if this request holds the lock.
	 */
	private function acquire_sync_lock() {
		if ( get_transient( self::LOCK_KEY ) ) {
			return false;
		}
		return (bool) set_transient( self::LOCK_KEY, 1, self::LOCK_TTL );
	}

	/**
	 * Release the sync lock.
	 *
	 * @return void
	 */
	private function release_sync_lock() {
		delete_transient( self::LOCK_KEY );
	}

	/**
	 * Resolve post author for automated / cron sync.
	 *
	 * @return int User ID.
	 */
	private function resolve_sync_author_id() {
		$user_id = absint( Config::get( 'sync_post_author', 0 ) );
		if ( $user_id > 0 && get_userdata( $user_id ) ) {
			return $user_id;
		}

		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ids',
			)
		);
		if ( ! empty( $admins ) ) {
			return (int) $admins[0];
		}

		$owner = get_user_by( 'email', get_option( 'admin_email' ) );
		if ( $owner ) {
			return (int) $owner->ID;
		}

		return 1;
	}

	/**
	 * Manual synchronization.
	 *
	 * @return string|WP_Error Success message or error.
	 */
	public function manual_sync() {
		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			return new WP_Error( 'insufficient_permissions', __( 'Insufficient permissions to perform manual synchronization', 'feed-favorites' ) );
		}

		if ( ! $this->acquire_sync_lock() ) {
			return new WP_Error( 'sync_locked', __( 'A synchronization is already in progress. Please try again later.', 'feed-favorites' ) );
		}

		try {
			$feed_url = Config::get( 'feed_url' );

			if ( empty( $feed_url ) ) {
				return new WP_Error( 'no_feed_url', __( 'Feed URL not configured', 'feed-favorites' ) );
			}

			$result = $this->sync_feed( $feed_url );

			if ( is_wp_error( $result ) ) {
				$this->log_error( 'Manual synchronization failed: ' . $result->get_error_message() );
				$this->update_stats( false );
				return $result;
			}

			$this->log_success( 'Manual synchronization successful: ' . $result . ' items processed' );
			$this->update_stats( true, $result );
			/* translators: %d: Number of items processed */
			return sprintf( __( 'Synchronization successful: %d items processed', 'feed-favorites' ), $result );
		} finally {
			$this->release_sync_lock();
		}
	}

	/**
	 * Automatic synchronization via cron.
	 *
	 * @return void
	 */
	public function automatic_sync() {
		if ( ! $this->acquire_sync_lock() ) {
			$this->log_error( 'Automatic synchronization skipped: another sync is in progress' );
			return;
		}

		try {
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
		} finally {
			$this->release_sync_lock();
		}
	}

	/**
	 * Feed synchronization.
	 *
	 * @param string $feed_url The feed URL to sync.
	 * @return int|WP_Error Number of items processed or error.
	 */
	private function sync_feed( $feed_url ) {
		$response = Http::fetch_feed( $feed_url, 30 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return new WP_Error( 'empty_feed', __( 'Feed is empty', 'feed-favorites' ) );
		}

		$parsed = Http::parse_feed_document( $body );

		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		$count     = 0;
		$max_items = intval( Config::get( 'max_items', 50 ) );

		foreach ( $parsed['items'] as $raw ) {
			if ( $max_items > 0 && $count >= $max_items ) {
				break;
			}

			if ( 'rss' === $parsed['type'] ) {
				$data = $this->extract_entry_data( $raw );
			} else {
				$data = $this->extract_atom_entry_data( $raw );
			}

			if ( $this->process_feed_item_data( $data ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Process normalized feed item data.
	 *
	 * @param array|WP_Error $data Item fields or error from extraction.
	 * @return bool True if a new post was created.
	 */
	private function process_feed_item_data( $data ) {
		if ( is_wp_error( $data ) ) {
			return false;
		}

		if ( Post_Meta::entry_exists( $data['link'] ) ) {
			return false;
		}

		$post_id = $this->create_post( $data );

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		$this->update_post_meta( $post_id, $data );

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
			'source_url'   => esc_url_raw( (string) $item->link ),
		);

		if ( empty( $data['title'] ) || empty( $data['link'] ) ) {
			return new WP_Error( 'invalid_entry', __( 'Invalid entry data', 'feed-favorites' ) );
		}

		return $data;
	}

	/**
	 * Extract Atom entry data.
	 *
	 * @param SimpleXMLElement $entry The Atom entry element.
	 * @return array|WP_Error Extracted data or error.
	 */
	private function extract_atom_entry_data( $entry ) {
		$ns = Http::ATOM_NS;
		$e  = $entry->children( $ns );

		$title = sanitize_text_field( (string) $e->title );

		$link_href = '';
		foreach ( $e->link as $link_el ) {
			$rel = isset( $link_el['rel'] ) ? (string) $link_el['rel'] : 'alternate';
			if ( 'alternate' === $rel ) {
				$link_href = isset( $link_el['href'] ) ? esc_url_raw( (string) $link_el['href'] ) : '';
				if ( $link_href ) {
					break;
				}
			}
		}
		if ( empty( $link_href ) && isset( $e->link[0]['href'] ) ) {
			$link_href = esc_url_raw( (string) $e->link[0]['href'] );
		}

		$content = '';
		if ( isset( $e->content ) && '' !== trim( (string) $e->content ) ) {
			$content = wp_kses_post( (string) $e->content );
		} elseif ( isset( $e->summary ) ) {
			$content = wp_kses_post( (string) $e->summary );
		}

		$published = '';
		if ( isset( $e->updated ) ) {
			$published = sanitize_text_field( (string) $e->updated );
		}
		if ( '' === $published && isset( $e->published ) ) {
			$published = sanitize_text_field( (string) $e->published );
		}

		$author = '';
		if ( isset( $e->author->name ) ) {
			$author = sanitize_text_field( (string) $e->author->name );
		}

		$source_title = '';
		$source_url   = $link_href;
		if ( isset( $e->source ) ) {
			$src = $e->source->children( $ns );
			if ( isset( $src->title ) ) {
				$source_title = sanitize_text_field( (string) $src->title );
			}
			if ( isset( $e->source->link[0]['href'] ) ) {
				$source_url = esc_url_raw( (string) $e->source->link[0]['href'] );
			}
		}

		if ( empty( $title ) || empty( $link_href ) ) {
			return new WP_Error( 'invalid_entry', __( 'Invalid entry data', 'feed-favorites' ) );
		}

		return array(
			'title'        => $title,
			'link'         => $link_href,
			'content'      => $content,
			'published'    => $published,
			'author'       => $author,
			'source_title' => $source_title,
			'source_url'   => $source_url,
		);
	}

	/**
	 * Create a post.
	 *
	 * @param array $data The post data.
	 * @return int|WP_Error The post ID or error.
	 */
	private function create_post( $data ) {
		$status = Config::get( 'auto_sync', '1' ) ? 'publish' : 'draft';

		$ts = strtotime( $data['published'] );
		if ( empty( $data['published'] ) || false === $ts ) {
			$published_gmt = current_time( 'mysql', true );
		} else {
			$published_gmt = gmdate( 'Y-m-d H:i:s', $ts );
		}

		$post_data = array(
			'post_title'    => $data['title'],
			'post_content'  => $data['content'],
			'post_status'   => $status,
			'post_type'     => 'favorite',
			'post_date_gmt' => $published_gmt,
			'post_author'   => $this->resolve_sync_author_id(),
		);

		$post_id = wp_insert_post( $post_data, true );

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
		update_post_meta( $post_id, 'feed_link', $data['link'] );

		Post_Meta::update( $post_id, Post_Meta::EXTERNAL_URL, $data['link'] );
		Post_Meta::update( $post_id, Post_Meta::SOURCE_AUTHOR, $data['author'] );
		Post_Meta::update( $post_id, Post_Meta::SOURCE_SITE, $data['source_title'] );
		Post_Meta::update( $post_id, Post_Meta::SOURCE_TYPE, 'rss_auto' );

		if ( ! empty( $data['content'] ) ) {
			$summary = wp_trim_words( $data['content'], 50, '...' );
			Post_Meta::update( $post_id, Post_Meta::LINK_SUMMARY, wp_kses_post( $summary ) );
		}

		Post_Meta::update( $post_id, Post_Meta::LINK_COMMENTARY, '' );
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
		$logger = new Logger();
		$logger->log( 'SUCCESS', $message );
	}

	/**
	 * Update statistics.
	 *
	 * @param bool     $success True for success, false for failure.
	 * @param int|null $items_processed Number of items processed on success; omit to leave count unchanged.
	 * @return void
	 */
	private function update_stats( $success = true, $items_processed = null ) {
		Config::set( 'last_sync', current_time( 'mysql' ) );

		if ( $success ) {
			$sync_count = get_option( 'feed_favorites_sync_count', 0 );
			update_option( 'feed_favorites_sync_count', $sync_count + 1 );
			if ( null !== $items_processed ) {
				Config::set( 'last_sync_items', (int) $items_processed );
			}
		} else {
			$error_count = get_option( 'feed_favorites_error_count', 0 );
			update_option( 'feed_favorites_error_count', $error_count + 1 );
		}
	}
}
