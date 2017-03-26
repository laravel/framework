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
        $dbPath = $this->connection->getConfig('database');

        if (file_exists($dbPath)) {
            unlink($dbPath);
        }

        touch($dbPath);
    }
}
