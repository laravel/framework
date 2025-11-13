<?php

namespace Illuminate\Queue\Events;

class QueueDepthExceeded
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connection  The connection name.
     * @param  string  $queue  The queue name.
     * @param  int  $size  The current size of the queue.
     * @param  int  $threshold  The configured threshold.
     */
    public function __construct(
        public $connection,
        public $queue,
        public $size,
        public $threshold,
    ) {
    }
}
