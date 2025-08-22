# Feed Favorites - Assets Directory

This directory contains assets for the Feed Favorites WordPress plugin.

## Structure

```
assets/
├── README.md           # This file
├── .gitkeep           # Git placeholder file
└── index.php          # Security file (empty)
```

## Purpose

The `assets` directory is intended for:
- Plugin images and icons
- CSS and JavaScript files (if not using admin/ directory)
- Media files
- Documentation assets

## Image Specifications

When adding images to this directory, follow these guidelines:

### Icons
- **Format**: SVG preferred, PNG acceptable
- **Size**: 16x16px to 64x64px
- **Style**: Consistent with WordPress admin design
- **Naming**: Use descriptive names (e.g., `sync-icon.svg`, `stats-chart.png`)

### Screenshots
- **Format**: PNG or JPG
- **Size**: 1280x720px (16:9 ratio)
- **Quality**: High quality for documentation
- **Naming**: Use descriptive names (e.g., `admin-dashboard.png`, `import-interface.png`)

### Logos
- **Format**: SVG preferred
- **Size**: Scalable vector format
- **Style**: Professional, consistent with plugin branding
- **Naming**: Use descriptive names (e.g., `plugin-logo.svg`, `wordpress-org-banner.png`)

## Security

The `index.php` file in this directory prevents direct access to assets when the plugin is not properly loaded through WordPress.

## Usage

Assets in this directory can be referenced in your plugin code using:

```php
$plugin_url = plugin_dir_url(__FILE__);
$image_url = $plugin_url . 'assets/icon.png';
```

## Contributing

When adding new assets:
1. Ensure they follow the naming conventions
2. Optimize images for web use
3. Use appropriate formats (SVG for icons, PNG/JPG for photos)
4. Update this README if adding new asset types 