<?php

namespace Illuminate\Database\Schema;

use Illuminate\Database\QueryException;
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
     * Determine if the given table exists.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        $table = $this->connection->getTablePrefix().$table;

        return (bool) $this->connection->scalar(
            $this->grammar->compileTableExists($table)
        );
    }

    /**
     * Get the tables for the database.
     *
     * @param  bool  $withSize
     * @return array
     */
    public function getTables($withSize = true)
    {
        if ($withSize) {
            try {
                $withSize = $this->connection->scalar($this->grammar->compileDbstatExists());
            } catch (QueryException $e) {
                $withSize = false;
            }
        }

        return $this->connection->getPostProcessor()->processTables(
            $this->connection->selectFromWriteConnection($this->grammar->compileTables($withSize))
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

        return $this->connection->getPostProcessor()->processColumns(
            $this->connection->selectFromWriteConnection($this->grammar->compileColumns($table)),
            $this->connection->scalar($this->grammar->compileSqlCreateStatement($table))
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

        $this->connection->select($this->grammar->compileDropAllTables());

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

        $this->connection->select($this->grammar->compileDropAllViews());

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
}
