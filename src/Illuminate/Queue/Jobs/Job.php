<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Support\InteractsWithTime;

abstract class Job
{
    use InteractsWithTime;

    /**
     * The job handler instance.
     *
     * @var mixed
     */
    protected $instance;

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Indicates if the job has been deleted.
     *
     * @var bool
     */
    protected $deleted = false;

    /**
     * Indicates if the job has been released.
     *
     * @var bool
     */
    protected $released = false;

    /**
     * Indicates if the job has failed.
     *
     * @var bool
     */
    protected $failed = false;

    /**
     * The name of the connection the job belongs to.
     */
    protected $connectionName;

    /**
     * The name of the queue the job belongs to.
     *
     * @var string
     */
    protected $queue;

    /**
     * Get the job identifier.
     *
     * @return string
     */
    abstract public function getJobId();

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    abstract public function getRawBody();

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        $payload = $this->payload();

        list($class, $method) = JobName::parse($payload['job']);

        ($this->instance = $this->resolve($class))->{$method}($this, $payload['data']);
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        $this->deleted = true;
    }

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return void
     */
    public function release($delay = 0)
    {
        $this->released = true;
    }

    /**
     * Determine if the job was released back into the queue.
     *
     * @return bool
     */
    public function isReleased()
    {
        return $this->released;
    }

    /**
     * Determine if the job has been deleted or released.
     *
     * @return bool
     */
    public function isDeletedOrReleased()
    {
        return $this->isDeleted() || $this->isReleased();
    }

    /**
     * Determine if the job has been marked as a failure.
     *
     * @return bool
     */
    public function hasFailed()
    {
        return $this->failed;
    }

    /**
     * Mark the job as "failed".
     *
     * @return void
     */
    public function markAsFailed()
    {
        $this->failed = true;
    }

    /**
     * Process an exception that caused the job to fail.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function failed($e)
    {
        $this->markAsFailed();

        $payload = $this->payload();

        list($class, $method) = JobName::parse($payload['job']);

        if (method_exists($this->instance = $this->resolve($class), 'failed')) {
            $this->instance->failed($payload['data'], $e);
        }
    }

    /**
     * Resolve the given class.
     *
     * @param  string  $class
     * @return mixed
     */
    protected function resolve($class)
    {
        return $this->container->make($class);
    }

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload()
    {
        return json_decode($this->getRawBody(), true);
    }

    /**
     * Get the number of times to attempt a job.
     *
     * @return int|null
     */
    public function maxTries()
    {
        return $this->payload()['maxTries'] ?? null;
    }

    /**
     * Get the number of seconds the job can run.
     *
     * @return int|null
     */
    public function timeout()
    {
        return $this->payload()['timeout'] ?? null;
    }

    /**
     * Get the timestamp indicating when the job should timeout.
     *
     * @return int|null
     */
    public function timeoutAt()
    {
        return $this->payload()['timeoutAt'] ?? null;
    }

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName()
    {
        return $this->payload()['job'];
    }

    /**
     * Get the resolved name of the queued job class.
     *
     * Resolves the name of "wrapped" jobs such as class-based handlers.
     *
     * @return string
     */
    public function resolveName()
    {
        return JobName::resolve($this->getName(), $this->payload());
    }

    /**
     * Get the name of the connection the job belongs to.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Get the service container instance.
     *
     * @return \Illuminate\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}
