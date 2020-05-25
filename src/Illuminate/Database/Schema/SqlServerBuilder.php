<?php

namespace Illuminate\Database\Schema;

use Illuminate\Database\Schema\Grammars\SqlServerGrammar;

class SqlServerBuilder extends Builder
{
    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $this->connection->statement($this->grammar->compileDropAllForeignKeys());

        $this->connection->statement($this->grammar->compileDropAllTables());
    }

    /**
     * Drop all views from the database.
     *
     * @return void
     */
    public function dropAllViews()
    {
        $this->connection->statement($this->grammar->compileDropAllViews());
    }

    /**
     * Determine if the given table exists.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        /** @var SqlServerGrammar $grammar */
        $grammar = $this->grammar;
        $table = $this->connection->getTablePrefix().$table;
        [$schema, $tableName] = $grammar->parseSchemaAndTable($table);

        $result = $this->connection->selectFromWriteConnection(
                $grammar->compileTableExists() , [$tableName, ($schema  ?? 'SCHEMA_NAME()')]
            );
        return count($result) > 0;
    }
}
