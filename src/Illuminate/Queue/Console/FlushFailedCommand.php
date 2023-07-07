<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:flush')]
class FlushFailedCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:flush {--hours= : The number of hours to retain failed job data}';

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
        $count = $this->laravel['queue.failer']->flush($this->option('hours'));

        if ($this->option('hours')) {
            $this->components->info(sprintf(
                '%d %s that failed more than %d %s ago have been deleted successfully.',
                $count,
                Str::plural('job', $count),
                $this->option('hours'),
                Str::plural('hour', $this->option('hours'))
            ));

            return;
        }

        $this->components->info(sprintf(
            '%d failed %s deleted successfully.',
            $count,
            Str::plural('job', $count)
        ));
    }
}
