<?php

namespace Illuminate\Database\Query\Processors;

use Illuminate\Database\Query\Builder;

class SqlServerProcessor extends Processor
{
    /**
     * Process an "insert get ID" query.
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

        //$id = $query->getConnection()->getPdo()->lastInsertId(); this is not working on linux pdo driver and a sql server table with a trigger inserting a new record. It brings me the id of insert trigger made, not the id of the main table. Its a bug on PDO linux driver (on windows it works fine)
        $id = $query->selectRaw("SCOPE_IDENTITY() AS lastId")->first()->lastId; //this works fine for everyone.

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
        $mapping = function ($r) {
            $r = (object) $r;

            return $r->name;
        };

        return array_map($mapping, $results);
    }
}
