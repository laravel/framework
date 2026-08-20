<?php

namespace Illuminate\Tests\App\Jobs;

class UniqueUntilProcessingJobThatReleasesOnce extends UniqueTestJobThatDoesNotRelease
{
    public $tries = 2;

    public function middleware()
    {
        return [
            function ($job, $next) {
                if ($job->attempts() === 1) {
                    return $job->release();
                }

                return $next($job);
            },
        ];
    }

    public function uniqueId()
    {
        return 200;
    }
}
