<?php

namespace Illuminate\Database\Schema;

class SQLiteBuilder extends Builder
{
    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        unlink($this->connection->getDatabaseName());

        touch($this->connection->getDatabaseName());
    }
}
