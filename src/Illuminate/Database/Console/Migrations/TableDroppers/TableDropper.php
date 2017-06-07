<?php

namespace Illuminate\Database\Console\Migrations\TableDroppers;

use Illuminate\Database\ConnectionInterface;

interface TableDropper
{
    /**
     * Drop all tables on the database connection.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @return void
     */
    public function dropAllTables(ConnectionInterface $connection);
}
