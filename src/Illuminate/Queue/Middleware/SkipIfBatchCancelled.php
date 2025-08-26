<?php

namespace Illuminate\Queue\Middleware;

class SkipIfBatchCancelled
{
    /**
     * Process the job.
     *
     * @param  callable  $next
     */
    public function handle($job, $next)
    {
        if (method_exists($job, 'batch') && $job->batch()?->cancelled()) {
            return;
        }

        $next($job);
    }
}
