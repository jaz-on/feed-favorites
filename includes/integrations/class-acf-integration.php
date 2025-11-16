<?php
/**
 * Feed Favorites ACF Integration Class
 *
 * Optional bidirectional sync between ACF fields and native WordPress post meta.
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACF integration management.
 */
class ACF_Integration {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Only activate if ACF is available.
		if ( ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) ) {
			return;
		}

		// Sync from ACF to native meta when ACF fields are saved.
		add_action( 'acf/save_post', array( $this, 'sync_from_acf' ), 20 );

		// Sync from native meta to ACF when native meta is saved.
		add_action( 'save_post', array( $this, 'sync_to_acf' ), 20, 2 );
	}

	/**
	 * Sync from ACF fields to native meta.
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function sync_from_acf( $post_id ) {
		// Only for favorite posts.
		if ( 'favorite' !== get_post_type( $post_id ) ) {
			return;
		}

		// Get ACF fields.
		$acf_link           = get_field( 'feed_link', $post_id );
		$acf_author         = get_field( 'feed_author', $post_id );
		$acf_source_title    = get_field( 'feed_source_title', $post_id );
		$acf_source_url       = get_field( 'feed_source_url', $post_id );
		$acf_published_date   = get_field( 'feed_published_date', $post_id );

		// Sync to native meta.
		if ( ! empty( $acf_link ) ) {
			Post_Meta::update( $post_id, Post_Meta::EXTERNAL_URL, esc_url_raw( $acf_link ) );
			// Also update feed_link for compatibility.
			update_post_meta( $post_id, 'feed_link', esc_url_raw( $acf_link ) );
		}

		if ( ! empty( $acf_author ) ) {
			Post_Meta::update( $post_id, Post_Meta::SOURCE_AUTHOR, sanitize_text_field( $acf_author ) );
		}

		if ( ! empty( $acf_source_title ) ) {
			Post_Meta::update( $post_id, Post_Meta::SOURCE_SITE, sanitize_text_field( $acf_source_title ) );
		}
	}

	/**
	 * Sync from native meta to ACF fields.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post The post object.
	 * @return void
	 */
	public function sync_to_acf( $post_id, $post ) {
		// Only for favorite posts.
		if ( 'favorite' !== $post->post_type ) {
			return;
		}

		// Skip if this is an ACF save (to avoid infinite loop).
		if ( doing_action( 'acf/save_post' ) ) {
			return;
		}

		// Get native meta values.
		$external_url    = Post_Meta::get( $post_id, Post_Meta::EXTERNAL_URL );
		$source_author   = Post_Meta::get( $post_id, Post_Meta::SOURCE_AUTHOR );
		$source_site     = Post_Meta::get( $post_id, Post_Meta::SOURCE_SITE );
		$link_summary    = Post_Meta::get( $post_id, Post_Meta::LINK_SUMMARY );
		$link_commentary = Post_Meta::get( $post_id, Post_Meta::LINK_COMMENTARY );

		// Sync to ACF fields.
		if ( ! empty( $external_url ) ) {
			update_field( 'feed_link', $external_url, $post_id );
		}

		if ( ! empty( $source_author ) ) {
			update_field( 'feed_author', $source_author, $post_id );
		}

		if ( ! empty( $source_site ) ) {
			update_field( 'feed_source_title', $source_site, $post_id );
		}
	}
}

