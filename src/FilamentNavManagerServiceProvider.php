<?php

namespace Ycookies\FilamentNavManager;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
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

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
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
        // Only register assets if dist files exist
        $assets = [];
        
        $cssPath = __DIR__ . '/../resources/dist/filament-nav-manager.css';
        $jsPath = __DIR__ . '/../resources/dist/filament-nav-manager.js';
        
        if (file_exists($cssPath)) {
            $assets[] = Css::make('filament-nav-manager-styles', $cssPath);
        }
        
        if (file_exists($jsPath)) {
            $assets[] = Js::make('filament-nav-manager-scripts', $jsPath);
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
}
