<?php

namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Attributes\Delay;
use Illuminate\Queue\Jobs\CloudflareJob;
use Illuminate\Support\Collection;

use function Illuminate\Support\enum_value;

class CloudflareQueue extends Queue implements QueueContract, ClearableQueue
{
    /**
     * Cloudflare's hard maximum for delay_seconds on a single message (12 hours).
     *
     * @var int
     */
    const MAX_DELAY_SECONDS = 43_200;

    /**
     * Marker key embedded in the message body when a job needs more than one delay hop.
     *
     * @var string
     */
    const DELAY_WRAPPER_KEY = '__cf_delay_wrapper';

    /**
     * The maximum number of messages allowed per batch send request.
     *
     * @var int
     */
    const MAX_MESSAGES_PER_BATCH = 100;

    /**
     * In-memory buffer of messages pulled in the last batch.
     *
     * @var array<int, array<string, mixed>>
     */
    protected $messageBuffer = [];

    /**
     * Create a new Cloudflare queue instance.
     */
    public function __construct(
        protected CloudflareQueueClient $client,
        protected $default,
        $dispatchAfterCommit = false,
        protected int $batchSize = 1,
        protected int $visibilityTimeoutMs = 30_000,
    ) {
        $this->dispatchAfterCommit = $dispatchAfterCommit;
        $this->batchSize = max(1, $this->batchSize);
        $this->visibilityTimeoutMs = max(1_000, $this->visibilityTimeoutMs);
    }

    /**
     * Get the size of the queue.
     *
     * @param  \UnitEnum|string|null  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return $this->pendingSize($queue);
    }

    /**
     * Get the number of pending jobs.
     *
     * @param  \UnitEnum|string|null  $queue
     * @return int
     */
    public function pendingSize($queue = null)
    {
        $info = $this->client->info();

        return (int) ($info['message_backlog_count'] ?? $info['backlog_count'] ?? 0);
    }

    /**
     * Get the number of delayed jobs.
     *
     * @param  \UnitEnum|string|null  $queue
     * @return int
     */
    public function delayedSize($queue = null)
    {
        return 0;
    }

    /**
     * Get the number of reserved jobs.
     *
     * @param  \UnitEnum|string|null  $queue
     * @return int
     */
    public function reservedSize($queue = null)
    {
        return 0;
    }

    /**
     * Get the number of jobs across every queue.
     *
     * @return int
     */
    public function totalSize()
    {
        return 0;
    }

    /**
     * Get the number of pending jobs across every queue.
     *
     * @return int
     */
    public function totalPendingSize()
    {
        return 0;
    }

    /**
     * Get the number of delayed jobs across every queue.
     *
     * @return int
     */
    public function totalDelayedSize()
    {
        return 0;
    }

    /**
     * Get the number of reserved jobs across every queue.
     *
     * @return int
     */
    public function totalReservedSize()
    {
        return 0;
    }

    /**
     * Get the pending jobs for the given queue.
     *
     * @param  \UnitEnum|string|null  $queue
     * @return \Illuminate\Support\Collection
     */
    public function pendingJobs($queue = null): Collection
    {
        return new Collection;
    }

    /**
     * Get the delayed jobs for the given queue.
     *
     * @param  \UnitEnum|string|null  $queue
     * @return \Illuminate\Support\Collection
     */
    public function delayedJobs($queue = null): Collection
    {
        return new Collection;
    }

