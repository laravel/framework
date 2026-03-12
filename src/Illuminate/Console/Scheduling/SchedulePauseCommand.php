<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
use Illuminate\Console\Events\SchedulePaused;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
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
    public function handle(Cache $cache, Dispatcher $dispatcher)
    {
        $cache->forever('illuminate:schedule:paused', true);

        $dispatcher->dispatch(new SchedulePaused);

        $this->components->info('Scheduled task processing has been paused.');

        return 0;
    }
}
