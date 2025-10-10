<?php

namespace Illuminate\Bus\Events;

use Illuminate\Bus\Batch;

class QueueFailedOver
{
    /**
     * Create a new event instance.
     *
     * @param  string|null  $connectionName  The queue connection that failed.
     * @param  mixed  $command  The command / job that was queued.
     */
    public function __construct(
        public ?string $connectionName,
        public mixed $command,
    ) {
    }
}
