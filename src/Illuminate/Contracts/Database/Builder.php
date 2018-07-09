<?php

namespace Illuminate\Contracts\Database;

use Closure;

interface Builder
{
    /**
     * Set the columns to be selected.
     *
     * @param  array|mixed $columns
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function select($columns = ['*']);

    /**
     * Add a new "raw" select expression to the query.
     *
     * @param  string $expression
     * @param  array $bindings
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function selectRaw($expression, array $bindings = []);

    /**
     * Add a subselect expression to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  string $as
     * @return \Illuminate\Contracts\Database\Builder
     *
     * @throws \InvalidArgumentException
     */
    public function selectSub($query, $as);

    /**
     * Add a new select column to the query.
     *
     * @param  array|mixed $column
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function addSelect($column);

    /**
     * Force the query to only return distinct results.
     *
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function distinct();

    /**
     * Add a join clause to the query.
     *
     * @param  string $table
     * @param  string $first
     * @param  string|null $operator
     * @param  string|null $second
     * @param  string $type
     * @param  bool $where
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false);

    /**
     * Add a "join where" clause to the query.
     *
     * @param  string $table
     * @param  string $first
     * @param  string $operator
     * @param  string $second
     * @param  string $type
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function joinWhere($table, $first, $operator, $second, $type = 'inner');

    /**
     * Add a left join to the query.
     *
     * @param  string $table
     * @param  string $first
     * @param  string|null $operator
     * @param  string|null $second
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function leftJoin($table, $first, $operator = null, $second = null);

    /**
     * Add a "join where" clause to the query.
     *
     * @param  string $table
     * @param  string $first
     * @param  string $operator
     * @param  string $second
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function leftJoinWhere($table, $first, $operator, $second);

    /**
     * Add a right join to the query.
     *
     * @param  string $table
     * @param  string $first
     * @param  string|null $operator
     * @param  string|null $second
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function rightJoin($table, $first, $operator = null, $second = null);

    /**
     * Add a "right join where" clause to the query.
     *
     * @param  string $table
     * @param  string $first
     * @param  string $operator
     * @param  string $second
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function rightJoinWhere($table, $first, $operator, $second);

    /**
     * Add a "cross join" clause to the query.
     *
     * @param  string $table
     * @param  string|null $first
     * @param  string|null $operator
     * @param  string|null $second
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function crossJoin($table, $first = null, $operator = null, $second = null);

    /**
     * Merge an array of where clauses and bindings.
     *
     * @param  array $wheres
     * @param  array $bindings
     * @return void
     */
    public function mergeWheres($wheres, $bindings);

