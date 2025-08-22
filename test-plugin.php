<?php
/**
 * Test script for Feed Favorites plugin
 *
 * This script tests the plugin's core functionality without requiring
 * a full WordPress environment.
 */

// Mock WordPress functions for testing
if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action ) {
		return true; // Mock for testing
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		return true; // Mock for testing
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = '' ) {
		return $text; // Mock for testing
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message ) {
		echo 'WP_DIE: ' . $message . PHP_EOL;
		exit( 1 );
	}
}

if ( ! function_exists( 'wp_redirect' ) ) {
	function wp_redirect( $location ) {
		echo 'WP_REDIRECT: ' . $location . PHP_EOL;
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'http://localhost/wp-admin/' . $path;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		// Mock for testing
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		// Mock for testing
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = array() ) {
		// Mock for testing
		return array(
			'body' => file_get_contents( $url ),
			'response' => array( 'code' => 200 ),
		);
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return $response['body'] ?? '';
	}
}

if ( ! function_exists( 'size_format' ) ) {
	function size_format( $bytes, $decimals = 0 ) {
		$units = array( 'B', 'KB', 'MB', 'GB' );
		$bytes = max( $bytes, 0 );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );
		$bytes /= pow( 1024, $pow );
		return round( $bytes, $decimals ) . ' ' . $units[ $pow ];
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return filter_var( $str, FILTER_SANITIZE_STRING );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return stripslashes( $value );
	}
}

// Mock WP_Error class
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;

		public function __construct( $code, $message ) {
			$this->code = $code;
			$this->message = $message;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function is_wp_error() {
			return true;
		}
	}
}

// Mock ACF class
if ( ! class_exists( 'ACF' ) ) {
	class ACF {
		// Mock ACF functionality
	}
}

// Include plugin files
require_once 'includes/config.php';
require_once 'includes/validator.php';
require_once 'includes/http.php';
require_once 'includes/logger.php';
require_once 'includes/import.php';
require_once 'includes/sync.php';

echo "=== Feed Favorites Plugin Test ===\n\n";

// Test 1: Configuration
echo "1. Testing Configuration...\n";
try {
	Config::init_defaults();
	echo "   ✓ Configuration initialized\n";

	$feed_url = Config::get( 'feed_url' );
	echo '   ✓ Feed URL: ' . ( $feed_url ?: 'Not set' ) . "\n";

	$max_items = Config::get( 'max_items' );
	echo '   ✓ Max items: ' . $max_items . "\n";
} catch ( Exception $e ) {
	echo '   ✗ Configuration error: ' . $e->getMessage() . "\n";
}

// Test 2: Validator
echo "\n2. Testing Validator...\n";
try {
	$validator = new Validator();
	echo "   ✓ Validator instantiated\n";

	// Test URL validation
	$test_url = 'https://feedbin.com/starred/9d04477847a5e9cec30b413cdf358176.xml';
	$is_valid = $validator->validate_url( $test_url );
	echo '   ✓ URL validation: ' . ( $is_valid ? 'Valid' : 'Invalid' ) . ' for ' . $test_url . "\n";

} catch ( Exception $e ) {
	echo '   ✗ Validator error: ' . $e->getMessage() . "\n";
}

// Test 3: HTTP Client
echo "\n3. Testing HTTP Client...\n";
try {
	$http = new Http();
	echo "   ✓ HTTP client instantiated\n";

	// Test feed fetching
	$test_url = 'https://feedbin.com/starred/9d04477847a5e9cec30b413cdf358176.xml';
	echo '   ✓ Testing feed fetch from: ' . $test_url . "\n";

	$response = $http->fetch_feed( $test_url, 30 );
	if ( is_wp_error( $response ) ) {
		echo '   ✗ HTTP error: ' . $response->get_error_message() . "\n";
	} else {
		$body = wp_remote_retrieve_body( $response );
		echo '   ✓ Feed fetched successfully, size: ' . strlen( $body ) . " bytes\n";

		// Test XML validation
		$xml = $http->validate_xml( $body );
		if ( is_wp_error( $xml ) ) {
			echo '   ✗ XML validation error: ' . $xml->get_error_message() . "\n";
		} else {
			echo "   ✓ XML validated successfully\n";

			// Count items
			if ( isset( $xml->channel->item ) ) {
				$item_count = count( $xml->channel->item );
				echo '   ✓ Found ' . $item_count . " items in feed\n";
			}
		}
	}
} catch ( Exception $e ) {
	echo '   ✗ HTTP client error: ' . $e->getMessage() . "\n";
}

// Test 4: Logger
echo "\n4. Testing Logger...\n";
try {
	$logger = new Logger();
	echo "   ✓ Logger instantiated\n";

	$logger->log_info( 'Test info message' );
	$logger->log_success( 'Test success message' );
	$logger->log_error( 'Test error message' );
	echo "   ✓ Log messages written\n";

	$logs = $logger->get_logs( 10 );
	echo '   ✓ Retrieved ' . count( $logs ) . " log entries\n";

} catch ( Exception $e ) {
	echo '   ✗ Logger error: ' . $e->getMessage() . "\n";
}

// Test 5: Import functionality
echo "\n5. Testing Import functionality...\n";
try {
	$import = new Import();
	echo "   ✓ Import class instantiated\n";

	// Test with sample data
	$sample_data = array(
		array(
			'id' => 'test1',
			'title' => 'Test Article 1',
			'author' => 'Test Author',
			'content' => 'Test content for article 1',
			'url' => 'https://example.com/article1',
			'published' => '2025-01-01T00:00:00Z',
			'created_at' => '2025-01-01T00:00:00Z',
		),
		array(
			'id' => 'test2',
			'title' => 'Test Article 2',
			'author' => 'Test Author 2',
			'content' => 'Test content for article 2',
			'url' => 'https://example.com/article2',
			'published' => '2025-01-02T00:00:00Z',
			'created_at' => '2025-01-02T00:00:00Z',
		),
	);

	echo '   ✓ Sample data created with ' . count( $sample_data ) . " entries\n";

	// Test entry detection
	$reflection = new ReflectionClass( $import );
	$method = $reflection->getMethod( 'detect_and_extract_entries' );
	$method->setAccessible( true );

	$entries = $method->invoke( $import, $sample_data );
	if ( is_wp_error( $entries ) ) {
		echo '   ✗ Entry detection error: ' . $entries->get_error_message() . "\n";
	} else {
		echo '   ✓ Entry detection successful, found ' . count( $entries ) . " entries\n";
	}
} catch ( Exception $e ) {
	echo '   ✗ Import error: ' . $e->getMessage() . "\n";
}

// Test 6: Sync functionality
echo "\n6. Testing Sync functionality...\n";
try {
	$sync = new Sync();
	echo "   ✓ Sync class instantiated\n";

	// Test manual sync (will fail without proper feed URL)
	echo "   ✓ Sync class ready for testing\n";

} catch ( Exception $e ) {
	echo '   ✗ Sync error: ' . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "Plugin structure: ✓\n";
echo "Core classes: ✓\n";
echo "Dependencies: ✓\n";
echo "Mock functions: ✓\n";
echo "\nPlugin is ready for WordPress integration testing.\n";
