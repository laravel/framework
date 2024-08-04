<?php

namespace Illuminate\Database\Schema;

class MySqlBuilder extends Builder
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
     * Get the tables for the database.
     *
     * @return array
     */
    public function getTables()
    {
        return $this->connection->getPostProcessor()->processTables(
            $this->connection->selectFromWriteConnection(
                $this->grammar->compileTables($this->connection->getDatabaseName())
            )
        );
    }

    /**
     * Get the views for the database.
     *
     * @return array
     */
    public function getViews()
    {
        return $this->connection->getPostProcessor()->processViews(
            $this->connection->selectFromWriteConnection(
                $this->grammar->compileViews($this->connection->getDatabaseName())
            )
        );
    }

    /**
     * Get the columns for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getColumns($table)
    {
        $table = $this->connection->getTablePrefix().$table;

        $results = $this->connection->selectFromWriteConnection(
            $this->grammar->compileColumns($this->connection->getDatabaseName(), $table)
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
        $table = $this->connection->getTablePrefix().$table;

        return $this->connection->getPostProcessor()->processIndexes(
            $this->connection->selectFromWriteConnection(
                $this->grammar->compileIndexes($this->connection->getDatabaseName(), $table)
            )
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
        $table = $this->connection->getTablePrefix().$table;

        return $this->connection->getPostProcessor()->processForeignKeys(
            $this->connection->selectFromWriteConnection(
                $this->grammar->compileForeignKeys($this->connection->getDatabaseName(), $table)
            )
        );
    }

    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $tables = array_column($this->getTables(), 'name');

        if (empty($tables)) {
            return;
        }

        $this->disableForeignKeyConstraints();

        $this->connection->statement(
            $this->grammar->compileDropAllTables($tables)
        );

        $this->enableForeignKeyConstraints();
    }

    /**
     * Drop all views from the database.
     *
     * @return void
     */
    public function dropAllViews()
    {
        $views = array_column($this->getViews(), 'name');

        if (empty($views)) {
            return;
        }

        $this->connection->statement(
            $this->grammar->compileDropAllViews($views)
        );
    }
}