    /**
     * Add a basic where clause to the query.
     *
     * @param  string|array|\Closure $column
     * @param  string|null $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and');

    /**
     * Add an "or where" clause to the query.
     *
     * @param  string|array|\Closure $column
     * @param  string|null $operator
     * @param  mixed $value
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orWhere($column, $operator = null, $value = null);

    /**
     * Add a "where" clause comparing two columns to the query.
     *
     * @param  string|array $first
     * @param  string|null $operator
     * @param  string|null $second
     * @param  string|null $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereColumn($first, $operator = null, $second = null, $boolean = 'and');

    /**
     * Add an "or where" clause comparing two columns to the query.
     *
     * @param  string|array $first
     * @param  string|null $operator
     * @param  string|null $second
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orWhereColumn($first, $operator = null, $second = null);

    /**
     * Add a raw where clause to the query.
     *
     * @param  string $sql
     * @param  mixed $bindings
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereRaw($sql, $bindings = [], $boolean = 'and');

    /**
     * Add a raw or where clause to the query.
     *
     * @param  string $sql
     * @param  mixed $bindings
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orWhereRaw($sql, $bindings = []);

    /**
     * Add a "where in" clause to the query.
     *
     * @param  string $column
     * @param  mixed $values
     * @param  string $boolean
     * @param  bool $not
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false);

    /**
     * Add an "or where in" clause to the query.
     *
     * @param  string $column
     * @param  mixed $values
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orWhereIn($column, $values);

    /**
     * Add a "where not in" clause to the query.
     *
     * @param  string $column
     * @param  mixed $values
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereNotIn($column, $values, $boolean = 'and');

    /**
     * Add an "or where not in" clause to the query.
     *
     * @param  string $column
     * @param  mixed $values
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orWhereNotIn($column, $values);

    /**
     * Add a "where null" clause to the query.
     *
     * @param  string $column
     * @param  string $boolean
     * @param  bool $not
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereNull($column, $boolean = 'and', $not = false);

    /**
     * Add an "or where null" clause to the query.
     *
     * @param  string $column
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orWhereNull($column);

    /**
     * Add a "where not null" clause to the query.
     *
     * @param  string $column
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereNotNull($column, $boolean = 'and');

    /**
     * Add a where between statement to the query.
     *
     * @param  string $column
     * @param  array $values
     * @param  string $boolean
     * @param  bool $not
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false);

    /**
     * Add an or where between statement to the query.
     *
     * @param  string $column
     * @param  array $values
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orWhereBetween($column, array $values);

    /**
     * Add a where not between statement to the query.
     *
     * @param  string $column
     * @param  array $values
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereNotBetween($column, array $values, $boolean = 'and');

    /**
     * Add an or where not between statement to the query.
     *
     * @param  string $column
     * @param  array $values
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orWhereNotBetween($column, array $values);

    /**
     * Add an "or where not null" clause to the query.
     *
     * @param  string $column
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orWhereNotNull($column);

    /**
     * Add a "where date" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereDate($column, $operator, $value = null, $boolean = 'and');

    /**
     * Add an "or where date" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  string $value
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orWhereDate($column, $operator, $value);

    /**
     * Add a "where time" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  int $value
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereTime($column, $operator, $value, $boolean = 'and');

    /**
     * Add an "or where time" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  int $value
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orWhereTime($column, $operator, $value);

    /**
     * Add a "where day" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereDay($column, $operator, $value = null, $boolean = 'and');

    /**
     * Add a "where month" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereMonth($column, $operator, $value = null, $boolean = 'and');

    /**
     * Add a "where year" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereYear($column, $operator, $value = null, $boolean = 'and');

    /**
     * Add a nested where statement to the query.
     *
     * @param  \Closure $callback
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereNested(Closure $callback, $boolean = 'and');

    /**
     * Create a new query instance for nested where condition.
     *
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function forNestedWhere();

    /**
     * Add another query builder as a nested where to the query builder.
     *
     * @param  \Illuminate\Database\Query\Builder|static $query
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function addNestedWhereQuery($query, $boolean = 'and');

    /**
     * Add an exists clause to the query.
     *
     * @param  \Closure $callback
     * @param  string $boolean
     * @param  bool $not
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereExists(Closure $callback, $boolean = 'and', $not = false);

    /**
     * Add an or exists clause to the query.
     *
     * @param  \Closure $callback
     * @param  bool $not
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orWhereExists(Closure $callback, $not = false);

    /**
     * Add a where not exists clause to the query.
     *
     * @param  \Closure $callback
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function whereNotExists(Closure $callback, $boolean = 'and');

    /**
     * Add a where not exists clause to the query.
     *
     * @param  \Closure $callback
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orWhereNotExists(Closure $callback);

    /**
     * Add an exists clause to the query.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  string $boolean
     * @param  bool $not
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function addWhereExistsQuery(\Illuminate\Database\Query\Builder $query, $boolean = 'and', $not = false);

    /**
     * Handles dynamic "where" clauses to the query.
     *
     * @param  string $method
     * @param  string $parameters
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function dynamicWhere($method, $parameters);

    /**
     * Add a "group by" clause to the query.
     *
     * @param  array ...$groups
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function groupBy(...$groups);

    /**
     * Add a "having" clause to the query.
     *
     * @param  string $column
     * @param  string|null $operator
     * @param  string|null $value
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function having($column, $operator = null, $value = null, $boolean = 'and');

    /**
     * Add a "or having" clause to the query.
     *
     * @param  string $column
     * @param  string|null $operator
     * @param  string|null $value
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orHaving($column, $operator = null, $value = null);

    /**
     * Add a raw having clause to the query.
     *
     * @param  string $sql
     * @param  array $bindings
     * @param  string $boolean
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function havingRaw($sql, array $bindings = [], $boolean = 'and');

    /**
     * Add a raw or having clause to the query.
     *
     * @param  string $sql
     * @param  array $bindings
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orHavingRaw($sql, array $bindings = []);

    /**
     * Add an "order by" clause to the query.
     *
     * @param  string $column
     * @param  string $direction
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orderBy($column, $direction = 'asc');

    /**
     * Add a descending "order by" clause to the query.
     *
     * @param  string $column
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orderByDesc($column);

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string $column
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function latest($column = 'created_at');

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string $column
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function oldest($column = 'created_at');

    /**
     * Put the query's results in random order.
     *
     * @param  string $seed
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function inRandomOrder($seed = '');

    /**
     * Add a raw "order by" clause to the query.
     *
     * @param  string $sql
     * @param  array $bindings
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function orderByRaw($sql, $bindings = []);

    /**
     * Alias to set the "offset" value of the query.
     *
     * @param  int $value
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function skip($value);

    /**
     * Set the "offset" value of the query.
     *
     * @param  int $value
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function offset($value);

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param  int $value
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function take($value);

    /**
     * Set the "limit" value of the query.
     *
     * @param  int $value
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function limit($value);

    /**
     * Set the limit and offset for a given page.
     *
     * @param  int $page
     * @param  int $perPage
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function forPage($page, $perPage = 15);

    /**
     * Constrain the query to the next "page" of results after a given ID.
     *
     * @param  int $perPage
     * @param  int $lastId
     * @param  string $column
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function forPageAfterId($perPage = 15, $lastId = 0, $column = 'id');

    /**
     * Add a union statement to the query.
     *
     * @param  \Illuminate\Database\Query\Builder|\Closure $query
     * @param  bool $all
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function union($query, $all = false);

    /**
     * Add a union all statement to the query.
     *
     * @param  \Illuminate\Database\Query\Builder|\Closure $query
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function unionAll($query);

    /**
     * Lock the selected rows in the table.
     *
     * @param  string|bool $value
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function lock($value = true);

    /**
     * Lock the selected rows in the table for updating.
     *
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function lockForUpdate();

    /**
     * Share lock the selected rows in the table.
     *
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function sharedLock();

    /**
     * Get the SQL representation of the query.
     *
     * @return string
     */
    public function toSql();

