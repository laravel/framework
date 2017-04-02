<?php

namespace Illuminate\Database\Query\Processors;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

class SqlServerProcessor extends Processor
{
    /**
     * Process a "last insert ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sequence
     * @return int
     */
    public function processLastInsertId(Builder $query, $sequence = null)
    {
        $connection = $query->getConnection();

        if ($connection->getConfig('odbc') === true) {
            $id = $this->processInsertGetIdForOdbc($connection);
        } else {
            $id = $connection->getPdo()->lastInsertId();
        }

        return is_numeric($id) ? (int) $id : $id;
    }

    /**
     * Process a "last insert ID" query for ODBC.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return int
     * @throws \Exception
     */
    protected function processInsertGetIdForOdbc(Connection $connection)
    {
        $result = $connection->selectFromWriteConnection('SELECT CAST(COALESCE(SCOPE_IDENTITY(), @@IDENTITY) AS int) AS insertid');

        if (! $result) {
            throw new Exception('Unable to retrieve lastInsertID for ODBC.');
        }

        $row = $result[0];

        return is_object($row) ? $row->insertid : $row['insertid'];
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
