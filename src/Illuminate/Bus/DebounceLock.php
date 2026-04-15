<?php

namespace Illuminate\Bus;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Queue\Attributes\ReadsQueueAttributes;
use Illuminate\Support\Str;

class DebounceLock
{
    use ReadsQueueAttributes;

    /**
     * The cache repository implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new debounce lock manager instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Store a debounce owner token for the given job.
     *
     * Overwrites any existing token, implementing last-writer-wins semantics.
     * The TTL is generous (10x debounceFor) for garbage collection only —
     * correctness does not depend on it.
     *
     * @param  mixed  $job
     * @param  int|null  $debounceFor
     * @param  int|null  $maxWait
     * @return array{owner: string, maxWaitExceeded: bool}
     */
    public function acquire($job, $debounceFor = null, $maxWait = null)
    {
        $debounceFor = $debounceFor ?? $this->getDebounceDelay($job);

        $cache = $this->resolveCache($job);
        $key = static::getKey($job);
        $ttl = max($debounceFor * 10, 300);

        $owner = Str::random(40);

        // The TTL is intentionally generous — it exists only for garbage collection,
        // not correctness. The token is intentionally left in place after execution
        // to prevent a race where a superseded job sees an empty cache and runs
        // via fail-open.
        $cache->put($key, $owner, $ttl);

        $maxWait = $maxWait ?? $this->getMaxDebounceWait($job);
        $maxWaitExceeded = false;

        if (! is_null($maxWait)) {
            $timestampKey = $key.':first_dispatched_at';

            if (! $cache->has($timestampKey)) {
                $cache->put($timestampKey, now()->timestamp, $ttl);
            } else {
                $elapsed = now()->timestamp - $cache->get($timestampKey);

                if ($elapsed >= $maxWait) {
                    $maxWaitExceeded = true;
                    $cache->forget($timestampKey);
                }
            }
        }

        return ['owner' => $owner, 'maxWaitExceeded' => $maxWaitExceeded];
    }

    /**
     * Determine if the given owner is the current owner for this debounce key.
     *
     * @param  mixed  $job
     * @param  string  $owner
     * @return bool
     */
    public function isCurrentOwner($job, string $owner)
    {
        return $this->resolveCache($job)->get(static::getKey($job)) === $owner;
    }

    /**
     * Determine if a debounce token exists for the given job.
     *
     * @param  mixed  $job
     * @return bool
     */
    public function lockExists($job)
    {
        return ! is_null($this->resolveCache($job)->get(static::getKey($job)));
    }

    /**
     * Remove the debounce token for the given job.
     *
     * When an owner is provided, the token is only removed if it still
     * belongs to that owner — preventing a finished job from wiping
     * a newer dispatch's token.
     *
     * @param  mixed  $job
     * @param  string  $owner
     * @return void
     */
    public function release($job, string $owner = '')
    {
        $cache = $this->resolveCache($job);
        $key = static::getKey($job);

        if (! empty($owner) && $cache->get($key) !== $owner) {
            return;
        }

        $cache->forget($key);
    }

    /**
     * Get the debounce delay for the given job.
     *
     * @param  mixed  $job
     * @return int|null
     */
    public function getDebounceDelay($job)
    {
        return $this->getAttributeValue($job, DebounceFor::class, 'debounceFor');
    }

    /**
     * Get the maximum debounce wait time for the given job.
     *
     * @param  mixed  $job
     * @return int|null
     */
    public function getMaxDebounceWait($job)
    {
        if (method_exists($job, 'maxDebounceWait')) {
            return $job->maxDebounceWait();
        }

        $attributes = (new \ReflectionClass($job))->getAttributes(DebounceFor::class);

        if (count($attributes) > 0) {
            return $attributes[0]->newInstance()->maxWait;
        }

        return null;
    }

    /**
     * Generate the cache key for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    public static function getKey($job)
    {
        $debounceId = method_exists($job, 'debounceId')
            ? $job->debounceId()
            : ($job->debounceId ?? '');

        $jobName = method_exists($job, 'displayName')
            ? hash('xxh128', $job->displayName())
            : get_class($job);

        return 'laravel_debounced_job:'.$jobName.':'.$debounceId;
    }

    /**
     * Resolve the cache store for the given job.
     *
     * @param  mixed  $job
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function resolveCache($job)
    {
        return method_exists($job, 'debounceVia')
            ? ($job->debounceVia() ?? $this->cache)
            : $this->cache;
    }
}
