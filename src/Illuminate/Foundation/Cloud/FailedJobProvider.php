<?php

namespace Illuminate\Foundation\Cloud;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Encryption\StringEncrypter;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\Failed\CountableFailedJobProvider;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Queue\Failed\PrunableFailedJobProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Iterator;
use RuntimeException;
use Throwable;

class FailedJobProvider implements FailedJobProviderInterface, CountableFailedJobProvider, PrunableFailedJobProvider
{
    /**
     * The connected queue instance.
     *
     * @var ?\Illuminate\Foundation\Cloud\Queue
     */
    protected $queue = null;

    /**
     * The loaded failed jobs keyed by ID.
     *
     * @var array<string, object>
     */
    protected $loadedFailedJobs = [];

    /**
     * Create a new instance.
     */
    public function __construct(
        protected FailedJobProviderInterface $failer,
        protected Events $events,
        protected StringEncrypter $encrypter,
    ) {
        //
    }

    /**
     * Log a failed job into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  \Throwable  $exception
     * @return string|null
     */
    public function log($connection, $queue, $payload, $exception)
    {
        if ($connection !== 'cloud') {
            return $this->failer->log(...func_get_args());
        }

        if ($this->queue === null) {
            throw new RuntimeException('The failed job provider does not have a configured queue');
        }

        $timestamp = CarbonImmutable::now('UTC');
        $processingJobDetails = $this->queue->processingJobDetails();

        $this->events->emit([
            '_cloud_event' => 'failed_job',
            'id' => $id = Str::uuid7($timestamp)->toString(),
            'queue' => $processingJobDetails['queue'],
            'started_at' => $processingJobDetails['started_at']->toDateTimeString('microsecond'),
            'attempts' => $processingJobDetails['attempts'],
            'payload' => $payload,
            'exception_preview' => mb_substr(
                string: $exception->getMessage()
                    ? $exception::class.': '.$exception->getMessage().' in '.$exception->getFile().':'.$exception->getLine()
                    : $exception::class.' in '.$exception->getFile().':'.$exception->getLine(),
                start: 0,
                length: 1001,
                encoding: 'UTF-8',
            ),
            'job_name' => (json_decode($payload, associative: true) ?? [])['displayName'] ?? '',
            'exception' => (string) $exception,
        ]);

        $this->queue->finishProcessingJob(timestamp: $timestamp);

        return $id;
    }

    /**
     * Get the IDs of all of the failed jobs.
     *
     * @param  string|null  $queue
     * @return array
     */
    public function ids($queue = null)
    {
        return $this->failer->ids(...func_get_args());
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all()
    {
        return $this->failer->all(...func_get_args());
    }

    /**
     * Get a single failed job.
     *
     * @param  mixed  $id
     * @return object|null
     */
    public function find($id)
    {
        if (! str_starts_with($id, 'https://')) {
            return $this->failer->find($id);
        }

        $iterator = $this->failedJobsIterator($id);

        return new LazyCollection(static fn () => yield from $iterator);
    }

    /**
     * Get an iterator for the failed jobs from the given URL.
     */
    protected function failedJobsIterator(string $url): Iterator
    {
        $payload = $this->resolveFailedJobsPayload($url);

        while ($job = array_shift($payload->data)) {
            $key = $payload->links->self.':'.$job->id;

            $this->loadedFailedJobs[$key] = $job;

            yield $key => $job;

            if ($payload->data === [] && $payload->links->next !== null) {
                $payload = $this->resolveFailedJobsPayload($payload->links->next);
            }
        }
    }

    /**
     * Resolve the failed jobs payload for the given URL.
     */
    protected function resolveFailedJobsPayload(string $url): object
    {
        $response = $this->fetchFailedJobs($url);

        $payload = json_decode(
            json: $this->encrypter->decryptString($response->body()),
            associative: false,
            flags: JSON_THROW_ON_ERROR,
        );

        return match ($response->header('Cloud-Payload-Version')) {
            '1' => $payload,
            default => literal(
                data: [$payload],
                links: literal(
                    self: $url,
                    next: null,
                ),
            ),
        };
    }

    /**
     * Fetch the failed jobs from the given URL.
     */
    protected function fetchFailedJobs(string $url): Response
    {
        // without global
        return Http::connectTimeout(10)
            ->timeout(10)
            ->retry(5, fn ($attempt) => $attempt * 500, when: function (Throwable $e) {
                if ($e instanceof RequestException && $e->response->status() === 403) {
                    return false;
                }

                return true;
            })
            ->withHeaders([
                'Cloud-Payload-Version' => '1',
                'Cloud-Encryption-Cipher' => Config::get('app.cipher'),
            ])
            ->throw()
            ->get($url);
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id)
    {
        if (! str_starts_with($id, 'https://')) {
            return $this->failer->forget($id);
        }

        if (is_null($job = $this->loadedFailedJobs[$id] ?? null)) {
            return false;
        }

        unset($this->loadedFailedJobs[$id]);

        return $this->events->emit([
            '_cloud_event' => 'failed_job',
            'id' => $job->id,
            'queue' => $job->queue,
            'retried_at' => CarbonImmutable::now('UTC')->toDateTimeString('microsecond'),
        ]);
    }

    /**
     * Flush all of the failed jobs from storage.
     *
     * @param  int|null  $hours
     * @return void
     */
    public function flush($hours = null)
    {
        $this->failer->flush(...func_get_args());
    }

    /**
     * Count the failed jobs.
     *
     * @param  string|null  $connection
     * @param  string|null  $queue
     * @return int
     */
    public function count($connection = null, $queue = null)
    {
        if (! $this->failer instanceof CountableFailedJobProvider) {
            return 0;
        }

        return $this->failer->count(...func_get_args());
    }

    /**
     * Prune all of the entries older than the given date.
     *
     * @param  \DateTimeInterface  $before
     * @return int
     */
    public function prune(DateTimeInterface $before)
    {
        if (! $this->failer instanceof PrunableFailedJobProvider) {
            return 0;
        }

        return $this->failer->prune(...func_get_args());
    }

    /**
     * Set the connected queue instance.
     *
     * @param  \Illuminate\Foundation\Cloud\Queue  $queue
     * @return $this
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }
}
