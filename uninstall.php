<?php
/**
 * Uninstall script for Feed Favorites plugin
 *
 * This file is executed when the plugin is deleted from WordPress admin.
 * It cleans up all plugin data including options, custom post types, and files.
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Security check
if ( ! current_user_can( 'activate_plugins' ) ) {
	exit;
}

// Clean up options
delete_option( 'feed_favorites_settings' );
delete_option( 'feed_favorites_last_sync' );
delete_option( 'feed_favorites_sync_status' );

// Clean up transients
delete_transient( 'feed_favorites_sync_lock' );
delete_transient( 'feed_favorites_api_cache' );

// Clean up scheduled events
wp_clear_scheduled_hook( 'feed_favorites_cron_sync' );

// Clean up custom post types and their data
$post_types = array( 'feed_favorite' );

foreach ( $post_types as $post_type ) {
	// Get all posts of this type
	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	// Delete each post
	foreach ( $posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}
}

// Clean up taxonomies
$taxonomies = array( 'feed_favorite_category' );

foreach ( $taxonomies as $taxonomy ) {
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		)
	);

	foreach ( $terms as $term ) {
		wp_delete_term( $term->term_id, $taxonomy );
	}
}

// Clean up uploaded files (if any)
$upload_dir = wp_upload_dir();
$plugin_upload_dir = $upload_dir['basedir'] . '/feed-favorites/';

if ( is_dir( $plugin_upload_dir ) ) {
	// Remove the entire directory
	require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

	$filesystem = new WP_Filesystem_Direct( null );
	$filesystem->rmdir( $plugin_upload_dir, true );
}

// Flush rewrite rules
flush_rewrite_rules();
