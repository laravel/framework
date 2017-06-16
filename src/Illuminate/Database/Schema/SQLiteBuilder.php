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
        if ($this->connection->getDatabaseName() != ':memory:') {
            return $this->deleteDatabaseFile();
        }

        $this->connection->select($this->grammar->compileEnableWriteableSchema());

        $this->connection->select($this->grammar->compileDropAllTables());

        $this->connection->select($this->grammar->compileDisableWriteableSchema());
    }

    /**
     * Delete the database file.
     *
     * @return void
     */
    public function deleteDatabaseFile()
    {
        unlink($this->connection->getDatabaseName());

        touch($this->connection->getDatabaseName());
    }
}
