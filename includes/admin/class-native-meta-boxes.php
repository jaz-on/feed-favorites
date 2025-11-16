<?php
/**
 * Feed Favorites Native Meta Boxes Class
 *
 * Manages native WordPress meta boxes for favorite posts.
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Native meta boxes management.
 */
class Native_Meta_Boxes {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add meta boxes.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'feed-favorites-link-details',
			__( 'Link Details', 'feed-favorites' ),
			array( $this, 'render_link_details_box' ),
			'favorite',
			'normal',
			'high'
		);

		add_meta_box(
			'feed-favorites-display-options',
			__( 'Display Options', 'feed-favorites' ),
			array( $this, 'render_display_options_box' ),
			'favorite',
			'side',
			'default'
		);
	}

	/**
	 * Render link details meta box.
	 *
	 * @param WP_Post $post The post object.
	 * @return void
	 */
	public function render_link_details_box( $post ) {
		// Security nonce.
		wp_nonce_field( 'feed_favorites_save_meta', 'feed_favorites_meta_nonce' );

		// Get current values.
		$external_url    = Post_Meta::get( $post->ID, Post_Meta::EXTERNAL_URL );
		$link_summary    = Post_Meta::get( $post->ID, Post_Meta::LINK_SUMMARY );
		$link_commentary = Post_Meta::get( $post->ID, Post_Meta::LINK_COMMENTARY );
		$source_author   = Post_Meta::get( $post->ID, Post_Meta::SOURCE_AUTHOR );
		$source_site     = Post_Meta::get( $post->ID, Post_Meta::SOURCE_SITE );
		$source_type     = Post_Meta::get( $post->ID, Post_Meta::SOURCE_TYPE, 'rss_auto' );

		// Check if post is from RSS import.
		$is_rss_import = 'rss_auto' === $source_type;

		?>
		<div class="feed-favorites-meta-box">
			<?php if ( $is_rss_import ) : ?>
				<div class="notice notice-info inline">
					<p><?php esc_html_e( 'This post was imported from RSS. Some fields may be read-only.', 'feed-favorites' ); ?></p>
				</div>
			<?php endif; ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="feed_favorites_external_url"><?php esc_html_e( 'External URL', 'feed-favorites' ); ?></label>
						<span class="required">*</span>
					</th>
					<td>
						<input type="url" 
							id="feed_favorites_external_url" 
							name="feed_favorites_external_url" 
							value="<?php echo esc_attr( $external_url ); ?>" 
							class="regular-text" 
							required />
						<button type="button" class="button button-small" id="feed-favorites-preview-url">
							<?php esc_html_e( 'Preview', 'feed-favorites' ); ?>
						</button>
						<p class="description"><?php esc_html_e( 'The external URL this favorite links to', 'feed-favorites' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="feed_favorites_link_summary"><?php esc_html_e( 'Link Summary', 'feed-favorites' ); ?></label>
						<?php if ( Config::get( 'link_summary_required', false ) ) : ?>
							<span class="required">*</span>
						<?php endif; ?>
					</th>
					<td>
						<textarea 
							id="feed_favorites_link_summary" 
							name="feed_favorites_link_summary" 
							rows="4" 
							class="large-text"
							<?php echo Config::get( 'link_summary_required', false ) ? 'required' : ''; ?>><?php echo esc_textarea( $link_summary ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'A brief summary or excerpt from the original article', 'feed-favorites' ); ?>
							<span class="char-count" id="summary-char-count"></span>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="feed_favorites_link_commentary"><?php esc_html_e( 'Commentary', 'feed-favorites' ); ?></label>
						<?php if ( Config::get( 'commentary_required', false ) ) : ?>
							<span class="required">*</span>
						<?php endif; ?>
					</th>
					<td>
						<?php
						wp_editor(
							$link_commentary,
							'feed_favorites_link_commentary',
							array(
								'textarea_name' => 'feed_favorites_link_commentary',
								'textarea_rows' => 6,
								'media_buttons' => false,
								'teeny'         => true,
							)
						);
						?>
						<p class="description"><?php esc_html_e( 'Your personal thoughts or commentary about this link', 'feed-favorites' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="feed_favorites_source_author"><?php esc_html_e( 'Source Author', 'feed-favorites' ); ?></label>
					</th>
					<td>
						<input type="text" 
							id="feed_favorites_source_author" 
							name="feed_favorites_source_author" 
							value="<?php echo esc_attr( $source_author ); ?>" 
							class="regular-text" />
						<p class="description"><?php esc_html_e( 'Author of the original article', 'feed-favorites' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="feed_favorites_source_site"><?php esc_html_e( 'Source Site', 'feed-favorites' ); ?></label>
					</th>
					<td>
						<input type="text" 
							id="feed_favorites_source_site" 
							name="feed_favorites_source_site" 
							value="<?php echo esc_attr( $source_site ); ?>" 
							class="regular-text" />
						<p class="description"><?php esc_html_e( 'Name of the source website or publication', 'feed-favorites' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render display options meta box.
	 *
	 * @param WP_Post $post The post object.
	 * @return void
	 */
	public function render_display_options_box( $post ) {
		// Get current values.
		$show_emoji = get_post_meta( $post->ID, '_feed_favorites_show_emoji', true );
		$show_emoji = '' === $show_emoji ? Config::get( 'default_show_emoji', true ) : (bool) $show_emoji;

		$open_new_tab = get_post_meta( $post->ID, '_feed_favorites_open_new_tab', true );
		$open_new_tab = '' === $open_new_tab ? Config::get( 'default_open_new_tab', true ) : (bool) $open_new_tab;

		?>
		<div class="feed-favorites-display-options">
			<p>
				<label>
					<input type="checkbox" 
						name="feed_favorites_show_emoji" 
						value="1" 
						<?php checked( $show_emoji, true ); ?> />
					<?php esc_html_e( 'Show emoji in title', 'feed-favorites' ); ?>
				</label>
			</p>
			<p>
				<label>
					<input type="checkbox" 
						name="feed_favorites_open_new_tab" 
						value="1" 
						<?php checked( $open_new_tab, true ); ?> />
					<?php esc_html_e( 'Open link in new tab', 'feed-favorites' ); ?>
				</label>
			</p>
		</div>
		<?php
	}

	/**
	 * Save meta boxes.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post The post object.
	 * @return void
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Security checks.
		if ( 'favorite' !== $post->post_type ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['feed_favorites_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['feed_favorites_meta_nonce'] ) ), 'feed_favorites_save_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save meta fields.
		if ( isset( $_POST['feed_favorites_external_url'] ) ) {
			$external_url = esc_url_raw( wp_unslash( $_POST['feed_favorites_external_url'] ) );
			Post_Meta::update( $post_id, Post_Meta::EXTERNAL_URL, $external_url );
			// Also update feed_link for compatibility.
			update_post_meta( $post_id, 'feed_link', $external_url );
		}

		if ( isset( $_POST['feed_favorites_link_summary'] ) ) {
			Post_Meta::update( $post_id, Post_Meta::LINK_SUMMARY, wp_kses_post( wp_unslash( $_POST['feed_favorites_link_summary'] ) ) );
		}

		if ( isset( $_POST['feed_favorites_link_commentary'] ) ) {
			Post_Meta::update( $post_id, Post_Meta::LINK_COMMENTARY, wp_kses_post( wp_unslash( $_POST['feed_favorites_link_commentary'] ) ) );
		}

		if ( isset( $_POST['feed_favorites_source_author'] ) ) {
			Post_Meta::update( $post_id, Post_Meta::SOURCE_AUTHOR, sanitize_text_field( wp_unslash( $_POST['feed_favorites_source_author'] ) ) );
		}

		if ( isset( $_POST['feed_favorites_source_site'] ) ) {
			Post_Meta::update( $post_id, Post_Meta::SOURCE_SITE, sanitize_text_field( wp_unslash( $_POST['feed_favorites_source_site'] ) ) );
		}

		// Set source_type to manual if not already set (for new posts).
		$source_type = Post_Meta::get( $post_id, Post_Meta::SOURCE_TYPE );
		if ( empty( $source_type ) ) {
			Post_Meta::update( $post_id, Post_Meta::SOURCE_TYPE, 'manual' );
		}

		// Save display options.
		$show_emoji = isset( $_POST['feed_favorites_show_emoji'] ) ? 1 : 0;
		update_post_meta( $post_id, '_feed_favorites_show_emoji', $show_emoji );

		$open_new_tab = isset( $_POST['feed_favorites_open_new_tab'] ) ? 1 : 0;
		update_post_meta( $post_id, '_feed_favorites_open_new_tab', $open_new_tab );

		// Set post format to 'link' if enabled.
		if ( Config::get( 'use_link_format', true ) ) {
			set_post_format( $post_id, 'link' );
		}
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		global $post_type;

		if ( 'favorite' !== $post_type || ( 'post.php' !== $hook && 'post-new.php' !== $hook ) ) {
			return;
		}

		wp_enqueue_script(
			'feed-favorites-meta-box',
			FEED_FAVORITES_PLUGIN_URL . 'assets/js/meta-box.js',
			array( 'jquery' ),
			FEED_FAVORITES_VERSION,
			true
		);

		wp_localize_script(
			'feed-favorites-meta-box',
			'feedFavoritesMetaBox',
			array(
				'strings'                => array(
					'urlRequired'      => __( 'Please enter a URL first.', 'feed-favorites' ),
					'invalidUrl'       => __( 'Invalid URL format.', 'feed-favorites' ),
					'summaryRequired'  => __( 'Link summary is required.', 'feed-favorites' ),
					'commentaryRequired' => __( 'Commentary is required.', 'feed-favorites' ),
				),
				'linkSummaryRequired'   => Config::get( 'link_summary_required', false ),
				'commentaryRequired'    => Config::get( 'commentary_required', false ),
			)
		);
	}
}

