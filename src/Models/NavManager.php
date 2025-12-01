<?php

namespace Ycookies\FilamentNavManager\Models;

use BladeUI\Icons\Factory as IconFactory;
use Closure;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page as FilamentPage;
use Filament\Resources\Resource as FilamentResource;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use function Filament\Support\original_request;

class NavManager extends Model
{
    public const TYPE_GROUP    = 'group';
    public const TYPE_RESOURCE = 'resource';
    public const TYPE_PAGE     = 'page';
    public const TYPE_ROUTE    = 'route';
    public const TYPE_URL      = 'url';

    protected $table = 'nav_manager';

    protected $fillable = [
        'parent_id',
        'panel',
        'order',
        'title',
        'type',
        'icon',
        'uri',
        'target',
        'extension',
        'show',
        'badge',
        'badge_color',
        'is_collapsed',
        'permission',
    ];

    protected $casts = [
        'parent_id'    => 'integer',
        'order'        => 'integer',
        'show'         => 'boolean',
        'is_collapsed' => 'boolean',
    ];

    /**
     * Get the parent menu.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavManager::class, 'parent_id');
    }

    /**
     * Get child menus.
     */
    public function children(): HasMany
    {
        return $this->hasMany(NavManager::class, 'parent_id')->ordered()->visible();
    }

