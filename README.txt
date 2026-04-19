=== Feed Favorites ===
Contributors: jasonrouet
Tags: rss, synchronization, import, bookmarks, favorites
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Synchronize starred RSS articles with WordPress and keep a curated archive.

== Description ==

Feed Favorites synchronizes starred articles from RSS feeds with your WordPress site and allows manual creation of favorite link posts. It creates WordPress posts with preserved metadata and provides scheduling, validation, and logging features for reliable synchronization.

= Features =

- Automatic synchronization of starred RSS items
- Manual creation of favorite link posts with summary and commentary
- Native WordPress post meta exclusively
- Hybrid template system (theme templates + plugin fallback)
- Kevin Quirk-style display (summary + commentary + external link)
- Post format support (link format)
- Scheduling via WordPress cron
- JSON import for historical data
- Data validation and logging
- SEO integration (OpenGraph, Twitter Cards, structured data)

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

Yes. The plugin registers a `favorite` post type with a hybrid template system:
- Create `single-favorite.php` in your theme to fully customize the display
- Use template functions like `feed_favorites_get_url()`, `feed_favorites_get_summary()`, etc.
- If no theme template exists, the plugin provides a default template with Kevin Quirk-style structure
- All posts use the 'link' post format for better theme integration

= How do I create favorite posts manually? =

1. Go to Favorites → Add New
2. Enter the post title
3. Fill in the External URL (required)
4. Add a Link Summary (optional, can be required in settings)
5. Add your Commentary (optional, can be required in settings)
6. Optionally add Source Author and Source Site
7. Publish the post

= Is the plugin secure? =

Nonce verification, capability checks, and data sanitization are implemented for all operations.

== Screenshots ==

1. Setup screen
2. Import screen
3. Logs and statistics

== Template System ==

The plugin uses a hybrid template system:

1. **Theme Templates**: If your theme has `single-favorite.php` or `content-favorite.php`, it will be used automatically
2. **Plugin Fallback**: If no theme template exists, the plugin provides a default template

== Template Functions ==

All template functions are prefixed with `feed_favorites_`:

- `feed_favorites_get_url()` - Get external URL
- `feed_favorites_get_summary()` - Get link summary
- `feed_favorites_get_commentary()` - Get commentary
- `feed_favorites_get_external_link()` - Get external link HTML
- `feed_favorites_get_source_author()` - Get source author
- `feed_favorites_get_source_site()` - Get source site
- `feed_favorites_is_link()` - Check if post is a link post
- `feed_favorites_is_manual()` - Check if post is manually created
- `feed_favorites_is_rss_import()` - Check if post is RSS imported

== Cron ==

The plugin schedules a periodic synchronization. The frequency can be adjusted in Settings. Custom intervals: 15 minutes, 30 minutes, 1 hour, 2 hours (default), 4 hours, daily.

== Privacy ==

This plugin does not send data to third-party services. It stores:
- Options under the `feed_favorites_*` keys
- A capped in-database log (maximum 100 entries)
- Imported posts in the `favorite` post type
- Native WordPress post meta for link data

Upon uninstall, options, transients, logs, and `favorite` posts are removed.

== Accessibility ==

The admin UI uses native WordPress components (buttons, notices, tables) and supports keyboard navigation. No front-end scripts are injected by the plugin.

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

== Changelog ==

= 1.0.1 =
* Git Updater: primary branch set to `main` for stable updates
* Aligned plugin metadata (Tested up to 6.9) and developer documentation (branch workflow, manual QA checklist)
* CI: GitHub Actions for PHPCS and PHPUnit; `phpunit.xml.dist` and WordPress test installer under `bin/`
* Migration: run ACF/native meta migration only when upgrading from a version below 1.0.0

= 1.0.0 =
* Initial release
* Automatic RSS synchronization
* Manual creation of favorite link posts
* Native WordPress post meta exclusively
* Hybrid template system (theme templates + plugin fallback)
* Kevin Quirk-style display (summary + commentary + external link)
* Post format support (link format)
* Template functions for theme developers
* SEO integration (OpenGraph, Twitter Cards, structured data)
* Complete administration interface
* Robust data validation
* Detailed logging system
* Modern modular architecture
