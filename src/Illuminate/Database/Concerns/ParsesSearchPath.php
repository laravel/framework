<?php

namespace Illuminate\Database\Concerns;

trait ParsesSearchPath
{
    /**
     * Parse the Postgres "search_path" configuration value into an array.
     *
     * @param  string|array|null  $searchPath
     * @return array
     */
    protected function parseSearchPath($searchPath)
    {
        if (is_string($searchPath)) {
            preg_match_all('/[^\s,"\']+/', $searchPath, $matches);

            $searchPath = $matches[0];
        }

        $searchPath ??= [];

        array_walk($searchPath, static function (&$schema) {
            $schema = trim($schema, '\'"');
        });

        return $searchPath;
    }
}
