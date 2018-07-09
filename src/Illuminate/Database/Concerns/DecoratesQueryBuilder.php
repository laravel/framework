<?php

namespace Illuminate\Database\Concerns;

use Closure;
use Illuminate\Database\Query\Builder;

/**
 * Trait DecoratesQueryBuilder
 *
 * @property Builder $query
 */
trait DecoratesQueryBuilder
{
    /**
     * Set the columns to be selected.
     *
     * @param  array|mixed $columns
     * @return static
     */
    public function select($columns = ['*'])
    {
        $this->query->select($columns);

        return $this;
    }

    /**
     * Add a new "raw" select expression to the query.
     *
     * @param  string $expression
     * @param  array $bindings
     * @return static
     */
    public function selectRaw($expression, array $bindings = [])
    {
        $this->query->selectRaw($expression, $bindings);

        return $this;
    }

    /**
     * Add a subselect expression to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  string $as
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public function selectSub($query, $as)
    {
        $this->query->selectSub($query, $as);

        return $this;
    }

    /**
     * Add a new select column to the query.
     *
     * @param  array|mixed $column
     * @return static
     */
    public function addSelect($column)
    {
        $this->query->addSelect($column);

        return $this;
    }

    /**
     * Force the query to only return distinct results.
     *
     * @return static
     */
    public function distinct()
    {
        $this->query->distinct();

        return $this;
    }

    /**
     * Add a join clause to the query.
     *
     * @param  string $table
     * @param  string $first
     * @param  string|null $operator
     * @param  string|null $second
     * @param  string $type
     * @param  bool $where
     * @return static
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        $this->query->join($table, $first, $operator, $second, $type, $where);

        return $this;
    }

    /**
     * Add a "join where" clause to the query.
     *
     * @param  string $table
     * @param  string $first
     * @param  string $operator
     * @param  string $second
     * @param  string $type
     * @return static
     */
    public function joinWhere($table, $first, $operator, $second, $type = 'inner')
    {
        $this->query->joinWhere($table, $first, $operator, $second, $type);

        return $this;
    }

    /**
     * Add a left join to the query.
     *
     * @param  string $table
     * @param  string $first
     * @param  string|null $operator
     * @param  string|null $second
     * @return static
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        $this->query->leftJoin($table, $first, $operator, $second);

        return $this;
    }

    /**
     * Add a "join where" clause to the query.
     *
     * @param  string $table
     * @param  string $first
     * @param  string $operator
     * @param  string $second
     * @return static
     */
    public function leftJoinWhere($table, $first, $operator, $second)
    {
        $this->query->leftJoinWhere($table, $first, $operator, $second);

        return $this;
    }

    /**
     * Add a right join to the query.
     *
     * @param  string $table
     * @param  string $first
     * @param  string|null $operator
     * @param  string|null $second
     * @return static
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        $this->query->rightJoin($table, $first, $operator, $second);

        return $this;
    }

    /**
     * Add a "right join where" clause to the query.
     *
     * @param  string $table
     * @param  string $first
     * @param  string $operator
     * @param  string $second
     * @return static
     */
    public function rightJoinWhere($table, $first, $operator, $second)
    {
        $this->query->rightJoinWhere($table, $first, $operator, $second);

        return $this;
    }

    /**
     * Add a "cross join" clause to the query.
     *
     * @param  string $table
     * @param  string|null $first
     * @param  string|null $operator
     * @param  string|null $second
     * @return static
     */
    public function crossJoin($table, $first = null, $operator = null, $second = null)
    {
        $this->query->crossJoin($table, $first, $operator, $second);

        return $this;
    }

    /**
     * Merge an array of where clauses and bindings.
     *
     * @param  array $wheres
     * @param  array $bindings
     * @return void
     */
    public function mergeWheres($wheres, $bindings)
    {
        $this->query->mergeWheres($wheres, $bindings);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string|array|\Closure $column
     * @param  string|null $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return static
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->query->where($column, $operator, $value, $boolean);

        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param  string|array|\Closure $column
     * @param  string|null $operator
     * @param  mixed $value
     * @return static
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        $this->query->orWhere($column, $operator, $value);

        return $this;
    }

    /**
     * Add a "where" clause comparing two columns to the query.
     *
     * @param  string|array $first
     * @param  string|null $operator
     * @param  string|null $second
     * @param  string|null $boolean
     * @return static
     */
    public function whereColumn($first, $operator = null, $second = null, $boolean = 'and')
    {
        $this->query->whereColumn($first, $operator, $second, $boolean);

        return $this;
    }

