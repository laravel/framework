<?php

namespace Illuminate\Database\Events;

class BeforeQueryExecuted
{
    /**
     * The SQL query that was executed.
     *
     * @var string
     */
    public $sql;

    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    public $connection;

    /**
     * Create a new event instance.
     *
     * @param  string  $sql
     * @param  \Illuminate\Database\Connection  $connection
     * @return void
     */
    public function __construct(&$sql, $connection)
    {
        $this->sql = &$sql;
        $this->connection = $connection;
    }
}
