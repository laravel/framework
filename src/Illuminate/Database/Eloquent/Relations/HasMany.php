<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;

class HasMany extends HasOneOrMany
{
    /**
     * Get the results of the relationship.
     *
     * @return mixed
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

    /**
     *  Insert new records or update the existing ones.
     *
     * @param array $values
     * @param $uniqueBy
     * @param $update
     * @return int
     */
    public function upsert(array $values, $uniqueBy, $update = null)
    {
        $values = collect($values)
            ->map(function ($value) {
                return array_merge($value, [$this->getForeignKeyName() => $this->getParentKey()]);
            })->toArray();

        return parent::upsert($values, $uniqueBy, $update);
    }
}
