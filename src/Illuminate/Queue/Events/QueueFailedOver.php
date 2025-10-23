<?php

namespace Illuminate\Queue\Events;

class QueueFailedOver
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The queue connection that failed.
     * @param  mixed  $command  The command that was being processed.
     */
    public function __construct(
        public ?string $connectionName,
        public mixed $command,
    ) {
    }
}
