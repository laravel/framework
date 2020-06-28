<?php

namespace Illuminate\Foundation\Bus;

use Closure;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Queue\SerializableClosure;

class PendingChain
{
    /**
     * The name of the connection the chain should be sent to.
     *
     * @var string|null
     */
    public $connection;

    /**
     * The jobs to be chained.
     *
     * @var array
     */
    public $chain;

    /**
     * Callbacks to be executed on failure.
     *
     * @var array
     */
    public $catchCallbacks = [];

    /**
     * The name of the queue the chain should be sent to.
     *
     * @var string|null
     */
    public $queue;

    /**
     * The class name of the job being dispatched.
     *
     * @var mixed
     */
    public $job;

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
     * Add a callback to be executed on failure..
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function catch(Closure $callback)
    {
        $this->catchCallbacks[] = new SerializableClosure($callback);

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
     * Set the desired connection for the job.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function onConnection($connection)
    {
        $this->job->onConnection($connection);

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
        $this->job->onQueue($queue);

        return $this;
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

        $firstJob->chainCatchCallbacks = $this->catchCallbacks();
        $firstJob->allOnQueue($this->queue);
        $firstJob->allOnConnection($this->connection);

        return (new PendingDispatch($firstJob))->chain($this->chain);
    }
}
