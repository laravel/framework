<?php

namespace Illuminate\Bus;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Bus\Dispatcher;

class BatchFactory
{
    /**
     * The bus implementation.
     *
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $bus;

    /**
     * Create a new batch factory instance.
     *
     * @param  \Illuminate\Contracts\Bus\Dispatcher  $bus
     * @return void
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Create a new batch instance.
     *
     * @param  \Illuminate\Bus\BatchRepository  $repository
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
    public function make(BatchRepository $repository,
                         string $id,
                         int $totalJobs,
                         int $pendingJobs,
                         int $failedJobs,
                         array $options,
                         CarbonImmutable $cancelledAt,
                         CarbonImmutable $createdAt) {
        return new Batch($this->bus, $repository, $id, $totalJobs, $pendingJobs, $failedJobs, $options, $cancelledAt, $createdAt);
    }
}
