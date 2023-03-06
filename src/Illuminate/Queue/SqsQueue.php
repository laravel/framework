<?php

namespace Illuminate\Queue;

use Aws\Sqs\SqsClient;
use BadMethodCallException;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Support\Str;

class SqsQueue extends Queue implements QueueContract, ClearableQueue
{
    /**
     * The Amazon SQS instance.
     *
     * @var \Aws\Sqs\SqsClient
     */
    protected $sqs;

    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected $default;

    /**
     * The queue URL prefix.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The queue name suffix.
     *
     * @var string
     */
    protected $suffix;

    /**
     * The SQS queue type.
     *
     * @var string
     */
    private $type;

    /**
     * The message group id.
     *
     * @var string
     */
    private $messageGroupId;

    /**
     * The message deduplication id.
     *
     * @var string
     */
    private $messageDeduplicationId;

    /**
     * The flag for FIFO delays on queue.
     *
     * @var bool
     */
    private $allowDelay;

    /**
     * Create a new Amazon SQS queue instance.
     *
     * @param  \Aws\Sqs\SqsClient  $sqs
     * @param  string  $default
     * @param  string  $prefix
     * @param  string  $suffix
     * @param  bool  $dispatchAfterCommit
     * @param  string  $type
     * @param  string  $messageGroupId
     * @param  string  $messageDeduplicationId
     * @param  bool  $allowDelay
     * @return void
     */
    public function __construct(
        SqsClient $sqs,
        $default,
        $prefix = '',
        $suffix = '',
        $dispatchAfterCommit = false,
        $type = 'standard',
        $messageGroupId = null,
        $messageDeduplicationId = null,
        $allowDelay = false
    ) {
        $this->sqs = $sqs;
        $this->prefix = $prefix;
        $this->default = $default;
        $this->suffix = $suffix;
        $this->dispatchAfterCommit = $dispatchAfterCommit;
        $this->type = $type;
        $this->messageGroupId = $messageGroupId;
        $this->messageDeduplicationId = $messageDeduplicationId;
        $this->allowDelay = $allowDelay;
    }

    /**
     * Check if the queue is FIFO.
     *
     * @return bool
     */
    protected function isFifoQueue()
    {
        return $this->type == 'fifo';
    }

    /**
     * Get the size of the queue.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function size($queue = null)
    {
        $response = $this->sqs->getQueueAttributes([
            'QueueUrl' => $this->getQueue($queue),
            'AttributeNames' => ['ApproximateNumberOfMessages'],
        ]);

        $attributes = $response->get('Attributes');

        return (int) $attributes['ApproximateNumberOfMessages'];
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  mixed  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queue ?: $this->default, $data),
            $queue,
            null,
            function ($payload, $queue) use (&$job) {
                return $this->pushRaw($payload, $queue, $this->isFifoQueue() ? [
                    'MessageGroupId' => $job->messageGroupId ?? $this->messageGroupId,
                    'MessageDeduplicationId' => $job->messageDeduplicationId ?? $this->messageDeduplicationId,
                ] : []);
            }
        );
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $args = [
            'QueueUrl' => $this->getQueue($queue),
            'MessageBody' => $payload,
        ];
        if ($this->isFifoQueue()) {
            $args['MessageGroupId'] = (string) $options['MessageGroupId'];
            $args['MessageDeduplicationId'] = (string) $options['MessageDeduplicationId'];
        }
        return $this->sqs->sendMessage($args)->get('MessageId');
    }

    /**
     * Push a new job onto the queue after (n) seconds.
     * Note that SQS FIFO doesn't support delay per
     * message, instead it uses default delay on
     * queue which is configurable via AWS SQS
     * panel or API.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        if ($this->isFifoQueue()) {
            if ($this->allowDelay) {
                return $this->push($job, $data, $queue);
            }

            throw new BadMethodCallException('FIFO queues do not support per-message delays.');
        }

        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queue ?: $this->default, $data),
            $queue,
            $delay,
            function ($payload, $queue, $delay) {
                return $this->sqs->sendMessage([
                    'QueueUrl' => $this->getQueue($queue),
                    'MessageBody' => $payload,
                    'DelaySeconds' => $this->secondsUntil($delay),
                ])->get('MessageId');
            }
        );
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return void
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ((array) $jobs as $job) {
            if (isset($job->delay)) {
                $this->later($job->delay, $job, $data, $queue);
            } else {
                $this->push($job, $data, $queue);
            }
        }
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue = $this->getQueue($queue),
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (! is_null($response['Messages']) && count($response['Messages']) > 0) {
            return new SqsJob(
                $this->container,
                $this->sqs,
                $response['Messages'][0],
                $this->connectionName,
                $queue
            );
        }
    }

    /**
     * Delete all of the jobs from the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function clear($queue)
    {
        return tap($this->size($queue), function () use ($queue) {
            $this->sqs->purgeQueue([
                'QueueUrl' => $this->getQueue($queue),
            ]);
        });
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null  $queue
     * @return string
     */
    public function getQueue($queue)
    {
        $queue = $queue ?: $this->default;

        if ($this->isFifoQueue()) {
            $queue = sprintf('%s%s', $queue, '.fifo');
        }

        return filter_var($queue, FILTER_VALIDATE_URL) === false
            ? $this->suffixQueue($queue, $this->suffix)
            : $queue;
    }

    /**
     * Add the given suffix to the given queue name.
     *
     * @param  string  $queue
     * @param  string  $suffix
     * @return string
     */
    protected function suffixQueue($queue, $suffix = '')
    {
        if (str_ends_with($queue, '.fifo')) {
            $queue = Str::beforeLast($queue, '.fifo');

            return rtrim($this->prefix, '/').'/'.Str::finish($queue, $suffix).'.fifo';
        }

        return rtrim($this->prefix, '/').'/'.Str::finish($queue, $this->suffix);
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
