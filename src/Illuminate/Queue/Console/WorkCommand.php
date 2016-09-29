<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class WorkCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:work 
                            {connection? : The name of connection}
                            {--queue= : The queue to listen on}
                            {--daemon : Run the worker in daemon mode (Deprecated)}
                            {--once : Only process the next job on the queue}
                            {--delay=0 : Amount of time to delay failed jobs}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=0 : Number of times to attempt a job before logging it failed}';

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
}
