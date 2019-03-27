<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class OptimisticLockingException extends RuntimeException
{
    /**
     * Related model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Set related model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get related model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }
}
