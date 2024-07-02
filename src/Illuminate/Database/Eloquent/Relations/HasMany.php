<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\HasOneOrMany<TRelatedModel, TDeclaringModel, \Illuminate\Database\Eloquent\Collection<int, TRelatedModel>>
 */
class HasMany extends HasOneOrMany
{
    /**
     * Convert the relationship to a "has one" relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<TRelatedModel, TDeclaringModel>
     */
    public function one()
    {
        return HasOne::noConstraints(fn () => new HasOne(
            $this->getQuery(),
            $this->parent,
            $this->foreignKey,
            $this->localKey
        ));
    }

    /** @inheritDoc */
    public function getResults()
    {
        return ! is_null($this->getParentKey())
                ? $this->query->get()
                : $this->related->newCollection();
    }

    /** @inheritDoc */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /** @inheritDoc */
    public function match(array $models, Collection $results, $relation)
    {
        return $this->matchMany($models, $results, $relation);
    }
}
