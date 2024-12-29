<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Support\Arr;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class InvalidIdFormatException extends \RuntimeException
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
     * Set the affected Eloquent model and instance id.
     *
     * @param  class-string<TModel>  $model
     * @param  array<int, int|string>|int|string  $ids
     * @param  string|null  $field
     * @return $this
     */
    public function setModel($model, $ids = [], ?string $field = null)
    {
        $this->model = $model;
        $this->ids = Arr::wrap($ids);

        $this->message = 'Invalid key';
        if ($field !== null) {
            $this->message .= " [{$field}]";
        }

        $this->message .= " for model [{$model}]";

        if (count($this->ids) > 0) {
            $this->message .= ' '.implode(', ', $this->ids);
        }

        $this->message .= '.';

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
}
