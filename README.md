# Feed Favorites

![WordPress](https://img.shields.io/badge/WordPress-6.9-blue)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue)
![License](https://img.shields.io/badge/License-GPL%20v2+-green)

**Feed Favorites** is a modern WordPress plugin that automatically synchronizes your starred articles from RSS feeds with your WordPress site and allows manual creation of favorite link posts with summary and commentary.

## Features

- **Automatic synchronization** with RSS feeds
- **Manual creation** of favorite link posts with summary and commentary
- **Native WordPress post meta** exclusively
- **Hybrid template system** (theme templates + plugin fallback)
- **Kevin Quirk-style display** (summary + commentary + external link)
- **Post format support** (link format)
- **WordPress post creation** from starred articles
- **Complete metadata management** (title, URL, date, etc.)
- **Scheduled synchronization** via WordPress cron
- **Intuitive administration interface**
- **Robust data validation**
- **Detailed logging system**
- **SEO integration** (OpenGraph, Twitter Cards, structured data)
- **Modern modular architecture**

## Requirements

- WordPress 5.0 or higher
- PHP 8.2 or higher
- RSS feed with starred articles (for RSS import)

## Installation

### Manual Installation

1. Download the plugin
2. Upload the folder to `/wp-content/plugins/`
3. Activate the plugin via the 'Plugins' menu in WordPress
4. Configure the plugin in **Settings > Feed Favorites**

### Installation via Git

```bash
cd wp-content/plugins/
git clone https://github.com/jaz-on/feed-favorites.git
```

### Branches, Git Updater, and releases

- **`main`**: stable line; production sites and [Git Updater](https://git-updater.com/) should follow this branch (`Primary Branch: main` in the plugin header).
- **`dev`**: integration branch for ongoing work; merge into `main` after validation, then tag a release on `main` (for example `v1.0.1`).

### Manual QA checklist (before merging `dev` → `main`)

1. Activate the plugin on a test site (PHP 8.2+, WordPress version you target).
2. **Settings → Feed Favorites**: save options, run a manual sync with a real feed URL.
3. **Favorites → Add New**: publish a manual favorite (external URL, optional summary/commentary); confirm single view and front-end template.
4. If you use it: run a **JSON import** sample and confirm posts and logs.
5. Review **logs/statistics** in admin for errors.

## Configuration

### Basic Configuration

1. **Feed URL**: Enter your RSS feed URL
2. **Automatic synchronization**: Enable/disable automatic sync
3. **Frequency**: Set synchronization interval
4. **Maximum articles**: Limit number of articles per sync

### Advanced Configuration

- **Manual Creation**: Create favorite link posts manually with external URL, summary, and commentary
- **Display Options**: Configure emoji display, link behavior, and required fields
- **Templates**: Create `single-favorite.php` in your theme or use the default template
- **Template Functions**: Use functions like `feed_favorites_get_url()`, `feed_favorites_get_summary()`, etc.
- **Logs**: Configure logging detail level

## Template System

The plugin uses a hybrid template system:

1. **Theme Templates**: If your theme has `single-favorite.php` or `content-favorite.php`, it will be used automatically
2. **Plugin Fallback**: If no theme template exists, the plugin provides a default template with Kevin Quirk-style structure

### Creating a Theme Template

Create `single-favorite.php` in your theme root:

```php
<?php
get_header();
while ( have_posts() ) :
    the_post();
    ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'feed-favorite-post' ); ?>>
        <h1><?php the_title(); ?></h1>
        
        <?php if ( feed_favorites_get_summary() ) : ?>
            <div class="summary">
                <?php feed_favorites_the_summary(); ?>
            </div>
        <?php endif; ?>
        
        <?php if ( feed_favorites_get_commentary() ) : ?>
            <div class="commentary">
                <?php feed_favorites_the_commentary(); ?>
            </div>
        <?php endif; ?>
        
        <?php if ( feed_favorites_get_url() ) : ?>
            <a href="<?php feed_favorites_the_url(); ?>" class="external-link">
                Read Original
            </a>
        <?php endif; ?>
    </article>
    <?php
endwhile;
get_footer();
```

### Template Functions

All template functions are prefixed with `feed_favorites_`:

- `feed_favorites_get_url( $post_id = null )` - Get external URL
- `feed_favorites_the_url( $post_id = null )` - Display external URL
- `feed_favorites_get_summary( $post_id = null )` - Get link summary
- `feed_favorites_the_summary( $post_id = null )` - Display link summary
- `feed_favorites_get_commentary( $post_id = null )` - Get commentary
- `feed_favorites_the_commentary( $post_id = null )` - Display commentary
- `feed_favorites_get_external_link( $post_id = null, $text = null, $class = 'feed-favorites-external-link' )` - Get external link HTML
- `feed_favorites_the_external_link( $post_id = null, $text = null, $class = 'feed-favorites-external-link' )` - Display external link
- `feed_favorites_get_source_author( $post_id = null )` - Get source author
- `feed_favorites_the_source_author( $post_id = null )` - Display source author
- `feed_favorites_get_source_site( $post_id = null )` - Get source site
- `feed_favorites_the_source_site( $post_id = null )` - Display source site
- `feed_favorites_get_source_attribution( $post_id = null )` - Get source attribution HTML
- `feed_favorites_the_source_attribution( $post_id = null )` - Display source attribution
- `feed_favorites_is_link( $post_id = null )` - Check if post is a link post
- `feed_favorites_is_manual( $post_id = null )` - Check if post is manually created
- `feed_favorites_is_rss_import( $post_id = null )` - Check if post is RSS imported

## Architecture

The plugin uses a modular architecture:

```
feed-favorites/
├── feed-favorites.php          # Main entry point
├── includes/                   # Plugin classes
│   ├── class-feedfavorites.php # Main class (singleton)
│   ├── class-config.php        # Configuration management
│   ├── class-validator.php     # Data validation
│   ├── class-http.php          # HTTP requests
│   ├── class-ajax.php          # AJAX handling
│   ├── class-components.php    # Admin UI components
│   ├── class-admin.php         # Admin screens
│   ├── class-sync.php          # Synchronization
│   ├── class-import.php        # JSON import
│   ├── class-logger.php        # Logging
│   ├── core/
│   │   └── class-post-meta.php # Native post meta management
│   ├── creation/
│   │   └── class-manual-creator.php # Manual post creation
│   ├── admin/
│   │   └── class-native-meta-boxes.php # Native meta boxes
│   ├── display/
│   │   ├── class-template-tags.php # Template tags
│   │   ├── template-functions.php # Public template functions
│   │   ├── class-template-loader.php # Template loading
│   │   └── class-frontend-filters.php # Frontend content filters
│   └── integrations/
│       └── class-seo-integration.php # SEO meta tags
├── admin/
│   ├── js/
│   ├── css/
│   └── views/
├── assets/
│   ├── css/
│   └── js/
├── templates/
│   └── single-favorite.php # Default template
└── languages/
```

## Security

- All admin actions require proper capabilities and nonces
- AJAX requests are rate-limited and validate input URLs
- XML parsing forbids external network access and limits response size
- All data is sanitized and validated before storage

## Privacy

This plugin does not transmit personal data. It stores options and a capped in-database log (100 entries). Uninstall removes options, logs, transients, terms, and `favorite` posts.

## Accessibility

The admin UI uses native WordPress components (buttons, notices, tables) and supports keyboard navigation.

## Usage

### Manual Synchronization

Use the admin interface or programmatically:

```php
$sync = new Sync();
$result = $sync->manual_sync();
```

### Programmatic Configuration

```php
// Modify configuration
Config::set('feed_url', 'https://example.com/feed.xml');
Config::set('auto_sync', true);
Config::set('sync_interval', '7200');
```

### Manual Post Creation

```php
$data = array(
    'title' => 'Article Title',
    'external_url' => 'https://example.com/article',
    'link_summary' => 'Article summary...',
    'link_commentary' => 'My thoughts...',
    'source_author' => 'Author Name',
    'source_site' => 'Site Name',
);

$post_id = Manual_Creator::create_link_post($data);
```

## FAQ

### How do I customize the display?

Create `single-favorite.php` in your theme root directory. The plugin will automatically use it. If no theme template exists, the plugin provides a default template.

### Can I require summary or commentary?

Yes. In the admin settings under "Display Options", you can enable "Require Link Summary" and "Require Commentary".

### How are duplicates detected?

Duplicates are detected using the external URL. The plugin checks both the native `EXTERNAL_URL` meta and the legacy `feed_link` meta for compatibility.

### What post format is used?

All favorite posts use the WordPress 'link' post format for better theme integration.

## Changelog

### 1.0.1

- Stable branch for Git Updater set to `main`; documentation for branch workflow and manual QA.
- Aligned plugin headers and `readme.txt` with **Tested up to: 6.9**.
- Continuous integration: PHPCS and PHPUnit (WordPress test suite) via GitHub Actions; `phpunit.xml.dist` and `bin/install-wp-tests.sh` for automated tests.
- Migration runner only executes the 1.0.0 data migration when upgrading from below 1.0.0 (avoids re-running on 1.0.1).

### 1.0.0

- Initial release
- Automatic RSS synchronization
- Manual creation of favorite link posts
- Native WordPress post meta exclusively
- Hybrid template system (theme templates + plugin fallback)
- Kevin Quirk-style display (summary + commentary + external link)
- Post format support (link format)
- Template functions for theme developers
- SEO integration (OpenGraph, Twitter Cards, structured data)
- Complete administration interface
- Robust data validation
- Detailed logging system
- Modern modular architecture

## Support

- **Repository**: https://github.com/jaz-on/feed-favorites
- **Issues**: https://github.com/jaz-on/feed-favorites/issues
- **Releases**: https://github.com/jaz-on/feed-favorites/releases

## License

This project is licensed under GPL v2 or later. See the [LICENSE](LICENSE) file for details.

## Author

**Jason Rouet**

- GitHub: [@jaz-on](https://github.com/jaz-on)
- Website: [jasonrouet.com](https://jasonrouet.com)
