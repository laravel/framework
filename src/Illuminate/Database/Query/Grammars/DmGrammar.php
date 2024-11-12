<?php

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;
use RuntimeException;

class DmGrammar extends Grammar
{
    /**
     * @var string
     */
    protected $schema_prefix = '';

    /**
     * Compile a "where like" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereLike(Builder $query, $where)
    {
        $value = $this->parameter($where['value']);

        $where['operator'] = $where['not'] ? 'not ' : '';

        if ($where['caseSensitive']) {
            $where['operator'] .= 'regexp_like';
            $operator = str_replace('?', '??', $where['operator']);

            return $operator.'('.$this->wrap($where['column']).', '.$value.', \'i\')';
        } else {
            $where['operator'] .= 'like';
            $operator = str_replace('?', '??', $where['operator']);

            return $this->wrap($where['column']).' '.$operator.' '.$value;
        }
    }

    /**
     * Compile an exists statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileExists(Builder $query)
    {
        $q = clone $query;
        $q->columns = [];
        $q->selectRaw('1 as "exists"')->whereRaw('rownum = 1');

        return $this->compileSelect($q);
    }

    /**
     * Wrap a value in keyword identifiers.
     *
     * Override due to laravel's stringify integers.
     *
     * @param  \Illuminate\Database\Query\Expression|string  $value
     * @param  bool  $prefixAlias
     * @return string
     */
    public function wrap($value, $prefixAlias = false)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        return parent::wrap($value, $prefixAlias);
    }

    /**
     * Return the schema prefix.
     *
     * @return string
     */
    public function getSchemaPrefix()
    {
        return ! empty($this->schema_prefix) ? $this->wrapValue($this->schema_prefix).'.' : '';
    }

    /**
     * Set the schema prefix.
     *
     * @param  string  $prefix
     */
    public function setSchemaPrefix($prefix)
    {
        $this->schema_prefix = $prefix;
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }

        return '"'.str_replace('"', '""', $value).'"';
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
            return $value ? 'for update' : 'for share';
        }

        return $value;
    }

    /**
     * Compile a "where date" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereDate(Builder $query, $where)
    {
        $value = $this->parameter($where['value']);

        return "trunc({$this->wrap($where['column'])}) {$where['operator']} $value";
    }

    /**
     * Compile a "where time" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereTime(Builder $query, $where)
    {
        $value = $this->parameter($where['value']);

        return $this->wrap($where['column']).'::time '.$where['operator'].' '.$value;
    }

    /**
     * Compile a date based where clause.
     *
     * @param  string  $type
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function dateBasedWhere($type, Builder $query, $where)
    {
        $value = $this->parameter($where['value']);

        return "extract($type from {$this->wrap($where['column'])}) {$where['operator']} $value";
    }

    /**
     * Compile a "where not in raw" clause.
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotInRaw(Builder $query, $where)
    {
        if (! empty($where['values'])) {
            if (is_array($where['values']) && count($where['values']) > 1000) {
                return $this->resolveClause($where['column'], $where['values'], 'not in');
            } else {
                return $this->wrap($where['column']).' not in ('.implode(', ', $where['values']).')';
            }
        }

        return '1 = 1';
    }

    /**
     * Compile a "where in raw" clause.
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereInRaw(Builder $query, $where)
    {
        if (! empty($where['values'])) {
            if (is_array($where['values']) && count($where['values']) > 1000) {
                return $this->resolveClause($where['column'], $where['values'], 'in');
            } else {
                return $this->wrap($where['column']).' in ('.implode(', ', $where['values']).')';
            }
        }

        return '0 = 1';
    }

    private function resolveClause($column, $values, $type)
    {
        $chunks = array_chunk($values, 1000);
        $whereClause = '';
        $i = 0;
        $type = $this->wrap($column).' '.$type.' ';
        foreach ($chunks as $ch) {
            // Add or only at the second loop
            if ($i === 1) {
                $type = ' or '.$type.' ';
            }
            $whereClause .= $type.'('.implode(', ', $ch).')';
            $i++;
        }

        return '('.$whereClause.')';
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
     * Compile the random statement into SQL.
     *
     * @param  string  $seed
     * @return string
     */
    public function compileRandom($seed)
    {
        return 'DBMS_RANDOM.RANDOM';
    }

    /**
     * Compile the index hints for the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  \Illuminate\Database\Query\IndexHint  $indexHint
     * @return string
     */
    protected function compileIndexHint(Builder $query, $indexHint)
    {
        return $indexHint->type === 'force'
                    ? "index {$indexHint->index}"
                    : '';
    }

    /**
     * Compile an "upsert" statement into SQL.
     *
     * @return string
     */
    public function compileUpsert(Builder $query, array $values, array $uniqueBy, array $update)
    {
        $columns = $this->columnize(array_keys(reset($values)));
        $parameters = $this->compileUnionSelectFromDual($values);

        $source = $this->wrapTable('laravel_source');

        $sql = 'merge into '.$this->wrapTable($query->from).' ';
        $sql .= 'using ('.$parameters.') '.$source;

        $on = collect($uniqueBy)->map(function ($column) use ($query) {
            return $this->wrap('laravel_source.'.$column).' = '.$this->wrap($query->from.'.'.$column);
        })->implode(' and ');

        $sql .= ' on ('.$on.') ';

        if ($update) {
            $update = collect($update)
                ->reject(function ($value, $key) use ($uniqueBy) {
                    return in_array($value, $uniqueBy);
                })
                ->map(function ($value, $key) {
                    return is_numeric($key)
                        ? $this->wrap($value).' = '.$this->wrap('laravel_source.'.$value)
                        : $this->wrap($key).' = '.$this->parameter($value);
                })
                ->implode(', ');

            $sql .= 'when matched then update set '.$update.' ';
        }

        $columnValues = collect(explode(', ', $columns))->map(function ($column) use ($source) {
            return $source.'.'.$column;
        })->implode(', ');

        $sql .= 'when not matched then insert ('.$columns.') values ('.$columnValues.')';

        return $sql;
    }

    /**
     * @param  array  $values
     * @return string
     */
    protected function compileUnionSelectFromDual(array $values): string
    {
        return collect($values)->map(function ($record) {
            $values = collect($record)->map(function ($value, $key) {
                return '? as '.$this->wrap($key);
            })->implode(', ');

            return 'select '.$values.' from dual';
        })->implode(' union all ');
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
        $keys = array_keys(reset($values));
        $columns = $this->columnize($keys);

        $parameters = $this->compileUnionSelectFromDual($values);

        $source = $this->wrapTable('laravel_source');

        $sql = 'merge into '.$this->wrapTable($query->from).' ';
        $sql .= 'using ('.$parameters.') '.$source;

        $uniqueBy = $keys;
        if (strtolower($query->from) == 'cache') {
            $uniqueBy = ['key'];
        }

        $on = collect($uniqueBy)->map(function ($column) use ($query) {
            return $this->wrap('laravel_source.'.$column).' = '.$this->wrap($query->from.'.'.$column);
        })->implode(' and ');

        $sql .= ' on ('.$on.') ';

        $columnValues = collect(explode(', ', $columns))->map(function ($column) use ($source) {
            return $source.'.'.$column;
        })->implode(', ');

        $sql .= 'when not matched then insert ('.$columns.') values ('.$columnValues.')';

        return $sql;
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
     * Compile a query to get the number of open connections for a database.
     *
     * @return string
     */
    public function compileThreadCount()
    {
        return 'select count(*) from V$SESSIONS';
    }

    /**
     * @param  string  $value
     * @return string
     */
    protected function wrapJsonSelector($value)
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($value);

        return 'json_value('.$field.$path.')';
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
     * Compile a "JSON overlaps" statement into SQL.
     *
     * @param  string  $column
     * @param  string  $value
     * @return string
     */
    protected function compileJsonOverlaps($column, $value)
    {
        $parts = explode('->', $column, 2);
        $field = $this->wrap($parts[0]);
        $sql = 'json_overlaps('.$field.', ?)';

        return $sql;
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
     * Compile an aggregated select clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query, $aggregate)
    {
        $column = $this->columnize($aggregate['columns']);

        // If the query has a "distinct" constraint and we're not asking for all columns
        // we need to prepend "distinct" onto the column name so that the query takes
        // it into account when it performs the aggregating operations on the data.
        if (is_array($query->distinct)) {
            $column = 'distinct '.$this->columnize($query->distinct);
        } elseif ($query->distinct && $column !== '*') {
            $column = 'distinct '.$column;
        }

        return 'select '.$aggregate['function'].'('.$column.') as "aggregate"';
    }

    /**
     * Compile a group limit clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileGroupLimit(Builder $query)
    {
        throw new RuntimeException('This database engine does not support groupLimit.');
    }
}
