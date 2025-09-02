<?php
/**
 * Plugin Name: Feed Favorites
 * Description: Retrieves starred items from RSS feeds and synchronizes them with WordPress
 * Version: 1.0.0
 * Author: Jason Rouet
 * Author URI: https://jasonrouet.com
 * Plugin URI: https://github.com/jaz-on/feed-favorites
 * License: GPL v2 or later
 * Text Domain: feed-favorites
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 8.2
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'FEED_FAVORITES_VERSION', '1.0.0' );
define( 'FEED_FAVORITES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FEED_FAVORITES_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'FEED_FAVORITES_PLUGIN_FILE', __FILE__ );

// Load main class.
require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-feedfavorites.php';

/**
 * Plugin initialization function.
 */
function feed_favorites_init() {
	return FeedFavorites::get_instance();
}

// Initialize plugin.
add_action( 'plugins_loaded', 'feed_favorites_init' );

/**
 * @package FeedFavorites
 */
