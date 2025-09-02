<?php
/**
 * Feed Favorites Admin Page Template
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get data.
$feed_url      = Config::get( 'feed_url' );
$pending_stars = 0;
if ( ! empty( $feed_url ) ) {
	$pending_stars = wp_rand( 5, 25 ); // To be replaced with real counting logic.
}

// Get statistics.
$stats = array(
	'total_posts' => wp_count_posts( 'favorite' )->publish,
	'sync_count'  => get_option( 'feed_favorites_sync_count', 0 ),
	'error_count' => get_option( 'feed_favorites_error_count', 0 ),
	'last_sync'   => get_option( 'feed_favorites_last_sync', '' ),
);

// Get recent logs.
$logger = new Logger();
$logs   = $logger->get_recent_logs( 10 );

// Handle import messages with nonce verification.
$nonce_param = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
$nonce_ok    = ! empty( $nonce_param ) && wp_verify_nonce( $nonce_param, 'feed_favorites_admin' );

$import_success = ( $nonce_ok && isset( $_GET['import_success'] ) ) ? sanitize_text_field( wp_unslash( $_GET['import_success'] ) ) : '';
$import_error   = ( $nonce_ok && isset( $_GET['import_error'] ) ) ? sanitize_text_field( wp_unslash( $_GET['import_error'] ) ) : '';

// Get current tab (nonce required to switch via GET).
$current_tab = ( $nonce_ok && isset( $_GET['tab'] ) ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'dashboard';

// $admin is passed from core.php.
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<hr class="wp-header-end">
	
	<?php if ( ! empty( $import_success ) ) : ?>
		<div class="notice notice-success is-dismissible" id="feed-favorites-import-success-notice">
			<p><?php echo esc_html( $import_success ); ?></p>
		</div>
	<?php endif; ?>
	
	<?php if ( ! empty( $import_error ) ) : ?>
		<div class="notice notice-error is-dismissible" id="feed-favorites-import-error-notice">
			<p><?php echo esc_html( $import_error ); ?></p>
		</div>
	<?php endif; ?>
	
	<!-- Notice de compatibilité -->
	<div class="notice notice-warning is-dismissible" id="feed-favorites-compatibility-notice">
		<p><strong><?php esc_html_e( 'Compatibility Note:', 'feed-favorites' ); ?></strong> <?php esc_html_e( 'This plugin has been primarily tested with Feedbin but is designed to work with any RSS reader service including Feedly, Inoreader, FreshRSS, and others.', 'feed-favorites' ); ?></p>
	</div>
	
	<nav class="nav-tab-wrapper wp-clearfix">
		<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'post_type' => 'favorite', 'page' => 'feed-favorites', 'tab' => 'dashboard' ), admin_url( 'edit.php' ) ), 'feed_favorites_admin' ) ); ?>" class="nav-tab <?php echo 'dashboard' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-admin-home"></span>
			<?php esc_html_e( 'Dashboard', 'feed-favorites' ); ?>
		</a>
		<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'post_type' => 'favorite', 'page' => 'feed-favorites', 'tab' => 'setup' ), admin_url( 'edit.php' ) ), 'feed_favorites_admin' ) ); ?>" class="nav-tab <?php echo 'setup' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-admin-settings"></span>
			<?php esc_html_e( 'Setup', 'feed-favorites' ); ?>
		</a>
		<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'post_type' => 'favorite', 'page' => 'feed-favorites', 'tab' => 'maintenance' ), admin_url( 'edit.php' ) ), 'feed_favorites_admin' ) ); ?>" class="nav-tab <?php echo 'maintenance' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-admin-tools"></span>
			<?php esc_html_e( 'Maintenance', 'feed-favorites' ); ?>
		</a>
	</nav>
	
	<?php
	switch ( $current_tab ) {
		case 'dashboard':
			$admin->render_dashboard_tab( $stats, $logs, $pending_stars );
			break;
		case 'setup':
			$admin->render_setup_tab();
			break;
		case 'maintenance':
			$admin->render_maintenance_tab( $stats, $logs );
			break;
		default:
			$admin->render_dashboard_tab( $stats, $logs, $pending_stars );
			break;
	}
	?>
	
	<div id="feed-favorites-messages"></div>
</div> 