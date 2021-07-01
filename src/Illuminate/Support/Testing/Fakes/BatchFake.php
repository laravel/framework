<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Bus\Batch;
use Illuminate\Support\Arr;

class BatchFake extends Batch
{
    /**
     * The fake bus instance.
     *
     * @var \Illuminate\Support\Testing\Fakes\BusFake
     */
    protected $bus;

    /**
     * Add additional jobs to the batch.
     *
     * @param  \Illuminate\Support\Collection|array  $jobs
     * @return self
     */
    public function add($jobs)
    {
        $jobs = Arr::wrap($jobs);

        $this->totalJobs += count($jobs);
        $this->pendingJobs += count($jobs);
        $this->finishedAt = null;

        return $this->bus->recordBatch($this);
    }

    /**
     * Set the fake bus instance.
     *
     * @param  \Illuminate\Support\Testing\Fakes\BusFake  $bus
     */
    public function setBus(BusFake $bus)
    {
        $this->bus = $bus;
    }
}
