<?php

namespace Illuminate\Foundation\Cloud;

use Carbon\CarbonImmutable;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;

class Queue implements QueueContract, ClearableQueue
{
    use ForwardsCalls;

    /**
     * The currently processing job.
     *
     * @var \Illuminate\Contracts\Queue\Job|null
     */
    protected $processingJob = null;

    /**
     * The queue for the currently processing job.
     *
     * @var string|null
     */
    protected $processingQueue = null;

    /**
     * The date the last job started processing.
     *
     * @var \Carbon\CarbonImmutable
     */
    protected $processingJobStartedAt = null;

    /**
     * Create a new Queue instance.
     */
    public function __construct(
        protected QueueContract $queue,
        protected Events $events,
        protected array $config,
    ) {
        //
    }

    /**
     * Get the size of the queue.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return $this->queue->size(...func_get_args());
    }

    /**
     * Get the number of pending jobs.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function pendingSize($queue = null)
    {
        return $this->queue->pendingSize(...func_get_args());
    }

    /**
     * Get the number of delayed jobs.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function delayedSize($queue = null)
    {
        return $this->queue->delayedSize(...func_get_args());
    }

    /**
     * Get the number of reserved jobs.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function reservedSize($queue = null)
    {
        return $this->queue->reservedSize(...func_get_args());
    }

    /**
     * Get the creation timestamp of the oldest pending job, excluding delayed jobs.
     *
     * @param  string|null  $queue
     * @return int|null
     */
    public function creationTimeOfOldestPendingJob($queue = null)
    {
        return $this->queue->creationTimeOfOldestPendingJob(...func_get_args());
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->queue->push(...func_get_args());
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $queue
     * @param  string|object  $job
     * @param  mixed  $data
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '')
    {
        return $this->queue->pushOn(...func_get_args());
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $result = $this->queue->pushRaw(...func_get_args());

        $this->finishQueueingJob($queue);

        return $result;
    }

    /**
     * Push a new job onto the queue after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->queue->later(...func_get_args());
    }

    /**
     * Push a new job onto a specific queue after (n) seconds.
     *
     * @param  string  $queue
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|object  $job
     * @param  mixed  $data
     * @return mixed
     */
    public function laterOn($queue, $delay, $job, $data = '')
    {
        return $this->queue->laterOn(...func_get_args());
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        return $this->queue->bulk(...func_get_args());
    }

    /**
     * Pop the next job off of the queue.
     *
     * The message has already been received from SQS by the dispatcher and
     * handed to this pod's in-container cloud-agent, which holds it and extends
     * its visibility. Rather than receive from SQS ourselves we long-poll the
     * agent's runtime socket (GET /next) and wrap the message in a CloudJob.
     *
     * The agent blocks until a job is available or returns 204 so we re-poll,
     * exactly like SQS long polling. The returned CloudJob never touches SQS
     * itself; it reports the outcome (and, for a release, the requested delay)
     * back to the agent via POST /result, and the poller owns the terminal SQS
     * operation.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Foundation\Cloud\CloudJob|null
     */
    public function pop($queue = null)
    {
        $this->finishProcessingJob();

        $response = $this->agentClient()->get('/next', [
            // Outlast the agent's ~55s poll cycle so a 204 is a deliberate
            // "nothing yet", never our own timeout cutting a poll short.
            'timeout' => 65,
        ]);

        $data = $response->getStatusCode() === 204
            ? null
            : json_decode((string) $response->getBody(), true);

        $job = is_array($data) && ! empty($data['messageId'])
            ? new CloudJob(
                $this->queue->getContainer(),
                $this->queue->getSqs(),
                [
                    'MessageId' => $data['messageId'],
                    'ReceiptHandle' => $data['receiptHandle'] ?? null,
                    'Body' => $data['body'] ?? '',
                    'Attributes' => ($data['attributes'] ?? []) + ['ApproximateReceiveCount' => '1'],
                ],
                $this->queue->getConnectionName(),
                // The agent reports the real SQS queue URL the dispatcher received
                // from; delete / release go back to that queue, not our default.
                $data['queueUrl'] ?? $this->queue->getQueue($queue),
                fn (string $status, ?string $error, ?int $delay) => $this->reportResultToAgent($data['messageId'], $status, $error, $delay),
            )
            : null;

        $this->startProcessingJob($queue, $job);

        return $job;
    }

