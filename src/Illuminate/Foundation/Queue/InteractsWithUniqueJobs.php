<?php

namespace Illuminate\Foundation\Queue;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Arr;

trait InteractsWithUniqueJobs
{
    /**
     * Saves the used cache driver for the lock and
     * the lock key for emergency forceRelease in
     * case we can't instantiate a job instance.
     */
    public function rememberLockIfJobIsUnique($job): void
    {
        if ($this->isUniqueJob($job)) {
            context()->addHidden([
                'laravel_unique_job_cache_driver' => $this->getCacheDriver($job),
                'laravel_unique_job_key' => $this->getKey($job),
            ]);
        }
    }

    /**
     * forget the used lock.
     */
    public function forgetLockIfJobIsUnique($job): void
    {
        if ($this->isUniqueJob($job)) {
            context()->forgetHidden(['laravel_unique_job_cache_driver', 'laravel_unique_job_key']);
        }
    }

    /**
     * Determine if job has unique lock.
     */
    private function isUniqueJob($job): bool
    {
        return $job instanceof ShouldBeUnique;
    }

    /**
     * Get the used cache driver as string from the config,
     * CacheManger will handle invalid drivers.
     */
    private function getCacheDriver($job): ?string
    {
        /** @var \Illuminate\Cache\Repository */
        $cache = method_exists($job, 'uniqueVia') ?
            $job->uniqueVia() :
            app()->make(Repository::class);

        $store = collect(config('cache')['stores'])

            ->firstWhere(
                function ($store) use ($cache) {
                    return $cache === rescue(fn () => cache()->driver(($store['driver'])));
                }
            );

        return Arr::get($store, 'driver');
    }

    //NOTE: can I change visibility of the original method in src/Illuminate/Bus/UniqueLock.php ?
    /**
     * Generate the lock key for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    private function getKey($job)
    {
        $uniqueId = method_exists($job, 'uniqueId')
                    ? $job->uniqueId()
                    : ($job->uniqueId ?? '');

        return 'laravel_unique_job:'.get_class($job).':'.$uniqueId;
    }
}
