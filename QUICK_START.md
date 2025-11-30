# 快速开始 / Quick Start

## 🚀 本地安装测试（5 步完成）

### 步骤 1: 配置 Composer 路径仓库

已在 `composer.json` 中添加路径仓库配置，包已安装完成 ✅

### 步骤 2: 运行安装命令

```bash
php artisan filament-nav-manager:install
```

按提示操作：
- 输入 `y` 运行迁移
- 选择要同步的面板（可选）

### 步骤 3: 在面板提供者中启用插件

编辑 `app/Providers/Filament/AdminPanelProvider.php`：

```php
use Ycookies\FilamentNavManager\FilamentNavManagerPlugin;
use Ycookies\FilamentNavManager\Models\NavManager;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(FilamentNavManagerPlugin::make()) // 添加这一行
        ->navigation(
            NavManager::generate() // 替换 AdminMenu 为 NavManager
                ->panel($panel->getId())
                ->cacheTime(0)
                ->toClosure()
        )
        // ... 其他配置
}
```

### 步骤 4: 清除缓存

```bash
php artisan optimize:clear
```

### 步骤 5: 访问测试

1. 访问：`http://your-app.test/admin`
2. 查看导航菜单中是否有"导航管理"
3. 点击进入测试功能

---

## 📝 完整命令清单

```bash
# 1. 确保包已安装（如果还没添加）
composer require ycookies/filament-nav-manager:@dev

# 2. 运行安装命令
php artisan filament-nav-manager:install

# 3. 清除缓存
php artisan optimize:clear

# 4. 测试同步功能（可选）
php artisan filament-nav-manager:sync admin
```

---

## ✅ 验证安装

```bash
# 检查包是否正确安装
composer show ycookies/filament-nav-manager

# 检查命令是否可用
php artisan list | grep filament-nav-manager

# 检查配置是否发布
ls -la config/nav-manager.php

# 检查数据库表是否创建
php artisan migrate:status | grep nav_manager
```

---

## 🎯 测试清单

- [ ] 包已安装（`composer show` 显示本地路径）
- [ ] 配置文件已发布
- [ ] 数据库表已创建
- [ ] 插件已注册
- [ ] 导航菜单显示"导航管理"
- [ ] 可以访问导航管理页面
- [ ] 同步功能正常工作
- [ ] 可以创建/编辑/删除导航项

---

## 🔧 开发提示

**修改代码后：**
- ✅ 无需重新安装，直接刷新页面即可
- ⚠️ 如果是新类，运行 `composer dump-autoload`

**常见问题：**
- 类找不到 → `composer dump-autoload`
- 配置不生效 → `php artisan config:clear`
- 页面不更新 → `php artisan optimize:clear`