    /**
     * Report a job's terminal outcome back to the agent (POST /result) so it
     * can stop heartbeating the message and accept the next invoke. The poller
     * performs the SQS operation; on a release the delay (in whole seconds)
     * tells it the visibility to reset the message to.
     */
    protected function reportResultToAgent(string $messageId, string $status, ?string $error, ?int $delay = null): void
    {
        $this->agentClient()->post('/result', [
            'json' => array_filter([
                'messageId' => $messageId,
                'status' => $status,
                'error' => $error,
                'delay' => $delay,
            ], fn ($value) => $value !== null),
            'timeout' => 10,
        ]);
    }

    /**
     * A Guzzle client bound to the agent's unix runtime socket, so callers just
     * pass the path (GET /next, POST /result).
     */
    protected function agentClient(): HttpClient
    {
        return new HttpClient([
            'base_uri' => 'http://localhost',
            'http_errors' => false,
            'curl' => [
                CURLOPT_UNIX_SOCKET_PATH => '/tmp/cloud-agent.sock',
            ],
        ]);
    }

    /**
     * Delete all of the jobs from the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function clear($queue)
    {
        return $this->queue->clear(...func_get_args());
    }

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->queue->getConnectionName();
    }

    /**
     * Set the connection name for the queue.
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName($name)
    {
        $this->queue->setConnectionName(...func_get_args());

        return $this;
    }

    /**
     * Set the queue configuration array.
     *
     * @param  array  $config
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        $this->queue->setConfig($config['connection']);

        return $this;
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
        if (! method_exists($this->queue, 'getQueueableOptions')) {
            return [];
        }

        return $this->queue->getQueueableOptions(...func_get_args());
    }

    /**
     * Finish processing the current job and emit a queue event.
     *
     * @param  string  $default
     * @param  \Carbon\CarbonImmutable|null  $timestamp
     * @return void
     */
    public function finishProcessingJob($default = 'processed', $timestamp = null)
    {
        if (! $this->processingJob) {
            return;
        }

        $timestamp ??= CarbonImmutable::now('UTC');

        $this->events->emit([
            '_cloud_event' => 'queue',
            'timestamp' => $timestamp->toDateTimeString('microsecond'),
            'type' => match (true) {
                $this->processingJob->hasFailed() => 'failed',
                $this->processingJob->isReleased() => 'released',
                default => $default,
            },
            'queue' => $this->processingQueue,
            'duration_ms' => (int) $this->processingJobStartedAt->diffInMilliseconds($timestamp),
        ]);

        $this->processingQueue
            = $this->processingJob
            = $this->processingJobStartedAt
            = null;
    }

    /**
     * Last job details resolver.
     *
     * @return array{queue: string, attempts: int, started_at: CarbonImmutable}
     */
    public function processingJobDetails()
    {
        return [
            'queue' => $this->processingQueue,
            'attempts' => $this->processingJob->attempts(),
            'started_at' => $this->processingJobStartedAt,
        ];
    }

    /**
     * Handle jobs finishing being queued.
     *
     * @param  string  $queue
     */
    public function finishQueueingJob($queue)
    {
        $this->events->emit([
            '_cloud_event' => 'queue',
            'timestamp' => CarbonImmutable::now('UTC')->toDateTimeString('microsecond'),
            'type' => 'queued',
            'queue' => $this->normalizeQueue($queue),
        ]);
    }

    /**
     * Handle a job being popped.
     *
     * @param  string|null  $queue
     * @param  \Illuminate\Contracts\Queue\Job|null  $job
     * @return void
     */
    protected function startProcessingJob($queue, $job)
    {
        if (! $job) {
            return;
        }

        $this->processingJob = $job;
        $this->processingQueue = $this->normalizeQueue($queue);
        $this->processingJobStartedAt = CarbonImmutable::now('UTC');

        $this->events->emit([
            '_cloud_event' => 'queue',
            'timestamp' => $this->processingJobStartedAt->toDateTimeString('microsecond'),
            'type' => 'started',
            'queue' => $this->processingQueue,
        ]);
    }

    /**
     * Normalize the queue name.
     *
     * @param  string|null  $queue
     * @return string
     */
    public function normalizeQueue($queue)
    {
        $prefix = $this->config['connection']['prefix'] ?? null;
        $suffix = $this->config['connection']['suffix'] ?? null;

        return Str::of($this->queue->getQueue($queue))
            ->when($prefix, fn ($str) => $str->chopStart($prefix.'/'))
            ->when($suffix, fn ($str) => $str->endsWith('.fifo')
                ? $str->chopEnd('.fifo')->chopEnd($suffix)->append('.fifo')
                : $str->chopEnd($suffix))
            ->toString();
    }

    /**
     * Dynamically pass method calls to the underlying queue.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardDecoratedCallTo($this->queue, $method, $parameters);
    }
}
