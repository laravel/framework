<?php

namespace Illuminate\Queue;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;

use function Illuminate\Support\enum_value;

trait InteractsWithRemoteWorker
{
    /**
     * The callbacks to run once the remote worker replies.
     *
     * @var array{then: array, catch: array}
     */
    protected $remoteCallbacks = ['then' => [], 'catch' => []];

    /**
     * The queue the remote worker should send its reply to.
     *
     * @var string|null
     */
    protected $replyQueue;

    /**
     * Add a callback to run when the remote worker completes the job.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function then($callback)
    {
        return $this->addRemoteCallback('then', $callback);
    }

    /**
     * Add a callback to run when the remote worker fails the job.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function catch($callback)
    {
        return $this->addRemoteCallback('catch', $callback);
    }

    /**
     * Set the queue the remote worker should send its reply to.
     *
     * @param  \UnitEnum|string  $queue
     * @return $this
     */
    public function replyOn($queue)
    {
        $this->replyQueue = enum_value($queue);

        return $this;
    }

    /**
     * Get the callbacks to run once the remote worker replies.
     *
     * @return array{then: array, catch: array}
     */
    public function remoteCallbacks()
    {
        return $this->remoteCallbacks;
    }

    /**
     * Get the queue the remote worker should send its reply to.
     *
     * @return string|null
     */
    public function replyQueue()
    {
        return $this->replyQueue;
    }

    /**
     * Register a remote callback with proper serialization.
     *
     * @param  string  $type
     * @param  callable  $callback
     * @return $this
     */
    protected function addRemoteCallback(string $type, $callback)
    {
        $this->remoteCallbacks[$type][] = $callback instanceof Closure
            ? new SerializableClosure($callback)
            : $callback;

        return $this;
    }
}
