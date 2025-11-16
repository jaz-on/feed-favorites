<?php
/**
 * Feed Favorites Manual Creator Class
 *
 * Handles manual creation of favorite link posts.
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manual link post creation management.
 */
class Manual_Creator {

	/**
	 * Create a link post manually.
	 *
	 * @param array $data The post data.
	 * @return int|WP_Error The post ID or error.
	 */
	public static function create_link_post( $data ) {
		// Validate required fields.
		$validation = self::validate_data( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Prepare post data.
		$post_data = array(
			'post_title'   => sanitize_text_field( $data['title'] ),
			'post_content' => isset( $data['content'] ) ? wp_kses_post( $data['content'] ) : '',
			'post_status'  => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'publish',
			'post_type'     => 'favorite',
			'post_author'   => isset( $data['author'] ) ? intval( $data['author'] ) : get_current_user_id(),
		);

		// Set post date if provided.
		if ( isset( $data['post_date'] ) && ! empty( $data['post_date'] ) ) {
			$post_data['post_date'] = sanitize_text_field( $data['post_date'] );
		}

		// Create post.
		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Set post format to 'link' if enabled.
		if ( Config::get( 'use_link_format', true ) ) {
			set_post_format( $post_id, 'link' );
		}

		// Save all meta fields.
		$meta_data = array(
			'external_url'    => esc_url_raw( $data['external_url'] ),
			'link_summary'    => isset( $data['link_summary'] ) ? wp_kses_post( $data['link_summary'] ) : '',
			'link_commentary' => isset( $data['link_commentary'] ) ? wp_kses_post( $data['link_commentary'] ) : '',
			'source_author'   => isset( $data['source_author'] ) ? sanitize_text_field( $data['source_author'] ) : '',
			'source_site'     => isset( $data['source_site'] ) ? sanitize_text_field( $data['source_site'] ) : '',
			'source_type'     => 'manual',
		);

		Post_Meta::update_all( $post_id, $meta_data );

		// Also save feed_link for duplicate detection compatibility.
		update_post_meta( $post_id, 'feed_link', esc_url_raw( $data['external_url'] ) );

		return $post_id;
	}

	/**
	 * Validate data for manual post creation.
	 *
	 * @param array $data The data to validate.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	private static function validate_data( $data ) {
		// Validate title.
		if ( empty( $data['title'] ) ) {
			return new WP_Error( 'missing_title', __( 'Title is required', 'feed-favorites' ) );
		}

		// Validate external URL.
		if ( empty( $data['external_url'] ) ) {
			return new WP_Error( 'missing_url', __( 'External URL is required', 'feed-favorites' ) );
		}

		// Validate URL format.
		if ( ! filter_var( $data['external_url'], FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', __( 'Invalid URL format', 'feed-favorites' ) );
		}

		// Check if link_summary is required.
		if ( Config::get( 'link_summary_required', false ) && empty( $data['link_summary'] ) ) {
			return new WP_Error( 'missing_summary', __( 'Link summary is required', 'feed-favorites' ) );
		}

		// Check if commentary is required.
		if ( Config::get( 'commentary_required', false ) && empty( $data['link_commentary'] ) ) {
			return new WP_Error( 'missing_commentary', __( 'Commentary is required', 'feed-favorites' ) );
		}

		return true;
	}
}

