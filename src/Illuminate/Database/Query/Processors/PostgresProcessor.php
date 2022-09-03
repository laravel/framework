<?php

namespace Illuminate\Database\Query\Processors;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;

class PostgresProcessor extends Processor
{
    /**
     * Process an "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|array|null  $sequence
     * @return mixed
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $connection = $query->getConnection();

        $connection->recordsHaveBeenModified();

        $result = $connection->selectFromWriteConnection($sql, Arr::flatten($values));

        if ($sequence === null) {
            $sequence = ['id'];
            $flat = true;
        } elseif (! is_array($sequence)) {
            $sequence = [$sequence];
            $flat = true;
        } else {
            $flat = count($sequence) == 1;
        }

        $ids = [];

        foreach ($result as $row) {
            $currentIdSelection = [];

            foreach ($sequence as $field) {
                $fieldValue = is_object($row) ? $row->{$field} : $row[$field];
                $fieldValue = is_numeric($fieldValue) ? (int) $fieldValue : $fieldValue;

                if ($flat) {
                    $currentIdSelection = $fieldValue;
                } else {
                    $currentIdSelection[$field] = $fieldValue;
                }
            }

            $ids[] = $currentIdSelection;
        }

        return match (count($ids)) {
            0 => null,
            1 => $ids[0],
            default => $ids,
        };
    }

    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        return array_map(function ($result) {
            return ((object) $result)->column_name;
        }, $results);
    }
}