    /**
     * Get recursive children.
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    /**
     * Get menu depth.
     */
    public function depth(): int
    {
        $depth  = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    /**
     * Scope: ordered.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    /**
     * Scope: visible.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('show', true);
    }

    /**
     * Scope: for panel.
     */
    public function scopeForPanel(Builder $query, string $panel): Builder
    {
        return $query->where(function (Builder $query) use ($panel): void {
            $query
                ->whereNull('panel')
                ->orWhere('panel', $panel);
        });
    }

    /**
     * Get panel options.
     */
    public static function panelOptions(): array
    {
        return collect(Filament::getPanels())
            ->mapWithKeys(function (Panel $panel): array {
                $label = method_exists($panel, 'getBrandName') ? $panel->getBrandName() : null;

                if ($label instanceof Htmlable) {
                    $label = strip_tags($label->toHtml());
                }

                $label = $label ?: Str::headline($panel->getId());

                return [$panel->getId() => $label];
            })
            ->all();
    }

    /**
     * Get parent select options.
     */
    public static function parentSelectOptions(?string $panel = null, ?int $exceptId = null): array
    {
        $panelId = $panel ?? Filament::getCurrentPanel()?->getId();

        $menus = static::query()
            ->when($panelId, fn (Builder $query) => $query->forPanel($panelId))
            ->orderBy('order')
            ->orderBy('title')
            ->get(['id', 'parent_id', 'title']);

        $grouped = $menus->groupBy(fn (self $menu) => $menu->parent_id ?? 0);

        $options = [0 => __('nav-manager::nav-manager.form.parent_menu') ?: '顶级'];

        $traverse = function (int $parentId, string $prefix = '') use (&$traverse, &$options, $grouped, $exceptId): void {
            foreach ($grouped->get($parentId, collect()) as $menu) {
                if ($menu->id === $exceptId) {
                    continue;
                }

                $options[$menu->id] = $prefix . $menu->title;

                $traverse($menu->id, $prefix . '-- ');
            }
        };

        $traverse(0);

        return $options;
    }

    /**
     * Get resource options for a panel.
     */
    public static function resourceOptions(?string $panelId = null): array
    {
        // 如果没有提供 panelId，尝试获取当前面板
        if (!$panelId) {
            $panel = Filament::getCurrentPanel();
        } else {
            $panel = Filament::getPanel($panelId, isStrict: false);
        }

        // 如果仍然没有面板，尝试获取默认面板
        if (!$panel) {
            $panel = Filament::getDefaultPanel();
        }

        if (!$panel) {
            return [];
        }

        try {
            $resources = $panel->getResources();

            if (empty($resources)) {
                return [];
            }

            return collect($resources)
                ->filter(fn (string $class) => class_exists($class) && is_subclass_of($class, FilamentResource::class))
                ->mapWithKeys(function (string $class) use ($panel): array {
                    try {
                        $label = $class::getPluralModelLabel($panel);
                        return [$class => $label];
                    } catch (\Throwable $e) {
                        // 如果获取标签失败，使用类名
                        return [$class => class_basename($class)];
                    }
                })
                ->all();
        } catch (\Throwable $e) {
            // 如果获取资源失败，返回空数组
            return [];
        }
    }

    /**
     * Get page options for a panel.
     */
    public static function pageOptions(?string $panelId = null): array
    {
        // 如果没有提供 panelId，尝试获取当前面板
        if (!$panelId) {
            $panel = Filament::getCurrentPanel();
        } else {
            $panel = Filament::getPanel($panelId, isStrict: false);
        }

        // 如果仍然没有面板，尝试获取默认面板
        if (!$panel) {
            $panel = Filament::getDefaultPanel();
        }

        if (!$panel) {
            return [];
        }

        try {
            $pages = $panel->getPages();

            if (empty($pages)) {
                return [];
            }

            return collect($pages)
                ->filter(fn (string $class) => class_exists($class) && is_subclass_of($class, FilamentPage::class))
                ->mapWithKeys(function (string $class) use ($panel): array {
                    try {
                        $label = method_exists($class, 'getNavigationLabel') ? $class::getNavigationLabel() : null;
                        $label ??= class_basename($class);
                        return [$class => $label];
                    } catch (\Throwable $e) {
                        // 如果获取标签失败，使用类名
                        return [$class => class_basename($class)];
                    }
                })
                ->all();
        } catch (\Throwable $e) {
            // 如果获取页面失败，返回空数组
            return [];
        }
    }

    /**
     * Sync panel resources and pages.
     */
    public static function syncPanel(\Filament\Panel $panel): int
    {
        $syncedCount = 0;
        $groupMap    = [];
        $order       = 1;

        $panelId   = $panel->getId();
        $panelPath = $panel->getPath();

        try {
            // Get all resources and pages, including those with isDiscovered = false
            // We need to manually scan the discovery directories because Filament skips
            // resources/pages with isDiscovered = false during discovery
            
            $resources = [];
            $pages     = [];
            
            // Get manually registered resources/pages (from ->resources() and ->pages() methods)
            $registeredResources = $panel->getResources();
            $registeredPages     = $panel->getPages();
            
            // Get discovery directories and namespaces from Panel
            $resourceDirectories = $panel->getResourceDirectories();
            $resourceNamespaces  = $panel->getResourceNamespaces();
            $pageDirectories     = $panel->getPageDirectories();
            $pageNamespaces      = $panel->getPageNamespaces();
            
            // Scan discovery directories to find all resources/pages (including isDiscovered = false)
            $filesystem = app(\Illuminate\Filesystem\Filesystem::class);
            
            // Discover all resources from directories
            foreach ($resourceDirectories as $index => $directory) {
                $namespace = $resourceNamespaces[$index] ?? null;
                if (!$namespace || !$filesystem->exists($directory)) {
                    continue;
                }
                
                $discoveredResources = static::discoverClassesInDirectory(
                    $filesystem,
                    $directory,
                    $namespace,
                    FilamentResource::class
                );
                $resources = array_merge($resources, $discoveredResources);
            }
            
            // Discover all pages from directories
            foreach ($pageDirectories as $index => $directory) {
                $namespace = $pageNamespaces[$index] ?? null;
                if (!$namespace || !$filesystem->exists($directory)) {
                    continue;
                }
                
                $discoveredPages = static::discoverClassesInDirectory(
                    $filesystem,
                    $directory,
                    $namespace,
                    FilamentPage::class
                );
                $pages = array_merge($pages, $discoveredPages);
            }
            
            // Merge with manually registered resources/pages
            $resources = array_unique(array_merge($resources, $registeredResources));
            $pages     = array_unique(array_merge($pages, $registeredPages));
        } catch (\Throwable $e) {
            // Fallback to getResources() and getPages() if discovery fails
            try {
                $resources = $panel->getResources();
                $pages     = $panel->getPages();
            } catch (\Throwable $e2) {
                throw new \RuntimeException('Failed to get panel resources and pages: ' . $e->getMessage());
            }
        }

        // Collect navigation groups
        $groupOrderMap = [];
        $orderIndex    = 1;

        foreach ($resources as $resource) {
            if (is_string($resource) && class_exists($resource) && is_subclass_of($resource, FilamentResource::class)) {
                $group = method_exists($resource, 'getNavigationGroup') ? $resource::getNavigationGroup() : null;
                if ($group && !isset($groupOrderMap[$group])) {
                    $groupOrderMap[$group] = $orderIndex++;
                }
            }
        }

        foreach ($pages as $page) {
            if (is_string($page) && class_exists($page) && is_subclass_of($page, FilamentPage::class)) {
                $group = method_exists($page, 'getNavigationGroup') ? $page::getNavigationGroup() : null;
                if ($group && !isset($groupOrderMap[$group])) {
                    $groupOrderMap[$group] = $orderIndex++;
                }
            }
        }

        // Set panel context for icon retrieval
        $originalPanel = Filament::getCurrentPanel();
        try {
            Filament::setCurrentPanel($panel);
        } catch (\Throwable $e) {
            // Silently fail if we can't set panel context
        }

        // Create navigation groups
        $sortedGroups = collect($groupOrderMap)
            ->sortBy(fn($order) => $order)
            ->keys()
            ->filter()
            ->values();

        foreach ($sortedGroups as $groupTitle) {
            $groupOrder = $groupOrderMap[$groupTitle];
            
            // 使用 title + type + panel 查找现有导航组（增量更新）
            $existingGroup = static::where('title', $groupTitle)
                ->where('parent_id', 0)
                ->where('type', static::TYPE_GROUP)
                ->where('panel', $panelId)
                ->first();

            if ($existingGroup) {
                // 导航组已存在，保留原有排序，只更新其他字段
                $existingGroup->update([
                    'icon'      => null,
                    'uri'       => '#',
                    'extension' => 'filament',
                    'show'      => 1,
                    // 不更新 order，保留用户设置的排序
                ]);
                $groupMenu = $existingGroup;
            } else {
                // 导航组不存在，创建新记录
                $groupMenu = static::create([
                    'title'     => $groupTitle,
                    'parent_id' => 0,
                    'order'     => $groupOrder,
                    'icon'      => null,
                    'uri'       => '#',
                    'extension' => 'filament',
                    'show'      => 1,
                    'type'      => static::TYPE_GROUP,
                    'panel'     => $panelId,
                ]);
            }
            
            $groupMap[$groupTitle] = $groupMenu->id;
        }

        $order = max($groupOrderMap ?: [0]) + 1;

        // Sync resources
        foreach ($resources as $resource) {
            if (is_string($resource) && class_exists($resource) && is_subclass_of($resource, FilamentResource::class)) {
                if (static::syncResource($resource, $groupMap, $order++, $panelId, $panelPath)) {
                    $syncedCount++;
                }
            }
        }

        // Sync pages
        foreach ($pages as $page) {
            if (is_string($page) && class_exists($page) && is_subclass_of($page, FilamentPage::class)) {
                if (static::syncPage($page, $groupMap, $order++, $panelId, $panelPath, $panel)) {
                    $syncedCount++;
                }
            }
        }

        static::reorderTopLevelMenus($panelId);

        return $syncedCount;
    }

    protected static function syncResource(string $resource, array $groupMap, int $order, ?string $panelId = null, string $panelPath = 'admin'): bool
    {
        $label = method_exists($resource, 'getNavigationLabel')
            ? $resource::getNavigationLabel()
            : (method_exists($resource, 'getModelLabel')
                ? $resource::getModelLabel()
                : class_basename($resource));
        $icon  = static::getResourceIcon($resource);
        $group = method_exists($resource, 'getNavigationGroup') ? $resource::getNavigationGroup() : null;
        $sort  = method_exists($resource, 'getNavigationSort') ? ($resource::getNavigationSort() ?? $order) : $order;
        $slug  = method_exists($resource, 'getSlug') ? $resource::getSlug() : null;

        $parentId = 0;
        if ($group && isset($groupMap[$group])) {
            $parentId = $groupMap[$group];
        }

        $uri = $slug ? "{$panelPath}/{$slug}" : '#';

        // 如果 isDiscovered = false，跳过同步，不添加到数据库
        $isDiscovered = method_exists($resource, 'isDiscovered') ? $resource::isDiscovered() : true;
        if (!$isDiscovered) {
            return false;
        }

        // 检查 shouldRegisterNavigation，决定 show 的值
        $shouldRegisterNavigation = method_exists($resource, 'shouldRegisterNavigation') 
            ? $resource::shouldRegisterNavigation() 
            : true;
        $show = $shouldRegisterNavigation ? 1 : 0;

        // 使用 target 字段查找现有记录（更准确），实现增量更新
        $existing = static::where('target', $resource)
            ->where('type', static::TYPE_RESOURCE)
            ->where('panel', $panelId)
            ->first();

        if ($existing) {
            // 记录已存在，保留原有排序，只更新其他字段
            $existing->update([
                'title'     => $label,
                'parent_id' => $parentId,
                'icon'      => $icon,
                'uri'       => $uri,
                'extension' => 'filament',
                'show'      => $show,
                // 不更新 order，保留用户设置的排序
            ]);
        } else {
            // 记录不存在，创建新记录
            static::create([
                'title'     => $label,
                'parent_id' => $parentId,
                'order'     => $sort,
                'icon'      => $icon,
                'uri'       => $uri,
                'target'    => $resource,
                'type'      => static::TYPE_RESOURCE,
                'extension' => 'filament',
                'show'      => $show,
                'panel'     => $panelId,
            ]);
        }
        
        return true;
    }

    protected static function syncPage(string $page, array $groupMap, int $order, ?string $panelId = null, string $panelPath = 'admin', ?\Filament\Panel $panel = null): bool
    {
        $label = method_exists($page, 'getNavigationLabel')
            ? $page::getNavigationLabel()
            : (method_exists($page, 'getTitle')
                ? $page::getTitle()
                : class_basename($page));
        $icon  = static::getPageIcon($page, $panel);
        $group = method_exists($page, 'getNavigationGroup') ? $page::getNavigationGroup() : null;
        $sort  = method_exists($page, 'getNavigationSort') ? ($page::getNavigationSort() ?? $order) : $order;
        $slug  = method_exists($page, 'getSlug') ? $page::getSlug() : null;

        $parentId = 0;
        if ($group && isset($groupMap[$group])) {
            $parentId = $groupMap[$group];
        }

        $uri = $slug ? "{$panelPath}/{$slug}" : '#';

        // 如果 isDiscovered = false，跳过同步，不添加到数据库
        $isDiscovered = method_exists($page, 'isDiscovered') ? $page::isDiscovered() : true;
        if (!$isDiscovered) {
            return false;
        }

        // 检查 shouldRegisterNavigation，决定 show 的值
        $shouldRegisterNavigation = true; // 默认值
        if (method_exists($page, 'shouldRegisterNavigation')) {
            try {
                // 尝试无参数调用（普通 Page）
                $shouldRegisterNavigation = $page::shouldRegisterNavigation();
            } catch (\Throwable $e) {
                // 如果失败，尝试带空数组参数（Resource Page）
                try {
                    $shouldRegisterNavigation = $page::shouldRegisterNavigation([]);
                } catch (\Throwable $e2) {
                    // 如果都失败，默认为 true
                    $shouldRegisterNavigation = true;
                }
            }
        }
        $show = $shouldRegisterNavigation ? 1 : 0;

        // 使用 target 字段查找现有记录（更准确），实现增量更新
        $existing = static::where('target', $page)
            ->where('type', static::TYPE_PAGE)
            ->where('panel', $panelId)
            ->first();

        if ($existing) {
            // 记录已存在，保留原有排序，只更新其他字段
            $existing->update([
                'title'     => $label,
                'parent_id' => $parentId,
                'icon'      => $icon,
                'uri'       => $uri,
                'extension' => 'filament',
                'show'      => $show,
                // 不更新 order，保留用户设置的排序
            ]);
        } else {
            // 记录不存在，创建新记录
            static::create([
                'title'     => $label,
                'parent_id' => $parentId,
                'order'     => $sort,
                'icon'      => $icon,
                'uri'       => $uri,
                'target'    => $page,
                'type'      => static::TYPE_PAGE,
                'extension' => 'filament',
                'show'      => $show,
                'panel'     => $panelId,
            ]);
        }
        
        return true;
    }

    protected static function getResourceIcon(string $resource): ?string
    {
        if (!method_exists($resource, 'getNavigationIcon')) {
            return null;
        }

        try {
            $icon = $resource::getNavigationIcon();
        } catch (\Throwable $e) {
            return null;
        }

        if ($icon === null) {
            return null;
        }

        return static::normalizeIcon($icon);
    }

    protected static function getPageIcon(string $page, ?\Filament\Panel $panel = null): ?string
    {
        if (!method_exists($page, 'getNavigationIcon')) {
            return null;
        }

        try {
            // Try to set panel context if provided, so getNavigationIcon() can work properly
            $originalPanel = null;
            if ($panel) {
                try {
                    $originalPanel = Filament::getCurrentPanel();
                    Filament::setCurrentPanel($panel);
                } catch (\Throwable $e) {
                    // Silently fail if we can't set panel context
                }
            }

            $icon = $page::getNavigationIcon();

            // Restore original panel context
            if ($panel && $originalPanel !== null) {
                try {
                    Filament::setCurrentPanel($originalPanel);
                } catch (\Throwable $e) {
                    // Silently fail
                }
            }
        } catch (\Throwable $e) {
            // If getNavigationIcon() fails, try to get icon from static property directly
            if (property_exists($page, 'navigationIcon')) {
                $reflection = new \ReflectionClass($page);
                $property   = $reflection->getProperty('navigationIcon');
                $property->setAccessible(true);
                $icon = $property->getValue();
            } else {
                $icon = null;
            }

            // If still no icon, try default dashboard icon
            if ($icon === null && str_contains($page, 'Dashboard')) {
                try {
                    $icon = \Filament\Support\Icons\Heroicon::OutlinedHome;
                } catch (\Throwable $e) {
                    // Silently fail
                }
            }
        }

        if ($icon === null) {
            return null;
        }

        return static::normalizeIcon($icon);
    }

    protected static function normalizeIcon($icon): ?string
    {
        if ($icon === null) {
            return null;
        }

        if ($icon instanceof \BackedEnum) {
            $value = $icon->value;
            return Str::startsWith($value, 'heroicon-') ? $value : 'heroicon-' . $value;
        }

        if (is_string($icon)) {
            return Str::startsWith($icon, 'heroicon-') ? $icon : 'heroicon-' . $icon;
        }

        if (is_object($icon)) {
            if (property_exists($icon, 'value')) {
                $value = $icon->value;
                if (is_string($value)) {
                    return Str::startsWith($value, 'heroicon-') ? $value : 'heroicon-' . $value;
                }
            } elseif (property_exists($icon, 'name')) {
                $name = $icon->name;
                if (is_string($name)) {
                    return Str::startsWith($name, 'heroicon-') ? $name : 'heroicon-' . $name;
                }
            }
        }

        return null;
    }

    protected static function reorderTopLevelMenus(?string $panelId = null): void
    {
        if (!$panelId) {
            return;
        }

        $topLevelMenus = static::query()
            ->where('parent_id', 0)
            ->where('panel', $panelId)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $order = 1;
        foreach ($topLevelMenus as $menu) {
            $menu->order = $order++;
            $menu->save();
        }
    }

    /**
     * Start a navigation generator instance.
     */
    public static function generate(): \Ycookies\FilamentNavManager\NavManagerNavigationGenerator
    {
        return \Ycookies\FilamentNavManager\NavManagerNavigationGenerator::make();
    }

    /**
     * Build the navigation elements for a panel, optionally using cache.
     *
     * @return array<NavigationGroup|NavigationItem>
     */
    public static function navigationForPanel(string $panel, ?int $cacheSeconds = null): array
    {
        $generator = static::generate()->panel($panel);

        if ($cacheSeconds !== null) {
            $generator->cacheTime($cacheSeconds);
        }

        return $generator->build();
    }

    /**
     * Flush cached navigation for a panel.
     */
    public static function flushNavigationCache(?string $panelId = null): void
    {
        \Ycookies\FilamentNavManager\NavManagerNavigationGenerator::flush($panelId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function navigationDataForPanel(string $panel): array
    {
        return static::query()
            ->forPanel($panel)
            ->visible()
            ->where('parent_id', 0)
            ->orderBy('order', 'asc')
            ->with(['childrenRecursive' => fn ($query) => $query->ordered()->visible()])
            ->get()
            ->map(fn (NavManager $menu) => $menu->toNavigationData())
            ->toArray();
    }

    /**
     * @param  array<int, array<string, mixed>>  $menusData
     * @return array<NavigationGroup|NavigationItem>
     */
    public static function navigationElementsFromData(array $menusData, bool $includeClosures = true): array
    {
        return collect($menusData)
            ->map(fn (array $data) => static::menuFromData($data)->toNavigationElement($includeClosures))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function menuFromData(array $data): self
    {
        $menu = new static;
        $menu->forceFill($data['attributes'] ?? []);

        $children = collect($data['children'] ?? [])
            ->map(fn (array $child) => static::menuFromData($child))
            ->values();

        $menu->setRelation('childrenRecursive', $children);

        return $menu;
    }

    protected function toNavigationData(): array
    {
        return [
            'attributes' => $this->only([
                'id',
                'parent_id',
                'panel',
                'order',
                'title',
                'type',
                'icon',
                'uri',
                'target',
                'badge',
                'badge_color',
                'is_collapsed',
                'permission',
            ]),
            'children' => $this->childrenRecursive
                ->map(fn (NavManager $child) => $child->toNavigationData())
                ->toArray(),
        ];
    }

    protected function toNavigationElement(bool $includeClosures = true): NavigationGroup | NavigationItem | null
    {
        $children    = $this->childrenRecursive;
        $hasChildren = $children->isNotEmpty();

        if ($hasChildren) {
            $items = $children
                ->map(fn (NavManager $child) => $child->toNavigationItem(false, $includeClosures))
                ->filter()
                ->values()
                ->all();
        } else {
            $items = array_filter([
                $this->toNavigationItem(false, $includeClosures),
            ]);
        }

        if (!$hasChildren) {
            return $items[0] ?? null;
        }

        if (empty($items)) {
            return null;
        }

        $group = NavigationGroup::make()
            ->items($items)
            ->collapsed($hasChildren ? ($this->is_collapsed ?? true) : false)
            ->collapsible($hasChildren);

        if ($label = $this->title) {
            $group->label($label);
        }

        if ($this->hasIcon()) {
            $validatedIcon = $this->validateIcon($this->icon);
            if ($validatedIcon) {
                $group->icon($validatedIcon);
            }
        }

        if ($this->badge) {
            $group->badge($this->badge, color: $this->badge_color ?: null);
        }

        return $group;
    }

    protected function toNavigationItem(bool $ancestorHasIcon = false, bool $includeClosures = true): ?NavigationItem
    {
        $item = NavigationItem::make($this->title)
            ->sort($this->order ?? 0);

        $icon = $this->determineIcon($ancestorHasIcon);

        if ($icon) {
            $validatedIcon = $this->validateIcon($icon);
            if ($validatedIcon) {
                $item->icon($validatedIcon);
            }
        }

        // Handle badge - real-time from Resource/Page if available, otherwise use database value
        $badgeCallback = $this->getRealTimeBadgeCallback();
        
        if ($badgeCallback) {
            // Use Closure to get badge in real-time
            $item->badge(
                $badgeCallback['badge'],
                $badgeCallback['color'] ?? null
            );
            
            // Support badge tooltip if Resource/Page provides it
            if (isset($badgeCallback['tooltip']) && method_exists($item, 'badgeTooltip')) {
                $item->badgeTooltip($badgeCallback['tooltip']);
            }
        } elseif ($this->badge) {
            // Fallback to database badge
            $item->badge($this->badge, $this->badge_color ?: null);
        }

        if ($includeClosures) {
            $activePatterns = $this->getActiveRoutePatterns();

            if (!empty($activePatterns)) {
                $item->isActiveWhen(fn (): bool => original_request()->routeIs(...$activePatterns));
            }
        }

        if ($this->permission) {
            $item->visible(function (): bool {
                $guard = Filament::auth();

                if (!$guard->check()) {
                    return false;
                }

                return (bool) optional($guard->user())->can($this->permission);
            });
        }

        $item = $this->applyDestination($item);
        if (!$item) {
            return null;
        }

        return $item;
    }

    /**
     * @return array<int, string>
     */
    protected function getActiveRoutePatterns(): array
    {
        $patterns = [];

        $panel = $this->panel
            ? Filament::getPanel($this->panel, isStrict: false)
            : Filament::getCurrentOrDefaultPanel();

        if ($panel === null) {
            return $patterns;
        }

        switch ($this->type) {
            case self::TYPE_RESOURCE:
                $resource = $this->target;

                if ($resource && class_exists($resource) && is_subclass_of($resource, FilamentResource::class)) {
                    try {
                        $routeBase  = $resource::getRouteBaseName($panel);
                        $patterns[] = "{$routeBase}.*";
                    } catch (\Throwable) {
                        //
                    }
                }

                break;

            case self::TYPE_PAGE:
                $page = $this->target;

                if ($page && class_exists($page) && is_subclass_of($page, FilamentPage::class)) {
                    try {
                        $patterns[] = $page::getRouteName($panel);
                    } catch (\Throwable) {
                        //
                    }
                }

                break;

            case self::TYPE_ROUTE:
                $routeName = $this->target ?: $this->uri;

                if ($routeName) {
                    $patterns[] = $routeName;
                }

                break;

            default:
                $routeName = $this->target ?: $this->uri;

                if ($routeName) {
                    $patterns[] = $routeName;
                }

                break;
        }

        return array_filter($patterns);
    }

    protected function applyDestination(NavigationItem $item): ?NavigationItem
    {
        $panelId = $this->panel ?? Filament::getCurrentPanel()?->getId();

        return match ($this->type) {
            self::TYPE_RESOURCE => $this->applyResourceDestination($item, $panelId),
            self::TYPE_PAGE     => $this->applyPageDestination($item, $panelId),
            self::TYPE_URL      => $this->target
                ? $this->applyUrlDestination($item, $this->target)
                : ($this->uri ? $this->applyUrlDestination($item, $this->uri) : null),
            self::TYPE_ROUTE => $this->applyRouteDestination($item),
            default          => $this->uri
                ? $this->applyUrlDestination($item, $this->uri)
                : null,
        };
    }

    protected function applyResourceDestination(NavigationItem $item, ?string $panelId = null): ?NavigationItem
    {
        $resource = $this->target;

        if (!$resource || !class_exists($resource) || !is_subclass_of($resource, FilamentResource::class)) {
            return null;
        }

        try {
            $url = $resource::getUrl(panel: $panelId);
        } catch (\Throwable) {
            $url = $resource::getUrl();
        }

        return $item->url($url);
    }

    protected function applyPageDestination(NavigationItem $item, ?string $panelId = null): ?NavigationItem
    {
        $page = $this->target;

        if (!$page || !class_exists($page) || !is_subclass_of($page, FilamentPage::class)) {
            return null;
        }

        try {
            $url = $page::getUrl(panel: $panelId);
        } catch (\Throwable) {
            $url = $page::getUrl();
        }

        return $item->url($url);
    }

    protected function applyRouteDestination(NavigationItem $item): ?NavigationItem
    {
        $routeName = $this->target ?: $this->uri;

        if (!$routeName || !Route::has($routeName)) {
            return null;
        }

        return $item->url(route($routeName));
    }

    protected function applyUrlDestination(NavigationItem $item, string $url): NavigationItem
    {
        $openInNewTab = Str::startsWith($url, ['http://', 'https://']);
        $resolvedUrl  = $openInNewTab ? $url : url($url);

        return $item->url($resolvedUrl, $openInNewTab);
    }

    protected function hasIcon(): bool
    {
        return filled($this->icon);
    }

    protected function determineIcon(bool $ancestorHasIcon = false): ?string
    {
        $hasChildren = $this->childrenRecursive->isNotEmpty();

        if ($this->hasIcon()) {
            if ($ancestorHasIcon && $hasChildren) {
                return null;
            }

            return $this->icon;
        }

        if ($ancestorHasIcon) {
            return null;
        }

        $firstChildIcon = $this->childrenRecursive
            ->map(fn (NavManager $child) => $child->determineIcon(false))
            ->filter()
            ->first();

        if ($firstChildIcon) {
            return $firstChildIcon;
        }

        return $hasChildren ? 'heroicon-o-rectangle-stack' : null;
    }

    /**
     * Validate if an icon exists and return it, or return null if invalid.
     * This prevents SvgNotFound exceptions when invalid icon names are stored in the database.
     * 
     * Handles incomplete icon names (e.g., 'cube-transparent') by trying multiple formats:
     * - heroicon-o-{name} (outlined)
     * - heroicon-m-{name} (medium)
     * - heroicon-c-{name} (mini)
     * - heroicon-s-{name} (solid)
     *
     * @param string|null $icon
     * @return string|null
     */
    protected function validateIcon(?string $icon): ?string
    {
        if (blank($icon)) {
            return null;
        }

        // If icon already has heroicon- prefix but missing variant (e.g., heroicon-cube-transparent)
        // Try to fix it by trying common variants
        if (Str::startsWith($icon, 'heroicon-') && !preg_match('/^heroicon-[ocms]-/', $icon)) {
            $baseName = str_replace('heroicon-', '', $icon);
            $variants = ['o-', 'm-', 'c-', 's-'];
            
            foreach ($variants as $variant) {
                $testIcon = "heroicon-{$variant}{$baseName}";
                if ($this->tryResolveIcon($testIcon)) {
                    return $testIcon;
                }
            }
            
            // If no variant works, return null
            if (config('app.debug')) {
                Log::warning("Navigation icon format invalid (missing variant): {$icon}", [
                    'menu_title' => $this->title,
                    'menu_id'    => $this->id,
                ]);
            }
            return null;
        }

        // If icon doesn't have heroicon- prefix, add it and try common variants
        if (!Str::startsWith($icon, 'heroicon-')) {
            $variants = ['o-', 'm-', 'c-', 's-'];
            
            foreach ($variants as $variant) {
                $testIcon = "heroicon-{$variant}{$icon}";
                if ($this->tryResolveIcon($testIcon)) {
                    return $testIcon;
                }
            }
            
            // If no variant works, return null
            if (config('app.debug')) {
                Log::warning("Navigation icon not found (tried variants): {$icon}", [
                    'menu_title' => $this->title,
                    'menu_id'    => $this->id,
                ]);
            }
            return null;
        }

        // Icon already has correct format, just validate it exists
        if ($this->tryResolveIcon($icon)) {
            return $icon;
        }

        // Icon doesn't exist
        if (config('app.debug')) {
            Log::warning("Navigation icon not found: {$icon}", [
                'menu_title' => $this->title,
                'menu_id'    => $this->id,
            ]);
        }
        return null;
    }

    /**
     * Try to resolve an icon and return true if it exists, false otherwise.
     *
     * @param string $icon
     * @return bool
     */
    protected function tryResolveIcon(string $icon): bool
    {
        try {
            if (function_exists('svg')) {
                svg($icon);
            } else {
                $iconFactory = app(IconFactory::class);
                $iconFactory->svg($icon);
            }
            return true;
        } catch (\BladeUI\Icons\Exceptions\SvgNotFound $e) {
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get real-time badge callback for Resource/Page types.
     * Badge is fetched in real-time and not cached.
     *
     * @return array{badge: Closure, color?: Closure, tooltip?: Closure}|null
     */
    protected function getRealTimeBadgeCallback(): ?array
    {
        // Only for Resource and Page types
        if ($this->type !== self::TYPE_RESOURCE && $this->type !== self::TYPE_PAGE) {
            return null;
        }

        $target = $this->target;
        
        if (!$target || !class_exists($target)) {
            return null;
        }

        // For Resource type
        if ($this->type === self::TYPE_RESOURCE && is_subclass_of($target, FilamentResource::class)) {
            return [
                'badge' => function () use ($target): ?string {
                    try {
                        // Check if Resource has getNavigationBadge method
                        if (method_exists($target, 'getNavigationBadge')) {
                            $badge = $target::getNavigationBadge();
                            if ($badge !== null) {
                                return (string) $badge;
                            }
                        }
                    } catch (\Throwable $e) {
                        // Silently fail and fallback to database badge
                        if (config('app.debug')) {
                            Log::warning("Error getting real-time badge from Resource: {$target}", [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                    
                    // Fallback to database badge
                    return $this->badge ?: null;
                },
                'color' => function () use ($target) {
                    try {
                        // Check if Resource has getNavigationBadgeColor method
                        if (method_exists($target, 'getNavigationBadgeColor')) {
                            $color = $target::getNavigationBadgeColor();
                            if ($color !== null) {
                                return $color; // Can be string or array
                            }
                        }
                    } catch (\Throwable $e) {
                        // Silently fail and fallback to database badge color
                    }
                    
                    // Fallback to database badge color
                    return $this->badge_color ?: null;
                },
                'tooltip' => function () use ($target) {
                    try {
                        // Check if Resource has getNavigationBadgeTooltip method
                        if (method_exists($target, 'getNavigationBadgeTooltip')) {
                            return $target::getNavigationBadgeTooltip();
                        }
                    } catch (\Throwable $e) {
                        // Silently fail
                    }
                    
                    return null;
                },
            ];
        }

        // For Page type
        if ($this->type === self::TYPE_PAGE && is_subclass_of($target, FilamentPage::class)) {
            return [
                'badge' => function () use ($target): ?string {
                    try {
                        // Check if Page has getNavigationBadge method
                        if (method_exists($target, 'getNavigationBadge')) {
                            $badge = $target::getNavigationBadge();
                            if ($badge !== null) {
                                return (string) $badge;
                            }
                        }
                    } catch (\Throwable $e) {
                        // Silently fail and fallback to database badge
                        if (config('app.debug')) {
                            Log::warning("Error getting real-time badge from Page: {$target}", [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                    
                    // Fallback to database badge
                    return $this->badge ?: null;
                },
                'color' => function () use ($target) {
                    try {
                        // Check if Page has getNavigationBadgeColor method
                        if (method_exists($target, 'getNavigationBadgeColor')) {
                            $color = $target::getNavigationBadgeColor();
                            if ($color !== null) {
                                return $color;
                            }
                        }
                    } catch (\Throwable $e) {
                        // Silently fail and fallback to database badge color
                    }
                    
                    // Fallback to database badge color
                    return $this->badge_color ?: null;
                },
                'tooltip' => function () use ($target) {
                    try {
                        // Check if Page has getNavigationBadgeTooltip method
                        if (method_exists($target, 'getNavigationBadgeTooltip')) {
                            return $target::getNavigationBadgeTooltip();
                        }
                    } catch (\Throwable $e) {
                        // Silently fail
                    }
                    
                    return null;
                },
            ];
        }

        return null;
    }

    /**
     * Discover all classes in a directory, including those with isDiscovered = false.
     * This is similar to Filament's discoverComponents but doesn't filter by isDiscovered.
     *
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     * @param string $directory
     * @param string $namespace
     * @param string $baseClass
     * @return array<string>
     */
    protected static function discoverClassesInDirectory(
        \Illuminate\Filesystem\Filesystem $filesystem,
        string $directory,
        string $namespace,
        string $baseClass
    ): array {
        $classes = [];
        
        if (blank($directory) || blank($namespace)) {
            return $classes;
        }

        if ((!$filesystem->exists($directory)) && (!\Illuminate\Support\Str::contains($directory, '*'))) {
            return $classes;
        }

        $namespace = \Illuminate\Support\Str::of($namespace);

        foreach ($filesystem->allFiles($directory) as $file) {
            $variableNamespace = $namespace->contains('*') ? str_ireplace(
                ['\\' . $namespace->before('*'), $namespace->after('*')],
                ['', ''],
                str_replace([DIRECTORY_SEPARATOR], ['\\'], (string) \Illuminate\Support\Str::of($file->getPath())->after(base_path())),
            ) : null;

            if (is_string($variableNamespace)) {
                $variableNamespace = (string) \Illuminate\Support\Str::of($variableNamespace)->before('\\');
            }

            $class = (string) $namespace
                ->append('\\', $file->getRelativePathname())
                ->replace('*', $variableNamespace ?? '')
                ->replace([DIRECTORY_SEPARATOR, '.php'], ['\\', '']);

            if (!class_exists($class)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($class);
                
                if ($reflection->isAbstract()) {
                    continue;
                }

                if (!is_subclass_of($class, $baseClass)) {
                    continue;
                }

                // Don't filter by isDiscovered - include all classes
                $classes[] = $class;
            } catch (\Throwable $e) {
                // Skip invalid classes
                continue;
            }
        }

        return $classes;
    }
}

