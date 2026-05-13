<?php

namespace Illuminate\Queue;

use Aws\Sqs\SqsClient;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SqsQueue extends Queue implements QueueContract, ClearableQueue
{
    /**
     * The maximum SQS payload size in bytes (1 MB).
     *
     * @var int
     */
    const MAX_SQS_PAYLOAD_SIZE = 1048576;

    /**
     * The cache key prefix for extended SQS payloads.
     *
     * @var string
     */
    const EXTENDED_PAYLOAD_CACHE_PREFIX = 'laravel:sqs-payloads:';

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
     * The overflow storage options for large payload offloading.
     *
     * @var array
     */
    protected $overflowStorage = [];

    /**
     * Create a new Amazon SQS queue instance.
     *
     * @param  \Aws\Sqs\SqsClient  $sqs
     * @param  string  $default
     * @param  string  $prefix
     * @param  string  $suffix
     * @param  bool  $dispatchAfterCommit
     * @param  array  $overflowStorage
     */
    public function __construct(
        SqsClient $sqs,
        $default,
        $prefix = '',
        $suffix = '',
        $dispatchAfterCommit = false,
        array $overflowStorage = [],
    ) {
        $this->sqs = $sqs;
        $this->prefix = $prefix;
        $this->default = $default;
        $this->suffix = $suffix;
        $this->dispatchAfterCommit = $dispatchAfterCommit;
        $this->overflowStorage = $overflowStorage;
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
     * Get the pending jobs for the given queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Support\Collection
     */
    public function pendingJobs($queue = null): Collection
    {
        return new Collection;
    }

    /**
     * Get the delayed jobs for the given queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Support\Collection
     */
    public function delayedJobs($queue = null): Collection
    {
        return new Collection;
    }

    /**
     * Get the reserved jobs for the given queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Support\Collection
     */
    public function reservedJobs($queue = null): Collection
    {
        return new Collection;
    }

    /**
     * Get all pending jobs across every queue.
     *
     * @return \Illuminate\Support\Collection
     */
    public function allPendingJobs(): Collection
    {
        return new Collection;
    }

    /**
     * Get all delayed jobs across every queue.
     *
     * @return \Illuminate\Support\Collection
     */
    public function allDelayedJobs(): Collection
    {
        return new Collection;
    }

    /**
     * Get all reserved jobs across every queue.
     *
     * @return \Illuminate\Support\Collection
     */
    public function allReservedJobs(): Collection
    {
        return new Collection;
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
                return $this->pushRaw($payload, $queue, $this->getQueueableOptions($job, $queue, $payload));
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
        if ($this->willOverflow($payload)) {
            $payload = $this->overflow($payload);
        }

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
                return $this->pushRaw($payload, $queue, $this->getQueueableOptions($job, $queue, $payload, $delay));
            }
        );
    }

    /**
     * Get the queueable options from the job.
     *
     * @param  mixed  $job
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  \DateTimeInterface|\DateInterval|int|null  $delay
     * @return array{DelaySeconds?: int, MessageGroupId?: string, MessageDeduplicationId?: string}
     */
    public function getQueueableOptions($job, $queue, $payload, $delay = null): array
    {
        // Make sure we have a queue name to properly determine if it's a FIFO queue...
        $queue ??= $this->default;

        $isObject = is_object($job);
        $isFifo = str_ends_with((string) $queue, '.fifo');

        $options = [];

        // DelaySeconds cannot be used with FIFO queues. AWS will return an error...
        if (! empty($delay) && ! $isFifo) {
            $options['DelaySeconds'] = $this->secondsUntil($delay);
        }

        // If the job is a string job on a standard queue, there are no more options...
        if (! $isObject && ! $isFifo) {
            return $options;
        }

        $transformToString = fn ($value) => (string) $value;

        // The message group ID is required for FIFO queues and is optional for
        // standard queues. Job objects contain a group ID. With string jobs
        // sent to FIFO queues, assign these to the same message group ID.
        $messageGroupId = null;

        if ($isObject) {
            $messageGroupId = transform($job->messageGroup ?? (method_exists($job, 'messageGroup') ? $job->messageGroup() : null), $transformToString);
        } elseif ($isFifo) {
            $messageGroupId = transform($queue, $transformToString);
        }

        $options['MessageGroupId'] = $messageGroupId;

        // The message deduplication ID is only valid for FIFO queues. Every job
        // without the method will be considered unique. To use content-based
        // deduplication enable it in AWS and have the method return empty.
        $messageDeduplicationId = null;

        if ($isFifo) {
            $messageDeduplicationId = match (true) {
                $isObject && isset($job->deduplicator) && is_callable($job->deduplicator) => transform(call_user_func($job->deduplicator, $payload, $queue), $transformToString),
                $isObject && method_exists($job, 'deduplicationId') => transform($job->deduplicationId($payload, $queue), $transformToString),
                default => (string) Str::orderedUuid(),
            };
        }

        $options['MessageDeduplicationId'] = $messageDeduplicationId;

        return array_filter($options);
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
     * Determine if the payload should be sent to overflow storage.
     *
     * @param  string  $payload
     * @return bool
     */
    protected function willOverflow($payload)
    {
        if (! Arr::get($this->overflowStorage, 'enabled', false)) {
            return false;
        }

        return Arr::get($this->overflowStorage, 'always', false)
            || strlen($payload) >= static::MAX_SQS_PAYLOAD_SIZE;
    }

    /**
     * Store the payload in overflow storage and return a pointer payload.
     *
     * @param  string  $payload
     * @return string
     */
    protected function overflow($payload)
    {
        $decoded = json_decode($payload);

        $uuid = is_object($decoded) && isset($decoded->uuid)
            ? $decoded->uuid
            : (string) Str::uuid();

        $path = $this->overflowPath($uuid);

        if ($this->overflowDriverIsFilesystem()) {
            $this->container->make('filesystem')->disk(
                Arr::get($this->overflowStorage, 'disk')
            )->put($path, $payload);
        } else {
            $this->container->make('cache')->store(
                Arr::get($this->overflowStorage, 'store')
            )->put($path, $payload);
        }

        return json_encode(['@pointer' => $path]);
    }

    /**
     * Determine if the overflow driver is the filesystem driver.
     *
     * @return bool
     */
    protected function overflowDriverIsFilesystem()
    {
        return Arr::get($this->overflowStorage, 'driver', 'cache') === 'filesystem';
    }

    /**
     * Build the overflow storage path or cache key for the given uuid.
     *
     * @param  string  $uuid
     * @return string
     */
    protected function overflowPath($uuid)
    {
        $prefix = Arr::get($this->overflowStorage, 'prefix');

        if ($this->overflowDriverIsFilesystem()) {
            return ltrim(($prefix ?: 'laravel/sqs-payloads').'/'.$uuid.'.json', '/');
        }

        return ($prefix ? rtrim($prefix, ':').':' : static::EXTENDED_PAYLOAD_CACHE_PREFIX).$uuid;
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
                $this->connectionName, $queue, $this->overflowStorage
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

            if ($this->overflowDriverIsFilesystem() &&
                Arr::get($this->overflowStorage, 'delete_after_processing') &&
                $prefix = Arr::get($this->overflowStorage, 'prefix')) {
                $this->container->make('filesystem')->disk(
                    Arr::get($this->overflowStorage, 'disk')
                )->deleteDirectory($prefix);
            }
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
