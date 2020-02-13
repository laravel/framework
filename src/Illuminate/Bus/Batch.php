<?php

namespace Illuminate\Bus;

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
     * @return void
     */
    public function dispatch()
    {
        $id = Str::uuid();

        app('cache')->put('batch_'.$id.'_counter', count($this->jobs), 3600);
        app('cache')->put('batch_'.$id, json_encode([
            'success' => serialize($this->callback),
            'failure' => serialize($this->failureCallback),
        ]), 3600);

        foreach($this->jobs as $job){
            $job->batchId($id);

            $job->onConnection($job->connection ?: $this->connection);
            $job->onQueue($job->queue ?: $this->queue);

            dispatch($job);
        }
    }
}
