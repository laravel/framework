<?php

namespace Illuminate\Contracts\Database\Query;

use Closure;

interface Builder
{
    /**
     * Set the columns to be selected.
     *
     * @param  array|mixed  $columns
     * @return static
     */
    public function select($columns = ['*']);

    /**
     * Add a subselect expression to the query.
     *
     * @param  \Closure|static|string  $query
     * @param  string  $as
     * @return static
     */
    public function selectSub($query, $as);

    /**
     * Add a new "raw" select expression to the query.
     *
     * @param  string  $expression
     * @param  array  $bindings
     * @return static
     */
    public function selectRaw($expression, array $bindings = []);

    /**
     * Makes "from" fetch from a subquery.
     *
     * @param  \Closure|static|string  $query
     * @param  string  $as
     * @return static
     */
    public function fromSub($query, $as);

    /**
     * Add a raw from clause to the query.
     *
     * @param  string  $expression
     * @param  mixed  $bindings
     * @return static
     */
    public function fromRaw($expression, $bindings = []);

    /**
     * Add a new select column to the query.
     *
     * @param  array|mixed  $column
     * @return static
     */
    public function addSelect($column);

    /**
     * Force the query to only return distinct results.
     *
     * @param  mixed  ...$distinct
     * @return static
     */
    public function distinct();

    /**
     * Set the table which the query is targeting.
     *
     * @param  \Closure|static|string  $table
     * @param  string|null  $as
     * @return static
     */
    public function from($table, $as = null);

    /**
     * Add a join clause to the query.
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @param  bool  $where
     * @return static
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false);

    /**
     * Add a "join where" clause to the query.
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string  $operator
     * @param  string  $second
     * @param  string  $type
     * @return static
     */
    public function joinWhere($table, $first, $operator, $second, $type = 'inner');

    /**
     * Add a subquery join clause to the query.
     *
     * @param  \Closure|static|string  $query
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @param  bool  $where
     * @return static
     */
    public function joinSub($query, $as, $first, $operator = null, $second = null, $type = 'inner', $where = false);

    /**
     * Add a left join to the query.
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return static
     */
    public function leftJoin($table, $first, $operator = null, $second = null);

    /**
     * Add a "join where" clause to the query.
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return static
     */
    public function leftJoinWhere($table, $first, $operator, $second);

    /**
     * Add a subquery left join to the query.
     *
     * @param  \Closure|static|string  $query
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return static
     */
    public function leftJoinSub($query, $as, $first, $operator = null, $second = null);

    /**
     * Add a right join to the query.
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return static
     */
    public function rightJoin($table, $first, $operator = null, $second = null);

    /**
     * Add a "right join where" clause to the query.
     *
     * @param  string  $table
     * @param  \Closure|string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return static
     */
    public function rightJoinWhere($table, $first, $operator, $second);

    /**
     * Add a subquery right join to the query.
     *
     * @param  \Closure|static|string  $query
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return static
     */
    public function rightJoinSub($query, $as, $first, $operator = null, $second = null);

    /**
     * Add a "cross join" clause to the query.
     *
     * @param  string  $table
     * @param  \Closure|string|null  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return static
     */
    public function crossJoin($table, $first = null, $operator = null, $second = null);

    /**
     * Add a subquery cross join to the query.
     *
     * @param  \Closure|static|string  $query
     * @param  string  $as
     * @return static
     */
    public function crossJoinSub($query, $as);

    /**
     * Merge an array of where clauses and bindings.
     *
     * @param  array  $wheres
     * @param  array  $bindings
     * @return static
     */
    public function mergeWheres($wheres, $bindings);

    /**
     * Add a basic where clause to the query.
     *
     * @param  \Closure|\Illuminate\Contracts\Database\Query\Builder|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return static
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and');

    /**
     * Prepare the value and operator for a where clause.
     *
     * @param  string  $value
     * @param  string  $operator
     * @param  bool  $useDefault
     * @return array
     */
    public function prepareValueAndOperator($value, $operator, $useDefault = false);

    /**
     * Add an "or where" clause to the query.
     *
     * @param  \Closure|\Illuminate\Contracts\Database\Query\Builder|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return static
     */
    public function orWhere($column, $operator = null, $value = null);

    /**
     * Add a "where" clause comparing two columns to the query.
     *
     * @param  string|array  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string|null  $boolean
     * @return static
     */
    public function whereColumn($first, $operator = null, $second = null, $boolean = 'and');

    /**
     * Add an "or where" clause comparing two columns to the query.
     *
     * @param  string|array  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return static
     */
    public function orWhereColumn($first, $operator = null, $second = null);

    /**
     * Add a raw where clause to the query.
     *
     * @param  string  $sql
     * @param  mixed  $bindings
     * @param  string  $boolean
     * @return static
     */
    public function whereRaw($sql, $bindings = [], $boolean = 'and');

