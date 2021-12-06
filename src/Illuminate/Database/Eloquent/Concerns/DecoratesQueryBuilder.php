<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Closure;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @mixin \Illuminate\Database\Query\Builder
 */
trait DecoratesQueryBuilder
{
    use ForwardsCalls;

    /**
     * The decorated query builder instance.
     *
     * @var \Illuminate\Contracts\Database\Query\Builder
     */
    protected $query;

    /**
     * Get a base query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    abstract public function toBase();

    /**
     * {@inheritdoc}
     */
    public function select($columns = ['*'])
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function selectSub($query, $as)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function selectRaw($expression, array $bindings = [])
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function fromSub($query, $as)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function fromRaw($expression, $bindings = [])
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function addSelect($column)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function distinct()
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function from($table, $as = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function joinWhere($table, $first, $operator, $second, $type = 'inner')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function joinSub($query, $as, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function leftJoinWhere($table, $first, $operator, $second)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function leftJoinSub($query, $as, $first, $operator = null, $second = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rightJoinWhere($table, $first, $operator, $second)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rightJoinSub($query, $as, $first, $operator = null, $second = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function crossJoin($table, $first = null, $operator = null, $second = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function crossJoinSub($query, $as)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function mergeWheres($wheres, $bindings)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function prepareValueAndOperator($value, $operator, $useDefault = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereColumn($first, $operator = null, $second = null, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereColumn($first, $operator = null, $second = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereRaw($sql, $bindings = [], $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereRaw($sql, $bindings = [])
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereIn($column, $values)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereNotIn($column, $values)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereIntegerInRaw($column, $values, $boolean = 'and', $not = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereIntegerInRaw($column, $values)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereIntegerNotInRaw($column, $values, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereIntegerNotInRaw($column, $values)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereNull($columns, $boolean = 'and', $not = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereNull($column)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereNotNull($columns, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereBetween($column, iterable $values, $boolean = 'and', $not = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereBetweenColumns($column, array $values, $boolean = 'and', $not = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereBetween($column, iterable $values)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereBetweenColumns($column, array $values)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereNotBetween($column, iterable $values, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereNotBetweenColumns($column, array $values, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereNotBetween($column, iterable $values)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereNotBetweenColumns($column, array $values)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereNotNull($column)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereDate($column, $operator, $value = null, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereDate($column, $operator, $value = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereTime($column, $operator, $value = null, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereTime($column, $operator, $value = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereDay($column, $operator, $value = null, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereDay($column, $operator, $value = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereMonth($column, $operator, $value = null, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereMonth($column, $operator, $value = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereYear($column, $operator, $value = null, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereYear($column, $operator, $value = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereNested(Closure $callback, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function addNestedWhereQuery($query, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereExists(Closure $callback, $boolean = 'and', $not = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereExists(Closure $callback, $not = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereNotExists(Closure $callback, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereNotExists(Closure $callback)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereRowValues($columns, $operator, $values, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereRowValues($columns, $operator, $values)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereJsonContains($column, $value, $boolean = 'and', $not = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereJsonContains($column, $value)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereJsonDoesntContain($column, $value, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereJsonDoesntContain($column, $value)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function whereJsonLength($column, $operator, $value = null, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereJsonLength($column, $operator, $value = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(...$groups)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function groupByRaw($sql, array $bindings = [])
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function having($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orHaving($column, $operator = null, $value = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function havingNull($columns, $boolean = 'and', $not = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orHavingNull($column)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function havingNotNull($columns, $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function orHavingNotNull($column)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function havingBetween($column, array $values, $boolean = 'and', $not = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function havingRaw($sql, array $bindings = [], $boolean = 'and')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function orHavingRaw($sql, array $bindings = [])
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function orderBy($column, $direction = 'asc')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function orderByDesc($column)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function latest($column = 'created_at')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function oldest($column = 'created_at')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function inRandomOrder($seed = '')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function orderByRaw($sql, $bindings = [])
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function skip($value)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function offset($value)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function take($value)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function limit($value)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function forPage($page, $perPage = 15)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function forPageBeforeId($perPage = 15, $lastId = 0, $column = 'id')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function forPageAfterId($perPage = 15, $lastId = 0, $column = 'id')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function reorder($column = null, $direction = 'asc')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function union($query, $all = false)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function unionAll($query)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function lock($value = true)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function lockForUpdate()
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function sharedLock()
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function toSql()
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function find($id, $columns = ['*'])
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function value($column)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function get($columns = ['*'])
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function cursor()
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function pluck($column, $key = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function implode($column, $glue = '')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function exists()
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function doesntExist()
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function existsOr(Closure $callback)
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function doesntExistOr(Closure $callback)
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function count($columns = '*')
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function min($column)
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function max($column)
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function sum($column)
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function avg($column)
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function average($column)
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function aggregate($function, $columns = ['*'])
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function numericAggregate($function, $columns = ['*'])
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function insert(array $values)
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function insertOrIgnore(array $values)
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function insertGetId(array $values, $sequence = null)
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function insertUsing(array $columns, $query)
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function update(array $values)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function updateOrInsert(array $attributes, array $values = [])
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function upsert(array $values, $uniqueBy, $update = null)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function truncate()
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function newQuery()
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function raw($value)
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function getBindings()
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function getRawBindings()
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function setBindings(array $bindings, $type = 'where')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function addBinding($value, $type = 'where')
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function mergeBindings(Builder $query)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function cleanBindings(array $bindings)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function getConnection()
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function getProcessor()
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function getGrammar()
    {
        return $this->toBase()->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function useWritePdo()
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function clone()
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function cloneWithout(array $properties)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function cloneWithoutBindings(array $except)
    {
        return $this->forwardDecoratedCallTo($this->query, __FUNCTION__, func_get_args());
    }

    /**
     * Handle dynamic method calls to the query builder.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardDecoratedCallTo($this->query, $method, $parameters);
    }
}
