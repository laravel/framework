<?php

namespace Illuminate\Http\Client;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;

class CircuitBreaker
{
    /**
     * The cache repository used to persist circuit state.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The identifier used to namespace circuit state entries.
     *
     * @var string
     */
    protected $key;

    /**
     * The number of consecutive failures that will open the circuit.
     *
     * @var positive-int
     */
    protected $failureThreshold;

    /**
     * The number of seconds the circuit stays open before a probe is allowed.
     *
     * @var positive-int
     */
    protected $resetTimeout;

    /**
     * The number of seconds failure and state entries persist in the cache.
     *
     * @var positive-int
     */
    protected $cacheTtl;

    /**
     * Create a new circuit breaker instance.
     */
    public function __construct(Repository $cache, string $key, int $failureThreshold = 5, int $resetTimeout = 30, ?int $cacheTtl = null)
    {
        $this->cache = $cache;
        $this->key = $key;
        $this->failureThreshold = max(1, $failureThreshold);
        $this->resetTimeout = max(1, $resetTimeout);
        $this->cacheTtl = $cacheTtl ?? max($resetTimeout * 10, 600);
    }

    /**
     * Ensure the circuit is not open. Throw if it is and the half-open probe slot is taken.
     *
     * @throws \Illuminate\Http\Client\CircuitOpenException
     */
    public function guard(): void
    {
        $openedAt = $this->cache->get($this->openedAtKey());

        if ($openedAt === null) {
            return;
        }

        $elapsed = Carbon::now()->getTimestamp() - (int) $openedAt;

        if ($elapsed < $this->resetTimeout) {
            throw new CircuitOpenException($this->key, $this->resetTimeout - $elapsed);
        }

        if (! $this->cache->add($this->probeKey(), 1, $this->resetTimeout)) {
            throw new CircuitOpenException($this->key, 1);
        }
    }

    /**
     * Record a successful request, closing the circuit if it was half-open.
     */
    public function recordSuccess(): void
    {
        $this->cache->forget($this->failuresKey());
        $this->cache->forget($this->openedAtKey());
        $this->cache->forget($this->probeKey());
    }

    /**
     * Record a failed request, opening the circuit if the threshold is exceeded.
     */
    public function recordFailure(): void
    {
        $this->cache->forget($this->probeKey());

        if ($this->cache->get($this->openedAtKey()) !== null) {
            $this->cache->put($this->openedAtKey(), Carbon::now()->getTimestamp(), $this->cacheTtl);

            return;
        }

        $failures = (int) $this->cache->increment($this->failuresKey());

        if ($failures === 1) {
            $this->cache->put($this->failuresKey(), 1, $this->cacheTtl);
        }

        if ($failures >= $this->failureThreshold) {
            $this->cache->put($this->openedAtKey(), Carbon::now()->getTimestamp(), $this->cacheTtl);
        }
    }

    /**
     * Determine whether the circuit is currently open.
     */
    public function isOpen(): bool
    {
        $openedAt = $this->cache->get($this->openedAtKey());

        if ($openedAt === null) {
            return false;
        }

        return (Carbon::now()->getTimestamp() - (int) $openedAt) < $this->resetTimeout;
    }

    /**
     * Get the identifier used for this circuit.
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * Get the cache key used to track consecutive failures.
     */
    protected function failuresKey(): string
    {
        return "illuminate:http:circuit_breaker:failures:{$this->key}";
    }

    /**
     * Get the cache key used to track when the circuit opened.
     */
    protected function openedAtKey(): string
    {
        return "illuminate:http:circuit_breaker:opened_at:{$this->key}";
    }

    /**
     * Get the cache key used to reserve the half-open probe slot.
     */
    protected function probeKey(): string
    {
        return "illuminate:http:circuit_breaker:probe:{$this->key}";
    }
}
