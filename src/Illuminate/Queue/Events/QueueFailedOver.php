<?php

namespace Illuminate\Queue\Events;

class QueueFailedOver
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The queue connection that failed.
     * @param  \Closure|string|object  $job  The job instance.
     */
    public function __construct(
        public ?string $connectionName,
        public mixed $command,
    ) {
    }
}
