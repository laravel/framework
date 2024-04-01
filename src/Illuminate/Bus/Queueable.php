<?php

namespace Illuminate\Bus;

use Closure;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Assert as PHPUnit;
use RuntimeException;

trait Queueable
{
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
     * @var \DateTimeInterface|\DateInterval|array|int|null
     */
    public $delay;

    /**
     * Indicates whether the job should be dispatched after all database transactions have committed.
     *
     * @var bool|null
     */
    public $afterCommit;

    /**
     * The middleware the job should be dispatched through.
     *
     * @var array
     */
    public $middleware = [];

    /**
     * The jobs that should run if this job is successful.
     *
     * @var array
     */
    public $chained = [];

    /**
     * The name of the connection the chain should be sent to.
     *
     * @var string|null
     */
    public $chainConnection;

    /**
     * The name of the queue the chain should be sent to.
     *
     * @var string|null
     */
    public $chainQueue;

    /**
     * The callbacks to be executed on chain failure.
     *
     * @var array|null
     */
    public $chainCatchCallbacks;

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
     * Set the desired connection for the chain.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function allOnConnection($connection)
    {
        $this->chainConnection = $connection;
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the desired queue for the chain.
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function allOnQueue($queue)
    {
        $this->chainQueue = $queue;
        $this->queue = $queue;

        return $this;
    }

    /**
     * Set the desired delay in seconds for the job.
     *
     * @param  \DateTimeInterface|\DateInterval|array|int|null  $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * Indicate that the job should be dispatched after all database transactions have committed.
     *
     * @return $this
     */
    public function afterCommit()
    {
        $this->afterCommit = true;

        return $this;
    }

    /**
     * Indicate that the job should not wait until database transactions have been committed before dispatching.
     *
     * @return $this
     */
    public function beforeCommit()
    {
        $this->afterCommit = false;

        return $this;
    }

    /**
     * Specify the middleware the job should be dispatched through.
     *
     * @param  array|object  $middleware
     * @return $this
     */
    public function through($middleware)
    {
        $this->middleware = Arr::wrap($middleware);

        return $this;
    }

    /**
     * Set the jobs that should run if this job is successful.
     *
     * @param  array  $chain
     * @return $this
     */
    public function chain($chain)
    {
        $this->chained = collect($chain)->map(function ($job) {
            return $this->serializeJob($job);
        })->all();

        return $this;
    }

    /**
     * Prepend a job to the current chain so that it is run after the currently running job.
     *
     * @param  mixed  $job
     * @return $this
     */
    public function prependToChain($job)
    {
        $this->chained = Arr::prepend($this->chained, $this->serializeJob($job));

        return $this;
    }

    /**
     * Append a job to the end of the current chain.
     *
     * @param  mixed  $job
     * @return $this
     */
    public function appendToChain($job)
    {
        $this->chained = array_merge($this->chained, [$this->serializeJob($job)]);

        return $this;
    }

    /**
     * Serialize a job for queuing.
     *
     * @param  mixed  $job
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function serializeJob($job)
    {
        if ($job instanceof Closure) {
            if (! class_exists(CallQueuedClosure::class)) {
                throw new RuntimeException(
                    'To enable support for closure jobs, please install the illuminate/queue package.'
                );
            }

            $job = CallQueuedClosure::create($job);
        }

        return serialize($job);
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
                $next->chained = $this->chained;

                $next->onConnection($next->connection ?: $this->chainConnection);
                $next->onQueue($next->queue ?: $this->chainQueue);

                $next->chainConnection = $this->chainConnection;
                $next->chainQueue = $this->chainQueue;
                $next->chainCatchCallbacks = $this->chainCatchCallbacks;
            }));
        }
    }

    /**
     * Invoke all of the chain's failed job callbacks.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function invokeChainCatchCallbacks($e)
    {
        collect($this->chainCatchCallbacks)->each(function ($callback) use ($e) {
            $callback($e);
        });
    }

    /**
     * Assert that the job has the given chain of jobs attached to it.
     *
     * @param  array  $expectedChain
     * @return void
     */
    public function assertHasChain($expectedChain)
    {
        PHPUnit::assertTrue(
            collect($expectedChain)->isNotEmpty(),
            'The expected chain can not be empty.'
        );

        if (collect($expectedChain)->contains(fn ($job) => is_object($job))) {
            $expectedChain = collect($expectedChain)->map(fn ($job) => serialize($job))->all();
        } else {
            $chain = collect($this->chained)->map(fn ($job) => get_class(unserialize($job)))->all();
        }

        PHPUnit::assertTrue(
            $expectedChain === ($chain ?? $this->chained),
            'The job does not have the expected chain.'
        );
    }

    /**
     * Assert that the job has no remaining chained jobs.
     *
     * @return void
     */
    public function assertDoesntHaveChain()
    {
        PHPUnit::assertEmpty($this->chained, 'The job has chained jobs.');
    }
}
