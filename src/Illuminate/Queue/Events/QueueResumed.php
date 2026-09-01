<?php

namespace Illuminate\Queue\Events;

class QueueResumed
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @param  string  $queue  The queue name.
     */
    public function __construct(
        public $connectionName,
        public $queue,
    ) {
    }
}
