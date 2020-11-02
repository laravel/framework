<?php

namespace Illuminate\Foundation\Bus;

use Closure;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Queue\SerializableClosure;

class PendingChain
{
    /**
     * The class name of the job being dispatched.
     *
     * @var mixed
     */
    public $job;

    /**
     * The jobs to be chained.
     *
     * @var array
     */
    public $chain;

    /**
     * The name of the connection the chain should be sent to.
     *
     * @var string|null
     */
    public $connection;

    /**
     * The name of the queue the chain should be sent to.
     *
     * @var string|null
     */
    public $queue;

    /**
     * The number of seconds before the chain should be made available.
     *
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    public $delay;

    /**
     * The callbacks to be executed on failure.
     *
     * @var array
     */
    public $catchCallbacks = [];

    /**
     * Create a new PendingChain instance.
     *
     * @param  mixed  $job
     * @param  array  $chain
     * @return void
     */
    public function __construct($job, $chain)
    {
        $this->job = $job;
        $this->chain = $chain;
    }

    /**
     * Set the desired connection for the job.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function onConnection($connection)
    {
        $this->connection = $connection;

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
        $this->queue = $queue;

        return $this;
    }

    /**
     * Set the desired delay for the chain.
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * Add a callback to be executed on job failure.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function catch($callback)
    {
        $this->catchCallbacks[] = $callback instanceof Closure
                        ? new SerializableClosure($callback)
                        : $callback;

        return $this;
    }

    /**
     * Get the "catch" callbacks that have been registered.
     *
     * @return array
     */
    public function catchCallbacks()
    {
        return $this->catchCallbacks ?? [];
    }

    /**
     * Dispatch the job with the given arguments.
     *
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public function dispatch()
    {
        if (is_string($this->job)) {
            $firstJob = new $this->job(...func_get_args());
        } elseif ($this->job instanceof Closure) {
            $firstJob = CallQueuedClosure::create($this->job);
        } else {
            $firstJob = $this->job;
        }

        if ($this->connection) {
            $firstJob->chainConnection = $this->connection;
            $firstJob->connection = $firstJob->connection ?: $this->connection;
        }

        if ($this->queue) {
            $firstJob->chainQueue = $this->queue;
            $firstJob->queue = $firstJob->queue ?: $this->queue;
        }

        if ($this->delay) {
            $firstJob->delay = ! is_null($firstJob->delay) ? $firstJob->delay : $this->delay;
        }

        $firstJob->chain($this->chain);
        $firstJob->chainCatchCallbacks = $this->catchCallbacks();

        return app(Dispatcher::class)->dispatch($firstJob);
    }
}
