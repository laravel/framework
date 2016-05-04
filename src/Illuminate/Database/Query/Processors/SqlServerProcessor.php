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
        $connection = $query->getConnection();
        $connection->insert($sql, $values);
        if ($connection->getConfig('odbc') === true) {
            $result = $connection->select("SELECT CAST(COALESCE(SCOPE_IDENTITY(), @@IDENTITY) AS int) AS insertid");
            if (!$result) {
                throw new Exception('Error retrieving lastInsertId');
            }
            $id = $result[0]->insertid;
        }
        else {
            $id = $connection->getPdo()->lastInsertId();
        }

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
