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
     * The interval (in seconds) the scheduler is run daemon mode.
     *
     * @var int
     */
    const SCHEDULER_INTERVAL = 60;

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
            ['daemon', null, InputOption::VALUE_NONE, 'Run schedule in daemon mode. You must ensure a single schedule:run never exceeds 60 seconds'],
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

        while (true) {
            $start = time();
            $this->doScheduleDueEvents();
            if (! $daemon) {
                break;
            }

            $sleepTime = max(0, self::SCHEDULER_INTERVAL - (time() - $start));
            if (0 == $sleepTime) {
                $this->error(sprintf('schedule:run did not finish in %d seconds. Some events might have been skipped.',
                    self::SCHEDULER_INTERVAL));
            }
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
