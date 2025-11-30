<?php

namespace Ycookies\FilamentNavManager\Resources\NavManagerResource\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Ycookies\FilamentNavManager\Models\NavManager;

class NavManagerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('nav-manager::nav-manager.form.basic_info') ?: 'Basic Information')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Select::make('panel')
                                    ->label(__('nav-manager::nav-manager.form.panel') ?: 'Panel')
                                    ->options(fn (): array => NavManager::panelOptions())
                                    ->default(fn () => Filament::getCurrentPanel()?->getId())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set) {
                                        $set('parent_id', null);
                                        $set('target', null);
                                    }),

                                Select::make('parent_id')
                                    ->label(__('nav-manager::nav-manager.form.parent_menu') ?: 'Parent Menu')
                                    ->options(fn (callable $get, ?NavManager $record): array => NavManager::parentSelectOptions(
                                        $get('panel') ?? Filament::getCurrentPanel()?->getId(),
                                        $record?->getKey(),
                                    ))
                                    ->default(0)
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->live(),

                                TextInput::make('title')
                                    ->label(__('nav-manager::nav-manager.form.title') ?: 'Title')
                                    ->required()
                                    ->maxLength(255),

                                Select::make('type')
                                    ->label(__('nav-manager::nav-manager.form.type') ?: 'Type')
                                    ->options([
                                        NavManager::TYPE_GROUP => __('nav-manager::nav-manager.types.group') ?: 'Group',
                                        NavManager::TYPE_RESOURCE => __('nav-manager::nav-manager.types.resource') ?: 'Resource',
                                        NavManager::TYPE_PAGE => __('nav-manager::nav-manager.types.page') ?: 'Page',
                                        NavManager::TYPE_ROUTE => __('nav-manager::nav-manager.types.route') ?: 'Route',
                                        NavManager::TYPE_URL => __('nav-manager::nav-manager.types.url') ?: 'URL',
                                    ])
                                    ->default(NavManager::TYPE_ROUTE)
                                    ->required()
                                    ->live(),

                                TextInput::make('icon')
                                    ->label(__('nav-manager::nav-manager.form.icon') ?: 'Icon')
                                    ->placeholder('heroicon-o-bars-3')
                                    ->hint('Heroicon name (e.g., heroicon-o-bars-3)'),
                            ])
                            ->columns(2),
                    ]),

                Section::make(__('nav-manager::nav-manager.form.basic_info') ?: 'Target & Route')
                    ->schema([
                        Select::make('target')
                            ->label(__('nav-manager::nav-manager.form.target') ?: 'Resource')
                            ->options(function (callable $get): array {
                                $panelId = $get('panel');
                                if (empty($panelId)) {
                                    $panelId = Filament::getCurrentPanel()?->getId();
                                }

                                $type = $get('type');

                                if ($type === NavManager::TYPE_RESOURCE) {
                                    return NavManager::resourceOptions($panelId);
                                }

                                if ($type === NavManager::TYPE_PAGE) {
                                    return NavManager::pageOptions($panelId);
                                }

                                return [];
                            })
                            ->searchable()
                            ->preload()
                            ->hidden(fn (callable $get) => !in_array($get('type'), [NavManager::TYPE_RESOURCE, NavManager::TYPE_PAGE]))
                            ->required(fn (callable $get) => in_array($get('type'), [NavManager::TYPE_RESOURCE, NavManager::TYPE_PAGE]))
                            ->dehydrated(fn (callable $get) => in_array($get('type'), [NavManager::TYPE_RESOURCE, NavManager::TYPE_PAGE]))
                            ->live(),

                        TextInput::make('uri')
                            ->label(__('nav-manager::nav-manager.form.uri') ?: 'URI')
                            ->placeholder('/admin/users')
                            ->hidden(fn (callable $get) => in_array($get('type'), [NavManager::TYPE_RESOURCE, NavManager::TYPE_PAGE]))
                            ->required(fn (callable $get) => !in_array($get('type'), [NavManager::TYPE_RESOURCE, NavManager::TYPE_PAGE]) && $get('type') !== NavManager::TYPE_GROUP)
                            ->live(),

                        TextInput::make('permission')
                            ->label(__('nav-manager::nav-manager.form.permission') ?: 'Permission')
                            ->placeholder('view-admin-menu')
                            ->hint('Laravel permission name'),

                        Toggle::make('show')
                            ->label(__('nav-manager::nav-manager.form.show') ?: 'Show')
                            ->default(true),

                        TextInput::make('order')
                            ->label(__('nav-manager::nav-manager.table.order') ?: 'Order')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }
}