    /**
     * Add a raw "or where" clause to the query.
     *
     * @param  string  $sql
     * @param  mixed  $bindings
     * @return static
     */
    public function orWhereRaw($sql, $bindings = []);

    /**
     * Add a "where in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false);

    /**
     * Add an "or where in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $values
     * @return static
     */
    public function orWhereIn($column, $values);

    /**
     * Add a "where not in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @return static
     */
    public function whereNotIn($column, $values, $boolean = 'and');

    /**
     * Add an "or where not in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $values
     * @return static
     */
    public function orWhereNotIn($column, $values);

    /**
     * Add a "where in raw" clause for integer values to the query.
     *
     * @param  string  $column
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereIntegerInRaw($column, $values, $boolean = 'and', $not = false);

    /**
     * Add an "or where in raw" clause for integer values to the query.
     *
     * @param  string  $column
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $values
     * @return static
     */
    public function orWhereIntegerInRaw($column, $values);

    /**
     * Add a "where not in raw" clause for integer values to the query.
     *
     * @param  string  $column
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $values
     * @param  string  $boolean
     * @return static
     */
    public function whereIntegerNotInRaw($column, $values, $boolean = 'and');

    /**
     * Add an "or where not in raw" clause for integer values to the query.
     *
     * @param  string  $column
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $values
     * @return static
     */
    public function orWhereIntegerNotInRaw($column, $values);

    /**
     * Add a "where null" clause to the query.
     *
     * @param  string|array  $columns
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereNull($columns, $boolean = 'and', $not = false);

    /**
     * Add an "or where null" clause to the query.
     *
     * @param  string|array  $column
     * @return static
     */
    public function orWhereNull($column);

    /**
     * Add a "where not null" clause to the query.
     *
     * @param  string|array  $columns
     * @param  string  $boolean
     * @return static
     */
    public function whereNotNull($columns, $boolean = 'and');

    /**
     * Add a where between statement to the query.
     *
     * @param  string|\Illuminate\Database\Query\Expression  $column
     * @param  iterable  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereBetween($column, iterable $values, $boolean = 'and', $not = false);

    /**
     * Add a where between statement using columns to the query.
     *
     * @param  string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereBetweenColumns($column, array $values, $boolean = 'and', $not = false);

    /**
     * Add an or where between statement to the query.
     *
     * @param  string  $column
     * @param  iterable  $values
     * @return static
     */
    public function orWhereBetween($column, iterable $values);

    /**
     * Add an or where between statement using columns to the query.
     *
     * @param  string  $column
     * @param  array  $values
     * @return static
     */
    public function orWhereBetweenColumns($column, array $values);

    /**
     * Add a where not between statement to the query.
     *
     * @param  string  $column
     * @param  iterable  $values
     * @param  string  $boolean
     * @return static
     */
    public function whereNotBetween($column, iterable $values, $boolean = 'and');

    /**
     * Add a where not between statement using columns to the query.
     *
     * @param  string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @return static
     */
    public function whereNotBetweenColumns($column, array $values, $boolean = 'and');

    /**
     * Add an or where not between statement to the query.
     *
     * @param  string  $column
     * @param  iterable  $values
     * @return static
     */
    public function orWhereNotBetween($column, iterable $values);

    /**
     * Add an or where not between statement using columns to the query.
     *
     * @param  string  $column
     * @param  array  $values
     * @return static
     */
    public function orWhereNotBetweenColumns($column, array $values);

    /**
     * Add an "or where not null" clause to the query.
     *
     * @param  string  $column
     * @return static
     */
    public function orWhereNotNull($column);

    /**
     * Add a "where date" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @param  string  $boolean
     * @return static
     */
    public function whereDate($column, $operator, $value = null, $boolean = 'and');

    /**
     * Add an "or where date" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @return static
     */
    public function orWhereDate($column, $operator, $value = null);

    /**
     * Add a "where time" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @param  string  $boolean
     * @return static
     */
    public function whereTime($column, $operator, $value = null, $boolean = 'and');

    /**
     * Add an "or where time" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @return static
     */
    public function orWhereTime($column, $operator, $value = null);

    /**
     * Add a "where day" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @param  string  $boolean
     * @return static
     */
    public function whereDay($column, $operator, $value = null, $boolean = 'and');

    /**
     * Add an "or where day" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @return static
     */
    public function orWhereDay($column, $operator, $value = null);

    /**
     * Add a "where month" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @param  string  $boolean
     * @return static
     */
    public function whereMonth($column, $operator, $value = null, $boolean = 'and');

    /**
     * Add an "or where month" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|null  $value
     * @return static
     */
    public function orWhereMonth($column, $operator, $value = null);

