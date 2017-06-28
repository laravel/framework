<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;

class ScheduleListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List scheduled commands and their next run date';

    /**
     * The schedule instance.
     *
     * @var \Illuminate\Console\Scheduling\Schedule
     */
    protected $schedule;

    /**
     * Create a new command instance.
     *
     * @param Schedule $schedule
     */
    public function __construct(Schedule $schedule)
    {
        parent::__construct();
        $this->schedule = $schedule;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $order = 1;
        foreach ($this->schedule->events() as $event) {
            if (! $event->filtersPass($this->laravel)) {
                continue;
            }

            $next = $event->nextRunDate();

            $nextInfo = "{$order}. ".$event->getSummaryForDisplay().' will run next time at '.$next->toDateTimeString();

            $this->info($nextInfo);
            $order++;
        }
    }
}
