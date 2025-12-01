<?php

namespace Ycookies\FilamentNavManager;

use Closure;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Filament\Actions\Action;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Concerns\PackageServiceProvider\ProcessTranslations;
use Ycookies\FilamentNavManager\Commands\FilamentNavManagerCommand;
use Ycookies\FilamentNavManager\Commands\InstallCommand as NavManagerInstallCommand;
use Ycookies\FilamentNavManager\Commands\SyncPanelCommand;
use Ycookies\FilamentNavManager\Testing\TestsFilamentNavManager;

class FilamentNavManagerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-nav-manager';

    public static string $viewNamespace = 'filament-nav-manager';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->endWith(function (InstallCommand $command) {
                        // After migrations, ask which panels to sync
                        $command->newLine();
                        $command->info('📋 Sync Filament Resources and Pages');
                        $command->comment('You can sync panels now by running: php artisan filament-nav-manager:sync {panel-id}');
                        $command->newLine();
                    })
                    ->askToStarRepoOnGitHub('ycookies/filament-nav-manager');
            });

        // Explicitly specify config file name since it's 'nav-manager.php' not 'filament-nav-manager.php'
        $configFileName = 'nav-manager';
        
        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile($configFileName);
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        // Don't use hasTranslations() as it uses package shortName as namespace
        // We need 'nav-manager' namespace instead of 'filament-nav-manager'
        // We'll manually load translations in bootPackageTranslations() with correct namespace
        // if (file_exists($package->basePath('/../resources/lang'))) {
        //     $package->hasTranslations();
        // }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        // Translations will be loaded in bootPackageTranslations() with 'nav-manager' namespace
    }

    /**
     * Override bootPackageTranslations to load translations with 'nav-manager' namespace
     * instead of using the default 'filament-nav-manager' namespace from shortName()
     */
    protected function bootPackageTranslations(): self
    {
        $langPath = $this->package->basePath('/../resources/lang');
        
        if (file_exists($langPath)) {
            // Load translations with 'nav-manager' namespace to match our translation keys
            $this->loadTranslationsFrom($langPath, 'nav-manager');
            
            // Also load JSON translations
            $this->loadJsonTranslationsFrom($langPath);
            
            // Publish translations for publishing
            if ($this->app->runningInConsole()) {
                $appTranslations = (function_exists('lang_path'))
                    ? lang_path('vendor/nav-manager')
                    : resource_path('lang/vendor/nav-manager');
                
                $this->publishes(
                    [$langPath => $appTranslations],
                    'nav-manager-translations'
                );
            }
        }
        
        return $this;
    }

    public function packageBooted(): void
    {
        // Register table treeView macro
        $this->tableTreeView();

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-nav-manager/{$file->getFilename()}"),
                ], 'filament-nav-manager-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsFilamentNavManager);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'ycookies/filament-nav-manager';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        $assets = [];
        
        // Register CSS from dist if exists
        $cssPath = __DIR__ . '/../resources/dist/filament-nav-manager.css';
        if (file_exists($cssPath)) {
            $assets[] = Css::make('filament-nav-manager-styles', $cssPath);
        }
        
        // Register JS - prefer dist, fallback to source
        $jsDistPath   = __DIR__ . '/../resources/dist/filament-nav-manager.js';
        $jsSourcePath = __DIR__ . '/../resources/js/index.js';
        
        if (file_exists($jsDistPath)) {
            $assets[] = Js::make('filament-nav-manager-scripts', $jsDistPath);
        } elseif (file_exists($jsSourcePath)) {
            $assets[] = Js::make('filament-nav-manager-tree-view', $jsSourcePath);
        }
        
        return $assets;
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FilamentNavManagerCommand::class,
            NavManagerInstallCommand::class,
            SyncPanelCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_nav_manager_table',
        ];
    }

    //  Definition for tree view table macro
    protected function tableTreeView(){
        
        Table::macro('treeView', function (array $options = []) {
            /** @var Table $this */
            $options = array_merge([
                'parentColumn'       => 'parent_id',
                'rootValues'         => [0, '0', null, -1, '-1'],
                'orderColumn'        => 'order',
                'keyColumn'          => null,
                'reorderable'        => true,
                'hiddenClass'        => 'hidden',
                'recordVisibleUsing' => null,
                'isRootUsing'        => null,
                'reorderLabels'      => [
                    'enable'  => '启用重新排序',
                    'disable' => '禁用重新排序',
                ],
            ], $options);

            $isRecordVisible = function ($record) use ($options): bool {
                if ($options['recordVisibleUsing'] instanceof Closure) {
                    return (bool) value($options['recordVisibleUsing'], $record, $options);
                }

                if ($options['isRootUsing'] instanceof Closure) {
                    return (bool) value($options['isRootUsing'], $record, $options);
                }

                $parentValue = data_get($record, $options['parentColumn']);

                return in_array($parentValue, Arr::wrap($options['rootValues']), true);
            };

            $this->paginated(false)
                ->recordUrl(false)
                ->recordClasses(function ($record) use ($options, $isRecordVisible) {
                    $rid = 'pr-'.$record->parent_id;
                    return $isRecordVisible($record) ? '' : $rid.' '.$options['hiddenClass'];
                });

            if ($options['reorderable']) {
                $this->reorderable($options['orderColumn'])
                    ->reorderRecordsTriggerAction(
                        fn (Action $action, bool $isReordering) => $action
                            ->button()
                            ->label(
                                $isReordering
                                    ? $options['reorderLabels']['disable']
                                    : $options['reorderLabels']['enable'],
                            ),
                    );
            }

            $this->records(function () use ($options) {
                /** @var Table $this */
                $query = $this->getLivewire()->getFilteredSortedTableQuery();

                if (! $query) {
                    return collect();
                }

                $records = $query->get();

                if ($records->isEmpty()) {
                    return $records;
                }

                $keyColumn    = $options['keyColumn'] ?? $query->getModel()->getKeyName();
                $parentColumn = $options['parentColumn'];
                $orderColumn  = $options['orderColumn'];
                $rootValues   = Arr::wrap($options['rootValues']);

                $grouped   = $records->groupBy(fn ($record) => data_get($record, $parentColumn));
                $visited   = [];
                $flattened = collect();

                $traverse = function ($parentValue) use (&$traverse, $grouped, &$flattened, &$visited, $keyColumn, $orderColumn): void {
                    $children = $grouped->get($parentValue);

                    if (! $children) {
                        return;
                    }

                    $children = $children
                        ->sortBy([[$orderColumn, 'asc'], [$keyColumn, 'asc']])
                        ->values();

                    foreach ($children as $child) {
                        $recordKey = data_get($child, $keyColumn);

                        if ($recordKey === null) {
                            continue;
                        }

                        $flattened->push($child);
                        $visited[$recordKey] = true;

                        $traverse($recordKey);
                    }
                };

                foreach ($rootValues as $rootValue) {
                    $traverse($rootValue);
                }

                if ($records->count() !== count($visited)) {
                    $records
                        ->filter(fn ($record) => ! array_key_exists(data_get($record, $keyColumn), $visited))
                        ->sortBy([[$parentColumn, 'asc'], [$orderColumn, 'asc'], [$keyColumn, 'asc']])
                        ->each(function ($record) use (&$traverse, &$flattened, $keyColumn, &$visited): void {
                            $recordKey = data_get($record, $keyColumn);

                            if ($recordKey === null || array_key_exists($recordKey, $visited)) {
                                return;
                            }

                            $flattened->push($record);
                            $visited[$recordKey] = true;

                            $traverse($recordKey);
                        });
                }

                return $flattened;
            });

            return $this;
        });
    }
}
