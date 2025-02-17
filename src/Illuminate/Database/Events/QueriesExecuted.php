<?php

namespace Illuminate\Database\Events;

class QueriesExecuted
{
    /**
     * Queries that were run.
     *
     * @var array
     */
    public $queryLog;

    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    public $connection;

    /**
     * The database connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * Create a new event instance.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  float|null  $time
     * @param  \Illuminate\Database\Connection  $connection
     * @return void
     */
    public function __construct($queryLog, $connection)
    {
        $this->queryLog = $queryLog;
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}
