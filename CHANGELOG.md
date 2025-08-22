# Changelog

All notable changes to the **Feed Favorites** plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Support for multiple RSS feed formats
- Enhanced error handling and recovery
- Performance optimizations for large imports

### Changed
- Improved user interface responsiveness
- Enhanced logging system with better categorization

### Fixed
- Memory usage optimization for large JSON imports
- Better handling of malformed RSS feeds

## [1.0.0] - 2024-07-17

### Added
- Initial complete version
- Complete refactoring of the original code
- Modular architecture with 9 specialized classes
- Automatic synchronization with RSS feeds
- Complete and intuitive administration interface
- Detailed logging system for debugging
- Robust data validation
- Complete error handling
- Scheduled synchronization via WordPress cron
- ACF Pro support for custom fields
- Modern file naming (without class- prefix)
- Complete documentation (readme.txt, README.md, license.txt)
- Standard WordPress structure respected
- Enhanced security (AJAX nonces, permission validation)
- WordPress 5.0+ and PHP 7.4+ compatibility
- Ready for WordPress.org and GitHub distribution

### Technical Features
- **Core Class**: Main plugin initialization and management
- **Config Class**: Centralized configuration management
- **Validator Class**: Data validation and sanitization
- **Http Class**: HTTP client for RSS feed fetching
- **Ajax Class**: AJAX request handling
- **Components Class**: Reusable administration components
- **Admin Class**: Administration interface management
- **Sync Class**: Data synchronization logic
- **Logger Class**: Comprehensive logging system
- **Import Class**: JSON import functionality

### User Features
- **Manual Synchronization**: On-demand RSS feed synchronization
- **Automatic Synchronization**: Scheduled sync via WordPress cron
- **JSON Import**: Bulk import of historical favorites
- **Real-time Validation**: Live feed URL validation
- **Progress Tracking**: Import progress monitoring
- **Statistics Dashboard**: Comprehensive sync statistics
- **Log Management**: Detailed activity logging
- **Data Reset**: Maintenance tools for data management

### Supported Formats
- **RSS Feeds**: Standard RSS 2.0 format
- **JSON Exports**: Feedbin, FreshRSS, Google Reader formats
- **ACF Integration**: Advanced Custom Fields Pro support
- **Custom Post Types**: Automatic 'favorite' CPT creation

### Security Features
- **Nonce Verification**: AJAX security with WordPress nonces
- **Permission Checks**: User capability validation
- **Data Sanitization**: Input/output sanitization
- **Error Handling**: Comprehensive error management

### Performance Features
- **Batch Processing**: Configurable import batch sizes
- **Memory Optimization**: Efficient memory usage for large imports
- **Cron Integration**: WordPress cron for background processing
- **Cache Management**: Optimized data caching

---

## Version Compatibility

| Version | WordPress | PHP | ACF Pro |
|---------|-----------|-----|---------|
| 1.0.0   | 5.0+      | 7.4+ | Required |

## Upgrade Notes

### From Previous Versions
- This is the initial release of the refactored plugin
- No upgrade path from previous versions
- Fresh installation recommended

### Breaking Changes
- Custom Post Type renamed from `feedbin_star` to `favorite`
- ACF field names updated (e.g., `feed_link` instead of `feedbin_link`)
- Hook names updated (e.g., `feed_favorites_sync` instead of `feedbin_stars_sync`)
- Option names updated (e.g., `feed_favorites_feed_url` instead of `feedbin_stars_feed_url`)

## Support

For support and questions:
- **Documentation**: [Project Wiki](https://github.com/jaz-on/feed-favorites/wiki)
- **Issues**: [GitHub Issues](https://github.com/jaz-on/feed-favorites/issues)
- **Discussions**: [GitHub Discussions](https://github.com/jaz-on/feed-favorites/discussions)

---

*This changelog is maintained according to the [Keep a Changelog](https://keepachangelog.com/) standard.*
