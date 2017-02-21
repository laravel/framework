<?php

namespace Illuminate\Database\Console\Migrations\TableDroppers;

use Illuminate\Database\ConnectionInterface;
use stdClass;

class Mysql implements TableDropper
{
    /**
     * Drop all tables on the database connection
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return void
     */
    public function dropAllTables(ConnectionInterface $connection)
    {
        $connection->getSchemaBuilder()->disableForeignKeyConstraints();

        collect($connection->select('SHOW TABLES'))
            ->map(function (stdClass $tableProperties) {
                return get_object_vars($tableProperties)[key($tableProperties)];
            })
            ->each(function (string $tableName) use ($connection) {
                $connection->getSchemaBuilder()->drop($tableName);
            });

        $connection->getSchemaBuilder()->enableForeignKeyConstraints();
    }
}