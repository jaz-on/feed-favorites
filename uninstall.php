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

// Clean up all plugin options
$options_to_delete = array(
	'feed_favorites_feed_url',
	'feed_favorites_auto_sync',
	'feed_favorites_sync_interval',
	'feed_favorites_max_items',
	'feed_favorites_default_show_emoji',
	'feed_favorites_default_open_new_tab',
	'feed_favorites_link_summary_required',
	'feed_favorites_commentary_required',
	'feed_favorites_use_link_format',
	'feed_favorites_last_sync',
	'feed_favorites_sync_count',
	'feed_favorites_error_count',
	'feed_favorites_logs',
	'feed_favorites_db_version',
	'feed_favorites_has_template',
	'feed_favorites_system_check_shown',
);

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

// Clean up transients
delete_transient( 'feed_favorites_sync_lock' );

// Clean up scheduled events
wp_clear_scheduled_hook( 'feed_favorites_cron_sync' );

// Clean up custom post types and their data
$post_types = array( 'favorite' );

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

// Clean up taxonomies (if any exist)
$taxonomies = array();

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
