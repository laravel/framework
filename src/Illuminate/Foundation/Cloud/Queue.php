<?php

namespace Illuminate\Foundation\Cloud;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use RuntimeException;

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
     * By default the worker receives the job straight from SQS via the
     * underlying queue, exactly as a non-managed SQS connection would. When the
     * cloud-agent is enabled (config: agent.enabled) the message has instead
     * already been received from SQS by the dispatcher and handed to this pod's
     * in-container cloud-agent, which holds it and extends its visibility; in
     * that mode we long-poll the agent's runtime socket rather than SQS.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $this->finishProcessingJob();

        $job = $this->usesAgent()
            ? $this->popFromAgent($queue)
            : $this->queue->pop(...func_get_args());

        $this->startProcessingJob($queue, $job);

        return $job;
    }

    /**
     * Determine whether jobs should be popped from the in-container cloud-agent
     * rather than received directly from SQS.
     */
    protected function usesAgent(): bool
    {
        return (bool) ($this->config['agent']['enabled'] ?? false);
    }

    /**
     * Long-poll the in-container cloud-agent's runtime socket (GET /next) and
     * wrap the next message in a CloudJob.
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
    protected function popFromAgent($queue = null)
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
                // Coerce a non-string body to '' so a malformed agent
                // response degrades to an empty payload rather than blowing
                // up json_decode() in payload().
                'Body' => is_string($body = $data['body'] ?? null) ? $body : '',
                'Attributes' => $data['attributes'] ?? [],
            ],
            $this->queue->getConnectionName(),
            // The agent reports the real SQS queue URL the dispatcher received
            // from; delete / release go back to that queue, not our default.
            $data['queueUrl'] ?? $this->queue->getQueue($queue),
            // Capture only the message id so the closure doesn't pin the
            // whole agent response (including the body) for the job's life.
            fn (string $status, ?int $delay): bool => $this->reportResultToAgent($messageId, $status, $delay),
            $this->config['connection']['overflow'] ?? [],
        );
    }

    /**
     * Long-poll the agent's runtime socket (GET /next) for the next job.
     *
     * Returns the decoded message payload, or null when the agent has nothing
     * (HTTP 204) or returns an unexpected status — in which case the worker
     * idles and re-polls, with the unexpected status reported so an agent
     * fault is visible instead of looking like an empty queue.
     *
     * An unreachable socket is different: it means the agent is not running in
     * the pod (or is wedged past its poll cycle), which is unrecoverable. We let
     * that surface as an AgentUnreachableException — which the worker treats as
     * a lost connection and exits on — so the pod is restarted rather than the
     * worker spinning forever as if the queue were simply empty.
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

        if ($response->failed()) {
            report(new RuntimeException(
                "The Laravel Cloud agent returned HTTP {$response->status()} from GET /next."
            ));

            return null;
        }

        if (! is_array($data = $response->json())) {
            // A 200 with a body we can't decode into a message is an agent
            // fault, not an empty queue — report it rather than idling silently.
            report(new RuntimeException(
                'The Laravel Cloud agent returned a non-array body from GET /next.'
            ));

            return null;
        }

        return $data;
    }

    /**
     * Report a job's terminal outcome back to the agent (POST /result) so it
     * can stop heartbeating the message and accept the next invoke. The poller
     * performs the SQS operation; on a release the delay (in whole seconds)
     * tells it the visibility to reset the message to.
     *
     * Reporting is retried for transient socket hiccups. The two failure modes
     * are then handled differently:
     *
     *  - The agent responds but rejects the report (an HTTP error). It is alive
     *    and its poller still owns the message, so we must not touch SQS
     *    ourselves: the error is reported and we return false, leaving the
     *    retry / teardown safety net to try again.
     *  - The agent is unreachable (a connection error). It has crashed, so its
     *    poller can no longer act on the message at all. We surface an
     *    AgentUnreachableException so the job falls back to operating on SQS
     *    directly rather than lose its outcome. (This is the same fatal
     *    condition GET /next escalates to a worker exit; here we can still
     *    salvage the in-flight job first.)
     *
     * Returns whether the agent accepted the report.
     *
     * @throws \Illuminate\Foundation\Cloud\AgentUnreachableException
     */
    protected function reportResultToAgent(string $messageId, string $status, ?int $delay = null): bool
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

            return true;
        } catch (ConnectionException $e) {
            throw new AgentUnreachableException(
                'The Laravel Cloud agent runtime socket is unreachable.', previous: $e
            );
        } catch (RequestException $e) {
            report($e);

            return false;
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

        $type = match (true) {
            $this->processingJob->hasFailed() => 'failed',
            $this->processingJob->isReleased() => 'released',
            default => $default,
        };

        $this->events->emit([
            '_cloud_event' => 'queue',
            'timestamp' => $timestamp->toDateTimeString('microsecond'),
            'type' => $type,
            'queue' => $this->processingQueue,
            'duration_ms' => (int) $this->processingJobStartedAt->diffInMilliseconds($timestamp),
        ]);

        // Make sure the agent learns the outcome even when the worker is torn
        // down mid-job (timeout, fatal error) and the normal delete()/release()
        // reporting never ran. This is a no-op once the job has reported, so the
        // common path adds no extra request.
        if ($this->processingJob instanceof CloudJob) {
            $this->processingJob->reportToAgent($type === 'released' ? 'released' : 'processed');
        }

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
