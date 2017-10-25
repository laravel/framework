<?php

namespace Illuminate\Database\Schema;

class PostgresBuilder extends Builder
{
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

    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $tables = [];

        $excludedTables = ['spatial_ref_sys'];

        foreach ($this->getAllTables() as $row) {
            $row = (array) $row;

            $table = reset($row);

            if (! in_array($table, $excludedTables)) {
                $tables[] = $table;
            }
        }

        if (empty($tables)) {
            return;
        }

        $this->connection->statement(
            $this->grammar->compileDropAllTables($tables)
        );
    }

    /**
     * Get all of the table names for the database.
     *
     * @return array
     */
    protected function getAllTables()
    {
        return $this->connection->select(
            $this->grammar->compileGetAllTables($this->connection->getConfig('schema'))
        );
    }

    /**
     * Get the column listing for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getColumnListing($table)
    {
        if (is_array($schema = $this->connection->getConfig('schema'))) {
            $schema = head($schema);
        }

        $schema = $schema ? $schema : 'public';

        $table = $this->connection->getTablePrefix().$table;

        $results = $this->connection->select(
            $this->grammar->compileColumnListing(), [$schema, $table]
        );

        return $this->connection->getPostProcessor()->processColumnListing($results);
    }
}
