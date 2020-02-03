<?php

namespace Illuminate\Database\Events;

class NoPendingMigrations
{
    /**
     * The migration method that was called.
     *
     * @var string
     */
    public $method;

    /**
     * Create a new event instance.
     *
     * @param  string  $method
     * @return void
     */
    public function __construct($method)
    {
        $this->method = $method;
    }
}
