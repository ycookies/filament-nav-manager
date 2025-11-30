<?php

namespace Ycookies\FilamentNavManager\Commands;

use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    protected $signature = 'filament-nav-manager:install';

    protected $description = 'Install Filament Nav Manager package';

    public function handle(): int
    {
        $this->info('🚀 Installing Filament Nav Manager...');
        $this->newLine();

        // Step 1: Check if migrations exist and run them
        if ($this->confirm('Run migrations?', true)) {
            $this->info('Running migrations...');
            Artisan::call('migrate');
            $this->info(Artisan::output());
        }

        // Step 2: Ask which panels to sync
        $this->newLine();
        $this->info('📋 Sync Filament Resources and Pages');
        $this->comment('Select which panels you want to sync:');

        $panels = Filament::getPanels();
        
        if ($panels->isEmpty()) {
            $this->warn('No Filament panels found. Please create a panel first.');
            return self::SUCCESS;
        }

        $panelOptions = $panels->mapWithKeys(function ($panel) {
            $label = method_exists($panel, 'getBrandName') 
                ? $panel->getBrandName() 
                : $panel->getId();
            
            return [$panel->getId() => (string) $label];
        })->all();

        // Display available panels
        $this->line('Available panels:');
        foreach ($panelOptions as $id => $label) {
            $this->line("  - {$id}: {$label}");
        }
        $this->newLine();

        // Ask if user wants to sync all panels
        $syncAll = $this->confirm('Sync all panels?', true);

        if ($syncAll) {
            $selectedPanels = array_keys($panelOptions);
        } else {
            // Ask for specific panel IDs
            $panelInput = $this->ask('Enter panel IDs to sync (comma-separated):');
            $selectedPanels = array_filter(
                array_map('trim', explode(',', $panelInput ?? '')),
                fn($id) => isset($panelOptions[$id])
            );
        }

        // Step 3: Sync selected panels
        foreach ($selectedPanels as $panelId) {
            if (!isset($panelOptions[$panelId])) {
                continue;
            }

            $this->newLine();
            $this->info("Syncing panel: {$panelOptions[$panelId]} ({$panelId})");

            try {
                $panel = Filament::getPanel($panelId, isStrict: false);
                
                if (!$panel) {
                    $this->warn("Panel {$panelId} not found, skipping...");
                    continue;
                }

                // Call sync command for this panel
                Artisan::call('filament-nav-manager:sync', [
                    'panel' => $panelId,
                ]);

                $output = Artisan::output();
                if (!empty(trim($output))) {
                    $this->line($output);
                }

                $this->info("✓ Panel {$panelOptions[$panelId]} synced successfully");
            } catch (\Throwable $e) {
                $this->error("✗ Failed to sync panel {$panelOptions[$panelId]}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('✅ Installation complete!');

        return self::SUCCESS;
    }
}

