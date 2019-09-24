<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class LogsClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'logs:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear log files';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        exec('rm '.storage_path('logs/*.log'));
        $this->info('Logs have been cleared!');
    }
}
