<?php

namespace Illuminate\Database\Concerns;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

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
        $sql = $this->combineSqlAndBindings($this);

        if ($callback) {
            $callback($sql);
        } else {
            echo $sql;
        }

        return $this;
    }

    /**
     * @param  EloquentBuilder|QueryBuilder|string  $query
     * @return string
     */
    private function combineSqlAndBindings($query): string
    {
        if (\is_string($query)) {
            return $query;
        }

        $bindings = $query->getConnection()->prepareBindings($query->getBindings());

        $sql = preg_replace_callback('/(?<!\?)\?(?!\?)/', function () use (&$bindings, $query) {

            $value = array_shift($bindings);

            switch($value){
                case null:
                    $value = 'null';
                    break;
                case is_bool($value):
                    $value = $value ? 'true' : 'false';
                    break;
                case is_numeric($value):
                    break;
                default:
                    $value = with($query->getConnection(), fn ($connection) => $connection->getPdo()->quote((string) $value));
                    break;
            }
            return $value;

        }, $query->toSql());

        return $sql;
    }
}