    /**
     * Execute a query for a single record by ID.
     *
     * @param  int $id
     * @param  array $columns
     * @return mixed|static
     */
    public function find($id, $columns = ['*']);

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param  string $column
     * @return mixed
     */
    public function value($column);

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*']);

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param  int $perPage
     * @param  array $columns
     * @param  string $pageName
     * @param  int|null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null);

    /**
     * Get a paginator only supporting simple next and previous links.
     *
     * This is more efficient on larger data-sets, etc.
     *
     * @param  int $perPage
     * @param  array $columns
     * @param  string $pageName
     * @param  int|null $page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null);

    /**
     * Get the count of the total records for the paginator.
     *
     * @param  array $columns
     * @return int
     */
    public function getCountForPagination($columns = ['*']);

    /**
     * Get a generator for the given query.
     *
     * @return \Generator
     */
    public function cursor();

    /**
     * Chunk the results of a query by comparing numeric IDs.
     *
     * @param  int $count
     * @param  callable $callback
     * @param  string $column
     * @param  string $alias
     * @return bool
     */
    public function chunkById($count, callable $callback, $column = 'id', $alias = null);

    /**
     * Get an array with the values of a given column.
     *
     * @param  string $column
     * @param  string|null $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null);

    /**
     * Concatenate values of a given column as a string.
     *
     * @param  string $column
     * @param  string $glue
     * @return string
     */
    public function implode($column, $glue = '');

    /**
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
    public function exists();

    /**
     * Retrieve the "count" result of the query.
     *
     * @param  string $columns
     * @return int
     */
    public function count($columns = '*');

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public function min($column);

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public function max($column);

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public function sum($column);

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public function avg($column);

    /**
     * Alias for the "avg" method.
     *
     * @param  string $column
     * @return mixed
     */
    public function average($column);

    /**
     * Execute an aggregate function on the database.
     *
     * @param  string $function
     * @param  array $columns
     * @return mixed
     */
    public function aggregate($function, $columns = ['*']);

    /**
     * Execute a numeric aggregate function on the database.
     *
     * @param  string $function
     * @param  array $columns
     * @return float|int
     */
    public function numericAggregate($function, $columns = ['*']);

    /**
     * Insert a new record into the database.
     *
     * @param  array $values
     * @return bool
     */
    public function insert(array $values);

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array $values
     * @param  string|null $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null);

    /**
     * Update a record in the database.
     *
     * @param  array $values
     * @return int
     */
    public function update(array $values);

    /**
     * Insert or update a record matching the attributes, and fill it with values.
     *
     * @param  array $attributes
     * @param  array $values
     * @return bool
     */
    public function updateOrInsert(array $attributes, array $values = []);

    /**
     * Increment a column's value by a given amount.
     *
     * @param  string $column
     * @param  int $amount
     * @param  array $extra
     * @return int
     */
    public function increment($column, $amount = 1, array $extra = []);

    /**
     * Decrement a column's value by a given amount.
     *
     * @param  string $column
     * @param  int $amount
     * @param  array $extra
     * @return int
     */
    public function decrement($column, $amount = 1, array $extra = []);

    /**
     * Run a truncate statement on the table.
     *
     * @return void
     */
    public function truncate();

    /**
     * Create a raw database expression.
     *
     * @param  mixed $value
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
     * @param  array $bindings
     * @param  string $type
     * @return \Illuminate\Contracts\Database\Builder
     *
     * @throws \InvalidArgumentException
     */
    public function setBindings(array $bindings, $type = 'where');

    /**
     * Add a binding to the query.
     *
     * @param  mixed $value
     * @param  string $type
     * @return \Illuminate\Contracts\Database\Builder
     *
     * @throws \InvalidArgumentException
     */
    public function addBinding($value, $type = 'where');

    /**
     * Merge an array of bindings into our bindings.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function mergeBindings(\Illuminate\Database\Query\Builder $query);

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
     * @return \Illuminate\Contracts\Database\Builder
     */
    public function useWritePdo();
}
