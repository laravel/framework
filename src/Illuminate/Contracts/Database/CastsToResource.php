<?php

namespace Illuminate\Contracts\Database;

interface CastsToResource
{
    /**
     * Cast the given model into a resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $model
     */
    public static function castToResource($request, $model);

    /**
     * Cast the given collection into a resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Support\Collection  $collection
     */
    public static function castCollectionToResource($request, $collection);
}
