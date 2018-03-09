<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class OptimizeClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'optimize:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all caches (routes, config, views, compiled class)';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('cache:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('clear-compiled');

        $this->info('Config, routes and view cache cleared successfully!');
    }
}
