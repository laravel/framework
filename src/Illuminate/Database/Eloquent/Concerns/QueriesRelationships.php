<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Closure;
use RuntimeException;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

trait QueriesRelationships
{
    /**
     * Add a relationship count / exists condition to the query.
     *
     * @param  string  $relation
     * @param  string  $operator
     * @param  int     $count
     * @param  string  $boolean
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function has($relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null)
    {
        if (strpos($relation, '.') !== false) {
            return $this->hasNested($relation, $operator, $count, $boolean, $callback);
        }

        $relation = $this->getRelationWithoutConstraints($relation);

        if ($relation instanceof MorphTo) {
            throw new RuntimeException('has() and whereHas() do not support MorphTo relationships.');
        }

        // If we only need to check for the existence of the relation, then we can optimize
        // the subquery to only run a "where exists" clause instead of this full "count"
        // clause. This will make these queries run much faster compared with a count.
        $method = $this->canUseExistsForExistenceCheck($operator, $count)
                        ? 'getRelationExistenceQuery'
                        : 'getRelationExistenceCountQuery';

        $hasQuery = $relation->{$method}(
            $relation->getRelated()->newQueryWithoutRelationships(), $this
        );

        // Next we will call any given callback as an "anonymous" scope so they can get the
        // proper logical grouping of the where clauses if needed by this Eloquent query
        // builder. Then, we will be ready to finalize and return this query instance.
        if ($callback) {
            $hasQuery->callScope($callback);
        }

        return $this->addHasWhere(
            $hasQuery, $relation, $operator, $count, $boolean
        );
    }

    /**
     * Add nested relationship count / exists conditions to the query.
     *
     * Sets up recursive call to whereHas until we finish the nested relation.
     *
     * @param  string  $relations
     * @param  string  $operator
     * @param  int     $count
     * @param  string  $boolean
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    protected function hasNested($relations, $operator = '>=', $count = 1, $boolean = 'and', $callback = null)
    {
        $relations = explode('.', $relations);

        $doesntHave = $operator === '<' && $count === 1;

        if ($doesntHave) {
            $operator = '>=';
            $count = 1;
        }

        $closure = function ($q) use (&$closure, &$relations, $operator, $count, $callback) {
            // In order to nest "has", we need to add count relation constraints on the
            // callback Closure. We'll do this by simply passing the Closure its own
            // reference to itself so it calls itself recursively on each segment.
            count($relations) > 1
                ? $q->whereHas(array_shift($relations), $closure)
                : $q->has(array_shift($relations), $operator, $count, 'and', $callback);
        };

        return $this->has(array_shift($relations), $doesntHave ? '<' : '>=', 1, $boolean, $closure);
    }

    /**
     * Add a relationship count / exists condition to the query with an "or".
     *
     * @param  string  $relation
     * @param  string  $operator
     * @param  int     $count
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function orHas($relation, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'or');
    }

    /**
     * Add a relationship count / exists condition to the query.
     *
     * @param  string  $relation
     * @param  string  $boolean
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function doesntHave($relation, $boolean = 'and', Closure $callback = null)
    {
        return $this->has($relation, '<', 1, $boolean, $callback);
    }

    /**
     * Add a relationship count / exists condition to the query with an "or".
     *
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function orDoesntHave($relation)
    {
        return $this->doesntHave($relation, 'or');
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  string  $relation
     * @param  \Closure|null  $callback
     * @param  string  $operator
     * @param  int     $count
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function whereHas($relation, Closure $callback = null, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'and', $callback);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  string    $relation
     * @param  \Closure  $callback
     * @param  string    $operator
     * @param  int       $count
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function orWhereHas($relation, Closure $callback = null, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'or', $callback);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  string  $relation
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function whereDoesntHave($relation, Closure $callback = null)
    {
        return $this->doesntHave($relation, 'and', $callback);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  string    $relation
     * @param  \Closure  $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function orWhereDoesntHave($relation, Closure $callback = null)
    {
        return $this->doesntHave($relation, 'or', $callback);
    }

    /**
     * Add subselect queries to count the relations.
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function withCount(...$relations)
    {
        return $this->withAggregate('count', ...$relations);
    }

    /**
     * Add subselect queries to retrieve the minimum value of a column on the relations.
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function withMin(...$relations)
    {
        return $this->withAggregate('min', ...$relations);
    }

    /**
     * Add subselect queries to retrieve the maximum value of a column on the relations.
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function withMax(...$relations)
    {
        return $this->withAggregate('max', ...$relations);
    }

    /**
     * Add subselect queries to retrieve the sum the value of a column on the relations.
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function withSum(...$relations)
    {
        return $this->withAggregate('sum', ...$relations);
    }

    /**
     * Add subselect queries to retrieve the average the value of a column on the relations.
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function withAvg(...$relations)
    {
        return $this->withAggregate('avg', ...$relations);
    }

    /**
     * Alias for the "withAvg" method.
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function withAverage(...$relations)
    {
        return $this->withAvg(...$relations);
    }

    /**
     * Add subselect queries to retrieve the aggregate function of a column on the relations.
     *
     * @param  string $function
     * @param  mixed  $relations
     * @return $this
     */
    public function withAggregate($function, $relations)
    {
        if (empty($relations)) {
            return $this;
        }

        if (is_null($this->query->columns)) {
            $this->query->select([$this->query->from.'.*']);
        }

        $relations = is_array($relations) ? $relations : array_slice(func_get_args(), 1);

        foreach ($this->parseWithRelations($relations, false) as $name => $constraints) {
            // First we will determine if the name has been aliased using an "as" clause on the name
            // and if it has we will extract the actual relationship name and the desired name of
            // the resulting column. This allows multiple aggregates for the same relationship.
            $segments = explode(' ', $name);

            unset($alias);

            if (count($segments) === 3 && Str::lower($segments[1]) === 'as') {
                [$name, $alias] = [$segments[0], $segments[2]];
            }

            // Determine column to do the aggregate function
            $segments = explode(':', $name);

            unset($aggregateColumn);

            if (count($segments) === 2) {
                [$name, $aggregateColumn] = $segments;
            }

            $relation = $this->getRelationWithoutConstraints($name);

            // Here we will get the relationship aggregate query and prepare to add it to the main query
             // as a sub-select. First we'll get the "has" query and use that to retrieve the relation
            // aggregate query. We normalize the relation name, and append _$column_$aggregateName.
            $query = $relation->getRelationExistenceAggregateQuery(
                $relation->getRelated()->newQuery(), $this, $function, $aggregateColumn ?? '*'
            );

            $query->callScope($constraints);

            $query = $query->mergeConstraintsFrom($relation->getQuery())->toBase();

            if (count($query->columns) > 1) {
                $query->columns = [$query->columns[0]];
            }

            // Finally we will add the proper result column alias to the query and run the subselect
            // statement against the query builder. Then we will return the builder instance back
            // to the developer for further constraint chaining that needs to take place on it.
            $suffix = isset($aggregateColumn) ? '_'.$aggregateColumn : null;
            $column = $alias ?? Str::snake($name.$suffix.'_'.$function);

            $this->selectSub($query, $column);
        }

        return $this;
    }

