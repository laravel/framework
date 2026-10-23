<?php

namespace Illuminate\Database\Events;

use Throwable;

class QueryFailed
{
    /**
     * The SQL query that failed.
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
     * The number of milliseconds it took before the query failed.
     *
     * @var float|null
     */
    public $time;

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
     * The exception that was thrown.
     *
     * @var \Throwable
     */
    public $exception;

    /**
     * The PDO read / write type for the failed query.
     *
     * @var null|'read'|'write'|'direct'
     */
    public $readWriteType;

    /**
     * Create a new event instance.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  float|null  $time
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Throwable  $exception
     * @param  null|'read'|'write'|'direct'  $readWriteType
     */
    public function __construct($sql, $bindings, $time, $connection, Throwable $exception, $readWriteType = null)
    {
        $this->sql = $sql;
        $this->time = $time;
        $this->bindings = $bindings;
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
        $this->exception = $exception;
        $this->readWriteType = $readWriteType;
    }

    /**
     * Get the raw SQL representation of the query with embedded bindings.
     *
     * @return string
     */
    public function toRawSql()
    {
        return $this->connection
            ->query()
            ->getGrammar()
            ->substituteBindingsIntoRawSql($this->sql, $this->connection->prepareBindings($this->bindings));
    }
}
