<?php

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

class MySqlGrammar extends Grammar
{
    /**
     * The grammar specific operators.
     *
     * @var string[]
     */
    protected $operators = ['sounds like'];

    /**
     * Add a "where null" clause to the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNull(Builder $query, $where)
    {
        if ($this->isJsonSelector($where['column'])) {
            [$field, $path] = $this->wrapJsonFieldAndPath($where['column']);

            return '(json_extract('.$field.$path.') is null OR json_type(json_extract('.$field.$path.')) = \'NULL\')';
        }

        return parent::whereNull($query, $where);
    }

    /**
     * Add a "where not null" clause to the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotNull(Builder $query, $where)
    {
        if ($this->isJsonSelector($where['column'])) {
            [$field, $path] = $this->wrapJsonFieldAndPath($where['column']);

            return '(json_extract('.$field.$path.') is not null AND json_type(json_extract('.$field.$path.')) != \'NULL\')';
        }

        return parent::whereNotNull($query, $where);
    }

    /**
     * Compile a "where fulltext" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    public function whereFullText(Builder $query, $where)
    {
        $columns = $this->columnize($where['columns']);

        $value = $this->parameter($where['value']);

        $mode = ($where['options']['mode'] ?? []) === 'boolean'
            ? ' in boolean mode'
            : ' in natural language mode';

        $expanded = ($where['options']['expanded'] ?? []) && ($where['options']['mode'] ?? []) !== 'boolean'
            ? ' with query expansion'
            : '';

        return "match ({$columns}) against (".$value."{$mode}{$expanded})";
    }

    /**
     * Compile an insert ignore statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileInsertOrIgnore(Builder $query, array $values)
    {
        return Str::replaceFirst('insert', 'insert ignore', $this->compileInsert($query, $values));
    }

    /**
     * Compile a "JSON contains" statement into SQL.
     *
     * @param  string  $column
     * @param  string  $value
     * @return string
     */
    protected function compileJsonContains($column, $value)
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($column);

        return 'json_contains('.$field.', '.$value.$path.')';
    }

    /**
     * Compile a "JSON contains key" statement into SQL.
     *
     * @param  string  $column
     * @return string
     */
    protected function compileJsonContainsKey($column)
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($column);

        return 'ifnull(json_contains_path('.$field.', \'one\''.$path.'), 0)';
    }

    /**
     * Compile a "JSON length" statement into SQL.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     * @return string
     */
    protected function compileJsonLength($column, $operator, $value)
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($column);

        return 'json_length('.$field.$path.') '.$operator.' '.$value;
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param  string  $seed
     * @return string
     */
    public function compileRandom($seed)
    {
        return 'RAND('.$seed.')';
    }

    /**
     * Compile the lock into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  bool|string  $value
     * @return string
     */
    protected function compileLock(Builder $query, $value)
    {
        if (! is_string($value)) {
            return $value ? 'for update' : 'lock in share mode';
        }

        return $value;
    }

    /**
     * Compile an insert statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileInsert(Builder $query, array $values)
    {
        if (empty($values)) {
            $values = [[]];
        }

        return parent::compileInsert($query, $values);
    }

    /**
     * Compile the columns for an update statement.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    protected function compileUpdateColumns(Builder $query, array $values)
    {
        return collect($values)->map(function ($value, $key) {
            if ($this->isJsonSelector($key)) {
                return $this->compileJsonUpdateColumn($key, $value);
            }

            return $this->wrap($key).' = '.$this->parameter($value);
        })->implode(', ');
    }

    /**
     * Compile an "upsert" statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @param  array  $uniqueBy
     * @param  array  $update
     * @return string
     */
    public function compileUpsert(Builder $query, array $values, array $uniqueBy, array $update)
    {
        $sql = $this->compileInsert($query, $values).' on duplicate key update ';

        $columns = collect($update)->map(function ($value, $key) {
            return is_numeric($key)
                ? $this->wrap($value).' = values('.$this->wrap($value).')'
                : $this->wrap($key).' = '.$this->parameter($value);
        })->implode(', ');

        return $sql.$columns;
    }

    /**
     * Prepare a JSON column being updated using the JSON_SET function.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return string
     */
    protected function compileJsonUpdateColumn($key, $value)
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $value = 'cast(? as json)';
        } else {
            $value = $this->parameter($value);
        }

        [$field, $path] = $this->wrapJsonFieldAndPath($key);

        return "{$field} = json_set({$field}{$path}, {$value})";
    }

    /**
     * Compile an update statement without joins into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @param  string  $columns
     * @param  string  $where
     * @return string
     */
    protected function compileUpdateWithoutJoins(Builder $query, $table, $columns, $where)
    {
        $sql = parent::compileUpdateWithoutJoins($query, $table, $columns, $where);

        if (! empty($query->orders)) {
            $sql .= ' '.$this->compileOrders($query, $query->orders);
        }

        if (isset($query->limit)) {
            $sql .= ' '.$this->compileLimit($query, $query->limit);
        }

        return $sql;
    }

    /**
     * Prepare the bindings for an update statement.
     *
     * Booleans, integers, and doubles are inserted into JSON updates as raw values.
     *
     * @param  array  $bindings
     * @param  array  $values
     * @return array
     */
    public function prepareBindingsForUpdate(array $bindings, array $values)
    {
        $values = collect($values)->reject(function ($value, $column) {
            return $this->isJsonSelector($column) && is_bool($value);
        })->map(function ($value) {
            return is_array($value) ? json_encode($value) : $value;
        })->all();

        return parent::prepareBindingsForUpdate($bindings, $values);
    }

    /**
     * Compile a delete query that does not use joins.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @param  string  $where
     * @return string
     */
    protected function compileDeleteWithoutJoins(Builder $query, $table, $where)
    {
        $sql = parent::compileDeleteWithoutJoins($query, $table, $where);

        // When using MySQL, delete statements may contain order by statements and limits
        // so we will compile both of those here. Once we have finished compiling this
        // we will return the completed SQL statement so it will be executed for us.
        if (! empty($query->orders)) {
            $sql .= ' '.$this->compileOrders($query, $query->orders);
        }

        if (isset($query->limit)) {
            $sql .= ' '.$this->compileLimit($query, $query->limit);
        }

        return $sql;
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        return $value === '*' ? $value : '`'.str_replace('`', '``', $value).'`';
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

        return 'json_unquote(json_extract('.$field.$path.'))';
    }

    /**
     * Wrap the given JSON selector for boolean values.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapJsonBooleanSelector($value)
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($value);

        return 'json_extract('.$field.$path.')';
    }
}
