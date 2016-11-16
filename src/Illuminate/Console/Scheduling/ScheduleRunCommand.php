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
            ['loop', 'l', InputOption::VALUE_NONE, 'Don\'t terminate. Run forever'],
            ['interval', null, InputOption::VALUE_REQUIRED, 'Run every interval seconds. Default: 60', 60],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $loop = $this->option('loop');
        $interval = $this->option('interval');
        if (! is_int($interval) && $interval <= 0) {
            $this->error('Interval must be an 1 <= integer <= 86400');
            exit(1);
        }
        while (true) {
            $start = time();
            $this->doScheduleDueEvents();
            if (! $loop) {
                break;
            }

            //pause for a minimum of one second.
            $sleep_time = max(1, $interval - (time() - $start));
            sleep($sleep_time);
        }
    }

    /**
     * Trigger due events.
     */
    private function doScheduleDueEvents()
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
