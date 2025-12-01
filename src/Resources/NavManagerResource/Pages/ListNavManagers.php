<?php

namespace Ycookies\FilamentNavManager\Resources\NavManagerResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Ycookies\FilamentNavManager\Models\NavManager;
use Ycookies\FilamentNavManager\NavManagerNavigationGenerator;
use Ycookies\FilamentNavManager\Resources\NavManagerResource;

class ListNavManagers extends ListRecords
{
    protected static string $resource = NavManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('nav-manager::nav-manager.actions.create') ?: '添加导航菜单'),
            Action::make('sync')
                ->label(__('nav-manager::nav-manager.actions.sync') ?: 'Sync Filament Nav Menu')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('nav-manager::nav-manager.actions.sync') ?: 'Sync Filament Nav Menu')
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

            // Clear Laravel caches to avoid route not found errors
            // Note: We skip view:clear to avoid $errors variable issues during re-render
            // View cache is not critical and will be refreshed on next request
            // try {
            //     Artisan::call('route:clear');
            //     Artisan::call('route:cache');
            // } catch (\Throwable $cacheError) {
            //     // Log cache clearing errors but don't fail the sync
            //     // Log::warning('Failed to clear caches after nav sync: ' . $cacheError->getMessage());
            // }
            $this->dispatch('$refresh');
            Notification::make()
                ->title(__('nav-manager::nav-manager.actions.sync_complete_title') ?: 'Sync Complete')
                ->body(__('nav-manager::nav-manager.actions.sync_success', ['count' => $syncedCount]) ?: "Successfully synced {$syncedCount} menu items")
                ->success()
                ->send();
                
            // Refresh the component to ensure everything is properly initialized
            
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('nav-manager::nav-manager.actions.sync_error') ?: 'Sync Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

