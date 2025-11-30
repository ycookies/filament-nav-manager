<?php

namespace Ycookies\FilamentNavManager\Commands;

use Illuminate\Console\Command;

class FilamentNavManagerCommand extends Command
{
    public $signature = 'filament-nav-manager';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
