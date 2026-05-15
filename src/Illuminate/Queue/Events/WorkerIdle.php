<?php

namespace Illuminate\Queue\Events;

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
        public $connectionName,
        public $queue,
        public $workerOptions,
    ) {
    }
}
