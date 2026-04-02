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
     * @return string
     */
    public function acquire($job)
    {
        $debounceFor = method_exists($job, 'debounceFor')
            ? $job->debounceFor()
            : $this->getAttributeValue($job, DebounceFor::class, 'debounceFor');

        $cache = $this->resolveCache($job);

        $owner = Str::random(40);

        // The TTL is intentionally generous — it exists only for garbage collection,
        // not correctness. The token is explicitly removed after the job executes.
        $cache->put(static::getKey($job), $owner, max($debounceFor * 10, 300));

        return $owner;
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
