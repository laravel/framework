<?php

namespace Illuminate\Database\Query\Processors;

class MySqlProcessor extends Processor
{
    /**
     * Process the results of columns query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumns($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'name' => $result->name,
                'type_name' => $result->type_name,
                'type' => $result->type,
                'collation' => $result->collation,
                'nullable' => $result->nullable === 'YES',
                'default' => $result->default,
                'auto_increment' => $result->extra === 'auto_increment',
                'comment' => $result->comment,
            ];
        }, $results);
    }
}
