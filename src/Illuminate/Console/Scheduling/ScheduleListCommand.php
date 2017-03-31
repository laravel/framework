<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;

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
    protected $description = 'List all scheduled commands';

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
    public function fire()
    {
        $events = $this->schedule->events();

        if (count($events) === 0) {
            $this->info('No scheduled commands.');

            return;
        }

        foreach ($events as $event) {
            $command = $event->buildCommand();
            $desc = $event->getSummaryForDisplay();
            $expression = $event->getExpression();

            // show only command name
            $command = substr($command, 0, strpos($command, '>'));
            $command = trim(str_replace([PHP_BINARY, 'artisan', '\'', '"'], '', $command));

            // if description contain 2>&1, it is not really description
            if (strpos($desc, '2>&1') !== false) {
                $desc = '';
            }

            // move description to brackets
            if (! empty($command) && ! empty($desc)) {
                $desc = '('.$desc.')';
            }

            $this->line($expression."\t".trim($command.' '.$desc));
        }
    }
}
