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
     * The reason why the worker is stopping.
     *
     * @var \Illuminate\Queue\WorkerOptions|null
     */
    public $reason;

    /**
     * Create a new event instance.
     *
     * @param  int  $status
     * @param  \Illuminate\Queue\WorkerOptions|null  $workerOptions
     * @param  \Illuminate\Queue\WorkerStopReason|null  $reason
     * @return void
     */
    public function __construct($status = 0, $workerOptions = null, $reason = null)
    {
        $this->status = $status;
        $this->workerOptions = $workerOptions;
        $this->reason = $reason;
    }
}
