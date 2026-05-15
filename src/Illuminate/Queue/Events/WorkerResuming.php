<?php

namespace Illuminate\Queue\Events;

use Illuminate\Queue\WorkerOptions;

class WorkerResuming
{
    /**
     * Create a new event instance.
     *
     * @param  string|null  $connectionName
     * @param  string|null  $queue
     * @param  WorkerOptions|null  $workerOptions
     */
    public function __construct(
        public ?string $connectionName = null,
        public ?string $queue = null,
        public ?WorkerOptions $workerOptions = null,
    ) {
    }
}
