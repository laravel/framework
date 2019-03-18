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
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

trait JoinsRelationships
{
    /**
     * Add a relationship join condition to the query.
     *
     * @param  string  $relation
     * @param  \Closure|null  $callback
     * @param  string  $type
     * @param  boolean  $through
     * @param  \Illuminate\Database\Eloquent\Builder  $relatedQuery
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function joinRelation($relation, Closure $callback = null, $type = 'inner', $through = false, Builder $relatedQuery = null)
    {
        if (strpos($relation, '.') !== false) {
            return $this->joinNestedRelation($relation, $callback, $type, $through);
        }

        if (stripos($relation, ' as ') !== false) {
            [$relation, $alias] = preg_split('/\s+as\s+/i', $relation);
        }

        $relation = ($relatedQuery ?: $this)->getRelationWithoutConstraints($relation);

        if ($relation instanceof MorphTo) {
            throw new RuntimeException('joinRelation() does not support MorphTo relationships.');
        }

        $joinQuery = $relation->getRelationJoinQuery(
            $relation->getRelated()->newQueryWithoutRelationships(), $relatedQuery ?: $this, $type, $alias ?? null
        );

        // If we're simply passing through a relation, then we want to advance the relation
        // without actually applying any joins. Presumably the developer has already used
        // a modified version of this join, and they don't want to do it all over again.
        if($through) {
            return $joinQuery;
        }

        // Next we will call any given callback as an "anonymous" scope so they can get the
        // proper logical grouping of the where clauses if needed by this Eloquent query
        // builder. Then, we will be ready to finalize and return this query instance.
        if ($callback) {
            $joinQuery->callScope($callback);
        }

        $this->addJoinRelationWhere(
            $joinQuery, $relation, $type
        );

        return !is_null($relatedQuery) ? $joinQuery : $this;
    }

    /**
     * Add nested relationship join conditions to the query.
     *
     * @param  string  $relations
     * @param  \Closure|null  $callback
     * @param  string  $type
     * @param  boolean  $through
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    protected function joinNestedRelation($relations, Closure $callback = null, $type = 'inner', $through = false)
    {
        $relations = explode('.', $relations);

        $relatedQuery = $this;

        while(count($relations) > 0) {
            $closure = count($relations) > 1 ? null : $callback;
            $useThrough = count($relations) > 1 && $through;

            $relatedQuery = $this->joinRelation(array_shift($relations), $closure, $type, $useThrough, $relatedQuery);
        }

        return $this;
    }

    /**
     * Add the "join relation" condition where clause to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $joinQuery
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    protected function addJoinRelationWhere(Builder $joinQuery, Relation $relation, $type)
    {
        $joinQuery->mergeConstraintsFrom($relation->getQuery());

        $baseJoinQuery = $joinQuery->toBase();

        if(!empty($baseJoinQuery->joins)) {
            $this->mergeJoins($baseJoinQuery->joins, $baseJoinQuery->bindings['join']);
        }

        return $this->join($baseJoinQuery->from, function($join) use ($baseJoinQuery) {
            if(!empty($baseJoinQuery->wheres)) {
                $join->mergeWheres($baseJoinQuery->wheres, $baseJoinQuery->bindings['where']);
            }
        }, null, null, $type);
    }

    /**
     * Add a relationship left join condition to the query.
     *
     * @param  string  $relation
     * @param  \Closure|null  $callback
     * @param  boolean  $through
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function leftJoinRelation($relation, Closure $callback = null, $through = false)
    {
        return $this->joinRelation($relation, $callback, 'left', $through);
    }

    /**
     * Add a relationship right join condition to the query.
     *
     * @param  string  $relation
     * @param  \Closure|null  $callback
     * @param  boolean  $through
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function rightJoinRelation($relation, Closure $callback = null, $through = false)
    {
        return $this->joinRelation($relation, $callback, 'right', $through);
    }

    /**
     * Add a relationship cross join condition to the query.
     *
     * @param  string  $relation
     * @param  \Closure|null  $callback
     * @param  boolean  $through
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function crossJoinRelation($relation, Closure $callback = null, $through = false)
    {
        return $this->joinRelation($relation, $callback, 'cross', $through);
    }

    /**
     * Add a relationship join condition through a related model to the query.
     *
     * @param  string  $relation
     * @param  \Closure|null  $callback
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function joinThroughRelation($relation, Closure $callback = null, $type = 'inner')
    {
        return $this->joinRelation($relation, $callback, $type, true);
    }

    /**
     * Add a relationship left join condition through a related model to the query.
     *
     * @param  string  $relation
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function leftJoinThroughRelation($relation, Closure $callback = null)
    {
        return $this->joinRelation($relation, $callback, 'left', true);
    }

    /**
     * Add a relationship right join condition through a related model to the query.
     *
     * @param  string  $relation
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function rightJoinThroughRelation($relation, Closure $callback = null)
    {
        return $this->joinRelation($relation, $callback, 'right', true);
    }

    /**
     * Add a relationship cross join condition through a related model to the query.
     *
     * @param  string  $relation
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function crossJoinThroughRelation($relation, Closure $callback = null)
    {
        return $this->joinRelation($relation, $callback, 'cross', true);
    }
}