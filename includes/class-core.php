<?php
/**
 * Main Feed Favorites Plugin Class
 *
 * This class handles the main plugin functionality including initialization,
 * dependency loading, admin menu creation, and custom post type registration.
 *
 * @package FeedFavorites
 * @since 1.0.0
 * @author Jason Rouet
 * @license GPL-2.0-or-later
 */

// Security
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 */
class FeedFavorites {

	/**
	 * Single instance of the class
	 *
	 * @var FeedFavorites|null
	 */
	private static $instance = null;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Admin instance
	 *
	 * @var Admin
	 */
	private $admin;

	/**
	 * Sync instance
	 *
	 * @var Sync
	 */
	private $sync;

	/**
	 * Logger instance
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Ajax instance
	 *
	 * @var Ajax
	 */
	private $ajax;

	/**
	 * Import instance
	 *
	 * @var Import
	 */
	private $import;

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Single instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Cron job for automatic synchronization
		add_action( 'feed_favorites_cron_sync', array( $this, 'cron_sync' ) );

		// Activation hook
		register_activation_hook( FEED_FAVORITES_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( FEED_FAVORITES_PLUGIN_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Plugin initialization
	 */
	public function init() {
		// Check requirements
		$this->check_requirements();

		// Load dependencies
		$this->load_dependencies();

		// Register Custom Post Types
		$this->register_post_types();
	}

	/**
	 * Check requirements
	 */
	private function check_requirements() {
		// Check PHP version
		if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>Feed Favorites requires PHP 8.2 or higher.</p></div>';
				}
			);
			return;
		}

		// Check WordPress version
		if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>Feed Favorites requires WordPress 5.0 or higher.</p></div>';
				}
			);
			return;
		}

		// Check for ACF Pro
		if ( ! class_exists( 'ACF' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>Feed Favorites requires ACF Pro to function.</p></div>';
				}
			);
		}
	}

	/**
	 * Load dependencies
	 */
	private function load_dependencies() {
		// Load classes
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-config.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-validator.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-http.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-ajax.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-components.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-admin.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-sync.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-logger.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-import.php';

		// Initialize components
		$this->admin  = new Admin();
		$this->sync   = new Sync();
		$this->logger = new Logger();
		$this->ajax   = new Ajax();
		$this->import = new Import();
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Check requirements before activation
		if ( version_compare( PHP_VERSION, '8.2', '<' ) || version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			deactivate_plugins( plugin_basename( FEED_FAVORITES_PLUGIN_FILE ) );
			wp_die( 'Feed Favorites requires PHP 8.2+ and WordPress 5.0+' );
		}

		// Initialize configuration
		Config::init_defaults();

		// Schedule cron job
		$this->schedule_cron();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Clean up cron job
		wp_clear_scheduled_hook( 'feed_favorites_cron_sync' );

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Schedule cron job
	 */
	private function schedule_cron() {
		if ( ! wp_next_scheduled( 'feed_favorites_cron_sync' ) ) {
			wp_schedule_event( time(), 'hourly', 'feed_favorites_cron_sync' );
		}
	}

	/**
	 * Register Custom Post Types
	 */
	private function register_post_types() {
		// CPT for favorite articles
		$labels = array(
			'name'               => __( 'Favorite Articles', 'feed-favorites' ),
			'singular_name'      => __( 'Favorite Article', 'feed-favorites' ),
			'menu_name'          => __( 'Favorites', 'feed-favorites' ),
			'all_items'          => __( 'All Favorites', 'feed-favorites' ),
			'add_new'            => __( 'Add Favorite', 'feed-favorites' ),
			'add_new_item'       => __( 'Add Favorite', 'feed-favorites' ),
			'edit_item'          => __( 'Edit Favorite Article', 'feed-favorites' ),
			'new_item'           => __( 'New Favorite Article', 'feed-favorites' ),
			'view_item'          => __( 'View Favorite Article', 'feed-favorites' ),
			'search_items'       => __( 'Search Favorite Articles', 'feed-favorites' ),
			'not_found'          => __( 'No favorite articles found', 'feed-favorites' ),
			'not_found_in_trash' => __( 'No favorite articles found in trash', 'feed-favorites' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'favorites' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-star-filled',
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
			'can_export'         => true,
			'delete_with_user'   => false,
		);

		register_post_type( 'favorite', $args );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		// Add submenu page to the Feed Favorites CPT menu
		add_submenu_page(
			'edit.php?post_type=favorite',
			__( 'Feed Favorites Settings', 'feed-favorites' ),
			__( 'Settings', 'feed-favorites' ),
			'manage_options',
			'feed-favorites',
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Initialize admin
	 */
	public function admin_init() {
		// Register settings with sanitization
		$this->register_settings();
	}

	/**
	 * Register settings
	 */
	private function register_settings() {
		$settings = array(
			'feed_url'      => array(
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => array( Validator::class, 'validate_feed_url' ),
			),
			'auto_sync'     => array(
				'sanitize_callback' => 'intval',
				'validate_callback' => array( Validator::class, 'validate_auto_sync' ),
			),
			'sync_interval' => array(
				'sanitize_callback' => array( Validator::class, 'validate_sync_interval' ),
			),
			'max_items'     => array(
				'sanitize_callback' => array( Validator::class, 'validate_max_items' ),
			),
		);

		foreach ( $settings as $key => $callbacks ) {
			register_setting(
				'feed_favorites_options',
				Config::OPTION_PREFIX . $key,
				$callbacks
			);
		}
	}

	/**
	 * Admin page
	 */
	public function admin_page() {
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'feed-favorites' ) );
		}

		// Get data for template
		$stats = $this->logger->get_stats();
		$logs  = $this->logger->get_recent_logs( 10 );

		// Pass Admin instance to template
		$admin = $this->admin;

		// Include admin template
		require_once FEED_FAVORITES_PLUGIN_PATH . 'admin/views/admin-page.php';
	}

	/**
	 * Automatic synchronization via cron
	 */
	public function cron_sync() {
		$this->sync->automatic_sync();
	}
}
