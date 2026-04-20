<?php
/**
 * Feed Favorites SEO Integration Class
 *
 * Adds SEO meta tags for favorite link posts.
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEO integration management.
 */
class SEO_Integration {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'add_meta_tags' ) );
		add_action( 'wp_head', array( $this, 'add_structured_data' ) );
	}

	/**
	 * Whether a known third-party SEO plugin is active.
	 *
	 * @return bool
	 */
	private function detect_third_party_seo_plugin() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return true;
		}
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			return true;
		}
		if ( defined( 'AIOSEO_VERSION' ) || defined( 'AIOSEO_PHP_VERSION_DIR' ) ) {
			return true;
		}
		if ( defined( 'SEOPRESS_VERSION' ) ) {
			return true;
		}
		if ( defined( 'THE_SEO_FRAMEWORK_VERSION' ) || class_exists( 'The_SEO_Framework\Load', false ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Whether this plugin should output its own meta tags and JSON-LD.
	 *
	 * @return bool
	 */
	private function should_output_seo() {
		$detected = $this->detect_third_party_seo_plugin();

		/**
		 * Filters whether Feed Favorites prints default SEO meta and structured data.
		 *
		 * Default is false when a known SEO plugin is active, to avoid duplicate tags.
		 *
		 * @since 1.0.2
		 *
		 * @param bool $output Default output decision.
		 */
		return (bool) apply_filters( 'feed_favorites_output_seo_meta', ! $detected );
	}

	/**
	 * Add meta tags for SEO.
	 *
	 * @return void
	 */
	public function add_meta_tags() {
		if ( ! $this->should_output_seo() ) {
			return;
		}

		if ( ! is_singular( 'favorite' ) ) {
			return;
		}

		global $post;
		$post_id = $post->ID;

		// Get meta data.
		$external_url = Template_Tags::get_external_url( $post_id );
		$link_summary = Template_Tags::get_summary( $post_id );
		$title        = get_the_title( $post_id );

		// Meta description from summary.
		if ( ! empty( $link_summary ) ) {
			$description = wp_strip_all_tags( $link_summary );
			$description = wp_trim_words( $description, 30, '...' );
			echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
		}

		// OpenGraph tags.
		if ( ! empty( $external_url ) ) {
			echo '<meta property="og:url" content="' . esc_url( get_permalink( $post_id ) ) . '" />' . "\n";
			echo '<meta property="og:type" content="article" />' . "\n";
			echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
			if ( ! empty( $link_summary ) ) {
				$og_description = wp_strip_all_tags( $link_summary );
				$og_description = wp_trim_words( $og_description, 30, '...' );
				echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '" />' . "\n";
			}
			if ( has_post_thumbnail( $post_id ) ) {
				$thumbnail_url = get_the_post_thumbnail_url( $post_id, 'large' );
				if ( $thumbnail_url ) {
					echo '<meta property="og:image" content="' . esc_url( $thumbnail_url ) . '" />' . "\n";
				}
			}
		}

		// Twitter Card tags.
		echo '<meta name="twitter:card" content="summary" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
		if ( ! empty( $link_summary ) ) {
			$twitter_description  = wp_strip_all_tags( $link_summary );
			$twitter_description = wp_trim_words( $twitter_description, 30, '...' );
			echo '<meta name="twitter:description" content="' . esc_attr( $twitter_description ) . '" />' . "\n";
		}
		if ( has_post_thumbnail( $post_id ) ) {
			$thumbnail_url = get_the_post_thumbnail_url( $post_id, 'large' );
			if ( $thumbnail_url ) {
				echo '<meta name="twitter:image" content="' . esc_url( $thumbnail_url ) . '" />' . "\n";
			}
		}
	}

	/**
	 * Add structured data (JSON-LD).
	 *
	 * @return void
	 */
	public function add_structured_data() {
		if ( ! $this->should_output_seo() ) {
			return;
		}

		if ( ! is_singular( 'favorite' ) ) {
			return;
		}

		global $post;
		$post_id = $post->ID;

		// Get meta data.
		$external_url  = Template_Tags::get_external_url( $post_id );
		$link_summary  = Template_Tags::get_summary( $post_id );
		$source_author = Template_Tags::get_source_author( $post_id );
		$source_site   = Template_Tags::get_source_site( $post_id );

		$structured_data = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'Article',
			'headline'      => get_the_title( $post_id ),
			'datePublished' => get_the_date( 'c', $post_id ),
			'dateModified'  => get_the_modified_date( 'c', $post_id ),
		);

		if ( ! empty( $link_summary ) ) {
			$structured_data['description'] = wp_strip_all_tags( $link_summary );
		}

		if ( ! empty( $external_url ) ) {
			$structured_data['url'] = esc_url( $external_url );
		}

		if ( ! empty( $source_author ) ) {
			$structured_data['author'] = array(
				'@type' => 'Person',
				'name'  => $source_author,
			);
		}

		if ( ! empty( $source_site ) ) {
			$structured_data['publisher'] = array(
				'@type' => 'Organization',
				'name'  => $source_site,
			);
		}

		if ( has_post_thumbnail( $post_id ) ) {
			$thumbnail_url = get_the_post_thumbnail_url( $post_id, 'large' );
			if ( $thumbnail_url ) {
				$structured_data['image'] = esc_url( $thumbnail_url );
			}
		}

		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $structured_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		echo "\n" . '</script>' . "\n";
	}
}
