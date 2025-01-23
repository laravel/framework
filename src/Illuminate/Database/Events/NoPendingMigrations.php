<?php

namespace Illuminate\Database\Events;

class NoPendingMigrations
{
    /**
     * Create a new event instance.
     *
     * @param  string  $method  The migration method that was called.
     * @return void
     */
    public function __construct(
        public $method,
    ) {
    }
}
