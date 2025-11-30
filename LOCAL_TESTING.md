# 本地测试指南 / Local Testing Guide

## ✅ 已完成

1. ✅ Composer 路径仓库已配置在 `composer.json`
2. ✅ 包已添加到依赖并安装
3. ✅ 自动加载文件已生成

## 📋 接下来的步骤

### 步骤 1: 运行安装命令

```bash
php artisan filament-nav-manager:install
```

这将：
- 发布配置文件到 `config/nav-manager.php`
- 发布迁移文件到 `database/migrations/`
- 询问是否运行迁移（选择 `y`）
- 可选同步面板

### 步骤 2: 在面板提供者中启用插件

编辑 `app/Providers/Filament/AdminPanelProvider.php`：

**找到这行：**
```php
->navigation(
    AdminMenu::generate()
        ->panel($panel->getId())
        ->cacheTime(0)
        ->toClosure()
)
```

**替换为：**
```php
->plugin(FilamentNavManagerPlugin::make())
->navigation(
    \Ycookies\FilamentNavManager\Models\NavManager::generate()
        ->panel($panel->getId())
        ->cacheTime(0)
        ->toClosure()
)
```

**同时更新 use 语句：**
```php
use Ycookies\FilamentNavManager\FilamentNavManagerPlugin;
use Ycookies\FilamentNavManager\Models\NavManager;
```

### 步骤 3: 清除缓存

```bash
php artisan optimize:clear
```

或者分别清除：

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 步骤 4: 测试

1. **访问面板**：`http://your-app.test/admin`
2. **查看导航**：应该看到"导航管理"或"Navigation Manager"
3. **点击进入**：导航管理页面
4. **测试同步**：点击"同步 Filament 菜单"按钮
5. **测试 CRUD**：创建、编辑、删除导航项

## 🔍 验证安装

运行以下命令检查：

```bash
# 检查包是否正确安装（应该显示本地路径）
composer show ycookies/filament-nav-manager

# 检查配置文件是否存在
cat config/nav-manager.php

# 检查迁移文件
ls -la database/migrations/*nav_manager*

# 检查数据库表是否存在
php artisan tinker --execute="echo Schema::hasTable('nav_manager') ? 'Table exists' : 'Table not found';"
```

## 💡 开发提示

### 修改代码后

由于使用路径仓库（path repository），代码修改会立即生效：

- ✅ **修改 PHP 代码**：直接刷新页面即可看到更改
- ✅ **修改配置**：运行 `php artisan config:clear`
- ✅ **修改翻译**：运行 `php artisan config:clear`
- ⚠️ **添加新类**：需要运行 `composer dump-autoload`

### 调试命令

```bash
# 检查包的详细信息
composer info ycookies/filament-nav-manager

# 查看包的实际路径（应该指向本地路径）
composer show ycookies/filament-nav-manager | grep path

# 重新生成自动加载
composer dump-autoload

# 清除所有缓存
php artisan optimize:clear

# 查看已注册的服务提供者
php artisan package:discover

# 查看日志
tail -f storage/logs/laravel.log
```

## 🚨 常见问题

### 问题 1: 找不到 NavManager 类

**解决方案：**
```bash
composer dump-autoload
php artisan optimize:clear
```

### 问题 2: 导航菜单不显示

**检查清单：**
1. ✅ 插件是否在面板提供者中注册？
2. ✅ 导航生成器是否配置正确？
3. ✅ 用户是否有权限？（检查 `config/nav-manager.php`）
4. ✅ 是否清除了缓存？

### 问题 3: 迁移失败

**解决方案：**
```bash
# 查看迁移状态
php artisan migrate:status

# 手动运行迁移
php artisan migrate

# 如果需要回滚
php artisan migrate:rollback
```

### 问题 4: 同步功能报错

**检查：**
1. 面板 ID 是否正确
2. 资源和页面是否正确注册到面板
3. 查看错误日志：`storage/logs/laravel.log`

## 📝 快速命令参考

```bash
# 安装
php artisan filament-nav-manager:install

# 同步面板
php artisan filament-nav-manager:sync admin

# 清除缓存
php artisan optimize:clear

# 检查安装
composer show ycookies/filament-nav-manager
```

---

**提示**：修改包代码后无需重新运行 `composer update`，直接刷新页面即可看到更改！

