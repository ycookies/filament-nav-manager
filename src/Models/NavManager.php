<?php

namespace Ycookies\FilamentNavManager\Models;

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
            $resources = $panel->getResources();
            $pages     = $panel->getPages();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to get panel resources and pages: ' . $e->getMessage());
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

        // Create navigation groups
        $sortedGroups = collect($groupOrderMap)
            ->sortBy(fn($order) => $order)
            ->keys()
            ->filter()
            ->values();

        foreach ($sortedGroups as $groupTitle) {
            $groupOrder = $groupOrderMap[$groupTitle];
            $groupMenu  = static::updateOrCreate(
                [
                    'title'     => $groupTitle,
                    'parent_id' => 0,
                    'type'      => static::TYPE_GROUP,
                    'panel'     => $panelId,
                ],
                [
                    'order'     => $groupOrder,
                    'icon'      => null,
                    'uri'       => '#',
                    'extension' => 'filament',
                    'show'      => 1,
                    'type'      => static::TYPE_GROUP,
                    'panel'     => $panelId,
                ]
            );
            $groupMap[$groupTitle] = $groupMenu->id;
        }

        $order = max($groupOrderMap ?: [0]) + 1;

        // Sync resources
        foreach ($resources as $resource) {
            if (is_string($resource) && class_exists($resource) && is_subclass_of($resource, FilamentResource::class)) {
                static::syncResource($resource, $groupMap, $order++, $panelId, $panelPath);
                $syncedCount++;
            }
        }

        // Sync pages
        foreach ($pages as $page) {
            if (is_string($page) && class_exists($page) && is_subclass_of($page, FilamentPage::class)) {
                static::syncPage($page, $groupMap, $order++, $panelId, $panelPath);
                $syncedCount++;
            }
        }

        static::reorderTopLevelMenus($panelId);

        return $syncedCount;
    }

    protected static function syncResource(string $resource, array $groupMap, int $order, ?string $panelId = null, string $panelPath = 'admin'): void
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

        $isDiscovered = method_exists($resource, 'isDiscovered') ? $resource::isDiscovered() : true;
        $show         = $isDiscovered ? 1 : 0;

        static::updateOrCreate(
            [
                'title' => $label,
                'uri'   => $uri,
                'type'  => static::TYPE_RESOURCE,
                'panel' => $panelId,
            ],
            [
                'parent_id' => $parentId,
                'order'     => $sort,
                'icon'      => $icon,
                'uri'       => $uri,
                'target'    => $resource,
                'type'      => static::TYPE_RESOURCE,
                'extension' => 'filament',
                'show'      => $show,
                'panel'     => $panelId,
            ]
        );
    }

    protected static function syncPage(string $page, array $groupMap, int $order, ?string $panelId = null, string $panelPath = 'admin'): void
    {
        $label = method_exists($page, 'getNavigationLabel')
            ? $page::getNavigationLabel()
            : (method_exists($page, 'getTitle')
                ? $page::getTitle()
                : class_basename($page));
        $icon  = static::getPageIcon($page);
        $group = method_exists($page, 'getNavigationGroup') ? $page::getNavigationGroup() : null;
        $sort  = method_exists($page, 'getNavigationSort') ? ($page::getNavigationSort() ?? $order) : $order;
        $slug  = method_exists($page, 'getSlug') ? $page::getSlug() : null;

        $parentId = 0;
        if ($group && isset($groupMap[$group])) {
            $parentId = $groupMap[$group];
        }

        $uri = $slug ? "{$panelPath}/{$slug}" : '#';

        $isDiscovered = method_exists($page, 'isDiscovered') ? $page::isDiscovered() : true;
        $show         = $isDiscovered ? 1 : 0;

        static::updateOrCreate(
            [
                'title' => $label,
                'uri'   => $uri,
                'type'  => static::TYPE_PAGE,
                'panel' => $panelId,
            ],
            [
                'parent_id' => $parentId,
                'order'     => $sort,
                'icon'      => $icon,
                'uri'       => $uri,
                'target'    => $page,
                'type'      => static::TYPE_PAGE,
                'extension' => 'filament',
                'show'      => $show,
                'panel'     => $panelId,
            ]
        );
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

    protected static function getPageIcon(string $page): ?string
    {
        if (!method_exists($page, 'getNavigationIcon')) {
            return null;
        }

        try {
            $icon = $page::getNavigationIcon();
        } catch (\Throwable $e) {
            return null;
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
            $group->icon($this->icon);
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
            $item->icon($icon);
        }

        if ($this->badge) {
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
}

