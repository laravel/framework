<?php

namespace Illuminate\Queue\Events;

class WorkerStopping
{
    public WorkerExitCode $status;

    /**
     * Create a new event instance.
     *
     * @param  int|\Illuminate\Queue\Enums\WorkerExitCode  $status  The worker exit status.
     * @param  \Illuminate\Queue\WorkerOptions|null  $workerOptions  The worker options.
     * @return void
     */
    public function __construct(
        $status = 0,
        public $workerOptions = null
    ) {
        $this->status = WorkerExitCode::from($status);
    }
}
