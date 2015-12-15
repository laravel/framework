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
     * @var \Illuminate\Contracts\Jobs\Job
     */
    public $job;

    /**
     * The data given to the job.
     *
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Jobs\Job  $job
     * @param  array  $data
     * @return void
     */
    public function __construct($connectionName, $job, $data)
    {
        $this->job = $job;
        $this->data = $data;
        $this->connectionName = $connectionName;
    }
}
