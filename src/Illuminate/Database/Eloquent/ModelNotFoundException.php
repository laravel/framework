<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class ModelNotFoundException extends RuntimeException
{
    /**
     * Name of the affected Eloquent model.
     *
     * @var string
     */
    protected $model;

    /**
     * Id or array of ids of the affected instances
     *
     * @var integer|array
     */
    protected $instanceId;

    /**
     * Set the affected Eloquent model.
     *
     * @param  string        $model
     * @param  integer|array $instanceId
     * @return $this
     */
    public function setModel($model, $instanceId)
    {
        $this->model      = $model;
        $this->instanceId = $instanceId;

        if (is_array($instanceId)) {
            $instanceId = sprintf('[%s]', implode($instanceId, ', '));
        }

        $this->message = "No query results for model [{$model}] with id={$instanceId}.";

        return $this;
    }

    /**
     * Get the affected Eloquent model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the affected instance id or array of ids
     *
     * @return integer|array
     */
    public function getInstanceId()
    {
        return $this->instanceId;
    }
}
