# 本地安装和测试指南 / Local Installation & Testing Guide

## 📋 快速开始 / Quick Start

### 步骤 1: 配置 Composer 路径仓库

编辑项目根目录的 `composer.json`，添加 `repositories` 配置：

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./vendor/ycookies/filament-nav-manager",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

### 步骤 2: 添加包到依赖

在 `composer.json` 的 `require` 中添加：

```json
{
    "require": {
        "ycookies/filament-nav-manager": "@dev"
    }
}
```

### 步骤 3: 安装/更新包

```bash
composer require ycookies/filament-nav-manager:@dev
```

或如果已存在：

```bash
composer update ycookies/filament-nav-manager
```

### 步骤 4: 运行安装命令

```bash
php artisan filament-nav-manager:install
```

这会：
- ✅ 发布配置文件到 `config/nav-manager.php`
- ✅ 发布迁移文件
- ✅ 询问是否运行迁移
- ✅ 可选同步面板

### 步骤 5: 在面板提供者中启用

编辑 `app/Providers/Filament/AdminPanelProvider.php`：

**替换现有的：**
```php
use App\Models\AdminMenu;
use App\Support\AdminMenuNavigationGenerator;

->navigation(
    AdminMenu::generate()
        ->panel($panel->getId())
        ->cacheTime(0)
        ->toClosure()
)
```

**改为：**
```php
use Ycookies\FilamentNavManager\FilamentNavManagerPlugin;
use Ycookies\FilamentNavManager\Models\NavManager;

->plugin(FilamentNavManagerPlugin::make())
->navigation(
    NavManager::generate()
        ->panel($panel->getId())
        ->cacheTime(0)
        ->toClosure()
)
```

### 步骤 6: 清除缓存

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### 步骤 7: 测试

1. 访问 Filament 面板：`http://your-app.test/admin`
2. 查看导航菜单，应该看到"导航管理"或"Navigation Manager"
3. 点击进入导航管理页面
4. 尝试"同步 Filament 菜单"功能
5. 创建、编辑、删除导航项

---

## 🔧 详细步骤说明

### 1. 配置 Composer

在 `composer.json` 中添加完整配置：

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./vendor/ycookies/filament-nav-manager",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "ycookies/filament-nav-manager": "@dev"
    }
}
```

### 2. 运行 Composer 命令

```bash
# 在项目根目录执行
cd /Users/yangg/Downloads/www/filament/filament4

# 更新依赖
composer update ycookies/filament-nav-manager

# 如果提示找不到包，先添加
composer require ycookies/filament-nav-manager:@dev --no-update
composer update ycookies/filament-nav-manager
```

### 3. 验证安装

```bash
# 查看包信息
composer show ycookies/filament-nav-manager

# 查看包路径（应该是本地路径）
composer info ycookies/filament-nav-manager
```

### 4. 运行安装命令

```bash
php artisan filament-nav-manager:install
```

按照提示：
- 输入 `y` 运行迁移
- 选择要同步的面板

### 5. 配置面板提供者

完整的 `AdminPanelProvider.php` 示例：

```php
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Ycookies\FilamentNavManager\FilamentNavManagerPlugin;
use Ycookies\FilamentNavManager\Models\NavManager;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->plugin(FilamentNavManagerPlugin::make()) // 添加插件
            ->navigation(
                NavManager::generate() // 使用 NavManager
                    ->panel($panel->getId())
                    ->cacheTime(config('nav-manager.cache_seconds', 0))
                    ->toClosure()
            )
            // ... 其他配置
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            // ...
    }
}
```

### 6. 清除所有缓存

```bash
php artisan optimize:clear
```

这会清除：
- 配置缓存
- 路由缓存
- 视图缓存
- 应用缓存

---

## 🧪 测试检查清单

- [ ] 包是否正确安装（`composer show ycookies/filament-nav-manager`）
- [ ] 配置文件已发布（`config/nav-manager.php` 存在）
- [ ] 数据库表已创建（`nav_manager` 表存在）
- [ ] 插件已在面板提供者中注册
- [ ] 导航生成器已配置
- [ ] 导航菜单中显示"导航管理"
- [ ] 可以访问导航管理页面
- [ ] 同步功能正常工作
- [ ] 可以创建导航项
- [ ] 可以编辑导航项
- [ ] 可以删除导航项
- [ ] 导航项在侧边栏正确显示

---

## 🔍 调试命令

如果遇到问题，运行以下命令：

```bash
# 检查包安装
composer show ycookies/filament-nav-manager

# 查看包详细信息
composer info ycookies/filament-nav-manager

# 检查自动加载
composer dump-autoload

# 检查已注册的服务提供者
php artisan package:discover

# 检查配置
php artisan config:show nav-manager

# 查看路由
php artisan route:list | grep nav-manager

# 清除所有缓存
php artisan optimize:clear

# 查看日志
tail -f storage/logs/laravel.log
```

---

## 🚨 常见问题

### 问题 1: 找不到包

**解决方案：**
```bash
# 确保 repositories 配置正确
composer config repositories.filament-nav-manager path ./vendor/ycookies/filament-nav-manager

# 重新安装
composer require ycookies/filament-nav-manager:@dev
```

### 问题 2: 类找不到

**解决方案：**
```bash
composer dump-autoload
php artisan optimize:clear
```

### 问题 3: 导航不显示

**检查：**
1. 插件是否在面板提供者中注册
2. 导航生成器是否正确配置
3. 用户是否有权限（检查 `config/nav-manager.php` 中的 `allowed_roles`）
4. 运行 `php artisan optimize:clear`

### 问题 4: 迁移失败

**解决方案：**
```bash
# 检查表是否已存在
php artisan migrate:status

# 手动运行迁移
php artisan migrate --path=vendor/ycookies/filament-nav-manager/database/migrations
```

---

## 💡 开发提示

### 修改代码后

由于使用路径仓库，修改包代码后：
- ✅ 无需重新运行 `composer update`
- ✅ 直接刷新页面即可看到更改
- ⚠️ 如果是新类，需要运行 `composer dump-autoload`

### 修改配置后

```bash
php artisan config:clear
```

### 修改翻译后

```bash
php artisan config:clear
```

### 修改视图后

```bash
php artisan view:clear
```

---

## 📦 从旧系统迁移

如果您之前使用的是 `AdminMenu`，迁移步骤：

1. **备份数据**（如果 `admin_menus` 表有重要数据）
2. **运行新包的迁移**创建 `nav_manager` 表
3. **迁移数据**（如需要，可以编写数据迁移脚本）
4. **更新代码**替换 `AdminMenu` 为 `NavManager`
5. **测试**确保功能正常

---

## 🎯 下一步

安装成功后，您可以：

1. 访问导航管理页面
2. 同步 Filament 资源和页面
3. 创建自定义导航项
4. 配置权限和角色
5. 自定义导航组和图标

更多信息请查看 [README.md](README.md) 或 [README.zh_CN.md](README.zh_CN.md)

