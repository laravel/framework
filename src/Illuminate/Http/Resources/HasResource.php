<?php

namespace Illuminate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

trait HasResource
{
    /**
     * Get a new resource instance for the given resource(s).
     *
     * @param mixed ...$parameters
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public static function resource(...$parameters)
    {
        return static::newResource(...$parameters)
            ?: JsonResource::resourceForModel(get_called_class(), ...$parameters);
    }

    /**
     * Create a new resource instance for the model.
     *
     * @param static|null $model
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    protected static function newResource($model = null)
    {
       //
    }

    /**
     * Get the resource representation of the model.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function toResource()
    {
        return static::resource($this);
    }
}
