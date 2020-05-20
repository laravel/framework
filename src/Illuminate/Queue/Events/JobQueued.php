<?php

namespace Illuminate\Queue\Events;

class JobQueued
{
    /**
     * @var mixed
     */
    public $job;

    /**
     * @var mixed
     */
    public $jobId;

    /**
     * JobQueued constructor.
     *
     * @param mixed $job
     * @param mixed $jobId
     * @return void
     */
    public function __construct($job, $jobId)
    {
        $this->job = $job;
        $this->jobId = $jobId;
    }
}
