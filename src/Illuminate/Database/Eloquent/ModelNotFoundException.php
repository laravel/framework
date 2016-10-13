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
     * Ids of the affected instances
     *
     * @var integer|array
     */
    protected $instanceIds;

    /**
     * Set the affected Eloquent model and instance ids
     *
     * @param  string        $model
     * @param  integer|array $instanceIds
     * @return $this
     */
    public function setModel($model, $instanceIds)
    {
        $this->model       = $model;
        $this->instanceIds = $instanceIds;

        if (is_array($instanceIds)) {
            $instanceIds = sprintf('[%s]', implode($instanceIds, ', '));
        }

        $this->message = "No query results for model [{$model}] with id={$instanceIds}.";

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
     * Get the affected instance ids
     *
     * @return integer|array
     */
    public function getInstanceIds()
    {
        return $this->instanceId;
    }
}
