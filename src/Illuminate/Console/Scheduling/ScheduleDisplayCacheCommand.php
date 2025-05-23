<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:display-cache')]
class ScheduleDisplayCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schedule:display-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the cached mutex files created by scheduler';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function handle(Schedule $schedule)
    {
        $existingMutex = false;

        foreach ($schedule->events($this->laravel) as $event) {
            if ($event->mutex->exists($event)) {
                $this->components->info(sprintf('Existing mutex for [%s]', $event->command));

                $existingMutex = true;
            }
        }

        if (! $existingMutex) {
            $this->components->info('No mutex files were found.');
        }
    }
}
