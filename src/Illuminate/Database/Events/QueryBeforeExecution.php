<?php

namespace Illuminate\Database\Events;

class QueryBeforeExecution
{
    /**
     * The SQL query that was executed.
     *
     * @var string
     */
    public $sql;

    /**
     * The query verb.
     *
     * @var string
     */
    public $verb;

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

    const SELECT = 'SELECT';
    const INSERT = 'INSERT';
    const UPDATE = 'UPDATE';
    const DELETE = 'DELETE';


    /**
     * Create a new event instance.
     *
     * @param  string  $sql
     * @param  string  $verb
     * @param  array  $bindings
     * @param  \Illuminate\Database\Connection  $connection
     * @return void
     */
    public function __construct(&$sql, $verb, $bindings, $connection)
    {
        $this->sql = &$sql;
        $this->verb = $verb;
        $this->bindings = $bindings;
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}
