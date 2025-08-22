<?php
/**
 * Advanced test script for Feed Favorites plugin
 * Tests import functionality and RSS synchronization
 */

echo "=== Feed Favorites Advanced Test ===\n\n";

// Test 1: JSON Import Analysis
echo "1. Testing JSON Import Analysis...\n";
try {
    $json_file = '../../../.doc/starred.json';
    if (file_exists($json_file)) {
        $content = file_get_contents($json_file);
        $data = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "   ✓ JSON loaded successfully: " . count($data) . " entries\n";
            
            // Analyze entry structure
            if (count($data) > 0) {
                $first_entry = $data[0];
                $entry_keys = array_keys($first_entry);
                echo "   ✓ Entry structure: " . implode(', ', $entry_keys) . "\n";
                
                // Check data quality
                $valid_entries = 0;
                $invalid_entries = 0;
                $sample_titles = [];
                
                foreach (array_slice($data, 0, 100) as $entry) { // Test first 100 entries
                    if (isset($entry['title']) && !empty($entry['title']) && 
                        isset($entry['url']) && !empty($entry['url'])) {
                        $valid_entries++;
                        if (count($sample_titles) < 5) {
                            $sample_titles[] = substr($entry['title'], 0, 60) . '...';
                        }
                    } else {
                        $invalid_entries++;
                    }
                }
                
                echo "   ✓ Data quality: " . $valid_entries . " valid, " . $invalid_entries . " invalid entries\n";
                echo "   ✓ Sample titles:\n";
                foreach ($sample_titles as $title) {
                    echo "     - " . $title . "\n";
                }
            }
        } else {
            echo "   ✗ JSON decode error: " . json_last_error_msg() . "\n";
        }
    } else {
        echo "   ✗ JSON file not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ JSON analysis error: " . $e->getMessage() . "\n";
}

// Test 2: RSS Feed Analysis
echo "\n2. Testing RSS Feed Analysis...\n";
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
        echo "   ✓ Feed fetched: " . strlen($feed_content) . " bytes\n";
        
        // Parse XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($feed_content);
        
        if ($xml !== false) {
            echo "   ✓ XML parsed successfully\n";
            
            if (isset($xml->channel->item)) {
                $items = $xml->channel->item;
                $item_count = count($items);
                echo "   ✓ Found " . $item_count . " items\n";
                
                if ($item_count > 0) {
                    // Analyze first few items
                    $sample_items = [];
                    for ($i = 0; $i < min(3, $item_count); $i++) {
                        $sample_items[] = $items[$i];
                    }
                    echo "   ✓ Sample items:\n";
                    
                    foreach ($sample_items as $index => $item) {
                        $title = isset($item->title) ? (string)$item->title : 'No title';
                        $link = isset($item->link) ? (string)$item->link : 'No link';
                        $pub_date = isset($item->pubDate) ? (string)$item->pubDate : 'No date';
                        
                        echo "     " . ($index + 1) . ". " . substr($title, 0, 50) . "...\n";
                        echo "        Link: " . substr($link, 0, 60) . "...\n";
                        echo "        Date: " . $pub_date . "\n";
                    }
                    
                    // Check for required RSS elements
                    $required_elements = ['title', 'link', 'description', 'pubDate'];
                    $missing_elements = [];
                    
                    foreach ($required_elements as $element) {
                        if (!isset($items[0]->$element)) {
                            $missing_elements[] = $element;
                        }
                    }
                    
                    if (empty($missing_elements)) {
                        echo "   ✓ All required RSS elements present\n";
                    } else {
                        echo "   ✗ Missing RSS elements: " . implode(', ', $missing_elements) . "\n";
                    }
                }
            } else {
                echo "   ✗ No items found in RSS feed\n";
            }
        } else {
            echo "   ✗ XML parsing failed\n";
            $errors = libxml_get_errors();
            foreach (array_slice($errors, 0, 3) as $error) {
                echo "     - Line " . $error->line . ": " . $error->message . "\n";
            }
            libxml_clear_errors();
        }
    } else {
        echo "   ✗ Failed to fetch RSS feed\n";
    }
} catch (Exception $e) {
    echo "   ✗ RSS analysis error: " . $e->getMessage() . "\n";
}

