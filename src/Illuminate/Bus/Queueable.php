<?php

namespace Illuminate\Bus;

trait Queueable
{
    /**
     * The name of the connection the jobs should be sent to if not set on job.
     *
     * @var string|null
     */
    public $chainConnection = null;

    /**
     * The name of the queue the chained jobs should be sent to if not set on job.
     *
     * @var string|null
     */
    public $chainQueue = null;

    /**
     * The name of the connection the job should be sent to.
     *
     * @var string|null
     */
    public $connection;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue;

    /**
     * The number of seconds before the job should be made available.
     *
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    public $delay;

    /**
     * The jobs that should run if this job is successful.
     *
     * @var array
     */
    public $chained = [];

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
     * Set the desired delay for the job.
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
     * Set the jobs that should run if this job is successful.
     *
     * @param array $chain
     * @param null|string $queue
     * @param null|string $connection
     *
     * @return $this
     */
    public function chain($chain, $queue = null, $connection = null)
    {
        $this->chained = collect($chain)->map(function ($job) {
            return serialize($job);
        })->all();
        $this->onChainConnection($connection);
        $this->onChainQueue($queue);

        return $this;
    }

    /**
     * Set the desired default connection for the jobs on the chain.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function onChainConnection($connection)
    {
        $this->chainConnection = $connection;

        return $this;
    }

    /**
     * Set the desired default queue for the jobs on the chain.
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function onChainQueue($queue)
    {
        $this->chainQueue = $queue;

        return $this;
    }

    /**
     * Dispatch the next job on the chain.
     *
     * @return void
     */
    public function dispatchNextJobInChain()
    {
        if (! empty($this->chained)) {
            dispatch(tap(unserialize(array_shift($this->chained)), function ($next) {
                /* @var \Illuminate\Bus\Queueable $next */
                if (!in_array('Illuminate\Bus\Queueable', class_uses_recursive($next))) {
                    throw new \Exception('Trying to dispatch an object that is not Queueable');
                }
                // pass the chain settings on to the next job in the chain, IF this job does not have a new chain settings set...
                $next->onChainConnection($next->chainConnection ?: $this->chainConnection);
                $next->onChainQueue($next->chainQueue ?: $this->chainQueue);
                // array of remaining jobs...
                $next->chained = $this->chained;
                // use the chain setting if this job is not specifically set.
                $next->onConnection($next->connection ?: $this->chainConnection);
                $next->onQueue($next->queue ?: $this->chainQueue);
            }));
        }
    }
}
