<?php

namespace Illuminate\Foundation\Bus;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Bus\Dispatcher;

class PendingDispatch
{
    /**
     * The list of jobs pending dispatch.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $jobs;

    /**
     * The chain conductor instance.
     *
     * @var \Illuminate\Foundation\Bus\ChainConductor
     */
    protected $chainConductor;

    /**
     * Create a new pending job dispatch.
     *
     * @param  mixed  $job
     * @return void
     */
    public function __construct($jobs, ChainConductor $chainConductor)
    {
        $this->jobs = Collection::wrap($jobs);
        $this->chainConductor = $chainConductor;
    }

    /**
     * Set the desired connection for the job.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function onConnection($connection)
    {
        $this->jobs->each->onConnection($connection);

        return $this;
    }

    /**
     * Set the desired queue for the job.
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function onQueue($queue)
    {
        $this->jobs->each->onQueue($queue);

        return $this;
    }

    /**
     * Set the desired delay for the job.
     *
     * @param  \DateTime|int|null  $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->jobs->each->delay($delay);

        return $this;
    }

    /**
     * Set up a chain of jobs.
     *
     * @param  object|array  ...$jobs
     * @return void
     */
    public function chain(...$jobs)
    {
        // Here we transform the chain into a multi-dimensional collection.
        // The outer collection holds the links in the chain, the inner
        // collections are the jobs that should be run concurrently.
        $chain = Collection::make($jobs)->map(function ($jobs) {
            return Collection::wrap($jobs);
        })->prepend($this->jobs);

        $this->chainConductor->createChain($chain);
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        $dispactcher = app(Dispatcher::class);

        foreach ($this->jobs as $job) {
            $dispactcher->dispatch($job);
        }
    }
}
