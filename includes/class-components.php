<?php
/**
 * Feed Favorites Administration Components Class
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reusable components for administration interface
 */
class Components {

	/**
	 * Render statistics cards
	 */
	public static function render_stats_cards( $stats ) {
		$total_syncs = isset( $stats['sync_count'] ) && isset( $stats['error_count'] ) ? $stats['sync_count'] + $stats['error_count'] : 0;

		$cards = array(
			array(
				'icon'  => 'dashicons-admin-post',
				'value' => $stats['total_posts'],
				'label' => __( 'Synchronized Articles', 'feed-favorites' ),
				'class' => '',
			),
			array(
				'icon'  => 'dashicons-yes-alt',
				'value' => $stats['sync_count'],
				'label' => __( 'Successful Syncs', 'feed-favorites' ),
				'class' => '',
			),
			array(
				'icon'  => 'dashicons-warning',
				'value' => $stats['error_count'],
				'label' => __( 'Errors', 'feed-favorites' ),
				'class' => '',
			),
			array(
				'icon'  => 'dashicons-clock',
				'value' => $stats['last_sync'] ? date_i18n( 'd/m/Y H:i', strtotime( $stats['last_sync'] ) ) : __( 'Never', 'feed-favorites' ),
				'label' => __( 'Last Sync', 'feed-favorites' ),
				'class' => 'rss-text-small',
			),
		);

		$calculated = array(
			array(
				'icon'   => 'dashicons-chart-line',
				'value'  => $total_syncs > 0 ? round( ( $stats['sync_count'] / $total_syncs ) * 100, 1 ) : 0,
				'label'  => __( 'Success Rate', 'feed-favorites' ),
				'suffix' => '%',
			),
			array(
				'icon'  => 'dashicons-chart-bar',
				'value' => $stats['sync_count'] > 0 ? round( $stats['total_posts'] / $stats['sync_count'], 1 ) : 0,
				'label' => __( 'Average/Sync', 'feed-favorites' ),
			),
		);

		ob_start();
		?>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Metric', 'feed-favorites' ); ?></th>
					<th><?php esc_html_e( 'Value', 'feed-favorites' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $cards as $card ) : ?>
					<tr>
						<td>
							<span class="dashicons <?php echo esc_attr( $card['icon'] ); ?>"></span>
							<?php echo esc_html( $card['label'] ); ?>
						</td>
						<td><strong><?php echo esc_html( $card['value'] ); ?></strong></td>
					</tr>
				<?php endforeach; ?>
				
				<?php foreach ( $calculated as $card ) : ?>
					<tr>
						<td>
							<span class="dashicons <?php echo esc_attr( $card['icon'] ); ?>"></span>
							<?php echo esc_html( $card['label'] ); ?>
						</td>
						<td><strong><?php echo esc_html( $card['value'] ); ?><?php echo isset( $card['suffix'] ) ? esc_html( $card['suffix'] ) : ''; ?></strong></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render JSON import interface
	 */
	public static function render_json_import() {
		ob_start();
		?>
		<div class="metabox-holder">
			<!-- Import Overview -->
			<div class="overview-section" id="feed-favorites-import-overview">
				<h4 class="collapsible-section" data-target="import-overview-content">
					<span class="dashicons dashicons-arrow-down-alt2 toggle-icon"></span>
					<span class="dashicons dashicons-upload"></span>
					<?php esc_html_e( 'Import Overview', 'feed-favorites' ); ?>
				</h4>
				<div id="import-overview-content" class="collapsible-content">
					<h4><?php esc_html_e( 'Complete import of all your historical favorites', 'feed-favorites' ); ?></h4>
					<p><?php esc_html_e( 'This method allows you to import ALL your RSS favorites, even the oldest ones. Ideal for creating a complete history.', 'feed-favorites' ); ?></p>
					
					<h4><?php esc_html_e( 'Recommendations for large imports:', 'feed-favorites' ); ?></h4>
					<ul>
						<li><strong><?php esc_html_e( 'Batch size:', 'feed-favorites' ); ?></strong> <?php esc_html_e( '20 articles per batch to avoid server overload', 'feed-favorites' ); ?></li>
						<li><strong><?php esc_html_e( 'Import limit:', 'feed-favorites' ); ?></strong> <?php esc_html_e( 'Start with 50 articles to test', 'feed-favorites' ); ?></li>
						<li><strong><?php esc_html_e( 'Patience:', 'feed-favorites' ); ?></strong> <?php esc_html_e( 'An import of 1500 articles can take 5-10 minutes', 'feed-favorites' ); ?></li>
					</ul>
				</div>
			</div>
			
			<!-- Import Form -->
			<div class="overview-section" id="feed-stars-import-form">
				<h4 class="collapsible-section" data-target="import-config-content">
					<span class="dashicons dashicons-arrow-down-alt2 toggle-icon"></span>
					<span class="dashicons dashicons-admin-settings"></span>
					<?php esc_html_e( 'Import Configuration', 'feed-favorites' ); ?>
				</h4>
				<div id="import-config-content" class="collapsible-content">
					<form method="post" enctype="multipart/form-data" id="rss-json-import-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="feed_favorites_json_import">
						<?php wp_nonce_field( 'feed_favorites_json_import', 'feed_favorites_json_nonce' ); ?>
						
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="rss_json_file"><?php esc_html_e( 'Export File', 'feed-favorites' ); ?></label>
								</th>
								<td>
									<input type="file" 
											id="rss_json_file" 
											name="rss_json_file" 
											accept=".json,.xml" />
									<p class="description">
										<?php esc_html_e( 'Select the JSON or XML file exported from your RSS reader service', 'feed-favorites' ); ?>
									</p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="rss_batch_size"><?php esc_html_e( 'Batch Size', 'feed-favorites' ); ?></label>
								</th>
								<td>
									<input type="number" 
											id="rss_batch_size" 
											name="rss_batch_size" 
											value="20" 
											min="5" 
											max="100" 
											class="small-text" />
									<p class="description">
										<?php esc_html_e( 'Number of articles processed per batch. Recommended: 20 for large imports (1000+ articles)', 'feed-favorites' ); ?>
									</p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="rss_import_limit"><?php esc_html_e( 'Import Limit', 'feed-favorites' ); ?></label>
								</th>
								<td>
									<input type="number" 
											id="rss_import_limit" 
											name="rss_import_limit" 
											value="50" 
											min="0" 
											max="1000" 
											class="small-text" />
									<p class="description">
										<?php esc_html_e( 'Maximum number of articles to import (0 = all articles). Recommended: 50 for testing.', 'feed-favorites' ); ?>
									</p>
								</td>
							</tr>
						</table>
						
						<p class="submit">
							<button type="submit" id="rss-import-btn" class="button button-primary" disabled>
								<span class="dashicons dashicons-upload"></span>
								<span><?php esc_html_e( 'Start Import', 'feed-favorites' ); ?></span>
							</button>
							<span id="import-status"></span>
						</p>
					</form>
				</div>
			</div>
			
			<!-- Supported Formats -->
			<div class="overview-section" id="feed-favorites-supported-formats">
				<h4 class="collapsible-section" data-target="supported-formats-content">
					<span class="dashicons dashicons-arrow-down-alt2 toggle-icon"></span>
					<span class="dashicons dashicons-media-document"></span>
					<?php esc_html_e( 'Supported Export Formats', 'feed-favorites' ); ?>
				</h4>
				<div id="supported-formats-content" class="collapsible-content">
					<h4><?php esc_html_e( 'RSS Reader Services', 'feed-favorites' ); ?></h4>
					<p><?php esc_html_e( 'The plugin supports exports from most popular RSS reader services:', 'feed-favorites' ); ?></p>
					
					<ul>
						<li><strong>Feedbin</strong> - <?php esc_html_e( 'JSON export from Settings > Export', 'feed-favorites' ); ?></li>
						<li><strong>Feedly</strong> - <?php esc_html_e( 'JSON export via API or manual export', 'feed-favorites' ); ?></li>
						<li><strong>Inoreader</strong> - <?php esc_html_e( 'JSON export from Settings > Export', 'feed-favorites' ); ?></li>
						<li><strong>FreshRSS</strong> - <?php esc_html_e( 'JSON export from Settings > Export', 'feed-favorites' ); ?></li>
						<li><strong>Google Reader</strong> - <?php esc_html_e( 'JSON export (if you have old exports)', 'feed-favorites' ); ?></li>
					</ul>
					
					<h4><?php esc_html_e( 'Export Instructions', 'feed-favorites' ); ?></h4>
					<ol>
						<li><?php esc_html_e( 'Log into your RSS reader service', 'feed-favorites' ); ?></li>
						<li><?php esc_html_e( 'Go to Settings/Preferences > Export', 'feed-favorites' ); ?></li>
						<li><?php esc_html_e( 'Select "Starred Articles" or "Favorites"', 'feed-favorites' ); ?></li>
						<li><?php esc_html_e( 'Download the JSON file', 'feed-favorites' ); ?></li>
					</ol>
					
					<div class="notice notice-warning is-dismissible" id="feed-stars-xml-notice">
						<button type="button" class="notice-dismiss">
							<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'feed-favorites' ); ?></span>
						</button>
						<h5><?php esc_html_e( 'XML (coming soon)', 'feed-favorites' ); ?></h5>
						<p><?php esc_html_e( 'XML export support in development', 'feed-favorites' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render synchronization options (deprecated - using JSON import instead)
	 */
	public static function render_sync_options() {
		return '<p>' . esc_html__( 'This feature has been replaced by the JSON import functionality above.', 'feed-favorites' ) . '</p>';
	}

	/**
	 * Render recent logs
	 */
	public static function render_recent_logs( $logs ) {
		ob_start();
		?>
		<?php if ( empty( $logs ) ) : ?>
			<p class="description">
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'No logs available', 'feed-favorites' ); ?>
			</p>
		<?php else : ?>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'feed-favorites' ); ?></th>
						<th><?php esc_html_e( 'Level', 'feed-favorites' ); ?></th>
						<th><?php esc_html_e( 'Message', 'feed-favorites' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td><?php echo date_i18n( 'd/m/Y H:i:s', strtotime( $log['timestamp'] ) ); ?></td>
							<td>
								<span style="color: <?php echo $log['level'] === 'ERROR' ? '#dc3232' : '#46b450'; ?>; font-weight: bold;">
									<?php echo esc_html( $log['level'] ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $log['message'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render reset buttons
	 */
	public static function render_reset_buttons() {
		$reset_buttons = array(
			array(
				'id'          => 'reset-logs-btn',
				'action'      => 'logs',
				'icon'        => 'dashicons-trash',
				'text'        => esc_html__( 'Reset Logs', 'feed-favorites' ),
				'description' => esc_html__( 'Delete all synchronization logs', 'feed-favorites' ),
				'class'       => 'button-secondary',
			),
			array(
				'id'          => 'reset-stats-btn',
				'action'      => 'stats',
				'icon'        => 'dashicons-chart-line',
				'text'        => esc_html__( 'Reset Statistics', 'feed-favorites' ),
				'description' => esc_html__( 'Reset sync counters to zero', 'feed-favorites' ),
				'class'       => 'button-secondary',
			),
			array(
				'id'          => 'reset-system-notice-btn',
				'action'      => 'system_notice',
				'icon'        => 'dashicons-admin-tools',
				'text'        => esc_html__( 'Reset System Notice', 'feed-favorites' ),
				'description' => esc_html__( 'Show system configuration notice again', 'feed-favorites' ),
				'class'       => 'button-secondary',
			),
			array(
				'id'          => 'reset-all-btn',
				'action'      => 'all',
				'icon'        => 'dashicons-update',
				'text'        => esc_html__( 'Reset Everything', 'feed-favorites' ),
				'description' => esc_html__( 'Logs + Statistics + System Notice', 'feed-favorites' ),
				'class'       => 'button-secondary',
			),
		);

		ob_start();
		?>
		<div class="reset-buttons-grid">
			<?php foreach ( $reset_buttons as $button ) : ?>
				<div class="reset-button-card">
					<div class="reset-button-header">
						<span class="dashicons <?php echo esc_attr( $button['icon'] ); ?>"></span>
						<h4><?php echo esc_html( $button['text'] ); ?></h4>
					</div>
					<p class="description"><?php echo esc_html( $button['description'] ); ?></p>
					<button type="button" 
							id="<?php echo esc_attr( $button['id'] ); ?>" 
							class="button <?php echo esc_attr( $button['class'] ); ?><?php echo esc_attr( $button['action'] === 'all' ? ' button-link-delete' : '' ); ?>"
							data-reset-action="<?php echo esc_attr( $button['action'] ); ?>">
						<?php echo esc_html( $button['text'] ); ?>
					</button>
				</div>
			<?php endforeach; ?>
		</div>
		
		<div id="reset-status"></div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render system check
	 */
	public static function render_system_check() {
		$memory_limit        = ini_get( 'memory_limit' );
		$max_execution_time  = ini_get( 'max_execution_time' );
		$upload_max_filesize = ini_get( 'upload_max_filesize' );
		$post_max_size       = ini_get( 'post_max_size' );

		// Convert memory limit to bytes for comparison
		$memory_limit_bytes        = self::convert_to_bytes( $memory_limit );
		$upload_max_filesize_bytes = self::convert_to_bytes( $upload_max_filesize );
		$post_max_size_bytes       = self::convert_to_bytes( $post_max_size );

		// Check if values are sufficient
		$memory_ok = $memory_limit_bytes >= 256 * 1024 * 1024; // 256MB minimum
		$time_ok   = $max_execution_time >= 300 || $max_execution_time == 0; // 5 minutes or unlimited
		$upload_ok = $upload_max_filesize_bytes >= 50 * 1024 * 1024; // 50MB minimum
		$post_ok   = $post_max_size_bytes >= 50 * 1024 * 1024; // 50MB minimum

		$all_ok = $memory_ok && $time_ok && $upload_ok && $post_ok;

		// Check if this is the first time showing the system check
		$system_check_shown = get_option( 'feed_favorites_system_check_shown', false );

		ob_start();
		?>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Setting', 'feed-favorites' ); ?></th>
					<th><?php esc_html_e( 'Current Value', 'feed-favorites' ); ?></th>
					<th><?php esc_html_e( 'Recommended', 'feed-favorites' ); ?></th>
					<th><?php esc_html_e( 'Status', 'feed-favorites' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $all_ok ) : ?>
				<tr class="system-status-row">
					<td colspan="4">
						<div class="notice notice-warning" style="margin: 0;">
							<p><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'System configuration may limit import performance', 'feed-favorites' ); ?></p>
							<p><?php esc_html_e( 'Some server settings are below recommended values. Large imports may fail or be slow.', 'feed-favorites' ); ?></p>
						</div>
					</td>
				</tr>
				<?php endif; ?>
				
				<tr>
					<td><strong><?php esc_html_e( 'Memory Limit', 'feed-favorites' ); ?></strong></td>
					<td><?php echo esc_html( $memory_limit ); ?></td>
					<td><?php esc_html_e( '256M or higher', 'feed-favorites' ); ?></td>
					<td>
						<?php if ( $memory_ok ) : ?>
							<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <?php esc_html_e( 'OK', 'feed-favorites' ); ?>
						<?php else : ?>
							<span class="dashicons dashicons-warning" style="color: #ffb900;"></span> <?php esc_html_e( 'Low', 'feed-favorites' ); ?>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Max Execution Time', 'feed-favorites' ); ?></strong></td>
					<td><?php echo $max_execution_time == 0 ? esc_html__( 'Unlimited', 'feed-favorites' ) : esc_html( $max_execution_time . 's' ); ?></td>
					<td><?php esc_html_e( '300s or unlimited', 'feed-favorites' ); ?></td>
					<td>
						<?php if ( $time_ok ) : ?>
							<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <?php esc_html_e( 'OK', 'feed-favorites' ); ?>
						<?php else : ?>
							<span class="dashicons dashicons-warning" style="color: #ffb900;"></span> <?php esc_html_e( 'Low', 'feed-favorites' ); ?>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Upload Max Filesize', 'feed-favorites' ); ?></strong></td>
					<td><?php echo esc_html( $upload_max_filesize ); ?></td>
					<td><?php esc_html_e( '50M or higher', 'feed-favorites' ); ?></td>
					<td>
						<?php if ( $upload_ok ) : ?>
							<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <?php esc_html_e( 'OK', 'feed-favorites' ); ?>
						<?php else : ?>
							<span class="dashicons dashicons-warning" style="color: #ffb900;"></span> <?php esc_html_e( 'Low', 'feed-favorites' ); ?>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Post Max Size', 'feed-favorites' ); ?></strong></td>
					<td><?php echo esc_html( $post_max_size ); ?></td>
					<td><?php esc_html_e( '50M or higher', 'feed-favorites' ); ?></td>
					<td>
						<?php if ( $post_ok ) : ?>
							<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <?php esc_html_e( 'OK', 'feed-favorites' ); ?>
						<?php else : ?>
							<span class="dashicons dashicons-warning" style="color: #ffb900;"></span> <?php esc_html_e( 'Low', 'feed-favorites' ); ?>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>
			
			<?php if ( ! $all_ok ) : ?>
				<div class="notice notice-info">
					<h4><?php esc_html_e( 'How to fix these issues:', 'feed-favorites' ); ?></h4>
					<ul>
						<li><strong><?php esc_html_e( 'Memory Limit:', 'feed-favorites' ); ?></strong> <?php esc_html_e( 'Add this line to your wp-config.php: define(\'WP_MEMORY_LIMIT\', \'512M\');', 'feed-favorites' ); ?></li>
						<li><strong><?php esc_html_e( 'Max Execution Time:', 'feed-favorites' ); ?></strong> <?php esc_html_e( 'Add this line to your wp-config.php: set_time_limit(0); or contact your hosting provider.', 'feed-favorites' ); ?></li>
						<li><strong><?php esc_html_e( 'Upload/Post Size:', 'feed-favorites' ); ?></strong> <?php esc_html_e( 'Contact your hosting provider to increase these limits.', 'feed-favorites' ); ?></li>
					</ul>
				</div>
			<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Convert PHP size string to bytes
	 */
	private static function convert_to_bytes( $size_str ) {
		$size_str = trim( $size_str );
		$last     = strtolower( $size_str[ strlen( $size_str ) - 1 ] );
		$size     = (int) $size_str;

		switch ( $last ) {
			case 'g':
				$size *= 1024;
			case 'm':
				$size *= 1024;
			case 'k':
				$size *= 1024;
		}

		return $size;
	}
}
