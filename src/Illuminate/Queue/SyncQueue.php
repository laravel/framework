<?php

namespace Illuminate\Queue;

use Exception;
use Throwable;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class SyncQueue extends Queue implements QueueContract
{
    /**
     * Get the size of the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return 0;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     *
     * @throws \Exception|\Throwable
     */
    public function push($job, $data = '', $queue = null)
    {
        $queueJob = $this->resolveJob($this->createPayload($job, $data, $queue));

        try {
            $this->raiseBeforeJobEvent($queueJob);

            $queueJob->fire();

            $this->raiseAfterJobEvent($queueJob);
        } catch (Exception $e) {
            $this->handleSyncException($queueJob, $e);
        } catch (Throwable $e) {
            $this->handleSyncException($queueJob, new FatalThrowableError($e));
        }

        return 0;
    }

    /**
     * Handle an exception that occured while processing a job.
     *
     * @param  \Illuminate\Queue\Jobs\Job  $queueJob
     * @param  \Exception  $e
     * @return void
     */
    protected function handleSyncException($queueJob, $e)
    {
        $this->raiseExceptionOccurredJobEvent($queueJob, $e);

        $this->handleFailedJob($queueJob, $e);

        throw $e;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        //
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        //
    }

    /**
     * Resolve a Sync job instance.
     *
     * @param  string  $payload
     * @return \Illuminate\Queue\Jobs\SyncJob
     */
    protected function resolveJob($payload)
    {
        return new SyncJob($this->container, $payload);
    }

    /**
     * Raise the before queue job event.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseBeforeJobEvent(Job $job)
    {
        if ($this->container->bound('events')) {
            $this->container['events']->fire(new Events\JobProcessing('sync', $job));
        }
    }

    /**
     * Raise the after queue job event.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseAfterJobEvent(Job $job)
    {
        if ($this->container->bound('events')) {
            $this->container['events']->fire(new Events\JobProcessed('sync', $job));
        }
    }

    /**
     * Raise the exception occurred queue job event.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function raiseExceptionOccurredJobEvent(Job $job, $e)
    {
        if ($this->container->bound('events')) {
            $this->container['events']->fire(new Events\JobExceptionOccurred('sync', $job, $e));
        }
    }

    /**
     * Handle the failed job.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $e
     * @return array
     */
    protected function handleFailedJob(Job $job, $e)
    {
        $job->failed($e);

        $this->raiseFailedJobEvent($job, $e);
    }

    /**
     * Raise the failed queue job event.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function raiseFailedJobEvent(Job $job, $e)
    {
        if ($this->container->bound('events')) {
            $this->container['events']->fire(new Events\JobFailed('sync', $job, $e));
        }
    }
}
