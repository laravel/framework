<?php

namespace Illuminate\Console\Scheduling;

use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ScheduleListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schedule:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the scheduled commands';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     * @throws \Exception
     */
    public function handle(Schedule $schedule)
    {
        foreach ($schedule->events() as $event) {
            $rows[] = [
                $event->command,
                $event->expression,
                $event->description,
                (new CronExpression($event->expression))->getPreviousRunDate(Carbon::now()),
                (new CronExpression($event->expression))->getNextRunDate(Carbon::now()),
            ];
        }

        $this->table([
            'Command',
            'Interval',
            'Description',
            'Last Run',
            'Next Due',
        ], $rows ?? []);
    }
}
