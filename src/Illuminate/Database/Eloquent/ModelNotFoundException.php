<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Arr;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class ModelNotFoundException extends RecordsNotFoundException
{
    /**
     * Name of the affected Eloquent model.
     *
     * @var class-string<TModel>
     */
    protected $model;

    /**
     * The affected model IDs.
     *
     * @var array<int, int|string>
     */
    protected $ids;

    /**
     * Set the affected Eloquent model and instance ids.
     *
     * @param  class-string<TModel>  $model
     * @param  array<int, int|string>|int|string  $ids
     * @return $this
     */
    public function setModel($model, $ids = [])
    {
        $this->model = $model;
        $this->ids = Arr::wrap($ids);
        $this->message = $this->message($model);

        return $this;
    }

    /**
     * Get the affected Eloquent model.
     *
     * @return class-string<TModel>
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the affected Eloquent model IDs.
     *
     * @return array<int, int|string>
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * Get the message text.
     *
     * @return string
     */
    protected function message($model)
    {
        if (config('app.debug')) {
            $ids = count($this->ids) > 0 ? ' '.implode(', ', $this->ids) : '.';

            return __('No query results for model [:model]:ids', compact('model', 'ids'));
        }

        return __('Not Found');
    }
}
