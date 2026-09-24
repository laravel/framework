<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait SupportsPivotInverseRelations
{
    /**
     * The name of the declaring model's relationship on the pivot.
     */
    protected ?string $declaringInverseRelationship = null;

    /**
     * The name of the related model's relationship on the pivot.
     */
    protected ?string $relatedInverseRelationship = null;

    /**
     * Instruct Eloquent to link the declaring and related models back to the pivot after the relationship query has run.
     *
     * @return $this
     */
    public function chaperone(?string $declaring = null, ?string $related = null): static
    {
        if (! $this->using) {
            return $this;
        }

        $pivotModel = new $this->using;

        $this->declaringInverseRelationship = $this->resolvePivotInverseRelation(
            $pivotModel, $declaring, $this->foreignPivotKey, $this->parent
        );

        $this->relatedInverseRelationship = $this->resolvePivotInverseRelation(
            $pivotModel, $related, $this->relatedPivotKey, $this->related
        );

        return $this;
    }

    /**
     * Remove the chaperone relationships for this query.
     *
     * @return $this
     */
    public function withoutChaperone(): static
    {
        $this->declaringInverseRelationship = null;
        $this->relatedInverseRelationship = null;

        return $this;
    }

    /**
     * Resolve the inverse relation name on the pivot for a given model.
     *
     * If an explicit name is provided and invalid, an exception is thrown.
     * If guessing fails, null is returned.
     *
     * @throws \Illuminate\Database\Eloquent\RelationNotFoundException
     */
    protected function resolvePivotInverseRelation(Model $pivotModel, ?string $relation, string $foreignKey, Model $model): ?string
    {
        if ($relation !== null) {
            if (! $pivotModel->isRelation($relation)) {
                throw RelationNotFoundException::make($pivotModel, $relation);
            }

            return $relation;
        }

        return $this->guessPivotInverseRelation($pivotModel, $foreignKey, $model);
    }

    /**
     * Attempt to guess the inverse relation name on the pivot for a given model.
     */
    protected function guessPivotInverseRelation(Model $pivotModel, string $foreignKey, Model $model): ?string
    {
        $candidates = array_filter(array_unique([
            Str::camel(Str::beforeLast($foreignKey, $model->getKeyName())),
            Str::camel(class_basename($model)),
        ]));

        return Arr::first(
            $candidates,
            fn ($relation) => $pivotModel->isRelation($relation)
        );
    }

    /**
     * Apply chaperone relationships to a pivot model instance.
     */
    protected function applyChaperonesToPivot(Model $pivot, Model $declaring, Model $related): void
    {
        if ($this->declaringInverseRelationship) {
            $pivot->setRelation($this->declaringInverseRelationship, $declaring);
        }

        if ($this->relatedInverseRelationship) {
            $pivot->setRelation($this->relatedInverseRelationship, $related);
        }
    }
}