    /**
     * Add an "or where" clause comparing two columns to the query.
     *
     * @param  string|array $first
     * @param  string|null $operator
     * @param  string|null $second
     * @return static
     */
    public function orWhereColumn($first, $operator = null, $second = null)
    {
        $this->query->orWhereColumn($first, $operator, $second);

        return $this;
    }

    /**
     * Add a raw where clause to the query.
     *
     * @param  string $sql
     * @param  mixed $bindings
     * @param  string $boolean
     * @return static
     */
    public function whereRaw($sql, $bindings = [], $boolean = 'and')
    {
        $this->query->whereRaw($sql, $bindings, $boolean);

        return $this;
    }

    /**
     * Add a raw or where clause to the query.
     *
     * @param  string $sql
     * @param  mixed $bindings
     * @return static
     */
    public function orWhereRaw($sql, $bindings = [])
    {
        $this->query->orWhereRaw($sql, $bindings);

        return $this;
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param  string $column
     * @param  mixed $values
     * @param  string $boolean
     * @param  bool $not
     * @return static
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $this->query->whereIn($column, $values, $boolean, $not);

        return $this;
    }

    /**
     * Add an "or where in" clause to the query.
     *
     * @param  string $column
     * @param  mixed $values
     * @return static
     */
    public function orWhereIn($column, $values)
    {
        $this->query->orWhereIn($column, $values);

        return $this;
    }

    /**
     * Add a "where not in" clause to the query.
     *
     * @param  string $column
     * @param  mixed $values
     * @param  string $boolean
     * @return static
     */
    public function whereNotIn($column, $values, $boolean = 'and')
    {
        $this->query->whereNotIn($column, $values, $boolean);

        return $this;
    }

    /**
     * Add an "or where not in" clause to the query.
     *
     * @param  string $column
     * @param  mixed $values
     * @return static
     */
    public function orWhereNotIn($column, $values)
    {
        $this->query->orWhereNotIn($column, $values);

        return $this;
    }

    /**
     * Add a "where null" clause to the query.
     *
     * @param  string $column
     * @param  string $boolean
     * @param  bool $not
     * @return static
     */
    public function whereNull($column, $boolean = 'and', $not = false)
    {
        $this->query->whereNull($column, $boolean, $not);

        return $this;
    }

    /**
     * Add an "or where null" clause to the query.
     *
     * @param  string $column
     * @return static
     */
    public function orWhereNull($column)
    {
        $this->query->orWhereNull($column);

        return $this;
    }

    /**
     * Add a "where not null" clause to the query.
     *
     * @param  string $column
     * @param  string $boolean
     * @return static
     */
    public function whereNotNull($column, $boolean = 'and')
    {
        $this->query->whereNotNull($column, $boolean);

        return $this;
    }

    /**
     * Add a where between statement to the query.
     *
     * @param  string $column
     * @param  array $values
     * @param  string $boolean
     * @param  bool $not
     * @return static
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        $this->query->whereBetween($column, $values, $boolean, $not);

        return $this;
    }

    /**
     * Add an or where between statement to the query.
     *
     * @param  string $column
     * @param  array $values
     * @return static
     */
    public function orWhereBetween($column, array $values)
    {
        $this->query->orWhereBetween($column, $values);

        return $this;
    }

    /**
     * Add a where not between statement to the query.
     *
     * @param  string $column
     * @param  array $values
     * @param  string $boolean
     * @return static
     */
    public function whereNotBetween($column, array $values, $boolean = 'and')
    {
        $this->query->whereNotBetween($column, $values, $boolean);

        return $this;
    }

    /**
     * Add an or where not between statement to the query.
     *
     * @param  string $column
     * @param  array $values
     * @return static
     */
    public function orWhereNotBetween($column, array $values)
    {
        $this->query->orWhereNotBetween($column, $values);

        return $this;
    }

    /**
     * Add an "or where not null" clause to the query.
     *
     * @param  string $column
     * @return static
     */
    public function orWhereNotNull($column)
    {
        $this->query->orWhereNotNull($column);

        return $this;
    }

    /**
     * Add a "where date" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return static
     */
    public function whereDate($column, $operator, $value = null, $boolean = 'and')
    {
        $this->query->whereDate($column, $operator, $value, $boolean);

        return $this;
    }

    /**
     * Add an "or where date" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  string $value
     * @return static
     */
    public function orWhereDate($column, $operator, $value)
    {
        $this->query->orWhereDate($column, $operator, $value);

        return $this;
    }

