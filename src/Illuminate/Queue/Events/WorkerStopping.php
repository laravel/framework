<?php

namespace Illuminate\Queue\Events;

use Illuminate\Queue\WorkerStoppingReason;

class WorkerStopping
{
    /**
     * Create a new event instance.
     *
     * @param  int  $status  The worker exit status.
     * @param  \Illuminate\Queue\WorkerOptions|null  $workerOptions  The worker options.
     * @param  WorkerStoppingReason|null  $reason  The reason why it's stopping.
     */
    public function __construct(
        public $status = 0,
        public $workerOptions = null,
        public $reason = null,
    ) {
    }
}
