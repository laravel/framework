<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Arr;

class ModelNotFoundException extends RecordsNotFoundException
{
    /**
     * Name of the affected Eloquent model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model;

    /**
     * The affected model IDs.
     *
     * @var array<int>|array<string>
     */
    protected $ids;

    /**
     * Set the affected Eloquent model and instance ids.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  int|array<int>|string|array<string>  $ids
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
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the affected Eloquent model IDs.
     *
     * @return array<int>|array<string>
     */
    public function getIds()
    {
        return $this->ids;
    }
}
