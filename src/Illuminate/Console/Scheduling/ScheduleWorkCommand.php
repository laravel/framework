<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ScheduleWorkCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schedule:work';

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
        $this->info('Schedule worker started successfully.');

        $lastExecutionStartedAt = null;

        $keyOfLastExecutionWithOutput = null;

        $executions = [];

        while (true) {
            if (Carbon::now()->second === 0 && ! Carbon::now()->startOfMinute()->equalTo($lastExecutionStartedAt)) {
                $execution = new Process([PHP_BINARY, 'artisan', 'schedule:run']);
                $execution->start();
                $executions[] = $execution;
                $lastExecutionStartedAt = Carbon::now()->startOfMinute();
            }

            foreach ($executions as $key => $execution) {
                $incrementalOutput = trim($execution->getIncrementalOutput());

                if (Str::length($incrementalOutput) > 0) {
                    if ($key !== $keyOfLastExecutionWithOutput) {
                        $this->info(PHP_EOL.'Execution #'.($key + 1).' output:');
                        $keyOfLastExecutionWithOutput = $key;
                    }

                    $this->warn($incrementalOutput);
                }

                $incrementalErrorOutput = trim($execution->getIncrementalErrorOutput());

                if (Str::length($incrementalErrorOutput) > 0) {
                    if ($key !== $keyOfLastExecutionWithOutput) {
                        $this->info(PHP_EOL.'Execution #'.($key + 1).' output:');
                        $keyOfLastExecutionWithOutput = $key;
                    }

                    $this->error(trim($incrementalErrorOutput));
                }

                if (! $execution->isRunning()) {
                    unset($executions[$key]);
                }
            }
        }
    }
}
