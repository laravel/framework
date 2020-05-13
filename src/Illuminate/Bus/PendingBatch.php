<?php

namespace Illuminate\Bus;

use Illuminate\Collections\Collection;
use Illuminate\Contracts\Container\Container;
use Throwable;

class PendingBatch
{
    /**
     * The jobs that belong to the batch.
     *
     * @var \Illuminate\Collections\Collection
     */
    public $jobs;

    /**
     * Create a new pending batch instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  \Illuminate\Collections\Collection  $jobs
     * @return void
     */
    public function __construct(Container $container, Collection $jobs)
    {
        $this->container = $container;
        $this->jobs = $jobs;
    }

    /**
     * Dispatch the batch.
     *
     * @return void
     */
    public function dispatch()
    {
        $repository = $this->container->make(BatchRepository::class);

        try {
            $batch = $repository->store($this);
        } catch (Throwable $e) {
            if (isset($batch)) {
                $repository->delete($batch->id);
            }

            throw $e;
        }

        return $batch;
    }
}
