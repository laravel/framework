<?php

namespace Illuminate\Queue\Events;

class JobExceptionOccurred extends AbstractJobEvent
{
    /**
     * The exception instance.
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
