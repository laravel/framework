<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait SupportsBelongsToInverseRelations
{
    /**
     * The name of the inverse relationship.
     */
    protected ?string $inverseRelationship = null;

    /**
     * Whether the inverse relationship is to-one, keyed by inverse relationship and owner class.
     *
     * @var array<string, array<class-string<\Illuminate\Database\Eloquent\Model>, bool>>
     */
    protected array $toOneInverseByOwner = [];

    /**
     * Instruct Eloquent to keep the owner's inverse relationship in sync with this child.
     *
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\RelationNotFoundException
     */
    public function chaperone(?string $relation = null): static
    {
        $relation ??= $this->guessInverseRelation();

        if (! $relation || ! $this->getModel()->isRelation($relation)) {
            throw RelationNotFoundException::make($this->getModel(), $relation ?: 'null');
        }

        $this->inverseRelationship = $relation;

        return $this;
    }

    /**
     * Remove the chaperone relationship for this query.
     *
     * @return $this
     */
    public function withoutChaperone(): static
    {
        $this->inverseRelationship = null;

        return $this;
    }

    /**
     * Guess the name of the inverse relationship.
     */
    protected function guessInverseRelation(): ?string
    {
        return Arr::first([
            Str::camel(Str::plural(class_basename($this->getParent()))),
            Str::camel(class_basename($this->getParent())),
        ], fn ($relation) => $this->getModel()->isRelation($relation));
    }

    /**
     * Get the currently associated owner, if a chaperone is configured and the owner is loaded.
     */
    protected function getLoadedOwner(): ?Model
    {
        if ($this->inverseRelationship === null || ! $this->child->relationLoaded($this->relationName)) {
            return null;
        }

        $owner = $this->child->getRelation($this->relationName);

        return $owner instanceof Model ? $owner : null;
    }

    /**
     * Sync the child into the inverse relation on the owner, if that relation is loaded.
     */
    protected function applyInverseRelation(Model $owner, Model $child): void
    {
        $inverse = $this->inverseRelationship;

        if ($inverse === null || ! $owner->relationLoaded($inverse)) {
            return;
        }

        $related = $owner->getRelation($inverse);

        if (! $related instanceof EloquentCollection) {
            if ($this->inverseRelationIsToOne($owner)) {
                $owner->setRelation($inverse, $child);
            } else {
                $owner->unsetRelation($inverse);
            }

            return;
        }

        if (! $related->contains(fn ($model) => $this->isSameModel($model, $child))) {
            $owner->setRelation($inverse, $related->concat([$child]));
        }
    }

    /**
     * Set an existing child as the inverse relation on the owner, if that relation is to-one and not yet loaded.
     *
     * @throws \Illuminate\Database\Eloquent\RelationNotFoundException
     */
    protected function applyInverseRelationToOwner(Model $owner, Model $child): void
    {
        $inverse = $this->inverseRelationship;

        if ($inverse === null || ! $child->exists) {
            return;
        }

        if (! $owner->isRelation($inverse)) {
            throw RelationNotFoundException::make($owner, $inverse);
        }

        if ($this->inverseRelationIsToOne($owner) && ! $owner->relationLoaded($inverse)) {
            $owner->setRelation($inverse, $child);
        }
    }

    /**
     * Determine whether the inverse relation on the given owner is an unconstrained to-one relation over this relation's keys.
     */
    protected function inverseRelationIsToOne(Model $owner): bool
    {
        return $this->toOneInverseByOwner[$this->inverseRelationship][$owner::class] ??= $this->resolveInverseRelationIsToOne($owner);
    }

    /**
     * Resolve whether the inverse relation on the given owner is an unconstrained to-one relation over this relation's keys.
     */
    protected function resolveInverseRelationIsToOne(Model $owner): bool
    {
        $relation = Relation::noConstraintsForRelation(fn () => $owner->{$this->inverseRelationship}());

        if (! ($relation instanceof HasOne || $relation instanceof MorphOne) || $relation->isOneOfMany()) {
            return false;
        }

        $query = $relation->getBaseQuery();

        if ($query->wheres || $query->orders) {
            return false;
        }

        if ($relation instanceof MorphOne && ! ($this instanceof MorphTo && $relation->getMorphType() === $this->morphType)) {
            return false;
        }

        return $relation->getForeignKeyName() === $this->foreignKey
            && $relation->getLocalKeyName() === ($this->ownerKey ?? $owner->getKeyName());
    }

    /**
     * Remove the child from the inverse relation on the owner, if that relation is loaded.
     */
    protected function removeInverseRelation(?Model $owner, Model $child): void
    {
        $inverse = $this->inverseRelationship;

        if (! $owner || $inverse === null || ! $owner->relationLoaded($inverse)) {
            return;
        }

        $related = $owner->getRelation($inverse);

        if ($related instanceof EloquentCollection) {
            $owner->setRelation($inverse, $related->reject(fn ($model) => $this->isSameModel($model, $child))->values());
        } elseif ($related instanceof Model && $this->isSameModel($related, $child)) {
            if ($this->inverseRelationIsToOne($owner)) {
                $owner->setRelation($inverse, null);
            } else {
                $owner->unsetRelation($inverse);
            }
        }
    }

    /**
     * Determine whether associating the given model or key moves the child away from the previous owner.
     *
     * @param  \Illuminate\Database\Eloquent\Model|int|string|null  $model
     */
    protected function ownerChanged(Model $previousOwner, $model): bool
    {
        if ($model instanceof Model) {
            return ! $this->isSameModel($previousOwner, $model);
        }

        return is_null($model) || $previousOwner->getAttribute($this->ownerKey) != $model;
    }

    /**
     * Determine whether two models are the same, by instance or by row.
     */
    protected function isSameModel(Model $model, Model $other): bool
    {
        return $model === $other
            || ($model->exists && $other->exists && $model->is($other));
    }

    /**
     * Get the name of the inverse relationship.
     */
    public function getInverseRelationship(): ?string
    {
        return $this->inverseRelationship;
    }
}
