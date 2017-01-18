<?php

namespace Illuminate\Queue\Events;

class JobFailed extends AbstractJobEvent
{
    /**
     * The exception that caused the job to fail.
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $exception
     * @return void
     */
    public function __construct($connectionName, $job, $exception)
    {
        parent::__construct($connectionName, $job);
        $this->exception = $exception;
    }
}
