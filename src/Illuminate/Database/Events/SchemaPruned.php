<?php

declare(strict_types=1);

namespace Illuminate\Database\Events;

use Illuminate\Database\Connection;

class SchemaPruned
{
    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    public Connection $connection;

    /**
     * The database connection name.
     *
     * @var string
     */
    public ?string $connectionName;

    /**
     * Path to the deleted directory.
     *
     * @var string
     */
    public string $path;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $path
     * @return void
     */
    public function __construct(Connection $connection, string $path)
    {
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
        $this->path = $path;
    }
}
