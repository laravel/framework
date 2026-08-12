<?php

namespace Illuminate\Queue\Events;

class QueuesResumed
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public array $except = [],
    ) {
    }
}
