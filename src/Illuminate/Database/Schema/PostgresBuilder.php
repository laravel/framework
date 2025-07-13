<?php

namespace Illuminate\Database\Schema;

use Illuminate\Database\Concerns\ParsesSearchPath;

class PostgresBuilder extends Builder
{
    use ParsesSearchPath;

    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $tables = [];
        $hypertables = [];

        $excludedTables = $this->connection->getConfig('dont_drop') ?? ['spatial_ref_sys'];
        $hasTimescaleDB = ! empty($this->connection->select("SELECT 1 FROM pg_extension WHERE extname = 'timescaledb'"));

        if ($hasTimescaleDB) {
            $hypertables = $this->connection->select(
                "SELECT hypertable_schema || '.' || hypertable_name as name FROM timescaledb_information.hypertables"
            );
            $hypertables = array_column($hypertables, 'name');
        }

        foreach ($this->getTables($this->getCurrentSchemaListing()) as $table) {
            if (! in_array($table['name'], $excludedTables) && ! in_array($table['schema_qualified_name'], $excludedTables)) {
                if (in_array($table['schema_qualified_name'], $hypertables)) {
                    $this->connection->statement("DROP TABLE IF EXISTS {$table['schema_qualified_name']} CASCADE");
                } else {
                    $tables[] = $table['schema_qualified_name'];
                }
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
        $views = array_column($this->getViews($this->getCurrentSchemaListing()), 'schema_qualified_name');

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

        foreach ($this->getTypes($this->getCurrentSchemaListing()) as $type) {
            if (! $type['implicit']) {
                if ($type['type'] === 'domain') {
                    $domains[] = $type['schema_qualified_name'];
                } else {
                    $types[] = $type['schema_qualified_name'];
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
     * Get the current schemas for the connection.
     *
     * @return string[]
     */
    public function getCurrentSchemaListing()
    {
        return array_map(
            fn ($schema) => $schema === '$user' ? $this->connection->getConfig('username') : $schema,
            $this->parseSearchPath(
                $this->connection->getConfig('search_path')
                    ?: $this->connection->getConfig('schema')
                    ?: 'public'
            )
        );
    }
}
