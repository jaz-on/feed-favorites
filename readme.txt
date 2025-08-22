=== Feed Favorites ===
Contributors: jasonrouet
Tags: rss, feed, favorites, bookmarks, synchronization, import, acf, content-curation
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Synchronize starred RSS articles with WordPress and keep a curated archive.

== Description ==

Feed Favorites synchronizes starred articles from RSS feeds with your WordPress site. It creates WordPress posts with preserved metadata and provides scheduling, validation, and logging features for reliable synchronization.

= Features =

- Automatic synchronization of starred RSS items
- Creates WordPress posts with metadata
- ACF Pro integration for custom fields
- Scheduling via WordPress cron
- JSON import for historical data
- Data validation and logging

== Installation ==

1. Upload the `feed-favorites` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the Plugins menu in WordPress
3. Go to Settings → Feed Favorites
4. Enter your RSS feed URL and configure synchronization options

== Frequently Asked Questions ==

= What RSS feeds are supported? =

Standard RSS 2.0 feeds. It works well with Feedbin starred article feeds and any feed containing article data.

= Can I import my existing favorites? =

Yes. JSON import supports Feedbin, FreshRSS, Google Reader, and custom formats.

= How often does synchronization occur? =

You can synchronize manually or automatically (15 minutes to 24 hours). A 2-hour interval fits most sites.

= What happens to duplicate articles? =

Duplicates are detected using the original URL and are skipped.

= Can I customize the post display? =

Yes. The plugin registers a `favorite` post type. You can create templates, use loops, and style with your theme and ACF field groups.

= Is the plugin secure? =

Nonce verification, capability checks, and data sanitization are implemented for all operations.

== Support ==

- Repository: https://github.com/jaz-on/feed-favorites
- Issues: https://github.com/jaz-on/feed-favorites/issues

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