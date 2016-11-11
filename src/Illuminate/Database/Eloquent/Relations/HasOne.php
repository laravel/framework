<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class HasOne extends HasOneOrMany
{
    /**
     * Determine whether getResults should return a default new model instance or not.
     *
     * @var bool
     */
    protected $withDefault = false;

    /**
     * Return a new model instance in case the relationship does not exist.
     *
     * @return $this
     */
    public function withDefault()
    {
        $this->withDefault = true;

        return $this;
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->query->first() ?: $this->getDefaultFor($this->parent);
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array   $models
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
     * Match the eagerly loaded results to their parents.
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        return $this->matchOne($models, $results, $relation);
    }

    /**
     * Get the default value for this relation.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getDefaultFor(Model $model)
    {
        if ($this->withDefault) {
            return $this->related->newInstance()->setAttribute(
                $this->getPlainForeignKey(), $model->getAttribute($this->localKey)
            );
        }
    }
}
