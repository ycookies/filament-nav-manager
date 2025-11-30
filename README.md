# Filament Nav Manager

A powerful navigation management package for Filament v4 that allows you to dynamically manage your Filament panel navigation menus through a user-friendly interface.

> 📖 [简体中文文档](README.zh_CN.md) | [English Documentation](README.md)

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/ycookies/filament-nav-manager)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue.svg)](https://php.net)
[![Filament Version](https://img.shields.io/badge/filament-%5E4.0-orange.svg)](https://filamentphp.com)

## Features

✨ **Rich Feature Set**
- 🎯 Dynamic navigation management through Filament UI
- 🔄 Automatic synchronization of Filament resources and pages
- 🌳 Tree-structured menu hierarchy support
- 🎨 Customizable navigation groups, icons, and badges
- 🔐 Role-based access control
- 🌍 Multi-language support (English, Simplified Chinese, Traditional Chinese)
- ⚡ Navigation caching for better performance
- 📦 Easy installation and setup

## Installation

You can install the package via Composer:

```bash
composer require ycookies/filament-nav-manager
```

### Publish and Run Migrations

```bash
php artisan filament-nav-manager:install
```

This command will:
1. Publish the configuration file
2. Publish migration files
3. Ask if you want to run migrations
4. Allow you to sync panels

Or manually:

```bash
php artisan vendor:publish --tag="filament-nav-manager-migrations"
php artisan migrate
php artisan vendor:publish --tag="filament-nav-manager-config"
```

## Configuration

### Enable the Plugin

Add the plugin to your Filament panel provider:

```php
use Ycookies\FilamentNavManager\FilamentNavManagerPlugin;
use Ycookies\FilamentNavManager\Models\NavManager;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(FilamentNavManagerPlugin::make())
        ->navigation(
            NavManager::generate()
                ->panel($panel->getId())
                ->cacheTime(config('nav-manager.cache_seconds', 0))
                ->toClosure()
        );
}
```

### Configure Permissions

Edit `config/nav-manager.php`:

```php
return [
    // Allow specific roles to access Nav Manager
    'allowed_roles' => ['admin', 'super_admin'], // or null for all authenticated users
    
    // Cache navigation for better performance (in seconds)
    'cache_seconds' => 3600, // 0 to disable caching
    
    // Database table name
    'table_name' => 'nav_manager',
    
    // Navigation group for Nav Manager resource in sidebar
    'navigation_group' => null, // null to use translation, or custom name like 'System', 'Settings'
];
```

### Customize Navigation Group

You can customize which navigation group the Nav Manager resource appears in:

```php
// config/nav-manager.php
return [
    'navigation_group' => 'System Settings', // Custom group name
    // or
    'navigation_group' => null, // Use default translation
];
```

## Usage

### Syncing Filament Resources and Pages

#### Option 1: Via UI

1. Navigate to "Navigation Manager" in your Filament panel
2. Click the "Sync Filament Menu" button
3. Confirm the sync operation

#### Option 2: Via Command Line

Sync a specific panel:

```bash
php artisan filament-nav-manager:sync admin
```

Or sync all panels during installation:

```bash
php artisan filament-nav-manager:install
```

### Managing Navigation Items

Once installed, you'll see "Navigation Manager" in your Filament navigation. From there you can:

- ✅ Create new navigation items
- ✅ Edit existing items
- ✅ Delete items
- ✅ Reorder items (drag and drop if tree view is enabled)
- ✅ Toggle visibility
- ✅ Manage icons and badges
- ✅ Set permissions

### Navigation Item Types

The package supports several navigation item types:

1. **Group** - A navigation group that can contain child items
2. **Resource** - Links to a Filament resource
3. **Page** - Links to a Filament page
4. **Route** - Links to a Laravel route
5. **URL** - Links to any URL (internal or external)

## Navigation Structure

Navigation items are organized in a hierarchical tree structure:

```
Navigation Group
├── Resource Item
├── Page Item
└── Navigation Group
    ├── Resource Item
    └── Route Item
```

## Advanced Usage

### Programmatic Navigation Management

You can also manage navigation programmatically:

```php
use Ycookies\FilamentNavManager\Models\NavManager;

// Create a navigation item
NavManager::create([
    'title' => 'My Menu',
    'type' => NavManager::TYPE_RESOURCE,
    'target' => \App\Filament\Resources\Users\UserResource::class,
    'panel' => 'admin',
    'parent_id' => 0,
    'order' => 1,
    'show' => true,
    'icon' => 'heroicon-o-users',
]);

// Sync panel resources and pages
$panel = Filament::getPanel('admin');
$count = NavManager::syncPanel($panel);

// Clear navigation cache
NavManager::flushNavigationCache('admin');
```

### Custom Navigation Generation

```php
use Ycookies\FilamentNavManager\Models\NavManager;

// Generate navigation for a specific panel
$navigation = NavManager::navigationForPanel('admin', cacheSeconds: 3600);

// Use in panel configuration
$panel->navigation(
    NavManager::generate()
        ->panel('admin')
        ->cacheTime(3600)
        ->toClosure()
);
```

## Multi-Language Support

The package includes translations for:

- 🇬🇧 English (`en`)
- 🇨🇳 Simplified Chinese (`zh_CN`)
- 🇹🇼 Traditional Chinese (`zh_TW`)

Translations are automatically loaded. Set your application locale:

```php
config(['app.locale' => 'zh_CN']);
```

## Role-Based Access Control

Configure which roles can access the Navigation Manager:

```php
// config/nav-manager.php
return [
    'allowed_roles' => ['admin', 'super_admin'],
];
```

If using Spatie Laravel Permission:

```php
// The package automatically checks if user has any of the allowed roles
'allowed_roles' => ['admin', 'super_admin'],
```

Set to `null` to allow all authenticated users:

```php
'allowed_roles' => null, // All authenticated users can access
```

## Tree View Support

If your application has a `treeView` table macro (commonly used for hierarchical data), the navigation table will automatically use it for a better tree-structured display.

## Clearing Navigation Cache

```php
use Ycookies\FilamentNavManager\NavManagerNavigationGenerator;

// Clear cache for current panel
NavManagerNavigationGenerator::flush();

// Clear cache for specific panel
NavManagerNavigationGenerator::flush('admin');
```

Or via model:

```php
use Ycookies\FilamentNavManager\Models\NavManager;

NavManager::flushNavigationCache('admin');
```

## Database Schema

The package creates a `nav_manager` table with the following structure:

- `id` - Primary key
- `parent_id` - Parent menu item ID (0 for top-level)
- `panel` - Filament panel ID
- `order` - Display order
- `title` - Menu title
- `type` - Menu type (group, resource, page, route, url)
- `icon` - Heroicon name
- `uri` - URI path
- `target` - Resource class, Page class, or route name
- `extension` - Extension identifier
- `show` - Visibility toggle
- `badge` - Badge text
- `badge_color` - Badge color
- `is_collapsed` - Collapsed state
- `permission` - Required permission
- `created_at` / `updated_at` - Timestamps

## Commands

### Install Command

```bash
php artisan filament-nav-manager:install
```

Runs migrations and optionally syncs panels.

### Sync Command

```bash
php artisan filament-nav-manager:sync {panel}
```

Syncs Filament resources and pages for a specific panel.

## Troubleshooting

### Navigation Not Appearing

1. Ensure the plugin is registered in your Panel Provider
2. Check that navigation items have `show = true`
3. Verify user has required roles (if configured)
4. Clear navigation cache: `NavManager::flushNavigationCache()`

### Sync Not Working

1. Verify the panel ID exists
2. Check that resources/pages are properly registered in the panel
3. Review error logs for specific issues

### Permission Issues

1. Check `config/nav-manager.php` for `allowed_roles` configuration
2. Verify user roles are correctly assigned
3. Ensure Spatie Laravel Permission is installed if using roles

## Requirements

- PHP >= 8.2
- Laravel >= 10.0
- Filament >= 4.0

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

- 📧 Email: 3664839@qq.com
- 🐛 Issues: [GitHub Issues](https://github.com/ycookies/filament-nav-manager/issues)
- 📖 Documentation: [GitHub Wiki](https://github.com/ycookies/filament-nav-manager/wiki)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

**Made with ❤️ by [eRic](https://github.com/ycookies)**

