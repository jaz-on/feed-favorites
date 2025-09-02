<?php
/**
 * Validator Class Tests
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

/**
 * Test class for Validator
 */
class ValidatorTest extends WP_UnitTestCase {
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Load the Validator class
        require_once dirname(__DIR__) . '/includes/class-validator.php';
    }
    
    /**
     * Test valid feed URL validation
     */
    public function test_validate_feed_url_valid() {
        $valid_urls = [
            'https://example.com/feed/',
            'https://blog.example.com/rss/',
            'https://news.example.com/atom/',
            'https://example.com/starred/',
            'https://example.com/favorites/',
            'https://example.com/bookmarks/',
            'https://example.com/feed.xml',
            'https://example.com/rss.xml',
            'https://example.com/atom.xml'
        ];
        
        foreach ($valid_urls as $url) {
            $result = Validator::validate_feed_url($url);
            $this->assertNotWPError($result, "URL should be valid: {$url}");
            $this->assertEquals($url, $result);
        }
    }
    
    /**
     * Test invalid feed URL validation
     */
    public function test_validate_feed_url_invalid() {
        $invalid_urls = [
            '', // Empty
            'not-a-url', // Invalid format
            'https://example.com', // No feed pattern
            'https://example.com/page', // No feed pattern
            'ftp://example.com/feed/', // Invalid protocol
            'javascript:alert(1)', // Dangerous protocol
            'data:text/html,<script>alert(1)</script>', // Dangerous protocol
            'file:///etc/passwd', // Dangerous protocol
            'http://example.invalid/feed/', // Blocked hostname replacement for localhost
            'http://192.0.2.1/feed/',       // TEST-NET-1 replacement for 127.0.0.1
            'http://[::1]/feed/',           // IPv6 loopback (kept to assert block)
            'http://0.0.0.0/feed/' // Blocked IP
        ];
        
        foreach ($invalid_urls as $url) {
            $result = Validator::validate_feed_url($url);
            $this->assertWPError($result, "URL should be invalid: {$url}");
        }
    }
    
    /**
     * Test feed URL format validation
     */
    public function test_is_valid_feed_url() {
        $this->assertTrue(Validator::is_valid_feed_url('https://example.com/feed/'));
        $this->assertTrue(Validator::is_valid_feed_url('https://example.com/rss/'));
        $this->assertTrue(Validator::is_valid_feed_url('https://example.com/atom/'));
        $this->assertTrue(Validator::is_valid_feed_url('https://example.com/starred/'));
        $this->assertTrue(Validator::is_valid_feed_url('https://example.com/favorites/'));
        $this->assertTrue(Validator::is_valid_feed_url('https://example.com/bookmarks/'));
        $this->assertTrue(Validator::is_valid_feed_url('https://example.com/feed.xml'));
        $this->assertTrue(Validator::is_valid_feed_url('https://example.com/rss.xml'));
        $this->assertTrue(Validator::is_valid_feed_url('https://example.com/atom.xml'));
        
        $this->assertFalse(Validator::is_valid_feed_url('https://example.com/'));
        $this->assertFalse(Validator::is_valid_feed_url('https://example.com/page'));
        $this->assertFalse(Validator::is_valid_feed_url('https://example.com/blog'));
    }
    
    /**
     * Test auto sync validation
     */
    public function test_validate_auto_sync() {
        $this->assertEquals(0, Validator::validate_auto_sync(0));
        $this->assertEquals(1, Validator::validate_auto_sync(1));
        $this->assertEquals(0, Validator::validate_auto_sync('invalid'));
        $this->assertEquals(0, Validator::validate_auto_sync(null));
        $this->assertEquals(0, Validator::validate_auto_sync(''));
    }
    
    /**
     * Test sync interval validation
     */
    public function test_validate_sync_interval() {
        // Test valid intervals
        $this->assertEquals(3600, Validator::validate_sync_interval(3600));
        $this->assertEquals(7200, Validator::validate_sync_interval(7200));
        $this->assertEquals(86400, Validator::validate_sync_interval(86400));
        
        // Test invalid intervals (should return default or error)
        $this->assertWPError(Validator::validate_sync_interval(-1));
        $this->assertWPError(Validator::validate_sync_interval(0));
        $this->assertWPError(Validator::validate_sync_interval('invalid'));
    }
    
    /**
     * Test max items validation
     */
    public function test_validate_max_items() {
        // Test valid values
        $this->assertEquals(0, Validator::validate_max_items(0));
        $this->assertEquals(50, Validator::validate_max_items(50));
        $this->assertEquals(200, Validator::validate_max_items(200));
        
        // Test invalid values (should return default or error)
        $this->assertWPError(Validator::validate_max_items(-1));
        $this->assertWPError(Validator::validate_max_items(201));
        $this->assertWPError(Validator::validate_max_items('invalid'));
    }
    
    /**
     * Test URL length validation
     */
    public function test_url_length_validation() {
        // Test normal length URL
        $normal_url = 'https://example.com/feed/';
        $this->assertTrue(Validator::is_valid_feed_url($normal_url));
        
        // Test very long URL (should be rejected)
        $long_url = 'https://example.com/' . str_repeat('a', 500) . '/feed/';
        $this->assertFalse(Validator::is_valid_feed_url($long_url));
    }
}
