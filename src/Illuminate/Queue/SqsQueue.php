<?php

namespace Illuminate\Queue;

use Aws\Command;
use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use GuzzleHttp\Promise\Each;
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
     * The maximum number of messages allowed per SendMessageBatch request.
     *
     * @var int
     */
    const MAX_MESSAGES_PER_BATCH = 10;

    /**
     * The maximum number of concurrent SendMessageBatch requests for standard queues.
     *
     * @var int
     */
    const MAX_CONCURRENT_BATCH_REQUESTS = 10;

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

        return (int) ($response['Attributes']['ApproximateNumberOfMessages'] ?? 0);
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

        return (int) ($response['Attributes']['ApproximateNumberOfMessagesDelayed'] ?? 0);
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

        return (int) ($response['Attributes']['ApproximateNumberOfMessagesNotVisible'] ?? 0);
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
     * Push an array of jobs onto the queue using the SendMessageBatch API.
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return void
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        $jobs = array_values((array) $jobs);

        if (empty($jobs)) {
            return;
        }

        [$afterCommit, $immediate] = $this->partitionJobsByAfterCommit($jobs);

        if (! empty($immediate)) {
            $this->sendBatchedMessages($this->prepareBatchMessages($immediate, $data, $queue), $queue);
        }

        if (! empty($afterCommit)) {
            foreach ($afterCommit as $job) {
                $this->registerRollbackCallbacksForJobsThatDispatchAfterCommit($job);
            }

            $messages = $this->prepareBatchMessages($afterCommit, $data, $queue);

            $this->container->make('db.transactions')->addCallback(
                fn () => $this->sendBatchedMessages($messages, $queue),
            );
        }
    }

    /**
     * Partition the given jobs by whether they should be deferred until the active database transaction commits.
     *
     * @param  array  $jobs
     * @return array{0: array, 1: array}
     */
    protected function partitionJobsByAfterCommit(array $jobs)
    {
        if (! $this->container->bound('db.transactions')) {
            return [[], $jobs];
        }

        return (new Collection($jobs))
            ->partition(fn ($job) => $this->shouldDispatchAfterCommit($job))
            ->map(fn ($jobs) => $jobs->values()->all())
            ->all();
    }

    /**
     * Create the payload for each of the given jobs.
     *
     * Payloads are created at dispatch time, even for jobs deferred until after the transaction commits.
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return array<int, array{job: mixed, delay: mixed, payload: string}>
     */
    protected function prepareBatchMessages(array $jobs, $data, $queue)
    {
        return (new Collection($jobs))
            ->map(function ($job) use ($data, $queue) {
                $delay = is_object($job) ? ($job->delay ?? null) : null;

                return [
                    'job' => $job,
                    'delay' => $delay,
                    'payload' => $this->createPayload($job, $queue ?: $this->default, $data, $delay),
                ];
            })
            ->all();
    }

    /**
     * Build entries, raise queueing events, dispatch chunks, and raise queued events with SQS message IDs.
     *
     * @param  array  $messages
     * @param  string|null  $queue
     * @return void
     *
     * @throws \Aws\Sqs\Exception\SqsException
     * @throws \Throwable
     */
    protected function sendBatchedMessages(array $messages, $queue)
    {
        $entries = [];

        foreach ($messages as $id => $message) {
            $this->raiseJobQueueingEvent($queue, $message['job'], $message['payload'], $message['delay']);

            $entries[$id] = $this->prepareSendMessageBatchEntry($id, $message, $queue);
        }

        $queueUrl = $this->getQueue($queue);

        $chunks = $this->chunkBatchEntries($entries);

        $requests = function () use ($chunks, $queueUrl) {
            foreach ($chunks as $index => $chunk) {
                yield $index => $this->sqs->sendMessageBatchAsync([
                    'QueueUrl' => $queueUrl,
                    'Entries' => $chunk,
                ]);
            }
        };

        Each::ofLimitAll(
            $requests(),
            str_ends_with($queueUrl, '.fifo') ? 1 : static::MAX_CONCURRENT_BATCH_REQUESTS,
            function ($result, $index, $aggregate) use ($chunks, $messages, $queue, $queueUrl) {
                $this->raiseJobQueuedEventsForBatchResult($result, $messages, $queue);

                // A batch can return HTTP 200 while rejecting entries, so surface those failures as an SqsException...
                if (! empty($result['Failed'])) {
                    $aggregate->reject($this->toBatchEntriesFailedException($result, $chunks[$index], $queueUrl));
                }
            },
        )->wait();
    }

    /**
     * Raise the queued events for entries accepted by SQS in the given batch result.
     *
     * @param  \Aws\Result  $result
     * @param  array  $messages
     * @param  string|null  $queue
     * @return void
     */
    protected function raiseJobQueuedEventsForBatchResult($result, array $messages, $queue)
    {
        foreach ($result['Successful'] ?? [] as $success) {
            if (! isset($messages[$success['Id']])) {
                continue;
            }

            $message = $messages[$success['Id']];

            $this->raiseJobQueuedEvent(
                $queue, $success['MessageId'], $message['job'], $message['payload'], $message['delay']
            );
        }
    }

    /**
     * Create the exception for a batch result that was accepted with rejected entries.
     *
     * @param  \Aws\Result  $result
     * @param  array  $chunk
     * @param  string  $queueUrl
     * @return \Aws\Sqs\Exception\SqsException
     */
    protected function toBatchEntriesFailedException($result, array $chunk, $queueUrl)
    {
        $failure = $result['Failed'][0];

        return new SqsException(
            sprintf(
                'SQS SendMessageBatch rejected [%d] of [%d] messages. First failure [%s]: %s',
                count($result['Failed']),
                count($chunk),
                $failure['Code'] ?? 'Unknown',
                $failure['Message'] ?? '',
            ),
            new Command('SendMessageBatch', ['QueueUrl' => $queueUrl, 'Entries' => $chunk]),
            [
                'code' => $failure['Code'] ?? null,
                'message' => $failure['Message'] ?? null,
                'result' => $result,
            ],
        );
    }

    /**
     * Build the SendMessageBatch entry for a single prepared message.
     *
     * The entry Id maps each Successful or Failed result returned by SQS back to its job.
     *
     * @param  int  $id
     * @param  array{job: mixed, delay: mixed, payload: string}  $message
     * @param  string|null  $queue
     * @return array
     */
    protected function prepareSendMessageBatchEntry($id, array $message, $queue)
    {
        ['job' => $job, 'delay' => $delay, 'payload' => $payload] = $message;

        return [
            'Id' => (string) $id,
            'MessageBody' => $this->willOverflow($payload) ? $this->overflow($payload) : $payload,
            ...$this->getQueueableOptions($job, $queue, $payload, $delay),
        ];
    }

    /**
     * Chunk batch entries respecting both the 10-message and cumulative payload-size limits enforced by SendMessageBatch.
     *
     * @param  array  $entries
     * @return array
     */
    protected function chunkBatchEntries(array $entries)
    {
        [$chunks, $currentChunk, $currentBytes] = [[], [], 0];

        foreach ($entries as $item) {
            $bytes = strlen($item['MessageBody']);

            $wouldExceedCount = count($currentChunk) >= static::MAX_MESSAGES_PER_BATCH;
            $wouldExceedBytes = $currentBytes + $bytes > static::MAX_SQS_PAYLOAD_SIZE;

            if (! empty($currentChunk) && ($wouldExceedCount || $wouldExceedBytes)) {
                $chunks[] = $currentChunk;
                $currentChunk = [];
                $currentBytes = 0;
            }

            $currentChunk[] = $item;
            $currentBytes += $bytes;
        }

        if (! empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

    /**
     * Determine if the payload should be stored in cache.
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
     * Store the payload in cache and return a pointer payload.
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

        $this->container->make('cache')->store(
            Arr::get($this->overflowStorage, 'store')
        )->put(
            $path = static::EXTENDED_PAYLOAD_CACHE_PREFIX.$uuid, $payload
        );

        return json_encode(['@pointer' => $path]);
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
                $isObject && isset($job->deduplicator) && is_callable($job->deduplicator) => transform(
                    call_user_func($job->deduplicator, $payload, $queue), $transformToString
                ),
                $isObject && method_exists($job, 'deduplicationId') => transform(
                    $job->deduplicationId($payload, $queue), $transformToString
                ),
                default => (string) Str::orderedUuid(),
            };
        }

        $options['MessageDeduplicationId'] = $messageDeduplicationId;

        return array_filter($options);
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
     * @param  string|null  $queue
     * @return int
     */
    public function clear($queue = null)
    {
        return tap($this->size($queue), function () use ($queue) {
            $this->sqs->purgeQueue([
                'QueueUrl' => $this->getQueue($queue),
            ]);

            if (Arr::get($this->overflowStorage, 'enabled')
                && Arr::get($this->overflowStorage, 'flush_on_clear')) {
                $this->container->make('cache')->store(
                    Arr::get($this->overflowStorage, 'store')
                )->flush();
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
