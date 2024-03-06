<?php

declare(strict_types = 1);

namespace Illuminate\Queue\Middleware;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;

class Debounced
{
    /**
     * @param InteractsWithQueue|mixed $job
     * @param $next
     *
     * @return mixed|void
     */
    public function handle(mixed $job, $next)
    {
        if (($job->connection ?? config('queue.default')) === 'sync') {
            if (config('app.debug') && app()->isLocal()) {
                throw new \LogicException('Debounced jobs must not run on the sync queue.');
            }

            $next($job);
            return;
        }

        $key = 'debounced.' . get_class($job);

        if ($job instanceof ShouldBeUnique && method_exists($job, 'uniqueId')) {
            // use the uniqueId to debounce by if defined
            $key .= '.uniqueBy.' . $job->uniqueId();
        }

        $intendedExecutionTime = cache()->pull($key);

        if (
            // if there's a value for this key, this is a debounced job
            !is_null($intendedExecutionTime) &&
            !in_array(InteractsWithQueue::class, class_uses_recursive($job), true)
        ) {
            // using the class-string so there's a hard reference
            $traitName = class_basename(InteractsWithQueue::class);
            throw new \InvalidArgumentException("The Debounced jobs must use the $traitName trait.");
        }

        $count = cache()->pull($key . '.count', 1);

        if ($count > 1) {
            // this is an earlier job, so we should delete it
            $job->delete();

            // decrement the count
            cache()->forever($key . '.count', $count - 1);
            return;
        }

        if ($intendedExecutionTime) {
            $intendedExecutionTime = Carbon::parse($intendedExecutionTime);

            if ($intendedExecutionTime->gt(now())) {
                // ensure that the intended execution time from the last job is used
                $job->release($intendedExecutionTime->diffInSeconds(now(), false));
                return;
            }
        }

        // todo - this is still marked as RUNNING and DONE in the console for every job despite only the last job executes (JobProcessing, JobProcessed events still fire)
        $next($job);
    }
}
