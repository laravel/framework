<?php

namespace Illuminate\Queue;

use Exception;
use Throwable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class Worker
{
    /**
     * The queue manager instance.
     *
     * @var \Illuminate\Queue\QueueManager
     */
    protected $manager;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The cache repository implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The exception handler instance.
     *
     * @var \Illuminate\Foundation\Exceptions\Handler
     */
    protected $exceptions;

    /**
     * Create a new queue worker.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  \Illuminate\Contracts\Debug\ExceptionHandler  $exceptions
     * @return void
     */
    public function __construct(QueueManager $manager,
                                Dispatcher $events,
                                ExceptionHandler $exceptions)
    {
        $this->events = $events;
        $this->manager = $manager;
        $this->exceptions = $exceptions;
    }

    /**
     * Listen to the given queue in a loop.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     */
    public function daemon($connectionName, $queue, WorkerOptions $options)
    {
        $lastRestart = $this->getTimestampOfLastQueueRestart();

        while (true) {
            if ($this->daemonShouldRun()) {
                $this->runNextJob($connectionName, $queue, $options);
            } else {
                $this->sleep($options->sleep);
            }

            if ($this->memoryExceeded($options->memory) ||
                $this->queueShouldRestart($lastRestart)) {
                $this->stop();
            }
        }
    }

    /**
     * Determine if the daemon should process on this iteration.
     *
     * @return bool
     */
    protected function daemonShouldRun()
    {
        if ($this->manager->isDownForMaintenance() ||
            $this->events->until('illuminate.queue.looping') === false) {
            // If the application is down for maintenance or doesn't want the queues to run
            // we will sleep for one second just in case the developer has it set to not
            // sleep at all. This just prevents CPU from maxing out in this situation.
            $this->sleep(1);

            return false;
        }

        return true;
    }

    /**
     * Process the next job on the queue.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     */
    public function runNextJob($connectionName, $queue, WorkerOptions $options)
    {
        try {
            $job = $this->getNextJob(
                $this->manager->connection($connectionName), $queue
            );

            // If we're able to pull a job off of the stack, we will process it and then return
            // from this method. If there is no job on the queue, we will "sleep" the worker
            // for the specified number of seconds, then keep processing jobs after sleep.
            if ($job) {
                return $this->process(
                    $connectionName, $job, $options
                );
            }
        } catch (Exception $e) {
            $this->exceptions->report($e);
        } catch (Throwable $e) {
            $this->exceptions->report(new FatalThrowableError($e));
        }

        $this->sleep($options->sleep);
    }

    /**
     * Get the next job from the queue connection.
     *
     * @param  \Illuminate\Contracts\Queue\Queue  $connection
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    protected function getNextJob($connection, $queue)
    {
        foreach (explode(',', $queue) as $queue) {
            if (! is_null($job = $connection->pop($queue))) {
                return $job;
            }
        }
    }

    /**
     * Process a given job from the queue.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     *
     * @throws \Throwable
     */
    public function process($connectionName, $job, WorkerOptions $options)
    {
        try {
            $this->raiseBeforeJobEvent($connectionName, $job);

            $this->markJobAsFailedIfAlreadyExceedsMaxAttempts(
                $connectionName, $job, (int) $options->maxTries
            );

            // Here we will fire off the job and let it process. We will catch any exceptions so
            // they can be reported to the developers logs, etc. Once the job is finished the
            // proper events will be fired to let any listeners know this job has finished.
            $job->fire();

            $this->raiseAfterJobEvent($connectionName, $job);
        } catch (Exception $e) {
            $this->handleJobException($connectionName, $job, $options, $e);
        } catch (Throwable $e) {
            $this->handleJobException(
                $connectionName, $job, $options, new FatalThrowableError($e)
            );
        }
    }

    /**
     * Handle an exception that occurred while the job was running.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    protected function handleJobException($connectionName, $job, WorkerOptions $options, $e)
    {
        // If we catch an exception, we will attempt to release the job back onto the queue
        // so it is not lost entirely. This'll let the job be retried at a later time by
        // another listener (or this same one). We will re-throw this exception after.
        try {
            $this->markJobAsFailedIfHasExceededMaxAttempts(
                $connectionName, $job, (int) $options->maxTries, $e
            );

            $this->raiseExceptionOccurredJobEvent(
                $connectionName, $job, $e
            );
        } finally {
            if (! $job->isDeleted()) {
                $job->release($options->delay);
            }
        }

        throw $e;
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     *
     * This will likely be because the job previously exceeded a timeout.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  int  $maxTries
     * @return void
     */
    protected function markJobAsFailedIfAlreadyExceedsMaxAttempts($connectionName, $job, $maxTries)
    {
        if ($maxTries === 0 || $job->attempts() <= $maxTries) {
            return;
        }

        $e = new MaxAttemptsExceededException(
            'A queued job has been attempted too many times. The job may have previously timed out.'
        );

        $this->failJob($connectionName, $job, $e);

        throw $e;
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  int  $maxTries
     * @param  \Exception  $e
     * @return void
     */
    protected function markJobAsFailedIfHasExceededMaxAttempts(
        $connectionName, $job, $maxTries, $e
    ) {
        if ($maxTries === 0 || $job->attempts() < $maxTries) {
            return;
        }

        $this->failJob($connectionName, $job, $e);
    }

    /**
     * Mark the given job as failed and raise the relevant event.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function failJob($connectionName, $job, $e)
    {
        if ($job->isDeleted()) {
            return;
        }

        // If the job has failed, we will delete it, call the "failed" method and then call
        // an event indicating the job has failed so it can be logged if needed. This is
        // to allow every developer to better keep monitor of their failed queue jobs.
        $job->delete();

        $job->failed($e);

        $this->raiseFailedJobEvent($connectionName, $job, $e);
    }

    /**
     * Raise the before queue job event.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseBeforeJobEvent($connectionName, $job)
    {
        $this->events->fire(new Events\JobProcessing(
            $connectionName, $job
        ));
    }

    /**
     * Raise the after queue job event.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseAfterJobEvent($connectionName, $job)
    {
        $this->events->fire(new Events\JobProcessed(
            $connectionName, $job
        ));
    }

    /**
     * Raise the exception occurred queue job event.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function raiseExceptionOccurredJobEvent($connectionName, $job, $e)
    {
        $this->events->fire(new Events\JobExceptionOccurred(
            $connectionName, $job, $e
        ));
    }

    /**
     * Raise the failed queue job event.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function raiseFailedJobEvent($connectionName, $job, $e)
    {
        $this->events->fire(new Events\JobFailed(
            $connectionName, $job, $e
        ));
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param  int   $memoryLimit
     * @return bool
     */
    public function memoryExceeded($memoryLimit)
    {
        return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @return void
     */
    public function stop()
    {
        $this->events->fire(new Events\WorkerStopping);

        die;
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param  int   $seconds
     * @return void
     */
    public function sleep($seconds)
    {
        sleep($seconds);
    }

    /**
     * Get the last queue restart timestamp, or null.
     *
     * @return int|null
     */
    protected function getTimestampOfLastQueueRestart()
    {
        if ($this->cache) {
            return $this->cache->get('illuminate:queue:restart');
        }
    }

    /**
     * Determine if the queue worker should restart.
     *
     * @param  int|null  $lastRestart
     * @return bool
     */
    protected function queueShouldRestart($lastRestart)
    {
        return $this->getTimestampOfLastQueueRestart() != $lastRestart;
    }

    /**
     * Set the cache repository implementation.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function setCache(CacheContract $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get the queue manager instance.
     *
     * @return \Illuminate\Queue\QueueManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Set the queue manager instance.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    public function setManager(QueueManager $manager)
    {
        $this->manager = $manager;
    }
}
