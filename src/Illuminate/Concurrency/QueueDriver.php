<?php

namespace Illuminate\Concurrency;

use Carbon\CarbonInterval;
use Closure;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Concurrency\Driver;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Defer\DeferredCallback;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use RuntimeException;
use Throwable;

use function Illuminate\Support\defer;
use function Illuminate\Support\enum_value;

class QueueDriver implements Driver
{
    /**
     * Create a new queue based concurrency driver.
     */
    public function __construct(
        protected Dispatcher $bus,
        protected CacheFactory $cache,
        protected ConfigRepository $config,
        protected array $options = [],
    ) {
        //
    }

    /**
     * Run the given tasks concurrently and return an array containing the results.
     *
     * @throws \Throwable
     */
    public function run(Closure|array $tasks, CarbonInterval|int|null $timeout = null): array
    {
        $tasks = Arr::wrap($tasks);

        if ($tasks === []) {
            return [];
        }

        $connection = $this->connection();
        $store = $this->resolveStore();
        $inline = $this->queueConnectionDriver($connection) === 'sync';

        $this->ensureQueueConnectionIsSupported($connection);
        $this->ensureStoreIsSupported($store, $inline);

        $timeout = $this->normalizeTimeout($timeout);
        $ttl = max((int) ($this->options['ttl'] ?? 0), $timeout + 60);

        $startedAt = Carbon::now();
        $runId = (string) Str::ulid();
        $cancellationKey = $this->cancellationKey($runId);
        $repository = $this->cache->store($store);

        [$keys, $inlineEnvelopes] = $this->dispatchTasks(
            $repository, $tasks, $runId, $cancellationKey, $connection, $store, $inline, $timeout, $ttl, $startedAt,
        );

        $envelopes = $inline
            ? $this->ensureInlineEnvelopes($repository, $keys, $inlineEnvelopes)
            : $this->wait(
                $repository, $keys, $cancellationKey, $startedAt, $timeout, $ttl, $connection, $store,
            );

        return $this->resolveResults($repository, $tasks, $keys, $cancellationKey, $envelopes);
    }

    /**
     * Dispatch one queued job per task, collecting inline results as they run.
     *
     * @throws \Throwable
     */
    protected function dispatchTasks(
        CacheRepository $repository,
        array $tasks,
        string $runId,
        string $cancellationKey,
        ?string $connection,
        ?string $store,
        bool $inline,
        int $timeout,
        int $ttl,
        Carbon $startedAt,
    ): array {
        $deadline = $startedAt->getTimestamp() + $timeout;

        $keys = [];
        $inlineEnvelopes = [];

        try {
            foreach (array_values($tasks) as $index => $task) {
                $keys[$index] = $this->resultKey($runId, $index);

                $job = new InvokeQueuedClosure(
                    $runId,
                    $keys[$index],
                    $cancellationKey,
                    $store,
                    $ttl,
                    $deadline,
                    ! $inline,
                    new SerializableClosure($task),
                );

                $job->timeout = $timeout;

                $job->onConnection($connection);

                if (! is_null($queue = $this->queue())) {
                    $job->onQueue($queue);
                }

                $this->bus->dispatch($job);

                // Inline execution is not bounded by the timeout, so each
                // envelope is collected right away instead of relying on
                // its expiry outliving the remaining tasks.
                if ($inline) {
                    $inlineEnvelopes[$keys[$index]] = $repository->get($keys[$index]);
                }
            }
        } catch (Throwable $e) {
            $repository->put($cancellationKey, true, $ttl);

            $repository->deleteMultiple(array_values($keys));

            throw $e;
        }

        return [$keys, $inlineEnvelopes];
    }

    /**
     * Unwrap the collected envelopes in their original task order, then clean up.
     *
     * @throws \Throwable
     */
    protected function resolveResults(
        CacheRepository $repository,
        array $tasks,
        array $keys,
        string $cancellationKey,
        array $envelopes,
    ): array {
        try {
            $results = [];

            foreach (array_keys($tasks) as $index => $key) {
                $results[$key] = TaskResult::unwrap($envelopes[$keys[$index]]);
            }

            return $results;
        } finally {
            $repository->deleteMultiple([...array_values($keys), $cancellationKey]);
        }
    }

    /**
     * Start the given tasks in the background after the current task has finished.
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        $connection = $this->connection();

        $this->ensureQueueConnectionIsSupported($connection);

        return defer(function () use ($tasks, $connection) {
            foreach (Arr::wrap($tasks) as $task) {
                $job = CallQueuedClosure::create($task)->onConnection($connection);

                if (! is_null($queue = $this->queue())) {
                    $job->onQueue($queue);
                }

                $this->bus->dispatch($job);
            }
        });
    }

    /**
     * Specify the queue connection that tasks should be dispatched to.
     *
     * @param  \UnitEnum|string|null  $connection
     */
    public function onConnection($connection): static
    {
        $driver = clone $this;

        $driver->options['connection'] = enum_value($connection);

        return $driver;
    }

