<?php

namespace Illuminate\Database\Events;

class DatabaseBusy
{
    /**
     * The database connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The number of open connections.
     *
     * @var int
     */
    public $connections;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  int  $connections
     */
    public function __construct($connectionName, $connections)
    {
        $this->connectionName = $connectionName;
        $this->connections = $connections;
    }
}
