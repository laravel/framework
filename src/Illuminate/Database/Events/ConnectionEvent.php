<?php

namespace Illuminate\Database\Events;

use Illuminate\Database\Connection;

abstract class ConnectionEvent
{
    /**
     * The name of the connection.
     */
    public string $connectionName;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Connection $connection,
    ) {
        $this->connectionName = $connection->getName();
    }
}
