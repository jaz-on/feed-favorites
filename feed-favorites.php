<?php
/**
 * Plugin Name: Feed Favorites
 * Plugin URI: https://github.com/jaz-on/feed-favorites
 * Description: Synchronizes starred items from RSS feeds into WordPress so you can curate and display them on your site.
 * Version: 1.0.1
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 8.2
 * Author: Jason Rouet
 * Author URI: https://jasonrouet.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: feed-favorites
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/jaz-on/feed-favorites
 * Primary Branch: main
 *
 * @package FeedFavorites
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'FEED_FAVORITES_VERSION', '1.0.1' );
define( 'FEED_FAVORITES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FEED_FAVORITES_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'FEED_FAVORITES_PLUGIN_FILE', __FILE__ );
define( 'FEED_FAVORITES_GITHUB_URL', 'https://github.com/jaz-on/feed-favorites' );
define( 'FEED_FAVORITES_KOFI_URL', 'https://ko-fi.com/jasonrouet' );

// Load main class.
require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-feedfavorites.php';

/**
 * Bootstrap the plugin (singleton).
 *
 * @return FeedFavorites Main plugin instance.
 */
function feed_favorites_init() {
	return FeedFavorites::get_instance();
}

// Initialize plugin.
add_action( 'plugins_loaded', 'feed_favorites_init' );
