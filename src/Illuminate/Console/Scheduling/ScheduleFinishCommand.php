<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;

class ScheduleFinishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:finish {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle the completion of a scheduled command';

    /**
     * Indicates whether the command should be shown in the Artisan command list.
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function handle(Schedule $schedule)
    {
        collect($schedule->events())->filter(function ($value) {
            return $value->mutexName() == $this->argument('id');
        })->each->callAfterCallbacks($this->laravel);
    }
}
