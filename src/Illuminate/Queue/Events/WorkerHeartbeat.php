<?php

namespace Illuminate\Queue\Events;

use Illuminate\Queue\WorkerOptions;

class WorkerHeartbeat
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @param  string  $queue  The queue name.
     * @param  \Illuminate\Queue\WorkerOptions  $workerOptions  The worker options.
     * @param  int|null  $jobsProcessed  The number of jobs processed by the worker.
     * @param  int|float|null  $lastJobProcessedAt  The timestamp of the last job processed by the worker.
     * @param  int|float|null  $memoryUsage  The memory usage of the worker in MB.
     */
    public function __construct(
        public string $connectionName,
        public string $queue,
        public WorkerOptions $workerOptions,
        public ?int $jobsProcessed = null,
        public int|float|null $lastJobProcessedAt = null,
        public int|float|null $memoryUsage = null,
    ) {
    }
}
