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
     * The data given to the job.
     *
     * @var array
     */
    public $data;

    /**
     * The ID of the entry in the failed jobs table.
     *
     * @var int|null
     */
    public $failedId;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  array  $data
     * @param  int|null  $failedId
     * @return void
     */
    public function __construct($connectionName, $job, $data, $failedId = null)
    {
        $this->job = $job;
        $this->data = $data;
        $this->failedId = $failedId;
        $this->connectionName = $connectionName;
    }
}
