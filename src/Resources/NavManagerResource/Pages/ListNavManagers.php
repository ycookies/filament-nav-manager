<?php

namespace Ycookies\FilamentNavManager\Resources\NavManagerResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Ycookies\FilamentNavManager\Models\NavManager;
use Ycookies\FilamentNavManager\NavManagerNavigationGenerator;
use Ycookies\FilamentNavManager\Resources\NavManagerResource;

class ListNavManagers extends ListRecords
{
    protected static string $resource = NavManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('sync')
                ->label(__('nav-manager::nav-manager.actions.sync') ?: 'Sync Filament Menu')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('nav-manager::nav-manager.actions.sync') ?: 'Sync Filament Menu')
                ->modalDescription(__('nav-manager::nav-manager.actions.sync_description') ?: 'This will sync all Filament resources, pages and navigation groups to the menu database. Existing menus will be updated.')
                ->action(function () {
                    $this->syncFilamentMenus();
                }),
        ];
    }

    protected function syncFilamentMenus(): void
    {
        // Get current panel
        $panel = Filament::getCurrentPanel();

        if (!$panel) {
            Notification::make()
                ->title(__('nav-manager::nav-manager.actions.sync_error') ?: 'Error')
                ->body('Unable to get current panel')
                ->danger()
                ->send();
            return;
        }

        try {
            $syncedCount = NavManager::syncPanel($panel);

            // Clear navigation cache
            NavManagerNavigationGenerator::flush($panel->getId());

            Notification::make()
                ->title(__('nav-manager::nav-manager.actions.sync_success') ?: 'Sync Complete')
                ->body(__('nav-manager::nav-manager.actions.sync_success', ['count' => $syncedCount]) ?: "Successfully synced {$syncedCount} menu items")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('nav-manager::nav-manager.actions.sync_error') ?: 'Sync Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

