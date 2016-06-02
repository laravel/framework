<?php

namespace Illuminate\Queue;

use Exception;
use Throwable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
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
     * The failed job provider implementation.
     *
     * @var \Illuminate\Queue\Failed\FailedJobProviderInterface
     */
    protected $failer;

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
     * @param  \Illuminate\Queue\Failed\FailedJobProviderInterface  $failer
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(QueueManager $manager,
                                FailedJobProviderInterface $failer = null,
                                Dispatcher $events = null)
    {
        $this->failer = $failer;
        $this->events = $events;
        $this->manager = $manager;
    }

    /**
     * Listen to the given queue in a loop.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  int     $delay
     * @param  int     $memory
     * @param  int     $timeout
     * @param  int     $sleep
     * @param  int     $maxTries
     * @return void
     */
    public function daemon($connectionName, $queue = null, $delay = 0, $memory = 128, $timeout = 60, $sleep = 3, $maxTries = 0)
    {
        $lastRestart = $this->getTimestampOfLastQueueRestart();

        while (true) {
            if ($this->daemonShouldRun()) {
                $this->runNextJobForDaemon(
                    $connectionName, $queue, $delay, $timeout, $sleep, $maxTries
                );
            } else {
                $this->sleep($sleep);
            }

            if ($this->memoryExceeded($memory) || $this->queueShouldRestart($lastRestart)) {
                $this->stop();
            }
        }
    }

    /**
     * Run the next job for the daemon worker.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  int  $delay
     * @param  int  $timeout
     * @param  int  $sleep
     * @param  int  $maxTries
     * @return void
     */
    protected function runNextJobForDaemon($connectionName, $queue, $delay, $timeout, $sleep, $maxTries)
    {
        if ($processId = pcntl_fork()) {
            $this->waitForChildProcess($processId, $timeout);
        } else {
            try {
                $this->runNextJob($connectionName, $queue, $delay, $sleep, $maxTries);
            } catch (Exception $e) {
                if ($this->exceptions) {
                    $this->exceptions->report($e);
                }
            } catch (Throwable $e) {
                if ($this->exceptions) {
                    $this->exceptions->report(new FatalThrowableError($e));
                }
            } finally {
                exit;
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
        return $this->manager->isDownForMaintenance()
                    ? false : $this->events->until('illuminate.queue.looping') !== false;
    }

    /**
     * Wait for the given child process to finish.
     *
     * @param  int  $processId
     * @param  int  $timeout
     * @return void
     */
    protected function waitForChildProcess($processId, $timeout)
    {
        declare(ticks=1) {
            pcntl_signal(SIGALRM, function () use ($processId) {
                posix_kill($processId, SIGKILL);

                if ($this->exceptions) {
                    $this->exceptions->report(new Exception('Daemon queue child process timed out.'));
                }
            }, true);

            pcntl_alarm($timeout);

            pcntl_waitpid($processId, $status);

            pcntl_alarm(0);
        }
    }

    /**
     * Process the next job on the queue.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  int     $delay
     * @param  int     $sleep
     * @param  int     $maxTries
     * @return void
     */
    public function runNextJob($connectionName, $queue = null, $delay = 0, $sleep = 3, $maxTries = 0)
    {
        try {
            $connection = $this->manager->connection($connectionName);

            $job = $this->getNextJob($connection, $queue);

            // If we're able to pull a job off of the stack, we will process it and then return
            // from this method. If there is no job on the queue, we will "sleep" the worker
            // for the specified number of seconds, then keep processing jobs after sleep.
            if (! is_null($job)) {
                return $this->process(
                    $this->manager->getName($connectionName), $job, $maxTries, $delay
                );
            }
        } catch (Exception $e) {
            if ($this->exceptions) {
                $this->exceptions->report($e);
            }
        }

        $this->sleep($sleep);
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
        if (is_null($queue)) {
            return $connection->pop();
        }

        foreach (explode(',', $queue) as $queue) {
            if (! is_null($job = $connection->pop($queue))) {
                return $job;
            }
        }
    }

    /**
     * Process a given job from the queue.
     *
     * @param  string  $connection
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  int  $maxTries
     * @param  int  $delay
     * @return void
     *
     * @throws \Throwable
     */
    public function process($connection, Job $job, $maxTries = 0, $delay = 0)
    {
        if ($maxTries > 0 && $job->attempts() > $maxTries) {
            return $this->logFailedJob($connection, $job);
        }

        try {
            $this->raiseBeforeJobEvent($connection, $job);

            // Here we will fire off the job and let it process. We will catch any exceptions so
            // they can be reported to the developers logs, etc. Once the job is finished the
            // proper events will be fired to let any listeners know this job has finished.
            $job->fire();

            $this->raiseAfterJobEvent($connection, $job);
        } catch (Exception $e) {
            $this->handleJobException($connection, $job, $delay, $e);
        } catch (Throwable $e) {
            $this->handleJobException($connection, $job, $delay, $e);
        }
    }

    /**
     * Handle an exception that occurred while the job was running.
     *
     * @param  string  $connection
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  int  $delay
     * @param  \Throwable  $e
     * @return void
     *
     * @throws \Throwable
     */
    protected function handleJobException($connection, Job $job, $delay, $e)
    {
        // If we catch an exception, we will attempt to release the job back onto the queue
        // so it is not lost entirely. This'll let the job be retried at a later time by
        // another listener (or this same one). We will re-throw this exception after.
        try {
            $this->raiseExceptionOccurredJobEvent(
                $connection, $job, $e
            );
        } finally {
            if (! $job->isDeleted()) {
                $job->release($delay);
            }
        }

        throw $e;
    }

    /**
     * Raise the before queue job event.
     *
     * @param  string  $connection
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseBeforeJobEvent($connection, Job $job)
    {
        if ($this->events) {
            $data = json_decode($job->getRawBody(), true);

            $this->events->fire(new Events\JobProcessing($connection, $job, $data));
        }
    }

    /**
     * Raise the after queue job event.
     *
     * @param  string  $connection
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseAfterJobEvent($connection, Job $job)
    {
        if ($this->events) {
            $data = json_decode($job->getRawBody(), true);

            $this->events->fire(new Events\JobProcessed($connection, $job, $data));
        }
    }

    /**
     * Raise the exception occurred queue job event.
     *
     * @param  string  $connection
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Throwable  $exception
     * @return void
     */
    protected function raiseExceptionOccurredJobEvent($connection, Job $job, $exception)
    {
        if ($this->events) {
            $data = json_decode($job->getRawBody(), true);

            $this->events->fire(new Events\JobExceptionOccurred($connection, $job, $data, $exception));
        }
    }

    /**
     * Log a failed job into storage.
     *
     * @param  string  $connection
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function logFailedJob($connection, Job $job)
    {
        if (! $this->failer) {
            return;
        }

        $this->failer->log($connection, $job->getQueue(), $job->getRawBody());

        $job->delete();

        $job->failed();

        $this->raiseFailedJobEvent($connection, $job);
    }

    /**
     * Raise the failed queue job event.
     *
     * @param  string  $connection
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseFailedJobEvent($connection, Job $job)
    {
        if ($this->events) {
            $data = json_decode($job->getRawBody(), true);

            $this->events->fire(new Events\JobFailed($connection, $job, $data));
        }
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
     * Set the exception handler instance.
     *
     * @param  \Illuminate\Contracts\Debug\ExceptionHandler  $handler
     * @return void
     */
    public function setExceptionHandler(ExceptionHandler $handler)
    {
        $this->exceptions = $handler;
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
