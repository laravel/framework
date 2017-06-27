<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;

class PushableRelation
{
    /**
     * Return true if the relation is inverse, false otherwise.
     *
     * @return bool
     */
    public function isInverse()
    {
        return false;
    }

    /**
     * Save the model and all of its relationships.
     *
     * @param Model $model
     * @return bool
     */
    public function push(Model $model)
    {
        return $model->push();
    }
}
