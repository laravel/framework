<?php

namespace Illuminate\Queue\Events;

class JobPopping
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @return void
     */
    public function __construct(
        public $connectionName,
    ) {
    }
}
