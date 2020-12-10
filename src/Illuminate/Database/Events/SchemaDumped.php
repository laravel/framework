<?php

namespace Illuminate\Database\Events;

class SchemaDumped
{
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
     * The path to the schema dump.
     *
     * @var string
     */
    public $path;

    /**
     * Extra flags added to the dumper command.
     *
     * @var string|null
     */
    public $extraDumpFlags;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $path
     * @param  string|null  $extraDumpFlags
     * @return void
     */
    public function __construct($connection, $path, $extraDumpFlags = null)
    {
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
        $this->path = $path;
        $this->extraDumpFlags = $extraDumpFlags;
    }
}
