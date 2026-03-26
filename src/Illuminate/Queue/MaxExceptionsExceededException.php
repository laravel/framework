<?php

namespace Illuminate\Queue;

use RuntimeException;

class MaxExceptionsExceededException extends RuntimeException
{
    /**
     * The job instance.
     *
     * @var \Illuminate\Contracts\Queue\Job|null
     */
    public $job;

    /**
     * Create a new instance for the job.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return static
     */
    public static function forJob($job)
    {
        return tap(new static($job->resolveName().' has exceeded the maximum number of exceptions.'), function ($e) use ($job) {
            $e->job = $job;
        });
    }
}
