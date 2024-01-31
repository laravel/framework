<?php

namespace Illuminate\Database\Concerns;

trait UsesSchemaAwareTables
{

    /**
     * Get the columns for a given table.
     *
     * @param string $table
     *
     * @return array
     */
    public function getColumns($table)
    {
        [$database, $schema, $table] = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix() . $table;

        $results = $this->connection->selectFromWriteConnection(
            $this->grammar->compileColumns($database, $schema, $table)
        );

        return $this->connection->getPostProcessor()->processColumns($results);
    }

    /**
     * Determine if the given table exists.
     *
     * @param string $table
     *
     * @return bool
     */
    public function hasTable($table)
    {
        [, $schema, $table] = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix() . $table;

        foreach ($this->getTables() as $value) {
            if (strtolower($table) === strtolower($value['name']) &&
                strtolower($schema) === strtolower($value['schema'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse the database object reference and extract the database, schema, and table.
     *
     * @param string $reference
     *
     * @return array
     */
    protected function parseSchemaAndTable($reference)
    {
        $parts = explode('.', $reference);

        // In order to be fully backward compatibel with previous version where users
        // may have used square brackets with the identifiers in SQLServer grammar
        // e.g. "schema.[table1]". We shall trim the parts for their occurrence.
        $parts = array_map(function ($part) {
            return trim($part, '[]');
        }, $parts);

        $database = $this->connection->getConfig('database');

        // If the reference contains a database name, we will use that instead of the
        // default database name for the connection. This allows the database name
        // to be specified in the query instead of at the full connection level.
        if (count($parts) === 3) {
            $database = $parts[0];
            array_shift($parts);
        }

        // We will use the default schema unless the schema has been specified in the
        // query. If the schema has been specified in the query then we can use it
        // instead of using the Postgres configuration or SQL database default.
        $schema = $this->getDefaultSchema();

        if (count($parts) === 2) {
            $schema = $parts[0];
            array_shift($parts);
        }

        return [$database, $schema, $parts[0]];
    }

}
