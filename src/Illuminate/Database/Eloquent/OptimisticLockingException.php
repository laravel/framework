<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class OptimisticLockingException extends RuntimeException
{
    /**
     * Instance of the locked Eloquent model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Set the affected Eloquent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model   $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get the affected Eloquent model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }
}
