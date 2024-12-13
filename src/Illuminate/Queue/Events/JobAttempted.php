<?php

namespace Illuminate\Queue\Events;

class JobAttempted
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The job instance.
     *
     * @var \Illuminate\Contracts\Queue\Job
     */
    public $job;

    /**
     * Indicates if an exception occurred while processing the job.
     *
     * @var bool
     */
    public $exceptionOccurred;

    /**
     * The time it took the job to process.
     *
     * @var float
     */
    public $time;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  float  $time
     * @param  bool  $exceptionOccurred
     * @return void
     */
    public function __construct($connectionName, $job, $time, $exceptionOccurred = false)
    {
        $this->job = $job;
        $this->connectionName = $connectionName;
        $this->exceptionOccurred = $exceptionOccurred;
        $this->time = $time;
    }

    /**
     * Determine if the job completed with failing or an unhandled exception occurring.
     *
     * @return bool
     */
    public function successful(): bool
    {
        return ! $this->job->hasFailed() && ! $this->exceptionOccurred;
    }
}
