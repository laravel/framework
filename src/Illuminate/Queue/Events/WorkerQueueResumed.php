<?php

namespace Illuminate\Queue\Events;

class WorkerQueueResumed
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @param  string  $queue  The name of the queue the worker found to be resumed.
     */
    public function __construct(
        public $connectionName,
        public $queue,
    ) {
    }
}
