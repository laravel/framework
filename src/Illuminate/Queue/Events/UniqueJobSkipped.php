<?php

namespace Illuminate\Queue\Events;

class UniqueJobSkipped
{
    /**
     * Create a new event instance.
     *
     * @param  mixed  $job  The job that was not dispatched.
     */
    public function __construct(
        public $job,
    ) {
    }
}
