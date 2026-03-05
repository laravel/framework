<?php

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinLateralClause;
use Illuminate\Support\Str;
use RuntimeException;

class MariaDbGrammar extends MySqlGrammar
{
    /**
     * Compile a "lateral join" clause.
     *
     * @param  \Illuminate\Database\Query\JoinLateralClause  $join
     * @param  string  $expression
     * @return string
     *
     * @throws \RuntimeException
     */
    public function compileJoinLateral(JoinLateralClause $join, string $expression): string
    {
        throw new RuntimeException('This database engine does not support lateral joins.');
    }

    /**
     * Compile an insert ignore statement with returning into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @param  array  $uniqueBy
     * @param  array  $returning
     * @return string
     */
    public function compileInsertOrIgnoreReturning(Builder $query, array $values, array $uniqueBy, array $returning)
    {
        return Str::replaceFirst('insert', 'insert ignore', $this->compileInsert($query, $values))
            .' returning '.$this->columnize($returning);
    }

    /**
     * Compile a "JSON value cast" statement into SQL.
     *
     * @param  string  $value
     * @return string
     */
    public function compileJsonValueCast($value)
    {
        return "json_query({$value}, '$')";
    }

    /**
     * Compile a query to get the number of open connections for a database.
     *
     * @return string
     */
    public function compileThreadCount()
    {
        return 'select variable_value as `Value` from information_schema.global_status where variable_name = \'THREADS_CONNECTED\'';
    }

    /**
     * Determine whether to use a legacy group limit clause for MySQL < 8.0.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return bool
     */
    public function useLegacyGroupLimit(Builder $query)
    {
        return false;
    }

    /**
     * Wrap the given JSON selector.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapJsonSelector($value)
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($value);

        return 'json_value('.$field.$path.')';
    }
}
