=== Feed Favorites ===
Contributors: jasonrouet
Tags: rss, feed, favorites, bookmarks, synchronization, import, acf
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically synchronize your starred articles from RSS feeds with WordPress. Create a complete history of your favorite content.

== Description ==

**Feed Favorites** is a modern WordPress plugin that automatically synchronizes your starred articles from RSS feeds with your WordPress site. Perfect for content curators, researchers, and anyone who wants to maintain a comprehensive collection of their favorite articles.

= Key Features =

* **Automatic RSS Synchronization**: Seamlessly sync starred articles from any RSS feed
* **WordPress Integration**: Creates custom posts with full metadata preservation
* **ACF Pro Support**: Advanced Custom Fields integration for enhanced content management
* **Scheduled Synchronization**: Automated sync via WordPress cron jobs
* **Bulk Import**: Import historical favorites from JSON exports
* **Real-time Validation**: Live feed URL testing and validation
* **Comprehensive Logging**: Detailed activity tracking and debugging
* **Modern Architecture**: Clean, modular code following WordPress standards

= Use Cases =

* **Content Curation**: Build a personal library of favorite articles
* **Research Projects**: Maintain organized collections of research materials
* **Content Aggregation**: Create curated content sections on your website
* **Personal Knowledge Base**: Build a searchable archive of valuable content

= Supported Formats =

* **RSS Feeds**: Standard RSS 2.0 format
* **JSON Exports**: Feedbin, FreshRSS, Google Reader formats
* **ACF Integration**: Advanced Custom Fields Pro support
* **Custom Post Types**: Automatic 'favorite' CPT creation

= Technical Features =

* **Modular Architecture**: 9 specialized classes for maintainable code
* **Security First**: Nonce verification, permission checks, data sanitization
* **Performance Optimized**: Batch processing, memory management, cron integration
* **WordPress Standards**: Follows WordPress coding standards and best practices

== Installation ==

1. Upload the `feed-favorites` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Settings > Feed Favorites** to configure the plugin
4. Enter your RSS feed URL and configure synchronization options
5. Start synchronizing your favorite articles!

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* ACF Pro plugin (Advanced Custom Fields Pro)

== Frequently Asked Questions ==

= What RSS feeds are supported? =

The plugin supports standard RSS 2.0 feeds. It's specifically designed to work with Feedbin starred article feeds, but can work with any RSS feed containing article data.

= Can I import my existing favorites? =

Yes! The plugin includes a JSON import feature that supports multiple formats:
* Feedbin exports
* FreshRSS exports
* Google Reader exports
* Custom JSON formats

= How often does synchronization occur? =

You can configure the synchronization frequency:
* Manual synchronization (on-demand)
* Automatic synchronization (15 minutes to 24 hours)
* Recommended: 2 hours for most sites

= What happens to duplicate articles? =

The plugin automatically detects and skips duplicate articles based on the original URL, ensuring your database stays clean.

= Can I customize the post display? =

Absolutely! The plugin creates a custom post type called 'favorite' with ACF fields. You can:
* Create custom templates
* Use WordPress loops
* Customize with ACF field groups
* Apply your theme's styling

= Is the plugin secure? =

Yes, the plugin follows WordPress security best practices:
* Nonce verification for all AJAX requests
* User capability checks
* Data sanitization and validation
* Secure file handling

= What if my feed becomes unavailable? =

The plugin includes comprehensive error handling:
* Detailed logging of all issues
* Graceful degradation when feeds are unavailable
* Automatic retry mechanisms
* User notifications for problems

== Screenshots ==

1. **Administration Dashboard**: Clean, intuitive interface for managing synchronization
2. **Configuration Panel**: Easy setup of feed URLs and sync options
3. **Statistics Dashboard**: Comprehensive overview of synchronization activity
4. **Import Interface**: Bulk import of historical favorites
5. **Log Management**: Detailed activity tracking and debugging tools

== Changelog ==

= 1.0.0 =
* Initial release
* Complete modular architecture
* RSS feed synchronization
* JSON import functionality
* ACF Pro integration
* Comprehensive logging system
* WordPress 5.0+ compatibility
* PHP 7.4+ support

== Upgrade Notice ==

= 1.0.0 =
This is the initial release of the refactored plugin. Fresh installation recommended.

== Developer Notes ==

= Hooks and Filters =

The plugin provides several hooks for customization:

```php
// Before synchronization
add_action('feed_favorites_before_sync', function($feed_url) {
    // Custom code before sync
});

// After synchronization
add_action('feed_favorites_after_sync', function($result) {
    // Custom code after sync
});

// Modify item data
add_filter('feed_favorites_item_data', function($item_data, $original_item) {
    // Modify data before post creation
    return $item_data;
}, 10, 2);
```

= Custom Post Type =

The plugin automatically creates a 'favorite' custom post type with:
* Title: Article title
* Content: Article content/excerpt
* Custom fields: Original URL, author, source, publication date
* Archive support
* REST API support

= ACF Fields =

The following ACF fields are automatically created:
* `feed_link`: Original article URL
* `feed_author`: Article author
* `feed_source_title`: Source publication name
* `feed_source_url`: Source publication URL
* `feed_published_date`: Original publication date

== Support ==

For support and questions:
* **Documentation**: [Project Wiki](https://github.com/jaz-on/feed-favorites/wiki)
* **Issues**: [GitHub Issues](https://github.com/jaz-on/feed-favorites/issues)
* **Discussions**: [GitHub Discussions](https://github.com/jaz-on/feed-favorites/discussions)

== Credits ==

Developed by [Jason Rouet](https://jasonrouet.local)

Built with WordPress best practices and modern PHP standards.

== License ==

This plugin is licensed under the GPL v2 or later.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details. 