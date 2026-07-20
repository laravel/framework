<?php

namespace Illuminate\Bus;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Attributes\ReadsQueueAttributes;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Support\Str;

class UniqueLock
{
    use ReadsQueueAttributes;

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
        $uniqueFor = method_exists($job, 'uniqueFor')
            ? $job->uniqueFor()
            : ($this->getAttributeValue($job, UniqueFor::class, 'uniqueFor') ?? 0);

        $cache = method_exists($job, 'uniqueVia')
            ? ($job->uniqueVia() ?? $this->cache)
            : $this->cache;

        $owner = Str::random(40);

        $acquired = (bool) $cache->lock(self::getKey($job), $uniqueFor, $owner)->get();

        if ($acquired && property_exists($job, 'uniqueLockOwner')) {
            $job->uniqueLockOwner = $owner;
        }

        return $acquired;
    }

    /**
     * Release the lock for the given job.
     *
     * Releases by owner when the job carries one, so a dispatch that never
     * released its own lock (e.g. a retry after being released back to the
     * queue by another middleware) can't release a lock a newer, unrelated
     * dispatch has since legitimately acquired.
     *
     * @param  mixed  $job
     * @return void
     */
    public function release($job)
    {
        $cache = method_exists($job, 'uniqueVia')
            ? ($job->uniqueVia() ?? $this->cache)
            : $this->cache;

        $owner = property_exists($job, 'uniqueLockOwner') ? $job->uniqueLockOwner : '';

        if (! empty($owner)) {
            $cache->restoreLock(self::getKey($job), $owner)->release();

            return;
        }

        $cache->lock(self::getKey($job))->forceRelease();
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
}
