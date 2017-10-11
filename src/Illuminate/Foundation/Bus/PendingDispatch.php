<?php

namespace Illuminate\Foundation\Bus;

use Illuminate\Contracts\Bus\Dispatcher;

class PendingDispatch
{
    /**
     * The job.
     *
     * @var mixed
     */
    protected $job;

    /**
     * Create a new pending job dispatch.
     *
     * @param  mixed  $job
     * @return void
     */
    public function __construct($job)
    {
        $this->job = $job;
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
     * Set the desired delay for the job.
     *
     * @param  \DateTime|int|null  $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->job->delay($delay);

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
        $this->job->chain($chain)->onChainQueue($queue)->onChainConnection($connection);

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
        $this->job->onChainConnection($connection);

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
        $this->job->onChainQueue($queue);

        return $this;
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        app(Dispatcher::class)->dispatch($this->job);
    }
}
