<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:status')]
class ScheduleStatusCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the status of the scheduler';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Cache $cache)
    {
        $paused = $cache->get('illuminate:schedule:paused', false);

        if ($paused) {
            $this->components->warn('Scheduler is currently paused.');

            return self::FAILURE;
        }

        $this->components->info('Scheduler is currently running.');

        return self::SUCCESS;
    }
}
