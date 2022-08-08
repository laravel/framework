<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'schedule:work')]
class ScheduleWorkCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schedule:work';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'schedule:work';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the schedule worker';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->components->info('Running schedule tasks every minute.');

        [$lastExecutionStartedAt, $keyOfLastExecutionWithOutput, $executions] = [null, null, []];

        while (true) {
            usleep(100 * 1000);

            if (Carbon::now()->second === 0 &&
                ! Carbon::now()->startOfMinute()->equalTo($lastExecutionStartedAt)) {
                $executions[] = $execution = new Process([
                    PHP_BINARY,
                    defined('ARTISAN_BINARY') ? ARTISAN_BINARY : 'artisan',
                    'schedule:run',
                ]);

                $execution->start();

                $lastExecutionStartedAt = Carbon::now()->startOfMinute();
            }

            foreach ($executions as $key => $execution) {
                $output = $execution->getIncrementalOutput().
                          $execution->getIncrementalErrorOutput();

                $this->output->write(ltrim($output, "\n"));

                if (! $execution->isRunning()) {
                    unset($executions[$key]);
                }
            }
        }
    }
}
