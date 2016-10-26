<?php

namespace Illuminate\Database\Query;

use Closure;
use InvalidArgumentException;

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
     * On clauses can be chained, e.g.
     *
     *  $join->on('contacts.user_id', '=', 'users.id')
     *       ->on('contacts.info_id', '=', 'info.id')
     *
     * will produce the following SQL:
     *
     * on `contacts`.`user_id` = `users`.`id`  and `contacts`.`info_id` = `info`.`id`
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $boolean
     * @param  bool  $where
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function on($first, $operator = null, $second = null, $boolean = 'and', $where = false)
    {
        if ($first instanceof Closure) {
            return $this->nest($first, $boolean);
        }

        if (func_num_args() < 3) {
            throw new InvalidArgumentException('Not enough arguments for the on clause.');
        }

        if ($where) {
            $this->bindings[] = $second;
        }

        if ($where && ($operator === 'in' || $operator === 'not in') && is_array($second)) {
            $second = count($second);
        }

        $nested = false;

        $this->clauses[] = compact('first', 'operator', 'second', 'boolean', 'where', 'nested');

        return $this;
    }

    /**
     * Add an "or on" clause to the join.
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function orOn($first, $operator = null, $second = null)
    {
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Add an "on where" clause to the join.
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function where($first, $operator = null, $second = null, $boolean = 'and')
    {
        return $this->on($first, $operator, $second, $boolean, true);
    }

    /**
     * Add an "or on where" clause to the join.
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function orWhere($first, $operator = null, $second = null)
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

    /**
     * Add a nested where statement to the query.
     *
     * @param  \Closure  $callback
     * @param  string   $boolean
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function nest(Closure $callback, $boolean = 'and')
    {
        $join = new static($this->type, $this->table);

        $callback($join);

        if (count($join->clauses)) {
            $nested = true;

            $this->clauses[] = compact('nested', 'join', 'boolean');
            $this->bindings = array_merge($this->bindings, $join->bindings);
        }

        return $this;
    }
}
