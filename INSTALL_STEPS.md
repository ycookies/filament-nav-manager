# 🚀 本地安装和测试完整步骤

## ✅ 当前状态

1. ✅ **Composer 配置已完成**
   - 路径仓库已添加到 `composer.json`
   - 包已添加到依赖列表
   - 包已成功安装

2. ✅ **安装命令已运行**
   - 迁移文件已发布到 `database/migrations/`
   - 配置文件需要手动复制（见下方）

3. ⚠️ **配置文件需要手动发布**
   ```bash
   cp vendor/ycookies/filament-nav-manager/config/nav-manager.php config/nav-manager.php
   ```

## 📋 接下来的完整步骤

### 步骤 1: 发布配置文件

```bash
cp vendor/ycookies/filament-nav-manager/config/nav-manager.php config/nav-manager.php
```

或者：

```bash
php artisan vendor:publish --tag=nav-manager-config
```

### 步骤 2: 运行迁移

```bash
php artisan migrate
```

或者查看迁移状态：

```bash
php artisan migrate:status
```

### 步骤 3: 在面板提供者中启用插件

编辑 `app/Providers/Filament/AdminPanelProvider.php`：

**添加 use 语句：**
```php
use Ycookies\FilamentNavManager\FilamentNavManagerPlugin;
use Ycookies\FilamentNavManager\Models\NavManager;
```

**在 `panel()` 方法中，找到：**
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
    NavManager::generate()
        ->panel($panel->getId())
        ->cacheTime(config('nav-manager.cache_seconds', 0))
        ->toClosure()
)
```

**同时，在 `plugins()` 数组中添加（如果还没有）：**
```php
->plugins([
    FilamentShieldPlugin::make(),
    FilamentAwinTheme::make(),
    FilamentScaffoldPlugin::make(),
    CustomFieldsPlugin::make(),
    WorkflowManager::make(),
    FilamentNavManagerPlugin::make(), // 添加这一行
    // ...
])
```

### 步骤 4: 清除缓存

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

### 步骤 5: 同步导航（可选）

```bash
php artisan filament-nav-manager:sync admin
```

### 步骤 6: 测试

1. **启动服务器**（如果还没启动）：
   ```bash
   php artisan serve
   ```

2. **访问面板**：
   ```
   http://localhost:8000/admin
   ```

3. **查看导航菜单**：
   - 应该看到"导航管理"或"Navigation Manager"
   - 点击进入导航管理页面

4. **测试功能**：
   - ✅ 点击"同步 Filament 菜单"按钮
   - ✅ 创建新的导航项
   - ✅ 编辑导航项
   - ✅ 删除导航项
   - ✅ 查看导航在侧边栏是否正确显示

## 🔍 验证清单

运行以下命令验证安装：

```bash
# 1. 检查包是否正确安装
composer show ycookies/filament-nav-manager

# 2. 检查配置文件是否存在
ls -la config/nav-manager.php

# 3. 检查迁移文件
ls -la database/migrations/*nav_manager*

# 4. 检查数据库表是否存在
php artisan tinker --execute="echo Schema::hasTable('nav_manager') ? 'Table exists ✅' : 'Table not found ❌';"

# 5. 检查命令是否可用
php artisan list | grep filament-nav-manager

# 6. 检查路由（如果资源已注册）
php artisan route:list | grep nav-manager
```

## 💡 开发模式提示

### 修改代码后

由于使用路径仓库，修改包代码会**立即生效**：

- ✅ **修改 PHP 类**：直接刷新浏览器即可
- ✅ **修改配置**：运行 `php artisan config:clear`
- ✅ **修改翻译**：运行 `php artisan config:clear`
- ⚠️ **添加新类**：运行 `composer dump-autoload`

### 不需要的操作

- ❌ 不需要重新运行 `composer update`
- ❌ 不需要重新运行 `composer install`
- ❌ 不需要重新发布配置文件

## 🚨 常见问题解决

### 问题 1: 找不到 NavManager 类

```bash
composer dump-autoload
php artisan optimize:clear
```

### 问题 2: 配置不生效

```bash
php artisan config:clear
php artisan cache:clear
```

### 问题 3: 导航不显示

检查：
1. 插件是否在面板提供者中注册
2. 导航生成器是否配置正确
3. 用户权限是否正确（`config/nav-manager.php`）
4. 是否清除了缓存

### 问题 4: 迁移失败

```bash
# 查看错误信息
php artisan migrate

# 如果需要回滚
php artisan migrate:rollback

# 重新运行
php artisan migrate
```

## 📚 相关文档

- [README.md](README.md) - 完整文档
- [README.zh_CN.md](README.zh_CN.md) - 中文文档
- [INSTALL_LOCAL.md](INSTALL_LOCAL.md) - 详细安装指南
- [LOCAL_TESTING.md](LOCAL_TESTING.md) - 测试指南

---

**🎉 祝您开发愉快！如有问题，请查看文档或提交 Issue。**

