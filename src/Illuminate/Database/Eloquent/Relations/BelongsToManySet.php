<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithDictionary;

class BelongsToManySet extends Relation
{
    use InteractsWithDictionary;

    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The local key of the parent model.
     *
     * @var string
     */
    protected $localKey;

    /**
     * The location of the set collumn.
     *
     * @var bool
     */
    protected $setIsLocal = true;

    /**
     * Create a new has one or many relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @param  bool  $setLocal
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey, $setIsLocal)
    {
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;
        $this->setIsLocal = $setIsLocal;

        parent::__construct($query, $parent);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $query = $this->getRelationQuery();

            if ($this->setIsLocal) {
                $query->whereIn($this->foreignKey, explode(',', $this->getParentKey()));
            } else {
                $query->whereRaw('FIND_IN_SET(?, '.$this->foreignKey.')', $this->getParentKey());
            }

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
        if ($this->setIsLocal) {
            $whereIn = $this->whereInMethod($this->parent, $this->localKey);

            $this->query->{$whereIn}(
                $this->foreignKey,
                $this->getSets($models, $this->localKey)
            );
        } else {
            foreach ($models as $model) {
                $this->query->orWhereRaw(
                    'FIND_IN_SET(?, '.$this->foreignKey.')',
                    $model->getAttribute($this->localKey)
                );
            }
        }
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
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {
            $relations = collect($this->getSet($model, $this->localKey))->map(function ($value) use ($dictionary) {
                return isset($dictionary[$key = $value]) ? $this->getRelationValue($dictionary, $key) : null;
            })->flatten()->values()->unique(null, true)->filter()->all();

            $model->setRelation(
                $relation,
                $this->related->newCollection($relations)
            );
        }

        return $models;
    }

    /**
     * Get all of the primary keys for an array of models.
     *
     * @param  array  $models
     * @param  string|null  $key
     * @return array
     */
    protected function getKeys(array $models, $key = null)
    {
        return collect($models)->map(function ($value) use ($key) {
            return $key ? $value->getAttribute($key) : $value->getKey();
        })->values()->unique(null, true)->sort()->all();
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
     * Get the value of a many-to-many relationship.
     *
     * @param  array  $dictionary
     * @param  string  $key
     * @return mixed
     */
    protected function getRelationValue(array $dictionary, $key)
    {
        $value = $dictionary[$key];

        return $this->related->newCollection($value);
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        $foreign = $this->getForeignKeyName();

        if ($this->setIsLocal) {
            return $results->mapToDictionary(function ($result) use ($foreign) {
                return [$this->getDictionaryKey($result->{$foreign}) => $result];
            })->all();
        }

        $dictionary = [];

        foreach ($results as $result) {
            $keys = explode(',', $result->{$foreign});

            foreach ($keys as $key) {
                $dictionary[$key][] = $result;
            }
        }

        return $dictionary;
    }

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
     * Get the key value of the parent's local key.
     *
     * @return mixed
     */
    public function getParentKey()
    {
        return $this->parent->getAttribute($this->localKey);
    }

    /**
     * Get the plain foreign key.
     *
     * @return string
     */
    public function getForeignKeyName()
    {
        $segments = explode('.', $this->foreignKey);

        return end($segments);
    }
}
