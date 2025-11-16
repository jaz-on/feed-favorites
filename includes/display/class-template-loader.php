<?php
/**
 * Feed Favorites Template Loader Class
 *
 * Handles template loading for favorite posts.
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template loader management.
 */
class Template_Loader {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'single_template', array( $this, 'load_template' ) );
		add_filter( 'template_include', array( $this, 'template_include' ) );
	}

	/**
	 * Load single template for favorite posts.
	 *
	 * @param string $template The template path.
	 * @return string Modified template path.
	 */
	public function load_template( $template ) {
		global $post;

		if ( 'favorite' === $post->post_type ) {
			// Check for theme template first.
			$theme_template = locate_template( array( 'single-favorite.php' ) );

			if ( $theme_template ) {
				return $theme_template;
			}

			// Fallback to plugin template.
			$plugin_template = FEED_FAVORITES_PLUGIN_PATH . 'templates/single-favorite.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}

	/**
	 * Template include filter.
	 *
	 * @param string $template The template path.
	 * @return string Modified template path.
	 */
	public function template_include( $template ) {
		if ( is_singular( 'favorite' ) ) {
			// Check if theme has template.
			$theme_template = locate_template( array( 'single-favorite.php', 'content-favorite.php' ) );

			if ( $theme_template ) {
				// Theme has template, mark that we should use it.
				update_option( 'feed_favorites_has_template', true );
				return $template; // Let WordPress use the theme template.
			}

			// No theme template, mark for content filter injection.
			update_option( 'feed_favorites_has_template', false );

			// Check if plugin template exists.
			$plugin_template = FEED_FAVORITES_PLUGIN_PATH . 'templates/single-favorite.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}

	/**
	 * Check if theme has template.
	 *
	 * Uses option for cache instead of transient for better reliability.
	 *
	 * @return bool True if theme has template.
	 */
	public static function theme_has_template() {
		$has_template = get_option( 'feed_favorites_has_template', null );
		if ( null === $has_template ) {
			$has_template = (bool) locate_template( array( 'single-favorite.php', 'content-favorite.php' ) );
			update_option( 'feed_favorites_has_template', $has_template );
		}
		return (bool) $has_template;
	}
}

