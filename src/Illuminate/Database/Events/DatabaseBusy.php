<?php

namespace Illuminate\Database\Events;

class DatabaseBusy
{
    /**
     * The database configuration key.
     *
     * @var string
     */
    public $database;

    /**
     * The number of open connections.
     *
     * @var int
     */
    public $connections;

    /**
     * Create a new event instance.
     *
     * @param  string  $database
     * @param  int  $connections
     */
    public function __construct($database, $connections)
    {
        $this->database = $database;
        $this->connections = $connections;
    }
}
