<?php
/**
 * Simple test script for Feed Favorites plugin components
 */

echo "=== Feed Favorites Component Test ===\n\n";

// Test 1: Basic file inclusion
echo "1. Testing file inclusion...\n";
try {
    if (file_exists('includes/config.php')) {
        echo "   ✓ config.php exists\n";
    } else {
        echo "   ✗ config.php not found\n";
    }
    
    if (file_exists('includes/validator.php')) {
        echo "   ✓ validator.php exists\n";
    } else {
        echo "   ✗ validator.php not found\n";
    }
    
    if (file_exists('includes/http.php')) {
        echo "   ✓ http.php exists\n";
    } else {
        echo "   ✗ http.php not found\n";
    }
    
    if (file_exists('includes/import.php')) {
        echo "   ✓ import.php exists\n";
    } else {
        echo "   ✗ import.php not found\n";
    }
    
    if (file_exists('includes/sync.php')) {
        echo "   ✓ sync.php exists\n";
    } else {
        echo "   ✗ sync.php not found\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ File check error: " . $e->getMessage() . "\n";
}

// Test 2: File content analysis
echo "\n2. Testing file content...\n";
try {
    $config_content = file_get_contents('includes/config.php');
    if (strpos($config_content, 'class Config') !== false) {
        echo "   ✓ Config class found in config.php\n";
    } else {
        echo "   ✗ Config class not found in config.php\n";
    }
    
    $validator_content = file_get_contents('includes/validator.php');
    if (strpos($validator_content, 'class Validator') !== false) {
        echo "   ✓ Validator class found in validator.php\n";
    } else {
        echo "   ✗ Validator class not found in validator.php\n";
    }
    
    $http_content = file_get_contents('includes/http.php');
    if (strpos($http_content, 'class Http') !== false) {
        echo "   ✓ Http class found in http.php\n";
    } else {
        echo "   ✗ Http class not found in http.php\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Content analysis error: " . $e->getMessage() . "\n";
}

// Test 3: JSON file analysis
echo "\n3. Testing JSON file structure...\n";
try {
    $json_file = '../../.doc/starred.json';
    if (file_exists($json_file)) {
        echo "   ✓ starred.json found\n";
        
        $json_content = file_get_contents($json_file);
        $json_data = json_decode($json_content, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "   ✓ JSON is valid\n";
            echo "   ✓ JSON contains " . count($json_data) . " entries\n";
            
            if (count($json_data) > 0) {
                $first_entry = $json_data[0];
                echo "   ✓ First entry keys: " . implode(', ', array_keys($first_entry)) . "\n";
                
                // Check required fields
                $required_fields = ['id', 'title', 'content', 'url', 'published'];
                $missing_fields = [];
                
                foreach ($required_fields as $field) {
                    if (!isset($first_entry[$field])) {
                        $missing_fields[] = $field;
                    }
                }
                
                if (empty($missing_fields)) {
                    echo "   ✓ All required fields present\n";
                } else {
                    echo "   ✗ Missing fields: " . implode(', ', $missing_fields) . "\n";
                }
            }
        } else {
            echo "   ✗ JSON error: " . json_last_error_msg() . "\n";
        }
    } else {
        echo "   ✗ starred.json not found\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ JSON analysis error: " . $e->getMessage() . "\n";
}

// Test 4: RSS feed test
echo "\n4. Testing RSS feed...\n";
try {
    $feed_url = 'https://feedbin.com/starred/9d04477847a5e9cec30b413cdf358176.xml';
    echo "   ✓ Testing feed: " . $feed_url . "\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Feed Favorites Plugin Test/1.0'
        ]
    ]);
    
    $feed_content = file_get_contents($feed_url, false, $context);
    
    if ($feed_content !== false) {
        echo "   ✓ Feed fetched successfully\n";
        echo "   ✓ Feed size: " . strlen($feed_content) . " bytes\n";
        
        // Check if it's valid XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($feed_content);
        
        if ($xml !== false) {
            echo "   ✓ XML is valid\n";
            
            if (isset($xml->channel->item)) {
                $item_count = count($xml->channel->item);
                echo "   ✓ Found " . $item_count . " items in feed\n";
                
                if ($item_count > 0) {
                    $first_item = $xml->channel->item[0];
                    echo "   ✓ First item title: " . (string)$first_item->title . "\n";
                    echo "   ✓ First item link: " . (string)$first_item->link . "\n";
                }
            } else {
                echo "   ✗ No items found in feed\n";
            }
        } else {
            echo "   ✗ XML is not valid\n";
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                echo "     - Line " . $error->line . ": " . $error->message . "\n";
            }
            libxml_clear_errors();
        }
    } else {
        echo "   ✗ Failed to fetch feed\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ RSS feed test error: " . $e->getMessage() . "\n";
}

// Test 5: Plugin structure analysis
echo "\n5. Testing plugin structure...\n";
try {
    $plugin_files = [
        'feed-favorites.php',
        'includes/core.php',
        'includes/admin.php',
        'includes/ajax.php',
        'includes/components.php',
        'admin/css/admin.css',
        'admin/js/admin.js',
        'admin/views/admin-page.php'
    ];
    
    foreach ($plugin_files as $file) {
        if (file_exists($file)) {
            echo "   ✓ " . $file . " exists\n";
        } else {
            echo "   ✗ " . $file . " not found\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Structure analysis error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "Plugin structure analysis finished.\n";
echo "Ready for WordPress integration testing.\n";
