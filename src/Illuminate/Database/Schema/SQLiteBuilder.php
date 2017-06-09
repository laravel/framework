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
        $this->connection->select($this->grammar->compileEnableWriteableSchema());

        $this->connection->select($this->grammar->compileDropAllTables());

        $this->connection->select($this->grammar->compileDisableWriteableSchema());
    }
}
