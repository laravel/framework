<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;

class RelationPusher
{
    /**
     * The name of the relationship.
     *
     * @var string
     */
    protected $name;

    /**
     * The model or models to push.
     *
     * @var \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    protected $models;

    /**
     * The relationship object.
     *
     * @var \Illuminate\Database\Eloquent\Relations\Relation|null
     */
    protected $relation;

    /**
     * RelationPusher constructor.
     * @param  string  $name
     * @param  \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model  $models
     * @param  \Illuminate\Database\Eloquent\Relations\Relation|null  $relation
     * @return void
     */
    public function __construct($name, $models, $relation)
    {
        $this->name = $name;
        $this->models = $models instanceof Collection ? $models : collect([$models]);
        $this->relation = $relation;
    }

    /**
     * Return true if the relation is inverse, false otherwise.
     *
     * @return bool
     */
    public function isInverse()
    {
        // If there is no relationship object available, we'll assume
        // the relation is not inverse as it's the most common case
        // otherwise, we will ask the relationship object itself.
        return $this->relation && $this->relation->isInverse();
    }

    /**
     * Save the model(s) and all of its(their) relationships.
     *
     * @return bool
     */
    public function push()
    {
        // If there is a relationship object available, let's push the model(s)
        // through this object, since the relation can contain extra logic to
        // associate relatives, otherwise just push the model(s) directly.
        return $this->relation
            ? $this->pushThroughRelation()
            : $this->pushModelsDirectly();
    }

    /**
     * Save the models using the relationship object.
     *
     * @return bool
     */
    protected function pushThroughRelation()
    {
        return $this->models->every(function ($model) {
            return $this->relation->push($model);
        });
    }

    /**
     * Push models directly (without using a relationship object).
     *
     * @return bool
     */
    protected function pushModelsDirectly()
    {
        return $this->models->every(function ($model) {
            return $model->push();
        });
    }
}
