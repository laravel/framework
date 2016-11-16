<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class ScheduleRunCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schedule:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the scheduled commands';

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
     * Get the console command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            ['daemon', null, InputOption::VALUE_NONE, 'Run schedule in daemon mode'],
            ['interval', null, InputOption::VALUE_REQUIRED, 'Run every interval seconds', 60],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $daemon = $this->option('daemon');
        $interval = $this->option('interval');
        if (! is_numeric($interval)) {
            $this->error('Interval must be a positive number');
            exit(1);
        }
        while (true) {
            $start = time();
            $this->doScheduleDueEvents();
            if (! $daemon) {
                break;
            }

            //pause for a minimum of one second.
            $sleepTime = max(1, $interval - (time() - $start));
            sleep($sleepTime);
        }
    }

    /**
     * Trigger due events.
     *
     * @return void
     */
    protected function doScheduleDueEvents()
    {
        $events = $this->schedule->dueEvents($this->laravel);

        $eventsRan = 0;

        foreach ($events as $event) {
            if (! $event->filtersPass($this->laravel)) {
                continue;
            }

            $this->line('<info>Running scheduled command:</info> '.$event->getSummaryForDisplay());

            $event->run($this->laravel);

            ++$eventsRan;
        }

        if (count($events) === 0 || $eventsRan === 0) {
            $this->info('No scheduled commands are ready to run.');
        }
    }
}
