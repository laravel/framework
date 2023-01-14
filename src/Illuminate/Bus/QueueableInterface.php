<?php

namespace Illuminate\Bus;

interface QueueableInterface
{
    /**
     * Set the desired connection for the job.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function onConnection(?string $connection): self;

    /**
     * Set the desired queue for the job.
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function onQueue(?string $queue): self;

    /**
     * Set the desired connection for the chain.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function allOnConnection(?string $connection): self;

    /**
     * Set the desired queue for the chain.
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function allOnQueue(?string $queue): self;

    /**
     * Set the desired delay in seconds for the job.
     *
     * @param  \DateTimeInterface|\DateInterval|array|int|null  $delay
     * @return $this
     */
    public function delay(\DateInterval|\DateTimeInterface|int|array|null $delay): self;

    /**
     * Indicate that the job should be dispatched after all database transactions have committed.
     *
     * @return $this
     */
    public function afterCommit(): self;

    /**
     * Indicate that the job should not wait until database transactions have been committed before dispatching.
     *
     * @return $this
     */
    public function beforeCommit(): self;

    /**
     * Specify the middleware the job should be dispatched through.
     *
     * @param  array|object  $middleware
     * @return $this
     */
    public function through($middleware): self;

    /**
     * Set the jobs that should run if this job is successful.
     *
     * @param  array  $chain
     * @return $this
     */
    public function chain(array $chain): self;

    /**
     * Prepend a job to the current chain so that it is run after the currently running job.
     *
     * @param  mixed  $job
     * @return $this
     */
    public function prependToChain($job): self;

    /**
     * Append a job to the end of the current chain.
     *
     * @param  mixed  $job
     * @return $this
     */
    public function appendToChain($job): self;

    /**
     * Dispatch the next job on the chain.
     *
     * @return void
     */
    public function dispatchNextJobInChain(): void;

    /**
     * Invoke all of the chain's failed job callbacks.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function invokeChainCatchCallbacks(\Throwable $e);
}
