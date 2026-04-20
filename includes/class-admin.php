<?php
/**
 * Feed Favorites Administration Class
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Administration interface management.
 */
class Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( FEED_FAVORITES_PLUGIN_FILE ), array( $this, 'add_plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Load administration scripts.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'favorite_page_feed-favorites' !== $hook ) {
			return;
		}

		// Load CSS.
		wp_enqueue_style(
			'feed-favorites-admin',
			FEED_FAVORITES_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			FEED_FAVORITES_VERSION
		);

		wp_enqueue_script(
			'feed-favorites-admin',
			FEED_FAVORITES_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			FEED_FAVORITES_VERSION,
			true
		);

		// Enable native WordPress postbox toggles (collapse/expand).
		wp_enqueue_script( 'postbox' );
		wp_add_inline_script(
			'feed-favorites-admin',
			'jQuery(function($){ if ( typeof postboxes !== "undefined" ) { postboxes.add_postbox_toggles("favorite_page_feed-favorites"); } });'
		);

		wp_localize_script(
			'feed-favorites-admin',
			'feedFavoritesAjax',
			array(
				'ajaxurl' => esc_url( admin_url( 'admin-ajax.php' ) ),
				'nonce'   => wp_create_nonce( 'feed_favorites_sync' ),
				'strings' => array(
					'syncing'  => __( 'Synchronizing...', 'feed-favorites' ),
					'sync_now' => __( 'Sync Now', 'feed-favorites' ),
					'success'  => __( 'Synchronization successful', 'feed-favorites' ),
					'error'    => __( 'Error during synchronization', 'feed-favorites' ),
				),
			)
		);
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links The existing action links.
	 * @return array Modified action links.
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'edit.php?post_type=favorite&page=feed-favorites' ) ),
			__( 'Settings', 'feed-favorites' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add GitHub and Donate links to the plugin meta row.
	 *
	 * @param array  $plugin_meta An array of plugin row meta links.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 * @return array Plugin row meta links.
	 */
	public function add_plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( plugin_basename( FEED_FAVORITES_PLUGIN_FILE ) !== $plugin_file ) {
			return $plugin_meta;
		}

		$new_links = array(
			sprintf(
				'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
				esc_url( FEED_FAVORITES_GITHUB_URL ),
				esc_html__( 'GitHub', 'feed-favorites' )
			),
			sprintf(
				'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
				esc_url( FEED_FAVORITES_KOFI_URL ),
				esc_html__( 'Donate', 'feed-favorites' )
			),
		);

		return array_merge( $plugin_meta, $new_links );
	}

	/**
	 * Render dashboard tab.
	 *
	 * @param array $stats The statistics data.
	 * @return void
	 */
	public function render_dashboard_tab( $stats ) {
		?>
		<div class="metabox-holder">
			<!-- Overview -->
			<div class="postbox" id="feed-favorites-overview">
				<h2 class="hndle ui-sortable-handle">
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'Overview', 'feed-favorites' ); ?>
				</h2>
				<div class="inside">
					<p class="description"><?php esc_html_e( 'Feed Favorites synchronizes your starred items from RSS feeds to WordPress to display them on your site.', 'feed-favorites' ); ?></p>
					
					<div class="overview-content">
						<div class="overview-section">
							<h4><?php esc_html_e( 'Get Started', 'feed-favorites' ); ?></h4>
							<p class="get-started-text">
								<?php esc_html_e( 'Ready to start importing your RSS favorites?', 'feed-favorites' ); ?>
							</p>
							<a href="?post_type=favorite&page=feed-favorites&tab=setup" class="button button-primary button-hero">
								<span class="dashicons dashicons-admin-settings"></span>
								<?php esc_html_e( 'Go to Setup', 'feed-favorites' ); ?>
							</a>
						</div>
						
											<div class="postbox">
						<h2 class="hndle ui-sortable-handle">
							<span class="dashicons dashicons-admin-generic"></span>
							<?php esc_html_e( 'Requirements & Features', 'feed-favorites' ); ?>
						</h2>
						<div class="inside">
								<!-- Features Section -->
								<div class="features-section">
									<h5><?php esc_html_e( 'Available Features', 'feed-favorites' ); ?></h5>
									<div class="feature-grid">
										<span class="feature-item"><strong><?php esc_html_e( 'Post Type:', 'feed-favorites' ); ?></strong> "favorite"</span>
										<span class="feature-item"><strong><?php esc_html_e( 'Template:', 'feed-favorites' ); ?></strong> single-favorite.php</span>
										<span class="feature-item shortcode-item">
											<strong><?php esc_html_e( 'Shortcode:', 'feed-favorites' ); ?></strong> 
											<div class="shortcode-container">
												<code id="feed-favorites-shortcode">[feed_favorites]</code>
												<button type="button" class="copy-shortcode-btn" data-clipboard-target="#feed-favorites-shortcode" title="<?php esc_attr_e( 'Copy shortcode to clipboard', 'feed-favorites' ); ?>">
													<span class="dashicons dashicons-clipboard"></span>
												</button>
											</div>
										</span>
									</div>
								</div>
								
								<!-- Requirements Section -->
								<div class="requirements-section">
									<h5><?php esc_html_e( 'Requirements', 'feed-favorites' ); ?></h5>
									<div class="requirements-grid">
										<span class="requirement-item">
											<strong><?php esc_html_e( 'RSS Feed', 'feed-favorites' ); ?></strong> 
											<span class="requirement-value"><?php esc_html_e( 'With starred items from any RSS reader service.', 'feed-favorites' ); ?></span>
										</span>
										<span class="requirement-item">
											<strong><?php esc_html_e( 'Theme', 'feed-favorites' ); ?></strong> 
											<span class="requirement-value"><?php esc_html_e( 'Compatible that supports custom post types.', 'feed-favorites' ); ?></span>
										</span>
									</div>
								</div>
						</div>
					</div>
					</div>
				</div>
			</div>
			
			<!-- Statistics -->
			<div class="postbox" id="feed-favorites-statistics">
				<h2 class="hndle ui-sortable-handle">
					<span class="dashicons dashicons-chart-bar"></span>
					<?php esc_html_e( 'Statistics', 'feed-favorites' ); ?>
				</h2>
				<div class="inside">
					<div class="stats-grid">
						<div class="stat-card">
							<div class="stat-number"><?php echo esc_html( $stats['total_posts'] ); ?></div>
							<div class="stat-label"><?php esc_html_e( 'Total Favorites', 'feed-favorites' ); ?></div>
							<div class="stat-description"><?php esc_html_e( 'All favorite posts', 'feed-favorites' ); ?></div>
						</div>
						
						<div class="stat-card">
							<div class="stat-number"><?php echo esc_html( isset( $stats['rss_posts'] ) ? $stats['rss_posts'] : 0 ); ?></div>
							<div class="stat-label"><?php esc_html_e( 'RSS Imported', 'feed-favorites' ); ?></div>
							<div class="stat-description"><?php esc_html_e( 'From RSS feeds', 'feed-favorites' ); ?></div>
						</div>
						
						<div class="stat-card">
							<div class="stat-number"><?php echo esc_html( isset( $stats['manual_posts'] ) ? $stats['manual_posts'] : 0 ); ?></div>
							<div class="stat-label"><?php esc_html_e( 'Manual Posts', 'feed-favorites' ); ?></div>
							<div class="stat-description"><?php esc_html_e( 'Created manually', 'feed-favorites' ); ?></div>
						</div>
						
						<div class="stat-card">
							<div class="stat-number"><?php echo esc_html( $stats['sync_count'] ); ?></div>
							<div class="stat-label"><?php esc_html_e( 'Successful Syncs', 'feed-favorites' ); ?></div>
							<div class="stat-description"><?php esc_html_e( 'Last 30 days', 'feed-favorites' ); ?></div>
						</div>
						
						<div class="stat-card">
							<div class="stat-number"><?php echo esc_html( $stats['error_count'] ); ?></div>
							<div class="stat-label"><?php esc_html_e( 'Errors', 'feed-favorites' ); ?></div>
							<div class="stat-description"><?php esc_html_e( 'Sync issues', 'feed-favorites' ); ?></div>
						</div>
						
						<div class="stat-card">
							<div class="stat-number"><?php echo ! empty( $stats['last_sync'] ) ? esc_html( $stats['last_sync'] ) : '—'; ?></div>
							<div class="stat-label"><?php esc_html_e( 'Last Sync', 'feed-favorites' ); ?></div>
							<div class="stat-description"><?php echo ! empty( $stats['last_sync'] ) ? esc_html__( 'Last synchronization', 'feed-favorites' ) : esc_html__( 'Never', 'feed-favorites' ); ?></div>
						</div>
					</div>
					
					<div class="sync-status">
						<?php if ( ! empty( $stats['last_sync'] ) ) : ?>
							<span class="status-indicator status-success"></span>
							<span class="status-text"><?php esc_html_e( 'Synchronization configured and working', 'feed-favorites' ); ?></span>
						<?php else : ?>
							<span class="status-indicator status-none"></span>
							<span class="status-text"><?php esc_html_e( 'No synchronization configured yet', 'feed-favorites' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render setup tab (Configuration + Import combined)
	 */
	public function render_setup_tab() {
		?>
		<div class="metabox-holder">
			<!-- Initial Import -->
			<div class="postbox">
				<h2 class="hndle ui-sortable-handle">
					<span class="dashicons dashicons-upload"></span>
					<?php esc_html_e( 'Initial Import', 'feed-favorites' ); ?>
				</h2>
				<div class="inside">
					<p class="description"><?php esc_html_e( 'Start by importing your existing starred items from RSS services', 'feed-favorites' ); ?></p>
					
					<div class="import-options">
						<div class="import-method">
							<h4><?php esc_html_e( 'Method 1: JSON Export', 'feed-favorites' ); ?></h4>
							<p><?php esc_html_e( 'Upload a JSON file exported from your RSS reader', 'feed-favorites' ); ?></p>
							<form method="post" enctype="multipart/form-data" class="import-form">
								<input type="file" name="rss_export" accept=".json" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Select your RSS export file (.json)', 'feed-favorites' ); ?></p>
								<input type="submit" name="import_json" value="<?php esc_attr_e( 'Import JSON', 'feed-favorites' ); ?>" class="button button-primary" />
							</form>
						</div>
						
						<div class="import-method">
							<h4><?php esc_html_e( 'Method 2: Manual Creation', 'feed-favorites' ); ?></h4>
							<p><?php esc_html_e( 'Create favorite link posts manually with summary and commentary', 'feed-favorites' ); ?></p>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=favorite' ) ); ?>" class="button button-primary">
								<span class="dashicons dashicons-plus-alt"></span>
								<?php esc_html_e( 'Create Link Post', 'feed-favorites' ); ?>
							</a>
							<p class="description"><?php esc_html_e( 'Manually create favorite posts with external URL, summary, and your personal commentary.', 'feed-favorites' ); ?></p>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Automated Synchronization -->
			<div class="postbox">
				<h2 class="hndle ui-sortable-handle">
					<span class="dashicons dashicons-admin-settings"></span>
					<?php esc_html_e( 'Automated Synchronization', 'feed-favorites' ); ?>
				</h2>
				<div class="inside">
					<p class="description"><?php esc_html_e( 'Configure automatic import from your RSS feed', 'feed-favorites' ); ?></p>
					
					<form method="post" action="options.php" class="setup-form">
						<?php settings_fields( 'feed_favorites_options' ); ?>
						<?php do_settings_sections( 'feed_favorites_options' ); ?>
						
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="rss_feed_url"><?php esc_html_e( 'RSS Feed URL', 'feed-favorites' ); ?></label>
								</th>
								<td>
									<input type="url" 
										id="rss_feed_url" 
										name="feed_favorites_feed_url" 
										value="<?php echo esc_attr( get_option( 'feed_favorites_feed_url' ) ); ?>" 
										class="regular-text"
										placeholder="https://example.com/feed/starred.xml" />
									<p class="description"><?php esc_html_e( 'Your RSS feed URL with starred/favorite items', 'feed-favorites' ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="sync_frequency"><?php esc_html_e( 'Sync Frequency', 'feed-favorites' ); ?></label>
								</th>
								<td>
									<select id="sync_frequency" name="feed_favorites_sync_interval">
										<option value="900" <?php selected( get_option( 'feed_favorites_sync_interval', '7200' ), '900' ); ?>><?php esc_html_e( 'Every 15 minutes', 'feed-favorites' ); ?></option>
										<option value="1800" <?php selected( get_option( 'feed_favorites_sync_interval', '7200' ), '1800' ); ?>><?php esc_html_e( 'Every 30 minutes', 'feed-favorites' ); ?></option>
										<option value="3600" <?php selected( get_option( 'feed_favorites_sync_interval', '7200' ), '3600' ); ?>><?php esc_html_e( 'Every hour', 'feed-favorites' ); ?></option>
										<option value="7200" <?php selected( get_option( 'feed_favorites_sync_interval', '7200' ), '7200' ); ?>><?php esc_html_e( 'Every 2 hours', 'feed-favorites' ); ?></option>
										<option value="14400" <?php selected( get_option( 'feed_favorites_sync_interval', '7200' ), '14400' ); ?>><?php esc_html_e( 'Every 4 hours', 'feed-favorites' ); ?></option>
										<option value="86400" <?php selected( get_option( 'feed_favorites_sync_interval', '7200' ), '86400' ); ?>><?php esc_html_e( 'Daily', 'feed-favorites' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'How often to check for new starred items', 'feed-favorites' ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="max_items"><?php esc_html_e( 'Max Items per Sync', 'feed-favorites' ); ?></label>
								</th>
								<td>
									<input type="number" 
										id="max_items" 
										name="feed_favorites_max_items" 
										value="<?php echo esc_attr( get_option( 'feed_favorites_max_items', '50' ) ); ?>" 
										class="small-text" 
										min="1" 
										max="100" />
									<p class="description"><?php esc_html_e( 'Maximum number of items to import per synchronization', 'feed-favorites' ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="auto_publish"><?php esc_html_e( 'Auto-publish', 'feed-favorites' ); ?></label>
								</th>
								<td>
									<input type="checkbox" 
										id="auto_publish" 
										name="feed_favorites_auto_sync" 
										value="1" 
										<?php checked( get_option( 'feed_favorites_auto_sync', '1' ), '1' ); ?> />
									<label for="auto_publish"><?php esc_html_e( 'Automatically publish imported favorites', 'feed-favorites' ); ?></label>
									<p class="description"><?php esc_html_e( 'If unchecked, items will be saved as drafts', 'feed-favorites' ); ?></p>
								</td>
							</tr>
						</table>
						
						<p class="submit">
							<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'feed-favorites' ); ?>" />
						</p>
					</form>
				</div>
			</div>
			
			<!-- Display Options -->
			<div class="postbox">
				<h2 class="hndle ui-sortable-handle">
					<span class="dashicons dashicons-visibility"></span>
					<?php esc_html_e( 'Display Options', 'feed-favorites' ); ?>
				</h2>
				<div class="inside">
					<p class="description"><?php esc_html_e( 'Configure default display settings for favorite posts', 'feed-favorites' ); ?></p>
					
					<form method="post" action="options.php" class="setup-form">
						<?php settings_fields( 'feed_favorites_options' ); ?>
						
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="default_show_emoji"><?php esc_html_e( 'Show Emoji by Default', 'feed-favorites' ); ?></label>
								</th>
								<td>
									<input type="checkbox" 
										id="default_show_emoji" 
										name="feed_favorites_default_show_emoji" 
										value="1" 
										<?php checked( Config::get( 'default_show_emoji', true ), true ); ?> />
									<label for="default_show_emoji"><?php esc_html_e( 'Display emoji in post titles by default', 'feed-favorites' ); ?></label>
									<p class="description"><?php esc_html_e( 'If enabled, emojis will be shown in favorite post titles. Can be overridden per post.', 'feed-favorites' ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="default_open_new_tab"><?php esc_html_e( 'Open Links in New Tab by Default', 'feed-favorites' ); ?></label>
								</th>
								<td>
									<input type="checkbox" 
										id="default_open_new_tab" 
										name="feed_favorites_default_open_new_tab" 
										value="1" 
										<?php checked( Config::get( 'default_open_new_tab', true ), true ); ?> />
									<label for="default_open_new_tab"><?php esc_html_e( 'Open external links in a new tab by default', 'feed-favorites' ); ?></label>
									<p class="description"><?php esc_html_e( 'If enabled, external links will open in a new tab. Can be overridden per post.', 'feed-favorites' ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="link_summary_required"><?php esc_html_e( 'Require Link Summary', 'feed-favorites' ); ?></label>
								</th>
								<td>
									<input type="checkbox" 
										id="link_summary_required" 
										name="feed_favorites_link_summary_required" 
										value="1" 
										<?php checked( Config::get( 'link_summary_required', false ), true ); ?> />
									<label for="link_summary_required"><?php esc_html_e( 'Make link summary a required field', 'feed-favorites' ); ?></label>
									<p class="description"><?php esc_html_e( 'If enabled, users must provide a summary when creating favorite posts.', 'feed-favorites' ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="commentary_required"><?php esc_html_e( 'Require Commentary', 'feed-favorites' ); ?></label>
								</th>
								<td>
									<input type="checkbox" 
										id="commentary_required" 
										name="feed_favorites_commentary_required" 
										value="1" 
										<?php checked( Config::get( 'commentary_required', false ), true ); ?> />
									<label for="commentary_required"><?php esc_html_e( 'Make commentary a required field', 'feed-favorites' ); ?></label>
									<p class="description"><?php esc_html_e( 'If enabled, users must provide commentary when creating favorite posts.', 'feed-favorites' ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="use_link_format"><?php esc_html_e( 'Use Link Post Format', 'feed-favorites' ); ?></label>
								</th>
								<td>
									<input type="checkbox" 
										id="use_link_format" 
										name="feed_favorites_use_link_format" 
										value="1" 
										<?php checked( Config::get( 'use_link_format', true ), true ); ?> />
									<label for="use_link_format"><?php esc_html_e( 'Automatically set post format to "link" for favorite posts', 'feed-favorites' ); ?></label>
									<p class="description"><?php esc_html_e( 'If enabled, all favorite posts will use the WordPress link post format.', 'feed-favorites' ); ?></p>
								</td>
							</tr>
						</table>
						
						<p class="submit">
							<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Display Options', 'feed-favorites' ); ?>" />
						</p>
					</form>
				</div>
			</div>
			
			<!-- Test and Validation -->
			<div class="postbox">
				<h2 class="hndle ui-sortable-handle">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php esc_html_e( 'Test Configuration', 'feed-favorites' ); ?>
				</h2>
				<div class="inside">
					<p class="description"><?php esc_html_e( 'Verify your setup works correctly', 'feed-favorites' ); ?></p>
					
					<div class="test-actions">
						<button type="button" id="test-feed" class="button button-secondary">
							<span class="dashicons dashicons-admin-links"></span>
							<?php esc_html_e( 'Test Feed Connection', 'feed-favorites' ); ?>
						</button>
						
						<button type="button" id="manual-sync" class="button button-secondary">
							<span class="dashicons dashicons-update"></span>
							<?php esc_html_e( 'Run Manual Sync', 'feed-favorites' ); ?>
						</button>
						
						<div id="test-results" class="test-results" style="display: none;"></div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render maintenance tab.
	 *
	 * @param array $stats The statistics data.
	 * @param array $logs The logs data.
	 * @return void
	 */
	public function render_maintenance_tab( $stats, $logs ) {
		?>
		<div class="metabox-holder">
			<!-- System Status -->
			<div class="postbox">
				<h2 class="hndle ui-sortable-handle">
					<span class="dashicons dashicons-admin-network"></span>
					<?php esc_html_e( 'System Status', 'feed-favorites' ); ?>
				</h2>
				<div class="inside">
					<p class="description"><?php esc_html_e( 'Check if your server meets the plugin requirements', 'feed-favorites' ); ?></p>
					<?php echo wp_kses_post( Components::render_system_check() ); ?>
				</div>
			</div>
			
			<!-- Recent Logs -->
			<div class="postbox">
				<h2 class="hndle ui-sortable-handle">
					<span class="dashicons dashicons-list-view"></span>
					<?php esc_html_e( 'Recent Logs', 'feed-favorites' ); ?>
				</h2>
				<div class="inside">
					<?php echo wp_kses_post( Components::render_recent_logs( $logs ) ); ?>
				</div>
			</div>
			
			<!-- Maintenance Tools -->
			<div class="postbox">
				<h2 class="hndle ui-sortable-handle">
					<span class="dashicons dashicons-admin-tools"></span>
					<?php esc_html_e( 'Maintenance Tools', 'feed-favorites' ); ?>
				</h2>
				<div class="inside">
					<div class="maintenance-intro">
						<div class="notice notice-warning is-dismissible" id="feed-favorites-maintenance-notice">
							<button type="button" class="notice-dismiss">
								<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'feed-favorites' ); ?></span>
							</button>
							<p><strong><?php esc_html_e( 'Warning: These actions are irreversible!', 'feed-favorites' ); ?></strong></p>
							<p><?php esc_html_e( 'Resetting statistics does not delete synchronized articles. Only counters and logs are affected.', 'feed-favorites' ); ?></p>
						</div>
					</div>
					
					<?php echo wp_kses_post( Components::render_reset_buttons() ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}
