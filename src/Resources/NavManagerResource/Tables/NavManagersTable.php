<?php

namespace Ycookies\FilamentNavManager\Resources\NavManagerResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Ycookies\FilamentNavManager\Models\NavManager;

class NavManagersTable
{
    public static function configure(Table $table): Table
    {
        // Check if treeView macro exists
        $hasTreeView = method_exists($table, 'treeView') || \Filament\Tables\Table::hasMacro('treeView');

        $table = $hasTreeView
            ? $table->treeView()
            : $table;

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $panelId = Filament::getCurrentPanel()?->getId();

                $query->with('parent')->withCount(['children as children_count']);

                // Filter by current panel
                if ($panelId) {
                    $query->where(function ($q) use ($panelId) {
                        $q->whereNull('panel')
                            ->orWhere('panel', $panelId);
                    });
                }

                return $query;
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('title')
                    ->extraAttributes(['class' => $hasTreeView ? 'tree-title' : ''])
                    ->label(__('nav-manager::nav-manager.table.title') ?: 'Title')
                    ->icon(fn (NavManager $record) => $record->children_count ? 'heroicon-o-chevron-right' : null)
                    ->searchable()
                    ->html()
                    ->formatStateUsing(
                        fn (string $state, NavManager $record): HtmlString => new HtmlString(
                            ($hasTreeView ? str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $record->depth()) : '') . e($state)
                        )
                    ),

                IconColumn::make('icon')
                    ->label(__('nav-manager::nav-manager.table.icon') ?: 'Icon')
                    ->icon(fn (?string $state) => filled($state) ? $state : null)
                    ->color('primary'),

                TextColumn::make('type')
                    ->label(__('nav-manager::nav-manager.table.type') ?: 'Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        NavManager::TYPE_GROUP => __('nav-manager::nav-manager.types.group') ?: 'Group',
                        NavManager::TYPE_RESOURCE => __('nav-manager::nav-manager.types.resource') ?: 'Resource',
                        NavManager::TYPE_PAGE => __('nav-manager::nav-manager.types.page') ?: 'Page',
                        NavManager::TYPE_ROUTE => __('nav-manager::nav-manager.types.route') ?: 'Route',
                        NavManager::TYPE_URL => __('nav-manager::nav-manager.types.url') ?: 'URL',
                        default => 'Unknown',
                    }),

                TextColumn::make('target')
                    ->label(__('nav-manager::nav-manager.form.target') ?: 'Target')
                    ->formatStateUsing(function (?string $state, NavManager $record): ?string {
                        if (blank($state)) {
                            return $record->uri ?: null;
                        }

                        return match ($record->type) {
                            NavManager::TYPE_RESOURCE, NavManager::TYPE_PAGE => class_basename($state),
                            default => $state,
                        };
                    })
                    ->tooltip(fn (?string $state, NavManager $record) => $state ?? $record->uri)
                    ->copyable()
                    ->copyMessage(__('nav-manager::nav-manager.table.copied') ?: 'Copied')
                    ->copyMessageDuration(1500)
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('badge')
                    ->label(__('nav-manager::nav-manager.table.badge') ?: 'Badge')
                    ->badge()
                    ->color(fn (?string $color) => $color ?: 'primary')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('permission')
                    ->label(__('nav-manager::nav-manager.form.permission') ?: 'Permission')
                    ->badge()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('show')
                    ->label(__('nav-manager::nav-manager.table.show') ?: 'Show')
                    ->afterStateUpdated(function (Component $livewire, $state) {
                        // Refresh navigation cache
                        NavManager::flushNavigationCache();
                        $livewire->dispatch('refresh-sidebar');
                    }),

                ToggleColumn::make('is_collapsed')
                    ->label(__('nav-manager::nav-manager.table.is_collapsed') ?: 'Collapsed')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('order')
                    ->label(__('nav-manager::nav-manager.table.order') ?: 'Order')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label(__('nav-manager::nav-manager.table.updated_at') ?: 'Updated At')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('panel')
                    ->label(__('nav-manager::nav-manager.table.panel') ?: 'Panel')
                    ->options(NavManager::panelOptions()),

                SelectFilter::make('type')
                    ->label(__('nav-manager::nav-manager.table.type') ?: 'Type')
                    ->options([
                        NavManager::TYPE_GROUP => __('nav-manager::nav-manager.types.group') ?: 'Group',
                        NavManager::TYPE_RESOURCE => __('nav-manager::nav-manager.types.resource') ?: 'Resource',
                        NavManager::TYPE_PAGE => __('nav-manager::nav-manager.types.page') ?: 'Page',
                        NavManager::TYPE_ROUTE => __('nav-manager::nav-manager.types.route') ?: 'Route',
                        NavManager::TYPE_URL => __('nav-manager::nav-manager.types.url') ?: 'URL',
                    ]),

                TernaryFilter::make('show')
                    ->label(__('nav-manager::nav-manager.table.show') ?: 'Show Status')
                    ->placeholder(__('nav-manager::nav-manager.table.all') ?: 'All')
                    ->trueLabel(__('nav-manager::nav-manager.table.show') ?: 'Show')
                    ->falseLabel(__('nav-manager::nav-manager.table.hide') ?: 'Hide'),

                SelectFilter::make('parent_id')
                    ->label(__('nav-manager::nav-manager.form.parent_menu') ?: 'Parent Menu')
                    ->options(fn () => NavManager::parentSelectOptions())
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->defaultSort('order', 'asc')
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

