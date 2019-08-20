<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class OptimizeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache the framework bootstrap files';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call(ConfigCacheCommand::class);
        $this->call(RouteCacheCommand::class);

        $this->info('Files cached successfully!');
    }
}
