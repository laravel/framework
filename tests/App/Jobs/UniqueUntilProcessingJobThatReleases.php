<?php

namespace Illuminate\Tests\App\Jobs;

class UniqueUntilProcessingJobThatReleases extends UniqueTestJobThatDoesNotRelease
{
    public function middleware()
    {
        return [
            function ($job) {
                static::$released = true;

                return $job->release(30);
            },
        ];
    }

    public function uniqueId()
    {
        return 100;
    }
}