    /**
     * Add a "where year" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|int|null  $value
     * @param  string  $boolean
     * @return static
     */
    public function whereYear($column, $operator, $value = null, $boolean = 'and');

    /**
     * Add an "or where year" statement to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \DateTimeInterface|string|int|null  $value
     * @return static
     */
    public function orWhereYear($column, $operator, $value = null);

    /**
     * Add a nested where statement to the query.
     *
     * @param  \Closure  $callback
     * @param  string  $boolean
     * @return static
     */
    public function whereNested(Closure $callback, $boolean = 'and');

    /**
     * Add another query builder as a nested where to the query builder.
     *
     * @param  static  $query
     * @param  string  $boolean
     * @return static
     */
    public function addNestedWhereQuery($query, $boolean = 'and');

    /**
     * Add an exists clause to the query.
     *
     * @param  \Closure  $callback
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereExists(Closure $callback, $boolean = 'and', $not = false);

    /**
     * Add an or exists clause to the query.
     *
     * @param  \Closure  $callback
     * @param  bool  $not
     * @return static
     */
    public function orWhereExists(Closure $callback, $not = false);

    /**
     * Add a where not exists clause to the query.
     *
     * @param  \Closure  $callback
     * @param  string  $boolean
     * @return static
     */
    public function whereNotExists(Closure $callback, $boolean = 'and');

    /**
     * Add a where not exists clause to the query.
     *
     * @param  \Closure  $callback
     * @return static
     */
    public function orWhereNotExists(Closure $callback);

    /**
     * Adds a where condition using row values.
     *
     * @param  array  $columns
     * @param  string  $operator
     * @param  array  $values
     * @param  string  $boolean
     * @return static
     */
    public function whereRowValues($columns, $operator, $values, $boolean = 'and');

    /**
     * Adds an or where condition using row values.
     *
     * @param  array  $columns
     * @param  string  $operator
     * @param  array  $values
     * @return static
     */
    public function orWhereRowValues($columns, $operator, $values);

    /**
     * Add a "where JSON contains" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $value
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereJsonContains($column, $value, $boolean = 'and', $not = false);

    /**
     * Add an "or where JSON contains" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $value
     * @return static
     */
    public function orWhereJsonContains($column, $value);

    /**
     * Add a "where JSON not contains" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $value
     * @param  string  $boolean
     * @return static
     */
    public function whereJsonDoesntContain($column, $value, $boolean = 'and');

    /**
     * Add an "or where JSON not contains" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $value
     * @return static
     */
    public function orWhereJsonDoesntContain($column, $value);

    /**
     * Add a "where JSON length" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return static
     */
    public function whereJsonLength($column, $operator, $value = null, $boolean = 'and');

    /**
     * Add an "or where JSON length" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return static
     */
    public function orWhereJsonLength($column, $operator, $value = null);

    /**
     * Add a "group by" clause to the query.
     *
     * @param  array|string  ...$groups
     * @return static
     */
    public function groupBy(...$groups);

    /**
     * Add a raw groupBy clause to the query.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @return static
     */
    public function groupByRaw($sql, array $bindings = []);

    /**
     * Add a "having" clause to the query.
     *
     * @param  string  $column
     * @param  string|null  $operator
     * @param  string|null  $value
     * @param  string  $boolean
     * @return static
     */
    public function having($column, $operator = null, $value = null, $boolean = 'and');

    /**
     * Add an "or having" clause to the query.
     *
     * @param  string  $column
     * @param  string|null  $operator
     * @param  string|null  $value
     * @return static
     */
    public function orHaving($column, $operator = null, $value = null);

    /**
     * Add a "having null" clause to the query.
     *
     * @param  string|array  $columns
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function havingNull($columns, $boolean = 'and', $not = false);

    /**
     * Add an "or having null" clause to the query.
     *
     * @param  string  $column
     * @return static
     */
    public function orHavingNull($column);

    /**
     * Add a "having not null" clause to the query.
     *
     * @param  string|array  $columns
     * @param  string  $boolean
     * @return static
     */
    public function havingNotNull($columns, $boolean = 'and');

    /**
     * Add an "or having not null" clause to the query.
     *
     * @param  string  $column
     * @return static
     */
    public function orHavingNotNull($column);

    /**
     * Add a "having between" clause to the query.
     *
     * @param  string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function havingBetween($column, array $values, $boolean = 'and', $not = false);

    /**
     * Add a raw having clause to the query.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  string  $boolean
     * @return static
     */
    public function havingRaw($sql, array $bindings = [], $boolean = 'and');

    /**
     * Add a raw or having clause to the query.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @return static
     */
    public function orHavingRaw($sql, array $bindings = []);

