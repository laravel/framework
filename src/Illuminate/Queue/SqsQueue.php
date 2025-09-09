<?php

namespace Illuminate\Queue;

use Aws\Sqs\SqsClient;
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
     * Create a new Amazon SQS queue instance.
     *
     * @param  \Aws\Sqs\SqsClient  $sqs
     * @param  string  $default
     * @param  string  $prefix
     * @param  string  $suffix
     * @param  bool  $dispatchAfterCommit
     */
    public function __construct(
        SqsClient $sqs,
        $default,
        $prefix = '',
        $suffix = '',
        $dispatchAfterCommit = false,
    ) {
        $this->sqs = $sqs;
        $this->prefix = $prefix;
        $this->default = $default;
        $this->suffix = $suffix;
        $this->dispatchAfterCommit = $dispatchAfterCommit;
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
            'AttributeNames' => [
                'ApproximateNumberOfMessages',
                'ApproximateNumberOfMessagesDelayed',
                'ApproximateNumberOfMessagesNotVisible',
            ],
        ]);

        $a = $response['Attributes'];

        return (int) $a['ApproximateNumberOfMessages']
            + (int) $a['ApproximateNumberOfMessagesDelayed']
            + (int) $a['ApproximateNumberOfMessagesNotVisible'];
    }

    /**
     * Get the number of pending jobs.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function pendingSize($queue = null)
    {
        $response = $this->sqs->getQueueAttributes([
            'QueueUrl' => $this->getQueue($queue),
            'AttributeNames' => ['ApproximateNumberOfMessages'],
        ]);

        return (int) $response['Attributes']['ApproximateNumberOfMessages'] ?? 0;
    }

    /**
     * Get the number of delayed jobs.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function delayedSize($queue = null)
    {
        $response = $this->sqs->getQueueAttributes([
            'QueueUrl' => $this->getQueue($queue),
            'AttributeNames' => ['ApproximateNumberOfMessagesDelayed'],
        ]);

        return (int) $response['Attributes']['ApproximateNumberOfMessagesDelayed'] ?? 0;
    }

    /**
     * Get the number of reserved jobs.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function reservedSize($queue = null)
    {
        $response = $this->sqs->getQueueAttributes([
            'QueueUrl' => $this->getQueue($queue),
            'AttributeNames' => ['ApproximateNumberOfMessagesNotVisible'],
        ]);

        return (int) $response['Attributes']['ApproximateNumberOfMessagesNotVisible'] ?? 0;
    }

    /**
     * Get the creation timestamp of the oldest pending job, excluding delayed jobs.
     *
     * Not supported by SQS, returns null.
     *
     * @param  string|null  $queue
     * @return int|null
     */
    public function creationTimeOfOldestPendingJob($queue = null)
    {
        // Not supported by SQS...
        return null;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
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
            function ($payload, $queue) use ($job) {
                return $this->pushRaw($payload, $queue, $this->getQueueableOptions($job, $queue));
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
        return $this->sqs->sendMessage([
            'QueueUrl' => $this->getQueue($queue), 'MessageBody' => $payload, ...$options,
        ])->get('MessageId');
    }

    /**
     * Push a new job onto the queue after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queue ?: $this->default, $data, $delay),
            $queue,
            $delay,
            function ($payload, $queue, $delay) use ($job) {
                return $this->pushRaw($payload, $queue, [
                    'DelaySeconds' => $this->secondsUntil($delay),
                    ...$this->getQueueableOptions($job, $queue),
                ]);
            }
        );
    }

    /**
     * Get the queueable options from the job.
     *
     * @param  mixed  $job
     * @param  string|null  $queue
     * @return array{MessageGroupId?: string, MessageDeduplicationId?: string}
     */
    protected function getQueueableOptions($job, $queue): array
    {
        if (! is_object($job) || ! str_ends_with((string) $queue, '.fifo')) {
            return [];
        }

        $transformToString = fn ($value) => strval($value);

        $messageGroupId = transform($job->messageGroup ?? null, $transformToString);

        $messageDeduplicationId = match (true) {
            method_exists($job, 'deduplicationId') => transform($job->deduplicationId(), $transformToString),
            default => (string) Str::orderedUuid(),
        };

        return array_filter([
            'MessageGroupId' => $messageGroupId,
            'MessageDeduplicationId' => $messageDeduplicationId,
        ]);
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
                $this->container, $this->sqs, $response['Messages'][0],
                $this->connectionName, $queue
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
