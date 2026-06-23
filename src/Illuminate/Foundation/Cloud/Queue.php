<?php

namespace Illuminate\Foundation\Cloud;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Symfony\Component\Console\Input\ArgvInput;

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
        protected Application $app,
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
     * Jobs come straight from SQS unless the cloud-agent is enabled, in which
     * case we long-poll the agent's runtime socket instead.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $this->finishProcessingJob();

        $job = $this->usesAgent($queue)
            ? $this->popFromAgent()
            : $this->queue->pop(...func_get_args());

        $this->startProcessingJob($queue, $job);

        return $job;
    }

    /**
     * Determine whether the next job should be received from the in-container
     * cloud-agent rather than directly from SQS.
     *
     * The agent is a sidecar that long-polls a single queue on behalf of one
     * queue:work worker, so it only serves work when that worker is popping the
     * queue it was started for. A pop for any other queue - or one outside a
     * queue:work worker - has no agent feeding it and falls back to SQS.
     *
     * @param  string|null  $queue
     */
    protected function usesAgent($queue = null): bool
    {
        return ($this->config['agent']['enabled'] ?? false)
            && $this->app->runningConsoleCommand('queue:work')
            && $this->queue->getQueue($queue) === $this->queue->getQueue($this->workerQueue());
    }

    /**
     * Get the queue the running queue:work worker is processing: its --queue
     * option, or null (the connection default) when the option is omitted.
     *
     * The agent serves a single queue, so a managed worker is expected to run
     * one too. The raw option is returned as-is: a comma-separated --queue (the
     * worker splits these and pops each individually) won't match any single
     * popped queue, so such a worker simply falls back to SQS.
     *
     * @return string|null
     */
    protected function workerQueue()
    {
        return (new ArgvInput)->getParameterOption('--queue') ?: null;
    }

    /**
     * Long-poll the cloud-agent's runtime socket and wrap the next message in a
     * CloudJob.
     *
     * The agent only ever receives messages, so it has no queue to pop from;
     * each message reports the SQS queue URL it came from instead.
     *
     * @return \Illuminate\Foundation\Cloud\CloudJob|null
     */
    protected function popFromAgent()
    {
        $data = $this->requestNextFromAgent();

        if (! (is_array($data) && is_string($messageId = $data['messageId'] ?? null) && $messageId !== '')) {
            return null;
        }

        return new CloudJob(
            $this->queue->getContainer(),
            $this->queue->getSqs(),
            [
                'MessageId' => $messageId,
                'ReceiptHandle' => $data['receiptHandle'] ?? null,
                // Coerce a non-string body to '' so a malformed response degrades
                // to an empty payload rather than blowing up json_decode().
                'Body' => is_string($body = $data['body'] ?? null) ? $body : '',
                'Attributes' => $data['attributes'] ?? [],
            ],
            $this->queue->getConnectionName(),
            // The agent reports the real SQS queue URL the message came from;
            // delete / release go back to that queue, not our default.
            $data['queueUrl'] ?? null,
            // Capture only the message id so the closure doesn't pin the whole
            // agent response for the job's life.
            fn (string $status, ?int $delay) => $this->reportResultToAgent($messageId, $status, $delay),
            $this->config['connection']['overflow'] ?? [],
        );
    }

    /**
     * Long-poll the agent's runtime socket (GET /next) for the next job.
     *
     * Returns the decoded message payload, or null when the agent has nothing
     * (HTTP 204). The agent only ever answers 200 with a job or 204 when empty,
     * so an unreachable socket, any other status, or a malformed body all mean
     * the agent cannot serve work — a crashed or wedged agent — and each
     * surfaces as an AgentUnreachableException to restart the pod rather than
     * spin re-polling a broken agent forever.
     *
     * @throws \Illuminate\Foundation\Cloud\AgentUnreachableException
     */
    protected function requestNextFromAgent(): ?array
    {
        try {
            $response = $this->agentRequest()
                // Outlast the agent's ~55s poll cycle so a 204 is a deliberate
                // "nothing yet", never our own timeout cutting a poll short.
                ->timeout(65)
                ->get('/next');
        } catch (ConnectionException $e) {
            throw new AgentUnreachableException(
                'The Laravel Cloud agent runtime socket is unreachable.', previous: $e
            );
        }

        if ($response->status() === 204) {
            return null;
        }

        if (! $response->ok()) {
            throw new AgentUnreachableException(
                "The Laravel Cloud agent returned HTTP {$response->status()} from GET /next."
            );
        }

        if (! is_array($data = $response->json())) {
            throw new AgentUnreachableException(
                'The Laravel Cloud agent returned a non-array body from GET /next.'
            );
        }

        return $data;
    }

    /**
     * Report a job's terminal outcome back to the agent (POST /result) so it
     * can stop heartbeating the message and the poller can perform the SQS
     * operation. On a release the delay (in whole seconds) is the visibility to
     * reset the message to.
     *
     * The request is retried for transient socket hiccups. A client-error
     * rejection (the agent is the authority on a valid outcome for this one
     * message) propagates as a RequestException so the worker reports it and
     * moves on. An unreachable socket or a server error means the agent itself
     * is wedged, so both raise an AgentUnreachableException to exit the worker
     * and restart the pod. The job is never lost — a crashed agent stops
     * heartbeating, so SQS redelivers once the visibility timeout lapses.
     *
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Illuminate\Foundation\Cloud\AgentUnreachableException
     */
    protected function reportResultToAgent(string $messageId, string $status, ?int $delay = null): void
    {
        try {
            $this->agentRequest()
                ->timeout(10)
                ->throw()
                ->retry(3, 100)
                ->post('/result', array_filter([
                    'messageId' => $messageId,
                    'status' => $status,
                    'delay' => $delay,
                ], fn ($value) => $value !== null));
        } catch (ConnectionException $e) {
            throw new AgentUnreachableException(
                'The Laravel Cloud agent runtime socket is unreachable.', previous: $e
            );
        } catch (RequestException $e) {
            if ($e->response->serverError()) {
                throw new AgentUnreachableException(
                    "The Laravel Cloud agent returned HTTP {$e->response->status()} from POST /result.", previous: $e
                );
            }

            throw $e;
        }
    }

    /**
     * A pending HTTP request bound to the agent's unix runtime socket, so
     * callers just pass the path (GET /next, POST /result).
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function agentRequest()
    {
        return Http::baseUrl('http://localhost')->withOptions([
            'curl' => [
                CURLOPT_UNIX_SOCKET_PATH => $this->config['agent']['socket'] ?? '/tmp/cloud-agent.sock',
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
