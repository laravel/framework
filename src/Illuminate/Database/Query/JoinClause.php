<?php

namespace Illuminate\Database\Query;

use Closure;

class JoinClause extends Builder
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
     * The parent query builder instance.
     *
     * @var \Illuminate\Database\Query\Builder
     */
    private $parentQuery;

    /**
     * Create a new join clause instance.
     *
     * @param  \Illuminate\Database\Query\Builder $parentQuery
     * @param  string  $type
     * @param  string  $table
     * @return void
     */
    public function __construct(Builder $parentQuery, $type, $table)
    {
        $this->type = $type;
        $this->table = $table;
        $this->parentQuery = $parentQuery;

        parent::__construct(
            $parentQuery->getConnection(), $parentQuery->getGrammar(), $parentQuery->getProcessor()
        );
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
     * @param  \Closure|string|null $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function on($first = null, $operator = null, $second = null, $boolean = 'and')
    {
        if ($first instanceof Closure) {
            return $this->whereNested($first, $boolean);
        }

        // If no columns are provided, infer them based on these assumptions:
        // - the primary key of this table is 'id' => 'users.id'
        // - the foreign key is named singular like this table with '_id' appended => 'user_id'
        if (! $first && ! $second) {
            $first = $this->table.'.id';

            $operator = '=';

            // Be pragmatic and only strip of the 's'. If that's not enough
            // you've to explicitly provide the information.
            $second = $this->parentQuery->from.'.'.rtrim($this->table, 's').'_id';
        }

        return $this->whereColumn($first, $operator, $second, $boolean);
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
     * Get a new instance of the join clause builder.
     *
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function newQuery()
    {
        return new static($this->parentQuery, $this->type, $this->table);
    }

    /**
     * Create a new query instance for sub-query.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function forSubQuery()
    {
        return $this->parentQuery->newQuery();
    }
}
