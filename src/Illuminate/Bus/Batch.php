<?php

namespace Illuminate\Bus;

use Illuminate\Queue\ExistingBatch;
use Illuminate\Queue\SerializableClosure;
use Illuminate\Support\Str;

class Batch
{
    /**
     * The name of the connection the batch should be sent to.
     *
     * @var string|null
     */
    public $connection;

    /**
     * The name of the queue the batch should be sent to.
     *
     * @var string|null
     */
    public $queue;

    /**
     * The jobs collection.
     *
     * @var array
     */
    public $jobs;

    /**
     * The success callback.
     *
     * @var \Closure|null
     */
    public $callback;

    /**
     * The failure callback.
     *
     * @var \Closure|null
     */
    public $failureCallback;

    /**
     * Determine if the batch should fail if any job fails.
     *
     * @var bool
     */
    public $allowFailure = false;

    /**
     * Create a new batch instance.
     *
     * @param  array  $jobs
     * @return void
     */
    public function __construct($jobs)
    {
        $this->jobs = $jobs;
    }

    /**
     * Register a callback to be called after the batch.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function then($callback)
    {
        $this->callback = new SerializableClosure($callback);

        return $this;
    }

    /**
     * Register a callback to be called after the batch fails.
     *
     * @param  \Closure  $failureCallback
     * @return $this
     */
    public function otherwise($failureCallback)
    {
        $this->failureCallback = new SerializableClosure($failureCallback);

        return $this;
    }

    /**
     * Allow the batch to continue of a job fails.
     *
     * @return $this
     */
    public function allowFailure()
    {
        $this->allowFailure = true;

        return $this;
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
     * Dispatch the batch to queue.
     *
     * @return string
     */
    public function dispatch()
    {
        $id = Str::uuid();

        app('cache')->put('batch_'.$id.'_size', 0, 3600);
        app('cache')->put('batch_'.$id.'_pending', 0, 3600);
        app('cache')->put('batch_'.$id.'_failed', 0, 3600);
        app('cache')->put('batch_'.$id, json_encode([
            'allowFailure' => $this->allowFailure,
            'connection' => $this->connection,
            'queue' => $this->queue,
            'success' => $this->callback ? serialize($this->callback) : null,
            'failure' => $this->failureCallback ? serialize($this->failureCallback) : null,
        ]), 3600);

        static::find($id)->add($this->jobs);

        return $id;
    }

    /**
     * Find an existing batch.
     *
     * @param  string  $id
     * @return \Illuminate\Queue\ExistingBatch|null
     */
    public static function find($id)
    {
        try {
            return new ExistingBatch($id);
        } catch (\InvalidArgumentException $e) {
            // return null
        }
    }
}
