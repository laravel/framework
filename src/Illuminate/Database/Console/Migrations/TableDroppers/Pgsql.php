<?php

namespace Illuminate\Database\Console\Migrations\TableDroppers;

use Illuminate\Database\ConnectionInterface;

class Pgsql implements TableDropper
{
    /**
     * Drop all tables on the database connection.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return void
     */
    public function dropAllTables(ConnectionInterface $connection)
    {
        $tableNames = $this->getTableNames($connection);

        if ($tableNames->isEmpty()) {
            return;
        }

        $connection->statement("DROP TABLE {$tableNames->implode(',')} CASCADE");
    }

    /**
     * Get a list of all tables in the schema.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return \Illuminate\Support\Collection
     */
    protected function getTableNames(ConnectionInterface $connection)
    {
        return collect(
            $connection->select('SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = ?', [$connection->getConfig('schema')])
        )->pluck('tablename');
    }
}
