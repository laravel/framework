<?php

namespace Illuminate\Database\Query\Processors;

class MariaDbProcessor extends MySqlProcessor
{
    /**
     * Process the results of a check constraints query.
     *
     * @param  list<array<string, mixed>>  $results
     * @return list<array{name: string|null, columns: list<string>, definition: string}>
     */
    public function processCheckConstraints($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'name' => $result->name ?? null,
                'columns' => explode(',', $result->columns ?? ''),
                'definition' => '('.$result->definition.')',
            ];
        }, $results);
    }
}
