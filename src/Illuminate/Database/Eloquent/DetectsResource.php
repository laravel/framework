<?php

namespace Illuminate\Database\Eloquent;

use Exception;

trait DetectsResource
{
    /**
     * Cast the given model into a resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $model
     * @return mixed
     */
    public static function castToResource($request, $model)
    {
        $class = $model->detectResourceName();

        return new $class($model);
    }

    /**
     * Cast the given paginator or collection into a resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Support\Collection  $collection
     * @return mixed
     */
    public static function castCollectionToResource($request, $collection)
    {
        $class = $collection->first()->detectResourceName('Collection');

        return new $class($collection);
    }

    /**
     * Detect the resource name for the model.
     *
     * @param  string  $suffix
     * @return string
     */
    public function detectResourceName($suffix = '')
    {
        $segments = explode('\\', get_class($this));

        $base = array_pop($segments);

        if (class_exists($class = implode('\\', $segments).'\\Http\\Resources\\'.$base.$suffix)) {
            return $class;
        }

        throw new Exception(
            "Unable to detect the resource for the [".get_class($this)."] model."
        );
    }
}