    /**
     * Specify the queue that tasks should be dispatched to.
     *
     * @param  \UnitEnum|string|null  $queue
     */
    public function onQueue($queue): static
    {
        $driver = clone $this;

        $driver->options['queue'] = enum_value($queue);

        return $driver;
    }

    /**
     * Specify the cache store that should transport task results.
     *
     * @param  \UnitEnum|string|null  $store
     */
    public function store($store): static
    {
        $driver = clone $this;

        $driver->options['store'] = enum_value($store);

        return $driver;
    }

    /**
     * Ensure every inline task reported a result envelope during dispatch.
     *
     * @throws \RuntimeException
     */
    protected function ensureInlineEnvelopes(CacheRepository $repository, array $keys, array $envelopes): array
    {
        if (in_array(null, $envelopes, true)) {
            $missing = count(array_keys($envelopes, null, true));

            $repository->deleteMultiple(array_values($keys));

            throw new RuntimeException(
                sprintf(
                    '[%d] of [%d] concurrency tasks did not report a result. Ensure the queue connection and cache store are configured correctly.',
                    $missing, count($keys),
                )
            );
        }

        return $envelopes;
    }

    /**
     * Wait until every task has written a result envelope or the timeout has elapsed.
     *
     * @throws \Illuminate\Concurrency\TaskTimedOutException
     */
    protected function wait(
        CacheRepository $repository,
        array $keys,
        string $cancellationKey,
        Carbon $startedAt,
        int $timeout,
        int $ttl,
        ?string $connection,
        ?string $store,
    ): array {
        $envelopes = $repository->many(array_values($keys));

        if (! in_array(null, $envelopes, true)) {
            return $envelopes;
        }

        $starting = ((int) $startedAt->format('Uu')) / 1000;

        $deadline = $starting + $timeout * 1000;

        $poll = max((int) ($this->options['poll'] ?? 100), 1);

        while (true) {
            $now = ((int) Carbon::now()->format('Uu')) / 1000;

            if ($now >= $deadline) {
                $repository->put($cancellationKey, true, $ttl);

                $received = count($keys) - count(array_keys($envelopes, null, true));

                $repository->deleteMultiple(array_values($keys));

                throw new TaskTimedOutException(
                    $received, count($keys), $timeout, $connection, $this->queue(), $store,
                );
            }

            Sleep::usleep((int) (max(min($poll, $deadline - $now), 1) * 1000));

            foreach ($repository->many(array_keys($envelopes, null, true)) as $key => $envelope) {
                $envelopes[$key] = $envelope;
            }

            if (! in_array(null, $envelopes, true)) {
                return $envelopes;
            }
        }
    }

    /**
     * Ensure the resolved queue connection is able to process tasks.
     *
     * @throws \RuntimeException
     */
    protected function ensureQueueConnectionIsSupported(?string $connection): void
    {
        if (in_array($this->queueConnectionDriver($connection), ['deferred', 'null', 'background'], true)) {
            throw new RuntimeException(
                "The [{$connection}] queue connection may not be used with the queue concurrency driver, as its jobs would never run while waiting for results."
            );
        }
    }

    /**
     * Ensure the resolved cache store is able to transport task results.
     *
     * @throws \RuntimeException
     */
    protected function ensureStoreIsSupported(?string $store, bool $inline): void
    {
        $cacheDriver = $this->config->get('cache.stores.'.$store.'.driver');

        if (! $inline && in_array($cacheDriver, ['array', 'null', 'session', 'octane', 'apc'], true)) {
            throw new RuntimeException(
                "The [{$store}] cache store is not shared across processes and may not transport concurrency results. Configure the queue concurrency driver to use a shared store such as \"redis\", \"memcached\", or \"database\"."
            );
        }
    }

    /**
     * Get the queue connection name that tasks should be dispatched to.
     */
    protected function connection(): ?string
    {
        return $this->options['connection'] ?? $this->config->get('queue.default');
    }

    /**
     * Get the queue name that tasks should be dispatched to.
     */
    protected function queue(): ?string
    {
        return $this->options['queue'] ?? null;
    }

    /**
     * Get the cache store name that should transport task results.
     */
    protected function resolveStore(): ?string
    {
        return $this->options['store'] ?? $this->config->get('cache.default');
    }

    /**
     * Get the driver of the given queue connection.
     */
    protected function queueConnectionDriver(?string $connection): ?string
    {
        return $this->config->get('queue.connections.'.$connection.'.driver');
    }

    /**
     * Normalize the given timeout into a positive number of seconds.
     */
    protected function normalizeTimeout(CarbonInterval|int|null $timeout): int
    {
        $timeout ??= $this->options['timeout'] ?? 60;

        if ($timeout instanceof CarbonInterval) {
            $timeout = (int) $timeout->totalSeconds;
        }

        return max((int) $timeout, 1);
    }

    /**
     * Get the cache key for the given task's result envelope.
     */
    protected function resultKey(string $runId, int $index): string
    {
        return "illuminate:concurrency:{$runId}:{$index}";
    }

    /**
     * Get the cache key for the given run's cancellation flag.
     */
    protected function cancellationKey(string $runId): string
    {
        return "illuminate:concurrency:{$runId}:cancelled";
    }
}
