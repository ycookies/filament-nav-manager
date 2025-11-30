# Filament Nav Manager - 使用说明

## 已实现的功能

### 1. ✅ 数据库迁移
- 表名: `nav_manager` (已从 `admin_menus` 改为 `nav_manager`)
- 包含所有必要的字段：parent_id, panel, order, title, type, icon, uri, target, extension, show, badge, badge_color, is_collapsed, permission

### 2. ✅ 模型 (NavManager)
- 位置: `Ycookies\FilamentNavManager\Models\NavManager`
- 包含所有业务逻辑：同步资源、页面、导航组等

### 3. ✅ 导航生成器 (NavManagerNavigationGenerator)
- 位置: `Ycookies\FilamentNavManager\NavManagerNavigationGenerator`
- 支持缓存和排序

### 4. ✅ 安装命令
- `php artisan filament-nav-manager:install` - 完整安装流程
- 自动运行迁移
- 交互式询问同步哪个 panel

### 5. ✅ 同步命令
- `php artisan filament-nav-manager:sync {panel}` - 同步指定 panel 的资源 and 页面

### 6. ✅ 配置文件
- 位置: `config/nav-manager.php`
- 支持配置角色权限 (`allowed_roles`)
- 支持配置缓存时间 (`cache_seconds`)
- 支持配置表名 (`table_name`)

### 7. ✅ 多语言支持
- 英文 (en)
- 简体中文 (zh_CN)
- 繁体中文 (zh_TW)

## 使用方法

### 安装

```bash
php artisan filament-nav-manager:install
```

这将：
1. 发布配置文件
2. 发布迁移文件
3. 运行迁移
4. 询问是否同步 panel

### 在 Panel Provider 中使用

```php
use Ycookies\FilamentNavManager\Models\NavManager;

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        ->navigation(
            NavManager::generate()
                ->panel($panel->getId())
                ->cacheTime(0)
                ->toClosure()
        );
}
```

### 同步 Panel 资源

```bash
# 同步指定 panel
php artisan filament-nav-manager:sync admin

# 同步当前 panel（如果在 Filament 环境中）
php artisan filament-nav-manager:sync
```

### 配置角色权限

编辑 `config/nav-manager.php`:

```php
return [
    'allowed_roles' => ['admin', 'super_admin'], // 允许的角色列表，null 表示所有已认证用户
    'cache_seconds' => 3600, // 缓存时间（秒），0 表示不缓存
    'table_name' => 'nav_manager',
];
```

## 待完成

- [ ] 创建 NavManagerResource 资源文件（用于在 Filament 中管理导航）
- [ ] 添加路由权限检查（基于配置的 `allowed_roles`）

## 注意事项

1. 迁移文件位于 `database/migrations/create_nav_manager_table.php.stub`，需要发布后运行
2. 模型和导航生成器已完全迁移自 `AdminMenu` 和 `AdminMenuNavigationGenerator`
3. 所有语言文件已创建，但资源文件的翻译需要等待 NavManagerResource 创建后添加

