<?php
/**
 * Feed Favorites Frontend Filters Class
 *
 * Injects content when no template is available.
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend content filters management.
 */
class Frontend_Filters {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Check dynamically via template_redirect hook.
		add_action( 'template_redirect', array( $this, 'check_template_and_setup' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Check if template exists and setup filters accordingly.
	 *
	 * @return void
	 */
	public function check_template_and_setup() {
		if ( ! is_singular( 'favorite' ) ) {
			return;
		}

		// Only activate content filter if no template found.
		if ( ! $this->should_inject_content() ) {
			return;
		}

		add_filter( 'the_content', array( $this, 'filter_content' ), 20 );
	}

	/**
	 * Check if content should be injected.
	 *
	 * Dynamically checks if theme has template.
	 *
	 * @return bool True if content should be injected.
	 */
	private function should_inject_content() {
		// Check if theme has template.
		$theme_template = locate_template( array( 'single-favorite.php', 'content-favorite.php' ) );
		if ( $theme_template ) {
			// Update option cache.
			update_option( 'feed_favorites_has_template', true );
			return false;
		}

		// Update option cache.
		update_option( 'feed_favorites_has_template', false );
		return true;
	}

	/**
	 * Filter post content to inject link structure.
	 *
	 * @param string $content The post content.
	 * @return string Modified content.
	 */
	public function filter_content( $content ) {
		global $post;

		// Only for favorite posts.
		if ( ! $post || 'favorite' !== $post->post_type ) {
			return $content;
		}

		// Only on single posts.
		if ( ! is_singular( 'favorite' ) ) {
			return $content;
		}

		// Get meta data.
		$external_url    = Template_Tags::get_external_url();
		$link_summary    = Template_Tags::get_summary();
		$link_commentary = Template_Tags::get_commentary();
		$source_author   = Template_Tags::get_source_author();
		$source_site     = Template_Tags::get_source_site();

		// Build structured HTML.
		$html = '<div class="feed-favorite-post">';

		// Link summary section.
		if ( ! empty( $link_summary ) ) {
			$html .= '<div class="feed-favorite-summary">';
			$html .= wp_kses_post( $link_summary );
			$html .= '</div>';
		}

		// Commentary section.
		if ( ! empty( $link_commentary ) ) {
			$html .= '<div class="feed-favorite-commentary">';
			$html .= wp_kses_post( $link_commentary );
			$html .= '</div>';
		}

		// External link button.
		if ( ! empty( $external_url ) ) {
			$link_text = __( 'Read Original', 'feed-favorites' );
			$open_new_tab = get_post_meta( $post->ID, '_feed_favorites_open_new_tab', true );
			if ( '' === $open_new_tab ) {
				$open_new_tab = Config::get( 'default_open_new_tab', true );
			} else {
				$open_new_tab = (bool) $open_new_tab;
			}

			$target = $open_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';

			$html .= '<div class="feed-favorite-link">';
			$html .= sprintf(
				'<a href="%s" class="feed-favorites-external-link button"%s>%s</a>',
				esc_url( $external_url ),
				$target,
				esc_html( $link_text )
			);
			$html .= '</div>';
		}

		// Source attribution.
		if ( ! empty( $source_author ) || ! empty( $source_site ) ) {
			$html .= '<div class="feed-favorite-source">';
			$html .= Template_Tags::get_source_attribution();
			$html .= '</div>';
		}

		$html .= '</div>';

		// Prepend to content.
		return $html . $content;
	}

	/**
	 * Enqueue frontend styles.
	 *
	 * Only enqueue if no theme template exists.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		if ( ! is_singular( 'favorite' ) ) {
			return;
		}

		// Only enqueue if we're injecting content (no theme template).
		if ( $this->should_inject_content() ) {
			wp_enqueue_style(
				'feed-favorites-frontend',
				FEED_FAVORITES_PLUGIN_URL . 'assets/css/frontend.css',
				array(),
				FEED_FAVORITES_VERSION
			);
		}
	}
}
