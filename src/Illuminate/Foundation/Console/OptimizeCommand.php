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
    protected $description = 'Optimize everything (cache routes, config)';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('cache:clear');
        $this->call('config:cache');
        $this->call('route:clear');
        $this->call('route:cache');

        $this->info('Config and routes cached successfully!');
    }
}
