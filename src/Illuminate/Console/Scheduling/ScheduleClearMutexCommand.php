<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;

class ScheduleClearMutexCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schedule:clear-mutex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all the mutex files created by withoutOverlap()';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function handle(Schedule $schedule)
    {
        $mutexCleared = false;
        foreach ($schedule->events($this->laravel) as $event) {
            $command = explode(' ', $event->command)[2];
            if ($event->mutex->exists($event)) {
                $this->line('<info>Clearing mutex for:</info> '.$command);
                $event->mutex->forget($event);
                $mutexCleared = true;
            }
        }

        if (! $mutexCleared) {
            $this->info('No mutex to clear.');
        }
    }
}
