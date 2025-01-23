<?php

namespace Illuminate\Database\Schema;

use Illuminate\Database\Concerns\ParsesSearchPath;
use InvalidArgumentException;

class PostgresBuilder extends Builder
{
    use ParsesSearchPath {
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
     * Determine if the given table exists.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        [$schema, $table] = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix().$table;

        return (bool) $this->connection->scalar(
            $this->grammar->compileTableExists($schema, $table)
        );
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
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $tables = [];

        $excludedTables = $this->connection->getConfig('dont_drop') ?? ['spatial_ref_sys'];

        $schemas = $this->getSchemas();

        foreach ($this->getTables() as $table) {
            $qualifiedName = $table['schema'].'.'.$table['name'];

            if (in_array($table['schema'], $schemas) &&
                empty(array_intersect([$table['name'], $qualifiedName], $excludedTables))) {
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

        $schemas = $this->getSchemas();

        foreach ($this->getViews() as $view) {
            if (in_array($view['schema'], $schemas)) {
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
     * Drop all types from the database.
     *
     * @return void
     */
    public function dropAllTypes()
    {
        $types = [];
        $domains = [];

        $schemas = $this->getSchemas();

        foreach ($this->getTypes() as $type) {
            if (! $type['implicit'] && in_array($type['schema'], $schemas)) {
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
     * Get the schemas for the connection.
     *
     * @return array
     */
    public function getSchemas()
    {
        return $this->parseSearchPath(
            $this->connection->getConfig('search_path') ?: $this->connection->getConfig('schema') ?: 'public'
        );
    }

    /**
     * Parse the database object reference and extract the schema and table.
     *
     * @param  string  $reference
     * @return array
     */
    public function parseSchemaAndTable($reference)
    {
        $parts = explode('.', $reference);

        if (count($parts) > 2) {
            $database = $parts[0];

            throw new InvalidArgumentException("Using three-part reference is not supported, you may use `Schema::connection('$database')` instead.");
        }

        // We will use the default schema unless the schema has been specified in the
        // query. If the schema has been specified in the query then we can use it
        // instead of a default schema configured in the connection search path.
        $schema = $this->getSchemas()[0];

        if (count($parts) === 2) {
            $schema = $parts[0];
            array_shift($parts);
        }

        return [$schema, $parts[0]];
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
