<?php

namespace Illuminate\Queue\Events;

class WorkerPausing
{
    /**
     * Create a new event instance.
     *
     * @param  string|null  $connectionName
     * @param  string|null  $queue
     * @param  \Illuminate\Queue\WorkerOptions|null  $workerOptions
     */
    public function __construct(
        public ?string $connectionName = null,
        public ?string $queue = null,
        public $workerOptions = null,
    ) {
    }
}
