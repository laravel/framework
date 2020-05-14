<?php

namespace Illuminate\Bus;

use Carbon\CarbonImmutable;
use Illuminate\Collections\Arr;
use Illuminate\Collections\Collection;
use Illuminate\Contracts\Queue\Factory as QueueFactory;

class Batch
{
    /**
     * The queue factory implementation.
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    protected $queue;

    /**
     * The repository implementation.
     *
     * @var \Illuminate\Bus\BatchRepository
     */
    protected $repository;

    /**
     * The batch ID.
     *
     * @var string
     */
    public $id;

    /**
     * The total number of jobs that belong to the batch.
     *
     * @var int
     */
    public $totalJobs;

    /**
     * The total number of jobs that are still pending.
     *
     * @var int
     */
    public $pendingJobs;

    /**
     * The total number of jobs that have failed.
     *
     * @var int
     */
    public $failedJobs;

    /**
     * The batch options.
     *
     * @var array
     */
    public $options;

    /**
     * The date indicating when the batch was created.
     *
     * @var \Illuminate\Support\CarbonImmutable
     */
    public $createdAt;

    /**
     * The date indicating when the batch was cancelled.
     *
     * @var \Illuminate\Support\CarbonImmutable|null
     */
    public $cancelledAt;

    /**
     * The date indicating when the batch was finished.
     *
     * @var \Illuminate\Support\CarbonImmutable|null
     */
    public $finishedAt;

    /**
     * Create a new batch instance.
     *
     * @param  \Illuminate\Contracts\Bus\Dispatcher  $bus
     * @param  \Illuminate\Bus\BatchRepository  $repository
     * @param  string  $id
     * @param  string  $id
     * @param  int  $totalJobs
     * @param  int  $pendingJobs
     * @param  int  $failedJobs
     * @param  array  $options
     * @param  \Illuminate\Support\CarbonImmutable  $createdAt
     * @param  \Illuminate\Support\CarbonImmutable|null  $cancelledAt
     * @param  \Illuminate\Support\CarbonImmutable|null  $finishedAt
     * @return void
     */
    public function __construct(QueueFactory $queue,
                                BatchRepository $repository,
                                string $id,
                                int $totalJobs,
                                int $pendingJobs,
                                int $failedJobs,
                                array $options,
                                CarbonImmutable $createdAt,
                                ?CarbonImmutable $cancelledAt,
                                ?CarbonImmutable $finishedAt)
    {
        $this->queue = $queue;
        $this->repository = $repository;
        $this->id = $id;
        $this->totalJobs = $totalJobs;
        $this->pendingJobs = $pendingJobs;
        $this->failedJobs = $failedJobs;
        $this->options = $options;
        $this->createdAt = $createdAt;
        $this->cancelledAt = $cancelledAt;
        $this->finishedAt = $finishedAt;
    }

    /**
     * Get a fresh instance of the batch represented by this ID.
     *
     * @return self
     */
    public function fresh()
    {
        return $this->repository->find($this->id);
    }

    /**
     * Add additional jobs to the batch.
     *
     * @param  \Illuminate\Collections\Collection|array  $jobs
     * @param  string  $connection
     * @param  string  $queue
     * @return void
     */
    public function add($jobs, string $connection = null, string $queue = null)
    {
        $jobs = Collection::wrap($jobs);

        $jobs->each->withBatchId($this->id);

        $this->repository->transaction(function () use ($jobs, $connection, $queue) {
            $this->repository->incrementTotalJobs($this->id, count($jobs));

            $this->queue->connection($connection)->bulk(
                $jobs->all(),
                $data = '',
                $queue
            );
        });
    }

    /**
     * Get the total number of jobs that have been processed by the batch thus far.
     */
    public function processedJobs()
    {
        return $this->totalJobs - $this->pendingJobs;
    }

    /**
     * Get the percentage of jobs that have been processed.
     *
     * @return float
     */
    public function progress()
    {
        return round($this->processedJobs() / $this->totalJobs, 2);
    }

    /**
     * Record that a job within the batch finished successfully, executing any callbacks if necessary.
     *
     * @return void
     */
    public function recordSuccessfulJob()
    {
        if ($this->decrementPendingJobs() > 0) {
            return;
        }

        $this->repository->markAsFinished($this->id);

        if ($this->hasThenCallbacks()) {
            $batch = $this->fresh();

            collect($this->options['then'])->each->__invoke($batch);
        }
    }

    /**
     * Decrement the pending jobs for the batch.
     *
     * @return int
     */
    public function decrementPendingJobs()
    {
        return $this->repository->decrementPendingJobs($this->id);
    }

    /**
     * Determine if the batch has finished executing.
     *
     * @return bool
     */
    public function finished()
    {
        return ! is_null($this->finishedAt);
    }

    /**
     * Determine if the batch has "then" callbacks.
     *
     * @return bool
     */
    public function hasThenCallbacks()
    {
        return isset($this->options['then']) && ! empty($this->options['then']);
    }

    /**
     * Determine if the batch allows jobs to fail without cancelling the batch.
     *
     * @return bool
     */
    public function allowsFailures()
    {
        return Arr::get($this->options, 'allowFailures', false) === true;
    }

    /**
     * Determine if the batch has job failures.
     *
     * @return bool
     */
    public function hasFailures()
    {
        return $this->failedJobs > 0;
    }

    /**
     * Record that a job within the batch failed to finish successfully, executing any callbacks if necessary.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function recordFailedJob($e)
    {
        if ($this->incrementFailedJobs() > 1) {
            return;
        }

        if ($this->hasCatchCallbacks()) {
            $batch = $this->fresh();

            collect($this->options['catch'])->each->__invoke($batch, $e);
        }
    }

    /**
     * Increment the failed jobs for the batch.
     *
     * @return int
     */
    public function incrementFailedJobs()
    {
        return $this->repository->incrementFailedJobs($this->id);
    }

    /**
     * Determine if the batch has "catch" callbacks.
     *
     * @return bool
     */
    public function hasCatchCallbacks()
    {
        return isset($this->options['catch']) && ! empty($this->options['catch']);
    }

    /**
     * Cancel the batch.
     *
     * @return void
     */
    public function cancel()
    {
        $this->repository->cancel($this->id);
    }

    /**
     * Determine if the batch has been cancelled.
     *
     * @return bool
     */
    public function cancelled()
    {
        return ! is_null($this->cancelledAt);
    }
}
