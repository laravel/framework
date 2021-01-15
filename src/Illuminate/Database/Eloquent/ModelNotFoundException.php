<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Arr;

class ModelNotFoundException extends RecordsNotFoundException
{
    /**
     * Name of the affected Eloquent model.
     *
     * @var string
     */
    protected $model;

    /**
     * The affected model IDs.
     *
     * @var int|array
     */
    protected $ids;

    /**
     * Set the affected Eloquent model and instance ids.
     *
     * @param  string  $model
     * @param  int|array  $ids
     * @return $this
     */
    public function setModel($model, $ids = [])
    {
        $this->model = $model;
        $this->ids = Arr::wrap($ids);

        $this->message = "No query results for model [{$model}]";

        if (count($this->ids) > 0) {
            $this->message .= ' '.implode(', ', $this->ids);
        } else {
            $this->message .= '.';
        }

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
     * Get the affected Eloquent model IDs.
     *
     * @return int|array
     */
    public function getIds()
    {
        return $this->ids;
    }
}
