<?php

namespace Illuminate\Queue\Events;

class Looping
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @param  string  $queue  The queue name.
     * @param  \Illuminate\Queue\WorkerOptions|null  $workerOptions  The worker options.
     */
    public function __construct(
        public $connectionName,
        public $queue,
        public $workerOptions = null,
    ) {
    }
}
