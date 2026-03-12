<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:resume')]
class ScheduleResumeCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resume the scheduled tasks';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Cache $cache)
    {
        $cache->forget('illuminate:schedule:paused');

        $this->components->info('Scheduled tasks resumed.');

        return 0;
    }
}
