<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\MariaDbConnection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\SqlServerConnection;
use Illuminate\Support\Arr;

abstract class DatabaseInspectionCommand extends Command
{
    /**
     * Get a human-readable name for the given connection.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  string  $database
     * @return string
     *
     * @deprecated Use $connection->getName() instead.
     */
    protected function getConnectionName(ConnectionInterface $connection, $database)
    {
        return match (true) {
            $connection instanceof MariaDbConnection => 'MariaDB',
            $connection instanceof MySqlConnection && $connection->isMaria() => 'MariaDB',
            $connection instanceof MySqlConnection => 'MySQL',
            $connection instanceof PostgresConnection => 'PostgreSQL',
            $connection instanceof SQLiteConnection => 'SQLite',
            $connection instanceof SqlServerConnection => 'SQL Server',
            default => $database,
        };
    }

    /**
     * Get the number of open connections for a database.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return int|null
     */
    protected function getConnectionCount(ConnectionInterface $connection)
    {
        return $connection->getQueryGrammar()->getConnectionCount();
    }

    /**
     * Get the connection configuration details for the given connection.
     *
     * @param  string  $database
     * @return array
     */
    protected function getConfigFromDatabase($database)
    {
        $database ??= config('database.default');

        return Arr::except(config('database.connections.'.$database), ['password']);
    }

    /**
     * Remove the table prefix from a table name, if it exists.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  string  $table
     * @return string
     */
    protected function withoutTablePrefix(ConnectionInterface $connection, string $table)
    {
        $prefix = $connection->getTablePrefix();

        return str_starts_with($table, $prefix)
            ? substr($table, strlen($prefix))
            : $table;
    }
}
