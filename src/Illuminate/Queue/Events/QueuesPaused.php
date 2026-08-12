<?php

namespace Illuminate\Queue\Events;

class QueuesPaused
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public array $except = [],
    ) {
    }
}
