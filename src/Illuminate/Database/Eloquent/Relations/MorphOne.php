<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Contracts\Database\Eloquent\SupportsPartialRelations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\CanBeOneOfMany;
use Illuminate\Database\Eloquent\Relations\Concerns\ComparesRelatedModels;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;
use Illuminate\Database\Query\JoinClause;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TParentModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\MorphOneOrMany<TRelatedModel, TParentModel>
 */
class MorphOne extends MorphOneOrMany implements SupportsPartialRelations
{
    use CanBeOneOfMany, ComparesRelatedModels, SupportsDefaultModels;

    /**
     * Get the results of the relationship.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getResults()
    {
        if (is_null($this->getParentKey())) {
            return $this->getDefaultFor($this->parent);
        }

        $result = $this->query->first();

        return $result ?: tap($this->getDefaultFor($this->parent), function ($instance) {
            $instance->setAttribute($this->getMorphType(), $this->parent->getMorphClass());

            $key = $this->parent->getKey();

            if (! is_null($key)) {
                $instance->setAttribute($this->getForeignKeyName(), $key);
            }
        });
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array  $models
     * @param  string  $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->getDefaultFor($model));
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parent models.
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, EloquentCollection $results, $relation)
    {
        return $this->matchOne($models, $results, $relation);
    }

    /**
     * Get the query for existence check on the relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|string  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        if ($this->isOneOfMany()) {
            $this->mergeOneOfManyJoinsTo($query);
        }

        return parent::getRelationExistenceQuery($query, $parentQuery, $columns);
    }

    /**
     * Add constraints for "one of many" subquery.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $column
     * @param  string|null  $aggregate
     * @return void
     */
    public function addOneOfManySubQueryConstraints(Builder $query, $column = null, $aggregate = null)
    {
        $query->addSelect($this->foreignKey, $this->morphType);
    }

    /**
     * Get columns needed for "one of many" subquery.
     *
     * @return array
     */
    public function getOneOfManySubQuerySelectColumns()
    {
        return [$this->foreignKey, $this->morphType];
    }

    /**
     * Add join constraints for "one of many" subquery.
     *
     * @param  \Illuminate\Database\Query\JoinClause  $join
     * @return void
     */
    public function addOneOfManyJoinSubQueryConstraints(JoinClause $join)
    {
        $join->on(
            $this->qualifySubSelectColumn($this->morphType),
            '=',
            $this->qualifyRelatedColumn($this->morphType)
        )->on(
            $this->qualifySubSelectColumn($this->foreignKey),
            '=',
            $this->qualifyRelatedColumn($this->foreignKey)
        );
    }

    /**
     * Make a new related instance for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function newRelatedInstanceFor(Model $parent)
    {
        return tap($this->related->newInstance(), function ($instance) use ($parent) {
            $instance->setAttribute($this->getForeignKeyName(), $parent->{$this->localKey});
            $instance->setAttribute($this->getMorphType(), $this->morphClass);

            $this->applyInverseRelationToModel($instance, $parent);
        });
    }

    /**
     * Get the foreign key value from the related model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    protected function getRelatedKeyFrom(Model $model)
    {
        return $model->getAttribute($this->getForeignKeyName());
    }
}
