<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Cron\CronExpression;

class ScheduleOverviewCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:overview {--detailed : wheter to use the detailed view or not}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the schedule overview';

     /**
     * @var Schedule
     */
    protected $schedule;

    /**
     * Create a new command instance.
     *
     * @return void
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
        // Check for the argument "detailed"
        $detailedView = $this->option('detailed');

        // Map the cron events
        $events = array_map(function ($event) use ($detailedView) {

            $cron = CronExpression::factory($event->expression);

            // "Default" events
            $cronEvents = [
                'cron' => $event->expression,
                'command' => $this->fixupCommand($event->command),
                'previousRun' => $cron->getPreviousRunDate()->format('Y-m-d H:i:s'),
                'nextRun' => $cron->getNextRunDate()->format('Y-m-d H:i:s'),
                'timezone' => $event->timezone,
                'withoutOverlapping' => $event->withoutOverlapping ? 'Yes' : 'No',
            ];

            // If detailed view, insert the extra data
            if ($detailedView) {
                array_splice($cronEvents, 4, 0, [
                    'minute' => $cron->getExpression(CronExpression::MINUTE),
                    'hour' => $cron->getExpression(CronExpression::HOUR),
                    'dayOfMonth' => $cron->getExpression(CronExpression::DAY),
                    'month' => $cron->getExpression(CronExpression::MONTH),
                    'dayOfWeek' => $cron->getExpression(CronExpression::WEEKDAY),
                ]);
            }

            return $cronEvents;

        }, $this->schedule->events());

        // Setup table
        $table = [
            'Cron', 'Artisan command', 'Previous run', 'Next run', 'Timezone', 'Without overlapping?'
        ];

        // If detailed view, add the missing columns
        if ($detailedView) {
            array_splice($table, 4, 0, [
                'Minute', 'Hour', 'Day of month', 'Month', 'Day of week',
            ]);
        }

        $this->table($table, $events);
    }

   /**
     * Delete command partials ("php artisan")
     *
     * @param $command
     * @return string
     */
    private function fixupCommand($command)
    {
        $parts = explode(' ', $command);
        if (count($parts) > 2 && $parts[1] === "'artisan'") {
            array_shift($parts);
            array_shift($parts);
        }

        return implode(' ', $parts);
    }
}