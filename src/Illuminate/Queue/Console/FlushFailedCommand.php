<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;

class FlushFailedCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:flush {--hours= : The number of hours to retain failed job data}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'queue:flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush all of the failed queue jobs';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->laravel['queue.failer']->flush($this->option('hours'));

        if ($this->option('hours')) {
            $this->info("All jobs that failed more than {$this->option('hours')} hours ago have been deleted successfully.");

            return;
        }

        $this->info('All failed jobs deleted successfully.');
    }
}
