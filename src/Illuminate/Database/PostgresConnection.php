<?php

namespace Illuminate\Database;

use Illuminate\Database\Schema\PostgresBuilder;
use Doctrine\DBAL\Driver\PDOPgSql\Driver as DoctrineDriver;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Illuminate\Database\Query\Grammars\PostgresGrammar as QueryGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar as SchemaGrammar;

class PostgresConnection extends Connection
{
    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\PostgresBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new PostgresBuilder($this);
    }

    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            // We need to transform all instances of DateTimeInterface into the actual
            // date string. Each query grammar maintains its own date string format
            // so we'll just ask the grammar for the format to get from the date.
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } 
            elseif ($value === false) {
                $bindings[$key] = 0;
            }
            elseif (empty($value) && ! is_numeric($value)) {
                // Some types, such as date, do not involve the use of an empty string as a null.
                // Therefore use more enhanced version of a null value/
                $bindings[$key] = null;
            }
        }

        return $bindings;
    }



    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\PostgresGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\PostgresGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\PostgresProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new PostgresProcessor;
    }

    /**
     * Get the Doctrine DBAL driver.
     *
     * @return \Doctrine\DBAL\Driver\PDOPgSql\Driver
     */
    protected function getDoctrineDriver()
    {
        return new DoctrineDriver;
    }
}
