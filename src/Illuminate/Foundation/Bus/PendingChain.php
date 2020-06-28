<?php

namespace Illuminate\Foundation\Bus;

use Closure;
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
     * Callbacks to be executed on failure.
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

        return (new PendingDispatch($firstJob))->chain($this->chain);
    }
}
