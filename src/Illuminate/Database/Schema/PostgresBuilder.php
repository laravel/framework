<?php

namespace Illuminate\Database\Schema;

use Illuminate\Database\Concerns\ParsesSearchPath;
use Illuminate\Database\Concerns\UsesSchemaAwareTables;

class PostgresBuilder extends Builder
{
    use ParsesSearchPath, UsesSchemaAwareTables {
        parseSearchPath as baseParseSearchPath;
    }

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
     * Get the user-defined types that belong to the database.
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->connection->getPostProcessor()->processTypes(
            $this->connection->selectFromWriteConnection($this->grammar->compileTypes())
        );
    }

    /**
     * Get all of the table names for the database.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @return array
     */
    public function getAllTables()
    {
        return $this->connection->select(
            $this->grammar->compileGetAllTables(
                $this->parseSearchPath(
                    $this->connection->getConfig('search_path') ?: $this->connection->getConfig('schema')
                )
            )
        );
    }

    /**
     * Get all of the view names for the database.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @return array
     */
    public function getAllViews()
    {
        return $this->connection->select(
            $this->grammar->compileGetAllViews(
                $this->parseSearchPath(
                    $this->connection->getConfig('search_path') ?: $this->connection->getConfig('schema')
                )
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
        $tables = [];

        $excludedTables = $this->grammar->escapeNames(
            $this->connection->getConfig('dont_drop') ?? ['spatial_ref_sys']
        );

        $schemas = $this->grammar->escapeNames($this->getSchemas());

        foreach ($this->getTables() as $table) {
            $qualifiedName = $table['schema'].'.'.$table['name'];

            if (empty(array_intersect($this->grammar->escapeNames([$table['name'], $qualifiedName]), $excludedTables))
                && in_array($this->grammar->escapeNames([$table['schema']])[0], $schemas)) {
                $tables[] = $qualifiedName;
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
     * Drop all views from the database.
     *
     * @return void
     */
    public function dropAllViews()
    {
        $views = [];

        $schemas = $this->grammar->escapeNames($this->getSchemas());

        foreach ($this->getViews() as $view) {
            if (in_array($this->grammar->escapeNames([$view['schema']])[0], $schemas)) {
                $views[] = $view['schema'].'.'.$view['name'];
            }
        }

        if (empty($views)) {
            return;
        }

        $this->connection->statement(
            $this->grammar->compileDropAllViews($views)
        );
    }

    /**
     * Get all of the type names for the database.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @return array
     */
    public function getAllTypes()
    {
        return $this->connection->select(
            $this->grammar->compileGetAllTypes()
        );
    }

    /**
     * Drop all types from the database.
     *
     * @return void
     */
    public function dropAllTypes()
    {
        $types = [];
        $domains = [];

        $schemas = $this->grammar->escapeNames($this->getSchemas());

        foreach ($this->getTypes() as $type) {
            if (! $type['implicit'] && in_array($this->grammar->escapeNames([$type['schema']])[0], $schemas)) {
                if ($type['type'] === 'domain') {
                    $domains[] = $type['schema'].'.'.$type['name'];
                } else {
                    $types[] = $type['schema'].'.'.$type['name'];
                }
            }
        }

        if (! empty($types)) {
            $this->connection->statement($this->grammar->compileDropAllTypes($types));
        }

        if (! empty($domains)) {
            $this->connection->statement($this->grammar->compileDropAllDomains($domains));
        }
    }

    /**
     * Get the default schema for the connection
     *
     * @return string
     */
    public function getDefaultSchema()
    {
        return $this->getSchemas()[0];
    }

    /**
     * Get the indexes for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getIndexes($table)
    {
        [, $schema, $table] = $this->parseSchemaAndTable($table);

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
        [, $schema, $table] = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix().$table;

        return $this->connection->getPostProcessor()->processForeignKeys(
            $this->connection->selectFromWriteConnection($this->grammar->compileForeignKeys($schema, $table))
        );
    }

    /**
     * Get the schemas for the connection.
     *
     * @return array
     */
    protected function getSchemas()
    {
        return $this->parseSearchPath(
            $this->connection->getConfig('search_path') ?: $this->connection->getConfig('schema') ?: 'public'
        );
    }


    /**
     * Parse the "search_path" configuration value into an array.
     *
     * @param  string|array|null  $searchPath
     * @return array
     */
    protected function parseSearchPath($searchPath)
    {
        return array_map(function ($schema) {
            return $schema === '$user'
                ? $this->connection->getConfig('username')
                : $schema;
        }, $this->baseParseSearchPath($searchPath));
    }
}
