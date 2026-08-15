<?php

namespace Illuminate\Queue\Events;

class QueueForwarded
{
    /**
     * Create a new event instance.
     *
     * @param  string|null  $connectionName  The queue connection the queue was forwarded on.
     * @param  string  $from  The queue that was forwarded.
     * @param  string  $to  The queue that was forwarded to.
     */
    public function __construct(
        public ?string $connectionName,
        public string $from,
        public string $to,
    ) {
    }
}
