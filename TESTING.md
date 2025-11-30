# 本地测试指南 / Local Testing Guide

## 简体中文

### 方式一：使用 Composer 路径仓库（推荐）

这是测试本地扩展包的最佳方式，修改代码后无需重新安装。

#### 1. 在主项目的 `composer.json` 中添加路径仓库

编辑项目根目录的 `composer.json`：

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./vendor/ycookies/filament-nav-manager"
        }
    ],
    "require": {
        "ycookies/filament-nav-manager": "*"
    }
}
```

#### 2. 安装/更新包

```bash
cd /Users/yangg/Downloads/www/filament/filament4
composer require ycookies/filament-nav-manager:@dev --no-update
composer update ycookies/filament-nav-manager
```

或者如果已经在 `require` 中：

```bash
composer update ycookies/filament-nav-manager
```

#### 3. 运行安装命令

```bash
php artisan filament-nav-manager:install
```

这将：
- 发布配置文件
- 发布迁移文件
- 询问是否运行迁移
- 可选同步面板

#### 4. 在面板提供者中启用插件

编辑 `app/Providers/Filament/AdminPanelProvider.php` 或相应的面板提供者：

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
                ->cacheTime(0)
                ->toClosure()
        );
}
```

#### 5. 清除缓存

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

#### 6. 测试

1. 访问 Filament 面板
2. 查看导航中是否有"导航管理"
3. 点击进入导航管理页面
4. 尝试同步 Filament 菜单
5. 创建、编辑、删除导航项

### 方式二：符号链接（Symlink）

如果包在其他目录，可以使用符号链接：

```bash
# 创建符号链接
ln -s /path/to/filament-nav-manager /Users/yangg/Downloads/www/filament/filament4/vendor/ycookies/filament-nav-manager

# 然后在 composer.json 中添加路径仓库
```

### 开发提示

1. **修改代码后**：无需重新运行 `composer update`，直接刷新页面即可看到更改
2. **修改配置后**：运行 `php artisan config:clear`
3. **修改翻译后**：运行 `php artisan config:clear`
4. **添加新类后**：运行 `composer dump-autoload`

### 调试

如果遇到问题：

```bash
# 查看已安装的包
composer show ycookies/filament-nav-manager

# 查看包的详细信息
composer info ycookies/filament-nav-manager

# 重新生成自动加载文件
composer dump-autoload

# 清除所有缓存
php artisan optimize:clear
```

---

## English

### Method 1: Using Composer Path Repository (Recommended)

This is the best way to test a local package - changes are reflected immediately without reinstalling.

#### 1. Add Path Repository to Main Project's `composer.json`

Edit the root `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./vendor/ycookies/filament-nav-manager"
        }
    ],
    "require": {
        "ycookies/filament-nav-manager": "*"
    }
}
```

#### 2. Install/Update Package

```bash
cd /Users/yangg/Downloads/www/filament/filament4
composer require ycookies/filament-nav-manager:@dev --no-update
composer update ycookies/filament-nav-manager
```

Or if already in `require`:

```bash
composer update ycookies/filament-nav-manager
```

#### 3. Run Installation Command

```bash
php artisan filament-nav-manager:install
```

This will:
- Publish config file
- Publish migration files
- Ask to run migrations
- Optionally sync panels

#### 4. Enable Plugin in Panel Provider

Edit `app/Providers/Filament/AdminPanelProvider.php` or your panel provider:

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
                ->cacheTime(0)
                ->toClosure()
        );
}
```

#### 5. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

#### 6. Test

1. Access your Filament panel
2. Check if "Navigation Manager" appears in navigation
3. Click to access navigation manager
4. Try syncing Filament menus
5. Create, edit, delete navigation items

### Development Tips

1. **After code changes**: No need to run `composer update`, just refresh the page
2. **After config changes**: Run `php artisan config:clear`
3. **After translation changes**: Run `php artisan config:clear`
4. **After adding new classes**: Run `composer dump-autoload`

### Troubleshooting

If you encounter issues:

```bash
# Check installed package
composer show ycookies/filament-nav-manager

# View package details
composer info ycookies/filament-nav-manager

# Regenerate autoload files
composer dump-autoload

# Clear all caches
php artisan optimize:clear
```

