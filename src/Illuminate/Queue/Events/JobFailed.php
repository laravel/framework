<?php

namespace Illuminate\Queue\Events;

class JobFailed
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
     * The exception / throwable that caused the job to fail.
     *
     * @var \Throwable
     */
    public $throwable;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Throwable  $throwable
     * @return void
     */
    public function __construct($connectionName, $job, $throwable)
    {
        $this->job = $job;
        $this->throwable = $throwable;
        $this->connectionName = $connectionName;
    }
}
