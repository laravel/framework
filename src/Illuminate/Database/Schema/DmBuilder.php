<?php

namespace Illuminate\Database\Schema;

use Closure;

class DmBuilder extends Builder
{
    public static ?int $defaultTimePrecision = null;

    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $this->connection->statement(
            $this->grammar->compileDropAllTables()
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
            if (strtolower($view) === strtolower($value['NAME'])
                && strtolower($schema) === strtolower($value['SCHNAME'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the id for a given table.
     *
     * @param  string  $schema
     * @param  string  $table
     * @return array
     */
    protected function getTableId($schema, $table)
    {
        return $this->connection->selectFromWriteConnection(
                $this->grammar->compileTableId($schema, $table)
            );
    }

    /**
     * Get the id for a given schema.
     *
     * @param  string  $schema
     * @return array
     */
    protected function getSchemaId($schema)
    {
        return $this->connection->selectFromWriteConnection(
                $this->grammar->compileSchemaId($schema)
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
        [$schema, $table] = $this->parseSchemaAndTable($table);
        $table = $this->connection->getTablePrefix().$table;
        $tableId = $this->getTableID($schema, $table);
        if (isset($tableId)) {
            // Get the identity columns
            $idenLists = $this->connection->selectFromWriteConnection(
                $this->grammar->compileIdentityColumns($tableId)
            );
            
            // Get the tables' columns
            $results = $this->connection->selectFromWriteConnection(
                $this->grammar->compileColumns($schema, $table, $tableId)
            );

            $i = 0;
            foreach($results as $column) {
                // Change the class name which starts with the "CLASS" to the real name
                // such as SYSGEO2.ST_Geometry(CLASS234881137), SYSGEO2.ST_Point(CLASS234881138)...
                if (str_starts_with($column->TYPE_NAME,'CLASS')) {
                    $classId = substr($column->TYPE_NAME,5);
                    $className = $this->connection->getPostProcessor()->processClassName(
                        $this->connection->selectFromWriteConnection(
                            $this->grammar->compileClassName($classId)
                        )    
                    );
                    $classNameLists[$i] =  $className[0]['class_name'];
                }
                else {
                    $classNameLists[$i] =  $column->TYPE_NAME;
                }
                $i++;
            }

            // Add 'AUTO_INCREMENT' and change the 'TYPE_NAME' to the result array
            $results = $this->connection->getPostProcessor()->processColumnsIncrementClassName(
                $results, $idenLists, $classNameLists
            );
        }
        else {
            $results = [];
        }

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

        if (isset($this->getSchemaId($schema)[0]) && isset($this->getTableID($schema, $table)[0])) {
            $schemaId = $this->getSchemaId($schema)[0]->ID;
            $tableId = $this->getTableID($schema, $table)[0]->ID;
            $results = $this->connection->selectFromWriteConnection($this->grammar->compileIndexes($schemaId, $tableId));

            foreach($results as $result) {
                $columns = $this->connection->selectFromWriteConnection(
                            $this->grammar->compileIndexColumns($result->INDEXID, $tableId)
                        );
                $columnList = [];
                foreach($columns as $column) {
                    array_push($columnList,$column->NAME);
                }
                $result->COLUMNS = $columnList;
            }
        }
        else {
            $results = [];
        }
        return $this->connection->getPostProcessor()->processIndexes($results);
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
        
        $results = $this->connection->selectFromWriteConnection(
            $this->grammar->compileForeignKeys($schema, $table)
        );

        foreach($results as $result) {
            $foreign = $this->connection->selectFromWriteConnection(
                        $this->grammar->compileForeignReference($result->CONSTRAINT_NAME)
                    );
            $result->FOREIGN_SCHEMA = $foreign[0]->OWNER;
            $result->FOREIGN_TABLE = $foreign[0]->TABLE_NAME;
            $result->FOREIGN_COLUMNS = $foreign[0]->COLUMNS;
        }

        return $this->connection->getPostProcessor()->processForeignKeys(
            $results
        );
    }

    /**
     * Get the tables for the database.
     *
     * @return array
     */
    public function getTables()
    {
        $schema = $this->connection->getSchema();
        $schemaId = $this->getSchemaId($schema)[0];
        if (isset($schemaId)) {
            $results = $this->connection->getPostProcessor()->processTables(
                $this->connection->selectFromWriteConnection(
                    $this->grammar->compileTables($schema, $schemaId)
                )
            );
        }
        else {
            $results = [];
        }

        return $results;
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
                $this->grammar->compileViews($this->connection->getSchema())
            )
        );
    }

    /**
     * Parse the database object reference and extract the schema and table.
     *
     * @param  string  $reference
     * @return array
     */
    protected function parseSchemaAndTable($reference)
    {
        $parts = explode('.', $reference);

        if (count($parts) > 2) {
            $database = $parts[0];

            throw new InvalidArgumentException("Using three-part reference is not supported, you may use `Schema::connection('$database')` instead.");
        }

        // We will use the default schema unless the schema has been specified in the
        // query. If the schema has been specified in the query then we can use it
        // instead of a default schema configured in the connection search path.
        $schema = $this->connection->getSchema();
        if (count($parts) === 2) {
            $schema = $parts[0];
            array_shift($parts);
        }

        return [$schema, $parts[0]];
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
