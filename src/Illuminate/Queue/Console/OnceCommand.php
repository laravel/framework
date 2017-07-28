<?php

namespace Illuminate\Queue\Console;

class OnceCommand extends WorkCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:once
                            {connection? : The name of the queue connection to work}
                            {--once=1 : Only process the next job on the queue}
                            {--queue= : The names of the queues to work}
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
    protected $description = 'Process a single job on the queue';
}
