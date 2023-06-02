<?php

namespace Illuminate\Database\Query\Processors;

class SQLiteProcessor extends Processor
{
    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        return array_map(function ($result) {
            return ((object) $result)->name;
        }, $results);
    }

    /**
     * Process the results of a constraint listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processConstraintisting($results)
    {
        return array_map(function ($result) {
            return ((object) $result)->constraint_name;
        }, $results);
    }
}
