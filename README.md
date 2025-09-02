# Feed Favorites

![WordPress](https://img.shields.io/badge/WordPress-6.5+-blue)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue)
![License](https://img.shields.io/badge/License-GPL%20v2+-green)

**Feed Favorites** is a modern WordPress plugin that automatically synchronizes your starred articles from RSS feeds with your WordPress site.

## Features

- **Automatic synchronization** with RSS feeds
- **WordPress post creation** from starred articles
- **Complete metadata management** (title, URL, date, etc.)
- **Scheduled synchronization** via WordPress cron
- **Intuitive administration interface**
- **Robust data validation**
- **Detailed logging system**
- **Modern modular architecture**

## Requirements

- WordPress 5.0 or higher
- PHP 8.2 or higher
- ACF Pro plugin (Advanced Custom Fields Pro)
- RSS feed with starred articles

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

## Configuration

### Basic Configuration

1. **Feed URL**: Enter your RSS feed URL
2. **Automatic synchronization**: Enable/disable automatic sync
3. **Frequency**: Set synchronization interval
4. **Maximum articles**: Limit number of articles per sync

### Advanced Configuration

- **ACF Fields**: Customize fields according to your needs
- **Templates**: Modify article display
- **Logs**: Configure logging detail level

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
│   └── class-logger.php        # Logging
├── admin/
│   ├── js/
│   ├── css/
│   └── views/
└── languages/
```

### Usage

Prefer using hooks and the main singleton:

```php
$plugin = FeedFavorites::get_instance();
// Or trigger a manual sync via AJAX action (secured) rather than instantiating classes directly.
```

## Security
- All admin actions require proper capabilities and nonces.
- AJAX requests are rate-limited and validate input URLs.
- XML parsing forbids external network access and limits response size.

## Privacy
This plugin does not transmit personal data. It stores options and a capped in-database log (100 entries). Uninstall removes options, logs, transients, terms, and `favorite` posts.

## Accessibility
The admin UI uses native WordPress components (buttons, notices, tables) and supports keyboard navigation.

### Main Classes

| Class | Responsibility |
|-------|----------------|
| `FeedFavorites` | Main plugin class |
| `Config` | Centralized configuration management |
| `Validator` | Centralized data validation |
| `Http` | HTTP request management |
| `Ajax` | AJAX request handling |
| `Components` | Administration components |
| `Admin` | Administration interface |
| `Sync` | Data synchronization |
| `Logger` | Log management |

## Usage

### Manual Synchronization

```php
// Manual synchronization
$sync = new Sync();
$result = $sync->manual_sync();
```

### Programmatic Configuration

```php
// Modify configuration
Config::set('feed_url', 'https://example.com/feed.xml');
Config::set('auto_sync', true);
Config::set('sync_interval', 'hourly');
```

### Hooks and Filters

```php
// Hook before synchronization
add_action('feed_favorites_before_sync', function($feed_url) {
    // Custom code before sync
});

// Hook after synchronization
add_action('feed_favorites_after_sync', function($result) {
    // Custom code after sync
});

// Filter to modify data
add_filter('feed_favorites_item_data', function($item_data, $original_item) {
    // Modify data
    return $item_data;
}, 10, 2);
```

## Logs and Debugging

The plugin includes a complete logging system:

```php
// Use logger
Logger::info('Synchronization started');
Logger::error('Sync error', $exception);
Logger::debug('Received data', $data);
```

### Log Levels

- **ERROR**: Critical errors
- **WARNING**: Warnings
- **INFO**: General information
- **DEBUG**: Debug information

## Testing

### Unit Tests

```bash
# Install test dependencies
composer install --dev

# Run tests
vendor/bin/phpunit
```

### Integration Tests

```bash
# Tests with WordPress
vendor/bin/phpunit --testsuite integration
```

## Development

### Code Structure

The plugin follows WordPress standards and PHP best practices:

- **PSR-4**: Class autoloading
- **PSR-12**: Coding standards
- **WordPress Coding Standards**: WordPress compliance

### Adding Features

1. **New class**: Create file in `includes/`
2. **Admin interface**: Add components in `components.php`
3. **Tests**: Write corresponding tests
4. **Documentation**: Update this documentation

### Contributing

1. Fork the project
2. Create a branch for your feature
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

## Changelog

### 1.0.0
- Initial version
- Automatic synchronization with RSS feeds
- Complete administration interface
- Integrated logging system
- Data validation
- ACF Pro support
- Modern modular architecture

## Support

- **Repository**: https://github.com/jaz-on/feed-favorites
- **Issues**: https://github.com/jaz-on/feed-favorites/issues
- **Releases**: https://github.com/jaz-on/feed-favorites/releases
- **Documentation**: https://github.com/jaz-on/feed-favorites/wiki
- **Discussions**: https://github.com/jaz-on/feed-favorites/discussions

## License

This project is licensed under GPL v2 or later. See the [LICENSE](LICENSE) file for details.

## Author

**Jason Rouet**

- GitHub: [@jaz-on](https://github.com/jaz-on)
- Website: [jasonrouet.com](https://jasonrouet.com)
- Email: [bonjour@jasonrouet.com](mailto:bonjour@jasonrouet.com)

## Acknowledgments

- [WordPress](https://wordpress.org/) for the platform
- [ACF Pro](https://www.advancedcustomfields.com/) for custom fields
- [RSS](https://en.wikipedia.org/wiki/RSS) for feed standards
