<?php

namespace Illuminate\Queue\Events;

class WorkerStopping
{
    /**
     * Create a new event instance.
     *
     * @param  int  $status  The worker exit status.
     * @param  \Illuminate\Queue\WorkerOptions|null  $workerOptions  The worker options.
     * @param  \Illuminate\Queue\WorkerStopReason|null  $reason  The reason why the worker is stopping.
     * @param  int|null  $jobsProcessed  The number of jobs processed by the worker.
     * @param  int|float|null  $lastJobProcessedAt  The timestamp of the last job processed by the worker.
     * @param  float|null  $memoryUsage  The memory usage of the worker in MB.
     */
    public function __construct(
        public $status = 0,
        public $workerOptions = null,
        public $reason = null,
        public $jobsProcessed = null,
        public $lastJobProcessedAt = null,
        public $memoryUsage = null,
    ) {
    }
}
