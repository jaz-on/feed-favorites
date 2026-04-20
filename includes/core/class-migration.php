<?php
/**
 * Feed Favorites Migration Class
 *
 * Handles data migration from ACF to native WordPress post meta.
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration management class.
 */
class Migration {

	/**
	 * Current plugin version.
	 *
	 * @var string
	 */
	const CURRENT_VERSION = '1.0.2';

	/**
	 * Option name for stored version.
	 *
	 * @var string
	 */
	const VERSION_OPTION = 'feed_favorites_db_version';

	/**
	 * Run migrations if needed.
	 *
	 * @return void
	 */
	public static function run() {
		$stored_version = get_option( self::VERSION_OPTION, '0.0.0' );

		if ( version_compare( $stored_version, '1.0.0', '<' ) ) {
			self::migrate_to_1_0_0();
		}

		if ( version_compare( $stored_version, '1.0.2', '<' ) ) {
			self::migrate_to_1_0_2();
		}

		if ( version_compare( $stored_version, self::CURRENT_VERSION, '<' ) ) {
			update_option( self::VERSION_OPTION, self::CURRENT_VERSION );
		}
	}

	/**
	 * Migrate to version 1.0.2.
	 *
	 * Registers capabilities and ensures new options exist.
	 *
	 * @return void
	 */
	private static function migrate_to_1_0_2() {
		Capabilities::register();

		foreach (
			array(
				'sync_post_author'  => 0,
				'last_sync_items'   => 0,
			) as $key => $default
		) {
			$option_name = Config::OPTION_PREFIX . $key;
			if ( false === get_option( $option_name ) ) {
				add_option( $option_name, $default );
			}
		}
	}

	/**
	 * Migrate to version 1.0.0.
	 *
	 * Migrates existing favorite posts to native WordPress post meta,
	 * sets post format to 'link', and adds source_type meta.
	 *
	 * @return void
	 */
	private static function migrate_to_1_0_0() {
		// Get all favorite posts.
		$posts = get_posts(
			array(
				'post_type'      => 'favorite',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		if ( empty( $posts ) ) {
			return;
		}

		$migrated = 0;
		$logger   = new Logger();

		foreach ( $posts as $post ) {
			$post_id = $post->ID;

			// Set post format to 'link'.
			if ( Config::get( 'use_link_format', true ) ) {
				set_post_format( $post_id, 'link' );
			}

			// Check if already migrated (has source_type).
			$source_type = Post_Meta::get( $post_id, Post_Meta::SOURCE_TYPE );
			if ( ! empty( $source_type ) ) {
				continue; // Already migrated.
			}

			// Migrate from ACF if available.
			if ( function_exists( 'get_field' ) ) {
				$acf_link           = get_field( 'feed_link', $post_id );
				$acf_author         = get_field( 'feed_author', $post_id );
				$acf_source_title    = get_field( 'feed_source_title', $post_id );
				$acf_source_url      = get_field( 'feed_source_url', $post_id );
				$acf_published_date  = get_field( 'feed_published_date', $post_id );

				// Migrate ACF fields to native meta.
				if ( ! empty( $acf_link ) ) {
					Post_Meta::update( $post_id, Post_Meta::EXTERNAL_URL, esc_url_raw( $acf_link ) );
				}

				if ( ! empty( $acf_author ) ) {
					Post_Meta::update( $post_id, Post_Meta::SOURCE_AUTHOR, sanitize_text_field( $acf_author ) );
				}

				if ( ! empty( $acf_source_title ) ) {
					Post_Meta::update( $post_id, Post_Meta::SOURCE_SITE, sanitize_text_field( $acf_source_title ) );
				}

				// Use feed_link as external_url if not set from ACF.
				$feed_link = get_post_meta( $post_id, 'feed_link', true );
				if ( ! empty( $feed_link ) && empty( $acf_link ) ) {
					Post_Meta::update( $post_id, Post_Meta::EXTERNAL_URL, esc_url_raw( $feed_link ) );
				}

				// Set link_summary from post excerpt or content.
				$excerpt = get_the_excerpt( $post_id );
				if ( ! empty( $excerpt ) ) {
					Post_Meta::update( $post_id, Post_Meta::LINK_SUMMARY, wp_kses_post( $excerpt ) );
				} else {
					$content = get_post_field( 'post_content', $post_id );
					if ( ! empty( $content ) ) {
						// Use first paragraph or truncated content.
						$summary = wp_trim_words( $content, 50, '...' );
						Post_Meta::update( $post_id, Post_Meta::LINK_SUMMARY, wp_kses_post( $summary ) );
					}
				}
			} else {
				// No ACF, migrate from existing native meta.
				$feed_link = get_post_meta( $post_id, 'feed_link', true );
				if ( ! empty( $feed_link ) ) {
					Post_Meta::update( $post_id, Post_Meta::EXTERNAL_URL, esc_url_raw( $feed_link ) );
				}

				// Set link_summary from post excerpt or content.
				$excerpt = get_the_excerpt( $post_id );
				if ( ! empty( $excerpt ) ) {
					Post_Meta::update( $post_id, Post_Meta::LINK_SUMMARY, wp_kses_post( $excerpt ) );
				} else {
					$content = get_post_field( 'post_content', $post_id );
					if ( ! empty( $content ) ) {
						$summary = wp_trim_words( $content, 50, '...' );
						Post_Meta::update( $post_id, Post_Meta::LINK_SUMMARY, wp_kses_post( $summary ) );
					}
				}
			}

			// Set source_type to 'rss_auto' for existing posts (default assumption).
			Post_Meta::update( $post_id, Post_Meta::SOURCE_TYPE, 'rss_auto' );

			++$migrated;
		}

		if ( $migrated > 0 ) {
			/* translators: %d: Number of posts migrated */
			$logger->log_success( sprintf( __( 'Migration to 1.0.0 completed: %d posts migrated', 'feed-favorites' ), $migrated ) );
		}
	}
}
