<?php

namespace Illuminate\Database\Concerns;

trait ShowsQueries
{
    /**
     * Shows the query
     *
     * @return $this
     */
    public function show($toScreen = false)
    {
        $sql = $this->toSql();

        $bindings = $this->getBindings();

        echo $this->combineSqlAndBindings($sql, $bindings);

        return $this;
    }

    private function combineSqlAndBindings($sql, $bindings)
    {
        return vsprintf(str_replace('?', '%s', $sql), collect($bindings)->map(function ($binding) {
            return is_numeric($binding) ? $binding : "'{$binding}'";
        })->toArray());
    }
}

