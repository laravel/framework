<?php

namespace Illuminate\Queue\Events;

class Looping
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @param  string  $queue  The queue name.
     * @return void
     */
    public function __construct(
        public $connectionName,
        public $queue,
    ) {
    }
}
