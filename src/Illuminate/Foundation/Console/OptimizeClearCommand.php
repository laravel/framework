<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Cache\Console\ClearCommand as CacheClearCommand;

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
    protected $description = 'Remove the cached bootstrap files';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call(ViewClearCommand::class);
        $this->call(CacheClearCommand::class);
        $this->call(RouteClearCommand::class);
        $this->call(ConfigClearCommand::class);
        $this->call(ClearCompiledCommand::class);

        $this->info('Caches cleared successfully!');
    }
}
