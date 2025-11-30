<?php

namespace Ycookies\FilamentNavManager\Resources\NavManagerResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Ycookies\FilamentNavManager\Models\NavManager;
use Ycookies\FilamentNavManager\NavManagerNavigationGenerator;
use Ycookies\FilamentNavManager\Resources\NavManagerResource;

class EditNavManager extends EditRecord
{
    protected static string $resource = NavManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Clear navigation cache after update
        NavManagerNavigationGenerator::flush();
    }

    protected function afterDelete(): void
    {
        // Clear navigation cache after delete
        NavManagerNavigationGenerator::flush();
    }
}

