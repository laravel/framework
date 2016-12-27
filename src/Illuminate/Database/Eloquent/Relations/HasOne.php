<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class HasOne extends HasOneOrMany
{
    /**
     * Indicates if a default model instance should be used.
     *
     * Alternatively, may be a Closure to execute to retrieve default value.
     *
     * @var \Closure|bool
     */
    protected $withDefault;

    /**
     * Return a new model instance in case the relationship does not exist.
     *
     * @param  \Closure|bool  $callback
     * @return $this
     */
    public function withDefault($callback = true)
    {
        $this->withDefault = $callback;

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
        if (! $this->withDefault) {
            return;
        }

        $instance = $this->related->newInstance()->setAttribute(
            $this->getPlainForeignKey(), $model->getAttribute($this->localKey)
        );

        if (is_callable($this->withDefault)) {
            return call_user_func($this->withDefault, $instance) ?: $instance;
        }

        if (is_array($this->withDefault)) {
            $instance->forceFill($this->withDefault);
        }

        return $instance;
    }

    /**
     * Attach a model instance to the parent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function save(Model $model)
    {
        $this->deleteOldRelation($model);

        $model->setAttribute($this->getPlainForeignKey(), $this->getParentKey());

        return $model->save() ? $model : false;
    }

    /**
     * It's impossible to attach many models to hasOne relation.
     *
     * @param \Traversable|array $models
     *
     * @return void
     * @throws \LogicException
     */
    public function saveMany($models = [])
    {
        throw new \LogicException('Can\'t attach many models to hasOne relation');
    }

    /**
     * Create a new instance of the related model.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $attributes)
    {
        // Here we will set the raw attributes to avoid hitting the "fill" method so
        // that we do not have to worry about a mass accessor rules blocking sets
        // on the models. Otherwise, some of these attributes will not get set.
        $instance = $this->related->newInstance($attributes);

        $this->deleteOldRelation($instance);

        $instance->setAttribute($this->getPlainForeignKey(), $this->getParentKey());

        $instance->save();

        return $instance;
    }

    /**
     * It's impossible to create many models with hasOne relation.
     *
     * @param array $array
     *
     * @return void
     * @throws \LogicException
     */
    public function createMany(array $array = [])
    {
        throw new \LogicException('Can\'t create many models with hasOne relation');
    }

    /**
     * Delete old relation if exists.
     *
     * @param Model $model
     *
     * @return bool
     */
    protected function deleteOldRelation(Model $model)
    {
        $existingModel = $model->newQuery()
            ->where($this->getPlainForeignKey(), $this->getParentKey())->first();

        return $existingModel ? (bool) $existingModel->delete() : true;
    }
}
