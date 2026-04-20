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

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class FeedFavorites {

	/**
	 * Single instance of the class.
	 *
	 * @var FeedFavorites|null
	 */
	private static $instance = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.2';

	/**
	 * Admin instance.
	 *
	 * @var Admin
	 */
	private $admin;

	/**
	 * Sync instance.
	 *
	 * @var Sync
	 */
	private $sync;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Ajax instance.
	 *
	 * @var Ajax
	 */
	private $ajax;

	/**
	 * Import instance.
	 *
	 * @var Import
	 */
	private $import;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Single instance.
	 *
	 * @return FeedFavorites
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 0 );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Cron job for automatic synchronization.
		add_action( 'feed_favorites_cron_sync', array( $this, 'cron_sync' ) );

		// Activation/Deactivation hooks.
		register_activation_hook( FEED_FAVORITES_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( FEED_FAVORITES_PLUGIN_FILE, array( $this, 'deactivate' ) );

		// Reschedule cron when interval option is updated.
		add_action(
			'update_option_feed_favorites_sync_interval',
			function () {
				wp_clear_scheduled_hook( 'feed_favorites_cron_sync' );
				if ( ! wp_next_scheduled( 'feed_favorites_cron_sync' ) ) {
					wp_schedule_event( time(), 'feed_favorites_interval', 'feed_favorites_cron_sync' );
				}
			},
			10,
			0
		);
	}

	/**
	 * Load plugin translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'feed-favorites',
			false,
			dirname( plugin_basename( FEED_FAVORITES_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Plugin initialization.
	 *
	 * @return void
	 */
	public function init() {
		// Check requirements.
		$this->check_requirements();

		// Load dependencies.
		$this->load_dependencies();

		// Register post meta fields.
		Post_Meta::register();

		// Add post format support.
		add_theme_support( 'post-formats', array( 'link' ) );

		// Register Custom Post Types.
		$this->register_post_types();
	}

	/**
	 * Check requirements.
	 *
	 * @return void
	 */
	private function check_requirements() {
		// Check PHP version.
		if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>Feed Favorites requires PHP 8.2 or higher.</p></div>';
				}
			);
			return;
		}

		// Check WordPress version.
		if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>Feed Favorites requires WordPress 5.0 or higher.</p></div>';
				}
			);
			return;
		}
	}

	/**
	 * Load dependencies.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		// Load core classes first.
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-config.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/core/class-post-meta.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/core/class-capabilities.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/core/class-migration.php';

		// Load other classes.
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-validator.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-http.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-ajax.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-components.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-admin.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-sync.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-logger.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-import.php';

		// Load creation classes.
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/creation/class-manual-creator.php';

		// Load admin classes.
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/admin/class-native-meta-boxes.php';

		// Load display classes.
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/display/class-template-tags.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/display/template-functions.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/display/class-template-loader.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/display/class-frontend-filters.php';

		// Load optional integration classes.
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/integrations/class-acf-integration.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/integrations/class-seo-integration.php';

		// Initialize components.
		$this->admin  = new Admin();
		$this->sync   = new Sync();
		$this->logger = new Logger();
		$this->ajax   = new Ajax();
		$this->import = new Import();

		// Initialize new components.
		new Native_Meta_Boxes();
		new Template_Loader();
		new Frontend_Filters();

		// Initialize optional integrations.
		new ACF_Integration();
		new SEO_Integration();
	}

	/**
	 * Plugin activation.
	 *
	 * @return void
	 */
	public function activate() {
		// Check requirements before activation.
		if ( version_compare( PHP_VERSION, '8.2', '<' ) || version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			deactivate_plugins( plugin_basename( FEED_FAVORITES_PLUGIN_FILE ) );
			wp_die( 'Feed Favorites requires PHP 8.2+ and WordPress 5.0+' );
		}

		// Bootstrap classes needed before plugins_loaded (activation runs early).
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-config.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/core/class-post-meta.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/class-logger.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/core/class-capabilities.php';
		require_once FEED_FAVORITES_PLUGIN_PATH . 'includes/core/class-migration.php';

		// Initialize configuration.
		Config::init_defaults();

		Capabilities::register();

		// Run migration.
		Migration::run();

		// Schedule cron job.
		$this->schedule_cron();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 *
	 * @return void
	 */
	public function deactivate() {
		// Clean up cron job.
		wp_clear_scheduled_hook( 'feed_favorites_cron_sync' );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Schedule cron job.
	 *
	 * @return void
	 */
	private function schedule_cron() {
		if ( ! wp_next_scheduled( 'feed_favorites_cron_sync' ) ) {
			wp_schedule_event( time(), 'feed_favorites_interval', 'feed_favorites_cron_sync' );
		}
	}

	/**
	 * Register Custom Post Types.
	 *
	 * @return void
	 */
	private function register_post_types() {
		// CPT for favorite articles.
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
	 * Add admin menu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		// Add submenu page to the Feed Favorites CPT menu.
		add_submenu_page(
			'edit.php?post_type=favorite',
			__( 'Feed Favorites Settings', 'feed-favorites' ),
			__( 'Settings', 'feed-favorites' ),
			Capabilities::MANAGE,
			'feed-favorites',
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Initialize admin.
	 *
	 * @return void
	 */
	public function admin_init() {
		// Run migration if needed.
		Migration::run();

		// Register settings with sanitization.
		$this->register_settings();
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	private function register_settings() {
		$settings = array(
			'feed_url'              => array(
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => array( Validator::class, 'validate_feed_url' ),
			),
			'auto_sync'             => array(
				'sanitize_callback' => 'intval',
				'validate_callback' => array( Validator::class, 'validate_auto_sync' ),
			),
			'sync_interval'         => array(
				'sanitize_callback' => array( Validator::class, 'validate_sync_interval' ),
			),
			'max_items'             => array(
				'sanitize_callback' => array( Validator::class, 'validate_max_items' ),
			),
			'sync_post_author'      => array(
				'sanitize_callback' => array( Validator::class, 'validate_sync_post_author' ),
			),
			'default_show_emoji'    => array(
				'sanitize_callback' => array( Validator::class, 'validate_boolean_option' ),
			),
			'default_open_new_tab'  => array(
				'sanitize_callback' => array( Validator::class, 'validate_boolean_option' ),
			),
			'link_summary_required' => array(
				'sanitize_callback' => array( Validator::class, 'validate_boolean_option' ),
			),
			'commentary_required'   => array(
				'sanitize_callback' => array( Validator::class, 'validate_boolean_option' ),
			),
			'use_link_format'       => array(
				'sanitize_callback' => array( Validator::class, 'validate_boolean_option' ),
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
	 * Admin page.
	 *
	 * @return void
	 */
	public function admin_page() {
		// Check permissions.
		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'feed-favorites' ) );
		}

		// Get data for template.
		$stats = $this->logger->get_stats();
		$logs  = $this->logger->get_recent_logs( 10 );

		// Pass Admin instance to template.
		$admin = $this->admin;

		// Include admin template.
		require_once FEED_FAVORITES_PLUGIN_PATH . 'admin/views/admin-page.php';
	}

	/**
	 * Automatic synchronization via cron.
	 *
	 * @return void
	 */
	public function cron_sync() {
		$this->sync->automatic_sync();
	}
}
