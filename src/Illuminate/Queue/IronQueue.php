<?php

namespace Illuminate\Queue;

use IronMQ\IronMQ;
use Illuminate\Http\Response;
use Illuminate\Queue\Jobs\IronJob;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class IronQueue extends Queue implements QueueContract
{
    /**
     * The IronMQ instance.
     *
     * @var \IronMQ\IronMQ
     */
    protected $iron;

    /**
     * The name of the default tube.
     *
     * @var string
     */
    protected $default;

    /**
     * Indicates if the messages should be encrypted.
     *
     * @var bool
     */
    protected $shouldEncrypt;

    /**
     * Create a new IronMQ queue instance.
     *
     * @param  \IronMQ\IronMQ  $iron
     * @param  string  $default
     * @param  bool  $shouldEncrypt
     * @return void
     */
    public function __construct(IronMQ $iron, $default, $shouldEncrypt = false)
    {
        $this->iron = $iron;
        $this->default = $default;
        $this->shouldEncrypt = $shouldEncrypt;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data, $queue), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        if ($this->shouldEncrypt) {
            $payload = $this->crypt->encrypt($payload);
        }

        return $this->iron->postMessage($this->getQueue($queue), $payload, $options)->id;
    }

    /**
     * Push a raw payload onto the queue after encrypting the payload.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  int     $delay
     * @return mixed
     */
    public function recreate($payload, $queue = null, $delay)
    {
        $options = ['delay' => $this->getSeconds($delay)];

        return $this->pushRaw($payload, $queue, $options);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $delay = $this->getSeconds($delay);

        $payload = $this->createPayload($job, $data, $queue);

        return $this->pushRaw($payload, $queue, compact('delay'));
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        $job = $this->iron->getMessage($queue);

        // If we were able to pop a message off of the queue, we will need to decrypt
        // the message body, as all Iron.io messages are encrypted, since the push
        // queues will be a security hazard to unsuspecting developers using it.
        if (! is_null($job)) {
            $job->body = $this->parseJobBody($job->body);

            return new IronJob($this->container, $this, $job);
        }
    }

    /**
     * Delete a message from the Iron queue.
     *
     * @param  string  $queue
     * @param  string  $id
     * @return void
     */
    public function deleteMessage($queue, $id)
    {
        $this->iron->deleteMessage($queue, $id);
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return string
     */
    protected function createPayload($job, $data = '', $queue = null)
    {
        $payload = $this->setMeta(parent::createPayload($job, $data), 'attempts', 1);

        return $this->setMeta($payload, 'queue', $this->getQueue($queue));
    }

    /**
     * Parse the job body for firing.
     *
     * @param  string  $body
     * @return string
     */
    protected function parseJobBody($body)
    {
        return $this->shouldEncrypt ? $this->crypt->decrypt($body) : $body;
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null  $queue
     * @return string
     */
    public function getQueue($queue)
    {
        return $queue ?: $this->default;
    }

    /**
     * Get the underlying IronMQ instance.
     *
     * @return \IronMQ\IronMQ
     */
    public function getIron()
    {
        return $this->iron;
    }
}
