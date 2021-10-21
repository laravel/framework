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
        $uniqueId = method_exists($job, 'uniqueId')
                    ? $job->uniqueId()
                    : ($job->uniqueId ?? '');

        $cache = method_exists($job, 'uniqueVia')
                    ? $job->uniqueVia()
                    : $this->cache;

        return (bool) $cache->lock(
            $key = 'laravel_unique_job:'.get_class($job).$uniqueId,
            $job->uniqueFor ?? 0
        )->get();
    }
}
