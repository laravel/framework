<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait CanBeOneOfMany
{
    /**
     * Determines whether the relationship is one-of-many.
     *
     * @var bool
     */
    protected $isOneOfMany = false;

    /**
     * The name of the relationship.
     *
     * @var string
     */
    protected $relationName;

    /**
     * Add join sub constraints.
     *
     * @param JoinClause $join
     * @return void
     */
    abstract public function addJoinSubConstraints(JoinClause $join);

    /**
     * Get the columns the determine the relationship groups.
     *
     * @return array|string
     */
    abstract public function getGroups();

    /**
     * Add constraints for inner join subselect.
     *
     * @param Builder $query
     * @param string|null $column
     * @param string|null $aggregate
     * @return void
     */
    abstract public function addSubQueryConstraints(Builder $query, $column = null, $aggregate = null);

    /**
     * Indicate that the relation is a single result of a larger one-to-many relationship.
     *
     * @param  string|array|null  $column
     * @param  string|Closure|null  $aggregate
     * @param  string|null  $relation
     * @return $this
     */
    public function ofMany($column = 'id', $aggregate = 'MAX', $relation = null)
    {
        $this->isOneOfMany = true;

        $this->relationName = $relation ?: $this->guessRelationship();

        $keyName = $this->query->getModel()->getKeyName();

        $columns = is_string($columns = $column) ? [
            $column => $aggregate,
            $keyName => $aggregate,
        ] : $column;

        if ($aggregate instanceof Closure) {
            $closure = $aggregate;
        }

        foreach ($columns as $column => $aggregate) {
            $subQuery = $this->newSubQuery(
                isset($previous) ? $previous['column'] : $this->getGroups(),
                $column, $aggregate
            );

            if (isset($previous)) {
                $this->addJoinSub($subQuery, $previous['subQuery'], $previous['column']);
            } elseif (isset($closure)) {
                $closure($subQuery);
            }

            if (array_key_last($columns) == $column) {
                $this->addJoinSub($this->query, $subQuery, $column);
            }

            $previous = [
                'subQuery' => $subQuery,
                'column' => $column,
            ];
        }

        return $this;
    }

    /**
     * Get a new query for the related model, grouping the query by the given column, often the foreign key of the relationship.
     *
     * @param  string|array  $groupBy
     * @param  string|null  $column
     * @param  string|null  $aggregate
     * @return void
     */
    protected function newSubQuery($groupBy, $column = null, $aggregate = null)
    {
        $subQuery = $this->query->getModel()
            ->newQuery();

        foreach (Arr::wrap($groupBy) as $group) {
            $subQuery->groupBy($this->qualifyRelatedColumn($group));
        }

        if (! is_null($column)) {
            $subQuery->selectRaw($aggregate.'('.$column.') as '.$column);
        }

        $this->addSubQueryConstraints($subQuery, $groupBy, $column, $aggregate);

        return $subQuery;
    }

    /**
     * Add the join subquery to the given query on the given column and the relationship's foreign key.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $parent
     * @param  \Illuminate\Database\Eloquent\Builder  $subQuery
     * @param  string  $on
     * @return void
     */
    protected function addJoinSub(Builder $parent, Builder $subQuery, $on)
    {
        $parent->joinSub($subQuery, $this->relationName, function ($join) use ($on) {
            $join->on($this->qualifySubSelectColumn($on), '=', $this->qualifyRelatedColumn($on));

            $this->addJoinSubConstraints($join, $on);
        });
    }

    /**
     * Get the qualified column name for the one-of-many relationship using the subselect join query's alias.
     *
     * @param  string  $column
     * @return string
     */
    public function qualifySubSelectColumn($column)
    {
        return $this->getRelationName().'.'.last(explode('.', $column));
    }

    /**
     * Qualify related column using the related table name if it is not already qualified.
     *
     * @param  string  $column
     * @return string
     */
    protected function qualifyRelatedColumn($column)
    {
        return Str::contains($column, '.') ? $column : $this->query->getModel()->getTable().'.'.$column;
    }

    /**
     * Guess the "hasOne" relationship's name via backtrace.
     *
     * @return string
     */
    protected function guessRelationship()
    {
        return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]['function'];
    }

    /**
     * Determine whether the relationship is a one-of-many relationship.
     *
     * @return bool
     */
    public function isOneOfMany()
    {
        return $this->isOneOfMany;
    }

    /**
     * Get the name of the relationship.
     *
     * @return string
     */
    public function getRelationName()
    {
        return $this->relationName;
    }
}
