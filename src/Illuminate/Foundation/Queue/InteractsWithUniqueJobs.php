<?php

namespace Illuminate\Foundation\Queue;

use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Context;

trait InteractsWithUniqueJobs
{
    /**
     * Store unique job information in the context in case we can't resolve the job on the queue side.
     *
     * @param  object  $job
     * @return void
     */
    public function addUniqueJobInformationToContext($job): void
    {
        if ($this->isUniqueJob($job)) {
            Context::addHidden([
                'laravel_unique_job_cache_driver' => $this->getUniqueJobCacheStore($job),
                'laravel_unique_job_key' => UniqueLock::getKey($job),
            ]);
        }
    }

    /**
     * Remove the unique job information from the context.
     *
     * @param  object  $job
     * @return void
     */
    public function removeUniqueJobInformationFromContext($job): void
    {
        if ($this->isUniqueJob($job)) {
            Context::forgetHidden([
                'laravel_unique_job_cache_driver',
                'laravel_unique_job_key',
            ]);
        }
    }

    /**
     * Determine the cache store used by the unique job to acquire locks.
     *
     * @param  object  $job
     * @return string
     */
    private function getUniqueJobCacheStore($job): ?string
    {
        return method_exists($job, 'uniqueVia')
            ? $job->uniqueVia()
            : config('cache.default');
    }

    /**
     * Determine if job should be unique.
     *
     * @param  mixed  $job
     * @return bool
     */
    private function isUniqueJob($job): bool
    {
        return $job instanceof ShouldBeUnique;
    }
}
