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
        if ($this->connection->getDatabaseName() !== ':memory:') {
            return $this->refreshDatabaseFile();
        }

        $this->connection->select($this->grammar->compileEnableWriteableSchema());

        $this->connection->select($this->grammar->compileDropAllTables());

        $this->connection->select($this->grammar->compileDisableWriteableSchema());
    }

    /**
     * Empty the database file.
     *
     * @return void
     */
    public function refreshDatabaseFile()
    {
        file_put_contents($this->connection->getDatabaseName(), '');
    }
}
