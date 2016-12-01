<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Queue\ElasticsearchQueue;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;

class ElasticsearchJob extends Job implements JobContract
{
    /**
     * The elasticsearch queue instance.
     *
     * @var \Illuminate\Queue\ElasticsearchQueue
     */
    protected $elasticsearch;

    /**
     * The elasticsearch job payload.
     *
     * @var \StdClass
     */
    protected $job;

    /**
     * @var string
     */
    protected $queue;

    /**
     * ElasticsearchJob constructor.
     * @param Container $container
     * @param ElasticsearchQueue $elasticsearch
     * @param $job
     * @param $queue
     */
    public function __construct(Container $container, ElasticsearchQueue $elasticsearch, $job, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->elasticsearch = $elasticsearch;
        $this->container = $container;
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->elasticsearch->deleteReserved($this->queue, $this->job->id);
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $this->delete();

        $this->elasticsearch->release($this->queue, $this->job, $delay);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return (int) $this->job->attempts;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->job->id;
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job->payload;
    }

    /**
     * Get the IoC container instance.
     *
     * @return \Illuminate\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get the underlying queue driver instance.
     *
     * @return \Illuminate\Queue\ElasticsearchQueue
     */
    public function getElasticsearchQueue()
    {
        return $this->elasticsearch;
    }

    /**
     * Get the underlying elasticsearch job.
     *
     * @return \StdClass
     */
    public function getElasticsearchJob()
    {
        return $this->job;
    }
}
