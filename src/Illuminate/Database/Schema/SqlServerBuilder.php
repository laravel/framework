<?php

namespace Illuminate\Database\Schema;

class SqlServerBuilder extends Builder
{
    /**
     * Determine if the given table exists.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        list($schema, $table) = $this->parseSchemaAndTable($table);

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
        $this->disableForeignKeyConstraints();

        $this->connection->statement($this->grammar->compileDropAllTables());

        $this->enableForeignKeyConstraints();
    }

    /**
     * Get the column listing for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getColumnListing($table)
    {
        list($schema, $table) = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix().$table;

        $results = $this->connection->select(
            $this->grammar->compileColumnListing(), [$schema, $table]
        );

        return $this->connection->getPostProcessor()->processColumnListing($results);
    }

    /**
     * Parse the table name and extract the schema and table.
     *
     * @param  string  $table
     * @return array
     */
    protected function parseSchemaAndTable($table)
    {
        $table = explode('.', $table);

        if (is_array($schema = $this->connection->getConfig('schema'))) {
            if (in_array($table[0], $schema)) {
                return [array_shift($table), implode('.', $table)];
            }

            $schema = head($schema);
        }
        return [$schema ?: $this->getDefaultSchema(), implode('.', $table)];
    }

    protected function getDefaultSchema()
    {
        $results = $this->connection->select(
            $this->grammar->compileDefaultSchema()
        );
        return head($this->connection->getPostProcessor()->processDefaultSchema($results));
    }
}
