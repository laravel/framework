<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\ProcessUtils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'schedule:work')]
class ScheduleWorkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:work
        {--run-output-file= : The file to direct <info>schedule:run</info> output to}
        {--whisper : Do not output message indicating that no jobs were ready to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the schedule worker';

    /**
     * The "schedule:run" executions that are currently running.
     *
     * @var \Symfony\Component\Process\Process[]
     */
    protected $executions = [];

    /**
     * Indicates if the schedule worker should exit.
     *
     * @var bool
     */
    protected $shouldQuit = false;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->components->info(
            'Running scheduled tasks.',
            $this->getLaravel()->environment('local') ? OutputInterface::VERBOSITY_NORMAL : OutputInterface::VERBOSITY_VERBOSE
        );

        $command = Application::formatCommandString('schedule:run');

        if ($this->option('whisper')) {
            $command .= ' --whisper';
        }

        if ($this->option('run-output-file')) {
            $command .= ' >> '.ProcessUtils::escapeArgument($this->option('run-output-file')).' 2>&1';
        }

        $this->listenForSignals();

        return $this->work($command);
    }

    /**
     * Run the schedule worker loop until it is signalled to stop.
     *
     * @param  string  $command
     * @return int
     */
    protected function work($command)
    {
        $lastExecutionStartedAt = Carbon::now()->subMinutes(10);

        while (true) {
            $this->sleep();

            // Once a stop signal has been received we stop scheduling new runs so
            // that the worker can stop any in-flight executions before exiting
            // which lets the current tasks execute instead of being stopped.
            if (! $this->shouldQuit &&
                Carbon::now()->second === 0 &&
                ! Carbon::now()->startOfMinute()->equalTo($lastExecutionStartedAt)) {
                $this->executions[] = $execution = Process::fromShellCommandline($command, base_path());

                $execution->start();

                $lastExecutionStartedAt = Carbon::now()->startOfMinute();
            }

            foreach ($this->executions as $key => $execution) {
                $output = $execution->getIncrementalOutput().
                    $execution->getIncrementalErrorOutput();

                $this->output->write(ltrim($output, "\n"));

                if (! $execution->isRunning()) {
                    unset($this->executions[$key]);
                }
            }

            if ($this->shouldQuit && empty($this->executions)) {
                return static::SUCCESS;
            }
        }
    }

    /**
     * Listen for the signals that should terminate the schedule worker.
     *
     * @return void
     */
    protected function listenForSignals()
    {
        $this->trap(fn () => [SIGINT, SIGTERM, SIGQUIT], function () {
            $this->shouldQuit = true;
        });
    }

    /**
     * Sleep for a short period before the next worker tick.
     *
     * @return void
     */
    protected function sleep()
    {
        usleep(100 * 1000);
    }
}
