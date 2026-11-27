<?php

namespace Illuminate\Queue\Events;

class JobAttempted
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @param  \Illuminate\Contracts\Queue\Job  $job  The job instance.
     * @param  \Throwable|null  $exception  The exception, if one occurred while processing the job.
     * @param  float|null  $duration  The number of milliseconds the job took to process.
     * @param  int|float|null  $memoryUsage  The peak memory usage of the worker while processing the job in MB.
     */
    public function __construct(
        public $connectionName,
        public $job,
        public $exception = null,
        public $duration = null,
        public $memoryUsage = null,
    ) {
    }

    /**
     * Determine if the job completed with failing or an unhandled exception occurring.
     *
     * @return bool
     */
    public function successful(): bool
    {
        return ! $this->job->hasFailed() && is_null($this->exception);
    }
}
