<?php

namespace Illuminate\Queue\Events;

class WorkerStopping
{
    /**
     * The worker exit status.
     *
     * @var int
     */
    public $status;

    /**
     * The worker options.
     *
     * @var \Illuminate\Queue\WorkerOptions|null
     */
    public $workerOptions;

    /**
     * Create a new event instance.
     *
     * @param  int  $status
     * @param  \Illuminate\Queue\WorkerOptions|null  $workerOptions
     * @return void
     */
    public function __construct($status = 0, $workerOptions = null)
    {
        $this->status = $status;
        $this->workerOptions = $workerOptions;
    }
}
