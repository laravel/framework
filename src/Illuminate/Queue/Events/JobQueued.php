<?php

namespace Illuminate\Queue\Events;

class JobQueued
{
    /**
     * @var  string|object
     */
    public $job;

    /**
     * @var  string|int|null
     */
    public $jobId;

    /**
     * JobQueued constructor.
     *
     * @param  string|object  $job
     * @param  string|int|null  $jobId
     * @return  void
     */
    public function __construct($job, $jobId)
    {
        $this->job = $job;
        $this->jobId = $jobId;
    }
}
