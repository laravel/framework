<?php

namespace Illuminate\Database\Schema;

use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class SQLiteBuilder extends Builder
{
    /**
     * Create a database in the schema.
     *
     * @param  string  $name
     * @return bool
     */
    public function createDatabase($name)
    {
        return File::put($name, '') !== false;
    }

    /**
     * Drop a database from the schema if the database exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function dropDatabaseIfExists($name)
    {
        return File::exists($name)
            ? File::delete($name)
            : true;
    }

    /**
     * Get the tables that belong to the connection.
     *
     * @param  string|string[]|null  $schema
     * @return array
     */
    public function getTables($schema = null)
    {
        try {
            $withSize = $this->connection->scalar($this->grammar->compileDbstatExists());
        } catch (QueryException) {
            $withSize = false;
        }

        if (version_compare($this->connection->getServerVersion(), '3.37.0', '<')) {
            $schema ??= array_column($this->getSchemas(), 'name');

            $tables = [];

            foreach (Arr::wrap($schema) as $name) {
                $tables = array_merge($tables, $this->connection->selectFromWriteConnection(
                    $this->grammar->compileLegacyTables($name, $withSize)
                ));
            }

            return $this->connection->getPostProcessor()->processTables($tables);
        }

        return $this->connection->getPostProcessor()->processTables(
            $this->connection->selectFromWriteConnection(
                $this->grammar->compileTables($schema, $withSize)
            )
        );
    }

    /**
     * Get the views that belong to the connection.
     *
     * @param  string|string[]|null  $schema
     * @return array
     */
    public function getViews($schema = null)
    {
        $schema ??= array_column($this->getSchemas(), 'name');

        $views = [];

        foreach (Arr::wrap($schema) as $name) {
            $views = array_merge($views, $this->connection->selectFromWriteConnection(
                $this->grammar->compileViews($name)
            ));
        }

        return $this->connection->getPostProcessor()->processViews($views);
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

        return $this->connection->getPostProcessor()->processColumns(
            $this->connection->selectFromWriteConnection($this->grammar->compileColumns($schema, $table)),
            $this->connection->scalar($this->grammar->compileSqlCreateStatement($schema, $table))
        );
    }

    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $database = $this->connection->getDatabaseName();

        if ($database !== ':memory:' &&
            ! str_contains($database, '?mode=memory') &&
            ! str_contains($database, '&mode=memory')
        ) {
            return $this->refreshDatabaseFile();
        }

        $this->connection->select($this->grammar->compileEnableWriteableSchema());

        foreach ($this->getCurrentSchemaListing() as $schema) {
            $this->connection->select($this->grammar->compileDropAllTables($schema));
        }

        $this->connection->select($this->grammar->compileDisableWriteableSchema());

        $this->connection->select($this->grammar->compileRebuild());
    }

    /**
     * Drop all views from the database.
     *
     * @return void
     */
    public function dropAllViews()
    {
        $this->connection->select($this->grammar->compileEnableWriteableSchema());

        foreach ($this->getCurrentSchemaListing() as $schema) {
            $this->connection->select($this->grammar->compileDropAllViews($schema));
        }

        $this->connection->select($this->grammar->compileDisableWriteableSchema());

        $this->connection->select($this->grammar->compileRebuild());
    }

    /**
     * Set the busy timeout.
     *
     * @param  int  $milliseconds
     * @return bool
     */
    public function setBusyTimeout($milliseconds)
    {
        return $this->connection->statement(
            $this->grammar->compileSetBusyTimeout($milliseconds)
        );
    }

    /**
     * Set the journal mode.
     *
     * @param  string  $mode
     * @return bool
     */
    public function setJournalMode($mode)
    {
        return $this->connection->statement(
            $this->grammar->compileSetJournalMode($mode)
        );
    }

    /**
     * Set the synchronous mode.
     *
     * @param  int  $mode
     * @return bool
     */
    public function setSynchronous($mode)
    {
        return $this->connection->statement(
            $this->grammar->compileSetSynchronous($mode)
        );
    }

    /**
     * Empty the database file.
     *
     * @return void
     */
    public function refreshDatabaseFile()
    {
        file_put_contents($this->connection->getDatabaseName(), '');
    }

    /**
     * Get the names of current schemas for the connection.
     *
     * @return string[]|null
     */
    public function getCurrentSchemaListing()
    {
        return ['main'];
    }
}
