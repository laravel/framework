<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

trait TracksPreviousAttributes
{
    /**
     * The previous model attributes.
     *
     * @var array
     */
    protected $previous = [];

    /**
     * Begin tracking the models previous attributes.
     *
     * @return void
     */
    public static function bootTracksPreviousAttributes()
    {
        static::updating(function (Model $model) {
            $model->syncPrevious();
        });
    }

    /**
     * Sync the previous attributes.
     *
     * @return $this
     */
    public function syncPrevious()
    {
        $this->previous = $this->getRawOriginal();

        return $this;
    }

    /**
     * Get the model's attribute values prior to an update.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed|array
     */
    public function getPrevious($key = null, $default = null)
    {
        return (new static)->setRawAttributes(
            $this->previous, $sync = true
        )->getOriginalWithoutRewindingModel($key, $default);
    }

    /**
     * Get the model's raw previous attribute values.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed|array
     */
    public function getRawPrevious($key = null, $default = null)
    {
        return Arr::get($this->previous, $key, $default);
    }
}
