<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputOption;

class WorkCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:work';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start processing jobs from the queue';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $process = $this->newProxyProcess();

        exit($process->run(function ($type, $line) {
            if (trim($line) !== '.') {
                $this->output->write($line);
            }
        }));
    }

    /**
     * Get a new proxy process to the daemon command.
     *
     * @return Process
     */
    protected function newProxyProcess()
    {
        $_SERVER['argv'][1] = 'queue:daemon';

        return (new Process(PHP_BINARY.' '.implode(' ', $_SERVER['argv']), getcwd()))
                    ->setTimeout(null)
                    ->setIdleTimeout($this->option('timeout') + $this->option('sleep'));
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['queue', null, InputOption::VALUE_OPTIONAL, 'The queue to listen on'],

            ['daemon', null, InputOption::VALUE_NONE, 'Run the worker in daemon mode (Deprecated)'],

            ['once', null, InputOption::VALUE_NONE, 'Only process the next job on the queue'],

            ['delay', null, InputOption::VALUE_OPTIONAL, 'Amount of time to delay failed jobs', 0],

            ['force', null, InputOption::VALUE_NONE, 'Force the worker to run even in maintenance mode'],

            ['memory', null, InputOption::VALUE_OPTIONAL, 'The memory limit in megabytes', 128],

            ['sleep', null, InputOption::VALUE_OPTIONAL, 'Number of seconds to sleep when no job is available', 3],

            ['timeout', null, InputOption::VALUE_OPTIONAL, 'The number of seconds a child process can run', 60],

            ['tries', null, InputOption::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 0],
        ];
    }
}
