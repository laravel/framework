<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BelongsToManySet extends HasMany
{
    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $query = $this->getRelationQuery();

            $query->whereIn($this->foreignKey, explode(',', $this->getParentKey()));

            $query->whereNotNull($this->foreignKey);
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $whereIn = $this->whereInMethod($this->parent, $this->localKey);

        $this->query->{$whereIn}(
            $this->foreignKey, $this->getSets($models, $this->localKey)
        );
    }

    /**
     * Get get a set for a model.
     *
     * @param  Model  $model
     * @param  string  $key
     * @return array
     */
    protected function getSet(Model $model, $key)
    {
        return explode(',', $model->getAttribute($key));
    }

    /**
     * Get all of the sets for an array of models.
     *
     * @param  array  $models
     * @param  string  $key
     * @return array
     */
    protected function getSets(array $models, $key)
    {
        return collect($models)->map(function ($value) use ($key) {
            return $this->getSet($value, $key);
        })->flatten()->values()->unique(null, true)->sort()->filter()->all();
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @param  string  $type
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {
            $relations = collect($this->getSet($model, $this->localKey))->map(function ($value) use ($dictionary, $type) {
                return isset($dictionary[$key = $value]) ? $this->getRelationValue($dictionary, $key, $type) : null;
            })->flatten()->values()->unique(null, true)->filter()->all();

            $model->setRelation(
                $relation, $this->related->newCollection($relations)
            );
        }

        return $models;
    }
}
