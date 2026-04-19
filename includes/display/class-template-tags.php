<?php
/**
 * Feed Favorites Template Tags Class
 *
 * Provides template functions for displaying favorite posts.
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template tags management.
 */
class Template_Tags {

	/**
	 * Get external URL.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return string External URL.
	 */
	public static function get_external_url( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		return Post_Meta::get( $post_id, Post_Meta::EXTERNAL_URL );
	}

	/**
	 * Display external URL.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return void
	 */
	public static function the_external_url( $post_id = null ) {
		echo esc_url( self::get_external_url( $post_id ) );
	}

	/**
	 * Get link summary.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return string Link summary.
	 */
	public static function get_summary( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		return Post_Meta::get( $post_id, Post_Meta::LINK_SUMMARY );
	}

	/**
	 * Display link summary.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return void
	 */
	public static function the_summary( $post_id = null ) {
		echo wp_kses_post( self::get_summary( $post_id ) );
	}

	/**
	 * Get link commentary.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return string Link commentary.
	 */
	public static function get_commentary( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		return Post_Meta::get( $post_id, Post_Meta::LINK_COMMENTARY );
	}

	/**
	 * Display link commentary.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return void
	 */
	public static function the_commentary( $post_id = null ) {
		echo wp_kses_post( self::get_commentary( $post_id ) );
	}

	/**
	 * Get external link HTML.
	 *
	 * @param int    $post_id Optional. Post ID. Defaults to current post.
	 * @param string $text Optional. Link text. Defaults to URL.
	 * @param string $class Optional. CSS class.
	 * @return string Link HTML.
	 */
	public static function get_external_link( $post_id = null, $text = null, $class = 'feed-favorites-external-link' ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		$url = self::get_external_url( $post_id );
		if ( empty( $url ) ) {
			return '';
		}

		if ( null === $text ) {
			$text = $url;
		}

		$open_new_tab = get_post_meta( $post_id, '_feed_favorites_open_new_tab', true );
		if ( '' === $open_new_tab ) {
			$open_new_tab = Config::get( 'default_open_new_tab', true );
		} else {
			$open_new_tab = (bool) $open_new_tab;
		}

		$target = $open_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';

		return sprintf(
			'<a href="%s" class="%s"%s>%s</a>',
			esc_url( $url ),
			esc_attr( $class ),
			$target,
			esc_html( $text )
		);
	}

	/**
	 * Display external link.
	 *
	 * @param int    $post_id Optional. Post ID. Defaults to current post.
	 * @param string $text Optional. Link text. Defaults to URL.
	 * @param string $class Optional. CSS class.
	 * @return void
	 */
	public static function the_external_link( $post_id = null, $text = null, $class = 'feed-favorites-external-link' ) {
		echo self::get_external_link( $post_id, $text, $class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in get_external_link.
	}

	/**
	 * Get source author.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return string Source author.
	 */
	public static function get_source_author( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		return Post_Meta::get( $post_id, Post_Meta::SOURCE_AUTHOR );
	}

	/**
	 * Display source author.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return void
	 */
	public static function the_source_author( $post_id = null ) {
		echo esc_html( self::get_source_author( $post_id ) );
	}

	/**
	 * Get source site.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return string Source site.
	 */
	public static function get_source_site( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		return Post_Meta::get( $post_id, Post_Meta::SOURCE_SITE );
	}

	/**
	 * Display source site.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return void
	 */
	public static function the_source_site( $post_id = null ) {
		echo esc_html( self::get_source_site( $post_id ) );
	}

	/**
	 * Get source attribution HTML.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return string Attribution HTML.
	 */
	public static function get_source_attribution( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		$author = self::get_source_author( $post_id );
		$site   = self::get_source_site( $post_id );

		if ( empty( $author ) && empty( $site ) ) {
			return '';
		}

		$parts = array();
		if ( ! empty( $author ) ) {
			$parts[] = esc_html( $author );
		}
		if ( ! empty( $site ) ) {
			$parts[] = esc_html( $site );
		}

		return '<span class="feed-favorites-source-attribution">' . implode( ' / ', $parts ) . '</span>';
	}

	/**
	 * Display source attribution.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return void
	 */
	public static function the_source_attribution( $post_id = null ) {
		echo self::get_source_attribution( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in get_source_attribution.
	}

	/**
	 * Check if post is a link post.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return bool True if link post.
	 */
	public static function is_link( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		return Post_Meta::is_link_post( $post_id );
	}

	/**
	 * Check if post is manually created.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return bool True if manually created.
	 */
	public static function is_manual( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		$source_type = Post_Meta::get( $post_id, Post_Meta::SOURCE_TYPE, 'rss_auto' );
		return 'manual' === $source_type;
	}

	/**
	 * Check if post is RSS imported.
	 *
	 * @param int $post_id Optional. Post ID. Defaults to current post.
	 * @return bool True if RSS imported.
	 */
	public static function is_rss_import( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		$source_type = Post_Meta::get( $post_id, Post_Meta::SOURCE_TYPE, 'rss_auto' );
		return 'rss_auto' === $source_type;
	}
}
