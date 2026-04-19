<?php
/**
 * Feed Favorites Post Meta Management Class
 *
 * Manages native WordPress post meta for favorite posts.
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post meta management class.
 */
class Post_Meta {

	/**
	 * Meta key constants.
	 */
	const EXTERNAL_URL    = '_feed_favorites_external_url';
	const LINK_SUMMARY    = '_feed_favorites_link_summary';
	const LINK_COMMENTARY = '_feed_favorites_link_commentary';
	const SOURCE_AUTHOR   = '_feed_favorites_source_author';
	const SOURCE_SITE     = '_feed_favorites_source_site';
	const SOURCE_TYPE     = '_feed_favorites_source_type';

	/**
	 * Register meta fields for REST API and Gutenberg support.
	 *
	 * @return void
	 */
	public static function register() {
		$meta_keys = array(
			self::EXTERNAL_URL    => array(
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => array( __CLASS__, 'auth_callback' ),
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
			),
			self::LINK_SUMMARY    => array(
				'sanitize_callback' => 'wp_kses_post',
				'auth_callback'     => array( __CLASS__, 'auth_callback' ),
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
			),
			self::LINK_COMMENTARY => array(
				'sanitize_callback' => 'wp_kses_post',
				'auth_callback'     => array( __CLASS__, 'auth_callback' ),
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
			),
			self::SOURCE_AUTHOR   => array(
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => array( __CLASS__, 'auth_callback' ),
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
			),
			self::SOURCE_SITE     => array(
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => array( __CLASS__, 'auth_callback' ),
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
			),
			self::SOURCE_TYPE     => array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_source_type' ),
				'auth_callback'     => array( __CLASS__, 'auth_callback' ),
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
			),
		);

		foreach ( $meta_keys as $key => $args ) {
			register_post_meta( 'favorite', $key, $args );
		}
	}

	/**
	 * Authentication callback for meta fields.
	 *
	 * @param bool   $allowed Whether the user can add the meta.
	 * @param string $meta_key The meta key.
	 * @param int    $post_id The post ID.
	 * @param int    $user_id The user ID.
	 * @param string $cap The capability.
	 * @param array  $caps User capabilities.
	 * @return bool Whether the user can add the meta.
	 */
	public static function auth_callback( $allowed, $meta_key, $post_id, $user_id, $cap, $caps ) {
		$post = get_post( $post_id );
		if ( ! $post || 'favorite' !== $post->post_type ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Sanitize source type.
	 *
	 * @param string $value The value to sanitize.
	 * @return string Sanitized value.
	 */
	public static function sanitize_source_type( $value ) {
		$allowed = array( 'rss_auto', 'manual' );
		return in_array( $value, $allowed, true ) ? $value : 'rss_auto';
	}

	/**
	 * Get meta value.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $meta_key The meta key constant.
	 * @param mixed  $default Default value if meta doesn't exist.
	 * @return mixed Meta value or default.
	 */
	public static function get( $post_id, $meta_key, $default = '' ) {
		$value = get_post_meta( $post_id, $meta_key, true );
		return '' !== $value ? $value : $default;
	}

	/**
	 * Update meta value.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $meta_key The meta key constant.
	 * @param mixed  $value The value to set.
	 * @return bool|int Meta ID if the key didn't exist, true on successful update, false on failure.
	 */
	public static function update( $post_id, $meta_key, $value ) {
		return update_post_meta( $post_id, $meta_key, $value );
	}

	/**
	 * Delete meta value.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $meta_key The meta key constant.
	 * @return bool True on success, false on failure.
	 */
	public static function delete( $post_id, $meta_key ) {
		return delete_post_meta( $post_id, $meta_key );
	}

	/**
	 * Check if post is a link post.
	 *
	 * @param int $post_id The post ID.
	 * @return bool True if post is a link post.
	 */
	public static function is_link_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'favorite' !== $post->post_type ) {
			return false;
		}

		$post_format = get_post_format( $post_id );
		return 'link' === $post_format;
	}

	/**
	 * Check if an entry with the given URL already exists.
	 *
	 * Centralized duplicate detection method. Checks EXTERNAL_URL first,
	 * then falls back to feed_link for compatibility.
	 *
	 * @param string $url The URL to check.
	 * @return bool True if entry exists, false otherwise.
	 */
	public static function entry_exists( $url ) {
		if ( empty( $url ) ) {
			return false;
		}

		// First check EXTERNAL_URL (native meta).
		$existing_post = get_posts(
			array(
				'post_type'              => 'favorite',
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Optimized with limits and cache flags
					array(
						'key'     => self::EXTERNAL_URL,
						'value'   => $url,
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

		if ( ! empty( $existing_post ) ) {
			return true;
		}

		// Fallback: check feed_link for compatibility with older entries.
		$existing_post = get_posts(
			array(
				'post_type'              => 'favorite',
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Optimized with limits and cache flags
					array(
						'key'     => 'feed_link',
						'value'   => $url,
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
	 * Get all meta for a post.
	 *
	 * @param int $post_id The post ID.
	 * @return array All meta values.
	 */
	public static function get_all( $post_id ) {
		return array(
			'external_url'    => self::get( $post_id, self::EXTERNAL_URL ),
			'link_summary'    => self::get( $post_id, self::LINK_SUMMARY ),
			'link_commentary' => self::get( $post_id, self::LINK_COMMENTARY ),
			'source_author'   => self::get( $post_id, self::SOURCE_AUTHOR ),
			'source_site'     => self::get( $post_id, self::SOURCE_SITE ),
			'source_type'     => self::get( $post_id, self::SOURCE_TYPE, 'rss_auto' ),
		);
	}

	/**
	 * Update all meta for a post.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $data The data to update.
	 * @return void
	 */
	public static function update_all( $post_id, $data ) {
		if ( isset( $data['external_url'] ) ) {
			self::update( $post_id, self::EXTERNAL_URL, esc_url_raw( $data['external_url'] ) );
		}
		if ( isset( $data['link_summary'] ) ) {
			self::update( $post_id, self::LINK_SUMMARY, wp_kses_post( $data['link_summary'] ) );
		}
		if ( isset( $data['link_commentary'] ) ) {
			self::update( $post_id, self::LINK_COMMENTARY, wp_kses_post( $data['link_commentary'] ) );
		}
		if ( isset( $data['source_author'] ) ) {
			self::update( $post_id, self::SOURCE_AUTHOR, sanitize_text_field( $data['source_author'] ) );
		}
		if ( isset( $data['source_site'] ) ) {
			self::update( $post_id, self::SOURCE_SITE, sanitize_text_field( $data['source_site'] ) );
		}
		if ( isset( $data['source_type'] ) ) {
			self::update( $post_id, self::SOURCE_TYPE, self::sanitize_source_type( $data['source_type'] ) );
		}
	}
}
