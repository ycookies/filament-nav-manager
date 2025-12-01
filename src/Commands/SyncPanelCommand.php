<?php

namespace Ycookies\FilamentNavManager\Commands;

use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Ycookies\FilamentNavManager\Models\NavManager;
use Ycookies\FilamentNavManager\NavManagerNavigationGenerator;

class SyncPanelCommand extends Command
{
    protected $signature = 'filament-nav-manager:sync {panel? : The panel ID to sync}';

    protected $description = 'Sync Filament resources and pages to Nav Manager';

    public function handle(): int
    {
        $panelId = $this->argument('panel') ?? Filament::getCurrentPanel()?->getId();

        if (!$panelId) {
            $this->error('No panel specified. Please provide a panel ID or run this command from within a Filament panel.');
            return self::FAILURE;
        }

        $panel = Filament::getPanel($panelId, isStrict: false);

        if (!$panel) {
            $this->error("Panel '{$panelId}' not found.");
            return self::FAILURE;
        }

        $this->info("Syncing panel: {$panel->getId()}");

        try {
            $syncedCount = NavManager::syncPanel($panel);
            
            // Clear navigation cache
            NavManagerNavigationGenerator::flush($panel->getId());
            
            // Clear Laravel caches to avoid route not found errors
            $this->info('Clearing caches...');
            try {
                $this->call('route:clear');
                $this->call('route:cache');
                $this->info('✓ Caches cleared successfully.');
            } catch (\Throwable $cacheError) {
                $this->warn('⚠ Failed to clear some caches: ' . $cacheError->getMessage());
                // Continue even if cache clearing fails
            }
            
            $this->info("✓ Successfully synced {$syncedCount} items.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("✗ Sync failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}

