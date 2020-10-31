<?php

namespace Illuminate\Queue\Middleware;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;

class ReleaseUniqueJobLock
{
    /**
     * Process the job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @param  string  $key
     * @return mixed
     */
    public function handle($job, $next, $key)
    {
        try {
            $next($job);
        } finally {
            Container::getInstance()->make(Cache::class)
                ->lock($key)
                ->forceRelease();
        }
    }
}
