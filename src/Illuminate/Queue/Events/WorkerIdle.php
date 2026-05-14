<?php

namespace Illuminate\Queue\Events;

class WorkerIdle
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     */
    public function __construct(
        public $connectionName,
        public $queue,
    ) {
    }
}
