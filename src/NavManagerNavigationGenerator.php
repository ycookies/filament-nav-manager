<?php

namespace Ycookies\FilamentNavManager;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Cache;
use Ycookies\FilamentNavManager\Models\NavManager;

class NavManagerNavigationGenerator
{
    protected ?string $panelId = null;

    protected ?int $cacheSeconds = null;

    /**
     * @var array<int, array<string, mixed>>|null 缓存的菜单数据（用于排序）
     */
    protected ?array $cachedMenuData = null;

    public static function make(): self
    {
        return new self;
    }

    public function panel(?string $panelId): self
    {
        $this->panelId = $panelId;

        return $this;
    }

    public function cacheTime(?int $seconds): self
    {
        $this->cacheSeconds = $seconds;

        return $this;
    }

    public function toClosure(): \Closure
    {
        return fn (NavigationBuilder $builder): NavigationBuilder => $this($builder);
    }

    /**
     * Allows the instance to be passed directly to ->navigation().
     */
    public function __invoke(NavigationBuilder $builder): NavigationBuilder
    {
        $elements = $this->build();

        if (empty($elements)) {
            return $builder;
        }

        // 获取原始菜单数据以保持顺序
        $panelId = $this->panelId
            ?? Filament::getCurrentPanel()?->getId()
            ?? Filament::getDefaultPanel()?->getId();

        if (!$panelId) {
            return $builder;
        }

        // 使用缓存的菜单数据
        $menuData = $this->cachedMenuData ?? NavManager::navigationDataForPanel($panelId);

        // 创建一个映射，用于快速查找元素
        $elementMap = [];
        foreach ($elements as $element) {
            $label = null;
            if ($element instanceof NavigationGroup) {
                $label = $element->getLabel();
            } elseif ($element instanceof NavigationItem) {
                $label = $element->getLabel();
            }
            if ($label) {
                $elementMap[$label] = $element;
            }
        }

        // 按照数据库中的 order 顺序构建导航组
        $orderedGroups = [];
        foreach ($menuData as $menuItem) {
            $title = $menuItem['attributes']['title'] ?? null;
            if (!$title || !isset($elementMap[$title])) {
                continue;
            }

            $element = $elementMap[$title];

            if ($element instanceof NavigationGroup) {
                $orderedGroups[] = $element;
            } elseif ($element instanceof NavigationItem) {
                // 将 NavigationItem 包装在无标签的 NavigationGroup 中，以保持顺序
                $orderedGroups[] = NavigationGroup::make()
                    ->items([$element])
                    ->collapsible(false);
            }
        }

        if (!empty($orderedGroups)) {
            $builder->groups($orderedGroups);
        }

        return $builder;
    }

    /**
     * Build the navigation element list.
     *
     * @return array<NavigationGroup|NavigationItem>
     */
    public function build(): array
    {
        $panelId = $this->panelId
            ?? Filament::getCurrentPanel()?->getId()
            ?? Filament::getDefaultPanel()?->getId();

        if (!$panelId) {
            return [];
        }

        $cacheSeconds = $this->cacheSeconds ?? (int) config('nav-manager.cache_seconds', 0);

        $this->cachedMenuData = $cacheSeconds <= 0
            ? NavManager::navigationDataForPanel($panelId)
            : Cache::remember(
                $this->cacheKey($panelId),
                now()->addSeconds($cacheSeconds),
                fn () => NavManager::navigationDataForPanel($panelId),
            );

        return NavManager::navigationElementsFromData($this->cachedMenuData);
    }

    public static function flush(?string $panelId = null): void
    {
        $panelId ??= Filament::getCurrentPanel()?->getId()
            ?? Filament::getDefaultPanel()?->getId();

        if (!$panelId) {
            return;
        }

        Cache::forget((new self)->cacheKey($panelId));
    }

    protected function cacheKey(string $panelId): string
    {
        return "nav-manager:navigation:{$panelId}";
    }
}

