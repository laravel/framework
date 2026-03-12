<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:pause')]
class SchedulePauseCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pause the scheduler';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Cache $cache)
    {
        $cache->forever('illuminate:schedule:paused', true);

        $this->components->info('Scheduled task processing has been paused.');

        return 0;
    }
}
