<?php

namespace Illuminate\Queue\Jobs;

use DateTime;
use Illuminate\Support\Str;

abstract class Job
{
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
     * The name of the queue the job belongs to.
     *
     * @var string
     */
    protected $queue;

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
     * Fire the job.
     *
     * @return void
     */
    abstract public function fire();

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
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    abstract public function attempts();

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    abstract public function getRawBody();

    /**
     * Resolve and fire the job handler method.
     *
     * @param  array  $payload
     * @return void
     */
    protected function resolveAndFire(array $payload)
    {
        list($class, $method) = $this->parseJob($payload['job']);

        $this->instance = $this->resolve($class);

        $this->instance->{$method}($this, $this->resolveQueueableEntities($payload['data']));
    }

    /**
     * Parse the job declaration into class and method.
     *
     * @param  string  $job
     * @return array
     */
    protected function parseJob($job)
    {
        $segments = explode('@', $job);

        return count($segments) > 1 ? $segments : [$segments[0], 'fire'];
    }

    /**
     * Resolve the given job handler.
     *
     * @param  string  $class
     * @return mixed
     */
    protected function resolve($class)
    {
        return $this->container->make($class);
    }

    /**
     * Resolve all of the queueable entities in the given payload.
     *
     * @param  mixed  $data
     * @return mixed
     */
    protected function resolveQueueableEntities($data)
    {
        if (is_string($data)) {
            return $this->resolveQueueableEntity($data);
        }

        if (is_array($data)) {
            array_walk($data, function (&$d) { $d = $this->resolveQueueableEntity($d); });
        }

        return $data;
    }

    /**
     * Resolve a single queueable entity from the resolver.
     *
     * @param  mixed  $value
     * @return \Illuminate\Contracts\Queue\QueueableEntity
     */
    protected function resolveQueueableEntity($value)
    {
        if (is_string($value) && Str::startsWith($value, '::entity::')) {
            list($marker, $type, $id) = explode('|', $value, 3);

            return $this->getEntityResolver()->resolve($type, $id);
        }

        return $value;
    }

    /**
     * Call the failed method on the job instance.
     *
     * @return void
     */
    public function failed()
    {
        $payload = json_decode($this->getRawBody(), true);

        list($class, $method) = $this->parseJob($payload['job']);

        $this->instance = $this->resolve($class);

        if (method_exists($this->instance, 'failed')) {
            $this->instance->failed($this->resolveQueueableEntities($payload['data']));
        }
    }

    /**
     * Get an entity resolver instance.
     *
     * @return \Illuminate\Contracts\Queue\EntityResolver
     */
    protected function getEntityResolver()
    {
        return $this->container->make('Illuminate\Contracts\Queue\EntityResolver');
    }

    /**
     * Calculate the number of seconds with the given delay.
     *
     * @param  \DateTime|int  $delay
     * @return int
     */
    protected function getSeconds($delay)
    {
        if ($delay instanceof DateTime) {
            return max(0, $delay->getTimestamp() - $this->getTime());
        }

        return (int) $delay;
    }

    /**
     * Get the current system time.
     *
     * @return int
     */
    protected function getTime()
    {
        return time();
    }

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName()
    {
        return json_decode($this->getRawBody(), true)['job'];
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
}
