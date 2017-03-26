<?php

namespace Illuminate\Database\Schema;

class PostgresBuilder extends Builder
{
    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $tables = [];

        foreach ($this->connection->select($this->grammar->compileGetAllTables($this->connection->getConfig('schema'))) as $table) {
            $tables[] = get_object_vars($table)[key($table)];
        }

        if (empty($tables)) {
            return;
        }

        $this->connection->statement($this->grammar->compileDropAllTables($tables));
    }

    /**
     * Determine if the given table exists.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        if (is_array($schema = $this->connection->getConfig('schema'))) {
            $schema = head($schema);
        }

        $schema = $schema ? $schema : 'public';

        $table = $this->connection->getTablePrefix().$table;

        return count($this->connection->select(
            $this->grammar->compileTableExists(), [$schema, $table]
        )) > 0;
    }
}
