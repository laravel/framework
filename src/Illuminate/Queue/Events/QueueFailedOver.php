<?php

namespace Illuminate\Queue\Events;

use Throwable;

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
        public Throwable $exception,
    ) {
    }
}