    /**
     * Get the reserved jobs for the given queue.
     *
     * @param  \UnitEnum|string|null  $queue
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
     * @param  \UnitEnum|string|null  $queue
     * @return int|null
     */
    public function creationTimeOfOldestPendingJob($queue = null)
    {
        $info = $this->client->info();

        $timestampMs = $info['oldest_message_timestamp_ms'] ?? null;

        if (! is_numeric($timestampMs) || (int) $timestampMs <= 0) {
            return null;
        }

        return (int) floor(((int) $timestampMs) / 1000);
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed  $data
     * @param  \UnitEnum|string|null  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $this->getQueue($queue), $data),
            $queue,
            null,
            fn ($payload, $queue) => $this->pushRaw($payload, $queue),
        );
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  \UnitEnum|string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $delay = (int) ($options['delay'] ?? $options['DelaySeconds'] ?? 0);

        $originalUuid = json_decode($payload, true)['uuid'] ?? null;

        [$payload, $delay] = $this->preparePayloadDelay($payload, $delay);

        $this->client->send($payload, $delay);

        return $originalUuid;
    }

    /**
     * Push a new job onto the queue after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed  $data
     * @param  \UnitEnum|string|null  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $this->getQueue($queue), $data, $delay),
            $queue,
            $delay,
            fn ($payload, $queue, $delay) => $this->pushRaw($payload, $queue, [
                'delay' => $this->secondsUntil($delay),
            ]),
        );
    }

    /**
     * Push an array of jobs onto the queue using the batch API.
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  \UnitEnum|string|null  $queue
     * @return void
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        $jobs = array_values((array) $jobs);

        if ($jobs === []) {
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
     * Create the payload for each of the given jobs.
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  \UnitEnum|string|null  $queue
     * @return array<int, array{job: mixed, delay: mixed, payload: string, job_id: string|null}>
     */
    protected function prepareBatchMessages(array $jobs, $data, $queue)
    {
        return (new Collection($jobs))
            ->map(function ($job) use ($data, $queue) {
                $delay = is_object($job) ? $this->getAttributeValue($job, Delay::class, 'delay') : null;

                $payload = $this->createPayload($job, $this->getQueue($queue), $data, $delay);

                return [
                    'job' => $job,
                    'delay' => $delay,
                    'payload' => $payload,
                    'job_id' => json_decode($payload, true)['uuid'] ?? null,
                ];
            })
            ->all();
    }

    /**
     * Build messages, raise queueing events, dispatch chunks, and raise queued events.
     *
     * @param  array<int, array{job: mixed, delay: mixed, payload: string, job_id: string|null}>  $messages
     * @param  \UnitEnum|string|null  $queue
     * @return void
     */
    protected function sendBatchedMessages(array $messages, $queue)
    {
        $entries = [];

        foreach ($messages as $id => $message) {
            $this->raiseJobQueueingEvent($queue, $message['job'], $message['payload'], $message['delay']);

            $delaySeconds = ! empty($message['delay']) ? $this->secondsUntil($message['delay']) : 0;

            [$payload, $delaySeconds] = $this->preparePayloadDelay($message['payload'], $delaySeconds);

            $entries[$id] = [
                'body' => $payload,
                'delay_seconds' => $delaySeconds,
                'job_id' => $message['job_id'],
                'job' => $message['job'],
                'payload' => $message['payload'],
                'delay' => $message['delay'],
            ];
        }

        foreach (array_chunk($entries, static::MAX_MESSAGES_PER_BATCH, true) as $chunk) {
            $this->client->bulkSend(array_map(
                fn ($entry) => [
                    'body' => $entry['body'],
                    'delay_seconds' => $entry['delay_seconds'],
                ],
                $chunk,
            ));

            foreach ($chunk as $entry) {
                $this->raiseJobQueuedEvent(
                    $queue,
                    $entry['job_id'],
                    $entry['job'],
                    $entry['payload'],
                    $entry['delay'],
                );
            }
        }
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  \UnitEnum|string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        if ($this->messageBuffer === []) {
            $this->messageBuffer = $this->client->pull(
                $this->batchSize,
                $this->visibilityTimeoutMs,
            );
        }

        if ($this->messageBuffer === []) {
            return null;
        }

        $message = array_shift($this->messageBuffer);

        $message = $this->resolveDelayWrapper($message);

        if ($message === null) {
            return $this->pop($queue);
        }

        return new CloudflareJob(
            $this->container,
            $this->client,
            $message,
            $this->connectionName,
            $this->getQueue($queue),
        );
    }

    /**
     * Delete all of the jobs from the queue.
     *
     * @param  \UnitEnum|string|null  $queue
     * @return int
     */
    public function clear($queue = null)
    {
        return tap($this->size($queue), function () {
            $this->client->purge();
        });
    }

    /**
     * Get the queue or return the default.
     *
     * @param  \UnitEnum|string|null  $queue
     * @return string
     */
    public function getQueue($queue)
    {
        $queue = enum_value($queue) ?: $this->default;

        return $this->resolveQueue($queue);
    }

    /**
     * Prepare a payload and delay for sending, wrapping long delays when needed.
     *
     * @return array{0: string, 1: int}
     */
    protected function preparePayloadDelay(string $payload, int $delay): array
    {
        if ($delay > static::MAX_DELAY_SECONDS) {
            $payload = $this->wrapForDelayedHop($payload, $this->currentTime() + $delay);
            $delay = static::MAX_DELAY_SECONDS;
        }

        return [$payload, $delay];
    }

    /**
     * Inspect a pulled message for a delay-relay wrapper.
     *
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>|null
     */
    protected function resolveDelayWrapper(array $message): ?array
    {
        $body = json_decode($message['body'], true);

        if (! is_array($body) || ! isset($body[static::DELAY_WRAPPER_KEY])) {
            return $message;
        }

        $remaining = (int) $body['__cf_execute_at'] - $this->currentTime();

        if ($remaining > 0) {
            $nextDelay = min($remaining, static::MAX_DELAY_SECONDS);

            if ($remaining <= static::MAX_DELAY_SECONDS) {
                $this->client->send($body['__cf_payload'], $nextDelay);
            } else {
                $this->client->send(
                    $this->wrapForDelayedHop($body['__cf_payload'], (int) $body['__cf_execute_at']),
                    $nextDelay,
                );
            }

            $this->client->ack([$message['lease_id']]);

            return null;
        }

        $message['body'] = $body['__cf_payload'];

        return $message;
    }

    /**
     * Wrap a payload in a delay-relay envelope for multi-hop delivery.
     */
    protected function wrapForDelayedHop(string $payload, int $executeAt): string
    {
        return json_encode([
            static::DELAY_WRAPPER_KEY => true,
            '__cf_execute_at' => $executeAt,
            '__cf_payload' => $payload,
        ]);
    }
}
