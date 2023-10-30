<?php

namespace Illuminate\Database\Query\Processors;

class SQLiteProcessor extends Processor
{
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
            return ((object) $result)->name;
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
        $hasPrimaryKey = array_sum(array_column($results, 'primary')) === 1;

        return array_map(function ($result) use ($hasPrimaryKey) {
            $result = (object) $result;

            $type = strtolower($result->type);

            return [
                'name' => $result->name,
                'type_name' => strtok($type, '('),
                'type' => $type,
                'collation' => null,
                'nullable' => (bool) $result->nullable,
                'default' => $result->default,
                'auto_increment' => $hasPrimaryKey && $result->primary && $type === 'integer',
                'comment' => null,
            ];
        }, $results);
    }
}