    /**
     * Add a "where time" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  int $value
     * @param  string $boolean
     * @return static
     */
    public function whereTime($column, $operator, $value, $boolean = 'and')
    {
        $this->query->whereTime($column, $operator, $value, $boolean);

        return $this;
    }

    /**
     * Add an "or where time" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  int $value
     * @return static
     */
    public function orWhereTime($column, $operator, $value)
    {
        $this->query->orWhereTime($column, $operator, $value);

        return $this;
    }

    /**
     * Add a "where day" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return static
     */
    public function whereDay($column, $operator, $value = null, $boolean = 'and')
    {
        $this->query->whereDay($column, $operator, $value, $boolean);

        return $this;
    }

    /**
     * Add a "where month" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return static
     */
    public function whereMonth($column, $operator, $value = null, $boolean = 'and')
    {
        $this->query->whereMonth($column, $operator, $value, $boolean);

        return $this;
    }

    /**
     * Add a "where year" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return static
     */
    public function whereYear($column, $operator, $value = null, $boolean = 'and')
    {
        $this->query->whereYear($column, $operator, $value, $boolean);

        return $this;
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param  \Closure $callback
     * @param  string $boolean
     * @return static
     */
    public function whereNested(Closure $callback, $boolean = 'and')
    {
        $this->query->whereNested($callback, $boolean);

        return $this;
    }

    /**
     * Create a new query instance for nested where condition.
     *
     * @return static
     */
    public function forNestedWhere()
    {
        $this->query->forNestedWhere();

        return $this;
    }

    /**
     * Add another query builder as a nested where to the query builder.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  string $boolean
     * @return static
     */
    public function addNestedWhereQuery($query, $boolean = 'and')
    {
        $this->query->addNestedWhereQuery($query, $boolean);

        return $this;
    }

    /**
     * Add an exists clause to the query.
     *
     * @param  \Closure $callback
     * @param  string $boolean
     * @param  bool $not
     * @return static
     */
    public function whereExists(Closure $callback, $boolean = 'and', $not = false)
    {
        $this->query->whereExists($callback, $boolean, $not);

        return $this;
    }

    /**
     * Add an or exists clause to the query.
     *
     * @param  \Closure $callback
     * @param  bool $not
     * @return static
     */
    public function orWhereExists(Closure $callback, $not = false)
    {
        $this->query->orWhereExists($callback, $not);

        return $this;
    }

    /**
     * Add a where not exists clause to the query.
     *
     * @param  \Closure $callback
     * @param  string $boolean
     * @return static
     */
    public function whereNotExists(Closure $callback, $boolean = 'and')
    {
        $this->query->whereNotExists($callback, $boolean);

        return $this;
    }

    /**
     * Add a where not exists clause to the query.
     *
     * @param  \Closure $callback
     * @return static
     */
    public function orWhereNotExists(Closure $callback)
    {
        $this->query->orWhereNotExists($callback);

        return $this;
    }

    /**
     * Add an exists clause to the query.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  string $boolean
     * @param  bool $not
     * @return static
     */
    public function addWhereExistsQuery(Builder $query, $boolean = 'and', $not = false)
    {
        $this->query->addWhereExistsQuery($query, $boolean, $not);

        return $this;
    }

    /**
     * Handles dynamic "where" clauses to the query.
     *
     * @param  string $method
     * @param  string $parameters
     * @return static
     */
    public function dynamicWhere($method, $parameters)
    {
        $this->query->dynamicWhere($method, $parameters);

        return $this;
    }

    /**
     * Add a "group by" clause to the query.
     *
     * @param  array ...$groups
     * @return static
     */
    public function groupBy(...$groups)
    {
        $this->query->groupBy(...$groups);

        return $this;
    }

    /**
     * Add a "having" clause to the query.
     *
     * @param  string $column
     * @param  string|null $operator
     * @param  string|null $value
     * @param  string $boolean
     * @return static
     */
    public function having($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->query->having($column, $operator, $value, $boolean);

        return $this;
    }

    /**
     * Add a "or having" clause to the query.
     *
     * @param  string $column
     * @param  string|null $operator
     * @param  string|null $value
     * @return static
     */
    public function orHaving($column, $operator = null, $value = null)
    {
        $this->query->orHaving($column, $operator, $value);

        return $this;
    }

    /**
     * Add a raw having clause to the query.
     *
     * @param  string $sql
     * @param  array $bindings
     * @param  string $boolean
     * @return static
     */
    public function havingRaw($sql, array $bindings = [], $boolean = 'and')
    {
        $this->query->havingRaw($sql, $bindings, $boolean);

        return $this;
    }

    /**
     * Add a raw or having clause to the query.
     *
     * @param  string $sql
     * @param  array $bindings
     * @return static
     */
    public function orHavingRaw($sql, array $bindings = [])
    {
        $this->query->orHavingRaw($sql, $bindings);

        return $this;
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  string $column
     * @param  string $direction
     * @return static
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->query->orderBy($column, $direction);

        return $this;
    }

    /**
     * Add a descending "order by" clause to the query.
     *
     * @param  string $column
     * @return static
     */
    public function orderByDesc($column)
    {
        $this->query->orderByDesc($column);

        return $this;
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string $column
     * @return static
     */
    public function latest($column = 'created_at')
    {
        $this->query->latest($column);

        return $this;
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string $column
     * @return static
     */
    public function oldest($column = 'created_at')
    {
        $this->query->oldest($column);

        return $this;
    }

    /**
     * Put the query's results in random order.
     *
     * @param  string $seed
     * @return static
     */
    public function inRandomOrder($seed = '')
    {
        $this->query->inRandomOrder($seed);

        return $this;
    }

    /**
     * Add a raw "order by" clause to the query.
     *
     * @param  string $sql
     * @param  array $bindings
     * @return static
     */
    public function orderByRaw($sql, $bindings = [])
    {
        $this->query->orderByRaw($sql, $bindings);

        return $this;
    }

    /**
     * Alias to set the "offset" value of the query.
     *
     * @param  int $value
     * @return static
     */
    public function skip($value)
    {
        $this->query->skip($value);

        return $this;
    }

    /**
     * Set the "offset" value of the query.
     *
     * @param  int $value
     * @return static
     */
    public function offset($value)
    {
        $this->query->offset($value);

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param  int $value
     * @return static
     */
    public function take($value)
    {
        $this->query->take($value);

        return $this;
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param  int $value
     * @return static
     */
    public function limit($value)
    {
        $this->query->limit($value);

        return $this;
    }

    /**
     * Set the limit and offset for a given page.
     *
     * @param  int $page
     * @param  int $perPage
     * @return static
     */
    public function forPage($page, $perPage = 15)
    {
        $this->query->forPage($page, $perPage);

        return $this;
    }

    /**
     * Constrain the query to the next "page" of results after a given ID.
     *
     * @param  int $perPage
     * @param  int $lastId
     * @param  string $column
     * @return static
     */
    public function forPageAfterId($perPage = 15, $lastId = 0, $column = 'id')
    {
        $this->query->forPageAfterId($perPage, $lastId, $column);

        return $this;
    }

    /**
     * Add a union statement to the query.
     *
     * @param  \Illuminate\Database\Query\Builder|\Closure $query
     * @param  bool $all
     * @return static
     */
    public function union($query, $all = false)
    {
        $this->query->union($query, $all);

        return $this;
    }

    /**
     * Add a union all statement to the query.
     *
     * @param  \Illuminate\Database\Query\Builder|\Closure $query
     * @return static
     */
    public function unionAll($query)
    {
        $this->query->unionAll($query);

        return $this;
    }

    /**
     * Lock the selected rows in the table.
     *
     * @param  string|bool $value
     * @return static
     */
    public function lock($value = true)
    {
        $this->query->lock($value);

        return $this;
    }

    /**
     * Lock the selected rows in the table for updating.
     *
     * @return static
     */
    public function lockForUpdate()
    {
        $this->query->lockForUpdate();

        return $this;
    }

    /**
     * Share lock the selected rows in the table.
     *
     * @return static
     */
    public function sharedLock()
    {
        $this->query->sharedLock();

        return $this;
    }

    /**
     * Get the SQL representation of the query.
     *
     * @return string
     */
    public function toSql()
    {
        return $this->query->toSql();
    }

    /**
     * Execute a query for a single record by ID.
     *
     * @param  int $id
     * @param  array $columns
     * @return mixed|static
     */
    public function find($id, $columns = ['*'])
    {
        return $this->query->find($id, $columns);
    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param  string $column
     * @return mixed
     */
    public function value($column)
    {
        return $this->query->value($column);
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param  int $perPage
     * @param  array $columns
     * @param  string $pageName
     * @param  int|null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return $this->query->paginate($perPage, $columns, $pageName, $page);
    }

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
    public function simplePaginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return $this->query->simplePaginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Get the count of the total records for the paginator.
     *
     * @param  array $columns
     * @return int
     */
    public function getCountForPagination($columns = ['*'])
    {
        return $this->query->getCountForPagination($columns);
    }

    /**
     * Get a generator for the given query.
     *
     * @return \Generator
     */
    public function cursor()
    {
        return $this->query->cursor();
    }

    /**
     * Chunk the results of a query by comparing numeric IDs.
     *
     * @param  int $count
     * @param  callable $callback
     * @param  string $column
     * @param  string $alias
     * @return bool
     */
    public function chunkById($count, callable $callback, $column = 'id', $alias = null)
    {
        return $this->query->chunkById($count, $callback, $column, $alias);
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param  string $column
     * @param  string|null $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null)
    {
        return $this->query->pluck($column, $key);
    }

    /**
     * Concatenate values of a given column as a string.
     *
     * @param  string $column
     * @param  string $glue
     * @return string
     */
    public function implode($column, $glue = '')
    {
        return $this->query->implode($column, $glue);
    }

    /**
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->query->exists();
    }

    /**
     * Retrieve the "count" result of the query.
     *
     * @param  string $columns
     * @return int
     */
    public function count($columns = '*')
    {
        return $this->query->count($columns);
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public function min($column)
    {
        return $this->query->min($column);
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public function max($column)
    {
        return $this->query->max($column);
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public function sum($column)
    {
        return $this->query->sum($column);
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public function avg($column)
    {
        return $this->query->avg($column);
    }

    /**
     * Alias for the "avg" method.
     *
     * @param  string $column
     * @return mixed
     */
    public function average($column)
    {
        return $this->query->average($column);
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param  string $function
     * @param  array $columns
     * @return mixed
     */
    public function aggregate($function, $columns = ['*'])
    {
        return $this->query->aggregate($function, $columns);
    }

    /**
     * Execute a numeric aggregate function on the database.
     *
     * @param  string $function
     * @param  array $columns
     * @return float|int
     */
    public function numericAggregate($function, $columns = ['*'])
    {
        return $this->query->numericAggregate($function, $columns);
    }

    /**
     * Insert a new record into the database.
     *
     * @param  array $values
     * @return bool
     */
    public function insert(array $values)
    {
        return $this->query->insert($values);
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array $values
     * @param  string|null $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        return $this->query->insertGetId($values, $sequence);
    }

    /**
     * Update a record in the database.
     *
     * @param  array $values
     * @return int
     */
    public function update(array $values)
    {
        return $this->query->update($values);
    }

    /**
     * Insert or update a record matching the attributes, and fill it with values.
     *
     * @param  array $attributes
     * @param  array $values
     * @return bool
     */
    public function updateOrInsert(array $attributes, array $values = [])
    {
        return $this->query->updateOrInsert($attributes, $values);
    }

    /**
     * Increment a column's value by a given amount.
     *
     * @param  string $column
     * @param  int $amount
     * @param  array $extra
     * @return int
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        return $this->query->increment($column, $amount, $extra);
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * @param  string $column
     * @param  int $amount
     * @param  array $extra
     * @return int
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        return $this->query->decrement($column, $amount, $extra);
    }

    /**
     * Run a truncate statement on the table.
     *
     * @return void
     */
    public function truncate()
    {
        $this->query->truncate();
    }

    /**
     * Create a raw database expression.
     *
     * @param  mixed $value
     * @return \Illuminate\Database\Query\Expression
     */
    public function raw($value)
    {
        return $this->query->raw($value);
    }

    /**
     * Get the current query value bindings in a flattened array.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->query->getBindings();
    }

    /**
     * Get the raw array of bindings.
     *
     * @return array
     */
    public function getRawBindings()
    {
        return $this->query->getRawBindings();
    }

    /**
     * Set the bindings on the query builder.
     *
     * @param  array $bindings
     * @param  string $type
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public function setBindings(array $bindings, $type = 'where')
    {
        $this->query->setBindings($bindings, $type);

        return $this;
    }

    /**
     * Add a binding to the query.
     *
     * @param  mixed $value
     * @param  string $type
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public function addBinding($value, $type = 'where')
    {
        $this->query->addBinding($value, $type);

        return $this;
    }

    /**
     * Merge an array of bindings into our bindings.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return static
     */
    public function mergeBindings(Builder $query)
    {
        $this->query->mergeBindings($query);

        return $this;
    }

    /**
     * Get the database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->query->getConnection();
    }

    /**
     * Get the database query processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    public function getProcessor()
    {
        return $this->query->getProcessor();
    }

    /**
     * Get the query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    public function getGrammar()
    {
        return $this->query->getGrammar();
    }

    /**
     * Use the write pdo for query.
     *
     * @return static
     */
    public function useWritePdo()
    {
        $this->query->useWritePdo();

        return $this;
    }

    /**
     * Force a clone of the underlying query builder when cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }
}
