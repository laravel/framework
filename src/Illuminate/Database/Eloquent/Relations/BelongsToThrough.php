<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

class BelongsToThrough extends Relation
{
    /**
     * Column alias for matching eagerly loaded models.
     *
     * @var string
     */
    const RELATED_THROUGH_KEY = '__deep_related_through_key';

    /**
     * List of intermediate model instances.
     *
     * @var array
     */
    protected $models;

    /**
     * The local key on the relationship.
     *
     * @var string
     */
    protected $localKey;

    /**
     * Create a new instance of relation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  array  $models
     * @param  string|null  $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, array $models, $localKey = null)
    {
        $this->models = $models;
        $this->localKey = $localKey ?: $parent->getKeyName();

        parent::__construct($query, $parent);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        $this->setJoins();

        $this->getQuery()->select([$this->getRelated()->getTable().'.*']);

        if (static::$constraints) {
            $this->getQuery()->where($this->getQualifiedParentKeyName(), '=', $this->parent[$this->localKey]);
            $this->setSoftDeletes();
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
        $this->getQuery()->addSelect([
            $this->getParent()->getQualifiedKeyName().' as '.self::RELATED_THROUGH_KEY,
        ]);

        $this->getQuery()->whereIn($this->getParent()->getQualifiedKeyName(), $this->getKeys($models, $this->localKey));
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array $models
     * @param  string  $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->getRelated());
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

        foreach ($models as $model) {
            $key = $model->{$this->localKey};

            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            }
        }

        return $models;
    }

    /**
     * Get the results of the relationship.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getResults()
    {
        return $this->getQuery()->first();
    }

    /**
     * Set the required joins on the relation query.
     *
     * @return void
     */
    protected function setJoins()
    {
        $one = $this->getRelated()->getQualifiedKeyName();
        $prev = $this->getRelated()->getForeignKey();
        foreach ($this->models as $model) {
            $other = $model->getTable().'.'.$prev;
            $this->getQuery()->leftJoin($model->getTable(), $one, '=', $other);

            $prev = $model->getForeignKey();
            $one = $model->getQualifiedKeyName();
        }
    }

    /**
     * Set the soft deleting constraints on the relation query.
     *
     * @return void
     */
    protected function setSoftDeletes()
    {
        foreach ($this->models as $model) {
            if ($this->hasSoftDeletes($model)) {
                $this->getQuery()->whereNull($model->getQualifiedDeletedAtColumn());
            }
        }
    }

    /**
     * Determine whether the model uses Soft Deletes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    public function hasSoftDeletes(Model $model)
    {
        return in_array(SoftDeletes::class, class_uses_recursive(get_class($model)));
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->{static::RELATED_THROUGH_KEY}] = $result;
            unset($result[static::RELATED_THROUGH_KEY]);
        }

        return $dictionary;
    }
}
