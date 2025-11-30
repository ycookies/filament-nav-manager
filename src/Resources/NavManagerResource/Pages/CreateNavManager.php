<?php

namespace Ycookies\FilamentNavManager\Resources\NavManagerResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Ycookies\FilamentNavManager\Models\NavManager;
use Ycookies\FilamentNavManager\NavManagerNavigationGenerator;
use Ycookies\FilamentNavManager\Resources\NavManagerResource;

class CreateNavManager extends CreateRecord
{
    protected static string $resource = NavManagerResource::class;

    protected function afterCreate(): void
    {
        // Clear navigation cache after creation
        NavManagerNavigationGenerator::flush();
    }
}

