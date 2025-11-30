# Filament Nav Manager

一个强大的 Filament v4 导航管理扩展包，允许您通过友好的用户界面动态管理 Filament 面板的导航菜单。

> 📖 [简体中文文档](README.zh_CN.md) | [English Documentation](README.md)

[![最新版本](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/ycookies/filament-nav-manager)
[![许可证](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE.md)
[![PHP 版本](https://img.shields.io/badge/php-%3E%3D8.2-blue.svg)](https://php.net)
[![Filament 版本](https://img.shields.io/badge/filament-%5E4.0-orange.svg)](https://filamentphp.com)

## 功能特性

✨ **丰富的功能集**
- 🎯 通过 Filament UI 动态管理导航
- 🔄 自动同步 Filament 资源和页面
- 🌳 支持树形结构的菜单层级
- 🎨 可自定义的导航组、图标和徽标
- 🔐 基于角色的访问控制
- 🌍 多语言支持（英语、简体中文、繁体中文）
- ⚡ 导航缓存提升性能
- 📦 简单易用的安装和设置

## 安装

您可以通过 Composer 安装此扩展包：

```bash
composer require ycookies/filament-nav-manager
```

### 发布并运行迁移

```bash
php artisan filament-nav-manager:install
```

此命令将：
1. 发布配置文件
2. 发布迁移文件
3. 询问是否运行迁移
4. 允许您同步面板

或者手动执行：

```bash
php artisan vendor:publish --tag="filament-nav-manager-migrations"
php artisan migrate
php artisan vendor:publish --tag="filament-nav-manager-config"
```

## 配置

### 启用插件

在您的 Filament 面板提供者中添加插件：

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

### 配置权限

编辑 `config/nav-manager.php`：

```php
return [
    // 允许特定角色访问导航管理器
    'allowed_roles' => ['admin', 'super_admin'], // 或 null 表示所有已认证用户
    
    // 缓存导航以提升性能（单位：秒）
    'cache_seconds' => 3600, // 0 表示禁用缓存
    
    // 数据库表名
    'table_name' => 'nav_manager',
    
    // 导航管理器资源在侧边栏中的导航组
    'navigation_group' => null, // null 表示使用翻译，或自定义名称如 '系统管理'、'设置'
];
```

### 自定义导航组

您可以自定义导航管理器资源显示在哪个导航组中：

```php
// config/nav-manager.php
return [
    'navigation_group' => '系统设置', // 自定义组名
    // 或
    'navigation_group' => null, // 使用默认翻译
];
```

## 使用方法

### 同步 Filament 资源和页面

#### 方式一：通过 UI 界面

1. 在 Filament 面板中导航到"导航管理"
2. 点击"同步 Filament 菜单"按钮
3. 确认同步操作

#### 方式二：通过命令行

同步指定面板：

```bash
php artisan filament-nav-manager:sync admin
```

或在安装过程中同步所有面板：

```bash
php artisan filament-nav-manager:install
```

### 管理导航项

安装后，您将在 Filament 导航中看到"导航管理"。您可以：

- ✅ 创建新的导航项
- ✅ 编辑现有项
- ✅ 删除项
- ✅ 重新排序项（如果启用了树形视图，支持拖放）
- ✅ 切换显示/隐藏
- ✅ 管理图标和徽标
- ✅ 设置权限

### 导航项类型

扩展包支持以下导航项类型：

1. **分组** - 可以包含子项的导航组
2. **资源** - 链接到 Filament 资源
3. **页面** - 链接到 Filament 页面
4. **路由** - 链接到 Laravel 路由
5. **URL** - 链接到任何 URL（内部或外部）

## 导航结构

导航项以分层树形结构组织：

```
导航组
├── 资源项
├── 页面项
└── 导航组
    ├── 资源项
    └── 路由项
```

## 高级用法

### 编程方式管理导航

您也可以通过编程方式管理导航：

```php
use Ycookies\FilamentNavManager\Models\NavManager;

// 创建导航项
NavManager::create([
    'title' => '我的菜单',
    'type' => NavManager::TYPE_RESOURCE,
    'target' => \App\Filament\Resources\Users\UserResource::class,
    'panel' => 'admin',
    'parent_id' => 0,
    'order' => 1,
    'show' => true,
    'icon' => 'heroicon-o-users',
]);

// 同步面板资源和页面
$panel = Filament::getPanel('admin');
$count = NavManager::syncPanel($panel);

// 清除导航缓存
NavManager::flushNavigationCache('admin');
```

### 自定义导航生成

```php
use Ycookies\FilamentNavManager\Models\NavManager;

// 为指定面板生成导航
$navigation = NavManager::navigationForPanel('admin', cacheSeconds: 3600);

// 在面板配置中使用
$panel->navigation(
    NavManager::generate()
        ->panel('admin')
        ->cacheTime(3600)
        ->toClosure()
);
```

## 多语言支持

扩展包包含以下语言的翻译：

- 🇬🇧 英语 (`en`)
- 🇨🇳 简体中文 (`zh_CN`)
- 🇹🇼 繁体中文 (`zh_TW`)

翻译会自动加载。设置应用程序语言环境：

```php
config(['app.locale' => 'zh_CN']);
```

## 基于角色的访问控制

配置哪些角色可以访问导航管理器：

```php
// config/nav-manager.php
return [
    'allowed_roles' => ['admin', 'super_admin'],
];
```

如果使用 Spatie Laravel Permission：

```php
// 扩展包会自动检查用户是否拥有允许的角色之一
'allowed_roles' => ['admin', 'super_admin'],
```

设置为 `null` 允许所有已认证用户：

```php
'allowed_roles' => null, // 所有已认证用户都可以访问
```

## 树形视图支持

如果您的应用程序有 `treeView` 表格宏（通常用于分层数据），导航表格会自动使用它以获得更好的树形结构显示。

## 清除导航缓存

```php
use Ycookies\FilamentNavManager\NavManagerNavigationGenerator;

// 清除当前面板的缓存
NavManagerNavigationGenerator::flush();

// 清除指定面板的缓存
NavManagerNavigationGenerator::flush('admin');
```

或通过模型：

```php
use Ycookies\FilamentNavManager\Models\NavManager;

NavManager::flushNavigationCache('admin');
```

## 数据库结构

扩展包创建 `nav_manager` 表，包含以下结构：

- `id` - 主键
- `parent_id` - 父菜单项 ID（0 表示顶级）
- `panel` - Filament 面板 ID
- `order` - 显示顺序
- `title` - 菜单标题
- `type` - 菜单类型（group, resource, page, route, url）
- `icon` - Heroicon 名称
- `uri` - URI 路径
- `target` - 资源类、页面类或路由名称
- `extension` - 扩展标识符
- `show` - 显示/隐藏开关
- `badge` - 徽标文本
- `badge_color` - 徽标颜色
- `is_collapsed` - 折叠状态
- `permission` - 所需权限
- `created_at` / `updated_at` - 时间戳

## 命令

### 安装命令

```bash
php artisan filament-nav-manager:install
```

运行迁移并可选择同步面板。

### 同步命令

```bash
php artisan filament-nav-manager:sync {panel}
```

同步指定面板的 Filament 资源和页面。

## 故障排除

### 导航未显示

1. 确保插件已在面板提供者中注册
2. 检查导航项是否设置了 `show = true`
3. 验证用户是否具有所需角色（如果已配置）
4. 清除导航缓存：`NavManager::flushNavigationCache()`

### 同步不工作

1. 验证面板 ID 是否存在
2. 检查资源和页面是否正确注册到面板中
3. 查看错误日志了解具体问题

### 权限问题

1. 检查 `config/nav-manager.php` 中的 `allowed_roles` 配置
2. 验证用户角色是否正确分配
3. 如果使用角色，请确保已安装 Spatie Laravel Permission

## 系统要求

- PHP >= 8.2
- Laravel >= 10.0
- Filament >= 4.0

## 贡献

欢迎贡献！请随时提交 Pull Request。

## 许可证

MIT 许可证。更多信息请查看[许可证文件](LICENSE.md)。

## 支持

- 📧 邮箱：3664839@qq.com
- 🐛 问题反馈：[GitHub Issues](https://github.com/ycookies/filament-nav-manager/issues)
- 📖 文档：[GitHub Wiki](https://github.com/ycookies/filament-nav-manager/wiki)

## 更新日志

更多变更信息请查看 [CHANGELOG](CHANGELOG.md)。

---

**由 [eRic](https://github.com/ycookies) 用 ❤️ 制作**

