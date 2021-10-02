<?php

namespace Illuminate\Database\Concerns;

trait ShowsQueries
{
    /**
     * Shows the query.
     *
     * @param  \Closure|null  $callback
     * @return mixed
     */
    public function show(\Closure $callback = null)
    {
        $sql = $this->toSql();

        $bindings = $this->getBindings();

        $sql = $this->combineSqlAndBindings($sql, $bindings);

        if ($callback) {
            $callback($sql);
        } else {
            echo $sql;
        }

        return $this;
    }

    /**
     * @param $sql
     * @param $bindings
     * @return string
     */
    private function combineSqlAndBindings($sql, $bindings)
    {
        return vsprintf(str_replace('?', '%s', $sql), collect($bindings)->map(function ($binding) {
            return is_numeric($binding) ? $binding : "'{$binding}'";
        })->toArray());
    }
}
