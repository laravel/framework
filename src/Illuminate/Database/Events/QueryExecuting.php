<?php

namespace Illuminate\Database\Events;

class QueryExecuting
{
    /**
     * The SQL query that will be executed.
     *
     * @var string
     */
    public $sql;

    /**
     * The array of query bindings.
     *
     * @var array
     */
    public $bindings;

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
     * @param  \Illuminate\Database\Connection  $connection
     * @return void
     */
    public function __construct($sql, $bindings, $connection)
    {
        $this->sql = $sql;
        $this->bindings = $bindings;
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}
