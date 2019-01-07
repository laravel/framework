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
     * The name of the queue.
     *
     * @var string
     */
    protected $name;

    /**
     * The full queue URL.
     *
     * @var string
     */
    protected $url;

    /**
     * Create a new Amazon SQS queue instance.
     *
     * @param  \Aws\Sqs\SqsClient  $sqs
     * @param  string  $default
     * @param  string  $url
     * @return void
     */
    public function __construct(SqsClient $sqs, $name, $url = '')
    {
        $this->sqs = $sqs;
        $this->name = $name;
        $this->url = $url;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        $response = $this->sqs->getQueueAttributes([
            'QueueUrl' => $this->url,
            'AttributeNames' => ['ApproximateNumberOfMessages'],
        ]);

        $attributes = $response->get('Attributes');

        return (int) $attributes['ApproximateNumberOfMessages'];
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
        return $this->pushRaw($this->createPayload($job, $queue ?: $this->name, $data), $queue);
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
        return $this->sqs->sendMessage([
            'QueueUrl' => $this->url, 'MessageBody' => $payload,
        ])->get('MessageId');
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->sqs->sendMessage([
            'QueueUrl' => $this->url,
            'MessageBody' => $this->createPayload($job, $queue ?: $this->name, $data),
            'DelaySeconds' => $this->secondsUntil($delay),
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
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $this->url,
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (! is_null($response['Messages']) && count($response['Messages']) > 0) {
            return new SqsJob(
                $this->container, $this->sqs, $response['Messages'][0],
                $this->connectionName, $queue ?: $this->name
            );
        }
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
