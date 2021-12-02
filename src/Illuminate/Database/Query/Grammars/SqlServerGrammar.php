<?php

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SqlServerGrammar extends Grammar
{
    /**
     * All of the available clause operators.
     *
     * @var string[]
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '!<', '!>', '<>', '!=',
        'like', 'not like', 'ilike',
        '&', '&=', '|', '|=', '^', '^=',
    ];

    /**
     * Compile a select query into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        if (! $query->offset) {
            return parent::compileSelect($query);
        }

        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }

        $components = $this->compileComponents($query);

        if (! empty($components['orders'])) {
            return parent::compileSelect($query)." offset {$query->offset} rows fetch next {$query->limit} rows only";
        }

        // If an offset is present on the query, we will need to wrap the query in
        // a big "ANSI" offset syntax block. This is very nasty compared to the
        // other database systems but is necessary for implementing features.
        return $this->compileAnsiOffset(
            $query, $components
        );
    }

    /**
     * Compile the "select *" portion of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $columns
     * @return string|null
     */
    protected function compileColumns(Builder $query, $columns)
    {
        if (! is_null($query->aggregate)) {
            return;
        }

        $select = $query->distinct ? 'select distinct ' : 'select ';

        // If there is a limit on the query, but not an offset, we will add the top
        // clause to the query, which serves as a "limit" type clause within the
        // SQL Server system similar to the limit keywords available in MySQL.
        if (is_numeric($query->limit) && $query->limit > 0 && $query->offset <= 0) {
            $select .= 'top '.((int) $query->limit).' ';
        }

        return $select.$this->columnize($columns);
    }

    /**
     * Compile the "from" portion of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @return string
     */
    protected function compileFrom(Builder $query, $table)
    {
        $from = parent::compileFrom($query, $table);

        if (is_string($query->lock)) {
            return $from.' '.$query->lock;
        }

        if (! is_null($query->lock)) {
            return $from.' with(rowlock,'.($query->lock ? 'updlock,' : '').'holdlock)';
        }

        return $from;
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

        return 'cast('.$this->wrap($where['column']).' as date) '.$where['operator'].' '.$value;
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

        return 'cast('.$this->wrap($where['column']).' as time) '.$where['operator'].' '.$value;
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

        return $value.' in (select [value] from openjson('.$field.$path.'))';
    }

    /**
     * Prepare the binding for a "JSON contains" statement.
     *
     * @param  mixed  $binding
     * @return string
     */
    public function prepareBindingForJsonContains($binding)
    {
        return is_bool($binding) ? json_encode($binding) : $binding;
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

        return '(select count(*) from openjson('.$field.$path.')) '.$operator.' '.$value;
    }

    /**
     * Create a full ANSI offset clause for the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $components
     * @return string
     */
    protected function compileAnsiOffset(Builder $query, $components)
    {
        // An ORDER BY clause is required to make this offset query work, so if one does
        // not exist we'll just create a dummy clause to trick the database and so it
        // does not complain about the queries for not having an "order by" clause.
        if (empty($components['orders'])) {
            $components['orders'] = 'order by (select 0)';
        }

        // We need to add the row number to the query so we can compare it to the offset
        // and limit values given for the statements. So we will add an expression to
        // the "select" that will give back the row numbers on each of the records.
        $components['columns'] .= $this->compileOver($components['orders']);

        unset($components['orders']);

        if ($this->queryOrderContainsSubquery($query)) {
            $query->bindings = $this->sortBindingsForSubqueryOrderBy($query);
        }

        // Next we need to calculate the constraints that should be placed on the query
        // to get the right offset and limit from our query but if there is no limit
        // set we will just handle the offset only since that is all that matters.
        $sql = $this->concatenate($components);

        return $this->compileTableExpression($sql, $query);
    }

    /**
     * Compile the over statement for a table expression.
     *
     * @param  string  $orderings
     * @return string
     */
    protected function compileOver($orderings)
    {
        return ", row_number() over ({$orderings}) as row_num";
    }

    /**
     * Determine if the query's order by clauses contain a subquery.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return bool
     */
    protected function queryOrderContainsSubquery($query)
    {
        if (! is_array($query->orders)) {
            return false;
        }

        return Arr::first($query->orders, function ($value) {
            return $this->isExpression($value['column'] ?? null);
        }, false) !== false;
    }

    /**
     * Move the order bindings to be after the "select" statement to account for an order by subquery.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array
     */
    protected function sortBindingsForSubqueryOrderBy($query)
    {
        return Arr::sort($query->bindings, function ($bindings, $key) {
            return array_search($key, ['select', 'order', 'from', 'join', 'where', 'groupBy', 'having', 'union', 'unionOrder']);
        });
    }

    /**
     * Compile a common table expression for a query.
     *
     * @param  string  $sql
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileTableExpression($sql, $query)
    {
        $constraint = $this->compileRowConstraint($query);

        return "select * from ({$sql}) as temp_table where row_num {$constraint} order by row_num";
    }

    /**
     * Compile the limit / offset row constraint for a query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileRowConstraint($query)
    {
        $start = (int) $query->offset + 1;

        if ($query->limit > 0) {
            $finish = (int) $query->offset + (int) $query->limit;

            return "between {$start} and {$finish}";
        }

        return ">= {$start}";
    }

    /**
     * Compile a delete statement without joins into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @param  string  $where
     * @return string
     */
    protected function compileDeleteWithoutJoins(Builder $query, $table, $where)
    {
        $sql = parent::compileDeleteWithoutJoins($query, $table, $where);

        return ! is_null($query->limit) && $query->limit > 0 && $query->offset <= 0
                        ? Str::replaceFirst('delete', 'delete top ('.$query->limit.')', $sql)
                        : $sql;
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param  string  $seed
     * @return string
     */
    public function compileRandom($seed)
    {
        return 'NEWID()';
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $limit
     * @return string
     */
    protected function compileLimit(Builder $query, $limit)
    {
        return '';
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $offset
     * @return string
     */
    protected function compileOffset(Builder $query, $offset)
    {
        return '';
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
        return '';
    }

    /**
     * Wrap a union subquery in parentheses.
     *
     * @param  string  $sql
     * @return string
     */
    protected function wrapUnion($sql)
    {
        return 'select * from ('.$sql.') as '.$this->wrapTable('temp_table');
    }

    /**
     * Compile an exists statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileExists(Builder $query)
    {
        $existsQuery = clone $query;

        $existsQuery->columns = [];

        return $this->compileSelect($existsQuery->selectRaw('1 [exists]')->limit(1));
    }

    /**
     * Compile an update statement with joins into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @param  string  $columns
     * @param  string  $where
     * @return string
     */
    protected function compileUpdateWithJoins(Builder $query, $table, $columns, $where)
    {
        $alias = last(explode(' as ', $table));

        $joins = $this->compileJoins($query, $query->joins);

        return "update {$alias} set {$columns} from {$table} {$joins} {$where}";
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
        $columns = $this->columnize(array_keys(reset($values)));

        $sql = 'merge '.$this->wrapTable($query->from).' ';

        $parameters = collect($values)->map(function ($record) {
            return '('.$this->parameterize($record).')';
        })->implode(', ');

        $sql .= 'using (values '.$parameters.') '.$this->wrapTable('laravel_source').' ('.$columns.') ';

        $on = collect($uniqueBy)->map(function ($column) use ($query) {
            return $this->wrap('laravel_source.'.$column).' = '.$this->wrap($query->from.'.'.$column);
        })->implode(' and ');

        $sql .= 'on '.$on.' ';

        if ($update) {
            $update = collect($update)->map(function ($value, $key) {
                return is_numeric($key)
                    ? $this->wrap($value).' = '.$this->wrap('laravel_source.'.$value)
                    : $this->wrap($key).' = '.$this->parameter($value);
            })->implode(', ');

            $sql .= 'when matched then update set '.$update.' ';
        }

        $sql .= 'when not matched then insert ('.$columns.') values ('.$columns.');';

        return $sql;
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
        $cleanBindings = Arr::except($bindings, 'select');

        return array_values(
            array_merge($values, Arr::flatten($cleanBindings))
        );
    }

    /**
     * Compile the SQL statement to define a savepoint.
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepoint($name)
    {
        return 'SAVE TRANSACTION '.$name;
    }

    /**
     * Compile the SQL statement to execute a savepoint rollback.
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepointRollBack($name)
    {
        return 'ROLLBACK TRANSACTION '.$name;
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y-m-d H:i:s.v';
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        return $value === '*' ? $value : '['.str_replace(']', ']]', $value).']';
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

    /**
     * Wrap the given JSON boolean value.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapJsonBooleanValue($value)
    {
        return "'".$value."'";
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  \Illuminate\Database\Query\Expression|string  $table
     * @return string
     */
    public function wrapTable($table)
    {
        if (! $this->isExpression($table)) {
            return $this->wrapTableValuedFunction(parent::wrapTable($table));
        }

        return $this->getValue($table);
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  string  $table
     * @return string
     */
    protected function wrapTableValuedFunction($table)
    {
        if (preg_match('/^(.+?)(\(.*?\))]$/', $table, $matches) === 1) {
            $table = $matches[1].']'.$matches[2];
        }

        return $table;
    }
}
