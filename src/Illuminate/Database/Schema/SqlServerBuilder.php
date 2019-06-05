<?php

namespace Illuminate\Database\Schema;

class SqlServerBuilder extends Builder
{
    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $this->connection->statement($this->grammar->dropAllForeignKeys());
        $this->connection->statement($this->grammar->compileDropAllTables());
    }
}
