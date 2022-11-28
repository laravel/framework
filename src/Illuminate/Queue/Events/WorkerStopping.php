<?php

namespace Illuminate\Queue\Events;

use Illuminate\Queue\WorkerOptions;

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
     * @var WorkerOptions|null
     */
    public $workerOptions;

    /**
     * Create a new event instance.
     *
     * @param  int  $status
     * @param  WorkerOptions|null  $workerOptions
     * @return void
     */
    public function __construct($status = 0, $workerOptions = null)
    {
        $this->status = $status;
        $this->workerOptions = $workerOptions;
    }
}
