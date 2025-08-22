<?php
/**
 * Security Test Script for Feed Favorites Plugin
 * 
 * This script tests for common security vulnerabilities
 * WARNING: Only run in a controlled testing environment
 */

echo "=== Feed Favorites Security Test ===\n\n";

// Mock WordPress functions for security testing
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) {
        return $nonce === 'valid_nonce'; // Mock for testing
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return $capability === 'manage_options'; // Mock for testing
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message) {
        echo "WP_DIE: " . $message . PHP_EOL;
        return false;
    }
}

if (!function_exists('sanitize_url')) {
    function sanitize_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return filter_var($str, FILTER_SANITIZE_STRING);
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($content) {
        return strip_tags($content, '<p><br><strong><em><a>');
    }
}

// Test 1: Nonce Verification
echo "1. Testing Nonce Verification...\n";
try {
    $files_to_check = [
        'includes/import.php',
        'includes/ajax.php',
        'includes/sync.php'
    ];
    
    $nonce_issues = [];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Check for nonce verification
            if (strpos($content, 'wp_verify_nonce') === false) {
                $nonce_issues[] = "$file: Missing nonce verification";
            }
            
            // Check for nonce creation
            if (strpos($content, 'wp_create_nonce') === false) {
                $nonce_issues[] = "$file: Missing nonce creation";
            }
        }
    }
    
    if (empty($nonce_issues)) {
        echo "   ✓ All files have nonce verification\n";
    } else {
        echo "   ⚠ Nonce verification issues found:\n";
        foreach ($nonce_issues as $issue) {
            echo "     - " . $issue . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Nonce test error: " . $e->getMessage() . "\n";
}

// Test 2: Capability Checks
echo "\n2. Testing Capability Checks...\n";
try {
    $capability_issues = [];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Check for capability checks
            if (strpos($content, 'current_user_can') === false) {
                $capability_issues[] = "$file: Missing capability checks";
            }
            
            // Check for specific capabilities
            if (strpos($content, 'manage_options') === false && 
                strpos($content, 'current_user_can') !== false) {
                $capability_issues[] = "$file: Using generic capability checks";
            }
        }
    }
    
    if (empty($capability_issues)) {
        echo "   ✓ All files have proper capability checks\n";
    } else {
        echo "   ⚠ Capability check issues found:\n";
        foreach ($capability_issues as $issue) {
            echo "     - " . $issue . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Capability test error: " . $e->getMessage() . "\n";
}

// Test 3: Input Sanitization
echo "\n3. Testing Input Sanitization...\n";
try {
    $sanitization_issues = [];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Check for input sanitization
            $sanitization_functions = [
                'sanitize_url',
                'sanitize_text_field',
                'sanitize_email',
                'wp_kses_post',
                'esc_url',
                'esc_html',
                'esc_attr'
            ];
            
            $has_sanitization = false;
            foreach ($sanitization_functions as $func) {
                if (strpos($content, $func) !== false) {
                    $has_sanitization = true;
                    break;
                }
            }
            
            if (!$has_sanitization) {
                $sanitization_issues[] = "$file: Missing input sanitization";
            }
            
            // Check for direct $_POST/$_GET usage
            if (preg_match('/\$_POST\[[^\]]+\]/', $content) || 
                preg_match('/\$_GET\[[^\]]+\]/', $content)) {
                $sanitization_issues[] = "$file: Direct superglobal access detected";
            }
        }
    }
    
    if (empty($sanitization_issues)) {
        echo "   ✓ All files have proper input sanitization\n";
    } else {
        echo "   ⚠ Input sanitization issues found:\n";
        foreach ($sanitization_issues as $issue) {
            echo "     - " . $issue . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Sanitization test error: " . $e->getMessage() . "\n";
}

// Test 4: SQL Injection Prevention
echo "\n4. Testing SQL Injection Prevention...\n";
try {
    $sql_issues = [];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Check for wpdb usage
            if (strpos($content, 'wpdb') !== false) {
                // Check for prepared statements
                if (strpos($content, 'prepare') === false) {
                    $sql_issues[] = "$file: Using wpdb without prepared statements";
                }
                
                // Check for direct query construction
                if (preg_match('/\$wpdb->query\s*\(\s*[\'"]\s*SELECT.*\$\w+/', $content)) {
                    $sql_issues[] = "$file: Potential SQL injection in SELECT queries";
                }
                
                if (preg_match('/\$wpdb->query\s*\(\s*[\'"]\s*INSERT.*\$\w+/', $content)) {
                    $sql_issues[] = "$file: Potential SQL injection in INSERT queries";
                }
                
                if (preg_match('/\$wpdb->query\s*\(\s*[\'"]\s*UPDATE.*\$\w+/', $content)) {
                    $sql_issues[] = "$file: Potential SQL injection in UPDATE queries";
                }
            }
        }
    }
    
    if (empty($sql_issues)) {
        echo "   ✓ No SQL injection vulnerabilities detected\n";
    } else {
        echo "   ⚠ SQL injection risks found:\n";
        foreach ($sql_issues as $issue) {
            echo "     - " . $issue . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ SQL injection test error: " . $e->getMessage() . "\n";
}

// Test 5: XSS Prevention
echo "\n5. Testing XSS Prevention...\n";
try {
    $xss_issues = [];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Check for output escaping
            $escaping_functions = [
                'esc_html',
                'esc_attr',
                'esc_url',
                'wp_kses_post'
            ];
            
            $has_escaping = false;
            foreach ($escaping_functions as $func) {
                if (strpos($content, $func) !== false) {
                    $has_escaping = true;
                    break;
                }
            }
            
            // Check for echo statements without escaping
            if (preg_match('/echo\s+\$[^;]+;/', $content) && !$has_escaping) {
                $xss_issues[] = "$file: Echo statements without proper escaping";
            }
            
            // Check for direct variable output
            if (preg_match('/<\?php\s+echo\s+\$[^;]+;\s*\?>/', $content)) {
                $xss_issues[] = "$file: Direct variable output detected";
            }
        }
    }
    
    if (empty($xss_issues)) {
        echo "   ✓ No XSS vulnerabilities detected\n";
    } else {
        echo "   ⚠ XSS risks found:\n";
        foreach ($xss_issues as $issue) {
            echo "     - " . $issue . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ XSS test error: " . $e->getMessage() . "\n";
}

// Test 6: File Upload Security
echo "\n6. Testing File Upload Security...\n";
try {
    $upload_issues = [];
    
    $import_file = 'includes/import.php';
    if (file_exists($import_file)) {
        $content = file_get_contents($import_file);
        
        // Check for file type validation
        if (strpos($content, 'pathinfo') === false && strpos($content, 'mime_content_type') === false) {
            $upload_issues[] = "Missing file type validation";
        }
        
        // Check for file size limits
        if (strpos($content, 'filesize') === false && strpos($content, '$_FILES') !== false) {
            $upload_issues[] = "Missing file size validation";
        }
        
        // Check for MIME type validation
        if (strpos($content, 'finfo') === false && strpos($content, 'mime_content_type') === false) {
            $upload_issues[] = "Missing MIME type validation";
        }
        
        // Check for temporary file handling
        if (strpos($content, 'is_uploaded_file') === false) {
            $upload_issues[] = "Missing uploaded file validation";
        }
    }
    
    if (empty($upload_issues)) {
        echo "   ✓ File upload security measures in place\n";
    } else {
        echo "   ⚠ File upload security issues found:\n";
        foreach ($upload_issues as $issue) {
            echo "     - " . $issue . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ File upload test error: " . $e->getMessage() . "\n";
}

// Test 7: Rate Limiting
echo "\n7. Testing Rate Limiting...\n";
try {
    $rate_limit_issues = [];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Check for rate limiting mechanisms
            if (strpos($content, 'transient') === false && 
                strpos($content, 'option') === false &&
                strpos($content, 'wp_cache') === false) {
                
                // Check if this file handles AJAX requests
                if (strpos($content, 'wp_ajax') !== false || 
                    strpos($content, 'admin_post') !== false) {
                    $rate_limit_issues[] = "$file: No rate limiting detected for AJAX endpoints";
                }
            }
        }
    }
    
    if (empty($rate_limit_issues)) {
        echo "   ✓ Rate limiting mechanisms detected\n";
    } else {
        echo "   ⚠ Rate limiting issues found:\n";
        foreach ($rate_limit_issues as $issue) {
            echo "     - " . $issue . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Rate limiting test error: " . $e->getMessage() . "\n";
}

// Security Score Calculation
echo "\n=== Security Score Calculation ===\n";

$total_tests = 7;
$passed_tests = 0;
$security_issues = [];

// Collect all issues
$all_issues = array_merge(
    $nonce_issues ?? [],
    $capability_issues ?? [],
    $sanitization_issues ?? [],
    $sql_issues ?? [],
    $xss_issues ?? [],
    $upload_issues ?? [],
    $rate_limit_issues ?? []
);

$critical_issues = 0;
$high_issues = 0;
$medium_issues = 0;

foreach ($all_issues as $issue) {
    if (strpos($issue, 'Missing nonce') !== false || 
        strpos($issue, 'Missing capability') !== false ||
        strpos($issue, 'SQL injection') !== false) {
        $critical_issues++;
    } elseif (strpos($issue, 'Missing sanitization') !== false ||
              strpos($issue, 'XSS') !== false) {
        $high_issues++;
    } else {
        $medium_issues++;
    }
}

// Calculate score
$critical_score = $critical_issues * 10;
$high_score = $high_issues * 5;
$medium_score = $medium_issues * 2;
$total_score = 100 - $critical_score - $high_score - $medium_score;
$total_score = max(0, $total_score);

echo "Security Score: " . $total_score . "/100\n";
echo "Critical Issues: " . $critical_issues . " (-" . $critical_score . " points)\n";
echo "High Issues: " . $high_issues . " (-" . $high_score . " points)\n";
echo "Medium Issues: " . $medium_issues . " (-" . $medium_score . " points)\n";

if ($total_score >= 90) {
    echo "Status: ✅ EXCELLENT - Plugin is secure\n";
} elseif ($total_score >= 70) {
    echo "Status: ⚠️ GOOD - Minor security improvements needed\n";
} elseif ($total_score >= 50) {
    echo "Status: ⚠️ FAIR - Significant security improvements needed\n";
} else {
    echo "Status: ❌ POOR - Critical security improvements required\n";
}

echo "\n=== Security Test Complete ===\n";
echo "Total security issues found: " . count($all_issues) . "\n";
echo "Recommendation: Address critical and high priority issues immediately\n";
