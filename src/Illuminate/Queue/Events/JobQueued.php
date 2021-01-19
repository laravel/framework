<?php

namespace Illuminate\Queue\Events;

class JobQueued
{
    /**
     * @var string|int|null
     */
    public $jobId;

    /**
     * @var string|object
     */
    public $job;

    /**
     * JobQueued constructor.
     *
     * @param  string|int|null  $jobId
     * @param  \Closure|string|object  $job
     * @return void
     */
    public function __construct($jobId, $job)
    {
        $this->jobId = $jobId;
        $this->job = $job;
    }
}
