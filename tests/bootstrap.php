<?php
/**
 * PHPUnit bootstrap file for Feed Favorites Plugin
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

// Load WordPress test environment
if (file_exists(getenv('WP_TESTS_DIR') . '/includes/bootstrap.php')) {
    require_once getenv('WP_TESTS_DIR') . '/includes/bootstrap.php';
} else {
    // Fallback for local development
    require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-tests-lib/includes/bootstrap.php';
}

// Load plugin
require_once dirname(__DIR__) . '/feed-favorites.php';

// Initialize plugin for testing
if (function_exists('feed_favorites_init')) {
    feed_favorites_init();
}

// Helper functions for testing
if (!function_exists('create_test_post')) {
    /**
     * Create a test post for testing purposes
     */
    function create_test_post($args = array()) {
        $defaults = array(
            'post_title' => 'Test Post',
            'post_content' => 'Test content',
            'post_status' => 'publish',
            'post_type' => 'favorite',
            'post_author' => 1
        );
        
        $args = wp_parse_args($args, $defaults);
        
        return wp_insert_post($args);
    }
}

if (!function_exists('create_test_user')) {
    /**
     * Create a test user for testing purposes
     */
    function create_test_user($args = array()) {
        $defaults = array(
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => 'password',
            'role' => 'administrator'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        return wp_insert_user($args);
    }
}
