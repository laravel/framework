<?php

namespace Illuminate\Database\Query\Processors;

class MySqlProcessor extends Processor
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
            return ((object) $result)->column_name;
        }, $results);
    }

    /**
     * Process the results of a constraint listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processConstraintListing($results)
    {
        return array_map(function ($result) {
            return ((object) $result)->constraint_name;
        }, $results);
    }
}
