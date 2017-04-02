<?php

namespace Illuminate\Queue\Console;

use Carbon\Carbon;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\NoJobsLeft;
use Illuminate\Queue\Events\QueueExceptionOccurred;
use Illuminate\Queue\Events\Sleeping;
use Illuminate\Queue\Worker;
use Illuminate\Console\Command;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Symfony\Component\Console\Output\OutputInterface;

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
    protected $description = 'Start processing jobs on the queue as a daemon';

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

        $this->output->writeln("Using connection: {$connection}", OutputInterface::VERBOSITY_VERBOSE);
        $this->output->writeln("Using queue: {$queue}", OutputInterface::VERBOSITY_VERBOSE);

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
        $this->worker->setCache($this->laravel['cache']->driver());

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
            $this->option('tries'), $this->option('force')
        );
    }

    /**
     * Listen for the queue events in order to update the console output.
     *
     * @return void
     */
    protected function listenForEvents()
    {
        $this->laravel['events']->listen(JobProcessed::class, function ($event) {
            $this->output->writeln('<info>['.Carbon::now()->format('Y-m-d H:i:s').'] Processed:</info> '.$event->job->resolveName());
        });

        $this->laravel['events']->listen(JobProcessing::class, function ($event) {
            $this->writeOutput($event->job, false);
        });

        $this->laravel['events']->listen(NoJobsLeft::class, function () {
            $this->output->writeln('The queue seems to be empty.', OutputInterface::VERBOSITY_VERBOSE);
        });

        $this->laravel['events']->listen(Sleeping::class, function ($event) {
            $this->output->writeln("Sleeping for {$event->seconds} seconds.", OutputInterface::VERBOSITY_VERY_VERBOSE);
        });

        $this->laravel['events']->listen(QueueExceptionOccurred::class, function ($event) {
            $this->output->writeln($event->getMessage(), OutputInterface::VERBOSITY_VERY_VERBOSE);
            $this->output->writeln("Couldn't fetch a job from the queue. See the log file for more information.", OutputInterface::VERBOSITY_VERBOSE);
        });

        $this->laravel['events']->listen(JobFailed::class, function ($event) {
            $this->output->writeln('<error>['.Carbon::now()->format('Y-m-d H:i:s').'] Failed:</error> '.$event->job->resolveName());

            $this->logFailedJob($event);
        });
    }

    /**
     * Store a failed job event.
     *
     * @param  JobFailed  $event
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
