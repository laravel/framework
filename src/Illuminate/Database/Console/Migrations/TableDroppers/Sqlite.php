<?php

namespace Illuminate\Database\Console\Migrations\TableDroppers;

use Illuminate\Database\ConnectionInterface;

class Sqlite implements TableDropper
{
    /**
     * Drop all tables on the database connection.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return void
     */
    public function dropAllTables(ConnectionInterface $connection)
    {
        $dbPath = $connection->getConfig('database');

        if (file_exists($dbPath)) {
            unlink($dbPath);
        }

        touch($dbPath);
    }
}
