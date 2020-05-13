<?php

namespace Illuminate\Bus;

use Carbon\CarbonImmutable;
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
     * The date indicating when the batch was cancelled.
     *
     * @var \Illuminate\Support\CarbonImmutable
     */
    public $cancelledAt;

    /**
     * The date indicating when the batch was created.
     *
     * @var \Illuminate\Support\CarbonImmutable
     */
    public $createdAt;

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
     * @param  \Illuminate\Support\CarbonImmutable  $cancelledAt
     * @param  \Illuminate\Support\CarbonImmutable  $createdAt
     * @param  \Illuminate\Support\CarbonImmutable  $updatedAt
     * @return void
     */
    public function __construct(QueueFactory $queue,
                                BatchRepository $repository,
                                string $id,
                                int $totalJobs,
                                int $pendingJobs,
                                int $failedJobs,
                                array $options,
                                CarbonImmutable $cancelledAt,
                                CarbonImmutable $createdAt)
    {
        $this->queue = $queue;
        $this->repository = $repository;
        $this->id = $id;
        $this->totalJobs = $totalJobs;
        $this->pendingJobs = $pendingJobs;
        $this->failedJobs = $failedJobs;
        $this->options = $options;
        $this->cancelledAt = $cancelledAt;
        $this->createdAt = $createdAt;
    }

    /**
     * Add additional jobs to the batch.
     *
     * @param  \Illuminate\Collections\Collection|array  $jobs
     * @return void
     */
    public function add($jobs)
    {
        $jobs = Collection::wrap($jobs);

        $jobs->each->withBatchId($this->id);

        $this->repository->transaction(function () use ($jobs) {
            $this->repository->incrementTotalJobs($this->id, count($jobs));

            $this->queue->bulk($jobs->all());
        });
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
}
