<?php

namespace Illuminate\Queue;

use DateTimeInterface;
use Illuminate\Container\Container;
use Illuminate\Support\InteractsWithTime;

abstract class Queue
{
    use InteractsWithTime;

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The encrypter implementation.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The connection name for the queue.
     *
     * @var string
     */
    protected $connectionName;

    /**
     * Push a new job onto the queue.
     *
     * @param  string $queue
     * @param  string $job
     * @param  mixed  $data
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '')
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  string                               $queue
     * @param  \DateTimeInterface|\DateInterval|int $delay
     * @param  string                               $job
     * @param  mixed                                $data
     * @return mixed
     */
    public function laterOn($queue, $delay, $job, $data = '')
    {
        return $this->later($delay, $job, $data, $queue);
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  string $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ((array) $jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    /**
     * @return PayloadSerializerManager
     */
    protected function getPayloadSerializerManager()
    {
        return $this->container->make(PayloadSerializerManager::class);
    }

    /**
     * @param $connectionName
     * @return \Illuminate\Contracts\Queue\PayloadSerializer
     */
    protected function getPayloadSerializer($connectionName)
    {
        return $this->getPayloadSerializerManager()->getSerializer($connectionName);
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param string       $queueName
     * @param string|mixed $job
     * @param mixed        $data
     * @return string
     *
     * @throws \Illuminate\Queue\InvalidPayloadException
     */
    protected function createPayload($queueName, $job, $data = '')
    {
        $serializer = $this->getPayloadSerializer($this->getConnectionName());

        return $serializer->serialize($serializer->createPayloadArray(
            $this->getConnectionName(),
            $queueName,
            $job,
            $data
        ));
    }

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Set the connection name for the queue.
     *
     * @param  string $name
     * @return $this
     */
    public function setConnectionName($name)
    {
        $this->connectionName = $name;

        return $this;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  \Illuminate\Container\Container $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}