// Test 3: Plugin Class Analysis
echo "\n3. Testing Plugin Class Analysis...\n";
try {
    // Check if we can load the classes (without WordPress)
    $class_files = [
        'includes/config.php' => 'Config',
        'includes/validator.php' => 'Validator',
        'includes/http.php' => 'Http',
        'includes/import.php' => 'Import',
        'includes/sync.php' => 'Sync'
    ];
    
    foreach ($class_files as $file => $class_name) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (strpos($content, "class $class_name") !== false) {
                echo "   ✓ $class_name class found in $file\n";
                
                // Check for key methods
                $methods = [];
                if (strpos($content, 'public function') !== false || strpos($content, 'private function') !== false) {
                    preg_match_all('/(?:public|private|protected)\s+function\s+(\w+)/', $content, $matches);
                    if (isset($matches[1])) {
                        $methods = array_slice($matches[1], 0, 5); // First 5 methods
                        echo "     Methods: " . implode(', ', $methods) . "\n";
                    }
                }
            } else {
                echo "   ✗ $class_name class not found in $file\n";
            }
        } else {
            echo "   ✗ $file not found\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Class analysis error: " . $e->getMessage() . "\n";
}

// Test 4: Security Analysis
echo "\n4. Testing Security Analysis...\n";
try {
    $security_issues = [];
    
    // Check for common security issues
    $files_to_check = [
        'includes/import.php',
        'includes/ajax.php',
        'includes/sync.php'
    ];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Check for nonce verification
            if (strpos($content, 'wp_verify_nonce') === false) {
                $security_issues[] = "$file: Missing nonce verification";
            }
            
            // Check for capability checks
            if (strpos($content, 'current_user_can') === false) {
                $security_issues[] = "$file: Missing capability checks";
            }
            
            // Check for data sanitization
            if (strpos($content, 'sanitize_') === false && strpos($content, 'esc_') === false) {
                $security_issues[] = "$file: Missing data sanitization";
            }
            
            // Check for SQL injection prevention
            if (strpos($content, 'prepare') === false && strpos($content, 'wpdb') !== false) {
                $security_issues[] = "$file: Potential SQL injection risk";
            }
        }
    }
    
    if (empty($security_issues)) {
        echo "   ✓ No major security issues detected\n";
    } else {
        echo "   ⚠ Security issues found:\n";
        foreach ($security_issues as $issue) {
            echo "     - " . $issue . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Security analysis error: " . $e->getMessage() . "\n";
}

// Test 5: Performance Analysis
echo "\n5. Testing Performance Analysis...\n";
try {
    // Test JSON processing performance
    $json_file = '../../../.doc/starred.json';
    if (file_exists($json_file)) {
        $start_time = microtime(true);
        $content = file_get_contents($json_file);
        $data = json_decode($content, true);
        $end_time = microtime(true);
        
        $processing_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
        $memory_usage = memory_get_usage(true);
        
        echo "   ✓ JSON processing: " . number_format($processing_time, 2) . "ms\n";
        echo "   ✓ Memory usage: " . size_format($memory_usage) . "\n";
        echo "   ✓ Data size: " . size_format(strlen($content)) . "\n";
        
        // Test batch processing simulation
        if (count($data) > 100) {
            $sample_data = array_slice($data, 0, 100);
            $batch_size = 20;
            $batches = array_chunk($sample_data, $batch_size);
            
            echo "   ✓ Batch processing simulation: " . count($batches) . " batches of " . $batch_size . "\n";
            
            $start_time = microtime(true);
            foreach ($batches as $batch) {
                // Simulate processing
                foreach ($batch as $entry) {
                    $title = $entry['title'] ?? '';
                    $url = $entry['url'] ?? '';
                    // Simulate some processing
                    $processed = !empty($title) && !empty($url);
                }
                usleep(1000); // Simulate 1ms processing time
            }
            $end_time = microtime(true);
            
            $batch_time = ($end_time - $start_time) * 1000;
            echo "   ✓ Batch processing time: " . number_format($batch_time, 2) . "ms\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Performance analysis error: " . $e->getMessage() . "\n";
}

// Helper function for size formatting
function size_format($bytes, $decimals = 0) {
    $units = array('B', 'KB', 'MB', 'GB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $decimals) . ' ' . $units[$pow];
}

echo "\n=== Advanced Test Complete ===\n";
echo "Plugin functionality analysis finished.\n";
echo "Ready for WordPress integration and production testing.\n";
