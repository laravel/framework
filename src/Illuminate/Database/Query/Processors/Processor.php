<?php

namespace Illuminate\Database\Query\Processors;

use Illuminate\Database\Query\Builder;

class Processor
{
    /**
     * Process the results of a "select" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $results
     * @return array
     */
    public function processSelect(Builder $query, $results)
    {
        return $results;
    }

    /**
     * Process an  "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $query->getConnection()->insert($sql, $values);

        $id = $query->getConnection()->getPdo()->lastInsertId($sequence);

        return is_numeric($id) ? (int) $id : $id;
    }

    /**
     * Process the results of a tables query.
     *
     * @param  array  $results
     * @return array
     */
    public function processTables($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'name' => $result->name,
                'schema' => $result->schema ?? null, // PostgreSQL and SQL Server
                'size' => isset($result->size) ? (int) $result->size : null,
                'comment' => $result->comment ?? null, // MySQL and PostgreSQL
                'collation' => $result->collation ?? null, // MySQL only
                'engine' => $result->engine ?? null, // MySQL only
            ];
        }, $results);
    }

    /**
     * Process the results of a views query.
     *
     * @param  array  $results
     * @return array
     */
    public function processViews($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'name' => $result->name,
                'schema' => $result->schema ?? null, // PostgreSQL and SQL Server
                'definition' => $result->definition,
            ];
        }, $results);
    }

    /**
     * Process the results of a types query.
     *
     * @param  array  $results
     * @return array
     */
    public function processTypes($results)
    {
        return $results;
    }

    /**
     * Process the results of a columns query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumns($results)
    {
        return $results;
    }

    /**
     * Process the results of an indexes query.
     *
     * @param  array  $results
     * @return array
     */
    public function processIndexes($results)
    {
        return $results;
    }

    /**
     * Process the results of a foreign keys query.
     *
     * @param  array  $results
     * @return array
     */
    public function processForeignKeys($results)
    {
        return $results;
    }
}
