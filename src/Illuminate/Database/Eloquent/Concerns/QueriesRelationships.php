<?php

namespace Illuminate\Database\Eloquent\Concerns;

use BadMethodCallException;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

use function Illuminate\Support\enum_value;

/** @mixin \Illuminate\Database\Eloquent\Builder */
trait QueriesRelationships
{
    /**
     * Add a relationship count / exists condition to the query.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<TRelatedModel, *, *>|string  $relation
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
     * @param  string  $boolean
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|null  $callback
     * @return $this
     *
     * @throws \RuntimeException
     */
    public function has($relation, $operator = '>=', $count = 1, $boolean = 'and', ?Closure $callback = null)
    {
        if (is_string($relation)) {
            if (str_contains($relation, '.')) {
                return $this->hasNested($relation, $operator, $count, $boolean, $callback);
            }

            $relation = $this->getRelationWithoutConstraints($relation);
        }

        if ($relation instanceof MorphTo) {
            return $this->hasMorph($relation, ['*'], $operator, $count, $boolean, $callback);
        }

        $strategy = $this->relationCrossConnectionStrategy($relation);

        if ($strategy === 'resolve') {
            return $this->addCrossConnectionHasWhere(
                $relation, $operator, $count, $boolean, $callback
            );
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

        if ($strategy === 'prefix') {
            $this->applyCrossDatabasePrefixToHasQuery($hasQuery, $relation);
        }

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
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
     * @param  string  $boolean
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<*>): mixed)|null  $callback
     * @return $this
     */
    protected function hasNested($relations, $operator = '>=', $count = 1, $boolean = 'and', $callback = null)
    {
        $relations = explode('.', $relations);

        $initialRelations = [...$relations];

        $doesntHave = $operator === '<' && $count === 1;

        if ($doesntHave) {
            $operator = '>=';
            $count = 1;
        }

        $closure = function ($q) use (&$closure, &$relations, $operator, $count, $callback, $initialRelations) {
            // If the same closure is called multiple times, reset the relation array to loop through them again...
            if ($count === 1 && empty($relations)) {
                $relations = [...$initialRelations];

                array_shift($relations);
            }

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
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<*, *, *>|string  $relation
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
     * @return $this
     */
    public function orHas($relation, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'or');
    }

    /**
     * Add a relationship count / exists condition to the query.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<TRelatedModel, *, *>|string  $relation
     * @param  string  $boolean
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|null  $callback
     * @return $this
     */
    public function doesntHave($relation, $boolean = 'and', ?Closure $callback = null)
    {
        return $this->has($relation, '<', 1, $boolean, $callback);
    }

    /**
     * Add a relationship count / exists condition to the query with an "or".
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<*, *, *>|string  $relation
     * @return $this
     */
    public function orDoesntHave($relation)
    {
        return $this->doesntHave($relation, 'or');
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<TRelatedModel, *, *>|string  $relation
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|null  $callback
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
     * @return $this
     */
    public function whereHas($relation, ?Closure $callback = null, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'and', $callback);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * Also load the relationship with the same condition.
     *
     * @param  string  $relation
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<*>|\Illuminate\Database\Eloquent\Relations\Relation<*, *, *>): mixed)|null  $callback
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
     * @return $this
     */
    public function withWhereHas($relation, ?Closure $callback = null, $operator = '>=', $count = 1)
    {
        return $this->whereHas(Str::before($relation, ':'), $callback, $operator, $count)
            ->with($callback ? [$relation => fn ($query) => $callback($query)] : $relation);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<TRelatedModel, *, *>|string  $relation
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|null  $callback
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
     * @return $this
     */
    public function orWhereHas($relation, ?Closure $callback = null, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'or', $callback);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<TRelatedModel, *, *>|string  $relation
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|null  $callback
     * @return $this
     */
    public function whereDoesntHave($relation, ?Closure $callback = null)
    {
        return $this->doesntHave($relation, 'and', $callback);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<TRelatedModel, *, *>|string  $relation
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|null  $callback
     * @return $this
     */
    public function orWhereDoesntHave($relation, ?Closure $callback = null)
    {
        return $this->doesntHave($relation, 'or', $callback);
    }

    /**
     * Add a polymorphic relationship count / exists condition to the query.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<TRelatedModel, *>|string  $relation
     * @param  string|array<int, string>  $types
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
     * @param  string  $boolean
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>, string): mixed)|null  $callback
     * @return $this
     */
    public function hasMorph($relation, $types, $operator = '>=', $count = 1, $boolean = 'and', ?Closure $callback = null)
    {
        if (is_string($relation)) {
            $relation = $this->getRelationWithoutConstraints($relation);
        }

        $types = (array) $types;

        $checkMorphNull = $types === ['*']
            && (($operator === '<' && $count >= 1)
                || ($operator === '<=' && $count >= 0)
                || ($operator === '=' && $count === 0)
                || ($operator === '!=' && $count >= 1));

        if ($types === ['*']) {
            $types = $this->model->newModelQuery()->distinct()->pluck($relation->getMorphType())
                ->filter()
                ->map(fn ($item) => enum_value($item))
                ->all();
        }

        if (empty($types)) {
            return $this->where(new Expression('0'), $operator, $count, $boolean);
        }

        foreach ($types as &$type) {
            $type = Relation::getMorphedModel($type) ?? $type;
        }

        return $this->where(function ($query) use ($relation, $callback, $operator, $count, $types, $checkMorphNull) {
            foreach ($types as $type) {
                $query->orWhere(function ($query) use ($relation, $callback, $operator, $count, $type) {
                    $belongsTo = $this->getBelongsToRelation($relation, $type);

                    if ($callback) {
                        $callback = function ($query) use ($callback, $type) {
                            return $callback($query, $type);
                        };
                    }

                    $query->where($this->qualifyColumn($relation->getMorphType()), '=', (new $type)->getMorphClass())
                        ->whereHas($belongsTo, $callback, $operator, $count);
                });
            }

            $query->when($checkMorphNull, fn (self $query) => $query->orWhereMorphedTo($relation, null));
        }, null, null, $boolean);
    }

    /**
     * Get the BelongsTo relationship for a single polymorphic type.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<*, TDeclaringModel>  $relation
     * @param  class-string<TRelatedModel>  $type
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<TRelatedModel, TDeclaringModel>
     */
    protected function getBelongsToRelation(MorphTo $relation, $type)
    {
        $belongsTo = Relation::noConstraints(function () use ($relation, $type) {
            return $this->model->belongsTo(
                $type,
                $relation->getForeignKeyName(),
                $relation->getOwnerKeyName()
            );
        });

        $belongsTo->getQuery()->mergeConstraintsFrom($relation->getQuery());

        return $belongsTo;
    }

    /**
     * Add a polymorphic relationship count / exists condition to the query with an "or".
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<*, *>|string  $relation
     * @param  string|array<int, string>  $types
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
     * @return $this
     */
    public function orHasMorph($relation, $types, $operator = '>=', $count = 1)
    {
        return $this->hasMorph($relation, $types, $operator, $count, 'or');
    }

    /**
     * Add a polymorphic relationship count / exists condition to the query.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<TRelatedModel, *>|string  $relation
     * @param  string|array<int, string>  $types
     * @param  string  $boolean
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>, string): mixed)|null  $callback
     * @return $this
     */
    public function doesntHaveMorph($relation, $types, $boolean = 'and', ?Closure $callback = null)
    {
        return $this->hasMorph($relation, $types, '<', 1, $boolean, $callback);
    }

    /**
     * Add a polymorphic relationship count / exists condition to the query with an "or".
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<*, *>|string  $relation
     * @param  string|array<int, string>  $types
     * @return $this
     */
    public function orDoesntHaveMorph($relation, $types)
    {
        return $this->doesntHaveMorph($relation, $types, 'or');
    }

    /**
     * Add a polymorphic relationship count / exists condition to the query with where clauses.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<TRelatedModel, *>|string  $relation
     * @param  string|array<int, string>  $types
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>, string): mixed)|null  $callback
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
     * @return $this
     */
    public function whereHasMorph($relation, $types, ?Closure $callback = null, $operator = '>=', $count = 1)
    {
        return $this->hasMorph($relation, $types, $operator, $count, 'and', $callback);
    }

    /**
     * Add a polymorphic relationship count / exists condition to the query with where clauses and an "or".
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<TRelatedModel, *>|string  $relation
     * @param  string|array<int, string>  $types
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>, string): mixed)|null  $callback
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
     * @return $this
     */
    public function orWhereHasMorph($relation, $types, ?Closure $callback = null, $operator = '>=', $count = 1)
    {
        return $this->hasMorph($relation, $types, $operator, $count, 'or', $callback);
    }

    /**
     * Add a polymorphic relationship count / exists condition to the query with where clauses.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<TRelatedModel, *>|string  $relation
     * @param  string|array<int, string>  $types
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>, string): mixed)|null  $callback
     * @return $this
     */
    public function whereDoesntHaveMorph($relation, $types, ?Closure $callback = null)
    {
        return $this->doesntHaveMorph($relation, $types, 'and', $callback);
    }

    /**
     * Add a polymorphic relationship count / exists condition to the query with where clauses and an "or".
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<TRelatedModel, *>|string  $relation
     * @param  string|array<int, string>  $types
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>, string): mixed)|null  $callback
     * @return $this
     */
    public function orWhereDoesntHaveMorph($relation, $types, ?Closure $callback = null)
    {
        return $this->doesntHaveMorph($relation, $types, 'or', $callback);
    }

    /**
     * Add a basic where clause to a relationship query.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<TRelatedModel, *, *>|string  $relation
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|string|array|\Illuminate\Contracts\Database\Query\Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function whereRelation($relation, $column, $operator = null, $value = null)
    {
        return $this->whereHas($relation, function ($query) use ($column, $operator, $value) {
            if ($column instanceof Closure) {
                $column($query);
            } else {
                $query->where($column, $operator, $value);
            }
        });
    }

    /**
     * Add a basic where clause to a relationship query and eager-load the relationship with the same conditions.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<*, *, *>|string  $relation
     * @param  \Closure|string|array|\Illuminate\Contracts\Database\Query\Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function withWhereRelation($relation, $column, $operator = null, $value = null)
    {
        return $this->whereRelation($relation, $column, $operator, $value)
            ->with([
                $relation => fn ($query) => $column instanceof Closure
                    ? $column($query)
                    : $query->where($column, $operator, $value),
            ]);
    }

    /**
     * Add an "or where" clause to a relationship query.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<TRelatedModel, *, *>|string  $relation
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|string|array|\Illuminate\Contracts\Database\Query\Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function orWhereRelation($relation, $column, $operator = null, $value = null)
    {
        return $this->orWhereHas($relation, function ($query) use ($column, $operator, $value) {
            if ($column instanceof Closure) {
                $column($query);
            } else {
                $query->where($column, $operator, $value);
            }
        });
    }

    /**
     * Add a basic count / exists condition to a relationship query.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<TRelatedModel, *, *>|string  $relation
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|string|array|\Illuminate\Contracts\Database\Query\Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function whereDoesntHaveRelation($relation, $column, $operator = null, $value = null)
    {
        return $this->whereDoesntHave($relation, function ($query) use ($column, $operator, $value) {
            if ($column instanceof Closure) {
                $column($query);
            } else {
                $query->where($column, $operator, $value);
            }
        });
    }

    /**
     * Add an "or where" clause to a relationship query.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<TRelatedModel, *, *>|string  $relation
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|string|array|\Illuminate\Contracts\Database\Query\Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function orWhereDoesntHaveRelation($relation, $column, $operator = null, $value = null)
    {
        return $this->orWhereDoesntHave($relation, function ($query) use ($column, $operator, $value) {
            if ($column instanceof Closure) {
                $column($query);
            } else {
                $query->where($column, $operator, $value);
            }
        });
    }

    /**
     * Add a polymorphic relationship condition to the query with a where clause.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<TRelatedModel, *>|string  $relation
     * @param  string|array<int, string>  $types
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|string|array|\Illuminate\Contracts\Database\Query\Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function whereMorphRelation($relation, $types, $column, $operator = null, $value = null)
    {
        return $this->whereHasMorph($relation, $types, function ($query) use ($column, $operator, $value) {
            $query->where($column, $operator, $value);
        });
    }

    /**
     * Add a polymorphic relationship condition to the query with an "or where" clause.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<TRelatedModel, *>|string  $relation
     * @param  string|array<int, string>  $types
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|string|array|\Illuminate\Contracts\Database\Query\Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function orWhereMorphRelation($relation, $types, $column, $operator = null, $value = null)
    {
        return $this->orWhereHasMorph($relation, $types, function ($query) use ($column, $operator, $value) {
            $query->where($column, $operator, $value);
        });
    }

    /**
     * Add a polymorphic relationship condition to the query with a doesn't have clause.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<TRelatedModel, *>|string  $relation
     * @param  string|array<int, string>  $types
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|string|array|\Illuminate\Contracts\Database\Query\Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function whereMorphDoesntHaveRelation($relation, $types, $column, $operator = null, $value = null)
    {
        return $this->whereDoesntHaveMorph($relation, $types, function ($query) use ($column, $operator, $value) {
            $query->where($column, $operator, $value);
        });
    }

    /**
     * Add a polymorphic relationship condition to the query with an "or doesn't have" clause.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<TRelatedModel, *>|string  $relation
     * @param  string|array<int, string>  $types
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TRelatedModel>): mixed)|string|array|\Illuminate\Contracts\Database\Query\Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function orWhereMorphDoesntHaveRelation($relation, $types, $column, $operator = null, $value = null)
    {
        return $this->orWhereDoesntHaveMorph($relation, $types, function ($query) use ($column, $operator, $value) {
            $query->where($column, $operator, $value);
        });
    }

    /**
     * Add a morph-to relationship condition to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<*, *>|string  $relation
     * @param  \Illuminate\Database\Eloquent\Model|iterable<int, \Illuminate\Database\Eloquent\Model>|string|null  $model
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function whereMorphedTo($relation, $model, $boolean = 'and')
    {
        if (is_string($relation)) {
            $relation = $this->getRelationWithoutConstraints($relation);
        }

        if (is_null($model)) {
            return $this->whereNull($relation->qualifyColumn($relation->getMorphType()), $boolean);
        }

        if (is_string($model)) {
            $morphMap = Relation::morphMap();

            if (! empty($morphMap) && in_array($model, $morphMap)) {
                $model = array_search($model, $morphMap, true);
            }

            return $this->where($relation->qualifyColumn($relation->getMorphType()), $model, null, $boolean);
        }

        $models = BaseCollection::wrap($model);

        if ($models->isEmpty()) {
            throw new InvalidArgumentException('Collection given to whereMorphedTo method may not be empty.');
        }

        return $this->where(function ($query) use ($relation, $models) {
            $models->groupBy(fn ($model) => $model->getMorphClass())->each(function ($models) use ($query, $relation) {
                $query->orWhere(function ($query) use ($relation, $models) {
                    $query->where($relation->qualifyColumn($relation->getMorphType()), $models->first()->getMorphClass())
                        ->whereIn($relation->qualifyColumn($relation->getForeignKeyName()), $models->map->getKey());
                });
            });
        }, null, null, $boolean);
    }

    /**
     * Add a not morph-to relationship condition to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<*, *>|string  $relation
     * @param  \Illuminate\Database\Eloquent\Model|iterable<int, \Illuminate\Database\Eloquent\Model>|string  $model
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function whereNotMorphedTo($relation, $model, $boolean = 'and')
    {
        if (is_string($relation)) {
            $relation = $this->getRelationWithoutConstraints($relation);
        }

        if (is_string($model)) {
            $morphMap = Relation::morphMap();

            if (! empty($morphMap) && in_array($model, $morphMap)) {
                $model = array_search($model, $morphMap, true);
            }

            return $this->whereNot(fn ($query) => $query->whereNullSafeEquals(
                $relation->qualifyColumn($relation->getMorphType()), $model
            ), null, null, $boolean);
        }

        $models = BaseCollection::wrap($model);

        if ($models->isEmpty()) {
            throw new InvalidArgumentException('Collection given to whereNotMorphedTo method may not be empty.');
        }

        return $this->whereNot(function ($query) use ($relation, $models) {
            $models->groupBy(fn ($model) => $model->getMorphClass())->each(function ($models) use ($query, $relation) {
                $query->orWhere(function ($query) use ($relation, $models) {
                    $query->whereNullSafeEquals($relation->qualifyColumn($relation->getMorphType()), $models->first()->getMorphClass())
                        ->whereIn($relation->qualifyColumn($relation->getForeignKeyName()), $models->map->getKey());
                });
            });
        }, null, null, $boolean);
    }

    /**
     * Add a morph-to relationship condition to the query with an "or where" clause.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<*, *>|string  $relation
     * @param  \Illuminate\Database\Eloquent\Model|iterable<int, \Illuminate\Database\Eloquent\Model>|string|null  $model
     * @return $this
     */
    public function orWhereMorphedTo($relation, $model)
    {
        return $this->whereMorphedTo($relation, $model, 'or');
    }

    /**
     * Add a not morph-to relationship condition to the query with an "or where" clause.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\MorphTo<*, *>|string  $relation
     * @param  \Illuminate\Database\Eloquent\Model|iterable<int, \Illuminate\Database\Eloquent\Model>|string  $model
     * @return $this
     */
    public function orWhereNotMorphedTo($relation, $model)
    {
        return $this->whereNotMorphedTo($relation, $model, 'or');
    }

    /**
     * Add a "belongs to" relationship where clause to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>  $related
     * @param  string|null  $relationshipName
     * @param  string  $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\RelationNotFoundException
     */
    public function whereBelongsTo($related, $relationshipName = null, $boolean = 'and')
    {
        if (! $related instanceof EloquentCollection) {
            $relatedCollection = $related->newCollection([$related]);
        } else {
            $relatedCollection = $related;

            $related = $relatedCollection->first();
        }

        if ($relatedCollection->isEmpty()) {
            throw new InvalidArgumentException('Collection given to whereBelongsTo method may not be empty.');
        }

        if ($relationshipName === null) {
            $relationshipName = Str::camel(class_basename($related));
        }

        try {
            $relationship = $this->model->{$relationshipName}();
        } catch (BadMethodCallException) {
            throw RelationNotFoundException::make($this->model, $relationshipName);
        }

        if (! $relationship instanceof BelongsTo) {
            throw RelationNotFoundException::make($this->model, $relationshipName, BelongsTo::class);
        }

        $this->whereIn(
            $relationship->getQualifiedForeignKeyName(),
            $relatedCollection->pluck($relationship->getOwnerKeyName())->toArray(),
            $boolean,
        );

        return $this;
    }

    /**
     * Add a "BelongsTo" relationship with an "or where" clause to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $related
     * @param  string|null  $relationshipName
     * @return $this
     */
    public function orWhereBelongsTo($related, $relationshipName = null)
    {
        return $this->whereBelongsTo($related, $relationshipName, 'or');
    }

    /**
     * Add a "belongs to many" relationship where clause to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>  $related
     * @param  string|null  $relationshipName
     * @param  string  $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\RelationNotFoundException
     */
    public function whereAttachedTo($related, $relationshipName = null, $boolean = 'and')
    {
        $relatedCollection = $related instanceof EloquentCollection ? $related : $related->newCollection([$related]);

        $related = $relatedCollection->first();

        if ($relatedCollection->isEmpty()) {
            throw new InvalidArgumentException('Collection given to whereAttachedTo method may not be empty.');
        }

        if ($relationshipName === null) {
            $relationshipName = Str::plural(Str::camel(class_basename($related)));
        }

        try {
            $relationship = $this->model->{$relationshipName}();
        } catch (BadMethodCallException) {
            throw RelationNotFoundException::make($this->model, $relationshipName);
        }

        if (! $relationship instanceof BelongsToMany) {
            throw RelationNotFoundException::make($this->model, $relationshipName, BelongsToMany::class);
        }

        $this->has(
            $relationshipName,
            boolean: $boolean,
            callback: fn (Builder $query) => $query->whereKey($relatedCollection->pluck($related->getKeyName())),
        );

        return $this;
    }

    /**
     * Add a "belongs to many" relationship with an "or where" clause to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $related
     * @param  string|null  $relationshipName
     * @return $this
     *
     * @throws \RuntimeException
     */
    public function orWhereAttachedTo($related, $relationshipName = null)
    {
        return $this->whereAttachedTo($related, $relationshipName, 'or');
    }

    /**
     * Add subselect queries to include an aggregate value for a relationship.
     *
     * @param  mixed  $relations
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
     * @param  string|null  $function
     * @return $this
     */
    public function withAggregate($relations, $column, $function = null)
    {
        if (empty($relations)) {
            return $this;
        }

        if (is_null($this->query->columns)) {
            $this->query->select([$this->query->from.'.*']);
        }

        $relations = is_array($relations) ? $relations : [$relations];

        foreach ($this->parseWithRelations($relations) as $name => $constraints) {
            // First we will determine if the name has been aliased using an "as" clause on the name
            // and if it has we will extract the actual relationship name and the desired name of
            // the resulting column. This allows multiple aggregates on the same relationships.
            $segments = explode(' ', $name);

            unset($alias);

            if (count($segments) === 3 && Str::lower($segments[1]) === 'as') {
                [$name, $alias] = [$segments[0], $segments[2]];
            }

            $relation = $this->getRelationWithoutConstraints($name);

            // When the related model lives on a connection that cannot share a single
            // SQL statement with the parent's connection, fall back to resolving the
            // aggregate as a separate query and attaching the values to each model
            // after the parent query has been executed.
            if ($this->relationCrossConnectionStrategy($relation) === 'resolve') {
                $this->addCrossConnectionAggregate(
                    $name, $relation, $column, $function, $constraints, $alias ?? null
                );

                continue;
            }

            if ($function) {
                if ($this->getQuery()->getGrammar()->isExpression($column)) {
                    $aggregateColumn = $this->getQuery()->getGrammar()->getValue($column);
                } else {
                    $hashedColumn = $this->getRelationHashedColumn($column, $relation);

                    $aggregateColumn = $this->getQuery()->getGrammar()->wrap(
                        $column === '*' ? $column : $relation->getRelated()->qualifyColumn($hashedColumn)
                    );
                }

                $expression = $function === 'exists' ? $aggregateColumn : sprintf('%s(%s)', $function, $aggregateColumn);
            } else {
                $expression = $this->getQuery()->getGrammar()->getValue($column);
            }

            // Here, we will grab the relationship sub-query and prepare to add it to the main query
            // as a sub-select. First, we'll get the "has" query and use that to get the relation
            // sub-query. We'll format this relationship name and append this column if needed.
            $query = $relation->getRelationExistenceQuery(
                $relation->getRelated()->newQuery(), $this, new Expression($expression)
            )->setBindings([], 'select');

            $query->callScope($constraints);

            $query = $query->mergeConstraintsFrom($relation->getQuery())->toBase();

            // If the query contains certain elements like orderings / more than one column selected
            // then we will remove those elements from the query so that it will execute properly
            // when given to the database. Otherwise, we may receive SQL errors or poor syntax.
            $query->orders = null;
            $query->setBindings([], 'order');

            if (count($query->columns) > 1) {
                $query->columns = [$query->columns[0]];
                $query->bindings['select'] = [];
            }

            // Finally, we will make the proper column alias to the query and run this sub-select on
            // the query builder. Then, we will return the builder instance back to the developer
            // for further constraint chaining that needs to take place on the query as needed.
            $alias ??= Str::snake(
                preg_replace(
                    '/[^[:alnum:][:space:]_]/u',
                    '',
                    sprintf('%s %s %s', $name, $function, strtolower($this->getQuery()->getGrammar()->getValue($column)))
                )
            );

            if ($function === 'exists') {
                $this->selectRaw(
                    sprintf('exists(%s) as %s', $query->toSql(), $this->getQuery()->grammar->wrap($alias)),
                    $query->getBindings()
                )->withCasts([$alias => 'bool']);
            } else {
                $this->selectSub(
                    $function ? $query : $query->limit(1),
                    $alias
                );
            }
        }

        return $this;
    }

    /**
     * Get the relation hashed column name for the given column and relation.
     *
     * @param  string  $column
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<*, *, *>  $relation
     * @return string
     */
    protected function getRelationHashedColumn($column, $relation)
    {
        if (str_contains($column, '.')) {
            return $column;
        }

        return $this->getQuery()->from === $relation->getQuery()->getQuery()->from
            ? "{$relation->getRelationCountHash(false)}.$column"
            : $column;
    }

    /**
     * Add subselect queries to count the relations.
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function withCount($relations)
    {
        return $this->withAggregate(is_array($relations) ? $relations : func_get_args(), '*', 'count');
    }

    /**
     * Add subselect queries to include the max of the relation's column.
     *
     * @param  string|array  $relation
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
     * @return $this
     */
    public function withMax($relation, $column)
    {
        return $this->withAggregate($relation, $column, 'max');
    }

    /**
     * Add subselect queries to include the min of the relation's column.
     *
     * @param  string|array  $relation
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
     * @return $this
     */
    public function withMin($relation, $column)
    {
        return $this->withAggregate($relation, $column, 'min');
    }

    /**
     * Add subselect queries to include the sum of the relation's column.
     *
     * @param  string|array  $relation
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
     * @return $this
     */
    public function withSum($relation, $column)
    {
        return $this->withAggregate($relation, $column, 'sum');
    }

    /**
     * Add subselect queries to include the average of the relation's column.
     *
     * @param  string|array  $relation
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
     * @return $this
     */
    public function withAvg($relation, $column)
    {
        return $this->withAggregate($relation, $column, 'avg');
    }

    /**
     * Add subselect queries to include the existence of related models.
     *
     * @param  string|array  $relation
     * @return $this
     */
    public function withExists($relation)
    {
        return $this->withAggregate($relation, '*', 'exists');
    }

    /**
     * Register a deferred aggregate computation for a relation that lives on a
     * different database connection than the parent.
     *
     * The aggregate is resolved after the parent query has been executed by
     * running a single grouped query against the related connection, keyed
     * by the parent's foreign key, and then attaching the resulting value
     * to each hydrated model as the requested attribute alias.
     *
     * @param  string  $name
     * @param  \Illuminate\Database\Eloquent\Relations\HasOneOrMany<*, *, *> |\Illuminate\Database\Eloquent\Relations\BelongsTo<*, *, *>  $relation
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
     * @param  'count'|'exists'|'min'|'max'|'sum'|'avg'|null  $function
     * @param  \Closure(array{self}&array<array-key, mixed>)|null  $constraints
     * @param  string|null  $alias
     * @return void
     *
     * @throws \RuntimeException
     */
    protected function addCrossConnectionAggregate($name, Relation $relation, $column, $function, $constraints, $alias)
    {
        if (! ($relation instanceof HasOneOrMany || $relation instanceof BelongsTo)) {
            throw new RuntimeException(sprintf(
                'Cross-connection aggregate queries are not supported for the [%s] relation (type: [%s]). '
                .'Supported relation types: HasOne, HasMany, MorphOne, MorphMany, BelongsTo.',
                $name,
                $relation::class,
            ));
        }

        if (! in_array($function, ['count', 'exists', 'min', 'max', 'sum', 'avg'], true)) {
            throw new RuntimeException(sprintf(
                'Cross-connection aggregate function [%s] is not supported. '
                .'Supported functions: count, exists, min, max, sum, avg.',
                $function ?? 'null',
            ));
        }

        if ($alias === null) {
            $alias = Str::snake(preg_replace(
                '/[^[:alnum:][:space:]_]/u',
                '',
                sprintf(
                    '%s %s %s',
                    $name,
                    $function,
                    strtolower($this->getQuery()->getGrammar()->getValue($column))
                )
            ));
        }

        if (is_null($this->query->columns)) {
            $this->query->select([$this->query->from.'.*']);
        }

        $localKey = $relation instanceof BelongsTo
            ? $relation->getForeignKeyName()
            : $relation->getLocalKeyName();

        $relatedKey = $relation instanceof BelongsTo
            ? $relation->getOwnerKeyName()
            : $relation->getForeignKeyName();

        if ($function === 'exists') {
            $this->withCasts([$alias => 'bool']);
        }

        $this->afterQuery(function ($result) use ($alias, $relation, $relatedKey, $localKey, $constraints, $column, $function) {
            return $this->loadCrossConnectionAggregateValues(
                $result, $alias, $relation, $relatedKey, $localKey, $constraints, $column, $function
            );
        });
    }

    /**
     * Resolve aggregate values from the related connection and attach them to each parent model.
     *
     * @param  mixed  $result
     * @param  string  $alias
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<*, *, *>  $relation
     * @param  string  $relatedKey
     * @param  string  $localKey
     * @param  \Closure|null  $constraints
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
     * @param  string  $function
     * @return mixed
     */
    protected function loadCrossConnectionAggregateValues($result, $alias, Relation $relation, $relatedKey, $localKey, $constraints, $column, $function)
    {
        $models = $this->collectModelsForAfterQuery($result);

        if ($models === null) {
            return $result;
        }

        if ($models->isEmpty()) {
            return $result;
        }

        $defaultValue = match ($function) {
            'count' => 0,
            'exists' => false,
            default => null,
        };

        $keys = (new BaseCollection($models))
            ->map(fn ($model) => $model->getAttribute($localKey))
            ->filter(fn ($value) => $value !== null)
            ->unique()
            ->values()
            ->all();

        if (empty($keys)) {
            foreach ($models as $model) {
                $model->setAttribute($alias, $defaultValue);
            }

            return $result;
        }

        $relatedQuery = $relation->getRelated()->newQueryWithoutRelationships();

        if ($relation instanceof MorphOneOrMany) {
            $relatedQuery->where(
                $relation->getMorphType(),
                '=',
                $this->getModel()->getMorphClass()
            );
        }

        if ($constraints instanceof Closure) {
            $relatedQuery->callScope($constraints);
        }

        $relatedQuery->whereIn($relatedKey, $keys);

        if ($function === 'exists') {
            $matched = array_fill_keys(array_map(
                'strval',
                $relatedQuery->select($relatedKey)->distinct()->pluck($relatedKey)->all()
            ), true);

            foreach ($models as $model) {
                $key = $model->getAttribute($localKey);

                $model->setAttribute(
                    $alias,
                    $key !== null && isset($matched[(string) $key])
                );
            }

            return $result;
        }

        $grammar = $relatedQuery->getQuery()->getGrammar();

        if ($function === 'count') {
            $aggregateSql = 'count(*)';
        } elseif ($grammar->isExpression($column)) {
            $aggregateSql = sprintf('%s(%s)', $function, $grammar->getValue($column));
        } else {
            $aggregateSql = sprintf('%s(%s)', $function, $grammar->wrap($column));
        }

        $rows = $relatedQuery
            ->select($relatedKey)
            ->selectRaw($aggregateSql.' as aggregate_value')
            ->groupBy($relatedKey)
            ->toBase()
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $map[(string) $row->{$relatedKey}] = $row->aggregate_value;
        }

        foreach ($models as $model) {
            $key = $model->getAttribute($localKey);

            $model->setAttribute(
                $alias,
                $key !== null && array_key_exists((string) $key, $map)
                    ? $map[(string) $key]
                    : $defaultValue
            );
        }

        return $result;
    }

    /**
     * Normalize an after-query result into an EloquentCollection of models, or
     * null when the result does not contain models we can attach values to.
     *
     * Single Model results are wrapped in a fresh collection. The wrapping
     * collection is discarded after the callback runs, but mutations to the
     * model itself are preserved by reference.
     *
     * @param  mixed  $result
     * @return \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>|null
     */
    protected function collectModelsForAfterQuery($result)
    {
        if ($result instanceof EloquentCollection) {
            return $result;
        }

        if ($result instanceof Model) {
            return $result->newCollection([$result]);
        }

        return null;
    }

    /**
     * Determine the cross-connection strategy to use for a relation.
     *
     * Returns one of:
     *  - "same":    parent and related share the same connection (no special handling).
     *  - "prefix":  related lives on a different database but the same server, so
     *               the existence subquery can stay on a single PDO connection by
     *               qualifying the table with its database name.
     *  - "resolve": related lives on a different server or driver; the existence
     *               subquery must be resolved as a separate query and converted
     *               into a "where in" / "where not in" clause on the parent.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<*, *, *>  $relation
     * @return string
     */
    protected function relationCrossConnectionStrategy(Relation $relation)
    {
        $outer = $this->getModel()->getConnection();
        $inner = $relation->getRelated()->getConnection();

        if ($outer === $inner || $outer->getName() === $inner->getName()) {
            return 'same';
        }

        if (! method_exists($outer, 'getDriverName') || ! method_exists($inner, 'getDriverName')) {
            return 'resolve';
        }

        if ($outer->getDriverName() !== $inner->getDriverName()) {
            return 'resolve';
        }

        if ($outer->getDriverName() === 'sqlite') {
            return 'resolve';
        }

        $outerConfig = method_exists($outer, 'getConfig') ? $outer->getConfig() : [];
        $innerConfig = method_exists($inner, 'getConfig') ? $inner->getConfig() : [];

        $sameHost = ($outerConfig['host'] ?? null) === ($innerConfig['host'] ?? null);
        $samePort = ($outerConfig['port'] ?? null) === ($innerConfig['port'] ?? null);

        return ($sameHost && $samePort) ? 'prefix' : 'resolve';
    }

    /**
     * Prefix the existence subquery's table with the related connection's database name.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $hasQuery
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<*, *, *>  $relation
     * @return void
     */
    protected function applyCrossDatabasePrefixToHasQuery(Builder $hasQuery, Relation $relation)
    {
        $base = $hasQuery->getQuery();

        if (! is_string($base->from)) {
            return;
        }

        $databaseName = $relation->getRelated()->getConnection()->getDatabaseName();

        if ($databaseName === null || $databaseName === '') {
            return;
        }

        if (str_starts_with($base->from, $databaseName.'.') || str_contains($base->from, '.')) {
            return;
        }

        $base->from($databaseName.'.'.$base->from);
    }

    /**
     * Add a "has" condition for a relation whose related model is on a foreign connection.
     *
     * Executes the inner query against the related connection, collects the matching
     * parent keys, and applies them as a "where in" / "where not in" clause on the
     * outer query. This mirrors what Laravel already does for eager loads via "with",
     * applied to relationship-existence checks.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<*, *, *>  $relation
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
     * @param  string  $boolean
     * @param  \Closure|null  $callback
     * @return $this
     */
    protected function addCrossConnectionHasWhere(Relation $relation, $operator, $count, $boolean, ?Closure $callback)
    {
        [$relatedKey, $parentColumn] = $this->resolveCrossConnectionKeyPair($relation);

        $relatedQuery = $relation->getRelated()->newQueryWithoutRelationships();

        if ($relation instanceof MorphOneOrMany) {
            $relatedQuery->where(
                $relation->getMorphType(),
                '=',
                $this->getModel()->getMorphClass()
            );
        }

        if ($callback) {
            $relatedQuery->callScope($callback);
        }

        $useExists = $this->canUseExistsForExistenceCheck($operator, $count);
        $not = false;

        if ($useExists) {
            $not = ($operator === '<' && $count === 1);

            $ids = $relatedQuery->distinct()->pluck($relatedKey)->all();
        } else {
            if ($count instanceof Expression) {
                throw new RuntimeException(
                    'Cross-connection relationship existence queries do not support '
                    .'Expression count values. Use a literal integer count, or move the '
                    .'relationship to a single connection.'
                );
            }

            $zeroSatisfies = $this->zeroCountSatisfiesCrossConnectionOperator($operator, $count);
            $effectiveOperator = $zeroSatisfies
                ? $this->complementCrossConnectionCountOperator($operator)
                : $operator;

            $ids = $relatedQuery
                ->select($relatedKey)
                ->groupBy($relatedKey)
                ->havingRaw('count(*) '.$effectiveOperator.' ?', [$count])
                ->pluck($relatedKey)
                ->all();

            if ($zeroSatisfies) {
                $not = true;
            }
        }

        if (empty($ids)) {
            return $this->addEmptyCrossConnectionConstraint($not, $boolean);
        }

        return $this->whereIn($parentColumn, $ids, $boolean, $not);
    }

    /**
     * Apply the correct empty-result constraint when a cross-connection resolution
     * returns no matching parent keys.
     *
     * "has" with no related rows ⇒ no parents qualify (force the clause to false).
     * "doesntHave" with no related rows ⇒ all parents qualify (force it to true).
     *
     * @param  bool  $not
     * @param  string  $boolean
     * @return $this
     */
    protected function addEmptyCrossConnectionConstraint($not, $boolean)
    {
        if ($not) {
            return $boolean === 'or'
                ? $this->orWhereRaw('1 = 1')
                : $this;
        }

        return $boolean === 'or'
            ? $this->orWhereRaw('0 = 1')
            : $this->whereRaw('0 = 1');
    }

    /**
     * Resolve the [related column, parent column] pair used by cross-connection
     * relationship existence queries.
     *
     * Cross-connection support currently covers HasOne, HasMany, MorphOne,
     * MorphMany, and BelongsTo relations — the most common relationship types
     * that span database boundaries in practice. Other relation types throw a
     * descriptive exception so the developer can restructure their query or
     * move the relationship to a single connection.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<*, *, *>  $relation
     * @return array{0: string, 1: string}
     * @phpstan-return ($relation is \Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Eloquent\Relations\HasOneOrMany ? array{0: string, 1: string} : never)
     *
     * @throws \RuntimeException
     */
    protected function resolveCrossConnectionKeyPair(Relation $relation)
    {
        if ($relation instanceof BelongsTo) {
            return [
                $relation->getOwnerKeyName(),
                $relation->getQualifiedForeignKeyName(),
            ];
        }

        if ($relation instanceof HasOneOrMany) {
            return [
                $relation->getForeignKeyName(),
                $relation->getQualifiedParentKeyName(),
            ];
        }

        throw new RuntimeException(sprintf(
            'Cross-connection relationship existence queries are not supported for [%s] '
            .'(parent connection [%s], related connection [%s]). Supported relation types: '
            .'HasOne, HasMany, MorphOne, MorphMany, BelongsTo. Move the relationship to a '
            .'single connection, or use a connection that can address both databases.',
            $relation::class,
            $this->getModel()->getConnection()->getName(),
            $relation->getRelated()->getConnection()->getName(),
        ));
    }

    /**
     * Determine whether a parent row with zero matching related rows satisfies
     * the given count constraint.
     *
     * This is the signal we use to flip the cross-connection resolution from
     * "select parents that match" (whereIn) to "select parents that do NOT
     * match the complementary condition" (whereNotIn), so that parents with
     * no related rows are included where the operator allows them to be.
     *
     * @param  string  $operator
     * @param  mixed  $count
     * @return bool
     */
    protected function zeroCountSatisfiesCrossConnectionOperator($operator, $count)
    {
        if (! is_numeric($count)) {
            return false;
        }

        $count = (int) $count;

        return match ($operator) {
            '>=' => $count <= 0,
            '>' => $count < 0,
            '=', '==' => $count === 0,
            '<=' => $count >= 0,
            '<' => $count > 0,
            '!=', '<>' => $count !== 0,
            default => false,
        };
    }

    /**
     * Return the logical complement of a count comparison operator.
     *
     * @param  string  $operator
     * @return string
     */
    protected function complementCrossConnectionCountOperator($operator)
    {
        return match ($operator) {
            '>=' => '<',
            '>' => '<=',
            '=', '==' => '!=',
            '<=' => '>',
            '<' => '>=',
            '!=', '<>' => '=',
            default => $operator,
        };
    }

    /**
     * Add the "has" condition where clause to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $hasQuery
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<*, *, *>  $relation
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
     * @param  string  $boolean
     * @return $this
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
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $from
     * @return $this
     */
    public function mergeConstraintsFrom(Builder $from)
    {
        $whereBindings = $from->getQuery()->getRawBindings()['where'] ?? [];

        $wheres = $from->getQuery()->from !== $this->getQuery()->from
            ? $this->requalifyWhereTables(
                $from->getQuery()->wheres,
                $from->getQuery()->grammar->getValue($from->getQuery()->from),
                $this->getModel()->getTable()
            ) : $from->getQuery()->wheres;

        // Here we have some other query that we want to merge the where constraints from. We will
        // copy over any where constraints on the query as well as remove any global scopes the
        // query might have removed. Then we will return ourselves with the finished merging.
        return $this->withoutGlobalScopes(
            $from->removedScopes()
        )->mergeWheres(
            $wheres, $whereBindings
        );
    }

    /**
     * Updates the table name for any columns with a new qualified name.
     *
     * @param  array  $wheres
     * @param  string  $from
     * @param  string  $to
     * @return array
     */
    protected function requalifyWhereTables(array $wheres, string $from, string $to): array
    {
        return (new BaseCollection($wheres))->map(function ($where) use ($from, $to) {
            return (new BaseCollection($where))->map(function ($value) use ($from, $to) {
                return is_string($value) && str_starts_with($value, $from.'.')
                    ? $to.'.'.Str::afterLast($value, '.')
                    : $value;
            });
        })->toArray();
    }

    /**
     * Add a sub-query count clause to this query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
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
     * @return \Illuminate\Database\Eloquent\Relations\Relation<*, *, *>
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
     * @param  \Illuminate\Contracts\Database\Query\Expression|int  $count
     * @return bool
     */
    protected function canUseExistsForExistenceCheck($operator, $count)
    {
        return ($operator === '>=' || $operator === '<') && $count === 1;
    }
}
