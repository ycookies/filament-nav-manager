<?php

namespace Ycookies\FilamentNavManager;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentNavManagerPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-nav-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            \Ycookies\FilamentNavManager\Resources\NavManagerResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