    /**
     * Add an "order by" clause to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Expression|\Illuminate\Contracts\Database\Query\Builder|string  $column
     * @param  string  $direction
     * @return static
     */
    public function orderBy($column, $direction = 'asc');

    /**
     * Add a descending "order by" clause to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Expression|\Illuminate\Contracts\Database\Query\Builder|string  $column
     * @return static
     */
    public function orderByDesc($column);

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Expression|\Illuminate\Contracts\Database\Query\Builder|string  $column
     * @return static
     */
    public function latest($column = 'created_at');

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Expression|\Illuminate\Contracts\Database\Query\Builder|string  $column
     * @return static
     */
    public function oldest($column = 'created_at');

    /**
     * Put the query's results in random order.
     *
     * @param  string  $seed
     * @return static
     */
    public function inRandomOrder($seed = '');

    /**
     * Add a raw "order by" clause to the query.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @return static
     */
    public function orderByRaw($sql, $bindings = []);

    /**
     * Alias to set the "offset" value of the query.
     *
     * @param  int  $value
     * @return static
     */
    public function skip($value);

    /**
     * Set the "offset" value of the query.
     *
     * @param  int  $value
     * @return static
     */
    public function offset($value);

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param  int  $value
     * @return static
     */
    public function take($value);

    /**
     * Set the "limit" value of the query.
     *
     * @param  int  $value
     * @return static
     */
    public function limit($value);

    /**
     * Set the limit and offset for a given page.
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return static
     */
    public function forPage($page, $perPage = 15);

    /**
     * Constrain the query to the previous "page" of results before a given ID.
     *
     * @param  int  $perPage
     * @param  int|null  $lastId
     * @param  string  $column
     * @return static
     */
    public function forPageBeforeId($perPage = 15, $lastId = 0, $column = 'id');

    /**
     * Constrain the query to the next "page" of results after a given ID.
     *
     * @param  int  $perPage
     * @param  int|null  $lastId
     * @param  string  $column
     * @return static
     */
    public function forPageAfterId($perPage = 15, $lastId = 0, $column = 'id');

    /**
     * Remove all existing orders and optionally add a new order.
     *
     * @param  string|null  $column
     * @param  string  $direction
     * @return static
     */
    public function reorder($column = null, $direction = 'asc');

    /**
     * Add a union statement to the query.
     *
     * @param  static|\Closure  $query
     * @param  bool  $all
     * @return static
     */
    public function union($query, $all = false);

    /**
     * Add a union all statement to the query.
     *
     * @param  static|\Closure  $query
     * @return static
     */
    public function unionAll($query);

    /**
     * Lock the selected rows in the table.
     *
     * @param  string|bool  $value
     * @return static
     */
    public function lock($value = true);

    /**
     * Lock the selected rows in the table for updating.
     *
     * @return static
     */
    public function lockForUpdate();

    /**
     * Share lock the selected rows in the table.
     *
     * @return static
     */
    public function sharedLock();

    /**
     * Get a new instance of the query builder.
     *
     * @return static
     */
    public function newQuery();

    /**
     * Create a raw database expression.
     *
     * @param  mixed  $value
     * @return \Illuminate\Database\Query\Expression
     */
    public function raw($value);

    /**
     * Get the current query value bindings in a flattened array.
     *
     * @return array
     */
    public function getBindings();

    /**
     * Get the raw array of bindings.
     *
     * @return array
     */
    public function getRawBindings();

    /**
     * Set the bindings on the query builder.
     *
     * @param  array  $bindings
     * @param  string  $type
     * @return static
     */
    public function setBindings(array $bindings, $type = 'where');

    /**
     * Add a binding to the query.
     *
     * @param  mixed  $value
     * @param  string  $type
     * @return static
     */
    public function addBinding($value, $type = 'where');

    /**
     * Merge an array of bindings into our bindings.
     *
     * @param  \Illuminate\Contracts\Database\Query\Builder  $query
     * @return static
     */
    public function mergeBindings(Builder $query);

    /**
     * Remove all of the expressions from a list of bindings.
     *
     * @param  array  $bindings
     * @return array
     */
    public function cleanBindings(array $bindings);

    /**
     * Get the database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection();

    /**
     * Get the database query processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    public function getProcessor();

    /**
     * Get the query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    public function getGrammar();

    /**
     * Use the write pdo for query.
     *
     * @return static
     */
    public function useWritePdo();

    /**
     * Clone the query.
     *
     * @return static
     */
    public function clone();

    /**
     * Clone the query without the given properties.
     *
     * @param  array  $properties
     * @return static
     */
    public function cloneWithout(array $properties);

    /**
     * Clone the query without the given bindings.
     *
     * @param  array  $except
     * @return static
     */
    public function cloneWithoutBindings(array $except);
}
