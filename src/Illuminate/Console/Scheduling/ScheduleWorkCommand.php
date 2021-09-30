<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class ScheduleWorkCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schedule:work {--watch-env : Watch env changes}';

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

        [$lastExecutionStartedAt, $keyOfLastExecutionWithOutput, $executions] = [null, null, []];

        $environmentFile = $this->option('env')
            ? base_path('.env').'.'.$this->option('env')
            : base_path('.env');

        $hasEnvironment = $this->option('watch-env') && file_exists($environmentFile);

        while (true) {
            usleep(100 * 1000);

            if (Carbon::now()->second === 0 &&
                Carbon::now()->startOfMinute()->notEqualTo($lastExecutionStartedAt)) {
                if ($hasEnvironment) {
                    clearstatcache(false, $environmentFile);

                    $this->comment('Environment modified.');
                }

                $executions[] = $this->startProcess($hasEnvironment);

                $lastExecutionStartedAt = Carbon::now()->startOfMinute();
            }

            foreach ($executions as $key => $execution) {
                $output = trim($execution->getIncrementalOutput()).
                          trim($execution->getIncrementalErrorOutput());

                if (! empty($output)) {
                    if ($key !== $keyOfLastExecutionWithOutput) {
                        $this->info(PHP_EOL.'['.date('c').'] Execution #'.($key + 1).' output:');

                        $keyOfLastExecutionWithOutput = $key;
                    }

                    $this->output->writeln($output);
                }

                if (! $execution->isRunning()) {
                    unset($executions[$key]);
                }
            }
        }
    }

    /**
     * Start a new schedule process.
     *
     * @param  bool  $hasEnvironment
     * @return \Symfony\Component\Process\Process
     */
    protected function startProcess(bool $hasEnvironment): Process
    {
        $process = new Process($this->scheduleRunCommand(), null, collect($_ENV)->mapWithKeys(function ($value, $key) use ($hasEnvironment) {
            if (! $hasEnvironment) {
                return [$key => $value];
            }

            return in_array($key, [
                'APP_ENV',
                'LARAVEL_SAIL',
                'PHP_CLI_SERVER_WORKERS',
                'XDEBUG_CONFIG',
                'XDEBUG_MODE',
            ]) ? [$key => $value] : [$key => false];
        })->all());

        $process->start(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        return $process;
    }

    /**
     * Get the schedule command.
     *
     * @return array
     */
    protected function scheduleRunCommand()
    {
        return [
            (new PhpExecutableFinder)->find(false),
            'artisan',
            'schedule:run',
        ];
    }
}
