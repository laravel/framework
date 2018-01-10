<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Illuminate\Database\Eloquent\Model;

trait SetsOppositeRelations
{
    /**
     * @var string|null
     */
    protected $oppositeRelationName;

    /**
     * @var bool
     */
    protected $oppositeRelationLoaded = false;

    /**
     * Specify that opposite relationship with given name should be set with parent model.
     *
     * @param string $relation
     *
     * @return $this
     */
    public function withOpposite($relation)
    {
        $this->oppositeRelationName = $relation;

        return $this;
    }

    /**
     * Set opposite relationship for given model/models with parent model.
     *
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|null $models
     */
    public function setOppositeRelation($models)
    {
        $parent = $this->getParent();
        
        if ($this->oppositeRelationLoaded || !$this->oppositeRelationName || !$parent->exists) {
            return;
        }

        $this->oppositeRelationLoaded = true;

        if ($models instanceof Model) {
            $models->setRelation($this->oppositeRelationName, $parent);
            return;
        }

        foreach ($models as $model) {
            $model->setRelation($this->oppositeRelationName, $parent);
        }
    }
}
