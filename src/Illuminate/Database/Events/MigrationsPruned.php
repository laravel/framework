<?php

namespace Illuminate\Database\Events;

use Illuminate\Database\Connection;

class MigrationsPruned
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
     * @var string|null
     */
    public $connectionName;

    /**
     * The path to the directory where migrations were pruned.
     *
     * @var string
     */
    public $path;

    /**
     * The migration files that were removed.
     *
     * @var array<int, string>
     */
    public $migrationFilesDeleted;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $path
     * @param  array<int, string>  $migrationFilesDeleted
     */
    public function __construct(Connection $connection, string $path, array $migrationFilesDeleted = [])
    {
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
        $this->path = $path;
        $this->migrationFilesDeleted = $migrationFilesDeleted;
    }
}
