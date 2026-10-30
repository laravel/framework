<?php

namespace Illuminate\Queue\Events;

class QueueBusy
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @param  string  $queue  The queue name.
     * @param  int  $size  The size of the queue.
     * @param  int|null  $wait  The number of seconds the oldest pending job has been waiting.
     */
    public function __construct(
        public $connectionName,
        public $queue,
        public $size,
        public $wait = null,
    ) {
    }
}
