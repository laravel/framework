<?php

namespace Illuminate\Database\Query\Processors;

use Illuminate\Database\Query\Builder;

class Processor
{
    /**
     * Process the results of a "select" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array   $results
     * @param  string  $indexBy
     * @return array
     */
    public function processSelect(Builder $query, $results, $indexBy = null)
    {
        if ($indexBy !== null && count($results)) {
            $results = $this->indexResultsByColumn($results, $indexBy);
        }

        return $results;
    }

    /**
     * Index results by column.
     *
     * @param array $results
     * @param string $column
     * @return array
     * */
    protected function indexResultsByColumn(array $results, $column)
    {
        if ($column === null) {
            return $results;
        }

        return array_combine(array_map(function ($row) use ($column) {
            if (is_array($row)) {
                return $row[$column];
            } elseif (is_object($row)) {
                return $row->{$column};
            }
            throw new \InvalidArgumentException('Array or object must be passed to data_get.');
        }, $results), $results);
    }

    /**
     * Process an  "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array   $values
     * @param  string  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $query->getConnection()->insert($sql, $values);

        $id = $query->getConnection()->getPdo()->lastInsertId($sequence);

        return is_numeric($id) ? (int) $id : $id;
    }

    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        return $results;
    }
}
