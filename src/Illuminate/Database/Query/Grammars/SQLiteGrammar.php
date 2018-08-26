<?php

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Query\Builder;

class SQLiteGrammar extends Grammar
{
    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'lock',
    ];

    /**
     * All of the available clause operators.
     *
     * @var array
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'ilike',
        '&', '|', '<<', '>>',
    ];

    /**
     * Compile a select query into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        $sql = parent::compileSelect($query);

        if ($query->unions) {
            $sql = 'select * from ('.$sql.') '.$this->compileUnions($query);
        }

        return $sql;
    }

    /**
     * Compile a single union statement.
     *
     * @param  array  $union
     * @return string
     */
    protected function compileUnion(array $union)
    {
        $conjunction = $union['all'] ? ' union all ' : ' union ';

        return $conjunction.'select * from ('.$union['query']->toSql().')';
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
        return $this->dateBasedWhere('%Y-%m-%d', $query, $where);
    }

    /**
     * Compile a "where day" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereDay(Builder $query, $where)
    {
        return $this->dateBasedWhere('%d', $query, $where);
    }

    /**
     * Compile a "where month" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereMonth(Builder $query, $where)
    {
        return $this->dateBasedWhere('%m', $query, $where);
    }

    /**
     * Compile a "where year" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereYear(Builder $query, $where)
    {
        return $this->dateBasedWhere('%Y', $query, $where);
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
        return $this->dateBasedWhere('%H:%M:%S', $query, $where);
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

        return "strftime('{$type}', {$this->wrap($where['column'])}) {$where['operator']} cast({$value} as text)";
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
        // Essentially we will force every insert to be treated as a batch insert which
        // simply makes creating the SQL easier for us since we can utilize the same
        // basic routine regardless of an amount of records given to us to insert.
        $table = $this->wrapTable($query->from);

        if (! is_array(reset($values))) {
            $values = [$values];
        }

        // If there is only one record being inserted, we will just use the usual query
        // grammar insert builder because no special syntax is needed for the single
        // row inserts in SQLite. However, if there are multiples, we'll continue.
        if (count($values) === 1) {
            return empty(reset($values))
                    ? "insert into $table default values"
                    : parent::compileInsert($query, reset($values));
        }

        $names = $this->columnize(array_keys(reset($values)));

        $columns = [];

        // SQLite requires us to build the multi-row insert as a listing of select with
        // unions joining them together. So we'll build out this list of columns and
        // then join them all together with select unions to complete the queries.
        foreach (array_keys(reset($values)) as $column) {
            $columns[] = '? as '.$this->wrap($column);
        }

        $columns = array_fill(0, count($values), implode(', ', $columns));

        return "insert into $table ($names) select ".implode(' union all select ', $columns);
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileUpdate(Builder $query, $values)
    {
        $table = $this->wrapTable($query->from);

        $columns = collect($values)->map(function ($value, $key) use ($query) {
            return $this->wrap(Str::after($key, $query->from.'.')).' = '.$this->parameter($value);
        })->implode(', ');

        if (isset($query->joins) || isset($query->limit)) {
            $selectSql = parent::compileSelect($query->select("{$query->from}.rowid"));

            return "update {$table} set $columns where {$this->wrap('rowid')} in ({$selectSql})";
        }

        return trim("update {$table} set {$columns} {$this->compileWheres($query)}");
    }

    /**
     * Prepare the bindings for an update statement.
     *
     * @param  array  $bindings
     * @param  array  $values
     * @return array
     */
    public function prepareBindingsForUpdate(array $bindings, array $values)
    {
        $cleanBindings = Arr::except($bindings, ['select', 'join']);

        return array_values(
            array_merge($values, $bindings['join'], Arr::flatten($cleanBindings))
        );
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileDelete(Builder $query)
    {
        if (isset($query->joins) || isset($query->limit)) {
            $selectSql = parent::compileSelect($query->select("{$query->from}.rowid"));

            return "delete from {$this->wrapTable($query->from)} where {$this->wrap('rowid')} in ({$selectSql})";
        }

        $wheres = is_array($query->wheres) ? $this->compileWheres($query) : '';

        return trim("delete from {$this->wrapTable($query->from)} $wheres");
    }

    /**
     * Prepare the bindings for a delete statement.
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindingsForDelete(array $bindings)
    {
        $cleanBindings = Arr::except($bindings, ['select', 'join']);

        return array_values(
            array_merge($bindings['join'], Arr::flatten($cleanBindings))
        );
    }

    /**
     * Compile a truncate table statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array
     */
    public function compileTruncate(Builder $query)
    {
        return [
            'delete from sqlite_sequence where name = ?' => [$query->from],
            'delete from '.$this->wrapTable($query->from) => [],
        ];
    }

    /**
     * Wrap the given JSON selector.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapJsonSelector($value)
    {
        $parts = explode('->', $value, 2);

        $field = $this->wrap($parts[0]);

        $path = count($parts) > 1 ? ', '.$this->wrapJsonPath($parts[1]) : '';

        $selector = 'json_extract('.$field.$path.')';

        return $selector;
    }
}
