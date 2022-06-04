<?php

namespace Illuminate\Bus;

use Illuminate\Contracts\Cache\Repository as Cache;

class UniqueLock
{
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
     * @return void
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
        $cache = $this->getJobUniqueVia($job);

        return (bool) $cache->lock(
            $this->getJobUniqueKey($job),
            $this->getJobUniqueFor($job)
        )->get();
    }

    /**
     * Release the lock for the given job.
     *
     * @param  mixed  $job
     * @return void
     */
    public function release($job)
    {
        $cache = $this->getJobUniqueVia($job);

        $cache->lock(
            $this->getJobUniqueKey($job),
            $this->getJobUniqueFor($job)
        )->forceRelease();
    }

    /**
     * Determine the lock duration for the given job.
     *
     * @param  mixed $job
     * @return int
     */
    protected function getJobUniqueFor(mixed $job): int
    {
        return method_exists($job, 'uniqueFor')
            ? $job->uniqueFor()
            : ($job->uniqueFor ?? 0);
    }

    /**
     * Determine the lock key for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    protected function getJobUniqueKey($job): string
    {
        $uniqueId = method_exists($job, 'uniqueId')
            ? $job->uniqueId()
            : ($job->uniqueId ?? '');

        return 'laravel_unique_job:'.get_class($job).$uniqueId;
    }

    /**
     * @param  mixed $job
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function getJobUniqueVia(mixed $job): Cache
    {
        return method_exists($job, 'uniqueVia')
            ? $job->uniqueVia()
            : $this->cache;
    }
}
