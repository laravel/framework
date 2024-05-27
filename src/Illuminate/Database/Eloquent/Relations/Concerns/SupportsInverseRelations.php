<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;

trait SupportsInverseRelations
{
    protected string|null $inverseRelationship = null;

    /**
     * Gets the name of the inverse relationship.
     *
     * @return string|null
     */
    public function getInverseRelationship()
    {
        return $this->inverseRelationship;
    }

    /**
     * Links the related models back to the parent after the query has run.
     *
     * @param  string  $relation
     * @return $this
     */
    public function inverse(string $relation)
    {
        if (! $this->getModel()->isRelation($relation)) {
            throw RelationNotFoundException::make($this->getModel(), $relation);
        }

        if ($this->inverseRelationship === null && $relation) {
            $this->query->afterQuery(function ($result) {
                return $this->inverseRelationship
                    ? $this->applyInverseRelationToCollection($result, $this->getParent())
                    : $result;
            });
        }

        $this->inverseRelationship = $relation;

        return $this;
    }

    /**
     * Removes the inverse relationship for this query.
     *
     * @return $this
     */
    public function withoutInverse()
    {
        $this->inverseRelationship = null;

        return $this;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @param  \Illuminate\Database\Eloquent\Model|null  $parent
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function applyInverseRelationToCollection($models, ?Model $parent = null)
    {
        $parent ??= $this->getParent();

        foreach ($models as $model) {
            $this->applyInverseRelationToModel($model, $parent);
        }

        return $models;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  \Illuminate\Database\Eloquent\Model|null  $parent
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function applyInverseRelationToModel(Model $model, ?Model $parent = null)
    {
        if ($inverse = $this->getInverseRelationship()) {
            $parent ??= $this->getParent();

            $model->setRelation($inverse, $parent);
        }

        return $model;
    }
}
