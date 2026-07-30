<?php

namespace Illuminate\Queue\Events;

use Illuminate\Queue\WorkerOptions;

class WorkerIdle
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \Illuminate\Queue\WorkerOptions  $workerOptions
     */
    public function __construct(
        public string $connectionName,
        public string $queue,
        public WorkerOptions $workerOptions,
    ) {
    }
}
