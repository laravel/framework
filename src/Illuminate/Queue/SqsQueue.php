<?php

namespace Illuminate\Queue;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class SqsQueue extends Queue implements QueueContract
{
    /**
     * The Amazon SQS instance.
     *
     * @var \Aws\Sqs\SqsClient
     */
    protected $sqs;

    /**
     * The name of the default tube.
     *
     * @var string
     */
    protected $default;

    /**
     * The job creator callback.
     *
     * @var callable|null
     */
    protected $jobCreator;

    /**
     * Create a new Amazon SQS queue instance.
     *
     * @param  \Aws\Sqs\SqsClient  $sqs
     * @param  string  $default
     * @return void
     */
    public function __construct(SqsClient $sqs, $default)
    {
        $this->sqs = $sqs;
        $this->default = $default;
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
        return $this->pushRaw($this->createPayload($job, $data), $queue);
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
        $response = $this->sqs->sendMessage(['QueueUrl' => $this->getQueue($queue), 'MessageBody' => $payload]);

        return $response->get('MessageId');
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
        $payload = $this->createPayload($job, $data);

        $delay = $this->getSeconds($delay);

        return $this->sqs->sendMessage([

            'QueueUrl' => $this->getQueue($queue), 'MessageBody' => $payload, 'DelaySeconds' => $delay,

        ])->get('MessageId');
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

        $response = $this->sqs->receiveMessage(
            ['QueueUrl' => $queue, 'AttributeNames' => ['ApproximateReceiveCount']]
        );

        if (count($response['Messages']) > 0) {
            if ($this->jobCreator) {
                return call_user_func($this->jobCreator, $this->container, $this->sqs, $queue, $response);
            } else {
                return new SqsJob($this->container, $this->sqs, $queue, $response['Messages'][0]);
            }
        }
    }

    /**
     * Define the job creator callback for the connection.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function createJobsUsing(callable $callback)
    {
        $this->jobCreator = $callback;

        return $this;
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
     * Get the underlying SQS instance.
     *
     * @return \Aws\Sqs\SqsClient
     */
    public function getSqs()
    {
        return $this->sqs;
    }
}
