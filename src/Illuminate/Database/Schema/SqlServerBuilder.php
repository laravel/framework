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
        $this->disableForeignKeyConstraints();

        $this->connection->statement($this->grammar->compileDropAllTables());

        $this->enableForeignKeyConstraints();
    }

    /**
     * Determine if the given database exists.
     *
     * @param  string  $database
     * @return bool
     */
    public function hasDatabase($database)
    {
        return count($this->connection->select(
            $this->grammar->compileDatabaseExists(), [$database]
        )) > 0;
    }

    /**
     * Create a new database.
     *
     * @param  string  $database
     * @return bool
     */
    public function createDatabase($database)
    {
        return $this->connection->statement(
            $this->grammar->compileCreateDatabase($database, $this->connection)
        );
    }
}
