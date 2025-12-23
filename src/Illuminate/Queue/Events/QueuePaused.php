<?php

namespace Illuminate\Queue\Events;

class QueuePaused
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connection  The connection name.
     * @param  string  $queue  The queue name.
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl  The TTL for the pause, if set.
     */
    public function __construct(
        public $connection,
        public $queue,
        public $ttl = null,
    ) {
    }
}
