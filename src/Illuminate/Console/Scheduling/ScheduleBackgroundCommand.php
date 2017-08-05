<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;

class ScheduleBackgroundCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:background {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle running a scheduled background command';

    /**
     * Indicates whether the command should be shown in the Artisan command list.
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * The schedule instance.
     *
     * @var \Illuminate\Console\Scheduling\Schedule
     */
    protected $schedule;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $event = $this->findEventMutex()) {
            $this->error('No scheduled event could be found that matches the given id.');

            return;
        }

        $event->runCommandInForeground($this->laravel);
    }

    /**
     * Find the event that matches the id.
     *
     * @return \Illuminate\Console\Scheduling\Event|null
     */
    protected function findEventMutex()
    {
        return Arr::first($this->schedule->events(), (function ($value) {
            return $value->mutexName() === $this->argument('id');
        }));
    }
}
