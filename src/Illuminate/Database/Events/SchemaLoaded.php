<?php

namespace Illuminate\Database\Events;

use Illuminate\Database\Connection;

class SchemaLoaded
{
    /**
     * The database connection name.
     */
    public string $connectionName;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Connection $connection,
        public string $path,
    ) {
        $this->connectionName = $connection->getName();
    }
}
