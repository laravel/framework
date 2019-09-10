<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Carbon;

class WorkCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:work
                            {connection? : The name of the queue connection to work}
                            {--queue= : The names of the queues to work}
                            {--daemon : Run the worker in daemon mode (Deprecated)}
                            {--once : Only process the next job on the queue}
                            {--stop-when-empty : Stop when the queue is empty}
                            {--delay=0 : The number of seconds to delay failed jobs}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=1 : Number of times to attempt a job before logging it failed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start processing jobs on the queue as a daemon';

    /**
     * The queue worker instance.
     *
     * @var \Illuminate\Queue\Worker
     */
    protected $worker;

    /**
     * The cache store implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new queue work command.
     *
     * @param  \Illuminate\Queue\Worker  $worker
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function __construct(Worker $worker, Cache $cache)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->worker = $worker;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->downForMaintenance() && $this->option('once')) {
            return $this->worker->sleep($this->option('sleep'));
        }

        // We'll listen to the processed and failed events so we can write information
        // to the console as jobs are processed, which will let the developer watch
        // which jobs are coming through a queue and be informed on its progress.
        $this->listenForEvents();

        $connection = $this->argument('connection')
                        ?: $this->laravel['config']['queue.default'];

        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
        $queue = $this->getQueue($connection);

        $this->runWorker(
            $connection, $queue
        );
    }

    /**
     * Run the worker instance.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @return array
     */
    protected function runWorker($connection, $queue)
    {
        $this->worker->setCache($this->cache);

        return $this->worker->{$this->option('once') ? 'runNextJob' : 'daemon'}(
            $connection, $queue, $this->gatherWorkerOptions()
        );
    }

    /**
     * Gather all of the queue worker options as a single object.
     *
     * @return \Illuminate\Queue\WorkerOptions
     */
    protected function gatherWorkerOptions()
    {
        return new WorkerOptions(
            $this->option('delay'), $this->option('memory'),
            $this->option('timeout'), $this->option('sleep'),
            $this->option('tries'), $this->option('force'),
            $this->option('stop-when-empty')
        );
    }

    /**
     * Listen for the queue events in order to update the console output.
     *
     * @return void
     */
    protected function listenForEvents()
    {
        $this->laravel['events']->listen(JobProcessing::class, function ($event) {
            $this->writeOutput($event->job, 'starting');
        });

        $this->laravel['events']->listen(JobProcessed::class, function ($event) {
            $this->writeOutput($event->job, 'success');
        });

        $this->laravel['events']->listen(JobFailed::class, function ($event) {
            $this->writeOutput($event->job, 'failed');

            $this->logFailedJob($event);
        });
    }

    /**
     * Write the status output for the queue worker.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  string $status
     * @return void
     */
    protected function writeOutput(Job $job, $status)
    {
        switch ($status) {
            case 'starting':
                return $this->writeStatus($job, 'Processing', 'comment');
            case 'success':
                return $this->writeStatus($job, 'Processed', 'info');
            case 'failed':
                return $this->writeStatus($job, 'Failed', 'error');
        }
    }

    /**
     * Format the status output for the queue worker.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  string  $status
     * @param  string  $type
     * @return void
     */
    protected function writeStatus(Job $job, $status, $type)
    {
        $this->output->writeln(sprintf(
            "<{$type}>[%s][%s] %s</{$type}> %s",
            Carbon::now()->format('Y-m-d H:i:s'),
            $job->getJobId(),
            str_pad("{$status}:", 11), $job->resolveName()
        ));
    }

    /**
     * Store a failed job event.
     *
     * @param  \Illuminate\Queue\Events\JobFailed  $event
     * @return void
     */
    protected function logFailedJob(JobFailed $event)
    {
        $this->laravel['queue.failer']->log(
            $event->connectionName, $event->job->getQueue(),
            $event->job->getRawBody(), $event->exception
        );
    }

    /**
     * Get the queue name for the worker.
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        return $this->option('queue') ?: $this->laravel['config']->get(
            "queue.connections.{$connection}.queue", 'default'
        );
    }

    /**
     * Determine if the worker should run in maintenance mode.
     *
     * @return bool
     */
    protected function downForMaintenance()
    {
        return $this->option('force') ? false : $this->laravel->isDownForMaintenance();
    }
}
