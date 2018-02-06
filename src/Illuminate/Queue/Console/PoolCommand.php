<?php

namespace Illuminate\Queue\Console;

use Illuminate\Queue\Pool;
use Illuminate\Console\Command;
use Illuminate\Queue\PoolOption;

class PoolCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:pool
                            {connection? : The name of the queue connection to work}
                            {--workers= : The amount of the workers to start}
                            {--queue= : The names of the queues to work}
                            {--delay=0 : The number of seconds to delay failed jobs}
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
    protected $description = 'Start a pool of workers to process the queue';

    /**
     * The queue pool instance.
     *
     * @var \Illuminate\Queue\Pool
     */
    protected $pool;

    /**
     * Create a new queue work command.
     *
     * @param  \Illuminate\Queue\Pool  $pool
     * @return void
     */
    public function __construct(Pool $pool)
    {
        parent::__construct();

        $this->setOutputHandler($this->pool = $pool);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
        $queue = $this->getQueue(
            $connection = $this->input->getArgument('connection')
        );

        $this->pool->start(
            $connection, $queue, $this->gatherOptions()
        );
    }

    /**
     * Get the name of the queue connection to listen on.
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        $connection = $connection ?: $this->laravel['config']['queue.default'];

        return $this->input->getOption('queue') ?: $this->laravel['config']->get(
            "queue.connections.{$connection}.queue", 'default'
        );
    }

    /**
     * Get the listener options for the command.
     *
     * @return \Illuminate\Queue\PoolOption
     */
    protected function gatherOptions()
    {
        return new PoolOption(
            $this->option('workers'), $this->option('env'),
            $this->option('delay'), $this->option('memory'),
            $this->option('timeout'), $this->option('sleep'),
            $this->option('tries'), $this->option('force')
        );
    }

    /**
     * Set the options on the queue listener.
     *
     * @param  \Illuminate\Queue\Pool  $pool
     * @return void
     */
    protected function setOutputHandler(Pool $pool)
    {
        $pool->setOutputHandler(function ($type, $line) {
            $this->output->write($line);
        });
    }
}
