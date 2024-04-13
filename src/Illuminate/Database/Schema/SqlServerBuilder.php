<?php

namespace Illuminate\Database\Schema;

use InvalidArgumentException;

class SqlServerBuilder extends Builder
{
    /**
     * Create a database in the schema.
     *
     * @param  string  $name
     * @return bool
     */
    public function createDatabase($name)
    {
        return $this->connection->statement(
            $this->grammar->compileCreateDatabase($name, $this->connection)
        );
    }

    /**
     * Drop a database from the schema if the database exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function dropDatabaseIfExists($name)
    {
        return $this->connection->statement(
            $this->grammar->compileDropDatabaseIfExists($name)
        );
    }

    /**
     * Determine if the given table exists.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        [$schema, $table] = $this->parseSchemaAndTable($table);

        $schema ??= $this->getDefaultSchema();
        $table = $this->connection->getTablePrefix().$table;

        foreach ($this->getTables() as $value) {
            if (strtolower($table) === strtolower($value['name'])
                && strtolower($schema) === strtolower($value['schema'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the given view exists.
     *
     * @param  string  $view
     * @return bool
     */
    public function hasView($view)
    {
        [$schema, $view] = $this->parseSchemaAndTable($view);

        $schema ??= $this->getDefaultSchema();
        $view = $this->connection->getTablePrefix().$view;

        foreach ($this->getViews() as $value) {
            if (strtolower($view) === strtolower($value['name'])
                && strtolower($schema) === strtolower($value['schema'])) {
                return true;
            }
        }

        return false;
    }

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
     * Get the columns for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getColumns($table)
    {
        [$schema, $table] = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix().$table;

        $results = $this->connection->selectFromWriteConnection(
            $this->grammar->compileColumns($schema, $table)
        );

        return $this->connection->getPostProcessor()->processColumns($results);
    }

    /**
     * Get the indexes for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getIndexes($table)
    {
        [$schema, $table] = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix().$table;

        return $this->connection->getPostProcessor()->processIndexes(
            $this->connection->selectFromWriteConnection($this->grammar->compileIndexes($schema, $table))
        );
    }

    /**
     * Get the foreign keys for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getForeignKeys($table)
    {
        [$schema, $table] = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix().$table;

        return $this->connection->getPostProcessor()->processForeignKeys(
            $this->connection->selectFromWriteConnection($this->grammar->compileForeignKeys($schema, $table))
        );
    }

    /**
     * Get the default schema for the connection.
     *
     * @return string
     */
    protected function getDefaultSchema()
    {
        return $this->connection->scalar($this->grammar->compileDefaultSchema());
    }

    /**
     * Parse the database object reference and extract the schema and table.
     *
     * @param  string  $reference
     * @return array
     */
    protected function parseSchemaAndTable($reference)
    {
        $parts = array_pad(explode('.', $reference, 2), -2, null);

        if (str_contains($parts[1], '.')) {
            $database = $parts[0];

            throw new InvalidArgumentException("Using three-part reference is not supported, you may use `Schema::connection('$database')` instead.");
        }

        return $parts;
    }
}
