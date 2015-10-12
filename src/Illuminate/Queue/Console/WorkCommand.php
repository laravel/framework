<?php

namespace Illuminate\Queue\Console;

use Illuminate\Queue\Worker;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Job;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

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
    protected $description = 'Process the next job on a queue';

    /**
     * The queue worker instance.
     *
     * @var \Illuminate\Queue\Worker
     */
    protected $worker;

    /**
     * Create a new queue listen command.
     *
     * @param  \Illuminate\Queue\Worker  $worker
     * @return void
     */
    public function __construct(Worker $worker)
    {
        parent::__construct();

        $this->worker = $worker;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if ($this->downForMaintenance() && ! $this->option('daemon')) {
            return $this->worker->sleep($this->option('sleep'));
        }

        $queue = $this->option('queue');

        $delay = $this->option('delay');

        // The memory limit is the amount of memory we will allow the script to occupy
        // before killing it and letting a process manager restart it for us, which
        // is to protect us against any memory leaks that will be in the scripts.
        $memory = $this->option('memory');

        $connection = $this->argument('connection');

        $response = $this->runWorker(
            $connection, $queue, $delay, $memory, $this->option('daemon')
        );

        // If a job was fired by the worker, we'll write the output out to the console
        // so that the developer can watch live while the queue runs in the console
        // window, which will also of get logged if stdout is logged out to disk.
        if (! is_null($response['job'])) {
            $this->writeOutput($response['job'], $response['failed']);
        }
    }

    /**
     * Run the worker instance.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  int  $delay
     * @param  int  $memory
     * @param  bool  $daemon
     * @return array
     */
    protected function runWorker($connection, $queue, $delay, $memory, $daemon = false)
    {
        if ($daemon) {
            $this->worker->setCache($this->laravel['cache']->driver());

            $this->worker->setDaemonExceptionHandler(
                $this->laravel['Illuminate\Contracts\Debug\ExceptionHandler']
            );

            return $this->worker->daemon(
                $connection, $queue, $delay, $memory,
                $this->option('sleep'), $this->option('tries')
            );
        }

        return $this->worker->pop(
            $connection, $queue, $delay,
            $this->option('sleep'), $this->option('tries')
        );
    }

    /**
     * Write the status output for the queue worker.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  bool  $failed
     * @return void
     */
    protected function writeOutput(Job $job, $failed)
    {
        if ($failed) {
            $this->output->writeln('<error>Failed:</error> '.$job->getName());
        } else {
            $this->output->writeln('<info>Processed:</info> '.$job->getName());
        }
    }

    /**
     * Determine if the worker should run in maintenance mode.
     *
     * @return bool
     */
    protected function downForMaintenance()
    {
        if ($this->option('force')) {
            return false;
        }

        return $this->laravel->isDownForMaintenance();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['connection', InputArgument::OPTIONAL, 'The name of connection', null],
        ];
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

            ['daemon', null, InputOption::VALUE_NONE, 'Run the worker in daemon mode'],

            ['delay', null, InputOption::VALUE_OPTIONAL, 'Amount of time to delay failed jobs', 0],

            ['force', null, InputOption::VALUE_NONE, 'Force the worker to run even in maintenance mode'],

            ['memory', null, InputOption::VALUE_OPTIONAL, 'The memory limit in megabytes', 128],

            ['sleep', null, InputOption::VALUE_OPTIONAL, 'Number of seconds to sleep when no job is available', 3],

            ['tries', null, InputOption::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 0],
        ];
    }
}
