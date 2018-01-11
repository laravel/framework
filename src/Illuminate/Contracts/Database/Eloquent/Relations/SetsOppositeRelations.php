<?php

namespace Illuminate\Contracts\Database\Eloquent\Relations;

interface SetsOppositeRelations
{
    /**
     * Specify that opposite relationship with given name should be set with parent model.
     *
     * @param string $relation
     *
     * @return $this
     */
    public function withOpposite($relation);

    /**
     * Set opposite relationship for given model/models with parent model.
     *
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|null $models
     */
    public function setOppositeRelation($models);    
}