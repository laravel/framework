<?php

namespace Illuminate\Bus;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Attributes\ReadsQueueAttributes;
use Illuminate\Queue\Attributes\UniqueFor;

class UniqueLock
{
    use ReadsQueueAttributes;

    /**
     * Default TTL (seconds) for the per-dispatch "released" marker when a job
     * does not declare a uniqueFor value. Long enough to cover any realistic
     * retry chain; the marker is uuid-scoped so it cannot collide with future
     * dispatches.
     */
    protected const DEFAULT_RELEASED_MARKER_TTL = 86400;

    /**
     * The cache repository implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new unique lock manager instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to acquire a lock for the given job.
     *
     * @param  mixed  $job
     * @return bool
     */
    public function acquire($job)
    {
        return (bool) $this->resolveCache($job)
            ->lock(self::getKey($job), $this->resolveUniqueFor($job))
            ->get();
    }

    /**
     * Release the lock for the given job.
     *
     * @param  mixed  $job
     * @return void
     */
    public function release($job)
    {
        $this->resolveCache($job)->lock(self::getKey($job))->forceRelease();
    }

    /**
     * Record that the unique lock has been released by the given dispatch.
     *
     * Used to make subsequent attempts of the same dispatch idempotent so
     * they do not force-release a lock that may now belong to a different
     * dispatch.
     *
     * @param  mixed  $job
     * @param  string  $owner
     * @return void
     */
    public function recordReleasedBy($job, string $owner)
    {
        $this->resolveCache($job)->put(
            self::getReleasedKey($job, $owner),
            true,
            $this->resolveUniqueFor($job) ?: self::DEFAULT_RELEASED_MARKER_TTL
        );
    }

    /**
     * Determine whether the unique lock was previously released by the given dispatch.
     *
     * @param  mixed  $job
     * @param  string  $owner
     * @return bool
     */
    public function wasReleasedBy($job, string $owner)
    {
        return (bool) $this->resolveCache($job)->get(self::getReleasedKey($job, $owner));
    }

    /**
     * Forget the per-dispatch "released" marker for the given job.
     *
     * @param  mixed  $job
     * @param  string  $owner
     * @return void
     */
    public function forgetReleasedBy($job, string $owner)
    {
        $this->resolveCache($job)->forget(self::getReleasedKey($job, $owner));
    }

    /**
     * Resolve the cache store for the given job.
     *
     * @param  mixed  $job
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function resolveCache($job)
    {
        return method_exists($job, 'uniqueVia')
            ? ($job->uniqueVia() ?? $this->cache)
            : $this->cache;
    }

    /**
     * Resolve the uniqueFor value (in seconds) for the given job.
     *
     * @param  mixed  $job
     * @return int
     */
    protected function resolveUniqueFor($job)
    {
        return method_exists($job, 'uniqueFor')
            ? $job->uniqueFor()
            : ($this->getAttributeValue($job, UniqueFor::class, 'uniqueFor') ?? 0);
    }

    /**
     * Generate the lock key for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    public static function getKey($job)
    {
        $uniqueId = method_exists($job, 'uniqueId')
            ? $job->uniqueId()
            : ($job->uniqueId ?? '');

        $jobName = method_exists($job, 'displayName')
            ? hash('xxh128', $job->displayName())
            : get_class($job);

        return 'laravel_unique_job:'.$jobName.':'.$uniqueId;
    }

    /**
     * Generate the per-dispatch "released" marker key for the given job.
     *
     * @param  mixed  $job
     * @param  string  $owner
     * @return string
     */
    public static function getReleasedKey($job, string $owner)
    {
        return self::getKey($job).':released:'.$owner;
    }
}
