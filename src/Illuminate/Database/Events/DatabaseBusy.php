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
    public $openConnections;

    /**
     * Create a new event instance.
     *
     * @param  string  $database
     * @param  int  $openConnections
     */
    public function __construct($database, $openConnections)
    {
        $this->database = $database;
        $this->openConnections = $openConnections;
    }
}
