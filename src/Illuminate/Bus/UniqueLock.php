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
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to acquire a lock for the given job.
     *
     * @param  mixed  $job
     * @param  mixed  $arguments
     * @return bool
     */
    public function acquire($job, ...$arguments)
    {
        $uniqueFor = method_exists($job, 'uniqueFor')
            ? $job->uniqueFor(...$arguments)
            : ($job->uniqueFor ?? 0);

        $cache = method_exists($job, 'uniqueVia')
            ? $job->uniqueVia(...$arguments)
            : $this->cache;

        return (bool) $cache->lock($this->getKey($job, ...$arguments), $uniqueFor)->get();
    }

    /**
     * Release the lock for the given job.
     *
     * @param  mixed  $job
     * @param  mixed  $arguments
     * @return void
     */
    public function release($job, ...$arguments)
    {
        $cache = method_exists($job, 'uniqueVia')
            ? $job->uniqueVia(...$arguments)
            : $this->cache;

        $cache->lock($this->getKey($job, ...$arguments))->forceRelease();
    }

    /**
     * Generate the lock key for the given job.
     *
     * @param  mixed  $job
     * @param  mixed  $arguments
     * @return string
     */
    public static function getKey($job, ...$arguments)
    {
        $uniqueId = method_exists($job, 'uniqueId')
            ? $job->uniqueId(...$arguments)
            : ($job->uniqueId ?? '');

        return 'laravel_unique_job:'.get_class($job).':'.$uniqueId;
    }
}
