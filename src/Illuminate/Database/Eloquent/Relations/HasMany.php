<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;


/**
 * @template TRelated of \Illuminate\Database\Eloquent\Model
 * @template TParent of \Illuminate\Database\Eloquent\Model origin of the relationship
 * @extends HasOneOrMany<TRelated, TParent>
 */
class HasMany extends HasOneOrMany
{
    /**
     * Get the results of the relationship.
     *
     * @return Collection<TRelated>
     */
    public function getResults()
    {
        return ! is_null($this->getParentKey())
                ? $this->query->get()
                : $this->related->newCollection();
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
            $model->setRelation($relation, $this->related->newCollection());
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
        return $this->matchMany($models, $results, $relation);
    }
}
