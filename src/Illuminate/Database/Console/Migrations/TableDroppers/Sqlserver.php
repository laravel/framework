<?php

namespace Illuminate\Database\Console\Migrations\TableDroppers;

use Illuminate\Database\ConnectionInterface;

class Sqlserver implements TableDropper
{
    /**
     * Drop all tables on the database connection.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return void
     */
    public function dropAllTables(ConnectionInterface $connection)
    {
        $connection->getSchemaBuilder()->disableForeignKeyConstraints();

        $connection->statement("EXEC sp_msforeachtable 'DROP TABLE ?'");

        $connection->getSchemaBuilder()->enableForeignKeyConstraints();
    }
}
