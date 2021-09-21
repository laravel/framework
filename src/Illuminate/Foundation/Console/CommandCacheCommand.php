<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CommandCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a cache file for faster command loading';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        Artisan::cacheCommands();

        $this->info('Commands cached successfully!');
    }
}
