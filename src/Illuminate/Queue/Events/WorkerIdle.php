<?php

namespace Illuminate\Queue\Events;

class WorkerIdle
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  string|null  $name The name of the worker.
     */
    public function __construct(
        public $connectionName,
        public $queue,
        public $name = null,
    ) {
    }
}
