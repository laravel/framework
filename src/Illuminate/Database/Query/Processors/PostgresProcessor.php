<?php

namespace Illuminate\Database\Query\Processors;

use Illuminate\Database\Query\Builder;

class PostgresProcessor extends Processor
{
    /**
     * Process an "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $connection = $query->getConnection();

        $connection->recordsHaveBeenModified();

        $result = $connection->selectFromWriteConnection($sql, $values)[0];

        $sequence = $sequence ?: 'id';

        $id = is_object($result) ? $result->{$sequence} : $result[$sequence];

        return is_numeric($id) ? (int) $id : $id;
    }

    /**
     * Process the results of a column listing query.
     *
     * @deprecated Will be removed in a future Laravel version.
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

    /**
     * Process the results of a types query.
     *
     * @param  array  $results
     * @return array
     */
    public function processTypes($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'name' => $result->name,
                'schema' => $result->schema,
                'implicit' => (bool) $result->implicit,
                'type' => match (strtolower($result->type)) {
                    'b' => 'base',
                    'c' => 'composite',
                    'd' => 'domain',
                    'e' => 'enum',
                    'p' => 'pseudo',
                    'r' => 'range',
                    'm' => 'multirange',
                    default => null,
                },
                'category' => match (strtolower($result->category)) {
                    'a' => 'array',
                    'b' => 'boolean',
                    'c' => 'composite',
                    'd' => 'date_time',
                    'e' => 'enum',
                    'g' => 'geometric',
                    'i' => 'network_address',
                    'n' => 'numeric',
                    'p' => 'pseudo',
                    'r' => 'range',
                    's' => 'string',
                    't' => 'timespan',
                    'u' => 'user_defined',
                    'v' => 'bit_string',
                    'x' => 'unknown',
                    'z' => 'internal_use',
                    default => null,
                },
            ];
        }, $results);
    }

    /**
     * Process the results of a columns query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumns($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            $autoincrement = $result->default !== null && str_starts_with($result->default, 'nextval(');

            return [
                'name' => str_starts_with($result->name, '"') ? str_replace('"', '', $result->name) : $result->name,
                'type_name' => $result->type_name,
                'type' => $result->type,
                'collation' => $result->collation,
                'nullable' => (bool) $result->nullable,
                'default' => $autoincrement ? null : $result->default,
                'auto_increment' => $autoincrement,
                'comment' => $result->comment,
            ];
        }, $results);
    }

    /**
     * Process the results of an indexes query.
     *
     * @param  array  $results
     * @return array
     */
    public function processIndexes($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'name' => strtolower($result->name),
                'columns' => explode(',', $result->columns),
                'type' => strtolower($result->type),
                'unique' => (bool) $result->unique,
                'primary' => (bool) $result->primary,
            ];
        }, $results);
    }
}