    /**
     * Apply a set of aggregate functions of related model.
     *
     * @param  array  $aggregates
     * @return $this
     */
    public function withAggregates($aggregates)
    {
        foreach ($aggregates as $function => $columns) {
            foreach ((array) $columns as $column) {
                $this->withAggregate($function, $column);
            }
        }

        return $this;
    }

    /**
     * Add the "has" condition where clause to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $hasQuery
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    protected function addHasWhere(Builder $hasQuery, Relation $relation, $operator, $count, $boolean)
    {
        $hasQuery->mergeConstraintsFrom($relation->getQuery());

        return $this->canUseExistsForExistenceCheck($operator, $count)
                ? $this->addWhereExistsQuery($hasQuery->toBase(), $boolean, $operator === '<' && $count === 1)
                : $this->addWhereCountQuery($hasQuery->toBase(), $operator, $count, $boolean);
    }

    /**
     * Merge the where constraints from another query to the current query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $from
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function mergeConstraintsFrom(Builder $from)
    {
        $whereBindings = $from->getQuery()->getRawBindings()['where'] ?? [];

        // Here we have some other query that we want to merge the where constraints from. We will
        // copy over any where constraints on the query as well as remove any global scopes the
        // query might have removed. Then we will return ourselves with the finished merging.
        return $this->withoutGlobalScopes(
            $from->removedScopes()
        )->mergeWheres(
            $from->getQuery()->wheres, $whereBindings
        );
    }

    /**
     * Add a sub-query count clause to this query.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @return $this
     */
    protected function addWhereCountQuery(QueryBuilder $query, $operator = '>=', $count = 1, $boolean = 'and')
    {
        $this->query->addBinding($query->getBindings(), 'where');

        return $this->where(
            new Expression('('.$query->toSql().')'),
            $operator,
            is_numeric($count) ? new Expression($count) : $count,
            $boolean
        );
    }

    /**
     * Get the "has relation" base query instance.
     *
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    protected function getRelationWithoutConstraints($relation)
    {
        return Relation::noConstraints(function () use ($relation) {
            return $this->getModel()->{$relation}();
        });
    }

    /**
     * Check if we can run an "exists" query to optimize performance.
     *
     * @param  string  $operator
     * @param  int  $count
     * @return bool
     */
    protected function canUseExistsForExistenceCheck($operator, $count)
    {
        return ($operator === '>=' || $operator === '<') && $count === 1;
    }
}
