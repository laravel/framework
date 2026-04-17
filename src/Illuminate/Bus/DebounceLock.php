<?php

namespace Illuminate\Bus;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Queue\Attributes\ReadsQueueAttributes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use ReflectionClass;

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
     *
     * @param  mixed  $job
     * @param  int|null  $debounceFor
     * @param  int|null  $maxWait
     * @return array{owner: string, maxWaitExceeded: bool}
     */
    public function acquire($job, $debounceFor = null, $maxWait = null)
    {
        $cache = $this->resolveCache($job);

        $ttl = max(($debounceFor ?? $this->getDebounceDelay($job)) * 10, 300);

        $cache->put($key = static::getKey($job), $owner = Str::random(40), $ttl);

        return [
            'owner' => $owner,
            'maxWaitExceeded' => $this->maxWaitExceeded(
                $cache, $key, $ttl, $maxWait ?? $this->getMaxDebounceWait($job)
            ),
        ];
    }

    /**
     * Determine if the maximum debounce wait time has been exceeded.
     */
    protected function maxWaitExceeded(Cache $cache, string $key, int $ttl, ?int $maxWait): bool
    {
        if (is_null($maxWait)) {
            return false;
        }

        $timestampKey = $key.':first_dispatched_at';

        if (! $cache->has($timestampKey)) {
            $cache->put($timestampKey, Carbon::now()->getTimestamp(), $ttl);

            return false;
        }

        $elapsed = Carbon::now()->getTimestamp() - $cache->get($timestampKey);

        if ($elapsed >= $maxWait) {
            $cache->forget($timestampKey);

            return true;
        }

        return false;
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
     * @param  mixed  $job
     * @param  string  $owner
     * @return void
     */
    public function release($job, string $owner = '')
    {
        $key = static::getKey($job);

        $cache = $this->resolveCache($job);

        if (! empty($owner) && $cache->get($key) !== $owner) {
            return;
        }

        $cache->forget($key);
        $cache->forget($key.':first_dispatched_at');
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
        $attributes = (new ReflectionClass($job))->getAttributes(DebounceFor::class);

        return count($attributes) > 0
            ? $attributes[0]->newInstance()->maxWait
            : null;
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
