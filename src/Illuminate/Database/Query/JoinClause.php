<?php

namespace Illuminate\Database\Query;

class JoinClause
{
    /**
     * The type of join being performed.
     *
     * @var string
     */
    public $type;

    /**
     * The table the join clause is joining to.
     *
     * @var string
     */
    public $table;

    /**
     * The "on" clauses for the join.
     *
     * @var array
     */
    public $clauses = [];

    /**
     * The "on" bindings for the join.
     *
     * @var array
     */
    public $bindings = [];

    /**
     * Create a new join clause instance.
     *
     * @param  string  $type
     * @param  string  $table
     * @return void
     */
    public function __construct($type, $table)
    {
        $this->type = $type;
        $this->table = $table;
    }

    /**
     * Add an "on" clause to the join.
     *
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @param  string  $boolean
     * @param  bool  $where
     * @return $this
     */
    public function on($first, $operator, $second, $boolean = 'and', $where = false)
    {
        if ($where) {
            $this->bindings[] = $second;
        }

        if ($where && ($operator === 'in' || $operator === 'not in') && is_array($second)) {
            $second = count($second);
        }

        $this->clauses[] = compact('first', 'operator', 'second', 'boolean', 'where');

        return $this;
    }

    /**
     * Add an "or on" clause to the join.
     *
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function orOn($first, $operator, $second)
    {
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Add an "on where" clause to the join.
     *
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function where($first, $operator, $second, $boolean = 'and')
    {
        return $this->on($first, $operator, $second, $boolean, true);
    }

    /**
     * Add an "or on where" clause to the join.
     *
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function orWhere($first, $operator, $second)
    {
        return $this->on($first, $operator, $second, 'or', true);
    }

    /**
     * Add an "on where is null" clause to the join.
     *
     * @param  string  $column
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function whereNull($column, $boolean = 'and')
    {
        return $this->on($column, 'is', new Expression('null'), $boolean, false);
    }

    /**
     * Add an "or on where is null" clause to the join.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function orWhereNull($column)
    {
        return $this->whereNull($column, 'or');
    }

    /**
     * Add an "on where is not null" clause to the join.
     *
     * @param  string  $column
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function whereNotNull($column, $boolean = 'and')
    {
        return $this->on($column, 'is', new Expression('not null'), $boolean, false);
    }

    /**
     * Add an "or on where is not null" clause to the join.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function orWhereNotNull($column)
    {
        return $this->whereNotNull($column, 'or');
    }

    /**
     * Add an "on where in (...)" clause to the join.
     *
     * @param  string  $column
     * @param  array  $values
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function whereIn($column, array $values)
    {
        return $this->on($column, 'in', $values, 'and', true);
    }

    /**
     * Add an "on where not in (...)" clause to the join.
     *
     * @param  string  $column
     * @param  array  $values
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function whereNotIn($column, array $values)
    {
        return $this->on($column, 'not in', $values, 'and', true);
    }

    /**
     * Add an "or on where in (...)" clause to the join.
     *
     * @param  string  $column
     * @param  array  $values
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function orWhereIn($column, array $values)
    {
        return $this->on($column, 'in', $values, 'or', true);
    }

    /**
     * Add an "or on where not in (...)" clause to the join.
     *
     * @param  string  $column
     * @param  array  $values
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function orWhereNotIn($column, array $values)
    {
        return $this->on($column, 'not in', $values, 'or', true);
    }
}
